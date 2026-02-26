<?php
/**
 * FILE: includes/Applicant.php
 * BU-aware Applicant data access aligned with csnk.sql (composite FKs on applicant children).
 *
 * Key alignments:
 * - applicant_documents: requires (applicant_id, business_unit_id), document_type (enum), document_type_id (nullable)
 * - applicant_reports / applicant_status_reports: require business_unit_id in your csnk.sql
 * - client_bookings: composite FKs to (applicant_id, business_unit_id)
 *
 * This class focuses on what Add Applicant needs:
 *  - create applicant
 *  - add/get documents with BU
 *  - update video fields (with optional BU guard)
 *  - list business units for SMC and pull document types by BU (country)
 *
 * You can extend with more methods similarly (status, delete, replacements, etc.) with BU guards.
 */

class Applicant
{
    /** @var mysqli */
    private $db;

    public function __construct($database)
    {
        $this->db = $database->getConnection();
    }

    /* ============================================================
     * BUSINESS UNITS & DOCUMENT TYPES
     * ============================================================ */

    /**
     * Get business units with country and agency, optional active filter.
     * Optionally filter by agency code (e.g., 'smc') and/or allowed BU IDs.
     *
     * @param bool        $activeOnly
     * @param string|null $agencyCode  e.g., 'smc' or 'csnk'
     * @param array|null  $allowedBuIds Only return these BU ids (if provided)
     * @return array
     */
    public function getAllBusinessUnits(bool $activeOnly = true, ?string $agencyCode = null, ?array $allowedBuIds = null): array
    {
        $where = [];
        $params = [];
        $types = '';

        $sql = "
            SELECT
                bu.id,
                bu.code,
                bu.name          AS bu_name,
                c.id             AS country_id,
                c.name           AS country_name,
                ag.code          AS agency_code,
                CONCAT(bu.code, ' — ', c.name) AS label
            FROM business_units bu
            JOIN countries c ON c.id = bu.country_id
            JOIN agencies  ag ON ag.id = bu.agency_id
        ";

        if ($activeOnly) {
            $where[] = "bu.active = 1";
            $where[] = "c.active = 1";
        }
        if (!empty($agencyCode)) {
            $where[] = "ag.code = ?";
            $types .= 's';
            $params[] = $agencyCode;
        }
        if (!empty($allowedBuIds)) {
            // Securely filter numeric allowed IDs
            $safeIds = array_values(array_filter(array_map('intval', $allowedBuIds), fn($v) => $v > 0));
            if (!empty($safeIds)) {
                $in = implode(',', array_fill(0, count($safeIds), '?'));
                $where[] = "bu.id IN ($in)";
                $types .= str_repeat('i', count($safeIds));
                $params = array_merge($params, $safeIds);
            }
            // If all IDs filtered to empty, don't add the IN clause (allow all)
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY bu.code";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt)
                return [];
            $this->bindByRef($stmt, $types, $params);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $rows;
        }

        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Convenience: SMC business units only, with optional allowed BU filter.
     */
    public function getBusinessUnitsByAgency(string $agencyCode, bool $activeOnly = true, ?array $allowedBuIds = null): array
    {
        return $this->getAllBusinessUnits($activeOnly, $agencyCode, $allowedBuIds);
    }

    /**
     * Return document_types for a BU via its country (code, label, id, is_required, active).
     * If BU not found or no doc types, returns [].
     *
     * @param int $businessUnitId
     * @return array
     */
    public function getDocumentTypesForBu(int $businessUnitId): array
    {
        // Resolve BU -> country_id
        $stmt = $this->db->prepare("SELECT country_id FROM business_units WHERE id = ? LIMIT 1");
        if (!$stmt)
            return [];
        $stmt->bind_param("i", $businessUnitId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$row)
            return [];
        $countryId = (int) $row['country_id'];

        $stmt2 = $this->db->prepare("
            SELECT id, code, label, is_required, active
            FROM document_types
            WHERE country_id = ?
              AND active = 1
            ORDER BY id ASC
        ");
        if (!$stmt2)
            return [];
        $stmt2->bind_param("i", $countryId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $rows = $res2 ? $res2->fetch_all(MYSQLI_ASSOC) : [];
        $stmt2->close();

        // Normalize keys
        foreach ($rows as &$r) {
            $r['id'] = isset($r['id']) ? (int) $r['id'] : null;
            $r['label'] = (string) ($r['label'] ?? $r['code'] ?? '');
            $r['code'] = (string) ($r['code'] ?? '');
        }
        unset($r);
        return $rows;
    }

    /**
     * Get one document_type.id by BU + code, or null if not found.
     */
    public function getDocumentTypeIdByCode(int $businessUnitId, string $code): ?int
    {
        $stmt = $this->db->prepare("
            SELECT dt.id
            FROM document_types dt
            JOIN business_units bu ON bu.country_id = dt.country_id
            WHERE bu.id = ? AND dt.code = ? AND dt.active = 1
            LIMIT 1
        ");
        if (!$stmt)
            return null;
        $stmt->bind_param("is", $businessUnitId, $code);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ? (int) $row['id'] : null;
    }

    /* ============================================================
     * APPLICANTS
     * ============================================================ */

    /**
     * Create applicant (includes specialization_skills JSON and business_unit_id).
     * Returns new ID or false on failure.
     *
     * @param array $data
     * @return int|false
     */
    public function create(array $data)
    {
        // Normalize daily_rate from input (optional, not always provided from SMC UI)
        $dailyRate = null;
        if (isset($data['daily_rate']) && $data['daily_rate'] !== '') {
            $dailyRate = round((float) $data['daily_rate'], 2);
        }

        // Values
        $first = $data['first_name'] ?? null;
        $middle = $data['middle_name'] ?? null;
        $last = $data['last_name'] ?? null;
        $suffix = $data['suffix'] ?? null;
        $phone = $data['phone_number'] ?? null;
        $alt = $data['alt_phone_number'] ?? null;
        $email = $data['email'] ?? null;
        $dob = $data['date_of_birth'] ?? null;
        $addr = $data['address'] ?? null;

        $educA = $data['educational_attainment'] ?? null; // JSON string
        $workH = $data['work_history'] ?? null;           // JSON string
        $pref = $data['preferred_location'] ?? null;     // JSON string
        $langs = $data['languages'] ?? null;              // JSON string
        $skills = $data['specialization_skills'] ?? null;  // JSON string

        $pic = $data['picture'] ?? null;
        $status = $data['status'] ?? 'pending';
        $empTy = $data['employment_type'] ?? null;
        $eduLv = $data['education_level'] ?? null;
        $years = isset($data['years_experience']) ? (int) $data['years_experience'] : 0;
        $createdBy = isset($data['created_by']) ? (int) $data['created_by'] : null;

        $businessUnitId = isset($data['business_unit_id']) ? (int) $data['business_unit_id'] : null;

        if ($businessUnitId === null) {
            // BU is mandatory in your schema (FK), refuse to create without it
            return false;
        }

        if ($dailyRate === null) {
            $sql = "INSERT INTO applicants (
                        first_name, middle_name, last_name, suffix,
                        phone_number, alt_phone_number, email, date_of_birth, address,
                        educational_attainment, work_history, daily_rate, preferred_location, languages, specialization_skills,
                        picture, status, employment_type, education_level, years_experience, created_by, business_unit_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (create NULL rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "ssssssssssssssssssiii",
                $first,
                $middle,
                $last,
                $suffix,
                $phone,
                $alt,
                $email,
                $dob,
                $addr,
                $educA,
                $workH,
                $pref,
                $langs,
                $skills,
                $pic,
                $status,
                $empTy,
                $eduLv,
                $years,
                $createdBy,
                $businessUnitId
            );
        } else {
            $sql = "INSERT INTO applicants (
                        first_name, middle_name, last_name, suffix,
                        phone_number, alt_phone_number, email, date_of_birth, address,
                        educational_attainment, work_history, daily_rate, preferred_location, languages, specialization_skills,
                        picture, status, employment_type, education_level, years_experience, created_by, business_unit_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (create with rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "sssssssssss" . "d" . "sssssss" . "iii",
                $first,
                $middle,
                $last,
                $suffix,
                $phone,
                $alt,
                $email,
                $dob,
                $addr,
                $educA,
                $workH,
                $dailyRate,
                $pref,
                $langs,
                $skills,
                $pic,
                $status,
                $empTy,
                $eduLv,
                $years,
                $createdBy,
                $businessUnitId
            );
        }

        $ok = $stmt->execute();
        if (!$ok) {
            error_log('Execute failed (create): ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $newId = (int) $this->db->insert_id;
        $stmt->close();
        return $newId ?: false;
    }

    /**
     * Update video fields for an applicant (optional BU guard).
     * If $businessUnitId is provided, ensure the applicant belongs to that BU.
     */
    public function updateVideoFields(int $id, array $videoData, ?int $businessUnitId = null): bool
    {
        if ($businessUnitId !== null && !$this->isApplicantInBusinessUnit($id, $businessUnitId)) {
            return false;
        }

        $sql = "UPDATE applicants SET
                    video_url = ?, video_provider = ?, video_type = ?, 
                    video_title = ?, video_thumbnail_url = ?, video_duration_seconds = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            return false;

        $video_url = $videoData['video_url'] ?? null;
        $provider = $videoData['video_provider'] ?? null;
        $vtype = $videoData['video_type'] ?? 'iframe';
        $vtitle = $videoData['video_title'] ?? null;
        $thumb = $videoData['video_thumbnail_url'] ?? null;
        $duration = isset($videoData['video_duration_seconds']) && $videoData['video_duration_seconds'] !== null
            ? (int) $videoData['video_duration_seconds'] : null;

        // Duration and id are ints; mysqli requires explicit type mapping
        // We'll cast duration to int or null and use 'ii' at the end.
        $stmt->bind_param(
            "sssssii",
            $video_url,
            $provider,
            $vtype,
            $vtitle,
            $thumb,
            $duration,
            $id
        );

        return $stmt->execute();
    }

    /**
     * Check if applicant belongs to the specified business unit.
     */
    public function isApplicantInBusinessUnit(int $applicantId, int $businessUnitId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM applicants WHERE id = ? AND business_unit_id = ? LIMIT 1");
        if (!$stmt)
            return false;
        $stmt->bind_param("ii", $applicantId, $businessUnitId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return (bool) $row;
    }

    /**
     * Get single applicant; if BU provided, restrict to that BU.
     */
    public function getById(int $id, ?int $businessUnitId = null): ?array
    {
        if ($businessUnitId !== null) {
            $stmt = $this->db->prepare("SELECT * FROM applicants WHERE id = ? AND business_unit_id = ? LIMIT 1");
            if (!$stmt)
                return null;
            $stmt->bind_param("ii", $id, $businessUnitId);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM applicants WHERE id = ? LIMIT 1");
            if (!$stmt)
                return null;
            $stmt->bind_param("i", $id);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Get all applicants (optionally by a single status and BU).
     * Excludes blacklisted (active) and soft-deleted.
     */
    public function getAll(?string $status = null, ?int $businessUnitId = null): array
    {
        $where = [];
        $types = '';
        $params = [];

        if ($businessUnitId !== null && $businessUnitId > 0) {
            $where[] = "a.business_unit_id = ?";
            $types .= "i";
            $params[] = $businessUnitId;
        }

        $where[] = "a.deleted_at IS NULL";
        $where[] = "NOT EXISTS (
            SELECT 1 FROM blacklisted_applicants b
            WHERE b.applicant_id = a.id AND b.is_active = 1
        )";

        if ($status !== null) {
            $where[] = "a.status = ?";
            $types .= "s";
            $params[] = $status;
        }

        $sql = "SELECT a.* FROM applicants a WHERE " . implode(" AND ", $where) . " ORDER BY a.created_at DESC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (getAll): ' . $this->db->error);
                return [];
            }
            $this->bindByRef($stmt, $types, $params);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $rows;
        } else {
            $res = $this->db->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }
    }

    /* ============================================================
     * DOCUMENTS (BU-SAFE)
     * ============================================================ */

    /**
     * Get documents for an applicant within a BU.
     */
    public function getDocuments(int $applicantId, int $businessUnitId): array
    {
        $sql = "SELECT *
                FROM applicant_documents
                WHERE applicant_id = ?
                  AND business_unit_id = ?
                ORDER BY uploaded_at ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            return [];
        $stmt->bind_param("ii", $applicantId, $businessUnitId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    /**
     * Add document for an applicant within a BU.
     * - document_type_id is optional (nullable in DB)
     * - document_type must be one of the enum strings in your schema
     */
    public function addDocument(int $applicantId, int $businessUnitId, string $documentTypeCode, string $filePath, ?int $documentTypeId = null): bool
    {
        // Guard: ensure applicant belongs to BU
        if (!$this->isApplicantInBusinessUnit($applicantId, $businessUnitId)) {
            return false;
        }

        $sql = "INSERT INTO applicant_documents
                    (applicant_id, business_unit_id, document_type_id, document_type, file_path)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            return false;

        // document_type_id is nullable: use 'i' and pass null via bind_param by converting to PHP null.
        if ($documentTypeId !== null) {
            $stmt->bind_param("iiiss", $applicantId, $businessUnitId, $documentTypeId, $documentTypeCode, $filePath);
        } else {
            // When passing NULL for an 'i' param, we still pass null var.
            $null = null;
            $stmt->bind_param("iiiss", $applicantId, $businessUnitId, $null, $documentTypeCode, $filePath);
        }

        return $stmt->execute();
    }

    /**
     * Delete one document by id, but only within BU (safety).
     */
    public function deleteDocument(int $documentId, int $businessUnitId): bool
    {
        // Delete only if the doc row is in this BU
        $sql = "DELETE FROM applicant_documents WHERE id = ? AND business_unit_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            return false;
        $stmt->bind_param("ii", $documentId, $businessUnitId);
        return $stmt->execute();
    }

    /**
     * Delete by type within BU (e.g., when replacing or re-uploading a doc category).
     */
    public function deleteDocumentsByType(int $applicantId, int $businessUnitId, string $documentTypeCode): bool
    {
        $sql = "DELETE FROM applicant_documents WHERE applicant_id = ? AND business_unit_id = ? AND document_type = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            return false;
        $stmt->bind_param("iis", $applicantId, $businessUnitId, $documentTypeCode);
        return $stmt->execute();
    }

    /* ============================================================
     * UTILITIES
     * ============================================================ */

    /**
     * Bind params by reference for mysqli prepared statements.
     */
    private function bindByRef(mysqli_stmt $stmt, string $types, array $values): void
    {
        $refs = [];
        $refs[] = &$types;
        foreach ($values as $k => $v) {
            $values[$k] = $v;
            $refs[] = &$values[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    /* ============================================================
     * COUNTRY FILTERING (for SMC international applicants)
     * ============================================================ */

    /**
     * Get countries with applicant counts for SMC (excludes Philippines).
     * This is used for the country filter on the applicants list page.
     * 
     * @param int|null $buId BU scope - null for unscoped (super admin/employee)
     * @param string $status Filter by status ('all' or specific status)
     * @param string $q Search query (searches name, email, phone)
     * @param bool $notDeleted Exclude deleted applicants (default true)
     * @param bool $notBlacklisted Exclude blacklisted applicants (default true)
     * @return array Array of countries with counts: ['id', 'name', 'count']
     */
    public function getCountriesWithCounts(
        ?int $buId = null,
        string $status = 'all',
        string $q = '',
        bool $notDeleted = true,
        bool $notBlacklisted = true
    ): array {
        // Get all countries except Philippines (id=1) for SMC
        $sql = "
            SELECT 
                c.id,
                c.name AS country_name,
                c.iso2
            FROM countries c
            WHERE c.active = 1
              AND c.id != 1
            ORDER BY c.name ASC
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        $countries = $res->fetch_all(MYSQLI_ASSOC);

        // Build filtered count query with aligned WHERE clauses
        $countSql = "
            SELECT 
                bu.country_id,
                COUNT(a.id) AS applicant_count
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            WHERE 1=1
        ";

        // BU scope: (:buId IS NULL OR a.bu_id = :buId)
        if ($buId !== null && $buId > 0) {
            $countSql .= " AND a.business_unit_id = " . (int) $buId;
        }

        // Status filter: (:status = 'all' OR a.status = :status)
        if ($status !== 'all') {
            $statusEsc = $this->db->real_escape_string($status);
            $countSql .= " AND a.status = '{$statusEsc}'";
        }

        // Visibility flags
        if ($notDeleted) {
            $countSql .= " AND a.deleted_at IS NULL";
        }

        if ($notBlacklisted) {
            $countSql .= " AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
            )";
        }

        // Search query: (:q = '' OR a.name LIKE :q OR a.email LIKE :q OR a.phone LIKE :q)
        if ($q !== '') {
            $qEsc = '%' . $this->db->real_escape_string($q) . '%';
            $countSql .= " AND (a.first_name LIKE '{$qEsc}' OR a.last_name LIKE '{$qEsc}' OR a.email LIKE '{$qEsc}' OR a.phone_number LIKE '{$qEsc}')";
        }

        $countSql .= " GROUP BY bu.country_id";

        $countRes = $this->db->query($countSql);
        $counts = [];
        if ($countRes) {
            while ($row = $countRes->fetch_assoc()) {
                $counts[(int) $row['country_id']] = (int) $row['applicant_count'];
            }
        }

        // Merge counts into countries
        $result = [];
        foreach ($countries as $c) {
            $result[] = [
                'id' => (int) $c['id'],
                'name' => $c['country_name'],
                'iso2' => $c['iso2'],
                'count' => $counts[$c['id']] ?? 0
            ];
        }

        return $result;
    }

    /**
     * Get applicants with comprehensive filtering + pagination.
     * Uses aligned WHERE clauses with getCountriesWithCounts().
     * 
     * @param int|null $buId BU scope - null for unscoped (super admin/employee)
     * @param int|null $countryId Filter by country ID (null for 'all')
     * @param string $status Filter by status ('all' or specific status)
     * @param string $q Search query (searches name, email, phone)
     * @param bool $notDeleted Exclude deleted applicants (default true)
     * @param bool $notBlacklisted Exclude blacklisted applicants (default true)
     * @param int $page Page number (1-based)
     * @param int $pageSize Number of results per page
     * @return array Array of applicant records
     */
    public function getApplicants(
        ?int $buId = null,
        ?int $countryId = null,
        string $status = 'all',
        string $q = '',
        bool $notDeleted = true,
        bool $notBlacklisted = true,
        int $page = 1,
        int $pageSize = 25
    ): array {
        // Build WHERE clause with aligned logic
        $where = "1=1";

        // BU scope: (:buId IS NULL OR a.bu_id = :buId)
        if ($buId !== null && $buId > 0) {
            $where .= " AND a.business_unit_id = " . (int) $buId;
        }

        // Country filter: (:countryId IS NULL OR a.country_id = :countryId)
        // Note: We filter by country_id via business_unit mapping
        if ($countryId !== null && $countryId > 0) {
            $where .= " AND bu.country_id = " . (int) $countryId;
        }

        // Status filter: (:status = 'all' OR a.status = :status)
        if ($status !== 'all') {
            $statusEsc = $this->db->real_escape_string($status);
            $where .= " AND a.status = '{$statusEsc}'";
        }

        // Visibility flags
        if ($notDeleted) {
            $where .= " AND a.deleted_at IS NULL";
        }

        if ($notBlacklisted) {
            $where .= " AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
            )";
        }

        // Search query: (:q = '' OR a.name LIKE :q OR a.email LIKE :q OR a.phone LIKE :q)
        if ($q !== '') {
            $qEsc = '%' . $this->db->real_escape_string($q) . '%';
            $where .= " AND (a.first_name LIKE '{$qEsc}' OR a.last_name LIKE '{$qEsc}' OR a.email LIKE '{$qEsc}' OR a.phone_number LIKE '{$qEsc}')";
        }

        // Calculate offset for pagination
        $offset = ($page - 1) * $pageSize;

        // Build the full query with JOIN to get country info
        $sql = "
            SELECT 
                a.*,
                bu.country_id,
                c.name AS country_name
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN countries c ON c.id = bu.country_id
            WHERE {$where}
            ORDER BY a.created_at DESC
            LIMIT " . (int) $pageSize . " OFFSET " . (int) $offset . "
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            error_log('getApplicants query failed: ' . $this->db->error);
            return [];
        }

        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get total count of applicants matching filters (for pagination).
     * 
     * @param int|null $buId BU scope
     * @param int|null $countryId Filter by country ID
     * @param string $status Filter by status
     * @param string $q Search query
     * @param bool $notDeleted Exclude deleted
     * @param bool $notBlacklisted Exclude blacklisted
     * @return int Total count
     */
    public function getApplicantsCount(
        ?int $buId = null,
        ?int $countryId = null,
        string $status = 'all',
        string $q = '',
        bool $notDeleted = true,
        bool $notBlacklisted = true
    ): int {
        // Build WHERE clause with aligned logic
        $where = "1=1";

        if ($buId !== null && $buId > 0) {
            $where .= " AND a.business_unit_id = " . (int) $buId;
        }

        if ($countryId !== null && $countryId > 0) {
            $where .= " AND bu.country_id = " . (int) $countryId;
        }

        if ($status !== 'all') {
            $statusEsc = $this->db->real_escape_string($status);
            $where .= " AND a.status = '{$statusEsc}'";
        }

        if ($notDeleted) {
            $where .= " AND a.deleted_at IS NULL";
        }

        if ($notBlacklisted) {
            $where .= " AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
            )";
        }

        if ($q !== '') {
            $qEsc = '%' . $this->db->real_escape_string($q) . '%';
            $where .= " AND (a.first_name LIKE '{$qEsc}' OR a.last_name LIKE '{$qEsc}' OR a.email LIKE '{$qEsc}' OR a.phone_number LIKE '{$qEsc}')";
        }

        $sql = "
            SELECT COUNT(*) as total
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN countries c ON c.id = bu.country_id
            WHERE {$where}
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            return 0;
        }

        $row = $res->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Get all business units with their country info for SMC (excluding Philippines).
     * This is used to map country_id to business_unit_id for filtering.
     * 
     * @return array Array of BUs: ['id', 'country_id', 'country_name', 'code', 'name']
     */
    public function getBusinessUnitsByCountry(?int $countryId = null): array
    {
        $sql = "
            SELECT 
                bu.id AS business_unit_id,
                bu.code,
                bu.name AS bu_name,
                c.id AS country_id,
                c.name AS country_name,
                c.iso2
            FROM business_units bu
            JOIN countries c ON c.id = bu.country_id
            WHERE bu.active = 1
              AND c.active = 1
              AND c.id != 1  -- Exclude Philippines (for SMC)
        ";

        if ($countryId !== null && $countryId > 0) {
            $sql .= " AND c.id = " . (int) $countryId;
        }

        $sql .= " ORDER BY c.name ASC, bu.code ASC";

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        return $res->fetch_all(MYSQLI_ASSOC);
    }
}

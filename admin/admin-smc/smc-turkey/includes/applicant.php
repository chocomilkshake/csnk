<?php
/**
 * FILE: includes/Applicant.php
 * BU-aware Applicant data access (SMC-scoped).
 *
 * SMC Scope:
 * - All read queries are forced to agency = 'smc' by joining business_units -> agencies and filtering ag.code='smc'.
 *
 * Key alignments:
 * - applicant_documents: requires (applicant_id, business_unit_id), document_type (enum), document_type_id (nullable)
 * - applicant_reports / applicant_status_reports: require business_unit_id in your schema
 * - client_bookings: composite FKs to (applicant_id, business_unit_id)
 *
 * Features:
 *  - create / update applicant
 *  - add/get/delete documents with BU guard
 *  - update video fields (with optional BU guard)
 *  - business units / country filters (SMC-only)
 *  - lifecycle helpers (soft delete/restore/status)
 *  - statistics & latest booking (SMC-only)
 *  - replacement workflow (selection → assign) with BU-safe booking reassignment
 */

class Applicant
{
    /** @var mysqli */
    private $db;

    /** Agency code for this class scope */
    private const AGENCY_CODE = 'smc';

    public function __construct($database)
    {
        $this->db = $database->getConnection();
    }

    /* ============================================================
     * BUSINESS UNITS & DOCUMENT TYPES
     * ============================================================ */

    /**
     * Get business units with country and agency, optional active filter.
     * Optionally filter by allowed BU IDs.
     *
     * NOTE: Default agency is SMC.
     *
     * @param bool        $activeOnly
     * @param string|null $agencyCode  (defaults to 'smc')
     * @param array|null  $allowedBuIds Only return these BU ids (if provided)
     * @return array
     */
    public function getAllBusinessUnits(bool $activeOnly = true, ?string $agencyCode = self::AGENCY_CODE, ?array $allowedBuIds = null): array
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
            $safeIds = array_values(array_filter(array_map('intval', $allowedBuIds), fn($v) => $v > 0));
            if (!empty($safeIds)) {
                $in = implode(',', array_fill(0, count($safeIds), '?'));
                $where[] = "bu.id IN ($in)";
                $types .= str_repeat('i', count($safeIds));
                $params = array_merge($params, $safeIds);
            }
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
    public function getBusinessUnitsByAgency(string $agencyCode = self::AGENCY_CODE, bool $activeOnly = true, ?array $allowedBuIds = null): array
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
     * BU HELPERS
     * ============================================================ */

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
     * Update applicant's business unit (country assignment).
     * NOTE: In SMC, consider downstream composite FKs before using this.
     */
    public function updateBusinessUnit(int $applicantId, int $businessUnitId): bool
    {
        $stmt = $this->db->prepare("UPDATE applicants SET business_unit_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $businessUnitId, $applicantId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /* ============================================================
     * APPLICANTS (CREATE / UPDATE / FETCH)
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
        // Normalize daily_rate from input (optional)
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
        $pref  = $data['preferred_location'] ?? null;     // JSON string
        $langs = $data['languages'] ?? null;              // JSON string
        $skills= $data['specialization_skills'] ?? null;  // JSON string

        $pic = $data['picture'] ?? null;
        $status = $data['status'] ?? 'pending';
        $empTy = $data['employment_type'] ?? null;
        $eduLv = $data['education_level'] ?? null;
        $years = isset($data['years_experience']) ? (int) $data['years_experience'] : 0;
        $createdBy = isset($data['created_by']) ? (int) $data['created_by'] : null;

        $businessUnitId = isset($data['business_unit_id']) ? (int) $data['business_unit_id'] : null;
        $countryId = isset($data['country_id']) ? (int) $data['country_id'] : null;

        if ($businessUnitId === null) {
            // BU is mandatory in your schema (FK), refuse to create without it
            return false;
        }

        if ($dailyRate === null) {
            $sql = "INSERT INTO applicants (
                        first_name, middle_name, last_name, suffix,
                        phone_number, alt_phone_number, email, date_of_birth, address,
                        educational_attainment, work_history, daily_rate, preferred_location, languages, specialization_skills,
                        picture, status, employment_type, education_level, years_experience, created_by, business_unit_id, country_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (create NULL rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "ssssssssssssssssssiiii",
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
                $businessUnitId,
                $countryId
            );
        } else {
            $sql = "INSERT INTO applicants (
                        first_name, middle_name, last_name, suffix,
                        phone_number, alt_phone_number, email, date_of_birth, address,
                        educational_attainment, work_history, daily_rate, preferred_location, languages, specialization_skills,
                        picture, status, employment_type, education_level, years_experience, created_by, business_unit_id, country_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (create with rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "sssssssssss" . "d" . "sssssss" . "iiii",
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
                $businessUnitId,
                $countryId
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
     * Update applicant (includes specialization_skills JSON and business_unit_id).
     * Mirrors CSNK update but preserved for SMC schema (no BU guard in signature for compatibility).
     */
    public function update($id, $data)
    {
        // Normalize daily_rate
        $dailyRate = null;
        if (isset($data['daily_rate']) && $data['daily_rate'] !== '') {
            $dailyRate = round((float) $data['daily_rate'], 2);
        }

        // Common values
        $first  = $data['first_name'] ?? null;
        $middle = $data['middle_name'] ?? null;
        $last   = $data['last_name'] ?? null;
        $suffix = $data['suffix'] ?? null;
        $phone  = $data['phone_number'] ?? null;
        $alt    = $data['alt_phone_number'] ?? null;
        $email  = $data['email'] ?? null;
        $dob    = $data['date_of_birth'] ?? null;
        $addr   = $data['address'] ?? null;
        $educA  = $data['educational_attainment'] ?? null; // JSON
        $workH  = $data['work_history'] ?? null;           // JSON
        $pref   = $data['preferred_location'] ?? null;     // JSON
        $langs  = $data['languages'] ?? null;              // JSON
        $skills = $data['specialization_skills'] ?? null;  // JSON
        $pic    = $data['picture'] ?? null;
        $status = $data['status'] ?? 'pending';
        $empTy  = $data['employment_type'] ?? null;
        $eduLv  = $data['education_level'] ?? null;
        $years  = isset($data['years_experience']) ? (int) $data['years_experience'] : 0;

        // Business Unit (Country)
        $businessUnitId = isset($data['business_unit_id']) && $data['business_unit_id'] !== ''
            ? (int) $data['business_unit_id']
            : null;
        $countryId = isset($data['country_id']) && $data['country_id'] !== ''
            ? (int) $data['country_id']
            : null;

        if ($dailyRate === null) {
            // daily_rate to NULL
            $sql = "UPDATE applicants SET
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    phone_number = ?, alt_phone_number = ?, email = ?, date_of_birth = ?, address = ?,
                    educational_attainment = ?, work_history = ?, daily_rate = NULL, preferred_location = ?, languages = ?, specialization_skills = ?,
                    picture = ?, status = ?, employment_type = ?, education_level = ?, years_experience = ?, business_unit_id = ?, country_id = ?
                WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (update NULL rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "ssssssssssssssssssiiii",
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
                $businessUnitId,
                $countryId,
                $id
            );
        } else {
            // daily_rate bound as double
            $sql = "UPDATE applicants SET
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    phone_number = ?, alt_phone_number = ?, email = ?, date_of_birth = ?, address = ?,
                    educational_attainment = ?, work_history = ?, daily_rate = ?, preferred_location = ?, languages = ?, specialization_skills = ?,
                    picture = ?, status = ?, employment_type = ?, education_level = ?, years_experience = ?, business_unit_id = ?, country_id = ?
                WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (update with rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "sssssssssss" . "d" . "sssssss" . "iiii",
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
                $businessUnitId,
                $countryId,
                $id
            );
        }

        $ok = $stmt->execute();
        if (!$ok) {
            error_log('Execute failed (update): ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }

    /**
     * Get single applicant; if BU provided, restrict to that BU.
     * SMC-scoped.
     */
    public function getById(int $id, ?int $businessUnitId = null): ?array
    {
        if ($businessUnitId !== null) {
            $stmt = $this->db->prepare("
                SELECT a.*
                FROM applicants a
                JOIN business_units bu ON bu.id = a.business_unit_id
                JOIN agencies ag ON ag.id = bu.agency_id
                WHERE a.id = ? AND a.business_unit_id = ? AND ag.code = ?
                LIMIT 1
            ");
            if (!$stmt)
                return null;
            $ag = self::AGENCY_CODE;
            $stmt->bind_param("iis", $id, $businessUnitId, $ag);
        } else {
            $stmt = $this->db->prepare("
                SELECT a.*
                FROM applicants a
                JOIN business_units bu ON bu.id = a.business_unit_id
                JOIN agencies ag ON ag.id = bu.agency_id
                WHERE a.id = ? AND ag.code = ?
                LIMIT 1
            ");
            if (!$stmt)
                return null;
            $ag = self::AGENCY_CODE;
            $stmt->bind_param("is", $id, $ag);
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
     * SMC-scoped.
     */
    public function getAll(?string $status = null, ?int $businessUnitId = null): array
    {
        $where = [];
        $types = 's'; // for agency code
        $params = [self::AGENCY_CODE];

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

        $sql = "
            SELECT a.*
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
            WHERE ag.code = ? AND " . implode(" AND ", $where) . "
            ORDER BY a.created_at DESC
        ";

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
    }

    /**
     * NEW (Client-facing): Get list for public site.
     * - Returns only non-deleted, non-approved applicants
     *   i.e., status IN ('pending','on_process').
     * - Ordered by created_at DESC.
     * SMC-scoped.
     */
    public function getAllForPublic(): array
    {
        $ag = self::AGENCY_CODE;
        $sql = "
            SELECT a.*
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
            WHERE ag.code = ?
              AND a.deleted_at IS NULL
              AND a.status IN ('pending','on_process')
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
            ORDER BY a.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("s", $ag);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get all soft-deleted applicants (excludes actively blacklisted).
     * SMC-scoped.
     */
    public function getDeleted(): array
    {
        $ag = self::AGENCY_CODE;
        $sql = "
            SELECT a.*
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
            WHERE ag.code = ?
              AND a.deleted_at IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
            ORDER BY a.deleted_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("s", $ag);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* ============================================================
     * VIDEO FIELDS
     * ============================================================ */

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
        $provider  = $videoData['video_provider'] ?? null;
        $vtype     = $videoData['video_type'] ?? 'iframe';
        $vtitle    = $videoData['video_title'] ?? null;
        $thumb     = $videoData['video_thumbnail_url'] ?? null;
        $duration  = isset($videoData['video_duration_seconds']) && $videoData['video_duration_seconds'] !== null
            ? (int) $videoData['video_duration_seconds'] : null;

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

        if ($documentTypeId !== null) {
            $stmt->bind_param("iiiss", $applicantId, $businessUnitId, $documentTypeId, $documentTypeCode, $filePath);
        } else {
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
     * LIFECYCLE HELPERS
     * ============================================================ */

    public function softDelete($id): bool
    {
        $stmt = $this->db->prepare("UPDATE applicants SET deleted_at = NOW(), status = 'deleted' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function restore($id): bool
    {
        $stmt = $this->db->prepare("UPDATE applicants SET deleted_at = NULL, status = 'pending' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function permanentDelete($id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM applicants WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateStatus($id, $status): bool
    {
        $stmt = $this->db->prepare("UPDATE applicants SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    /** Convenience for booking workflow */
    public function markOnProcess(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE applicants SET status = 'on_process', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /* ============================================================
     * STATISTICS & LATEST BOOKING (SMC-SCOPED)
     * ============================================================ */

    public function getStatistics(): array
    {
        $stats = [];
        $ag = self::AGENCY_CODE;

        $sqlBase = "
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
            WHERE ag.code = ?
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ";

        // total
        $stmt = $this->db->prepare("SELECT COUNT(*) as total " . $sqlBase . " AND a.deleted_at IS NULL");
        $stmt->bind_param("s", $ag);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stats['total'] = $row['total'] ?? 0;
        $stmt->close();

        // pending
        $stmt = $this->db->prepare("SELECT COUNT(*) as pending " . $sqlBase . " AND a.deleted_at IS NULL AND a.status = 'pending'");
        $stmt->bind_param("s", $ag);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stats['pending'] = $row['pending'] ?? 0;
        $stmt->close();

        // on_process
        $stmt = $this->db->prepare("SELECT COUNT(*) as on_process " . $sqlBase . " AND a.deleted_at IS NULL AND a.status = 'on_process'");
        $stmt->bind_param("s", $ag);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stats['on_process'] = $row['on_process'] ?? 0;
        $stmt->close();

        // deleted
        $stmt = $this->db->prepare("SELECT COUNT(*) as deleted " . $sqlBase . " AND a.deleted_at IS NOT NULL");
        $stmt->bind_param("s", $ag);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stats['deleted'] = $row['deleted'] ?? 0;
        $stmt->close();

        return $stats;
    }

    /**
     * Get latest booking id for applicant (BU-aware).
     * For SMC composite FK, we ensure booking matches applicant's BU and SMC scope via getById().
     */
    public function getLatestBookingIdForApplicant(int $applicantId): ?int
    {
        $app = $this->getById($applicantId);
        if (!$app) return null;
        $buId = (int) ($app['business_unit_id'] ?? 0);
        if ($buId <= 0) return null;

        $sql = "SELECT id 
                FROM client_bookings 
                WHERE applicant_id = ? AND business_unit_id = ?
                ORDER BY created_at DESC 
                LIMIT 1";
        if (!$stmt = $this->db->prepare($sql))
            return null;
        $stmt->bind_param("ii", $applicantId, $buId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Get all "on_process" applicants with their latest client booking (if any).
     * SMC-scoped; BU-aware join to client_bookings (composite).
     */
    public function getOnProcessWithLatestBooking(): array
    {
        $ag = self::AGENCY_CODE;
        $sql = "
            SELECT
                a.*,
                cb.id                AS booking_id,
                cb.appointment_type,
                cb.appointment_date,
                cb.appointment_time,
                cb.client_first_name,
                cb.client_middle_name,
                cb.client_last_name,
                cb.client_phone,
                cb.client_email,
                cb.client_address,
                cb.status            AS booking_status,
                cb.created_at        AS booking_created_at
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
            LEFT JOIN (
                SELECT c1.*
                FROM client_bookings c1
                INNER JOIN (
                    SELECT applicant_id, business_unit_id, MAX(created_at) AS max_created
                    FROM client_bookings
                    GROUP BY applicant_id, business_unit_id
                ) t ON t.applicant_id = c1.applicant_id 
                   AND t.business_unit_id = c1.business_unit_id
                   AND t.max_created = c1.created_at
            ) cb ON cb.applicant_id = a.id
                AND cb.business_unit_id = a.business_unit_id
            WHERE ag.code = ?
              AND a.deleted_at IS NULL
              AND a.status = 'on_process'
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
            ORDER BY a.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("s", $ag);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* ============================================================
     * REPLACEMENT FEATURE — NEW METHODS (BU-SAFE for SMC)
     * ============================================================ */

    /** Ensure table applicant_replacements exists (safe to call many times) */
    private function ensureApplicantReplacementsTable(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS `applicant_replacements` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `original_applicant_id` INT(10) UNSIGNED NOT NULL,
          `replacement_applicant_id` INT(10) UNSIGNED DEFAULT NULL,
          `client_booking_id` INT(10) UNSIGNED DEFAULT NULL,
          `reason` ENUM('AWOL','Client Left','Not Finished Contract','Performance Issue','Other') NOT NULL,
          `report_text` TEXT NOT NULL,
          `attachments_json` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments_json`)),
          `status` ENUM('selection','assigned','cancelled') NOT NULL DEFAULT 'selection',
          `created_by` INT(10) UNSIGNED DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `assigned_at` DATETIME DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_ar_original_applicant_id` (`original_applicant_id`),
          KEY `idx_ar_replacement_applicant_id` (`replacement_applicant_id`),
          KEY `idx_ar_client_booking_id` (`client_booking_id`),
          KEY `idx_ar_status` (`status`),
          CONSTRAINT `fk_ar_original_applicant` FOREIGN KEY (`original_applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_ar_replacement_applicant` FOREIGN KEY (`replacement_applicant_id`) REFERENCES `applicants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_ar_client_booking` FOREIGN KEY (`client_booking_id`) REFERENCES `client_bookings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_ar_created_by_admin` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        try {
            $this->db->query($sql);
        } catch (\Throwable $e) { /* silent */ }
    }

    /** Helper: safe JSON decode to array */
    private function decodeJsonArray($val): array
    {
        if ($val === null || $val === '' || $val === '[]')
            return [];
        $arr = json_decode((string) $val, true);
        return is_array($arr) ? $arr : [];
    }

    /** Normalizes array of strings (trim, lowercase, unique) */
    private function normalizeStringArray(array $arr): array
    {
        $out = [];
        foreach ($arr as $v) {
            $s = strtolower(trim((string) $v));
            if ($s !== '' && !in_array($s, $out, true)) {
                $out[] = $s;
            }
        }
        return $out;
    }

    /** Count overlap items between two string arrays (case-insensitive) */
    private function overlapCount(array $a, array $b): int
    {
        $a = $this->normalizeStringArray($a);
        $b = $this->normalizeStringArray($b);
        return count(array_intersect($a, $b));
    }

    /** Bind params by reference for mysqli prepared statements. */
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

    /**
     * Compute similarity score for candidate against original.
     * score = (2 * skill_overlap) + (1 * city_overlap)
     */
    private function computeSimilarityScore(array $original, array $candidate): int
    {
        $origSkills = $this->decodeJsonArray($original['specialization_skills'] ?? '[]');
        $origCities = $this->decodeJsonArray($original['preferred_location'] ?? '[]');
        $candSkills = $this->decodeJsonArray($candidate['specialization_skills'] ?? '[]');
        $candCities = $this->decodeJsonArray($candidate['preferred_location'] ?? '[]');

        $skillOverlap = $this->overlapCount($origSkills, $candSkills);
        $cityOverlap  = $this->overlapCount($origCities, $candCities);

        return (2 * $skillOverlap) + (1 * $cityOverlap);
    }

    /**
     * Return pending applicants sorted by similarity to original (desc).
     * Excludes deleted and active blacklisted.
     * SMC-scoped (and BU-restricted to the original's BU).
     */
    public function searchPendingCandidatesForReplacement(int $originalApplicantId, int $limit = 50): array
    {
        $original = $this->getById($originalApplicantId);
        if (!$original)
            return [];

        $origBu = (int) ($original['business_unit_id'] ?? 0);

        $sql = "
            SELECT a.*
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
            WHERE ag.code = 'smc'
              AND a.status = 'pending'
              AND a.deleted_at IS NULL
              AND NOT EXISTS (SELECT 1 FROM blacklisted_applicants b WHERE b.applicant_id = a.id AND b.is_active = 1)
        ";
        if ($origBu > 0) {
            $sql .= " AND a.business_unit_id = " . (int)$origBu;
        }

        $res = $this->db->query($sql);
        if (!$res)
            return [];
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as &$r) {
            $r['_score'] = $this->computeSimilarityScore($original, $r);
        }
        unset($r);

        usort($rows, function ($x, $y) {
            if ($y['_score'] !== $x['_score'])
                return $y['_score'] <=> $x['_score'];
            $yx = (int) ($y['years_experience'] ?? 0);
            $xx = (int) ($x['years_experience'] ?? 0);
            if ($yx !== $xx)
                return $yx <=> $xx;
            return strcmp((string) ($x['created_at'] ?? ''), (string) ($y['created_at'] ?? ''));
        });

        if ($limit > 0 && count($rows) > $limit) {
            $rows = array_slice($rows, 0, $limit);
        }
        return $rows;
    }

    /**
     * Create a replacement record (selection phase).
     * Also logs applicant_reports (with BU) and activity_logs.
     * @param array $attachmentsPaths e.g. ['replacements/abc.jpg', ...]
     * @return int|null replacement id
     */
    public function createReplacementInit(
        int $originalApplicantId,
        string $reason,
        string $reportText,
        array $attachmentsPaths,
        int $adminId
    ): ?int {
        $this->ensureApplicantReplacementsTable();

        $allowedReasons = ['AWOL', 'Client Left', 'Not Finished Contract', 'Performance Issue', 'Other'];
        if (!in_array($reason, $allowedReasons, true)) {
            $reason = 'Other';
        }

        $orig = $this->getById($originalApplicantId);
        if (!$orig || ($orig['status'] ?? '') !== 'approved') {
            return null; // Only allowed from approved original
        }

        $origBu = (int) ($orig['business_unit_id'] ?? 0);

        // Capture latest booking (if any) - BU aware
        $bookingId = $this->getLatestBookingIdForApplicant($originalApplicantId);
        $attachmentsJson = json_encode(array_values($attachmentsPaths), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Insert replacement record
        $sql = "INSERT INTO applicant_replacements
                (original_applicant_id, client_booking_id, reason, report_text, attachments_json, status, created_by)
                VALUES (?, ?, ?, ?, ?, 'selection', ?)";
        $stmt = $this->db->prepare($sql);
        $bindBooking = $bookingId !== null ? $bookingId : null;
        $stmt->bind_param(
            "iisssi",
            $originalApplicantId,
            $bindBooking,
            $reason,
            $reportText,
            $attachmentsJson,
            $adminId
        );
        if (!$stmt->execute()) {
            return null;
        }
        $replaceId = (int) $this->db->insert_id;

        // Write applicant report for the original (include business_unit_id for SMC)
        $repNote = "Replacement Initiated (Reason: {$reason})\n" . $reportText;
        $stmt2 = $this->db->prepare("INSERT INTO applicant_reports (applicant_id, business_unit_id, admin_id, note_text) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("iiis", $originalApplicantId, $origBu, $adminId, $repNote);
        $stmt2->execute();

        // Activity log
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $action = 'Start Replacement';
        $desc = "Start replacement for Applicant ID {$originalApplicantId}; Reason: {$reason}";
        $stmt3 = $this->db->prepare("INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("isss", $adminId, $action, $desc, $ip);
        $stmt3->execute();

        return $replaceId;
    }

    /** Fetch a replacement record by id */
    public function getReplacementById(int $replaceId): ?array
    {
        $this->ensureApplicantReplacementsTable();
        $stmt = $this->db->prepare("SELECT * FROM applicant_replacements WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $replaceId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        return $row ?: null;
    }

    /**
     * Assign the chosen pending applicant as replacement:
     * - set applicant.status = 'on_process'
     * - insert applicant_status_reports (with BU)
     * - reassign client_bookings (BU-aware: set applicant_id + business_unit_id)
     * - update applicant_replacements row
     * - activity_logs
     */
    public function assignReplacement(int $replaceId, int $replacementApplicantId, int $adminId): bool
    {
        $this->ensureApplicantReplacementsTable();

        // Load replacement record
        $rep = $this->getReplacementById($replaceId);
        if (!$rep || ($rep['status'] ?? '') !== 'selection') {
            return false;
        }

        $originalId = (int) $rep['original_applicant_id'];
        $original = $this->getById($originalId);
        if (!$original)
            return false;

        $candidate = $this->getById($replacementApplicantId);
        if (!$candidate || ($candidate['status'] ?? '') !== 'pending') {
            return false;
        }

        $clientBookingId = $rep['client_booking_id'] !== null ? (int) $rep['client_booking_id'] : null;
        $reason = (string) $rep['reason'];

        // Build status report text
        $origName = trim(($original['first_name'] ?? '') . ' ' . ($original['last_name'] ?? ''));
        $reportText = "Replacement for {$origName} (ID: {$originalId}) due to {$reason}.";

        $this->db->begin_transaction();
        try {
            // 1) Update applicant status => on_process (only if was pending)
            $stmt1 = $this->db->prepare("UPDATE applicants SET status = 'on_process', updated_at = NOW() WHERE id = ? AND status = 'pending' AND deleted_at IS NULL");
            $stmt1->bind_param("i", $replacementApplicantId);
            $stmt1->execute();
            if ($this->db->affected_rows <= 0) {
                throw new \RuntimeException('Failed to move candidate to on_process.');
            }

            // 2) Insert status report line (include business_unit_id)
            $businessUnitId = (int) ($candidate['business_unit_id'] ?? 0);
            $stmt2 = $this->db->prepare("
                INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                VALUES (?, ?, 'pending', 'on_process', ?, ?)
            ");
            $stmt2->bind_param("iisi", $replacementApplicantId, $businessUnitId, $reportText, $adminId);
            $stmt2->execute();

            // 3) Reassign client booking if available (BU-aware)
            if ($clientBookingId !== null) {
                $stmt3 = $this->db->prepare("UPDATE client_bookings SET applicant_id = ?, business_unit_id = ?, updated_at = NOW() WHERE id = ?");
                $stmt3->bind_param("iii", $replacementApplicantId, $businessUnitId, $clientBookingId);
                $stmt3->execute();
            }

            // 4) Update replacement row => assigned
            $stmt4 = $this->db->prepare("
                UPDATE applicant_replacements
                   SET replacement_applicant_id = ?, status = 'assigned', assigned_at = NOW()
                 WHERE id = ? AND status = 'selection'
            ");
            $stmt4->bind_param("ii", $replacementApplicantId, $replaceId);
            $stmt4->execute();
            if ($this->db->affected_rows <= 0) {
                throw new \RuntimeException('Failed to mark replacement as assigned.');
            }

            // 5) Activity log
            $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
            $action = 'Assign Replacement';
            $desc = "Assigned Applicant ID {$replacementApplicantId} as replacement for Original ID {$originalId}";
            $stmt5 = $this->db->prepare("INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt5->bind_param("isss", $adminId, $action, $desc, $ip);
            $stmt5->execute();

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    /* ============================================================
     * COUNTRY FILTERING (for SMC international applicants)
     * ============================================================ */

    /**
     * Get countries with applicant counts for SMC (excludes Philippines).
     * This is used for the country filter on the applicants list page.
     *
     * Counts are SMC-only (ag.code='smc').
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
        // Countries list (unchanged; counts will be SMC-only)
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

        // Build filtered count query with SMC scope
        $countSql = "
            SELECT 
                bu.country_id,
                COUNT(a.id) AS applicant_count
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
            WHERE ag.code = 'smc'
        ";

        if ($buId !== null && $buId > 0) {
            $countSql .= " AND a.business_unit_id = " . (int) $buId;
        }

        if ($status !== 'all') {
            $statusEsc = $this->db->real_escape_string($status);
            $countSql .= " AND a.status = '{$statusEsc}'";
        }

        if ($notDeleted) {
            $countSql .= " AND a.deleted_at IS NULL";
        }

        if ($notBlacklisted) {
            $countSql .= " AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
            )";
        }

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

        $result = [];
        foreach ($countries as $c) {
            $result[] = [
                'id'    => (int) $c['id'],
                'name'  => $c['country_name'],
                'iso2'  => $c['iso2'],
                'count' => $counts[$c['id']] ?? 0
            ];
        }

        return $result;
    }

    /**
     * Get applicants with comprehensive filtering + pagination.
     * SMC-only via agencies join.
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
        $where = "ag.code = 'smc'";

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

        $offset = ($page - 1) * $pageSize;

        $sql = "
            SELECT 
                a.*,
                bu.country_id,
                c.name AS country_name
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
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
     * Get total count of applicants matching filters (SMC-only).
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
        $where = "ag.code = 'smc'";

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
            JOIN agencies ag ON ag.id = bu.agency_id
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
     * SMC-only.
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
            JOIN agencies ag ON ag.id = bu.agency_id
            JOIN countries c ON c.id = bu.country_id
            WHERE ag.code = 'smc'
              AND bu.active = 1
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
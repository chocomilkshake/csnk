<?php
class Applicant
{
    /** @var mysqli */
    private $db;

    /** CSNK agency code used for scoping */
    private const CSNK_AGENCY_CODE = 'csnk';

    public function __construct($database)
    {
        $this->db = $database->getConnection();
    }

    /* ============================================================
     * BU (Business Unit/Country) Helpers
     * ============================================================ */

    /**
     * Get all business units (countries) from database.
     * @param bool $activeOnly - If true, only return active BUs
     * @return array
     */
    public function getAllBusinessUnits(bool $activeOnly = true): array
    {
        $rows = [];
        $where = $activeOnly ? " WHERE bu.active = 1 " : "";
        $sql = "
        SELECT 
            bu.id,
            bu.code,
            bu.name         AS bu_name,
            c.id            AS country_id,
            c.name          AS country_name,
            CONCAT(bu.code, ' — ', c.name) AS label
        FROM business_units bu
        JOIN countries c ON c.id = bu.country_id
        {$where}
        ORDER BY bu.code
    ";

        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /**
     * Check if applicant belongs to the specified business unit.
     * @param int $applicantId
     * @param int $businessUnitId
     * @return bool
     */
    public function isApplicantInBusinessUnit(int $applicantId, int $businessUnitId): bool
    {
        $stmt = $this->db->prepare("SELECT business_unit_id FROM applicants WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row && isset($row['business_unit_id']) && (int) $row['business_unit_id'] === $businessUnitId;
    }

    /**
     * Update applicant's business unit (country assignment).
     * @param int $applicantId
     * @param int $businessUnitId
     * @return bool
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
     * EXISTING METHODS (kept)
     * ============================================================ */

    /**
     * Admin/general: Get all applicants (optionally by a single status).
     * - Excludes deleted by default.
     * - Use getAllForPublic() for client-facing lists.
     * - NEW: Optionally filter by business_unit_id
     * - NEW: Optionally filter by agency (csnk/smc)
     */
    public function getAll($status = null, ?int $businessUnitId = null, ?string $agency = null): array
    {
        $where = [];
        $types = '';
        $params = [];

        // Agency filtering: If agency is specified, filter by business units belonging to that agency
        if ($agency !== null && in_array($agency, ['csnk', 'smc'], true)) {
            $where[] = " EXISTS (
                SELECT 1 FROM business_units bu 
                JOIN agencies a ON a.id = bu.agency_id 
                WHERE bu.id = applicants.business_unit_id AND a.code = ?
            ) ";
            $types .= "s";
            $params[] = $agency;
        }

        if ($businessUnitId !== null && $businessUnitId > 0) {
            $where[] = "applicants.business_unit_id = ?";
            $types .= "i";
            $params[] = $businessUnitId;
        }

        $where[] = "applicants.deleted_at IS NULL";
        $where[] = "NOT EXISTS (
        SELECT 1 FROM blacklisted_applicants b
        WHERE b.applicant_id = applicants.id AND b.is_active = 1
    )";

        if ($status !== null) {
            $where[] = "applicants.status = ?";
            $types .= "s";
            $params[] = $status;
        }

        $sql = "SELECT * FROM applicants WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (getAll): ' . $this->db->error);
                return [];
            }
            $this->bindByRef($stmt, $types, $params); // helper below
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

    /**
     * NEW (Client-facing): Get list for public site.
     * - Returns only non-deleted, non-approved applicants
     *   i.e., status IN ('pending','on_process').
     * - Ordered by created_at DESC.
     */
    public function getAllForPublic(): array
    {
        $sql = "SELECT *
                FROM applicants
                WHERE deleted_at IS NULL
                  AND status IN ('pending','on_process')
                  AND NOT EXISTS (
                    SELECT 1 FROM blacklisted_applicants b
                    WHERE b.applicant_id = applicants.id AND b.is_active = 1
                  )
                ORDER BY created_at DESC";
        $res = $this->db->query($sql);
        if (!$res)
            return [];
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getDeleted()
    {
        $sql = "
            SELECT *
            FROM applicants
            WHERE deleted_at IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
            ORDER BY deleted_at DESC
        ";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM applicants WHERE id = ? LIMIT 1");
        if (!$stmt) {
            error_log('getById prepare failed: ' . $this->db->error);
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    /**
     * Create applicant (includes specialization_skills JSON and business_unit_id).
     */
    public function create($data)
    {
        // Normalize daily_rate from input
        $dailyRate = null;
        if (isset($data['daily_rate']) && $data['daily_rate'] !== '') {
            $dailyRate = round((float) $data['daily_rate'], 2);
        }

        // Common values
        $first = $data['first_name'] ?? null;
        $middle = $data['middle_name'] ?? null;
        $last = $data['last_name'] ?? null;
        $suffix = $data['suffix'] ?? null;
        $phone = $data['phone_number'] ?? null;
        $alt = $data['alt_phone_number'] ?? null;
        $email = $data['email'] ?? null;
        $dob = $data['date_of_birth'] ?? null;
        $addr = $data['address'] ?? null;
        $educA = $data['educational_attainment'] ?? null; // JSON
        $workH = $data['work_history'] ?? null;           // JSON
        $pref = $data['preferred_location'] ?? null;     // JSON
        $langs = $data['languages'] ?? null;              // JSON
        $skills = $data['specialization_skills'] ?? null;  // JSON
        $pic = $data['picture'] ?? null;
        $status = $data['status'] ?? 'pending';
        $empTy = $data['employment_type'] ?? null;
        $eduLv = $data['education_level'] ?? null;
        $years = isset($data['years_experience']) ? (int) $data['years_experience'] : 0;
        $createdBy = isset($data['created_by']) ? (int) $data['created_by'] : 0;

        // Business Unit (Country) - NEW
        $businessUnitId = isset($data['business_unit_id']) ? (int) $data['business_unit_id'] : null;
        $countryId = isset($data['country_id']) ? (int) $data['country_id'] : null;

        if ($dailyRate === null) {
            // Use NULL explicitly in SQL for true NULL
            // Include business_unit_id and country_id in the insert
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
            // 18 strings + 4 ints (years_experience, created_by, business_unit_id, country_id)
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
            // daily_rate bound as double (d)
            // Include business_unit_id and country_id in the insert
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
            // 12th param is daily_rate (double), then the rest + business_unit_id + country_id
            // Types: 18 strings + 1 double + 4 ints => "sssssssssss" + "d" + "ssssss" + "iiii"
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
        $newId = $this->db->insert_id;
        $stmt->close();
        return $newId ?: false;
    }

    /**
     * Update applicant (includes specialization_skills JSON and business_unit_id).
     */
    public function update($id, $data)
    {
        // Normalize daily_rate
        $dailyRate = null;
        if (isset($data['daily_rate']) && $data['daily_rate'] !== '') {
            $dailyRate = round((float) $data['daily_rate'], 2);
        }

        // Common values
        $first = $data['first_name'] ?? null;
        $middle = $data['middle_name'] ?? null;
        $last = $data['last_name'] ?? null;
        $suffix = $data['suffix'] ?? null;
        $phone = $data['phone_number'] ?? null;
        $alt = $data['alt_phone_number'] ?? null;
        $email = $data['email'] ?? null;
        $dob = $data['date_of_birth'] ?? null;
        $addr = $data['address'] ?? null;
        $educA = $data['educational_attainment'] ?? null; // JSON
        $workH = $data['work_history'] ?? null;           // JSON
        $pref = $data['preferred_location'] ?? null;     // JSON
        $langs = $data['languages'] ?? null;              // JSON
        $skills = $data['specialization_skills'] ?? null;  // JSON
        $pic = $data['picture'] ?? null;
        $status = $data['status'] ?? 'pending';
        $empTy = $data['employment_type'] ?? null;
        $eduLv = $data['education_level'] ?? null;
        $years = isset($data['years_experience']) ? (int) $data['years_experience'] : 0;

        // Business Unit (Country) - NEW
        $businessUnitId = isset($data['business_unit_id']) && $data['business_unit_id'] !== ''
            ? (int) $data['business_unit_id']
            : null;
        $countryId = isset($data['country_id']) && $data['country_id'] !== ''
            ? (int) $data['country_id']
            : null;

        if ($dailyRate === null) {
            // daily_rate to NULL
            // Include business_unit_id and country_id in the update
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
            // 18 strings + 3 ints (years, business_unit_id, country_id) + 1 int (id)
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
            // Include business_unit_id and country_id in the update
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
            // Types: "sssssssssss" + "d" + "sssssss" + "iiii"
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
     * Update video fields for an applicant
     */
    public function updateVideoFields($id, $videoData)
    {
        $sql = "UPDATE applicants SET
                    video_url = ?, video_provider = ?, video_type = ?, 
                    video_title = ?, video_thumbnail_url = ?, video_duration_seconds = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "sssssii",
            $videoData['video_url'],
            $videoData['video_provider'],
            $videoData['video_type'],
            $videoData['video_title'],
            $videoData['video_thumbnail_url'],
            $videoData['video_duration_seconds'],
            $id
        );

        return $stmt->execute();
    }

    public function softDelete($id)
    {
        $stmt = $this->db->prepare("UPDATE applicants SET deleted_at = NOW(), status = 'deleted' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function restore($id)
    {
        $stmt = $this->db->prepare("UPDATE applicants SET deleted_at = NULL, status = 'pending' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function permanentDelete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM applicants WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE applicants SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Convenience for booking workflow */
    public function markOnProcess(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE applicants SET status = 'on_process', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getDocuments($applicantId)
    {
        $stmt = $this->db->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ?");
        if (!$stmt) {
            error_log('getDocuments prepare failed: ' . $this->db->error);
            return [];
        }
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    public function addDocument($applicantId, $documentType, $filePath)
    {
        $stmt = $this->db->prepare("INSERT INTO applicant_documents (applicant_id, document_type, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $applicantId, $documentType, $filePath);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteDocument($documentId)
    {
        $stmt = $this->db->prepare("DELETE FROM applicant_documents WHERE id = ?");
        $stmt->bind_param("i", $documentId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** NEW: delete all docs of a given type for an applicant (for replacement) */
    public function deleteDocumentsByType($applicantId, $documentType)
    {
        $stmt = $this->db->prepare("DELETE FROM applicant_documents WHERE applicant_id = ? AND document_type = ?");
        $stmt->bind_param("is", $applicantId, $documentType);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getStatistics()
    {
        $stats = [];

        $result = $this->db->query("
            SELECT COUNT(*) as total
            FROM applicants a
            WHERE a.deleted_at IS NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ");
        $stats['total'] = $result->fetch_assoc()['total'] ?? 0;

        $result = $this->db->query("
            SELECT COUNT(*) as pending
            FROM applicants a
            WHERE a.status = 'pending'
              AND a.deleted_at IS NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ");
        $stats['pending'] = $result->fetch_assoc()['pending'] ?? 0;

        $result = $this->db->query("
            SELECT COUNT(*) as on_process
            FROM applicants a
            WHERE a.status = 'on_process'
              AND a.deleted_at IS NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ");
        $stats['on_process'] = $result->fetch_assoc()['on_process'] ?? 0;

        $result = $this->db->query("
            SELECT COUNT(*) as deleted
            FROM applicants a
            WHERE a.deleted_at IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ");
        $stats['deleted'] = $result->fetch_assoc()['deleted'] ?? 0;

        return $stats;
    }

    /**
     * NEW: Get all "on_process" applicants with their latest client booking (if any).
     * Returns applicant columns + aliased booking columns:
     *  - booking_id, appointment_type, appointment_date, appointment_time,
     *    client_first_name, client_middle_name, client_last_name, client_phone, client_email,
     *    client_address, booking_status, booking_created_at
     */
    public function getOnProcessWithLatestBooking(): array
    {
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
            LEFT JOIN (
                SELECT c1.*
                FROM client_bookings c1
                INNER JOIN (
                    SELECT applicant_id, MAX(created_at) AS max_created
                    FROM client_bookings
                    GROUP BY applicant_id
                ) t ON t.applicant_id = c1.applicant_id AND t.max_created = c1.created_at
            ) cb ON cb.applicant_id = a.id
            WHERE a.deleted_at IS NULL
              AND a.status = 'on_process'
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
            ORDER BY a.created_at DESC
        ";

        $res = $this->db->query($sql);
        if (!$res)
            return [];
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /* ============================================================
     * REPLACEMENT FEATURE — NEW METHODS
     * ============================================================ */

    /** Ensure table applicant_replacements exists (safe to call many times) */
    private function ensureApplicantReplacementsTable(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS `applicant_replacements` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `business_unit_id` INT(10) UNSIGNED DEFAULT NULL,
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
          KEY `idx_ar_business_unit_id` (`business_unit_id`)
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

    /** Bind params by reference to a prepared statement. */
    private function bindByRef(mysqli_stmt $stmt, string $types, array $values): void
    {
        $refs = [];
        $refs[] = &$types;
        foreach ($values as $k => $v) {
            $values[$k] = $v;     // ensure variable
            $refs[] = &$values[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    /** Get agency code (e.g., 'csnk' or 'smc') for an applicant id. */
    private function getAgencyCodeByApplicantId(int $applicantId): ?string
    {
        $sql = "
            SELECT ag.code AS agency_code
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
            WHERE a.id = ?
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('getAgencyCodeByApplicantId prepare failed: ' . $this->db->error);
            return null;
        }
        $stmt->bind_param('i', $applicantId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row['agency_code'] ?? null;
    }

    /** Get latest booking id for applicant (or null) */
    public function getLatestBookingIdForApplicant(int $applicantId): ?int
    {
        $sql = "SELECT id FROM client_bookings WHERE applicant_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('getLatestBookingIdForApplicant prepare failed: ' . $this->db->error);
            return null;
        }
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ? (int) $row['id'] : null;
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
        $cityOverlap = $this->overlapCount($origCities, $candCities);

        return (2 * $skillOverlap) + (1 * $cityOverlap);
    }

    /**
     * Return pending applicants sorted by similarity to original (desc).
     * Excludes deleted and active blacklisted.
     */
    public function searchPendingCandidatesForReplacement(int $originalApplicantId, int $limit = 50): array
    {
        $original = $this->getById($originalApplicantId);
        if (!$original)
            return [];

        $originalBuId = isset($original['business_unit_id']) ? (int)$original['business_unit_id'] : null;

        $baseSql = "
            SELECT a.*
            FROM applicants a
            WHERE a.status = 'pending'
              AND a.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM blacklisted_applicants b
                  WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ";

        $rows = [];
        if ($originalBuId !== null && $originalBuId > 0) {
            $sql = $baseSql . " AND a.business_unit_id = ? ";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('searchPendingCandidatesForReplacement prepare failed: ' . $this->db->error);
                return [];
            }
            $stmt->bind_param('i', $originalBuId);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
        } else {
            $res = $this->db->query($baseSql);
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }

        // Score & sort
        foreach ($rows as &$r) {
            $r['_score'] = $this->computeSimilarityScore($original, $r);
        }
        unset($r);

        usort($rows, function ($x, $y) {
            // Sort by score DESC, then years_experience DESC, then created_at ASC
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
     * Also logs applicant_reports and activity_logs.
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
            error_log('createReplacementInit: Original not found or not approved');
            return null; // Only allowed from approved original
        }

        // Get the business_unit_id from the original applicant
        $businessUnitId = isset($orig['business_unit_id']) ? (int)$orig['business_unit_id'] : null;
        if ($businessUnitId === null || $businessUnitId <= 0) {
            error_log('createReplacementInit: Original applicant has no business_unit_id');
            return null;
        }

        // Capture latest booking (if any)
        $bookingId = $this->getLatestBookingIdForApplicant($originalApplicantId);
        $attachmentsJson = json_encode(array_values($attachmentsPaths), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Insert replacement record (include business_unit_id)
        $sql = "INSERT INTO applicant_replacements
                (business_unit_id, original_applicant_id, client_booking_id, reason, report_text, attachments_json, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 'selection', ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('createReplacementInit prepare error: ' . $this->db->error);
            return null;
        }
        $bindBooking = $bookingId !== null ? $bookingId : null;
        $stmt->bind_param(
            "iiisssi",
            $businessUnitId,
            $originalApplicantId,
            $bindBooking,
            $reason,
            $reportText,
            $attachmentsJson,
            $adminId
        );
        if (!$stmt->execute()) {
            error_log('createReplacementInit insert error: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $replaceId = (int) $this->db->insert_id;
        $stmt->close();

        // Write applicant report for the original (include business_unit_id if present)
        $repNote = "Replacement Initiated (Reason: {$reason})\n" . $reportText;

        // Check if business_unit_id column exists in applicant_reports
        $checkCol = $this->db->query("SHOW COLUMNS FROM applicant_reports LIKE 'business_unit_id'");
        if ($checkCol && $checkCol->num_rows > 0) {
            $stmt2 = $this->db->prepare("INSERT INTO applicant_reports (applicant_id, business_unit_id, admin_id, note_text) VALUES (?, ?, ?, ?)");
            if ($stmt2) {
                $stmt2->bind_param("iiis", $originalApplicantId, $businessUnitId, $adminId, $repNote);
                $stmt2->execute();
                $stmt2->close();
            }
        } else {
            $stmt2 = $this->db->prepare("INSERT INTO applicant_reports (applicant_id, admin_id, note_text) VALUES (?, ?, ?)");
            if ($stmt2) {
                $stmt2->bind_param("iis", $originalApplicantId, $adminId, $repNote);
                $stmt2->execute();
                $stmt2->close();
            }
        }

        // Activity log
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $action = 'Start Replacement';
        $desc = "Start replacement for Applicant ID {$originalApplicantId}; Reason: {$reason}";
        $stmt3 = $this->db->prepare("INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        if ($stmt3) {
            $stmt3->bind_param("isss", $adminId, $action, $desc, $ip);
            $stmt3->execute();
            $stmt3->close();
        }

        return $replaceId;
    }

    /** Fetch a replacement record by id */
    public function getReplacementById(int $replaceId): ?array
    {
        $this->ensureApplicantReplacementsTable();
        $stmt = $this->db->prepare("SELECT * FROM applicant_replacements WHERE id = ? LIMIT 1");
        if (!$stmt) {pplicantId);
                    $stmt->execute();
                    $stmt->close();

                    // Record status report with the REAL from_status
                    $reportText = "Replacement assignment — moved from {$candStatus} to on_process.";
                    $stmt2 = $this->db->prepare("
                        INSERT INTO applicant_status_reports (applicant_id, from_status, to_status, report_text, admin_id)
                        VALUES (?, ?, 'on_process', ?, ?)
                    ");
   

    /* ============================================================
     * COUNTRY FILTERING (for SMC international applicants)
     * ============================================================ */

    /**
     * Get countries with applicant counts for SMC (excludes Philippines).
     * This is used for the country filter on the applicants list page.
     * 
     * @param int|null $businessUnitId If provided, only count applicants for this BU
     * @return array Array of countries with counts: ['id', 'name', 'count']
     */



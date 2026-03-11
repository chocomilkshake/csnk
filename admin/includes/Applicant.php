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

    public function getAll($status = null, ?int $businessUnitId = null, ?string $agency = null): array
    {
        $where = [];
        $types = '';
        $params = [];

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
                WHERE b.applicant_id = applicants.id AND b.is_active = 1
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

    public function create($data)
    {
        $dailyRate = null;
        if (isset($data['daily_rate']) && $data['daily_rate'] !== '') {
            $dailyRate = round((float) $data['daily_rate'], 2);
        }

        $first = $data['first_name'] ?? null;
        $middle = $data['middle_name'] ?? null;
        $last = $data['last_name'] ?? null;
        $suffix = $data['suffix'] ?? null;
        $phone = $data['phone_number'] ?? null;
        $alt = $data['alt_phone_number'] ?? null;
        $email = $data['email'] ?? null;
        $dob = $data['date_of_birth'] ?? null;
        $addr = $data['address'] ?? null;
        $educA = $data['educational_attainment'] ?? null;
        $workH = $data['work_history'] ?? null;
        $pref = $data['preferred_location'] ?? null;
        $langs = $data['languages'] ?? null;
        $skills = $data['specialization_skills'] ?? null;
        $pic = $data['picture'] ?? null;
        $status = $data['status'] ?? 'pending';
        $empTy = $data['employment_type'] ?? null;
        $eduLv = $data['education_level'] ?? null;
        $years = isset($data['years_experience']) ? (int) $data['years_experience'] : 0;
        $createdBy = isset($data['created_by']) ? (int) $data['created_by'] : 0;

        $businessUnitId = isset($data['business_unit_id']) ? (int) $data['business_unit_id'] : null;
        $countryId = isset($data['country_id']) ? (int) $data['country_id'] : null;

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
                $first, $middle, $last, $suffix,
                $phone, $alt, $email, $dob, $addr,
                $educA, $workH, $pref, $langs, $skills,
                $pic, $status, $empTy, $eduLv, $years,
                $createdBy, $businessUnitId, $countryId
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
                $first, $middle, $last, $suffix,
                $phone, $alt, $email, $dob, $addr,
                $educA, $workH, $dailyRate, $pref, $langs, $skills,
                $pic, $status, $empTy, $eduLv, $years,
                $createdBy, $businessUnitId, $countryId
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

    public function update($id, $data)
    {
        $dailyRate = null;
        if (isset($data['daily_rate']) && $data['daily_rate'] !== '') {
            $dailyRate = round((float) $data['daily_rate'], 2);
        }

        $first = $data['first_name'] ?? null;
        $middle = $data['middle_name'] ?? null;
        $last = $data['last_name'] ?? null;
        $suffix = $data['suffix'] ?? null;
        $phone = $data['phone_number'] ?? null;
        $alt = $data['alt_phone_number'] ?? null;
        $email = $data['email'] ?? null;
        $dob = $data['date_of_birth'] ?? null;
        $addr = $data['address'] ?? null;
        $educA = $data['educational_attainment'] ?? null;
        $workH = $data['work_history'] ?? null;
        $pref = $data['preferred_location'] ?? null;
        $langs = $data['languages'] ?? null;
        $skills = $data['specialization_skills'] ?? null;
        $pic = $data['picture'] ?? null;
        $status = $data['status'] ?? 'pending';
        $empTy = $data['employment_type'] ?? null;
        $eduLv = $data['education_level'] ?? null;
        $years = isset($data['years_experience']) ? (int) $data['years_experience'] : 0;

        $businessUnitId = isset($data['business_unit_id']) && $data['business_unit_id'] !== '' ? (int)$data['business_unit_id'] : null;
        $countryId      = isset($data['country_id']) && $data['country_id'] !== '' ? (int)$data['country_id']      : null;

        if ($dailyRate === null) {
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
                $first, $middle, $last, $suffix,
                $phone, $alt, $email, $dob, $addr,
                $educA, $workH, $pref, $langs, $skills,
                $pic, $status, $empTy, $eduLv, $years,
                $businessUnitId, $countryId, $id
            );
        } else {
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
                $first, $middle, $last, $suffix,
                $phone, $alt, $email, $dob, $addr,
                $educA, $workH, $dailyRate, $pref, $langs, $skills,
                $pic, $status, $empTy, $eduLv, $years,
                $businessUnitId, $countryId, $id
            );
        }

        $ok = $stmt->execute();
        if (!$ok) {
            error_log('Execute failed (update): ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }

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
        try { $this->db->query($sql); } catch (\Throwable $e) { /* silent */ }
    }

    /** Safe JSON decode */
    private function decodeJsonArray($val): array
    {
        if ($val === null || $val === '' || $val === '[]')
            return [];
        $arr = json_decode((string) $val, true);
        return is_array($arr) ? $arr : [];
    }

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

    private function overlapCount(array $a, array $b): int
    {
        $a = $this->normalizeStringArray($a);
        $b = $this->normalizeStringArray($b);
        return count(array_intersect($a, $b));
    }

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

    /** Map education level labels to ranks (higher is “better”) */
    private function getEducationRank(?string $label): int
    {
        if (!$label) return 0;
        static $map = [
            'Elementary Graduate' => 1,
            'Secondary Level (Attended High School)' => 2,
            'Secondary Graduate (Junior High School / Old Curriculum)' => 3,
            'Senior High School Graduate (K-12 Curriculum)' => 4,
            'Technical-Vocational / TESDA Graduate' => 5,
            'Tertiary Level (College Undergraduate)' => 6,
            'Tertiary Graduate (Bachelor’s Degree)' => 7,
        ];
        return $map[$label] ?? 0;
        // Note: keep in sync with enum values in DB
    }

    /**
     * Compute improved similarity score for candidate vs original.
     * Weights:
     *  - skills overlap:         x4
     *  - preferred cities:       x2
     *  - languages overlap:      x1
     *  - employment type match: +2
     *  - education >= original: +1
     *  - experience bonus:      + floor(years/2)  (0..6 for 0..12 yrs)
     *  - required docs complete: + min(completed, 3) (0..3)
     */
    private function computeSimilarityScore(array $original, array $candidate, int $docsCompleted): int
    {
        $origSkills = $this->decodeJsonArray($original['specialization_skills'] ?? '[]');
        $origCities = $this->decodeJsonArray($original['preferred_location'] ?? '[]');
        $origLangs  = $this->decodeJsonArray($original['languages'] ?? '[]');
        $candSkills = $this->decodeJsonArray($candidate['specialization_skills'] ?? '[]');
        $candCities = $this->decodeJsonArray($candidate['preferred_location'] ?? '[]');
        $candLangs  = $this->decodeJsonArray($candidate['languages'] ?? '[]');

        $skillOverlap = $this->overlapCount($origSkills, $candSkills);
        $cityOverlap  = $this->overlapCount($origCities, $candCities);
        $langOverlap  = $this->overlapCount($origLangs, $candLangs);

        $score  = 0;
        $score += $skillOverlap * 4;
        $score += $cityOverlap  * 2;
        $score += $langOverlap  * 1;

        $origEmp = strtolower(trim((string)($original['employment_type'] ?? '')));
        $candEmp = strtolower(trim((string)($candidate['employment_type'] ?? '')));
        if ($origEmp !== '' && $origEmp === $candEmp) $score += 2;

        $origEduRank = $this->getEducationRank($original['education_level'] ?? null);
        $candEduRank = $this->getEducationRank($candidate['education_level'] ?? null);
        if ($candEduRank >= $origEduRank && $candEduRank > 0) $score += 1;

        $years = (int)($candidate['years_experience'] ?? 0);
        $score += intdiv(max(0, min($years, 12)), 2); // 0..6

        $score += min(max(0, (int)$docsCompleted), 3); // reward up to 3

        return (int)$score;
    }

    /**
     * Return BEST pending candidates for replacement (CSNK-only), prioritizing same BU.
     * - Filters:
     *   status='pending', not deleted, not blacklisted, agency = CSNK
     * - Phase 1: same BU as original
     * - Phase 2 (fallback): any CSNK BU (if Phase 1 results < $limit)
     * - Includes required document completeness (for original’s BU country)
     * - Sorted by score DESC, docs_completed DESC, years_experience DESC, created_at ASC
     */
    public function searchPendingCandidatesForReplacement(int $originalApplicantId, int $limit = 50): array
    {
        $original = $this->getById($originalApplicantId);
        if (!$original) return [];

        // Resolve BU + country for the ORIGINAL from business_units (more reliable than applicants.country_id)
        $origBuId = null;
        $origCountryId = null;
        $stmt = $this->db->prepare("
            SELECT bu.id AS bu_id, bu.country_id AS country_id
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            WHERE a.id = ?
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param('i', $originalApplicantId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                $origBuId = (int)$row['bu_id'];
                $origCountryId = (int)$row['country_id'];
            }
            $stmt->close();
        }
        if (!$origBuId) $origBuId = (int)($original['business_unit_id'] ?? 0);
        if (!$origCountryId) $origCountryId = (int)($original['country_id'] ?? 0);

        // Helper to fetch candidates with required docs count
        $fetchCandidates = function (?int $buIdFilter, int $maxRows) use ($origCountryId): array {
            $rows = [];
            $sql = "
                SELECT 
                    a.*,
                    -- count of required documents completed for the ORIGINAL's country
                    (
                        SELECT COUNT(*)
                        FROM applicant_documents ad
                        JOIN document_types dt ON dt.id = ad.document_type_id
                        WHERE ad.applicant_id = a.id
                          AND dt.is_required = 1
                          AND dt.country_id = ?
                    ) AS docs_completed
                FROM applicants a
                JOIN business_units bu ON bu.id = a.business_unit_id
                JOIN agencies ag ON ag.id = bu.agency_id
                WHERE a.status = 'pending'
                  AND a.deleted_at IS NULL
                  AND ag.code = ?
                  AND NOT EXISTS (
                      SELECT 1 FROM blacklisted_applicants b
                      WHERE b.applicant_id = a.id AND b.is_active = 1
                  )
            ";
            $types = "is";
            $params = [$origCountryId, self::CSNK_AGENCY_CODE];

            if ($buIdFilter !== null && $buIdFilter > 0) {
                $sql .= " AND a.business_unit_id = ? ";
                $types .= "i";
                $params[] = $buIdFilter;
            }

            // Soft pre-sort: more recent first can be noisy; let PHP sort by score thoroughly.
            $sql .= " ORDER BY a.created_at DESC LIMIT ? ";
            $types .= "i";
            $params[] = $maxRows;

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('searchPendingCandidatesForReplacement prepare failed: ' . $this->db->error);
                return [];
            }
            $this->bindByRef($stmt, $types, $params);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $rows;
        };

        // Phase 1: same BU (strong locality)
        $phase1 = $fetchCandidates($origBuId, max(100, $limit));

        // If not enough, Phase 2: expand to any CSNK BU
        $phase2 = [];
        if (count($phase1) < $limit) {
            $phase2 = $fetchCandidates(null, max(200, $limit * 2));
        }

        // Merge unique by id (phase1 priority)
        $byId = [];
        foreach ($phase1 as $r) { $byId[(int)$r['id']] = $r; }
        foreach ($phase2 as $r) {
            $id = (int)$r['id'];
            if (!isset($byId[$id])) $byId[$id] = $r;
        }
        $rows = array_values($byId);

        // Score each candidate
        foreach ($rows as &$r) {
            $docsCompleted = (int)($r['docs_completed'] ?? 0);
            $r['_score'] = $this->computeSimilarityScore($original, $r, $docsCompleted);
        }
        unset($r);

        // Sort by: score DESC, docs_completed DESC, years_experience DESC, created_at ASC
        usort($rows, function ($x, $y) {
            if (($y['_score'] ?? 0) !== ($x['_score'] ?? 0)) return ($y['_score'] ?? 0) <=> ($x['_score'] ?? 0);
            $y_docs = (int)($y['docs_completed'] ?? 0);
            $x_docs = (int)($x['docs_completed'] ?? 0);
            if ($y_docs !== $x_docs) return $y_docs <=> $x_docs;
            $y_exp = (int)($y['years_experience'] ?? 0);
            $x_exp = (int)($x['years_experience'] ?? 0);
            if ($y_exp !== $x_exp) return $y_exp <=> $x_exp;
            // earlier created first (longer in pipeline)
            return strcmp((string)($x['created_at'] ?? ''), (string)($y['created_at'] ?? ''));
        });

        if ($limit > 0 && count($rows) > $limit) {
            $rows = array_slice($rows, 0, $limit);
        }
        return $rows;
    }

    public function createReplacementInit(
        int $originalApplicantId,
        string $reason,
        string $reportText,
        array $attachmentsPaths,
        int $adminId
    ): ?int {
        try { $this->ensureApplicantReplacementsTable(); } catch (\Throwable $e) {}

        $allowedReasons = ['AWOL', 'Client Left', 'Not Finished Contract', 'Performance Issue', 'Other'];
        if (!in_array($reason, $allowedReasons, true)) {
            $reason = 'Other';
        }

        $orig = $this->getById($originalApplicantId);
        if (!$orig) { error_log('createReplacementInit: original not found'); return null; }
        if (($orig['status'] ?? '') !== 'approved') { error_log('createReplacementInit: original not approved'); return null; }

        $businessUnitId = isset($orig['business_unit_id']) ? (int)$orig['business_unit_id'] : null;
        if ($businessUnitId === null || $businessUnitId <= 0) { error_log('createReplacementInit: no BU'); return null; }

        $bookingId = $this->getLatestBookingIdForApplicant($originalApplicantId);
        $attachmentsJson = json_encode(array_values($attachmentsPaths), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sql = "INSERT INTO applicant_replacements
        (business_unit_id, original_applicant_id, client_booking_id, reason, report_text, attachments_json, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, 'selection', ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) { error_log('createReplacementInit prepare error: ' . $this->db->error); return null; }
        $bindBusinessUnit = ($businessUnitId !== null && $businessUnitId > 0) ? $businessUnitId : null;
        $bindBooking = $bookingId !== null ? $bookingId : null;
        $stmt->bind_param("iiisssi", $bindBusinessUnit, $originalApplicantId, $bindBooking, $reason, $reportText, $attachmentsJson, $adminId);
        if (!$stmt->execute()) { error_log('createReplacementInit insert error: ' . $stmt->error); $stmt->close(); return null; }
        $replaceId = (int) $this->db->insert_id;
        $stmt->close();

        $repNote = "Replacement Initiated (Reason: {$reason})\n" . $reportText;
        $checkCol = $this->db->query("SHOW COLUMNS FROM applicant_reports LIKE 'business_unit_id'");
        if ($checkCol && $checkCol->num_rows > 0) {
            $stmt2 = $this->db->prepare("INSERT INTO applicant_reports (applicant_id, business_unit_id, admin_id, note_text) VALUES (?, ?, ?, ?)");
            if ($stmt2) { $stmt2->bind_param("iiis", $originalApplicantId, $businessUnitId, $adminId, $repNote); $stmt2->execute(); $stmt2->close(); }
        } else {
            $stmt2 = $this->db->prepare("INSERT INTO applicant_reports (applicant_id, admin_id, note_text) VALUES (?, ?, ?)");
            if ($stmt2) { $stmt2->bind_param("iis", $originalApplicantId, $adminId, $repNote); $stmt2->execute(); $stmt2->close(); }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $action = 'Start Replacement';
        $desc = "Start replacement for Applicant ID {$originalApplicantId}; Reason: {$reason}";
        $stmt3 = $this->db->prepare("INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        if ($stmt3) { $stmt3->bind_param("isss", $adminId, $action, $desc, $ip); $stmt3->execute(); $stmt3->close(); }

        return $replaceId;
    }

    public function getReplacementById(int $replaceId): ?array
    {
        $this->ensureApplicantReplacementsTable();
        $stmt = $this->db->prepare("SELECT * FROM applicant_replacements WHERE id = ? LIMIT 1");
        if (!$stmt) {
            error_log('getReplacementById prepare failed: ' . $this->db->error);
            return null;
        }
        $stmt->bind_param("i", $replaceId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Assign replacement and move statuses (candidate -> on_process, original -> on_hold)
     */
    public function assignReplacement(int $replaceId, int $replacementApplicantId, int $adminId): bool
    {
        $allowedCandidateStatuses = ['pending', 'approved', 'on_process'];

        $this->ensureApplicantReplacementsTable();
        $this->db->begin_transaction();
        try {
            $sqlLock = "
                SELECT id, original_applicant_id, replacement_applicant_id, status, business_unit_id
                FROM applicant_replacements
                WHERE id = ?
                FOR UPDATE
            ";
            $stmt = $this->db->prepare($sqlLock);
            if (!$stmt) throw new \RuntimeException('Failed to prepare replacement lock statement.');
            $stmt->bind_param('i', $replaceId);
            $stmt->execute();
            $res = $stmt->get_result();
            $rep = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if (!$rep) throw new \RuntimeException('Replacement record not found.');
            if (!empty($rep['replacement_applicant_id'])) throw new \RuntimeException('This replacement is already assigned.');
            if (strtolower((string)$rep['status']) !== 'selection') throw new \RuntimeException('Replacement not in selectable state.');

            $originalId = (int)$rep['original_applicant_id'];
            $repBuId    = isset($rep['business_unit_id']) ? (int)$rep['business_unit_id'] : null;
            if ($originalId <= 0) throw new \RuntimeException('Invalid original applicant link.');

            $origAgency = $this->getAgencyCodeByApplicantId($originalId);
            if (strtolower((string)$origAgency) !== self::CSNK_AGENCY_CODE) throw new \RuntimeException('Operation blocked: original applicant is not CSNK.');
            $candAgency = $this->getAgencyCodeByApplicantId($replacementApplicantId);
            if (strtolower((string)$candAgency) !== self::CSNK_AGENCY_CODE) throw new \RuntimeException('Candidate is not from CSNK.');
            if ($replacementApplicantId === $originalId) throw new \RuntimeException('Cannot assign the same person as their own replacement.');

            // Load statuses + BU
            $st = $this->db->prepare("SELECT status, business_unit_id FROM applicants WHERE id = ? LIMIT 1");
            if (!$st) throw new \RuntimeException('Failed to prepare original status check.');
            $st->bind_param('i', $originalId);
            $st->execute();
            $ro = $st->get_result();
            $origRow = $ro ? $ro->fetch_assoc() : null;
            $st->close();
            if (!$origRow) throw new \RuntimeException('Original applicant not found.');
            $origStatus = strtolower((string)$origRow['status']);
            $origBuId   = (int)($origRow['business_unit_id'] ?? $repBuId);

            $st = $this->db->prepare("SELECT status, business_unit_id FROM applicants WHERE id = ? LIMIT 1");
            if (!$st) throw new \RuntimeException('Failed to prepare candidate status check.');
            $st->bind_param('i', $replacementApplicantId);
            $st->execute();
            $rc = $st->get_result();
            $candRow = $rc ? $rc->fetch_assoc() : null;
            $st->close();
            if (!$candRow) throw new \RuntimeException('Candidate not found.');
            $candStatus = strtolower((string)$candRow['status']);
            $candBuId   = (int)($candRow['business_unit_id'] ?? null);

            if (!in_array($candStatus, $allowedCandidateStatuses, true))
                throw new \RuntimeException('Candidate not in assignable status (Pending/Approved/On-Process).');

            // Assign
            $stmt = $this->db->prepare("
                UPDATE applicant_replacements
                SET replacement_applicant_id = ?, status = 'assigned', assigned_at = NOW()
                WHERE id = ?
                  AND replacement_applicant_id IS NULL
                  AND status IN ('selection')
                LIMIT 1
            ");
            if (!$stmt) throw new \RuntimeException('Failed to prepare assignment update.');
            $stmt->bind_param('ii', $replacementApplicantId, $replaceId);
            $stmt->execute();
            $affectedAssign = $stmt->affected_rows;
            $stmt->close();
            if ($affectedAssign !== 1) throw new \RuntimeException('Failed to assign (already assigned?).');

            // Candidate -> on_process (+ report)
            if (in_array($candStatus, ['pending', 'approved'], true)) {
                $stmt = $this->db->prepare("UPDATE applicants SET status = 'on_process', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('i', $replacementApplicantId);
                    $stmt->execute();
                    $stmt->close();
                    $reportText = "Replacement assignment — moved from {$candStatus} to on_process.";
                    $stmt2 = $this->db->prepare("
                        INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                        VALUES (?, ?, ?, 'on_process', ?, ?)
                    ");
                    if ($stmt2) {
                        $stmt2->bind_param('iissi', $replacementApplicantId, $candBuId, $candStatus, $reportText, $adminId);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                }
            }

            // Original -> on_hold (+ status + note)
            $fromOriginal = $origStatus ?: 'approved';
            $stmt = $this->db->prepare("UPDATE applicants SET status = 'on_hold', updated_at = NOW() WHERE id = ? AND status <> 'on_hold' LIMIT 1");
            if ($stmt) { $stmt->bind_param('i', $originalId); $stmt->execute(); $stmt->close(); }

            $origReport = "Replaced by Applicant ID {$replacementApplicantId}. Original moved to on_hold.";
            $stmt2 = $this->db->prepare("
                INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                VALUES (?, ?, ?, 'on_hold', ?, ?)
            ");
            if ($stmt2) {
                $stmt2->bind_param('iissi', $originalId, $origBuId, $fromOriginal, $origReport, $adminId);
                $stmt2->execute();
                $stmt2->close();
            }

            $stmt3 = $this->db->prepare("INSERT INTO applicant_reports (applicant_id, business_unit_id, admin_id, note_text) VALUES (?, ?, ?, ?)");
            if ($stmt3) {
                $note = "Replaced by Applicant ID {$replacementApplicantId}. Status moved to On Hold.";
                $stmt3->bind_param('iiis', $originalId, $origBuId, $adminId, $note);
                $stmt3->execute();
                $stmt3->close();
            }

            // Activity log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $action = 'Assign Replacement';
            $desc = "Assigned Applicant ID {$replacementApplicantId} as replacement for Original ID {$originalId}; original set to On Hold";
            $stmt = $this->db->prepare("INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            if ($stmt) { $stmt->bind_param("isss", $adminId, $action, $desc, $ip); $stmt->execute(); $stmt->close(); }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('assignReplacement error: ' . $e->getMessage());
            return false;
        }
    }

    /* ============================================================
     * COUNTRY FILTERING (for SMC international applicants)
     * ============================================================ */

    public function getCountriesWithCounts(?int $businessUnitId = null): array
    {
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

        $countSql = "
            SELECT 
                bu.country_id,
                COUNT(a.id) AS applicant_count
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            WHERE a.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM blacklisted_applicants b
                  WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ";

        if ($businessUnitId !== null && $businessUnitId > 0) {
            $countSql .= " AND a.business_unit_id = " . (int) $businessUnitId;
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
                'id'   => (int) $c['id'],
                'name' => $c['country_name'],
                'iso2' => $c['iso2'],
                'count'=> $counts[$c['id']] ?? 0
            ];
        }

        return $result;
    }

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
              AND c.id != 1
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
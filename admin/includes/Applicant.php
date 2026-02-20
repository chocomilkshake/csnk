<?php
class Applicant {
    /** @var mysqli */
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    /* ============================================================
     * EXISTING METHODS (kept)
     * ============================================================ */

    /**
     * Admin/general: Get all applicants (optionally by a single status).
     * - Excludes deleted by default.
     * - Use getAllForPublic() for client-facing lists.
     */
    public function getAll($status = null) {
        if ($status !== null) {
            $sql = "SELECT * FROM applicants
                    WHERE deleted_at IS NULL
                      AND status = ?
                      AND NOT EXISTS (
                        SELECT 1 FROM blacklisted_applicants b
                        WHERE b.applicant_id = applicants.id AND b.is_active = 1
                      )
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        $sql = "
            SELECT *
            FROM applicants
            WHERE deleted_at IS NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = applicants.id AND b.is_active = 1
              )
            ORDER BY created_at DESC
        ";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * NEW (Client-facing): Get list for public site.
     * - Returns only non-deleted, non-approved applicants
     *   i.e., status IN ('pending','on_process').
     * - Ordered by created_at DESC.
     */
    public function getAllForPublic(): array {
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
        if (!$res) return [];
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getDeleted() {
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

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM applicants WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Create applicant (includes specialization_skills JSON).
     */
    public function create($data) {
        $sql = "INSERT INTO applicants (
                    first_name, middle_name, last_name, suffix,
                    phone_number, alt_phone_number, email, date_of_birth, address,
                    educational_attainment, work_history, preferred_location, languages, specialization_skills,
                    picture, status, employment_type, education_level, years_experience, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        /*
         * bind_param types:
         * - 18 strings: first_name .. education_level  (18 x 's')
         * - 2 integers: years_experience, created_by   (2 x 'i')
         */
        $stmt->bind_param(
            "ssssssssssssssssssii",
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['suffix'],
            $data['phone_number'],
            $data['alt_phone_number'],
            $data['email'],
            $data['date_of_birth'],
            $data['address'],
            $data['educational_attainment'], // JSON
            $data['work_history'],           // JSON
            $data['preferred_location'],     // JSON
            $data['languages'],              // JSON
            $data['specialization_skills'],  // JSON
            $data['picture'],
            $data['status'],
            $data['employment_type'],        // string
            $data['education_level'],        // string
            $data['years_experience'],       // int
            $data['created_by']              // int
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Update applicant (includes specialization_skills JSON).
     */
    public function update($id, $data) {
        $sql = "UPDATE applicants SET
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    phone_number = ?, alt_phone_number = ?, email = ?, date_of_birth = ?, address = ?,
                    educational_attainment = ?, work_history = ?, preferred_location = ?, languages = ?, specialization_skills = ?,
                    picture = ?, status = ?, employment_type = ?, education_level = ?, years_experience = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        /*
         * bind_param types:
         * - 18 strings before years_experience: first_name .. education_level (18 x 's')
         * - 1 integer for years_experience (i)
         * - 1 integer for id (i)
         *
         * Total: "ssssssssssssssssssii"
         */
        $stmt->bind_param(
            "ssssssssssssssssssii",
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['suffix'],
            $data['phone_number'],
            $data['alt_phone_number'],
            $data['email'],
            $data['date_of_birth'],
            $data['address'],
            $data['educational_attainment'], // JSON
            $data['work_history'],           // JSON
            $data['preferred_location'],     // JSON
            $data['languages'],              // JSON
            $data['specialization_skills'],  // JSON
            $data['picture'],
            $data['status'],
            $data['employment_type'],        // string
            $data['education_level'],        // string
            $data['years_experience'],       // int
            $id                               // int
        );

        return $stmt->execute();
    }

    /**
     * Update video fields for an applicant
     */
    public function updateVideoFields($id, $videoData) {
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

    public function softDelete($id) {
        $stmt = $this->db->prepare("UPDATE applicants SET deleted_at = NOW(), status = 'deleted' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function restore($id) {
        $stmt = $this->db->prepare("UPDATE applicants SET deleted_at = NULL, status = 'pending' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function permanentDelete($id) {
        $stmt = $this->db->prepare("DELETE FROM applicants WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE applicants SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    /** Convenience for booking workflow */
    public function markOnProcess(int $id): bool {
        $stmt = $this->db->prepare("UPDATE applicants SET status = 'on_process', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getDocuments($applicantId) {
        $stmt = $this->db->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ?");
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addDocument($applicantId, $documentType, $filePath) {
        $stmt = $this->db->prepare("INSERT INTO applicant_documents (applicant_id, document_type, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $applicantId, $documentType, $filePath);
        return $stmt->execute();
    }

    public function deleteDocument($documentId) {
        $stmt = $this->db->prepare("DELETE FROM applicant_documents WHERE id = ?");
        $stmt->bind_param("i", $documentId);
        return $stmt->execute();
    }

    /** NEW: delete all docs of a given type for an applicant (for replacement) */
    public function deleteDocumentsByType($applicantId, $documentType) {
        $stmt = $this->db->prepare("DELETE FROM applicant_documents WHERE applicant_id = ? AND document_type = ?");
        $stmt->bind_param("is", $applicantId, $documentType);
        return $stmt->execute();
    }

    public function getStatistics() {
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
    public function getOnProcessWithLatestBooking(): array {
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
        if (!$res) return [];
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /* ============================================================
     * REPLACEMENT FEATURE — NEW METHODS
     * ============================================================ */

    /** Ensure table applicant_replacements exists (safe to call many times) */
    private function ensureApplicantReplacementsTable(): void {
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
        try { $this->db->query($sql); } catch (\Throwable $e) { /* silent */ }
    }

    /** Helper: safe JSON decode to array */
    private function decodeJsonArray($val): array {
        if ($val === null || $val === '' || $val === '[]') return [];
        $arr = json_decode((string)$val, true);
        return is_array($arr) ? $arr : [];
    }

    /** Normalizes array of strings (trim, lowercase, unique) */
    private function normalizeStringArray(array $arr): array {
        $out = [];
        foreach ($arr as $v) {
            $s = strtolower(trim((string)$v));
            if ($s !== '' && !in_array($s, $out, true)) {
                $out[] = $s;
            }
        }
        return $out;
    }

    /** Count overlap items between two string arrays (case-insensitive) */
    private function overlapCount(array $a, array $b): int {
        $a = $this->normalizeStringArray($a);
        $b = $this->normalizeStringArray($b);
        return count(array_intersect($a, $b));
    }

    /** Get latest booking id for applicant (or null) */
    public function getLatestBookingIdForApplicant(int $applicantId): ?int {
        $sql = "SELECT id FROM client_bookings WHERE applicant_id = ? ORDER BY created_at DESC LIMIT 1";
        if (!$stmt = $this->db->prepare($sql)) return null;
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        return $row ? (int)$row['id'] : null;
    }

    /**
     * Compute similarity score for candidate against original.
     * score = (2 * skill_overlap) + (1 * city_overlap)
     */
    private function computeSimilarityScore(array $original, array $candidate): int {
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
     */
    public function searchPendingCandidatesForReplacement(int $originalApplicantId, int $limit = 50): array {
        $original = $this->getById($originalApplicantId);
        if (!$original) return [];

        $sql = "
            SELECT *
            FROM applicants a
            WHERE a.status = 'pending'
              AND a.deleted_at IS NULL
              AND NOT EXISTS (SELECT 1 FROM blacklisted_applicants b WHERE b.applicant_id = a.id AND b.is_active = 1)
        ";
        $res = $this->db->query($sql);
        if (!$res) return [];
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        // Score & sort
        foreach ($rows as &$r) {
            $r['_score'] = $this->computeSimilarityScore($original, $r);
        }
        unset($r);

        usort($rows, function($x, $y) {
            // Sort by score DESC, then years_experience DESC, then created_at ASC
            if ($y['_score'] !== $x['_score']) return $y['_score'] <=> $x['_score'];
            $yx = (int)($y['years_experience'] ?? 0);
            $xx = (int)($x['years_experience'] ?? 0);
            if ($yx !== $xx) return $yx <=> $xx;
            return strcmp((string)($x['created_at'] ?? ''), (string)($y['created_at'] ?? ''));
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

        $allowedReasons = ['AWOL','Client Left','Not Finished Contract','Performance Issue','Other'];
        if (!in_array($reason, $allowedReasons, true)) {
            $reason = 'Other';
        }

        $orig = $this->getById($originalApplicantId);
        if (!$orig || ($orig['status'] ?? '') !== 'approved') {
            return null; // Only allowed from approved original
        }

        // Capture latest booking (if any)
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
        $replaceId = (int)$this->db->insert_id;

        // Write applicant report for the original
        $repNote = "Replacement Initiated (Reason: {$reason})\n" . $reportText;
        $stmt2 = $this->db->prepare("INSERT INTO applicant_reports (applicant_id, admin_id, note_text) VALUES (?, ?, ?)");
        $stmt2->bind_param("iis", $originalApplicantId, $adminId, $repNote);
        $stmt2->execute();

        // Activity log
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
        $action = 'Start Replacement';
        $desc = "Start replacement for Applicant ID {$originalApplicantId}; Reason: {$reason}";
        $stmt3 = $this->db->prepare("INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("isss", $adminId, $action, $desc, $ip);
        $stmt3->execute();

        return $replaceId;
    }

    /** Fetch a replacement record by id */
    public function getReplacementById(int $replaceId): ?array {
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
     * - insert applicant_status_reports
     * - reassign client_bookings (single id stored on replacement)
     * - update applicant_replacements row
     * - activity_logs
     */
    public function assignReplacement(int $replaceId, int $replacementApplicantId, int $adminId): bool {
        $this->ensureApplicantReplacementsTable();

        // Load replacement record
        $rep = $this->getReplacementById($replaceId);
        if (!$rep || ($rep['status'] ?? '') !== 'selection') {
            return false;
        }

        $originalId = (int)$rep['original_applicant_id'];
        $original = $this->getById($originalId);
        if (!$original) return false;

        $candidate = $this->getById($replacementApplicantId);
        if (!$candidate || ($candidate['status'] ?? '') !== 'pending') {
            return false;
        }

        $clientBookingId = $rep['client_booking_id'] !== null ? (int)$rep['client_booking_id'] : null;
        $reason = (string)$rep['reason'];

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

            // 2) Insert status report line
            $stmt2 = $this->db->prepare("
                INSERT INTO applicant_status_reports (applicant_id, from_status, to_status, report_text, admin_id)
                VALUES (?, 'pending', 'on_process', ?, ?)
            ");
            $stmt2->bind_param("isi", $replacementApplicantId, $reportText, $adminId);
            $stmt2->execute();

            // 3) Reassign client booking if available
            if ($clientBookingId !== null) {
                $stmt3 = $this->db->prepare("UPDATE client_bookings SET applicant_id = ?, updated_at = NOW() WHERE id = ?");
                $stmt3->bind_param("ii", $replacementApplicantId, $clientBookingId);
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
            $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
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
}
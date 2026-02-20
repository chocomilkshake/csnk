<?php
class Applicant
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database->getConnection();
    }

    /**
     * Admin/general: Get all applicants (optionally by a single status).
     * - Excludes deleted by default.
     * - Use getAllForPublic() for client-facing lists.
     */
    public function getAll($status = null)
    {
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
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Create applicant (includes specialization_skills JSON).
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

        if ($dailyRate === null) {
            // Use NULL explicitly in SQL for true NULL
            $sql = "INSERT INTO applicants (
                    first_name, middle_name, last_name, suffix,
                    phone_number, alt_phone_number, email, date_of_birth, address,
                    educational_attainment, work_history, daily_rate, preferred_location, languages, specialization_skills,
                    picture, status, employment_type, education_level, years_experience, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (create NULL rate): ' . $this->db->error);
                return false;
            }
            // 18 strings + 2 ints (years_experience, created_by)
            $stmt->bind_param(
                "ssssssssssssssssssii",
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
                $createdBy
            );
        } else {
            // daily_rate bound as double (d)
            $sql = "INSERT INTO applicants (
                    first_name, middle_name, last_name, suffix,
                    phone_number, alt_phone_number, email, date_of_birth, address,
                    educational_attainment, work_history, daily_rate, preferred_location, languages, specialization_skills,
                    picture, status, employment_type, education_level, years_experience, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (create with rate): ' . $this->db->error);
                return false;
            }
            // 12th param is daily_rate (double), then the rest
            // Types: 18 strings + 1 double + 2 ints => but order matters
            // Our order: 11 strings, 1 double, 6 strings, 2 ints => "sssssssssss" + "d" + "ssssss" + "ii"
            $stmt->bind_param(
                "sssssssssss" . "d" . "sssssss" . "ii",
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
                $createdBy
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
     * Update applicant (includes specialization_skills JSON).
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

        if ($dailyRate === null) {
            // daily_rate to NULL
            $sql = "UPDATE applicants SET
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    phone_number = ?, alt_phone_number = ?, email = ?, date_of_birth = ?, address = ?,
                    educational_attainment = ?, work_history = ?, daily_rate = NULL, preferred_location = ?, languages = ?, specialization_skills = ?,
                    picture = ?, status = ?, employment_type = ?, education_level = ?, years_experience = ?
                WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (update NULL rate): ' . $this->db->error);
                return false;
            }
            // 18 strings + 1 int (years) + 1 int (id)
            $stmt->bind_param(
                "ssssssssssssssssssii",
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
                $id
            );
        } else {
            // daily_rate bound as double
            $sql = "UPDATE applicants SET
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    phone_number = ?, alt_phone_number = ?, email = ?, date_of_birth = ?, address = ?,
                    educational_attainment = ?, work_history = ?, daily_rate = ?, preferred_location = ?, languages = ?, specialization_skills = ?,
                    picture = ?, status = ?, employment_type = ?, education_level = ?, years_experience = ?
                WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (update with rate): ' . $this->db->error);
                return false;
            }
            // Order before id: 11 strings, 1 double, 6 strings, 1 int (years) → then id
            // Types: "sssssssssss" + "d" + "ssssss" + "i" + "i"
            $stmt->bind_param(
                "sssssssssss" . "d" . "sssssss" . "ii",
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
        return $stmt->execute();
    }

    public function restore($id)
    {
        $stmt = $this->db->prepare("UPDATE applicants SET deleted_at = NULL, status = 'pending' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function permanentDelete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM applicants WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateStatus($id, $status)
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

    public function getDocuments($applicantId)
    {
        $stmt = $this->db->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ?");
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addDocument($applicantId, $documentType, $filePath)
    {
        $stmt = $this->db->prepare("INSERT INTO applicant_documents (applicant_id, document_type, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $applicantId, $documentType, $filePath);
        return $stmt->execute();
    }

    public function deleteDocument($documentId)
    {
        $stmt = $this->db->prepare("DELETE FROM applicant_documents WHERE id = ?");
        $stmt->bind_param("i", $documentId);
        return $stmt->execute();
    }

    /** NEW: delete all docs of a given type for an applicant (for replacement) */
    public function deleteDocumentsByType($applicantId, $documentType)
    {
        $stmt = $this->db->prepare("DELETE FROM applicant_documents WHERE applicant_id = ? AND document_type = ?");
        $stmt->bind_param("is", $applicantId, $documentType);
        return $stmt->execute();
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

}


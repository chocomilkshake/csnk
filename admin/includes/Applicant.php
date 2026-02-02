<?php
class Applicant {
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    public function getAll($status = null) {
        $sql = "SELECT * FROM applicants WHERE deleted_at IS NULL";
        if ($status) {
            $sql .= " AND status = '" . $this->db->real_escape_string($status) . "'";
        }
        $sql .= " ORDER BY created_at DESC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getDeleted() {
        $sql = "SELECT * FROM applicants WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
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

        $result = $this->db->query("SELECT COUNT(*) as total FROM applicants WHERE deleted_at IS NULL");
        $stats['total'] = $result->fetch_assoc()['total'] ?? 0;

        $result = $this->db->query("SELECT COUNT(*) as pending FROM applicants WHERE status = 'pending' AND deleted_at IS NULL");
        $stats['pending'] = $result->fetch_assoc()['pending'] ?? 0;

        $result = $this->db->query("SELECT COUNT(*) as on_process FROM applicants WHERE status = 'on_process' AND deleted_at IS NULL");
        $stats['on_process'] = $result->fetch_assoc()['on_process'] ?? 0;

        $result = $this->db->query("SELECT COUNT(*) as deleted FROM applicants WHERE deleted_at IS NOT NULL");
        $stats['deleted'] = $result->fetch_assoc()['deleted'] ?? 0;

        return $stats;
    }
}
<?php
class Admin {
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    public function getAll() {
        $sql = "SELECT id, username, email, full_name, avatar, role, status, created_at FROM admin_users ORDER BY created_at DESC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function create($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("INSERT INTO admin_users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss",
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['full_name'],
            $data['role'],
            $data['status']
        );

        return $stmt->execute();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE admin_users SET username = ?, email = ?, full_name = ?, role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssi",
            $data['username'],
            $data['email'],
            $data['full_name'],
            $data['role'],
            $data['status'],
            $id
        );

        return $stmt->execute();
    }

    public function updatePassword($id, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    public function updateProfile($id, $data) {
        $stmt = $this->db->prepare("UPDATE admin_users SET username = ?, email = ?, full_name = ?, avatar = ? WHERE id = ?");
        $stmt->bind_param("ssssi",
            $data['username'],
            $data['email'],
            $data['full_name'],
            $data['avatar'],
            $id
        );

        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function usernameExists($username, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $username, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = ?");
            $stmt->bind_param("s", $username);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function emailExists($email, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM admin_users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM admin_users WHERE email = ?");
            $stmt->bind_param("s", $email);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }
}

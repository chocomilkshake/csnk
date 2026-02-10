<?php
class Admin {
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    /**
     * Get all admin users.
     * @param bool $excludeSuperAdmins If true, exclude users with role 'super_admin'
     */
    public function getAll(bool $excludeSuperAdmins = false): array {
        if ($excludeSuperAdmins) {
            $sql = "SELECT id, username, email, full_name, avatar, role, status, created_at
                    FROM admin_users
                    WHERE role <> 'super_admin'
                    ORDER BY created_at DESC";
        } else {
            $sql = "SELECT id, username, email, full_name, avatar, role, status, created_at
                    FROM admin_users
                    ORDER BY created_at DESC";
        }
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get users by role.
     */
    public function getByRole(string $role): array {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, full_name, avatar, role, status, created_at
             FROM admin_users
             WHERE role = ?
             ORDER BY created_at DESC"
        );
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? ($result->fetch_assoc() ?: null) : null;
    }

    public function create(array $data): bool {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            "INSERT INTO admin_users (username, email, password, full_name, role, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssssss",
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['full_name'],
            $data['role'],
            $data['status']
        );

        return $stmt->execute();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE admin_users
             SET username = ?, email = ?, full_name = ?, role = ?, status = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            "sssssi",
            $data['username'],
            $data['email'],
            $data['full_name'],
            $data['role'],
            $data['status'],
            $id
        );

        return $stmt->execute();
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    public function updateProfile(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE admin_users SET username = ?, email = ?, full_name = ?, avatar = ? WHERE id = ?"
        );
        $stmt->bind_param(
            "ssssi",
            $data['username'],
            $data['email'],
            $data['full_name'],
            $data['avatar'],
            $id
        );

        return $stmt->execute();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool {
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
        return (int)($row['count'] ?? 0) > 0;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool {
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
        return (int)($row['count'] ?? 0) > 0;
    }
}
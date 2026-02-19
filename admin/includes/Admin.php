<?php
// FILE: includes/Admin.php
// Note: Requires `admin_users` table to have `agency ENUM('csnk','smc') NULL`.
// For employees: agency must be 'csnk' or 'smc'.
// For admin/super_admin: agency must be NULL.

class Admin {
    /** @var mysqli */
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    /**
     * Hash password using Argon2id if available, else Bcrypt.
     */
    private function hashPassword(string $plain): string {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($plain, PASSWORD_ARGON2ID);
        }
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    /**
     * Normalize/validate role.
     */
    private function normalizeRole(string $role): string {
        $r = strtolower(trim($role));
        return in_array($r, ['super_admin','admin','employee'], true) ? $r : 'employee';
    }

    /**
     * Normalize/validate agency by role.
     * - For 'employee': must be 'csnk' or 'smc'.
     * - For 'admin'/'super_admin': must be null.
     *
     * @return string|null normalized agency or null
     */
    private function computeAgencyForRole(string $role, ?string $agency): ?string {
        $role = $this->normalizeRole($role);
        if ($role === 'employee') {
            $ag = strtolower((string)$agency);
            if (in_array($ag, ['csnk','smc'], true)) {
                return $ag;
            }
            // invalid for employee -> caller should catch this and fail
            return null;
        }
        // admin/super_admin => global scope
        return null;
    }

    /**
     * Get all admin users.
     * @param bool $excludeSuperAdmins If true, exclude users with role 'super_admin'
     */
    public function getAll(bool $excludeSuperAdmins = false): array {
        if ($excludeSuperAdmins) {
            $sql = "SELECT id, username, email, full_name, avatar, role, status, agency, created_at
                    FROM admin_users
                    WHERE role <> 'super_admin'
                    ORDER BY created_at DESC";
        } else {
            $sql = "SELECT id, username, email, full_name, avatar, role, status, agency, created_at
                    FROM admin_users
                    ORDER BY created_at DESC";
        }
        $result = $this->db->query($sql);
        return $result ? ($result->fetch_all(MYSQLI_ASSOC) ?: []) : [];
    }

    /**
     * Get users by role.
     */
    public function getByRole(string $role): array {
        $role = $this->normalizeRole($role);
        $stmt = $this->db->prepare(
            "SELECT id, username, email, full_name, avatar, role, status, agency, created_at
             FROM admin_users
             WHERE role = ?
             ORDER BY created_at DESC"
        );
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? ($res->fetch_all(MYSQLI_ASSOC) ?: []) : [];
    }

    /**
     * Get single user by id (returns all columns).
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? ($result->fetch_assoc() ?: null) : null;
    }

    /**
     * Create user.
     * Expected keys in $data: username, email, password, full_name, role, status, (agency for employees)
     * - Employees: agency required ('csnk' or 'smc')
     * - Admin/Super Admin: agency forced to NULL (global)
     */
    public function create(array $data): bool {
        $username  = (string)($data['username'] ?? '');
        $email     = (string)($data['email'] ?? '');
        $fullName  = (string)($data['full_name'] ?? '');
        $role      = $this->normalizeRole((string)($data['role'] ?? 'employee'));
        $status    = in_array(($data['status'] ?? 'active'), ['active','inactive'], true) ? (string)$data['status'] : 'active';
        $plainPwd  = (string)($data['password'] ?? '');

        if ($username === '' || $email === '' || $fullName === '' || $plainPwd === '') {
            return false;
        }

        // Compute/validate agency by role
        $agencyInput = $data['agency'] ?? null;
        $agency = $this->computeAgencyForRole($role, $agencyInput);
        if ($role === 'employee' && $agency === null) {
            // agency required for employees
            return false;
        }

        $hashedPassword = $this->hashPassword($plainPwd);

        // Insert includes agency column
        $stmt = $this->db->prepare(
            "INSERT INTO admin_users (username, email, password, full_name, role, status, agency)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        // agency can be null for admin/super_admin
        $stmt->bind_param(
            "sssssss",
            $username,
            $email,
            $hashedPassword,
            $fullName,
            $role,
            $status,
            $agency
        );

        return $stmt->execute();
    }

    /**
     * Update user (general admin edit).
     * Expected keys in $data: username, email, full_name, role, status, (agency for employees)
     *
     * NOTE: This repository layer does not enforce RBAC; enforce in your controller/UI.
     * We do ensure agency validity based on final role:
     *  - If final role is 'employee' => agency must be 'csnk' or 'smc' (if provided, else keep existing)
     *  - If final role is admin/super_admin => agency = NULL
     */
    public function update(int $id, array $data): bool {
        $existing = $this->getById($id);
        if (!$existing) return false;

        $newUsername  = (string)($data['username']  ?? $existing['username']);
        $newEmail     = (string)($data['email']     ?? $existing['email']);
        $newFullName  = (string)($data['full_name'] ?? $existing['full_name']);
        $newRole      = $this->normalizeRole((string)($data['role'] ?? $existing['role']));
        $newStatus    = in_array(($data['status'] ?? $existing['status']), ['active','inactive'], true) ? (string)$data['status'] : $existing['status'];

        // Determine new agency based on final role
        $newAgency = $existing['agency'] ?? null;
        if ($newRole === 'employee') {
            if (array_key_exists('agency', $data)) {
                $candidate = $this->computeAgencyForRole('employee', (string)$data['agency']);
                if ($candidate === null) {
                    // invalid agency for employee
                    return false;
                }
                $newAgency = $candidate;
            } else {
                // If not provided, keep what exists; but ensure it is valid
                $newAgency = $this->computeAgencyForRole('employee', (string)$newAgency);
                if ($newAgency === null) return false; // ensure invariant
            }
        } else {
            // admin/super_admin => force null
            $newAgency = null;
        }

        $stmt = $this->db->prepare(
            "UPDATE admin_users
             SET username = ?, email = ?, full_name = ?, role = ?, status = ?, agency = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            "ssssssi",
            $newUsername,
            $newEmail,
            $newFullName,
            $newRole,
            $newStatus,
            $newAgency,
            $id
        );

        return $stmt->execute();
    }

    /**
     * Update only password.
     */
    public function updatePassword(int $id, string $newPassword): bool {
        $hashedPassword = $this->hashPassword($newPassword);
        $stmt = $this->db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    /**
     * Update a subset of profile fields (no role/status/agency here).
     */
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

    /**
     * Delete user.
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Check if username exists (optionally excluding a specific id).
     */
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
        $row = $result ? $result->fetch_assoc() : null;
        return (int)($row['count'] ?? 0) > 0;
    }

    /**
     * Check if email exists (optionally excluding a specific id).
     */
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
        $row = $result ? $result->fetch_assoc() : null;
        return (int)($row['count'] ?? 0) > 0;
    }
}
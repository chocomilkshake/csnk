<?php
// FILE: includes/Admin.php
// Note: Requires `admin_users` table to have `agency ENUM('csnk','smc') NULL`.
// For employees: agency must be 'csnk' or 'smc'.
// For admin/super_admin: agency must be NULL.

class Admin
{
    /** @var mysqli */
    private $db;

    public function __construct($database)
    {
        $this->db = $database->getConnection();
    }

    /**
     * Hash password using Argon2id if available, else Bcrypt.
     */
    private function hashPassword(string $plain): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($plain, PASSWORD_ARGON2ID);
        }
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    /**
     * Normalize/validate role.
     */
    private function normalizeRole(string $role): string
    {
        $r = strtolower(trim($role));
        return in_array($r, ['super_admin', 'admin', 'employee'], true) ? $r : 'employee';
    }

    /**
     * Get active agencies (used for account creation/filtering).
     *
     * Returns array of ['code'=>..., 'name'=>...] rows from agencies table.
     */
    public function getAgencies(): array
    {
        $sql = "SELECT code, name FROM agencies WHERE active = 1 ORDER BY name ASC";
        $result = $this->db->query($sql);
        return $result ? ($result->fetch_all(MYSQLI_ASSOC) ?: []) : [];
    }

    /**
     * Get active branches for dropdown.
     */
    public function getActiveBranches(): array
    {
        $sql = "SELECT id, code, name FROM csnk_branches WHERE status = 'ACTIVE' ORDER BY sort_order ASC, name ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get active SMC business units (agency_id=2) with country info.
     * Mirrors getActiveBranches() for SMC.
     */
    public function getActiveBusinessUnits(int $agencyId = 2): array
    {
        $sql = "SELECT 
                    bu.id, 
                    bu.code,
                    CONCAT(c.iso2, ' - ', c.name) AS name,
                    bu.active AS status,
                    bu.sort_order,
                    bu.is_default
                FROM business_units bu
                JOIN countries c ON bu.country_id = c.id
                WHERE bu.agency_id = ? AND bu.active = 1 AND c.active = 1
                ORDER BY bu.sort_order ASC, c.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $agencyId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get SMC business units for dropdown (id => label).
     */
    public function getSmcBusinessUnitsForDropdown(int $agencyId = 2): array
    {
        $units = $this->getActiveBusinessUnits($agencyId);
        $dropdown = [];
        foreach ($units as $unit) {
            $dropdown[$unit['id']] = $unit['name'] . (isset($unit['code']) ? ' (' . $unit['code'] . ')' : '');
        }
        return $dropdown;
    }

    /**
     * Validate branch_id for role.
     * Employee: must be valid active branch ID.
     * Admin/Super Admin: NULL or valid branch ID.
     */
    private function validateBranchIdForRole(string $role, ?int $branchId): bool
    {
        $role = $this->normalizeRole($role);
        if ($role === 'employee') {
            if ($branchId === null || $branchId <= 0)
                return false;
            // Check exists and active
            $stmt = $this->db->prepare("SELECT id FROM csnk_branches WHERE id = ? AND status = 'ACTIVE'");
            $stmt->bind_param("i", $branchId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->num_rows > 0;
        }
        // admin/super_admin: optional
        return true;
    }

    /**
     * Get all admin users.
     * @param bool $excludeSuperAdmins If true, exclude users with role 'super_admin'
     */
    /**
     * Get all admin users with agency.
     */
    public function getAll(bool $excludeSuperAdmins = true): array
    {
        $sql = "SELECT au.id, au.username, au.email, au.full_name, au.avatar, au.role, au.status, au.agency, au.created_at,
                b.id as branch_id, b.code as branch_code, b.name as branch_name, au.business_unit_id
                FROM admin_users au
                LEFT JOIN csnk_branches b ON au.business_unit_id = b.id";
        if ($excludeSuperAdmins) {
            $sql .= " WHERE au.role <> 'super_admin'";
        }
        $sql .= " ORDER BY au.agency ASC, au.role DESC, au.created_at DESC";
        $result = $this->db->query($sql);
        return $result ? ($result->fetch_all(MYSQLI_ASSOC) ?: []) : [];
    }

    /**
     * Get users by agency.
     */
    public function getByAgency(?string $agency = null, string $role = 'employee'): array
    {
        $role = $this->normalizeRole($role);
        $sql = "SELECT au.id, au.username, au.email, au.full_name, au.avatar, au.role, au.status, au.agency, au.created_at,
                b.id as branch_id, b.code as branch_code, b.name as branch_name, au.business_unit_id
                FROM admin_users au
                LEFT JOIN csnk_branches b ON au.business_unit_id = b.id
                WHERE au.role = ?";
        $params = [$role];
        $types = "s";

        if ($agency && in_array($agency, ['csnk', 'smc'], true)) {
            $sql .= " AND au.agency = ?";
            $params[] = $agency;
            $types .= "s";
        }

        $sql .= " ORDER BY au.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? ($res->fetch_all(MYSQLI_ASSOC) ?: []) : [];
    }

    /**
     * Get users by role.
     */
    public function getByRole(string $role): array
    {
        $role = $this->normalizeRole($role);
        $stmt = $this->db->prepare(
            "SELECT au.id, au.username, au.email, au.full_name, au.avatar, au.role, au.status, au.agency, au.created_at,
             b.id as branch_id, b.code as branch_code, b.name as branch_name
             FROM admin_users au
             LEFT JOIN csnk_branches b ON au.business_unit_id = b.id
             WHERE au.role = ?
             ORDER BY au.created_at DESC"
        );
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? ($res->fetch_all(MYSQLI_ASSOC) ?: []) : [];
    }

    /**
     * Get users by role and branch filter.
     */
    public function getByRoleAndBranch(string $role, int $branchId): array
    {
        $role = $this->normalizeRole($role);
        $stmt = $this->db->prepare(
            "SELECT au.id, au.username, au.email, au.full_name, au.avatar, au.role, au.status, au.agency, au.created_at,
             b.id as branch_id, b.code as branch_code, b.name as branch_name
             FROM admin_users au
             INNER JOIN csnk_branches b ON au.business_unit_id = b.id
             WHERE au.role = ? AND b.id = ?
             ORDER BY au.created_at DESC"
        );
        $stmt->bind_param("si", $role, $branchId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? ($res->fetch_all(MYSQLI_ASSOC) ?: []) : [];
    }

    /**
     * Get single user by id (returns all columns).
     */
    public function getById(int $id): ?array
    {
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
    public function create(array $data): bool
    {
        $username = (string) ($data['username'] ?? '');
        $email = (string) ($data['email'] ?? '');
        $fullName = (string) ($data['full_name'] ?? '');
        $role = $this->normalizeRole((string) ($data['role'] ?? 'employee'));
        $status = in_array(($data['status'] ?? 'active'), ['active', 'inactive'], true) ? (string) $data['status'] : 'active';
        $plainPwd = (string) ($data['password'] ?? '');
        $branchId = isset($data['business_unit_id']) ? (int) $data['business_unit_id'] : null;
        $agency = isset($data['agency']) ? (string) $data['agency'] : null;

        if ($username === '' || $email === '' || $fullName === '' || $plainPwd === '') {
            return false;
        }

        // If this is an employee from a non‑CSNK agency we do not require a branch
        if (!($role === 'employee' && $agency !== 'csnk')) {
            // Validate branch_id for role (CSNK employees require a valid branch)
            if (!$this->validateBranchIdForRole($role, $branchId)) {
                return false;
            }
        }

        $hashedPassword = $this->hashPassword($plainPwd);

        // Insert with business_unit_id (agency kept for legacy)
        $stmt = $this->db->prepare(
            "INSERT INTO admin_users (username, email, password, full_name, role, status, business_unit_id, agency)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $agencyValue = $role === 'employee' ? ($data['agency'] ?? 'csnk') : null;
        // types: username (s), email (s), password (s), full_name (s),
        // role (s), status (s), business_unit_id (i), agency (s|null)
        $stmt->bind_param(
            "ssssssis",
            $username,
            $email,
            $hashedPassword,
            $fullName,
            $role,
            $status,
            $branchId,
            $agencyValue
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
    public function update(int $id, array $data): bool
    {
        $existing = $this->getById($id);
        if (!$existing)
            return false;

        $newUsername = (string) ($data['username'] ?? $existing['username']);
        $newEmail = (string) ($data['email'] ?? $existing['email']);
        $newFullName = (string) ($data['full_name'] ?? $existing['full_name']);
        $newRole = $this->normalizeRole((string) ($data['role'] ?? $existing['role']));
        $newStatus = in_array(($data['status'] ?? $existing['status']), ['active', 'inactive'], true) ? (string) $data['status'] : $existing['status'];
        $newBranchId = isset($data['business_unit_id']) ? (int) $data['business_unit_id'] : ($existing['business_unit_id'] ?? null);

        // Validate new branch_id for role
        if (!$this->validateBranchIdForRole($newRole, $newBranchId)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE admin_users
             SET username = ?, email = ?, full_name = ?, role = ?, status = ?, business_unit_id = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            "sssssii",
            $newUsername,
            $newEmail,
            $newFullName,
            $newRole,
            $newStatus,
            $newBranchId,
            $id
        );

        return $stmt->execute();
    }

    /**
     * Update only password.
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $hashedPassword = $this->hashPassword($newPassword);
        $stmt = $this->db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    /**
     * Update a subset of profile fields (no role/status/agency here).
     */
    public function updateProfile(int $id, array $data): bool
    {
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
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Check if username exists (optionally excluding a specific id).
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
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
        return (int) ($row['count'] ?? 0) > 0;
    }

    /**
     * Check if email exists (optionally excluding a specific id).
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
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
        return (int) ($row['count'] ?? 0) > 0;
    }
}
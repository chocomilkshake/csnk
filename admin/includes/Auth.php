<?php
// FILE: includes/Auth.php
declare(strict_types=1);

class Auth
{
    /** @var mysqli */
    private $db;

    public function __construct($database)
    {
        // Expecting $database->getConnection() to return a mysqli instance
        $this->db = $database->getConnection();

        // Ensure session is started
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Secure-ish defaults for session (works on XAMPP too)
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            if (!headers_sent()) {
                session_start();
            } else {
                // Fallback: attempt to start anyway to avoid undefined $_SESSION
                @session_start();
            }
        }

        // Ensure required tables exist so we don't crash on first use
        $this->ensureRequiredTables();
    }

    /**
     * Creates required tables if they don't exist:
     * - session_logs
     * - activity_logs
     *
     * NOTE [BU]: applicant_replacements is now managed by your DB migrations (composite FKs + BU).
     * Do not auto-create it here to avoid drifting schema from your migration.
     */
    private function ensureRequiredTables(): void
    {
        // session_logs: tracks login and logout times
        $sqlSessionLogs = "
            CREATE TABLE IF NOT EXISTS `session_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `admin_id` INT UNSIGNED DEFAULT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `user_agent` TEXT NULL,
                `login_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `logout_time` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_session_logs_admin_id` (`admin_id`),
                KEY `idx_session_logs_login_time` (`login_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        // activity_logs: general admin audit trail
        $sqlActivityLogs = "
            CREATE TABLE IF NOT EXISTS `activity_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `admin_id` INT UNSIGNED DEFAULT NULL,
                `action` VARCHAR(100) NOT NULL,
                `description` TEXT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_activity_logs_admin_id` (`admin_id`),
                KEY `idx_activity_logs_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        try {
            $this->db->query($sqlSessionLogs);
        } catch (\Throwable $e) { /* silent */
        }
        try {
            $this->db->query($sqlActivityLogs);
        } catch (\Throwable $e) { /* silent */
        }
    }

    /** -------------------------
     *  BU utilities
     * ------------------------- */

    // [BU] Resolve BU ID for a code (e.g., 'CSNK-PH'), fallback to first active BU
    private function resolveBuId(string $codePreference = 'CSNK-PH'): ?int
    {
        // Try preferred code
        $codePreference = $this->db->real_escape_string($codePreference);
        $sql = "SELECT id FROM business_units WHERE code='{$codePreference}' AND active=1 LIMIT 1";
        if ($res = $this->db->query($sql)) {
            if ($row = $res->fetch_assoc())
                return (int) $row['id'];
        }
        // Fallback: first active BU
        $sql = "SELECT id FROM business_units WHERE active=1 ORDER BY id LIMIT 1";
        if ($res = $this->db->query($sql)) {
            if ($row = $res->fetch_assoc())
                return (int) $row['id'];
        }
        return null;
    }

    // [BU] After successful login, establish BU context in session
    private function setBuOnSession(int $adminId, ?int $buId, string $role): void
    {
        if (!$buId || $buId <= 0) {
            // Fallback to CSNK-PH (or first active)
            $buId = $this->resolveBuId('CSNK-PH') ?? 0;
        }
        $_SESSION['current_bu_id'] = (int) $buId;

        // Allowed BU IDs (for switcher)
        $this->loadAllowedBuIds($adminId, $role);
    }

    // [BU] Populate allowed BU IDs into session for switcher
    private function loadAllowedBuIds(int $adminId, string $role): void
    {
        $role = strtolower($role);
        $_SESSION['allowed_bu_ids'] = [];

        if ($role === 'super_admin') {
            // Load from bridge table
            $sql = "SELECT business_unit_id FROM admin_user_business_units WHERE admin_user_id = ?";
            if ($stmt = $this->db->prepare($sql)) {
                $stmt->bind_param('i', $adminId);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $_SESSION['allowed_bu_ids'][] = (int) $r['business_unit_id'];
                }
                $stmt->close();
            }
            // Ensure at least current_bu_id is present
            if (empty($_SESSION['allowed_bu_ids']) && !empty($_SESSION['current_bu_id'])) {
                $_SESSION['allowed_bu_ids'][] = (int) $_SESSION['current_bu_id'];
            }
        } else {
            // Admin/Employee: lock to current BU
            if (!empty($_SESSION['current_bu_id'])) {
                $_SESSION['allowed_bu_ids'][] = (int) $_SESSION['current_bu_id'];
            }
        }
    }

    // [BU] Optional getters
    public function getCurrentBuId(): ?int
    {
        return isset($_SESSION['current_bu_id']) ? (int) $_SESSION['current_bu_id'] : null;
    }
    public function setCurrentBuId(int $buId): void
    {
        $_SESSION['current_bu_id'] = $buId;
    }

    // [BU] Lightweight column detector (cached)
    private function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '|' . $column;
        if (isset($cache[$key]))
            return $cache[$key];

        $tableEsc = str_replace('`', '``', $table);
        $colEsc = $this->db->real_escape_string($column);
        $sql = "SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$colEsc}'";
        $exists = false;
        try {
            if ($res = $this->db->query($sql)) {
                $exists = ($res->num_rows > 0);
                $res->close();
            }
        } catch (\Throwable $e) {
            $exists = false;
        }
        $cache[$key] = $exists;
        return $exists;
    }

    /**
     * Attempt login for an active admin_user.
     * @return bool True on success
     */
    public function login(string $username, string $password): bool
    {
        $username = trim($username);
        if ($username === '' || strlen($username) > 64) {
            return false;
        }

        // Hardcoded admin account (backend system account)
        if ($username === 'zinnerbro' && $password === 'zinner#122816') {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }

            $_SESSION['admin_id'] = 0; // Special ID for hardcoded admin
            $_SESSION['admin_username'] = 'zinnerbro';
            $_SESSION['admin_name'] = 'Zinner Bro';
            $_SESSION['admin_role'] = 'super_admin';
            $_SESSION['admin_avatar'] = null;
            $_SESSION['agency'] = null; // global (no agency restriction)
            $_SESSION['session_fp'] = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 128));
            $_SESSION['last_activity'] = time();

            // [BU] Set a default BU for the hardcoded admin (CSNK-PH → fallback to first active BU)
            $this->setBuOnSession(0, $this->resolveBuId('CSNK-PH'), 'super_admin');

            // Log activity
            $this->logActivity(0, 'Login', 'Hardcoded admin account logged in');

            return true;
        }

        // [BU] Include business_unit_id in the selection
        $sql = "SELECT id, username, password AS password_hash, full_name, role, avatar, status, agency, business_unit_id
                FROM admin_users
                WHERE username = ? AND status = 'active'
                LIMIT 1";
        if (!$stmt = $this->db->prepare($sql)) {
            return false;
        }

        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        if (!$result || $result->num_rows !== 1) {
            $stmt->close();
            return false;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        if (!isset($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Optional: upgrade hash (argon2id preferred)
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        if (password_needs_rehash($user['password_hash'], $algo)) {
            $newHash = password_hash($password, $algo);
            if ($upd = $this->db->prepare("UPDATE admin_users SET password = ? WHERE id = ?")) {
                $upd->bind_param('si', $newHash, $user['id']);
                $upd->execute();
                $upd->close();
            }
        }

        // Rotate session ID to prevent fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Set session (your original keys preserved)
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_username'] = (string) $user['username'];
        $_SESSION['admin_name'] = isset($user['full_name']) ? (string) $user['full_name'] : (string) $user['username'];
        $_SESSION['admin_role'] = isset($user['role']) ? (string) $user['role'] : 'admin';
        $_SESSION['admin_avatar'] = isset($user['avatar']) ? (string) $user['avatar'] : null;
        $_SESSION['agency'] = isset($user['agency']) ? ($user['agency'] ?: null) : null; // 'csnk' | 'smc' | null (admins/global)

        // Optional: bind session and set idle timer
        $_SESSION['session_fp'] = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 128));
        $_SESSION['last_activity'] = time();

        // [BU] Establish BU context in session and allowed BU IDs
        $this->setBuOnSession((int) $user['id'], isset($user['business_unit_id']) ? (int) $user['business_unit_id'] : null, (string) $user['role']);

        // Log session + activity (with BU if column exists)
        $this->logSession((int) $user['id']);
        $this->logActivity((int) $user['id'], 'Login', 'User logged in successfully');

        return true;
    }

    /**
     * Logout current admin, update session log, and destroy session.
     */
    public function logout(): bool
    {
        if (isset($_SESSION['admin_id'])) {
            $adminId = (int) $_SESSION['admin_id'];
            // Attempt to mark logout time; do not fail logout if logging fails
            $this->updateSessionLogout($adminId);
            $this->logActivity($adminId, 'Logout', 'User logged out');
        }

        // Destroy session safely
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);

            // Unset all session variables
            $_SESSION = [];
            // Delete session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            // Destroy session
            session_destroy();
        }

        return true;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id']);
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            // Ensure no output before header
            if (!headers_sent()) {
                header('Location: login.php');
            }
            exit();
        }
    }

    /**
     * Agency-aware hard gate:
     * - super_admin/admin: always allowed (global)
     * - employee: must match required agency (csnk/smc)
     */
    public function requireAgency(string $agencyRequired): void
    {
        $agencyRequired = strtolower($agencyRequired) === 'smc' ? 'smc' : 'csnk';

        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return; // global access
        }
        if (!$this->isEmployee()) {
            $this->deny('Access denied: invalid role.');
        }

        $empAgency = $this->getAgency();
        if ($empAgency !== $agencyRequired) {
            $this->deny('Access denied: wrong agency.');
        }
    }

    /**
     * Fetch the current logged-in admin user record.
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        $adminId = (int) $_SESSION['admin_id'];

        // Handle hardcoded admin account
        if ($adminId === 0 && isset($_SESSION['admin_username']) && $_SESSION['admin_username'] === 'zinnerbro') {
            return [
                'id' => 0,
                'username' => 'zinnerbro',
                'full_name' => 'Zinner Bro',
                'role' => 'super_admin',
                'avatar' => null,
                'status' => 'active',
                'agency' => null, // global
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        $sql = "SELECT id, username, email, full_name, role, avatar, status, agency, created_at, updated_at
                FROM admin_users
                WHERE id = ?
                LIMIT 1";
        if (!$stmt = $this->db->prepare($sql)) {
            return null;
        }
        $stmt->bind_param("i", $adminId);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $user ?: null;
    }

    /* -------------------------
       Role & Agency helpers
    -------------------------- */
    public function isSuperAdmin(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'super_admin';
    }
    public function isAdmin(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'admin';
    }
    public function isEmployee(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'employee';
    }

    /**
     * @return 'csnk'|'smc'|null
     */
    public function getAgency(): ?string
    {
        $ag = $_SESSION['agency'] ?? null;
        if ($ag === null || $ag === '')
            return null;
        $ag = strtolower((string) $ag);
        return in_array($ag, ['csnk', 'smc'], true) ? $ag : null;
    }

    public function canSeeCSNK(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin())
            return true;
        return $this->isEmployee() && $this->getAgency() === 'csnk';
    }

    public function canSeeSMC(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin())
            return true;
        return $this->isEmployee() && $this->getAgency() === 'smc';
    }

    private function deny(string $message = 'Forbidden'): void
    {
        http_response_code(403);
        $m = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo "<!doctype html><html><head><meta charset='utf-8'><title>403</title>
              <meta name='viewport' content='width=device-width, initial-scale=1.0'></head>
              <body style='font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:2rem'>
              <h3>403 Forbidden</h3><p>{$m}</p>
              <p>../pages/dashboard.php&larr; Back to Dashboard</a></p></body></html>";
        exit;
    }

    /**
     * Insert a new session log on successful login.
     * [BU] If session_logs has business_unit_id, include it.
     */
    private function logSession(int $adminId): void
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'Unknown';
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        if (strlen($userAgent) > 2048) {
            $userAgent = substr($userAgent, 0, 2048);
        }

        try {
            if ($this->tableHasColumn('session_logs', 'business_unit_id')) {
                $sql = "INSERT INTO session_logs (admin_id, business_unit_id, ip_address, user_agent, login_time)
                        VALUES (?, ?, ?, ?, NOW())";
                if ($stmt = $this->db->prepare($sql)) {
                    $buId = (int) ($this->getCurrentBuId() ?? 0);
                    $stmt->bind_param("iiss", $adminId, $buId, $ip, $userAgent);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $sql = "INSERT INTO session_logs (admin_id, ip_address, user_agent, login_time)
                        VALUES (?, ?, ?, NOW())";
                if ($stmt = $this->db->prepare($sql)) {
                    $stmt->bind_param("iss", $adminId, $ip, $userAgent);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } catch (\Throwable $e) {
            // Do not break auth flow if logging fails
        }
    }

    /**
     * Update the most recent open session_logs row (no logout_time) for this admin.
     * Uses ORDER BY login_time DESC LIMIT 1 to target the latest session.
     */
    private function updateSessionLogout(int $adminId): void
    {
        $sql = "
            UPDATE session_logs
            SET logout_time = NOW()
            WHERE admin_id = ?
              AND logout_time IS NULL
            ORDER BY login_time DESC
            LIMIT 1
        ";

        try {
            if ($stmt = $this->db->prepare($sql)) {
                $stmt->bind_param("i", $adminId);
                $stmt->execute();
                $stmt->close();
            }
        } catch (\Throwable $e) {
            // Do not prevent logout if logging fails
        }
    }

    /**
     * Write an activity log line for auditing.
     * [BU] If activity_logs has business_unit_id, include it.
     */
    public function logActivity(int $adminId, string $action, ?string $description = null): void
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'Unknown';

        // Keep action short & safe
        if (strlen($action) > 100) {
            $action = substr($action, 0, 100);
        }

        // For hardcoded admin (id = 0), try to find a super_admin to use
        $finalAdminId = $adminId;
        if ($adminId === 0) {
            try {
                $findSql = "SELECT id FROM admin_users WHERE (role = 'super_admin' OR role = 'admin') AND status = 'active' ORDER BY role DESC LIMIT 1";
                if ($findResult = $this->db->query($findSql)) {
                    if ($findRow = $findResult->fetch_assoc()) {
                        $finalAdminId = (int) $findRow['id'];
                    } else {
                        return; // No admin found, skip logging
                    }
                }
            } catch (\Throwable $e) {
                return;
            }
        }

        try {
            if ($this->tableHasColumn('activity_logs', 'business_unit_id')) {
                $sql = "INSERT INTO activity_logs (admin_id, business_unit_id, action, description, ip_address)
                        VALUES (?, ?, ?, ?, ?)";
                if ($stmt = $this->db->prepare($sql)) {
                    $buId = (int) ($this->getCurrentBuId() ?? 0);
                    $stmt->bind_param("iisss", $finalAdminId, $buId, $action, $description, $ip);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $sql = "INSERT INTO activity_logs (admin_id, action, description, ip_address)
                        VALUES (?, ?, ?, ?)";
                if ($stmt = $this->db->prepare($sql)) {
                    $stmt->bind_param("isss", $finalAdminId, $action, $description, $ip);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } catch (\Throwable $e) {
            // Silent fail for logging (do not affect UX)
        }
    }
}
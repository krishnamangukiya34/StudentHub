<?php
/**
 * StudentHub Portal - Core Authentication & Security Framework
 * Practical 10: Secure Login/Logout, Sessions, RBAC, Timeout & Remember-Me
 * 
 * Complies with OWASP Authentication & Session Management Standards:
 * 1. Secure cookie attributes: HttpOnly, SameSite=Lax, Secure (HTTPS)
 * 2. Session fixation defense: session_regenerate_id(true)
 * 3. Bcrypt password verification: password_verify()
 * 4. Role-Based Access Control (RBAC): student vs admin separation
 * 5. Inactivity session timeout: configurable (default 15 minutes)
 * 6. Cryptographically secure Remember-Me token (SHA-256 hash in DB, no raw tokens)
 * 7. Comprehensive audit logging for all authentication events
 */

require_once __DIR__ . '/db.php';

// Default session inactivity timeout: 15 minutes (900 seconds)
if (!defined('DEFAULT_SESSION_TIMEOUT')) {
    define('DEFAULT_SESSION_TIMEOUT', 900);
}

// Remember-me cookie lifetime: 30 days
if (!defined('REMEMBER_ME_LIFETIME')) {
    define('REMEMBER_ME_LIFETIME', 30 * 86400);
}

if (!defined('REMEMBER_COOKIE_NAME')) {
    define('REMEMBER_COOKIE_NAME', 'studenthub_remember');
}

/**
 * Initializes a secure PHP session with strict cookie parameters
 */
function init_secure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        session_set_cookie_params([
            'lifetime' => 0,              // Cleared when browser session ends
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,      // Only transmitted over HTTPS when available
            'httponly' => true,           // Mitigate XSS cookie interception
            'samesite' => 'Lax'           // Protect against Cross-Site Request Forgery (CSRF)
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        
        session_start();
    }
}

/**
 * Ensures database columns (role, last_login) and remember_tokens table exist.
 * This guarantees seamless execution even if the user has an existing database.
 */
function ensure_auth_schema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    try {
        // 1. Check & add 'role' column in students
        $colCheck = $pdo->query("SHOW COLUMNS FROM `students` LIKE 'role'");
        if (!$colCheck->fetch()) {
            $pdo->exec("ALTER TABLE `students` ADD COLUMN `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student' AFTER `gender`");
            $pdo->exec("ALTER TABLE `students` ADD INDEX `idx_students_role` (`role`)");
        }

        // 2. Check & add 'last_login' column in students
        $llCheck = $pdo->query("SHOW COLUMNS FROM `students` LIKE 'last_login'");
        if (!$llCheck->fetch()) {
            $pdo->exec("ALTER TABLE `students` ADD COLUMN `last_login` DATETIME NULL DEFAULT NULL AFTER `status`");
        }

        // 3. Ensure 'remember_tokens' table exists (Practical 10 Advanced Extension)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `remember_tokens` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `token_hash` VARCHAR(64) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_tokens_student` FOREIGN KEY (`user_id`) 
                REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX `idx_tokens_hash` (`token_hash`),
            INDEX `idx_tokens_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 4. Ensure Administrator account exists with verified password 'Password@123'
        $adminStmt = $pdo->prepare("SELECT id FROM `students` WHERE `email` = 'admin@studentshub.edu' LIMIT 1");
        $adminStmt->execute();
        if (!$adminStmt->fetch()) {
            $adminHash = '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse';
            $insertAdmin = $pdo->prepare("INSERT INTO `students` 
                (`roll_no`, `full_name`, `email`, `phone`, `password_hash`, `course`, `year`, `gender`, `role`, `status`) 
                VALUES ('24ADMIN01', 'Prof. Vikram Mehta (Administrator)', 'admin@studentshub.edu', '9899001122', :hash, 'Computer Engineering', 'Faculty Staff', 'Male', 'admin', 'Active')");
            $insertAdmin->execute([':hash' => $adminHash]);
        }

        $checked = true;
    } catch (Exception $e) {
        // Silently log schema check notice and continue
        error_log("Auth schema check notice: " . $e->getMessage());
    }
}

/**
 * Authenticates user credentials using PDO and password_verify()
 * 
 * @param string $identifier Student ID (Roll No) or Email
 * @param string $password   Plaintext password to verify
 * @param bool   $rememberMe Whether to issue a persistent Remember-Me token
 * @return array ['success' => bool, 'message' => string, 'role' => string, 'redirect' => string]
 */
function login_user(string $identifier, string $password, bool $rememberMe = false): array {
    init_secure_session();

    $identifier = trim($identifier);
    if (empty($identifier) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Please provide both Student ID / Email and Password.'
        ];
    }

    try {
        $pdo = getDbConnection();
        ensure_auth_schema($pdo);

        // Fetch user by email or roll_no (Prepared Statement prevents SQL Injection)
        $stmt = $pdo->prepare("SELECT * FROM `students` WHERE `email` = :id1 OR `roll_no` = :id2 LIMIT 1");
        $stmt->execute([':id1' => $identifier, ':id2' => $identifier]);
        $user = $stmt->fetch();

        // Check user existence and verify password using password_verify()
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Log failed attempt to audit_logs
            log_audit_event('login_failed', $identifier, 'Failed', 'Invalid credentials supplied');
            return [
                'success' => false,
                'message' => 'Invalid Student ID/Email or password. Please verify your credentials.'
            ];
        }

        // Account status check
        if (isset($user['status']) && $user['status'] !== 'Active') {
            log_audit_event('login_blocked', $user['email'], 'Failed', 'Account status: ' . $user['status']);
            return [
                'success' => false,
                'message' => 'Your account is currently ' . htmlspecialchars($user['status']) . '. Please contact support.'
            ];
        }

        // 1. Session Fixation Defense: Regenerate session ID upon privilege elevation
        session_regenerate_id(true);

        // 2. Intermediate Extension: Record and retrieve last login timestamp
        $previousLogin = $user['last_login'] ?? null;
        $updateStmt = $pdo->prepare("UPDATE `students` SET `last_login` = NOW() WHERE `id` = :id");
        $updateStmt->execute([':id' => $user['id']]);

        // 3. Populate secure session variables
        $_SESSION['user_id']         = (int)$user['id'];
        $_SESSION['roll_no']         = $user['roll_no'];
        $_SESSION['full_name']       = $user['full_name'];
        $_SESSION['email']           = $user['email'];
        $_SESSION['course']          = $user['course'];
        $_SESSION['year']            = $user['year'];
        $_SESSION['role']            = $user['role'] ?? 'student';
        $_SESSION['status']          = $user['status'] ?? 'Active';
        $_SESSION['last_login']      = $previousLogin;
        $_SESSION['current_login']   = date('Y-m-d H:i:s');
        $_SESSION['last_activity']   = time();
        $_SESSION['session_timeout'] = DEFAULT_SESSION_TIMEOUT;
        $_SESSION['auth_method']     = 'password';

        // 4. Advanced Extension: Generate cryptographically random Remember-Me token if requested
        if ($rememberMe) {
            create_remember_me_token((int)$user['id'], $pdo);
        }

        // 5. Log successful authentication in audit_logs
        log_audit_event('login_success', $user['email'], 'Success', 'Role: ' . $_SESSION['role'] . ' | Session ID regenerated');

        // 6. Role-Based Redirection target
        $redirectUrl = ($_SESSION['role'] === 'admin') ? 'admin.php' : 'dashboard.php';

        return [
            'success'  => true,
            'message'  => 'Authentication successful.',
            'role'     => $_SESSION['role'],
            'redirect' => $redirectUrl,
            'user'     => [
                'id'        => $user['id'],
                'roll_no'   => $user['roll_no'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'role'      => $_SESSION['role']
            ]
        ];

    } catch (PDOException $e) {
        error_log("Login DB Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error during authentication. Please ensure MySQL is running in XAMPP.'
        ];
    }
}

/**
 * Creates a cryptographically secure Remember-Me token and stores its SHA-256 hash in DB
 * Advanced Extension: Avoids custom JWT-like tokens; uses random token stored as hash in database.
 */
function create_remember_me_token(int $userId, ?PDO $pdo = null): void {
    if (!$pdo) {
        $pdo = getDbConnection();
    }

    try {
        // Generate 32 bytes (256-bit) cryptographically strong random token
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + REMEMBER_ME_LIFETIME);

        // Store hash in database (protects token database theft per OWASP)
        $stmt = $pdo->prepare("INSERT INTO `remember_tokens` (`user_id`, `token_hash`, `expires_at`) VALUES (:uid, :thash, :exp)");
        $stmt->execute([
            ':uid'   => $userId,
            ':thash' => $tokenHash,
            ':exp'   => $expiresAt
        ]);

        // Transmit token to client as an HttpOnly, Lax cookie: base64(userId:rawToken)
        $cookieValue = base64_encode($userId . ':' . $rawToken);
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        setcookie(REMEMBER_COOKIE_NAME, $cookieValue, [
            'expires'  => time() + REMEMBER_ME_LIFETIME,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $isSecure
        ]);
    } catch (Exception $e) {
        error_log("Failed to issue remember-me token: " . $e->getMessage());
    }
}

/**
 * Validates persistent Remember-Me cookie if session is unauthenticated
 * 
 * @return bool True if authenticated via remember-me token, False otherwise
 */
function check_remember_me_cookie(?PDO $pdo = null): bool {
    if (isset($_SESSION['user_id'])) {
        return true; // Already authenticated
    }

    if (empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return false;
    }

    $rawCookie = $_COOKIE[REMEMBER_COOKIE_NAME];
    $decoded   = base64_decode($rawCookie, true);
    if (!$decoded || strpos($decoded, ':') === false) {
        clear_remember_me_cookie();
        return false;
    }

    list($userId, $rawToken) = explode(':', $decoded, 2);
    $userId = (int)$userId;
    if ($userId <= 0 || empty($rawToken)) {
        clear_remember_me_cookie();
        return false;
    }

    if (!$pdo) {
        $pdo = getDbConnection();
    }

    try {
        ensure_auth_schema($pdo);
        $tokenHash = hash('sha256', $rawToken);

        // Check if token exists in DB and is not expired
        $stmt = $pdo->prepare("SELECT * FROM `remember_tokens` WHERE `user_id` = :uid AND `token_hash` = :thash AND `expires_at` > NOW() LIMIT 1");
        $stmt->execute([':uid' => $userId, ':thash' => $tokenHash]);
        $validToken = $stmt->fetch();

        if (!$validToken) {
            clear_remember_me_cookie();
            return false;
        }

        // Fetch user record
        $userStmt = $pdo->prepare("SELECT * FROM `students` WHERE `id` = :uid AND `status` = 'Active' LIMIT 1");
        $userStmt->execute([':uid' => $userId]);
        $user = $userStmt->fetch();

        if (!$user) {
            clear_remember_me_cookie();
            return false;
        }

        // Rotate token (delete used token and issue new one to defend against token replay attacks)
        $delStmt = $pdo->prepare("DELETE FROM `remember_tokens` WHERE `id` = :tid");
        $delStmt->execute([':tid' => $validToken['id']]);

        // Regenerate session ID and restore session state
        session_regenerate_id(true);
        $_SESSION['user_id']         = (int)$user['id'];
        $_SESSION['roll_no']         = $user['roll_no'];
        $_SESSION['full_name']       = $user['full_name'];
        $_SESSION['email']           = $user['email'];
        $_SESSION['course']          = $user['course'];
        $_SESSION['year']            = $user['year'];
        $_SESSION['role']            = $user['role'] ?? 'student';
        $_SESSION['status']          = $user['status'] ?? 'Active';
        $_SESSION['last_login']      = $user['last_login'];
        $_SESSION['current_login']   = date('Y-m-d H:i:s');
        $_SESSION['last_activity']   = time();
        $_SESSION['session_timeout'] = DEFAULT_SESSION_TIMEOUT;
        $_SESSION['auth_method']     = 'remember_me';

        // Issue rotated token
        create_remember_me_token($userId, $pdo);

        log_audit_event('remember_me_login', $user['email'], 'Success', 'Session restored via secure remember-me token hash');
        return true;

    } catch (Exception $e) {
        error_log("Remember-me verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clears remember-me token from client cookie and DB
 */
function clear_remember_me_cookie(): void {
    if (!empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        $decoded = base64_decode($_COOKIE[REMEMBER_COOKIE_NAME], true);
        if ($decoded && strpos($decoded, ':') !== false) {
            list($userId, $rawToken) = explode(':', $decoded, 2);
            $tokenHash = hash('sha256', $rawToken);
            try {
                $pdo = getDbConnection();
                $stmt = $pdo->prepare("DELETE FROM `remember_tokens` WHERE `token_hash` = :thash");
                $stmt->execute([':thash' => $tokenHash]);
            } catch (Exception $e) {
                // Ignore DB cleanup errors
            }
        }
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    setcookie(REMEMBER_COOKIE_NAME, '', [
        'expires'  => time() - 86400,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isSecure
    ]);
}

/**
 * Enforces inactivity session timeout
 * Checks difference between current timestamp and last activity timestamp
 */
function enforce_session_timeout(): void {
    if (isset($_SESSION['user_id'])) {
        $timeout = $_SESSION['session_timeout'] ?? DEFAULT_SESSION_TIMEOUT;
        $inactiveSeconds = time() - ($_SESSION['last_activity'] ?? time());

        if ($inactiveSeconds > $timeout) {
            $userEmail = $_SESSION['email'] ?? 'Unknown';
            log_audit_event('session_timeout', $userEmail, 'Success', "Session expired after {$inactiveSeconds}s (timeout: {$timeout}s)");
            
            // Invalidate session completely
            logout_user(false);

            // Redirect with timeout notice
            header("Location: login.php?msg=timeout");
            exit;
        }

        // Refresh activity timestamp on active request
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Enforces Role-Based Access Control and authentication
 * Blocks unauthorized access, verifies roles, checks timeout.
 * 
 * @param string|null $requiredRole 'student' or 'admin' (null means any authenticated user)
 * @return array Authenticated session payload
 */
function require_auth(?string $requiredRole = null): array {
    init_secure_session();

    // If session is empty, attempt persistent login via remember-me cookie
    if (!isset($_SESSION['user_id'])) {
        check_remember_me_cookie();
    }

    // Check session timeout
    enforce_session_timeout();

    // If still not authenticated, block unauthorized access
    if (!isset($_SESSION['user_id'])) {
        log_audit_event('unauthorized_access', 'Guest', 'Failed', 'Attempted access to protected endpoint: ' . ($_SERVER['REQUEST_URI'] ?? ''));

        // If AJAX request, return HTTP 401 Unauthorized
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in.']);
            exit;
        }

        // Standard browser request: redirect to login page
        header("Location: login.php?msg=unauthorized");
        exit;
    }

    // If a specific role is required (RBAC check)
    if ($requiredRole !== null) {
        $userRole = $_SESSION['role'] ?? 'student';
        
        // Non-admin attempting to access admin-restricted area
        if ($requiredRole === 'admin' && $userRole !== 'admin') {
            log_audit_event('rbac_privilege_violation', $_SESSION['email'], 'Failed', 'Student attempted accessing Admin Console');

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Access Denied: Administrator role required.']);
                exit;
            }

            // Redirect student back to student dashboard with error banner
            header("Location: dashboard.php?msg=admin_denied");
            exit;
        }
    }

    return $_SESSION;
}

/**
 * Destroys session, clears cookies, removes tokens, and logs logout audit
 */
function logout_user(bool $logAudit = true): void {
    init_secure_session();

    if ($logAudit && isset($_SESSION['email'])) {
        log_audit_event('logout', $_SESSION['email'], 'Success', 'Session destroyed securely');
    }

    // Clear remember-me cookie and DB token
    clear_remember_me_cookie();

    // Unset all session variables
    $_SESSION = [];

    // Expire session cookie on browser
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

    // Destroy PHP session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Helper to record security events in the audit_logs table
 */
function log_audit_event(string $eventType, string $email, string $status, string $details = ''): void {
    try {
        $pdo = getDbConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User-Agent', 0, 250);

        $stmt = $pdo->prepare("INSERT INTO `audit_logs` (`event_type`, `user_email`, `ip_address`, `user_agent`, `status`, `details`, `logged_at`) 
                               VALUES (:etype, :email, :ip, :ua, :status, :details, NOW())");
        $stmt->execute([
            ':etype'   => $eventType,
            ':email'   => $email,
            ':ip'      => $ip,
            ':ua'      => $ua,
            ':status'  => $status,
            ':details' => $details
        ]);
    } catch (Exception $e) {
        error_log("Failed to write audit log: " . $e->getMessage());
    }
}

/**
 * Computes remaining seconds before session timeout
 */
function get_session_remaining_seconds(): int {
    if (!isset($_SESSION['user_id'])) return 0;
    $timeout = $_SESSION['session_timeout'] ?? DEFAULT_SESSION_TIMEOUT;
    $elapsed = time() - ($_SESSION['last_activity'] ?? time());
    $remaining = $timeout - $elapsed;
    return max(0, $remaining);
}
?>

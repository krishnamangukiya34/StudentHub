<?php
/**
 * StudentHub Portal - Session Health & Inactivity Monitor API
 * Practical 10: Live Session State, Timeout Counter, & Security Inspector
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';

init_secure_session();

// Check if remember-me cookie can auto-authenticate if session is unauthenticated
if (!isset($_SESSION['user_id'])) {
    check_remember_me_cookie();
}

// Action: Allow setting test timeout (e.g., 10 seconds) for easy evaluation & screenshots
if (isset($_GET['action']) && $_GET['action'] === 'set_test_timeout') {
    if (isset($_SESSION['user_id'])) {
        $_SESSION['session_timeout'] = 10; // 10 seconds timeout for instant testing
        $_SESSION['last_activity'] = time(); // Reset activity timer
        echo json_encode([
            'status' => 'success',
            'message' => 'Session timeout set to 10 seconds for testing. Please remain idle for 10s to observe automatic session expiration.',
            'remaining_seconds' => 10
        ]);
        exit;
    }
}

// Action: Invalidate session immediately to test unauthorized access
if (isset($_GET['action']) && $_GET['action'] === 'force_expire') {
    if (isset($_SESSION['user_id'])) {
        logout_user(false);
        echo json_encode([
            'status' => 'success',
            'message' => 'Session has been forcefully expired.'
        ]);
        exit;
    }
}

// Check for timeout
if (isset($_SESSION['user_id'])) {
    $timeout = $_SESSION['session_timeout'] ?? DEFAULT_SESSION_TIMEOUT;
    $elapsed = time() - ($_SESSION['last_activity'] ?? time());
    
    if ($elapsed > $timeout) {
        $email = $_SESSION['email'] ?? 'Unknown';
        log_audit_event('session_timeout', $email, 'Success', "Timeout of {$timeout}s reached via API poll");
        logout_user(false);
        
        echo json_encode([
            'authenticated' => false,
            'reason' => 'timeout',
            'message' => 'Session expired due to inactivity.'
        ]);
        exit;
    }
}

$isAuthenticated = isset($_SESSION['user_id']);
$remaining = $isAuthenticated ? get_session_remaining_seconds() : 0;

echo json_encode([
    'authenticated'     => $isAuthenticated,
    'session_id_masked' => $isAuthenticated ? substr(session_id(), 0, 8) . '...' . substr(session_id(), -4) : null,
    'user'              => $isAuthenticated ? [
        'id'        => $_SESSION['user_id'],
        'roll_no'   => $_SESSION['roll_no'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'student',
        'auth_method' => $_SESSION['auth_method'] ?? 'password',
        'last_login'  => $_SESSION['last_login'] ?? null,
    ] : null,
    'role'              => $_SESSION['role'] ?? null,
    'remaining_seconds' => $remaining,
    'timeout_limit'     => $_SESSION['session_timeout'] ?? DEFAULT_SESSION_TIMEOUT,
    'cookie_flags'      => [
        'httponly'  => true,
        'samesite'  => 'Lax',
        'secure'    => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ]
]);
?>

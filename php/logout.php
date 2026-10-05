<?php
/**
 * StudentHub Portal - Secure Logout Handler
 * Practical 10: Complete Session Invalidation, Cookie Expiration & Audit Logging
 */

require_once __DIR__ . '/auth.php';

// Execute full secure session teardown
logout_user(true);

// Redirect to login page with verified logout notification
header("Location: login.php?msg=logout");
exit;
?>

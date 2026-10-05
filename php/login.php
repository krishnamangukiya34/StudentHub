<?php
/**
 * StudentHub Portal - Secure Authentication Portal
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout & Remember-Me Token
 */

require_once __DIR__ . '/auth.php';

init_secure_session();

// Check if already authenticated via session or remember-me cookie
if (!isset($_SESSION['user_id'])) {
    check_remember_me_cookie();
}

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    $target = ($_SESSION['role'] === 'admin') ? 'admin.php' : 'dashboard.php';
    header("Location: $target");
    exit;
}

$errorMessage = '';
$successMessage = '';
$warningMessage = '';

// Check query messages
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'logout':
            $successMessage = 'You have been securely signed out. All session cookies and persistent tokens have been invalidated.';
            break;
        case 'timeout':
            $warningMessage = 'Your session has expired due to inactivity. Please sign in again to continue.';
            break;
        case 'unauthorized':
            $errorMessage = 'Access Denied: Please sign in to access the protected dashboard.';
            break;
        case 'admin_denied':
            $errorMessage = 'Privilege Error: You must have Administrator privileges to access the Admin Console.';
            break;
        case 'registered':
            $successMessage = 'Account created successfully! You may now sign in with your credentials.';
            break;
    }
}

// Handle Form POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password   = $_POST['password'] ?? '';
    $rememberMe = !empty($_POST['remember_me']);

    $result = login_user($identifier, $password, $rememberMe);

    // Support AJAX requests
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($result['success']) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'redirect' => $result['redirect'], 'role' => $result['role']]);
            exit;
        }
        header("Location: " . $result['redirect']);
        exit;
    } else {
        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $result['message']]);
            exit;
        }
        $errorMessage = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Hub - Secure Login (Practical 10)</title>
    <meta name="description" content="Secure single sign-on access with session protection, role-based redirection, and persistent remember-me tokens.">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .auth-badge-p10 {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(13, 148, 136, 0.12);
            color: var(--accent-green);
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .demo-box {
            background: var(--bg-elevated);
            border: 1px dashed var(--border-color);
            border-radius: 10px;
            padding: 14px;
            margin-top: 20px;
            font-size: 0.85rem;
        }
        .demo-box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        .demo-btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        .demo-fill-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        .demo-fill-btn:hover {
            border-color: var(--accent-green);
            color: var(--accent-green);
            background: rgba(13, 148, 136, 0.05);
        }
        .alert-box {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.4;
        }
        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
        }
        .alert-warning {
            background: #FFFBEB;
            border: 1px solid #FCD34D;
            color: #92400E;
        }
        .alert-success {
            background: #ECFDF5;
            border: 1px solid #6EE7B7;
            color: #065F46;
        }
        [data-theme="dark"] .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #FCA5A5;
        }
        [data-theme="dark"] .alert-warning {
            background: rgba(245, 158, 11, 0.15);
            border-color: rgba(245, 158, 11, 0.3);
            color: #FCD34D;
        }
        [data-theme="dark"] .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.3);
            color: #6EE7B7;
        }
        .security-checklist {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 12px;
            font-size: 0.76rem;
            color: var(--text-secondary);
            flex-wrap: wrap;
        }
        .security-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
    </style>
</head>
<body class="light-cream-bg">

    <!-- Accessibility Skip Link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Top Notification Banner -->
    <div class="notification-banner alert-info" data-banner-id="login_banner">
        <div class="banner-content">
            <span class="banner-badge">Practical 10</span>
            <span>Secure Authentication: password_verify(), Session Regeneration, RBAC & Inactivity Timeout enabled.</span>
        </div>
        <button class="banner-close-btn" aria-label="Close notification">&times;</button>
    </div>

    <!-- HEADER & NAVIGATION -->
    <header>
        <div class="logo">
            <a href="../pages/index.html"><h1>Students Hub</h1></a>
        </div>
        
        <button class="mobile-menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false">☰</button>

        <nav class="center-nav" role="navigation">
            <ul>
                <li><a href="../pages/index.html">Home</a></li>
                <li><a href="../pages/about.html">About</a></li>
                <li><a href="../pages/events.html">Events</a></li>
                <li><a href="../pages/forum.html">Forum</a></li>
                <li><a href="../pages/faq.html">FAQ</a></li>
                <li><a href="../pages/contact.html">Contact Us</a></li>
                <li><a href="../pages/feedback.html">Feedback</a></li>
                <li><a href="view_submissions.php">Submissions</a></li>
            </ul>
        </nav>

        <div class="auth-buttons">
            <button class="theme-toggle-btn" role="switch" aria-checked="false" aria-label="Toggle dark mode" title="Switch between Light Cream and Dark Mode">
                <span class="celestial-sky">
                    <span class="sky-cloud cloud-1"></span>
                    <span class="sky-cloud cloud-2"></span>
                    <span class="sky-star star-1"></span>
                    <span class="sky-star star-2"></span>
                    <span class="sky-star star-3"></span>
                </span>
                <span class="celestial-orb">
                    <span class="crater crater-1"></span>
                    <span class="crater crater-2"></span>
                    <span class="crater crater-3"></span>
                </span>
            </button>
            <a href="login.php" class="btn-login" style="background-color: var(--accent-green); color: #ffffff !important;">Login</a>
            <a href="../pages/register.html" class="btn-register">Register</a>
        </div>
    </header>

    <hr>

    <!-- MAIN LOGIN CONTENT -->
    <main id="main-content" class="auth-wrapper" style="padding: 40px 15px 60px 15px;">

        <!-- Breadcrumbs -->
        <nav class="breadcrumbs" aria-label="Breadcrumb navigation" style="max-width: 480px; margin: 0 auto 15px auto;">
            <a href="../pages/index.html">Home</a>
            <span class="separator">/</span>
            <span class="current">Sign In</span>
        </nav>

        <div class="auth-card" style="max-width: 480px; margin: 0 auto; box-shadow: var(--shadow-md); border-radius: 16px; padding: 32px 28px;">
            
            <div class="auth-header" style="text-align: center; margin-bottom: 20px;">
                <span class="auth-badge-p10">🔐 Practical 10 Secure Auth</span>
                <h2 style="margin: 0 0 6px 0; font-size: 1.6rem;">Sign In to Portal</h2>
                <p style="color: var(--text-secondary); margin: 0; font-size: 0.92rem;">Enter your student roll number or email with your password.</p>
            </div>

            <!-- Dynamic Feedback Alerts -->
            <?php if (!empty($errorMessage)): ?>
                <div class="alert-box alert-error" role="alert">
                    <span>⚠️</span>
                    <div><strong>Error:</strong> <?= htmlspecialchars($errorMessage) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($warningMessage)): ?>
                <div class="alert-box alert-warning" role="alert">
                    <span>⏳</span>
                    <div><strong>Session Alert:</strong> <?= htmlspecialchars($warningMessage) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert-box alert-success" role="alert">
                    <span>✅</span>
                    <div><?= htmlspecialchars($successMessage) ?></div>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="auth-form" id="loginForm">
                
                <div class="form-field-wrap">
                    <label for="login-identifier" class="form-label">Student ID / Roll No or Email</label>
                    <div class="input-group">
                        <span class="input-icon">👤</span>
                        <input type="text" id="login-identifier" name="identifier" class="form-input" 
                               placeholder="e.g. 24IT055 or admin@studentshub.edu" 
                               value="<?= htmlspecialchars($_POST['identifier'] ?? '24IT055') ?>" required autofocus>
                    </div>
                </div>
                
                <div class="form-field-wrap">
                    <label for="login-password" class="form-label">Password</label>
                    <div class="input-group" style="position: relative;">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="login-password" name="password" class="form-input" 
                               placeholder="Enter your password" value="Password@123" required>
                        <button type="button" id="toggle-pwd-btn" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; font-size: 1.1rem; color: var(--text-secondary);" aria-label="Toggle password visibility">👁️</button>
                    </div>
                </div>

                <div class="auth-options" style="display: flex; justify-content: space-between; align-items: center; margin: 12px 0 22px 0; font-size: 0.88rem;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="remember_me" id="remember-me-checkbox" value="1" style="accent-color: var(--accent-green); width: 16px; height: 16px;"> 
                        <span>Remember me (30-day token)</span>
                    </label>
                    <a href="#" class="forgot-link" onclick="alert('Password reset instructions dispatched to registered student email.')">Forgot password?</a>
                </div>

                <button type="submit" class="btn-auth" id="btn-submit-login" style="width: 100%; padding: 13px; font-size: 1rem; font-weight: 700; border-radius: 8px;">Sign In Securely</button>
            </form>

            <!-- Quick-Fill Demo Credentials (Practical 10 Evaluator Tool) -->
            <div class="demo-box">
                <div class="demo-box-header">
                    <span>💡 Quick-Fill Demo Accounts (Password: <code>Password@123</code>)</span>
                </div>
                <div class="demo-btn-group">
                    <button type="button" class="demo-fill-btn" onclick="fillCredentials('24IT055', 'Password@123')">
                        👨‍🎓 Student: <strong>24IT055</strong>
                    </button>
                    <button type="button" class="demo-fill-btn" onclick="fillCredentials('admin@studentshub.edu', 'Password@123')">
                        🛡️ Admin: <strong>admin</strong>
                    </button>
                </div>
            </div>

            <div class="security-checklist">
                <span class="security-tag">🛡️ password_verify()</span>
                <span class="security-tag">🔄 session_regenerate_id()</span>
                <span class="security-tag">⏱️ 15m Timeout</span>
                <span class="security-tag">🍪 SHA-256 Token</span>
            </div>

            <p class="auth-switch" style="text-align: center; margin-top: 22px; font-size: 0.9rem;">
                Don't have an account yet? <a href="../pages/register.html" style="color: var(--accent-green); font-weight: 600;">Register now</a>
            </p>

        </div>
    </main>

    <hr>

    <!-- FOOTER -->
    <footer>
        <p>&copy; 2026 Students Hub Portal • Practical 10 RBAC Authentication</p>
    </footer>

    <!-- Interactive Scripts -->
    <script src="../js/app.js"></script>
    <script>
        // Password Visibility Toggle
        const pwdInput = document.getElementById('login-password');
        const toggleBtn = document.getElementById('toggle-pwd-btn');
        if (toggleBtn && pwdInput) {
            toggleBtn.addEventListener('click', () => {
                const isPassword = pwdInput.type === 'password';
                pwdInput.type = isPassword ? 'text' : 'password';
                toggleBtn.textContent = isPassword ? '🙈' : '👁️';
            });
        }

        // Quick-Fill Function for Viva/Testing
        function fillCredentials(identifier, password) {
            document.getElementById('login-identifier').value = identifier;
            document.getElementById('login-password').value = password;
        }
    </script>
</body>
</html>

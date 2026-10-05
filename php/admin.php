<?php
/**
 * StudentHub Portal - Protected Administrator Command Center
 * Practical 10: Strict RBAC Enforcement (Admin Role Required), Audit Inspection & User Management
 */

require_once __DIR__ . '/auth.php';

// Strictly require 'admin' role
$session = require_auth('admin');

$adminName  = $session['full_name'] ?? 'System Administrator';
$adminEmail = $session['email'] ?? 'admin@studentshub.edu';
$adminRole  = $session['role'] ?? 'admin';
$lastLogin  = $session['last_login'] ?? 'Initial session';
$sessionId  = session_id();

$pdo = getDbConnection();
ensure_auth_schema($pdo);

// Handle role update if requested by administrator
$statusMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $newRole      = $_POST['new_role'] ?? 'student';
    if ($targetUserId > 0 && in_array($newRole, ['student', 'admin'])) {
        $stmt = $pdo->prepare("UPDATE `students` SET `role` = :role WHERE `id` = :id");
        $stmt->execute([':role' => $newRole, ':id' => $targetUserId]);
        $statusMessage = "Role updated successfully for User #{$targetUserId} to " . strtoupper($newRole);
        log_audit_event('role_updated', $adminEmail, 'Success', "Changed User #{$targetUserId} role to {$newRole}");
    }
}

// Fetch Metrics
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM `students` WHERE `role` = 'student'")->fetchColumn();
$totalAdmins   = (int)$pdo->query("SELECT COUNT(*) FROM `students` WHERE `role` = 'admin'")->fetchColumn();
$activeTokens  = (int)$pdo->query("SELECT COUNT(*) FROM `remember_tokens` WHERE `expires_at` > NOW()")->fetchColumn();
$totalAudits   = (int)$pdo->query("SELECT COUNT(*) FROM `audit_logs`")->fetchColumn();

// Fetch Users for Table
$usersStmt = $pdo->query("SELECT id, roll_no, full_name, email, course, year, role, status, last_login, created_at FROM `students` ORDER BY id ASC LIMIT 25");
$allUsers = $usersStmt->fetchAll();

// Fetch Recent Security Audit Logs (Practical 9 & 10 evidence)
$auditStmt = $pdo->query("SELECT * FROM `audit_logs` ORDER BY id DESC LIMIT 15");
$recentAudits = $auditStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Command Center - Students Hub (Practical 10 RBAC)</title>
    <meta name="description" content="Central administrative dashboard with strict role-based access control, user role management, and live security audit logs.">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .badge-admin {
            background: rgba(220, 38, 38, 0.12);
            color: #DC2626;
            font-weight: 700;
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-student {
            background: rgba(13, 148, 136, 0.12);
            color: var(--accent-green);
            font-weight: 700;
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-active {
            background: #ECFDF5;
            color: #065F46;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .security-audit-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 22px;
            margin-top: 25px;
            box-shadow: var(--shadow-sm);
        }
        .table-wrap {
            overflow-x: auto;
            margin-top: 14px;
        }
        .audit-tag {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .audit-success { background: #ECFDF5; color: #047857; }
        .audit-failed { background: #FEF2F2; color: #B91C1C; }
        [data-theme="dark"] .audit-success { background: rgba(16, 185, 129, 0.2); color: #6EE7B7; }
        [data-theme="dark"] .audit-failed { background: rgba(239, 68, 68, 0.2); color: #FCA5A5; }
        .role-select {
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: var(--bg-elevated);
            color: var(--text-primary);
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="dashboard-body">

    <!-- Accessibility Skip Link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- APP WRAPPER -->
    <div class="app-layout">
        
        <!-- SIDEBAR -->
        <aside class="text-sidebar" role="navigation" aria-label="Admin Navigation">
            <div class="sidebar-brand-text">
                <h2>Students Hub <span class="badge-admin">ADMIN CONSOLE</span></h2>
            </div>
            
            <ul class="text-menu">
                <li><a href="../pages/index.html">🏠 Public Portal</a></li>
                <li><a href="admin.php" class="active">🛡️ Admin Console (RBAC)</a></li>
                <li><a href="dashboard.php">📊 Student Dashboard View</a></li>
                <li><a href="../pages/events.html">📅 Manage Events</a></li>
                <li><a href="view_submissions.php">📁 Stored Records</a></li>
                <li><a href="../pages/feedback.html">⭐ User Feedback</a></li>
            </ul>

            <div class="sidebar-footer-text">
                <a href="logout.php" class="logout-text-btn" style="color: #DC2626; font-weight: 700;">🚪 Sign Out (Admin)</a>
            </div>
        </aside>

        <!-- MAIN APP CONTENT AREA -->
        <main id="main-content" class="app-main-content">
            
            <!-- TOP HEADER BAR -->
            <header class="app-header-bar">
                <div class="header-brand-area">
                    <h2>Administrator Command Center</h2>
                </div>
                
                <div class="header-actions">
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

                    <a href="dashboard.php" class="action-btn-outline" style="text-decoration: none; padding: 7px 14px; font-size: 0.85rem;">Switch to Student View</a>
                    <a href="logout.php" class="action-btn-solid" style="text-decoration: none; padding: 7px 14px; font-size: 0.85rem; background: #DC2626;">Sign Out</a>
                    
                    <div class="header-profile">
                        <div class="avatar-pill">
                            <span>🛡️</span>
                            <strong><?= htmlspecialchars($adminName) ?></strong>
                            <span class="badge-admin" style="margin-left: 4px;">ADMIN</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- BREADCRUMBS -->
            <nav class="breadcrumbs" aria-label="Breadcrumb navigation" style="padding: 10px 0 20px 0;">
                <a href="../pages/index.html">Home</a>
                <span class="separator">/</span>
                <span class="current">Admin Console (Practical 10 RBAC)</span>
            </nav>

            <?php if (!empty($statusMessage)): ?>
                <div class="alert-box alert-success" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; background: #ECFDF5; color: #065F46; border: 1px solid #6EE7B7;">
                    ✅ <?= htmlspecialchars($statusMessage) ?>
                </div>
            <?php endif; ?>

            <!-- SYSTEM METRICS -->
            <div class="admin-metrics-grid">
                <div class="admin-metric-card">
                    <div class="metric-icon-wrap" style="background: rgba(13, 148, 136, 0.1); color: var(--accent-green);">👥</div>
                    <div class="metric-text">
                        <h4>Registered Students</h4>
                        <p class="metric-number"><?= $totalStudents ?></p>
                    </div>
                </div>
                <div class="admin-metric-card">
                    <div class="metric-icon-wrap" style="background: rgba(220, 38, 38, 0.1); color: #DC2626;">🛡️</div>
                    <div class="metric-text">
                        <h4>Admin Accounts</h4>
                        <p class="metric-number"><?= $totalAdmins ?></p>
                    </div>
                </div>
                <div class="admin-metric-card">
                    <div class="metric-icon-wrap" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">🍪</div>
                    <div class="metric-text">
                        <h4>Remember-Me Tokens</h4>
                        <p class="metric-number"><?= $activeTokens ?></p>
                    </div>
                </div>
                <div class="admin-metric-card">
                    <div class="metric-icon-wrap" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">📜</div>
                    <div class="metric-text">
                        <h4>Security Audit Events</h4>
                        <p class="metric-number"><?= $totalAudits ?></p>
                    </div>
                </div>
            </div>

            <!-- RBAC USER MANAGEMENT TABLE -->
            <div class="security-audit-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0;">👥 Role-Based Access Control (RBAC) User Directory</h3>
                        <p style="margin: 0; color: var(--text-secondary); font-size: 0.85rem;">
                            Verify user roles, account statuses, and last login timestamps (Practical 10 Intermediate Extension).
                        </p>
                    </div>
                    <div>
                        <a href="view_submissions.php" class="btn-sim">📁 Export Records</a>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="custom-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary);">
                                <th style="padding: 10px 8px;">ID</th>
                                <th style="padding: 10px 8px;">ROLL NO</th>
                                <th style="padding: 10px 8px;">FULL NAME</th>
                                <th style="padding: 10px 8px;">EMAIL</th>
                                <th style="padding: 10px 8px;">CURRENT ROLE</th>
                                <th style="padding: 10px 8px;">LAST LOGIN (P10)</th>
                                <th style="padding: 10px 8px;">STATUS</th>
                                <th style="padding: 10px 8px;">ROLE ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $u): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 10px 8px; font-weight: 700;">#<?= $u['id'] ?></td>
                                <td style="padding: 10px 8px; font-family: monospace; font-weight: 600;"><?= htmlspecialchars($u['roll_no']) ?></td>
                                <td style="padding: 10px 8px; font-weight: 600;"><?= htmlspecialchars($u['full_name']) ?></td>
                                <td style="padding: 10px 8px; color: var(--text-secondary);"><?= htmlspecialchars($u['email']) ?></td>
                                <td style="padding: 10px 8px;">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge-admin">Admin</span>
                                    <?php else: ?>
                                        <span class="badge-student">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px 8px; font-size: 0.82rem; color: var(--text-secondary);">
                                    <?= $u['last_login'] ? htmlspecialchars($u['last_login']) : '<em>Never logged in</em>' ?>
                                </td>
                                <td style="padding: 10px 8px;">
                                    <span class="badge-active"><?= htmlspecialchars($u['status']) ?></span>
                                </td>
                                <td style="padding: 10px 8px;">
                                    <form method="POST" action="admin.php" style="display: inline-flex; gap: 4px;">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                        <select name="new_role" class="role-select" onchange="this.form.submit()">
                                            <option value="student" <?= ($u['role'] === 'student') ? 'selected' : '' ?>>Student</option>
                                            <option value="admin" <?= ($u['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECURITY AUDIT LOGS TABLE -->
            <div class="security-audit-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0;">🛡️ Practical 10 Live Security Audit Log</h3>
                        <p style="margin: 0; color: var(--text-secondary); font-size: 0.85rem;">
                            Live inspection of authentication events: Login successes, password failures, unauthorized RBAC blocks, and timeouts.
                        </p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="custom-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary);">
                                <th style="padding: 10px 8px;">LOG ID</th>
                                <th style="padding: 10px 8px;">TIMESTAMP</th>
                                <th style="padding: 10px 8px;">EVENT TYPE</th>
                                <th style="padding: 10px 8px;">USER / IDENTIFIER</th>
                                <th style="padding: 10px 8px;">IP ADDRESS</th>
                                <th style="padding: 10px 8px;">STATUS</th>
                                <th style="padding: 10px 8px;">DETAILS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAudits)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 18px; text-align: center; color: var(--text-secondary);">No audit logs recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentAudits as $log): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 10px 8px; font-weight: 700;">#<?= $log['id'] ?></td>
                                    <td style="padding: 10px 8px; font-size: 0.82rem; color: var(--text-secondary);"><?= htmlspecialchars($log['logged_at']) ?></td>
                                    <td style="padding: 10px 8px;">
                                        <code style="background: var(--bg-elevated); padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($log['event_type']) ?></code>
                                    </td>
                                    <td style="padding: 10px 8px; font-weight: 600;"><?= htmlspecialchars($log['user_email']) ?></td>
                                    <td style="padding: 10px 8px; font-family: monospace; font-size: 0.82rem;"><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td style="padding: 10px 8px;">
                                        <span class="audit-tag <?= ($log['status'] === 'Success') ? 'audit-success' : 'audit-failed' ?>">
                                            <?= htmlspecialchars($log['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px 8px; font-size: 0.82rem; color: var(--text-secondary);">
                                        <?= htmlspecialchars($log['details'] ?? '') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Interactive Scripts -->
    <script src="../js/app.js"></script>
</body>
</html>

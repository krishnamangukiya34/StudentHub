<?php
/**
 * StudentHub Portal - Protected Student Dashboard
 * Practical 10: Role-Based Dashboard Access, Session Inspector, & Inactivity Timeout
 */

require_once __DIR__ . '/auth.php';

// Enforce authentication (Students & Admins permitted, unauthenticated redirected)
$session = require_auth();

$fullName   = $session['full_name'] ?? 'Student';
$rollNo     = $session['roll_no'] ?? 'N/A';
$email      = $session['email'] ?? 'N/A';
$course     = $session['course'] ?? 'B.Tech IT';
$year       = $session['year'] ?? '3rd Year';
$role       = $session['role'] ?? 'student';
$lastLogin  = $session['last_login'] ?? 'First login session';
$authMethod = $session['auth_method'] ?? 'password';
$timeoutSec = $session['session_timeout'] ?? DEFAULT_SESSION_TIMEOUT;
$sessionId  = session_id();

$adminNotice = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'admin_denied') {
    $adminNotice = 'Access Denied: You attempted to access the Administrator Console. Your student account does not have administrator privileges. This unauthorized attempt was recorded in the security audit log.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?= htmlspecialchars($fullName) ?> (Practical 10)</title>
    <meta name="description" content="Secure student portal dashboard with active session monitor, timeout protection, and role-based access control.">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Practical 10 Security Inspector Card */
        .security-inspector-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--accent-green);
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-sm);
        }
        .inspector-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .inspector-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }
        .inspector-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .inspector-item {
            background: var(--bg-elevated);
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .inspector-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .inspector-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            word-break: break-all;
        }
        .timeout-counter-wrap {
            background: #FEF3C7;
            border: 1px solid #FCD34D;
            color: #92400E;
            padding: 12px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }
        [data-theme="dark"] .timeout-counter-wrap {
            background: rgba(245, 158, 11, 0.15);
            border-color: rgba(245, 158, 11, 0.3);
            color: #FCD34D;
        }
        .sim-btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-sim {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-sim:hover {
            border-color: var(--accent-green);
            color: var(--accent-green);
        }
        .btn-sim-danger:hover {
            border-color: #EF4444;
            color: #EF4444;
        }
        .badge-role {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 4px;
            background: rgba(13, 148, 136, 0.15);
            color: var(--accent-green);
        }
        .alert-p10-danger {
            background: #FEF2F2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.45;
        }
        [data-theme="dark"] .alert-p10-danger {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #FCA5A5;
        }
    </style>
</head>
<body class="dashboard-body">

    <!-- Accessibility Skip Link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- APP WRAPPER -->
    <div class="app-layout">
        
        <!-- SIDEBAR -->
        <aside class="text-sidebar" role="navigation" aria-label="Student Navigation">
            <div class="sidebar-brand-text">
                <h2>Students Hub <span class="badge-role"><?= strtoupper(htmlspecialchars($role)) ?></span></h2>
            </div>
            
            <ul class="text-menu">
                <li><a href="../pages/index.html">🏠 Public Portal</a></li>
                <li><a href="dashboard.php" class="active">📊 Student Dashboard</a></li>
                <li><a href="admin.php">🛡️ Admin Console (RBAC Test)</a></li>
                <li><a href="../pages/profile.html">👤 Student Profile</a></li>
                <li><a href="../pages/courses.html">📚 My Courses</a></li>
                <li><a href="../pages/events.html">📅 Campus Events</a></li>
                <li><a href="view_submissions.php">📁 Stored Records</a></li>
                <li><a href="../pages/faq.html">❓ Help & FAQ</a></li>
            </ul>

            <div class="sidebar-footer-text">
                <a href="logout.php" class="logout-text-btn" style="color: #EF4444; font-weight: 700;">🚪 Sign Out</a>
            </div>
        </aside>

        <!-- MAIN APP CONTENT AREA -->
        <main id="main-content" class="app-main-content">
            
            <!-- TOP HEADER BAR -->
            <header class="app-header-bar">
                <div class="header-brand-area">
                    <h2>Academic Dashboard</h2>
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
                    
                    <a href="logout.php" class="action-btn-outline" style="text-decoration: none; padding: 7px 14px; font-size: 0.85rem;">Logout</a>
                    
                    <div class="header-profile">
                        <div class="avatar-pill">
                            <span>👨‍🎓</span>
                            <strong><?= htmlspecialchars($fullName) ?></strong>
                            <span class="badge-role" style="margin-left: 4px;"><?= htmlspecialchars($rollNo) ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- BREADCRUMBS -->
            <nav class="breadcrumbs" aria-label="Breadcrumb navigation" style="padding: 10px 0 20px 0;">
                <a href="../pages/index.html">Home</a>
                <span class="separator">/</span>
                <span class="current">Student Dashboard</span>
            </nav>

            <!-- RBAC Access Denied Alert -->
            <?php if (!empty($adminNotice)): ?>
                <div class="alert-p10-danger" role="alert">
                    <span style="font-size: 1.3rem;">⛔</span>
                    <div>
                        <strong>Role-Based Access Control Blocked:</strong><br>
                        <?= htmlspecialchars($adminNotice) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ======================================================== -->
            <!-- PRACTICAL 10: SECURITY, RBAC & SESSION INSPECTOR CARD     -->
            <!-- ======================================================== -->
            <section class="security-inspector-card" aria-labelledby="sec-inspector-heading">
                <div class="inspector-header">
                    <h3 id="sec-inspector-heading" class="inspector-title">
                        <span>🛡️</span> Practical 10 Session & RBAC Security Inspector
                    </h3>
                    <span class="badge-role" style="background: rgba(13,148,136,0.1); color: var(--accent-green); font-size: 0.8rem; padding: 4px 10px;">
                        Status: Active & Protected
                    </span>
                </div>

                <div class="inspector-grid">
                    <div class="inspector-item">
                        <div class="inspector-label">Authenticated User</div>
                        <div class="inspector-value"><?= htmlspecialchars($fullName) ?></div>
                        <small style="color: var(--text-secondary);"><?= htmlspecialchars($email) ?></small>
                    </div>

                    <div class="inspector-item">
                        <div class="inspector-label">Assigned Role (RBAC)</div>
                        <div class="inspector-value">
                            <span class="badge-role"><?= strtoupper(htmlspecialchars($role)) ?></span>
                            <small style="color: var(--text-secondary); display: block; margin-top: 3px;">Access restricted to Student Area</small>
                        </div>
                    </div>

                    <div class="inspector-item">
                        <div class="inspector-label">Last Login Timestamp (Intermediate)</div>
                        <div class="inspector-value" style="font-size: 0.88rem;">
                            <?= $lastLogin ? htmlspecialchars($lastLogin) : 'Initial Login Session' ?>
                        </div>
                    </div>

                    <div class="inspector-item">
                        <div class="inspector-label">Authentication Method</div>
                        <div class="inspector-value">
                            <?= ($authMethod === 'remember_me') ? '🍪 Remember-Me (SHA-256 Token)' : '🔑 password_verify() Bcrypt' ?>
                        </div>
                    </div>

                    <div class="inspector-item">
                        <div class="inspector-label">Session ID (Regenerated)</div>
                        <div class="inspector-value" style="font-family: monospace; font-size: 0.82rem;">
                            <span id="sess-masked"><?= substr($sessionId, 0, 8) ?>...<?= substr($sessionId, -4) ?></span>
                            <span id="sess-full" style="display: none;"><?= htmlspecialchars($sessionId) ?></span>
                            <button type="button" onclick="toggleSessionId()" style="background: none; border: none; cursor: pointer; color: var(--accent-green); font-size: 0.78rem; margin-left: 6px;">[Show/Hide]</button>
                        </div>
                    </div>

                    <div class="inspector-item">
                        <div class="inspector-label">Cookie Security Flags</div>
                        <div class="inspector-value" style="font-size: 0.82rem;">
                            <span title="Prevents JavaScript document.cookie access">✅ HttpOnly</span> | 
                            <span title="Protects against CSRF attacks">✅ SameSite=Lax</span>
                        </div>
                    </div>
                </div>

                <!-- Live Timeout Counter & Inactivity Monitor -->
                <div class="timeout-counter-wrap">
                    <div>
                        <strong>⏱️ Inactivity Session Timeout:</strong>
                        <span>Auto-logout in <strong id="timeout-timer" style="font-size: 1.15rem; color: #B45309;">15:00</strong> minutes</span>
                        <span style="display: block; font-size: 0.78rem; opacity: 0.85;">Timer automatically resets on user interaction.</span>
                    </div>
                    <div style="font-size: 0.82rem;">
                        <button type="button" onclick="pingSession()" class="btn-sim" style="background: rgba(255,255,255,0.8);">🔄 Stay Active</button>
                    </div>
                </div>

                <!-- Practical 10 Evaluator & Viva Controls -->
                <div class="demo-box" style="margin-top: 10px;">
                    <div class="demo-box-header">
                        <span>🧪 Practical 10 Evaluation & Viva Demonstration Controls</span>
                    </div>
                    <div class="sim-btn-group">
                        <!-- Button 1: Test 10s Timeout -->
                        <button type="button" class="btn-sim" onclick="setTestTimeout()">
                            ⏳ Test 10-Second Timeout
                        </button>

                        <!-- Button 2: Test Unauthorized Admin Access -->
                        <a href="admin.php" class="btn-sim btn-sim-danger">
                            ⛔ Test Unauthorized Admin Access (RBAC Block)
                        </a>

                        <!-- Button 3: Force Immediate Session Expiry -->
                        <button type="button" class="btn-sim btn-sim-danger" onclick="forceExpireSession()">
                            ⚡ Force Session Expiration
                        </button>

                        <!-- Button 4: Clean Sign Out -->
                        <a href="logout.php" class="btn-sim">
                            🚪 Secure Logout
                        </a>
                    </div>
                </div>
            </section>

            <!-- ACADEMIC METRICS ROW -->
            <div class="admin-metrics-grid">
                <div class="admin-metric-card">
                    <div class="metric-icon-wrap" style="background: rgba(13, 148, 136, 0.1); color: var(--accent-green);">📚</div>
                    <div class="metric-text">
                        <h4>Enrolled Courses</h4>
                        <p class="metric-number">6</p>
                    </div>
                </div>
                <div class="admin-metric-card">
                    <div class="metric-icon-wrap" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">📊</div>
                    <div class="metric-text">
                        <h4>Semester Attendance</h4>
                        <p class="metric-number">91.4%</p>
                    </div>
                </div>
                <div class="admin-metric-card">
                    <div class="metric-icon-wrap" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">📝</div>
                    <div class="metric-text">
                        <h4>Pending Practicals</h4>
                        <p class="metric-number">1</p>
                    </div>
                </div>
                <div class="admin-metric-card">
                    <div class="metric-icon-wrap" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">🎓</div>
                    <div class="metric-text">
                        <h4>Current SGPA</h4>
                        <p class="metric-number">9.12</p>
                    </div>
                </div>
            </div>

            <!-- STUDENT DETAILS & COURSES -->
            <div class="data-table-card" style="margin-top: 25px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 22px;">
                <h3 style="margin-top: 0; margin-bottom: 15px;">Active Semester Course Enrollment</h3>
                <table class="custom-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary); font-size: 0.85rem;">
                            <th style="padding: 10px;">COURSE CODE</th>
                            <th style="padding: 10px;">COURSE TITLE</th>
                            <th style="padding: 10px;">CREDITS</th>
                            <th style="padding: 10px;">FACULTY</th>
                            <th style="padding: 10px;">ATTENDANCE</th>
                            <th style="padding: 10px;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.92rem;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 10px;"><strong>IT312</strong></td>
                            <td style="padding: 12px 10px;">Web Development Frameworks (WDF)</td>
                            <td style="padding: 12px 10px;">4.0</td>
                            <td style="padding: 12px 10px;">Prof. Ananya Patel</td>
                            <td style="padding: 12px 10px;"><strong style="color: var(--accent-green);">94%</strong></td>
                            <td style="padding: 12px 10px;"><span class="badge-role">Active</span></td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 10px;"><strong>IT314</strong></td>
                            <td style="padding: 12px 10px;">Database Management Systems & SQL</td>
                            <td style="padding: 12px 10px;">4.0</td>
                            <td style="padding: 12px 10px;">Dr. Rajesh Sharma</td>
                            <td style="padding: 12px 10px;"><strong style="color: var(--accent-green);">89%</strong></td>
                            <td style="padding: 12px 10px;"><span class="badge-role">Active</span></td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 10px;"><strong>IT316</strong></td>
                            <td style="padding: 12px 10px;">Design & Analysis of Algorithms</td>
                            <td style="padding: 12px 10px;">4.0</td>
                            <td style="padding: 12px 10px;">Prof. Vikram Desai</td>
                            <td style="padding: 12px 10px;"><strong style="color: var(--accent-green);">92%</strong></td>
                            <td style="padding: 12px 10px;"><span class="badge-role">Active</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Interactive Scripts -->
    <script src="../js/app.js"></script>
    <script>
        // Toggle masked vs full session id
        function toggleSessionId() {
            const masked = document.getElementById('sess-masked');
            const full = document.getElementById('sess-full');
            if (masked.style.display === 'none') {
                masked.style.display = 'inline';
                full.style.display = 'none';
            } else {
                masked.style.display = 'none';
                full.style.display = 'inline';
            }
        }

        // Live Inactivity Countdown Timer (Practical 10 Requirement)
        let remainingSeconds = <?= (int)get_session_remaining_seconds() ?>;
        const timerElem = document.getElementById('timeout-timer');

        function updateTimerDisplay() {
            const m = Math.floor(remainingSeconds / 60);
            const s = remainingSeconds % 60;
            timerElem.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;

            if (remainingSeconds <= 30) {
                timerElem.style.color = '#DC2626'; // Red warning
            } else {
                timerElem.style.color = '#B45309';
            }

            if (remainingSeconds <= 0) {
                // Session expired! Auto redirect to login with timeout notice
                window.location.href = 'login.php?msg=timeout';
            }
        }

        // Countdown interval
        const timerInterval = setInterval(() => {
            if (remainingSeconds > 0) {
                remainingSeconds--;
                updateTimerDisplay();
            } else {
                clearInterval(timerInterval);
                window.location.href = 'login.php?msg=timeout';
            }
        }, 1000);

        // Ping session to stay alive
        async function pingSession() {
            try {
                const res = await fetch('session_status.php');
                const data = await res.json();
                if (data.authenticated) {
                    remainingSeconds = data.remaining_seconds;
                    updateTimerDisplay();
                    alert('Session activity refreshed! Timeout extended.');
                } else {
                    window.location.href = 'login.php?msg=timeout';
                }
            } catch (e) {
                console.error('Session ping error', e);
            }
        }

        // Set 10-second test timeout for easy viva & screenshot evaluation
        async function setTestTimeout() {
            try {
                const res = await fetch('session_status.php?action=set_test_timeout');
                const data = await res.json();
                if (data.status === 'success') {
                    remainingSeconds = data.remaining_seconds;
                    updateTimerDisplay();
                    alert(data.message);
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Force session expiration
        async function forceExpireSession() {
            try {
                await fetch('session_status.php?action=force_expire');
                window.location.href = 'login.php?msg=timeout';
            } catch (e) {
                console.error(e);
            }
        }

        updateTimerDisplay();
    </script>
</body>
</html>

<?php
/**
 * StudentHub Portal - Stored Submissions Viewer
 * Practical 7 Intermediate Extension: Display stored CSV and JSON records on a webpage
 */

$csvFile = __DIR__ . '/../data/registrations.csv';
$jsonFile = __DIR__ . '/../data/registrations.json';
$contactCsv = __DIR__ . '/../data/contacts.csv';

$registrations = [];
if (file_exists($jsonFile)) {
    $registrations = json_decode(file_get_contents($jsonFile), true) ?: [];
}

$contacts = [];
if (file_exists($contactCsv)) {
    if (($handle = fopen($contactCsv, "r")) !== FALSE) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            $contacts[] = $data;
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions Archive - Students Hub</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .archive-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .data-table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            overflow-x: auto;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.95rem;
        }
        .custom-table th {
            background: #F4EFEB;
            color: var(--text-primary);
            padding: 12px 16px;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
        }
        .custom-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        .custom-table tr:hover {
            background: #FAF8F5;
        }
        .badge-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(15, 118, 110, 0.1);
            color: var(--accent-green);
        }
    </style>
</head>
<body class="light-cream-bg">
    <header>
        <div class="logo"><a href="../index.html"><h1>Students Hub</h1></a></div>
        <nav class="center-nav">
            <ul>
                <li><a href="dashboard.php">Student Dashboard</a></li>
                <li><a href="admin.php">Admin Portal</a></li>
                <li><a href="login.php">Sign In</a></li>
                <li><a href="../pages/register.html">Register New</a></li>
            </ul>
        </nav>
    </header>

    <main class="archive-wrapper">
        <section>
            <h2>Stored Form Submissions (File Storage Engine)</h2>
            <p style="color: var(--text-secondary);">Records saved server-side into <code>data/registrations.json</code>, <code>data/registrations.csv</code>, and <code>data/contacts.csv</code>.</p>
        </section>

        <!-- Registrations Table -->
        <div class="data-table-card">
            <h3>Registered Students (JSON Storage)</h3>
            <?php if (!empty($registrations)): ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Gender</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($registrations) as $row): ?>
                            <tr>
                                <td><code>#<?php echo htmlspecialchars($row['id'] ?? '-'); ?></code></td>
                                <td style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($row['fullname'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile'] ?? '-'); ?></td>
                                <td><span class="badge-pill"><?php echo htmlspecialchars($row['course'] ?? '-'); ?></span></td>
                                <td><?php echo htmlspecialchars($row['year'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['gender'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-secondary); margin-top: 15px;">No registration records stored yet in <code>data/registrations.json</code>. Try submitting the <a href="../pages/register.html">Registration Form</a>.</p>
            <?php endif; ?>
        </div>

        <!-- Contact Inquiries Table -->
        <div class="data-table-card">
            <h3>Contact Messages (CSV Storage)</h3>
            <?php if (!empty($contacts)): ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($contacts) as $c): ?>
                            <tr>
                                <td><code>#<?php echo htmlspecialchars($c[0] ?? '-'); ?></code></td>
                                <td style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($c[1] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($c[2] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($c[3] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($c[4] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($c[5] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-secondary); margin-top: 15px;">No inquiries recorded yet in <code>data/contacts.csv</code>. Try sending a message via the <a href="../pages/contact.html">Contact Us</a> page.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Students Hub Portal - File-based Submissions Module</p>
    </footer>
</body>
</html>

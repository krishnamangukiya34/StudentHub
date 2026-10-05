<?php
/**
 * StudentHub Portal - Registration Processor
 * Practical 7: Server-side validation, sanitization, CSV and JSON file storage
 */

session_start();

$errors = [];
$successMessage = "";

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(["status" => "error", "message" => "Method Not Allowed. Please submit the form via POST."]));
}

// 1. Sanitize Inputs
$fullname = trim(filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$mobile   = trim(filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$password = $_POST['reg-password'] ?? '';
$confirm  = $_POST['confirm-password'] ?? '';
$course   = trim(filter_input(INPUT_POST, 'course', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$year     = trim(filter_input(INPUT_POST, 'year', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$gender   = trim(filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$terms    = isset($_POST['terms']);

// 2. Validate Inputs with Regular Expressions & Rules
if (empty($fullname) || !preg_match("/^[a-zA-Z\s]{3,60}$/", $fullname)) {
    $errors['fullname'] = "Full name must be 3-60 characters and contain only letters and spaces.";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "A valid university or personal email address is required.";
}

if (empty($mobile) || !preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors['mobile'] = "Mobile number must be exactly 10 numeric digits.";
}

// Password validation: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
$passwordRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
if (empty($password) || !preg_match($passwordRegex, $password)) {
    $errors['password'] = "Password must be at least 8 characters with 1 uppercase, 1 lowercase, 1 digit, and 1 special symbol.";
}

if ($password !== $confirm) {
    $errors['confirm-password'] = "Confirm password does not match password.";
}

$validCourses = ['B.Tech IT', 'B.Tech CSE', 'B.Tech AI&DS', 'BCA', 'MCA', 'M.Tech CSE'];
if (empty($course) || !in_array($course, $validCourses)) {
    $errors['course'] = "Please select a valid enrolled course from the options.";
}

$validYears = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
if (empty($year) || !in_array($year, $validYears)) {
    $errors['year'] = "Please specify a valid academic year.";
}

$validGenders = ['Male', 'Female', 'Other'];
if (empty($gender) || !in_array($gender, $validGenders)) {
    $errors['gender'] = "Please select a valid gender option.";
}

if (!$terms) {
    $errors['terms'] = "You must agree to the Terms of Service to register.";
}

// Check for errors
if (!empty($errors)) {
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        http_response_code(422);
        echo json_encode(["status" => "error", "errors" => $errors]);
        exit;
    }
} else {
    // 3. Prepare Safe Record
    $timestamp = date("Y-m-d H:i:s");
    $record = [
        "id"         => time(),
        "fullname"   => $fullname,
        "email"      => $email,
        "mobile"     => $mobile,
        "course"     => $course,
        "year"       => $year,
        "gender"     => $gender,
        "created_at" => $timestamp
    ];

    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    // A. Store in CSV format (data/registrations.csv)
    $csvFile = $dataDir . '/registrations.csv';
    $isNewFile = !file_exists($csvFile) || filesize($csvFile) === 0;
    
    $fp = fopen($csvFile, 'a');
    if ($fp) {
        if (flock($fp, LOCK_EX)) {
            if ($isNewFile) {
                fputcsv($fp, ["ID", "Full Name", "Email", "Mobile", "Course", "Year", "Gender", "Registered At"]);
            }
            fputcsv($fp, [
                $record['id'],
                $record['fullname'],
                $record['email'],
                $record['mobile'],
                $record['course'],
                $record['year'],
                $record['gender'],
                $record['created_at']
            ]);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    // B. Store in JSON format (data/registrations.json)
    $jsonFile = $dataDir . '/registrations.json';
    $jsonList = [];
    if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
        $existing = file_get_contents($jsonFile);
        $jsonList = json_decode($existing, true) ?: [];
    }
    $jsonList[] = $record;
    file_put_contents($jsonFile, json_encode($jsonList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        echo json_encode([
            "status"  => "success",
            "message" => "Registration successful! Record safely stored in CSV and JSON formats.",
            "data"    => $record
        ]);
        exit;
    }

    $successMessage = "Registration successful! Your profile has been recorded.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status - Students Hub</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .result-box {
            max-width: 600px;
            margin: 60px auto;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .error-list {
            text-align: left;
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px 25px;
            border-radius: 8px;
            margin-top: 20px;
            color: #991b1b;
        }
    </style>
</head>
<body class="light-cream-bg">
    <header>
        <div class="logo"><a href="../index.html"><h1>Students Hub</h1></a></div>
    </header>
    <main>
        <div class="result-box">
            <?php if (!empty($successMessage)): ?>
                <div style="font-size: 3rem;">🎉</div>
                <h2 style="color: var(--accent-green); margin: 15px 0 10px;"><?php echo htmlspecialchars($successMessage); ?></h2>
                <p style="color: var(--text-secondary);">Student: <strong><?php echo htmlspecialchars($fullname); ?></strong> (<?php echo htmlspecialchars($course); ?>)</p>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Record stored in <code>data/registrations.csv</code> and <code>data/registrations.json</code>.</p>
                <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
                    <a href="../pages/login.html" class="btn-register">Proceed to Login</a>
                    <a href="view_submissions.php" class="btn-login">View All Submissions</a>
                </div>
            <?php else: ?>
                <div style="font-size: 3rem;">⚠️</div>
                <h2 style="color: #dc2626; margin: 15px 0 10px;">Registration Form Errors</h2>
                <p style="color: var(--text-secondary);">Please correct the following fields and resubmit:</p>
                <ul class="error-list">
                    <?php foreach ($errors as $field => $msg): ?>
                        <li><strong><?php echo htmlspecialchars(ucfirst($field)); ?>:</strong> <?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div style="margin-top: 30px;">
                    <a href="../pages/register.html" class="btn-register">Back to Register Form</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>

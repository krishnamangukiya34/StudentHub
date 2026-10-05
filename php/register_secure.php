<?php
/**
 * StudentHub Portal - Secure Registration Handler
 * Practical 9: Database Insert, Duplicate Email/Username Check, and Password Hashing using PDO Prepared Statements
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Only POST method accepted."]);
    exit;
}

// 1. Sanitize and collect inputs
$fullname = trim(filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$mobile   = trim(filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$rollNo   = trim(filter_input(INPUT_POST, 'roll_no', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$password = $_POST['reg-password'] ?? '';
$confirm  = $_POST['confirm-password'] ?? '';
$course   = trim(filter_input(INPUT_POST, 'course', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$year     = trim(filter_input(INPUT_POST, 'year', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$gender   = trim(filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

// Generate default roll number if not explicitly submitted
if (empty($rollNo)) {
    $rollNo = "24IT" . str_pad(strval(rand(100, 999)), 3, "0", STR_PAD_LEFT);
}

$errors = [];

// 2. Validate inputs
if (empty($fullname) || !preg_match("/^[a-zA-Z\s]{3,60}$/", $fullname)) {
    $errors['fullname'] = "Full name must be 3-60 characters and contain letters only.";
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "A valid email address is required.";
}
if (empty($mobile) || !preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors['mobile'] = "Mobile number must be 10 digits.";
}
$passwordRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
if (empty($password) || !preg_match($passwordRegex, $password)) {
    $errors['password'] = "Password must be at least 8 characters with upper, lower, number, and special character.";
}
if ($password !== $confirm) {
    $errors['confirm-password'] = "Confirm password does not match.";
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "errors" => $errors]);
    exit;
}

try {
    $pdo = getDbConnection();

    // 3. Duplicate Email and Roll No Check using Prepared Statement
    $checkSql = "SELECT id, email, roll_no FROM students WHERE email = :email OR roll_no = :roll_no LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([
        ':email'   => $email,
        ':roll_no' => $rollNo
    ]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        http_response_code(409); // Conflict
        $conflictField = ($existing['email'] === $email) ? "email" : "roll_no";
        
        // Log failed attempt to audit log
        $auditSql = "INSERT INTO audit_logs (event_type, user_email, ip_address, user_agent, status, details) 
                     VALUES ('registration_attempt', :email, :ip, :ua, 'Failed', 'Duplicate entry detected')";
        $pdo->prepare($auditSql)->execute([
            ':email' => $email,
            ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':ua'    => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250)
        ]);

        echo json_encode([
            "status" => "error",
            "message" => "An account with this " . ($conflictField === "email" ? "email address" : "roll number") . " is already registered."
        ]);
        exit;
    }

    // 4. Secure Password Hashing using password_hash() with Bcrypt
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // 5. Database Insert with Prepared Statement (prevents SQL Injection)
    $insertSql = "INSERT INTO students (roll_no, full_name, email, phone, password_hash, course, year, gender, role, created_at)
                  VALUES (:roll_no, :full_name, :email, :phone, :hash, :course, :year, :gender, 'student', NOW())";
    
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':roll_no'   => $rollNo,
        ':full_name' => $fullname,
        ':email'     => $email,
        ':phone'     => $mobile,
        ':hash'      => $passwordHash,
        ':course'    => $course ?: 'B.Tech IT',
        ':year'      => $year ?: '1st Year',
        ':gender'    => $gender ?: 'Other'
    ]);

    $newStudentId = $pdo->lastInsertId();

    // 6. Audit Log Entry (Practical 9 Advanced Extension)
    $auditSql = "INSERT INTO audit_logs (event_type, user_email, ip_address, user_agent, status, details) 
                 VALUES ('student_registration', :email, :ip, :ua, 'Success', :details)";
    $pdo->prepare($auditSql)->execute([
        ':email'   => $email,
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250),
        ':details' => "Account created with ID: " . $newStudentId
    ]);

    echo json_encode([
        "status"  => "success",
        "message" => "Secure registration completed successfully! Your password has been hashed and stored.",
        "student" => [
            "id"        => $newStudentId,
            "roll_no"   => $rollNo,
            "full_name" => $fullname,
            "email"     => $email,
            "course"    => $course
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Secure Registration DB Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Database transaction failed. Please ensure MySQL server is initialized and `database/studenthub.sql` is imported."
    ]);
}
?>

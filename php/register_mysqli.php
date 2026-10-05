<?php
/**
 * StudentHub Portal - Secure Registration Handler (MySQLi Implementation)
 * Practical 9: Explicit MySQLi Prepared Statements, password_hash(), Duplicate Email Check
 */

header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'studenthub';
$port = 3306;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Only POST method accepted."]);
    exit;
}

$conn = @new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "MySQLi Connection failed: " . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

// 1. Sanitize Inputs
$fullname = trim(filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$mobile   = trim(filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$password = $_POST['reg-password'] ?? '';
$confirm  = $_POST['confirm-password'] ?? '';
$course   = trim(filter_input(INPUT_POST, 'course', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'B.Tech IT');
$year     = trim(filter_input(INPUT_POST, 'year', FILTER_SANITIZE_SPECIAL_CHARS) ?? '1st Year');
$gender   = trim(filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Other');
$rollNo   = "24IT" . rand(100, 999);

// 2. Validate Inputs
$errors = [];
if (empty($fullname) || !preg_match("/^[a-zA-Z\s]{3,60}$/", $fullname)) {
    $errors['fullname'] = "Full name must be 3-60 characters (letters and spaces only).";
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "A valid university email address is required.";
}
if (empty($mobile) || !preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors['mobile'] = "Mobile number must be 10 digits.";
}
if (strlen($password) < 8) {
    $errors['password'] = "Password must be at least 8 characters.";
}
if ($password !== $confirm) {
    $errors['confirm-password'] = "Passwords do not match.";
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "errors" => $errors]);
    $conn->close();
    exit;
}

// 3. Duplicate Email Check using MySQLi Prepared Statement
$checkStmt = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "An account with this email address already exists in the database."]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// 4. Secure Password Hashing
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// 5. Insert Record using MySQLi Prepared Statement
$insertStmt = $conn->prepare("INSERT INTO students (roll_no, full_name, email, phone, password_hash, course, year, gender, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$insertStmt->bind_param("ssssssss", $rollNo, $fullname, $email, $mobile, $passwordHash, $course, $year, $gender);

if ($insertStmt->execute()) {
    $newId = $insertStmt->insert_id;
    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully using MySQLi prepared statements!",
        "student_id" => $newId,
        "roll_no" => $rollNo
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Insert execution failed: " . $insertStmt->error]);
}

$insertStmt->close();
$conn->close();
?>

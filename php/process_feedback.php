<?php
/**
 * StudentHub Portal - Feedback Processor
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Only POST method accepted."]);
    exit;
}

$name     = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'General');
$rating   = intval($_POST['rating'] ?? 5);
$message  = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

$errors = [];
if (empty($name)) $errors['name'] = "Full name is required.";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Valid email is required.";
if ($rating < 1 || $rating > 5) $errors['rating'] = "Rating must be between 1 and 5.";
if (empty($message) || strlen($message) < 5) $errors['message'] = "Please provide feedback of at least 5 characters.";

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "errors" => $errors]);
    exit;
}

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$record = [
    "id"         => time(),
    "name"       => $name,
    "email"      => $email,
    "category"   => $category,
    "rating"     => $rating,
    "message"    => $message,
    "created_at" => date("Y-m-d H:i:s")
];

$jsonFile = $dataDir . '/feedback.json';
$list = [];
if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $list = json_decode(file_get_contents($jsonFile), true) ?: [];
}
$list[] = $record;
file_put_contents($jsonFile, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

echo json_encode([
    "status" => "success",
    "message" => "Thank you! Your feedback helps us improve StudentHub."
]);
?>

<?php
/**
 * StudentHub Portal - Contact Form Processor
 * Practical 7: Process contact inquiries, validate, and store in CSV/JSON
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Only POST method accepted."]);
    exit;
}

$name    = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email   = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

$errors = [];
if (empty($name) || strlen($name) < 2) {
    $errors['name'] = "Name must be at least 2 characters.";
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "A valid email address is required.";
}
if (empty($subject) || strlen($subject) < 3) {
    $errors['subject'] = "Subject must be at least 3 characters.";
}
if (empty($message) || strlen($message) < 5) {
    $errors['message'] = "Message content must be at least 5 characters.";
}

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
    "subject"    => $subject,
    "message"    => $message,
    "created_at" => date("Y-m-d H:i:s")
];

// Append to CSV
$csvFile = $dataDir . '/contacts.csv';
$isNew = !file_exists($csvFile) || filesize($csvFile) === 0;
$fp = fopen($csvFile, 'a');
if ($fp) {
    if (flock($fp, LOCK_EX)) {
        if ($isNew) {
            fputcsv($fp, ["ID", "Name", "Email", "Subject", "Message", "Timestamp"]);
        }
        fputcsv($fp, [$record['id'], $record['name'], $record['email'], $record['subject'], $record['message'], $record['created_at']]);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// Append to JSON
$jsonFile = $dataDir . '/contacts.json';
$list = [];
if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $list = json_decode(file_get_contents($jsonFile), true) ?: [];
}
$list[] = $record;
file_put_contents($jsonFile, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

echo json_encode([
    "status" => "success",
    "message" => "Thank you! Your message has been received. Our campus support team will contact you shortly."
]);
?>

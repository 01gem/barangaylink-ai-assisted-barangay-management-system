<?php
require_once __DIR__ . '/../common.php';
session_start();
if (empty($_SESSION['official_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_post();
$db = get_db();
$input = read_json_input();

$fname = trim((string)($input['fname'] ?? ''));
$lname = trim((string)($input['lname'] ?? ''));
$address = trim((string)($input['address'] ?? ''));
$contact = trim((string)($input['contact'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($fname === '' || $lname === '' || $address === '' || $contact === '' || $email === '' || $password === '') {
  json_error('All resident fields are required.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_error('Invalid email address.');
}

$check = $db->prepare("SELECT id FROM residents WHERE email = ? LIMIT 1");
if (!$check) json_error('Failed to validate resident email.', 500);
$check->bind_param('s', $email);
$rows = db_query_all($check);
$check->close();
if (count($rows) > 0) {
  json_error('Resident email already exists.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = $db->prepare("INSERT INTO residents (fname, lname, address, contact, email, password) VALUES (?, ?, ?, ?, ?, ?)");
if (!$insert) json_error('Failed to prepare resident insert.', 500);
$insert->bind_param('ssssss', $fname, $lname, $address, $contact, $email, $hash);
if (!$insert->execute()) {
  json_error('Failed to create resident: ' . $insert->error, 500);
}
$insert->close();

json_success(['message' => 'Resident created successfully.']);
?>

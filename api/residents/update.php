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

$id = (int)($input['resident_id'] ?? 0);
$fname = trim((string)($input['fname'] ?? ''));
$lname = trim((string)($input['lname'] ?? ''));
$address = trim((string)($input['address'] ?? ''));
$contact = trim((string)($input['contact'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($id <= 0 || $fname === '' || $lname === '' || $address === '' || $contact === '' || $email === '') {
  json_error('Resident id and required fields are missing.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_error('Invalid email address.');
}

$check = $db->prepare("SELECT id FROM residents WHERE email = ? AND id <> ? LIMIT 1");
if (!$check) json_error('Failed to validate resident email.', 500);
$check->bind_param('si', $email, $id);
$rows = db_query_all($check);
$check->close();
if (count($rows) > 0) {
  json_error('Resident email already exists.');
}

if ($password !== '') {
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $db->prepare("UPDATE residents SET fname = ?, lname = ?, address = ?, contact = ?, email = ?, password = ? WHERE id = ?");
  if (!$stmt) json_error('Failed to prepare resident update.', 500);
  $stmt->bind_param('ssssssi', $fname, $lname, $address, $contact, $email, $hash, $id);
} else {
  $stmt = $db->prepare("UPDATE residents SET fname = ?, lname = ?, address = ?, contact = ?, email = ? WHERE id = ?");
  if (!$stmt) json_error('Failed to prepare resident update.', 500);
  $stmt->bind_param('sssssi', $fname, $lname, $address, $contact, $email, $id);
}

if (!$stmt->execute()) {
  json_error('Failed to update resident: ' . $stmt->error, 500);
}
$stmt->close();

json_success(['message' => 'Resident updated successfully.']);
?>

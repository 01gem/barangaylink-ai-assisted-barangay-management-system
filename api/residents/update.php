<?php
require_once __DIR__ . '/../common.php';
session_start();
require_official_admin();

require_post();
$db = get_db();
$input = read_json_input();

$id = (int)($input['resident_id'] ?? 0);
$fname = trim((string)($input['fname'] ?? ''));
$lname = trim((string)($input['lname'] ?? ''));
$address = trim((string)($input['address'] ?? ''));
$contact = trim((string)($input['contact'] ?? ''));
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($id <= 0 || $fname === '' || $lname === '' || $address === '' || $contact === '' || $username === '') {
  json_error('Resident id and required fields are missing.');
}
if (!preg_match('/^[a-zA-Z0-9._]{3,50}$/', $username)) {
  json_error('Username must be 3-50 characters and contain only letters, numbers, dots, and underscores.');
}

$check = $db->prepare("SELECT id FROM residents WHERE username = ? AND id <> ? LIMIT 1");
if (!$check) json_error('Failed to validate resident username.', 500);
$check->bind_param('si', $username, $id);
$rows = db_query_all($check);
$check->close();
if (count($rows) > 0) {
  json_error('Resident username already exists.');
}

if ($password !== '') {
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $db->prepare("UPDATE residents SET fname = ?, lname = ?, address = ?, contact = ?, username = ?, password = ? WHERE id = ?");
  if (!$stmt) json_error('Failed to prepare resident update.', 500);
  $stmt->bind_param('ssssssi', $fname, $lname, $address, $contact, $username, $hash, $id);
} else {
  $stmt = $db->prepare("UPDATE residents SET fname = ?, lname = ?, address = ?, contact = ?, username = ? WHERE id = ?");
  if (!$stmt) json_error('Failed to prepare resident update.', 500);
  $stmt->bind_param('sssssi', $fname, $lname, $address, $contact, $username, $id);
}

if (!$stmt->execute()) {
  json_error('Failed to update resident: ' . $stmt->error, 500);
}
$stmt->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Updated resident',
  'resident',
  "{$fname} {$lname} (ID: {$id})"
);

json_success(['message' => 'Resident updated successfully.']);
?>

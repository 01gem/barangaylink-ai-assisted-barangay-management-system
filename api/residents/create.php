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
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($fname === '' || $lname === '' || $address === '' || $contact === '' || $username === '' || $password === '') {
  json_error('All resident fields are required.');
}
if (!preg_match('/^[a-zA-Z0-9._]{3,50}$/', $username)) {
  json_error('Username must be 3-50 characters and contain only letters, numbers, dots, and underscores.');
}

$check = $db->prepare("SELECT id FROM residents WHERE username = ? LIMIT 1");
if (!$check) json_error('Failed to validate resident username.', 500);
$check->bind_param('s', $username);
$rows = db_query_all($check);
$check->close();
if (count($rows) > 0) {
  json_error('Resident username already exists.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = $db->prepare("INSERT INTO residents (fname, lname, address, contact, username, password) VALUES (?, ?, ?, ?, ?, ?)");
if (!$insert) json_error('Failed to prepare resident insert.', 500);
$insert->bind_param('ssssss', $fname, $lname, $address, $contact, $username, $hash);
if (!$insert->execute()) {
  json_error('Failed to create resident: ' . $insert->error, 500);
}
$residentId = (int)$insert->insert_id;
$insert->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Created resident',
  'resident',
  "{$fname} {$lname} (ID: {$residentId})"
);

json_success(['message' => 'Resident created successfully.']);
?>

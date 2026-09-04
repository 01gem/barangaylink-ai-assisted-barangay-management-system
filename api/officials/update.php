<?php
require_once __DIR__ . '/../common.php';
session_start();
require_official_admin();

require_post();
$db = get_db();
$input = read_json_input();

$id = (int)($input['official_id'] ?? 0);
$fname = trim((string)($input['fname'] ?? ''));
$lname = trim((string)($input['lname'] ?? ''));
$address = trim((string)($input['address'] ?? ''));
$contact = trim((string)($input['contact'] ?? ''));
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');
$position = trim((string)($input['position'] ?? ''));
$role = trim((string)($input['role'] ?? ''));

if ($id <= 0 || $fname === '' || $lname === '' || $address === '' || $contact === '' || $username === '' || $position === '' || $role === '') {
  json_error('Official id and required fields are missing.');
}
if (!preg_match('/^[a-zA-Z0-9._]{3,50}$/', $username)) {
  json_error('Username must be 3-50 characters and contain only letters, numbers, dots, and underscores.');
}
if (!in_array($role, ['staff', 'admin'], true)) {
  json_error('Role must be staff or admin.');
}

$existingStmt = $db->prepare('SELECT id, role, status FROM barangay_officials WHERE id = ? LIMIT 1');
if (!$existingStmt) json_error('Failed to load official record.', 500);
$existingStmt->bind_param('i', $id);
$existingRows = db_query_all($existingStmt);
$existingStmt->close();
if (count($existingRows) === 0) {
  json_error('Official account was not found.');
}

$sessionOfficialId = (int)$_SESSION['official_id'];
if ($id === $sessionOfficialId && $role !== 'admin') {
  json_error('You cannot remove admin access from your own account.');
}

if ($existingRows[0]['role'] === 'admin' && $role !== 'admin') {
  $adminCountStmt = $db->prepare("SELECT COUNT(*) AS total FROM barangay_officials WHERE role = 'admin' AND status = 'active'");
  if (!$adminCountStmt) json_error('Failed to validate admin accounts.', 500);
  $adminCountRows = db_query_all($adminCountStmt);
  $adminCountStmt->close();
  if ((int)($adminCountRows[0]['total'] ?? 0) <= 1) {
    json_error('Cannot demote the last active admin account.');
  }
}

$check = $db->prepare('SELECT id FROM barangay_officials WHERE username = ? AND id <> ? LIMIT 1');
if (!$check) json_error('Failed to validate official username.', 500);
$check->bind_param('si', $username, $id);
$rows = db_query_all($check);
$check->close();
if (count($rows) > 0) {
  json_error('Official username already exists.');
}

if ($password !== '') {
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $db->prepare('UPDATE barangay_officials SET fname = ?, lname = ?, username = ?, role = ?, address = ?, contact = ?, password = ?, position = ? WHERE id = ?');
  if (!$stmt) json_error('Failed to prepare official update.', 500);
  $stmt->bind_param('ssssssssi', $fname, $lname, $username, $role, $address, $contact, $hash, $position, $id);
} else {
  $stmt = $db->prepare('UPDATE barangay_officials SET fname = ?, lname = ?, username = ?, role = ?, address = ?, contact = ?, position = ? WHERE id = ?');
  if (!$stmt) json_error('Failed to prepare official update.', 500);
  $stmt->bind_param('sssssssi', $fname, $lname, $username, $role, $address, $contact, $position, $id);
}

if (!$stmt->execute()) {
  json_error('Failed to update official: ' . $stmt->error, 500);
}
$stmt->close();

if ($id === $sessionOfficialId) {
  $_SESSION['official_name'] = trim($fname . ' ' . $lname);
  $_SESSION['official_role'] = $role;
}

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Updated official',
  'official',
  official_full_name(['fname' => $fname, 'lname' => $lname]) . " (ID: {$id})"
);

json_success(['message' => 'Official account updated successfully.']);
?>

<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

require_once __DIR__ . '/../common.php';
session_start();
require_official_admin();

require_post();
$db = get_db();
$input = read_json_input();

$fname = trim((string)($input['fname'] ?? ''));
$lname = trim((string)($input['lname'] ?? ''));
$address = trim((string)($input['address'] ?? ''));
$contact = trim((string)($input['contact'] ?? ''));
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');
$position = trim((string)($input['position'] ?? ''));
$role = trim((string)($input['role'] ?? 'staff'));

if ($fname === '' || $lname === '' || $address === '' || $contact === '' || $username === '' || $password === '' || $position === '') {
  json_error('All official fields are required.');
}
if (!preg_match('/^[a-zA-Z0-9._]{3,50}$/', $username)) {
  json_error('Username must be 3-50 characters and contain only letters, numbers, dots, and underscores.');
}
if (!in_array($role, ['staff', 'admin'], true)) {
  json_error('Role must be staff or admin.');
}

$check = $db->prepare('SELECT id FROM barangay_officials WHERE username = ? LIMIT 1');
if (!$check) json_error('Failed to validate official username.', 500);
$check->bind_param('s', $username);
$rows = db_query_all($check);
$check->close();
if (count($rows) > 0) {
  json_error('Official username already exists.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = $db->prepare('INSERT INTO barangay_officials (fname, lname, username, role, address, contact, password, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
if (!$insert) json_error('Failed to prepare official insert.', 500);
$insert->bind_param('ssssssss', $fname, $lname, $username, $role, $address, $contact, $hash, $position);
if (!$insert->execute()) {
  json_error('Failed to create official: ' . $insert->error, 500);
}
$officialId = (int)$insert->insert_id;
$insert->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Created official',
  'official',
  official_full_name(['fname' => $fname, 'lname' => $lname]) . " (ID: {$officialId})"
);

json_success(['message' => 'Official account created successfully.']);
?>

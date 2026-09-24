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

$id = (int)($input['official_id'] ?? 0);
if ($id <= 0) {
  json_error('Valid official id is required.');
}

if ($id === (int)$_SESSION['official_id']) {
  json_error('You cannot deactivate your own account.');
}

$lookup = $db->prepare('SELECT fname, lname, role, status FROM barangay_officials WHERE id = ? LIMIT 1');
if (!$lookup) json_error('Failed to load official record.', 500);
$lookup->bind_param('i', $id);
$rows = db_query_all($lookup);
$lookup->close();
if (count($rows) === 0) {
  json_error('Official account was not found.');
}

$current = $rows[0];
$currentStatus = (string)$current['status'];
$newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

if ($current['role'] === 'admin' && $currentStatus === 'active' && $newStatus === 'inactive') {
  $adminCountStmt = $db->prepare("SELECT COUNT(*) AS total FROM barangay_officials WHERE role = 'admin' AND status = 'active'");
  if (!$adminCountStmt) json_error('Failed to validate admin accounts.', 500);
  $adminCountRows = db_query_all($adminCountStmt);
  $adminCountStmt->close();
  if ((int)($adminCountRows[0]['total'] ?? 0) <= 1) {
    json_error('Cannot deactivate the last active admin account.');
  }
}

$stmt = $db->prepare('UPDATE barangay_officials SET status = ? WHERE id = ?');
if (!$stmt) json_error('Failed to prepare official status update.', 500);
$stmt->bind_param('si', $newStatus, $id);
if (!$stmt->execute()) {
  json_error('Failed to update official status: ' . $stmt->error, 500);
}
$stmt->close();

$fullName = official_full_name($current);
$action = $newStatus === 'inactive' ? 'Deactivated official' : 'Activated official';
log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  $action,
  'official',
  "{$fullName} (ID: {$id})"
);

json_success([
  'message' => $newStatus === 'inactive'
    ? 'Official account deactivated successfully.'
    : 'Official account activated successfully.',
  'status' => $newStatus
]);
?>

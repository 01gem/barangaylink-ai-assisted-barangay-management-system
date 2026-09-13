<?php
require_once __DIR__ . '/../common.php';
session_start();
require_official_admin();

require_post();
$db = get_db();
$input = read_json_input();

$id = (int)($input['resident_id'] ?? 0);
if ($id <= 0) {
  json_error('Valid resident id is required.');
}

$lookup = $db->prepare('SELECT fname, lname, status FROM residents WHERE id = ? LIMIT 1');
if (!$lookup) json_error('Failed to load resident record.', 500);
$lookup->bind_param('i', $id);
$rows = db_query_all($lookup);
$lookup->close();
if (count($rows) === 0) {
  json_error('Resident account was not found.', 404);
}

$current = $rows[0];
$currentStatus = (string)($current['status'] ?? 'active');
$newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

$stmt = $db->prepare('UPDATE residents SET status = ? WHERE id = ?');
if (!$stmt) json_error('Failed to prepare resident status update.', 500);
$stmt->bind_param('si', $newStatus, $id);
if (!$stmt->execute()) {
  json_error('Failed to update resident status: ' . $stmt->error, 500);
}
$stmt->close();

$fullName = trim(((string)($current['fname'] ?? '')) . ' ' . ((string)($current['lname'] ?? '')));
$targetRef = $fullName !== '' ? "{$fullName} (ID: {$id})" : "Resident ID {$id}";
$action = $newStatus === 'inactive' ? 'Deactivated resident' : 'Activated resident';
log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  $action,
  'resident',
  $targetRef
);

json_success([
  'message' => $newStatus === 'inactive'
    ? 'Resident account deactivated successfully.'
    : 'Resident account activated successfully.',
  'status' => $newStatus
]);
?>

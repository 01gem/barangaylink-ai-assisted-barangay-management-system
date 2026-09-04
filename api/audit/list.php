<?php
require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['official_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$db = get_db();

$officialRole = (string)($_SESSION['official_role'] ?? '');
$query = 'SELECT id, official_id, official_name, action, target_type, target_reference, details, created_at FROM audit_log';
if ($officialRole === 'staff') {
  $query .= ' WHERE official_id = ?';
}
$query .= ' ORDER BY created_at DESC, id DESC';

$stmt = $db->prepare($query);
if (!$stmt) json_error('Failed to prepare audit log query.', 500);
if ($officialRole === 'staff') {
  $officialId = (int)$_SESSION['official_id'];
  $stmt->bind_param('i', $officialId);
}
$rows = db_query_all($stmt);
$stmt->close();

json_success(['logs' => $rows]);
?>

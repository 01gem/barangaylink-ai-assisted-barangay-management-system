<?php
require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['official_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$db = get_db();

$stmt = $db->prepare('SELECT id, official_id, official_name, action, target_type, target_reference, details, created_at FROM audit_log ORDER BY created_at DESC, id DESC');
if (!$stmt) json_error('Failed to prepare audit log query.', 500);
$rows = db_query_all($stmt);
$stmt->close();

json_success(['logs' => $rows]);
?>

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
$referenceNo = trim((string)($input['reference_no'] ?? ''));
$newStatus = trim((string)($input['status'] ?? ''));

if ($referenceNo === '' || $newStatus === '') {
  json_error('Reference number and new status are required.');
}

$allowed = ['pending','processing','ready','completed'];
if (!in_array($newStatus, $allowed, true)) {
  json_error('Invalid status.');
}

try {
  $stmt = $db->prepare("SELECT id, status FROM document_requests WHERE reference_no = ? LIMIT 1");
  if (!$stmt) json_error('Failed to prepare lookup.', 500);
  $stmt->bind_param('s', $referenceNo);
  $rows = db_query_all($stmt);
  $stmt->close();
  if (count($rows) === 0) json_error('Document request not found.', 404);
  $req = $rows[0];

  $up = $db->prepare("UPDATE document_requests SET status = ? WHERE id = ?");
  if (!$up) json_error('Failed to prepare update.', 500);
  $rid = (int)$req['id'];
  $up->bind_param('si', $newStatus, $rid);
  if (!$up->execute()) json_error('Failed to update status: ' . $up->error, 500);
  $up->close();
} catch (mysqli_sql_exception $e) {
  json_error('Database error while updating status.', 500);
}

json_success(['message' => "Status updated to {$newStatus}."]); 
?>

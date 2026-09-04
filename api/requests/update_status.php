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
  $stmt = $db->prepare("SELECT id, status, resident_id, reference_no FROM document_requests WHERE reference_no = ? LIMIT 1");
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

  $residentId = (int)($req['resident_id'] ?? 0);
  if ($residentId > 0) {
    $title = 'Document Request Update';
    $statusText = $newStatus === 'ready' ? 'ready for pickup' : $newStatus;
    $body = "Your request {$req['reference_no']} is now {$statusText}.";
    $createdAt = date('Y-m-d H:i:s');
    $isRead = 0;

    $notify = $db->prepare('INSERT INTO notifications (resident_id, title, body, is_read, created_at) VALUES (?, ?, ?, ?, ?)');
    if (!$notify) json_error('Failed to prepare notification insert.', 500);
    $notify->bind_param('issis', $residentId, $title, $body, $isRead, $createdAt);
    if (!$notify->execute()) {
      json_error('Failed to create notification: ' . $notify->error, 500);
    }
    $notify->close();
  }

  log_audit(
    $db,
    isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
    (string)($_SESSION['official_name'] ?? ''),
    'Updated document request status',
    'document_request',
    (string)$req['reference_no'],
    "New status: {$newStatus}"
  );
} catch (mysqli_sql_exception $e) {
  json_error('Database error while updating status.', 500);
}

json_success(['message' => "Status updated to {$newStatus}."]); 
?>

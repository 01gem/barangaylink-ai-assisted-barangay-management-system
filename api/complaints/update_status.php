<?php
require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['official_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_post();

$input = read_json_input();
$id = (int)($input['id'] ?? 0);
$status = trim((string)($input['status'] ?? ''));
$officialNote = trim((string)($input['official_note'] ?? ''));

if ($id <= 0 || $status === '') {
  json_error('Complaint id and status are required.');
}

$allowed = ['open', 'investigating', 'resolved'];
if (!in_array($status, $allowed, true)) {
  json_error('Invalid status. Allowed values are: open, investigating, resolved.');
}

$db = get_db();
$check = $db->prepare('SELECT id FROM complaints WHERE id = ? LIMIT 1');
if (!$check) json_error('Failed to prepare complaint lookup.', 500);
$check->bind_param('i', $id);
$rows = db_query_all($check);
$check->close();
if (count($rows) === 0) {
  json_error('Complaint not found.', 404);
}

$stmt = $db->prepare('UPDATE complaints SET status = ?, official_note = ? WHERE id = ?');
if (!$stmt) json_error('Failed to prepare complaint update.', 500);
$stmt->bind_param('ssi', $status, $officialNote, $id);
if (!$stmt->execute()) {
  json_error('Failed to update complaint: ' . $stmt->error, 500);
}
$stmt->close();

json_success(['message' => "Complaint status updated to {$status}."]);
?>

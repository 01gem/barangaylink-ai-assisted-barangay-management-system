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
if ($id <= 0) {
  json_error('Valid announcement id is required.');
}

$db = get_db();
$targetReference = "Announcement ID {$id}";
$lookup = $db->prepare('SELECT title FROM announcements WHERE id = ? LIMIT 1');
if ($lookup) {
  $lookup->bind_param('i', $id);
  if ($lookup->execute()) {
    $lookup->bind_result($title);
    if ($lookup->fetch()) {
      $resolvedTitle = trim((string)$title);
      if ($resolvedTitle !== '') {
        $targetReference = $resolvedTitle;
      }
    }
  }
  $lookup->close();
}

$stmt = $db->prepare('DELETE FROM announcements WHERE id = ?');
if (!$stmt) json_error('Failed to prepare announcement delete.', 500);
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
  json_error('Failed to delete announcement: ' . $stmt->error, 500);
}
if ($stmt->affected_rows === 0) {
  $stmt->close();
  json_error('Announcement not found.', 404);
}
$stmt->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Deleted announcement',
  'announcement',
  $targetReference
);

json_success(['message' => 'Announcement deleted successfully.']);
?>

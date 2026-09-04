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
$title = trim((string)($input['title'] ?? ''));
$content = trim((string)($input['content'] ?? ''));
$expiresAt = trim((string)($input['expires_at'] ?? ''));
$isPinned = !empty($input['is_pinned']) ? 1 : 0;

if ($id <= 0 || $title === '' || $content === '') {
  json_error('Announcement id, title, and content are required.');
}
if ($expiresAt !== '') {
  $parsedExpiry = DateTime::createFromFormat('Y-m-d\TH:i', $expiresAt)
    ?: DateTime::createFromFormat('Y-m-d H:i:s', $expiresAt)
    ?: DateTime::createFromFormat('Y-m-d H:i', $expiresAt);
  $dateErrors = DateTime::getLastErrors();
  if (!$parsedExpiry || ($dateErrors !== false && array_sum($dateErrors) > 0)) {
    json_error('Expiration date and time is invalid.');
  }
  $expiresAt = $parsedExpiry->format('Y-m-d H:i:s');
} else {
  $expiresAt = null;
}

$db = get_db();
$check = $db->prepare('SELECT id FROM announcements WHERE id = ? LIMIT 1');
if (!$check) json_error('Failed to prepare announcement lookup.', 500);
$check->bind_param('i', $id);
$rows = db_query_all($check);
$check->close();
if (count($rows) === 0) {
  json_error('Announcement not found.', 404);
}

$stmt = $db->prepare('UPDATE announcements SET title = ?, content = ?, expires_at = ?, is_pinned = ? WHERE id = ?');
if (!$stmt) json_error('Failed to prepare announcement update.', 500);
$stmt->bind_param('sssii', $title, $content, $expiresAt, $isPinned, $id);
if (!$stmt->execute()) {
  json_error('Failed to update announcement: ' . $stmt->error, 500);
}
$stmt->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Updated announcement',
  'announcement',
  $title
);

json_success(['message' => 'Announcement updated successfully.']);
?>

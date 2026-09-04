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
$title = trim((string)($input['title'] ?? ''));
$content = trim((string)($input['content'] ?? ''));
$isPinned = !empty($input['is_pinned']) ? 1 : 0;

if ($title === '' || $content === '') {
  json_error('Title and content are required.');
}

$db = get_db();
$postedBy = (int)$_SESSION['official_id'];
$stmt = $db->prepare('INSERT INTO announcements (title, content, is_pinned, posted_by) VALUES (?, ?, ?, ?)');
if (!$stmt) json_error('Failed to prepare announcement insert.', 500);
$stmt->bind_param('ssii', $title, $content, $isPinned, $postedBy);
if (!$stmt->execute()) {
  json_error('Failed to create announcement: ' . $stmt->error, 500);
}
$announcementId = $stmt->insert_id;
$stmt->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Created announcement',
  'announcement',
  $title
);

json_success(['id' => $announcementId, 'message' => 'Announcement created successfully.']);
?>

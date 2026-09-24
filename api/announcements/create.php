<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

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
$expiresAt = trim((string)($input['expires_at'] ?? ''));
$isPinned = !empty($input['is_pinned']) ? 1 : 0;

if ($title === '' || $content === '') {
  json_error('Title and content are required.');
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
$postedBy = (int)$_SESSION['official_id'];
$stmt = $db->prepare('INSERT INTO announcements (title, content, expires_at, is_pinned, posted_by) VALUES (?, ?, ?, ?, ?)');
if (!$stmt) json_error('Failed to prepare announcement insert.', 500);
$stmt->bind_param('sssii', $title, $content, $expiresAt, $isPinned, $postedBy);
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

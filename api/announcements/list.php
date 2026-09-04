<?php
require_once __DIR__ . '/../common.php';

$db = get_db();
$sql = 'SELECT id, title, content, is_pinned, created_at FROM announcements ORDER BY is_pinned DESC, created_at DESC';
$stmt = $db->prepare($sql);
if (!$stmt) json_error('Failed to prepare announcements query.', 500);
$rows = db_query_all($stmt);
$stmt->close();

json_success(['announcements' => $rows]);
?>

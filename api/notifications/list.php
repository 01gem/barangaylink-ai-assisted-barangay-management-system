<?php
require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['resident_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
}

$db = get_db();

$sql = "SELECT id, title, body, is_read, created_at
        FROM notifications
        ORDER BY id DESC";
$stmt = $db->prepare($sql);
if (!$stmt) json_error('Failed to prepare notifications query.', 500);
$rows = db_query_all($stmt);
$stmt->close();

json_success(['notifications' => $rows]);
?>

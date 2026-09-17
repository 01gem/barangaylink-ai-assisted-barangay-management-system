<?php
require_once __DIR__ . '/../common.php';
header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['resident_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_post();
$residentId = (int)$_SESSION['resident_id'];
$db = get_db();
$stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE resident_id = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $residentId);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
}

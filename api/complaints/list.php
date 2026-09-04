<?php
require_once __DIR__ . '/../common.php';
session_start();

$officialSession = !empty($_SESSION['official_id']);
$residentSession = !empty($_SESSION['resident_id']);
if (!$officialSession && !$residentSession) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
}

$db = get_db();

$sql = "SELECT id, resident_id, reference_no, resident_name, category, location_text, description, date_filed, status, official_note
        FROM complaints";
if ($officialSession) {
  $sql .= ' ORDER BY id DESC';
} else {
  $sql .= ' WHERE resident_id = ? ORDER BY id DESC';
}
$stmt = $db->prepare($sql);
if (!$stmt) json_error('Failed to prepare complaints query.', 500);
if (!$officialSession) {
  $residentId = (int)$_SESSION['resident_id'];
  $stmt->bind_param('i', $residentId);
}
$rows = db_query_all($stmt);
$stmt->close();

json_success(['complaints' => $rows]);
?>

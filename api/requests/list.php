<?php
require_once __DIR__ . '/../common.php';
session_start();

$db = get_db();

$officialSession = !empty($_SESSION['official_id']);
$residentSession = !empty($_SESSION['resident_id']);
if (!$officialSession && !$residentSession) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

try {
  $stmt = $db->prepare('SELECT * FROM document_requests ORDER BY id DESC');
  if (!$stmt) json_error('Failed to prepare requests query.', 500);
  $rows = db_query_all($stmt);
  $stmt->close();
} catch (mysqli_sql_exception $e) {
  json_error('Failed to load requests.', 500);
}

if (!$officialSession) {
  $residentId = !empty($_SESSION['resident_id']) ? (int)$_SESSION['resident_id'] : 0;
  if ($residentId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
  }

  $rows = array_values(array_filter($rows, function ($row) use ($residentId) {
    $rowResidentId = isset($row['resident_id']) ? (int)$row['resident_id'] : 0;
    return $rowResidentId === $residentId;
  }));
}

json_success(['requests' => $rows]);
?>
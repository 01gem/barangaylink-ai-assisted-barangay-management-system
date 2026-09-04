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
  $residentEmail = trim((string)($_SESSION['resident_email'] ?? ''));
  $residentName = trim((string)($_SESSION['resident_name'] ?? ''));

  if ($residentId <= 0 && $residentEmail === '' && $residentName === '') {
    json_success(['requests' => []]);
  }

  $rows = array_values(array_filter($rows, function ($row) use ($residentId, $residentEmail, $residentName) {
    $rowResidentId = isset($row['resident_id']) ? (int)$row['resident_id'] : 0;
    $rowEmail = trim((string)($row['resident_email'] ?? ''));
    $rowName = trim((string)($row['resident_name'] ?? ''));

    if ($residentId > 0 && $rowResidentId > 0 && $rowResidentId === $residentId) {
      return true;
    }
    if ($residentEmail !== '' && $rowEmail !== '' && strcasecmp($rowEmail, $residentEmail) === 0) {
      return true;
    }
    if ($residentName !== '' && $rowName !== '' && strcasecmp($rowName, $residentName) === 0) {
      return true;
    }
    return false;
  }));
}

json_success(['requests' => $rows]);
?>
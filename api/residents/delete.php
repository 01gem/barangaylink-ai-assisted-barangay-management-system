<?php
require_once __DIR__ . '/../common.php';
session_start();
if (empty($_SESSION['official_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_post();
$db = get_db();
$input = read_json_input();

$id = (int)($input['resident_id'] ?? 0);
if ($id <= 0) {
  json_error('Valid resident id is required.');
}

$targetReference = "Resident ID {$id}";
$lookup = $db->prepare('SELECT fname, lname FROM residents WHERE id = ? LIMIT 1');
if ($lookup) {
  $lookup->bind_param('i', $id);
  if ($lookup->execute()) {
    $lookup->bind_result($fname, $lname);
    if ($lookup->fetch()) {
      $fullName = trim(((string)$fname) . ' ' . ((string)$lname));
      if ($fullName !== '') {
        $targetReference = "{$fullName} (ID: {$id})";
      }
    }
  }
  $lookup->close();
}

$stmt = $db->prepare("DELETE FROM residents WHERE id = ?");
if (!$stmt) json_error('Failed to prepare resident delete.', 500);
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
  json_error('Failed to delete resident: ' . $stmt->error, 500);
}
$stmt->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Deleted resident',
  'resident',
  $targetReference
);

json_success(['message' => 'Resident deleted successfully.']);
?>

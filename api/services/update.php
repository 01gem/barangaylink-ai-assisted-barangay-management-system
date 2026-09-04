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
$serviceName = trim((string)($input['service_name'] ?? ''));
$category = trim((string)($input['category'] ?? ''));
$contactNumber = trim((string)($input['contact_number'] ?? ''));
$address = trim((string)($input['address'] ?? ''));
$operatingHours = trim((string)($input['operating_hours'] ?? ''));
$description = trim((string)($input['description'] ?? ''));

if ($id <= 0 || $serviceName === '' || $category === '') {
  json_error('Service id, service name, and category are required.');
}

$db = get_db();
$check = $db->prepare('SELECT id FROM local_services WHERE id = ? LIMIT 1');
if (!$check) json_error('Failed to prepare service lookup.', 500);
$check->bind_param('i', $id);
$rows = db_query_all($check);
$check->close();
if (count($rows) === 0) {
  json_error('Service not found.', 404);
}

$stmt = $db->prepare('UPDATE local_services SET service_name = ?, category = ?, contact_number = ?, address = ?, operating_hours = ?, description = ? WHERE id = ?');
if (!$stmt) json_error('Failed to prepare service update.', 500);
$stmt->bind_param('ssssssi', $serviceName, $category, $contactNumber, $address, $operatingHours, $description, $id);
if (!$stmt->execute()) {
  json_error('Failed to update service: ' . $stmt->error, 500);
}
$stmt->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Updated service',
  'service',
  $serviceName
);

json_success(['message' => 'Service updated successfully.']);
?>

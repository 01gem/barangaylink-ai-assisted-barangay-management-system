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
$serviceName = trim((string)($input['service_name'] ?? ''));
$category = trim((string)($input['category'] ?? ''));
$contactNumber = trim((string)($input['contact_number'] ?? ''));
$address = trim((string)($input['address'] ?? ''));
$operatingHours = trim((string)($input['operating_hours'] ?? ''));
$description = trim((string)($input['description'] ?? ''));

if ($serviceName === '' || $category === '') {
  json_error('Service name and category are required.');
}

$db = get_db();
$postedBy = (int)$_SESSION['official_id'];
$stmt = $db->prepare('INSERT INTO local_services (service_name, category, contact_number, address, operating_hours, description, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) json_error('Failed to prepare service insert.', 500);
$stmt->bind_param('ssssssi', $serviceName, $category, $contactNumber, $address, $operatingHours, $description, $postedBy);
if (!$stmt->execute()) {
  json_error('Failed to create service: ' . $stmt->error, 500);
}
$serviceId = $stmt->insert_id;
$stmt->close();

json_success(['id' => $serviceId, 'message' => 'Service created successfully.']);
?>

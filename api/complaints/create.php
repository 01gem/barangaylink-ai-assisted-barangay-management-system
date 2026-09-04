<?php
require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['resident_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_post();

$db = get_db();
$input = read_json_input();

$category = trim((string)($input['category'] ?? ''));
$location = trim((string)($input['location_text'] ?? ''));
$description = trim((string)($input['description'] ?? ''));
if ($category === '' || $location === '' || $description === '') {
  json_error('Category, location, and description are required.');
}

$prefix = 'CSR-' . date('Ymd') . '-';
$ref = $prefix . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
$residentId = (int)$_SESSION['resident_id'];
$residentName = trim((string)($_SESSION['resident_name'] ?? ''));
if ($residentName === '') {
  json_error('Resident session expired. Please log in again.', 401);
}
$dateFiled = date('M d, Y');
$status = 'open';
$note = 'Received. Awaiting assignment.';

$stmt = $db->prepare("INSERT INTO complaints (resident_id, reference_no, resident_name, category, location_text, description, date_filed, status, official_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) json_error('Failed to prepare complaint insert.', 500);
$stmt->bind_param('issssssss', $residentId, $ref, $residentName, $category, $location, $description, $dateFiled, $status, $note);
if (!$stmt->execute()) {
  json_error('Failed to submit complaint: ' . $stmt->error, 500);
}
$stmt->close();

json_success(['reference_no' => $ref, 'message' => 'Complaint submitted successfully.']);
?>

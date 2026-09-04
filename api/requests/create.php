<?php
require_once __DIR__ . '/../common.php';
session_start();

require_post();

$db = get_db();
$input = read_json_input();

$documentType = trim((string)($input['document_type'] ?? ''));
$purpose = trim((string)($input['purpose'] ?? ''));
if ($documentType === '' || $purpose === '') {
  json_error('Document type and purpose are required.');
}

$residentId = !empty($_SESSION['resident_id']) ? (int)$_SESSION['resident_id'] : 0;
$residentName = trim((string)($_SESSION['resident_name'] ?? ''));

if ($residentId <= 0) {
  json_error('Resident session is missing required account linkage. Please log in again.', 401);
}
if ($residentName === '') {
  json_error('Resident session expired. Please log in again.', 401);
}

$prefix = 'REQ-' . date('Ymd') . '-';
$ref = $prefix . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
$dateRequested = date('M d, Y');
$status = 'pending';

try {
  $stmt = $db->prepare("INSERT INTO document_requests (reference_no, resident_id, resident_name, document_type, purpose, date_requested, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
  if (!$stmt) json_error('Failed to prepare request insert.', 500);
  $stmt->bind_param('sisssss', $ref, $residentId, $residentName, $documentType, $purpose, $dateRequested, $status);

  if (!$stmt->execute()) {
    json_error('Failed to submit request: ' . $stmt->error, 500);
  }
  $stmt->close();
} catch (Throwable $e) {
  json_error('Failed to submit request: ' . $e->getMessage(), 500);
}

json_success(['reference_no' => $ref, 'message' => 'Request submitted successfully.']);
?>
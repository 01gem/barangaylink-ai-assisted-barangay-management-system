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
$residentEmail = trim((string)($_SESSION['resident_email'] ?? ''));

if ($residentId <= 0 || $residentName === '') {
  json_error('Resident session expired. Please log in again.', 401);
}

$hasResidentIdCol = false;
$hasResidentEmailCol = false;
$hasResidentNameCol = false;

try {
  $colStmt = $db->prepare('SHOW COLUMNS FROM document_requests');
  if ($colStmt) {
    $colRows = db_query_all($colStmt);
    foreach ($colRows as $colRow) {
      $field = (string)($colRow['Field'] ?? '');
      if ($field === 'resident_id') $hasResidentIdCol = true;
      if ($field === 'resident_email') $hasResidentEmailCol = true;
      if ($field === 'resident_name') $hasResidentNameCol = true;
    }
    $colStmt->close();
  }
} catch (Throwable $schemaError) {
  // Leave flags false; insert logic will fall back or return a JSON error below.
}

$prefix = 'REQ-' . date('Ymd') . '-';
$ref = $prefix . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
$dateRequested = date('M d, Y');
$status = 'pending';

try {
  if ($hasResidentIdCol) {
    $stmt = $db->prepare("INSERT INTO document_requests (reference_no, resident_id, resident_name, resident_email, document_type, purpose, date_requested, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) json_error('Failed to prepare request insert.', 500);
    $stmt->bind_param('sissssss', $ref, $residentId, $residentName, $residentEmail, $documentType, $purpose, $dateRequested, $status);
  } elseif ($hasResidentEmailCol) {
    $stmt = $db->prepare("INSERT INTO document_requests (reference_no, resident_name, resident_email, document_type, purpose, date_requested, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) json_error('Failed to prepare request insert.', 500);
    $stmt->bind_param('sssssss', $ref, $residentName, $residentEmail, $documentType, $purpose, $dateRequested, $status);
  } elseif ($hasResidentNameCol) {
    $stmt = $db->prepare("INSERT INTO document_requests (reference_no, resident_name, document_type, purpose, date_requested, status) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) json_error('Failed to prepare request insert.', 500);
    $stmt->bind_param('ssssss', $ref, $residentName, $documentType, $purpose, $dateRequested, $status);
  } else {
    json_error('Document request table is missing resident columns.', 500);
  }

  if (!$stmt->execute()) {
    json_error('Failed to submit request: ' . $stmt->error, 500);
  }
  $stmt->close();
} catch (Throwable $e) {
  json_error('Failed to submit request: ' . $e->getMessage(), 500);
}

json_success(['reference_no' => $ref, 'message' => 'Request submitted successfully.']);
?>
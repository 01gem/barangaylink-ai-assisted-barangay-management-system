<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

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

$referenceNo = trim((string)($input['reference_no'] ?? ''));
$contactOverride = trim((string)($input['contact'] ?? ''));
if ($referenceNo === '') {
  json_error('Request reference number is required.');
}
if ($contactOverride !== '' && !preg_match('/^[+0-9][0-9\s-]{6,}$/', $contactOverride)) {
  json_error('Provided contact number is invalid.');
}

$reqStmt = $db->prepare("SELECT id, reference_no, resident_id, resident_name, document_type, status FROM document_requests WHERE reference_no = ? LIMIT 1");
if (!$reqStmt) json_error('Failed to prepare request lookup.', 500);
$reqStmt->bind_param('s', $referenceNo);
$rows = db_query_all($reqStmt);
$reqStmt->close();
if (count($rows) === 0) {
  json_error('Document request not found.', 404);
}
$request = $rows[0];

$residentId = isset($request['resident_id']) ? (int)$request['resident_id'] : 0;
if ($residentId <= 0) {
  json_error('Document request is missing resident linkage.', 500);
}

$resContactStmt = $db->prepare('SELECT contact FROM residents WHERE id = ? LIMIT 1');
if (!$resContactStmt) json_error('Failed to prepare resident contact lookup.', 500);
$resContactStmt->bind_param('i', $residentId);
$resContactRows = db_query_all($resContactStmt);
$resContactStmt->close();
if (count($resContactRows) === 0) {
  json_error('Linked resident record not found for this request.', 404);
}
$residentContact = $contactOverride !== '' ? $contactOverride : trim((string)($resContactRows[0]['contact'] ?? ''));

if ($residentContact === '') {
  json_error('Resident contact number not found. Enter the number manually when sending SMS.', 400);
}

$safeResident = $request['resident_name'] !== '' ? $request['resident_name'] : 'Resident';
$smsMessage = "Hello {$safeResident}, your requested document ({$request['document_type']}) with reference number {$request['reference_no']} is now ready for pickup at the Barangay Hall of Sampaguita. Please bring a valid ID when claiming your document. - Barangay Hall of Sampaguita";

function send_sms_via_httpsms(string $apiKey, string $fromNumber, string $toNumber, string $content, string $simSlot = 'SIM1'): void {
  if ($apiKey === '' || $fromNumber === '' || $toNumber === '' || $content === '') {
    json_error('SMS configuration or recipient data is missing.', 500);
  }

  $payload = json_encode([
    'from' => $fromNumber,
    'to' => $toNumber,
    'content' => $content,
    'sim' => $simSlot ?: 'SIM1'
  ]);
  if ($payload === false) {
    json_error('SMS payload encoding failed.', 500);
  }

  if (!function_exists('curl_init')) {
    json_error('SMS send failed: PHP cURL extension is not enabled.', 500);
  }

  $ch = curl_init('https://api.httpsms.com/v1/messages/send');
  if ($ch === false) {
    json_error('SMS send failed: could not initialize cURL.', 500);
  }

  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Accept: application/json',
      'x-api-key: ' . $apiKey,
      'Content-Length: ' . strlen($payload),
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 25,
  ]);

  $response = curl_exec($ch);
  if ($response === false) {
    $errorMessage = curl_error($ch);
    curl_close($ch);
    json_error('SMS send failed: ' . ($errorMessage !== '' ? $errorMessage : 'unable to reach httpSMS.'), 500);
  }

  $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if (!in_array($httpStatus, [200, 201, 202], true)) {
    $decoded = json_decode($response, true);
    $errorMessage = is_array($decoded) && !empty($decoded['message']) ? $decoded['message'] : $response;
    json_error('SMS send failed: ' . $errorMessage, 500);
  }
}

$smsApiKey = getenv('HTTPSMS_API_KEY') ?: '';
$smsFromNumber = getenv('HTTPSMS_FROM_NUMBER') ?: '';
$smsSimSlot = getenv('HTTPSMS_SIM_SLOT') ?: 'SIM1';
send_sms_via_httpsms($smsApiKey, $smsFromNumber, $residentContact, $smsMessage, $smsSimSlot);

$status = 'ready';
$upStmt = $db->prepare("UPDATE document_requests SET status = ? WHERE id = ?");
if (!$upStmt) json_error('Failed to prepare request status update.', 500);
$requestId = (int)$request['id'];
$upStmt->bind_param('si', $status, $requestId);
if (!$upStmt->execute()) {
  json_error('Failed to update request status: ' . $upStmt->error, 500);
}
$upStmt->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Sent pickup notification',
  'document_request',
  (string)$request['reference_no'],
  "Status updated to {$status}. SMS sent to {$residentContact}."
);

json_success([
  'message' => "SMS ready notice sent to {$residentContact} from Barangay Hall of Sampaguita."
]);
?>

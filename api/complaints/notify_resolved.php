<?php
require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['official_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_post();

function send_sms_via_httpsms(string $apiKey, string $fromNumber, string $toNumber, string $content, string $simSlot = 'SIM1'): array {
  if ($apiKey === '' || $fromNumber === '' || $toNumber === '' || $content === '') {
    return ['status' => 'failed', 'reason' => 'SMS configuration or recipient data is missing.'];
  }

  $payload = json_encode([
    'from' => $fromNumber,
    'to' => $toNumber,
    'content' => $content,
    'sim' => $simSlot ?: 'SIM1'
  ]);
  if ($payload === false) {
    return ['status' => 'failed', 'reason' => 'SMS payload encoding failed.'];
  }

  if (!function_exists('curl_init')) {
    return ['status' => 'failed', 'reason' => 'SMS send failed: PHP cURL extension is not enabled.'];
  }

  $ch = curl_init('https://api.httpsms.com/v1/messages/send');
  if ($ch === false) {
    return ['status' => 'failed', 'reason' => 'SMS send failed: could not initialize cURL.'];
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
    return ['status' => 'failed', 'reason' => 'SMS send failed: ' . ($errorMessage !== '' ? $errorMessage : 'unable to reach httpSMS.')];
  }

  $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if (!in_array($httpStatus, [200, 201, 202], true)) {
    $decoded = json_decode($response, true);
    $errorMessage = is_array($decoded) && !empty($decoded['message']) ? $decoded['message'] : $response;
    return ['status' => 'failed', 'reason' => 'SMS send failed: ' . $errorMessage];
  }

  return ['status' => 'sent', 'reason' => 'SMS resolution notice sent.'];
}

$input = read_json_input();
$id = (int)($input['id'] ?? 0);
$officialNote = trim((string)($input['official_note'] ?? ''));
if ($id <= 0) {
  json_error('Valid complaint id is required.');
}

$db = get_db();
$complaintStmt = $db->prepare('SELECT id, reference_no, resident_id FROM complaints WHERE id = ? LIMIT 1');
if (!$complaintStmt) json_error('Failed to prepare complaint lookup.', 500);
$complaintStmt->bind_param('i', $id);
$complaintRows = db_query_all($complaintStmt);
$complaintStmt->close();
if (count($complaintRows) === 0) {
  json_error('Complaint not found.', 404);
}
$complaint = $complaintRows[0];

$sms = ['status' => 'skipped', 'reason' => 'No SMS was sent because this complaint has no linked resident.'];
$residentId = (int)($complaint['resident_id'] ?? 0);
if ($residentId > 0) {
  $residentStmt = $db->prepare('SELECT contact FROM residents WHERE id = ? LIMIT 1');
  if (!$residentStmt) json_error('Failed to prepare resident contact lookup.', 500);
  $residentStmt->bind_param('i', $residentId);
  $residentRows = db_query_all($residentStmt);
  $residentStmt->close();

  if (count($residentRows) === 0) {
    $sms = ['status' => 'skipped', 'reason' => 'No SMS was sent because the linked resident record was not found.'];
  } else {
    $contact = trim((string)($residentRows[0]['contact'] ?? ''));
    if ($contact === '') {
      $sms = ['status' => 'skipped', 'reason' => 'No SMS was sent because the linked resident has no contact number.'];
    } elseif (!preg_match('/^[+0-9][0-9\s-]{6,}$/', $contact)) {
      $sms = ['status' => 'failed', 'reason' => 'SMS was not sent because the linked resident contact number is invalid.'];
    } else {
      $noteText = $officialNote !== '' ? " Official note: {$officialNote}" : '';
      $smsMessage = "Your complaint with reference number {$complaint['reference_no']} has been resolved by Barangay Hall of Sampaguita." . $noteText;
      $sms = send_sms_via_httpsms(
        getenv('HTTPSMS_API_KEY') ?: '',
        getenv('HTTPSMS_FROM_NUMBER') ?: '',
        $contact,
        $smsMessage,
        getenv('HTTPSMS_SIM_SLOT') ?: 'SIM1'
      );
    }
  }
}

$status = 'resolved';
$updateStmt = $db->prepare('UPDATE complaints SET status = ?, official_note = ? WHERE id = ?');
if (!$updateStmt) json_error('Failed to prepare complaint resolution update.', 500);
$updateStmt->bind_param('ssi', $status, $officialNote, $id);
if (!$updateStmt->execute()) {
  json_error('Failed to resolve complaint: ' . $updateStmt->error, 500);
}
$updateStmt->close();

$residentId = (int)($complaint['resident_id'] ?? 0);
if ($residentId > 0) {
  $title = 'Complaint Update';
  $body = "Your complaint {$complaint['reference_no']} is now {$status}.";
  $createdAt = date('Y-m-d H:i:s');
  $isRead = 0;

  $notify = $db->prepare('INSERT INTO notifications (resident_id, title, body, is_read, created_at) VALUES (?, ?, ?, ?, ?)');
  if (!$notify) json_error('Failed to prepare complaint notification insert.', 500);
  $notify->bind_param('issis', $residentId, $title, $body, $isRead, $createdAt);
  if (!$notify->execute()) {
    json_error('Failed to create complaint notification: ' . $notify->error, 500);
  }
  $notify->close();
}

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Resolved complaint',
  'complaint',
  (string)$complaint['reference_no'],
  $officialNote !== '' ? "Official note: {$officialNote}" : ''
);

json_success([
  'status_updated' => true,
  'sms_status' => $sms['status'],
  'sms_reason' => $sms['reason'],
  'message' => "Complaint resolved. {$sms['reason']}"
]);
?>

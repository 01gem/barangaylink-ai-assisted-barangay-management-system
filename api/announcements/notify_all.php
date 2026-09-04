<?php
require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['official_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_post();

function send_announcement_sms(string $apiKey, string $fromNumber, string $toNumber, string $content, string $simSlot): array {
  if ($apiKey === '' || $fromNumber === '' || $toNumber === '' || $content === '') {
    return ['ok' => false, 'error' => 'SMS configuration or recipient data is missing.'];
  }
  $payload = json_encode([
    'from' => $fromNumber,
    'to' => $toNumber,
    'content' => $content,
    'sim' => $simSlot ?: 'SIM1'
  ]);
  if ($payload === false) {
    return ['ok' => false, 'error' => 'SMS payload encoding failed.'];
  }
  if (!function_exists('curl_init')) {
    return ['ok' => false, 'error' => 'SMS send failed: PHP cURL extension is not enabled.'];
  }

  $ch = curl_init('https://api.httpsms.com/v1/messages/send');
  if ($ch === false) {
    return ['ok' => false, 'error' => 'SMS send failed: could not initialize cURL.'];
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
    $error = curl_error($ch);
    curl_close($ch);
    return ['ok' => false, 'error' => $error !== '' ? $error : 'Unable to reach httpSMS.'];
  }
  $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if (!in_array($httpStatus, [200, 201, 202], true)) {
    $decoded = json_decode($response, true);
    $message = is_array($decoded) && !empty($decoded['message']) ? $decoded['message'] : trim($response);
    return ['ok' => false, 'error' => $message !== '' ? $message : "httpSMS returned HTTP {$httpStatus}."];
  }
  return ['ok' => true, 'error' => ''];
}

$input = read_json_input();
$announcementId = (int)($input['id'] ?? $input['announcement_id'] ?? 0);
if ($announcementId <= 0) {
  json_error('Valid announcement id is required.');
}

$db = get_db();
$announcementStmt = $db->prepare('SELECT title, content FROM announcements WHERE id = ? LIMIT 1');
if (!$announcementStmt) json_error('Failed to prepare announcement lookup.', 500);
$announcementStmt->bind_param('i', $announcementId);
$announcementRows = db_query_all($announcementStmt);
$announcementStmt->close();
if (count($announcementRows) === 0) {
  json_error('Announcement not found.', 404);
}

$announcement = $announcementRows[0];
$title = trim((string)$announcement['title']);
$content = trim((string)$announcement['content']);
$excerpt = preg_replace('/\s+/', ' ', $content);
$excerpt = trim((string)$excerpt);
if (strlen($excerpt) > 120) {
  $excerpt = rtrim(substr($excerpt, 0, 120)) . '...';
}
$smsMessage = "Barangay announcement: {$title}. {$excerpt}";

$residentsStmt = $db->prepare('SELECT id, contact FROM residents');
if (!$residentsStmt) json_error('Failed to prepare resident lookup.', 500);
$residents = db_query_all($residentsStmt);
$residentsStmt->close();

$sent = 0;
$skipped = 0;
$failed = 0;
$smsApiKey = getenv('HTTPSMS_API_KEY') ?: '';
$smsFromNumber = getenv('HTTPSMS_FROM_NUMBER') ?: '';
$smsSimSlot = getenv('HTTPSMS_SIM_SLOT') ?: 'SIM1';

foreach ($residents as $resident) {
  $residentId = (int)$resident['id'];
  $contact = trim((string)($resident['contact'] ?? ''));
  if ($contact === '') {
    $skipped++;
    continue;
  }

  $result = send_announcement_sms($smsApiKey, $smsFromNumber, $contact, $smsMessage, $smsSimSlot);
  if (!$result['ok']) {
    $failed++;
    continue;
  }

  $sent++;
  $notificationTitle = 'New Announcement';
  $notificationBody = $title . ': ' . $excerpt;
  $isRead = 0;
  $createdAt = date('Y-m-d H:i:s');
  $notificationStmt = $db->prepare('INSERT INTO notifications (resident_id, title, body, is_read, created_at) VALUES (?, ?, ?, ?, ?)');
  if ($notificationStmt) {
    $notificationStmt->bind_param('issis', $residentId, $notificationTitle, $notificationBody, $isRead, $createdAt);
    $notificationStmt->execute();
    $notificationStmt->close();
  } else {
    error_log('Announcement notification prepare failed: ' . $db->error);
  }
}

log_audit(
  $db,
  (int)$_SESSION['official_id'],
  (string)($_SESSION['official_name'] ?? ''),
  'Sent announcement SMS broadcast',
  'announcement',
  $title,
  "Sent: {$sent}; Skipped: {$skipped}; Failed: {$failed}"
);

json_success([
  'message' => 'Announcement SMS broadcast completed.',
  'sent' => $sent,
  'skipped' => $skipped,
  'failed' => $failed
]);
?>

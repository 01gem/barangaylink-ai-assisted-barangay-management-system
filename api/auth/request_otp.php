<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

require_once __DIR__ . '/../common.php';

require_post();
$input = read_json_input();
$accountType = trim((string)($input['account_type'] ?? ''));
$username = trim((string)($input['username'] ?? ''));

if (!in_array($accountType, ['resident', 'official'], true) || $username === '') {
  json_error('Account type and username are required.');
}

$db = get_db();
$table = $accountType === 'resident' ? 'residents' : 'barangay_officials';
$stmt = $db->prepare("SELECT id, contact FROM {$table} WHERE username = ? LIMIT 1");
if (!$stmt) json_error('Unable to process password recovery.', 500);
$stmt->bind_param('s', $username);
$rows = db_query_all($stmt);
$stmt->close();

$genericMessage = 'If that username exists and has a contact number, a reset code has been sent.';
if (count($rows) === 0 || trim((string)($rows[0]['contact'] ?? '')) === '') {
  json_success(['message' => $genericMessage]);
}

$accountId = (int)$rows[0]['id'];
$contact = trim((string)$rows[0]['contact']);
$rateStmt = $db->prepare('SELECT id FROM otp_codes WHERE account_type = ? AND account_id = ? AND used = 0 AND expires_at > NOW() AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND) ORDER BY created_at DESC LIMIT 1');
if (!$rateStmt) json_error('Unable to process password recovery.', 500);
$rateStmt->bind_param('si', $accountType, $accountId);
$rateRows = db_query_all($rateStmt);
$rateStmt->close();
if (count($rateRows) > 0) {
  json_success(['message' => 'A reset code was recently sent. Please wait at least 60 seconds before requesting another.']);
}
$code = (string)random_int(100000, 999999);
$code = (string)random_int(100000, 999999);
$insert = $db->prepare('INSERT INTO otp_codes (account_type, account_id, code, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL 10 MINUTE)');
if (!$insert) json_error('Unable to create password reset code.', 500);
$insert->bind_param('sis', $accountType, $accountId, $code);
if (!$insert->execute()) json_error('Unable to create password reset code.', 500);
$insert->close();

$apiKey = getenv('HTTPSMS_API_KEY') ?: '';
$fromNumber = getenv('HTTPSMS_FROM_NUMBER') ?: '';
$simSlot = getenv('HTTPSMS_SIM_SLOT') ?: 'SIM1';
$payload = json_encode([
  'from' => $fromNumber,
  'to' => $contact,
  'content' => "Your BarangayLink password reset code is {$code}. It expires in 10 minutes.",
  'sim' => $simSlot
]);
if ($payload === false || !function_exists('curl_init')) {
  json_error('Password reset SMS could not be sent.', 500);
}
$ch = curl_init('https://api.httpsms.com/v1/messages/send');
if ($ch === false) json_error('Password reset SMS could not be sent.', 500);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'x-api-key: ' . $apiKey, 'Content-Length: ' . strlen($payload)],
  CURLOPT_POSTFIELDS => $payload,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_TIMEOUT => 25,
]);
$response = curl_exec($ch);
$curlError = $response === false ? curl_error($ch) : '';
$httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($response === false || !in_array($httpStatus, [200, 201, 202], true)) {
  error_log('Password reset SMS failed: ' . ($curlError !== '' ? $curlError : "httpSMS HTTP {$httpStatus}"));
  json_error('Password reset SMS could not be sent. Please try again later.', 500);
}

json_success(['message' => 'A password reset code has been sent to your registered contact number.']);
?>

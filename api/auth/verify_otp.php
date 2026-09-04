<?php
require_once __DIR__ . '/../common.php';

require_post();
$input = read_json_input();
$accountType = trim((string)($input['account_type'] ?? ''));
$username = trim((string)($input['username'] ?? ''));
$code = trim((string)($input['code'] ?? ''));

if (!in_array($accountType, ['resident', 'official'], true) || $username === '' || !preg_match('/^\d{6}$/', $code)) {
  json_error('Invalid or expired code.');
}

$db = get_db();
$table = $accountType === 'resident' ? 'residents' : 'barangay_officials';
$accountStmt = $db->prepare("SELECT id FROM {$table} WHERE username = ? LIMIT 1");
if (!$accountStmt) json_error('Unable to verify code.', 500);
$accountStmt->bind_param('s', $username);
$accountRows = db_query_all($accountStmt);
$accountStmt->close();
if (count($accountRows) === 0) json_error('Invalid or expired code.');
$accountId = (int)$accountRows[0]['id'];

$otpStmt = $db->prepare('SELECT id FROM otp_codes WHERE account_type = ? AND account_id = ? AND code = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1');
if (!$otpStmt) json_error('Unable to verify code.', 500);
$otpStmt->bind_param('sis', $accountType, $accountId, $code);
$otpRows = db_query_all($otpStmt);
$otpStmt->close();
if (count($otpRows) === 0) json_error('Invalid or expired code.');

json_success(['message' => 'Code verified.']);
?>

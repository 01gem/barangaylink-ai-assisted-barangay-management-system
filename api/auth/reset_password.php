<?php
require_once __DIR__ . '/../common.php';

require_post();
$input = read_json_input();
$accountType = trim((string)($input['account_type'] ?? ''));
$username = trim((string)($input['username'] ?? ''));
$code = trim((string)($input['code'] ?? ''));
$newPassword = (string)($input['new_password'] ?? '');

if (!in_array($accountType, ['resident', 'official'], true) || $username === '' || !preg_match('/^\d{6}$/', $code) || $newPassword === '') {
  json_error('Account type, username, 6-digit code, and new password are required.');
}

$db = get_db();
$table = $accountType === 'resident' ? 'residents' : 'barangay_officials';
$accountStmt = $db->prepare("SELECT id FROM {$table} WHERE username = ? LIMIT 1");
if (!$accountStmt) json_error('Unable to process password reset.', 500);
$accountStmt->bind_param('s', $username);
$accountRows = db_query_all($accountStmt);
$accountStmt->close();
if (count($accountRows) === 0) json_error('Invalid or expired reset code.');
$accountId = (int)$accountRows[0]['id'];

$otpStmt = $db->prepare('SELECT id FROM otp_codes WHERE account_type = ? AND account_id = ? AND code = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1');
if (!$otpStmt) json_error('Unable to process password reset.', 500);
$otpStmt->bind_param('sis', $accountType, $accountId, $code);
$otpRows = db_query_all($otpStmt);
$otpStmt->close();
if (count($otpRows) === 0) json_error('Invalid or expired reset code.');

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $db->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
if (!$update) json_error('Unable to update password.', 500);
$update->bind_param('si', $hash, $accountId);
if (!$update->execute()) json_error('Unable to update password.', 500);
$update->close();

$used = $db->prepare('UPDATE otp_codes SET used = 1 WHERE id = ?');
if (!$used) json_error('Password changed, but reset code could not be closed.', 500);
$otpId = (int)$otpRows[0]['id'];
$used->bind_param('i', $otpId);
if (!$used->execute()) json_error('Password changed, but reset code could not be closed.', 500);
$used->close();

json_success(['message' => 'Your password has been reset. You can now log in.']);
?>

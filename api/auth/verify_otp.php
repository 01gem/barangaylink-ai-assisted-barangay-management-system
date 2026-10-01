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

// Fetch the latest unused, unexpired OTP for the account — do NOT match code in SQL,
// so wrong guesses are counted against the same row.
$otpStmt = $db->prepare('SELECT id, code, attempts FROM otp_codes WHERE account_type = ? AND account_id = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1');
if (!$otpStmt) json_error('Unable to verify code.', 500);
$otpStmt->bind_param('si', $accountType, $accountId);
$otpRows = db_query_all($otpStmt);
$otpStmt->close();
if (count($otpRows) === 0) json_error('Invalid or expired code.');

$otpRow = $otpRows[0];
if ((int)$otpRow['attempts'] >= 5) {
  json_error('Invalid or expired code.');
}

if (!hash_equals((string)$otpRow['code'], $code)) {
  $bump = $db->prepare('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?');
  if ($bump) {
    $otpId = (int)$otpRow['id'];
    $bump->bind_param('i', $otpId);
    $bump->execute();
    $bump->close();
  }
  json_error('Invalid or expired code.');
}

json_success(['message' => 'Code verified.']);
?>

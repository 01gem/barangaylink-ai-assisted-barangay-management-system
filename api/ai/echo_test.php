<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

session_start();
require_once __DIR__ . '/../common.php';
require_official_session();
require_post();

$input = read_json_input();
$prompt = trim((string)($input['prompt'] ?? ''));
if ($prompt === '') {
    $prompt = 'Say hello and confirm you are working correctly.';
}
if (strlen($prompt) > 2000) {
    json_error('Prompt must be 2000 characters or fewer.', 422);
}

$result = ai_chat($prompt, 'You are a diagnostic assistant confirming AI connectivity.');
if (!$result['success']) {
    json_error($result['error'] ?? 'AI request failed.', 502);
}

json_success(['reply' => (string)($result['content'] ?? '')]);

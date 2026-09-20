<?php
session_start();
require_once __DIR__ . '/../common.php';
require_official_admin();
require_post();

$input = read_json_input();
$prompt = trim($input['prompt'] ?? 'Reply with a short confirmation that the AI connection is working.');

$result = ai_chat($prompt, 'You are a diagnostic assistant confirming AI connectivity.');

if (!$result['success']) {
  json_error($result['error'], 502);
}

json_success(['content' => $result['content'], 'model' => $result['model']]);
?>

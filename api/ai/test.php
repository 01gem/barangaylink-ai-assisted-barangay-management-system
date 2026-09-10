<?php
session_start();
require_once __DIR__ . '/../common.php';
require_official_admin();
require_post();

$input = read_json_input();
$prompt = trim($input['prompt'] ?? 'Reply with a short confirmation that the Omniroute connection is working.');

$result = omniroute_chat($prompt, 'You are a diagnostic assistant confirming API connectivity.');

if (!$result['success']) {
  json_error($result['error'], 502);
}

json_success(['content' => $result['content'], 'model' => $result['model']]);
?>

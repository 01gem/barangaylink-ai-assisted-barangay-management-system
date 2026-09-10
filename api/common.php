<?php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

function json_success(array $payload = []): void {
  echo json_encode(array_merge(['success' => true], $payload));
  exit;
}

function json_error(string $message, int $status = 400): void {
  http_response_code($status);
  echo json_encode(['success' => false, 'message' => $message]);
  exit;
}

function read_json_input(): array {
  $raw = file_get_contents('php://input');
  if ($raw === false || $raw === '') return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function require_post(): void {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 405);
  }
}

function require_official_session(): void {
  if (empty($_SESSION['official_id'])) {
    json_error('Unauthorized', 401);
  }
}

function require_official_admin(): void {
  require_official_session();
  if (($_SESSION['official_role'] ?? '') !== 'admin') {
    json_error('Admin access required.', 403);
  }
}

function official_full_name(array $row): string {
  return trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
}

function db_query_all(mysqli_stmt $stmt): array {
  if (!$stmt->execute()) {
    json_error('Database query failed: ' . $stmt->error, 500);
  }
  $result = $stmt->get_result();
  return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function log_audit(
  mysqli $conn,
  ?int $officialId,
  string $officialName,
  string $action,
  string $targetType,
  ?string $targetReference,
  string $details = ''
): bool {
  $safeOfficialName = trim($officialName) !== '' ? trim($officialName) : 'Unknown Official';
  $safeAction = trim($action);
  $safeTargetType = trim($targetType);
  $safeTargetReference = $targetReference !== null ? trim($targetReference) : null;
  $safeDetails = trim($details);
  $safeOfficialId = ($officialId !== null && $officialId > 0) ? $officialId : null;

  $stmt = $conn->prepare('INSERT INTO audit_log (official_id, official_name, action, target_type, target_reference, details) VALUES (?, ?, ?, ?, ?, ?)');
  if (!$stmt) {
    error_log('Audit log prepare failed: ' . $conn->error);
    return false;
  }

  $stmt->bind_param('isssss', $safeOfficialId, $safeOfficialName, $safeAction, $safeTargetType, $safeTargetReference, $safeDetails);
  $ok = $stmt->execute();
  if (!$ok) {
    error_log('Audit log execute failed: ' . $stmt->error);
  }
  $stmt->close();
  return $ok;
}

function omniroute_chat(string $userMessage, string $systemPrompt = '', string $model = 'auto'): array {
  $apiKey = getenv('OMNIROUTE_API_KEY') ?: '';
  $baseUrl = rtrim(getenv('OMNIROUTE_BASE_URL') ?: '', '/');

  if ($apiKey === '' || $baseUrl === '') {
    return ['success' => false, 'error' => 'Omniroute is not configured (missing OMNIROUTE_API_KEY or OMNIROUTE_BASE_URL).'];
  }

  $messages = [];
  if ($systemPrompt !== '') {
    $messages[] = ['role' => 'system', 'content' => $systemPrompt];
  }
  $messages[] = ['role' => 'user', 'content' => $userMessage];

  $payload = json_encode(['model' => $model, 'messages' => $messages]);

  if (!function_exists('curl_init')) {
    return ['success' => false, 'error' => 'cURL extension is not enabled.'];
  }

  $ch = curl_init($baseUrl . '/chat/completions');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 30,
  ]);

  $response = curl_exec($ch);
  if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    return ['success' => false, 'error' => 'Omniroute request failed: ' . $error];
  }
  $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $decoded = json_decode($response, true);
  if ($httpStatus < 200 || $httpStatus >= 300 || !is_array($decoded)) {
    return ['success' => false, 'error' => 'Omniroute returned HTTP ' . $httpStatus, 'raw' => $response];
  }

  $content = $decoded['choices'][0]['message']['content'] ?? null;
  if ($content === null) {
    return ['success' => false, 'error' => 'Unexpected Omniroute response shape.', 'raw' => $decoded];
  }

  return ['success' => true, 'content' => $content, 'model' => $decoded['model'] ?? $model];
}
?>

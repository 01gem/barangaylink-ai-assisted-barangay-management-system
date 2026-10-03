<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

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

// Log the real database/exception detail server-side; never send it to the client.
function json_db_error(string $context, string $detail, int $status = 500): void {
  error_log($context . ': ' . $detail);
  json_error('Something went wrong. Please try again.', $status);
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
    json_db_error('Database query failed', $stmt->error);
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

function calculate_eligibility_score(array $resident): int {
  $score = 0;

  $employment = (string)($resident['employment_status'] ?? '');
  if ($employment === 'Unemployed') {
    $score += 30;
  } elseif ($employment === 'Self-Employed') {
    $score += 10;
  }

  $dependents = min((int)($resident['number_of_dependents'] ?? 0), 5);
  $score += $dependents * 5;

  if (!empty($resident['is_pwd'])) {
    $score += 20;
  }

  $birthdate = (string)($resident['birthdate'] ?? '');
  if ($birthdate !== '') {
    $birth = DateTime::createFromFormat('Y-m-d', $birthdate);
    if ($birth !== false) {
      $age = (int)(new DateTime())->diff($birth)->y;
      if ($age >= 60) {
        $score += 15;
      }
    }
  }

  $bracket = (string)($resident['monthly_income_bracket'] ?? '');
  if ($bracket === 'Below 5000') {
    $score += 20;
  } elseif ($bracket === '5000-10000') {
    $score += 10;
  }

  return min(max($score, 0), 100);
}

function ai_chat(
  string $userMessage,
  string $systemPrompt = '',
  string $model = 'auto',
  bool $jsonResponse = false,
  int $timeoutSeconds = 15
): array {
  $apiKey = trim((string)(getenv('GEMINI_API_KEY') ?: ''));
  $defaultModel = trim((string)(getenv('GEMINI_MODEL') ?: ''));
  if ($defaultModel === '') {
    $defaultModel = 'gemini-3.5-flash-lite';
  }
  $modelName = $model !== '' && $model !== 'auto' ? $model : $defaultModel;

  if ($apiKey === '') {
    return ['success' => false, 'error' => 'Gemini is not configured (missing GEMINI_API_KEY).'];
  }

  if (!function_exists('curl_init')) {
    return ['success' => false, 'error' => 'cURL extension is not enabled.'];
  }

  $contents = [
    ['parts' => [['text' => $userMessage]]],
  ];
  $payload = ['contents' => $contents];
  if ($systemPrompt !== '') {
    $payload['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
  }
  if ($jsonResponse) {
    $payload['generationConfig'] = ['responseMimeType' => 'application/json'];
  }

  $encodedPayload = json_encode($payload);
  if ($encodedPayload === false) {
    return ['success' => false, 'error' => 'Could not encode the Gemini request.'];
  }

  $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
    . rawurlencode($modelName)
    . ':generateContent';

  $maxAttempts = 2;
  $response = false;
  $httpStatus = 0;
  for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $retryAfterHeader = null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
      ],
      CURLOPT_POSTFIELDS => $encodedPayload,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => max(5, $timeoutSeconds),
      CURLOPT_HEADERFUNCTION => function ($ch, string $header) use (&$retryAfterHeader): int {
        if (stripos($header, 'Retry-After:') === 0) {
          $retryAfterHeader = trim(substr($header, strlen('Retry-After:')));
        }
        return strlen($header);
      },
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
      error_log('Gemini cURL error for model ' . $modelName . ': ' . curl_error($ch));
      curl_close($ch);
      return ['success' => false, 'error' => 'Gemini request failed.'];
    }

    $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpStatus !== 503 && $httpStatus !== 429) {
      break;
    }

    error_log('Gemini HTTP ' . $httpStatus . ' for model ' . $modelName . ' (attempt ' . $attempt . ' of ' . $maxAttempts . ').');
    if ($attempt < $maxAttempts) {
      $wait = 1;
      if ($httpStatus === 429) {
        $wait = 2;
        if ($retryAfterHeader !== null && is_numeric($retryAfterHeader)) {
          $wait = min(5, max(1, (int)ceil((float)$retryAfterHeader)));
        }
      }
      sleep($wait);
    }
  }

  $decoded = json_decode((string)$response, true);

  if ($httpStatus < 200 || $httpStatus >= 300) {
    return ['success' => false, 'error' => 'Gemini returned HTTP ' . $httpStatus . '.'];
  }
  if (!is_array($decoded)) {
    return ['success' => false, 'error' => 'Gemini returned an invalid response.'];
  }

  $content = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
  if (!is_string($content) || trim($content) === '') {
    return ['success' => false, 'error' => 'Gemini returned no text response.'];
  }

  return ['success' => true, 'content' => $content, 'model' => $modelName];
}

function ai_decode_json(string $content): ?array {
  $text = trim($content);
  $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;
  $decoded = json_decode($text, true);
  return is_array($decoded) ? $decoded : null;
}
?>

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
?>

<?php
require_once __DIR__ . '/../common.php';
session_start();

require_official_session();
require_post();

$input = read_json_input();
$rawPuroks = is_array($input['puroks'] ?? null) ? $input['puroks'] : [];
$puroks = [];
foreach ($rawPuroks as $purok) {
  if (!is_string($purok)) continue;
  $clean = trim($purok);
  if ($clean !== '' && mb_strlen($clean) <= 100) $puroks[] = $clean;
}
$puroks = array_values(array_unique($puroks));
if (!$puroks) {
  json_error('Select at least one Purok.');
}

$residentCount = max(0, (int)($input['resident_count'] ?? 0));
$ranking = ($input['mode'] ?? '') === 'ai' ? 'AI (Gemini)' : 'Formula score';
$situation = mb_substr(trim((string)($input['situation'] ?? '')), 0, 300);

$details = 'Puroks: ' . implode(', ', $puroks) . '; Residents: ' . $residentCount . '; Ranking: ' . $ranking;
if ($situation !== '') {
  $details .= '; Situation: ' . $situation;
}

$db = get_db();
log_audit(
  $db,
  (int)$_SESSION['official_id'],
  (string)($_SESSION['official_name'] ?? ''),
  'Generated calamity relief priority list',
  'calamity_triage',
  implode(', ', $puroks),
  $details
);

json_success(['message' => 'Triage action logged.']);
?>

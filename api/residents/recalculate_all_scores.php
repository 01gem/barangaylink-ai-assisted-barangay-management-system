<?php
require_once __DIR__ . '/../common.php';
session_start();
require_official_session();
require_post();

$db = get_db();
$select = $db->prepare(
  'SELECT id, birthdate, employment_status, number_of_dependents, is_pwd, monthly_income_bracket FROM residents'
);
if (!$select) {
  json_error('Failed to prepare resident score query.', 500);
}
$rows = db_query_all($select);
$select->close();

$stmt = $db->prepare('UPDATE residents SET eligibility_score = ? WHERE id = ?');
if (!$stmt) {
  json_error('Failed to prepare score update.', 500);
}

$updated = 0;
$db->begin_transaction();
try {
  foreach ($rows as $resident) {
    $score = calculate_eligibility_score($resident);
    $residentId = (int)$resident['id'];
    $stmt->bind_param('ii', $score, $residentId);
    if (!$stmt->execute()) {
      throw new RuntimeException($stmt->error);
    }
    $updated++;
  }
  $db->commit();
} catch (Throwable $error) {
  $db->rollback();
  $stmt->close();
  json_error('Failed to recalculate resident scores.', 500);
}
$stmt->close();

json_success(['updated' => $updated]);

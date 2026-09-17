<?php
require_once __DIR__ . '/../common.php';
session_start();
if (empty($_SESSION['official_id'])) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Unauthorized']);
	exit;
}

$db = get_db();

$sql = "SELECT id, fname, lname, address, contact, username, profile_photo, status,
  birthdate, civil_status, purok_zone, household_size, number_of_dependents,
  is_household_head, is_solo_parent, is_pwd, is_4ps_member, years_of_residency,
  educational_attainment, employment_status, occupation, monthly_income_bracket,
  skills, work_experience_years, training_certifications, work_availability,
  has_drivers_license
  FROM residents ORDER BY id DESC";
$stmt = $db->prepare($sql);
if (!$stmt) json_error('Failed to prepare residents query.', 500);
$rows = db_query_all($stmt);
$stmt->close();

json_success(['residents' => $rows]);
?>

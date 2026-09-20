<?php
require_once __DIR__ . '/../common.php';
session_start();
// Allow both staff and admin officials (401 if no official session at all)
if (empty($_SESSION['official_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_post();
$db = get_db();
$input = read_json_input();

$fname = trim((string)($input['fname'] ?? ''));
$lname = trim((string)($input['lname'] ?? ''));
$address = trim((string)($input['address'] ?? ''));
$contact = trim((string)($input['contact'] ?? ''));
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($fname === '' || $lname === '' || $address === '' || $contact === '' || $username === '' || $password === '') {
  json_error('All resident fields are required.');
}
if (!preg_match('/^[a-zA-Z0-9._]{3,50}$/', $username)) {
  json_error('Username must be 3-50 characters and contain only letters, numbers, dots, and underscores.');
}

$check = $db->prepare("SELECT id FROM residents WHERE username = ? LIMIT 1");
if (!$check) json_error('Failed to validate resident username.', 500);
$check->bind_param('s', $username);
$rows = db_query_all($check);
$check->close();
if (count($rows) > 0) {
  json_error('Resident username already exists.');
}

// ── Profiling fields (all optional) ──
$birthdate = trim((string)($input['birthdate'] ?? '')) ?: null;
$civil_status = trim((string)($input['civil_status'] ?? '')) ?: null;
$purok_zone = trim((string)($input['purok_zone'] ?? '')) ?: null;
$household_size = isset($input['household_size']) && $input['household_size'] !== '' ? (int)$input['household_size'] : 1;
$number_of_dependents = isset($input['number_of_dependents']) && $input['number_of_dependents'] !== '' ? (int)$input['number_of_dependents'] : 0;
$is_household_head = !empty($input['is_household_head']) ? 1 : 0;
$is_solo_parent = !empty($input['is_solo_parent']) ? 1 : 0;
$is_pwd = !empty($input['is_pwd']) ? 1 : 0;
$is_4ps_member = !empty($input['is_4ps_member']) ? 1 : 0;
$years_of_residency = isset($input['years_of_residency']) && $input['years_of_residency'] !== '' ? (int)$input['years_of_residency'] : 0;
$educational_attainment = trim((string)($input['educational_attainment'] ?? '')) ?: null;
$employment_status = trim((string)($input['employment_status'] ?? '')) ?: null;
$occupation = trim((string)($input['occupation'] ?? '')) ?: null;
$monthly_income_bracket = trim((string)($input['monthly_income_bracket'] ?? '')) ?: null;
$skills = trim((string)($input['skills'] ?? '')) ?: null;
$work_experience_years = isset($input['work_experience_years']) && $input['work_experience_years'] !== '' ? (int)$input['work_experience_years'] : 0;
$training_certifications = trim((string)($input['training_certifications'] ?? '')) ?: null;
$work_availability = trim((string)($input['work_availability'] ?? '')) ?: null;
$has_drivers_license = !empty($input['has_drivers_license']) ? 1 : 0;

$hash = password_hash($password, PASSWORD_DEFAULT);
$score = calculate_eligibility_score([
  'employment_status' => $employment_status,
  'number_of_dependents' => $number_of_dependents,
  'is_pwd' => $is_pwd,
  'birthdate' => $birthdate,
  'monthly_income_bracket' => $monthly_income_bracket,
]);
$insert = $db->prepare("INSERT INTO residents (fname, lname, address, contact, username, password, birthdate, civil_status, purok_zone, household_size, number_of_dependents, is_household_head, is_solo_parent, is_pwd, is_4ps_member, years_of_residency, educational_attainment, employment_status, occupation, monthly_income_bracket, skills, work_experience_years, training_certifications, work_availability, has_drivers_license, eligibility_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$insert) json_error('Failed to prepare resident insert.', 500);
$insert->bind_param('sssssssssiiiiiiisssssissii',
  $fname, $lname, $address, $contact, $username, $hash,
  $birthdate, $civil_status, $purok_zone, $household_size, $number_of_dependents,
  $is_household_head, $is_solo_parent, $is_pwd, $is_4ps_member, $years_of_residency,
  $educational_attainment, $employment_status, $occupation, $monthly_income_bracket,
  $skills, $work_experience_years, $training_certifications, $work_availability, $has_drivers_license,
  $score
);
if (!$insert->execute()) {
  json_error('Failed to create resident: ' . $insert->error, 500);
}
$residentId = (int)$insert->insert_id;
$insert->close();

log_audit(
  $db,
  isset($_SESSION['official_id']) ? (int)$_SESSION['official_id'] : null,
  (string)($_SESSION['official_name'] ?? ''),
  'Created resident',
  'resident',
  "{$fname} {$lname} (ID: {$residentId})"
);

json_success(['message' => 'Resident created successfully.']);
?>

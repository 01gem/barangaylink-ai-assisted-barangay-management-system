<?php
require_once __DIR__ . '/../common.php';

$db = get_db();
$sql = 'SELECT id, service_name, category, contact_number, address, operating_hours, description, created_at FROM local_services ORDER BY category ASC, service_name ASC';
$stmt = $db->prepare($sql);
if (!$stmt) json_error('Failed to prepare services query.', 500);
$rows = db_query_all($stmt);
$stmt->close();

json_success(['services' => $rows]);
?>

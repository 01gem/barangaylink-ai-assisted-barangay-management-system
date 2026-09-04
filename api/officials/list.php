<?php
require_once __DIR__ . '/../common.php';
session_start();
require_official_admin();

$db = get_db();
$stmt = $db->prepare('SELECT id, fname, lname, username, contact, address, position, role, status FROM barangay_officials ORDER BY id ASC');
if (!$stmt) json_error('Failed to prepare officials query.', 500);
$rows = db_query_all($stmt);
$stmt->close();

json_success(['officials' => $rows]);
?>

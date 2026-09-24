<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

require_once __DIR__ . '/../common.php';
session_start();

if (empty($_SESSION['resident_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
}

$db = get_db();
$residentId = (int)$_SESSION['resident_id'];

$sql = "SELECT id, title, body, is_read, created_at
        FROM notifications
        WHERE resident_id = ?
        ORDER BY id DESC";
$stmt = $db->prepare($sql);
if (!$stmt) json_error('Failed to prepare notifications query.', 500);
$stmt->bind_param('i', $residentId);
$rows = db_query_all($stmt);
$stmt->close();

json_success(['notifications' => $rows]);
?>

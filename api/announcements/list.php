<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

require_once __DIR__ . '/../common.php';

$db = get_db();
$officialRequest = (($_SERVER['HTTP_X_PORTAL_SOURCE'] ?? '') === 'official');
$sql = 'SELECT id, title, content, expires_at, is_pinned, created_at';
$sql .= $officialRequest
  ? ', (expires_at IS NOT NULL AND expires_at <= NOW()) AS is_expired FROM announcements'
  : ' FROM announcements WHERE expires_at IS NULL OR expires_at > NOW()';
$sql .= ' ORDER BY is_pinned DESC, created_at DESC';
$stmt = $db->prepare($sql);
if (!$stmt) json_error('Failed to prepare announcements query.', 500);
$rows = db_query_all($stmt);
$stmt->close();

json_success(['announcements' => $rows]);
?>

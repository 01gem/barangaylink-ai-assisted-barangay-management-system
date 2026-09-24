<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

require_once __DIR__ . '/../common.php';

$db = get_db();
$residentResult = $db->query('SELECT COUNT(*) AS total FROM residents');
$serviceResult = $db->query('SELECT COUNT(*) AS total FROM local_services');
if (!$residentResult || !$serviceResult) {
  json_error('Unable to load landing page statistics.', 500);
}

$residentRow = $residentResult->fetch_assoc();
$serviceRow = $serviceResult->fetch_assoc();

// Placeholder/sample value: not derived from request records.
$requestFulfillmentRate = 92;
// Placeholder/sample value: not derived from request records.
$avgResponseTimeMinutes = 15;

json_success([
  'registered_residents' => (int)$residentRow['total'],
  'verified_local_services' => (int)$serviceRow['total'],
  'request_fulfillment_rate' => $requestFulfillmentRate,
  'avg_response_time_minutes' => $avgResponseTimeMinutes
]);
?>

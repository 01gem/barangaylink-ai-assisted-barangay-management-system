<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

// Session cookie hardening. Must run before any session_start().
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');

function load_env($filePath = __DIR__ . '/.env') {
  if (!file_exists($filePath)) {
    return;
  }

  $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (!$lines) {
    return;
  }

  foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') {
      continue;
    }

    if (strpos($line, '=') === false) {
      continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);

    if (!empty($key) && !isset($_ENV[$key]) && !isset($_SERVER[$key])) {
      putenv("$key=$value");
      $_ENV[$key] = $value;
    }
  }
}

load_env(__DIR__ . '/.env');
load_env(__DIR__ . '/.env.sms');
load_env();

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'barangay_system';

function get_db() {
  global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
  static $db = null;
  if ($db !== null) {
    return $db;
  }
  // PHP >= 8.1 throws mysqli_sql_exception by default; this codebase checks return
  // values, so keep the return-false behavior.
  mysqli_report(MYSQLI_REPORT_OFF);
  $db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  if ($db->connect_error) {
    error_log('Database connection failed: ' . $db->connect_error);
    die('Database connection failed.');
  }
  $db->set_charset('utf8mb4');
  return $db;
}
?>

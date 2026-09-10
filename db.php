<?php
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
  $db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  if ($db->connect_error) {
    die('Database connection failed: ' . $db->connect_error);
  }
  $db->set_charset('utf8mb4');
  return $db;
}
?>

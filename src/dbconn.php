<?php
$envFile = __DIR__ . '/../.env';
if (is_readable($envFile)) {
  foreach (parse_ini_file($envFile) as $key => $value) {
    if (getenv($key) === false) {
      putenv("$key=$value");
    }
  }
}

$dbHost = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
$dbName = getenv('DB_NAME');

if ($dbHost === false || $dbUser === false || $dbPass === false || $dbName === false) {
  http_response_code(500);
  die("Database configuration missing.");
}

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
  http_response_code(500);
  die("Database connection failed.");
}

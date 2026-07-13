<?php
$envFile = __DIR__ . '/../.env';
if (is_readable($envFile)) {
  foreach (parse_ini_file($envFile) as $key => $value) {
    if (getenv($key) === false) {
      putenv("$key=$value");
    }
  }
}

$conn = new mysqli(
  getenv('DB_HOST') ?: 'localhost',
  getenv('DB_USER') ?: 'root',
  getenv('DB_PASS') ?: '',
  getenv('DB_NAME') ?: 'coffeebuddydb'
);
if ($conn->connect_error) {
  http_response_code(500);
  die("Database connection failed.");
}

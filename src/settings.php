<?php

function get_setting(mysqli $conn, string $name): ?string
{
  $stmt = $conn->prepare("SELECT value FROM settings WHERE name = ?");
  if ($stmt === false) {
    return null;
  }
  $stmt->bind_param("s", $name);
  $stmt->execute();
  $stmt->bind_result($value);
  $found = $stmt->fetch();
  $stmt->close();
  return $found ? $value : null;
}

function set_setting(mysqli $conn, string $name, string $value): void
{
  $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE value = VALUES(value)");
  $stmt->bind_param("ss", $name, $value);
  $stmt->execute();
  $stmt->close();
}

function store_status(mysqli $conn): array
{
  static $status = null;
  if ($status !== null) {
    return $status;
  }

  $status = [
    'open' => get_setting($conn, 'store_open') !== '0',
    'message' => get_setting($conn, 'closed_message') ?? '',
  ];

  return $status;
}

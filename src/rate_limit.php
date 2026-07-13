<?php

const RATE_LIMIT_MAX_ATTEMPTS = 5;
const RATE_LIMIT_WINDOW_SECONDS = 900;

function rate_limit_identifier(string $username): string
{
  return mb_strtolower($username);
}

/** Seconds remaining until the identifier can try again, or null if not locked. */
function rate_limit_check(mysqli $conn, string $identifier): ?int
{
  $stmt = $conn->prepare("SELECT locked_until FROM login_attempts WHERE identifier = ?");
  $stmt->bind_param("s", $identifier);
  $stmt->execute();
  $stmt->bind_result($lockedUntil);
  $found = $stmt->fetch();
  $stmt->close();

  if (!$found || $lockedUntil === null) {
    return null;
  }

  $remaining = strtotime($lockedUntil) - time();
  return $remaining > 0 ? $remaining : null;
}

function rate_limit_record_failure(mysqli $conn, string $identifier): void
{
  $stmt = $conn->prepare("SELECT attempts, first_attempt_at FROM login_attempts WHERE identifier = ?");
  $stmt->bind_param("s", $identifier);
  $stmt->execute();
  $stmt->bind_result($attempts, $firstAttemptAt);
  $found = $stmt->fetch();
  $stmt->close();

  $windowExpired = $found && (time() - strtotime($firstAttemptAt)) > RATE_LIMIT_WINDOW_SECONDS;

  if (!$found || $windowExpired) {
    $stmt = $conn->prepare(
      "INSERT INTO login_attempts (identifier, attempts, first_attempt_at, locked_until)
       VALUES (?, 1, NOW(), NULL)
       ON DUPLICATE KEY UPDATE attempts = 1, first_attempt_at = NOW(), locked_until = NULL"
    );
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $stmt->close();
    return;
  }

  $attempts++;
  $lockedUntil = $attempts >= RATE_LIMIT_MAX_ATTEMPTS
    ? date('Y-m-d H:i:s', time() + RATE_LIMIT_WINDOW_SECONDS)
    : null;

  $stmt = $conn->prepare("UPDATE login_attempts SET attempts = ?, locked_until = ? WHERE identifier = ?");
  $stmt->bind_param("iss", $attempts, $lockedUntil, $identifier);
  $stmt->execute();
  $stmt->close();
}

function rate_limit_clear(mysqli $conn, string $identifier): void
{
  $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ?");
  $stmt->bind_param("s", $identifier);
  $stmt->execute();
  $stmt->close();
}

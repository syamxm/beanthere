<?php

// login_attempts.identifier holds one of two composite keys:
//   "user:<lowercased username>|<ip>"  — locks one account from one IP
//   "ip:<scope>|<ip>"                  — locks one IP across all usernames
const RATE_LIMIT_MAX_ATTEMPTS = 5;
const RATE_LIMIT_IP_MAX_ATTEMPTS = 20;
const RATE_LIMIT_WINDOW_SECONDS = 900;

function rate_limit_client_ip(): string
{
  return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function rate_limit_identifier(string $username): string
{
  return 'user:' . mb_strtolower(trim($username)) . '|' . rate_limit_client_ip();
}

/** Throttles one IP across a whole action ("login", "register", "chat"). */
function rate_limit_ip_identifier(string $scope): string
{
  return 'ip:' . $scope . '|' . rate_limit_client_ip();
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

function rate_limit_record(
  mysqli $conn,
  string $identifier,
  int $maxAttempts = RATE_LIMIT_MAX_ATTEMPTS,
  int $windowSeconds = RATE_LIMIT_WINDOW_SECONDS
): void {
  $stmt = $conn->prepare("SELECT attempts, first_attempt_at FROM login_attempts WHERE identifier = ?");
  $stmt->bind_param("s", $identifier);
  $stmt->execute();
  $stmt->bind_result($attempts, $firstAttemptAt);
  $found = $stmt->fetch();
  $stmt->close();

  $windowExpired = $found && (time() - strtotime($firstAttemptAt)) > $windowSeconds;

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
  $lockedUntil = $attempts >= $maxAttempts
    ? date('Y-m-d H:i:s', time() + $windowSeconds)
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

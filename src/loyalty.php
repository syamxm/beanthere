<?php

const LOYALTY_TIERS = [
  ['name' => 'gold', 'min' => 1500, 'multiplier' => 1.5],
  ['name' => 'silver', 'min' => 500, 'multiplier' => 1.25],
  ['name' => 'bronze', 'min' => 0, 'multiplier' => 1.0],
];

function get_tier(int $lifetimePoints): array
{
  $matched = count(LOYALTY_TIERS) - 1;
  foreach (LOYALTY_TIERS as $i => $tier) {
    if ($lifetimePoints >= $tier['min']) {
      $matched = $i;
      break;
    }
  }

  $tier = LOYALTY_TIERS[$matched];
  $tier['next'] = $matched > 0 ? LOYALTY_TIERS[$matched - 1] : null;
  return $tier;
}

function award_points(mysqli $conn, int $userID, int $points, string $reason): void
{
  $stmt = $conn->prepare("INSERT INTO loyalty_ledger (userID, points, reason) VALUES (?, ?, ?)");
  $stmt->bind_param("iis", $userID, $points, $reason);
  $stmt->execute();
  $stmt->close();

  if ($points > 0) {
    $stmt = $conn->prepare("UPDATE users SET lifetime_points = lifetime_points + ? WHERE userID = ?");
    $stmt->bind_param("ii", $points, $userID);
    $stmt->execute();
    $stmt->close();
  }
}

function get_balance(mysqli $conn, int $userID): int
{
  $stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) FROM loyalty_ledger WHERE userID = ?");
  $stmt->bind_param("i", $userID);
  $stmt->execute();
  $stmt->bind_result($balance);
  $stmt->fetch();
  $stmt->close();
  return (int)$balance;
}

function get_balance_for_nav(string $username): ?int
{
  $conn = @new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
  if ($conn->connect_error) {
    return null;
  }
  $stmt = $conn->prepare("SELECT COALESCE(SUM(l.points), 0)
                          FROM users u LEFT JOIN loyalty_ledger l ON l.userID = u.userID
                          WHERE u.username = ?");
  if ($stmt === false) {
    $conn->close();
    return null;
  }
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->bind_result($balance);
  $found = $stmt->fetch();
  $stmt->close();
  $conn->close();
  return $found ? (int)$balance : null;
}

<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header('Location: user_login.php?return=rewards.php');
  exit();
}

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/loyalty.php';

$username = $_SESSION['current_user'];

$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

$flashSuccess = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $rewardID = (int)($_POST['reward_id'] ?? 0);

  $stmt = $conn->prepare("SELECT r.name, r.points_cost, r.voucherID
                          FROM rewards r
                          WHERE r.id = ? AND r.active = 1");
  $stmt->bind_param("i", $rewardID);
  $stmt->execute();
  $stmt->bind_result($rewardName, $pointsCost, $voucherID);
  $rewardFound = $stmt->fetch();
  $stmt->close();

  if (!$rewardFound) {
    $flashError = "That reward is not available.";
  } else {
    $stmt = $conn->prepare("SELECT membershipID FROM membership WHERE userID = ? AND status = 'active'");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($membershipID);
    $isMember = $stmt->fetch();
    $stmt->close();

    if (!$isMember) {
      $flashError = "Rewards are redeemed as membership vouchers — join the membership (it's free) to redeem.";
    } else {
      $conn->begin_transaction();
      try {
        $lock = $conn->prepare("SELECT lifetime_points FROM users WHERE userID = ? FOR UPDATE");
        $lock->bind_param("i", $userID);
        $lock->execute();
        $lock->bind_result($lifetimePoints);
        $lock->fetch();
        $lock->close();

        $balance = get_balance($conn, $userID);
        if ($balance < $pointsCost) {
          $conn->rollback();
          $flashError = "Not enough points for that reward.";
        } else {
          $deduction = -$pointsCost;
          award_points($conn, $userID, $deduction, "Redeemed: $rewardName");

          $grant = $conn->prepare("INSERT INTO member_vouchers (membershipID, voucherID, assigned_at, used) VALUES (?, ?, NOW(), 0)");
          $grant->bind_param("ii", $membershipID, $voucherID);
          $grant->execute();
          $grant->close();

          $conn->commit();
          $flashSuccess = "Redeemed $rewardName — it's in your vouchers, ready for checkout.";
        }
      } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $flashError = "Something went wrong — no points were taken. Please try again.";
      }
    }
  }
}

$stmt = $conn->prepare("SELECT lifetime_points FROM users WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($lifetimePoints);
$stmt->fetch();
$stmt->close();

$balance = get_balance($conn, $userID);
$tier = get_tier((int)$lifetimePoints);

$hasMembership = false;
$stmt = $conn->prepare("SELECT membershipID FROM membership WHERE userID = ? AND status = 'active'");
$stmt->bind_param("i", $userID);
$stmt->execute();
$hasMembership = (bool)$stmt->fetch();
$stmt->close();

$rewards = [];
$stmt = $conn->prepare("SELECT r.id, r.name, r.points_cost, v.discount_value
                        FROM rewards r JOIN vouchers v ON r.voucherID = v.voucherID
                        WHERE r.active = 1 ORDER BY r.points_cost");
$stmt->execute();
$rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

if ($tier['next'] !== null) {
  $span = $tier['next']['min'] - $tier['min'];
  $progressPercent = (int)round(min(1, ($lifetimePoints - $tier['min']) / $span) * 100);
} else {
  $progressPercent = 100;
}

$pageTitle = 'Rewards - Bean There';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow max-w-4xl mx-auto w-full px-4 py-10">
    <h1 class="text-3xl font-bold mb-2">Rewards</h1>
    <p class="text-foam text-sm mb-8">Earn 1 point per RM1 spent. Regulars level up and earn faster.</p>

    <?php if ($flashSuccess): ?>
      <p class="text-green-400 text-sm mb-4"><?= htmlspecialchars($flashSuccess) ?></p>
    <?php elseif ($flashError): ?>
      <p class="text-red-400 text-sm mb-4"><?= htmlspecialchars($flashError) ?></p>
    <?php endif; ?>

    <div class="bg-roast border border-bean rounded-2xl p-6 mb-8">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div>
          <p class="text-foam text-sm">Your balance</p>
          <p class="text-3xl font-bold text-caramel"><?= number_format($balance) ?> <span class="text-base font-normal text-foam">points</span></p>
        </div>
        <span class="text-sm font-semibold px-4 py-1.5 rounded-full bg-caramel text-espresso uppercase tracking-widest">
          <i class="fa-solid fa-medal mr-1"></i><?= htmlspecialchars($tier['name']) ?> · <?= rtrim(rtrim(number_format($tier['multiplier'], 2), '0'), '.') ?>x points
        </span>
      </div>
      <?php if ($tier['next'] !== null): ?>
        <div class="w-full bg-bean rounded-full h-2.5 mb-2">
          <div class="bg-caramel h-2.5 rounded-full" style="width: <?= $progressPercent ?>%"></div>
        </div>
        <p class="text-foam text-xs"><?= number_format(max(0, $tier['next']['min'] - $lifetimePoints)) ?> more lifetime points to <?= htmlspecialchars($tier['next']['name']) ?> (<?= rtrim(rtrim(number_format($tier['next']['multiplier'], 2), '0'), '.') ?>x points)</p>
      <?php else: ?>
        <p class="text-foam text-xs">You're at the top tier — thanks for being a regular.</p>
      <?php endif; ?>
    </div>

    <?php if (!$hasMembership): ?>
      <div class="bg-roast border border-caramel/40 rounded-xl px-4 py-3 mb-6 text-sm text-foam">
        <i class="fa-solid fa-circle-info text-caramel mr-1"></i>
        Rewards are redeemed as membership vouchers.
        <a href="membership.php" class="text-caramel underline hover:text-crema">Join the membership (it's free)</a> to redeem.
      </div>
    <?php endif; ?>

    <h2 class="text-xl font-semibold text-caramel tracking-widest mb-6">REDEEM POINTS</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($rewards as $reward): ?>
        <div class="h-full flex flex-col bg-roast border border-bean rounded-2xl p-6 hover:border-caramel transition">
          <i class="fa-solid fa-ticket text-caramel text-3xl mb-4"></i>
          <h3 class="font-semibold text-lg mb-1"><?= htmlspecialchars($reward['name']) ?></h3>
          <p class="text-foam text-sm mb-4 grow"><?= number_format($reward['discount_value'], 0) ?>% off voucher, applied at checkout like any other.</p>
          <p class="text-caramel font-semibold mb-4"><?= number_format($reward['points_cost']) ?> points</p>
          <?php if (!$hasMembership): ?>
            <a href="membership.php" class="w-full text-center border border-caramel text-caramel font-semibold py-2 rounded-lg hover:bg-caramel hover:text-espresso transition">Join membership to redeem</a>
          <?php elseif ($balance >= $reward['points_cost']): ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="reward_id" value="<?= (int)$reward['id'] ?>">
              <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2 rounded-lg hover:bg-crema transition">Redeem</button>
            </form>
          <?php else: ?>
            <button type="button" disabled class="w-full bg-bean text-foam font-semibold py-2 rounded-lg cursor-not-allowed">Not enough points</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>

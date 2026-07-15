<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header("Location: user_login.php?return=cart.php");
  exit;
}

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/payment.php';

$username = $_SESSION['current_user'];
$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

$selectedIDs = array_values(array_filter((array)($_POST['selected_ids'] ?? []), 'is_numeric'));
$deliveryMethod = ($_POST['delivery_method'] ?? '') === 'Delivery' ? 'Delivery' : 'Pickup';
$postedVoucherID = (isset($_POST['memberVoucherID']) && is_numeric($_POST['memberVoucherID']))
  ? intval($_POST['memberVoucherID'])
  : null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($selectedIDs)) {
  header("Location: cart.php");
  exit;
}

if (!store_status($conn)['open']) {
  $_SESSION['message'] = "We're closed right now — checkout is paused until we reopen.";
  $_SESSION['success'] = false;
  header("Location: cart.php");
  exit;
}

$deliveryCharge = $deliveryMethod === 'Delivery' ? 3.00 : 0.00;

// Look up the chosen voucher (read-only here; re-locked inside the transaction).
$discountPercent = 0;
$voucherValid = false;
if ($postedVoucherID !== null) {
  $vStmt = $conn->prepare("
    SELECT v.discount_value
    FROM member_vouchers mv
    JOIN vouchers v ON mv.voucherID = v.voucherID
    JOIN membership m ON mv.membershipID = m.membershipID
    WHERE mv.memberVoucherID = ? AND m.userID = ? AND mv.used = 0
      AND v.status = 'active' AND CURDATE() BETWEEN v.valid_from AND v.valid_until
  ");
  $vStmt->bind_param("ii", $postedVoucherID, $userID);
  $vStmt->execute();
  $vStmt->bind_result($discountPercent);
  $voucherValid = (bool)$vStmt->fetch();
  $vStmt->close();
  if (!$voucherValid) {
    $discountPercent = 0;
    $postedVoucherID = null;
  }
}

$placeholders = implode(',', array_fill(0, count($selectedIDs), '?'));
$types = str_repeat('i', count($selectedIDs) + 1);
// nosemgrep: php.lang.security.injection.tainted-sql-string.tainted-sql-string -- $placeholders is only "?" marks; all values are bound below
$sql = "SELECT * FROM cart WHERE userID = ? AND cartID IN ($placeholders)";
$cartStmt = $conn->prepare($sql);
$cartStmt->bind_param($types, ...array_merge([$userID], $selectedIDs));
$cartStmt->execute();
$cartRows = $cartStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cartStmt->close();

if (empty($cartRows)) {
  header("Location: cart.php");
  exit;
}

$subtotal = 0.0;
foreach ($cartRows as $row) {
  $subtotal += round($row['total'] * (1 - $discountPercent / 100), 2);
}
$subtotal = round($subtotal, 2);
$orderTotal = round($subtotal + $deliveryCharge, 2);

// ---- Confirm step: create the Awaiting Payment order and hand off to gateway.
if (isset($_POST['proceed'])) {
  csrf_verify();

  $checkoutID = bin2hex(random_bytes(6));
  $conn->begin_transaction();
  try {
    $memberVoucherID = null;
    $lockedDiscount = 0;
    if ($postedVoucherID !== null) {
      $lockStmt = $conn->prepare("
        SELECT mv.memberVoucherID, v.discount_value
        FROM member_vouchers mv
        JOIN vouchers v ON mv.voucherID = v.voucherID
        JOIN membership m ON mv.membershipID = m.membershipID
        WHERE mv.memberVoucherID = ? AND m.userID = ? AND mv.used = 0
          AND v.status = 'active' AND CURDATE() BETWEEN v.valid_from AND v.valid_until
        FOR UPDATE
      ");
      $lockStmt->bind_param("ii", $postedVoucherID, $userID);
      $lockStmt->execute();
      $lockStmt->bind_result($memberVoucherID, $lockedDiscount);
      if (!$lockStmt->fetch()) {
        $memberVoucherID = null;
        $lockedDiscount = 0;
      }
      $lockStmt->close();
    }
    $discountPercent = $lockedDiscount;

    $awaiting = PAYMENT_AWAITING;
    foreach ($cartRows as $row) {
      $rowTotal = round($row['total'] * (1 - $discountPercent / 100), 2);

      $insert = $conn->prepare("INSERT INTO orders (
        checkoutID, userID, itemID, name, drinkType, roastLevel, caffeineLevel, milkType,
        sugarLevel, toppings, syrups, delivery, total, delivery_fee, member_voucher_id,
        discount_percent, qty, orderStatus, orderTime, lastStatusUpdate
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
      $insert->bind_param(
        "siisssssssssddidis",
        $checkoutID,
        $userID,
        $row['itemID'],
        $row['name'],
        $row['drinkType'],
        $row['roastLevel'],
        $row['caffeineLevel'],
        $row['milkType'],
        $row['sugarLevel'],
        $row['toppings'],
        $row['syrups'],
        $deliveryMethod,
        $rowTotal,
        $deliveryCharge,
        $memberVoucherID,
        $discountPercent,
        $row['qty'],
        $awaiting
      );
      $insert->execute();
      $insert->close();

      $delete = $conn->prepare("DELETE FROM cart WHERE cartID = ?");
      $delete->bind_param("i", $row['cartID']);
      $delete->execute();
      $delete->close();

      $update = $conn->prepare("UPDATE menu_items SET stock = stock - ? WHERE id = ? AND stock >= ?");
      $update->bind_param("iii", $row['qty'], $row['itemID'], $row['qty']);
      $update->execute();
      $stockTaken = $update->affected_rows === 1;
      $update->close();

      if (!$stockTaken) {
        throw new RuntimeException("Sorry — " . $row['name'] . " just sold out. Nothing was charged.");
      }
    }

    if ($memberVoucherID !== null) {
      $useVoucher = $conn->prepare("UPDATE member_vouchers SET used = 1 WHERE memberVoucherID = ? AND used = 0");
      $useVoucher->bind_param("i", $memberVoucherID);
      $useVoucher->execute();
      $consumed = $useVoucher->affected_rows === 1;
      $useVoucher->close();
      if (!$consumed) {
        throw new RuntimeException("That voucher was already used. Nothing was charged — try again.");
      }
    }

    $conn->commit();
  } catch (RuntimeException $e) {
    $conn->rollback();
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['success'] = false;
    header("Location: cart.php");
    exit;
  } catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $_SESSION['message'] = "Checkout could not be completed — nothing was charged. Please try again.";
    $_SESSION['success'] = false;
    header("Location: cart.php");
    exit;
  }

  $amount = number_format($orderTotal, 2, '.', '');
  $sig = payment_sign($checkoutID, $amount);
  $conn->close();
  header("Location: gateway/pay.php?ref=$checkoutID&amt=$amount&sig=$sig");
  exit;
}

$pageTitle = 'Review order - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow max-w-xl mx-auto w-full px-4 py-10">
    <a href="cart.php" class="text-foam hover:text-caramel text-sm mb-6 inline-block">
      <i class="fa-solid fa-arrow-left mr-1"></i> Back to cart
    </a>
    <h1 class="text-2xl font-bold mb-2">Review your order</h1>
    <p class="inline-flex items-center gap-1.5 text-xs text-foam bg-bean/40 border border-bean rounded-full px-3 py-1 mb-6">
      <i class="fa-solid fa-flask text-caramel"></i> Demo — you'll be sent to a simulated payment page. No real money moves.
    </p>

    <div class="bg-roast border border-bean rounded-2xl p-6">
      <div class="flex flex-col gap-2 text-sm mb-4">
        <?php foreach ($cartRows as $row):
          $lineDiscounted = round($row['total'] * (1 - $discountPercent / 100), 2); ?>
          <div class="flex justify-between">
            <span><?= htmlspecialchars($row['name']) ?> <span class="text-foam">×<?= htmlspecialchars((string)(int)$row['qty']) ?></span></span>
            <span class="text-foam">RM<?= htmlspecialchars(number_format($lineDiscounted, 2)) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="border-t border-bean pt-4 flex flex-col gap-1.5 text-sm">
        <?php if ($discountPercent > 0): ?>
          <div class="flex justify-between text-foam"><span>Voucher discount</span><span><?= htmlspecialchars(number_format($discountPercent, 0)) ?>% applied</span></div>
        <?php endif; ?>
        <div class="flex justify-between text-foam"><span><?= htmlspecialchars($deliveryMethod) ?></span><span>RM<?= htmlspecialchars(number_format($deliveryCharge, 2)) ?></span></div>
        <div class="flex justify-between font-semibold text-base mt-1"><span>Total</span><span class="text-caramel">RM<?= htmlspecialchars(number_format($orderTotal, 2)) ?></span></div>
      </div>

      <form method="post" action="paymentMethod.php" class="mt-6">
        <?= csrf_field() ?>
        <input type="hidden" name="proceed" value="1">
        <input type="hidden" name="delivery_method" value="<?= htmlspecialchars($deliveryMethod) ?>">
        <?php if ($postedVoucherID !== null): ?>
          <input type="hidden" name="memberVoucherID" value="<?= htmlspecialchars((string)(int)$postedVoucherID) ?>">
        <?php endif; ?>
        <?php foreach ($selectedIDs as $id): ?>
          <input type="hidden" name="selected_ids[]" value="<?= htmlspecialchars((string)(int)$id) ?>">
        <?php endforeach; ?>
        <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-3 rounded-lg hover:bg-crema transition">
          Continue to payment · RM<?= htmlspecialchars(number_format($orderTotal, 2)) ?>
        </button>
      </form>
    </div>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>

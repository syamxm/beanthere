<?php

require_once __DIR__ . '/loyalty.php';

const PAYMENT_AWAITING = 'Awaiting Payment';
const PAYMENT_FAILED = 'Payment Failed';
const PAYMENT_SUCCESS_STATUS = 'Order Received';
const PAYMENT_EXPIRY_MINUTES = 15;

function payment_secret(): string
{
  $secret = getenv('PAYMENT_SECRET');
  return ($secret === false || $secret === '') ? 'insecure-demo-secret-change-me' : $secret;
}

/** Signs the two things the gateway must not be able to tamper with. */
function payment_sign(string $checkoutID, string $amount): string
{
  return hash_hmac('sha256', $checkoutID . '|' . $amount, payment_secret());
}

function payment_verify(string $checkoutID, string $amount, string $signature): bool
{
  return hash_equals(payment_sign($checkoutID, $amount), $signature);
}

/** Amount owed for a checkout: discounted item totals plus the one delivery fee. */
function payment_group_amount(mysqli $conn, string $checkoutID): ?string
{
  $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0), COALESCE(MAX(delivery_fee), 0), COUNT(*)
                          FROM orders WHERE checkoutID = ?");
  $stmt->bind_param("s", $checkoutID);
  $stmt->execute();
  $stmt->bind_result($itemsTotal, $deliveryFee, $rowCount);
  $stmt->fetch();
  $stmt->close();

  if ((int)$rowCount === 0) {
    return null;
  }
  return number_format((float)$itemsTotal + (float)$deliveryFee, 2, '.', '');
}

function payment_group_status(mysqli $conn, string $checkoutID): ?string
{
  $stmt = $conn->prepare("SELECT orderStatus FROM orders WHERE checkoutID = ? LIMIT 1");
  $stmt->bind_param("s", $checkoutID);
  $stmt->execute();
  $stmt->bind_result($status);
  $found = $stmt->fetch();
  $stmt->close();
  return $found ? $status : null;
}

/**
 * Confirms a paid checkout: flips Awaiting Payment to Order Received and awards
 * points once. Idempotent — replaying the callback is a no-op. Returns true
 * only on the transition that actually completed the payment.
 */
function payment_mark_paid(mysqli $conn, string $checkoutID, string $method, ?string $last4): bool
{
  $conn->begin_transaction();
  try {
    $rows = payment_lock_group($conn, $checkoutID);
    if (empty($rows) || $rows[0]['orderStatus'] !== PAYMENT_AWAITING) {
      $conn->rollback();
      return false;
    }

    $userID = (int)$rows[0]['userID'];
    $subtotal = 0.0;
    foreach ($rows as $row) {
      $subtotal += (float)$row['total'];
    }

    $update = $conn->prepare("UPDATE orders
      SET orderStatus = ?, statusSource = 'auto', payment_method = ?, card_last4 = ?,
          paid_at = NOW(), lastStatusUpdate = NOW()
      WHERE checkoutID = ?");
    $status = PAYMENT_SUCCESS_STATUS;
    $update->bind_param("ssss", $status, $method, $last4, $checkoutID);
    $update->execute();
    $update->close();

    if ($subtotal > 0) {
      $tierStmt = $conn->prepare("SELECT lifetime_points FROM users WHERE userID = ? FOR UPDATE");
      $tierStmt->bind_param("i", $userID);
      $tierStmt->execute();
      $tierStmt->bind_result($lifetimePoints);
      $tierStmt->fetch();
      $tierStmt->close();

      $tier = get_tier((int)$lifetimePoints);
      $points = (int)floor($subtotal * $tier['multiplier']);
      if ($points > 0) {
        award_points($conn, $userID, $points, "Order $checkoutID");
      }
    }

    $conn->commit();
    return true;
  } catch (mysqli_sql_exception $e) {
    $conn->rollback();
    return false;
  }
}

/**
 * Fails a checkout: restores stock, releases the reserved voucher and marks the
 * group Payment Failed. Idempotent. Points were never awarded, so none reverse.
 */
function payment_mark_failed(mysqli $conn, string $checkoutID): bool
{
  $conn->begin_transaction();
  try {
    $rows = payment_lock_group($conn, $checkoutID);
    if (empty($rows) || $rows[0]['orderStatus'] !== PAYMENT_AWAITING) {
      $conn->rollback();
      return false;
    }

    foreach ($rows as $row) {
      if ($row['itemID'] !== null) {
        $restock = $conn->prepare("UPDATE menu_items SET stock = stock + ? WHERE id = ?");
        $restock->bind_param("ii", $row['qty'], $row['itemID']);
        $restock->execute();
        $restock->close();
      }
    }

    $voucherID = $rows[0]['member_voucher_id'];
    if ($voucherID !== null) {
      $release = $conn->prepare("UPDATE member_vouchers SET used = 0 WHERE memberVoucherID = ?");
      $release->bind_param("i", $voucherID);
      $release->execute();
      $release->close();
    }

    $update = $conn->prepare("UPDATE orders SET orderStatus = ?, lastStatusUpdate = NOW() WHERE checkoutID = ?");
    $failed = PAYMENT_FAILED;
    $update->bind_param("ss", $failed, $checkoutID);
    $update->execute();
    $update->close();

    $conn->commit();
    return true;
  } catch (mysqli_sql_exception $e) {
    $conn->rollback();
    return false;
  }
}

/** Expires checkouts abandoned at the gateway for over 15 minutes. */
function payment_expire_stale(mysqli $conn): void
{
  $stmt = $conn->prepare("SELECT DISTINCT checkoutID FROM orders
                          WHERE orderStatus = ? AND orderTime < (NOW() - INTERVAL ? MINUTE)");
  $awaiting = PAYMENT_AWAITING;
  $minutes = PAYMENT_EXPIRY_MINUTES;
  $stmt->bind_param("si", $awaiting, $minutes);
  $stmt->execute();
  $stale = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  foreach ($stale as $row) {
    payment_mark_failed($conn, $row['checkoutID']);
  }
}

function payment_lock_group(mysqli $conn, string $checkoutID): array
{
  $stmt = $conn->prepare("SELECT orderID, userID, itemID, qty, total, orderStatus, member_voucher_id
                          FROM orders WHERE checkoutID = ? FOR UPDATE");
  $stmt->bind_param("s", $checkoutID);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  return $rows;
}

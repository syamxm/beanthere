<?php

require_once __DIR__ . '/order_status.php';

/**
 * Applies an admin status change to a whole checkout group inside one
 * transaction. Returns [true, message] on success or [false, message] if the
 * transition is illegal or the group is gone. Cancelling restocks the drinks
 * and reverses the loyalty points; it does NOT release the voucher (documented
 * on the board). A voucher, once spent on an order, is spent.
 */
function apply_group_status(mysqli $conn, string $checkoutID, string $newStatus): array
{
  $conn->begin_transaction();
  try {
    $stmt = $conn->prepare("SELECT orderID, userID, itemID, qty, delivery, orderStatus
                            FROM orders WHERE checkoutID = ? FOR UPDATE");
    $stmt->bind_param("s", $checkoutID);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($rows)) {
      $conn->rollback();
      return [false, "That order no longer exists."];
    }

    $current = $rows[0]['orderStatus'];
    $delivery = $rows[0]['delivery'] ?? '';
    $userID = (int)$rows[0]['userID'];

    if ($current === $newStatus) {
      $conn->rollback();
      return [false, "That order is already " . $newStatus . "."];
    }
    if (!order_can_transition($delivery, $current, $newStatus)) {
      $conn->rollback();
      return [false, "That status change isn't allowed."];
    }

    $update = $conn->prepare("UPDATE orders SET orderStatus = ?, statusSource = 'manual', lastStatusUpdate = NOW() WHERE checkoutID = ?");
    $update->bind_param("ss", $newStatus, $checkoutID);
    $update->execute();
    $update->close();

    if ($newStatus === ORDER_CANCELLED) {
      cancel_group_side_effects($conn, $checkoutID, $userID, $rows);
    }

    $conn->commit();
    return [true, "Order updated to " . $newStatus . "."];
  } catch (mysqli_sql_exception $e) {
    $conn->rollback();
    return [false, "Something went wrong — the order was not changed."];
  }
}

/** Restock every drink in the group and reverse the points once. */
function cancel_group_side_effects(mysqli $conn, string $checkoutID, int $userID, array $rows): void
{
  foreach ($rows as $row) {
    if ($row['itemID'] === null) {
      continue;
    }
    $restock = $conn->prepare("UPDATE menu_items SET stock = stock + ? WHERE id = ?");
    $restock->bind_param("ii", $row['qty'], $row['itemID']);
    $restock->execute();
    $restock->close();
  }

  $orderReason = "Order $checkoutID";
  $reversalReason = "Cancelled $checkoutID";

  // Reverse the exact points this checkout earned, and only once.
  $stmt = $conn->prepare("SELECT
      (SELECT points FROM loyalty_ledger WHERE userID = ? AND reason = ? LIMIT 1) AS earned,
      (SELECT COUNT(*) FROM loyalty_ledger WHERE userID = ? AND reason = ?) AS alreadyReversed");
  $stmt->bind_param("isis", $userID, $orderReason, $userID, $reversalReason);
  $stmt->execute();
  $stmt->bind_result($earned, $alreadyReversed);
  $stmt->fetch();
  $stmt->close();

  if ($earned !== null && (int)$earned > 0 && (int)$alreadyReversed === 0) {
    require_once __DIR__ . '/loyalty.php';
    award_points($conn, $userID, -(int)$earned, $reversalReason);
  }
}

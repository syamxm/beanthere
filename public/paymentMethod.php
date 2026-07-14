<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header("Location: user_login.php?return=cart.php");
  exit;
}

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/loyalty.php';

$username = $_SESSION['current_user'];
$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paymentMethod'])) {
  csrf_verify();

  if (!store_status()['open']) {
    $_SESSION['message'] = "We're closed right now — checkout is paused until we reopen.";
    $_SESSION['success'] = false;
    header("Location: cart.php");
    exit;
  }

  $paymentMethod = $_POST['paymentMethod'];
  $selectedIDs = $_POST['selected_ids'] ?? [];

  if (empty($selectedIDs)) {
    header("Location: cart.php");
    exit;
  }

  // Validate selected IDs
  $placeholders = implode(',', array_fill(0, count($selectedIDs), '?'));
  $types = str_repeat('i', count($selectedIDs) + 1); // +1 for userID
  // nosemgrep: php.lang.security.injection.tainted-sql-string.tainted-sql-string -- $placeholders is only "?" marks; all values are bound below
  $sql = "SELECT * FROM cart WHERE userID = ? AND cartID IN ($placeholders)";
  $stmt = $conn->prepare($sql);
  $params = array_merge([$userID], $selectedIDs);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();

  $deliveryMethod = ($_POST['delivery_method'] ?? '') === 'Delivery' ? 'Delivery' : 'Pickup';
  $deliveryCharge = $deliveryMethod === 'Delivery' ? 3.00 : 0.00;

  // Validate voucher server-side: must belong to this user, be active, unused and in date
  $discountPercent = 0;
  $memberVoucherID = null;
  if (isset($_POST['memberVoucherID']) && is_numeric($_POST['memberVoucherID'])) {
    $voucherStmt = $conn->prepare("
      SELECT mv.memberVoucherID, v.discount_value
      FROM member_vouchers mv
      JOIN vouchers v ON mv.voucherID = v.voucherID
      JOIN membership m ON mv.membershipID = m.membershipID
      WHERE mv.memberVoucherID = ? AND m.userID = ? AND mv.used = 0
        AND v.status = 'active' AND CURDATE() BETWEEN v.valid_from AND v.valid_until
    ");
    $postedVoucherID = intval($_POST['memberVoucherID']);
    $voucherStmt->bind_param("ii", $postedVoucherID, $userID);
    $voucherStmt->execute();
    $voucherStmt->bind_result($memberVoucherID, $discountPercent);
    if (!$voucherStmt->fetch()) {
      $memberVoucherID = null;
      $discountPercent = 0;
    }
    $voucherStmt->close();
  }

  $conn->begin_transaction();
  try {

  $subtotal = 0;
  $firstRow = true;
  while ($row = $result->fetch_assoc()) {
    $initialStatus = "Order Received";

    $rowTotal = $row['total'] * (1 - $discountPercent / 100);
    $subtotal += $rowTotal;
    if ($firstRow) {
      $rowTotal += $deliveryCharge;
      $firstRow = false;
    }
    $rowTotal = round($rowTotal, 2);

    $insert = $conn->prepare("INSERT INTO orders (
      userID, name, drinkType, roastLevel, caffeineLevel, milkType,
      sugarLevel, toppings, syrups, delivery, total, qty, orderStatus, orderTime
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $insert->bind_param(
      "isssssssssdis",
      $userID,
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
      $row['qty'],
      $initialStatus
    );
    $insert->execute();
    $insert->close();

    $delete = $conn->prepare("DELETE FROM cart WHERE cartID = ?");
    $delete->bind_param("i", $row['cartID']);
    $delete->execute();
    $delete->close();

    $update = $conn->prepare("UPDATE menu_items SET stock = stock - ? WHERE id = ?");
    $update->bind_param("ii", $row['qty'], $row['itemID']);
    $update->execute();
    $update->close();
  }

  if ($memberVoucherID !== null) {
    $updateVoucher = $conn->prepare("UPDATE member_vouchers SET used = 1 WHERE memberVoucherID = ? AND used = 0");
    $updateVoucher->bind_param("i", $memberVoucherID);
    $updateVoucher->execute();
    $updateVoucher->close();
  }

  $earnedPoints = 0;
  if ($subtotal > 0) {
    $tierStmt = $conn->prepare("SELECT lifetime_points FROM users WHERE userID = ? FOR UPDATE");
    $tierStmt->bind_param("i", $userID);
    $tierStmt->execute();
    $tierStmt->bind_result($lifetimePoints);
    $tierStmt->fetch();
    $tierStmt->close();

    $tier = get_tier((int)$lifetimePoints);
    $earnedPoints = (int)floor($subtotal * $tier['multiplier']);
    if ($earnedPoints > 0) {
      award_points($conn, $userID, $earnedPoints, 'Order');
    }
  }

  $conn->commit();
  } catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $_SESSION['message'] = "Payment could not be completed — nothing was charged. Please try again.";
    $_SESSION['success'] = false;
    header("Location: cart.php");
    exit;
  }

  $stmt->close();
  $conn->close();

  $successText = "Payment via $paymentMethod completed successfully!";
  if ($earnedPoints > 0) {
    $successText .= " You earned $earnedPoints points!";
  }
  $paymentMessage = json_encode($successText, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
  // nosemgrep: php.lang.security.injection.echoed-request.echoed-request -- $paymentMessage is json_encode'd with HEX flags, safe in this script context
  echo "<script>
    alert($paymentMessage);
    window.location.href = 'user_order_tracking.php';
  </script>";
  exit();
}

$pageTitle = 'Payment - Bean There';
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
    <h1 class="text-2xl font-bold mb-2">Choose payment method</h1>
    <p class="inline-flex items-center gap-1.5 text-xs text-foam bg-bean/40 border border-bean rounded-full px-3 py-1 mb-6">
      <i class="fa-solid fa-flask text-caramel"></i> Demo — no real payment is processed, don't enter a real card number.
    </p>

    <form id="paymentForm" method="post" class="bg-roast border border-bean rounded-2xl p-6">
      <?= csrf_field() ?>
      <?php
      if (isset($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
        foreach ($_POST['selected_ids'] as $id) {
          echo '<input type="hidden" name="selected_ids[]" value="' . htmlspecialchars($id) . '">';
        }
      }
      if (isset($_POST['memberVoucherID'])) {
        echo '<input type="hidden" name="memberVoucherID" value="' . htmlspecialchars($_POST['memberVoucherID']) . '">';
      }
      if (isset($_POST['delivery_method'])) {
        echo '<input type="hidden" name="delivery_method" value="' . htmlspecialchars($_POST['delivery_method']) . '">';
      }
      ?>

      <label class="payment-option flex items-center gap-3 border border-bean rounded-xl px-4 py-3.5 mb-2 cursor-pointer hover:border-caramel bg-espresso">
        <input type="radio" name="paymentMethod" value="Card" required class="accent-[#c49b63]">
        <span><i class="fa-regular fa-credit-card text-caramel mr-2"></i>Credit / debit card</span>
      </label>
      <div id="cardFields" class="hidden ml-2 mb-4 flex flex-col gap-2">
        <input type="text" id="cardNumber" placeholder="Card number" inputmode="numeric"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 text-crema focus:outline-none focus:border-caramel">
        <input type="text" id="cvv" placeholder="CVV" inputmode="numeric"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 text-crema focus:outline-none focus:border-caramel">
        <input type="month" id="expiryDate"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 text-crema focus:outline-none focus:border-caramel">
      </div>

      <label class="payment-option flex items-center gap-3 border border-bean rounded-xl px-4 py-3.5 mb-2 cursor-pointer hover:border-caramel bg-espresso">
        <input type="radio" name="paymentMethod" value="E-Wallet" class="accent-[#c49b63]">
        <span><i class="fa-solid fa-wallet text-caramel mr-2"></i>E-Wallet (Touch 'n Go, Boost, etc.)</span>
      </label>
      <div id="walletFields" class="hidden ml-2 mb-4">
        <input type="text" id="walletPhone" placeholder="Phone number" inputmode="tel"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 text-crema focus:outline-none focus:border-caramel">
      </div>

      <label class="payment-option flex items-center gap-3 border border-bean rounded-xl px-4 py-3.5 mb-2 cursor-pointer hover:border-caramel bg-espresso">
        <input type="radio" name="paymentMethod" value="Online Banking" class="accent-[#c49b63]">
        <span><i class="fa-solid fa-building-columns text-caramel mr-2"></i>Online banking</span>
      </label>
      <div id="bankFields" class="hidden ml-2 mb-4">
        <select id="bankSelect"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 text-crema focus:outline-none focus:border-caramel">
          <option value="">Select your bank</option>
          <option>Maybank</option>
          <option>CIMB</option>
          <option>RHB</option>
          <option>Bank Islam</option>
          <option>BSN</option>
          <option>Ambank</option>
          <option>Public Bank</option>
          <option>Hong Leong Bank</option>
          <option>Agrobank</option>
          <option>UOB</option>
          <option>Affin Bank</option>
        </select>
      </div>

      <button type="submit" id="confirmPaymentBtn" class="w-full bg-caramel text-espresso font-semibold py-3 rounded-lg hover:bg-crema transition mt-2 disabled:opacity-60 disabled:cursor-not-allowed">Confirm payment</button>
    </form>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>

  <script>
    const paymentRadios = document.querySelectorAll("input[name='paymentMethod']");
    const cardFields = document.getElementById("cardFields");
    const walletFields = document.getElementById("walletFields");
    const bankFields = document.getElementById("bankFields");

    paymentRadios.forEach(radio => {
      radio.addEventListener("change", () => {
        const value = radio.value;
        cardFields.classList.toggle("hidden", value !== "Card");
        walletFields.classList.toggle("hidden", value !== "E-Wallet");
        bankFields.classList.toggle("hidden", value !== "Online Banking");
      });
    });

    function isExpired(expiry) {
      const [year, month] = expiry.split('-').map(Number);
      const now = new Date();
      return new Date(year, month) < now;
    }

    const confirmPaymentBtn = document.getElementById("confirmPaymentBtn");

    function lockSubmit(form) {
      confirmPaymentBtn.disabled = true;
      confirmPaymentBtn.textContent = "Processing...";
      form.submit();
    }

    document.getElementById("paymentForm").addEventListener("submit", function (e) {
      e.preventDefault();

      if (confirmPaymentBtn.disabled) {
        return;
      }

      const selected = document.querySelector("input[name='paymentMethod']:checked");
      if (!selected) {
        alert("Please select a payment method.");
        return;
      }

      if (selected.value === "Card") {
        const cardNum = document.getElementById("cardNumber").value.trim();
        const cvv = document.getElementById("cvv").value.trim();
        const expiry = document.getElementById("expiryDate").value;
        if (!cardNum || !cvv || !expiry) {
          alert("Please fill in all card details.");
          return;
        }
        if (isExpired(expiry)) {
          alert("Card is expired. Please use a valid card.");
          return;
        }
        lockSubmit(e.target);
        return;
      }

      if (selected.value === "E-Wallet") {
        const phone = document.getElementById("walletPhone").value.trim();
        if (!phone) {
          alert("Please enter your phone number.");
          return;
        }
        confirmPaymentBtn.disabled = true;
        confirmPaymentBtn.textContent = "Redirecting...";
        alert("Redirecting to E-Wallet...");
        setTimeout(() => lockSubmit(e.target), 3000);
        return;
      }

      if (selected.value === "Online Banking") {
        const bank = document.getElementById("bankSelect").value;
        if (!bank) {
          alert("Please select a bank.");
          return;
        }
        confirmPaymentBtn.disabled = true;
        confirmPaymentBtn.textContent = "Redirecting...";
        alert("Redirecting to " + bank + " online banking portal...");
        setTimeout(() => lockSubmit(e.target), 3000);
      }
    });
  </script>
</body>

</html>

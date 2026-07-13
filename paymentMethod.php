<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  die("Unauthorized. Please log in.");
}

require_once 'dbconn.php';

$username = $_SESSION['current_user'];
$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paymentMethod'])) {
  $paymentMethod = $_POST['paymentMethod'];
  $selectedIDs = $_POST['selected_ids'] ?? [];
  //$finalTotal = isset($_POST['final_total']) ? floatval($_POST['final_total']) : 0.00;

  if (empty($selectedIDs)) {
    die("No items selected.");
  }

  // Validate selected IDs
  $placeholders = implode(',', array_fill(0, count($selectedIDs), '?'));
  $types = str_repeat('i', count($selectedIDs) + 1); // +1 for userID
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

  $firstRow = true;
  while ($row = $result->fetch_assoc()) {
    $initialStatus = "Order Received";

    $rowTotal = $row['total'] * (1 - $discountPercent / 100);
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

  $stmt->close();
  $conn->close();

  $paymentMessage = json_encode("Payment via $paymentMethod completed successfully!", JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
  echo "<script>
    alert($paymentMessage);
    window.location.href = 'user_order_tracking.php';
  </script>";
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Choose Payment - Bean There</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fdfaf6;
      color: #333;
    }

    .container {
      max-width: 600px;
      margin: 3rem auto;
      padding: 2rem;
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }

    .section-title {
      font-size: 1.75rem;
      font-weight: 700;
      text-align: center;
      color: #8d6e63;
      margin-bottom: 2rem;
    }

    .payment-option {
      display: flex;
      align-items: center;
      padding: 1rem;
      border-radius: 0.75rem;
      border: 2px solid #e0e0e0;
      margin-bottom: 1rem;
      cursor: pointer;
      background-color: #f9f6f2;
      transition: all 0.3s ease-in-out;
    }

    .payment-option:hover {
      border-color: #c49b63;
      background-color: #fffaf3;
    }

    .payment-option input {
      margin-right: 1rem;
      accent-color: #c49b63;
    }

    .sub-fields {
      margin-left: 2rem;
      margin-top: 0.5rem;
      margin-bottom: 1rem;
    }

    .sub-fields input,
    .sub-fields select {
      width: 100%;
      padding: 0.6rem 0.8rem;
      border: 1px solid #ccc;
      border-radius: 0.5rem;
      font-size: 0.95rem;
      margin-bottom: 0.5rem;
      background-color: #fefefe;
    }

    .btn-submit {
      background-color: #c49b63;
      color: white;
      padding: 0.9rem 1.5rem;
      border: none;
      border-radius: 0.75rem;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      width: 100%;
      transition: background-color 0.3s ease;
    }

    .btn-submit:hover {
      background-color: #aa8152;
    }

    .back-link {
      position: absolute;
      top: 1.2rem;
      left: 1.2rem;
      background-color: white;
      border: 1px solid #ccc;
      color: #333;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      font-size: 0.9rem;
      text-decoration: none;
      transition: all 0.2s ease-in-out;
    }

    .back-link:hover {
      background-color: #f0f0f0;
      border-color: #aaa;
    }

    .hidden {
      display: none;
    }
  </style>
</head>

<body>
  <a href="cart.php" class="back-link">← Back to Cart</a>
  <div class="container">
    <h2 class="section-title">Choose Payment Method</h2>

    <form id="paymentForm" method="post">
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

      <label class="payment-option">
        <input type="radio" name="paymentMethod" value="Card" required />
        <span>Credit / Debit Card</span>
      </label>
      <div id="cardFields" class="sub-fields hidden">
        <input type="text" id="cardNumber" placeholder="Card Number" />
        <input type="text" id="cvv" placeholder="CVV" />
        <input type="month" id="expiryDate" />
      </div>

      <label class="payment-option">
        <input type="radio" name="paymentMethod" value="E-Wallet" />
        <span>E-Wallet (Touch 'n Go, Boost, etc.)</span>
      </label>
      <div id="walletFields" class="sub-fields hidden">
        <input type="text" id="walletPhone" placeholder="Phone Number" />
      </div>

      <label class="payment-option">
        <input type="radio" name="paymentMethod" value="Online Banking" />
        <span>Online Banking</span>
      </label>
      <div id="bankFields" class="sub-fields hidden">
        <select id="bankSelect">
          <option value="">Select Your Bank</option>
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

      <button type="submit" class="btn-submit">Confirm Payment</button>
    </form>
  </div>

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

    document.getElementById("paymentForm").addEventListener("submit", function(e) {
      e.preventDefault(); // Always prevent default at first

      const selected = document.querySelector("input[name='paymentMethod']:checked");
      if (!selected) {
        alert("Please select a payment method.");
        return;
      }

      // Card validation
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

        // Submit immediately for Card
        e.target.submit();
        return;
      }

      // E-Wallet validation
      if (selected.value === "E-Wallet") {
        const phone = document.getElementById("walletPhone").value.trim();
        if (!phone) {
          alert("Please enter your phone number.");
          return;
        }
        // Fake redirect message
        alert("Redirecting to E-Wallet...");
        setTimeout(() => {
          e.target.submit(); // Submit form after 3 sec
        }, 3000);
        return;
      }

      // Online Banking validation
      if (selected.value === "Online Banking") {
        const bank = document.getElementById("bankSelect").value;
        if (!bank) {
          alert("Please select a bank.");
          return;
        }
        alert("Redirecting to " + bank + " online banking portal...");
        setTimeout(() => {
          e.target.submit(); // Submit after 3 sec
        }, 3000);
        return;
      }
    });
  </script>

</body>

</html>
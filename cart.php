<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  die("Unauthorized. Please log in.");
}

include "dbconn.php";

$username = $_SESSION['current_user'];
$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['cart_id'])) {
  $cart_id = intval($_POST['cart_id']);
  if ($_POST['action'] === 'increase') {
    $stmt = $conn->prepare("UPDATE cart c JOIN menu_items m ON c.itemID = m.id
                            SET c.total = c.total / c.qty * (c.qty + 1), c.qty = c.qty + 1
                            WHERE c.cartID = ? AND c.userID = ? AND c.qty < m.stock");
    $stmt->bind_param("ii", $cart_id, $userID);
    $stmt->execute();
    $stmt->close();
  } elseif ($_POST['action'] === 'decrease') {
    $stmt = $conn->prepare("UPDATE cart
                            SET total = total / qty * GREATEST(qty - 1, 1), qty = GREATEST(qty - 1, 1)
                            WHERE cartID = ? AND userID = ?");
    $stmt->bind_param("ii", $cart_id, $userID);
    $stmt->execute();
    $stmt->close();
  } elseif ($_POST['action'] === 'remove') {
    $stmt = $conn->prepare("DELETE FROM cart WHERE cartID = ? AND userID = ?");
    $stmt->bind_param("ii", $cart_id, $userID);
    $stmt->execute();
    $stmt->close();
  }
}

$items = [];
$stmt = $conn->prepare("
  SELECT c.*, m.stock
  FROM cart c
  JOIN menu_items m ON c.itemID = m.id
  WHERE c.userID = ?
");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $items[] = $row;
}
$stmt->close();


// Fetch user's vouchers (if member)
$vouchers = [];
$voucher_query = "
  SELECT v.voucherID, v.code, v.discount_value, mv.memberVoucherID
  FROM vouchers v
  JOIN member_vouchers mv ON v.voucherID = mv.voucherID
  JOIN membership m ON mv.membershipID = m.membershipID
  WHERE m.userID = ?
    AND v.status = 'active'
    AND CURDATE() BETWEEN v.valid_from AND v.valid_until
    AND mv.used = 0
";

$stmt = $conn->prepare($voucher_query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $vouchers[] = $row;
}


$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Cart - Bean There</title>
  <style>
    body {
      background: #000;
      color: #fff;
      font-family: 'Poppins', sans-serif;
      padding: 50px;
    }

    h1 {
      color: #c49b63;
      text-align: center;
      margin-bottom: 30px;
    }

    .cart-item {
      display: grid;
      grid-template-columns: auto 1fr auto auto;
      align-items: center;
      gap: 15px;
      background: #111;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 15px;
      position: relative;
      transition: box-shadow 0.3s ease;
    }

    .cart-item.selected {
      box-shadow: 0 0 10px 2px #c49b63;
    }

    .custom-checkbox {
      display: flex;
      align-items: center;
    }

    .custom-checkbox input[type="checkbox"] {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .custom-checkbox .checkmark {
      width: 20px;
      height: 20px;
      background-color: #222;
      border: 2px solid #c49b63;
      border-radius: 4px;
      position: relative;
      transition: background-color 0.2s ease;
    }

    .custom-checkbox .checkmark::after {
      content: '✔';
      color: #000;
      position: absolute;
      font-size: 14px;
      left: 3px;
      top: -2px;
      display: none;
    }

    .custom-checkbox input:checked+.checkmark {
      background-color: #c49b63;
    }

    .custom-checkbox input:checked+.checkmark::after {
      display: block;
    }

    .item-info {
      color: #eee;
      font-size: 15px;
    }

    .qty-box {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .qty-box button {
      background: #c49b63;
      border: none;
      padding: 5px 10px;
      cursor: pointer;
      color: #000;
      font-weight: bold;
      border-radius: 4px;
    }

    .remove-btn {
      background: #ff4d4d;
      color: #fff;
      border: none;
      border-radius: 4px;
      padding: 5px 8px;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
    }

    .checkout-btn {
      background: #c49b63;
      color: #000;
      padding: 15px;
      text-align: center;
      border-radius: 5px;
      font-weight: bold;
      margin-top: 30px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .checkout-btn.disabled {
      background: #555;
      color: #999;
      cursor: not-allowed;
    }

    .button-link {
      display: inline-block;
      margin-top: 10px;
      color: #f8b400;
      text-decoration: none;
      font-weight: bold;
    }

    .button-link:hover {
      text-decoration: underline;
    }

    #summary {
      text-align: right;
      margin-top: 15px;
      font-size: 1.2rem;
      font-weight: bold;
      color: #f8b400;
    }

    .voucher-box {
      margin-top: 30px;
      background: #1a1a1a;
      padding: 20px;
      border-radius: 12px;
      border: 1px solid #c49b63;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
      text-align: center;
    }

    .voucher-heading {
      font-size: 1.25rem;
      color: #f8b400;
      margin-bottom: 10px;
    }

    .voucher-label {
      font-size: 1rem;
      color: #ddd;
      margin-right: 8px;
    }

    .voucher-select {
      font-size: 16px;
      padding: 10px;
      background: #1c1c1c;
      border: 1px solid #c49b63;
      color: white;
      border-radius: 5px;
      width: 100%;
      max-width: 300px;
    }
  </style>
</head>

<body>

  <h1>Your Cart</h1>
  <div id="cart">
    <?php foreach ($items as $index => $item):
      $details = [];
      if ($item['drinkType']) $details[] = $item['drinkType'];
      if ($item['milkType']) $details[] = $item['milkType'];
      if (!empty($item['syrups'])) $details[] = "Syrups: " . $item['syrups'];
      if (!empty($item['toppings'])) $details[] = "Toppings: " . $item['toppings'];
      $totalFormatted = number_format($item['total'], 2);
    ?>
      <div class="cart-item" id="item-<?= $index ?>" data-cartid="<?= $item['cartID'] ?>">
        <label class="custom-checkbox">
          <input type="checkbox" id="check-<?= $index ?>" onchange="updateSummary()">
          <span class="checkmark"></span>
        </label>
        <span class="item-info" data-price="<?= $item['total'] ?>" data-qty="<?= $item['qty'] ?>">
          <?= htmlspecialchars($item['name']) ?><?php if (!empty($details)) echo htmlspecialchars(" (" . implode(', ', $details) . ")"); ?>, Total: RM<?= $totalFormatted ?>
        </span>
        <div class="qty-box">
          <form method="POST" style="display:inline">
            <input type="hidden" name="cart_id" value="<?= $item['cartID'] ?>">
            <input type="hidden" name="action" value="decrease">
            <button type="submit">-</button>
          </form>
          <span><?= $item['qty'] ?></span>
          <form method="POST" style="display:inline">
            <input type="hidden" name="cart_id" value="<?= $item['cartID'] ?>">
            <input type="hidden" name="action" value="increase">
            <button type="submit" <?= $item['qty'] >= $item['stock'] ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>+</button>
          </form>

        </div>
        <form method="POST" style="display:inline">
          <input type="hidden" name="cart_id" value="<?= $item['cartID'] ?>">
          <input type="hidden" name="action" value="remove">
          <button class="remove-btn" type="submit" onclick="return confirm('Are you sure you want to remove')">×</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
  <form id="checkoutForm" method="POST" action="paymentMethod.php">
    <!-- Selected cartIDs will be injected here -->
  </form>


  <div id="checkoutBtn" class="checkout-btn" onclick="checkout()">Checkout Selected</div>
  <div id="summary">Subtotal: RM 0.00</div>
  <a href="user_dashboard.php" class="button-link">⬅ Back to Main Page</a>

  <?php if (count($vouchers) > 0): ?>
    <div class="voucher-box">
      <h2 class="voucher-heading">🎁 Apply a Voucher</h2>
      <label for="voucher" class="voucher-label">Choose Voucher:</label>
      <select id="voucher" class="voucher-select" onchange="updateSummary()">
        <option value="0" data-discount="0">-- No Voucher --</option>
        <?php foreach ($vouchers as $v): ?>
          <option
            value="<?= $v['voucherID'] ?>"
            data-discount="<?= $v['discount_value'] ?>"
            data-member-id="<?= $v['memberVoucherID'] ?>">
            <?= htmlspecialchars($v['code']) ?> (<?= $v['discount_value'] ?>% Off)
          </option>
        <?php endforeach; ?>

      </select>
    </div>
  <?php endif; ?>

  <div class="voucher-box">
    <h2 class="voucher-heading">🚚 Delivery Method</h2>
    <label class="voucher-label">Choose:</label>
    <select id="deliveryMethod" class="voucher-select" onchange="updateSummary()">
      <option value="Pickup" data-charge="0">Pickup (Free)</option>
      <option value="Delivery" data-charge="3">Delivery (RM 3.00)</option>
    </select>
  </div>





  <script>
    document.querySelectorAll('.cart-item').forEach((item, index) => {
      item.addEventListener('click', function(e) {
        const tag = e.target.tagName.toLowerCase();
        if (["button", "svg", "path", "form", "input"].includes(tag)) return;
        const checkbox = document.getElementById(`check-${index}`);
        checkbox.checked = !checkbox.checked;
        updateSummary();
      });
    });

    let globalDiscountedTotal = 0;

    function updateSummary() {
      const cartItems = document.querySelectorAll(".cart-item");
      let sum = 0;
      let selectedCount = 0;

      cartItems.forEach((item, i) => {
        const checkbox = document.getElementById(`check-${i}`);
        const price = parseFloat(item.querySelector(".item-info").dataset.price);

        if (checkbox.checked) {
          sum += price;
          selectedCount++;
          item.classList.add('selected');
        } else {
          item.classList.remove('selected');
        }
      });

      const voucher = document.getElementById("voucher");
      const discountPercent = voucher ? parseFloat(voucher.selectedOptions[0].dataset.discount) : 0;
      const discounted = sum * (1 - discountPercent / 100);

      const deliverySelect = document.getElementById("deliveryMethod");
      const deliveryCharge = deliverySelect ? parseFloat(deliverySelect.selectedOptions[0].dataset.charge) : 0;

      const totalWithDelivery = discounted + deliveryCharge;
      globalDiscountedTotal = totalWithDelivery; // save for checkout

      document.getElementById('summary').textContent =
        `Subtotal: RM ${totalWithDelivery.toFixed(2)} (Before Discount: RM ${sum.toFixed(2)}${deliveryCharge > 0 ? ` + RM ${deliveryCharge.toFixed(2)} delivery` : ''})`;

      const btn = document.getElementById("checkoutBtn");
      btn.classList.toggle('disabled', selectedCount === 0);
    }



    function checkout() {
      const btn = document.getElementById("checkoutBtn");
      if (btn.classList.contains("disabled")) return;

      const form = document.getElementById("checkoutForm");
      form.innerHTML = '';

      document.querySelectorAll(".cart-item").forEach((item, i) => {
        const checkbox = document.getElementById(`check-${i}`);
        if (checkbox.checked) {
          const cartID = item.dataset.cartid;
          const input = document.createElement("input");
          input.type = "hidden";
          input.name = "selected_ids[]";
          input.value = cartID;
          form.appendChild(input);
        }
      });

      const voucher = document.getElementById("voucher");
      if (voucher && voucher.value !== "0") {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "voucherID";
        input.value = voucher.value;
        form.appendChild(input);

        const memberVoucherID = voucher.selectedOptions[0].dataset.memberId;
        const memberInput = document.createElement("input");
        memberInput.type = "hidden";
        memberInput.name = "memberVoucherID";
        memberInput.value = memberVoucherID;
        form.appendChild(memberInput);
      }

      const deliveryMethod = document.getElementById("deliveryMethod");
      const deliveryCharge = parseFloat(deliveryMethod.selectedOptions[0].dataset.charge);

      const deliveryInput = document.createElement("input");
      deliveryInput.type = "hidden";
      deliveryInput.name = "delivery_method";
      deliveryInput.value = deliveryMethod.value;
      form.appendChild(deliveryInput);

      const chargeInput = document.createElement("input");
      chargeInput.type = "hidden";
      chargeInput.name = "delivery_charge";
      chargeInput.value = deliveryCharge.toFixed(2);
      form.appendChild(chargeInput);

      form.submit();
    }


    updateSummary();
  </script>


</body>

</html>
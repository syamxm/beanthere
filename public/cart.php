<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header("Location: user_login.php?return=cart.php");
  exit;
}

require_once __DIR__ . '/../src/dbconn.php';

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
  SELECT c.*, m.stock, m.image_path
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

$pageTitle = 'Cart - Bean There';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow max-w-6xl mx-auto w-full px-4 py-10">
    <h1 class="text-3xl font-bold mb-2">Your cart</h1>
    <p class="text-foam text-sm mb-8">Tick the items you want to check out.</p>

    <?php if (count($items) === 0): ?>
      <div class="bg-roast border border-bean rounded-2xl p-10 text-center">
        <i class="fa-solid fa-mug-hot text-caramel text-4xl mb-4"></i>
        <p class="text-lg font-semibold mb-2">Nothing here yet</p>
        <p class="text-foam text-sm mb-6">Your cart is empty — the menu isn't.</p>
        <a href="user_dashboard.php" class="bg-caramel text-espresso font-semibold px-6 py-2.5 rounded-full hover:bg-crema transition">Browse the menu</a>
      </div>
    <?php else: ?>
      <div class="grid lg:grid-cols-3 gap-8 items-start">
        <div id="cart" class="lg:col-span-2 flex flex-col gap-4">
          <?php foreach ($items as $index => $item):
            $details = [];
            if ($item['drinkType']) $details[] = $item['drinkType'];
            if ($item['milkType']) $details[] = $item['milkType'];
            if (!empty($item['syrups'])) $details[] = "Syrups: " . $item['syrups'];
            if (!empty($item['toppings'])) $details[] = "Toppings: " . $item['toppings'];
          ?>
            <div class="cart-item bg-roast border border-bean rounded-2xl p-4 flex flex-wrap items-center gap-4 cursor-pointer transition"
              id="item-<?= $index ?>" data-cartid="<?= (int)$item['cartID'] ?>">
              <input type="checkbox" id="check-<?= $index ?>" onchange="updateSummary()"
                class="accent-[#c49b63] w-5 h-5 shrink-0">
              <img src="<?= htmlspecialchars($item['image_path']) ?>" alt=""
                class="w-14 h-14 rounded-xl object-cover border border-bean shrink-0">
              <span class="item-info grow min-w-40" data-price="<?= (float)$item['total'] ?>" data-qty="<?= (int)$item['qty'] ?>">
                <span class="font-semibold block"><?= htmlspecialchars($item['name']) ?></span>
                <?php if (!empty($details)): ?>
                  <span class="text-foam text-xs block"><?= htmlspecialchars(implode(' · ', $details)) ?></span>
                <?php endif; ?>
                <span class="text-caramel text-sm font-semibold">RM<?= number_format($item['total'], 2) ?></span>
              </span>
              <div class="flex items-center gap-2">
                <form method="POST" class="inline">
                  <input type="hidden" name="cart_id" value="<?= (int)$item['cartID'] ?>">
                  <input type="hidden" name="action" value="decrease">
                  <button type="submit" class="w-8 h-8 rounded-lg bg-bean text-crema hover:bg-caramel hover:text-espresso transition font-bold">−</button>
                </form>
                <span class="w-6 text-center"><?= (int)$item['qty'] ?></span>
                <form method="POST" class="inline">
                  <input type="hidden" name="cart_id" value="<?= (int)$item['cartID'] ?>">
                  <input type="hidden" name="action" value="increase">
                  <button type="submit" <?= $item['qty'] >= $item['stock'] ? 'disabled class="w-8 h-8 rounded-lg bg-bean text-foam opacity-50 cursor-not-allowed font-bold"' : 'class="w-8 h-8 rounded-lg bg-bean text-crema hover:bg-caramel hover:text-espresso transition font-bold"' ?>>+</button>
                </form>
                <form method="POST" class="inline ml-1">
                  <input type="hidden" name="cart_id" value="<?= (int)$item['cartID'] ?>">
                  <input type="hidden" name="action" value="remove">
                  <button type="submit" onclick="return confirm('Remove this item from your cart?')"
                    class="w-8 h-8 rounded-lg text-red-400 border border-red-400/40 hover:bg-red-400 hover:text-espresso transition" aria-label="Remove item">×</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <aside class="bg-roast border border-bean rounded-2xl p-6 lg:sticky lg:top-24">
          <h2 class="font-semibold text-lg mb-4">Order summary</h2>

          <?php if (count($vouchers) > 0): ?>
            <label for="voucher" class="block text-sm text-foam mb-1.5">Voucher</label>
            <select id="voucher" onchange="updateSummary()"
              class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">
              <option value="0" data-discount="0">No voucher</option>
              <?php foreach ($vouchers as $v): ?>
                <option value="<?= (int)$v['voucherID'] ?>" data-discount="<?= (float)$v['discount_value'] ?>" data-member-id="<?= (int)$v['memberVoucherID'] ?>">
                  <?= htmlspecialchars($v['code']) ?> (<?= (float)$v['discount_value'] ?>% off)
                </option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>

          <label for="deliveryMethod" class="block text-sm text-foam mb-1.5">Delivery</label>
          <select id="deliveryMethod" onchange="updateSummary()"
            class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">
            <option value="Pickup" data-charge="0">Pickup (free)</option>
            <option value="Delivery" data-charge="3">Delivery (RM 3.00)</option>
          </select>

          <div id="summary" class="text-crema font-semibold border-t border-bean pt-4 mb-4 text-sm">Subtotal: RM 0.00</div>

          <form id="checkoutForm" method="POST" action="paymentMethod.php"></form>
          <button id="checkoutBtn" onclick="checkout()"
            class="w-full bg-caramel text-espresso font-semibold py-3 rounded-lg hover:bg-crema transition disabled:bg-bean disabled:text-foam disabled:cursor-not-allowed" disabled>
            Checkout selected
          </button>
        </aside>
      </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>

  <script>
    document.querySelectorAll('.cart-item').forEach((item, index) => {
      item.addEventListener('click', function (e) {
        const tag = e.target.tagName.toLowerCase();
        if (["button", "svg", "path", "form", "input"].includes(tag)) return;
        const checkbox = document.getElementById(`check-${index}`);
        checkbox.checked = !checkbox.checked;
        updateSummary();
      });
    });

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
          item.classList.add('border-caramel');
        } else {
          item.classList.remove('border-caramel');
        }
      });

      const voucher = document.getElementById("voucher");
      const discountPercent = voucher ? parseFloat(voucher.selectedOptions[0].dataset.discount) : 0;
      const discounted = sum * (1 - discountPercent / 100);

      const deliverySelect = document.getElementById("deliveryMethod");
      const deliveryCharge = deliverySelect ? parseFloat(deliverySelect.selectedOptions[0].dataset.charge) : 0;

      const totalWithDelivery = discounted + deliveryCharge;

      document.getElementById('summary').textContent =
        `Subtotal: RM ${totalWithDelivery.toFixed(2)}` +
        (discountPercent > 0 || deliveryCharge > 0
          ? ` (items RM ${sum.toFixed(2)}${deliveryCharge > 0 ? ` + RM ${deliveryCharge.toFixed(2)} delivery` : ''})`
          : '');

      document.getElementById("checkoutBtn").disabled = selectedCount === 0;
    }

    function checkout() {
      const btn = document.getElementById("checkoutBtn");
      if (btn.disabled) return;
      btn.disabled = true;
      btn.textContent = "Redirecting...";

      const form = document.getElementById("checkoutForm");
      form.innerHTML = '';

      document.querySelectorAll(".cart-item").forEach((item, i) => {
        const checkbox = document.getElementById(`check-${i}`);
        if (checkbox.checked) {
          const input = document.createElement("input");
          input.type = "hidden";
          input.name = "selected_ids[]";
          input.value = item.dataset.cartid;
          form.appendChild(input);
        }
      });

      const voucher = document.getElementById("voucher");
      if (voucher && voucher.value !== "0") {
        const memberInput = document.createElement("input");
        memberInput.type = "hidden";
        memberInput.name = "memberVoucherID";
        memberInput.value = voucher.selectedOptions[0].dataset.memberId;
        form.appendChild(memberInput);
      }

      const deliveryMethod = document.getElementById("deliveryMethod");
      const deliveryInput = document.createElement("input");
      deliveryInput.type = "hidden";
      deliveryInput.name = "delivery_method";
      deliveryInput.value = deliveryMethod.value;
      form.appendChild(deliveryInput);

      form.submit();
    }

    if (document.getElementById("checkoutBtn")) {
      updateSummary();
    }
  </script>
</body>

</html>

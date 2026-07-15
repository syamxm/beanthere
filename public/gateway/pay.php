<?php
session_start();

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/payment.php';

// SyamPay is a pretend payment service provider. It only knows a reference, an
// amount and a signature — never BeanThere's session or database internals
// beyond confirming the order is still awaiting payment.
$ref = (string)($_GET['ref'] ?? $_POST['ref'] ?? '');
$amt = (string)($_GET['amt'] ?? $_POST['amt'] ?? '');
$sig = (string)($_GET['sig'] ?? $_POST['sig'] ?? '');

$linkValid = $ref !== '' && payment_verify($ref, $amt, $sig)
  && payment_group_amount($conn, $ref) === $amt;
$status = $linkValid ? payment_group_status($conn, $ref) : null;
$payable = $status === PAYMENT_AWAITING;

$threeDsThreshold = 50.00;
$amountValue = (float)$amt;
$error = '';
$stage = 'form';           // form | threeds | posting
$result = '';
$method = 'Card';
$last4 = null;

function luhn_ok(string $number): bool
{
  $digits = preg_replace('/\D/', '', $number);
  if (strlen($digits) < 12) {
    return false;
  }
  $sum = 0;
  $alt = false;
  for ($i = strlen($digits) - 1; $i >= 0; $i--) {
    $d = (int)$digits[$i];
    if ($alt) {
      $d *= 2;
      if ($d > 9) {
        $d -= 9;
      }
    }
    $sum += $d;
    $alt = !$alt;
  }
  return $sum % 10 === 0;
}

const TEST_CARDS = [
  '4242424242424242' => 'success',
  '4000000000000002' => 'declined',
  '4000000000009995' => 'insufficient',
];

if ($payable && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $channel = $_POST['channel'] ?? 'card';

  if ($channel === 'card' && isset($_POST['threeds'])) {
    // 3-D Secure confirmation: trust the outcome computed a step earlier.
    $result = in_array($_POST['result'] ?? '', ['success', 'declined', 'insufficient'], true) ? $_POST['result'] : 'declined';
    $last4 = preg_match('/^\d{4}$/', $_POST['last4'] ?? '') ? $_POST['last4'] : null;
    $stage = 'posting';
  } elseif ($channel === 'card') {
    $number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $expiry = trim($_POST['expiry'] ?? '');
    $cvv = preg_replace('/\D/', '', $_POST['cvv'] ?? '');

    // Accept MM/YY or MM/YYYY.
    $expiryOk = false;
    if (preg_match('#^(\d{2})\s*/\s*(\d{2}|\d{4})$#', $expiry, $m)) {
      $expMonth = (int)$m[1];
      $expYear = strlen($m[2]) === 2 ? 2000 + (int)$m[2] : (int)$m[2];
      $expiryOk = $expMonth >= 1 && $expMonth <= 12
        && strtotime(sprintf('%04d-%02d-01', $expYear, $expMonth)) >= strtotime(date('Y-m-01'));
    }

    if (!luhn_ok($number)) {
      $error = 'That card number is not valid.';
    } elseif (!$expiryOk) {
      $error = 'Enter a future expiry date as MM/YY.';
    } elseif (strlen($cvv) < 3) {
      $error = 'Enter the 3-digit security code.';
    } else {
      $last4 = substr($number, -4);
      $result = TEST_CARDS[$number] ?? 'declined';
      $method = 'Card';
      $stage = ($result === 'success' && $amountValue > $threeDsThreshold) ? 'threeds' : 'posting';
    }
  } else {
    // E-wallet / online banking: one-click simulate.
    $method = $channel === 'ewallet' ? 'E-Wallet' : 'Online Banking';
    $result = ($_POST['sim'] ?? '') === 'success' ? 'success' : 'declined';
    $stage = 'posting';
  }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SyamPay — Secure checkout</title>
  <style>
    :root { color-scheme: light; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #eef1f6; color: #1a2233; display: flex; min-height: 100vh; align-items: flex-start; justify-content: center; padding: 32px 16px; }
    .card { background: #fff; width: 100%; max-width: 420px; border-radius: 14px; box-shadow: 0 10px 40px rgba(20,30,60,.12); overflow: hidden; }
    .brand { background: #14224a; color: #fff; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; }
    .brand b { font-size: 20px; letter-spacing: .5px; }
    .brand span { font-size: 12px; opacity: .8; }
    .body { padding: 24px; }
    .amount { text-align: center; margin-bottom: 4px; font-size: 30px; font-weight: 700; }
    .merchant { text-align: center; color: #64708a; font-size: 13px; margin-bottom: 20px; }
    .demo { background: #fff7e6; border: 1px solid #ffe0a3; color: #8a6100; font-size: 12px; border-radius: 8px; padding: 8px 12px; margin-bottom: 18px; text-align: center; }
    .tabs { display: flex; gap: 6px; margin-bottom: 18px; }
    .tabs button { flex: 1; padding: 8px; border: 1px solid #d5dbe6; background: #f6f8fc; border-radius: 8px; font-size: 13px; cursor: pointer; }
    .tabs button.active { background: #14224a; color: #fff; border-color: #14224a; }
    label { display: block; font-size: 12px; color: #64708a; margin: 10px 0 4px; }
    input, select { width: 100%; padding: 11px 12px; border: 1px solid #d5dbe6; border-radius: 8px; font-size: 15px; }
    .row { display: flex; gap: 10px; }
    .row > div { flex: 1; }
    .pay-btn { width: 100%; margin-top: 18px; padding: 13px; background: #1b8a5a; color: #fff; border: 0; border-radius: 9px; font-size: 15px; font-weight: 600; cursor: pointer; }
    .pay-btn.alt { background: #14224a; }
    .err { background: #fdecec; color: #b3261e; border: 1px solid #f5c2c0; border-radius: 8px; padding: 9px 12px; font-size: 13px; margin-bottom: 12px; }
    .hint { font-size: 11px; color: #8a94a6; margin-top: 14px; line-height: 1.5; }
    .pane { display: none; }
    .pane.active { display: block; }
    .spinner { width: 42px; height: 42px; border: 4px solid #d5dbe6; border-top-color: #14224a; border-radius: 50%; margin: 24px auto; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .outcome { text-align: center; margin: 20px 0 12px; }
    .outcome .badge { display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; border-radius: 50%; font-size: 30px; color: #fff; animation: pop .3s ease; }
    .outcome.ok .badge { background: #1b8a5a; }
    .outcome.bad .badge { background: #b3261e; }
    @keyframes pop { from { transform: scale(0); } to { transform: scale(1); } }
    .center { text-align: center; }
    .foot { text-align: center; font-size: 11px; color: #9aa4b6; padding: 14px; }
  </style>
</head>

<body>
  <div class="card">
    <div class="brand"><b>SyamPay</b><span>Secure checkout</span></div>
    <div class="body">

      <?php if (!$linkValid): ?>
        <div class="err">This payment link is invalid or has been tampered with.</div>
        <p class="center hint">Return to the store and try again.</p>

      <?php elseif (!$payable): ?>
        <p class="center" style="margin:12px 0;">This payment has already been handled.</p>
        <p class="center"><a href="/user_order_tracking.php">Back to BeanThere</a></p>

      <?php elseif ($stage === 'posting'):
        $outcome = [
          'success' => ['ok', '✓', 'Payment approved', 'Taking you to your receipt…'],
          'declined' => ['bad', '✕', 'Payment declined', 'Returning you to the store…'],
          'insufficient' => ['bad', '✕', 'Insufficient funds', 'Returning you to the store…'],
        ][$result] ?? ['bad', '✕', 'Payment failed', 'Returning you to the store…'];
      ?>
        <div class="outcome <?= $outcome[0] ?>"><span class="badge"><?= $outcome[1] ?></span></div>
        <p class="center" style="font-weight:600;font-size:17px;margin-bottom:4px;"><?= htmlspecialchars($outcome[2]) ?></p>
        <p class="center hint" style="margin-top:0;"><?= htmlspecialchars($outcome[3]) ?></p>
        <form id="callbackForm" method="post" action="../payment_callback.php">
          <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
          <input type="hidden" name="amt" value="<?= htmlspecialchars($amt) ?>">
          <input type="hidden" name="sig" value="<?= htmlspecialchars($sig) ?>">
          <input type="hidden" name="result" value="<?= htmlspecialchars($result) ?>">
          <input type="hidden" name="method" value="<?= htmlspecialchars($method) ?>">
          <input type="hidden" name="last4" value="<?= htmlspecialchars((string)$last4) ?>">
        </form>
        <script>setTimeout(() => document.getElementById('callbackForm').submit(), 2500);</script>

      <?php elseif ($stage === 'threeds'): ?>
        <div class="demo">3-D Secure — your bank wants to confirm this RM<?= htmlspecialchars(number_format($amountValue, 2)) ?> payment.</div>
        <div class="spinner"></div>
        <form method="post" action="pay.php">
          <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
          <input type="hidden" name="amt" value="<?= htmlspecialchars($amt) ?>">
          <input type="hidden" name="sig" value="<?= htmlspecialchars($sig) ?>">
          <input type="hidden" name="channel" value="card">
          <input type="hidden" name="threeds" value="1">
          <input type="hidden" name="result" value="<?= htmlspecialchars($result) ?>">
          <input type="hidden" name="last4" value="<?= htmlspecialchars((string)$last4) ?>">
          <button type="submit" class="pay-btn alt">Confirm payment</button>
        </form>

      <?php else: ?>
        <div class="amount">RM<?= htmlspecialchars(number_format($amountValue, 2)) ?></div>
        <div class="merchant">Paying BeanThere Coffee · ref <?= htmlspecialchars($ref) ?></div>
        <div class="demo">Demo gateway — no real payment is taken. Use the test cards below.</div>

        <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="tabs">
          <button type="button" class="active" data-pane="card">Card</button>
          <button type="button" data-pane="ewallet">E-Wallet</button>
          <button type="button" data-pane="bank">Bank</button>
        </div>

        <div class="pane active" id="pane-card">
          <form method="post" action="pay.php">
            <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
            <input type="hidden" name="amt" value="<?= htmlspecialchars($amt) ?>">
            <input type="hidden" name="sig" value="<?= htmlspecialchars($sig) ?>">
            <input type="hidden" name="channel" value="card">
            <label>Card number</label>
            <input name="card_number" inputmode="numeric" placeholder="4242 4242 4242 4242" autocomplete="off" required>
            <div class="row">
              <div>
                <label>Expiry</label>
                <input name="expiry" inputmode="numeric" placeholder="MM/YY" maxlength="7" autocomplete="off" required>
              </div>
              <div>
                <label>CVV</label>
                <input name="cvv" inputmode="numeric" placeholder="123" maxlength="4" autocomplete="off" required>
              </div>
            </div>
            <button type="submit" class="pay-btn">Pay RM<?= htmlspecialchars(number_format($amountValue, 2)) ?></button>
          </form>
          <p class="hint">
            Test cards:<br>
            4242 4242 4242 4242 — success<br>
            4000 0000 0000 0002 — declined<br>
            4000 0000 0000 9995 — insufficient funds<br>
            Payments over RM50 trigger a 3-D Secure step.
          </p>
        </div>

        <div class="pane" id="pane-ewallet">
          <form method="post" action="pay.php">
            <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
            <input type="hidden" name="amt" value="<?= htmlspecialchars($amt) ?>">
            <input type="hidden" name="sig" value="<?= htmlspecialchars($sig) ?>">
            <input type="hidden" name="channel" value="ewallet">
            <button type="submit" name="sim" value="success" class="pay-btn">Simulate successful e-wallet payment</button>
            <button type="submit" name="sim" value="fail" class="pay-btn alt" style="margin-top:8px;background:#b3261e;">Simulate failed payment</button>
          </form>
        </div>

        <div class="pane" id="pane-bank">
          <form method="post" action="pay.php">
            <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
            <input type="hidden" name="amt" value="<?= htmlspecialchars($amt) ?>">
            <input type="hidden" name="sig" value="<?= htmlspecialchars($sig) ?>">
            <input type="hidden" name="channel" value="bank">
            <button type="submit" name="sim" value="success" class="pay-btn">Simulate successful bank transfer</button>
            <button type="submit" name="sim" value="fail" class="pay-btn alt" style="margin-top:8px;background:#b3261e;">Simulate failed payment</button>
          </form>
        </div>

        <script>
          document.querySelectorAll('.tabs button').forEach(btn => {
            btn.addEventListener('click', () => {
              document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
              document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
              btn.classList.add('active');
              document.getElementById('pane-' + btn.dataset.pane).classList.add('active');
            });
          });
        </script>
      <?php endif; ?>
    </div>
    <div class="foot">SyamPay is a demonstration payment simulator · not a real payment service.</div>
  </div>
</body>

</html>

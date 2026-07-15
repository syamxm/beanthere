<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/order_actions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkoutID'], $_POST['newStatus'])) {
  csrf_verify();
  [$ok, $message] = apply_group_status($conn, (string)$_POST['checkoutID'], (string)$_POST['newStatus']);
  $_SESSION['board_flash'] = $message;
  header("Location: order_board.php");
  exit();
}
$conn->close();

$flash = $_SESSION['board_flash'] ?? '';
unset($_SESSION['board_flash']);

$pageTitle = 'Barista board - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-full mx-auto px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
      <h1 class="text-2xl font-bold">Barista board</h1>
      <div class="flex items-center gap-3">
        <span id="pollState" class="text-foam text-xs">Live · updates every 5s</span>
        <a href="adminOrderManagement.php" class="text-foam hover:text-caramel text-sm">List view</a>
      </div>
    </div>

    <?php if ($flash): ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php endif; ?>

    <div id="board" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4"></div>
    <p id="boardEmpty" class="hidden text-foam text-center py-16">No live orders right now.</p>
  </main>

  <template id="cardTemplate">
    <div class="card bg-roast border border-bean rounded-2xl p-4">
      <div class="flex items-center justify-between mb-2">
        <span class="cust font-semibold"></span>
        <span class="age text-xs text-foam"></span>
      </div>
      <ul class="items text-sm text-crema mb-3 flex flex-col gap-1.5"></ul>
      <div class="actions flex gap-2"></div>
    </div>
  </template>

  <script>
    const CSRF = <?= json_encode(csrf_token()) ?>;
    const board = document.getElementById('board');
    const boardEmpty = document.getElementById('boardEmpty');
    const pollState = document.getElementById('pollState');

    function ageLabel(orderTime) {
      const mins = Math.max(0, Math.floor((Date.now() - new Date(orderTime.replace(' ', 'T')).getTime()) / 60000));
      if (mins < 1) return 'just now';
      if (mins < 60) return mins + 'm ago';
      return Math.floor(mins / 60) + 'h ago';
    }

    function actionForm(checkoutID, status, label, danger) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.className = 'inline';
      if (danger) {
        form.onsubmit = () => confirm('Cancel this order? Stock restored, points reversed, voucher not refunded.');
      }
      form.innerHTML =
        `<input type="hidden" name="csrf_token" value="${CSRF}">` +
        `<input type="hidden" name="checkoutID" value="${checkoutID}">` +
        `<input type="hidden" name="newStatus" value="${status.replace(/"/g, '&quot;')}">` +
        `<button type="submit" class="${danger ? 'btn-danger' : 'btn-caramel'} text-sm">${label}</button>`;
      return form;
    }

    function render(data) {
      board.innerHTML = '';
      let total = 0;
      const tpl = document.getElementById('cardTemplate');

      data.columns.forEach(status => {
        const groups = data.groups[status] || [];
        total += groups.length;

        const column = document.createElement('div');
        column.className = 'flex flex-col gap-3';
        column.innerHTML =
          `<h2 class="text-caramel font-semibold text-sm tracking-wide flex items-center justify-between">
             <span>${status}</span><span class="text-foam">${groups.length}</span>
           </h2>`;

        groups.forEach(group => {
          const card = tpl.content.cloneNode(true);
          card.querySelector('.cust').textContent = group.username;
          card.querySelector('.age').textContent = ageLabel(group.orderTime);
          const items = card.querySelector('.items');
          group.items.forEach(item => {
            const li = document.createElement('li');
            const opts = item.options ? `<span class="text-foam text-xs block">${escapeHtml(item.options)}</span>` : '';
            li.innerHTML = `<span class="font-semibold">${item.qty}×</span> ${escapeHtml(item.name)}${opts}`;
            items.appendChild(li);
          });
          const actions = card.querySelector('.actions');
          if (group.next) {
            actions.appendChild(actionForm(group.checkoutID, group.next, 'Mark ' + group.next, false));
          }
          actions.appendChild(actionForm(group.checkoutID, <?= json_encode(ORDER_CANCELLED) ?>, 'Cancel', true));
          column.appendChild(card);
        });

        board.appendChild(column);
      });

      boardEmpty.classList.toggle('hidden', total > 0);
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text ?? '';
      return div.innerHTML;
    }

    async function poll() {
      try {
        const res = await fetch('order_board_data.php', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(res.status);
        render(await res.json());
        pollState.textContent = 'Live · updates every 5s';
      } catch (e) {
        pollState.textContent = 'Reconnecting…';
      }
    }

    poll();
    setInterval(poll, 5000);
  </script>
</body>

</html>

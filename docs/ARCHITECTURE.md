# Architecture

Deep technical reference. For the overview, start at the
[README](../README.md).

## Layout

```
public/            user pages (Apache docroot)
public/admin/      admin pages
public/assets/     css, images, chat.js
src/               dbconn.php, csrf.php, rate_limit.php, ollama.php,
                   get_recommendation.php, partials/
scripts/           cron jobs (not web-served)
db/                schema + seed, auto-imported on first DB boot
logs/              cron logs (gitignored, outside docroot)
```

## Deployment topology

In production the app container joins the external `proxy-net` network and is
routed by the central Nginx proxy (`~/nginx`) as `beanthere.syamxm.com`,
exposed through Cloudflare Tunnel. HTTPS terminates at Cloudflare, which
reaches nginx through the `cloudflared` tunnel — there is no certbot and no
host port is exposed publicly. The `127.0.0.1:8081` binding is for
direct/local access only.

App and database both run on `Asia/Kuala_Lumpur` time (PHP `date.timezone`
and the MariaDB container's `TZ`), so opening hours and cron timing line up
with store-local time.

Static assets are cached by Cloudflare for 4 hours. `recommendation.php`
loads `chat.js` with a `?v=<filemtime>` cache-buster, so edits to that file
take effect immediately. Other static assets don't have this — after editing
them, purge in the Cloudflare dashboard or the CDN edge keeps serving the old
version until its cache expires.

## Payment flow

Checkout is a two-phase, gateway-mediated flow so the demo has real failure
paths, not just a success alert.

1. **Review** (`paymentMethod.php`) reserves stock and the voucher inside one
   transaction and writes the order as `Awaiting Payment` — no points yet —
   then redirects to the gateway with a signed reference:
   `HMAC(checkoutID|amount)` using `PAYMENT_SECRET`. Raw order IDs are never
   trusted from the URL.
2. **SyamPay** (`public/gateway/pay.php`) is a self-contained pretend PSP
   with its own look. Card payments run a Luhn check and require a future
   expiry; payments over RM50 show a fake 3-D Secure step. E-wallet and bank
   tabs are one-click success/fail simulators. Test cards:
   - `4242 4242 4242 4242` — success
   - `4000 0000 0000 0002` — declined
   - `4000 0000 0000 9995` — insufficient funds
3. **Callback** (`payment_callback.php`) verifies the HMAC and that the
   signed amount still matches the order, then acts **idempotently**
   (replaying it is a no-op — points are never doubled). On success the group
   flips to `Order Received` and points are awarded. On decline or a
   15-minute abandon (swept by `payment_expire_stale()` on the tracking page
   and by the cron) the group becomes `Payment Failed`, stock is restored and
   the voucher released. The listed test-card numbers are a feature for demo
   visitors, not a secret.

   **Decision:** points/stock/voucher effects live only in the callback
   (points) or are reserved at checkout and released on failure (stock,
   voucher). A failed payment does **not** repopulate the cart — the customer
   reorders from the menu.

4. **Receipt** (`receipt.php?ref=…`, owner-only) shows items, fee, discount,
   points earned, payment method + last 4 + gateway ref, with a print
   stylesheet and a clearly-labelled email preview (nothing is sent).

## Orders and the barista board

A checkout writes one row per drink, all sharing a random `checkoutID`, so an
order is a group, not a loose pile of rows. The delivery fee is stored once
per group in `orders.delivery_fee` (same value on each row, read once) and
shown as its own line on the tracking and admin pages — never folded into a
drink price.

- **Order management** (`adminOrderManagement.php`) shows one card per
  checkout with the legal next-status button and a Cancel button. Transitions
  are whitelisted server-side in `src/order_status.php` (an admin may advance
  one step or cancel; nothing else), and apply to the whole group.
- **Barista board** (`order_board.php`) is a counter-tablet view: columns per
  live status, a card per order with drinks and customisations, polling
  `order_board_data.php` every 5 seconds (admin-only,
  `Cache-Control: no-store`). Advancing a stale card is a friendly no-op —
  the transition is re-checked server-side, so two tabs can't double-advance.
- **Cancelling** restocks every drink and reverses the exact loyalty points
  the order earned (matched by `checkoutID` in the ledger, reversed at most
  once). The voucher is **not** refunded — once spent on an order it is
  spent. This is stated on the cancel confirmation.
- **Ready ping** — the customer tracking page polls `order_updates.php` (user
  scoped, same 5-second cadence as the board) and shows a toast plus a
  flashing tab title when a group reaches *Ready for Pickup* or *Out for
  Delivery*.
- **Reorder** — every past checkout group has a Reorder button that
  re-inserts its rows into the cart at **current** menu prices, re-checking
  stock and item existence. Removed items and price changes are flagged in
  the flash message, not silently absorbed.

## Cron

Scheduled on the host crontab, calling into the app container:

```cron
* * * * * docker exec beanthere-app php /var/www/html/scripts/update_order_status.php >/dev/null 2>&1
0 3 * * * docker exec beanthere-app php /var/www/html/scripts/assign_voucher.php >/dev/null 2>&1
```

`update_order_status.php` advances order status over time **for auto orders
only** — once an admin changes a checkout group's status (list view or
barista board), that group's `statusSource` flips to `manual` and the cron
leaves it alone, so a live barista and the unattended demo clock never fight.
It also skips `Cancelled` groups and sweeps stale `Awaiting Payment` groups.

`assign_voucher.php` grants active members the vouchers marked
`type = 'monthly'` (vouchers marked `type = 'reward'`, e.g.
`REWARD5`/`REWARD15`/`REWARD25`, are only obtainable by spending loyalty
points). A monthly voucher is granted **once per calendar month** per
member — `member_vouchers.grant_period` holds the `YYYY-MM` it was granted
for, and a unique key makes a second grant in the same month impossible.
Points redemptions leave `grant_period` NULL, so the same reward voucher can
be redeemed as often as a member can pay for it.

Both scripts refuse to run over HTTP and log to `logs/`.

## Opening hours

Per-day opening hours live in the `settings` table
(`hours_<day>_open/close`), edited from the admin dashboard (blank = closed
that day). `store_status()` consults today's schedule; the old open/closed
checkbox still exists as a **manual override** — when the override toggle is
on (or no schedule has been saved yet), the manual toggle wins in both
directions. Outside hours the storefront behaves exactly like store-closed
(banner + ordering blocked), and when open the banner shows today's hours.

## Analytics

`admin_report.php` has a date-range picker (defaults to the last 30 days):
revenue by day and by week, top items by revenue and by quantity, plus the
original all-time charts. Revenue counts only paid groups —
`Awaiting Payment`, `Payment Failed` and `Cancelled` are excluded
(`src/report_range.php`). **Export CSV** streams the range's order rows via
`fputcsv` (`export_orders_csv.php`, admin-only), one row per drink with the
group ref.

## Data integrity

- **Money is `DECIMAL(8,2)`**, not float — `cart.total` and `orders.total`.
  Line totals are recomputed as menu price × quantity on every change
  (`src/pricing.php`), so repeatedly nudging the quantity cannot drift the
  price.
- **Constraints do the enforcing.** Unique: `users.username`, `users.email`,
  `vouchers.code`, `membership.userID`,
  `member_vouchers(membershipID, voucherID, grant_period)`. Foreign keys
  cover the money and loyalty paths; deleting a menu item cascades to carts,
  and everything else is `RESTRICT` so order and points history cannot be
  silently destroyed. `users.phone_number` is deliberately *not* unique —
  families share a number. The check-then-insert paths (sign-up, membership
  application) catch the duplicate-key error as the authoritative guard; the
  prior `SELECT` only decides the friendly message.
- **Stock cannot go negative.** Checkout decrements with
  `... WHERE id = ? AND stock >= ?` inside the transaction and rolls the
  whole order back if the row count is 0 ("just sold out" — nothing charged).
- **Vouchers cannot be double-spent.** The voucher is validated with
  `SELECT ... FOR UPDATE` inside the checkout transaction, and a `used = 1`
  update that affects 0 rows rolls the checkout back.

## Recommendation chatbot

`recommendation.php` is a chat UI. It posts the message to
`recommend_api.php`, which builds a system prompt from the in-stock menu,
asks a local Ollama model for `{"drink_id", "reason"}`, and checks the id
against the menu before rendering the drink card. No drink outside the menu
can be recommended.

If Ollama is down, slow (10s timeout), or replies with anything unexpected,
the request silently falls back to the keyword scoring in
`src/get_recommendation.php`. The user always gets a drink, never an error,
so the app runs fine on a machine with no Ollama at all.

Each bot reply shows a small badge — "AI generated" or "Fallback answer" —
so you can tell at a glance which path answered. It's a debugging aid, left
visible intentionally since Ollama uptime on the homeserver isn't guaranteed.

Config (`.env`, read by both PHP and compose):

```
OLLAMA_URL=http://172.31.0.1:11434
OLLAMA_MODEL=qwen2.5:3b-instruct
```

`172.31.0.1` is the host, seen from the app container: it is the gateway of
the `internal` bridge, whose subnet is pinned in `docker-compose.yml` so the
address survives a recreate. Leave `OLLAMA_URL` empty to force the rule-based
path.

## Ollama setup

The homeserver GPU is an RTX 3050 Ti (4 GB VRAM), so stay at ~3B parameters
and Q4.

```bash
curl -fsSL https://ollama.com/install.sh | sh
ollama pull qwen2.5:3b-instruct     # or llama3.2:3b
```

The install script registers and starts a systemd unit, but it binds to
loopback only, which the app container cannot reach. Bind it to the bridge
instead:

```bash
sudo systemctl edit ollama
```

```ini
[Service]
Environment="OLLAMA_HOST=0.0.0.0:11434"
```

```bash
sudo systemctl daemon-reload && sudo systemctl restart ollama
systemctl is-enabled ollama          # should print: enabled
```

ufw drops traffic from the Docker bridge by default, so also open 11434 to
the app's subnet only — never to the LAN:

```bash
sudo ufw allow from 172.31.0.0/16 to any port 11434 proto tcp comment 'BeanThere app -> Ollama'
```

Verify from inside the container:

```bash
docker exec beanthere-app curl -s -m 3 http://172.31.0.1:11434/api/tags
```

Nothing else is needed — if this check fails the site keeps working, it just
answers with the rule-based fallback instead of the model.

# BeanThere

PHP coffee shop site. Apache + PHP 8.2, MariaDB 10.4, no framework or build step.

Live at https://beanthere.syamxm.com

This is a portfolio/demo project. Payment and account verification are simulated
— see [Demo limitations](#demo-limitations).

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

## Local Development

Everything runs in Docker with one command, on Windows, Linux, or macOS. You do
not need PHP or MariaDB installed locally — only Docker and Git.

Local runs never touch production. `beanthere.syamxm.com` is this same
homeserver, but a local run is a completely separate set of containers, its
own database, and its own `.env` — nothing you do locally reaches the live
site.

### Prerequisites

**Linux**

- [Docker Engine](https://docs.docker.com/engine/install/) with the Docker
  Compose plugin (included in modern installs — check with `docker compose version`)
- Git

**Windows**

- [Docker Desktop](https://docs.docker.com/desktop/setup/install/windows-install/)
  — requires WSL 2 (the installer sets it up; if prompted, run `wsl --install`
  in PowerShell as Administrator and reboot first)
- [Git for Windows](https://git-scm.com/downloads/win)
- Make sure Docker Desktop is **running** (whale icon in the system tray)
  before using any `docker` command

### Setup (all operating systems)

Run these in a terminal (Linux: any shell; Windows: PowerShell or Git Bash).

1. Clone and enter the project:

   ```bash
   git clone https://github.com/syamxm/BeanThere.git
   cd BeanThere
   ```

2. Create the local Docker network (one time only). Production uses this
   network for its reverse proxy; `docker-compose.yml` declares it as
   `external`, so `docker compose up` fails with `network proxy-net declared
   as external, but could not be found` until it exists — even locally, where
   nothing actually routes through it:

   ```bash
   docker network create proxy-net
   ```

   If it already exists (e.g. you're setting up other `syamxm` projects on the
   same machine) this errors with `network with name proxy-net already
   exists` — that's fine, skip the step.

3. Create your local config:

   ```bash
   cp .env.example .env
   ```

   On Windows PowerShell use `copy` instead of `cp`:

   ```powershell
   copy .env.example .env
   ```

   Then open `.env` and set real values for `DB_PASS` and `DB_ROOT_PASS` (any
   values work locally — they just need to exist). Leave `OLLAMA_URL` as-is or
   blank; see [Recommendation chatbot](#recommendation-chatbot) — the chatbot
   works locally either way, it just always uses the rule-based fallback
   unless you also stand up Ollama on your machine. Never commit `.env` — it
   is git-ignored.

4. Build and start everything:

   ```bash
   docker compose up -d --build
   ```

   The first build takes a minute or two. Later runs are much faster.

### Verify it works

Open **http://127.0.0.1:8081** in your browser. You should see the BeanThere
landing page. Go to `user_register.php`, create an account, log in, add a
drink to your cart, and check out (see [Demo limitations](#demo-limitations) —
checkout doesn't charge anything real). If that works, the app and database
are running correctly.

The database imports `db/coffeebuddydb.sql` on first boot only — seeded
accounts (`admin`, `testuser`) have no working password by default; set one
manually after first boot, see the comment block at the bottom of that file.

### Day-to-day commands

| Action | Command |
|--------|---------|
| Start (after first setup) | `docker compose up -d` |
| View logs | `docker compose logs -f app` |
| Rebuild after code changes | `docker compose up -d --build` |
| Stop | `docker compose down` |
| **Reset the local database** (wipes all local users/orders) | `docker compose down -v` |

### Windows gotchas

- **Docker Desktop must be running** before any `docker` command — otherwise
  you get `error during connect` / `cannot connect to the Docker daemon`.
- **WSL 2 is required.** If Docker Desktop complains about WSL, run
  `wsl --install` in an Administrator PowerShell and reboot.
- **Paths:** use backslashes in PowerShell (`db\coffeebuddydb.sql`) and
  forward slashes in Git Bash (`db/coffeebuddydb.sql`).

### Production (for reference — do not run locally)

In production the app container also joins the external `proxy-net` network
and is routed by the central Nginx proxy (`~/nginx`) as
`beanthere.syamxm.com`, exposed through Cloudflare Tunnel — see
[Public access](#public-access). The `127.0.0.1:8081` port binding is for
direct/local access only and is never exposed publicly.

## Public access

The nginx container (`~/nginx`) reverse-proxies `beanthere.syamxm.com` to this
app by container name over the shared `proxy-net` network. HTTPS terminates at
Cloudflare, which reaches nginx through the `cloudflared` tunnel — there is no
certbot and no host port is exposed publicly.

Static assets are cached by Cloudflare for 4 hours. `recommendation.php` loads
`chat.js` with a `?v=<filemtime>` cache-buster, so edits to that file take
effect immediately without a manual cache purge. Other static assets don't have
this — after editing them, purge the file (or purge everything) in the
Cloudflare dashboard, or the CDN edge will keep serving the old version until
its cache expires.

## Cron

Scheduled on the host crontab, calling into the app container:

```cron
* * * * * docker exec beanthere-app php /var/www/html/scripts/update_order_status.php >/dev/null 2>&1
0 3 * * * docker exec beanthere-app php /var/www/html/scripts/assign_voucher.php >/dev/null 2>&1
```

`update_order_status.php` advances order status over time **for auto orders
only** — once an admin changes a checkout group's status (list view or barista
board), that group's `statusSource` flips to `manual` and the cron leaves it
alone, so a live barista and the unattended demo clock never fight. It also
skips `Cancelled` groups. `assign_voucher.php`
grants active members the vouchers marked `type = 'monthly'` (vouchers marked
`type = 'reward'`, e.g. `REWARD5`/`REWARD15`/`REWARD25`, are only obtainable by
spending loyalty points). A monthly voucher is granted **once per calendar
month** per member — `member_vouchers.grant_period` holds the `YYYY-MM` it was
granted for, and a unique key makes a second grant in the same month impossible.
Points redemptions leave `grant_period` NULL, so the same reward voucher can be
redeemed as often as a member can pay for it. Both scripts refuse to run over
HTTP and log to `logs/`.

## Orders and the barista board

A checkout writes one row per drink, all sharing a random `checkoutID`, so an
order is a group, not a loose pile of rows. The delivery fee is stored once per
group in `orders.delivery_fee` (same value on each row, read once) and shown as
its own line on the tracking and admin pages — never folded into a drink price.

- **Order management** (`adminOrderManagement.php`) shows one card per checkout
  with the legal next-status button and a Cancel button. Transitions are
  whitelisted server-side in `src/order_status.php` (an admin may advance one
  step or cancel; nothing else), and apply to the whole group.
- **Barista board** (`order_board.php`) is a counter-tablet view: columns per
  live status, a card per order with drinks and customisations, polling
  `order_board_data.php` every 5 seconds (admin-only, `Cache-Control: no-store`).
  Advancing a stale card is a friendly no-op — the transition is re-checked
  server-side, so two tabs can't double-advance.
- **Cancelling** restocks every drink and reverses the exact loyalty points the
  order earned (matched by `checkoutID` in the ledger, reversed at most once).
  The voucher is **not** refunded — once spent on an order it is spent. This is
  stated on the cancel confirmation.

## Data integrity

- **Money is `DECIMAL(8,2)`**, not float — `cart.total` and `orders.total`. Line
  totals are recomputed as menu price × quantity on every change
  (`src/pricing.php`), so repeatedly nudging the quantity cannot drift the price.
- **Constraints do the enforcing.** Unique: `users.username`, `users.email`,
  `vouchers.code`, `membership.userID`,
  `member_vouchers(membershipID, voucherID, grant_period)`. Foreign keys cover
  the money and loyalty paths; deleting a menu item cascades to carts, and
  everything else is `RESTRICT` so order and points history cannot be silently
  destroyed. `users.phone_number` is deliberately *not* unique — families share
  a number. The check-then-insert paths (sign-up, membership application) catch
  the duplicate-key error as the authoritative guard; the prior `SELECT` only
  decides the friendly message.
- **Stock cannot go negative.** Checkout decrements with
  `... WHERE id = ? AND stock >= ?` inside the transaction and rolls the whole
  order back if the row count is 0 ("just sold out" — nothing charged).
- **Vouchers cannot be double-spent.** The voucher is validated with
  `SELECT ... FOR UPDATE` inside the checkout transaction, and a `used = 1`
  update that affects 0 rows rolls the checkout back.

## Security

- **CSRF** — `src/csrf.php`, required on every state-changing POST.
- **SQL injection** — every query is a prepared statement with bound
  parameters. There is no raw string interpolation of request input into SQL
  anywhere in the app.
- **Passwords** — `password_hash()`/`password_verify()` (bcrypt). Logins also
  re-hash on success when PHP's default algorithm/cost has moved on
  (`password_needs_rehash()`). Legacy plaintext passwords are *not* accepted
  or migrated — such accounts must reset.
- **Rate limiting** — `src/rate_limit.php`, backed by the `login_attempts`
  table (created by `db/coffeebuddydb.sql` on fresh installs — see below to add
  it to an existing database). Two counters run in parallel:
  - 5 failed logins per **username+IP** (`user:<username>|<ip>`) locks that
    pair out for 15 minutes;
  - 20 failed logins per **IP** (`ip:login|<ip>`) locks the IP for 15 minutes,
    so rotating through usernames from one host gets throttled too.

  The same table also throttles sign-ups (10 per IP / 15 min) and the
  recommendation chatbot (10 per session and per IP / minute — over the limit
  the reply still comes, but from the rule-based path, so Ollama is protected).
- **Input whitelisting** — order status, voucher status and every drink
  attribute (type, roast, caffeine, sugar, milk, syrups, toppings) are checked
  against a server-side allowlist; the browser form is never trusted.
- **Registration rules** — password ≥ 6 characters, username ≤ 25, profile
  fields capped to their column sizes, all enforced server-side.
- **XSS** — all dynamic output goes through `htmlspecialchars()` or a numeric
  cast before reaching HTML.
- **Session fixation** — `session_regenerate_id(true)` on every successful
  login (user and admin).
- **Session hygiene** — logging in as a user clears any admin session keys and
  vice versa (`src/session_role.php`). Residual limitation: both roles still
  share one session cookie name, so a single browser cannot hold a user and an
  admin session at the same time — the newer login wins. Separate cookie names
  would fix it and are out of scope for the demo.
- **PHP hardening** (`php/production.ini`, baked into the image) —
  `display_errors=Off`, `log_errors=On` to stderr (so stack traces land in
  `docker logs beanthere-app`, never in a response), `expose_php=Off`, and
  session cookies with `HttpOnly`, `SameSite=Lax` and `session.use_strict_mode`.
  Set `SESSION_COOKIE_SECURE=1` in `.env` on an HTTPS deployment to add the
  `Secure` flag; it defaults to `0` so local HTTP development keeps working.
- **Security headers** — set by Apache in the app container
  (`apache/security-headers.conf`), possible because the site makes zero
  third-party requests — Tailwind, fonts, icons and Chart.js
  (`public/assets/vendor/chart.umd.min.js`, pinned to 4.4.7) are all
  self-hosted:
  - `Content-Security-Policy: default-src 'self'; script-src 'self'
    'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;
    font-src 'self'; connect-src 'self'; base-uri 'self'; form-action 'self';
    frame-ancestors 'none'` — `'unsafe-inline'` is kept for scripts/styles
    because the app uses small per-page inline `<script>` blocks (menu
    toggles, price calculators, drag reorder) and inline style attributes;
    none of them interpolate raw user input (values pass through
    `json_encode`/`htmlspecialchars`). Tightening to nonces is possible later
    by moving those blocks into files.
  - `X-Content-Type-Options: nosniff`, `Referrer-Policy:
    strict-origin-when-cross-origin`, `X-Frame-Options: DENY`,
    `Permissions-Policy: camera=(), microphone=(), geolocation=()`.
  - If the host nginx (or Cloudflare) also sets any of these, remove the
    duplicate from one side — duplicated CSP headers get combined and the
    strictest wins, but duplicated `X-Frame-Options` can confuse browsers.

## DevSecOps pipeline

```
lint ──┐
       ├──▶ build ──▶ trivy ──▶ (merge) ──▶ deploy
semgrep┘
gitleaks (parallel, plus weekly full-history scheduled scan)
```

One line per gate:

- **lint** — `php -l` on every PHP file; catches syntax errors before anything else runs.
- **semgrep** (`p/php` + `p/security-audit`, ERROR severity) — static analysis for injection, XSS and insecure-code patterns; false positives are suppressed inline with `// nosemgrep: rule-id -- reason`.
- **gitleaks** — scans commits for committed secrets (API keys, passwords); runs on every push/PR and a full-history sweep every Monday.
- **build** — the Docker image must build (includes the Tailwind compile stage).
- **trivy fs** — Dockerfile/compose misconfiguration scan (accepted exceptions live in `.trivyignore`, one comment each).
- **trivy image** — HIGH/CRITICAL CVE scan of the built image; `ignore-unfixed` because base-image CVEs without a Debian fix are not actionable here.
- **deploy** — over Tailscale SSH (see `deploy.yml`). Triggered by
  `workflow_run`: it waits for CI on `main` to finish and only runs when the
  conclusion is `success`, so a red pipeline never reaches the server.

### Applying `login_attempts` to an existing database

If you're upgrading a database that predates this table (i.e. it was seeded
before this change), run once:

```bash
docker exec beanthere-db mariadb -u root -p"$DB_ROOT_PASS" coffeebuddydb -e "
CREATE TABLE IF NOT EXISTS login_attempts (
  identifier varchar(191) NOT NULL,
  attempts int(11) NOT NULL DEFAULT 1,
  first_attempt_at datetime NOT NULL,
  locked_until datetime DEFAULT NULL,
  PRIMARY KEY (identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
"
```

## Demo limitations

Not a real e-commerce backend — nothing here charges money or sends messages.

- **Payment** (`paymentMethod.php`) — card/e-wallet/bank fields are collected
  and validated client-side (expiry, non-empty) but never sent to a real
  payment gateway. Submitting places the order immediately. Labeled "Demo" in
  the UI; don't enter a real card number.
- **Account verification** (`user_verify.php`) — no OTP or email is actually
  sent. Choosing "verify via phone/email" marks the account verified
  instantly. Labeled "Demo" in the UI.

## Recommendation chatbot

`recommendation.php` is a chat UI. It posts the message to `recommend_api.php`,
which builds a system prompt from the in-stock menu, asks a local Ollama model
for `{"drink_id", "reason"}`, and checks the id against the menu before
rendering the drink card. No drink outside the menu can be recommended.

If Ollama is down, slow (10s timeout), or replies with anything unexpected, the
request silently falls back to the keyword scoring in
`src/get_recommendation.php`. The user always gets a drink, never an error, so
the app runs fine on a machine with no Ollama at all.

Each bot reply shows a small badge — "AI generated" or "Fallback answer" —
so you can tell at a glance which path answered. It's a debugging aid, left
visible intentionally since Ollama uptime on the homeserver isn't guaranteed.

Config (`.env`, read by both PHP and compose):

```
OLLAMA_URL=http://172.31.0.1:11434
OLLAMA_MODEL=qwen2.5:3b-instruct
```

`172.31.0.1` is the host, seen from the app container: it is the gateway of the
`internal` bridge, whose subnet is pinned in `docker-compose.yml` so the address
survives a recreate. Leave `OLLAMA_URL` empty to force the rule-based path.

### Ollama on the homeserver

The GPU is an RTX 3050 Ti (4 GB VRAM), so stay at ~3B parameters and Q4.

```bash
curl -fsSL https://ollama.com/install.sh | sh
ollama pull qwen2.5:3b-instruct     # or llama3.2:3b
```

The install script registers and starts a systemd unit, but it binds to loopback
only, which the app container cannot reach. Bind it to the bridge instead:

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

ufw drops traffic from the Docker bridge by default, so also open 11434 to the
app's subnet only — never to the LAN:

```bash
sudo ufw allow from 172.31.0.0/16 to any port 11434 proto tcp comment 'BeanThere app -> Ollama'
```

Verify from inside the container:

```bash
docker exec beanthere-app curl -s -m 3 http://172.31.0.1:11434/api/tags
```

Nothing else is needed — if this check fails the site keeps working, it just
answers with the rule-based fallback instead of the model.

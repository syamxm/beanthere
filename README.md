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

## Run

```bash
cp .env.example .env     # then edit the passwords
docker compose up -d --build
```

Serves on `127.0.0.1:8081`. The database imports `db/*.sql` on first boot only;
to reimport, drop the volume with `docker compose down -v`.

Seeded accounts (`admin`, `testuser`) have no working password by default —
set one manually after first boot; see the comment block at the bottom of
`db/coffeebuddydb.sql`.

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

`update_order_status.php` advances order status over time; `assign_voucher.php`
grants valid vouchers to active members. Both refuse to run over HTTP and log to
`logs/`.

## Security

- **CSRF** — `src/csrf.php`, required on every state-changing POST.
- **SQL injection** — every query is a prepared statement with bound
  parameters. There is no raw string interpolation of request input into SQL
  anywhere in the app.
- **Passwords** — `password_hash()`/`password_verify()` (bcrypt). Logins also
  transparently upgrade any legacy plaintext-equality match to a proper hash
  on next successful login.
- **Login rate limiting** — `src/rate_limit.php`, shared by `user_login.php`
  and `admin_login.php`. 5 failed attempts per username+IP locks that pair out
  for 15 minutes; the DB table `login_attempts` tracks it (created by
  `db/coffeebuddydb.sql` on fresh installs — see below to add it to an
  existing database).
- **XSS** — all dynamic output goes through `htmlspecialchars()` or a numeric
  cast before reaching HTML.
- **Session fixation** — `session_regenerate_id(true)` on every successful
  login (user and admin).

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

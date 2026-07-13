# BeanThere

PHP coffee shop site. Apache + PHP 8.2, MariaDB 10.4, no framework or build step.

Live at https://beanthere.syamxm.com

## Layout

```
public/          user pages (Apache docroot)
public/admin/    admin pages
public/assets/   css, images
src/             dbconn.php, csrf.php, ollama.php, get_recommendation.php, partials/
scripts/         cron jobs (not web-served)
db/              schema + seed, auto-imported on first DB boot
logs/            cron logs (gitignored, outside docroot)
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

## Cron

Scheduled on the host crontab, calling into the app container:

```cron
* * * * * docker exec beanthere-app php /var/www/html/scripts/update_order_status.php >/dev/null 2>&1
0 3 * * * docker exec beanthere-app php /var/www/html/scripts/assign_voucher.php >/dev/null 2>&1
```

`update_order_status.php` advances order status over time; `assign_voucher.php`
grants valid vouchers to active members. Both refuse to run over HTTP and log to
`logs/`.

## Recommendation chatbot

`recommendation.php` is a chat UI. It posts the message to `recommend_api.php`,
which builds a system prompt from the in-stock menu, asks a local Ollama model
for `{"drink_id", "reason"}`, and checks the id against the menu before
rendering the drink card. No drink outside the menu can be recommended.

If Ollama is down, slow (10s timeout), or replies with anything unexpected, the
request silently falls back to the keyword scoring in
`src/get_recommendation.php`. The user always gets a drink, never an error, so
the app runs fine on a machine with no Ollama at all.

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

# BeanThere

PHP coffee shop site. Apache + PHP 8.2, MariaDB 10.4, no framework or build step.

Live at https://beanthere.syamxm.com

## Layout

```
public/          user pages (Apache docroot)
public/admin/    admin pages
public/assets/   css, images
src/             dbconn.php, get_recommendation.php
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

Seeded accounts: `admin` / `admin123`, `testuser` / `test123`.

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

## Recommendations

`src/get_recommendation.php` filters the menu by the answers from the
recommendation form. It is the single integration point for the local model
service planned later — swap the body of `get_recommendation()` for one HTTP
call.

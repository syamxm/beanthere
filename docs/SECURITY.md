# Security model & CI/CD pipeline

Deep technical reference. For the overview, start at the
[README](../README.md).

## Application security

- **CSRF** — `src/csrf.php`, required on every state-changing POST.
- **SQL injection** — every query is a prepared statement with bound
  parameters. There is no raw string interpolation of request input into SQL
  anywhere in the app.
- **Passwords** — `password_hash()`/`password_verify()` (bcrypt). Logins
  also re-hash on success when PHP's default algorithm/cost has moved on
  (`password_needs_rehash()`). Legacy plaintext passwords are *not* accepted
  or migrated — such accounts must reset.
- **Rate limiting** — `src/rate_limit.php`, backed by the `login_attempts`
  table (created by `db/coffeebuddydb.sql` on fresh installs — see below to
  add it to an existing database). Two counters run in parallel:
  - 5 failed logins per **username+IP** (`user:<username>|<ip>`) locks that
    pair out for 15 minutes;
  - 20 failed logins per **IP** (`ip:login|<ip>`) locks the IP for
    15 minutes, so rotating through usernames from one host gets throttled
    too.

  The same table also throttles sign-ups (10 per IP / 15 min) and the
  recommendation chatbot (10 per session and per IP / minute — over the
  limit the reply still comes, but from the rule-based path, so Ollama is
  protected).
- **Input whitelisting** — order status, voucher status and every drink
  attribute (type, roast, caffeine, sugar, milk, syrups, toppings) are
  checked against a server-side allowlist; the browser form is never
  trusted.
- **Registration rules** — password ≥ 6 characters, username ≤ 25, profile
  fields capped to their column sizes, all enforced server-side.
- **XSS** — all dynamic output goes through `htmlspecialchars()` or a
  numeric cast before reaching HTML.
- **Session fixation** — `session_regenerate_id(true)` on every successful
  login (user and admin).
- **Session hygiene** — logging in as a user clears any admin session keys
  and vice versa (`src/session_role.php`). Residual limitation: both roles
  still share one session cookie name, so a single browser cannot hold a
  user and an admin session at the same time — the newer login wins.
  Separate cookie names would fix it and are out of scope for the demo.
- **Payment references** — the mock gateway handoff is signed with
  `HMAC(checkoutID|amount)` using `PAYMENT_SECRET`, and the callback
  re-verifies both the signature and that the signed amount still matches
  the order. See [ARCHITECTURE.md](ARCHITECTURE.md#payment-flow).
- **PHP hardening** (`php/production.ini`, baked into the image) —
  `display_errors=Off`, `log_errors=On` to stderr (so stack traces land in
  `docker logs beanthere-app`, never in a response), `expose_php=Off`, and
  session cookies with `HttpOnly`, `SameSite=Lax` and
  `session.use_strict_mode`. Set `SESSION_COOKIE_SECURE=1` in `.env` on an
  HTTPS deployment to add the `Secure` flag; it defaults to `0` so local
  HTTP development keeps working.

## Security headers

Set by Apache in the app container (`apache/security-headers.conf`),
possible because the site makes zero third-party requests — Tailwind, fonts,
icons and Chart.js (`public/assets/vendor/chart.umd.min.js`, pinned to
4.4.7) are all self-hosted:

- `Content-Security-Policy: default-src 'self'; script-src 'self'
  'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;
  font-src 'self'; connect-src 'self'; base-uri 'self'; form-action 'self';
  frame-ancestors 'none'` — `'unsafe-inline'` is kept for scripts/styles
  because the app uses small per-page inline `<script>` blocks (menu
  toggles, price calculators, drag reorder) and inline style attributes;
  none of them interpolate raw user input (values pass through
  `json_encode`/`htmlspecialchars`). Tightening to nonces is possible later
  by moving those blocks into files.
- `X-Content-Type-Options: nosniff`,
  `Referrer-Policy: strict-origin-when-cross-origin`,
  `X-Frame-Options: DENY`,
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

- **lint** — `php -l` on every PHP file; catches syntax errors before
  anything else runs.
- **semgrep** (`p/php` + `p/security-audit`, ERROR severity) — static
  analysis for injection, XSS and insecure-code patterns; false positives
  are suppressed inline with `// nosemgrep: rule-id -- reason`.
- **gitleaks** — scans commits for committed secrets (API keys, passwords);
  runs on every push/PR and a full-history sweep every Monday.
- **build** — the Docker image must build (includes the Tailwind compile
  stage).
- **trivy fs** — Dockerfile/compose misconfiguration scan (accepted
  exceptions live in `.trivyignore`, one comment each).
- **trivy image** — HIGH/CRITICAL CVE scan of the built image;
  `ignore-unfixed` because base-image CVEs without a Debian fix are not
  actionable here.
- **deploy** — over Tailscale SSH (see `deploy.yml`). Triggered by
  `workflow_run`: it waits for CI on `main` to finish and only runs when the
  conclusion is `success`, so a red pipeline never reaches the server.

## Applying `login_attempts` to an existing database

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

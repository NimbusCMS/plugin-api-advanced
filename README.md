# NimbusCMS Advanced API

Advanced programmatic-access features for [NimbusCMS](https://github.com/NimbusCMS/nimbus) —
an **official plugin**, and a home for API "pro" capabilities that don't belong
in the lean core. The first is a **security audit log** of API access failures.

## What it does

Every refused API request is recorded server-side into a table this plugin owns,
and an **API audit** page appears in the admin showing:

- a 24-hour summary of rejected tokens and scope denials,
- the most recent failures — when, kind, the token (for scope denials), IP and path.

Two things are recorded, from core's best-effort events:

- **`token_rejected`** — a request with no bearer token, or one that didn't
  resolve (invalid / expired / revoked / paused). **The presented token is never
  stored** — only the reason, IP, and path.
- **`access_denied`** — a *valid* token refused by scope, with the token's id and
  name and the `resource:action` it was denied.

These events fire only *after* Nimbus's per-IP flood guard, so a flood is already
rate-limited before it reaches the log. Recording happens on isolated events, so
it can never slow or break an API response.

## Install

```bash
composer require nimbuscms/api-advanced
php bin/nimbus migrate      # creates the audit table
```

Discovery is automatic. Disable it without uninstalling in `config/plugins.php`:

```php
return ['nimbuscms.api-advanced' => false];
```

## What it demonstrates

This is the **second unrelated consumer** (after Analytics) of NimbusCMS's plugin
**event** and **storage** capabilities — the independent proof both were waiting
for. It uses only the public plugin contract: its own migration + table (ADR
0005), event listeners, and an admin page.

## Requirements

- PHP 8.2+, ext-json
- NimbusCMS (the events, migrations, storage and admin-page capabilities, plus
  the `api.token_rejected` / `api.access_denied` events)

## License

MIT.

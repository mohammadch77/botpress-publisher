# BotPress Publisher

A WordPress plugin: manage Telegram/Bale bot channels and publish WordPress posts to them, immediately or on a schedule, from a Vue admin dashboard embedded in wp-admin.

## Stack
- WordPress plugin, native PHP 7.4+ (no framework)
- Admin dashboard: Vue 3 + TypeScript + Vite, Pinia for state, Tailwind for styling
- WP REST API namespace: `botpress/v1`
- Scheduling: WP-Cron with a custom `every_minute` schedule

## Structure
- `includes/bot/` — bot drivers behind `interface-bot-driver.php` (Telegram, Bale); add a new platform by implementing the interface and registering it in `class-driver-factory.php`.
- `includes/publisher/` — publishing engine (`class-publisher-engine.php`) orchestrates `class-channel-publisher.php` (bot channels) and `class-wordpress-publisher.php` (WP itself).
- `includes/scheduler/` — `class-cron-scheduler.php` (WP-Cron hooks) + `class-queue-manager.php` (queue state, retry, cancellation).
- `includes/api/class-rest-api.php` — all REST endpoints; every mutating route requires a valid WP nonce.
- `src/` — the Vue admin SPA (not loaded via npm from WordPress; build output goes to `admin/` and is enqueued by `class-plugin.php::enqueue_assets()` only on the plugin's own admin page).

## Required practices
- **Nonce + capability checks on every REST route.** No endpoint under `botpress/v1` skips `wp_verify_nonce` or a `current_user_can` check.
- **Encrypt bot tokens at rest** — use `class-encryption.php`, never store a raw token in `wp_options` or the database.
- **Webhook idempotency** — the Telegram/Bale webhook handler (`class-webhook-handler.php`) must stay idempotent and enforce a payload size limit; don't remove the dedup/rate-limit logic when adding commands.
- **Validate all REST input** server-side, even though the Vue dashboard also validates — never trust the client.
- **Uninstall cleanly** — any new option, table, or scheduled hook must be removed in `uninstall.php`.

## Admin dashboard (`src/`)
- Build with `npm run build` (runs `vue-tsc --noEmit` then `vite build`); output is consumed by the PHP plugin from `admin/`.
- State lives in Pinia stores (`stores/app.ts`, `stores/auth.ts`); API calls go through `utils/api.ts`, which attaches the WP REST nonce automatically.
- New screens go in `src/pages/` and are registered in `src/router/index.ts`.

## Git workflow
- Repo: github.com/mohammadch77/botpress-publisher (private)
- Commit messages describe the user-visible or architectural change, not the file list.

## Current status
See `readme.txt` changelog for the released state: core plugin foundation (schema, REST stubs, admin UI shell) plus a full security-hardening pass are done (v1.0.0). AI-assisted article generation is the active in-development feature — not yet released.

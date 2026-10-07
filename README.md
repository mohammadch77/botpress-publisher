# BotPress Publisher

A WordPress plugin that lets you manage Telegram and Bale bot channels directly from your WordPress admin, and publish posts to those channels — immediately or on a schedule — without leaving the WordPress dashboard.

The backend is a native WordPress plugin (PHP 7.4+), and the admin dashboard is a standalone Vue 3 + TypeScript single-page app embedded in the WordPress admin screen.

## Features

- Connect multiple Telegram and Bale bot channels from one place.
- Publish WordPress posts to one or more channels, immediately or scheduled.
- Full bot command set for managing content from inside Telegram/Bale: `/posts`, `/search`, `/publish`, `/schedule`, `/cancel`, `/pending`, `/channels`, `/status`, `/help`, `/post_detail`.
- Per-platform message templates with live preview.
- Publishing queue with retry, cancellation, and activity logs.
- Production-hardened: rate limiting on the bot webhook, REST API input validation and nonce verification, webhook idempotency and payload size limits, encrypted bot token storage, centralized error logging.

## Architecture

```
botpress-publisher/
├── botpress-publisher.php     # Plugin bootstrap
├── includes/
│   ├── api/                   # WordPress REST API endpoints (botpress/v1)
│   ├── bot/                   # Bot drivers (Telegram, Bale), command router, webhook handler
│   ├── publisher/             # Publishing engine: channel + WordPress publishers
│   ├── scheduler/             # Cron-based scheduling and queue management
│   └── helpers/                # Encryption, message templating
├── admin/                     # Compiled admin assets (enqueued into wp-admin)
└── src/                       # Vue 3 + TypeScript admin dashboard source
    ├── pages/                 # Dashboard, Channels, Queue, Templates, Logs, Bot Settings
    ├── stores/                 # Pinia state (app, auth)
    └── router/
```

The PHP side exposes a REST API under `botpress/v1` (secured with WordPress nonces), a bot-driver abstraction (`interface-bot-driver.php`) so Telegram and Bale are interchangeable, and a scheduler built on WP-Cron with a custom "every minute" schedule for timely queue processing. The Vue dashboard talks to that REST API and is the only UI surface — there are no legacy PHP admin screens.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Node.js 18+ (to build the admin dashboard from source)

## Installation

1. Upload the plugin to `/wp-content/plugins/botpress-publisher`.
2. Activate it from the WordPress **Plugins** screen.
3. Open the **BotPress** menu item in wp-admin to connect your Telegram/Bale bot(s) and configure channels.

## Developing the admin dashboard

```bash
cd src
npm install
npm run dev      # local dev server
npm run build    # type-checks (vue-tsc) and builds into admin/
```

## Security

This plugin was built with a dedicated hardening pass: REST input validation, nonce verification on every mutating request, rate limiting and idempotency on the incoming bot webhook, payload size limits, output sanitization, encrypted storage for bot tokens, and a complete uninstall routine that removes plugin data on deletion.

## License

GPLv2 or later — see [license](https://www.gnu.org/licenses/gpl-2.0.html).

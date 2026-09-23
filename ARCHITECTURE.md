# BotPress Publisher — Complete Project Specification
**Version:** 1.0.0 — Pre-Implementation  
**Stack:** Laravel 11 · PHP 8.3 · MySQL 8 · Vue 3 + TypeScript + Tailwind · WordPress Plugin

---

## 1. Product Definition

**BotPress Publisher** یک پلتفرم Multi-Tenant مدیریت و انتشار محتواست که رابط اصلی کاربر Bot-first است.

کاربر از طریق Telegram یا Bale Bot محتوا می‌سازد، ویرایش می‌کند، زمان‌بندی می‌کند و منتشر می‌کند. مقصدها می‌توانند WordPress، Telegram Channel/Group، یا Bale Channel/Group باشند.

**اصول اولیه:**
- Bot = Interface، نه Logic
- Business Logic = Application/Domain Layer
- Content از Destination مستقل است
- Publication مستقل و Idempotent است
- Multi-Tenant از روز اول

---

## 2. System Architecture

```
┌─────────────────────────────────────────┐
│              ADMIN PANEL                │
│         (Vue 3 + TypeScript)            │
└────────────────┬────────────────────────┘
                 │ REST API
┌────────────────▼────────────────────────┐
│            LARAVEL CORE                 │
│  ┌──────────┐ ┌──────────┐ ┌─────────┐ │
│  │ Auth &   │ │ Content  │ │Schedule │ │
│  │ Identity │ │ Engine   │ │ Engine  │ │
│  └──────────┘ └──────────┘ └─────────┘ │
│  ┌──────────┐ ┌──────────┐ ┌─────────┐ │
│  │ Bot      │ │ Publish  │ │ Media   │ │
│  │ Engine   │ │ Engine   │ │ Engine  │ │
│  └──────────┘ └──────────┘ └─────────┘ │
└──┬──────────────────────────────────┬───┘
   │                                  │
┌──▼──────────┐              ┌────────▼──────┐
│ TELEGRAM    │              │  BALE BOT     │
│ BOT ADAPTER │              │  ADAPTER      │
└─────────────┘              └───────────────┘
         │                           │
         └─────────┬─────────────────┘
                   │ User
         ┌─────────▼──────────┐
         │  PUBLISHING LAYER  │
         └──┬──────────┬──────┘
            │          │
┌───────────▼──┐  ┌────▼────────────┐
│  WORDPRESS   │  │  BALE/TELEGRAM  │
│  PUBLISHER   │  │  PUBLISHER      │
└──────────────┘  └─────────────────┘
```

---

## 3. Domain Model

```
Tenant
  └── User (Platform Identity: Telegram / Bale)
        └── Role / Permission
              └── Destination (WordPress Site | Telegram Channel | Bale Channel | ...)
                    └── Content
                          └── Publication
                                └── Schedule
```

---

## 4. Database Architecture — Full Schema

> قانون: هر جدول باید `tenant_id` داشته باشد. Soft Delete روی موجودیت‌های اصلی. UUID برای IDهایی که به خارج expose می‌شوند.

---

### 4.1 جداول Core / Multi-Tenant

```sql
-- ===================================================
-- TENANTS
-- ===================================================
CREATE TABLE tenants (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL UNIQUE,
    name            VARCHAR(191) NOT NULL,
    slug            VARCHAR(100) NOT NULL UNIQUE,
    plan            ENUM('free','pro','enterprise') NOT NULL DEFAULT 'free',
    status          ENUM('active','suspended','trial','cancelled') NOT NULL DEFAULT 'active',
    settings        JSON NULL,                    -- tenant-level config
    trial_ends_at   TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,
    INDEX idx_tenants_slug (slug),
    INDEX idx_tenants_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- USERS
-- ===================================================
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL UNIQUE,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(191) NOT NULL,
    email           VARCHAR(191) NULL UNIQUE,
    password        VARCHAR(255) NULL,             -- NULL اگر فقط از طریق Bot وارد شده
    status          ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
    last_seen_at    TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_users_tenant (tenant_id),
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ROLES
-- ===================================================
CREATE TABLE roles (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NULL,          -- NULL = System Role
    name            VARCHAR(100) NOT NULL,
    slug            VARCHAR(100) NOT NULL,
    description     TEXT NULL,
    is_system       TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_roles_tenant_slug (tenant_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- PERMISSIONS
-- ===================================================
CREATE TABLE permissions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(191) NOT NULL UNIQUE,
    group           VARCHAR(100) NOT NULL,          -- content, publish, schedule, admin ...
    description     TEXT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ROLE_PERMISSIONS
-- ===================================================
CREATE TABLE role_permissions (
    role_id         BIGINT UNSIGNED NOT NULL,
    permission_id   BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- USER_ROLES
-- ===================================================
CREATE TABLE user_roles (
    user_id         BIGINT UNSIGNED NOT NULL,
    role_id         BIGINT UNSIGNED NOT NULL,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    assigned_by     BIGINT UNSIGNED NULL,
    assigned_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.2 جداول Platform Identity

```sql
-- ===================================================
-- PLATFORM_IDENTITIES
-- ربط هویت Telegram/Bale به User
-- ===================================================
CREATE TABLE platform_identities (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NULL,          -- NULL = هنوز verify نشده
    tenant_id           BIGINT UNSIGNED NULL,
    platform            ENUM('telegram','bale') NOT NULL,
    external_user_id    VARCHAR(191) NOT NULL,          -- Telegram user_id
    external_username   VARCHAR(191) NULL,              -- @handle
    display_name        VARCHAR(191) NULL,
    language_code       VARCHAR(20) NULL,
    is_bot              TINYINT(1) NOT NULL DEFAULT 0,
    is_verified         TINYINT(1) NOT NULL DEFAULT 0,
    verification_code   VARCHAR(64) NULL,
    verification_token  VARCHAR(255) NULL,
    verified_at         TIMESTAMP NULL,
    metadata            JSON NULL,                      -- extra platform data
    last_interaction_at TIMESTAMP NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    UNIQUE KEY uk_platform_identity (platform, external_user_id),
    INDEX idx_pi_user (user_id),
    INDEX idx_pi_tenant (tenant_id),
    INDEX idx_pi_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.3 جداول Bot

```sql
-- ===================================================
-- BOTS
-- ===================================================
CREATE TABLE bots (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL UNIQUE,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    platform        ENUM('telegram','bale') NOT NULL,
    name            VARCHAR(191) NOT NULL,
    username        VARCHAR(191) NULL,
    token_encrypted TEXT NOT NULL,                 -- AES-256 encrypted
    token_hash      VARCHAR(64) NOT NULL,          -- SHA-256 for lookup (never logged)
    webhook_url     VARCHAR(500) NULL,
    webhook_secret  VARCHAR(255) NULL,             -- encrypted
    status          ENUM('active','inactive','error','pending') NOT NULL DEFAULT 'pending',
    last_error      TEXT NULL,
    last_error_at   TIMESTAMP NULL,
    last_webhook_at TIMESTAMP NULL,
    metadata        JSON NULL,                     -- bot info از API
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_bots_tenant (tenant_id),
    INDEX idx_bots_platform (platform),
    INDEX idx_bots_status (status),
    INDEX idx_bots_token_hash (token_hash)         -- برای پیدا کردن bot از webhook
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.4 جداول Conversation Engine

```sql
-- ===================================================
-- CONVERSATION_SESSIONS
-- ===================================================
CREATE TABLE conversation_sessions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                CHAR(36) NOT NULL UNIQUE,
    bot_id              BIGINT UNSIGNED NOT NULL,
    platform_identity_id BIGINT UNSIGNED NOT NULL,
    user_id             BIGINT UNSIGNED NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    chat_id             VARCHAR(191) NOT NULL,       -- telegram/bale chat id
    chat_type           ENUM('private','group','supergroup','channel') NOT NULL DEFAULT 'private',
    current_flow        VARCHAR(100) NULL,           -- e.g. create_wordpress_post
    current_step        VARCHAR(100) NULL,           -- e.g. awaiting_title
    context             JSON NULL,                   -- draft_id, selected_site_id, ...
    message_id          BIGINT NULL,                 -- last bot message (for edit)
    status              ENUM('active','completed','cancelled','expired') NOT NULL DEFAULT 'active',
    expires_at          TIMESTAMP NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_identity_id) REFERENCES platform_identities(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_cs_bot_chat (bot_id, chat_id),
    INDEX idx_cs_status (status),
    INDEX idx_cs_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- CONVERSATION_HISTORY
-- برای Audit و Debug
-- ===================================================
CREATE TABLE conversation_history (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id          BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    direction           ENUM('incoming','outgoing') NOT NULL,
    message_type        VARCHAR(50) NOT NULL,        -- text, photo, document, callback_query ...
    external_message_id VARCHAR(191) NULL,
    content             TEXT NULL,                   -- sanitized content
    metadata            JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES conversation_sessions(id) ON DELETE CASCADE,
    INDEX idx_ch_session (session_id),
    INDEX idx_ch_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.5 جداول Destination

```sql
-- ===================================================
-- DESTINATIONS
-- جدول abstract والد
-- ===================================================
CREATE TABLE destinations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL UNIQUE,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    type            ENUM('wordpress_site','telegram_channel','telegram_group','bale_channel','bale_group') NOT NULL,
    name            VARCHAR(191) NOT NULL,
    slug            VARCHAR(191) NULL,
    status          ENUM('active','inactive','error','pending') NOT NULL DEFAULT 'pending',
    last_error      TEXT NULL,
    last_error_at   TIMESTAMP NULL,
    last_sync_at    TIMESTAMP NULL,
    metadata        JSON NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_dest_tenant (tenant_id),
    INDEX idx_dest_type (type),
    INDEX idx_dest_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- WORDPRESS_SITES
-- ===================================================
CREATE TABLE wordpress_sites (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id          BIGINT UNSIGNED NOT NULL UNIQUE,
    tenant_id               BIGINT UNSIGNED NOT NULL,
    url                     VARCHAR(500) NOT NULL,
    api_key_encrypted       TEXT NOT NULL,
    api_key_hash            VARCHAR(64) NOT NULL,
    plugin_version          VARCHAR(20) NULL,
    wp_version              VARCHAR(20) NULL,
    -- Discovered capabilities
    has_elementor           TINYINT(1) NOT NULL DEFAULT 0,
    has_rank_math           TINYINT(1) NOT NULL DEFAULT 0,
    has_yoast               TINYINT(1) NOT NULL DEFAULT 0,
    seo_plugin              VARCHAR(50) NULL,        -- 'rank_math','yoast','none','other'
    -- Discovery metadata
    site_profile            JSON NULL,               -- capabilities, post_types, taxonomies
    content_profile         JSON NULL,               -- analyzed patterns
    discovery_completed_at  TIMESTAMP NULL,
    discovery_status        ENUM('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_wp_sites_tenant (tenant_id),
    INDEX idx_wp_sites_url (url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TELEGRAM_CHANNELS (و BALE یک ساختار مشابه)
-- ===================================================
CREATE TABLE telegram_destinations (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id      BIGINT UNSIGNED NOT NULL UNIQUE,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    bot_id              BIGINT UNSIGNED NOT NULL,    -- کدام bot این channel را manage می‌کند
    external_chat_id    VARCHAR(191) NOT NULL,
    title               VARCHAR(255) NULL,
    username            VARCHAR(191) NULL,
    invite_link         VARCHAR(500) NULL,
    member_count        INT UNSIGNED NULL,
    bot_is_admin        TINYINT(1) NOT NULL DEFAULT 0,
    permissions         JSON NULL,                   -- bot permissions در channel
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    INDEX idx_tg_dest_tenant (tenant_id),
    INDEX idx_tg_dest_bot (bot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bale_destinations (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id      BIGINT UNSIGNED NOT NULL UNIQUE,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    bot_id              BIGINT UNSIGNED NOT NULL,
    external_chat_id    VARCHAR(191) NOT NULL,
    title               VARCHAR(255) NULL,
    username            VARCHAR(191) NULL,
    invite_link         VARCHAR(500) NULL,
    member_count        INT UNSIGNED NULL,
    bot_is_admin        TINYINT(1) NOT NULL DEFAULT 0,
    permissions         JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    INDEX idx_bale_dest_tenant (tenant_id),
    INDEX idx_bale_dest_bot (bot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- DESTINATION_USERS
-- کدام user به کدام destination دسترسی دارد
-- ===================================================
CREATE TABLE destination_users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id  BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    permission      ENUM('view','publish','manage') NOT NULL DEFAULT 'publish',
    granted_by      BIGINT UNSIGNED NULL,
    granted_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_du_destination_user (destination_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.6 جداول WordPress Discovery

```sql
-- ===================================================
-- WP_POST_TYPES
-- ===================================================
CREATE TABLE wp_post_types (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wordpress_site_id   BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    slug                VARCHAR(100) NOT NULL,
    label               VARCHAR(191) NULL,
    supports            JSON NULL,                  -- ['title','editor','thumbnail',...]
    is_public           TINYINT(1) NOT NULL DEFAULT 1,
    has_archive         TINYINT(1) NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wordpress_site_id) REFERENCES wordpress_sites(id) ON DELETE CASCADE,
    UNIQUE KEY uk_wpt_site_slug (wordpress_site_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- WP_TAXONOMIES
-- ===================================================
CREATE TABLE wp_taxonomies (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wordpress_site_id   BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    slug                VARCHAR(100) NOT NULL,
    label               VARCHAR(191) NULL,
    post_types          JSON NULL,                  -- کدام post_typeها این taxonomy را دارند
    is_hierarchical     TINYINT(1) NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wordpress_site_id) REFERENCES wordpress_sites(id) ON DELETE CASCADE,
    UNIQUE KEY uk_wptax_site_slug (wordpress_site_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- WP_CATEGORIES
-- ===================================================
CREATE TABLE wp_categories (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wordpress_site_id   BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    external_id         INT NOT NULL,              -- WordPress term ID
    name                VARCHAR(191) NOT NULL,
    slug                VARCHAR(191) NOT NULL,
    parent_id           INT NULL,                  -- WordPress parent term ID
    taxonomy            VARCHAR(100) NOT NULL DEFAULT 'category',
    post_count          INT UNSIGNED NOT NULL DEFAULT 0,
    synced_at           TIMESTAMP NULL,
    FOREIGN KEY (wordpress_site_id) REFERENCES wordpress_sites(id) ON DELETE CASCADE,
    UNIQUE KEY uk_wpc_site_external (wordpress_site_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- WP_TAGS
-- ===================================================
CREATE TABLE wp_tags (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wordpress_site_id   BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    external_id         INT NOT NULL,
    name                VARCHAR(191) NOT NULL,
    slug                VARCHAR(191) NOT NULL,
    post_count          INT UNSIGNED NOT NULL DEFAULT 0,
    synced_at           TIMESTAMP NULL,
    FOREIGN KEY (wordpress_site_id) REFERENCES wordpress_sites(id) ON DELETE CASCADE,
    UNIQUE KEY uk_wptag_site_external (wordpress_site_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- WP_AUTHORS
-- ===================================================
CREATE TABLE wp_authors (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wordpress_site_id   BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    external_id         INT NOT NULL,
    name                VARCHAR(191) NOT NULL,
    slug                VARCHAR(191) NOT NULL,
    email               VARCHAR(191) NULL,
    role                VARCHAR(100) NULL,
    synced_at           TIMESTAMP NULL,
    FOREIGN KEY (wordpress_site_id) REFERENCES wordpress_sites(id) ON DELETE CASCADE,
    UNIQUE KEY uk_wpauth_site_external (wordpress_site_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- WP_ELEMENTOR_TEMPLATES
-- ===================================================
CREATE TABLE wp_elementor_templates (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wordpress_site_id   BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    external_id         INT NOT NULL,
    name                VARCHAR(255) NOT NULL,
    type                VARCHAR(100) NULL,           -- single, archive, header, footer ...
    conditions          JSON NULL,                   -- display conditions از Elementor
    post_types          JSON NULL,                   -- کدام post_typeها این template را دارند
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    synced_at           TIMESTAMP NULL,
    FOREIGN KEY (wordpress_site_id) REFERENCES wordpress_sites(id) ON DELETE CASCADE,
    UNIQUE KEY uk_wpet_site_external (wordpress_site_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- WP_CONTENT_PROFILES
-- الگوهای محتوایی استخراج‌شده از تحلیل مقالات
-- ===================================================
CREATE TABLE wp_content_profiles (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wordpress_site_id       BIGINT UNSIGNED NOT NULL,
    tenant_id               BIGINT UNSIGNED NOT NULL,
    name                    VARCHAR(191) NOT NULL,   -- e.g. Tutorial, News, Review
    post_type               VARCHAR(100) NOT NULL DEFAULT 'post',
    category_id             BIGINT UNSIGNED NULL,
    template_id             BIGINT UNSIGNED NULL,
    required_fields         JSON NULL,               -- ['title','featured_image','meta_description']
    recommended_structure   JSON NULL,               -- content structure analysis result
    seo_config              JSON NULL,
    avg_word_count          INT UNSIGNED NULL,
    sample_count            INT UNSIGNED NOT NULL DEFAULT 0,
    is_default              TINYINT(1) NOT NULL DEFAULT 0,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (wordpress_site_id) REFERENCES wordpress_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES wp_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES wp_elementor_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.7 جداول Media / Assets

```sql
-- ===================================================
-- ASSETS
-- فایل‌های رسانه‌ای مستقل از Destination
-- ===================================================
CREATE TABLE assets (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL UNIQUE,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    owner_id        BIGINT UNSIGNED NOT NULL,        -- user_id
    type            ENUM('image','video','audio','document','other') NOT NULL,
    mime_type       VARCHAR(100) NOT NULL,
    original_name   VARCHAR(255) NOT NULL,
    storage_disk    VARCHAR(50) NOT NULL DEFAULT 'local',
    storage_path    VARCHAR(500) NOT NULL,
    file_size       BIGINT UNSIGNED NOT NULL,
    width           INT UNSIGNED NULL,               -- برای image
    height          INT UNSIGNED NULL,
    duration        INT UNSIGNED NULL,               -- برای video/audio (seconds)
    checksum        VARCHAR(64) NULL,                -- SHA-256
    metadata        JSON NULL,                       -- EXIF, dimensions, ...
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_assets_tenant (tenant_id),
    INDEX idx_assets_owner (owner_id),
    INDEX idx_assets_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ASSET_UPLOADS
-- ردیابی upload در هر destination
-- ===================================================
CREATE TABLE asset_uploads (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id        BIGINT UNSIGNED NOT NULL,
    destination_id  BIGINT UNSIGNED NOT NULL,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    external_id     VARCHAR(191) NULL,               -- WordPress media ID یا Telegram file_id
    external_url    VARCHAR(500) NULL,
    status          ENUM('pending','uploaded','failed') NOT NULL DEFAULT 'pending',
    uploaded_at     TIMESTAMP NULL,
    last_error      TEXT NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    UNIQUE KEY uk_au_asset_destination (asset_id, destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.8 جداول Content

```sql
-- ===================================================
-- CONTENTS
-- محتوای اصلی (مستقل از Destination)
-- ===================================================
CREATE TABLE contents (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL UNIQUE,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    owner_id        BIGINT UNSIGNED NOT NULL,
    type            ENUM('article','note','media_post','thread') NOT NULL DEFAULT 'article',
    title           VARCHAR(500) NULL,
    body            LONGTEXT NULL,
    body_format     ENUM('html','markdown','plain') NOT NULL DEFAULT 'html',
    status          ENUM('draft','review','approved','archived') NOT NULL DEFAULT 'draft',
    featured_asset_id BIGINT UNSIGNED NULL,
    language        VARCHAR(10) NOT NULL DEFAULT 'fa',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (featured_asset_id) REFERENCES assets(id) ON DELETE SET NULL,
    INDEX idx_contents_tenant (tenant_id),
    INDEX idx_contents_owner (owner_id),
    INDEX idx_contents_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- CONTENT_ASSETS
-- رابطه M:N بین محتوا و فایل‌ها
-- ===================================================
CREATE TABLE content_assets (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_id  BIGINT UNSIGNED NOT NULL,
    asset_id    BIGINT UNSIGNED NOT NULL,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    role        ENUM('featured','inline','attachment','gallery') NOT NULL DEFAULT 'inline',
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    UNIQUE KEY uk_ca_content_asset_role (content_id, asset_id, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- WORDPRESS_PUBLICATION_DATA
-- Platform-specific data برای WordPress
-- ===================================================
CREATE TABLE wordpress_publication_data (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publication_id      BIGINT UNSIGNED NOT NULL UNIQUE,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    -- Content fields
    title               VARCHAR(500) NULL,
    slug                VARCHAR(500) NULL,
    excerpt             TEXT NULL,
    content             LONGTEXT NULL,               -- final HTML
    post_status         ENUM('publish','draft','pending','private','future') NOT NULL DEFAULT 'publish',
    post_type           VARCHAR(100) NOT NULL DEFAULT 'post',
    -- Relations
    wp_category_ids     JSON NULL,                   -- [1, 5, 12]
    wp_tag_ids          JSON NULL,
    wp_author_id        INT NULL,
    wp_template_id      INT NULL,
    featured_image_asset_id BIGINT UNSIGNED NULL,
    -- SEO
    seo_title           VARCHAR(500) NULL,
    seo_description     TEXT NULL,
    focus_keyword       VARCHAR(255) NULL,
    canonical_url       VARCHAR(500) NULL,
    robots              VARCHAR(100) NULL,           -- 'index,follow'
    -- Elementor
    elementor_data      LONGTEXT NULL,               -- اگر نیاز به injection داشت
    -- Result
    external_post_id    INT NULL,                    -- WordPress Post ID بعد از publish
    external_url        VARCHAR(500) NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_wpd_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- TELEGRAM_PUBLICATION_DATA
-- ===================================================
CREATE TABLE telegram_publication_data (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publication_id      BIGINT UNSIGNED NOT NULL UNIQUE,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    message_text        TEXT NULL,
    caption             TEXT NULL,
    parse_mode          ENUM('HTML','Markdown','MarkdownV2') NULL DEFAULT 'HTML',
    media_asset_id      BIGINT UNSIGNED NULL,
    media_type          ENUM('photo','video','document','audio','animation') NULL,
    disable_web_page_preview TINYINT(1) NOT NULL DEFAULT 0,
    disable_notification TINYINT(1) NOT NULL DEFAULT 0,
    reply_to_message_id BIGINT NULL,
    inline_keyboard     JSON NULL,
    -- Result
    external_message_id BIGINT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tgd_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- BALE_PUBLICATION_DATA
-- ===================================================
CREATE TABLE bale_publication_data (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publication_id      BIGINT UNSIGNED NOT NULL UNIQUE,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    message_text        TEXT NULL,
    caption             TEXT NULL,
    parse_mode          ENUM('HTML','Markdown') NULL DEFAULT 'HTML',
    media_asset_id      BIGINT UNSIGNED NULL,
    media_type          ENUM('photo','video','document','audio','animation') NULL,
    disable_web_page_preview TINYINT(1) NOT NULL DEFAULT 0,
    disable_notification TINYINT(1) NOT NULL DEFAULT 0,
    reply_to_message_id BIGINT NULL,
    inline_keyboard     JSON NULL,
    -- Result
    external_message_id BIGINT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_baled_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.9 جداول Publication Engine

```sql
-- ===================================================
-- PUBLICATIONS
-- Entity مرکزی هر بار انتشار
-- ===================================================
CREATE TABLE publications (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                CHAR(36) NOT NULL UNIQUE,
    idempotency_key     CHAR(36) NOT NULL UNIQUE,    -- برای جلوگیری از duplicate
    tenant_id           BIGINT UNSIGNED NOT NULL,
    content_id          BIGINT UNSIGNED NOT NULL,
    destination_id      BIGINT UNSIGNED NOT NULL,
    created_by          BIGINT UNSIGNED NOT NULL,
    status              ENUM(
                            'draft',
                            'pending',
                            'scheduled',
                            'processing',
                            'published',
                            'failed',
                            'cancelled',
                            'partial'
                        ) NOT NULL DEFAULT 'draft',
    -- Dependency
    depends_on_publication_id BIGINT UNSIGNED NULL,  -- باید بعد از این publish شود
    dependency_field    VARCHAR(100) NULL,            -- e.g. 'external_url'
    -- Scheduling
    scheduled_at        TIMESTAMP NULL,
    published_at        TIMESTAMP NULL,
    -- Retry
    max_attempts        TINYINT UNSIGNED NOT NULL DEFAULT 3,
    attempts            TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt_at     TIMESTAMP NULL,
    next_retry_at       TIMESTAMP NULL,
    last_error          TEXT NULL,
    last_error_code     VARCHAR(100) NULL,
    -- External result
    external_id         VARCHAR(191) NULL,
    external_url        VARCHAR(500) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (depends_on_publication_id) REFERENCES publications(id) ON DELETE SET NULL,
    INDEX idx_pub_tenant (tenant_id),
    INDEX idx_pub_status (status),
    INDEX idx_pub_scheduled (scheduled_at),
    INDEX idx_pub_next_retry (next_retry_at),
    INDEX idx_pub_content (content_id),
    INDEX idx_pub_destination (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- PUBLICATION_LOGS
-- هر مرحله از چرخه انتشار
-- ===================================================
CREATE TABLE publication_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publication_id  BIGINT UNSIGNED NOT NULL,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    level           ENUM('debug','info','warning','error') NOT NULL DEFAULT 'info',
    stage           VARCHAR(100) NOT NULL,           -- prepare, media_upload, create, seo, publish, success, failure
    message         TEXT NOT NULL,
    context         JSON NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    INDEX idx_pl_publication (publication_id),
    INDEX idx_pl_tenant (tenant_id),
    INDEX idx_pl_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.10 جدول Audit Log

```sql
-- ===================================================
-- AUDIT_LOGS
-- برای Security و Compliance
-- ===================================================
CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NULL,
    user_id         BIGINT UNSIGNED NULL,
    event           VARCHAR(191) NOT NULL,           -- 'destination.created', 'content.published'
    auditable_type  VARCHAR(191) NULL,               -- Model class
    auditable_id    BIGINT UNSIGNED NULL,
    old_values      JSON NULL,
    new_values      JSON NULL,
    ip_address      VARCHAR(45) NULL,
    user_agent      TEXT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_al_tenant (tenant_id),
    INDEX idx_al_user (user_id),
    INDEX idx_al_event (event),
    INDEX idx_al_auditable (auditable_type, auditable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 5. Multi-Tenant Strategy

**Shared Database, Shared Schema** با `tenant_id` روی هر جدول.

**قوانین:**
1. هر Model باید `HasTenantScope` Global Scope داشته باشد
2. هر Query باید `where('tenant_id', auth()->user()->tenant_id)` داشته باشد
3. هیچ Service نباید با ID خالص Resource دیگران را بخواند
4. Tenant Context از Middleware تزریق می‌شود

---

## 6. Bot Architecture — Bot Driver Abstraction

```php
interface BotDriverInterface {
    public function sendMessage(string $chatId, string $text, array $options = []): MessageResult;
    public function sendPhoto(string $chatId, mixed $photo, array $options = []): MessageResult;
    public function sendVideo(string $chatId, mixed $video, array $options = []): MessageResult;
    public function sendDocument(string $chatId, mixed $document, array $options = []): MessageResult;
    public function editMessage(string $chatId, int $messageId, string $text, array $options = []): MessageResult;
    public function deleteMessage(string $chatId, int $messageId): bool;
    public function answerCallback(string $callbackId, array $options = []): bool;
    public function getChat(string $chatId): ChatInfo;
    public function setWebhook(string $url, array $options = []): bool;
    public function deleteWebhook(): bool;
    public function getMe(): BotInfo;
}

class TelegramBotDriver implements BotDriverInterface { ... }
class BaleBotDriver implements BotDriverInterface { ... }
```

---

## 7. Conversation Engine — Flows

**Flows مشخص:**
- `onboarding` — شناسایی و verify کاربر جدید
- `main_menu` — منوی اصلی
- `create_content` — ایجاد محتوای جدید
- `create_wp_post` — پست WordPress
- `create_channel_post` — پست کانال
- `schedule_publication` — زمان‌بندی
- `manage_drafts` — مدیریت پیش‌نویس‌ها
- `settings` — تنظیمات کاربر

---

## 8. Content Model — اصل جداسازی

```
Content (محتوای خام)
    ↓
Publication (تصمیم انتشار)
    ↓
Destination (مقصد)
    ↓
Platform-specific Data (wordpress_publication_data | telegram_publication_data | bale_publication_data)
```

---

## 9. Publisher Architecture

```php
interface PublisherInterface {
    public function canPublish(Publication $publication): bool;
    public function prepare(Publication $publication): PreparedPayload;
    public function publish(Publication $publication, PreparedPayload $payload): PublishResult;
    public function checkStatus(Publication $publication): PublicationStatus;
}

class WordPressPublisher implements PublisherInterface { ... }
class TelegramPublisher implements PublisherInterface { ... }
class BalePublisher implements PublisherInterface { ... }
```

---

## 10. WordPress Plugin API Contract

```
GET  /wp-json/botpress/v1/site-info
GET  /wp-json/botpress/v1/capabilities
GET  /wp-json/botpress/v1/post-types
GET  /wp-json/botpress/v1/taxonomies
GET  /wp-json/botpress/v1/categories?taxonomy=category&per_page=100
GET  /wp-json/botpress/v1/tags?per_page=100
GET  /wp-json/botpress/v1/authors
GET  /wp-json/botpress/v1/elementor/templates
GET  /wp-json/botpress/v1/elementor/conditions
GET  /wp-json/botpress/v1/analysis?count=10
POST /wp-json/botpress/v1/media
POST /wp-json/botpress/v1/posts
PUT  /wp-json/botpress/v1/posts/{id}
POST /wp-json/botpress/v1/posts/{id}/publish
GET  /wp-json/botpress/v1/posts/{id}/status
```

Authentication: `X-BotPress-Key: {api_key}`

---

## 11. Scheduling Architecture

**نسخه اول:** Laravel Scheduler هر دقیقه

```php
// app/Console/Kernel.php
$schedule->command('publications:process')->everyMinute()->withoutOverlapping(5);
```

**ProcessPublicationsCommand:**
1. Publications با `status=scheduled` و `scheduled_at <= now()` را پیدا کن
2. Dependencies را چک کن
3. `status=processing` کن
4. Publisher مناسب را اجرا کن
5. نتیجه را ذخیره کن
6. در صورت خطا Retry را schedule کن

**Queue-ready بودن:** تمام منطق در Job قرار گیرد، فعلاً synchronous اجرا شود.

---

## 12. Security Architecture

```
1. API Keys — AES-256-CBC encrypted در DB
2. Bot Tokens — encrypted، هرگز در Log نمایش نده
3. Webhook Verification — signature check
4. Tenant Isolation — Global Scope روی همه Models
5. Resource Ownership — هر Query با tenant_id + owner_id
6. Input Validation — Form Requests برای همه API calls
7. File Validation — MIME type + extension + size
8. Rate Limiting — per IP + per user
9. Audit Logging — همه تغییرات حساس
```

---

## 13. Admin Panel Design System

**Color Palette:**
- Primary: Indigo 600 (#4F46E5)
- Surface: Gray 50 / White
- Text: Gray 900 / 600 / 400
- Success: Emerald 500
- Warning: Amber 500
- Danger: Red 500

**Typography:**
- Font: Inter
- Scale: 12/14/16/18/20/24/30px
- Weight: 400/500/600

**Components:** Button, Input, Select, Table, Card, Badge, Modal, Drawer, Toast, Empty State, Skeleton, Status Indicator

---

## 14. Phase Breakdown

| Phase | عنوان | وابستگی |
|-------|-------|---------|
| 0 | Architecture & Specification | - |
| 1 | Laravel Foundation + DB + Admin Skeleton | - |
| 2 | Identity + Auth + Authorization | Phase 1 |
| 3 | Bot Engine + Telegram + Bale + Webhooks | Phase 2 |
| 4 | Conversation Engine | Phase 3 |
| 5 | WordPress Plugin Foundation | Phase 1 |
| 6 | WordPress Site Discovery | Phase 5 |
| 7 | Template + Content Analysis | Phase 6 |
| 8 | Content + Draft + Media System | Phase 4 |
| 9 | Publication Engine Core | Phase 8 |
| 10 | Telegram Publisher | Phase 9 |
| 11 | Bale Publisher | Phase 9 |
| 12 | WordPress Publisher | Phase 9, 6 |
| 13 | Scheduler + Dependencies + Retry | Phase 10,11,12 |
| 14 | Admin Panel UI/UX Completion | Phase 1-13 |
| 15 | Security Hardening + Tests | همه |
| 16 | Production Readiness | همه |

---

## 15. Definition of Done (هر Phase)

```
✅ Code implemented and passes PSR-12
✅ All Migrations run cleanly
✅ Feature Tests pass
✅ No architecture rules violated
✅ tenant_id در همه queries
✅ No hardcoded IDs / URLs / Tokens
✅ Secrets not in logs
✅ README updated
✅ No unrelated changes
✅ Git commit ready
```

---

## 16. Architecture Rules (برای Claude Code)

```
1.  Business Logic در Bot Handler نوشته نشود
2.  Telegram و Bale Business Logic مشترک داشته باشند
3.  Platform-specific code در Adapter باشد
4.  WordPress code در WordPress Integration باشد
5.  Content از Destination مستقل باشد
6.  Publication مستقل و Idempotent باشد
7.  همه Queries باید tenant_id-scoped باشند
8.  هیچ Site/Channel/Template hardcode نشود
9.  Laravel نباید Elementor HTML تولید کند
10. WordPress Plugin مسئول تعامل با WordPress باشد
11. Publish باید Idempotent باشد (idempotency_key)
12. Retry در سطح Publication باشد
13. سیستم Queue-ready باشد (Job structure) ولی sync اجرا شود
14. Docker/Redis اضافه نشود
15. Controller و Handler باید Thin باشند
16. Domain Logic در Application/Domain Layer باشد
17. هر API باید Form Request Validation داشته باشد
18. Tokenها در Log نمایش داده نشوند
```

---

*این سند Living Document است. با هر Phase به‌روز می‌شود.*

# Changelog

All notable changes to **XEN LevelUp** are documented here.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [1.2.0] - 2026-06-04

### What's New
- 🛍️ **Custom Shop Items** — Admins can now create, edit, activate/deactivate, and delete shop items directly from the admin panel. Supports all item types (frame, border, name color, title, theme, badge) with JSON item data, pricing, image URL, sort order, and premium flags.
- 🔍 **Shop Filtering** — Both admin and public shop views support filtering by item type. Admin also supports filtering by active/inactive status and title search.
- 📄 **Shop Pagination** — Admin shop table and public shop grid are now paginated (20 per page in admin, 12 per page on the front end) with AJAX-powered navigation in the public shop.

### Added
- `Xen_Shop::create_item()`, `update_item()`, `delete_item()`, `toggle_active()`, `get_item_any()` — full admin CRUD for shop items.
- `Xen_Shop::get_items_paged()` — paginated query with type, search, and active/inactive filters.
- `Xen_Shop::count_items()` — count query for pagination math (supports null `$active_only` for "all statuses").
- `admin/views/shop.php` rewritten: inline "Add New Item" collapsible form, filter bar (type + status + search), paginated table with Edit / Activate-Deactivate / Delete per row, dedicated edit form view.
- `admin/class-xen-admin.php` `page_shop()` now handles form POST actions (create, update, delete, toggle) with nonce verification and post–redirect–get pattern.
- `xen_get_shop_items` AJAX handler updated to accept `page` and `per_page` parameters and return `owned_ids`, `equipped_ids`, `total`, `pages` alongside items.
- `public/js/xen-shop.js` fully rewritten: AJAX filter buttons, AJAX pagination with numbered page controls, JS item card renderer for dynamic reloads.
- `xenData` localization gains `loginUrl` and shop i18n strings (`buy`, `equip`, `equipped`, `loginToBuy`, etc.).
- `[gamified_shop]` shortcode supports `per_page` attribute.
- Admin CSS: `.xen-type-badge`, `.xen-status-badge`, `.xen-btn-danger`, filter bar styles, pagination styles.
- Public CSS: `.xen-pager`, `.xen-page-btn`, `.xen-page-current`, `.xen-page-gap`.

### Fixed
- Public shop JS action names corrected (`xen_purchase_item`, `xen_equip_item` — previously referenced wrong action slugs).
- Shop item `data-type` attribute renamed to `data-item-type` on item cards to match JS selector expectations.
- Shop item button `data-id` renamed to `data-item-id` on purchase/equip buttons to match JS `data('item-id')` reads.

---

## [1.1.3] - 2026-06-03

### What's New
- 🖥️ **Improved Admin Dashboard** — Plugin info card (version, developer, Check for Updates), shortcodes reference, features list, and a 6-step Getting Started guide are now displayed on the main admin dashboard.

### Changed
- Admin Dashboard now shows: plugin version, author (Richard C. Cupal, LPT / Xenroth Digital Innovations), GitHub link, and a "Check for Updates" button.
- Added Available Shortcodes table, Features overview, and Getting Started steps to the admin dashboard.
- Coin stat card uses the configured currency name and symbol instead of a hardcoded emoji.

### Fixed
- None.

---

## [1.1.2] - 2026-06-03

### Fixed
- **Critical error on Admin → Legendary tab**: `get_all()` was called with no arguments but required `$user_id`. Made `$user_id` optional (default `0`); when omitted the method returns all legendary quests across all users (for the admin view); when a user ID is supplied it scopes the result to that user.

---

## [1.1.1] - 2026-06-03

### What's New
- 📋 **Changelog File** — A full `CHANGELOG.md` is now included in the plugin. The dashboard "What's New" card reads feature highlights directly from it — no PHP changes needed for future releases.

### Added
- `CHANGELOG.md` at the plugin root with complete version history.

### Changed
- `Xen_Overview::whats_new()` now parses `CHANGELOG.md` to extract feature highlights instead of using a hardcoded array. Add a `### What's New` section under any `## [x.x.x]` heading to populate the dashboard card for that version.

---

## [1.1.0] - 2026-06-10

### What's New
- 📅 **Daily Check-In Rewards** — Check in every day to earn XP and coins. Build streaks to unlock bigger rewards every 7 days.
- 📊 **Dashboard Overview Stats** — See system-wide stats — total hunters, quests completed, XP earned, and more — right on your dashboard.
- 💎 **Custom Currency Name & Symbol** — Administrators can rename the in-game currency and choose any symbol via Settings → Currency.

### Added
- `Xen_Daily_Checkin` class: `can_checkin()`, `checkin()`, `get_streak()`, `get_total_checkins()`, `get_history()`.
- `Xen_Overview` class: `whats_new()` (parses CHANGELOG.md), `get_stats()` (cached overview), `dismiss()` / `is_dismissed()`.
- 18th DB table `xen_checkins` (`user_id`, `checkin_date`, `streak`, `xp_earned`, `coins_earned`).
- `Xen_Currency::name()` and `Xen_Currency::symbol()` static helpers.
- AJAX actions: `xen_daily_checkin`, `xen_dismiss_whats_new`.
- `xenData.currencyName`, `xenData.currencySymbol`, `xenData.whatsNewVersion` injected into front-end JS.
- Currency Name and Currency Symbol fields in Admin → Settings.

### Changed
- `XEN_LEVELUP_DB_VERSION` bumped to `1.1.0`; `dbDelta` runs automatically on update.
- Dashboard now uses dynamic currency symbol instead of hardcoded 🪙.
- What's New card and Overview Stats strip added above the hero card on the public dashboard.
- Daily Check-In card added below the Today's Quests section.
- Streak reward formula: base 50 XP + 10 coins; every 7-day milestone adds +25 XP / +10 coins.

---

## [1.0.2] - 2026-06-03

### Changed
- Full changelog added to `readme.txt`.
- Minor documentation updates.

---

## [1.0.1] - 2026-06-03

### Changed
- Updated plugin author to Richard C. Cupal, LPT (Xenroth) — Xenroth Digital Innovations.
- Plugin URI and Author URI updated to `https://xenroth.com`.
- GitHub auto-updater added (`class-xen-github-updater.php`): WordPress detects and applies updates automatically when a new release tag is pushed to `https://github.com/xenroth/xen-levelup`.
- Optional GitHub token support via `XEN_LEVELUP_GITHUB_TOKEN` constant or `xen_levelup_github_token` WP option.

---

## [1.0.0] - 2026-06-03

### Added
- Initial public release.
- Singleton plugin architecture with `spl_autoload_register` class loader.
- 17 custom database tables via `dbDelta()` — no CPTs required.
- XP formula: `floor(100 × level^1.9 × (1 + level × 0.005))`.
- Level 1–100 with rank titles: Unranked → E-Rank → D-Rank → C-Rank → B-Rank → A-Rank → S-Rank → National-Level → Shadow Monarch.
- 8 RPG stats: Strength, Intelligence, Discipline, Endurance, Wisdom, Charisma, Focus, Vitality.
- 10 Life Development Trees: Physique, Intelligence, Knowledge, Discipline, Wealth, Communication, Leadership, Relationships, Spirituality, Longevity.
- 3-step onboarding wizard.
- Daily, random, special, and legendary quest types.
- Habit tracker with streak support.
- Achievement system.
- In-game shop with item equip system.
- Global and weekly rankings / leaderboard.
- REST API endpoints.
- Shortcodes: `[gamified_dashboard]`, `[gamified_profile]`, `[gamified_shop]`, `[gamified_rankings]`, `[gamified_onboarding]`.
- Activation, deactivation, and full uninstall routines.

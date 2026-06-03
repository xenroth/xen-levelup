=== XEN LevelUp ===
Contributors: xenroth
Author: Richard C. Cupal, LPT (Xenroth)
Author URI: https://xenroth.com
Company: Xenroth Digital Innovations
Contact: +639150388448 | me@xenroth.com
Tags: gamification, rpg, leveling, quests, habits, productivity, solo leveling, personal development
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Solo Leveling-inspired personal development system. Level up your real life through quests, habits, tasks, and a 10-tree Life Development System.

== Description ==

**XEN LevelUp** turns your personal development journey into a solo-leveling RPG experience right inside WordPress.

= Key Features =

* **Hunter Profile** — Level 1–100 with XP progression, rank titles (E-Rank → Shadow Monarch), and a custom title system
* **10 Life Development Trees** — Physique, Intelligence, Knowledge, Discipline, Wealth, Communication, Leadership, Relationships, Spirituality, Longevity
* **8 RPG Stats** — Strength, Intelligence, Discipline, Endurance, Wisdom, Charisma, Focus, Vitality — all generated from your onboarding personality + interest profile
* **Quest System** — Daily quests (auto-generated), Random quests (hourly), Special/weekly quests, Legendary quests (awarded to chosen hunters)
* **Daily Tasks** — Up to 10 tasks per day with priority levels (Critical → Low), optional due dates
* **Habit Tracker** — Daily/weekly habits with streak counters, 7-day streak bonus XP
* **Achievements** — 22+ built-in achievements, automatically checked and awarded
* **Global Rankings** — All-time, weekly, and monthly leaderboards
* **Shop** — Buy profile frames, borders, name colours, titles, themes, and badges with in-game coins
* **Currency** — Earn coins by completing quests and levelling up; spend in the shop
* **Onboarding Wizard** — 3-step personality + interests + priorities wizard that auto-generates your initial stats
* **Notifications** — In-app notification system with a dropdown bell
* **Admin Dashboard** — Full plugin management: users, quests, shop items, achievements, rankings, analytics
* **REST API + AJAX** — All features available via WordPress REST API (`xen/v1`) and standard AJAX
* **Solo Leveling Theme** — Dark UI with neon glow, glassmorphism cards, animated XP bars, level-up particle burst

= Shortcodes =

See the Shortcodes section below.

= Requirements =

* WordPress 5.8+
* PHP 7.4+
* MySQL 5.6+ (uses `ON DUPLICATE KEY UPDATE`)
* jQuery + jQuery UI (bundled with WordPress)

== Installation ==

1. Upload the `xen-levelup` folder to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins → Installed Plugins**.
3. Go to **XEN LevelUp → Settings** and assign WordPress pages to each shortcode (Dashboard, Profile, Quests, etc.).
4. Add the appropriate shortcode to each page (e.g. `[gamified_dashboard]` on your Dashboard page).
5. (Optional) Visit **XEN LevelUp → Shop** to customise the default shop items.
6. Users will be redirected to the onboarding wizard on first login.

== Shortcodes ==

| Shortcode                       | Description                                           |
|---------------------------------|-------------------------------------------------------|
| `[gamified_dashboard]`          | Main hunter dashboard — XP bar, life trees, daily quests |
| `[gamified_profile]`            | Full hunter profile with stats, achievements, rank   |
| `[gamified_quests]`             | Quest list (optional `type` attribute: daily/special/legendary) |
| `[gamified_daily_quests]`       | Today's daily quest cards with complete buttons      |
| `[gamified_special_quests]`     | Weekly special quest cards                           |
| `[gamified_legendary_quests]`   | Legendary quest cards (chosen hunters only)          |
| `[gamified_tasks]`              | Personal task list — add, complete, delete           |
| `[gamified_habits]`             | Habit tracker — add habits, log daily, view streaks  |
| `[gamified_rankings]`           | Leaderboard — all-time, weekly, monthly tabs         |
| `[gamified_leaderboard]`        | Minimal leaderboard widget (top 10)                  |
| `[gamified_shop]`               | Item shop — filter by type, buy, equip               |
| `[gamified_achievements]`       | Achievement gallery — locked/unlocked states         |
| `[gamified_stats]`              | Life tree bars + RPG attribute grid                  |
| `[gamified_character]`          | Full character sheet                                 |
| `[gamified_level]`              | Inline level badge (use inside other content)        |

== Frequently Asked Questions ==

= Will this slow down my site? =

XEN LevelUp only loads its CSS and JS on pages that contain its shortcodes. Database queries use transient caching (5 min) for profile, stats, and life tree data.

= Can I customise the XP formula? =

The formula `floor(100 × level^1.9 × (1 + level × 0.005))` is defined in `class-xen-leveling.php`. You can override it using the `xen_xp_for_level` filter (planned for a future release).

= Does it work with any theme? =

Yes. The plugin does not modify your theme. All styles are scoped to `.xen-wrap` and only load on shortcode pages.

= Can I reset a user's progress? =

From **XEN LevelUp → Users**, click on a user and use the Reset button (coming in v1.1). For now you can delete their rows from the `xen_user_profiles` table.

= Where are the plugin pages set up? =

Go to **XEN LevelUp → Settings** and assign an existing WordPress page to each feature. Then add the matching shortcode to that page.

== Screenshots ==

1. Hunter Dashboard — hero card, life trees, today's quests
2. Onboarding wizard — personality sliders
3. Character sheet — RPG stats grid
4. Shop page — item grid with buy/equip states
5. Rankings leaderboard — period tabs
6. Admin dashboard — stats and top hunters
7. Level-up modal — particle burst effect

== Changelog ==

= 1.2.1 — 2026-06-04 =
* Fixed PHP TypeError (critical error on PHP 8+): `Xen_User::get_full_data()` was returning the raw `user_stats` DB object as the `stats` key; the dashboard view then tried to access it as an array (`$stats['life_trees']`), causing a fatal crash. Now correctly returns the structured array from `Xen_Stats::get_all_stats()`.
* Fixed: dashboard XP bar always showed 0 because `get_full_data()` returned `experience` but the view read `xp`. Added `xp` as an explicit key.
* Fixed: `get_all_stats()` now strips DB-only columns (`id`, `user_id`, `updated_at`) from `life_trees` and `rpg` arrays via `array_intersect_key`, preventing phantom entries in life tree displays.
* Removed the system-wide overview stats strip from the public dashboard (total hunters, total XP, quests done, tasks done, active today, top hunter). The dashboard now shows only personal stats.

= 1.2.0 — 2026-06-04 =
* **New: Custom Shop Items** — Admins can create, edit, activate/deactivate, and delete shop items directly from the admin panel with full control over item type, price, JSON item data, image URL, sort order, and premium flag.
* **New: Shop Filtering** — Both admin and public shop views support filtering by item type. Admin adds status (active/inactive) filter and title search.
* **New: Shop Pagination** — Admin shop table is paginated (20 per page, server-side). Public shop grid is paginated (12 per page) with AJAX-powered numbered page controls; filter buttons also trigger AJAX reloads without page refresh.
* Fixed public shop JS action name mismatches (purchase/equip AJAX actions now correctly map to PHP handlers).
* Fixed `data-type` → `data-item-type` on shop item cards and `data-id` → `data-item-id` on action buttons.
* Added `loginUrl`, `buy`, `equip`, `equipped` and other shop i18n strings to `xenData`.

= 1.1.3 — 2026-06-03 =
* Admin Dashboard redesigned: added Plugin Info card (version, developer, Check for Updates button, GitHub link), Available Shortcodes reference table, Features list, and a 6-step Getting Started guide.
* Coin stat card now uses the configured currency name and symbol instead of a hardcoded emoji.

= 1.1.2 — 2026-06-03 =
* Fixed critical error on Admin → Legendary tab: `get_all()` was called without the required `$user_id` argument. The method now defaults to `$user_id = 0` and returns all legendary quests across all users when called without an argument (admin view), or a single user's quests when a user ID is supplied.

= 1.1.1 — 2026-06-03 =
* Added `CHANGELOG.md` to the plugin root following Keep a Changelog format.
* Dashboard "What's New" card now reads feature highlights directly from `CHANGELOG.md` instead of a hardcoded array — add a `### What's New` section under any future `## [x.x.x]` heading to update the card without touching PHP.

= 1.1.0 — 2026-06-10 =
* **New: Daily Check-In Rewards** — Users can check in once per calendar day to earn XP and coins. Consecutive streaks increase rewards every 7 days (Day 7+: bonus XP and coins, scaling with each milestone).
* **New: Dashboard What's New Card** — A dismissible card at the top of the public dashboard shows feature highlights for the current version. Dismissed state is saved per user.
* **New: Dashboard Overview Stats Strip** — System-wide totals (total hunters, total XP, quests completed, tasks done, active today, top hunter) now display on the dashboard, cached for 15 minutes.
* **New: Custom Currency Name & Symbol** — Administrators can now rename the in-game currency and choose any symbol via Settings → Currency. Defaults: 'System Coins' / '💎'. The name and symbol propagate to the dashboard, quest rewards, and front-end JS (`xenData.currencyName` / `xenData.currencySymbol`).
* Added `xen_checkins` (18th DB table). `XEN_LEVELUP_DB_VERSION` bumped to `1.1.0`; dbDelta runs automatically on update.
* Added `Xen_Currency::name()` and `Xen_Currency::symbol()` static helpers.
* Added `Xen_Daily_Checkin` and `Xen_Overview` module classes.
* Added AJAX actions: `xen_daily_checkin`, `xen_dismiss_whats_new`.

= 1.0.1 — 2026-06-03 =
* Updated plugin author to Richard C. Cupal, LPT (Xenroth) — Xenroth Digital Innovations
* Added GitHub auto-updater: WordPress will now detect and apply updates automatically when a new release tag is pushed to https://github.com/xenroth/xen-levelup
* Updated Plugin URI and Author URI to https://xenroth.com
* Optional GitHub token support via `XEN_LEVELUP_GITHUB_TOKEN` constant or `xen_levelup_github_token` WP option

= 1.0.0 — 2026-06-03 =
Initial public release.

**Core**
* Singleton plugin architecture with `spl_autoload_register` class loader
* 17 custom database tables via `dbDelta()` — no CPTs required
* XP formula: `floor(100 × level^1.9 × (1 + level × 0.005))` — ~100 XP at L1, ~2 000 XP at L10
* Activation, deactivation, and full uninstall routines

**Hunter Profile & Progression**
* Level 1–100 with rank titles: Unranked → E-Rank → D-Rank → C-Rank → B-Rank → A-Rank → S-Rank → National-Level → Shadow Monarch
* 8 RPG stats: Strength, Intelligence, Discipline, Endurance, Wisdom, Charisma, Focus, Vitality
* 10 Life Development Trees: Physique, Intelligence, Knowledge, Discipline, Wealth, Communication, Leadership, Relationships, Spirituality, Longevity
* Custom title, profile frame, name colour, and badge equip system

**Onboarding**
* 3-step wizard: personality sliders, interest ratings, drag-and-drop priority ordering
* Stats auto-generated from personality × interests × priorities algorithm with random ±4 variance
* Completion awards 100 coins and generates the first daily quest set

**Quest System**
* Daily quests — 5 main + 2 secondary, auto-generated at midnight via WP-Cron
* Random quests — up to 2 active, 30 % spawn chance per hourly cron run, 3-hour expiry
* Special / weekly quests — expire next Monday midnight
* Legendary quests — awarded to 10 chosen hunters per week
* 50 seeded quest templates across 7 difficulty tiers (Very Easy → Legendary)

**Tasks**
* Personal task list with Critical / High / Medium / Low priority
* 10 tasks per day cap, optional due dates
* Priority-aware display order via `ORDER BY FIELD()`

**Habits**
* Daily and weekly habit tracking with streak counters
* 7-day streak bonus XP; duplicate-log protection
* Deactivate without deleting history

**Achievements**
* 22 built-in achievements across level, quest, task, habit, and legendary categories
* Idempotent award system — achievements can never be double-granted

**Rankings**
* Global, weekly, and monthly leaderboards
* Recalculated twice daily via cron using `ON DUPLICATE KEY UPDATE`

**Shop & Currency**
* 23 seeded shop items: frames, borders, name colours, titles, themes, badges
* Coin economy: earn from quests and level-ups, spend in the shop
* Equip / unequip with automatic slot management per item type

**Notifications**
* In-app notification system with dropdown bell widget
* Automatic 30-day pruning via daily cron

**Admin Panel**
* 9 admin views: Dashboard, Users, Quests, Legendary, Shop, Achievements, Rankings, Analytics, Settings
* Custom columns on WP Users list (Level, Rank, XP)
* Toggleable feature flags and page assignments in Settings

**Developer API**
* REST API namespace `xen/v1` — 20 endpoints (GET/PATCH profile, stats, quests, tasks, habits, achievements, rankings, shop, notifications, onboarding)
* WordPress AJAX handlers for all interactive frontend actions
* All queries use `$wpdb->prepare()`; all output escaped; AJAX nonce-verified

**Frontend**
* 15 shortcodes
* Solo Leveling dark theme — Primary `#00D4FF`, Accent `#7B61FF`, Background `#0B1020`
* Neon glow, glassmorphism cards, animated XP bars, level-up particle burst (canvas)
* Responsive down to 480 px

== Upgrade Notice ==

= 1.0.1 =
Author and branding update; adds GitHub auto-updater. Safe to update — no database changes.

= 1.0.0 =
Initial release. No upgrade path required.

# Changelog

All notable changes to **XEN LevelUp** are documented here.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [1.4.2] - 2026-06-03

### What's New
- 🔍 **Wallet: Live User Search** — The "Send Coins" recipient field now has a live search input. Type a player's display name, username, or numeric ID and matching hunters appear instantly in a suggestion dropdown — no more scrolling through a long select list.
- 📛 **Wallet: Display Names in History** — Transfer and transaction history now shows actual player names ("Sent to Xenroth") instead of raw user ID numbers.
- 🔔 **Notification Bell: Fully Functional** — The 🔔 bell on the dashboard is now a clickable button that opens a notification panel. Shows recent notifications with unread highlights, single-notification mark-read on click, and a "Mark all read" button. Count badge hides when all are read.
- 🏅 **Rankings: Period Tabs Now Work** — The All-Time / Weekly / Monthly tabs on the `[gamified_rankings]` page now actually switch the leaderboard data (the `?period=` URL parameter is now read correctly by the shortcode). Rankings are also auto-calculated on first page load if the table is empty (no need to wait for cron).
- 🖼️ **Admin Shop: Image Upload** — Admins can now click "Upload / Select Image" to open the WordPress Media Library and pick or upload a PNG image for each shop item. Includes size recommendations per item type: Frame (420×420), Border (420×420), Name Color (100×32), Title (200×48), Theme (320×200), Badge (80×80). Image preview shows live in the form.

### Added
- `xen_search_users` AJAX action — searches users by display name, username, or numeric ID; returns up to 10 matches for the wallet autocomplete.
- Notification dropdown panel in dashboard view with full open/close, lazy-load, mark-read, mark-all-read.
- WP Media Library integration on Admin → Shop page (`wp_enqueue_media()` scoped to shop hook).

### Changed
- `public/views/wallet.php` — Replaced `<select>` recipient dropdown with text search input + hidden ID field + suggestion dropdown.
- `public/js/xen-profile-wallet.js` — Added live search with 300ms debounce, suggestion click to select, outside-click close.
- `includes/class-xen-currency.php` — `transfer()` now resolves display names via `get_userdata()` for transaction log descriptions instead of storing raw `#ID`.
- `public/views/dashboard.php` — Notification bell changed from static `<div>` to accessible `<button>` with ARIA attributes; panel HTML added.
- `public/js/xen-dashboard.js` — Bell click handler, notification panel toggle, lazy-load notifications, single/all mark-read.
- `public/css/xen-components.css` — Added `.xen-notif-*` (bell, panel, items) and `.xen-user-search-wrap` / `.xen-sug-*` (wallet autocomplete) styles.
- `includes/class-xen-shortcodes.php` — `render_rankings()` now reads `$_GET['period']` to support tab navigation; triggers `recalculate_all()` when rankings table is empty.
- `admin/views/shop.php` — "Image URL" field replaced with upload button + URL input + live preview + size hints per item type.
- `admin/js/admin.js` — Added WP media frame handler for `.xen-media-upload-btn`; live URL preview on manual input.
- `admin/class-xen-admin.php` — `enqueue_assets()` calls `wp_enqueue_media()` on the shop admin page.

---

## [1.4.1] - 2026-06-04

### What's New
- 🐛 **Daily Check-In Fix** — Check-in now uses WordPress timezone (`current_time()` / `wp_date()`) instead of PHP server time, preventing incorrect "already checked in" errors on sites where the WP timezone differs from the server's PHP timezone.
- 📸 **Profile Photo Upload** — Players can now upload a custom profile photo directly from the Edit Profile panel on the public profile page. Photos are stored via `wp_handle_upload` and override Gravatar site-wide via the `get_avatar_url` filter.
- ⚔️ **Tasks → Side Quests** — Every pending task now has a "⚔️ Convert to Quest" button. Clicking it creates a Side Quest (Special type, Medium difficulty, 150 XP / 30 coins) from the task and marks the original task complete.
- 📢 **Social Activity Feed** — New `[gamified_feed]` shortcode renders a live activity feed. Posts are created automatically when players check in, complete tasks/quests, or finish onboarding. Players can like, comment, and follow friends.
- 👥 **Friends System** — Send, accept friend requests, and filter the activity feed to show only friends' activity.
- 🔔 **System-wide Activity Stream** — Game events (check-in, task complete, quest complete, onboarding complete) automatically post to the global activity feed so the whole community sees achievements.
- 🚪 **Disable WP Dashboard for Non-Admins** — New toggle in Settings blocks non-administrator users from accessing `/wp-admin`, redirecting them to the front-end dashboard instead.
- 🏁 **Auto-Onboarding on Registration** — User profiles are now created immediately on `user_register`, ensuring every new user is captured for onboarding on their first login.
- 🥇 **Gold Full Credit Line** — The entire "Developed by Richard C. Cupal, LPT (Xenroth) — Xenroth Digital Innovations" credit is now displayed in gold on the Admin Dashboard.

### Added
- `includes/class-xen-social.php` — Full social module: activity feed posts, reactions (likes), comments, friends (send/accept requests), event hooks.
- `public/views/feed.php` — Activity feed view template: post box, feed items, like/comment, friend requests panel.
- `public/js/xen-social.js` — Social JavaScript: post, like, comment, load more, add friend, accept friend.
- New DB tables (via `dbDelta`, `XEN_LEVELUP_DB_VERSION` bumped to `1.4.1`): `xen_activity_feed`, `xen_activity_reactions`, `xen_activity_comments`, `xen_friends`.
- `[gamified_feed]` shortcode with `mode` (friends/global) and `limit` attributes.
- AJAX actions: `xen_upload_avatar`, `xen_convert_task_to_quest`, `xen_post_activity`, `xen_get_feed`, `xen_like_activity`, `xen_add_comment`, `xen_get_comments`, `xen_send_friend_request`, `xen_accept_friend_request`.
- Admin Setting: "Disable WP Dashboard for Non-Admins" checkbox.
- Admin Setting: "Activity Feed Page" page selector.
- `Xen_LevelUp::maybe_block_wp_admin()` — `admin_init` hook for WP dashboard blocking.
- `Xen_LevelUp::custom_avatar_url()` — `get_avatar_url` filter for custom uploaded photos.
- `user_register` hook → `Xen_User::create_profile()` for immediate profile creation on signup.
- `do_action('xen_onboarding_complete', $user_id)` fired at end of `Xen_Onboarding::complete()`.

### Changed
- `includes/class-xen-daily-checkin.php` — All `date('Y-m-d')` calls replaced with `current_time('Y-m-d')` / `wp_date()` for WP-timezone consistency.
- `admin/views/dashboard.php` — Full credit line wrapped in `<span class="xen-credit-full">` (gold).
- `admin/css/admin.css` — Added `.xen-credit-full { color: #FFD700; font-weight: 600; }`.
- `admin/views/settings.php` — Added "Disable WP Dashboard" toggle and "Activity Feed Page" selector.
- `admin/class-xen-admin.php` — `save_settings()` persists `xen_disable_wp_dashboard` and `xen_levelup_feed_page`.
- `public/views/profile.php` — Profile photo upload input added to Edit Profile panel.
- `public/views/tasks.php` — "⚔️ Convert to Quest" button added per pending task.
- `includes/class-xen-levelup.php` — Registers `Xen_Social`, enqueues `xen-social.js`, adds hooks for all social events.
- `includes/class-xen-shortcodes.php` — Added `gamified_feed` → `render_feed()`.
- `includes/class-xen-installer.php` — Added 4 new social tables (19–22).

---

## [1.4.0] - 2026-06-03

### What's New
- 🛠️ **Admin: Edit Hunter Stats** — Admins can now manually edit any user's Level, XP, and Coin balance directly from the Users admin page via a dedicated "Edit Stats" form. Includes bonus XP and bonus Coins fields for quick rewards. Rank title syncs automatically on save.
- 📰 **What's New moved to Admin** — The "What's New" release card is now displayed exclusively on the Admin Dashboard (not on the player-facing dashboard). Dismissible per-version by the admin.
- ✏️ **Admin Users Table** — Added an "Edit Stats" action button per user row in the Hunters admin table.

### Added
- `admin/views/user-edit.php` — new admin form for editing a user's Level, XP, Coins, and awarding bonus amounts.
- `Xen_Admin::handle_save_user_stats()` — `admin_post_xen_admin_save_user_stats` handler; validates nonce, clamps level 1–100, syncs rank title via `Xen_User::rank_title_for_level()`.
- `Xen_Admin::ajax_dismiss_whats_new()` — `wp_ajax_xen_admin_dismiss_whats_new`; stores dismissed version in WP option `xen_admin_whats_new_dismissed`.
- Admin CSS: `.xen-author-xenroth` (gold), `.xen-whats-new-admin-card`, `.xen-user-edit-*` styles.
- Admin JS: dismiss handler for `#xen-admin-dismiss-whats-new`.

### Changed
- `admin/views/dashboard.php` — "What's New" card added at top; "Xenroth" name highlighted in gold; removed duplicate stat-card and leaderboard blocks that appeared after the closing wrap div.
- `public/views/dashboard.php` — "What's New" card and its PHP variables removed (card now admin-only).
- `admin/views/users.php` — added "Actions" column with "Edit Stats" link per row; colspan updated from 8 → 9.
- `admin/class-xen-admin.php` — `page_users()` now routes to `user-edit.php` when `?action=edit&uid=X` is present.

---

## [1.3.0] - 2026-06-05

### What's New
- 🗺️ **Quest Hub** — Unified `[gamified_quest_hub]` shortcode with four tabs: Daily, Side Quests, Legendary, and History. Quests now have a `pending → active → completed` lifecycle; players must explicitly accept side and legendary quests before they become active.
- ✏️ **Profile Editing** — Logged-in users can now edit their display name, bio, and hunter title directly on their profile page (game-style character sheet). Changes are saved via AJAX without a page reload.
- 💰 **Currency Wallet** — New `[gamified_wallet]` shortcode provides a currency wallet with balance overview, peer-to-peer coin transfers, transfer history, and transaction history.
- 🏆 **Legendary Quest Fix** — Legendary quests are now assigned with `pending` status and appear immediately in the Quest Hub. Users must accept them before the timer starts, fixing the issue where quests weren't visible to users on fresh installs.
- 🎨 **Profile v2** — Public profile page redesigned as a Solo Leveling-style character sheet: avatar with level orb, RPG stat bars, equipment grid, achievement grid, and inline edit panel.

### Added
- `xen_accept_quest` AJAX action — transitions a quest from `pending` to `active`.
- `xen_get_quest_hub` AJAX action — returns daily, special, and legendary quest data.
- `xen_update_profile` AJAX action — saves display name, bio, and hunter title.
- `xen_transfer_currency` AJAX action — peer-to-peer coin transfer with validation.
- `xen_get_wallet` AJAX action — returns balance, transactions, and transfer history.
- `Xen_Currency::transfer()` — atomic deduct-and-add with full transaction logging.
- `Xen_Currency::admin_send()` — admin reward helper.
- `Xen_Currency::get_transfer_history()` — queries the new `currency_transfers` table.
- `Xen_Quests::accept_quest()` — validates and promotes a pending quest to active.
- New DB table `{prefix}xen_currency_transfers` (auto-created/upgraded via `dbDelta`).
- New views: `public/views/quest-hub.php`, `public/views/wallet.php`.
- New JS: `public/js/xen-quest-hub.js`, `public/js/xen-profile-wallet.js`.
- New CSS: quest hub cards, profile v2 character sheet, wallet components.

### Fixed
- Legendary quest assignment skips users who already have a `pending` OR `active` legendary quest (previously only checked `active`).
- Special quest generation likewise respects `pending` status to avoid duplicates.
- `expire_stale_quests()` now expires both `active` and `pending` quests past their deadline.

---

## [1.2.1] - 2026-06-04

### What's New
- 🐛 **Dashboard Critical Error Fixed** — A PHP TypeError on PHP 8+ caused a white-screen crash when loading the dashboard. Stats and life trees now render correctly for all users.
- 🧹 **Cleaner Dashboard** — Removed the redundant system-wide overview stats strip (total hunters, total XP, quests done, etc.). The dashboard now focuses entirely on your personal stats.

### Fixed
- `Xen_User::get_full_data()` now returns a properly structured `stats` array (via `get_all_stats()`) instead of a raw `stdClass` DB row, eliminating the TypeError when the view accessed `$stats['life_trees']` on PHP 8+.
- Added missing `xp` key to `get_full_data()` return — the dashboard XP display was always showing 0 because the view read `$user_data['xp']` but the method only returned `experience`.
- `Xen_Stats::get_all_stats()` now strips DB-only columns (`id`, `user_id`, `updated_at`) from the returned `rpg` and `life_trees` arrays, preventing phantom "user_id" entries from appearing in life tree lists.
- Removed the system-wide overview stats block from the public dashboard view (was showing redundant system totals from "Total Hunters" to "Top Hunter" alongside personal stats).

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

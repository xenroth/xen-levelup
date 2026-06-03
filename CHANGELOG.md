# Changelog

All notable changes to **XEN LevelUp** are documented here.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

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

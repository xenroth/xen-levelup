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
Stable tag: 1.0.0
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

= 1.0.0 =
* Initial release
* 10 Life Development Trees + 8 RPG Stats
* Full quest system: Daily, Random, Special, Legendary
* Task manager with priority system
* Habit tracker with streak bonuses
* 22 built-in achievements
* Global, weekly, monthly rankings
* Item shop with 23 seeded items
* 3-step onboarding wizard
* In-app notifications with dropdown
* Full admin panel (9 views)
* REST API namespace `xen/v1`
* 15 shortcodes
* Solo Leveling dark theme with animations

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade path required.

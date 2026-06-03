<?php
/**
 * Plugin installer: activation, deactivation, and database schema.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Installer
 */
class Xen_Installer {

	// ─── Activation ───────────────────────────────────────────────────────

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		self::create_tables();
		self::seed_quest_templates();
		self::seed_achievements();
		self::seed_shop_items();
		self::seed_rank_definitions();
		self::set_default_options();
		self::schedule_cron_events();

		// Store the DB version
		update_option( 'xen_levelup_db_version', XEN_LEVELUP_DB_VERSION );
		update_option( 'xen_levelup_version',    XEN_LEVELUP_VERSION );

		// Flush rewrite rules for any custom post types / endpoints
		flush_rewrite_rules();
	}

	// ─── Deactivation ─────────────────────────────────────────────────────

	/**
	 * Run on plugin deactivation (does NOT remove data).
	 */
	public static function deactivate() {
		// Remove cron events
		$events = array(
			'xen_daily_quest_generation',
			'xen_random_quest_generation',
			'xen_weekly_tasks',
			'xen_rankings_update',
		);
		foreach ( $events as $event ) {
			$timestamp = wp_next_scheduled( $event );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $event );
			}
		}

		flush_rewrite_rules();
	}

	// ─── Database Schema ─────────────────────────────────────────────────

	/**
	 * Create / upgrade all plugin custom tables.
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix . 'xen_';

		// ── 1. User Profiles ───────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}user_profiles (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id         BIGINT(20) UNSIGNED NOT NULL,
			level           SMALLINT(6)          NOT NULL DEFAULT 1,
			experience      BIGINT(20)           NOT NULL DEFAULT 0,
			coins           INT(11)              NOT NULL DEFAULT 0,
			rank_title      VARCHAR(100)         NOT NULL DEFAULT 'Unranked',
			rebirth_count   SMALLINT(6)          NOT NULL DEFAULT 0,
			current_title   VARCHAR(100)                  DEFAULT NULL,
			profile_frame   VARCHAR(100)                  DEFAULT NULL,
			name_color      VARCHAR(50)                   DEFAULT NULL,
			onboarding_done TINYINT(1)           NOT NULL DEFAULT 0,
			total_quests    INT(11)              NOT NULL DEFAULT 0,
			total_tasks     INT(11)              NOT NULL DEFAULT 0,
			total_habits    INT(11)              NOT NULL DEFAULT 0,
			login_streak    INT(11)              NOT NULL DEFAULT 0,
			last_login      DATE                          DEFAULT NULL,
			created_at      DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY   user_id (user_id),
			KEY          level (level),
			KEY          experience (experience),
			KEY          rebirth_count (rebirth_count)
		) $charset;" );

		// ── 2. RPG Stats ───────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}user_stats (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id      BIGINT(20) UNSIGNED NOT NULL,
			strength     SMALLINT(6) NOT NULL DEFAULT 5,
			intelligence SMALLINT(6) NOT NULL DEFAULT 5,
			discipline   SMALLINT(6) NOT NULL DEFAULT 5,
			endurance    SMALLINT(6) NOT NULL DEFAULT 5,
			wisdom       SMALLINT(6) NOT NULL DEFAULT 5,
			charisma     SMALLINT(6) NOT NULL DEFAULT 5,
			focus        SMALLINT(6) NOT NULL DEFAULT 5,
			vitality     SMALLINT(6) NOT NULL DEFAULT 5,
			updated_at   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY  user_id (user_id)
		) $charset;" );

		// ── 3. Life Development Trees ──────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}user_life_trees (
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id       BIGINT(20) UNSIGNED NOT NULL,
			physique      SMALLINT(6) NOT NULL DEFAULT 5,
			intelligence  SMALLINT(6) NOT NULL DEFAULT 5,
			knowledge     SMALLINT(6) NOT NULL DEFAULT 5,
			discipline    SMALLINT(6) NOT NULL DEFAULT 5,
			wealth        SMALLINT(6) NOT NULL DEFAULT 5,
			communication SMALLINT(6) NOT NULL DEFAULT 5,
			leadership    SMALLINT(6) NOT NULL DEFAULT 5,
			relationships SMALLINT(6) NOT NULL DEFAULT 5,
			spirituality  SMALLINT(6) NOT NULL DEFAULT 5,
			longevity     SMALLINT(6) NOT NULL DEFAULT 5,
			updated_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY  user_id (user_id)
		) $charset;" );

		// ── 4. Onboarding ──────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}onboarding (
			id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id          BIGINT(20) UNSIGNED NOT NULL,
			current_step     TINYINT(1)          NOT NULL DEFAULT 0,
			personality_data LONGTEXT                     DEFAULT NULL,
			interests_data   LONGTEXT                     DEFAULT NULL,
			priorities_data  LONGTEXT                     DEFAULT NULL,
			completed        TINYINT(1)          NOT NULL DEFAULT 0,
			created_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_id (user_id)
		) $charset;" );

		// ── 5. Quest Templates ─────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}quest_templates (
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title         VARCHAR(255)        NOT NULL,
			description   TEXT                         DEFAULT NULL,
			category      VARCHAR(50)         NOT NULL DEFAULT 'physique',
			difficulty    VARCHAR(20)         NOT NULL DEFAULT 'easy',
			quest_type    VARCHAR(20)         NOT NULL DEFAULT 'daily',
			xp_reward     INT(11)             NOT NULL DEFAULT 50,
			coin_reward   INT(11)             NOT NULL DEFAULT 10,
			stat_rewards  LONGTEXT                     DEFAULT NULL,
			icon          VARCHAR(10)                  DEFAULT NULL,
			is_active     TINYINT(1)          NOT NULL DEFAULT 1,
			created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY category   (category),
			KEY difficulty (difficulty),
			KEY quest_type (quest_type)
		) $charset;" );

		// ── 6. User Quests (active / archived) ────────────────────────────
		dbDelta( "CREATE TABLE {$p}user_quests (
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id       BIGINT(20) UNSIGNED NOT NULL,
			template_id   BIGINT(20) UNSIGNED          DEFAULT NULL,
			title         VARCHAR(255)        NOT NULL,
			description   TEXT                         DEFAULT NULL,
			category      VARCHAR(50)         NOT NULL DEFAULT 'physique',
			difficulty    VARCHAR(20)         NOT NULL DEFAULT 'easy',
			quest_type    VARCHAR(20)         NOT NULL DEFAULT 'daily',
			xp_reward     INT(11)             NOT NULL DEFAULT 50,
			coin_reward   INT(11)             NOT NULL DEFAULT 10,
			stat_rewards  LONGTEXT                     DEFAULT NULL,
			status        VARCHAR(20)         NOT NULL DEFAULT 'active',
			progress      INT(11)             NOT NULL DEFAULT 0,
			target        INT(11)             NOT NULL DEFAULT 1,
			quest_date    DATE                NOT NULL,
			expires_at    DATETIME                     DEFAULT NULL,
			completed_at  DATETIME                     DEFAULT NULL,
			assigned_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id    (user_id),
			KEY status     (status),
			KEY quest_date (quest_date),
			KEY quest_type (quest_type),
			KEY expires_at (expires_at)
		) $charset;" );

		// ── 7. User Tasks ──────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}user_tasks (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id      BIGINT(20) UNSIGNED NOT NULL,
			title        VARCHAR(255)        NOT NULL,
			description  TEXT                         DEFAULT NULL,
			category     VARCHAR(50)                  DEFAULT 'general',
			priority     VARCHAR(20)         NOT NULL DEFAULT 'medium',
			status       VARCHAR(20)         NOT NULL DEFAULT 'pending',
			due_date     DATE                         DEFAULT NULL,
			notes        TEXT                         DEFAULT NULL,
			xp_reward    INT(11)             NOT NULL DEFAULT 100,
			coin_reward  INT(11)             NOT NULL DEFAULT 20,
			completed_at DATETIME                     DEFAULT NULL,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id  (user_id),
			KEY status   (status),
			KEY due_date (due_date)
		) $charset;" );

		// ── 8. Habits ──────────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}habits (
			id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id           BIGINT(20) UNSIGNED NOT NULL,
			title             VARCHAR(255)        NOT NULL,
			description       TEXT                         DEFAULT NULL,
			category          VARCHAR(50)         NOT NULL DEFAULT 'custom',
			icon              VARCHAR(10)                  DEFAULT NULL,
			current_streak    INT(11)             NOT NULL DEFAULT 0,
			longest_streak    INT(11)             NOT NULL DEFAULT 0,
			total_completions INT(11)             NOT NULL DEFAULT 0,
			last_logged       DATE                         DEFAULT NULL,
			is_active         TINYINT(1)          NOT NULL DEFAULT 1,
			created_at        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id  (user_id),
			KEY category (category),
			KEY is_active (is_active)
		) $charset;" );

		// ── 9. Habit Logs ──────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}habit_logs (
			id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			habit_id   BIGINT(20) UNSIGNED NOT NULL,
			user_id    BIGINT(20) UNSIGNED NOT NULL,
			log_date   DATE               NOT NULL,
			notes      TEXT                        DEFAULT NULL,
			created_at DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_habit_day (habit_id, log_date),
			KEY user_id  (user_id),
			KEY log_date (log_date)
		) $charset;" );

		// ── 10. Achievements (definitions) ────────────────────────────────
		dbDelta( "CREATE TABLE {$p}achievements (
			id                  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			slug                VARCHAR(100)        NOT NULL,
			title               VARCHAR(255)        NOT NULL,
			description         TEXT                         DEFAULT NULL,
			icon                VARCHAR(10)                  DEFAULT '🏆',
			category            VARCHAR(50)         NOT NULL DEFAULT 'general',
			xp_reward           INT(11)             NOT NULL DEFAULT 0,
			coin_reward         INT(11)             NOT NULL DEFAULT 0,
			requirement_type    VARCHAR(50)         NOT NULL,
			requirement_value   INT(11)             NOT NULL DEFAULT 1,
			requirement_extra   VARCHAR(100)                 DEFAULT NULL,
			is_active           TINYINT(1)          NOT NULL DEFAULT 1,
			created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug)
		) $charset;" );

		// ── 11. User Achievements ─────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}user_achievements (
			id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id        BIGINT(20) UNSIGNED NOT NULL,
			achievement_id BIGINT(20) UNSIGNED NOT NULL,
			earned_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_achievement (user_id, achievement_id),
			KEY user_id (user_id)
		) $charset;" );

		// ── 12. Currency Transactions ─────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}currency_transactions (
			id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id        BIGINT(20) UNSIGNED NOT NULL,
			amount         INT(11)             NOT NULL,
			type           VARCHAR(20)         NOT NULL,
			description    VARCHAR(255)                 DEFAULT NULL,
			reference_id   BIGINT(20)                   DEFAULT NULL,
			reference_type VARCHAR(50)                  DEFAULT NULL,
			balance_after  INT(11)             NOT NULL DEFAULT 0,
			created_at     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id   (user_id),
			KEY type      (type),
			KEY created_at (created_at)
		) $charset;" );

		// ── 12b. Currency Transfers ───────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}currency_transfers (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			sender_id   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			receiver_id BIGINT(20) UNSIGNED NOT NULL,
			amount      INT(11)             NOT NULL,
			note        VARCHAR(255)                 DEFAULT NULL,
			type        VARCHAR(30)         NOT NULL DEFAULT 'transfer',
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY sender_id   (sender_id),
			KEY receiver_id (receiver_id),
			KEY created_at  (created_at)
		) $charset;" );

		// ── 13. Shop Items ────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}shop_items (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title       VARCHAR(255)        NOT NULL,
			description TEXT                         DEFAULT NULL,
			item_type   VARCHAR(50)         NOT NULL,
			item_data   LONGTEXT                     DEFAULT NULL,
			price       INT(11)             NOT NULL DEFAULT 0,
			is_premium  TINYINT(1)          NOT NULL DEFAULT 0,
			is_active   TINYINT(1)          NOT NULL DEFAULT 1,
			image_url   VARCHAR(255)                 DEFAULT NULL,
			sort_order  INT(11)             NOT NULL DEFAULT 0,
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY item_type (item_type),
			KEY is_active (is_active)
		) $charset;" );

		// ── 14. User Inventory ────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}user_inventory (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id      BIGINT(20) UNSIGNED NOT NULL,
			item_id      BIGINT(20) UNSIGNED NOT NULL,
			is_equipped  TINYINT(1)          NOT NULL DEFAULT 0,
			purchased_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_item (user_id, item_id),
			KEY user_id (user_id)
		) $charset;" );

		// ── 15. Notifications ─────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}notifications (
			id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id    BIGINT(20) UNSIGNED NOT NULL,
			type       VARCHAR(50)         NOT NULL,
			title      VARCHAR(255)        NOT NULL,
			message    TEXT                         DEFAULT NULL,
			data       LONGTEXT                     DEFAULT NULL,
			is_read    TINYINT(1)          NOT NULL DEFAULT 0,
			created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id  (user_id),
			KEY is_read  (is_read),
			KEY type     (type),
			KEY created_at (created_at)
		) $charset;" );

		// ── 16. Rankings Cache ────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}rankings (
			id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id          BIGINT(20) UNSIGNED NOT NULL,
			period_type      VARCHAR(20)         NOT NULL DEFAULT 'global',
			period_key       VARCHAR(20)         NOT NULL DEFAULT 'all',
			rank_position    INT(11)             NOT NULL DEFAULT 0,
			score            BIGINT(20)          NOT NULL DEFAULT 0,
			level            SMALLINT(6)         NOT NULL DEFAULT 1,
			quests_completed INT(11)             NOT NULL DEFAULT 0,
			tasks_completed  INT(11)             NOT NULL DEFAULT 0,
			updated_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_period (user_id, period_type, period_key),
			KEY period_type   (period_type),
			KEY rank_position (rank_position),
			KEY score         (score)
		) $charset;" );

		// ── 17. XP Log ────────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}xp_log (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id      BIGINT(20) UNSIGNED NOT NULL,
			xp_amount    INT(11)             NOT NULL,
			source_type  VARCHAR(50)         NOT NULL,
			source_id    BIGINT(20)                   DEFAULT NULL,
			description  VARCHAR(255)                 DEFAULT NULL,
			level_before SMALLINT(6)         NOT NULL DEFAULT 1,
			level_after  SMALLINT(6)         NOT NULL DEFAULT 1,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id    (user_id),
			KEY source_type (source_type),
			KEY created_at  (created_at)
		) $charset;" );

		// ── 18. Daily Check-Ins ───────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}checkins (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id      BIGINT(20) UNSIGNED NOT NULL,
			checkin_date DATE                NOT NULL,
			streak       INT(11)             NOT NULL DEFAULT 1,
			xp_earned    INT(11)             NOT NULL DEFAULT 0,
			coins_earned INT(11)             NOT NULL DEFAULT 0,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_date (user_id, checkin_date),
			KEY user_id      (user_id),
			KEY checkin_date (checkin_date)
		) $charset;" );

		// ── 19. Activity Feed ─────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}activity_feed (
			id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id    BIGINT(20) UNSIGNED NOT NULL,
			type       VARCHAR(50)         NOT NULL,
			content    TEXT                         DEFAULT NULL,
			meta_data  LONGTEXT                     DEFAULT NULL,
			created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id    (user_id),
			KEY type       (type),
			KEY created_at (created_at)
		) $charset;" );

		// ── 20. Activity Reactions (Likes) ────────────────────────────────
		dbDelta( "CREATE TABLE {$p}activity_reactions (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			activity_id BIGINT(20) UNSIGNED NOT NULL,
			user_id     BIGINT(20) UNSIGNED NOT NULL,
			reaction    VARCHAR(20)         NOT NULL DEFAULT 'like',
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_activity (user_id, activity_id),
			KEY activity_id (activity_id)
		) $charset;" );

		// ── 21. Activity Comments ─────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}activity_comments (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			activity_id BIGINT(20) UNSIGNED NOT NULL,
			user_id     BIGINT(20) UNSIGNED NOT NULL,
			content     TEXT                NOT NULL,
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY activity_id (activity_id),
			KEY user_id     (user_id)
		) $charset;" );

		// ── 22. Friends ───────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}friends (
			id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id    BIGINT(20) UNSIGNED NOT NULL,
			friend_id  BIGINT(20) UNSIGNED NOT NULL,
			status     VARCHAR(20)         NOT NULL DEFAULT 'pending',
			created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY pair (user_id, friend_id),
			KEY user_id   (user_id),
			KEY friend_id (friend_id),
			KEY status    (status)
		) $charset;" );

		// ── 23. Rank Definitions ──────────────────────────────────────────
		dbDelta( "CREATE TABLE {$p}rank_definitions (
			id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title            VARCHAR(100)        NOT NULL,
			icon             VARCHAR(20)                  DEFAULT NULL,
			color            VARCHAR(20)                  DEFAULT NULL,
			rebirth_required SMALLINT(6)         NOT NULL DEFAULT 0,
			description      TEXT                         DEFAULT NULL,
			sort_order       INT(11)             NOT NULL DEFAULT 0,
			is_active        TINYINT(1)          NOT NULL DEFAULT 1,
			created_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY rebirth_required (rebirth_required),
			KEY sort_order       (sort_order)
		) $charset;" );
	}

	// ─── Default Options ─────────────────────────────────────────────────

	/**
	 * Seed the default rank definitions (only runs once — skips if rows already exist).
	 */
	private static function seed_rank_definitions() {
		global $wpdb;
		$table = $wpdb->prefix . 'xen_rank_definitions';

		// Skip if already seeded
		if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) > 0 ) {
			return;
		}

		$defaults = array(
			array( 'Unranked',                '⚫', '#6b7280', 0, 'Starting rank — no rebirths yet.',          0 ),
			array( 'E-Rank Hunter',           '🟤', '#a16207', 1, 'First rebirth — entered the system.',       10 ),
			array( 'D-Rank Hunter',           '⚪', '#9ca3af', 2, 'Second rebirth — gaining strength.',        20 ),
			array( 'C-Rank Hunter',           '🟢', '#16a34a', 3, 'Third rebirth — competent hunter.',         30 ),
			array( 'B-Rank Hunter',           '🔵', '#2563eb', 4, 'Fourth rebirth — elite combatant.',         40 ),
			array( 'A-Rank Hunter',           '🟡', '#ca8a04', 5, 'Fifth rebirth — top-class hunter.',         50 ),
			array( 'S-Rank Hunter',           '🔴', '#dc2626', 6, 'Sixth rebirth — national-class threat.',    60 ),
			array( 'National-Level Hunter',   '🟣', '#7c3aed', 7, 'Seventh rebirth — world-level power.',      70 ),
			array( 'Shadow Monarch',          '⭐', '#eab308', 8, 'Eighth rebirth — apex existence.',          80 ),
		);

		foreach ( $defaults as $r ) {
			$wpdb->insert(
				$table,
				array(
					'title'            => $r[0],
					'icon'             => $r[1],
					'color'            => $r[2],
					'rebirth_required' => $r[3],
					'description'      => $r[4],
					'sort_order'       => $r[5],
					'is_active'        => 1,
				),
				array( '%s', '%s', '%s', '%d', '%s', '%d', '%d' )
			);
		}
	}

	/**
	 * Set default plugin options on first activation.
	 */
	private static function set_default_options() {
		$defaults = array(
			'xen_levelup_daily_quest_count'     => 7,
			'xen_levelup_main_quest_count'      => 5,
			'xen_levelup_secondary_quest_count' => 2,
			'xen_levelup_legendary_user_count'  => 10,
			'xen_levelup_currency_name'         => 'System Coins',
			'xen_levelup_currency_symbol'       => '💎',
			'xen_levelup_checkin_base_xp'       => 50,
			'xen_levelup_checkin_base_coins'    => 10,
			'xen_levelup_max_level'             => 100,
			'xen_levelup_login_page'            => '',
			'xen_levelup_dashboard_page'        => '',
			'xen_levelup_profile_page'          => '',
			'xen_levelup_shop_page'             => '',
			'xen_levelup_rankings_page'         => '',
			'xen_levelup_enable_notifications'  => 1,
			'xen_levelup_enable_particles'      => 1,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				update_option( $key, $value );
			}
		}
	}

	// ─── Cron ─────────────────────────────────────────────────────────────

	/**
	 * Schedule all recurring cron events.
	 */
	private static function schedule_cron_events() {
		if ( ! wp_next_scheduled( 'xen_daily_quest_generation' ) ) {
			// Midnight local time
			$midnight = strtotime( 'tomorrow midnight' );
			wp_schedule_event( $midnight, 'daily', 'xen_daily_quest_generation' );
		}

		if ( ! wp_next_scheduled( 'xen_random_quest_generation' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'xen_random_quest_generation' );
		}

		if ( ! wp_next_scheduled( 'xen_weekly_tasks' ) ) {
			wp_schedule_event( strtotime( 'next monday midnight' ), 'weekly', 'xen_weekly_tasks' );
		}

		if ( ! wp_next_scheduled( 'xen_rankings_update' ) ) {
			wp_schedule_event( time() + 3600, 'twicedaily', 'xen_rankings_update' );
		}
	}

	// ─── Seed Quest Templates ────────────────────────────────────────────

	/**
	 * Insert default quest templates.
	 */
	public static function seed_quest_templates() {
		global $wpdb;
		$table = $wpdb->prefix . 'xen_quest_templates';

		// Only seed if table is empty
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
		if ( $count > 0 ) {
			return;
		}

		$templates = self::get_quest_template_data();

		foreach ( $templates as $t ) {
			$wpdb->insert(
				$table,
				array(
					'title'       => $t['title'],
					'description' => $t['description'],
					'category'    => $t['category'],
					'difficulty'  => $t['difficulty'],
					'quest_type'  => $t['type'],
					'xp_reward'   => $t['xp'],
					'coin_reward' => $t['coins'],
					'stat_rewards'=> wp_json_encode( $t['stats'] ),
					'icon'        => $t['icon'],
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Quest template data array.
	 *
	 * @return array[]
	 */
	private static function get_quest_template_data() {
		return array(
			// ── PHYSIQUE ──────────────────────────────────────────────────
			array( 'title' => 'Drink 2 Liters of Water',             'description' => 'Stay hydrated throughout the day.',               'category' => 'physique',      'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'vitality' => 1 ),                                  'icon' => '💧' ),
			array( 'title' => 'Walk 3,000 Steps',                    'description' => 'Take a 3,000 step walk.',                          'category' => 'physique',      'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'strength' => 1, 'endurance' => 1 ),               'icon' => '🚶' ),
			array( 'title' => 'Walk 6,000 Steps',                    'description' => 'Reach 6,000 steps today.',                         'category' => 'physique',      'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'strength' => 1, 'endurance' => 2 ),               'icon' => '🚶' ),
			array( 'title' => 'Walk 10,000 Steps',                   'description' => 'Complete 10,000 steps today.',                     'category' => 'physique',      'difficulty' => 'hard',      'type' => 'daily',   'xp' => 250,  'coins' => 40, 'stats' => array( 'strength' => 2, 'endurance' => 3 ),               'icon' => '🚶' ),
			array( 'title' => 'Complete 20 Pushups',                 'description' => 'Do 20 consecutive pushups.',                       'category' => 'physique',      'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'strength' => 2 ),                               'icon' => '💪' ),
			array( 'title' => 'Complete 50 Pushups',                 'description' => 'Complete 50 pushups throughout the day.',          'category' => 'physique',      'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'strength' => 3 ),                               'icon' => '💪' ),
			array( 'title' => 'Complete 100 Pushups',                'description' => 'Hit 100 pushups — beast mode.',                    'category' => 'physique',      'difficulty' => 'hard',      'type' => 'daily',   'xp' => 250,  'coins' => 40, 'stats' => array( 'strength' => 5 ),                               'icon' => '💪' ),
			array( 'title' => 'Do 20 Squats',                        'description' => 'Strengthen your legs with 20 squats.',             'category' => 'physique',      'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'strength' => 1, 'endurance' => 1 ),               'icon' => '🏋️' ),
			array( 'title' => 'Exercise for 30 Minutes',             'description' => 'Complete any form of exercise for 30 minutes.',    'category' => 'physique',      'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'strength' => 2, 'endurance' => 2 ),               'icon' => '🏃' ),
			array( 'title' => 'Exercise for 60 Minutes',             'description' => 'Power through a full hour of exercise.',           'category' => 'physique',      'difficulty' => 'hard',      'type' => 'daily',   'xp' => 250,  'coins' => 40, 'stats' => array( 'strength' => 3, 'endurance' => 3, 'vitality' => 2 ), 'icon' => '🏃' ),
			array( 'title' => 'Complete a HIIT Workout',             'description' => 'Push your limits with a HIIT session.',            'category' => 'physique',      'difficulty' => 'hard',      'type' => 'daily',   'xp' => 250,  'coins' => 40, 'stats' => array( 'strength' => 3, 'endurance' => 4 ),               'icon' => '⚡' ),
			array( 'title' => 'Stretch for 15 Minutes',              'description' => 'Improve flexibility with a stretching session.',   'category' => 'physique',      'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'vitality' => 1 ),                                  'icon' => '🧘' ),
			array( 'title' => 'Sleep 8+ Hours',                      'description' => 'Get quality sleep for peak performance.',          'category' => 'physique',      'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'vitality' => 2, 'endurance' => 1 ),               'icon' => '😴' ),
			array( 'title' => 'No Junk Food Today',                  'description' => 'Eat clean — zero junk food for the full day.',     'category' => 'physique',      'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'vitality' => 2, 'discipline' => 1 ),               'icon' => '🥗' ),
			array( 'title' => 'Run for 20 Minutes',                  'description' => 'Hit the pavement for a 20-minute run.',            'category' => 'physique',      'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'strength' => 2, 'endurance' => 3 ),               'icon' => '🏃' ),
			array( 'title' => 'Exercise 5 Days Straight',            'description' => 'Complete a 5-day exercise streak.',                'category' => 'physique',      'difficulty' => 'very_hard', 'type' => 'special', 'xp' => 500,  'coins' => 100,'stats' => array( 'strength' => 5, 'endurance' => 5, 'discipline' => 3 ), 'icon' => '🔥' ),

			// ── INTELLIGENCE ──────────────────────────────────────────────
			array( 'title' => 'Watch an Educational Video',          'description' => 'Learn something new from a quality video.',        'category' => 'intelligence',  'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'intelligence' => 1 ),                             'icon' => '📺' ),
			array( 'title' => 'Read for 20 Minutes',                 'description' => 'Spend 20 minutes reading anything educational.',   'category' => 'intelligence',  'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'intelligence' => 1, 'wisdom' => 1 ),               'icon' => '📖' ),
			array( 'title' => 'Read 10 Pages',                       'description' => 'Read 10 pages of a book or article.',              'category' => 'intelligence',  'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'intelligence' => 1, 'wisdom' => 1 ),               'icon' => '📚' ),
			array( 'title' => 'Read 30 Pages',                       'description' => 'Deep-dive with 30 pages of reading.',              'category' => 'intelligence',  'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'intelligence' => 2, 'wisdom' => 2 ),               'icon' => '📚' ),
			array( 'title' => 'Learn One New Concept',               'description' => 'Research and understand a new idea or concept.',   'category' => 'intelligence',  'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'intelligence' => 2 ),                             'icon' => '💡' ),
			array( 'title' => 'Complete an Online Lesson',           'description' => 'Finish at least one lesson on any learning platform.','category' => 'intelligence', 'difficulty' => 'medium',  'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'intelligence' => 2, 'focus' => 1 ),                'icon' => '🎓' ),
			array( 'title' => 'Write a 200-Word Summary',            'description' => 'Summarize what you learned in 200 words.',          'category' => 'intelligence',  'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'intelligence' => 2, 'wisdom' => 1 ),               'icon' => '✍️' ),
			array( 'title' => 'Read an Entire Book',                 'description' => 'Complete an entire book cover to cover.',           'category' => 'intelligence',  'difficulty' => 'very_hard', 'type' => 'special', 'xp' => 500,  'coins' => 100,'stats' => array( 'intelligence' => 5, 'wisdom' => 5, 'knowledge' => 5 ), 'icon' => '📗' ),

			// ── KNOWLEDGE ─────────────────────────────────────────────────
			array( 'title' => 'Write Down 5 New Facts',              'description' => 'Record 5 facts you learned today.',                'category' => 'knowledge',     'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'knowledge' => 1 ),                                'icon' => '📝' ),
			array( 'title' => 'Study for 30 Minutes',                'description' => 'Focus on studying a subject for 30 minutes.',      'category' => 'knowledge',     'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'knowledge' => 2, 'focus' => 2 ),                  'icon' => '📓' ),
			array( 'title' => 'Read an Article in Your Field',       'description' => 'Stay current in your area of expertise.',          'category' => 'knowledge',     'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'knowledge' => 2 ),                                'icon' => '📰' ),
			array( 'title' => 'Complete a Course Module',            'description' => 'Finish a module from any course.',                  'category' => 'knowledge',     'difficulty' => 'hard',      'type' => 'daily',   'xp' => 250,  'coins' => 40, 'stats' => array( 'knowledge' => 4, 'intelligence' => 2 ),           'icon' => '🎓' ),
			array( 'title' => 'Practice a New Skill for 20 Minutes', 'description' => 'Dedicate 20 minutes to practicing a new skill.',   'category' => 'knowledge',     'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'knowledge' => 2, 'focus' => 1 ),                  'icon' => '🛠️' ),

			// ── DISCIPLINE ────────────────────────────────────────────────
			array( 'title' => 'Wake Up Before 6 AM',                 'description' => 'Rise early — before 6:00 AM.',                     'category' => 'discipline',    'difficulty' => 'hard',      'type' => 'daily',   'xp' => 250,  'coins' => 40, 'stats' => array( 'discipline' => 4, 'focus' => 2 ),                 'icon' => '⏰' ),
			array( 'title' => 'Wake Up Before 7 AM',                 'description' => 'Start your day early — before 7:00 AM.',           'category' => 'discipline',    'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'discipline' => 2 ),                               'icon' => '⏰' ),
			array( 'title' => 'Meditate for 10 Minutes',             'description' => 'Clear your mind with 10 minutes of meditation.',   'category' => 'discipline',    'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'discipline' => 1, 'focus' => 2, 'wisdom' => 1 ),  'icon' => '🧘' ),
			array( 'title' => 'Meditate for 20 Minutes',             'description' => 'Deep meditation session — 20 full minutes.',       'category' => 'discipline',    'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'discipline' => 2, 'focus' => 3, 'wisdom' => 2 ),  'icon' => '🧘' ),
			array( 'title' => 'No Social Media for 3 Hours',         'description' => 'Avoid all social media for 3 hours straight.',     'category' => 'discipline',    'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'discipline' => 3, 'focus' => 2 ),                 'icon' => '📵' ),
			array( 'title' => 'No Social Media All Day',             'description' => 'Complete a full digital detox for one day.',       'category' => 'discipline',    'difficulty' => 'very_hard', 'type' => 'daily',   'xp' => 500,  'coins' => 80, 'stats' => array( 'discipline' => 6, 'focus' => 4 ),                 'icon' => '📵' ),
			array( 'title' => 'Complete All Planned Tasks',          'description' => 'Finish every task on your daily list.',            'category' => 'discipline',    'difficulty' => 'hard',      'type' => 'daily',   'xp' => 250,  'coins' => 40, 'stats' => array( 'discipline' => 4, 'focus' => 2 ),                 'icon' => '✅' ),
			array( 'title' => 'Journal for 10 Minutes',              'description' => 'Write in your journal for 10 minutes.',            'category' => 'discipline',    'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'discipline' => 1, 'wisdom' => 2 ),                'icon' => '📔' ),
			array( 'title' => 'Deep Work for 90 Minutes',            'description' => 'Zero distractions — 90 minutes pure focus.',      'category' => 'discipline',    'difficulty' => 'hard',      'type' => 'daily',   'xp' => 250,  'coins' => 40, 'stats' => array( 'discipline' => 4, 'focus' => 5 ),                 'icon' => '🎯' ),
			array( 'title' => 'Plan Tomorrow the Night Before',      'description' => 'Write your plan for tomorrow before bed.',         'category' => 'discipline',    'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'discipline' => 2 ),                               'icon' => '📋' ),
			array( 'title' => 'Complete 20 Tasks in a Week',         'description' => 'Crush 20 tasks within a single week.',             'category' => 'discipline',    'difficulty' => 'very_hard', 'type' => 'special', 'xp' => 500,  'coins' => 100,'stats' => array( 'discipline' => 6, 'focus' => 4 ),                 'icon' => '🏆' ),

			// ── WEALTH ────────────────────────────────────────────────────
			array( 'title' => 'Track All Expenses Today',            'description' => 'Record every purchase you make today.',            'category' => 'wealth',        'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'wealth' => 1 ),                                   'icon' => '💰' ),
			array( 'title' => 'Work on a Side Project (30 min)',     'description' => 'Invest 30 minutes into building something.',       'category' => 'wealth',        'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'wealth' => 2, 'discipline' => 1 ),                'icon' => '💼' ),
			array( 'title' => 'Learn One Financial Concept',         'description' => 'Study investing, budgeting, or business basics.',  'category' => 'wealth',        'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'wealth' => 2, 'intelligence' => 1 ),              'icon' => '📈' ),
			array( 'title' => 'Complete One Work Deliverable',       'description' => 'Finish and submit a professional task.',           'category' => 'wealth',        'difficulty' => 'hard',      'type' => 'daily',   'xp' => 250,  'coins' => 40, 'stats' => array( 'wealth' => 4, 'discipline' => 2 ),                'icon' => '📊' ),
			array( 'title' => 'Network with One Professional',       'description' => 'Reach out and connect with a professional.',       'category' => 'wealth',        'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'wealth' => 2, 'communication' => 2 ),             'icon' => '🤝' ),

			// ── COMMUNICATION ─────────────────────────────────────────────
			array( 'title' => 'Express Gratitude to 3 People',       'description' => 'Tell 3 people something you appreciate about them.','category' => 'communication', 'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'communication' => 1, 'relationships' => 1 ),       'icon' => '🙏' ),
			array( 'title' => 'Have a Meaningful Conversation',      'description' => 'Engage in a deep, productive conversation.',       'category' => 'communication', 'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'communication' => 2, 'charisma' => 1 ),            'icon' => '💬' ),
			array( 'title' => 'Practice Public Speaking 10 Minutes', 'description' => 'Speak aloud or record yourself for 10 minutes.',   'category' => 'communication', 'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'communication' => 3, 'charisma' => 2 ),            'icon' => '🎤' ),
			array( 'title' => 'Write 300 Words',                     'description' => 'Write creatively, professionally, or for a journal.','category' => 'communication', 'difficulty' => 'easy',    'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'communication' => 2 ),                            'icon' => '✍️' ),

			// ── LEADERSHIP ────────────────────────────────────────────────
			array( 'title' => 'Set Clear Goals for the Week',        'description' => 'Define and write down your weekly goals.',         'category' => 'leadership',    'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'leadership' => 2, 'discipline' => 1 ),             'icon' => '🎯' ),
			array( 'title' => 'Help Someone Solve a Problem',        'description' => 'Actively help another person with their challenge.','category' => 'leadership',   'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'leadership' => 2, 'charisma' => 1 ),               'icon' => '🤝' ),
			array( 'title' => 'Make One Important Decision',         'description' => 'Identify and act on one key decision.',            'category' => 'leadership',    'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'leadership' => 3, 'wisdom' => 1 ),                 'icon' => '⚖️' ),

			// ── RELATIONSHIPS ─────────────────────────────────────────────
			array( 'title' => 'Check in with a Friend',              'description' => 'Send a message or call a friend you care about.',  'category' => 'relationships', 'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'relationships' => 1 ),                            'icon' => '👥' ),
			array( 'title' => 'Call a Family Member',                'description' => 'Connect with a family member via phone or video.',  'category' => 'relationships', 'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'relationships' => 2, 'charisma' => 1 ),            'icon' => '👨‍👩‍👧' ),
			array( 'title' => 'Do Something Kind for Someone',       'description' => 'Perform a random act of kindness.',                'category' => 'relationships', 'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'relationships' => 2, 'charisma' => 1 ),            'icon' => '❤️' ),
			array( 'title' => 'Spend Quality Time with Loved Ones',  'description' => 'Dedicate focused time to the people who matter.',  'category' => 'relationships', 'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'relationships' => 3 ),                            'icon' => '🏠' ),

			// ── SPIRITUALITY ──────────────────────────────────────────────
			array( 'title' => 'Write 3 Gratitude Entries',           'description' => 'Note three things you are grateful for today.',    'category' => 'spirituality',  'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'spirituality' => 1, 'wisdom' => 1 ),               'icon' => '🌟' ),
			array( 'title' => 'Practice Mindfulness for 10 Minutes', 'description' => 'Be fully present for 10 minutes.',                 'category' => 'spirituality',  'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'spirituality' => 2, 'focus' => 2 ),                'icon' => '☯️' ),
			array( 'title' => 'Spend 20 Minutes in Quiet Reflection','description' => 'Disconnect and reflect on your life and goals.',   'category' => 'spirituality',  'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'spirituality' => 2, 'wisdom' => 2 ),               'icon' => '🌅' ),
			array( 'title' => 'Practice Breathing Exercises',        'description' => 'Complete a breathing exercise routine.',           'category' => 'spirituality',  'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'spirituality' => 1, 'vitality' => 1 ),             'icon' => '🌬️' ),
			array( 'title' => 'Digital Detox for 2 Hours',           'description' => 'No screens or digital devices for 2 hours.',      'category' => 'spirituality',  'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'spirituality' => 3, 'discipline' => 2 ),           'icon' => '📵' ),

			// ── LONGEVITY ─────────────────────────────────────────────────
			array( 'title' => 'Drink 8 Glasses of Water',            'description' => 'Reach your daily hydration goal.',                 'category' => 'longevity',     'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'vitality' => 1, 'longevity' => 1 ),               'icon' => '💧' ),
			array( 'title' => 'Eat 3+ Servings of Vegetables',       'description' => 'Include vegetables in every meal today.',          'category' => 'longevity',     'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'vitality' => 2, 'longevity' => 1 ),               'icon' => '🥦' ),
			array( 'title' => 'Spend 20 Minutes Outside',            'description' => 'Get fresh air and natural light.',                 'category' => 'longevity',     'difficulty' => 'very_easy', 'type' => 'daily',   'xp' => 20,   'coins' => 5,  'stats' => array( 'vitality' => 1, 'longevity' => 1 ),               'icon' => '🌳' ),
			array( 'title' => 'No Processed Food Today',             'description' => 'Eat only whole, natural foods.',                   'category' => 'longevity',     'difficulty' => 'medium',    'type' => 'daily',   'xp' => 100,  'coins' => 20, 'stats' => array( 'vitality' => 3, 'longevity' => 2, 'discipline' => 1 ), 'icon' => '🥗' ),
			array( 'title' => 'Practice Stress-Reduction Techniques','description' => 'Do a stress management activity.',                 'category' => 'longevity',     'difficulty' => 'easy',      'type' => 'daily',   'xp' => 50,   'coins' => 10, 'stats' => array( 'vitality' => 2, 'longevity' => 2 ),               'icon' => '😌' ),

			// ── LEGENDARY ─────────────────────────────────────────────────
			array( 'title' => '10,000 Steps Daily for 7 Days',       'description' => 'Walk 10,000 steps every day for a full week.',     'category' => 'physique',      'difficulty' => 'legendary', 'type' => 'legendary','xp' => 5000, 'coins' => 500,'stats' => array( 'strength' => 10, 'endurance' => 10, 'vitality' => 5 ), 'icon' => '👑' ),
			array( 'title' => 'Read 100 Pages in One Week',          'description' => 'Read 100 pages across 7 days.',                    'category' => 'intelligence',  'difficulty' => 'legendary', 'type' => 'legendary','xp' => 5000, 'coins' => 500,'stats' => array( 'intelligence' => 10, 'wisdom' => 8, 'knowledge' => 8 ), 'icon' => '👑' ),
			array( 'title' => 'Zero Screen Time After 9 PM for 7 Days','description' => 'No screens after 9 PM every day this week.',   'category' => 'discipline',    'difficulty' => 'legendary', 'type' => 'legendary','xp' => 5000, 'coins' => 500,'stats' => array( 'discipline' => 12, 'vitality' => 6, 'focus' => 8 ),   'icon' => '👑' ),
			array( 'title' => 'Complete a Major Life Goal Milestone', 'description' => 'Make a significant, measurable life improvement.', 'category' => 'discipline',    'difficulty' => 'legendary', 'type' => 'legendary','xp' => 7500, 'coins' => 750,'stats' => array( 'discipline' => 15, 'wisdom' => 10, 'focus' => 10 ),  'icon' => '👑' ),
		);
	}

	// ─── Seed Achievements ────────────────────────────────────────────────

	/**
	 * Insert default achievement definitions.
	 */
	public static function seed_achievements() {
		global $wpdb;
		$table = $wpdb->prefix . 'xen_achievements';

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
		if ( $count > 0 ) {
			return;
		}

		$achievements = array(
			// Leveling
			array( 'slug' => 'first_steps',      'title' => 'First Steps',        'description' => 'Complete your first quest.',                  'icon' => '👣', 'category' => 'quests',    'xp' => 50,   'coins' => 20,  'req_type' => 'total_quests',    'req_value' => 1   ),
			array( 'slug' => 'level_5',           'title' => 'Rising Hunter',      'description' => 'Reach Level 5.',                              'icon' => '⚡', 'category' => 'leveling',  'xp' => 100,  'coins' => 50,  'req_type' => 'level',           'req_value' => 5   ),
			array( 'slug' => 'level_10',          'title' => 'E-Rank Conqueror',   'description' => 'Reach Level 10.',                             'icon' => '🔷', 'category' => 'leveling',  'xp' => 200,  'coins' => 100, 'req_type' => 'level',           'req_value' => 10  ),
			array( 'slug' => 'level_25',          'title' => 'C-Rank Hunter',      'description' => 'Reach Level 25.',                             'icon' => '💠', 'category' => 'leveling',  'xp' => 500,  'coins' => 250, 'req_type' => 'level',           'req_value' => 25  ),
			array( 'slug' => 'level_50',          'title' => 'A-Rank Elite',       'description' => 'Reach Level 50.',                             'icon' => '🌟', 'category' => 'leveling',  'xp' => 1000, 'coins' => 500, 'req_type' => 'level',           'req_value' => 50  ),
			array( 'slug' => 'level_75',          'title' => 'S-Rank Awakened',    'description' => 'Reach Level 75.',                             'icon' => '⭐', 'category' => 'leveling',  'xp' => 2500, 'coins' => 1000,'req_type' => 'level',           'req_value' => 75  ),
			array( 'slug' => 'level_100',         'title' => 'Shadow Monarch',     'description' => 'Reach the maximum Level 100.',                'icon' => '👑', 'category' => 'leveling',  'xp' => 5000, 'coins' => 2000,'req_type' => 'level',           'req_value' => 100 ),

			// Quests
			array( 'slug' => 'quests_10',         'title' => 'Quest Initiate',     'description' => 'Complete 10 quests.',                         'icon' => '📜', 'category' => 'quests',    'xp' => 100,  'coins' => 50,  'req_type' => 'total_quests',    'req_value' => 10  ),
			array( 'slug' => 'quests_50',         'title' => 'Quest Hunter',       'description' => 'Complete 50 quests.',                         'icon' => '🗡️', 'category' => 'quests',    'xp' => 300,  'coins' => 150, 'req_type' => 'total_quests',    'req_value' => 50  ),
			array( 'slug' => 'quests_100',        'title' => 'Centurion',          'description' => 'Complete 100 quests.',                        'icon' => '⚔️', 'category' => 'quests',    'xp' => 500,  'coins' => 250, 'req_type' => 'total_quests',    'req_value' => 100 ),
			array( 'slug' => 'quests_500',        'title' => 'Legendary Hunter',   'description' => 'Complete 500 quests.',                        'icon' => '🏆', 'category' => 'quests',    'xp' => 2000, 'coins' => 1000,'req_type' => 'total_quests',    'req_value' => 500 ),

			// Tasks
			array( 'slug' => 'tasks_10',          'title' => 'Task Starter',       'description' => 'Complete 10 personal tasks.',                 'icon' => '✅', 'category' => 'tasks',     'xp' => 100,  'coins' => 50,  'req_type' => 'total_tasks',     'req_value' => 10  ),
			array( 'slug' => 'tasks_100',         'title' => 'Productivity Pro',   'description' => 'Complete 100 personal tasks.',                'icon' => '📋', 'category' => 'tasks',     'xp' => 500,  'coins' => 250, 'req_type' => 'total_tasks',     'req_value' => 100 ),

			// Habits
			array( 'slug' => 'streak_7',          'title' => 'Week Warrior',       'description' => 'Maintain a 7-day habit streak.',              'icon' => '🔥', 'category' => 'habits',    'xp' => 200,  'coins' => 100, 'req_type' => 'habit_streak',    'req_value' => 7   ),
			array( 'slug' => 'streak_30',         'title' => 'Monthly Grind',      'description' => 'Maintain a 30-day habit streak.',             'icon' => '💫', 'category' => 'habits',    'xp' => 500,  'coins' => 250, 'req_type' => 'habit_streak',    'req_value' => 30  ),
			array( 'slug' => 'streak_100',        'title' => 'Century Discipline', 'description' => 'Maintain a 100-day habit streak.',            'icon' => '🌠', 'category' => 'habits',    'xp' => 2000, 'coins' => 1000,'req_type' => 'habit_streak',    'req_value' => 100 ),
			array( 'slug' => 'streak_365',        'title' => '365-Day Legend',     'description' => 'Maintain a 365-day habit streak.',            'icon' => '👑', 'category' => 'habits',    'xp' => 10000,'coins' => 5000,'req_type' => 'habit_streak',    'req_value' => 365 ),

			// Special
			array( 'slug' => 'bookworm',          'title' => 'Bookworm',           'description' => 'Complete 10 reading-related quests.',          'icon' => '📚', 'category' => 'special',   'xp' => 300,  'coins' => 150, 'req_type' => 'category_quests', 'req_value' => 10, 'req_extra' => 'intelligence' ),
			array( 'slug' => 'iron_body',         'title' => 'Iron Body',          'description' => 'Complete 20 physical quests.',                 'icon' => '💪', 'category' => 'special',   'xp' => 500,  'coins' => 250, 'req_type' => 'category_quests', 'req_value' => 20, 'req_extra' => 'physique'      ),
			array( 'slug' => 'master_strategist', 'title' => 'Master Strategist',  'description' => 'Complete 15 discipline quests.',               'icon' => '🧠', 'category' => 'special',   'xp' => 400,  'coins' => 200, 'req_type' => 'category_quests', 'req_value' => 15, 'req_extra' => 'discipline'    ),
			array( 'slug' => 'first_legendary',   'title' => 'Chosen by the System','description' => 'Complete your first Legendary Quest.',       'icon' => '⚡', 'category' => 'special',   'xp' => 1000, 'coins' => 500, 'req_type' => 'legendary_quests','req_value' => 1   ),
		);

		foreach ( $achievements as $a ) {
			$extra = isset( $a['req_extra'] ) ? $a['req_extra'] : null;
			$wpdb->insert(
				$table,
				array(
					'slug'              => $a['slug'],
					'title'             => $a['title'],
					'description'       => $a['description'],
					'icon'              => $a['icon'],
					'category'          => $a['category'],
					'xp_reward'         => $a['xp'],
					'coin_reward'       => $a['coins'],
					'requirement_type'  => $a['req_type'],
					'requirement_value' => $a['req_value'],
					'requirement_extra' => $extra,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s' )
			);
		}
	}

	// ─── Seed Shop Items ─────────────────────────────────────────────────

	/**
	 * Insert default shop items.
	 */
	public static function seed_shop_items() {
		global $wpdb;
		$table = $wpdb->prefix . 'xen_shop_items';

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
		if ( $count > 0 ) {
			return;
		}

		$items = array(
			// Frames
			array( 'title' => 'Basic Blue Frame',    'description' => 'A clean blue profile frame.', 'type' => 'frame', 'data' => array( 'css_class' => 'frame-blue-basic' ),    'price' => 200,   'sort' => 1  ),
			array( 'title' => 'Neon Frame',          'description' => 'Animated neon glow frame.',   'type' => 'frame', 'data' => array( 'css_class' => 'frame-neon' ),          'price' => 500,   'sort' => 2  ),
			array( 'title' => 'Golden Frame',        'description' => 'Prestigious golden border.',  'type' => 'frame', 'data' => array( 'css_class' => 'frame-golden' ),        'price' => 1000,  'sort' => 3  ),
			array( 'title' => 'Legendary Frame',     'description' => 'Epic animated frame.',        'type' => 'frame', 'data' => array( 'css_class' => 'frame-legendary' ),    'price' => 5000,  'sort' => 4  ),
			array( 'title' => 'Shadow Monarch Frame','description' => 'Tribute to the Shadow Monarch.','type' => 'frame','data' => array( 'css_class' => 'frame-shadow-monarch' ),'price' => 10000, 'sort' => 5  ),

			// Avatar Borders
			array( 'title' => 'Electric Border',     'description' => 'Crackling electric border.',  'type' => 'border','data' => array( 'css_class' => 'border-electric' ),    'price' => 300,   'sort' => 10 ),
			array( 'title' => 'Fire Border',         'description' => 'Burning fire avatar border.', 'type' => 'border','data' => array( 'css_class' => 'border-fire' ),        'price' => 600,   'sort' => 11 ),
			array( 'title' => 'Ice Border',          'description' => 'Frosty ice avatar border.',   'type' => 'border','data' => array( 'css_class' => 'border-ice' ),         'price' => 600,   'sort' => 12 ),
			array( 'title' => 'Shadow Border',       'description' => 'Dark shadow avatar border.',  'type' => 'border','data' => array( 'css_class' => 'border-shadow' ),      'price' => 1000,  'sort' => 13 ),

			// Name Colors
			array( 'title' => 'Blue Name',           'description' => 'Glowing blue username color.','type' => 'name_color','data' => array( 'color' => '#00D4FF' ),             'price' => 200,   'sort' => 20 ),
			array( 'title' => 'Purple Name',         'description' => 'Vibrant purple username.',   'type' => 'name_color','data' => array( 'color' => '#7B61FF' ),             'price' => 400,   'sort' => 21 ),
			array( 'title' => 'Gold Name',           'description' => 'Prestigious golden name.',   'type' => 'name_color','data' => array( 'color' => '#FFD700' ),             'price' => 600,   'sort' => 22 ),
			array( 'title' => 'Rainbow Name',        'description' => 'Animated rainbow username.', 'type' => 'name_color','data' => array( 'color' => 'rainbow', 'animated' => true ), 'price' => 1500, 'sort' => 23 ),

			// Titles
			array( 'title' => 'The Determined',      'description' => 'Show your iron will.',        'type' => 'title', 'data' => array( 'text' => 'The Determined' ),         'price' => 500,   'sort' => 30 ),
			array( 'title' => 'Shadow Hunter',       'description' => 'Hunter of the shadows.',      'type' => 'title', 'data' => array( 'text' => 'Shadow Hunter' ),          'price' => 1000,  'sort' => 31 ),
			array( 'title' => 'Iron Will',           'description' => 'Your will is unbreakable.',   'type' => 'title', 'data' => array( 'text' => 'Iron Will' ),              'price' => 800,   'sort' => 32 ),
			array( 'title' => 'The Scholar',         'description' => 'Knowledge is power.',         'type' => 'title', 'data' => array( 'text' => 'The Scholar' ),            'price' => 800,   'sort' => 33 ),
			array( 'title' => 'Beast Mode',          'description' => 'Physical perfection.',        'type' => 'title', 'data' => array( 'text' => 'Beast Mode' ),             'price' => 600,   'sort' => 34 ),
			array( 'title' => "System's Chosen",     'description' => 'Chosen by the system itself.','type' => 'title', 'data' => array( 'text' => "System's Chosen" ),        'price' => 2000,  'sort' => 35 ),
			array( 'title' => 'Sovereign',           'description' => 'You stand above all.',        'type' => 'title', 'data' => array( 'text' => 'Sovereign' ),              'price' => 5000,  'sort' => 36 ),

			// Themes
			array( 'title' => 'Shadow Theme',        'description' => 'Darker, more dramatic UI.',   'type' => 'theme', 'data' => array( 'theme_id' => 'shadow' ),             'price' => 2000,  'sort' => 40 ),
			array( 'title' => 'Crimson Theme',       'description' => 'Red accent intensity.',       'type' => 'theme', 'data' => array( 'theme_id' => 'crimson' ),            'price' => 1500,  'sort' => 41 ),
			array( 'title' => 'Gold Theme',          'description' => 'Premium golden experience.',  'type' => 'theme', 'data' => array( 'theme_id' => 'gold' ),               'price' => 2500,  'sort' => 42 ),
		);

		foreach ( $items as $item ) {
			$wpdb->insert(
				$table,
				array(
					'title'       => $item['title'],
					'description' => $item['description'],
					'item_type'   => $item['type'],
					'item_data'   => wp_json_encode( $item['data'] ),
					'price'       => $item['price'],
					'sort_order'  => $item['sort'],
				),
				array( '%s', '%s', '%s', '%s', '%d', '%d' )
			);
		}
	}

	// ─── Upgrade ─────────────────────────────────────────────────────────

	/**
	 * Run database upgrades when plugin version changes.
	 */
	public static function maybe_upgrade() {
		$installed = get_option( 'xen_levelup_db_version', '0' );
		if ( version_compare( $installed, XEN_LEVELUP_DB_VERSION, '<' ) ) {
			self::create_tables();
			update_option( 'xen_levelup_db_version', XEN_LEVELUP_DB_VERSION );
		}
	}
}

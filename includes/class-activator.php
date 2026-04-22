<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

final class WPSB_Activator {

	public static function activate(): void {
		self::create_metrics_table();
		self::set_defaults();
		self::record_install_date();
		self::ensure_hmac_secret();
	}

	private static function create_metrics_table(): void {
		global $wpdb;

		$table           = $wpdb->prefix . 'wpsb_metrics';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			metric_name VARCHAR(16) NOT NULL,
			metric_value DOUBLE NOT NULL,
			url_path VARCHAR(255) NOT NULL,
			device_type VARCHAR(16) NOT NULL,
			anonymous_hash CHAR(32) NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_metric_created (metric_name, created_at),
			KEY idx_url_created (url_path, created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function set_defaults(): void {
		if ( ! get_option( WPSB_OPTION_KEY ) ) {
			require_once WPSB_PLUGIN_DIR . 'includes/class-settings.php';
			add_option( WPSB_OPTION_KEY, WPSB_Settings::defaults() );
		}
		if ( ! get_option( 'wpsb_version' ) ) {
			add_option( 'wpsb_version', WPSB_VERSION, '', 'yes' );
		}
	}

	private static function record_install_date(): void {
		if ( ! get_option( 'wpsb_install_date' ) ) {
			add_option( 'wpsb_install_date', current_time( 'mysql' ), '', 'yes' );
		}
	}

	private static function ensure_hmac_secret(): void {
		if ( ! get_option( 'wpsb_hmac_secret' ) ) {
			add_option( 'wpsb_hmac_secret', wp_generate_password( 64, true, true ), '', 'no' );
		}
	}
}

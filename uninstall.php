<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$table = $wpdb->prefix . 'wpsb_metrics';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

delete_option( 'wpsb_settings' );
delete_option( 'wpsb_version' );
delete_option( 'wpsb_install_date' );
delete_option( 'wpsb_hmac_secret' );
delete_option( 'wpsb_settings_history' );

$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpsb\_rl\_%' OR option_name LIKE '_transient_timeout_wpsb\_rl\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

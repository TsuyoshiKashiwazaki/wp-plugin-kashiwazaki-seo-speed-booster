<?php
/**
 * Plugin Name: Kashiwazaki SEO Speed Booster
 * Plugin URI:  https://contencial.co.jp/
 * Description: Core Web Vitals (LCP / INP / CLS / FCP / TTFB) 改善のための軽量プラグイン。予測プリフェッチ・Speculation Rules・Smart UX スピナー・計測ダッシュボードを提供。
 * Version:     1.0.0
 * Author:      Contencial
 * Author URI:  https://contencial.co.jp/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kashiwazaki-seo-speed-booster
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 *
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

define( 'WPSB_VERSION', '1.0.0' );
define( 'WPSB_PLUGIN_FILE', __FILE__ );
define( 'WPSB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPSB_OPTION_KEY', 'wpsb_settings' );
define( 'WPSB_TEXT_DOMAIN', 'kashiwazaki-seo-speed-booster' );

require_once WPSB_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, [ 'WPSB_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'WPSB_Deactivator', 'deactivate' ] );

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( WPSB_TEXT_DOMAIN, false, dirname( WPSB_PLUGIN_BASENAME ) . '/languages' );
		WPSB_Plugin::get_instance()->boot();
	}
);

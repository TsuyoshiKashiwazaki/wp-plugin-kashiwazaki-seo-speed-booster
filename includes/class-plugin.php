<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

require_once WPSB_PLUGIN_DIR . 'includes/class-activator.php';
require_once WPSB_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once WPSB_PLUGIN_DIR . 'includes/class-settings.php';
require_once WPSB_PLUGIN_DIR . 'includes/class-url-matcher.php';
require_once WPSB_PLUGIN_DIR . 'includes/class-admin.php';
require_once WPSB_PLUGIN_DIR . 'includes/class-frontend.php';
require_once WPSB_PLUGIN_DIR . 'includes/class-image-optimizer.php';
require_once WPSB_PLUGIN_DIR . 'includes/class-metrics.php';
require_once WPSB_PLUGIN_DIR . 'includes/class-rest-api.php';

final class WPSB_Plugin {

	private static ?WPSB_Plugin $instance = null;

	public WPSB_Settings $settings;
	public WPSB_Admin $admin;
	public WPSB_Frontend $frontend;
	public WPSB_Image_Optimizer $image_optimizer;
	public WPSB_Metrics $metrics;
	public WPSB_Rest_Api $rest_api;

	public static function get_instance(): WPSB_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->settings        = new WPSB_Settings();
		$this->admin           = new WPSB_Admin( $this->settings );
		$this->frontend        = new WPSB_Frontend( $this->settings );
		$this->image_optimizer = new WPSB_Image_Optimizer( $this->settings );
		$this->metrics         = new WPSB_Metrics();
		$this->rest_api        = new WPSB_Rest_Api( $this->metrics, $this->settings );
	}

	public function boot(): void {
		do_action( 'wpsb_before_init' );

		$this->settings->register();
		$this->admin->register();
		$this->frontend->register();
		$this->image_optimizer->register();
		$this->rest_api->register();

		add_action( 'wpsb_daily_purge', [ $this->metrics, 'auto_purge' ] );
		if ( ! wp_next_scheduled( 'wpsb_daily_purge' ) ) {
			wp_schedule_event( time(), 'daily', 'wpsb_daily_purge' );
		}

		add_filter( 'plugin_action_links_' . WPSB_PLUGIN_BASENAME, [ $this, 'add_settings_link' ] );

		do_action( 'wpsb_after_init' );
	}

	public function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . WPSB_Admin::MENU_SLUG ) ),
			esc_html__( '設定', 'kashiwazaki-seo-speed-booster' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}

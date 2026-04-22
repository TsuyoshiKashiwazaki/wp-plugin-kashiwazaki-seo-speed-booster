<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

final class WPSB_Frontend {

	private WPSB_Settings $settings;

	public function __construct( WPSB_Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_head', [ $this, 'output_speculation_rules' ], 1 );
	}

	public function enqueue_assets(): void {
		$opts = $this->settings->get_options();
		if ( empty( $opts['enabled'] ) ) {
			return;
		}
		if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
			return;
		}

		$prefetch_on = apply_filters( 'wpsb_prefetch_enabled', ! empty( $opts['prefetch_enabled'] ) );
		$smart_ux_on = ! empty( $opts['smart_ux_enabled'] );

		if ( $prefetch_on || $smart_ux_on ) {
			wp_enqueue_script(
				'wpsb-booster',
				WPSB_PLUGIN_URL . 'assets/js/booster.js',
				[],
				WPSB_VERSION,
				true
			);
			wp_localize_script(
				'wpsb-booster',
				'wpsbConfig',
				$this->build_booster_config( $opts, $prefetch_on )
			);
		}

		if ( $smart_ux_on ) {
			wp_enqueue_style(
				'wpsb-spinner',
				WPSB_PLUGIN_URL . 'assets/css/spinner.css',
				[],
				WPSB_VERSION
			);
		}

		if ( ! empty( $opts['metrics_enabled'] ) ) {
			wp_enqueue_script(
				'wpsb-web-vitals',
				WPSB_PLUGIN_URL . 'assets/js/web-vitals.iife.js',
				[],
				'4.2.4',
				true
			);
			wp_enqueue_script(
				'wpsb-metrics',
				WPSB_PLUGIN_URL . 'assets/js/metrics.js',
				[ 'wpsb-web-vitals' ],
				WPSB_VERSION,
				true
			);
			wp_add_inline_script(
				'wpsb-metrics',
				'var wpsbMetricsConfig=' . wp_json_encode( $this->build_metrics_config() ) . ';',
				'before'
			);
		}
	}

	private function build_booster_config( array $opts, bool $prefetch_on = false ): array {
		return [
			'prefetch' => [
				'enabled'      => $prefetch_on,
				'viewport'     => (bool) ( $opts['prefetch_viewport'] ?? 0 ),
				'hover'        => (bool) ( $opts['prefetch_hover'] ?? 0 ),
				'touch'        => (bool) ( $opts['prefetch_touch'] ?? 0 ),
				'hoverDelayMs' => (int) ( $opts['prefetch_hover_delay_ms'] ?? 200 ),
			],
			'speculation' => [
				'enabled'   => (bool) ( $opts['speculation_enabled'] ?? 0 ),
				'prerender' => (bool) ( $opts['speculation_prerender'] ?? 0 ),
				'prefetch'  => (bool) ( $opts['speculation_prefetch'] ?? 0 ),
				'eagerness' => $opts['speculation_eagerness'] ?? 'moderate',
			],
			'smartUx' => [
				'enabled'     => (bool) ( $opts['smart_ux_enabled'] ?? 0 ),
				'thresholdMs' => (int) ( $opts['smart_ux_threshold_ms'] ?? 200 ),
				'logoUrl'     => $opts['smart_ux_logo_url'] ?? '',
				'bgColor'     => $opts['smart_ux_bg_color'] ?? '#ffffff',
				'bgOpacity'   => (float) ( $opts['smart_ux_bg_opacity'] ?? 0.8 ),
			],
			'origin'            => self::get_origin(),
			'speculationActive' => $this->will_output_speculation_rules( $opts ),
			'excludePatterns'   => $this->get_exclude_patterns( $opts ),
			'includePatterns'   => $this->get_include_patterns( $opts ),
		];
	}

	private function build_metrics_config(): array {
		$secret = get_option( 'wpsb_hmac_secret', '' );
		if ( ! $secret ) {
			$secret = wp_generate_password( 64, true, true );
			if ( ! add_option( 'wpsb_hmac_secret', $secret, '', 'no' ) ) {
				$secret = (string) get_option( 'wpsb_hmac_secret' );
			}
		}
		$bucket = intdiv( time(), DAY_IN_SECONDS );
		$token  = hash_hmac( 'sha256', (string) $bucket, $secret );

		$opts = $this->settings->get_options();
		$cookie_days = (int) apply_filters( 'wpsb_visitor_cookie_days', (int) ( $opts['metrics_cookie_days'] ?? 30 ) );

		return [
			'endpoint'       => rest_url( 'wpsb/v1/metrics' ),
			'token'          => $token,
			'sampleRate'     => max( 0.01, min( 1.0, (float) ( $opts['metrics_sample_rate'] ?? 0.1 ) ) ),
			'cookieDays'     => max( 0, $cookie_days ),
		];
	}

	private function will_output_speculation_rules( array $opts ): bool {
		if ( empty( $opts['enabled'] ) || empty( $opts['speculation_enabled'] ) ) {
			return false;
		}
		if ( empty( $opts['speculation_prerender'] ) && empty( $opts['speculation_prefetch'] ) ) {
			return false;
		}
		$exclude_patterns = $this->get_exclude_patterns( $opts );
		if ( ! empty( array_filter( $exclude_patterns, [ 'WPSB_URL_Matcher', 'is_regex' ] ) ) ) {
			return false;
		}
		$include_patterns = $this->get_include_patterns( $opts );
		if ( ! empty( $include_patterns ) ) {
			$include_globs = array_filter( $include_patterns, fn( $p ) => ! WPSB_URL_Matcher::is_regex( $p ) );
			if ( empty( $include_globs ) ) {
				return false;
			}
		}
		return true;
	}

	public function output_speculation_rules(): void {
		$opts = $this->settings->get_options();
		if ( ! $this->will_output_speculation_rules( $opts ) ) {
			return;
		}

		$exclude_patterns = $this->get_exclude_patterns( $opts );
		$exclude_globs    = array_values( array_filter( $exclude_patterns, fn( $p ) => ! WPSB_URL_Matcher::is_regex( $p ) ) );

		$include_patterns = $this->get_include_patterns( $opts );
		$include_globs    = array_values( array_filter( $include_patterns, fn( $p ) => ! WPSB_URL_Matcher::is_regex( $p ) ) );

		$valid_eagerness = [ 'immediate', 'eager', 'moderate', 'conservative' ];
		$eagerness       = $opts['speculation_eagerness'] ?? 'moderate';
		if ( ! in_array( $eagerness, $valid_eagerness, true ) ) {
			$eagerness = 'moderate';
		}
		$base_where = ! empty( $include_globs )
			? [ 'href_matches' => $include_globs ]
			: [ 'href_matches' => '/*' ];

		$rules = [];
		if ( ! empty( $opts['speculation_prerender'] ) ) {
			$rules['prerender'] = [ [ 'where' => $base_where, 'eagerness' => $eagerness ] ];
		}
		if ( ! empty( $opts['speculation_prefetch'] ) ) {
			$rules['prefetch'] = [ [ 'where' => $base_where, 'eagerness' => $eagerness ] ];
		}

		$all_excludes = array_values( array_unique( array_merge(
			[
				'/wp-admin/*', '/wp-login.php', '/wp-json/*', '/xmlrpc.php',
				'/feed/*', '/*/feed/*', '*/cart/*', '*/checkout/*', '*/my-account/*',
				'/*?*add-to-cart*', '/*?*remove_item*', '/*?*action=logout*',
				'/*?*action=delete*', '/*?*_wpnonce*', '/*?*preview=*',
				'/*?*s=*', '/*?*replytocom=*', '/*?*orderby=*', '/*?*filter_*',
			],
			$exclude_globs
		) ) );

		if ( ! empty( $rules ) ) {
			foreach ( $rules as $type => $entries ) {
				foreach ( $entries as $i => $entry ) {
					$rules[ $type ][ $i ]['where'] = [
						'and' => [
							$entry['where'],
							[ 'not' => [ 'href_matches' => $all_excludes ] ],
						],
					];
				}
			}
		}

		if ( empty( $rules ) ) {
			return;
		}

		$json = wp_json_encode( $rules, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return;
		}
		echo '<script type="speculationrules">' . $json . '</script>' . "\n";
	}

	private function get_exclude_patterns( array $opts ): array {
		if ( empty( $opts['exclude_enabled'] ) ) {
			return [];
		}
		return apply_filters( 'wpsb_exclude_patterns', WPSB_URL_Matcher::parse_patterns( (string) ( $opts['exclude_patterns'] ?? '' ) ) );
	}

	private function get_include_patterns( array $opts ): array {
		if ( empty( $opts['exclude_enabled'] ) ) {
			return [];
		}
		return apply_filters( 'wpsb_include_patterns', WPSB_URL_Matcher::parse_patterns( (string) ( $opts['include_patterns'] ?? '' ) ) );
	}

	private static function get_origin(): string {
		$parsed = wp_parse_url( home_url() );
		$origin = ( $parsed['scheme'] ?? 'https' ) . '://' . ( $parsed['host'] ?? '' );
		if ( ! empty( $parsed['port'] ) ) {
			$origin .= ':' . $parsed['port'];
		}
		return $origin;
	}
}

<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

final class WPSB_Rest_Api {

	public const NAMESPACE = 'wpsb/v1';

	private WPSB_Metrics $metrics;
	private WPSB_Settings $settings;

	public function __construct( WPSB_Metrics $metrics, WPSB_Settings $settings ) {
		$this->metrics  = $metrics;
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/metrics',
			[
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [ $this, 'handle_metrics_post' ],
			]
		);
		register_rest_route(
			self::NAMESPACE,
			'/purge',
			[
				'methods'             => 'POST',
				'permission_callback' => [ $this, 'permission_manage' ],
				'callback'            => [ $this, 'handle_purge_post' ],
			]
		);
	}

	public function permission_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public function handle_metrics_post( WP_REST_Request $request ) {
		$opts = $this->settings->get_options();
		if ( empty( $opts['enabled'] ) || empty( $opts['metrics_enabled'] ) ) {
			return new WP_REST_Response( [ 'error' => 'disabled' ], 400 );
		}

		if ( ! $this->check_same_origin( $request ) ) {
			return new WP_REST_Response( [ 'error' => 'invalid_origin' ], 403 );
		}

		$raw_len = strlen( (string) $request->get_body() );
		if ( $raw_len > 4096 ) {
			return new WP_REST_Response( [ 'error' => 'payload_too_large' ], 413 );
		}
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return new WP_REST_Response( [ 'error' => 'invalid_body' ], 422 );
		}

		$token = $body['token'] ?? $request->get_header( 'x-wpsb-token' ) ?? '';
		if ( ! $token || ! $this->verify_token( (string) $token ) ) {
			return new WP_REST_Response( [ 'error' => 'invalid_token' ], 403 );
		}

		$metrics_list = $body['metrics'] ?? [];
		if ( ! is_array( $metrics_list ) || count( $metrics_list ) === 0 || count( $metrics_list ) > 10 ) {
			return new WP_REST_Response( [ 'error' => 'invalid_metrics' ], 422 );
		}

		$first_hash = '';
		if ( ! empty( $metrics_list[0]['hash'] ) ) {
			$first_hash = substr( preg_replace( '/[^a-f0-9]/', '', (string) $metrics_list[0]['hash'] ), 0, 32 );
		}
		if ( ! $this->check_rate_limit( $first_hash ) ) {
			return new WP_REST_Response( [ 'error' => 'rate_limited' ], 429 );
		}

		$inserted = 0;
		foreach ( $metrics_list as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$name  = isset( $entry['name'] ) ? (string) $entry['name'] : '';
			$value = $entry['value'] ?? null;
			if ( ! $this->metrics->is_valid_metric( $name, $value ) ) {
				continue;
			}

			$hash = isset( $entry['hash'] ) ? substr( preg_replace( '/[^a-f0-9]/', '', (string) $entry['hash'] ), 0, 32 ) : '';
			if ( strlen( $hash ) !== 32 ) {
				continue;
			}

			$url_path    = isset( $entry['url'] ) ? substr( (string) $entry['url'], 0, 255 ) : '';
			$device_type = in_array( $entry['device'] ?? '', [ 'mobile', 'desktop', 'tablet' ], true ) ? $entry['device'] : 'desktop';

			if ( $this->metrics->insert(
				[
					'metric_name'    => $name,
					'metric_value'   => (float) $value,
					'url_path'       => $url_path,
					'device_type'    => $device_type,
					'anonymous_hash' => $hash,
				]
			) ) {
				$inserted++;
			}
		}

		return new WP_REST_Response( [ 'ok' => true, 'inserted' => $inserted ], 200 );
	}

	public function handle_purge_post( WP_REST_Request $request ) {
		$days    = max( 1, (int) ( $request->get_param( 'days' ) ?? 90 ) );
		$deleted = $this->metrics->purge_older_than( $days );
		return new WP_REST_Response( [ 'ok' => true, 'deleted' => $deleted ], 200 );
	}

	private function check_same_origin( WP_REST_Request $request ): bool {
		$origin  = $request->get_header( 'origin' );
		$referer = $request->get_header( 'referer' );
		$home    = home_url();
		$parts   = wp_parse_url( $home );
		if ( empty( $parts['host'] ) ) {
			return false;
		}
		$site_scheme = $parts['scheme'] ?? 'https';
		$site_host   = $parts['host'];
		$site_port   = $parts['port'] ?? ( $site_scheme === 'https' ? 443 : 80 );

		$check = function ( $url ) use ( $site_scheme, $site_host, $site_port ) {
			if ( ! $url ) {
				return false;
			}
			$p = wp_parse_url( $url );
			if ( empty( $p['host'] ) ) {
				return false;
			}
			$scheme = $p['scheme'] ?? 'https';
			$port   = $p['port'] ?? ( $scheme === 'https' ? 443 : 80 );
			return $p['host'] === $site_host && $scheme === $site_scheme && $port === $site_port;
		};

		$origin_ok  = $origin !== null && $check( $origin );
		$referer_ok = $referer !== null && $check( $referer );

		if ( $origin === null && $referer === null ) {
			return false;
		}
		return $origin_ok || $referer_ok;
	}

	private function verify_token( string $token ): bool {
		$secret  = $this->get_hmac_secret();
		$bucket  = intdiv( time(), DAY_IN_SECONDS );
		$current = hash_hmac( 'sha256', (string) $bucket, $secret );
		$prev    = hash_hmac( 'sha256', (string) ( $bucket - 1 ), $secret );
		return hash_equals( $current, $token ) || hash_equals( $prev, $token );
	}

	private function get_hmac_secret(): string {
		$secret = get_option( 'wpsb_hmac_secret' );
		if ( $secret ) {
			return (string) $secret;
		}
		$new_secret = wp_generate_password( 64, true, true );
		$added      = add_option( 'wpsb_hmac_secret', $new_secret, '', 'no' );
		if ( ! $added ) {
			return (string) get_option( 'wpsb_hmac_secret' );
		}
		return $new_secret;
	}

	private function check_rate_limit( string $visitor_hash = '' ): bool {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key_input = $visitor_hash !== '' ? $ip . '|' . $visitor_hash : $ip;
		$key = 'wpsb_rl_' . substr( md5( $key_input ), 0, 16 );
		$data = get_transient( $key );
		$now  = time();

		if ( ! is_array( $data ) || ! isset( $data['c'], $data['t'] ) || ! is_int( $data['c'] ) || ( $now - $data['t'] ) >= 90 ) {
			set_transient( $key, [ 'c' => 1, 't' => $now ], 90 );
			return true;
		}
		if ( $data['c'] >= 20 ) {
			return false;
		}
		$remaining = max( 1, min( 90, 90 - ( $now - $data['t'] ) ) );
		$data['c']++;
		set_transient( $key, $data, $remaining );
		return true;
	}
}

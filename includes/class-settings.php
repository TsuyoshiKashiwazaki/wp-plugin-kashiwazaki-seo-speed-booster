<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

final class WPSB_Settings {

	public const GROUP = 'wpsb_settings_group';

	private const TAB_FIELDS = [
		'general'     => [
			'enabled', 'prefetch_enabled', 'speculation_enabled',
			'smart_ux_enabled', 'image_enabled', 'metrics_enabled',
			'exclude_enabled',
		],
		'prefetch'    => [
			'prefetch_enabled', 'prefetch_viewport', 'prefetch_hover',
			'prefetch_touch', 'prefetch_hover_delay_ms',
		],
		'speculation' => [
			'speculation_enabled', 'speculation_prerender',
			'speculation_prefetch', 'speculation_eagerness',
		],
		'smart_ux'    => [
			'smart_ux_enabled', 'smart_ux_threshold_ms',
			'smart_ux_logo_url', 'smart_ux_bg_color', 'smart_ux_bg_opacity',
		],
		'images'      => [
			'image_enabled', 'image_lazy', 'image_decoding_async',
			'image_lcp_fetchpriority',
		],
		'exclusions'  => [
			'exclude_enabled', 'exclude_patterns', 'include_patterns',
		],
		'metrics'     => [
			'metrics_sample_rate', 'metrics_cookie_days', 'metrics_retention_days',
		],
	];

	private const CHECKBOX_FIELDS = [
		'enabled', 'prefetch_enabled', 'prefetch_viewport', 'prefetch_hover',
		'prefetch_touch', 'speculation_enabled', 'speculation_prerender',
		'speculation_prefetch', 'smart_ux_enabled', 'image_enabled',
		'image_lazy', 'image_decoding_async', 'image_lcp_fetchpriority',
		'metrics_enabled', 'exclude_enabled',
	];

	private const EAGERNESS_VALUES = [ 'immediate', 'eager', 'moderate', 'conservative' ];

	public function register(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_settings(): void {
		register_setting(
			self::GROUP,
			WPSB_OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => self::defaults(),
			]
		);
	}

	private ?array $cached = null;

	public function get_options(): array {
		return $this->cached ??= wp_parse_args( (array) get_option( WPSB_OPTION_KEY, [] ), self::defaults() );
	}

	public function flush_cache(): void {
		$this->cached = null;
	}

	public static function defaults(): array {
		return [
			'enabled'                 => 0,
			'prefetch_enabled'        => 0,
			'prefetch_viewport'       => 0,
			'prefetch_hover'          => 0,
			'prefetch_touch'          => 0,
			'prefetch_hover_delay_ms' => 200,
			'speculation_enabled'     => 0,
			'speculation_prerender'   => 0,
			'speculation_prefetch'    => 0,
			'speculation_eagerness'   => 'moderate',
			'smart_ux_enabled'        => 0,
			'smart_ux_threshold_ms'   => 200,
			'smart_ux_logo_url'       => '',
			'smart_ux_bg_color'       => '#ffffff',
			'smart_ux_bg_opacity'     => 0.8,
			'image_enabled'           => 0,
			'image_lazy'              => 0,
			'image_decoding_async'    => 0,
			'image_lcp_fetchpriority' => 0,
			'metrics_enabled'         => 0,
			'metrics_sample_rate'     => 0.1,
			'metrics_cookie_days'     => 30,
			'metrics_retention_days'  => 90,
			'exclude_enabled'         => 1,
			'exclude_patterns'        => '',
			'include_patterns'        => '',
		];
	}

	/**
	 * F1:  Merge with existing options so other tabs' fields are preserved.
	 * F4:  sanitize_pattern_list handles non-string input.
	 * F12: wp_strip_all_tags instead of sanitize_text_field for patterns.
	 * C3:  speculation_eagerness handled via null-safe access.
	 * C4:  sanitize_hex_color('') fallback uses ?: not ??.
	 * C5:  record_history only fires when values actually changed.
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return self::defaults();
		}

		$existing = $this->get_options();
		$tab      = isset( $input['_tab'] ) ? sanitize_key( $input['_tab'] ) : '';

		if ( $tab !== '' && isset( self::TAB_FIELDS[ $tab ] ) ) {
			$clean      = $existing;
			$tab_fields = self::TAB_FIELDS[ $tab ];
			foreach ( $tab_fields as $field ) {
				$clean[ $field ] = $this->sanitize_field( $field, $input );
			}
		} else {
			$clean = [];
			foreach ( array_keys( self::defaults() ) as $field ) {
				$clean[ $field ] = $this->sanitize_field( $field, $input );
			}
		}

		unset( $clean['_tab'] );

		$this->maybe_record_history( $existing, $clean );
		$this->flush_cache();

		return $clean;
	}

	private function sanitize_field( string $field, array $input ) {
		$defaults = self::defaults();

		if ( in_array( $field, self::CHECKBOX_FIELDS, true ) ) {
			return ! empty( $input[ $field ] ) ? 1 : 0;
		}

		if ( $field === 'prefetch_hover_delay_ms' ) {
			return isset( $input[ $field ] ) ? max( 0, min( 2000, (int) $input[ $field ] ) ) : $defaults[ $field ];
		}
		if ( $field === 'smart_ux_threshold_ms' ) {
			return isset( $input[ $field ] ) ? max( 0, min( 5000, (int) $input[ $field ] ) ) : $defaults[ $field ];
		}
		if ( $field === 'smart_ux_bg_opacity' ) {
			return isset( $input[ $field ] ) ? max( 0.0, min( 1.0, (float) $input[ $field ] ) ) : $defaults[ $field ];
		}

		if ( $field === 'speculation_eagerness' ) {
			$val = $input[ $field ] ?? 'moderate';
			return in_array( $val, self::EAGERNESS_VALUES, true ) ? $val : 'moderate';
		}

		if ( $field === 'smart_ux_bg_color' ) {
			$val       = isset( $input[ $field ] ) ? (string) $input[ $field ] : '';
			$sanitized = sanitize_hex_color( $val );
			return $sanitized ? $sanitized : '#ffffff';
		}

		if ( $field === 'smart_ux_logo_url' ) {
			return isset( $input[ $field ] ) ? esc_url_raw( (string) $input[ $field ] ) : '';
		}

		if ( $field === 'metrics_sample_rate' ) {
			return isset( $input[ $field ] ) ? max( 0.01, min( 1.0, (float) $input[ $field ] ) ) : $defaults[ $field ];
		}
		if ( $field === 'metrics_cookie_days' ) {
			return isset( $input[ $field ] ) ? max( 0, min( 365, (int) $input[ $field ] ) ) : $defaults[ $field ];
		}
		if ( $field === 'metrics_retention_days' ) {
			return isset( $input[ $field ] ) ? max( 7, min( 730, (int) $input[ $field ] ) ) : $defaults[ $field ];
		}

		if ( $field === 'exclude_patterns' || $field === 'include_patterns' ) {
			return $this->sanitize_pattern_list( $input[ $field ] ?? '' );
		}

		return $defaults[ $field ] ?? '';
	}

	private function sanitize_pattern_list( $raw ): string {
		if ( ! is_string( $raw ) ) {
			return '';
		}
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$clean = [];
		foreach ( $lines as $line ) {
			$line = wp_strip_all_tags( trim( $line ) );
			if ( $line === '' ) {
				continue;
			}
			if ( WPSB_URL_Matcher::is_regex( $line ) && @preg_match( $line, '' ) === false ) {
				add_settings_error(
					WPSB_OPTION_KEY,
					'invalid_regex',
					sprintf(
						/* translators: %s: invalid regex pattern */
						__( '無効な正規表現パターンを除去しました: %s', 'kashiwazaki-seo-speed-booster' ),
						$line
					),
					'error'
				);
				continue;
			}
			$clean[] = $line;
		}
		return implode( "\n", $clean );
	}

	private function maybe_record_history( array $old, array $new ): void {
		$changed = [];
		foreach ( $new as $key => $val ) {
			if ( ! array_key_exists( $key, $old ) || $old[ $key ] !== $val ) {
				$changed[ $key ] = [ 'old' => $old[ $key ] ?? null, 'new' => $val ];
			}
		}
		if ( empty( $changed ) ) {
			return;
		}
		$history = get_option( 'wpsb_settings_history', [] );
		if ( ! is_array( $history ) ) {
			$history = [];
		}
		$history[] = [
			'time'    => current_time( 'mysql' ),
			'user'    => get_current_user_id(),
			'changes' => $changed,
		];
		if ( count( $history ) > 100 ) {
			$history = array_slice( $history, -100 );
		}
		update_option( 'wpsb_settings_history', $history, false );
	}
}

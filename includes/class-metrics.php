<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

final class WPSB_Metrics {

	public const ALLOWED_METRICS = [ 'LCP', 'INP', 'CLS', 'FCP', 'TTFB' ];

	public const VALUE_LIMITS = [
		'LCP'  => 60000,
		'INP'  => 60000,
		'CLS'  => 50,
		'FCP'  => 60000,
		'TTFB' => 60000,
	];

	public function insert( array $data ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'wpsb_metrics';
		$row   = [
			'metric_name'    => (string) $data['metric_name'],
			'metric_value'   => (float) $data['metric_value'],
			'url_path'       => $this->normalize_path( (string) $data['url_path'] ),
			'device_type'    => (string) $data['device_type'],
			'anonymous_hash' => (string) $data['anonymous_hash'],
			'created_at'     => current_time( 'mysql', true ),
		];

		/**
		 * 記録前のデータ加工フック。
		 */
		$row = apply_filters( 'wpsb_metrics_data', $row );

		$result = $wpdb->insert(
			$table,
			$row,
			[ '%s', '%f', '%s', '%s', '%s', '%s' ]
		);

		return $result !== false;
	}

	public function normalize_path( string $path ): string {
		$parsed_path = parse_url( $path, PHP_URL_PATH );
		$path = is_string( $parsed_path ) ? $parsed_path : '/';
		$path = trim( $path );

		/**
		 * url_path 正規化のカスタマイズフック (フィルタで全上書き可)。
		 */
		$filtered = apply_filters( 'wpsb_normalize_path', null, $path );
		if ( is_string( $filtered ) ) {
			return substr( $filtered, 0, 255 );
		}

		// デフォルト正規化
		// /author/{name}/ → /author/:author/
		$path = preg_replace( '#^(/author/)[^/]+(/?.*)$#', '$1:author$2', $path );
		// /members/{id}/ or /users/{id}/ → /{prefix}/:user/
		$path = preg_replace( '#^(/(?:members|users)/)[^/]+(/?.*)$#', '$1:user$2', $path );
		// 3 桁以上連続数字を :id に置換
		$path = preg_replace( '/\d{3,}/', ':id', $path );

		return substr( $path, 0, 255 );
	}

	// https://web.dev/articles/lcp, /inp, /cls, /fcp, /ttfb
	public const THRESHOLDS = [
		'LCP'  => [ 'good' => 2500, 'poor' => 4000 ],
		'FCP'  => [ 'good' => 1800, 'poor' => 3000 ],
		'INP'  => [ 'good' => 200,  'poor' => 500 ],
		'CLS'  => [ 'good' => 0.1,  'poor' => 0.25 ],
		'TTFB' => [ 'good' => 800,  'poor' => 1800 ],
	];

	public const PERIOD_MAP = [
		'1d'   => [ 'days' => 1,   'granularity' => 'hourly' ],
		'3d'   => [ 'days' => 3,   'granularity' => 'daily' ],
		'7d'   => [ 'days' => 7,   'granularity' => 'daily' ],
		'30d'  => [ 'days' => 30,  'granularity' => 'daily' ],
		'90d'  => [ 'days' => 90,  'granularity' => 'monthly' ],
		'180d' => [ 'days' => 180, 'granularity' => 'monthly' ],
		'365d' => [ 'days' => 365, 'granularity' => 'monthly' ],
	];

	public static function parse_period( string $period, string $custom_from = '', string $custom_to = '' ): array {
		$tz  = wp_timezone();
		$now = new \DateTimeImmutable( 'now', $tz );
		$utc = new \DateTimeZone( 'UTC' );

		if ( $period === 'custom' && $custom_from !== '' && $custom_to !== '' ) {
			$from_dt = \DateTimeImmutable::createFromFormat( '!Y-m-d', $custom_from, $tz );
			$to_dt   = \DateTimeImmutable::createFromFormat( '!Y-m-d', $custom_to, $tz );
			if ( ! $from_dt || ! $to_dt
				|| $from_dt->format( 'Y-m-d' ) !== $custom_from
				|| $to_dt->format( 'Y-m-d' ) !== $custom_to
			) {
				return self::parse_period( '1d' );
			}
			$from = $from_dt;
			$to   = $to_dt;

			if ( $from > $to ) {
				[ $from, $to ] = [ $to, $from ];
			}

			$from = $from->setTime( 0, 0, 0 );
			$to   = $to->setTime( 23, 59, 59 );
			$days = max( 1, (int) $from->diff( $to )->days + 1 );

			if ( $days > 730 ) {
				$to   = $from->modify( '+729 days' )->setTime( 23, 59, 59 );
				$days = 730;
			}

			$granularity = match ( true ) {
				$days <= 1  => 'hourly',
				$days <= 30 => 'daily',
				default     => 'monthly',
			};

			return [
				'period'      => 'custom',
				'cutoff_utc'  => $from->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
				'end_utc'     => $to->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
				'days'        => $days,
				'granularity' => $granularity,
				'from_date'   => $from->format( 'Y-m-d' ),
				'to_date'     => $to->format( 'Y-m-d' ),
				'range_start' => $from,
				'range_end'   => $to,
			];
		}

		$map      = self::PERIOD_MAP[ $period ] ?? self::PERIOD_MAP['1d'];
		$resolved = isset( self::PERIOD_MAP[ $period ] ) ? $period : '1d';
		$cutoff   = $now->modify( "-{$map['days']} day" );

		return [
			'period'      => $resolved,
			'cutoff_utc'  => $cutoff->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
			'end_utc'     => null,
			'days'        => $map['days'],
			'granularity' => $map['granularity'],
			'from_date'   => null,
			'to_date'     => null,
			'range_start' => $cutoff,
			'range_end'   => $now,
		];
	}

	public static function classify( string $metric, ?float $value ): string {
		if ( null === $value || $value < 0 ) {
			return 'unknown';
		}
		$t = self::THRESHOLDS[ $metric ] ?? null;
		if ( ! $t ) {
			return 'unknown';
		}
		if ( $value <= $t['good'] ) {
			return 'good';
		}
		if ( $value <= $t['poor'] ) {
			return 'needs-improvement';
		}
		return 'poor';
	}

	public function is_valid_metric( string $name, $value ): bool {
		if ( ! in_array( $name, self::ALLOWED_METRICS, true ) ) {
			return false;
		}
		if ( ! is_numeric( $value ) ) {
			return false;
		}
		$value = (float) $value;
		if ( $value < 0 ) {
			return false;
		}
		$max = self::VALUE_LIMITS[ $name ] ?? PHP_INT_MAX;
		return $value <= $max;
	}

	public function auto_purge(): void {
		$opts = get_option( WPSB_OPTION_KEY, [] );
		$days = max( 7, (int) ( $opts['metrics_retention_days'] ?? 90 ) );
		$this->purge_older_than( $days );
	}

	public function purge_older_than( int $days ): int {
		global $wpdb;
		if ( $days <= 0 ) {
			return 0;
		}
		$table  = $wpdb->prefix . 'wpsb_metrics';
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

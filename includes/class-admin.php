<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

final class WPSB_Admin {

	public const MENU_SLUG = 'wpsb-settings';

	private WPSB_Settings $settings;

	public function __construct( WPSB_Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_wpsb_export_csv', [ $this, 'handle_export_csv' ] );
		add_action( 'admin_post_wpsb_import_csv', [ $this, 'handle_import_csv' ] );
		add_action( 'admin_post_wpsb_purge', [ $this, 'handle_purge' ] );
		add_action( 'admin_post_wpsb_truncate', [ $this, 'handle_truncate' ] );
	}

	public function handle_export_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '権限がありません。', 'kashiwazaki-seo-speed-booster' ), '', [ 'response' => 403 ] );
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wpsb_export_csv' ) ) {
			wp_die( esc_html__( 'nonce 検証に失敗しました。', 'kashiwazaki-seo-speed-booster' ), '', [ 'response' => 403 ] );
		}

		$period_raw  = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : '1d';
		$custom_from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$custom_to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		$parsed      = WPSB_Metrics::parse_period( $period_raw, $custom_from, $custom_to );
		$cutoff      = $parsed['cutoff_utc'];

		$fname = 'wpsb-metrics-' . $parsed['period'];
		if ( $parsed['period'] === 'custom' && $parsed['from_date'] ) {
			$fname .= '-' . $parsed['from_date'] . '_' . $parsed['to_date'];
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wpsb_metrics';

		$where_end = '';
		if ( $parsed['end_utc'] !== null ) {
			$where_end = $wpdb->prepare( ' AND created_at <= %s', $parsed['end_utc'] );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $fname . '.csv"' );

		set_time_limit( 0 ); // phpcs:ignore
		echo "\xEF\xBB\xBF"; // UTF-8 BOM
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, [ 'id', 'metric_name', 'metric_value', 'url_path', 'device_type', 'anonymous_hash', 'created_at' ] );

		$batch_size = 5000;
		$last_id    = 0;
		do {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE created_at >= %s{$where_end} AND id > %d ORDER BY id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
				$cutoff,
				$last_id,
				$batch_size
			), ARRAY_A );

			if ( empty( $rows ) ) {
				break;
			}
			foreach ( $rows as $row ) {
				fputcsv( $out, array_map( [ self::class, 'escape_csv_cell' ], $row ) );
				$last_id = (int) $row['id'];
			}
			if ( ob_get_level() > 0 ) {
				ob_flush();
			}
			flush();
		} while ( count( $rows ) === $batch_size );

		fclose( $out );
		exit;
	}

	public static function escape_csv_cell( $value ): string {
		$s = (string) $value;
		if ( $s === '' ) {
			return '';
		}
		// CSV injection 対策: 危険な先頭文字を ' でエスケープ
		if ( in_array( $s[0], [ '=', '+', '-', '@', "\t", "\r", "\n" ], true ) ) {
			return "'" . $s;
		}
		return $s;
	}

	public function handle_import_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '権限がありません。', 'kashiwazaki-seo-speed-booster' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( 'wpsb_import_csv' );

		$redirect_url = add_query_arg(
			[ 'page' => self::MENU_SLUG, 'tab' => 'dashboard' ],
			admin_url( 'admin.php' )
		);

		if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
			set_transient( 'wpsb_import_notice_' . get_current_user_id(), [ 'error' => __( 'ファイルが選択されていません。', 'kashiwazaki-seo-speed-booster' ) ], 60 );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$file = $_FILES['csv_file'];

		if ( $file['size'] > 10 * MB_IN_BYTES ) {
			set_transient( 'wpsb_import_notice_' . get_current_user_id(), [ 'error' => __( 'ファイルサイズが 10MB を超えています。', 'kashiwazaki-seo-speed-booster' ) ], 60 );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$tmp_path = $file['tmp_name'];
		$inserted = 0;
		$skipped  = 0;

		try {
			$fp = fopen( $tmp_path, 'rb' );
			if ( ! $fp ) {
				set_transient( 'wpsb_import_notice_' . get_current_user_id(), [ 'error' => __( 'ファイルを開けませんでした。', 'kashiwazaki-seo-speed-booster' ) ], 60 );
				wp_safe_redirect( $redirect_url );
				exit;
			}

			// BOM 除去
			$bom = fread( $fp, 3 );
			if ( $bom !== "\xEF\xBB\xBF" ) {
				rewind( $fp );
			}

			// ヘッダ行スキップ
			$header = fgetcsv( $fp );
			if ( ! $header ) {
				fclose( $fp );
				set_transient( 'wpsb_import_notice_' . get_current_user_id(), [ 'error' => __( 'CSV のヘッダ行を読み取れませんでした。', 'kashiwazaki-seo-speed-booster' ) ], 60 );
				wp_safe_redirect( $redirect_url );
				exit;
			}

			$metrics  = WPSB_Plugin::get_instance()->metrics;
			$rows_buf = [];
			$line_num = 0;
			$max_rows = 50000;

			while ( ( $row = fgetcsv( $fp ) ) !== false ) {
				++$line_num;
				if ( $line_num > $max_rows ) {
					$skipped += 1;
					continue;
				}

				if ( count( $row ) < 7 ) {
					++$skipped;
					continue;
				}

				// id(0), metric_name(1), metric_value(2), url_path(3), device_type(4), anonymous_hash(5), created_at(6)
				$metric_name  = trim( $row[1] );
				$metric_value = trim( $row[2] );
				$url_path     = trim( $row[3] );
				$device_type  = trim( $row[4] );
				$anon_hash    = trim( $row[5] );
				$created_at   = trim( $row[6] );

				if ( ! $metrics->is_valid_metric( $metric_name, $metric_value ) ) {
					++$skipped;
					continue;
				}
				if ( ! in_array( $device_type, [ 'desktop', 'mobile', 'tablet' ], true ) ) {
					++$skipped;
					continue;
				}
				if ( ! preg_match( '/^[a-f0-9]{32}$/i', $anon_hash ) ) {
					++$skipped;
					continue;
				}
				$dt = \DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $created_at, new \DateTimeZone( 'UTC' ) );
				if ( ! $dt || $dt->format( 'Y-m-d H:i:s' ) !== $created_at ) {
					++$skipped;
					continue;
				}
				if ( $url_path === '' || strlen( $url_path ) > 255 ) {
					++$skipped;
					continue;
				}

				$rows_buf[] = [
					'metric_name'    => $metric_name,
					'metric_value'   => (float) $metric_value,
					'url_path'       => $url_path,
					'device_type'    => $device_type,
					'anonymous_hash' => $anon_hash,
					'created_at'     => $created_at,
				];
			}

			fclose( $fp );

			if ( ! empty( $rows_buf ) ) {
				$result   = $metrics->bulk_insert( $rows_buf );
				$inserted = $result['inserted'];
				$skipped += $result['skipped'];
			}

			if ( $inserted > 0 ) {
				WPSB_Metrics::clear_dashboard_cache();
			}

			$notice = [
				'success' => sprintf(
					/* translators: 1: inserted count, 2: skipped count */
					__( '%1$d 件インポート / %2$d 件スキップ', 'kashiwazaki-seo-speed-booster' ),
					$inserted,
					$skipped
				),
			];
			if ( $line_num > $max_rows ) {
				$notice['warning'] = sprintf(
					__( '行数上限 (%d 行) を超えたため、超過分はスキップされました。', 'kashiwazaki-seo-speed-booster' ),
					$max_rows
				);
			}
			set_transient( 'wpsb_import_notice_' . get_current_user_id(), $notice, 60 );

		} finally {
			if ( file_exists( $tmp_path ) ) {
				@unlink( $tmp_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function handle_truncate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '権限がありません。', 'kashiwazaki-seo-speed-booster' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( 'wpsb_truncate' );

		$confirm = isset( $_POST['confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['confirm'] ) ) : '';
		if ( $confirm !== '1' ) {
			wp_safe_redirect( add_query_arg(
				[ 'page' => self::MENU_SLUG, 'tab' => 'dashboard' ],
				admin_url( 'admin.php' )
			) );
			exit;
		}

		$metrics = WPSB_Plugin::get_instance()->metrics;
		$success = $metrics->truncate_all();

		if ( $success ) {
			WPSB_Metrics::clear_dashboard_cache();
		}

		$notice = $success
			? __( '全データを削除しました。', 'kashiwazaki-seo-speed-booster' )
			: __( 'データの削除に失敗しました。', 'kashiwazaki-seo-speed-booster' );
		set_transient( 'wpsb_truncate_notice_' . get_current_user_id(), [ 'success' => $success, 'message' => $notice ], 60 );

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'tab' => 'dashboard' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public function handle_purge(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '権限がありません。', 'kashiwazaki-seo-speed-booster' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( 'wpsb_purge' );

		$days    = isset( $_POST['days'] ) ? max( 1, (int) wp_unslash( $_POST['days'] ) ) : 90;
		$metrics = WPSB_Plugin::get_instance()->metrics;
		$deleted = $metrics->purge_older_than( $days );

		set_transient( 'wpsb_purge_notice_' . get_current_user_id(), $deleted, 60 );

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'tab' => 'dashboard' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'Kashiwazaki SEO Speed Booster', 'kashiwazaki-seo-speed-booster' ),
			__( 'Kashiwazaki SEO Speed Booster', 'kashiwazaki-seo-speed-booster' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ],
			'dashicons-performance',
			81
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( $hook !== 'toplevel_page_' . self::MENU_SLUG ) {
			return;
		}
		wp_enqueue_style(
			'wpsb-admin',
			WPSB_PLUGIN_URL . 'assets/css/admin.css',
			[],
			WPSB_VERSION
		);

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$admin_deps = [];
		if ( $tab === 'dashboard' ) {
			wp_register_script(
				'wpsb-chartjs',
				WPSB_PLUGIN_URL . 'assets/js/vendor/chart.umd.js',
				[],
				'4.5.1',
				true
			);
			$admin_deps[] = 'wpsb-chartjs';
		}

		wp_enqueue_script(
			'wpsb-admin',
			WPSB_PLUGIN_URL . 'assets/js/admin.js',
			$admin_deps,
			WPSB_VERSION,
			true
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = $this->get_tabs();
		if ( ! isset( $tabs[ $current_tab ] ) ) {
			$current_tab = 'dashboard';
		}
		include WPSB_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	public function get_tabs(): array {
		return [
			'dashboard'   => __( 'CWV ダッシュボード', 'kashiwazaki-seo-speed-booster' ),
			'general'     => __( '一般設定', 'kashiwazaki-seo-speed-booster' ),
			'prefetch'    => __( '予測プリフェッチ', 'kashiwazaki-seo-speed-booster' ),
			'speculation' => __( 'Speculation Rules', 'kashiwazaki-seo-speed-booster' ),
			'smart_ux'    => __( 'Smart UX', 'kashiwazaki-seo-speed-booster' ),
			'images'      => __( '画像最適化', 'kashiwazaki-seo-speed-booster' ),
			'metrics'     => __( '計測設定', 'kashiwazaki-seo-speed-booster' ),
			'exclusions'  => __( '除外 URL', 'kashiwazaki-seo-speed-booster' ),
		];
	}
}

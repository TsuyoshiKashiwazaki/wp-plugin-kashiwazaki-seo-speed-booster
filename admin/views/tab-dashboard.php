<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 *
 * @var array $options
 */

defined( 'ABSPATH' ) || exit;

// --- パージ通知 ---
$purge_notice = get_transient( 'wpsb_purge_notice_' . get_current_user_id() );
if ( false !== $purge_notice ) :
	delete_transient( 'wpsb_purge_notice_' . get_current_user_id() );
	?>
	<div class="notice notice-success is-dismissible">
		<p><?php printf( esc_html__( '%d 件のデータを削除しました。', 'kashiwazaki-seo-speed-booster' ), (int) $purge_notice ); ?></p>
	</div>
<?php endif;

global $wpdb;
$table = $wpdb->prefix . 'wpsb_metrics';

// --- 期間パース ---
$period_raw  = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : '1d'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$custom_from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$custom_to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';     // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$parsed      = WPSB_Metrics::parse_period( $period_raw, $custom_from, $custom_to );
$period      = $parsed['period'];
$cutoff      = $parsed['cutoff_utc'];
$end_utc     = $parsed['end_utc'];
$days        = $parsed['days'];
$granularity = $parsed['granularity'];

$utc_offset   = (int) ( (float) get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );
$metric_order = "FIELD(metric_name, 'LCP', 'INP', 'CLS', 'FCP', 'TTFB')";

// --- WHERE句 (事前 prepare 済み) ---
$where_time = $wpdb->prepare( 'created_at >= %s', $cutoff ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
if ( $end_utc !== null ) {
	$where_time .= $wpdb->prepare( ' AND created_at <= %s', $end_utc );
}

// --- キャッシュ (180d 以上) ---
$use_cache = $days >= 180;
$cache_key = null;
$cached    = false;

if ( $use_cache ) {
	$cache_key = 'wpsb_dash_' . ( $period === 'custom'
		? 'c_' . md5( $parsed['from_date'] . '_' . $parsed['to_date'] )
		: $period );
	$cached = get_transient( $cache_key );
}

if ( false !== $cached && is_array( $cached ) ) {
	$summary_rows = $cached['summary'];
	$time_rows    = $cached['series'];
	$url_ranking  = $cached['urls'];
} else {
	$summary_rows = $wpdb->get_results(
		"SELECT metric_name, COUNT(*) AS samples,
		        AVG(metric_value) AS avg_v,
		        MAX(metric_value) AS max_v,
		        MIN(metric_value) AS min_v
		 FROM {$table}
		 WHERE {$where_time}
		 GROUP BY metric_name
		 ORDER BY {$metric_order}", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		ARRAY_A
	);

	foreach ( $summary_rows as &$row ) {
		$p75_offset = max( 0, (int) ceil( (int) $row['samples'] * 0.75 ) - 1 );
		$p75_val    = $wpdb->get_var( $wpdb->prepare(
			"SELECT metric_value FROM {$table}
			 WHERE {$where_time} AND metric_name = %s
			 ORDER BY metric_value ASC
			 LIMIT 1 OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			$row['metric_name'],
			$p75_offset
		) );
		$row['p75_v'] = $p75_val !== null ? (float) $p75_val : (float) $row['avg_v'];
	}
	unset( $row );

	// --- 時系列バケット SQL ---
	$bucket_sql = match ( $granularity ) {
		'hourly' => $wpdb->prepare(
			"DATE_FORMAT(DATE_ADD(created_at, INTERVAL %d SECOND), '%%Y-%%m-%%d %%H:00:00')",
			$utc_offset
		),
		'monthly' => $wpdb->prepare(
			"DATE_FORMAT(DATE_ADD(created_at, INTERVAL %d SECOND), '%%Y-%%m-01')",
			$utc_offset
		),
		default => $wpdb->prepare(
			"DATE(DATE_ADD(created_at, INTERVAL %d SECOND))",
			$utc_offset
		),
	};

	$time_rows = $wpdb->get_results(
		"SELECT metric_name,
		        {$bucket_sql} AS bucket,
		        AVG(metric_value) AS avg_v,
		        COUNT(*) AS samples
		 FROM {$table}
		 WHERE {$where_time}
		 GROUP BY metric_name, bucket
		 ORDER BY bucket ASC", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		ARRAY_A
	);

	$url_ranking = $wpdb->get_results(
		"SELECT url_path, AVG(metric_value) AS avg_v, COUNT(*) AS samples
		 FROM {$table}
		 WHERE {$where_time} AND metric_name = 'LCP'
		 GROUP BY url_path
		 HAVING samples >= 3
		 ORDER BY avg_v DESC
		 LIMIT 20", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		ARRAY_A
	);

	if ( $use_cache ) {
		set_transient( $cache_key, [
			'summary' => $summary_rows,
			'series'  => $time_rows,
			'urls'    => $url_ranking,
		], HOUR_IN_SECONDS );
	}
}

$install_date     = get_option( 'wpsb_install_date', '' );
$retention_days   = (int) ( $options['metrics_retention_days'] ?? 90 );

// --- 期間 UI 用データ ---
$pill_periods = [
	'1d'  => __( '24h', 'kashiwazaki-seo-speed-booster' ),
	'3d'  => __( '3 日', 'kashiwazaki-seo-speed-booster' ),
	'7d'  => __( '7 日', 'kashiwazaki-seo-speed-booster' ),
	'30d' => __( '30 日', 'kashiwazaki-seo-speed-booster' ),
	'90d' => __( '90 日', 'kashiwazaki-seo-speed-booster' ),
];
$longterm_periods = [
	'180d' => __( '半年', 'kashiwazaki-seo-speed-booster' ),
	'365d' => __( '1 年', 'kashiwazaki-seo-speed-booster' ),
];
$is_longterm = isset( $longterm_periods[ $period ] );
$base_args   = [ 'page' => WPSB_Admin::MENU_SLUG, 'tab' => 'dashboard' ];
?>

<div class="wpsb-dashboard-toolbar">
	<div class="wpsb-period-selector">
		<div class="wpsb-period-pills">
			<?php foreach ( $pill_periods as $p => $lbl ) : ?>
				<a
					href="<?php echo esc_url( add_query_arg( array_merge( $base_args, [ 'period' => $p ] ), admin_url( 'admin.php' ) ) ); ?>"
					class="<?php echo esc_attr( $period === $p ? 'is-active' : '' ); ?>"
				><?php echo esc_html( $lbl ); ?></a>
			<?php endforeach; ?>

			<select class="<?php echo esc_attr( 'wpsb-period-longterm' . ( $is_longterm ? ' is-active' : '' ) ); ?>">
				<option value=""><?php esc_html_e( '長期…', 'kashiwazaki-seo-speed-booster' ); ?></option>
				<?php foreach ( $longterm_periods as $p => $lbl ) :
					$url = add_query_arg( array_merge( $base_args, [ 'period' => $p ] ), admin_url( 'admin.php' ) );
				?>
					<option value="<?php echo esc_url( $url ); ?>"<?php selected( $period, $p ); ?>><?php echo esc_html( $lbl ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="<?php echo esc_attr( 'wpsb-period-custom' . ( $period === 'custom' ? ' is-active' : '' ) ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( WPSB_Admin::MENU_SLUG ); ?>" />
			<input type="hidden" name="tab" value="dashboard" />
			<input type="hidden" name="period" value="custom" />
			<label class="screen-reader-text"><?php esc_html_e( '開始日', 'kashiwazaki-seo-speed-booster' ); ?></label>
			<input type="date" name="from"
				value="<?php echo esc_attr( $parsed['from_date'] ?? '' ); ?>"
				max="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" />
			<span class="wpsb-period-custom__sep"><?php esc_html_e( '〜', 'kashiwazaki-seo-speed-booster' ); ?></span>
			<label class="screen-reader-text"><?php esc_html_e( '終了日', 'kashiwazaki-seo-speed-booster' ); ?></label>
			<input type="date" name="to"
				value="<?php echo esc_attr( $parsed['to_date'] ?? '' ); ?>"
				max="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" />
			<button type="submit" class="button button-small"><?php esc_html_e( '適用', 'kashiwazaki-seo-speed-booster' ); ?></button>
		</form>
	</div>

	<?php if ( $install_date ) : ?>
		<p class="description"><?php printf( esc_html__( '導入日: %s', 'kashiwazaki-seo-speed-booster' ), esc_html( $install_date ) ); ?></p>
	<?php endif; ?>
</div>

<?php if ( $days > $retention_days ) : ?>
	<div class="wpsb-retention-warning">
		<p><?php printf(
			esc_html__( '選択期間（%1$d 日）がデータ保持期間（%2$d 日）を超えています。古いデータは自動パージ済みの可能性があります。保持期間は「計測設定」タブで変更できます。', 'kashiwazaki-seo-speed-booster' ),
			$days,
			$retention_days
		); ?></p>
	</div>
<?php endif; ?>

<?php
if ( empty( $summary_rows ) ) :
	$metrics_on  = ! empty( $options['metrics_enabled'] ) && ! empty( $options['enabled'] );
	$sample_rate = (float) ( $options['metrics_sample_rate'] ?? 0.1 );
	?>
	<div class="wpsb-settings-card">
		<p class="wpsb-empty-state">
			<?php if ( ! $metrics_on ) : ?>
				<?php esc_html_e( 'Core Web Vitals 計測が無効です。「一般設定」タブでプラグインと計測機能を有効にしてください。', 'kashiwazaki-seo-speed-booster' ); ?>
			<?php else : ?>
				<?php
				printf(
					/* translators: 1: sample rate percentage, 2: period label */
					esc_html__( '計測は有効ですが、選択期間（%2$s）内のデータがありません。サンプリングレート: %1$s%%。フロントエンドを数回訪問するか、期間を広げてお試しください。', 'kashiwazaki-seo-speed-booster' ),
					esc_html( number_format( $sample_rate * 100, 0 ) ),
					esc_html( $period )
				);
				?>
			<?php endif; ?>
		</p>
	</div>
<?php else : ?>

	<?php
	$rating_labels = [
		'good'              => __( '合格', 'kashiwazaki-seo-speed-booster' ),
		'needs-improvement' => __( '要改善', 'kashiwazaki-seo-speed-booster' ),
		'poor'              => __( '不合格', 'kashiwazaki-seo-speed-booster' ),
		'insufficient'      => __( 'データ不足', 'kashiwazaki-seo-speed-booster' ),
		'unknown'           => __( '判定不可', 'kashiwazaki-seo-speed-booster' ),
	];
	$metric_fullnames = [
		'LCP'  => 'Largest Contentful Paint',
		'FCP'  => 'First Contentful Paint',
		'INP'  => 'Interaction to Next Paint',
		'CLS'  => 'Cumulative Layout Shift',
		'TTFB' => 'Time to First Byte',
	];
	?>
	<div class="wpsb-stat-cards">
		<?php foreach ( $summary_rows as $row ) : ?>
			<?php
			$metric    = $row['metric_name'];
			$is_cls    = $metric === 'CLS';
			$p75       = (float) $row['p75_v'];
			$avg       = (float) $row['avg_v'];
			$samples   = (int) $row['samples'];
			$fmt_p75   = $is_cls ? number_format( $p75, 3 ) : number_format( $p75, 0 );
			$fmt_avg   = $is_cls ? number_format( $avg, 3 ) : number_format( $avg, 0 );
			$unit      = $is_cls ? '' : ' ms';
			$fullname  = $metric_fullnames[ $metric ] ?? $metric;

			$rating       = $samples >= 10 ? WPSB_Metrics::classify( $metric, $p75 ) : 'insufficient';
			$rating_label = $rating_labels[ $rating ] ?? '';

			$thresholds   = WPSB_Metrics::THRESHOLDS[ $metric ] ?? null;
			$meter_pct    = 0;
			if ( $thresholds ) {
				$scale_max = $thresholds['poor'] * 1.5;
				$meter_pct = min( 100, max( 0, ( $p75 / $scale_max ) * 100 ) );
				$good_pct  = ( $thresholds['good'] / $scale_max ) * 100;
				$poor_pct  = ( $thresholds['poor'] / $scale_max ) * 100;
				$fmt_good  = $is_cls ? number_format( $thresholds['good'], 1 ) : number_format( $thresholds['good'], 0 );
				$fmt_poor  = $is_cls ? number_format( $thresholds['poor'], 2 ) : number_format( $thresholds['poor'], 0 );
			}
			?>
			<div class="wpsb-stat-card wpsb-stat-card--<?php echo esc_attr( $rating ); ?>">
				<div class="wpsb-stat-card__header">
					<span class="wpsb-stat-card__abbr"><?php echo esc_html( $metric ); ?></span>
					<?php if ( $rating_label ) : ?>
						<span class="wpsb-rating-badge wpsb-rating-badge--<?php echo esc_attr( $rating ); ?>"><?php echo esc_html( $rating_label ); ?></span>
					<?php endif; ?>
				</div>
				<div class="wpsb-stat-card__fullname"><?php echo esc_html( $fullname ); ?></div>
				<div class="wpsb-stat-card__value"><?php echo esc_html( $fmt_p75 . $unit ); ?></div>
				<?php if ( $thresholds && $samples >= 10 ) : ?>
					<div class="wpsb-meter">
						<div class="wpsb-meter__bar">
							<div class="wpsb-meter__zone wpsb-meter__zone--good" style="width:<?php echo esc_attr( $good_pct ); ?>%"></div>
							<div class="wpsb-meter__zone wpsb-meter__zone--mid" style="width:<?php echo esc_attr( $poor_pct - $good_pct ); ?>%"></div>
							<div class="wpsb-meter__zone wpsb-meter__zone--poor" style="width:<?php echo esc_attr( 100 - $poor_pct ); ?>%"></div>
						</div>
						<div class="wpsb-meter__marker" style="left:<?php echo esc_attr( $meter_pct ); ?>%">
							<span class="wpsb-meter__needle"></span>
						</div>
						<div class="wpsb-meter__labels">
							<span class="wpsb-meter__label" style="left:0">0</span>
							<span class="wpsb-meter__label" style="left:<?php echo esc_attr( $good_pct ); ?>%"><?php echo esc_html( $fmt_good . ( $is_cls ? '' : ' ms' ) ); ?></span>
							<span class="wpsb-meter__label" style="left:<?php echo esc_attr( $poor_pct ); ?>%"><?php echo esc_html( $fmt_poor . ( $is_cls ? '' : ' ms' ) ); ?></span>
						</div>
					</div>
				<?php endif; ?>
				<div class="wpsb-stat-card__footer">
					<span>p75</span>
					<span><?php printf( esc_html__( '平均: %s', 'kashiwazaki-seo-speed-booster' ), esc_html( $fmt_avg . $unit ) ); ?></span>
					<span><?php printf( esc_html__( 'n=%d', 'kashiwazaki-seo-speed-booster' ), $samples ); ?></span>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<hr class="wpsb-section-divider" />

	<?php
	// --- チャート用データ ---
	$by_metric   = [];
	foreach ( $time_rows as $r ) {
		$by_metric[ $r['metric_name'] ][] = [ 'bucket' => $r['bucket'], 'avg_v' => (float) $r['avg_v'] ];
	}

	$tz          = wp_timezone();
	$range_start = $parsed['range_start'];
	$range_end   = $parsed['range_end'];

	$all_buckets  = [];
	$chart_labels = [];

	switch ( $granularity ) {
		case 'hourly':
			$d = $range_start->setTime( (int) $range_start->format( 'H' ), 0, 0 );
			while ( $d <= $range_end ) {
				$all_buckets[] = $d->format( 'Y-m-d H:00:00' );
				$d = $d->modify( '+1 hour' );
			}
			$chart_labels = array_map( function ( $b ) use ( $days ) {
				return $days <= 1
					? substr( $b, 11, 5 )
					: substr( $b, 5, 2 ) . '/' . substr( $b, 8, 2 ) . ' ' . substr( $b, 11, 5 );
			}, $all_buckets );
			break;

		case 'monthly':
			$d = $range_start->setDate(
				(int) $range_start->format( 'Y' ),
				(int) $range_start->format( 'm' ),
				1
			)->setTime( 0, 0, 0 );
			while ( $d <= $range_end ) {
				$all_buckets[] = $d->format( 'Y-m-d' );
				$d = $d->modify( '+1 month' );
			}
			$chart_labels = array_map( function ( $b ) {
				return substr( $b, 0, 4 ) . '/' . substr( $b, 5, 2 );
			}, $all_buckets );
			break;

		default: // daily
			$d = $range_start->setTime( 0, 0, 0 );
			while ( $d <= $range_end ) {
				$all_buckets[] = $d->format( 'Y-m-d' );
				$d = $d->modify( '+1 day' );
			}
			$chart_labels = array_map( function ( $b ) {
				return substr( $b, 5, 2 ) . '/' . substr( $b, 8, 2 );
			}, $all_buckets );
			break;
	}

	$chart_datasets = [];
	foreach ( $by_metric as $metric => $series ) {
		$bucket_values = [];
		foreach ( $series as $e ) {
			$bucket_values[ $e['bucket'] ] = $e['avg_v'];
		}
		$data_points = [];
		foreach ( $all_buckets as $bucket ) {
			$data_points[] = $bucket_values[ $bucket ] ?? null;
		}
		$chart_datasets[ $metric ] = $data_points;
	}

	wp_add_inline_script(
		'wpsb-admin',
		'var wpsbChartData=' . wp_json_encode( [
			'labels'  => $chart_labels,
			'metrics' => $chart_datasets,
		] ) . ';',
		'before'
	);

	$chart_subtitle = match ( $granularity ) {
		'hourly' => __( '時間別平均', 'kashiwazaki-seo-speed-booster' ),
		'monthly' => __( '月次平均', 'kashiwazaki-seo-speed-booster' ),
		default  => __( '日次平均', 'kashiwazaki-seo-speed-booster' ),
	};
	?>

	<?php foreach ( array_keys( $by_metric ) as $metric ) : ?>
		<div class="wpsb-dashboard-chart">
			<h3><?php echo esc_html( $metric ); ?> <?php echo esc_html( $chart_subtitle ); ?></h3>
			<div class="wpsb-chart-wrap">
				<canvas id="wpsb-chart-<?php echo esc_attr( strtolower( $metric ) ); ?>"></canvas>
			</div>
		</div>
	<?php endforeach; ?>

	<hr class="wpsb-section-divider" />

	<div class="wpsb-settings-card">
		<h4 class="wpsb-settings-card__title"><?php esc_html_e( 'LCP が遅い URL TOP 20', 'kashiwazaki-seo-speed-booster' ); ?></h4>
		<?php if ( ! empty( $url_ranking ) ) : ?>
			<table class="wpsb-url-ranking widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'URL', 'kashiwazaki-seo-speed-booster' ); ?></th>
						<th><?php esc_html_e( '平均 LCP (ms)', 'kashiwazaki-seo-speed-booster' ); ?></th>
						<th><?php esc_html_e( 'サンプル数', 'kashiwazaki-seo-speed-booster' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $url_ranking as $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $row['url_path'] ); ?></code></td>
							<td><?php echo esc_html( number_format( (float) $row['avg_v'], 1 ) ); ?></td>
							<td><?php echo esc_html( (int) $row['samples'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'LCP データが 3 件以上ある URL がまだありません。', 'kashiwazaki-seo-speed-booster' ); ?></p>
		<?php endif; ?>
	</div>

	<div class="wpsb-settings-card">
		<h4 class="wpsb-settings-card__title"><?php esc_html_e( 'CSV エクスポート', 'kashiwazaki-seo-speed-booster' ); ?></h4>
		<p>
			<?php
			$csv_args = [ 'action' => 'wpsb_export_csv', 'period' => $period, '_wpnonce' => wp_create_nonce( 'wpsb_export_csv' ) ];
			if ( $period === 'custom' && $parsed['from_date'] ) {
				$csv_args['from'] = $parsed['from_date'];
				$csv_args['to']   = $parsed['to_date'];
			}
			$csv_url = add_query_arg( $csv_args, admin_url( 'admin-post.php' ) );
			?>
			<a href="<?php echo esc_url( $csv_url ); ?>" class="button"><?php esc_html_e( '現在の期間を CSV でダウンロード', 'kashiwazaki-seo-speed-booster' ); ?></a>
		</p>
	</div>

	<div class="wpsb-settings-card">
		<h4 class="wpsb-settings-card__title"><?php esc_html_e( '手動パージ', 'kashiwazaki-seo-speed-booster' ); ?></h4>
		<p class="wpsb-field__help" style="margin-top:0;"><?php esc_html_e( 'DB 圧迫時に古いデータを削除します。', 'kashiwazaki-seo-speed-booster' ); ?></p>
		<form id="wpsb-purge-form" class="wpsb-purge-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-confirm="<?php echo esc_attr( __( '本当に削除しますか?', 'kashiwazaki-seo-speed-booster' ) ); ?>">
			<?php wp_nonce_field( 'wpsb_purge' ); ?>
			<input type="hidden" name="action" value="wpsb_purge" />
			<label><?php esc_html_e( '保持期間:', 'kashiwazaki-seo-speed-booster' ); ?>
				<select name="days">
					<option value="90">90 <?php esc_html_e( '日', 'kashiwazaki-seo-speed-booster' ); ?></option>
					<option value="180">180 <?php esc_html_e( '日', 'kashiwazaki-seo-speed-booster' ); ?></option>
					<option value="365">365 <?php esc_html_e( '日', 'kashiwazaki-seo-speed-booster' ); ?></option>
				</select>
			</label>
			<button type="submit" class="button button-secondary"><?php esc_html_e( '指定日数より古いデータを削除', 'kashiwazaki-seo-speed-booster' ); ?></button>
		</form>
	</div>
<?php endif; ?>

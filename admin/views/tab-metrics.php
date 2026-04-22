<?php
/**
 * 計測設定タブ。
 *
 * @package KashiwazakiSeoSpeedBooster
 *
 * @var array $options
 */

defined( 'ABSPATH' ) || exit;
?>
<form method="post" action="options.php">
	<?php settings_fields( WPSB_Settings::GROUP ); ?>
	<input type="hidden" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[_tab]' ); ?>" value="metrics" />

	<div class="wpsb-settings-card">
		<h4 class="wpsb-settings-card__title"><?php esc_html_e( 'データ収集', 'kashiwazaki-seo-speed-booster' ); ?></h4>
		<div class="wpsb-field">
			<div class="wpsb-field__label">
				<label for="wpsb-sample-rate"><?php esc_html_e( 'サンプリングレート', 'kashiwazaki-seo-speed-booster' ); ?></label>
			</div>
			<div class="wpsb-field__control">
				<input type="number" id="wpsb-sample-rate" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[metrics_sample_rate]' ); ?>"
					value="<?php echo esc_attr( $options['metrics_sample_rate'] ?? 0.1 ); ?>"
					min="0.01" max="1" step="0.01" style="width:100px;" />
			</div>
			<p class="wpsb-field__help"><?php esc_html_e( '0.01〜1.0（1.0 = 全訪問者を計測、0.1 = 10% をサンプリング）', 'kashiwazaki-seo-speed-booster' ); ?></p>
		</div>
		<div class="wpsb-field">
			<div class="wpsb-field__label">
				<label for="wpsb-cookie-days"><?php esc_html_e( '訪問者クッキー有効期間', 'kashiwazaki-seo-speed-booster' ); ?></label>
			</div>
			<div class="wpsb-field__control" style="display:flex; align-items:center; gap:8px;">
				<input type="number" id="wpsb-cookie-days" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[metrics_cookie_days]' ); ?>"
					value="<?php echo esc_attr( $options['metrics_cookie_days'] ?? 30 ); ?>"
					min="0" max="365" step="1" style="width:100px;" />
				<span><?php esc_html_e( '日', 'kashiwazaki-seo-speed-booster' ); ?></span>
			</div>
			<p class="wpsb-field__help"><?php esc_html_e( '0 = セッションクッキー（ブラウザを閉じるとリセット）。30 以上推奨。', 'kashiwazaki-seo-speed-booster' ); ?></p>
		</div>
	</div>

	<div class="wpsb-settings-card">
		<h4 class="wpsb-settings-card__title"><?php esc_html_e( 'データ保持', 'kashiwazaki-seo-speed-booster' ); ?></h4>
		<div class="wpsb-field">
			<div class="wpsb-field__label">
				<label for="wpsb-retention-days"><?php esc_html_e( '自動パージ保持期間', 'kashiwazaki-seo-speed-booster' ); ?></label>
			</div>
			<div class="wpsb-field__control" style="display:flex; align-items:center; gap:8px;">
				<input type="number" id="wpsb-retention-days" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[metrics_retention_days]' ); ?>"
					value="<?php echo esc_attr( $options['metrics_retention_days'] ?? 90 ); ?>"
					min="7" max="730" step="1" style="width:100px;" />
				<span><?php esc_html_e( '日', 'kashiwazaki-seo-speed-booster' ); ?></span>
			</div>
			<p class="wpsb-field__help"><?php esc_html_e( 'この日数を超えたデータは毎日自動で削除されます。7〜730 日。', 'kashiwazaki-seo-speed-booster' ); ?></p>
		</div>
	</div>

	<?php submit_button( __( '計測設定を保存', 'kashiwazaki-seo-speed-booster' ) ); ?>
</form>

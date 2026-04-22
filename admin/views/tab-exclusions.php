<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 *
 * @var array $options
 */

defined( 'ABSPATH' ) || exit;

$is_off = empty( $options['exclude_enabled'] );
?>
<form method="post" action="options.php">
	<?php settings_fields( WPSB_Settings::GROUP ); ?>
	<input type="hidden" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[_tab]' ); ?>" value="exclusions" />

	<div class="wpsb-feature-header <?php echo $is_off ? 'is-off' : ''; ?>">
		<div class="wpsb-feature-header__body">
			<div>
				<div class="wpsb-feature-header__title-row">
					<h3 class="wpsb-feature-header__title"><?php esc_html_e( 'URL 除外フィルタ', 'kashiwazaki-seo-speed-booster' ); ?></h3>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'プリフェッチと Speculation Rules の両方から除外する URL パターンを管理します。glob（* と ?）または /regex/ 形式で指定でき、カート・ログアウト等の副作用がある URL を保護します。OFF にすると除外パターンとホワイトリストの両方が無効になります。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
					<span class="wpsb-badge wpsb-badge--on"><?php esc_html_e( '稼働中', 'kashiwazaki-seo-speed-booster' ); ?></span>
					<span class="wpsb-badge wpsb-badge--off"><?php esc_html_e( '停止中', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</div>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--on"><?php esc_html_e( '指定したパターンに一致する URL をプリフェッチ・事前レンダリングの対象から除外します。', 'kashiwazaki-seo-speed-booster' ); ?></p>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--off"><?php esc_html_e( 'カスタム除外パターンとホワイトリストは停止中です。WP 管理画面・ログイン・カート等の組み込み除外は常に有効です。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
			<label class="wpsb-toggle">
				<input type="checkbox" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[exclude_enabled]' ); ?>" value="1" <?php checked( 1, (int) ( $options['exclude_enabled'] ?? 1 ) ); ?> />
				<span class="wpsb-toggle__slider"></span>
			</label>
		</div>
	</div>

	<div class="wpsb-settings-panel <?php echo $is_off ? 'is-disabled' : ''; ?>">
		<div class="wpsb-settings-card">
			<h4 class="wpsb-settings-card__title">
				<?php esc_html_e( '除外パターン', 'kashiwazaki-seo-speed-booster' ); ?>
				<span class="wpsb-tooltip">
					<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
					<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'ここに記載した URL パターンはプリフェッチと Speculation Rules の両方から除外されます。glob（* と ?）または /regex/ 形式で指定できます。', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</span>
			</h4>

			<div class="wpsb-field">
				<div class="wpsb-field__control">
					<textarea id="exclude_patterns" class="large-text code" rows="8" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[exclude_patterns]' ); ?>"><?php echo esc_textarea( $options['exclude_patterns'] ?? '' ); ?></textarea>
				</div>
				<p class="wpsb-field__help"><?php esc_html_e( '1 行 1 パターン。glob（* と ?）、または /regex/ 形式。', 'kashiwazaki-seo-speed-booster' ); ?></p>
				<div class="wpsb-info-box wpsb-info-box--warning">
					<strong><?php esc_html_e( 'WooCommerce / EC サイト運用の方へ:', 'kashiwazaki-seo-speed-booster' ); ?></strong>
					<?php esc_html_e( 'カート・チェックアウト・マイアカウント等の URL を必ず追加してください。', 'kashiwazaki-seo-speed-booster' ); ?>
					<br><code>*/cart/*</code>&ensp;<code>*/checkout/*</code>&ensp;<code>*/my-account/*</code>
				</div>
			</div>
		</div>

		<div class="wpsb-settings-card">
			<h4 class="wpsb-settings-card__title">
				<?php esc_html_e( 'ホワイトリスト（任意）', 'kashiwazaki-seo-speed-booster' ); ?>
				<span class="wpsb-tooltip">
					<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
					<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( '設定すると、ここに一致する URL だけがプリフェッチ対象になります（ホワイトリストモード）。通常は空のまま（全 URL が対象）で問題ありません。', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</span>
			</h4>

			<div class="wpsb-field">
				<div class="wpsb-field__control">
					<textarea id="include_patterns" class="large-text code" rows="5" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[include_patterns]' ); ?>"><?php echo esc_textarea( $options['include_patterns'] ?? '' ); ?></textarea>
				</div>
				<p class="wpsb-field__help"><?php esc_html_e( '設定すると、ここに一致する URL のみプリフェッチされます（ホワイトリストモード）。通常は空のままで OK。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
		</div>
	</div>

	<?php submit_button(); ?>
</form>

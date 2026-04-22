<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 *
 * @var array $options
 */

defined( 'ABSPATH' ) || exit;

$is_off = empty( $options['smart_ux_enabled'] );
?>
<form method="post" action="options.php">
	<?php settings_fields( WPSB_Settings::GROUP ); ?>
	<input type="hidden" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[_tab]' ); ?>" value="smart_ux" />

	<div class="wpsb-feature-header <?php echo $is_off ? 'is-off' : ''; ?>">
		<div class="wpsb-feature-header__body">
			<div>
				<div class="wpsb-feature-header__title-row">
					<h3 class="wpsb-feature-header__title"><?php esc_html_e( 'Smart UX（遅延スピナー）', 'kashiwazaki-seo-speed-booster' ); ?></h3>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( '閾値（デフォルト 200ms）を超えてもページ遷移が完了しない場合にスピナーオーバーレイを表示します。CLS を発生させないよう position:fixed で実装し、背景色・不透明度・ロゴを管理画面から変更できます。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
					<span class="wpsb-badge wpsb-badge--on"><?php esc_html_e( '稼働中', 'kashiwazaki-seo-speed-booster' ); ?></span>
					<span class="wpsb-badge wpsb-badge--off"><?php esc_html_e( '停止中', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</div>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--on"><?php esc_html_e( 'ページ遷移が遅い場合にスピナーを表示し、体感待ち時間を短縮します。', 'kashiwazaki-seo-speed-booster' ); ?></p>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--off"><?php esc_html_e( 'Smart UX は停止中です。有効にすると、遷移時にスピナーが表示されます。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
			<label class="wpsb-toggle">
				<input type="checkbox" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[smart_ux_enabled]' ); ?>" value="1" <?php checked( 1, (int) ( $options['smart_ux_enabled'] ?? 0 ) ); ?> />
				<span class="wpsb-toggle__slider"></span>
			</label>
		</div>
	</div>

	<div class="wpsb-settings-panel <?php echo $is_off ? 'is-disabled' : ''; ?>">
		<div class="wpsb-settings-card">
			<h4 class="wpsb-settings-card__title">
				<?php esc_html_e( '表示設定', 'kashiwazaki-seo-speed-booster' ); ?>
				<span class="wpsb-tooltip">
					<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
					<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'スピナーが表示されるまでの待機時間を設定します。短すぎると一瞬の遷移でもスピナーが見えてしまい、長すぎると遅い遷移でもフィードバックが出ません。', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</span>
			</h4>

			<div class="wpsb-field">
				<label class="wpsb-field__label" for="smart_ux_threshold_ms">
					<?php esc_html_e( '表示閾値', 'kashiwazaki-seo-speed-booster' ); ?>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( '短すぎると一瞬の遷移でもスピナーが見えてしまい、長すぎると遅い遷移でもフィードバックが出ません。200ms がバランスの良い値です。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
				</label>
				<div class="wpsb-field__control">
					<input type="number" min="0" max="5000" step="50" id="smart_ux_threshold_ms" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[smart_ux_threshold_ms]' ); ?>" value="<?php echo esc_attr( (int) ( $options['smart_ux_threshold_ms'] ?? 200 ) ); ?>" /> ms
				</div>
				<p class="wpsb-field__help"><?php esc_html_e( '遷移時間がこの値を超えた場合のみスピナーを表示します。デフォルト 200ms。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
		</div>

		<div class="wpsb-settings-card">
			<h4 class="wpsb-settings-card__title">
				<?php esc_html_e( 'デザイン', 'kashiwazaki-seo-speed-booster' ); ?>
				<span class="wpsb-tooltip">
					<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
					<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'スピナーの見た目をサイトのデザインに合わせてカスタマイズできます。ロゴを設定するとスピナーの中央にブランドロゴが表示されます。', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</span>
			</h4>

			<div class="wpsb-field">
				<label class="wpsb-field__label" for="smart_ux_logo_url">
					<?php esc_html_e( 'ロゴ URL（任意）', 'kashiwazaki-seo-speed-booster' ); ?>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'SVG / PNG / WebP 等の画像 URL を指定するとスピナー中央にロゴが表示されます。空欄ならデフォルトの CSS スピナーを使います。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
				</label>
				<div class="wpsb-field__control">
					<input type="url" id="smart_ux_logo_url" class="regular-text" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[smart_ux_logo_url]' ); ?>" value="<?php echo esc_attr( $options['smart_ux_logo_url'] ?? '' ); ?>" placeholder="https://example.com/logo.svg" />
				</div>
				<p class="wpsb-field__help"><?php esc_html_e( '未設定時はデフォルトの SVG スピナーを表示します。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>

			<div class="wpsb-field">
				<label class="wpsb-field__label" for="smart_ux_bg_color">
					<?php esc_html_e( '背景色', 'kashiwazaki-seo-speed-booster' ); ?>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'スピナーオーバーレイの背景色を HEX で指定します。サイトの基調色に合わせるとスムーズな印象になります。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
				</label>
				<div class="wpsb-field__control">
					<input type="text" id="smart_ux_bg_color" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[smart_ux_bg_color]' ); ?>" value="<?php echo esc_attr( $options['smart_ux_bg_color'] ?? '#ffffff' ); ?>" placeholder="#ffffff" />
				</div>
			</div>

			<div class="wpsb-field">
				<label class="wpsb-field__label" for="smart_ux_bg_opacity">
					<?php esc_html_e( '背景の不透明度', 'kashiwazaki-seo-speed-booster' ); ?>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( '0 で完全透明、1 で完全不透明です。0.8 前後が背景を薄く見せつつスピナーに視線を集める推奨値です。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
				</label>
				<div class="wpsb-field__control">
					<input type="number" min="0" max="1" step="0.1" id="smart_ux_bg_opacity" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[smart_ux_bg_opacity]' ); ?>" value="<?php echo esc_attr( $options['smart_ux_bg_opacity'] ?? 0.8 ); ?>" />
				</div>
				<p class="wpsb-field__help"><?php esc_html_e( '0（完全透明）〜 1（不透明）。デフォルト 0.8。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
		</div>
	</div>

	<?php submit_button(); ?>
</form>

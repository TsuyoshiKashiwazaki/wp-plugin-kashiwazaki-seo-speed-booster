<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 *
 * @var array $options
 */

defined( 'ABSPATH' ) || exit;

$is_off = empty( $options['prefetch_enabled'] );

$features = [
	'prefetch_viewport' => [
		'label'   => __( 'ビューポート検知', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( 'IntersectionObserver で画面内に入ったリンクを自動検出して先読みしています。スクロール中に次に見えるリンクが対象になり、一覧ページで特に効果的です。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'ビューポート検知は停止中です。有効にすると、画面に入ったリンクを自動で先読みします。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( 'IntersectionObserver で画面内に入ったリンクを自動検出して先読みします。スクロール中に次に見えるリンクが対象になるため、一覧ページで特に効果的です。', 'kashiwazaki-seo-speed-booster' ),
	],
	'prefetch_hover'    => [
		'label'   => __( 'ホバー検知', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( 'マウスカーソルがリンク上に乗ると待機時間の経過後に先読みを開始します。クリック意図の高いリンクだけを対象にし、無駄なネットワーク消費を抑えます。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'ホバー検知は停止中です。有効にすると、マウスホバー時にリンク先を先読みします。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( 'マウスカーソルがリンク上に乗ってから待機時間（下の設定）経過後に先読みを開始します。クリック意図の高いリンクだけを対象にでき、無駄なネットワーク消費を抑えます。', 'kashiwazaki-seo-speed-booster' ),
	],
	'prefetch_touch'    => [
		'label'   => __( 'タッチ検知', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( 'touchstart イベントでタップ直前のリンクを先読みしています。モバイル端末ではホバーが使えないため、このトリガーがメインになります。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'タッチ検知は停止中です。有効にすると、モバイル端末でタップ直前のリンクを先読みします。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( 'touchstart イベントでタップ直前のリンクを先読みします。モバイル端末ではホバーが使えないため、このトリガーがメインになります。', 'kashiwazaki-seo-speed-booster' ),
	],
];
?>
<form method="post" action="options.php">
	<?php settings_fields( WPSB_Settings::GROUP ); ?>
	<input type="hidden" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[_tab]' ); ?>" value="prefetch" />

	<div class="wpsb-feature-header <?php echo $is_off ? 'is-off' : ''; ?>">
		<div class="wpsb-feature-header__body">
			<div>
				<div class="wpsb-feature-header__title-row">
					<h3 class="wpsb-feature-header__title"><?php esc_html_e( '予測プリフェッチ', 'kashiwazaki-seo-speed-booster' ); ?></h3>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'IntersectionObserver（ビューポート進入）、mouseover（ホバー 200ms）、touchstart（タップ直前）の 3 層トリガーでリンク先を <link rel="prefetch"> で先読みします。同一オリジン内のみ対象で、外部ドメインは除外されます。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
					<span class="wpsb-badge wpsb-badge--on"><?php esc_html_e( '稼働中', 'kashiwazaki-seo-speed-booster' ); ?></span>
					<span class="wpsb-badge wpsb-badge--off"><?php esc_html_e( '停止中', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</div>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--on"><?php esc_html_e( 'ユーザーのマウスホバー・タッチ・ビューポート進入を検知してリンク先を先読みします。', 'kashiwazaki-seo-speed-booster' ); ?></p>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--off"><?php esc_html_e( '予測プリフェッチは停止中です。有効にすると、ユーザー行動を検知してリンク先を事前読み込みします。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
			<label class="wpsb-toggle">
				<input type="checkbox" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[prefetch_enabled]' ); ?>" value="1" <?php checked( 1, (int) ( $options['prefetch_enabled'] ?? 0 ) ); ?> />
				<span class="wpsb-toggle__slider"></span>
			</label>
		</div>
	</div>

	<div class="wpsb-settings-panel <?php echo $is_off ? 'is-disabled' : ''; ?>">
		<div class="wpsb-feature-grid">
			<?php foreach ( $features as $key => $item ) :
				$card_off = empty( $options[ $key ] );
			?>
				<div class="wpsb-feature-card <?php echo $card_off ? 'is-off' : ''; ?>">
					<div class="wpsb-feature-card__header">
						<span class="wpsb-feature-card__label"><?php echo esc_html( $item['label'] ); ?></span>
						<span class="wpsb-tooltip">
							<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
							<span class="wpsb-tooltip-bubble" hidden><?php echo esc_html( $item['tooltip'] ); ?></span>
						</span>
						<span class="wpsb-badge wpsb-badge--on"><?php esc_html_e( '稼働中', 'kashiwazaki-seo-speed-booster' ); ?></span>
						<span class="wpsb-badge wpsb-badge--off"><?php esc_html_e( '停止中', 'kashiwazaki-seo-speed-booster' ); ?></span>
						<label class="wpsb-toggle wpsb-toggle--sm">
							<input type="checkbox" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[' . $key . ']' ); ?>" value="1" <?php checked( 1, (int) ( $options[ $key ] ?? 0 ) ); ?> />
							<span class="wpsb-toggle__slider"></span>
						</label>
					</div>
					<p class="wpsb-feature-card__desc wpsb-feature-card__desc--on"><?php echo esc_html( $item['tip_on'] ); ?></p>
					<p class="wpsb-feature-card__desc wpsb-feature-card__desc--off"><?php echo esc_html( $item['tip_off'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wpsb-settings-card">
			<h4 class="wpsb-settings-card__title">
				<?php esc_html_e( 'ホバー待機時間', 'kashiwazaki-seo-speed-booster' ); ?>
				<span class="wpsb-tooltip">
					<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
					<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'マウスカーソルがリンク上に乗ってからプリフェッチ開始までの待機時間です。短すぎると通過しただけのリンクも先読みされ、ネットワーク負荷が増します。', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</span>
			</h4>
			<div class="wpsb-field">
				<div class="wpsb-field__control">
					<input type="number" min="0" max="2000" step="10" id="prefetch_hover_delay_ms" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[prefetch_hover_delay_ms]' ); ?>" value="<?php echo esc_attr( (int) ( $options['prefetch_hover_delay_ms'] ?? 200 ) ); ?>" /> ms
				</div>
				<p class="wpsb-field__help"><?php esc_html_e( 'マウスホバーからプリフェッチ開始までの待機時間。デフォルト 200ms。短すぎると誤検知でネットワーク負荷が増えます。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
		</div>
	</div>

	<?php submit_button(); ?>
</form>

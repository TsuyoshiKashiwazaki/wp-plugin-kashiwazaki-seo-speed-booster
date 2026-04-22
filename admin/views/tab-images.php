<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 *
 * @var array $options
 */

defined( 'ABSPATH' ) || exit;

$is_off = empty( $options['image_enabled'] );

$features = [
	'image_lazy'              => [
		'label'   => __( 'loading="lazy"', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( '画面外にある画像をスクロールで表示領域に入るまで読み込みを遅延しています。初期表示に必要ない画像のネットワーク消費を削減し、FCP と LCP を改善します。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( '遅延読み込みは停止中です。有効にすると、画面外の画像読み込みを遅延して初期表示を高速化します。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( '画面外にある画像をスクロールで表示領域に入るまで読み込みを遅延します。初期表示に必要ない画像のネットワーク消費を削減し、FCP と LCP を改善します。', 'kashiwazaki-seo-speed-booster' ),
	],
	'image_decoding_async'    => [
		'label'   => __( 'decoding="async"', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( '画像デコードをメインスレッドからオフロードしています。テキスト描画やユーザー操作をブロックせず、INP の改善に寄与します。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( '非同期デコードは停止中です。有効にすると、画像デコードによるメインスレッドのブロックを防ぎます。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( '画像デコードをメインスレッドからオフロードし、テキスト描画やユーザー操作をブロックしません。INP の改善に寄与します。', 'kashiwazaki-seo-speed-booster' ),
	],
	'image_lcp_fetchpriority' => [
		'label'   => __( 'fetchpriority="high"', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( '投稿の最初の画像（LCP 候補）にブラウザの優先読み込みヒントを付与しています。preload scanner が早期に取得を開始し、LCP を改善します。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'LCP 優先読み込みは停止中です。有効にすると、最初の画像に優先ヒントを付与して LCP を改善します。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( '投稿の最初の画像（LCP 候補）にブラウザの優先読み込みヒントを付与し、preload scanner が早期に取得を開始します。LCP の改善に直結します。', 'kashiwazaki-seo-speed-booster' ),
	],
];
?>
<form method="post" action="options.php">
	<?php settings_fields( WPSB_Settings::GROUP ); ?>
	<input type="hidden" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[_tab]' ); ?>" value="images" />

	<div class="wpsb-feature-header <?php echo $is_off ? 'is-off' : ''; ?>">
		<div class="wpsb-feature-header__body">
			<div>
				<div class="wpsb-feature-header__title-row">
					<h3 class="wpsb-feature-header__title"><?php esc_html_e( '画像最適化', 'kashiwazaki-seo-speed-booster' ); ?></h3>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'the_content フィルタで投稿内の <img> タグを解析し、loading="lazy"・decoding="async"・fetchpriority="high" を条件に応じて自動付与します。WordPress 6.3 以降のネイティブ遅延読み込みと協調して動作します。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
					<span class="wpsb-badge wpsb-badge--on"><?php esc_html_e( '稼働中', 'kashiwazaki-seo-speed-booster' ); ?></span>
					<span class="wpsb-badge wpsb-badge--off"><?php esc_html_e( '停止中', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</div>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--on"><?php esc_html_e( '投稿内の画像に最適な属性を自動付与し、LCP と読み込みパフォーマンスを改善します。', 'kashiwazaki-seo-speed-booster' ); ?></p>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--off"><?php esc_html_e( '画像最適化は停止中です。有効にすると、画像属性が自動で最適化されます。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
			<label class="wpsb-toggle">
				<input type="checkbox" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[image_enabled]' ); ?>" value="1" <?php checked( 1, (int) ( $options['image_enabled'] ?? 0 ) ); ?> />
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
	</div>

	<?php submit_button(); ?>
</form>

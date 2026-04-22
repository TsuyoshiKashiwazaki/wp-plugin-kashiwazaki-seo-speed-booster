<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 *
 * @var array $options
 */

defined( 'ABSPATH' ) || exit;

$features = [
	'prefetch_enabled'    => [
		'label'   => __( '予測プリフェッチ', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( 'ユーザーがリンクにマウスを乗せたりタップしようとした瞬間に、リンク先ページを裏で先読みします。クリック時には取得済みなのでページ遷移が体感的に速くなります。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( '予測プリフェッチは停止中です。有効にすると、リンク先を事前読み込みしてページ遷移を高速化します。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( 'IntersectionObserver（ビューポート進入）、mouseover（ホバー 200ms）、touchstart（タップ直前）の 3 層トリガーでリンク先を <link rel="prefetch"> で先読みします。同一オリジン内のみ対象で、外部ドメインは除外されます。', 'kashiwazaki-seo-speed-booster' ),
	],
	'speculation_enabled' => [
		'label'   => __( 'Speculation Rules API', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( 'Chrome 121+ の Speculation Rules API で次のページを事前レンダリングし、遷移をほぼ瞬時にします。非対応ブラウザでは無視されます。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'Speculation Rules は停止中です。有効にすると、対応ブラウザでページを事前レンダリングして遷移を瞬時にします。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( '<script type="speculationrules"> を HTML に挿入し、ブラウザにプリレンダリング / プリフェッチを指示します。Chromium 121 以降のみ有効で、非対応ブラウザでは無視されます。Prerender は GA タグが遷移前に発火する副作用があるため注意が必要です。', 'kashiwazaki-seo-speed-booster' ),
	],
	'smart_ux_enabled'    => [
		'label'   => __( 'Smart UX（遅延スピナー）', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( 'ページ遷移に時間がかかる場合にスピナーを表示して処理中であることを伝えます。CLS を発生させない設計で体感待ち時間を短縮します。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'Smart UX は停止中です。有効にすると、遷移が遅い場合にスピナーで処理中を伝え、離脱を防ぎます。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( '閾値（デフォルト 200ms）を超えてもページ遷移が完了しない場合にスピナーオーバーレイを表示します。CLS を発生させないよう position:fixed で実装し、背景色・不透明度・ロゴを管理画面から変更できます。', 'kashiwazaki-seo-speed-booster' ),
	],
	'image_enabled'       => [
		'label'   => __( '画像最適化', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( '投稿内の画像に decoding="async"・fetchpriority 属性を自動付与し、LCP と読み込みパフォーマンスを改善します。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( '画像最適化は停止中です。有効にすると、画像属性を自動付与して LCP を改善します。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( 'the_content フィルタで投稿内の <img> タグを解析し、loading="lazy"・decoding="async"・fetchpriority="high" を条件に応じて自動付与します。WordPress 6.3 以降のネイティブ遅延読み込みと協調して動作します。', 'kashiwazaki-seo-speed-booster' ),
	],
	'metrics_enabled'     => [
		'label'   => __( 'Core Web Vitals 計測', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( '実際の訪問者ブラウザで Core Web Vitals を計測し、ダッシュボードで推移を確認できます。軽量スクリプト（< 5 KB）で表示速度への影響はほぼありません。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'CWV 計測は停止中です。有効にすると、訪問者のブラウザで表示速度を自動計測します。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( 'Google 製 web-vitals ライブラリ（< 5 KB）で LCP / INP / CLS / FCP / TTFB を計測し、REST API 経由で DB に記録します。HMAC rolling token 認証でページキャッシュと共存し、IP アドレスは保存しません。', 'kashiwazaki-seo-speed-booster' ),
	],
	'exclude_enabled'     => [
		'label'   => __( 'URL 除外フィルタ', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( '指定したパターンに一致する URL をプリフェッチ・事前レンダリングの対象から除外します。カート・ログアウト等の副作用を防ぎます。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'URL 除外は停止中です。有効にすると、指定パターンに一致する URL がプリフェッチ対象から除外されます。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( 'プリフェッチと Speculation Rules の両方から除外する URL パターンを管理します。glob（* と ?）または /regex/ 形式で指定でき、カート・ログアウト等の副作用がある URL を保護します。', 'kashiwazaki-seo-speed-booster' ),
	],
];

$master_off = empty( $options['enabled'] );
?>
<form method="post" action="options.php">
	<?php settings_fields( WPSB_Settings::GROUP ); ?>
	<input type="hidden" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[_tab]' ); ?>" value="general" />

	<div class="wpsb-master-switch <?php echo $master_off ? 'is-off' : ''; ?>">
		<div class="wpsb-master-switch__body">
			<div class="wpsb-master-switch__text">
				<div class="wpsb-master-switch__header">
					<h3 class="wpsb-master-switch__title"><?php esc_html_e( 'プラグイン全体', 'kashiwazaki-seo-speed-booster' ); ?></h3>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'このスイッチでプラグインの全機能を一括で有効/無効にします。OFF にすると、プリフェッチ・Speculation Rules・Smart UX・画像最適化・CWV 計測のすべてが停止し、フロントエンドへの出力もなくなります。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
					<span class="wpsb-badge wpsb-badge--on"><?php esc_html_e( '稼働中', 'kashiwazaki-seo-speed-booster' ); ?></span>
					<span class="wpsb-badge wpsb-badge--off"><?php esc_html_e( '停止中', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</div>
				<p class="wpsb-master-switch__desc wpsb-master-switch__desc--on"><?php esc_html_e( '以下の 5 機能がサイトに適用されています。各機能は下のカードで個別に切り替えできます。', 'kashiwazaki-seo-speed-booster' ); ?></p>
				<p class="wpsb-master-switch__desc wpsb-master-switch__desc--off"><?php esc_html_e( 'すべての機能が停止中です。プリフェッチ・事前レンダリング・スピナー・画像最適化・CWV 計測のいずれもサイトに反映されていません。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
			<label class="wpsb-toggle">
				<input type="checkbox" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[enabled]' ); ?>" value="1" <?php checked( 1, (int) ( $options['enabled'] ?? 0 ) ); ?> />
				<span class="wpsb-toggle__slider"></span>
			</label>
		</div>
	</div>

	<div class="wpsb-feature-grid <?php echo $master_off ? 'is-master-off' : ''; ?>">
		<?php foreach ( $features as $key => $item ) :
			$card_off = empty( $options[ $key ] ) || $master_off;
		?>
			<div class="wpsb-feature-card <?php echo $card_off ? 'is-off' : ''; ?>">
				<div class="wpsb-feature-card__header">
					<span class="wpsb-feature-card__label"><?php echo esc_html( $item['label'] ); ?></span>
					<?php if ( ! empty( $item['tooltip'] ) ) : ?>
						<span class="wpsb-tooltip">
							<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
							<span class="wpsb-tooltip-bubble" hidden><?php echo esc_html( $item['tooltip'] ); ?></span>
						</span>
					<?php endif; ?>
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

	<?php submit_button(); ?>
</form>

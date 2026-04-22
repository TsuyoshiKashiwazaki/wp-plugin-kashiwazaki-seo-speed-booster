<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 *
 * @var array $options
 */

defined( 'ABSPATH' ) || exit;

$is_off = empty( $options['speculation_enabled'] );

$features = [
	'speculation_prerender' => [
		'label'   => __( 'Prerender', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( 'ページ全体を裏で事前レンダリングしています。遷移がほぼ瞬時になりますが、メモリ・CPU 消費が大きく、GA・広告タグが遷移前に発火する場合があります。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'Prerender は停止中です。有効にすると、次のページ全体を裏で事前レンダリングして遷移を瞬時にします。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( 'ページ全体を裏で事前レンダリングします。遷移がほぼ瞬時になりますが、メモリ・CPU 消費が大きく、GA・広告タグが遷移前に発火する副作用があります。', 'kashiwazaki-seo-speed-booster' ),
	],
	'speculation_prefetch'  => [
		'label'   => __( 'Prefetch', 'kashiwazaki-seo-speed-booster' ),
		'tip_on'  => __( 'リソース（HTML・CSS・JS）のみ事前取得しています。Prerender より軽量で副作用が少なく、安全に導入できます。', 'kashiwazaki-seo-speed-booster' ),
		'tip_off' => __( 'Prefetch は停止中です。有効にすると、リソースを事前取得して遷移を高速化します。', 'kashiwazaki-seo-speed-booster' ),
		'tooltip' => __( 'リソース（HTML・CSS・JS）のみ事前取得し、レンダリングは遷移時に行います。Prerender より軽量で副作用が少なく、安全に導入できます。', 'kashiwazaki-seo-speed-booster' ),
	],
];
?>
<form method="post" action="options.php">
	<?php settings_fields( WPSB_Settings::GROUP ); ?>
	<input type="hidden" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[_tab]' ); ?>" value="speculation" />

	<div class="wpsb-feature-header <?php echo $is_off ? 'is-off' : ''; ?>">
		<div class="wpsb-feature-header__body">
			<div>
				<div class="wpsb-feature-header__title-row">
					<h3 class="wpsb-feature-header__title"><?php esc_html_e( 'Speculation Rules API', 'kashiwazaki-seo-speed-booster' ); ?></h3>
					<span class="wpsb-tooltip">
						<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
						<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( '<script type="speculationrules"> を HTML に挿入し、ブラウザにプリレンダリング / プリフェッチを指示します。Chromium 121 以降のみ有効で、非対応ブラウザでは無視されます。Prerender は GA タグが遷移前に発火する副作用があるため注意が必要です。', 'kashiwazaki-seo-speed-booster' ); ?></span>
					</span>
					<span class="wpsb-badge wpsb-badge--on"><?php esc_html_e( '稼働中', 'kashiwazaki-seo-speed-booster' ); ?></span>
					<span class="wpsb-badge wpsb-badge--off"><?php esc_html_e( '停止中', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</div>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--on"><?php esc_html_e( 'Chrome 121+ で次のページを事前レンダリングし、遷移をほぼ瞬時にします。', 'kashiwazaki-seo-speed-booster' ); ?></p>
				<p class="wpsb-feature-header__desc wpsb-feature-header__desc--off"><?php esc_html_e( 'Speculation Rules は停止中です。有効にすると、対応ブラウザで事前レンダリングが動作します。', 'kashiwazaki-seo-speed-booster' ); ?></p>
			</div>
			<label class="wpsb-toggle">
				<input type="checkbox" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[speculation_enabled]' ); ?>" value="1" <?php checked( 1, (int) ( $options['speculation_enabled'] ?? 0 ) ); ?> />
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

		<div class="wpsb-info-box wpsb-info-box--warning">
			<?php esc_html_e( 'Prerender は Google Analytics・広告タグが遷移前に発火する副作用があります。他のトラッキングタグは document.prerendering 対応が必要です。', 'kashiwazaki-seo-speed-booster' ); ?>
		</div>

		<div class="wpsb-settings-card">
			<h4 class="wpsb-settings-card__title">
				<?php esc_html_e( 'eagerness レベル', 'kashiwazaki-seo-speed-booster' ); ?>
				<span class="wpsb-tooltip">
					<button type="button" class="wpsb-tooltip-trigger" aria-expanded="false">?</button>
					<span class="wpsb-tooltip-bubble" hidden><?php esc_html_e( 'ブラウザが投機的読み込みを開始するタイミングを制御します。Conservative はクリック直前のみ、Moderate はホバー時、Eager / Immediate は表示時に開始します。', 'kashiwazaki-seo-speed-booster' ); ?></span>
				</span>
			</h4>
			<div class="wpsb-field">
				<div class="wpsb-field__control">
					<?php
					$eagerness_labels = [
						'conservative' => __( 'Conservative — クリック直前のみ（最も控えめ）', 'kashiwazaki-seo-speed-booster' ),
						'moderate'     => __( 'Moderate — ホバー時に開始（推奨）', 'kashiwazaki-seo-speed-booster' ),
						'eager'        => __( 'Eager — 表示直後に開始', 'kashiwazaki-seo-speed-booster' ),
						'immediate'    => __( 'Immediate — 即時開始（リソース消費大）', 'kashiwazaki-seo-speed-booster' ),
					];
					?>
					<select id="speculation_eagerness" name="<?php echo esc_attr( WPSB_OPTION_KEY . '[speculation_eagerness]' ); ?>">
						<?php foreach ( $eagerness_labels as $val => $lbl ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $options['speculation_eagerness'] ?? 'moderate', $val ); ?>><?php echo esc_html( $lbl ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
	</div>

	<?php submit_button(); ?>
</form>

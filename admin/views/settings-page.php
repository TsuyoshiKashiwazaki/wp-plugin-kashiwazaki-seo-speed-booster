<?php
/**
 * 管理画面 タブレイアウト + 各タブの view 読み込み。
 *
 * @package KashiwazakiSeoSpeedBooster
 *
 * @var WPSB_Admin $this
 * @var array      $tabs
 * @var string     $current_tab
 */

defined( 'ABSPATH' ) || exit;

$options = $this->settings->get_options();
?>
<div class="wrap wpsb-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<nav class="wpsb-nav-tabs">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a
				href="<?php echo esc_url( add_query_arg( [ 'page' => WPSB_Admin::MENU_SLUG, 'tab' => $slug ], admin_url( 'admin.php' ) ) ); ?>"
				class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>"
			>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<hr class="wpsb-tab-divider" />

	<div class="wpsb-tab-content">
		<?php
		$view_file = WPSB_PLUGIN_DIR . 'admin/views/tab-' . $current_tab . '.php';
		if ( file_exists( $view_file ) ) {
			include $view_file;
		} else {
			echo '<p>' . esc_html__( 'このタブはまだ実装されていません。', 'kashiwazaki-seo-speed-booster' ) . '</p>';
		}
		?>
	</div>
</div>

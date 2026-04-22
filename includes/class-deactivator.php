<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

final class WPSB_Deactivator {

	public static function deactivate(): void {
		// 無効化時はデータ保持。uninstall.php で完全削除。
		wp_clear_scheduled_hook( 'wpsb_daily_purge' );
	}
}

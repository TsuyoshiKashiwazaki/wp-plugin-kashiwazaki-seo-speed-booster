<?php
/**
 * PHPUnit bootstrap。WP 環境なしで URL matcher 等の純粋ロジックをテスト。
 *
 * @package KashiwazakiSeoSpeedBooster
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

// apply_filters スタブ (テスト時は filter を素通し)
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		return $value;
	}
}

require_once __DIR__ . '/../includes/class-url-matcher.php';

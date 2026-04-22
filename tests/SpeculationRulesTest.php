<?php
/**
 * Speculation Rules output logic tests.
 *
 * Tests will_output_speculation_rules() via reflection to verify
 * Fix A (speculationActive desync), Fix B (regex include fallback),
 * and Fix D (eagerness re-validation).
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/class-settings.php';
require_once __DIR__ . '/../includes/class-frontend.php';

// Stub WP functions used by WPSB_Frontend
if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script() {}
}
if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script() {}
}
if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '' ) { return '/plugins/' . $path; }
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action() {}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) { return $default; }
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url ) { return parse_url( $url ); }
}
if ( ! function_exists( 'home_url' ) ) {
	function home_url() { return 'http://localhost'; }
}
if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( $path = '' ) { return '/wp-json/' . $path; }
}
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password() { return 'test'; }
}
if ( ! function_exists( 'add_option' ) ) {
	function add_option() { return true; }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $flags = 0 ) { return json_encode( $data, $flags ); }
}
if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
}
if ( ! function_exists( 'register_setting' ) ) {
	function register_setting() {}
}
if ( ! defined( 'WPSB_OPTION_KEY' ) ) {
	define( 'WPSB_OPTION_KEY', 'kssb_settings' );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

class SpeculationRulesTest extends \PHPUnit\Framework\TestCase {

	private function makeInstance( array $opts_override = [] ): \WPSB_Frontend {
		$settings = new \WPSB_Settings();
		$ref = new \ReflectionClass( $settings );
		$cached = $ref->getProperty( 'cached' );
		$cached->setAccessible( true );
		$defaults = \WPSB_Settings::defaults();
		$cached->setValue( $settings, array_merge( $defaults, $opts_override ) );

		return new \WPSB_Frontend( $settings );
	}

	private function callWillOutput( \WPSB_Frontend $fe, array $opts ): bool {
		$ref = new \ReflectionMethod( $fe, 'will_output_speculation_rules' );
		$ref->setAccessible( true );
		return $ref->invoke( $fe, $opts );
	}

	private function baseOpts(): array {
		return array_merge( \WPSB_Settings::defaults(), [
			'enabled'              => 1,
			'speculation_enabled'  => 1,
			'speculation_prerender' => 1,
			'speculation_prefetch' => 0,
			'speculation_eagerness' => 'moderate',
			'exclude_enabled'      => 0,
			'exclude_patterns'     => '',
			'include_patterns'     => '',
		] );
	}

	// --- Fix A tests ---

	public function test_will_output_true_when_no_regex_patterns(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$this->assertTrue( $this->callWillOutput( $fe, $opts ) );
	}

	public function test_will_output_false_when_disabled(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$opts['enabled'] = 0;
		$this->assertFalse( $this->callWillOutput( $fe, $opts ) );
	}

	public function test_will_output_false_when_speculation_disabled(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$opts['speculation_enabled'] = 0;
		$this->assertFalse( $this->callWillOutput( $fe, $opts ) );
	}

	public function test_will_output_false_when_neither_prerender_nor_prefetch(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$opts['speculation_prerender'] = 0;
		$opts['speculation_prefetch'] = 0;
		$this->assertFalse( $this->callWillOutput( $fe, $opts ) );
	}

	public function test_will_output_false_when_regex_exclude_present(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$opts['exclude_enabled'] = 1;
		$opts['exclude_patterns'] = "/^\\/author\\/.+/i\n/blog/*";
		$this->assertFalse( $this->callWillOutput( $fe, $opts ) );
	}

	public function test_will_output_true_with_glob_excludes_only(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$opts['exclude_enabled'] = 1;
		$opts['exclude_patterns'] = "/private/*\n/secret/*";
		$this->assertTrue( $this->callWillOutput( $fe, $opts ) );
	}

	// --- Fix B tests ---

	public function test_will_output_false_when_all_includes_are_regex(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$opts['exclude_enabled'] = 1;
		$opts['include_patterns'] = "/^\\/blog\\/.+/i\n/^\\/news\\/.+/";
		$this->assertFalse( $this->callWillOutput( $fe, $opts ) );
	}

	public function test_will_output_true_when_includes_have_globs(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$opts['exclude_enabled'] = 1;
		$opts['include_patterns'] = "/blog/*\n/news/*";
		$this->assertTrue( $this->callWillOutput( $fe, $opts ) );
	}

	public function test_will_output_true_with_mixed_includes(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$opts['exclude_enabled'] = 1;
		$opts['include_patterns'] = "/^\\/blog\\/.+/i\n/news/*";
		$this->assertTrue( $this->callWillOutput( $fe, $opts ) );
	}

	public function test_will_output_true_with_no_include_patterns(): void {
		$fe = $this->makeInstance();
		$opts = $this->baseOpts();
		$opts['exclude_enabled'] = 1;
		$opts['include_patterns'] = '';
		$this->assertTrue( $this->callWillOutput( $fe, $opts ) );
	}

	// --- Fix D tests (eagerness re-validation via output) ---

	public function test_output_uses_moderate_for_invalid_eagerness(): void {
		$opts_override = [
			'enabled'              => 1,
			'speculation_enabled'  => 1,
			'speculation_prerender' => 1,
			'speculation_prefetch' => 0,
			'speculation_eagerness' => 'INVALID_VALUE',
			'exclude_enabled'      => 0,
		];
		$fe = $this->makeInstance( $opts_override );
		ob_start();
		$fe->output_speculation_rules();
		$output = ob_get_clean();

		$this->assertStringContainsString( '"eagerness":"moderate"', $output );
		$this->assertStringNotContainsString( 'INVALID_VALUE', $output );
	}

	public function test_output_accepts_valid_eagerness(): void {
		foreach ( [ 'immediate', 'eager', 'moderate', 'conservative' ] as $val ) {
			$fe = $this->makeInstance( [
				'enabled'              => 1,
				'speculation_enabled'  => 1,
				'speculation_prerender' => 1,
				'speculation_eagerness' => $val,
				'exclude_enabled'      => 0,
			] );
			ob_start();
			$fe->output_speculation_rules();
			$output = ob_get_clean();
			$this->assertStringContainsString( '"eagerness":"' . $val . '"', $output, "Eagerness $val should appear in output" );
		}
	}

	// --- Edge case: both prerender and prefetch ---

	public function test_output_has_both_prerender_and_prefetch(): void {
		$fe = $this->makeInstance( [
			'enabled'              => 1,
			'speculation_enabled'  => 1,
			'speculation_prerender' => 1,
			'speculation_prefetch' => 1,
			'exclude_enabled'      => 0,
		] );
		ob_start();
		$fe->output_speculation_rules();
		$output = ob_get_clean();
		$this->assertStringContainsString( '"prerender"', $output );
		$this->assertStringContainsString( '"prefetch"', $output );
	}

	// --- Edge case: regex exclude disables output entirely ---

	public function test_output_empty_when_regex_exclude(): void {
		$fe = $this->makeInstance( [
			'enabled'              => 1,
			'speculation_enabled'  => 1,
			'speculation_prerender' => 1,
			'exclude_enabled'      => 1,
			'exclude_patterns'     => "/^\\/author\\/.+/i",
		] );
		ob_start();
		$fe->output_speculation_rules();
		$output = ob_get_clean();
		$this->assertEmpty( $output );
	}
}

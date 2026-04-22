<?php
/**
 * Image Optimizer tests.
 * Tests Fix C (LCP restrict to post thumbnail), Fix D (filter_content guards),
 * and Fix E (regex path loading="eager" parity).
 */

require_once __DIR__ . '/bootstrap.php';

// Define plugin constants
if ( ! defined( 'WPSB_OPTION_KEY' ) ) {
	define( 'WPSB_OPTION_KEY', 'wpsb_settings' );
}

// Stub WP_Post
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public int $ID = 0;
		public function __construct( $data = null ) {
			if ( $data ) {
				foreach ( (array) $data as $k => $v ) {
					$this->$k = $v;
				}
			}
		}
	}
}

// Stub WP_Query
if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		private bool $_main = false;
		public function __construct( bool $main = false ) { $this->_main = $main; }
		public function is_main_query(): bool { return $this->_main; }
	}
}

// Controllable options via global
$_test_opts = [];

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) {
		global $_test_opts;
		return $key === WPSB_OPTION_KEY ? $_test_opts : $default;
	}
}
if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		return array_merge( $defaults, (array) $args );
	}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action() {}
}
if ( ! function_exists( 'sanitize_hex_color' ) ) {
	function sanitize_hex_color( $c ) { return preg_match( '/^#[0-9a-fA-F]{6}$/', $c ) ? $c : null; }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $u ) { return $u; }
}
if ( ! function_exists( 'register_setting' ) ) {
	function register_setting() {}
}
if ( ! function_exists( 'add_settings_section' ) ) {
	function add_settings_section() {}
}
if ( ! function_exists( 'add_settings_field' ) ) {
	function add_settings_field() {}
}

require_once __DIR__ . '/../includes/class-settings.php';
require_once __DIR__ . '/../includes/class-image-optimizer.php';

// Context flags for test control
$_test_context = [
	'is_feed'     => false,
	'is_admin'    => false,
	'is_singular' => false,
	'thumb_id'    => 0,
	'queried_id'  => 0,
];

if ( ! function_exists( 'is_feed' ) ) {
	function is_feed() { global $_test_context; return $_test_context['is_feed']; }
}
if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() { global $_test_context; return $_test_context['is_admin']; }
}
if ( ! function_exists( 'is_singular' ) ) {
	function is_singular() { global $_test_context; return $_test_context['is_singular']; }
}
if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
	function get_post_thumbnail_id( $post_id = 0 ) { global $_test_context; return $_test_context['thumb_id']; }
}
if ( ! function_exists( 'get_queried_object_id' ) ) {
	function get_queried_object_id() { global $_test_context; return $_test_context['queried_id']; }
}

class ImageOptimizerTest extends \PHPUnit\Framework\TestCase {

	private function make_optimizer( array $overrides = [] ): WPSB_Image_Optimizer {
		global $_test_opts;
		$_test_opts = array_merge( [
			'enabled'                 => 1,
			'image_enabled'           => 1,
			'image_lazy'              => 1,
			'image_decoding_async'    => 1,
			'image_lcp_fetchpriority' => 1,
		], $overrides );

		$settings = new WPSB_Settings();
		$settings->flush_cache();
		return new WPSB_Image_Optimizer( $settings );
	}

	protected function setUp(): void {
		global $_test_context;
		$_test_context = [
			'is_feed'     => false,
			'is_admin'    => false,
			'is_singular' => false,
			'thumb_id'    => 0,
			'queried_id'  => 0,
		];
	}

	// --- Fix D: filter_content guards ---

	public function test_filter_content_skips_feed(): void {
		global $_test_context;
		$_test_context['is_feed'] = true;

		$opt = $this->make_optimizer();
		$html = '<img src="test.jpg">';
		$this->assertSame( $html, $opt->filter_content( $html ) );
	}

	public function test_filter_content_skips_admin(): void {
		global $_test_context;
		$_test_context['is_admin'] = true;

		$opt = $this->make_optimizer();
		$html = '<img src="test.jpg">';
		$this->assertSame( $html, $opt->filter_content( $html ) );
	}

	public function test_filter_content_skips_rest(): void {
		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}

		$opt = $this->make_optimizer();
		$html = '<img src="test.jpg">';
		$this->assertSame( $html, $opt->filter_content( $html ) );
	}

	// --- Fix E: loading="eager" parity ---

	public function test_first_image_gets_eager_and_fetchpriority(): void {
		global $_test_context;
		$_test_context['is_singular'] = true;

		$opt = $this->make_optimizer();
		$ref = new \ReflectionMethod( $opt, 'process_img_tags' );
		$ref->setAccessible( true );

		$html = '<img src="hero.jpg"><img src="other.jpg">';
		$result = $ref->invoke( $opt, $html, true );

		$this->assertStringContainsString( 'fetchpriority="high"', $result );
		$this->assertStringContainsString( 'loading="eager"', $result );
	}

	public function test_first_image_lazy_replaced_with_eager(): void {
		global $_test_context;
		$_test_context['is_singular'] = true;

		$opt = $this->make_optimizer();
		$ref = new \ReflectionMethod( $opt, 'process_img_tags' );
		$ref->setAccessible( true );

		$html = '<img src="hero.jpg" loading="lazy"><img src="other.jpg">';
		$result = $ref->invoke( $opt, $html, true );

		$this->assertStringContainsString( 'loading="eager"', $result );
		$this->assertStringContainsString( 'fetchpriority="high"', $result );
		$first_img_end = strpos( $result, '<img', 1 );
		$first_img = substr( $result, 0, $first_img_end ?: strlen( $result ) );
		$this->assertStringNotContainsString( 'loading="lazy"', $first_img );
	}

	public function test_second_image_gets_lazy(): void {
		global $_test_context;
		$_test_context['is_singular'] = true;

		$opt = $this->make_optimizer();
		$ref = new \ReflectionMethod( $opt, 'process_img_tags' );
		$ref->setAccessible( true );

		$html = '<img src="hero.jpg"><img src="other.jpg">';
		$result = $ref->invoke( $opt, $html, true );

		$parts = explode( '<img', $result );
		$this->assertGreaterThanOrEqual( 3, count( $parts ) );
		$second_img = $parts[2];
		$this->assertStringContainsString( 'loading="lazy"', $second_img );
	}

	public function test_decoding_async_added(): void {
		$opt = $this->make_optimizer();
		$ref = new \ReflectionMethod( $opt, 'process_img_tags' );
		$ref->setAccessible( true );

		$html = '<img src="test.jpg">';
		$result = $ref->invoke( $opt, $html, false );

		$this->assertStringContainsString( 'decoding="async"', $result );
	}

	// --- Fix C: LCP restrict to post thumbnail ---

	public function test_lcp_assigned_to_post_thumbnail(): void {
		global $_test_context;
		$_test_context['is_singular'] = true;
		$_test_context['queried_id'] = 42;
		$_test_context['thumb_id'] = 100;

		$opt = $this->make_optimizer();

		$thumb = new \WP_Post( (object) [ 'ID' => 100 ] );
		$attr = $opt->filter_attachment_attrs( [], $thumb, 'full' );
		$this->assertSame( 'high', $attr['fetchpriority'] ?? null );
		$this->assertSame( 'eager', $attr['loading'] ?? null );
	}

	public function test_lcp_not_assigned_to_non_thumbnail(): void {
		global $_test_context;
		$_test_context['is_singular'] = true;
		$_test_context['queried_id'] = 42;
		$_test_context['thumb_id'] = 100;

		$opt = $this->make_optimizer();

		$non_thumb = new \WP_Post( (object) [ 'ID' => 999 ] );
		$attr = $opt->filter_attachment_attrs( [], $non_thumb, 'full' );
		$this->assertArrayNotHasKey( 'fetchpriority', $attr );
		$this->assertSame( 'lazy', $attr['loading'] ?? null );
	}

	public function test_lcp_not_assigned_when_no_thumbnail(): void {
		global $_test_context;
		$_test_context['is_singular'] = true;
		$_test_context['queried_id'] = 42;
		$_test_context['thumb_id'] = 0;

		$opt = $this->make_optimizer();

		$post = new \WP_Post( (object) [ 'ID' => 50 ] );
		$attr = $opt->filter_attachment_attrs( [], $post, 'full' );
		$this->assertArrayNotHasKey( 'fetchpriority', $attr );
	}

	public function test_lcp_not_assigned_on_archive(): void {
		global $_test_context;
		$_test_context['is_singular'] = false;
		$_test_context['thumb_id'] = 100;

		$opt = $this->make_optimizer();

		$thumb = new \WP_Post( (object) [ 'ID' => 100 ] );
		$attr = $opt->filter_attachment_attrs( [], $thumb, 'full' );
		$this->assertArrayNotHasKey( 'fetchpriority', $attr );
	}
}

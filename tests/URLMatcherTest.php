<?php
/**
 * @package KashiwazakiSeoSpeedBooster
 */

use PHPUnit\Framework\TestCase;

final class URLMatcherTest extends TestCase {

	public function test_glob_match_star(): void {
		$this->assertTrue( WPSB_URL_Matcher::glob_match( '/wp-admin/users.php', '/wp-admin/*' ) );
		$this->assertTrue( WPSB_URL_Matcher::glob_match( '/cart/', '*/cart/*' ) );
		$this->assertFalse( WPSB_URL_Matcher::glob_match( '/home/', '/wp-admin/*' ) );
	}

	public function test_glob_match_question_mark(): void {
		$this->assertTrue( WPSB_URL_Matcher::glob_match( '/a.php', '/?.php' ) );
		$this->assertFalse( WPSB_URL_Matcher::glob_match( '/ab.php', '/?.php' ) );
	}

	public function test_is_regex(): void {
		$this->assertTrue( WPSB_URL_Matcher::is_regex( '/^\/author\/.+/i' ) );
		$this->assertTrue( WPSB_URL_Matcher::is_regex( '/\.php$/' ) );
		$this->assertFalse( WPSB_URL_Matcher::is_regex( '/wp-admin/*' ) ); // glob
		$this->assertFalse( WPSB_URL_Matcher::is_regex( '/wp-admin' ) ); // 末尾 / なし
	}

	public function test_regex_match(): void {
		$this->assertTrue( WPSB_URL_Matcher::match( '/author/tanaka/', '/^\/author\/.+/' ) );
		$this->assertFalse( WPSB_URL_Matcher::match( '/users/', '/^\/author\/.+/' ) );
	}

	public function test_is_excluded(): void {
		$patterns = [
			'/wp-admin/*',
			'/wp-login.php',
			'*/cart/*',
		];
		$this->assertTrue( WPSB_URL_Matcher::is_excluded( '/wp-admin/users.php', $patterns ) );
		$this->assertTrue( WPSB_URL_Matcher::is_excluded( '/wp-login.php', $patterns ) );
		$this->assertTrue( WPSB_URL_Matcher::is_excluded( '/shop/cart/', $patterns ) );
		$this->assertFalse( WPSB_URL_Matcher::is_excluded( '/blog/2026/post-1/', $patterns ) );
	}

	public function test_is_included_whitelist_empty_allows_all(): void {
		$this->assertTrue( WPSB_URL_Matcher::is_included( '/anything/', [] ) );
	}

	public function test_is_included_whitelist_filters(): void {
		$patterns = [ '/blog/*' ];
		$this->assertTrue( WPSB_URL_Matcher::is_included( '/blog/post-1/', $patterns ) );
		$this->assertFalse( WPSB_URL_Matcher::is_included( '/news/', $patterns ) );
	}

	public function test_parse_patterns_strips_empty_lines(): void {
		$raw = "/wp-admin/*\n\n/xmlrpc.php\n  \n";
		$parsed = WPSB_URL_Matcher::parse_patterns( $raw );
		$this->assertCount( 2, $parsed );
		$this->assertSame( [ '/wp-admin/*', '/xmlrpc.php' ], $parsed );
	}

	public function test_invalid_regex_safely_returns_false(): void {
		$this->assertFalse( WPSB_URL_Matcher::match( '/anything/', '/[unclosed/' ) );
	}

	public function test_is_regex_rejects_unsupported_flags(): void {
		$this->assertFalse( WPSB_URL_Matcher::is_regex( '/pattern/x' ) );
		$this->assertFalse( WPSB_URL_Matcher::is_regex( '/pattern/U' ) );
		$this->assertFalse( WPSB_URL_Matcher::is_regex( '/pattern/A' ) );
		$this->assertTrue( WPSB_URL_Matcher::is_regex( '/pattern/i' ) );
		$this->assertTrue( WPSB_URL_Matcher::is_regex( '/pattern/ims' ) );
	}

	public function test_path_like_pattern_treated_as_glob(): void {
		$this->assertFalse( WPSB_URL_Matcher::is_regex( '/wp-admin/' ) );
		$this->assertFalse( WPSB_URL_Matcher::is_regex( '/page/' ) );
		$this->assertTrue( WPSB_URL_Matcher::glob_match( '/wp-admin/', '/wp-admin/' ) );
	}
}

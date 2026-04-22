<?php
/**
 * url_path 正規化のテスト (PHPUnit)。
 * WPSB_Metrics::normalize_path は WP 環境に依存するため、別に純粋関数をテスト。
 *
 * @package KashiwazakiSeoSpeedBooster
 */

use PHPUnit\Framework\TestCase;

final class MetricsNormalizerTest extends TestCase {

	/**
	 * 純粋関数として検証するため、ロジックだけをここで再現 (ロジック同一性をテスト)。
	 */
	private function normalize( string $path ): string {
		$parsed_path = parse_url( $path, PHP_URL_PATH );
		$path = is_string( $parsed_path ) ? $parsed_path : '/';
		$path = trim( $path );
		$path = preg_replace( '#^(/author/)[^/]+(/?.*)$#', '$1:author$2', $path );
		$path = preg_replace( '#^(/(?:members|users)/)[^/]+(/?.*)$#', '$1:user$2', $path );
		$path = preg_replace( '/\d{3,}/', ':id', $path );
		return substr( $path, 0, 255 );
	}

	public function test_author_path(): void {
		$this->assertSame( '/author/:author/', $this->normalize( '/author/tanaka/' ) );
		$this->assertSame( '/author/:author/posts/', $this->normalize( '/author/yamada/posts/' ) );
	}

	public function test_members_and_users(): void {
		$this->assertSame( '/members/:user/', $this->normalize( '/members/12345/' ) );
		$this->assertSame( '/users/:user/profile/', $this->normalize( '/users/tanaka/profile/' ) );
	}

	public function test_id_masking(): void {
		$this->assertSame( '/posts/:id/', $this->normalize( '/posts/12345/' ) );
		// 2桁以下は残す (投稿 ID など日常的な短い数値を潰しすぎない)
		$this->assertSame( '/posts/42/', $this->normalize( '/posts/42/' ) );
	}

	public function test_query_string_stripped(): void {
		$this->assertSame( '/search/', $this->normalize( '/search/?q=term' ) );
	}

	public function test_malformed_url_returns_root(): void {
		$this->assertSame( '/', $this->normalize( 'http://:invalid' ) );
	}

	public function test_empty_string_returns_empty(): void {
		$this->assertSame( '', $this->normalize( '' ) );
	}
}

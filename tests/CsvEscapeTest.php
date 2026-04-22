<?php
/**
 * CSV injection escape tests.
 *
 * @package KashiwazakiSeoSpeedBooster
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/class-admin.php';

final class CsvEscapeTest extends TestCase {

	public function test_formula_prefix_escaped(): void {
		$this->assertSame( "'=SUM(A1)", WPSB_Admin::escape_csv_cell( '=SUM(A1)' ) );
		$this->assertSame( "'+cmd", WPSB_Admin::escape_csv_cell( '+cmd' ) );
		$this->assertSame( "'-data", WPSB_Admin::escape_csv_cell( '-data' ) );
		$this->assertSame( "'@import", WPSB_Admin::escape_csv_cell( '@import' ) );
	}

	public function test_tab_and_cr_escaped(): void {
		$this->assertSame( "'\tfoo", WPSB_Admin::escape_csv_cell( "\tfoo" ) );
		$this->assertSame( "'\rbar", WPSB_Admin::escape_csv_cell( "\rbar" ) );
	}

	public function test_lf_escaped(): void {
		$this->assertSame( "'\nbaz", WPSB_Admin::escape_csv_cell( "\nbaz" ) );
	}

	public function test_safe_value_unchanged(): void {
		$this->assertSame( 'hello', WPSB_Admin::escape_csv_cell( 'hello' ) );
		$this->assertSame( '123.45', WPSB_Admin::escape_csv_cell( '123.45' ) );
	}

	public function test_empty_string(): void {
		$this->assertSame( '', WPSB_Admin::escape_csv_cell( '' ) );
	}
}

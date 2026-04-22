<?php
/**
 * p75 percentile offset calculation test (nearest-rank method).
 *
 * @package KashiwazakiSeoSpeedBooster
 */

use PHPUnit\Framework\TestCase;

final class P75CalculationTest extends TestCase {

	private function p75_offset( int $samples ): int {
		return max( 0, (int) ceil( $samples * 0.75 ) - 1 );
	}

	public function test_1_sample(): void {
		$this->assertSame( 0, $this->p75_offset( 1 ) );
	}

	public function test_2_samples(): void {
		$this->assertSame( 1, $this->p75_offset( 2 ) );
	}

	public function test_3_samples(): void {
		$this->assertSame( 2, $this->p75_offset( 3 ) );
	}

	public function test_4_samples(): void {
		$this->assertSame( 2, $this->p75_offset( 4 ) );
	}

	public function test_100_samples(): void {
		$this->assertSame( 74, $this->p75_offset( 100 ) );
	}

	public function test_1000_samples(): void {
		$this->assertSame( 749, $this->p75_offset( 1000 ) );
	}
}

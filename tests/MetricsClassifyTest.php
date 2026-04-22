<?php
/**
 * WPSB_Metrics::classify() の単体テスト。
 *
 * @package KashiwazakiSeoSpeedBooster
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once dirname( __DIR__ ) . '/includes/class-metrics.php';

final class MetricsClassifyTest extends TestCase {

	public static function thresholdProvider(): array {
		return [
			// LCP
			[ 'LCP', 0.0,    'good' ],
			[ 'LCP', 2500.0, 'good' ],
			[ 'LCP', 2500.1, 'needs-improvement' ],
			[ 'LCP', 4000.0, 'needs-improvement' ],
			[ 'LCP', 4000.1, 'poor' ],

			// FCP
			[ 'FCP', 1800.0, 'good' ],
			[ 'FCP', 1800.1, 'needs-improvement' ],
			[ 'FCP', 3000.0, 'needs-improvement' ],
			[ 'FCP', 3000.1, 'poor' ],

			// INP
			[ 'INP', 200.0, 'good' ],
			[ 'INP', 200.1, 'needs-improvement' ],
			[ 'INP', 500.0, 'needs-improvement' ],
			[ 'INP', 500.1, 'poor' ],

			// CLS
			[ 'CLS', 0.0,   'good' ],
			[ 'CLS', 0.1,   'good' ],
			[ 'CLS', 0.101, 'needs-improvement' ],
			[ 'CLS', 0.25,  'needs-improvement' ],
			[ 'CLS', 0.251, 'poor' ],

			// TTFB
			[ 'TTFB', 800.0,  'good' ],
			[ 'TTFB', 800.1,  'needs-improvement' ],
			[ 'TTFB', 1800.0, 'needs-improvement' ],
			[ 'TTFB', 1800.1, 'poor' ],
		];
	}

	#[DataProvider('thresholdProvider')]
	public function test_classify_thresholds( string $metric, float $value, string $expected ): void {
		$this->assertSame( $expected, WPSB_Metrics::classify( $metric, $value ) );
	}

	public function test_classify_null_returns_unknown(): void {
		$this->assertSame( 'unknown', WPSB_Metrics::classify( 'LCP', null ) );
	}

	public function test_classify_negative_returns_unknown(): void {
		$this->assertSame( 'unknown', WPSB_Metrics::classify( 'LCP', -1.0 ) );
	}

	public function test_classify_unknown_metric_returns_unknown(): void {
		$this->assertSame( 'unknown', WPSB_Metrics::classify( 'NONEXIST', 100.0 ) );
	}
}

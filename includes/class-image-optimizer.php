<?php
/**
 * 画像最適化 (PHP サーバー側)。
 * - loading="lazy" / decoding="async" を未設定 img に付与
 * - LCP 候補 (ファーストビュー) に fetchpriority="high" を付与
 *
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

final class WPSB_Image_Optimizer {

	private WPSB_Settings $settings;
	private bool $lcp_assigned = false;

	public function __construct( WPSB_Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		$opts = $this->settings->get_options();
		if ( empty( $opts['enabled'] ) || empty( $opts['image_enabled'] ) ) {
			return;
		}

		add_filter( 'wp_get_attachment_image_attributes', [ $this, 'filter_attachment_attrs' ], 10, 3 );
		add_filter( 'the_content', [ $this, 'filter_content' ], 20 );
		add_action( 'the_post', [ $this, 'reset_lcp_state' ], 10, 2 );
	}

	public function reset_lcp_state( \WP_Post $post, \WP_Query $query ): void {
		if ( $query->is_main_query() ) {
			$this->lcp_assigned = false;
		}
	}

	public function filter_attachment_attrs( array $attr, $attachment, $size ): array {
		$opts = $this->settings->get_options();

		if ( ! empty( $opts['image_lazy'] ) && ! isset( $attr['loading'] ) ) {
			$attr['loading'] = 'lazy';
		}
		if ( ! empty( $opts['image_decoding_async'] ) && ! isset( $attr['decoding'] ) ) {
			$attr['decoding'] = 'async';
		}

		if ( ! empty( $opts['image_lcp_fetchpriority'] ) && ! $this->lcp_assigned && is_singular() ) {
			$thumb_id = (int) get_post_thumbnail_id( get_queried_object_id() );
			if ( $thumb_id && $attachment instanceof \WP_Post && (int) $attachment->ID === $thumb_id ) {
				$attr['fetchpriority'] = 'high';
				$attr['loading']       = 'eager';
				$this->lcp_assigned    = true;
			}
		}

		return $attr;
	}

	public function filter_content( string $content ): string {
		if ( is_feed() || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $content;
		}
		return $this->process_img_tags( $content, is_singular() );
	}

	private function process_img_tags( string $html, bool $in_singular ): string {
		$opts = $this->settings->get_options();
		if ( strpos( $html, '<img' ) === false ) {
			return $html;
		}

		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new WP_HTML_Tag_Processor( $html );
			while ( $processor->next_tag( 'img' ) ) {
				if ( ! $this->lcp_assigned && $in_singular && ! empty( $opts['image_lcp_fetchpriority'] ) ) {
					$processor->set_attribute( 'fetchpriority', 'high' );
					$processor->set_attribute( 'loading', 'eager' );
					$this->lcp_assigned = true;
				} else {
					if ( ! empty( $opts['image_lazy'] ) && $processor->get_attribute( 'loading' ) === null ) {
						$processor->set_attribute( 'loading', 'lazy' );
					}
				}
				if ( ! empty( $opts['image_decoding_async'] ) && $processor->get_attribute( 'decoding' ) === null ) {
					$processor->set_attribute( 'decoding', 'async' );
				}
			}
			return $processor->get_updated_html();
		}

		$is_first = true;
		return preg_replace_callback(
			'/<img\b(?:[^>"\']*|"[^"]*"|\'[^\']*\')*>/i',
			function ( $m ) use ( $opts, $in_singular, &$is_first ) {
				$tag = $m[0];
				if ( $is_first && $in_singular && ! empty( $opts['image_lcp_fetchpriority'] ) && ! $this->lcp_assigned ) {
					if ( ! preg_match( '/\bfetchpriority\s*=/i', $tag ) ) {
						$tag = preg_replace( '/\/?>$/', ' fetchpriority="high"$0', $tag );
					}
					if ( preg_match( '/\bloading\s*=\s*["\']?lazy["\']?/i', $tag ) ) {
						$tag = preg_replace( '/\bloading\s*=\s*["\']?lazy["\']?/i', 'loading="eager"', $tag );
					} elseif ( ! preg_match( '/\bloading\s*=/i', $tag ) ) {
						$tag = preg_replace( '/\/?>$/', ' loading="eager"$0', $tag );
					}
					$this->lcp_assigned = true;
					$is_first = false;
				} else {
					if ( ! empty( $opts['image_lazy'] ) && ! preg_match( '/\bloading\s*=/i', $tag ) ) {
						$tag = preg_replace( '/\/?>$/', ' loading="lazy"$0', $tag );
					}
					$is_first = false;
				}
				if ( ! empty( $opts['image_decoding_async'] ) && ! preg_match( '/\bdecoding\s*=/i', $tag ) ) {
					$tag = preg_replace( '/\/?>$/', ' decoding="async"$0', $tag );
				}
				return $tag;
			},
			$html
		);
	}
}

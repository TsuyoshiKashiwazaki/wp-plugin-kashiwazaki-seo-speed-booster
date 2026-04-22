<?php
/**
 * URL パターンマッチャ (glob + regex)
 *
 * - 正規表現は `/pattern/flags` 形式 (例: `/^\/author\/.+/i`) で指定
 * - それ以外は glob (`*` は任意文字列、`?` は 1 文字) として評価
 *
 * @package KashiwazakiSeoSpeedBooster
 */

defined( 'ABSPATH' ) || exit;

final class WPSB_URL_Matcher {

	/**
	 * パスが除外パターンリストのいずれかにマッチするか。
	 */
	public static function is_excluded( string $path, array $patterns ): bool {
		$result = false;
		foreach ( $patterns as $pattern ) {
			if ( self::match( $path, $pattern ) ) {
				$result = true;
				break;
			}
		}

		/**
		 * 除外判定をフィルタリングするためのフック。
		 *
		 * @param bool   $result  除外対象か。
		 * @param string $path    判定対象パス。
		 * @param array  $patterns 設定されたパターン。
		 */
		return (bool) apply_filters( 'wpsb_exclude_url', $result, $path, $patterns );
	}

	/**
	 * ホワイトリストモード: パターンが設定されていればそれにマッチするものだけ許可。
	 * 空なら常に許可 (ホワイトリストモード無効)。
	 */
	public static function is_included( string $path, array $patterns ): bool {
		if ( empty( $patterns ) ) {
			return true;
		}
		foreach ( $patterns as $pattern ) {
			if ( self::match( $path, $pattern ) ) {
				return true;
			}
		}
		return false;
	}

	public static function match( string $path, string $pattern ): bool {
		$pattern = trim( $pattern );
		if ( $pattern === '' ) {
			return false;
		}

		if ( self::is_regex( $pattern ) ) {
			$prev_limit = ini_get( 'pcre.backtrack_limit' );
			ini_set( 'pcre.backtrack_limit', 10000 ); // phpcs:ignore WordPress.PHP.IniSet.Risky
			$result = @preg_match( $pattern, $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			ini_set( 'pcre.backtrack_limit', $prev_limit ); // phpcs:ignore WordPress.PHP.IniSet.Risky
			return $result === 1;
		}

		return self::glob_match( $path, $pattern );
	}

	/**
	 * glob → PCRE への変換 + マッチ。
	 */
	public static function glob_match( string $path, string $glob ): bool {
		// glob の `*` / `?` を PCRE に変換
		$regex = '';
		$len   = strlen( $glob );
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $glob[ $i ];
			if ( $c === '*' ) {
				$regex .= '.*';
			} elseif ( $c === '?' ) {
				$regex .= '.';
			} else {
				$regex .= preg_quote( $c, '#' );
			}
		}
		return (bool) preg_match( '#^' . $regex . '$#', $path );
	}

	public static function is_regex( string $pattern ): bool {
		if ( strlen( $pattern ) < 2 ) {
			return false;
		}
		if ( $pattern[0] !== '/' ) {
			return false;
		}
		$last_slash = strrpos( $pattern, '/' );
		if ( $last_slash === 0 ) {
			return false;
		}
		$flags = substr( $pattern, $last_slash + 1 );
		if ( $flags !== '' && ! preg_match( '/^[imsu]+$/', $flags ) ) {
			return false;
		}
		// F2: Without flags, require at least one regex metacharacter in the body
		// to avoid misclassifying paths like /wp-admin/ as regex.
		if ( $flags === '' ) {
			$body = substr( $pattern, 1, $last_slash - 1 );
			if ( ! preg_match( '/[\\\\^$\[()|+{]/', $body ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 複数行文字列をパターン配列に変換。
	 */
	public static function parse_patterns( string $raw ): array {
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$out   = [];
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( $line === '' ) {
				continue;
			}
			$out[] = $line;
		}
		return $out;
	}
}

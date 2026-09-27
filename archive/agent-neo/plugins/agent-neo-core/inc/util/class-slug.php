<?php
/**
 * 共有 slug 正規化ユーティリティ。
 *
 * R-09a（data-model-ids.md §4）に定めた `sanitize_slug()` アルゴリズムを実装する。
 * section_id / cta_id 等の内部 ID slug 生成に使用し、`sanitize_title()` の代替として
 * 非 ASCII 文字を確実に排除した `[a-z0-9-]` のみの文字列を返す。
 *
 * CARRY-G2-009 / CARRY-G2-013 の要求に対応する唯一の正本関数。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 共有 slug 正規化ユーティリティクラス。
 *
 * 全メソッドは static で提供する。インスタンス化は不要。
 *
 * 使用例:
 *   $slug = Agent_Neo_Core_Slug::sanitize_slug( 'SEO 基礎' ); // → 'seo'
 *   $slug = Agent_Neo_Core_Slug::sanitize_slug( '日本語のみ' ); // → 'a1b2c3d4e5f6'（UUID短縮）
 */
final class Agent_Neo_Core_Slug {

	/**
	 * slug の最大長（文字数）。data-model-ids.md §R-09 準拠。
	 *
	 * @var int
	 */
	private const MAX_LENGTH = 128;

	/**
	 * インスタンス化を禁止する。
	 */
	private function __construct() {}

	/**
	 * 入力文字列を内部 slug へ正規化する（R-09a アルゴリズム）。
	 *
	 * 処理ステップ:
	 *   1. 非 ASCII を除去（iconv で ASCII に変換 + 残留非 ASCII を削除）
	 *   2. 小文字化
	 *   3. 許可外文字（`[^a-z0-9-]`）をハイフンへ置換
	 *   4. 連続ハイフンを 1 個に圧縮
	 *   5. 先頭・末尾のハイフンを除去
	 *   6. 128 文字超を末尾切り捨て後、末尾ハイフン再除去
	 *   7. 結果が空 or `^[a-z0-9-]+$` 不一致 → `wp_generate_uuid4()` 先頭 12 文字で代替
	 *
	 * PHP 環境では `unicodedata.normalize('NFKD')` に相当する操作を
	 * `iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ...)` で実現する。
	 * これにより ä → a、é → e などのラテン拡張文字が ASCII に変換され、
	 * 変換できない非 ASCII（CJK 等）は除去される。
	 *
	 * @param string $input 正規化対象の入力文字列。
	 * @return string `[a-z0-9-]` のみから成る非空文字列。必ず 1 文字以上を返す。
	 */
	public static function sanitize_slug( string $input ): string {
		// 1. NFKD 相当の ASCII 変換 + 非 ASCII 除去。
		//    iconv の //TRANSLIT は合成文字をラテン近似に変換し、//IGNORE は変換不能文字を除去する。
		$ascii = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $input );
		if ( false === $ascii ) {
			// iconv が利用できない環境では ASCII 範囲外を直接除去する。
			$ascii = preg_replace( '/[^\x00-\x7F]/', '', $input );
			if ( ! is_string( $ascii ) ) {
				$ascii = '';
			}
		}

		// 2. 小文字化。
		$result = strtolower( $ascii );

		// 3. 許可外文字（スペース・記号等）をハイフンへ置換。
		$result = (string) preg_replace( '/[^a-z0-9-]/', '-', $result );

		// 4. 連続ハイフンを 1 個に圧縮。
		$result = (string) preg_replace( '/-{2,}/', '-', $result );

		// 5. 先頭・末尾のハイフンを除去。
		$result = trim( $result, '-' );

		// 6. 128 文字超を末尾切り捨て後、末尾ハイフン再除去。
		if ( strlen( $result ) > self::MAX_LENGTH ) {
			$result = rtrim( substr( $result, 0, self::MAX_LENGTH ), '-' );
		}

		// 7. フォールバック判定: 空 or パターン不一致 → UUID 短縮形（先頭 12 文字）。
		if ( '' === $result || ! (bool) preg_match( '/^[a-z0-9-]+$/', $result ) ) {
			// wp_generate_uuid4() は 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx' 形式を返す。
			// ハイフンを除いた小文字 hex 32 文字から先頭 12 文字を取り出す。
			$uuid   = wp_generate_uuid4();
			$result = substr( str_replace( '-', '', $uuid ), 0, 12 );
		}

		return $result;
	}

	/**
	 * slug として有効な文字列（`^[a-z0-9-]+$`）かを検証する。
	 *
	 * 先頭・末尾ハイフン禁止ルール（R-09）も合わせて検証する。
	 *
	 * @param string $value 検証対象の文字列。
	 * @return bool 有効なら true、無効なら false。
	 */
	public static function is_valid_slug( string $value ): bool {
		if ( '' === $value ) {
			return false;
		}

		// 最大長チェック。
		if ( strlen( $value ) > self::MAX_LENGTH ) {
			return false;
		}

		// 文字セット + 先頭・末尾ハイフン禁止チェック（R-09a 準拠）。
		return (bool) preg_match( '/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/', $value );
	}
}

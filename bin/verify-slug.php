<?php
/**
 * TC-017b / TC-025 単体検証スクリプト。
 *
 * 実行方法:
 *   docker compose run --rm -T wpcli eval-file /tmp/host/slug-verify.php
 *
 * 検証項目:
 *   1. "SEO 基礎"    → "seo"（非ASCII除去、ASCII "SEO" 残留 → "seo"）
 *   2. "日本語のみ"   → UUID短縮形 [a-z0-9]{12}（全非ASCII → フォールバック）
 *   3. "Hello World!" → "hello-world"
 *   4. "  Multiple---Hyphens  " → "multiple-hyphens"
 *   5. 全ケースの出力が /^[a-z0-9-]+$/ に合致すること
 *   6. sanitize_title() と sanitize_slug() が非ASCII入力で異なること（TC-017b②分離証明）
 *
 * @package AgentNeoCore
 */

// Agent_Neo_Core_Slug クラスを読み込む。
// wp eval-file は WP 環境内で実行されるためプラグインが自動読み込み済みのはず。
// 万一未読み込みの場合は直接 require する。
if ( ! class_exists( 'Agent_Neo_Core_Slug' ) ) {
	$class_path = WP_PLUGIN_DIR . '/agent-neo-plugins/agent-neo-core/inc/util/class-slug.php';
	if ( ! file_exists( $class_path ) ) {
		// フォールバックパス（wp-content/plugins/agent-neo-plugins/ 配下）。
		$class_path = dirname( __DIR__, 2 ) . '/plugins/agent-neo-plugins/agent-neo-core/inc/util/class-slug.php';
	}
	if ( file_exists( $class_path ) ) {
		require_once $class_path;
	} else {
		// Docker 内のシンボリックリンク経由パスで再試探。
		$candidates = glob( WP_PLUGIN_DIR . '/*/agent-neo-core/inc/util/class-slug.php' );
		if ( ! empty( $candidates ) ) {
			require_once $candidates[0];
		}
	}
}

if ( ! class_exists( 'Agent_Neo_Core_Slug' ) ) {
	echo "FATAL: Agent_Neo_Core_Slug クラスが見つかりません。\n";
	exit( 1 );
}

// ─────────────────────────────────────────────────────────────
// ヘルパー関数
// ─────────────────────────────────────────────────────────────

/** @var int $pass_count */
$pass_count = 0;
/** @var int $fail_count */
$fail_count = 0;

/**
 * 単一テストケースを実行して PASS/FAIL を出力する。
 *
 * @param string $label     テストケース名。
 * @param string $actual    実際の結果。
 * @param string $expected  期待値（'__uuid_fallback__' は UUID 短縮形パターン検証）。
 * @param int    &$pass_count PASS 数（参照）。
 * @param int    &$fail_count FAIL 数（参照）。
 */
function assert_slug( string $label, string $actual, string $expected, int &$pass_count, int &$fail_count ): void {
	// 文字セット検証は全ケース共通。
	$charset_ok = (bool) preg_match( '/^[a-z0-9-]+$/', $actual );

	if ( '__uuid_fallback__' === $expected ) {
		// UUID 短縮形: 小文字 hex 12 文字。
		$value_ok = (bool) preg_match( '/^[a-f0-9]{12}$/', $actual );
	} else {
		$value_ok = ( $actual === $expected );
	}

	$ok = $charset_ok && $value_ok;

	if ( $ok ) {
		echo "PASS [{$label}] → \"{$actual}\"\n";
		++$pass_count;
	} else {
		$charset_note = $charset_ok ? '' : '  ※ 文字セット違反（非ASCII/大文字/記号を含む）';
		$value_note   = $value_ok  ? '' : "  ※ 期待値: \"{$expected}\"";
		echo "FAIL [{$label}] → \"{$actual}\"{$charset_note}{$value_note}\n";
		++$fail_count;
	}
}

// ─────────────────────────────────────────────────────────────
// テストケース実行
// ─────────────────────────────────────────────────────────────

echo "=== TC-017b / TC-025 slug 単体検証 ===\n\n";
echo "--- ケース 1-4: 値・文字セット検証 ---\n";

// ケース 1: "SEO 基礎" → "seo"（"基礎" は非ASCII除去、ASCII "SEO" が残り小文字化）
assert_slug(
	'TC-017b-C1: "SEO 基礎" → "seo"',
	Agent_Neo_Core_Slug::sanitize_slug( 'SEO 基礎' ),
	'seo',
	$pass_count,
	$fail_count
);

// ケース 2: "日本語のみ" → UUID短縮形（全非ASCII → 空 → フォールバック）
assert_slug(
	'TC-017b-C2: "日本語のみ" → UUID短縮フォールバック',
	Agent_Neo_Core_Slug::sanitize_slug( '日本語のみ' ),
	'__uuid_fallback__',
	$pass_count,
	$fail_count
);

// ケース 3: "Hello World!" → "hello-world"（! は除去 → ハイフン → 末尾strip）
assert_slug(
	'TC-025-C3: "Hello World!" → "hello-world"',
	Agent_Neo_Core_Slug::sanitize_slug( 'Hello World!' ),
	'hello-world',
	$pass_count,
	$fail_count
);

// ケース 4: "  Multiple---Hyphens  " → "multiple-hyphens"（連続ハイフン圧縮・前後除去）
assert_slug(
	'TC-025-C4: "  Multiple---Hyphens  " → "multiple-hyphens"',
	Agent_Neo_Core_Slug::sanitize_slug( '  Multiple---Hyphens  ' ),
	'multiple-hyphens',
	$pass_count,
	$fail_count
);

// ─────────────────────────────────────────────────────────────
// ケース 5: 全ケースの文字セット検証（上記で charset_ok も検証済みだが明示的に再確認）
// ─────────────────────────────────────────────────────────────
echo "\n--- ケース 5: 文字セット一括確認（[a-z0-9-]+ のみ） ---\n";

$charset_inputs = array(
	'SEO 基礎',
	'日本語のみ',
	'Hello World!',
	'  Multiple---Hyphens  ',
	'café résumé',         // ラテン拡張 → ASCII 変換
	'',                    // 空文字 → UUID フォールバック
	str_repeat( 'a', 200 ), // 200 文字超 → 128 文字切り捨て
);

$charset_all_pass = true;
foreach ( $charset_inputs as $input ) {
	$out = Agent_Neo_Core_Slug::sanitize_slug( $input );
	if ( ! (bool) preg_match( '/^[a-z0-9-]+$/', $out ) ) {
		echo "FAIL [文字セット] input=\"{$input}\" → \"{$out}\" 非ASCII/大文字/記号を含む\n";
		++$fail_count;
		$charset_all_pass = false;
	}
}
if ( $charset_all_pass ) {
	echo "PASS [文字セット一括] 全 " . count( $charset_inputs ) . " ケースが [a-z0-9-]+ 準拠\n";
	++$pass_count;
}

// ─────────────────────────────────────────────────────────────
// ケース 6: sanitize_title() ≠ sanitize_slug() の分離証明（TC-017b②）
// ─────────────────────────────────────────────────────────────
echo "\n--- ケース 6: sanitize_title() との分離証明（TC-017b ②） ---\n";

$separation_inputs = array(
	'SEO 基礎',
	'日本語のみ',
);

$separation_passed = true;
foreach ( $separation_inputs as $input ) {
	$title_result = sanitize_title( $input );
	$slug_result  = Agent_Neo_Core_Slug::sanitize_slug( $input );

	// sanitize_title() の戻り値と sanitize_slug() の戻り値が異なる（分離の証明）。
	// かつ sanitize_title() の戻り値が非ASCII or 空になることを確認。
	$are_different = ( $title_result !== $slug_result );

	// sanitize_slug() は必ず [a-z0-9-]+ を保証するが、sanitize_title() は保証しない。
	$slug_charset_ok  = (bool) preg_match( '/^[a-z0-9-]+$/', $slug_result );
	$title_charset_ok = (bool) preg_match( '/^[a-z0-9-]+$/', $title_result );

	if ( $are_different && $slug_charset_ok ) {
		echo "PASS [分離-\"" . mb_substr( $input, 0, 12 ) . "\"] " .
			"sanitize_title=\"{$title_result}\" / sanitize_slug=\"{$slug_result}\" → 分離確認\n";
		++$pass_count;

		// sanitize_title が非ASCII slugを返す場合は追加情報として表示。
		if ( ! $title_charset_ok ) {
			echo "     ※ sanitize_title() は [a-z0-9-] 外の文字を含む → 内部ID用途不可（TC-017b②証明）\n";
		}
	} else {
		echo "FAIL [分離-\"" . mb_substr( $input, 0, 12 ) . "\"] " .
			"sanitize_title=\"{$title_result}\" / sanitize_slug=\"{$slug_result}\" " .
			"→ 分離できていない or sanitize_slug の文字セット違反\n";
		++$fail_count;
		$separation_passed = false;
	}
}

// ─────────────────────────────────────────────────────────────
// 追加: 境界値テスト（回帰性確認）
// ─────────────────────────────────────────────────────────────
echo "\n--- ケース 7: 境界値・追加ケース ---\n";

// 数字のみ入力。
assert_slug(
	'境界値: "123" → "123"',
	Agent_Neo_Core_Slug::sanitize_slug( '123' ),
	'123',
	$pass_count,
	$fail_count
);

// ハイフン始まり・終わり入力。
assert_slug(
	'境界値: "-hello-" → "hello"',
	Agent_Neo_Core_Slug::sanitize_slug( '-hello-' ),
	'hello',
	$pass_count,
	$fail_count
);

// 既にスラグな入力はそのまま返す。
assert_slug(
	'境界値: "valid-slug-123" → "valid-slug-123"',
	Agent_Neo_Core_Slug::sanitize_slug( 'valid-slug-123' ),
	'valid-slug-123',
	$pass_count,
	$fail_count
);

// ラテン拡張文字（iconv TRANSLIT でASCIIに変換されること）。
$cafe_result = Agent_Neo_Core_Slug::sanitize_slug( 'café résumé' );
$cafe_charset_ok = (bool) preg_match( '/^[a-z0-9-]+$/', $cafe_result );
if ( $cafe_charset_ok ) {
	echo "PASS [ラテン拡張: \"café résumé\"] → \"{$cafe_result}\" （文字セット準拠）\n";
	++$pass_count;
} else {
	echo "FAIL [ラテン拡張: \"café résumé\"] → \"{$cafe_result}\" （文字セット違反）\n";
	++$fail_count;
}

// ─────────────────────────────────────────────────────────────
// サマリ出力
// ─────────────────────────────────────────────────────────────
echo "\n=== 結果サマリ ===\n";
echo "PASS: {$pass_count} / FAIL: {$fail_count}\n";

if ( 0 === $fail_count ) {
	echo "ALL PASS - TC-017b / TC-025 検証完了\n";
	exit( 0 );
} else {
	echo "FAILED - {$fail_count} 件のテストが失敗しました\n";
	exit( 1 );
}

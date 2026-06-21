<?php
/**
 * AGENT NEO SBOM 生成スクリプト（CycloneDX 1.6 JSON 形式）
 *
 * 使用方法: php bin/generate-sbom.php
 * 出力: リポジトリルート sbom.cdx.json
 *
 * 外部依存ゼロの first-party コンポーネントのみで構成されるため、
 * components = AGENT NEO 自身（3件）+ WordPress プラットフォーム + PHP ランタイム。
 *
 * @package AgentNeo
 */

// リポジトリルートを特定する（bin/ の親ディレクトリ）
$repo_root = dirname( __DIR__ );

// ============================================================
// 1. コンポーネント定義
// ============================================================

/**
 * プラグイン・テーマヘッダから指定フィールドを読み取る。
 *
 * @param string $file_path ヘッダを含む PHP / CSS ファイルパス。
 * @param string $field     取得するフィールド名（例: "Version"）。
 * @return string 取得値（未発見時は空文字列）。
 */
function sbom_read_header_field( string $file_path, string $field ): string {
	if ( ! is_readable( $file_path ) ) {
		return '';
	}
	// WordPress get_file_data 相当の簡易読み取り（先頭 8KB のみ走査）
	$content = file_get_contents( $file_path, false, null, 0, 8192 );
	if ( $content === false ) {
		return '';
	}
	// パターン: "* {Field}: {value}" または "/* {Field}: {value}" 形式
	if ( preg_match( '/^[\/\s\*]*' . preg_quote( $field, '/' ) . '\s*:\s*(.+)$/mi', $content, $matches ) ) {
		return trim( $matches[1] );
	}
	return '';
}

/**
 * ディレクトリ配下の PHP ファイル（直下 + 1階層）を対象に SHA-256 ハッシュを計算する。
 * 再現性を保つため、ファイルパスをソートしてから結合して最終ハッシュを導出する。
 *
 * @param string $dir_path コンポーネントディレクトリの絶対パス。
 * @return array{alg: string, content: string} CycloneDX hashes 形式の配列。
 */
function sbom_component_hash( string $dir_path ): array {
	// 対象: エントリポイント PHP ファイル（メインファイル + functions.php + style.css）
	$target_files = [];

	// プラグインメインファイル（ディレクトリ名.php）
	$basename    = basename( $dir_path );
	$main_php    = $dir_path . '/' . $basename . '.php';
	// テーマ: style.css が正本ヘッダ
	$style_css   = $dir_path . '/style.css';
	$functions   = $dir_path . '/functions.php';

	foreach ( [ $main_php, $style_css, $functions ] as $f ) {
		if ( is_readable( $f ) ) {
			$target_files[] = $f;
		}
	}

	// ファイルが一切見つからない場合はディレクトリ直下の PHP 全件
	if ( empty( $target_files ) ) {
		foreach ( glob( $dir_path . '/*.php' ) as $f ) {
			$target_files[] = $f;
		}
	}

	sort( $target_files );

	// 各ファイルの sha256 を結合し、最終的なコンポーネントハッシュを生成
	$combined = '';
	foreach ( $target_files as $f ) {
		$combined .= hash_file( 'sha256', $f );
	}
	$final_hash = hash( 'sha256', $combined );

	return [
		'alg'     => 'SHA-256',
		'content' => $final_hash,
	];
}

// ============================================================
// 2. AGENT NEO コンポーネント定義（ヘッダから動的読み取り）
// ============================================================

$core_dir   = $repo_root . '/plugins/agent-neo-core';
$embed_dir  = $repo_root . '/plugins/agent-neo-embed';
$theme_dir  = $repo_root . '/themes/agent-neo-theme';

$core_version  = sbom_read_header_field( $core_dir  . '/agent-neo-core.php', 'Version' ) ?: '0.1.0';
$embed_version = sbom_read_header_field( $embed_dir . '/agent-neo-embed.php', 'Version' ) ?: '0.1.0';
$theme_version = sbom_read_header_field( $theme_dir . '/style.css', 'Version' ) ?: '0.1.0';

// ライセンス（SPDX 識別子）
// GPL v2 or later → SPDX: GPL-2.0-or-later
$gpl_license = [
	[
		'license' => [
			'id'  => 'GPL-2.0-or-later',
			'url' => 'https://www.gnu.org/licenses/gpl-2.0.html',
		],
	],
];

// サプライヤー
$supplier = [
	'name'    => 'AGENT NEO / RetryYN',
	'url'     => [ 'https://github.com/RetryYN/AGENT-NEO' ],
	'contact' => [],
];

// ============================================================
// 3. CycloneDX 1.6 JSON ドキュメント構築
// ============================================================

$sbom = [
	'bomFormat'   => 'CycloneDX',
	'specVersion' => '1.6',
	'version'     => 1,
	'serialNumber' => 'urn:uuid:' . sbom_generate_deterministic_uuid( 'agent-neo-sbom-v' . $core_version ),
	'metadata'    => [
		// 再現性のためタイムスタンプはハードコード
		'timestamp' => '2026-06-21T00:00:00Z',
		'tools'     => [
			[
				'type'    => 'application',
				'name'    => 'AGENT NEO SBOM Generator',
				'version' => '1.0.0',
				'vendor'  => 'RetryYN',
			],
		],
		'component' => [
			'type'    => 'application',
			'name'    => 'AGENT NEO Distribution',
			'version' => $core_version,
			'supplier' => $supplier,
			'licenses' => $gpl_license,
			'description' => 'AGENT NEO テーマ + 2 プラグインの商用配布パッケージ（first-party のみ・外部サードパーティ依存ゼロ）',
		],
	],
	'components'  => [],
	'dependencies' => [],
];

// ---- AGENT NEO Core プラグイン ----
$sbom['components'][] = [
	'type'        => 'application',
	'bom-ref'     => 'pkg:wordpress-plugin/agent-neo-core@' . $core_version,
	'name'        => 'AGENT NEO Core',
	'version'     => $core_version,
	'description' => 'AGENT NEO REST API・監査ログ・スキーマ・ライフサイクル基盤プラグイン',
	'supplier'    => $supplier,
	'licenses'    => $gpl_license,
	'purl'        => 'pkg:wordpress-plugin/agent-neo-core@' . $core_version,
	'hashes'      => [ sbom_component_hash( $core_dir ) ],
	'properties'  => [
		[ 'name' => 'wordpress:text-domain',  'value' => 'agent-neo-core' ],
		[ 'name' => 'wordpress:requires-php', 'value' => '8.1' ],
		[ 'name' => 'wordpress:requires-at-least', 'value' => '6.6' ],
	],
];

// ---- AGENT NEO Embed プラグイン ----
$sbom['components'][] = [
	'type'        => 'application',
	'bom-ref'     => 'pkg:wordpress-plugin/agent-neo-embed@' . $embed_version,
	'name'        => 'AGENT NEO Embed',
	'version'     => $embed_version,
	'description' => 'AGENT NEO dual-mode 埋め込みブロック（Shadow DOM / sandbox iframe）プラグイン',
	'supplier'    => $supplier,
	'licenses'    => $gpl_license,
	'purl'        => 'pkg:wordpress-plugin/agent-neo-embed@' . $embed_version,
	'hashes'      => [ sbom_component_hash( $embed_dir ) ],
	'properties'  => [
		[ 'name' => 'wordpress:text-domain',  'value' => 'agent-neo-embed' ],
		[ 'name' => 'wordpress:requires-php', 'value' => '8.1' ],
		[ 'name' => 'wordpress:requires-at-least', 'value' => '6.3' ],
	],
];

// ---- AGENT NEO Theme ----
$sbom['components'][] = [
	'type'        => 'application',
	'bom-ref'     => 'pkg:wordpress-theme/agent-neo@' . $theme_version,
	'name'        => 'AGENT NEO',
	'version'     => $theme_version,
	'description' => 'AI エージェントが安全に参照できる WordPress FSE テーマ基盤',
	'supplier'    => $supplier,
	'licenses'    => $gpl_license,
	'purl'        => 'pkg:wordpress-theme/agent-neo@' . $theme_version,
	'hashes'      => [ sbom_component_hash( $theme_dir ) ],
	'properties'  => [
		[ 'name' => 'wordpress:text-domain',  'value' => 'agent-neo' ],
		[ 'name' => 'wordpress:requires-php', 'value' => '8.1' ],
		[ 'name' => 'wordpress:requires-at-least', 'value' => '6.6' ],
	],
];

// ---- WordPress プラットフォーム（注記コンポーネント）----
$sbom['components'][] = [
	'type'        => 'framework',
	'bom-ref'     => 'pkg:generic/wordpress@6.9-or-later',
	'name'        => 'WordPress',
	'version'     => '>=6.9',
	'description' => 'WordPress CMS プラットフォーム（実行基盤）',
	'supplier'    => [
		'name' => 'WordPress Foundation',
		'url'  => [ 'https://wordpress.org/' ],
	],
	'licenses'    => $gpl_license,
	'purl'        => 'pkg:generic/wordpress@6.9',
	'properties'  => [
		[ 'name' => 'sbom:role', 'value' => 'platform-runtime' ],
		[ 'name' => 'sbom:note', 'value' => 'AGENT NEO の実行基盤。配布物には含まれない。' ],
	],
];

// ---- PHP ランタイム（注記コンポーネント）----
$sbom['components'][] = [
	'type'        => 'framework',
	'bom-ref'     => 'pkg:generic/php@8.1-or-later',
	'name'        => 'PHP',
	'version'     => '>=8.1',
	'description' => 'PHP ランタイム（実行基盤）',
	'supplier'    => [
		'name' => 'The PHP Group',
		'url'  => [ 'https://www.php.net/' ],
	],
	'licenses'    => [
		[
			'license' => [
				'id'  => 'PHP-3.01',
				'url' => 'https://www.php.net/license/3_01.txt',
			],
		],
	],
	'purl'        => 'pkg:generic/php@8.1',
	'properties'  => [
		[ 'name' => 'sbom:role', 'value' => 'platform-runtime' ],
		[ 'name' => 'sbom:note', 'value' => 'AGENT NEO の実行基盤。配布物には含まれない。' ],
	],
];

// ============================================================
// 4. dependencies セクション
// 外部サードパーティ依存ゼロ（first-party のみ）を明示する。
// ============================================================

// メタパッケージ（配布物全体）が 3 コンポーネントを包含する
$sbom['dependencies'][] = [
	'ref'      => 'pkg:generic/agent-neo-distribution@' . $core_version,
	'dependsOn' => [
		'pkg:wordpress-plugin/agent-neo-core@'  . $core_version,
		'pkg:wordpress-plugin/agent-neo-embed@' . $embed_version,
		'pkg:wordpress-theme/agent-neo@'         . $theme_version,
	],
	'properties' => [
		[ 'name' => 'sbom:external-dependencies', 'value' => 'none' ],
		[ 'name' => 'sbom:dependency-note', 'value' => '外部サードパーティ依存ゼロ。composer.json / package.json / vendor/ / node_modules/ は存在しない。全て first-party コード。' ],
	],
];

// 各 first-party コンポーネントの外部依存は空（self のみ）
foreach (
	[
		'pkg:wordpress-plugin/agent-neo-core@'  . $core_version,
		'pkg:wordpress-plugin/agent-neo-embed@' . $embed_version,
		'pkg:wordpress-theme/agent-neo@'         . $theme_version,
	] as $ref
) {
	$sbom['dependencies'][] = [
		'ref'       => $ref,
		'dependsOn' => [], // 外部サードパーティ依存ゼロ
	];
}

// ============================================================
// 5. 出力
// ============================================================

$json_output = json_encode( $sbom, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
if ( $json_output === false ) {
	fwrite( STDERR, '[ERROR] JSON エンコード失敗: ' . json_last_error_msg() . PHP_EOL );
	exit( 1 );
}

$output_path = $repo_root . '/sbom.cdx.json';
if ( file_put_contents( $output_path, $json_output . PHP_EOL ) === false ) {
	fwrite( STDERR, '[ERROR] ファイル書き込み失敗: ' . $output_path . PHP_EOL );
	exit( 1 );
}

echo '[OK] SBOM を生成しました: ' . $output_path . PHP_EOL;
echo '     コンポーネント数: ' . count( $sbom['components'] ) . ' 件（first-party 3件 + platform/runtime 2件）' . PHP_EOL;

// ============================================================
// ユーティリティ関数
// ============================================================

/**
 * 文字列シードから決定的 UUID v5（SHA-1 ベース / namespace OID）を生成する。
 *
 * @param string $name シード文字列。
 * @return string UUID 形式文字列（例: xxxxxxxx-xxxx-5xxx-yxxx-xxxxxxxxxxxx）。
 */
function sbom_generate_deterministic_uuid( string $name ): string {
	// RFC 4122 UUID v5 / namespace: OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8'
	$namespace_hex = '6ba7b8129dad11d180b400c04fd430c8';
	$namespace_bin = hex2bin( $namespace_hex );

	// SHA-1 を生成し 16 バイトのバイナリに切り詰める
	$hash_bin = substr( sha1( $namespace_bin . $name, true ), 0, 16 );

	// バイト配列として操作
	$bytes = array_values( unpack( 'C*', $hash_bin ) );

	// version = 5: バイト[6] の上位4ビットを 0101 にセット
	$bytes[6] = ( $bytes[6] & 0x0F ) | 0x50;

	// variant = RFC4122: バイト[8] の上位2ビットを 10 にセット
	$bytes[8] = ( $bytes[8] & 0x3F ) | 0x80;

	// 16進文字列に変換
	$hex = implode( '', array_map( fn( $b ) => sprintf( '%02x', $b ), $bytes ) );

	// UUID 形式（8-4-4-4-12）に整形
	return sprintf(
		'%s-%s-%s-%s-%s',
		substr( $hex, 0, 8 ),
		substr( $hex, 8, 4 ),
		substr( $hex, 12, 4 ),
		substr( $hex, 16, 4 ),
		substr( $hex, 20, 12 )
	);
}

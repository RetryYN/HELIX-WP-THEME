<?php
/**
 * TC-029: lp-blueprint セクション整合 単体テスト。
 *
 * 受入条件（L3-test-plan.md §3.2 TC-029 / carry-012）:
 *   lp-blueprint のセクション定義が api-catalog および L2 設計と一致すること（12セクション整合確認）。
 *
 * 12 セクション（L2-design.md §Blueprint_Controller / SECTION_KINDS 定数）:
 *   hero / problem / agitation / solution / feature / benefit /
 *   use-case / proof / comparison / pricing / faq / final-cta
 *
 * 検証観点:
 *   1. themes/agent-neo-theme/patterns/ に lp-* ファイルが 12 本存在すること。
 *   2. 各 lp-*.php のファイル名が上記 12 スラッグのいずれかに対応すること。
 *   3. Blueprint_Controller::SECTION_KINDS 定数（private）が 12 種類であること。
 *   4. パターンファイルの slug 集合と SECTION_KINDS が一致すること。
 *
 * 実装方針:
 *   - WP コア不要。ファイルシステムの静的検査と ReflectionClass で実施。
 *   - Brain Monkey は最小限（ABSPATH 依存のファイルロードのみ）。
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use ReflectionClass;

/**
 * TC-029: lp-blueprint 12 セクション整合検証。
 */
class TC029_LpBlueprintSectionTest extends TestCase {

	/** @var string テーマ patterns ディレクトリパス */
	private string $patterns_dir;

	/** 期待する 12 セクション slug（L2-design.md §Blueprint_Controller 凍結仕様） */
	private const EXPECTED_SECTION_KINDS = array(
		'hero',
		'problem',
		'agitation',
		'solution',
		'feature',
		'benefit',
		'use-case',
		'proof',
		'comparison',
		'pricing',
		'faq',
		'final-cta',
	);

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		Functions\stubs( array(
			'__'         => fn( $text, $domain = 'default' ) => $text,
			'esc_html__' => fn( $text, $domain = 'default' ) => $text,
		) );

		$this->patterns_dir = dirname( AGENT_NEO_CORE_DIR, 2 ) . '/themes/agent-neo-theme/patterns/';
	}

	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// ヘルパー
	// ------------------------------------------------------------------

	/**
	 * patterns ディレクトリから lp-* ファイルの slug 一覧を取得する。
	 *
	 * @return array<string> lp-{slug}.php から抽出した slug 一覧。
	 */
	private function get_lp_pattern_slugs(): array {
		if ( ! is_dir( $this->patterns_dir ) ) {
			return array();
		}

		$slugs = array();
		foreach ( glob( $this->patterns_dir . 'lp-*.php' ) as $file ) {
			$basename = basename( $file, '.php' );
			// "lp-{slug}" → slug を抽出。
			$slug   = substr( $basename, 3 ); // "lp-" の 3 文字を除去。
			$slugs[] = $slug;
		}

		return $slugs;
	}

	// ------------------------------------------------------------------
	// TC-029-01: patterns ディレクトリが存在すること
	// ------------------------------------------------------------------

	/**
	 * TC-029-01: themes/agent-neo-theme/patterns/ ディレクトリが存在すること。
	 *
	 * @return void
	 */
	public function test_tc029_patterns_directory_exists(): void {
		$this->assertDirectoryExists(
			$this->patterns_dir,
			'themes/agent-neo-theme/patterns/ ディレクトリが存在すること'
		);
	}

	// ------------------------------------------------------------------
	// TC-029-02: lp-* ファイルが 12 本存在すること
	// ------------------------------------------------------------------

	/**
	 * TC-029-02: lp-*.php パターンファイルが 12 本存在すること。
	 *
	 * @return void
	 */
	public function test_tc029_exactly_12_lp_pattern_files_exist(): void {
		$slugs = $this->get_lp_pattern_slugs();

		$this->assertCount(
			12,
			$slugs,
			'lp-*.php パターンファイルが 12 本存在すること。実際: ' . implode( ', ', $slugs )
		);
	}

	// ------------------------------------------------------------------
	// TC-029-03: 各 lp-*.php が期待する 12 スラッグと一致すること
	// ------------------------------------------------------------------

	/**
	 * TC-029-03: lp-*.php のファイル名 slug が 12 セクション凍結仕様と一致すること。
	 *
	 * @return void
	 */
	public function test_tc029_lp_pattern_slugs_match_expected_section_kinds(): void {
		$actual_slugs   = $this->get_lp_pattern_slugs();
		$expected_slugs = self::EXPECTED_SECTION_KINDS;

		sort( $actual_slugs );
		sort( $expected_slugs );

		$this->assertSame(
			$expected_slugs,
			$actual_slugs,
			'lp-*.php のスラッグ集合が 12 セクション凍結仕様と完全一致すること'
		);
	}

	/**
	 * TC-029-03b: 各期待スラッグに対応する lp-{slug}.php が存在すること（個別確認）。
	 *
	 * @return void
	 */
	public function test_tc029_each_expected_section_has_pattern_file(): void {
		foreach ( self::EXPECTED_SECTION_KINDS as $slug ) {
			$file = $this->patterns_dir . 'lp-' . $slug . '.php';
			$this->assertFileExists(
				$file,
				"lp-{$slug}.php が存在すること"
			);
		}
	}

	// ------------------------------------------------------------------
	// TC-029-04: Blueprint_Controller の SECTION_KINDS 定数が 12 種類であること
	// ------------------------------------------------------------------

	/**
	 * TC-029-04: Blueprint_Controller の private SECTION_KINDS が期待する 12 種類を持つこと。
	 *
	 * @return void
	 */
	public function test_tc029_blueprint_controller_section_kinds_const_has_12_items(): void {
		// Blueprint_Controller のロードに必要な依存クラスをロードする。
		$files = array(
			AGENT_NEO_CORE_DIR . 'inc/rest/class-rest-controller-base.php',
			AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php',
			AGENT_NEO_CORE_DIR . 'inc/rest/class-blueprint-controller.php',
		);

		// WP 関数スタブ追加（クラスロード時に必要なもの）。
		Functions\stubs( array(
			'add_action'   => fn( ...$args ) => null,
			'add_filter'   => fn( ...$args ) => null,
		) );

		foreach ( $files as $file ) {
			if ( file_exists( $file ) && ! class_exists( 'Agent_Neo_Core_Blueprint_Controller' ) ) {
				require_once $file;
			}
		}

		if ( ! class_exists( 'Agent_Neo_Core_Blueprint_Controller' ) ) {
			$this->markTestSkipped( 'Agent_Neo_Core_Blueprint_Controller が見つからない' );
		}

		$ref  = new ReflectionClass( 'Agent_Neo_Core_Blueprint_Controller' );
		$const = $ref->getReflectionConstant( 'SECTION_KINDS' );

		$this->assertNotFalse( $const, 'SECTION_KINDS 定数が存在すること' );
		$kinds = $const->getValue();
		$this->assertIsArray( $kinds );
		$this->assertCount( 12, $kinds, 'SECTION_KINDS は 12 種類であること' );
	}

	/**
	 * TC-029-05: Blueprint_Controller の SECTION_KINDS が期待スラッグと一致すること。
	 *
	 * @return void
	 */
	public function test_tc029_blueprint_controller_section_kinds_match_spec(): void {
		if ( ! class_exists( 'Agent_Neo_Core_Blueprint_Controller' ) ) {
			$this->markTestSkipped( 'Agent_Neo_Core_Blueprint_Controller が見つからない' );
		}

		$ref   = new ReflectionClass( 'Agent_Neo_Core_Blueprint_Controller' );
		$const = $ref->getReflectionConstant( 'SECTION_KINDS' );

		if ( false === $const ) {
			$this->markTestSkipped( 'SECTION_KINDS 定数が見つからない' );
		}

		$kinds = $const->getValue();
		sort( $kinds );

		$expected = self::EXPECTED_SECTION_KINDS;
		sort( $expected );

		$this->assertSame(
			$expected,
			$kinds,
			'Blueprint_Controller::SECTION_KINDS が 12 セクション凍結仕様と完全一致すること'
		);
	}

	// ------------------------------------------------------------------
	// TC-029-06: パターンファイルの slug と SECTION_KINDS が整合すること
	// ------------------------------------------------------------------

	/**
	 * TC-029-06: lp-*.php のスラッグ集合と Blueprint_Controller::SECTION_KINDS が完全一致すること。
	 *
	 * api-catalog との整合確認:
	 *   パターンファイル（テーマ側）と Blueprint_Controller 定数（コア側）の両方が
	 *   同じ 12 種類を定義していること。
	 *
	 * @return void
	 */
	public function test_tc029_pattern_files_and_controller_const_are_consistent(): void {
		if ( ! class_exists( 'Agent_Neo_Core_Blueprint_Controller' ) ) {
			$this->markTestSkipped( 'Agent_Neo_Core_Blueprint_Controller が見つからない' );
		}

		$ref   = new ReflectionClass( 'Agent_Neo_Core_Blueprint_Controller' );
		$const = $ref->getReflectionConstant( 'SECTION_KINDS' );

		if ( false === $const ) {
			$this->markTestSkipped( 'SECTION_KINDS 定数が見つからない' );
		}

		$kinds         = $const->getValue();
		$pattern_slugs = $this->get_lp_pattern_slugs();

		sort( $kinds );
		sort( $pattern_slugs );

		$this->assertSame(
			$kinds,
			$pattern_slugs,
			'パターンファイルの slug 集合と SECTION_KINDS が完全に一致すること'
		);
	}
}

<?php
/**
 * CTA 計装ユニットテスト。
 *
 * TC-CTA-001〜TC-CTA-006: agent_neo_core_extract_cta_id_from_class() の許可/不正ケース。
 * TC-CTA-101〜TC-CTA-104: export 集計ロジック（queue→events 整形、after フィルタ、limit）。
 *
 * WP 非依存の純 helper は Brain Monkey なしで検証可能。
 * export の集計ロジックは get_option / get_transient をスタブして検証する。
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * CTA 計装 + export 集計ユニットテスト。
 */
class TC_CTAInstrumenterTest extends TestCase {

	/**
	 * Brain Monkey を初期化する。
	 *
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		// class-cta-instrumenter.php をロードする（ABSPATH が定義済み）。
		if ( ! function_exists( 'agent_neo_core_extract_cta_id_from_class' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}
	}

	/**
	 * Brain Monkey を後片付けする。
	 *
	 * @return void
	 */
	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// TC-CTA-001: 正常系 — クラス列に an-cta--<id> が含まれる
	// ------------------------------------------------------------------

	/**
	 * an-cta an-cta--article_cta → 'article_cta' が返ること。
	 *
	 * @return void
	 */
	public function test_extract_cta_id_full_class_string(): void {
		$result = agent_neo_core_extract_cta_id_from_class( 'an-cta an-cta--article_cta' );
		$this->assertSame( 'article_cta', $result );
	}

	// ------------------------------------------------------------------
	// TC-CTA-002: 正常系 — an-cta-- 単独
	// ------------------------------------------------------------------

	/**
	 * an-cta--lp_final → 'lp_final' が返ること。
	 *
	 * @return void
	 */
	public function test_extract_cta_id_single_class(): void {
		$result = agent_neo_core_extract_cta_id_from_class( 'an-cta--lp_final' );
		$this->assertSame( 'lp_final', $result );
	}

	// ------------------------------------------------------------------
	// TC-CTA-003: ハイフンを含む ID
	// ------------------------------------------------------------------

	/**
	 * an-cta--home-hero-primary → 'home-hero-primary' が返ること。
	 *
	 * @return void
	 */
	public function test_extract_cta_id_hyphenated(): void {
		$result = agent_neo_core_extract_cta_id_from_class( 'an-cta--home-hero-primary' );
		$this->assertSame( 'home-hero-primary', $result );
	}

	// ------------------------------------------------------------------
	// TC-CTA-004: 空文字列 → '' を返す
	// ------------------------------------------------------------------

	/**
	 * 空文字列を渡すと '' が返ること。
	 *
	 * @return void
	 */
	public function test_extract_cta_id_empty_string(): void {
		$result = agent_neo_core_extract_cta_id_from_class( '' );
		$this->assertSame( '', $result );
	}

	// ------------------------------------------------------------------
	// TC-CTA-005: 対象クラスなし → '' を返す
	// ------------------------------------------------------------------

	/**
	 * an-cta-- を含まないクラス文字列は '' を返すこと。
	 *
	 * @return void
	 */
	public function test_extract_cta_id_no_match(): void {
		$result = agent_neo_core_extract_cta_id_from_class( 'wp-block-group an-article-cta' );
		$this->assertSame( '', $result );
	}

	// ------------------------------------------------------------------
	// TC-CTA-006: 不正文字（スペース含む ID）→ 最初の有効部分を返す or ''
	// ------------------------------------------------------------------

	/**
	 * an-cta-- の直後にスペースが続く場合は '' を返すこと（\b でマッチしない）。
	 *
	 * @return void
	 */
	public function test_extract_cta_id_invalid_characters(): void {
		// スペースが入る形（an-cta-- の後にスペース）→ 正規表現にマッチしない。
		$result = agent_neo_core_extract_cta_id_from_class( 'an-cta-- invalid' );
		$this->assertSame( '', $result );
	}

	// ------------------------------------------------------------------
	// TC-CTA-010: instrument_block() 本経路 — アフィリエイトリンク計装
	//
	// 回帰テスト: 修正前は self::extract_cta_id_from_class() 呼び出しで
	// PHP Fatal Error が発生し、全フロントページが HTTP 500 になっていた。
	// instrument_block() を通じて (1) fatal が出ない (2) data-agent-neo-affiliate
	// と data-cta-id が付与される ことを確認する。
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-010: an-cta--<id> クラスを持つ <a> を含む block_content を
	 * instrument_block() に渡すと data-agent-neo-affiliate / data-cta-id が付与されること。
	 *
	 * WP_HTML_Tag_Processor スタブが wp-stubs.php に存在するため本経路が通る。
	 *
	 * @return void
	 */
	public function test_instrument_block_affiliate_link_adds_data_attributes(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<p><a class="an-cta an-cta--article_cta" href="https://example.com">リンク</a></p>';
		$parsed_block  = array( 'blockName' => 'core/paragraph', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString( 'data-agent-neo-affiliate', $result, 'data-agent-neo-affiliate 属性が付与されること' );
		$this->assertStringContainsString( 'data-cta-id="article_cta"', $result, 'data-cta-id="article_cta" が付与されること' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-011: instrument_block() 本経路 — banner 系 div 計装
	//
	// 回帰テスト: 122行目の self::extract_cta_id_from_class() 呼び出し経路も
	// agent_neo_core_extract_cta_id_from_class() に修正されていることを確認する。
	// an-article-cta の div を渡し、data-agent-neo-ad が付与されること。
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-011: an-article-cta クラスを持つ <div> を含む block_content を
	 * instrument_block() に渡すと data-agent-neo-ad / data-cta-id が付与されること。
	 *
	 * @return void
	 */
	public function test_instrument_block_banner_div_adds_ad_attributes(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<div class="wp-block-group an-article-cta"><p>バナー</p></div>';
		$parsed_block  = array( 'blockName' => 'core/group', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString( 'data-agent-neo-ad', $result, 'data-agent-neo-ad 属性が付与されること' );
		$this->assertStringContainsString( 'data-cta-id=', $result, 'data-cta-id 属性が付与されること' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-020: WP 標準ボタン構造（div ラッパ） — 内側 <a> に計装されること
	//
	// 実テーマの wp:button 構造:
	//   <div class="wp-block-button an-cta an-cta--hero_primary">
	//     <a class="wp-block-button__link" href="...">CTA</a>
	//   </div>
	// この場合、an-cta--<id> は div に付き <a> には付かない。
	// 修正後は内側の <a> に data-agent-neo-affiliate と data-cta-id が付くこと。
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-020: WP 標準 wp:button 構造で div ラッパに an-cta--<id> が付いている場合、
	 * 内側の <a> に data-agent-neo-affiliate / data-cta-id="hero_primary" が付与されること。
	 *
	 * @return void
	 */
	public function test_instrument_block_wp_button_wrapper_div_instruments_inner_a(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<div class="wp-block-button an-cta an-cta--hero_primary">'
			. '<a class="wp-block-button__link" href="https://example.com">導入をはじめる</a>'
			. '</div>';
		$parsed_block  = array( 'blockName' => 'core/button', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString(
			'data-agent-neo-affiliate',
			$result,
			'内側 <a> に data-agent-neo-affiliate が付与されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="hero_primary"',
			$result,
			'内側 <a> に data-cta-id="hero_primary" が付与されること'
		);

		// <a> の class に an-cta-- は付いていない（div に付いている）→ 計装がラッパ経由で行われていること。
		$this->assertStringNotContainsString(
			'wp-block-button__link" data-agent-neo-affiliate',
			$result,
			'<a> 自身の class 列は改変されていないこと'
		);
	}

	// ------------------------------------------------------------------
	// TC-CTA-021: <a> 直付けケースの後方互換
	//
	// テキストリンク型 CTA: <a class="an-cta an-cta--article_cta" href="...">
	// 修正後も <a> 自身への計装が機能すること。
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-021: <a> 自身に an-cta--<id> が付いている場合（テキストリンク型 CTA）、
	 * 引き続き data-agent-neo-affiliate / data-cta-id が付与されること（後方互換）。
	 *
	 * @return void
	 */
	public function test_instrument_block_a_direct_class_backward_compat(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<p><a class="an-cta an-cta--text_link_cta" href="https://example.com">テキストCTA</a></p>';
		$parsed_block  = array( 'blockName' => 'core/paragraph', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString(
			'data-agent-neo-affiliate',
			$result,
			'<a> 直付けケースでも data-agent-neo-affiliate が付与されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="text_link_cta"',
			$result,
			'<a> 直付けケースでも data-cta-id="text_link_cta" が付与されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-CTA-022: div ラッパに an-cta-- があるが内側に <a> が無いケース
	//
	// fatal / 誤付与しないこと（fail-safe）。
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-022: div ラッパに an-cta--<id> が付いているが内側に <a> が存在しない場合、
	 * fatal が発生せず、出力 HTML に data-agent-neo-affiliate が付与されないこと。
	 *
	 * @return void
	 */
	public function test_instrument_block_wrapper_div_without_inner_a_is_safe(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		// <a> のない div ラッパ（スパン等）。
		$block_content = '<div class="wp-block-button an-cta an-cta--no_link"><span>テキスト</span></div>';
		$parsed_block  = array( 'blockName' => 'core/button', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		// fatal / 例外なし（test 自体が通れば OK）。
		$this->assertStringNotContainsString(
			'data-agent-neo-affiliate',
			$result,
			'内側に <a> が無い場合は data-agent-neo-affiliate が付与されないこと'
		);
		// HTML は改変されないこと（元文字列を返す）。
		$this->assertStringContainsString( 'an-cta--no_link', $result, '元の class 属性が維持されること' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-030: <p> ラッパ CTA 計装（gw_* 型 / home-gateway.php 由来）
	//
	// wp:paragraph の <p class="an-cta an-cta--x"><a href="#">...</a></p> 構造で
	// 内側 <a> に data-cta-id が付くこと（旧実装では漏れていた）。
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-030: <p> ラッパ CTA（gateway 型）で内側 <a> に計装されること。
	 *
	 * @return void
	 */
	public function test_instrument_block_p_wrapper_cta_instruments_inner_a(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<p class="an-cta an-cta--gw_affiliate"><a href="#">記事運用を見る →</a></p>';
		$parsed_block  = array( 'blockName' => 'core/paragraph', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString(
			'data-agent-neo-affiliate',
			$result,
			'<p> ラッパ CTA: 内側 <a> に data-agent-neo-affiliate が付与されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="gw_affiliate"',
			$result,
			'<p> ラッパ CTA: 内側 <a> に data-cta-id="gw_affiliate" が付与されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-CTA-031: div ラッパ型（wp:button）の計装（再確認）
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-031: <div> ラッパ CTA（button 型）で内側 <a> に計装されること。
	 *
	 * @return void
	 */
	public function test_instrument_block_div_wrapper_button_type(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<div class="wp-block-button an-cta an-cta--hero_primary">'
			. '<a class="wp-block-button__link" href="https://example.com">導入をはじめる</a>'
			. '</div>';
		$parsed_block  = array( 'blockName' => 'core/button', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString( 'data-agent-neo-affiliate', $result );
		$this->assertStringContainsString( 'data-cta-id="hero_primary"', $result );
	}

	// ------------------------------------------------------------------
	// TC-CTA-032: <a> 直付け型（後方互換）
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-032: <a> 自身に an-cta--<id> がある場合（直付け型）に計装されること。
	 *
	 * @return void
	 */
	public function test_instrument_block_a_direct_type(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<a class="an-cta an-cta--direct_link" href="https://example.com">直付けCTA</a>';
		$parsed_block  = array( 'blockName' => 'core/paragraph', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString( 'data-agent-neo-affiliate', $result );
		$this->assertStringContainsString( 'data-cta-id="direct_link"', $result );
	}

	// ------------------------------------------------------------------
	// TC-CTA-033: 同一 cta_id のラッパが 2 つ → 両方の内側 <a> が計装されること（M-02 解消）
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-033: 同一 cta_id のラッパが 2 つある場合、両方の内側 <a> が計装されること。
	 *
	 * @return void
	 */
	public function test_instrument_block_duplicate_cta_id_both_instrumented(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		// 同じ cta_id を持つラッパが 2 つ。
		$block_content = '<p class="an-cta an-cta--duplicate"><a href="https://a.example.com">リンク1</a></p>'
			. '<p class="an-cta an-cta--duplicate"><a href="https://b.example.com">リンク2</a></p>';
		$parsed_block  = array( 'blockName' => 'core/paragraph', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		// data-cta-id="duplicate" が 2 箇所に付くこと。
		$count = substr_count( $result, 'data-cta-id="duplicate"' );
		$this->assertSame( 2, $count, '同一 cta_id のラッパが 2 つある場合、両方の内側 <a> が計装されること' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-034: ラッパ内に <a> が複数 → 最初の <a> のみ計装されること
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-034: ラッパ内に <a> が複数ある場合、最初の <a> のみ計装されること。
	 *
	 * @return void
	 */
	public function test_instrument_block_multiple_a_inside_wrapper_only_first(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		// ラッパ内に <a> が 2 つ（実際にはまれだが fail-safe として検証）。
		$block_content = '<div class="an-cta an-cta--multi">'
			. '<a href="https://first.example.com">1番目</a>'
			. '<a href="https://second.example.com">2番目</a>'
			. '</div>';
		$parsed_block  = array( 'blockName' => 'core/group', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		// 最初の <a> だけに計装（pending は最初の <a> で消費されるため）。
		$count = substr_count( $result, 'data-cta-id="multi"' );
		$this->assertSame( 1, $count, 'ラッパ内に <a> が複数ある場合、最初の <a> のみ計装されること' );

		// 2番目の <a> には data-agent-neo-affiliate がないこと。
		$this->assertStringContainsString( '<a href="https://second.example.com">2番目</a>', $result, '2番目の <a> は改変されないこと' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-035: class名に "data-agent-neo-affiliate" 文字列を含むが属性は無いケース
	//             → 計装される（M-01: strpos 部分一致で誤スキップしない）
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-035: class 名に "data-agent-neo-affiliate" 文字列を含むが
	 * 属性として付いていない場合、計装されること（M-01 解消確認）。
	 *
	 * @return void
	 */
	public function test_instrument_block_class_contains_attribute_name_string_still_instruments(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		// class に "data-agent-neo-affiliate" という文字列を含むが属性ではない。
		// 旧実装では strpos 部分一致でスキップしてしまっていた。
		$block_content = '<a class="an-cta an-cta--tricky data-agent-neo-affiliate-like" href="https://example.com">トリッキー</a>';
		$parsed_block  = array( 'blockName' => 'core/paragraph', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		// 属性としては存在しないので計装されるべき。
		$this->assertStringContainsString(
			'data-cta-id="tricky"',
			$result,
			'class 名に data-agent-neo-affiliate 文字列を含んでも属性がなければ計装されること（M-01）'
		);
	}

	// ------------------------------------------------------------------
	// TC-CTA-036: 既に data-agent-neo-affiliate 属性がある <a> → スキップ（二重付与防止）
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-036: 既に data-agent-neo-affiliate 属性がある <a> はスキップされること。
	 *
	 * @return void
	 */
	public function test_instrument_block_already_instrumented_a_is_skipped(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<p class="an-cta an-cta--existing">'
			. '<a href="https://example.com" data-agent-neo-affiliate="" data-cta-id="old_id" data-variant-id="default">既計装</a>'
			. '</p>';
		$parsed_block  = array( 'blockName' => 'core/paragraph', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		// data-cta-id は old_id のままであること（上書きされていないこと）。
		$this->assertStringContainsString( 'data-cta-id="old_id"', $result, '既存の data-cta-id が保持されること' );
		$this->assertStringNotContainsString( 'data-cta-id="existing"', $result, '二重付与されないこと' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-037: ラッパに an-cta-- があるが内側に <a> が無い → fatal なし・誤付与なし
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-037: ラッパに an-cta-- が付いているが内側に <a> が存在しない場合、
	 * fatal が発生せず data-agent-neo-affiliate が付与されないこと。
	 *
	 * @return void
	 */
	public function test_instrument_block_wrapper_without_a_is_safe(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<div class="an-cta an-cta--no_link"><span>リンクなし</span></div>';
		$parsed_block  = array( 'blockName' => 'core/group', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringNotContainsString( 'data-agent-neo-affiliate', $result, '内側に <a> が無い場合は計装されないこと' );
		$this->assertStringContainsString( 'an-cta--no_link', $result, '元の class が維持されること' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-038: 不正 cta_id（特殊文字）→ extract 側で弾かれる
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-038: cta_id に特殊文字が含まれる場合、extract_cta_id_from_class() が
	 * 空文字を返すため計装されないこと。
	 *
	 * @return void
	 */
	public function test_instrument_block_invalid_cta_id_not_instrumented(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		// an-cta-- の直後にスペース = \b で境界マッチしない → extract 側で '' を返す。
		$block_content = '<a class="an-cta an-cta-- invalid" href="https://example.com">不正ID</a>';
		$parsed_block  = array( 'blockName' => 'core/paragraph', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringNotContainsString( 'data-agent-neo-affiliate', $result, '不正 cta_id では計装されないこと' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-012: instrument_block() 本経路 — an-cta--<id> 付き banner div
	//
	// banner div が an-cta--<id> クラスも持つ場合、CTA ID が抽出されること（122行目経路）。
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-012: an-article-cta かつ an-cta--<id> を持つ div を instrument_block() に渡すと
	 * data-cta-id="<id>" が付与されること。
	 *
	 * @return void
	 */
	public function test_instrument_block_banner_with_cta_id_class(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<div class="wp-block-group an-article-cta an-cta--my-banner"><p>バナー</p></div>';
		$parsed_block  = array( 'blockName' => 'core/group', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString( 'data-agent-neo-ad', $result, 'data-agent-neo-ad 属性が付与されること' );
		$this->assertStringContainsString( 'data-cta-id="my-banner"', $result, 'an-cta--my-banner から ID が抽出されること' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-039: ネスト誤爆防止 — 外側 an-cta-- が内側 an-cta-- で上書きされること（I-1）
	//
	// <div class="an-cta--outer"><div class="an-cta--inner"><a> の構造では
	// 最も内側の an-cta--inner が優先されること。
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-039: 外側 an-cta--outer < 内側 an-cta--inner の優先順位確認（I-1）。
	 *
	 * @return void
	 */
	public function test_instrument_block_nested_cta_inner_wins(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		// 外側 an-cta--outer 内に 内側 an-cta--inner があり、直後に <a>。
		// pending は outer → inner で上書きされ、<a> には inner が付くこと。
		$block_content = '<div class="an-cta an-cta--outer">'
			. '<div class="an-cta an-cta--inner">'
			. '<a href="https://example.com">ネスト</a>'
			. '</div>'
			. '</div>';
		$parsed_block  = array( 'blockName' => 'core/group', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString(
			'data-cta-id="inner"',
			$result,
			'ネスト時は内側 an-cta--inner が優先されること（I-1）'
		);
		$this->assertStringNotContainsString(
			'data-cta-id="outer"',
			$result,
			'外側 an-cta--outer は誤爆しないこと'
		);
	}

	// ------------------------------------------------------------------
	// TC-CTA-040: href に & を含む <a> — data 属性が付与されること（I-5）
	//
	// スタブ注意: DOMDocument は & を &amp; に正規化するため、
	// get_updated_html() の出力では & が &amp; になる場合がある。
	// ここでは data-cta-id の付与を確認し、href 変換は許容する。
	// 実WP での挙動は WP_HTML_Tag_Processor が & を保持する（curl で確認済み）。
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-040: href に & を含む <a> にも data 属性が付与されること。
	 *
	 * スタブの DOMDocument は & を &amp; に変換する場合があるため、
	 * href 値の変換は許容し data-cta-id の付与のみ確認する。
	 * 実WP での & 保持は curl 実機確認で担保。
	 *
	 * @return void
	 */
	public function test_instrument_block_href_with_ampersand(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<a class="an-cta an-cta--amp_test" href="https://example.com/?a=1&amp;b=2">リンク</a>';
		$parsed_block  = array( 'blockName' => 'core/paragraph', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		// data-cta-id が付与されること（href の & 変換はスタブの許容範囲）。
		$this->assertStringContainsString(
			'data-cta-id="amp_test"',
			$result,
			'href に &amp; を含む <a> にも data-cta-id が付与されること'
		);
		$this->assertStringContainsString( 'data-agent-neo-affiliate', $result );
	}

	// ------------------------------------------------------------------
	// TC-CTA-041: ラッパとリンクが混在する複合構造 — それぞれ正しく計装されること（I-5）
	// ------------------------------------------------------------------

	/**
	 * TC-CTA-041: p ラッパ + div ラッパ + <a> 直付けが同一 HTML に混在する場合。
	 *
	 * @return void
	 */
	public function test_instrument_block_mixed_wrapper_types(): void {
		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$block_content = '<p class="an-cta an-cta--gw_affiliate"><a href="#">p型</a></p>'
			. '<div class="wp-block-button an-cta--hero_primary"><a class="wp-block-button__link" href="#">div型</a></div>'
			. '<a class="an-cta an-cta--direct_x" href="#">直付け</a>';
		$parsed_block  = array( 'blockName' => 'core/group', 'attrs' => array() );

		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		$result       = $instrumenter->instrument_block( $block_content, $parsed_block );

		$this->assertStringContainsString( 'data-cta-id="gw_affiliate"', $result, 'p ラッパ型が計装されること' );
		$this->assertStringContainsString( 'data-cta-id="hero_primary"', $result, 'div ラッパ型が計装されること' );
		$this->assertStringContainsString( 'data-cta-id="direct_x"', $result, '直付け型が計装されること' );
	}

	// ------------------------------------------------------------------
	// TC-CTA-101: export 集計 — queue から events を整形できること
	// ------------------------------------------------------------------

	/**
	 * event_id 配列から正しく events フォーマットに変換されること。
	 *
	 * @return void
	 */
	public function test_export_formats_event_from_queue(): void {
		if ( ! class_exists( 'Agent_Neo_Core_Tracking_Export_Controller' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-tracking-export-controller.php';
		}

		// インメモリ transient ストア。
		$transient_store = array();

		$event_id = 'evt_abc123';
		$event    = array(
			'event_id'    => $event_id,
			'event_type'  => 'ad_impression',
			'section_id'  => 'ad',
			'cta_id'      => 'article_cta',
			'variant_id'  => 'default',
			'accepted_at' => '2026-06-26T12:00:00+00:00',
			'article_id'  => 'https://example.com/article',
			'metadata'    => array( 'element' => 'ad' ),
		);

		$transient_key                   = 'agent_neo_tracking_event_' . substr( hash( 'sha256', $event_id ), 0, 40 );
		$transient_store[ $transient_key ] = $event;

		// load_events を Reflection で直接呼ぶ。get_transient のみスタブする。
		Functions\expect( 'get_transient' )
			->andReturnUsing( static function ( string $key ) use ( &$transient_store ) {
				return $transient_store[ $key ] ?? false;
			} );

		// export_events を呼ぶためにリフレクションを使う。
		$ref = new \ReflectionClass( 'Agent_Neo_Core_Tracking_Export_Controller' );

		// load_events を直接テストする。
		$load = $ref->getMethod( 'load_events' );
		$load->setAccessible( true );

		$controller = $ref->newInstanceWithoutConstructor();
		$result     = $load->invoke( $controller, array( $event_id ), '', 100, array(), 0 );

		$this->assertCount( 1, $result, 'イベントが1件返ること' );
		$this->assertSame( 'evt_abc123', $result[0]['event_id'] );
		$this->assertSame( 'ad_impression', $result[0]['event_type'] );
		$this->assertSame( '2026-06-26T12:00:00+00:00', $result[0]['occurred_at'] );
		$this->assertSame( 'https://example.com/article', $result[0]['canonical_url'] );
	}

	// ------------------------------------------------------------------
	// TC-CTA-102: after フィルタ — 指定 event_id より古い側のみ返すこと
	// ------------------------------------------------------------------

	/**
	 * after カーソル指定で正しくスライスされること。
	 *
	 * @return void
	 */
	public function test_export_after_filter_returns_older_events(): void {
		if ( ! class_exists( 'Agent_Neo_Core_Tracking_Export_Controller' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-tracking-export-controller.php';
		}

		// queue: [evt_new, evt_mid, evt_old]（新着 prepend）
		// after=evt_mid → evt_old のみ返す。
		$events = array(
			'evt_new' => array(
				'event_id'    => 'evt_new',
				'event_type'  => 'click',
				'section_id'  => 'ad',
				'cta_id'      => 'cta_new',
				'variant_id'  => 'default',
				'accepted_at' => '2026-06-26T13:00:00+00:00',
				'article_id'  => '',
				'metadata'    => array(),
			),
			'evt_old' => array(
				'event_id'    => 'evt_old',
				'event_type'  => 'affiliate_click',
				'section_id'  => 'ad',
				'cta_id'      => 'cta_old',
				'variant_id'  => 'default',
				'accepted_at' => '2026-06-26T11:00:00+00:00',
				'article_id'  => '',
				'metadata'    => array(),
			),
		);

		Functions\expect( 'get_transient' )
			->andReturnUsing( static function ( string $key ) use ( &$events ): mixed {
				foreach ( $events as $id => $event ) {
					$expected_key = 'agent_neo_tracking_event_' . substr( hash( 'sha256', $id ), 0, 40 );
					if ( $key === $expected_key ) {
						return $event;
					}
				}
				return false;
			} );

		$ref  = new \ReflectionClass( 'Agent_Neo_Core_Tracking_Export_Controller' );
		$load = $ref->getMethod( 'load_events' );
		$load->setAccessible( true );
		$controller = $ref->newInstanceWithoutConstructor();

		// index は新着 prepend: [evt_new, evt_mid（仮）, evt_old]
		// after=evt_new → evt_old のみ。
		$result = $load->invoke(
			$controller,
			array( 'evt_new', 'evt_old' ),
			'evt_new', // after=evt_new
			100,
			array(),
			0
		);

		$this->assertCount( 1, $result, 'after より古いイベントが1件のみ返ること' );
		$this->assertSame( 'evt_old', $result[0]['event_id'] );
	}

	// ------------------------------------------------------------------
	// TC-CTA-103: limit — 件数上限が機能すること
	// ------------------------------------------------------------------

	/**
	 * limit=1 の場合、1件のみ返ること。
	 *
	 * @return void
	 */
	public function test_export_limit_restricts_count(): void {
		if ( ! class_exists( 'Agent_Neo_Core_Tracking_Export_Controller' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-tracking-export-controller.php';
		}

		$ids    = array( 'evt_1', 'evt_2', 'evt_3' );
		$events = array();
		foreach ( $ids as $i => $id ) {
			$events[ $id ] = array(
				'event_id'    => $id,
				'event_type'  => 'click',
				'section_id'  => 'ad',
				'cta_id'      => 'cta_' . $i,
				'variant_id'  => 'default',
				'accepted_at' => '2026-06-26T12:0' . $i . ':00+00:00',
				'article_id'  => '',
				'metadata'    => array(),
			);
		}

		Functions\expect( 'get_transient' )
			->andReturnUsing( static function ( string $key ) use ( &$events ): mixed {
				foreach ( $events as $id => $event ) {
					$expected_key = 'agent_neo_tracking_event_' . substr( hash( 'sha256', $id ), 0, 40 );
					if ( $key === $expected_key ) {
						return $event;
					}
				}
				return false;
			} );

		$ref  = new \ReflectionClass( 'Agent_Neo_Core_Tracking_Export_Controller' );
		$load = $ref->getMethod( 'load_events' );
		$load->setAccessible( true );
		$controller = $ref->newInstanceWithoutConstructor();

		$result = $load->invoke( $controller, $ids, '', 1, array(), 0 );

		$this->assertCount( 1, $result, 'limit=1 なので1件のみ返ること' );
		$this->assertSame( 'evt_1', $result[0]['event_id'] );
	}

	// ------------------------------------------------------------------
	// TC-CTA-104: event_type フィルタ
	// ------------------------------------------------------------------

	/**
	 * event_type フィルタで指定種別のみ返ること。
	 *
	 * @return void
	 */
	public function test_export_event_type_filter(): void {
		if ( ! class_exists( 'Agent_Neo_Core_Tracking_Export_Controller' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-tracking-export-controller.php';
		}

		$raw_events = array(
			'evt_imp' => array(
				'event_id'    => 'evt_imp',
				'event_type'  => 'ad_impression',
				'section_id'  => 'ad',
				'cta_id'      => 'cta_a',
				'variant_id'  => 'default',
				'accepted_at' => '2026-06-26T12:00:00+00:00',
				'article_id'  => '',
				'metadata'    => array(),
			),
			'evt_clk' => array(
				'event_id'    => 'evt_clk',
				'event_type'  => 'affiliate_click',
				'section_id'  => 'ad',
				'cta_id'      => 'cta_b',
				'variant_id'  => 'default',
				'accepted_at' => '2026-06-26T12:01:00+00:00',
				'article_id'  => '',
				'metadata'    => array(),
			),
		);

		Functions\expect( 'get_transient' )
			->andReturnUsing( static function ( string $key ) use ( &$raw_events ): mixed {
				foreach ( $raw_events as $id => $event ) {
					$expected_key = 'agent_neo_tracking_event_' . substr( hash( 'sha256', $id ), 0, 40 );
					if ( $key === $expected_key ) {
						return $event;
					}
				}
				return false;
			} );

		$ref  = new \ReflectionClass( 'Agent_Neo_Core_Tracking_Export_Controller' );
		$load = $ref->getMethod( 'load_events' );
		$load->setAccessible( true );
		$controller = $ref->newInstanceWithoutConstructor();

		// affiliate_click のみ絞り込む。
		$result = $load->invoke(
			$controller,
			array( 'evt_imp', 'evt_clk' ),
			'',
			100,
			array( 'affiliate_click' ),
			0
		);

		$this->assertCount( 1, $result, 'affiliate_click のみ1件返ること' );
		$this->assertSame( 'affiliate_click', $result[0]['event_type'] );
	}
}

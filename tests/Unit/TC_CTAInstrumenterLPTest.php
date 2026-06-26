<?php
/**
 * CTA 計装 × LP 実マークアップ テスト。
 *
 * LP パターン（lp-hero.php / lp-pricing.php）の実際のブロック HTML 構造を用いて
 * instrumenter の動作を検証する。
 *
 * 背景（VDD 前科):
 *   前セッション (part41) で「unit/契約 test は実 DOM 構造を反映しないとすり抜ける」
 *   ことが判明した（CTA 計装の <p> ラッパ漏れをすり抜け、実機で発覚）。
 *   本テストは LP パターンファイルの実際の HTML 断片を入力として使用し、
 *   実 DOM 構造との乖離を防ぐ。
 *
 * カバー内容:
 *   TC-LP-001: lp-hero primary CTA（div.wp-block-button.an-cta.an-cta--lp_hero_primary/<a>）→ affiliate 計装
 *   TC-LP-002: lp-hero secondary CTA（div.wp-block-button.an-cta.an-cta--lp_hero_secondary/<a>）→ affiliate 計装
 *   TC-LP-003: lp-hero 複合 HTML（primary + secondary 同時）→ 両方計装・cta_id 一致
 *   TC-LP-004: lp-pricing starter CTA（div.wp-block-button.an-cta.an-cta--lp_pricing_starter/<a>）→ affiliate 計装
 *   TC-LP-005: lp-pricing pro CTA（div.wp-block-button.an-cta.an-cta--lp_pricing_pro/<a>）→ affiliate 計装
 *   TC-LP-006: lp-pricing business CTA（div.wp-block-button.an-cta.an-cta--lp_pricing_business/<a>）→ affiliate 計装
 *   TC-LP-007: lp-pricing 複合 HTML（3プラン同時）→ 3件の cta_id が全て計装されること
 *   TC-LP-008: LP final-cta（an-lp-final-cta クラス div）→ ad 計装（affiliate ではない）
 *   TC-LP-009: 二重付与防止 — 既計装の LP CTA は上書きされないこと
 *   TC-LP-010: lp_hero_primary の cta_id が正確に 'lp_hero_primary' であること（ID精度）
 *   TC-LP-011: lp_pricing_pro の内側 <a> に data-variant-id="default" が付与されること
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * CTA 計装 × LP 実マークアップ テスト。
 */
class TC_CTAInstrumenterLPTest extends TestCase {

	/**
	 * Brain Monkey 初期化 + クラスロード。
	 *
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		if ( ! class_exists( 'Agent_Neo_Core_CTA_Instrumenter' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}
	}

	/**
	 * Brain Monkey 後片付け。
	 *
	 * @return void
	 */
	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// 共通ヘルパー: instrument_block() 呼び出し簡略化
	// ------------------------------------------------------------------

	/**
	 * instrument_block() を呼ぶ。
	 *
	 * @param string $html         入力 HTML。
	 * @param string $block_name   ブロック名（任意）。
	 * @return string
	 */
	private function instrument( string $html, string $block_name = 'core/button' ): string {
		$instrumenter = new \Agent_Neo_Core_CTA_Instrumenter();
		return $instrumenter->instrument_block( $html, array( 'blockName' => $block_name, 'attrs' => array() ) );
	}

	// ------------------------------------------------------------------
	// TC-LP-001: lp-hero primary CTA（実 HTML 断片）
	//
	// lp-hero.php より抽出した実際のボタン HTML:
	//   <div class="wp-block-button an-cta an-cta--lp_hero_primary">
	//     <a class="wp-block-button__link ..." href="#">...</a>
	//   </div>
	// → 内側 <a> に data-agent-neo-affiliate / data-cta-id="lp_hero_primary" が付与されること。
	// ------------------------------------------------------------------

	/**
	 * TC-LP-001: lp-hero primary CTA ボタン → 内側 <a> に affiliate 計装されること。
	 *
	 * @return void
	 */
	public function test_lp_hero_primary_cta_is_instrumented(): void {
		// lp-hero.php の実 HTML 断片（PHP 出力後の HTML）。
		$html = '<div class="wp-block-button an-cta an-cta--lp_hero_primary">'
			. '<a class="wp-block-button__link has-accent-aa-background-color has-background-color has-text-color has-background wp-element-button" href="#" style="border-radius:6px;font-weight:700;">無料で試してみる →</a>'
			. '</div>';

		$result = $this->instrument( $html );

		$this->assertStringContainsString(
			'data-agent-neo-affiliate',
			$result,
			'TC-LP-001: lp_hero_primary — 内側 <a> に data-agent-neo-affiliate が付与されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="lp_hero_primary"',
			$result,
			'TC-LP-001: lp_hero_primary — data-cta-id が正確に付与されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-LP-002: lp-hero secondary CTA（実 HTML 断片）
	// ------------------------------------------------------------------

	/**
	 * TC-LP-002: lp-hero secondary CTA ボタン → 内側 <a> に affiliate 計装されること。
	 *
	 * @return void
	 */
	public function test_lp_hero_secondary_cta_is_instrumented(): void {
		// lp-hero.php の outline ボタン断片。
		$html = '<div class="wp-block-button is-style-outline an-cta an-cta--lp_hero_secondary">'
			. '<a class="wp-block-button__link wp-element-button" href="#" style="border-radius:6px;border:2px solid var(--wp--preset--color--primary);">導入事例を見る</a>'
			. '</div>';

		$result = $this->instrument( $html );

		$this->assertStringContainsString(
			'data-agent-neo-affiliate',
			$result,
			'TC-LP-002: lp_hero_secondary — data-agent-neo-affiliate が付与されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="lp_hero_secondary"',
			$result,
			'TC-LP-002: lp_hero_secondary — data-cta-id が正確に付与されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-LP-003: lp-hero 複合 HTML（primary + secondary 同時）
	//
	// wp:buttons ラッパ内に 2 ボタンが並ぶ実際のパターン。
	// 両方の内側 <a> が独立して計装されること。
	// ------------------------------------------------------------------

	/**
	 * TC-LP-003: lp-hero に primary + secondary が同時存在 → 両方 affiliate 計装されること。
	 *
	 * @return void
	 */
	public function test_lp_hero_both_ctas_instrumented_simultaneously(): void {
		// lp-hero.php の wp:buttons ブロック全体。
		$html = '<div class="wp-block-buttons">'
			. '<div class="wp-block-button an-cta an-cta--lp_hero_primary">'
			. '<a class="wp-block-button__link has-accent-aa-background-color wp-element-button" href="#">無料で試してみる →</a>'
			. '</div>'
			. '<div class="wp-block-button is-style-outline an-cta an-cta--lp_hero_secondary">'
			. '<a class="wp-block-button__link wp-element-button" href="#">導入事例を見る</a>'
			. '</div>'
			. '</div>';

		$result = $this->instrument( $html, 'core/buttons' );

		$this->assertStringContainsString(
			'data-cta-id="lp_hero_primary"',
			$result,
			'TC-LP-003: lp_hero_primary が計装されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="lp_hero_secondary"',
			$result,
			'TC-LP-003: lp_hero_secondary が計装されること'
		);

		// 両方の <a> が個別に計装されていること（data-agent-neo-affiliate が 2 箇所）。
		$count = substr_count( $result, 'data-agent-neo-affiliate' );
		$this->assertSame( 2, $count, 'TC-LP-003: 2 つの CTA ボタンが独立して計装されること' );
	}

	// ------------------------------------------------------------------
	// TC-LP-004: lp-pricing starter CTA
	// ------------------------------------------------------------------

	/**
	 * TC-LP-004: lp-pricing スタータープランの CTA ボタン → affiliate 計装されること。
	 *
	 * @return void
	 */
	public function test_lp_pricing_starter_cta_is_instrumented(): void {
		// lp-pricing.php スタータープランボタン断片。
		$html = '<div class="wp-block-button is-style-outline an-cta an-cta--lp_pricing_starter has-custom-width wp-block-button__width-100">'
			. '<a class="wp-block-button__link wp-element-button" href="#" style="border-radius:6px;border:2px solid var(--wp--preset--color--primary);">無料で試す</a>'
			. '</div>';

		$result = $this->instrument( $html );

		$this->assertStringContainsString(
			'data-agent-neo-affiliate',
			$result,
			'TC-LP-004: lp_pricing_starter — data-agent-neo-affiliate が付与されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="lp_pricing_starter"',
			$result,
			'TC-LP-004: lp_pricing_starter — data-cta-id が正確に付与されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-LP-005: lp-pricing pro CTA
	// ------------------------------------------------------------------

	/**
	 * TC-LP-005: lp-pricing プロプランの CTA ボタン → affiliate 計装されること。
	 *
	 * @return void
	 */
	public function test_lp_pricing_pro_cta_is_instrumented(): void {
		// lp-pricing.php プロプランボタン断片（accent-aa 背景）。
		$html = '<div class="wp-block-button an-cta an-cta--lp_pricing_pro has-custom-width wp-block-button__width-100">'
			. '<a class="wp-block-button__link has-accent-aa-background-color has-background-color has-text-color has-background wp-element-button" href="#">無料で試す →</a>'
			. '</div>';

		$result = $this->instrument( $html );

		$this->assertStringContainsString(
			'data-agent-neo-affiliate',
			$result,
			'TC-LP-005: lp_pricing_pro — data-agent-neo-affiliate が付与されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="lp_pricing_pro"',
			$result,
			'TC-LP-005: lp_pricing_pro — data-cta-id が正確に付与されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-LP-006: lp-pricing business（enterprise）CTA
	// ------------------------------------------------------------------

	/**
	 * TC-LP-006: lp-pricing エンタープライズプランの CTA ボタン → affiliate 計装されること。
	 *
	 * @return void
	 */
	public function test_lp_pricing_business_cta_is_instrumented(): void {
		// lp-pricing.php エンタープライズボタン断片。
		$html = '<div class="wp-block-button is-style-outline an-cta an-cta--lp_pricing_business has-custom-width wp-block-button__width-100">'
			. '<a class="wp-block-button__link wp-element-button" href="#" style="border-radius:6px;border:2px solid var(--wp--preset--color--primary);">お問い合わせ</a>'
			. '</div>';

		$result = $this->instrument( $html );

		$this->assertStringContainsString(
			'data-agent-neo-affiliate',
			$result,
			'TC-LP-006: lp_pricing_business — data-agent-neo-affiliate が付与されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="lp_pricing_business"',
			$result,
			'TC-LP-006: lp_pricing_business — data-cta-id が正確に付与されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-LP-007: lp-pricing 複合 HTML（3プラン同時）
	//
	// 1 ページに starter/pro/business の 3 ボタンが同時に存在する構造。
	// 全て affiliate 計装されること。
	// ------------------------------------------------------------------

	/**
	 * TC-LP-007: lp-pricing の 3 プラン CTA が同時存在 → 全て affiliate 計装されること。
	 *
	 * @return void
	 */
	public function test_lp_pricing_all_three_ctas_instrumented(): void {
		// lp-pricing.php の 3 ボタン構造（wp:columns 内から抽出）。
		$html = '<div class="wp-block-columns">'
			// スタータープラン。
			. '<div class="wp-block-column">'
			. '<div class="wp-block-button is-style-outline an-cta an-cta--lp_pricing_starter has-custom-width wp-block-button__width-100">'
			. '<a class="wp-block-button__link wp-element-button" href="#">無料で試す</a>'
			. '</div>'
			. '</div>'
			// プロプラン。
			. '<div class="wp-block-column">'
			. '<div class="wp-block-button an-cta an-cta--lp_pricing_pro has-custom-width wp-block-button__width-100">'
			. '<a class="wp-block-button__link has-accent-aa-background-color has-background wp-element-button" href="#">無料で試す →</a>'
			. '</div>'
			. '</div>'
			// エンタープライズプラン。
			. '<div class="wp-block-column">'
			. '<div class="wp-block-button is-style-outline an-cta an-cta--lp_pricing_business has-custom-width wp-block-button__width-100">'
			. '<a class="wp-block-button__link wp-element-button" href="#">お問い合わせ</a>'
			. '</div>'
			. '</div>'
			. '</div>';

		$result = $this->instrument( $html, 'core/columns' );

		$this->assertStringContainsString(
			'data-cta-id="lp_pricing_starter"',
			$result,
			'TC-LP-007: lp_pricing_starter が計装されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="lp_pricing_pro"',
			$result,
			'TC-LP-007: lp_pricing_pro が計装されること'
		);
		$this->assertStringContainsString(
			'data-cta-id="lp_pricing_business"',
			$result,
			'TC-LP-007: lp_pricing_business が計装されること'
		);

		// 3 つ全ての <a> が計装されていること。
		$count = substr_count( $result, 'data-agent-neo-affiliate' );
		$this->assertSame( 3, $count, 'TC-LP-007: 3 プランすべての CTA が独立して計装されること' );
	}

	// ------------------------------------------------------------------
	// TC-LP-008: LP final-cta（an-lp-final-cta）→ ad 計装（affiliate ではない）
	//
	// final-cta は banner 系グループ（is_banner_class が true）に分類されるため
	// data-agent-neo-ad が付与される。affiliate ではないことを確認する。
	// ------------------------------------------------------------------

	/**
	 * TC-LP-008: an-lp-final-cta クラスの div → data-agent-neo-ad が付与され affiliate でないこと。
	 *
	 * @return void
	 */
	public function test_lp_final_cta_gets_ad_instrumentation_not_affiliate(): void {
		// an-lp-final-cta は instrument_banner_divs() の対象（an-*-final-cta パターン）。
		$html = '<div class="wp-block-group an-lp-final-cta alignfull">'
			. '<p>今すぐ始める</p>'
			. '</div>';

		$result = $this->instrument( $html, 'core/group' );

		$this->assertStringContainsString(
			'data-agent-neo-ad',
			$result,
			'TC-LP-008: an-lp-final-cta → data-agent-neo-ad が付与されること'
		);
		$this->assertStringNotContainsString(
			'data-agent-neo-affiliate',
			$result,
			'TC-LP-008: an-lp-final-cta → data-agent-neo-affiliate は付与されないこと（ad 計装のみ）'
		);
		$this->assertStringContainsString(
			'data-cta-id="lp_final_cta"',
			$result,
			'TC-LP-008: an-lp-final-cta → cta_id が lp_final_cta になること'
		);
	}

	// ------------------------------------------------------------------
	// TC-LP-009: 二重付与防止 — 既計装の LP CTA ボタン
	//
	// 既に data-agent-neo-affiliate が付いている内側 <a> は計装されないこと。
	// （SSR キャッシュ等で 2 回 render_block が走っても安全）
	// ------------------------------------------------------------------

	/**
	 * TC-LP-009: 既計装（data-agent-neo-affiliate あり）の LP CTA → 二重付与されないこと。
	 *
	 * @return void
	 */
	public function test_lp_already_instrumented_cta_not_double_instrumented(): void {
		// 既に計装済みの状態（1回目の render_block 後）。
		$html = '<div class="wp-block-button an-cta an-cta--lp_hero_primary">'
			. '<a class="wp-block-button__link wp-element-button" href="#" '
			. 'data-agent-neo-affiliate="" data-cta-id="lp_hero_primary" data-variant-id="default">無料で試してみる →</a>'
			. '</div>';

		$result = $this->instrument( $html );

		// data-cta-id は元の値のままであること。
		$this->assertStringContainsString(
			'data-cta-id="lp_hero_primary"',
			$result,
			'TC-LP-009: 既存の data-cta-id が保持されること'
		);

		// data-agent-neo-affiliate は 1 つだけであること（二重付与なし）。
		$count = substr_count( $result, 'data-agent-neo-affiliate' );
		$this->assertSame( 1, $count, 'TC-LP-009: data-agent-neo-affiliate は二重付与されないこと' );
	}

	// ------------------------------------------------------------------
	// TC-LP-010: lp_hero_primary の cta_id 精度確認
	//
	// an-cta--lp_hero_primary から extract した cta_id が
	// 正確に 'lp_hero_primary' であること（アンダースコアが保持されること）。
	// ------------------------------------------------------------------

	/**
	 * TC-LP-010: lp_hero_primary の cta_id にアンダースコアが正しく含まれること。
	 *
	 * @return void
	 */
	public function test_lp_hero_primary_cta_id_preserves_underscores(): void {
		// an-cta--lp_hero_primary の cta_id は 'lp_hero_primary'（アンダースコア込み）。
		if ( ! function_exists( 'agent_neo_core_extract_cta_id_from_class' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
		}

		$cta_id = agent_neo_core_extract_cta_id_from_class( 'wp-block-button an-cta an-cta--lp_hero_primary' );
		$this->assertSame( 'lp_hero_primary', $cta_id, 'TC-LP-010: アンダースコアを含む LP CTA ID が正確に抽出されること' );
	}

	// ------------------------------------------------------------------
	// TC-LP-011: lp_pricing_pro の内側 <a> に data-variant-id="default" が付与されること
	//
	// affiliate 計装では data-agent-neo-affiliate + data-cta-id に加えて
	// data-variant-id="default" が必須。LP CTA でも付与されることを確認する。
	// ------------------------------------------------------------------

	/**
	 * TC-LP-011: lp_pricing_pro の内側 <a> に data-variant-id="default" が付与されること。
	 *
	 * @return void
	 */
	public function test_lp_pricing_pro_has_variant_id_default(): void {
		$html = '<div class="wp-block-button an-cta an-cta--lp_pricing_pro has-custom-width wp-block-button__width-100">'
			. '<a class="wp-block-button__link has-accent-aa-background-color wp-element-button" href="#">無料で試す →</a>'
			. '</div>';

		$result = $this->instrument( $html );

		$this->assertStringContainsString(
			'data-variant-id="default"',
			$result,
			'TC-LP-011: lp_pricing_pro の <a> に data-variant-id="default" が付与されること'
		);
	}
}

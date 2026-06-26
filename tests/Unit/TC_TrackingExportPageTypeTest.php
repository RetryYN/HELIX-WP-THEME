<?php
/**
 * tracking export × page_type 契約テスト。
 *
 * ad-tracking.js は sendEvent() 内で metadata に page_type を付与する（JS 側担当）。
 * PHP 側の tracking-export-controller は format_event() で metadata 配列を
 * そのまま返すため、event store に page_type が入っていれば export 時に保持される。
 *
 * 本テストが守る不変条件:
 *   1. metadata.page_type を含む event が export されるとき、page_type が返ること。
 *   2. metadata に page_type がない event は page_type なしで返ること（空配列）。
 *   3. ad_impression / affiliate_click / scroll_depth 各イベント種別で
 *      metadata が正しく保持されること（種別横断確認）。
 *   4. metadata の他フィールド（element 等）は page_type と共存して返ること。
 *
 * テスト基盤: format_event() を Reflection で直接呼ぶ（load_events と独立）。
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * export 契約 × metadata.page_type テスト。
 */
class TC_TrackingExportPageTypeTest extends TestCase {

	/** @var \ReflectionMethod format_event Reflection */
	private \ReflectionMethod $format_method;

	/** @var \ReflectionMethod load_events Reflection */
	private \ReflectionMethod $load_method;

	/** @var object Agent_Neo_Core_Tracking_Export_Controller インスタンス */
	private object $controller;

	/**
	 * Brain Monkey + クラスロード + Reflection セットアップ。
	 *
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		if ( ! class_exists( 'Agent_Neo_Core_REST_Controller_Base' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-rest-controller-base.php';
		}
		if ( ! class_exists( 'Agent_Neo_Core_Tracking_Export_Controller' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-tracking-export-controller.php';
		}

		$ref = new \ReflectionClass( 'Agent_Neo_Core_Tracking_Export_Controller' );

		$this->format_method = $ref->getMethod( 'format_event' );
		$this->format_method->setAccessible( true );

		$this->load_method = $ref->getMethod( 'load_events' );
		$this->load_method->setAccessible( true );

		$this->controller = $ref->newInstanceWithoutConstructor();
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
	// TC-EXP-PT-001: metadata.page_type が 'lp' のイベント → export で保持されること
	//
	// LP ページで発火した ad_impression は metadata.page_type='lp' を含む。
	// format_event() がこのフィールドを metadata 配列内に保持して返すこと。
	// ------------------------------------------------------------------

	/**
	 * TC-EXP-PT-001: metadata.page_type='lp' の ad_impression イベント → 保持されること。
	 *
	 * @return void
	 */
	public function test_format_event_preserves_page_type_lp(): void {
		$raw_event = array(
			'event_id'    => 'evt_lp_001',
			'event_type'  => 'ad_impression',
			'section_id'  => 'ad',
			'cta_id'      => 'lp_hero_primary',
			'variant_id'  => 'default',
			'accepted_at' => '2026-06-27T10:00:00+00:00',
			'article_id'  => 'https://example.com/lp/',
			'metadata'    => array(
				'element'   => 'ad',
				'page_type' => 'lp',
			),
		);

		$result = $this->format_method->invoke( $this->controller, $raw_event );

		$this->assertArrayHasKey( 'metadata', $result, 'metadata キーが存在すること' );
		$this->assertArrayHasKey( 'page_type', $result['metadata'], 'metadata.page_type キーが存在すること' );
		$this->assertSame( 'lp', $result['metadata']['page_type'], 'metadata.page_type が "lp" であること' );
	}

	// ------------------------------------------------------------------
	// TC-EXP-PT-002: metadata に page_type がないイベント → page_type なしで返ること
	//
	// 旧形式（page_type 付与前）のイベントも正常に処理されること。
	// ------------------------------------------------------------------

	/**
	 * TC-EXP-PT-002: metadata に page_type が含まれないイベント → metadata.page_type が存在しないこと。
	 *
	 * @return void
	 */
	public function test_format_event_without_page_type_returns_no_page_type(): void {
		$raw_event = array(
			'event_id'    => 'evt_old_001',
			'event_type'  => 'affiliate_click',
			'section_id'  => 'ad',
			'cta_id'      => 'article_cta',
			'variant_id'  => 'default',
			'accepted_at' => '2026-06-27T09:00:00+00:00',
			'article_id'  => 'https://example.com/article/',
			'metadata'    => array(
				'element' => 'link',
			),
		);

		$result = $this->format_method->invoke( $this->controller, $raw_event );

		$this->assertArrayHasKey( 'metadata', $result, 'metadata キーが存在すること' );
		$this->assertArrayNotHasKey( 'page_type', $result['metadata'], 'page_type のないイベントには metadata.page_type が含まれないこと' );
	}

	// ------------------------------------------------------------------
	// TC-EXP-PT-003: affiliate_click でも metadata.page_type='post' が保持されること
	//
	// イベント種別横断確認。ad_impression 以外でも page_type が保持される。
	// ------------------------------------------------------------------

	/**
	 * TC-EXP-PT-003: affiliate_click イベントの metadata.page_type='post' が保持されること。
	 *
	 * @return void
	 */
	public function test_format_event_affiliate_click_preserves_page_type_post(): void {
		$raw_event = array(
			'event_id'    => 'evt_post_click_001',
			'event_type'  => 'affiliate_click',
			'section_id'  => 'ad',
			'cta_id'      => 'article_cta',
			'variant_id'  => 'default',
			'accepted_at' => '2026-06-27T11:00:00+00:00',
			'article_id'  => 'https://example.com/post/my-article/',
			'metadata'    => array(
				'element'   => 'link',
				'page_type' => 'post',
			),
		);

		$result = $this->format_method->invoke( $this->controller, $raw_event );

		$this->assertSame(
			'post',
			$result['metadata']['page_type'],
			'TC-EXP-PT-003: affiliate_click でも metadata.page_type="post" が保持されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-EXP-PT-004: scroll_depth でも metadata.page_type='home' が保持されること
	//
	// scroll_depth イベントの場合も横断確認。
	// ------------------------------------------------------------------

	/**
	 * TC-EXP-PT-004: scroll_depth イベントの metadata.page_type='home' が保持されること。
	 *
	 * @return void
	 */
	public function test_format_event_scroll_depth_preserves_page_type_home(): void {
		$raw_event = array(
			'event_id'    => 'evt_scroll_001',
			'event_type'  => 'scroll_depth',
			'section_id'  => 'ad',
			'cta_id'      => 'page',
			'variant_id'  => 'default',
			'accepted_at' => '2026-06-27T12:00:00+00:00',
			'article_id'  => 'https://example.com/',
			'metadata'    => array(
				'depth_pct' => 75,
				'page_type' => 'home',
			),
		);

		$result = $this->format_method->invoke( $this->controller, $raw_event );

		$this->assertSame(
			'home',
			$result['metadata']['page_type'],
			'TC-EXP-PT-004: scroll_depth でも metadata.page_type="home" が保持されること'
		);
		// depth_pct も同時に保持されること。
		$this->assertArrayHasKey( 'depth_pct', $result['metadata'], 'depth_pct も metadata に保持されること' );
	}

	// ------------------------------------------------------------------
	// TC-EXP-PT-005: metadata に page_type と element が共存して返ること
	//
	// page_type は element 等の他フィールドを上書きしないこと。
	// ------------------------------------------------------------------

	/**
	 * TC-EXP-PT-005: metadata.page_type と metadata.element が共存して返ること。
	 *
	 * @return void
	 */
	public function test_format_event_page_type_coexists_with_other_metadata_fields(): void {
		$raw_event = array(
			'event_id'    => 'evt_lp_002',
			'event_type'  => 'ad_impression',
			'section_id'  => 'ad',
			'cta_id'      => 'lp_pricing_pro',
			'variant_id'  => 'default',
			'accepted_at' => '2026-06-27T13:00:00+00:00',
			'article_id'  => 'https://example.com/pricing/',
			'metadata'    => array(
				'element'   => 'ad',
				'page_type' => 'lp',
				'section'   => 'pricing',
			),
		);

		$result = $this->format_method->invoke( $this->controller, $raw_event );

		$this->assertSame( 'lp', $result['metadata']['page_type'], 'page_type が保持されること' );
		$this->assertSame( 'ad', $result['metadata']['element'], 'element が保持されること' );
		$this->assertSame( 'pricing', $result['metadata']['section'], 'section が保持されること' );
	}

	// ------------------------------------------------------------------
	// TC-EXP-PT-006: metadata が配列でない場合 → 空配列で返ること
	//
	// 旧形式や壊れた event で metadata が配列でない場合、format_event() は
	// 空配列を返す（実装: is_array チェック）。page_type が失われても exception は出ない。
	// ------------------------------------------------------------------

	/**
	 * TC-EXP-PT-006: metadata が配列でない event → metadata が空配列で返ること（fail-safe）。
	 *
	 * @return void
	 */
	public function test_format_event_non_array_metadata_returns_empty_array(): void {
		$raw_event = array(
			'event_id'    => 'evt_broken_001',
			'event_type'  => 'ad_impression',
			'section_id'  => 'ad',
			'cta_id'      => 'some_cta',
			'variant_id'  => 'default',
			'accepted_at' => '2026-06-27T14:00:00+00:00',
			'article_id'  => '',
			// metadata が文字列（壊れた形式）。
			'metadata'    => 'corrupted',
		);

		$result = $this->format_method->invoke( $this->controller, $raw_event );

		$this->assertIsArray( $result['metadata'], 'metadata は常に配列を返すこと（fail-safe）' );
		$this->assertEmpty( $result['metadata'], 'metadata が配列でない場合は空配列を返すこと' );
	}

	// ------------------------------------------------------------------
	// TC-EXP-PT-007: load_events 経由で page_type が保持されること（統合確認）
	//
	// format_event 単体テストに加え、load_events → format_event の経路全体で
	// metadata.page_type が保持されることを確認する。
	// ------------------------------------------------------------------

	/**
	 * TC-EXP-PT-007: load_events → format_event 経路で metadata.page_type が保持されること。
	 *
	 * @return void
	 */
	public function test_load_events_preserves_page_type_through_full_pipeline(): void {
		$event_id  = 'evt_pipeline_001';
		$raw_event = array(
			'event_id'    => $event_id,
			'event_type'  => 'ad_impression',
			'section_id'  => 'ad',
			'cta_id'      => 'lp_hero_primary',
			'variant_id'  => 'default',
			'accepted_at' => '2026-06-27T15:00:00+00:00',
			'article_id'  => 'https://example.com/lp/',
			'metadata'    => array(
				'element'   => 'ad',
				'page_type' => 'lp',
			),
		);

		$transient_key = 'agent_neo_tracking_event_' . substr( hash( 'sha256', $event_id ), 0, 40 );

		Functions\expect( 'get_transient' )
			->andReturnUsing( static function ( string $key ) use ( $transient_key, $raw_event ): mixed {
				return $key === $transient_key ? $raw_event : false;
			} );

		$result = $this->load_method->invoke(
			$this->controller,
			array( $event_id ),
			'',
			100,
			array(),
			0
		);

		$this->assertCount( 1, $result, 'イベントが1件返ること' );
		$this->assertArrayHasKey( 'metadata', $result[0], 'metadata キーが存在すること' );
		$this->assertSame(
			'lp',
			$result[0]['metadata']['page_type'],
			'TC-EXP-PT-007: load_events → format_event 経路で page_type="lp" が保持されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-EXP-PT-008: page_type='other' のイベント → 'other' が保持されること
	//
	// 4 種類の page_type 値（home/post/lp/page/other）のうち 'other' を確認する。
	// ------------------------------------------------------------------

	/**
	 * TC-EXP-PT-008: metadata.page_type='other' → 'other' が保持されること。
	 *
	 * @return void
	 */
	public function test_format_event_preserves_page_type_other(): void {
		$raw_event = array(
			'event_id'    => 'evt_other_001',
			'event_type'  => 'scroll_depth',
			'section_id'  => 'ad',
			'cta_id'      => 'page',
			'variant_id'  => 'default',
			'accepted_at' => '2026-06-27T16:00:00+00:00',
			'article_id'  => 'https://example.com/?s=keyword',
			'metadata'    => array(
				'depth_pct' => 50,
				'page_type' => 'other',
			),
		);

		$result = $this->format_method->invoke( $this->controller, $raw_event );

		$this->assertSame(
			'other',
			$result['metadata']['page_type'],
			'TC-EXP-PT-008: page_type="other" が保持されること'
		);
	}
}

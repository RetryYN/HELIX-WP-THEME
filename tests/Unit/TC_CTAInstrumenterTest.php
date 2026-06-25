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

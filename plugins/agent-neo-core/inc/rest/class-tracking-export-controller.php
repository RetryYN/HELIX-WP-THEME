<?php
/**
 * GET /tracking/export controller。
 *
 * PULL 型計測ループの統合契約エンドポイント（schema_version: 1）。
 *
 * Automation SEO は既存接続（check_read_permission = ログイン + edit_posts）で
 * このエンドポイントを pull し、event_id カーソルで増分取得する。
 *
 * event store 構造:
 *   - agent_neo_tracking_event_queue option: 最大 100 件の event_id 配列（新着 prepend）。
 *   - agent_neo_tracking_event_<sha256(event_id)[0:40]> transient: 各イベント本体。
 *   （class-tracking-controller.php の queue_event() 実装に完全準拠）
 *
 * query params:
 *   after=<event_id>   指定 event_id より後（新着側）のみ返す（増分 pull）。
 *   limit=<n>          返す件数上限（1-100、既定 100）。
 *   event_type=<csv>   カンマ区切り event_type 絞り込み（任意）。
 *   since=<ISO8601>    UTC 時刻下限（任意）。
 *
 * response:
 *   { schema_version: 1, events: [...], next_cursor: <latest_event_id|null>, count: n }
 *
 * PII 非含有: event store の値のみ返す。IP 等は queue_event 段階で除外済み。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * tracking export endpoint。
 */
final class Agent_Neo_Core_Tracking_Export_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * tracking-controller と共通の event queue option キー。
	 *
	 * @var string
	 */
	private const EVENT_QUEUE_OPTION = 'agent_neo_tracking_event_queue';

	/**
	 * transient キーのプレフィックス（queue_event 実装に合わせる）。
	 *
	 * @var string
	 */
	private const EVENT_TRANSIENT_PREFIX = 'agent_neo_tracking_event_';

	/**
	 * limit の上限値。
	 *
	 * @var int
	 */
	private const MAX_LIMIT = 100;

	/**
	 * schema バージョン。
	 *
	 * @var int
	 */
	private const SCHEMA_VERSION = 1;

	/**
	 * rest_api_init に route 登録を接続する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Routes を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->register_agent_route(
			'/tracking/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_events' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'after'      => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
					'limit'      => array(
						'type'              => 'integer',
						'default'           => self::MAX_LIMIT,
						'minimum'           => 1,
						'maximum'           => self::MAX_LIMIT,
						'sanitize_callback' => 'absint',
					),
					'event_type' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
					'since'      => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
				),
			)
		);
	}

	/**
	 * Read 権限を確認する（ログイン + edit_posts 必須）。
	 *
	 * llmo-summary と同一: Automation SEO は既存接続で満たす。
	 *
	 * @return true|WP_Error
	 */
	public function check_read_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO tracking export.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability for AGENT NEO tracking export.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * GET /tracking/export。
	 *
	 * queue option + transient を読んでフィルタ適用し、
	 * PULL 型統合契約フォーマットで返す。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response
	 */
	public function export_events( WP_REST_Request $request ): WP_REST_Response {
		$after      = (string) $request->get_param( 'after' );
		$limit      = max( 1, min( (int) $request->get_param( 'limit' ), self::MAX_LIMIT ) );
		$event_type = (string) $request->get_param( 'event_type' );
		$since      = (string) $request->get_param( 'since' );

		// event_type CSV を配列へ展開する。
		$type_filter = array();
		if ( '' !== $event_type ) {
			$type_filter = array_filter(
				array_map( 'trim', explode( ',', $event_type ) ),
				static fn ( string $t ): bool => '' !== $t
			);
		}

		// since を Unix timestamp へ変換する。
		$since_ts = 0;
		if ( '' !== $since ) {
			$ts = strtotime( $since );
			if ( false !== $ts ) {
				$since_ts = $ts;
			}
		}

		// event queue を読む。
		$index = get_option( self::EVENT_QUEUE_OPTION, array() );
		if ( ! is_array( $index ) ) {
			$index = array();
		}

		// after 指定: queue は新着 prepend なので after 以降（より古い方向）の event を返す。
		// after が指定された場合、そのIDより古い（後ろの）IDのみ対象とする。
		$events = $this->load_events( $index, $after, $limit, $type_filter, $since_ts );

		// next_cursor: 最新（先頭）の event_id を返す（次回の after に使う）。
		$next_cursor = ! empty( $events ) ? $events[0]['event_id'] : null;

		// 最新のeventが先頭になるよう、queueの先頭event_idをnext_cursorとする。
		if ( null === $next_cursor && ! empty( $index ) ) {
			$next_cursor = null; // イベントなし。
		}

		$data = array(
			'schema_version' => self::SCHEMA_VERSION,
			'events'         => $events,
			'next_cursor'    => $next_cursor,
			'count'          => count( $events ),
		);

		$request_id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $request_id ) || '' === $request_id ) {
			$request_id = wp_generate_uuid4();
		}

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $data, $request_id ) );
	}

	/**
	 * queue index からフィルタ適用済みイベント配列を返す。
	 *
	 * queue は新着 prepend（index[0] が最新）。
	 * after 指定時: queue の中から after の位置を探し、それより後ろ（古い側）の範囲を対象にする。
	 *
	 * @param array<int,string>  $index       event_id 配列（新着 prepend）。
	 * @param string             $after       カーソル event_id（空なら全件）。
	 * @param int                $limit       最大返却件数。
	 * @param array<int,string>  $type_filter event_type フィルタ（空なら全種別）。
	 * @param int                $since_ts    Unix timestamp 下限（0 なら制限なし）。
	 * @return array<int,array<string,mixed>>
	 */
	private function load_events( array $index, string $after, int $limit, array $type_filter, int $since_ts ): array {
		if ( empty( $index ) ) {
			return array();
		}

		// after カーソル以降（古い側）に絞る。
		if ( '' !== $after ) {
			$pos = array_search( $after, $index, true );
			if ( false === $pos ) {
				// after に対応する event が queue にない: 空を返す。
				return array();
			}
			// $pos は after の位置。それより後（古い側 = index が大きい側）が対象。
			$index = array_slice( $index, (int) $pos + 1 );
		}

		$result = array();

		foreach ( $index as $event_id ) {
			if ( ! is_string( $event_id ) || '' === $event_id ) {
				continue;
			}

			$key   = self::EVENT_TRANSIENT_PREFIX . substr( hash( 'sha256', $event_id ), 0, 40 );
			$event = get_transient( $key );

			if ( ! is_array( $event ) ) {
				// transient 期限切れはスキップ。
				continue;
			}

			// since フィルタ。
			if ( $since_ts > 0 && isset( $event['accepted_at'] ) && is_string( $event['accepted_at'] ) ) {
				$ts = strtotime( $event['accepted_at'] );
				if ( false !== $ts && $ts < $since_ts ) {
					continue;
				}
			}

			// event_type フィルタ。
			if ( ! empty( $type_filter ) ) {
				$evt_type = isset( $event['event_type'] ) ? (string) $event['event_type'] : '';
				if ( ! in_array( $evt_type, $type_filter, true ) ) {
					continue;
				}
			}

			// PII 非含有: event store から必要フィールドのみ抽出する。
			// IP 等は queue_event 段階で保存されていないため、array_intersect_key で明示的に絞る。
			$result[] = $this->format_event( $event );

			if ( count( $result ) >= $limit ) {
				break;
			}
		}

		return $result;
	}

	/**
	 * event 配列を export 契約フォーマットに整形する。
	 *
	 * 出力フィールド: event_id / event_type / section_id / cta_id / variant_id /
	 *                 occurred_at / canonical_url / metadata。
	 *
	 * accepted_at を occurred_at としてマップする（WP 側内部名と契約名の差異を吸収）。
	 *
	 * @param array<string,mixed> $event 生イベント配列。
	 * @return array<string,mixed>
	 */
	private function format_event( array $event ): array {
		return array(
			'event_id'      => isset( $event['event_id'] ) ? (string) $event['event_id'] : '',
			'event_type'    => isset( $event['event_type'] ) ? (string) $event['event_type'] : '',
			'section_id'    => isset( $event['section_id'] ) ? (string) $event['section_id'] : '',
			'cta_id'        => isset( $event['cta_id'] ) ? (string) $event['cta_id'] : '',
			'variant_id'    => isset( $event['variant_id'] ) ? (string) $event['variant_id'] : '',
			'occurred_at'   => isset( $event['accepted_at'] ) ? (string) $event['accepted_at'] : '',
			'canonical_url' => isset( $event['article_id'] ) ? (string) $event['article_id'] : '',
			'metadata'      => isset( $event['metadata'] ) && is_array( $event['metadata'] ) ? $event['metadata'] : array(),
		);
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Tracking_Export_Controller();
		$controller->register();
		$container->register_module( 'rest-tracking-export' );
	}
);

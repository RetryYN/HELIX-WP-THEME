<?php
/**
 * GET /tracking/llmo-summary controller。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LLMO 計測サマリ endpoint。
 *
 * ai_impressions / ai_citations / ai_referral_clicks の 24h 集計を返す。
 * AI referral は UA 文字列の決定的マッチで分類する（AI 推論なし）。
 * tracking-controller が保存したイベントストア（transient + option index）から集計する。
 */
final class Agent_Neo_Core_LLMO_Summary_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * tracking-controller が使う event queue option キー。
	 *
	 * class-tracking-controller.php の queue_event() で
	 * `agent_neo_tracking_event_queue` に最大 100 件のイベント ID を保持する。
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
	 * 集計対象ウィンドウ（秒）。
	 *
	 * @var int
	 */
	private const WINDOW_SECONDS = DAY_IN_SECONDS;

	/**
	 * AI クローラ・アシスタントと判定する UA 文字列（決定的マッチ）。
	 *
	 * このリストは LLMO 計測固有の AI referral UA セットであり、
	 * class-tracking-controller.php の check_bot_policy() とは独立して管理する。
	 * tracking-controller はボットフィルタ（ブロック）用途のため対象が異なる。
	 * 追加・削除はこちらのファイルのみ編集すればよい。
	 *
	 * @var array<int, string>
	 */
	private const AI_UA_PATTERNS = array(
		'GPTBot',
		'ChatGPT-User',
		'ClaudeBot',
		'Claude-Web',
		'PerplexityBot',
		'Perplexity-User',
		'Bytespider',
		'YouBot',
		'BingBot',
		'Bingbot',
		'CopilotBot',
		'Google-Extended',
		'GoogleOther',
		'Meta-ExternalAgent',
		'Meta-ExternalFetcher',
		'GeminiBot',
		'MistralBot',
	);

	/**
	 * impression event_type 値。
	 *
	 * @var string
	 */
	private const EVENT_TYPE_IMPRESSION = 'impression';

	/**
	 * click event_type 値。
	 *
	 * @var string
	 */
	private const EVENT_TYPE_CLICK = 'click';

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
			'/tracking/llmo-summary',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_llmo_summary' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);
	}

	/**
	 * Read 権限を確認する（ログイン + edit_posts 必須）。
	 *
	 * @return true|WP_Error
	 */
	public function check_read_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO LLMO summary.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability for AGENT NEO LLMO summary.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * GET /tracking/llmo-summary。
	 *
	 * tracking-controller が保存したイベントストア（transient + option index）
	 * から過去 24h 分を集計して LLMO サマリを返す。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response
	 */
	public function get_llmo_summary( WP_REST_Request $request ): WP_REST_Response {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $request_id ) || '' === $request_id ) {
			$request_id = wp_generate_uuid4();
		}

		$window_start = time() - self::WINDOW_SECONDS;
		$events       = $this->load_recent_events( $window_start );

		$ai_impressions    = 0;
		$ai_citations      = 0;
		$ai_referral_clicks = 0;

		foreach ( $events as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}

			$event_type = isset( $event['event_type'] ) ? (string) $event['event_type'] : '';
			$metadata   = isset( $event['metadata'] ) && is_array( $event['metadata'] ) ? $event['metadata'] : array();

			// impression イベント: AI クローラからの場合は ai_impressions にカウントする。
			if ( self::EVENT_TYPE_IMPRESSION === $event_type ) {
				$ua = isset( $metadata['user_agent'] ) ? (string) $metadata['user_agent'] : '';
				if ( $this->is_ai_ua( $ua ) ) {
					++$ai_impressions;
				}
			}

			// click イベント: referrer が AI サービスの場合は ai_referral_clicks にカウントする。
			if ( self::EVENT_TYPE_CLICK === $event_type ) {
				$referrer = isset( $metadata['referrer'] ) ? (string) $metadata['referrer'] : '';
				if ( $this->is_ai_referrer( $referrer ) ) {
					++$ai_referral_clicks;
				}
			}

			// ai_citation フラグが metadata に立っている場合は ai_citations にカウントする。
			// Automation SEO 側が citation 確認時に metadata.ai_citation=true でイベントを送る。
			if ( isset( $metadata['ai_citation'] ) && true === $metadata['ai_citation'] ) {
				++$ai_citations;
			}
		}

		$scanned   = count( $events );
		// event queue は最大 100 件に切り詰められる（tracking-controller queue_event 実装参照）。
		// scanned が 100 件に達している場合、24h 内の実イベント数がそれを超えていた可能性があり、
		// 集計値が過小になるリスクがある。truncated フラグでクライアントに明示する。
		$truncated = $scanned >= 100;

		$data = array(
			'window_seconds'      => self::WINDOW_SECONDS,
			'window_start_utc'    => gmdate( 'c', $window_start ),
			'ai_impressions'      => $ai_impressions,
			'ai_citations'        => $ai_citations,
			'ai_referral_clicks'  => $ai_referral_clicks,
			'scanned'             => $scanned,
			'truncated'           => $truncated,
		);

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $data, $request_id ) );
	}

	/**
	 * option index から過去 $since 秒以降のイベントを読み込む。
	 *
	 * tracking-controller の queue_event() が維持する
	 * agent_neo_tracking_event_queue（最大 100 件の event_id 配列）を参照し、
	 * 各 event_id に対応する transient を読んで accepted_at でフィルタする。
	 *
	 * @param int $since Unix timestamp（これ以降のイベントを対象にする）。
	 * @return array<int, array<string, mixed>>
	 */
	private function load_recent_events( int $since ): array {
		$index = get_option( self::EVENT_QUEUE_OPTION, array() );
		if ( ! is_array( $index ) || empty( $index ) ) {
			// イベントがまだ記録されていない場合は空で返す。
			return array();
		}

		$events = array();
		foreach ( $index as $event_id ) {
			if ( ! is_string( $event_id ) || '' === $event_id ) {
				continue;
			}

			// queue_event() の set_transient キーに合わせる。
			$key   = self::EVENT_TRANSIENT_PREFIX . substr( hash( 'sha256', $event_id ), 0, 40 );
			$event = get_transient( $key );

			if ( ! is_array( $event ) ) {
				// transient 有効期限切れ（24h 超）は自然にスキップする。
				continue;
			}

			// accepted_at が window 内かを確認する。
			if ( isset( $event['accepted_at'] ) && is_string( $event['accepted_at'] ) ) {
				$accepted_ts = strtotime( $event['accepted_at'] );
				if ( false !== $accepted_ts && $accepted_ts < $since ) {
					continue;
				}
			}

			$events[] = $event;
		}

		return $events;
	}

	/**
	 * UA 文字列が AI クローラ・アシスタントか決定的に判定する。
	 *
	 * AI_UA_PATTERNS の各文字列が UA に含まれるかを case-insensitive で確認する。
	 * AI 推論は行わない。
	 *
	 * @param string $ua User-Agent 文字列。
	 * @return bool
	 */
	private function is_ai_ua( string $ua ): bool {
		if ( '' === $ua ) {
			return false;
		}

		$ua_lower = strtolower( $ua );
		foreach ( self::AI_UA_PATTERNS as $pattern ) {
			if ( str_contains( $ua_lower, strtolower( $pattern ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * referrer URL が AI サービス起点か決定的に判定する。
	 *
	 * AI サービスの既知ドメインリストに含まれるかを決定的マッチで確認する。
	 * AI 推論は行わない。
	 *
	 * @param string $referrer Referrer URL 文字列。
	 * @return bool
	 */
	private function is_ai_referrer( string $referrer ): bool {
		if ( '' === $referrer ) {
			return false;
		}

		$parsed = wp_parse_url( $referrer );
		if ( ! is_array( $parsed ) || empty( $parsed['host'] ) ) {
			return false;
		}

		$host = strtolower( (string) $parsed['host'] );

		// 既知の AI サービスドメイン（決定的リスト）。
		$ai_domains = array(
			'chat.openai.com',
			'chatgpt.com',
			'claude.ai',
			'perplexity.ai',
			'copilot.microsoft.com',
			'bing.com',
			'you.com',
			'phind.com',
			'poe.com',
			'character.ai',
			'gemini.google.com',
			'bard.google.com',
			'mistral.ai',
			'chat.mistral.ai',
			'huggingface.co',
			'cohere.com',
		);

		foreach ( $ai_domains as $domain ) {
			// ホストが一致するか、サブドメインである場合に該当とする。
			if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) {
				return true;
			}
		}

		return false;
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_LLMO_Summary_Controller();
		$controller->register();
		$container->register_module( 'rest-llmo-summary' );
	}
);

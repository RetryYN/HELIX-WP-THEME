<?php
/**
 * POST /blog-card/fetch — 外部OGP取得 REST endpoint。
 *
 * C-A1-001: 外部URL取得時の SSRF ガード + Action Scheduler / wp_cron fallback で非同期取得。
 *
 * REQ-NF-025 厳守: AIロジック・モデル呼び出し・統計判定を一切含まない。
 * OGP パース = 静的な文字列抽出（meta タグの regex / DOM パース）のみ。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 外部 Blog Card / OGP 取得エンドポイント。
 */
final class Agent_Neo_Core_Blog_Card_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * OGP キャッシュの TTL（秒）。デフォルト 24h。
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * 非同期取得クーロンフック名。
	 */
	private const CRON_HOOK = 'agent_neo_blog_card_fetch_async';

	/**
	 * rest_api_init に route 登録を接続する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( self::CRON_HOOK, array( $this, 'process_async_fetch' ), 10, 1 );
	}

	/**
	 * Routes を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// POST /blog-card/fetch — OGP 取得（同期 or 非同期キュー登録）。
		$this->register_agent_route(
			'/blog-card/fetch',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'fetch_ogp' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// GET /blog-card/cache — キャッシュ取得。
		$this->register_agent_route(
			'/blog-card/cache',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_cached' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Read / Write 共通 permission callback。
	 * ログイン済み + edit_posts 権限を要求する。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_permission( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * POST /blog-card/fetch ハンドラ。
	 *
	 * SSRF ガード適用後、Action Scheduler が有効なら非同期 queue に積む。
	 * 無効なら wp_schedule_single_event（wp_cron fallback）で非同期化する。
	 * キャッシュ済みなら即時返却する。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function fetch_ogp( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );
		$params     = $request->get_json_params();

		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		if ( empty( $params['url'] ) || ! is_string( $params['url'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'url is required.', 'agent-neo-core' ),
				array( 'field' => 'url' )
			);
		}

		$url = esc_url_raw( trim( $params['url'] ) );

		// SSRF ガード（C-A1-001）: 内部アドレスへのアクセスを遮断。
		$ssrf = Agent_Neo_Core_SSRF_Guard::validate( $url );
		if ( is_wp_error( $ssrf ) ) {
			return $ssrf;
		}

		// キャッシュヒット時は即時返却。
		$cache_key = $this->cache_key( $url );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response(
					array(
						'url'    => $url,
						'ogp'    => $cached,
						'cached' => true,
					),
					$request_id
				)
			);
		}

		// 非同期キュー登録。
		$async = isset( $params['async'] ) ? (bool) $params['async'] : true;
		if ( $async ) {
			$this->schedule_async_fetch( $url );
			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response(
					array(
						'url'     => $url,
						'queued'  => true,
						'cached'  => false,
					),
					$request_id
				)
			);
		}

		// async=false の場合は同期取得（管理画面プレビュー用）。
		$ogp = $this->do_fetch( $url );
		if ( is_wp_error( $ogp ) ) {
			return $ogp;
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'url'    => $url,
					'ogp'    => $ogp,
					'cached' => false,
				),
				$request_id
			)
		);
	}

	/**
	 * GET /blog-card/cache ハンドラ。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_cached( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );
		$url        = (string) $request->get_param( 'url' );

		if ( '' === $url ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'url query parameter is required.', 'agent-neo-core' ),
				array( 'field' => 'url' )
			);
		}

		$url = esc_url_raw( trim( $url ) );

		$ssrf = Agent_Neo_Core_SSRF_Guard::validate( $url );
		if ( is_wp_error( $ssrf ) ) {
			return $ssrf;
		}

		$cache_key = $this->cache_key( $url );
		$cached    = get_transient( $cache_key );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'url'    => $url,
					'ogp'    => is_array( $cached ) ? $cached : null,
					'cached' => is_array( $cached ),
				),
				$request_id
			)
		);
	}

	/**
	 * wp_cron 非同期フック: OGP 取得を実行してキャッシュに保存する。
	 *
	 * @param string $url 取得対象 URL。
	 * @return void
	 */
	public function process_async_fetch( string $url ): void {
		// 非同期実行時も SSRF ガードを再適用する（queue への不正登録対策）。
		$ssrf = Agent_Neo_Core_SSRF_Guard::validate( $url );
		if ( is_wp_error( $ssrf ) ) {
			return;
		}

		$ogp = $this->do_fetch( $url );
		if ( is_wp_error( $ogp ) ) {
			// 取得失敗は静かにスキップ（cron 再実行なし）。
			return;
		}

		$cache_key = $this->cache_key( $url );
		set_transient( $cache_key, $ogp, self::CACHE_TTL );
	}

	/**
	 * Action Scheduler / wp_cron どちらかで非同期取得をスケジュールする。
	 *
	 * @param string $url 取得対象 URL。
	 * @return void
	 */
	private function schedule_async_fetch( string $url ): void {
		// Action Scheduler が利用可能な場合は優先して使用する。
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::CRON_HOOK, array( $url ), 'agent-neo' );
			return;
		}

		// wp_cron fallback: 1秒後に single event を登録する。
		if ( ! wp_next_scheduled( self::CRON_HOOK, array( $url ) ) ) {
			wp_schedule_single_event( time() + 1, self::CRON_HOOK, array( $url ) );
		}
	}

	/**
	 * URL から OGP / meta 情報を取得して配列で返す。
	 *
	 * redirect 非追従（redirection=0）を Agent_Neo_Core_SSRF_Guard::safe_get 経由で保証。
	 * DOMDocument / regex による静的パースのみ（AIロジックなし REQ-NF-025）。
	 *
	 * @param string $url 取得対象 URL。
	 * @return array<string, mixed>|WP_Error
	 */
	private function do_fetch( string $url ) {
		$response = Agent_Neo_Core_SSRF_Guard::safe_get(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => 'AGENT-NEO-OGP/1.0 (WordPress; +https://agent-neo.com)',
			)
		);

		if ( is_wp_error( $response ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'Failed to fetch URL.', 'agent-neo-core' ),
				array( 'field' => 'url', 'reason' => $response->get_error_message() )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 400 ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'URL returned HTTP %d.', 'agent-neo-core' ),
					$code
				),
				array( 'field' => 'url', 'http_status' => $code )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$ogp  = $this->parse_ogp( $body, $url );

		return $ogp;
	}

	/**
	 * HTML から OGP / meta タグを静的パースして返す。
	 *
	 * AIロジック・モデル呼び出し・推論は一切行わない（REQ-NF-025）。
	 * DOMDocument を利用できない環境向けに regex フォールバックも提供する。
	 *
	 * @param string $html HTML body。
	 * @param string $url  取得元 URL（title fallback 用）。
	 * @return array<string, mixed>
	 */
	private function parse_ogp( string $html, string $url ): array {
		$ogp = array(
			'title'       => '',
			'description' => '',
			'image'       => '',
			'site_name'   => '',
			'url'         => $url,
			'type'        => '',
		);

		// DOMDocument による安全なパース。
		if ( class_exists( 'DOMDocument' ) ) {
			$dom = new DOMDocument();
			// 警告を抑制しながら UTF-8 で読み込む。
			libxml_use_internal_errors( true );
			$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
			libxml_clear_errors();

			$metas = $dom->getElementsByTagName( 'meta' );
			foreach ( $metas as $meta ) {
				if ( ! ( $meta instanceof DOMElement ) ) {
					continue;
				}
				$property = strtolower( (string) $meta->getAttribute( 'property' ) );
				$name     = strtolower( (string) $meta->getAttribute( 'name' ) );
				$content  = (string) $meta->getAttribute( 'content' );

				switch ( $property ) {
					case 'og:title':
						$ogp['title'] = sanitize_text_field( $content );
						break;
					case 'og:description':
						$ogp['description'] = sanitize_text_field( $content );
						break;
					case 'og:image':
						// OGP 画像は SSRF ガードを適用しない（表示用 URL であり取得しない）。
						$ogp['image'] = esc_url_raw( $content );
						break;
					case 'og:site_name':
						$ogp['site_name'] = sanitize_text_field( $content );
						break;
					case 'og:url':
						$ogp['url'] = esc_url_raw( $content );
						break;
					case 'og:type':
						$ogp['type'] = sanitize_key( $content );
						break;
				}

				// meta name="description" fallback。
				if ( 'description' === $name && '' === $ogp['description'] ) {
					$ogp['description'] = sanitize_text_field( $content );
				}
			}

			// <title> タグ fallback。
			if ( '' === $ogp['title'] ) {
				$titles = $dom->getElementsByTagName( 'title' );
				if ( $titles->length > 0 && $titles->item( 0 ) instanceof DOMElement ) {
					$ogp['title'] = sanitize_text_field( $titles->item( 0 )->textContent );
				}
			}
		} else {
			// DOMDocument 非対応環境向け regex フォールバック。
			if ( preg_match( '/<meta[^>]+property=["\']og:title["\'][^>]+content=["\'](.*?)["\']/si', $html, $m ) ) {
				$ogp['title'] = sanitize_text_field( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
			}
			if ( preg_match( '/<meta[^>]+property=["\']og:description["\'][^>]+content=["\'](.*?)["\']/si', $html, $m ) ) {
				$ogp['description'] = sanitize_text_field( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
			}
			if ( preg_match( '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/si', $html, $m ) ) {
				$ogp['image'] = esc_url_raw( $m[1] );
			}
			if ( preg_match( '/<title[^>]*>(.*?)<\/title>/si', $html, $m ) && '' === $ogp['title'] ) {
				$ogp['title'] = sanitize_text_field( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
			}
		}

		return $ogp;
	}

	/**
	 * トランジェントキャッシュキーを生成する。
	 *
	 * @param string $url URL。
	 * @return string
	 */
	private function cache_key( string $url ): string {
		return 'agent_neo_blog_card_' . substr( hash( 'sha256', $url ), 0, 40 );
	}

	/**
	 * X-Request-Id を取得または生成する。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return string
	 */
	private function resolve_request_id( WP_REST_Request $request ): string {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $request_id ) || '' === $request_id ) {
			$request_id = wp_generate_uuid4();
		}
		return $request_id;
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Blog_Card_Controller();
		$controller->register();
		$container->register_module( 'rest-blog-card' );
	}
);

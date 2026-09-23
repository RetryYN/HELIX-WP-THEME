<?php
/**
 * GET /pages, GET /pages/{id}, POST /pages/{id}/preview controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ページ読み取りおよびプレビュートークン発行 endpoint。
 *
 * 既存の class-pages-controller.php（apply/rollback）とは独立して動作する。
 * preview token は `_agent_neo_preview_content` meta に保存し、
 * TTL は WordPress transient で管理する。
 * apply 昇格時は from_preview_token に token を渡すことで
 * class-pages-controller.php::apply_page() の preview_state() が "consumed" を返す。
 *
 * REQ-NF-025: AI ロジック一切なし。WP read と確定済みコンテンツの保存のみ。
 */
final class Agent_Neo_Core_Pages_Read_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * プレビューコンテンツ保存先 meta key。
	 * class-pages-controller.php の apply/rollback で使用する meta key と異なる専用 key。
	 */
	private const PREVIEW_META_KEY = '_agent_neo_preview_content';

	/**
	 * プレビュー token → page_id マッピング保存先 transient prefix。
	 * transient key: agent_neo_preview_{token}
	 */
	private const PREVIEW_TRANSIENT_PREFIX = 'agent_neo_preview_';

	/**
	 * プレビュー有効期限（秒）。 30 分。
	 */
	private const PREVIEW_TTL = 1800;

	/**
	 * Auth helper。
	 *
	 * @var Agent_Neo_Core_Auth
	 */
	private Agent_Neo_Core_Auth $auth;

	/**
	 * Idempotency store。
	 *
	 * @var Agent_Neo_Core_Idempotency_Store
	 */
	private Agent_Neo_Core_Idempotency_Store $idempotency_store;

	/**
	 * @param Agent_Neo_Core_Auth              $auth Auth helper。
	 * @param Agent_Neo_Core_Idempotency_Store $idempotency_store Idempotency store。
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_Idempotency_Store $idempotency_store
	) {
		$this->auth              = $auth;
		$this->idempotency_store = $idempotency_store;
	}

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
		// GET /pages — ページ一覧。
		$this->register_agent_route(
			'/pages',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_pages' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// GET /pages/{id} — ページ構造 + blueprint。
		$this->register_agent_route(
			'/pages/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_page' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// POST /pages/{id}/preview — プレビューコンテンツ保存 + token 発行。
		$this->register_agent_route(
			'/pages/(?P<id>\d+)/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_preview' ),
				'permission_callback' => array( $this, 'check_preview_permission' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * Read permission を確認する（ログイン + read capability）。
	 *
	 * @return true|WP_Error
	 */
	public function check_read_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO pages.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'read' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot read AGENT NEO pages.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * Preview 作成権限を確認する（ログイン + edit_posts + ページ個別 edit_post）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return true|WP_Error
	 */
	public function check_preview_permission( WP_REST_Request $request ) {
		// write 基本権限（nonce + edit_posts）を確認する。
		$permission = $this->auth->check_write_permission( $request, 'edit_posts' );
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		// ページ個別の edit_post 権限を確認する。
		$page_id = (int) $request['id'];
		if ( $page_id < 1 ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'Invalid page id.', 'agent-neo-core' ),
				array( 'field' => 'id' )
			);
		}

		if ( ! current_user_can( 'edit_post', $page_id ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot edit this page.', 'agent-neo-core' ),
				array( 'page_id' => $page_id )
			);
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * GET /pages — ページ一覧。
	 *
	 * クエリパラメータ: page, per_page, status, orderby, order。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function list_pages( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );

		// ページネーション引数の取得とサニタイズ。
		$page     = max( 1, (int) ( $request->get_param( 'page' ) ?? 1 ) );
		$per_page = min( 100, max( 1, (int) ( $request->get_param( 'per_page' ) ?? $request->get_param( 'limit' ) ?? 20 ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		// ステータスフィルタ（許可リスト）。
		$allowed_statuses = array( 'publish', 'draft', 'private', 'pending', 'future', 'trash', 'any' );
		$status_param     = $request->get_param( 'status' );
		$status           = ( is_string( $status_param ) && in_array( $status_param, $allowed_statuses, true ) )
			? $status_param
			: 'publish';

		// ソート。
		$allowed_orderby = array( 'date', 'modified', 'title', 'ID', 'menu_order' );
		$orderby_param   = $request->get_param( 'orderby' );
		$sort_param      = $request->get_param( 'sort' );

		// sort=-modified → orderby=modified, order=DESC 形式に対応。
		$orderby = 'modified';
		$order   = 'DESC';
		if ( is_string( $sort_param ) && '' !== $sort_param ) {
			if ( str_starts_with( $sort_param, '-' ) ) {
				$orderby_candidate = ltrim( $sort_param, '-' );
				if ( in_array( $orderby_candidate, $allowed_orderby, true ) ) {
					$orderby = $orderby_candidate;
					$order   = 'DESC';
				}
			} else {
				if ( in_array( $sort_param, $allowed_orderby, true ) ) {
					$orderby = $sort_param;
					$order   = 'ASC';
				}
			}
		} elseif ( is_string( $orderby_param ) && in_array( $orderby_param, $allowed_orderby, true ) ) {
			$orderby = $orderby_param;
			$order_param = $request->get_param( 'order' );
			$order = ( is_string( $order_param ) && 'ASC' === strtoupper( $order_param ) ) ? 'ASC' : 'DESC';
		}

		// WP_Query でページ一覧を取得する。
		$query_args = array(
			'post_type'      => 'page',
			'post_status'    => $status,
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'orderby'        => $orderby,
			'order'          => $order,
			'no_found_rows'  => false,
		);

		$query = new WP_Query( $query_args );
		$posts = $query->posts;
		$total = (int) $query->found_posts;

		// ページオブジェクト一覧を整形する。
		$items = array();
		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$items[] = $this->page_list_item( $post );
		}

		// Link ヘッダ計算。
		$total_pages = (int) ceil( $total / $per_page );

		$response = rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'items'      => $items,
					'pagination' => array(
						'total'       => $total,
						'total_pages' => $total_pages,
						'page'        => $page,
						'per_page'    => $per_page,
					),
				),
				$request_id
			)
		);

		// Link ヘッダを付与する（RFC 5988）。
		if ( $response instanceof WP_REST_Response ) {
			$base = rest_url( self::NAMESPACE . '/pages' );
			$links = array();
			if ( $page > 1 ) {
				$links[] = '<' . esc_url_raw( add_query_arg( array( 'page' => $page - 1, 'per_page' => $per_page ), $base ) ) . '>; rel="prev"';
			}
			if ( $page < $total_pages ) {
				$links[] = '<' . esc_url_raw( add_query_arg( array( 'page' => $page + 1, 'per_page' => $per_page ), $base ) ) . '>; rel="next"';
			}
			$links[] = '<' . esc_url_raw( add_query_arg( array( 'page' => 1, 'per_page' => $per_page ), $base ) ) . '>; rel="first"';
			$links[] = '<' . esc_url_raw( add_query_arg( array( 'page' => $total_pages, 'per_page' => $per_page ), $base ) ) . '>; rel="last"';
			$response->header( 'Link', implode( ', ', $links ) );
			$response->header( 'X-Total-Count', (string) $total );
		}

		return $response;
	}

	/**
	 * GET /pages/{id} — ページ構造 + blueprint。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_page( WP_REST_Request $request ) {
		$page_id = (int) $request['id'];
		$request_id = $this->resolve_request_id( $request );

		// ページの取得と権限確認。
		$page = $this->get_page_post( $page_id );
		if ( is_wp_error( $page ) ) {
			return $page;
		}

		// ブロック構造を parse_blocks() で取得する。
		$blocks = $this->parse_page_blocks( $page->post_content );

		// 保存済み blueprint を取得する。
		$blueprint = $this->get_blueprint_for_page( $page_id );

		// プレビューコンテンツの有無を確認する（meta にあるか）。
		$preview_meta = get_post_meta( $page_id, self::PREVIEW_META_KEY, true );
		$has_preview  = is_array( $preview_meta ) && ! empty( $preview_meta );

		$data = array(
			'page_id'     => $page_id,
			'id'          => $page_id,
			'title'       => get_the_title( $page ),
			'status'      => $page->post_status,
			'modified'    => get_the_modified_date( 'c', $page ),
			'link'        => get_permalink( $page ),
			'post_type'   => $page->post_type,
			'blocks'      => $blocks,
			'block_count' => count( $blocks ),
			'blueprint'   => $blueprint,
			'has_preview' => $has_preview,
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * POST /pages/{id}/preview — 確定済みコンテンツをプレビュー保存し token を発行する。
	 *
	 * リクエスト JSON body:
	 * {
	 *   "content":         string  // 確定済み post_content（Gutenberg block markup）
	 *   "idempotency_key": string  // 必須。冪等性キー
	 *   "request_id":      string  // 任意。UUIDv4
	 * }
	 *
	 * レスポンス:
	 * {
	 *   "page_id":           int,
	 *   "preview_token":     string,
	 *   "preview_url":       string,
	 *   "expires_at":        string (ISO 8601),
	 *   "from_preview_token": string  // apply 昇格時に pages-controller へ渡す値
	 * }
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_preview( WP_REST_Request $request ) {
		$page_id = (int) $request['id'];
		$params  = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		// 入力バリデーション。
		$validation = $this->validate_preview_params( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$request_id      = $this->resolve_request_id_from_params( $params );
		$idempotency_key = sanitize_text_field( (string) $params['idempotency_key'] );
		$content         = (string) $params['content'];

		// ページ存在確認（post_type=page のみ）。
		$page = $this->get_page_post( $page_id );
		if ( is_wp_error( $page ) ) {
			return $page;
		}

		// 冪等性チェック。
		$payload_hash  = $this->idempotency_store->payload_hash(
			array(
				'page_id'    => $page_id,
				'content'    => $content,
				'request_id' => $request_id,
			)
		);
		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}
		if ( is_array( $stored_result ) ) {
			// 冪等再生：同一結果を返す。
			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response( $stored_result, $request_id )
			);
		}

		// preview token 生成（wp_generate_password で 24 文字英数字のみ）。
		$token      = wp_generate_password( 24, false );
		$expires_at = gmdate( 'c', time() + self::PREVIEW_TTL );

		// token → page_id マッピングを transient で保存（TTL 付き）。
		set_transient( self::PREVIEW_TRANSIENT_PREFIX . $token, $page_id, self::PREVIEW_TTL );

		// 確定済みコンテンツをプレビュー meta として保存する。
		// 複数トークンが同一ページに存在できる。配列形式で管理する。
		$existing_meta = get_post_meta( $page_id, self::PREVIEW_META_KEY, true );
		$previews      = is_array( $existing_meta ) ? $existing_meta : array();
		$previews[ $token ] = array(
			'content'    => $content,
			'request_id' => $request_id,
			'created_at' => gmdate( 'c' ),
			'expires_at' => $expires_at,
		);

		// 期限切れエントリを掃除する（上限 20 件）。
		$previews = $this->prune_expired_previews( $previews );

		update_post_meta( $page_id, self::PREVIEW_META_KEY, $previews );

		// preview URL を生成する（WordPress 標準の preview 機能を利用）。
		$preview_url = $this->build_preview_url( $page, $token );

		$result = array(
			'page_id'            => $page_id,
			'preview_token'      => $token,
			'preview_url'        => $preview_url,
			'expires_at'         => $expires_at,
			'ttl_seconds'        => self::PREVIEW_TTL,
			// apply 昇格時: POST /pages/{id}/apply の from_preview_token に渡す値。
			// class-pages-controller.php の preview_state() がこの token を検証する。
			'from_preview_token' => $token,
			'request_id'         => $request_id,
		);

		// 冪等性結果を保存する。
		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );

		$response = rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $result, $request_id )
		);

		// 201 Created を返す。
		if ( $response instanceof WP_REST_Response ) {
			$response->set_status( 201 );
		}

		return $response;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * post_type=page であることを確認してページを返す。
	 *
	 * @param int $page_id Page id。
	 * @return WP_Post|WP_Error
	 */
	private function get_page_post( int $page_id ) {
		$post = get_post( $page_id );
		if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Page was not found.', 'agent-neo-core' ),
				array( 'page_id' => $page_id )
			);
		}

		// 読み取り権限を確認する。
		if ( ! current_user_can( 'read_post', $page_id ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot read this page.', 'agent-neo-core' ),
				array( 'page_id' => $page_id )
			);
		}

		return $post;
	}

	/**
	 * JSON body を返す。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return array<string, mixed>|WP_Error
	 */
	private function json_params( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		return $params;
	}

	/**
	 * preview リクエストパラメータを検証する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function validate_preview_params( array $params ) {
		// content は必須。
		if ( ! array_key_exists( 'content', $params ) || ! is_string( $params['content'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'content is required and must be a string.', 'agent-neo-core' ),
				array( 'field' => 'content' )
			);
		}

		// idempotency_key は必須。
		if ( empty( $params['idempotency_key'] ) || ! is_string( $params['idempotency_key'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'idempotency_key is required.', 'agent-neo-core' ),
				array( 'field' => 'idempotency_key' )
			);
		}

		// request_id は任意だが指定時は UUIDv4 でなければならない。
		if ( isset( $params['request_id'] ) ) {
			if ( ! is_string( $params['request_id'] ) || ! $this->is_uuid_v4( $params['request_id'] ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'request_id must be UUIDv4.', 'agent-neo-core' ),
					array( 'field' => 'request_id' )
				);
			}
		}

		return true;
	}

	/**
	 * POST content を parse_blocks() でブロック配列に変換する。
	 *
	 * @param string $post_content Post content。
	 * @return array<int, array<string, mixed>>
	 */
	private function parse_page_blocks( string $post_content ): array {
		if ( ! function_exists( 'parse_blocks' ) ) {
			return array();
		}

		$raw_blocks = parse_blocks( $post_content );
		if ( ! is_array( $raw_blocks ) ) {
			return array();
		}

		$blocks = array();
		foreach ( $raw_blocks as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			// null ブロック（空行）はスキップする。
			$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : null;
			if ( null === $block_name ) {
				continue;
			}

			$blocks[] = array(
				'index'       => $index,
				'blockName'   => $block_name,
				'attrs'       => isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array(),
				'innerBlocks' => $this->map_inner_blocks( $block['innerBlocks'] ?? array() ),
				'innerHTML'   => isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ? $block['innerHTML'] : '',
			);
		}

		return $blocks;
	}

	/**
	 * innerBlocks を再帰的にマップする。
	 *
	 * @param mixed $inner_blocks Inner blocks。
	 * @return array<int, array<string, mixed>>
	 */
	private function map_inner_blocks( $inner_blocks ): array {
		if ( ! is_array( $inner_blocks ) ) {
			return array();
		}

		$result = array();
		foreach ( $inner_blocks as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : null;
			if ( null === $block_name ) {
				continue;
			}

			$result[] = array(
				'index'       => $index,
				'blockName'   => $block_name,
				'attrs'       => isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array(),
				'innerBlocks' => $this->map_inner_blocks( $block['innerBlocks'] ?? array() ),
				'innerHTML'   => isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ? $block['innerHTML'] : '',
			);
		}

		return $result;
	}

	/**
	 * ページに紐づく blueprint を取得する（blueprint-controller と同じ option から読む）。
	 *
	 * class-blueprint-controller.php の BLUEPRINTS_OPTION = 'agent_neo_blueprints_json' を参照。
	 * ページ ID に直接紐づく blueprint は page_id キーで検索する。
	 *
	 * @param int $page_id Page id。
	 * @return array<string, mixed>|null
	 */
	private function get_blueprint_for_page( int $page_id ): ?array {
		$json     = get_option( 'agent_neo_blueprints_json', '{}' );
		$decoded  = is_string( $json ) && '' !== $json ? json_decode( $json, true ) : null;

		if ( ! is_array( $decoded ) ) {
			return null;
		}

		// blueprint_payload の page_id フィールドで一致するものを探す。
		foreach ( $decoded as $blueprint_id => $blueprint ) {
			if ( ! is_array( $blueprint ) ) {
				continue;
			}

			// page_apply で保存されたページとの紐づきを確認する。
			if ( isset( $blueprint['page_id'] ) && (int) $blueprint['page_id'] === $page_id ) {
				return $blueprint;
			}

			// blueprint_id がページスラッグと一致する場合も返す。
			$page = get_post( $page_id );
			if ( $page instanceof WP_Post && is_string( $blueprint_id ) && $page->post_name === $blueprint_id ) {
				return $blueprint;
			}
		}

		return null;
	}

	/**
	 * ページ一覧の 1 アイテムを整形する。
	 *
	 * @param WP_Post $post Post object。
	 * @return array<string, mixed>
	 */
	private function page_list_item( WP_Post $post ): array {
		return array(
			'id'       => $post->ID,
			'title'    => get_the_title( $post ),
			'status'   => $post->post_status,
			'modified' => get_the_modified_date( 'c', $post ),
			'link'     => get_permalink( $post ),
			'slug'     => $post->post_name,
			'parent'   => (int) $post->post_parent,
		);
	}

	/**
	 * preview URL を生成する。
	 *
	 * WordPress 標準の preview_post_link をベースに agent_neo_preview token を付与する。
	 *
	 * @param WP_Post $page Page object。
	 * @param string  $token Preview token。
	 * @return string
	 */
	private function build_preview_url( WP_Post $page, string $token ): string {
		$base_url = get_permalink( $page );
		if ( ! is_string( $base_url ) || '' === $base_url ) {
			$base_url = home_url( '/?page_id=' . $page->ID );
		}

		return add_query_arg(
			array(
				'preview'             => 'true',
				'agent_neo_preview'   => rawurlencode( $token ),
			),
			$base_url
		);
	}

	/**
	 * 期限切れのプレビューエントリを掃除する（最大 20 件まで保持）。
	 *
	 * @param array<string, mixed> $previews Existing previews。
	 * @return array<string, mixed>
	 */
	private function prune_expired_previews( array $previews ): array {
		$now    = time();
		$active = array();

		foreach ( $previews as $token => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$expires_at = isset( $entry['expires_at'] ) && is_string( $entry['expires_at'] ) ? strtotime( $entry['expires_at'] ) : 0;
			if ( false === $expires_at || 0 === $expires_at || $expires_at <= $now ) {
				continue;
			}

			$active[ (string) $token ] = $entry;
		}

		// 上限 20 件を超える場合は古いものから削除する。
		if ( count( $active ) > 20 ) {
			// created_at でソートして古いものを削除する。
			uasort(
				$active,
				static function ( array $a, array $b ): int {
					$a_time = isset( $a['created_at'] ) ? strtotime( (string) $a['created_at'] ) : 0;
					$b_time = isset( $b['created_at'] ) ? strtotime( (string) $b['created_at'] ) : 0;
					return (int) $a_time - (int) $b_time;
				}
			);

			$active = array_slice( $active, -20, 20, true );
		}

		return $active;
	}

	/**
	 * X-Request-Id ヘッダまたは新規 UUID を返す。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return string
	 */
	private function resolve_request_id( WP_REST_Request $request ): string {
		$header_value = $request->get_header( 'X-Request-Id' );
		if ( is_string( $header_value ) && '' !== $header_value && $this->is_uuid_v4( $header_value ) ) {
			return $header_value;
		}

		return wp_generate_uuid4();
	}

	/**
	 * params から request_id を取得するか、新規 UUID を返す。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return string
	 */
	private function resolve_request_id_from_params( array $params ): string {
		if ( isset( $params['request_id'] ) && is_string( $params['request_id'] ) && $this->is_uuid_v4( $params['request_id'] ) ) {
			return $params['request_id'];
		}

		return wp_generate_uuid4();
	}

	/**
	 * UUIDv4 形式か判定する。
	 *
	 * @param string $value Value。
	 * @return bool
	 */
	private function is_uuid_v4( string $value ): bool {
		return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value );
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Pages_Read_Controller( $container->auth(), $container->idempotency_store() );
		$controller->register();
		$container->register_module( 'rest-pages-read' );
	}
);

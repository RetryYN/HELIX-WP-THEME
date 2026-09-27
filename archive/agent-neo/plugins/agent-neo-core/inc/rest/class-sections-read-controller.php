<?php
/**
 * GET /sections, GET /sections/{section_id}, POST /sections/{section_id}/apply controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * セクション一覧・詳細・静的 apply endpoint。
 *
 * AI ロジック禁止 (REQ-NF-025)。リクエストで渡された確定済み内容の
 * 静的 apply と WP read のみ実装する。
 */
final class Agent_Neo_Core_Sections_Read_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/** セクションスナップショットを保存する post meta key。 */
	private const ROLLBACK_META_KEY = '_agent_neo_section_rollback_points';

	/** 保持するロールバックポイントの最大数。 */
	private const MAX_ROLLBACKS = 30;

	/**
	 * @var Agent_Neo_Core_Auth
	 */
	private Agent_Neo_Core_Auth $auth;

	/**
	 * @var Agent_Neo_Core_JSON_Patch
	 */
	private Agent_Neo_Core_JSON_Patch $json_patch;

	/**
	 * @var Agent_Neo_Core_Idempotency_Store
	 */
	private Agent_Neo_Core_Idempotency_Store $idempotency_store;

	/**
	 * @var Agent_Neo_Core_Rollback_Store
	 */
	private Agent_Neo_Core_Rollback_Store $rollback_store;

	/**
	 * @var Agent_Neo_Core_Audit_Log
	 */
	private Agent_Neo_Core_Audit_Log $audit_log;

	/**
	 * @param Agent_Neo_Core_Auth              $auth Auth helper。
	 * @param Agent_Neo_Core_JSON_Patch        $json_patch JSON Patch helper。
	 * @param Agent_Neo_Core_Idempotency_Store $idempotency_store Idempotency store。
	 * @param Agent_Neo_Core_Rollback_Store    $rollback_store Rollback store。
	 * @param Agent_Neo_Core_Audit_Log         $audit_log Audit log。
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_JSON_Patch $json_patch,
		Agent_Neo_Core_Idempotency_Store $idempotency_store,
		Agent_Neo_Core_Rollback_Store $rollback_store,
		Agent_Neo_Core_Audit_Log $audit_log
	) {
		$this->auth              = $auth;
		$this->json_patch        = $json_patch;
		$this->idempotency_store = $idempotency_store;
		$this->rollback_store    = $rollback_store;
		$this->audit_log         = $audit_log;
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
		// GET /sections — セクション一覧。?post_id で絞り込み可。
		$this->register_agent_route(
			'/sections',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_sections' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// GET /sections/{section_id} — セクション詳細。
		$this->register_agent_route(
			'/sections/(?P<section_id>[A-Za-z0-9_\-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_section' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// POST /sections/{section_id}/apply — セクション更新（dryRun/apply）。
		$this->register_agent_route(
			'/sections/(?P<section_id>[A-Za-z0-9_\-]+)/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_section' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	/**
	 * Read permission を確認する。ログイン + read capability を要求する。
	 *
	 * @return true|WP_Error
	 */
	public function check_read_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO sections.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'read' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot read AGENT NEO sections.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * Write permission を確認する。Nonce + edit_posts capability を要求する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		return $this->auth->check_write_permission( $request, 'edit_posts' );
	}

	/**
	 * GET /sections — セクション一覧を返す。
	 *
	 * ?post_id=<id> で対象投稿を絞り込む。省略時は公開済み投稿を走査する。
	 * 各エントリに section_id / heading / block_count を付与する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function list_sections( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );
		$post_id    = $request->get_param( 'post_id' );

		if ( null !== $post_id ) {
			// 単一投稿モード。
			$post_id = (int) $post_id;
			if ( $post_id < 1 ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'post_id must be a positive integer.', 'agent-neo-core' ),
					array( 'field' => 'post_id' )
				);
			}

			$post = get_post( $post_id );
			if ( ! $post instanceof WP_Post ) {
				return Agent_Neo_Core_Auth::error(
					'NOT_FOUND',
					__( 'Post was not found.', 'agent-neo-core' ),
					array( 'post_id' => $post_id )
				);
			}

			if ( ! current_user_can( 'read_post', $post_id ) ) {
				return Agent_Neo_Core_Auth::error(
					'FORBIDDEN',
					__( 'Current user cannot read this post.', 'agent-neo-core' ),
					array( 'post_id' => $post_id )
				);
			}

			$sections = $this->extract_sections_from_post( $post );

			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response(
					array(
						'post_id'  => $post_id,
						'sections' => $sections,
						'total'    => count( $sections ),
					),
					$request_id
				)
			);
		}

		// 複数投稿走査モード。公開済み全投稿を対象にする。
		$posts = get_posts(
			array(
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'fields'         => 'all',
			)
		);

		$all_sections = array();
		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			if ( ! current_user_can( 'read_post', $post->ID ) ) {
				continue;
			}
			$post_sections = $this->extract_sections_from_post( $post );
			foreach ( $post_sections as $section ) {
				$section['post_id'] = $post->ID;
				$all_sections[]     = $section;
			}
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'sections' => $all_sections,
					'total'    => count( $all_sections ),
				),
				$request_id
			)
		);
	}

	/**
	 * GET /sections/{section_id} — セクション詳細（ブロック内訳）を返す。
	 *
	 * section_id は投稿 ID との複合キーでないため、?post_id=<id> を推奨する。
	 * post_id 省略時は最初にマッチした投稿を使用する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_section( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );
		$section_id = sanitize_text_field( (string) $request['section_id'] );

		if ( '' === $section_id ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'section_id is required.', 'agent-neo-core' )
			);
		}

		$post_id_param = $request->get_param( 'post_id' );
		if ( null !== $post_id_param ) {
			$post_id = (int) $post_id_param;
			$post    = get_post( $post_id );
			if ( ! $post instanceof WP_Post ) {
				return Agent_Neo_Core_Auth::error(
					'NOT_FOUND',
					__( 'Post was not found.', 'agent-neo-core' ),
					array( 'post_id' => $post_id )
				);
			}
			if ( ! current_user_can( 'read_post', $post_id ) ) {
				return Agent_Neo_Core_Auth::error(
					'FORBIDDEN',
					__( 'Current user cannot read this post.', 'agent-neo-core' ),
					array( 'post_id' => $post_id )
				);
			}
			return $this->section_detail_response( $post, $section_id, $request_id );
		}

		// post_id 省略時: 全公開投稿を走査して最初にマッチしたものを返す。
		$posts = get_posts(
			array(
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'all',
			)
		);

		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			if ( ! current_user_can( 'read_post', $post->ID ) ) {
				continue;
			}
			$document = $this->json_patch->document_from_post_content( $post->post_content );
			$blocks   = isset( $document['blocks'] ) && is_array( $document['blocks'] ) ? $document['blocks'] : array();
			$range    = $this->json_patch->find_section_range( $blocks, $section_id );
			if ( ! is_wp_error( $range ) ) {
				return $this->section_detail_response( $post, $section_id, $request_id );
			}
		}

		return Agent_Neo_Core_Auth::error(
			'NOT_FOUND',
			__( 'Section was not found in any accessible post.', 'agent-neo-core' ),
			array( 'section_id' => $section_id )
		);
	}

	/**
	 * POST /sections/{section_id}/apply — セクション内容を静的に適用する。
	 *
	 * AI 生成は行わない (REQ-NF-025)。リクエストで渡された確定済みセクション
	 * 内容をそのまま WP ブロックコンテンツに書き込む。
	 *
	 * dry_run=true の場合は副作用なしで diff/risk を返す。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function apply_section( WP_REST_Request $request ) {
		$section_id = sanitize_text_field( (string) $request['section_id'] );
		if ( '' === $section_id ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'section_id is required.', 'agent-neo-core' )
			);
		}

		// JSON body 必須チェック (seo-controller の apply_payload を踏襲)。
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		// post_id は body 必須フィールド。
		if ( ! isset( $params['post_id'] ) || ! is_int( $params['post_id'] ) && ! ctype_digit( (string) $params['post_id'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'post_id is required in request body.', 'agent-neo-core' ),
				array( 'field' => 'post_id' )
			);
		}
		$post_id = (int) $params['post_id'];
		if ( $post_id < 1 ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'post_id must be a positive integer.', 'agent-neo-core' ),
				array( 'field' => 'post_id' )
			);
		}

		// section_content は body 必須フィールド（確定済み HTML 文字列）。
		if ( ! isset( $params['section_content'] ) || ! is_string( $params['section_content'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'section_content is required and must be a string.', 'agent-neo-core' ),
				array( 'field' => 'section_content' )
			);
		}
		// wp_kses_post() で WP ブロックマークアップの許可タグ外要素（script 等）を除去する。
		// class-sections-controller.php の section_blocks_from_payload が content を
		// parse_blocks() に渡す前に wp_kses_post() を適用しない方式を採用しているが、
		// こちらは直接書き込み経路のため、ここで先に sanitize してストアド XSS を封じる。
		$section_content = wp_kses_post( $params['section_content'] );

		// risk は任意配列フィールド。
		$risk = isset( $params['risk'] ) && is_array( $params['risk'] ) ? $params['risk'] : array();

		// dry_run フラグ。
		$dry_run = isset( $params['dry_run'] ) ? (bool) $params['dry_run'] : false;

		// idempotency_key は省略可（省略時は request_id から生成）。
		$request_id      = $this->resolve_request_id( $request );
		$idempotency_key = isset( $params['idempotency_key'] ) && is_string( $params['idempotency_key'] ) && '' !== $params['idempotency_key']
			? $params['idempotency_key']
			: 'section_apply_' . $request_id;

		// 投稿存在・編集権限チェック。
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Post was not found.', 'agent-neo-core' ),
				array( 'post_id' => $post_id )
			);
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot edit this post.', 'agent-neo-core' ),
				array( 'post_id' => $post_id )
			);
		}

		// 現在の before ブロック群を取得する。
		$document     = $this->json_patch->document_from_post_content( $post->post_content );
		$before_blocks = isset( $document['blocks'] ) && is_array( $document['blocks'] ) ? $document['blocks'] : array();

		// セクション範囲を特定する。
		$before_range = $this->json_patch->find_section_range( $before_blocks, $section_id );
		if ( is_wp_error( $before_range ) ) {
			return $before_range;
		}

		$before_section_blocks = array_slice( $before_blocks, $before_range['start'], $before_range['length'] );

		// リクエストで渡されたコンテンツをパースして after ブロックを生成する（AI 処理なし）。
		$after_section_blocks = $this->blocks_from_content_string( $section_content, $section_id );

		// diff / diff_hash を計算する。
		$diff      = $this->json_patch->diff( $before_section_blocks, $after_section_blocks );
		$diff_hash = $this->json_patch->diff_hash( $diff );

		// diff_hash 不一致チェック (PRECONDITION_FAILED)。
		if ( isset( $params['diff_hash'] ) && is_string( $params['diff_hash'] ) && '' !== $params['diff_hash'] ) {
			if ( ! hash_equals( $params['diff_hash'], $diff_hash ) ) {
				return Agent_Neo_Core_Auth::error(
					'PRECONDITION_FAILED',
					__( 'Section diff_hash does not match current content.', 'agent-neo-core' ),
					array( 'expected' => $diff_hash )
				);
			}
		}

		// dry_run モード: 副作用なしで diff / risk を返す。
		if ( $dry_run ) {
			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response(
					array(
						'post_id'    => $post_id,
						'section_id' => $section_id,
						'dry_run'    => true,
						'applied'    => false,
						'diff_hash'  => $diff_hash,
						'diff'       => $diff,
						'risk'       => $risk,
					),
					$request_id
				)
			);
		}

		// 冪等チェック。
		$payload_hash  = $this->idempotency_store->payload_hash(
			array(
				'post_id'        => $post_id,
				'section_id'     => $section_id,
				'section_content' => $section_content,
				'diff_hash'      => $diff_hash,
			)
		);
		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}
		if ( is_array( $stored_result ) ) {
			// 既に適用済みのリプレイ。
			$stored_result['applied']    = false;
			$stored_result['warnings'][] = array(
				'code'    => 'IDEMPOTENT_REPLAY',
				'message' => 'Stored section result returned without reapplying.',
			);
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, $request_id ) );
		}

		// ロールバックポイントを保存する。
		$rollback_point_id = $this->snapshot_section( $post_id, $post->post_content, $section_id, $request_id );

		// セクションを置換してコンテンツを再構築する。
		$patched_blocks = $this->json_patch->replace_section_by_id( $before_blocks, $section_id, $after_section_blocks );
		if ( is_wp_error( $patched_blocks ) ) {
			return $patched_blocks;
		}

		$document['blocks'] = $patched_blocks;
		$new_content        = $this->json_patch->post_content_from_document( $document );

		// wp_update_post で投稿を更新する。
		$updated = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'Post update failed.', 'agent-neo-core' ),
				array( 'reason' => $updated->get_error_message() )
			);
		}

		// リソースバージョンをインクリメントする。
		$this->rollback_store->increment_resource_version( $post_id );

		// 監査ログを記録する。
		$audit_id = $this->audit_log->record(
			'section_apply',
			$request_id,
			$diff_hash,
			$idempotency_key,
			array(
				'post_id'            => $post_id,
				'section_id'         => $section_id,
				'rollback_point_id'  => $rollback_point_id,
			)
		);

		$result = array(
			'post_id'           => $post_id,
			'section_id'        => $section_id,
			'applied'           => true,
			'diff_hash'         => $diff_hash,
			'diff'              => $diff,
			'risk'              => $risk,
			'rollback_point_id' => $rollback_point_id,
			'audit_id'          => $audit_id,
			'request_id'        => $request_id,
			'warnings'          => array(),
		);

		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * 投稿から全セクション一覧を抽出する。
	 *
	 * 既存の section_id 導出規約 (class-sections-controller.php 整合) に従い、
	 * json_patch->section_id() を使用する。H2 heading を起点にセクションを分割し、
	 * 各セクションに section_id / heading / block_count を付与する。
	 *
	 * @param WP_Post $post 対象投稿。
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_sections_from_post( WP_Post $post ): array {
		$document = $this->json_patch->document_from_post_content( $post->post_content );
		$blocks   = isset( $document['blocks'] ) && is_array( $document['blocks'] ) ? $document['blocks'] : array();
		$sections = array();

		$current_section_blocks = array();
		$current_section_id     = '';
		$current_heading        = '';

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$is_h2 = $this->is_h2_heading_block( $block );

			if ( $is_h2 ) {
				// 前のセクションを確定する。
				if ( '' !== $current_section_id ) {
					$sections[] = array(
						'section_id'  => $current_section_id,
						'heading'     => $current_heading,
						'block_count' => count( $current_section_blocks ),
					);
				}

				// 新しいセクションを開始する。
				$current_section_id     = $this->json_patch->section_id( $block );
				$current_heading        = $this->heading_text_from_block( $block );
				$current_section_blocks = array( $block );
			} else {
				if ( '' !== $current_section_id ) {
					$current_section_blocks[] = $block;
				}
			}
		}

		// 最後のセクションを確定する。
		if ( '' !== $current_section_id ) {
			$sections[] = array(
				'section_id'  => $current_section_id,
				'heading'     => $current_heading,
				'block_count' => count( $current_section_blocks ),
			);
		}

		return $sections;
	}

	/**
	 * 指定セクションの詳細レスポンスを返す。
	 *
	 * @param WP_Post $post 対象投稿。
	 * @param string  $section_id セクション ID。
	 * @param string  $request_id リクエスト ID。
	 * @return WP_REST_Response|WP_Error
	 */
	private function section_detail_response( WP_Post $post, string $section_id, string $request_id ) {
		$document = $this->json_patch->document_from_post_content( $post->post_content );
		$blocks   = isset( $document['blocks'] ) && is_array( $document['blocks'] ) ? $document['blocks'] : array();

		$range = $this->json_patch->find_section_range( $blocks, $section_id );
		if ( is_wp_error( $range ) ) {
			return $range;
		}

		$section_blocks = array_slice( $blocks, $range['start'], $range['length'] );
		$heading_block  = isset( $section_blocks[0] ) && is_array( $section_blocks[0] ) ? $section_blocks[0] : array();
		$heading_text   = $this->heading_text_from_block( $heading_block );

		// ブロック内訳を整形する。
		$block_details = array();
		foreach ( $section_blocks as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$block_details[] = array(
				'index'      => $index,
				'block_name' => isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '',
				'block_id'   => $this->json_patch->block_id( $block ),
				'section_id' => $this->json_patch->section_id( $block ),
				'inner_html' => isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ? $block['innerHTML'] : '',
			);
		}

		// 現在のセクションコンテンツの diff_hash（クライアントが apply 前に参照する用）。
		$current_diff_hash = $this->json_patch->diff_hash(
			$this->json_patch->diff( array(), $section_blocks )
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'post_id'          => $post->ID,
					'section_id'       => $section_id,
					'heading'          => $heading_text,
					'block_count'      => count( $section_blocks ),
					'blocks'           => $block_details,
					'current_diff_hash' => $current_diff_hash,
				),
				$request_id
			)
		);
	}

	/**
	 * セクションコンテンツ文字列をブロック配列に変換する（AI 処理なし）。
	 *
	 * section_id マーカーが先頭ブロックに存在しない場合は付与する。
	 * class-sections-controller.php の ensure_section_marker と整合させる。
	 *
	 * @param string $content セクションコンテンツ（WP ブロックマークアップ）。
	 * @param string $section_id セクション ID。
	 * @return array<int, array<string, mixed>>
	 */
	private function blocks_from_content_string( string $content, string $section_id ): array {
		if ( function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( $content );
		} else {
			// parse_blocks が利用不可な環境向けフォールバック。
			$blocks = array(
				array(
					'blockName'    => null,
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => $content,
					'innerContent' => array( $content ),
				),
			);
		}

		if ( ! is_array( $blocks ) || empty( $blocks ) ) {
			$blocks = array();
		}

		// 先頭ブロックに section_id マーカーがない場合は付与する。
		foreach ( $blocks as $block ) {
			if ( is_array( $block ) && $section_id === $this->json_patch->section_id( $block ) ) {
				return $blocks;
			}
		}

		if ( empty( $blocks ) ) {
			$blocks = array(
				array(
					'blockName'    => null,
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array( '' ),
				),
			);
		}

		$attrs                           = isset( $blocks[0]['attrs'] ) && is_array( $blocks[0]['attrs'] ) ? $blocks[0]['attrs'] : array();
		$attrs['section_id']             = $section_id;
		$agent_neo                       = isset( $attrs['agentNeo'] ) && is_array( $attrs['agentNeo'] ) ? $attrs['agentNeo'] : array();
		$agent_neo['section_id']         = $section_id;
		$attrs['agentNeo']               = $agent_neo;
		$blocks[0]['attrs']              = $attrs;

		return $blocks;
	}

	/**
	 * セクションスナップショット（ロールバックポイント）を post meta に保存する。
	 *
	 * @param int    $post_id 投稿 ID。
	 * @param string $post_content 更新前のコンテンツ。
	 * @param string $section_id セクション ID。
	 * @param string $request_id リクエスト ID。
	 * @return string ロールバックポイント ID。
	 */
	private function snapshot_section( int $post_id, string $post_content, string $section_id, string $request_id ): string {
		$points = get_post_meta( $post_id, self::ROLLBACK_META_KEY, true );
		$points = is_array( $points ) ? $points : array();

		$rollback_point_id = 'sec_rb_' . wp_generate_uuid4();
		$point             = array(
			'rollback_point_id' => $rollback_point_id,
			'post_id'           => $post_id,
			'section_id'        => $section_id,
			'post_content'      => $post_content,
			'request_id'        => $request_id,
			'created_at'        => gmdate( 'c' ),
		);

		$points[] = $point;
		if ( count( $points ) > self::MAX_ROLLBACKS ) {
			$points = array_slice( $points, -1 * self::MAX_ROLLBACKS );
		}

		update_post_meta( $post_id, self::ROLLBACK_META_KEY, $points );

		return $rollback_point_id;
	}

	/**
	 * X-Request-Id ヘッダーを読み、存在しなければ UUID を生成する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return string
	 */
	private function resolve_request_id( WP_REST_Request $request ): string {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( is_string( $request_id ) && '' !== $request_id && $this->is_uuid_v4( $request_id ) ) {
			return $request_id;
		}

		return wp_generate_uuid4();
	}

	/**
	 * block が H2 heading か判定する。
	 *
	 * class-json-patch.php の is_h2_heading() と同じ判定基準を使う。
	 *
	 * @param array<string, mixed> $block Block。
	 * @return bool
	 */
	private function is_h2_heading_block( array $block ): bool {
		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		return isset( $block['blockName'] )
			&& 'core/heading' === $block['blockName']
			&& isset( $attrs['level'] )
			&& 2 === (int) $attrs['level'];
	}

	/**
	 * H2 heading block から見出しテキストを抽出する。
	 *
	 * @param array<string, mixed> $block Block。
	 * @return string
	 */
	private function heading_text_from_block( array $block ): string {
		$inner_html = isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ? $block['innerHTML'] : '';
		if ( '' === $inner_html ) {
			return '';
		}

		// タグを除去してテキストのみ返す。
		$text = wp_strip_all_tags( $inner_html );
		return is_string( $text ) ? trim( $text ) : '';
	}

	/**
	 * UUIDv4 形式か判定する。
	 *
	 * @param string $value 検証対象文字列。
	 * @return bool
	 */
	private function is_uuid_v4( string $value ): bool {
		// UUID v4 の正しいフォーマット: 8-4-4-4-12（ハイフン区切り）。
		// 修正前: 8-4-4-13（最後のグループが 12 桁でなく、前グループの 3 桁と結合していた誤り）。
		return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value );
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Sections_Read_Controller(
			$container->auth(),
			$container->json_patch(),
			$container->idempotency_store(),
			$container->rollback_store(),
			$container->audit_log()
		);
		$controller->register();
		$container->register_module( 'rest-sections-read' );
	}
);

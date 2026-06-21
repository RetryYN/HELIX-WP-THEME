<?php
/**
 * GET /ctas, GET /ctas/{cta_id}, POST /ctas/{cta_id}/apply controller.
 *
 * 法人専用（corporate package）エンドポイント。
 * 個人版（personal）からのアクセスは permission_callback で 403 FORBIDDEN を返す。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CTA 一覧・詳細・apply endpoint。
 *
 * - GET  /ctas              → CTA 一覧（corporate のみ）
 * - GET  /ctas/{cta_id}    → CTA 詳細（corporate のみ）/ 404 未登録
 * - POST /ctas/{cta_id}/apply → CTA swap（corporate のみ）/ 409 diff_hash 不一致 / idempotency
 *
 * ライセンス縮退（403 FEATURE_DISABLED / 503 LICENSE_GRACE_PERIOD）は
 * Agent_Neo_Core_License_State::guard_write_request が apply に対して自動適用するため
 * このクラスでは再実装しない（横断ポリシー TB-18a）。
 *
 * ## POST /ctas/{cta_id}/apply のスコープ境界（契約確定済み）
 *
 * 本 endpoint の責務は「CTA 定義レジストリ（agent_neo_ctas option）の更新」のみ。
 * FSE テーマが cta_id 参照でこの定義をレンダリングし、blueprint/section が
 * cta_id で参照する構造は blueprint/sections の option 保持モデルと同一。
 * `applied=true` は「CTA 定義を registry に保存した」ことを意味し、
 * 記事本文（post_content）への書き込みは一切行わない。
 *
 * 記事内の cta_id インスタンスを別 cta_id へ content-level で差し替える操作
 * （ACC-023: cta-old の全インスタンスを cta-new に置換・他テキスト無変更）は
 * 別 endpoint POST /elements/swap（REQ-F-023・api-catalog L69・現在未実装）の責務。
 * この endpoint は post_id をリクエストに取らず、Automation SEO 側が swap 判断を行う
 * （REQ-NF-025）。
 */
final class Agent_Neo_Core_CTAs_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * CTA レコードを保存する option key。
	 * blueprint の SECTIONS_OPTION と同じパターンで cta_id => record のマップを保持する。
	 */
	private const CTAS_OPTION = 'agent_neo_ctas';

	/**
	 * apply 時に diff_hash を計算する際のアルゴリズム。
	 */
	private const HASH_ALGO = 'sha256';

	private Agent_Neo_Core_Auth $auth;
	private Agent_Neo_Core_License_State $license_state;
	private Agent_Neo_Core_Idempotency_Store $idempotency_store;

	/**
	 * @param Agent_Neo_Core_Auth              $auth              認証・認可ヘルパー。
	 * @param Agent_Neo_Core_License_State     $license_state     ライセンス状態。package() で corporate 判定に使う。
	 * @param Agent_Neo_Core_Idempotency_Store $idempotency_store 冪等性ストア。apply 再送時に applied=false で返す。
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_License_State $license_state,
		Agent_Neo_Core_Idempotency_Store $idempotency_store
	) {
		$this->auth              = $auth;
		$this->license_state     = $license_state;
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
		// GET /ctas — CTA 一覧。
		$this->register_agent_route(
			'/ctas',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_ctas' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// GET /ctas/{cta_id} — CTA 詳細。
		// cta_id は slug 規約 [a-z0-9-] / license guard（is_guarded_write_route）と一致させる。
		$this->register_agent_route(
			'/ctas/(?P<cta_id>[a-z0-9-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_cta' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'cta_id' => array(
						'description'       => 'CTA ID（スラグ形式）。',
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		// POST /ctas/{cta_id}/apply — CTA swap。
		$this->register_agent_route(
			'/ctas/(?P<cta_id>[a-z0-9-]+)/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_cta' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
				'args'                => array(
					'cta_id' => array(
						'description'       => 'CTA ID（スラグ形式）。',
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * 読み取り系（GET）の permission callback。
	 * ログイン + edit_posts + corporate package が必要。
	 *
	 * @return true|WP_Error
	 */
	public function check_read_permission() {
		// ログイン確認。
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO CTA operations.', 'agent-neo-core' )
			);
		}

		// capability 確認。
		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability for AGENT NEO CTA operations.', 'agent-neo-core' )
			);
		}

		// corporate package 境界（受入の核心）: personal → 403 FORBIDDEN。
		return $this->auth->check_package_scope( 'corporate', $this->license_state->package() );
	}

	/**
	 * 書き込み系（apply）の permission callback。
	 * nonce 検証 + edit_posts + corporate package が必要。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		// nonce + capability を blueprint と同水準で検証する。
		$permission = $this->auth->check_write_permission( $request, 'edit_posts' );
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		// corporate package 境界: personal → 403 FORBIDDEN。
		return $this->auth->check_package_scope( 'corporate', $this->license_state->package() );
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * GET /ctas — CTA 一覧を返す。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function list_ctas( WP_REST_Request $request ) {
		$request_id = $this->request_id_from_header( $request );
		$records    = $this->load_option();
		if ( is_wp_error( $records ) ) {
			return $records;
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'ctas'  => array_values( $records ),
					'total' => count( $records ),
				),
				$request_id
			)
		);
	}

	/**
	 * GET /ctas/{cta_id} — CTA 詳細を返す。未登録 cta_id は 404 NotFound。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_cta( WP_REST_Request $request ) {
		$request_id = $this->request_id_from_header( $request );
		$cta_id     = (string) $request->get_param( 'cta_id' );

		$records = $this->load_option();
		if ( is_wp_error( $records ) ) {
			return $records;
		}

		if ( ! array_key_exists( $cta_id, $records ) ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'CTA was not found.', 'agent-neo-core' ),
				array( 'cta_id' => $cta_id )
			);
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array( 'cta' => $records[ $cta_id ] ),
				$request_id
			)
		);
	}

	/**
	 * POST /ctas/{cta_id}/apply — CTA 定義を registry（agent_neo_ctas option）に保存する。
	 *
	 * ## スコープ境界（契約確定済み）
	 *
	 * 本ハンドラは CTA 定義レジストリの更新のみを行う。post_content の書き換えは行わない。
	 * リクエストボディに post_id を取らないのはこのためで、「定義を保存した」ことを
	 * `applied=true` として返す。
	 *
	 * 記事内の cta_id インスタンスを別 cta_id へ content-level で差し替える操作
	 * （ACC-023）は POST /elements/swap（REQ-F-023）の責務であり、本ハンドラの範囲外。
	 *
	 * ## 処理フロー
	 *
	 *  1. JSON body バリデーション（必須フィールド・未知キー・型）。
	 *  2. idempotency_key の既存チェック → 再送なら applied=false で冪等応答。
	 *  3. 現在の CTA レコードから diff_hash を計算し、リクエストの diff_hash と比較。
	 *     不一致なら 409 CONFLICT。
	 *  4. cta_payload を保存し、新しい diff_hash を算出して 200 応答。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function apply_cta( WP_REST_Request $request ) {
		$request_id = $this->request_id_from_header( $request );
		$cta_id     = (string) $request->get_param( 'cta_id' );

		// JSON body を取得する。
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		// additionalProperties:false — 未知キーを拒否する。
		$allowed_keys = array( 'cta_payload', 'diff_hash', 'idempotency_key' );
		foreach ( array_keys( $params ) as $key ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'Unknown field is not allowed.', 'agent-neo-core' ),
					array( 'field' => $key )
				);
			}
		}

		// 必須フィールドの存在チェック。
		foreach ( $allowed_keys as $field ) {
			if ( ! array_key_exists( $field, $params ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'Required field is missing.', 'agent-neo-core' ),
					array( 'field' => $field )
				);
			}
		}

		// 型チェック。
		if ( ! is_array( $params['cta_payload'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'cta_payload must be an object.', 'agent-neo-core' ),
				array( 'field' => 'cta_payload' )
			);
		}

		if ( ! is_string( $params['diff_hash'] ) || '' === $params['diff_hash'] ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'diff_hash must be a non-empty string.', 'agent-neo-core' ),
				array( 'field' => 'diff_hash' )
			);
		}

		if ( ! is_string( $params['idempotency_key'] ) || '' === $params['idempotency_key'] ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'idempotency_key must be a non-empty string.', 'agent-neo-core' ),
				array( 'field' => 'idempotency_key' )
			);
		}

		$cta_payload     = $params['cta_payload'];
		$req_diff_hash   = $params['diff_hash'];
		$idempotency_key = $params['idempotency_key'];

		// idempotency チェック: 同一 key の再送なら applied=false で冪等応答する。
		$payload_hash = $this->idempotency_store->payload_hash(
			array(
				'cta_id'      => $cta_id,
				'cta_payload' => $cta_payload,
				'diff_hash'   => $req_diff_hash,
			)
		);

		$cached = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $cached ) ) {
			// 同一 key で異なる payload → 409 CONFLICT。
			return $cached;
		}

		if ( is_array( $cached ) ) {
			// 再送 → applied=false で冪等応答。
			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response(
					array_merge( $cached, array( 'applied' => false ) ),
					$request_id
				)
			);
		}

		// 現在の CTA option を読み込む。
		$records = $this->load_option();
		if ( is_wp_error( $records ) ) {
			return $records;
		}

		// 現在の CTA レコードから正規 diff_hash を計算する。
		// レコードが未登録の場合は空配列をベースラインとする。
		$current_record    = $records[ $cta_id ] ?? array();
		$current_diff_hash = $this->compute_diff_hash( $current_record );

		// diff_hash 整合チェック（409 CONFLICT）。
		if ( ! hash_equals( $current_diff_hash, $req_diff_hash ) ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'CTA diff_hash mismatch. The CTA has been modified by another request.', 'agent-neo-core' ),
				array(
					'cta_id'            => $cta_id,
					'expected_diff_hash' => $current_diff_hash,
					'provided_diff_hash' => $req_diff_hash,
				)
			);
		}

		// cta_payload を保存する（blueprint の store_record と同パターン）。
		$new_record = array(
			'cta_id'     => $cta_id,
			'cta_payload' => $cta_payload,
			'updated_at'  => gmdate( DATE_ATOM ),
		);

		$stored = $this->store_record( $cta_id, $new_record );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}

		// 保存後の新しい diff_hash を算出する。
		$new_diff_hash = $this->compute_diff_hash( $new_record );

		// apply 結果を組み立てる。
		$result = array(
			'cta_id'    => $cta_id,
			'diff_hash' => $new_diff_hash,
		);

		// idempotency 保存（同一 key の再送に備える）。
		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array_merge( $result, array( 'applied' => true ) ),
				$request_id
			)
		);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * CTA option から全レコードを配列として読み込む。
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	private function load_option() {
		$value = get_option( self::CTAS_OPTION, '{}' );
		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}

		$decoded = json_decode( $value, true );
		if ( ! is_array( $decoded ) ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'Stored CTA JSON is invalid.', 'agent-neo-core' )
			);
		}

		return $decoded;
	}

	/**
	 * CTA レコードを option に保存する（blueprint の store_record と同パターン）。
	 *
	 * @param string               $cta_id CTA ID。
	 * @param array<string, mixed> $record CTA レコード。
	 * @return true|WP_Error
	 */
	private function store_record( string $cta_id, array $record ) {
		$records = $this->load_option();
		if ( is_wp_error( $records ) ) {
			return $records;
		}

		$records[ $cta_id ] = $record;
		$json               = wp_json_encode( $records, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $json ) ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'CTA JSON serialization failed.', 'agent-neo-core' )
			);
		}

		$updated = update_option( self::CTAS_OPTION, $json, false );
		if ( ! $updated && get_option( self::CTAS_OPTION, '' ) !== $json ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'CTA storage update failed.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * CTA レコードの正規 diff_hash を計算する。
	 *
	 * レコードを JSON エンコードしてハッシュ化する。blueprint の json_patch->diff_hash に相当。
	 * 未登録（空配列）の場合は空 JSON "{}" のハッシュをベースラインとする。
	 *
	 * @param array<string, mixed> $record CTA レコード。
	 * @return string
	 */
	private function compute_diff_hash( array $record ): string {
		$json = wp_json_encode( $record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return hash( self::HASH_ALGO, is_string( $json ) ? $json : '{}' );
	}

	/**
	 * X-Request-Id ヘッダまたは生成した UUID を返す。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return string
	 */
	private function request_id_from_header( WP_REST_Request $request ): string {
		$header = $request->get_header( 'X-Request-Id' );
		return ( is_string( $header ) && '' !== $header ) ? $header : wp_generate_uuid4();
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_CTAs_Controller(
			$container->auth(),
			$container->license_state(),
			$container->idempotency_store()
		);
		$controller->register();
		$container->register_module( 'rest-ctas' );
	}
);

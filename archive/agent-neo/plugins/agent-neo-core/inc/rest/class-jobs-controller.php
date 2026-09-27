<?php
/**
 * POST /jobs, GET /jobs/{job_id}, POST /jobs/{job_id}/cancel controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 非同期ジョブ管理の REST controller。
 *
 * ステートマシン: queued → running → done | failed
 *                queued | running → cancelled
 *
 * AI ロジック / LLM 実行は一切持たない（REQ-NF-025）。
 * 状態管理のみ担当する。
 */
final class Agent_Neo_Core_Jobs_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * ジョブ option キーのプレフィックス。
	 */
	private const JOB_OPTION_PREFIX = 'agent_neo_core_job_';

	/**
	 * ジョブ一覧 index の option キー。
	 */
	private const JOB_INDEX_OPTION = 'agent_neo_core_job_index';

	/**
	 * ジョブ status として有効な値一覧。
	 *
	 * @var string[]
	 */
	private const VALID_STATUSES = array( 'queued', 'running', 'done', 'cancelled', 'failed' );

	/**
	 * cancel 可能な status 一覧。
	 *
	 * @var string[]
	 */
	private const CANCELLABLE_STATUSES = array( 'queued', 'running' );

	/**
	 * ジョブ index の最大保持件数。
	 */
	private const JOB_INDEX_MAX = 1000;

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
	 * ジョブ系 route を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// POST /jobs — ジョブ作成。
		$this->register_agent_route(
			'/jobs',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_job' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		// GET /jobs/{job_id} — ジョブ状態・結果取得。
		$this->register_agent_route(
			'/jobs/(?P<job_id>[A-Za-z0-9_\-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_job' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// POST /jobs/{job_id}/cancel — ジョブ取消。
		$this->register_agent_route(
			'/jobs/(?P<job_id>[A-Za-z0-9_\-]+)/cancel',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'cancel_job' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
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
				__( 'Authentication required for AGENT NEO job access.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'read' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot read AGENT NEO jobs.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * Write permission を確認する（nonce + edit_posts capability）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		return $this->auth->check_write_permission( $request, 'edit_posts' );
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * POST /jobs — 非同期ジョブを作成し status=queued で返す。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_job( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );

		// JSON body を取得する。
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		// idempotency_key 必須バリデーション。
		$idempotency_key = isset( $params['idempotency_key'] ) ? trim( (string) $params['idempotency_key'] ) : '';
		if ( '' === $idempotency_key ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'idempotency_key is required for job creation.', 'agent-neo-core' ),
				array( 'field' => 'idempotency_key' )
			);
		}

		// type バリデーション（必須・文字列）。
		$type = isset( $params['type'] ) ? sanitize_text_field( (string) $params['type'] ) : '';
		if ( '' === $type ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'type is required for job creation.', 'agent-neo-core' ),
				array( 'field' => 'type' )
			);
		}

		// payload（任意・配列）。
		$payload = isset( $params['payload'] ) && is_array( $params['payload'] )
			? $this->sanitize_deep( $params['payload'] )
			: array();

		// idempotency チェック。
		$payload_hash  = $this->idempotency_store->payload_hash(
			array(
				'idempotency_key' => $idempotency_key,
				'type'            => $type,
				'payload'         => $payload,
			)
		);
		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}

		if ( is_array( $stored_result ) ) {
			// 同一キー・同一 payload の再送 — 保存済みジョブを返す。
			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response( $stored_result, $request_id )
			);
		}

		// 新規ジョブレコードを組み立てる。
		$job_id     = 'job_' . wp_generate_uuid4();
		$created_at = gmdate( 'c' );

		$job = array(
			'job_id'     => $job_id,
			'type'       => $type,
			'status'     => 'queued',
			'payload'    => $payload,
			'result'     => null,
			'created_at' => $created_at,
			'updated_at' => $created_at,
		);

		// option に保存する。
		$option_key = self::JOB_OPTION_PREFIX . $job_id;
		update_option( $option_key, $job, false );

		// index に job_id を追記する。
		$this->add_to_index( $job_id );

		// idempotency store に保存する（再送応答用）。
		$this->idempotency_store->save( $idempotency_key, $payload_hash, $job );

		$response = rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $job, $request_id )
		);
		if ( $response instanceof WP_REST_Response ) {
			$response->set_status( 201 );
		}

		return $response;
	}

	/**
	 * GET /jobs/{job_id} — ジョブ状態・結果を返す。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_job( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );

		$job_id = sanitize_text_field( (string) $request['job_id'] );
		$job    = $this->load_job( $job_id );
		if ( is_wp_error( $job ) ) {
			return $job;
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $job, $request_id )
		);
	}

	/**
	 * POST /jobs/{job_id}/cancel — ジョブを取り消す。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function cancel_job( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );

		$job_id = sanitize_text_field( (string) $request['job_id'] );
		$job    = $this->load_job( $job_id );
		if ( is_wp_error( $job ) ) {
			return $job;
		}

		$current_status = (string) $job['status'];

		// 既に cancelled の場合は冪等 cancel — 現状をそのまま 200 OK で返す。
		if ( 'cancelled' === $current_status ) {
			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response( $job, $request_id )
			);
		}

		// done / failed はすでに取消不能な終端状態 — CONFLICT。
		if ( ! in_array( $current_status, self::CANCELLABLE_STATUSES, true ) ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				sprintf(
					/* translators: %s: current job status */
					__( 'Job cannot be cancelled because its current status is "%s".', 'agent-neo-core' ),
					$current_status
				),
				array(
					'job_id'  => $job_id,
					'status'  => $current_status,
				)
			);
		}

		// ステータスを cancelled に遷移する。
		$job['status']     = 'cancelled';
		$job['updated_at'] = gmdate( 'c' );

		$option_key = self::JOB_OPTION_PREFIX . $job_id;
		update_option( $option_key, $job, false );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $job, $request_id )
		);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * option からジョブレコードを取得する。存在しない場合は NOT_FOUND を返す。
	 *
	 * @param string $job_id ジョブ ID。
	 * @return array<string, mixed>|WP_Error
	 */
	private function load_job( string $job_id ) {
		if ( '' === $job_id ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Job was not found.', 'agent-neo-core' ),
				array( 'job_id' => $job_id )
			);
		}

		$option_key = self::JOB_OPTION_PREFIX . $job_id;
		$job        = get_option( $option_key, null );

		if ( ! is_array( $job ) || empty( $job['job_id'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Job was not found.', 'agent-neo-core' ),
				array( 'job_id' => $job_id )
			);
		}

		return $job;
	}

	/**
	 * ジョブ一覧 index に job_id を追記する。
	 *
	 * 新しいジョブを先頭に挿入し、上限（JOB_INDEX_MAX）を超えた分を末尾から除去する。
	 * index から外れた古い job の個別 option も合わせて削除してストレージ肥大を防ぐ。
	 *
	 * @param string $job_id ジョブ ID。
	 * @return void
	 */
	private function add_to_index( string $job_id ): void {
		$index = get_option( self::JOB_INDEX_OPTION, array() );
		if ( ! is_array( $index ) ) {
			$index = array();
		}

		// 重複を除外してから先頭に挿入する。
		$index = array_values( array_filter( $index, static function ( $id ) use ( $job_id ) {
			return $id !== $job_id;
		} ) );
		array_unshift( $index, $job_id );

		// 上限を超えた分（末尾 = 古いジョブ）を除去し、個別 option も削除する。
		if ( count( $index ) > self::JOB_INDEX_MAX ) {
			$evicted = array_splice( $index, self::JOB_INDEX_MAX );
			foreach ( $evicted as $evicted_id ) {
				delete_option( self::JOB_OPTION_PREFIX . $evicted_id );
			}
		}

		update_option( self::JOB_INDEX_OPTION, $index, false );
	}

	/**
	 * X-Request-Id ヘッダを読み、空であれば UUID を生成して返す。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return string
	 */
	private function resolve_request_id( WP_REST_Request $request ): string {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( is_string( $request_id ) && '' !== $request_id ) {
			return $request_id;
		}

		return wp_generate_uuid4();
	}

	/**
	 * 配列・スカラー値を再帰的に sanitize する。
	 *
	 * @param mixed $value Value。
	 * @return mixed
	 */
	private function sanitize_deep( $value ) {
		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $key => $child ) {
				$sanitized[ $key ] = $this->sanitize_deep( $child );
			}
			return $sanitized;
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		return $value;
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Jobs_Controller( $container->auth(), $container->idempotency_store() );
		$controller->register();
		$container->register_module( 'rest-jobs' );
	}
);

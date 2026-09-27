<?php
/**
 * POST /agent-neo/v1/migration/jobs controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 移行ジョブ作成 endpoint を担う REST controller。
 *
 * AI ロジック禁止（REQ-NF-025）。ジョブレコードの作成と状態管理のみ実施する。
 * Plan A / Plan B の移行判断は Automation SEO 側の責務であり本 endpoint に持たない。
 */
final class Agent_Neo_Core_Migration_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * ジョブを保存する WP option キーのプレフィックス。
	 */
	private const JOB_OPTION_PREFIX = 'agent_neo_core_migration_job_';

	/**
	 * 受け付ける plan 値。
	 *
	 * @var array<int, string>
	 */
	private const ALLOWED_PLANS = array( 'A', 'B' );

	/**
	 * ジョブの実行ステップ定義。
	 *
	 * @var array<int, string>
	 */
	private const JOB_STEPS = array( 'extract', 'transform', 'preview', 'apply' );

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
	 * POST /migration/jobs を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->register_agent_route(
			'/migration/jobs',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_job' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
			)
		);
	}

	/**
	 * edit_posts capability を確認する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return true|WP_Error
	 */
	public function check_create_permission( WP_REST_Request $request ) {
		return $this->auth->check_write_permission( $request, 'edit_posts' );
	}

	/**
	 * POST /migration/jobs ハンドラ。
	 *
	 * 移行ジョブレコードを作成し、job_id / status=queued / steps 配列を返す。
	 * バックグラウンド実行の起動は本 endpoint では行わない（呼び出し元が担う）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_job( WP_REST_Request $request ) {
		// request_id 確定。
		$request_id = $this->resolve_request_id( $request );

		// JSON body を取得する。
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body が必要です。', 'agent-neo-core' )
			);
		}

		// idempotency_key 必須チェック。
		$idempotency_key = isset( $params['idempotency_key'] ) ? sanitize_text_field( (string) $params['idempotency_key'] ) : '';
		if ( '' === $idempotency_key ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'idempotency_key は必須です。', 'agent-neo-core' ),
				array( 'field' => 'idempotency_key' )
			);
		}

		// plan バリデーション（A または B）。
		$plan = isset( $params['plan'] ) ? sanitize_text_field( (string) $params['plan'] ) : '';
		if ( ! in_array( $plan, self::ALLOWED_PLANS, true ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				sprintf(
					/* translators: %s: allowed plan values */
					__( 'plan は %s のいずれかを指定してください。', 'agent-neo-core' ),
					implode( ' または ', self::ALLOWED_PLANS )
				),
				array( 'field' => 'plan', 'allowed' => self::ALLOWED_PLANS )
			);
		}

		// source_url がある場合は URL 形式を検証する。
		// sanitize_text_field は & をエンティティ化するため除外し esc_url_raw のみ適用する。
		$source_url = '';
		if ( ! empty( $params['source_url'] ) ) {
			$source_url = esc_url_raw( (string) $params['source_url'] );
			if ( '' === $source_url ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'source_url が無効な URL です。', 'agent-neo-core' ),
					array( 'field' => 'source_url' )
				);
			}
		}

		// メモ（任意）のサニタイズ。
		$note = isset( $params['note'] ) ? sanitize_text_field( (string) $params['note'] ) : '';

		// ペイロードハッシュを計算し冪等性確認をする。
		$payload_hash = $this->idempotency_store->payload_hash(
			array(
				'idempotency_key' => $idempotency_key,
				'plan'            => $plan,
				'source_url'      => $source_url,
			)
		);

		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			// CONFLICT: 同一キーで異なるペイロード。
			return $stored_result;
		}

		if ( is_array( $stored_result ) ) {
			// 冪等リプレイ: 保存済み結果をそのまま返す。
			return rest_ensure_response(
				Agent_Neo_Core_Auth::success_response( $stored_result, $request_id )
			);
		}

		// ジョブ ID 生成（migration_ プレフィックス + UUID v4）。
		$job_id = 'migration_' . wp_generate_uuid4();

		// ステップ配列を初期化する（全ステップ pending）。
		$steps = array_map(
			static function ( string $step ): array {
				return array(
					'name'       => $step,
					'status'     => 'pending',
					'started_at' => null,
					'done_at'    => null,
					'message'    => null,
				);
			},
			self::JOB_STEPS
		);

		// ジョブレコードを構築する。
		$now = gmdate( 'c' );
		$job = array(
			'job_id'          => $job_id,
			'plan'            => $plan,
			'status'          => 'queued',
			'steps'           => $steps,
			'idempotency_key' => $idempotency_key,
			'source_url'      => $source_url,
			'note'            => $note,
			'created_by'      => get_current_user_id(),
			'created_at'      => $now,
			'updated_at'      => $now,
		);

		// WP option ベースの軽量ストアへ保存する。
		// update_option で autoload=false を明示し jobs-controller と方式を統一する。
		// add_option と異なり既存キーでも上書き可能だが、$job_id は UUID v4 のため衝突は理論上無視できる。
		$option_key = self::JOB_OPTION_PREFIX . $job_id;
		$saved      = update_option( $option_key, $job, false );

		if ( ! $saved ) {
			// 万一重複した場合（UUID 衝突は理論上無視できるが念のため）。
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( '移行ジョブの保存に失敗しました。再試行してください。', 'agent-neo-core' ),
				array( 'job_id' => $job_id )
			);
		}

		// 冪等性ストアに結果を保存する（同一キーの再送信対策）。
		$result = array(
			'job_id' => $job_id,
			'plan'   => $plan,
			'status' => 'queued',
			'steps'  => $steps,
		);
		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $result, $request_id )
		);
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * X-Request-Id ヘッダーを取得するか UUID v4 を生成する。
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
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Migration_Controller(
			$container->auth(),
			$container->idempotency_store()
		);
		$controller->register();
		$container->register_module( 'rest-migration' );
	}
);

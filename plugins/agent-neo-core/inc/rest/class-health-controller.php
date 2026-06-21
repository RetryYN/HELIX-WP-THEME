<?php
/**
 * GET /agent-neo/v1/health および /contracts controller。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ヘルス診断と OpenAPI 契約取得の REST endpoints。
 *
 * REQ-NF-025 準拠: AI 判断ロジック禁止。WP 内部状態の読み取りと
 * 決定的な診断集約のみを行う。LLM 呼び出しは一切行わない。
 */
final class Agent_Neo_Core_Health_Controller extends Agent_Neo_Core_REST_Controller_Base {
	/**
	 * loopback 接続確認のタイムアウト（秒）。
	 */
	private const LOOPBACK_TIMEOUT = 3;

	/**
	 * Schema loader。
	 *
	 * @var Agent_Neo_Core_Schema_Loader
	 */
	private Agent_Neo_Core_Schema_Loader $schema_loader;

	/**
	 * License state helper。
	 *
	 * @var Agent_Neo_Core_License_State
	 */
	private Agent_Neo_Core_License_State $license_state;

	/**
	 * @param Agent_Neo_Core_Schema_Loader $schema_loader Schema loader。
	 * @param Agent_Neo_Core_License_State $license_state License state。
	 */
	public function __construct(
		Agent_Neo_Core_Schema_Loader $schema_loader,
		Agent_Neo_Core_License_State $license_state
	) {
		$this->schema_loader = $schema_loader;
		$this->license_state = $license_state;
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
	 * /health と /contracts を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /health — 認証不要の読み取り専用診断。
		$this->register_agent_route(
			'/health',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_health' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /contracts — 認証必須の読み取り専用 OpenAPI 契約取得。
		$this->register_agent_route(
			'/contracts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_contracts' ),
				'permission_callback' => array( $this, 'check_contracts_permission' ),
			)
		);
	}

	/**
	 * GET /health — REST / DB / Cron / loopback / license を診断して返す。
	 *
	 * 各チェックは { status: ok|degraded|fail, detail: string } 形式。
	 * overall status はチェック結果の最悪値で集約する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response
	 */
	public function get_health( WP_REST_Request $request ): WP_REST_Response {
		$request_id = $this->resolve_request_id( $request );

		$checks = array(
			'db'       => $this->check_db(),
			'cron'     => $this->check_cron(),
			'loopback' => $this->check_loopback(),
			'license'  => $this->check_license(),
		);

		$overall = $this->aggregate_overall_status( $checks );

		$data = array(
			'status' => $overall,
			'checks' => $checks,
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * GET /contracts — OpenAPI 正本から登録済み endpoint 契約サマリを返す。
	 *
	 * AI がスキーマ確認に使う読み取り専用 endpoint。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_contracts( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );

		// openapi.yaml の内容を決定的に返す（AI 判断なし）。
		$openapi_path = $this->openapi_yaml_path();

		if ( '' === $openapi_path || ! is_readable( $openapi_path ) ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'OpenAPI schema file is not available.', 'agent-neo-core' )
			);
		}

		$raw = file_get_contents( $openapi_path );
		if ( false === $raw ) {
			return Agent_Neo_Core_Auth::error(
				'INTERNAL_ERROR',
				__( 'Failed to read OpenAPI schema file.', 'agent-neo-core' )
			);
		}

		// schema_loader の valid 状態と schema errors を添付する。
		$schema_valid  = $this->schema_loader->is_valid();
		$schema_errors = $this->schema_loader->get_errors();

		// 登録済み REST endpoint の agent-neo/v1 サマリを収集する。
		$endpoint_summary = $this->collect_registered_endpoints();

		// StandardResponse の error code enum を openapi.yaml から抽出する。
		$error_codes = $this->extract_error_code_enum( $raw );

		$data = array(
			'schema_valid'     => $schema_valid,
			'schema_errors'    => $schema_errors,
			'openapi_raw'      => $raw,
			'error_code_enum'  => $error_codes,
			'endpoints'        => $endpoint_summary,
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * GET /contracts の permission callback。
	 *
	 * ログイン済み + read capability を必要とする。
	 *
	 * @return true|WP_Error
	 */
	public function check_contracts_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO contracts.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'read' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability for AGENT NEO contracts.', 'agent-neo-core' )
			);
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// 診断チェック群（決定的な WP 内部状態読み取りのみ）
	// -------------------------------------------------------------------------

	/**
	 * DB の軽量疎通チェックを行う。
	 *
	 * @return array<string, string>
	 */
	private function check_db(): array {
		global $wpdb;

		// $wpdb->check_connection() は WP 3.9+ で利用可能。false の場合は fail。
		if ( method_exists( $wpdb, 'check_connection' ) ) {
			$connected = $wpdb->check_connection( false );
			if ( ! $connected ) {
				return array(
					'status' => 'fail',
					'detail' => 'wpdb connection check returned false',
				);
			}
		}

		// 軽量クエリで実際の読み書きパスを確認する。
		$result = $wpdb->get_var( "SELECT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( '1' !== (string) $result ) {
			return array(
				'status' => 'fail',
				'detail' => 'lightweight SELECT 1 query failed',
			);
		}

		return array(
			'status' => 'ok',
			'detail' => 'database connection healthy',
		);
	}

	/**
	 * WP Cron の状態を診断する。
	 *
	 * DISABLE_WP_CRON 定数が true の場合は外部 cron 運用を前提と見なし degraded 扱い。
	 * agent-neo-core のスケジュールイベントが登録されているかを確認する。
	 *
	 * @return array<string, string>
	 */
	private function check_cron(): array {
		// DISABLE_WP_CRON が true のときは WP 内部 cron が無効。
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return array(
				'status' => 'degraded',
				'detail' => 'DISABLE_WP_CRON is true; external cron is assumed',
			);
		}

		// agent-neo-core の定期チェックイベント有無を確認する。
		$event = wp_get_scheduled_event( 'agent_neo_core_license_check' );
		if ( false === $event ) {
			return array(
				'status' => 'degraded',
				'detail' => 'scheduled event agent_neo_core_license_check not found',
			);
		}

		return array(
			'status' => 'ok',
			'detail' => 'cron enabled and scheduled event found',
		);
	}

	/**
	 * REST API loopback 到達性を確認する。
	 *
	 * GET /agent-neo/v1/status に自己 HTTP リクエストを投げて到達性を診断する。
	 * タイムアウトは短め（LOOPBACK_TIMEOUT 秒）に設定する。
	 *
	 * @return array<string, string>
	 */
	private function check_loopback(): array {
		$url = rest_url( 'agent-neo/v1/status' );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => self::LOOPBACK_TIMEOUT,
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'fail',
				'detail' => 'loopback request failed: ' . $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// 200 or 401/403 は REST API が動作している証拠。
		if ( in_array( $code, array( 200, 401, 403 ), true ) ) {
			return array(
				'status' => 'ok',
				'detail' => sprintf( 'loopback reachable (HTTP %d)', $code ),
			);
		}

		return array(
			'status' => 'degraded',
			'detail' => sprintf( 'loopback returned unexpected HTTP %d', $code ),
		);
	}

	/**
	 * ライセンス状態を診断する。
	 *
	 * @return array<string, string>
	 */
	private function check_license(): array {
		$mode = $this->license_state->license_mode();

		if ( 'valid' === $mode ) {
			return array(
				'status' => 'ok',
				'detail' => 'license mode: valid',
			);
		}

		if ( 'grace' === $mode ) {
			return array(
				'status' => 'degraded',
				'detail' => 'license mode: grace period active',
			);
		}

		if ( 'readonly' === $mode ) {
			return array(
				'status' => 'degraded',
				'detail' => 'license mode: readonly (not yet activated)',
			);
		}

		// invalid / expired / その他。
		return array(
			'status' => 'fail',
			'detail' => sprintf( 'license mode: %s', sanitize_text_field( $mode ) ),
		);
	}

	// -------------------------------------------------------------------------
	// ユーティリティ
	// -------------------------------------------------------------------------

	/**
	 * チェック群の overall status を集約する。
	 *
	 * fail が 1 件でも → 'fail'、degraded が 1 件でも → 'degraded'、全 ok → 'ok'。
	 *
	 * @param array<string, array<string, string>> $checks チェック結果。
	 * @return string
	 */
	private function aggregate_overall_status( array $checks ): string {
		$overall = 'ok';

		foreach ( $checks as $check ) {
			$status = $check['status'] ?? 'ok';
			if ( 'fail' === $status ) {
				return 'fail';
			}
			if ( 'degraded' === $status ) {
				$overall = 'degraded';
			}
		}

		return $overall;
	}

	/**
	 * X-Request-Id ヘッダを取得し、なければ UUID を生成して返す。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return string
	 */
	private function resolve_request_id( WP_REST_Request $request ): string {
		$id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $id ) || '' === $id ) {
			return wp_generate_uuid4();
		}

		return sanitize_text_field( $id );
	}

	/**
	 * openapi.yaml の絶対パスを返す。
	 *
	 * schema ディレクトリは plugin root/schema/ に固定する。
	 *
	 * @return string
	 */
	private function openapi_yaml_path(): string {
		$plugin_dir = plugin_dir_path( dirname( __DIR__, 1 ) );
		$path       = $plugin_dir . 'schema/openapi.yaml';

		return is_readable( $path ) ? $path : '';
	}

	/**
	 * 登録済み agent-neo/v1 endpoint の概要を収集する。
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function collect_registered_endpoints(): array {
		$server = rest_get_server();
		if ( ! $server instanceof WP_REST_Server ) {
			return array();
		}

		$routes  = $server->get_routes( 'agent-neo/v1' );
		$summary = array();

		foreach ( $routes as $route => $handlers ) {
			if ( ! is_array( $handlers ) ) {
				continue;
			}

			$methods = array();
			foreach ( $handlers as $handler ) {
				if ( is_array( $handler ) && isset( $handler['methods'] ) && is_array( $handler['methods'] ) ) {
					foreach ( array_keys( $handler['methods'] ) as $method ) {
						if ( is_string( $method ) && ! in_array( $method, $methods, true ) ) {
							$methods[] = $method;
						}
					}
				}
			}

			$summary[] = array(
				'route'   => sanitize_text_field( $route ),
				'methods' => $methods,
			);
		}

		return $summary;
	}

	/**
	 * openapi.yaml の StandardResponse.error.code enum 値を抽出する。
	 *
	 * YAML をパースせず、テキストスキャンで enum 行を収集する（決定的処理）。
	 *
	 * @param string $yaml_raw openapi.yaml の生テキスト。
	 * @return array<int, string>
	 */
	private function extract_error_code_enum( string $yaml_raw ): array {
		$codes = array();
		$lines = explode( "\n", $yaml_raw );

		$in_enum = false;
		foreach ( $lines as $line ) {
			// enum セクション開始を検知する。
			if ( false !== strpos( $line, 'enum:' ) ) {
				$in_enum = true;
				continue;
			}

			if ( $in_enum ) {
				// enum エントリは "- CODE_NAME" の形式。
				if ( preg_match( '/^\s+-\s+([A-Z_]+)\s*$/', $line, $matches ) ) {
					$codes[] = sanitize_text_field( $matches[1] );
				} else {
					// インデントが外れたら enum セクション終了。
					if ( '' !== trim( $line ) && false === strpos( $line, '- ' ) ) {
						$in_enum = false;
					}
				}
			}
		}

		return $codes;
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Health_Controller(
			$container->schema_loader(),
			$container->license_state()
		);
		$controller->register();
		$container->register_module( 'rest-health' );
	}
);

<?php
/**
 * WP-CLI 操作面: wp agent-neo <subcommand>。
 *
 * 4操作面（REST / MCP / WP-CLI / React UI）は同一 JSON 契約に集約する（ADR-002/012）。
 * このクラスは REST controller が所有する契約・バリデーション・認証を完全再利用するため
 * 内部で rest_do_request() を呼ぶ薄いアダプタとして実装する。
 * 機能差異ゼロを構造的に保証する。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP-CLI が存在する場合のみ登録する。
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * AGENT NEO Core Plugin の WP-CLI コマンド群。
 *
 * ## EXAMPLES
 *
 *   wp agent-neo status
 *   wp agent-neo features --include=all
 *   wp agent-neo request GET /agent-neo/v1/status
 *   wp agent-neo request POST /agent-neo/v1/actions/dry-run --body='{"ops":[]}'
 */
class Agent_Neo_Core_CLI_Command {

	/**
	 * プラグイン REST namespace。
	 */
	private const REST_NAMESPACE = 'agent-neo/v1';

	/**
	 * CLI 実行時に使用する WordPress ユーザー ID。
	 * 0 の場合はカレントユーザーをそのまま使用する。
	 *
	 * @var int
	 */
	private int $cli_user_id = 0;

	// -------------------------------------------------------------------------
	// サブコマンド
	// -------------------------------------------------------------------------

	/**
	 * AGENT NEO Core Plugin の状態を表示する。
	 *
	 * 内部で GET /agent-neo/v1/status を rest_do_request() 経由で呼ぶ。
	 * REST と同一の StandardResponse 封筒を JSON で出力する。
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : 出力フォーマット。json（省略時）または yaml。
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *   wp agent-neo status
	 *   wp agent-neo status --format=yaml
	 *
	 * @param array<int, string>    $args       positional args（未使用）。
	 * @param array<string, string> $assoc_args named options。
	 * @return void
	 */
	public function status( array $args, array $assoc_args ): void {
		$response = $this->do_rest_request( 'GET', '/status' );
		$this->output_response( $response, $assoc_args );
	}

	/**
	 * パッケージ機能フラグ一覧を表示する。
	 *
	 * 内部で GET /agent-neo/v1/features を rest_do_request() 経由で呼ぶ。
	 *
	 * ## OPTIONS
	 *
	 * [--include=<include>]
	 * : 返却する機能フラグ種別。package（省略時）または all。
	 * ---
	 * default: package
	 * options:
	 *   - package
	 *   - all
	 * ---
	 *
	 * [--format=<format>]
	 * : 出力フォーマット。json（省略時）または yaml。
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *   wp agent-neo features
	 *   wp agent-neo features --include=all
	 *
	 * @param array<int, string>    $args       positional args（未使用）。
	 * @param array<string, string> $assoc_args named options。
	 * @return void
	 */
	public function features( array $args, array $assoc_args ): void {
		$params = array();
		if ( ! empty( $assoc_args['include'] ) ) {
			$params['include'] = sanitize_text_field( $assoc_args['include'] );
		}

		$response = $this->do_rest_request( 'GET', '/features', array(), $params );
		$this->output_response( $response, $assoc_args );
	}

	/**
	 * A/B テスト操作サブコマンド群。
	 *
	 * ## SUBCOMMANDS
	 *
	 *   stop — A/B テストを緊急停止し、以後 variant エンドポイントが default を返すようにする。
	 *
	 * ## EXAMPLES
	 *
	 *   wp agent-neo ab-test stop --post_id=42
	 *
	 * @param array<int, string>    $args       positional args。args[0] = サブコマンド名。
	 * @param array<string, string> $assoc_args named options。
	 * @return void
	 */
	public function ab_test( array $args, array $assoc_args ): void {
		$subcommand = isset( $args[0] ) ? $args[0] : '';

		if ( 'stop' !== $subcommand ) {
			WP_CLI::error(
				sprintf(
					'不明なサブコマンド "%s"。使用可能なサブコマンド: stop',
					$subcommand
				)
			);
		}

		$this->ab_test_stop( $assoc_args );
	}

	/**
	 * 指定投稿の A/B テストを緊急停止する。
	 *
	 * _agent_neo_ab_active を "false" に設定する。
	 * 以後、GET /agent-neo/v1/ab-test/variant は default variant を返す。
	 *
	 * ACC-024b（緊急停止 CLI）充足。
	 * REQ-NF-025 順守: AI 判定ロジックなし。meta フラグを書き換えるだけ。
	 *
	 * ## OPTIONS
	 *
	 * --post_id=<post_id>
	 * : 停止対象の投稿 ID。
	 *
	 * [--format=<format>]
	 * : 出力フォーマット。json（省略時）または yaml。
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *   wp agent-neo ab-test stop --post_id=42
	 *
	 * @param array<string, string> $assoc_args named options。
	 * @return void
	 */
	private function ab_test_stop( array $assoc_args ): void {
		if ( empty( $assoc_args['post_id'] ) || ! ctype_digit( (string) $assoc_args['post_id'] ) ) {
			WP_CLI::error( '--post_id に正の整数を指定してください。例: --post_id=42' );
		}

		$post_id = (int) $assoc_args['post_id'];
		if ( $post_id <= 0 ) {
			WP_CLI::error( '--post_id に正の整数を指定してください。' );
		}

		// rest_do_request() 経由で実行するため管理者ユーザーを確立する。
		$this->ensure_admin_user();

		// post の存在確認（get_post は 0 / 負値を正しく処理できない）。
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			WP_CLI::error( sprintf( '投稿 ID=%d が見つかりません。', $post_id ) );
		}

		// _agent_neo_ab_active を "false" に設定する。
		update_post_meta( $post_id, Agent_Neo_Core_AB_Test_Controller::META_ACTIVE, 'false' );

		/**
		 * A/B テスト緊急停止後に外部処理をトリガーするための action hook。
		 *
		 * @param int    $post_id    対象投稿 ID。
		 * @param string $stopped_at 停止日時（ISO 8601）。
		 */
		do_action( 'agent_neo_ab_test_stopped', $post_id, gmdate( 'c' ) );

		$format = $assoc_args['format'] ?? 'json';
		WP_CLI::print_value(
			array(
				'success' => true,
				'data'    => array(
					'post_id'        => $post_id,
					'ab_test_active' => false,
					'stopped_at'     => gmdate( 'c' ),
					'message'        => sprintf( '投稿 ID=%d の A/B テストを停止しました。以後 default variant を配信します。', $post_id ),
				),
				'meta'    => array(
					'request_id' => 'ab_stop_' . substr( hash( 'sha256', (string) $post_id . '|' . gmdate( 'c' ) ), 0, 24 ),
				),
				'error'   => null,
			),
			array( 'format' => $format )
		);
	}

	/**
	 * 任意の agent-neo/v1 ルートを rest_do_request() 経由で呼ぶ汎用コマンド。
	 *
	 * 同一契約の実証に有用。route は /agent-neo/v1/ から始まる絶対パス
	 * または /status のような relative パスの両方を受け付ける。
	 *
	 * ## OPTIONS
	 *
	 * <method>
	 * : HTTP メソッド（GET / POST / PUT / PATCH / DELETE）。
	 *
	 * <route>
	 * : REST ルートパス（例: /agent-neo/v1/status または /status）。
	 *
	 * [--body=<json>]
	 * : リクエストボディ（JSON 文字列）。POST / PUT / PATCH で使用する。
	 *
	 * [--param=<pair>]
	 * : クエリパラメータ（key=value 形式）。複数回指定可能（例: --param=include=all）。
	 *
	 * [--format=<format>]
	 * : 出力フォーマット。json（省略時）または yaml。
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *   wp agent-neo request GET /agent-neo/v1/status
	 *   wp agent-neo request GET /status
	 *   wp agent-neo request GET /features --param="include=all"
	 *   wp agent-neo request POST /actions/dry-run --body='{"ops":[],"diff_hash":"abc"}'
	 *
	 * @param array<int, string>    $args       positional args（method, route）。
	 * @param array<string, string> $assoc_args named options。
	 * @return void
	 */
	public function request( array $args, array $assoc_args ): void {
		if ( count( $args ) < 2 ) {
			WP_CLI::error( 'Usage: wp agent-neo request <METHOD> <route> [--body=<json>] [--param=<key=value>]' );
		}

		$method = strtoupper( $args[0] );
		$route  = $args[1];

		// /agent-neo/v1/ プレフィックスを正規化する。
		// 既に絶対パスなら使いそのまま、relative なら namespace プレフィックスを付ける。
		if ( ! str_starts_with( $route, '/' . self::REST_NAMESPACE ) ) {
			// /status → /agent-neo/v1/status に変換する。
			$route = '/' . self::REST_NAMESPACE . '/' . ltrim( $route, '/' );
		}

		// JSON ボディのパース。
		$body = array();
		if ( ! empty( $assoc_args['body'] ) ) {
			$decoded = json_decode( $assoc_args['body'], true );
			if ( null === $decoded && 'null' !== $assoc_args['body'] ) {
				WP_CLI::error( '--body に有効な JSON を指定してください。' );
			}

			$body = is_array( $decoded ) ? $decoded : array();
		}

		// クエリパラメータのパース（--param=key=value）。
		$query_params = array();
		if ( ! empty( $assoc_args['param'] ) ) {
			$raw_params = (array) $assoc_args['param'];
			foreach ( $raw_params as $pair ) {
				$parts = explode( '=', $pair, 2 );
				if ( 2 === count( $parts ) ) {
					$query_params[ $parts[0] ] = $parts[1];
				}
			}
		}

		$response = $this->do_rest_request( $method, $route, $body, $query_params, true );
		$this->output_response( $response, $assoc_args );
	}

	// -------------------------------------------------------------------------
	// 内部ヘルパー
	// -------------------------------------------------------------------------

	/**
	 * REST リクエストを内部で実行し、レスポンスデータと HTTP ステータスを返す。
	 *
	 * ADR-002/012 の同一契約原則に従い、認証・バリデーションは REST layer が担う。
	 * CLI 実行コンテキストでは管理者ユーザー（ID=1 以上）として wp_set_current_user() を行う。
	 *
	 * @param string               $method       HTTP メソッド。
	 * @param string               $route        REST ルートパス（/status や /agent-neo/v1/status）。
	 * @param array<string, mixed> $body         リクエストボディ。
	 * @param array<string, mixed> $query_params クエリパラメータ。
	 * @param bool                 $absolute     $route が絶対パスかどうか（true の場合は変換しない）。
	 * @return array{status: int, data: mixed}
	 */
	private function do_rest_request(
		string $method,
		string $route,
		array $body = array(),
		array $query_params = array(),
		bool $absolute = false
	): array {
		// CLI 実行ユーザーを確立する。
		// rest_do_request() は WordPress の認証レイヤーを経由するため、
		// CLI コンテキストで管理者権限を持つユーザーを設定する必要がある。
		$this->ensure_admin_user();

		// 絶対パスでない場合は namespace プレフィックスを付与する。
		if ( ! $absolute && ! str_starts_with( $route, '/' . self::REST_NAMESPACE ) ) {
			$route = '/' . self::REST_NAMESPACE . '/' . ltrim( $route, '/' );
		}

		$request = new WP_REST_Request( $method, $route );

		// クエリパラメータを設定する。
		foreach ( $query_params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		// POST / PUT / PATCH のボディを設定する。
		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
			$request->set_body_params( $body );
		}

		// rest_do_request() を実行する（REST layer が認証・バリデーションを担う）。
		$wp_response = rest_do_request( $request );
		$status      = $wp_response->get_status();
		$data        = $wp_response->get_data();

		return array(
			'status' => $status,
			'data'   => $data,
		);
	}

	/**
	 * CLI 実行時のユーザーを管理者に設定する。
	 *
	 * WP-CLI 経由で実行された場合、wp_get_current_user() は 0 を返すことがある。
	 * REST permission callback が is_user_logged_in() / current_user_can() を使うため
	 * 管理者ユーザー（`edit_posts` capability を持つ最初のユーザー）を設定する。
	 *
	 * @return void
	 */
	private function ensure_admin_user(): void {
		if ( $this->cli_user_id > 0 ) {
			wp_set_current_user( $this->cli_user_id );
			return;
		}

		// 既にログイン済みかつ edit_posts がある場合はそのまま使用する。
		if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			$this->cli_user_id = get_current_user_id();
			return;
		}

		// administrator ロールを持つユーザーを検索して設定する。
		$admins = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'fields'  => array( 'ID' ),
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);

		if ( empty( $admins ) ) {
			WP_CLI::error(
				'WordPress に administrator ロールを持つユーザーが存在しません。' .
				'wp user create <login> <email> --role=administrator で作成してください。'
			);
		}

		$this->cli_user_id = (int) $admins[0]->ID;
		wp_set_current_user( $this->cli_user_id );

		WP_CLI::debug(
			sprintf( 'CLI 実行ユーザーを administrator (ID=%d) に設定しました。', $this->cli_user_id ),
			'agent-neo'
		);
	}

	/**
	 * REST レスポンスを出力する。
	 *
	 * 4xx / 5xx は WP_CLI::error() で終了する（exit code != 0）。
	 * 成功時は REST と同一の StandardResponse 封筒を JSON / YAML で出力する。
	 *
	 * @param array{status: int, data: mixed} $response   do_rest_request の戻り値。
	 * @param array<string, string>           $assoc_args コマンドオプション。
	 * @return void
	 */
	private function output_response( array $response, array $assoc_args ): void {
		$status = $response['status'];
		$data   = $response['data'];

		// エラー応答: WP_CLI::error() で exit code 1 にする。
		if ( $status >= 400 ) {
			$code    = 'UNKNOWN_ERROR';
			$message = 'REST リクエストが ' . $status . ' を返しました。';

			if ( is_array( $data ) ) {
				if ( isset( $data['error']['code'] ) ) {
					$code = $data['error']['code'];
				}

				if ( isset( $data['error']['message'] ) ) {
					$message = $data['error']['message'];
				}

				// WordPress の標準エラー形式（WP_Error が直接シリアライズされた場合）。
				if ( isset( $data['code'] ) && isset( $data['message'] ) ) {
					$code    = $data['code'];
					$message = $data['message'];
				}
			}

			WP_CLI::error( sprintf( '[%s] %s (HTTP %d)', $code, $message, $status ) );
		}

		// 成功応答: REST と同一の StandardResponse 封筒をそのまま出力する。
		$format = $assoc_args['format'] ?? 'json';

		WP_CLI::print_value( $data, array( 'format' => $format ) );
	}
}

// WP-CLI にコマンドを登録する。
WP_CLI::add_command( 'agent-neo', Agent_Neo_Core_CLI_Command::class );

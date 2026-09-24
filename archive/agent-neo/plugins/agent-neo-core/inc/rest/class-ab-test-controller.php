<?php
/**
 * A/B テスト variant 配信・勝者受領 controller。
 *
 * REQ-F-024: AI 自律 A/B テスト機構（AGENT-NEO 側責務）
 * REQ-NF-025: variant 生成・統計判定・勝者選定は Automation SEO 側。
 *             本 controller は variant 配信 + 計測連携受け口 + 適用のみ行う。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A/B テスト variant 配信・勝者受領。
 *
 * エンドポイント:
 *   GET  /agent-neo/v1/ab-test/variant  — active variant 定義を返す（配信・AI 判定なし）
 *   POST /agent-neo/v1/ab-test/winner   — 勝者を受領し post meta に反映する
 *
 * post meta キー:
 *   _agent_neo_ab_variants  — array: Automation SEO が登録した variant 定義一覧
 *   _agent_neo_ab_active    — string|false: 現在の active variant_id（false = 停止中）
 *   _agent_neo_ab_archive   — array: 終了した variant の履歴
 */
final class Agent_Neo_Core_AB_Test_Controller extends Agent_Neo_Core_REST_Controller_Base {
	/**
	 * Meta キー: variant 定義一覧。
	 */
	public const META_VARIANTS = '_agent_neo_ab_variants';

	/**
	 * Meta キー: 現在の active variant_id（false = 停止中）。
	 */
	public const META_ACTIVE = '_agent_neo_ab_active';

	/**
	 * Meta キー: 終了 variant の履歴。
	 */
	public const META_ARCHIVE = '_agent_neo_ab_archive';

	/**
	 * variant_id / cta_id の最大文字数。
	 */
	private const MAX_ID_SIZE = 128;

	// -------------------------------------------------------------------------
	// 登録
	// -------------------------------------------------------------------------

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
		// GET /ab-test/variant — Automation SEO が HMAC 認証で呼ぶ配信エンドポイント。
		$this->register_agent_route(
			'/ab-test/variant',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_variant' ),
				'permission_callback' => array( $this, 'check_hmac_permission' ),
			)
		);

		// POST /ab-test/winner — Automation SEO が HMAC 認証で勝者を送信するエンドポイント。
		$this->register_agent_route(
			'/ab-test/winner',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'accept_winner' ),
				'permission_callback' => array( $this, 'check_hmac_permission' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * HMAC + site_token 署名検証（Automation SEO からの呼び出し用）。
	 *
	 * GET の場合はクエリパラメータ、POST の場合は JSON ボディを署名対象とする。
	 * 署名規約は tracking endpoint と同一の canonical_json 方式を採用する。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_hmac_permission( WP_REST_Request $request ) {
		// GET はクエリパラメータ、POST は JSON ボディから認証フィールドを取得する。
		if ( 'GET' === $request->get_method() ) {
			$params = $request->get_query_params();
		} else {
			$params = $request->get_json_params();
			if ( ! is_array( $params ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'JSON body is required.', 'agent-neo-core' ) );
			}
		}

		// 認証フィールドの存在確認。
		foreach ( array( 'site_token', 'signature', 'nonce' ) as $field ) {
			if ( empty( $params[ $field ] ) || ! is_string( $params[ $field ] ) ) {
				return Agent_Neo_Core_Auth::error(
					'SIGNATURE_INVALID',
					__( 'Tracking signature or nonce is invalid.', 'agent-neo-core' )
				);
			}
		}

		// nonce 形式チェック。
		$nonce = (string) $params['nonce'];
		if ( strlen( $nonce ) < 8 || strlen( $nonce ) > self::MAX_ID_SIZE || ! preg_match( '/^[A-Za-z0-9_.:-]+$/', $nonce ) ) {
			return Agent_Neo_Core_Auth::error(
				'SIGNATURE_INVALID',
				__( 'Tracking signature or nonce is invalid.', 'agent-neo-core' )
			);
		}

		// site_token / hmac_key を取得する。
		$secrets = $this->load_secrets();
		if ( is_wp_error( $secrets ) ) {
			return $secrets;
		}

		// site_token の照合。
		if ( ! hash_equals( $secrets['site_token'], (string) $params['site_token'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'SIGNATURE_INVALID',
				__( 'Tracking signature or nonce is invalid.', 'agent-neo-core' )
			);
		}

		// HMAC 検証。
		$verified = $this->verify_hmac( $request, $params, $secrets['hmac_key'] );
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * GET /ab-test/variant
	 *
	 * post_id と cta_id を受け取り、active variant 定義を返す。
	 * A/B テストが停止中（_agent_neo_ab_active = false または未設定）の場合は
	 * default variant を返す。
	 * AI による判定ロジックは一切持たない（REQ-NF-025）。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_variant( WP_REST_Request $request ) {
		// post_id バリデーション。
		$post_id = (int) $request->get_param( 'post_id' );
		if ( $post_id <= 0 ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'post_id is required and must be a positive integer.', 'agent-neo-core' ),
				array( 'field' => 'post_id' )
			);
		}

		// cta_id バリデーション。
		$cta_id = (string) ( $request->get_param( 'cta_id' ) ?? '' );
		if ( '' === $cta_id || strlen( $cta_id ) > self::MAX_ID_SIZE || ! preg_match( '/^[A-Za-z0-9_-]+$/', $cta_id ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'cta_id is required and must contain only alphanumeric, underscore, or hyphen characters.', 'agent-neo-core' ),
				array( 'field' => 'cta_id' )
			);
		}

		// post 存在確認。
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Post not found.', 'agent-neo-core' ) );
		}

		// variant 定義と active フラグを取得する。
		$variants_raw = get_post_meta( $post_id, self::META_VARIANTS, true );
		$active_raw   = get_post_meta( $post_id, self::META_ACTIVE, true );

		$variants = is_array( $variants_raw ) ? $variants_raw : array();

		// active フラグが false（文字列 "false" / 空文字 / PHP false）の場合は停止中。
		$is_active = $this->is_active_flag( $active_raw );

		// cta_id でフィルタリングする。
		$cta_variants = array_values(
			array_filter(
				$variants,
				static function ( $v ) use ( $cta_id ): bool {
					return is_array( $v ) && isset( $v['cta_id'] ) && $v['cta_id'] === $cta_id;
				}
			)
		);

		// active variant を選定する（AI 判定なし・単純な meta 読み出し）。
		$serving_variant = null;
		$active_id       = is_string( $active_raw ) && '' !== $active_raw && 'false' !== $active_raw ? $active_raw : '';

		if ( $is_active && '' !== $active_id ) {
			// active variant_id に一致する定義を探す。
			foreach ( $cta_variants as $v ) {
				if ( isset( $v['variant_id'] ) && $v['variant_id'] === $active_id ) {
					$serving_variant = $v;
					break;
				}
			}
		}

		// active variant が見つからない場合は default（is_default=true）を返す。
		if ( null === $serving_variant ) {
			foreach ( $cta_variants as $v ) {
				if ( ! empty( $v['is_default'] ) ) {
					$serving_variant = $v;
					break;
				}
			}
		}

		// default もない場合は定義の先頭要素を使う（フォールバック）。
		if ( null === $serving_variant && ! empty( $cta_variants ) ) {
			$serving_variant = $cta_variants[0];
		}

		$response_data = array(
			'post_id'         => $post_id,
			'cta_id'          => $cta_id,
			'ab_test_active'  => $is_active,
			'active_id'       => $active_id,
			'serving_variant' => $serving_variant,
			'all_variants'    => $cta_variants,
		);

		$request_id = 'abv_' . substr( hash( 'sha256', (string) $post_id . '|' . $cta_id . '|' . gmdate( 'c' ) ), 0, 32 );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $response_data, $request_id )
		);
	}

	/**
	 * POST /ab-test/winner
	 *
	 * Automation SEO から勝者の variant_id を受け取り、post meta に反映する。
	 * - 勝者 variant を default に昇格する（is_default = true）
	 * - 敗者 variant を archive meta に退避する
	 * - _agent_neo_ab_active を false に設定して A/B テストを終了する
	 *
	 * AI による勝者選定ロジックは一切持たない（REQ-NF-025）。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function accept_winner( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'JSON body is required.', 'agent-neo-core' ) );
		}

		// post_id バリデーション。
		if ( ! isset( $params['post_id'] ) || ! is_numeric( $params['post_id'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'post_id is required and must be a positive integer.', 'agent-neo-core' ),
				array( 'field' => 'post_id' )
			);
		}
		$post_id = (int) $params['post_id'];
		if ( $post_id <= 0 ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'post_id is required and must be a positive integer.', 'agent-neo-core' ),
				array( 'field' => 'post_id' )
			);
		}

		// winning_variant_id バリデーション。
		if ( ! isset( $params['winning_variant_id'] ) || ! is_string( $params['winning_variant_id'] ) || '' === trim( $params['winning_variant_id'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'winning_variant_id is required.', 'agent-neo-core' ),
				array( 'field' => 'winning_variant_id' )
			);
		}
		$winning_id = sanitize_text_field( $params['winning_variant_id'] );
		if ( strlen( $winning_id ) > self::MAX_ID_SIZE || ! preg_match( '/^[A-Za-z0-9_-]+$/', $winning_id ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'winning_variant_id contains invalid characters.', 'agent-neo-core' ),
				array( 'field' => 'winning_variant_id' )
			);
		}

		// post 存在確認。
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Post not found.', 'agent-neo-core' ) );
		}

		// 既存の variant 定義と archive を取得する。
		$variants_raw = get_post_meta( $post_id, self::META_VARIANTS, true );
		$archive_raw  = get_post_meta( $post_id, self::META_ARCHIVE, true );

		$variants = is_array( $variants_raw ) ? $variants_raw : array();
		$archive  = is_array( $archive_raw ) ? $archive_raw : array();

		// 勝者 variant が定義内に存在するか確認する。
		$winner_found = false;
		foreach ( $variants as $v ) {
			if ( is_array( $v ) && isset( $v['variant_id'] ) && $v['variant_id'] === $winning_id ) {
				$winner_found = true;
				break;
			}
		}

		if ( ! $winner_found ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'winning_variant_id not found in registered variants for this post.', 'agent-neo-core' ),
				array( 'field' => 'winning_variant_id', 'post_id' => $post_id )
			);
		}

		// variant 定義を更新する:
		// - 勝者: is_default = true
		// - 敗者: archive へ退避 + variants から除去
		$decided_at  = gmdate( 'c' );
		$new_variants = array();
		$archived     = array();

		foreach ( $variants as $v ) {
			if ( ! is_array( $v ) || ! isset( $v['variant_id'] ) ) {
				$new_variants[] = $v;
				continue;
			}

			if ( $v['variant_id'] === $winning_id ) {
				// 勝者: default に昇格。
				$v['is_default']  = true;
				$v['decided_at']  = $decided_at;
				$v['winner']      = true;
				$new_variants[]   = $v;
			} else {
				// 敗者: archive へ退避。
				$v['is_default']  = false;
				$v['decided_at']  = $decided_at;
				$v['winner']      = false;
				$archived[]       = $v;
			}
		}

		// archive に追記する（重複防止のため variant_id で上書き）。
		foreach ( $archived as $av ) {
			if ( ! isset( $av['variant_id'] ) ) {
				$archive[] = $av;
				continue;
			}
			// 既存 archive に同一 variant_id があれば上書きする。
			$replaced = false;
			foreach ( $archive as &$existing_av ) {
				if ( is_array( $existing_av ) && isset( $existing_av['variant_id'] ) && $existing_av['variant_id'] === $av['variant_id'] ) {
					$existing_av = $av;
					$replaced    = true;
					break;
				}
			}
			unset( $existing_av );
			if ( ! $replaced ) {
				$archive[] = $av;
			}
		}

		// post meta を保存する。
		update_post_meta( $post_id, self::META_VARIANTS, $new_variants );
		update_post_meta( $post_id, self::META_ACTIVE, 'false' );
		update_post_meta( $post_id, self::META_ARCHIVE, $archive );

		/**
		 * 勝者受領後に外部処理をトリガーするための action hook。
		 *
		 * @param int    $post_id     対象投稿 ID。
		 * @param string $winning_id  勝者 variant_id。
		 * @param string $decided_at  決定日時（ISO 8601）。
		 */
		do_action( 'agent_neo_ab_test_winner_accepted', $post_id, $winning_id, $decided_at );

		$request_id = 'abw_' . substr( hash( 'sha256', (string) $post_id . '|' . $winning_id . '|' . $decided_at ), 0, 32 );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'post_id'            => $post_id,
					'winning_variant_id' => $winning_id,
					'ab_test_active'     => false,
					'decided_at'         => $decided_at,
					'archived_count'     => count( $archived ),
				),
				$request_id
			)
		);
	}

	// -------------------------------------------------------------------------
	// 内部ヘルパー
	// -------------------------------------------------------------------------

	/**
	 * site_token / hmac_key を option / 環境変数から読み込む。
	 *
	 * @return array{site_token:string,hmac_key:string}|WP_Error
	 */
	private function load_secrets() {
		$site_token = $this->first_non_empty(
			array(
				$this->get_env( 'AGENT_NEO_SITE_TOKEN' ),
				$this->get_env( 'AGENT_NEO_TRACKING_SITE_TOKEN' ),
				get_option( 'agent_neo_site_token', '' ),
				get_option( 'agent_neo_tracking_site_token', '' ),
			)
		);

		$hmac_key = $this->first_non_empty(
			array(
				$this->get_env( 'AGENT_NEO_TRACKING_HMAC_KEY' ),
				$this->get_env( 'AGENT_NEO_HMAC_KEY' ),
				get_option( 'agent_neo_tracking_hmac_key', '' ),
				get_option( 'agent_neo_hmac_key', '' ),
			)
		);

		if ( '' === $site_token || '' === $hmac_key ) {
			return Agent_Neo_Core_Auth::error(
				'SIGNATURE_INVALID',
				__( 'Tracking signature or nonce is invalid.', 'agent-neo-core' )
			);
		}

		return array(
			'site_token' => $site_token,
			'hmac_key'   => $hmac_key,
		);
	}

	/**
	 * HMAC 署名を検証する。
	 *
	 * 署名対象 payload は tracking endpoint と同一の canonical_json 方式を使用する。
	 * canonical path は /agent-neo/v1/ab-test/variant で統一する（GET/POST 両方）。
	 *
	 * @param WP_REST_Request     $request  Request。
	 * @param array<string,mixed> $params   パラメータ（クエリ or JSON ボディ）。
	 * @param string              $hmac_key HMAC key。
	 * @return true|WP_Error
	 */
	private function verify_hmac( WP_REST_Request $request, array $params, string $hmac_key ) {
		$provided = $this->normalize_signature( (string) $params['signature'] );

		// signature フィールドを除いた canonical JSON でペイロードを構成する。
		$body = $params;
		unset( $body['signature'] );
		$canonical = $this->canonical_json( $body );

		$payload = implode(
			'|',
			array(
				$request->get_method(),
				'/agent-neo/v1/ab-test/variant',
				(string) $params['nonce'],
				hash( 'sha256', $canonical ),
			)
		);

		$raw      = hash_hmac( 'sha256', $payload, $hmac_key, true );
		$accepted = array(
			hash_hmac( 'sha256', $payload, $hmac_key ),
			base64_encode( $raw ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		);

		foreach ( $accepted as $signature ) {
			if ( hash_equals( $signature, $provided ) ) {
				return true;
			}
		}

		return Agent_Neo_Core_Auth::error(
			'SIGNATURE_INVALID',
			__( 'Tracking signature or nonce is invalid.', 'agent-neo-core' )
		);
	}

	/**
	 * _agent_neo_ab_active meta 値が active かどうかを判定する。
	 *
	 * @param mixed $raw_value get_post_meta の戻り値。
	 * @return bool
	 */
	private function is_active_flag( $raw_value ): bool {
		// 未設定（空文字）/ false / "false" = 停止中。
		if ( false === $raw_value || '' === $raw_value || 'false' === $raw_value ) {
			return false;
		}

		// variant_id 文字列が入っている場合は active。
		return is_string( $raw_value ) && '' !== $raw_value;
	}

	/**
	 * Canonical JSON を生成する（tracking endpoint と同一ロジック）。
	 *
	 * @param mixed $value Value。
	 * @return string
	 */
	private function canonical_json( $value ): string {
		$value = $this->sort_recursive( $value );
		$json  = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return is_string( $json ) ? $json : '';
	}

	/**
	 * 配列 key を再帰 sort する。
	 *
	 * @param mixed $value Value。
	 * @return mixed
	 */
	private function sort_recursive( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $item ) {
			$value[ $key ] = $this->sort_recursive( $item );
		}

		if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			ksort( $value );
		}

		return $value;
	}

	/**
	 * 署名表記を正規化する。
	 *
	 * @param string $signature Signature。
	 * @return string
	 */
	private function normalize_signature( string $signature ): string {
		$signature = trim( $signature );
		if ( str_starts_with( $signature, 'sha256=' ) ) {
			return substr( $signature, 7 );
		}

		return $signature;
	}

	/**
	 * 最初の non-empty string を返す。
	 *
	 * @param array<mixed> $values Values。
	 * @return string
	 */
	private function first_non_empty( array $values ): string {
		foreach ( $values as $value ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return trim( $value );
			}
		}

		return '';
	}

	/**
	 * 環境変数を取得する。
	 *
	 * @param string $name 環境変数名。
	 * @return string
	 */
	private function get_env( string $name ): string {
		$value = getenv( $name );
		return is_string( $value ) ? $value : '';
	}
}

// glob 自己登録: bootstrap.php の glob( 'inc/rest/*-controller.php' ) で自動 require される。
add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_AB_Test_Controller();
		$controller->register();
		$container->register_module( 'rest-ab-test' );
	}
);

<?php
/**
 * GET /design-tokens, POST /design-tokens/apply, PATCH /batch controller。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * デザイントークン取得・更新・バッチ操作 endpoint。
 *
 * REQ-F-009: color / font / spacing を option に保存し、
 * theme.json settings を決定的なフォールバックとして使う。
 * REQ-F-026: PATCH /batch で最大 20 件の操作を受け付け、部分失敗を分離返却する。
 * REQ-NF-025: AI ロジック禁止。静的適用と WP read のみ。
 */
final class Agent_Neo_Core_Design_Tokens_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/** デザイントークンを保存する option キー。 */
	private const OPTION_KEY = 'agent_neo_core_design_tokens';

	/** ロールバックポイントを保存する option キー。 */
	private const ROLLBACK_OPTION_KEY = 'agent_neo_core_design_tokens_rollback';

	/** ロールバック保持上限。 */
	private const MAX_ROLLBACKS = 30;

	/** バッチ操作の最大件数。 */
	private const BATCH_MAX_OPERATIONS = 20;

	/** バッチ operation で許容する op 種別。 */
	private const ALLOWED_OPS = array( 'add', 'remove', 'replace' );

	/** バッチ operation で許容する path プレフィックス（トークングループ）。 */
	private const ALLOWED_PATHS = array( '/color', '/font', '/spacing' );

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
	 * @var Agent_Neo_Core_Audit_Log
	 */
	private Agent_Neo_Core_Audit_Log $audit_log;

	/**
	 * @param Agent_Neo_Core_Auth              $auth Auth helper。
	 * @param Agent_Neo_Core_JSON_Patch        $json_patch JSON diff helper。
	 * @param Agent_Neo_Core_Idempotency_Store $idempotency_store Idempotency store。
	 * @param Agent_Neo_Core_Audit_Log         $audit_log Audit log。
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_JSON_Patch $json_patch,
		Agent_Neo_Core_Idempotency_Store $idempotency_store,
		Agent_Neo_Core_Audit_Log $audit_log
	) {
		$this->auth              = $auth;
		$this->json_patch        = $json_patch;
		$this->idempotency_store = $idempotency_store;
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
		// GET /design-tokens — デザイントークン取得。
		$this->register_agent_route(
			'/design-tokens',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tokens' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// POST /design-tokens/apply — デザイントークン更新。
		$this->register_agent_route(
			'/design-tokens/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_tokens' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		// PATCH /batch — バッチ操作（最大 20 件）。PATCH のみ受理。
		$this->register_agent_route(
			'/batch',
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'batch' ),
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
				__( 'Authentication required for AGENT NEO design tokens.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'read' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot read AGENT NEO design tokens.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * Write permission を確認する（nonce + edit_posts）。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		return $this->auth->check_write_permission( $request, 'edit_posts' );
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * GET /design-tokens。
	 *
	 * 保存済み option を返す。未保存の場合は active theme の theme.json settings
	 * から color / font / spacing を決定的に導出して返す（option には保存しない）。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response
	 */
	public function get_tokens( WP_REST_Request $request ): WP_REST_Response {
		$request_id = $this->resolve_request_id( $request );
		$tokens     = $this->load_tokens();

		$data = array(
			'tokens'     => $tokens,
			'source'     => $this->tokens_source(),
			'updated_at' => $this->tokens_updated_at(),
		);

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $data, $request_id ) );
	}

	/**
	 * POST /design-tokens/apply。
	 *
	 * color / font / spacing を option に保存する。
	 * diff_hash 不一致は PRECONDITION_FAILED。idempotency_key で冪等性を保証する。
	 * apply 前後の diff と rollback_point_id を返す。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function apply_tokens( WP_REST_Request $request ) {
		// --- JSON body 検証 ---
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		// --- 入力 sanitize・バリデーション ---
		$payload = $this->normalize_token_payload( $params );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		// --- request_id / idempotency_key 解決 ---
		$request_id      = $this->resolve_request_id( $request );
		$idempotency_key = $this->resolve_idempotency_key( $params, $request_id, 'dt_apply' );

		// --- 現在値取得 & diff_hash 検証 ---
		$before    = $this->load_tokens();
		$after     = $this->merged_tokens( $before, $payload );
		$diff      = $this->json_patch->diff( $before, $after );
		$diff_hash = $this->json_patch->diff_hash( $diff );

		if (
			isset( $params['diff_hash'] ) &&
			is_string( $params['diff_hash'] ) &&
			'' !== $params['diff_hash'] &&
			! hash_equals( $params['diff_hash'], $diff_hash )
		) {
			return Agent_Neo_Core_Auth::error(
				'PRECONDITION_FAILED',
				__( 'Design token diff_hash does not match current state.', 'agent-neo-core' ),
				array( 'expected' => $diff_hash )
			);
		}

		// --- idempotency_key チェック ---
		$payload_hash  = $this->idempotency_store->payload_hash(
			array(
				'payload'       => $payload,
				'computed_hash' => $diff_hash,
			)
		);
		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}

		if ( is_array( $stored_result ) ) {
			$stored_result['applied']     = false;
			$stored_result['warnings'][]  = array(
				'code'    => 'IDEMPOTENT_REPLAY',
				'message' => 'Design token result returned without reapplying.',
			);
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, $request_id ) );
		}

		// --- rollback snapshot & 保存 ---
		$rollback_point = $this->snapshot_tokens( $before, $request_id );
		$save_result    = $this->save_tokens( $after );
		if ( is_wp_error( $save_result ) ) {
			return $save_result;
		}

		// --- audit 記録 ---
		$audit_id = $this->audit_log->record(
			'design_tokens_apply',
			$request_id,
			$diff_hash,
			$idempotency_key,
			array(
				'rollback_point_id' => $rollback_point['rollback_point_id'],
			)
		);

		$result = array(
			'applied'           => true,
			'diff_hash'         => $diff_hash,
			'diff'              => $diff,
			'tokens'            => $this->load_tokens(),
			'rollback_point'    => $rollback_point,
			'rollback_point_id' => $rollback_point['rollback_point_id'],
			'request_id'        => $request_id,
			'audit_id'          => $audit_id,
			'warnings'          => array(),
		);

		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
	}

	/**
	 * PATCH /batch。
	 *
	 * operations 配列（最大 20 件）を順に検証・適用する。
	 * 21 件以上は VALIDATION_ERROR。部分失敗時は成功/失敗を分離して per-item 結果を返す。
	 * 全体レスポンスは HTTP 200。idempotency_key 必須。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function batch( WP_REST_Request $request ) {
		// --- JSON body 検証 ---
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		// --- idempotency_key 必須チェック ---
		if ( empty( $params['idempotency_key'] ) || ! is_string( $params['idempotency_key'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'idempotency_key is required for batch operations.', 'agent-neo-core' ),
				array( 'field' => 'idempotency_key' )
			);
		}

		// --- operations 必須チェック ---
		if ( ! isset( $params['operations'] ) || ! is_array( $params['operations'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'operations must be an array.', 'agent-neo-core' ),
				array( 'field' => 'operations' )
			);
		}

		$operations = $params['operations'];

		// --- 最大 20 件チェック ---
		if ( count( $operations ) > self::BATCH_MAX_OPERATIONS ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				sprintf(
					/* translators: %d: max operations */
					__( 'Batch operations must not exceed %d items.', 'agent-neo-core' ),
					self::BATCH_MAX_OPERATIONS
				),
				array(
					'field'   => 'operations',
					'limit'   => self::BATCH_MAX_OPERATIONS,
					'received' => count( $operations ),
				)
			);
		}

		// --- request_id / idempotency_key 解決 ---
		$request_id      = $this->resolve_request_id( $request );
		$idempotency_key = (string) $params['idempotency_key'];

		// --- idempotency_key チェック ---
		$payload_hash  = $this->idempotency_store->payload_hash(
			array(
				'idempotency_key' => $idempotency_key,
				'operations'      => $operations,
			)
		);
		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}

		if ( is_array( $stored_result ) ) {
			$stored_result['replayed'] = true;
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, $request_id ) );
		}

		// --- 各 operation を順に検証・適用（部分失敗を分離）---
		$results       = array();
		$success_count = 0;
		$failure_count = 0;

		// rollback 用に適用前の全体スナップショットを 1 回だけ取る。
		$before_batch   = $this->load_tokens();
		$current_tokens = $before_batch;

		foreach ( $operations as $index => $op ) {
			$op_result = $this->apply_single_batch_op( $current_tokens, $op, $index );
			if ( is_wp_error( $op_result ) ) {
				$results[] = array(
					'index'   => $index,
					'status'  => 'failure',
					'error'   => array(
						'code'    => $op_result->get_error_code(),
						'message' => $op_result->get_error_message(),
					),
				);
				++$failure_count;
			} else {
				$current_tokens = $op_result;
				$results[]      = array(
					'index'  => $index,
					'status' => 'success',
				);
				++$success_count;
			}
		}

		// 1 件でも成功した operation があれば保存する。
		$rollback_point = null;
		$audit_id       = '';
		if ( $success_count > 0 ) {
			$diff      = $this->json_patch->diff( $before_batch, $current_tokens );
			$diff_hash = $this->json_patch->diff_hash( $diff );

			$rollback_point  = $this->snapshot_tokens( $before_batch, $request_id );
			$save_result     = $this->save_tokens( $current_tokens );
			if ( is_wp_error( $save_result ) ) {
				return $save_result;
			}

			$audit_id = $this->audit_log->record(
				'design_tokens_batch',
				$request_id,
				$diff_hash,
				$idempotency_key,
				array(
					'success_count'     => $success_count,
					'failure_count'     => $failure_count,
					'rollback_point_id' => is_array( $rollback_point ) ? $rollback_point['rollback_point_id'] : '',
				)
			);
		}

		$result = array(
			'results'       => $results,
			'success_count' => $success_count,
			'failure_count' => $failure_count,
			'tokens'        => $this->load_tokens(),
			'rollback_point' => $rollback_point,
			'request_id'    => $request_id,
			'audit_id'      => $audit_id,
		);

		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
	}

	// -------------------------------------------------------------------------
	// Private helpers — token storage
	// -------------------------------------------------------------------------

	/**
	 * 保存済みトークンを返す。未保存なら theme.json から導出する。
	 *
	 * @return array<string, mixed>
	 */
	private function load_tokens(): array {
		$raw = get_option( self::OPTION_KEY );
		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		if ( is_array( $raw ) ) {
			return $raw;
		}

		// フォールバック: active theme の theme.json から決定的に導出する。
		return $this->derive_from_theme_json();
	}

	/**
	 * トークンを option に保存する。
	 *
	 * update_option は値不変時も false を返すため、保存後に get_option で再読込し
	 * 値が一致しない場合のみ CONFLICT を返す（seo-controller の手本に倣う）。
	 *
	 * @param array<string, mixed> $tokens Tokens。
	 * @return true|WP_Error
	 */
	private function save_tokens( array $tokens ) {
		$tokens['source']     = 'agent_neo_core';
		$tokens['updated_at'] = gmdate( 'c' );

		$encoded = wp_json_encode( $tokens, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$saved   = update_option( self::OPTION_KEY, $encoded, false );
		// update_option は値不変でも false を返すため、保存後に再読込して比較する。
		$stored  = get_option( self::OPTION_KEY );
		if ( false === $saved && $stored !== $encoded ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'Design tokens could not be persisted.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * 現在の保存ソースを返す。
	 *
	 * @return string
	 */
	private function tokens_source(): string {
		$raw = get_option( self::OPTION_KEY );
		if ( $raw ) {
			$decoded = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
			if ( is_array( $decoded ) && isset( $decoded['source'] ) ) {
				return (string) $decoded['source'];
			}
		}

		return 'theme_json';
	}

	/**
	 * 最終更新日時を返す。
	 *
	 * @return string
	 */
	private function tokens_updated_at(): string {
		$raw = get_option( self::OPTION_KEY );
		if ( $raw ) {
			$decoded = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
			if ( is_array( $decoded ) && isset( $decoded['updated_at'] ) ) {
				return (string) $decoded['updated_at'];
			}
		}

		return '';
	}

	/**
	 * active theme の theme.json settings から color / font / spacing を決定的に導出する。
	 *
	 * REQ-NF-025 準拠: AI ロジック不使用。静的な WP read のみ。
	 *
	 * @return array<string, mixed>
	 */
	private function derive_from_theme_json(): array {
		$theme_json = WP_Theme_JSON_Resolver::get_merged_data();
		$settings   = $theme_json->get_settings();

		// color パレット。
		$color = array();
		if ( isset( $settings['color']['palette'] ) && is_array( $settings['color']['palette'] ) ) {
			foreach ( $settings['color']['palette'] as $entry ) {
				if ( isset( $entry['slug'], $entry['color'] ) && is_string( $entry['slug'] ) && is_string( $entry['color'] ) ) {
					$color[ sanitize_key( $entry['slug'] ) ] = sanitize_hex_color( $entry['color'] ) ?? '';
				}
			}
		}

		// typography / font family。
		$font = array();
		if ( isset( $settings['typography']['fontFamilies'] ) && is_array( $settings['typography']['fontFamilies'] ) ) {
			foreach ( $settings['typography']['fontFamilies'] as $entry ) {
				if ( isset( $entry['slug'], $entry['fontFamily'] ) && is_string( $entry['slug'] ) && is_string( $entry['fontFamily'] ) ) {
					$font[ sanitize_key( $entry['slug'] ) ] = sanitize_text_field( $entry['fontFamily'] );
				}
			}
		}

		// spacing scale（preset ベース）。
		$spacing = array();
		if ( isset( $settings['spacing']['spacingSizes'] ) && is_array( $settings['spacing']['spacingSizes'] ) ) {
			foreach ( $settings['spacing']['spacingSizes'] as $entry ) {
				if ( isset( $entry['slug'], $entry['size'] ) && is_string( $entry['slug'] ) && is_string( $entry['size'] ) ) {
					$spacing[ sanitize_key( $entry['slug'] ) ] = sanitize_text_field( $entry['size'] );
				}
			}
		}

		return array(
			'color'   => $color,
			'font'    => $font,
			'spacing' => $spacing,
			'source'  => 'theme_json',
		);
	}

	/**
	 * before トークンに payload を適用して after を返す。
	 *
	 * @param array<string, mixed> $before Before tokens。
	 * @param array<string, mixed> $payload Sanitized payload。
	 * @return array<string, mixed>
	 */
	private function merged_tokens( array $before, array $payload ): array {
		$after = $before;

		foreach ( array( 'color', 'font', 'spacing' ) as $group ) {
			if ( isset( $payload[ $group ] ) && is_array( $payload[ $group ] ) ) {
				$current        = isset( $after[ $group ] ) && is_array( $after[ $group ] ) ? $after[ $group ] : array();
				// array_merge は数値キー（spacing の "10" 等）を 0 起点へ再割当てし
				// slug が消えるため、キー保存の array_replace でマージする。
				$after[ $group ] = array_replace( $current, $payload[ $group ] );
			}
		}

		return $after;
	}

	// -------------------------------------------------------------------------
	// Private helpers — validation
	// -------------------------------------------------------------------------

	/**
	 * POST /design-tokens/apply payload を検証・sanitize する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return array<string, mixed>|WP_Error
	 */
	private function normalize_token_payload( array $params ) {
		// color / font / spacing が 1 つも無ければエラー。
		$has_group = false;
		foreach ( array( 'color', 'font', 'spacing' ) as $group ) {
			if ( isset( $params[ $group ] ) ) {
				$has_group = true;
				break;
			}
		}

		if ( ! $has_group ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'At least one of color, font, or spacing is required.', 'agent-neo-core' ),
				array( 'fields' => array( 'color', 'font', 'spacing' ) )
			);
		}

		$payload = array();

		// color の sanitize（hex color）。
		if ( isset( $params['color'] ) ) {
			if ( ! is_array( $params['color'] ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'color must be an object.', 'agent-neo-core' ),
					array( 'field' => 'color' )
				);
			}
			$sanitized_color = array();
			foreach ( $params['color'] as $slug => $value ) {
				if ( ! is_string( $slug ) || ! is_string( $value ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'color keys and values must be strings.', 'agent-neo-core' ),
						array( 'field' => 'color' )
					);
				}
				$hex = sanitize_hex_color( $value );
				if ( null === $hex ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						sprintf(
							/* translators: %s: color slug */
							__( 'color[%s] must be a valid hex color.', 'agent-neo-core' ),
							sanitize_key( $slug )
						),
						array(
							'field' => 'color',
							'slug'  => sanitize_key( $slug ),
						)
					);
				}
				$sanitized_color[ sanitize_key( $slug ) ] = $hex;
			}
			$payload['color'] = $sanitized_color;
		}

		// font の sanitize（テキスト）。
		if ( isset( $params['font'] ) ) {
			if ( ! is_array( $params['font'] ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'font must be an object.', 'agent-neo-core' ),
					array( 'field' => 'font' )
				);
			}
			$sanitized_font = array();
			foreach ( $params['font'] as $slug => $value ) {
				if ( ! is_string( $slug ) || ! is_string( $value ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'font keys and values must be strings.', 'agent-neo-core' ),
						array( 'field' => 'font' )
					);
				}
				$sanitized_font[ sanitize_key( $slug ) ] = sanitize_text_field( $value );
			}
			$payload['font'] = $sanitized_font;
		}

		// spacing の sanitize（テキスト — CSS value を許容）。
		if ( isset( $params['spacing'] ) ) {
			if ( ! is_array( $params['spacing'] ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'spacing must be an object.', 'agent-neo-core' ),
					array( 'field' => 'spacing' )
				);
			}
			$sanitized_spacing = array();
			foreach ( $params['spacing'] as $slug => $value ) {
				// JSON の数値文字列キー（"10" 等 — theme.json の spacing slug 既定値）は
				// PHP の配列で int キーへ変換されるため、文字列へ戻してから検査する。
				$slug = is_int( $slug ) ? (string) $slug : $slug;
				if ( ! is_string( $slug ) || ! is_string( $value ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'spacing keys and values must be strings.', 'agent-neo-core' ),
						array( 'field' => 'spacing' )
					);
				}
				$sanitized_spacing[ sanitize_key( $slug ) ] = sanitize_text_field( $value );
			}
			$payload['spacing'] = $sanitized_spacing;
		}

		return $payload;
	}

	/**
	 * バッチの 1 operation を検証・適用する。
	 *
	 * @param array<string, mixed> $current_tokens 現在のトークン状態。
	 * @param mixed                $op Operation。
	 * @param int                  $index Operation インデックス（エラー詳細用）。
	 * @return array<string, mixed>|WP_Error 成功時は適用後のトークン、失敗時は WP_Error。
	 */
	private function apply_single_batch_op( array $current_tokens, $op, int $index ) {
		// op が連想配列であることを確認する。
		if ( ! is_array( $op ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'Each operation must be an object.', 'agent-neo-core' ),
				array( 'index' => $index )
			);
		}

		// op フィールドの検証。
		if ( empty( $op['op'] ) || ! is_string( $op['op'] ) || ! in_array( $op['op'], self::ALLOWED_OPS, true ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				sprintf(
					/* translators: %s: allowed ops list */
					__( 'Operation op must be one of: %s.', 'agent-neo-core' ),
					implode( ', ', self::ALLOWED_OPS )
				),
				array(
					'index' => $index,
					'field' => 'op',
				)
			);
		}

		// path フィールドの検証（/color/*, /font/*, /spacing/* のみ許容）。
		if ( empty( $op['path'] ) || ! is_string( $op['path'] ) || ! str_starts_with( $op['path'], '/' ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'Operation path must be a JSON Pointer starting with /.', 'agent-neo-core' ),
				array(
					'index' => $index,
					'field' => 'path',
				)
			);
		}

		$path_allowed = false;
		foreach ( self::ALLOWED_PATHS as $prefix ) {
			if ( str_starts_with( $op['path'], $prefix ) ) {
				$path_allowed = true;
				break;
			}
		}

		if ( ! $path_allowed ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				sprintf(
					/* translators: %s: allowed path prefixes */
					__( 'Operation path must start with one of: %s.', 'agent-neo-core' ),
					implode( ', ', self::ALLOWED_PATHS )
				),
				array(
					'index' => $index,
					'field' => 'path',
					'path'  => $op['path'],
				)
			);
		}

		// add / replace は value 必須。
		if ( in_array( $op['op'], array( 'add', 'replace' ), true ) && ! array_key_exists( 'value', $op ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'Operation value is required for add/replace.', 'agent-neo-core' ),
				array(
					'index' => $index,
					'field' => 'value',
				)
			);
		}

		// path を分解して group / key を取得する（/group/key 形式）。
		$parts = explode( '/', ltrim( $op['path'], '/' ), 2 );
		$group = $parts[0];
		$key   = isset( $parts[1] ) ? $parts[1] : '';

		// group がトークン配列として存在しない場合は初期化する。
		$group_tokens = isset( $current_tokens[ $group ] ) && is_array( $current_tokens[ $group ] )
			? $current_tokens[ $group ]
			: array();

		switch ( $op['op'] ) {
			case 'add':
			case 'replace':
				// value の sanitize（group 別）。
				$value = isset( $op['value'] ) ? $op['value'] : null;
				if ( 'color' === $group ) {
					if ( ! is_string( $value ) ) {
						return Agent_Neo_Core_Auth::error(
							'VALIDATION_ERROR',
							__( 'color value must be a string.', 'agent-neo-core' ),
							array( 'index' => $index, 'path' => $op['path'] )
						);
					}
					$sanitized = sanitize_hex_color( $value );
					if ( null === $sanitized ) {
						return Agent_Neo_Core_Auth::error(
							'VALIDATION_ERROR',
							__( 'color value must be a valid hex color.', 'agent-neo-core' ),
							array( 'index' => $index, 'path' => $op['path'] )
						);
					}
					$group_tokens[ sanitize_key( $key ) ] = $sanitized;
				} else {
					// font / spacing はテキスト sanitize。
					if ( ! is_string( $value ) ) {
						return Agent_Neo_Core_Auth::error(
							'VALIDATION_ERROR',
							__( 'Value must be a string.', 'agent-neo-core' ),
							array( 'index' => $index, 'path' => $op['path'] )
						);
					}
					$group_tokens[ sanitize_key( $key ) ] = sanitize_text_field( $value );
				}
				break;

			case 'remove':
				if ( '' === $key ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'Cannot remove an entire token group; specify a key.', 'agent-neo-core' ),
						array( 'index' => $index, 'path' => $op['path'] )
					);
				}
				unset( $group_tokens[ sanitize_key( $key ) ] );
				break;
		}

		$updated_tokens           = $current_tokens;
		$updated_tokens[ $group ] = $group_tokens;

		return $updated_tokens;
	}

	// -------------------------------------------------------------------------
	// Private helpers — rollback snapshot
	// -------------------------------------------------------------------------

	/**
	 * 適用前のトークンを rollback option に追記する。
	 *
	 * @param array<string, mixed> $before Before tokens。
	 * @param string               $request_id Request id。
	 * @return array<string, mixed>
	 */
	private function snapshot_tokens( array $before, string $request_id ): array {
		$points = get_option( self::ROLLBACK_OPTION_KEY );
		if ( ! is_array( $points ) ) {
			$raw_option = is_string( $points ) ? json_decode( $points, true ) : null;
			$points     = is_array( $raw_option ) ? $raw_option : array();
		}

		$point = array(
			'rollback_point_id' => 'dt_rb_' . wp_generate_uuid4(),
			'tokens'            => $before,
			'request_id'        => $request_id,
			'created_at'        => gmdate( 'c' ),
		);

		$points[] = $point;

		if ( count( $points ) > self::MAX_ROLLBACKS ) {
			$points = array_slice( $points, -1 * self::MAX_ROLLBACKS );
		}

		// autoload=false: ロールバックポイントは起動時に読み込まない。
		update_option( self::ROLLBACK_OPTION_KEY, $points, false );

		return $point;
	}

	// -------------------------------------------------------------------------
	// Private helpers — request utilities
	// -------------------------------------------------------------------------

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
	 * X-Request-Id ヘッダーを UUID v4 検証して返す。不正形式は新規生成する。
	 *
	 * @param WP_REST_Request $request Request。
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
	 * UUIDv4 形式か判定する。
	 *
	 * @param string $value Value。
	 * @return bool
	 */
	private function is_uuid_v4( string $value ): bool {
		return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{12}$/i', $value );
	}

	/**
	 * params['idempotency_key'] または prefix + request_id を返す。
	 *
	 * @param array<string, mixed> $params Params。
	 * @param string               $request_id Request id。
	 * @param string               $prefix Prefix for auto-generated key。
	 * @return string
	 */
	private function resolve_idempotency_key( array $params, string $request_id, string $prefix ): string {
		if ( isset( $params['idempotency_key'] ) && is_string( $params['idempotency_key'] ) && '' !== $params['idempotency_key'] ) {
			return $params['idempotency_key'];
		}

		return $prefix . '_' . $request_id;
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Design_Tokens_Controller(
			$container->auth(),
			$container->json_patch(),
			$container->idempotency_store(),
			$container->audit_log()
		);
		$controller->register();
		$container->register_module( 'rest-design-tokens' );
	}
);

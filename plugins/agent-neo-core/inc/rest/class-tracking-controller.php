<?php
/**
 * POST /tracking/event controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public tracking event endpoint.
 */
final class Agent_Neo_Core_Tracking_Controller extends Agent_Neo_Core_REST_Controller_Base {
	private const NONCE_TTL       = 600;
	private const EVENT_TTL       = DAY_IN_SECONDS;
	private const RATE_WINDOW     = MINUTE_IN_SECONDS;
	private const RATE_LIMIT      = 60;
	private const MAX_STRING_SIZE = 128;

	/**
	 * permission_callback で検証した request context。
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $request_context = array();

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
		$this->register_agent_route(
			'/tracking/event',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'accept_event' ),
				'permission_callback' => array( $this, 'check_tracking_permission' ),
			)
		);

		$this->register_agent_route(
			'/tracking/context',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'accept_context' ),
				'permission_callback' => array( $this, 'check_context_permission' ),
			)
		);
	}

	/**
	 * Public tracking 用 permission callback。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_tracking_permission( WP_REST_Request $request ) {
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$validation = $this->validate_tracking_request( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$secrets = $this->tracking_secrets();
		if ( is_wp_error( $secrets ) ) {
			return $secrets;
		}

		if ( ! hash_equals( $secrets['site_token'], (string) $params['site_token'] ) ) {
			return $this->signature_error();
		}

		$signature = $this->verify_signature( $request, $params, $secrets['hmac_key'] );
		if ( is_wp_error( $signature ) ) {
			return $signature;
		}

		$nonce = $this->consume_nonce( (string) $params['site_token'], (string) $params['nonce'], $signature );
		if ( is_wp_error( $nonce ) ) {
			return $nonce;
		}

		$context = array(
			'accepted_at' => isset( $nonce['accepted_at'] ) ? (string) $nonce['accepted_at'] : gmdate( 'c' ),
			'event_id'    => isset( $nonce['event_id'] ) ? (string) $nonce['event_id'] : $this->event_id( $params, $signature ),
			'params'      => $params,
			'replay'      => ! empty( $nonce['replay'] ),
			'signature'   => $signature,
		);

		if ( empty( $context['replay'] ) ) {
			$bot_filter = $this->check_bot_policy( $request, $params );
			if ( is_wp_error( $bot_filter ) ) {
				$this->delete_nonce( (string) $params['site_token'], (string) $params['nonce'] );
				return $bot_filter;
			}

			$rate_limit = $this->check_rate_limit( $request, (string) $params['site_token'] );
			if ( is_wp_error( $rate_limit ) ) {
				$this->delete_nonce( (string) $params['site_token'], (string) $params['nonce'] );
				return $rate_limit;
			}
		}

		$this->request_context[ spl_object_id( $request ) ] = $context;
		return true;
	}

	/**
	 * POST /tracking/context 専用 permission callback。
	 *
	 * /tracking/event と同じ署名検証コア（site_token / HMAC / nonce / bot policy / rate limit）を
	 * 実行するが、event 固有フィールド（cta_id / variant_id / event_type）は検証しない。
	 * semantic フィールド（site_id / article_id / section_id）の必須検証は
	 * ハンドラ accept_context 側で行う。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_context_permission( WP_REST_Request $request ) {
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		// 認証フィールド（site_token / signature / nonce）の存在・string のみ検証する。
		// event 固有フィールド（cta_id / variant_id / event_type）は検証しない。
		foreach ( array( 'site_token', 'signature', 'nonce' ) as $field ) {
			if ( empty( $params[ $field ] ) || ! is_string( $params[ $field ] ) ) {
				return $this->signature_error();
			}
		}

		$secrets = $this->tracking_secrets();
		if ( is_wp_error( $secrets ) ) {
			return $secrets;
		}

		if ( ! hash_equals( $secrets['site_token'], (string) $params['site_token'] ) ) {
			return $this->signature_error();
		}

		$signature = $this->verify_signature( $request, $params, $secrets['hmac_key'] );
		if ( is_wp_error( $signature ) ) {
			return $signature;
		}

		$nonce = $this->consume_nonce( (string) $params['site_token'], (string) $params['nonce'], $signature );
		if ( is_wp_error( $nonce ) ) {
			return $nonce;
		}

		$context = array(
			'accepted_at' => isset( $nonce['accepted_at'] ) ? (string) $nonce['accepted_at'] : gmdate( 'c' ),
			'event_id'    => isset( $nonce['event_id'] ) ? (string) $nonce['event_id'] : $this->event_id( $params, $signature ),
			'params'      => $params,
			'replay'      => ! empty( $nonce['replay'] ),
			'signature'   => $signature,
		);

		if ( empty( $context['replay'] ) ) {
			$bot_filter = $this->check_bot_policy( $request, $params );
			if ( is_wp_error( $bot_filter ) ) {
				$this->delete_nonce( (string) $params['site_token'], (string) $params['nonce'] );
				return $bot_filter;
			}

			$rate_limit = $this->check_rate_limit( $request, (string) $params['site_token'] );
			if ( is_wp_error( $rate_limit ) ) {
				$this->delete_nonce( (string) $params['site_token'], (string) $params['nonce'] );
				return $rate_limit;
			}
		}

		$this->request_context[ spl_object_id( $request ) ] = $context;
		return true;
	}

	/**
	 * POST /tracking/event。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function accept_event( WP_REST_Request $request ) {
		$context = $this->request_context[ spl_object_id( $request ) ] ?? null;
		if ( ! is_array( $context ) ) {
			$permission = $this->check_tracking_permission( $request );
			if ( is_wp_error( $permission ) ) {
				return $permission;
			}
			$context = $this->request_context[ spl_object_id( $request ) ] ?? null;
		}

		if ( ! is_array( $context ) || ! isset( $context['params'] ) || ! is_array( $context['params'] ) ) {
			return Agent_Neo_Core_Auth::error( 'INTERNAL_ERROR', __( 'Tracking request context is unavailable.', 'agent-neo-core' ) );
		}

		$params   = $context['params'];
		$event_id = (string) $context['event_id'];

		if ( empty( $context['replay'] ) ) {
			$this->queue_event(
				array(
					'event_id'    => $event_id,
					'accepted_at' => (string) $context['accepted_at'],
					'event_type'  => (string) $params['event_type'],
					'section_id'  => (string) $params['section_id'],
					'cta_id'      => (string) $params['cta_id'],
					'variant_id'  => (string) $params['variant_id'],
					'article_id'  => $this->resolve_article_id( $request, $params ),
					'metadata'    => isset( $params['metadata'] ) && is_array( $params['metadata'] ) ? $this->sanitize_metadata( $params['metadata'] ) : array(),
				)
			);
		}

		unset( $this->request_context[ spl_object_id( $request ) ] );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'event_id'    => $event_id,
					'replay'      => ! empty( $context['replay'] ),
					'queued'      => true,
					'accepted_at' => (string) $context['accepted_at'],
				),
				$event_id
			)
		);
	}

	/**
	 * POST /tracking/context。
	 *
	 * Automation SEO 互換 context を受理・同期する。
	 * site_id / article_id / section_id を正規化して記録し、
	 * StandardResponse 封筒で受理確認を返す。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function accept_context( WP_REST_Request $request ) {
		// permission_callback 経由で設定された request context を取得する。
		$context = $this->request_context[ spl_object_id( $request ) ] ?? null;
		if ( ! is_array( $context ) ) {
			// フォールバック: context 専用 permission コールバックで署名検証を再実行する。
			$permission = $this->check_context_permission( $request );
			if ( is_wp_error( $permission ) ) {
				return $permission;
			}
			$context = $this->request_context[ spl_object_id( $request ) ] ?? null;
		}

		if ( ! is_array( $context ) || ! isset( $context['params'] ) || ! is_array( $context['params'] ) ) {
			return Agent_Neo_Core_Auth::error( 'INTERNAL_ERROR', __( 'Tracking request context is unavailable.', 'agent-neo-core' ) );
		}

		$params = $context['params'];

		// context 固有フィールドのバリデーション（site_id / article_id / section_id 必須 / string 型）。
		foreach ( array( 'site_id', 'article_id', 'section_id' ) as $field ) {
			if ( ! isset( $params[ $field ] ) || ! is_string( $params[ $field ] ) || '' === trim( $params[ $field ] ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					sprintf(
						/* translators: %s: field name */
						__( '%s is required.', 'agent-neo-core' ),
						$field
					),
					array( 'field' => $field )
				);
			}

			if ( strlen( $params[ $field ] ) > self::MAX_STRING_SIZE ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					sprintf(
						/* translators: %s: field name */
						__( '%s exceeds maximum length.', 'agent-neo-core' ),
						$field
					),
					array( 'field' => $field )
				);
			}
		}

		// 外部入力を sanitize して正規化する。
		$site_id    = sanitize_text_field( (string) $params['site_id'] );
		$article_id = sanitize_text_field( (string) $params['article_id'] );
		$section_id = sanitize_text_field( (string) $params['section_id'] );

		$received_at = gmdate( 'c' );
		// nonce を追加して同秒・同一フィールド値のリクエストによる context_id 衝突を防ぐ。
		// check_context_permission が検証済みの nonce は $context['params']['nonce'] に保存されている。
		$ctx_nonce  = isset( $context['params']['nonce'] ) && is_string( $context['params']['nonce'] ) ? $context['params']['nonce'] : '';
		$context_id = 'ctx_' . substr( hash( 'sha256', $site_id . '|' . $article_id . '|' . $section_id . '|' . $received_at . '|' . $ctx_nonce ), 0, 32 );

		// Automation SEO 互換 context を WordPress option に記録する（tracking/event の queue_event に準じた軽量記録）。
		$record = array(
			'context_id'  => $context_id,
			'site_id'     => $site_id,
			'article_id'  => $article_id,
			'section_id'  => $section_id,
			'received_at' => $received_at,
		);
		set_transient( 'agent_neo_tracking_context_' . $context_id, $record, self::EVENT_TTL );

		/**
		 * 受理した context を外部ストアに同期するための hook。
		 *
		 * @param array<string,mixed> $record Context record。
		 */
		do_action( 'agent_neo_tracking_context_accepted', $record );

		unset( $this->request_context[ spl_object_id( $request ) ] );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'context_id'  => $context_id,
					'site_id'     => $site_id,
					'article_id'  => $article_id,
					'section_id'  => $section_id,
					'received'    => true,
					'received_at' => $received_at,
				),
				$context_id
			)
		);
	}

	/**
	 * JSON body を取得する。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return array<string, mixed>|WP_Error
	 */
	private function json_params( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'JSON body is required.', 'agent-neo-core' ) );
		}

		return $params;
	}

	/**
	 * Request body を検証する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function validate_tracking_request( array $params ) {
		foreach ( array( 'site_token', 'signature', 'nonce' ) as $field ) {
			if ( empty( $params[ $field ] ) || ! is_string( $params[ $field ] ) ) {
				return $this->signature_error();
			}
		}

		foreach ( array( 'section_id', 'cta_id', 'variant_id' ) as $field ) {
			if ( empty( $params[ $field ] ) || ! is_string( $params[ $field ] ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					sprintf(
						/* translators: %s: field name */
						__( '%s is required.', 'agent-neo-core' ),
						$field
					),
					array( 'field' => $field )
				);
			}

			if ( ! $this->is_tracking_id( (string) $params[ $field ] ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					sprintf(
						/* translators: %s: field name */
						__( '%s contains invalid characters.', 'agent-neo-core' ),
						$field
					),
					array( 'field' => $field )
				);
			}
		}

		if ( empty( $params['event_type'] ) || ! is_string( $params['event_type'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'event_type is required.', 'agent-neo-core' ), array( 'field' => 'event_type' ) );
		}

		if ( ! in_array( $params['event_type'], array( 'impression', 'click', 'conversion' ), true ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'event_type is invalid.', 'agent-neo-core' ), array( 'field' => 'event_type' ) );
		}

		if ( isset( $params['article_id'] ) && ( ! is_string( $params['article_id'] ) || strlen( $params['article_id'] ) > self::MAX_STRING_SIZE ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'article_id is invalid.', 'agent-neo-core' ), array( 'field' => 'article_id' ) );
		}

		if ( isset( $params['metadata'] ) && ! is_array( $params['metadata'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'metadata must be an object.', 'agent-neo-core' ), array( 'field' => 'metadata' ) );
		}

		return true;
	}

	/**
	 * Tracking secret を option/env から読む。
	 *
	 * @return array{site_token:string,hmac_key:string}|WP_Error
	 */
	private function tracking_secrets() {
		$site_token = $this->first_string(
			array(
				$this->env( 'AGENT_NEO_SITE_TOKEN' ),
				$this->env( 'AGENT_NEO_TRACKING_SITE_TOKEN' ),
				get_option( 'agent_neo_site_token', '' ),
				get_option( 'agent_neo_tracking_site_token', '' ),
			)
		);
		$hmac_key   = $this->first_string(
			array(
				$this->env( 'AGENT_NEO_TRACKING_HMAC_KEY' ),
				$this->env( 'AGENT_NEO_HMAC_KEY' ),
				get_option( 'agent_neo_tracking_hmac_key', '' ),
				get_option( 'agent_neo_hmac_key', '' ),
			)
		);

		if ( '' === $site_token || '' === $hmac_key ) {
			return $this->signature_error();
		}

		return array(
			'site_token' => $site_token,
			'hmac_key'   => $hmac_key,
		);
	}

	/**
	 * HMAC signature を検証し、正規署名値を返す。
	 *
	 * @param WP_REST_Request     $request Request。
	 * @param array<string,mixed> $params Params。
	 * @param string              $hmac_key HMAC key。
	 * @return string|WP_Error
	 */
	private function verify_signature( WP_REST_Request $request, array $params, string $hmac_key ) {
		$provided = $this->normalize_signature( (string) $params['signature'] );
		$payload  = $this->signature_payload( $request, $params );
		$raw      = hash_hmac( 'sha256', $payload, $hmac_key, true );
		$accepted = array(
			hash_hmac( 'sha256', $payload, $hmac_key ),
			base64_encode( $raw ),
		);

		foreach ( $accepted as $signature ) {
			if ( hash_equals( $signature, $provided ) ) {
				return hash_hmac( 'sha256', $payload, $hmac_key );
			}
		}

		return $this->signature_error();
	}

	/**
	 * 署名対象 payload を作る。
	 *
	 * canonical パスは `/agent-neo/v1/tracking/event` で固定する。
	 * `/tracking/context` エンドポイントの署名検証でも意図的に同一 canonical を共有している。
	 * クライアント（Automation SEO）と署名規約を統一するための設計であり、誤記ではない。
	 *
	 * @param WP_REST_Request     $request Request。
	 * @param array<string,mixed> $params Params。
	 * @return string
	 */
	private function signature_payload( WP_REST_Request $request, array $params ): string {
		$body = $params;
		unset( $body['signature'] );
		$canonical = $this->canonical_json( $body );

		return implode(
			'|',
			array(
				$request->get_method(),
				'/agent-neo/v1/tracking/event',
				(string) $params['nonce'],
				hash( 'sha256', $canonical ),
			)
		);
	}

	/**
	 * Nonce を single-use として登録する。
	 *
	 * @param string $site_token Site token。
	 * @param string $nonce Nonce。
	 * @param string $signature Signature。
	 * @return array{replay:bool,event_id:string,accepted_at:string}|WP_Error
	 */
	private function consume_nonce( string $site_token, string $nonce, string $signature ) {
		if ( strlen( $nonce ) < 8 || strlen( $nonce ) > self::MAX_STRING_SIZE || ! preg_match( '/^[A-Za-z0-9_.:-]+$/', $nonce ) ) {
			return $this->signature_error();
		}

		$key         = 'agent_neo_tracking_nonce_' . hash( 'sha256', $site_token . '|' . $nonce );
		$value_key   = '_transient_' . $key;
		$timeout_key = '_transient_timeout_' . $key;
		$now         = time();
		$timeout     = (int) get_option( $timeout_key, 0 );

		if ( $timeout > 0 && $timeout < $now ) {
			delete_option( $timeout_key );
			delete_option( $value_key );
		}

		$existing = get_option( $value_key, false );
		if ( is_array( $existing ) ) {
			if ( ! isset( $existing['signature'] ) || ! hash_equals( (string) $existing['signature'], $signature ) ) {
				return $this->signature_error();
			}

			return array(
				'replay'      => true,
				'event_id'    => isset( $existing['event_id'] ) ? (string) $existing['event_id'] : 'evt_' . substr( hash( 'sha256', $site_token . '|' . $nonce ), 0, 32 ),
				'accepted_at' => isset( $existing['accepted_at'] ) ? (string) $existing['accepted_at'] : gmdate( 'c', $now ),
			);
		}

		$record = array(
			'signature'   => $signature,
			'event_id'    => 'evt_' . substr( hash( 'sha256', $site_token . '|' . $nonce . '|' . $signature ), 0, 32 ),
			'accepted_at' => gmdate( 'c', $now ),
		);

		add_option( $timeout_key, $now + self::NONCE_TTL, '', false );
		if ( ! add_option( $value_key, $record, '', false ) ) {
			$existing = get_option( $value_key, false );
			if ( is_array( $existing ) && isset( $existing['signature'] ) && hash_equals( (string) $existing['signature'], $signature ) ) {
				return array(
					'replay'      => true,
					'event_id'    => isset( $existing['event_id'] ) ? (string) $existing['event_id'] : $record['event_id'],
					'accepted_at' => isset( $existing['accepted_at'] ) ? (string) $existing['accepted_at'] : $record['accepted_at'],
				);
			}

			return $this->signature_error();
		}

		return array(
			'replay'      => false,
			'event_id'    => $record['event_id'],
			'accepted_at' => $record['accepted_at'],
		);
	}

	/**
	 * Reject 済み request の nonce 記録を戻す。
	 *
	 * @param string $site_token Site token。
	 * @param string $nonce Nonce。
	 * @return void
	 */
	private function delete_nonce( string $site_token, string $nonce ): void {
		$key = 'agent_neo_tracking_nonce_' . hash( 'sha256', $site_token . '|' . $nonce );
		delete_option( '_transient_timeout_' . $key );
		delete_option( '_transient_' . $key );
	}

	/**
	 * Bot policy を適用する。
	 *
	 * @param WP_REST_Request     $request Request。
	 * @param array<string,mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function check_bot_policy( WP_REST_Request $request, array $params ) {
		$metadata    = isset( $params['metadata'] ) && is_array( $params['metadata'] ) ? $params['metadata'] : array();
		$user_agent  = (string) $request->get_header( 'User-Agent' );
		$bot_detected = false;

		if ( isset( $metadata['bot'] ) && true === $metadata['bot'] ) {
			$bot_detected = true;
		}

		if ( isset( $metadata['bot_policy'] ) && 'block' === $metadata['bot_policy'] ) {
			$bot_detected = true;
		}

		if ( preg_match( '/(GPTBot|ClaudeBot|PerplexityBot|Bytespider|AhrefsBot|SemrushBot|MJ12bot|DotBot)/i', $user_agent ) ) {
			$bot_detected = true;
		}

		$bot_detected = (bool) apply_filters( 'agent_neo_tracking_bot_detected', $bot_detected, $request, $params );
		if ( $bot_detected ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Tracking event rejected by bot policy.', 'agent-neo-core' ) );
		}

		return true;
	}

	/**
	 * Rate limit を確認する。
	 *
	 * @param WP_REST_Request $request Request。
	 * @param string          $site_token Site token。
	 * @return true|WP_Error
	 */
	private function check_rate_limit( WP_REST_Request $request, string $site_token ) {
		$limit  = (int) apply_filters( 'agent_neo_tracking_rate_limit', self::RATE_LIMIT, $request );
		$window = (int) apply_filters( 'agent_neo_tracking_rate_window', self::RATE_WINDOW, $request );
		if ( $limit <= 0 || $window <= 0 ) {
			return true;
		}

		$key   = 'agent_neo_rate_block_' . hash( 'sha256', $site_token . '|' . $this->client_ip( $request ) );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return Agent_Neo_Core_Auth::error( 'RATE_LIMITED', __( 'Tracking event rate limit exceeded.', 'agent-neo-core' ) );
		}

		set_transient( $key, $count + 1, $window );
		return true;
	}

	/**
	 * Event を軽量 queue に保存する。
	 *
	 * @param array<string,mixed> $event Event。
	 * @return void
	 */
	private function queue_event( array $event ): void {
		$event_id = isset( $event['event_id'] ) ? (string) $event['event_id'] : $this->event_id( $event, wp_generate_uuid4() );
		set_transient( 'agent_neo_tracking_event_' . substr( hash( 'sha256', $event_id ), 0, 40 ), $event, self::EVENT_TTL );

		$index = get_option( 'agent_neo_tracking_event_queue', array() );
		if ( ! is_array( $index ) ) {
			$index = array();
		}

		array_unshift( $index, $event_id );
		$index = array_values( array_unique( array_slice( $index, 0, 100 ) ) );
		update_option( 'agent_neo_tracking_event_queue', $index, false );

		/**
		 * 保存先を外部 queue / custom table に差し替えるための hook。
		 *
		 * @param array<string,mixed> $event Event。
		 */
		do_action( 'agent_neo_tracking_event_accepted', $event );
	}

	/**
	 * article_id を補完する。
	 *
	 * @param WP_REST_Request     $request Request。
	 * @param array<string,mixed> $params Params。
	 * @return string
	 */
	private function resolve_article_id( WP_REST_Request $request, array $params ): string {
		if ( ! empty( $params['article_id'] ) && is_string( $params['article_id'] ) ) {
			return sanitize_text_field( $params['article_id'] );
		}

		$metadata = isset( $params['metadata'] ) && is_array( $params['metadata'] ) ? $params['metadata'] : array();
		$url      = '';
		foreach ( array( 'url', 'current_url', 'referrer' ) as $field ) {
			if ( ! empty( $metadata[ $field ] ) && is_string( $metadata[ $field ] ) ) {
				$url = esc_url_raw( $metadata[ $field ] );
				break;
			}
		}

		if ( '' === $url ) {
			$url = esc_url_raw( (string) $request->get_header( 'Referer' ) );
		}

		if ( '' === $url || ! function_exists( 'url_to_postid' ) ) {
			return '';
		}

		$post_id = (int) url_to_postid( $url );
		if ( $post_id <= 0 ) {
			return '';
		}

		$article_id = get_post_meta( $post_id, '_agent_neo_article_id', true );
		return is_string( $article_id ) && '' !== $article_id ? $article_id : (string) $post_id;
	}

	/**
	 * Metadata を保存用に sanitize する。
	 *
	 * @param array<mixed> $metadata Metadata。
	 * @return array<mixed>
	 */
	private function sanitize_metadata( array $metadata ): array {
		$sanitized = array();
		foreach ( $metadata as $key => $value ) {
			if ( ! is_string( $key ) || strlen( $key ) > 64 ) {
				continue;
			}

			$key = sanitize_key( $key );
			if ( '' === $key ) {
				continue;
			}

			if ( is_scalar( $value ) || null === $value ) {
				$sanitized[ $key ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_metadata( $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Canonical JSON を生成する。
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

		if ( $this->is_assoc( $value ) ) {
			ksort( $value );
		}

		return $value;
	}

	/**
	 * 連想配列か判定する。
	 *
	 * @param array<mixed> $value Value。
	 * @return bool
	 */
	private function is_assoc( array $value ): bool {
		return array_keys( $value ) !== range( 0, count( $value ) - 1 );
	}

	/**
	 * Event id を作る。
	 *
	 * @param array<string,mixed> $params Params。
	 * @param string              $signature Signature。
	 * @return string
	 */
	private function event_id( array $params, string $signature ): string {
		return 'evt_' . substr( hash( 'sha256', $this->canonical_json( $params ) . '|' . $signature ), 0, 32 );
	}

	/**
	 * Client IP を取得する。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return string
	 */
	private function client_ip( WP_REST_Request $request ): string {
		$remote_addr = $this->remote_addr();
		if ( $this->is_trusted_proxy( $remote_addr ) ) {
			$forwarded = $this->forwarded_for_ip( $request );
			if ( '' !== $forwarded ) {
				return $forwarded;
			}
		}

		return '' !== $remote_addr ? $remote_addr : 'unknown';
	}

	/**
	 * REMOTE_ADDR を取得する。
	 *
	 * @return string
	 */
	private function remote_addr(): string {
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return $this->is_valid_ip( $remote_addr ) ? $remote_addr : '';
	}

	/**
	 * X-Forwarded-For の先頭 client IP を取得する。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return string
	 */
	private function forwarded_for_ip( WP_REST_Request $request ): string {
		$forwarded = (string) $request->get_header( 'X-Forwarded-For' );
		if ( '' === $forwarded ) {
			return '';
		}

		$parts = explode( ',', $forwarded );
		$ip    = trim( (string) $parts[0] );
		return $this->is_valid_ip( $ip ) ? $ip : '';
	}

	/**
	 * REMOTE_ADDR が信頼済み proxy か判定する。
	 *
	 * @param string $remote_addr Remote IP。
	 * @return bool
	 */
	private function is_trusted_proxy( string $remote_addr ): bool {
		if ( '' === $remote_addr ) {
			return false;
		}

		foreach ( $this->trusted_proxies() as $trusted ) {
			if ( $remote_addr === $trusted || $this->ip_in_cidr( $remote_addr, $trusted ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 信頼済み proxy allowlist を option / constant / filter から読む。
	 *
	 * @return array<int, string>
	 */
	private function trusted_proxies(): array {
		$values = array();
		if ( defined( 'AGENT_NEO_TRUSTED_PROXIES' ) ) {
			$values[] = constant( 'AGENT_NEO_TRUSTED_PROXIES' );
		}
		$values[] = get_option( 'agent_neo_trusted_proxies', array() );
		$values   = apply_filters( 'agent_neo_tracking_trusted_proxies', $values );

		$proxies = array();
		foreach ( (array) $values as $value ) {
			foreach ( $this->normalize_proxy_list( $value ) as $proxy ) {
				$proxies[ $proxy ] = true;
			}
		}

		return array_keys( $proxies );
	}

	/**
	 * proxy allowlist 値を配列へ正規化する。
	 *
	 * @param mixed $value Raw value。
	 * @return array<int, string>
	 */
	private function normalize_proxy_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\s,]+/', $value );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $value as $proxy ) {
			if ( ! is_string( $proxy ) ) {
				continue;
			}

			$proxy = trim( $proxy );
			if ( $this->is_valid_ip( $proxy ) || $this->is_valid_cidr( $proxy ) ) {
				$normalized[] = $proxy;
			}
		}

		return $normalized;
	}

	/**
	 * IP 文字列を検証する。
	 *
	 * @param string $ip IP address。
	 * @return bool
	 */
	private function is_valid_ip( string $ip ): bool {
		return false !== filter_var( $ip, FILTER_VALIDATE_IP );
	}

	/**
	 * CIDR 文字列を検証する。
	 *
	 * @param string $cidr CIDR。
	 * @return bool
	 */
	private function is_valid_cidr( string $cidr ): bool {
		$parts = explode( '/', $cidr, 2 );
		if ( 2 !== count( $parts ) || ! $this->is_valid_ip( $parts[0] ) || ! ctype_digit( $parts[1] ) ) {
			return false;
		}

		$packed = inet_pton( $parts[0] );
		$bits   = false !== $packed && 4 === strlen( $packed ) ? 32 : 128;
		$prefix = (int) $parts[1];
		return $prefix >= 0 && $prefix <= $bits;
	}

	/**
	 * IP が CIDR に含まれるか判定する。
	 *
	 * @param string $ip IP address。
	 * @param string $cidr CIDR。
	 * @return bool
	 */
	private function ip_in_cidr( string $ip, string $cidr ): bool {
		if ( ! $this->is_valid_cidr( $cidr ) ) {
			return false;
		}

		list( $network, $prefix ) = explode( '/', $cidr, 2 );
		$ip_bin      = inet_pton( $ip );
		$network_bin = inet_pton( $network );
		if ( false === $ip_bin || false === $network_bin || strlen( $ip_bin ) !== strlen( $network_bin ) ) {
			return false;
		}

		$prefix = (int) $prefix;
		$bytes  = intdiv( $prefix, 8 );
		$bits   = $prefix % 8;
		if ( 0 !== $bytes && substr( $ip_bin, 0, $bytes ) !== substr( $network_bin, 0, $bytes ) ) {
			return false;
		}

		if ( 0 === $bits ) {
			return true;
		}

		$mask = ( 0xff << ( 8 - $bits ) ) & 0xff;
		return ( ord( $ip_bin[ $bytes ] ) & $mask ) === ( ord( $network_bin[ $bytes ] ) & $mask );
	}

	/**
	 * Tracking ID の形式を判定する。
	 *
	 * @param string $value Value。
	 * @return bool
	 */
	private function is_tracking_id( string $value ): bool {
		return strlen( $value ) <= self::MAX_STRING_SIZE && 1 === preg_match( '/^[A-Za-z0-9_-]+$/', $value );
	}

	/**
	 * Signature 表記を正規化する。
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
	private function first_string( array $values ): string {
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
	 * @param string $name Name。
	 * @return string
	 */
	private function env( string $name ): string {
		$value = getenv( $name );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * SIGNATURE_INVALID error。
	 *
	 * @return WP_Error
	 */
	private function signature_error(): WP_Error {
		return Agent_Neo_Core_Auth::error( 'SIGNATURE_INVALID', __( 'Tracking signature or nonce is invalid.', 'agent-neo-core' ) );
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Tracking_Controller();
		$controller->register();
		$container->register_module( 'rest-tracking' );
	}
);

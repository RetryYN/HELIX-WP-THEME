<?php
/**
 * catalog-update producer.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * D-PLUGIN-CONTRACT §17 catalog-update producer.
 */
final class Agent_Neo_Core_Catalog_Update_Producer {
	private const ENDPOINT_PATH      = '/aseo/v1/agent-neo/catalog-update';
	private const OUTBOX_OPTION      = 'agent_neo_catalog_update_outbox';
	private const DLQ_OPTION         = 'agent_neo_catalog_update_dlq';
	private const RECEIPT_OPTION     = 'agent_neo_catalog_update_receipts';
	private const KNOWN_BLOCKS       = 'agent_neo_catalog_update_known_blocks';
	private const CRON_HOOK          = 'agent_neo_catalog_update_process_outbox';
	private const EVENT_TTL          = DAY_IN_SECONDS;
	private const MAX_ATTEMPTS       = 5;
	private const INITIAL_BACKOFF    = 1;
	private const MAX_QUEUE_ITEMS    = 200;
	private const MAX_RECEIPTS       = 50;
	private const MAX_DLQ_ITEMS      = 100;
	private const SIGNATURE_WINDOW   = 300;
	private const HTTP_TIMEOUT       = 10;
	private const MAX_STRING_LENGTH  = 512;
	private const ALLOWED_EVENT_KIND = array(
		'block_registered',
		'block_unregistered',
		'template_updated',
		'theme_token_updated',
	);

	/**
	 * Hook を登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'registered_block_type', array( $this, 'on_block_registered' ), 10, 2 );
		add_action( 'unregistered_block_type', array( $this, 'on_block_unregistered' ), 10, 1 );
		add_action( 'wp_after_insert_post', array( $this, 'on_template_post_saved' ), 10, 4 );
		add_action( 'updated_option', array( $this, 'on_option_updated' ), 10, 3 );
		add_action( self::CRON_HOOK, array( $this, 'process_outbox' ) );

		add_action( 'agent_neo_catalog_block_registered', array( $this, 'enqueue_block_registered' ), 10, 3 );
		add_action( 'agent_neo_catalog_block_unregistered', array( $this, 'enqueue_block_unregistered' ), 10, 3 );
		add_action( 'agent_neo_catalog_template_updated', array( $this, 'enqueue_template_updated' ), 10, 3 );
		add_action( 'agent_neo_catalog_theme_token_updated', array( $this, 'enqueue_theme_token_updated' ), 10, 3 );
	}

	/**
	 * registered_block_type hook。
	 *
	 * @param mixed  $block_type Block type object/string。
	 * @param string $block_name Block name。
	 * @return void
	 */
	public function on_block_registered( $block_type, string $block_name = '' ): void {
		$name = '' !== $block_name ? $block_name : $this->block_name_from_value( $block_type );
		if ( '' === $name || ! $this->should_track_block( $name ) ) {
			return;
		}

		$known = $this->known_blocks();
		if ( isset( $known[ $name ] ) ) {
			return;
		}

		$known[ $name ] = gmdate( 'c' );
		$this->save_known_blocks( $known );
		$this->enqueue_block_registered( $name );
	}

	/**
	 * unregistered_block_type hook。
	 *
	 * @param mixed $block_type Block type object/string。
	 * @return void
	 */
	public function on_block_unregistered( $block_type ): void {
		$name = $this->block_name_from_value( $block_type );
		if ( '' === $name || ! $this->should_track_block( $name ) ) {
			return;
		}

		$known = $this->known_blocks();
		unset( $known[ $name ] );
		$this->save_known_blocks( $known );
		$this->enqueue_block_unregistered( $name );
	}

	/**
	 * wp_template / wp_template_part の保存を catalog-update に変換する。
	 *
	 * @param int          $post_id Post ID。
	 * @param WP_Post      $post Post。
	 * @param bool         $update Update flag。
	 * @param WP_Post|null $post_before Previous post。
	 * @return void
	 */
	public function on_template_post_saved( int $post_id, WP_Post $post, bool $update, $post_before ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( 'wp_template', 'wp_template_part' ), true ) ) {
			return;
		}

		$slug = '' !== $post->post_name ? $post->post_name : (string) $post_id;
		$diff = array(
			'post_id'      => $post_id,
			'post_type'    => $post->post_type,
			'updated'      => $update,
			'before_hash'  => $post_before instanceof WP_Post ? hash( 'sha256', (string) $post_before->post_content ) : '',
			'current_hash' => hash( 'sha256', (string) $post->post_content ),
		);

		$this->enqueue_template_updated( $slug, $diff );
	}

	/**
	 * theme token 系 option 更新を catalog-update に変換する。
	 *
	 * @param string $option Option name。
	 * @param mixed  $old_value Old value。
	 * @param mixed  $value New value。
	 * @return void
	 */
	public function on_option_updated( string $option, $old_value, $value ): void {
		if ( ! $this->is_theme_token_option( $option ) ) {
			return;
		}

		$this->enqueue_theme_token_updated(
			array(
				'option'      => $option,
				'before_hash' => $this->value_hash( $old_value ),
				'after_hash'  => $this->value_hash( $value ),
			)
		);
	}

	/**
	 * block_registered event を enqueue する。
	 *
	 * @param string               $block_name Block name。
	 * @param array<string, mixed> $extra_payload Extra payload。
	 * @param string               $event_id Optional event id。
	 * @return array<string, mixed>
	 */
	public function enqueue_block_registered( string $block_name, array $extra_payload = array(), string $event_id = '' ): array {
		return $this->enqueue_event(
			'block_registered',
			array_merge(
				array(
					'block_name' => $this->sanitize_string( $block_name ),
				),
				$this->sanitize_payload( $extra_payload )
			),
			$event_id
		);
	}

	/**
	 * block_unregistered event を enqueue する。
	 *
	 * @param string               $block_name Block name。
	 * @param array<string, mixed> $extra_payload Extra payload。
	 * @param string               $event_id Optional event id。
	 * @return array<string, mixed>
	 */
	public function enqueue_block_unregistered( string $block_name, array $extra_payload = array(), string $event_id = '' ): array {
		return $this->enqueue_event(
			'block_unregistered',
			array_merge(
				array(
					'block_name' => $this->sanitize_string( $block_name ),
				),
				$this->sanitize_payload( $extra_payload )
			),
			$event_id
		);
	}

	/**
	 * template_updated event を enqueue する。
	 *
	 * @param string               $template_part_slug Template/template part slug。
	 * @param array<string, mixed> $diff Diff metadata。
	 * @param string               $event_id Optional event id。
	 * @return array<string, mixed>
	 */
	public function enqueue_template_updated( string $template_part_slug, array $diff = array(), string $event_id = '' ): array {
		return $this->enqueue_event(
			'template_updated',
			array(
				'template_part_slug' => $this->sanitize_string( $template_part_slug ),
				'diff'               => $this->sanitize_payload( $diff ),
			),
			$event_id
		);
	}

	/**
	 * theme_token_updated event を enqueue する。
	 *
	 * @param array<string, mixed> $diff Diff metadata。
	 * @param array<string, mixed> $extra_payload Extra payload。
	 * @param string              $event_id Optional event id。
	 * @return array<string, mixed>
	 */
	public function enqueue_theme_token_updated( array $diff = array(), array $extra_payload = array(), string $event_id = '' ): array {
		return $this->enqueue_event(
			'theme_token_updated',
			array_merge(
				array(
					'diff' => $this->sanitize_payload( $diff ),
				),
				$this->sanitize_payload( $extra_payload )
			),
			$event_id
		);
	}

	/**
	 * 任意 event を enqueue する。
	 *
	 * @param string               $event_kind Event kind。
	 * @param array<string, mixed> $payload Payload。
	 * @param string               $event_id Optional event id。
	 * @return array<string, mixed>
	 */
	public function enqueue_event( string $event_kind, array $payload, string $event_id = '' ): array {
		$request = $this->build_request( $event_kind, $payload, $event_id );
		if ( is_wp_error( $request ) ) {
			return array(
				'enqueued' => false,
				'error'    => $request->get_error_code(),
			);
		}

		$event_id = (string) $request['event_id'];
		$marker   = $this->idempotency_marker( $event_id );
		if ( is_array( $marker ) && ! empty( $marker['active'] ) ) {
			return array(
				'enqueued'     => false,
				'deduplicated' => true,
				'event_id'     => $event_id,
				'status'       => isset( $marker['status'] ) ? (string) $marker['status'] : 'queued',
			);
		}

		$item = array(
			'event_id'     => $event_id,
			'event_kind'   => $event_kind,
			'request'      => $request,
			'attempts'     => 0,
			'status'       => 'pending',
			'created_at'   => time(),
			'next_due_at'  => time(),
			'last_error'   => '',
			'last_status'  => null,
			'last_attempt' => null,
		);

		$outbox              = $this->outbox();
		$outbox[ $event_id ] = $item;
		$this->save_outbox( $outbox );
		$this->save_idempotency_marker( $event_id, 'queued' );
		$this->schedule_processing( time() );

		/**
		 * catalog-update event が outbox に入った後の拡張点。
		 *
		 * @param array<string,mixed> $item Outbox item。
		 */
		do_action( 'agent_neo_catalog_update_enqueued', $item );

		return array(
			'enqueued' => true,
			'event_id' => $event_id,
			'status'   => 'pending',
		);
	}

	/**
	 * §17.2 request を構築する。
	 *
	 * @param string               $event_kind Event kind。
	 * @param array<string, mixed> $payload Payload。
	 * @param string               $event_id Optional event id。
	 * @return array<string,mixed>|WP_Error
	 */
	public function build_request( string $event_kind, array $payload, string $event_id = '' ) {
		if ( ! in_array( $event_kind, self::ALLOWED_EVENT_KIND, true ) ) {
			return new WP_Error( 'invalid_event_kind', __( 'catalog-update event_kind is invalid.', 'agent-neo-core' ) );
		}

		$event_id = '' !== $event_id ? $this->sanitize_string( $event_id ) : $this->new_event_id();
		if ( '' === $event_id ) {
			return new WP_Error( 'invalid_event_id', __( 'catalog-update event_id is invalid.', 'agent-neo-core' ) );
		}

		return array(
			'site_hash'         => $this->site_hash(),
			'agent_neo_version' => defined( 'AGENT_NEO_CORE_VERSION' ) ? AGENT_NEO_CORE_VERSION : '0.0.0',
			'event_kind'        => $event_kind,
			'event_id'          => $event_id,
			'occurred_at'       => gmdate( 'c' ),
			'payload'           => $this->sanitize_payload( $payload ),
		);
	}

	/**
	 * due item を送信する。
	 *
	 * @return array<string,mixed>
	 */
	public function process_outbox(): array {
		$outbox    = $this->outbox();
		$now       = time();
		$processed = 0;
		$sent      = 0;
		$retrying  = 0;
		$dead      = 0;

		foreach ( $outbox as $event_id => $item ) {
			if ( ! is_array( $item ) ) {
				unset( $outbox[ $event_id ] );
				continue;
			}

			$next_due_at = isset( $item['next_due_at'] ) ? (int) $item['next_due_at'] : 0;
			if ( $next_due_at > $now ) {
				continue;
			}

			++$processed;
			$result = $this->send_item( $item );
			if ( ! empty( $result['sent'] ) ) {
				unset( $outbox[ $event_id ] );
				$this->save_idempotency_marker( (string) $event_id, 'sent' );
				++$sent;
				continue;
			}

			if ( ! empty( $result['dead'] ) ) {
				unset( $outbox[ $event_id ] );
				++$dead;
				continue;
			}

			$outbox[ $event_id ] = isset( $result['item'] ) && is_array( $result['item'] ) ? $result['item'] : $item;
			++$retrying;
		}

		$this->save_outbox( $outbox );
		$this->schedule_next_due( $outbox );

		return array(
			'processed' => $processed,
			'sent'      => $sent,
			'retrying'  => $retrying,
			'dead'      => $dead,
			'pending'   => count( $outbox ),
		);
	}

	/**
	 * 契約参照サマリを返す。
	 *
	 * @return array<string, mixed>
	 */
	public function contract_summary(): array {
		$status = $this->queue_status();

		return array(
			'producer'          => 'agent-neo-core',
			'consumer'          => 'automation-seo',
			'contract'          => 'D-PLUGIN-CONTRACT §17 / §17.11',
			'implemented'       => true,
			'endpoint'          => self::ENDPOINT_PATH,
			'event_kinds'       => self::ALLOWED_EVENT_KIND,
			'request_fields'    => array( 'site_hash', 'agent_neo_version', 'event_kind', 'event_id', 'occurred_at', 'payload' ),
			'response_fields'   => array( 'received', 'event_id', 'deduplicated', 'next_action' ),
			'status'            => $status['producer_status'],
			'outbox'            => $status['outbox'],
			'dlq'               => $status['dlq'],
			'retry'             => array(
				'initial_backoff_seconds' => self::INITIAL_BACKOFF,
				'multiplier'              => 2,
				'max_attempts'            => self::MAX_ATTEMPTS,
				'jitter'                  => '+/-10%',
				'retry_on'                => array( '5xx', '429', 'network_timeout' ),
				'no_retry_on'             => array( '4xx_except_429' ),
			),
		);
	}

	/**
	 * health サマリを返す。
	 *
	 * @return array<string, mixed>
	 */
	public function health(): array {
		return $this->contract_summary();
	}

	/**
	 * 1 item を送信する。
	 *
	 * @param array<string,mixed> $item Outbox item。
	 * @return array<string,mixed>
	 */
	private function send_item( array $item ): array {
		$event_id = isset( $item['event_id'] ) ? (string) $item['event_id'] : '';
		$request  = isset( $item['request'] ) && is_array( $item['request'] ) ? $item['request'] : array();
		$endpoint = $this->endpoint_url();
		$body     = $this->canonical_json( $request );

		if ( '' === $event_id || empty( $request ) ) {
			$this->dead_letter( $event_id, 'VALIDATION_ERROR', 400, $item );
			return array( 'dead' => true );
		}

		if ( is_wp_error( $endpoint ) ) {
			$this->dead_letter( $event_id, $endpoint->get_error_code(), 400, $item );
			return array( 'dead' => true );
		}

		$headers = $this->signature_headers( $body );
		if ( is_wp_error( $headers ) ) {
			$this->dead_letter( $event_id, $headers->get_error_code(), 401, $item );
			return array( 'dead' => true );
		}

		$item['attempts']     = isset( $item['attempts'] ) ? (int) $item['attempts'] + 1 : 1;
		$item['last_attempt'] = time();
		$item['status']       = 'running';

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'     => self::HTTP_TIMEOUT,
				'redirection' => 0,
				'headers'     => array_merge(
					array(
						'Content-Type' => 'application/json',
						'Accept'       => 'application/json',
					),
					$headers
				),
				'body'        => $body,
				'data_format' => 'body',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->retry_or_dead( $item, $response->get_error_code(), 0 );
		}

		$status_code         = (int) wp_remote_retrieve_response_code( $response );
		$item['last_status'] = $status_code;

		if ( 200 <= $status_code && $status_code < 300 ) {
			$body_data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $body_data ) || ! $this->is_valid_response( $body_data, $event_id ) ) {
				return $this->retry_or_dead( $item, 'INVALID_RESPONSE', $status_code );
			}

			$this->record_receipt( $body_data, $item );

			if ( 'scan-catalog' === (string) $body_data['next_action'] ) {
				do_action( 'agent_neo_catalog_update_scan_requested', $event_id, $body_data, $item );
			}

			return array(
				'sent'     => true,
				'response' => $body_data,
			);
		}

		if ( $this->is_retryable_status( $status_code ) ) {
			return $this->retry_or_dead( $item, $this->error_code_for_status( $status_code ), $status_code );
		}

		$this->dead_letter( $event_id, $this->error_code_for_status( $status_code ), $status_code, $item );
		return array( 'dead' => true );
	}

	/**
	 * 再試行または DLQ を決定する。
	 *
	 * @param array<string,mixed> $item Outbox item。
	 * @param string              $reason Reason。
	 * @param int                 $status_code HTTP status。
	 * @return array<string,mixed>
	 */
	private function retry_or_dead( array $item, string $reason, int $status_code ): array {
		$event_id = isset( $item['event_id'] ) ? (string) $item['event_id'] : '';
		$attempts = isset( $item['attempts'] ) ? (int) $item['attempts'] : 0;

		if ( $attempts >= self::MAX_ATTEMPTS ) {
			$this->dead_letter( $event_id, 'RETRY_EXHAUSTED', 409, $item );
			return array( 'dead' => true );
		}

		$item['status']      = 'retrying';
		$item['last_error']  = $reason;
		$item['last_status'] = $status_code;
		$item['next_due_at'] = time() + $this->backoff_seconds( $attempts );

		$this->save_idempotency_marker( $event_id, 'retrying' );

		return array(
			'retrying' => true,
			'item'     => $item,
		);
	}

	/**
	 * DLQ に送る。
	 *
	 * @param string              $event_id Event id。
	 * @param string              $reason Reason。
	 * @param int                 $status_code Producer status。
	 * @param array<string,mixed> $item Outbox item。
	 * @return void
	 */
	private function dead_letter( string $event_id, string $reason, int $status_code, array $item ): void {
		$event_id = '' !== $event_id ? $event_id : 'missing_event_id_' . substr( hash( 'sha256', wp_json_encode( $item ) ), 0, 16 );
		$dlq      = $this->dlq();
		$record   = array(
			'event_id'    => $event_id,
			'reason'      => $reason,
			'status'      => $status_code,
			'attempts'    => isset( $item['attempts'] ) ? (int) $item['attempts'] : 0,
			'event_kind'  => isset( $item['event_kind'] ) ? (string) $item['event_kind'] : '',
			'dead_at'     => time(),
			'dead_at_iso' => gmdate( 'c' ),
		);

		array_unshift( $dlq, $record );
		$dlq = array_slice( $dlq, 0, self::MAX_DLQ_ITEMS );
		update_option( self::DLQ_OPTION, $dlq, false );
		$this->save_idempotency_marker( $event_id, 'dead:' . $reason );

		do_action( 'agent_neo_catalog_update_dead_letter', $event_id, $reason, $record );
	}

	/**
	 * 成功 receipt を保存する。
	 *
	 * @param array<string,mixed> $response Response body。
	 * @param array<string,mixed> $item Outbox item。
	 * @return void
	 */
	private function record_receipt( array $response, array $item ): void {
		$receipts = get_option( self::RECEIPT_OPTION, array() );
		if ( ! is_array( $receipts ) ) {
			$receipts = array();
		}

		array_unshift(
			$receipts,
			array(
				'event_id'      => (string) $response['event_id'],
				'received'      => (bool) $response['received'],
				'deduplicated'  => (bool) $response['deduplicated'],
				'next_action'   => (string) $response['next_action'],
				'attempts'      => isset( $item['attempts'] ) ? (int) $item['attempts'] : 0,
				'received_at'   => time(),
				'received_iso'  => gmdate( 'c' ),
				'response_keys' => array_keys( $response ),
			)
		);

		$receipts = array_slice( $receipts, 0, self::MAX_RECEIPTS );
		update_option( self::RECEIPT_OPTION, $receipts, false );
	}

	/**
	 * HMAC headers を作る。
	 *
	 * @param string $body Canonical JSON body。
	 * @return array<string,string>|WP_Error
	 */
	private function signature_headers( string $body ) {
		$key = $this->hmac_key();
		if ( '' === $key ) {
			return new WP_Error( 'PLUGIN_AUTH_FAILED', __( 'catalog-update HMAC key is not configured.', 'agent-neo-core' ) );
		}

		$timestamp  = (string) time();
		$once_token = $this->once_token();
		$payload    = implode(
			'|',
			array(
				'POST',
				self::ENDPOINT_PATH,
				$timestamp,
				$once_token,
				hash( 'sha256', $body ),
			)
		);

		return array(
			'X-App-Timestamp'  => $timestamp,
			'X-App-Once-Token' => $once_token,
			'X-App-Signature'  => base64_encode( hash_hmac( 'sha256', $payload, $key, true ) ),
			'X-App-Window'     => (string) self::SIGNATURE_WINDOW,
		);
	}

	/**
	 * 送信先 URL を返す。
	 *
	 * @return string|WP_Error
	 */
	private function endpoint_url() {
		$endpoint = $this->first_string(
			array(
				$this->env( 'AGENT_NEO_CATALOG_UPDATE_ENDPOINT' ),
				$this->env( 'AGENT_NEO_ASEO_CATALOG_UPDATE_ENDPOINT' ),
				get_option( 'agent_neo_catalog_update_endpoint', '' ),
				get_option( 'agent_neo_aseo_catalog_update_endpoint', '' ),
			)
		);

		if ( '' === $endpoint ) {
			$base = $this->first_string(
				array(
					$this->env( 'AGENT_NEO_ASEO_BASE_URL' ),
					get_option( 'agent_neo_aseo_base_url', '' ),
				)
			);
			if ( '' !== $base ) {
				$endpoint = untrailingslashit( $base ) . self::ENDPOINT_PATH;
			}
		}

		$endpoint = (string) apply_filters( 'agent_neo_catalog_update_endpoint', $endpoint );
		if ( '' === $endpoint ) {
			return new WP_Error( 'ENDPOINT_NOT_CONFIGURED', __( 'catalog-update endpoint is not configured.', 'agent-neo-core' ) );
		}

		$endpoint = esc_url_raw( $endpoint );
		$parts    = wp_parse_url( $endpoint );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) || 'https' !== strtolower( (string) $parts['scheme'] ) ) {
			return new WP_Error( 'ENDPOINT_NOT_ALLOWED', __( 'catalog-update endpoint must be HTTPS.', 'agent-neo-core' ) );
		}

		$host = strtolower( (string) $parts['host'] );
		if ( ! $this->is_allowed_endpoint_host( $host ) ) {
			return new WP_Error( 'ENDPOINT_NOT_ALLOWED', __( 'catalog-update endpoint host is not allowlisted.', 'agent-neo-core' ) );
		}

		return $endpoint;
	}

	/**
	 * host allowlist を確認する。
	 *
	 * @param string $host Host。
	 * @return bool
	 */
	private function is_allowed_endpoint_host( string $host ): bool {
		$allowlist = $this->endpoint_host_allowlist();
		if ( empty( $allowlist ) ) {
			return false;
		}

		return in_array( $host, $allowlist, true );
	}

	/**
	 * endpoint host allowlist を返す。
	 *
	 * @return array<int,string>
	 */
	private function endpoint_host_allowlist(): array {
		$values = array(
			$this->env( 'AGENT_NEO_ASEO_ALLOWED_HOSTS' ),
			get_option( 'agent_neo_aseo_allowed_hosts', array() ),
		);

		$values = apply_filters( 'agent_neo_catalog_update_allowed_hosts', $values );
		$hosts  = array();

		foreach ( (array) $values as $value ) {
			if ( is_string( $value ) ) {
				$value = preg_split( '/[\s,]+/', $value );
			}
			if ( ! is_array( $value ) ) {
				continue;
			}
			foreach ( $value as $host ) {
				if ( ! is_string( $host ) ) {
					continue;
				}
				$host = strtolower( trim( $host ) );
				if ( '' !== $host ) {
					$hosts[ $host ] = true;
				}
			}
		}

		return array_keys( $hosts );
	}

	/**
	 * HMAC key を env/option から返す。
	 *
	 * @return string
	 */
	private function hmac_key(): string {
		return (string) apply_filters(
			'agent_neo_catalog_update_hmac_key',
			$this->first_string(
				array(
					$this->env( 'AGENT_NEO_CATALOG_UPDATE_HMAC_KEY' ),
					$this->env( 'AGENT_NEO_HMAC_KEY' ),
					get_option( 'agent_neo_catalog_update_hmac_key', '' ),
					get_option( 'agent_neo_hmac_key', '' ),
				)
			)
		);
	}

	/**
	 * site_hash を返す。
	 *
	 * @return string
	 */
	private function site_hash(): string {
		$site_hash = $this->first_string(
			array(
				$this->env( 'AGENT_NEO_SITE_HASH' ),
				get_option( 'agent_neo_site_hash', '' ),
			)
		);

		if ( '' === $site_hash ) {
			$site_hash = 'site_' . substr( hash( 'sha256', home_url( '/' ) ), 0, 32 );
		}

		return $this->sanitize_string( (string) apply_filters( 'agent_neo_catalog_update_site_hash', $site_hash ) );
	}

	/**
	 * option が theme token 更新対象かを返す。
	 *
	 * @param string $option Option name。
	 * @return bool
	 */
	private function is_theme_token_option( string $option ): bool {
		$tracked = array(
			'agent_neo_theme_tokens',
			'agent_neo_design_tokens',
			'theme_mods_agent-neo',
			'wp_global_styles',
		);
		$tracked = apply_filters( 'agent_neo_catalog_update_token_options', $tracked );

		return is_array( $tracked ) && in_array( $option, $tracked, true );
	}

	/**
	 * block を追跡対象にするか返す。
	 *
	 * @param string $block_name Block name。
	 * @return bool
	 */
	private function should_track_block( string $block_name ): bool {
		$namespaces = apply_filters( 'agent_neo_catalog_update_block_namespaces', array( 'agent-neo/' ) );
		foreach ( (array) $namespaces as $namespace ) {
			if ( is_string( $namespace ) && str_starts_with( $block_name, $namespace ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * block value から名前を得る。
	 *
	 * @param mixed $block_type Block type。
	 * @return string
	 */
	private function block_name_from_value( $block_type ): string {
		if ( is_string( $block_type ) ) {
			return $this->sanitize_string( $block_type );
		}

		if ( is_object( $block_type ) && isset( $block_type->name ) && is_string( $block_type->name ) ) {
			return $this->sanitize_string( $block_type->name );
		}

		return '';
	}

	/**
	 * Outbox state を返す。
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function outbox(): array {
		$outbox = get_option( self::OUTBOX_OPTION, array() );
		return is_array( $outbox ) ? $outbox : array();
	}

	/**
	 * Outbox state を保存する。
	 *
	 * @param array<string,array<string,mixed>> $outbox Outbox。
	 * @return void
	 */
	private function save_outbox( array $outbox ): void {
		uasort(
			$outbox,
			static function ( array $left, array $right ): int {
				return ( (int) ( $left['created_at'] ?? 0 ) ) <=> ( (int) ( $right['created_at'] ?? 0 ) );
			}
		);

		if ( count( $outbox ) > self::MAX_QUEUE_ITEMS ) {
			$outbox = array_slice( $outbox, - self::MAX_QUEUE_ITEMS, null, true );
		}

		update_option( self::OUTBOX_OPTION, $outbox, false );
	}

	/**
	 * DLQ state を返す。
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function dlq(): array {
		$dlq = get_option( self::DLQ_OPTION, array() );
		return is_array( $dlq ) ? $dlq : array();
	}

	/**
	 * known block index を返す。
	 *
	 * @return array<string,string>
	 */
	private function known_blocks(): array {
		$known = get_option( self::KNOWN_BLOCKS, array() );
		return is_array( $known ) ? $known : array();
	}

	/**
	 * known block index を保存する。
	 *
	 * @param array<string,string> $known Known blocks。
	 * @return void
	 */
	private function save_known_blocks( array $known ): void {
		ksort( $known );
		update_option( self::KNOWN_BLOCKS, $known, false );
	}

	/**
	 * queue status を返す。
	 *
	 * @return array<string,mixed>
	 */
	private function queue_status(): array {
		$outbox  = $this->outbox();
		$dlq     = $this->dlq();
		$pending = 0;
		$retrying = 0;
		$running = 0;

		foreach ( $outbox as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$status = isset( $item['status'] ) ? (string) $item['status'] : 'pending';
			if ( 'retrying' === $status ) {
				++$retrying;
			} elseif ( 'running' === $status ) {
				++$running;
			} else {
				++$pending;
			}
		}

		$producer_status = '200 OK';
		if ( ! empty( $dlq ) ) {
			$latest = $dlq[0];
			$status = isset( $latest['status'] ) ? (int) $latest['status'] : 409;
			$reason = isset( $latest['reason'] ) ? (string) $latest['reason'] : 'DLQ';
			$producer_status = $status . ' ' . $reason;
		}

		return array(
			'producer_status' => $producer_status,
			'outbox'          => array(
				'pending'  => $pending,
				'retrying' => $retrying,
				'running'  => $running,
				'total'    => count( $outbox ),
			),
			'dlq'             => array(
				'dead'   => count( $dlq ),
				'latest' => isset( $dlq[0] ) && is_array( $dlq[0] ) ? $dlq[0] : null,
			),
		);
	}

	/**
	 * event idempotency marker を返す。
	 *
	 * @param string $event_id Event id。
	 * @return mixed
	 */
	private function idempotency_marker( string $event_id ) {
		return get_transient( $this->idempotency_key( $event_id ) );
	}

	/**
	 * event idempotency marker を保存する。
	 *
	 * @param string $event_id Event id。
	 * @param string $status Status。
	 * @return void
	 */
	private function save_idempotency_marker( string $event_id, string $status ): void {
		if ( '' === $event_id ) {
			return;
		}

		set_transient(
			$this->idempotency_key( $event_id ),
			array(
				'active'     => true,
				'status'     => $status,
				'updated_at' => time(),
			),
			self::EVENT_TTL
		);
	}

	/**
	 * idempotency transient key を返す。
	 *
	 * @param string $event_id Event id。
	 * @return string
	 */
	private function idempotency_key( string $event_id ): string {
		return 'agent_neo_cat_evt_' . substr( hash( 'sha256', $event_id ), 0, 40 );
	}

	/**
	 * 次回処理を schedule する。
	 *
	 * @param int $timestamp Timestamp。
	 * @return void
	 */
	private function schedule_processing( int $timestamp ): void {
		if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_single_event' ) ) {
			return;
		}

		$timestamp = max( time(), $timestamp );
		$scheduled = wp_next_scheduled( self::CRON_HOOK );
		if ( false === $scheduled ) {
			wp_schedule_single_event( $timestamp, self::CRON_HOOK );
			return;
		}

		if ( $scheduled > $timestamp && function_exists( 'wp_unschedule_event' ) ) {
			wp_unschedule_event( $scheduled, self::CRON_HOOK );
			wp_schedule_single_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Outbox の次回 due を schedule する。
	 *
	 * @param array<string,array<string,mixed>> $outbox Outbox。
	 * @return void
	 */
	private function schedule_next_due( array $outbox ): void {
		$next = null;
		foreach ( $outbox as $item ) {
			if ( is_array( $item ) && isset( $item['next_due_at'] ) ) {
				$due  = (int) $item['next_due_at'];
				$next = null === $next ? $due : min( $next, $due );
			}
		}

		if ( null !== $next ) {
			$this->schedule_processing( $next );
		}
	}

	/**
	 * Retry backoff seconds を返す。
	 *
	 * @param int $attempts Attempts after current failure。
	 * @return int
	 */
	private function backoff_seconds( int $attempts ): int {
		$base   = self::INITIAL_BACKOFF * ( 2 ** max( 0, $attempts - 1 ) );
		$jitter = (float) wp_rand( 900, 1100 ) / 1000;
		return max( 1, (int) round( $base * $jitter ) );
	}

	/**
	 * Retryable HTTP status か返す。
	 *
	 * @param int $status_code Status code。
	 * @return bool
	 */
	private function is_retryable_status( int $status_code ): bool {
		return 429 === $status_code || $status_code >= 500 || 0 === $status_code;
	}

	/**
	 * Status code に対応する error code を返す。
	 *
	 * @param int $status_code Status code。
	 * @return string
	 */
	private function error_code_for_status( int $status_code ): string {
		if ( 400 === $status_code ) {
			return 'VALIDATION_ERROR';
		}
		if ( 401 === $status_code ) {
			return 'PLUGIN_AUTH_FAILED';
		}
		if ( 409 === $status_code ) {
			return 'AGENT_NEO_NOT_INSTALLED';
		}
		if ( 429 === $status_code ) {
			return 'RATE_LIMITED';
		}
		if ( $status_code >= 500 ) {
			return 'INTERNAL_ERROR';
		}

		return 'HTTP_' . $status_code;
	}

	/**
	 * §17.2 response の4フィールドだけを検証する。
	 *
	 * @param array<string,mixed> $response Response body。
	 * @param string              $event_id Event id。
	 * @return bool
	 */
	private function is_valid_response( array $response, string $event_id ): bool {
		foreach ( array( 'received', 'event_id', 'deduplicated', 'next_action' ) as $field ) {
			if ( ! array_key_exists( $field, $response ) ) {
				return false;
			}
		}

		return is_bool( $response['received'] )
			&& is_string( $response['event_id'] )
			&& hash_equals( $event_id, (string) $response['event_id'] )
			&& is_bool( $response['deduplicated'] )
			&& in_array( $response['next_action'], array( 'scan-catalog', 'none' ), true );
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

		if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			ksort( $value );
		}

		return $value;
	}

	/**
	 * Payload を保存/送信用に sanitize する。
	 *
	 * @param mixed $payload Payload。
	 * @return mixed
	 */
	private function sanitize_payload( $payload ) {
		if ( is_array( $payload ) ) {
			$sanitized = array();
			foreach ( $payload as $key => $value ) {
				$clean_key = is_string( $key ) ? sanitize_key( $key ) : $key;
				if ( '' === $clean_key ) {
					continue;
				}
				$sanitized[ $clean_key ] = $this->sanitize_payload( $value );
			}
			return $sanitized;
		}

		if ( is_string( $payload ) ) {
			return $this->sanitize_string( $payload );
		}

		return is_scalar( $payload ) || null === $payload ? $payload : null;
	}

	/**
	 * 文字列を sanitize する。
	 *
	 * @param string $value Value。
	 * @return string
	 */
	private function sanitize_string( string $value ): string {
		return substr( sanitize_text_field( $value ), 0, self::MAX_STRING_LENGTH );
	}

	/**
	 * 値 hash を返す。
	 *
	 * @param mixed $value Value。
	 * @return string
	 */
	private function value_hash( $value ): string {
		return 'sha256:' . hash( 'sha256', $this->canonical_json( $value ) );
	}

	/**
	 * URL-safe once-token を生成する。
	 *
	 * @return string
	 */
	private function once_token(): string {
		$bytes = random_bytes( 32 );
		return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
	}

	/**
	 * event_id を生成する。
	 *
	 * @return string
	 */
	private function new_event_id(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}

		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0x0fff ) | 0x4000,
			wp_rand( 0, 0x3fff ) | 0x8000,
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff )
		);
	}

	/**
	 * 最初の非空文字列を返す。
	 *
	 * @param array<int,mixed> $values Values。
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
	 * getenv wrapper。
	 *
	 * @param string $name Env name。
	 * @return string
	 */
	private function env( string $name ): string {
		$value = getenv( $name );
		return is_string( $value ) ? $value : '';
	}
}

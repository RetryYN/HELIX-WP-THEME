<?php
/**
 * GET /logs controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AgentAction audit log read endpoint.
 */
final class Agent_Neo_Core_Logs_Controller extends Agent_Neo_Core_REST_Controller_Base {
	private const DEFAULT_PER_PAGE = 20;
	private const MAX_PER_PAGE     = 100;

	private Agent_Neo_Core_Audit_Log $audit_log;

	/**
	 * @param Agent_Neo_Core_Audit_Log $audit_log Audit log.
	 */
	public function __construct( Agent_Neo_Core_Audit_Log $audit_log ) {
		$this->audit_log = $audit_log;
	}

	/**
	 * rest_api_init に route 登録を接続する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'agent_neo_tracking_event_accepted', array( $this, 'record_tracking_event' ) );
	}

	/**
	 * GET /logs を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->register_agent_route(
			'/logs',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_logs' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);
	}

	/**
	 * GET /logs の閲覧権限を確認する。
	 *
	 * @return true|WP_Error
	 */
	public function check_read_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO operation logs.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability for AGENT NEO operation logs.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * GET /logs.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_logs( WP_REST_Request $request ) {
		$filters = $this->filters( $request );
		if ( is_wp_error( $filters ) ) {
			return $filters;
		}

		$query = new WP_Query( $this->query_args( $filters ) );
		$logs  = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$logs[] = $this->log_entry( $post );
			}
		}

		$page        = (int) $filters['page'];
		$per_page    = (int) $filters['per_page'];
		$total       = (int) $query->found_posts;
		$total_pages = (int) $query->max_num_pages;
		$request_id  = $this->request_id( $request );
		$response    = Agent_Neo_Core_Auth::success_response(
			array(
				'logs' => $logs,
			),
			$request_id
		);

		$response['meta']['pagination'] = array(
			'page'        => $page,
			'per_page'    => $per_page,
			'total'       => $total,
			'total_pages' => $total_pages,
			'has_next'    => $page < $total_pages,
			'has_prev'    => $page > 1,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Tracking event を agent_action CPT に監査保存する。
	 *
	 * @param array<string, mixed> $event Tracking event.
	 * @return void
	 */
	public function record_tracking_event( array $event ): void {
		$event_id = isset( $event['event_id'] ) && is_scalar( $event['event_id'] )
			? sanitize_text_field( (string) $event['event_id'] )
			: 'evt_' . substr( hash( 'sha256', wp_json_encode( $event ) ?: wp_generate_uuid4() ), 0, 32 );

		$this->audit_log->record(
			'tracking_event',
			$event_id,
			hash( 'sha256', wp_json_encode( $event ) ?: $event_id ),
			$event_id,
			array(
				'target'      => $this->tracking_target( $event ),
				'diff'        => array(
					'event_type'  => isset( $event['event_type'] ) ? (string) $event['event_type'] : '',
					'section_id'  => isset( $event['section_id'] ) ? (string) $event['section_id'] : '',
					'cta_id'      => isset( $event['cta_id'] ) ? (string) $event['cta_id'] : '',
					'variant_id'  => isset( $event['variant_id'] ) ? (string) $event['variant_id'] : '',
					'article_id'  => isset( $event['article_id'] ) ? (string) $event['article_id'] : '',
					'accepted_at' => isset( $event['accepted_at'] ) ? (string) $event['accepted_at'] : '',
				),
				'event_type'  => isset( $event['event_type'] ) ? sanitize_key( (string) $event['event_type'] ) : '',
				'accepted_at' => isset( $event['accepted_at'] ) ? sanitize_text_field( (string) $event['accepted_at'] ) : '',
			)
		);
	}

	/**
	 * Request filters を検証して返す。
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array<string, mixed>|WP_Error
	 */
	private function filters( WP_REST_Request $request ) {
		$page     = $this->positive_int( $request->get_param( 'page' ), 1, 'page' );
		$per_page = $this->positive_int( $request->get_param( 'per_page' ), self::DEFAULT_PER_PAGE, 'per_page' );
		if ( is_wp_error( $page ) ) {
			return $page;
		}

		if ( is_wp_error( $per_page ) ) {
			return $per_page;
		}

		if ( $per_page > self::MAX_PER_PAGE ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'per_page exceeds the maximum page size.', 'agent-neo-core' ),
				array( 'max' => self::MAX_PER_PAGE )
			);
		}

		$status = $this->optional_token( $request->get_param( 'status' ), 'status' );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$actor = $this->actor_filter( $request->get_param( 'actor' ) );
		if ( is_wp_error( $actor ) ) {
			return $actor;
		}

		$target = $this->optional_text( $request->get_param( 'target' ), 'target' );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$request_id = $this->optional_text( $request->get_param( 'request_id' ), 'request_id' );
		if ( is_wp_error( $request_id ) ) {
			return $request_id;
		}

		$from_value = $request->get_param( 'from' ) ?? $request->get_param( 'date_from' ) ?? $request->get_param( 'after' ) ?? $request->get_param( 'start' );
		$to_value   = $request->get_param( 'to' ) ?? $request->get_param( 'date_to' ) ?? $request->get_param( 'before' ) ?? $request->get_param( 'end' );
		$from       = $this->optional_date( $from_value, 'from' );
		if ( is_wp_error( $from ) ) {
			return $from;
		}

		$to = $this->optional_date( $to_value, 'to' );
		if ( is_wp_error( $to ) ) {
			return $to;
		}

		if ( '' !== $from && '' !== $to && strtotime( $from ) > strtotime( $to ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'from must be earlier than or equal to to.', 'agent-neo-core' ),
				array(
					'from' => $from,
					'to'   => $to,
				)
			);
		}

		return array(
			'page'       => $page,
			'per_page'   => $per_page,
			'status'     => $status,
			'actor'      => $actor,
			'target'     => $target,
			'request_id' => $request_id,
			'from'       => $from,
			'to'         => $to,
		);
	}

	/**
	 * WP_Query args を組み立てる。
	 *
	 * @param array<string, mixed> $filters Filters.
	 * @return array<string, mixed>
	 */
	private function query_args( array $filters ): array {
		$args = array(
			'post_type'           => Agent_Neo_Core_Agent_Action_CPT::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $filters['per_page'],
			'paged'               => (int) $filters['page'],
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);

		$meta_query = array( 'relation' => 'AND' );
		foreach ( array( 'status', 'actor', 'target', 'request_id' ) as $filter ) {
			if ( '' !== $filters[ $filter ] ) {
				$meta_query[] = array(
					'key'     => '_agent_neo_' . $filter,
					'value'   => (string) $filters[ $filter ],
					'compare' => '=',
				);
			}
		}

		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query;
		}

		$date_query = array();
		if ( '' !== $filters['from'] ) {
			$date_query['after'] = (string) $filters['from'];
		}
		if ( '' !== $filters['to'] ) {
			$date_query['before'] = (string) $filters['to'];
		}
		if ( array() !== $date_query ) {
			$date_query['inclusive'] = true;
			$args['date_query']      = array( $date_query );
		}

		return $args;
	}

	/**
	 * Log entry を整形する。
	 *
	 * @param WP_Post $post Audit post.
	 * @return array<string, mixed>
	 */
	private function log_entry( WP_Post $post ): array {
		$details = $this->details( $post->ID );

		return array(
			'id'          => (int) $post->ID,
			'audit_id'    => $this->meta( $post->ID, '_agent_neo_audit_id' ),
			'action_type' => $this->meta( $post->ID, '_agent_neo_action_type' ),
			'actor'       => $this->actor( $post ),
			'request_id'  => $this->meta( $post->ID, '_agent_neo_request_id' ),
			'target'      => $this->target( $post->ID, $details ),
			'diff'        => $this->diff( $post->ID ),
			'status'      => $this->status( $post->ID ),
			'created_at'  => get_post_time( 'c', true, $post ),
			'details'     => $details,
		);
	}

	/**
	 * Scalar meta を返す。
	 *
	 * @param int    $post_id Post id.
	 * @param string $key Meta key.
	 * @return string
	 */
	private function meta( int $post_id, string $key ): string {
		$value = get_post_meta( $post_id, $key, true );

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Actor を返す。
	 *
	 * @param WP_Post $post Audit post.
	 * @return string
	 */
	private function actor( WP_Post $post ): string {
		$actor = $this->meta( $post->ID, '_agent_neo_actor' );
		if ( '' !== $actor ) {
			return $actor;
		}

		return $post->post_author > 0 ? (string) $post->post_author : 'system';
	}

	/**
	 * Target を返す。
	 *
	 * @param int                  $post_id Post id.
	 * @param array<string, mixed> $details Details.
	 * @return string
	 */
	private function target( int $post_id, array $details ): string {
		$target = $this->meta( $post_id, '_agent_neo_target' );
		if ( '' !== $target ) {
			return $target;
		}

		foreach ( array( 'target', 'post_id', 'page_id', 'resource_id', 'rollback_point_id' ) as $key ) {
			if ( isset( $details[ $key ] ) && is_scalar( $details[ $key ] ) && '' !== (string) $details[ $key ] ) {
				return in_array( $key, array( 'post_id', 'page_id', 'resource_id' ), true )
					? 'post:' . (string) absint( $details[ $key ] )
					: sanitize_text_field( (string) $details[ $key ] );
			}
		}

		return 'unknown';
	}

	/**
	 * Diff を返す。
	 *
	 * @param int $post_id Post id.
	 * @return array<string, mixed>|array<int, mixed>
	 */
	private function diff( int $post_id ): array {
		$stored = $this->meta( $post_id, '_agent_neo_diff' );
		if ( '' !== $stored ) {
			$decoded = json_decode( $stored, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return array( 'hash' => $this->meta( $post_id, '_agent_neo_diff_hash' ) );
	}

	/**
	 * Status を返す。
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function status( int $post_id ): string {
		$status = $this->meta( $post_id, '_agent_neo_status' );

		return '' !== $status ? $status : 'unknown';
	}

	/**
	 * Details JSON を返す。
	 *
	 * @param int $post_id Post id.
	 * @return array<string, mixed>
	 */
	private function details( int $post_id ): array {
		$details = get_post_meta( $post_id, '_agent_neo_details', true );
		if ( ! is_string( $details ) || '' === $details ) {
			return array();
		}

		$decoded = json_decode( $details, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Positive integer filter.
	 *
	 * @param mixed  $value Value.
	 * @param int    $default Default.
	 * @param string $field Field.
	 * @return int|WP_Error
	 */
	private function positive_int( $value, int $default, string $field ) {
		if ( null === $value || '' === $value ) {
			return $default;
		}

		if ( is_int( $value ) ) {
			$number = $value;
		} elseif ( is_string( $value ) && ctype_digit( $value ) ) {
			$number = (int) $value;
		} else {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Pagination parameter must be a positive integer.', 'agent-neo-core' ), array( 'field' => $field ) );
		}

		if ( $number < 1 ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Pagination parameter must be a positive integer.', 'agent-neo-core' ), array( 'field' => $field ) );
		}

		return $number;
	}

	/**
	 * Optional token filter.
	 *
	 * @param mixed  $value Value.
	 * @param string $field Field.
	 * @return string|WP_Error
	 */
	private function optional_token( $value, string $field ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		if ( ! is_string( $value ) || 1 !== preg_match( '/^[a-z0-9_-]{1,64}$/i', $value ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Filter value is invalid.', 'agent-neo-core' ), array( 'field' => $field ) );
		}

		return sanitize_key( $value );
	}

	/**
	 * Actor filter.
	 *
	 * @param mixed $value Value.
	 * @return string|WP_Error
	 */
	private function actor_filter( $value ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		if ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) {
			return (string) absint( $value );
		}

		if ( ! is_string( $value ) || strlen( $value ) > 60 ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'actor filter is invalid.', 'agent-neo-core' ), array( 'field' => 'actor' ) );
		}

		if ( 'system' === $value ) {
			return 'system';
		}

		$user = get_user_by( 'login', $value );
		if ( false === $user ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'actor filter does not match a user.', 'agent-neo-core' ), array( 'field' => 'actor' ) );
		}

		return (string) $user->ID;
	}

	/**
	 * Optional text filter.
	 *
	 * @param mixed  $value Value.
	 * @param string $field Field.
	 * @return string|WP_Error
	 */
	private function optional_text( $value, string $field ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		if ( ! is_string( $value ) || strlen( $value ) > 160 ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Filter value is invalid.', 'agent-neo-core' ), array( 'field' => $field ) );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Optional date filter.
	 *
	 * @param mixed  $value Value.
	 * @param string $field Field.
	 * @return string|WP_Error
	 */
	private function optional_date( $value, string $field ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		if ( ! is_string( $value ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Date filter is invalid.', 'agent-neo-core' ), array( 'field' => $field ) );
		}

		$timestamp = strtotime( $value );
		if ( false === $timestamp ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Date filter is invalid.', 'agent-neo-core' ), array( 'field' => $field ) );
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Request id を返す。
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return string
	 */
	private function request_id( WP_REST_Request $request ): string {
		$request_id = $request->get_header( 'X-Request-Id' );

		return is_string( $request_id ) && '' !== $request_id ? sanitize_text_field( $request_id ) : wp_generate_uuid4();
	}

	/**
	 * Tracking event の監査 target を返す。
	 *
	 * @param array<string, mixed> $event Tracking event.
	 * @return string
	 */
	private function tracking_target( array $event ): string {
		$parts = array();
		foreach ( array( 'article_id', 'section_id', 'cta_id', 'variant_id' ) as $key ) {
			if ( isset( $event[ $key ] ) && is_scalar( $event[ $key ] ) && '' !== (string) $event[ $key ] ) {
				$parts[] = $key . ':' . sanitize_text_field( (string) $event[ $key ] );
			}
		}

		return array() === $parts ? 'tracking:unknown' : 'tracking:' . implode( '|', $parts );
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Logs_Controller(
			$container->audit_log()
		);
		$controller->register();
		$container->register_module( 'rest-logs' );
	}
);

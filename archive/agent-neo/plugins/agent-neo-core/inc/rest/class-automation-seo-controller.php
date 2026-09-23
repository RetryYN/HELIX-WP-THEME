<?php
/**
 * Automation SEO 連携 REST controller。
 *
 * GET  /agent-neo/v1/automation-seo/fit           — theme capability / section / CTA / SEO mapping 診断
 * POST /agent-neo/v1/automation-seo/fit           — safe apply readiness 同期・連携設定保存
 * GET  /agent-neo/v1/automation-seo/bridge-profile — Theme Bridge Plugin 互換プロファイル
 *
 * REQ-NF-019 (A-023) / REQ-NF-020 (A-024) / REQ-NF-025（AI ロジック禁止）
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Automation SEO 連携 REST endpoint 群。
 *
 * theme capability の決定的診断・連携設定保存・bridge profile の決定的導出のみを担う。
 * AI による最適化判断は持たない（REQ-NF-025）。
 */
final class Agent_Neo_Core_Automation_SEO_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * 連携設定 option name。
	 */
	private const OPTION_INTEGRATION = 'agent_neo_automation_seo_integration';

	/**
	 * AGENT NEO テーマ slug。
	 */
	private const AGENT_NEO_THEME_SLUG = 'agent-neo-theme';

	/**
	 * Auth helper。
	 *
	 * @var Agent_Neo_Core_Auth
	 */
	private Agent_Neo_Core_Auth $auth;

	/**
	 * License state。
	 *
	 * @var Agent_Neo_Core_License_State
	 */
	private Agent_Neo_Core_License_State $license_state;

	/**
	 * @param Agent_Neo_Core_Auth          $auth          Auth helper。
	 * @param Agent_Neo_Core_License_State $license_state License state。
	 */
	public function __construct( Agent_Neo_Core_Auth $auth, Agent_Neo_Core_License_State $license_state ) {
		$this->auth          = $auth;
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
	 * 全 route を登録する。
	 *
	 * GET と POST は coverage チェックが別々に検出するよう 2 回 register_agent_route する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /automation-seo/fit — 診断のみ。
		$this->register_agent_route(
			'/automation-seo/fit',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_fit' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// POST /automation-seo/fit — 同期 / apply。
		$this->register_agent_route(
			'/automation-seo/fit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'post_fit' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
				'args'                => $this->post_fit_args(),
			)
		);

		// GET /automation-seo/bridge-profile — Bridge Plugin 互換プロファイル。
		$this->register_agent_route(
			'/automation-seo/bridge-profile',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_bridge_profile' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * GET 系の permission callback。ログイン + read capability を要求する。
	 *
	 * @return true|WP_Error
	 */
	public function check_read_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO Automation SEO.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability for AGENT NEO Automation SEO.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * POST 系の permission callback。nonce + manage_options を要求する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		return $this->auth->check_write_permission( $request, 'manage_options' );
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * GET /automation-seo/fit — theme capability 診断を返す。
	 *
	 * アクティブテーマが AGENT NEO か、対応 capability（section 操作 / CTA / SEO / blueprint）一覧、
	 * 連携準備状況を決定的に返す。AI による判断は行わない（REQ-NF-025）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response
	 */
	public function get_fit( WP_REST_Request $request ): WP_REST_Response {
		$request_id = $this->resolve_request_id( $request );

		$is_agent_neo = $this->is_agent_neo_theme_active();
		$capabilities = $this->resolve_capabilities( $is_agent_neo );
		$integration  = $this->load_integration_option();

		$data = array(
			'is_agent_neo_theme' => $is_agent_neo,
			'theme'              => $this->theme_info(),
			'capabilities'       => $capabilities,
			'readiness'          => $this->resolve_readiness( $is_agent_neo, $integration ),
			'integration'        => $integration,
			'license'            => array(
				'mode'    => $this->license_state->license_mode(),
				'package' => $this->license_state->package(),
			),
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * POST /automation-seo/fit — 連携設定を保存し readiness を返す。
	 *
	 * 受信した設定値を sanitize して option に永続化する。
	 * AI による最適化判断は行わない（REQ-NF-025）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function post_fit( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );

		// 入力取得・sanitize。
		$params      = $request->get_json_params();
		$params      = is_array( $params ) ? $params : array();
		$integration = $this->sanitize_integration_params( $params );

		// URL が指定されている場合は SSRF ガードを適用する。
		// 保存のみでも将来の発火時に内部ネットワークへアクセスするリスクがあるため防御する。
		if ( isset( $integration['automation_seo_url'] ) && '' !== $integration['automation_seo_url'] ) {
			$ssrf_check = $this->validate_url_not_internal( $integration['automation_seo_url'] );
			if ( is_wp_error( $ssrf_check ) ) {
				return $ssrf_check;
			}
		}

		// 既存設定とマージして保存。
		$current = $this->load_integration_option();
		$merged  = array_merge( $current, $integration, array( 'updated_at' => gmdate( DATE_ATOM ) ) );
		update_option( self::OPTION_INTEGRATION, $merged, false );

		$is_agent_neo = $this->is_agent_neo_theme_active();

		$data = array(
			'saved'      => true,
			'readiness'  => $this->resolve_readiness( $is_agent_neo, $merged ),
			'integration' => $merged,
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * GET /automation-seo/bridge-profile — Theme Bridge Plugin 互換プロファイルを返す。
	 *
	 * 既存テーマ横断情報を source / confidence 付きで返す。
	 * safe_apply_state は AGENT NEO テーマで `write-ready`、それ以外は `preview-only` 固定。
	 * AI による判断は行わない（REQ-NF-025）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response
	 */
	public function get_bridge_profile( WP_REST_Request $request ): WP_REST_Response {
		$request_id   = $this->resolve_request_id( $request );
		$is_agent_neo = $this->is_agent_neo_theme_active();

		// safe_apply_state: AGENT NEO テーマのみ write-ready（ADR-019）。
		$safe_apply_state = $is_agent_neo ? 'write-ready' : 'preview-only';

		$data = array(
			'safe_apply_state'  => $safe_apply_state,
			'theme'             => $this->theme_bridge_info( $is_agent_neo ),
			'capabilities'      => $this->resolve_capabilities( $is_agent_neo ),
			'section_support'   => $this->resolve_section_support( $is_agent_neo ),
			'cta_support'       => $this->resolve_cta_support( $is_agent_neo ),
			'seo_mapping'       => $this->resolve_seo_mapping( $is_agent_neo ),
			'blueprint_support' => $this->resolve_blueprint_support( $is_agent_neo ),
			'license'           => array(
				'mode'    => $this->license_state->license_mode(),
				'package' => $this->license_state->package(),
			),
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	// -------------------------------------------------------------------------
	// POST /fit args schema
	// -------------------------------------------------------------------------

	/**
	 * POST /automation-seo/fit の引数スキーマを返す。
	 *
	 * @return array<string, mixed>
	 */
	private function post_fit_args(): array {
		return array(
			'automation_seo_url' => array(
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'sanitize_url',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => 'Automation SEO バックエンド URL。',
			),
			'site_hash' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => 'テナント識別子。',
			),
			'sync_enabled' => array(
				'type'              => 'boolean',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => '同期有効フラグ。',
			),
		);
	}

	// -------------------------------------------------------------------------
	// Theme determination helpers
	// -------------------------------------------------------------------------

	/**
	 * アクティブテーマが AGENT NEO テーマかを返す。
	 *
	 * status-controller の判定ロジックに準拠する。
	 *
	 * @return bool
	 */
	private function is_agent_neo_theme_active(): bool {
		$theme      = wp_get_theme();
		$stylesheet = $theme->get_stylesheet();
		$template   = $theme->get_template();

		return $this->is_agent_neo_theme_slug( $stylesheet )
			|| $this->is_agent_neo_theme_slug( $template );
	}

	/**
	 * Theme slug が AGENT NEO テーマかを判定する。
	 *
	 * @param string $slug Theme slug。
	 * @return bool
	 */
	private function is_agent_neo_theme_slug( string $slug ): bool {
		return self::AGENT_NEO_THEME_SLUG === basename( $slug )
			|| str_ends_with( $slug, '/' . self::AGENT_NEO_THEME_SLUG );
	}

	// -------------------------------------------------------------------------
	// Data resolution helpers（AI ロジックなし）
	// -------------------------------------------------------------------------

	/**
	 * theme 基本情報を返す。
	 *
	 * @return array<string, mixed>
	 */
	private function theme_info(): array {
		$theme = wp_get_theme();
		return array(
			'stylesheet' => $theme->get_stylesheet(),
			'template'   => $theme->get_template(),
			'name'       => (string) $theme->get( 'Name' ),
			'version'    => (string) $theme->get( 'Version' ),
		);
	}

	/**
	 * Bridge profile 用 theme 情報（source / confidence 付き）を返す。
	 *
	 * @param bool $is_agent_neo AGENT NEO テーマか。
	 * @return array<string, mixed>
	 */
	private function theme_bridge_info( bool $is_agent_neo ): array {
		$theme = wp_get_theme();
		return array(
			'stylesheet' => $theme->get_stylesheet(),
			'template'   => $theme->get_template(),
			'name'       => (string) $theme->get( 'Name' ),
			'version'    => (string) $theme->get( 'Version' ),
			'source'     => 'wp_get_theme',
			'confidence' => $is_agent_neo ? 'high' : 'medium',
		);
	}

	/**
	 * テーマに応じた capability 一覧を返す。
	 *
	 * @param bool $is_agent_neo AGENT NEO テーマか。
	 * @return array<string, bool>
	 */
	private function resolve_capabilities( bool $is_agent_neo ): array {
		return array(
			'section_edit'      => $is_agent_neo,
			'cta_swap'          => $is_agent_neo,
			'seo_mapping'       => $is_agent_neo,
			'blueprint_apply'   => $is_agent_neo,
			'token_sync'        => $is_agent_neo,
			'preview_render'    => true, // プレビューは常に可能。
		);
	}

	/**
	 * セクション操作サポート情報（source / confidence 付き）を返す。
	 *
	 * AGENT NEO テーマは静的検出で high。非 AGENT NEO テーマは対応の根拠が薄いため low。
	 *
	 * @param bool $is_agent_neo AGENT NEO テーマか。
	 * @return array<string, mixed>
	 */
	private function resolve_section_support( bool $is_agent_neo ): array {
		return array(
			'supported'  => $is_agent_neo,
			'source'     => 'theme_detection',
			'confidence' => $is_agent_neo ? 'high' : 'low',
			'note'       => $is_agent_neo
				? 'AGENT NEO テーマはセクション操作に対応しています。'
				: 'アクティブテーマはセクション操作に対応していません。',
		);
	}

	/**
	 * CTA サポート情報（source / confidence 付き）を返す。
	 *
	 * AGENT NEO テーマは静的検出で high。非 AGENT NEO テーマは対応の根拠が薄いため low。
	 *
	 * @param bool $is_agent_neo AGENT NEO テーマか。
	 * @return array<string, mixed>
	 */
	private function resolve_cta_support( bool $is_agent_neo ): array {
		return array(
			'supported'  => $is_agent_neo,
			'source'     => 'theme_detection',
			'confidence' => $is_agent_neo ? 'high' : 'low',
			'note'       => $is_agent_neo
				? 'AGENT NEO テーマは CTA swap に対応しています。'
				: 'アクティブテーマは CTA swap に対応していません。',
		);
	}

	/**
	 * SEO mapping 情報（source / confidence 付き）を返す。
	 *
	 * AGENT NEO テーマは静的検出で high。非 AGENT NEO テーマは対応の根拠が薄いため low。
	 *
	 * @param bool $is_agent_neo AGENT NEO テーマか。
	 * @return array<string, mixed>
	 */
	private function resolve_seo_mapping( bool $is_agent_neo ): array {
		return array(
			'supported'  => $is_agent_neo,
			'source'     => 'theme_detection',
			'confidence' => $is_agent_neo ? 'high' : 'low',
			'note'       => $is_agent_neo
				? 'AGENT NEO テーマは SEO mapping に対応しています。'
				: 'アクティブテーマは SEO mapping に対応していません。',
		);
	}

	/**
	 * Blueprint サポート情報（source / confidence 付き）を返す。
	 *
	 * AGENT NEO テーマは静的検出で high。非 AGENT NEO テーマは対応の根拠が薄いため low。
	 *
	 * @param bool $is_agent_neo AGENT NEO テーマか。
	 * @return array<string, mixed>
	 */
	private function resolve_blueprint_support( bool $is_agent_neo ): array {
		return array(
			'supported'  => $is_agent_neo,
			'source'     => 'theme_detection',
			'confidence' => $is_agent_neo ? 'high' : 'low',
			'note'       => $is_agent_neo
				? 'AGENT NEO テーマは blueprint apply に対応しています。'
				: 'アクティブテーマは blueprint apply に対応していません。',
		);
	}

	/**
	 * 連携準備状況を返す。
	 *
	 * @param bool                 $is_agent_neo AGENT NEO テーマか。
	 * @param array<string, mixed> $integration  連携設定 option 値。
	 * @return array<string, mixed>
	 */
	private function resolve_readiness( bool $is_agent_neo, array $integration ): array {
		$automation_seo_url = isset( $integration['automation_seo_url'] ) && is_string( $integration['automation_seo_url'] )
			? $integration['automation_seo_url']
			: '';
		$site_hash          = isset( $integration['site_hash'] ) && is_string( $integration['site_hash'] )
			? $integration['site_hash']
			: '';
		$sync_enabled       = ! empty( $integration['sync_enabled'] );

		$url_configured  = '' !== $automation_seo_url;
		$hash_configured = '' !== $site_hash;

		$ready = $is_agent_neo && $url_configured && $hash_configured && $sync_enabled;

		return array(
			'ready'              => $ready,
			'is_agent_neo_theme' => $is_agent_neo,
			'url_configured'     => $url_configured,
			'hash_configured'    => $hash_configured,
			'sync_enabled'       => $sync_enabled,
		);
	}

	/**
	 * option から連携設定を読み込む。
	 *
	 * @return array<string, mixed>
	 */
	private function load_integration_option(): array {
		$opt = get_option( self::OPTION_INTEGRATION, array() );
		return is_array( $opt ) ? $opt : array();
	}

	/**
	 * POST body の連携設定パラメータを sanitize して返す。
	 *
	 * @param array<string, mixed> $params Raw params。
	 * @return array<string, mixed>
	 */
	private function sanitize_integration_params( array $params ): array {
		$result = array();

		if ( isset( $params['automation_seo_url'] ) && is_string( $params['automation_seo_url'] ) ) {
			$result['automation_seo_url'] = sanitize_url( $params['automation_seo_url'] );
		}

		if ( isset( $params['site_hash'] ) && is_string( $params['site_hash'] ) ) {
			$result['site_hash'] = sanitize_text_field( $params['site_hash'] );
		}

		if ( isset( $params['sync_enabled'] ) ) {
			$result['sync_enabled'] = (bool) $params['sync_enabled'];
		}

		return $result;
	}

	/**
	 * URL がプライベート / ループバック / リンクローカルアドレスを指していないかを検証する（SSRF ガード）。
	 *
	 * 拒否対象:
	 *   - ループバック : 127.0.0.0/8、::1
	 *   - プライベート : 10.0.0.0/8、172.16.0.0/12、192.168.0.0/16
	 *   - リンクローカル: 169.254.0.0/16、fe80::/10
	 *   - ホスト名   : localhost（大文字小文字問わず）
	 *
	 * @param string $url 検証対象 URL。
	 * @return true|WP_Error
	 */
	private function validate_url_not_internal( string $url ) {
		$parsed = wp_parse_url( $url );
		if ( ! is_array( $parsed ) || empty( $parsed['host'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'automation_seo_url is not a valid URL.', 'agent-neo-core' ),
				array( 'field' => 'automation_seo_url' )
			);
		}

		$host = strtolower( trim( $parsed['host'] ) );

		// ホスト名によるループバック拒否。
		if ( 'localhost' === $host ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'automation_seo_url must not point to an internal address.', 'agent-neo-core' ),
				array( 'field' => 'automation_seo_url' )
			);
		}

		// IP アドレスとして解決できる場合は CIDR 範囲チェックを行う。
		// IPv6 ブラケット記法（[::1]）を取り除く。
		$ip_candidate = ltrim( rtrim( $host, ']' ), '[' );
		if ( false !== filter_var( $ip_candidate, FILTER_VALIDATE_IP ) ) {
			$internal_ranges = array(
				// IPv4 ループバック。
				'127.0.0.0/8',
				// IPv4 プライベート。
				'10.0.0.0/8',
				'172.16.0.0/12',
				'192.168.0.0/16',
				// IPv4 リンクローカル。
				'169.254.0.0/16',
				// IPv6 ループバック（単一アドレスを /128 で表現）。
				'::1/128',
				// IPv6 リンクローカル。
				'fe80::/10',
			);

			foreach ( $internal_ranges as $cidr ) {
				if ( $this->ip_in_cidr( $ip_candidate, $cidr ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'automation_seo_url must not point to an internal address.', 'agent-neo-core' ),
						array( 'field' => 'automation_seo_url' )
					);
				}
			}
		}

		return true;
	}

	/**
	 * IP アドレスが CIDR 範囲内かを判定する（IPv4 / IPv6 両対応）。
	 *
	 * @param string $ip   対象 IP アドレス文字列。
	 * @param string $cidr CIDR 表記のネットワーク範囲。
	 * @return bool
	 */
	private function ip_in_cidr( string $ip, string $cidr ): bool {
		$parts = explode( '/', $cidr, 2 );
		if ( 2 !== count( $parts ) ) {
			return false;
		}

		$network = $parts[0];
		$prefix  = (int) $parts[1];

		$ip_bin      = inet_pton( $ip );
		$network_bin = inet_pton( $network );

		if ( false === $ip_bin || false === $network_bin ) {
			return false;
		}

		// IPv4 と IPv6 でアドレス長が異なるため不一致は比較不能。
		if ( strlen( $ip_bin ) !== strlen( $network_bin ) ) {
			return false;
		}

		$total_bits = strlen( $ip_bin ) * 8;
		if ( $prefix < 0 || $prefix > $total_bits ) {
			return false;
		}

		// プレフィックス長分のビットを比較する。
		$bytes    = intdiv( $prefix, 8 );
		$rem_bits = $prefix % 8;

		if ( $bytes > 0 && substr( $ip_bin, 0, $bytes ) !== substr( $network_bin, 0, $bytes ) ) {
			return false;
		}

		if ( 0 === $rem_bits ) {
			return true;
		}

		$mask = ( 0xff << ( 8 - $rem_bits ) ) & 0xff;
		return ( ord( $ip_bin[ $bytes ] ) & $mask ) === ( ord( $network_bin[ $bytes ] ) & $mask );
	}

	/**
	 * request_id を X-Request-Id ヘッダから取得、なければ生成する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return string
	 */
	private function resolve_request_id( WP_REST_Request $request ): string {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $request_id ) || '' === $request_id ) {
			$request_id = wp_generate_uuid4();
		}
		return $request_id;
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Automation_SEO_Controller(
			$container->auth(),
			$container->license_state()
		);
		$controller->register();
		$container->register_module( 'rest-automation-seo' );
	}
);

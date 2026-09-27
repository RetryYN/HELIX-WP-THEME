<?php
/**
 * GET /risks/hazards, GET /crawler-policy, POST /crawler-policy controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO / WP 運用 / AI 運用のリスク管理と crawler アクセスポリシーを提供する。
 *
 * - GET  /risks/hazards   : WP 内部状態の決定的ハザード検査（AI 不使用）
 * - GET  /crawler-policy  : crawler アクセスマトリクスと active preset の取得
 * - POST /crawler-policy  : crawler アクセスマトリクスの更新
 */
final class Agent_Neo_Core_Risks_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * crawler policy を保存する option キー。
	 */
	private const OPTION_KEY = 'agent_neo_core_crawler_policy';

	/**
	 * 有効な preset 値。
	 */
	private const VALID_PRESETS = array( 'permissive', 'balanced', 'restrictive' );

	/**
	 * 既知の競合プラグインスラッグ（slug => 競合カテゴリ）。
	 *
	 * @var array<string, string>
	 */
	private const CONFLICT_PLUGINS = array(
		// キャッシュ系
		'w3-total-cache/w3-total-cache.php'              => 'cache',
		'wp-super-cache/wp-cache.php'                    => 'cache',
		'wp-fastest-cache/wpFastestCache.php'            => 'cache',
		'litespeed-cache/litespeed-cache.php'            => 'cache',
		'sg-cachepress/sg-cachepress.php'                => 'cache',
		'wp-rocket/wp-rocket.php'                        => 'cache',
		'breeze/breeze.php'                              => 'cache',
		// SEO 系
		'wordpress-seo/wp-seo.php'                       => 'seo',
		'wordpress-seo-premium/wp-seo-premium.php'       => 'seo',
		'seo-by-rank-math/rank-math.php'                 => 'seo',
		'all-in-one-seo-pack/all_in_one_seo_pack.php'   => 'seo',
		'seopress/seopress.php'                          => 'seo',
	);

	/**
	 * preset ごとのデフォルト crawler マトリクス。
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private const PRESET_DEFAULTS = array(
		'permissive'  => array(
			'googlebot'     => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'bingbot'       => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'gptbot'        => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'claude-web'    => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'ccbot'         => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'anthropic-ai'  => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'perplexitybot' => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
		),
		'balanced'    => array(
			'googlebot'     => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'bingbot'       => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'gptbot'        => array( 'allowed' => false, 'noindex' => false, 'blocked' => true  ),
			'claude-web'    => array( 'allowed' => false, 'noindex' => false, 'blocked' => true  ),
			'ccbot'         => array( 'allowed' => false, 'noindex' => false, 'blocked' => true  ),
			'anthropic-ai'  => array( 'allowed' => false, 'noindex' => false, 'blocked' => true  ),
			'perplexitybot' => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
		),
		'restrictive' => array(
			'googlebot'     => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'bingbot'       => array( 'allowed' => true,  'noindex' => false, 'blocked' => false ),
			'gptbot'        => array( 'allowed' => false, 'noindex' => false, 'blocked' => true  ),
			'claude-web'    => array( 'allowed' => false, 'noindex' => false, 'blocked' => true  ),
			'ccbot'         => array( 'allowed' => false, 'noindex' => false, 'blocked' => true  ),
			'anthropic-ai'  => array( 'allowed' => false, 'noindex' => false, 'blocked' => true  ),
			'perplexitybot' => array( 'allowed' => false, 'noindex' => false, 'blocked' => true  ),
		),
	);

	/**
	 * Auth helper。
	 *
	 * @var Agent_Neo_Core_Auth
	 */
	private Agent_Neo_Core_Auth $auth;

	/**
	 * @param Agent_Neo_Core_Auth $auth Auth helper。
	 */
	public function __construct( Agent_Neo_Core_Auth $auth ) {
		$this->auth = $auth;
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
	 * 3 つの route を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /risks/hazards — ハザード一覧（read 権限）。
		$this->register_agent_route(
			'/risks/hazards',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_hazards' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// GET /crawler-policy — crawler ポリシー取得（read 権限）。
		$this->register_agent_route(
			'/crawler-policy',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_crawler_policy' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// POST /crawler-policy — crawler ポリシー更新（write 権限）。
		$this->register_agent_route(
			'/crawler-policy',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_crawler_policy' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * GET 系 endpoint の read 権限を確認する（ログイン + read capability）。
	 *
	 * @return true|WP_Error
	 */
	public function check_read_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO risks.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'read' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot read AGENT NEO risks.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * POST /crawler-policy の write 権限を確認する（nonce + manage_options）。
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
	 * GET /risks/hazards — WP 内部状態の決定的ハザード検査。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response
	 */
	public function get_hazards( WP_REST_Request $request ): WP_REST_Response {
		$request_id = $this->resolve_request_id( $request );

		// is_plugin_active() を使うために wp-admin ヘルパーを読み込む。
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$hazards = array_merge(
			$this->check_cron_hazards(),
			$this->check_seo_plugin_hazards(),
			$this->check_cache_plugin_hazards(),
			$this->check_plugin_conflict_hazards(),
			$this->check_canonical_hazards(),
			$this->check_ai_operation_hazards()
		);

		$data = array(
			'hazards'      => $hazards,
			'total'        => count( $hazards ),
			'detected'     => count( array_filter( $hazards, static fn( array $h ): bool => $h['detected'] ) ),
			'generated_at' => gmdate( 'c' ),
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * GET /crawler-policy — 保存済み crawler ポリシーを返す（未保存時は balanced 既定値）。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response
	 */
	public function get_crawler_policy( WP_REST_Request $request ): WP_REST_Response {
		$request_id = $this->resolve_request_id( $request );
		$stored     = $this->load_stored_policy();

		$data = array(
			'preset' => $stored['preset'],
			'matrix' => $stored['matrix'],
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * POST /crawler-policy — crawler ポリシーを option に保存する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_crawler_policy( WP_REST_Request $request ) {
		$request_id = $this->resolve_request_id( $request );

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		// preset の検証。
		$preset = null;
		if ( array_key_exists( 'preset', $params ) ) {
			if ( ! is_string( $params['preset'] ) || ! in_array( $params['preset'], self::VALID_PRESETS, true ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'preset must be one of: permissive, balanced, restrictive.', 'agent-neo-core' ),
					array( 'field' => 'preset', 'allowed' => self::VALID_PRESETS )
				);
			}
			$preset = $params['preset'];
		}

		// matrix の検証と sanitize。
		$matrix = null;
		if ( array_key_exists( 'matrix', $params ) ) {
			if ( ! is_array( $params['matrix'] ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'matrix must be an object.', 'agent-neo-core' ),
					array( 'field' => 'matrix' )
				);
			}
			$matrix_result = $this->sanitize_matrix( $params['matrix'] );
			if ( is_wp_error( $matrix_result ) ) {
				return $matrix_result;
			}
			$matrix = $matrix_result;
		}

		// preset のみ指定 → preset のデフォルト matrix で上書き。
		// matrix のみ指定 → 既存 preset を維持して matrix 更新。
		// 両方指定 → preset のデフォルト matrix を matrix で部分マージ。
		$stored = $this->load_stored_policy();

		if ( null !== $preset ) {
			// preset 適用: デフォルト matrix を起点にする。
			$base_matrix = self::PRESET_DEFAULTS[ $preset ];
			if ( null !== $matrix ) {
				// 明示 matrix エントリで上書きする（既存キーのみマージ）。
				foreach ( $matrix as $crawler => $entry ) {
					$base_matrix[ $crawler ] = $entry;
				}
			}
			$new_preset = $preset;
			$new_matrix = $base_matrix;
		} else {
			// preset なし → 既存 preset を維持して matrix のみ更新。
			$new_preset  = $stored['preset'];
			$new_matrix  = null !== $matrix ? array_merge( $stored['matrix'], $matrix ) : $stored['matrix'];
		}

		$save_value = array(
			'preset'     => $new_preset,
			'matrix'     => $new_matrix,
			'updated_at' => gmdate( 'c' ),
		);

		update_option( self::OPTION_KEY, $save_value, false );

		$data = array(
			'preset'     => $new_preset,
			'matrix'     => $new_matrix,
			'updated_at' => $save_value['updated_at'],
		);

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	// -------------------------------------------------------------------------
	// Hazard detectors（AI 不使用 / WP 内部状態の決定的検査）
	// -------------------------------------------------------------------------

	/**
	 * WP-Cron 無効化ハザードを検出する。
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function check_cron_hazards(): array {
		$hazards = array();

		// DISABLE_WP_CRON が true → cron が動かない。
		$cron_disabled = defined( 'DISABLE_WP_CRON' ) && true === DISABLE_WP_CRON;
		$hazards[]     = array(
			'id'       => 'cron_disabled',
			'category' => 'wp_operation',
			'severity' => 'warning',
			'detected' => $cron_disabled,
			'detail'   => $cron_disabled
				? __( 'DISABLE_WP_CRON is true. Scheduled tasks will not run unless an external cron is configured.', 'agent-neo-core' )
				: __( 'WP-Cron is enabled.', 'agent-neo-core' ),
		);

		// ALTERNATE_WP_CRON が true → 代替 cron モード（情報提供）。
		$alt_cron  = defined( 'ALTERNATE_WP_CRON' ) && true === ALTERNATE_WP_CRON;
		$hazards[] = array(
			'id'       => 'cron_alternate_mode',
			'category' => 'wp_operation',
			'severity' => 'info',
			'detected' => $alt_cron,
			'detail'   => $alt_cron
				? __( 'ALTERNATE_WP_CRON is true. WP-Cron runs via redirect-based fallback instead of HTTP request.', 'agent-neo-core' )
				: __( 'Alternate cron mode is not active.', 'agent-neo-core' ),
		);

		return $hazards;
	}

	/**
	 * SEO プラグイン競合ハザードを検出する（複数の SEO プラグインが同時有効）。
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function check_seo_plugin_hazards(): array {
		$seo_plugins = array(
			'wordpress-seo/wp-seo.php'                     => 'Yoast SEO',
			'wordpress-seo-premium/wp-seo-premium.php'     => 'Yoast SEO Premium',
			'seo-by-rank-math/rank-math.php'               => 'Rank Math SEO',
			'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
			'seopress/seopress.php'                        => 'SEOPress',
			'the-seo-framework/the-seo-framework.php'      => 'The SEO Framework',
		);

		$active_seo = array();
		foreach ( $seo_plugins as $slug => $name ) {
			if ( is_plugin_active( $slug ) ) {
				$active_seo[] = $name;
			}
		}

		$conflict  = count( $active_seo ) > 1;
		$detail    = $conflict
			? sprintf(
				/* translators: %s: カンマ区切りのプラグイン名一覧 */
				__( 'Multiple SEO plugins are active simultaneously: %s. This may cause duplicate meta tags and canonical conflicts.', 'agent-neo-core' ),
				implode( ', ', $active_seo )
			)
			: __( 'No SEO plugin conflict detected.', 'agent-neo-core' );

		return array(
			array(
				'id'       => 'seo_plugin_conflict',
				'category' => 'seo',
				'severity' => 'warning',
				'detected' => $conflict,
				'detail'   => $detail,
			),
		);
	}

	/**
	 * キャッシュプラグインによる REST nonce 問題のハザードを検出する。
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function check_cache_plugin_hazards(): array {
		$cache_plugins = array(
			'w3-total-cache/w3-total-cache.php'  => 'W3 Total Cache',
			'wp-super-cache/wp-cache.php'         => 'WP Super Cache',
			'wp-fastest-cache/wpFastestCache.php' => 'WP Fastest Cache',
			'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
			'sg-cachepress/sg-cachepress.php'     => 'SG Optimizer',
			'wp-rocket/wp-rocket.php'             => 'WP Rocket',
			'breeze/breeze.php'                   => 'Breeze',
		);

		$active_cache = array();
		foreach ( $cache_plugins as $slug => $name ) {
			if ( is_plugin_active( $slug ) ) {
				$active_cache[] = $name;
			}
		}

		$detected = ! empty( $active_cache );
		$detail   = $detected
			? sprintf(
				/* translators: %s: カンマ区切りのプラグイン名一覧 */
				__( 'Cache plugin(s) active: %s. Ensure REST API responses and nonces are excluded from caching to avoid authentication failures.', 'agent-neo-core' ),
				implode( ', ', $active_cache )
			)
			: __( 'No cache plugin detected.', 'agent-neo-core' );

		return array(
			array(
				'id'       => 'cache_rest_nonce_risk',
				'category' => 'wp_operation',
				'severity' => 'info',
				'detected' => $detected,
				'detail'   => $detail,
			),
		);
	}

	/**
	 * 既知の競合プラグインアクティブ状態を検出する。
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function check_plugin_conflict_hazards(): array {
		$active_conflicts = array();
		foreach ( self::CONFLICT_PLUGINS as $slug => $category ) {
			if ( is_plugin_active( $slug ) ) {
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $slug, false, false );
				$label       = isset( $plugin_data['Name'] ) && '' !== $plugin_data['Name']
					? $plugin_data['Name']
					: $slug;
				$active_conflicts[] = array(
					'slug'     => $slug,
					'label'    => $label,
					'category' => $category,
				);
			}
		}

		$detected = ! empty( $active_conflicts );
		$detail   = $detected
			? sprintf(
				/* translators: %d: 件数 */
				__( '%d known potentially conflicting plugin(s) are active. Review the list for possible interference.', 'agent-neo-core' ),
				count( $active_conflicts )
			)
			: __( 'No known conflicting plugins detected.', 'agent-neo-core' );

		return array(
			array(
				'id'       => 'plugin_conflict',
				'category' => 'wp_operation',
				'severity' => 'info',
				'detected' => $detected,
				'detail'   => $detail,
				'plugins'  => $active_conflicts,
			),
		);
	}

	/**
	 * canonical 重複・SEO プラグイン canonical 競合ハザードを検出する。
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function check_canonical_hazards(): array {
		$hazards = array();

		// SEO プラグインが canonical を出力する可能性がある場合の警告。
		$seo_with_canonical = array(
			'wordpress-seo/wp-seo.php'                     => 'Yoast SEO',
			'wordpress-seo-premium/wp-seo-premium.php'     => 'Yoast SEO Premium',
			'seo-by-rank-math/rank-math.php'               => 'Rank Math SEO',
			'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
			'seopress/seopress.php'                        => 'SEOPress',
		);

		$active_canonical_plugins = array();
		foreach ( $seo_with_canonical as $slug => $name ) {
			if ( is_plugin_active( $slug ) ) {
				$active_canonical_plugins[] = $name;
			}
		}

		$canonical_risk = ! empty( $active_canonical_plugins );
		$hazards[]      = array(
			'id'       => 'canonical_duplicate_risk',
			'category' => 'seo',
			'severity' => $canonical_risk ? 'warning' : 'info',
			'detected' => $canonical_risk,
			'detail'   => $canonical_risk
				? sprintf(
					/* translators: %s: カンマ区切りのプラグイン名一覧 */
					__( 'SEO plugin(s) that output canonical tags are active: %s. AGENT NEO canonical may conflict with plugin-generated canonical.', 'agent-neo-core' ),
					implode( ', ', $active_canonical_plugins )
				)
				: __( 'No canonical duplication risk detected from active SEO plugins.', 'agent-neo-core' ),
		);

		return $hazards;
	}

	/**
	 * AI 運用関連のハザードを検出する。
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function check_ai_operation_hazards(): array {
		$hazards = array();

		// robots.txt に AI クローラー制限が記述されているか option 経由で確認。
		$stored_policy = $this->load_stored_policy();
		$blocked_ai    = array();
		foreach ( $stored_policy['matrix'] as $crawler => $entry ) {
			if ( isset( $entry['blocked'] ) && true === $entry['blocked'] ) {
				$blocked_ai[] = $crawler;
			}
		}

		$ai_blocked = ! empty( $blocked_ai );
		$hazards[]  = array(
			'id'       => 'ai_crawler_blocked',
			'category' => 'ai_operation',
			'severity' => 'info',
			'detected' => $ai_blocked,
			'detail'   => $ai_blocked
				? sprintf(
					/* translators: %s: カンマ区切りのクローラー名一覧 */
					__( 'The following AI crawlers are set to blocked in the crawler policy: %s.', 'agent-neo-core' ),
					implode( ', ', $blocked_ai )
				)
				: __( 'No AI crawlers are explicitly blocked in the current crawler policy.', 'agent-neo-core' ),
		);

		// WP_DEBUG が本番環境で有効になっていないか確認。
		$debug_on  = defined( 'WP_DEBUG' ) && true === WP_DEBUG;
		$hazards[] = array(
			'id'       => 'wp_debug_enabled',
			'category' => 'ai_operation',
			'severity' => $debug_on ? 'warning' : 'info',
			'detected' => $debug_on,
			'detail'   => $debug_on
				? __( 'WP_DEBUG is enabled. Debug output may expose internal details in AI-generated responses. Disable on production.', 'agent-neo-core' )
				: __( 'WP_DEBUG is disabled.', 'agent-neo-core' ),
		);

		return $hazards;
	}

	// -------------------------------------------------------------------------
	// Crawler policy helpers
	// -------------------------------------------------------------------------

	/**
	 * 保存済み crawler policy を取得する。未保存時は balanced 既定値を返す。
	 *
	 * @return array{preset: string, matrix: array<string, array<string, mixed>>}
	 */
	private function load_stored_policy(): array {
		$stored = get_option( self::OPTION_KEY, null );

		if (
			is_array( $stored ) &&
			isset( $stored['preset'], $stored['matrix'] ) &&
			is_string( $stored['preset'] ) &&
			in_array( $stored['preset'], self::VALID_PRESETS, true ) &&
			is_array( $stored['matrix'] )
		) {
			return array(
				'preset' => $stored['preset'],
				'matrix' => $stored['matrix'],
			);
		}

		// 未保存または不正データ → balanced 既定値。
		return array(
			'preset' => 'balanced',
			'matrix' => self::PRESET_DEFAULTS['balanced'],
		);
	}

	/**
	 * matrix 入力値を検証・sanitize する。
	 *
	 * @param array<string, mixed> $raw_matrix 入力 matrix。
	 * @return array<string, array<string, mixed>>|WP_Error
	 */
	private function sanitize_matrix( array $raw_matrix ) {
		$sanitized = array();

		foreach ( $raw_matrix as $crawler => $entry ) {
			// crawler キーは文字列スラッグ形式を強制。
			$crawler = sanitize_key( (string) $crawler );
			if ( '' === $crawler ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'matrix crawler key must be a non-empty string slug.', 'agent-neo-core' ),
					array( 'field' => 'matrix' )
				);
			}

			if ( ! is_array( $entry ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'matrix entry must be an object.', 'agent-neo-core' ),
					array( 'field' => 'matrix.' . $crawler )
				);
			}

			// allowed / blocked / noindex はすべて boolean。
			foreach ( array( 'allowed', 'blocked', 'noindex' ) as $bool_field ) {
				if ( array_key_exists( $bool_field, $entry ) && ! is_bool( $entry[ $bool_field ] ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						/* translators: 1: フィールド名 2: crawler スラッグ */
						sprintf( __( 'matrix.%1$s.%2$s must be boolean.', 'agent-neo-core' ), $crawler, $bool_field ),
						array( 'field' => 'matrix.' . $crawler . '.' . $bool_field )
					);
				}
			}

			$sanitized[ $crawler ] = array(
				'allowed' => isset( $entry['allowed'] ) ? (bool) $entry['allowed'] : false,
				'blocked' => isset( $entry['blocked'] ) ? (bool) $entry['blocked'] : false,
				'noindex' => isset( $entry['noindex'] ) ? (bool) $entry['noindex'] : false,
			);
		}

		return $sanitized;
	}

	// -------------------------------------------------------------------------
	// Utilities
	// -------------------------------------------------------------------------

	/**
	 * X-Request-Id ヘッダーを取得するか、新規 UUID を生成する。
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
		$controller = new Agent_Neo_Core_Risks_Controller( $container->auth() );
		$controller->register();
		$container->register_module( 'rest-risks' );
	}
);

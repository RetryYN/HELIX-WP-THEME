<?php
/**
 * Core plugin kernel を起動する。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AGENT_NEO_CORE_DIR . 'inc/util/class-slug.php';
require_once AGENT_NEO_CORE_DIR . 'inc/util/class-ssrf-guard.php';
require_once AGENT_NEO_CORE_DIR . 'inc/schema/class-schema-loader.php';
require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php';
require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-rest-controller-base.php';
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-json-patch.php';
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-idempotency-store.php';
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-rollback-store.php';
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-audit-log.php';
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-dry-run-store.php';
require_once AGENT_NEO_CORE_DIR . 'inc/cpt/class-agent-action-cpt.php';
require_once AGENT_NEO_CORE_DIR . 'inc/cpt/class-ad-tag-cpt.php';
require_once AGENT_NEO_CORE_DIR . 'inc/license/class-license-state.php';
require_once AGENT_NEO_CORE_DIR . 'inc/lifecycle/class-lifecycle.php';
require_once AGENT_NEO_CORE_DIR . 'inc/catalog/class-catalog-update-producer.php';
require_once AGENT_NEO_CORE_DIR . 'inc/design/class-design-tokens-presenter.php';
require_once AGENT_NEO_CORE_DIR . 'inc/class-container.php';

$agent_neo_core_rest_controllers = glob( AGENT_NEO_CORE_DIR . 'inc/rest/*-controller.php' );
if ( is_array( $agent_neo_core_rest_controllers ) ) {
	sort( $agent_neo_core_rest_controllers, SORT_STRING );

	$agent_neo_core_legacy_rest_order = array(
		'class-status-controller.php'   => 0,
		'class-actions-controller.php'  => 1,
		'class-blocks-controller.php'   => 2,
		'class-sections-controller.php' => 3,
		'class-pages-controller.php'    => 4,
	);

	usort(
		$agent_neo_core_rest_controllers,
		static function ( string $left, string $right ) use ( $agent_neo_core_legacy_rest_order ): int {
			$left_order  = $agent_neo_core_legacy_rest_order[ basename( $left ) ] ?? 100;
			$right_order = $agent_neo_core_legacy_rest_order[ basename( $right ) ] ?? 100;

			if ( $left_order === $right_order ) {
				return strcmp( $left, $right );
			}

			return $left_order <=> $right_order;
		}
	);

	foreach ( $agent_neo_core_rest_controllers as $agent_neo_core_rest_controller ) {
		require_once $agent_neo_core_rest_controller;
	}
}
require_once AGENT_NEO_CORE_DIR . 'inc/mcp/class-abilities.php';
require_once AGENT_NEO_CORE_DIR . 'inc/payment-link.php';
require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-cta-instrumenter.php';
require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-tracking-assets.php';
require_once AGENT_NEO_CORE_DIR . 'inc/class-agent-neo-core.php';

// WP-CLI 操作面: REST contract と同一の JSON 封筒を rest_do_request() 経由で返す薄いアダプタ。
// WP_CLI 定数が定義されている場合のみ読み込む（通常 HTTP リクエストへの影響ゼロ）。
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once AGENT_NEO_CORE_DIR . 'inc/cli/class-cli-command.php';
}

// React UI 操作面: wp-admin 管理ページ。同一 REST 契約を apiFetch で消費する薄いアダプタ。
// is_admin() チェックは enqueue_assets フック内で行うためここでは常時 require。
require_once AGENT_NEO_CORE_DIR . 'inc/admin/class-admin-page.php';

add_action(
	'init',
	static function (): void {
		$admin_page = new Agent_Neo_Core_Admin_Page();
		$admin_page->register();
	}
);

// Gutenberg ブロック: blocks/*/block.json を glob して一括登録する。
// 複数ブロックへの拡張に対応できるよう glob ループで処理する。
add_action(
	'init',
	static function (): void {
		$block_dirs = glob( AGENT_NEO_CORE_DIR . 'blocks/*/block.json' );
		if ( ! is_array( $block_dirs ) ) {
			return;
		}

		foreach ( $block_dirs as $block_json ) {
			$block_dir = dirname( $block_json );
			register_block_type( $block_dir );
		}
	}
);

// フロント計測 JS (ad-tracking.js) の enqueue + token 供給 + consent gate 設定。
// class-tracking-assets.php が wp_localize_script で agentNeoTracking を注入する。
// site_token / hmac_key は class-tracking-controller.php の検証元（option）と一致させる。
// consent キーは consent.js の STORAGE_KEY（agent_neo_consent_v2）に合わせる。
$agent_neo_tracking_assets = new Agent_Neo_Core_Tracking_Assets();
$agent_neo_tracking_assets->register();

// CTA / バナー計装: render_block フィルタで data 属性を付与する。
// WP_HTML_Tag_Processor（WP6.2+）を使用。アフィリエイトリンク + banner 系 div が対象。
$agent_neo_cta_instrumenter = new Agent_Neo_Core_CTA_Instrumenter();
$agent_neo_cta_instrumenter->register();

register_activation_hook( AGENT_NEO_CORE_FILE, array( 'Agent_Neo_Core_Lifecycle', 'activate' ) );
register_deactivation_hook( AGENT_NEO_CORE_FILE, array( 'Agent_Neo_Core_Lifecycle', 'deactivate' ) );

global $agent_neo_core;
$agent_neo_core = new Agent_Neo_Core();
$agent_neo_core->register();

if ( ! function_exists( 'agent_neo_core_health' ) ) {
	/**
	 * Core plugin の health サマリを返す。
	 *
	 * @return array<string, mixed>
	 */
	function agent_neo_core_health(): array {
		global $agent_neo_core;

		if ( ! $agent_neo_core instanceof Agent_Neo_Core ) {
			return array(
				'loaded'         => false,
				'loaded_modules' => array(),
				'errors'         => array( 'core_instance_missing' ),
			);
		}

		return $agent_neo_core->health();
	}
}

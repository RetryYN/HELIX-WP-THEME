<?php
/**
 * React UI 操作面: wp-admin 管理ページ。
 *
 * 4操作面（REST / MCP / WP-CLI / React UI）は同一 JSON 契約に集約する（ADR-002/012）。
 * このクラスは wp-element + wp-api-fetch を使い、既存 REST エンドポイントを apiFetch で
 * 消費するだけのアダプタとして実装する（新しいロジック・契約は追加しない）。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AGENT NEO 管理ページ。
 */
final class Agent_Neo_Core_Admin_Page {

	/**
	 * 管理ページのスラッグ。
	 */
	private const PAGE_SLUG = 'agent-neo';

	/**
	 * enqueue するスクリプトのハンドル。
	 */
	private const SCRIPT_HANDLE = 'agent-neo-admin-app';

	/**
	 * enqueue するスタイルのハンドル。
	 */
	private const STYLE_HANDLE = 'agent-neo-admin-css';

	/**
	 * WordPress フックに登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * トップレベルメニューを登録する。
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__( 'AGENT NEO', 'agent-neo-core' ),       // ページタイトル
			__( 'AGENT NEO', 'agent-neo-core' ),       // メニューラベル
			'edit_posts',                               // 必要 capability
			self::PAGE_SLUG,                            // スラッグ
			array( $this, 'render_page' ),              // コールバック
			'data:image/svg+xml;base64,' . base64_encode( $this->menu_icon_svg() ), // dashicons 代替 SVG
			30                                          // 位置
		);
	}

	/**
	 * 管理ページのコンテナ HTML を出力する。
	 *
	 * React アプリがこの div にマウントされる。
	 *
	 * @return void
	 */
	public function render_page(): void {
		?>
		<div id="agent-neo-admin-root" style="min-height:200px;"></div>
		<?php
	}

	/**
	 * 対象ページのみ JS / CSS を enqueue する。
	 *
	 * @param string $hook_suffix 現在の管理ページフックサフィックス。
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// top-level メニューページのフックは "toplevel_page_{slug}" の形式。
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		// React UI アプリ本体。
		// wp-element / wp-api-fetch / wp-components は WordPress 本体が提供する。
		// ビルドツール不要: トランスパイルなし素の JS（JSX なし / h() 呼び出し形式）。
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			AGENT_NEO_CORE_URL . 'assets/admin/app.js',
			array( 'wp-element', 'wp-api-fetch', 'wp-components' ),
			AGENT_NEO_CORE_VERSION,
			true // footer に出力し、wp-element が先にロードされることを保証
		);

		// REST nonce を渡す。
		// apiFetch は X-WP-Nonce ヘッダーを自動付与するが、
		// createNonceMiddleware を明示セットアップするためにも nonce を渡す。
		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			sprintf(
				'window.agentNeoAdmin = { nonce: %s, restBase: %s };',
				wp_json_encode( wp_create_nonce( 'wp_rest' ) ),
				wp_json_encode( rest_url( 'agent-neo/v1' ) )
			),
			'before'
		);

		// 最小スタイル。
		wp_enqueue_style(
			self::STYLE_HANDLE,
			AGENT_NEO_CORE_URL . 'assets/admin/app.css',
			array( 'wp-components' ),
			AGENT_NEO_CORE_VERSION
		);
	}

	/**
	 * メニューアイコン SVG（オレンジ菱形）を返す。
	 *
	 * @return string SVG 文字列。
	 */
	private function menu_icon_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#ff6b00"><polygon points="10,1 19,10 10,19 1,10"/></svg>';
	}
}

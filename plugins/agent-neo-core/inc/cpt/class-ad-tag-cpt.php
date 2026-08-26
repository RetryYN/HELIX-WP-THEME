<?php
/**
 * ad_tag CPT 登録。
 *
 * CARRY-A2-002: ThemeB の inc/cpt/ad_tag/ を参考に広告タグ管理 CPT を登録する。
 * 5分岐（amazon / affiliate / ranking / normal / text）+ meta スキーマを定義する。
 * REQ-NF-025 厳守: AIロジック・モデル呼び出し・統計判定を一切含まない。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 広告タグ CPT を登録する。
 */
final class Agent_Neo_Core_Ad_Tag_CPT {

	/**
	 * CPT スラッグ。
	 */
	public const POST_TYPE = 'agent_neo_ad_tag';

	/**
	 * 許可される広告タイプ値。
	 *
	 * amazon       — Amazon アソシエイト
	 * affiliate    — 汎用アフィリエイトリンク
	 * ranking      — ランキング表示型
	 * normal       — バナー/インライン通常広告
	 * text         — テキスト広告
	 *
	 * @var string[]
	 */
	public const ALLOWED_TYPES = array(
		'amazon',
		'affiliate',
		'ranking',
		'normal',
		'text',
	);

	/**
	 * CPT と meta を登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * agent_neo_ad_tag CPT を登録する。
	 *
	 * 管理画面には表示する（show_ui=true）が、公開サイトには非公開（public=false）。
	 * REST API には expose せず、本プラグイン独自エンドポイント経由でのみアクセスする。
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Ad Tags', 'agent-neo-core' ),
					'singular_name'      => __( 'Ad Tag', 'agent-neo-core' ),
					'add_new_item'       => __( 'Add New Ad Tag', 'agent-neo-core' ),
					'edit_item'          => __( 'Edit Ad Tag', 'agent-neo-core' ),
					'view_item'          => __( 'View Ad Tag', 'agent-neo-core' ),
					'search_items'       => __( 'Search Ad Tags', 'agent-neo-core' ),
					'not_found'          => __( 'No ad tags found.', 'agent-neo-core' ),
					'not_found_in_trash' => __( 'No ad tags found in Trash.', 'agent-neo-core' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'has_archive'         => false,
				'supports'            => array( 'title', 'custom-fields' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * 広告タグ meta キーを登録する。
	 *
	 * @return void
	 */
	public function register_meta(): void {
		foreach ( $this->meta_schema() as $key => $args ) {
			register_post_meta( self::POST_TYPE, $key, $args );
		}
	}

	/**
	 * ad_tag meta スキーマ定義。
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function meta_schema(): array {
		$string_meta = array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => static function (): bool {
				return current_user_can( 'manage_options' );
			},
		);

		return array(
			// 広告タイプ（5分岐）。
			'_agent_neo_ad_type'       => $string_meta,
			// 広告タグ本文（HTML / スクリプト）。管理者のみ設定可。
			'_agent_neo_ad_code'       => array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => static function ( $value ): string {
					// 管理者設定のため許容タグを広めに許可する。
					// XSS 対策は权限チェック（manage_options）で担保する。
					return wp_kses_post( (string) $value );
				},
				'auth_callback'     => static function (): bool {
					return current_user_can( 'manage_options' );
				},
			),
			// Amazon ASIN（amazon タイプ専用）。
			'_agent_neo_ad_asin'       => $string_meta,
			// アフィリエイトリンク URL（affiliate / ranking タイプ）。
			'_agent_neo_ad_url'        => array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => static function (): bool {
					return current_user_can( 'manage_options' );
				},
			),
			// 有効/無効フラグ（'1' / '0'）。
			'_agent_neo_ad_enabled'    => $string_meta,
			// 対象ページタイプ（all / single / archive / category: comma 区切り）。
			'_agent_neo_ad_page_types' => $string_meta,
		);
	}
}

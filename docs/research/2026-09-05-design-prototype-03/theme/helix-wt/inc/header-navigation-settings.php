<?php
/** Site Editorから共通ヘッダーの参照だけを保存する。 */
defined( 'ABSPATH' ) || exit;
function helix_wt_header_navigation_state() {
	$choices = array();
	foreach ( get_posts(
		array(
			'post_type'      => 'wp_navigation',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	) as $post ) {
		$choices[] = array(
			'value' => $post->ID,
			'label' => $post->post_title,
		);
	}
	return array(
		'ref'     => wtcf_shared_navigation_ref(),
		'choices' => $choices,
	);
}
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'helix-wt/v1',
			'/header-navigation',
			array(
				'methods'             => 'GET,POST',
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' ); },
				'callback'            => function ( $request ) {
					if ( 'POST' === $request->get_method() ) {
						$ref = $request->get_param( 'ref' );
						$post = is_int( $ref ) && $ref > 0 ? get_post( $ref ) : null;
						if ( ! is_int( $ref ) || $ref < 0 || ( $ref && ( ! $post || 'wp_navigation' !== $post->post_type || 'publish' !== $post->post_status ) ) ) {
							return new WP_Error( 'helix_wt_invalid_navigation', __( '公開済みのナビゲーションを選択してください。', 'helix-wt' ), array( 'status' => 400 ) );
						}
						set_theme_mod( 'wt_content_navigation_ref', $ref );
					}
					return helix_wt_header_navigation_state();
				},
			)
		);
	}
);
add_action(
	'enqueue_block_editor_assets',
	function () {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return; }
		wp_enqueue_script( 'helix-wt-header-navigation-editor', get_theme_file_uri( 'assets/js/header-navigation-editor.js' ), array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-hooks', 'wp-compose', 'wp-i18n' ), filemtime( get_theme_file_path( 'assets/js/header-navigation-editor.js' ) ), true );
		$data           = helix_wt_header_navigation_state();
		$data['labels'] = array(
			'text0' => __( '共通ヘッダーナビ', 'helix-wt' ),
			'text1' => __( '全ヘッダー型で使うナビゲーションを選びます。本文やフッターのナビは変更しません。', 'helix-wt' ),
			'text2' => __( '参照するナビゲーション', 'helix-wt' ),
			'text3' => __( 'なし', 'helix-wt' ),
			'text4' => __( '共通ヘッダーナビを保存しました。', 'helix-wt' ),
			'text5' => __( '参照先を保存', 'helix-wt' ),
			'text6' => __( 'リンクの内容は標準ナビゲーション編集で変更できます。参照先の保存はサイト全体に反映されます。', 'helix-wt' ),
			'text7' => __( '共通ヘッダーナビは未選択です。共通ヘッダーナビ設定から参照先を選んでください。', 'helix-wt' ),
		);
		wp_localize_script( 'helix-wt-header-navigation-editor', 'helixWTNavigation', $data );
	}
);

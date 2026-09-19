<?php
/** Fixed-page patterns generated from the companion plugin's JSON declaration. */
defined( 'ABSPATH' ) || exit;
function wtcf_site_url( $key ) {
	$manifest = wtcf_site_manifest();
	$slug = $manifest['pages'][ $key ]['slug'] ?? '';
	$post = $slug ? get_page_by_path( $slug ) : null;
	return $post && $post->post_status === 'publish' ? get_permalink( $post ) : '';
}
function wtcf_site_handoff_url( $manifest ) {
	$origin = $manifest['handoff_origin'] ?? '';
	$path = $manifest['handoff_path'] ?? '';
	if ( ! is_string( $origin ) || ! is_string( $path ) || ! str_starts_with( $path, '/' ) ) { return ''; }
	$parts = wp_parse_url( $origin );
	if ( ! is_array( $parts ) || ! in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) || empty( $parts['host'] ) ) { return ''; }
	foreach ( array( 'user', 'pass', 'query', 'fragment' ) as $forbidden ) { if ( isset( $parts[ $forbidden ] ) ) { return ''; } }
	if ( isset( $parts['path'] ) && ! in_array( $parts['path'], array( '', '/' ), true ) ) { return ''; }
	return untrailingslashit( $origin ) . '/' . ltrim( $path, '/' );
}
function wtcf_render_site_page( $attributes ) {
	if ( is_singular() && post_password_required() ) { return get_the_password_form(); }
	if ( ! function_exists( 'wtcf_site_manifest' ) ) { return '<p>' . esc_html__( 'サイト情報を取得できません。', 'helix-wt' ) . '</p>'; }
	$manifest = wtcf_site_manifest(); $key = $attributes['pageKey'] ?? '';
	if ( ! is_string( $key ) || ! isset( $manifest['pages'][ $key ] ) ) { return '<p>' . esc_html__( 'このページ種別は登録されていません。', 'helix-wt' ) . '</p>'; }
	$page = $manifest['pages'][ $key ]; $settings = wtcf_site_settings(); $business = $settings['business'];
	ob_start(); echo wtcf_shared_part( 'header' ); ?>
	<div class="wtsite" data-site-page="<?php echo esc_attr( $key ); ?>">
	<a class="wtsite-skip" href="#site-main"><?php esc_html_e( '本文へ移動', 'helix-wt' ); ?></a>
	<?php if ( ! wtcf_shared_chrome() ) : ?><header class="wtsite-header"><a class="wtsite-brand" href="<?php echo esc_url( wtcf_site_url( 'company' ) ); ?>">HELIX<span>DESIGN & EDITORIAL</span></a><nav aria-label="<?php echo esc_attr__( '事業の案内', 'helix-wt' ); ?>"><?php foreach ( array( 'service', 'pricing', 'company', 'contact' ) as $nav ) { echo wtcf_link( wtcf_site_url( $nav ), $manifest['pages'][ $nav ]['label'] ); } ?></nav></header><?php endif; ?>
	<?php echo wtcf_shared_layout_start(); ?><main id="site-main" tabindex="-1"><header class="wtsite-intro"><p class="wtsite-eyebrow"><?php echo esc_html( $page['eyebrow'] ); ?></p><h1><?php echo esc_html( $page['title'] ); ?></h1><p class="wtsite-lead"><?php echo esc_html( $page['lead'] ); ?></p><p class="wtsite-updated"><?php echo esc_html( sprintf( __( '更新 %s', 'helix-wt' ), $manifest['updated'] ) ); ?></p></header>
	<div class="wtsite-layout"><aside><nav aria-label="<?php echo esc_attr__( 'このページの目次', 'helix-wt' ); ?>"><p>ON THIS PAGE</p><?php foreach ( $page['sections'] as $i => $section ) { echo wtcf_link( '#site-section-' . $i, $section[0] ); } ?></nav></aside><article>
	<?php if ( $page['fields'] ) : ?><dl class="wtsite-facts"><?php foreach ( $page['fields'] as $field ) { echo '<div><dt>' . esc_html( $manifest['field_labels'][ $field ] ) . '</dt><dd data-business-field="' . esc_attr( $field ) . '">' . esc_html( $business[ $field ] ) . '</dd></div>'; } ?></dl><?php endif; ?>
	<?php if ( isset( $page['plans'] ) ) : ?><div class="wtsite-plans"><?php foreach ( $page['plans'] as $plan ) : ?><section><h2><?php echo esc_html( $plan['name'] ); ?></h2><p class="wtsite-price"><?php echo esc_html( $plan['price'] ); ?></p><p><?php echo esc_html( $plan['unit'] ); ?></p><h3><?php esc_html_e( '含まれるもの', 'helix-wt' ); ?></h3><p><?php echo esc_html( $plan['scope'] ); ?></p><h3><?php esc_html_e( '含まれないもの', 'helix-wt' ); ?></h3><p><?php echo esc_html( $plan['limit'] ); ?></p></section><?php endforeach; ?></div><?php endif; ?>
	<?php if ( ! empty( $page['destinations'] ) ) : ?><div class="wtsite-destinations"><?php if ( ! $settings['destinations'] ) { echo '<p>' . esc_html__( '登録された外部送信先はありません。', 'helix-wt' ) . '</p>'; } ?><?php foreach ( $settings['destinations'] as $destination ) : ?><section><h2><?php echo esc_html( $destination['name'] ); ?></h2><dl><?php foreach ( array( 'purpose' => __( '目的', 'helix-wt' ), 'data' => __( '対象情報', 'helix-wt' ), 'condition' => __( '動作条件', 'helix-wt' ) ) as $field => $label ) { echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $destination[ $field ] ) . '</dd></div>'; } ?></dl></section><?php endforeach; ?></div><?php endif; ?>
	<?php foreach ( $page['sections'] as $i => $section ) : ?><section class="wtsite-section" id="site-section-<?php echo (int) $i; ?>" tabindex="-1"><h2><?php echo esc_html( $section[0] ); ?></h2><p><?php echo esc_html( $section[1] ); ?></p></section><?php endforeach; ?>
	<?php $handoff_url = wtcf_site_handoff_url( $manifest ); if ( ! empty( $page['handoff'] ) && '' !== $handoff_url ) : ?><div class="wtsite-handoff"><h2><?php esc_html_e( '別の受付画面へ進む', 'helix-wt' ); ?></h2><p><?php esc_html_e( '個人情報の入力は不要です。検証用の分類を選ぶ操作だけを試せます。', 'helix-wt' ); ?></p><?php echo wtcf_link( $handoff_url, __( '受付例を開く →', 'helix-wt' ), 'wtsite-button' ); ?></div><?php endif; ?>
	<nav class="wtsite-related" aria-label="<?php echo esc_attr__( '関連する案内', 'helix-wt' ); ?>"><?php foreach ( $page['links'] as $link ) { echo wtcf_link( wtcf_site_url( $link ), $manifest['pages'][ $link ]['label'] . ' →' ); } ?></nav>
	</article></div></main><?php echo wtcf_shared_layout_end(); ?>
	<?php if ( ! wtcf_shared_chrome() ) : ?><footer class="wtsite-footer"><p data-business-field="name"><?php echo esc_html( $business['name'] ); ?></p><nav aria-label="<?php echo esc_attr__( 'サイトの利用案内', 'helix-wt' ); ?>"><?php foreach ( array( 'privacy', 'commerce', 'transmissions', 'accessibility', 'terms' ) as $link ) { echo wtcf_link( wtcf_site_url( $link ), $manifest['pages'][ $link ]['label'] ); } ?></nav><p><?php esc_html_e( '表示・導線を確かめるための架空サイトです。', 'helix-wt' ); ?></p></footer><?php endif; ?></div><?php echo wtcf_shared_part( 'footer' ); ?>
	<?php return ob_get_clean();
}
add_action( 'init', function () {
	wp_register_script( 'helix-wt-site-page-editor', get_theme_file_uri( 'blocks/site-page/editor.js' ), array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data' ), filemtime( get_theme_file_path( 'blocks/site-page/editor.js' ) ), true );
	register_block_type( get_theme_file_path( 'blocks/site-page' ), array( 'render_callback' => 'wtcf_render_site_page' ) );
	if ( ! function_exists( 'wtcf_site_manifest' ) ) { return; }
	$options = array();
	foreach ( wtcf_site_manifest()['pages'] as $key => $page ) { $options[] = array( 'value' => $key, 'label' => $page['label'] ); }
	wp_add_inline_script( 'helix-wt-site-page-editor', 'window.helixSitePageEditor = ' . wp_json_encode( array( 'pages' => $options ) ) . ';', 'before' );
	register_block_pattern_category( 'helix-site-pages', array( 'label' => __( '常設案内・規約', 'helix-wt' ) ) );
	foreach ( wtcf_site_manifest()['pages'] as $key => $page ) { register_block_pattern( 'helix-wt/site-' . $key, array( 'title' => $page['label'], 'categories' => array( 'helix-site-pages' ), 'content' => '<!-- wp:helix-wt/site-page ' . wp_json_encode( array( 'pageKey' => $key ) ) . ' /-->' ) ); }
} );
add_action( 'wp_enqueue_scripts', function () {
	if ( is_page() && has_block( 'helix-wt/site-page', get_post() ) ) { wp_enqueue_style( 'wt-site-pages', get_theme_file_uri( 'assets/css/site-pages.css' ), array( 'helix-wt' ), '0.1.0' ); }
} );

add_action( 'enqueue_block_assets', function () {
	if ( is_admin() ) { wp_enqueue_style( 'wt-site-pages-editor-preview', get_theme_file_uri( 'assets/css/site-pages.css' ), array(), filemtime( get_theme_file_path( 'assets/css/site-pages.css' ) ) ); }
} );

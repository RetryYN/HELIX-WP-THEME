<?php
/**
 * WT Proto child theme (container-only). 親テーマ style.css を先に読み、子テーマの試作 CSS を後に読む。
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'agent-neo-parent-style', get_template_directory_uri() . '/style.css', array(), '0.1.0' );
	wp_enqueue_style( 'wt-proto-style', get_stylesheet_uri(), array( 'agent-neo-parent-style' ), '0.0.1' );
}, 20 );

add_action( 'init', function () {
	$styles = array(
		array( 'core/group', 'wt-box-info', '囲み: 情報' ),
		array( 'core/group', 'wt-box-warn', '囲み: 注意' ),
		array( 'core/group', 'wt-box-point', '囲み: ポイント' ),
		array( 'core/group', 'wt-link-card', 'リンクカード' ),
		array( 'core/group', 'wt-review', 'レビュー' ),
		array( 'core/group', 'wt-cta-bundle', 'CTA 束' ),
		array( 'core/group', 'wt-toc', '目次' ),
		array( 'core/button', 'wt-ghost', 'ゴースト' ),
	);
	foreach ( $styles as $s ) {
		register_block_style( $s[0], array( 'name' => $s[1], 'label' => $s[2] ) );
	}
} );

<?php
/**
 * Title: Hero セクション（最小パターン）
 * Slug: agent-neo-probe/hero
 * Categories: featured, banner
 * Description: ページ上部に配置するヒーローセクションの最小パターン。見出し・説明文・CTAボタンで構成。
 * Keywords: hero, banner, cta, トップ, ヒーロー
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: page, wp_template
 * Inserter: true
 */

// このファイルは WP のパターン登録用。直接アクセス禁止。
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"hero-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"backgroundColor":"primary","textColor":"background","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull hero-section has-primary-background-color has-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">

	<!-- wp:heading {"level":1,"textAlign":"center","fontSize":"xx-large"} -->
	<h1 class="wp-block-heading has-text-align-center has-xx-large-font-size">
		<?php esc_html_e( 'あなたのサイトタイトル', 'agent-neo-probe' ); ?>
	</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
	<p class="has-text-align-center has-large-font-size">
		<?php esc_html_e( 'キャッチフレーズをここに入力します。', 'agent-neo-probe' ); ?>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"accent","textColor":"background","fontSize":"medium"} -->
		<div class="wp-block-button">
			<a class="wp-block-button__link has-accent-background-color has-background-color has-text-color has-background has-medium-font-size wp-element-button" href="#">
				<?php esc_html_e( '詳しく見る', 'agent-neo-probe' ); ?>
			</a>
		</div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->

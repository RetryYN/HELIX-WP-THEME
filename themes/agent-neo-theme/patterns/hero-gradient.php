<?php
/**
 * Title: ヒーロー ② グラデーション（accent → primary）
 * Slug: agent-neo/hero-gradient
 * Categories: agent-neo, agent-neo-home
 * Description: 背景をグラデーションプリセット（accent-to-primary）にしたヒーロー。色はプリセットの差し替えで変わり、生値を持たない。左寄せの見出しと 2 ボタン。
 * Keywords: hero, gradient, mv
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"an-section an-section\u002d\u002dhero an-hero\u002d\u002dgradient","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"}},"textColor":"background","gradient":"accent-to-primary","layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group alignfull an-section an-section--hero an-hero--gradient has-background-color has-accent-to-primary-gradient-background has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"textColor":"background","fontSize":"small"} -->
<p class="has-background-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'AI-operated WordPress', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"style":{"typography":{"fontWeight":"800","lineHeight":"1.05"}},"textColor":"background","fontSize":"xxx-large"} -->
<h1 class="wp-block-heading has-background-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1.05"><?php esc_html_e( '書く・直す・配る。運用の全部を、AI が回す。', 'agent-neo' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"background","fontSize":"large"} -->
<p class="has-background-color has-text-color has-large-font-size"><?php esc_html_e( '人が決めるのは方針と最終判断だけ。記事生成から SEO・配信までを自動で継続します。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"background","textColor":"primary","style":{"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '導入をはじめる →', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"textColor":"background","className":"is-style-outline","style":{"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-background-color has-text-color wp-element-button" href="/lp/" style="font-weight:700"><?php esc_html_e( '機能を見る', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->

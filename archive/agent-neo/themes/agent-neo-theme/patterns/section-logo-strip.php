<?php
/**
 * Title: セクション ⑫ ロゴ帯
 * Slug: agent-neo/section-logo-strip
 * Categories: agent-neo
 * Description: 導入先や協力先の名前を横一列のワードマークとして見せるロゴ帯。実画像を使わず、静かな信頼補強を置きたいページ向け。
 * Keywords: section, logos, partners, trust, strip
 * Viewport Width: 1280
 * Block Types: core/group, core/columns
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"an-section an-section\u002d\u002dlogo-strip","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--logo-strip has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:paragraph {"align":"center","textColor":"muted","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-muted-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'さまざまなチームに選ばれています', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|20","top":"var:preset|spacing|20"}}}} -->
<div class="wp-block-columns alignwide">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"800"}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size" style="font-weight:800">PARTNER A</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"800"}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size" style="font-weight:800">PARTNER B</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"800"}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size" style="font-weight:800">PARTNER C</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"800"}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size" style="font-weight:800">PARTNER D</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"800"}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size" style="font-weight:800">PARTNER E</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

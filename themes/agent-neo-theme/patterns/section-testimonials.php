<?php
/**
 * Title: セクション ⑩ お客様の声
 * Slug: agent-neo/section-testimonials
 * Categories: agent-neo
 * Description: 3 つの引用カードを横並びにして、利用後の実感を伝えるお客様の声セクション。導入事例の前後に置く proof 向け。
 * Keywords: section, testimonials, quote, proof, reviews
 * Viewport Width: 1280
 * Block Types: core/group, core/columns, core/quote
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"an-section an-section\u002d\u002dtestimonials","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--testimonials has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'お客様の声', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontWeight":"800"}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size" style="font-weight:800"><?php esc_html_e( '使った人の言葉から知る', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"},"blockGap":{"left":"var:preset|spacing|30","top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:quote {"className":"an-testimonial-card","style":{"border":{"left":{"color":"var(--wp--preset--color--accent-aa)","width":"1px","style":"solid"}},"spacing":{"padding":{"left":"var:preset|spacing|30","top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"backgroundColor":"background"} -->
<blockquote class="wp-block-quote an-testimonial-card has-background-background-color has-background" style="border-left-color:var(--wp--preset--color--accent-aa);border-left-style:solid;border-left-width:1px;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--30)"><p><?php esc_html_e( 'やることの順番が見え、会議の時間が短くなりました。', 'agent-neo' ); ?></p><cite><?php esc_html_e( '企画担当 A', 'agent-neo' ); ?></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:quote {"className":"an-testimonial-card","style":{"border":{"left":{"color":"var(--wp--preset--color--accent-aa)","width":"1px","style":"solid"}},"spacing":{"padding":{"left":"var:preset|spacing|30","top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"backgroundColor":"background"} -->
<blockquote class="wp-block-quote an-testimonial-card has-background-background-color has-background" style="border-left-color:var(--wp--preset--color--accent-aa);border-left-style:solid;border-left-width:1px;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--30)"><p><?php esc_html_e( '文章の目的を揃えたことで、チームの判断が早くなりました。', 'agent-neo' ); ?></p><cite><?php esc_html_e( '編集担当 B', 'agent-neo' ); ?></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:quote {"className":"an-testimonial-card","style":{"border":{"left":{"color":"var(--wp--preset--color--accent-aa)","width":"1px","style":"solid"}},"spacing":{"padding":{"left":"var:preset|spacing|30","top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"backgroundColor":"background"} -->
<blockquote class="wp-block-quote an-testimonial-card has-background-background-color has-background" style="border-left-color:var(--wp--preset--color--accent-aa);border-left-style:solid;border-left-width:1px;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--30)"><p><?php esc_html_e( '迷ったときに戻れる基準ができ、更新を続けやすくなりました。', 'agent-neo' ); ?></p><cite><?php esc_html_e( '運用担当 C', 'agent-neo' ); ?></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<?php
/**
 * Title: ヒーロー ④ 左右分割
 * Slug: agent-neo/hero-split
 * Categories: agent-neo, agent-neo-home
 * Description: 左にメッセージと CTA、右にビジュアルを置く左右分割型ヒーロー。サービス紹介や導入ページの冒頭に向く。
 * Keywords: hero, split, image, cta, two-column
 * Viewport Width: 1280
 * Block Types: core/group, core/columns, core/image
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$an_cover = esc_url( get_theme_file_uri( 'assets/images/placeholder-cover.jpg' ) );
?>

<!-- wp:group {"align":"full","className":"an-section an-section\u002d\u002dhero an-hero\u002d\u002dsplit","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--hero an-hero--split has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60","top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center">
<!-- wp:column {"verticalAlignment":"center","width":"52%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:52%">
<!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '考え方から整える', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"style":{"typography":{"fontWeight":"800","lineHeight":"1.08"}},"fontSize":"xxx-large"} -->
<h1 class="wp-block-heading has-xxx-large-font-size" style="font-weight:800;line-height:1.08"><?php esc_html_e( '伝えたいことを、届く形へ。', 'agent-neo' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"large"} -->
<p class="has-muted-color has-text-color has-large-font-size"><?php esc_html_e( '情報を整理し、読み手の次の行動までをひとつの流れにします。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)">
<!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","style":{"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '相談してみる', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline","textColor":"accent-aa"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-accent-aa-color has-text-color wp-element-button" href="/about/"><?php esc_html_e( '考え方を読む', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"48%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:48%">
<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"an-hero-split-image"} -->
<figure class="wp-block-image size-large an-hero-split-image"><img src="<?php echo $an_cover; ?>" alt="<?php echo esc_attr__( '抽象的な風景を写したイメージ', 'agent-neo' ); ?>" /></figure>
<!-- /wp:image -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

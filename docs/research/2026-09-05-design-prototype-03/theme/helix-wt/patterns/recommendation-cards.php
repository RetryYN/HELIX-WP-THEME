<?php
/**
 * Title: 記事一覧比較：写真から次の記事を選ぶ
 * Slug: helix-wt/recommendation-cards
 * Categories: helix-wt
 * Description: 同じ新着3件を表示型だけ変えて比較する記事一覧
 */
?>
<!-- wp:group {"className":"wt-recommendation-cards","align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide wt-recommendation-cards"><!-- wp:group {"className":"wt-recommendation-intro"} -->
<div class="wp-block-group wt-recommendation-intro"><!-- wp:paragraph {"fontSize":"s"} -->
<p class="has-s-font-size"><?php esc_html_e( 'READ NEXT / 読みもの', 'helix-wt' ); ?></p>
<!-- /wp:paragraph --><!-- wp:heading {"level":2,"fontSize":"hero"} -->
<h2 class="wp-block-heading has-hero-font-size"><?php esc_html_e( '写真から次の記事を選ぶ', 'helix-wt' ); ?></h2>
<!-- /wp:heading --><!-- wp:paragraph -->
<p><?php esc_html_e( '暮らしと仕事を整える、小さなヒント。新しく公開した記事からご紹介します。', 'helix-wt' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --><!-- wp:query {"queryId":0,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false},"className":"wt-recommendation-query"} -->
<div class="wp-block-query wt-recommendation-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":2},"style":{"spacing":{"blockGap":"24px"}}} -->
<!-- wp:group {"className":"wt-recommendation-item","style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"16px","right":"16px"}},"border":{"radius":"16px","color":"#d9e1dc","width":"1px"}}} -->
<div class="wp-block-group wt-recommendation-item has-border-color" style="border-color:#d9e1dc;border-width:1px;border-radius:16px;padding-top:16px;padding-right:16px;padding-bottom:16px;padding-left:16px"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2","width":"100%","style":{"border":{"radius":"12px"}}} /--><!-- wp:group {"className":"wt-recommendation-copy","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"0px","right":"0px"}}}} -->
<div class="wp-block-group wt-recommendation-copy" style="padding-top:20px;padding-right:0px;padding-bottom:20px;padding-left:0px"><!-- wp:post-date {"format":"Y年n月j日","fontSize":"s","style":{"color":{"text":"#53645e"}}} /--><!-- wp:post-title {"level":3,"isLink":true,"fontSize":"xl"} /--><!-- wp:post-excerpt {"moreText":"","excerptLength":28,"fontSize":"s"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template --><!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p><?php esc_html_e( '記事は準備中です。公開までしばらくお待ちください。', 'helix-wt' ); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->

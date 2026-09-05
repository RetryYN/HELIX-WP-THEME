<?php
/**
 * Title: バナー画像 CTA
 * Slug: helix-wt/cta-banner
 * Categories: helix-wt
 * Description: banner-image 型。画像全面リンク + キャプション
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:image {"sizeSlug":"large","className":"is-style-wt-banner"} --><figure class="wp-block-image size-large is-style-wt-banner"><a href="/lp/"><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt="無料診断へのバナー: 3 分であなたに合うデスクを提案" width="1200" height="675"/></a><figcaption class="wp-element-caption">3 分の無料診断で、この記事の 3 製品から合うものを提案します。</figcaption></figure><!-- /wp:image -->

<?php
/**
 * Title: フッターのサイトマップ
 * Slug: helix-wt/footer-sitemap
 * Categories: helix-wt
 * Description: 各グループで保存済みメニューを選択。空グループは公開面で省略します。
 */
?>
<!-- wp:group {"tagName":"section","className":"wt-footer__layout wt-footer__layout--sitemap wt-footer-navigation-group"} -->
<section class="wp-block-group wt-footer__layout wt-footer__layout--sitemap wt-footer-navigation-group">
<!-- wp:group {"className":"wt-footer__sitemap","style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group wt-footer__sitemap">
<!-- wp:details {"showContent":true,"className":"wt-footer-navigation-group"} -->
<details class="wp-block-details wt-footer-navigation-group" open><summary><?php esc_html_e( '読む', 'helix-wt' ); ?></summary><!-- wp:navigation {"className": "wt-footer-data-navigation", "ariaLabel": "読む", "overlayMenu": "never"} /--></details>
<!-- /wp:details -->
<!-- wp:details {"showContent":true,"className":"wt-footer-navigation-group"} -->
<details class="wp-block-details wt-footer-navigation-group" open><summary><?php esc_html_e( '使う', 'helix-wt' ); ?></summary><!-- wp:navigation {"className": "wt-footer-data-navigation", "ariaLabel": "使う", "overlayMenu": "never"} /--></details>
<!-- /wp:details -->
<!-- wp:details {"showContent":true,"className":"wt-footer-navigation-group"} -->
<details class="wp-block-details wt-footer-navigation-group" open><summary><?php esc_html_e( '知る', 'helix-wt' ); ?></summary><!-- wp:navigation {"className": "wt-footer-data-navigation", "ariaLabel": "知る", "overlayMenu": "never"} /--></details>
<!-- /wp:details -->
<!-- wp:details {"showContent":true,"className":"wt-footer-navigation-group"} -->
<details class="wp-block-details wt-footer-navigation-group" open><summary><?php esc_html_e( '案内', 'helix-wt' ); ?></summary><!-- wp:navigation {"className": "wt-footer-data-navigation", "ariaLabel": "案内", "overlayMenu": "never"} /--></details>
<!-- /wp:details -->
</div><!-- /wp:group -->
</section><!-- /wp:group -->

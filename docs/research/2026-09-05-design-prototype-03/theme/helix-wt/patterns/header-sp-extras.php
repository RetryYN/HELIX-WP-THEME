<?php
/**
 * Title: ヘッダー SP 追加要素（CTA / テキストナビ）
 * Slug: helix-wt/header-sp-extras
 * Categories: helix-wt
 * Description: 2026-09-06 PO 反応 17 回目 WT-EVT-0270（Claude 案）。SP ヘッダー型 sp:cta（≡ + CTA、台帳 13%）と sp:text-nav（≡ なしテキストナビ、台帳 10%）の要素。
 *              全ヘッダー部品に含め、body.wt-sp-cta / body.wt-sp-text-nav の SP 幅でだけ表示する（CSS）。PC では常に非表示。
 */
?>
<!-- wp:html -->
<a class="wt-header__spcta" href="/lp/"><?php esc_html_e( '無料で診断', 'helix-wt' ); ?></a>
<nav class="wt-header__textnav" aria-label="<?php echo esc_attr__( '主要ナビ（SP）', 'helix-wt' ); ?>"><a href="/category/accounting/"><?php esc_html_e( '比較記事', 'helix-wt' ); ?></a><a href="#ranking"><?php esc_html_e( 'ランキング', 'helix-wt' ); ?></a><a href="#guide"><?php esc_html_e( '選び方', 'helix-wt' ); ?></a><a href="#glossary"><?php esc_html_e( '用語集', 'helix-wt' ); ?></a></nav>
<!-- /wp:html -->

<?php
/**
 * Title: HP（ホーム / トップページ・選択可能）
 * Slug: helix-wt/home
 * Categories: helix-wt
 * Description: 段 10b（WT-EVT-0292）で home-hero + home-sections の 2 パターンに分け、本パターンはその合成（front-page テンプレートは 2 つを個別に置く）。 2026-09-06 PO 反応 17 回目 WT-EVT-0277「HP ページは？」の Claude 案。hero 6 型・CTA 4 型・区間セット 3 種（企業 HP / サービス / メディア）・お知らせ 4 型・問い合わせ帯 5 型・固定導線 4 型。段8（WT-EVT-0287、台帳 home-event-recapture-v2）: hero +3（cards-carousel / product-shot / search-box）・CTA +1（search）・区間セット +2（店舗・スクール / 学校法人・団体）・問い合わせ帯 +1（double-cta）・固定ページ用パーツ（helix-wt-page/*）を区間として転用。
 *              既定は台帳 research-r17（HP 39 件）の最多型。文言・数値・社名は PoC 用の架空。フォームは送信しない（action="#" method="get"、type="button"）。
 */
?>
<!-- wp:pattern {"slug":"helix-wt/home-hero"} /-->
<!-- wp:pattern {"slug":"helix-wt/home-sections"} /-->

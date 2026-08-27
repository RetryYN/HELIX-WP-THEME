<?php
/**
 * Title: ヘッダー ⑥ 検索つき（ロゴ左・検索中央・ナビ右）
 * Slug: agent-neo/header-search
 * Categories: agent-neo, agent-neo-shared
 * Description: ロゴ・検索フォーム・ナビを 1 行に並べ、下段にカテゴリーの帯を置く。記事数の多いメディア・ドキュメント向け。
 * Keywords: header, search, categories, media
 * Viewport Width: 1280
 * Block Types: core/template-part/header
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-site-header-inner an-header--search","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-site-header-inner an-header--search">

	<!-- wp:group {"className":"an-header-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
	<div class="wp-block-group an-header-main" style="padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:site-title {"level":0,"isLink":true,"fontSize":"large","style":{"typography":{"fontWeight":"800"}},"textColor":"foreground"} /-->
		<!-- wp:search {"label":"検索","showLabel":false,"placeholder":"キーワードで探す","width":360,"widthUnit":"px","buttonText":"検索","buttonPosition":"button-inside","buttonUseIcon":true,"fontSize":"small","className":"an-header-search"} /-->
		<!-- wp:navigation {"ariaLabel":"グローバルナビゲーション","overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|30"},"typography":{"fontWeight":"600"}},"textColor":"foreground"} -->
			<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"運営者","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"an-header-catbar","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","justifyContent":"left","flexWrap":"wrap"}} -->
	<div class="wp-block-group an-header-catbar has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--10);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:navigation {"ariaLabel":"カテゴリー","overlayMenu":"never","layout":{"type":"flex","justifyContent":"left","flexWrap":"wrap"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"textColor":"muted"} -->
			<!-- wp:navigation-link {"label":"AI 運用","url":"/category/ai-ops/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"SEO","url":"/category/seo/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"導入事例","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"HELIX","url":"/category/helix/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"検証","url":"/category/verify/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->
	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->

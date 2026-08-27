<?php
/**
 * Title: ヘッダー ③ トップバー（連絡帯 + メイン行）
 * Slug: agent-neo/header-topbar
 * Categories: agent-neo, agent-neo-shared
 * Description: 上に濃色の細い帯（営業時間・連絡先・補助リンク）、下にロゴ・ナビ・CTA のメイン行を持つ 2 段ヘッダー。法人サイト向け。
 * Keywords: header, topbar, corporate
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

<!-- wp:group {"className":"an-site-header-inner an-header--topbar","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-site-header-inner an-header--topbar">

	<!-- wp:group {"className":"an-header-topbar","backgroundColor":"primary","textColor":"background","fontSize":"small","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group an-header-topbar has-background-color has-primary-background-color has-text-color has-background has-small-font-size" style="padding-top:var(--wp--preset--spacing--10);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:paragraph {"fontSize":"small"} -->
		<p class="has-small-font-size"><?php esc_html_e( '平日 10:00–18:00 受付 ｜ 初回相談は無料です', 'agent-neo' ); ?></p>
		<!-- /wp:paragraph -->
		<!-- wp:navigation {"ariaLabel":"補助ナビゲーション","overlayMenu":"never","layout":{"type":"flex","justifyContent":"right"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"textColor":"background"} -->
			<!-- wp:navigation-link {"label":"運営者情報","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"採用","url":"/recruit/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"an-header-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"bottom":{"color":"var(--wp--preset--color--secondary)","width":"1px","style":"solid"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group an-header-main" style="border-bottom:1px solid var(--wp--preset--color--secondary);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">

		<!-- wp:group {"className":"an-site-branding","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
		<div class="wp-block-group an-site-branding" style="gap:var(--wp--preset--spacing--20)">
			<!-- wp:site-logo {"width":40} /-->
			<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"0"}}} -->
			<div class="wp-block-group">
				<!-- wp:site-title {"level":0,"isLink":true,"fontSize":"large","style":{"typography":{"fontWeight":"700"}},"textColor":"foreground"} /-->
				<!-- wp:site-tagline {"fontSize":"small","textColor":"muted"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center","justifyContent":"right"},"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
		<div class="wp-block-group" style="gap:var(--wp--preset--spacing--40)">
			<!-- wp:navigation {"ariaLabel":"グローバルナビゲーション","overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|30"},"typography":{"fontWeight":"600"}},"textColor":"foreground"} -->
				<!-- wp:navigation-link {"label":"サービス","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"導入事例","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"会社情報","url":"/about/","kind":"custom","isTopLevelLink":true} /-->
			<!-- /wp:navigation -->
			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|10"}}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-outline an-header-cta-secondary","textColor":"accent-aa","fontSize":"small","style":{"typography":{"fontWeight":"700"}}} -->
				<div class="wp-block-button is-style-outline an-header-cta-secondary"><a class="wp-block-button__link has-accent-aa-color has-text-color has-small-font-size wp-element-button" href="/download/" style="font-weight:700"><?php esc_html_e( '資料請求', 'agent-neo' ); ?></a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"an-header-cta","backgroundColor":"accent-aa","textColor":"background","fontSize":"small","style":{"typography":{"fontWeight":"700"}}} -->
				<div class="wp-block-button an-header-cta"><a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background has-small-font-size wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '無料で相談', 'agent-neo' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->

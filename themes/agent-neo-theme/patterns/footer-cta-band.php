<?php
/**
 * Title: フッター ③ CTA 帯 + 濃色フッター
 * Slug: agent-neo/footer-cta-band
 * Categories: agent-neo, agent-neo-shared
 * Description: 本文とフッターの間にアクセント色の CTA 帯（見出し・一言・ボタン 2 つ）を挟み、その下に 2 列の濃色フッター。営業サイト向け。
 * Keywords: footer, cta, band, conversion
 * Viewport Width: 1280
 * Block Types: core/template-part/footer
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-site-footer-inner an-footer--cta-band","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-site-footer-inner an-footer--cta-band">

	<!-- wp:group {"className":"an-footer-cta-band","backgroundColor":"accent-aa","textColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group an-footer-cta-band has-background-color has-accent-aa-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:heading {"level":2,"textAlign":"center","fontSize":"xx-large","textColor":"background","style":{"typography":{"fontWeight":"800","lineHeight":"1.2"}}} -->
		<h2 class="wp-block-heading has-text-align-center has-background-color has-text-color has-xx-large-font-size" style="font-weight:800;line-height:1.2"><?php esc_html_e( '運用を、AI に任せてみませんか。', 'agent-neo' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"align":"center","fontSize":"medium","textColor":"background"} -->
		<p class="has-text-align-center has-background-color has-text-color has-medium-font-size"><?php esc_html_e( '初回相談は無料。現状のサイトを見ながら、どこから自動化できるかを一緒に決めます。', 'agent-neo' ); ?></p>
		<!-- /wp:paragraph -->
		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"background","textColor":"accent-aa","style":{"typography":{"fontWeight":"700"}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-accent-aa-color has-background-background-color has-text-color has-background wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '無料で相談する', 'agent-neo' ); ?></a></div>
			<!-- /wp:button -->
			<!-- wp:button {"className":"is-style-outline","textColor":"background","style":{"typography":{"fontWeight":"700"}}} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-background-color has-text-color wp-element-button" href="/download/" style="font-weight:700"><?php esc_html_e( '資料をダウンロード', 'agent-neo' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"backgroundColor":"footer-bg","textColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group has-background-color has-footer-bg-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">

		<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"width":"50%"} -->
			<div class="wp-block-column" style="flex-basis:50%">
				<!-- wp:site-title {"level":0,"isLink":true,"fontSize":"large","style":{"typography":{"fontWeight":"700"}},"textColor":"background"} /-->
				<!-- wp:site-tagline {"fontSize":"small","textColor":"muted"} /-->
			</div>
			<!-- /wp:column -->
			<!-- wp:column {"width":"50%"} -->
			<div class="wp-block-column" style="flex-basis:50%">
				<!-- wp:navigation {"ariaLabel":"フッターナビゲーション","overlayMenu":"never","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"textColor":"background"} -->
					<!-- wp:navigation-link {"label":"サービス","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->
					<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->
					<!-- wp:navigation-link {"label":"運営者情報","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->
					<!-- wp:navigation-link {"label":"プライバシーポリシー","url":"/privacy/","kind":"custom","isTopLevelLink":true} /-->
				<!-- /wp:navigation -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"},"style":{"border":{"top":{"color":"var(--wp--preset--color--muted)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}}} -->
		<div class="wp-block-group" style="border-top:1px solid var(--wp--preset--color--muted);padding-top:var(--wp--preset--spacing--30)">
			<!-- wp:pattern {"slug":"agent-neo/footer-credit"} /-->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->

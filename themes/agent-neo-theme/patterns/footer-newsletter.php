<?php
/**
 * Title: フッター ⑤ ニュースレター（登録帯 + 2 列 + 規約行）
 * Slug: agent-neo/footer-newsletter
 * Categories: agent-neo, agent-neo-shared
 * Description: グラデーション背景の登録帯（見出し + 検索ブロック流用の入力欄）を上に、下に濃色 2 列と規約行。メール獲得を重視するメディア向け。
 * Keywords: footer, newsletter, subscribe, gradient
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

<!-- wp:group {"className":"an-site-footer-inner an-footer--newsletter","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-site-footer-inner an-footer--newsletter">

	<!-- wp:group {"className":"an-footer-newsletter-band","gradient":"primary-to-footer","textColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group an-footer-newsletter-band has-background-color has-primary-to-footer-gradient-background has-text-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
		<div class="wp-block-columns alignwide are-vertically-aligned-center">
			<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
			<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">
				<!-- wp:heading {"level":2,"fontSize":"x-large","textColor":"background","style":{"typography":{"fontWeight":"800","lineHeight":"1.2"}}} --><h2 class="wp-block-heading has-background-color has-text-color has-x-large-font-size" style="font-weight:800;line-height:1.2"><?php esc_html_e( '週 1 通、運用の実験結果だけ送ります。', 'agent-neo' ); ?></h2><!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"small","textColor":"muted"} --><p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '宣伝なし。効かなかった施策も載せます。いつでも解除できます。', 'agent-neo' ); ?></p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
			<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
			<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
				<!-- wp:search {"label":"メールアドレス","showLabel":false,"placeholder":"you@example.com","buttonText":"登録する","buttonPosition":"button-inside","fontSize":"medium","className":"an-newsletter-input"} /-->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"backgroundColor":"footer-bg","textColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group has-background-color has-footer-bg-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
		<div class="wp-block-group alignwide">
			<!-- wp:site-title {"level":0,"isLink":true,"fontSize":"medium","style":{"typography":{"fontWeight":"700"}},"textColor":"background"} /-->
			<!-- wp:navigation {"ariaLabel":"フッターナビゲーション","overlayMenu":"never","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"textColor":"background"} -->
				<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"運営者情報","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"プライバシーポリシー","url":"/privacy/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
			<!-- /wp:navigation -->
		</div>
		<!-- /wp:group -->
		<!-- wp:group {"align":"wide","style":{"border":{"top":{"color":"var(--wp--preset--color--muted)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|20"}}},"layout":{"type":"flex","justifyContent":"space-between"}} -->
		<div class="wp-block-group alignwide" style="border-top:1px solid var(--wp--preset--color--muted);padding-top:var(--wp--preset--spacing--20)">
			<!-- wp:pattern {"slug":"agent-neo/footer-credit"} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->

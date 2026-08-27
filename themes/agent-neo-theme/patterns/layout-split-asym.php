<?php
/**
 * Title: レイアウト ② 非対称スプリット（画像 5 : 文 7、全幅・余白ずらし）
 * Slug: agent-neo/layout-split-asym
 * Categories: agent-neo
 * Description: 左 5 / 右 7 の非対称 2 列。左は画面端まで伸びる画像、右は幅を絞った本文。2 つ目のブロックで左右を反転して交互に使う。
 * Keywords: layout, split, asymmetric, columns, image
 * Viewport Width: 1280
 * Block Types: core/columns
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

<!-- wp:group {"align":"full","className":"an-section an-section--split","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull an-section an-section--split" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">

	<!-- wp:columns {"isStackedOnMobile":true,"align":"full","style":{"spacing":{"blockGap":{"left":"0","top":"0"}}}} -->
	<div class="wp-block-columns alignfull">
		<!-- wp:column {"width":"41.66%"} -->
		<div class="wp-block-column" style="flex-basis:41.66%">
			<!-- wp:cover {"url":"<?php echo $an_cover; ?>","dimRatio":0,"minHeight":60,"minHeightUnit":"vh","className":"an-split-image","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->
			<div class="wp-block-cover an-split-image" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;min-height:60vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $an_cover; ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"></div></div>
			<!-- /wp:cover -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"center","width":"58.33%","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|60","right":"var:preset|spacing|50"}}}} -->
		<div class="wp-block-column is-vertically-aligned-center has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60);flex-basis:58.33%">
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained","justifyContent":"left","contentSize":"520px"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"fontSize":"small","textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}}} --><p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase">01 — <?php esc_html_e( '設計', 'agent-neo' ); ?></p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"fontSize":"xx-large","style":{"typography":{"fontWeight":"800","lineHeight":"1.15"}}} --><h2 class="wp-block-heading has-xx-large-font-size" style="font-weight:800;line-height:1.15"><?php esc_html_e( '最初の 1 週間で、サイトの「型」を決める。', 'agent-neo' ); ?></h2><!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"medium"} --><p class="has-medium-font-size"><?php esc_html_e( '誰に何を届けるか、どの型の記事を何本持つか。ここが決まれば、あとは AI が量産と改善を引き受けます。', 'agent-neo' ); ?></p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"fontSize":"small","style":{"typography":{"fontWeight":"700"}}} --><p class="has-small-font-size" style="font-weight:700"><a href="/lp/"><?php esc_html_e( '設計プロセスを見る →', 'agent-neo' ); ?></a></p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:columns {"isStackedOnMobile":true,"align":"full","style":{"spacing":{"blockGap":{"left":"0","top":"0"}}}} -->
	<div class="wp-block-columns alignfull">
		<!-- wp:column {"verticalAlignment":"center","width":"58.33%","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|60"}}}} -->
		<div class="wp-block-column is-vertically-aligned-center" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50);flex-basis:58.33%">
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained","justifyContent":"right","contentSize":"520px"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"fontSize":"small","textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}}} --><p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase">02 — <?php esc_html_e( '運用', 'agent-neo' ); ?></p><!-- /wp:paragraph -->
				<!-- wp:heading {"level":2,"fontSize":"xx-large","style":{"typography":{"fontWeight":"800","lineHeight":"1.15"}}} --><h2 class="wp-block-heading has-xx-large-font-size" style="font-weight:800;line-height:1.15"><?php esc_html_e( '書いて、測って、書き直す。毎週。', 'agent-neo' ); ?></h2><!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"medium"} --><p class="has-medium-font-size"><?php esc_html_e( '順位・流入・CV を見て、伸びる記事に追記し、古い記事を畳む。人は月 1 回の確認だけ。', 'agent-neo' ); ?></p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"width":"41.66%"} -->
		<div class="wp-block-column" style="flex-basis:41.66%">
			<!-- wp:cover {"url":"<?php echo $an_cover; ?>","dimRatio":30,"overlayColor":"accent","isUserOverlayColor":true,"minHeight":60,"minHeightUnit":"vh","className":"an-split-image","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->
			<div class="wp-block-cover an-split-image" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;min-height:60vh"><span aria-hidden="true" class="wp-block-cover__background has-accent-background-color has-background-dim-30 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $an_cover; ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"></div></div>
			<!-- /wp:cover -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->

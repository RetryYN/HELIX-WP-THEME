<?php
/**
 * Title: レイアウト ③ 4 列フィーチャー（罫線区切り・番号つき）
 * Slug: agent-neo/layout-4col-features
 * Categories: agent-neo
 * Description: 幅広 4 列を縦罫線で区切り、番号・見出し・一文を縦に積む。カード背景を使わず、線と余白だけで列を成立させる。
 * Keywords: layout, columns, four, features, numbered
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
$an_items = array(
	array( '01', __( '要求から記事へ', 'agent-neo' ), __( 'キーワードと読者像を渡すと、構成と本文が揃う。', 'agent-neo' ) ),
	array( '02', __( '公開前の検査', 'agent-neo' ), __( '事実・表記・リンク切れ・重複を機械的に止める。', 'agent-neo' ) ),
	array( '03', __( '配信と計測', 'agent-neo' ), __( 'SNS・メールへ同時配信し、流入と CV を記事単位で追う。', 'agent-neo' ) ),
	array( '04', __( '改善ループ', 'agent-neo' ), __( '伸びる記事に追記、古い記事を統合。人は月 1 回の承認だけ。', 'agent-neo' ) ),
);
?>

<!-- wp:group {"align":"full","className":"an-section an-section--features4","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--features4" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">

	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"xx-large","style":{"typography":{"fontWeight":"800"}}} --><h2 class="wp-block-heading has-xx-large-font-size" style="font-weight:800"><?php esc_html_e( '運用の 4 工程', 'agent-neo' ); ?></h2><!-- /wp:heading -->
		<!-- wp:paragraph {"fontSize":"small","textColor":"muted"} --><p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'すべて AI が担当。人は方針と承認。', 'agent-neo' ); ?></p><!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"align":"wide","isStackedOnMobile":true,"className":"an-features4","style":{"spacing":{"blockGap":{"left":"0","top":"var:preset|spacing|30"}},"border":{"top":{"color":"var(--wp--preset--color--primary)","width":"1px","style":"solid"}}}} -->
	<div class="wp-block-columns alignwide an-features4" style="border-top-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dprimary);border-top-style:solid;border-top-width:1px">
	<?php foreach ( $an_items as $i => $it ) : ?>
		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}<?php echo $i > 0 ? ',"border":{"left":{"color":"var(--wp--preset--color--secondary)","width":"1px","style":"solid"}}' : ''; ?>}} -->
		<div class="wp-block-column" style="<?php echo $i > 0 ? 'border-left-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dsecondary);border-left-style:solid;border-left-width:1px;' : ''; ?>padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:paragraph {"fontSize":"xx-large","textColor":"accent-aa","style":{"typography":{"fontWeight":"800","lineHeight":"1"}}} --><p class="has-accent-aa-color has-text-color has-xx-large-font-size" style="font-weight:800;line-height:1"><?php echo esc_html( $it[0] ); ?></p><!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"fontSize":"large","style":{"typography":{"fontWeight":"700"}}} --><h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php echo esc_html( $it[1] ); ?></h3><!-- /wp:heading -->
			<!-- wp:paragraph {"fontSize":"small","textColor":"muted"} --><p class="has-muted-color has-text-color has-small-font-size"><?php echo esc_html( $it[2] ); ?></p><!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->

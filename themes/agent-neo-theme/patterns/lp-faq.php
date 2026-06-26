<?php
/**
 * Title: LP よくある質問（FAQ）
 * Slug: agent-neo/lp-faq
 * Categories: agent-neo
 * Description: LP FAQ セクション。Q をオレンジ太字、A を通常テキストで5問構成。secondary 背景帯。
 * Keywords: lp, faq, question, answer, support
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: page, wp_template
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"an-lp-faq","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-faq has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'よくある質問', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:var(--wp--preset--color--foreground)"><?php esc_html_e( '導入前によくいただくご質問をまとめました。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group" style="gap:0">

		<!-- Q1 -->
		<!-- wp:group {"style":{"border":{"top":{"color":"var(--wp--preset--color--background)","width":"1px"},"bottom":{"color":"var(--wp--preset--color--background)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"margin":{"bottom":"0"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group" style="border-top:1px solid var(--wp--preset--color--background);border-bottom:1px solid var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);margin-bottom:0">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Q. WordPress の知識がなくても使えますか？', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'A. はい、ご利用いただけます。専用プラグインのインストール後は、管理画面からの操作はほぼ不要です。コンテンツ生成から公開・分析まで、すべて AGENT NEO が自動で進めます。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- Q2 -->
		<!-- wp:group {"style":{"border":{"bottom":{"color":"var(--wp--preset--color--background)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"margin":{"bottom":"0"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group" style="border-bottom:1px solid var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);margin-bottom:0">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Q. 生成される記事の品質はどの程度ですか？', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'A. AI による品質チェックを経た後に公開されます。導入サイト平均の品質スコアは87点（独自基準）。公開前に担当者がレビュー・修正する承認フローも設定できます。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- Q3 -->
		<!-- wp:group {"style":{"border":{"bottom":{"color":"var(--wp--preset--color--background)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"margin":{"bottom":"0"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group" style="border-bottom:1px solid var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);margin-bottom:0">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Q. 既存の WordPress テーマや他プラグインと干渉しませんか？', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'A. AGENT NEO は REST API 経由でのみ WordPress と連携するため、テーマや他プラグインへの影響はほとんどありません。導入前にサイト診断を実施し、干渉リスクを事前に確認します。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- Q4 -->
		<!-- wp:group {"style":{"border":{"bottom":{"color":"var(--wp--preset--color--background)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"margin":{"bottom":"0"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group" style="border-bottom:1px solid var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);margin-bottom:0">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Q. 無料トライアル終了後、自動的に課金されますか？', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'A. 自動課金はされません。トライアル期間終了後は利用が停止され、ご自身でプランをお選びいただく形です。クレジットカードの登録はトライアル開始時点では不要です。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- Q5 -->
		<!-- wp:group {"style":{"border":{"bottom":{"color":"var(--wp--preset--color--background)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"margin":{"bottom":"0"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group" style="border-bottom:1px solid var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);margin-bottom:0">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Q. 解約はいつでもできますか？', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'A. 月次契約のため、いつでも解約いただけます。解約後は次の請求日をもって利用が終了します。データのエクスポートには30日間の猶予期間が設けられています。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->

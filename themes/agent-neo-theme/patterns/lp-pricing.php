<?php
/**
 * Title: LP 料金プラン
 * Slug: agent-neo/lp-pricing
 * Categories: agent-neo
 * Description: LP 料金プランセクション。3プランカード（推奨プランをオレンジ枠強調・価格大きく・特徴リスト・CTAボタン）。白背景帯。
 * Keywords: lp, pricing, plan, price, cost
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

<!-- wp:group {"align":"full","className":"an-lp-pricing","backgroundColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-pricing has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)">シンプルな料金プラン</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"#555555"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:#555555">すべてのプランで14日間の無料トライアルが利用できます。クレジットカード登録は不要です。</p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"verticalAlignment":"top","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns is-not-stacked-on-mobile are-vertically-aligned-top" style="gap:var(--wp--preset--spacing--30)">

		<!-- スタータープラン -->
		<!-- wp:column {"verticalAlignment":"top","style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#fafafa"}}} -->
		<div class="wp-block-column is-vertically-aligned-top" style="border-radius:8px;border:2px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#fafafa">

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10)">スターター</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
			<p style="font-size:var(--wp--preset--font-size--small);color:#888888;margin-bottom:var(--wp--preset--spacing--30)">小規模サイト・個人メディア向け</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p style="font-weight:800;font-size:2.5rem;line-height:1.0;color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10)">¥29,800<span style="font-size:1rem;font-weight:400">/月</span></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
			<p style="font-size:var(--wp--preset--font-size--small);color:#888888;margin-bottom:var(--wp--preset--spacing--40)">（税抜）</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="gap:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;AI 記事生成：月20本まで</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;1 WordPressサイト接続</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;基本 SEO 最適化</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;分析ダッシュボード</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#aaaaaa"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#aaaaaa">— &nbsp;マルチサイト管理</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#aaaaaa"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#aaaaaa">— &nbsp;専任サポート</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-outline","width":100,"style":{"border":{"radius":"6px","color":"var(--wp--preset--color--primary)","width":"2px"},"typography":{"fontWeight":"600"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem"}},"color":{"text":"var(--wp--preset--color--primary)"}}} -->
				<div class="wp-block-button is-style-outline has-custom-width wp-block-button__width-100">
					<a class="wp-block-button__link wp-element-button" href="#" style="border-radius:6px;border:2px solid var(--wp--preset--color--primary);font-weight:600;padding-top:0.875rem;padding-bottom:0.875rem;color:var(--wp--preset--color--primary)">無料で試す</a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

		</div>
		<!-- /wp:column -->

		<!-- プロプラン（推奨） -->
		<!-- wp:column {"verticalAlignment":"top","style":{"border":{"radius":"8px","color":"var(--wp--preset--color--accent)","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column is-vertically-aligned-top" style="border-radius:8px;border:2px solid var(--wp--preset--color--accent);padding:var(--wp--preset--spacing--40);background-color:#ffffff">

			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","margin":{"bottom":"var:preset|spacing|10"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
			<div class="wp-block-group" style="gap:var(--wp--preset--spacing--10);margin-bottom:var(--wp--preset--spacing--10);display:flex;align-items:center">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary)">プロ</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"border":{"radius":"4px"},"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#ffffff","background":"var(--wp--preset--color--accent)"},"spacing":{"padding":{"top":"0.2rem","bottom":"0.2rem","left":"0.6rem","right":"0.6rem"}}}} -->
				<p style="border-radius:4px;font-weight:700;font-size:var(--wp--preset--font-size--small);color:#ffffff;background-color:var(--wp--preset--color--accent);padding:0.2rem 0.6rem">おすすめ</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
			<p style="font-size:var(--wp--preset--font-size--small);color:#888888;margin-bottom:var(--wp--preset--spacing--30)">成長フェーズの企業・メディア向け</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p style="font-weight:800;font-size:2.5rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--10)">¥89,800<span style="font-size:1rem;font-weight:400;color:#333333">/月</span></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
			<p style="font-size:var(--wp--preset--font-size--small);color:#888888;margin-bottom:var(--wp--preset--spacing--40)">（税抜）</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="gap:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-weight:600;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;AI 記事生成：月100本まで</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-weight:600;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;5 WordPressサイト接続</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-weight:600;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;高度 SEO 最適化 + A/B テスト</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-weight:600;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;詳細分析ダッシュボード</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-weight:600;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;マルチサイト一元管理</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#aaaaaa"}}} -->
				<p style="font-weight:600;font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#aaaaaa">— &nbsp;専任サポート</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"backgroundColor":"accent","textColor":"background","width":100,"style":{"border":{"radius":"6px"},"typography":{"fontWeight":"700"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem"}}}} -->
				<div class="wp-block-button has-custom-width wp-block-button__width-100">
					<a class="wp-block-button__link has-accent-background-color has-background-color has-text-color has-background wp-element-button" href="#" style="border-radius:6px;font-weight:700;padding-top:0.875rem;padding-bottom:0.875rem">無料で試す →</a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

		</div>
		<!-- /wp:column -->

		<!-- エンタープライズ -->
		<!-- wp:column {"verticalAlignment":"top","style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#fafafa"}}} -->
		<div class="wp-block-column is-vertically-aligned-top" style="border-radius:8px;border:2px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#fafafa">

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10)">エンタープライズ</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
			<p style="font-size:var(--wp--preset--font-size--small);color:#888888;margin-bottom:var(--wp--preset--spacing--30)">大規模メディア・複数ブランド運営向け</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p style="font-weight:800;font-size:2rem;line-height:1.0;color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10)">要お問い合わせ</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
			<p style="font-size:var(--wp--preset--font-size--small);color:#888888;margin-bottom:var(--wp--preset--spacing--40)">規模・要件に応じた個別見積もり</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="gap:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;AI 記事生成：無制限</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;無制限サイト接続</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;カスタム SEO 戦略設定</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;BI 連携・カスタムレポート</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;マルチサイト一元管理</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.5"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.5;color:#333333">✓ &nbsp;専任カスタマーサクセス</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-outline","width":100,"style":{"border":{"radius":"6px","color":"var(--wp--preset--color--primary)","width":"2px"},"typography":{"fontWeight":"600"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem"}},"color":{"text":"var(--wp--preset--color--primary)"}}} -->
				<div class="wp-block-button is-style-outline has-custom-width wp-block-button__width-100">
					<a class="wp-block-button__link wp-element-button" href="#" style="border-radius:6px;border:2px solid var(--wp--preset--color--primary);font-weight:600;padding-top:0.875rem;padding-bottom:0.875rem;color:var(--wp--preset--color--primary)">お問い合わせ</a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->

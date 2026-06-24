<?php
/**
 * Title: シェアボタン
 * Slug: agent-neo/share-buttons
 * Categories: agent-neo
 * Description: 記事末尾に表示するSNSシェアボタン（X / Facebook / LINE / はてブ）。
 * Keywords: share, sns, twitter, facebook, line, hatena
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: wp_template, post
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// シェアボタンは単一投稿コンテキストでのみ動作する。
// ブロックパターンとして挿入された場合は WP のループコンテキストを使用する。
$share_url   = rawurlencode( (string) get_permalink() );
$share_title = rawurlencode( (string) get_the_title() );

$x_url       = 'https://twitter.com/intent/tweet?url=' . $share_url . '&text=' . $share_title;
$fb_url      = 'https://www.facebook.com/sharer/sharer.php?u=' . $share_url;
$line_url    = 'https://social-plugins.line.me/lineit/share?url=' . $share_url;
$hatena_url  = 'https://b.hatena.ne.jp/entry/s/' . ltrim( rawurldecode( $share_url ), 'https://' );
// はてブ は https:// のまま entry/s/ に続けるのが正規形式。
$hatena_url  = 'https://b.hatena.ne.jp/entry/s/' . preg_replace( '#^https?://#', '', (string) get_permalink() );
?>

<!-- wp:group {"className":"an-share-buttons","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"},"margin":{"top":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center","gap":"var:preset|spacing|20"}} -->
<div class="wp-block-group an-share-buttons" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);margin-top:var(--wp--preset--spacing--40)">

	<!-- wp:html -->
	<div class="an-share-buttons__inner" style="display:flex;flex-wrap:wrap;gap:0.75rem;justify-content:center;align-items:center">

		<span class="an-share-buttons__label" style="font-size:0.875rem;color:var(--wp--preset--color--muted,#6b6b6b);font-weight:600;margin-right:0.5rem">
			<?php esc_html_e( 'この記事をシェアする', 'agent-neo' ); ?>
		</span>

		<!-- X（旧 Twitter） -->
		<a href="<?php echo esc_url( $x_url ); ?>"
		   class="an-share-btn an-share-btn--x"
		   target="_blank"
		   rel="noopener noreferrer"
		   aria-label="<?php esc_attr_e( 'X（旧 Twitter）でシェアする', 'agent-neo' ); ?>"
		   style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;border-radius:4px;font-size:0.875rem;font-weight:600;text-decoration:none;background-color:#000000;color:#ffffff;line-height:1.4">
			<svg width="16" height="16" viewBox="0 0 1200 1227" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M714.163 519.284 1160.89 0h-105.86L667.137 450.887 357.328 0H0l468.492 681.821L0 1226.37h105.866l409.625-476.152 327.181 476.152H1200L714.137 519.284zM569.165 687.828l-47.468-67.894-377.686-540.24h162.604l304.797 435.991 47.468 67.894 396.2 566.721H892.476L569.165 687.854z" fill="currentColor"/></svg>
			<?php esc_html_e( 'X', 'agent-neo' ); ?>
		</a>

		<!-- Facebook -->
		<a href="<?php echo esc_url( $fb_url ); ?>"
		   class="an-share-btn an-share-btn--facebook"
		   target="_blank"
		   rel="noopener noreferrer"
		   aria-label="<?php esc_attr_e( 'Facebook でシェアする', 'agent-neo' ); ?>"
		   style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;border-radius:4px;font-size:0.875rem;font-weight:600;text-decoration:none;background-color:#1877f2;color:#ffffff;line-height:1.4">
			<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="currentColor"/></svg>
			<?php esc_html_e( 'Facebook', 'agent-neo' ); ?>
		</a>

		<!-- LINE -->
		<a href="<?php echo esc_url( $line_url ); ?>"
		   class="an-share-btn an-share-btn--line"
		   target="_blank"
		   rel="noopener noreferrer"
		   aria-label="<?php esc_attr_e( 'LINE でシェアする', 'agent-neo' ); ?>"
		   style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;border-radius:4px;font-size:0.875rem;font-weight:600;text-decoration:none;background-color:#06c755;color:#ffffff;line-height:1.4">
			<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125zM14.219 8.108c0-.345.281-.63.63-.63.345 0 .63.285.63.63v3.771c0 .2-.1.376-.25.488-.15.111-.349.149-.524.082L12.054 10.7v1.179c0 .344-.282.629-.63.629-.345 0-.63-.285-.63-.629V8.108c0-.2.1-.376.25-.488.15-.111.349-.149.524-.082l2.651 1.749zm-6.724 3.13c0 .344-.281.629-.63.629H4.53c-.349 0-.63-.285-.63-.629V8.108c0-.345.281-.63.63-.63.349 0 .63.285.63.63v3.13h1.705c.349 0 .63.285.63.63zm-3.96-3.13c0-.345.281-.63.63-.63.345 0 .63.285.63.63v3.771c0 .344-.285.629-.63.629-.349 0-.63-.285-.63-.629zM24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" fill="currentColor"/></svg>
			<?php esc_html_e( 'LINE', 'agent-neo' ); ?>
		</a>

		<!-- はてブ -->
		<a href="<?php echo esc_url( $hatena_url ); ?>"
		   class="an-share-btn an-share-btn--hatena"
		   target="_blank"
		   rel="noopener noreferrer"
		   aria-label="<?php esc_attr_e( 'はてなブックマークに追加する', 'agent-neo' ); ?>"
		   style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;border-radius:4px;font-size:0.875rem;font-weight:600;text-decoration:none;background-color:#00a4de;color:#ffffff;line-height:1.4">
			<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M20.47 0C22.42 0 24 1.58 24 3.53v16.94C24 22.42 22.42 24 20.47 24H3.53C1.58 24 0 22.42 0 20.47V3.53C0 1.58 1.58 0 3.53 0zM8.873 16.478H6.17V7.522h2.702zm7.302.181c-1.503 0-2.747-.49-3.578-1.273-.818-.77-1.255-1.882-1.255-3.208 0-1.337.437-2.449 1.255-3.22.831-.783 2.075-1.272 3.578-1.272 1.49 0 2.735.49 3.565 1.272.82.77 1.257 1.883 1.257 3.22 0 1.326-.437 2.437-1.257 3.208-.83.783-2.074 1.273-3.565 1.273zm0-7.197c-.73 0-1.332.24-1.773.649-.44.41-.68 1.015-.68 1.867 0 .857.24 1.46.68 1.869.44.41 1.043.648 1.773.648.717 0 1.32-.239 1.76-.648.44-.41.681-1.012.681-1.869 0-.852-.24-1.458-.68-1.867-.44-.41-1.044-.649-1.76-.649zM7.521 6.262c.862 0 1.56.697 1.56 1.56 0 .862-.698 1.56-1.56 1.56-.862 0-1.56-.698-1.56-1.56 0-.863.698-1.56 1.56-1.56z" fill="currentColor"/></svg>
			<?php esc_html_e( 'はてブ', 'agent-neo' ); ?>
		</a>

	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->

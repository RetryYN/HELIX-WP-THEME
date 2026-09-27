<?php
/**
 * Title: Footer Credit
 * Slug: agent-neo/footer-credit
 * Categories: agent-neo-home
 * Description: フッターのコピーライト表記（年を自動更新）。
 * Keywords: footer, copyright, credit
 * Viewport Width: 1280
 * Block Types: core/paragraph
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:paragraph {"className":"an-footer-copyright","style":{"typography":{"fontSize":"0.8125rem"},"color":{"text":"#adadad"}}} -->
<p class="an-footer-copyright" style="font-size:0.8125rem;color:#adadad"><?php
	printf( esc_html__( '© %s AGENT NEO. All rights reserved.', 'agent-neo' ), esc_html( date_i18n( 'Y' ) ) );
?></p>
<!-- /wp:paragraph -->

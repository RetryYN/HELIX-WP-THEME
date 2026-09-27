<?php
/**
 * Dynamic render for agent-neo/embed.
 *
 * @package AgentNeoEmbed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$agent_neo_mode        = isset( $attributes['mode'] ) && 'interactive' === $attributes['mode'] ? 'interactive' : 'static';
$agent_neo_title       = isset( $attributes['title'] ) ? sanitize_text_field( (string) $attributes['title'] ) : __( 'AGENT NEO embed', 'agent-neo-embed' );
$agent_neo_payload_id  = isset( $attributes['payloadId'] ) ? sanitize_text_field( (string) $attributes['payloadId'] ) : '';
$agent_neo_reset_url   = defined( 'AGENT_NEO_EMBED_URL' ) ? AGENT_NEO_EMBED_URL . 'assets/embed-reset.css' : '';
$agent_neo_base_attrs  = array(
	'class'                => 'agent-neo-embed',
	'data-agent-neo-embed' => 'true',
	'data-mode'            => $agent_neo_mode,
	'data-payload-id'      => $agent_neo_payload_id,
);
$agent_neo_wrapper_attr = get_block_wrapper_attributes( $agent_neo_base_attrs );

if ( 'interactive' === $agent_neo_mode ) {
	$agent_neo_embed_url       = isset( $attributes['embedUrl'] ) ? esc_url_raw( (string) $attributes['embedUrl'], array( 'http', 'https' ) ) : '';
	$agent_neo_embed_origin    = function_exists( 'agent_neo_embed_sandbox_origin_from_url' ) ? agent_neo_embed_sandbox_origin_from_url( $agent_neo_embed_url ) : '';
	$agent_neo_allowed_origins = function_exists( 'agent_neo_embed_allowed_sandbox_origins' ) ? agent_neo_embed_allowed_sandbox_origins() : array();

	if ( '' === $agent_neo_embed_url || '' === $agent_neo_embed_origin || ! in_array( $agent_neo_embed_origin, $agent_neo_allowed_origins, true ) ) {
		echo sprintf(
			'<div %1$s><div class="agent-neo-embed__blocked" role="note"><p>%2$s</p></div></div>',
			$agent_neo_wrapper_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() はWPコアが生成する既エスケープ済み属性文字列。
			esc_html__( 'Interactive embed blocked: sandbox origin is not configured or not allowed.', 'agent-neo-embed' )
		);
		return;
	}

	echo sprintf(
		'<div %1$s><iframe class="agent-neo-embed__frame" sandbox="allow-scripts" src="%2$s" title="%3$s" loading="lazy" referrerpolicy="no-referrer" data-agent-neo-iframe="true" data-agent-neo-nonce="%4$s" height="160"></iframe></div>',
		$agent_neo_wrapper_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() はWPコアが生成する既エスケープ済み属性文字列。
		esc_url( $agent_neo_embed_url, array( 'http', 'https' ) ),
		esc_attr( '' !== $agent_neo_title ? $agent_neo_title : __( 'AGENT NEO embed', 'agent-neo-embed' ) ),
		esc_attr( $agent_neo_payload_id )
	);
	return;
}

$agent_neo_raw_static_html = isset( $attributes['staticHtml'] ) ? (string) $attributes['staticHtml'] : '';
$agent_neo_static_html     = function_exists( 'agent_neo_embed_sanitize_static_html' ) ? agent_neo_embed_sanitize_static_html( $agent_neo_raw_static_html ) : wp_kses_post( $agent_neo_raw_static_html );

echo sprintf(
	'<div %1$s><template shadowrootmode="open"><link rel="stylesheet" href="%2$s">%3$s</template></div>',
	$agent_neo_wrapper_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() はWPコアが生成する既エスケープ済み属性文字列。
	esc_url( $agent_neo_reset_url ),
	$agent_neo_static_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- agent_neo_embed_sanitize_static_html() または wp_kses_post() でサニタイズ済み。
);

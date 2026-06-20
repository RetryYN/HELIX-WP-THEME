<?php
/**
 * Dynamic render for agent-neo/embed.
 *
 * @package AgentNeoEmbed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mode        = isset( $attributes['mode'] ) && 'interactive' === $attributes['mode'] ? 'interactive' : 'static';
$title       = isset( $attributes['title'] ) ? sanitize_text_field( (string) $attributes['title'] ) : __( 'AGENT NEO embed', 'agent-neo-embed' );
$payload_id  = isset( $attributes['payloadId'] ) ? sanitize_text_field( (string) $attributes['payloadId'] ) : '';
$reset_url   = defined( 'AGENT_NEO_EMBED_URL' ) ? AGENT_NEO_EMBED_URL . 'assets/embed-reset.css' : '';
$base_attrs  = array(
	'class'                => 'agent-neo-embed',
	'data-agent-neo-embed' => 'true',
	'data-mode'            => $mode,
	'data-payload-id'      => $payload_id,
);
$wrapper_attr = get_block_wrapper_attributes( $base_attrs );

if ( 'interactive' === $mode ) {
	$embed_url       = isset( $attributes['embedUrl'] ) ? esc_url_raw( (string) $attributes['embedUrl'], array( 'http', 'https' ) ) : '';
	$embed_origin    = function_exists( 'agent_neo_embed_sandbox_origin_from_url' ) ? agent_neo_embed_sandbox_origin_from_url( $embed_url ) : '';
	$allowed_origins = function_exists( 'agent_neo_embed_allowed_sandbox_origins' ) ? agent_neo_embed_allowed_sandbox_origins() : array();

	if ( '' === $embed_url || '' === $embed_origin || ! in_array( $embed_origin, $allowed_origins, true ) ) {
		echo sprintf(
			'<div %1$s><div class="agent-neo-embed__blocked" role="note"><p>%2$s</p></div></div>',
			$wrapper_attr,
			esc_html__( 'Interactive embed blocked: sandbox origin is not configured or not allowed.', 'agent-neo-embed' )
		);
		return;
	}

	echo sprintf(
		'<div %1$s><iframe class="agent-neo-embed__frame" sandbox="allow-scripts" src="%2$s" title="%3$s" loading="lazy" referrerpolicy="no-referrer" data-agent-neo-iframe="true" data-agent-neo-nonce="%4$s" height="160"></iframe></div>',
		$wrapper_attr,
		esc_url( $embed_url, array( 'http', 'https' ) ),
		esc_attr( '' !== $title ? $title : __( 'AGENT NEO embed', 'agent-neo-embed' ) ),
		esc_attr( $payload_id )
	);
	return;
}

$raw_static_html = isset( $attributes['staticHtml'] ) ? (string) $attributes['staticHtml'] : '';
$static_html     = function_exists( 'agent_neo_embed_sanitize_static_html' ) ? agent_neo_embed_sanitize_static_html( $raw_static_html ) : wp_kses_post( $raw_static_html );

echo sprintf(
	'<div %1$s><template shadowrootmode="open"><link rel="stylesheet" href="%2$s">%3$s</template></div>',
	$wrapper_attr,
	esc_url( $reset_url ),
	$static_html
);

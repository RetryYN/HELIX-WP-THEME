<?php
/**
 * Plugin Name: WT PoC: core Tabs -> SP accordion (CSS only)
 * Description: PoC mu-plugin. Injects CSS (no JS) so core/tabs renders accordion-like at <=480px: tab buttons and panels are interleaved with display:contents + order, only the active panel stays visible (core Interactivity API keeps aria-selected / hidden in sync).
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', static function () {
	$css = <<<'CSS'
@media (max-width: 480px) {
	.wp-block-tabs.is-style-sp-accordion,
	.wp-block-tabs[data-wt-sp-accordion] {
		display: flex;
		flex-direction: column;
		gap: 0;
	}
	/* Flatten the two wrappers so buttons and panels become siblings inside .wp-block-tabs. */
	.wp-block-tabs.is-style-sp-accordion > .wp-block-tab-list,
	.wp-block-tabs.is-style-sp-accordion > .wp-block-tab-panels {
		display: contents;
	}
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-list > button {
		display: block;
		width: 100%;
		text-align: left;
		border: 1px solid currentColor;
		border-radius: 0;
		margin: 0;
		padding: 0.75em 1em;
		background: transparent;
	}
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-list > button::after {
		content: "\25BC";
		float: right;
		font-size: 0.7em;
		line-height: 1.8;
	}
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-list > button[aria-selected="true"]::after {
		content: "\25B2";
	}
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-panels > .wp-block-tab-panel {
		border: 1px solid currentColor;
		border-top: 0;
		padding: 1em;
		margin: 0;
	}
	/* Interleave: button n gets order 2n-1, panel n gets order 2n (supports up to 6 tabs in this PoC). */
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-list > button:nth-child(1) { order: 1; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-panels > .wp-block-tab-panel:nth-child(1) { order: 2; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-list > button:nth-child(2) { order: 3; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-panels > .wp-block-tab-panel:nth-child(2) { order: 4; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-list > button:nth-child(3) { order: 5; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-panels > .wp-block-tab-panel:nth-child(3) { order: 6; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-list > button:nth-child(4) { order: 7; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-panels > .wp-block-tab-panel:nth-child(4) { order: 8; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-list > button:nth-child(5) { order: 9; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-panels > .wp-block-tab-panel:nth-child(5) { order: 10; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-list > button:nth-child(6) { order: 11; }
	.wp-block-tabs.is-style-sp-accordion .wp-block-tab-panels > .wp-block-tab-panel:nth-child(6) { order: 12; }
}
CSS;
	// Attach to the core tabs block style handle so it loads whenever the block is on the page.
	wp_add_inline_style( 'wp-block-tabs', $css );
} );

// Register the block style so the editor offers it (the PoC post uses the class directly).
add_action( 'init', static function () {
	register_block_style( 'core/tabs', array( 'name' => 'sp-accordion', 'label' => 'SP accordion' ) );
} );

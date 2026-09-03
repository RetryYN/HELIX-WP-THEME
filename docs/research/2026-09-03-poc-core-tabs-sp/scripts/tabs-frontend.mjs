// Front-end checks for core/tabs + CSS-only SP accordion: screenshots at 390/1280, ARIA, keyboard, layout order.
import { createRequire } from 'node:module';
import fs from 'node:fs';
const { S, URL, OUT, SHOTS } = process.env;
const { chromium } = createRequire( `${ S }/package.json` )( 'playwright' );
const browser = await chromium.launch();
const results = {};
const inspect = () => {
	const tabs = document.querySelector( '.wp-block-tabs' );
	const list = tabs.querySelector( '.wp-block-tab-list' );
	const buttons = [ ...list.querySelectorAll( 'button' ) ];
	const panels = [ ...tabs.querySelectorAll( '.wp-block-tab-panel' ) ];
	const rect = ( el ) => { const r = el.getBoundingClientRect(); return { top: Math.round( r.top ), height: Math.round( r.height ) }; };
	return {
		aria: {
			tablist_role: list.getAttribute( 'role' ), tablist_label: list.getAttribute( 'aria-label' ),
			tabs: buttons.map( ( b ) => ( { role: b.getAttribute( 'role' ), selected: b.getAttribute( 'aria-selected' ), tabindex: b.getAttribute( 'tabindex' ), controls: b.getAttribute( 'aria-controls' ), id: b.id } ) ),
			panels: panels.map( ( p ) => ( { tag: p.tagName.toLowerCase(), role: p.getAttribute( 'role' ), hidden: p.hidden, labelledby: p.getAttribute( 'aria-labelledby' ), id: p.id } ) ),
		},
		layout: {
			tabs_display: getComputedStyle( tabs ).display, list_display: getComputedStyle( list ).display,
			visible_panel_index: panels.findIndex( ( p ) => ! p.hidden ),
			buttons_rect: buttons.map( rect ), panels_rect: panels.map( rect ),
			interactivity_ready: !! tabs.getAttribute( 'data-wp-interactive' ),
		},
		active_element: document.activeElement?.textContent?.trim().slice( 0, 20 ),
	};
};
for ( const width of [ 390, 1280 ] ) {
	const page = await ( await browser.newContext( { viewport: { width, height: width === 390 ? 844 : 900 }, deviceScaleFactor: 1 } ) ).newPage();
	await page.goto( URL, { waitUntil: 'networkidle' } );
	await page.waitForFunction( () => document.querySelector( '.wp-block-tab-list button[aria-selected]' ) !== null, null, { timeout: 15000 } ).catch( () => {} );
	const r = { initial: await page.evaluate( inspect ) };
	await page.locator( '.wp-block-tabs' ).screenshot( { path: `${ SHOTS }/tabs-${ width }-initial.png` } );
	// Click second tab.
	await page.locator( '.wp-block-tab-list button' ).nth( 1 ).click();
	await page.waitForTimeout( 300 );
	r.after_click_tab2 = await page.evaluate( inspect );
	await page.locator( '.wp-block-tabs' ).screenshot( { path: `${ SHOTS }/tabs-${ width }-tab2.png` } );
	// Keyboard: focus first tab, ArrowRight -> tab 3? (from tab 2 -> tab 3), then ArrowLeft, then Tab key into the panel.
	await page.locator( '.wp-block-tab-list button' ).nth( 1 ).focus();
	await page.keyboard.press( 'ArrowRight' );
	await page.waitForTimeout( 200 );
	r.after_arrow_right = await page.evaluate( inspect );
	await page.keyboard.press( 'Enter' );
	await page.waitForTimeout( 200 );
	r.after_arrow_right_enter = await page.evaluate( inspect );
	await page.keyboard.press( 'ArrowLeft' );
	await page.waitForTimeout( 200 );
	r.after_arrow_left = await page.evaluate( inspect );
	await page.keyboard.press( 'Space' );
	await page.waitForTimeout( 200 );
	r.after_arrow_left_space = await page.evaluate( inspect );
	r.aria_snapshot = await page.locator( '.wp-block-tabs' ).ariaSnapshot();
	await page.keyboard.press( 'Tab' );
	await page.waitForTimeout( 200 );
	r.after_tab_key = await page.evaluate( () => ( { active_element: document.activeElement?.tagName + '.' + document.activeElement?.className, in_panel: !! document.activeElement?.closest( '.wp-block-tab-panel' ) } ) );
	await page.locator( '.wp-block-tabs' ).screenshot( { path: `${ SHOTS }/tabs-${ width }-tab3-keyboard.png` } );
	await page.screenshot( { path: `${ SHOTS }/page-${ width }.png`, fullPage: false } );
	results[ width ] = r;
	await page.context().close();
}
await browser.close();
fs.writeFileSync( `${ OUT }/frontend-checks.json`, JSON.stringify( results, null, 2 ) );
for ( const [ w, r ] of Object.entries( results ) ) {
	console.log( w, 'display', r.initial.layout.tabs_display, r.initial.layout.list_display, 'visible', r.initial.layout.visible_panel_index, '->click', r.after_click_tab2.layout.visible_panel_index, '->right', r.after_arrow_right.layout.visible_panel_index, 'focus', r.after_arrow_right.active_element, '->enter', r.after_arrow_right_enter.layout.visible_panel_index, '->left', r.after_arrow_left.layout.visible_panel_index, '->space', r.after_arrow_left_space.layout.visible_panel_index, 'tabkey-in-panel', r.after_tab_key.in_panel );
	console.log( w, 'buttons', JSON.stringify( r.after_click_tab2.layout.buttons_rect ), 'panels', JSON.stringify( r.after_click_tab2.layout.panels_rect ) );
	console.log( w, 'aria', JSON.stringify( r.after_click_tab2.aria.tabs ) );
	console.log( w, 'aria snapshot:\n' + r.aria_snapshot );
}

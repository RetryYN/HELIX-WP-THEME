// Create the Tabs test post through the block editor (canonical save markup), then reload and verify every block is valid.
import { createRequire } from 'node:module';
import fs from 'node:fs';
const { S, OUT, WP_ADMIN_USER, WP_ADMIN_PASS } = process.env;
const BASE_URL = process.env.BASE_URL ?? 'http://localhost:8086';
const { chromium } = createRequire( `${ S }/package.json` )( 'playwright' );
const browser = await chromium.launch();
const page = await ( await browser.newContext( { viewport: { width: 1400, height: 900 } } ) ).newPage();
await page.goto( `${ BASE_URL }/wp-login.php` );
await page.fill( '#user_login', WP_ADMIN_USER );
await page.fill( '#user_pass', WP_ADMIN_PASS );
await page.click( '#wp-submit' );
await page.waitForLoadState( 'networkidle' );
await page.goto( `${ BASE_URL }/wp-admin/post-new.php` );
await page.waitForFunction( () => !! window.wp?.data?.select( 'core/editor' )?.getCurrentPostId(), null, { timeout: 60000 } );
await page.waitForTimeout( 1500 );
const postId = await page.evaluate( async () => {
	const { createBlock } = wp.blocks;
	const table = ( i ) => createBlock( 'core/table', { hasFixedLayout: true, body: [ 0, 1 ].map( ( r ) => ( { cells: [ { content: `Item ${ i }-${ r + 1 }`, tag: 'td' }, { content: `Value ${ i }-${ r + 1 }`, tag: 'td' } ] } ) ) } );
	const panels = [ [ 'Overview', 'Tab one paragraph: general overview text for the SP accordion PoC.' ], [ 'Specs', 'Tab two paragraph: specification details.' ], [ 'Pricing', 'Tab three paragraph: pricing notes.' ] ]
		.map( ( [ label, text ], i ) => createBlock( 'core/tab-panel', { label }, [ createBlock( 'core/paragraph', { content: text } ), table( i + 1 ) ] ) );
	const tabs = createBlock( 'core/tabs', {}, [ createBlock( 'core/tab-list' ), createBlock( 'core/tab-panels', {}, panels ) ] );
	wp.data.dispatch( 'core/block-editor' ).insertBlocks( [ tabs ] );
	wp.data.dispatch( 'core/editor' ).editPost( { title: 'poc-core-tabs-sp-canonical', status: 'publish' } );
	await wp.data.dispatch( 'core/editor' ).savePost();
	return wp.data.select( 'core/editor' ).getCurrentPostId();
} );
await page.goto( `${ BASE_URL }/wp-admin/post.php?post=${ postId }&action=edit` );
await page.waitForFunction( () => window.wp?.data?.select( 'core/block-editor' )?.getBlocks().length > 0, null, { timeout: 60000 } );
await page.waitForTimeout( 2000 );
const report = await page.evaluate( () => {
	const walk = ( blocks, acc = [] ) => { for ( const b of blocks ) { acc.push( { name: b.name, isValid: b.isValid } ); walk( b.innerBlocks, acc ); } return acc; };
	const all = walk( wp.data.select( 'core/block-editor' ).getBlocks() );
	return { postId: wp.data.select( 'core/editor' ).getCurrentPostId(), blocks: all, invalid: all.filter( ( b ) => ! b.isValid ).map( ( b ) => b.name ), savedContent: wp.data.select( 'core/editor' ).getEditedPostContent() };
} );
fs.mkdirSync( OUT, { recursive: true } );
await page.screenshot( { path: `${ OUT }/editor-1280-reloaded.png` } );
fs.writeFileSync( `${ OUT }/editor-validation.json`, JSON.stringify( report, null, 2 ) );
console.log( JSON.stringify( { postId: report.postId, invalid: report.invalid, count: report.blocks.length } ) );
await browser.close();

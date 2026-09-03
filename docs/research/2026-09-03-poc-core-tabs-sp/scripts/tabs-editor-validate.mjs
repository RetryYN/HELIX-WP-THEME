// Open the Tabs test post in the block editor, count invalid blocks, screenshot, then Update to persist editor-generated markup.
import { createRequire } from 'node:module';
import fs from 'node:fs';
const { S, POST_ID, OUT, WP_ADMIN_USER, WP_ADMIN_PASS } = process.env;
const BASE_URL = process.env.BASE_URL ?? 'http://localhost:8086';
const { chromium } = createRequire( `${ S }/package.json` )( 'playwright' );
const browser = await chromium.launch();
const page = await ( await browser.newContext( { viewport: { width: 1400, height: 900 } } ) ).newPage();
await page.goto( `${ BASE_URL }/wp-login.php` );
await page.fill( '#user_login', WP_ADMIN_USER );
await page.fill( '#user_pass', WP_ADMIN_PASS );
await page.click( '#wp-submit' );
await page.waitForLoadState( 'networkidle' );
await page.goto( `${ BASE_URL }/wp-admin/post.php?post=${ POST_ID }&action=edit` );
await page.waitForFunction( () => window.wp?.data?.select( 'core/block-editor' )?.getBlocks().length > 0, null, { timeout: 60000 } );
await page.waitForTimeout( 2000 );
const report = await page.evaluate( () => {
	const sel = wp.data.select( 'core/block-editor' );
	const walk = ( blocks, acc = [] ) => { for ( const b of blocks ) { acc.push( { name: b.name, isValid: b.isValid, clientId: b.clientId } ); walk( b.innerBlocks, acc ); } return acc; };
	const all = walk( sel.getBlocks() );
	return { blocks: all, invalid: all.filter( ( b ) => ! b.isValid ).map( ( b ) => b.name ), hasBlockRecovery: !! document.querySelector( '.block-editor-warning' ) };
} );
fs.mkdirSync( OUT, { recursive: true } );
await page.screenshot( { path: `${ OUT }/editor-1280-before-save.png`, fullPage: false } );
// Persist the editor's own serialization (tab-list is saved by the editor, not by the server).
await page.evaluate( () => wp.data.dispatch( 'core/editor' ).savePost() );
await page.waitForFunction( () => ! wp.data.select( 'core/editor' ).isSavingPost() && ! wp.data.select( 'core/editor' ).isEditedPostDirty(), null, { timeout: 30000 } );
report.savedContent = await page.evaluate( () => wp.data.select( 'core/editor' ).getEditedPostContent() );
fs.writeFileSync( `${ OUT }/editor-validation.json`, JSON.stringify( report, null, 2 ) );
console.log( JSON.stringify( { invalid: report.invalid, count: report.blocks.length, recovery: report.hasBlockRecovery } ) );
await browser.close();

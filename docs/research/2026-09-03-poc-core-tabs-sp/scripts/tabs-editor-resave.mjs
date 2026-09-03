// Re-open the post, let the tab-list block populate its `tabs` attribute, save again, and report validity + whether buttons persisted.
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
const open = async () => {
	await page.goto( `${ BASE_URL }/wp-admin/post.php?post=${ POST_ID }&action=edit` );
	await page.waitForFunction( () => window.wp?.data?.select( 'core/block-editor' )?.getBlocks().length > 0, null, { timeout: 60000 } );
	await page.waitForTimeout( 3000 );
	return page.evaluate( () => {
		const walk = ( blocks, acc = [] ) => { for ( const b of blocks ) { acc.push( { name: b.name, isValid: b.isValid } ); walk( b.innerBlocks, acc ); } return acc; };
		const all = walk( wp.data.select( 'core/block-editor' ).getBlocks() );
		const content = wp.data.select( 'core/editor' ).getEditedPostContent();
		return { invalid: all.filter( ( b ) => ! b.isValid ).map( ( b ) => b.name ), count: all.length, dirty: wp.data.select( 'core/editor' ).isEditedPostDirty(), buttons_in_serialized: ( content.match( /<button/g ) || [] ).length, savedContent: content };
	} );
};
const first = await open();
await page.evaluate( async () => { await wp.data.dispatch( 'core/editor' ).savePost(); } );
await page.waitForFunction( () => ! wp.data.select( 'core/editor' ).isSavingPost(), null, { timeout: 30000 } );
const second = await open();
await page.screenshot( { path: `${ OUT }/../screenshots/editor-1280-reloaded.png` } );
fs.writeFileSync( `${ OUT }/editor-validation.json`, JSON.stringify( { postId: Number( POST_ID ), first_open: { ...first, savedContent: undefined }, after_resave_reload: second }, null, 2 ) );
console.log( JSON.stringify( { first: { invalid: first.invalid, dirty: first.dirty, buttons: first.buttons_in_serialized }, second: { invalid: second.invalid, dirty: second.dirty, buttons: second.buttons_in_serialized } } ) );
await browser.close();

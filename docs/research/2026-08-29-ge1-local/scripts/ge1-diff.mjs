// 指定パターンの Block validation 差分を表示するローカル診断スクリプト。
// 注意: createBlock による innerBlocks の再生成は、paragraph の content など
// HTML ラッパーを含む rich-text 属性を二重にラップすることがある。
// EXP の子ブロック部分は参考値とし、親ブロックのラッパー差分のみを写すこと。
import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';

const { REPO, S, SLUG, WP_ADMIN_USER, WP_ADMIN_PASS } = process.env;
const BASE_URL = process.env.BASE_URL ?? 'http://localhost:8086';

if ( ! REPO || ! S || ! SLUG || ! WP_ADMIN_USER || ! WP_ADMIN_PASS ) {
	throw new Error( 'REPO, S, SLUG, WP_ADMIN_USER, and WP_ADMIN_PASS are required.' );
}

const { chromium } = createRequire( `${ S }/package.json` )( 'playwright' );

const wp = ( args, input ) => execFileSync(
	'docker',
	[ 'compose', 'run', '--rm', '-T', 'wpcli', ...args ],
	{
		cwd: REPO,
		input,
		stdio: [ 'pipe', 'pipe', 'ignore' ],
		maxBuffer: 64 * 1024 * 1024,
	}
).toString().trim();

const patternName = JSON.stringify( `agent-neo/${ SLUG }` );
const content = wp( [
	'eval',
	`echo WP_Block_Patterns_Registry::get_instance()->get_registered(${ patternName })["content"];`,
] );
const id = wp( [
	'post',
	'create',
	'--post_type=page',
	'--post_status=draft',
	`--post_title=ge1d-${ SLUG }`,
	'--porcelain',
	'-',
], content ).split( '\n' ).pop();

const browser = await chromium.launch();
const page = await ( await browser.newContext() ).newPage();
await page.goto( `${ BASE_URL }/wp-login.php` );
await page.fill( '#user_login', WP_ADMIN_USER );
await page.fill( '#user_pass', WP_ADMIN_PASS );
await page.click( '#wp-submit' );
await page.waitForLoadState( 'networkidle' );
await page.goto( `${ BASE_URL }/wp-admin/post.php?post=${ id }&action=edit`, { waitUntil: 'domcontentloaded' } );
await page.waitForFunction(
	() => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && wp.data.select( 'core/block-editor' ).getBlocks().length > 0,
	null,
	{ timeout: 60000 }
);
await page.waitForTimeout( 1500 );

const differences = await page.evaluate( () => {
	const selector = wp.data.select( 'core/block-editor' );
	const result = [];
	const fresh = ( block ) => wp.blocks.createBlock(
		block.name,
		block.attributes,
		( block.innerBlocks || [] ).map( fresh )
	);
	const walk = ( blockList ) => {
		for ( const block of blockList ) {
			if ( block.isValid === false ) {
				result.push( {
					name: block.name,
					original: block.originalContent || '',
					expected: wp.blocks.getSaveContent( block.name, block.attributes, ( block.innerBlocks || [] ).map( fresh ) ),
				} );
			}
			walk( block.innerBlocks || [] );
		}
	};
	walk( selector.getBlocks() );
	return result;
} );

await browser.close();
wp( [ 'post', 'delete', id, '--force' ] );
for ( const difference of differences ) {
	console.log( `== ${ difference.name }` );
	console.log( 'ORIG:', difference.original.slice( 0, 400 ) );
	console.log( 'EXP :', difference.expected.slice( 0, 400 ) );
}

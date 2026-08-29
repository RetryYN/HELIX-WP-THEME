// G-E1 ローカル版: 登録済みパターンを下書きページへ投入し、編集画面で isValid を集計する。
import fs from 'node:fs';
import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';

const { REPO, S, WP_ADMIN_USER, WP_ADMIN_PASS } = process.env;
const BASE_URL = process.env.BASE_URL ?? 'http://localhost:8086';

if ( ! REPO || ! S || ! WP_ADMIN_USER || ! WP_ADMIN_PASS ) {
	throw new Error( 'REPO, S, WP_ADMIN_USER, and WP_ADMIN_PASS are required.' );
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

const slugs = fs.readFileSync( `${ S }/slugs.txt`, 'utf8' ).split( /\s+/ ).filter( Boolean );
const ids = {};

for ( const slug of slugs ) {
	const patternName = JSON.stringify( `agent-neo/${ slug }` );
	const content = wp( [
		'eval',
		`echo WP_Block_Patterns_Registry::get_instance()->get_registered(${ patternName })["content"];`,
	] );
	if ( ! content ) {
		console.error( 'NO CONTENT', slug );
		continue;
	}
	ids[ slug ] = wp( [
		'post',
		'create',
		'--post_type=page',
		'--post_status=draft',
		`--post_title=ge1-${ slug }`,
		'--porcelain',
		'-',
	], content ).split( '\n' ).pop();
}

const browser = await chromium.launch();
const context = await browser.newContext( { viewport: { width: 1400, height: 900 } } );
const page = await context.newPage();
await page.goto( `${ BASE_URL }/wp-login.php` );
await page.fill( '#user_login', WP_ADMIN_USER );
await page.fill( '#user_pass', WP_ADMIN_PASS );
await page.click( '#wp-submit' );
await page.waitForLoadState( 'networkidle' );

const output = {};
for ( const [ slug, id ] of Object.entries( ids ) ) {
	await page.goto( `${ BASE_URL }/wp-admin/post.php?post=${ id }&action=edit`, {
		waitUntil: 'domcontentloaded',
		timeout: 60000,
	} );
	await page.waitForFunction(
		() => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && wp.data.select( 'core/block-editor' ).getBlocks().length > 0,
		null,
		{ timeout: 30000 }
	).catch( () => {} );
	await page.waitForTimeout( 1200 );

	output[ slug ] = await page.evaluate( () => {
		const selector = wp.data.select( 'core/block-editor' );
		const invalid = [];
		let blocks = 0;
		const walk = ( blockList ) => {
			for ( const block of blockList ) {
				blocks += 1;
				if ( block.isValid === false ) {
					invalid.push( block.name );
				}
				walk( block.innerBlocks || [] );
			}
		};
		walk( selector.getBlocks() );
		return { blocks, invalid };
	} );
	if ( output[ slug ].invalid.length ) {
		await page.screenshot( { path: `${ S }/invalid-${ slug }.jpg`, type: 'jpeg', quality: 60 } );
	}
	console.log( slug, JSON.stringify( output[ slug ] ) );
}

await browser.close();
for ( const id of Object.values( ids ) ) {
	wp( [ 'post', 'delete', id, '--force' ] );
}
fs.writeFileSync( `${ S }/ge1-local.json`, JSON.stringify( output, null, 1 ) );
console.log( 'TOTAL invalid:', Object.values( output ).reduce( ( total, result ) => total + result.invalid.length, 0 ), '/ patterns:', Object.keys( output ).length );

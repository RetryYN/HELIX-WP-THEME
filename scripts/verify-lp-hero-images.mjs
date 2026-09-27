import assert from 'node:assert/strict';
import { createHash, randomBytes } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';
import { contentLab } from './lib/content-lab-env.mjs';

const root = fileURLToPath(new URL('../', import.meta.url));
const theme = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/';
const themeFiles = [
  'functions.php',
  'inc/lp-hero-images.php',
  'templates/page-lp.html',
  'patterns/lp.php',
  'theme.json',
  'assets/css/theme.css',
  'assets/img/hero.png',
  'assets/img/product-a.png',
];
const producerPath = 'scripts/verify-lp-hero-images.mjs';
const sources = [producerPath, 'scripts/lib/content-lab-env.mjs', ...themeFiles.map(file => theme + file)];
const digestSources = () => Object.fromEntries(sources.map(file => [
  file, createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex'),
]));
const timestamp = new Date().toISOString().replace(/[-:.]/gu, '');
const runId = `${timestamp}-${randomBytes(4).toString('hex')}`;
const relativeOutput = path.join('local-evidence', 'lp-hero-images', runId);
const output = path.join(root, relativeOutput);
const reportPath = path.join(output, 'verification.json');
const fixtureSlug = 'lp-hero-image-regression';
const owner = randomBytes(24).toString('hex');
const ownerSha256 = createHash('sha256').update(owner).digest('hex');
const variants = ['split', 'fullbleed', 'product', 'text-only'];
const devices = [
  { id: 'sp', viewport: { width: 390, height: 844 }, deviceScaleFactor: 3 },
  { id: 'pc', viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 },
];
const report = {
  schema: 'wt-lp-hero-image-verification.v1',
  completed: false,
  scope: 'LP hero image-loading regression; not WT-NFR-PERF-03 performance acceptance',
  runId,
  sourceDigests: {},
  runtime: [],
  browser: [],
  fixture: { slug: fixtureSlug, ownerSha256, created: false, deleted: false },
  cleanup: {},
  errors: [],
};
fs.mkdirSync(path.join(output, 'screenshots'), { recursive: true });
const save = () => fs.writeFileSync(reportPath, `${JSON.stringify(report, null, 2)}\n`);
save();

const php = String.raw`$variant = getenv( 'LP_HERO_VARIANT' );
if ( $variant ) { $_GET['wt'] = 'lp_hero:' . $variant; }
require '/var/www/html/wp-load.php';
$action = getenv( 'LP_HERO_ACTION' );
$slug = getenv( 'LP_HERO_SLUG' );
$owner = getenv( 'LP_HERO_OWNER' );
$files = json_decode( getenv( 'LP_HERO_THEME_FILES' ), true );
if ( 'preflight' === $action ) {
  $hashes = array();
  foreach ( $files as $file ) {
    $path = get_theme_file_path( $file );
    $hashes[ $file ] = is_file( $path ) ? hash_file( 'sha256', $path ) : null;
  }
  echo wp_json_encode( array(
    'wordpress' => get_bloginfo( 'version' ), 'theme' => get_stylesheet(), 'url' => home_url( '/' ),
    'templateExists' => is_file( get_theme_file_path( 'templates/page-lp.html' ) ),
    'hashes' => $hashes, 'themeModsHash' => hash( 'sha256', maybe_serialize( get_theme_mods() ) ),
  ) ); exit;
}
$page = get_page_by_path( $slug, OBJECT, 'page' );
if ( 'inspect' === $action ) {
  echo wp_json_encode( array( 'exists' => (bool) $page,
    'id' => $page ? (int) $page->ID : null,
    'owner' => $page ? (string) get_post_meta( $page->ID, '_helix_lp_hero_image_owner', true ) : null,
    'template' => $page ? get_page_template_slug( $page->ID ) : null,
    'themeModsHash' => hash( 'sha256', maybe_serialize( get_theme_mods() ) ) ) ); exit;
}
if ( 'create' === $action ) {
  if ( $page ) { fwrite( STDERR, 'fixture slug collision' ); exit( 41 ); }
  $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish',
    'post_name' => $slug, 'post_title' => 'Private LP image verification fixture',
    'post_content' => '<!-- wp:heading {"level":1} --><h1>LP image verification fixture</h1><!-- /wp:heading --><p>Private image-loading verification.</p>',
    'meta_input' => array( '_helix_lp_hero_image_owner' => $owner, '_wp_page_template' => 'page-lp' ) ), true );
  if ( is_wp_error( $id ) || ! $id ) { fwrite( STDERR, 'fixture creation failed' ); exit( 42 ); }
  update_post_meta( (int) $id, '_wp_page_template', 'page-lp' );
  $page = get_post( (int) $id );
  $verified = hash_equals( $owner, (string) get_post_meta( $page->ID, '_helix_lp_hero_image_owner', true ) )
    && 'page-lp' === get_page_template_slug( $page->ID );
  echo wp_json_encode( array( 'created' => (bool) $verified, 'id' => (int) $page->ID,
    'template' => get_page_template_slug( $page->ID ), 'url' => get_permalink( $page->ID ) ) ); exit;
}
if ( 'delete' === $action ) {
  if ( $page ) {
    if ( ! hash_equals( $owner, (string) get_post_meta( $page->ID, '_helix_lp_hero_image_owner', true ) ) ) {
      fwrite( STDERR, 'fixture owner check failed; refusing deletion' ); exit( 43 );
    }
    wp_delete_post( $page->ID, true );
  }
  echo wp_json_encode( array( 'absent' => ! get_page_by_path( $slug, OBJECT, 'page' ) ) ); exit;
}
if ( 'render' === $action ) {
  if ( ! function_exists( 'wt_defer_inactive_lp_hero_images' )
    || false === has_filter( 'render_block_core/html', 'wt_defer_inactive_lp_hero_images' ) ) {
    fwrite( STDERR, 'LP image filter is not loaded' ); exit( 44 );
  }
  ob_start(); include get_theme_file_path( 'patterns/lp.php' ); $pattern = ob_get_clean();
  $blocks = parse_blocks( $pattern ); $target = null;
  foreach ( $blocks as $block ) {
    if ( 'core/html' === ( $block['blockName'] ?? null ) && str_contains( $block['innerHTML'] ?? '', 'wt-lp-hero-slot' ) ) {
      $target = $block; break;
    }
  }
  if ( ! $target ) { fwrite( STDERR, 'LP hero core/html block not found' ); exit( 45 ); }
  $outside = '<img data-lp-verifier-outside="1" src="outside-probe.png" loading="eager" fetchpriority="high" alt="">';
  $target['innerHTML'] .= $outside;
  $target['innerContent'] = array( $target['innerHTML'] );
  $original = $target['innerHTML'];
  $rendered = render_block( $target );
  $collect = static function ( $html ) {
    $tags = new WP_HTML_Tag_Processor( $html ); $sections = array();
    $images = array( 'split' => array(), 'fullbleed' => array(), 'product' => array(), 'text-only' => array(), 'outside' => array() );
    while ( $tags->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
      if ( 'SECTION' === $tags->get_tag() ) {
        if ( $tags->is_tag_closer() ) { array_pop( $sections ); continue; }
        $classes = preg_split( '/\\s+/', trim( (string) $tags->get_attribute( 'class' ) ) ); $variant = null;
        foreach ( array( 'split', 'fullbleed', 'product', 'text-only' ) as $candidate ) {
          if ( in_array( 'wt-lp-hero--' . $candidate, $classes, true ) ) { $variant = $candidate; break; }
        }
        $sections[] = $variant;
      }
      if ( 'IMG' === $tags->get_tag() ) {
        $variant = null;
        foreach ( array_reverse( $sections ) as $candidate ) { if ( null !== $candidate ) { $variant = $candidate; break; } }
        $images[ $variant ?? 'outside' ][] = array( 'src' => $tags->get_attribute( 'src' ),
          'loading' => $tags->get_attribute( 'loading' ), 'fetchpriority' => $tags->get_attribute( 'fetchpriority' ),
          'outsideMarker' => $tags->get_attribute( 'data-lp-verifier-outside' ) );
      }
    }
    return $images;
  };
  $before = $collect( $original ); $after = $collect( $rendered ); $rows = array();
  $check = static function ( $id, $pass ) use ( &$rows ) { $rows[] = array( 'id' => $id, 'pass' => (bool) $pass ); };
  $count_sections = static function ( $html ) {
    $counts = array_fill_keys( array( 'split', 'fullbleed', 'product', 'text-only' ), 0 );
    $tags = new WP_HTML_Tag_Processor( $html );
    while ( $tags->next_tag() ) {
      if ( 'SECTION' !== $tags->get_tag() ) { continue; }
      $classes = preg_split( '/\\s+/', trim( (string) $tags->get_attribute( 'class' ) ) );
      foreach ( array_keys( $counts ) as $candidate ) {
        if ( in_array( 'wt-lp-hero--' . $candidate, $classes, true ) ) { $counts[ $candidate ]++; }
      }
    }
    return $counts;
  };
  $before_sections = $count_sections( $original );
  $after_sections = $count_sections( $rendered );
  $hero_keys = array( 'split', 'fullbleed', 'product', 'text-only' );
  $before_hero = array_intersect_key( $before, array_fill_keys( $hero_keys, true ) );
  $after_hero = array_intersect_key( $after, array_fill_keys( $hero_keys, true ) );
  $expected_image_counts = array( 'split' => 1, 'fullbleed' => 1, 'product' => 1, 'text-only' => 0 );
  $selected = wt_opt( 'lp_hero' );
  $body = get_body_class();
  $check( 'axis-body-class-match', $selected === $variant && in_array( 'wt-lp-hero-' . $selected, $body, true ) );
  $check( 'all-four-source-sections-present', array_values( $before_sections ) === array( 1, 1, 1, 1 ) );
  $check( 'all-four-rendered-sections-preserved', $after_sections === $before_sections
    && array_values( $after_sections ) === array( 1, 1, 1, 1 ) );
  $check( 'source-hero-image-counts-expected', array_map( 'count', $before_hero ) === $expected_image_counts );
  $check( 'rendered-hero-image-counts-preserved', array_map( 'count', $after_hero ) === $expected_image_counts );
  $check( 'three-hero-images-preserved', array_sum( array_map( 'count', $before_hero ) ) === 3
    && array_sum( array_map( 'count', $after_hero ) ) === 3 );
  foreach ( array( 'split', 'fullbleed', 'product', 'text-only' ) as $candidate ) {
    if ( $candidate === $selected ) {
      $check( 'selected-' . $candidate . '-attributes-preserved', $before[ $candidate ] === $after[ $candidate ] );
    } else {
      $before_srcs = array_column( $before[ $candidate ], 'src' );
      $after_srcs = array_column( $after[ $candidate ], 'src' );
      $check( 'inactive-' . $candidate . '-images-retained-in-order', count( $before_srcs ) === count( $after_srcs )
        && $before_srcs === $after_srcs );
      $check( 'inactive-' . $candidate . '-lazy-no-priority', count( $after[ $candidate ] ) === count( $before[ $candidate ] )
        && array_reduce( $after[ $candidate ], static function ( $ok, $image ) {
          return $ok && 'lazy' === $image['loading'] && null === $image['fetchpriority'];
        }, true ) );
    }
  }
  $check( 'slot-outside-image-unchanged', $before['outside'] === $after['outside']
    && 1 === count( array_filter( $after['outside'], static fn( $image ) => '1' === $image['outsideMarker'] ) ) );
  $unknown = str_replace( 'wt-lp-hero--product', 'wt-lp-hero--unknown', $original );
  $malformed = '<div class="wt-lp-hero-slot"><section class="wt-lp-hero wt-lp-hero--split"><img src="x.png" loading="eager" fetchpriority="high"></div></section>';
  $unmarked = '<section><img src="outside.png" loading="eager" fetchpriority="high"></section>';
  $check( 'unknown-variant-no-mutation', $unknown === wt_defer_inactive_lp_hero_images( $unknown ) );
  $check( 'malformed-markup-no-mutation', $malformed === wt_defer_inactive_lp_hero_images( $malformed ) );
  $check( 'unmarked-html-no-mutation', $unmarked === wt_defer_inactive_lp_hero_images( $unmarked ) );
  echo wp_json_encode( array( 'variant' => $selected, 'rows' => $rows, 'passed' => ! in_array( false, array_column( $rows, 'pass' ), true ) ) ); exit;
}
fwrite( STDERR, 'unknown action' ); exit( 46 );`;

function phpCall(action, variant = '') {
  const args = ['exec', '-i', '-e', `LP_HERO_ACTION=${action}`, '-e', `LP_HERO_SLUG=${fixtureSlug}`,
    '-e', `LP_HERO_OWNER=${owner}`, '-e', `LP_HERO_VARIANT=${variant}`,
    '-e', `LP_HERO_THEME_FILES=${JSON.stringify(themeFiles)}`, contentLab.wpContainer, 'php', '-r', php];
  return JSON.parse(execFileSync('docker', args, { cwd: root, encoding: 'utf8', maxBuffer: 4 * 1024 * 1024 }).trim());
}
const digest = bytes => createHash('sha256').update(bytes).digest('hex');

let browser;
let created = false;
try {
  assert.ok(['localhost', '127.0.0.1'].includes(new URL(contentLab.baseUrl).hostname), 'loopback Content Lab only');
  report.sourceDigests = digestSources();
  const preflight = phpCall('preflight');
  assert.equal(preflight.theme, 'helix-wt');
  assert.equal(preflight.url.replace(/\/$/u, ''), contentLab.baseUrl, 'WordPress URL must match selected Content Lab');
  assert.equal(preflight.templateExists, true, 'mounted page-lp template is required');
  for (const file of themeFiles) assert.equal(preflight.hashes[file], report.sourceDigests[theme + file], `mounted source: ${file}`);
  report.wordpressVersion = preflight.wordpress;
  report.templateMounted = preflight.templateExists;
  report.themeModsHashBefore = preflight.themeModsHash;

  const before = phpCall('inspect');
  report.fixture.before = { exists: before.exists, id: before.id, template: before.template };
  assert.equal(before.exists, false, 'reserved fixture slug already exists; refusing to touch it');
  created = true;
  const fixture = phpCall('create');
  assert.equal(fixture.created, true);
  assert.equal(fixture.template, 'page-lp');
  assert.equal(new URL(fixture.url).origin, contentLab.baseUrl);
  report.fixture.created = true;
  report.fixture.urlPath = `${new URL(fixture.url).pathname}`;
  report.fixture.template = fixture.template;
  report.fixture.id = fixture.id;
  const afterCreate = phpCall('inspect');
  assert.equal(afterCreate.owner, owner);
  assert.equal(afterCreate.themeModsHash, report.themeModsHashBefore, 'fixture creation changed theme settings');
  report.fixture.ownerVerified = true;
  save();

  for (const variant of variants) {
    const row = phpCall('render', variant);
    report.runtime.push({ variant, ...row });
    assert.equal(row.passed, true, `WP HTML rendering checks failed for ${variant}`);
  }

  const launchOptions = { headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage'] };
  if (process.env.CHROME_PATH) launchOptions.executablePath = process.env.CHROME_PATH;
  browser = await chromium.launch(launchOptions);
  report.browserVersion = browser.version();
  for (const device of devices) for (const variant of variants) {
    const row = { device: device.id, viewport: device.viewport, deviceScaleFactor: device.deviceScaleFactor,
      reducedMotion: 'no-preference', variant, errors: [] };
    let context;
    try {
      context = await browser.newContext({ viewport: device.viewport, deviceScaleFactor: device.deviceScaleFactor, reducedMotion: 'no-preference' });
      const page = await context.newPage();
      const imageRequests = [];
      page.on('request', request => {
        if (request.resourceType() !== 'image') return;
        try { imageRequests.push(new URL(request.url()).pathname); } catch {}
      });
      const target = new URL(report.fixture.urlPath, contentLab.baseUrl);
      target.searchParams.set('wt', `lp_hero:${variant}`);
      const response = await page.goto(target.href, { waitUntil: 'networkidle', timeout: 45000 });
      assert.equal(response?.status(), 200);
      await page.evaluate(() => document.fonts.ready);
      const inspected = await page.evaluate(async selected => {
        const sectionInfo = [...document.querySelectorAll('.wt-lp-hero-slot section.wt-lp-hero')].map(section => {
          const rect = section.getBoundingClientRect(); const style = getComputedStyle(section);
          const images = [...section.querySelectorAll('img')].map(image => ({
            src: image.currentSrc || image.src, path: new URL(image.currentSrc || image.src).pathname,
            loading: image.getAttribute('loading'), fetchpriority: image.getAttribute('fetchpriority'),
            complete: image.complete, naturalWidth: image.naturalWidth,
          }));
          return { variant: [...section.classList].find(name => name.startsWith('wt-lp-hero--'))?.slice('wt-lp-hero--'.length),
            visible: style.display !== 'none' && rect.width > 0 && rect.height > 0, images };
        });
        const outsideImages = [...document.querySelectorAll('img')].filter(image => !image.closest('.wt-lp-hero-slot'))
          .map(image => new URL(image.currentSrc || image.src).pathname);
        const heroVariantCounts = Object.fromEntries(['split', 'fullbleed', 'product', 'text-only'].map(variant =>
          [variant, sectionInfo.filter(section => section.variant === variant).length]));
        const heroImageCounts = Object.fromEntries(['split', 'fullbleed', 'product', 'text-only'].map(variant =>
          [variant, sectionInfo.filter(section => section.variant === variant).reduce((count, section) => count + section.images.length, 0)]));
        const heroImageCount = Object.values(heroImageCounts).reduce((count, imageCount) => count + imageCount, 0);
        const activeElement = [...document.querySelectorAll('.wt-lp-hero-slot section.wt-lp-hero')]
          .find(section => section.classList.contains(`wt-lp-hero--${selected}`))?.querySelector('img');
        let selectedDecodeSucceeded = selected === 'text-only';
        if (activeElement) {
          try { await activeElement.decode(); selectedDecodeSucceeded = true; } catch { selectedDecodeSucceeded = false; }
        }
        if (activeElement) {
          const active = sectionInfo.find(section => section.variant === selected);
          if (active?.images[0]) {
            active.images[0].complete = activeElement.complete;
            active.images[0].naturalWidth = activeElement.naturalWidth;
            active.images[0].decodeSucceeded = selectedDecodeSucceeded;
          }
        }
        const active = sectionInfo.find(section => section.variant === selected);
        return { bodyClasses: [...document.body.classList], visible: sectionInfo.filter(section => section.visible).map(section => section.variant),
          sections: sectionInfo, heroVariantCounts, heroImageCounts, heroImageCount, active, outsideImages, selectedDecodeSucceeded };
      }, variant);
      row.visibleVariants = inspected.visible;
      row.bodySelectionMatches = inspected.bodyClasses.includes(`wt-lp-hero-${variant}`);
      row.selectedImages = inspected.active?.images ?? [];
      row.selectedDecodeSucceeded = inspected.selectedDecodeSucceeded;
      row.inactiveImages = inspected.sections.filter(section => section.variant !== variant).flatMap(section => section.images);
      row.heroVariantCounts = inspected.heroVariantCounts;
      row.heroImageCounts = inspected.heroImageCounts;
      row.heroImageCount = inspected.heroImageCount;
      row.outsideImageCount = inspected.outsideImages.length;
      row.imageRequestPaths = [...new Set(imageRequests)].sort();
      row.screenshot = `screenshots/${variant}-${device.id}.png`;
      const screenshot = await page.screenshot({ path: path.join(output, row.screenshot), animations: 'disabled' });
      row.screenshotSha256 = digest(screenshot);

      if (inspected.visible.length !== 1 || inspected.visible[0] !== variant) row.errors.push('selected hero visibility does not match variant');
      if (!row.bodySelectionMatches) row.errors.push('body selection class differs from wt_opt selection');
      if (Object.values(row.heroVariantCounts).some(count => count !== 1)
        || row.heroImageCount !== 3
        || row.heroImageCounts.split !== 1 || row.heroImageCounts.fullbleed !== 1
        || row.heroImageCounts.product !== 1 || row.heroImageCounts['text-only'] !== 0) {
        row.errors.push('hero variants or three-image structure changed');
      }
      const expectedSelectedCount = variant === 'text-only' ? 0 : 1;
      if (row.selectedImages.length !== expectedSelectedCount) row.errors.push('selected variant image count is wrong');
      for (const image of row.selectedImages) {
        if (image.loading !== 'eager' || image.fetchpriority !== 'high') row.errors.push('selected image attributes changed');
        if (!image.decodeSucceeded || !image.complete || image.naturalWidth <= 0) row.errors.push('selected image did not decode');
      }
      if (row.inactiveImages.some(image => image.loading !== 'lazy' || image.fetchpriority !== null)) {
        row.errors.push('inactive hero image is not lazy without fetchpriority');
      }
      const activePaths = new Set(row.selectedImages.map(image => image.path));
      const outsidePaths = new Set(inspected.outsideImages);
      const inactiveDistinctPaths = [...new Set(row.inactiveImages.map(image => image.path))]
        .filter(imagePath => !activePaths.has(imagePath) && !outsidePaths.has(imagePath));
      row.inactiveDistinctPaths = inactiveDistinctPaths;
      row.inactiveDistinctRequests = inactiveDistinctPaths.filter(imagePath => row.imageRequestPaths.includes(imagePath));
      row.sharedInactivePaths = [...new Set(row.inactiveImages.map(image => image.path))]
        .filter(imagePath => activePaths.has(imagePath) || outsidePaths.has(imagePath));
      if (row.inactiveDistinctRequests.length) row.errors.push('distinct inactive hero image was requested');
      if (expectedSelectedCount && row.selectedImages.some(image =>
        !outsidePaths.has(image.path) && !row.imageRequestPaths.includes(image.path))) {
        row.errors.push('selected hero asset was not requested');
      }
      row.pass = row.errors.length === 0;
      report.browser.push(row);
      await page.close();
      save();
    } catch (error) {
      row.errors.push(String(error?.message ?? error).slice(0, 500));
      row.pass = false;
      report.browser.push(row);
      save();
    } finally {
      await context?.close();
    }
  }
} catch (error) {
  report.errors.push(String(error?.message ?? error).slice(0, 1000));
} finally {
  try { await browser?.close(); }
  catch (error) { report.errors.push(`browser close failed: ${String(error?.message ?? error).slice(0, 300)}`); }
  if (created) {
    try {
      const current = phpCall('inspect');
      if (!current.exists || current.owner !== owner) throw new Error('fixture ownership cannot be verified; refusing cleanup');
      report.cleanup.beforeDeleteOwnerVerified = true;
      report.cleanup.deleted = phpCall('delete').absent;
      report.cleanup.absenceConfirmed = phpCall('inspect').exists === false;
      report.fixture.deleted = report.cleanup.deleted && report.cleanup.absenceConfirmed;
      report.themeModsHashAfter = phpCall('inspect').themeModsHash;
      if (report.themeModsHashAfter !== report.themeModsHashBefore) report.errors.push('theme settings changed during run');
    } catch (error) { report.cleanup.error = String(error?.message ?? error).slice(0, 500); }
  }
  report.sourceDigestsAfter = digestSources();
  report.sourcesUnchanged = JSON.stringify(report.sourceDigestsAfter) === JSON.stringify(report.sourceDigests);
  if (!report.sourcesUnchanged) report.errors.push('source files changed during run');
  report.completed = report.errors.length === 0 && report.runtime.length === variants.length
    && report.runtime.every(row => row.passed) && report.browser.length === variants.length * devices.length
    && report.browser.every(row => row.pass) && report.fixture.deleted && report.cleanup.absenceConfirmed;
  save();
}

console.log(JSON.stringify({ completed: report.completed, variants: variants.length, devices: devices.length,
  runtimeCases: report.runtime.length, browserCases: report.browser.length, cleanup: report.cleanup,
  output: relativeOutput }, null, 2));
if (!report.completed) process.exitCode = 1;

import { test, expect } from '@playwright/test';

// Serve the repository root locally; this suite does not contact WordPress.
const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
test.beforeEach(async ({ page }) => {
  await page.goto(url);
  await expect(page.locator('#total')).toHaveText('604');
});

test('search, comparison limit, PC/SP and requirement discovery', async ({ page }) => {
  await expect(page.locator('#collection-title')).toHaveText('共通設定・部品');
  await page.locator('[data-face="all"]').click();
  await page.locator('#search').fill('nonexistent-candidate');
  await expect(page.locator('#empty')).toBeVisible();
  await page.locator('#search').fill('');
  for (let i = 0; i < 4; i++) await page.locator('.compare-pick input').nth(i).click();
  await expect(page.locator('.compare-pick input:checked')).toHaveCount(3);
  await expect(page.locator('#notice')).toContainText('3候補まで');
  await page.locator('#open-compare').click();
  await expect(page.locator('#compare .compare-grid>section')).toHaveCount(3);
  await page.keyboard.press('Escape');
  await page.locator('[data-device="sp"]').click();
  await expect(page.locator('#count')).toContainText('SP');
  await page.locator('#tab-requirements').click();
  await expect(page.locator('.req-row')).toHaveCount(132);
  await page.locator('#req-search').fill('WT-FR-FORM-01');
  await expect(page.locator('.req-row')).toHaveCount(1);
  await page.locator('.req-row > summary').click();
  await expect(page.locator('.req-row')).toContainText('WT-AC-');
  await page.locator('.req-row button').click();
  await expect(page.locator('#collection-title')).toHaveText('要求に関連する候補');
});

test('memo survives reload and export/import; invalid input does not change it', async ({ page }) => {
  await page.locator('.tile-open').first().click();
  await page.locator('#detail').getByRole('button', { name: '採用候補', exact: true }).click();
  await page.locator('#detail textarea').fill('余白と見出しを再確認');
  await page.keyboard.press('Escape');
  await page.reload();
  await page.locator('.tile-open').first().click();
  await expect(page.locator('#detail textarea')).toHaveValue('余白と見出しを再確認');
  await expect(page.locator('#detail').getByRole('button', { name: '採用候補', exact: true })).toHaveAttribute('aria-pressed', 'true');
  await page.keyboard.press('Escape');
  const download = page.waitForEvent('download'); await page.locator('#export').click();
  const exported = await download; const file = await exported.path();
  expect(file).toBeTruthy();
  await page.evaluate(() => localStorage.clear()); await page.reload();
  await page.locator('#file').setInputFiles(file!);
  await expect(page.locator('.decision-badge').first()).toHaveText('採用候補');
  const before = await page.evaluate(() => localStorage.getItem('helix-selection-memos.v1'));
  await page.locator('#file').setInputFiles({ name: 'invalid.json', mimeType: 'application/json', buffer: Buffer.from('{"schema":"helix-selection-memos.v1","memos":{"unknown":{"status":"adopt","note":"bad"}}}') });
  await expect(page.locator('#notice')).toContainText('読込できません');
  expect(await page.evaluate(() => localStorage.getItem('helix-selection-memos.v1'))).toBe(before);
});

test('mobile fits viewport and tabs support keyboard; images load', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: 375, height: 812 });
  await page.locator('[data-device="sp"]').click();
  await expect(page.locator('.preview img').first()).toBeVisible();
  await expect.poll(() => page.locator('.preview img').first().evaluate((img: HTMLImageElement) => img.naturalWidth)).toBeGreaterThan(0);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.locator('#tab-gallery').focus(); await page.keyboard.press('ArrowRight');
  await expect(page.locator('#tab-requirements')).toBeFocused();
  await expect(page.locator('#requirements-panel')).toBeVisible();
  await page.keyboard.press('Home');
  await expect(page.locator('#tab-gallery')).toBeFocused();
  await page.screenshot({ path: testInfo.outputPath('mobile.png'), fullPage: false });
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.locator('[data-device="pc"]').click();
  await page.screenshot({ path: testInfo.outputPath('desktop.png'), fullPage: false });
});


test('acceptance ID, remaining text and evidence status filters preserve scope and recover from zero', async ({ page }) => {
  await page.locator('#tab-requirements').click();
  await expect(page.locator('#requirements-count')).toContainText('132要求 / 290受入条件');
  await page.locator('#req-search').fill('WT-AC-INTERVIEW-01C');
  await expect(page.locator('.req-row')).toHaveCount(1);
  await expect(page.locator('.acceptance-row')).toHaveCount(1);
  await expect(page.locator('.acceptance-row')).toHaveAttribute('open', '');
  await expect(page.locator('.acceptance-row')).toContainText('確認取り消し');
  await page.locator('#evidence-state').selectOption('missing');
  await expect(page.locator('#requirements-empty')).toBeVisible();
  await page.locator('#reset-requirements').click();
  await expect(page.locator('#req-search')).toBeFocused();
  await expect(page.locator('.req-row')).toHaveCount(132);
  await page.locator('#evidence-state').selectOption('partial');
  expect(await page.locator('.acceptance-row').count()).toBeGreaterThan(0);
  await expect(page.locator('.acceptance-row:not([data-evidence-state="partial"])')).toHaveCount(0);
  await page.locator('#req-search').fill('___no_matching_remaining___');
  await expect(page.locator('#requirements-empty')).toBeVisible();
  await page.locator('#reset-requirements').click();
  await page.locator('#req-search').fill('専用');
  expect(await page.locator('.acceptance-row').count()).toBeGreaterThan(0);
  await page.setViewportSize({ width: 375, height: 812 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});

test('site guide collection exposes eleven paired pages and the live pricing route', async ({ page }) => {
  await page.locator('[data-face="site"]').click();
  await expect(page.locator('.tile-open')).toHaveCount(11);
  await page.locator('#search').fill('site-pricing');
  await expect(page.locator('.tile-open')).toHaveCount(1);
  await page.locator('[data-device="sp"]').click();
  await page.locator('.tile-open').click();
  await expect(page.locator('#detail .large-preview img')).toHaveAttribute('src', /site-pages\/pricing-sp\.jpg$/);
  await expect(page.locator('#detail').getByRole('link', { name: 'ローカルの実機で操作する ↗' })).toHaveAttribute('href', 'http://127.0.0.1:8098/site-pricing/');
  await expect(page.locator('#detail')).toContainText('WT-FR-PAGE-01');
});

test('quality comparison opens from catalog with paired evidence and a return path', async ({ page }) => {
  await page.getByRole('link', { name: '常設案内の改善を、変更前後で比較する →' }).click();
  await expect(page.getByRole('heading', { level: 1 })).toContainText('押せる領域を広げる');
  await expect(page.locator('figure')).toHaveCount(8);
  await expect(page.locator('table')).toContainText('370');
  for (const image of await page.locator('img').all()) {
    await image.scrollIntoViewIfNeeded();
    await expect.poll(() => image.evaluate((e: HTMLImageElement) => e.complete && e.naturalWidth > 0)).toBe(true);
  }
  await page.setViewportSize({ width: 375, height: 812 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  for (const name of ['変更前の測定', '変更後の測定']) {
    const href = await page.getByRole('link', { name, exact: true }).getAttribute('href');
    const response = await page.request.get(new URL(href!, page.url()).href);
    expect(response.ok()).toBe(true);
    expect((await response.json()).completed).toBe(true);
  }
  await page.getByRole('link', { name: '← 選択カタログへ' }).click();
  await expect(page.locator('.tile-open').first()).toBeVisible();
});


test('independent faces expose shared, own and off comparisons', async ({ page }) => {
  await page.locator('[data-face="inheritance"]').click();
  await expect(page.locator('.tile-open')).toHaveCount(18);
  await page.locator('#search').fill('chrome-content-content_learning');
  await expect(page.locator('.tile-open')).toHaveCount(3);
  await page.locator('[data-device="sp"]').click();
  await page.locator('.tile-open').first().click();
  await expect(page.locator('#detail')).toContainText('WT-FR-PARTS-03');
  await expect(page.locator('#detail')).toContainText('WT-FR-LEARN-01');
  await expect(page.locator('#detail').getByRole('link', { name: 'ローカルの実機で操作する ↗' })).toHaveAttribute('href', /content_chrome/);
});

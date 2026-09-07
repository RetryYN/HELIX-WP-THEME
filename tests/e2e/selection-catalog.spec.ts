import { test, expect } from '@playwright/test';

// Serve the repository root locally; this suite does not contact WordPress.
const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
test.beforeEach(async ({ page }) => {
  await page.goto(url);
  await expect(page.locator('#total')).toHaveText('574');
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

import { test, expect } from '@playwright/test';
const base = process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099';
for (const [device, width] of [['pc', 1440], ['sp', 390]] as const) {
  test(`toc saved settings comparison / ${device}`, async ({ page }, testInfo) => {
    await page.setViewportSize({ width, height: 950 });
    await page.goto(base + '/docs/research/2026-09-08-selection-catalog/');
    await page.locator('[data-face="article"]').click();
    await page.locator('#search').fill('目次設定比較');
    await page.locator(`[data-device="${device}"]`).click();
    await expect(page.locator('.tile-open')).toHaveCount(4);
    for (let i = 0; i < 2; i++) await page.locator('.compare-pick input').nth(i).check();
    await page.locator('#open-compare').click();
    await expect(page.locator('#compare .compare-grid>section')).toHaveCount(2);
    await expect(page.locator('#comparison-facts')).toContainText('サイト既定を継承');
    await expect(page.locator('#comparison-facts')).toContainText('投稿メタ wt_toc = box');
    await expect(page.locator('#comparison-facts')).toContainText('投稿メタ wt_toc = float');
    const first = page.locator('#compare .compare-grid>section').first();
    await expect(first.locator('img')).toHaveAttribute('src', new RegExp(`box-${device}\\.jpg$`));
    await expect.poll(() => first.locator('img').evaluate(e => (e as HTMLImageElement).naturalWidth)).toBeGreaterThan(0);
    await first.getByRole('button', { name: '保留', exact: true }).click();
    await first.locator('textarea').fill('章構成を先に見せるか、本文の横に置くかを比較する');
    await page.keyboard.press('Escape');
    await page.reload();
    await page.locator('.tile-open').first().click();
    await expect(page.locator('#detail textarea')).toHaveValue('章構成を先に見せるか、本文の横に置くかを比較する');
    await page.keyboard.press('Escape');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.screenshot({ path: testInfo.outputPath(`toc-catalog-${device}.png`), fullPage: true });
  });
}

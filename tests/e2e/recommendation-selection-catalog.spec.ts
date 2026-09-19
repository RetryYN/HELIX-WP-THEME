import { test, expect } from '@playwright/test';
const base = process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099';
for (const [device, width] of [['pc', 1440], ['sp', 390]] as const) {
  test(`recommendation layout comparison and saved choice / ${device}`, async ({ page }) => {
    await page.setViewportSize({ width, height: 950 });
    await page.goto(base + '/docs/research/2026-09-08-selection-catalog/');
    await page.locator('[data-face="article"]').click();
    await page.locator('#search').fill('記事一覧比較');
    await page.locator(`[data-device="${device}"]`).click();
    await expect(page.locator('.tile-open')).toHaveCount(2);
    for (let i = 0; i < 2; i++) await page.locator('.compare-pick input').nth(i).check();
    await page.locator('#open-compare').click();
    await expect(page.locator('#compare .compare-grid>section')).toHaveCount(2);
    const facts = page.locator('#comparison-facts');
    await expect(facts).toContainText('公開記事・新着順・3件（両型共通）');
    await expect(facts).toContainText('helix-wt/recommendation-cards');
    await expect(facts).toContainText('helix-wt/recommendation-list');
    const first = page.locator('#compare .compare-grid>section').first();
    await expect(first.locator('img')).toHaveAttribute('src', new RegExp(`cards-${device}\\.jpg$`));
    expect(await first.locator('img').evaluate(e => (e as HTMLImageElement).naturalWidth)).toBeGreaterThan(0);
    await first.getByRole('button', { name: '保留', exact: true }).click();
    await first.locator('textarea').fill('写真の有無と説明量を比べてから選ぶ');
    await page.keyboard.press('Escape');
    await page.reload();
    await expect(page.locator('#search')).toHaveValue('記事一覧比較');
    await page.locator('.tile-open').first().click();
    await expect(page.locator('#detail textarea')).toHaveValue('写真の有無と説明量を比べてから選ぶ');
    await expect(page.locator('#detail .selection-facts')).toContainText('helix-wt/recommendation-cards');
    await page.keyboard.press('Escape');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.screenshot({ path: `docs/research/2026-09-19-recommendation-layouts/catalog-${device}.png`, fullPage: true });
  });
}

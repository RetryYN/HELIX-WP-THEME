import { test, expect } from '@playwright/test';
const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
for (const width of [320, 390, 700, 1440]) {
  test(`variant labels remain readable without clipping at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 844 });
    await page.goto(url);
    await page.locator('[data-face="all"]').click();
    for (const query of ['', 'own_category_footer_layout']) {
      await page.locator('#search').fill(query);
      const labels = page.locator('.tile .variant');
      await expect(labels.first()).toBeVisible();
      const rows = await labels.evaluateAll(elements => elements.map(e => ({
        font: parseFloat(getComputedStyle(e).fontSize),
        lineHeight: parseFloat(getComputedStyle(e).lineHeight),
        clipped: e.scrollWidth > e.clientWidth + 1 || e.scrollHeight > e.clientHeight + 1,
        text: e.textContent,
      })));
      for (const row of rows) {
        expect(row.font).toBeGreaterThanOrEqual(12);
        expect(row.lineHeight).toBeGreaterThanOrEqual(19.2);
        expect(row.clipped).toBe(false);
        expect(row.text).toBeTruthy();
      }
      if (query) await expect(labels.first()).toContainText(query);
    }
  });
}

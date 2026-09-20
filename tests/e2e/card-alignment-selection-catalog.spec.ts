import { test, expect } from '@playwright/test';
const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
for (const width of [320, 390, 768, 1440]) {
  test(`mixed card actions share a consistent lower edge at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 844 });
    await page.goto(url);
    await page.locator('[data-face="all"]').click();
    await expect(page.locator('.tile')).toHaveCount(36);
    await page.locator('.tile img').first().evaluate((image: HTMLImageElement) => image.decode());
    const rows = await page.locator('.tile').evaluateAll(elements => elements.slice(0, 2).map(e => {
      const rect = e.getBoundingClientRect();
      const footer = e.querySelector('.tile-footer')!.getBoundingClientRect();
      const badge = e.querySelector('.decision-badge')!.getBoundingClientRect();
      return { top: rect.top, bottom: rect.bottom, footerTop: footer.top, badgeBottom: badge.bottom, overflow: e.scrollWidth > e.clientWidth };
    }));
    for (const row of rows) {
      expect(row.overflow).toBe(false);
      expect(row.bottom - row.badgeBottom).toBeLessThan(20);
    }
    if (width > 360) {
      expect(rows[0].top).toBeCloseTo(rows[1].top, 0);
      expect(rows[0].footerTop).toBeCloseTo(rows[1].footerTop, 0);
      expect(rows[0].badgeBottom).toBeCloseTo(rows[1].badgeBottom, 0);
    }
    const first = page.locator('.compare-pick input').first();
    await first.check();
    await expect(first).toBeChecked();
    await expect(page.locator('#compare-bar')).toBeVisible();
  });
}

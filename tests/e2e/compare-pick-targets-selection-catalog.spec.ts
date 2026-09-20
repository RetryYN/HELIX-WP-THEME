import { test, expect } from '@playwright/test';
const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
for (const width of [320, 390, 768, 1440]) {
  test(`comparison targets remain distinct and operable at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 844 });
    await page.goto(url);
    for (const collection of ['common', 'all']) {
      await page.locator(`[data-face="${collection}"]`).click();
      const labels = page.locator('.tile-footer .compare-pick');
      await expect(labels).toHaveCount(36);
      const measurements = await labels.evaluateAll(elements => elements.map(e => {
        const r = e.getBoundingClientRect();
        const footer = e.parentElement!;
        const purpose = footer.querySelector('.purpose-tag')!.getBoundingClientRect();
        return { width: r.width, height: r.height, overlaps: purpose.right > r.left + 1, overflow: footer.scrollWidth > footer.clientWidth };
      }));
      for (const row of measurements) {
        expect(row.width).toBeGreaterThanOrEqual(96);
        expect(row.height).toBeGreaterThanOrEqual(44);
        expect(row.overlaps).toBe(false);
        expect(row.overflow).toBe(false);
      }
      const label = labels.first();
      const checkbox = label.locator('input');
      await label.click({ position: { x: 4, y: 22 } });
      await expect(checkbox).toBeChecked();
      await checkbox.focus();
      await page.keyboard.press('Space');
      await expect(checkbox).not.toBeChecked();
    }
  });
}

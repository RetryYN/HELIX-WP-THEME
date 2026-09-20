import { test, expect } from '@playwright/test';
const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;

for (const width of [320, 390, 700, 1440]) {
  test(`image controls keep three readable touch targets at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 640 });
    await page.goto(url);
    await page.locator('.tile-open').first().click();
    for (const dialog of ['#detail', '#compare']) {
      if (dialog === '#compare') {
        await page.locator('#detail .close').click();
        await page.locator('.compare-pick input').nth(0).check();
        await page.locator('.compare-pick input').nth(1).check();
        await page.locator('#open-compare').click();
      }
      const buttons = page.locator(`${dialog} .image-controls button`);
      await expect(buttons).toHaveCount(3);
      const boxes = await buttons.evaluateAll(elements => elements.map(e => ({
        top: e.getBoundingClientRect().top,
        height: e.getBoundingClientRect().height,
        width: e.getBoundingClientRect().width,
        font: parseFloat(getComputedStyle(e).fontSize),
        overflow: e.scrollWidth > e.clientWidth,
      })));
      for (const box of boxes) {
        expect(box.height).toBeGreaterThanOrEqual(44);
        expect(box.width).toBeGreaterThanOrEqual(44);
        expect(box.overflow).toBe(false);
        expect(box.top).toBeCloseTo(boxes[0].top, 0);
        if (width <= 700) expect(box.font).toBeGreaterThanOrEqual(12);
      }
      await buttons.nth(2).click();
      await expect(buttons.nth(2)).toHaveAttribute('aria-pressed', 'true');
      await expect(page.locator(`${dialog} .image-viewer`).first()).toHaveAttribute('data-mode', 'native');
      expect(await page.locator(dialog).evaluate(e => e.scrollWidth > e.clientWidth)).toBe(false);
    }
  });
}

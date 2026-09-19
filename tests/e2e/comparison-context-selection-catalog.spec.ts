import { test, expect } from '@playwright/test';

const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;

for (const width of [390, 768, 1440]) {
  test(`comparison keeps candidate and criterion headers visible after scrolling at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 844 });
    await page.goto(url);
    await page.locator('.compare-pick input').first().waitFor();
    for (let i = 0; i < 3; i++) await page.locator('.compare-pick input').nth(i).check();
    await page.locator('#open-compare').click();
    if (width <= 700) await page.locator('.comparison-summary summary').click();
    const region = page.locator('#comparison-facts');
    await region.evaluate(element => {
      element.scrollTop = element.scrollHeight;
      element.scrollLeft = element.scrollWidth;
    });
    const positions = await region.evaluate(element => {
      const bounds = element.getBoundingClientRect();
      const heading = element.querySelector('thead th')!.getBoundingClientRect();
      const captionHeight = element.querySelector('caption')!.getBoundingClientRect().height;
      const rowHeading = element.querySelector('tbody th')!.getBoundingClientRect();
      return { scrollTop: element.scrollTop, top: heading.top - bounds.top, captionHeight, left: rowHeading.left - bounds.left };
    });
    expect(positions.scrollTop).toBeGreaterThan(0);
    expect(positions.top).toBeGreaterThanOrEqual(0);
    expect(positions.top).toBeLessThanOrEqual(positions.captionHeight + 1);
    expect(positions.left).toBeGreaterThanOrEqual(0);
    expect(positions.left).toBeLessThan(3);
    await expect(region.locator('thead th').first()).toHaveAttribute('scope', 'col');
    await expect(region.locator('tbody th').first()).toHaveAttribute('scope', 'row');
  });
}

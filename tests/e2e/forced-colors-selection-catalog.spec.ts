import { test, expect } from '@playwright/test';
const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
for (const width of [390, 1440]) for (const forcedColors of ['active', 'none'] as const) for (const colorScheme of ['light', 'dark'] as const) {
  test(`catalog selection remains visible: ${width}px forced colors ${forcedColors} ${colorScheme}`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.emulateMedia({ forcedColors, colorScheme });
    await page.goto(url);
    const expected = forcedColors === 'active' ? 'underline' : 'none';
    await page.locator('#tab-requirements').click();
    await expect(page.locator('#tab-requirements')).toHaveCSS('text-decoration-line', expected);
    await page.locator('#tab-gallery').click();
    await expect(page.locator('#tab-requirements')).toHaveCSS('text-decoration-line', 'none');
    for (const face of ['common', 'all']) {
      await page.locator(`[data-face="${face}"]`).click();
      await expect(page.locator('nav [aria-current=true]')).toHaveCSS('text-decoration-line', expected);
      await expect(page.locator('nav [aria-current=false]').first()).toHaveCSS('text-decoration-line', 'none');
    }
    await expect(page.locator('.workspace-tabs [aria-selected=true]')).toHaveCSS('text-decoration-line', expected);
    for (const device of ['sp', 'pc']) {
      await page.locator(`[data-device="${device}"]`).click();
      await expect(page.locator('.device [aria-pressed=true]')).toHaveCSS('text-decoration-line', expected);
      await expect(page.locator('.device [aria-pressed=false]')).toHaveCSS('text-decoration-line', 'none');
    }
    await page.locator('.tile-open').first().click();
    for (const mode of ['width', 'native', 'overview']) {
      await page.locator(`#detail [data-image-mode="${mode}"]`).click();
      await expect(page.locator('#detail .image-controls [aria-pressed=true]')).toHaveCSS('text-decoration-line', expected);
      await expect(page.locator('#detail .image-controls [aria-pressed=false]').first()).toHaveCSS('text-decoration-line', 'none');
    }
    await page.locator('#detail .close').click();
    await page.locator('.compare-pick input').nth(0).check();
    await page.locator('.compare-pick input').nth(1).check();
    await page.locator('#open-compare').click();
    if (width === 390) {
      await page.locator('.compare-switcher button').nth(1).click();
      const selected = page.locator('.compare-switcher [aria-pressed=true]');
      await expect(selected).toHaveCSS('text-decoration-line', expected);
      await expect(page.locator('.compare-switcher [aria-pressed=false]')).toHaveCSS('text-decoration-line', 'none');
      await page.keyboard.press('Tab');
      await page.keyboard.press('Shift+Tab');
      await expect(selected).toBeFocused();
      await expect(selected).toHaveCSS('outline-style', 'solid');
      await expect(selected).toHaveCSS('text-decoration-line', expected);
    }
    await page.locator('#compare .close').click();
    await page.locator('[data-face=home]').click();
    await page.locator('#home-finished-only').click();
    await expect(page.locator('#home-finished-only')).toHaveCSS('text-decoration-line', expected);
    await page.locator('#home-with-parts').click();
    await expect(page.locator('#home-finished-only')).toHaveCSS('text-decoration-line', 'none');
  });
}

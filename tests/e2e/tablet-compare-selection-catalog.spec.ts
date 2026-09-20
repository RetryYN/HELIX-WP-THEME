import { test, expect } from '@playwright/test';

const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
for (const width of [390, 700, 701, 768, 1000, 1001, 1440]) for (const count of [2, 3]) {
  test(`${width}pxで${count}候補を読める幅で切り替える`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto(url);
    for (let i = 0; i < count; i++) await page.locator('.compare-pick input').nth(i).check();
    await page.locator('#open-compare').click();
    const columns = page.locator('#compare .compare-grid>section');
    await expect(columns).toHaveCount(count);
    await expect(page.locator('#comparison-facts thead th')).toHaveCount(count + 1);
    const single = width <= 1000;
    await expect(page.locator('#compare .compare-grid>section:visible')).toHaveCount(single ? 1 : count);
    if (single) {
      for (let i = 0; i < count; i++) {
        const button = page.locator('.compare-switcher button').nth(i);
        await button.focus();
        await page.keyboard.press('Enter');
        await expect(button).toHaveAttribute('aria-pressed', 'true');
        await expect(columns.nth(i)).toBeVisible();
        const geometry = await columns.nth(i).evaluate(element => ({
          width: element.getBoundingClientRect().width,
          available: element.parentElement!.getBoundingClientRect().width,
          overflow: element.scrollWidth - element.clientWidth,
        }));
        expect(geometry.width).toBeGreaterThanOrEqual(geometry.available - 1);
        expect(geometry.overflow).toBeLessThanOrEqual(1);
      }
    } else await expect(page.locator('.compare-switcher')).toBeHidden();
    expect(await page.locator('#compare').evaluate(e => e.scrollWidth - e.clientWidth)).toBeLessThanOrEqual(1);
  });
}

test('中間幅でメモと画像モードを保持し、広い画面へ戻す', async ({ page }) => {
  await page.setViewportSize({ width: 768, height: 900 });
  await page.goto(url);
  for (let i = 0; i < 3; i++) await page.locator('.compare-pick input').nth(i).check();
  await page.locator('#open-compare').click();
  const columns = page.locator('#compare .compare-grid>section');
  for (let i = 0; i < 3; i++) {
    await page.locator('.compare-switcher button').nth(i).click();
    await columns.nth(i).locator('textarea').fill(`候補${i + 1}の確認メモ`);
  }
  await page.locator('#compare .image-controls button').nth(1).click();
  await expect(page.locator('#compare .image-viewer[data-mode=width]')).toHaveCount(3);
  await page.setViewportSize({ width: 1440, height: 900 });
  await expect(page.locator('#compare .compare-grid>section:visible')).toHaveCount(3);
  for (let i = 0; i < 3; i++) await expect(columns.nth(i).locator('textarea')).toHaveValue(`候補${i + 1}の確認メモ`);
  await page.setViewportSize({ width: 768, height: 900 });
  await expect(columns.nth(2)).toBeVisible();
  await expect(page.locator('#compare .compare-grid>section:visible')).toHaveCount(1);
});

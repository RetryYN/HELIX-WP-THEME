import { test, expect } from '@playwright/test';
const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
for (const [width, height] of [[844, 320], [844, 390], [390, 844], [1024, 600], [1440, 900]]) for (const [index, mode] of ['overview', 'width', 'native'].entries()) {
  test(`${width}×${height} ${mode} の詳細画像を末尾まで確認できる`, async ({ page }) => {
    await page.setViewportSize({ width, height });
    await page.goto(url);
    await page.locator('[data-face=lp]').click();
    await page.locator('#search').fill('standard');
    await page.locator('.tile-open').click();
    await page.locator('#detail .image-controls button').nth(index).click();
    const dialog = page.locator('#detail'), viewer = dialog.locator('.image-viewer');
    await expect(viewer).toHaveAttribute('data-mode', mode);
    await viewer.locator('img').evaluate((image: HTMLImageElement) => image.decode());
    if (width > 700) await dialog.evaluate(element => { element.scrollTop = 500; });
    else await viewer.scrollIntoViewIfNeeded();
    const measure = () => viewer.evaluate(element => {
      const box = element.getBoundingClientRect(), dialog = element.closest('dialog')!;
      const frame = dialog.getBoundingClientRect(), header = dialog.querySelector('.dialog-head')!.getBoundingClientRect();
      const image = element.querySelector('img')!.getBoundingClientRect();
      return { top: box.top, bottom: box.bottom, height: box.height, frameBottom: frame.bottom, headerBottom: header.bottom, imageBottom: image.bottom, scrollTop: element.scrollTop, scrollHeight: element.scrollHeight, clientHeight: element.clientHeight };
    });
    if (width > 700) {
      const geometry = await measure();
      expect(geometry.top).toBeGreaterThanOrEqual(geometry.headerBottom);
      expect(geometry.bottom).toBeLessThanOrEqual(geometry.frameBottom - 1);
      expect(geometry.height).toBeGreaterThan(100);
      if (mode === 'overview') expect(geometry.imageBottom).toBeLessThanOrEqual(geometry.bottom);
      if (height >= 600) expect(geometry.height).toBeCloseTo(height * .6, 0);
    }
    if (mode !== 'overview') {
      await viewer.evaluate(element => { element.scrollTop = element.scrollHeight; });
      const end = await measure();
      expect(end.scrollTop).toBeGreaterThan(0);
      expect(end.scrollHeight - end.scrollTop - end.clientHeight).toBeLessThanOrEqual(1);
      expect(end.imageBottom).toBeLessThanOrEqual(end.bottom);
      if (width > 700) expect(end.imageBottom).toBeLessThan(end.frameBottom);
    }
    await dialog.getByRole('button', { name: '詳細を閉じる' }).click();
    await expect(dialog).not.toBeVisible();
  });
}

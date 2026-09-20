import { test, expect } from '@playwright/test';

const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
for (const width of [320, 390, 420, 421, 768, 1440]) {
  test(`PoC count badges remain distinct and readable at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto(url);
    await page.locator('#tab-requirements').click();
    const colors: string[] = [];
    for (const [id, text] of [['WT-TR-CORE-01', 'PoC確認 2/2条件'], ['WT-FR-TPL-01', 'PoC確認 1/3条件'], ['WT-NFR-GATE-01', 'PoC確認 0/2条件']]) {
      await page.locator('#req-search').fill(id);
      const badge = page.locator('.req-row > summary .status-label');
      await expect(badge).toHaveText(text);
      const metrics = await badge.evaluate(e => {
        const s = getComputedStyle(e), r = e.getBoundingClientRect();
        const id = e.parentElement!.querySelector('strong')!.getBoundingClientRect();
        const luminance = (color: string) => {
          const c = color.match(/\d+/g)!.slice(0, 3).map(Number).map(v => {
            const n = v / 255; return n <= .04045 ? n / 12.92 : ((n + .055) / 1.055) ** 2.4;
          });
          return c[0] * .2126 + c[1] * .7152 + c[2] * .0722;
        };
        const fg = luminance(s.color), bg = luminance(s.backgroundColor);
        return { background: s.backgroundColor, contrast: (Math.max(fg, bg) + .05) / (Math.min(fg, bg) + .05), font: parseFloat(s.fontSize), left: r.left, right: r.right, top: r.top, idBottom: id.bottom, idLeft: id.left, idRight: id.right };
      });
      colors.push(metrics.background);
      expect(metrics.contrast).toBeGreaterThanOrEqual(4.5);
      expect(metrics.font).toBeGreaterThanOrEqual(12);
      expect(metrics.left).toBeGreaterThanOrEqual(0);
      expect(metrics.right).toBeLessThanOrEqual(width);
      if (width <= 420) {
        expect(metrics.top).toBeGreaterThanOrEqual(metrics.idBottom);
        expect(Math.abs(metrics.left - metrics.idLeft)).toBeLessThan(4);
      } else {
        expect(metrics.top).toBeLessThan(metrics.idBottom);
        expect(metrics.left).toBeGreaterThanOrEqual(metrics.idRight);
      }
      const summary = page.locator('.req-row > summary');
      await summary.focus(); await page.keyboard.press('Enter');
      await expect(page.locator('.req-row')).toHaveAttribute('open', '');
      await page.keyboard.press('Enter');
      await expect(page.locator('.req-row')).not.toHaveAttribute('open', '');
    }
    expect(new Set(colors).size).toBe(3);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  });
}

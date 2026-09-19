import { test, expect } from '@playwright/test';

const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;

for (const width of [1440, 390]) {
  test(`filter context, individual removal and resume at ${width}px`, async ({ page }, testInfo) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto(url);
    await page.locator('[data-face=all]').click();
    await page.locator('#search').fill('料金');
    await page.locator('#decision').selectOption('unreviewed');
    await expect(page.locator('#filter-chips button')).toHaveCount(2);
    await expect(page.locator('#count')).toHaveText('3候補 / PC');
    await page.reload();
    await expect(page.locator('[data-filter=decision]')).toContainText('選択メモ: 未選択');
    await expect(page.locator('[data-filter=search]')).toContainText('検索: 料金');
    await page.locator('.results-head').scrollIntoViewIfNeeded();
    await page.screenshot({ path: testInfo.outputPath(`filter-context-${width}.png`) });
    await page.locator('.compare-pick input').first().check();
    await page.locator('[data-filter=decision]').focus();
    await page.keyboard.press('Enter');
    await expect(page.locator('#decision')).toHaveValue('');
    await expect(page.locator('[data-filter=search]')).toBeFocused();
    await expect(page.locator('#search')).toHaveValue('料金');
    await expect(page.locator('#compare-count')).toHaveText('1候補を選択中');
    await page.keyboard.press('Enter');
    await expect(page.locator('#active-filters')).toBeHidden();
    await expect(page.locator('#search')).toBeFocused();
    await expect(page.locator('#count')).toHaveText('660候補 / PC');
  });

  test(`zero results, long text and reset preserve decisions at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto(url);
    await page.locator('.tile-open').first().click();
    await page.locator('#detail').getByRole('button', { name: '採用候補', exact: true }).click();
    await page.locator('#detail textarea').fill('検討を続ける');
    await page.keyboard.press('Escape');
    const saved = await page.evaluate(() => localStorage.getItem('helix-selection-memos.v1'));
    await page.locator('.compare-pick input').first().check();
    await page.locator('[data-device=sp]').click();
    await page.locator('#purpose').selectOption({ index: 1 });
    await page.locator('#search').fill('<img src=x onerror=alert(1)>'.repeat(8));
    await expect(page.locator('#empty')).toBeVisible();
    await expect(page.locator('[data-filter=purpose]')).toBeVisible();
    await expect(page.locator('#filter-chips img')).toHaveCount(0);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.locator('#clear-filters').click();
    await expect(page.locator('#active-filters')).toBeHidden();
    await expect(page.locator('#purpose')).toHaveValue('');
    await expect(page.locator('#count')).toHaveText('660候補 / SP');
    await expect(page.locator('#search')).toBeFocused();
    await expect(page.locator('#compare-count')).toHaveText('1候補を選択中');
    expect(await page.evaluate(() => localStorage.getItem('helix-selection-memos.v1'))).toBe(saved);
  });
}

test('related requirement scope is visible and individually removable', async ({ page }) => {
  await page.goto(url);
  await page.locator('#tab-requirements').click();
  await page.locator('#req-search').fill('WT-FR-FORM-01');
  await page.locator('.req-row > summary').click();
  await page.locator('.req-row button').click();
  await expect(page.locator('[data-filter=linked]')).toContainText('要求に関連する候補');
  await page.locator('[data-filter=linked]').click();
  await expect(page.locator('#count')).toHaveText('660候補 / PC');
  await expect(page.locator('#search')).toBeFocused();
});

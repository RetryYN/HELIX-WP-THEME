import { test, expect } from '@playwright/test';

const base = process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099';
const url = `${base}/docs/research/2026-09-08-selection-catalog/`;

for (const width of [1440, 390, 320]) {
  test(`詳細内で絞り込み順に判断を続け、メモと操作位置を保つ ${width}`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto(url);
    await page.locator('#decision').selectOption('unreviewed');
    const first = page.locator('.tile-open').nth(0), second = page.locator('.tile-open').nth(1);
    const firstId = await first.getAttribute('data-entry-id');
    const secondId = await second.getAttribute('data-entry-id');
    await first.click();
    const dialog = page.locator('#detail');
    await expect(dialog.locator('[data-step=previous]')).toBeDisabled();
    await dialog.getByRole('button', { name: '採用候補', exact: true }).click();
    await dialog.locator('textarea').fill('一覧を戻っても残す理由');
    await dialog.locator('[data-step=next]').focus();
    await page.keyboard.press('Enter');
    await expect(dialog.locator('.detail-position')).toContainText('2 /');
    await expect(dialog.locator('textarea')).toHaveValue('');
    await expect(dialog.locator('[data-step=next]')).toBeFocused();
    await dialog.locator('[data-step=previous]').click();
    await expect(dialog.locator('textarea')).toHaveValue('一覧を戻っても残す理由');
    await expect(dialog.locator('.current-decision')).toHaveText('採用候補');
    await dialog.locator('[data-step=next]').click();
    expect(await dialog.evaluate(d => d.scrollWidth <= d.clientWidth)).toBe(true);
    expect(await dialog.locator('.detail-sequence button').evaluateAll(buttons => buttons.every(b => b.getBoundingClientRect().height >= 44))).toBe(true);
    await page.keyboard.press('Escape');
    await expect(page.locator(`.tile-open[data-entry-id="${secondId}"]`)).toBeFocused();
    await expect(page.locator(`.tile-open[data-entry-id="${firstId}"]`)).toHaveCount(0);
  });
}

test('一覧の末尾で次へ進まない', async ({ page }) => {
  await page.goto(url);
  await page.locator('#faces button').filter({ hasText: /^404$/ }).click();
  const count = await page.locator('.tile-open').count();
  await page.locator('.tile-open').last().click();
  await expect(page.locator('#detail [data-step=next]')).toBeDisabled();
  await expect(page.locator('#detail .detail-position')).toHaveText(`${count} / ${count}候補`);
});

test('初期36件より先へ進み、閉じた候補のカードへ戻る', async ({ page }) => {
  await page.goto(url);
  await page.locator('.tile-open').last().click();
  await page.locator('#detail [data-step=next]').click();
  await expect(page.locator('#detail .detail-position')).toContainText('37 /');
  await page.locator('#detail').evaluate(d => { d.scrollTop = 500; });
  expect(await page.locator('#detail').evaluate(d => d.querySelector('.image-viewer')!.getBoundingClientRect().top >= d.querySelector('.dialog-head')!.getBoundingClientRect().bottom)).toBe(true);
  await page.keyboard.press('Escape');
  await expect(page.locator('.tile-open')).toHaveCount(37);
  await expect(page.locator('.tile-open').last()).toBeFocused();
});

test('候補が1件の場合は前後とも無効', async ({ page }) => {
  await page.route('**/catalog-data.json', async route => {
    const response = await route.fetch();
    const data = await response.json();
    data.entries = data.entries.filter((entry: { group: string }) => entry.group === '共通設定・部品').slice(0, 1);
    await route.fulfill({ json: data });
  });
  await page.goto(url);
  await page.locator('.tile-open').click();
  await expect(page.locator('#detail [data-step=previous]')).toBeDisabled();
  await expect(page.locator('#detail [data-step=next]')).toBeDisabled();
  await expect(page.locator('#detail .detail-position')).toHaveText('1 / 1候補');
});

test('JS無効時の代替索引はそのまま使える', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto(url);
  await expect(page.getByRole('link', { name: '撮影索引' })).toBeVisible();
  await expect(page.locator('.detail-sequence')).toHaveCount(0);
  await context.close();
});

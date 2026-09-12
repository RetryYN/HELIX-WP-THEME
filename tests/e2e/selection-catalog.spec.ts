import { test, expect } from '@playwright/test';

// Serve the repository root locally; this suite does not contact WordPress.
const url = `${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
test.beforeEach(async ({ page }) => {
  await page.goto(url);
  await expect(page.locator('#total')).toHaveText('613');
});

test('search, comparison limit, PC/SP and requirement discovery', async ({ page }) => {
  await expect(page.locator('#collection-title')).toHaveText('共通設定・部品');
  await page.locator('[data-face="all"]').click();
  await page.locator('#search').fill('nonexistent-candidate');
  await expect(page.locator('#empty')).toBeVisible();
  await page.locator('#search').fill('');
  for (let i = 0; i < 4; i++) await page.locator('.compare-pick input').nth(i).click();
  await expect(page.locator('.compare-pick input:checked')).toHaveCount(3);
  await expect(page.locator('#notice')).toContainText('3候補まで');
  await page.locator('#open-compare').click();
  await expect(page.locator('#compare .compare-grid>section')).toHaveCount(3);
  await page.keyboard.press('Escape');
  await page.locator('[data-device="sp"]').click();
  await expect(page.locator('#count')).toContainText('SP');
  await page.locator('#tab-requirements').click();
  await expect(page.locator('.req-row')).toHaveCount(133);
  await page.locator('#req-search').fill('WT-FR-FORM-01');
  await expect(page.locator('.req-row')).toHaveCount(1);
  await page.locator('.req-row > summary').click();
  await expect(page.locator('.req-row')).toContainText('WT-AC-');
  await page.locator('.req-row button').click();
  await expect(page.locator('#collection-title')).toHaveText('要求に関連する候補');
});

test('memo survives reload and export/import; invalid input does not change it', async ({ page }) => {
  await page.locator('.tile-open').first().click();
  await page.locator('#detail').getByRole('button', { name: '採用候補', exact: true }).click();
  await page.locator('#detail textarea').fill('余白と見出しを再確認');
  await page.keyboard.press('Escape');
  await page.reload();
  await page.locator('.tile-open').first().click();
  await expect(page.locator('#detail textarea')).toHaveValue('余白と見出しを再確認');
  await expect(page.locator('#detail').getByRole('button', { name: '採用候補', exact: true })).toHaveAttribute('aria-pressed', 'true');
  await page.keyboard.press('Escape');
  const download = page.waitForEvent('download'); await page.locator('#export').click();
  const exported = await download; const file = await exported.path();
  expect(file).toBeTruthy();
  await page.evaluate(() => localStorage.clear()); await page.reload();
  await page.locator('#file').setInputFiles(file!);
  await expect(page.locator('.decision-badge').first()).toHaveText('採用候補');
  const before = await page.evaluate(() => localStorage.getItem('helix-selection-memos.v1'));
  await page.locator('#file').setInputFiles({ name: 'invalid.json', mimeType: 'application/json', buffer: Buffer.from('{"schema":"helix-selection-memos.v1","memos":{"unknown":{"status":"adopt","note":"bad"}}}') });
  await expect(page.locator('#notice')).toContainText('読込できません');
  expect(await page.evaluate(() => localStorage.getItem('helix-selection-memos.v1'))).toBe(before);
});

test('mobile fits viewport and tabs support keyboard; images load', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: 375, height: 812 });
  await page.locator('[data-device="sp"]').click();
  await expect(page.locator('.preview img').first()).toBeVisible();
  await expect.poll(() => page.locator('.preview img').first().evaluate((img: HTMLImageElement) => img.naturalWidth)).toBeGreaterThan(0);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.locator('#tab-gallery').focus(); await page.keyboard.press('ArrowRight');
  await expect(page.locator('#tab-requirements')).toBeFocused();
  await expect(page.locator('#requirements-panel')).toBeVisible();
  await page.keyboard.press('Home');
  await expect(page.locator('#tab-gallery')).toBeFocused();
  await page.screenshot({ path: testInfo.outputPath('mobile.png'), fullPage: false });
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.locator('[data-device="pc"]').click();
  await page.screenshot({ path: testInfo.outputPath('desktop.png'), fullPage: false });
});


test('acceptance ID, remaining text and evidence status filters preserve scope and recover from zero', async ({ page }) => {
  await page.locator('#tab-requirements').click();
  await expect(page.locator('#requirements-count')).toContainText('133要求 / 294受入条件');
  await page.locator('#req-search').fill('WT-AC-INTERVIEW-01C');
  await expect(page.locator('.req-row')).toHaveCount(1);
  await expect(page.locator('.acceptance-row')).toHaveCount(1);
  await expect(page.locator('.acceptance-row')).toHaveAttribute('open', '');
  await expect(page.locator('.acceptance-row')).toContainText('確認取り消し');
  await page.locator('#evidence-state').selectOption('missing');
  await expect(page.locator('#requirements-empty')).toBeVisible();
  await page.locator('#reset-requirements').click();
  await expect(page.locator('#req-search')).toBeFocused();
  await expect(page.locator('.req-row')).toHaveCount(133);
  await page.locator('#evidence-state').selectOption('partial');
  expect(await page.locator('.acceptance-row').count()).toBeGreaterThan(0);
  await expect(page.locator('.acceptance-row:not([data-evidence-state="partial"])')).toHaveCount(0);
  await page.locator('#req-search').fill('___no_matching_remaining___');
  await expect(page.locator('#requirements-empty')).toBeVisible();
  await page.locator('#reset-requirements').click();
  await page.locator('#req-search').fill('専用');
  expect(await page.locator('.acceptance-row').count()).toBeGreaterThan(0);
  await page.setViewportSize({ width: 375, height: 812 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});

test('site guide collection exposes eleven paired pages and the live pricing route', async ({ page }) => {
  await page.locator('[data-face="site"]').click();
  await expect(page.locator('.tile-open')).toHaveCount(11);
  await page.locator('#search').fill('site-pricing');
  await expect(page.locator('.tile-open')).toHaveCount(1);
  await page.locator('[data-device="sp"]').click();
  await page.locator('.tile-open').click();
  await expect(page.locator('#detail .large-preview img')).toHaveAttribute('src', /site-pages\/pricing-sp\.jpg$/);
  await expect(page.locator('#detail').getByRole('link', { name: 'ローカルの実機で操作する ↗' })).toHaveAttribute('href', 'http://127.0.0.1:8098/site-pricing/');
  await expect(page.locator('#detail')).toContainText('WT-FR-PAGE-01');
});

test('quality comparison opens from catalog with paired evidence and a return path', async ({ page }) => {
  await page.locator('.quality-links summary').click();
  await page.getByRole('link', { name: '常設案内 →', exact: true }).click();
  await expect(page.getByRole('heading', { level: 1 })).toContainText('押せる領域を広げる');
  await expect(page.locator('figure')).toHaveCount(8);
  await expect(page.locator('table')).toContainText('370');
  for (const image of await page.locator('img').all()) {
    await image.scrollIntoViewIfNeeded();
    await expect.poll(() => image.evaluate((e: HTMLImageElement) => e.complete && e.naturalWidth > 0)).toBe(true);
  }
  await page.setViewportSize({ width: 375, height: 812 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  for (const name of ['変更前の測定', '変更後の測定']) {
    const href = await page.getByRole('link', { name, exact: true }).getAttribute('href');
    const response = await page.request.get(new URL(href!, page.url()).href);
    expect(response.ok()).toBe(true);
    expect((await response.json()).completed).toBe(true);
  }
  await page.getByRole('link', { name: '← 選択カタログへ' }).click();
  await expect(page.locator('.tile-open').first()).toBeVisible();
});


test('independent faces expose shared, own and off comparisons', async ({ page }) => {
  await page.locator('[data-face="inheritance"]').click();
  await expect(page.locator('.tile-open')).toHaveCount(18);
  await page.locator('#search').fill('chrome-content-content_learning');
  await expect(page.locator('.tile-open')).toHaveCount(3);
  await page.locator('[data-device="sp"]').click();
  await page.locator('.tile-open').first().click();
  await expect(page.locator('#detail')).toContainText('WT-FR-PARTS-03');
  await expect(page.locator('#detail')).toContainText('WT-FR-LEARN-01');
  await expect(page.locator('#detail').getByRole('link', { name: 'ローカルの実機で操作する ↗' })).toHaveAttribute('href', /content_chrome/);
});


test('navigation comparison exposes paired images and scoped verification', async ({ page }) => {
  await page.locator('.quality-links summary').click();
  await page.getByRole('link', { name: '共通ナビ →', exact: true }).click();
  await expect(page.getByRole('heading', { level: 1 })).toContainText('共通ヘッダーへ');
  await expect(page.locator('figure')).toHaveCount(8);
  for (const image of await page.locator('img').all()) {
    await image.scrollIntoViewIfNeeded();
    await expect.poll(() => image.evaluate((e: HTMLImageElement) => e.complete && e.naturalWidth > 0)).toBe(true);
  }
  await page.setViewportSize({ width: 375, height: 812 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.getByRole('link', { name: '変更後の測定', exact: true }).click();
  const result = await (await page.request.get(page.url())).json();
  expect(result.completed).toBe(true);
  expect(result.rows).toHaveLength(62);
  expect(result.rows.every((row: { pass: boolean }) => row.pass)).toBe(true);
});


test('site search states expose scoped evidence and a live query', async ({ page }) => {
  await page.locator('[data-face="search"]').click();
  await expect(page.locator('.tile-open')).toHaveCount(3);
  await page.locator('.tile-open').first().click();
  await expect(page.locator('#detail')).toContainText('全権限行列');
  await expect(page.locator('#detail')).toContainText('WT-FR-SEARCH-01');
  await expect(page.locator('#detail img').first()).toHaveAttribute('src', /ja-(results|empty)-/);
  await expect(page.locator('#detail').getByRole('link', { name: 'ローカルの実機で操作する ↗' })).toHaveAttribute('href', /\?s=/);
});


test('search start comparison retains before and after evidence', async ({ page }) => {
  await page.locator('.quality-links summary').click();
  await page.getByRole('link', { name: '検索開始画面 →', exact: true }).click();
  await expect(page.getByRole('heading', { level: 1 })).toContainText('次の操作を伝える');
  await expect(page.locator('figure')).toHaveCount(4);
  for (const image of await page.locator('img').all()) {
    await image.scrollIntoViewIfNeeded();
    await expect.poll(() => image.evaluate((e: HTMLImageElement) => e.complete && e.naturalWidth > 0)).toBe(true);
  }
  await page.setViewportSize({ width: 375, height: 812 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.getByRole('link', { name: '← 選択カタログへ' }).click();
  await expect(page.locator('[data-face="search"]')).toBeVisible();
});

test('comparison, collection filters and requirement context resume after reload', async ({ page }) => {
  for (let i = 0; i < 3; i++) await page.locator('.compare-pick input').nth(i).check();
  const picks = await page.locator('#compare-picks button').allTextContents();
  await page.locator('[data-face="article"]').click();
  await page.locator('[data-device="sp"]').click();
  await page.locator('#search').fill('検討再開の検索語');
  const purpose = await page.locator('#purpose option').nth(1).getAttribute('value');
  await page.locator('#purpose').selectOption(purpose!);
  await page.locator('#decision').selectOption('hold');
  await page.locator('#tab-requirements').click();
  await page.locator('#req-search').fill('WT-AC-SEARCH-01B');
  await page.locator('#evidence-state').selectOption('partial');
  await page.reload();
  await expect(page.locator('#tab-requirements')).toHaveAttribute('aria-selected', 'true');
  await expect(page.locator('#req-search')).toHaveValue('WT-AC-SEARCH-01B');
  await expect(page.locator('#evidence-state')).toHaveValue('partial');
  await expect(page.locator('#compare-picks button')).toHaveText(picks);
  await page.locator('#open-compare').click();
  await expect(page.locator('#compare .compare-grid>section')).toHaveCount(3);
  await page.keyboard.press('Escape');
  await expect(page.locator('#open-compare')).toBeFocused();
  await page.locator('#tab-gallery').click();
  await expect(page.locator('#collection-title')).toHaveText('記事');
  await expect(page.locator('[data-device="sp"]')).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('#search')).toHaveValue('検討再開の検索語');
  await expect(page.locator('#purpose')).toHaveValue(purpose!);
  await expect(page.locator('#decision')).toHaveValue('hold');
  await page.locator('#compare-picks button').nth(1).click();
  await expect(page.locator('#compare-picks button')).toHaveText([picks[0], picks[2]]);
  await page.locator('#reset-gallery').click();
  await expect(page.locator('#search')).toBeFocused();
  await expect(page.locator('#count')).toHaveText('613候補 / SP');
  await expect(page.locator('#compare-picks button')).toHaveCount(2);
  await page.reload();
  await expect(page.locator('#compare-picks button')).toHaveCount(2);
  await page.locator('#clear-compare').click();
  await page.reload();
  await expect(page.locator('#compare-bar')).toBeHidden();
});

test('decision notes are searchable and closing the editor restores a useful focus target', async ({ page }) => {
  const id = await page.locator('.tile-open').first().getAttribute('data-entry-id');
  await page.locator('.tile-open').first().click();
  await page.locator('#detail textarea').fill('余白を広くしたい');
  await page.locator('#detail').getByRole('button', { name: '採用候補', exact: true }).click();
  await page.keyboard.press('Escape');
  await expect(page.locator('.tile-open').first()).toBeFocused();
  await page.locator('#search').fill('余白を広くしたい');
  await expect(page.locator('.tile-open')).toHaveCount(1);
  await expect(page.locator('.tile-open')).toHaveAttribute('data-entry-id', id!);
  await page.reload();
  await expect(page.locator('.tile-open')).toHaveCount(1);
  await page.locator('.tile-open').click();
  await page.locator('#detail textarea').fill('文字の大きさを再確認');
  await page.locator('#detail .close').click();
  await expect(page.locator('#empty')).toBeVisible();
  await expect(page.locator('#search')).toBeFocused();
  await page.locator('#reset-gallery').click();
  await page.locator('#search').fill('文字の大きさを再確認');
  await expect(page.locator('.tile-open')).toHaveCount(1);
});

test('invalid or obsolete workspace values cannot erase saved decisions or exceed the comparison limit', async ({ page }) => {
  const ids = await page.locator('.tile-open').evaluateAll(nodes => nodes.slice(0, 4).map(n => (n as HTMLElement).dataset.entryId!));
  await page.locator('.tile-open').first().click();
  await page.locator('#detail textarea').fill('この判断は保持する');
  await page.keyboard.press('Escape');
  // close handler renders and persists the workspace before focus returns.
  await expect(page.locator('.tile-open').first()).toBeFocused();
  const memo = await page.evaluate(() => localStorage.getItem('helix-selection-memos.v1'));
  await page.evaluate(ids => localStorage.setItem('helix-selection-workspace.v1', JSON.stringify({ schema: 'helix-selection-workspace.v1', face: 'removed-face', device: 'unknown', limit: -10, comparison: ['missing-id', ids[0], ids[0], ...ids], purpose: 'removed-purpose', decision: 'invalid', linkedIds: 'bad-type' })), ids);
  await page.reload();
  await expect(page.locator('#compare-picks button')).toHaveCount(3);
  await expect(page.locator('#collection-title')).toHaveText('共通設定・部品');
  await expect(page.locator('[data-device="pc"]')).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('.tile')).toHaveCount(36);
  await page.evaluate(() => localStorage.setItem('helix-selection-workspace.v1', '{invalid'));
  await page.reload();
  await expect(page.locator('#notice')).toContainText('前回の絞り込みを復元できません');
  expect(await page.evaluate(() => localStorage.getItem('helix-selection-memos.v1'))).toBe(memo);
  await page.locator('.tile-open').first().click();
  await expect(page.locator('#detail textarea')).toHaveValue('この判断は保持する');
});

test('comparison tray fits desktop and mobile and reserves room for the final candidate', async ({ page }, testInfo) => {
  for (let i = 0; i < 3; i++) await page.locator('.compare-pick input').nth(i).check();
  for (const [label, width] of [['desktop', 1440], ['mobile', 375]] as const) {
    await page.setViewportSize({ width, height: 900 });
    await expect.poll(() => page.evaluate(() => {
      const main = document.querySelector('main')!, bar = document.querySelector('#compare-bar')!;
      return parseFloat(getComputedStyle(main).paddingBottom) >= bar.getBoundingClientRect().height + 39;
    })).toBe(true);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    const chip = page.locator('#compare-picks button').last();
    await chip.focus();
    await expect(chip).toBeFocused();
    await expect.poll(() => chip.evaluate(e => {
      const r = e.getBoundingClientRect(), tray = e.parentElement!.getBoundingClientRect();
      return r.left >= tray.left && r.right <= tray.right;
    })).toBe(true);
    const rect = await chip.boundingBox();
    expect(rect!.height).toBeGreaterThanOrEqual(44);
    await page.screenshot({ path: testInfo.outputPath(`comparison-tray-${label}.png`), fullPage: false });
  }
});


test('related candidates and expanded results resume, and removing the last comparison keeps keyboard focus', async ({ page }) => {
  await page.locator('#tab-requirements').click();
  await page.locator('#req-search').fill('WT-FR-FORM-01');
  await page.locator('.req-row > summary').click();
  await page.locator('.req-row button').click();
  await page.locator('#more').click();
  await expect(page.locator('.tile')).toHaveCount(51);
  const ids = await page.locator('.tile-open').evaluateAll(nodes => nodes.map(n => (n as HTMLElement).dataset.entryId));
  await page.reload();
  await expect(page.locator('#collection-title')).toHaveText('要求に関連する候補');
  await expect(page.locator('.tile')).toHaveCount(51);
  expect(await page.locator('.tile-open').evaluateAll(nodes => nodes.map(n => (n as HTMLElement).dataset.entryId))).toEqual(ids);
  await page.locator('.compare-pick input').first().check();
  await page.locator('#tab-requirements').click();
  await page.locator('#compare-picks button').click();
  await expect(page.locator('#req-search')).toBeFocused();
  await page.locator('[data-face="article"]').click();
  await expect(page.locator('#tab-gallery')).toHaveAttribute('aria-selected', 'true');
  await page.locator('.compare-pick input').first().check();
  await page.locator('#clear-compare').click();
  await expect(page.locator('#search')).toBeFocused();
});

test('comparison images offer overview, fitted width and keyboard-scrollable native pixels', async ({ page }, testInfo) => {
  await page.locator('[data-face="paid"]').click();
  for (let i = 0; i < 2; i++) await page.locator('.compare-pick input').nth(i).check();
  await page.locator('#open-compare').click();
  for (const [device, width] of [['pc', 1440], ['sp', 375]] as const) {
    await page.setViewportSize({ width, height: 900 });
    const viewer = page.locator('#compare .image-viewer').first();
    const img = viewer.locator('img');
    await img.evaluate((n: HTMLImageElement) => n.decode());
    await page.locator('#compare').getByRole('button', { name: '原寸で読む', exact: true }).click();
    await expect.poll(() => img.evaluate((n: HTMLImageElement) => Math.abs(n.getBoundingClientRect().width - n.naturalWidth))).toBeLessThan(1);
    await viewer.focus();
    await page.keyboard.press('ArrowDown');
    await expect.poll(() => viewer.evaluate(n => n.scrollTop)).toBeGreaterThan(0);
    await page.keyboard.press('ArrowRight');
    await expect.poll(() => viewer.evaluate(n => n.scrollLeft)).toBeGreaterThan(0);
    await page.screenshot({ path: testInfo.outputPath(`image-native-${device}.png`), fullPage: false });
    await page.locator('#compare').getByRole('button', { name: '幅に合わせる', exact: true }).click();
    await expect.poll(() => viewer.evaluate(n => Math.abs(n.querySelector('img')!.getBoundingClientRect().width - (n.clientWidth - 24)))).toBeLessThan(2);
    await expect.poll(() => viewer.evaluate(n => n.scrollLeft + n.scrollTop)).toBe(0);
    await page.locator('#compare').getByRole('button', { name: '全体を見る', exact: true }).click();
    expect(await img.evaluate(n => n.getBoundingClientRect().height <= n.parentElement!.clientHeight)).toBe(true);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  }
  await page.locator('#compare').getByRole('button', { name: '原寸で読む', exact: true }).click();
  await page.keyboard.press('Escape');
  await page.reload();
  await page.locator('#open-compare').click();
  await expect(page.locator('#compare [data-image-mode="native"]')).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('#compare .image-viewer').first()).toHaveAttribute('data-mode', 'native');
});

test('detail image modes preserve decision editing and restore focus when closed', async ({ page }) => {
  await page.locator('[data-face="paid"]').click();
  await page.locator('.tile-open').first().click();
  await page.locator('#detail').getByRole('button', { name: '幅に合わせる', exact: true }).click();
  await expect(page.locator('#detail .image-viewer')).toHaveAttribute('data-mode', 'width');
  await page.locator('#detail textarea').fill('本文の行間を原寸で確認');
  await page.locator('#detail').getByRole('button', { name: '保留', exact: true }).click();
  await page.keyboard.press('Escape');
  await expect(page.locator('.tile-open').first()).toBeFocused();
  await page.locator('.tile-open').first().click();
  await expect(page.locator('#detail textarea')).toHaveValue('本文の行間を原寸で確認');
  await expect(page.locator('#detail [data-image-mode="width"]')).toHaveAttribute('aria-pressed', 'true');
});

test('event availability fixtures are discoverable with their limits', async ({ page }) => {
  await page.locator('[data-face="all"]').click();
  await page.locator('#search').fill('event-state-fixture');
  await expect(page.locator('.tile-open')).toHaveCount(4);
  await page.locator('.tile-open').first().click();
  await expect(page.locator('#detail')).toContainText('業務予約・定員更新・実送信は未実装');
  await page.keyboard.press('Escape');
  await page.locator('#tab-requirements').click();
  await page.locator('#req-search').fill('WT-AC-EVENT-01A');
  await expect(page.locator('.req-row')).toHaveCount(1);
  await page.locator('.req-row > summary').click();
  await expect(page.locator('.req-row')).toContainText('121');
});

test('footer saved and empty states expose paired images and remaining editor scope', async ({ page }) => {
  await page.locator('[data-face="all"]').click();
  await page.locator('#search').fill('footer-data-navigation');
  await expect(page.locator('.tile-open')).toHaveCount(2);
  for (let i = 0; i < 2; i++) await page.locator('.compare-pick input').nth(i).check();
  await page.locator('#open-compare').click();
  await expect(page.locator('#compare .compare-grid>section')).toHaveCount(2);
  await expect(page.locator('#compare')).toContainText('未登録の案内を省略');
  await expect(page.locator('#compare')).toContainText('登録済みの案内を表示');
  await expect.poll(() => page.locator('#compare img').evaluateAll(es => es.length === 2 && es.every(e => e.complete && e.naturalWidth > 0))).toBe(true);
  await page.keyboard.press('Escape');
  await page.locator('#tab-requirements').click();
  await page.locator('#req-search').fill('WT-AC-PARTS-01C');
  await expect(page.locator('.req-row')).toHaveCount(1);
  await page.locator('.req-row > summary').click();
  await expect(page.locator('.req-row')).toContainText('部分確認');
  await expect(page.locator('.req-row')).toContainText('メニュー選択');
});


test('compact catalog keeps narrow filters paired and selection states legible', async ({ page }) => {
  for (const width of [1440, 375, 320]) {
    await page.setViewportSize({ width, height: 900 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    expect((await page.locator('#gallery').boundingBox())!.y).toBeLessThan(700);
    if (width < 700) {
      const purpose = (await page.locator('#purpose').boundingBox())!;
      const decision = (await page.locator('#decision').boundingBox())!;
      expect(Math.abs(purpose.y - decision.y)).toBeLessThan(1);
      expect(purpose.x + purpose.width).toBeLessThan(decision.x);
    }
  }
  await page.locator('.quality-links summary').focus();
  await page.keyboard.press('Enter');
  await expect(page.getByRole('link', { name: '常設案内 →', exact: true })).toBeVisible();
  const colors = new Set<string>();
  for (const label of ['未選択', '採用候補', '保留', '除外']) {
    await page.locator('.tile-open').first().click();
    await page.locator('#detail').getByRole('button', { name: label, exact: true }).click();
    await page.keyboard.press('Escape');
    // closeイベントの再描画とフォーカス復帰後に、接続中のバッジを測る。
    await expect(page.locator('.tile-open').first()).toBeFocused();
    const badge = page.locator('.decision-badge').first();
    await expect(badge).toHaveText(label);
    const color = await badge.evaluate(e => e.isConnected ? getComputedStyle(e).backgroundColor : '');
    expect(color).not.toBe('');
    colors.add(color);
  }
  expect(colors.size).toBe(4);
});

test('decision facts stay aligned between cards and the comparison table', async ({ page }) => {
  await page.locator('.tile-open').first().click();
  await page.locator('#detail textarea').fill('見出し密度を優先する');
  await page.locator('#detail').getByRole('button', { name: '採用候補', exact: true }).click();
  await page.keyboard.press('Escape');
  await expect(page.locator('.tile-note').first()).toContainText('見出し密度を優先する');
  await expect(page.locator('.tile-facts').first()).toContainText(/PC|SP/);
  await expect(page.locator('.tile-facts').first()).toContainText(/関連 \d+要求/);

  await page.locator('.compare-pick input').nth(0).click();
  await page.locator('.compare-pick input').nth(1).click();
  await page.locator('#open-compare').click();
  const facts = page.locator('#comparison-facts');
  await expect(facts.getByRole('table')).toHaveAccessibleName(/候補の違い/);
  await expect(facts.getByRole('rowheader', { name: /選ぶ理由・確認事項/ })).toContainText('差分あり');
  await expect(facts).toContainText('見出し密度を優先する');
  await expect(page.locator('.comparison-evidence-note')).toContainText('受入条件の達成を示すものではありません');
  expect(await facts.locator('thead th').evaluateAll(cells => cells.every(cell => cell.getAttribute('scope') === 'col'))).toBe(true);
  expect(await facts.locator('tbody th').evaluateAll(cells => cells.every(cell => cell.getAttribute('scope') === 'row'))).toBe(true);

  await page.locator('#compare textarea').nth(1).fill('余白とCTAを優先する');
  await expect(facts).toContainText('余白とCTAを優先する');
  await page.locator('#compare section').nth(1).getByRole('button', { name: '保留', exact: true }).click();
  await expect(facts.getByRole('rowheader', { name: /選択メモ/ })).toContainText('差分あり');
  await expect(facts).toContainText('保留');

  await page.setViewportSize({ width: 375, height: 812 });
  await facts.focus();
  await expect(facts).toBeFocused();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});

test('mobile component selection uses full width and switches comparison without losing notes', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 900 });
  const first = page.locator('.component-tile').first();
  expect(await first.evaluate(e => e.getBoundingClientRect().width)).toBeGreaterThan(340);
  expect(await first.locator('.compare-pick').evaluate(e => e.getBoundingClientRect().bottom)).toBeLessThan(900);
  for (let i = 0; i < 2; i++) await page.locator('.compare-pick input').nth(i).check();
  await page.locator('#open-compare').click();
  await expect(page.locator('#compare .compare-grid>section').first()).toBeVisible();
  await expect(page.locator('#compare .compare-grid>section').nth(1)).toBeHidden();
  await page.locator('#compare textarea').first().fill('幅と余白を確認');
  await page.locator('.compare-switcher button').nth(1).click();
  await expect(page.locator('#compare .compare-grid>section').nth(1)).toBeVisible();
  await page.locator('.compare-switcher button').first().click();
  await expect(page.locator('#compare textarea').first()).toHaveValue('幅と余白を確認');
  await page.locator('.comparison-summary>summary').click();
  await expect(page.locator('#comparison-facts')).toContainText('幅と余白を確認');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});

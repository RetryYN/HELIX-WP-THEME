import { test, expect } from '@playwright/test';
import { copy, fixture, validateEvent, validateTrackingContract } from '../../docs/research/2026-09-20-lp-tracking-poc/model.mjs';
import { startProductServer } from '../../scripts/product-surfaces-server.mjs';

let service;
test.beforeAll(async () => { service = await startProductServer(); });
test.afterAll(async () => { await service.close(); });
const pageUrl = () => service.base + '/docs/research/2026-09-20-lp-tracking-poc/index.html';

test('AC-LP-02A declares form placement, LP variation, and versioned event contract', async ({ page }) => {
  validateTrackingContract();
  await page.goto(pageUrl());
  await expect(page.locator('[data-pattern="lp-form"]')).toBeVisible();
  await expect(page.locator('[data-pattern="lp-form"] form')).toHaveAttribute('data-lp-form', 'consultation-form');
  await expect(page.locator('.hero code').nth(1)).toHaveText('lp-lead-v2');
  const contract = await page.locator('body').evaluate(() => window.helixTracking.fixture.tracking);
  expect(contract).toEqual(fixture.tracking);
});

test('AC-LP-02A records view, scroll, CTA, and submit with required IDs', async ({ page }) => {
  const outbound = [];
  page.on('request', request => outbound.push(request.url()));
  await page.goto(pageUrl());
  await page.evaluate(() => { window.scrollTo(0, 500); window.dispatchEvent(new Event('scroll')); });
  await page.locator('[data-helix-cta]').click();
  await page.locator('[data-lp-form] input[name="name"]').fill('fixture');
  await page.locator('[data-lp-form] input[name="email"]').fill('fixture@example.invalid');
  await page.locator('[data-lp-form] button[type="submit"]').click();
  const events = await page.evaluate(() => window.helixDataLayer);
  expect(events.map(event => event.name)).toEqual(['lp_view', 'lp_scroll', 'lp_cta_click', 'lp_form_submit']);
  for (const row of events) {
    validateEvent(row);
    expect(row.version).toBe('wt-data-layer.v1');
    expect(row.lpId).toBe(fixture.lp.id);
    expect(row.goalCvId).toBe(fixture.lp.goalCvId);
    expect(row.variantId).toBe(fixture.lp.variantId);
  }
  expect(events.at(-1).formId).toBe('consultation-form');
  await expect(page.locator('#form-result')).toHaveText('送信イベントを記録しました。外部送信はありません。');
  expect(outbound.some(url => /https?:\/\//.test(url) && !url.startsWith(service.base))).toBe(false);
});

test('AC-LP-02A remains readable without JavaScript', async ({ browser }) => {
  for (const width of [1440, 390]) {
    const context = await browser.newContext({ viewport: { width, height: 844 }, javaScriptEnabled: false });
    const page = await context.newPage();
    await page.goto(pageUrl());
    await expect(page.locator('h1')).toHaveText(fixture.lp.title);
    await expect(page.locator('[data-lp-form] input[name="email"]')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    if (process.env.LP_TRACKING_CAPTURE_DIR) await page.screenshot({ path: process.env.LP_TRACKING_CAPTURE_DIR + (width === 1440 ? '/lp-tracking-pc.jpg' : '/lp-tracking-sp.jpg'), fullPage: true, type: 'jpeg', quality: 84 });
    await context.close();
  }
});

test('AC-LP-02B rejects missing IDs, incomplete events, and theme-side optimization', () => {
  const missingId = copy(fixture); missingId.lp.goalCvId = '';
  expect(() => validateTrackingContract(missingId)).toThrow('lp.goalCvId is required');
  const missingEvent = copy(fixture); missingEvent.tracking.events = ['lp_view'];
  expect(() => validateTrackingContract(missingEvent)).toThrow('tracking event set is incomplete');
  const optimization = copy(fixture); optimization.tracking.optimizationOwner = 'theme';
  expect(() => validateTrackingContract(optimization)).toThrow('optimization must remain outside the theme');
  expect(() => validateEvent({ name: 'lp_cta_click', eventId: 'fixture', version: fixture.tracking.version, lpId: fixture.lp.id, goalCvId: '', variantId: fixture.lp.variantId })).toThrow('event.goalCvId is required');
});

test('AC-LP-02B rejects an underspecified LP pattern inventory', () => {
  const candidate = copy(fixture); candidate.lp.patternIds = ['lp-form'];
  expect(() => validateTrackingContract(candidate)).toThrow('LP patterns are required');
});

test('AC-LP-02B rejects a form slot count other than one', () => {
  const candidate = copy(fixture); candidate.formSlots.push({ ...copy(fixture.formSlots[0]), id: 'alternate-form' });
  expect(() => validateTrackingContract(candidate)).toThrow('exactly one form slot is required');
});

test('AC-LP-02B rejects duplicate form slot identities', () => {
  const candidate = copy(fixture); candidate.formSlots.push(copy(fixture.formSlots[0]));
  expect(() => validateTrackingContract(candidate)).toThrow('duplicate form slot: consultation-form');
});

test('AC-LP-02B rejects a non-boolean form field requirement', () => {
  const candidate = copy(fixture); candidate.formSlots[0].fields[0].required = 'true';
  expect(() => validateTrackingContract(candidate)).toThrow('field.required must be boolean: name');
});

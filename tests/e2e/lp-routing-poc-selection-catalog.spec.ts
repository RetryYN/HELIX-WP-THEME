import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { manifest, records, fixedPage, restProjection, copy, validateFixture, validateRecord } from '../../docs/research/2026-09-20-lp-routing-poc/model.mjs';
import { startProductServer } from '../../scripts/product-surfaces-server.mjs';

let service;
test.beforeAll(async () => { service = await startProductServer(); });
test.afterAll(async () => { await service.close(); });
const pageUrl = () => `${service.base}/docs/research/2026-09-20-lp-routing-poc/index.html`;

test('AC-LP-01A manifest and REST expose flat LP routes and independent kinds', async ({ page }) => {
  await page.goto(pageUrl());
  const response = await page.request.get(`${service.base}/docs/research/2026-09-20-lp-routing-poc/rest.json`);
  expect(response.ok()).toBe(true);
  const rest = await response.json();
  expect(manifest.contentTypes.map(type => type.id)).toEqual(['wt_lp', 'wt_event', 'wt_comparison', 'wt_blp']);
  expect(rest.records).toEqual(restProjection());
  expect(rest.records.filter(record => record.type !== 'wt_blp').every(record => !record.slug.includes('/') && record.link.endsWith(`/${record.slug}/`))).toBe(true);
  expect(fixedPage.path).toBe('/company/about/');
  await expect(page.locator('h1')).toHaveText('LP・イベント・比較特設を分けて管理する');
  await expect(page.locator('[data-record="101"] code')).toHaveText('/start-editorial-session/');
});

test('AC-LP-01A remains usable without JavaScript at PC and SP widths', async ({ browser }) => {
  for (const width of [1440, 390]) {
    const context = await browser.newContext({ viewport: { width, height: 1000 }, javaScriptEnabled: false });
    const page = await context.newPage(); await page.goto(pageUrl());
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await expect(page.locator('[data-route="blp"]')).toHaveAttribute('href', '/guides/editorial-fit/');
    await expect(page.locator('[data-route="lp"]')).toHaveAttribute('href', '/start-editorial-session/');
    if (process.env.LP_ROUTING_CAPTURE_DIR) {
      fs.mkdirSync(process.env.LP_ROUTING_CAPTURE_DIR, { recursive: true });
      await page.screenshot({ path: path.join(process.env.LP_ROUTING_CAPTURE_DIR, width === 1440 ? 'lp-routing-pc.jpg' : 'lp-routing-sp.jpg'), fullPage: true, type: 'jpeg', quality: 84 });
    }
    await context.close();
  }
});

test('AC-LP-01B rejects hierarchical LP slugs and collapsing event kinds into LP', () => {
  const hierarchical = copy(records); hierarchical[0].slug = 'campaign/start'; hierarchical[0].path = '/campaign/start/';
  expect(() => validateFixture(hierarchical)).toThrow('hierarchical LP slug');
  const collapsed = copy(records); collapsed[1].type = 'wt_lp';
  expect(() => validateFixture(collapsed)).toThrow('event and comparison types are required');
  const wrongPage = copy(records); wrongPage[0].path = '/guides/start-editorial-session/';
  expect(() => validateRecord(wrongPage[0])).toThrow('LP-like path is not flat');
});

test('AC-LP-01C keeps BLP purpose and destination separate from LP and preserves independent kinds', async ({ page }) => {
  await page.goto(pageUrl());
  const blp = records.find(record => record.type === 'wt_blp');
  const lp = records.find(record => record.type === 'wt_lp');
  expect(blp.goalCvId).toBeNull(); expect(blp.sendsTo).toBe(lp.id);
  expect(manifest.contentTypes.find(type => type.id === 'wt_blp').purpose).toContain('判断材料');
  expect(manifest.contentTypes.find(type => type.id === 'wt_lp').purpose).toContain('CV');
  expect(new Set(records.filter(record => ['wt_event', 'wt_comparison'].includes(record.type)).map(record => record.type))).toEqual(new Set(['wt_event', 'wt_comparison']));
  await expect(page.locator('.journey')).toContainText('BLP');
});

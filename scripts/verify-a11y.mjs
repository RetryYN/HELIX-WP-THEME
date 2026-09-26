#!/usr/bin/env node
import AxeBuilder from '@axe-core/playwright';
import { chromium } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname } from 'node:path';
import { pathToFileURL } from 'node:url';

const baseUrl = (process.env.A11Y_BASE_URL || 'http://localhost:8086').replace(/\/$/, '');
const negativeControl = process.argv.includes('--negative-control');
const routes = negativeControl
  ? ['/']
  : ['/', '/a11y-page/', '/a11y-fixture-article/', '/category/a11y-fixture/', '/?s=Accessibility'];
const viewports = negativeControl
  ? [{ name: 'desktop', width: 1440, height: 900 }]
  : [
      { name: 'desktop', width: 1440, height: 900 },
      { name: 'mobile', width: 390, height: 844 },
    ];
const wcagTags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];
const findings = [];
const scanResults = [];
let expectedNegativeSeen = false;

export function assertNoAxeViolations(violations) {
  if (violations.length > 0) {
    throw new Error(`axe found ${violations.length} violation(s): ${violations.map(({ id }) => id).join(', ')}`);
  }
}

async function main() {
const browser = await chromium.launch({ headless: true });
try {
  for (const viewport of viewports) {
    const context = await browser.newContext({ viewport: { width: viewport.width, height: viewport.height } });
    const page = await context.newPage();
    for (const route of routes) {
      const response = await page.goto(new URL(route, `${baseUrl}/`).toString(), { waitUntil: 'networkidle' });
      if (!response || response.status() !== 200) {
        throw new Error(`${route} returned HTTP ${response?.status() ?? 'no response'}`);
      }
      const activeTheme = await page.locator('body').evaluate((body) => body.classList.contains('wp-theme-helix-wt'));
      if (!activeTheme) throw new Error(`${route} did not render the helix-wt theme`);

      let mobileOverflow = null;
      if (negativeControl) {
        await page.evaluate(() => {
          const button = document.createElement('button');
          button.type = 'button';
          button.dataset.a11yNegativeControl = 'true';
          button.style.cssText = 'position:fixed;left:8px;top:8px;z-index:2147483647';
          document.body.append(button);
        });
      } else if (viewport.name === 'mobile') {
        const dimensions = await page.evaluate(() => ({
          viewport: document.documentElement.clientWidth,
          content: document.documentElement.scrollWidth,
        }));
        if (dimensions.content > dimensions.viewport) {
          throw new Error(`${route} overflows at mobile width: ${dimensions.content}px > ${dimensions.viewport}px`);
        }
        mobileOverflow = dimensions.content > dimensions.viewport;
      }

      const results = await new AxeBuilder({ page }).withTags(wcagTags).analyze();
      const violations = results.violations.map(({ id, impact, help, nodes }) => ({
        id,
        impact,
        help,
        nodes: nodes.map(({ target }) => target),
      }));
      scanResults.push({ viewport: viewport.name, route, httpStatus: response.status(),
        horizontalOverflow: mobileOverflow, violations });
      if (negativeControl) {
        expectedNegativeSeen ||= results.violations.some((violation) =>
          violation.id === 'button-name' && violation.nodes.some((node) =>
            node.target.some((target) => target.includes('[data-a11y-negative-control="true"]'))));
      } else {
        try {
          assertNoAxeViolations(results.violations);
        } catch (error) {
          findings.push({ viewport: viewport.name, route, violationIds: results.violations.map(({ id }) => id) });
        }
      }
      console.log(JSON.stringify({ viewport: viewport.name, route, violations: results.violations.length,
        violationIds: results.violations.map(({ id }) => id) }));
    }
    await context.close();
  }
} finally {
  await browser.close();
}

if (negativeControl) {
  if (!expectedNegativeSeen || scanResults.length !== 1 || scanResults[0].violations.length !== 1) {
    console.error('Negative control failed: expected exactly one axe violation for the injected unnamed button.');
    process.exitCode = 1;
  } else {
    console.log('Negative control detected the injected button-name violation.');
    process.exitCode = 2;
  }
} else if (findings.length > 0) {
  console.error(JSON.stringify({ failedRoutes: findings }));
  process.exitCode = 1;
}

const reportPath = process.env.A11Y_REPORT_PATH;
if (reportPath) {
  await mkdir(dirname(reportPath), { recursive: true });
  await writeFile(reportPath, `${JSON.stringify({
    schema: 'wt-axe-gate-report.v1',
    mode: negativeControl ? 'negative-control' : 'positive-gate',
    baseUrl,
    tags: wcagTags,
    completed: negativeControl ? expectedNegativeSeen : findings.length === 0,
    scanResults,
  }, null, 2)}\n`);
}
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  main().catch((error) => {
    console.error(error.stack || error.message || String(error));
    process.exitCode = 1;
  });
}

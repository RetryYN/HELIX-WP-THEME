#!/usr/bin/env node
import AxeBuilder from '@axe-core/playwright';
import { chromium } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname } from 'node:path';
import { pathToFileURL } from 'node:url';

const baseUrl = (process.env.A11Y_BASE_URL || 'http://localhost:8086').replace(/\/$/, '');
const negativeControl = process.argv.includes('--negative-control');
const routes = negativeControl
  ? [{ path: '/', expectedStatus: 200 }]
  : [
      { path: '/', expectedStatus: 200 },
      { path: '/a11y-page/', expectedStatus: 200 },
      { path: '/a11y-fixture-article/', expectedStatus: 200 },
      { path: '/category/a11y-fixture/', expectedStatus: 200 },
      { path: '/?s=Accessibility', expectedStatus: 200 },
      { path: '/a11y-missing-route/', expectedStatus: 404 },
      { path: '/a11y-fixture-lp/', expectedStatus: 200, expectedTemplate: 'page-lp' },
      { path: '/a11y-fixture-event/', expectedStatus: 200, expectedTemplate: 'page-event' },
      { path: '/a11y-fixture-zone-catalog/', expectedStatus: 200, expectedTemplate: 'page-zone-catalog' },
      { path: '/a11y-fixture-canvas/', expectedStatus: 200, expectedTemplate: 'page-canvas' },
    ];
const viewports = negativeControl
  ? [{ name: 'desktop', width: 1440, height: 900 }]
  : [
      { name: 'desktop', width: 1440, height: 900 },
      { name: 'mobile', width: 390, height: 844 },
    ];
const wcagTags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];

export function assertNoAxeViolations(violations) {
  if (violations.length > 0) {
    throw new Error(`axe found ${violations.length} violation(s): ${violations.map(({ id }) => id).join(', ')}`);
  }
}

function messageOf(error) {
  return error instanceof Error ? error.message : String(error);
}

export async function runA11yScan({ browserType = chromium, Axe = AxeBuilder, scanRoutes = routes,
  scanViewports = viewports, isNegativeControl = negativeControl, urlBase = baseUrl,
  log = console.log } = {}) {
  const scanResults = [];
  const errors = [];
  const findings = [];
  let expectedNegativeSeen = false;
  let browser;

  try {
    browser = await browserType.launch({ headless: true });
    for (const viewport of scanViewports) {
      let context;
      try {
        context = await browser.newContext({ viewport: { width: viewport.width, height: viewport.height } });
      } catch (error) {
        errors.push({ viewport: viewport.name, stage: 'context', message: messageOf(error) });
        continue;
      }

      try {
        let page;
        try {
          page = await context.newPage();
        } catch (error) {
          errors.push({ viewport: viewport.name, stage: 'page', message: messageOf(error) });
          continue;
        }

        for (const route of scanRoutes) {
          const row = {
            viewport: viewport.name,
            route: route.path,
            expectedStatus: route.expectedStatus,
            httpStatus: null,
            themeActive: null,
            horizontalOverflow: null,
            violations: [],
            errors: [],
          };
          scanResults.push(row);

          let response;
          try {
            response = await page.goto(new URL(route.path, `${urlBase}/`).toString(), {
              waitUntil: 'networkidle', timeout: 30000,
            });
            row.httpStatus = response?.status() ?? null;
            if (row.httpStatus !== route.expectedStatus) {
              row.errors.push(`expected HTTP ${route.expectedStatus}, got ${row.httpStatus ?? 'no response'}`);
            }
          } catch (error) {
            row.errors.push(`navigation: ${messageOf(error)}`);
          }

          if (response) {
            try {
              const bodyState = await page.locator('body').evaluate((body) => ({
                themeActive: body.classList.contains('wp-theme-helix-wt'),
                className: body.className,
              }));
              row.themeActive = bodyState.themeActive;
              if (!row.themeActive) row.errors.push('helix-wt theme is not active');
              if (route.expectedTemplate && !bodyState.className.split(/\s+/).includes(`page-template-${route.expectedTemplate}`)) {
                row.errors.push(`expected WordPress page template ${route.expectedTemplate} is not active`);
              }
            } catch (error) {
              row.errors.push(`theme check: ${messageOf(error)}`);
            }

            if (isNegativeControl) {
              try {
                await page.evaluate(() => {
                  const button = document.createElement('button');
                  button.type = 'button';
                  button.dataset.a11yNegativeControl = 'true';
                  button.style.cssText = 'position:fixed;left:8px;top:8px;z-index:2147483647';
                  document.body.append(button);
                });
              } catch (error) {
                row.errors.push(`negative-control injection: ${messageOf(error)}`);
              }
            } else if (viewport.name === 'mobile') {
              try {
                const dimensions = await page.evaluate(() => ({
                  viewport: document.documentElement.clientWidth,
                  content: document.documentElement.scrollWidth,
                }));
                row.horizontalOverflow = dimensions.content > dimensions.viewport;
                if (row.horizontalOverflow) {
                  row.errors.push(`horizontal overflow: ${dimensions.content}px > ${dimensions.viewport}px`);
                }
              } catch (error) {
                row.errors.push(`mobile reflow check: ${messageOf(error)}`);
              }
            }

            try {
              const results = await new Axe({ page }).withTags(wcagTags).analyze();
              row.violations = results.violations.map(({ id, impact, help, nodes }) => ({
                id, impact, help, nodes: nodes.map(({ target }) => target),
              }));
              if (isNegativeControl) {
                expectedNegativeSeen ||= results.violations.some((violation) =>
                  violation.id === 'button-name' && violation.nodes.some((node) =>
                    node.target.some((target) => target.includes('[data-a11y-negative-control="true"]'))));
              } else if (row.violations.length > 0) {
                findings.push({ viewport: viewport.name, route: route.path, violationIds: row.violations.map(({ id }) => id) });
              }
            } catch (error) {
              row.errors.push(`axe scan: ${messageOf(error)}`);
            }
          }

          for (const message of row.errors) errors.push({ viewport: viewport.name, route: route.path, stage: 'scan', message });
          log(JSON.stringify({ viewport: viewport.name, route: route.path, httpStatus: row.httpStatus,
            horizontalOverflow: row.horizontalOverflow, violations: row.violations.length,
            violationIds: row.violations.map(({ id }) => id), errors: row.errors }));
        }
      } finally {
        try {
          await context.close();
        } catch (error) {
          errors.push({ viewport: viewport.name, stage: 'context-close', message: messageOf(error) });
        }
      }
    }
  } catch (error) {
    errors.push({ stage: 'browser', message: messageOf(error) });
  } finally {
    if (browser) {
      try {
        await browser.close();
      } catch (error) {
        errors.push({ stage: 'browser-close', message: messageOf(error) });
      }
    }
  }

  const clean = errors.length === 0 && findings.length === 0;
  const negativePass = isNegativeControl && clean && expectedNegativeSeen
    && scanResults.length === 1 && scanResults[0].violations.length === 1;
  const negativeFail = isNegativeControl && !negativePass;
  return {
    report: {
      schema: 'wt-axe-gate-report.v1',
      mode: isNegativeControl ? 'negative-control' : 'positive-gate',
      baseUrl: urlBase,
      tags: wcagTags,
      completed: isNegativeControl ? negativePass : clean,
      expectedNegativeSeen,
      scanResults,
      errors,
      failedRoutes: findings,
    },
    exitCode: isNegativeControl ? (negativePass ? 2 : 1) : (clean ? 0 : 1),
  };
}

async function main() {
  const result = await runA11yScan();
  const reportPath = process.env.A11Y_REPORT_PATH;
  if (reportPath) await writeA11yReport(reportPath, result.report);
  if (result.exitCode === 2) console.log('Negative control detected the injected button-name violation.');
  if (result.exitCode === 1) console.error(JSON.stringify({ errors: result.report.errors, failedRoutes: result.report.failedRoutes }));
  process.exitCode = result.exitCode;
}

export async function writeA11yReport(reportPath, report) {
  await mkdir(dirname(reportPath), { recursive: true });
  await writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`);
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  main().catch((error) => {
    console.error(error.stack || error.message || String(error));
    process.exitCode = 1;
  });
}

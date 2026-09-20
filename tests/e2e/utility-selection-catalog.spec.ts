import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
const url=`${process.env.CATALOG_BASE_URL||'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
for(const width of [1440,390])test(`utility candidates expose partial scope and compare at ${width}`,async({page})=>{
 await page.setViewportSize({width,height:900});await page.goto(url);await page.locator('[data-face=utility]').click();await expect(page.locator('.tile-open')).toHaveCount(3);await expect(page.locator('.tile-open')).toContainText(['予算の計算','準備のチェック','見出しの生成']);
 await page.locator('.tile-open').first().click();await expect(page.locator('#detail')).toContainText('4受入条件は部分確認');await expect(page.locator('#detail')).toContainText('WT-FR-UTILITY-01');await expect(page.locator('#detail')).toContainText('WordPress 7.2');await page.keyboard.press('Escape');
 for(let i=0;i<3;i++)await page.locator('.compare-pick input').nth(i).check();await page.locator('#open-compare').click();await expect(page.locator('#compare .compare-grid>section')).toHaveCount(3);await page.keyboard.press('Escape');await page.locator('[data-device=sp]').click();await expect(page.locator('.tile-open img').first()).toHaveAttribute('src',/utility-poc\/calculator-sp.jpg$/);
 await page.locator('#tab-requirements').click();await page.locator('#req-search').fill('WT-FR-UTILITY-01');await page.locator('.req-row>summary').click();await expect(page.locator('.acceptance-row')).toHaveCount(4);await expect(page.locator('.acceptance-row[data-evidence-state=partial]')).toHaveCount(4);
});

test('utility runtime matrix is exercised by the catalog CI entrypoint',async({},info)=>{
 test.setTimeout(120000);
 const report=JSON.parse(execFileSync(process.execPath,['node_modules/@playwright/test/cli.js','test','tests/e2e/utility-poc.spec.ts','--workers=1','--reporter=json','--output',info.outputPath('runtime')],{encoding:'utf8',maxBuffer:16*1024*1024}));
 expect(report.stats.expected).toBe(46);expect(report.stats.unexpected+report.stats.flaky+report.stats.skipped).toBe(0);
});

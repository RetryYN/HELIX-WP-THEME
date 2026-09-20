import {test,expect} from '@playwright/test';
const url=`${process.env.CATALOG_BASE_URL||'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`;
for(const width of [1440,390])test(`product surfaces expose two partial ACs at ${width}`,async({page})=>{
 await page.setViewportSize({width,height:900});await page.goto(url);await page.locator('[data-face=all]').click();await page.locator('#search').fill('販売面：');await expect(page.locator('.tile-open')).toHaveCount(5);
 await page.locator('.tile-open').first().click();await expect(page.locator('#detail')).toContainText('2受入条件は部分確認');await expect(page.locator('#detail')).toContainText('WT-FR-SELL-02');await expect(page.locator('#detail')).toContainText('merchant listing');await page.keyboard.press('Escape');
 for(let i=0;i<3;i++)await page.locator('.compare-pick input').nth(i).check();await page.locator('#open-compare').click();await expect(page.locator('#compare .compare-grid>section')).toHaveCount(3);await page.keyboard.press('Escape');await page.locator('[data-device=sp]').click();await expect(page.locator('.tile-open img').first()).toHaveAttribute('src',/product-surfaces-poc\/card-sp.jpg$/);
 await page.locator('#tab-requirements').click();await page.locator('#req-search').fill('WT-FR-SELL-02');await page.locator('.req-row>summary').click();await expect(page.locator('.acceptance-row')).toHaveCount(2);await expect(page.locator('.acceptance-row[data-evidence-state=partial]')).toHaveCount(2);
});

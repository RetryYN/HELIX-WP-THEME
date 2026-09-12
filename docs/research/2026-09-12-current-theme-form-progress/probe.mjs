import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';
import assert from 'node:assert/strict';

const mode = process.argv[2] || 'before';
const root = new URL('../../../', import.meta.url);
const out = new URL('./', import.meta.url);
const wp = code => JSON.parse(execFileSync('docker', ['exec', 'helix-content-wp', 'php', '-r', `require '/var/www/html/wp-load.php';${code}`], { encoding: 'utf8' }));
const slug = 'form-progress-visual-audit';
const fixture = wp(`if(get_page_by_path('${slug}',OBJECT,'page'))throw new Exception('collision');$id=wp_insert_post(['post_type'=>'page','post_name'=>'${slug}','post_title'=>'入力ステップの確認','post_status'=>'publish','post_content'=>'<!-- wp:helix-wt/form /-->'],true);if(is_wp_error($id))throw new Exception('create failed');update_post_meta($id,'_wp_page_template','page-canvas');echo wp_json_encode(['id'=>$id,'slug'=>'${slug}']);`);
const results = [];
const browser = await chromium.launch({ args: ['--no-sandbox'] });
try {
  for (const width of [390, 1440]) for (const noJs of [false, true]) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, javaScriptEnabled: !noJs });
    const response = await page.goto(`http://127.0.0.1:8098/${slug}/?wt=form_layout:steps,form_captcha:question,form_side:tel`, { waitUntil: noJs ? 'load' : 'networkidle' });
    const state = await page.evaluate(() => {
      const steps = [...document.querySelectorAll('.wt-form__steps li')];
      const stepBox = document.querySelector('.wt-form__steps')?.getBoundingClientRect();
      const markerStyle = getComputedStyle(document.querySelector('.wt-form__steps li.is-current'), '::before');
      const rgb = value => (value.match(/[\d.]+/g) || []).slice(0, 3).map(Number);
      const luminance = value => rgb(value).map(channel => channel / 255).map(channel => channel <= .04045 ? channel / 12.92 : ((channel + .055) / 1.055) ** 2.4).reduce((sum, channel, index) => sum + channel * [.2126, .7152, .0722][index], 0);
      const l1 = luminance(markerStyle.backgroundColor), l2 = luminance(markerStyle.color);
      const interactive = [...document.querySelectorAll('.wt-form button,.wt-form input:not([type=hidden]):not([type=checkbox]):not([type=radio]),.wt-form select,.wt-form textarea')].filter(el => {
        const box = el.getBoundingClientRect();
        return !el.closest('.wt-form__hp') && box.width > 0 && box.height > 0 && getComputedStyle(el).visibility !== 'hidden';
      });
      return {
        overflow: document.documentElement.scrollWidth > innerWidth,
        stepCount: steps.length,
        currentCount: steps.filter(el => el.classList.contains('is-current')).length,
        stepWidth: stepBox?.width ?? 0,
        formWidth: document.querySelector('.wt-form')?.getBoundingClientRect().width ?? 0,
        visibleFieldsets: [...document.querySelectorAll('.wt-form__step')].filter(el => !el.hidden).length,
        stepsLabel: document.querySelector('.wt-form__steps')?.getAttribute('aria-label') ?? '',
        markerContrast: (Math.max(l1, l2) + .05) / (Math.min(l1, l2) + .05),
        undersizedTargets: interactive.filter(el => el.getBoundingClientRect().height < 44).map(el => `${el.tagName.toLowerCase()}#${el.id}`),
      };
    });
    results.push({ width, noJs, http: response.status(), ...state });
    await page.locator('.wt-form').first().screenshot({ path: new URL(`${mode}-${width}-${noJs ? 'nojs' : 'js'}.png`, out).pathname });
    if (mode === 'after') {
      assert.equal(response.status(), 200);
      assert.equal(state.overflow, false);
      assert.equal(state.stepCount, 3);
      assert.equal(state.currentCount, 1);
      assert(state.stepWidth <= state.formWidth + 1);
      assert.equal(state.visibleFieldsets, noJs ? 3 : 1);
      assert(state.stepsLabel.length > 0);
      assert(state.markerContrast >= 4.5);
      assert.deepEqual(state.undersizedTargets, []);
    }
    await page.close();
  }
} finally {
  await browser.close();
  Object.assign(fixture, wp(`$p=get_post(${fixture.id});if(!$p||$p->post_name!=='${slug}')throw new Exception('identity mismatch');wp_delete_post(${fixture.id},true);echo wp_json_encode(['absent'=>get_post(${fixture.id})===null]);`));
}
const payload = { schema: 'wt-form-progress-visual-audit.v1', mode, fixture, results };
writeFileSync(new URL(`${mode}.json`, out), `${JSON.stringify(payload, null, 2)}\n`);
if (mode === 'after') {
  const source = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/theme.css';
  payload.sourceDigests = { [source]: createHash('sha256').update(readFileSync(new URL(source, root))).digest('hex') };
  payload.completed = fixture.absent === true && results.length === 4 && results.every(row => row.http === 200 && !row.overflow && row.stepCount === 3 && row.currentCount === 1 && row.stepWidth <= row.formWidth + 1 && row.visibleFieldsets === (row.noJs ? 3 : 1) && row.stepsLabel.length > 0 && row.markerContrast >= 4.5 && row.undersizedTargets.length === 0);
  writeFileSync(new URL('verify.json', out), `${JSON.stringify(payload, null, 2)}\n`);
  assert.equal(payload.completed, true);
}
console.log(mode, JSON.stringify(results));

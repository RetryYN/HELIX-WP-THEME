// Issue #388: WT-FR-EVENT-01 / WT-FR-FORM-01、WT-AC-EVENT-01A/B・WT-AC-FORM-01A/B
// の検証器7本を実ソースから駆動する復元回帰検査。テーマ描画の受入検査とは別。
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';
import { absentOption, option } from './helpers/fixture-lifecycle-mock.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const runner = path.join(root, 'tests/helpers/fixture-verifier-runner.mjs');
const scripts = ['event-boundaries', 'event-state', 'form-boundaries', 'form-flow', 'form-kinds', 'form-slots', 'form-steps'];
function run(name, config = {}) {
  const result = spawnSync(process.execPath, ['--experimental-vm-modules', runner,
    path.join(root, `scripts/verify-${name}.mjs`), JSON.stringify({ initial: option('0'), args: ['--baseline'], ...config })], {
    encoding: 'utf8', timeout: 15000,
  });
  assert.equal(result.status, 0, result.stderr);
  return JSON.parse(result.stdout);
}
const mutations = result => result.calls.filter(args => (args[0] === 'option' && args[1] !== 'get')
  || (args[0] === 'post' && !['list'].includes(args[1])) || (args[0] === 'eval' && args[1].includes('fixture-mode restore')));
const assertRestored = (result, initial = option('0')) => {
  assert.deepEqual(result.option, initial);
  assert.ok(result.stages.includes('restore'));
  assert.ok(result.reports[0].rows.some(row => row.name === 'fixture-mode-restored' && row.pass));
};

for (const name of scripts) {
  test(`${name}: backup failure releases its lock before launch or fixture mutation`, () => {
    for (const config of [{ failStage: 'snapshot', failCount: 1 }, { snapshotOutput: '' }]) {
      const result = run(name, config);
      assert.ok(result.error);
      assert.ok(result.stages.includes('snapshot'));
      assert.equal(mutations(result).length, 0);
      assert.deepEqual(result.browserCalls, []);
      assert.deepEqual(result.option, option('0'));
      assert.equal(result.lock, undefined);
    }
  });
  test(`${name}: an existing lifecycle lock prevents snapshot and temporary mode adoption`, () => {
    const result = run(name, { existingLock: 'another-verifier' });
    assert.equal(result.error, 'Fixture lifecycle lock unavailable');
    assert.equal(result.stages.includes('snapshot'), false);
    assert.equal(mutations(result).length, 0);
    assert.deepEqual(result.browserCalls, []);
    assert.deepEqual(result.option, option('0'));
    assert.equal(result.lock, 'another-verifier');
  });
  test(`${name}: a reserved fixture is preserved before any option mutation`, () => {
    const slug = name === 'event-boundaries' ? 'event-boundary-fixture' : name + '-fixture';
    const result = run(name, { existingPosts: { [slug]: 77 } });
    assert.equal(result.error, 'Reserved fixture exists');
    assert.equal(mutations(result).length, 0);
    assert.deepEqual(result.posts, [['77', { slug }]]);
    assert.deepEqual(result.option, option('0'));
  });
  test(`${name}: output initialization failure happens before mutations`, () => {
    const result = run(name, { mkdirFails: true });
    assert.equal(result.error, 'Injected output initialization failure');
    assert.equal(mutations(result).length, 0);
    assert.deepEqual(result.option, option('0'));
  });
  test(`${name}: launch failure independently restores and verifies the option`, () => {
    const result = run(name, { initial: absentOption, launchFails: true });
    assert.equal(result.error, 'Injected browser launch failure');
    assertRestored(result, absentOption);
    assert.deepEqual(result.browserCalls, ['launch']);
    assert.equal(result.reports[0].completed, false);
  });
  test(`${name}: body and browser close failures still remove owned fixtures and restore`, () => {
    const result = run(name, { bodyFails: true, closeFails: true });
    assert.equal(result.error, 'Injected browser body failure');
    assertRestored(result);
    assert.equal(result.posts.length, 0);
    assert.equal(result.exitCode, 1);
    assert.ok(result.reports[0].rows.some(row => row.name === 'browser-closed' && !row.pass));
  });
  test(`${name}: acknowledged and unacknowledged creation failures restore`, () => {
    for (const failStage of ['create', 'create-after']) {
      const result = run(name, { failStage, failCount: 1 });
      assert.match(result.error, /create.*failure/);
      assertRestored(result);
      assert.equal(result.posts.length, 0);
      assert.equal(result.reports[0].completed, false);
    }
  });
  test(`${name}: option initialization failure after its write restores the original state`, () => {
    const result = run(name, { failStage: 'enable-after', failCount: 1 });
    assert.equal(result.error, 'Injected enable-after failure');
    assertRestored(result);
    assert.equal(result.posts.length, 0);
    assert.equal(result.reports[0].completed, false);
  });
  test(`${name}: normal completion restores a non-default value and binds the helper digest`, () => {
    const initial = option('a:1:{s:3:"key";i:7;}', 'array');
    const result = run(name, { initial });
    assert.equal(result.error, undefined);
    assert.equal(result.reports[0].completed, true);
    assertRestored(result, initial);
    assert.equal(result.posts.length, 0);
    assert.ok(result.reports[0].sourceDigests['scripts/lib/fixture-lifecycle.mjs']);
    assert.ok(result.reports[0].rows.filter(row => row.cleanup).every(row => row.pass));
    assert.equal(result.lock, undefined);
  });
  test(`${name}: cleanup failure remains a failure after normal completion, including baseline`, () => {
    const result = run(name, { closeFails: true });
    assert.equal(result.error, undefined);
    assert.equal(result.reports[0].completed, true);
    assert.equal(result.exitCode, 1);
    assertRestored(result);
    assert.equal(result.posts.length, 0);
    assert.ok(result.reports[0].rows.some(row => row.name === 'browser-closed' && !row.pass));
  });
  test(`${name}: a cleanup lookup failure prevents deletion but cannot skip option restore`, () => {
    const result = run(name, { failStage: 'list-owned', failCount: 1 });
    assert.equal(result.exitCode, 1);
    assertRestored(result);
    assert.ok(result.posts.length > 0);
    assert.ok(result.reports[0].rows.some(row => row.name.endsWith('-locate') && !row.pass));
  });
  test(`${name}: delete, removal check, restore and recheck failures cannot pass baseline`, () => {
    const failures = [
      ['delete', 1], ['list-owned', 2], ['restore', 1], ['snapshot', 2],
    ];
    for (const [failStage, failCount] of failures) {
      const result = run(name, { failStage, failCount });
      assert.equal(result.error, undefined);
      assert.equal(result.reports[0].completed, true);
      assert.equal(result.exitCode, 1);
      assert.ok(result.reports[0].rows.some(row => row.cleanup && !row.pass));
      assert.ok(result.stages.includes('restore'));
      assert.equal(result.stages.filter(stage => stage === 'snapshot').length, 2);
      if (failStage !== 'restore') assert.deepEqual(result.option, option('0'));
      if (['restore', 'snapshot'].includes(failStage)) {
        assert.ok(result.lock);
        assert.ok(result.reports[0].rows.some(row => row.manualRecoveryRequired));
      } else assert.equal(result.lock, undefined);
    }
  });
  test(`${name}: report write failure occurs after cleanup and restoration`, () => {
    const result = run(name, { reportFails: true });
    assert.equal(result.error, 'Injected report write failure');
    assert.deepEqual(result.option, option('0'));
    assert.equal(result.posts.length, 0);
    assert.ok(result.stages.includes('restore'));
  });
}

for (const name of ['form-slots', 'form-steps']) {
  test(`${name}: existing thanks is preserved when body fails`, () => {
    const result = run(name, { existingPosts: { thanks: 77 }, bodyFails: true });
    assertRestored(result);
    assert.deepEqual(result.posts, [['77', { slug: 'thanks' }]]);
    assert.equal(result.calls.some(args => args[0] === 'post' && args[1] === 'delete' && args[2] === '77'), false);
  });
  test(`${name}: thanks deletion failure does not skip fixture deletion or restoration`, () => {
    const result = run(name, { failStage: 'delete', failCount: 1 });
    assert.equal(result.exitCode, 1);
    assertRestored(result);
    assert.equal(result.posts.length, 1);
    assert.equal(result.posts[0][1].slug, 'thanks');
    assert.ok(result.reports[0].rows.some(row => row.name === 'owned-fixture-removed' && row.pass));
  });
}

test('the standard content-lab test gate imports both fixture regression suites', () => {
  const source = fs.readFileSync(path.join(root, 'tests/content-lab-env.test.mjs'), 'utf8');
  assert.match(source, /import '\.\/fixture-lifecycle\.test\.mjs';/);
  assert.match(source, /import '\.\/fixture-verifiers\.test\.mjs';/);
});

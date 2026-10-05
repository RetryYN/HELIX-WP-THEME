// Issue #388: WT-FR-EVENT-01 / WT-FR-FORM-01、WT-AC-EVENT-01A/B・WT-AC-FORM-01A/B
// の検証器が環境を復元するための回帰検査。製品側の受入達成は主張しない。
import assert from 'node:assert/strict';
import test from 'node:test';
import { createFixtureLifecycle, snapshotFixtureMode } from '../scripts/lib/fixture-lifecycle.mjs';
import { absentOption, option, mockWp } from './helpers/fixture-lifecycle-mock.mjs';

const createArgs = slug => ['post', 'create', '--post_type=page', '--post_status=publish', '--post_name=' + slug, '--porcelain'];
const states = [
  ['absent', absentOption],
  ['string 0', option('0')],
  ['string 1', option('1')],
  ['empty string', option('')],
  ['other string', option('custom\n日本語')],
  ['serialized false', option('b:0;', 'boolean')],
  ['serialized integer', option('i:0;', 'integer')],
  ['serialized double', option('d:1.5;', 'double')],
  ['serialized null', option('N;', 'NULL')],
  ['serialized array', option('a:2:{s:3:"key";s:1:"0";i:0;i:7;}', 'array')],
  ['serialized object', option('O:8:"stdClass":1:{s:3:"key";s:1:"1";}', 'object')],
];

for (const [label, initial] of states) for (const abnormal of [false, true]) {
  test(`restores ${label}, including autoload, after ${abnormal ? 'body failure' : 'success'}`, async () => {
    const fake = mockWp(initial);
    const lifecycle = createFixtureLifecycle(fake.wp);
    const rows = [];
    const browser = { close: async () => {} };
    try {
      try {
        fake.wp(['option', 'update', 'wtcf_event_fixture_mode', '1']);
        lifecycle.createPost(createArgs('fixture'));
        if (abnormal) throw new Error('Body failed');
      } finally {
        assert.equal(await lifecycle.cleanup(browser, rows), true);
      }
    } catch (error) {
      assert.equal(abnormal, true);
      assert.equal(error.message, 'Body failed');
    }
    assert.deepEqual(fake.option, initial);
    assert.equal(fake.posts.size, 0);
    assert.ok(rows.every(row => row.pass));
    assert.equal(fake.stages.filter(stage => stage === 'snapshot').length, 2);
  });
}

test('backup errors and malformed responses fail closed without mutation', () => {
  const outputs = ['', 'null', '{}', 'false', '{"exists":false}',
    JSON.stringify({ ...absentOption, exists: 'false' }),
    JSON.stringify({ ...absentOption, valueBase64: '' }),
    JSON.stringify({ ...option('0'), type: 'unknown' }),
    JSON.stringify({ ...option('0'), valueBase64: '@@' }),
    JSON.stringify({ ...option('0'), autoloadBase64: null }),
    JSON.stringify({ ...option('0'), extra: true })];
  for (const snapshotOutput of outputs) {
    const fake = mockWp(option('0'), { snapshotOutput });
    assert.throws(() => createFixtureLifecycle(fake.wp));
    assert.deepEqual(fake.stages, ['snapshot']);
    assert.deepEqual(fake.option, option('0'));
  }
  const fake = mockWp(absentOption, { fail: stage => stage === 'snapshot' });
  assert.throws(() => createFixtureLifecycle(fake.wp), /snapshot failure/);
  assert.deepEqual(fake.stages, ['snapshot']);
  assert.deepEqual(snapshotFixtureMode(mockWp(absentOption).wp), absentOption);
});

test('an unacknowledged post creation is recovered by its owner marker', async () => {
  const fake = mockWp(option('0'), { fail: stage => stage === 'create-after' });
  const lifecycle = createFixtureLifecycle(fake.wp);
  fake.wp(['option', 'update', 'wtcf_event_fixture_mode', '1']);
  assert.throws(() => lifecycle.createPost(createArgs('fixture')), /create-after failure/);
  assert.equal(fake.posts.size, 1);
  const rows = [];
  assert.equal(await lifecycle.cleanup(undefined, rows), true);
  assert.equal(fake.posts.size, 0);
  assert.deepEqual(fake.option, option('0'));
});

for (const createOutput of ['', '0', 'NaN', '100 101', '9007199254740992']) {
  test(`invalid create output ${JSON.stringify(createOutput)} does not lose ownership`, async () => {
    const fake = mockWp(option('0'), { createOutput });
    const lifecycle = createFixtureLifecycle(fake.wp);
    assert.throws(() => lifecycle.createPost(createArgs('fixture')));
    assert.equal(await lifecycle.cleanup(undefined, []), true);
    assert.equal(fake.posts.size, 0);
    assert.deepEqual(fake.option, option('0'));
  });
}

const failures = [
  ['browser close', {}, true, 'browser-closed'],
  ['thanks delete', { fail: (stage, args) => stage === 'delete' && args[2] === '100' }, false, 'owned-thanks-delete'],
  ['fixture delete', { fail: (stage, args) => stage === 'delete' && args[2] === '101' }, false, 'owned-fixture-delete'],
  ['owner lookup', { fail: (stage, args, count) => stage === 'list-owned' && count === 1 }, false, 'owned-thanks-locate'],
  ['removal check', { fail: (stage, args, count) => stage === 'list-owned' && count === 2 }, false, 'owned-thanks-removed'],
  ['silent delete failure', { deleteNoop: true }, false, 'owned-thanks-removed'],
  ['option restore', { fail: stage => stage === 'restore' }, false, 'fixture-mode-restore'],
  ['restore acknowledgment', { restoreOutput: '' }, false, 'fixture-mode-restore'],
  ['silent option restore failure', { restoreNoop: true }, false, 'fixture-mode-restored'],
  ['restore recheck', { fail: (stage, args, count) => stage === 'snapshot' && count === 2 }, false, 'fixture-mode-restored'],
];
for (const [label, config, closeFails, failedRow] of failures) {
  test(`${label} is a failure and leaves independent cleanup running`, async () => {
    const previousExitCode = process.exitCode;
    try {
      const fake = mockWp(option('0'), config);
      const lifecycle = createFixtureLifecycle(fake.wp);
      fake.wp(['option', 'update', 'wtcf_event_fixture_mode', '1']);
      lifecycle.createPost(createArgs('thanks'), 'owned-thanks');
      lifecycle.createPost(createArgs('fixture'));
      const rows = [];
      const browser = { close: async () => { if (closeFails) throw new Error('Close failed'); } };
      assert.equal(await lifecycle.cleanup(browser, rows), false);
      assert.equal(process.exitCode, 1);
      assert.ok(rows.some(row => row.name === failedRow && !row.pass));
      assert.ok(fake.stages.includes('restore'));
      assert.equal(fake.stages.filter(stage => stage === 'snapshot').length, 2);
      assert.ok(rows.some(row => row.name === 'owned-fixture-locate'));
      if (!config.restoreNoop && label !== 'option restore') assert.deepEqual(fake.option, option('0'));
      if (label !== 'fixture delete' && label !== 'silent delete failure') assert.equal(fake.posts.has('101'), false);
    } finally {
      process.exitCode = previousExitCode;
    }
  });
}

for (const mismatch of ['owner changed', 'returned ID belongs to another post']) {
  test(`${mismatch} never deletes the unowned post`, async () => {
    const previousExitCode = process.exitCode;
    try {
      const fake = mockWp(option('0'), mismatch === 'owner changed' ? {} : {
        createOutput: '77', existingPosts: { reserved: 77 },
      });
      const lifecycle = createFixtureLifecycle(fake.wp);
      const id = lifecycle.createPost(createArgs('fixture'));
      if (mismatch === 'owner changed') fake.posts.get(String(id)).token = 'different-owner';
      const rows = [];
      assert.equal(await lifecycle.cleanup(undefined, rows), false);
      assert.equal(fake.posts.has(String(id)), true);
      assert.equal(fake.calls.some(args => args[0] === 'post' && args[1] === 'delete'), false);
      assert.ok(rows.some(row => row.name === 'owned-fixture-locate' && !row.pass));
      assert.deepEqual(fake.option, option('0'));
    } finally {
      process.exitCode = previousExitCode;
    }
  });
}

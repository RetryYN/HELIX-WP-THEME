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

test('backup errors and malformed responses release only the acquired lock without fixture mutation', () => {
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
    assert.deepEqual(fake.stages, ['lock-acquire', 'lock-acquire-after', 'snapshot', 'lock-release', 'lock-release-after']);
    assert.deepEqual(fake.option, option('0'));
    assert.equal(fake.lock, undefined);
  }
  const fake = mockWp(absentOption, { fail: stage => stage === 'snapshot' });
  assert.throws(() => createFixtureLifecycle(fake.wp), /snapshot failure/);
  assert.deepEqual(fake.stages, ['lock-acquire', 'lock-acquire-after', 'snapshot', 'lock-release', 'lock-release-after']);
  assert.equal(fake.lock, undefined);
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

test('a concurrent lifecycle cannot snapshot the enabled temporary value', async () => {
  const fake = mockWp(absentOption);
  const first = createFixtureLifecycle(fake.wp);
  const firstOwner = fake.lock;
  fake.wp(['option', 'update', 'wtcf_event_fixture_mode', '1']);
  assert.throws(() => createFixtureLifecycle(fake.wp), /lock unavailable/);
  assert.equal(fake.stages.filter(stage => stage === 'snapshot').length, 1);
  assert.equal(fake.lock, firstOwner);
  assert.equal(await first.cleanup(undefined, []), true);
  assert.deepEqual(fake.option, absentOption);
  assert.equal(fake.lock, undefined);
  const second = createFixtureLifecycle(fake.wp);
  fake.wp(['option', 'update', 'wtcf_event_fixture_mode', '1']);
  assert.equal(await second.cleanup(undefined, []), true);
  assert.deepEqual(fake.option, absentOption);
  assert.equal(fake.lock, undefined);
});

for (const config of [
  { fail: stage => stage === 'lock-acquire' },
  { fail: stage => stage === 'lock-acquire-after' },
  { acquireOutput: '' },
]) {
  test('failed or unacknowledged lock acquisition never reaches snapshot', () => {
    const fake = mockWp(option('0'), config);
    assert.throws(() => createFixtureLifecycle(fake.wp));
    assert.equal(fake.stages.includes('snapshot'), false);
    assert.equal(fake.lock, undefined);
    assert.deepEqual(fake.option, option('0'));
  });
}

test('backup failure with failed lock release requires manual recovery and blocks new snapshots', () => {
  const fake = mockWp(option('0'), { fail: stage => stage === 'snapshot' || stage === 'lock-release' });
  assert.throws(() => createFixtureLifecycle(fake.wp), /manual recovery required/);
  const owner = fake.lock;
  assert.ok(owner);
  assert.throws(() => createFixtureLifecycle(fake.wp), /manual recovery required/);
  assert.equal(fake.lock, owner);
  assert.equal(fake.stages.filter(stage => stage === 'snapshot').length, 1);
  assert.deepEqual(fake.option, option('0'));
});

test('an owner change after lookup is rejected inside the deletion command', async () => {
  const previousExitCode = process.exitCode;
  try {
    const fake = mockWp(option('0'), { afterOwnedList: (posts, ids) => {
      if (ids) posts.get(ids).token = 'replacement-owner';
    } });
    const lifecycle = createFixtureLifecycle(fake.wp);
    const id = lifecycle.createPost(createArgs('fixture'));
    const rows = [];
    assert.equal(await lifecycle.cleanup(undefined, rows), false);
    assert.equal(fake.posts.get(String(id)).token, 'replacement-owner');
    assert.ok(rows.some(row => row.name === 'owned-fixture-delete' && !row.pass));
    assert.ok(rows.some(row => row.name === 'fixture-mode-restored' && row.pass));
    assert.equal(fake.lock, undefined);
  } finally { process.exitCode = previousExitCode; }
});

test('the deletion command locks both the post and owner before invoking native deletion', async () => {
  let competingUpdate;
  const fake = mockWp(option('0'), { duringDelete: (id, changeOwner) => {
    competingUpdate = changeOwner(id, 'replacement-owner');
  } });
  const lifecycle = createFixtureLifecycle(fake.wp);
  lifecycle.createPost(createArgs('fixture'));
  assert.equal(await lifecycle.cleanup(undefined, []), true);
  assert.equal(competingUpdate, 'blocked');
  const php = fake.calls.find(args => args[1]?.includes('/* fixture owned-post-delete */'))[1];
  // mock はDBロックの成立を証明しない。実DBへ渡す保護区間の欠落も検知する。
  assert.match(php, /SET TRANSACTION ISOLATION LEVEL SERIALIZABLE/);
  assert.match(php, /strcasecmp\(\(string\) \$engine, 'InnoDB'\)/);
  const begin = php.indexOf('START TRANSACTION');
  const postLock = php.indexOf("post_type = 'page' FOR UPDATE");
  const ownerLock = php.indexOf('meta_key = %s FOR UPDATE');
  const ownerCheck = php.indexOf('$owners !== array($fixture_owner)');
  const nativeDelete = php.indexOf('wp_delete_post($fixture_id, true)');
  const commit = php.indexOf("$wpdb->query('COMMIT')");
  assert.ok(begin < postLock && postLock < ownerLock && ownerLock < ownerCheck && ownerCheck < nativeDelete && nativeDelete < commit);
  assert.match(php, /query\('ROLLBACK'\)/);
  assert.equal(fake.posts.size, 0);
});

for (const [label, config] of [
  ['restore mismatch', { restoreNoop: true }],
  ['recheck error', { fail: (stage, args, count) => stage === 'snapshot' && count === 2 }],
]) {
  test(`${label} retains the lock and prevents another lifecycle from accepting dirty state`, async () => {
    const previousExitCode = process.exitCode;
    try {
      const fake = mockWp(option('0'), config);
      const lifecycle = createFixtureLifecycle(fake.wp);
      const owner = fake.lock;
      fake.wp(['option', 'update', 'wtcf_event_fixture_mode', '1']);
      const rows = [];
      assert.equal(await lifecycle.cleanup(undefined, rows), false);
      assert.equal(fake.lock, owner);
      assert.ok(rows.some(row => row.name === 'fixture-mode-lock-retained' && row.manualRecoveryRequired));
      const snapshots = fake.stages.filter(stage => stage === 'snapshot').length;
      assert.throws(() => createFixtureLifecycle(fake.wp), /lock unavailable/);
      assert.equal(fake.stages.filter(stage => stage === 'snapshot').length, snapshots);
      assert.equal(fake.lock, owner);
    } finally { process.exitCode = previousExitCode; }
  });
}

test('restore failure after commit releases the lock only after independent exact recheck', async () => {
  const previousExitCode = process.exitCode;
  try {
    const fake = mockWp(option('0'), { fail: stage => stage === 'restore-after' });
    const lifecycle = createFixtureLifecycle(fake.wp);
    fake.wp(['option', 'update', 'wtcf_event_fixture_mode', '1']);
    const rows = [];
    assert.equal(await lifecycle.cleanup(undefined, rows), false);
    assert.ok(rows.some(row => row.name === 'fixture-mode-restore' && !row.pass));
    assert.ok(rows.some(row => row.name === 'fixture-mode-restored' && row.pass));
    assert.ok(rows.some(row => row.name === 'fixture-mode-lock-release' && row.pass));
    assert.equal(fake.lock, undefined);
    assert.deepEqual(fake.option, option('0'));
  } finally { process.exitCode = previousExitCode; }
});

test('a stolen lock forbids restoring or releasing another owner state', async () => {
  const previousExitCode = process.exitCode;
  try {
    const fake = mockWp(option('0'));
    const lifecycle = createFixtureLifecycle(fake.wp);
    fake.lock = 'replacement-owner';
    fake.option = option('replacement-state');
    const rows = [];
    assert.equal(await lifecycle.cleanup(undefined, rows), false);
    assert.deepEqual(fake.option, option('replacement-state'));
    assert.equal(fake.lock, 'replacement-owner');
    assert.equal(fake.stages.includes('lock-release'), false);
    assert.ok(rows.some(row => row.name === 'fixture-mode-lock-retained' && row.manualRecoveryRequired));
  } finally { process.exitCode = previousExitCode; }
});

test('lock release failure is reported and prevents a new lifecycle', async () => {
  const previousExitCode = process.exitCode;
  try {
    const fake = mockWp(option('0'), { fail: stage => stage === 'lock-release' });
    const lifecycle = createFixtureLifecycle(fake.wp);
    const rows = [];
    assert.equal(await lifecycle.cleanup(undefined, rows), false);
    assert.ok(rows.some(row => row.name === 'fixture-mode-lock-release' && !row.pass && row.manualRecoveryRequired));
    const snapshots = fake.stages.filter(stage => stage === 'snapshot').length;
    assert.throws(() => createFixtureLifecycle(fake.wp), /manual recovery required/);
    assert.equal(fake.stages.filter(stage => stage === 'snapshot').length, snapshots);
  } finally { process.exitCode = previousExitCode; }
});

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

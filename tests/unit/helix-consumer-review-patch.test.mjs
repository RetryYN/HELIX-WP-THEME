import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
const script = fs.readFileSync(new URL('../../scripts/patch-helix-consumer-review.mjs', import.meta.url), 'utf8');
const specs = JSON.parse(script.match(/const specs = (\[[\s\S]*?\]);\nconst pending/)[1]);
function fixture(run) {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-consumer-patch-'));
  try {
    fs.mkdirSync(path.join(dir, 'scripts'));
    const command = path.join(dir, 'scripts/patch-helix-consumer-review.mjs');
    fs.writeFileSync(command, script);
    const targets = specs.map(spec => {
      let original = fs.readFileSync(new URL('../../node_modules/helix/' + spec.path, import.meta.url), 'utf8');
      for (const [before, after] of [...spec.replacements].reverse()) original = original.replace(after, before);
      const target = path.join(dir, 'node_modules/helix', spec.path);
      fs.mkdirSync(path.dirname(target), { recursive: true });
      fs.writeFileSync(target, original);
      return target;
    });
    run(targets, () => spawnSync(process.execPath, [command], { cwd: os.tmpdir(), encoding: 'utf8' }));
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
}
test('fresh multi-file patch applies and is idempotent', () => fixture((targets, invoke) => {
  assert.equal(invoke().status, 0);
  const first = targets.map(p => fs.readFileSync(p, 'utf8'));
  assert.equal(invoke().status, 0);
  assert.deepEqual(targets.map(p => fs.readFileSync(p, 'utf8')), first);
}));
test('unknown final file rejects before changing any earlier file', () => fixture((targets, invoke) => {
  fs.appendFileSync(targets.at(-1), '\n// unknown upstream\n');
  const before = targets.map(p => fs.readFileSync(p, 'utf8'));
  assert.notEqual(invoke().status, 0);
  assert.deepEqual(targets.map(p => fs.readFileSync(p, 'utf8')), before);
}));

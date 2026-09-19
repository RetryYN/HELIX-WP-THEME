import test from 'node:test';
import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';

const capture = 'docs/research/2026-09-08-selection-catalog/visual-quality/dialog-focus/capture.mjs';
function run(env) {
  return new Promise(resolve => {
    const child = spawn(process.execPath, [capture, 'after'], {
      env: { ...process.env, CATALOG_BASELINE_REF: '', ...env },
    });
    let output = '';
    child.stderr.on('data', data => { output += data; });
    child.on('exit', code => resolve({ code, output }));
  });
}

test('baseline must be explicit before contacting the server', async () => {
  const result = await run({ CATALOG_BASE_URL: 'http://127.0.0.1:1' });
  assert.notEqual(result.code, 0);
  assert.match(result.output, /CATALOG_BASELINE_REF is required/);
});

for (const mismatch of [false, true]) {
  test(mismatch ? 'rejects another worktree source' : 'rejects byte-identical baseline', async () => {
    const server = createServer(async (request, response) => {
      try {
        const body = mismatch ? 'wrong source' : await readFile(`.${request.url}`);
        response.end(body);
      } catch { response.writeHead(404).end(); }
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    try {
      const result = await run({ CATALOG_BASELINE_REF: 'HEAD', CATALOG_BASE_URL: `http://127.0.0.1:${server.address().port}` });
      assert.notEqual(result.code, 0);
      assert.match(result.output, mismatch ? /Server source mismatch/ : /Baseline and current sources must differ/);
    } finally { await new Promise(resolve => server.close(resolve)); }
  });
}

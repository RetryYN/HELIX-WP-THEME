import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
const root=new URL('../../',import.meta.url);
const script=fs.readFileSync(new URL('scripts/patch-helix-receipt.mjs',root));
const installed=fs.readFileSync(new URL('node_modules/helix/src/doctor/l3-g3-logical-db-receipt.ts',root),'utf8');
const upstream=installed.replace('readFileSync(new URL("../../" + POLICY_PATH, import.meta.url), "utf8")','readFileSync(join(repoRoot, POLICY_PATH), "utf8")').replace('readFileSync(new URL("../../" + SCRIPT_PATH, import.meta.url))','readFileSync(join(repoRoot, SCRIPT_PATH))');
function fixture(source,run){const dir=fs.mkdtempSync(path.join(os.tmpdir(),'helix-patch-'));try{fs.mkdirSync(path.join(dir,'scripts'));const target=path.join(dir,'node_modules/helix/src/doctor/l3-g3-logical-db-receipt.ts');fs.mkdirSync(path.dirname(target),{recursive:true});fs.writeFileSync(target,source);const command=path.join(dir,'scripts/patch-helix-receipt.mjs');fs.writeFileSync(command,script);run({target,invoke:()=>spawnSync(process.execPath,[command],{cwd:os.tmpdir(),encoding:'utf8'})});}finally{fs.rmSync(dir,{recursive:true,force:true});}}
test('fresh dependency resolves verifier assets from package and keeps consumer projection root',()=>fixture(upstream,({target,invoke})=>{assert.equal(invoke().status,0);const result=fs.readFileSync(target,'utf8');assert.match(result,/new URL\("\.\.\/\.\.\/" \+ POLICY_PATH, import.meta.url\)/);assert.match(result,/const sourceHead = git\(repoRoot,/);assert.match(result,/rebuildHarnessDb\(\{\s*repoRoot,/);assert.match(result,/first.checkpoint_population_valid &&/);}));
test('reapplication leaves patched bytes unchanged',()=>fixture(upstream,({target,invoke})=>{assert.equal(invoke().status,0);const first=fs.readFileSync(target);assert.equal(invoke().status,0);assert.deepEqual(fs.readFileSync(target),first);}));
test('unknown upstream is rejected without modifying dependency',()=>fixture(upstream+'\n// incompatible upstream\n',({target,invoke})=>{const before=fs.readFileSync(target);assert.notEqual(invoke().status,0);assert.deepEqual(fs.readFileSync(target),before);}));

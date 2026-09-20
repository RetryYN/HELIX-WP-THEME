import { fixture, patches } from '../docs/research/2026-09-20-recovery-contract-poc/contract.mjs';
const json = value => JSON.stringify(value, null, 2).replaceAll('<', '\\u003c');
export const render = () => `<!doctype html>
<html lang="ja"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>変更回復契約 PoC</title><body>
<a href="#main">本文へ進む</a><main id="main"><h1>変更回復契約</h1><p>構造・スタイル・値・ゾーンを同じ契約で dry-run、apply、rollback するローカル検証です。</p><p id="status" role="status">まだ操作していません。</p>
<section aria-labelledby="operations-heading"><h2 id="operations-heading">操作</h2><button id="apply">dry-run → apply</button><button id="rollback" disabled>rollback</button><button id="invalid">無効なreceiptを試す</button><label>変更対象<select id="kind">${Object.keys(patches).map(kind => `<option value="${kind}">${kind}</option>`).join('')}</select></label></section>
<section aria-labelledby="state-heading"><h2 id="state-heading">状態</h2><pre id="state">${json(fixture)}</pre></section><section aria-labelledby="receipt-heading"><h2 id="receipt-heading">receipt / rollback point</h2><pre id="receipt">未発行</pre></section></main><script type="module" src="view.mjs"></script></body></html>`;

import { fixture, patches, dryRun, apply, rollback, digest } from './contract.mjs';
let state = structuredClone(fixture); let point = null;
const status = document.querySelector('#status'); const stateOut = document.querySelector('#state'); const receiptOut = document.querySelector('#receipt'); const rollbackButton = document.querySelector('#rollback');
const show = value => JSON.stringify(value, null, 2);
const publish = message => { status.textContent = message; stateOut.textContent = show(state); receiptOut.textContent = point ? show(point) : '未発行'; window.__recoveryState = { digest: digest(state), hasRollbackPoint: Boolean(point), message }; };
document.querySelector('#apply').addEventListener('click', () => { const kind = document.querySelector('#kind').value; const patch = patches[kind]; const receipt = dryRun(state, patch, `req-${kind}-001`); const result = apply(state, patch, receipt); state = result.state; point = result.rollbackPoint; rollbackButton.disabled = false; publish(`適用済み: ${kind} / before=${receipt.beforeDigest} / after=${receipt.targetDigest}`); });
rollbackButton.addEventListener('click', () => { const result = rollback(state, point); state = result.state; point = null; rollbackButton.disabled = true; publish(`復元済み: ${result.restoredDigest}`); });
document.querySelector('#invalid').addEventListener('click', () => { try { const receipt = dryRun(state, patches.structure, 'req-invalid-001'); apply(state, patches.style, receipt); publish('予期せず適用された'); } catch (error) { publish(`拒否: ${error.message}`); } });
publish('初期状態');

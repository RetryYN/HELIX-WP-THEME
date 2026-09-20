export const fixture = Object.freeze({
  schema: 'wt-recovery-contract.v1', revision: 1, resourceId: 'demo-site',
  resources: {
    structure: { slots: ['header', 'main', 'footer'], parts: ['header', 'footer'], templates: ['index', 'single', 'page'] },
    styles: { bodyFontScale: '1', contentWidth: 'wide', accent: 'brand' },
    values: { spacingScale: 'preset-40', minWidth: 'min-20', gradient: 'brand-soft' },
    zones: { header: 'site-header', main: 'content', footer: 'site-footer' },
  },
});

const clone = value => structuredClone(value);
const sorted = value => {
  if (Array.isArray(value)) return value.map(sorted);
  if (value && typeof value === 'object') return Object.fromEntries(Object.keys(value).sort().map(key => [key, sorted(value[key])]));
  return value;
};
export const canonical = value => JSON.stringify(sorted(value));
// The browser PoC must run without a Node-only module. This stable 64-bit
// digest binds the fixture and receipt inside the demo; it is not a security
// hash and is not used for production authorization.
const stableDigest = text => {
  let hash = 1469598103934665603n;
  for (const character of text) {
    hash ^= BigInt(character.codePointAt(0));
    hash = BigInt.asUintN(64, hash * 1099511628211n);
  }
  return hash.toString(16).padStart(16, '0');
};
export const digest = value => 'digest:' + stableDigest(canonical(value));

const getAt = (value, path) => path.split('.').reduce((current, key) => current?.[key], value);
const setAt = (value, path, next) => {
  const parts = path.split('.');
  if (parts.length < 3 || parts[0] !== 'resources') throw new Error(`Unknown resource path: ${path}`);
  const target = parts.slice(0, -1).reduce((current, key) => current?.[key], value);
  const key = parts.at(-1);
  if (!target || !Object.hasOwn(target, key)) throw new Error(`Unknown resource path: ${path}`);
  target[key] = next;
};

export const patchDigest = patch => digest(patch);
export const project = (state, patch) => {
  if (!Array.isArray(patch) || patch.length === 0) throw new Error('Patch must contain at least one operation');
  const next = clone(state);
  for (const operation of patch) {
    if (!operation || typeof operation.path !== 'string' || !Object.hasOwn(operation, 'value')) throw new Error('Patch operation requires path and value');
    if (!getAt(next, operation.path)) throw new Error(`Patch target is missing: ${operation.path}`);
    setAt(next, operation.path, clone(operation.value));
  }
  return next;
};

export const dryRun = (state, patch, requestId = 'req-demo-001') => {
  const beforeDigest = digest(state); const next = project(state, patch); const afterDigest = digest(next);
  return { schema: 'wt-recovery-receipt.v1', requestId, resourceId: state.resourceId, action: 'apply', beforeDigest, targetDigest: afterDigest, patchDigest: patchDigest(patch), diffCount: patch.length, preview: next.resources };
};

export const apply = (state, patch, receipt) => {
  if (!receipt || receipt.schema !== 'wt-recovery-receipt.v1') throw new Error('Apply requires a valid receipt');
  if (receipt.resourceId !== state.resourceId || receipt.action !== 'apply') throw new Error('Receipt resource mismatch');
  if (receipt.beforeDigest !== digest(state)) throw new Error('Receipt base digest is stale');
  if (receipt.patchDigest !== patchDigest(patch)) throw new Error('Receipt patch digest mismatch');
  const next = project(state, patch);
  if (digest(next) !== receipt.targetDigest) throw new Error('Receipt target digest mismatch');
  return { state: next, rollbackPoint: { schema: 'wt-recovery-rollback.v1', resourceId: state.resourceId, before: clone(state), beforeDigest: receipt.beforeDigest, targetDigest: receipt.targetDigest, receiptId: receipt.requestId } };
};

export const rollback = (state, point) => {
  if (!point || point.schema !== 'wt-recovery-rollback.v1') throw new Error('Rollback point is invalid');
  if (point.resourceId !== state.resourceId) throw new Error('Rollback resource mismatch');
  if (digest(state) !== point.targetDigest) throw new Error('Rollback target digest is stale');
  const restored = clone(point.before);
  if (digest(restored) !== point.beforeDigest) throw new Error('Rollback snapshot digest mismatch');
  return { state: restored, restoredDigest: digest(restored) };
};

export const patches = {
  structure: [{ path: 'resources.structure.templates', value: ['index', 'single', 'page', 'archive'] }],
  style: [{ path: 'resources.styles.contentWidth', value: 'constrained' }],
  value: [{ path: 'resources.values.spacingScale', value: 'preset-50' }],
  zone: [{ path: 'resources.zones.footer', value: 'footer-compact' }],
};

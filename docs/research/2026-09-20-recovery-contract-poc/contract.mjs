export const fixture = Object.freeze({
  schema: 'wt-recovery-contract.v1', revision: 1, resourceId: 'demo-site',
  resources: {
    structure: { slots: ['header', 'main', 'footer'], parts: ['header', 'footer'], templates: ['index', 'single', 'page'] },
    styles: { bodyFontScale: '1', contentWidth: 'wide', accent: 'brand' },
    values: { spacingScale: 'preset-40', minWidth: 'min-20', gradient: 'brand-soft' },
    zones: { header: 'site-header', main: 'content', footer: 'site-footer' },
  },
});

export const tokenProjectionFixture = Object.freeze({
  schema: 'wt-token-projection.v1',
  parent: {
    typography: { slugs: ['small', 'medium', 'large', 'x-large', 'xx-large', 'xxx-large'], values: ['0.875rem', '1rem', '1.125rem', '1.5rem', '2rem', '3rem'] },
    spacing: { slugs: ['10', '20', '30', '40', '50', '60'], values: ['0.5rem', '1rem', '1.5rem', '2rem', '3rem', '4rem'] },
    widths: { slugs: ['content', 'wide'], values: ['760px', '1200px'] },
    safeValues: {
      dimensionPreset: { slug: 'preset-40', value: '2rem' },
      minWidth: { slug: 'min-20', value: '20rem' },
      backgroundGradient: { slug: 'brand-soft', value: 'linear-gradient(135deg, #16324f, #5b8def)' },
    },
  },
  bridge: {
    typography: { slugs: ['small', 'medium', 'large', 'x-large', 'xx-large', 'xxx-large'], values: ['0.9rem', '1rem', '1.125rem', '1.5rem', '2rem', '3rem'] },
    spacing: { slugs: ['10', '20', '30', '40', '50', '60'], values: ['0.5rem', '1rem', '1.5rem', '2.25rem', '3rem', '4.5rem'] },
    widths: { slugs: ['content', 'wide'], values: ['800px', '1280px'] },
    safeValues: {
      dimensionPreset: { slug: 'preset-40', value: '2.25rem' },
      minWidth: { slug: 'min-20', value: '20rem' },
      backgroundGradient: { slug: 'brand-soft', value: 'linear-gradient(135deg, #16324f, #7aa2f7)' },
    },
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

const sameKeys = (left, right) => JSON.stringify(Object.keys(left).sort()) === JSON.stringify(Object.keys(right).sort());
const validateTokenDimension = (parent, projection, name) => {
  if (!projection || typeof projection !== 'object' || Array.isArray(projection)) throw new Error(`${name} projection must be an object`);
  if (!sameKeys(parent, projection)) throw new Error(`${name} projection changes dimensions`);
  if (JSON.stringify(parent.slugs) !== JSON.stringify(projection.slugs)) throw new Error(`${name} projection changes slugs`);
  if (!Array.isArray(projection.values) || projection.values.length !== parent.slugs.length) throw new Error(`${name} projection changes scale length`);
  if (projection.values.some(value => typeof value !== 'string' || !value.trim())) throw new Error(`${name} projection has invalid value`);
};

export const projectTokenLayer = (parent, projection) => {
  if (!parent || !projection || typeof parent !== 'object' || typeof projection !== 'object') throw new Error('Token projection requires objects');
  if (!sameKeys(parent, projection) || Object.hasOwn(projection, 'settings')) throw new Error('Token projection settings override is forbidden');
  for (const name of ['typography', 'spacing', 'widths']) validateTokenDimension(parent[name], projection[name], name);
  if (!sameKeys(parent.safeValues, projection.safeValues)) throw new Error('safe value dimensions cannot change');
  for (const name of Object.keys(parent.safeValues)) {
    const expected = parent.safeValues[name]; const actual = projection.safeValues[name];
    if (!actual || expected.slug !== actual.slug || typeof actual.value !== 'string' || !actual.value.trim()) throw new Error(`safe value ${name} changes slug or value`);
  }
  return structuredClone(projection);
};

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

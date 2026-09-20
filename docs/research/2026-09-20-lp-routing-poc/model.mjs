export const manifest = {
  schema: 'wt-lp-routing-manifest.v1',
  contentTypes: [
    { id: 'wt_lp', label: '獲得LP', purpose: '購入意思の高い利用者のCV獲得', slugBase: null, hierarchical: false },
    { id: 'wt_event', label: 'イベント', purpose: '開催情報と申込の管理', slugBase: null, hierarchical: false },
    { id: 'wt_comparison', label: '比較特設', purpose: '比較検討のための特設案内', slugBase: null, hierarchical: false },
    { id: 'wt_blp', label: '理解・比較ページ', purpose: '判断材料を提供してLPへ送る', slugBase: 'guides', hierarchical: true },
  ],
  routes: { lp: 'flat', fixedPage: 'hierarchical' },
};

export const records = [
  { id: 101, type: 'wt_lp', kind: 'normal', title: '編集相談を始める', slug: 'start-editorial-session', path: '/start-editorial-session/', goalCvId: 'consultation-start', blpId: 'blp-editorial-fit' },
  { id: 102, type: 'wt_event', kind: 'event', title: '編集設計ワークショップ', slug: 'editorial-workshop-2026', path: '/editorial-workshop-2026/', goalCvId: 'workshop-register', blpId: null },
  { id: 103, type: 'wt_comparison', kind: 'comparison', title: '運用支援プラン比較', slug: 'compare-support-plans', path: '/compare-support-plans/', goalCvId: 'plan-comparison-cta', blpId: 'blp-plan-fit' },
  { id: 104, type: 'wt_blp', kind: 'blp', title: '編集支援が向く条件', slug: 'editorial-fit', path: '/guides/editorial-fit/', goalCvId: null, blpId: null, sendsTo: 101 },
];

export const fixedPage = { id: 201, type: 'page', title: '会社概要', slug: 'about', path: '/company/about/', hierarchical: true };

const clone = value => structuredClone(value);
const knownTypes = new Map(manifest.contentTypes.map(type => [type.id, type]));

export function validateManifest(value = manifest) {
  if (value.schema !== manifest.schema || value.routes.lp !== 'flat' || value.routes.fixedPage !== 'hierarchical') throw new Error('manifest routing contract is invalid');
  if (value.contentTypes.length !== 4) throw new Error('manifest content types are incomplete');
  for (const type of value.contentTypes) {
    if (!type.id || !type.label || !type.purpose || typeof type.hierarchical !== 'boolean') throw new Error(`invalid content type: ${type.id}`);
    if (type.id !== 'wt_blp' && type.slugBase !== null) throw new Error(`non-BLP type has a directory base: ${type.id}`);
  }
  return true;
}

export function validateRecord(record) {
  const type = knownTypes.get(record.type);
  if (!type) throw new Error(`unknown content type: ${record.type}`);
  if (!record.slug || record.slug.includes('/')) throw new Error(`hierarchical LP slug: ${record.slug}`);
  if (record.type === 'wt_blp') {
    if (!/^\/guides\/[a-z0-9-]+\/$/.test(record.path) || record.path !== `/guides/${record.slug}/`) throw new Error('BLP path must remain hierarchical under guides');
  } else {
    if (!/^\/[a-z0-9-]+\/$/.test(record.path) || record.path !== `/${record.slug}/`) throw new Error(`LP-like path is not flat: ${record.path}`);
  }
  if (record.type !== 'wt_blp' && !record.goalCvId) throw new Error(`CV goal is required: ${record.id}`);
  if (record.type === 'wt_blp' && record.goalCvId !== null) throw new Error('BLP must not own a CV goal');
  if (record.type === 'wt_blp' && !record.sendsTo) throw new Error('BLP must declare its LP destination');
  return true;
}

export function validateFixture(nextRecords = records) {
  validateManifest();
  const ids = new Set();
  for (const record of nextRecords) {
    if (ids.has(record.id)) throw new Error(`duplicate record ID: ${record.id}`);
    ids.add(record.id);
    validateRecord(record);
    if (record.sendsTo && !nextRecords.some(candidate => candidate.id === record.sendsTo && candidate.type === 'wt_lp')) throw new Error('BLP destination must be a normal LP');
  }
  if (!nextRecords.some(record => record.type === 'wt_event') || !nextRecords.some(record => record.type === 'wt_comparison')) throw new Error('event and comparison types are required');
  return true;
}

export function restProjection(nextRecords = records) {
  validateFixture(nextRecords);
  return nextRecords.map(record => ({ id: record.id, type: record.type, kind: record.kind, slug: record.slug, link: `https://example.invalid${record.path}`, showInRest: true, hierarchical: record.type === 'wt_blp' }));
}

export function copy(value) { return clone(value); }

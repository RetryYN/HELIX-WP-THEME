export const fixture = {
  schema: 'wt-recommendation-contract.v1',
  methods: {
    related: {
      id: 'related',
      selection: 'taxonomy',
      fallback: ['category', 'tag', 'manual'],
      manualOrder: ['post-101', 'post-102', 'post-103'],
    },
    popular: {
      id: 'popular',
      source: 'self',
      method: 'views',
      period: '7d',
      aggregation: 'daily',
      filters: { excludeBots: true, excludeAdmins: true },
      storage: { fields: ['postId', 'day', 'count'], forbidden: ['ip', 'rawView'] },
      rankingOwner: 'external',
      externalReadback: { enabled: true, endpointId: 'analytics.recommendations.v1' },
    },
    manual: {
      id: 'manual',
      selection: 'explicit-order',
      postIds: ['post-103', 'post-101', 'post-102'],
    },
  },
  displayTypes: ['cards', 'list', 'ranked', 'thumbnail-scale', 'horizontal-scroll'],
  displayBindings: {
    cards: { referenceId: 'helix-wt/recommendation-cards', purpose: '写真から次の記事を選ぶ' },
    list: { referenceId: 'helix-wt/recommendation-list', purpose: '内容を確かめて次の記事を選ぶ' },
    ranked: { referenceId: 'helix-wt/recommendation-ranked', purpose: '順位を添えて選ぶ' },
    'thumbnail-scale': { referenceId: 'helix-wt/recommendation-thumbnail-scale', purpose: '写真の大小で選ぶ' },
    'horizontal-scroll': { referenceId: 'helix-wt/recommendation-horizontal-scroll', purpose: '横送りで選ぶ' },
  },
};

const clone = value => structuredClone(value);
const required = (value, label) => {
  if (typeof value !== 'string' || value.trim() === '') throw new Error(`${label} is required`);
};
const exact = (actual, expected, label) => {
  if (JSON.stringify(actual) !== JSON.stringify(expected)) throw new Error(`${label} is invalid`);
};

export function validateRecommendationContract(value = fixture) {
  if (value.schema !== fixture.schema) throw new Error('recommendation schema is invalid');
  exact(Object.keys(value.methods || {}).sort(), ['manual', 'popular', 'related'], 'method set');
  const related = value.methods.related;
  exact(related.fallback, fixture.methods.related.fallback, 'related fallback');
  if (related.selection !== 'taxonomy' || !Array.isArray(related.manualOrder) || related.manualOrder.length !== 3) throw new Error('related selection is incomplete');

  const popular = value.methods.popular;
  if (!['self', 'external-readback'].includes(popular.source)) throw new Error('popular source is invalid');
  if (!['views', 'clicks'].includes(popular.method)) throw new Error('popular method is invalid');
  if (!['day', '7d', '30d'].includes(popular.period)) throw new Error('popular period is invalid');
  if (popular.source === 'self' && popular.aggregation !== 'daily') throw new Error('self aggregation must be daily');
  if (popular.filters?.excludeBots !== true || popular.filters?.excludeAdmins !== true) throw new Error('bot/admin exclusion is required');
  exact(popular.storage?.fields, ['postId', 'day', 'count'], 'stored fields');
  if (!Array.isArray(popular.storage?.forbidden) || !['ip', 'rawView'].every(field => popular.storage.forbidden.includes(field))) throw new Error('privacy boundary is invalid');
  if (popular.storage.fields.some(field => ['ip', 'rawView'].includes(field))) throw new Error('forbidden visitor field is stored');
  if (popular.rankingOwner !== 'external') throw new Error('ranking decision must remain external');
  if (popular.externalReadback?.enabled !== true) throw new Error('external readback is required');
  required(popular.externalReadback.endpointId, 'externalReadback.endpointId');

  const manual = value.methods.manual;
  if (manual.selection !== 'explicit-order' || !Array.isArray(manual.postIds) || manual.postIds.length < 1 || new Set(manual.postIds).size !== manual.postIds.length) throw new Error('manual order is invalid');
  exact(value.displayTypes, fixture.displayTypes, 'display type set');
  for (const type of value.displayTypes) {
    const binding = value.displayBindings?.[type];
    required(binding?.referenceId, `displayBindings.${type}.referenceId`);
    required(binding?.purpose, `displayBindings.${type}.purpose`);
  }
  return true;
}

export function readBack(value = fixture) {
  validateRecommendationContract(value);
  return clone(JSON.parse(JSON.stringify(value)));
}

export function aggregateDaily(records, value = fixture) {
  validateRecommendationContract(value);
  if (value.methods.popular.source !== 'self') throw new Error('daily aggregation is only available for self source');
  const totals = new Map();
  for (const record of records) {
    if (record.bot || record.admin) continue;
    if ('ip' in record || 'rawView' in record) throw new Error('raw visitor fields are forbidden');
    required(record.postId, 'record.postId');
    required(record.day, 'record.day');
    if (!Number.isInteger(record.count) || record.count < 0) throw new Error('record.count is invalid');
    const key = `${record.postId}:${record.day}`;
    totals.set(key, { postId: record.postId, day: record.day, count: (totals.get(key)?.count || 0) + record.count });
  }
  return [...totals.values()].sort((a, b) => `${a.day}:${a.postId}`.localeCompare(`${b.day}:${b.postId}`));
}

export function projectDisplay(result, type, value = fixture) {
  validateRecommendationContract(value);
  if (!value.displayTypes.includes(type)) throw new Error(`unknown display type: ${type}`);
  const referenceId = value.displayBindings[type].referenceId;
  return { method: result.method, postIds: [...result.postIds], count: result.postIds.length, referenceId };
}

export function copy(value) { return clone(value); }

export const schema = 'wt-zone-overrides.v1';

// The source audit reports 23 semantic families. Directional variants such as
// before/after and top/bottom remain in subpositions instead of becoming
// ambiguous ad-hoc zone IDs.
export const declaredZones = Object.freeze([
  { id: 'article_before', subpositions: ['before'] },
  { id: 'article_head', subpositions: ['title-after'] },
  { id: 'content_before_h2', subpositions: ['first-h2-before'] },
  { id: 'content_toc', subpositions: ['before', 'after'] },
  { id: 'article_foot', subpositions: ['body-after'] },
  { id: 'article_cta', subpositions: ['article-end'] },
  { id: 'related', subpositions: ['before', 'after', 'inside'] },
  { id: 'related_before', subpositions: ['before'] },
  { id: 'related_after', subpositions: ['after'] },
  { id: 'article_after', subpositions: ['after'] },
  { id: 'sidebar_main', subpositions: ['main'] },
  { id: 'sidebar_top', subpositions: ['top'] },
  { id: 'sidebar_sticky', subpositions: ['sticky'] },
  { id: 'sidebar_sp', subpositions: ['sp'] },
  { id: 'header_box', subpositions: ['after-header'] },
  { id: 'footer_main', subpositions: ['main'] },
  { id: 'footer_sp', subpositions: ['sp'] },
  { id: 'footer_before', subpositions: ['before'] },
  { id: 'nav_drawer', subpositions: ['drawer'] },
  { id: 'bottom_fixed', subpositions: ['fixed'] },
  { id: 'front', subpositions: ['top', 'bottom'] },
  { id: 'page', subpositions: ['top', 'bottom'] },
  { id: 'archive_head', subpositions: ['heading'] },
]);

const zoneIds = new Set(declaredZones.map(zone => zone.id));
const clone = value => structuredClone(value);
const fail = message => { throw new Error(message); };

const matches = (rule, context) => {
  const match = rule.match ?? {};
  if (match.device && match.device !== context.device) return false;
  if (match.post_type && match.post_type !== context.post_type) return false;
  if (match.taxonomy) {
    if (match.taxonomy !== context.taxonomy) return false;
    const expected = Array.isArray(match.terms) ? match.terms.map(String) : [];
    const actual = Array.isArray(context.terms) ? context.terms.map(String) : [];
    if (!expected.some(term => actual.includes(term))) return false;
  }
  return true;
};

export const validate = config => {
  if (!config || config.schema !== schema || !Array.isArray(config.zones)) fail('Zone schema is invalid');
  for (const zone of config.zones) {
    if (!zone || typeof zone.id !== 'string' || !zoneIds.has(zone.id)) fail(`Unknown zone id: ${zone?.id ?? ''}`);
    if (zone.creative_ref !== undefined && (typeof zone.creative_ref !== 'string' || !zone.creative_ref)) fail(`Creative reference is invalid: ${zone.id}`);
    if (zone.overrides !== undefined && !Array.isArray(zone.overrides)) fail(`Overrides must be an array: ${zone.id}`);
    for (const rule of zone.overrides ?? []) {
      if (!rule || typeof rule.creative_ref !== 'string' || !rule.creative_ref) fail(`Override creative reference is invalid: ${zone.id}`);
      if (!rule.match || typeof rule.match !== 'object' || Array.isArray(rule.match)) fail(`Override match is invalid: ${zone.id}`);
      if (rule.match.terms !== undefined && (!Array.isArray(rule.match.terms) || rule.match.terms.length === 0)) fail(`Override terms are invalid: ${zone.id}`);
      if (rule.match.device !== undefined && !['pc', 'sp'].includes(rule.match.device)) fail(`Override device is invalid: ${zone.id}`);
    }
  }
  return true;
};

export const resolve = (config, zoneId, context = {}) => {
  validate(config);
  if (!zoneIds.has(zoneId)) fail(`Unknown zone id: ${zoneId}`);
  const zone = config.zones.find(item => item.id === zoneId);
  if (!zone) return { zoneId, creative_ref: null, matched: null };
  const matched = (zone.overrides ?? []).find(rule => matches(rule, context));
  return { zoneId, creative_ref: matched?.creative_ref ?? zone.creative_ref ?? null, matched: matched ? clone(matched.match) : null };
};

export const fixture = Object.freeze({
  schema,
  zones: [
    {
      id: 'article_before', creative_ref: 'creative:default',
      overrides: [
        { match: { taxonomy: 'category', terms: ['guide'] }, creative_ref: 'creative:guide' },
        { match: { taxonomy: 'category', terms: ['guide', 'review'] }, creative_ref: 'creative:review' },
        { match: { device: 'sp' }, creative_ref: 'creative:mobile' },
      ],
    },
    { id: 'sidebar_sticky', creative_ref: 'creative:sidebar' },
    { id: 'front', creative_ref: 'creative:front' },
  ],
});

export const cases = Object.freeze([
  ['schema:23 semantic zones declared', () => declaredZones.length === 23],
  ['schema:directional variants remain explicit', () => declaredZones.find(z => z.id === 'content_toc').subpositions.join(',') === 'before,after'],
  ['schema:creative references are IDs', () => validate(fixture) === true],
  ['resolve:default creative', () => resolve(fixture, 'sidebar_sticky', {}).creative_ref === 'creative:sidebar'],
  ['resolve:first matching override wins', () => resolve(fixture, 'article_before', { taxonomy: 'category', terms: ['guide', 'review'], device: 'pc' }).creative_ref === 'creative:guide'],
  ['resolve:later rule is not merged', () => resolve(fixture, 'article_before', { taxonomy: 'category', terms: ['guide', 'review'], device: 'pc' }).matched.terms.length === 1],
  ['resolve:device fallback after taxonomy miss', () => resolve(fixture, 'article_before', { taxonomy: 'tag', terms: ['news'], device: 'sp' }).creative_ref === 'creative:mobile'],
  ['resolve:empty zone is explicit null', () => resolve(fixture, 'archive_head', {}).creative_ref === null],
  ['negative:unknown zone rejected', () => { try { resolve(fixture, 'category_override'); return false; } catch (error) { return error.message === 'Unknown zone id: category_override'; } }],
  ['negative:unknown config zone rejected', () => { try { validate({ ...fixture, zones: [{ id: 'not-declared' }] }); return false; } catch { return true; } }],
  ['negative:overrides object rejected', () => { try { validate({ ...fixture, zones: [{ id: 'front', overrides: {} }] }); return false; } catch { return true; } }],
  ['negative:missing creative reference rejected', () => { try { validate({ ...fixture, zones: [{ id: 'front', overrides: [{ match: {} }] }] }); return false; } catch { return true; } }],
]);

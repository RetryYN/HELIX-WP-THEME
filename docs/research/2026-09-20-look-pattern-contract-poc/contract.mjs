export const fixture = {
  schema: 'wt-look-pattern-contract.v1',
  owner: 'helix',
  survey: {
    index: 'docs/research/2026-09-04-site-survey/sites-index.json',
    analysis: 'docs/research/2026-09-04-site-survey/results/analysis.json',
    taxonomy: 'docs/research/2026-09-05-parts-pattern-taxonomy/aggregate.json',
  },
  observedPatterns: ['corporate', 'service', 'brand', 'portal', 'compare', 'motion'],
  variationMap: {
    corporate: ['business'],
    service: ['light'],
    brand: ['vivid', 'warm'],
    portal: ['editorial', 'mono'],
    compare: ['depth'],
    motion: ['dark', 'night-contrast'],
  },
  openPatterns: [
    { id: 'commerce', status: 'unobserved', source: 'page-type-ledger' },
    { id: 'membership', status: 'unobserved', source: 'page-type-ledger' },
    { id: 'jobs', status: 'unobserved', source: 'page-type-ledger' },
    { id: 'maintenance', status: 'unobserved', source: 'page-type-ledger' },
  ],
  gates: { gT1b: true, gT3: true, completion: false },
  sourceContract: 'docs/requirements/l3/requirements-ir.json',
};

const clone = value => structuredClone(value);

export function validateContract(value = fixture, observed = fixture.observedPatterns) {
  if (value.schema !== fixture.schema || value.owner !== 'helix') throw new Error('look pattern ownership or schema is invalid');
  for (const path of ['index', 'analysis', 'taxonomy']) if (value.survey?.[path] !== fixture.survey[path]) throw new Error(`look survey source is invalid: ${path}`);
  const observedSet = new Set(observed);
  if (observedSet.size !== fixture.observedPatterns.length || fixture.observedPatterns.some(pattern => !observedSet.has(pattern))) throw new Error('look observed pattern vocabulary is invalid');
  const mappedStyles = new Set();
  for (const pattern of fixture.observedPatterns) {
    const styles = value.variationMap?.[pattern];
    if (!Array.isArray(styles) || styles.length === 0) throw new Error(`missing variation mapping: ${pattern}`);
    for (const style of styles) mappedStyles.add(style);
  }
  if (mappedStyles.size !== 9) throw new Error(`variation mapping must cover nine existing styles: ${mappedStyles.size}`);
  if (!value.gates?.gT1b || !value.gates?.gT3) throw new Error('look consistency gates are incomplete');
  if (value.gates.completion !== false) throw new Error('unverified look patterns cannot be complete');
  if (!Array.isArray(value.openPatterns) || value.openPatterns.length < 1) throw new Error('unobserved pattern index is empty');
  for (const pattern of value.openPatterns) {
    if (!pattern.id || pattern.status !== 'unobserved' || observedSet.has(pattern.id)) throw new Error(`open pattern is not preserved: ${pattern.id}`);
    if (value.variationMap?.[pattern.id]) throw new Error(`unobserved pattern has a variation mapping: ${pattern.id}`);
  }
  if (value.sourceContract !== fixture.sourceContract) throw new Error('look source contract is invalid');
  return true;
}

export function project(value = fixture) { validateContract(value); return clone(value); }

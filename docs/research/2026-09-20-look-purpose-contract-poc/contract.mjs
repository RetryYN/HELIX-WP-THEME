export const fixture = {
  schema: 'wt-look-purpose-contract.v1',
  owner: 'helix',
  ledger: 'docs/research/2026-09-05-parts-pattern-taxonomy/by-purpose.md',
  surfaces: ['heading', 'box', 'cta', 'comparison', 'graph', 'blog-card', 'related', 'image-treatment'],
  axes: ['motion', 'depth', 'density', 'detext'],
  gates: { pc: true, sp: true, noJs: true, reducedMotion: true, contrastRatio: 4.5 },
  sourceContract: 'docs/research/2026-09-05-design-prototype-03/results/verify.json',
};
const clone = value => structuredClone(value);
export function validateContract(value = fixture) {
  if (value.schema !== fixture.schema || value.owner !== 'helix' || value.ledger !== fixture.ledger) throw new Error('look purpose ownership or ledger is invalid');
  for (const name of fixture.surfaces) if (!value.surfaces.includes(name)) throw new Error(`missing surface: ${name}`);
  for (const axis of fixture.axes) if (!value.axes.includes(axis)) throw new Error(`missing axis: ${axis}`);
  if (value.gates.pc !== true || value.gates.sp !== true || value.gates.noJs !== true || value.gates.reducedMotion !== true || value.gates.contrastRatio < 4.5) throw new Error('look quality gates are incomplete');
  if (value.sourceContract !== fixture.sourceContract) throw new Error('look source contract is invalid');
  return true;
}
export function project(value = fixture) { validateContract(value); return clone(value); }

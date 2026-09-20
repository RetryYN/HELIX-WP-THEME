export const fixture = {
  schema: 'wt-lp-tracking-fixture.v1',
  lp: {
    id: 'lp-editorial-session',
    title: '編集相談を始める',
    goalCvId: 'consultation-start',
    variantId: 'lp-editorial-session-a',
    variationId: 'lp-lead-v2',
    patternIds: ['lp-hero', 'lp-proof', 'lp-form'],
  },
  formSlots: [
    {
      id: 'consultation-form',
      placement: 'lp-form',
      submitTo: 'local:demo',
      confirmation: 'inline',
      fields: [
        { id: 'name', type: 'text', required: true },
        { id: 'email', type: 'email', required: true },
        { id: 'message', type: 'textarea', required: false },
      ],
    },
  ],
  tracking: {
    version: 'wt-data-layer.v1',
    events: ['lp_view', 'lp_scroll', 'lp_cta_click', 'lp_form_submit'],
    required: ['eventId', 'version', 'lpId', 'goalCvId', 'variantId'],
    destination: 'helix.dataLayer',
    owner: 'helix',
    optimizationOwner: 'external',
  },
};

const clone = value => structuredClone(value);
const requiredString = (value, label) => {
  if (typeof value !== 'string' || value.trim() === '') throw new Error(`${label} is required`);
};

export function validateTrackingContract(value = fixture) {
  if (value.schema !== fixture.schema) throw new Error('tracking fixture schema is invalid');
  for (const key of ['id', 'goalCvId', 'variantId', 'variationId']) requiredString(value.lp?.[key], `lp.${key}`);
  if (!Array.isArray(value.lp.patternIds) || value.lp.patternIds.length < 2) throw new Error('LP patterns are required');
  if (!Array.isArray(value.formSlots) || value.formSlots.length !== 1) throw new Error('exactly one form slot is required');
  const ids = new Set();
  for (const form of value.formSlots) {
    requiredString(form.id, 'form.id');
    requiredString(form.placement, 'form.placement');
    requiredString(form.submitTo, 'form.submitTo');
    if (ids.has(form.id)) throw new Error(`duplicate form slot: ${form.id}`);
    ids.add(form.id);
    if (!Array.isArray(form.fields) || !form.fields.length) throw new Error(`form fields are required: ${form.id}`);
    for (const field of form.fields) {
      requiredString(field.id, 'field.id');
      requiredString(field.type, 'field.type');
      if (typeof field.required !== 'boolean') throw new Error(`field.required must be boolean: ${field.id}`);
    }
  }
  const tracking = value.tracking;
  if (tracking?.version !== fixture.tracking.version) throw new Error('tracking version is invalid');
  if (tracking.destination !== 'helix.dataLayer' || tracking.owner !== 'helix') throw new Error('tracking destination is outside the HELIX contract');
  if (tracking.optimizationOwner !== 'external') throw new Error('optimization must remain outside the theme');
  if (JSON.stringify(tracking.events) !== JSON.stringify(fixture.tracking.events)) throw new Error('tracking event set is incomplete');
  if (JSON.stringify(tracking.required) !== JSON.stringify(fixture.tracking.required)) throw new Error('tracking required IDs are incomplete');
  return true;
}

export function validateEvent(event, value = fixture) {
  validateTrackingContract(value);
  if (!value.tracking.events.includes(event.name)) throw new Error(`unknown tracking event: ${event.name}`);
  for (const key of value.tracking.required) requiredString(event[key], `event.${key}`);
  if (event.version !== value.tracking.version) throw new Error('event version is invalid');
  if (event.lpId !== value.lp.id || event.goalCvId !== value.lp.goalCvId || event.variantId !== value.lp.variantId) throw new Error('event LP identity does not match fixture');
  if (event.name === 'lp_form_submit' && event.formId !== value.formSlots[0].id) throw new Error('event form identity does not match fixture');
  return true;
}

export function event(name, extra = {}, value = fixture) {
  const next = {
    name,
    eventId: `${name}:${value.lp.id}:${value.lp.variantId}`,
    version: value.tracking.version,
    lpId: value.lp.id,
    goalCvId: value.lp.goalCvId,
    variantId: value.lp.variantId,
    ...extra,
  };
  validateEvent(next, value);
  return next;
}

export function copy(value) { return clone(value); }

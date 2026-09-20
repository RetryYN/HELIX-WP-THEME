import { event, fixture, validateEvent } from './model.mjs';

const layer = window.helixDataLayer = [];
let sequence = 0;
const log = () => {
  const node = document.querySelector('#event-log');
  if (node) node.textContent = JSON.stringify(layer, null, 2);
};
const push = (name, extra = {}) => {
  const row = event(name, { ...extra, eventId: name + ':' + fixture.lp.id + ':' + (++sequence) }, fixture);
  validateEvent(row, fixture);
  layer.push(row);
  log();
  return row;
};

window.helixTracking = { fixture, layer, push };
push('lp_view', { surface: 'lp' });
let scrolled = false;
window.addEventListener('scroll', () => {
  if (scrolled) return;
  scrolled = true;
  push('lp_scroll', { scrollDepth: Math.round((scrollY / Math.max(1, document.documentElement.scrollHeight - innerHeight)) * 100) });
}, { passive: true });
document.querySelector('[data-helix-cta]')?.addEventListener('click', eventTarget => {
  eventTarget.preventDefault();
  push('lp_cta_click', { ctaId: 'consultation-form', placement: 'hero' });
  document.querySelector('#consultation-form')?.scrollIntoView({ block: 'start' });
});
document.querySelector('[data-lp-form]')?.addEventListener('submit', eventTarget => {
  eventTarget.preventDefault();
  const form = eventTarget.currentTarget;
  push('lp_form_submit', { formId: form.dataset.lpForm, fieldIds: [...form.elements].filter(element => element.name).map(element => element.name) });
  document.querySelector('#form-result').textContent = '送信イベントを記録しました。外部送信はありません。';
});

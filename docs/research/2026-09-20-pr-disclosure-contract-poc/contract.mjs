export const fixture = {
  schema: 'wt-pr-disclosure-contract.v1',
  defaultText: '本記事にはプロモーションが含まれます。',
  triggers: ['advertising-part', 'affiliate-link', 'product-link', 'affiliate-block', 'ad-creative'],
  detection: {
    owner: 'theme',
    mode: 'automatic',
    paragraphs: 3,
    maxCharacters: 600,
    topicWords: ['PR', '広告', 'アフィリエイト', 'プロモーション'],
    disclosureVerbs: ['含む', '含みます', '含まれます', '掲載', '表記'],
    negations: ['ない', 'なし', 'ません', 'ありません', 'ございません'],
  },
  placement: {
    location: 'article-top',
    firstView: true,
    count: 1,
    adjacentTo: [],
  },
  style: {
    tone: 'subtle',
    fontSize: 'theme-minimum',
    contrastRatio: 4.5,
    editorSelectable: true,
    aiSelectable: true,
  },
  controls: {
    pageScope: true,
    postScope: true,
    bodyCanRemove: false,
    overrideOnly: true,
  },
  provenance: {
    policyRef: 'WT-FR-VOCAB-03',
    defaultTextDecision: 'WT-EVT-0267',
    researchRef: 'docs/research/2026-09-20-pr-disclosure-contract-poc/external-observations.md',
  },
};

const clone = value => structuredClone(value);
const has = (value, list) => list.some(item => value.includes(item));
const normalize = value => String(value).replace(/<[^>]*>/g, '').trim();

export function hasExistingDisclosure(body, value = fixture) {
  const paragraphs = [...String(body).matchAll(/<p[^>]*>(.*?)<\/p>/gs)].slice(0, value.detection.paragraphs);
  const source = paragraphs.length ? paragraphs.map(match => normalize(match[1])).join('\n') : normalize(body);
  const limited = [...source].slice(0, value.detection.maxCharacters).join('');
  const sentences = limited.split(/(?<=[。！？])|\n+/u).filter(Boolean);
  return sentences.some(sentence => has(sentence, value.detection.topicWords)
    && has(sentence, value.detection.disclosureVerbs)
    && !has(sentence, value.detection.negations));
}

export function hasCommercialSignal(article, value = fixture) {
  return Array.isArray(article.signals) && article.signals.some(signal => value.triggers.includes(signal));
}

export function resolveNotice(article, value = fixture) {
  if (!hasCommercialSignal(article, value)) return null;
  if (article.override === 'off') return null;
  if (article.override === 'on' || !hasExistingDisclosure(article.body || '', value)) {
    return { text: value.defaultText, location: value.placement.location, count: value.placement.count, automatic: true };
  }
  return null;
}

export function validateContract(value = fixture) {
  if (value.schema !== fixture.schema || value.defaultText !== fixture.defaultText) throw new Error('PR disclosure identity is invalid');
  if (value.detection.owner !== 'theme' || value.detection.mode !== 'automatic') throw new Error('automatic detection ownership is invalid');
  if (value.detection.paragraphs !== 3 || value.detection.maxCharacters !== 600) throw new Error('detection boundary is invalid');
  if (!value.triggers.includes('affiliate-link') || !value.triggers.includes('product-link') || !value.triggers.includes('ad-creative')) throw new Error('commercial triggers are incomplete');
  if (value.placement.location !== 'article-top' || value.placement.firstView !== true || value.placement.count !== 1) throw new Error('placement is invalid');
  if (value.placement.adjacentTo.length !== 0) throw new Error('notice cannot be adjacent to CTA or banner');
  if (value.style.tone !== 'subtle' || value.style.fontSize !== 'theme-minimum' || value.style.contrastRatio < 4.5) throw new Error('style boundary is invalid');
  if (value.controls.bodyCanRemove !== false || value.controls.overrideOnly !== true) throw new Error('body removal boundary is invalid');
  if (!value.controls.pageScope || !value.controls.postScope) throw new Error('display scopes are incomplete');
  return true;
}

export function readBack(value = fixture) {
  validateContract(value);
  return clone(JSON.parse(JSON.stringify(value)));
}

export function copy(value) { return clone(value); }

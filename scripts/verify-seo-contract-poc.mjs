import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { fileURLToPath, pathToFileURL } from 'node:url';
import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { documentHtml, writeDocuments } from '../docs/research/2026-09-20-seo-contract-poc/render.mjs';
import { checkedAt, forbiddenTypes, graph as fixtureGraph, page, requiredProperties, sourceRegistry } from '../docs/research/2026-09-20-seo-contract-poc/fixture.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../docs/research/2026-09-20-seo-contract-poc');
const digest = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rows = [];
const check = (name, pass) => rows.push({ name, pass: Boolean(pass) });

function flattenTypes(graph) { return graph.flatMap(node => Array.isArray(node['@type']) ? node['@type'] : [node['@type']]); }
function contractResult(graph, registry) {
  const article = graph.find(node => node['@type'] === 'Article');
  const breadcrumbs = graph.find(node => node['@type'] === 'BreadcrumbList');
  if (!article || !breadcrumbs || flattenTypes(graph).some(type => forbiddenTypes.includes(type))) return false;
  for (const [type, properties] of Object.entries(requiredProperties)) {
    const node = graph.find(item => item['@type'] === type);
    if (!node || properties.some(property => node[property] === undefined || node[property] === '')) return false;
  }
  if (article.image !== page.image || article.headline !== page.title) return false;
  if (!Array.isArray(breadcrumbs.itemListElement) || breadcrumbs.itemListElement.length < 2) return false;
  if (breadcrumbs.itemListElement.some((item, index) => item.position !== index + 1)) return false;
  if (registry.some(source => source.status !== 'current' || source.checkedAt !== checkedAt || !/^https:\/\//.test(source.url))) return false;
  return true;
}

writeDocuments();
const html = fs.readFileSync(path.join(root, 'article.html'), 'utf8');
const parsed = JSON.parse(html.match(/<script id="seo-jsonld" type="application\/ld\+json">([\s\S]*?)<\/script>/)[1]);
const metadata = {
  title: html.match(/<title>([^<]+)<\/title>/)?.[1],
  description: html.match(/<meta name="description" content="([^"]+)/)?.[1],
  canonical: html.match(/<link rel="canonical" href="([^"]+)/)?.[1],
  robots: html.match(/<meta name="robots" content="([^"]+)/)?.[1],
  ogImage: html.match(/<meta property="og:image" content="([^"]+)/)?.[1],
};
check('SEO-04A visible metadata and one canonical graph', metadata.title === page.title && metadata.description === page.description && metadata.canonical === page.url && metadata.robots.includes('max-image-preview:large') && contractResult(parsed['@graph'], sourceRegistry));
check('SEO-04A breadcrumb and image contracts', metadata.ogImage === page.image && page.imageWidth >= 1200 && parsed['@graph'].find(node => node['@type'] === 'Article').image === metadata.ogImage && !html.includes('SearchAction'));
check('SEO-04A sponsored links keep ordinary links distinct', html.includes('rel="sponsored nofollow"') && html.includes('href="https://example.test/about"'));

const missingRequired = JSON.parse(JSON.stringify(fixtureGraph));
delete missingRequired.find(node => node['@type'] === 'Article').headline;
const missingAuthor = JSON.parse(JSON.stringify(fixtureGraph));
delete missingAuthor.find(node => node['@type'] === 'Article').author;
const missingPublished = JSON.parse(JSON.stringify(fixtureGraph));
delete missingPublished.find(node => node['@type'] === 'Article').datePublished;
const forbiddenAdded = JSON.parse(JSON.stringify(fixtureGraph));
forbiddenAdded.push({ '@type': 'FAQPage', mainEntity: [] });
const staleSource = sourceRegistry.map(source => ({ ...source, checkedAt: '2026-09-03' }));
check('SEO-04B missing required property is rejected', !contractResult(missingRequired, sourceRegistry));
check('SEO-04B forbidden type is rejected', !contractResult(forbiddenAdded, sourceRegistry));
check('NFR-SEO-01A registry and test lane enforce required fields', contractResult(fixtureGraph, sourceRegistry) && Object.keys(requiredProperties).length === 2 && forbiddenTypes.length === 3 && sourceRegistry.every(source => source.url && source.checkedAt));
check('NFR-SEO-01B missing Article.author is rejected', !contractResult(missingAuthor, sourceRegistry));
check('NFR-SEO-01B missing Article.datePublished is rejected', !contractResult(missingPublished, sourceRegistry));
check('NFR-SEO-01B stale source date is rejected', !contractResult(fixtureGraph, staleSource));

const browser = await chromium.launch({ headless: true });
const shots = [];
try {
  for (const view of ['article', 'registry']) {
    const pageView = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    await pageView.setContent(fs.readFileSync(path.join(root, `${view}.html`), 'utf8'));
    for (const [device, width] of [['pc', 1440], ['sp', 390]]) {
      await pageView.setViewportSize({ width, height: 1000 });
      const file = `${view}-${device}.jpg`;
      await pageView.screenshot({ path: path.join(root, file), type: 'jpeg', fullPage: true, quality: 85 });
      shots.push({ id: view, device, file, sha256: digest(path.join(root, file)) });
    }
    await pageView.close();
  }
} finally { await browser.close(); }

assert(rows.every(row => row.pass), JSON.stringify(rows));
const sources = [
  'scripts/verify-seo-contract-poc.mjs',
  'docs/research/2026-09-20-seo-contract-poc/fixture.mjs',
  'docs/research/2026-09-20-seo-contract-poc/render.mjs',
  'docs/research/2026-09-20-seo-contract-poc/article.html',
  'docs/research/2026-09-20-seo-contract-poc/registry.html',
];
const sourceDigests = Object.fromEntries(sources.map(source => [source, digest(path.resolve(source))]));
fs.writeFileSync(path.join(root, 'verification.json'), JSON.stringify({
  schema: 'wt-seo-contract-poc-verification.v1', completed: true,
  rows, shots, sourceDigests, checkedAt,
  scope: 'Local static metadata and JSON-LD contract only; no Rich Results, WordPress, search display, or production SEO plugin integration.',
  limitations: ['WP 7.2実機・12ページ種別・第三者SEOプラグイン譲渡・Rich Results Test・URL Inspection・実検索表示は未検証。'],
}, null, 2) + '\n');
const remaining = ['WP 7.2実機・12ページ種別・第三者SEOプラグイン譲渡・Rich Results Test・URL Inspection・実検索表示は未検証。', '本番のmeta/OGP/sitemap/CWV/A-B配信は未接続。'];
const proofRows = names => ({ path: 'docs/research/2026-09-20-seo-contract-poc/verification.json', row_names: rows.filter(row => names.includes(row.name)).map(row => row.name) });
fs.writeFileSync(path.join(root, 'acceptance-candidate.json'), JSON.stringify({
  'WT-AC-SEO-04A': { status: 'partial', scope: '同一fixtureから可視meta・BreadcrumbList・Article・画像・出典台帳を生成し、PC/SPで再読する。', remaining, proofs: [proofRows(['SEO-04A visible metadata and one canonical graph', 'SEO-04A breadcrumb and image contracts', 'SEO-04A sponsored links keep ordinary links distinct'])] },
  'WT-AC-SEO-04B': { status: 'partial', scope: '必須プロパティ欠落・禁止型・出典日付の負例を同じ契約で拒否する。', remaining, proofs: [proofRows(['SEO-04B missing required property is rejected', 'SEO-04B forbidden type is rejected'])] },
  'WT-AC-NFR-SEO-01A': { status: 'partial', scope: 'required propertiesと廃止型台帳を検査するローカルtest laneを持つ。', remaining, proofs: [proofRows(['NFR-SEO-01A registry and test lane enforce required fields'])] },
  'WT-AC-NFR-SEO-01B': { status: 'partial', scope: '廃止型追加・必須欠落・古い出典日付をFAILへ落とす。', remaining, proofs: [proofRows(['NFR-SEO-01B missing Article.author is rejected', 'NFR-SEO-01B missing Article.datePublished is rejected', 'NFR-SEO-01B stale source date is rejected', 'SEO-04B forbidden type is rejected'])] },
}, null, 2) + '\n');
fs.writeFileSync(path.join(root, 'catalog-candidates.json'), JSON.stringify({
  schema: 'wt-seo-contract-catalog-candidates.v1', entries: [
    { id: 'seo-contract:article', face: 'article', part: 'seo-contract-article', label: 'SEO契約：可視メタとJSON-LD', variant: 'article', description: '可視本文・meta・BreadcrumbList・Articleを同じ正本から確かめる。4受入条件は部分確認。', purpose: '検索向け出力の正本と境界を選ぶ', group: 'ページ・本文', images: { pc: '../2026-09-20-seo-contract-poc/article-pc.jpg', sp: '../2026-09-20-seo-contract-poc/article-sp.jpg' }, requirementIds: ['WT-FR-SEO-04'], referenceId: 'wt-seo-contract-poc.v1', evidence: '../2026-09-20-seo-contract-poc/verification.json', selectionFacts: { '対象と判断': '可視本文・meta・JSON-LDを一つのfixtureから照合する。', '正本': 'Article / BreadcrumbList / meta / OGPを同じfixtureから出力。', '実測': '1440/390px、JSON-LD値、Breadcrumb順序、画像1200px、rel属性、負例。', '未検証': remaining.join(' ') } },
    { id: 'seo-contract:registry', face: 'article', part: 'seo-contract-registry', label: 'SEO契約：型・出典台帳', variant: 'registry', description: '必須プロパティと非推奨型の出典・参照日を確認する。4受入条件は部分確認。', purpose: '検索仕様の更新を要求へ戻す', group: '共通設定・部品', images: { pc: '../2026-09-20-seo-contract-poc/registry-pc.jpg', sp: '../2026-09-20-seo-contract-poc/registry-sp.jpg' }, requirementIds: ['WT-NFR-SEO-01'], referenceId: 'wt-seo-contract-poc.v1', evidence: '../2026-09-20-seo-contract-poc/verification.json', selectionFacts: { '対象と判断': '型ごとの必須プロパティと廃止型を台帳で検査する。', '台帳': '公式URL・確認日・statusをJSON正本へ保持。', '実測': 'required fields、Forbidden types、古い日付・欠落・禁止型の負例。', '未検証': remaining.join(' ') } },
  ]
}, null, 2) + '\n');
console.log(JSON.stringify({ completed: true, tests: rows.length, screenshots: shots.length, acceptance: '4 partial candidates' }));

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { graph as defaultGraph, page, sourceRegistry } from './fixture.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)));

export function documentHtml({ graph = defaultGraph, registry = sourceRegistry, view = 'article' } = {}) {
  const jsonLd = JSON.stringify({ '@context': 'https://schema.org', '@graph': graph });
  const registryRows = registry.map(source => `<tr><td>${source.id}</td><td><a href="${source.url}">${source.url}</a></td><td>${source.checkedAt}</td><td>${source.status}</td></tr>`).join('');
  return `<!doctype html>
<html lang="ja"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>${page.title}</title>
<meta name="description" content="${page.description}">
<link rel="canonical" href="${page.url}">
<meta name="robots" content="index,follow,max-image-preview:large">
<meta property="og:title" content="${page.title}">
<meta property="og:description" content="${page.description}">
<meta property="og:url" content="${page.url}">
<meta property="og:image" content="${page.image}">
<meta property="og:image:width" content="${page.imageWidth}">
<meta property="og:image:height" content="${page.imageHeight}">
<script id="seo-jsonld" type="application/ld+json">${jsonLd}</script>
</head><body><main>
<nav aria-label="パンくず"><ol><li><a href="https://example.test/">ホーム</a></li><li><a href="https://example.test/articles/">記事</a></li><li aria-current="page">${page.title}</li></ol></nav>
<article><h1>${page.title}</h1><p>${page.description}</p><p><a href="https://example.test/shop" rel="sponsored nofollow">商品を見る</a> <a href="https://example.test/about">編集方針</a></p></article>
${view === 'registry' ? `<section><h2>出典と型の台帳</h2><table><thead><tr><th>ID</th><th>URL</th><th>確認日</th><th>状態</th></tr></thead><tbody>${registryRows}</tbody></table><p>禁止型: FAQPage / HowTo / SearchAction</p></section>` : ''}
</main></body></html>`;
}

export function writeDocuments() {
  fs.mkdirSync(root, { recursive: true });
  fs.writeFileSync(path.join(root, 'article.html'), documentHtml());
  fs.writeFileSync(path.join(root, 'registry.html'), documentHtml({ view: 'registry' }));
}

if (process.argv[1] && path.resolve(process.argv[1]) === path.resolve(fileURLToPath(import.meta.url))) writeDocuments();

export const checkedAt = '2026-09-20';

export const page = Object.freeze({
  url: 'https://example.test/articles/structured-data-contract',
  title: '構造化データ契約の検証記事',
  description: '可視本文と検索向けメタデータを同じ正本から検査する架空記事です。',
  image: 'https://example.test/assets/structured-data-contract-1200x630.jpg',
  imageWidth: 1200,
  imageHeight: 630,
});

export const graph = Object.freeze([
  {
    '@type': 'Article',
    '@id': `${page.url}#article`,
    headline: page.title,
    description: page.description,
    image: page.image,
    datePublished: '2026-09-20',
    author: { '@type': 'Person', name: 'Example Editorial' },
    mainEntityOfPage: page.url,
  },
  {
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'ホーム', item: 'https://example.test/' },
      { '@type': 'ListItem', position: 2, name: '記事', item: 'https://example.test/articles/' },
      { '@type': 'ListItem', position: 3, name: page.title },
    ],
  },
]);

export const sourceRegistry = Object.freeze([
  { id: 'google-structured-data-intro', url: 'https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data', checkedAt, status: 'current' },
  { id: 'google-structured-data-policies', url: 'https://developers.google.com/search/docs/appearance/structured-data/sd-policies', checkedAt, status: 'current' },
  { id: 'google-structured-data-updates', url: 'https://developers.google.com/search/updates', checkedAt, status: 'current' },
]);

export const requiredProperties = Object.freeze({
  Article: ['headline', 'author', 'datePublished', 'image'],
  BreadcrumbList: ['itemListElement'],
});

export const forbiddenTypes = Object.freeze(['FAQPage', 'HowTo', 'SearchAction']);

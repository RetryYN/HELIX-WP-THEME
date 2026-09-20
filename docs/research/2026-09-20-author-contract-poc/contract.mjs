export const fixture = {
  schema: 'wt-author-contract.v1',
  owner: 'helix',
  source: 'config/author-registry.json',
  author: {
    id: 'person:ayaka-sato',
    name: '佐藤 綾香',
    bio: '編集者。生活用品の比較記事を担当。',
    credentials: ['編集者', '家電製品アドバイザー'],
    url: 'https://example.test/authors/ayaka-sato',
    sameAs: ['https://example.test/about/ayaka-sato'],
    image: 'https://example.test/media/ayaka-sato.jpg',
  },
  supervisor: {
    id: 'person:kenji-mori',
    name: '森 健司',
    bio: '監修者。電気用品安全法の実務を監修。',
    credentials: ['電気主任技術者'],
    url: 'https://example.test/supervisors/kenji-mori',
    sameAs: ['https://example.test/about/kenji-mori'],
    image: 'https://example.test/media/kenji-mori.jpg',
  },
  surfaces: ['authorBox', 'supervisorBox', 'authorArchive'],
  structuredData: { articleAuthor: 'author', reviewedBy: 'supervisor', profilePage: true },
  policy: { authorType: 'Person', reviewedByType: 'Person', reviewAuthorOrganization: false, themeGeneratesDecision: false },
};

const clone = value => structuredClone(value);
const requiredPerson = person => {
  if (!person || person.id?.startsWith('person:') !== true || !person.name || !person.bio || !person.url || !person.image || !Array.isArray(person.credentials) || !person.credentials.length || !Array.isArray(person.sameAs) || !person.sameAs.length) throw new Error('person registry entry is incomplete');
  if (person.url === person.image || person.sameAs.includes(person.url)) throw new Error('person profile links are not distinct');
};

export function validateContract(value = fixture) {
  if (value.schema !== fixture.schema || value.owner !== 'helix' || value.source !== fixture.source) throw new Error('author registry ownership is invalid');
  requiredPerson(value.author); requiredPerson(value.supervisor);
  if (value.author.id === value.supervisor.id) throw new Error('author and supervisor identities must be distinct');
  if (JSON.stringify(value.surfaces) !== JSON.stringify(fixture.surfaces)) throw new Error('author surfaces are incomplete');
  if (value.structuredData.articleAuthor !== 'author' || value.structuredData.reviewedBy !== 'supervisor' || value.structuredData.profilePage !== true) throw new Error('structured-data binding is invalid');
  if (value.policy.authorType !== 'Person' || value.policy.reviewedByType !== 'Person' || value.policy.reviewAuthorOrganization !== false || value.policy.themeGeneratesDecision !== false) throw new Error('author policy boundary is invalid');
  return true;
}

export function project(value = fixture) {
  validateContract(value);
  return {
    authorBox: clone(value.author),
    supervisorBox: clone(value.supervisor),
    authorArchive: clone(value.author),
    structuredData: {
      Article: { author: clone(value.author) },
      reviewedBy: clone(value.supervisor),
      ProfilePage: { mainEntity: clone(value.author), image: value.author.image, url: value.author.url },
    },
    source: value.source,
  };
}

export function update(value, patch) {
  const next = clone(value);
  Object.assign(next.author, patch.author ?? {});
  Object.assign(next.supervisor, patch.supervisor ?? {});
  return next;
}

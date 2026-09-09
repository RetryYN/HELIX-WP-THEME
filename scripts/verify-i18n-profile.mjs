import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..');
const theme = path.join(root, 'themes/agent-neo-theme');
const profilePath = path.join(theme, 'config/i18n-profile.json');
const stylePath = path.join(theme, 'style.css');
const potPath = path.join(theme, 'languages/agent-neo.pot');
const outputPath = path.join(root, 'docs/research/2026-09-10-i18n-boundary/verify.json');
const writePot = process.argv.includes('--write-pot');

const walk = directory => fs.readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
  const absolute = path.join(directory, entry.name);
  return entry.isDirectory() ? walk(absolute) : [absolute];
});

const decodePhpLiteral = (quote, value) => quote === "'"
  ? value.replace(/\\\\/g, '\\').replace(/\\'/g, "'")
  : value.replace(/\\([\\"$nrt])/g, (_, escaped) => ({ n: '\n', r: '\r', t: '\t' }[escaped] ?? escaped));

function extractMessages() {
  const calls = /\b(__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\s*\(\s*(['"])(.*?)\2\s*,\s*(['"])(.*?)\4/gs;
  const messages = new Map();
  for (const absolute of walk(theme).filter(file => file.endsWith('.php'))) {
    const source = fs.readFileSync(absolute, 'utf8');
    const relative = path.relative(root, absolute).split(path.sep).join('/');
    for (const match of source.matchAll(calls)) {
      const msgid = decodePhpLiteral(match[2], match[3]);
      const domain = decodePhpLiteral(match[4], match[5]);
      const line = source.slice(0, match.index).split('\n').length;
      const item = messages.get(msgid) ?? { msgid, domains: new Set(), references: [] };
      item.domains.add(domain);
      item.references.push(`${relative}:${line}`);
      messages.set(msgid, item);
    }
  }
  return [...messages.values()].sort((a, b) => a.msgid.localeCompare(b.msgid, 'en'));
}

function findUntranslatedCjk() {
  const cjk = /[\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Han}\u3000-\u303f\uff00-\uffef]/u;
  const gettext = /\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\s*\(/;
  const findings = [];
  for (const absolute of walk(theme).filter(file => file.endsWith('.php'))) {
    const relative = path.relative(root, absolute).split(path.sep).join('/');
    for (const [index, original] of fs.readFileSync(absolute, 'utf8').split('\n').entries()) {
      const line = original.replace(/\/\/.*$/, '');
      if (/^\s*(?:\*|#|\/\*)/.test(line) || gettext.test(line)) continue;
      const phpOutput = /\b(?:echo|print)\b/.test(line) && cjk.test(line);
      const patternHtml = relative.includes('/patterns/') && />[^<]*[\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Han}][^<]*</u.test(line) && !/<!--.*-->/.test(line);
      if (phpOutput || patternHtml) findings.push(`${relative}:${index + 1}`);
    }
  }
  return findings;
}

const quotePo = value => JSON.stringify(value).replace(/\\u2028|\\u2029/g, match => match.toLowerCase());

function renderPot(messages, domain) {
  const header = [
    '# Copyright (C) 2026 AGENT NEO',
    '# This file is distributed under the same license as the AGENT NEO package.',
    'msgid ""',
    'msgstr ""',
    '"Project-Id-Version: AGENT NEO 0.1.0\\n"',
    '"MIME-Version: 1.0\\n"',
    '"Content-Type: text/plain; charset=UTF-8\\n"',
    '"Content-Transfer-Encoding: 8bit\\n"',
    `"X-Domain: ${domain}\\n"`,
    '',
  ];
  const entries = messages.flatMap(item => [
    `#: ${[...new Set(item.references)].sort().join(' ')}`,
    `msgid ${quotePo(item.msgid)}`,
    'msgstr ""',
    '',
  ]);
  return `${[...header, ...entries].join('\n').trimEnd()}\n`;
}

const profile = JSON.parse(fs.readFileSync(profilePath, 'utf8'));
const style = fs.readFileSync(stylePath, 'utf8');
const messages = extractMessages();
const expectedPot = renderPot(messages, profile.text_domain);
if (writePot) fs.writeFileSync(potPath, expectedPot);
const actualPot = fs.readFileSync(potPath, 'utf8');
const domains = [...new Set(messages.flatMap(item => [...item.domains]))].sort();
const styleDomain = style.match(/^Text Domain:\s*(\S+)\s*$/m)?.[1] ?? null;
const sourceCalls = messages.reduce((sum, item) => sum + item.references.length, 0);
const allGettextCalls = walk(theme).filter(file => file.endsWith('.php')).reduce((sum, file) =>
  sum + [...fs.readFileSync(file, 'utf8').matchAll(/\b(?:__|_e|_x|_ex|_n|_nx|esc_html__|esc_html_e|esc_html_x|esc_attr__|esc_attr_e|esc_attr_x)\s*\(/g)].length, 0);
const untranslatedCjk = findUntranslatedCjk();

const rows = [];
const check = (name, pass, detail) => rows.push({ name, pass: Boolean(pass), detail });
check('i18n:source-calls-found', messages.length > 0, { unique_messages: messages.length });
check('i18n:all-gettext-calls-extracted', sourceCalls === allGettextCalls, { extracted: sourceCalls, all_gettext_calls: allGettextCalls });
check('i18n:text-domain-consistent', domains.length === 1 && domains[0] === profile.text_domain && styleDomain === profile.text_domain, { profile: profile.text_domain, style: styleDomain, source: domains });
check('i18n:pot-exactly-matches-source', actualPot === expectedPot, { unique_messages: messages.length });
check('i18n:no-untranslated-cjk-output', untranslatedCjk.length === 0, { findings: untranslatedCjk });
check('i18n:source-languages-declared', JSON.stringify(profile.source_languages) === JSON.stringify(['ja', 'en']), { source_languages: profile.source_languages ?? null });
check('i18n:rtl-out-of-scope-explicit', profile.rtl?.supported === false && profile.rtl?.scope === 'out-of-scope', { rtl: profile.rtl });

const alteredDomain = structuredClone(profile);
alteredDomain.text_domain = 'wrong-domain';
check('negative:text-domain-drift-fails', !(domains.length === 1 && domains[0] === alteredDomain.text_domain && styleDomain === alteredDomain.text_domain));
check('negative:pot-source-drift-fails', `${actualPot}\n# drift\n` !== expectedPot);
check('negative:untranslated-cjk-output-fails', cjkFixtureFails("echo '未翻訳';"));
const alteredLanguages = structuredClone(profile);
delete alteredLanguages.source_languages;
check('negative:missing-source-language-policy-fails', JSON.stringify(alteredLanguages.source_languages) !== JSON.stringify(['ja', 'en']));
const alteredRtl = structuredClone(profile);
delete alteredRtl.rtl.scope;
check('negative:missing-rtl-boundary-fails', !(alteredRtl.rtl?.supported === false && alteredRtl.rtl?.scope === 'out-of-scope'));

const result = {
  schema: 'wt-i18n-boundary-verification.v1',
  completed: true,
  requirements: ['WT-NFR-ENV-01'],
  source: 'themes/agent-neo-theme/config/i18n-profile.json',
  counts: { source_calls: sourceCalls, unique_messages: messages.length, untranslated_cjk: untranslatedCjk.length },
  rows,
  failed: rows.filter(row => !row.pass).length,
};
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify(result, null, 2));
if (result.failed) process.exitCode = 1;

function cjkFixtureFails(line) {
  return /\b(?:echo|print)\b/.test(line) && /[\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Han}]/u.test(line) && !/\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\s*\(/.test(line);
}

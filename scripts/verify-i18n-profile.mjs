import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..');
const theme = path.join(root, 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt');
const profilePath = path.join(theme, 'config/i18n-profile.json');
const stylePath = path.join(theme, 'style.css');
const potPath = path.join(theme, 'languages/helix-wt.pot');
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
    const source = fs.readFileSync(absolute, 'utf8');
    const htmlLines = stripPhp(source).split('\n');
    for (const [index, original] of source.split('\n').entries()) {
      const line = original.replace(/\/\/.*$/, '');
      if (/^\s*(?:\*|#|\/\*)/.test(line)) continue;
      const phpOutput = /\b(?:echo|print)\b/.test(line) && cjk.test(line) && !gettext.test(line);
      const htmlOutsidePhp = htmlLines[index] ?? '';
      const rawHtml = />[^<]*[\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Han}][^<]*</u.test(htmlOutsidePhp) && !/<!--.*-->/.test(htmlOutsidePhp);
      const rawAttribute = hasUntranslatedCjkAttribute(line);
      if (phpOutput || rawHtml || rawAttribute) findings.push(`${relative}:${index + 1}`);
    }
  }
  return findings;
}

function hasUntranslatedCjkAttribute(line) {
  const cjk = /[\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Han}]/u;
  const userFacingAttribute = /\b(?:alt|aria-label|placeholder|title)=(['"])(.*?)\1/g;
  for (const match of line.matchAll(userFacingAttribute)) {
    if (!cjk.test(match[2])) continue;
    if (/esc_attr(?:__|_e)?\s*\(/.test(match[2])) continue;
    return true;
  }
  return false;
}

function stripPhp(source) {
  let inPhp = false;
  let output = '';
  for (let index = 0; index < source.length;) {
    if (!inPhp && source.startsWith('<?php', index)) { inPhp = true; index += 5; continue; }
    if (inPhp && source.startsWith('?>', index)) { inPhp = false; index += 2; continue; }
    const character = source[index++];
    output += inPhp && character !== '\n' ? ' ' : character;
  }
  return output;
}

const quotePo = value => JSON.stringify(value).replace(/\\u2028|\\u2029/g, match => match.toLowerCase());

function renderPot(messages, domain) {
  const header = [
    '# Copyright (C) 2026 HELIX WT',
    '# This file is distributed under the same license as the HELIX WT package.',
    'msgid ""',
    'msgstr ""',
    '"Project-Id-Version: HELIX WT 0.3.21\\n"',
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

function evaluate(input) {
  return {
    'i18n:source-calls-found': input.uniqueMessages > 0,
    'i18n:all-gettext-calls-extracted': input.sourceCalls === input.allGettextCalls,
    'i18n:text-domain-consistent': input.domains.length === 1 && input.domains[0] === input.profile.text_domain && input.styleDomain === input.profile.text_domain,
    'i18n:pot-exactly-matches-source': input.actualPot === input.expectedPot,
    'i18n:no-untranslated-cjk-output': input.untranslatedCjk.length === 0,
    'i18n:source-languages-declared': JSON.stringify(input.profile.source_languages) === JSON.stringify(['ja', 'en']),
    'i18n:rtl-out-of-scope-explicit': input.profile.rtl?.supported === false && input.profile.rtl?.scope === 'out-of-scope',
  };
}

const productionInput = { profile, styleDomain, domains, actualPot, expectedPot, untranslatedCjk, sourceCalls, allGettextCalls, uniqueMessages: messages.length };
const gates = evaluate(productionInput);

const rows = [];
const check = (name, pass, detail) => rows.push({ name, pass: Boolean(pass), detail });
check('i18n:source-calls-found', gates['i18n:source-calls-found'], { unique_messages: messages.length });
check('i18n:all-gettext-calls-extracted', gates['i18n:all-gettext-calls-extracted'], { extracted: sourceCalls, all_gettext_calls: allGettextCalls });
check('i18n:text-domain-consistent', gates['i18n:text-domain-consistent'], { profile: profile.text_domain, style: styleDomain, source: domains });
check('i18n:pot-exactly-matches-source', gates['i18n:pot-exactly-matches-source'], { unique_messages: messages.length });
check('i18n:no-untranslated-cjk-output', gates['i18n:no-untranslated-cjk-output'], { findings: untranslatedCjk });
check('i18n:source-languages-declared', gates['i18n:source-languages-declared'], { source_languages: profile.source_languages ?? null });
check('i18n:rtl-out-of-scope-explicit', gates['i18n:rtl-out-of-scope-explicit'], { rtl: profile.rtl });

const alteredDomain = structuredClone(profile);
alteredDomain.text_domain = 'wrong-domain';
check('negative:text-domain-drift-fails', !evaluate({ ...productionInput, profile: alteredDomain })['i18n:text-domain-consistent'], { failed_gate: 'i18n:text-domain-consistent' });
check('negative:pot-source-drift-fails', !evaluate({ ...productionInput, actualPot: `${actualPot}\n# drift\n` })['i18n:pot-exactly-matches-source'], { failed_gate: 'i18n:pot-exactly-matches-source' });
check('negative:untranslated-cjk-output-fails', cjkFixtureFails("echo '未翻訳';") && !evaluate({ ...productionInput, untranslatedCjk: ['fixture.php:1'] })['i18n:no-untranslated-cjk-output'], { failed_gate: 'i18n:no-untranslated-cjk-output' });
check('negative:untranslated-cjk-template-html-fails', cjkFixtureFails('<p>未翻訳</p>') && !evaluate({ ...productionInput, untranslatedCjk: ['fixture.php:1'] })['i18n:no-untranslated-cjk-output'], { failed_gate: 'i18n:no-untranslated-cjk-output' });
check('negative:untranslated-cjk-attribute-fails', cjkFixtureFails('<input placeholder="未翻訳">') && !evaluate({ ...productionInput, untranslatedCjk: ['fixture.php:1'] })['i18n:no-untranslated-cjk-output'], { failed_gate: 'i18n:no-untranslated-cjk-output' });
const alteredLanguages = structuredClone(profile);
delete alteredLanguages.source_languages;
check('negative:missing-source-language-policy-fails', !evaluate({ ...productionInput, profile: alteredLanguages })['i18n:source-languages-declared'], { failed_gate: 'i18n:source-languages-declared' });
const alteredRtl = structuredClone(profile);
delete alteredRtl.rtl.scope;
check('negative:missing-rtl-boundary-fails', !evaluate({ ...productionInput, profile: alteredRtl })['i18n:rtl-out-of-scope-explicit'], { failed_gate: 'i18n:rtl-out-of-scope-explicit' });

const result = {
  schema: 'wt-i18n-boundary-verification.v1',
  completed: true,
  requirements: ['WT-NFR-ENV-01'],
  source: 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/config/i18n-profile.json',
  counts: { source_calls: sourceCalls, unique_messages: messages.length, untranslated_cjk: untranslatedCjk.length },
  rows,
  failed: rows.filter(row => !row.pass).length,
};
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify(result, null, 2));
if (result.failed) process.exitCode = 1;

function cjkFixtureFails(line) {
  const cjk = /[\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Han}]/u;
  const gettext = /\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\s*\(/;
  const phpOutput = /\b(?:echo|print)\b/.test(line) && cjk.test(line) && !gettext.test(line);
  const rawHtml = />[^<]*[\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Han}][^<]*</u.test(line.replace(/<\?php.*?\?>/g, ''));
  return phpOutput || rawHtml || hasUntranslatedCjkAttribute(line);
}

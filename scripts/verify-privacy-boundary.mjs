import crypto from 'node:crypto';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const roots = [
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt',
  'docs/research/2026-09-08-content-faces/plugin',
];
const extensions = new Set(['.css', '.html', '.js', '.json', '.mjs', '.php']);
const rules = [
  { id: 'ga4-measurement-id', category: 'tracking-id', pattern: /\bG-[A-Z0-9]{8,}\b/u },
  { id: 'google-tag-manager-id', category: 'tracking-id', pattern: /\bGTM-[A-Z0-9]{5,}\b/u },
  { id: 'universal-analytics-id', category: 'tracking-id', pattern: /\bUA-\d{4,}-\d+\b/u },
  { id: 'google-ads-id', category: 'tracking-id', pattern: /\bAW-\d{6,}\b/u },
  { id: 'adsense-publisher-id', category: 'tracking-id', pattern: /\bca-pub-\d{8,}\b/u },
  { id: 'meta-pixel-init', category: 'tracking-id', pattern: /\bfbq\s*\(\s*['"]init['"]\s*,\s*['"][^'"]+['"]/u },
  { id: 'vendor-ad-script', category: 'ad-code', pattern: /<script\b[^>]*\bsrc\s*=\s*['"][^'"]*(?:googletagmanager|google-analytics|googlesyndication|doubleclick)\b[^'"]*['"]/iu },
  { id: 'adsbygoogle-markup', category: 'ad-code', pattern: /<(?:ins|script)\b[^>]*(?:adsbygoogle|googlesyndication)[^>]*>/iu },
];

function walk(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
    const absolute = path.join(directory, entry.name);
    if (entry.isDirectory()) return walk(absolute);
    return entry.isFile() && extensions.has(path.extname(entry.name)) ? [absolute] : [];
  });
}
function scan(files) {
  const findings = [];
  for (const file of files) {
    const source = fs.readFileSync(file, 'utf8');
    for (const rule of rules) if (rule.pattern.test(source)) findings.push({ file: path.relative(root, file), rule: rule.id, category: rule.category });
  }
  return findings;
}
function fixture(name, source, expectedRule) {
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'wt-privacy-boundary-'));
  try {
    const file = path.join(directory, 'fixture.php');
    fs.writeFileSync(file, source);
    const findings = scan([file]);
    return { name, pass: expectedRule === null ? findings.length === 0 : findings.some(row => row.rule === expectedRule) };
  } finally {
    fs.rmSync(directory, { recursive: true, force: true });
  }
}

const files = roots.flatMap(relative => walk(path.join(root, relative))).sort();
const findings = scan(files);
const sourceDigests = Object.fromEntries(files.map(file => [path.relative(root, file), crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex')]));
const rows = [
  { name: 'privacy:production-tracking-ids-zero', pass: findings.every(row => row.category !== 'tracking-id'), detail: findings.filter(row => row.category === 'tracking-id') },
  { name: 'privacy:production-ad-code-zero', pass: findings.every(row => row.category !== 'ad-code'), detail: findings.filter(row => row.category === 'ad-code') },
  fixture('negative:ga4-measurement-id-fails', '<?php $id = "G-ABC12345";', 'ga4-measurement-id'),
  fixture('negative:tag-manager-id-fails', '<script>const id="GTM-ABC12";</script>', 'google-tag-manager-id'),
  fixture('negative:adsense-publisher-id-fails', '<div data-client="ca-pub-12345678"></div>', 'adsense-publisher-id'),
  fixture('negative:ad-script-fails', '<script src="https://example.googlesyndication.com/ads.js"></script>', 'vendor-ad-script'),
  fixture('negative:ad-markup-fails', '<ins class="adsbygoogle"></ins>', 'adsbygoogle-markup'),
  fixture('positive:contract-without-runtime-values-passes', '<?php $contract = ["tracking_owner" => "plugin", "ad_owner" => "external"];', null),
];
const report = {
  schema: 'wt-privacy-boundary-verification.v1', requirements: ['WT-NFR-PRIV-01'],
  completed: rows.every(row => row.pass), scannedRoots: roots, scannedFileCount: files.length,
  scopeBoundary: 'Current HELIX WT deliverable only. Legacy AGENT NEO theme/plugin roots are read-only reference assets and are excluded by the repository work boundary.',
  rules: rules.map(({ id, category }) => ({ id, category })), sourceDigests,
  rows, failed: rows.filter(row => !row.pass).length,
};
const output = path.join(root, 'docs/research/2026-09-13-privacy-boundary/verify.json');
fs.mkdirSync(path.dirname(output), { recursive: true });
fs.writeFileSync(output, `${JSON.stringify(report, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, scannedFiles: files.length, checks: rows.length, failed: report.failed }));
if (!report.completed) process.exitCode = 1;

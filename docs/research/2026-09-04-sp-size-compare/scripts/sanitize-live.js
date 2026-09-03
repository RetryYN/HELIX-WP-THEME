// 実運用サイト計測の生 JSON から、公開リポに置けない項目（URL・本文/見出し抜粋・実クラス名）を落とす。
const fs = require('fs');
const [inFile, outFile] = process.argv.slice(2);
const raw = JSON.parse(fs.readFileSync(inFile, 'utf8'));
const DROP = new Set(['url', 'text', 'selector', 'contentContainer', 'heroTag']);
const strip = (v) => {
  if (Array.isArray(v)) return v.map(strip);
  if (v && typeof v === 'object') { const o = {}; for (const [k, x] of Object.entries(v)) { if (DROP.has(k)) continue; o[k] = strip(x); } return o; }
  return v;
};
const out = strip(raw);
out.note = 'live-site measurement (GET only); url / text / class names removed for public repository';
fs.writeFileSync(outFile, JSON.stringify(out, null, 2));

#!/usr/bin/env node
// aggregate.mjs — measure.mjs の results/*.json を pattern 別に集計し summary.json / summary.md を出す。
//
// 使い方: node aggregate.mjs --out ./survey        (results/ を読み、summary.json / summary.md を書く)
//         環境変数 SURVEY_OUT でも指定可。--results <dir> で results ディレクトリを直接指定。

import fs from "node:fs";
import path from "node:path";

const args = {};
for (let i = 2; i < process.argv.length; i++) { const k = process.argv[i]; if (k.startsWith("--")) { const v = process.argv[i + 1]; if (v === undefined || v.startsWith("--")) args[k.slice(2)] = true; else { args[k.slice(2)] = v; i++; } } }
const OUT = path.resolve(args.out || process.env.SURVEY_OUT || "./survey");
const RESULTS = path.resolve(args.results || path.join(OUT, "results"));

const files = fs.readdirSync(RESULTS).filter((f) => f.endsWith(".json"));
const results = files.map((f) => JSON.parse(fs.readFileSync(path.join(RESULTS, f), "utf8")));

// ---------- 統計ヘルパ ----------
const nums = (a) => a.filter((v) => typeof v === "number" && Number.isFinite(v)).sort((x, y) => x - y);
const q = (sorted, p) => { if (!sorted.length) return null; const i = (sorted.length - 1) * p; const lo = Math.floor(i), hi = Math.ceil(i); return Math.round((sorted[lo] + (sorted[hi] - sorted[lo]) * (i - lo)) * 100) / 100; };
const stat = (arr) => { const s = nums(arr); if (!s.length) return null; const m = new Map(); for (const v of s) m.set(v, (m.get(v) || 0) + 1); const modeE = Array.from(m.entries()).sort((a, b) => b[1] - a[1])[0]; return { n: s.length, min: s[0], q1: q(s, 0.25), median: q(s, 0.5), q3: q(s, 0.75), max: s[s.length - 1], mode: modeE[0], modeCount: modeE[1] }; };
const rate = (arr) => { const v = arr.filter((x) => typeof x === "boolean"); return v.length ? { n: v.length, rate: Math.round((v.filter(Boolean).length / v.length) * 100) / 100 } : null; };
const freq = (arr, top = 10) => { const m = new Map(); for (const v of arr) { if (v == null || v === "") continue; m.set(v, (m.get(v) || 0) + 1); } return Array.from(m.entries()).sort((a, b) => b[1] - a[1]).slice(0, top).map(([value, count]) => ({ value, count })); };
const dist = (arr) => { const s = nums(arr); const m = new Map(); for (const v of s) m.set(v, (m.get(v) || 0) + 1); return Array.from(m.entries()).sort((a, b) => b[1] - a[1]).map(([value, count]) => ({ value, count, share: Math.round((count / s.length) * 100) / 100 })); };

// ---------- 色 ----------
function parseColor(c) {
  if (!c || typeof c !== "string") return null;
  let m = /rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*([\d.]+))?\s*\)/.exec(c);
  if (m) { if (m[4] !== undefined && parseFloat(m[4]) === 0) return null; return [+m[1], +m[2], +m[3]]; }
  m = /^#([0-9a-f]{6})$/i.exec(c);
  if (m) { const n = parseInt(m[1], 16); return [n >> 16, (n >> 8) & 255, n & 255]; }
  return null;
}
function toHsl([r, g, b]) {
  r /= 255; g /= 255; b /= 255; const max = Math.max(r, g, b), min = Math.min(r, g, b); const l = (max + min) / 2; let h = 0, s = 0;
  if (max !== min) { const d = max - min; s = l > 0.5 ? d / (2 - max - min) : d / (max + min); switch (max) { case r: h = (g - b) / d + (g < b ? 6 : 0); break; case g: h = (b - r) / d + 2; break; default: h = (r - g) / d; } h /= 6; }
  return { h: Math.round(h * 360), s: Math.round(s * 100), l: Math.round(l * 100) };
}
const hex = ([r, g, b]) => "#" + [r, g, b].map((v) => v.toString(16).padStart(2, "0")).join("");
const hueName = (h, s, l) => { if (l >= 95) return "white"; if (l <= 8) return "black"; if (s < 10) return "gray"; const names = ["red", "orange", "yellow", "lime", "green", "teal", "cyan", "azure", "blue", "violet", "magenta", "rose"]; return names[Math.floor(((h + 15) % 360) / 30)]; };
const lightBand = (l) => (l < 20 ? "dark" : l < 45 ? "mid-dark" : l < 70 ? "mid" : l < 90 ? "light" : "near-white");
const satBand = (s) => (s < 10 ? "gray" : s < 40 ? "muted" : s < 75 ? "moderate" : "vivid");

function colorSummary(colors) {
  const parsed = colors.map(parseColor).filter(Boolean);
  const hsl = parsed.map(toHsl);
  return {
    n: parsed.length,
    top: freq(parsed.map(hex), 12),
    hue: freq(hsl.map((c) => hueName(c.h, c.s, c.l)), 12),
    saturation: freq(hsl.map((c) => satBand(c.s))),
    lightness: freq(hsl.map((c) => lightBand(c.l))),
    hueStat: stat(hsl.filter((c) => c.s >= 10).map((c) => c.h)),
    satStat: stat(hsl.map((c) => c.s)),
    lightStat: stat(hsl.map((c) => c.l)),
  };
}

// ---------- 1 viewport の抽出 ----------
const g = (o, p) => p.split(".").reduce((x, k) => (x == null ? undefined : x[k]), o);
function extract(vpRes) {
  const d = vpRes && vpRes.data; if (!d) return null;
  const fam = (s) => (s || "").split(",")[0].replace(/["']/g, "").trim();
  return {
    // typography
    bodyFontSize: g(d, "typography.bodyText.fontSize"), bodyLineHeight: g(d, "typography.bodyText.lineHeightRatio"), bodyLetterSpacing: g(d, "typography.bodyText.letterSpacing"),
    bodyFamilyFirst: fam(g(d, "typography.bodyText.fontFamily") || g(d, "typography.body.fontFamily")), loadedFonts: g(d, "typography.loadedFonts") || [],
    htmlFontSize: g(d, "typography.html.fontSize"),
    h1Size: g(d, "typography.headings.h1.fontSize"), h2Size: g(d, "typography.headings.h2.fontSize"), h3Size: g(d, "typography.headings.h3.fontSize"),
    h1Weight: g(d, "typography.headings.h1.fontWeight"), h2Weight: g(d, "typography.headings.h2.fontWeight"),
    containerInner: g(d, "typography.container.innerWidth"), containerPadX: g(d, "typography.container.paddingLeft"),
    paragraphGap: g(d, "typography.bodyText.marginBottom"),
    headingMarginTop: g(d, "spacing.headingMargin.top"), headingMarginBottom: g(d, "spacing.headingMargin.bottom"),
    // colors
    bodyBg: g(d, "colors.bodyBg"), sectionBg: g(d, "colors.sectionBg"), textColor: g(d, "colors.text"), linkColor: g(d, "colors.link"), buttonBg: g(d, "colors.buttonBg"), buttonText: g(d, "colors.buttonText"),
    topColors: (g(d, "colors.topColors") || []).map((c) => c.hex),
    // components
    headerHeight: g(d, "components.header.height"), stickyHeader: g(d, "components.stickyHeader"), bottomFixed: g(d, "components.bottomFixed"), hamburger: g(d, "components.hamburger"), toc: g(d, "components.toc"), breadcrumb: g(d, "components.breadcrumb"),
    heroHeight: g(d, "components.hero.height"), heroVideo: g(d, "components.hero.hasVideo"),
    btnHeight: g(d, "components.buttons.height"), btnPadX: g(d, "components.buttons.paddingX"), btnPadY: g(d, "components.buttons.paddingY"), btnRadius: g(d, "components.buttons.radius"), btnFontSize: g(d, "components.buttons.fontSize"), btnCount: g(d, "components.buttons.count"), ctaFirstView: g(d, "components.buttons.ctaInFirstView"),
    imageRadius: g(d, "components.imageRadius"), shadowUsed: g(d, "components.shadowUsed"), shadowCount: g(d, "components.shadowCount"), borderWidth: g(d, "components.borderWidth"),
    cardCount: g(d, "components.cards.count"), cardPadding: g(d, "components.cards.padding"), cardRadius: g(d, "components.cards.radius"), cardShadowRate: g(d, "components.cards.shadowRate"),
    // spacing
    sectionPadTop: g(d, "spacing.paddingTop"), sectionPadBottom: g(d, "spacing.paddingBottom"), sectionGapMax: g(d, "spacing.gapMax"),
    // motion
    animatedElements: g(d, "motion.animatedElements"), transitionElements: g(d, "motion.transitionElements"), keyframes: g(d, "motion.keyframes"),
    scrollDriven: g(d, "motion.scrollDrivenAnimation"), reducedMotion: g(d, "motion.reducedMotionQuery"), raf: g(d, "motion.raf"), io: g(d, "motion.intersectionObserver"), scrollListeners: g(d, "motion.scrollListeners"),
    canvas: (g(d, "motion.canvas") || 0) > 0, webgl: g(d, "motion.webgl"), videoAutoplay: g(d, "motion.videoAutoplay"), lottie: g(d, "motion.lottie"), gsap: g(d, "motion.gsap"),
    usesMotion: ((g(d, "motion.keyframes") || 0) > 0) || ((g(d, "motion.animatedElements") || 0) > 0) || (g(d, "motion.io") || 0) > 0 || !!g(d, "motion.webgl"),
    // mobile
    horizontalScroll: g(d, "mobile.horizontalScroll"), smallTapRate: g(d, "mobile.smallTapRate"), bodyFontBelow16: g(d, "mobile.bodyFontBelow16"), firstViewTextChars: g(d, "mobile.firstViewTextChars"),
    // perf
    transferKB: g(d, "perf.transferBytes") != null ? Math.round(g(d, "perf.transferBytes") / 1024) : null, requests: g(d, "perf.requests"), imageMPx: g(d, "perf.imageTotalPx") != null ? Math.round(g(d, "perf.imageTotalPx") / 1e5) / 10 : null, fontFiles: g(d, "perf.fontFiles"), fontKB: g(d, "perf.fontBytes") != null ? Math.round(g(d, "perf.fontBytes") / 1024) : null, lcp: g(d, "perf.lcp"), domNodes: g(d, "perf.domNodes"),
    isWordPress: g(d, "meta.isWordPress"), generator: g(d, "meta.generator"),
  };
}

const NUM_KEYS = ["bodyFontSize", "bodyLineHeight", "htmlFontSize", "h1Size", "h2Size", "h3Size", "containerInner", "containerPadX", "paragraphGap", "headingMarginTop", "headingMarginBottom", "headerHeight", "heroHeight", "btnHeight", "btnPadX", "btnPadY", "btnRadius", "btnFontSize", "btnCount", "ctaFirstView", "imageRadius", "shadowCount", "borderWidth", "cardCount", "cardPadding", "cardRadius", "cardShadowRate", "sectionPadTop", "sectionPadBottom", "sectionGapMax", "animatedElements", "transitionElements", "keyframes", "raf", "io", "scrollListeners", "smallTapRate", "firstViewTextChars", "transferKB", "requests", "imageMPx", "fontFiles", "fontKB", "lcp", "domNodes"];
const BOOL_KEYS = ["stickyHeader", "bottomFixed", "hamburger", "toc", "breadcrumb", "heroVideo", "shadowUsed", "scrollDriven", "reducedMotion", "canvas", "webgl", "videoAutoplay", "lottie", "gsap", "usesMotion", "horizontalScroll", "bodyFontBelow16", "isWordPress"];

function aggregateGroup(rows, vp) {
  const xs = rows.map((r) => extract(r[vp])).filter(Boolean);
  const out = { n: xs.length, numeric: {}, rates: {}, distributions: {}, fonts: {}, colors: {} };
  for (const k of NUM_KEYS) out.numeric[k] = stat(xs.map((x) => x[k]));
  for (const k of BOOL_KEYS) out.rates[k] = rate(xs.map((x) => x[k]));
  out.distributions.btnRadius = dist(xs.map((x) => x.btnRadius));
  out.distributions.imageRadius = dist(xs.map((x) => x.imageRadius));
  out.distributions.cardRadius = dist(xs.map((x) => x.cardRadius));
  out.distributions.bodyFontSize = dist(xs.map((x) => x.bodyFontSize));
  out.distributions.bodyLetterSpacing = freq(xs.map((x) => x.bodyLetterSpacing));
  out.fonts.bodyFamilyFirst = freq(xs.map((x) => x.bodyFamilyFirst));
  out.fonts.loaded = freq(xs.flatMap((x) => x.loadedFonts));
  out.fonts.generator = freq(xs.map((x) => x.generator));
  out.colors.background = colorSummary(xs.flatMap((x) => [x.bodyBg, x.sectionBg]));
  out.colors.text = colorSummary(xs.map((x) => x.textColor));
  out.colors.link = colorSummary(xs.map((x) => x.linkColor));
  out.colors.button = colorSummary(xs.map((x) => x.buttonBg));
  out.colors.screenTop = colorSummary(xs.flatMap((x) => x.topColors));
  out.colors.accent = colorSummary(xs.flatMap((x) => [x.linkColor, x.buttonBg]).filter((c) => { const p = parseColor(c); if (!p) return false; const h = toHsl(p); return h.s >= 20 && h.l > 10 && h.l < 90; }));
  return out;
}

const byPattern = {};
for (const r of results) (byPattern[r.pattern || "unknown"] ||= []).push(r);
const summary = { generatedAt: new Date().toISOString(), resultsDir: path.relative(OUT, RESULTS) || ".", total: results.length, errors: results.filter((r) => r.error).map((r) => ({ id: r.id, pattern: r.pattern, kind: r.error.kind, sp: !!(r.sp && r.sp.data), pc: !!(r.pc && r.pc.data) })), elapsed: stat(results.map((r) => r.elapsedMs)), patterns: {} };
const ok = results.filter((r) => (r.sp && r.sp.data) || (r.pc && r.pc.data));
summary.patterns.__all__ = { count: ok.length, sp: aggregateGroup(ok, "sp"), pc: aggregateGroup(ok, "pc") };
for (const [p, rows] of Object.entries(byPattern)) { const okRows = rows.filter((r) => (r.sp && r.sp.data) || (r.pc && r.pc.data)); summary.patterns[p] = { count: okRows.length, ids: okRows.map((r) => r.id), sp: aggregateGroup(okRows, "sp"), pc: aggregateGroup(okRows, "pc") }; }
fs.writeFileSync(path.join(OUT, "summary.json"), JSON.stringify(summary, null, 2));

// ---------- Markdown ----------
const f = (v) => (v == null ? "-" : typeof v === "number" ? String(v) : String(v));
const statCell = (s) => (s ? `${f(s.median)} (${f(s.q1)}–${f(s.q3)}) mode ${f(s.mode)}×${s.modeCount} n=${s.n}` : "-");
const rateCell = (r) => (r ? `${Math.round(r.rate * 100)}% (n=${r.n})` : "-");
const md = [];
md.push(`# Survey summary`, ``, `- generated: ${summary.generatedAt}`, `- results: ${summary.total} (errors: ${summary.errors.length})`, `- elapsed per URL: ${statCell(summary.elapsed)} ms`, ``);
if (summary.errors.length) { md.push(`## Errors`, ``, `| id | pattern | kind | sp | pc |`, `|---|---|---|---|---|`); for (const e of summary.errors) md.push(`| ${e.id} | ${e.pattern} | ${e.kind} | ${e.sp ? "ok" : "-"} | ${e.pc ? "ok" : "-"} |`); md.push(``); }
const NUM_LABEL = { bodyFontSize: "本文 font-size", bodyLineHeight: "本文 line-height 比", h1Size: "h1 size", h2Size: "h2 size", h3Size: "h3 size", containerInner: "本文コンテナ内幅", containerPadX: "コンテナ左右 padding", paragraphGap: "段落 margin-bottom", headingMarginTop: "見出し margin-top", headingMarginBottom: "見出し margin-bottom", headerHeight: "ヘッダー高", heroHeight: "ヒーロー高", btnHeight: "ボタン高", btnPadX: "ボタン padding-x", btnPadY: "ボタン padding-y", btnRadius: "ボタン radius", btnFontSize: "ボタン font-size", ctaFirstView: "FV 内 CTA 数", imageRadius: "画像 radius", shadowCount: "影の要素数", borderWidth: "ボーダー幅", cardCount: "カード数", cardPadding: "カード padding", cardRadius: "カード radius", sectionPadTop: "セクション padding-top", sectionPadBottom: "セクション padding-bottom", sectionGapMax: "セクション間隔(最大側)", animatedElements: "animation 要素", transitionElements: "transition 要素", keyframes: "@keyframes 数", raf: "rAF 呼出", io: "IntersectionObserver 生成", scrollListeners: "scroll 系 listener", smallTapRate: "44px 未満タップ率", firstViewTextChars: "FV 本文文字数", transferKB: "転送 KB", requests: "リクエスト数", imageMPx: "画像総 Mpx", fontFiles: "webfont 数", fontKB: "webfont KB", lcp: "LCP ms", domNodes: "DOM ノード" };
const RATE_LABEL = { stickyHeader: "sticky ヘッダー率", bottomFixed: "SP 下部固定率", hamburger: "ハンバーガー率", toc: "目次率", breadcrumb: "パンくず率", heroVideo: "ヒーロー動画率", shadowUsed: "影の使用率", usesMotion: "動きの採用率", scrollDriven: "scroll-driven animation", reducedMotion: "reduced-motion 対応率", canvas: "canvas 率", webgl: "WebGL 率", videoAutoplay: "autoplay 動画率", lottie: "Lottie 率", gsap: "GSAP 率", horizontalScroll: "横スクロール発生率", bodyFontBelow16: "本文 16px 未満率", isWordPress: "WordPress 率" };
for (const [p, grp] of Object.entries(summary.patterns)) {
  md.push(`## pattern: ${p} (n=${grp.count})`, ``);
  if (grp.ids) md.push(`ids: ${grp.ids.join(", ")}`, ``);
  md.push(`### 数値 (median (q1–q3) mode×count)`, ``, `| 指標 | SP | PC |`, `|---|---|---|`);
  for (const k of NUM_KEYS) { if (!NUM_LABEL[k]) continue; md.push(`| ${NUM_LABEL[k]} | ${statCell(grp.sp.numeric[k])} | ${statCell(grp.pc.numeric[k])} |`); }
  md.push(``, `### 採用率`, ``, `| 部品 | SP | PC |`, `|---|---|---|`);
  for (const k of BOOL_KEYS) { if (!RATE_LABEL[k]) continue; md.push(`| ${RATE_LABEL[k]} | ${rateCell(grp.sp.rates[k])} | ${rateCell(grp.pc.rates[k])} |`); }
  md.push(``, `### 角丸の分布 (PC)`, ``, `- ボタン: ${grp.pc.distributions.btnRadius.map((d) => `${d.value}px×${d.count}`).join(", ") || "-"}`, `- 画像: ${grp.pc.distributions.imageRadius.map((d) => `${d.value}px×${d.count}`).join(", ") || "-"}`, `- カード: ${grp.pc.distributions.cardRadius.map((d) => `${d.value}px×${d.count}`).join(", ") || "-"}`, ``);
  md.push(`### フォント (PC)`, ``, `- 本文 family 先頭: ${grp.pc.fonts.bodyFamilyFirst.map((x) => `${x.value}×${x.count}`).join(", ") || "-"}`, `- 読込済 webfont: ${grp.pc.fonts.loaded.map((x) => `${x.value}×${x.count}`).join(", ") || "-"}`, `- generator: ${grp.pc.fonts.generator.map((x) => `${x.value}×${x.count}`).join(", ") || "-"}`, ``);
  md.push(`### 色 (PC)`, ``);
  for (const [name, c] of Object.entries(grp.pc.colors)) {
    if (!c || !c.n) { md.push(`- ${name}: -`); continue; }
    md.push(`- ${name} (n=${c.n}): top ${c.top.slice(0, 6).map((x) => `${x.value}×${x.count}`).join(", ")}; 色相 ${c.hue.map((x) => `${x.value}×${x.count}`).join(", ")}; 彩度 ${c.saturation.map((x) => `${x.value}×${x.count}`).join(", ")}; 明度 ${c.lightness.map((x) => `${x.value}×${x.count}`).join(", ")}`);
  }
  md.push(``);
}
fs.writeFileSync(path.join(OUT, "summary.md"), md.join("\n"));
console.log(`summary: ${results.length} results, ${Object.keys(byPattern).length} patterns -> ${path.join(OUT, "summary.json")}, summary.md`);

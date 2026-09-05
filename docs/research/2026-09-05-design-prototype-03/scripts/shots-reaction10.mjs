#!/usr/bin/env node
// shots-reaction10.mjs — 2026-09-06 PO 反応 17 回目（WT-EVT-0270〜0276）の撮影。
// 新規 22 枚: ヘッダー PC 5 型（center / two-rows / tel / band / overlay）+ SP 5 型（band / overlay / hamburger-cta / text-nav / center-logo）+ h2 numbox×h3 num 連動 ×2 + グラフ 5 型 ×2。
// 同名置換 98 枚（合計 120）: announce ×2（全幅化）、lp-line 2 型 ×2 + line-sticky ×2（アイコン）、tail-share-icons-row ×2 と footer 28 型 ×2（SNS グリフ）、
//   関連グリッドを含む shot（related 5 型・tail-order 3 型・tail-prevnext 2 型・tail-share-none・tail-author-none・axis-depth-tail 2 状態・axis-motion 2 状態 ×2 = 32 枚。fixture 投稿の除外）。
// 撮影は一時 dir → 予定集合との完全一致 → 退避 → 配置 → INDEX を try 内で原子的に置換 → 失敗時復元 → finally 清掃（reaction7〜9 と同方式）。
import fs from "node:fs";
import path from "node:path";
import { execFileSync } from "node:child_process";
import { createRequire } from "node:module";
const require = createRequire(process.env.NODE_PATH ? path.join(process.env.NODE_PATH, "x.js") : import.meta.url);
const { chromium } = require("playwright");

const args = Object.fromEntries(process.argv.slice(2).map((a, i, arr) => a.startsWith("--") ? [a.slice(2), arr[i + 1]] : null).filter(Boolean));
const BASE = args.base || "http://localhost:8086";
const OUT = path.resolve(args.out || "../results");
const ARTICLE = "/standing-desk-compare/";
const CATALOG = "/catalog-03/";
const LP = "/lp/";
fs.mkdirSync(OUT, { recursive: true });
const index = [];
const SP = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const PC = { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 };
const wt = (q) => "?wt=" + q;

function toJpeg(png, jpg) {
  execFileSync("ffmpeg", ["-y", "-loglevel", "error", "-i", png, "-vf", "scale='if(gte(iw,ih),min(1600,iw),-2)':'if(gte(iw,ih),-2,min(1600,ih))'", "-q:v", "5", jpg]);
  fs.unlinkSync(png);
}
// 同時実行保護（Astra 是正）: 同じ results/ に対する置換が並走しないよう O_EXCL でロックファイルを作る。
// 残っていれば前回が強制終了したと見なし、退避ディレクトリ（.reaction10-backup-*）の有無を確認してから手で消す運用にする。
const LOCK = path.join(OUT, ".reaction10.lock");
let lockFd = null;
try { lockFd = fs.openSync(LOCK, "wx"); fs.writeSync(lockFd, String(process.pid)); } catch (e) { throw new Error(`ロックファイルが存在します（別の撮影が実行中か、前回が強制終了）。退避 dir を確認してから削除してください: ${LOCK}`); }
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction10-capture-"));
// 画像の読込を待ち、読込失敗（complete だが naturalWidth 0）や 3 秒のタイムアウトは撮影失敗として例外にする（Astra 是正: 成功扱いにしない）
const waitImgs = async (page, sel) => {
  const bad = await page.evaluate(async (sel) => {
    const root = sel ? document.querySelector(sel) : document; const imgs = Array.from(root ? root.querySelectorAll("img") : []);
    imgs.forEach((i) => { i.loading = "eager"; });
    const results = await Promise.all(imgs.map((i) => i.complete ? Promise.resolve(i.naturalWidth > 0 ? "ok" : "error") : new Promise((r) => { i.onload = () => r("ok"); i.onerror = () => r("error"); setTimeout(() => r("timeout"), 3000); })));
    return results.map((state, idx) => ({ state, src: (imgs[idx].currentSrc || imgs[idx].src || "").split("/").pop() })).filter((x) => x.state !== "ok");
  }, sel || null);
  if (bad.length) throw new Error(`画像の読込に失敗または待機超過: ${bad.map((b) => `${b.src}(${b.state})`).join(", ")}`);
};
async function save(page, name, meta, opts = {}) {
  const png = path.join(TMP, name + ".png"), jpg = path.join(TMP, name + ".jpg");
  if (opts.selector) {
    const el = page.locator(opts.selector).first();
    await el.waitFor({ state: "visible", timeout: 8000 });
    if (opts.clipHeight) {
      // shots.mjs と同じ: viewport 外の要素は lazy 画像が未読込のまま写るため、スクロールして読込を待ち、fullPage + clip で切り出す（srcset 再読込のため 2 回撮る）
      await el.scrollIntoViewIfNeeded();
      await waitImgs(page, opts.selector);
      await page.waitForTimeout(200);
      const b = await el.boundingBox();
      const sy = await page.evaluate(() => window.scrollY);
      const clip = { x: b.x, y: b.y + sy, width: b.width, height: Math.min(opts.clipHeight, b.height) };
      await page.screenshot({ path: png, fullPage: true, clip });
      await waitImgs(page, opts.selector);
      await page.waitForTimeout(300);
      await page.screenshot({ path: png, fullPage: true, clip });
    } else {
      await el.scrollIntoViewIfNeeded();
      await waitImgs(page, opts.selector);
      await page.waitForTimeout(150);
      await el.screenshot({ path: png });
    }
  } else if (opts.viewportOnly) { await waitImgs(page, null); await page.screenshot({ path: png }); }
  else { await waitImgs(page, null); await page.screenshot({ path: png, fullPage: true }); }
  toJpeg(png, jpg);
  if (!fs.statSync(jpg).size) throw new Error(`空の JPEG: ${jpg}`);
  index.push({ file: name + ".jpg", ...meta });
  console.log("shot", name);
}
async function open(ctx, url) {
  const page = await ctx.newPage();
  await page.goto(BASE + url, { waitUntil: "networkidle" });
  await page.waitForTimeout(300);
  return page;
}
const assertBody = async (p, cls) => { if (!(await p.evaluate((c) => document.body.classList.contains(c), cls))) throw new Error(`body.${cls} が付いていません`); };

const HEADER_PC = ["center", "two-rows", "tel", "band", "overlay"];
const HEADER_SP_HEADER = ["band", "overlay"];
const HEADER_SP_AXIS = [["hamburger-cta", "cta"], ["text-nav", "text-nav"], ["center-logo", "center-logo"]];
const GRAPHS = ["grouped", "column", "score", "gauge", "radar"];
const FOOTER_AXES = [
  ["layout", ["sitemap", "single-row", "columns-3"]],
  ["above", ["none", "cta-band", "banner-row", "newsletter"]],
  ["legal", ["copyright-links", "copyright-only"]],
  ["extra", ["none", "sns", "sites", "badges", "address", "sns-sites", "sns-badges", "sns-address", "sites-badges", "sites-address", "badges-address", "sns-sites-badges", "sns-sites-address", "sns-badges-address", "sites-badges-address", "all"]],
  ["totop", ["off", "button"]],
  ["credit", ["text"]],
];
const RELATED = ["grid", "list", "rank", "carousel", "slider"];
const TAIL = [["order", ["related-author-share-cta", "cta-related-author-share", "related-cta-author"]], ["prevnext", ["off", "thumb"]], ["share", ["none", "icons-row"]], ["author", ["none"]]];

let browser = null;
async function main() {
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  if (browser) await browser.close();
  browser = await chromium.launch();
  const ctx = await browser.newContext(cfg);
  let p;
  // ヘッダー
  const headerTypes = dev === "pc" ? HEADER_PC : HEADER_SP_HEADER;
  for (const v of headerTypes) {
    const q = v === "overlay" ? "header:overlay,eyecatch:hero" : `header:${v}`;
    p = await open(ctx, ARTICLE + wt(q));
    await assertBody(p, `wt-header-${v}`);
    if (!(await p.evaluate((v) => !!document.querySelector(`.wt-header--${v}`), v))) throw new Error(`header:${v} の部品が描画されていません`);
    // overlay は透過ヘッダーなので hero を含む viewport で撮る（要素単体では背景が写らない）
    if (v === "overlay") await save(p, `header-${v}-${dev}`, { face: "article", part: "header", variant: v, dev }, { viewportOnly: true });
    else await save(p, `header-${v}-${dev}`, { face: "article", part: "header", variant: v, dev }, { selector: ".wt-header" });
    await p.close();
  }
  if (dev === "sp") {
    for (const [name, axis] of HEADER_SP_AXIS) {
      p = await open(ctx, ARTICLE + wt(`sp:${axis}`));
      await assertBody(p, `wt-sp-${axis}`);
      await save(p, `header-${name}-${dev}`, { face: "article", part: "header", variant: name, dev }, { selector: ".wt-header" });
      await p.close();
    }
  }
  // announce（全幅化、同名置換）
  p = await open(ctx, ARTICLE + wt("header:announce"));
  await save(p, `header-announce-${dev}`, { face: "article", part: "header", variant: "announce", dev }, { selector: ".wt-header" });
  await p.close();
  // numbox × num（新規）とグラフ 5 型（新規）
  p = await open(ctx, CATALOG);
  await save(p, `h2-numbox-h3num-${dev}`, { face: "article", part: "h2", variant: "numbox + h3 num", dev }, { selector: "#cat-h2-numbox-h3num" });
  for (const k of GRAPHS) {
    const ok = await p.evaluate((sel) => { const f = document.querySelector(sel + " figure.wt-graph"); return !!(f && f.querySelector("figcaption") && f.querySelector("table.wt-graph__data")); }, "#cat-graph-" + k);
    if (!ok) throw new Error(`#cat-graph-${k} に figure / figcaption / data table が揃っていません`);
    await save(p, `graph-${k}-${dev}`, { face: "article", part: "graph", variant: k, dev }, { selector: "#cat-graph-" + k });
  }
  await p.close();
  // LP LINE（同名置換）
  for (const v of ["button", "qr"]) {
    p = await open(ctx, LP + wt(`lp_sections:extended,lp_line:${v}`));
    await assertBody(p, `wt-lp-line-${v}`);
    if (!(await p.evaluate(() => !!document.querySelector(".wt-lp__section--line .wt-lp-line__mark .wt-i--bubble")))) throw new Error("LINE の吹き出しグリフが描画されていません");
    await save(p, `lp-line-${v}-${dev}`, { face: "lp", part: "lp-line", variant: v, dev }, { selector: ".wt-lp__section--line" });
    await p.close();
  }
  p = await open(ctx, LP + wt("lp_fixed:line-sticky"));
  if (!(await p.locator(".wt-lp-fixed--line-sticky").first().isVisible())) throw new Error("lp_fixed:line-sticky が表示されていません");
  await save(p, `lp-fixed-line-sticky-${dev}`, { face: "lp", part: "lp-fixed", variant: "line-sticky", dev }, { viewportOnly: true });
  await p.close();
  // footer（SNS グリフ、同名置換）
  for (const [part, variants] of FOOTER_AXES) {
    for (const variant of variants) {
      p = await open(ctx, ARTICLE + wt(`footer_${part}:${variant}`));
      await save(p, `footer-${part}-${variant}-${dev}`, { face: "footer", part: `footer-${part}`, variant, dev }, { selector: ".wt-footer", clipHeight: 1600 });
      await p.close();
    }
  }
  // 関連グリッドを含む shot（fixture 除外、同名置換）
  for (const v of RELATED) {
    p = await open(ctx, ARTICLE + wt("related:" + v));
    if (await p.evaluate(() => Array.from(document.querySelectorAll(".wt-related .wt-rcard")).some((c) => /Uncategorized/.test(c.textContent)))) throw new Error("関連カードに既定カテゴリの fixture 投稿が残っています");
    await save(p, `related-${v}-${dev}`, { face: "article", part: "related", variant: v, dev }, { selector: v === "slider" ? ".wt-tail__slot--related" : ".wt-tail" });
    await p.close();
  }
  for (const [part, variants] of TAIL) {
    for (const variant of variants) {
      p = await open(ctx, ARTICLE + wt(`tail_${part}:${variant}`));
      const tailSelector = part === "order" || variant === "none" || variant === "off" ? ".wt-tail" : `.wt-tail__slot--${part}`;
      await save(p, `tail-${part}-${variant}-${dev}`, { face: "article", part: `article-tail-${part}`, variant, dev }, { selector: tailSelector, clipHeight: 1600 });
      await p.close();
    }
  }
  for (const [state, q] of [["off", "depth:0"], ["on", "depth:1"]]) {
    p = await open(ctx, ARTICLE + wt(q));
    await save(p, `axis-depth-tail-${state}-${dev}`, { face: "article", part: "axis-depth", variant: q, dev }, { selector: ".wt-tail" });
    await p.close();
  }
  for (const [state, q] of [["off", "motion:off"], ["on", "motion:on"]]) {
    p = await open(ctx, ARTICLE + wt(q));
    const y = await p.evaluate(() => document.querySelector(".wt-tail").getBoundingClientRect().top + scrollY - 200);
    await p.evaluate((y) => scrollTo(0, y), y);
    await p.waitForTimeout(60);
    await save(p, `axis-motion-${state}-${dev}`, { face: "article", part: "axis-motion", variant: q + " (60ms after scroll)", dev }, { viewportOnly: true });
    await p.close();
  }
  await ctx.close();
}
} finally {
  if (browser) await browser.close();
}

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
const NEW_FILES = [
  ...HEADER_PC.map((v) => `header-${v}-pc.jpg`), ...HEADER_SP_HEADER.map((v) => `header-${v}-sp.jpg`), ...HEADER_SP_AXIS.map(([n]) => `header-${n}-sp.jpg`),
  "h2-numbox-h3num-sp.jpg", "h2-numbox-h3num-pc.jpg", ...GRAPHS.flatMap((k) => [`graph-${k}-sp.jpg`, `graph-${k}-pc.jpg`]),
];
const REPLACE_FILES = ["header-announce", "lp-line-button", "lp-line-qr", "lp-fixed-line-sticky",
  ...FOOTER_AXES.flatMap(([part, vs]) => vs.map((v) => `footer-${part}-${v}`)), ...RELATED.map((v) => `related-${v}`),
  ...TAIL.flatMap(([part, vs]) => vs.map((v) => `tail-${part}-${v}`)), "axis-depth-tail-off", "axis-depth-tail-on", "axis-motion-off", "axis-motion-on",
].flatMap((k) => [`${k}-sp.jpg`, `${k}-pc.jpg`]);
const planned = new Set([...NEW_FILES, ...REPLACE_FILES]);
if (planned.size !== NEW_FILES.length + REPLACE_FILES.length) throw new Error("予定集合に重複があります");
const known = new Set(existing.map((entry) => entry.file));
const notKnown = REPLACE_FILES.filter((f) => !known.has(f));
// 新規予定は初回は未登録、再実行（Astra 是正後の撮り直し）では登録済みになる。どちらも許容し、置換予定の未登録だけを前提違反とする
const alreadyKnown = NEW_FILES.filter((f) => known.has(f));
if (notKnown.length) throw new Error(`INDEX との前提が違います。置換予定だが未登録: ${notKnown.join(", ")}`);
console.log(`新規予定 ${NEW_FILES.length} 枚のうち登録済み（再実行）: ${alreadyKnown.length}`);
const captured = new Set(index.map((entry) => entry.file));
const missing = [...planned].filter((f) => !captured.has(f));
const unknown = [...captured].filter((f) => !planned.has(f));
if (missing.length || unknown.length || captured.size !== index.length) {
  throw new Error(`撮影集合が予定集合と一致しません（予定 ${planned.size} / 撮影 ${captured.size}）。欠落: ${missing.join(", ") || "なし"} / 予定外: ${unknown.join(", ") || "なし"}`);
}
for (const entry of index) {
  const tmp = path.join(TMP, entry.file);
  if (!fs.existsSync(tmp) || !fs.statSync(tmp).size) throw new Error(`一時ディレクトリ内の JPEG が見つからないか空です: ${tmp}`);
}
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction10-backup-"));
const backedUp = new Set();
const placed = [];
let keepBackup = false;
const replacement = new Map(index.map((entry) => [entry.file, entry]));
const added = index.filter((entry) => !known.has(entry.file));
const nextIndex = JSON.stringify(existing.map((entry) => replacement.get(entry.file) || entry).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction10.tmp";
try {
  for (const entry of index) {
    const target = path.join(OUT, entry.file);
    if (fs.existsSync(target)) { fs.renameSync(target, path.join(backupDir, entry.file)); backedUp.add(entry.file); }
  }
  for (const entry of index) { fs.renameSync(path.join(TMP, entry.file), path.join(OUT, entry.file)); placed.push(entry.file); }
  fs.writeFileSync(indexTmp, nextIndex);
  fs.renameSync(indexTmp, catalogFile);
} catch (error) {
  const restoreFailures = [];
  try { fs.rmSync(indexTmp, { force: true }); } catch (_) { /* 旧 INDEX は無傷 */ }
  for (const file of placed) {
    if (backedUp.has(file)) continue;
    try { fs.unlinkSync(path.join(OUT, file)); } catch (removeError) { restoreFailures.push({ file, error: String(removeError) }); }
  }
  for (const file of backedUp) {
    const backupPath = path.join(backupDir, file);
    if (!fs.existsSync(backupPath)) continue;
    try { fs.renameSync(backupPath, path.join(OUT, file)); } catch (restoreError) { restoreFailures.push({ file, error: String(restoreError) }); }
  }
  if (restoreFailures.length) { keepBackup = true; console.error(`画像の復元に失敗しました。退避ディレクトリを手動で確認してください: ${backupDir}`, restoreFailures); }
  throw error;
} finally {
  if (!keepBackup) {
    try { fs.rmSync(backupDir, { recursive: true, force: true }); } catch (cleanupError) { console.error(`退避ディレクトリの削除に失敗しました（配置済みの画像はそのまま）: ${backupDir}`, cleanupError); }
  }
}
console.log("reaction10 done", index.length, "entries", existing.length + added.length, "added", added.length);
}
try {
  await main();
} finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (cleanupError) { console.error(`一時ディレクトリの削除に失敗しました: ${TMP}`, cleanupError); }
  try { if (lockFd !== null) fs.closeSync(lockFd); fs.rmSync(LOCK, { force: true }); } catch (lockError) { console.error(`ロックファイルの削除に失敗しました: ${LOCK}`, lockError); }
}

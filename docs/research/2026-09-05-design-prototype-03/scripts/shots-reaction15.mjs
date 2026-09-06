#!/usr/bin/env node
// shots-reaction15.mjs — 2026-09-07 PO 反応 22 回目 WT-EVT-0292「サイドバーは普通置く」（段 10b: 既定 side_layout=right、HP は hero の下からサイドバー）の撮り直し + 新規撮影。
// 撮り直し（既定が変わって写真が変わるもの、同名置換）: 記事全長 article-full / 画面単位 article-screen-NN / 幅 width-* / HP 区間セット 5 種 home-sections-* / 固定ページ一覧 page-parts-full-* / HP owned セット side-home-owned-*。部品単体の写真（要素撮影）は本文列幅が同じ（--wt-content-max）ため撮り直さない。
// 新規（PC）: side-from（below-hero / top / top + left）、side-default（記事・固定ページの既定 first view）。
// 方式は reaction13（分割撮影・dev ごとにブラウザ再起動・ロック・予定集合照合・退避と原子的 INDEX 置換）。SP の全長は deviceScaleFactor 1（16384px 制限）。
import fs from "node:fs";
import path from "node:path";
import { execFileSync } from "node:child_process";
import { createRequire } from "node:module";
const require = createRequire(process.env.NODE_PATH ? path.join(process.env.NODE_PATH, "x.js") : import.meta.url);
const { chromium } = require("playwright");

const args = Object.fromEntries(process.argv.slice(2).map((a, i, arr) => a.startsWith("--") ? [a.slice(2), arr[i + 1]] : null).filter(Boolean));
const BASE = args.base || "http://localhost:8086";
const OUT = path.resolve(args.out || "../results");
const CATEGORY = "/category/topic-index/";
const EVENT = "/event/";
fs.mkdirSync(OUT, { recursive: true });
const index = [];
const SP = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const PC = { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 };
const wt = (q) => "?wt=" + q;
function toJpeg(png, jpg) {
  execFileSync("ffmpeg", ["-y", "-loglevel", "error", "-i", png, "-vf", "scale='if(gte(iw,ih),min(1600,iw),-2)':'if(gte(iw,ih),-2,min(1600,ih))'", "-q:v", "5", jpg]);
  fs.unlinkSync(png);
}
const LOCK = path.join(OUT, ".reaction15.lock");
let lockFd = null;
try { lockFd = fs.openSync(LOCK, "wx"); fs.writeSync(lockFd, String(process.pid)); } catch (e) { throw new Error(`ロックファイルが存在します（別の撮影が実行中か、前回が強制終了）。退避 dir を確認してから削除してください: ${LOCK}`); }
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction15-capture-"));
const waitImgs = async (page, sel) => {
  const bad = await page.evaluate(async (sel) => {
    const root = sel ? document.querySelector(sel) : document; const imgs = Array.from(root ? root.querySelectorAll("img") : []).filter((i) => i.getBoundingClientRect().width > 0);
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
    await el.scrollIntoViewIfNeeded();
    await waitImgs(page, opts.selector);
    await page.waitForTimeout(200);
    await el.screenshot({ path: png });
  } else if (opts.clipSelector) { // 既存 stage3 と同じ: 要素の上端から clipHeight まで
    const el = page.locator(opts.clipSelector).first(); await el.waitFor({ state: "visible", timeout: 8000 }); await el.scrollIntoViewIfNeeded(); await waitImgs(page, opts.clipSelector); await page.waitForTimeout(200);
    const box = await el.boundingBox(); const y = await page.evaluate(() => scrollY); await page.screenshot({ path: png, clip: { x: 0, y: box.y + y, width: page.viewportSize().width, height: Math.min(box.height, opts.clipHeight) }, fullPage: true });
  } else { await waitImgs(page, null); await page.screenshot({ path: png }); }
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
const assertVisible = async (p, sel) => { if (!(await p.evaluate((s) => { const el = document.querySelector(s); if (!el) return false; const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 && getComputedStyle(el).display !== "none"; }, sel))) throw new Error(`${sel} が表示されていません`); };
// 全長を viewport 分割で撮る（reaction9 方式）。分割数は撮影前に高さから決め、予定集合の照合に使う
const CHUNKS = {};
// 全長を viewport 分割で撮る（reaction9 方式）。rootSel の矩形を分割し、表示区間数（expectSections）を撮影前に照合する
async function chunked(browser, cfg, dev, url, key, meta, rootSel, sectionSel, expectSections) {
  const CHUNK = dev === "sp" ? 4000 : 1600;
  const ctx = await browser.newContext({ ...cfg, deviceScaleFactor: 1, viewport: { width: cfg.viewport.width, height: CHUNK } });
  const p = await open(ctx, url);
  const m = await p.evaluate(([sel, ssel]) => { const root = document.querySelector(sel); const r = root.getBoundingClientRect(); const vis = (el) => { const b = el.getBoundingClientRect(); return b.width > 0 && b.height > 0 && getComputedStyle(el).display !== "none"; }; return { top: r.top + scrollY, height: r.height, shown: ssel ? Array.from(root.querySelectorAll(ssel)).filter(vis).length : null }; }, [rootSel, sectionSel]);
  if (expectSections !== null && m.shown !== expectSections) throw new Error(`${key}: 表示区間が ${m.shown}（${expectSections} を期待）`);
  const chunks = Math.ceil(m.height / CHUNK); CHUNKS[`${key}-${dev}`] = chunks;
  for (let i = 0; i < chunks; i++) {
    await p.evaluate(([y]) => scrollTo(0, y), [m.top + i * CHUNK]); await p.waitForTimeout(250);
    await save(p, `${key}-${i + 1}-${dev}`, { face: meta.face, part: meta.part, variant: `${meta.variant} ${i + 1}/${chunks}`, dev });
  }
  await p.close(); await ctx.close();
}
const HOME = "/";
const PARTS = "/parts/";
const ARTICLE = "/standing-desk-compare/";
const HOME_SETS = { corporate: 9, service: 9, media: 7, "shop-school": 11, "school-org": 12 };
const WIDTHS = ["narrow", "default", "wide"];
const SIDE_FROM = [["side-from-below-hero", "side_from:below-hero", "below-hero（既定。hero の下からサイドバー）"], ["side-from-top", "side_from:top", "top（hero の横からサイドバー）"], ["side-from-top-left", "side_from:top,side_layout:left", "top + left（CV 狙いの左配置）"]];
const SCREENS = {};
let browser = null;
async function main() {
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  if (browser) await browser.close();
  browser = await chromium.launch();
  for (const [set, n] of Object.entries(HOME_SETS)) await chunked(browser, cfg, dev, HOME + wt(`home_sections:${set}`), `home-sections-${set}`, { face: "home", part: "home-sections", variant: set }, ".wt-home__sections", ":scope > .wt-home__section", n);
  await chunked(browser, cfg, dev, PARTS, "page-parts-full", { face: "page", part: "page-parts", variant: "15 パーツを並べた固定ページ" }, ".wt-page-parts", ":scope > .wt-part", 15);
  await chunked(browser, cfg, dev, HOME + wt("side_layout:right,side_set:owned,home_hero:text-only"), "side-home-owned", { face: "home", part: "side-set", variant: "owned（HP）" }, ".wt-side-layout", ":scope > .wt-side", dev === "pc" ? 1 : 1);
  // 記事全長（SP は DSF1 の別 context）
  const fullCtx = await browser.newContext({ ...cfg, deviceScaleFactor: 1 });
  let p = await open(fullCtx, ARTICLE); await assertBody(p, "wt-side-layout-right"); if (dev === "pc") await assertVisible(p, ".wt-side--right");
  await waitImgs(p, null); { const png = path.join(TMP, `article-full-${dev}.png`), jpg = path.join(TMP, `article-full-${dev}.jpg`); await p.screenshot({ path: png, fullPage: true }); toJpeg(png, jpg); if (!fs.statSync(jpg).size) throw new Error(`空の JPEG: ${jpg}`); index.push({ file: `article-full-${dev}.jpg`, face: "article", part: "full", variant: "default", dev }); console.log("shot", `article-full-${dev}`); }
  await p.close(); await fullCtx.close();
  const ctx = await browser.newContext(cfg);
  // 画面単位（reaction1 と同じ: viewport ごと、最大 10 画面）
  p = await open(ctx, ARTICLE);
  const vh = cfg.viewport.height, total = await p.evaluate(() => document.documentElement.scrollHeight); let n = 0;
  for (let i = 0, y = 0; y < total && i < 10; i++, y += vh) { await p.evaluate((y) => scrollTo(0, y), y); await p.waitForTimeout(150); await save(p, `article-screen-${String(i + 1).padStart(2, "0")}-${dev}`, { face: "article", part: "full-screens", variant: `default screen ${i + 1}`, dev }); n++; }
  SCREENS[dev] = n; await p.close();
  for (const preset of WIDTHS) { p = await open(ctx, ARTICLE + wt(`width:${preset}`)); await assertBody(p, `wt-width-${preset}`); await save(p, `width-${preset}-${dev}`, { face: "article", part: "width", variant: preset, dev }); await p.close(); }
  if (dev === "pc") {
    for (const [key, q, label] of SIDE_FROM) { p = await open(ctx, HOME + wt(q)); await assertBody(p, `wt-${q.split(",")[0].replace(":", "-")}`); await assertVisible(p, ".wt-side--right"); await save(p, `${key}-${dev}`, { face: "home", part: "side-from", variant: label, dev }); await p.close(); }
    p = await open(ctx, ARTICLE); await assertBody(p, "wt-side-layout-right"); await assertVisible(p, ".wt-side--right"); await save(p, `side-default-article-${dev}`, { face: "article", part: "side-layout", variant: "既定 right（?wt なし、見出し部の下から）", dev }); await p.close();
    p = await open(ctx, PARTS); await assertBody(p, "wt-side-layout-right"); await assertVisible(p, ".wt-side--right"); await save(p, `side-default-page-${dev}`, { face: "page", part: "side-layout", variant: "既定 right（固定ページ、?wt なし）", dev }); await p.close();
  }
  await ctx.close();
}
} finally { if (browser) await browser.close(); }

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
const perDev = (dev) => [
  "article-full", ...Array.from({ length: SCREENS[dev] || 0 }, (_, i) => `article-screen-${String(i + 1).padStart(2, "0")}`), ...WIDTHS.map((w) => `width-${w}`),
  ...(dev === "pc" ? [...SIDE_FROM.map(([k]) => k), "side-default-article", "side-default-page"] : []),
].map((k) => `${k}-${dev}.jpg`).concat(...[...Object.keys(HOME_SETS).map((s) => `home-sections-${s}`), "page-parts-full", "side-home-owned"].map((key) => Array.from({ length: CHUNKS[`${key}-${dev}`] || 0 }, (_, i) => `${key}-${i + 1}-${dev}.jpg`)));
const NEW_FILES = [...perDev("sp"), ...perDev("pc")];
for (const k of Object.keys(CHUNKS)) if (!CHUNKS[k]) throw new Error(`分割数が決まっていません: ${k}`);
for (const d of ["sp", "pc"]) if (!SCREENS[d]) throw new Error(`画面数が決まっていません: ${d}`);
const planned = new Set(NEW_FILES);
if (planned.size !== NEW_FILES.length) throw new Error("予定集合に重複があります");
const captured = new Set(index.map((e) => e.file));
const missing = [...planned].filter((f) => !captured.has(f)); const unknown = [...captured].filter((f) => !planned.has(f));
if (missing.length || unknown.length || captured.size !== index.length) throw new Error(`撮影集合が予定集合と一致しません（予定 ${planned.size} / 撮影 ${captured.size}）。欠落: ${missing.join(", ") || "なし"} / 予定外: ${unknown.join(", ") || "なし"}`);
for (const e of index) { const t = path.join(TMP, e.file); if (!fs.existsSync(t) || !fs.statSync(t).size) throw new Error(`一時 JPEG が無いか空: ${t}`); }
const known = new Set(existing.map((e) => e.file));
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction15-backup-"));
const backedUp = new Set(); const placed = []; let keepBackup = false;
const replacement = new Map(index.map((e) => [e.file, e]));
const added = index.filter((e) => !known.has(e.file));
// 分割数・画面数が変わった同名系列は旧末尾を INDEX から外し画像も退避する
const stale = existing.filter((e) => (/^home-sections-(corporate|service|media|shop-school|school-org)-\d+-(sp|pc)\.jpg$/.test(e.file) || /^page-parts-full-\d+-(sp|pc)\.jpg$/.test(e.file) || /^side-home-owned-\d+-(sp|pc)\.jpg$/.test(e.file) || /^article-screen-\d+-(sp|pc)\.jpg$/.test(e.file)) && !planned.has(e.file)).map((e) => e.file);
const nextIndex = JSON.stringify(existing.filter((e) => !stale.includes(e.file)).map((e) => replacement.get(e.file) || e).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction15.tmp";
try {
  for (const e of index) { const t = path.join(OUT, e.file); if (fs.existsSync(t)) { fs.renameSync(t, path.join(backupDir, e.file)); backedUp.add(e.file); } }
  for (const f of stale) { const t = path.join(OUT, f); if (fs.existsSync(t)) { fs.renameSync(t, path.join(backupDir, f)); backedUp.add(f); } }
  for (const e of index) { fs.renameSync(path.join(TMP, e.file), path.join(OUT, e.file)); placed.push(e.file); }
  fs.writeFileSync(indexTmp, nextIndex); fs.renameSync(indexTmp, catalogFile);
} catch (error) {
  const restoreFailures = [];
  try { fs.rmSync(indexTmp, { force: true }); } catch (_) { /* 旧 INDEX は無傷 */ }
  for (const f of placed) { if (backedUp.has(f)) continue; try { fs.unlinkSync(path.join(OUT, f)); } catch (e) { restoreFailures.push({ f, e: String(e) }); } }
  for (const f of backedUp) { const b = path.join(backupDir, f); if (!fs.existsSync(b)) continue; try { fs.renameSync(b, path.join(OUT, f)); } catch (e) { restoreFailures.push({ f, e: String(e) }); } }
  if (restoreFailures.length) { keepBackup = true; console.error(`画像の復元に失敗。退避ディレクトリを手動で確認: ${backupDir}`, restoreFailures); }
  throw error;
} finally {
  if (!keepBackup) { try { fs.rmSync(backupDir, { recursive: true, force: true }); } catch (e) { console.error(`退避ディレクトリの削除に失敗（配置済み画像はそのまま）: ${backupDir}`, e); } }
}
console.log("reaction15 done", index.length, "entries", existing.length - stale.length + added.length, "added", added.length, "stale", stale.length, "chunks", JSON.stringify(CHUNKS), "screens", JSON.stringify(SCREENS));
}
try { await main(); } finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (e) { console.error(`一時ディレクトリの削除に失敗: ${TMP}`, e); }
  try { if (lockFd !== null) fs.closeSync(lockFd); fs.rmSync(LOCK, { force: true }); } catch (e) { console.error(`ロックファイルの削除に失敗: ${LOCK}`, e); }
}

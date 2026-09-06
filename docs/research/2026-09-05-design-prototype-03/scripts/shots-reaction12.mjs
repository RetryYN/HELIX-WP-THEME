#!/usr/bin/env node
// shots-reaction12.mjs — 2026-09-06 PO 反応 18 回目 WT-EVT-0283「強化してくれ」（カテゴリ面、台帳 category-recapture n=54）の新規撮影。
// 面 category: 見出し name-count / リード 2 型 / 子分類 sidebar-tree・image-banners / カラム 2 型（全長を分割）/ sidebar 3 型 / 一覧 text-list・grid-2・timeline / カード minimal・rich / 絞り込み 4 型 / ページ送り none / ランキング top / ピックアップ 2 型 / CTA 3 型。既存型（試作 02〜03 の撮影）は撮り直さない。
// 方式は reaction9（分割撮影・dev ごとにブラウザ再起動）+ reaction10（画像読込失敗を例外・ロックファイル・予定集合照合・退避と原子的 INDEX 置換）。
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
fs.mkdirSync(OUT, { recursive: true });
const index = [];
const SP = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const PC = { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 };
const wt = (q) => "?wt=" + q;
function toJpeg(png, jpg) {
  execFileSync("ffmpeg", ["-y", "-loglevel", "error", "-i", png, "-vf", "scale='if(gte(iw,ih),min(1600,iw),-2)':'if(gte(iw,ih),-2,min(1600,ih))'", "-q:v", "5", jpg]);
  fs.unlinkSync(png);
}
const LOCK = path.join(OUT, ".reaction12.lock");
let lockFd = null;
try { lockFd = fs.openSync(LOCK, "wx"); fs.writeSync(lockFd, String(process.pid)); } catch (e) { throw new Error(`ロックファイルが存在します（別の撮影が実行中か、前回が強制終了）。退避 dir を確認してから削除してください: ${LOCK}`); }
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction12-capture-"));
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
async function chunked(browser, cfg, dev, url, key, meta, expectSections) {
  const CHUNK = dev === "sp" ? 4000 : 1600;
  const ctx = await browser.newContext({ ...cfg, deviceScaleFactor: 1, viewport: { width: cfg.viewport.width, height: CHUNK } });
  const p = await open(ctx, url);
  const m = await p.evaluate((sel) => { const root = document.querySelector(sel); const r = root.getBoundingClientRect(); return { top: r.top + scrollY, height: r.height, shown: Array.from(root.querySelectorAll(":scope > .wt-cat-primary, :scope > .wt-cat-aside")).filter((s) => s.getBoundingClientRect().height > 0).length }; }, meta.root);
  if (expectSections !== null && m.shown !== expectSections) throw new Error(`${key}: 表示区間が ${m.shown}（${expectSections} を期待）`);
  const chunks = Math.ceil(m.height / CHUNK); CHUNKS[`${key}-${dev}`] = chunks;
  for (let i = 0; i < chunks; i++) {
    await p.evaluate(([y]) => scrollTo(0, y), [m.top + i * CHUNK]); await p.waitForTimeout(250);
    await save(p, `${key}-${i + 1}-${dev}`, { face: meta.face, part: meta.part, variant: `${meta.variant} ${i + 1}/${chunks}`, dev });
  }
  await p.close(); await ctx.close();
}

const OLD_CATEGORY = [["header", ["name-only", "name-desc", "hero"]], ["children", ["none", "chips", "cards", "steps"]], ["list", ["grid", "thumb-list", "featured-grid"]], ["pagination", ["numbers", "load-more", "prev-next"]], ["ranking", ["none", "sidebar", "bottom"]], ["minihome", ["off", "on"]]];
const CAT_LEAD = ["lead-text", "editorial"];
const CAT_CHILDREN = ["sidebar-tree", "image-banners"];
const CAT_COLUMNS = [["sidebar-right", 2], ["1col", 1]]; // 全長分割。値は .wt-cat-layout の直下で可視の要素数（primary + aside / primary）
const CAT_SIDEBAR = ["standard", "with-cta", "full"];
const CAT_LIST = ["text-list", "grid-2", "timeline"];
const CAT_CARD = ["minimal", "rich"];
const CAT_FILTER = ["tabs", "year", "tag", "sort"];
const CAT_PICKUP = ["top-featured", "editor-pick-box"];
const CAT_CTA = ["lp-banner", "newsletter", "line"];
let browser = null;
async function main() {
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  if (browser) await browser.close();
  browser = await chromium.launch();
  for (const [v, n] of CAT_COLUMNS) await chunked(browser, cfg, dev, CATEGORY + wt(`cat_columns:${v},cat_sidebar:full,cat_pickup:top-featured,cat_cta:lp-banner`), `category-columns-${v}`, { face: "category", part: "category-columns", variant: `${v} (sidebar full / pickup / lp-banner)`, root: ".wt-cat-layout" }, n);
  const ctx = await browser.newContext(cfg);
  let p;
  p = await open(ctx, CATEGORY + wt("cat_header:name-count")); await assertBody(p, "wt-cat-header-name-count"); await assertVisible(p, ".wt-cat-head__count");
  await save(p, `category-header-name-count-${dev}`, { face: "category", part: "category-header", variant: "name-count", dev }, { selector: ".wt-cat-head" }); await p.close();
  for (const v of CAT_LEAD) {
    p = await open(ctx, CATEGORY + wt(`cat_lead:${v}`)); await assertBody(p, `wt-cat-lead-${v}`); await assertVisible(p, `.wt-cat-lead--${v}`);
    await save(p, `category-lead-${v}-${dev}`, { face: "category", part: "category-lead", variant: v, dev }, { selector: `.wt-cat-lead--${v}` }); await p.close();
  }
  for (const v of CAT_CHILDREN) {
    p = await open(ctx, CATEGORY + wt(`cat_children:${v}`)); await assertBody(p, `wt-cat-children-${v}`); await assertVisible(p, `.wt-cat-children--${v}`);
    await save(p, `category-children-${v}-${dev}`, { face: "category", part: "category-children", variant: v, dev }, { selector: v === "sidebar-tree" ? ".wt-cat-aside" : `.wt-cat-children--${v}` }); await p.close();
  }
  for (const v of CAT_SIDEBAR) {
    p = await open(ctx, CATEGORY + wt(`cat_sidebar:${v}`)); await assertBody(p, `wt-cat-sidebar-${v}`); await assertVisible(p, ".wt-cat-side");
    await save(p, `category-sidebar-${v}-${dev}`, { face: "category", part: "category-sidebar", variant: v, dev }, { selector: ".wt-cat-side" }); await p.close();
  }
  for (const v of CAT_LIST) {
    p = await open(ctx, CATEGORY + wt(`cat_list:${v}`)); await assertBody(p, `wt-cat-list-${v}`); await assertVisible(p, ".wt-cat-list");
    await save(p, `category-list-${v}-${dev}`, { face: "category", part: "category-list", variant: v, dev }, { selector: ".wt-cat-primary-list" }); await p.close();
  }
  for (const v of CAT_CARD) {
    p = await open(ctx, CATEGORY + wt(`cat_card:${v}`)); await assertBody(p, `wt-cat-card-${v}`); await assertVisible(p, ".wt-cat-list");
    await save(p, `category-card-${v}-${dev}`, { face: "category", part: "category-card", variant: v, dev }, { selector: ".wt-cat-primary-list" }); await p.close();
  }
  for (const v of CAT_FILTER) {
    p = await open(ctx, CATEGORY + wt(`cat_filter:${v}`)); await assertBody(p, `wt-cat-filter-${v}`); await assertVisible(p, `.wt-cat-filter--${v}`);
    await save(p, `category-filter-${v}-${dev}`, { face: "category", part: "category-filter", variant: v, dev }, { selector: `.wt-cat-filter--${v}` }); await p.close();
  }
  p = await open(ctx, CATEGORY + wt("cat_pagination:none")); await assertBody(p, "wt-cat-pagination-none");
  await save(p, `category-pagination-none-${dev}`, { face: "category", part: "category-pagination", variant: "none", dev }, { selector: ".wt-cat-primary" }); await p.close();
  p = await open(ctx, CATEGORY + wt("cat_ranking:top")); await assertBody(p, "wt-cat-ranking-top"); await assertVisible(p, ".wt-cat-ranking--top");
  await save(p, `category-ranking-top-${dev}`, { face: "category", part: "category-ranking", variant: "top", dev }, { selector: ".wt-cat-primary" }); await p.close();
  for (const v of CAT_PICKUP) {
    p = await open(ctx, CATEGORY + wt(`cat_pickup:${v}`)); await assertBody(p, `wt-cat-pickup-${v}`); await assertVisible(p, `.wt-cat-pickup--${v}`);
    await save(p, `category-pickup-${v}-${dev}`, { face: "category", part: "category-pickup", variant: v, dev }, { selector: `.wt-cat-pickup--${v}` }); await p.close();
  }
  for (const v of CAT_CTA) {
    p = await open(ctx, CATEGORY + wt(`cat_cta:${v}`)); await assertBody(p, `wt-cat-cta-${v}`); await assertVisible(p, `.wt-cat-cta--${v}`);
    await save(p, `category-cta-${v}-${dev}`, { face: "category", part: "category-cta", variant: v, dev }, { selector: `.wt-cat-cta--${v}` }); await p.close();
  }
  // 既定レイアウト（右 sidebar・件数見出し・抜粋表示）が変わったため、試作 02〜03 の既存カテゴリ写真 18 型 × 2 も同名で撮り直す（stage3 と同じ選択子）
  for (const [part, variants] of OLD_CATEGORY) for (const variant of variants) {
    p = await open(ctx, CATEGORY + wt(`cat_${part}:${variant}`)); await assertBody(p, `wt-cat-${part}-${variant}`);
    const selector = part === "children" && variant !== "none" ? ".wt-cat-children" : part === "header" ? ".wt-cat-head" : part === "ranking" && variant !== "none" ? ".wt-cat-ranking" : part === "minihome" && variant === "on" ? ".wt-cat-minihome" : { children: ".wt-category", list: ".wt-cat-primary", pagination: ".wt-cat-primary", ranking: ".wt-cat-primary", minihome: ".wt-category" }[part];
    if (part === "minihome") await save(p, `category-${part}-${variant}-${dev}`, { face: "category", part: `category-${part}`, variant, dev }, { clipSelector: selector, clipHeight: 1600 });
    else await save(p, `category-${part}-${variant}-${dev}`, { face: "category", part: `category-${part}`, variant, dev }, { selector });
    await p.close();
  }
  await ctx.close();
}
} finally { if (browser) await browser.close(); }

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
const perDev = (dev) => [
  "category-header-name-count", ...CAT_LEAD.map((v) => `category-lead-${v}`), ...CAT_CHILDREN.map((v) => `category-children-${v}`), ...CAT_SIDEBAR.map((v) => `category-sidebar-${v}`),
  ...CAT_LIST.map((v) => `category-list-${v}`), ...CAT_CARD.map((v) => `category-card-${v}`), ...CAT_FILTER.map((v) => `category-filter-${v}`), "category-pagination-none", "category-ranking-top",
  ...CAT_PICKUP.map((v) => `category-pickup-${v}`), ...CAT_CTA.map((v) => `category-cta-${v}`),
  ...OLD_CATEGORY.flatMap(([part, variants]) => variants.map((v) => `category-${part}-${v}`)),
].map((k) => `${k}-${dev}.jpg`).concat(...CAT_COLUMNS.map(([v]) => `category-columns-${v}`).map((key) => Array.from({ length: CHUNKS[`${key}-${dev}`] || 0 }, (_, i) => `${key}-${i + 1}-${dev}.jpg`)));
const NEW_FILES = [...perDev("sp"), ...perDev("pc")];
for (const k of Object.keys(CHUNKS)) if (!CHUNKS[k]) throw new Error(`分割数が決まっていません: ${k}`);
const planned = new Set(NEW_FILES);
if (planned.size !== NEW_FILES.length) throw new Error("予定集合に重複があります");
const captured = new Set(index.map((e) => e.file));
const missing = [...planned].filter((f) => !captured.has(f)); const unknown = [...captured].filter((f) => !planned.has(f));
if (missing.length || unknown.length || captured.size !== index.length) throw new Error(`撮影集合が予定集合と一致しません（予定 ${planned.size} / 撮影 ${captured.size}）。欠落: ${missing.join(", ") || "なし"} / 予定外: ${unknown.join(", ") || "なし"}`);
for (const e of index) { const t = path.join(TMP, e.file); if (!fs.existsSync(t) || !fs.statSync(t).size) throw new Error(`一時 JPEG が無いか空: ${t}`); }
const known = new Set(existing.map((e) => e.file));
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction12-backup-"));
const backedUp = new Set(); const placed = []; let keepBackup = false;
const replacement = new Map(index.map((e) => [e.file, e]));
const added = index.filter((e) => !known.has(e.file));
// 分割数が減った再実行では旧チャンクを INDEX から外し画像も退避する（reaction9 方式）
const stale = existing.filter((e) => /^category-columns-[a-z0-9-]+-\d+-(sp|pc)\.jpg$/.test(e.file) && !planned.has(e.file)).map((e) => e.file);
const nextIndex = JSON.stringify(existing.filter((e) => !stale.includes(e.file)).map((e) => replacement.get(e.file) || e).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction12.tmp";
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
console.log("reaction12 done", index.length, "entries", existing.length - stale.length + added.length, "added", added.length, "chunks", JSON.stringify(CHUNKS));
}
try { await main(); } finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (e) { console.error(`一時ディレクトリの削除に失敗: ${TMP}`, e); }
  try { if (lockFd !== null) fs.closeSync(lockFd); fs.rmSync(LOCK, { force: true }); } catch (e) { console.error(`ロックファイルの削除に失敗: ${LOCK}`, e); }
}

#!/usr/bin/env node
// shots-reaction13.mjs — 2026-09-06 PO 反応 19 回目 WT-EVT-0287「homeとイベントはもっとバリエーションを出して」「固定ページ継投で使えるパーツ」（段 8、台帳 home-event-recapture-v2 HP n=62 / イベント主集計 n=40）の新規撮影。
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
const LOCK = path.join(OUT, ".reaction13.lock");
let lockFd = null;
try { lockFd = fs.openSync(LOCK, "wx"); fs.writeSync(lockFd, String(process.pid)); } catch (e) { throw new Error(`ロックファイルが存在します（別の撮影が実行中か、前回が強制終了）。退避 dir を確認してから削除してください: ${LOCK}`); }
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction13-capture-"));
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
const HOME_HERO = ["cards-carousel", "product-shot", "search-box"];
const EVENT_APPLY = ["receipt-upload", "postcard", "messaging-app"];
const PART_NAMES = ["greeting", "logos-row", "stores", "event-list", "gallery", "sns-feed", "timeline", "countdown", "target-audience", "organizer", "tickets", "prizes", "entry-steps", "judges", "target-products"];
// 区間セット: 表示区間数（verify の SETS / ESETS と同じ）
const HOME_SETS = { corporate: 9, service: 9, media: 7, "shop-school": 11, "school-org": 12 };
const EVENT_SETS = { seminar: 11, conference: 11, festival: 12, campaign: 9 };
let browser = null;
async function main() {
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  if (browser) await browser.close();
  browser = await chromium.launch();
  // 全長（分割）: HP 区間セット 5 種（corporate / service は greeting / logos 追加で同名置換）、イベント区間セット 4 種、パーツ一覧ページ
  for (const [set, n] of Object.entries(HOME_SETS)) await chunked(browser, cfg, dev, HOME + wt(`home_sections:${set}`), `home-sections-${set}`, { face: "home", part: "home-sections", variant: set }, ".wt-home__sections", ":scope > .wt-home__section", n);
  for (const [set, n] of Object.entries(EVENT_SETS)) await chunked(browser, cfg, dev, EVENT + wt(`event_sections:${set},event_schedule:table,event_speakers:cards-photo,event_map:static-image`), `event-sections-${set}`, { face: "event", part: "event-sections", variant: set }, ".wt-event", ".wt-event__sections > .wt-event__section", n);
  await chunked(browser, cfg, dev, PARTS, "page-parts-full", { face: "page", part: "page-parts", variant: "15 パーツを並べた固定ページ" }, ".wt-page-parts", ":scope > .wt-part", 15);
  const ctx = await browser.newContext(cfg);
  let p;
  for (const v of HOME_HERO) {
    p = await open(ctx, HOME + wt(`home_hero:${v}`)); await assertBody(p, `wt-home-hero-${v}`); await assertVisible(p, `.wt-home-hero--${v}`);
    await save(p, `home-hero-${v}-${dev}`, { face: "home", part: "home-hero", variant: v, dev }, { selector: `.wt-home-hero--${v}` }); await p.close();
  }
  p = await open(ctx, HOME + wt("home_hero_cta:search")); await assertBody(p, "wt-home-hero-cta-search"); await assertVisible(p, ".wt-home-hero .wt-home-cta--search");
  await save(p, `home-hero-cta-search-${dev}`, { face: "home", part: "home-hero-cta", variant: "search", dev }, { selector: ".wt-home-hero--text-only" }); await p.close();
  p = await open(ctx, HOME + wt("home_contact:double-cta")); await assertBody(p, "wt-home-contact-double-cta"); await assertVisible(p, ".wt-home-contact--double-cta");
  await save(p, `home-contact-double-cta-${dev}`, { face: "home", part: "home-contact", variant: "double-cta", dev }, { selector: ".wt-home__section--contact" }); await p.close();
  for (const v of EVENT_APPLY) {
    p = await open(ctx, EVENT + wt(`event_apply:${v}`)); await assertBody(p, `wt-event-apply-${v}`); await assertVisible(p, `.wt-event-apply--${v}`);
    await save(p, `event-apply-${v}-${dev}`, { face: "event", part: "event-apply", variant: v, dev }, { selector: ".wt-event__section--apply" }); await p.close();
  }
  for (const v of PART_NAMES) {
    p = await open(ctx, PARTS); await assertVisible(p, `.wt-part--${v}`);
    await save(p, `part-${v}-${dev}`, { face: "page", part: "page-part", variant: v, dev }, { selector: `.wt-part--${v}` }); await p.close();
  }
  await ctx.close();
}
} finally { if (browser) await browser.close(); }

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
const perDev = (dev) => [
  ...HOME_HERO.map((v) => `home-hero-${v}`), "home-hero-cta-search", "home-contact-double-cta", ...EVENT_APPLY.map((v) => `event-apply-${v}`), ...PART_NAMES.map((v) => `part-${v}`),
].map((k) => `${k}-${dev}.jpg`).concat(...[...Object.keys(HOME_SETS).map((s) => `home-sections-${s}`), ...Object.keys(EVENT_SETS).map((s) => `event-sections-${s}`), "page-parts-full"].map((key) => Array.from({ length: CHUNKS[`${key}-${dev}`] || 0 }, (_, i) => `${key}-${i + 1}-${dev}.jpg`)));
const NEW_FILES = [...perDev("sp"), ...perDev("pc")];
for (const k of Object.keys(CHUNKS)) if (!CHUNKS[k]) throw new Error(`分割数が決まっていません: ${k}`);
const planned = new Set(NEW_FILES);
if (planned.size !== NEW_FILES.length) throw new Error("予定集合に重複があります");
const captured = new Set(index.map((e) => e.file));
const missing = [...planned].filter((f) => !captured.has(f)); const unknown = [...captured].filter((f) => !planned.has(f));
if (missing.length || unknown.length || captured.size !== index.length) throw new Error(`撮影集合が予定集合と一致しません（予定 ${planned.size} / 撮影 ${captured.size}）。欠落: ${missing.join(", ") || "なし"} / 予定外: ${unknown.join(", ") || "なし"}`);
for (const e of index) { const t = path.join(TMP, e.file); if (!fs.existsSync(t) || !fs.statSync(t).size) throw new Error(`一時 JPEG が無いか空: ${t}`); }
const known = new Set(existing.map((e) => e.file));
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction13-backup-"));
const backedUp = new Set(); const placed = []; let keepBackup = false;
const replacement = new Map(index.map((e) => [e.file, e]));
const added = index.filter((e) => !known.has(e.file));
// 分割数が減った再実行では旧チャンクを INDEX から外し画像も退避する（reaction9 方式）
// 分割数が変わった同名置換（home-sections-corporate / service）では旧チャンクを INDEX から外し画像も退避する。event-page-full-* は区間セット導入で並びが変わったため event-sections-seminar に置き換え、旧写真も退避する
const stale = existing.filter((e) => (/^home-sections-(corporate|service|media|shop-school|school-org)-\d+-(sp|pc)\.jpg$/.test(e.file) || /^event-page-full-\d+-(sp|pc)\.jpg$/.test(e.file)) && !planned.has(e.file)).map((e) => e.file);
const nextIndex = JSON.stringify(existing.filter((e) => !stale.includes(e.file)).map((e) => replacement.get(e.file) || e).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction13.tmp";
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
console.log("reaction13 done", index.length, "entries", existing.length - stale.length + added.length, "added", added.length, "chunks", JSON.stringify(CHUNKS));
}
try { await main(); } finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (e) { console.error(`一時ディレクトリの削除に失敗: ${TMP}`, e); }
  try { if (lockFd !== null) fs.closeSync(lockFd); fs.rmSync(LOCK, { force: true }); } catch (e) { console.error(`ロックファイルの削除に失敗: ${LOCK}`, e); }
}

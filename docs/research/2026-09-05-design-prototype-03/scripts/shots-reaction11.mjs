#!/usr/bin/env node
// shots-reaction11.mjs — 2026-09-06 PO 反応 17 回目 WT-EVT-0277（HP 面・イベントページ、Claude 案）の新規撮影。
// 面 home: hero 6 型 / hero CTA 3 型（none は hero-cta の非表示のため撮らない）/ 区間セット 3 種（全長を分割）/ お知らせ 3 型 / 問い合わせ 4 型 / 固定導線（float-cta SP+PC、sp-bottom-bar・float-tel は SP）。
// 面 event: hero 4 型 / 開催情報 3 型 / スケジュール 3 型 / 登壇者 3 型 / 申込 4 型 / 受付状態 3 型 / 地図 2 型 / 共有 2 型 / 固定導線（float-apply SP+PC、sp-bottom-bar は SP）/ 全長（既定、分割）。
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
const HOME = "/";
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
const LOCK = path.join(OUT, ".reaction11.lock");
let lockFd = null;
try { lockFd = fs.openSync(LOCK, "wx"); fs.writeSync(lockFd, String(process.pid)); } catch (e) { throw new Error(`ロックファイルが存在します（別の撮影が実行中か、前回が強制終了）。退避 dir を確認してから削除してください: ${LOCK}`); }
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction11-capture-"));
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
  const m = await p.evaluate((sel) => { const root = document.querySelector(sel); const r = root.getBoundingClientRect(); return { top: r.top + scrollY, height: r.height, shown: Array.from(root.querySelectorAll(":scope > section, :scope > .wt-home__section, :scope > .wt-event__section")).filter((s) => s.getBoundingClientRect().height > 0).length }; }, meta.root);
  if (expectSections !== null && m.shown !== expectSections) throw new Error(`${key}: 表示区間が ${m.shown}（${expectSections} を期待）`);
  const chunks = Math.ceil(m.height / CHUNK); CHUNKS[`${key}-${dev}`] = chunks;
  for (let i = 0; i < chunks; i++) {
    await p.evaluate(([y]) => scrollTo(0, y), [m.top + i * CHUNK]); await p.waitForTimeout(250);
    await save(p, `${key}-${i + 1}-${dev}`, { face: meta.face, part: meta.part, variant: `${meta.variant} ${i + 1}/${chunks}`, dev });
  }
  await p.close(); await ctx.close();
}
const HOME_HERO = ["text-only", "slider", "fullbleed", "split", "article-grid", "video"];
const HOME_CTA = ["double", "single", "tel-button"];
const HOME_SECTIONS = [["corporate", 8], ["service", 8], ["media", 7]];
const HOME_NEWS = ["list-with-date", "tabs", "cards"];
const HOME_CONTACT = ["tel-form", "form-only", "tel-only", "line"];
const EV_HERO = ["photo-overlay", "key-visual", "date-place-block", "text-only"];
const EV_INFO = ["inline-text", "table", "icon-list"];
const EV_SCHEDULE = ["table", "timeline", "accordion"];
const EV_SPEAKERS = ["cards-photo", "list", "single-profile"];
const EV_APPLY = ["inline-form", "external-form", "ticket-link", "closed-notice"];
const EV_STATUS = ["open", "few-seats", "ended"];
const EV_MAP = ["static-image", "text-only"];
const EV_SHARE = ["icons", "add-to-calendar"];
let browser = null;
async function main() {
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  if (browser) await browser.close();
  browser = await chromium.launch();
  // 全長（分割）: home 3 セット + event 既定
  for (const [set, n] of HOME_SECTIONS) await chunked(browser, cfg, dev, HOME + wt(`home_sections:${set}`), `home-sections-${set}`, { face: "home", part: "home-sections", variant: set, root: ".wt-home__sections" }, n);
  await chunked(browser, cfg, dev, EVENT + wt("event_schedule:table,event_speakers:cards-photo,event_map:static-image,event_share:icons"), "event-page-full", { face: "event", part: "event-page", variant: "full (schedule table / speakers cards / map / share)", root: ".wt-event__sections" }, 11);
  const ctx = await browser.newContext(cfg);
  let p;
  for (const v of HOME_HERO) {
    p = await open(ctx, HOME + wt(`home_hero:${v}`)); await assertBody(p, `wt-home-hero-${v}`); await assertVisible(p, `.wt-home-hero--${v}`);
    await save(p, `home-hero-${v}-${dev}`, { face: "home", part: "home-hero", variant: v, dev }, { selector: `.wt-home-hero--${v}` }); await p.close();
  }
  for (const v of HOME_CTA) {
    p = await open(ctx, HOME + wt(`home_hero_cta:${v}`)); await assertBody(p, `wt-home-hero-cta-${v}`); await assertVisible(p, `.wt-home-hero--text-only .wt-home-cta--${v}`);
    await save(p, `home-hero-cta-${v}-${dev}`, { face: "home", part: "home-hero-cta", variant: v, dev }, { selector: ".wt-home-hero--text-only" }); await p.close();
  }
  for (const v of HOME_NEWS) {
    p = await open(ctx, HOME + wt(`home_news:${v}`)); await assertBody(p, `wt-home-news-${v}`); await assertVisible(p, `.wt-home-news--${v}`);
    await save(p, `home-news-${v}-${dev}`, { face: "home", part: "home-news", variant: v, dev }, { selector: ".wt-home__section--news" }); await p.close();
  }
  for (const v of HOME_CONTACT) {
    p = await open(ctx, HOME + wt(`home_contact:${v}`)); await assertBody(p, `wt-home-contact-${v}`); await assertVisible(p, `.wt-home-contact--${v}`);
    await save(p, `home-contact-${v}-${dev}`, { face: "home", part: "home-contact", variant: v, dev }, { selector: ".wt-home__section--contact" }); await p.close();
  }
  for (const v of dev === "sp" ? ["float-cta", "sp-bottom-bar", "float-tel"] : ["float-cta"]) {
    p = await open(ctx, HOME + wt(`home_fixed:${v}`)); await assertBody(p, `wt-home-fixed-${v}`); await assertVisible(p, `.wt-home-fixed--${v}`);
    await save(p, `home-fixed-${v}-${dev}`, { face: "home", part: "home-fixed", variant: v, dev }); await p.close();
  }
  for (const v of EV_HERO) {
    p = await open(ctx, EVENT + wt(`event_hero:${v}`)); await assertBody(p, `wt-event-hero-${v}`); await assertVisible(p, `.wt-event-hero--${v}`);
    await save(p, `event-hero-${v}-${dev}`, { face: "event", part: "event-hero", variant: v, dev }, { selector: `.wt-event-hero--${v}` }); await p.close();
  }
  for (const v of EV_STATUS) {
    p = await open(ctx, EVENT + wt(`event_status:${v}`)); await assertBody(p, `wt-event-status-${v}`); await assertVisible(p, `.wt-event-hero--key-visual .wt-event-status--${v}`);
    await save(p, `event-status-${v}-${dev}`, { face: "event", part: "event-status", variant: v, dev }, { selector: ".wt-event-hero--key-visual" }); await p.close();
  }
  const simple = [["info", EV_INFO, ".wt-event__section--info"], ["schedule", EV_SCHEDULE, ".wt-event__section--schedule"], ["speakers", EV_SPEAKERS, ".wt-event__section--speakers"], ["apply", EV_APPLY, ".wt-event__section--apply"], ["map", EV_MAP, ".wt-event__section--access"], ["share", EV_SHARE, ".wt-event__section--notes"]];
  for (const [part, variants, selector] of simple) {
    for (const v of variants) {
      p = await open(ctx, EVENT + wt(`event_${part}:${v}`)); await assertBody(p, `wt-event-${part}-${v}`); await assertVisible(p, `.wt-event-${part}--${v}`);
      await save(p, `event-${part}-${v}-${dev}`, { face: "event", part: `event-${part}`, variant: v, dev }, { selector }); await p.close();
    }
  }
  for (const v of dev === "sp" ? ["float-apply", "sp-bottom-bar"] : ["float-apply"]) {
    p = await open(ctx, EVENT + wt(`event_fixed:${v}`)); await assertBody(p, `wt-event-fixed-${v}`); await assertVisible(p, `.wt-event-fixed--${v}`);
    await save(p, `event-fixed-${v}-${dev}`, { face: "event", part: "event-fixed", variant: v, dev }); await p.close();
  }
  await ctx.close();
}
} finally { if (browser) await browser.close(); }

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
const perDev = (dev) => [
  ...HOME_HERO.map((v) => `home-hero-${v}`), ...HOME_CTA.map((v) => `home-hero-cta-${v}`), ...HOME_NEWS.map((v) => `home-news-${v}`), ...HOME_CONTACT.map((v) => `home-contact-${v}`),
  ...(dev === "sp" ? ["float-cta", "sp-bottom-bar", "float-tel"] : ["float-cta"]).map((v) => `home-fixed-${v}`),
  ...EV_HERO.map((v) => `event-hero-${v}`), ...EV_STATUS.map((v) => `event-status-${v}`), ...EV_INFO.map((v) => `event-info-${v}`), ...EV_SCHEDULE.map((v) => `event-schedule-${v}`), ...EV_SPEAKERS.map((v) => `event-speakers-${v}`), ...EV_APPLY.map((v) => `event-apply-${v}`), ...EV_MAP.map((v) => `event-map-${v}`), ...EV_SHARE.map((v) => `event-share-${v}`),
  ...(dev === "sp" ? ["float-apply", "sp-bottom-bar"] : ["float-apply"]).map((v) => `event-fixed-${v}`),
].map((k) => `${k}-${dev}.jpg`).concat(...[...HOME_SECTIONS.map(([s]) => `home-sections-${s}`), "event-page-full"].map((key) => Array.from({ length: CHUNKS[`${key}-${dev}`] || 0 }, (_, i) => `${key}-${i + 1}-${dev}.jpg`)));
const NEW_FILES = [...perDev("sp"), ...perDev("pc")];
for (const k of Object.keys(CHUNKS)) if (!CHUNKS[k]) throw new Error(`分割数が決まっていません: ${k}`);
const planned = new Set(NEW_FILES);
if (planned.size !== NEW_FILES.length) throw new Error("予定集合に重複があります");
const captured = new Set(index.map((e) => e.file));
const missing = [...planned].filter((f) => !captured.has(f)); const unknown = [...captured].filter((f) => !planned.has(f));
if (missing.length || unknown.length || captured.size !== index.length) throw new Error(`撮影集合が予定集合と一致しません（予定 ${planned.size} / 撮影 ${captured.size}）。欠落: ${missing.join(", ") || "なし"} / 予定外: ${unknown.join(", ") || "なし"}`);
for (const e of index) { const t = path.join(TMP, e.file); if (!fs.existsSync(t) || !fs.statSync(t).size) throw new Error(`一時 JPEG が無いか空: ${t}`); }
const known = new Set(existing.map((e) => e.file));
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction11-backup-"));
const backedUp = new Set(); const placed = []; let keepBackup = false;
const replacement = new Map(index.map((e) => [e.file, e]));
const added = index.filter((e) => !known.has(e.file));
// 分割数が減った再実行では旧チャンクを INDEX から外し画像も退避する（reaction9 方式）
const stale = existing.filter((e) => /^(home-sections-[a-z]+|event-page-full)-\d+-(sp|pc)\.jpg$/.test(e.file) && !planned.has(e.file)).map((e) => e.file);
const nextIndex = JSON.stringify(existing.filter((e) => !stale.includes(e.file)).map((e) => replacement.get(e.file) || e).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction11.tmp";
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
console.log("reaction11 done", index.length, "entries", existing.length - stale.length + added.length, "added", added.length, "chunks", JSON.stringify(CHUNKS));
}
try { await main(); } finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (e) { console.error(`一時ディレクトリの削除に失敗: ${TMP}`, e); }
  try { if (lockFd !== null) fs.closeSync(lockFd); fs.rmSync(LOCK, { force: true }); } catch (e) { console.error(`ロックファイルの削除に失敗: ${LOCK}`, e); }
}

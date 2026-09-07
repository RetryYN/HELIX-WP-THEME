// 段 13（2026-09-07 WT-EVT-0303「OK」）: LP の lp_form:block とイベントの event_apply:block-form（段 11 のフォームブロックを選択肢として追加）の撮影。
// 方式は reaction7〜18 と同じ（一時 dir → 予定集合の照合 → 退避 → 配置 → INDEX を原子的置換 → 失敗時復元 → finally 清掃）。同名置換なし（新規 4 枚）、stale なし。
// 実行: scripts/ から `NODE_PATH=... node shots-reaction19.mjs`（--out ../results）。verify と同時に走らせない。
//
//
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
const LOCK = path.join(OUT, ".reaction19.lock");
let lockFd = null;
try { lockFd = fs.openSync(LOCK, "wx"); fs.writeSync(lockFd, String(process.pid)); } catch (e) { throw new Error(`ロックファイルが存在します（別の撮影が実行中か、前回が強制終了）。退避 dir を確認してから削除してください: ${LOCK}`); }
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction19-capture-"));
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
// LP の lp_form:block とイベントの event_apply:block-form（段 11 のフォームブロックを区間に置く）
let browser = null;
async function main() {
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  if (browser) await browser.close();
  browser = await chromium.launch();
  const ctx = await browser.newContext(cfg);
  let p;
  p = await open(ctx, "/lp/" + wt("lp_form:block,lp_sections:extended")); await assertBody(p, "wt-lp-form-block"); await assertVisible(p, ".wt-lp-form--block .wt-form__form"); await save(p, `lp-form-block-${dev}`, { face: "lp", part: "lp-form", variant: "block 段 11 のフォームブロック（種別 contact。段 13、置き換えず選択肢を追加。区間は lp_sections:extended で表示）", dev }, { selector: ".wt-lp__section--form" }); await p.close();
  p = await open(ctx, "/event/" + wt("event_apply:block-form")); await assertBody(p, "wt-event-apply-block-form"); await assertVisible(p, ".wt-event-apply--block-form .wt-form__form"); await save(p, `event-apply-block-form-${dev}`, { face: "event", part: "event-apply", variant: "block-form 段 11 のフォームブロック（種別 apply。段 13、置き換えず選択肢を追加）", dev }, { selector: ".wt-event__section--apply" }); await p.close();
  await ctx.close();
}
} finally { if (browser) await browser.close(); }

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
const NEW_FILES = ["sp", "pc"].flatMap((dev) => [`lp-form-block-${dev}.jpg`, `event-apply-block-form-${dev}.jpg`]);
const planned = new Set(NEW_FILES);
if (planned.size !== NEW_FILES.length) throw new Error("予定集合に重複があります");
const captured = new Set(index.map((e) => e.file));
const missing = [...planned].filter((f) => !captured.has(f)); const unknown = [...captured].filter((f) => !planned.has(f));
if (missing.length || unknown.length || captured.size !== index.length) throw new Error(`撮影集合が予定集合と一致しません（予定 ${planned.size} / 撮影 ${captured.size}）。欠落: ${missing.join(", ") || "なし"} / 予定外: ${unknown.join(", ") || "なし"}`);
for (const e of index) { const t = path.join(TMP, e.file); if (!fs.existsSync(t) || !fs.statSync(t).size) throw new Error(`一時 JPEG が無いか空: ${t}`); }
const known = new Set(existing.map((e) => e.file));
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction19-backup-"));
const backedUp = new Set(); const placed = []; let keepBackup = false;
const replacement = new Map(index.map((e) => [e.file, e]));
const added = index.filter((e) => !known.has(e.file));
const stale = []; // 段 13 は新規のみ
const nextIndex = JSON.stringify(existing.filter((e) => !stale.includes(e.file)).map((e) => replacement.get(e.file) || e).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction19.tmp";
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
console.log("reaction19 done", index.length, "entries", existing.length - stale.length + added.length, "added", added.length, "stale", stale.length);
}
try { await main(); } finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (e) { console.error(`一時ディレクトリの削除に失敗: ${TMP}`, e); }
  try { if (lockFd !== null) fs.closeSync(lockFd); fs.rmSync(LOCK, { force: true }); } catch (e) { console.error(`ロックファイルの削除に失敗: ${LOCK}`, e); }
}

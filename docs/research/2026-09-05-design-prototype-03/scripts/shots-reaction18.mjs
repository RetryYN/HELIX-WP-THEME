// 段 12（2026-09-07 WT-EVT-0302「増やす方向で」）: ヘッダー・フッター・固定 CTA の所属（共通 site / 独自設定 own / 非表示 off、LP は lp）を面ごとに撮る。
// 方式は reaction7〜17 と同じ（一時 dir → 予定集合の照合 → 退避 → 配置 → INDEX を原子的置換 → 失敗時復元 → finally 清掃）。同名置換なし（すべて新規 43 枚）、stale なし。
// 実行: scripts/ から `NODE_PATH=... node shots-reaction18.mjs`（--out ../results）。verify と同時に走らせない。
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
const LOCK = path.join(OUT, ".reaction18.lock");
let lockFd = null;
try { lockFd = fs.openSync(LOCK, "wx"); fs.writeSync(lockFd, String(process.pid)); } catch (e) { throw new Error(`ロックファイルが存在します（別の撮影が実行中か、前回が強制終了）。退避 dir を確認してから削除してください: ${LOCK}`); }
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction18-capture-"));
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
// 面 × 部位 × 所属の撮影。所属 site は共通軸の値（header:cta / footer_layout:columns-3 / fixed:float-cta|sp-bottom-bar）、own は own_<face>_*（tel / single-row / float-cta。HOME・イベント・LP の固定 CTA は home_fixed / event_fixed / lp_fixed）、off は描画なし
const FACES = [["home", "/", "HP"], ["article", "/standing-desk-compare/", "記事"], ["category", "/category/topic-index/", "カテゴリ"], ["event", "/event/", "イベント"], ["page", "/parts/", "固定ページ"], ["lp", "/lp/", "LP"]];
const pre = (f) => f === "category" ? "cat" : f;
const OWNFIX = { home: "home_fixed:float-cta", event: "event_fixed:float-apply", lp: "lp_fixed:float-cta" };
const ownFix = (f) => OWNFIX[f] || `own_${f}_fixed:float-cta`;
const shotTop = async (p, name, meta) => { await p.evaluate(() => scrollTo(0, 0)); await p.waitForTimeout(200); await save(p, name, meta); };
const shotBottom = async (p, name, meta) => { await p.evaluate(() => scrollTo(0, document.documentElement.scrollHeight)); await p.waitForTimeout(300); await save(p, name, meta); };
let browser = null;
async function main() {
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  if (browser) await browser.close();
  browser = await chromium.launch();
  const ctx = await browser.newContext(cfg);
  let p;
  for (const [f, url, ja] of FACES) {
    // ヘッダー own（tel）: 上端
    p = await open(ctx, url + wt(`${pre(f)}_head:own,own_${f}_header:tel,header:cta`)); await assertBody(p, "wt-head-bundle-own"); await assertVisible(p, ".wt-header--tel"); await shotTop(p, `chrome-head-own-${f}-${dev}`, { face: f, part: "chrome-head", variant: `own 独自設定（${ja}。own_${f}_header:tel。共通は header:cta のまま）`, dev }); await p.close();
    // 固定 CTA site: SP は下部バー、PC は浮遊ボタン
    const fx = dev === "sp" ? "sp-bottom-bar" : "float-cta";
    p = await open(ctx, url + wt(`${pre(f)}_fix:site,fixed:${fx},${ownFix(f)}`)); await assertBody(p, "wt-fix-bundle-site"); await assertVisible(p, `.wt-fixed--${fx}`); await shotTop(p, `chrome-fix-site-${f}-${dev}`, { face: f, part: "chrome-fix", variant: `site 共通の固定 CTA（${ja}。fixed:${fx}。面の own（${ownFix(f)}）は出ない）`, dev }); await p.close();
    if (dev === "pc") {
      p = await open(ctx, url + wt(`${pre(f)}_head:off,header:cta`)); await assertBody(p, "wt-head-bundle-none"); if (await p.$(".wt-header")) throw new Error("off なのに .wt-header がある"); await shotTop(p, `chrome-head-off-${f}-${dev}`, { face: f, part: "chrome-head", variant: `off 非表示（${ja}。ヘッダーを描画しない）`, dev }); await p.close();
      p = await open(ctx, url + wt(`${pre(f)}_foot:own,own_${f}_footer_layout:single-row,own_${f}_footer_above:cta-band,footer_layout:columns-3`)); await assertBody(p, "wt-foot-bundle-own"); await assertVisible(p, ".wt-footer__layout--single-row"); await save(p, `chrome-foot-own-${f}-${dev}`, { face: f, part: "chrome-foot", variant: `own 独自設定（${ja}。own_${f}_footer_layout:single-row + footer_above:cta-band。共通は columns-3 のまま）`, dev }, { selector: ".wt-footer" }); await p.close();
      p = await open(ctx, url + wt(`${pre(f)}_foot:off,footer_layout:columns-3`)); await assertBody(p, "wt-foot-bundle-none"); if (await p.$(".wt-footer")) throw new Error("off なのに .wt-footer がある"); await shotBottom(p, `chrome-foot-off-${f}-${dev}`, { face: f, part: "chrome-foot", variant: `off 非表示（${ja}。フッターを描画しない。ページ末尾）`, dev }); await p.close();
    }
  }
  if (dev === "pc") { p = await open(ctx, "/lp/" + wt("lp_head:site,header:cta")); await assertBody(p, "wt-head-bundle-site"); await assertVisible(p, ".wt-header--cta"); await shotTop(p, `chrome-head-site-lp-${dev}`, { face: "lp", part: "chrome-head", variant: "site 共通ヘッダーを LP に継承（lp_head:site。LP ヘッダー 3 種は隠れる。既定は lp = LP ヘッダー）", dev }); await p.close(); }
  await ctx.close();
}
} finally { if (browser) await browser.close(); }

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
const perDev = (dev) => [
  ...FACES.flatMap(([f]) => [`chrome-head-own-${f}`, `chrome-fix-site-${f}`, ...(dev === "pc" ? [`chrome-head-off-${f}`, `chrome-foot-own-${f}`, `chrome-foot-off-${f}`] : [])]),
  ...(dev === "pc" ? ["chrome-head-site-lp"] : []),
].map((k) => `${k}-${dev}.jpg`);
const NEW_FILES = [...perDev("sp"), ...perDev("pc")];
const planned = new Set(NEW_FILES);
if (planned.size !== NEW_FILES.length) throw new Error("予定集合に重複があります");
const captured = new Set(index.map((e) => e.file));
const missing = [...planned].filter((f) => !captured.has(f)); const unknown = [...captured].filter((f) => !planned.has(f));
if (missing.length || unknown.length || captured.size !== index.length) throw new Error(`撮影集合が予定集合と一致しません（予定 ${planned.size} / 撮影 ${captured.size}）。欠落: ${missing.join(", ") || "なし"} / 予定外: ${unknown.join(", ") || "なし"}`);
for (const e of index) { const t = path.join(TMP, e.file); if (!fs.existsSync(t) || !fs.statSync(t).size) throw new Error(`一時 JPEG が無いか空: ${t}`); }
const known = new Set(existing.map((e) => e.file));
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction18-backup-"));
const backedUp = new Set(); const placed = []; let keepBackup = false;
const replacement = new Map(index.map((e) => [e.file, e]));
const added = index.filter((e) => !known.has(e.file));
const stale = []; // 段 12 は新規のみ
const nextIndex = JSON.stringify(existing.filter((e) => !stale.includes(e.file)).map((e) => replacement.get(e.file) || e).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction18.tmp";
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
console.log("reaction18 done", index.length, "entries", existing.length - stale.length + added.length, "added", added.length, "stale", stale.length);
}
try { await main(); } finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (e) { console.error(`一時ディレクトリの削除に失敗: ${TMP}`, e); }
  try { if (lockFd !== null) fs.closeSync(lockFd); fs.rmSync(LOCK, { force: true }); } catch (e) { console.error(`ロックファイルの削除に失敗: ${LOCK}`, e); }
}

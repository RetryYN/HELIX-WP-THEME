#!/usr/bin/env node
// shots-reaction5.mjs — 2026-09-05 PO 反応 13 回目（WT-EVT-0254「PRが残ってるけど？」）の是正差分だけを再撮影する。
// 対象: CTA バナー（catalog の #cat-cta-banner、キャプション先頭の「PR: 」を除去）と、同パターンを本文に含む記事 full / full-screens。
// 撮影・JPEG 変換はすべて一時ディレクトリで行い、全枚数の非空検査と INDEX 照合を通った後に
// 旧画像を退避 → 新画像を配置（失敗時は退避先から復元）→ INDEX 書き出しの順で置換する（shots-reaction4 の motion 置換と同じ方式）。
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
fs.mkdirSync(OUT, { recursive: true });
const index = [];
const SP = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const PC = { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 };

function toJpeg(png, jpg) {
  execFileSync("ffmpeg", ["-y", "-loglevel", "error", "-i", png, "-vf", "scale='if(gte(iw,ih),min(1600,iw),-2)':'if(gte(iw,ih),-2,min(1600,ih))'", "-q:v", "5", jpg]);
  fs.unlinkSync(png);
}
// 撮影は一時ディレクトリへ行う。results/ の既存 JPEG は promote() で全枚数の検査を通った後にだけ置換される。
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction5-capture-"));
async function save(page, name, meta, opts = {}) {
  const png = path.join(TMP, name + ".png"), jpg = path.join(TMP, name + ".jpg");
  if (opts.selector) {
    const el = page.locator(opts.selector).first();
    await el.waitFor({ state: "visible", timeout: 8000 });
    await el.scrollIntoViewIfNeeded();
    await page.waitForTimeout(150);
    await el.screenshot({ path: png });
  } else if (opts.viewportOnly) await page.screenshot({ path: png });
  else await page.screenshot({ path: png, fullPage: true });
  toJpeg(png, jpg);
  if (!fs.statSync(jpg).size) throw new Error(`空の JPEG: ${jpg}`);
  index.push({ file: name + ".jpg", ...meta });
  console.log("shot", name);
}
// verify.mjs の ctaBannerNoPrPrefix と同じ禁止表記。catalog / 記事の両面で、撮影前に要素の存在と表記なしを確認する。
const PR_RE = /^(PR|広告|アフィリエイト|【PR】|\[PR\])/;
async function assertNoPr(page, selector, label) {
  const texts = await page.evaluate((sel) => Array.from(document.querySelectorAll(sel)).map((el) => el.textContent.trim()), selector);
  if (!texts.length) throw new Error(`${label}: CTA バナーのキャプションが見つかりません（${selector}）`);
  const bad = texts.filter((t) => PR_RE.test(t));
  if (bad.length) throw new Error(`${label}: CTA バナーのキャプションに PR 表記が残っています: ${bad.join(" / ")}`);
}
async function open(ctx, url) {
  const page = await ctx.newPage();
  await page.goto(BASE + url, { waitUntil: "networkidle" });
  await page.waitForTimeout(300);
  return page;
}

const browser = await chromium.launch();
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  const ctx = await browser.newContext(cfg);

  // CTA バナー（catalog）: キャプションに「PR: 」が残っていないことを撮影前に確認する
  let p = await open(ctx, CATALOG);
  await assertNoPr(p, "#cat-cta-banner figcaption", `catalog ${dev}`);
  await save(p, `cta-banner-${dev}`, { face: "article", part: "cta", variant: "banner", dev }, { selector: "#cat-cta-banner" });
  await p.close();

  // 記事 全長 + 画面単位（本文中の CTA バナーの反映）
  p = await open(ctx, ARTICLE);
  await assertNoPr(p, ".wp-block-post-content .is-style-wt-banner figcaption", `article ${dev}`);
  await save(p, `article-full-${dev}`, { face: "article", part: "full", variant: "default", dev });
  const vh = cfg.viewport.height, total = await p.evaluate(() => document.documentElement.scrollHeight);
  for (let i = 0, y = 0; y < total && i < 12; i++, y += vh) {
    await p.evaluate((y) => scrollTo(0, y), y); await p.waitForTimeout(150);
    await save(p, `article-screen-${String(i + 1).padStart(2, "0")}-${dev}`, { face: "article", part: "full-screens", variant: `default screen ${i + 1}`, dev }, { viewportOnly: true });
  }
  await p.close();
  await ctx.close();
}
await browser.close();

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
// 予定集合 = INDEX 上の cta-banner / article-full / article-screen（SP・PC）。撮影集合と完全一致しなければ置換に入らない
// （記事が短くなって画面数が減る等、一部だけ撮れた状態を成功扱いにしない）。
const planned = new Set(existing.filter((entry) => /^(cta-banner|article-full|article-screen-\d{2})-(sp|pc)\.jpg$/.test(entry.file)).map((entry) => entry.file));
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

// 置換: 旧画像を退避 → 新画像を配置。途中で失敗したら退避先から復元して元の例外を再送出する。
// 成功後の退避・一時ディレクトリ削除は復元処理と分離し、失敗しても配置済みの新画像には触れず警告のみ残す。
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction5-backup-"));
const backedUp = [];
try {
  for (const entry of index) {
    const target = path.join(OUT, entry.file);
    if (fs.existsSync(target)) { fs.renameSync(target, path.join(backupDir, entry.file)); backedUp.push(entry.file); }
  }
  for (const entry of index) fs.renameSync(path.join(TMP, entry.file), path.join(OUT, entry.file));
} catch (error) {
  const restoreFailures = [];
  for (const file of backedUp) {
    const backupPath = path.join(backupDir, file);
    if (!fs.existsSync(backupPath)) continue;
    try { fs.renameSync(backupPath, path.join(OUT, file)); } catch (restoreError) { restoreFailures.push({ file, error: String(restoreError) }); }
  }
  if (restoreFailures.length) console.error(`画像の復元に失敗しました。退避ディレクトリを手動で確認してください: ${backupDir}`, restoreFailures);
  throw error;
}
for (const [dir, label] of [[backupDir, "退避"], [TMP, "一時"]]) {
  try { fs.rmSync(dir, { recursive: true, force: true }); } catch (cleanupError) { console.error(`${label}ディレクトリの削除に失敗しました（配置済みの新画像はそのまま）: ${dir}`, cleanupError); }
}
// INDEX は画像の配置が完了した後に、一時ファイルへ書いてから rename で置換する。
const replacement = new Map(index.map((entry) => [entry.file, entry]));
const indexTmp = catalogFile + ".reaction5.tmp";
fs.writeFileSync(indexTmp, JSON.stringify(existing.map((entry) => replacement.get(entry.file) || entry), null, 1) + "\n");
fs.renameSync(indexTmp, catalogFile);
console.log("reaction5 done", index.length, "entries", existing.length);

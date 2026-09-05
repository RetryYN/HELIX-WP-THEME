#!/usr/bin/env node
// shots-reaction7.mjs — 2026-09-05 PO 反応 15 回目（WT-EVT-0257 データグラフ / WT-EVT-0259 metrics の SP 配置）の差分を撮影する。
// 対象: detext metrics（#cat-detext-metrics、SP 3 列化）の同名置換 2 枚と、データグラフ 4 型（#cat-graph-*）の新規 8 枚。
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
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction7-capture-"));
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
async function open(ctx, url) {
  const page = await ctx.newPage();
  await page.goto(BASE + url, { waitUntil: "networkidle" });
  await page.waitForTimeout(300);
  return page;
}

async function main() {
const browser = await chromium.launch();
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  const ctx = await browser.newContext(cfg);

  // detext metrics（SP 配置是正の反映）+ データグラフ 4 型
  const p = await open(ctx, CATALOG);
  const metrics = await p.evaluate(() => Array.from(document.querySelectorAll("#cat-detext-metrics .wt-detext__metric")).map((el) => Math.round(el.getBoundingClientRect().top)));
  if (metrics.length !== 3 || new Set(metrics).size !== 1) throw new Error(`metrics が 1 行に並んでいません（top: ${metrics.join(", ")}）`);
  await save(p, `reaction6-detext-metrics-${dev}`, { face: "article", part: "detext", variant: "metrics", dev }, { selector: "#cat-detext-metrics" });
  for (const k of ["bar", "stack", "donut", "line"]) {
    const ok = await p.evaluate((sel) => { const f = document.querySelector(sel + " figure.wt-graph"); return !!(f && f.querySelector("figcaption") && f.querySelector("table.wt-graph__data")); }, "#cat-graph-" + k);
    if (!ok) throw new Error(`グラフ ${k} に figure / figcaption / データ表が揃っていません`);
    await save(p, `graph-${k}-${dev}`, { face: "article", part: "graph", variant: k, dev }, { selector: "#cat-graph-" + k });
  }
  await p.close();
  await ctx.close();
}
} finally {
  await browser.close();
}

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
// 予定集合 = INDEX 上の reaction6-detext-metrics（同名置換 2 枚）+ 新規 8 枚（graph-{bar,stack,donut,line} × SP/PC）。
// 新規 4 枚は初回は追加、再実行時（既に INDEX にある）は同名置換として扱う。撮影集合と完全一致しなければ置換に入らない。
const NEW_FILES = ["bar", "stack", "donut", "line"].flatMap((k) => [`graph-${k}-sp.jpg`, `graph-${k}-pc.jpg`]);
const planned = new Set(existing.filter((entry) => /^reaction6-detext-metrics-(sp|pc)\.jpg$/.test(entry.file)).map((entry) => entry.file).concat(NEW_FILES));
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

// 置換: 旧画像を退避 → 新画像を配置。途中で失敗したら、配置済みの新画像を取り除き（退避のない新規分は削除）、退避先から旧画像を復元して元の例外を再送出する。
// 成功後の退避ディレクトリ削除は復元処理と分離し、失敗しても配置済みの新画像には触れず警告のみ残す。
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction7-backup-"));
const backedUp = new Set();
const placed = [];
let keepBackup = false;
try {
  for (const entry of index) {
    const target = path.join(OUT, entry.file);
    if (fs.existsSync(target)) { fs.renameSync(target, path.join(backupDir, entry.file)); backedUp.add(entry.file); }
  }
  for (const entry of index) { fs.renameSync(path.join(TMP, entry.file), path.join(OUT, entry.file)); placed.push(entry.file); }
} catch (error) {
  const restoreFailures = [];
  for (const file of placed) {
    // 退避のある同名置換は下で旧画像の rename が上書きする。退避のない新規分は INDEX 未登録の孤児になるため取り除く。
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
// INDEX は画像の配置が完了した後に、一時ファイルへ書いてから rename で置換する。既存エントリは同名置換、未登録の新規分だけ末尾に追加。
const replacement = new Map(index.map((entry) => [entry.file, entry]));
const known = new Set(existing.map((entry) => entry.file));
const added = index.filter((entry) => !known.has(entry.file));
const indexTmp = catalogFile + ".reaction7.tmp";
fs.writeFileSync(indexTmp, JSON.stringify(existing.map((entry) => replacement.get(entry.file) || entry).concat(added), null, 1) + "\n");
fs.renameSync(indexTmp, catalogFile);
console.log("reaction7 done", index.length, "entries", existing.length + added.length, "added", added.length);
}

// 一時ディレクトリは成否に関わらず最後に削除する（撮影中・照合・配置のどこで失敗しても残さない）。退避ディレクトリだけは復元失敗時に保持する。
try {
  await main();
} finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (cleanupError) { console.error(`一時ディレクトリの削除に失敗しました: ${TMP}`, cleanupError); }
}

#!/usr/bin/env node
// shots-reaction6.mjs — 2026-09-05 PO 反応 14 回目（WT-EVT-0255 商品カードの PR バッジ不要 / WT-EVT-0256 Q&A モーダル型追加）の差分を撮影する。
// 対象: 商品カード束（catalog #cat-cta-product、PR バッジ除去）と同パターンを本文に含む記事 full / full-screens の同名置換、
// 囲み Q&A モーダル型（#cat-box-qa-modal の閉状態と、ボタン押下後の開状態）の新規 4 枚。
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
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction6-capture-"));
async function save(page, name, meta, opts = {}) {
  const png = path.join(TMP, name + ".png"), jpg = path.join(TMP, name + ".jpg");
  if (opts.selector) {
    const el = page.locator(opts.selector).first();
    await el.waitFor({ state: "visible", timeout: 8000 });
    await el.scrollIntoViewIfNeeded();
    await page.waitForTimeout(150);
    await el.screenshot({ path: png });
  } else if (opts.viewportOnly) await page.screenshot({ path: png });
  // SP の全長撮影は DPR2 だと出力が 16384 device px を超え Chromium が "Unable to capture screenshot" を返す（実測: 記事 10758 css px）。
  // 全長画像は JPEG 化で長辺 1600 に縮むため、SP 全長だけ CSS px（1x）で出力する。情報量は縮小後と同等。
  else await page.screenshot({ path: png, fullPage: true, scale: meta.dev === "sp" ? "css" : "device" });
  toJpeg(png, jpg);
  if (!fs.statSync(jpg).size) throw new Error(`空の JPEG: ${jpg}`);
  index.push({ file: name + ".jpg", ...meta });
  console.log("shot", name);
}
// 撮影前の確認: 商品カード束が存在し、PR バッジ（.wt-badge--pr）が残っていないこと（catalog / 記事の両面）。
async function assertNoPrBadge(page, rootSelector, label) {
  const r = await page.evaluate((sel) => ({ cards: document.querySelectorAll(sel).length, badges: document.querySelectorAll(sel + " .wt-badge--pr").length }), rootSelector);
  if (!r.cards) throw new Error(`${label}: 商品カード束が見つかりません（${rootSelector}）`);
  if (r.badges) throw new Error(`${label}: 商品カードに PR バッジが残っています（${r.badges} 個）`);
}
async function open(ctx, url) {
  const page = await ctx.newPage();
  await page.goto(BASE + url, { waitUntil: "networkidle" });
  await page.waitForTimeout(300);
  return page;
}

const browser = await chromium.launch();
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  const ctx = await browser.newContext(cfg);

  // 記事 全長 + 画面単位（本文中の商品カード束の反映）
  let p = await open(ctx, ARTICLE);
  await assertNoPrBadge(p, ".wp-block-post-content .is-style-wt-product", `article ${dev}`);
  await save(p, `article-full-${dev}`, { face: "article", part: "full", variant: "default", dev });
  const vh = cfg.viewport.height, total = await p.evaluate(() => document.documentElement.scrollHeight);
  for (let i = 0, y = 0; y < total && i < 12; i++, y += vh) {
    await p.evaluate((y) => scrollTo(0, y), y); await p.waitForTimeout(150);
    await save(p, `article-screen-${String(i + 1).padStart(2, "0")}-${dev}`, { face: "article", part: "full-screens", variant: `default screen ${i + 1}`, dev }, { viewportOnly: true });
  }
  await p.close();
  // 商品カード束（catalog）: PR バッジ除去の反映
  p = await open(ctx, CATALOG);
  await assertNoPrBadge(p, "#cat-cta-product .is-style-wt-product", `catalog ${dev}`);
  await save(p, `cta-product-${dev}`, { face: "article", part: "cta", variant: "product", dev }, { selector: "#cat-cta-product" });

  // 囲み Q&A モーダル型（同じ context の最後に撮る。showModal 後に別ページの fullPage 撮影が失敗した実測があるため順序を固定）: 閉状態（要素撮影）→ ボタン押下 → 開状態（viewport 撮影。dialog は top layer なので要素撮影では背景が入らない）
  await save(p, `box-qa-modal-${dev}`, { face: "article", part: "box", variant: "qa-modal", dev }, { selector: "#cat-box-qa-modal" });
  const openBtn = p.locator("#cat-box-qa-modal .wt-qa-modal__open");
  await openBtn.waitFor({ state: "visible", timeout: 8000 });
  await openBtn.scrollIntoViewIfNeeded();
  await openBtn.click();
  await p.waitForFunction(() => { const d = document.querySelector("#cat-box-qa-modal dialog"); return d && d.open; }, null, { timeout: 5000 });
  await p.waitForTimeout(200);
  await save(p, `box-qa-modal-open-${dev}`, { face: "article", part: "box", variant: "qa-modal open", dev }, { viewportOnly: true });
  await p.keyboard.press("Escape");
  await p.close();

  await ctx.close();
}
} catch (error) {
  await browser.close();
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (_) { /* 一時 dir が残っても既存証跡には影響しない */ }
  throw error;
}
await browser.close();

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
// 予定集合 = INDEX 上の cta-product / article-full / article-screen（同名置換）+ 新規 4 枚（box-qa-modal / box-qa-modal-open × SP/PC）。
// 撮影集合と完全一致しなければ置換に入らない（一部だけ撮れた状態を成功扱いにしない）。
const NEW_FILES = ["box-qa-modal-sp.jpg", "box-qa-modal-open-sp.jpg", "box-qa-modal-pc.jpg", "box-qa-modal-open-pc.jpg"];
const planned = new Set(existing.filter((entry) => /^(cta-product|article-full|article-screen-\d{2})-(sp|pc)\.jpg$/.test(entry.file)).map((entry) => entry.file).concat(NEW_FILES));
const captured = new Set(index.map((entry) => entry.file));
const missing = [...planned].filter((f) => !captured.has(f));
const unknown = [...captured].filter((f) => !planned.has(f));
if (missing.length || unknown.length || captured.size !== index.length) {
  throw new Error(`撮影集合が予定集合と一致しません（予定 ${planned.size} / 撮影 ${captured.size}）。欠落: ${missing.join(", ") || "なし"} / 予定外: ${unknown.join(", ") || "なし"}`);
}
const alreadyKnownNew = NEW_FILES.filter((f) => existing.some((entry) => entry.file === f));
if (alreadyKnownNew.length) throw new Error(`新規予定のファイルが既に INDEX にあります: ${alreadyKnownNew.join(", ")}`);
for (const entry of index) {
  const tmp = path.join(TMP, entry.file);
  if (!fs.existsSync(tmp) || !fs.statSync(tmp).size) throw new Error(`一時ディレクトリ内の JPEG が見つからないか空です: ${tmp}`);
}

// 置換: 旧画像を退避 → 新画像を配置。途中で失敗したら退避先から復元して元の例外を再送出する。
// 成功後の退避・一時ディレクトリ削除は復元処理と分離し、失敗しても配置済みの新画像には触れず警告のみ残す。
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction6-backup-"));
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
const added = index.filter((entry) => NEW_FILES.includes(entry.file));
const indexTmp = catalogFile + ".reaction6.tmp";
fs.writeFileSync(indexTmp, JSON.stringify(existing.map((entry) => replacement.get(entry.file) || entry).concat(added), null, 1) + "\n");
fs.renameSync(indexTmp, catalogFile);
console.log("reaction6 done", index.length, "entries", existing.length + added.length);

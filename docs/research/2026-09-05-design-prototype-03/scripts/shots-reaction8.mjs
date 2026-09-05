#!/usr/bin/env node
// shots-reaction8.mjs — 2026-09-06 PO 反応 16 回目（WT-EVT-0261〜0266: related スライダー・SNS シェア・depth float・PR 文言・rich テーブル・フッタークレジット）の差分を撮影する。
// 対象: 同名置換 4 枚（pr-notice-one-line / tail-share-icons-row × SP/PC）と新規 8 枚（related-slider / axis-depth-float / table-rich / footer-credit-text × SP/PC）。
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
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction8-capture-"));
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

  // PR 表記の既定文言（PO 決定「本記事にはプロモーションが含まれます。」）と rich テーブル、depth:float は catalog で撮る
  let p = await open(ctx, CATALOG);
  const prText = await p.locator("#cat-pr .wt-pr").first().innerText();
  if (!prText.includes("本記事にはプロモーションが含まれます。")) throw new Error(`PR 表記の文言が PO 決定と異なります: ${prText}`);
  await save(p, `pr-notice-one-line-${dev}`, { face: "article", part: "pr-notice", variant: "one-line", dev }, { selector: "#cat-pr" });
  const rich = await p.evaluate(() => ({ imgs: document.querySelectorAll("#cat-table-rich .wt-tcell__img").length, btns: document.querySelectorAll("#cat-table-rich .wt-tbtn").length, icons: document.querySelectorAll("#cat-table-rich .wt-tcell__icon").length }));
  if (rich.imgs !== 3 || rich.btns !== 3 || rich.icons < 3) throw new Error(`rich テーブルの画像/ボタン/アイコンが揃っていません: ${JSON.stringify(rich)}`);
  await save(p, `table-rich-${dev}`, { face: "article", part: "table", variant: "rich", dev }, { selector: "#cat-table-rich" });
  await p.close();
  p = await open(ctx, CATALOG + "?wt=depth:float,motion:off");
  if (!(await p.evaluate(() => document.body.classList.contains("wt-depth-float")))) throw new Error("depth:float が body に反映されていません");
  await p.locator("#cat-cta-product .is-style-wt-product").first().hover();
  await p.waitForTimeout(450);
  await save(p, `axis-depth-float-${dev}`, { face: "article", part: "axis-depth", variant: "depth:float (hover)", dev }, { selector: "#cat-cta-product" });
  await p.close();

  // 記事面: related スライダー、SNS シェア、フッタークレジット
  p = await open(ctx, ARTICLE + "?wt=related:slider");
  const dots = await p.locator(".wt-tail__slot--related .wt-slider__dots button").count();
  if (dots < 2) throw new Error(`related:slider のドットが ${dots} 個しかありません`);
  await save(p, `related-slider-${dev}`, { face: "article", part: "related", variant: "slider", dev }, { selector: ".wt-tail__slot--related" });
  await p.close();
  p = await open(ctx, ARTICLE + "?wt=tail_share:icons-row");
  const sns = await p.locator(".wt-tail__slot--share a.wt-sns").count();
  if (sns !== 3) throw new Error(`SNS 共有先が ${sns} 件です（3 件を期待。Facebook は PO 判断待ち）`);
  await save(p, `tail-share-icons-row-${dev}`, { face: "article", part: "article-tail-share", variant: "icons-row", dev }, { selector: ".wt-tail__slot--share" });
  await p.close();
  p = await open(ctx, ARTICLE + "?wt=footer_credit:text");
  const credit = await p.locator(".wt-footer__credit").first();
  if (!(await credit.isVisible())) throw new Error("footer_credit:text でクレジットが表示されていません");
  await save(p, `footer-credit-text-${dev}`, { face: "footer", part: "footer-credit", variant: "text", dev }, { selector: "footer .wt-footer__legal--links, footer" });
  await p.close();
  await ctx.close();
}
} finally {
  await browser.close();
}

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
// 予定集合 = INDEX 上の pr-notice-one-line / tail-share-icons-row（同名置換 4 枚）+ 新規 8 枚（related-slider / axis-depth-float / table-rich / footer-credit-text × SP/PC）。
// 新規 4 枚は初回は追加、再実行時（既に INDEX にある）は同名置換として扱う。撮影集合と完全一致しなければ置換に入らない。
const NEW_FILES = ["related-slider", "axis-depth-float", "table-rich", "footer-credit-text"].flatMap((k) => [`${k}-sp.jpg`, `${k}-pc.jpg`]);
const planned = new Set(existing.filter((entry) => /^(pr-notice-one-line|tail-share-icons-row)-(sp|pc)\.jpg$/.test(entry.file)).map((entry) => entry.file).concat(NEW_FILES));
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
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction8-backup-"));
const backedUp = new Set();
const placed = [];
let keepBackup = false;
// INDEX の新内容は先に作っておく（既存エントリは同名置換、未登録の新規分だけ末尾に追加）。
const replacement = new Map(index.map((entry) => [entry.file, entry]));
const known = new Set(existing.map((entry) => entry.file));
const added = index.filter((entry) => !known.has(entry.file));
const nextIndex = JSON.stringify(existing.map((entry) => replacement.get(entry.file) || entry).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction8.tmp";
try {
  for (const entry of index) {
    const target = path.join(OUT, entry.file);
    if (fs.existsSync(target)) { fs.renameSync(target, path.join(backupDir, entry.file)); backedUp.add(entry.file); }
  }
  for (const entry of index) { fs.renameSync(path.join(TMP, entry.file), path.join(OUT, entry.file)); placed.push(entry.file); }
  // INDEX は画像配置の直後・退避削除の前に、一時ファイルへ書いてから rename で置換する（失敗したら下の catch で画像も戻す。rename は原子的で旧 INDEX は壊れない）。
  fs.writeFileSync(indexTmp, nextIndex);
  fs.renameSync(indexTmp, catalogFile);
} catch (error) {
  const restoreFailures = [];
  try { fs.rmSync(indexTmp, { force: true }); } catch (_) { /* 一時 INDEX が残っても旧 INDEX は無傷 */ }
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
console.log("reaction8 done", index.length, "entries", existing.length + added.length, "added", added.length);
}

// 一時ディレクトリは成否に関わらず最後に削除する（撮影中・照合・配置のどこで失敗しても残さない）。退避ディレクトリだけは復元失敗時に保持する。
try {
  await main();
} finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (cleanupError) { console.error(`一時ディレクトリの削除に失敗しました: ${TMP}`, cleanupError); }
}

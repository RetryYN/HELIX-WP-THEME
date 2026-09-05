#!/usr/bin/env node
// shots-reaction9.mjs — 2026-09-06 PO 反応 16 回目 WT-EVT-0268（LP パーツ 7 種、台帳 lp-recapture の多数派を既定）の新規撮影。
// 対象: 新規 41 枚（extended の分割数は高さから決まる: 2026-09-06 時点で SP 3 + PC 6）+ 15 型 × 2 + line-sticky × 2。当初コメントの 38 は誤り（lp-sections extended ×2、lp-interview 3 型・lp-review 3 型・lp-rating 3 型・lp-download 2 型・lp-form 2 型・lp-line 2 型・lp-fixed line-sticky を SP/PC）。同名置換なし。
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
const LP = "/lp/";
fs.mkdirSync(OUT, { recursive: true });
const index = [];
const SP = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const PC = { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 };

function toJpeg(png, jpg) {
  execFileSync("ffmpeg", ["-y", "-loglevel", "error", "-i", png, "-vf", "scale='if(gte(iw,ih),min(1600,iw),-2)':'if(gte(iw,ih),-2,min(1600,ih))'", "-q:v", "5", jpg]);
  fs.unlinkSync(png);
}
// 撮影は一時ディレクトリへ行う。results/ の既存 JPEG は promote() で全枚数の検査を通った後にだけ置換される。
const TMP = fs.mkdtempSync(path.join(OUT, ".reaction9-capture-"));
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

const PARTS = [
  ["interview", ["summary-card", "link-card", "logo-only"], ".wt-lp__section--interview"],
  ["review", ["quote-photo", "stars-count", "satisfaction-number"], ".wt-lp__section--review"],
  ["rating", ["certification", "client-logos", "award-badge"], ".wt-lp__section--rating"],
  ["download", ["button-to-form", "form-inline"], ".wt-lp__section--download"],
  ["form", ["external", "inline"], ".wt-lp__section--form"],
  ["line", ["button", "qr"], ".wt-lp__section--line"],
];
const EXTENDED_CHUNKS = {};
let browser = null;
async function main() {
try {
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  // メモリ逼迫で PC 側の page が落ちるため、dev ごとにブラウザを起動し直す（1 インスタンスで SP→PC を続けると PC の goto で "Page crashed" を実測）
  if (browser) await browser.close();
  browser = await chromium.launch();
  const ctx = await browser.newContext(cfg);
  // 全区間 + 7 種（extended）: 区間群は SP 11,500 css px / PC 8,500 css px と高く、この環境では要素撮影・fullPage とも
  // "Unable to capture screenshot" になる（DPR1 でも失敗を実測）。そのため高さ 4000px の viewport（DPR1）で上から順に viewport 撮影し、
  // 「extended 1/N … N/N」として分割保存する。N は撮影前に区間群の高さから決め、予定集合の照合に使う。
  const CHUNK = dev === "sp" ? 4000 : 1600; // PC は 1440×4000 / 2200 の viewport 撮影でレンダラが落ちたため 1600
  // extended は他の撮影と別のブラウザインスタンスで撮り、終わったら閉じる（同一インスタンス内で PC 2 枚目に "Page crashed" を実測）
  const ctxShot = await browser.newContext({ ...cfg, deviceScaleFactor: 1, viewport: { width: cfg.viewport.width, height: CHUNK } });
  let p = await open(ctxShot, LP + "?wt=lp_sections:extended");
  const m = await p.evaluate(() => { const el = document.querySelector(".wt-lp__sections"); const r = el.getBoundingClientRect(); return { shown: Array.from(document.querySelectorAll(".wt-lp__sections > .wt-lp__section")).filter((s) => s.getBoundingClientRect().height > 0).length, top: r.top + scrollY, height: r.height }; });
  if (m.shown !== 18) throw new Error(`lp_sections:extended の表示区間が ${m.shown}（18 を期待）`);
  const chunks = Math.ceil(m.height / CHUNK);
  EXTENDED_CHUNKS[dev] = chunks;
  for (let i = 0; i < chunks; i++) {
    await p.evaluate(([y]) => scrollTo(0, y), [m.top + i * CHUNK]); await p.waitForTimeout(250);
    await save(p, `lp-sections-extended-${i + 1}-${dev}`, { face: "lp", part: "lp-sections", variant: `extended ${i + 1}/${chunks}`, dev }, { viewportOnly: true });
  }
  await p.close(); await ctxShot.close();
  for (const [part, variants, selector] of PARTS) {
    for (const v of variants) {
      p = await open(ctx, LP + `?wt=lp_sections:extended,lp_${part}:${v}`);
      const ok = await p.evaluate(([part, v]) => document.body.classList.contains(`wt-lp-${part}-${v}`) && (() => { const el = document.querySelector(`.wt-lp-${part}--${v}`); return !!el && el.getBoundingClientRect().height > 0; })(), [part, v]);
      if (!ok) throw new Error(`lp_${part}:${v} が反映されていません`);
      await save(p, `lp-${part}-${v}-${dev}`, { face: "lp", part: `lp-${part}`, variant: v, dev }, { selector });
      await p.close();
    }
  }
  p = await open(ctx, LP + "?wt=lp_fixed:line-sticky");
  const sticky = await p.locator(".wt-lp-fixed--line-sticky").first();
  if (!(await sticky.isVisible())) throw new Error("lp_fixed:line-sticky が表示されていません");
  await save(p, `lp-fixed-line-sticky-${dev}`, { face: "lp", part: "lp-fixed", variant: "line-sticky", dev }, { viewportOnly: true });
  await p.close();
  await ctx.close();
}
} finally {
  if (browser) await browser.close();
}

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = JSON.parse(fs.readFileSync(catalogFile, "utf8"));
const NEW_FILES = [...PARTS.flatMap(([part, variants]) => variants.map((v) => `lp-${part}-${v}`)), "lp-fixed-line-sticky"].flatMap((k) => [`${k}-sp.jpg`, `${k}-pc.jpg`])
  .concat(...["sp", "pc"].map((dev) => Array.from({ length: EXTENDED_CHUNKS[dev] || 0 }, (_, i) => `lp-sections-extended-${i + 1}-${dev}.jpg`)));
if (!EXTENDED_CHUNKS.sp || !EXTENDED_CHUNKS.pc) throw new Error("extended の分割数が決まっていません");
const planned = new Set(NEW_FILES);
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
const backupDir = fs.mkdtempSync(path.join(OUT, ".reaction9-backup-"));
const backedUp = new Set();
const placed = [];
let keepBackup = false;
// INDEX の新内容は先に作っておく（既存エントリは同名置換、未登録の新規分だけ末尾に追加）。
const replacement = new Map(index.map((entry) => [entry.file, entry]));
const known = new Set(existing.map((entry) => entry.file));
const added = index.filter((entry) => !known.has(entry.file));
// 分割数が前回より減った場合、旧 extended チャンク（今回の予定集合に無いもの）は INDEX から外し、画像も退避 dir へ移す（成功時に削除、失敗時に復元）
const stale = existing.filter((entry) => /^lp-sections-extended-\d+-(sp|pc)\.jpg$/.test(entry.file) && !planned.has(entry.file)).map((entry) => entry.file);
const nextIndex = JSON.stringify(existing.filter((entry) => !stale.includes(entry.file)).map((entry) => replacement.get(entry.file) || entry).concat(added), null, 1) + "\n";
const indexTmp = catalogFile + ".reaction9.tmp";
try {
  for (const entry of index) {
    const target = path.join(OUT, entry.file);
    if (fs.existsSync(target)) { fs.renameSync(target, path.join(backupDir, entry.file)); backedUp.add(entry.file); }
  }
  for (const file of stale) { const t = path.join(OUT, file); if (fs.existsSync(t)) { fs.renameSync(t, path.join(backupDir, file)); backedUp.add(file); } }
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
console.log("reaction9 done", index.length, "entries", existing.length + added.length, "added", added.length);
}

// 一時ディレクトリは成否に関わらず最後に削除する（撮影中・照合・配置のどこで失敗しても残さない）。退避ディレクトリだけは復元失敗時に保持する。
try {
  await main();
} finally {
  try { fs.rmSync(TMP, { recursive: true, force: true }); } catch (cleanupError) { console.error(`一時ディレクトリの削除に失敗しました: ${TMP}`, cleanupError); }
}

#!/usr/bin/env node
// shots-reaction5.mjs — 2026-09-05 PO 反応 13 回目（WT-EVT-0254「PRが残ってるけど？」）の是正差分だけを再撮影する。
// 対象: CTA バナー（catalog の #cat-cta-banner、キャプション先頭の「PR: 」を除去）と、同パターンを本文に含む記事 full / full-screens。
// CATALOG-INDEX.json は同名ファイルだけ置換する（reaction2 と同じ merge 方式。新規エントリは無い）。
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
// 撮影は一時 PNG → JPEG 変換の順で行い、既存 JPEG は変換成功時にだけ上書きされる（事前削除しない）。
async function save(page, name, meta, opts = {}) {
  const png = path.join(OUT, name + ".png"), jpg = path.join(OUT, name + ".jpg");
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

const browser = await chromium.launch();
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  const ctx = await browser.newContext(cfg);

  // CTA バナー（catalog）: キャプションに「PR: 」が残っていないことを撮影前に確認する
  let p = await open(ctx, CATALOG);
  const caption = await p.locator("#cat-cta-banner figcaption").first().innerText();
  if (/^\s*(PR|広告|アフィリエイト)/.test(caption)) throw new Error(`CTA バナーのキャプションに PR 表記が残っています: ${caption}`);
  await save(p, `cta-banner-${dev}`, { face: "article", part: "cta", variant: "banner", dev }, { selector: "#cat-cta-banner" });
  await p.close();

  // 記事 全長 + 画面単位（本文中の CTA バナーの反映）
  p = await open(ctx, ARTICLE);
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
const known = new Set(existing.map((entry) => entry.file));
const unknown = index.filter((entry) => !known.has(entry.file));
if (unknown.length) throw new Error(`既存エントリに無いファイルを撮影しました（本スクリプトは置換のみ）: ${unknown.map((e) => e.file).join(", ")}`);
const replacement = new Map(index.map((entry) => [entry.file, entry]));
fs.writeFileSync(catalogFile, JSON.stringify(existing.map((entry) => replacement.get(entry.file) || entry), null, 1) + "\n");
console.log("reaction5 done", index.length, "entries", existing.length);

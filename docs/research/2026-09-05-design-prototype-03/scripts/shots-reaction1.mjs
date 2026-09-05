#!/usr/bin/env node
// shots-reaction1.mjs — 2026-09-05 PO 反応 1 回目の是正差分だけを再撮影する。
// 対象: 記事 full / full-screens、header 4 種（PC）、table-compare（SP/PC）、width プリセット 3 種 × SP/PC。
// CATALOG-INDEX.json は同名ファイルだけ置換し、他のエントリは保持する（stage3/stage4 と同じ merge 方式）。
// 使い方: NODE_PATH=<playwright の node_modules> node shots-reaction1.mjs --base http://localhost:8086 --out ../results
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
async function save(page, name, meta, opts = {}) {
  const png = path.join(OUT, name + ".png"), jpg = path.join(OUT, name + ".jpg");
  if (opts.selector) {
    const el = page.locator(opts.selector).first();
    await el.waitFor({ state: "visible", timeout: 8000 });
    await el.screenshot({ path: png });
  } else if (opts.viewportOnly) await page.screenshot({ path: png });
  else await page.screenshot({ path: png, fullPage: true });
  toJpeg(png, jpg);
  index.push({ file: name + ".jpg", ...meta });
  console.log("shot", name);
}
async function open(ctx, url) {
  const page = await ctx.newPage();
  await page.goto(BASE + url, { waitUntil: "networkidle" });
  await page.waitForTimeout(300);
  return page;
}
const wt = (q) => `?wt=${q}`;

const browser = await chromium.launch();
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  const ctx = await browser.newContext(cfg);

  // 記事 全長 + 画面単位（table caption / 価格文字サイズ是正の再確認）
  let p = await open(ctx, ARTICLE);
  await save(p, `article-full-${dev}`, { face: "article", part: "full", variant: "default", dev });
  const vh = cfg.viewport.height, total = await p.evaluate(() => document.documentElement.scrollHeight);
  for (let i = 0, y = 0; y < total && i < 10; i++, y += vh) {
    await p.evaluate((y) => scrollTo(0, y), y); await p.waitForTimeout(150);
    await save(p, `article-screen-${String(i + 1).padStart(2, "0")}-${dev}`, { face: "article", part: "full-screens", variant: `default screen ${i + 1}`, dev }, { viewportOnly: true });
  }
  await p.close();

  // ヘッダー（PC 4 種: wideSize 1120→1440、CTA 中央寄せ是正）
  if (dev === "pc") {
    for (const [v, q] of [["search", "header:search"], ["nav", "header:nav"], ["cta", "header:cta"], ["announce", "header:announce"]]) {
      p = await open(ctx, ARTICLE + wt(q));
      await save(p, `header-${v}-${dev}`, { face: "article", part: "header", variant: v, dev }, { selector: ".wt-header" });
      await p.close();
    }
  }

  // 比較表（caption 縦積み是正・価格文字サイズ是正）
  p = await open(ctx, CATALOG);
  await save(p, `table-compare-${dev}`, { face: "article", part: "table", variant: "compare", dev }, { selector: "#cat-table" });
  await p.close();

  // 幅プリセット 3 種（narrow / default / wide）。記事面で撮影し、本文列幅・ヘッダー内側幅の見た目差を残す。
  for (const preset of ["narrow", "default", "wide"]) {
    p = await open(ctx, ARTICLE + wt(`width:${preset}`));
    await save(p, `width-${preset}-${dev}`, { face: "article", part: "width", variant: preset, dev }, { viewportOnly: true });
    await p.close();
  }

  // 404（fullPage キャプチャでヘッダー全体を含むため、wideSize 1120→1440 の影響を受ける）
  for (const v of ["popular", "cta", "suggest"]) {
    p = await open(ctx, "/no-such-page-standing-desk-guide/" + wt("nf:" + v));
    await save(p, `404-${v}-${dev}`, { face: "404", part: "404", variant: v, dev });
    await p.close();
  }

  await ctx.close();
}
await browser.close();

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = fs.existsSync(catalogFile) ? JSON.parse(fs.readFileSync(catalogFile, "utf8")) : [];
const newFiles = new Set(index.map((entry) => entry.file));
fs.writeFileSync(catalogFile, JSON.stringify(existing.filter((entry) => !newFiles.has(entry.file)).concat(index), null, 1));
console.log("reaction1 done", index.length);

#!/usr/bin/env node
// shots-reaction2.mjs — 2026-09-05 PO 反応 2〜5 回目の是正差分だけを再撮影する。
// 対象: 記事 full / full-screens（h3 番号・2tone 下線・囲み追加の反映）、h2 新規4型、h3 新規2型、
// 囲み新規5型、CTA 新規4型、cat-pr（タグ縦積み是正）。
// CATALOG-INDEX.json は同名ファイルだけ置換・新規は追加する（stage3/4/reaction1 と同じ merge 方式）。
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
    await el.scrollIntoViewIfNeeded();
    await page.waitForTimeout(150);
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

const browser = await chromium.launch();
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  const ctx = await browser.newContext(cfg);

  // 記事 全長 + 画面単位（h3 番号是正・2tone 下線詰め・囲み2型追加の反映）
  let p = await open(ctx, ARTICLE);
  await save(p, `article-full-${dev}`, { face: "article", part: "full", variant: "default", dev });
  const vh = cfg.viewport.height, total = await p.evaluate(() => document.documentElement.scrollHeight);
  for (let i = 0, y = 0; y < total && i < 12; i++, y += vh) {
    await p.evaluate((y) => scrollTo(0, y), y); await p.waitForTimeout(150);
    await save(p, `article-screen-${String(i + 1).padStart(2, "0")}-${dev}`, { face: "article", part: "full-screens", variant: `default screen ${i + 1}`, dev }, { viewportOnly: true });
  }
  await p.close();

  // h2 新規4型 + 既存 icon（アイコン差し替え）/ underline（詰め）
  p = await open(ctx, CATALOG);
  for (const s of ["icon", "2tone", "underline", "numbox", "barbg", "doubleline", "label"]) {
    await save(p, `h2-${s}-${dev}`, { face: "article", part: "h2", variant: s, dev }, { selector: "#cat-h2-" + s });
  }
  // h3 新規2型 + 既存 dotted（詰め）/ num（サイズ・縦積み是正）
  for (const s of ["dotted", "num", "marker", "underline-thin"]) {
    await save(p, `h3-${s}-${dev}`, { face: "article", part: "h3", variant: s, dev }, { selector: "#cat-h3-" + s });
  }
  // 囲み新規5型
  for (const s of ["quote", "dashed", "steps", "qa", "warn-soft"]) {
    await save(p, `box-${s}-${dev}`, { face: "article", part: "box", variant: s, dev }, { selector: "#cat-box-" + s });
  }
  // CTA 新規4型
  for (const s of ["triple", "rank", "price-tier", "textlink"]) {
    await save(p, `cta-${s}-${dev}`, { face: "article", part: "cta", variant: s, dev }, { selector: "#cat-cta-" + s });
  }
  // PR（タグ縦積み是正）
  await save(p, `pr-notice-one-line-${dev}`, { face: "article", part: "pr-notice", variant: "one-line", dev }, { selector: "#cat-pr" });
  await p.close();

  // toc-float（サイドカラム幅トークン変更で left が動いた分の再撮影。PC のみ）
  if (dev === "pc") {
    const tp = await open(ctx, ARTICLE + "?wt=toc:float");
    await tp.evaluate(() => scrollTo(0, 900));
    await tp.waitForTimeout(400);
    await save(tp, `toc-float-${dev}`, { face: "article", part: "toc", variant: "float", dev }, { viewportOnly: true });
    await tp.close();
  }

  await ctx.close();
}
await browser.close();

const catalogFile = path.join(OUT, "..", "CATALOG-INDEX.json");
const existing = fs.existsSync(catalogFile) ? JSON.parse(fs.readFileSync(catalogFile, "utf8")) : [];
const newFiles = new Set(index.map((entry) => entry.file));
fs.writeFileSync(catalogFile, JSON.stringify(existing.filter((entry) => !newFiles.has(entry.file)).concat(index), null, 1));
console.log("reaction2 done", index.length);

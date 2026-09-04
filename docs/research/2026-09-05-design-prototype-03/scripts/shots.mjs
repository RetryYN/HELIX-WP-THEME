#!/usr/bin/env node
// shots.mjs — 試作 03 の変種を要素単位で撮影し、results/ に JPEG（q75、長辺 ≤1600）と CATALOG-INDEX.json を書く。
// 使い方: NODE_PATH=<playwright の node_modules> node shots.mjs --base http://localhost:8086 --out ../results
import fs from "node:fs";
import path from "node:path";
import { execFileSync } from "node:child_process";
import { createRequire } from "node:module";
const require = createRequire(process.env.NODE_PATH ? path.join(process.env.NODE_PATH, "x.js") : import.meta.url); // NODE_PATH の playwright を優先（リポ内の別版と混ざらないように）
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
  // 長辺 1600 に収め、JPEG q≈75（mjpeg qscale 5）
  execFileSync("ffmpeg", ["-y", "-loglevel", "error", "-i", png, "-vf", "scale='if(gte(iw,ih),min(1600,iw),-2)':'if(gte(iw,ih),-2,min(1600,ih))'", "-q:v", "5", jpg]);
  fs.unlinkSync(png);
}
async function save(page, name, meta, opts = {}) {
  const png = path.join(OUT, name + ".png"), jpg = path.join(OUT, name + ".jpg");
  if (opts.selector) {
    const el = page.locator(opts.selector).first();
    await el.waitFor({ state: "visible", timeout: 8000 });
    if (opts.clipHeight) {
      const b = await el.boundingBox();
      await page.screenshot({ path: png, clip: { x: b.x, y: b.y, width: b.width, height: Math.min(opts.clipHeight, b.height) } });
    } else await el.screenshot({ path: png });
  } else if (opts.viewportOnly) await page.screenshot({ path: png });
  else await page.screenshot({ path: png, fullPage: true });
  toJpeg(png, jpg);
  index.push({ file: name + ".jpg", ...meta });
  console.log("shot", name);
}
async function open(ctx, url, opts = {}) {
  const page = await ctx.newPage();
  await page.goto(BASE + url, { waitUntil: "networkidle" });
  if (opts.settle !== false) await page.waitForTimeout(300);
  return page;
}
const wt = (q) => `?wt=${q}`;

const browser = await chromium.launch();
for (const [dev, cfg] of [["sp", SP], ["pc", PC]]) {
  const ctx = await browser.newContext(cfg);
  // 記事 全長（既定）
  let p = await open(ctx, ARTICLE);
  await save(p, `article-full-${dev}`, { face: "article", part: "full", variant: "default", dev });
  // 全長は長辺 1600 に縮むため、画面単位（viewport 高さごと）の連番も保存する
  const vh = cfg.viewport.height, total = await p.evaluate(() => document.documentElement.scrollHeight);
  for (let i = 0, y = 0; y < total && i < 10; i++, y += vh) {
    await p.evaluate((y) => scrollTo(0, y), y); await p.waitForTimeout(150);
    await save(p, `article-screen-${String(i + 1).padStart(2, "0")}-${dev}`, { face: "article", part: "full-screens", variant: `default screen ${i + 1}`, dev }, { viewportOnly: true });
  }
  await p.close();

  // ヘッダー
  const headers = dev === "pc" ? [["search", "header:search"], ["nav", "header:nav"], ["cta", "header:cta"], ["announce", "header:announce"]] : [["hamburger-search", "sp:search"], ["hamburger-right", "sp:right"], ["hamburger-left", "sp:left"], ["announce", "header:announce"]];
  for (const [v, q] of headers) {
    p = await open(ctx, ARTICLE + wt(q));
    await save(p, `header-${v}-${dev}`, { face: "article", part: "header", variant: v, dev }, { selector: ".wt-header" });
    await p.close();
  }
  // アイキャッチ 5
  for (const v of ["title-image", "image-title", "hero", "side", "none"]) {
    p = await open(ctx, ARTICLE + wt("eyecatch:" + v));
    await p.waitForTimeout(400);
    await save(p, `eyecatch-${v}-${dev}`, { face: "article", part: "eyecatch", variant: v, dev }, { selector: ".wt-posthead" });
    await p.close();
  }
  // 目次 4
  for (const v of ["box", "float", "collapsible", "none"]) {
    p = await open(ctx, ARTICLE + wt("toc:" + v));
    if (v === "none") await save(p, `toc-${v}-${dev}`, { face: "article", part: "toc", variant: v, dev }, { selector: ".wp-block-post-content", clipHeight: 520 });
    else if (v === "float" && dev === "pc") { await p.evaluate(() => scrollTo(0, 900)); await p.waitForTimeout(400); await save(p, `toc-${v}-${dev}`, { face: "article", part: "toc", variant: v, dev }, { viewportOnly: true }); }
    else await save(p, `toc-${v}-${dev}`, { face: "article", part: "toc", variant: v, dev }, { selector: ".wt-toc" });
    await p.close();
  }
  if (dev === "sp") { // SP の box は閉じた状態と開いた状態
    p = await open(ctx, ARTICLE);
    await p.locator(".wt-toc summary").click(); await p.waitForTimeout(200);
    await save(p, `toc-box-open-sp`, { face: "article", part: "toc", variant: "box-open", dev }, { selector: ".wt-toc" });
    await p.close();
  }
  // カタログ: h2 / h3 / 囲み / CTA / 表 ほか
  p = await open(ctx, CATALOG);
  const cat = [
    ...["plain", "2tone", "icon", "bar", "underline", "band"].map((v) => ["h2", v, "#cat-h2-" + v]),
    ...["bar-thin", "dotted", "num"].map((v) => ["h3", v, "#cat-h3-" + v]),
    ...["plain-border", "tinted", "band-title", "tab-title", "label-title", "shadow-card", "check-list", "colors"].map((v) => ["box", v, "#cat-box-" + v]),
    ...["product", "banner", "button", "box"].map((v) => ["cta", v, "#cat-cta-" + v]),
    ["table", "compare", "#cat-table"], ["pros-cons", "label-title", "#cat-prosc"], ["review-bar", "default", "#cat-rate"], ["linkcard", "internal", "#cat-linkcard"], ["pr-notice", "one-line", "#cat-pr"],
    ["detext", "styles", "#cat-detext"],
  ];
  for (const [part, v, sel] of cat) await save(p, `${part}-${v}-${dev}`, { face: "article", part, variant: v, dev }, { selector: sel });
  // 自動コントラスト 3 輝度（data-wt-lum を読む）
  await p.waitForTimeout(500);
  for (const v of ["dark", "mid", "light"]) {
    const lum = await p.getAttribute(`#cat-contrast-${v}`, "data-wt-lum");
    const lv = await p.getAttribute(`#cat-contrast-${v}`, "data-wt-lum-value");
    await save(p, `contrast-guard-${v}-${dev}`, { face: "article", part: "contrast-guard", variant: `${v} (data-wt-lum=${lum}, L=${lv})`, dev }, { selector: `#cat-contrast-${v}` });
  }
  await p.close();
  // 記事末 関連 4
  for (const v of ["grid", "list", "rank", "carousel"]) {
    p = await open(ctx, ARTICLE + wt("related:" + v));
    await save(p, `related-${v}-${dev}`, { face: "article", part: "related", variant: v, dev }, { selector: ".wt-tail" });
    await p.close();
  }
  // 共有 float
  p = await open(ctx, ARTICLE + wt("share:float"));
  await p.evaluate(() => scrollTo(0, 600)); await p.waitForTimeout(300);
  await save(p, `share-float-${dev}`, { face: "article", part: "share", variant: "float", dev }, { viewportOnly: true });
  await p.close();
  p = await open(ctx, ARTICLE);
  await save(p, `share-top-${dev}`, { face: "article", part: "share", variant: "top-and-bottom (top)", dev }, { selector: ".wt-share--top" });
  await p.close();
  // 4 軸 on/off
  for (const [axis, off, on, sel, url] of [
    ["depth", "depth:0", "depth:2", "#cat-cta-product", CATALOG],
    ["depth-tail", "depth:0", "depth:1", ".wt-tail", ARTICLE],
    ["density", "density:compact", "density:airy", ".wp-block-post-content", ARTICLE],
    ["detext", "detext:off", "detext:on", ".wt-toc", ARTICLE],
    ["detext-list", "detext:off", "detext:on", "#cat-detext", CATALOG],
  ]) {
    for (const [state, q] of [["off", off], ["on", on]]) {
      p = await open(ctx, url + wt(q));
      await save(p, `axis-${axis}-${state}-${dev}`, { face: "article", part: "axis-" + axis.split("-")[0], variant: `${q}`, dev }, { selector: sel, clipHeight: axis === "density" ? 900 : undefined });
      await p.close();
    }
  }
  // motion: on では出現前の要素が透明（遷移途中を撮る）、off では即表示
  for (const [state, q] of [["off", "motion:off"], ["on", "motion:on"]]) {
    p = await open(ctx, ARTICLE + wt(q));
    const y = await p.evaluate(() => document.querySelector(".wt-tail").getBoundingClientRect().top + scrollY - 200);
    await p.evaluate((y) => scrollTo(0, y), y);
    await p.waitForTimeout(60);
    await save(p, `axis-motion-${state}-${dev}`, { face: "article", part: "axis-motion", variant: q + " (60ms after scroll)", dev }, { viewportOnly: true });
    await p.close();
  }
  // 404 3 変種
  for (const v of ["popular", "cta", "suggest"]) {
    p = await open(ctx, "/no-such-page-standing-desk-guide/" + wt("nf:" + v));
    await save(p, `404-${v}-${dev}`, { face: "404", part: "404", variant: v, dev });
    await p.close();
  }
  await ctx.close();
}
await browser.close();
fs.writeFileSync(path.join(OUT, "..", "CATALOG-INDEX.json"), JSON.stringify(index, null, 1));
console.log("done", index.length);

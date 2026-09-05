#!/usr/bin/env node
// shots-reaction3.mjs — PO反応6・7の追加型だけをSP/PCで撮影する。
// 既存画像・CATALOG-INDEX.jsonの既存エントリは変更せず、未登録ファイルだけ末尾へ追加する。
import fs from "node:fs";
import path from "node:path";
import { execFileSync } from "node:child_process";
import { createRequire } from "node:module";

const require = createRequire(process.env.NODE_PATH ? path.join(process.env.NODE_PATH, "x.js") : import.meta.url);
const { chromium } = require("playwright");
const args = Object.fromEntries(process.argv.slice(2).map((a, i, arr) => a.startsWith("--") ? [a.slice(2), arr[i + 1]] : null).filter(Boolean));
const BASE = args.base || "http://localhost:8086";
const OUT = path.resolve(args.out || "results");
const ARTICLE = "/standing-desk-compare/";
const CATALOG = "/catalog-03/";
const SP = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const PC = { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 };
fs.mkdirSync(OUT, { recursive: true });

function toJpeg(png, jpg) {
  execFileSync("ffmpeg", ["-y", "-loglevel", "error", "-i", png, "-vf", "scale='if(gte(iw,ih),min(1600,iw),-2)':'if(gte(iw,ih),-2,min(1600,ih))'", "-q:v", "5", jpg]);
  fs.unlinkSync(png);
}

async function save(page, name, meta, selector) {
  const png = path.join(OUT, name + ".png");
  const jpg = path.join(OUT, name + ".jpg");
  const el = page.locator(selector).first();
  await el.waitFor({ state: "visible", timeout: 8000 });
  await el.scrollIntoViewIfNeeded();
  await page.evaluate((sel) => {
    const root = document.querySelector(sel);
    Array.from(root ? root.querySelectorAll("img") : []).forEach((image) => { image.loading = "eager"; });
  }, selector);
  // 未着手の loading="lazy" 画像は complete が true を返すため、complete ではなく naturalWidth > 0（=読み込み済み）を全画像で待つ
  // （PC 撮影で関連カードのサムネが白抜けした実測への対応。上限 5 秒）
  await page.waitForFunction((sel) => { const root = document.querySelector(sel); return Array.from(root ? root.querySelectorAll("img") : []).every((image) => image.naturalWidth > 0); }, selector, { timeout: 5000 }).catch(() => null);
  await page.evaluate(async (sel) => { const root = document.querySelector(sel); await Promise.all(Array.from(root ? root.querySelectorAll("img") : []).map((image) => image.decode ? image.decode().catch(() => null) : null)); }, selector);
  // 要素が viewport より高いと Playwright が撮影時に viewport を拡縮し、srcset + sizes="auto" の画像が候補を再選択して白抜けする実測があった。
  // 先に viewport を要素の高さまで広げ、画像を待ち直してから撮影し、終わったら元に戻す。
  const vp = page.viewportSize(); const box = await el.boundingBox();
  const grow = box && vp && box.height + 120 > vp.height;
  if (grow) {
    await page.setViewportSize({ width: vp.width, height: Math.ceil(box.height) + 120 });
    await el.scrollIntoViewIfNeeded();
    await page.waitForFunction((sel) => { const root = document.querySelector(sel); return Array.from(root ? root.querySelectorAll("img") : []).every((image) => image.naturalWidth > 0); }, selector, { timeout: 5000 }).catch(() => null);
    await page.evaluate(async (sel) => { const root = document.querySelector(sel); await Promise.all(Array.from(root ? root.querySelectorAll("img") : []).map((image) => image.decode ? image.decode().catch(() => null) : null)); }, selector);
  }
  await page.waitForTimeout(500);
  await el.screenshot({ path: png });
  if (grow) await page.setViewportSize(vp);
  toJpeg(png, jpg);
  return { file: name + ".jpg", ...meta };
}

const catalogParts = [
  ["compare-table", "striped", "#cat-table-striped"],
  ["compare-table", "evaluation", "#cat-table-evaluation"],
  ["compare-table", "price", "#cat-table-price"],
  ["compare-table", "showdown", "#cat-table-showdown"],
  ["pros-cons", "contrast", "#cat-prosc-contrast"],
  ["pros-cons", "icons", "#cat-prosc-icons"],
  ["pros-cons", "band", "#cat-prosc-band"],
  ["review-bar", "stars", "#cat-rate-stars"],
  ["review-bar", "bars", "#cat-rate-bars"],
  ["review-bar", "score", "#cat-rate-score"],
  ["blogcard", "image-top", "#cat-blogcard-top"],
  ["blogcard", "text-band", "#cat-blogcard-band"],
  ["blogcard", "external-ogp", "#cat-blogcard-ogp"],
  ["pr-notice", "intro-label", "#cat-pr-intro"],
  ["pr-notice", "inline-label", "#cat-pr-inline"],
  ["pr-notice", "double-position", "#cat-pr-double"],
  ["pr-notice", "icon-band", "#cat-pr-band"],
  ["detext", "takeaways", "#cat-detext-takeaways"],
  ["detext", "metrics", "#cat-detext-metrics"],
  ["detext", "diagram", "#cat-detext-diagram"],
  ["detext", "large-quote", "#cat-detext-quote"],
];
const contrastParts = ["white-fade", "overlay-warm", "overlay-cool", "overlay-brand", "bottom-gradient", "blur-bright", "duotone"];
const relatedParts = ["grid", "list", "rank", "carousel", "featured", "ranking-numbers"];

const browser = await chromium.launch();
const index = [];
try {
  for (const [dev, config] of [["sp", SP], ["pc", PC]]) {
    const context = await browser.newContext(config);
    const catalogPage = await context.newPage();
    await catalogPage.goto(BASE + CATALOG, { waitUntil: "networkidle" });
    await catalogPage.waitForTimeout(500);
    for (const [part, variant, selector] of catalogParts) {
      index.push(await save(catalogPage, `reaction6-${part}-${variant}-${dev}`, { face: "article", part, variant, dev }, selector));
    }
    for (const variant of contrastParts) {
      const selector = `#cat-contrast-${variant}-mid`;
      index.push(await save(catalogPage, `reaction7-contrast-${variant}-${dev}`, { face: "article", part: "contrast-guard", variant: `${variant} / mid`, dev }, selector));
    }
    await catalogPage.close();
    for (const variant of relatedParts) {
      const page = await context.newPage();
      await page.goto(BASE + ARTICLE + `?wt=related:${variant}`, { waitUntil: "networkidle" });
      await page.waitForTimeout(400);
      index.push(await save(page, `reaction7-related-${variant}-${dev}`, { face: "article", part: "related", variant, dev }, ".wt-tail__slot--related"));
      await page.close();
    }
    await context.close();
  }
} finally {
  await browser.close();
}

const catalogFile = path.resolve(OUT, "..", "CATALOG-INDEX.json");
const existing = fs.existsSync(catalogFile) ? JSON.parse(fs.readFileSync(catalogFile, "utf8")) : [];
const existingFiles = new Set(existing.map((entry) => entry.file));
const additions = index.filter((entry) => !existingFiles.has(entry.file));
fs.writeFileSync(catalogFile, JSON.stringify(existing.concat(additions), null, 1) + "\n");
console.log(JSON.stringify({ captured: index.length, appended: additions.length, catalog: catalogFile }, null, 1));

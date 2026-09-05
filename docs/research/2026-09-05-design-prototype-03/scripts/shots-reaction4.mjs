#!/usr/bin/env node
// shots-reaction4.mjs — 2026-09-05 PO 反応 8 回目（WT-EVT-0249）の証跡を追加撮影する。
// density は同じ本文中盤を3値で比較し、detext は toc と本文を切り分け、
// motion は同じ関連カードを別ページで3フレーム撮影する。
// 既存画像・既存 CATALOG-INDEX.json エントリは変更せず、新規ファイルだけを末尾へ追記する。
import fs from "node:fs";
import path from "node:path";
import { execFileSync } from "node:child_process";
import { createRequire } from "node:module";

const require = createRequire(process.env.NODE_PATH ? path.join(process.env.NODE_PATH, "x.js") : import.meta.url);
const { chromium } = require("playwright");

const args = Object.fromEntries(
  process.argv.slice(2)
    .map((arg, index, all) => arg.startsWith("--") ? [arg.slice(2), all[index + 1]] : null)
    .filter(Boolean),
);
const BASE = args.base || "http://localhost:8086";
const OUT = path.resolve(args.out || "../results");
const CATALOG_FILE = path.join(OUT, "..", "CATALOG-INDEX.json");
const ARTICLE = "/standing-desk-compare/";
const DENSITIES = ["airy", "normal", "compact"];
const SP = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const PC = { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 };

fs.mkdirSync(OUT, { recursive: true });
const existingCatalog = JSON.parse(fs.readFileSync(CATALOG_FILE, "utf8"));
const existingFiles = new Set(existingCatalog.map((entry) => entry.file));
const addedEntries = [];
const addedFiles = new Set();
const densityMeasurements = [];

function assertNewFile(file) {
  if (existingFiles.has(file)) throw new Error(`既存 CATALOG-INDEX エントリと衝突: ${file}`);
  if (addedFiles.has(file)) throw new Error(`同一実行内でファイル名が重複: ${file}`);
  const absolute = path.join(OUT, file);
  if (fs.existsSync(absolute) || fs.existsSync(absolute.replace(/\.jpg$/, ".png"))) {
    throw new Error(`既存の撮影ファイルを上書きしないため中止: ${absolute}`);
  }
  addedFiles.add(file);
}

function toJpeg(png, jpg) {
  execFileSync("ffmpeg", [
    "-y", "-loglevel", "error", "-i", png,
    "-vf", "scale='if(gte(iw,ih),min(1600,iw),-2)':'if(gte(iw,ih),-2,min(1600,ih))'",
    "-q:v", "5", jpg,
  ]);
  fs.unlinkSync(png);
}

async function save(page, name, meta, options = {}) {
  const file = `${name}.jpg`;
  assertNewFile(file);
  const png = path.join(OUT, `${name}.png`);
  const jpg = path.join(OUT, file);
  if (options.selector) {
    const element = page.locator(options.selector).first();
    await element.waitFor({ state: "visible", timeout: 10000 });
    await element.screenshot({ path: png });
  } else if (options.viewportOnly) {
    await page.screenshot({ path: png });
  } else {
    await page.screenshot({ path: png, fullPage: true });
  }
  toJpeg(png, jpg);
  if (!fs.statSync(jpg).size) throw new Error(`空の JPEG が生成されました: ${jpg}`);
  addedEntries.push({ file, ...meta });
  console.log("shot", file);
}

async function open(context, url) {
  const page = await context.newPage();
  page.setDefaultTimeout(10000);
  await page.goto(BASE + url, { waitUntil: "networkidle" });
  await page.waitForTimeout(300);
  await page.evaluate(() => document.fonts?.ready);
  return page;
}

async function scrollToHeading(page, selector = "#h-4") {
  await page.locator(selector).first().waitFor({ state: "visible", timeout: 10000 });
  await page.evaluate(({ selector: targetSelector }) => {
    const target = document.querySelector(targetSelector);
    if (!target) throw new Error(`見出しが見つかりません: ${targetSelector}`);
    const offset = window.matchMedia("(max-width: 599px)").matches ? 128 : 132;
    window.scrollTo(0, Math.max(0, window.scrollY + target.getBoundingClientRect().top - offset));
  }, { selector });
  await page.waitForTimeout(150);
}

async function measureDensity(page, density, dev) {
  const measurement = await page.evaluate(() => {
    const content = document.querySelector(".wp-block-post-content");
    const heading = document.querySelector("#h-4");
    if (!content || !heading) throw new Error("density 計測対象が見つかりません");
    const paragraphsAfterHeading = [...content.querySelectorAll("p")]
      .filter((paragraph) => Boolean(heading.compareDocumentPosition(paragraph) & Node.DOCUMENT_POSITION_FOLLOWING));
    const immediateParagraph = paragraphsAfterHeading[0];
    const children = [...content.children];
    const paragraphPair = children.find((element, index) =>
      element.tagName === "P" && children[index + 1]?.tagName === "P"
    );
    if (!immediateParagraph || !paragraphPair) throw new Error("段落の計測対象が見つかりません");
    const nextParagraph = children[children.indexOf(paragraphPair) + 1];
    const headingStyle = getComputedStyle(heading);
    const paragraphStyle = getComputedStyle(immediateParagraph);
    const firstRect = paragraphPair.getBoundingClientRect();
    const secondRect = nextParagraph.getBoundingClientRect();
    return {
      heading_margin_top_px: parseFloat(headingStyle.marginTop),
      first_paragraph_margin_bottom_px: parseFloat(paragraphStyle.marginBottom),
      consecutive_paragraph_gap_px: Number((secondRect.top - firstRect.bottom).toFixed(2)),
      heading_id: heading.id,
      immediate_paragraph_text: immediateParagraph.textContent.trim().slice(0, 40),
      consecutive_paragraph_text: [paragraphPair.textContent.trim().slice(0, 40), nextParagraph.textContent.trim().slice(0, 40)],
      viewport: { width: window.innerWidth, height: window.innerHeight },
    };
  });
  densityMeasurements.push({ density, dev, ...measurement });
}

async function openTocForEvidence(page) {
  // SP の通常の box は JS で閉じるため、番号バッジ差分を同じ selector で読める状態にする。
  await page.locator(".wt-toc details").evaluate((details) => { details.open = true; });
}

async function prepareRevealEntry(page) {
  await page.locator(".wt-rcard").first().waitFor({ state: "attached", timeout: 10000 });
  await page.evaluate(() => {
    const card = document.querySelector(".wt-rcard");
    if (!card) throw new Error("motion 対象の .wt-rcard が見つかりません");
    // まず対象を viewport の下へ置き、次の scroll で IO の発火境界を横切らせる。
    const below = window.innerHeight + 48;
    window.scrollTo(0, Math.max(0, window.scrollY + card.getBoundingClientRect().top - below));
  });
  await page.waitForTimeout(80);
  await page.evaluate(() => {
    const card = document.querySelector(".wt-rcard");
    if (!card) throw new Error("motion 対象の .wt-rcard が見つかりません");
    const triggerTop = window.innerHeight - Math.round(window.innerHeight * 0.1) + 2;
    window.scrollTo(0, Math.max(0, window.scrollY + card.getBoundingClientRect().top - triggerTop));
  });
}

async function captureMotionFrame(context, frame, waitMs) {
  const page = await open(context, `${ARTICLE}?wt=motion:on`);
  await prepareRevealEntry(page);
  await page.waitForTimeout(waitMs);
  await save(page, `axis-motion-on-f${frame}-sp`, {
    face: "article",
    part: "axis-motion",
    variant: `motion:on frame ${frame} (t=${waitMs}ms)`,
    dev: "sp",
  }, { viewportOnly: true });
  await page.close();
}

const browser = await chromium.launch();
try {
  for (const [dev, device] of [["sp", SP], ["pc", PC]]) {
    const context = await browser.newContext(device);
    try {
      for (const density of DENSITIES) {
        const page = await open(context, `${ARTICLE}?wt=density:${density}`);
        await measureDensity(page, density, dev);
        await scrollToHeading(page);
        await save(page, `axis-density-${density}-${dev}`, {
          face: "article",
          part: "axis-density",
          variant: `density:${density}`,
          dev,
        }, { viewportOnly: true });
        await page.close();
      }

      for (const state of ["off", "on"]) {
        const page = await open(context, `${ARTICLE}?wt=toc:box,detext:${state}`);
        await openTocForEvidence(page);
        await save(page, `axis-detext-${state}-toc-${dev}`, {
          face: "article",
          part: "axis-detext",
          variant: `detext:${state}`,
          dev,
        }, { selector: ".wt-toc" });
        await page.close();
      }

      for (const state of ["off", "on"]) {
        const page = await open(context, `${ARTICLE}?wt=detext:${state}`);
        await scrollToHeading(page);
        await save(page, `axis-detext-${state}-body-${dev}`, {
          face: "article",
          part: "axis-detext",
          variant: `detext:${state} (body, no visible diff)`,
          dev,
        }, { viewportOnly: true });
        await page.close();
      }
    } finally {
      await context.close();
    }
  }

  const motionContext = await browser.newContext(SP);
  try {
    await captureMotionFrame(motionContext, 0, 0);
    await captureMotionFrame(motionContext, 1, 200);
    await captureMotionFrame(motionContext, 2, 900);

    const offPage = await open(motionContext, `${ARTICLE}?wt=motion:off`);
    await prepareRevealEntry(offPage);
    await offPage.waitForTimeout(900);
    await save(offPage, "axis-motion-off-f2-sp", {
      face: "article",
      part: "axis-motion",
      variant: "motion:off (no animation)",
      dev: "sp",
    }, { viewportOnly: true });
    await offPage.close();
  } finally {
    await motionContext.close();
  }
} finally {
  await browser.close();
}

if (addedEntries.length !== 18) {
  throw new Error(`追加画像数が想定外です: ${addedEntries.length}（期待値 18）`);
}

const counts = {
  density: addedEntries.filter((entry) => entry.part === "axis-density").length,
  detext_toc: addedEntries.filter((entry) =>
    entry.part === "axis-detext" && ["detext:off", "detext:on"].includes(entry.variant)
  ).length,
  detext_body: addedEntries.filter((entry) => entry.part === "axis-detext" && entry.variant.includes("body, no visible diff")).length,
  motion: addedEntries.filter((entry) => entry.part === "axis-motion").length,
};
if (JSON.stringify(counts) !== JSON.stringify({ density: 6, detext_toc: 4, detext_body: 4, motion: 4 })) {
  throw new Error(`追加画像の内訳が想定外です: ${JSON.stringify(counts)}`);
}

fs.writeFileSync(
  path.join(OUT, "density-measure.json"),
  JSON.stringify({ article: ARTICLE, measurements: densityMeasurements }, null, 2) + "\n",
);

const duplicateCatalogFile = addedEntries.find((entry) => existingFiles.has(entry.file));
if (duplicateCatalogFile) throw new Error(`既存エントリを変更しようとしています: ${duplicateCatalogFile.file}`);
fs.writeFileSync(CATALOG_FILE, JSON.stringify(existingCatalog.concat(addedEntries), null, 1) + "\n");

console.log("density measurements", JSON.stringify(densityMeasurements, null, 2));
console.log("reaction4 done", JSON.stringify(counts), `total=${addedEntries.length}`);

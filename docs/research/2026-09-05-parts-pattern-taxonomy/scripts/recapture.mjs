#!/usr/bin/env node
// recapture.mjs — footer / article tail / category-archive page re-capture (scratchpad only).
// usage: node recapture.mjs [--offset N] [--limit N] [--only id1,id2] [--concurrency 3] [--url-timeout 45000]
import fs from "node:fs";
import path from "node:path";
import { createRequire } from "node:module";
const require = createRequire(import.meta.url);
const HERE = path.dirname(new URL(import.meta.url).pathname);
const SURVEY = path.resolve(HERE, "../survey");
const OUT = HERE;
const SHOTS = path.join(OUT, "shots");
fs.mkdirSync(SHOTS, { recursive: true });

function parseArgs(argv) { const a = {}; for (let i = 0; i < argv.length; i++) { const k = argv[i]; if (k.startsWith("--")) { const n = k.slice(2), v = argv[i + 1]; if (v === undefined || v.startsWith("--")) a[n] = true; else { a[n] = v; i++; } } } return a; }
const args = parseArgs(process.argv.slice(2));
const CONCURRENCY = Number(args.concurrency || 3);
const PER_URL_MS = Number(args["url-timeout"] || 45000);
const NAV_MS = Number(args["nav-timeout"] || 20000);
const SCROLL_CAP_MS = 60000;
const pw = require(process.env.PLAYWRIGHT_MODULE || "playwright") // PLAYWRIGHT_MODULE で playwright の絶対パスを指定;

// ---------- sites (dedupe by url) + ids from existing results ----------
const idMap = new Map();
for (const dir of ["full", "extra", "new", "new-rules", "new-mincho", "new-dark"]) {
  const d = path.join(SURVEY, dir, "results"); if (!fs.existsSync(d)) continue;
  for (const f of fs.readdirSync(d)) { try { const j = JSON.parse(fs.readFileSync(path.join(d, f), "utf8")); const k = j.url.replace(/\/+$/, "").toLowerCase(); if (!idMap.has(k)) idMap.set(k, j.id); } catch {} }
}
function genId(s, counters) { const key = `${s.pattern}-${s.country}`; const n = counters.get(key) || 0; counters.set(key, n + 1); return `${key}-${String(n).padStart(3, "0")}`; }
let sites = []; const seen = new Set(); const counters = new Map();
for (const fn of ["sites.json", "sites.extra.json", "sites.new.json", "sites.new-rules.json", "sites.new-mincho.json", "sites.new-dark.json"]) {
  const p = path.join(SURVEY, fn); if (!fs.existsSync(p)) continue;
  for (const s of JSON.parse(fs.readFileSync(p, "utf8"))) {
    const k = s.url.replace(/\/+$/, "").toLowerCase(); if (seen.has(k)) continue; seen.add(k);
    sites.push({ ...s, id: s.id || idMap.get(k) || genId(s, counters) });
  }
}
if (args.only) { const set = new Set(String(args.only).split(",")); sites = sites.filter((s) => set.has(s.id)); }
if (args.offset) sites = sites.slice(Number(args.offset));
if (args.limit) sites = sites.slice(0, Number(args.limit));
// resume: skip sites listed in done.txt
const DONE = path.join(OUT, "done.txt");
const done = new Set(fs.existsSync(DONE) ? fs.readFileSync(DONE, "utf8").split("\n").filter(Boolean) : []);
if (!args["no-resume"]) sites = sites.filter((s) => !done.has(s.id));
console.error(`sites to process: ${sites.length}`);

const INDEX = path.join(OUT, "index.jsonl"), FAIL = path.join(OUT, "failures.txt");
const rec = (o) => { if (o.reason) o.reason = sanitize(o.reason); fs.appendFileSync(INDEX, JSON.stringify(o) + "\n"); };
const sanitize = (r) => String(r).replace(/https?:\/\/\S+/g, "<url>");
const fail = (id, page, dev, reason) => { reason = sanitize(reason); fs.appendFileSync(FAIL, `${id}\t${page}\t${dev}\t${reason}\n`); };

const DEVICES = {
  sp: { width: 390, height: 844, dpr: 2, mobile: true, ua: process.env.RECAPTURE_UA_SP || "Mozilla/5.0 (Mobile; rv:generic) Gecko" },
  pc: { width: 1440, height: 900, dpr: 1, mobile: false, ua: undefined },
};
const BOT_RE = /just a moment|access denied|attention required|captcha|verify you are|403 forbidden|429|bot detection|ロボットではありません|アクセスが拒否/i;
const CAT_HREF_RE = /\/(category|categories|archives?|tag|blog|news|column|works|case|topics)(\/|$|\?)/i;
const CAT_TEXT_RE = /カテゴリ|一覧|記事|ブログ|コラム|ニュース|事例|お知らせ/;

const withTimeout = (p, ms, label) => Promise.race([p, new Promise((_, rej) => setTimeout(() => rej(new Error(`timeout:${label}`)), ms))]);

async function scrollToBottom(page) {
  const t0 = Date.now();
  await page.evaluate(async () => {
    const t0 = Date.now();
    const H = () => Math.max(document.body?.scrollHeight || 0, document.documentElement.scrollHeight);
    let y = 0;
    while (y < H() && Date.now() - t0 < 58000) { y += 800; window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 250)); }
    window.scrollTo(0, H());
  }).catch(() => {});
  await page.waitForTimeout(1500);
  return Date.now() - t0;
}
async function docHeight(page) { return page.evaluate(() => Math.max(document.body?.scrollHeight || 0, document.documentElement.scrollHeight)); }

async function shot(page, file, y, h, vw) {
  const docH = await docHeight(page);
  let start = Math.max(0, y), height = Math.min(h, docH - start);
  if (height <= 0) return null;
  await page.screenshot({ path: file, type: "jpeg", quality: 70, fullPage: true, scale: "css", timeout: 30000, clip: { x: 0, y: start, width: vw, height } });
  return { start, height, docH };
}
function regions(docH, kind) {
  const foot = { y: Math.max(0, docH - 1400), h: 1400 };
  if (kind === "top") return { foot };
  if (kind === "article") return { tail: { y: Math.max(0, docH - 2400), h: Math.max(0, Math.min(1400, docH - 1000 - Math.max(0, docH - 2400))) }, foot };
  return { hero: { y: 0, h: 1000 }, mid: { y: Math.max(0, Math.round(docH / 2 - 500)), h: 1000 }, foot };
}

async function gotoChecked(page, url) {
  const res = await page.goto(url, { waitUntil: "domcontentloaded", timeout: NAV_MS });
  await page.waitForLoadState("load", { timeout: 10000 }).catch(() => {});
  await page.waitForTimeout(800);
  const status = res ? res.status() : 0;
  const title = await page.title().catch(() => "");
  if (status >= 400) throw new Error(`http:${status}`);
  if (BOT_RE.test(title)) throw new Error(`botwall:${title.slice(0, 40)}`);
  return status;
}

async function findCatLink(page, baseUrl) {
  return page.evaluate(({ hrefRe, textRe, baseUrl }) => {
    const HR = new RegExp(hrefRe, "i"), TR = new RegExp(textRe);
    const host = location.hostname.replace(/^www\./, "");
    const cur = location.href.replace(/[#?].*$/, "").replace(/\/+$/, "");
    const as = Array.from(document.querySelectorAll("a[href]"));
    const cands = [];
    for (const a of as) {
      let u; try { u = new URL(a.getAttribute("href"), location.href); } catch { continue; }
      if (!/^https?:$/.test(u.protocol)) continue;
      if (u.hostname.replace(/^www\./, "") !== host) continue;
      const p = u.pathname; const clean = u.href.replace(/#.*$/, "").replace(/\/+$/, "");
      if (clean === cur || p === "/" || p === "") continue;
      if (/\.(pdf|jpe?g|png|zip)$/i.test(p)) continue;
      const txt = (a.innerText || a.textContent || "").trim();
      const inNav = !!a.closest("nav, header, [class*='nav'], [class*='menu'], [id*='nav'], [id*='menu']");
      if (HR.test(p)) cands.push({ href: u.href, path: p + u.search, score: 2 + (inNav ? 1 : 0) });
      else if (inNav && TR.test(txt) && txt.length < 30) cands.push({ href: u.href, path: p + u.search, score: 1 });
    }
    if (!cands.length) return null;
    cands.sort((a, b) => b.score - a.score);
    return cands[0];
  }, { hrefRe: CAT_HREF_RE.source, textRe: CAT_TEXT_RE.source, baseUrl });
}

async function capturePage(ctx, site, dev, pageKind, url, catInfo) {
  const page = await ctx.newPage();
  const vw = DEVICES[dev].width;
  try {
    await withTimeout((async () => {
      await gotoChecked(page, url);
      await scrollToBottom(page);
      const docH = await docHeight(page);
      const regs = regions(docH, pageKind);
      for (const [region, r] of Object.entries(regs)) {
        const file = `${site.id}-${pageKind === "article" ? "article" : pageKind === "cat" ? "cat" : "top"}-${dev}--${region}.jpg`;
        if (r.h <= 0) { rec({ id: site.id, page: pageKind, dev, region, file, ok: false, reason: "doc_too_short", docH, ...(catInfo ? { cat_path: catInfo.path } : {}) }); continue; }
        const info = await shot(page, path.join(SHOTS, file), r.y, r.h, vw);
        rec({ id: site.id, page: pageKind, dev, region, file, ok: !!info, docH, ...(catInfo ? { cat_path: catInfo.path } : {}) });
      }
    })(), PER_URL_MS, `${pageKind}-${dev}`);
    return true;
  } catch (e) {
    const reason = String(e.message || e).split("\n")[0].slice(0, 120);
    fail(site.id, pageKind, dev, reason);
    for (const region of pageKind === "top" ? ["foot"] : pageKind === "article" ? ["tail", "foot"] : ["hero", "mid", "foot"])
      rec({ id: site.id, page: pageKind, dev, region, file: `${site.id}-${pageKind}-${dev}--${region}.jpg`, ok: false, reason, ...(catInfo ? { cat_path: catInfo.path } : {}) });
    return false;
  } finally { await page.close().catch(() => {}); }
}

async function processSite(browser, site) {
  const t0 = Date.now();
  for (const dev of ["sp", "pc"]) {
    const d = DEVICES[dev];
    const ctx = await browser.newContext({ viewport: { width: d.width, height: d.height }, deviceScaleFactor: d.dpr, isMobile: d.mobile, hasTouch: d.mobile, userAgent: d.ua, locale: "ja-JP", ignoreHTTPSErrors: true });
    try {
      // top: also discover a category link
      let cat = null;
      {
        const page = await ctx.newPage();
        try {
          await withTimeout((async () => {
            await gotoChecked(page, site.url);
            cat = await findCatLink(page, site.url).catch(() => null);
            await scrollToBottom(page);
            const docH = await docHeight(page);
            const r = regions(docH, "top").foot;
            const file = `${site.id}-top-${dev}--foot.jpg`;
            const info = await shot(page, path.join(SHOTS, file), r.y, r.h, d.width);
            rec({ id: site.id, page: "top", dev, region: "foot", file, ok: !!info, docH });
          })(), PER_URL_MS, `top-${dev}`);
        } catch (e) {
          const reason = String(e.message || e).split("\n")[0].slice(0, 120);
          fail(site.id, "top", dev, reason);
          rec({ id: site.id, page: "top", dev, region: "foot", file: `${site.id}-top-${dev}--foot.jpg`, ok: false, reason });
        } finally { await page.close().catch(() => {}); }
      }
      if (site.article_url) await capturePage(ctx, site, dev, "article", site.article_url, null);
      if (cat) await capturePage(ctx, site, dev, "cat", cat.href, cat);
      else fail(site.id, "cat", dev, "no_cat_link");
    } finally { await ctx.close().catch(() => {}); }
  }
  fs.appendFileSync(DONE, site.id + "\n");
  console.error(`${site.id} done ${Date.now() - t0}ms`);
}

let browser = await pw.chromium.launch({ headless: true });
let relaunching = null;
async function getBrowser() {
  if (browser.isConnected()) return browser;
  if (!relaunching) relaunching = (async () => { console.error("browser crashed; relaunching"); try { await browser.close(); } catch {} browser = await pw.chromium.launch({ headless: true }); relaunching = null; })();
  await relaunching; return browser;
}
let i = 0;
async function worker() {
  while (i < sites.length) {
    const s = sites[i++];
    let ok = false;
    for (let attempt = 0; attempt < 2 && !ok; attempt++) {
      try { await processSite(await getBrowser(), s); ok = true; }
      catch (e) {
        const msg = String(e.message).split("\n")[0].slice(0, 120);
        if (/has been closed|Not supported/.test(msg) && attempt === 0) { await new Promise((r) => setTimeout(r, 2000)); continue; }
        fail(s.id, "site", "-", msg); fs.appendFileSync(DONE, s.id + "\n"); ok = true;
      }
    }
  }
}
await Promise.all(Array.from({ length: CONCURRENCY }, worker));
await browser.close();
console.error("finished");

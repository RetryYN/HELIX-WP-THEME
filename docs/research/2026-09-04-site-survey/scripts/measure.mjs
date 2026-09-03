#!/usr/bin/env node
// measure.mjs — Playwright で実在 Web ページの外観・部品・動き・性能の目安を計測する。
//
// 使い方:
//   node measure.mjs --sites sites.json --out ./survey [--concurrency 3] [--only id1,id2] [--limit N]
//   node measure.mjs --url https://example.com --out ./survey      (単発試走)
//
// 環境変数 (引数が優先):
//   SURVEY_SITES        sites.json のパス
//   SURVEY_OUT          出力ディレクトリ (results/, shots/ を作る)
//   SURVEY_CONCURRENCY  並列数 (既定 3)
//   SURVEY_UA           User-Agent 上書き
//   PLAYWRIGHT_MODULE   playwright の解決パス (既定: 通常解決。別ディレクトリの node_modules を使う場合に指定)
//
// sites.json 形式: [{ id?, pattern, url, country, genre, notes }]  (id が無ければ URL から生成)
// 出力: results/<id>.json, shots/<id>-sp.webp, shots/<id>-pc.webp

import fs from "node:fs";
import path from "node:path";
import { createRequire } from "node:module";

const require = createRequire(import.meta.url);

// ---------- 引数 ----------
function parseArgs(argv) {
  const a = {};
  for (let i = 0; i < argv.length; i++) {
    const k = argv[i];
    if (k.startsWith("--")) {
      const name = k.slice(2);
      const next = argv[i + 1];
      if (next === undefined || next.startsWith("--")) a[name] = true;
      else { a[name] = next; i++; }
    }
  }
  return a;
}
const args = parseArgs(process.argv.slice(2));
const SITES = args.sites || process.env.SURVEY_SITES;
const OUT = path.resolve(args.out || process.env.SURVEY_OUT || "./survey");
const CONCURRENCY = Number(args.concurrency || process.env.SURVEY_CONCURRENCY || 3);
const PER_URL_MS = Number(args["url-timeout"] || 40000);
const NAV_MS = Number(args["nav-timeout"] || 15000);
const UA = args.ua || process.env.SURVEY_UA || null;
const DEBUG = !!(args.debug || process.env.SURVEY_DEBUG);
const dbg = (...a) => { if (DEBUG) console.error("[dbg]", ...a); };

async function loadPlaywright() {
  const mod = args.playwright || process.env.PLAYWRIGHT_MODULE;
  if (mod) {
    const p = path.isAbsolute(mod) ? mod : path.resolve(mod);
    return require(p);
  }
  try { return require("playwright"); } catch {}
  try { return createRequire(path.join(process.cwd(), "package.json"))("playwright"); } catch {
    throw new Error("playwright を解決できません。--playwright <path> か PLAYWRIGHT_MODULE で node_modules/playwright を指定してください。");
  }
}

function idFromUrl(u) {
  try {
    const x = new URL(u);
    const s = (x.hostname + x.pathname).replace(/^www\./, "").replace(/[^a-z0-9]+/gi, "-").replace(/^-|-$/g, "");
    return s.slice(0, 60).toLowerCase();
  } catch { return "invalid-" + Math.random().toString(36).slice(2, 8); }
}

let sites = [];
if (args.url) {
  sites = [{ id: args.id || idFromUrl(args.url), pattern: args.pattern || "adhoc", url: args.url, country: "", genre: "", notes: "" }];
} else if (SITES) {
  sites = JSON.parse(fs.readFileSync(SITES, "utf8"));
  sites = sites.map((s) => ({ ...s, id: s.id || idFromUrl(s.url) }));
} else {
  console.error("--sites <sites.json> か --url <URL> を指定してください");
  process.exit(2);
}
if (args.only) { const set = new Set(String(args.only).split(",")); sites = sites.filter((s) => set.has(s.id)); }
if (args.limit) sites = sites.slice(0, Number(args.limit));
// id の重複解消
{ const seen = new Map(); for (const s of sites) { const n = (seen.get(s.id) || 0) + 1; seen.set(s.id, n); if (n > 1) s.id = `${s.id}-${n}`; } }

fs.mkdirSync(path.join(OUT, "results"), { recursive: true });
fs.mkdirSync(path.join(OUT, "shots"), { recursive: true });

// ---------- ページ内注入: 動きの検出フック (ナビゲーション前) ----------
const INIT_SCRIPT = `
(() => {
  const s = { raf: 0, io: 0, scrollListeners: 0 };
  window.__survey = s;
  const raf = window.requestAnimationFrame;
  window.requestAnimationFrame = function (cb) { s.raf++; return raf.call(window, cb); };
  const IO = window.IntersectionObserver;
  if (IO) {
    window.IntersectionObserver = function (...a) { s.io++; return new IO(...a); };
    window.IntersectionObserver.prototype = IO.prototype;
  }
  const add = EventTarget.prototype.addEventListener;
  EventTarget.prototype.addEventListener = function (type, ...rest) {
    if (type === "scroll" || type === "wheel" || type === "touchmove") s.scrollListeners++;
    return add.call(this, type, ...rest);
  };
  try {
    window.__lcp = 0;
    new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__lcp = e.startTime; })
      .observe({ type: "largest-contentful-paint", buffered: true });
  } catch {}
})();
`;

// ---------- ページ内計測 (本体) ----------
const PAGE_EVAL = ({ vw, vh, isSp }) => {
  const num = (v) => { const n = parseFloat(v); return Number.isFinite(n) ? Math.round(n * 100) / 100 : null; };
  const cs = (el) => getComputedStyle(el);
  const mode = (arr) => {
    const m = new Map(); let best = null, bc = 0;
    for (const v of arr) { if (v == null) continue; const k = typeof v === "object" ? JSON.stringify(v) : v; const c = (m.get(k) || 0) + 1; m.set(k, c); if (c > bc) { bc = c; best = v; } }
    return { value: best, count: bc, total: arr.length };
  };
  const visible = (el) => {
    const r = el.getBoundingClientRect();
    if (r.width === 0 || r.height === 0) return false;
    const s = cs(el);
    return s.display !== "none" && s.visibility !== "hidden" && s.opacity !== "0";
  };
  const text = (el) => (el.innerText || "").trim();
  const isTransparent = (c) => !c || c === "transparent" || /rgba\(\s*\d+,\s*\d+,\s*\d+,\s*0\)/.test(c);
  const firstColor = (s) => { const m = /rgba?\([^)]*\)|#[0-9a-f]{3,8}/i.exec(s || ""); return m ? m[0] : null; };

  const html = document.documentElement, body = document.body;
  const hs = cs(html), bs = cs(body);
  const docH = Math.max(body.scrollHeight, html.scrollHeight);

  // --- メタ ---
  const gen = document.querySelector('meta[name="generator"]');
  const meta = {
    finalUrl: location.href,
    title: document.title,
    lang: html.getAttribute("lang"),
    generator: gen ? gen.getAttribute("content") : null,
    isWordPress: !!(gen && /wordpress/i.test(gen.content)) || !!document.querySelector('link[href*="/wp-content/"], script[src*="/wp-content/"], link[href*="/wp-includes/"]'),
    charset: document.characterSet,
    docHeight: docH,
  };

  // --- 実際に使われたフォント ---
  const loadedFonts = [];
  try { document.fonts.forEach((f) => { if (f.status === "loaded") loadedFonts.push(f.family.replace(/^"|"$/g, "")); }); } catch {}

  // --- 本文 p ---
  const main = document.querySelector("main, article, [role=main], #main, #content, .content, .entry-content") || body;
  const allP = Array.from(document.querySelectorAll("p")).filter((p) => visible(p) && text(p).length >= 20);
  const ps = (allP.filter((p) => main.contains(p)).length >= 3 ? allP.filter((p) => main.contains(p)) : allP);
  const pStyle = (p) => { const s = cs(p); return { fontSize: num(s.fontSize), lineHeight: num(s.lineHeight), color: s.color, fontFamily: s.fontFamily, letterSpacing: s.letterSpacing, marginTop: num(s.marginTop), marginBottom: num(s.marginBottom), fontWeight: s.fontWeight }; };
  const pStyles = ps.map(pStyle);
  const pMode = mode(pStyles.map((s) => s.fontSize)).value;
  const pMain = pStyles.filter((s) => s.fontSize === pMode);
  const bodyText = pMain.length ? {
    fontSize: pMode,
    lineHeight: mode(pMain.map((s) => s.lineHeight)).value,
    lineHeightRatio: (() => { const lh = mode(pMain.map((s) => s.lineHeight)).value; return lh && pMode ? Math.round((lh / pMode) * 100) / 100 : null; })(),
    color: mode(pMain.map((s) => s.color)).value,
    fontFamily: mode(pMain.map((s) => s.fontFamily)).value,
    letterSpacing: mode(pMain.map((s) => s.letterSpacing)).value,
    marginTop: mode(pMain.map((s) => s.marginTop)).value,
    marginBottom: mode(pMain.map((s) => s.marginBottom)).value,
    fontWeight: mode(pMain.map((s) => s.fontWeight)).value,
    sampleCount: pMain.length,
  } : null;
  // 本文コンテナ
  let container = null;
  if (pMain.length) {
    const parents = ps.filter((p) => num(cs(p).fontSize) === pMode).map((p) => p.parentElement).filter(Boolean);
    const pm = new Map(); let par = null, pc = 0; for (const e of parents) { const c = (pm.get(e) || 0) + 1; pm.set(e, c); if (c > pc) { pc = c; par = e; } }
    if (par) { const s = cs(par); container = { clientWidth: par.clientWidth, innerWidth: par.clientWidth - num(s.paddingLeft) - num(s.paddingRight), paddingLeft: num(s.paddingLeft), paddingRight: num(s.paddingRight), tag: par.tagName.toLowerCase(), className: String(par.className || "").slice(0, 80) }; }
  }

  // --- 見出し ---
  const headStyle = (el) => { if (!el) return null; const s = cs(el); return { fontSize: num(s.fontSize), lineHeight: num(s.lineHeight), fontFamily: s.fontFamily, fontWeight: s.fontWeight, color: s.color, letterSpacing: s.letterSpacing, marginTop: num(s.marginTop), marginBottom: num(s.marginBottom), text: text(el).slice(0, 80) }; };
  const h1 = Array.from(document.querySelectorAll("h1")).find(visible);
  const h2s = Array.from(document.querySelectorAll("h2")).filter(visible);
  const h2Mode = h2s.length ? mode(h2s.map((h) => num(cs(h).fontSize))).value : null;
  const h2 = h2s.find((h) => num(cs(h).fontSize) === h2Mode) || null;
  const h3s = Array.from(document.querySelectorAll("h3")).filter(visible);
  const h3Mode = h3s.length ? mode(h3s.map((h) => num(cs(h).fontSize))).value : null;
  const h3 = h3s.find((h) => num(cs(h).fontSize) === h3Mode) || null;
  const headings = { h1: headStyle(h1), h2: headStyle(h2), h3: headStyle(h3), h1Count: document.querySelectorAll("h1").length, h2Count: h2s.length, h3Count: h3s.length };
  const hm = [...h2s, ...h3s].map((h) => { const s = cs(h); return { top: num(s.marginTop), bottom: num(s.marginBottom) }; });
  const nzm = (a) => { const f = a.filter((v) => v > 0); return f.length ? f : a; };
  const headingMargin = { top: mode(nzm(hm.map((x) => x.top))).value, bottom: mode(nzm(hm.map((x) => x.bottom))).value };

  const typography = {
    html: { fontSize: num(hs.fontSize), lineHeight: hs.lineHeight, fontFamily: hs.fontFamily },
    body: { fontSize: num(bs.fontSize), lineHeight: bs.lineHeight, fontFamily: bs.fontFamily, letterSpacing: bs.letterSpacing, color: bs.color },
    loadedFonts: Array.from(new Set(loadedFonts)).slice(0, 12),
    bodyText, container, headings, headingMargin,
  };

  // --- 色 ---
  const sections = Array.from(document.querySelectorAll("section, main > *, body > *, article > *")).filter((e) => visible(e) && e.getBoundingClientRect().height > 120);
  const sectionBgs = sections.map((e) => cs(e).backgroundColor).filter((c) => !isTransparent(c));
  const links = Array.from(document.querySelectorAll("a[href]")).filter((a) => visible(a) && text(a).length > 0);
  const inTextLinks = links.filter((a) => a.closest("p, li"));
  const linkColor = mode((inTextLinks.length ? inTextLinks : links).map((a) => cs(a).color)).value;

  // ボタン
  const btnCands = Array.from(document.querySelectorAll("a, button, input[type=submit], [role=button]")).filter((e) => {
    if (!visible(e)) return false; const s = cs(e);
    const hasPad = num(s.paddingLeft) >= 8 && num(s.paddingTop) >= 4;
    const hasBg = !isTransparent(s.backgroundColor) || num(s.borderTopWidth) >= 1;
    return hasPad && hasBg && e.getBoundingClientRect().height <= 120;
  });
  const btnInfo = btnCands.map((e) => { const s = cs(e); const r = e.getBoundingClientRect(); return { bg: s.backgroundColor, color: s.color, height: Math.round(r.height), paddingY: num(s.paddingTop), paddingX: num(s.paddingLeft), radius: num(s.borderTopLeftRadius), fontSize: num(s.fontSize), fontWeight: s.fontWeight, borderWidth: num(s.borderTopWidth), shadow: s.boxShadow !== "none", top: r.top + scrollY }; });
  const solidBtn = btnInfo.filter((b) => !isTransparent(b.bg));
  const primaryBg = mode(solidBtn.map((b) => b.bg)).value;
  const primary = solidBtn.filter((b) => b.bg === primaryBg);
  const buttons = {
    count: btnInfo.length,
    primaryBg, primaryColor: mode(primary.map((b) => b.color)).value,
    height: mode(btnInfo.map((b) => b.height)).value,
    paddingY: mode(btnInfo.map((b) => b.paddingY)).value,
    paddingX: mode(btnInfo.map((b) => b.paddingX)).value,
    radius: mode(btnInfo.map((b) => b.radius)).value,
    radiusValues: btnInfo.map((b) => b.radius),
    fontSize: mode(btnInfo.map((b) => b.fontSize)).value,
    fontWeight: mode(btnInfo.map((b) => b.fontWeight)).value,
    shadowRate: btnInfo.length ? Math.round((btnInfo.filter((b) => b.shadow).length / btnInfo.length) * 100) / 100 : null,
    ctaInFirstView: btnInfo.filter((b) => b.top < vh).length,
  };
  const colors = { bodyBg: bs.backgroundColor, htmlBg: hs.backgroundColor, sectionBg: mode(sectionBgs).value, sectionBgs: Array.from(new Set(sectionBgs)).slice(0, 8), text: bodyText ? bodyText.color : bs.color, link: linkColor, buttonBg: primaryBg, buttonText: buttons.primaryColor };

  // --- 部品 ---
  const fixedEls = Array.from(document.querySelectorAll("body *")).filter((e) => { const p = cs(e).position; return (p === "fixed" || p === "sticky") && visible(e); });
  const headerEl = document.querySelector("header, [role=banner], .header, #header");
  let header = null;
  const topFixed = fixedEls.filter((e) => e.getBoundingClientRect().top <= 2 && e.getBoundingClientRect().width >= vw * 0.8).sort((a, b) => b.getBoundingClientRect().height - a.getBoundingClientRect().height)[0];
  const topBand = !headerEl && !topFixed ? Array.from(document.querySelectorAll("body *")).find((e) => { if (!visible(e)) return false; const r = e.getBoundingClientRect(); return r.top <= 2 && r.width >= vw * 0.8 && r.height >= 30 && r.height <= 250 && /nav|head|gnav|global/i.test((typeof e.className === "string" ? e.className : "") + " " + e.id); }) : null;
  const hEl = headerEl && visible(headerEl) ? headerEl : (topFixed || topBand);
  if (hEl) {
    const r = hEl.getBoundingClientRect(); const pos = cs(hEl).position;
    const innerSticky = Array.from(hEl.querySelectorAll("*")).some((e) => /fixed|sticky/.test(cs(e).position));
    header = { height: Math.round(r.height), position: pos, sticky: pos === "fixed" || pos === "sticky" || innerSticky || (topFixed ? topFixed.contains(hEl) || hEl.contains(topFixed) : false), bg: cs(hEl).backgroundColor, tag: hEl.tagName.toLowerCase() };
  }
  const bottomFixed = fixedEls.filter((e) => { const r = e.getBoundingClientRect(); return r.bottom >= vh - 2 && r.top > vh * 0.5 && r.width >= vw * 0.5; });
  const hamburger = !!Array.from(document.querySelectorAll("button, a, [role=button], label, div")).find((e) => { if (!visible(e)) return false; const r = e.getBoundingClientRect(); if (r.top > vh || r.width > 80 || r.height > 80) return false; const al = ((e.getAttribute("aria-label") || "") + " " + (e.className && typeof e.className === "string" ? e.className : "") + " " + (e.id || "")).toLowerCase(); return /hamburger|menu-toggle|menu-btn|navbar-toggle|burger|drawer|menu-open|toggle-nav|nav-toggle|menu-trigger|globalnav-menutrigger|menu-icon|sp-menu|js-menu|menu-button|mobile-menu|open-menu|メニュー/.test(al) || (e.tagName === "BUTTON" && /^(menu|メニュー|open|☰)$/i.test(text(e))); });
  // 目次
  const h2Ids = new Set(Array.from(document.querySelectorAll("h2[id], h3[id]")).map((h) => "#" + h.id));
  const tocCands = Array.from(document.querySelectorAll("nav, ol, ul, div, aside")).filter((n) => { const as = Array.from(n.querySelectorAll('a[href^="#"]')); if (as.length < 3) return false; const hit = as.filter((a) => h2Ids.has(a.getAttribute("href"))).length; const cls = ((typeof n.className === "string" ? n.className : "") + " " + n.id).toLowerCase(); return hit >= 3 || (as.length >= 3 && /toc|table-of-contents|mokuji|outline|目次/.test(cls)); });
  const toc = tocCands.length > 0;
  const breadcrumb = !!document.querySelector('[class*="breadcrumb"], [id*="breadcrumb"], [aria-label*="breadcrumb" i], [aria-label*="パンくず"], nav[class*="crumb"], [itemtype*="BreadcrumbList"], [class*="bread"]');
  // ヒーロー
  let hero = null;
  {
    const cands = Array.from(document.querySelectorAll("main > *, body > *:not(header):not(script):not(style):not(nav):not(footer), section, [class*=hero], [class*=mv], [class*=kv], [class*=main-visual], [class*=fv]")).filter((e) => { if (!visible(e)) return false; const r = e.getBoundingClientRect(); return r.top + scrollY < vh * 0.8 && r.height >= vh * 0.2 && r.height <= vh * 1.6 && r.width >= vw * 0.6 && !e.matches("header, footer, nav"); });
    // 上にあるものを優先、同じ位置なら小さい(=内側の)ものを優先
    cands.sort((a, b) => (a.getBoundingClientRect().top - b.getBoundingClientRect().top) || (a.getBoundingClientRect().height - b.getBoundingClientRect().height));
    const e = cands[0];
    if (e) { const r = e.getBoundingClientRect(); hero = { height: Math.round(r.height), top: Math.round(r.top + scrollY), tag: e.tagName.toLowerCase(), className: String(e.className || "").slice(0, 80), hasVideo: !!e.querySelector("video"), hasImg: !!e.querySelector("img, picture, svg, canvas") || cs(e).backgroundImage !== "none", hasH1: !!e.querySelector("h1") };
    }
  }
  // 画像角丸・影・ボーダー・カード
  const imgs = Array.from(document.querySelectorAll("img, picture, video")).filter(visible);
  const imgRadius = imgs.map((i) => num(cs(i).borderTopLeftRadius));
  const allEls = Array.from(document.querySelectorAll("body *")).filter((e) => visible(e));
  const shadowEls = allEls.filter((e) => cs(e).boxShadow !== "none");
  const borderWidths = allEls.map((e) => num(cs(e).borderTopWidth)).filter((w) => w > 0);
  const cards = Array.from(document.querySelectorAll("div, li, article, a")).filter((e) => {
    if (!visible(e)) return false; const r = e.getBoundingClientRect(); if (r.width < 120 || r.height < 80 || r.width > vw * 0.95) return false;
    const s = cs(e); const bg = !isTransparent(s.backgroundColor) && s.backgroundColor !== bs.backgroundColor; const bd = num(s.borderTopWidth) > 0 && s.borderTopStyle !== "none"; const sh = s.boxShadow !== "none";
    return (bg || bd || sh) && num(s.paddingLeft) >= 8 && e.children.length >= 1;
  });
  const cardInfo = cards.map((e) => { const s = cs(e); return { padding: num(s.paddingTop), paddingX: num(s.paddingLeft), radius: num(s.borderTopLeftRadius), shadow: s.boxShadow !== "none", bg: s.backgroundColor }; });
  const components = {
    header, stickyHeader: header ? header.sticky : fixedEls.some((e) => e.getBoundingClientRect().top <= 2),
    fixedElementCount: fixedEls.length, bottomFixed: bottomFixed.length > 0, bottomFixedHeight: bottomFixed[0] ? Math.round(bottomFixed[0].getBoundingClientRect().height) : null,
    hamburger, toc, breadcrumb, hero, buttons,
    imageRadius: mode(imgRadius).value, imageRadiusValues: imgRadius.slice(0, 200), imageCount: imgs.length,
    shadowUsed: shadowEls.length > 0, shadowCount: shadowEls.length,
    borderWidth: mode(borderWidths).value, borderCount: borderWidths.length,
    cards: { count: cardInfo.length, padding: mode(cardInfo.map((c) => c.padding)).value, paddingX: mode(cardInfo.map((c) => c.paddingX)).value, radius: mode(cardInfo.map((c) => c.radius)).value, shadowRate: cardInfo.length ? Math.round((cardInfo.filter((c) => c.shadow).length / cardInfo.length) * 100) / 100 : null },
  };

  // --- 余白 ---
  const blocks = Array.from(document.querySelectorAll("section, main > *, article > *, body > *")).filter((e) => visible(e) && e.getBoundingClientRect().height > 100 && !e.matches("header, footer, nav, script, style"));
  const sp = blocks.map((e) => { const s = cs(e); return { pt: num(s.paddingTop), pb: num(s.paddingBottom), mt: num(s.marginTop), mb: num(s.marginBottom) }; });
  const nz = (a) => a.filter((v) => v > 0);
  const spacing = { sectionCount: blocks.length, paddingTop: mode(nz(sp.map((x) => x.pt))).value, paddingBottom: mode(nz(sp.map((x) => x.pb))).value, marginTop: mode(nz(sp.map((x) => x.mt))).value, marginBottom: mode(nz(sp.map((x) => x.mb))).value, gapMax: mode(nz(sp.map((x) => Math.max(x.pt + x.mt, x.pb + x.mb)))).value, zeroGapRate: sp.length ? Math.round((sp.filter((x) => x.pt + x.mt + x.pb + x.mb === 0).length / sp.length) * 100) / 100 : null, headingMargin, paragraphGap: bodyText ? bodyText.marginBottom : null };
  const _spacingOld = { paddingTop: mode(sp.map((x) => x.pt)).value, paddingBottom: mode(sp.map((x) => x.pb)).value, marginTop: mode(sp.map((x) => x.mt)).value, marginBottom: mode(sp.map((x) => x.mb)).value, gapMax: mode(sp.map((x) => Math.max(x.pt + x.mt, x.pb + x.mb))).value, headingMargin, paragraphGap: bodyText ? bodyText.marginBottom : null };

  // --- 動き ---
  let keyframes = 0, reducedMotion = false, scrollTimeline = false, animCss = 0, transCss = 0, sheetsBlocked = 0;
  const walk = (rules) => { for (const r of rules) { try { if (r.type === CSSRule.KEYFRAMES_RULE) keyframes++; else if (r.type === CSSRule.MEDIA_RULE) { if (/prefers-reduced-motion/.test(r.conditionText || r.media.mediaText)) reducedMotion = true; walk(r.cssRules); } else if (r.type === CSSRule.SUPPORTS_RULE) walk(r.cssRules); else if (r.style) { if (r.style.animationTimeline && r.style.animationTimeline !== "auto") scrollTimeline = true; if (r.style.animationName && r.style.animationName !== "none") animCss++; if (r.style.transitionProperty && r.style.transitionProperty !== "all" && r.style.transitionProperty !== "none") transCss++; else if (r.style.transitionDuration && r.style.transitionDuration !== "0s") transCss++; } } catch {} } };
  for (const sh of document.styleSheets) { try { walk(sh.cssRules); } catch { sheetsBlocked++; } }
  let animEls = 0, transEls = 0;
  for (const e of allEls) { const s = cs(e); if (s.animationName !== "none" && s.animationName) animEls++; if (s.transitionDuration && s.transitionDuration.split(",").some((d) => parseFloat(d) > 0)) transEls++; }
  const videos = Array.from(document.querySelectorAll("video"));
  const motion = { animatedElements: animEls, transitionElements: transEls, keyframes, animationRulesInCss: animCss, transitionRulesInCss: transCss, stylesheetsBlocked: sheetsBlocked, scrollDrivenAnimation: scrollTimeline, reducedMotionQuery: reducedMotion, raf: window.__survey ? window.__survey.raf : null, intersectionObserver: window.__survey ? window.__survey.io : null, scrollListeners: window.__survey ? window.__survey.scrollListeners : null, canvas: document.querySelectorAll("canvas").length, webgl: Array.from(document.querySelectorAll("canvas")).some((c) => { try { return !!(c.getContext("webgl2") || c.getContext("webgl")); } catch { return false; } }), videoCount: videos.length, videoAutoplay: videos.some((v) => v.autoplay || v.hasAttribute("autoplay")), iframeVideo: document.querySelectorAll('iframe[src*="youtube"], iframe[src*="vimeo"]').length, lottie: !!document.querySelector("lottie-player, [class*=lottie], dotlottie-player"), gsap: !!(window.gsap || window.TweenMax), anime: !!window.anime, aos: !!document.querySelector("[data-aos]"), scrollReveal: !!(window.ScrollReveal || window.sr) };

  // --- SP 特有 ---
  const tap = Array.from(document.querySelectorAll("a[href], button")).filter(visible);
  const small = tap.filter((e) => { const r = e.getBoundingClientRect(); return r.height < 44 || r.width < 44; });
  const fvText = Array.from(document.querySelectorAll("p, li, h1, h2, h3, span")).filter((e) => { if (!visible(e)) return false; const r = e.getBoundingClientRect(); return r.top < vh && r.bottom > 0; }).reduce((n, e) => { const t = Array.from(e.childNodes).filter((c) => c.nodeType === 3).map((c) => c.textContent.trim()).join(""); return n + t.length; }, 0);
  const mobile = { horizontalScroll: html.scrollWidth > vw + 2 || body.scrollWidth > vw + 2, scrollWidth: Math.max(html.scrollWidth, body.scrollWidth), tapTargets: tap.length, smallTapRate: tap.length ? Math.round((small.length / tap.length) * 100) / 100 : null, bodyFontBelow16: bodyText ? bodyText.fontSize < 16 : null, firstViewTextChars: fvText };

  // --- 画像総 px ---
  const imgPx = Array.from(document.images).reduce((n, i) => n + (i.naturalWidth || 0) * (i.naturalHeight || 0), 0);
  const perf = { imageTotalPx: imgPx, imageElements: document.images.length, lcp: window.__lcp ? Math.round(window.__lcp) : null, domNodes: document.getElementsByTagName("*").length };

  return { meta, typography, colors, components, spacing, motion, mobile, perf };
};

// ---------- スクショ画素の量子化で上位色 ----------
const PALETTE_EVAL = async ({ dataUrl, k }) => {
  const img = new Image(); img.src = dataUrl; await img.decode();
  const c = document.createElement("canvas"); const scale = Math.min(1, 200 / img.width);
  c.width = Math.max(1, Math.round(img.width * scale)); c.height = Math.max(1, Math.round(img.height * scale));
  const ctx = c.getContext("2d"); ctx.drawImage(img, 0, 0, c.width, c.height);
  const d = ctx.getImageData(0, 0, c.width, c.height).data; const m = new Map(); const total = d.length / 4;
  for (let i = 0; i < d.length; i += 4) { const q = (v) => Math.min(255, Math.round(v / 16) * 16); const key = (q(d[i]) << 16) | (q(d[i + 1]) << 8) | q(d[i + 2]); m.set(key, (m.get(key) || 0) + 1); }
  return Array.from(m.entries()).sort((a, b) => b[1] - a[1]).slice(0, k).map(([key, n]) => ({ hex: "#" + key.toString(16).padStart(6, "0"), share: Math.round((n / total) * 1000) / 1000 }));
};

// ---------- 1 viewport 分の計測 ----------
async function measureViewport(browser, site, vp, isSp, out, contexts) {
  const t0 = Date.now();
  const context = await browser.newContext({
    viewport: { width: vp.width, height: vp.height }, deviceScaleFactor: vp.dpr, isMobile: isSp, hasTouch: isSp,
    userAgent: UA || (isSp ? "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1" : undefined),
    locale: site.country && /^(jp|ja)/i.test(site.country) ? "ja-JP" : "en-US",
    ignoreHTTPSErrors: true,
  });
  const requests = []; let transfer = 0; let fontBytes = 0, fontFiles = 0;
  context.on("response", async (res) => {
    try {
      const req = res.request(); const url = res.url(); const type = req.resourceType();
      let size = 0; try { const sz = await req.sizes(); size = sz.responseBodySize + sz.responseHeadersSize; } catch { const cl = res.headers()["content-length"]; size = cl ? Number(cl) : 0; }
      transfer += size; requests.push({ type, status: res.status() });
      const ct = res.headers()["content-type"] || "";
      if (type === "font" || /font\/|application\/font|\.(woff2?|ttf|otf)(\?|$)/i.test(ct + " " + url)) { fontFiles++; fontBytes += size; }
    } catch {}
  });
  contexts.push(context);
  const page = await context.newPage();
  await page.addInitScript(INIT_SCRIPT);
  out.viewport = vp; out.error = null; out.timing = {};
  try {
    let resp = null;
    try { resp = await page.goto(site.url, { waitUntil: "domcontentloaded", timeout: NAV_MS }); } catch (e) { if (!/Timeout/i.test(String(e))) throw e; out.timing.navTimeout = true; }
    dbg(site.id, isSp ? "sp" : "pc", "goto", Date.now() - t0);
    try { await page.waitForLoadState("networkidle", { timeout: Math.max(1000, NAV_MS - (Date.now() - t0)) }); } catch { out.timing.networkIdleTimeout = true; }
    dbg(site.id, isSp ? "sp" : "pc", "idle", Date.now() - t0);
    out.status = resp ? resp.status() : null;
    out.timing.loadMs = Date.now() - t0;
    const bodyTxt = await page.evaluate(() => (document.body ? document.body.innerText.slice(0, 2000) : "")).catch(() => "");
    const botLike = /access denied|attention required|verify you are human|are you a robot|just a moment|cloudflare|captcha|bot detection|blocked|forbidden|アクセスが拒否/i.test(bodyTxt) && bodyTxt.length < 1500;
    if ((out.status && out.status >= 400) || botLike) { out.error = { kind: out.status && out.status >= 400 ? `http-${out.status}` : "bot-detected", snippet: bodyTxt.slice(0, 200) }; }
    // 遅延読込・IO 系を発火させるため軽くスクロールして戻る
    await Promise.race([
      page.evaluate(async () => { const h = Math.min(document.documentElement.scrollHeight, 5000); for (let i = 1; i <= 4; i++) { scrollTo(0, (h * i) / 4); await new Promise((r) => requestAnimationFrame(() => setTimeout(r, 40))); } scrollTo(0, 0); await new Promise((r) => setTimeout(r, 200)); }),
      new Promise((r) => setTimeout(r, 4000)),
    ]).catch(() => {});
    dbg(site.id, isSp ? "sp" : "pc", "scrolled", Date.now() - t0);
    out.data = await page.evaluate(PAGE_EVAL, { vw: vp.width, vh: vp.height, isSp });
    dbg(site.id, isSp ? "sp" : "pc", "evaluated", Date.now() - t0);
    // スクリーンショット (上から N 画面)
    const shotH = isSp ? vp.height * 3 : vp.height * 2;
    const shotPath = path.join(OUT, "shots", `${site.id}-${isSp ? "sp" : "pc"}.webp`);
    const docH = out.data.meta.docHeight || shotH;
    const clipH = Math.min(shotH, Math.max(vp.height, docH));
    const buf = await page.screenshot({ path: shotPath, type: "webp", quality: 80, fullPage: true, clip: { x: 0, y: 0, width: vp.width, height: clipH }, timeout: 15000, animations: "disabled" }).catch(async () => page.screenshot({ path: shotPath, type: "webp", quality: 80, timeout: 10000 }));
    out.screenshot = path.relative(OUT, shotPath);
    dbg(site.id, isSp ? "sp" : "pc", "shot", Date.now() - t0);
    // 上位色: 撮った画像を JPEG 化して canvas で量子化
    try {
      out.data.colors.topColors = await page.evaluate(PALETTE_EVAL, { dataUrl: "data:image/webp;base64," + buf.toString("base64"), k: 6 });
    } catch (e) { out.data.colors.topColors = null; }
    dbg(site.id, isSp ? "sp" : "pc", "palette", Date.now() - t0);
    out.data.perf.transferBytes = transfer; out.data.perf.requests = requests.length;
    out.data.perf.requestsByType = requests.reduce((m, r) => { m[r.type] = (m[r.type] || 0) + 1; return m; }, {});
    out.data.perf.fontFiles = fontFiles; out.data.perf.fontBytes = fontBytes;
  } catch (e) {
    out.error = out.error || { kind: "exception", message: String(e && e.message || e).slice(0, 300) };
  } finally { out.timing.totalMs = Date.now() - t0; out.done = !out.error; await context.close().catch(() => {}); }
  return out;
}

async function measureSite(browser, site) {
  const started = Date.now();
  const contexts = [];
  const result = { id: site.id, pattern: site.pattern, url: site.url, country: site.country, genre: site.genre, notes: site.notes, measuredAt: new Date().toISOString(), error: null };
  const work = (async () => {
    result.sp = {}; result.pc = {};
    await Promise.all([
      measureViewport(browser, site, { width: 390, height: 844, dpr: 2 }, true, result.sp, contexts),
      measureViewport(browser, site, { width: 1280, height: 800, dpr: 1 }, false, result.pc, contexts),
    ]);
  })();
  let timer;
  const timeout = new Promise((_, rej) => { timer = setTimeout(() => rej(new Error(`per-url timeout ${PER_URL_MS}ms`)), PER_URL_MS); });
  try { await Promise.race([work, timeout]); } catch (e) {
    result.error = { kind: "timeout", message: String(e.message), partial: { sp: !!(result.sp && result.sp.data), pc: !!(result.pc && result.pc.data) } };
    for (const v of [result.sp, result.pc]) if (v && !v.done) { v.error = v.error || { kind: "timeout" }; v.timing = v.timing || {}; v.timing.totalMs = Date.now() - started; }
    await Promise.all(contexts.map((c) => c.close().catch(() => {})));
  } finally { clearTimeout(timer); }
  if (!result.error && (result.sp?.error || result.pc?.error)) result.error = result.sp?.error || result.pc?.error;
  result.elapsedMs = Date.now() - started;
  fs.writeFileSync(path.join(OUT, "results", `${site.id}.json`), JSON.stringify(result, null, 2));
  return result;
}

// ---------- メイン ----------
const { chromium } = await loadPlaywright();
const browser = await chromium.launch({ headless: true });
const t0 = Date.now();
let idx = 0; const summary = [];
async function worker() {
  while (idx < sites.length) {
    const s = sites[idx++];
    const r = await measureSite(browser, s);
    summary.push({ id: r.id, pattern: r.pattern, ms: r.elapsedMs, error: r.error ? r.error.kind : null });
    console.log(`[${summary.length}/${sites.length}] ${r.id} ${r.error ? "ERROR " + r.error.kind : "ok"} ${r.elapsedMs}ms`);
  }
}
await Promise.all(Array.from({ length: Math.min(CONCURRENCY, sites.length) }, worker));
await browser.close();
console.log(`done: ${sites.length} sites, ${Date.now() - t0}ms, errors=${summary.filter((s) => s.error).length}`);

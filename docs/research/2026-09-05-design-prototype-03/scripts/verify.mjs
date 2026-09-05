#!/usr/bin/env node
// verify.mjs — 試作 03 の検証: JS 無効描画、reduced-motion、CTA コントラスト、404 ステータス、SP/PC 44px 監査、自動コントラスト guard、LP の form/fixed/LCP。
// 使い方: NODE_PATH=<playwright の node_modules> node verify.mjs --base http://localhost:8086 --out ../results/verify.json
import fs from "node:fs";
import path from "node:path";
import { execFileSync } from "node:child_process";
import { createRequire } from "node:module";
const require = createRequire(process.env.NODE_PATH ? path.join(process.env.NODE_PATH, "x.js") : import.meta.url); // NODE_PATH の playwright を優先（リポ内の別版と混ざらないように）
const { chromium } = require("playwright");
const args = Object.fromEntries(process.argv.slice(2).map((a, i, arr) => a.startsWith("--") ? [a.slice(2), arr[i + 1]] : null).filter(Boolean));
const BASE = args.base || "http://localhost:8086";
// pr:auto の陽性/陰性/境界フィクスチャ検査に使う wp-cli（docker compose の wpcli サービス）の project dir。
// 未指定なら該当検査はスキップし出力に理由を残す（環境依存の docker-compose 経路を必須にしないため）。
const WPCLIDIR = args.wpclidir || null;
const OUT = path.resolve(args.out || "../results/verify.json");
const ARTICLE = "/standing-desk-compare/";
const CATEGORY = "/category/topic-index/";
const LP = "/lp/";
const SP = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const PC = { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 };
const out = {};
const browser = await chromium.launch();

const lum = (rgb) => { const [r, g, b] = rgb; const f = (c) => { c /= 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
const parse = (s) => { const m = s.match(/rgba?\(([^)]+)\)/); if (!m) return null; const p = m[1].split(",").map(Number); return { rgb: p.slice(0, 3), a: p[3] ?? 1 }; };
const ratio = (l1, l2) => (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);

// 1. JS 無効: 主要要素が見える・目次が開いている・出現要素が透明でない
{
  const ctx = await browser.newContext({ ...SP, javaScriptEnabled: false });
  const p = await ctx.newPage(); await p.goto(BASE + ARTICLE + "?wt=motion:on,header:announce", { waitUntil: "load" });
  out.noJs = await p.evaluate(() => {
    const vis = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.visibility !== "hidden" && s.display !== "none"; };
    const rev = Array.from(document.querySelectorAll(".wt-reveal"));
    return {
      wtJsClass: document.documentElement.classList.contains("wt-js"),
      tocVisible: vis(document.querySelector(".wt-toc")), tocOpen: !!document.querySelector(".wt-toc details[open]"),
      tocLinks: document.querySelectorAll(".wt-toc a").length,
      revealCount: rev.length, revealHidden: rev.filter((e) => parseFloat(getComputedStyle(e).opacity) < 1).length,
      productVisible: vis(document.querySelector(".is-style-wt-product")), tableVisible: vis(document.querySelector(".is-style-wt-compare")),
      relatedCards: document.querySelectorAll(".wt-related .wt-rcard").length,
      textChars: document.querySelector("main").innerText.replace(/\s+/g, "").length,
      headerVisible: vis(document.querySelector(".wt-header")), announceExists: !!document.querySelector(".wt-announce"), announceVisible: vis(document.querySelector(".wt-announce")),
    };
  });
  out.noJs.pass = out.noJs.announceExists && out.noJs.announceVisible && out.noJs.revealHidden === 0 && out.noJs.tocVisible;
  await ctx.close();
}
// 2. reduced-motion: motion:on でも出現要素が初期表示、ヘッダー transition なし、count-up は最終値
{
  const ctx = await browser.newContext({ ...SP, reducedMotion: "reduce" });
  const p = await ctx.newPage(); await p.goto(BASE + ARTICLE + "?wt=motion:on", { waitUntil: "networkidle" });
  const a = await p.evaluate(() => ({
    revealHidden: Array.from(document.querySelectorAll(".wt-reveal")).filter((e) => parseFloat(getComputedStyle(e).opacity) < 1).length,
    revealTotal: document.querySelectorAll(".wt-reveal").length,
    headerTransition: getComputedStyle(document.querySelector(".wt-header")).transitionProperty,
    buttonTransition: getComputedStyle(document.querySelector(".wp-block-button__link")).transitionProperty,
  }));
  await p.goto(BASE + "/catalog-03/?wt=motion:on", { waitUntil: "networkidle" });
  a.countUpText = await p.textContent(".wt-count");
  // 比較: reduced-motion なし・motion:on で読み込み直後の出現要素
  const ctx2 = await browser.newContext(SP); const p2 = await ctx2.newPage(); await p2.goto(BASE + ARTICLE + "?wt=motion:on", { waitUntil: "networkidle" });
  a.noPref_revealHiddenAtLoad = await p2.evaluate(() => Array.from(document.querySelectorAll(".wt-reveal")).filter((e) => parseFloat(getComputedStyle(e).opacity) < 1).length);
  await p2.evaluate(() => scrollTo(0, document.body.scrollHeight)); await p2.waitForTimeout(900);
  a.noPref_revealHiddenAfterScroll = await p2.evaluate(() => Array.from(document.querySelectorAll(".wt-reveal")).filter((e) => parseFloat(getComputedStyle(e).opacity) < 1).length);
  out.reducedMotion = a; await ctx.close(); await ctx2.close();
}
// 3. コントラスト: CTA ボタン、リンク、補助文字、目次リンク、帯見出し
{
  const ctx = await browser.newContext(PC); const p = await ctx.newPage(); await p.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  const pairs = await p.evaluate(() => {
    const bg = (el) => { let e = el; while (e) { const c = getComputedStyle(e).backgroundColor; if (c && !/rgba\(0, 0, 0, 0\)|transparent/.test(c)) return c; e = e.parentElement; } return "rgb(255, 255, 255)"; };
    const pick = (sel, label) => { const el = document.querySelector(sel); if (!el) return { label, missing: true }; const s = getComputedStyle(el); return { label, color: s.color, bg: bg(el), fontSize: parseFloat(s.fontSize), fontWeight: s.fontWeight }; };
    return [pick(".wp-block-button__link.has-cta-background-color", "CTA button"), pick(".wp-block-post-content > p > a", "body link (inline in paragraph)"), pick(".wt-sub", "helper text (mute)"), pick(".wt-pr", "PR notice"), pick(".wt-toc a", "toc link"), pick(".is-style-wt-band-title > :first-child", "band title"), pick(".wt-badge--rank", "rank badge"), pick(".is-style-outline .wp-block-button__link", "outline button"), pick(".wt-product__price small", "price unit"), pick(".wt-linkcard__label", "linkcard label"), pick(".wt-rcard .wp-block-post-date", "card date")];
  });
  out.contrast = pairs.map((x) => { if (x.missing) return x; const c = parse(x.color), b = parse(x.bg); const r = ratio(lum(c.rgb), lum(b.rgb)); const large = x.fontSize >= 24 || (x.fontSize >= 18.67 && parseInt(x.fontWeight) >= 700); return { ...x, ratio: Math.round(r * 100) / 100, required: large ? 3 : 4.5, pass: r >= (large ? 3 : 4.5) }; });
  await ctx.close();
}
// 4. 404 ステータス（3 変種 + 素の URL）
{
  const ctx = await browser.newContext(SP); const p = await ctx.newPage(); out.status404 = {};
  for (const u of ["/no-such-page-standing-desk-guide/", "/no-such-page/?wt=nf:popular", "/no-such-page/?wt=nf:cta", "/no-such-page/?wt=nf:suggest"]) { const r = await p.goto(BASE + u); out.status404[u] = r.status(); }
  out.status404.robotsAll = await p.evaluate(() => Array.from(document.querySelectorAll('meta[name="robots"]')).map((m) => m.content));
  out.status404.noindex = out.status404.robotsAll.some((c) => /\bnoindex\b/.test(c));
  out.status404.has = await p.evaluate(() => ({ apology: /すみません|申し訳/.test(document.body.innerText), cause: /可能性/.test(document.body.innerText), search: !!document.querySelector(".wt-404__search input[type=search]") && !!document.querySelector(".wt-404__search button"), popular: !!document.querySelector(".wt-404__variant--popular .wt-rcard"), categories: !!document.querySelector(".wt-404__cats a"), home: !!document.querySelector('.wt-404__home a[href="/"]'), cvSlot: document.querySelectorAll(".wt-cv__item").length, suggestLinks: document.querySelectorAll(".wt-suggest a").length }));
  await ctx.close();
}
// 5. SP タップ領域監査（記事・404・カタログ）: 除外は「p / li 直下の display:inline なリンク」だけ（WCAG 2.5.8 のインライン例外）。
//    独立リンク（サイト名・パンくず・ターム・カードタイトル等）は判定する。44px（P05 の独自目標）と 24px（WCAG 2.5.8 下限）を分けて記録
{
  const ctx = await browser.newContext(SP); const p = await ctx.newPage(); out.tap = {};
  for (const [k, u] of [["article", ARTICLE], ["article-announce", ARTICLE + "?wt=header:announce,related:carousel,share:float"], ["404", "/no-such-page/"], ["catalog", "/catalog-03/"]]) {
    await p.goto(BASE + u, { waitUntil: "networkidle" }); await p.waitForTimeout(300);
    out.tap[k] = await p.evaluate(() => {
      const els = Array.from(document.querySelectorAll("a[href], button, input, summary, [role=button]"));
      const res = { total: 0, ok44: 0, ok24: 0, inlineText: 0, srOnly: [], below44: [], below24: [] };
      for (const el of els) {
        const r = el.getBoundingClientRect(); const s = getComputedStyle(el);
        if (r.width === 0 || r.height === 0 || s.visibility === "hidden" || s.display === "none") continue;
        const desc = (el.tagName.toLowerCase() + (el.className && typeof el.className === "string" ? "." + el.className.split(" ").slice(0, 2).join(".") : "") + " '" + (el.getAttribute("aria-label") || el.textContent || el.value || "").trim().slice(0, 24) + "' " + Math.round(r.width) + "x" + Math.round(r.height));
        if (el.classList.contains("screen-reader-text")) { res.srOnly.push(desc); continue; } // フォーカス時のみ表示される SR 用リンク
        const inline = el.tagName === "A" && s.display === "inline" && el.parentElement && /^(P|LI)$/.test(el.parentElement.tagName);
        if (inline) { res.inlineText++; continue; }
        res.total++;
        if (r.width >= 44 && r.height >= 44) res.ok44++; else res.below44.push(desc);
        if (r.width >= 24 && r.height >= 24) res.ok24++; else res.below24.push(desc);
      }
      return res;
    });
  }
  await ctx.close();
}
// 6. 自動コントラスト guard（実描画から算出）: 文字要素の矩形位置で (a) スクリム擬似要素の linear-gradient を解析して実効 alpha / 色を線形補間、
//    (b) 画像を canvas に描き文字矩形の平均輝度を測り、合成輝度と文字色の比を出す。gradient・filter の補間は線形近似（概算）
{
  const ctx = await browser.newContext(PC); const p = await ctx.newPage();
  const measureOn = async (page, sel, pseudo, textSel) => page.evaluate(([sel, pseudo, textSel]) => {
    const el = document.querySelector(sel); if (!el) return { sel, missing: true };
    const img = el.querySelector("img"); const txt = el.querySelector(textSel);
    const er = el.getBoundingClientRect(), tr = txt.getBoundingClientRect();
    // (a) gradient の実効 alpha（to top: 0% = 下端）
    const bgi = getComputedStyle(el, pseudo).backgroundImage;
    const stops = Array.from(bgi.matchAll(/rgba?\(([^)]+)\)\s*([\d.]+)%/g)).map((m) => { const c = m[1].split(",").map(Number); return { rgb: c.slice(0, 3), a: c[3] ?? 1, pos: parseFloat(m[2]) / 100 }; });
    const fracFromBottom = (y) => (er.bottom - y) / er.height;
    const alphaAt = (f) => { if (!stops.length) return null; if (f <= stops[0].pos) return stops[0].a; for (let i = 1; i < stops.length; i++) if (f <= stops[i].pos) { const s0 = stops[i - 1], s1 = stops[i]; return s0.a + (s1.a - s0.a) * ((f - s0.pos) / (s1.pos - s0.pos)); } return stops[stops.length - 1].a; };
    const colorAt = (f) => { if (!stops.length) return null; if (f <= stops[0].pos) return stops[0].rgb; for (let i = 1; i < stops.length; i++) if (f <= stops[i].pos) { const s0 = stops[i - 1], s1 = stops[i], t = (f - s0.pos) / (s1.pos - s0.pos); return s0.rgb.map((v, i) => v + (s1.rgb[i] - v) * t); } return stops[stops.length - 1].rgb; };
    const aTop = alphaAt(fracFromBottom(tr.top)), aBottom = alphaAt(fracFromBottom(tr.bottom));
    const alpha = aTop == null ? null : Math.min(aTop, aBottom); // 文字矩形内で最も薄い位置（最悪値）
    const overlayAt = aTop == null ? null : (aTop <= aBottom ? colorAt(fracFromBottom(tr.top)) : colorAt(fracFromBottom(tr.bottom)));
    // (b) 画像の文字矩形の平均輝度
    const c = document.createElement("canvas"); c.width = img.naturalWidth; c.height = img.naturalHeight; const cx = c.getContext("2d"); cx.drawImage(img, 0, 0);
    // object-fit: cover の写像（中央基準）
    const ir = img.getBoundingClientRect(); const scale = Math.max(ir.width / img.naturalWidth, ir.height / img.naturalHeight);
    const dw = img.naturalWidth * scale, dh = img.naturalHeight * scale, ox = ir.left + (ir.width - dw) / 2, oy = ir.top + (ir.height - dh) / 2;
    const sx = Math.max(0, (tr.left - ox) / scale), sy = Math.max(0, (tr.top - oy) / scale), sw = Math.min(img.naturalWidth - sx, tr.width / scale), sh = Math.min(img.naturalHeight - sy, tr.height / scale);
    const d = cx.getImageData(Math.floor(sx), Math.floor(sy), Math.max(1, Math.floor(sw)), Math.max(1, Math.floor(sh))).data;
    const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    let sum = 0, n = 0, lmin = 1, lmax = 0; for (let i = 0; i < d.length; i += 4) { const L = 0.2126 * f(d[i]) + 0.7152 * f(d[i + 1]) + 0.0722 * f(d[i + 2]); sum += L; n++; lmin = Math.min(lmin, L); lmax = Math.max(lmax, L); }
    const ts = getComputedStyle(txt);
    const tc = ts.color.match(/[\d.]+/g).map(Number);
    const filter = getComputedStyle(img).filter;
    const brightnessMatch = filter.match(/brightness\(([\d.]+)\)/);
    return { sel, textLum: tc, lum: el.getAttribute("data-wt-lum"), sampledL: parseFloat(el.getAttribute("data-wt-lum-value")), gradient: bgi.slice(0, 160), overlayColor: overlayAt, filterBrightness: brightnessMatch ? parseFloat(brightnessMatch[1]) : 1, alphaAtText: alpha == null ? null : Math.round(alpha * 1000) / 1000, imageLAtText: Math.round((sum / n) * 1000) / 1000, imageLMaxAtText: Math.round(lmax * 1000) / 1000, textColor: ts.color, fontSize: parseFloat(ts.fontSize), fontWeight: ts.fontWeight };
  }, [sel, pseudo, textSel]);
  const measure = (sel, pseudo, textSel) => measureOn(p, sel, pseudo, textSel);
  const finish = (x) => { if (x.missing || x.alphaAtText == null) return { ...x, note: "スクリム未検出" }; const Lt = Array.isArray(x.textLum) ? lum(x.textLum.slice(0, 3)) : 1; const overlayL = Array.isArray(x.overlayColor) ? lum(x.overlayColor.slice(0, 3)) : 0; const factor = Number.isFinite(x.filterBrightness) ? x.filterBrightness : 1; const imageL = x.imageLAtText * factor, imageLMax = x.imageLMaxAtText * factor; const Lc = imageL * (1 - x.alphaAtText) + overlayL * x.alphaAtText, LcMax = imageLMax * (1 - x.alphaAtText) + overlayL * x.alphaAtText; const r = ratio(Lt, Lc), rWorst = ratio(Lt, LcMax); const large = x.fontSize >= 24 || (x.fontSize >= 18.67 && parseInt(x.fontWeight) >= 700); return { ...x, compositeL: Math.round(Lc * 1000) / 1000, textL: Math.round(Lt * 1000) / 1000, ratioText: Math.round(r * 100) / 100, ratioWorstPixel: Math.round(rWorst * 100) / 100, ratioWithoutScrim: Math.round(ratio(Lt, x.imageLAtText) * 100) / 100, required: large ? 3 : 4.5, pass: r >= (large ? 3 : 4.5) }; };
  await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" }); await p.waitForTimeout(600);
  out.contrastGuard = [];
  for (const k of ["dark", "mid", "light"]) { out.contrastGuard.push(finish(await measure("#cat-contrast-" + k, "::before", "p"))); out.contrastGuard.push(finish(await measure("#cat-contrast-" + k, "::before", "h3"))); }
  await p.goto(BASE + ARTICLE + "?wt=eyecatch:hero", { waitUntil: "networkidle" }); await p.waitForTimeout(600);
  // hero アイキャッチ: 文字は兄弟要素 .wt-posthead__text（同じ grid cell）
  const hero = await p.evaluate(() => {
    const el = document.querySelector(".wt-posthead__img"); const img = el.querySelector("img");
    const er = el.getBoundingClientRect(); const bgi = getComputedStyle(el, "::after").backgroundImage;
    const stops = Array.from(bgi.matchAll(/rgba?\(([^)]+)\)\s*([\d.]+)%/g)).map((m) => { const c = m[1].split(",").map(Number); return { a: c[3] ?? 1, pos: parseFloat(m[2]) / 100 }; });
    const alphaAt = (f) => { if (f <= stops[0].pos) return stops[0].a; for (let i = 1; i < stops.length; i++) { if (f <= stops[i].pos) { const s0 = stops[i - 1], s1 = stops[i]; return s0.a + (s1.a - s0.a) * ((f - s0.pos) / (s1.pos - s0.pos)); } } return stops[stops.length - 1].a; };
    const c = document.createElement("canvas"); c.width = img.naturalWidth; c.height = img.naturalHeight; const cx = c.getContext("2d"); cx.drawImage(img, 0, 0);
    const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    const sample = (tr) => { const ir = img.getBoundingClientRect(); const scale = Math.max(ir.width / img.naturalWidth, ir.height / img.naturalHeight); const dw = img.naturalWidth * scale, dh = img.naturalHeight * scale, ox = ir.left + (ir.width - dw) / 2, oy = ir.top + (ir.height - dh) / 2; const sx = Math.max(0, (tr.left - ox) / scale), sy = Math.max(0, (tr.top - oy) / scale); const d = cx.getImageData(Math.floor(sx), Math.floor(sy), Math.max(1, Math.floor(tr.width / scale)), Math.max(1, Math.floor(tr.height / scale))).data; let sm = 0, n = 0, mx = 0; for (let i = 0; i < d.length; i += 4) { const L = 0.2126 * f(d[i]) + 0.7152 * f(d[i + 1]) + 0.0722 * f(d[i + 2]); sm += L; n++; mx = Math.max(mx, L); } return [sm / n, mx]; };
    const res = (t, label) => { const tr = t.getBoundingClientRect(); const a = Math.min(alphaAt((er.bottom - tr.top) / er.height), alphaAt((er.bottom - tr.bottom) / er.height)); const s2 = sample(tr); const ts = getComputedStyle(t); const tc = ts.color.match(/[\d.]+/g).map(Number); return { sel: "article hero " + label, textLum: tc, lum: el.getAttribute("data-wt-lum"), sampledL: parseFloat(el.getAttribute("data-wt-lum-value")), gradient: bgi.slice(0, 120), alphaAtText: Math.round(a * 1000) / 1000, imageLAtText: Math.round(s2[0] * 1000) / 1000, imageLMaxAtText: Math.round(s2[1] * 1000) / 1000, textColor: ts.color, fontSize: parseFloat(ts.fontSize), fontWeight: ts.fontWeight }; };
    const metaKids = Array.from(document.querySelectorAll(".wt-posthead__text .wt-meta > *, .wt-posthead__text .wt-breadcrumb a")).map((el, i) => res(el.querySelector("time, a") || el, "meta:" + (el.className || el.tagName).toString().split(" ")[0] + "#" + i));
    return [res(document.querySelector(".wt-posthead__text h1"), "h1"), ...metaKids];
  });
  hero.forEach((h) => out.contrastGuard.push(finish(h)));
  await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" }); await p.waitForTimeout(600);
  // Astra 是正: 7 型 × dark/mid/light × 本文/見出し = 42 判定を PC / SP、さらに JS 無効（data-wt-lum 属性なし = 強の既定）でも実施する。
  // カタログの cover は型 class 単独（is-style-wt-scrim なし）で置かれており、singleClass で単独成立を確認する。
  const contrastVariants = ["white-fade", "overlay-warm", "overlay-cool", "overlay-brand", "bottom-gradient", "blur-bright", "duotone"];
  const collectVariants = async (page) => {
    const rows = [];
    for (const variant of contrastVariants) for (const image of ["dark", "mid", "light"]) {
      const id = `#cat-contrast-${variant}-${image}`;
      const meta = await page.evaluate((id) => { const el = document.querySelector(id); return el ? { singleClass: !el.classList.contains("is-style-wt-scrim"), hasBefore: getComputedStyle(el, "::before").content !== "none" } : { singleClass: false, hasBefore: false }; }, id);
      for (const [textSel, text] of [["p", "body"], ["h3", "heading"]]) {
        const r = finish(await measureOn(page, id, "::before", textSel)); rows.push({ ...r, ...meta, variant, image, text });
      }
    }
    return rows;
  };
  out.contrastVariants = await collectVariants(p);
  { const c2 = await browser.newContext(SP); const p2 = await c2.newPage(); await p2.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" }); await p2.waitForTimeout(600); out.contrastVariantsSp = await collectVariants(p2); await c2.close(); }
  { const c3 = await browser.newContext({ ...PC, javaScriptEnabled: false }); const p3 = await c3.newPage(); await p3.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" }); await p3.waitForTimeout(600); out.contrastVariantsNoJs = await collectVariants(p3); await c3.close(); }
  { const c4 = await browser.newContext({ ...SP, javaScriptEnabled: false }); const p4 = await c4.newPage(); await p4.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" }); await p4.waitForTimeout(600); out.contrastVariantsNoJsSp = await collectVariants(p4); await c4.close(); }
  await ctx.close();
}
// 7. 見出し 1 行収まり（SP 390、20 字）と本文列幅・目次しきい値
{
  const ctx = await browser.newContext(SP); const p = await ctx.newPage(); await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" });
  out.headline = await p.evaluate(() => { const h = document.querySelector("#cat-h2-plain h2"); const s = getComputedStyle(h); const r = h.getBoundingClientRect(); const lines = Math.round(r.height / (parseFloat(s.lineHeight))); return { text: h.textContent, chars: h.textContent.length, fontSize: parseFloat(s.fontSize), lineHeight: parseFloat(s.lineHeight), boxHeight: r.height, lines, contentWidth: h.parentElement.getBoundingClientRect().width }; });
  await p.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  out.table = await p.evaluate(() => { const t = document.querySelector(".is-style-wt-compare table"); const thead = t.querySelector("thead"); const ths = Array.from(thead.querySelectorAll("th")); return { theadIntact: !!thead && !document.querySelector("thead[ead], th[ead]") && !/<th scope="col"ead>/.test(t.outerHTML), thCount: ths.length, thWithScopeCol: ths.filter((th) => th.getAttribute("scope") === "col").length, rowHeaders: t.querySelectorAll("tbody th[scope=row]").length, rowHeaderTd: t.querySelectorAll("tbody td[scope=row]").length, rows: t.querySelectorAll("tbody tr").length, dataTh: t.querySelectorAll("tbody td[data-th]").length, rowHeaderDataTh: t.querySelectorAll("tbody th[data-th]").length, tfootRewritten: t.querySelectorAll("tfoot th[scope=row], tfoot td[data-th]").length, caption: !!t.querySelector("caption") }; });
  out.table.pass = out.table.theadIntact && out.table.thCount === out.table.thWithScopeCol && out.table.rowHeaders === out.table.rows && out.table.rowHeaderTd === 0 && out.table.rowHeaderDataTh === 0 && out.table.tfootRewritten === 0;
  out.toc = await p.evaluate(() => ({ h2Count: document.querySelectorAll(".wp-block-post-content h2").length, h3Count: document.querySelectorAll(".wp-block-post-content h3").length, tocH2: document.querySelectorAll(".wt-toc__list > li").length, tocH3: document.querySelectorAll(".wt-toc__list ol li").length, scrollMarginTop: getComputedStyle(document.querySelector("h2[id]")).scrollMarginTop, spClosedByJs: !document.querySelector(".wt-toc details").open }));
  await ctx.close();
}
// 8. 段 3 guard: カテゴリ面の SP / PC タップ監査、著者 SNS タップ、hero コントラスト（実描画）、footer の no-JS 展開、load-more の no-JS 退避と JS 実動、ページ送り 200
{
  const audit = async (page, rootSelector) => page.evaluate((selector) => {
    const root = selector ? document.querySelector(selector) : document;
    if (!root) return { total: 0, ok44: 0, ok24: 0, inlineText: 0, below44: [], below24: [], pass: false, missing: true };
    const els = Array.from(root.querySelectorAll("a[href], button, input, summary, [role=button]"));
    const res = { total: 0, ok44: 0, ok24: 0, inlineText: 0, below44: [], below24: [] };
    for (const el of els) {
      const r = el.getBoundingClientRect(); const s = getComputedStyle(el);
      if (r.width === 0 || r.height === 0 || s.visibility === "hidden" || s.display === "none") continue;
      const desc = el.tagName.toLowerCase() + " '" + (el.getAttribute("aria-label") || el.textContent || el.value || "").trim().slice(0, 24) + "' " + Math.round(r.width) + "x" + Math.round(r.height);
      const inline = el.tagName === "A" && s.display === "inline" && el.parentElement && /^(P|LI)$/.test(el.parentElement.tagName);
      if (inline) { res.inlineText++; continue; }
      res.total++;
      if (r.width >= 44 && r.height >= 44) res.ok44++; else res.below44.push(desc);
      if (r.width >= 24 && r.height >= 24) res.ok24++; else res.below24.push(desc);
    }
    res.pass = res.below44.length === 0 && res.below24.length === 0;
    return res;
  }, rootSelector);
  const sp = await browser.newContext(SP); const spPage = await sp.newPage();
  await spPage.goto(BASE + CATEGORY, { waitUntil: "networkidle" }); await spPage.waitForTimeout(300);
  out.tap.categorySp = await audit(spPage, ".wt-category");
  const pc = await browser.newContext(PC); const pcPage = await pc.newPage();
  await pcPage.goto(BASE + CATEGORY, { waitUntil: "networkidle" }); await pcPage.waitForTimeout(300);
  out.tap.categoryPc = await audit(pcPage, ".wt-category");
  const paginationLinks = await pcPage.$$eval(".wt-cat-pagination a[href]", (links) => links.map((a) => a.href));
  const paginationStatuses = await pcPage.evaluate(async (hrefs) => Promise.all(hrefs.map(async (href) => { try { const r = await fetch(href, { credentials: "same-origin" }); return { href, status: r.status }; } catch (e) { return { href, status: 0 }; } })), paginationLinks);
  out.categoryPagination = { links: paginationStatuses, pass: paginationStatuses.length > 0 && paginationStatuses.every((x) => x.status === 200) };

  // 著者ボックス avatar-bio-sns の SNS リンク（44px 基準、SP / PC）
  const authorUrl = ARTICLE + "?wt=tail_author:avatar-bio-sns";
  await spPage.goto(BASE + authorUrl, { waitUntil: "networkidle" }); await spPage.waitForTimeout(300);
  out.tap.authorSnsSp = await audit(spPage, ".wt-tail__slot--author");
  await pcPage.goto(BASE + authorUrl, { waitUntil: "networkidle" }); await pcPage.waitForTimeout(300);
  out.tap.authorSnsPc = await audit(pcPage, ".wt-tail__slot--author");
  // JS 有効時の load-more 実動: ボタン表示・番号送り非表示 → クリックで次ページの記事が追記される
  await pcPage.goto(BASE + CATEGORY + "?wt=cat_pagination:load-more", { waitUntil: "networkidle" }); await pcPage.waitForTimeout(300);
  {
    const visible = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none" && !el.hidden; };
    const before = await pcPage.evaluate(() => ({ items: document.querySelectorAll(".wt-cat-list > li").length, buttonVisible: (() => { const b = document.querySelector(".wt-load-more"); if (!b) return false; const r = b.getBoundingClientRect(); return r.width > 0 && r.height > 0 && getComputedStyle(b).display !== "none" && !b.hidden; })(), paginationVisible: (() => { const p = document.querySelector(".wt-cat-pagination"); if (!p) return false; const r = p.getBoundingClientRect(); return r.width > 0 && r.height > 0 && getComputedStyle(p).display !== "none"; })(), nextHref: document.querySelector(".wt-cat-pagination a.wp-block-query-pagination-next")?.href || null }));
    let after = { items: before.items, error: null };
    if (before.buttonVisible) {
      await pcPage.click(".wt-load-more");
      try { await pcPage.waitForFunction((n) => document.querySelectorAll(".wt-cat-list > li").length > n, before.items, { timeout: 8000 }); } catch (e) { after.error = "timeout"; }
      await pcPage.waitForTimeout(300);
      after = { ...after, ...(await pcPage.evaluate(() => { const b = document.querySelector(".wt-load-more"); const r = b ? b.getBoundingClientRect() : null; const s = b ? getComputedStyle(b) : null; return { items: document.querySelectorAll(".wt-cat-list > li").length, buttonText: b?.textContent.trim(), buttonHidden: !!b?.hidden, buttonDisplay: s?.display, buttonRect: r ? [Math.round(r.width), Math.round(r.height)] : null, buttonVisible: !!(b && r.width > 0 && r.height > 0 && s.display !== "none" && s.visibility !== "hidden"), nextLinkRemains: !!document.querySelector(".wt-cat-pagination a.wp-block-query-pagination-next"), bodyStillLoadMore: document.body.classList.contains("wt-cat-pagination-load-more") }; })) };
    }
    // 総件数 = カテゴリの投稿数（一覧 1 ページ目 + 追記）。本 PoC データは 17 件・1 ページ 10 件 → 1 回で最終ページ。最終ページ後はボタンが computed で非表示であること
    out.loadMoreJs = { before, after, added: after.items - before.items, pass: before.buttonVisible && !before.paginationVisible && !!before.nextHref && after.items > before.items && after.bodyStillLoadMore === true && after.error === null && after.buttonVisible === false };
  }

  // share:float と footer_totop:button の併用で固定ボタンが重ならない（SP / PC）。両方とも position:fixed なので最下部へスクロールしてから矩形を比較
  out.fixedOverlap = {};
  // 直前の load-more 検査（pcPage）で追記後に history / 追加読み込みが動いている最中に次の goto を始めると
  // "net::ERR_ABORTED; maybe frame was detached?" で中断することがある（2026-09-06 に 3 回連続で実測）。
  // networkidle を待ってから遷移し、中断時は 1 回だけやり直す。検査内容は変えない。
  const gotoSettled = async (page, url) => {
    await page.waitForLoadState("networkidle").catch(() => {}); await page.waitForTimeout(500);
    try { await page.goto(url, { waitUntil: "networkidle" }); }
    catch (e) { if (!/ERR_ABORTED|detached/.test(String(e))) throw e; await page.waitForTimeout(800); await page.goto(url, { waitUntil: "networkidle" }); }
  };
  for (const [dev, page] of [["sp", spPage], ["pc", pcPage]]) {
    await gotoSettled(page, BASE + ARTICLE + "?wt=share:float,footer_totop:button"); await page.evaluate(() => scrollTo(0, document.body.scrollHeight)); await page.waitForTimeout(400);
    out.fixedOverlap[dev] = await page.evaluate(() => {
      const rect = (el) => { const r = el.getBoundingClientRect(); return { x: Math.round(r.left), y: Math.round(r.top), w: Math.round(r.width), h: Math.round(r.height) }; };
      const share = document.querySelector(".wt-share--float"); const top = document.querySelector(".wt-totop");
      if (!share || !top) return { missing: true, pass: false };
      const a = rect(share), b = rect(top); const visible = (r) => r.w > 0 && r.h > 0;
      const intersects = a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y;
      const buttons = Array.from(share.querySelectorAll("button")).map(rect);
      const vw = innerWidth, vh = innerHeight; const inViewport = (r) => r.x >= 0 && r.y >= 0 && r.x + r.w <= vw && r.y + r.h <= vh;
      // クリック到達: 各ボタンの中心点で elementFromPoint がそのボタン（または子孫）であること
      const reach = (el) => { const r = el.getBoundingClientRect(); const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2); return !!hit && (hit === el || el.contains(hit)); };
      const reachable = [...share.querySelectorAll("button"), top].map(reach);
      return { share: a, shareButtons: buttons, totop: b, intersects, reachable, inViewport: inViewport(a) && inViewport(b), pass: visible(a) && visible(b) && !intersects && reachable.every(Boolean) };
    });
  }

  const footerUrl = ARTICLE + "?wt=footer_extra:all,footer_totop:button";
  await spPage.goto(BASE + footerUrl, { waitUntil: "networkidle" }); await spPage.waitForTimeout(300);
  out.tap.footerSp = await audit(spPage, ".wt-footer");
  await pcPage.goto(BASE + footerUrl, { waitUntil: "networkidle" }); await pcPage.waitForTimeout(300);
  out.tap.footerPc = await audit(pcPage, ".wt-footer");
  out.footerContrast = await pcPage.evaluate(() => {
    const root = document.querySelector(".wt-footer");
    if (!root) return { items: [], pass: false, missing: true };
    const parse = (s) => { const m = s.match(/rgba?\(([^)]+)\)/); if (!m) return null; const p = m[1].split(",").map(Number); return { rgb: p.slice(0, 3), a: p[3] ?? 1 }; };
    const lum = (rgb) => { const f = (c) => { c /= 255; return c <= .03928 ? c / 12.92 : Math.pow((c + .055) / 1.055, 2.4); }; return .2126 * f(rgb[0]) + .7152 * f(rgb[1]) + .0722 * f(rgb[2]); };
    const ratio = (a, b) => (Math.max(a, b) + .05) / (Math.min(a, b) + .05);
    const bg = parse(getComputedStyle(root).backgroundColor);
    if (!bg) return { items: [], pass: false, missing: true };
    const selectors = [
      [".wt-footer__brand .wp-block-site-title", "brand"],
      [".wt-footer__sitemap summary", "sitemap summary"],
      [".wt-footer__sitemap a", "sitemap link"],
      [".wt-footer__legal--links p", "legal copyright"],
      [".wt-footer__legal--links a", "legal link"],
      [".wt-footer-extra-slot--sns h2, .wt-footer-extra-slot--sites h2, .wt-footer-extra-slot--badges h2, .wt-footer-extra-slot--address h2", "extra heading"],
      [".wt-footer-extra-slot--sns a", "social icon"],
      [".wt-footer-extra-slot--sites a", "related site"],
      [".wt-footer-extra-slot--badges span", "badge"],
      [".wt-footer-extra-slot--address address", "address"],
      [".wt-totop", "to top"],
    ];
    // 要素自身の実効背景（丸アイコン・to-top ボタンのように自前の背景色を持つ要素は footer 背景でなくそれと比較する）
    const effectiveBg = (el) => { for (let n = el; n && n !== root.parentElement; n = n.parentElement) { const b = parse(getComputedStyle(n).backgroundColor); if (b && b.a > 0) return b; } return bg; };
    const items = [];
    for (const [selector, label] of selectors) for (const el of root.querySelectorAll(selector)) {
      const s = getComputedStyle(el), c = parse(s.color); if (!c) continue;
      const r = ratio(lum(c.rgb), lum(effectiveBg(el).rgb)); const large = parseFloat(s.fontSize) >= 24 || (parseFloat(s.fontSize) >= 18.67 && parseInt(s.fontWeight) >= 700);
      items.push({ label, selector, ratio: Math.round(r * 100) / 100, required: large ? 3 : 4.5, pass: r >= (large ? 3 : 4.5) });
    }
    return { background: getComputedStyle(root).backgroundColor, items, pass: items.length > 0 && items.every((item) => item.pass) };
  });

  const noJsFooter = await browser.newContext({ ...SP, javaScriptEnabled: false }); const noJsFooterPage = await noJsFooter.newPage();
  await noJsFooterPage.goto(BASE + ARTICLE, { waitUntil: "load" });
  out.footerNoJs = await noJsFooterPage.evaluate(() => {
    const details = Array.from(document.querySelectorAll(".wt-footer__sitemap details"));
    const visible = (el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none"; };
    const contentsVisible = details.every((d) => visible(d.querySelector("ul")));
    return { details: details.length, open: details.filter((d) => d.open).length, contentsVisible, pass: details.length > 0 && details.every((d) => d.open) && contentsVisible };
  });
  await noJsFooter.close(); await noJsFooterPage.close().catch(() => {});

  const noJsLoadMore = await browser.newContext({ ...SP, javaScriptEnabled: false }); const noJsLoadMorePage = await noJsLoadMore.newPage();
  await noJsLoadMorePage.goto(BASE + CATEGORY + "?wt=cat_pagination:load-more", { waitUntil: "load" });
  out.loadMoreNoJs = await noJsLoadMorePage.evaluate(() => {
    const pagination = document.querySelector(".wt-cat-pagination"); const button = document.querySelector(".wt-load-more");
    const visible = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none"; };
    const numbers = document.querySelectorAll(".wt-cat-pagination .wp-block-query-pagination-numbers a, .wt-cat-pagination .wp-block-query-pagination-numbers span").length;
    return { paginationVisible: visible(pagination), buttonVisible: visible(button), numbers, pass: visible(pagination) && !visible(button) && numbers > 0 };
  });
  await noJsLoadMore.close(); await noJsLoadMorePage.close().catch(() => {});

  await pcPage.goto(BASE + CATEGORY + "?wt=cat_header:hero", { waitUntil: "networkidle" }); await pcPage.waitForTimeout(300);
  // hero: 段 1 の guard と同じ方式。文字矩形ごとに (a) ::before の linear-gradient（角度付き）を gradient 軸へ射影して実効 α（矩形 4 隅の最小）、
  // (b) 背景画像（background-size: cover）を canvas に描いて文字矩形の平均 / 最大輝度、(c) 実色の輝度 Lt を取り、合成 Lc = L×(1−α) + Lscrim×α との比で判定
  out.categoryHeroContrast = await pcPage.evaluate(async () => {
    const head = document.querySelector(".wt-cat-head"); if (!head) return { missing: true, pass: false };
    const parse = (str) => { const m = str.match(/rgba?\(([^)]+)\)/); if (!m) return null; const c = m[1].split(",").map(Number); return { rgb: c.slice(0, 3), a: c[3] ?? 1 }; };
    const f = (v) => { v /= 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); };
    const lum = (rgb) => .2126 * f(rgb[0]) + .7152 * f(rgb[1]) + .0722 * f(rgb[2]);
    const ratio = (a, b) => (Math.max(a, b) + .05) / (Math.min(a, b) + .05);
    const hs = getComputedStyle(head); const url = (hs.backgroundImage.match(/url\("?([^")]+)"?\)/) || [])[1] || null;
    const scrim = getComputedStyle(head, "::before").backgroundImage;
    const stops = Array.from(scrim.matchAll(/rgba?\(([^)]+)\)(?:\s+([\d.]+)%)?/g)).map((m) => { const c = m[1].split(",").map(Number); return { rgb: c.slice(0, 3), a: c[3] ?? 1, pos: m[2] != null ? parseFloat(m[2]) / 100 : null }; });
    if (!stops.length || !url) return { missing: true, scrim, url, pass: false };
    if (stops[0].pos == null) stops[0].pos = 0; if (stops[stops.length - 1].pos == null) stops[stops.length - 1].pos = 1;
    for (let i = 1; i < stops.length - 1; i++) if (stops[i].pos == null) stops[i].pos = stops[i - 1].pos + (1 - stops[i - 1].pos) / (stops.length - i); // 位置省略は等分
    const angle = parseFloat((scrim.match(/linear-gradient\(\s*(-?[\d.]+)deg/) || [])[1] ?? "180");
    const er = head.getBoundingClientRect(); const th = angle * Math.PI / 180; const dx = Math.sin(th), dy = -Math.cos(th);
    const len = Math.abs(er.width * dx) + Math.abs(er.height * dy); const cx0 = er.left + er.width / 2, cy0 = er.top + er.height / 2;
    const frac = (x, y) => ((x - cx0) * dx + (y - cy0) * dy) / len + .5;
    const alphaAt = (p) => { if (p <= stops[0].pos) return stops[0].a; for (let i = 1; i < stops.length; i++) if (p <= stops[i].pos) { const s0 = stops[i - 1], s1 = stops[i]; return s0.a + (s1.a - s0.a) * ((p - s0.pos) / (s1.pos - s0.pos)); } return stops[stops.length - 1].a; };
    const img = new Image(); img.src = url; try { await img.decode(); } catch (e) { return { missing: true, url, error: "image decode", pass: false }; }
    const cv = document.createElement("canvas"); cv.width = img.naturalWidth; cv.height = img.naturalHeight; const cx = cv.getContext("2d"); cx.drawImage(img, 0, 0);
    const scale = Math.max(er.width / img.naturalWidth, er.height / img.naturalHeight); const dw = img.naturalWidth * scale, dh = img.naturalHeight * scale, ox = er.left + (er.width - dw) / 2, oy = er.top + (er.height - dh) / 2; // cover・center
    const scrimL = lum(stops[0].rgb);
    const measure = (t, label) => {
      const tr = t.getBoundingClientRect(); const ts = getComputedStyle(t); const tc = parse(ts.color);
      const alphas = [[tr.left, tr.top], [tr.right, tr.top], [tr.left, tr.bottom], [tr.right, tr.bottom]].map(([x, y]) => alphaAt(frac(x, y))); const alpha = Math.min(...alphas);
      const sx = Math.max(0, (tr.left - ox) / scale), sy = Math.max(0, (tr.top - oy) / scale), sw = Math.min(img.naturalWidth - sx, tr.width / scale), sh = Math.min(img.naturalHeight - sy, tr.height / scale);
      const d = cx.getImageData(Math.floor(sx), Math.floor(sy), Math.max(1, Math.floor(sw)), Math.max(1, Math.floor(sh))).data;
      let sum = 0, n = 0, lmax = 0; for (let i = 0; i < d.length; i += 4) { const L = lum([d[i], d[i + 1], d[i + 2]]); sum += L; n++; lmax = Math.max(lmax, L); }
      const L = sum / n, Lt = lum(tc.rgb), Lc = L * (1 - alpha) + scrimL * alpha, LcMax = lmax * (1 - alpha) + scrimL * alpha;
      const large = parseFloat(ts.fontSize) >= 24 || (parseFloat(ts.fontSize) >= 18.67 && parseInt(ts.fontWeight) >= 700); const r = ratio(Lt, Lc);
      return { label, textColor: ts.color, fontSize: parseFloat(ts.fontSize), fontWeight: ts.fontWeight, alphaAtText: Math.round(alpha * 1000) / 1000, imageLAtText: Math.round(L * 1000) / 1000, imageLMaxAtText: Math.round(lmax * 1000) / 1000, compositeL: Math.round(Lc * 1000) / 1000, textL: Math.round(Lt * 1000) / 1000, ratioText: Math.round(r * 100) / 100, ratioWorstPixel: Math.round(ratio(Lt, LcMax) * 100) / 100, ratioWithoutScrim: Math.round(ratio(Lt, L) * 100) / 100, required: large ? 3 : 4.5, pass: r >= (large ? 3 : 4.5) };
    };
    const items = [measure(head.querySelector("h1"), "h1")]; const desc = head.querySelector(".wt-cat-head__desc"); if (desc) items.push(measure(desc, "description"));
    return { image: url.split("/").slice(-2).join("/"), gradient: scrim.slice(0, 140), angle, scrimRgb: stops[0].rgb, items, pass: items.every((i) => i.pass) };
  });
  const reduce = await browser.newContext({ ...SP, reducedMotion: "reduce" }); const reducePage = await reduce.newPage();
  await reducePage.goto(BASE + CATEGORY + "?wt=footer_totop:button", { waitUntil: "networkidle" }); await reducePage.waitForTimeout(300);
  out.reducedMotion.categoryFooter = await reducePage.evaluate(() => {
    const card = document.querySelector(".wt-cat-card"); const top = document.querySelector(".wt-totop"); const footer = document.querySelector(".wt-footer");
    if (!card || !top || !footer) return { missing: true, pass: false };
    const cardStyle = getComputedStyle(card); const topStyle = getComputedStyle(top); const footerStyle = getComputedStyle(footer);
    const noTransition = (style) => style.transitionProperty === "none" || style.transitionDuration === "0s";
    return { cardTransition: cardStyle.transitionProperty, topTransition: topStyle.transitionProperty, footerTransition: footerStyle.transitionProperty, pass: noTransition(cardStyle) && noTransition(topStyle) && noTransition(footerStyle) };
  });
  await reduce.close();
  await sp.close(); await pc.close();
}

// 9. 段 4 guard: LP の SP / PC タップ領域、実色コントラスト、hero guard、form、アンカー、固定要素、motion、LCP
{
  const lpTapAudit = async (page) => page.evaluate(() => {
    const visible = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.visibility !== "hidden" && s.display !== "none"; };
    const els = Array.from(document.querySelectorAll("a[href], button, input, summary, [role=button]"));
    const res = { total: 0, ok44: 0, ok24: 0, inlineText: 0, srOnly: [], below44: [], below24: [] };
    for (const el of els) {
      if (!visible(el)) continue;
      const r = el.getBoundingClientRect(); const s = getComputedStyle(el);
      const desc = (el.tagName.toLowerCase() + (el.className && typeof el.className === "string" ? "." + el.className.split(" ").slice(0, 2).join(".") : "") + " '" + (el.getAttribute("aria-label") || el.textContent || el.value || "").trim().slice(0, 24) + "' " + Math.round(r.width) + "x" + Math.round(r.height));
      if (el.classList.contains("screen-reader-text")) { res.srOnly.push(desc); continue; }
      const inline = el.tagName === "A" && ((s.display === "inline" && el.parentElement && /^(P|LI)$/.test(el.parentElement.tagName)) || (el.closest(".wt-lp-legal") && el.parentElement?.tagName === "SUP"));
      if (inline) { res.inlineText++; continue; }
      res.total++;
      if (r.width >= 44 && r.height >= 44) res.ok44++; else res.below44.push(desc);
      if (r.width >= 24 && r.height >= 24) res.ok24++; else res.below24.push(desc);
    }
    res.pass = res.total > 0 && res.below44.length === 0 && res.below24.length === 0;
    return res;
  });
  const lpSpCtx = await browser.newContext(SP); const lpSpPage = await lpSpCtx.newPage();
  await lpSpPage.goto(BASE + LP, { waitUntil: "networkidle" }); await lpSpPage.waitForTimeout(500);
  out.tap.lpSp = await lpTapAudit(lpSpPage);
  const lpPcCtx = await browser.newContext(PC); const lpPcPage = await lpPcCtx.newPage();
  await lpPcPage.goto(BASE + LP, { waitUntil: "networkidle" }); await lpPcPage.waitForTimeout(500);
  out.tap.lpPc = await lpTapAudit(lpPcPage);

  const lpContrast = async (page, style) => page.evaluate((style) => {
    const parse = (s) => { const m = s.match(/rgba?\(([^)]+)\)/); if (!m) return null; const p = m[1].split(",").map(Number); return { rgb: p.slice(0, 3), a: p[3] ?? 1 }; };
    const lum = (rgb) => { const f = (c) => { c /= 255; return c <= .03928 ? c / 12.92 : Math.pow((c + .055) / 1.055, 2.4); }; return .2126 * f(rgb[0]) + .7152 * f(rgb[1]) + .0722 * f(rgb[2]); };
    const ratio = (a, b) => (Math.max(a, b) + .05) / (Math.min(a, b) + .05);
    const visible = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none" && s.visibility !== "hidden"; };
    const effectiveBg = (el) => { for (let n = el; n; n = n.parentElement) { const c = parse(getComputedStyle(n).backgroundColor); if (c && c.a > 0) return c; } return { rgb: [255, 255, 255], a: 1 }; };
    const selectors = [
      [".wt-lp-header .wt-lp-cta-action", "header CTA"],
      [".wt-lp-hero .wt-lp-cta-action", "hero CTA"],
      [".wt-lp-cta-band .wt-lp-cta-action", "CTA band action"],
      [".wt-lp-cta-band h2", "CTA band heading"],
      [".wt-lp-cta-band p", "CTA band text"],
      [".wt-plan--featured h3", "featured pricing heading"],
      [".wt-plan--featured .wt-price", "featured pricing price"],
      [".wt-plan--featured li", "featured pricing item"],
      [".wt-plan--featured .wp-block-button__link", "featured pricing CTA"],
    ];
    const items = [];
    for (const [selector, label] of selectors) for (const el of document.querySelectorAll(selector)) {
      if (!visible(el)) continue;
      const s = getComputedStyle(el); const c = parse(s.color); const b = effectiveBg(el); if (!c || !b) continue;
      const r = ratio(lum(c.rgb), lum(b.rgb)); const large = parseFloat(s.fontSize) >= 24 || (parseFloat(s.fontSize) >= 18.67 && parseInt(s.fontWeight) >= 700);
      items.push({ label, selector, color: s.color, background: `rgb(${b.rgb.join(", ")})`, ratio: Math.round(r * 100) / 100, required: large ? 3 : 4.5, pass: r >= (large ? 3 : 4.5) });
    }
    return { style, items, pass: items.length > 0 && items.every((item) => item.pass) };
  }, style);
  out.lpContrast = { styles: [] };
  for (const style of ["solid", "outline", "pill"]) {
    await lpPcPage.goto(BASE + LP + `?wt=lp_cta_style:${style}`, { waitUntil: "networkidle" }); await lpPcPage.waitForTimeout(350);
    out.lpContrast.styles.push(await lpContrast(lpPcPage, style));
  }
  out.lpContrast.pass = out.lpContrast.styles.length === 3 && out.lpContrast.styles.every((item) => item.pass);

  await lpPcPage.goto(BASE + LP + "?wt=lp_hero:fullbleed", { waitUntil: "networkidle" }); await lpPcPage.waitForTimeout(700);
  out.lpFullbleedContrast = await lpPcPage.evaluate(async () => {
    try {
      const hero = document.querySelector(".wt-lp-hero--fullbleed"); const img = hero?.querySelector("img");
      if (!hero || !img || !img.naturalWidth || !img.naturalHeight) return { missing: true, pass: false };
      const parse = (str) => { const m = str.match(/rgba?\(([^)]+)\)/); if (!m) return null; const c = m[1].split(",").map(Number); return { rgb: c.slice(0, 3), a: c[3] ?? 1 }; };
      const f = (v) => { v /= 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); };
      const lum = (rgb) => .2126 * f(rgb[0]) + .7152 * f(rgb[1]) + .0722 * f(rgb[2]);
      const ratio = (a, b) => (Math.max(a, b) + .05) / (Math.min(a, b) + .05);
      const er = hero.getBoundingClientRect(); const bgi = getComputedStyle(hero, "::before").backgroundImage;
      const stops = Array.from(bgi.matchAll(/rgba?\(([^)]+)\)\s*([\d.]+)%/g)).map((m) => { const c = m[1].split(",").map(Number); return { rgb: c.slice(0, 3), a: c[3] ?? 1, pos: parseFloat(m[2]) / 100 }; });
      if (!stops.length) return { missing: true, gradient: bgi, pass: false };
      const fracFromBottom = (y) => (er.bottom - y) / er.height;
      const alphaAt = (v) => { if (v <= stops[0].pos) return stops[0].a; for (let i = 1; i < stops.length; i++) if (v <= stops[i].pos) { const a = stops[i - 1], b = stops[i]; return a.a + (b.a - a.a) * ((v - a.pos) / (b.pos - a.pos)); } return stops[stops.length - 1].a; };
      const cv = document.createElement("canvas"); cv.width = img.naturalWidth; cv.height = img.naturalHeight; const cx = cv.getContext("2d"); cx.drawImage(img, 0, 0);
      const scale = Math.max(er.width / img.naturalWidth, er.height / img.naturalHeight); const dw = img.naturalWidth * scale; const dh = img.naturalHeight * scale; const ox = er.left + (er.width - dw) / 2; const oy = er.top + (er.height - dh) / 2;
      const sample = (target) => {
        const tr = target.getBoundingClientRect(); const sx = Math.max(0, Math.min(img.naturalWidth - 1, (tr.left - ox) / scale)); const sy = Math.max(0, Math.min(img.naturalHeight - 1, (tr.top - oy) / scale)); const sw = Math.max(1, Math.min(img.naturalWidth - sx, tr.width / scale)); const sh = Math.max(1, Math.min(img.naturalHeight - sy, tr.height / scale));
        const d = cx.getImageData(Math.floor(sx), Math.floor(sy), Math.max(1, Math.floor(sw)), Math.max(1, Math.floor(sh))).data; let sum = 0; let n = 0; let max = 0;
        for (let i = 0; i < d.length; i += 4) { const l = lum([d[i], d[i + 1], d[i + 2]]); sum += l; n++; max = Math.max(max, l); }
        return { mean: sum / n, max };
      };
      const measure = (target, label) => {
        const tr = target.getBoundingClientRect(); const ts = getComputedStyle(target); const tc = parse(ts.color); const alpha = Math.min(alphaAt(fracFromBottom(tr.top)), alphaAt(fracFromBottom(tr.bottom))); const source = sample(target); const scrimLum = lum(stops[0].rgb); const composite = source.mean * (1 - alpha) + scrimLum * alpha; const worst = source.max * (1 - alpha) + scrimLum * alpha; const textLum = lum(tc.rgb); const large = parseFloat(ts.fontSize) >= 24 || (parseFloat(ts.fontSize) >= 18.67 && parseInt(ts.fontWeight) >= 700); const r = ratio(textLum, composite);
        return { label, textColor: ts.color, alphaAtText: Math.round(alpha * 1000) / 1000, imageLAtText: Math.round(source.mean * 1000) / 1000, imageLMaxAtText: Math.round(source.max * 1000) / 1000, compositeL: Math.round(composite * 1000) / 1000, ratioText: Math.round(r * 100) / 100, ratioWorstPixel: Math.round(ratio(textLum, worst) * 100) / 100, required: large ? 3 : 4.5, pass: r >= (large ? 3 : 4.5) };
      };
      const targets = [[hero.querySelector("h1"), "h1"], [hero.querySelector(".wt-lp-hero__lead"), "lead"]].filter(([el]) => el);
      const items = targets.map(([el, label]) => measure(el, label));
      return { lum: hero.getAttribute("data-wt-lum"), sampledL: parseFloat(hero.getAttribute("data-wt-lum-value")), gradient: bgi.slice(0, 160), approximation: "段1と同じ canvas 輝度標本化 + linear-gradient の線形補間による概算", items, pass: items.length === 2 && items.every((item) => item.pass) };
    } catch (error) { return { error: String(error), pass: false }; }
  });

  const formCtx = await browser.newContext({ ...SP, javaScriptEnabled: false }); const formPage = await formCtx.newPage();
  await formPage.goto(BASE + LP + "?wt=lp_hero_cta:form-inline", { waitUntil: "load" });
  out.lpFormNoJs = await formPage.evaluate(() => {
    const visible = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none" && s.visibility !== "hidden"; };
    const hero = Array.from(document.querySelectorAll(".wt-lp-hero")).find(visible); const form = hero?.querySelector("form.wt-lp-cta-form"); const input = form?.querySelector("input[name=email]"); const label = input ? form.querySelector(`label[for="${CSS.escape(input.id)}"]`) : null; const method = form?.getAttribute("method")?.toLowerCase() || ""; const action = form?.getAttribute("action") || "";
    return { hero: hero?.className || null, method, action, inputId: input?.id || null, labelFor: label?.getAttribute("for") || null, pass: !!form && ["get", "post"].includes(method) && action.trim().length > 0 && !!input?.id && !!label && label.getAttribute("for") === input.id };
  });
  await formCtx.close();

  await lpSpPage.goto(BASE + LP + "?wt=lp_header:none", { waitUntil: "networkidle" }); await lpSpPage.waitForTimeout(350);
  out.lpAnchorNav = await lpSpPage.evaluate(() => {
    const nav = document.querySelector(".wt-lp-anchor-nav"); if (!nav) return { links: [], pass: false, missing: true };
    const visible = (el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none" && s.visibility !== "hidden"; };
    const links = Array.from(nav.querySelectorAll("a[href^='#']")).map((a) => { const targetId = a.getAttribute("href").slice(1); const target = document.getElementById(targetId); return { href: a.getAttribute("href"), targetId, targetExists: !!target, visible: visible(a) }; });
    return { links, targets: links.map((item) => item.targetId), pass: links.length > 0 && links.every((item) => item.targetExists && item.visible) };
  });

  out.lpSections = { variants: [] };
  const expectedSections = {
    full: ["numbers", "features", "steps", "logos", "testimonials", "pricing", "comparison", "faq", "badges", "cta-band--one", "cta-band--two", "cta-band--three"],
    short: ["features", "pricing", "faq", "cta-band--three"],
    trust: ["logos", "numbers", "testimonials", "badges", "cta-band--three"],
    // WT-EVT-0268: 全区間 + LP パーツ 7 種（順序は order 値）
    extended: ["numbers", "features", "steps", "logos", "interview", "testimonials", "review", "pricing", "comparison", "faq", "download", "badges", "rating", "cta-band--one", "cta-band--two", "form", "line", "cta-band--three"],
  };
  for (const variant of Object.keys(expectedSections)) {
    await lpPcPage.goto(BASE + LP + `?wt=lp_sections:${variant}`, { waitUntil: "networkidle" }); await lpPcPage.waitForTimeout(300);
    const result = await lpPcPage.evaluate(() => {
      const visible = (el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none" && s.visibility !== "hidden"; };
      const key = (el) => {
        const section = ["numbers", "features", "steps", "logos", "interview", "testimonials", "review", "pricing", "comparison", "faq", "download", "badges", "rating", "form", "line"].find((name) => el.classList.contains(`wt-lp__section--${name}`));
        const band = ["one", "two", "three"].find((name) => el.classList.contains(`wt-lp-cta-band--${name}`));
        return section || (band ? `cta-band--${band}` : undefined);
      };
      return Array.from(document.querySelectorAll(".wt-lp__sections > .wt-lp__section")).filter(visible).map((el) => ({ name: key(el), top: el.getBoundingClientRect().top })).filter((item) => item.name).sort((a, b) => a.top - b.top).map((item) => item.name);
    });
    out.lpSections.variants.push({ variant, visible: result, expected: expectedSections[variant], pass: JSON.stringify(result) === JSON.stringify(expectedSections[variant]) });
  }
  out.lpSections.pass = out.lpSections.variants.length === 4 && out.lpSections.variants.every((item) => item.pass);

  const fixedAudit = async (page, dev, variant) => {
    await page.goto(BASE + LP + `?wt=lp_fixed:${variant},footer_totop:button,share:float`, { waitUntil: "networkidle" }); await page.evaluate(() => scrollTo(0, document.body.scrollHeight)); await page.waitForTimeout(450);
    return page.evaluate(({ dev, variant }) => {
      const visible = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none" && s.visibility !== "hidden"; };
      const rect = (el) => { const r = el.getBoundingClientRect(); return { x: Math.round(r.left), y: Math.round(r.top), w: Math.round(r.width), h: Math.round(r.height) }; };
      const overlap = (a, b) => a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y;
      const fixed = variant === "none" ? null : document.querySelector(`.wt-lp-fixed--${variant}`); const share = document.querySelector(".wt-share--float"); const top = document.querySelector(".wt-totop");
      const fixedVisible = visible(fixed); const expectedFixedVisible = variant !== "none" && !(variant === "sp-bottom-bar" && dev === "pc"); const items = [["fixed", fixed], ["share", share], ["totop", top]].filter(([, el]) => visible(el)).map(([name, el]) => ({ name, el, rect: rect(el) }));
      const intersections = []; for (let i = 0; i < items.length; i++) for (let j = i + 1; j < items.length; j++) if (overlap(items[i].rect, items[j].rect)) intersections.push([items[i].name, items[j].name]);
      const inViewport = (r) => r.x >= 0 && r.y >= 0 && r.x + r.w <= innerWidth && r.y + r.h <= innerHeight;
      const reach = (el) => { if (!visible(el)) return true; const r = el.getBoundingClientRect(); const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2); return !!hit && (hit === el || el.contains(hit)); };
      const clickable = items.flatMap(({ el }) => el.matches("a,button") ? [el] : Array.from(el.querySelectorAll("a,button"))).map(reach);
      return { dev, variant, fixedVisible, expectedFixedVisible, items: items.map(({ name, rect }) => ({ name, ...rect })), intersections, clickable, inViewport: items.every((item) => inViewport(item.rect)), pass: fixedVisible === expectedFixedVisible && intersections.length === 0 && clickable.every(Boolean) && items.every((item) => inViewport(item.rect)) };
    }, { dev, variant });
  };
  out.lpFixedOverlap = { sp: [], pc: [] };
  for (const variant of ["none", "sp-bottom-bar", "float-cta"]) out.lpFixedOverlap.sp.push(await fixedAudit(lpSpPage, "sp", variant));
  for (const variant of ["none", "sp-bottom-bar", "float-cta"]) out.lpFixedOverlap.pc.push(await fixedAudit(lpPcPage, "pc", variant));
  out.lpFixedOverlap.sp.pass = out.lpFixedOverlap.sp.every((item) => item.pass);
  out.lpFixedOverlap.pc.pass = out.lpFixedOverlap.pc.every((item) => item.pass);

  const reduceCtx = await browser.newContext({ ...SP, reducedMotion: "reduce" }); const reducePage = await reduceCtx.newPage();
  await reducePage.goto(BASE + LP + "?wt=motion:on,lp_fixed:float-cta", { waitUntil: "networkidle" }); await reducePage.waitForTimeout(400);
  out.lpReducedMotion = await reducePage.evaluate(() => {
    const action = document.querySelector(".wt-lp-cta-action"); const section = document.querySelector(".wt-lp__section--features"); const hidden = Array.from(document.querySelectorAll(".wt-lp .wt-reveal")).filter((el) => parseFloat(getComputedStyle(el).opacity) < 1).length; const noTransition = (el) => { if (!el) return false; const s = getComputedStyle(el); return s.transitionProperty === "none" || s.transitionDuration === "0s"; };
    return { revealHidden: hidden, actionTransition: action ? getComputedStyle(action).transitionProperty : null, sectionTransition: section ? getComputedStyle(section).transitionProperty : null, pass: hidden === 0 && noTransition(action) && noTransition(section) };
  });
  await reduceCtx.close();

  out.lpLcpHero = { variants: [] };
  for (const variant of ["split", "fullbleed", "product", "text-only"]) {
    await lpPcPage.goto(BASE + LP + `?wt=lp_hero:${variant}`, { waitUntil: "networkidle" }); await lpPcPage.waitForTimeout(400);
    out.lpLcpHero.variants.push(await lpPcPage.evaluate((variant) => {
      const visible = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none" && s.visibility !== "hidden"; };
      const hero = document.querySelector(`.wt-lp-hero--${variant}`); const img = hero?.querySelector("img"); const imageExpected = variant !== "text-only"; const attrs = img ? { fetchpriority: img.getAttribute("fetchpriority"), loading: img.getAttribute("loading"), width: img.getAttribute("width"), height: img.getAttribute("height"), complete: img.complete, naturalWidth: img.naturalWidth } : null;
      return { variant, heroVisible: visible(hero), imageExpected, attrs, pass: visible(hero) && (imageExpected ? !!img && attrs.fetchpriority === "high" && Number(attrs.width) > 0 && Number(attrs.height) > 0 : !img) };
    }, variant));
  }
  out.lpLcpHero.pass = out.lpLcpHero.variants.length === 4 && out.lpLcpHero.variants.every((item) => item.pass);

  // LP 面判定（is_page_template）の修正確認: theme_mod 未設定時、LP 面だけ footer_layout の既定が single-row になり、非 LP 面（記事）は sitemap のまま。
  await lpPcPage.goto(BASE + LP, { waitUntil: "networkidle" });
  const lpBody = await lpPcPage.evaluate(() => document.body.className);
  await lpPcPage.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  const articleBody = await lpPcPage.evaluate(() => document.body.className);
  out.lpFooterFaceDefault = {
    lpHasFace: lpBody.split(/\s+/).includes("wt-face-lp"),
    lpHasSingleRow: lpBody.split(/\s+/).includes("wt-footer-layout-single-row"),
    articleHasFace: articleBody.split(/\s+/).includes("wt-face-lp"),
    articleHasSingleRow: articleBody.split(/\s+/).includes("wt-footer-layout-single-row"),
    articleHasSitemap: articleBody.split(/\s+/).includes("wt-footer-layout-sitemap"),
  };
  out.lpFooterFaceDefault.pass = out.lpFooterFaceDefault.lpHasFace && out.lpFooterFaceDefault.lpHasSingleRow && !out.lpFooterFaceDefault.articleHasFace && !out.lpFooterFaceDefault.articleHasSingleRow && out.lpFooterFaceDefault.articleHasSitemap;

  // double CTA の副ボタン: short / trust で比較セクションが非表示になる組み合わせでも、表示中の全アンカー（hero CTA を含む）の遷移先が存在し可視であること。
  // 構成ごとの期待遷移先（副 CTA は full → 比較表 / short → 料金 / trust → 声、のはずが消失していないかを検査する）
  const expectedSecondaryHref = { full: "#comparison", short: "#pricing", trust: "#voices", extended: "#comparison" };
  out.lpVisibleAnchors = { variants: [] };
  for (const variant of ["full", "short", "trust", "extended"]) {
    await lpPcPage.goto(BASE + LP + `?wt=lp_hero_cta:double,lp_sections:${variant}`, { waitUntil: "networkidle" }); await lpPcPage.waitForTimeout(300);
    const result = await lpPcPage.evaluate((expectedHref) => {
      const visible = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none" && s.visibility !== "hidden"; };
      // href="#"（フラグメント空）は共有アイコン等の placeholder リンクで、ページ内遷移を意図しないため対象外。実際にセクションを指すリンクだけを検査する。
      const links = Array.from(document.querySelectorAll("a[href^='#']")).filter((a) => a.getAttribute("href") !== "#").filter(visible).map((a) => {
        const targetId = a.getAttribute("href").slice(1);
        const target = document.getElementById(targetId);
        return { href: a.getAttribute("href"), targetId, targetExists: !!target, targetVisible: visible(target) };
      });
      // double CTA の副ボタン自体が消失していないか（可視本数・href）を直接検査する。可視リンク全体の存在チェックだけでは、
      // 副ボタンを除いた集合でも他のリンクが揃っていれば合格してしまうため、ここを分けて明示的に assert する。
      const secondaryButtons = Array.from(document.querySelectorAll(".wt-lp-cta-action--secondary[data-lp-cta-target]")).filter(visible).map((a) => a.getAttribute("href"));
      const secondaryPass = secondaryButtons.length === 1 && secondaryButtons[0] === expectedHref;
      return { links, secondaryButtons, expectedHref, secondaryPass, pass: links.length > 0 && links.every((l) => l.targetExists && l.targetVisible) && secondaryPass };
    }, expectedSecondaryHref[variant]);
    out.lpVisibleAnchors.variants.push({ variant, ...result });
  }
  out.lpVisibleAnchors.pass = out.lpVisibleAnchors.variants.length === 4 && out.lpVisibleAnchors.variants.every((item) => item.pass);

  await lpSpCtx.close(); await lpPcCtx.close();

  // LP 面限定の to-top 配置: 非 LP 面（記事）で lp_fixed:sp-bottom-bar を指定しても .wt-totop の bottom が既定（16px = 1rem、
  // theme.css:631 の .wt-totop{bottom:1rem}）から変わらないことを、SP・share:topbottom（float ではない）固定で確認する。
  {
    const totopCtx = await browser.newContext(SP); const totopPage = await totopCtx.newPage();
    await totopPage.goto(BASE + ARTICLE + "?wt=footer_totop:button,share:topbottom", { waitUntil: "networkidle" });
    const baseline = await totopPage.evaluate(() => { const el = document.querySelector(".wt-totop"); return el ? getComputedStyle(el).bottom : null; });
    await totopPage.goto(BASE + ARTICLE + "?wt=footer_totop:button,share:topbottom,lp_fixed:sp-bottom-bar", { waitUntil: "networkidle" });
    const withLpFixed = await totopPage.evaluate(() => { const el = document.querySelector(".wt-totop"); return el ? getComputedStyle(el).bottom : null; });
    const expected = "16px";
    out.lpFaceScopedTotop = { baseline, withLpFixed, expected, pass: baseline === expected && withLpFixed === expected };
    await totopCtx.close();
  }
}

// 11. 2026-09-05 PO 反応（比較表 SP caption・価格文字サイズ・ヘッダー内側幅・CTA 中央寄せ是正）
{
  // 11a. caption が 1 行以上の横書きで表示される（高さ/幅比で「1 文字ずつ縦積み」を検知）
  const spCtx = await browser.newContext(SP); const spPage = await spCtx.newPage();
  await spPage.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  out.tableCaptionSp = await spPage.evaluate(() => {
    const cap = document.querySelector(".is-style-wt-compare caption");
    if (!cap) return { exists: false };
    const r = cap.getBoundingClientRect();
    const s = getComputedStyle(cap);
    return { exists: true, width: r.width, height: r.height, ratio: r.height > 0 ? r.width / r.height : 0, display: s.display, text: cap.textContent.trim() };
  });
  out.tableCaptionSp.pass = out.tableCaptionSp.exists && out.tableCaptionSp.ratio > 3; // 横書き1〜数行なら幅は高さの数倍以上になる。縦積みだと概ね 1 未満
  await spCtx.close();

  // 11b. 表内数値セル（td.wt-num）の font-size が本文（p）の ±10% 以内（数字訴求 hero 用サイズが紛れ込んでいないか）
  const pcCtx = await browser.newContext(PC); const pcPage = await pcCtx.newPage();
  await pcPage.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  out.tableNumFontSize = await pcPage.evaluate(() => {
    const bodyFs = parseFloat(getComputedStyle(document.body).fontSize); // theme.json 本文 17px（preset font-size m）を基準にする。
    const cells = Array.from(document.querySelectorAll(".is-style-wt-compare td.wt-num"));
    const sizes = cells.map((c) => parseFloat(getComputedStyle(c).fontSize));
    return { bodyFs, cellCount: cells.length, sizes, maxDeviation: cells.length ? Math.max(...sizes.map((s) => Math.abs(s - bodyFs) / bodyFs)) : null };
  });
  out.tableNumFontSize.pass = out.tableNumFontSize.cellCount > 0 && out.tableNumFontSize.maxDeviation !== null && out.tableNumFontSize.maxDeviation <= 0.10;

  // 11c. ヘッダー内側幅 = min(viewport − 2×gutter, --wt-header-max)（header:cta 変種、幅プリセット既定）
  await pcPage.goto(BASE + ARTICLE + "?wt=header:cta", { waitUntil: "networkidle" });
  out.headerInnerWidth = await pcPage.evaluate(() => {
    const row = document.querySelector(".wt-header__row");
    const outer = row.closest(".wt-header");
    const r = row.getBoundingClientRect();
    const headerMax = parseFloat(getComputedStyle(document.body).getPropertyValue("--wt-header-max"));
    const gutter = parseFloat(getComputedStyle(outer).paddingLeft); // constrained layout の実際の padding-inline（gutter clamp の実測値）
    const viewport = window.innerWidth;
    const expectedRowWidth = Math.min(viewport, headerMax) - gutter * 2;
    return { rowWidth: r.width, headerMax, gutter, viewport, expectedRowWidth, withinTolerance: Math.abs(r.width - expectedRowWidth) <= 4 };
  });
  out.headerInnerWidth.pass = out.headerInnerWidth.withinTolerance;

  // 11d. CTA ボタンの中心 x が viewport 中央より右（符号付き）に 25% 以上ずれている（右寄せで、真ん中に来ていないこと）。
  // 2026-09-05 Astra レビュー是正: 絶対値だと左寄せでも合格してしまうため、符号を見る（cx > center * 1.25）。
  out.headerCtaOffCenter = await pcPage.evaluate(() => {
    const btn = document.querySelector(".wt-header__cta .wp-block-button__link");
    const r = btn.getBoundingClientRect();
    const cx = r.left + r.width / 2;
    const viewport = window.innerWidth;
    const viewportCenter = viewport / 2;
    const offsetSigned = (cx - viewportCenter) / viewportCenter; // 正 = 右、負 = 左
    return { cx, viewportCenter, viewport, offsetSigned };
  });
  out.headerCtaOffCenter.pass = out.headerCtaOffCenter.offsetSigned >= 0.25;

  await pcCtx.close();
}

// 12. 2026-09-05 PO 反応2・3・6回目（h3 番号前置・下線系見出し・PR タグの縦積み是正）
// Astra レビュー是正（head 85ae634 指摘）: 見出し全体の高さだけを見る検査は「番号自身は縦積みだが
// 本文側が短くて全体高さが閾値内」というケースを見逃す。white-space:nowrap・flex-shrink:0 という
// 「縦積みを構造的に禁止する CSS 宣言そのもの」を検査対象にし、幾何計測は補助情報として残す。
{
  const ctx = await browser.newContext(PC); const page = await ctx.newPage();
  await page.goto(BASE + ARTICLE, { waitUntil: "networkidle" });

  // 12a. h3 番号前置（is-style-wt-num）: (1) ::before 自身が white-space:nowrap かつ flex-shrink:0
  //      で「縮んで折り返す」ことが構造的に起きない（2) 番号の font-size が h3 テキスト本体より大きい
  const readHeadingNumber = async (p) => p.evaluate(() => {
    const h3 = document.querySelector(".wp-block-heading.is-style-wt-num");
    if (!h3) return { exists: false };
    const s = getComputedStyle(h3, "::before");
    const numFs = parseFloat(s.fontSize);
    const textFs = parseFloat(getComputedStyle(h3).fontSize);
    const r = h3.getBoundingClientRect();
    const lineHeight = parseFloat(getComputedStyle(h3).lineHeight) || textFs * 1.4;
    return {
      exists: true, numFs, textFs, headingHeight: r.height, lineHeight,
      whiteSpace: s.whiteSpace, flexShrink: s.flexShrink,
      structurallyNotStackable: s.whiteSpace === "nowrap" && parseFloat(s.flexShrink) === 0,
      biggerThanText: numFs > textFs,
    };
  });
  out.headingNumberPc = await readHeadingNumber(page);
  out.headingNumberPc.pass = out.headingNumberPc.exists && out.headingNumberPc.structurallyNotStackable && out.headingNumberPc.biggerThanText;
  const spCtxHn = await browser.newContext(SP); const spPageHn = await spCtxHn.newPage();
  await spPageHn.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  out.headingNumberSp = await readHeadingNumber(spPageHn);
  out.headingNumberSp.pass = out.headingNumberSp.exists && out.headingNumberSp.structurallyNotStackable && out.headingNumberSp.biggerThanText;
  await spCtxHn.close();

  // 12b. 下線系見出し（2tone/underline/dotted/underline-thin）の「実テキスト下端」〜「border-bottom の描画開始位置」の
  // 実距離が 4〜8px。padding-bottom の値そのものではなく、Range で実テキスト（疑似要素を含まない）の
  // bounding rect を取り、要素外枠の下端 − border 幅 と比較する。
  const readUnderlineGap = async (p) => p.evaluate(() => {
    const sels = [".is-style-wt-2tone", ".is-style-wt-underline", ".is-style-wt-dotted", ".is-style-wt-underline-thin"];
    return sels.map((sel) => {
      const el = document.querySelector(sel);
      if (!el) return { sel, exists: false };
      const range = document.createRange();
      range.selectNodeContents(el);
      const textRect = range.getBoundingClientRect();
      const elRect = el.getBoundingClientRect();
      const bw = parseFloat(getComputedStyle(el).borderBottomWidth) || 0;
      const lineY = elRect.bottom - bw;
      const gap = lineY - textRect.bottom;
      return { sel, exists: true, gap, pass: gap >= 4 && gap <= 8 };
    });
  });
  await page.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" });
  out.underlineGap = await readUnderlineGap(page);
  out.underlineGap.pass = out.underlineGap.length > 0 && out.underlineGap.every((x) => x.exists && x.pass);

  // 12c. PR タグ（.wt-pr__tag）が縦積みでない（幅 ≥ 高さ、= 横長）。SP でも同様に検査する。
  const readPrTag = async (p) => p.evaluate(() => {
    const tag = document.querySelector(".wt-pr__tag");
    if (!tag) return { exists: false };
    const r = tag.getBoundingClientRect();
    return { exists: true, width: r.width, height: r.height, ratio: r.height > 0 ? r.width / r.height : 0 };
  });
  await page.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  out.prTagNotStackedPc = await readPrTag(page);
  out.prTagNotStackedPc.pass = out.prTagNotStackedPc.exists && out.prTagNotStackedPc.ratio >= 1;
  const spCtxPr = await browser.newContext(SP); const spPagePr = await spCtxPr.newPage();
  await spPagePr.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  out.prTagNotStackedSp = await readPrTag(spPagePr);
  out.prTagNotStackedSp.pass = out.prTagNotStackedSp.exists && out.prTagNotStackedSp.ratio >= 1;
  await spCtxPr.close();

  // 12d. 目次 float（.wt-toc--float）の left が画面左へ欠けない（≥ 0）。1200/1280/1440 の3幅 × 幅プリセット3種で検査。
  // 2026-09-05 Astra レビュー是正: サイドカラム幅を 240→280px に上げた際、1200px 幅では
  // left = 600 − 340 − 16 − 280 = −36px と負値になっていた（theme.css で max(8px, …) に変更済み）。
  out.tocFloatLeft = [];
  for (const width of [1200, 1280, 1440]) {
    const tctx = await browser.newContext({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
    for (const preset of ["narrow", "default", "wide"]) {
      const tp = await tctx.newPage();
      await tp.goto(BASE + ARTICLE + "?wt=toc:float,width:" + preset, { waitUntil: "networkidle" });
      await tp.waitForTimeout(200);
      const left = await tp.evaluate(() => {
        const el = document.querySelector(".wt-toc--float");
        return el ? el.getBoundingClientRect().left : null;
      });
      out.tocFloatLeft.push({ width, preset, left, pass: left !== null && left >= 0 });
      await tp.close();
    }
    await tctx.close();
  }
  out.tocFloatLeft.pass = out.tocFloatLeft.length > 0 && out.tocFloatLeft.every((x) => x.pass);

  // 12e. PC の比較表: caption が表の上（thead より上）にあること（caption の y < thead の y）。
  // 2026-09-05 Astra レビュー是正: caption の display:block を SP カード表示専用の
  // max-width:599px 内へ移設したため、PC では table 書式のまま（caption-side:top の既定挙動）に戻る。
  await page.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  out.tableCaptionPcPosition = await page.evaluate(() => {
    const cap = document.querySelector(".is-style-wt-compare caption");
    const thead = document.querySelector(".is-style-wt-compare thead");
    if (!cap || !thead) return { exists: false };
    const capY = cap.getBoundingClientRect().top;
    const theadY = thead.getBoundingClientRect().top;
    return { exists: true, capY, theadY, aboveThead: capY < theadY };
  });
  out.tableCaptionPcPosition.pass = out.tableCaptionPcPosition.exists && out.tableCaptionPcPosition.aboveThead;

  await ctx.close();
}

// 13. 2026-09-05 Astra レビュー是正: pr:auto の陽性/陰性/境界フィクスチャ（重大指摘）。
// 先頭200字の単純部分一致だと「広告のない製品」「PROモデル」に誤検出し、201字目以降は見逃す問題への対応として、
// functions.php の wt_content_has_pr_disclosure() を文単位の共起判定へ書き換えた。ここでは実機（wp-cli で
// 一時記事を作成）で 8 フィクスチャ（陽性3・陰性3・境界2）を検証する。WPCLIDIR 未指定時はスキップする。
if (WPCLIDIR) {
  const fixtures = [
    { name: "pos-1", content: "<!-- wp:paragraph --><p>本記事にはアフィリエイト広告を含みます。</p><!-- /wp:paragraph -->", expectAuto: false, note: "定型の開示文（既存デフォルト文と同種）" },
    { name: "pos-2", content: "<!-- wp:paragraph --><p>この記事はPRを含みます。</p><!-- /wp:paragraph -->", expectAuto: false, note: "「PR」+「含みます」共起" },
    { name: "pos-3", content: "<!-- wp:paragraph --><p>本記事は企業からのプロモーションを含みます。</p><!-- /wp:paragraph -->", expectAuto: false, note: "「プロモーション」+「含みます」共起" },
    { name: "neg-1", content: "<!-- wp:paragraph --><p>広告のない製品を比較します。</p><!-- /wp:paragraph -->", expectAuto: true, note: "話題語のみ・開示述語なし（誤検出の代表例）" },
    { name: "neg-2", content: "<!-- wp:paragraph --><p>PROモデルを紹介します。</p><!-- /wp:paragraph -->", expectAuto: true, note: "「PRO」の部分一致除外（誤検出の代表例）" },
    { name: "neg-3", content: "<!-- wp:paragraph --><p>今日は天気がいいですね。デスクの話をします。</p><!-- /wp:paragraph -->", expectAuto: true, note: "無関係な本文（対照）" },
    { name: "neg-4", content: "<!-- wp:paragraph --><p>広告のない製品を掲載しています。</p><!-- /wp:paragraph -->", expectAuto: true, note: "否定文（「ない」+「掲載」の共起を誤って開示扱いしていた重大指摘の再現例）" },
    { name: "neg-5", content: "<!-- wp:paragraph --><p>本記事には広告を含みません。</p><!-- /wp:paragraph -->", expectAuto: true, note: "否定文（「含みません」を「含み」の部分一致で誤検出していた重大指摘の再現例）" },
    { name: "boundary-201plus", content: "<!-- wp:paragraph --><p>" + "あ".repeat(250) + "</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>" + "い".repeat(250) + "</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>" + "う".repeat(250) + "</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>本記事にはアフィリエイト広告を含みます。</p><!-- /wp:paragraph -->", expectAuto: true, note: "開示文が4段落目・600字超（走査範囲外、既知の限界＝挿入されるのが正)" },
    { name: "boundary-heading", content: "<!-- wp:heading --><h2>本記事はPRを含みます</h2><!-- /wp:heading --><!-- wp:paragraph --><p>本文です。</p><!-- /wp:paragraph -->", expectAuto: true, note: "見出し内のみの記述（段落を走査対象にするため対象外＝挿入されるのが正)" },
    { name: "pos-4-long-250", content: "<!-- wp:paragraph --><p>" + "この記事は在宅ワーク向けの電動昇降デスクを実機で比較したものです。".repeat(8) + "</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>本記事にはアフィリエイト広告を含みます。評価・掲載順は報酬額で決めていません。</p><!-- /wp:paragraph -->", expectAuto: false, note: "開示文の開始位置が266字目（先頭段落がフィラー264字、開示は2段落目・3段落/600字の走査範囲内。旧200字固定長では検出できなかった位置）" },
    { name: "pos-5-long-400", content: "<!-- wp:paragraph --><p>" + "この記事は在宅ワーク向けの電動昇降デスクを実機で比較したものです。".repeat(8) + "</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>" + "同じ部屋・同じ期間で使い比べ、価格と昇降範囲を比較しました。".repeat(5) + "</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>本記事にはアフィリエイト広告を含みます。評価・掲載順は報酬額で決めていません。</p><!-- /wp:paragraph -->", expectAuto: false, note: "開示文の開始位置が417字目（フィラー2段落・3段落/600字の走査範囲内、旧200字固定長では検出できなかった位置）" },
  ];
  const wp = (cmdArgs) => execFileSync("docker", ["compose", "run", "--rm", "-T", "wpcli", ...cmdArgs], { cwd: WPCLIDIR, encoding: "utf8" });
  out.prAutoFixtures = { results: [] };
  const ids = [];
  try {
    for (const f of fixtures) {
      const id = wp(["post", "create", "--post_type=post", "--post_status=publish", "--post_author=1", "--post_title=verify-fixture-" + f.name, "--post_content=" + f.content, "--porcelain"]).trim();
      ids.push(id);
      const page2 = await browser.newPage();
      await page2.goto(BASE + "/?p=" + id, { waitUntil: "networkidle" });
      const prCount = await page2.evaluate(() => document.querySelectorAll(".is-style-wt-pr").length);
      await page2.close();
      const autoInserted = prCount > 0;
      out.prAutoFixtures.results.push({ name: f.name, note: f.note, expectAuto: f.expectAuto, autoInserted, pass: autoInserted === f.expectAuto });
    }
  } finally {
    for (const id of ids) { try { wp(["post", "delete", id, "--force"]); } catch (e) { /* 後片付け失敗は握りつぶさず出力に残す */ out.prAutoFixtures.cleanupError = String(e); } }
  }
  out.prAutoFixtures.pass = out.prAutoFixtures.results.length === fixtures.length && out.prAutoFixtures.results.every((r) => r.pass);
} else {
  out.prAutoFixtures = { skipped: true, reason: "WPCLIDIR 未指定（--wpclidir <docker-compose project dir> を渡すと実行）" };
  out.prAutoFixtures.pass = null; // 集計からは除外（下記 checkList に含めない）
}

// 14. 関連記事品質（PC / SP、既存4型の再設計 + featured-big+small / ranking-numbers）
{
  const variants = ["grid", "list", "rank", "carousel", "featured", "ranking-numbers", "slider"];
  out.relatedQuality = { variants: [] };
  for (const [dev, config] of [["sp", SP], ["pc", PC]]) {
    const ctx = await browser.newContext(config); const page = await ctx.newPage();
    for (const variant of variants) {
      await page.goto(BASE + ARTICLE + `?wt=related:${variant}`, { waitUntil: "networkidle" }); await page.waitForTimeout(350);
      const audit = await page.evaluate((variant) => {
        const cards = Array.from(document.querySelectorAll(".wt-related:not(.wt-next) .wt-rcard"));
        const cardData = cards.map((card) => {
          const r = card.getBoundingClientRect();
          const title = card.querySelector(".wt-rcard__title, .wp-block-post-title");
          const image = card.querySelector(".wp-block-post-featured-image img");
          const ts = title ? getComputedStyle(title) : null;
          const ir = image ? image.getBoundingClientRect() : null;
          const tr = title ? title.getBoundingClientRect() : null;
          const lh = ts ? parseFloat(ts.lineHeight) : null;
          // 近年の Chromium は -webkit-box + -webkit-line-clamp を CSS Overflow 4 の legacy line-clamp として扱い、computed display を flow-root と報告する。
          // display のキーワードではなく、実描画の高さが 2 行分（line-height×2、a の min-height 44px を許容）に収まることで判定する。
          return { top: r.top, height: r.height, titleClamp: ts ? ts.webkitLineClamp : null, titleDisplay: ts ? ts.display : null, titleHeight: tr ? tr.height : null, titleLineHeight: lh, titleLines: tr && lh ? Math.round(tr.height / lh * 10) / 10 : null, ratio: ir && ir.height ? ir.width / ir.height : null };
        });
        const comparable = variant === "featured" ? cardData.slice(1) : cardData;
        const rows = [];
        for (const card of comparable) {
          let row = rows.find((candidate) => Math.abs(candidate.top - card.top) <= 2);
          if (!row) { row = { top: card.top, heights: [] }; rows.push(row); }
          row.heights.push(card.height);
        }
        const rowDiffs = rows.map((row) => Math.max(...row.heights) - Math.min(...row.heights));
        const sameRow = comparable.length > 0 && rowDiffs.every((diff) => diff <= 2);
        const titleClamp = cardData.length > 0 && cardData.every((card) => card.titleClamp === "2" && card.titleHeight !== null && card.titleHeight <= Math.max(card.titleLineHeight * 2, 44) + 2);
        const thumbnailRatio = cardData.length > 0 && cardData.every((card) => card.ratio !== null && Math.abs(card.ratio - 16 / 9) / (16 / 9) <= .01);
        return { cardCount: cardData.length, rowDiffs, sameRow, titleClamp, thumbnailRatio, cards: cardData, pass: sameRow && titleClamp && thumbnailRatio };
      }, variant);
      out.relatedQuality.variants.push({ dev, variant, ...audit });
    }
    await ctx.close();
  }
  out.relatedQuality.pass = out.relatedQuality.variants.length === 14 && out.relatedQuality.variants.every((item) => item.pass);
}

// 9.5. detext:on が実記事の h2/ol/blockquote のうち少なくとも1要素で off と異なる computed style になっているか
// （PO 反応8回目 WT-EVT-0249: is-style-wt-* を使う実記事で detext:on が見た目に変化しない不具合の回帰防止）
{
  const readState = async (state) => {
    const ctx = await browser.newContext(SP);
    const p = await ctx.newPage();
    await p.goto(BASE + ARTICLE + `?wt=detext:${state}`, { waitUntil: "networkidle" });
    const data = await p.evaluate(() => {
      const h2 = document.querySelector(".wp-block-post-content>h2");
      const ol = document.querySelector(".wp-block-post-content>ol");
      const bq = document.querySelector(".wp-block-quote");
      const before = (el) => el ? getComputedStyle(el, "::before").content : null;
      return {
        h2Before: before(h2), h2Class: h2 ? h2.className : null,
        olListStyle: ol ? getComputedStyle(ol).listStyleType : null, olClass: ol ? ol.className : null,
        bqBefore: before(bq), bqClass: bq ? bq.className : null,
      };
    });
    await ctx.close();
    return data;
  };
  const off = await readState("off");
  const on = await readState("on");
  out.detextVisualDiff = { off, on };
  const elementsPresent = off.h2Class !== null && on.h2Class !== null && off.olClass !== null && on.olClass !== null && off.bqClass !== null && on.bqClass !== null;
  out.detextVisualDiff.elementsPresent = elementsPresent;
  out.detextVisualDiff.pass = elementsPresent && (off.h2Before !== on.h2Before || off.olListStyle !== on.olListStyle || off.bqBefore !== on.bqBefore);
}

// 9.6. CTA バナーのキャプションに PR 表記が残っていないか（PO 反応 13 回目 WT-EVT-0254「PRが残ってるけど？」。
// PR 表記は pr 軸（記事側）で出す方針のため、CTA パーツ自体の文言には持たせない）。catalog と実記事本文の両方を見る。
{
  const ctx = await browser.newContext(PC);
  const p = await ctx.newPage();
  const read = async (url, sel) => {
    await p.goto(BASE + url, { waitUntil: "networkidle" });
    return p.evaluate((sel) => Array.from(document.querySelectorAll(sel)).map((el) => el.textContent.trim()), sel);
  };
  const catalog = await read("/catalog-03/", "#cat-cta-banner figcaption");
  const article = await read(ARTICLE, ".wp-block-post-content .is-style-wt-banner figcaption");
  await ctx.close();
  const re = /^(PR|広告|アフィリエイト|【PR】|\[PR\])/;
  out.ctaBannerNoPrPrefix = { catalog, article, pass: catalog.length > 0 && article.length > 0 && [...catalog, ...article].every((t) => !re.test(t)) };
}

// 9.7. 商品カード束に PR バッジが残っていないか（PO 判断 WT-EVT-0255「不要」）。catalog と実記事本文の両方でカードの存在を必須にする。
{
  const ctx = await browser.newContext(PC); const p = await ctx.newPage();
  const read = async (url) => { await p.goto(BASE + url, { waitUntil: "networkidle" }); return p.evaluate(() => ({ cards: document.querySelectorAll(".is-style-wt-product").length, prBadges: document.querySelectorAll(".wt-badge--pr").length })); };
  const catalog = await read("/catalog-03/"); const article = await read(ARTICLE);
  await ctx.close();
  out.productCardNoPrBadge = { catalog, article, pass: catalog.cards > 0 && article.cards > 0 && catalog.prBadges === 0 && article.prBadges === 0 };
}

// 9.8. 囲み Q&A モーダル型（PO 指示 WT-EVT-0256）: JS 無効時は回答が本文内に見える。JS 有効時はボタンで <dialog> が開き回答が見え、
//      Esc で閉じてフォーカスがボタンへ戻る。ボタン・閉じるは SP で 44px 以上。reduced-motion で transition なし。
{
  const sel = "#cat-box-qa-modal";
  const noJsCtx = await browser.newContext({ ...SP, javaScriptEnabled: false }); const np = await noJsCtx.newPage();
  await np.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" });
  const noJs = await np.evaluate((sel) => { const box = document.querySelector(sel); const ps = box ? box.querySelectorAll(":scope > p") : []; const a = ps[1]; const r = a ? a.getBoundingClientRect() : null; return { box: !!box, paragraphs: ps.length, answerVisible: !!(r && r.height > 0 && getComputedStyle(a).visibility !== "hidden"), dialogs: box ? box.querySelectorAll("dialog").length : 0, buttons: box ? box.querySelectorAll("button").length : 0 }; }, sel);
  await noJsCtx.close();
  const ctx = await browser.newContext(SP); const p = await ctx.newPage();
  await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" }); await p.waitForTimeout(300);
  const before = await p.evaluate((sel) => { const box = document.querySelector(sel); const d = box.querySelector("dialog"); const b = box.querySelector(".wt-qa-modal__open"); const r = b.getBoundingClientRect(); return { answerInBody: box.querySelectorAll(":scope > p").length, dialogOpen: d ? d.open : null, openBtn: { w: r.width, h: r.height }, transition: getComputedStyle(b).transitionDuration }; }, sel);
  await p.locator(sel + " .wt-qa-modal__open").scrollIntoViewIfNeeded(); await p.locator(sel + " .wt-qa-modal__open").click(); await p.waitForTimeout(200);
  const opened = await p.evaluate((sel) => { const box = document.querySelector(sel); const d = box.querySelector("dialog"); const a = d.querySelector(".wt-qa-modal__body p"); const ar = a.getBoundingClientRect(); const as = getComputedStyle(a); const c = d.querySelector(".wt-qa-modal__close"); const cr = c.getBoundingClientRect(); const visibleChain = (el) => { for (let e = el; e; e = e.parentElement) { const cs = getComputedStyle(e); if (cs.display === "none" || cs.visibility === "hidden" || parseFloat(cs.opacity) === 0) return false; } return true; }; return { open: d.open, answerText: a.textContent.trim().slice(0, 20), answerVisible: ar.height > 0 && ar.top >= 0 && ar.bottom <= innerHeight && visibleChain(a) && as.fontSize !== "0px" && as.color !== as.backgroundColor, closeBtn: { w: cr.width, h: cr.height }, focusInDialog: d.contains(document.activeElement), labelledby: d.getAttribute("aria-labelledby") && !!document.getElementById(d.getAttribute("aria-labelledby")) }; }, sel);
  await p.keyboard.press("Escape"); await p.waitForTimeout(150);
  const closed = await p.evaluate((sel) => { const box = document.querySelector(sel); return { open: box.querySelector("dialog").open, focusOnOpenBtn: document.activeElement === box.querySelector(".wt-qa-modal__open") }; }, sel);
  await ctx.close();
  out.qaModal = { noJs, before, opened, closed };
  out.qaModal.pass = noJs.box && noJs.paragraphs >= 2 && noJs.answerVisible && noJs.dialogs === 0 && noJs.buttons === 0
    && before.answerInBody === 1 && before.dialogOpen === false && before.openBtn.h >= 44
    && opened.open === true && opened.answerVisible && opened.answerText.length > 0 && opened.closeBtn.w >= 44 && opened.closeBtn.h >= 44 && opened.focusInDialog && opened.labelledby === true
    && closed.open === false && closed.focusOnOpenBtn;
}

// 9.9. データグラフ 4 型（PO 反応 15 回目 WT-EVT-0257、Claude 案）: 型ごとに「視覚化の値」と「読み上げ用の表の値」の一致、aria-label に表の値が含まれること、
//      figcaption、系列色と面（base）/ トラック（surface）の非テキストコントラスト 3:1 以上、JS 無効時に同じ内容が可視で描かれることを見る（Astra 是正: 空配列の every() すり抜け・可視性未検査を塞ぐ）。
{
  const lum = (rgb) => { const [r, g, b] = rgb.match(/\d+/g).slice(0, 3).map((v) => { v = +v / 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }); return 0.2126 * r + 0.7152 * g + 0.0722 * b; };
  const ratio = (a, b) => { const la = lum(a), lb = lum(b); return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05); };
  const read = async (ctx) => {
    const p = await ctx.newPage(); await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" });
    const data = await p.evaluate(() => Array.from(document.querySelectorAll("figure.wt-graph")).map((f) => {
      const cs = getComputedStyle(f);
      const visibleChain = (el) => { for (let e = el; e; e = e.parentElement) { const s = getComputedStyle(e); if (s.display === "none" || s.visibility === "hidden" || parseFloat(s.opacity) === 0) return false; } return true; };
      const tableVals = Array.from(f.querySelectorAll("table.wt-graph__data tbody tr td")).map((td) => parseFloat(td.textContent));
      const tableLabels = Array.from(f.querySelectorAll("table.wt-graph__data tbody tr th")).map((th) => th.textContent.trim());
      const type = f.dataset.wtGraph;
      let visualVals = [], aria = null, marks = 0;
      if (type === "bar") { visualVals = Array.from(f.querySelectorAll(".wt-graph__row")).map((el) => parseFloat(getComputedStyle(el).getPropertyValue("--v"))); marks = f.querySelectorAll(".wt-graph__bar i").length; }
      if (type === "stack") { const segs = Array.from(f.querySelectorAll(".wt-graph__seg")); visualVals = segs.map((el) => parseFloat(getComputedStyle(el).getPropertyValue("--v"))); marks = segs.length; aria = (f.querySelector(".wt-graph__stack") || {}).getAttribute?.("aria-label") || null; }
      if (type === "donut") { const d = f.querySelector(".wt-graph__donut"); const a = parseFloat(getComputedStyle(d).getPropertyValue("--a")), b = parseFloat(getComputedStyle(d).getPropertyValue("--b")); visualVals = [a, b - a, 100 - b]; marks = f.querySelectorAll(".wt-graph__legend li").length; aria = d.getAttribute("aria-label"); }
      if (type === "line") {
        // 折れ線の y 座標を目盛り（.wt-graph__ticks text の y と数値）から線形に値へ戻し、表の値と突合する（Astra 是正: 点数だけの比較だった）
        const ticks = Array.from(f.querySelectorAll(".wt-graph__ticks text")).map((t) => ({ y: parseFloat(t.getAttribute("y")) - 4, v: parseFloat(t.textContent) })).filter((t) => !Number.isNaN(t.y) && !Number.isNaN(t.v));
        const t0 = ticks[0], t1 = ticks[ticks.length - 1];
        const toVal = (y) => (t0 && t1 && t1.y !== t0.y) ? t0.v + (y - t0.y) * (t1.v - t0.v) / (t1.y - t0.y) : NaN;
        const pts = ((f.querySelector(".wt-graph__line") || {}).getAttribute?.("points") || "").trim().split(/\s+/).filter(Boolean).map((pt) => pt.split(",").map(Number));
        const dots = Array.from(f.querySelectorAll(".wt-graph__dots circle")).map((c) => [parseFloat(c.getAttribute("cx")), parseFloat(c.getAttribute("cy"))]);
        visualVals = pts.map(([, y]) => Math.round(toVal(y) * 100) / 100);
        marks = dots.length;
        aria = f.querySelector(".wt-graph__svg").getAttribute("aria-label");
        // 点（circle）が折れ線の頂点と一致するかも保持
        f.__lineDotsMatch = dots.length === pts.length && dots.every(([x, y], i) => Math.abs(x - pts[i][0]) < 0.01 && Math.abs(y - pts[i][1]) < 0.01);
      }
      const seriesEls = Array.from(f.querySelectorAll(".wt-graph__bar i, .wt-graph__seg, .wt-graph__sw"));
      const series = [...new Set(seriesEls.map((el) => getComputedStyle(el).backgroundColor))];
      const svgLine = f.querySelector(".wt-graph__line"); if (svgLine) series.push(getComputedStyle(svgLine).stroke);
      const visualEl = f.querySelector(".wt-graph__rows, .wt-graph__stack, .wt-graph__donut, .wt-graph__svg");
      // 描画要素そのもの（棒の塗り・区分・ドーナツ・折れ線と点）が可視で大きさを持つか（Astra 是正: コンテナだけ見ていた）
      const drawn = Array.from(f.querySelectorAll(".wt-graph__bar i, .wt-graph__seg, .wt-graph__donut, .wt-graph__line, .wt-graph__dots circle"));
      const drawnVisible = drawn.length > 0 && drawn.every((el) => { const r = el.getBoundingClientRect(); return visibleChain(el) && r.width > 0 && r.height > 0; });
      return { type, caption: (f.querySelector("figcaption") || {}).textContent?.trim() || "", tableLabels, tableVals, visualVals, marks, aria, series, bg: cs.backgroundColor, track: f.querySelector(".wt-graph__bar, .wt-graph__stack") ? getComputedStyle(f.querySelector(".wt-graph__bar, .wt-graph__stack")).backgroundColor : null, width: f.getBoundingClientRect().width, visible: visibleChain(f) && !!visualEl && visibleChain(visualEl) && visualEl.getBoundingClientRect().height > 0 && drawnVisible, lineDotsMatch: type === "line" ? f.__lineDotsMatch === true : null, text: f.textContent.replace(/\s+/g, " ").trim() };
    }));
    await p.close(); return data;
  };
  const ctxJs = await browser.newContext(SP); const withJs = await read(ctxJs); await ctxJs.close();
  const ctxNoJs = await browser.newContext({ ...SP, javaScriptEnabled: false }); const noJs = await read(ctxNoJs); await ctxNoJs.close();
  const ctxPc = await browser.newContext(PC); const pc = await read(ctxPc); await ctxPc.close();
  const near = (a, b) => Math.abs(a - b) < 0.01;
  const valuesMatch = (g) => {
    if (!g.tableVals.length || g.tableVals.some((v) => Number.isNaN(v))) return false;
    if (g.type === "bar" || g.type === "stack") return g.visualVals.length === g.tableVals.length && g.visualVals.every((v, i) => near(v, g.tableVals[i]) && v >= 0 && v <= 100) && g.marks === g.tableVals.length && (g.type !== "stack" || near(g.visualVals.reduce((x, y) => x + y, 0), 100));
    if (g.type === "donut") return g.visualVals.length === 3 && g.tableVals.length === 3 && g.visualVals.every((v, i) => near(v, g.tableVals[i]) && v > 0) && g.marks === 3;
    if (g.type === "line") return g.visualVals.length === g.tableVals.length && g.tableVals.length >= 3 && g.visualVals.every((v, i) => Math.abs(v - g.tableVals[i]) <= 0.05) && g.marks === g.tableVals.length && g.lineDotsMatch === true;
    return false;
  };
  // aria-label は「<説明>: <ラベル> <値>[%]、<ラベル> <値>[%]、…」の形式とみなし、コロン以降を「、」で分割した各区分が
  // 表の各行（順序どおり）の「ラベル 値」に完全一致することを要求する（Astra 是正 3 巡目: 部分一致・数値列だけの照合・ラベル位置非依存をやめた）。
  const fmtVal = (v) => Number.isInteger(v) ? String(v) : String(v);
  const ariaOk = (g) => {
    if (g.type === "bar") return true;
    if (!g.aria || !g.tableLabels.length || g.tableLabels.length !== g.tableVals.length) return false;
    const m = g.aria.match(/^[^:：]+[:：]\s*(.+)$/);
    if (!m) return false;
    const parts = m[1].split(/、|,\s*/).map((t) => t.trim());
    if (parts.length !== g.tableLabels.length) return false;
    return parts.every((part, i) => {
      const pm = part.match(/^(.+?)\s+(\d+(?:\.\d+)?)(%?)$/);
      return !!pm && pm[1] === g.tableLabels[i] && Number(pm[2]) === g.tableVals[i] && fmtVal(g.tableVals[i]) === pm[2];
    });
  };
  const contrast = withJs.map((g) => g.series.map((c) => ({ color: c, vsBg: +ratio(c, g.bg).toFixed(2), vsTrack: g.track ? +ratio(c, g.track).toFixed(2) : null })));
  const typesOk = ["bar", "stack", "donut", "line"].every((t) => withJs.filter((g) => g.type === t).length === 1);
  const each = withJs.every((g) => g.caption.length > 0 && valuesMatch(g) && ariaOk(g) && g.width > 0 && g.visible);
  const contrastOk = contrast.every((rows) => rows.length > 0 && rows.every((r) => r.vsBg >= 3 && (r.vsTrack === null || r.vsTrack >= 3)));
  const key = (g) => JSON.stringify([g.type, g.tableLabels, g.tableVals, g.visualVals, g.marks, g.aria, g.caption, g.text, g.visible, g.lineDotsMatch]);
  const sameNoJs = noJs.length === withJs.length && noJs.every((g, i) => key(g) === key(withJs[i])) && noJs.every((g) => g.visible);
  const spNoOverflow = withJs.every((g) => g.width <= 390);
  out.graphs = { count: withJs.length, sp: withJs, pc: pc.map((g) => ({ type: g.type, width: g.width, visible: g.visible })), contrast, typesOk, each, contrastOk, sameNoJs, spNoOverflow };
  out.graphs.pass = withJs.length === 4 && typesOk && each && contrastOk && sameNoJs && spNoOverflow && pc.every((g) => g.visible);
}

// 9.10. detext metrics の SP 配置（PO 反応 15 回目 WT-EVT-0259）: JS 有効・無効の両方で、3 指標が可視のまま 1 行（同じ top）に並び、はみ出しがなく、数字が幅内に収まる。
{
  const read = async (ctx) => {
    const p = await ctx.newPage(); await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" });
    const m = await p.evaluate(() => { const box = document.querySelector("#cat-detext-metrics"); if (!box) return { count: 0 }; const visibleChain = (el) => { for (let e = el; e; e = e.parentElement) { const s = getComputedStyle(e); if (s.display === "none" || s.visibility === "hidden" || parseFloat(s.opacity) === 0) return false; } return true; }; const items = Array.from(box.querySelectorAll(".wt-detext__metric")); const br = box.getBoundingClientRect(); return { count: items.length, tops: items.map((el) => Math.round(el.getBoundingClientRect().top)), rights: items.map((el) => el.getBoundingClientRect().right), boxRight: br.right, boxWidth: br.width, numOverflow: items.filter((el) => el.querySelector(".wt-num").scrollWidth > el.clientWidth).length, visible: items.every((el) => visibleChain(el) && el.getBoundingClientRect().height > 0), texts: items.map((el) => el.textContent.replace(/\s+/g, " ").trim()) }; });
    await p.close(); return m;
  };
  const ctx = await browser.newContext(SP); const js = await read(ctx); await ctx.close();
  const ctxNo = await browser.newContext({ ...SP, javaScriptEnabled: false }); const noJs = await read(ctxNo); await ctxNo.close();
  const ok = (m) => m.count === 3 && new Set(m.tops).size === 1 && m.rights.every((r) => r <= m.boxRight + 1) && m.numOverflow === 0 && m.visible === true && m.texts.every((t) => t.length > 0);
  out.metricsSp = { js, noJs };
  out.metricsSp.pass = ok(js) && ok(noJs) && JSON.stringify(js.texts) === JSON.stringify(noJs.texts);
}

// 9.11. PO 反応 16 回目（WT-EVT-0261〜0266）
// (a) PR 表記の既定文言 = PO 決定「本記事にはプロモーションが含まれます。」（pr:on の実記事と catalog の両方）
{
  const ctx = await browser.newContext(PC); const p = await ctx.newPage();
  await p.goto(BASE + ARTICLE + "?wt=pr:on", { waitUntil: "networkidle" });
  const article = await p.evaluate(() => Array.from(document.querySelectorAll(".wt-pr.is-style-wt-pr")).map((el) => el.textContent.replace(/\s+/g, "").replace(/^PR/, "")));
  await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" });
  const catalog = await p.evaluate(() => Array.from(document.querySelectorAll("#cat-pr .wt-pr")).map((el) => el.textContent.replace(/\s+/g, "").replace(/^PR/, "")));
  await ctx.close();
  const want = "本記事にはプロモーションが含まれます。";
  out.prNoticeText = { article, catalog, pass: article.length >= 1 && catalog.length >= 1 && [...article, ...catalog].every((t) => t === want) };
}
// (b) related:slider — 1 画面 1 枚（PC 2 枚）の snap、ドット数 = ページ数、前後ボタン、自動送りなし（1.5 秒待っても scrollLeft 不変）、ドットは 44px、JS 無効でも横スクロールで全カード到達可
{
  const read = async (ctx, js) => {
    const p = await ctx.newPage(); await p.goto(BASE + ARTICLE + "?wt=related:slider", { waitUntil: "networkidle" }); await p.waitForTimeout(400);
    const before = await p.evaluate(() => { const t = document.querySelector(".wt-related:not(.wt-next) .wp-block-post-template"); return t.scrollLeft; });
    await p.waitForTimeout(1500);
    const d = await p.evaluate((before) => {
      const t = document.querySelector(".wt-related:not(.wt-next) .wp-block-post-template"); const cs = getComputedStyle(t);
      const items = Array.from(t.children); const w = t.clientWidth; const per = Math.max(1, Math.round(w / (items[0].getBoundingClientRect().width + 16)));
      const dots = Array.from(document.querySelectorAll(".wt-tail__slot--related .wt-slider__dots button"));
      return { items: items.length, per, pages: Math.ceil(items.length / per), snap: cs.scrollSnapType, overflow: cs.overflowX, dots: dots.length, dotSize: dots.map((b) => [b.getBoundingClientRect().width, b.getBoundingClientRect().height]), selected: dots.filter((b) => b.getAttribute("aria-current") === "true").length, nav: document.querySelectorAll(".wt-tail__slot--related .wt-carousel__nav > button[aria-label]").length, autoMoved: t.scrollLeft !== before, reachable: t.scrollWidth - t.clientWidth >= (items.length - per) * items[0].getBoundingClientRect().width * 0.9 };
    }, before);
    if (js) {
      // 「次へ」を押して実際に送られること（scrollLeft が増える）、ドットの current が移ることを確認（Astra 是正: ボタン数だけ数えていた）
      const nextBtn = p.locator(".wt-tail__slot--related .wt-carousel__nav > button[aria-label='次へ']");
      d.nextExists = (await nextBtn.count()) === 1;
      if (d.nextExists) { await nextBtn.click(); await p.waitForTimeout(700); }
      const after = await p.evaluate(() => { const t = document.querySelector(".wt-related:not(.wt-next) .wp-block-post-template"); const cur = Array.from(document.querySelectorAll(".wt-tail__slot--related .wt-slider__dots button")).findIndex((b) => b.getAttribute("aria-current") === "true"); return { scrollLeft: t.scrollLeft, current: cur }; });
      d.movedByNext = after.scrollLeft > before + 10; d.currentAfterNext = after.current;
      // リサイズでドット数が作り直されるか（PC 幅 → SP 幅）
      if (d.per === 2) { await p.setViewportSize({ width: 390, height: 844 }); await p.waitForTimeout(500); d.dotsAfterResize = await p.evaluate(() => document.querySelectorAll(".wt-tail__slot--related .wt-slider__dots button").length); }
    }
    await p.close(); return d;
  };
  const c1 = await browser.newContext(SP); const sp = await read(c1, true); await c1.close();
  const c2 = await browser.newContext(PC); const pc = await read(c2, true); await c2.close();
  const c3 = await browser.newContext({ ...SP, javaScriptEnabled: false }); const noJs = await read(c3, false); await c3.close();
  out.relatedSlider = { sp, pc, noJs };
  const okJs = (d, per) => d.items >= 2 && d.per === per && d.snap.startsWith("x") && d.overflow === "auto" && d.dots === d.pages && d.selected === 1 && d.dotSize.every(([w, h]) => w >= 44 && h >= 44) && d.nav === 2 && d.nextExists && d.movedByNext && d.currentAfterNext === 1 && !d.autoMoved && d.reachable;
  out.relatedSlider.pass = okJs(sp, 1) && okJs(pc, 2) && pc.dotsAfterResize === pc.items && noJs.dots === 0 && noJs.overflow === "auto" && noJs.reachable && !noJs.autoMoved;
}
// (c) 記事末尾 SNS 共有（tail_share:icons-row）— 3 サービス（Facebook は guard 例外の PO 判断待ちで未収録）、href に本記事の permalink（エンコード済み）を含む、別タブ + noopener、44px、JS 無効でも href が有効
{
  const read = async (ctx) => {
    const p = await ctx.newPage(); await p.goto(BASE + ARTICLE + "?wt=tail_share:icons-row", { waitUntil: "networkidle" });
    const d = await p.evaluate(() => { const enc = encodeURIComponent(location.origin + location.pathname); return Array.from(document.querySelectorAll(".wt-tail__slot--share a.wt-sns")).map((a) => { const r = a.getBoundingClientRect(); return { key: a.dataset.wtSns, host: new URL(a.href).host, external: new URL(a.href).host !== location.host, hasUrl: a.href.includes(enc) || a.href.includes(location.origin + location.pathname), blank: a.target === "_blank", rel: a.rel, size: [r.width, r.height], label: a.getAttribute("aria-label") }; }); });
    await p.close(); return d;
  };
  const c1 = await browser.newContext(SP); const js = await read(c1); await c1.close();
  const c2 = await browser.newContext({ ...SP, javaScriptEnabled: false }); const noJs = await read(c2); await c2.close();
  out.shareSns = { js, noJs };
  const ok = (d) => d.length === 3 && new Set(d.map((x) => x.key)).size === 3 && d.every((x) => x.hasUrl && x.blank && /noopener/.test(x.rel) && x.size[0] >= 44 && x.size[1] >= 44 && x.label && x.host && x.external === true);
  out.shareSns.pass = ok(js) && ok(noJs);
}
// (d) depth:float — hover で transform が変わる（浮く）、reduced-motion では transform / animation なし、motion:on で商品カードに animation
{
  const ctx = await browser.newContext(PC); const p = await ctx.newPage();
  await p.goto(BASE + "/catalog-03/?wt=depth:float,motion:on", { waitUntil: "networkidle" });
  const sel = "#cat-cta-product .is-style-wt-product";
  const base = await p.evaluate((s) => { const el = document.querySelector(s); const cs = getComputedStyle(el); return { transform: cs.transform, shadow: cs.boxShadow, animation: cs.animationName }; }, sel);
  // 浮遊アニメーション中は要素が "stable" にならず locator.hover がタイムアウトするため、矩形中心へ mouse.move で hover する
  // locator の scrollIntoViewIfNeeded / boundingBox は要素の "stable" を待つため、浮遊アニメーション中はタイムアウトする。DOM API で直接スクロールし矩形中心へ mouse.move する
  const hoverAt = async (page, selector) => { await page.evaluate((sel) => document.querySelector(sel).scrollIntoView({ block: "center" }), selector); await page.waitForTimeout(150); const c = await page.evaluate((sel) => { const r = document.querySelector(sel).getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2 }; }, selector); await page.mouse.move(c.x, c.y); };
  await hoverAt(p, sel); await p.waitForTimeout(500);
  const hover = await p.evaluate((s) => { const cs = getComputedStyle(document.querySelector(s)); return { transform: cs.transform, shadow: cs.boxShadow, animation: cs.animationName }; }, sel);
  await ctx.close();
  const rm = await browser.newContext({ ...PC, reducedMotion: "reduce" }); const rp = await rm.newPage();
  await rp.goto(BASE + "/catalog-03/?wt=depth:float,motion:on", { waitUntil: "networkidle" });
  const reduced0 = await rp.evaluate((s) => getComputedStyle(document.querySelector(s)).animationName, sel);
  await hoverAt(rp, sel); await rp.waitForTimeout(500);
  const reduced = await rp.evaluate((s) => { const cs = getComputedStyle(document.querySelector(s)); return { transform: cs.transform, animation: cs.animationName }; }, sel);
  await rm.close();
  out.depthFloat = { base, hover, reduced0, reduced };
  // hover 中は CSS の translateY(-8px) scale(1.01) = matrix(1.01, 0, 0, 1.01, 0, -8) に一致すること（浮遊アニメーション中の base.transform は時々刻々変わるため差分比較ではなく期待値で判定。Astra 是正）
  out.depthFloat.expectedHover = "matrix(1.01, 0, 0, 1.01, 0, -8)";
  out.depthFloat.pass = base.animation === "wt-float" && typeof base.transform === "string" && hover.transform === out.depthFloat.expectedHover && hover.shadow !== base.shadow && hover.animation === "none" && reduced0 === "none" && reduced.transform === "none" && reduced.animation === "none";
}
// (e) 比較表 rich — 画像 3（alt あり・描画済み）、アイコン付きセル、購入ボタン 3（44px 以上、rel sponsored nofollow）、SP は横スクロール（はみ出さない）
{
  const read = async (ctx) => {
    const p = await ctx.newPage(); await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" });
    // 画像は loading="lazy" のため、表を viewport に入れて読み込み完了を待ってから判定する
    await p.evaluate(() => document.querySelector("#cat-table-rich").scrollIntoView({ block: "center" }));
    await p.waitForFunction(() => Array.from(document.querySelectorAll("#cat-table-rich .wt-tcell__img")).every((i) => i.complete), null, { timeout: 8000 });
    const d = await p.evaluate(() => { const fig = document.querySelector("#cat-table-rich .wp-block-table"); const imgs = Array.from(fig.querySelectorAll(".wt-tcell__img")); const btns = Array.from(fig.querySelectorAll(".wt-tbtn")); return { imgs: imgs.map((i) => ({ alt: i.alt, ok: i.complete && i.naturalWidth > 0, w: i.getBoundingClientRect().width })), icons: fig.querySelectorAll(".wt-tcell__icon .wt-i").length, btns: btns.map((b) => ({ h: b.getBoundingClientRect().height, w: b.getBoundingClientRect().width, rel: b.rel })), overflowX: getComputedStyle(fig).overflowX, figRight: fig.getBoundingClientRect().right, vw: innerWidth }; });
    await p.close(); return d;
  };
  const c1 = await browser.newContext(SP); const sp = await read(c1); await c1.close();
  const c2 = await browser.newContext(PC); const pc = await read(c2); await c2.close();
  out.tableRich = { sp, pc };
  const ok = (d) => d.imgs.length === 3 && d.imgs.every((i) => i.alt && i.ok && i.w > 0) && d.icons >= 6 && d.btns.length === 3 && d.btns.every((b) => b.h >= 44 && b.w >= 44 && /sponsored/.test(b.rel) && /nofollow/.test(b.rel)) && d.overflowX === "auto" && d.figRight <= d.vw;
  out.tableRich.pass = ok(sp) && ok(pc);
}
// (f) footer_credit — 既定 none で非表示、text で法的表記の下に表示、リンクは nofollow
{
  const ctx = await browser.newContext(SP); const p = await ctx.newPage();
  await p.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  const off = await p.evaluate(() => { const el = document.querySelector(".wt-footer__credit"); return el ? getComputedStyle(el).display : null; });
  await p.goto(BASE + ARTICLE + "?wt=footer_credit:text", { waitUntil: "networkidle" });
  const on = await p.evaluate(() => { const el = document.querySelector(".wt-footer__credit"); const legal = document.querySelector(".wt-footer__legal--links, .wt-footer__legal--only"); const a = el && el.querySelector("a"); return el ? { display: getComputedStyle(el).display, belowLegal: legal ? el.getBoundingClientRect().top >= legal.getBoundingClientRect().bottom - 1 : null, rel: a ? a.rel : null, text: el.textContent.trim() } : null; });
  await ctx.close();
  out.footerCredit = { off, on };
  out.footerCredit.pass = off === "none" && !!on && on.display !== "none" && on.belowLegal === true && /nofollow/.test(on.rel || "") && on.text.length > 0;
}

// 9.12. LP パーツ 7 種（WT-EVT-0268、Claude 案）: 軸ごとに 1 型だけが表示され他は非表示、JS 無効でも同じ、タップ 44px、
//       フォームは全入力に label、外部フォーム型はリンク（form 要素なし）、QR 型は SP で QR を隠しボタンを出す、追尾 LINE ボタンは float-cta と重ならず 44px 以上。
{
  const PARTS = { interview: ["summary-card", "link-card", "logo-only"], review: ["quote-photo", "stars-count", "satisfaction-number"], rating: ["certification", "client-logos", "award-badge"], download: ["button-to-form", "form-inline"], form: ["external", "inline"], line: ["button", "qr"] };
  const audit = async (ctx, dev) => {
    const p = await ctx.newPage(); const results = [];
    for (const [part, variants] of Object.entries(PARTS)) for (const v of variants) {
      await p.goto(BASE + LP + `?wt=lp_sections:extended,lp_${part}:${v}`, { waitUntil: "networkidle" });
      // 画像は loading="lazy" のため、当該区間を viewport に入れて読み込み完了を待ってから判定する
      await p.evaluate(([part]) => { const el = document.querySelector(`.wt-lp__section--${part}`); if (el) el.scrollIntoView({ block: "start" }); }, [part]);
      await p.waitForFunction(([part, v]) => { const el = document.querySelector(`.wt-lp-${part}--${v}`); return !el || Array.from(el.querySelectorAll("img")).every((i) => i.complete); }, [part, v], { timeout: 8000 }).catch(() => {});
      const r = await p.evaluate(([part, v, variants]) => {
        const vis = (el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.display !== "none" && s.visibility !== "hidden"; };
        const shown = variants.filter((x) => { const el = document.querySelector(`.wt-lp-${part}--${x}`); return el && vis(el); });
        const el = document.querySelector(`.wt-lp-${part}--${v}`);
        const taps = el ? Array.from(el.querySelectorAll("a[href], button, input:not([type=checkbox]), textarea")).filter(vis).map((t) => { const r = t.getBoundingClientRect(); return { tag: t.tagName.toLowerCase(), w: r.width, h: r.height, ok: r.height >= 44 && r.width >= 44 }; }) : [];
        const inputs = el ? Array.from(el.querySelectorAll("input, textarea")).filter(vis) : [];
        const labelled = inputs.every((i) => i.type === "checkbox" ? !!i.closest("label") : !!(i.id && document.querySelector(`label[for="${i.id}"]`)));
        const forms = el ? el.querySelectorAll("form").length + (el.tagName === "FORM" ? 1 : 0) : 0;
        const submitButtons = el ? el.querySelectorAll("button[type=submit], input[type=submit], button:not([type])").length : 0;
        const pocButtons = el ? el.querySelectorAll("form[data-wt-poc-form=no-submit] button[type=button]").length + (el.tagName === "FORM" && el.dataset.wtPocForm === "no-submit" ? el.querySelectorAll("button[type=button]").length : 0) : 0;
        const qr = el ? el.querySelector(".wt-lp-line__qr") : null; const spBtn = el ? el.querySelector(".wt-lp-line__btn--sp") : null;
        const imgs = el ? Array.from(el.querySelectorAll("img")).map((i) => ({ alt: i.getAttribute("alt"), ok: i.complete && i.naturalWidth > 0 })) : [];
        return { body: document.body.classList.contains(`wt-lp-${part}-${v}`), shown, taps, inputs: inputs.length, labelled, forms, submitButtons, pocButtons, qrVisible: qr ? vis(qr) : null, spBtnVisible: spBtn ? vis(spBtn) : null, imgs, text: el ? el.textContent.replace(/\s+/g, " ").trim().length : 0 };
      }, [part, v, variants]);
      results.push({ dev, part, v, ...r });
    }
    await p.goto(BASE + LP + "?wt=lp_sections:extended", { waitUntil: "networkidle" });
    const stickyOff = await p.evaluate(() => { const el = document.querySelector(".wt-lp-fixed--line-sticky"); return el ? getComputedStyle(el).display : null; });
    await p.goto(BASE + LP + "?wt=lp_fixed:line-sticky,lp_sections:extended", { waitUntil: "networkidle" });
    const sticky = await p.evaluate(() => { const el = document.querySelector(".wt-lp-fixed--line-sticky"); const r = el.getBoundingClientRect(); const float = document.querySelector(".wt-lp-fixed--float-cta"); const fr = float.getBoundingClientRect(); return { visible: r.width > 0 && r.height > 0 && getComputedStyle(el).display !== "none", w: r.width, h: r.height, fixed: getComputedStyle(el).position === "fixed", floatHidden: fr.width === 0 || getComputedStyle(float).display === "none", inViewport: r.bottom <= innerHeight && r.left >= 0 }; });
    sticky.hiddenByDefault = stickyOff === "none";
    await p.close(); return { results, sticky };
  };
  const cSp = await browser.newContext(SP); const sp = await audit(cSp, "sp"); await cSp.close();
  const cPc = await browser.newContext(PC); const pc = await audit(cPc, "pc"); await cPc.close();
  const cNo = await browser.newContext({ ...SP, javaScriptEnabled: false }); const noJs = await audit(cNo, "sp-nojs"); await cNo.close();
  // 必要要素の下限（空配列の every() で合格しないように）: タップ要素 1 以上、写真が要る型は img 1 以上、フォーム型は入力数と「送信できないボタン」
  const MIN = { "interview/summary-card": { imgs: 3 }, "interview/link-card": { imgs: 3, taps: 3 }, "review/quote-photo": { imgs: 3 }, "download/button-to-form": { imgs: 1, taps: 1 }, "download/form-inline": { imgs: 1, inputs: 2 }, "form/inline": { inputs: 6 }, "line/button": { taps: 1 }, "line/qr-sp": { taps: 1 }, "line/qr-pc": { taps: 0 } }; // qr は PC では QR 枠のみ（タップ要素なし）、SP ではボタンに切替
  const okItem = (r) => {
    const m = MIN[`${r.part}/${r.v}-${r.dev.startsWith("pc") ? "pc" : "sp"}`] || MIN[`${r.part}/${r.v}`] || {};
    if ((m.imgs || 0) > r.imgs.length || (m.taps || 0) > r.taps.length || (m.inputs || 0) > (r.inputs || 0)) return false;
    return r.body && r.shown.length === 1 && r.shown[0] === r.v && r.text > 0 && r.taps.every((t) => t.ok) && r.labelled // タップ要素の有無は MIN で型ごとに要求（表示だけの型は 0 でよい） && r.imgs.every((i) => i.alt !== null && i.ok)
    && (r.part !== "form" || (r.v === "external" ? r.forms === 0 && r.taps.some((t) => t.tag === "a") : r.forms >= 1 && r.submitButtons === 0 && r.pocButtons >= 1))
    && (r.part !== "download" || (r.v === "form-inline" ? r.forms >= 1 && r.submitButtons === 0 && r.pocButtons >= 1 : r.forms === 0))
    && (!(r.part === "line" && r.v === "qr") || (r.dev === "pc" ? r.qrVisible === true && r.spBtnVisible === false : r.qrVisible === false && r.spBtnVisible === true));
  };
  const okSticky = (s) => s.visible && s.fixed && s.w >= 44 && s.h >= 44 && s.floatHidden && s.inViewport && s.hiddenByDefault === true; // 既定（軸なし）では非表示（実装時に常時表示になっていた不具合の回帰防止）
  const sameNoJs = JSON.stringify(noJs.results.map((r) => [r.part, r.v, r.shown, r.text])) === JSON.stringify(sp.results.map((r) => [r.part, r.v, r.shown, r.text]));
  out.lpParts = { sp: sp.results, pc: pc.results, noJs: noJs.results, sticky: { sp: sp.sticky, pc: pc.sticky, noJs: noJs.sticky }, sameNoJs };
  // JS 無効の結果も同じ判定（okItem / okSticky）にかける（Astra 是正: 表示型とテキスト長の比較だけだった）
  out.lpParts.pass = sp.results.length === 15 && pc.results.length === 15 && noJs.results.length === 15 && sp.results.every(okItem) && pc.results.every(okItem) && noJs.results.every(okItem) && okSticky(sp.sticky) && okSticky(pc.sticky) && okSticky(noJs.sticky) && sameNoJs;
}

// 10. 結果の集計（既存 gate と段 3 / 段 4 gate を同じ verify.json に固定する）
out.status404.pass = Object.entries(out.status404).filter(([key]) => key.startsWith("/")).every(([, status]) => status === 404) && out.status404.noindex;
out.toc.pass = out.toc.tocH2 === out.toc.h2Count && out.toc.tocH3 === out.toc.h3Count && out.toc.scrollMarginTop !== "0px";
out.reducedMotion.pass = out.reducedMotion.revealHidden === 0 && out.reducedMotion.categoryFooter.pass;
// 段 1/2 の検査も合否を持たせる（コントラスト 11 項目、guard 12 判定、タップ監査 4 画面、見出し 1 行）
out.contrastPass = out.contrast.length > 0 && out.contrast.every((x) => !x.missing && x.pass);
out.contrastGuardPass = out.contrastGuard.length > 0 && out.contrastGuard.every((x) => x.pass === true);
const contrastVariantsOk = (rows, expectNoLum) => Array.isArray(rows) && rows.length === 42 && rows.every((x) => x.pass === true && x.required >= 3 && x.singleClass === true && x.hasBefore === true && (!expectNoLum || x.lum === null));
out.contrastVariantsPass = contrastVariantsOk(out.contrastVariants, false);
out.contrastVariantsSpPass = contrastVariantsOk(out.contrastVariantsSp, false);
out.contrastVariantsNoJsPass = contrastVariantsOk(out.contrastVariantsNoJs, true);
out.contrastVariantsNoJsSpPass = contrastVariantsOk(out.contrastVariantsNoJsSp, true);
for (const k of ["article", "article-announce", "404", "catalog"]) out.tap[k].pass = out.tap[k].below44.length === 0 && out.tap[k].below24.length === 0;
out.headline.pass = out.headline.lines === 1;
const checkList = [
  ["noJs", out.noJs.pass], ["reducedMotion", out.reducedMotion.pass], ["status404", out.status404.pass], ["table", out.table.pass], ["toc", out.toc.pass],
  ["contrast", out.contrastPass], ["contrastGuard", out.contrastGuardPass], ["contrastVariants", out.contrastVariantsPass], ["contrastVariantsSp", out.contrastVariantsSpPass], ["contrastVariantsNoJs", out.contrastVariantsNoJsPass], ["contrastVariantsNoJsSp", out.contrastVariantsNoJsSpPass], ["relatedQuality", out.relatedQuality.pass], ["headline", out.headline.pass],
  ["articleTapSp", out.tap.article.pass], ["articleAnnounceTapSp", out.tap["article-announce"].pass], ["notFoundTapSp", out.tap["404"].pass], ["catalogTapSp", out.tap.catalog.pass],
  ["categoryTapSp", out.tap.categorySp.pass], ["categoryTapPc", out.tap.categoryPc.pass], ["footerTapSp", out.tap.footerSp.pass], ["footerTapPc", out.tap.footerPc.pass], ["authorSnsTapSp", out.tap.authorSnsSp.pass], ["authorSnsTapPc", out.tap.authorSnsPc.pass],
  ["footerContrast", out.footerContrast.pass], ["footerNoJs", out.footerNoJs.pass], ["loadMoreNoJs", out.loadMoreNoJs.pass], ["loadMoreJs", out.loadMoreJs.pass], ["categoryPagination", out.categoryPagination.pass], ["categoryHeroContrast", out.categoryHeroContrast.pass], ["fixedOverlapSp", out.fixedOverlap.sp.pass], ["fixedOverlapPc", out.fixedOverlap.pc.pass],
  ["lpTapSp", out.tap.lpSp.pass], ["lpTapPc", out.tap.lpPc.pass], ["lpContrast", out.lpContrast.pass], ["lpFullbleedContrast", out.lpFullbleedContrast.pass], ["lpFormNoJs", out.lpFormNoJs.pass], ["lpAnchorNav", out.lpAnchorNav.pass], ["lpSections", out.lpSections.pass], ["lpFixedOverlapSp", out.lpFixedOverlap.sp.pass], ["lpFixedOverlapPc", out.lpFixedOverlap.pc.pass], ["lpReducedMotion", out.lpReducedMotion.pass], ["lpLcpHero", out.lpLcpHero.pass],
  ["lpFooterFaceDefault", out.lpFooterFaceDefault.pass], ["lpVisibleAnchors", out.lpVisibleAnchors.pass], ["lpFaceScopedTotop", out.lpFaceScopedTotop.pass],
  ["tableCaptionSp", out.tableCaptionSp.pass], ["tableNumFontSize", out.tableNumFontSize.pass], ["headerInnerWidth", out.headerInnerWidth.pass], ["headerCtaOffCenter", out.headerCtaOffCenter.pass],
  ["headingNumberPc", out.headingNumberPc.pass], ["headingNumberSp", out.headingNumberSp.pass], ["underlineGap", out.underlineGap.pass], ["prTagNotStackedPc", out.prTagNotStackedPc.pass], ["prTagNotStackedSp", out.prTagNotStackedSp.pass],
  ["tocFloatLeft", out.tocFloatLeft.pass], ["tableCaptionPcPosition", out.tableCaptionPcPosition.pass],
  ["detextVisualDiff", out.detextVisualDiff.pass], ["ctaBannerNoPrPrefix", out.ctaBannerNoPrPrefix.pass],
  ["productCardNoPrBadge", out.productCardNoPrBadge.pass], ["qaModal", out.qaModal.pass],
  ["graphs", out.graphs.pass], ["metricsSp", out.metricsSp.pass],
  ["prNoticeText", out.prNoticeText.pass], ["relatedSlider", out.relatedSlider.pass], ["shareSns", out.shareSns.pass], ["depthFloat", out.depthFloat.pass], ["tableRich", out.tableRich.pass], ["footerCredit", out.footerCredit.pass],
  ["lpParts", out.lpParts.pass],
];
// 2026-09-05 Astra 再レビュー是正（改善）: prAutoFixtures.pass===null（--wpclidir 未指定でスキップ）を
// true に変換して合格件数へ加算していたのは、実行していない検査を「合格扱い」に見せてしまう不正確な集計だった。
// skip 時は checkList（分母・分子とも）から除外し、summary.skipped に別掲する。
const skipped = [];
if (out.prAutoFixtures.pass === null) {
  skipped.push("prAutoFixtures");
} else {
  checkList.push(["prAutoFixtures", out.prAutoFixtures.pass]);
}
out.summary = { pass: checkList.filter(([, pass]) => pass).length, fail: checkList.filter(([, pass]) => !pass).length, skipped, checks: Object.fromEntries(checkList.map(([name, pass]) => [name, pass])) };
out.pass = out.summary.fail === 0;
await browser.close();
fs.writeFileSync(OUT, JSON.stringify(out, null, 1));
console.log(JSON.stringify(out, null, 1));

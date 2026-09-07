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
  // 2026-09-06: 追加 5 型（grouped / column / score / gauge / radar）は graphsMore で検査し、本検査は当初の 4 型に限定する
  const ORIG_GRAPHS = ["bar", "stack", "donut", "line"]; const onlyOrig = (arr) => arr.filter((g) => ORIG_GRAPHS.includes(g.type));
  const ctxJs = await browser.newContext(SP); const withJs = onlyOrig(await read(ctxJs)); await ctxJs.close();
  const ctxNoJs = await browser.newContext({ ...SP, javaScriptEnabled: false }); const noJs = onlyOrig(await read(ctxNoJs)); await ctxNoJs.close();
  const ctxPc = await browser.newContext(PC); const pc = onlyOrig(await read(ctxPc)); await ctxPc.close();
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
        const pocButtons = el ? (el.tagName === "FORM" && el.dataset.wtPocForm === "no-submit" ? el.querySelectorAll("button[type=button]").length : el.querySelectorAll("form[data-wt-poc-form=no-submit] button[type=button]").length) : 0;
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
    // タップ要素の有無は MIN で型ごとに要求（表示だけの型は 0 でよい）。画像は全件 alt あり・読み込み済み
    return r.body && r.shown.length === 1 && r.shown[0] === r.v && r.text > 0 && r.taps.every((t) => t.ok) && r.labelled && r.imgs.every((i) => i.alt !== null && i.ok)
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

// 2026-09-06 PO 反応 17 回目（WT-EVT-0270〜0276）の検査（Astra 是正 1 巡目を反映: 可視性・型固有部品・値の照合・JS 無効・コントラストを判定に含める）
const VIS_SRC = `(el) => { if (!el) return false; const r = el.getBoundingClientRect(); if (!(r.width > 0 && r.height > 0)) return false; for (let e = el; e; e = e.parentElement) { const s = getComputedStyle(e); if (s.display === "none" || s.visibility === "hidden" || parseFloat(s.opacity) === 0) return false; } return true; }`;
// CSS mask で描くグリフ（.wt-i）が実際に描かれているか: 可視・16px 以上・mask-image が none でない・currentColor が透明でない
const GLYPH_SRC = `(el) => { const vis = ${VIS_SRC}; if (!el || !vis(el)) return false; const r = el.getBoundingClientRect(); const s = getComputedStyle(el); const mask = s.maskImage && s.maskImage !== "none" ? s.maskImage : (s.webkitMaskImage && s.webkitMaskImage !== "none" ? s.webkitMaskImage : ""); const c = s.backgroundColor.match(/rgba?\\(([^)]+)\\)/); const a = c ? (c[1].split(",")[3] ?? "1") : "1"; return r.width >= 16 && r.height >= 16 && /url\\(/.test(mask) && parseFloat(a) > 0; }`;
const HEADER_AUDIT_SRC = `([expectBody, dev]) => {
  const vis = ${VIS_SRC};
  const header = document.querySelector(".wt-header");
  const title = header && header.querySelector(".wp-block-site-title a");
  const links = header ? Array.from(header.querySelectorAll(".wp-block-navigation-item__content, .wt-header__textnav a")).filter(vis) : [];
  const controls = header ? Array.from(header.querySelectorAll("a, button, input, [role=button]")).filter(vis).filter((el) => !el.closest(".wp-block-navigation__responsive-container.is-menu-open")) : [];
  // SP は全操作要素 44×44 以上。PC はナビ文字リンク（インライン扱い）を除いた操作要素（CTA・電話・検索）が 44×44 以上
  const sized = controls.filter((el) => dev === "sp" || !el.classList.contains("wp-block-navigation-item__content"));
  const below44 = sized.filter((el) => { const r = el.getBoundingClientRect(); return r.width < 44 || r.height < 44; }).map((el) => (el.className || el.tagName).toString().slice(0, 60));
  const bg = (el) => { for (let e = el; e; e = e.parentElement) { const c = getComputedStyle(e).backgroundColor; if (c && !/rgba\\(0, 0, 0, 0\\)|transparent/.test(c)) return c; } return "rgb(255, 255, 255)"; };
  const hs = header ? getComputedStyle(header) : null;
  const probe = document.createElement("span"); probe.style.color = "var(--wp--preset--color--accent)"; document.body.appendChild(probe); const accent = getComputedStyle(probe).color; probe.remove();
  const tel = header && header.querySelector(".wt-header__tel");
  const scrim = document.querySelector(".wt-posthead__img") ? getComputedStyle(document.querySelector(".wt-posthead__img"), "::after").backgroundImage : null;
  return {
    body: document.body.classList.contains(expectBody), headerVisible: vis(header), titleVisible: vis(title), titleColor: title ? getComputedStyle(title).color : null, titleBg: title ? bg(title) : null,
    titleShadow: title ? getComputedStyle(title).textShadow : null, position: hs ? hs.position : null, headerBg: hs ? hs.backgroundColor : null, accent,
    linkColors: links.map((a) => getComputedStyle(a).color), linkBg: links.length ? bg(links[0]) : null,
    visibleLinks: links.length, controls: controls.length, below44, titleCenterDx: title ? Math.round((title.getBoundingClientRect().left + title.getBoundingClientRect().width / 2) - innerWidth / 2) : null,
    hamburgerVisible: vis(header && header.querySelector(".wp-block-navigation__responsive-container-open")), searchVisible: vis(header && header.querySelector(".wt-header__search--sp")), pcSearchVisible: vis(header && header.querySelector(".wt-header__search.wt-only-pc")),
    ctaVisible: vis(header && header.querySelector(".wt-header__cta .wp-block-button__link")), telVisible: vis(tel), telHref: tel ? tel.getAttribute("href") : null,
    navlineVisible: vis(header && header.querySelector(".wt-header__row--navline")), navlineLinks: header ? Array.from(header.querySelectorAll(".wt-header__row--navline .wp-block-navigation-item__content")).filter(vis).length : 0, navbandVisible: vis(header && header.querySelector(".wt-header__navband")),
    spCtaVisible: vis(header && header.querySelector(".wt-header__spcta")), textNavVisible: vis(header && header.querySelector(".wt-header__textnav")), textNavLinks: header ? Array.from(header.querySelectorAll(".wt-header__textnav a")).filter(vis).length : 0,
    announce: (() => { const a = document.querySelector(".wt-announce__in"); if (!a) return null; const r = a.getBoundingClientRect(); return { visible: vis(a), left: Math.round(r.left), right: Math.round(r.right), width: Math.round(r.width), viewport: innerWidth }; })(),
    heroTop: (() => { const img = document.querySelector(".wt-posthead__img img"); return img ? Math.round(img.getBoundingClientRect().top + scrollY) : null; })(), scrim,
    textOnHero: (() => { const img = document.querySelector(".wt-posthead__img img"); if (!img) return null; const h = img.getBoundingClientRect(); const els = [title, ...links, header && header.querySelector(".wp-block-navigation__responsive-container-open")].filter(vis); return els.length > 0 && els.every((el) => { const r = el.getBoundingClientRect(); return r.left >= h.left - 1 && r.right <= h.right + 1 && r.top >= h.top - 1 && r.bottom <= h.bottom + 1; }); })(),
  };
}`;
{
  const contrastOk = (fg, bgc, min) => { const c = parse(fg), b = parse(bgc); return c && b ? ratio(lum(c.rgb), lum(b.rgb)) >= min : false; };
  // overlay の上端スクリム: "linear-gradient(rgba(0, 0, 0, α) 0px, rgba(0, 0, 0, α) 72px, …" の 2 番目の停止点（72px まで一定）の α を読む（computed style は既定方向 "to bottom" を省略する）。
  // 白背景の画像を最悪ケースとし、白文字とのコントラスト = 1.05 / ((1-α)+0.05) ≥ 4.5 を要求（α ≥ .82）
  const scrimTopAlpha = (bgImage) => { if (!bgImage) return null; const m = bgImage.match(/linear-gradient\((?:to bottom,\s*)?rgba\(0, 0, 0, ([\d.]+)\) 0(?:px)?,\s*rgba\(0, 0, 0, ([\d.]+)\) 72px/); return m ? Math.min(parseFloat(m[1]), parseFloat(m[2])) : null; };
  const overlayContrastOk = (a) => { const alpha = scrimTopAlpha(a.scrim); if (alpha === null) return false; const bgLum = 1 - alpha; return (1.05) / (bgLum + 0.05) >= 4.5; };
  const HEADER_AUDIT_FN = new Function("args", "return (" + HEADER_AUDIT_SRC + ")(args);");
  const audit = (p, expectBody, dev) => p.evaluate(HEADER_AUDIT_FN, [expectBody, dev]);
  const PC_TYPES = ["center", "two-rows", "tel", "band", "overlay"];
  const SP_TYPES = [["band", "header:band", "wt-header-band"], ["overlay", "header:overlay,eyecatch:hero", "wt-header-overlay"], ["hamburger-cta", "sp:cta", "wt-sp-cta"], ["text-nav", "sp:text-nav", "wt-sp-text-nav"], ["center-logo", "sp:center-logo", "wt-sp-center-logo"]];
  const judgePc = (v, a) => {
    let pass = a.body && a.headerVisible && a.titleVisible && a.visibleLinks >= 4 && a.below44.length === 0 && !a.hamburgerVisible;
    if (v === "center") pass = pass && Math.abs(a.titleCenterDx) <= 8 && a.navlineVisible && a.navlineLinks >= 4;
    if (v === "two-rows") pass = pass && a.navbandVisible && a.navlineLinks >= 6 && a.ctaVisible && a.pcSearchVisible;
    if (v === "tel") pass = pass && a.telVisible && /^tel:/.test(a.telHref || "") && a.ctaVisible;
    if (v === "band") pass = pass && a.headerBg === a.accent && contrastOk(a.titleColor, a.titleBg, 4.5) && a.linkColors.length > 0 && a.linkColors.every((c) => contrastOk(c, a.linkBg, 4.5));
    if (v === "overlay") pass = pass && a.position === "absolute" && /rgba\(0, 0, 0, 0\)|transparent/.test(a.headerBg) && a.titleColor === "rgb(255, 255, 255)" && a.titleShadow !== "none" && a.heroTop !== null && a.heroTop < 60 && overlayContrastOk(a) && a.linkColors.every((c) => c === "rgb(255, 255, 255)") && a.textOnHero === true;
    return pass;
  };
  const judgeSp = (v, a) => {
    let pass = a.body && a.headerVisible && a.titleVisible && a.below44.length === 0;
    if (v === "band") pass = pass && a.hamburgerVisible && a.headerBg === a.accent && contrastOk(a.titleColor, a.titleBg, 4.5);
    if (v === "overlay") pass = pass && a.hamburgerVisible && a.position === "absolute" && /rgba\(0, 0, 0, 0\)|transparent/.test(a.headerBg) && a.titleColor === "rgb(255, 255, 255)" && a.titleShadow !== "none" && a.heroTop !== null && a.heroTop < 60 && overlayContrastOk(a) && a.textOnHero === true;
    if (v === "hamburger-cta") pass = pass && a.hamburgerVisible && a.spCtaVisible && !a.searchVisible;
    if (v === "text-nav") pass = pass && !a.hamburgerVisible && a.textNavVisible && a.textNavLinks >= 4;
    if (v === "center-logo") pass = pass && a.hamburgerVisible && a.searchVisible && Math.abs(a.titleCenterDx) <= 8;
    return pass;
  };
  const results = [];
  const run = async (cfg, dev, js) => {
    const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage();
    const list = dev === "pc" ? PC_TYPES.map((v) => [v, `header:${v}` + (v === "overlay" ? ",eyecatch:hero" : ""), `wt-header-${v}`]) : SP_TYPES;
    for (const [v, q, expectBody] of list) {
      await p.goto(BASE + ARTICLE + `?wt=${q}`, { waitUntil: js ? "networkidle" : "load" });
      const a = await audit(p, expectBody, dev);
      results.push({ dev, js, variant: v, ...a, pass: dev === "pc" ? judgePc(v, a) : judgeSp(v, a) });
    }
    await ctx.close();
  };
  await run(PC, "pc", true); await run(SP, "sp", true); await run(PC, "pc", false); await run(SP, "sp", false);
  // JS 無効でも同じ型が同じ要素数で見えること（ヘッダーは JS に依存しない）
  const sameNoJs = results.filter((r) => r.js).every((r) => { const n = results.find((x) => !x.js && x.dev === r.dev && x.variant === r.variant); return n && n.visibleLinks === r.visibleLinks && n.controls === r.controls && n.spCtaVisible === r.spCtaVisible && n.textNavVisible === r.textNavVisible; });
  out.headerVariants = { results, sameNoJs, pass: results.length === 20 && results.every((r) => r.pass) && sameNoJs };
  // (b) announceFullWidth: 可視・左端 0 以上・右端 viewport 以下・幅が viewport − gutter×2 以上（PC は 1120 の旧上限を超える）
  const ann = [];
  for (const [cfg, dev, gutter] of [[PC, "pc", 40], [SP, "sp", 24]]) {
    const ctx = await browser.newContext(cfg); const p = await ctx.newPage(); await p.goto(BASE + ARTICLE + "?wt=header:announce", { waitUntil: "networkidle" });
    const a = await audit(p, "wt-header-announce", dev); await ctx.close();
    const x = a.announce; ann.push({ dev, ...x, pass: !!x && x.visible && x.left >= 0 && x.right <= x.viewport && x.width >= x.viewport - gutter * 2 && x.width <= x.viewport && (dev !== "pc" || x.width > 1120) });
  }
  out.announceFullWidth = { results: ann, pass: ann.length === 2 && ann.every((x) => x.pass) };
}
// (c) numboxNum: 期待する番号を DOM から組み立て（numbox h2 = 01, 02 …、配下 h3 = 親-連番、通常 h2 で連番を振り直す）、
//     CDP DOMSnapshot で ::before の**実描画文字列**（counter の解決値）を読み、見出しごとに完全一致で照合する（幅の照合では同桁の誤番号を排除できないため置換。Astra 2 巡目）。
//     さらに変異テスト: h3 の counter-increment を 2 にした状態で読み直し、h3 のラベルがすべて期待と不一致になる（= 判定が誤番号を検出できる）ことを合格条件に含める。
//     demo は post-content 内の Group（wt-numbox-demo）に入っており、実記事で numbox を Group に入れた構造と同じ（:has() は子孫判定）。
{
  const ctx = await browser.newContext(PC); const p = await ctx.newPage(); await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" });
  const readLabels = async (rootId) => {
    const cdp = await ctx.newCDPSession(p); const snap = await cdp.send("DOMSnapshot.captureSnapshot", { computedStyles: [] }); await cdp.detach();
    const doc = snap.documents[0], S = snap.strings, N = doc.nodes, L = doc.layout;
    const ids = {}; for (let i = 0; i < N.nodeName.length; i++) { const at = N.attributes[i] || []; for (let k = 0; k < at.length; k += 2) if (S[at[k]] === "id") ids[i] = S[at[k + 1]]; }
    const childText = {}; for (let i = 0; i < N.nodeName.length; i++) if (S[N.nodeName[i]] === "#text") childText[N.parentIndex[i]] = (childText[N.parentIndex[i]] || "") + S[N.nodeValue[i]];
    const inRoot = (ni) => { if (rootId === null) return true; let guard = 0; for (let x = ni; x >= 0 && guard < 200; x = N.parentIndex[x], guard++) { if (ids[x] === rootId) return true; if (N.parentIndex[x] === x) break; } return false; };
    const per = new Map();
    for (let i = 0; i < L.nodeIndex.length; i++) { const ni = L.nodeIndex[i]; if (S[N.nodeName[ni]] !== "::before") continue; const par = N.parentIndex[ni]; const pn = S[N.nodeName[par]]; if (pn !== "H2" && pn !== "H3") continue; if (!inRoot(par)) continue; const t = L.text[i]; if (t === undefined || t < 0) continue; per.set(par, (per.get(par) || "") + S[t]); }
    return Array.from(per.entries()).sort((x, y) => x[0] - y[0]).map(([par, label]) => ({ tag: S[N.nodeName[par]], heading: (childText[par] || "").trim(), label }));
  };
  const expected = await p.evaluate(() => { const demo = document.querySelector("#cat-h2-numbox-h3num"); const pad2 = (n) => String(n).padStart(2, "0"); const out = []; let h2n = 0, h3n = 0;
    for (const el of Array.from(demo ? demo.querySelectorAll("h2, h3") : [])) { if (el.tagName === "H2") { if (el.classList.contains("is-style-wt-numbox")) { h2n++; out.push({ tag: "H2", heading: el.textContent.trim(), label: pad2(h2n) }); } h3n = 0; continue; } if (!el.classList.contains("is-style-wt-num")) continue; h3n++; out.push({ tag: "H3", heading: el.textContent.trim(), label: `${pad2(h2n)}-${h3n}` }); }
    const h2s = Array.from(demo ? demo.querySelectorAll("h2.is-style-wt-numbox") : []);
    return { list: out, counterSet: h2s.map((h) => getComputedStyle(h).counterSet), h2Increment: h2s.map((h) => getComputedStyle(h).counterIncrement), h3Increment: Array.from(demo.querySelectorAll("h3.is-style-wt-num")).map((h) => getComputedStyle(h).counterIncrement) }; });
  const actual = await readLabels("cat-h2-numbox-h3num");
  const plain = await readLabels("cat-h3-num");
  // 専用 class なしの Group・post-content 直下・後続の別コンテナ・入れ子 Group の外へ出る境界（#cat-h2-numbox-plain 以降）。counter は DOM 順に引き継がれるため、見出し文字で拾って期待値と完全一致させる
  const allLabels = await readLabels(null);
  // h2 番号（wt-h2num）は本文全体で続くため、A の番号 = A より前にある（.wt-numbox-demo の入れ子スコープ外の）numbox h2 の数 + 1
  const nBefore = await p.evaluate(() => { const all = Array.from(document.querySelectorAll(".wp-block-post-content h2.is-style-wt-numbox")).filter((h) => !h.closest(".wt-numbox-demo")); const a = all.findIndex((h) => h.textContent.trim().startsWith("連番検証 A")); return a; });
  const pad2 = (v) => String(v).padStart(2, "0");
  const PLAIN_EXPECT = [["連番検証 A（専用 class なしの Group）", pad2(nBefore + 1)], ["連番検証 A-1", `${pad2(nBefore + 1)}-1`], ["連番検証 A-2", `${pad2(nBefore + 1)}-2`], ["連番検証 L（post-content 直下の単独 num）", "01"], ["連番検証 B（後続の別 Group）", pad2(nBefore + 2)], ["連番検証 B-1", `${pad2(nBefore + 2)}-1`], ["連番検証 M（B の後の単独 num）", "01"], ["連番検証 C（入れ子 Group の内側）", pad2(nBefore + 3)], ["連番検証 C-1", `${pad2(nBefore + 3)}-1`], ["連番検証 N（入れ子 Group の外へ出た単独 num）", "01"], ["連番検証 D（入れ子 Group、直後は段落）", pad2(nBefore + 4)], ["連番検証 D-1", `${pad2(nBefore + 4)}-1`], ["連番検証 P-1（段落の後の単独 num）", "01"], ["連番検証 P-2（後続への継承）", "02"]];
  const plainGroup = PLAIN_EXPECT.map(([heading, label]) => { const f = allLabels.find((x) => x.heading === heading); return { heading, expected: label, actual: f ? f.label : null }; });
  await p.addStyleTag({ content: ".wt-numbox-demo h3.is-style-wt-num{counter-increment:wt-h3 2}" });
  const mutated = await readLabels("cat-h2-numbox-h3num");
  const same = (x, y) => x.tag === y.tag && x.heading === y.heading && x.label === y.label;
  const h3Idx = expected.list.map((e, i) => (e.tag === "H3" ? i : -1)).filter((i) => i >= 0);
  const n = { expected: expected.list, actual, mutated, plain, plainGroup, numboxBeforeA: nBefore, counterSet: expected.counterSet, h2Increment: expected.h2Increment, h3Increment: expected.h3Increment };
  n.pass = expected.list.length === 5 && expected.list.map((e) => e.label).join(",") === "01,01-1,01-2,02,02-1"
    && actual.length === expected.list.length && actual.every((x, i) => same(x, expected.list[i]))
    && mutated.length === expected.list.length && h3Idx.length === 3 && h3Idx.every((i) => mutated[i].label !== expected.list[i].label) && mutated.filter((x) => x.tag === "H2").every((x, j) => x.label === expected.list.filter((e) => e.tag === "H2")[j].label)
    && n.counterSet.length === 2 && n.counterSet.every((v) => v === "wt-h3 0") && n.h2Increment.every((v) => v === "wt-h2num 1") && n.h3Increment.length === 3 && n.h3Increment.every((v) => v === "wt-h3 1")
    && plain.length >= 1 && plain[0].tag === "H3" && plain[0].label === "01" && plain.every((x) => !/-/.test(x.label))
    && nBefore >= 0 && plainGroup.length === 14 && plainGroup.every((x) => x.actual === x.expected);
  out.numboxNum = n;
  await ctx.close();
}
// (d) graphsMore: 追加 5 型。視覚要素が実際に見える（矩形・display・visibility・opacity）、表の値と視覚値（--v / data-v / 座標）が一致、role=img の数と aria-label、
//     figcaption、SP で横はみ出しなし、JS 無効でも同じ
{
  const read = async (cfg, js) => { const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage(); await p.goto(BASE + "/catalog-03/", { waitUntil: js ? "networkidle" : "load" });
    const r = await p.evaluate((visSrc) => {
      const vis = eval(visSrc);
      const num = (t) => parseFloat(String(t).replace(/[^\d.]/g, ""));
      const cssVar = (el, name) => { const m = (el.getAttribute("style") || "").match(new RegExp(name + ":\\s*([\\d.]+)")); return m ? parseFloat(m[1]) : null; };
      const out = [];
      for (const k of ["grouped", "column", "score", "gauge", "radar"]) {
        const f = document.querySelector(`#cat-graph-${k} figure.wt-graph`); if (!f) { out.push({ k, missing: true }); continue; }
        const tds = Array.from(f.querySelectorAll("table.wt-graph__data tbody tr")).map((tr) => Array.from(tr.querySelectorAll("td")).map((td) => num(td.textContent)));
        const imgs = Array.from(f.querySelectorAll("[role=img]"));
        const g = { k, rows: tds.length, caption: !!f.querySelector("figcaption") && f.querySelector("figcaption").textContent.trim().length > 0, ariaImgs: imgs.length, ariaLabelled: imgs.every((el) => (el.getAttribute("aria-label") || "").length > 5), overflow: f.scrollWidth - f.clientWidth, width: Math.round(f.getBoundingClientRect().width), figVisible: vis(f), valuesOk: false, visibleOk: false, detail: null };
        if (k === "grouped") {
          const rows = Array.from(f.querySelectorAll(".wt-graph__row"));
          const pairs = rows.map((row) => Array.from(row.querySelectorAll(".wt-graph__bar")).map((b) => ({ v: cssVar(b, "--v"), label: num(b.getAttribute("data-v")), vis: vis(b.querySelector("i")), w: b.querySelector("i").getBoundingClientRect().width, track: b.getBoundingClientRect().width })));
          const maxLabel = Math.max(...tds.flat()); const maxV = Math.max(...pairs.flat().map((x) => x.v));
          g.valuesOk = rows.length === tds.length && pairs.every((pr, i) => pr.length === 2 && pr.every((x, j) => x.label === tds[i][j] && Math.abs(x.v / maxV - x.label / maxLabel) < 0.02 && Math.abs(x.w / x.track - x.v / 100) < 0.03));
          g.visibleOk = pairs.flat().every((x) => x.vis && x.w > 0); g.detail = pairs;
        } else if (k === "column") {
          const cols = Array.from(f.querySelectorAll(".wt-graph__col")).map((c) => ({ v: cssVar(c, "--v"), label: num(c.querySelector("b").textContent), vis: vis(c.querySelector("i")), h: c.querySelector("i").getBoundingClientRect().height, box: c.getBoundingClientRect().height }));
          const maxLabel = Math.max(...tds.map((r) => r[0]));
          g.valuesOk = cols.length === tds.length && cols.every((c, i) => c.label === tds[i][0] && Math.abs(c.v - Math.round(c.label / maxLabel * 100)) <= 1 && Math.abs(c.h / c.box - c.v / 100) < 0.03) && f.querySelectorAll(".wt-graph__collabels span").length === cols.length;
          g.visibleOk = cols.every((c) => c.vis && c.h > 0); g.detail = cols;
        } else if (k === "score") {
          const scores = Array.from(f.querySelectorAll(".wt-graph__score")).map((sc) => { const cells = Array.from(sc.querySelectorAll("i")); const filled = cells.filter((i) => getComputedStyle(i).backgroundColor !== getComputedStyle(cells[4]).backgroundColor || num(sc.getAttribute("data-v")) === 5).length; return { v: num(sc.getAttribute("data-v")), cells: cells.length, filled, vis: cells.every(vis), shown: num(sc.parentElement.querySelector(".wt-graph__val").textContent) }; });
          g.valuesOk = scores.length === tds.length && scores.every((sc, i) => sc.cells === 5 && sc.v === tds[i][0] && sc.shown === tds[i][0] && sc.filled === sc.v);
          g.visibleOk = scores.every((sc) => sc.vis); g.detail = scores;
        } else if (k === "gauge") {
          // 描画値の照合: computed の conic-gradient は calc が解決済み（"from -90deg at 50% 100%, 塗 0deg, 塗 X%, 軌道 X%, 軌道 50%, 透明 50%"）。X = v × 0.5 を ±0.05 で要求し、塗色 ≠ 軌道色
          const gauges = Array.from(f.querySelectorAll(".wt-graph__gauge")).map((ga) => { const bg = getComputedStyle(ga).backgroundImage; const m = bg.match(/^conic-gradient\(from -90deg at 50% 100%, (rgba?\([^)]*\)) 0deg, \1 ([\d.]+)%, (rgba?\([^)]*\)) \2%, \3 50%, rgba\(0, 0, 0, 0\) 50%\)$/); return { v: cssVar(ga, "--v"), shown: num(ga.querySelector("b").textContent), vis: vis(ga), bg, stopPct: m ? parseFloat(m[2]) : null, fill: m ? m[1] : null, track: m ? m[3] : null }; });
          g.valuesOk = gauges.length === tds.length && gauges.every((ga, i) => ga.v === tds[i][0] && ga.shown === tds[i][0] && ga.stopPct !== null && Math.abs(ga.stopPct - ga.v * 0.5) <= 0.05 && ga.fill !== ga.track);
          g.visibleOk = gauges.every((ga) => ga.vis); g.detail = gauges;
        } else if (k === "radar") {
          // 座標の照合: 中心は軸線の始点、満点半径 R は格子の最外周（中心からの最大距離）。系列 s の点 i = 中心 + (v/5)·R·(sin θ, −cos θ)、θ = i·72°。各座標 ±0.6px（SVG は 0.1 刻み）
          const parsePts = (el) => (el && el.getAttribute("points") ? el.getAttribute("points").trim().split(/\s+/).map((pr) => pr.split(",").map(Number)) : []);
          const axes = Array.from(f.querySelectorAll("line")); const cx = axes.length ? parseFloat(axes[0].getAttribute("x1")) : NaN, cy = axes.length ? parseFloat(axes[0].getAttribute("y1")) : NaN;
          const gridPts = Array.from(f.querySelectorAll(".wt-graph__radar-grid")).flatMap(parsePts); const R = gridPts.length ? Math.max(...gridPts.map(([x, y]) => Math.hypot(x - cx, y - cy))) : NaN;
          const maxScore = 5;
          const polys = ["a", "b"].map((s, si) => { const pl = f.querySelector(`.wt-graph__radar-${s}`); const pts = parsePts(pl); const exp = tds.map((r, i) => { const th = (i * 72) * Math.PI / 180; const rr = r[si] / maxScore * R; return [cx + rr * Math.sin(th), cy - rr * Math.cos(th)]; });
            const dev = pts.length === exp.length ? Math.max(...pts.map(([x, y], i) => Math.max(Math.abs(x - exp[i][0]), Math.abs(y - exp[i][1])))) : Infinity;
            return { points: pts.length, vis: vis(pl), fill: pl ? getComputedStyle(pl).fillOpacity : null, maxDev: dev, maxDevShown: Math.round(dev * 100) / 100 }; });
          const legend = f.querySelectorAll(".wt-graph__legend li").length; const labels = f.querySelectorAll(".wt-graph__radar-labels text").length;
          g.valuesOk = tds.length === 5 && tds.every((r) => r.length === 2) && Number.isFinite(cx) && Number.isFinite(R) && R > 50 && axes.length === 5 && polys.every((pl) => pl.points === 5 && pl.maxDev <= 0.6) && legend === 2 && labels === 5;
          g.visibleOk = polys.every((pl) => pl.vis && parseFloat(pl.fill) > 0); g.detail = { polys, legend, labels, cx, cy, R };
        }
        out.push(g);
      }
      return out;
    }, VIS_SRC);
    await ctx.close(); return r; };
  const EXPECT_ARIA = { grouped: 0, column: 1, score: 0, gauge: 3, radar: 1 };
  const ok = (r) => r.length === 5 && r.every((x) => !x.missing && x.rows >= 3 && x.caption && x.ariaImgs === EXPECT_ARIA[x.k] && x.ariaLabelled && x.overflow <= 0 && x.width > 0 && x.figVisible && x.valuesOk && x.visibleOk);
  const pc = await read(PC, true), sp = await read(SP, true), spNoJs = await read(SP, false);
  const key = (g) => JSON.stringify([g.k, g.rows, g.caption, g.ariaImgs, g.valuesOk, g.visibleOk]);
  const sameNoJs = spNoJs.length === sp.length && spNoJs.every((g, i) => key(g) === key(sp[i]));
  out.graphsMore = { pc, sp, spNoJs, sameNoJs, pass: ok(pc) && ok(sp) && ok(spNoJs) && sameNoJs };
}
// (e) relatedNoFixture: 関連 6 件 + 次に読む 1 件に、既定カテゴリ（wp option default_category の名前。wp-cli で取れないときは fail にし source に理由を記録）の投稿と
//     自記事が入らず、各カードのカテゴリが 1 つ以上・画像が読込済み（naturalWidth > 0）
{
  let defaultCat = "Uncategorized", source = "fallback:Uncategorized";
  if (WPCLIDIR) {
    try {
      const wp = (cmdArgs) => execFileSync("docker", ["compose", "run", "--rm", "-T", "wpcli", ...cmdArgs], { cwd: WPCLIDIR, encoding: "utf8" });
      const id = wp(["option", "get", "default_category"]).trim();
      defaultCat = wp(["term", "get", "category", id, "--field=name"]).trim(); source = `wp-cli:default_category=${id}`;
    } catch (e) { source = `wp-cli failed (${String(e).slice(0, 80)}); fallback:Uncategorized`; }
  }
  const run = async (cfg) => { const ctx = await browser.newContext(cfg); const p = await ctx.newPage(); await p.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
    await p.evaluate(async () => { const imgs = Array.from(document.querySelectorAll(".wt-related img")); imgs.forEach((i) => { i.loading = "eager"; }); await Promise.all(imgs.map((i) => i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; setTimeout(r, 3000); }))); });
    const r = await p.evaluate(() => { const self = document.querySelector("h1").textContent.trim(); const info = (c) => ({ terms: Array.from(c.querySelectorAll(".wp-block-post-terms a")).map((a) => a.textContent.trim()), title: (c.querySelector(".wt-rcard__title") || {}).textContent?.trim(), img: (() => { const i = c.querySelector("img"); return i ? { complete: i.complete, naturalWidth: i.naturalWidth } : null; })() });
      return { count: document.querySelectorAll(".wt-related:not(.wt-next) .wt-rcard").length, cards: Array.from(document.querySelectorAll(".wt-related:not(.wt-next) .wt-rcard")).map(info), next: Array.from(document.querySelectorAll(".wt-related.wt-next .wt-rcard")).map(info), self }; });
    await ctx.close(); return r; };
  const pc = await run(PC), sp = await run(SP);
  const ok = (r) => r.count === 6 && r.next.length === 1 && [...r.cards, ...r.next].every((c) => !c.terms.includes(defaultCat) && c.terms.length > 0 && c.title && c.title !== r.self && c.img && c.img.complete && c.img.naturalWidth > 0);
  // 既定カテゴリ名は wp-cli から取れたときだけ合格にする（取得失敗・--wpclidir 未指定は fail。代用名での合格を許さない。Astra 2 巡目）
  out.relatedNoFixture = { defaultCat, source, pc, sp, pass: source.startsWith("wp-cli:") && ok(pc) && ok(sp) };
}
// (f) lineIcon: LINE 導線の mark が描かれた吹き出しグリフ（mask あり・可視・16px 以上）で文字 "LINE" を含まない。qr 型・button 型の両方を PC / SP で検証（qr 型は PC = 追尾 1 / SP = QR 枠内ボタン + 追尾 2、button 型は PC / SP とも ボタン + 追尾 2）。
//     qr 型の説明文は PC / SP で切り替わる（要素数 2 ずつを要求し、空配列の every を許さない）
{
  const run = async (cfg, dev, variant) => { const ctx = await browser.newContext(cfg); const p = await ctx.newPage(); await p.goto(BASE + LP + `?wt=lp_sections:extended,lp_line:${variant},lp_fixed:line-sticky`, { waitUntil: "networkidle" });
    const r = await p.evaluate(([visSrc, glyphSrc]) => { const vis = eval(visSrc), glyph = eval(glyphSrc);
      const marks = Array.from(document.querySelectorAll(".wt-lp-line__mark")).filter(vis); const wrap = document.querySelector(".wt-lp-line__qrwrap");
      return { marks: marks.length, marksWithGlyph: marks.filter((m) => glyph(m.querySelector(".wt-i--bubble"))).length, marksWithText: marks.filter((m) => /LINE/.test(m.textContent)).length,
        pcTextVisible: wrap ? Array.from(wrap.querySelectorAll(".wt-only-pc")).map(vis) : [], spTextVisible: wrap ? Array.from(wrap.querySelectorAll(".wt-only-sp")).map(vis) : [], qrVisible: vis(wrap && wrap.querySelector(".wt-lp-line__qr")), spBtnVisible: vis(wrap && wrap.querySelector(".wt-lp-line__btn--sp")),
        buttonBlockVisible: vis(document.querySelector(".wt-lp-line--button")), qrBlockVisible: vis(wrap),
        stickyGlyph: glyph(document.querySelector(".wt-lp-fixed--line-sticky .wt-i--bubble")), btnTexts: Array.from(document.querySelectorAll(".wt-lp-line__btn")).filter(vis).map((a) => (a.getAttribute("aria-label") || "") + " " + a.textContent.trim()) }; }, [VIS_SRC, GLYPH_SRC]);
    await ctx.close(); return { dev, variant, ...r }; };
  const qrPc = await run(PC, "pc", "qr"), qrSp = await run(SP, "sp", "qr"), btnPc = await run(PC, "pc", "button"), btnSp = await run(SP, "sp", "button");
  const expectMarks = (r) => (r.variant === "qr" && r.dev === "pc" ? 1 : 2);
  const STICKY_TEXT = "LINE で相談する（追尾ボタン。PoC ではお問い合わせ帯へのアンカー） 相談する", BTN_TEXT = "LINE で友だち追加";
  const expectTexts = (r) => (r.variant === "qr" && r.dev === "pc" ? [STICKY_TEXT] : [BTN_TEXT, STICKY_TEXT]);
  // 文言は完全一致（部分一致 /LINE/ は OFFLINE 等を通すため不可。Astra 3 巡目）
  const base = (r) => r.marks === expectMarks(r) && r.marksWithGlyph === r.marks && r.marksWithText === 0 && r.stickyGlyph && JSON.stringify(r.btnTexts.map((t) => t.trim())) === JSON.stringify(expectTexts(r));
  const qrOk = qrPc.qrBlockVisible && !qrPc.buttonBlockVisible && qrSp.qrBlockVisible && !qrSp.buttonBlockVisible
    && qrPc.pcTextVisible.length === 2 && qrPc.pcTextVisible.every(Boolean) && qrPc.spTextVisible.length === 2 && qrPc.spTextVisible.every((v) => !v) && qrPc.qrVisible && !qrPc.spBtnVisible
    && qrSp.spTextVisible.length === 2 && qrSp.spTextVisible.every(Boolean) && qrSp.pcTextVisible.length === 2 && qrSp.pcTextVisible.every((v) => !v) && !qrSp.qrVisible && qrSp.spBtnVisible;
  const btnOk = btnPc.buttonBlockVisible && !btnPc.qrBlockVisible && btnSp.buttonBlockVisible && !btnSp.qrBlockVisible;
  out.lineIcon = { qrPc, qrSp, btnPc, btnSp, pass: [qrPc, qrSp, btnPc, btnSp].every(base) && qrOk && btnOk };
}
// (g) snsIcons: footer の SNS 4 導線と記事末 icons-row 3 導線。グリフが描かれている（はてなは文字 "B!"）・aria-label・44×44・rel に nofollow（footer）・JS 無効でも同じ
{
  const run = async (cfg, js) => { const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage(); await p.goto(BASE + ARTICLE + "?wt=tail_share:icons-row", { waitUntil: js ? "networkidle" : "load" });
    const r = await p.evaluate(([visSrc, glyphSrc]) => { const vis = eval(visSrc), glyph = eval(glyphSrc); const box = (el) => { const r = el.getBoundingClientRect(); return [Math.round(r.width), Math.round(r.height)]; };
      const f = Array.from(document.querySelectorAll(".wt-footer .wt-social a")).map((a) => ({ visible: vis(a), label: a.getAttribute("aria-label") || "", glyph: glyph(a.querySelector(".wt-i")), digits: /^\s*\d\s*$/.test(a.textContent), size: box(a), rel: (a.getAttribute("rel") || "").split(/\s+/) }));
      const t = Array.from(document.querySelectorAll(".wt-tail-icons a")).map((a) => ({ visible: vis(a), label: a.getAttribute("aria-label") || "", key: a.getAttribute("data-wt-sns"), glyph: a.querySelector(".wt-i") ? glyph(a.querySelector(".wt-i")) : a.textContent.trim() === "B!", size: box(a) }));
      return { footer: f, tail: t }; }, [VIS_SRC, GLYPH_SRC]);
    await ctx.close(); return r; };
  const res = { spJs: await run(SP, true), pcJs: await run(PC, true), spNoJs: await run(SP, false) };
  const ok = (r) => r.footer.length === 4 && r.footer.every((a) => a.visible && a.label.length > 0 && a.glyph && !a.digits && a.size[0] >= 44 && a.size[1] >= 44 && a.rel.includes("nofollow")) && r.tail.length === 3 && r.tail.map((a) => a.key).join(",") === "x,line,hatena" && r.tail.every((a) => a.visible && a.label.length > 0 && a.glyph && a.size[0] >= 44 && a.size[1] >= 44);
  out.snsIcons = { ...res, pass: ok(res.spJs) && ok(res.pcJs) && ok(res.spNoJs) };
}

// 2026-09-06 PO 反応 17 回目 WT-EVT-0277: HP 面 / イベントページの検査（Claude 案）
const HOME = "/";
const EVENT = "/event/";
// (h) homeFace: 各軸の全型で「body に軸 class・当該型だけ可視・他型は非可視」。hero は 6 型、CTA 4 型（none は hero 内の CTA が 0）、区間セット 3 種の表示区間と順序、news 4 型、contact 5 型、fixed 4 型（SP/PC の出し分け）。SP/PC/SP JS 無効
{
  const AX = {
    home_hero: ["text-only", "slider", "fullbleed", "split", "article-grid", "video", "cards-carousel", "product-shot", "search-box"], // 段 8: +3
    home_hero_cta: ["double", "single", "none", "tel-button", "search"], // 段 8: +search（form ではなく role=search の div。暗黙送信なし）
    home_news: ["list-with-date", "tabs", "cards", "none"],
    home_contact: ["tel-form", "form-only", "tel-only", "line", "none", "double-cta"], // 段 8: +double-cta（フォームなし）
    home_fixed: ["none", "float-cta", "sp-bottom-bar", "float-tel"],
  };
  // 段 8: shop-school / school-org を新設（台帳 v2 の区分別上位区間）。段 9（WT-EVT-0288）: corporate に greeting、service に logos
  const SETS = { corporate: ["news", "greeting", "service-cards", "features", "numbers", "cases", "company", "access", "contact"], service: ["service-cards", "features", "numbers", "cases", "logos", "price", "faq", "cta-band", "contact"], media: ["article-grid", "category-cards", "ranking", "banner-row", "news", "cta-band", "contact"], "shop-school": ["news", "service-cards", "features", "stores", "events", "price", "faq", "recruit", "banner-row", "access", "contact"], "school-org": ["greeting", "news", "features", "service-cards", "events", "gallery", "sns", "history", "logos", "banner-row", "cta-band", "contact"] };
  const read = async (cfg, dev, js) => {
    const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage(); const results = [];
    for (const [axis, values] of Object.entries(AX)) {
      for (const v of values) {
        await p.goto(BASE + HOME + `?wt=${axis}:${v}`, { waitUntil: js ? "networkidle" : "load" });
        const r = await p.evaluate(([axis, v, values, visSrc]) => {
          const vis = eval(visSrc); const cls = axis.replace(/_/g, "-");
          const body = document.body.classList.contains(`wt-${cls}-${v}`);
          const prefix = { home_hero: ".wt-home-hero--", home_hero_cta: ".wt-home-hero .wt-home-cta--", home_news: ".wt-home-news--", home_contact: ".wt-home-contact--", home_fixed: ".wt-home-fixed--" }[axis];
          const shown = values.filter((x) => Array.from(document.querySelectorAll(prefix + x)).some(vis));
          const heroVisible = Array.from(document.querySelectorAll(".wt-home-hero")).filter(vis).length;
          const taps = Array.from(document.querySelectorAll(".wt-home a, .wt-home button, .wt-home input, .wt-home select, .wt-home textarea, .wt-home-fixed")).filter(vis);
          const below44 = taps.filter((el) => { const r = el.getBoundingClientRect(); const inline = el.tagName === "A" && getComputedStyle(el).display === "inline" && el.parentElement && /^(P|LI|TD|B|SPAN)$/.test(el.parentElement.tagName); const lab = el.tagName === "INPUT" && /^(checkbox|radio)$/.test(el.type) ? el.closest("label") : null; const labOk = !!lab && lab.getBoundingClientRect().height >= 44 && lab.getBoundingClientRect().width >= 44 && Math.min(r.width, r.height) >= 24; return !inline && !labOk && Math.min(r.width, r.height) < 44; }).map((el) => (el.className || el.tagName).toString().slice(0, 60));
          const forms = Array.from(document.querySelectorAll(".wt-home form")).filter((f) => !f.closest(".wt-side")).filter(vis) /* 段 10b: 既定で出る共通サイドバーの検索フォームは sideFace / sideDefaults で検査 */.map((f) => ({ method: (f.getAttribute("method") || "get").toLowerCase(), action: f.getAttribute("action") || "", submit: f.querySelectorAll("button:not([type]), button[type=submit], input[type=submit], input[type=image]").length, inputs: f.querySelectorAll("input:not([type=hidden]), textarea, select").length, labelled: Array.from(f.querySelectorAll("input:not([type=hidden]), textarea, select")).every((i) => i.id && f.querySelector(`label[for="${i.id}"]`)) }));
          const anchors = Array.from(document.querySelectorAll(".wt-home a[href^='#'], .wt-home-fixed[href^='#']")).filter(vis).map((a) => { const id = a.getAttribute("href").slice(1); const t = id ? document.getElementById(id) : null; return { href: "#" + id, targetVisible: !!t && vis(t) }; }); const deadAnchors = anchors.filter((a) => !a.targetVisible).map((a) => a.href);
          const sliderNav = document.querySelector(".wt-home-hero--slider .wt-home-slider__nav"); const sliderNavVisible = axis === "home_hero" && v === "slider" ? vis(sliderNav) && sliderNav.querySelectorAll("button").length >= 4 : null;
          return { body, shown, heroVisible, taps: taps.length, below44, forms, h1: document.querySelectorAll("h1").length, h1Visible: Array.from(document.querySelectorAll("h1")).filter(vis).length, sliderNavVisible, anchors: anchors.length, deadAnchors };
        }, [axis, v, values, VIS_SRC]);
        const expectShown = v === "none" ? [] : (axis === "home_fixed" && dev === "pc" && v !== "float-cta" ? [] : [v]);
        const expectForms = axis === "home_contact" ? (v === "tel-form" || v === "form-only" ? 1 : 0) : 1; // 既定 home_contact=tel-form はフォーム 1 つ
        const pass = r.body && JSON.stringify(r.shown) === JSON.stringify(expectShown) && r.heroVisible === 1 && r.h1Visible === 1 && r.below44.length === 0 && r.forms.length === expectForms && r.forms.every((f) => f.submit === 0 && f.inputs >= 2 && f.method === "get" && /^#/.test(f.action) && f.labelled) && (r.sliderNavVisible === null || r.sliderNavVisible === js) && r.anchors >= 1 && r.deadAnchors.length === 0;
        results.push({ dev, js, axis, v, expectForms, ...r, pass });
      }
    }
    // 区間セット: 表示区間の集合と順序
    const sets = [];
    for (const [set, expect] of Object.entries(SETS)) {
      await p.goto(BASE + HOME + `?wt=home_sections:${set}`, { waitUntil: js ? "networkidle" : "load" });
      const order = await p.evaluate((visSrc) => { const vis = eval(visSrc); return Array.from(document.querySelectorAll(".wt-home__sections > .wt-home__section")).filter(vis).sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top).map((s) => Array.from(s.classList).find((c) => c.startsWith("wt-home__section--")).replace("wt-home__section--", "")); }, VIS_SRC);
      const anchors = await p.evaluate((visSrc) => { const vis = eval(visSrc); return Array.from(document.querySelectorAll(".wt-home a[href^='#'], .wt-home-fixed[href^='#']")).filter(vis).map((a) => { const id = a.getAttribute("href").slice(1); const t = id ? document.getElementById(id) : null; return { href: "#" + id, text: a.textContent.trim().slice(0, 20), targetVisible: !!t && vis(t) }; }); }, VIS_SRC);
      const deadAnchors = anchors.filter((a) => !a.targetVisible);
      // 段 8: 区間内の固定ページ用パーツ（wt-part）が可視で、見出し h2 を持つこと。可視の画像に読込失敗（complete かつ naturalWidth 0）がないこと
      const parts = await p.evaluate((visSrc) => { const vis = eval(visSrc); return Array.from(document.querySelectorAll(".wt-home__section .wt-part")).filter(vis).map((el) => ({ part: Array.from(el.classList).find((c) => c.startsWith("wt-part--")), h2: !!el.querySelector("h2") && vis(el.querySelector("h2")), broken: Array.from(el.querySelectorAll("img")).filter((i) => vis(i) && i.complete && i.naturalWidth === 0).length })); }, VIS_SRC);
      const expectParts = { corporate: 1, service: 1, media: 0, "shop-school": 2, "school-org": 6 }[set];
      sets.push({ dev, js, set, order, anchors: anchors.length, deadAnchors, parts, pass: JSON.stringify(order) === JSON.stringify(expect) && anchors.length >= 2 && deadAnchors.length === 0 && parts.length === expectParts && parts.every((x) => x.h2 && x.broken === 0) });
    }
    // 段 8 Astra 是正: hero × 区間セットの交差（hero 内の導線がセットで非表示の区間を指さない）。9 hero × 5 セット
    const cross = [];
    for (const hero of AX.home_hero) for (const set of Object.keys(SETS)) {
      await p.goto(BASE + HOME + `?wt=home_hero:${hero},home_sections:${set}`, { waitUntil: js ? "networkidle" : "load" });
      const r = await p.evaluate((visSrc) => { const vis = eval(visSrc); const anchors = Array.from(document.querySelectorAll(".wt-home-hero a[href^='#']")).filter(vis).map((a) => { const id = a.getAttribute("href").slice(1); const t = id ? document.getElementById(id) : null; return { href: "#" + id, ok: !!t && vis(t) }; }); const links = Array.from(document.querySelectorAll(".wt-home-hero a[href]")).filter(vis).length; return { anchors: anchors.length, dead: anchors.filter((a) => !a.ok).map((a) => a.href), links }; }, VIS_SRC);
      cross.push({ dev, js, hero, set, ...r, pass: r.dead.length === 0 && r.links >= 1 });
    }
    await ctx.close(); return { results, sets, cross };
  };
  const sp = await read(SP, "sp", true), pc = await read(PC, "pc", true), spNoJs = await read(SP, "sp", false);
  const all = [...sp.results, ...pc.results, ...spNoJs.results, ...sp.sets, ...pc.sets, ...spNoJs.sets, ...sp.cross, ...pc.cross, ...spNoJs.cross];
  out.homeFace = { sp, pc, spNoJs, pass: all.length === (28 * 3 + 15 + 45 * 3) && all.every((x) => x.pass) };
}
// (i) homeHeroContrast: 白文字を置く hero（fullbleed / slider）は ::before の gradient を stop ごとに解析し、文字矩形の上端位置（下端からの %）の α を線形補間して白背景でも 4.5:1（α ≥ .82）を要求。文字は hero 矩形内。テキスト hero は文字色コントラスト
{
  const rows = [];
  for (const [cfg, dev, js] of [[PC, "pc", true], [SP, "sp", true], [SP, "sp", false]]) { const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage();
  for (const v of ["fullbleed", "slider"]) {
    await p.goto(BASE + HOME + `?wt=home_hero:${v}`, { waitUntil: js ? "networkidle" : "load" });
    rows.push(await p.evaluate((v) => { const el = document.querySelector(v === "slider" ? ".wt-home-slider__slide" : ".wt-home-hero--fullbleed"); const bg = getComputedStyle(el, "::before").backgroundImage; const stops = Array.from(bg.matchAll(/rgba\(0, 0, 0, ([\d.]+)\) ([\d.]+)%/g)).map((x) => [parseFloat(x[2]), parseFloat(x[1])]); const alphaAt = (pct) => { if (!stops.length) return null; if (pct <= stops[0][0]) return stops[0][1]; for (let i = 1; i < stops.length; i++) { if (pct <= stops[i][0]) { const [p0, a0] = stops[i - 1], [p1, a1] = stops[i]; return a0 + (a1 - a0) * ((pct - p0) / (p1 - p0)); } } return stops[stops.length - 1][1]; }; const title = el.querySelector("h1, h2"); const tr = title.getBoundingClientRect(); const er = el.getBoundingClientRect(); const topPct = (er.bottom - tr.top) / er.height * 100; const alpha = alphaAt(topPct); const img = el.querySelector("img"); const zi = (e, ps) => { const z = getComputedStyle(e, ps).zIndex; return z === "auto" ? 0 : parseInt(z, 10); }; const scrimAboveImg = !!img && zi(el, "::before") > zi(img) && getComputedStyle(el).isolation === "isolate"; const cx = tr.left + Math.min(20, tr.width / 2), cy = tr.top + Math.min(10, tr.height / 2); const hit = document.elementFromPoint(cx, cy); const hitInTitle = !!hit && (hit === title || title.contains(hit)); const ctas = Array.from(el.querySelectorAll(".wt-home-cta a")).filter((a) => a.getBoundingClientRect().width > 0 && getComputedStyle(a).display !== "none").map((a) => { const cs = getComputedStyle(a); return { text: a.textContent.trim().slice(0, 12), color: cs.color, bg: cs.backgroundColor, border: cs.borderTopColor }; }); return { v, stops, ctas, titleTopPctFromBottom: Math.round(topPct * 10) / 10, alphaAtTitleTop: alpha === null ? null : Math.round(alpha * 1000) / 1000, titleColor: getComputedStyle(title).color, scrimAboveImg, hitInTitle, pass: alpha !== null && (1.05 / ((1 - alpha) + 0.05)) >= 4.5 && getComputedStyle(title).color === "rgb(255, 255, 255)" && tr.top >= er.top && tr.bottom <= er.bottom && scrimAboveImg && hitInTitle }; }, v));
    // CTA: 塗りボタンは文字 / 背景 4.5:1、透明ボタンはスクリム（α 補間後の実効背景 = 黒 α）上で文字 4.5:1 かつ枠線が文字色
    { const row = rows[rows.length - 1]; const a = row.alphaAtTitleTop ?? 0; const scrimLum = (1 - a) * 1.0; const ctaOk = row.ctas.length >= 1 && row.ctas.every((c) => { const fg = lum(parse(c.color).rgb); const filled = !/rgba\(0, 0, 0, 0\)|transparent/.test(c.bg); return filled ? ratio(fg, lum(parse(c.bg).rgb)) >= 4.5 : ratio(fg, scrimLum) >= 4.5 && c.border === c.color; }); row.ctaOk = ctaOk; row.pass = row.pass && ctaOk; }
  }
  for (const v of ["text-only", "split", "video"]) {
    await p.goto(BASE + HOME + `?wt=home_hero:${v}`, { waitUntil: js ? "networkidle" : "load" });
    const r = await p.evaluate((v) => { const el = document.querySelector(`.wt-home-hero--${v}`); const bg = (e) => { for (; e; e = e.parentElement) { const c = getComputedStyle(e).backgroundColor; if (c && !/rgba\(0, 0, 0, 0\)|transparent/.test(c)) return c; } return "rgb(255, 255, 255)"; }; const t = el.querySelector("h1"), l = el.querySelector(".wt-home-hero__lead"); return { v, title: [getComputedStyle(t).color, bg(t)], lead: [getComputedStyle(l).color, bg(l)] }; }, v);
    const ok = ([fg, b]) => { const c = parse(fg), bb = parse(b); return ratio(lum(c.rgb), lum(bb.rgb)) >= 4.5; };
    rows.push({ ...r, pass: ok(r.title) && ok(r.lead) });
  }
  await ctx.close(); }
  out.homeHeroContrast = { rows: rows.map((r, i) => ({ dev: ["pc", "sp", "sp"][Math.floor(i / 5)], js: i < 10, ...r })), pass: rows.length === 15 && rows.every((r) => r.pass) };
}
// (j) homeFixedOverlap: 固定導線がフッターの操作要素・追尾要素と重ならず viewport 内（SP: sp-bottom-bar / float-cta / float-tel、PC: float-cta）
{
  const check = async (cfg, dev, variants, face = "home") => { const ctx = await browser.newContext(cfg); const p = await ctx.newPage(); const rows = [];
    for (const v of variants) { await p.goto(BASE + (face === "home" ? HOME : EVENT) + `?wt=${face}_fixed:${v},footer_totop:button`, { waitUntil: "networkidle" }); await p.evaluate(() => scrollTo(0, document.body.scrollHeight)); await p.waitForTimeout(300);
      rows.push(await p.evaluate(([v, face]) => { const vis = (el) => el && el.getBoundingClientRect().width > 0 && getComputedStyle(el).display !== "none"; const el = document.querySelector(`.wt-${face}-fixed--${v}`); if (!vis(el)) return { face, v, visible: false, pass: false }; const r = el.getBoundingClientRect(); const others = Array.from(document.querySelectorAll(".wt-totop, .wt-share--float, .wt-footer a, .wt-footer button")).filter(vis).filter((o) => o !== el && !el.contains(o)); const overlaps = others.filter((o) => { const q = o.getBoundingClientRect(); return !(q.right <= r.left || q.left >= r.right || q.bottom <= r.top || q.top >= r.bottom); }).map((o) => o.className.toString().slice(0, 50)); return { face, v, visible: true, inViewport: r.top >= 0 && r.bottom <= innerHeight && r.left >= 0 && r.right <= innerWidth, overlaps, pass: r.top >= 0 && r.bottom <= innerHeight && r.left >= 0 && r.right <= innerWidth && overlaps.length === 0 }; }, [v, face])); }
    await ctx.close(); return rows; };
  const sp = [...await check(SP, "sp", ["float-cta", "sp-bottom-bar", "float-tel"]), ...await check(SP, "sp", ["sp-bottom-bar", "float-apply"], "event")], pc = [...await check(PC, "pc", ["float-cta"]), ...await check(PC, "pc", ["float-apply"], "event")];
  out.homeFixedOverlap = { sp, pc, pass: sp.length === 5 && pc.length === 2 && [...sp, ...pc].every((r) => r.pass) };
}
// (k) eventFace: 各軸の全型（hero 4 / info 4 / schedule 4 / speakers 4 / apply 4 / status 4 / map 3 / fixed 3 / share 3）で軸 class・当該型だけ可視、closed-notice で申込導線が消える、フォームは非送信、44px。SP/PC/SP JS 無効
{
  const AX = { event_hero: ["photo-overlay", "key-visual", "date-place-block", "text-only"], event_info: ["inline-text", "table", "icon-list", "none"], event_schedule: ["none", "table", "timeline", "accordion"], event_speakers: ["none", "cards-photo", "list", "single-profile"], event_apply: ["inline-form", "external-form", "ticket-link", "closed-notice", "receipt-upload", "postcard", "messaging-app"], event_status: ["none", "open", "few-seats", "ended"], event_map: ["none", "static-image", "text-only", "embed"], event_fixed: ["none", "sp-bottom-bar", "float-apply"], event_share: ["none", "icons", "add-to-calendar"], event_sections: ["seminar", "seminar-classic", "conference", "festival", "campaign"] }; // 段 8: apply +3、区間セット 5 種。段 9: seminar = v2 構成（既定）、seminar-classic = 従来構成
  // 段 8: 区間セットごとの表示区間と順序（CSS の order と一致させる）
  const ESETS = { seminar: ["info", "overview", "audience", "schedule", "speakers", "tickets", "apply", "access", "faq", "organizer", "notes"], "seminar-classic": ["info", "overview", "schedule", "speakers", "tickets", "apply", "access", "faq", "sponsors", "past", "notes"], conference: ["info", "overview", "schedule", "speakers", "tickets", "sponsors", "apply", "access", "past", "organizer", "notes"], festival: ["info", "overview", "countdown", "schedule", "gallery", "apply", "access", "faq", "sponsors", "past", "organizer", "notes"], campaign: ["info", "overview", "prizes", "products", "entry", "apply", "judges", "organizer", "notes"] };
  const PREFIX = { event_sections: ".wt-event-sections--",  event_hero: ".wt-event-hero--", event_info: ".wt-event-info--", event_schedule: ".wt-event-schedule--", event_speakers: ".wt-event-speakers--", event_apply: ".wt-event-apply--", event_status: ".wt-event-status--", event_map: ".wt-event-map--", event_fixed: ".wt-event-fixed--", event_share: ".wt-event-share--" };
  // embed の未設定状態を先に用意する（wp-cli 必須。option を消し、消えたことを確認。取れなければ eventFace は fail）
  const wpE = WPCLIDIR ? (a) => execFileSync("docker", ["compose", "run", "--rm", "-T", "wpcli", ...a], { cwd: WPCLIDIR, encoding: "utf8" }) : null;
  const optionUnset = () => { try { wpE(["option", "get", "helix_wt_event_map_embed_url"]); return false; } catch (_) { return true; } };
  let unsetPrepared = false; if (wpE) { try { wpE(["option", "delete", "helix_wt_event_map_embed_url"]); } catch (_) { /* 未設定なら delete は失敗する */ } unsetPrepared = optionUnset(); }
  const baseHost = new URL(BASE).host;
  const read = async (cfg, dev, js) => { const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage(); const results = [];
    let external = []; p.on("request", (req) => { try { const u = new URL(req.url()); if (/^https?:$/.test(u.protocol) && u.host !== baseHost) external.push(u.host); } catch (_) { /* data: 等 */ } }); // 実通信: 同一ホスト以外への要求を全行で数える
    for (const [axis, values] of Object.entries(AX)) for (const v of values) {
      external = [];
      await p.goto(BASE + EVENT + `?wt=${axis}:${v}`, { waitUntil: js ? "networkidle" : "load" });
      const r = await p.evaluate(([axis, v, values, prefix, visSrc]) => { const vis = eval(visSrc); const cls = axis.replace(/_/g, "-");
        const shown = values.filter((x) => Array.from(document.querySelectorAll(prefix + x)).some(vis));
        const taps = Array.from(document.querySelectorAll(".wt-event a, .wt-event button, .wt-event input, .wt-event select, .wt-event-fixed")).filter(vis);
        const below44 = taps.filter((el) => { const r = el.getBoundingClientRect(); const inline = el.tagName === "A" && getComputedStyle(el).display === "inline" && el.parentElement && /^(P|LI|TD|B|SPAN)$/.test(el.parentElement.tagName); const lab = el.tagName === "INPUT" && /^(checkbox|radio)$/.test(el.type) ? el.closest("label") : null; const labOk = !!lab && lab.getBoundingClientRect().height >= 44 && lab.getBoundingClientRect().width >= 44 && Math.min(r.width, r.height) >= 24; return !inline && !labOk && Math.min(r.width, r.height) < 44; }).map((el) => (el.className || el.tagName).toString().slice(0, 60));
        const forms = Array.from(document.querySelectorAll(".wt-event form")).filter(vis).map((f) => ({ method: (f.getAttribute("method") || "get").toLowerCase(), action: f.getAttribute("action") || "", submit: f.querySelectorAll("button:not([type]), button[type=submit], input[type=submit], input[type=image]").length, inputs: f.querySelectorAll("input:not([type=hidden]), select, textarea").length, labelled: Array.from(f.querySelectorAll("input:not([type=hidden]):not([type=checkbox]), select")).every((i) => i.id && f.querySelector(`label[for="${i.id}"]`)) }));
        const applyLinks = Array.from(document.querySelectorAll(".wt-event-apply-link, .wt-event-fixed")).filter(vis).length;
        const anchors = Array.from(document.querySelectorAll(".wt-event a[href^='#'], .wt-event-fixed a[href^='#'], a.wt-event-fixed[href^='#']")).filter(vis).map((a) => { const id = a.getAttribute("href").slice(1); const t = id ? document.getElementById(id) : null; return { href: "#" + id, targetVisible: !!t && vis(t) }; }); const deadAnchors = anchors.filter((a) => !a.targetVisible).map((a) => a.href);
        const sectionOrder = Array.from(document.querySelectorAll(".wt-event__sections > .wt-event__section")).filter(vis).sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top).map((s) => Array.from(s.classList).find((c) => c.startsWith("wt-event__section--")).replace("wt-event__section--", ""));
        const parts = Array.from(document.querySelectorAll(".wt-event__section .wt-part")).filter(vis).map((el) => ({ part: Array.from(el.classList).find((c) => c.startsWith("wt-part--")), h2: !!el.querySelector("h2") && vis(el.querySelector("h2")), broken: Array.from(el.querySelectorAll("img")).filter((i) => vis(i) && i.complete && i.naturalWidth === 0).length }));
        return { body: document.body.classList.contains(`wt-${cls}-${v}`), shown, sectionOrder, parts, heroVisible: Array.from(document.querySelectorAll(".wt-event-hero")).filter(vis).length, h1Visible: Array.from(document.querySelectorAll("h1")).filter(vis).length, below44, forms, applyLinks, hasImgRole: axis === "event_map" && v === "static-image" ? !!document.querySelector(".wt-event-map--static-image img[alt]") : null, embed: axis === "event_map" && v === "embed" ? (() => { const box = document.querySelector(".wt-event-map--embed"); const f = box ? box.querySelector("iframe") : null; const ext = Array.from(document.querySelectorAll("iframe, script[src], img[src]")).map((e) => e.src || "").filter((s) => s && new URL(s, location.href).host !== location.host); return { state: box ? box.getAttribute("data-wt-embed") : null, iframe: !!f, lazy: f ? f.getAttribute("loading") === "lazy" : null, title: f ? (f.getAttribute("title") || "").length > 0 : null, srcHost: f ? new URL(f.src).host : null, sameHost: f ? new URL(f.src).host === location.host : null, unsetNote: !f && !!(box && box.querySelector(".wt-event-map__unset")), externalHosts: ext.slice(0, 5) }; })() : null, anchors: anchors.length, deadAnchors }; }, [axis, v, values, PREFIX[axis], VIS_SRC]);
      let expectShown = v === "none" || axis === "event_sections" ? [] : [v];
      if (axis === "event_fixed" && dev === "pc" && v === "sp-bottom-bar") expectShown = [];
      // 既定は event_info=inline-text / event_schedule=none / event_speakers=none / event_map=none。軸 none で隠れる区間はセットの並びから除く（当該軸の行はその値、他の行は既定）
      const hiddenBy = { info: "event_info", schedule: "event_schedule", speakers: "event_speakers", access: "event_map" }; const axisDefault = { event_info: "inline-text", event_schedule: "none", event_speakers: "none", event_map: "none" };
      const expectOrder = ESETS[axis === "event_sections" ? v : "seminar"].filter((sec) => { const ax = hiddenBy[sec]; if (!ax) return true; const val = axis === ax ? v : axisDefault[ax]; return val !== "none"; });
      const expectForms = axis === "event_apply" ? (v === "inline-form" ? 1 : 0) : 1; // 既定 event_apply=inline-form はフォーム 1 つ
      r.externalRequests = Array.from(new Set(external)); // DOM の src 列挙ではなく実際に出た要求
      let pass = r.body && r.externalRequests.length === 0 && JSON.stringify(r.shown) === JSON.stringify(expectShown) && r.heroVisible === 1 && r.h1Visible === 1 && r.below44.length === 0 && r.forms.length === expectForms && r.forms.every((f) => f.submit === 0 && f.inputs >= 2 && f.method === "get" && /^#/.test(f.action) && f.labelled) && (r.hasImgRole === null || r.hasImgRole === true) && r.deadAnchors.length === 0 && (r.embed === null || (unsetPrepared && r.embed.state === "unset" && r.embed.externalHosts.length === 0 && r.embed.unsetNote && !r.embed.iframe)); // 未設定状態を明示判定（設定済みでは通さない）
      if (axis === "event_apply") pass = pass && (v === "closed-notice" ? r.applyLinks === 0 : r.applyLinks >= 1);
      pass = pass && JSON.stringify(r.sectionOrder) === JSON.stringify(expectOrder) && r.parts.every((x) => x.h2 && x.broken === 0) && r.parts.length === expectOrder.filter((x) => ["audience", "organizer", "prizes", "products", "entry", "judges", "gallery", "countdown"].includes(x)).length; // 段 8: 区間の集合と順序、パーツ区間の見出し・画像
      results.push({ dev, js, axis, v, expectForms, expectOrder, ...r, pass });
    }
    await ctx.close(); return results; };
  const sp = await read(SP, "sp", true), pc = await read(PC, "pc", true), spNoJs = await read(SP, "sp", false);
  // 段 9（WT-EVT-0288、Astra 是正）: 既定値そのものの検査。?wt 指定なしの /event/ で hero が date-place-block、区間セットが seminar（v2 構成）であること（PC / SP / SP JS 無効）
  const defaults = [];
  for (const [cfg, dev, js] of [[PC, "pc", true], [SP, "sp", true], [SP, "sp", false]]) { const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage(); await p.goto(BASE + EVENT, { waitUntil: js ? "networkidle" : "load" });
    const r = await p.evaluate((visSrc) => { const vis = eval(visSrc); const heroes = Array.from(document.querySelectorAll(".wt-event-hero")).filter(vis).map((e) => Array.from(e.classList).find((c) => c.startsWith("wt-event-hero--")).replace("wt-event-hero--", "")); const order = Array.from(document.querySelectorAll(".wt-event__sections > .wt-event__section")).filter(vis).sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top).map((s) => Array.from(s.classList).find((c) => c.startsWith("wt-event__section--")).replace("wt-event__section--", "")); return { heroes, bodyHero: document.body.classList.contains("wt-event-hero-date-place-block"), bodySet: document.body.classList.contains("wt-event-sections-seminar"), order, dateBox: !!document.querySelector(".wt-event-hero--date-place-block .wt-event-date") && vis(document.querySelector(".wt-event-hero--date-place-block .wt-event-date")) }; }, VIS_SRC);
    await ctx.close();
    const expectOrder = ESETS.seminar.filter((sec) => !["schedule", "speakers", "access"].includes(sec)); // 既定軸 none で隠れる区間を除く
    defaults.push({ dev, js, ...r, expectOrder, pass: JSON.stringify(r.heroes) === JSON.stringify(["date-place-block"]) && r.bodyHero && r.bodySet && r.dateBox && JSON.stringify(r.order) === JSON.stringify(expectOrder) }); }
  // embed の「設定あり」状態: option に同一ホストの URL を入れて iframe が遅延読込・title 付き・同一ホストで出ること（wp-cli 必須。取れなければ fail）。終了時に option を消す
  let embedSet = { source: "unavailable", unsetPrepared, pass: false };
  if (wpE) {
    const wp = wpE; let cleanupOk = false;
    try {
      wp(["option", "update", "helix_wt_event_map_embed_url", BASE + "/lp/"]);
      const ctx2 = await browser.newContext(PC); const p2 = await ctx2.newPage(); const ext2 = []; p2.on("request", (req) => { try { const u = new URL(req.url()); if (/^https?:$/.test(u.protocol) && u.host !== baseHost) ext2.push(u.host); } catch (_) { /* data: */ } });
      await p2.goto(BASE + EVENT + "?wt=event_map:embed", { waitUntil: "networkidle" });
      const r = await p2.evaluate(() => { const box = document.querySelector(".wt-event-map--embed"); const f = box ? box.querySelector("iframe") : null; const ext = Array.from(document.querySelectorAll("iframe, script[src], img[src]")).map((e) => e.src || "").filter((s) => s && new URL(s, location.href).host !== location.host); return { state: box ? box.getAttribute("data-wt-embed") : null, iframe: !!f, lazy: f ? f.getAttribute("loading") === "lazy" : null, title: f ? (f.getAttribute("title") || "").length > 0 : null, sameHost: f ? new URL(f.src).host === location.host : null, visible: f ? f.getBoundingClientRect().width > 200 : false, externalHosts: ext.slice(0, 5) }; });
      r.externalRequests = Array.from(new Set(ext2)); await ctx2.close();
      embedSet = { source: "wp-cli:option helix_wt_event_map_embed_url", unsetPrepared, ...r, pass: unsetPrepared && r.state === "set" && r.iframe && r.lazy && r.title && r.sameHost && r.visible && r.externalHosts.length === 0 && r.externalRequests.length === 0 };
    } catch (e) { embedSet = { source: `wp-cli failed: ${String(e).slice(0, 120)}`, unsetPrepared, pass: false }; }
    finally {
      // 後始末: option を消し、消えたこと（wp option get が失敗）と画面が未設定表示に戻ることを確認。失敗なら不合格
      try { wp(["option", "delete", "helix_wt_event_map_embed_url"]); } catch (_) { /* 既に無ければ失敗する */ }
      cleanupOk = optionUnset();
      if (cleanupOk) { const ctx3 = await browser.newContext(PC); const p3 = await ctx3.newPage(); await p3.goto(BASE + EVENT + "?wt=event_map:embed", { waitUntil: "load" }); cleanupOk = await p3.evaluate(() => { const b = document.querySelector(".wt-event-map--embed"); return !!b && b.getAttribute("data-wt-embed") === "unset" && !b.querySelector("iframe"); }); await ctx3.close(); }
      embedSet.cleanupOk = cleanupOk; embedSet.pass = embedSet.pass && cleanupOk;
    }
  }
  const all = [...sp, ...pc, ...spNoJs];
  out.eventFace = { sp, pc, spNoJs, defaults, embedSet, pass: all.length === 42 * 3 && all.every((x) => x.pass) && defaults.length === 3 && defaults.every((x) => x.pass) && embedSet.pass };
}
// (l) eventHeroContrast: photo-overlay のスクリム α（下端 .88）と白文字、他 3 型の文字色 4.5:1、受付状態バッジ 3 型の文字コントラスト
{
  const rows = [];
  for (const [cfg, dev, js] of [[PC, "pc", true], [SP, "sp", true], [SP, "sp", false]]) { const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage();
  await p.goto(BASE + EVENT + "?wt=event_hero:photo-overlay,event_status:open", { waitUntil: js ? "networkidle" : "load" });
  rows.push(await p.evaluate(() => { const el = document.querySelector(".wt-event-hero--photo-overlay"); const bg = getComputedStyle(el, "::before").backgroundImage; const stops = Array.from(bg.matchAll(/rgba\(0, 0, 0, ([\d.]+)\) ([\d.]+)%/g)).map((x) => [parseFloat(x[2]), parseFloat(x[1])]); const alphaAt = (pct) => { if (!stops.length) return null; if (pct <= stops[0][0]) return stops[0][1]; for (let i = 1; i < stops.length; i++) { if (pct <= stops[i][0]) { const [p0, a0] = stops[i - 1], [p1, a1] = stops[i]; return a0 + (a1 - a0) * ((pct - p0) / (p1 - p0)); } } return stops[stops.length - 1][1]; }; const t = el.querySelector("h1"); const title = t; const tr = t.getBoundingClientRect(), er = el.getBoundingClientRect(); const topPct = (er.bottom - tr.top) / er.height * 100; const a = alphaAt(topPct); const img = el.querySelector("img"); const zi = (e, ps) => { const z = getComputedStyle(e, ps).zIndex; return z === "auto" ? 0 : parseInt(z, 10); }; const scrimAboveImg = !!img && zi(el, "::before") > zi(img) && getComputedStyle(el).isolation === "isolate"; const cx = tr.left + Math.min(20, tr.width / 2), cy = tr.top + Math.min(10, tr.height / 2); const hit = document.elementFromPoint(cx, cy); const hitInTitle = !!hit && (hit === title || title.contains(hit)); return { v: "photo-overlay", stops, titleTopPctFromBottom: Math.round(topPct * 10) / 10, alphaAtTitleTop: a === null ? null : Math.round(a * 1000) / 1000, titleColor: getComputedStyle(t).color, scrimAboveImg, hitInTitle, pass: a !== null && 1.05 / ((1 - a) + 0.05) >= 4.5 && getComputedStyle(t).color === "rgb(255, 255, 255)" && tr.top >= er.top && tr.bottom <= er.bottom && scrimAboveImg && hitInTitle }; }));
  const bgOf = `(e) => { for (; e; e = e.parentElement) { const c = getComputedStyle(e).backgroundColor; if (c && !/rgba\\(0, 0, 0, 0\\)|transparent/.test(c)) return c; } return "rgb(255, 255, 255)"; }`;
  for (const [v, st] of [["key-visual", "few-seats"], ["date-place-block", "ended"], ["text-only", "open"]]) {
    await p.goto(BASE + EVENT + `?wt=event_hero:${v},event_status:${st}`, { waitUntil: js ? "networkidle" : "load" });
    const r = await p.evaluate(([v, st, bgSrc]) => { const bg = eval(bgSrc); const el = document.querySelector(`.wt-event-hero--${v}`); const t = el.querySelector("h1"); const badge = el.querySelector(`.wt-event-status--${st}`); const meta = el.querySelector(".wt-event-hero__meta, .wt-event-hero__lead"); return { v, st, title: [getComputedStyle(t).color, bg(t)], badge: [getComputedStyle(badge).color, bg(badge)], badgeSize: parseFloat(getComputedStyle(badge).fontSize), meta: [getComputedStyle(meta).color, bg(meta)] }; }, [v, st, bgOf]);
    const ok = ([fg, b], min = 4.5) => { const c = parse(fg), bb = parse(b); return ratio(lum(c.rgb), lum(bb.rgb)) >= min; };
    rows.push({ ...r, pass: ok(r.title) && ok(r.meta) && ok(r.badge) });
  }
  await ctx.close(); }
  out.eventHeroContrast = { rows: rows.map((r, i) => ({ dev: ["pc", "sp", "sp"][Math.floor(i / 4)], js: i < 8, ...r })), pass: rows.length === 12 && rows.every((r) => r.pass) };
}
// (n) pageParts（段 8、WT-EVT-0287「固定ページ継投で使えるパーツ」）: 通常の固定ページ（/parts/、page.html）に helix-wt-page/* を 15 個並べた実ページで、各パーツが可視・h2 あり・幅 1120px 内側で全幅（本文幅 680px に閉じ込められない）・可視画像に読込失敗なし・操作要素 44px・form なし（検索欄は role=search の div）・#導線の到達先が可視・外部 http(s) 要求なし。
// カウントダウンは JS ありで数字、JS 無効で "--" と開催日の文字。SNS フィードは未設定で外部接続なし、option（同一ホスト URL）設定で遅延 iframe、終了時に option を消して未設定へ戻る（地図埋め込みと同じ手順）
{
  const PARTS = ["greeting", "logos-row", "stores", "event-list", "gallery", "sns-feed", "timeline", "countdown", "target-audience", "organizer", "tickets", "prizes", "entry-steps", "judges", "target-products"];
  const wpP = WPCLIDIR ? (a) => execFileSync("docker", ["compose", "run", "--rm", "-T", "wpcli", ...a], { cwd: WPCLIDIR, encoding: "utf8" }) : null;
  const snsUnset = () => { try { wpP(["option", "get", "helix_wt_sns_feed_embed_url"]); return false; } catch (_) { return true; } };
  let unsetPrepared = false; if (wpP) { try { wpP(["option", "delete", "helix_wt_sns_feed_embed_url"]); } catch (_) { /* 未設定なら失敗 */ } unsetPrepared = snsUnset(); }
  // 固定ページ /parts/（slug parts、page.html）が無ければ wp-cli で作る。本文は helix-wt-page/* の pattern block 15 個（alignfull の group で包む）。既にあれば本文を同じ内容に更新する（冪等）
  let pageSource = "unavailable";
  if (wpP) { try { const content = '<!-- wp:group {"className":"wt-page-parts","align":"full","layout":{"type":"default"}} --><div class="wp-block-group alignfull wt-page-parts">' + PARTS.map((k) => `<!-- wp:pattern {"slug":"helix-wt-page/${k}"} /-->`).join("") + "</div><!-- /wp:group -->";
    const id = wpP(["post", "list", "--post_type=page", "--name=parts", "--post_status=publish", "--field=ID"]).trim();
    if (id) { wpP(["post", "update", id, `--post_content=${content}`]); pageSource = `wp-cli:updated ${id}`; } else { const nid = wpP(["post", "create", "--post_type=page", "--post_status=publish", "--post_name=parts", "--post_title=固定ページ用パーツ一覧（PoC）", `--post_content=${content}`, "--porcelain"]).trim(); pageSource = `wp-cli:created ${nid}`; } } catch (e) { pageSource = `wp-cli failed: ${String(e).slice(0, 120)}`; } }
  const baseHost = new URL(BASE).host;
  const read = async (cfg, dev, js) => { const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage(); const external = []; p.on("request", (req) => { try { const u = new URL(req.url()); if (/^https?:$/.test(u.protocol) && u.host !== baseHost) external.push(u.host); } catch (_) { /* data: */ } });
    const res = await p.goto(BASE + "/parts/", { waitUntil: js ? "networkidle" : "load" });
    await p.evaluate(async () => { document.querySelectorAll("img").forEach((i) => { i.loading = "eager"; }); await Promise.all(Array.from(document.querySelectorAll("img")).map((i) => i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; setTimeout(r, 4000); }))); });
    const r = await p.evaluate(([parts, visSrc]) => { const vis = eval(visSrc); const $$ = (s) => Array.from(document.querySelectorAll(s));
      const found = $$(".wt-page-parts .wt-part").filter(vis).map((el) => ({ part: Array.from(el.classList).find((c) => c.startsWith("wt-part--")).replace("wt-part--", ""), h2: !!el.querySelector("h2") && vis(el.querySelector("h2")), width: Math.round(el.getBoundingClientRect().width), inner: Math.round(el.querySelector(".wt-lp-section-inner").getBoundingClientRect().width), imgs: el.querySelectorAll("img").length, broken: Array.from(el.querySelectorAll("img")).filter((i) => i.naturalWidth === 0).length }));
      const taps = $$(".wt-page-parts a, .wt-page-parts button, .wt-page-parts input").filter(vis);
      const below44 = taps.filter((el) => { const r = el.getBoundingClientRect(); const inline = el.tagName === "A" && getComputedStyle(el).display === "inline" && el.parentElement && /^(P|LI|TD|B|SPAN)$/.test(el.parentElement.tagName); return !inline && Math.min(r.width, r.height) < 44; }).map((el) => (el.className || el.tagName) + " " + Math.round(el.getBoundingClientRect().width) + "x" + Math.round(el.getBoundingClientRect().height));
      const anchors = $$(".wt-page-parts a[href^='#']").filter(vis).map((a) => { const id = a.getAttribute("href").slice(1); const t = id ? document.getElementById(id) : null; return { href: "#" + id, ok: !!t && vis(t) }; });
      const cd = document.querySelector(".wt-part-countdown"); const digits = cd ? Array.from(cd.querySelectorAll("[data-wt-cd]")).map((b) => b.textContent.trim()) : [];
      const feed = document.querySelector(".wt-part-sns__feed");
      return { status: null, parts: found, count: found.length, forms: $$(".wt-page-parts form").length, below44, deadAnchors: anchors.filter((a) => !a.ok).map((a) => a.href), anchors: anchors.length, viewport: innerWidth, mainW: Math.round(document.querySelector(".wt-side-main").getBoundingClientRect().width), digits, cdDateVisible: !!cd && vis(cd.querySelector(".wt-part-countdown__date")), feed: feed ? { state: feed.getAttribute("data-wt-embed"), iframe: !!feed.querySelector("iframe"), note: !!feed.querySelector(".wt-part-sns__unset") && vis(feed.querySelector(".wt-part-sns__unset")) } : null, searchInputs: $$(".wt-page-parts [role=search] input").length, h1: $$("h1").filter(vis).length };
    }, [PARTS, VIS_SRC]);
    r.status = res ? res.status() : null; r.externalRequests = Array.from(new Set(external)); await ctx.close();
    const names = r.parts.map((x) => x.part);
    const numeric = r.digits.length === 3 && r.digits.every((d) => /^\d+$/.test(d)); const dashes = r.digits.length === 3 && r.digits.every((d) => d === "--");
    const pass = r.status === 200 && JSON.stringify(names) === JSON.stringify(PARTS) && r.parts.every((x) => x.h2 && x.broken === 0 && x.width === r.mainW && x.inner <= 1120 && x.inner >= Math.min(1120, r.mainW) - 48 /* 段 10b: 既定で右サイドバーがあるので本文列（wt-side-main）の幅いっぱい。SP は 1 カラムで viewport 幅 */ && (r.viewport >= 1024 || r.mainW === r.viewport)) && r.forms === 0 && r.searchInputs === 1 && r.below44.length === 0 && r.deadAnchors.length === 0 && r.anchors >= 2 && r.h1 === 1 && r.externalRequests.length === 0 && (js ? numeric : dashes) && r.cdDateVisible && unsetPrepared && r.feed && r.feed.state === "unset" && !r.feed.iframe && r.feed.note;
    return { dev, js, ...r, pass }; };
  const rows = [await read(PC, "pc", true), await read(SP, "sp", true), await read(SP, "sp", false)];
  let feedSet = { source: "unavailable", unsetPrepared, pass: false };
  if (wpP) { let cleanupOk = false;
    try {
      // Astra 是正（PR #164）: パターンは挿入・保存後に静的化するため、登録済みパターンの展開後 markup（wp:pattern 参照ではなく中身）を固定ページ /parts-expanded/ に保存し、動的ブロック helix-wt/sns-feed-embed が option の変更に追従することを確かめる
      const expanded = wpP(["eval", "echo WP_Block_Patterns_Registry::get_instance()->get_registered(\"helix-wt-page/sns-feed\")[\"content\"];"]);
      if (!/<!-- wp:helix-wt\/sns-feed-embed \/-->/.test(expanded) || /<iframe|wt-part-sns__feed/.test(expanded)) throw new Error("展開後の pattern に動的ブロックが無いか、静的な埋め込み markup を含む");
      const eid = wpP(["post", "list", "--post_type=page", "--name=parts-expanded", "--post_status=publish", "--field=ID"]).trim();
      if (eid) wpP(["post", "update", eid, `--post_content=${expanded}`]); else wpP(["post", "create", "--post_type=page", "--post_status=publish", "--post_name=parts-expanded", "--post_title=SNS フィード（展開保存の検証用 PoC）", `--post_content=${expanded}`, "--porcelain"]);
      const readFeed = async (url) => { const ctx2 = await browser.newContext(PC); const p2 = await ctx2.newPage(); const ext2 = []; p2.on("request", (req) => { try { const u = new URL(req.url()); if (/^https?:$/.test(u.protocol) && u.host !== baseHost) ext2.push(u.host); } catch (_) { /* data: */ } });
        await p2.goto(BASE + url, { waitUntil: "networkidle" });
        const r = await p2.evaluate(() => { const f = document.querySelector(".wt-part-sns__feed"); const i = f ? f.querySelector("iframe") : null; return { state: f && f.getAttribute("data-wt-embed"), iframe: !!i, lazy: !!i && i.getAttribute("loading") === "lazy", title: !!i && !!i.getAttribute("title"), sameHost: !!i && new URL(i.src, location.href).host === location.host, visible: !!i && i.getBoundingClientRect().width > 0, ids: document.querySelectorAll("#part-sns-feed").length }; });
        r.externalRequests = Array.from(new Set(ext2)); await ctx2.close(); return r; };
      const expandedUnset = await readFeed("/parts-expanded/"); // option 未設定のうちに展開保存ページを読む
      wpP(["option", "update", "helix_wt_sns_feed_embed_url", BASE + "/lp/"]);
      const r = await readFeed("/parts/"); const r2 = await readFeed("/parts-expanded/");
      const okSet = (x) => x.state === "set" && x.iframe && x.lazy && x.title && x.sameHost && x.visible && x.externalRequests.length === 0 && x.ids === 1;
      feedSet = { source: "wp-cli:option helix_wt_sns_feed_embed_url", unsetPrepared, ...r, expandedUnset, expandedSet: r2, pass: unsetPrepared && okSet(r) && okSet(r2) && expandedUnset.state === "unset" && !expandedUnset.iframe && expandedUnset.externalRequests.length === 0 && expandedUnset.ids === 1 };
    } catch (e) { feedSet = { source: `wp-cli failed: ${String(e).slice(0, 120)}`, unsetPrepared, pass: false }; }
    finally { try { wpP(["option", "delete", "helix_wt_sns_feed_embed_url"]); } catch (_) { /* 既に無ければ失敗 */ }
      cleanupOk = snsUnset();
      if (cleanupOk) { const ctx3 = await browser.newContext(PC); const p3 = await ctx3.newPage(); const chk = async (u) => { await p3.goto(BASE + u, { waitUntil: "load" }); return p3.evaluate(() => { const f = document.querySelector(".wt-part-sns__feed"); return !!f && f.getAttribute("data-wt-embed") === "unset" && !f.querySelector("iframe"); }); }; cleanupOk = (await chk("/parts/")) && (await chk("/parts-expanded/")); await ctx3.close(); } // 展開保存ページでも未設定表示へ戻ること
      feedSet.cleanupOk = cleanupOk; feedSet.pass = feedSet.pass && cleanupOk; } }
  out.pageParts = { pageSource, rows, feedSet, pass: rows.length === 3 && rows.every((r) => r.pass) && feedSet.pass };
}
// 段 10c: 面ごとの束へ ?wt を変換（HP = home_side_*、固定ページは記事側の束を明示）
const sideQ = (face, q) => face === "home" ? q.replace(/(^|,)side_(layout|sticky|sp|set|nav):/g, "$1home_side_$2:") : face === "page" ? "page_side:article," + q : q;
// (o) sideFace（段 10、WT-EVT-0289）: 共通サイドバーの全軸 × 全値を記事 / 固定ページ / HP で検査。配置（grid の実トラック数と aside の可視）、追尾（computed position: sticky の実体と対象要素）、セット（可視ウィジェットの順序 = wt_side_sets）、SP 3 型（below-content = 本文の下で可視、drawer = JS ありでボタン→開閉、JS 無効は本文下に表示、hidden = 非表示）、サイドナビ 5 型（要素の可視と position: fixed、メガメニューは trigger の aria-expanded と Escape で閉じる）。44px、# 導線の到達先、フォームは検索のみ（GET、home_url）、外部 http(s) 要求なし
{
  const AX = { side_layout: ["none", "right", "left", "both"], side_sticky: ["none", "whole", "last-widget", "toc-only"], side_sp: ["below-content", "drawer", "hidden"], side_set: ["media", "blog", "owned", "corporate", "minimal", "full"], side_nav: ["none", "mega-menu", "fixed-left-nav", "fixed-right-icons", "drawer-pc", "toc-side"] };
  const SETS = { media: ["search", "categories", "popular-ranking", "toc-sticky", "cta-banner", "ad", "related-posts"], blog: ["search", "profile", "categories", "popular-ranking", "new-posts", "archive", "tags", "sns-follow"], owned: ["search", "categories", "popular-ranking", "new-posts", "tags", "newsletter", "recruit", "cta-banner"], corporate: ["contact-box", "tel-box", "new-posts", "event-list", "banner-stack"], minimal: ["popular-ranking", "related-posts", "banner-stack"], full: ["search", "profile", "categories", "popular-ranking", "new-posts", "tags", "cta-banner", "toc-sticky", "toc-dropdown", "newsletter", "sns-follow", "archive", "calendar", "ad", "related-posts", "event-list", "contact-box", "tel-box", "banner-stack", "recruit"] };
  const FACES = [["article", ARTICLE], ["page", "/parts/"], ["home", HOME]];
  const baseHost = new URL(BASE).host;
  const read = async (cfg, dev, js) => { const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage(); const rows = []; let external = []; p.on("request", (req) => { try { const u = new URL(req.url()); if (/^https?:$/.test(u.protocol) && u.host !== baseHost) external.push(u.host); } catch (_) { /* data: */ } });
    for (const [face, path] of FACES) for (const [axis, values] of Object.entries(AX)) for (const v of values) {
      external = [];
      const q0 = axis === "side_layout" ? `side_layout:${v}` : `side_layout:right,${axis}:${v}`; // 配置以外の軸は right で検査
      const q = sideQ(face, q0); // 段 10c: HP は HP 側の束（home_side_*）、固定ページは page_side:article で記事側の束を当てる
      await p.goto(BASE + path + `?wt=${q}`, { waitUntil: js ? "networkidle" : "load" });
      const r = await p.evaluate(([axis, v, face, visSrc, sets]) => { const vis = eval(visSrc); const $ = (s) => document.querySelector(s); const $$ = (s) => Array.from(document.querySelectorAll(s));
        const cls = axis.replace(/_/g, "-"); const body = document.body.classList.contains(`wt-${cls}-${v}`);
        const layout = $(".wt-side-layout"); const tracks = layout ? getComputedStyle(layout).gridTemplateColumns.split(" ").filter((x) => x && x !== "none").length : 0;
        const right = $(".wt-side--right"), left = $(".wt-side--left"); const rightVis = vis(right), leftVis = vis(left);
        const widgets = right ? Array.from(right.querySelectorAll(":scope > .wt-side-widget")).filter(vis).map((w) => w.getAttribute("data-wt-widget")) : [];
        const sticky = (el) => !!el && getComputedStyle(el).position === "sticky";
        const stickyWhole = sticky(right), stickyLast = right ? sticky(right.querySelector(":scope > .wt-side-widget:last-child")) : false, stickyToc = sticky($(".wt-side-widget--toc-sticky"));
        const mainTop = $(".wt-side-main") ? $(".wt-side-main").getBoundingClientRect().top + scrollY : null; const rightTop = rightVis ? right.getBoundingClientRect().top + scrollY : null; const rightLeft = rightVis ? right.getBoundingClientRect().left : null; const mainLeft = $(".wt-side-main") ? $(".wt-side-main").getBoundingClientRect().left : null;
        const openBtn = $(".wt-side-drawer__open"); const drawer = $("#wt-side-drawer");
        const nav = { fixedLeft: vis($(".wt-sidenav--fixed-left")) && getComputedStyle($(".wt-sidenav--fixed-left")).position === "fixed", fixedRight: vis($(".wt-sidenav--fixed-right")) && getComputedStyle($(".wt-sidenav--fixed-right")).position === "fixed", megaTrigger: vis($(".wt-megamenu__trigger")), megaOpen: !!$("#wt-megamenu") && !$("#wt-megamenu").hidden, tocFixed: !!$(".wt-toc") && getComputedStyle($(".wt-toc")).position === "fixed" };
        const taps = $$(".wt-side a, .wt-side button, .wt-side input, .wt-sidenav a, .wt-sidenav button, .wt-side-drawer__open, .wt-megamenu a, .wt-megamenu__trigger").filter(vis);
        const below44 = taps.filter((el) => { const r = el.getBoundingClientRect(); const inline = el.tagName === "A" && getComputedStyle(el).display === "inline" && el.parentElement && /^(P|LI|TD|B|SPAN)$/.test(el.parentElement.tagName); return !inline && Math.min(r.width, r.height) < 44; }).map((el) => (el.className || el.tagName) + " " + Math.round(el.getBoundingClientRect().width) + "x" + Math.round(el.getBoundingClientRect().height));
        const anchors = $$(".wt-side a[href^='#'], .wt-sidenav a[href^='#']").filter(vis).map((a) => { const id = a.getAttribute("href").slice(1); const t = id ? document.getElementById(id) : null; return { href: "#" + id, ok: !!t && vis(t) }; }); const deadAnchors = anchors.filter((a) => !a.ok).map((a) => a.href);
        const forms = $$(".wt-side form").filter(vis).map((f) => ({ method: (f.getAttribute("method") || "get").toLowerCase(), action: f.getAttribute("action") || "", role: f.getAttribute("role"), sameHost: new URL(f.getAttribute("action") || "/", location.href).host === location.host }));
        const brokenImgs = $$(".wt-side img").filter((i) => vis(i) && i.complete && i.naturalWidth === 0).length;
        return { body, tracks, rightVis, leftVis, widgets, stickyWhole, stickyLast, stickyToc, mainTop, rightTop, rightLeft, mainLeft, openBtnVis: vis(openBtn), drawerHidden: !drawer || drawer.hidden, nav, below44, deadAnchors, forms, brokenImgs, h1: $$("h1").filter(vis).length, viewport: innerWidth };
      }, [axis, v, face, VIS_SRC, SETS]);
      // 追尾の実体（PC・JS あり）: 1500px スクロールして、追尾対象の viewport 上端が header 高 + 1rem 付近に留まり、非追尾の先頭ウィジェットは画面外へ動くこと（Astra 是正: computed position だけでは aside が内容高で止まる不具合を見逃す）
      let stickyRun = null;
      if (js && dev === "pc" && r.rightVis) { stickyRun = await p.evaluate(([stickyV]) => { const q = (s) => document.querySelector(s); const right = q(".wt-side--right"); const target = stickyV === "whole" ? right : stickyV === "last-widget" ? right.querySelector(":scope > .wt-side-widget:last-child") : stickyV === "toc-only" ? q(".wt-side-widget--toc-sticky") : null; const first = right.querySelector(":scope > .wt-side-widget"); const before = { t: target ? target.getBoundingClientRect().top : null, f: first.getBoundingClientRect().top, asideH: right.getBoundingClientRect().height, mainH: q(".wt-side-main").getBoundingClientRect().height }; const hdr = q(".wt-header") ? q(".wt-header").getBoundingClientRect().height : 0; const y = target ? Math.max(1500, target.getBoundingClientRect().top + scrollY - hdr - 16 + 400) : 1500; scrollTo(0, y); /* 追尾対象が本来の位置を 400px 越えるまでスクロール（長いサイドバーでは 1500px では足りない） */ return new Promise((res) => setTimeout(() => { const after = { t: target ? target.getBoundingClientRect().top : null, f: first.getBoundingClientRect().top, asideBottom: right.getBoundingClientRect().bottom, targetH: target ? target.getBoundingClientRect().height : null, targetMB: target ? parseFloat(getComputedStyle(target).marginBottom) || 0 : 0 }; scrollTo(0, 0); res({ stickyV, hasTarget: !!target, before, after, scrolledTo: y, headerH: hdr }); }, 200)); }, [axis === "side_sticky" ? v : "last-widget"]); }
      // ドロワー: JS ありでボタン→開く→複製の中身（順序・フォーム・44px・参照先）とフォーカス封じ込め→Escape で閉じる
      let drawerRun = null;
      if (js && r.openBtnVis) { await p.click(".wt-side-drawer__open"); await p.waitForTimeout(150); drawerRun = await p.evaluate((visSrc) => { const vis = eval(visSrc); const d = document.getElementById("wt-side-drawer"); const body = d.querySelector(".wt-side-drawer__body"); const ws = Array.from(body.querySelectorAll(".wt-side-widget")).filter(vis).map((w) => w.getAttribute("data-wt-widget")); const dup = Array.from(document.querySelectorAll("[id]")).map((e) => e.id).filter((id, i, a) => a.indexOf(id) !== i); const forms = Array.from(body.querySelectorAll("form")).filter(vis).map((f) => ({ method: (f.getAttribute("method") || "get").toLowerCase(), role: f.getAttribute("role") })); const taps = Array.from(body.querySelectorAll("a, button, input")).filter(vis); const below44 = taps.filter((el) => { const r = el.getBoundingClientRect(); const inline = el.tagName === "A" && getComputedStyle(el).display === "inline"; return !inline && Math.min(r.width, r.height) < 44; }).length; const badRefs = []; ["for", "aria-labelledby", "aria-describedby"].forEach((attr) => body.querySelectorAll("[" + attr + "]").forEach((el) => el.getAttribute(attr).split(/\s+/).forEach((id) => { const t = document.getElementById(id); if (!t || !body.contains(t)) badRefs.push(attr + "=" + id); }))); const anchors = Array.from(body.querySelectorAll("a[href^='#']")).map((a) => a.getAttribute("href").slice(1)); const badAnchors = anchors.filter((id) => { const t = document.getElementById(id); return !t || (!body.contains(t) && !vis(t)); }); const inertOutside = Array.from(document.body.children).filter((c) => c !== d).every((c) => c.hasAttribute("inert")); return { open: !!d && !d.hidden, widgets: ws, expanded: document.querySelector(".wt-side-drawer__open").getAttribute("aria-expanded"), dupIds: dup.length, dupList: dup.slice(0, 5), bodyLocked: document.body.classList.contains("wt-side-drawer-open"), forms, below44, badRefs, badAnchors, inertOutside, focusInside: d.contains(document.activeElement) }; }, VIS_SRC);
        await p.keyboard.press("Shift+Tab"); await p.waitForTimeout(50); drawerRun.focusAfterShiftTab = await p.evaluate(() => document.getElementById("wt-side-drawer").contains(document.activeElement));
        await p.keyboard.press("Escape"); await p.waitForTimeout(100); drawerRun.closed = await p.evaluate(() => document.getElementById("wt-side-drawer").hidden && document.querySelector(".wt-side-drawer__open").getAttribute("aria-expanded") === "false" && !Array.from(document.body.children).some((c) => c.hasAttribute("inert"))); drawerRun.focusRestored = await p.evaluate(() => document.activeElement === document.querySelector(".wt-side-drawer__open")); }
      let megaRun = null;
      if (js && axis === "side_nav" && v === "mega-menu") { await p.click(".wt-megamenu__trigger"); await p.waitForTimeout(100); megaRun = await p.evaluate((visSrc) => { const vis = eval(visSrc); return { open: !document.getElementById("wt-megamenu").hidden, expanded: document.querySelector(".wt-megamenu__trigger").getAttribute("aria-expanded"), links: Array.from(document.querySelectorAll("#wt-megamenu a")).filter(vis).length, panelVisible: vis(document.getElementById("wt-megamenu")) }; }, VIS_SRC); await p.keyboard.press("Escape"); await p.waitForTimeout(100); megaRun.closed = await p.evaluate(() => document.getElementById("wt-megamenu").hidden); }
      r.externalRequests = Array.from(new Set(external));
      const layoutV = axis === "side_layout" ? v : "right"; const isPc = dev === "pc"; const hasSide = layoutV !== "none";
      const spMode = axis === "side_sp" ? v : "below-content"; const spShown = isPc ? true : (spMode === "below-content" || (spMode === "drawer" && !js));
      const expectRight = hasSide && spShown; const expectLeft = isPc && layoutV === "both";
      const expectTracks = !isPc ? 1 : layoutV === "none" ? 1 : layoutV === "both" ? 3 : 2;
      const setV = axis === "side_set" ? v : (face === "home" ? "corporate" : "media"); /* 段 10c: HP 側の束の既定セットは corporate */ let expectWidgets = SETS[setV]; if (face !== "article") expectWidgets = expectWidgets.filter((w) => w !== "toc-sticky" && w !== "toc-dropdown"); // 見出しの無い面では目次ウィジェットを出さない
      const stickyV = axis === "side_sticky" ? v : "last-widget"; const hasToc = expectWidgets.includes("toc-sticky");
      let pass = r.body && r.tracks === expectTracks && r.rightVis === expectRight && r.leftVis === expectLeft && r.below44.length === 0 && r.deadAnchors.length === 0 && r.brokenImgs === 0 && r.h1 === 1 && r.externalRequests.length === 0 && r.forms.every((f) => f.method === "get" && f.role === "search" && f.sameHost);
      if (expectRight) { pass = pass && JSON.stringify(r.widgets) === JSON.stringify(expectWidgets) && r.forms.length === (expectWidgets.includes("search") ? 1 : 0) + (expectLeft ? 1 : 0); /* 左カラム（both）にも検索フォームが 1 つ */ if (isPc) pass = pass && r.stickyWhole === (stickyV === "whole") && r.stickyLast === (stickyV === "last-widget" || (stickyV === "whole" ? r.stickyLast : false)) && r.stickyToc === (stickyV === "toc-only" && hasToc); }
      if (isPc && expectRight) pass = pass && (layoutV === "left" ? r.rightLeft < r.mainLeft : r.rightLeft > r.mainLeft); // 左右の実配置
      if (!isPc && expectRight) pass = pass && r.rightTop > r.mainTop; // SP は本文の下
      const drawerOk = (d) => d && d.open && JSON.stringify(d.widgets) === JSON.stringify(expectWidgets) && d.expanded === "true" && d.dupIds === 0 && d.bodyLocked && d.forms.length === (expectWidgets.includes("search") ? 1 : 0) && d.forms.every((f) => f.method === "get" && f.role === "search") && d.below44 === 0 && d.badRefs.length === 0 && d.badAnchors.length === 0 && d.inertOutside && d.focusInside && d.focusAfterShiftTab && d.closed && d.focusRestored;
      if (!isPc && hasSide && spMode === "drawer" && js) pass = pass && r.openBtnVis && drawerOk(drawerRun); else if (!(axis === "side_nav" && v === "drawer-pc" && js)) pass = pass && !r.openBtnVis;
      if (stickyRun) { const top = stickyRun.headerH + 16; const near = (x) => x !== null && Math.abs(x - top) <= 2; const sv = stickyRun.stickyV; if (sv === "none" || (sv === "toc-only" && !expectWidgets.includes("toc-sticky"))) pass = pass && stickyRun.after.f < 0; /* 目次ウィジェットの無い面では toc-only は何も追尾しない */ else if (sv === "whole") pass = pass && near(stickyRun.after.t); else { const a = stickyRun.after; const pinnedAtEnd = a.asideBottom !== null && a.targetH !== null && a.asideBottom < top + a.targetH + a.targetMB && Math.abs(a.asideBottom - a.targetMB - a.targetH - a.t) <= 2; /* 末尾ウィジェットの margin-bottom 分だけ aside の下端より上で止まる */ /* 段 10b: aside が本文と同じ高さで追尾の余地が無い（HP full セット）ときは、対象が aside の末尾に押し上げられた状態を正とする */ const noRoom = stickyRun.before.asideH >= stickyRun.before.mainH - 2 && (stickyRun.before.asideH - (stickyRun.before.t - stickyRun.before.f) - a.targetH - a.targetMB) < 400; /* aside の内容高が本文以上で、対象の下に 400px の余地が無いときだけ末尾固定を認める（Astra 1 巡目: aside が縮んだ退行を通さない） */ pass = pass && stickyRun.hasTarget && (near(a.t) || (pinnedAtEnd && noRoom)) && a.f < 0 && stickyRun.before.asideH >= stickyRun.before.mainH * 0.95; } } /* aside は本文の高さまで伸びている（main 側の余白分 16px 程度の差は許容） */ // 追尾対象は上端に留まり、先頭ウィジェットは画面外。aside は本文の高さまで伸びている
      if (axis === "side_nav") { const n = r.nav; pass = pass && n.fixedLeft === (v === "fixed-left-nav" && isPc && r.viewport >= 1200) && n.fixedRight === (v === "fixed-right-icons") && n.megaTrigger === (v === "mega-menu" && js) && (v !== "mega-menu" || !js || (megaRun && megaRun.open && megaRun.panelVisible && megaRun.expanded === "true" && megaRun.links >= 5 && megaRun.closed)) && n.tocFixed === (v === "toc-side" && isPc && face === "article"); if (v === "drawer-pc" && js) pass = pass && r.openBtnVis && drawerOk(drawerRun); } else pass = pass && !r.nav.fixedLeft && !r.nav.fixedRight && !r.nav.megaTrigger && !r.nav.tocFixed;
      rows.push({ dev, js, face, axis, v, expectTracks, expectRight, expectLeft, expectWidgets, ...r, stickyRun, drawerRun, megaRun, pass });
    }
    // 追加行（SP・JS あり）: drawer × full セット（newsletter / toc-dropdown を含む複製の参照先を実測）と、記事ではドロワー内の目次リンク → 閉じて inert が外れ、対象見出しへ移動すること（Astra 2 巡目）
    if (dev !== "pc" && js) for (const [face, path] of FACES) {
      external = [];
      await p.goto(BASE + path + "?wt=" + sideQ(face, "side_layout:right,side_sp:drawer,side_set:full"), { waitUntil: "networkidle" });
      let expectWidgets = SETS.full; if (face !== "article") expectWidgets = expectWidgets.filter((w) => w !== "toc-sticky" && w !== "toc-dropdown");
      await p.click(".wt-side-drawer__open"); await p.waitForTimeout(150);
      const d = await p.evaluate((visSrc) => { const vis = eval(visSrc); const d = document.getElementById("wt-side-drawer"); const body = d.querySelector(".wt-side-drawer__body"); const ws = Array.from(body.querySelectorAll(".wt-side-widget")).filter(vis).map((w) => w.getAttribute("data-wt-widget")); const dup = Array.from(document.querySelectorAll("[id]")).map((e) => e.id).filter((id, i, a) => a.indexOf(id) !== i); const forms = Array.from(body.querySelectorAll("form")).filter(vis).map((f) => ({ method: (f.getAttribute("method") || "get").toLowerCase(), role: f.getAttribute("role") })); const taps = Array.from(body.querySelectorAll("a, button, input")).filter(vis); const below44 = taps.filter((el) => { const r = el.getBoundingClientRect(); const inline = el.tagName === "A" && getComputedStyle(el).display === "inline"; return !inline && Math.min(r.width, r.height) < 44; }).length; const badRefs = []; ["for", "aria-labelledby", "aria-describedby"].forEach((attr) => body.querySelectorAll("[" + attr + "]").forEach((el) => el.getAttribute(attr).split(/\s+/).forEach((id) => { const t = document.getElementById(id); if (!t || !body.contains(t)) badRefs.push(attr + "=" + id); }))); const refCount = body.querySelectorAll("[for],[aria-labelledby],[aria-describedby]").length; const refByWidget = { searchFor: !!body.querySelector(".wt-side-widget--search label[for]") && !!body.querySelector("#" + CSS.escape(body.querySelector(".wt-side-widget--search label[for]").getAttribute("for"))), newsletterFor: !!body.querySelector(".wt-side-widget--newsletter label[for]") && !!body.querySelector("#" + CSS.escape(body.querySelector(".wt-side-widget--newsletter label[for]").getAttribute("for"))), newsletterDescribedby: !!body.querySelector(".wt-side-widget--newsletter [aria-describedby]") && !!body.querySelector("#" + CSS.escape(body.querySelector(".wt-side-widget--newsletter [aria-describedby]").getAttribute("aria-describedby"))), widgetLabelledby: Array.from(body.querySelectorAll(".wt-side-widget")).every((w) => w.getAttribute("aria-labelledby") && body.querySelector("#" + CSS.escape(w.getAttribute("aria-labelledby")))) }; const tocLink = body.querySelector(".wt-side-widget--toc-sticky .wt-side-toc a"); const inertOutside = Array.from(document.body.children).filter((c) => c !== d).every((c) => c.hasAttribute("inert")); return { open: !d.hidden, widgets: ws, dupIds: dup.length, forms, below44, badRefs, refCount, refByWidget, hasTocLink: !!tocLink, tocHref: tocLink ? tocLink.getAttribute("href") : null, inertOutside, focusInside: d.contains(document.activeElement) }; }, VIS_SRC);
      let tocRun = null;
      if (d.hasTocLink) { await p.click(".wt-side-drawer__body .wt-side-widget--toc-sticky .wt-side-toc a"); await p.waitForTimeout(300); tocRun = await p.evaluate((href) => { const t = document.getElementById(href.slice(1)); const r = t ? t.getBoundingClientRect() : null; return { closed: document.getElementById("wt-side-drawer").hidden, inertCleared: !Array.from(document.body.children).some((c) => c.hasAttribute("inert")), hash: location.hash, targetInView: !!r && r.top >= -2 && r.top < innerHeight }; }, d.tocHref); }
      const expectForms = expectWidgets.includes("search") ? 1 : 0;
      const pass = d.open && JSON.stringify(d.widgets) === JSON.stringify(expectWidgets) && d.dupIds === 0 && d.forms.length === expectForms && d.below44 === 0 && d.badRefs.length === 0 && d.refCount >= 3 && d.refByWidget.searchFor && d.refByWidget.newsletterFor && d.refByWidget.newsletterDescribedby && d.refByWidget.widgetLabelledby && d.inertOutside /* 属性別・ウィジェット別に参照先がパネル内に実在（Astra 3 巡目） */ && d.focusInside && (face !== "article" || (d.hasTocLink && tocRun && tocRun.closed && tocRun.inertCleared && tocRun.hash === d.tocHref && tocRun.targetInView)) && external.length === 0;
      rows.push({ dev, js, face, axis: "combo", v: "drawer+full", expectWidgets, drawer: d, tocRun, externalRequests: Array.from(new Set(external)), pass });
    }
    await ctx.close(); return rows; };
  const sp = await read(SP, "sp", true), pc = await read(PC, "pc", true), spNoJs = await read(SP, "sp", false);
  const all = [...sp, ...pc, ...spNoJs];
  out.sideFace = { sp, pc, spNoJs, pass: all.length === 23 * 3 * 3 + 3 && all.every((x) => x.pass) };
}
// (p) sideDefaults（段 10b、WT-EVT-0292 PO 反応 22 回目「サイドバーは普通置く」「置かないケースは LP ぐらい」）: 既定（?wt なし）で記事 / 固定ページ / HP に右サイドバーが出ること（PC = 2 トラック・aside 可視、SP = 1 トラックで本文の下）、カテゴリ面は記事側の束を継承して 2 列目に出ること、LP には共通サイドバーが無いこと（束 none・実効 none）、HP の side_from 2 型 × 配置 4 型で hero と aside の位置関係（below-hero: hero が端まで全幅で本文・aside は hero の下、top: hero は本文列の幅で aside の上端が hero の上端に揃う。SP と none は常に hero 全幅 → 本文 → aside）
{
  const rows = [];
  for (const [dev, cfg] of [["pc", PC], ["sp", SP]]) {
    const ctx = await browser.newContext(cfg); const p = await ctx.newPage();
    for (const [face, path] of [["article", ARTICLE], ["page", "/parts/"], ["home", HOME], ["lp", "/lp/"], ["category", CATEGORY]]) {
      await p.goto(BASE + path, { waitUntil: "networkidle" });
      const r = await p.evaluate((visSrc) => { const vis = eval(visSrc); const $ = (s) => document.querySelector(s); const layout = $(".wt-face-category") ? $(".wt-cat-layout") : $(".wt-side-layout"); const right = $(".wt-side--right"); const main = $(".wt-side-main"); return { bundle: (Array.from(document.body.classList).find((x) => x.startsWith("wt-side-bundle-")) || "").replace("wt-side-bundle-", ""), bodyNone: document.body.classList.contains("wt-side-layout-none"), bodyRight: document.body.classList.contains("wt-side-layout-right"), bodyFrom: document.body.classList.contains("wt-side-from-below-hero"), hasLayout: !!layout, hasSideEl: !!right, tracks: layout ? getComputedStyle(layout).gridTemplateColumns.split(" ").filter((x) => x && x !== "none").length : 0, rightVis: vis(right), mainTop: main ? main.getBoundingClientRect().top + scrollY : null, rightTop: vis(right) ? right.getBoundingClientRect().top + scrollY : null, mainBottom: main ? main.getBoundingClientRect().bottom + scrollY : null, h1: Array.from(document.querySelectorAll("h1")).filter(vis).length }; }, VIS_SRC);
      const common = face !== "lp"; // 段 10c: カテゴリも既定で記事側の束（cat_side:article）を継承。LP だけ実効 none（束 none）
      const expectBundle = { article: "article", page: "home", home: "home", category: "article", lp: "none" }[face];
      const pass = r.bundle === expectBundle && r.bodyFrom && r.hasLayout === common && r.hasSideEl === common && r.h1 === 1 && (common ? r.bodyRight && (dev === "pc" ? r.tracks === 2 && r.rightVis : r.tracks === 1 && r.rightVis && r.rightTop >= r.mainBottom - 1) : r.bodyNone && !r.bodyRight && !r.rightVis && r.tracks === 0);
      rows.push({ dev, face, axis: "default", ...r, pass });
    }
    for (const from of ["below-hero", "top"]) for (const layout of ["right", "left", "both", "none"]) {
      await p.goto(BASE + HOME + `?wt=side_from:${from},home_side_layout:${layout}`, { waitUntil: "networkidle" }); // 段 10c: HP は HP 側の束
      const r = await p.evaluate(([from, layout, visSrc]) => { const vis = eval(visSrc); const $ = (s) => document.querySelector(s); const rect = (s) => { const e = $(s); if (!e || !vis(e)) return null; const b = e.getBoundingClientRect(); return { l: b.left, r: b.right, t: b.top + scrollY, b: b.bottom + scrollY }; }; const slot = $(".wt-home-hero-slot"); return { body: document.body.classList.contains(`wt-side-from-${from}`) && document.body.classList.contains(`wt-side-layout-${layout}`), slotParentIsLayout: !!slot && slot.parentElement.classList.contains("wt-side-layout"), hero: rect(".wt-home-hero-slot"), main: rect(".wt-side-main"), right: rect(".wt-side--right"), left: rect(".wt-side--left"), vw: document.documentElement.clientWidth, heroVisible: Array.from(document.querySelectorAll(".wt-home-hero")).filter(vis).length, heroInner: rect(".wt-home-hero-slot .wt-home-hero__inner") }; }, [from, layout, VIS_SRC]);
      const isPc = dev === "pc"; const has = layout !== "none"; const near = (a, b, tol = 1) => a !== null && b !== null && Math.abs(a - b) <= tol;
      let pass = r.body && r.slotParentIsLayout && !!r.hero && !!r.main && !!r.heroInner && r.heroVisible === 1 && r.main.t >= r.hero.b - 1 && (has === !!r.right || !isPc) && r.heroInner.l >= r.hero.l && r.heroInner.r <= r.hero.r;
      if (!isPc) pass = pass && near(r.hero.l, 0) && near(r.hero.r, r.vw) && (!has || (!!r.right && r.right.t >= r.main.b - 1)) && !r.left; // SP: hero 全幅 → 本文 → aside（below-content）
      else if (!has) pass = pass && near(r.hero.l, 0) && near(r.hero.r, r.vw) && !r.right && !r.left;
      else if (from === "below-hero") pass = pass && near(r.hero.l, 0) && near(r.hero.r, r.vw) && r.right.t >= r.hero.b - 1 && near(r.right.t, r.main.t, 20) && (layout !== "both" || (!!r.left && r.left.t >= r.hero.b - 1));
      else pass = pass && near(r.hero.l, r.main.l) && near(r.hero.r, r.main.r) && r.hero.l > 0 && r.hero.r < r.vw && near(r.right.t, r.hero.t, 20) && r.right.b >= r.main.b - 1 && (layout === "left" ? r.right.r <= r.hero.l : r.right.l >= r.hero.r) && (layout !== "both" || (!!r.left && near(r.left.t, r.hero.t, 20) && r.left.r <= r.hero.l)); // top: hero は本文列、aside は hero の高さから本文の末尾まで貫く（aside の内側余白 1rem を許容）
      rows.push({ dev, face: "home", axis: "side_from", from, layout, ...r, pass });
    }
    await ctx.close();
  }
  out.sideDefaults = { rows, pass: rows.length === 2 * (5 + 8) && rows.every((x) => x.pass) };
}
// (q) sideOwner（段 10c、WT-EVT-0296 / 0297「合わせろ」、PO 決定 WT-EVT-0299「継承・独自設定・非表示を分離、後続の継承セット追加に対応」）: サイドバーの所属が面ごとに決まること。束の台帳は <meta name="wt-side-bundles">（functions.php wt_side_bundles）から読み、面の所属軸の値をそこから組み立てる（台帳に束を足せば行が増える = 追加への対応の検査）。カテゴリ cat_side = 束の名前（共通サイドバーが .wt-cat-layout の 2 列目、段 6 の aside は隠れる）/ classic（段 6 の aside だけ、PC 2 列）/ off（1 カラム）、イベント event_side = off / 束、固定ページ page_side = 束 / off。own は面専用の軸 own_<face>_side_*（面をまたいで混ざらない）。HOME の OFF（home_side_layout:none）。束の分離（HP で side_set を変えても変わらず、記事で home_side_set を変えても変わらない）。非表示（off / classic）はサイドナビ・ドロワーも出ない（side_nav:drawer-pc / fixed-right-icons / side_sp:drawer を当てても）。SP の JS 無効でも継承した aside は本文の下に見える。PC / SP
{
  const rows = [];
  const SETS2 = { media: ["search", "categories", "popular-ranking", "cta-banner", "ad", "related-posts"], corporate: ["contact-box", "tel-box", "new-posts", "event-list", "banner-stack"], blog: ["search", "profile", "categories", "popular-ranking", "new-posts", "archive", "tags", "sns-follow"], minimal: ["popular-ranking", "related-posts", "banner-stack"] }; // 記事以外の面は toc-* を除いた並び。own の既定セットは minimal
  const SETOF = { article: "media", home: "corporate", own: "minimal" }; // 束ごとの既定セット（継承 = 束の既定、own = own_<face>_side_set の既定）
  const ctx0 = await browser.newContext(PC); const p0 = await ctx0.newPage(); await p0.goto(BASE + HOME, { waitUntil: "load" });
  const bundles = await p0.evaluate(() => (document.querySelector('meta[name="wt-side-bundles"]')?.getAttribute("content") || "").split(",").filter(Boolean)); await ctx0.close();
  const registryOk = bundles.length >= 3 && ["home", "article", "own"].every((b) => bundles.includes(b)) && bundles.every((b) => b in SETOF); // 台帳に home / article / own があり、既定セットが分かる束だけ
  const FACES2 = [["category", CATEGORY, "cat_side", ["classic", "off"]], ["event", "/event/", "event_side", ["off"]], ["page", "/parts/", "page_side", ["off"]]];
  const CASES = [];
  for (const [face, path, axis, extra] of FACES2) {
    for (const v of [...bundles, ...extra]) CASES.push({ face, path, q: `${axis}:${v}`, v, bundle: bundles.includes(v) ? v : "none", catAside: v === "classic", set: SETOF[v] || null });
    for (const v of extra) CASES.push({ face, path, q: `${axis}:${v},side_nav:drawer-pc,side_sp:drawer,home_side_nav:fixed-right-icons,own_${face}_side_nav:drawer-pc`, v: `${v}+nav`, bundle: "none", catAside: v === "classic", set: null, noNav: true }); // 非表示はサイドナビ・ドロワーも切れる
    CASES.push({ face, path, q: `${axis}:own,own_${face}_side_set:blog,side_set:corporate,home_side_set:media,${FACES2.filter((f) => f[0] !== face).map((f) => `own_${f[0]}_side_set:corporate`).join(",")}`, v: "own uses own_<face>_side_*", bundle: "own", catAside: false, set: "blog" }); // own は自面の軸だけを見る（他面の own と記事側 / HP 側を変えても変わらない）
  }
  for (const [face, path, axis] of FACES2.filter((f) => f[0] !== "page")) CASES.push({ face, path, q: `${axis}:article,side_sp:drawer`, v: "article+drawer", bundle: "article", catAside: false, set: "media", drawer: true }); // 継承した束の SP ドロワー: PC は aside、SP JS ありはボタンだけ、SP JS 無効は本文の下に aside（Astra 2 巡目）
  CASES.push({ face: "home", path: HOME, q: "home_side_layout:none", v: "none", bundle: "home", catAside: false, set: null });
  CASES.push({ face: "home", path: HOME, q: "home_side_layout:none,side_nav:drawer-pc,home_side_nav:fixed-right-icons", v: "none+nav", bundle: "home", catAside: false, set: null, noNav: false }); // HOME の OFF は配置だけ（HP 側のサイドナビは HP 側の軸のまま生きる）
  CASES.push({ face: "home", path: HOME, q: "side_set:blog", v: "isolation(side_set on home)", bundle: "home", catAside: false, set: "corporate" }); // 記事側の束を変えても HP は変わらない
  CASES.push({ face: "article", path: ARTICLE, q: "home_side_set:blog,own_page_side_set:blog", v: "isolation(home_side_set on article)", bundle: "article", catAside: false, set: "media", article: true });
  CASES.push({ face: "lp", path: "/lp/", q: "page_side:home,event_side:home,cat_side:article,side_nav:drawer-pc", v: "lp has none", bundle: "none", catAside: false, set: null, lp: true });
  const READ = ([visSrc]) => { const vis = eval(visSrc); const $ = (s) => document.querySelector(s); const layout = $(".wt-face-category") ? $(".wt-cat-layout") : $(".wt-side-layout"); const bl = Array.from(document.body.classList).find((x) => x.startsWith("wt-side-bundle-")); const right = $(".wt-side--right"); const main = $(".wt-side-main"); return { bundle: bl ? bl.replace("wt-side-bundle-", "") : null, face: (Array.from(document.body.classList).find((x) => x.startsWith("wt-face-")) || "").replace("wt-face-", ""), tracks: layout ? getComputedStyle(layout).gridTemplateColumns.split(" ").filter((x) => x && x !== "none").length : 0, rightVis: vis(right), catAsideVis: vis($(".wt-cat-aside")), widgets: right ? Array.from(right.querySelectorAll(":scope > .wt-side-widget")).filter(vis).map((w) => w.getAttribute("data-wt-widget")).filter((w) => !/^toc-/.test(w)) : [], mainTop: main ? main.getBoundingClientRect().top + scrollY : null, mainBottom: main ? main.getBoundingClientRect().bottom + scrollY : null, rightTop: vis(right) ? right.getBoundingClientRect().top + scrollY : null, h1: Array.from(document.querySelectorAll("h1")).filter(vis).length, sideEl: !!right, openBtnVis: vis($(".wt-side-drawer__open")), navVis: Array.from(document.querySelectorAll(".wt-sidenav, .wt-megamenu__trigger")).filter(vis).length, drawerOpen: !!$("#wt-side-drawer") && !$("#wt-side-drawer").hidden }; };
  for (const [dev, cfg, js] of [["pc", PC, true], ["sp", SP, true], ["sp", SP, false]]) {
    const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage();
    for (const c of CASES) {
      if (!js && !(c.face === "category" || c.face === "event") ) continue; // JS 無効は段 10c で共通サイドバーを新たに持った面（カテゴリ / イベント）だけ
      await p.goto(BASE + c.path + "?wt=" + c.q, { waitUntil: js ? "networkidle" : "load" });
      const r = await p.evaluate(READ, [VIS_SRC]);
      const isPc = dev === "pc"; const expectRight = c.set !== null; /* home_side_layout:none は束 home のまま非表示 */
      const spDrawer = !!c.drawer && !isPc && js; // SP・JS あり・drawer → aside は隠れボタンだけ出る
      let pass = r.bundle === c.bundle && r.face === c.face && r.h1 === 1 && r.rightVis === (expectRight && !spDrawer) && r.catAsideVis === c.catAside && !r.drawerOpen;
      if (c.lp) pass = r.bundle === "none" && r.face === "lp" && !r.sideEl && !r.rightVis && r.h1 === 1 && !r.openBtnVis && r.navVis === 0;
      else if (spDrawer) pass = pass && r.tracks === 1 && r.openBtnVis && r.navVis === 0;
      else if (expectRight) { pass = pass && JSON.stringify(r.widgets) === JSON.stringify(SETS2[c.set]) && (isPc ? r.tracks === 2 : r.tracks === 1 && r.rightTop >= r.mainBottom - 1) && !r.openBtnVis; }
      else pass = pass && r.tracks === (c.catAside && isPc ? 2 : 1) && (c.noNav === false ? r.navVis === 1 && !r.openBtnVis : (!r.openBtnVis && r.navVis === 0)); /* classic は段 6 の aside が PC で 2 列目。非表示はドロワーのボタンもサイドナビも出ない。HOME の OFF は HP 側の fixed-right-icons が出る */
      rows.push({ dev, js, ...c, ...r, pass });
    }
    await ctx.close();
  }
  const expectRows = 2 * CASES.length + CASES.filter((c) => c.face === "category" || c.face === "event").length;
  out.sideOwner = { bundles, registryOk, rows, pass: registryOk && CASES.length === FACES2.reduce((n, f) => n + bundles.length + 2 * f[3].length + 1, 0) + 2 + 5 && rows.length === expectRows && rows.every((x) => x.pass) };
}
// (r) formFace（段 11、WT-EVT-0289「フォームの項目追加とかの項目調査」/ WT-EVT-0301「進めて」）: フォーム面 /contact/（page.html + helix-wt/form）と完了 /thanks/（helix-wt/form-thanks）。11 軸の全値を PC / SP / SP JS 無効で検査: 項目の並び（種別 9 × セット 4 は inc/form.php の表と同じ順）、必須表示 2 型、レイアウト 5 型（2col = PC で form が 2 列、label-left = PC で行が 2 列、placeholder-only = ラベルは読み上げ専用で文字入力欄に placeholder、steps = JS ありは最初の段だけ可視・JS 無効は全部可視）、同意 3 型、送信文言 9 型、エラー 3 型（空送信 → 必須項目だけが is-error + aria-invalid、項目下 / 上部まとめ / 両方、aria-describedby の参照先が実在、まとめのリンク先が実在する入力、フォーカス先）、captcha 3 型、代替導線 5 型（電話は発信リンクにしない）、完了 2 型。添付は name を持たず form は multipart でない（非送信）。遷移（JS あり / なし）: 入力 → 確認（値の一致、修正するで値が戻る）→ 完了（separate = /thanks/ へ redirect し ?wt= を引き継ぐ / inline = 同 URL）、confirm=no は直接完了、inline-review は JS ありで同一ページ見直し・JS 無効は確認画面。steps は contact（3 段）/ newsletter（2 段）/ reservation（3 段、希望日は段 2）。nonce 改ざん・honeypot 入力は通常のエラー表示へ。形式検査の文言。網羅性: 台帳 observations.json の fields トークン（本体観察）が全部 form_fields:full の行にあること（対応表は verify 側に持ち、台帳から独立）。44px、h1 1 つ、外部要求なし、POST・同一ホスト・nonce
{
  const FORM = "/contact/", THANKS = "/thanks/";
  const wpP = WPCLIDIR ? (a) => execFileSync("docker", ["compose", "run", "--rm", "-T", "wpcli", ...a], { cwd: WPCLIDIR, encoding: "utf8" }) : null;
  let pageSource = "unavailable";
  if (wpP) { try { for (const [slug, title, block] of [["contact", "お問い合わせ（PoC）", "helix-wt/form"], ["thanks", "送信完了（PoC）", "helix-wt/form-thanks"]]) { const content = `<!-- wp:group {"className":"wt-page-form","align":"full","layout":{"type":"constrained","contentSize":"1120px"}} --><div class="wp-block-group alignfull wt-page-form"><!-- wp:${block} /--></div><!-- /wp:group -->`; const id = wpP(["post", "list", "--post_type=page", `--name=${slug}`, "--post_status=publish", "--field=ID"]).trim(); if (id) wpP(["post", "update", id, `--post_content=${content}`]); else wpP(["post", "create", "--post_type=page", "--post_status=publish", `--post_name=${slug}`, `--post_title=${title}`, `--post_content=${content}`]); } pageSource = "wp-cli"; } catch (e) { pageSource = `wp-cli failed: ${String(e).slice(0, 120)}`; } }
  const KINDS = { contact: ["name", "name-kana", "company", "email", "tel", "subject-select", "message"], apply: ["name", "email", "tel", "subject-radio", "people-count", "message"], download: ["name", "company", "email", "tel", "postal", "address", "faculty"], reservation: ["name", "name-kana", "email", "tel", "date-pref", "time-pref", "people-count", "birthdate", "gender", "grade", "school-name", "message"], newsletter: ["email"], recruit: ["name", "name-kana", "email", "tel", "postal", "address", "subject-select", "date-pref", "message", "attachment"], quote: ["company", "department", "position", "name", "email", "tel", "industry-select", "business-model", "budget-select", "message", "attachment"], trial: ["name", "email", "tel", "company", "relationship", "grade", "how-found"], diagnosis: ["company", "name", "email", "tel", "industry-select", "subject-radio", "eligibility-questions"] };
  const FULL = ["name", "name-kana", "company", "company-kana", "department", "position", "email", "email-confirm", "tel", "fax", "postal", "address", "url", "subject-select", "subject-radio", "subject-free", "message", "attachment", "date-pref", "time-pref", "people-count", "industry-select", "business-model", "employee-count", "revenue-select", "founded-date", "budget-select", "product-select", "order-number", "frequency", "how-found", "gender", "birthdate", "occupation", "relationship", "guardian", "grade", "school-type", "school-name", "faculty", "classroom", "teacher-name", "format-radio", "option-questions", "eligibility-questions", "newsletter-optin", "hidden-tracking"];
  const SETS = { minimal: ["name", "email", "message"], standard: ["name", "company", "email", "tel", "subject-select", "message"], full: FULL };
  const REQ = new Set(["name", "name-kana", "email", "email-confirm", "subject-select", "subject-radio", "message", "date-pref", "time-pref", "people-count", "industry-select", "eligibility-questions", "consent", "captcha"]);
  const SUBMIT = { send: "送信する", "send-plain": "送信", confirm: "確認画面へ", check: "確認する", apply: "申し込む", register: "登録する", download: "ダウンロード", next: "次へ進む" };
  // 台帳の観察トークン → 項目キー（台帳 CODING-BRIEF の語彙 + other:*。verify 側の対応表 = 実装 'obs' から独立）
  const OBS = { name: "name", "name-kana": "name-kana", company: "company", department: "department", position: "position", email: "email", "email-confirm": "email-confirm", tel: "tel", postal: "postal", address: "address", "subject-select": "subject-select", "subject-radio": "subject-radio", message: "message", attachment: "attachment", "date-pref": "date-pref", "time-pref": "time-pref", "people-count": "people-count", "how-found": "how-found", "consent-checkbox": "consent", "privacy-link": "privacy-link", captcha: "captcha", "other:gender": "gender", "other:industry": "industry-select", "other:grade": "grade", "other:birthdate": "birthdate", "other:school-name": "school-name", "other:faculty": "faculty", "other:business-model": "business-model", "other:hidden-tracking": "hidden-tracking", "other:product": "product-select", "other:occupation": "occupation", "other:school-type": "school-type", "other:relationship": "relationship", "other:revenue": "revenue-select", "other:employee-count": "employee-count", "other:founded-date": "founded-date", "other:eligibility-questions": "eligibility-questions", "other:company-kana": "company-kana", "other:order-number": "order-number", "other:option-questions": "option-questions", "other:format": "format-radio", "other:guardian": "guardian", "other:teacher-name": "teacher-name", "other:fax": "fax", "other:frequency": "frequency", "other:classroom": "classroom", "other:subject-free": "subject-free" };
  const expectFields = (kind, set, consent, captcha) => { const f = [...(set === "by-kind" ? KINDS[kind] : SETS[set])]; if (captcha === "question") f.push("captcha"); f.push(consent === "checkbox" ? "consent" : "privacy-link"); return f; };
  const AX = { form_kind: Object.keys(KINDS), form_fields: ["minimal", "standard", "full"], form_required: ["label"], form_layout: ["2col", "label-left", "placeholder-only", "steps"], form_consent: ["link-only", "in-submit"], form_submit: Object.keys(SUBMIT), form_error: ["top-summary", "both"], form_captcha: ["question", "external-slot"], form_side: ["none", "email", "chat", "messaging-app"] }; // 既定値（contact / by-kind / asterisk / 1col / checkbox / auto / inline / none / tel）は form_kind:contact の行で検査
  const STEP1 = ["name", "name-kana", "company", "company-kana", "department", "position", "email", "email-confirm", "tel", "fax", "postal", "address", "url", "gender", "birthdate", "occupation", "relationship", "guardian", "grade", "school-type", "school-name", "faculty", "employee-count", "revenue-select", "founded-date", "business-model", "hidden-tracking"]; const STEP3 = ["consent", "privacy-link", "captcha", "newsletter-optin"];
  const stepOf = (f) => STEP3.includes(f) ? 3 : STEP1.includes(f) ? 1 : 2;
  const baseHost = new URL(BASE).host;
  const READ = ([visSrc]) => { const vis = eval(visSrc); const $ = (s) => document.querySelector(s); const $$ = (s) => Array.from(document.querySelectorAll(s)); const form = $(".wt-form__form"); const rows = $$(".wt-form__row"); const marks = rows.filter((r) => r.querySelector(".wt-form__req")).map((r) => [r.getAttribute("data-wt-field"), r.querySelector(".wt-form__req").className.includes("--label") ? "label" : "asterisk"]);
    const hiddenLabel = rows.filter((r) => vis(r) && r.querySelector(".wt-form__label")).map((r) => { const l = r.querySelector(".wt-form__label"); const b = l.getBoundingClientRect(); return [r.getAttribute("data-wt-field"), b.width <= 1 && b.height <= 1]; });
    const noPh = rows.filter(vis).flatMap((r) => Array.from(r.querySelectorAll("input[type=text], input[type=email], input[type=tel], input[type=url], textarea"))).filter((i) => !i.closest(".wt-form__row--captcha") && !i.getAttribute("placeholder")).length;
    const taps = $$(".wt-form a, .wt-form button, .wt-form input:not([type=hidden]):not([type=checkbox]):not([type=radio]), .wt-form select, .wt-form textarea, .wt-form label:has(> input[type=checkbox]), .wt-form label:has(> input[type=radio])").filter(vis).filter((el) => !el.closest(".wt-form__hp")); /* honeypot は画面外・操作対象外 */ const below44 = taps.filter((el) => { const r = el.getBoundingClientRect(); const inline = el.tagName === "A" && getComputedStyle(el).display === "inline"; return !inline && Math.min(r.width, r.height) < 44; }).map((el) => (el.id || el.className || el.tagName) + " " + Math.round(el.getBoundingClientRect().width) + "x" + Math.round(el.getBoundingClientRect().height));
    const bodyAx = Array.from(document.body.classList).filter((c) => c.startsWith("wt-form-"));
    const invalidRows = rows.filter((r) => r.querySelector("[aria-invalid=true]")); const descOk = invalidRows.every((r) => { const i = r.querySelector("[aria-invalid=true]"); const d = i.getAttribute("aria-describedby"); return !!d && !!document.getElementById(d); });
    return { fields: rows.map((r) => r.getAttribute("data-wt-field")), visibleFields: rows.filter(vis).map((r) => r.getAttribute("data-wt-field")), marks, hiddenLabel, noPh, submit: form ? (form.querySelector(".wt-form__submit") || {}).textContent : null, submitVis: vis(form && form.querySelector(".wt-form__submit")), nextVis: vis($(".wt-form__next")), steps: $$(".wt-form__steps li").map((l) => [l.getAttribute("data-wt-step-i"), l.className]), fieldsets: $$(".wt-form__step").map((f) => f.getAttribute("data-wt-step-i")), formCols: form ? getComputedStyle(form).gridTemplateColumns.split(" ").filter((x) => x && x !== "none").length : 0, rowCols: rows[0] ? getComputedStyle(rows[0]).gridTemplateColumns.split(" ").filter((x) => x && x !== "none").length : 0, consentBox: !!$("#wt-f-consent"), privacyText: vis($(".wt-form__privacy")), captchaRow: vis($(".wt-form__row--captcha")), captchaSlot: vis($(".wt-form__captcha-slot")), side: $(".wt-form__side") ? { tel: !!$(".wt-form__side .wt-form__side-tel"), mail: !!$(".wt-form__side a[href^='mailto:']"), chat: !!$(".wt-form__side button"), app: !!$(".wt-form__side a[href*='#line']"), vis: vis($(".wt-form__side")) } : null, noDial: $$(".wt-form a[href^='tel:']").length === 0, method: form ? (form.getAttribute("method") || "").toLowerCase() : null, sameHost: form ? new URL(form.getAttribute("action"), location.href).host === location.host : null, nonce: !!(form && form.querySelector("input[name=wt_form_nonce]")), hp: !!(form && form.querySelector(".wt-form__hp input")), notMultipart: !!form && form.enctype !== "multipart/form-data", fileNoName: $$(".wt-form input[type=file]").every((i) => !i.name), below44, h1: $$("h1").filter(vis).length, bodyAx, errRows: rows.filter((r) => r.classList.contains("is-error")).map((r) => r.getAttribute("data-wt-field")), errInline: $$(".wt-form__error").filter(vis).length, summary: vis($("#wt-form-summary")), summaryLinks: $$("#wt-form-summary a").map((a) => a.getAttribute("href")), summaryTargetsOk: $$("#wt-form-summary a").every((a) => { const t = document.getElementById(a.getAttribute("href").slice(1)); return !!t && /^(INPUT|SELECT|TEXTAREA)$/.test(t.tagName); }), focused: document.activeElement ? (document.activeElement.id || document.activeElement.tagName) : null, invalid: invalidRows.map((r) => r.getAttribute("data-wt-field")), describedBy: invalidRows.map((r) => r.querySelector("[aria-invalid=true]").getAttribute("aria-describedby")), descOk, step: $(".wt-form") ? $(".wt-form").getAttribute("data-wt-step") : null, jsReady: !!(form && form.getAttribute("data-wt-js")), summaryText: ($(".wt-form__summary") || {}).textContent || "" }; };
  const rows = [];
  const goto = async (p, url, js) => { await p.goto(BASE + url, { waitUntil: js ? "networkidle" : "load" }); };
  const clickSubmit = async (p) => { const btn = await p.$(".wt-form__form .wt-form__submit:not([hidden])"); if (btn) await btn.click(); else await p.click(".wt-form__next"); await p.waitForTimeout(500); };
  const focusIdOf = (f) => f === "date-pref" ? `wt-f-${f}-1` : ["subject-radio", "gender", "format-radio", "option-questions"].includes(f) ? `wt-f-${f}-0` : f === "eligibility-questions" ? `wt-f-${f}-0-y` : `wt-f-${f}`;
  // 網羅性: 台帳の本体観察 fields トークン → 対応表 → form_fields:full の行に全部あること。対応表に無いトークンがあれば fail
  { const ledger = JSON.parse(fs.readFileSync(path.resolve("../2026-09-05-parts-pattern-taxonomy/sidebar-forms-gap-survey/forms/observations.json"), "utf8")); const obsRows = Array.isArray(ledger) ? ledger : (ledger.rows || ledger.observations || Object.values(ledger)[0]);
    const tokens = Array.from(new Set(obsRows.filter((r) => r && r.form_presence === "observed").flatMap((r) => String(r.fields || "").split(",").map((x) => x.trim()).filter(Boolean)))).sort();
    const unmapped = tokens.filter((t) => !(t in OBS)); const ctx = await browser.newContext(PC); const p = await ctx.newPage(); await goto(p, FORM + "?wt=form_fields:full,form_captcha:question", true);
    const present = await p.$$eval(".wt-form__row", (a) => a.map((x) => x.getAttribute("data-wt-field"))); await ctx.close();
    const missing = tokens.filter((t) => OBS[t] && !present.includes(OBS[t]) && OBS[t] !== "privacy-link"); // privacy-link は form_consent:link-only の行で出る（同じ検査の consent 軸）
    rows.push({ dev: "pc", js: true, axis: "coverage", v: "ledger-fields", tokens: tokens.length, unmapped, missing, present: present.length, pass: tokens.length >= 40 && unmapped.length === 0 && missing.length === 0 && present.length === FULL.length + 2 /* full 47 + captcha + consent = 49 */ }); }
  for (const [dev, cfg, js] of [["pc", PC, true], ["sp", SP, true], ["sp", SP, false]]) {
    const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage(); let external = []; p.on("request", (req) => { try { const u = new URL(req.url()); if (/^https?:$/.test(u.protocol) && u.host !== baseHost) external.push(u.host); } catch (_) { /* data: */ } });
    for (const [axis, values] of Object.entries(AX)) for (const v of values) {
      external = []; const q = `${axis}:${v}`; await goto(p, FORM + "?wt=" + q, js);
      const r0 = await p.evaluate(READ, [VIS_SRC]);
      await clickSubmit(p); // 空送信 → 必須項目のエラー
      const r1 = await p.evaluate(READ, [VIS_SRC]);
      const kind = axis === "form_kind" ? v : "contact", set = axis === "form_fields" ? v : "by-kind", consent = axis === "form_consent" ? v : "checkbox", captcha = axis === "form_captcha" ? v : "none", layout = axis === "form_layout" ? v : "1col", errmode = axis === "form_error" ? v : "inline", side = axis === "form_side" ? v : "tel", required = axis === "form_required" ? v : "asterisk";
      const ef = expectFields(kind, set, consent, captcha); const reqF = ef.filter((f) => REQ.has(f)); const isPc = dev === "pc";
      let expectSubmit = axis === "form_submit" ? SUBMIT[v] : "入力内容を確認する"; if (consent === "in-submit") expectSubmit = "個人情報の取り扱いに同意して" + expectSubmit;
      const steps = layout === "steps"; const presentSteps = Array.from(new Set(ef.map(stepOf))).sort();
      let pass = r0.bodyAx.includes(`wt-${axis.replace(/_/g, "-")}-${v}`) && JSON.stringify(r0.fields) === JSON.stringify(ef) && r0.h1 === 1 && r0.method === "post" && r0.sameHost && r0.nonce && r0.hp && r0.notMultipart && r0.fileNoName && r0.noDial && r0.below44.length === 0 && external.length === 0 && r0.jsReady === js && r0.step === "input";
      pass = pass && r0.marks.every(([f, m]) => m === required) && r0.marks.map(([f]) => f).join() === reqF.filter((f) => f !== "consent").join(); // 必須印は必須項目（同意チェックはラベルなし）
      pass = pass && r0.submit === expectSubmit && r0.consentBox === (consent === "checkbox") && r0.privacyText === (consent !== "checkbox") && r0.captchaRow === (captcha === "question") && r0.captchaSlot === (captcha === "external-slot");
      pass = pass && (side === "none" ? r0.side === null : (!!r0.side && r0.side.vis && r0.side.tel === (side === "tel") && r0.side.mail === (side === "email") && r0.side.chat === (side === "chat") && r0.side.app === (side === "messaging-app")));
      if (layout === "2col") pass = pass && r0.formCols === (isPc ? 2 : 1); else if (layout === "label-left") pass = pass && r0.rowCols === (isPc ? 2 : 1); else pass = pass && r0.formCols === 1 && r0.rowCols === 1;
      if (layout === "placeholder-only") pass = pass && r0.hiddenLabel.filter(([f]) => !["subject-radio", "eligibility-questions", "date-pref", "captcha", "attachment"].includes(f)).every(([, h]) => h) && r0.noPh === 0; else pass = pass && r0.hiddenLabel.every(([, h]) => !h);
      if (steps) { pass = pass && JSON.stringify(r0.steps.map(([i]) => i)) === JSON.stringify(presentSteps.map(String)) && JSON.stringify(r0.fieldsets) === JSON.stringify(presentSteps.map(String)) && (js ? (JSON.stringify(r0.visibleFields) === JSON.stringify(ef.filter((f) => stepOf(f) === presentSteps[0])) && r0.nextVis && !r0.submitVis && r0.steps[0][1].includes("is-current")) : (JSON.stringify(r0.visibleFields) === JSON.stringify(ef) && r0.submitVis && !r0.nextVis)); } else pass = pass && JSON.stringify(r0.visibleFields) === JSON.stringify(ef) && r0.submitVis && !r0.nextVis && r0.steps.length === 0;
      // 空送信の結果: 必須項目だけ is-error + aria-invalid（steps + JS は最初の段の必須だけ）。項目下 / まとめ / 両方。説明の参照先とリンク先が実在。フォーカス先
      const expectErr = steps && js ? reqF.filter((f) => stepOf(f) === presentSteps[0]) : reqF;
      pass = pass && JSON.stringify(r1.errRows) === JSON.stringify(expectErr) && JSON.stringify(r1.invalid) === JSON.stringify(expectErr) && r1.descOk && r1.step === "input" && r1.h1 === 1;
      if (errmode === "inline") pass = pass && r1.errInline === expectErr.length && !r1.summary && r1.describedBy.every((d, i) => d === `wt-f-${expectErr[i]}-err`) && r1.focused === focusIdOf(expectErr[0]);
      else { pass = pass && r1.summary && JSON.stringify(r1.summaryLinks) === JSON.stringify(expectErr.map((f) => "#" + focusIdOf(f))) && r1.summaryTargetsOk && r1.describedBy.every((d) => d === "wt-form-summary") && r1.focused === "wt-form-summary" && (errmode === "top-summary" ? r1.errInline === 0 : r1.errInline === expectErr.length); }
      rows.push({ dev, js, axis, v, expectFields: ef, expectSubmit, expectErr, before: r0, after: r1, external: Array.from(new Set(external)), pass });
    }
    // 形式検査の文言（不正値）
    { await goto(p, FORM + "?wt=form_fields:full,form_captcha:question", js); await p.fill("#wt-f-name", "山田"); await p.fill("#wt-f-name-kana", "ヤマダ"); await p.fill("#wt-f-company-kana", "カナ"); await p.fill("#wt-f-email", "a@example..com"); await p.fill("#wt-f-email-confirm", "bad2@example.com"); await p.fill("#wt-f-tel", "12"); await p.fill("#wt-f-fax", "12"); await p.fill("#wt-f-postal", "12"); await p.fill("#wt-f-url", "example.com"); await p.selectOption("#wt-f-subject-select", { index: 1 }); await p.fill("#wt-f-message", "x"); await p.fill("#wt-f-date-pref-2", "2026-10-01"); await p.selectOption("#wt-f-time-pref", { index: 1 }); await p.fill("#wt-f-people-count", "0"); await p.selectOption("#wt-f-industry-select", { index: 1 }); await p.check("#wt-f-eligibility-questions-0-y"); await p.fill("#wt-f-captcha", "8"); await p.check("#wt-f-consent"); await clickSubmit(p);
      const r = await p.evaluate(() => Array.from(document.querySelectorAll(".wt-form__row.is-error")).map((x) => [x.getAttribute("data-wt-field"), (x.querySelector(".wt-form__error") || {}).textContent || ""]));
      const expect = [["name-kana", "ひらがなで入力してください。"], ["company-kana", "ひらがなで入力してください。"], ["email", "メールアドレスの形式が正しくありません。"], ["email-confirm", "メールアドレスが一致しません。"], ["tel", "電話番号の形式が正しくありません。"], ["fax", "FAX 番号の形式が正しくありません。"], ["postal", "郵便番号は 7 桁で入力してください。"], ["url", "https:// から始まる URL を入力してください。"], ["subject-radio", "参加形式を選択してください。"], ["date-pref", "第 1 希望日を入力してください。"], ["people-count", "1 以上の数を入力してください。"], ["eligibility-questions", "3 つの質問すべてに答えてください。"], ["captcha", "答えが違います。"]]; // 第 2 希望だけ入れても第 1 希望が空なら NG（JS / サーバ同じ）、はいを 1 問だけでは NG
      rows.push({ dev, js, axis: "validate", v: "invalid-values", got: r, expect, pass: JSON.stringify(r) === JSON.stringify(expect) }); }
    // nonce 改ざん / honeypot 入力 → 通常のエラー表示（値は保持、完了しない）
    for (const [what, tamper, text] of [["nonce", (f) => { f.querySelector("input[name=wt_form_nonce]").value = "x"; }, "有効期限"], ["honeypot", (f) => { f.querySelector("input[name=wt_hp]").value = "bot"; }, "受け付けられません"]]) {
      await goto(p, FORM + "?wt=form_confirm:no,form_thanks:inline", js); await p.fill("#wt-f-name", "山田 太郎"); await p.fill("#wt-f-name-kana", "やまだ たろう"); await p.fill("#wt-f-email", "taro@example.com"); await p.selectOption("#wt-f-subject-select", { index: 1 }); await p.fill("#wt-f-message", "本文"); await p.check("#wt-f-consent");
      await p.$eval(".wt-form__form", tamper); await clickSubmit(p); await p.waitForTimeout(300);
      const r = await p.evaluate(() => ({ step: (document.querySelector(".wt-form") || { getAttribute: () => null }).getAttribute("data-wt-step"), thanks: !!document.querySelector(".wt-form-thanks"), summary: (document.querySelector(".wt-form__summary") || {}).textContent || "", name: (document.querySelector("#wt-f-name") || {}).value, focused: document.activeElement.className, h1: Array.from(document.querySelectorAll("h1")).filter((h) => h.offsetHeight).length }));
      rows.push({ dev, js, axis: "tamper", v: what, r, pass: r.step === "input" && !r.thanks && r.summary.includes(text) && r.name === "山田 太郎" && r.focused.includes("wt-form__summary") && r.h1 === 1 });
    }
    // 異常 POST（項目単位）: 単値の欄を配列で送る / yes-no を別の質問キーで送る / select の選択肢外 → その項目のエラーで入力へ戻る（確認・完了へ進まない）。JS ありでもフォームの DOM を書き換えてから送るので JS 検証を素通りしうる = サーバの検査
    for (const [what, q, tamper, expectErr, text] of [
      ["array-type", "form_confirm:yes", (f) => { f.querySelector("#wt-f-tel").name = "wt_form[tel][]"; f.querySelector("#wt-f-tel").value = "0312345678"; }, "tel", "電話番号の形式が正しくありません。"],
      ["question-keys", "form_confirm:yes,form_kind:diagnosis", (f) => { f.querySelectorAll(".wt-form__yesno input").forEach((i, k) => { i.name = "wt_form[eligibility_questions][" + (3 + Math.floor(k / 2)) + "]"; }); }, "eligibility-questions", "3 つの質問すべてに答えてください。"],
      ["choice-outside", "form_confirm:yes", (f) => { const o = f.querySelector("#wt-f-subject-select option:checked"); o.value = "不正な値"; }, "subject-select", "お問い合わせ種別の選択肢にありません。"],
      ["question-extra", "form_confirm:yes,form_kind:diagnosis", (f) => { const i = document.createElement("input"); i.type = "hidden"; i.name = "wt_form[eligibility_questions][3]"; i.value = "不正な値"; f.appendChild(i); }, "eligibility-questions", "3 つの質問すべてに答えてください。"], /* 3 問すべて答えた上で余分なキーを足す → 拒否 */
    ]) {
      await goto(p, FORM + "?wt=" + q, js); const kind = q.includes("diagnosis") ? "diagnosis" : "contact";
      await p.fill("#wt-f-name", "山田 太郎"); await p.fill("#wt-f-email", "taro@example.com"); await p.fill("#wt-f-tel", "03-1234-5678"); await p.check("#wt-f-consent");
      if (kind === "contact") { await p.fill("#wt-f-name-kana", "やまだ たろう"); await p.selectOption("#wt-f-subject-select", { index: 1 }); await p.fill("#wt-f-message", "本文"); } else { await p.selectOption("#wt-f-industry-select", { index: 1 }); await p.check("#wt-f-subject-radio-0"); for (const i of [0, 1, 2]) await p.check(`#wt-f-eligibility-questions-${i}-y`); }
      await p.$eval(".wt-form__form", tamper); await p.$eval(".wt-form__form", (f) => f.submit()); /* HTMLFormElement.submit() は submit イベントを起こさない = JS 検証を通さず素の POST */ await p.waitForTimeout(800);
      const r = await p.evaluate(() => ({ step: (document.querySelector(".wt-form") || { getAttribute: () => null }).getAttribute("data-wt-step"), thanks: !!document.querySelector(".wt-form-thanks"), err: Array.from(document.querySelectorAll(".wt-form__row.is-error")).map((x) => [x.getAttribute("data-wt-field"), (x.querySelector(".wt-form__error") || {}).textContent || ""]), h1: Array.from(document.querySelectorAll("h1")).filter((h) => h.offsetHeight).length }));
      rows.push({ dev, js, axis: "tamper", v: what, r, pass: r.step === "input" && !r.thanks && r.err.length === 1 && r.err[0][0] === expectErr && r.err[0][1] === text && r.h1 === 1 });
    }
    // メールの規則（JS = isEmail / サーバ = is_email）が同じ: 許可 o'hara@example.com、拒否 a@-example.com / a@.example.com / a@example.com. / a@example..com / a@example
    { const cases = [["o'hara@example.com", false], ["a@-example.com", true], ["a@.example.com", true], ["a@example.com.", true], ["a@example..com", true], ["a@example", true]]; const got = [];
      for (const [mail] of cases) { await goto(p, FORM + "?wt=form_confirm:no,form_thanks:inline", js); await p.fill("#wt-f-name", "山田 太郎"); await p.fill("#wt-f-name-kana", "やまだ たろう"); await p.fill("#wt-f-email", mail); if (await p.$("#wt-f-email-confirm")) await p.fill("#wt-f-email-confirm", mail); await p.selectOption("#wt-f-subject-select", { index: 1 }); await p.fill("#wt-f-message", "本文"); await p.check("#wt-f-consent"); await clickSubmit(p); await p.waitForTimeout(300);
        got.push([mail, await p.evaluate(() => !!document.querySelector('.wt-form__row[data-wt-field="email"].is-error'))]); }
      rows.push({ dev, js, axis: "validate", v: "email-rules", got, pass: JSON.stringify(got) === JSON.stringify(cases) }); }
    // top-summary × ラジオ / 希望日 / yes-no を持つ種別（diagnosis / reservation）: まとめのリンク先が実在する入力で、aria-describedby がまとめを指す
    for (const kind of ["diagnosis", "reservation"]) {
      await goto(p, FORM + `?wt=form_error:top-summary,form_kind:${kind}`, js); await clickSubmit(p); const r1 = await p.evaluate(READ, [VIS_SRC]);
      const reqF = expectFields(kind, "by-kind", "checkbox", "none").filter((f) => REQ.has(f));
      rows.push({ dev, js, axis: "summary-kind", v: kind, r1: { errRows: r1.errRows, invalid: r1.invalid, links: r1.summaryLinks, targetsOk: r1.summaryTargetsOk, describedBy: r1.describedBy, focused: r1.focused }, pass: JSON.stringify(r1.errRows) === JSON.stringify(reqF) && JSON.stringify(r1.invalid) === JSON.stringify(reqF) && r1.summary && JSON.stringify(r1.summaryLinks) === JSON.stringify(reqF.map((f) => "#" + focusIdOf(f))) && r1.summaryTargetsOk && r1.describedBy.every((d) => d === "wt-form-summary") && r1.descOk && r1.focused === "wt-form-summary" && r1.errInline === 0 });
    }
    // 遷移: confirm 3 型 × thanks 2 型（JS あり / なし）
    for (const confirm of ["yes", "no", "inline-review"]) for (const thanks of ["separate", "inline"]) {
      external = []; const q = `form_confirm:${confirm},form_thanks:${thanks}`; await goto(p, FORM + "?wt=" + q, js);
      const VALUES = { "#wt-f-name": "山田 太郎", "#wt-f-name-kana": "やまだ たろう", "#wt-f-company": "株式会社サンプル", "#wt-f-email": "taro@example.com", "#wt-f-tel": "03-1234-5678", "#wt-f-message": "テスト本文\n2 行目" };
      for (const [sel, val] of Object.entries(VALUES)) await p.fill(sel, val); await p.selectOption("#wt-f-subject-select", { index: 2 }); await p.check("#wt-f-consent");
      await clickSubmit(p); await p.waitForTimeout(400);
      const step1 = await p.evaluate(() => ({ url: location.pathname + location.search, step: (document.querySelector(".wt-form") || { getAttribute: () => null }).getAttribute("data-wt-step"), reviewing: !!document.querySelector(".wt-form__form.is-reviewing"), review: Array.from(document.querySelectorAll(".wt-form__review dd, .wt-form__inline-review:not([hidden]) dd")).map((d) => [d.getAttribute("data-wt-field"), d.textContent]), thanks: !!document.querySelector(".wt-form-thanks"), h1: Array.from(document.querySelectorAll("h1")).filter((h) => h.offsetHeight).length }));
      const expectConfirm = confirm === "yes" || (confirm === "inline-review"); const expectReview = [["name", "山田 太郎"], ["name-kana", "やまだ たろう"], ["company", "株式会社サンプル"], ["email", "taro@example.com"], ["tel", "03-1234-5678"], ["subject-select", "料金について"], ["message", "テスト本文\n2 行目"], ["consent", "同意する"]];
      let pass = step1.h1 === 1 && external.length === 0;
      if (expectConfirm) { pass = pass && (confirm === "inline-review" && js ? step1.reviewing && step1.step === "input" : step1.step === "confirm") && JSON.stringify(step1.review.map(([f, t]) => [f, t.replace(/\r/g, "")])) === JSON.stringify(expectReview) && !step1.thanks;
        // 修正する → 値が戻る
        await p.click(confirm === "inline-review" && js ? ".wt-form__review-edit" : ".wt-form__confirm .wt-form__back"); await p.waitForTimeout(400);
        const back = await p.evaluate((vals) => Object.fromEntries(Object.entries(vals).map(([sel, v]) => [sel, (document.querySelector(sel) || {}).value === v])), VALUES); pass = pass && Object.values(back).every(Boolean) && await p.evaluate(() => document.querySelector("#wt-f-consent").checked);
        await clickSubmit(p); await p.waitForTimeout(400); await p.click(confirm === "inline-review" && js ? ".wt-form__review-send" : ".wt-form__confirm .wt-form__submit"); await p.waitForTimeout(800); }
      else pass = pass && step1.step === (thanks === "inline" ? "done" : null);
      const done = await p.evaluate(() => ({ url: location.pathname + location.search, thanks: (document.querySelector(".wt-form-thanks") || { getAttribute: () => null }).getAttribute("data-wt-thanks"), inline: !!document.querySelector(".wt-form-thanks--inline"), form: !!document.querySelector(".wt-form__form"), h1: Array.from(document.querySelectorAll("h1")).filter((h) => h.offsetHeight).length, btn: !!document.querySelector(".wt-form__thanks-btn") }));
      pass = pass && done.thanks === "contact" && done.h1 === 1 && done.btn && !done.form && (thanks === "separate" ? done.url === THANKS + "?wt=" + encodeURIComponent(q) && !done.inline : done.url.startsWith(FORM) && done.inline);
      rows.push({ dev, js, axis: "flow", v: q, step1, done, external: Array.from(new Set(external)), pass });
    }
    // steps の段階送り（JS あり）: contact 3 段 / newsletter 2 段（基本情報 → 確認・同意）/ reservation 3 段（希望日は段 2）。最初の段の不備はその段に留まる → 埋めて次へ → … → 最後の段で送信 → 確認へ
    if (js) for (const kind of ["contact", "newsletter", "reservation"]) { await goto(p, FORM + `?wt=form_layout:steps,form_kind:${kind}`, js); await p.click(".wt-form__next"); await p.waitForTimeout(300);
      const ef = expectFields(kind, "by-kind", "checkbox", "none"); const ps = Array.from(new Set(ef.map(stepOf))).sort(); const reqAt = (n) => ef.filter((f) => REQ.has(f) && stepOf(f) === n);
      const s1 = await p.evaluate(() => ({ cur: document.querySelector(".wt-form__steps li.is-current").getAttribute("data-wt-step-i"), err: Array.from(document.querySelectorAll(".wt-form__row.is-error")).map((r) => r.getAttribute("data-wt-field")) }));
      const fillStep = async (n) => { for (const f of ef.filter((x) => stepOf(x) === n)) { const id = "#wt-f-" + f; if (f === "name") await p.fill(id, "山田 太郎"); else if (f === "name-kana") await p.fill(id, "やまだ たろう"); else if (f === "email") await p.fill(id, "taro@example.com"); else if (f === "subject-select" || f === "time-pref") await p.selectOption(id, { index: 1 }); else if (f === "message") await p.fill(id, "本文"); else if (f === "date-pref") await p.fill(id + "-1", "2026-10-01"); else if (f === "people-count") await p.fill(id, "2"); else if (f === "consent") await p.check(id); } };
      const seen = [];
      for (let k = 0; k < ps.length; k++) { await fillStep(ps[k]); const st = await p.evaluate(() => ({ cur: document.querySelector(".wt-form__steps li.is-current").getAttribute("data-wt-step-i"), done: document.querySelectorAll(".wt-form__steps li.is-done").length, vis: Array.from(document.querySelectorAll(".wt-form__row")).filter((r) => r.offsetHeight).map((r) => r.getAttribute("data-wt-field")), prevVis: !!document.querySelector(".wt-form__prev") && !document.querySelector(".wt-form__prev").hidden, submitVis: !document.querySelector(".wt-form__submit").hidden, nextVis: !document.querySelector(".wt-form__next").hidden })); seen.push(st); if (k < ps.length - 1) { await p.click(".wt-form__next"); await p.waitForTimeout(300); } }
      await p.click(".wt-form__submit"); await p.waitForTimeout(500); const s4 = await p.evaluate(() => (document.querySelector(".wt-form") || { getAttribute: () => null }).getAttribute("data-wt-step"));
      const pass = s1.cur === String(ps[0]) && JSON.stringify(s1.err) === JSON.stringify(reqAt(ps[0])) && seen.length === ps.length && seen.every((st, k) => st.cur === String(ps[k]) && st.done === k && JSON.stringify(st.vis) === JSON.stringify(ef.filter((f) => stepOf(f) === ps[k])) && st.prevVis === (k > 0) && st.submitVis === (k === ps.length - 1) && st.nextVis === (k < ps.length - 1)) && s4 === "confirm";
      rows.push({ dev, js, axis: "flow", v: `steps:${kind}`, steps: ps, s1, seen, s4, pass }); }
    await ctx.close();
  }
  out.formFace = { pageSource, rows, pass: pageSource === "wp-cli" && rows.length === 160 && rows.every((x) => x.pass) }; // 1（網羅性）+ 3 × (35 軸行 + 1 形式 + 1 メール規則 + 2 改ざん + 4 異常 POST + 2 まとめ × 種別 + 6 遷移) + 2 × 3 steps = 160。件数は固定（軸の削除で減れば fail）
}
// (s) chromeOwner（段 12、PO 反応 25 回目 WT-EVT-0302「増やす方向で」、階層整理 WT-EVT-0296 ①「共通のヘッダー、フッターで全体制御」、決定 WT-EVT-0299「継承・独自設定・非表示を分離」）: ヘッダー・フッター・固定 CTA の所属が面ごとに決まること。束の台帳は <meta name="wt-chrome-bundles">（site / own）と面の一覧 <meta name="wt-chrome-faces"> から読む。
// 面 6（HOME / 記事 / カテゴリ / イベント / 固定ページ / LP）× 部位 3 × 所属（site = 共通の軸をそのまま、own = own_<face>_* だけを見る、off = 描画しない、LP のヘッダーは lp = テンプレート内の LP ヘッダー）。
// 検査: site は共通軸の値（header:cta / footer_layout:columns-3 / fixed:float-cta）が出る、own は own_<face>_*（tel / single-row / float-cta。HOME・イベント・LP の固定 CTA は home_fixed / event_fixed / lp_fixed）だけが出て他面の own を読まない、off は要素が無い（ヘッダー・フッター）/ 固定 CTA が 1 つも見えない、LP の lp は共通ヘッダー無し + LP ヘッダー 1 つ。他面（404）は共通。h1 は 1 つ
{
  const rows = [];
  const ctx0 = await browser.newContext(PC); const p0 = await ctx0.newPage(); await p0.goto(BASE + HOME, { waitUntil: "load" });
  const reg = await p0.evaluate(() => ({ bundles: (document.querySelector('meta[name="wt-chrome-bundles"]')?.getAttribute("content") || "").split(",").filter(Boolean), faces: (document.querySelector('meta[name="wt-chrome-faces"]')?.getAttribute("content") || "").split(",").filter(Boolean) })); await ctx0.close();
  const PATHS = { home: HOME, article: ARTICLE, category: CATEGORY, event: "/event/", page: "/parts/", lp: LP };
  const OWNFIX = { home: ["home_fixed", "float-cta", "wt-home-fixed--float-cta"], event: ["event_fixed", "float-apply", "wt-event-fixed--float-apply"], lp: ["lp_fixed", "float-cta", "wt-lp-fixed--float-cta"] }; // 既存軸を own の束として使う面
  const registryOk = ["site", "own"].every((b) => reg.bundles.includes(b)) && reg.bundles.length === 2 && reg.faces.length === 6 && reg.faces.every((f) => f in PATHS);
  const pre = (f) => f === "category" ? "cat" : f;
  const CASES = [];
  for (const face of reg.faces) {
    const others = reg.faces.filter((f) => f !== face);
    for (const v of [...reg.bundles, "off", ...(face === "lp" ? ["lp"] : [])]) CASES.push({ part: "head", face, v, q: `${pre(face)}_head:${v},header:cta,sp:search,own_${face}_header:tel,own_${face}_sp:cta,lp_header:logo-only` });
    CASES.push({ part: "head", face, v: "own-isolation", q: `${pre(face)}_head:own,own_${face}_header:tel,own_${face}_sp:cta,header:cta,sp:search,${others.map((f) => `own_${f}_header:band,own_${f}_sp:left`).join(",")}` }); // own は自面の軸だけ
    for (const v of [...reg.bundles, "off"]) CASES.push({ part: "foot", face, v, q: `${pre(face)}_foot:${v},footer_layout:columns-3,footer_above:none,own_${face}_footer_layout:single-row,own_${face}_footer_above:cta-band,${others.map((f) => `own_${f}_footer_layout:sitemap,own_${f}_footer_above:newsletter`).join(",")}` });
    for (const v of [...reg.bundles, "off"]) { const of = OWNFIX[face] || [`own_${face}_fixed`, "float-cta", "wt-fixed--float-cta"]; CASES.push({ part: "fix", face, v, q: `${pre(face)}_fix:${v},fixed:float-cta,${of[0]}:${of[1]},${others.map((f) => `${(OWNFIX[f] || [`own_${f}_fixed`])[0]}:sp-bottom-bar`).join(",")}`, ownSel: of[2] }); }
  }
  const READ = ([visSrc]) => { const vis = eval(visSrc); const $$ = (s) => Array.from(document.querySelectorAll(s)); const cls = (el, pfx) => (Array.from(el.classList).find((c) => c.startsWith(pfx) && c !== pfx.slice(0, -2)) || "").slice(pfx.length);
    const heads = $$(".wt-header"); const headVis = heads.filter(vis); const lpHeads = $$(".wt-lp-header").filter(vis);
    const b = (p) => (Array.from(document.body.classList).find((c) => c.startsWith(`wt-${p}-bundle-`)) || "").slice(`wt-${p}-bundle-`.length);
    return { faces: Array.from(document.body.classList).filter((c) => c.startsWith("wt-face-")), bundles: { head: b("head"), foot: b("foot"), fix: b("fix") }, headCount: heads.length, headVis: headVis.length, headVar: headVis.length ? (cls(headVis[0], "wt-header--") || "search") : null, lpHead: lpHeads.length ? lpHeads.map((e) => cls(e, "wt-lp-header--")) : [],
      spCta: $$(".wt-header__spcta").filter(vis).length, footerEl: !!document.querySelector(".wt-footer"), footAbove: $$(".wt-footer__above-slot").filter(vis).map((e) => cls(e, "wt-footer__above-slot--")), footLayout: $$(".wt-footer__layout").filter(vis).map((e) => cls(e, "wt-footer__layout--")), fixedVis: $$(".wt-fixed, .wt-home-fixed, .wt-event-fixed, .wt-lp-fixed").filter(vis).map((e) => Array.from(e.classList).find((c) => c.includes("-fixed--"))), h1: $$("h1").filter((h) => h.offsetHeight).length }; };
  for (const [dev, cfg, js] of [["pc", PC, true], ["sp", SP, true], ["sp", SP, false]]) {
    const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage();
    for (const c of CASES) {
      if (!js && c.part !== "head") continue; // JS 無効はヘッダー（サーバ描画の有無）だけ
      await p.goto(BASE + PATHS[c.face] + "?wt=" + c.q, { waitUntil: js ? "networkidle" : "load" });
      const r = await p.evaluate(READ, [VIS_SRC]);
      const eb = c.v === "own-isolation" ? "own" : c.v === "off" ? "none" : c.v; // 期待する束 class
      let pass = r.faces.includes("wt-face-" + c.face) && r.h1 === 1 && r.bundles[c.part] === eb;
      if (c.part === "head") {
        if (c.v === "site") pass = pass && r.headCount === 1 && r.headVis === 1 && r.headVar === "cta" && r.lpHead.length === 0 && r.spCta === 0; /* 共通の sp:search */
        else if (c.v === "own" || c.v === "own-isolation") pass = pass && r.headCount === 1 && r.headVis === 1 && r.headVar === "tel" && r.lpHead.length === 0 && r.spCta === (dev === "sp" ? 1 : 0); /* own の sp:cta は SP で CTA が出る */
        else if (c.v === "off") pass = pass && r.headCount === 0 && r.lpHead.length === 0;
        else pass = pass && r.headCount === 0 && JSON.stringify(r.lpHead) === JSON.stringify(["logo-only"]); // lp
      } else if (c.part === "foot") {
        if (c.v === "off") pass = pass && !r.footerEl;
        else pass = pass && r.footerEl && JSON.stringify(r.footLayout) === JSON.stringify([c.v === "site" ? "columns-3" : "single-row"]) && JSON.stringify(r.footAbove) === JSON.stringify(c.v === "site" ? [] : ["cta-band"]); /* own は footer_above も own_<face>_ を見る */
      } else {
        const expect = c.v === "site" ? ["wt-fixed--float-cta"] : c.v === "own" ? [c.ownSel] : [];
        pass = pass && JSON.stringify(r.fixedVis) === JSON.stringify(expect);
      }
      rows.push({ dev, js, ...c, ...r, pass });
    }
    if (dev === "pc") { await p.goto(BASE + "/no-such-page-wt-404/?wt=header:band,footer_layout:single-row", { waitUntil: "networkidle" }); const r = await p.evaluate(READ, [VIS_SRC]); rows.push({ dev, js, part: "other", face: "other", v: "site", ...r, pass: r.faces.includes("wt-face-other") && r.bundles.head === "site" && r.bundles.foot === "site" && r.headVar === "band" && JSON.stringify(r.footLayout) === JSON.stringify(["single-row"]) && r.h1 === 1 }); } // 他面（404）は共通
    await ctx.close();
  }
  out.chromeOwner = { reg, registryOk, rows, pass: registryOk && CASES.length === 61 && rows.length === 148 && rows.every((x) => x.pass) }; // 面 6 × (head 3 + foot 3 + fix 3 + isolation 1) + LP の lp 1 = 61 ケース。行 = head 25 × 3 + foot 18 × 2 + fix 18 × 2 + 404 1 = 148（固定値）
}
// (m) categoryVariants（段 6、WT-EVT-0283）: カテゴリ 12 軸の全型 × PC / SP / SP JS 無効。軸 class・当該型だけ可視・型固有の実体（件数 = wp-cli の投稿数、絞り込みリンクは 200 で同じカテゴリ面に留まる、並べ替えは先頭記事が変わる、右カラムの実トラック数、一覧の実カラム数、カード要素の可視、ランキングの置き場所、CTA の到達先・非送信フォーム・LINE グリフ）・h1 1 つ・44px・到達先なしのページ内リンク 0
{
  const AX = { cat_header: ["name-count", "name-only", "name-desc", "hero"], cat_lead: ["none", "lead-text", "editorial"], cat_children: ["none", "chips", "cards", "steps", "sidebar-tree", "image-banners"], cat_columns: ["sidebar-right", "1col"], cat_sidebar: ["standard", "with-cta", "full"], cat_list: ["grid", "text-list", "featured-grid", "grid-2", "thumb-list", "timeline"], cat_card: ["standard", "minimal", "rich"], cat_filter: ["none", "tabs", "year", "tag", "sort"], cat_pagination: ["numbers", "none", "load-more", "prev-next"], cat_ranking: ["none", "sidebar", "bottom", "top"], cat_pickup: ["none", "top-featured", "editor-pick-box"], cat_cta: ["none", "lp-banner", "newsletter", "line"] };
  let expectedCount = null, countSource = "unavailable";
  if (WPCLIDIR) {
    try { const wp = (a) => execFileSync("docker", ["compose", "run", "--rm", "-T", "wpcli", ...a], { cwd: WPCLIDIR, encoding: "utf8" }); const id = wp(["term", "get", "category", "topic-index", "--by=slug", "--field=term_id"]).trim(); expectedCount = parseInt(wp(["post", "list", "--post_type=post", "--post_status=publish", `--cat=${id}`, "--format=count"]).trim(), 10); countSource = `wp-cli:cat=${id}`; } catch (e) { countSource = `wp-cli failed: ${String(e).slice(0, 120)}`; }
  }
  const read = async (cfg, dev, js) => { const ctx = await browser.newContext({ ...cfg, javaScriptEnabled: js }); const p = await ctx.newPage(); const results = [];
    for (const [axis, values] of Object.entries(AX)) for (const v of values) {
      for (let tryN = 0; ; tryN++) { try { await p.goto(BASE + CATEGORY + `?wt=cat_side:classic,${axis}:${v}`, { waitUntil: js ? "networkidle" : "load" }); break; } catch (e) { if (tryN >= 1 || !/ERR_ABORTED/.test(String(e))) throw e; await p.waitForTimeout(500); } } // 段 10c 初回実行で前行（load-more）の残り処理が次の遷移を中断した（ERR_ABORTED）→ 1 回だけ再試行 // 段 10c: 段 6 の独自 aside の検査は cat_side:classic で
      const r = await p.evaluate(([axis, v, dev, js, visSrc]) => { const vis = eval(visSrc); const $ = (s) => document.querySelector(s); const $$ = (s) => Array.from(document.querySelectorAll(s)); const visN = (s) => $$(s).filter(vis).length; const tracks = (el) => el ? getComputedStyle(el).gridTemplateColumns.split(" ").filter((x) => x && x !== "none").length : 0;
        const cls = axis.replace(/_/g, "-"); const body = document.body.classList.contains(`wt-${cls}-${v}`);
        const taps = $$(".wt-category a, .wt-category button, .wt-category input, .wt-category select").filter(vis);
        const below44 = taps.filter((el) => { const r = el.getBoundingClientRect(); const inline = el.tagName === "A" && getComputedStyle(el).display === "inline" && el.parentElement && /^(P|LI|TD|B|SPAN|H2|H3)$/.test(el.parentElement.tagName); return !inline && Math.min(r.width, r.height) < 44; }).map((el) => (el.className || el.tagName).toString().slice(0, 60));
        const anchors = $$(".wt-category a[href^='#']").filter(vis).map((a) => { const id = a.getAttribute("href").slice(1); const t = id ? document.getElementById(id) : null; return { href: "#" + id, ok: !!t && vis(t) }; }); const deadAnchors = anchors.filter((a) => !a.ok).map((a) => a.href);
        const list = $(".wt-cat-list"); const card = list ? list.querySelector(".wt-cat-card") : null; const listTop = list ? list.getBoundingClientRect().top + scrollY : null;
        const o = { body, h1Visible: visN("h1"), below44, deadAnchors, txt: {} };
        if (axis === "cat_header") { const c = $(".wt-cat-head__count"); o.countVisible = vis(c); o.countText = c ? c.textContent.replace(/\s+/g, "") : null; o.countNum = c ? parseInt((c.querySelector("b") || c).textContent.replace(/[^\d]/g, ""), 10) : null; o.descVisible = vis($(".wt-cat-head__desc")); o.heroWhite = v === "hero" ? getComputedStyle($(".wt-cat-head h1")).color === "rgb(255, 255, 255)" : null; }
        if (axis === "cat_lead") { o.leadShown = ["lead-text", "editorial"].filter((x) => vis($(`.wt-cat-lead--${x}`))); const ed = $(".wt-cat-lead--editorial"); o.editorialH2 = ed ? !!ed.querySelector("h2") : false; o.editorialLen = ed ? ed.textContent.replace(/\s+/g, "").length : 0; }
        if (axis === "cat_children") { o.navShown = ["chips", "cards", "steps", "image-banners", "sidebar-tree"].filter((x) => vis($(`.wt-cat-children--${x}`))); const nav = $(`.wt-cat-children--${v}`); o.links = nav ? nav.querySelectorAll("a").length : 0; o.inAside = nav ? !!nav.closest(".wt-cat-aside") : null; o.imgs = nav ? Array.from(nav.querySelectorAll("img")).map((i) => i.naturalWidth > 0) : []; }
        if (axis === "cat_columns" || axis === "cat_sidebar") { o.asideVisible = vis($(".wt-cat-aside")); o.tracks = tracks($(".wt-cat-layout")); o.widgets = $$(".wt-cat-widget").filter(vis).map((w) => Array.from(w.classList).find((c) => c.startsWith("wt-cat-widget--")).replace("wt-cat-widget--", "")); const sf = $(".wt-cat-widget--search form"); o.search = sf ? { role: sf.getAttribute("role"), method: (sf.getAttribute("method") || "").toLowerCase(), submit: sf.querySelectorAll("button[type=submit]").length, labelled: !!sf.querySelector("label[for=wt-cat-s]") } : null; }
        if (axis === "cat_list") { o.listTracks = tracks(list); o.firstSpan = list && list.firstElementChild ? getComputedStyle(list.firstElementChild).gridColumnEnd : null; o.imgVisible = card ? vis(card.querySelector(".wp-block-post-featured-image")) : null; o.cardsVisible = visN(".wt-cat-card"); }
        if (axis === "cat_card") { o.card = card ? { img: vis(card.querySelector(".wp-block-post-featured-image")), terms: vis(card.querySelector(".wp-block-post-terms")), title: vis(card.querySelector(".wp-block-post-title")), date: vis(card.querySelector(".wp-block-post-date")), excerpt: vis(card.querySelector(".wt-cat-card__excerpt")), author: vis(card.querySelector(".wt-cat-card__author")), badges: vis(card.querySelector(".wt-cat-card__badges")), tags: $$(".wt-cat-card__tags a").filter(vis).length, newBadges: $$(".wt-cat-card__new").filter(vis).length } : null; }
        if (axis === "cat_filter") { o.filterShown = ["tabs", "year", "tag", "sort"].filter((x) => vis($(`.wt-cat-filter--${x}`))); const f = $(`.wt-cat-filter--${v}`); o.links = f ? Array.from(f.querySelectorAll("a")).filter(vis).map((a) => ({ href: a.href, current: a.getAttribute("aria-current") })) : []; const form = f ? f.querySelector("form") : null; o.sort = form ? { method: (form.getAttribute("method") || "").toLowerCase(), action: form.getAttribute("action"), select: !!form.querySelector("select#wt-cat-orderby"), label: !!form.querySelector("label[for=wt-cat-orderby]"), submit: form.querySelectorAll("button[type=submit]").length } : null; }
        if (axis === "cat_pagination") { o.numbers = vis($(".wp-block-query-pagination-numbers")); o.next = vis($(".wp-block-query-pagination-next")); o.pagination = vis($(".wt-cat-pagination")); o.loadMore = vis($(".wt-load-more")); }
        if (axis === "cat_ranking") { o.rankShown = ["top", "bottom", "aside"].filter((x) => vis($(`.wt-cat-ranking--${x}`))); const rk = $$(".wt-cat-ranking").find(vis); o.rankTop = rk ? rk.getBoundingClientRect().top + scrollY : null; o.listTop = listTop; o.rankInAside = rk ? !!rk.closest(".wt-cat-aside") : null; o.rankItems = rk ? rk.querySelectorAll("li a").length : 0; }
        if (axis === "cat_pickup") { o.pickShown = ["top-featured", "editor-pick-box"].filter((x) => vis($(`.wt-cat-pickup--${x}`))); const pk = $(`.wt-cat-pickup--${v}`); o.pickImgs = pk ? Array.from(pk.querySelectorAll("img")).filter(vis).map((i) => i.naturalWidth > 0) : []; o.pickLinks = pk ? pk.querySelectorAll("a[href]").length : 0; o.pickAboveList = pk && listTop !== null ? pk.getBoundingClientRect().top + scrollY < listTop : null; }
        if (axis === "cat_cta") { o.ctaShown = ["lp-banner", "newsletter", "line"].filter((x) => vis($(`.wt-cat-cta--${x}`))); const c = $(`.wt-cat-cta--${v}`); o.ctaHref = c && c.querySelector("a") ? c.querySelector("a").href : null; const fld = c ? c.querySelector(".wt-cat-cta__fields") : null; o.nlForm = fld ? { forms: c.querySelectorAll("form").length, inputInForm: !!(fld.querySelector("#wt-cat-nl-email") && fld.querySelector("#wt-cat-nl-email").form), submit: fld.querySelectorAll("button:not([type]), button[type=submit], input[type=submit]").length, labelled: !!fld.querySelector("label[for=wt-cat-nl-email]") && !!fld.querySelector("#wt-cat-nl-email"), poc: fld.getAttribute("data-wt-poc-form") } : null; const g = c ? c.querySelector(".wt-i") : null; o.lineGlyph = g ? { vis: vis(g), size: Math.min(g.getBoundingClientRect().width, g.getBoundingClientRect().height), mask: (getComputedStyle(g).maskImage || getComputedStyle(g).webkitMaskImage || "none") !== "none", color: getComputedStyle(g).color, aria: c.querySelector("a") ? c.querySelector("a").getAttribute("aria-label") : null } : null; }
        return o; }, [axis, v, dev, js, VIS_SRC]);
      let pass = r.body && r.h1Visible === 1 && r.below44.length === 0 && r.deadAnchors.length === 0;
      const only = (arr, x) => JSON.stringify(arr) === JSON.stringify(x === "none" ? [] : [x]);
      if (axis === "cat_header") { pass = pass && r.countVisible === (v === "name-count") && r.descVisible === (v === "name-desc" || v === "hero") && (v !== "hero" || r.heroWhite === true); if (v === "name-count") { if (expectedCount === null) pass = false; else pass = pass && r.countNum === expectedCount; } }
      if (axis === "cat_lead") { pass = pass && only(r.leadShown, v) && (v !== "editorial" || (r.editorialH2 && r.editorialLen >= 80)); }
      if (axis === "cat_children") { pass = pass && only(r.navShown, v) && (v === "none" || r.links >= 3) && (v !== "sidebar-tree" || r.inAside === true) && (v !== "image-banners" || (r.imgs.length >= 3 && r.imgs.every(Boolean))); }
      if (axis === "cat_columns") { const two = v === "sidebar-right"; pass = pass && r.asideVisible === two && (dev !== "pc" || r.tracks === (two ? 2 : 1)) && (dev !== "sp" || r.tracks === 1) && (two ? r.widgets.length >= 2 : r.widgets.length === 0); }
      if (axis === "cat_sidebar") { const want = { standard: ["categories", "popular"], "with-cta": ["categories", "popular", "cta"], full: ["categories", "popular", "cta", "search", "archive", "new", "profile", "tags"] }[v]; pass = pass && JSON.stringify(r.widgets) === JSON.stringify(want) && (v !== "full" || (r.search && r.search.role === "search" && r.search.method === "get" && r.search.submit === 1 && r.search.labelled)); }
      if (axis === "cat_list") { const pcTracks = { grid: 3, "grid-2": 2, "featured-grid": 3, "text-list": 1, "thumb-list": 1, timeline: 1 }[v]; pass = pass && r.cardsVisible >= 6 && (dev !== "pc" || r.listTracks === pcTracks) && (v !== "text-list" ? r.imgVisible === true : r.imgVisible === false) && (v !== "featured-grid" || dev !== "pc" || /^-1$|span 3/.test(r.firstSpan || "")); }
      if (axis === "cat_card") { const c = r.card; pass = pass && !!c && c.title && c.date && (v === "minimal" ? !c.img && !c.terms && !c.excerpt && !c.author : c.img && c.terms && (dev === "sp" ? !c.excerpt : c.excerpt)) /* SP の圧縮カードは抜粋を出さない（試作 02 からの SP 規則） */ && (v === "rich" ? c.author && c.badges && c.tags >= 1 : !c.author && !c.badges); }
      if (axis === "cat_filter") { pass = pass && only(r.filterShown, v); if (v === "tabs" || v === "year" || v === "tag") pass = pass && r.links.length >= (v === "tabs" ? 4 : 2) && r.links[0].current === "page"; if (v === "sort") pass = pass && !!r.sort && r.sort.method === "get" && r.sort.select && r.sort.label && r.sort.submit === 1 && /\/category\/topic-index\/?$/.test(r.sort.action || ""); }
      if (axis === "cat_pagination") { pass = pass && (v === "numbers" ? r.numbers && r.pagination && !r.loadMore : v === "none" ? !r.pagination && !r.loadMore : v === "prev-next" ? r.next && !r.numbers && !r.loadMore : js ? r.loadMore && !r.pagination : r.pagination && !r.loadMore); }
      if (axis === "cat_ranking") { const want = v === "sidebar" ? "aside" : v; pass = pass && only(r.rankShown, want) && (v === "none" || r.rankItems >= 3) && (v !== "sidebar" || r.rankInAside === true) && (v !== "top" || r.rankTop < r.listTop) && (v !== "bottom" || r.rankTop > r.listTop); }
      if (axis === "cat_pickup") { pass = pass && only(r.pickShown, v) && (v === "none" || (r.pickImgs.length >= (v === "top-featured" ? 1 : 3) && r.pickImgs.every(Boolean) && r.pickLinks >= 1 && r.pickAboveList === true)); }
      if (axis === "cat_cta") { pass = pass && only(r.ctaShown, v); if (v === "newsletter") { // form 要素を持たない（暗黙送信なし）+ Enter を実際に押して URL が変わらないこと
          const before = p.url(); await p.focus("#wt-cat-nl-email"); await p.keyboard.type("a@b.example"); await p.keyboard.press("Enter"); await p.waitForTimeout(500); r.enterUrlUnchanged = p.url() === before;
          pass = pass && !!r.nlForm && r.nlForm.forms === 0 && !r.nlForm.inputInForm && r.nlForm.submit === 0 && r.nlForm.labelled && r.nlForm.poc === "no-submit" && r.enterUrlUnchanged; } if (v === "line") pass = pass && !!r.lineGlyph && r.lineGlyph.vis && r.lineGlyph.size >= 16 && r.lineGlyph.mask && !/rgba\(0, 0, 0, 0\)|transparent/.test(r.lineGlyph.color) && /LINE/.test(r.lineGlyph.aria || ""); }
      results.push({ dev, js, axis, v, ...r, pass });
    }
    // 組合せ: cat_card:minimal × 一覧 6 型（一覧型の表示規則が minimal の非表示に勝たないこと）
    for (const l of AX.cat_list) {
      await p.goto(BASE + CATEGORY + `?wt=cat_side:classic,cat_card:minimal,cat_list:${l}`, { waitUntil: js ? "networkidle" : "load" });
      const r = await p.evaluate(([visSrc]) => { const vis = eval(visSrc); const cards = Array.from(document.querySelectorAll(".wt-cat-list .wt-cat-card")); return { cards: cards.length, shown: cards.map((c) => [vis(c.querySelector(".wp-block-post-featured-image")), vis(c.querySelector(".wp-block-post-terms")), vis(c.querySelector(".wt-cat-card__excerpt")), vis(c.querySelector(".wp-block-post-title")), vis(c.querySelector(".wp-block-post-date"))]) }; }, [VIS_SRC]);
      results.push({ dev, js, axis: "combo", v: `minimal+${l}`, ...r, pass: r.cards >= 6 && r.shown.every(([img, terms, ex, title, date]) => !img && !terms && !ex && title && date) });
    }
    await ctx.close(); return results; };
  const sp = await read(SP, "sp", true), pc = await read(PC, "pc", true), spNoJs = await read(SP, "sp", false);
  // 実 HTTP: 絞り込みリンクと CTA の到達先（PC JS 有効の行から）。リダイレクトを許さず 200、絞り込みは同じカテゴリ面に留まる。並べ替えは先頭記事のタイトルが既定と変わる
  const http = { links: [], sort: null };
  const ctx = await browser.newContext(PC); const p = await ctx.newPage();
  const urls = new Set();
  for (const r of pc) { if (r.axis === "cat_filter" && r.links) for (const l of r.links) urls.add(l.href); if (r.axis === "cat_cta" && r.ctaHref && !r.ctaHref.includes("#")) urls.add(r.ctaHref); }
  // 到達先: リダイレクト不可で 200。カテゴリ面のリンクは実際に開いて最終 URL のパスが同じカテゴリ面で、WP の body class（category-topic-index）と main.wt-category が DOM にあること（HTML の部分文字列一致にしない）
  const openCat = async (u) => { const res = await p.goto(u, { waitUntil: "load" }); const d = await p.evaluate(() => ({ path: location.pathname, cat: document.body.classList.contains("category"), self: document.body.classList.contains("category-topic-index"), main: !!document.querySelector("main.wt-category"), first: (document.querySelector(".wt-cat-list .wp-block-post-title") || {}).textContent?.trim() || null, cards: document.querySelectorAll(".wt-cat-list .wt-cat-card").length })); return { status: res ? res.status() : null, ...d }; };
  for (const u of urls) { const res = await p.request.get(u, { maxRedirects: 0 }); let ok = res.status() === 200; let d = null; if (ok && /\/category\/topic-index\//.test(u)) { d = await openCat(u); ok = d.status === 200 && d.path.startsWith("/category/topic-index/") && d.cat && d.main && d.cards >= 1; /* 子カテゴリ（tabs）は同じ木の下のカテゴリ面 */ } http.links.push({ url: u.replace(BASE, ""), status: res.status(), dom: d, ok }); }
  const d0 = await openCat(BASE + CATEGORY), d1 = await openCat(BASE + CATEGORY + "?orderby=title&order=asc"), dY = await openCat(BASE + CATEGORY + "?year=2026");
  const stay = (d) => d.status === 200 && d.path === "/category/topic-index/" && d.self && d.main && d.cards >= 1;
  http.sort = { defaultFirst: d0.first, titleAscFirst: d1.first, yearFirst: dY.first, pass: stay(d0) && stay(d1) && stay(dY) && !!d0.first && !!d1.first && d0.first !== d1.first };
  await ctx.close();
  const all = [...sp, ...pc, ...spNoJs];
  out.categoryVariants = { expectedCount, countSource, sp, pc, spNoJs, http, pass: all.length === (47 + 6) * 3 && all.every((x) => x.pass) && http.links.length >= 6 && http.links.every((l) => l.ok) && http.sort.pass };
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
  ["headerVariants", out.headerVariants.pass], ["announceFullWidth", out.announceFullWidth.pass], ["numboxNum", out.numboxNum.pass], ["graphsMore", out.graphsMore.pass], ["relatedNoFixture", out.relatedNoFixture.pass], ["lineIcon", out.lineIcon.pass], ["snsIcons", out.snsIcons.pass],
  ["homeFace", out.homeFace.pass], ["homeHeroContrast", out.homeHeroContrast.pass], ["homeFixedOverlap", out.homeFixedOverlap.pass], ["eventFace", out.eventFace.pass], ["eventHeroContrast", out.eventHeroContrast.pass],
  ["categoryVariants", out.categoryVariants.pass], ["pageParts", out.pageParts.pass], ["sideFace", out.sideFace.pass], ["sideDefaults", out.sideDefaults.pass], ["sideOwner", out.sideOwner.pass], ["chromeOwner", out.chromeOwner.pass], ["formFace", out.formFace.pass],
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

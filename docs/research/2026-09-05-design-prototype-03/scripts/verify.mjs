#!/usr/bin/env node
// verify.mjs — 試作 03 の検証: JS 無効描画、reduced-motion、CTA コントラスト、404 ステータス、SP 44px 監査、自動コントラスト guard の計算。
// 使い方: NODE_PATH=<playwright の node_modules> node verify.mjs --base http://localhost:8086 --out ../results/verify.json
import fs from "node:fs";
import path from "node:path";
import { createRequire } from "node:module";
const require = createRequire(process.env.NODE_PATH ? path.join(process.env.NODE_PATH, "x.js") : import.meta.url); // NODE_PATH の playwright を優先（リポ内の別版と混ざらないように）
const { chromium } = require("playwright");
const args = Object.fromEntries(process.argv.slice(2).map((a, i, arr) => a.startsWith("--") ? [a.slice(2), arr[i + 1]] : null).filter(Boolean));
const BASE = args.base || "http://localhost:8086";
const OUT = path.resolve(args.out || "../results/verify.json");
const ARTICLE = "/standing-desk-compare/";
const CATEGORY = "/category/topic-index/";
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
// 6. 自動コントラスト guard（実描画から算出）: 文字要素の矩形位置で (a) スクリム擬似要素の linear-gradient を解析して実効 alpha を線形補間、
//    (b) 画像を canvas に描き文字矩形の平均輝度を測り、合成輝度 L×(1−α)（黒スクリム）と白文字の比を出す。gradient の補間は線形近似（概算）
{
  const ctx = await browser.newContext(PC); const p = await ctx.newPage();
  const measure = async (sel, pseudo, textSel) => p.evaluate(([sel, pseudo, textSel]) => {
    const el = document.querySelector(sel); if (!el) return { sel, missing: true };
    const img = el.querySelector("img"); const txt = el.querySelector(textSel);
    const er = el.getBoundingClientRect(), tr = txt.getBoundingClientRect();
    // (a) gradient の実効 alpha（to top: 0% = 下端）
    const bgi = getComputedStyle(el, pseudo).backgroundImage;
    const stops = Array.from(bgi.matchAll(/rgba?\(([^)]+)\)\s*([\d.]+)%/g)).map((m) => { const c = m[1].split(",").map(Number); return { a: c[3] ?? 1, pos: parseFloat(m[2]) / 100 }; });
    const fracFromBottom = (y) => (er.bottom - y) / er.height;
    const alphaAt = (f) => { if (!stops.length) return null; if (f <= stops[0].pos) return stops[0].a; for (let i = 1; i < stops.length; i++) if (f <= stops[i].pos) { const s0 = stops[i - 1], s1 = stops[i]; return s0.a + (s1.a - s0.a) * ((f - s0.pos) / (s1.pos - s0.pos)); } return stops[stops.length - 1].a; };
    const aTop = alphaAt(fracFromBottom(tr.top)), aBottom = alphaAt(fracFromBottom(tr.bottom));
    const alpha = aTop == null ? null : Math.min(aTop, aBottom); // 文字矩形内で最も薄い位置（最悪値）
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
    return { sel, textLum: tc, lum: el.getAttribute("data-wt-lum"), sampledL: parseFloat(el.getAttribute("data-wt-lum-value")), gradient: bgi.slice(0, 120), alphaAtText: alpha == null ? null : Math.round(alpha * 1000) / 1000, imageLAtText: Math.round((sum / n) * 1000) / 1000, imageLMaxAtText: Math.round(lmax * 1000) / 1000, textColor: ts.color, fontSize: parseFloat(ts.fontSize), fontWeight: ts.fontWeight };
  }, [sel, pseudo, textSel]);
  const finish = (x) => { if (x.missing || x.alphaAtText == null) return { ...x, note: "スクリム未検出" }; const Lt = Array.isArray(x.textLum) ? lum(x.textLum.slice(0, 3)) : 1; const Lc = x.imageLAtText * (1 - x.alphaAtText), LcMax = x.imageLMaxAtText * (1 - x.alphaAtText); const r = ratio(Lt, Lc), rWorst = ratio(Lt, LcMax); const large = x.fontSize >= 24 || (x.fontSize >= 18.67 && parseInt(x.fontWeight) >= 700); return { ...x, compositeL: Math.round(Lc * 1000) / 1000, textL: Math.round(Lt * 1000) / 1000, ratioText: Math.round(r * 100) / 100, ratioWorstPixel: Math.round(rWorst * 100) / 100, ratioWithoutScrim: Math.round(ratio(Lt, x.imageLAtText) * 100) / 100, required: large ? 3 : 4.5, pass: r >= (large ? 3 : 4.5) }; };
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
      after = { ...after, ...(await pcPage.evaluate(() => ({ items: document.querySelectorAll(".wt-cat-list > li").length, buttonText: document.querySelector(".wt-load-more")?.textContent.trim(), buttonHidden: !!document.querySelector(".wt-load-more")?.hidden, bodyStillLoadMore: document.body.classList.contains("wt-cat-pagination-load-more") }))) };
    }
    out.loadMoreJs = { before, after, added: after.items - before.items, pass: before.buttonVisible && !before.paginationVisible && !!before.nextHref && after.items > before.items && after.bodyStillLoadMore === true };
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

// 9. 結果の集計（既存 gate と段 3 gate を同じ verify.json に固定する）
out.status404.pass = Object.entries(out.status404).filter(([key]) => key.startsWith("/")).every(([, status]) => status === 404) && out.status404.noindex;
out.toc.pass = out.toc.tocH2 === out.toc.h2Count && out.toc.tocH3 === out.toc.h3Count && out.toc.scrollMarginTop !== "0px";
out.reducedMotion.pass = out.reducedMotion.revealHidden === 0 && out.reducedMotion.categoryFooter.pass;
// 段 1/2 の検査も合否を持たせる（コントラスト 11 項目、guard 12 判定、タップ監査 4 画面、見出し 1 行）
out.contrastPass = out.contrast.length > 0 && out.contrast.every((x) => !x.missing && x.pass);
out.contrastGuardPass = out.contrastGuard.length > 0 && out.contrastGuard.every((x) => x.pass === true);
for (const k of ["article", "article-announce", "404", "catalog"]) out.tap[k].pass = out.tap[k].below44.length === 0 && out.tap[k].below24.length === 0;
out.headline.pass = out.headline.lines === 1;
const checkList = [
  ["noJs", out.noJs.pass], ["reducedMotion", out.reducedMotion.pass], ["status404", out.status404.pass], ["table", out.table.pass], ["toc", out.toc.pass],
  ["contrast", out.contrastPass], ["contrastGuard", out.contrastGuardPass], ["headline", out.headline.pass],
  ["articleTapSp", out.tap.article.pass], ["articleAnnounceTapSp", out.tap["article-announce"].pass], ["notFoundTapSp", out.tap["404"].pass], ["catalogTapSp", out.tap.catalog.pass],
  ["categoryTapSp", out.tap.categorySp.pass], ["categoryTapPc", out.tap.categoryPc.pass], ["footerTapSp", out.tap.footerSp.pass], ["footerTapPc", out.tap.footerPc.pass], ["authorSnsTapSp", out.tap.authorSnsSp.pass], ["authorSnsTapPc", out.tap.authorSnsPc.pass],
  ["footerContrast", out.footerContrast.pass], ["footerNoJs", out.footerNoJs.pass], ["loadMoreNoJs", out.loadMoreNoJs.pass], ["loadMoreJs", out.loadMoreJs.pass], ["categoryPagination", out.categoryPagination.pass], ["categoryHeroContrast", out.categoryHeroContrast.pass],
];
out.summary = { pass: checkList.filter(([, pass]) => pass).length, fail: checkList.filter(([, pass]) => !pass).length, checks: Object.fromEntries(checkList.map(([name, pass]) => [name, pass])) };
out.pass = out.summary.fail === 0;
await browser.close();
fs.writeFileSync(OUT, JSON.stringify(out, null, 1));
console.log(JSON.stringify(out, null, 1));

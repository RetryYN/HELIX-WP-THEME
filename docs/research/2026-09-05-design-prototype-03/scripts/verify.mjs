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
  const p = await ctx.newPage(); await p.goto(BASE + ARTICLE + "?wt=motion:on", { waitUntil: "load" });
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
      headerVisible: vis(document.querySelector(".wt-header")), announceHidden: getComputedStyle(document.querySelector(".wt-announce") || document.body).display === "none",
    };
  });
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
    return [pick(".wp-block-button__link.has-cta-background-color", "CTA button"), pick(".wp-block-post-content p a", "body link"), pick(".wt-sub", "helper text (mute)"), pick(".wt-pr", "PR notice"), pick(".wt-toc a", "toc link"), pick(".is-style-wt-band-title > :first-child", "band title"), pick(".wt-badge--rank", "rank badge"), pick(".is-style-outline .wp-block-button__link", "outline button"), pick(".wt-product__price small", "price unit"), pick(".wt-linkcard__label", "linkcard label"), pick(".wt-rcard .wp-block-post-date", "card date")];
  });
  out.contrast = pairs.map((x) => { if (x.missing) return x; const c = parse(x.color), b = parse(x.bg); const r = ratio(lum(c.rgb), lum(b.rgb)); const large = x.fontSize >= 24 || (x.fontSize >= 18.67 && parseInt(x.fontWeight) >= 700); return { ...x, ratio: Math.round(r * 100) / 100, required: large ? 3 : 4.5, pass: r >= (large ? 3 : 4.5) }; });
  await ctx.close();
}
// 4. 404 ステータス（3 変種 + 素の URL）
{
  const ctx = await browser.newContext(SP); const p = await ctx.newPage(); out.status404 = {};
  for (const u of ["/no-such-page-standing-desk-guide/", "/no-such-page/?wt=nf:popular", "/no-such-page/?wt=nf:cta", "/no-such-page/?wt=nf:suggest"]) { const r = await p.goto(BASE + u); out.status404[u] = r.status(); }
  out.status404.robots = await p.getAttribute('meta[name="robots"]', "content");
  out.status404.has = await p.evaluate(() => ({ apology: /すみません|申し訳/.test(document.body.innerText), cause: /可能性/.test(document.body.innerText), search: !!document.querySelector(".wt-404__search input[type=search]") && !!document.querySelector(".wt-404__search button"), popular: !!document.querySelector(".wt-404__variant--popular .wt-rcard"), categories: !!document.querySelector(".wt-404__cats a"), home: !!document.querySelector('.wt-404__home a[href="/"]'), cvSlot: document.querySelectorAll(".wt-cv__item").length, suggestLinks: document.querySelectorAll(".wt-suggest a").length }));
  await ctx.close();
}
// 5. SP 44px 監査（記事・404・カタログ）: インラインの本文リンクは WCAG 2.5.8 の例外として別掲
{
  const ctx = await browser.newContext(SP); const p = await ctx.newPage(); out.tap44 = {};
  for (const [k, u] of [["article", ARTICLE], ["article-announce", ARTICLE + "?wt=header:announce,related:carousel,share:float"], ["404", "/no-such-page/"], ["catalog", "/catalog-03/"]]) {
    await p.goto(BASE + u, { waitUntil: "networkidle" }); await p.waitForTimeout(300);
    out.tap44[k] = await p.evaluate(() => {
      const els = Array.from(document.querySelectorAll("a[href], button, input, summary, [role=button]"));
      const res = { total: 0, ok: 0, inlineText: [], violations: [] };
      for (const el of els) {
        const r = el.getBoundingClientRect(); const s = getComputedStyle(el);
        if (r.width === 0 || r.height === 0 || s.visibility === "hidden" || s.display === "none") continue;
        res.total++;
        const desc = (el.tagName.toLowerCase() + (el.className && typeof el.className === "string" ? "." + el.className.split(" ").slice(0, 2).join(".") : "") + " '" + (el.getAttribute("aria-label") || el.textContent || el.value || "").trim().slice(0, 24) + "' " + Math.round(r.width) + "x" + Math.round(r.height));
        if (r.width >= 44 && r.height >= 44) { res.ok++; continue; }
        const inline = el.tagName === "A" && s.display === "inline" && el.closest("p, li, cite, figcaption, .wp-block-post-terms");
        if (inline) res.inlineText.push(desc); else res.violations.push(desc);
      }
      return res;
    });
  }
  await ctx.close();
}
// 6. 自動コントラスト guard: data-wt-lum と CSS スクリム不透明度から、文字が載る下部での合成輝度と白文字の比を算出
{
  const ctx = await browser.newContext(PC); const p = await ctx.newPage(); await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" }); await p.waitForTimeout(600);
  const scrimAlphaAtText = { dark: 0.50, mid: 0.72, light: 0.86, none: 0.75 }; // 下部 0〜25%（文字位置）の gradient 平均（CSS の値から）
  out.contrastGuard = await p.evaluate(() => ["dark", "mid", "light"].map((k) => { const el = document.querySelector("#cat-contrast-" + k); return { image: k, lum: el.getAttribute("data-wt-lum"), L: parseFloat(el.getAttribute("data-wt-lum-value")), headingPx: parseFloat(getComputedStyle(el.querySelector("h3")).fontSize), bodyPx: parseFloat(getComputedStyle(el.querySelector("p")).fontSize) }; }));
  out.contrastGuard = out.contrastGuard.map((x) => { const a = scrimAlphaAtText[x.lum || "none"]; const Lc = x.L * (1 - a); const r = ratio(1, Lc); const rNoScrim = ratio(1, x.L); return { ...x, scrimAlpha: a, compositeL: Math.round(Lc * 1000) / 1000, ratioWhiteText: Math.round(r * 100) / 100, ratioWithoutScrim: Math.round(rNoScrim * 100) / 100, passBody: r >= 4.5, passHeading: r >= 3 }; });
  // 記事の hero 変種（写真アイキャッチ）
  await p.goto(BASE + ARTICLE + "?wt=eyecatch:hero", { waitUntil: "networkidle" }); await p.waitForTimeout(600);
  out.contrastGuard.push(await p.evaluate(() => { const el = document.querySelector(".wt-posthead__img"); return { image: "article eyecatch (hero)", lum: el.getAttribute("data-wt-lum"), L: parseFloat(el.getAttribute("data-wt-lum-value")) }; }));
  await ctx.close();
}
// 7. 見出し 1 行収まり（SP 390、20 字）と本文列幅・目次しきい値
{
  const ctx = await browser.newContext(SP); const p = await ctx.newPage(); await p.goto(BASE + "/catalog-03/", { waitUntil: "networkidle" });
  out.headline = await p.evaluate(() => { const h = document.querySelector("#cat-h2-plain h2"); const s = getComputedStyle(h); const r = h.getBoundingClientRect(); const lines = Math.round(r.height / (parseFloat(s.lineHeight))); return { text: h.textContent, chars: h.textContent.length, fontSize: parseFloat(s.fontSize), lineHeight: parseFloat(s.lineHeight), boxHeight: r.height, lines, contentWidth: h.parentElement.getBoundingClientRect().width }; });
  await p.goto(BASE + ARTICLE, { waitUntil: "networkidle" });
  out.toc = await p.evaluate(() => ({ h2Count: document.querySelectorAll(".wp-block-post-content h2").length, h3Count: document.querySelectorAll(".wp-block-post-content h3").length, tocH2: document.querySelectorAll(".wt-toc__list > li").length, tocH3: document.querySelectorAll(".wt-toc__list ol li").length, scrollMarginTop: getComputedStyle(document.querySelector("h2[id]")).scrollMarginTop, spClosedByJs: !document.querySelector(".wt-toc details").open }));
  await ctx.close();
}
await browser.close();
fs.writeFileSync(OUT, JSON.stringify(out, null, 1));
console.log(JSON.stringify(out, null, 1));

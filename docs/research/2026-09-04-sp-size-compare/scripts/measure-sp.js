// SP サイズ感計測: 390x844 (DSF2) で投稿ページとトップページを描画し、getComputedStyle で px を取る。PC 1280x800 も同項目で取る。
// 使い方: NODE_PATH=<playwright を持つ node_modules> node measure-sp.js <series> <base-url> <post-path> <out-json> <shot-dir> <webp-dir>
// series は伏せ字ラベル（theme-a / theme-b / ours-light / ours-compare 等）のみ。実 slug は渡さない。
// base-url は http://localhost:8086 のようなローカル WP でも、実運用サイトの https://... でもよい（GET のみ、書き込みなし）。
// セレクタはテーマ固有クラスに依存しない: 本文コンテナ = 20 文字超の p を最も多く含む最深の祖先、見出しは h1 / 本文内の h2・h3、
// ボタン様要素 = 背景色付き・padding 8px 以上の a / button。
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const [series, base, postPath, outJson, shotDir, webpDir] = process.argv.slice(2);
if (!webpDir) { console.error('usage: measure-sp.js <series> <base> <post-path> <out-json> <shot-dir> <webp-dir>'); process.exit(2); }

const VIEWPORT = { width: 390, height: 844 };
const PC_VIEWPORT = { width: 1280, height: 800 };

const MEASURE = () => {
  const px = (v) => (v == null || v === '' ? null : Math.round(parseFloat(v) * 100) / 100);
  const cs = (el) => (el ? getComputedStyle(el) : null);
  const rect = (el) => (el ? el.getBoundingClientRect() : null);
  const q = (sel, root = document) => root.querySelector(sel);
  const visible = (el) => { const r = rect(el); const s = cs(el); return r.height > 0 && r.width > 0 && s.visibility !== 'hidden' && s.display !== 'none'; };
  const FV = innerHeight;
  const typo = (el) => {
    const s = cs(el); if (!s) return null;
    const r = rect(el);
    return { fontSize: px(s.fontSize), lineHeight: px(s.lineHeight === 'normal' ? parseFloat(s.fontSize) * 1.2 : s.lineHeight),
      marginTop: px(s.marginTop), marginBottom: px(s.marginBottom), paddingTop: px(s.paddingTop), paddingBottom: px(s.paddingBottom),
      paddingLeft: px(s.paddingLeft), paddingRight: px(s.paddingRight), fontWeight: s.fontWeight,
      width: px(r.width), height: px(r.height), top: px(r.top + scrollY) };
  };
  const mode = (arr) => { const m = new Map(); arr.forEach((v) => m.set(v, (m.get(v) || 0) + 1)); let best = null, bc = 0; m.forEach((c, v) => { if (c > bc) { bc = c; best = v; } }); return best; };
  const main = q('main') || q('.wp-site-blocks') || document.body;
  // 本文コンテナ: 20 文字超の可視 p を最も多く含む最深の祖先（70% 以上を含むもの）
  const longPs = Array.from(document.querySelectorAll('p')).filter((p) => p.textContent.trim().length > 20 && visible(p));
  let content = document.body;
  if (longPs.length) {
    const count = new Map();
    longPs.forEach((p) => { let a = p.parentElement; while (a && a !== document.body) { count.set(a, (count.get(a) || 0) + 1); a = a.parentElement; } });
    let bestDepth = -1;
    count.forEach((c, el) => { if (c >= longPs.length * 0.7) { let d = 0, a = el; while (a) { d++; a = a.parentElement; } if (d > bestDepth) { bestDepth = d; content = el; } } });
  }
  const contentPs = longPs.filter((p) => content.contains(p));
  const firstP = contentPs[0] || null;
  const h1 = Array.from(document.querySelectorAll('h1')).find(visible) || null;
  const h2 = Array.from(content.querySelectorAll('h2')).find(visible) || Array.from(document.querySelectorAll('h2')).find(visible) || null;
  const h3 = Array.from(content.querySelectorAll('h3')).find(visible) || Array.from(document.querySelectorAll('h3')).find(visible) || null;
  const isButtonLike = (el) => { const s = cs(el); if (!visible(el)) return false; const bg = s.backgroundColor; const hasBg = bg && !/rgba\(0, 0, 0, 0\)|transparent/.test(bg); return hasBg && parseFloat(s.paddingTop) >= 8 && parseFloat(s.paddingLeft) >= 8 && el.textContent.trim().length > 0 && rect(el).width < innerWidth && rect(el).width >= 80 && parseFloat(s.fontSize) >= 12; };
  const btn = Array.from(content.querySelectorAll('a, button')).find(isButtonLike) || Array.from(main.querySelectorAll('a, button')).find(isButtonLike) || Array.from(document.querySelectorAll('a, button')).find(isButtonLike) || null;
  // ヘッダー: 上端付近にある可視 header（最初のもの）
  const header = Array.from(document.querySelectorAll('header')).find((h) => visible(h) && rect(h).top + scrollY < 200) || null;
  // サイト名: ヘッダー内でルートへ張るリンク（テキストか画像）
  const siteTitle = header ? Array.from(header.querySelectorAll('a')).find((a) => { try { const u = new URL(a.href); return u.pathname === '/' && u.origin === location.origin; } catch (e) { return false; } }) || null : null;
  const siteTitleTextEl = siteTitle && siteTitle.textContent.trim() ? siteTitle : null;
  const siteTitleImg = siteTitle ? siteTitle.querySelector('img, svg') : null;
  // ファーストビュー内の本文文字数: 上端 FV 以内に完全に入る text node の文字数
  const walker = document.createTreeWalker(content, NodeFilter.SHOW_TEXT);
  let visibleChars = 0; let n;
  while ((n = walker.nextNode())) {
    const t = n.textContent.replace(/\s+/g, ''); if (!t) continue;
    if (n.parentElement && !visible(n.parentElement)) continue;
    const rg = document.createRange(); rg.selectNodeContents(n);
    const rr = rg.getBoundingClientRect();
    if (rr.height > 0 && rr.bottom + scrollY <= FV && rr.top + scrollY >= 0) visibleChars += t.length;
  }
  // ヒーロー: main の最初の可視ブロック
  const hero = Array.from(main.children).find((el) => rect(el).height > 0) || null;
  // 下部固定要素（cookie バナー等）
  const fixedBottom = Array.from(document.querySelectorAll('body *')).filter((el) => { const s = cs(el); if (s.position !== 'fixed' || !visible(el) || parseFloat(s.opacity) === 0 || s.pointerEvents === 'none') return false; const r = rect(el); return r.bottom >= innerHeight - 2 && r.height >= 40 && r.height < innerHeight * 0.9 && r.width >= innerWidth * 0.8; })
    .map((el) => px(rect(el).height));
  const contentRect = rect(content);
  const contentStyle = cs(content);
  const pSizes = contentPs.map((p) => px(cs(p).fontSize));
  const h2s = Array.from(content.querySelectorAll('h2')).filter(visible);
  return {
    url: location.href, viewport: { w: innerWidth, h: innerHeight }, docHeight: document.documentElement.scrollHeight,
    htmlFontSize: px(cs(document.documentElement).fontSize), bodyFontSize: px(cs(document.body).fontSize),
    bodyLineHeight: px(cs(document.body).lineHeight),
    contentContainer: content === document.body ? 'body' : (content.className || content.tagName),
    contentWidth: px(contentRect.width), contentLeft: px(contentRect.left), contentPaddingLeft: px(contentStyle.paddingLeft), contentPaddingRight: px(contentStyle.paddingRight),
    paragraphCount: contentPs.length, paragraphFontSizeMode: pSizes.length ? mode(pSizes) : null,
    paragraph: firstP ? Object.assign(typo(firstP), { text: firstP.textContent.trim().slice(0, 30), textWidth: px(rect(firstP).width) }) : null,
    h1: h1 ? Object.assign(typo(h1), { text: h1.textContent.trim().slice(0, 40), chars: h1.textContent.trim().length }) : null,
    h2: h2 ? Object.assign(typo(h2), { text: h2.textContent.trim().slice(0, 40) }) : null,
    h2FontSizeMode: h2s.length ? mode(h2s.map((h) => px(cs(h).fontSize))) : null,
    h3: h3 ? Object.assign(typo(h3), { text: h3.textContent.trim().slice(0, 40) }) : null,
    button: btn ? Object.assign(typo(btn), { text: btn.textContent.trim().slice(0, 30), selector: btn.className }) : null,
    headerHeight: header ? px(rect(header).height) : null,
    siteTitleFontSize: siteTitleTextEl ? px(cs(siteTitleTextEl).fontSize) : null,
    siteTitleImageHeight: siteTitleImg ? px(rect(siteTitleImg).height) : null,
    heroHeight: hero ? px(rect(hero).height) : null, heroTag: hero ? (hero.className || hero.tagName) : null,
    h1Top: h1 ? px(rect(h1).top + scrollY) : null, firstParagraphTop: firstP ? px(rect(firstP).top + scrollY) : null,
    visibleBodyCharsInFirstView: contentPs.length ? visibleChars : null,
    fixedBottomHeights: fixedBottom,
  };
};

const UA_MOBILE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

(async () => {
  const browser = await chromium.launch(process.env.PW_EXECUTABLE ? { executablePath: process.env.PW_EXECUTABLE } : {});
  const ctx = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 2, isMobile: true, hasTouch: true, locale: 'ja-JP', userAgent: UA_MOBILE });
  const page = await ctx.newPage();
  await page.goto(base + postPath, { waitUntil: 'networkidle', timeout: 90000 }).catch(() => {});
  await page.waitForTimeout(1500);
  const post = await page.evaluate(MEASURE);
  await page.screenshot({ path: path.join(shotDir, `${series}-article-full.png`), fullPage: true });
  await page.screenshot({ path: path.join(shotDir, `${series}-article-fv.png`) });
  await page.goto(base + '/', { waitUntil: 'networkidle', timeout: 90000 }).catch(() => {});
  await page.waitForTimeout(1500);
  const top = await page.evaluate(MEASURE);
  await page.screenshot({ path: path.join(shotDir, `${series}-top-fv.png`) });
  await page.screenshot({ path: path.join(shotDir, `${series}-top-full.png`), fullPage: true });
  await ctx.close();
  // 代表画像: 幅 390 等倍 PNG（webp 化は run-series.sh で ffmpeg -q 80）
  const ctx1 = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 1, isMobile: true, hasTouch: true, locale: 'ja-JP', userAgent: UA_MOBILE });
  const p1 = await ctx1.newPage();
  await p1.goto(base + postPath, { waitUntil: 'networkidle', timeout: 90000 }).catch(() => {});
  await p1.waitForTimeout(1500);
  await p1.screenshot({ path: path.join(shotDir, `${series}-article-fv-390.png`) });
  await ctx1.close();
  // PC 1280x800
  const ctxPc = await browser.newContext({ viewport: PC_VIEWPORT, deviceScaleFactor: 1, locale: 'ja-JP' });
  const pc = await ctxPc.newPage();
  await pc.goto(base + postPath, { waitUntil: 'networkidle', timeout: 90000 }).catch(() => {});
  await pc.waitForTimeout(1500);
  const pcPost = await pc.evaluate(MEASURE);
  await pc.screenshot({ path: path.join(shotDir, `${series}-article-pc1280-fv.png`) });
  await pc.goto(base + '/', { waitUntil: 'networkidle', timeout: 90000 }).catch(() => {});
  await pc.waitForTimeout(1500);
  const pcTop = await pc.evaluate(MEASURE);
  await pc.screenshot({ path: path.join(shotDir, `${series}-top-pc1280-fv.png`) });
  await ctxPc.close();
  await browser.close();
  const out = { series, measuredAt: new Date().toISOString(), post, top, pc: { post: pcPost, top: pcTop } };
  fs.writeFileSync(outJson, JSON.stringify(out, null, 2));
  console.log(JSON.stringify({ series, p: post.paragraph && post.paragraph.fontSize, h1: post.h1 && post.h1.fontSize, h2: post.h2 && post.h2.fontSize, chars: post.visibleBodyCharsInFirstView, header: post.headerHeight }));
})().catch((e) => { console.error(e); process.exit(1); });

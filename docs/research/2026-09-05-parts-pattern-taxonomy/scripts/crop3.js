// full-page webp -> region crops (header/hero/mid/footer) via chromium
const { chromium } = require('playwright'); const fs = require('fs'); const path = require('path');
const [,, IN, OUT] = process.argv; fs.mkdirSync(OUT, { recursive: true });
(async () => { const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 800, height: 800 } });
  const files = fs.readdirSync(IN).filter(f => (f.endsWith('.webp')||f.endsWith('.jpg')));
  for (const f of files) {
    const buf = fs.readFileSync(path.join(IN, f)).toString('base64');
    await p.setContent(`<img id=i src="data:image/${f.endsWith('.webp')?'webp':'jpeg'};base64,${buf}" style="display:block">`);
    const { w, h } = await p.evaluate(() => { const i = document.getElementById('i'); return { w: i.naturalWidth, h: i.naturalHeight }; });
    const isSp = f.includes('-sp'); const dpr = isSp ? 2 : 1; const cssW = Math.round(w / dpr);
    const dw = Math.min(isSp ? w : cssW, 800); await p.setViewportSize({ width: dw, height: 800 });
    await p.evaluate((s) => { document.getElementById('i').style.width = s + 'px'; }, dw);
    const hc = Math.round(h * dw / w);
    const base = f.replace(/\.(webp|jpg)$/, '');
    const regions = isSp ? { hero: [0, 1000], mid: [Math.floor(hc / 2) - 700, 1400], foot: [Math.max(0, hc - 1400), 1400] } : { hero: [0, 900], mid: [Math.floor(hc / 2) - 450, 900], foot: [Math.max(0, hc - 900), 900] };
    if ((f.includes('article')||f.includes('-art')||f.includes('-cat'))) { regions.hero = [0, isSp ? 1800 : 1200]; regions.mid2 = [Math.floor(hc / 4), 1400]; }
    for (const [k, [y, hh]] of Object.entries(regions)) {
      const y0 = Math.max(0, Math.min(y, hc - 10)); const hh2 = Math.min(Math.min(hh, 1000), hc - y0);
      await p.evaluate((y) => window.scrollTo(0, y), 0);
      await p.screenshot({ path: `${OUT}/${base}--${k}.jpg`, type: 'jpeg', quality: 70, clip: { x: 0, y: y0, width: dw, height: hh2 }, fullPage: true });
    }
  }
  await b.close(); console.log('cropped', files.length); })();

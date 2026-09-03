// 各パターン最大 N 件の SP 初回画面を 234px 幅に縮小し base64 webp を JSON へ
import fs from "node:fs"; import path from "node:path"; import { createRequire } from "node:module";
const require = createRequire(path.join(process.env.HOME, "dev/poc-wp/package.json"));
const { chromium } = require("playwright");
const [,, resultsDir, shotsDir, outFile, perPattern="6"] = process.argv;
const rs = fs.readdirSync(resultsDir).map(f=>JSON.parse(fs.readFileSync(path.join(resultsDir,f)))).filter(r=>!r.error && r.sp?.screenshot);
const byP = {};
for (const r of rs.sort((a,b)=>a.id.localeCompare(b.id))) { if (r.id.endsWith("-article")) continue; (byP[r.pattern] ||= []); if (byP[r.pattern].length < +perPattern) byP[r.pattern].push(r); }
const b = await chromium.launch(); const p = await b.newPage();
const out = {};
for (const [pat, list] of Object.entries(byP)) {
  out[pat] = [];
  for (const r of list) {
    const file = path.join(shotsDir, path.basename(r.sp.screenshot));
    if (!fs.existsSync(file)) continue;
    const data = "data:image/webp;base64," + fs.readFileSync(file).toString("base64");
    const thumb = await p.evaluate(async (src) => {
      const img = new Image(); img.src = src; await img.decode();
      const c = document.createElement("canvas"); c.width = 234; c.height = 506;
      c.getContext("2d").drawImage(img, 0, 0, 390, 844, 0, 0, 234, 506);
      return c.toDataURL("image/webp", 0.8);
    }, data);
    out[pat].push({ id: r.id, country: r.country, genre: r.genre, thumb });
  }
  console.log(pat, out[pat].length);
}
await b.close(); fs.writeFileSync(outFile, JSON.stringify(out));

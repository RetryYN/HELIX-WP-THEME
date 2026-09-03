// 全 result の SP/PC 初回画面を PNG で切り出す（視覚コーディング用）
import fs from "node:fs"; import path from "node:path"; import { createRequire } from "node:module";
const require = createRequire(path.join(process.env.HOME, "dev/poc-wp/package.json"));
const { chromium } = require("playwright");
const [,, resultsDir, shotsDir, outDir] = process.argv; fs.mkdirSync(outDir,{recursive:true});
const rs = fs.readdirSync(resultsDir).map(f=>JSON.parse(fs.readFileSync(path.join(resultsDir,f)))).filter(r=>!r.error && !r.id.endsWith("-article"));
const b = await chromium.launch(); const p = await b.newPage();
for (const r of rs) for (const [dev,w,h,sw] of [["sp",390,844,390],["pc",1280,800,960]]) {
  const shot = r[dev]?.screenshot; if (!shot) continue;
  const file = path.join(shotsDir, path.basename(shot)); if (!fs.existsSync(file)) continue;
  const data = "data:image/webp;base64," + fs.readFileSync(file).toString("base64");
  const png = await p.evaluate(async ([src,w,h,sw]) => { const img=new Image(); img.src=src; await img.decode();
    const dpr=img.naturalWidth/w; const c=document.createElement("canvas"); const s=sw/w; c.width=sw; c.height=Math.round(h*s);
    c.getContext("2d").drawImage(img,0,0,w*dpr,h*dpr,0,0,c.width,c.height); return c.toDataURL("image/jpeg",0.8); }, [data,w,h,sw]);
  fs.writeFileSync(path.join(outDir, `${r.id}-${dev}.jpg`), Buffer.from(png.split(",")[1],"base64"));
}
await b.close(); console.log("done", rs.length);

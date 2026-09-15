import { chromium } from 'playwright';
import { fileURLToPath } from 'node:url';
const browser=await chromium.launch();
try{
for(const [device,width,height] of [['pc',1200,240],['sp',640,320]]){
 const page=await browser.newPage({viewport:{width,height},deviceScaleFactor:1});
 await page.setContent(`<html lang="ja"><meta charset="utf-8"><style>*{box-sizing:border-box}body{margin:0;background:#173d37;color:#fff;font-family:system-ui,sans-serif;padding:32px 48px;display:flex;align-items:center;justify-content:space-between;gap:28px;height:100vh}.tag{font-size:15px;letter-spacing:.12em;color:#d4efcc;font-weight:700}h1{font-size:${device==='pc'?38:32}px;margin:12px 0;line-height:1.3}p{margin:0;font-size:19px;line-height:1.6}.mark{flex:0 0 auto;border-radius:50%;border:2px solid #bde2ae;width:130px;height:130px;display:grid;place-items:center;color:#d4efcc;font-size:62px}@media(max-width:700px){body{padding:30px}.mark{display:none}}</style><div><div class="tag">HELIX DEMO · GUIDE</div><h1>迷う前に、選ぶポイントを。</h1><p>サービス選びのチェックリストを見る →</p></div><div class="mark" aria-hidden="true">✓</div></html>`);
 await page.screenshot({path:fileURLToPath(new URL(`creative-${device}.png`,import.meta.url))});await page.close();
}
}finally{await browser.close();}

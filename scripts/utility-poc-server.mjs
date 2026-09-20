import http from 'node:http';
import fs from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {executeUtility} from './utility-poc-provider.mjs';
const root=path.resolve(import.meta.dirname,'..');
export async function startUtilityServer(port=0){
 const server=http.createServer(async(req,res)=>{
  res.setHeader('Cache-Control','no-store');res.setHeader('X-Content-Type-Options','nosniff');
  const pathname=new URL(req.url,'http://localhost').pathname;
  if(pathname==='/utility/execute'&&req.method==='POST'){
   if(req.headers.origin&&req.headers.origin!==`http://${req.headers.host}`){res.writeHead(403);res.end();return;}
   let body='';try{for await(const chunk of req){body+=chunk;if(Buffer.byteLength(body)>8192)throw Error('size');}const {kind,input}=JSON.parse(body);if(!input||typeof input!=='object')throw Error('input');const output=executeUtility(kind,input);res.writeHead(output.errors?422:200,{'Content-Type':'application/json'});res.end(JSON.stringify(output));}catch{res.writeHead(400,{'Content-Type':'application/json'});res.end(JSON.stringify({errors:{form:'入力を確認してください。'}}));}return;
  }
  try{if(req.method!=='GET'||!pathname.startsWith('/docs/research/'))throw Error('route');let file=path.resolve(root,'.'+decodeURIComponent(pathname));if(!file.startsWith(root+'/docs/research/'))throw Error('path');if((await fs.stat(file)).isDirectory())file=path.join(file,'index.html');const body=await fs.readFile(file);const mime={'.html':'text/html; charset=utf-8','.css':'text/css','.mjs':'text/javascript','.json':'application/json','.jpg':'image/jpeg','.png':'image/png'}[path.extname(file)]||'application/octet-stream';res.writeHead(200,{'Content-Type':mime});res.end(body);}catch{res.writeHead(404);res.end('Not found');}
 });
 await new Promise(resolve=>server.listen(port,'127.0.0.1',resolve));return {base:`http://127.0.0.1:${server.address().port}`,close:()=>new Promise(resolve=>server.close(resolve))};
}
if(process.argv[1]&&fileURLToPath(import.meta.url)===path.resolve(process.argv[1])){startUtilityServer(Number(process.env.UTILITY_PORT||8130)).then(service=>console.log(`Utility PoC: ${service.base}/docs/research/2026-09-20-utility-poc/calculator.html`));}

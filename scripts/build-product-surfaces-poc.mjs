import fs from 'node:fs';
import {catalog,surfaces} from '../docs/research/2026-09-20-product-surfaces-poc/products.mjs';
import {renderPage} from './product-surfaces-renderer.mjs';
const root='docs/research/2026-09-20-product-surfaces-poc';
for(const p of catalog.products)fs.writeFileSync(`${root}/${p.id}.svg`,`<svg xmlns="http://www.w3.org/2000/svg" width="400" height="260" viewBox="0 0 400 260"><rect width="400" height="260" rx="16" fill="#eef1e7"/><ellipse cx="202" cy="223" rx="95" ry="10" fill="#d8dfd0"/><path d="M202 207V114l55-42" fill="none" stroke="${p.color}" stroke-width="12" stroke-linecap="round"/><path d="M218 77l49-32 48 60-74 20z" fill="${p.color}"/><path d="M250 126l46-15 28 78-101 0z" fill="#f7eac5" opacity=".8"/><rect x="155" y="203" width="95" height="15" rx="7" fill="${p.color}"/></svg>\n`);
for(const f of surfaces)fs.writeFileSync(`${root}/${f.id}.html`,renderPage(f.id));

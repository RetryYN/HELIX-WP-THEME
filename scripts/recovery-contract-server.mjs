import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const contentTypes = { '.html': 'text/html; charset=utf-8', '.mjs': 'text/javascript; charset=utf-8', '.json': 'application/json; charset=utf-8' };
export const startRecoveryServer = (port = 0) => new Promise(resolve => { const server = http.createServer((request, response) => { const relative = decodeURIComponent(new URL(request.url, 'http://localhost').pathname).replace(/^\/+/, ''); const file = path.resolve(root, relative || 'docs/research/2026-09-20-recovery-contract-poc/index.html'); if (!file.startsWith(`${root}${path.sep}`) || !fs.existsSync(file) || !fs.statSync(file).isFile()) { response.writeHead(404); response.end('Not found'); return; } response.writeHead(200, { 'content-type': contentTypes[path.extname(file)] || 'application/octet-stream' }); fs.createReadStream(file).pipe(response); }); server.listen(port, '127.0.0.1', () => { const address = server.address(); resolve({ base: `http://127.0.0.1:${address.port}`, close: () => new Promise(done => server.close(done)) }); }); });

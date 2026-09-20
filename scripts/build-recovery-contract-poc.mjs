import fs from 'node:fs';
import path from 'node:path';
import { render } from './recovery-contract-renderer.mjs';
const root = 'docs/research/2026-09-20-recovery-contract-poc';
fs.mkdirSync(root, { recursive: true });
fs.writeFileSync(path.join(root, 'index.html'), render());

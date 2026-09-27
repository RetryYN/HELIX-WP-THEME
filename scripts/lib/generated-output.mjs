import fs from 'node:fs';
import path from 'node:path';

export function writeGenerated(file, content, { check = process.env.CATALOG_GENERATED_CHECK === '1' } = {}) {
  if (check) {
    let current;
    try { current = fs.readFileSync(file, 'utf8'); }
    catch { throw new Error(`Generated artifact is missing: ${file}`); }
    if (current !== content) throw new Error(`Generated artifact is out of date: ${file}`);
    return;
  }
  fs.mkdirSync(path.dirname(file), { recursive: true });
  fs.writeFileSync(file, content);
}

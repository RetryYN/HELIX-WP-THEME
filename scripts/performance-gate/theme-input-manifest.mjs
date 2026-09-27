import { createHash } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';

export function collectThemeInputDigests(root, themeDir) {
  const themeRoot = path.join(root, themeDir);
  const digests = {};
  const visit = directory => {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
      if (entry.isSymbolicLink()) continue;
      const absolutePath = path.join(directory, entry.name);
      if (entry.isDirectory()) visit(absolutePath);
      else if (entry.isFile()) {
        const relativePath = path.relative(root, absolutePath).split(path.sep).join('/');
        digests[relativePath] = createHash('sha256').update(fs.readFileSync(absolutePath)).digest('hex');
      }
    }
  };
  visit(themeRoot);
  return Object.fromEntries(Object.entries(digests).sort(([left], [right]) => left.localeCompare(right)));
}

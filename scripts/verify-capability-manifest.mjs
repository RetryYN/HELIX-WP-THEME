import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..');
const themeDir = path.join(root, 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt');
const manifestPath = path.join(themeDir, 'config/capability-manifest.json');
const outputPath = path.join(root, 'docs/research/2026-09-09-capability-manifest/verify.json');
const readJson = file => JSON.parse(fs.readFileSync(file, 'utf8'));
const sorted = values => [...new Set(values)].sort((a, b) => String(a).localeCompare(String(b), 'en'));
const stems = (dir, ext) => sorted(fs.readdirSync(dir).filter(name => name.endsWith(ext)).map(name => name.slice(0, -ext.length)));
const at = (value, dotted) => dotted.split('.').reduce((current, key) => current?.[key], value);

function deriveCapabilities() {
  const theme = readJson(path.join(themeDir, 'theme.json'));
  const contentChrome = readJson(path.join(themeDir, 'config/content-chrome.json'));
  const functions = fs.readFileSync(path.join(themeDir, 'functions.php'), 'utf8');
  const patterns = fs.readdirSync(path.join(themeDir, 'patterns')).filter(name => name.endsWith('.php')).map(name => {
    const source = fs.readFileSync(path.join(themeDir, 'patterns', name), 'utf8');
    const slug = source.match(/^ \* Slug:\s*(\S+)\s*$/m)?.[1];
    if (!slug) throw new Error(`pattern header has no Slug: ${name}`);
    return slug;
  });
  const blockTypes = [...functions.matchAll(/register_block_type\(\s*['"]([^'"]+)/g)].map(match => match[1]);
  const blockStyles = [...functions.matchAll(/array\(\s*['"](core\/[^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\s*,/g)].map(match => `${match[1]}:${match[2]}`);
  const scalePaths = ['settings.color.palette', 'settings.color.gradients', 'settings.typography.fontFamilies', 'settings.typography.fontSizes', 'settings.spacing.spacingSizes'];
  return {
    patterns: sorted(patterns),
    template_parts: stems(path.join(themeDir, 'parts'), '.html'),
    templates: stems(path.join(themeDir, 'templates'), '.html'),
    style_variations: stems(path.join(themeDir, 'styles'), '.json'),
    slots: sorted(Object.keys(contentChrome)),
    custom_blocks: sorted(blockTypes),
    block_styles: sorted(blockStyles),
    value_scales: Object.fromEntries(scalePaths.map(key => [key, sorted((at(theme, key) ?? []).map(item => String(item.slug)))])),
  };
}

const observed = deriveCapabilities();
if (process.argv.includes('--write-manifest')) {
  fs.writeFileSync(manifestPath, `${JSON.stringify({ schema: 'helix-wt-capability-manifest.v1', boundary: { ai_decision_logic: { owner: 'helix', theme_allowed: false, content_fixture_plugin_allowed: false } }, capabilities: observed }, null, 2)}\n`);
}
const manifest = readJson(manifestPath);
const keys = Object.keys(observed);
const differences = declared => keys.filter(key => JSON.stringify(declared?.[key]) !== JSON.stringify(observed[key]));
const rows = [];
const check = (name, pass, detail) => rows.push({ name, pass: Boolean(pass), detail });
const drift = differences(manifest.capabilities);
check('manifest:declared-equals-current-helix-wt-source', drift.length === 0, { drift });
check('manifest:all-capability-kinds', keys.length === 8, { kinds: keys });
for (const key of keys) {
  const altered = structuredClone(manifest.capabilities);
  if (Array.isArray(altered[key])) altered[key] = altered[key].slice(1);
  else altered[key][Object.keys(altered[key])[0]] = altered[key][Object.keys(altered[key])[0]].slice(1);
  check(`negative:undeclared-${key}-fails`, differences(altered).includes(key), { expected_drift: key });
}
const theme = readJson(path.join(themeDir, 'theme.json'));
check('manifest:template-parts-match-theme-json', theme.templateParts.every(item => observed.template_parts.includes(item.name)), { declared: theme.templateParts.map(item => item.name), files: observed.template_parts });
check('manifest:custom-templates-exist', theme.customTemplates.every(item => observed.templates.includes(item.name)), { custom_templates: theme.customTemplates.map(item => item.name) });
const result = {
  schema: 'wt-capability-manifest-verification.v2',
  requirements: ['WT-TR-CORE-01', 'WT-TR-CORE-02'],
  completed: true,
  source: path.relative(root, manifestPath).split(path.sep).join('/'),
  counts: Object.fromEntries(Object.entries(observed).map(([key, value]) => [key, Array.isArray(value) ? value.length : Object.values(value).reduce((sum, items) => sum + items.length, 0)])),
  rows,
  failed: rows.filter(row => !row.pass).length,
};
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify(result, null, 2));
if (result.failed) process.exitCode = 1;

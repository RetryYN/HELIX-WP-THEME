import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..');
const themeDir = path.join(root, 'themes/agent-neo-theme');
const manifestPath = path.join(themeDir, 'config/theme-manifest.json');
const outputPath = path.join(root, 'docs/research/2026-09-09-capability-manifest/verify.json');

const readJson = file => JSON.parse(fs.readFileSync(file, 'utf8'));
const sorted = values => [...values].sort((a, b) => String(a) < String(b) ? -1 : String(a) > String(b) ? 1 : 0);
const listStems = (dir, extension) => sorted(fs.readdirSync(dir)
  .filter(name => name.endsWith(extension))
  .map(name => name.slice(0, -extension.length)));
const at = (value, dottedPath) => dottedPath.split('.').reduce((current, key) => current[key], value);

function stripPhpComments(source) {
  return source.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\/\/.*$/gm, '');
}

function deriveCapabilities() {
  const theme = readJson(path.join(themeDir, 'theme.json'));
  const sections = readJson(path.join(themeDir, 'config/section-registry.json'));
  const patternDir = path.join(themeDir, 'patterns');
  const patterns = fs.readdirSync(patternDir).filter(name => name.endsWith('.php')).map(name => {
    const source = fs.readFileSync(path.join(patternDir, name), 'utf8');
    const match = source.match(/^ \* Slug:\s*(\S+)\s*$/m);
    if (!match) throw new Error(`pattern header has no Slug: patterns/${name}`);
    return match[1];
  });
  const hooks = [];
  for (const relative of walkPhp(path.join(themeDir, 'inc'))) {
    const source = stripPhpComments(fs.readFileSync(path.join(themeDir, relative), 'utf8'));
    for (const match of source.matchAll(/add_(action|filter)\s*\(\s*['"]([^'"]+)/g)) {
      hooks.push({ type: match[1], hook: match[2], source: relative });
    }
  }
  const scalePaths = [
    'settings.color.palette',
    'settings.color.gradients',
    'settings.typography.fontFamilies',
    'settings.typography.fontSizes',
    'settings.shadow.presets',
    'settings.spacing.spacingSizes',
  ];
  return {
    patterns: sorted(patterns),
    template_parts: listStems(path.join(themeDir, 'parts'), '.html'),
    templates: listStems(path.join(themeDir, 'templates'), '.html'),
    style_variations: listStems(path.join(themeDir, 'styles'), '.json'),
    slots: sorted(sections.sections.map(section => section.section_id)),
    value_scales: Object.fromEntries(scalePaths.map(key => [key, sorted(at(theme, key).map(item => String(item.slug)))])),
    hooks: hooks.sort((a, b) => `${a.type}:${a.hook}:${a.source}`.localeCompare(`${b.type}:${b.hook}:${b.source}`)),
  };
}

function walkPhp(directory) {
  const found = [];
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const absolute = path.join(directory, entry.name);
    if (entry.isDirectory()) found.push(...walkPhp(absolute));
    else if (entry.name.endsWith('.php')) found.push(path.relative(themeDir, absolute).split(path.sep).join('/'));
  }
  return sorted(found);
}

function differences(declared, observed) {
  const keys = ['patterns', 'template_parts', 'templates', 'style_variations', 'slots', 'value_scales', 'hooks'];
  return keys.filter(key => JSON.stringify(declared?.[key]) !== JSON.stringify(observed[key]));
}

const manifest = readJson(manifestPath);
const observed = deriveCapabilities();
const rows = [];
const check = (name, pass, detail) => rows.push({ name, pass: Boolean(pass), detail });
const drift = differences(manifest.capabilities, observed);
check('manifest:declared-equals-source', drift.length === 0, { drift });
check('manifest:pattern-count', observed.patterns.length === 71, { count: observed.patterns.length });
check('manifest:all-capability-kinds', Object.keys(observed).length === 7, { kinds: Object.keys(observed) });

for (const key of ['patterns', 'template_parts', 'templates', 'style_variations', 'slots', 'hooks']) {
  const altered = structuredClone(manifest.capabilities);
  altered[key] = altered[key].slice(1);
  check(`negative:undeclared-${key}-fails`, differences(altered, observed).includes(key), { expected_drift: key });
}
const alteredScale = structuredClone(manifest.capabilities);
alteredScale.value_scales['settings.color.palette'] = alteredScale.value_scales['settings.color.palette'].slice(1);
check('negative:undeclared-value-scale-fails', differences(alteredScale, observed).includes('value_scales'), { expected_drift: 'value_scales' });

const theme = readJson(path.join(themeDir, 'theme.json'));
check('manifest:template-parts-match-theme-json',
  JSON.stringify(observed.template_parts) === JSON.stringify(sorted(theme.templateParts.map(item => item.name))),
  { manifest: observed.template_parts, theme_json: sorted(theme.templateParts.map(item => item.name)) });
check('manifest:custom-templates-exist',
  theme.customTemplates.every(item => observed.templates.includes(item.name)),
  { custom_templates: theme.customTemplates.map(item => item.name) });

const result = {
  schema: 'wt-capability-manifest-verification.v1',
  requirements: ['WT-TR-CORE-01', 'WT-TR-CORE-02'],
  completed: true,
  source: 'themes/agent-neo-theme/config/theme-manifest.json',
  counts: Object.fromEntries(Object.entries(observed).map(([key, value]) => [key, Array.isArray(value) ? value.length : Object.values(value).reduce((sum, items) => sum + items.length, 0)])),
  rows,
  failed: rows.filter(row => !row.pass).length,
};
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify(result, null, 2));
if (result.failed) process.exitCode = 1;

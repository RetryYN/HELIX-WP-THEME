#!/usr/bin/env node
/**
 * figma-structure-to-patterns.mjs
 *
 * Figma REST API（無料プランの個人アクセストークンで可）の files エンドポイントから
 * フレーム階層を取得し、命名規約（docs/design/figma-intake.md）に従うフレームだけを
 * 本テーマの「パターン骨格」（ブロック markup の雛形）へ変換する。
 *
 * 層 2/3 の不変条件: 値は書かない。サイズ・余白・色は Figma 側の Variable 名 →
 * プリセットスラッグ（var:preset|…）として参照だけを出力する。
 *
 * 使い方:
 *   FIGMA_TOKEN=... node tools/design/figma-structure-to-patterns.mjs --file <FILE_KEY> [--out <dir>]
 *   node tools/design/figma-structure-to-patterns.mjs --json <saved-file.json> [--out <dir>]   # 保存済み JSON から（オフライン）
 *
 * 出力: <out>/<slug>.php（パターン雛形。手で文言を入れる前提）と <out>/_report.json
 * トークンは環境変数のみ。リポジトリ・ログに書かない。
 */
import fs from 'node:fs';
import path from 'node:path';

const args = process.argv.slice(2);
const opt = (k, d = null) => { const i = args.indexOf(k); return i >= 0 ? args[i + 1] : d; };
const fileKey = opt('--file');
const jsonPath = opt('--json');
const outDir = opt('--out', 'tools/design/out');

// 命名規約: フレーム名 "pat/<slug>" がパターン、"sec/<name>" がセクション、"col/<n>" がカラム、
// "h2|h3|p|btn|img" が要素。Variable 参照はレイヤー名の末尾 "@size:x-large" "@space:40" "@color:accent" で示す。
const RE = {
	pattern: /^pat\/([a-z0-9-]+)/,
	section: /^sec\/([a-z0-9-]+)/,
	columns: /^col\/(\d+)/,
	element: /^(h1|h2|h3|h4|p|btn|img|list|quote)\b/,
	ref: /@(size|space|color):([a-z0-9-]+)/g,
};

async function loadDocument() {
	if (jsonPath) return JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
	if (!fileKey) throw new Error('--file <FILE_KEY> か --json <path> を指定してください');
	const token = process.env.FIGMA_TOKEN;
	if (!token) throw new Error('FIGMA_TOKEN 環境変数が必要です（個人アクセストークン。リポジトリに書かない）');
	const res = await fetch(`https://api.figma.com/v1/files/${fileKey}?depth=6`, { headers: { 'X-Figma-Token': token } });
	if (!res.ok) throw new Error(`Figma API ${res.status}: ${await res.text()}`);
	return res.json();
}

function refs(name) {
	const out = {};
	for (const m of name.matchAll(RE.ref)) out[m[1]] = m[2];
	return out;
}
const preset = (kind, slug) => ({ size: `var:preset|font-size|${slug}`, space: `var:preset|spacing|${slug}`, color: slug })[kind];

function elementMarkup(node) {
	const m = node.name.match(RE.element); if (!m) return '';
	const r = refs(node.name); const tag = m[1];
	const text = (node.characters || `【${tag}】`).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
	// save 側の直列化（class / style）をブロック属性と一致させる。fontSize はプリセット属性、色はスラッグ。
	const attrs = {};
	if (r.size) attrs.fontSize = r.size;
	if (r.color) attrs.textColor = r.color;
	const cls = [];
	if (r.color) cls.push(`has-${r.color}-color`, 'has-text-color');
	if (r.size) cls.push(`has-${r.size}-font-size`);
	const a = Object.keys(attrs).length ? ' ' + JSON.stringify(attrs) : '';
	const c = (base) => `class="${[base, ...cls].filter(Boolean).join(' ')}"`;
	switch (tag) {
		case 'h1': case 'h2': case 'h3': case 'h4': {
			const lv = tag.slice(1);
			const ha = JSON.stringify({ level: Number(lv), ...attrs });
			return `<!-- wp:heading ${ha} --><${tag} ${c('wp-block-heading')}><?php esc_html_e( '${text}', 'agent-neo' ); ?></${tag}><!-- /wp:heading -->`;
		}
		case 'p': return `<!-- wp:paragraph${a} --><p${cls.length ? ' ' + c('') .replace('class=" ', 'class="') : ''}><?php esc_html_e( '${text}', 'agent-neo' ); ?></p><!-- /wp:paragraph -->`;
		case 'btn': {
			const bcls = ['wp-block-button__link', ...(r.color ? [`has-${r.color}-color`, 'has-text-color'] : []), ...(r.size ? [`has-${r.size}-font-size`, 'has-custom-font-size'] : []), 'wp-element-button'].join(' ');
			return `<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button${a} --><div class="wp-block-button"><a class="${bcls}" href="#"><?php esc_html_e( '${text}', 'agent-neo' ); ?></a></div><!-- /wp:button --></div><!-- /wp:buttons -->`;
		}
		case 'img': return `<!-- wp:image --><figure class="wp-block-image"><img src="" alt=""/></figure><!-- /wp:image -->`;
		case 'list': return `<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li><?php esc_html_e( '${text}', 'agent-neo' ); ?></li><!-- /wp:list-item --></ul><!-- /wp:list -->`;
		case 'quote': return `<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p><?php esc_html_e( '${text}', 'agent-neo' ); ?></p><!-- /wp:paragraph --></blockquote><!-- /wp:quote -->`;
	}
	return '';
}

function walk(node, depth = 0) {
	const children = node.children || [];
	if (RE.columns.test(node.name)) {
		const cols = children.map(c => `<!-- wp:column --><div class="wp-block-column">${walk(c, depth + 1)}</div><!-- /wp:column -->`).join('\n');
		return `<!-- wp:columns --><div class="wp-block-columns">${cols}</div><!-- /wp:columns -->`;
	}
	if (RE.section.test(node.name)) {
		const r = refs(node.name); const name = node.name.match(RE.section)[1];
		const attrs = { className: `an-section an-section--${name}`, layout: { type: 'constrained' } };
		if (r.space) attrs.style = { spacing: { padding: { top: preset('space', r.space), bottom: preset('space', r.space) } } };
		if (r.color) attrs.backgroundColor = r.color;
		const cls = ['wp-block-group', `an-section an-section--${name}`, ...(r.color ? [`has-${r.color}-background-color`, 'has-background'] : [])].join(' ');
		const st = r.space ? ` style="padding-top:var(--wp--preset--spacing--${r.space});padding-bottom:var(--wp--preset--spacing--${r.space})"` : '';
		return `<!-- wp:group ${JSON.stringify(attrs)} --><div class="${cls}"${st}>\n${children.map(c => walk(c, depth + 1)).filter(Boolean).join('\n')}\n</div><!-- /wp:group -->`;
	}
	const el = elementMarkup(node); if (el) return el;
	return children.map(c => walk(c, depth + 1)).filter(Boolean).join('\n');
}

function collectPatterns(node, acc = []) {
	if (RE.pattern.test(node.name)) acc.push(node);
	else for (const c of node.children || []) collectPatterns(c, acc);
	return acc;
}

const doc = await loadDocument();
const patterns = collectPatterns(doc.document);
fs.mkdirSync(outDir, { recursive: true });
const report = [];
for (const p of patterns) {
	const slug = p.name.match(RE.pattern)[1];
	const body = walk(p);
	const raw = (body.match(/"[0-9.]+(px|rem|em)"/g) || []).length;
	const php = `<?php\n/**\n * Title: ${slug}\n * Slug: agent-neo/${slug}\n * Categories: agent-neo\n * Description: Figma フレーム "${p.name}" から生成した骨格。文言・画像は要編集。\n * Block Types: core/group\n * Post Types: page, wp_template\n * Inserter: true\n *\n * @package AgentNeo\n */\n\nif ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n?>\n\n${body}\n`;
	fs.writeFileSync(path.join(outDir, `${slug}.php`), php);
	report.push({ slug, frame: p.name, sections: (body.match(/<!-- wp:group \{[^\n]*an-section an-section--/g) || []).length, rawValues: raw });
}
fs.writeFileSync(path.join(outDir, '_report.json'), JSON.stringify({ file: fileKey || jsonPath, patterns: report }, null, 2));
console.log(`patterns: ${report.length} → ${outDir}`);
for (const r of report) console.log(`  ${r.slug}  sections=${r.sections}  rawValues=${r.rawValues}${r.rawValues ? '  ← G-T2 違反あり' : ''}`);

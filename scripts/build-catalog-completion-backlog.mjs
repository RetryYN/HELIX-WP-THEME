import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const readJson = file => JSON.parse(fs.readFileSync(path.join(root, file), 'utf8'));
const outputDir = path.join(root, 'docs/research/2026-09-08-selection-catalog');

const ir = readJson('docs/requirements/l3/requirements-ir.json');
const audit = readJson('docs/research/2026-09-08-selection-catalog/acceptance-audit.json');
const catalog = readJson('docs/research/2026-09-08-selection-catalog/catalog-data.json');
const ledger = fs.readFileSync(path.join(root, 'docs/requirements/discovery/page-type-ledger.md'), 'utf8');

const priorityRank = { P0: 0, P1: 1, P2: 2 };
const stateRank = { stale: 0, missing: 1, partial: 2, verified_in_poc: 3 };
const candidatesByRequirement = new Map();
for (const entry of catalog.entries) {
  for (const id of entry.requirementIds || []) {
    const list = candidatesByRequirement.get(id) || [];
    list.push(entry.id);
    candidatesByRequirement.set(id, list);
  }
}

const rows = ir.requirements.map(requirement => {
  const acceptance = audit.rows.filter(row => row.requirement_id === requirement.id);
  const counts = Object.fromEntries(['missing', 'partial', 'verified_in_poc', 'stale'].map(status => [status, acceptance.filter(row => row.status === status).length]));
  const candidates = candidatesByRequirement.get(requirement.id) || [];
  const state = counts.stale ? 'stale' : counts.missing ? 'missing' : counts.partial ? 'partial' : 'verified_in_poc';
  const nextAction = state === 'stale'
    ? '証跡を再実行し、実体とdigestを再照合する'
    : state === 'missing' && candidates.length
      ? '既存候補を受入条件へ対応付け、代表fixtureと負例を検証する'
      : state === 'missing'
        ? '調査結果から代表fixtureを作り、カタログ候補と検証器を追加する'
        : state === 'partial'
          ? 'remainingを一件ずつ閉じ、未検証範囲を証跡へ追加する'
          : '回帰監視を維持する';
  return {
    requirement_id: requirement.id,
    priority: requirement.priority,
    state,
    acceptance: { total: acceptance.length, ...counts },
    candidate_count: candidates.length,
    candidates,
    statement: requirement.statement,
    next_action: nextAction
  };
}).sort((a, b) =>
  stateRank[a.state] - stateRank[b.state]
  || priorityRank[a.priority] - priorityRank[b.priority]
  || a.candidate_count - b.candidate_count
  || a.requirement_id.localeCompare(b.requirement_id)
);

const pageGaps = [];
for (const line of ledger.split('\n')) {
  if (!line.startsWith('|') || line.includes('---')) continue;
  const cells = line.split('|').slice(1, -1).map(value => value.trim());
  if (cells.length !== 7 || !['未観察', '未検証'].includes(cells[4])) continue;
  pageGaps.push({ type: cells[0].replaceAll('**', ''), source: cells[2], disposition: cells[3], evidence_state: cells[4], evidence: cells[5], order: cells[6] });
}

const counts = Object.fromEntries(['missing', 'partial', 'verified_in_poc', 'stale'].map(status => [status, audit.rows.filter(row => row.status === status).length]));
const report = {
  schema: 'wt-catalog-completion-backlog.v1',
  requirement_count: ir.requirements.length,
  acceptance_count: audit.rows.length,
  catalog_candidate_count: catalog.entries.length,
  screenshot_count: catalog.screenshotCount,
  acceptance_counts: counts,
  page_gap_count: pageGaps.length,
  page_gaps: pageGaps,
  requirements: rows
};
fs.writeFileSync(path.join(outputDir, 'completion-backlog.json'), JSON.stringify(report, null, 2) + '\n');

const openRows = rows.filter(row => row.state !== 'verified_in_poc');
const md = [
  '# カタログ再現 completion backlog',
  '',
  'この一覧は要求、受入証跡、カタログ候補、ページ種別台帳を同じ時点で再集計する。行数や進捗を手書きしない。',
  '',
  `- 要求: ${report.requirement_count}`,
  `- 受入条件: ${report.acceptance_count}（missing ${counts.missing} / partial ${counts.partial} / verified_in_poc ${counts.verified_in_poc} / stale ${counts.stale}）`,
  `- カタログ候補: ${report.catalog_candidate_count}`,
  `- スクリーンショット: ${report.screenshot_count}`,
  `- ページ種別の未観察・未検証: ${pageGaps.length}`,
  '',
  '## 次に閉じる要求',
  '',
  '| 要求 | 優先度 | 状態 | AC | 候補 | 次の操作 |',
  '| --- | --- | --- | ---: | ---: | --- |',
  ...openRows.map(row => `| ${row.requirement_id} | ${row.priority} | ${row.state} | ${row.acceptance.total} | ${row.candidate_count} | ${row.next_action} |`),
  '',
  '## ページ種別の調査残',
  '',
  '| 種別 | 状態 | 出所 | 着手順 |',
  '| --- | --- | --- | --- |',
  ...pageGaps.map(row => `| ${row.type} | ${row.evidence_state} | ${row.source} | ${row.order} |`),
  ''
].join('\n');
fs.writeFileSync(path.join(outputDir, 'completion-backlog.md'), md);
console.log(JSON.stringify({ requirements: report.requirement_count, acceptance: report.acceptance_count, open: openRows.length, page_gaps: pageGaps.length }));

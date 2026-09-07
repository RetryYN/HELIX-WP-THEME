const $ = id => document.getElementById(id);
const el = (tag, text, className) => {
  const node = document.createElement(tag);
  if (text !== undefined) node.textContent = text;
  if (className) node.className = className;
  return node;
};
const button = (text, action) => {
  const node = el('button', text);
  node.type = 'button'; node.addEventListener('click', action); return node;
};
const labels = { unreviewed: '未選択', adopt: '採用候補', hold: '保留', reject: '除外' };
const faceLabels = { site: '会社・規約', article: '記事', learning: '学習・ヘルプ', paid: '有料記事', interview: 'インタビュー', blp: 'BLP', category: 'カテゴリ', footer: 'フッター', lp: 'LP', home: 'ホーム', event: 'イベント', page: '固定ページ', form: 'フォーム', '404': '404' };
const storageKey = 'helix-selection-memos.v1';
let noticeTimer;
function notify(message) {
  $('notice').textContent = message; $('notice').hidden = false;
  clearTimeout(noticeTimer); noticeTimer = setTimeout(() => { $('notice').hidden = true; }, 6000);
}

async function start() {
  const response = await fetch('catalog-data.json');
  if (!response.ok) throw Error('データを取得できませんでした。');
  const data = await response.json();
  const byId = new Map(data.entries.map(e => [e.id, e]));
  let memos = {};
  function validate(value) {
    if (value?.schema !== storageKey || !value.memos || Array.isArray(value.memos) || typeof value.memos !== 'object') throw Error('選択メモの形式が違います。');
    const clean = {};
    for (const [id, memo] of Object.entries(value.memos)) {
      if (!byId.has(id) || !memo || !Object.hasOwn(labels, memo.status) || typeof memo.note !== 'string' || memo.note.length > 4000) throw Error('未登録の候補、状態、または長すぎるメモがあります。');
      clean[id] = { status: memo.status, note: memo.note };
    }
    return clean;
  }
  try {
    const saved = localStorage.getItem(storageKey);
    if (saved) memos = validate(JSON.parse(saved));
  } catch { notify('保存済みメモを読めませんでした。元データは書出し元ファイルから再読込できます。'); }
  function save() {
    try { localStorage.setItem(storageKey, JSON.stringify({ schema: storageKey, memos })); }
    catch { notify('ブラウザに保存できません。メモを書出して保管してください。'); }
  }
  const memoFor = id => memos[id] || { status: 'unreviewed', note: '' };
  let face = 'common', device = 'pc', limit = 36, linkedIds = null;
  const comparison = new Set();
  $('total').textContent = data.entries.length;
  $('req-total').textContent = data.requirementCount;
  const collections = [['all', 'すべて'], ['common', '共通設定・部品'], ...['home', 'article', 'paid', 'interview', 'blp', 'learning', 'site', 'category', 'lp', 'event', 'page', 'form', 'footer', '404'].map(k => [k, faceLabels[k]])];
  for (const [key, label] of collections) {
    const b = button(label, () => { face = key; linkedIds = null; limit = 36; render(); });
    b.dataset.face = key; $('faces').append(b);
  }
  for (const purpose of new Set(data.entries.map(e => e.purpose))) {
    const option = el('option', purpose); option.value = purpose; $('purpose').append(option);
  }
  function imageFor(entry, className) {
    const wrapper = el('div', undefined, `${className} ${device === 'sp' ? 'sp' : ''}`);
    if (entry.images[device]) {
      const img = el('img'); img.src = entry.images[device]; img.alt = `${entry.label} / ${entry.variant} / ${device.toUpperCase()}`;
      img.loading = 'lazy'; img.decoding = 'async';
      img.addEventListener('error', () => { wrapper.replaceChildren(el('p', '画像を読み込めませんでした。', 'missing-shot')); });
      wrapper.append(img);
    } else wrapper.append(el('p', `${device.toUpperCase()}の撮影記録はありません`, 'missing-shot'));
    return wrapper;
  }
  function compareToggle(entry) {
    const label = el('label', undefined, 'compare-pick');
    const input = el('input'); input.type = 'checkbox'; input.checked = comparison.has(entry.id);
    input.setAttribute('aria-label', `${entry.label} ${entry.variant}を比較`);
    input.addEventListener('change', () => {
      if (input.checked && comparison.size >= 3) { input.checked = false; notify('比較は3候補までです。先に選択を外してください。'); return; }
      if (input.checked) comparison.add(entry.id); else comparison.delete(entry.id);
      updateCompare();
    });
    label.append(input, ' 比較'); return label;
  }
  function updateCompare() {
    $('compare-bar').hidden = !comparison.size;
    $('compare-count').textContent = `${comparison.size}候補を選択中`;
  }
  function render() {
    for (const b of $('faces').children) b.setAttribute('aria-current', String(b.dataset.face === face && !linkedIds));
    const query = $('search').value.trim().toLocaleLowerCase();
    const selected = $('decision').value;
    const entries = data.entries.filter(e => (!linkedIds || linkedIds.has(e.id))
      && (face === 'all' || face === 'common' ? face === 'all' || e.group === '共通設定・部品' : e.face === face)
      && (!$('purpose').value || e.purpose === $('purpose').value)
      && (!selected || memoFor(e.id).status === selected)
      && `${e.label} ${e.part} ${e.variant} ${e.requirementIds.join(' ')}`.toLocaleLowerCase().includes(query))
      .sort((a, b) => Number(Boolean(b.images[device])) - Number(Boolean(a.images[device])));
    $('collection-title').textContent = linkedIds ? '要求に関連する候補' : collections.find(([key]) => key === face)[1];
    $('count').textContent = `${entries.length}候補 / ${device.toUpperCase()}`;
    $('empty').hidden = entries.length > 0; $('more').hidden = entries.length <= limit;
    $('gallery').replaceChildren();
    for (const entry of entries.slice(0, limit)) {
      const tile = el('article', undefined, 'tile');
      const open = button('', () => detail(entry)); open.className = 'tile-open';
      open.setAttribute('aria-label', `${entry.label} ${entry.variant}の詳細`);
      open.append(imageFor(entry, 'preview'));
      const top = el('div', undefined, 'tile-top'); top.append(el('h3', entry.label), el('span', faceLabels[entry.face]));
      open.append(top, el('p', entry.variant, 'variant'));
      const bottom = el('div', undefined, 'tile-footer');
      bottom.append(el('span', entry.purpose, 'purpose-tag'), compareToggle(entry));
      tile.append(open, bottom, el('span', labels[memoFor(entry.id).status], 'decision-badge')); $('gallery').append(tile);
    }
    updateCompare();
  }
  function editor(entry) {
    const panel = el('div', undefined, 'detail-copy');
    panel.append(el('h2', entry.label), el('p', entry.variant, 'variant'), el('p', entry.description), el('p', entry.purpose));
    const choices = el('div', undefined, 'choices'); choices.setAttribute('role', 'group'); choices.setAttribute('aria-label', '選択メモの状態');
    for (const [status, label] of Object.entries(labels)) {
      const b = button(label, () => {
        memos[entry.id] = { ...memoFor(entry.id), status }; save();
        for (const other of choices.children) other.setAttribute('aria-pressed', String(other === b));
        render();
      });
      b.setAttribute('aria-pressed', String(memoFor(entry.id).status === status)); choices.append(b);
    }
    const label = el('label', '選ぶ理由・確認したいこと', 'note-label');
    const note = el('textarea'); note.maxLength = 4000; note.rows = 4; note.value = memoFor(entry.id).note;
    note.addEventListener('input', () => { memos[entry.id] = { ...memoFor(entry.id), note: note.value }; save(); }); label.append(note);
    panel.append(choices, label);
    if (entry.demoRoute && ['127.0.0.1', 'localhost'].includes(location.hostname)) {
      const live = el('a', 'ローカルの実機で操作する ↗', 'open-image');
      live.href = `http://${location.hostname}:8098${entry.demoRoute}`; live.target = '_blank'; live.rel = 'noopener'; panel.append(live);
    }
    if (entry.images[device]) {
      const link = el('a', '画像を原寸で開く ↗', 'open-image'); link.href = entry.images[device]; link.target = '_blank'; link.rel = 'noopener'; panel.append(link);
    }
    panel.append(el('p', data.evidenceNote), el('p', entry.requirementIds.join(' · ') || '要求との関連付けは未整理です。', 'requirement-tags'));
    return panel;
  }
  function detail(entry) {
    const layout = el('div', undefined, 'detail-layout'); layout.append(imageFor(entry, 'large-preview'), editor(entry));
    $('detail-content').replaceChildren(layout); $('detail').showModal();
  }
  for (const dialog of document.querySelectorAll('dialog')) dialog.querySelector('.close').addEventListener('click', () => dialog.close());
  $('clear-compare').addEventListener('click', () => { comparison.clear(); render(); });
  $('open-compare').addEventListener('click', () => {
    const grid = el('div', undefined, 'compare-grid');
    for (const id of comparison) { const entry = byId.get(id); const column = el('section'); column.append(imageFor(entry, 'large-preview'), editor(entry)); grid.append(column); }
    $('compare-content').replaceChildren(grid); $('compare').showModal();
  });
  for (const name of ['search', 'purpose', 'decision']) $(name).addEventListener(name === 'search' ? 'input' : 'change', () => { limit = 36; render(); });
  for (const b of document.querySelectorAll('[data-device]')) b.addEventListener('click', () => {
    device = b.dataset.device;
    for (const other of document.querySelectorAll('[data-device]')) other.setAttribute('aria-pressed', String(other === b));
    render();
  });
  $('more').addEventListener('click', () => { limit += 36; render(); });
  const tabs = [$('tab-gallery'), $('tab-requirements')];
  function switchTab(index) {
    tabs.forEach((tab, i) => { tab.setAttribute('aria-selected', String(i === index)); tab.tabIndex = i === index ? 0 : -1; $(tab.getAttribute('aria-controls')).hidden = i !== index; });
  }
  tabs.forEach((tab, i) => {
    tab.addEventListener('click', () => switchTab(i));
    tab.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault(); const next = event.key === 'Home' ? 0 : event.key === 'End' ? 1 : 1 - i;
      switchTab(next); tabs[next].focus();
    });
  });
  function requirements() {
    const query = $('req-search').value.trim().toLocaleLowerCase(); $('requirements').replaceChildren();
    const state = $('evidence-state').value;
    const exact = data.requirements.find(r => r.id.toLocaleLowerCase() === query);
    const exactCase = data.requirements.flatMap(r => r.acceptance).find(ac => ac.id.toLocaleLowerCase() === query);
    let shownRequirements = 0, shownCases = 0;
    for (const req of data.requirements) {
      if (exact && req !== exact) continue;
      const requirementMatches = `${req.id} ${req.statement}`.toLocaleLowerCase().includes(query);
      const cases = req.acceptance.filter(ac => (!state || ac.status === state) && (exactCase ? ac.id === exactCase.id : requirementMatches || `${ac.id} ${ac.oracle} ${ac.scope} ${(ac.remaining || []).join(' ')}`.toLocaleLowerCase().includes(query)));
      if (!cases.length) continue;
      shownRequirements++; shownCases += cases.length;
      const verified = req.acceptance.filter(ac => ac.status === 'verified_in_poc').length;
      const row = el('details', undefined, 'req-row');
      const summary = el('summary'); summary.append(el('strong', req.id), el('span', `PoC確認 ${verified}/${req.acceptance.length}条件`, 'status-label'), el('p', req.statement));
      const body = el('div'); body.append(el('p', req.next));
      if (req.evidence) { const proof = el('a', '全受入条件の証拠対応を見る'); proof.href = req.evidence; body.append(proof); }
      for (const ac of cases) {
        const caseRow = el('details', undefined, 'acceptance-row');
        caseRow.dataset.evidenceState = ac.status;
        const states = { missing: '証拠の対応付けなし', partial: '部分確認', verified_in_poc: 'PoC確認済み', stale: '再検証が必要' };
        caseRow.append(el('summary', `${ac.id} · ${states[ac.status] || '未検証'}`), el('p', ac.oracle));
        if (ac.scope) caseRow.append(el('p', `確認した範囲: ${ac.scope}`));
        for (const remaining of ac.remaining || []) caseRow.append(el('p', `残り: ${remaining}`));
        for (const proof of ac.evidence || []) {
          const link = el('a', `検査結果（${proof.row_names.length}行）`);
          link.href = '../../../' + proof.path; caseRow.append(link);
        }
        body.append(caseRow);
      }
      if (req.pending?.length) body.append(el('p', `未決事項: ${typeof req.pending === 'string' ? req.pending : JSON.stringify(req.pending)}`));
      if (req.relatedEntryIds.length) body.append(button(`関連する${req.relatedEntryIds.length}候補を見る`, () => {
        linkedIds = new Set(req.relatedEntryIds); face = 'all'; limit = 36;
        $('search').value = ''; $('purpose').value = ''; $('decision').value = '';
        switchTab(0); render(); $('tab-gallery').focus();
      }));
      row.append(summary, body); $('requirements').append(row);
      if (exactCase) { row.open = true; body.querySelector('.acceptance-row').open = true; }
    }
    $('requirements-count').textContent = `${shownRequirements}要求 / ${shownCases}受入条件（全${data.requirementCount}要求）`;
    $('requirements-empty').hidden = shownCases !== 0;
  }
  $('req-search').addEventListener('input', requirements);
  $('evidence-state').addEventListener('change', requirements);
  $('reset-requirements').addEventListener('click', () => { $('req-search').value = ''; $('evidence-state').value = ''; requirements(); $('req-search').focus(); });
  $('export').addEventListener('click', () => {
    const blob = new Blob([JSON.stringify({ schema: storageKey, memos }, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob); const a = el('a'); a.href = url; a.download = 'helix-selection-memos.json';
    a.click(); setTimeout(() => URL.revokeObjectURL(url), 1000);
  });
  $('import').addEventListener('click', () => $('file').click());
  $('file').addEventListener('change', async () => {
    try {
      const file = $('file').files[0]; if (!file) return;
      if (file.size > 1024 * 1024) throw Error('メモは1MB以下にしてください。');
      const imported = validate(JSON.parse(await file.text()));
      memos = { ...memos, ...imported }; save(); render(); notify(`${Object.keys(imported).length}候補のメモを読み込みました。同じ候補のメモは読込内容で更新しました。`);
    } catch (error) { notify(`読込できません: ${error.message}`); }
    finally { $('file').value = ''; }
  });
  switchTab(0); requirements(); render();
}
start().catch(error => {
  $('gallery').replaceChildren(el('p', `${error.message} リポジトリをHTTPサーバーで開き、再読込してください。`));
  notify('カタログを開始できませんでした。');
});

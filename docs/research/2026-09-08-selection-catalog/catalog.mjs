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
const faceLabels = { search: 'サイト内検索', inheritance: '共通設定の継承', site: '会社・規約', zone: '配置slot', article: '記事', learning: '学習・ヘルプ', paid: '有料記事', interview: 'インタビュー', blp: 'BLP', category: 'カテゴリ', footer: 'フッター', lp: 'LP', home: 'ホーム', event: 'イベント', page: '固定ページ', form: 'フォーム', '404': '404' };
const storageKey = 'helix-selection-memos.v1';
const workspaceKey = 'helix-selection-workspace.v1';
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
  let activeTab = 0, detailId = null, imageMode = 'overview';
  const focusWorkspace = () => $(activeTab === 1 ? 'req-search' : 'search').focus();
  function saveWorkspace() {
    try { localStorage.setItem(workspaceKey, JSON.stringify({ schema: workspaceKey, face, device, limit, linkedIds: linkedIds ? [...linkedIds] : null, comparison: [...comparison], activeTab, imageMode, search: $('search').value, purpose: $('purpose').value, decision: $('decision').value, reqSearch: $('req-search').value, evidenceState: $('evidence-state').value })); }
    catch { notify('絞り込みと比較候補を保存できません。選択メモは書出して保管してください。'); }
  }
  $('total').textContent = data.entries.length;
  $('req-total').textContent = data.requirementCount;
  const collections = [['all', 'すべて'], ['common', '共通設定・部品'], ...['inheritance', 'home', 'article', 'paid', 'interview', 'blp', 'learning', 'site', 'search', 'zone', 'category', 'lp', 'event', 'page', 'form', 'footer', '404'].map(k => [k, faceLabels[k]])];
  for (const [key, label] of collections) {
    const b = button(label, () => { face = key; linkedIds = null; limit = 36; switchTab(0); render(); });
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
  function inspectImage(entry) {
    const view = imageFor(entry, 'large-preview');
    view.classList.add('image-viewer'); view.dataset.mode = imageMode;
    view.dataset.component = String(entry.group === '共通設定・部品');
    view.tabIndex = 0; view.setAttribute('role', 'region');
    view.setAttribute('aria-label', `${entry.label} ${entry.variant}の画像。拡大時は矢印キーで移動`);
    return view;
  }
  function imageControls() {
    const controls = el('div', undefined, 'image-controls');
    controls.setAttribute('role', 'group'); controls.setAttribute('aria-label', '画像の表示倍率');
    for (const [mode, label] of [['overview', '全体を見る'], ['width', '幅に合わせる'], ['native', '原寸で読む']]) {
      const b = button(label, () => {
        imageMode = mode;
        for (const view of document.querySelectorAll('.image-viewer')) { view.dataset.mode = mode; view.scrollTop = 0; view.scrollLeft = 0; }
        for (const control of document.querySelectorAll('[data-image-mode]')) control.setAttribute('aria-pressed', String(control.dataset.imageMode === mode));
        saveWorkspace();
      });
      b.dataset.imageMode = mode; b.setAttribute('aria-pressed', String(mode === imageMode)); controls.append(b);
    }
    controls.append(el('p', '幅合わせ・原寸では画像内をスクロールできます。画像へTabで移動すると矢印キーも使えます。'));
    return controls;
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
    $('compare-picks').replaceChildren();
    for (const id of comparison) {
      const entry = byId.get(id);
      const chip = button(`${entry.label} / ${entry.variant} ×`, () => {
        comparison.delete(id); render();
        const next = $('compare-picks').querySelector('button');
        if (next) next.focus(); else focusWorkspace();
      });
      chip.setAttribute('aria-label', `${entry.label} ${entry.variant}を比較から外す`);
      $('compare-picks').append(chip);
    }
    saveWorkspace();
  }
  function render() {
    for (const b of $('faces').children) b.setAttribute('aria-current', String(b.dataset.face === face && !linkedIds));
    const query = $('search').value.trim().toLocaleLowerCase();
    const selected = $('decision').value;
    const entries = data.entries.filter(e => (!linkedIds || linkedIds.has(e.id))
      && (face === 'all' || face === 'common' ? face === 'all' || e.group === '共通設定・部品' : e.face === face)
      && (!$('purpose').value || e.purpose === $('purpose').value)
      && (!selected || memoFor(e.id).status === selected)
      && `${e.label} ${e.part} ${e.variant} ${e.requirementIds.join(' ')} ${memoFor(e.id).note}`.toLocaleLowerCase().includes(query))
      .sort((a, b) => Number(Boolean(b.images[device])) - Number(Boolean(a.images[device])));
    $('collection-title').textContent = linkedIds ? '要求に関連する候補' : collections.find(([key]) => key === face)[1];
    $('count').textContent = `${entries.length}候補 / ${device.toUpperCase()}`;
    $('empty').hidden = entries.length > 0; $('more').hidden = entries.length <= limit;
    $('gallery').dataset.collection = face;
    $('gallery').replaceChildren();
    for (const entry of entries.slice(0, limit)) {
      const tile = el('article', undefined, 'tile');
      tile.dataset.decision = memoFor(entry.id).status;
      const open = button('', () => detail(entry)); open.className = 'tile-open'; open.dataset.entryId = entry.id;
      open.setAttribute('aria-label', `${entry.label} ${entry.variant}の詳細`);
      const preview = imageFor(entry, 'preview');
      if (entry.group === '共通設定・部品') { tile.classList.add('component-tile'); preview.classList.add('component-preview'); }
      open.append(preview);
      const top = el('div', undefined, 'tile-top'); top.append(el('h3', entry.label), el('span', faceLabels[entry.face]));
      open.append(top, el('p', entry.variant, 'variant'));
      const bottom = el('div', undefined, 'tile-footer');
      bottom.append(el('span', entry.purpose, 'purpose-tag'), compareToggle(entry));
      const facts = el('div', undefined, 'tile-facts');
      facts.append(el('span', ['pc', 'sp'].filter(d => entry.images[d]).map(d => d.toUpperCase()).join(' / ') + ' 撮影'), el('span', `関連 ${entry.requirementIds.length}要求`));
      const memo = memoFor(entry.id);
      tile.append(open, facts, bottom, el('span', labels[memo.status], 'decision-badge'));
      if (memo.note) { const excerpt = el('p', `理由: ${memo.note}`, 'tile-note'); excerpt.title = memo.note; tile.append(excerpt); }
      $('gallery').append(tile);
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
        render(); refreshComparisonFacts();
      });
      b.setAttribute('aria-pressed', String(memoFor(entry.id).status === status)); choices.append(b);
    }
    const label = el('label', '選ぶ理由・確認したいこと', 'note-label');
    const note = el('textarea'); note.maxLength = 4000; note.rows = 4; note.value = memoFor(entry.id).note;
    note.addEventListener('input', () => { memos[entry.id] = { ...memoFor(entry.id), note: note.value }; save(); refreshComparisonFacts(); }); label.append(note);
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
    detailId = entry.id;
    const layout = el('div', undefined, 'detail-layout'); layout.append(inspectImage(entry), editor(entry));
    $('detail-content').replaceChildren(imageControls(), layout); $('detail').showModal();
  }
  for (const dialog of document.querySelectorAll('dialog')) {
    dialog.querySelector('.close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
      render();
      const target = dialog.id === 'detail' ? [...$('gallery').querySelectorAll('.tile-open')].find(n => n.dataset.entryId === detailId) : $('open-compare');
      (target && target.getClientRects().length ? target : $('search')).focus();
    });
  }
  $('reset-gallery').addEventListener('click', () => {
    face = 'all'; linkedIds = null; limit = 36;
    for (const id of ['search', 'purpose', 'decision']) $(id).value = '';
    render(); $('search').focus();
  });
  new ResizeObserver(() => {
    document.documentElement.style.setProperty('--compare-clearance', $('compare-bar').hidden ? '0px' : `${Math.ceil($('compare-bar').getBoundingClientRect().height) + 40}px`);
    if ($('compare-picks').contains(document.activeElement)) document.activeElement.scrollIntoView({ block: 'nearest', inline: 'nearest' });
  }).observe($('compare-bar'));
  $('clear-compare').addEventListener('click', () => { comparison.clear(); render(); focusWorkspace(); });
  function refreshComparisonFacts() {
    const holder = $('comparison-facts');
    if (!holder) return;
    const entries = [...comparison].map(id => byId.get(id));
    const table = el('table');
    table.append(el('caption', '候補の違いを確認 — 「差分あり」の行を見比べる。狭い画面では表を横にスクロール。'));
    const head = el('thead'), headings = el('tr');
    const corner = el('th', '比較項目'); corner.scope = 'col'; headings.append(corner);
    entries.forEach((entry, index) => { const h = el('th', `${index + 1}. ${entry.label} / ${entry.variant}`); h.scope = 'col'; headings.append(h); });
    head.append(headings); table.append(head);
    const body = el('tbody');
    for (const [label, value] of [
      ['目的', e => e.purpose],
      ['撮影記録', e => ['pc', 'sp'].filter(d => e.images[d]).map(d => d.toUpperCase()).join(' / ') || 'なし'],
      ['選択メモ', e => labels[memoFor(e.id).status]],
      ['選ぶ理由・確認事項', e => memoFor(e.id).note || 'まだ記入していません'],
      ['関連する要求', e => e.requirementIds.join(' · ') || '未整理'],
    ]) {
      const values = entries.map(value), row = el('tr');
      row.dataset.different = String(new Set(values).size > 1);
      const heading = el('th', label); heading.scope = 'row';
      if (new Set(values).size > 1) heading.append(el('span', '差分あり', 'difference-label'));
      row.append(heading);
      values.forEach(v => row.append(el('td', v))); body.append(row);
    }
    table.append(body); holder.replaceChildren(table);
  }
  $('open-compare').addEventListener('click', () => {
    const grid = el('div', undefined, 'compare-grid');
    grid.style.setProperty('--compare-columns', comparison.size);
    for (const id of comparison) { const entry = byId.get(id); const column = el('section'); column.append(el('p', `候補 ${grid.children.length + 1} / ${entry.label}`, 'compare-column-label'), inspectImage(entry), editor(entry)); grid.append(column); }
    const facts = el('div', undefined, 'comparison-facts'); facts.id = 'comparison-facts'; facts.tabIndex = 0; facts.setAttribute('role', 'region'); facts.setAttribute('aria-label', '候補の比較表。横にスクロールできます');
    const switcher = el('div', undefined, 'compare-switcher');
    switcher.setAttribute('role', 'group'); switcher.setAttribute('aria-label', '表示する比較候補');
    const applyCandidate = index => {
      grid.dataset.activeCandidate = String(index);
      for (const [i, section] of [...grid.children].entries()) section.dataset.active = String(i === index);
      for (const [i, control] of [...switcher.children].entries()) control.setAttribute('aria-pressed', String(i === index));
    };
    [...comparison].forEach((id, index) => {
      const entry = byId.get(id);
      switcher.append(button(`${index + 1}. ${entry.label} / ${entry.variant}`, () => applyCandidate(index)));
    });
    applyCandidate(0);
    const factsDisclosure = el('details', undefined, 'comparison-summary');
    factsDisclosure.open = !matchMedia('(max-width:700px)').matches;
    factsDisclosure.append(el('summary', '候補の比較表を見る'), facts);
    $('compare-content').replaceChildren(switcher, factsDisclosure, el('p', '関連要求は画像との対応を探す手掛かりです。受入条件の達成を示すものではありません。', 'comparison-evidence-note'), imageControls(), grid); refreshComparisonFacts(); $('compare').showModal();
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
    activeTab = index;
    tabs.forEach((tab, i) => { tab.setAttribute('aria-selected', String(i === index)); tab.tabIndex = i === index ? 0 : -1; $(tab.getAttribute('aria-controls')).hidden = i !== index; });
    saveWorkspace();
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
    saveWorkspace();
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
  try {
    const raw = localStorage.getItem(workspaceKey);
    if (raw) {
      if (raw.length > 1024 * 1024) throw Error('Workspace too large');
      const saved = JSON.parse(raw);
      if (saved?.schema !== workspaceKey) throw Error('Unknown workspace');
      if (collections.some(([key]) => key === saved.face)) face = saved.face;
      if (['pc', 'sp'].includes(saved.device)) device = saved.device;
      if (Number.isInteger(saved.limit)) limit = Math.min(data.entries.length, Math.max(36, saved.limit));
      if (Array.isArray(saved.linkedIds)) linkedIds = new Set(saved.linkedIds.filter(id => byId.has(id)));
      if (Array.isArray(saved.comparison)) for (const id of saved.comparison) { if (byId.has(id) && comparison.size < 3) comparison.add(id); }
      if (saved.activeTab === 1) activeTab = 1;
      if (['overview', 'width', 'native'].includes(saved.imageMode)) imageMode = saved.imageMode;
      for (const [id, key] of [['search', 'search'], ['req-search', 'reqSearch']]) if (typeof saved[key] === 'string' && saved[key].length <= 4000) $(id).value = saved[key];
      for (const [id, key] of [['purpose', 'purpose'], ['decision', 'decision'], ['evidence-state', 'evidenceState']]) if ([...$(id).options].some(o => o.value === saved[key])) $(id).value = saved[key];
      for (const b of document.querySelectorAll('[data-device]')) b.setAttribute('aria-pressed', String(b.dataset.device === device));
    }
  } catch { notify('前回の絞り込みを復元できませんでした。選択メモは保持しています。'); }
  switchTab(activeTab); requirements(); render();
}
start().catch(error => {
  $('gallery').replaceChildren(el('p', `${error.message} リポジトリをHTTPサーバーで開き、再読込してください。`));
  notify('カタログを開始できませんでした。');
});

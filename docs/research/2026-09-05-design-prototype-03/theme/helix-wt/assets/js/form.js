/* 段 11: フォーム（WT-EVT-0289 / 0301）。クライアント検証（サーバ inc/form.php wt_form_validate と同じ規則・同じ出し方 = data-wt-error）、steps の段階送り（fieldset の data-wt-step-i で対応）、inline-review の同一ページ見直し。JS 無効ではサーバ側が同じ遷移を担う */
(function(){
  var form = document.querySelector('.wt-form__form'); if (!form) return;
  var root = form.closest('.wt-form'); var mode = form.getAttribute('data-wt-error') || 'inline';
  var rows = function(){ return Array.prototype.slice.call(form.querySelectorAll('.wt-form__row')); };
  /* WordPress is_email() と同じ規則: 6 文字以上、@ は 1 つ、ローカル部は許可文字のみ、ドメインは '..' なし・ラベル 2 つ以上・各ラベルは英数字とハイフンでハイフン始まり/終わりでない */
  function isEmail(v){ if (v.length < 6 || v.indexOf('@', 1) === -1) return false; var at = v.lastIndexOf('@'); if (v.indexOf('@') !== at) return false; var local = v.slice(0, at), domain = v.slice(at + 1); if (!/^[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~.-]+$/.test(local)) return false; if (/\.\./.test(domain)) return false; var subs = domain.replace(/^\.+|\.+$/g, '').split('.'); if (subs.length < 2) return false; return subs.every(function(x){ return /^[a-z0-9-]+$/i.test(x) && !/^-|-$/.test(x); }); }
  function labelOf(row){ var l = row.querySelector('.wt-form__label'); return l ? l.textContent.replace(/[*＊]|必須|（必須）/g, '').trim() : ''; }
  function focusId(row){ var f = row.getAttribute('data-wt-field'); var t = row.className.match(/wt-form__row--([a-z0-9]+)/)[1]; if (t === 'date3') return 'wt-f-' + f + '-1'; if (t === 'radio' || t === 'checks') return 'wt-f-' + f + '-0'; if (t === 'yesno') return 'wt-f-' + f + '-0-y'; return 'wt-f-' + f; }
  function validateRow(row){
    var f = row.getAttribute('data-wt-field'); var t = row.className.match(/wt-form__row--([a-z0-9]+)/)[1]; var ctl = row.querySelector('.wt-form__control'); var req = !!row.querySelector('[aria-required="true"]'); var label = labelOf(row);
    var inputs = Array.prototype.slice.call(ctl.querySelectorAll('input, select, textarea'));
    if (t === 'privacy' || t === 'file' || t === 'hidden' || t === 'checks') return '';
    if (t === 'checkbox') return req && !inputs[0].checked ? '同意が必要です。' : '';
    if (t === 'yesno') { var groups = ctl.querySelectorAll('.wt-form__yesno'); var ok = Array.prototype.every.call(groups, function(g){ return !!g.querySelector('input:checked'); }); return req && !ok ? groups.length + ' つの質問すべてに答えてください。' : ''; }
    if (t === 'date3') return req && !inputs[0].value.trim() ? '第 1 希望日を入力してください。' : '';
    if (t === 'radio') return req && !inputs.some(function(i){ return i.checked; }) ? label + 'を選択してください。' : '';
    var v = (inputs[0].value || '').trim();
    if (!v) return req ? label + (t === 'select' ? 'を選択してください。' : 'を入力してください。') : '';
    if (t === 'email') { if (!isEmail(v)) return 'メールアドレスの形式が正しくありません。'; if (f === 'email-confirm') { var e = form.querySelector('#wt-f-email'); if (e && e.value.trim() !== v) return 'メールアドレスが一致しません。'; } }
    if (t === 'tel' && !/^[0-9０-９+\-() ]{8,20}$/.test(v)) return label + 'の形式が正しくありません。';
    if (t === 'postal' && !/^\d{3}-?\d{4}$/.test(v)) return '郵便番号は 7 桁で入力してください。';
    if (t === 'kana' && !/^[ぁ-ゖー\s　]+$/.test(v)) return 'ひらがなで入力してください。';
    if (t === 'number' && !(Number(v) >= 1)) return '1 以上の数を入力してください。';
    if (t === 'url' && !/^https?:\/\//.test(v)) return 'https:// から始まる URL を入力してください。';
    if (t === 'date' && !/^\d{4}-\d{2}-\d{2}$/.test(v)) return '日付の形式が正しくありません。';
    if (t === 'month' && !/^\d{4}-\d{2}$/.test(v)) return '年月の形式が正しくありません。';
    if (t === 'captcha' && v !== '7') return '答えが違います。';
    return '';
  }
  function clearErrors(){ form.querySelectorAll('.wt-form__row.is-error').forEach(function(r){ r.classList.remove('is-error'); var e = r.querySelector('.wt-form__error'); if (e) e.remove(); r.querySelectorAll('[aria-invalid]').forEach(function(i){ i.removeAttribute('aria-invalid'); i.removeAttribute('aria-describedby'); }); }); var s = root.querySelector('#wt-form-summary'); if (s) s.remove(); }
  function showErrors(list){
    if (!list.length) return;
    var summaryMode = mode === 'top-summary' || mode === 'both';
    list.forEach(function(x){ x.row.classList.add('is-error'); var i = document.getElementById(focusId(x.row)) || x.row.querySelector('input, select, textarea'); var descId = focusId(x.row) + '-err';
      if (mode === 'inline' || mode === 'both') { var p = document.createElement('p'); p.className = 'wt-form__error'; p.id = 'wt-f-' + x.f + '-err'; p.textContent = x.msg; x.row.querySelector('.wt-form__control').appendChild(p); descId = p.id; }
      if (i) { i.setAttribute('aria-invalid', 'true'); i.setAttribute('aria-describedby', summaryMode ? 'wt-form-summary' : descId); } /* サーバと同じ参照先（項目下 or まとめ） */
    });
    if (summaryMode) { var d = document.createElement('div'); d.className = 'wt-form__summary'; d.id = 'wt-form-summary'; d.setAttribute('role', 'alert'); d.setAttribute('tabindex', '-1'); d.innerHTML = '<p>入力内容に ' + list.length + ' 件の不備があります。</p><ul>' + list.map(function(x){ return '<li><a href="#' + focusId(x.row) + '">' + x.msg.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</a></li>'; }).join('') + '</ul>'; form.parentNode.insertBefore(d, form); d.focus(); }
    else { var first = document.getElementById(focusId(list[0].row)); if (first) first.focus(); }
  }
  function validate(scope){ clearErrors(); var list = []; rows().filter(function(r){ return !scope || scope.contains(r); }).forEach(function(r){ var m = validateRow(r); if (m) list.push({ f: r.getAttribute('data-wt-field'), row: r, msg: m }); }); showErrors(list); return !list.length; }
  // steps（fieldset と進捗 li は data-wt-step-i で対応。種別に無い段は無い）
  var steps = Array.prototype.slice.call(form.querySelectorAll('.wt-form__step')); var cur = 0;
  var prev = form.querySelector('.wt-form__prev'), next = form.querySelector('.wt-form__next'), submit = form.querySelector('.wt-form__submit');
  function showStep(i){ cur = i; var curId = steps[i].getAttribute('data-wt-step-i'); steps.forEach(function(s, k){ s.hidden = k !== i; }); form.querySelectorAll('.wt-form__steps li').forEach(function(li){ var id = li.getAttribute('data-wt-step-i'); li.classList.toggle('is-current', id === curId); li.classList.toggle('is-done', Number(id) < Number(curId)); }); if (prev) prev.hidden = i === 0; if (next) next.hidden = i === steps.length - 1; if (submit) submit.hidden = i !== steps.length - 1; var f = steps[i].querySelector('input:not([type=hidden]), select, textarea'); if (f) f.focus(); }
  if (steps.length) { showStep(0); next.addEventListener('click', function(){ if (validate(steps[cur])) showStep(cur + 1); }); prev.addEventListener('click', function(){ clearErrors(); showStep(cur - 1); }); }
  // inline-review
  var review = form.querySelector('.wt-form__inline-review'); var reviewed = false;
  function fillReview(){ var dl = review.querySelector('dl'); dl.innerHTML = ''; rows().forEach(function(r){ var f = r.getAttribute('data-wt-field'); var t = r.className.match(/wt-form__row--([a-z0-9]+)/)[1]; if (t === 'privacy' || t === 'captcha' || t === 'hidden') return; var v = ''; var ins = r.querySelectorAll('input, select, textarea'); if (t === 'checkbox') v = ins[0].checked ? '同意する' : '—'; else if (t === 'file') v = '（PoC ではファイルを送りません）'; else if (t === 'radio' || t === 'checks' || t === 'yesno') v = Array.prototype.filter.call(ins, function(i){ return i.checked; }).map(function(i){ return i.value; }).join(' / '); else if (t === 'date3') v = Array.prototype.map.call(ins, function(i){ return i.value; }).filter(Boolean).join(' / '); else v = ins[0].value; var dt = document.createElement('dt'); dt.textContent = labelOf(r) || f; var dd = document.createElement('dd'); dd.setAttribute('data-wt-field', f); dd.textContent = v || '—'; var div = document.createElement('div'); div.appendChild(dt); div.appendChild(dd); dl.appendChild(div); }); }
  if (review) { review.querySelector('.wt-form__review-edit').addEventListener('click', function(){ form.classList.remove('is-reviewing'); review.hidden = true; reviewed = false; var st0 = form.querySelector('input[name="wt_step"]'); if (st0) st0.value = 'input'; /* 修正後の再送信でもう一度見直しを出す */ var f = form.querySelector('input:not([type=hidden]), select, textarea'); if (f) f.focus(); }); }
  form.addEventListener('submit', function(ev){
    if (!validate()) { ev.preventDefault(); return; }
    if (review && !reviewed) { ev.preventDefault(); fillReview(); form.classList.add('is-reviewing'); review.hidden = false; review.querySelector('.wt-form__review-send').focus(); reviewed = true; var st = form.querySelector('input[name="wt_step"]'); if (st) st.value = 'confirm'; return; }
  });
  form.setAttribute('data-wt-js', 'ready');
})();

/* 段 11: フォーム（WT-EVT-0289 / 0301）。クライアント検証（サーバと同じ規則・同じ出し方 = data-wt-error）、steps の段階送り、inline-review の同一ページ見直し。JS 無効ではサーバ側が同じ遷移を担う */
(function(){
  var form = document.querySelector('.wt-form__form'); if (!form) return;
  var root = form.closest('.wt-form'); var mode = form.getAttribute('data-wt-error') || 'inline'; var confirmMode = form.getAttribute('data-wt-confirm');
  var rows = function(){ return Array.prototype.slice.call(form.querySelectorAll('.wt-form__row')); };
  var msgs = { req: 'を入力してください。', consent: '同意が必要です。', email: 'メールアドレスの形式が正しくありません。', emailc: 'メールアドレスが一致しません。', tel: '電話番号の形式が正しくありません。', postal: '郵便番号は 7 桁で入力してください。', kana: 'ひらがなで入力してください。', num: '1 以上の数を入力してください。', url: 'https:// から始まる URL を入力してください。', captcha: '答えが違います。' };
  function labelOf(row){ var l = row.querySelector('.wt-form__label'); return l ? l.textContent.replace(/[*＊]|必須|（必須）/g, '').trim() : ''; }
  function validateRow(row){
    var f = row.getAttribute('data-wt-field'); var ctl = row.querySelector('.wt-form__control'); var req = !!row.querySelector('[aria-required="true"]');
    var inputs = Array.prototype.slice.call(ctl.querySelectorAll('input, select, textarea'));
    if (f === 'privacy-link' || f === 'attachment') return '';
    if (f === 'consent') return inputs[0].checked ? '' : msgs.consent;
    if (f === 'newsletter-optin') return '';
    if (f === 'subject-radio') return inputs.some(function(i){ return i.checked; }) || !req ? '' : labelOf(row) + 'を選択してください。';
    if (f === 'yesno-questions') { var groups = ctl.querySelectorAll('.wt-form__yesno'); var ok = Array.prototype.every.call(groups, function(g){ return !!g.querySelector('input:checked'); }); return ok ? '' : '3 つの質問すべてに答えてください。'; }
    if (f === 'date-pref') { var first = inputs[0].value; return req && !first ? '第 1 希望日を入力してください。' : ''; }
    var v = (inputs[0].value || '').trim(); if (!v) return req ? labelOf(row) + msgs.req : '';
    if (f === 'email' || f === 'email-confirm') { if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return msgs.email; if (f === 'email-confirm') { var e = form.querySelector('#wt-f-email'); if (e && e.value.trim() !== v) return msgs.emailc; } }
    if (f === 'tel' && !/^[0-9０-９+\-() ]{8,20}$/.test(v)) return msgs.tel;
    if (f === 'postal' && !/^\d{3}-?\d{4}$/.test(v)) return msgs.postal;
    if (f === 'name-kana' && !/^[ぁ-ゖー\s　]+$/.test(v)) return msgs.kana;
    if (f === 'people-count' && !(Number(v) >= 1)) return msgs.num;
    if (f === 'url' && !/^https?:\/\//.test(v)) return msgs.url;
    if (f === 'captcha' && v !== '7') return msgs.captcha;
    return '';
  }
  function clearErrors(scope){ (scope || form).querySelectorAll('.wt-form__row.is-error').forEach(function(r){ r.classList.remove('is-error'); var e = r.querySelector('.wt-form__error'); if (e) e.remove(); r.querySelectorAll('[aria-invalid]').forEach(function(i){ i.removeAttribute('aria-invalid'); i.removeAttribute('aria-describedby'); }); }); var s = root.querySelector('#wt-form-summary'); if (s) s.remove(); }
  function showErrors(list){
    if (!list.length) return;
    if (mode === 'inline' || mode === 'both') list.forEach(function(x){ x.row.classList.add('is-error'); var id = 'wt-f-' + x.f + '-err'; var p = document.createElement('p'); p.className = 'wt-form__error'; p.id = id; p.textContent = x.msg; x.row.querySelector('.wt-form__control').appendChild(p); var i = x.row.querySelector('input, select, textarea'); if (i) { i.setAttribute('aria-invalid', 'true'); i.setAttribute('aria-describedby', id); } });
    else list.forEach(function(x){ x.row.classList.add('is-error'); });
    if (mode === 'top-summary' || mode === 'both') { var d = document.createElement('div'); d.className = 'wt-form__summary'; d.id = 'wt-form-summary'; d.setAttribute('role', 'alert'); d.setAttribute('tabindex', '-1'); d.innerHTML = '<p>入力内容に ' + list.length + ' 件の不備があります。</p><ul>' + list.map(function(x){ return '<li><a href="#wt-f-' + x.f + '">' + x.msg.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</a></li>'; }).join('') + '</ul>'; form.parentNode.insertBefore(d, form); d.focus(); }
    else { var first = list[0].row.querySelector('input, select, textarea'); if (first) first.focus(); }
  }
  function validate(scope){ clearErrors(); var list = []; rows().filter(function(r){ return !scope || scope.contains(r); }).forEach(function(r){ var m = validateRow(r); if (m) list.push({ f: r.getAttribute('data-wt-field'), row: r, msg: m }); }); showErrors(list); return !list.length; }
  // steps
  var steps = Array.prototype.slice.call(form.querySelectorAll('.wt-form__step')); var cur = 0;
  var prev = form.querySelector('.wt-form__prev'), next = form.querySelector('.wt-form__next'), submit = form.querySelector('.wt-form__submit');
  function showStep(i){ cur = i; steps.forEach(function(s, k){ s.hidden = k !== i; }); form.querySelectorAll('.wt-form__steps li').forEach(function(li, k){ li.classList.toggle('is-current', k === i); li.classList.toggle('is-done', k < i); }); if (prev) prev.hidden = i === 0; if (next) next.hidden = i === steps.length - 1; if (submit) submit.hidden = i !== steps.length - 1; var f = steps[i].querySelector('input:not([type=hidden]), select, textarea'); if (f) f.focus(); }
  if (steps.length) { showStep(0); next.addEventListener('click', function(){ if (validate(steps[cur])) showStep(cur + 1); }); prev.addEventListener('click', function(){ clearErrors(); showStep(cur - 1); }); }
  // inline-review
  var review = form.querySelector('.wt-form__inline-review');
  function fillReview(){ var dl = review.querySelector('dl'); dl.innerHTML = ''; rows().forEach(function(r){ var f = r.getAttribute('data-wt-field'); if (f === 'privacy-link' || f === 'captcha') return; var v = ''; var ins = r.querySelectorAll('input, select, textarea'); if (ins.length === 1 && ins[0].type !== 'checkbox' && ins[0].type !== 'radio') v = ins[0].type === 'file' ? '（PoC では送信しません）' : ins[0].value; else if (ins[0].type === 'checkbox') v = ins[0].checked ? '同意する' : '—'; else { v = Array.prototype.filter.call(ins, function(i){ return i.type !== 'radio' ? i.value : i.checked; }).map(function(i){ return i.value; }).join(' / '); } var dt = document.createElement('dt'); dt.textContent = labelOf(r) || (r.querySelector('label') || {}).textContent || f; var dd = document.createElement('dd'); dd.setAttribute('data-wt-field', f); dd.textContent = v || '—'; var div = document.createElement('div'); div.appendChild(dt); div.appendChild(dd); dl.appendChild(div); }); }
  var reviewed = false;
  if (review) { review.querySelector('.wt-form__review-edit').addEventListener('click', function(){ form.classList.remove('is-reviewing'); review.hidden = true; reviewed = false; var st0 = form.querySelector('input[name="wt_step"]'); if (st0) st0.value = 'input'; /* 修正後の再送信でもう一度見直しを出す */ var f = form.querySelector('input:not([type=hidden]), select, textarea'); if (f) f.focus(); }); }
  form.addEventListener('submit', function(ev){
    if (!validate()) { ev.preventDefault(); return; }
    if (review && !reviewed) { ev.preventDefault(); fillReview(); form.classList.add('is-reviewing'); review.hidden = false; review.querySelector('.wt-form__review-send').focus(); reviewed = true; var st = form.querySelector('input[name="wt_step"]'); if (st) st.value = 'confirm'; return; }
  });
  form.setAttribute('data-wt-js', 'ready');
})();

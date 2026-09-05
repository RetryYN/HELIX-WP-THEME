/* 囲み Q&A（モーダル型）: .is-style-wt-qa-modal の回答段落を <dialog> へ移し、「回答を見る」ボタンで開く。
   JS 無効時は回答段落が本文内に残る（qa 型と同じ見え方）。自動再生・自動オープンはしない。 */
(function(){
  if (!('HTMLDialogElement' in window)) return; // <dialog> 非対応ブラウザは本文内表示のまま
  document.querySelectorAll('.wp-block-group.is-style-wt-qa-modal').forEach(function(box, i){
    var ps = box.querySelectorAll(':scope > p');
    if (ps.length < 2) return;
    var q = ps[0], answers = Array.prototype.slice.call(ps, 1);
    var dlg = document.createElement('dialog');
    dlg.className = 'wt-qa-modal__dialog';
    dlg.setAttribute('aria-labelledby', 'wt-qa-modal-q-' + i);
    var head = document.createElement('div'); head.className = 'wt-qa-modal__head';
    var qc = q.cloneNode(true); qc.id = 'wt-qa-modal-q-' + i; qc.removeAttribute('style');
    var close = document.createElement('button'); close.type = 'button'; close.className = 'wt-qa-modal__close';
    close.setAttribute('aria-label', '閉じる'); close.textContent = '×';
    head.appendChild(qc); head.appendChild(close);
    var body = document.createElement('div'); body.className = 'wt-qa-modal__body';
    answers.forEach(function(a){ body.appendChild(a); }); // 本文から移動（JS 有効時は本文に回答を残さない）
    dlg.appendChild(head); dlg.appendChild(body);
    var open = document.createElement('button'); open.type = 'button'; open.className = 'wt-qa-modal__open';
    open.textContent = '回答を見る'; open.setAttribute('aria-haspopup', 'dialog');
    box.appendChild(open); box.appendChild(dlg);
    open.addEventListener('click', function(){ dlg.showModal(); close.focus(); });
    close.addEventListener('click', function(){ dlg.close(); });
    dlg.addEventListener('click', function(e){ if (e.target === dlg) dlg.close(); }); // 背景クリックで閉じる（内側のクリックは対象が子要素）
    dlg.addEventListener('close', function(){ open.focus(); });
  });
})();

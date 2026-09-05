/* 段 3 category: core の「次のページ」リンクは a 要素自身が .wp-block-query-pagination-next を持つ（子 a ではない）。 load-more は JS 有効時だけ番号送りを置き換え、失敗時は番号リンクへ戻す。 */
(function(){
  var button = document.querySelector('[data-wt-load-more]');
  var body = document.body;
  if (!button || !body.classList.contains('wt-cat-pagination-load-more') || !document.documentElement.classList.contains('wt-js')) return;
  var list = document.querySelector('.wt-cat-list');
  var next = document.querySelector('.wt-cat-pagination a.wp-block-query-pagination-next');
  var fallback = function(){
    body.classList.remove('wt-cat-pagination-load-more');
    button.hidden = true;
    var pagination = document.querySelector('.wt-cat-pagination'); if (pagination) pagination.hidden = false;
  };
  if (!list || !next) { fallback(); return; }
  var busy = false;
  var sync = function(doc){
    var incoming = doc.querySelectorAll('.wt-cat-list > li');
    incoming.forEach(function(item){ list.appendChild(document.importNode(item, true)); });
    var following = doc.querySelector('.wt-cat-pagination a.wp-block-query-pagination-next');
    if (following) { next = following; button.disabled = false; button.textContent = 'さらに読む'; }
    else { next = null; button.hidden = true; button.style.display = 'none'; button.setAttribute('aria-hidden', 'true'); } // 最終ページ: [hidden] は CSS の display:flex に負けるため CSS 側の [hidden] ルールと合わせて明示的に消す
  };
  button.addEventListener('click', function(){
    if (busy || !next) return;
    busy = true; button.disabled = true; button.textContent = '読み込み中…';
    fetch(next.href, { credentials: 'same-origin' }).then(function(response){
      if (!response.ok) throw new Error('pagination ' + response.status);
      return response.text();
    }).then(function(html){
      var doc = new DOMParser().parseFromString(html, 'text/html'); sync(doc);
    }).catch(fallback).finally(function(){ busy = false; });
  });
})();

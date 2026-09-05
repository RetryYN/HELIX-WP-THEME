/* ヘッダー: 部分固定（下スクロールで隠れ、上スクロールで再表示。R08 / P10）とお知らせ帯の閉状態記憶（P18） */
(function(){
  var header = document.querySelector('.wt-header');
  if (header) {
    var last = window.pageYOffset, hidden = false, h = header.offsetHeight;
    var onScroll = function(){
      var y = window.pageYOffset;
      if (header.querySelector('.wp-block-navigation__responsive-container.is-menu-open')) { last = y; return; }
      if (y > last + 4 && y > h * 2 && !hidden) { header.classList.add('is-hidden'); hidden = true; }
      else if (y < last - 4 && hidden) { header.classList.remove('is-hidden'); hidden = false; }
      last = y;
    };
    window.addEventListener('scroll', onScroll, { passive: true });
  }
  var bar = document.querySelector('.wt-announce');
  if (bar) {
    var key = 'wt-announce-closed:' + (bar.getAttribute('data-wt-id') || 'default');
    var btn = bar.querySelector('.wt-announce__close');
    if (btn) btn.addEventListener('click', function(){
      document.documentElement.classList.add('wt-announce-closed');
      try { localStorage.setItem(key, '1'); } catch (e) {}
    });
  }
})();

/* 記事面: 目次（SP 開閉・現在位置強調）、関連カルーセル（自動送りなし）、共有（Web Share / リンクコピー）、count-up */
(function(){
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isSp = window.matchMedia('(max-width: 599px)').matches;

  // 目次
  var toc = document.querySelector('.wt-toc');
  if (toc) {
    var d = toc.querySelector('details');
    if (d && isSp && toc.getAttribute('data-wt-toc') === 'box') d.removeAttribute('open'); // SP は開閉（既定閉、P19）
    var links = Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]'));
    var heads = links.map(function(a){ return document.getElementById(a.getAttribute('href').slice(1)); }).filter(Boolean);
    if (heads.length && 'IntersectionObserver' in window) {
      var current = null;
      var set = function(id){
        if (current === id) return; current = id;
        links.forEach(function(a){ a.classList.toggle('is-current', a.getAttribute('href') === '#' + id); });
      };
      var io = new IntersectionObserver(function(){
        var y = window.pageYOffset + 120, best = heads[0];
        heads.forEach(function(h){ if (h.offsetTop <= y) best = h; });
        set(best.id);
      }, { rootMargin: '-100px 0px -60% 0px', threshold: [0, 1] });
      heads.forEach(function(h){ io.observe(h); });
      window.addEventListener('scroll', function(){ var y = window.pageYOffset + 120, best = heads[0]; heads.forEach(function(h){ if (h.offsetTop <= y) best = h; }); set(best.id); }, { passive: true });
    }
  }

  // 関連カルーセル / スライダー: 前後ボタン（自動送りは実装しない）。slider はページ送り + ドット（PO 反応 16 回目 WT-EVT-0261、Claude 案）
  document.querySelectorAll('.wt-related:not(.wt-next) .wp-block-post-template').forEach(function(track){
    var isCarousel = document.body.classList.contains('wt-related-carousel'), isSlider = document.body.classList.contains('wt-related-slider');
    if (!isCarousel && !isSlider) return;
    var nav = document.createElement('div'); nav.className = 'wt-carousel__nav';
    var page = function(){ return isSlider ? track.clientWidth : track.clientWidth * 0.8; };
    var mk = function(dir, label){ var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', label); b.innerHTML = '<i class="wt-i wt-i--chevron-' + (dir < 0 ? 'right" style="transform:scaleX(-1)' : 'right"') + '" aria-hidden="true"></i>'; b.addEventListener('click', function(){ track.scrollBy({ left: dir * page(), behavior: reduce ? 'auto' : 'smooth' }); }); return b; };
    var prev = mk(-1, '前へ'), next = mk(1, '次へ');
    nav.appendChild(prev);
    var dots = null, dotBtns = [];
    if (isSlider) {
      dots = document.createElement('div'); dots.className = 'wt-slider__dots'; dots.setAttribute('role', 'tablist'); dots.setAttribute('aria-label', 'ページ');
      var items = track.children.length, per = Math.max(1, Math.round(track.clientWidth / (track.firstElementChild ? track.firstElementChild.getBoundingClientRect().width + 16 : track.clientWidth)));
      var pages = Math.max(1, Math.ceil(items / per));
      for (var i = 0; i < pages; i++) { var d = document.createElement('button'); d.type = 'button'; d.setAttribute('role', 'tab'); d.setAttribute('aria-label', (i + 1) + ' / ' + pages); (function(idx){ d.addEventListener('click', function(){ track.scrollTo({ left: idx * track.clientWidth, behavior: reduce ? 'auto' : 'smooth' }); }); })(i); dots.appendChild(d); dotBtns.push(d); }
      nav.appendChild(dots);
    }
    nav.appendChild(next);
    track.parentNode.insertBefore(nav, track.nextSibling);
    var upd = function(){
      prev.disabled = track.scrollLeft <= 2; next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
      if (dotBtns.length) { var cur = Math.min(dotBtns.length - 1, Math.round(track.scrollLeft / track.clientWidth)); dotBtns.forEach(function(d, i){ d.setAttribute('aria-selected', i === cur ? 'true' : 'false'); }); }
    };
    track.addEventListener('scroll', upd, { passive: true }); upd();
  });

  // 共有: Web Share API があれば共有、無ければリンクコピー
  document.querySelectorAll('.wt-share [data-wt-share], .wt-tail-icons [data-wt-share]').forEach(function(b){
    b.addEventListener('click', function(){
      var data = { title: document.title, url: location.href };
      if (b.getAttribute('data-wt-share') === 'share' && navigator.share) { navigator.share(data).catch(function(){}); return; }
      if (navigator.clipboard) navigator.clipboard.writeText(location.href).then(function(){ var t = b.querySelector('span'); if (t) { var o = t.textContent; t.textContent = 'コピーしました'; setTimeout(function(){ t.textContent = o; }, 1500); } });
    });
  });

  // count-up（motion ON かつ reduced-motion でないときだけ。それ以外は最終値を即表示）
  var counts = document.querySelectorAll('.wt-count[data-to]');
  if (counts.length) {
    var on = document.body.classList.contains('wt-motion-on') && !reduce && 'IntersectionObserver' in window;
    var fmt = function(n, dec){ return n.toLocaleString('ja-JP', { minimumFractionDigits: dec, maximumFractionDigits: dec }); };
    var run = function(el){
      var to = parseFloat(el.getAttribute('data-to')), dec = (el.getAttribute('data-to').split('.')[1] || '').length, t0 = null, dur = 900;
      var step = function(ts){ if (!t0) t0 = ts; var p = Math.min(1, (ts - t0) / dur); p = 1 - Math.pow(1 - p, 3); el.textContent = fmt(to * p, dec); if (p < 1) requestAnimationFrame(step); };
      requestAnimationFrame(step);
    };
    if (!on) { counts.forEach(function(el){ el.textContent = fmt(parseFloat(el.getAttribute('data-to')), (el.getAttribute('data-to').split('.')[1] || '').length); }); }
    else { var cio = new IntersectionObserver(function(es){ es.forEach(function(e){ if (e.isIntersecting) { run(e.target); cio.unobserve(e.target); } }); }); counts.forEach(function(el){ cio.observe(el); }); }
  }
})();

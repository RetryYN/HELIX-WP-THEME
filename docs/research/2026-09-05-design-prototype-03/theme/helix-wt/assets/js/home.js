/* HP hero スライダー（home_hero:slider）: 前後ボタンとドットのページ送り。自動送りなし。JS 無効時は横スクロール（scroll-snap）のみ */
(function(){
  var track = document.querySelector('.wt-home-hero--slider .wt-home-slider__track');
  if (!track) return;
  var slides = Array.prototype.slice.call(track.children);
  if (slides.length < 2) return;
  var nav = document.querySelector('.wt-home-hero--slider .wt-home-slider__nav');
  var dots = nav ? nav.querySelector('.wt-home-slider__dots') : null;
  var prev = nav ? nav.querySelector('[data-wt-slide="prev"]') : null;
  var next = nav ? nav.querySelector('[data-wt-slide="next"]') : null;
  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var current = 0;
  function go(i){ current = (i + slides.length) % slides.length; track.scrollTo({ left: slides[current].offsetLeft, behavior: reduced ? 'auto' : 'smooth' }); update(); }
  function update(){
    if (!dots) return;
    Array.prototype.forEach.call(dots.children, function(d, i){ d.setAttribute('aria-current', i === current ? 'true' : 'false'); });
  }
  if (dots) {
    dots.innerHTML = '';
    slides.forEach(function(_, i){ var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', (i + 1) + ' 枚目'); b.addEventListener('click', function(){ go(i); }); dots.appendChild(b); });
  }
  if (prev) prev.addEventListener('click', function(){ go(current - 1); });
  if (next) next.addEventListener('click', function(){ go(current + 1); });
  track.addEventListener('scroll', function(){ var i = Math.round(track.scrollLeft / track.clientWidth); if (i !== current) { current = i; update(); } }, { passive: true });
  track.addEventListener('keydown', function(e){ if (e.key === 'ArrowRight') { e.preventDefault(); go(current + 1); } if (e.key === 'ArrowLeft') { e.preventDefault(); go(current - 1); } });
  if (nav) nav.hidden = false;
  update();
})();

/* 段 8: 汎用カルーセル（[data-wt-carousel]: hero cards-carousel 等）。前後ボタンで 1 枚ずつ送る。自動送りなし。JS 無効時は横スクロール（scroll-snap）のみ */
(function(){
  Array.prototype.forEach.call(document.querySelectorAll('[data-wt-carousel]'), function(root){
    var track = root.querySelector('.wt-hcar__track'); if (!track) return;
    var items = Array.prototype.slice.call(track.children); if (items.length < 2) return;
    var nav = root.querySelector('.wt-hcar__nav');
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function step(dir){
      var w = items[0].getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || 0);
      track.scrollBy({ left: dir * w, behavior: reduced ? 'auto' : 'smooth' });
    }
    if (nav) {
      var prev = nav.querySelector('[data-wt-slide="prev"]'), next = nav.querySelector('[data-wt-slide="next"]');
      if (prev) prev.addEventListener('click', function(){ step(-1); });
      if (next) next.addEventListener('click', function(){ step(1); });
      nav.hidden = false;
    }
    track.addEventListener('keydown', function(e){ if (e.key === 'ArrowRight') { e.preventDefault(); step(1); } if (e.key === 'ArrowLeft') { e.preventDefault(); step(-1); } });
  });
})();

/* 段 8: カウントダウン（[data-wt-countdown]）。残り日・時・分を 1 分ごとに更新。開催後は「開催しました」。JS 無効時は開催日の文字だけ */
(function(){
  var els = document.querySelectorAll('[data-wt-countdown]'); if (!els.length) return;
  function tick(){
    Array.prototype.forEach.call(els, function(el){
      var t = Date.parse(el.getAttribute('data-wt-countdown')); if (isNaN(t)) return;
      var diff = t - Date.now(); var d = el.querySelector('[data-wt-cd="d"]'), h = el.querySelector('[data-wt-cd="h"]'), m = el.querySelector('[data-wt-cd="m"]');
      if (diff <= 0) { el.classList.add('is-past'); if (d) d.textContent = '0'; if (h) h.textContent = '0'; if (m) m.textContent = '0'; var done = el.querySelector('.wt-part-countdown__done'); if (!done) { done = document.createElement('p'); done.className = 'wt-part-countdown__done'; done.textContent = '開催しました'; el.appendChild(done); } return; }
      var mins = Math.floor(diff / 60000);
      if (d) d.textContent = String(Math.floor(mins / 1440)); if (h) h.textContent = String(Math.floor((mins % 1440) / 60)); if (m) m.textContent = String(mins % 60);
      el.classList.add('is-live');
    });
  }
  tick(); setInterval(tick, 60000);
})();

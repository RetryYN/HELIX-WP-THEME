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

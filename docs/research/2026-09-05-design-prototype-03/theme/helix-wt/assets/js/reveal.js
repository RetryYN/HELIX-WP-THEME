/* 出現アニメ（IO）。CSS 側は html.wt-js かつ body.wt-motion-on のときだけ初期非表示。reduced-motion では即表示 */
(function(){
  document.documentElement.classList.add('wt-js');
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var on = document.body.classList.contains('wt-motion-on');
  if (!('IntersectionObserver' in window) || reduce || !on) {
    document.querySelectorAll('.wt-reveal').forEach(function(el){ el.classList.add('is-in'); });
    return;
  }
  var io = new IntersectionObserver(function(es){
    es.forEach(function(e){ if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
  }, { rootMargin: '0px 0px -10% 0px' });
  document.querySelectorAll('.wt-reveal').forEach(function(el){ io.observe(el); });
})();

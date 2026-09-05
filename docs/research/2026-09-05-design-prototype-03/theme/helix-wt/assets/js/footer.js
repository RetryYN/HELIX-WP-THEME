/* 段 3 footer: SP の details は JS 有効時だけ閉じ、JS 無効時は HTML の open 属性で全展開する。 */
(function(){
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var details = document.querySelectorAll('.wt-footer__sitemap details');
  if (details.length && document.documentElement.classList.contains('wt-js') && window.matchMedia && window.matchMedia('(max-width: 599px)').matches) {
    details.forEach(function(d){ d.removeAttribute('open'); });
  }
  var top = document.querySelector('.wt-totop');
  if (top) top.addEventListener('click', function(e){
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
  });
})();

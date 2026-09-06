/* 段 10: サイドバー（WT-EVT-0289）。SP ドロワー（side_sp:drawer）と PC ドロワー（side_nav:drawer-pc）は右サイドバーの中身を複製して開閉。メガメニュー（side_nav:mega-menu）はヘッダーのナビ直下にパネルを出す。JS 無効時: ドロワー系はサイドバーを本文下に表示、メガメニューは出ない */
(function(){
  var body = document.body;
  var side = document.querySelector('.wt-side--right');
  var openBtn = document.querySelector('.wt-side-drawer__open');
  var drawer = document.getElementById('wt-side-drawer');
  var hasSide = !body.classList.contains('wt-side-layout-none');
  var useDrawer = (hasSide && body.classList.contains('wt-side-sp-drawer')) || body.classList.contains('wt-side-nav-drawer-pc');
  if (side && openBtn && drawer && useDrawer) {
    var bodyEl = drawer.querySelector('.wt-side-drawer__body');
    var closeBtn = drawer.querySelector('.wt-side-drawer__close');
    var last = null;
    function open(){ bodyEl.innerHTML = side.innerHTML; bodyEl.querySelectorAll('[id]').forEach(function(el){ el.id = 'drawer-' + el.id; }); drawer.hidden = false; body.classList.add('wt-side-drawer-open'); openBtn.setAttribute('aria-expanded', 'true'); last = document.activeElement; closeBtn.focus(); }
    function close(){ drawer.hidden = true; body.classList.remove('wt-side-drawer-open'); openBtn.setAttribute('aria-expanded', 'false'); if (last && last.focus) last.focus(); }
    openBtn.hidden = false;
    openBtn.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    drawer.addEventListener('click', function(e){ if (e.target === drawer) close(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !drawer.hidden) close(); });
    body.classList.add('wt-side-drawer-ready');
  }
  Array.prototype.forEach.call(document.querySelectorAll('[data-wt-totop]'), function(b){ b.addEventListener('click', function(){ window.scrollTo({ top: 0, behavior: 'smooth' }); }); });
  var mega = document.getElementById('wt-megamenu');
  var nav = document.querySelector('.wt-header__nav');
  if (mega && nav && body.classList.contains('wt-side-nav-mega-menu')) {
    var trigger = document.createElement('button'); trigger.type = 'button'; trigger.className = 'wt-megamenu__trigger'; trigger.setAttribute('aria-expanded', 'false'); trigger.setAttribute('aria-controls', 'wt-megamenu'); trigger.textContent = 'すべて';
    nav.insertBefore(trigger, nav.firstChild);
    var header = document.querySelector('.wt-header'); if (header) header.appendChild(mega);
    function toggle(force){ var on = typeof force === 'boolean' ? force : mega.hidden; mega.hidden = !on; trigger.setAttribute('aria-expanded', on ? 'true' : 'false'); }
    trigger.addEventListener('click', function(){ toggle(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !mega.hidden) { toggle(false); trigger.focus(); } });
    document.addEventListener('click', function(e){ if (!mega.hidden && !mega.contains(e.target) && e.target !== trigger) toggle(false); });
    body.classList.add('wt-megamenu-ready');
  }
})();

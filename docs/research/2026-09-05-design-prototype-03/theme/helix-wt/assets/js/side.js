/* 段 10: サイドバー（WT-EVT-0289）。SP ドロワー（side_sp:drawer）と PC ドロワー（side_nav:drawer-pc）は右サイドバーの中身を複製して開閉。メガメニュー（side_nav:mega-menu）はヘッダーのナビ直下にパネルを出す。JS 無効時: ドロワー系はサイドバーを本文下に表示、メガメニューは出ない */
(function(){
  var body = document.body;
  var side = document.querySelector('.wt-side--right');
  var openBtn = document.querySelector('.wt-side-drawer__open');
  var drawer = document.getElementById('wt-side-drawer');
  var hasSide = !body.classList.contains('wt-side-layout-none');
  var useDrawer = (hasSide && body.classList.contains('wt-side-sp-drawer')) || body.classList.contains('wt-side-nav-drawer-pc');
  if (side && openBtn && drawer && useDrawer) {
    document.body.appendChild(drawer); // main の中にあると main の inert で自分も不活性になるので body 直下へ移す
    var bodyEl = drawer.querySelector('.wt-side-drawer__body');
    var closeBtn = drawer.querySelector('.wt-side-drawer__close');
    var last = null;
    var inerted = [];
    function open(){
      bodyEl.innerHTML = side.innerHTML;
      // 複製内の id を付け替え、for / aria-labelledby / aria-describedby / #リンク の参照も対応表で更新（本文の目次 #h-N など複製外の参照はそのまま）
      var map = {};
      bodyEl.querySelectorAll('[id]').forEach(function(el){ map[el.id] = 'drawer-' + el.id; el.id = map[el.id]; });
      ['for', 'aria-labelledby', 'aria-describedby', 'aria-controls'].forEach(function(attr){ bodyEl.querySelectorAll('[' + attr + ']').forEach(function(el){ var v = el.getAttribute(attr).split(/\s+/).map(function(t){ return map[t] || t; }).join(' '); el.setAttribute(attr, v); }); });
      bodyEl.querySelectorAll('a[href^="#"]').forEach(function(a){ var t = a.getAttribute('href').slice(1); if (map[t]) a.setAttribute('href', '#' + map[t]); });
      drawer.hidden = false; body.classList.add('wt-side-drawer-open'); openBtn.setAttribute('aria-expanded', 'true'); last = document.activeElement;
      // フォーカス封じ込め: ドロワー以外の body 直下を inert に
      inerted = []; Array.prototype.forEach.call(body.children, function(c){ if (c !== drawer && !c.hasAttribute('inert')) { c.setAttribute('inert', ''); inerted.push(c); } });
      closeBtn.focus();
    }
    function close(){ drawer.hidden = true; body.classList.remove('wt-side-drawer-open'); openBtn.setAttribute('aria-expanded', 'false'); inerted.forEach(function(c){ c.removeAttribute('inert'); }); inerted = []; if (last && last.focus) last.focus(); }
    openBtn.hidden = false;
    openBtn.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    drawer.addEventListener('click', function(e){ if (e.target === drawer) close(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !drawer.hidden) close(); });
    // Tab / Shift+Tab をドロワー内で循環（inert だけでは document へ抜ける）
    drawer.addEventListener('keydown', function(e){ if (e.key !== 'Tab' || drawer.hidden) return; var f = Array.prototype.filter.call(drawer.querySelectorAll('a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])'), function(el){ return !el.disabled && el.getBoundingClientRect().width > 0; }); if (!f.length) return; var first = f[0], lastEl = f[f.length - 1]; if (e.shiftKey && (document.activeElement === first || !drawer.contains(document.activeElement))) { e.preventDefault(); lastEl.focus(); } else if (!e.shiftKey && document.activeElement === lastEl) { e.preventDefault(); first.focus(); } });
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

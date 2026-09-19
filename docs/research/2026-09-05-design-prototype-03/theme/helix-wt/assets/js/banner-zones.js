/* The close choice stays on this device. No tracking and no automatic creative motion. */
(() => {
  for (const notice of document.querySelectorAll('.wt-banner-zone--header-below')) {
    const key = 'helix-wt:notice:' + notice.dataset.wtBannerId;
    try { if (localStorage.getItem(key) === 'closed') notice.hidden = true; } catch { /* Storage denial leaves the notice available. */ }
    const close = notice.querySelector('[data-wt-banner-close]');
    if (!close) continue;
    close.hidden = false;
    close.addEventListener('click', () => {
      notice.hidden = true;
      try { localStorage.setItem(key, 'closed'); } catch { /* Closing still works for this page. */ }
      const target = document.querySelector('main');
      if (target) { const old = target.getAttribute('tabindex'); target.setAttribute('tabindex', '-1'); target.focus({ preventScroll: true }); target.addEventListener('blur', () => { if (old === null) target.removeAttribute('tabindex'); else target.setAttribute('tabindex', old); }, { once: true }); }
    });
  }
})();
(() => {
  const stack = document.querySelector('.wt-banner-stack');
  if (stack) {
    stack.dataset.ready = '';
    const sync = () => document.documentElement.style.setProperty('--wt-banner-stack-height', stack.getBoundingClientRect().height + 'px');
    new ResizeObserver(sync).observe(stack);
    sync();
  }
  const dialog = document.querySelector('[data-wt-initial-modal]');
  if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
})();

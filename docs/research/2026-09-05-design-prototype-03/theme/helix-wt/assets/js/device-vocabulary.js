/* Core content remains readable before enhancement and when scripting is unavailable. */
(() => {
  const media = matchMedia('(max-width: 767px)');
  for (const root of document.querySelectorAll('[data-wt-device-preset]')) {
    let contract;
    try { contract = JSON.parse(root.dataset.wtDeviceContract); } catch { continue; }
    const toc = root.querySelector('.wt-device-toc');
    const groups = [];
    for (const widget of root.querySelectorAll('[data-wt-device-tabs]')) {
      const list = widget.querySelector('.wt-device-tabs__list');
      const tabs = [...list.children];
      const panels = [...widget.querySelectorAll('.wt-device-tabs__panel')];
      const headings = [...widget.querySelectorAll('.wt-device-tabs__heading')];
      const toggles = headings.map(h => h.querySelector('button'));
      if (tabs.length !== panels.length || !tabs.length) continue;
      let active = 0;
      const select = index => {
        active = index;
        tabs.forEach((tab, i) => { tab.tabIndex = i === active ? 0 : -1; tab.setAttribute('aria-selected', String(i === active)); panels[i].hidden = i !== active; });
      };
      tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => select(index));
        tab.addEventListener('keydown', event => {
          const next = event.key === 'ArrowRight' ? (index + 1) % tabs.length : event.key === 'ArrowLeft' ? (index + tabs.length - 1) % tabs.length : event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : null;
          if (next !== null) { event.preventDefault(); select(next); tabs[next].focus(); }
        });
        toggles[index].addEventListener('click', () => { const open = toggles[index].getAttribute('aria-expanded') !== 'true'; toggles[index].setAttribute('aria-expanded', String(open)); panels[index].hidden = !open; });
      });
      groups.push(() => {
        const accordion = (media.matches ? contract.sp : contract.pc).tabs === 'accordion';
        const focused = widget.contains(document.activeElement);
        list.hidden = accordion;
        list.setAttribute('role', 'tablist');
        list.setAttribute('aria-label', '内容の切り替え');
        tabs.forEach((tab, i) => {
          tab.setAttribute('role', 'tab');
          headings[i].hidden = !accordion;
          headings[i].querySelector('span').hidden = true;
          toggles[i].hidden = !accordion;
          toggles[i].setAttribute('aria-expanded', 'true');
          panels[i].hidden = false;
          panels[i].setAttribute('role', accordion ? 'region' : 'tabpanel');
          panels[i].setAttribute('aria-labelledby', (accordion ? toggles[i] : tab).id);
          panels[i].tabIndex = 0;
        });
        if (!accordion) select(active);
        if (focused) (accordion ? toggles[active] : tabs[active]).focus();
      });
    }
    const update = () => {
      const device = media.matches ? 'sp' : 'pc';
      root.dataset.wtDeviceActive = device;
      if (toc) toc.open = contract[device].toc === 'open';
      groups.forEach(apply => apply());
    };
    update(); media.addEventListener('change', update);
    const action = root.querySelector('.wt-device-action');
    if (action && contract.sp.cta === 'sticky') {
      const slot = document.createElement('div'); action.before(slot); slot.append(action);
      const position = () => {
        const height = action.getBoundingClientRect().height;
        slot.style.minHeight = media.matches ? height + 'px' : '';
        document.documentElement.style.setProperty('--wt-device-action-height', height + 'px');
        action.classList.toggle('wt-device-action--fixed', media.matches && slot.getBoundingClientRect().top <= innerHeight - height);
      };
      addEventListener('scroll', position, { passive: true }); addEventListener('resize', position); position();
    }

    for (const gallery of root.querySelectorAll('.wt-device-gallery')) {
      const controls = document.createElement('div'); controls.className = 'wt-device-gallery__controls';
      for (const [label, direction] of [['前の写真', -1], ['次の写真', 1]]) {
        const button = document.createElement('button'); button.type = 'button'; button.textContent = label;
        button.addEventListener('click', () => gallery.scrollBy({ left: direction * gallery.clientWidth, behavior: 'auto' })); controls.append(button);
      }
      gallery.after(controls);
    }
  }
})();

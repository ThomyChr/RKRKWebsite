/**
 * Shared RKRK layout: left menu hide/resize + Boxen anzeigen.
 * Applies on every page unless an exception is set:
 *   <html data-rkrk-layout="off">  or  <body data-rkrk-layout="off">
 */
(function (global) {
  'use strict';

  function isException() {
    const roots = [document.documentElement, document.body];
    for (let i = 0; i < roots.length; i += 1) {
      const el = roots[i];
      if (!el || !el.getAttribute) continue;
      const v = String(el.getAttribute('data-rkrk-layout') || '').toLowerCase();
      if (v === 'off' || v === '0' || v === 'false' || v === 'none') return true;
    }
    return false;
  }

  function pageKey() {
    try {
      return String((location && location.pathname) || 'page').replace(/[^a-z0-9]+/gi, '_');
    } catch (_) {
      return 'page';
    }
  }

  function refreshMapSize(retry) {
    const n = Number(retry) || 0;
    try {
      if (typeof global.rkrkUpdateMapSize === 'function') global.rkrkUpdateMapSize();
    } catch (_) { /* noop */ }
    if (n < 8) setTimeout(() => refreshMapSize(n + 1), 120);
  }

  function collectBoxes() {
    return Array.from(document.querySelectorAll('[data-rkrk-box]'));
  }

  function setBoxVisible(box, on) {
    const mode = String(box.getAttribute('data-rkrk-box-toggle') || 'display');
    if (mode.indexOf('class:') === 0) {
      const cls = mode.slice(6) || 'show';
      box.classList.toggle(cls, !!on);
      box.style.display = '';
      return;
    }
    box.style.display = on ? '' : 'none';
  }

  function boxAllowed(box) {
    const req = String(box.getAttribute('data-rkrk-box-requires') || '');
    if (!req) return true;
    const parts = req.split(':');
    if (parts.length < 2) return true;
    const sel = parts[0];
    const allowed = parts[1].split(',').map((s) => s.trim()).filter(Boolean);
    const node = document.querySelector(sel);
    if (!node) return true;
    const val = String(node.value || '');
    return allowed.indexOf(val) !== -1;
  }

  function applyBoxes() {
    collectBoxes().forEach((box) => {
      const id = box.getAttribute('data-rkrk-box');
      const cb = document.querySelector('[data-rkrk-box-for="' + id + '"]');
      let on = cb ? !!cb.checked : true;
      if (cb) {
        const allowed = boxAllowed(box);
        cb.disabled = !allowed;
        if (!allowed) on = false;
      } else if (!boxAllowed(box)) {
        on = false;
      }
      setBoxVisible(box, on);
    });
  }

  function ensureSetupUi() {
    const boxes = collectBoxes();
    if (!boxes.length) return;
    const host = document.querySelector('.map-wrap') || document.body;
    let btn = document.getElementById('mapSetupBtn');
    if (!btn) {
      btn = document.createElement('button');
      btn.id = 'mapSetupBtn';
      btn.type = 'button';
      btn.className = 'rkrk-setup-btn map-setup-btn';
      btn.title = 'Setup: Boxen ein-/ausblenden';
      btn.textContent = '⚙';
      host.appendChild(btn);
    }
    let panel = document.getElementById('mapSetupPanel');
    if (!panel) {
      panel = document.createElement('div');
      panel.id = 'mapSetupPanel';
      panel.className = 'rkrk-setup-panel map-setup-panel';
      panel.innerHTML = '<div class="title">Boxen anzeigen</div>';
      host.appendChild(panel);
    }
    boxes.forEach((box) => {
      const id = box.getAttribute('data-rkrk-box');
      if (!id) return;
      let cb = document.querySelector('[data-rkrk-box-for="' + id + '"]');
      if (!cb) {
        const label = document.createElement('label');
        const title = box.getAttribute('data-rkrk-box-label') || id;
        cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.setAttribute('data-rkrk-box-for', id);
        cb.checked = box.getAttribute('data-rkrk-box-default') !== 'off';
        label.appendChild(cb);
        label.appendChild(document.createTextNode(' ' + title));
        panel.appendChild(label);
      }
      if (!cb.dataset.rkrkBoxBound) {
        cb.dataset.rkrkBoxBound = '1';
        cb.addEventListener('change', applyBoxes);
      }
    });
    if (!btn.dataset.rkrkSetupBound) {
      btn.dataset.rkrkSetupBound = '1';
      btn.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        if (ev.stopImmediatePropagation) ev.stopImmediatePropagation();
        panel.classList.toggle('show');
      });
      document.addEventListener('click', (ev) => {
        if (!panel.classList.contains('show')) return;
        if (panel.contains(ev.target) || btn.contains(ev.target)) return;
        panel.classList.remove('show');
      });
    }
    const requiresHosts = {};
    boxes.forEach((box) => {
      const req = String(box.getAttribute('data-rkrk-box-requires') || '');
      if (!req) return;
      const sel = req.split(':')[0];
      if (sel) requiresHosts[sel] = true;
    });
    Object.keys(requiresHosts).forEach((sel) => {
      const node = document.querySelector(sel);
      if (!node || node.dataset.rkrkBoxReqBound) return;
      node.dataset.rkrkBoxReqBound = '1';
      node.addEventListener('change', applyBoxes);
    });
    applyBoxes();
  }

  function installSidebar() {
    const wrap = document.querySelector('.wrap');
    const panel = wrap ? wrap.querySelector(':scope > .left') : null;
    const mapWrap = wrap ? wrap.querySelector(':scope > .map-wrap') : null;
    if (!wrap || !panel || !mapWrap || panel.dataset.rkrkSidebarInstalled === '1') return;
    panel.dataset.rkrkSidebarInstalled = '1';
    document.body.classList.add('rkrk-has-sidebar');

    const STORAGE_WIDTH = 'rkrk_sidebar_width_px_v2_' + pageKey();
    const STORAGE_COLLAPSED = 'rkrk_sidebar_collapsed_v1_' + pageKey();
    const minW = 180;
    const maxW = 680;
    const NARROW = 720;
    function isNarrow() {
      return window.innerWidth <= NARROW;
    }
    function defaultMenuWidth() {
      const avail = Math.max(160, window.innerWidth - 96);
      const quarter = Math.round(window.innerWidth / 4);
      return Math.max(Math.min(minW, avail), Math.min(350, quarter, avail));
    }
    const defaultW = defaultMenuWidth();

    const handle = document.createElement('div');
    handle.id = 'rkrkSidebarResizeHandle';
    handle.title = 'Menuebreite anpassen';
    document.body.appendChild(handle);
    const toggleBtn = document.createElement('button');
    toggleBtn.id = 'rkrkSidebarToggleBtn';
    toggleBtn.type = 'button';
    document.body.appendChild(toggleBtn);

    function clamp(v) {
      const n = Number(v);
      const cap = Math.min(maxW, Math.max(120, window.innerWidth - 96));
      if (!Number.isFinite(n)) return Math.min(defaultMenuWidth(), cap);
      return Math.max(Math.min(minW, cap), Math.min(cap, Math.round(n)));
    }

    function applyLayout(width, collapsed) {
      const narrow = isNarrow();
      const hide = !!collapsed;
      const w = clamp(width);
      document.body.classList.toggle('rkrk-sidebar-collapsed', hide);
      document.body.classList.toggle('rkrk-sidebar-narrow', narrow);
      wrap.style.gridTemplateColumns = '1fr';
      if (hide) {
        handle.style.left = '0px';
        handle.style.display = 'none';
        toggleBtn.textContent = '>>';
        toggleBtn.title = 'Menue einblenden';
        toggleBtn.setAttribute('aria-label', 'Linkes Menue einblenden');
        toggleBtn.style.left = '0px';
      } else if (narrow) {
        handle.style.display = 'none';
        toggleBtn.textContent = '<<';
        toggleBtn.title = 'Menue ausblenden';
        toggleBtn.setAttribute('aria-label', 'Linkes Menue ausblenden');
        toggleBtn.style.left = (Math.min(w, Math.round(window.innerWidth * 0.88)) - 8) + 'px';
      } else {
        wrap.style.gridTemplateColumns = w + 'px 1fr';
        handle.style.display = '';
        handle.style.left = (w - 4) + 'px';
        toggleBtn.textContent = '<<';
        toggleBtn.title = 'Menue ausblenden';
        toggleBtn.setAttribute('aria-label', 'Linkes Menue ausblenden');
        toggleBtn.style.left = (w - 8) + 'px';
      }
      refreshMapSize(0);
    }

    function isMapPage() {
      try {
        return /rkrk_map\.php$/i.test(String((location && location.pathname) || ''));
      } catch (_) {
        return false;
      }
    }
    function sideStartsCollapsed() {
      if (!isMapPage()) return false;
      try {
        const side = new URLSearchParams(window.location.search).get('Side') || '';
        return /^(off|0|false|no)$/i.test(side);
      } catch (_) {
        return false;
      }
    }

    let width = clamp(localStorage.getItem(STORAGE_WIDTH) || defaultW);
    let collapsed = isMapPage()
      ? (sideStartsCollapsed() || isNarrow())
      : (isNarrow() || localStorage.getItem(STORAGE_COLLAPSED) === '1');
    let wasNarrow = isNarrow();
    applyLayout(width, collapsed);

    let dragActive = false;
    function onMove(evt) {
      if (!dragActive || collapsed) return;
      width = clamp(evt.clientX);
      applyLayout(width, false);
    }
    function onUp() {
      if (!dragActive) return;
      dragActive = false;
      try { localStorage.setItem(STORAGE_WIDTH, String(width)); } catch (_) { /* noop */ }
      window.removeEventListener('mousemove', onMove);
      window.removeEventListener('mouseup', onUp);
      document.body.style.userSelect = '';
    }
    handle.addEventListener('mousedown', (evt) => {
      if (collapsed) return;
      evt.preventDefault();
      dragActive = true;
      document.body.style.userSelect = 'none';
      window.addEventListener('mousemove', onMove);
      window.addEventListener('mouseup', onUp);
    });
    toggleBtn.addEventListener('click', () => {
      collapsed = !collapsed;
      if (!isNarrow() && !isMapPage()) {
        try { localStorage.setItem(STORAGE_COLLAPSED, collapsed ? '1' : '0'); } catch (_) { /* noop */ }
      }
      if (!collapsed) {
        width = clamp(localStorage.getItem(STORAGE_WIDTH) || width);
      }
      applyLayout(width, collapsed);
    });
    window.addEventListener('resize', () => {
      const narrow = isNarrow();
      if (narrow && !wasNarrow) collapsed = true;
      if (!narrow && wasNarrow) {
        collapsed = isMapPage() ? sideStartsCollapsed() : localStorage.getItem(STORAGE_COLLAPSED) === '1';
      } else if (!narrow && !isMapPage()) {
        collapsed = localStorage.getItem(STORAGE_COLLAPSED) === '1';
      }
      wasNarrow = narrow;
      applyLayout(width, collapsed);
    });
  }

  function boot() {
    if (isException()) return;
    installSidebar();
    ensureSetupUi();
    global.RkrkLayout = {
      applyBoxes: applyBoxes,
      refreshMapSize: refreshMapSize,
      isException: isException,
    };
    try {
      document.dispatchEvent(new Event('rkrk-layout-ready'));
    } catch (_) { /* noop */ }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})(window);

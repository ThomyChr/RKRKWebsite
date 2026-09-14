(function (global) {
  const STORAGE_W = 'rkrk_help_drawer_width_px';

  function catalog() {
    return global.RKRK_HELP_CATALOG || { pages: {}, views: {}, functions: [] };
  }

  function el(tag, attrs, html) {
    const n = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach((k) => {
        if (k === 'class') n.className = attrs[k];
        else if (k === 'text') n.textContent = attrs[k];
        else n.setAttribute(k, attrs[k]);
      });
    }
    if (html) n.innerHTML = html;
    return n;
  }

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function renderInputs(list) {
    const rows = Array.isArray(list) ? list : [];
    if (!rows.length) return '<p class="rkrk-help-empty">Keine Pflichtfelder fuer diese Funktion.</p>';
    return rows.map((i) => {
      const flag = i.required
        ? '<span class="req">noetig</span>'
        : '<span class="opt">optional</span>';
      return `<div class="rkrk-help-input"><div><strong>${escapeHtml(i.name)}</strong><div class="meta">${escapeHtml(i.where || '')}</div></div>${flag}</div>`;
    }).join('');
  }

  function matchQuery(item, q) {
    if (!q) return true;
    const hay = [
      item.title, item.what, item.id,
      ...(item.inputs || []).map((i) => `${i.name} ${i.where}`),
    ].join(' ').toLowerCase();
    return hay.includes(q);
  }

  function functionsFor(page, viewId, q) {
    const fns = catalog().functions || [];
    return fns.filter((fn) => {
      if (page && Array.isArray(fn.pages) && fn.pages.length && !fn.pages.includes(page)) return false;
      if (viewId && Array.isArray(fn.views) && fn.views.length && !fn.views.includes(viewId)) {
        // still allow in "all" tab; current tab filters
      }
      return matchQuery(fn, q);
    });
  }

  const RkrkHelp = {
    page: 'editor',
    getView: () => 'editor.overview',
    tab: 'current',
    open: false,
    _root: null,

    mount(opts) {
      const o = opts || {};
      this.page = o.page || 'editor';
      if (typeof o.getView === 'function') this.getView = o.getView;
      this._ensureDom();
      if (!this._delegated) {
        this._delegated = true;
        document.addEventListener('click', (ev) => {
          const btn = ev.target && ev.target.closest && ev.target.closest('[data-rkrk-help]');
          if (!btn) return;
          ev.preventDefault();
          this.toggle();
        });
      }
      if (o.trigger && !o.trigger.dataset.rkrkHelp) {
        o.trigger.setAttribute('data-rkrk-help', '1');
      }
      document.addEventListener('keydown', (ev) => {
        const t = ev.target;
        const typing = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable);
        if (ev.key === 'Escape' && this.open) {
          this.close();
          return;
        }
        if (!typing && (ev.key === '?' || ev.key === 'F1')) {
          ev.preventDefault();
          this.toggle();
        }
      });
      this.render();
      return this;
    },

    currentViewId() {
      try {
        return this.getView() || `${this.page}.overview`;
      } catch (e) {
        return `${this.page}.overview`;
      }
    },

    setView() {
      if (this.open && this.tab === 'current') this.render();
    },

    toggle() {
      if (this.open) this.close();
      else this.show();
    },

    show() {
      this._ensureDom();
      this.open = true;
      this._root.backdrop.classList.add('show');
      this._root.drawer.classList.add('show');
      document.querySelectorAll('[data-rkrk-help], #mapHelpBtn').forEach((n) => {
        n.setAttribute('aria-expanded', 'true');
      });
      this.render();
      const q = this._root.search;
      if (q && window.matchMedia('(min-width: 721px)').matches) q.focus();
    },

    close() {
      this.open = false;
      if (!this._root) return;
      this._root.backdrop.classList.remove('show');
      this._root.drawer.classList.remove('show');
      document.querySelectorAll('[data-rkrk-help], #mapHelpBtn').forEach((n) => {
        n.setAttribute('aria-expanded', 'false');
      });
    },

    _ensureDom() {
      if (this._root) return;
      const backdrop = el('div', { class: 'rkrk-help-backdrop', 'aria-hidden': 'true' });
      const drawer = el('div', { class: 'rkrk-help-drawer', role: 'dialog', 'aria-label': 'Hilfe' });
      const savedW = Number(localStorage.getItem(STORAGE_W) || 0);
      if (Number.isFinite(savedW) && savedW >= 320 && savedW <= 720) {
        drawer.style.setProperty('--rkrk-help-width', `${savedW}px`);
        drawer.style.width = `${savedW}px`;
      }
      drawer.innerHTML = `
        <div class="rkrk-help-resize" title="Breite ziehen"></div>
        <div class="rkrk-help-head">
          <h2>Hilfe</h2>
          <a href="rkrk_help.php" target="_blank" rel="noopener" style="font-size:12px;color:#1f78d1;">Katalog</a>
          <button type="button" data-close title="Schliessen">✕</button>
        </div>
        <div class="rkrk-help-tabs">
          <button type="button" data-tab="current" class="active">Aktuelle Sicht</button>
          <button type="button" data-tab="all">Alle Funktionen</button>
        </div>
        <div class="rkrk-help-search"><input type="search" placeholder="Funktion oder Eingabe suchen..." /></div>
        <div class="rkrk-help-body"></div>
      `;
      document.body.appendChild(backdrop);
      document.body.appendChild(drawer);
      backdrop.addEventListener('click', () => this.close());
      drawer.querySelector('[data-close]').addEventListener('click', () => this.close());
      drawer.querySelectorAll('[data-tab]').forEach((b) => {
        b.addEventListener('click', () => {
          this.tab = b.getAttribute('data-tab') || 'current';
          drawer.querySelectorAll('[data-tab]').forEach((x) => x.classList.toggle('active', x === b));
          this.render();
        });
      });
      const search = drawer.querySelector('input');
      search.addEventListener('input', () => this.render());
      const handle = drawer.querySelector('.rkrk-help-resize');
      let dragging = false;
      handle.addEventListener('mousedown', (ev) => {
        dragging = true;
        ev.preventDefault();
      });
      document.addEventListener('mousemove', (ev) => {
        if (!dragging) return;
        const w = Math.min(720, Math.max(320, window.innerWidth - ev.clientX));
        drawer.style.width = `${w}px`;
        drawer.style.setProperty('--rkrk-help-width', `${w}px`);
      });
      document.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false;
        const w = parseInt(drawer.style.width, 10);
        if (Number.isFinite(w)) localStorage.setItem(STORAGE_W, String(w));
      });
      this._root = { backdrop, drawer, body: drawer.querySelector('.rkrk-help-body'), search };
    },

    renderFn(fn) {
      return `<article class="rkrk-help-fn">
        <h3>${escapeHtml(fn.title)}</h3>
        <div class="meta">${escapeHtml((fn.pages || []).join(', '))}</div>
        <p>${escapeHtml(fn.what)}</p>
        <h4>Eingaben</h4>
        ${renderInputs(fn.inputs)}
      </article>`;
    },

    render() {
      if (!this._root) return;
      const cat = catalog();
      const q = String(this._root.search.value || '').trim().toLowerCase();
      const viewId = this.currentViewId();
      const view = cat.views[viewId] || cat.views[`${this.page}.overview`] || {};
      const pageMeta = cat.pages[this.page] || {};

      if (this.tab === 'current') {
        const fns = (cat.functions || []).filter((fn) => {
          const inPage = !fn.pages || fn.pages.includes(this.page);
          const inView = !fn.views || fn.views.includes(viewId) || fn.views.includes(`${this.page}.overview`);
          return inPage && inView && matchQuery(fn, q);
        });
        this._root.body.innerHTML = `
          <span class="rkrk-help-view-badge">${escapeHtml(view.title || viewId)}</span>
          <div class="rkrk-help-card">
            <h3>${escapeHtml(pageMeta.title || this.page)} · ${escapeHtml(view.title || '')}</h3>
            <p>${escapeHtml(view.use || pageMeta.summary || '')}</p>
            <h4>Noetige Inputs fuer diese Sicht</h4>
            ${renderInputs(view.inputs)}
          </div>
          <h4 style="margin:4px 0 8px;color:#5b6f86;font-size:12px;text-transform:uppercase;">Funktionen dieser Sicht</h4>
          ${fns.length ? fns.map((f) => this.renderFn(f)).join('') : '<p class="rkrk-help-empty">Keine Treffer in dieser Sicht.</p>'}
        `;
        return;
      }

      const fns = functionsFor(this.page, null, q);
      this._root.body.innerHTML = `
        <div class="rkrk-help-card">
          <h3>Alle Funktionen · ${escapeHtml(pageMeta.title || '')}</h3>
          <p>${escapeHtml(pageMeta.summary || '')} Tastatur: <strong>F1</strong> oder <strong>?</strong> oeffnet die Hilfe, <strong>Esc</strong> schliesst.</p>
        </div>
        ${fns.length ? fns.map((f) => this.renderFn(f)).join('') : '<p class="rkrk-help-empty">Keine Treffer.</p>'}
      `;
    },

    renderStandalone(target) {
      const host = typeof target === 'string' ? document.querySelector(target) : target;
      if (!host) return;
      const cat = catalog();
      const parts = ['<div class="rkrk-help-page">'];
      parts.push('<h1>RKRK Hilfe</h1>');
      Object.keys(cat.pages || {}).forEach((pid) => {
        const p = cat.pages[pid];
        parts.push(`<h2>${escapeHtml(p.title)}</h2><p>${escapeHtml(p.summary)}</p>`);
        Object.keys(cat.views || {}).forEach((vid) => {
          const v = cat.views[vid];
          if (v.page !== pid) return;
          parts.push(`<div class="rkrk-help-card"><h3>Sicht: ${escapeHtml(v.title)}</h3><p>${escapeHtml(v.use)}</p><h4>Inputs</h4>${renderInputs(v.inputs)}</div>`);
        });
      });
      parts.push('<h2>Funktionskatalog</h2>');
      (cat.functions || []).forEach((fn) => parts.push(this.renderFn(fn)));
      parts.push('</div>');
      host.innerHTML = parts.join('');
    },
  };

  global.RkrkHelp = RkrkHelp;
})(window);

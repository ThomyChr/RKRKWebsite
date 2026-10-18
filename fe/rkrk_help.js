(function (global) {
  const STORAGE_W = 'rkrk_help_drawer_width_px_v2';
  const WIDTH_MIN = 448;
  const WIDTH_MAX = 840;

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

  function pageLabel(pid) {
    const p = (catalog().pages || {})[pid] || {};
    return p.label || p.title || pid || '';
  }

  function surfaceLabel(page) {
    if (page === 'editor') return 'DESK';
    if (page === 'rc') return 'MOB';
    return pageLabel(page);
  }

  function shortViewTitle(title) {
    return String(title || '').replace(/^\s*\d+\s*(?:[·•]\s*|-\s+)?/, '').trim();
  }

  function sentences(text) {
    return String(text || '').split(/(?<=\.)\s+/).map((s) => s.trim()).filter(Boolean);
  }

  function sentencePage(s) {
    const desk = /\bDESK\b/.test(s);
    const mob = /\bMOB\b/.test(s);
    if (desk && !mob) return 'editor';
    if (mob && !desk) return 'rc';
    return '';
  }

  function textForPage(text, page) {
    const raw = String(text || '');
    if (page !== 'editor' && page !== 'rc') return raw;
    const other = page === 'editor' ? 'rc' : 'editor';
    const parts = sentences(raw);
    if (!parts.length) return raw;
    return parts.filter((s) => sentencePage(s) !== other).join(' ');
  }

  function imagePage(img) {
    const src = String(img && img.src || '').toLowerCase();
    const desk = src.includes('-desk');
    const mob = src.includes('-mob');
    if (desk && !mob) return 'editor';
    if (mob && !desk) return 'rc';
    return '';
  }

  function imageForPage(img, page) {
    const owner = imagePage(img);
    if (!owner || (page !== 'editor' && page !== 'rc')) return true;
    return owner === page;
  }

  function helpImages(item) {
    const list = [];
    (Array.isArray(item && item.images) ? item.images : []).forEach((img) => {
      if (!img || !img.src) return;
      list.push({ src: String(img.src), alt: String(img.alt || img.caption || ''), caption: String(img.caption || '') });
    });
    const re = /!\[([^\]]*)\]\(([^)\s]+)\)/g;
    let match;
    const what = String(item && item.what || '');
    while ((match = re.exec(what))) {
      list.push({ src: match[2], alt: match[1] || '', caption: match[1] || '' });
    }
    return list;
  }

  function renderInputs(list, page) {
    const rows = (Array.isArray(list) ? list : []).filter((i) => {
      const blob = `${i && i.name || ''} ${i && i.where || ''}`;
      return sentencePage(blob) !== (page === 'editor' ? 'rc' : page === 'rc' ? 'editor' : 'nomatch');
    });
    if (!rows.length) return '<p class="rkrk-help-empty">Keine Pflichtfelder fuer diese Funktion.</p>';
    return rows.map((i) => {
      const flag = i.required
        ? '<span class="req">noetig</span>'
        : '<span class="opt">optional</span>';
      return `<div class="rkrk-help-input"><div><strong>${escapeHtml(i.name)}</strong><div class="meta">${escapeHtml(i.where || '')}</div></div>${flag}</div>`;
    }).join('');
  }

  function renderImages(item, page) {
    return helpImages(item).filter((img) => imageForPage(img, page)).map((img) => {
      const cap = img.caption ? `<figcaption>${escapeHtml(img.caption)}</figcaption>` : '';
      return `<figure class="rkrk-help-fig"><img src="${escapeHtml(img.src)}" alt="${escapeHtml(img.alt)}" />${cap}</figure>`;
    }).join('');
  }

  function proseForPage(item, page) {
    const raw = String(item && item.what || '').replace(/!\[[^\]]*\]\([^)\s]+\)/g, ' ');
    return textForPage(raw, page).replace(/\s+/g, ' ').trim();
  }

  function matchQuery(item, q) {
    if (!q) return true;
    const hay = [
      item.title, item.what, item.id,
      ...(item.inputs || []).map((i) => `${i.name} ${i.where}`),
      ...helpImages(item).map((img) => `${img.alt} ${img.caption}`),
    ].join(' ').toLowerCase();
    return hay.includes(q);
  }

  function foreignEditorId(id, page) {
    if (page === 'editor') return id.startsWith('rc.');
    if (page === 'rc') return id.startsWith('editor.') || id.startsWith('analyse.') || id.startsWith('nav.');
    if (page === 'analyse') return id.startsWith('rc.');
    return false;
  }

  function fnOwnedByPage(fn, page) {
    const pages = Array.isArray(fn.pages) ? fn.pages : [];
    if (pages.length && !pages.includes(page)) return false;
    return !foreignEditorId(String(fn.id || ''), page);
  }

  function fnOnFullPage(fn, page) {
    return fnOwnedByPage(fn, page);
  }

  function fnMatchesView(fn, page, viewId) {
    if (!fnOwnedByPage(fn, page)) return false;
    const views = Array.isArray(fn.views) ? fn.views : [];
    const own = views.filter((v) => String(v) === viewId || String(v).startsWith(`${page}.`));
    if (!views.length) return viewId === `${page}.overview`;
    if (!own.length) return false;
    return own.includes(viewId);
  }

  function viewRecord(page, viewId) {
    const views = catalog().views || {};
    const view = views[viewId];
    if (view && (view.page === page || String(viewId).startsWith(`${page}.`))) return view;
    return null;
  }

  function deskStepForView(viewId) {
    const fold = document.querySelector('.plan-fold.is-active');
    const phase = fold ? String(fold.getAttribute('data-plan-phase') || '') : '';
    const phaseView = {
      outline: 'editor.draw',
      start: 'editor.start',
      optimize: 'editor.edit_path',
      waypoints: 'editor.waypoint',
      place: 'editor.fielddata',
      finish: 'editor.fielddata',
    }[phase] || '';
    const phaseTitle = {
      outline: '1 · Fläche',
      start: '2 · Start',
      optimize: '3 · Flugpfad',
      waypoints: '4 - Prüfen',
      place: '5 · Speichern',
      finish: '6 · Daten',
    }[phase] || '';
    if (!phaseTitle || !phaseView) return '';
    if (phaseView === viewId) return phaseTitle;
    if (phase === 'optimize' && (viewId === 'editor.coverage' || viewId === 'editor.mission')) return phaseTitle;
    return '';
  }

  function mobStepForView(viewId) {
    return {
      'rc.list': 'Feldliste',
      'rc.ring': 'Fläche',
      'rc.start': 'Start',
      'rc.route': 'Flugpfad',
      'rc.wp': 'Wegpunkt',
      'rc.name': 'Speichern',
      'rc.check': 'Daten',
      'rc.field': 'Feld ansehen',
      'rc.drone': 'Drohne',
    }[viewId] || '';
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
      let id = '';
      try {
        id = String(this.getView() || '');
      } catch (e) {
        id = '';
      }
      if (!id || (id !== this.page && !id.startsWith(`${this.page}.`))) {
        return `${this.page}.overview`;
      }
      return id;
    },

    stepHeading(view, viewId) {
      const kind = surfaceLabel(this.page);
      const live = this.page === 'editor' ? deskStepForView(viewId) : (this.page === 'rc' ? mobStepForView(viewId) : '');
      const step = shortViewTitle(live || (view && view.title) || viewId);
      return `${kind} · ${step}`;
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
      if (Number.isFinite(savedW) && savedW >= WIDTH_MIN && savedW <= WIDTH_MAX) {
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
        const w = Math.min(WIDTH_MAX, Math.max(WIDTH_MIN, window.innerWidth - ev.clientX));
        drawer.style.width = `${w}px`;
        drawer.style.setProperty('--rkrk-help-width', `${w}px`);
      });
      document.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false;
        const w = parseInt(drawer.style.width, 10);
        if (Number.isFinite(w)) localStorage.setItem(STORAGE_W, String(w));
      });
      this._root = {
        backdrop,
        drawer,
        body: drawer.querySelector('.rkrk-help-body'),
        search,
        title: drawer.querySelector('.rkrk-help-head h2'),
        catalogLink: drawer.querySelector('.rkrk-help-head a'),
      };
    },

    renderFn(fn, page) {
      const pid = page || this.page;
      const labels = (fn.pages || []).filter((p) => p === pid || (pid !== 'editor' && pid !== 'rc')).map(surfaceLabel);
      const shown = labels.length ? labels : [pageLabel(pid)];
      const what = proseForPage(fn, pid);
      return `<article class="rkrk-help-fn">
        <h3>${escapeHtml(fn.title)}</h3>
        <div class="meta">${escapeHtml(shown.join(', '))}</div>
        <p>${escapeHtml(what)}</p>
        ${renderImages(fn, pid)}
        <h4>Eingaben</h4>
        ${renderInputs(fn.inputs, pid)}
      </article>`;
    },

    render() {
      if (!this._root) return;
      const cat = catalog();
      const q = String(this._root.search.value || '').trim().toLowerCase();
      const viewId = this.currentViewId();
      const view = viewRecord(this.page, viewId) || {};
      const pageMeta = cat.pages[this.page] || {};
      const heading = this.stepHeading(view, viewId);
      const use = textForPage(view.use || '', this.page);
      if (this._root.catalogLink) {
        this._root.catalogLink.href = `rkrk_help.php?page=${encodeURIComponent(this.page)}&view=${encodeURIComponent(viewId)}`;
      }

      if (this.tab === 'current') {
        if (this._root.title) this._root.title.textContent = heading;
        const fns = (cat.functions || []).filter((fn) => fnMatchesView(fn, this.page, viewId) && matchQuery(fn, q));
        this._root.body.innerHTML = `
          <span class="rkrk-help-view-badge">${escapeHtml(heading)}</span>
          <div class="rkrk-help-card">
            <h3>${escapeHtml(heading)}</h3>
            <p>${escapeHtml(use || pageMeta.summary || '')}</p>
            <h4>Noetige Inputs fuer diese Sicht</h4>
            ${renderInputs(view.inputs, this.page)}
          </div>
          <h4 style="margin:4px 0 8px;color:#5b6f86;font-size:12px;text-transform:uppercase;">Funktionen dieser Sicht</h4>
          ${fns.length ? fns.map((f) => this.renderFn(f, this.page)).join('') : '<p class="rkrk-help-empty">Keine Treffer in dieser Sicht.</p>'}
        `;
        return;
      }

      if (this._root.title) this._root.title.textContent = `${surfaceLabel(this.page)} · Alle Funktionen`;
      const fns = (cat.functions || []).filter((fn) => fnOwnedByPage(fn, this.page) && matchQuery(fn, q));
      this._root.body.innerHTML = `
        <div class="rkrk-help-card">
          <h3>Alle Funktionen · ${escapeHtml(surfaceLabel(this.page))}</h3>
          <p>${escapeHtml(textForPage(pageMeta.summary || '', this.page))} Tastatur: <strong>F1</strong> oder <strong>?</strong> oeffnet die Hilfe, <strong>Esc</strong> schliesst.</p>
        </div>
        ${fns.length ? fns.map((f) => this.renderFn(f, this.page)).join('') : '<p class="rkrk-help-empty">Keine Treffer.</p>'}
      `;
    },

    renderStandalone(target, opts) {
      const host = typeof target === 'string' ? document.querySelector(target) : target;
      if (!host) return;
      const cat = catalog();
      const focus = opts || {};
      const focusView = String(focus.view || '');
      const order = ['editor', 'rc', 'analyse'];
      const pages = order.concat(Object.keys(cat.pages || {}).filter((pid) => !order.includes(pid)));
      const parts = ['<div class="rkrk-help-page">', '<h1>RKRK Hilfe</h1>'];
      pages.forEach((pid) => {
        const p = (cat.pages || {})[pid];
        if (!p) return;
        const label = surfaceLabel(pid);
        const head = (label === 'DESK' || label === 'MOB') ? label : (p.title || label);
        parts.push(`<h2 id="help-page-${escapeHtml(pid)}">${escapeHtml(head)}</h2><p>${escapeHtml(textForPage(p.summary || '', pid))}</p>`);
        Object.keys(cat.views || {}).forEach((vid) => {
          const v = cat.views[vid];
          if (!v || v.page !== pid) return;
          const mark = vid === focusView ? ' is-current' : '';
          const style = vid === focusView ? ' style="outline:2px solid #1f78d1;background:#f3f8ff"' : '';
          const viewTitle = shortViewTitle(v.title);
          parts.push(`<div class="rkrk-help-card${mark}"${style} id="help-view-${escapeHtml(vid)}" data-help-view="${escapeHtml(vid)}"><h3>${escapeHtml(label)} · ${escapeHtml(viewTitle)}</h3><p>${escapeHtml(textForPage(v.use || '', pid))}</p><h4>Inputs</h4>${renderInputs(v.inputs, pid)}</div>`);
        });
        const fns = (cat.functions || []).filter((fn) => fnOnFullPage(fn, pid));
        if (!fns.length) return;
        parts.push(`<h3>Funktionen · ${escapeHtml(label)}</h3>`);
        fns.forEach((fn) => parts.push(this.renderFn(fn, pid)));
      });
      parts.push('</div>');
      host.innerHTML = parts.join('');
      if (!focusView) return;
      const node = host.querySelector(`[data-help-view="${focusView.replace(/"/g, '')}"]`);
      if (node && node.scrollIntoView) node.scrollIntoView({ block: 'start' });
    },
  };

  global.RkrkHelp = RkrkHelp;
})(window);

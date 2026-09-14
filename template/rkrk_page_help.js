(function (global) {
  function catalog() {
    return global.RKRK_HELP_CATALOG || { pages: {}, views: {}, functions: [], icons: {} };
  }

  function storageKey() {
    return catalog().storageKey || "rkrk_page_help_drawer_width_px";
  }

  function el(tag, attrs, html) {
    const n = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach((k) => {
        if (k === "class") n.className = attrs[k];
        else if (k === "text") n.textContent = attrs[k];
        else n.setAttribute(k, attrs[k]);
      });
    }
    if (html) n.innerHTML = html;
    return n;
  }

  function escapeHtml(s) {
    return String(s || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  function iconHtml(key) {
    const svg = (catalog().icons || {})[key];
    if (!svg) return "";
    return `<span class="rkrk-help-icon" aria-hidden="true">${svg}</span>`;
  }

  function glyphHtml(key) {
    const svg = (catalog().icons || {})[key];
    if (!svg) return "";
    return `<span class="rkrk-help-tool-glyph" aria-hidden="true">${svg}</span>`;
  }

  function renderInputs(list) {
    const rows = Array.isArray(list) ? list : [];
    if (!rows.length) return "";
    return rows.map((i) => {
      const flag = i.required
        ? '<span class="req">nötig</span>'
        : '<span class="opt">optional</span>';
      return `<div class="rkrk-help-input"><div><strong>${escapeHtml(i.name)}</strong><div class="meta">${escapeHtml(i.where || "")}</div></div>${flag}</div>`;
    }).join("");
  }

  function renderSteps(list) {
    const rows = Array.isArray(list) ? list : [];
    if (!rows.length) return "";
    return `<ol class="rkrk-help-steps">${rows.map((s) => {
      const text = typeof s === "string" ? s : (s && s.text) || "";
      const icon = typeof s === "string" ? "" : (s && s.icon) || "";
      return `<li>${icon ? glyphHtml(icon) : '<span></span>'}<span>${escapeHtml(text)}</span></li>`;
    }).join("")}</ol>`;
  }

  function renderTools(list) {
    const rows = Array.isArray(list) ? list : [];
    if (!rows.length) return "";
    return `<div class="rkrk-help-tools">${rows.map((t) => `
      <div class="rkrk-help-tool">
        ${glyphHtml(t.icon)}
        <div><strong>${escapeHtml(t.name || "")}</strong><p>${escapeHtml(t.text || "")}</p></div>
      </div>`).join("")}</div>`;
  }

  function matchQuery(item, q) {
    if (!q) return true;
    const hay = [
      item.title, item.what, item.id, item.use,
      ...(item.inputs || []).map((i) => `${i.name} ${i.where}`),
      ...(item.tools || []).map((t) => `${t.name} ${t.text}`),
      ...(item.steps || []).map((s) => (typeof s === "string" ? s : s.text)),
    ].join(" ").toLowerCase();
    return hay.includes(q);
  }

  function schulungHref() {
    const cat = catalog();
    return String(cat.schulung || (cat.pages && cat.pages[RkrkHelp.page] && cat.pages[RkrkHelp.page].schulung) || "");
  }

  function katalogHref() {
    return String(catalog().standaloneUrl || "");
  }

  function schulungBlock() {
    const pdf = schulungHref();
    if (!pdf) return "";
    return `<p><a class="rkrk-help-schulung" href="${escapeHtml(pdf)}" target="_blank" rel="noopener">${iconHtml("pdf")} Schulung (PDF)</a></p>`;
  }

  const RkrkHelp = {
    page: "page",
    getView: () => "page.overview",
    getRole: () => "admin",
    tab: "current",
    open: false,
    _root: null,

    mount(opts) {
      const o = opts || {};
      this.page = o.page || "page";
      if (typeof o.getView === "function") this.getView = o.getView;
      if (typeof o.getRole === "function") this.getRole = o.getRole;
      this._ensureDom();
      if (!this._delegated) {
        this._delegated = true;
        document.addEventListener("click", (ev) => {
          const btn = ev.target && ev.target.closest && ev.target.closest("[data-rkrk-help]");
          if (!btn) return;
          ev.preventDefault();
          this.toggle();
        });
      }
      if (o.trigger) {
        o.trigger.setAttribute("data-rkrk-help", "1");
        o.trigger.classList.add("rkrk-help-fab");
      }
      if (!this._keys) {
        this._keys = true;
        document.addEventListener("keydown", (ev) => {
          const t = ev.target;
          const typing = t && (t.tagName === "INPUT" || t.tagName === "TEXTAREA" || t.tagName === "SELECT" || t.isContentEditable);
          if (ev.key === "Escape" && this.open) {
            this.close();
            return;
          }
          if (!typing && (ev.key === "?" || ev.key === "F1")) {
            ev.preventDefault();
            this.toggle();
          }
        });
      }
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

    currentRole() {
      try {
        return this.getRole() || "admin";
      } catch (e) {
        return "admin";
      }
    },

    roleMatches(fn, role) {
      const roles = fn && fn.roles;
      if (!roles || !roles.length) return true;
      return roles.includes(String(role || "admin"));
    },

    setView() {
      if (this.open && this.tab === "current") this.render();
    },

    toggle() {
      if (this.open) this.close();
      else this.show();
    },

    show() {
      this._ensureDom();
      this.open = true;
      this._root.backdrop.classList.add("show");
      this._root.drawer.classList.add("show");
      document.querySelectorAll("[data-rkrk-help], #mapHelpBtn").forEach((n) => {
        n.setAttribute("aria-expanded", "true");
      });
      this.render();
      const q = this._root.search;
      if (q && window.matchMedia("(min-width: 721px)").matches) q.focus();
    },

    close() {
      this.open = false;
      if (!this._root) return;
      this._root.backdrop.classList.remove("show");
      this._root.drawer.classList.remove("show");
      document.querySelectorAll("[data-rkrk-help], #mapHelpBtn").forEach((n) => {
        n.setAttribute("aria-expanded", "false");
      });
    },

    _ensureDom() {
      if (this._root) return;
      const backdrop = el("div", { class: "rkrk-help-backdrop", "aria-hidden": "true" });
      const drawer = el("div", { class: "rkrk-help-drawer", role: "dialog", "aria-label": "Hilfe" });
      const savedW = Number(localStorage.getItem(storageKey()) || 0);
      if (Number.isFinite(savedW) && savedW >= 320 && savedW <= 720) {
        drawer.style.setProperty("--rkrk-help-width", `${savedW}px`);
        drawer.style.width = `${savedW}px`;
      }
      const kat = katalogHref();
      drawer.innerHTML = `
        <div class="rkrk-help-resize" title="Breite ziehen"></div>
        <div class="rkrk-help-head">
          <h2>Hilfe</h2>
          ${kat ? `<a href="${escapeHtml(kat)}" target="_blank" rel="noopener">Katalog</a>` : ""}
          <button type="button" data-close title="Schliessen">✕</button>
        </div>
        <div class="rkrk-help-tabs">
          <button type="button" data-tab="current" class="active">Aktuelle Sicht</button>
          <button type="button" data-tab="all">Alle Werkzeuge</button>
        </div>
        <div class="rkrk-help-search"><input type="search" placeholder="Werkzeug oder Schritt suchen..." /></div>
        <div class="rkrk-help-body"></div>
      `;
      document.body.appendChild(backdrop);
      document.body.appendChild(drawer);
      backdrop.addEventListener("click", () => this.close());
      drawer.querySelector("[data-close]").addEventListener("click", () => this.close());
      drawer.querySelectorAll("[data-tab]").forEach((b) => {
        b.addEventListener("click", () => {
          this.tab = b.getAttribute("data-tab") || "current";
          drawer.querySelectorAll("[data-tab]").forEach((x) => x.classList.toggle("active", x === b));
          this.render();
        });
      });
      const search = drawer.querySelector("input");
      search.addEventListener("input", () => this.render());
      const handle = drawer.querySelector(".rkrk-help-resize");
      let dragging = false;
      handle.addEventListener("mousedown", (ev) => {
        dragging = true;
        ev.preventDefault();
      });
      document.addEventListener("mousemove", (ev) => {
        if (!dragging) return;
        const w = Math.min(720, Math.max(320, window.innerWidth - ev.clientX));
        drawer.style.width = `${w}px`;
        drawer.style.setProperty("--rkrk-help-width", `${w}px`);
      });
      document.addEventListener("mouseup", () => {
        if (!dragging) return;
        dragging = false;
        const w = parseInt(drawer.style.width, 10);
        if (Number.isFinite(w)) localStorage.setItem(storageKey(), String(w));
      });
      this._root = { backdrop, drawer, body: drawer.querySelector(".rkrk-help-body"), search };
    },

    renderFn(fn) {
      const tools = renderTools(fn.tools);
      const steps = renderSteps(fn.steps);
      const inputs = renderInputs(fn.inputs);
      return `<article class="rkrk-help-fn">
        <h3>${iconHtml(fn.icon)}${escapeHtml(fn.title)}</h3>
        <p>${escapeHtml(fn.what)}</p>
        ${tools}
        ${steps}
        ${inputs ? `<h4>Eingaben</h4>${inputs}` : ""}
      </article>`;
    },

    render() {
      if (!this._root) return;
      const cat = catalog();
      const q = String(this._root.search.value || "").trim().toLowerCase();
      const viewId = this.currentViewId();
      const view = cat.views[viewId] || cat.views[`${this.page}.overview`] || {};
      const pageMeta = cat.pages[this.page] || {};

      if (this.tab === "current") {
        const role = this.currentRole();
        const fns = (cat.functions || []).filter((fn) => {
          if (!this.roleMatches(fn, role)) return false;
          const inPage = !fn.pages || !fn.pages.length || fn.pages.includes(this.page);
          const views = fn.views;
          const always = !views || !views.length;
          const inView = always || views.includes(viewId) || views.includes(`${this.page}.overview`);
          return inPage && inView && matchQuery(fn, q);
        });
        const viewTools = renderTools(view.tools);
        const viewSteps = renderSteps(view.steps);
        const viewInputs = renderInputs(view.inputs);
        this._root.body.innerHTML = `
          <span class="rkrk-help-view-badge">${iconHtml(view.icon)}${escapeHtml(view.title || viewId)}</span>
          <div class="rkrk-help-card">
            <h3>${iconHtml(view.icon)}${escapeHtml(view.title || "")}</h3>
            <p>${escapeHtml(view.use || pageMeta.summary || "")}</p>
            ${viewTools ? `<h4>Werkzeuge dieser Sicht</h4>${viewTools}` : ""}
            ${viewSteps ? `<h4>So geht's</h4>${viewSteps}` : ""}
            ${viewInputs ? `<h4>Was Sie brauchen</h4>${viewInputs}` : ""}
          </div>
          ${fns.length ? `<h4 style="margin:4px 0 8px;color:#5b6f86;font-size:12px;text-transform:uppercase;">Weitere Funktionen</h4>${fns.map((f) => this.renderFn(f)).join("")}` : ""}
          ${schulungBlock()}
        `;
        return;
      }

      const role = this.currentRole();
      const fns = (catalog().functions || []).filter((fn) => {
        if (!this.roleMatches(fn, role)) return false;
        if (this.page && Array.isArray(fn.pages) && fn.pages.length && !fn.pages.includes(this.page)) return false;
        return matchQuery(fn, q);
      });
      this._root.body.innerHTML = `
        <div class="rkrk-help-card">
          <h3>Alle Werkzeuge · ${escapeHtml(pageMeta.title || "")}</h3>
          <p>${escapeHtml(pageMeta.summary || "")} Taste <strong>F1</strong> oder <strong>?</strong> öffnet die Hilfe, <strong>Esc</strong> schliesst.</p>
        </div>
        ${fns.length ? fns.map((f) => this.renderFn(f)).join("") : '<p class="rkrk-help-empty">Keine Treffer.</p>'}
        ${schulungBlock()}
      `;
    },
  };

  global.RkrkHelp = RkrkHelp;
})(window);

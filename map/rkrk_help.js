(function (global) {
  const STORAGE_W = "rkrk_help_drawer_width_px";

  function catalog() {
    return global.RKRK_HELP_CATALOG || { pages: {}, views: {}, functions: [], icons: {} };
  }

  function productTitle() {
    try {
      if (typeof global.rkrOverlayEditorTitle === "function") {
        const t = global.rkrOverlayEditorTitle();
        if (t) return String(t);
      }
    } catch (_) { /* noop */ }
    return "Overlay-Editor";
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

  function hasUrlUnlock(flags) {
    if (!flags || !flags.length) return true;
    try {
      const u = new URL(window.location.href);
      const code = String(u.searchParams.get("code") || "").trim().toUpperCase();
      return flags.some((f) => {
        const key = String(f || "").toUpperCase();
        return u.searchParams.has(key) || u.searchParams.has(String(f)) || code === key;
      });
    } catch (_) {
      return false;
    }
  }

  function visibleTools(list) {
    return (Array.isArray(list) ? list : []).filter((t) => hasUrlUnlock(t && t.urlUnlock));
  }

  function functionWhat(fn) {
    const base = String((fn && fn.what) || "");
    if (fn && fn.whatUnlock && hasUrlUnlock(fn.urlUnlock || ["ADMIN", "HUB"])) {
      return `${base} ${fn.whatUnlock}`.trim();
    }
    return base;
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
      if (typeof s === "string") {
        return `<li><span></span><span>${escapeHtml(s)}</span></li>`;
      }
      return `<li>${iconHtml(s.icon)}<span>${escapeHtml(s.text || "")}</span></li>`;
    }).join("")}</ol>`;
  }

  function renderTools(list) {
    const rows = visibleTools(list);
    if (!rows.length) return "";
    return `<div class="rkrk-help-tools">${rows.map((t) => `
      <div class="rkrk-help-tool">
        ${glyphHtml(t.icon)}
        <div>
          <strong>${escapeHtml(t.name || "")}</strong>
          <p>${escapeHtml(t.text || "")}</p>
        </div>
      </div>`).join("")}</div>`;
  }

  function matchQuery(item, q) {
    if (!q) return true;
    const hay = [
      item.title, item.what, item.id, item.name, item.text, item.use,
      ...(item.inputs || []).map((i) => `${i.name} ${i.where}`),
      ...visibleTools(item.tools || []).map((t) => `${t.name} ${t.text}`),
      ...(item.steps || []).map((s) => (typeof s === "string" ? s : s.text)),
    ].join(" ").toLowerCase();
    return hay.includes(q);
  }

  function functionsFor(page, viewId, q) {
    const fns = catalog().functions || [];
    return fns.filter((fn) => {
      if (page && Array.isArray(fn.pages) && fn.pages.length && !fn.pages.includes(page)) return false;
      if (viewId && Array.isArray(fn.views) && fn.views.length && !fn.views.includes(viewId)) return false;
      return matchQuery(fn, q);
    });
  }

  function schulungHref() {
    try {
      const u = new URL(window.location.href);
      const kos = u.searchParams.has("KOS")
        || String(u.searchParams.get("code") || "").trim().toUpperCase() === "KOS";
      return kos
        ? "help/202608%20RKRSplus%20Overlay%20Editor%20-%20KOS.pdf"
        : "help/202608%20RKRSplus%20Overlay%20Editor.pdf";
    } catch (_) {
      return "help/202608%20RKRSplus%20Overlay%20Editor.pdf";
    }
  }

  const RkrkHelp = {
    page: "map",
    getView: () => "map.overview",
    getRole: () => "editor",
    tab: "current",
    open: false,
    _root: null,

    mount(opts) {
      const o = opts || {};
      this.page = o.page || "map";
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
        return this.getRole() || "editor";
      } catch (e) {
        return "editor";
      }
    },

    roleMatches(fn, role) {
      const roles = fn && fn.roles;
      if (!roles || !roles.length) return true;
      const r = String(role || "editor");
      if (roles.includes(r)) return true;
      // KOS-Host/Sub sehen auch allgemeine Editor-Hilfe
      if ((r === "kos_host" || r === "kos_sub" || r === "kos_ready") && roles.includes("editor")) return true;
      if (r === "kos_host" && roles.includes("kos")) return true;
      if (r === "kos_sub" && roles.includes("kos")) return true;
      if (r === "kos_ready" && roles.includes("kos")) return true;
      return false;
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
      const savedW = Number(localStorage.getItem(STORAGE_W) || 0);
      if (Number.isFinite(savedW) && savedW >= 320 && savedW <= 720) {
        drawer.style.setProperty("--rkrk-help-width", `${savedW}px`);
        drawer.style.width = `${savedW}px`;
      }
      drawer.innerHTML = `
        <div class="rkrk-help-resize" title="Breite ziehen"></div>
        <div class="rkrk-help-head">
          <h2>Hilfe</h2>
          <a href="rkrk_help.php" target="_blank" rel="noopener">Katalog</a>
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
        if (Number.isFinite(w)) localStorage.setItem(STORAGE_W, String(w));
      });
      this._root = { backdrop, drawer, body: drawer.querySelector(".rkrk-help-body"), search };
    },

    renderFn(fn) {
      const tools = renderTools(fn.tools);
      const steps = renderSteps(fn.steps);
      const inputs = renderInputs(fn.inputs);
      return `<article class="rkrk-help-fn">
        <h3>${iconHtml(fn.icon)}${escapeHtml(fn.title)}</h3>
        <p>${escapeHtml(functionWhat(fn))}</p>
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
      const pageTitle = this.page === "map" ? productTitle() : (pageMeta.title || "");
      const pdf = schulungHref();

      if (this.tab === "current") {
        const role = this.currentRole();
        const fns = (cat.functions || []).filter((fn) => {
          if (!this.roleMatches(fn, role)) return false;
          const inPage = !fn.pages || !fn.pages.length || fn.pages.includes(this.page);
          const views = fn.views;
          const always = !views || !views.length;
          const inView = always
            || views.includes(viewId)
            || views.includes(`${this.page}.overview`)
            || views.includes("map.live")
            || (role === "viewer" && views.includes("map.viewer"))
            || ((role === "kos_host" || role === "kos_sub" || role === "kos_ready") && views.includes("map.kos"));
          return inPage && inView && matchQuery(fn, q);
        });
        const roleViewId = role === "viewer" ? "map.viewer"
          : (role === "kos_sub" ? "map.kos_sub"
            : (role === "kos_host" || role === "kos_ready" ? "map.kos_host" : ""));
        const roleView = roleViewId ? (cat.views[roleViewId] || {}) : {};
        const viewTools = renderTools(view.tools);
        const viewSteps = renderSteps(view.steps);
        const viewInputs = renderInputs(view.inputs);
        const roleBlock = (roleView.title && roleViewId !== viewId) ? `
          <div class="rkrk-help-card">
            <h3>${iconHtml(roleView.icon)}${escapeHtml(roleView.title)}</h3>
            <p>${escapeHtml(roleView.use || "")}</p>
            ${renderTools(roleView.tools)}
            ${renderSteps(roleView.steps)}
          </div>` : "";
        this._root.body.innerHTML = `
          <span class="rkrk-help-view-badge">${iconHtml(view.icon)}${escapeHtml(view.title || viewId)}</span>
          ${roleBlock}
          <div class="rkrk-help-card">
            <h3>${iconHtml(view.icon)}${escapeHtml(view.title || "")}</h3>
            <p>${escapeHtml(view.use || pageMeta.summary || "")}</p>
            ${viewTools ? `<h4>Werkzeuge dieser Sicht</h4>${viewTools}` : ""}
            ${viewSteps ? `<h4>So geht's</h4>${viewSteps}` : ""}
            ${viewInputs ? `<h4>Was Sie brauchen</h4>${viewInputs}` : ""}
          </div>
          ${fns.length ? `<h4 style="margin:4px 0 8px;color:#5b6f86;font-size:12px;text-transform:uppercase;">Weitere Funktionen</h4>${fns.map((f) => this.renderFn(f)).join("")}` : ""}
          <p><a class="rkrk-help-schulung" href="${pdf}" target="_blank" rel="noopener">${iconHtml("pdf")} Schulung (PDF)</a></p>
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
          <h3>Alle Werkzeuge · ${escapeHtml(pageTitle)}</h3>
          <p>${escapeHtml(pageMeta.summary || "")} Taste <strong>F1</strong> oder <strong>?</strong> öffnet die Hilfe, <strong>Esc</strong> schliesst.</p>
        </div>
        ${fns.length ? fns.map((f) => this.renderFn(f)).join("") : '<p class="rkrk-help-empty">Keine Treffer.</p>'}
        <p><a class="rkrk-help-schulung" href="${pdf}" target="_blank" rel="noopener">${iconHtml("pdf")} Schulung (PDF)</a></p>
      `;
    },

    renderStandalone(target) {
      const host = typeof target === "string" ? document.querySelector(target) : target;
      if (!host) return;
      const cat = catalog();
      const parts = ['<div class="rkrk-help-page">'];
      parts.push("<h1>" + escapeHtml(productTitle()) + " Hilfe</h1>");
      Object.keys(cat.pages || {}).forEach((pid) => {
        const p = cat.pages[pid];
        const pTitle = pid === "map" ? productTitle() : (p.title || pid);
        parts.push(`<h2>${escapeHtml(pTitle)}</h2><p>${escapeHtml(p.summary)}</p>`);
        Object.keys(cat.views || {}).forEach((vid) => {
          const v = cat.views[vid];
          if (v.page !== pid) return;
          parts.push(`<div class="rkrk-help-card"><h3>${iconHtml(v.icon)}Sicht: ${escapeHtml(v.title)}</h3><p>${escapeHtml(v.use)}</p>${renderTools(v.tools)}${renderSteps(v.steps)}</div>`);
        });
      });
      parts.push("<h2>Werkzeugkatalog</h2>");
      (cat.functions || []).forEach((fn) => parts.push(this.renderFn(fn)));
      parts.push("</div>");
      host.innerHTML = parts.join("");
    },
  };

  global.RkrkHelp = RkrkHelp;
})(window);

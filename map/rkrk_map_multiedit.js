/**
 * Multi-Editor for live share: object locks + grayed object list.
 * Requires hooks from rkrk_map_obj_features.js (window.__rkrk*).
 */
(function () {
  "use strict";

  const SHARE_API = "share_drawing.php";
  const NAME_KEY = "rkrk_live_share_editor_name_v1";
  const ID_KEY = "rkrk_live_share_editor_id_v1";
  const SHARE_FETCH_MS = 8000;

  function fetchWithTimeout(url, opts, ms) {
    const timeoutMs = Math.max(500, Number(ms) || SHARE_FETCH_MS);
    if (typeof AbortController === "undefined") {
      return fetch(url, opts || {});
    }
    const ctrl = new AbortController();
    const timer = setTimeout(() => {
      try { ctrl.abort(); } catch (_) { /* noop */ }
    }, timeoutMs);
    const options = Object.assign({}, opts || {}, { signal: ctrl.signal });
    return fetch(url, options).finally(() => clearTimeout(timer));
  }

  function isObjectListRenaming() {
    try {
      if (typeof window.__rkrkIsObjectListRenaming === "function") {
        return !!window.__rkrkIsObjectListRenaming();
      }
    } catch (_) { /* noop */ }
    try {
      return !!document.querySelector("#objManagedList .obj-list-name-input");
    } catch (_) {
      return false;
    }
  }

  let multiEdit = false;
  let coEditor = false;
  let locks = {};
  let editorId = "";
  let editorName = "";
  let lockRenewTimer = null;
  let coPollTimer = null;
  let installed = false;

  function toast(msg, ms) {
    if (typeof window.__rkrkShowBroadcastToast === "function") window.__rkrkShowBroadcastToast(msg, ms);
  }
  function status(msg) {
    if (typeof window.__rkrkManagedStatusText === "function") window.__rkrkManagedStatusText(msg);
  }
  function refreshList() {
    if (isObjectListRenaming()) return;
    if (typeof window.__rkrkRefreshManagedObjectList === "function") window.__rkrkRefreshManagedObjectList();
  }


  function hasUnlock() {
    if (typeof window.__rkrkHasMapEditorShareUnlock === "function") {
      return !!window.__rkrkHasMapEditorShareUnlock();
    }
    try {
      const u = new URL(window.location.href);
      if (u.searchParams.has("ADMIN") || u.searchParams.has("KOS") || u.searchParams.has("INSTR")) return true;
      const code = String(u.searchParams.get("code") || "").trim().toUpperCase();
      return code === "ADMIN" || code === "KOS" || code === "INSTR";
    } catch (_) {
      return false;
    }
  }

  function appendUnlock(u) {
    try {
      const src = new URL(window.location.href);
      if (src.searchParams.has("ADMIN")) {
        const v = src.searchParams.get("ADMIN");
        u.searchParams.set("ADMIN", v == null ? "" : v);
      }
      if (src.searchParams.has("KOS")) {
        const v = src.searchParams.get("KOS");
        u.searchParams.set("KOS", v == null ? "" : v);
      }
      if (src.searchParams.has("INSTR")) {
        const v = src.searchParams.get("INSTR");
        u.searchParams.set("INSTR", v == null ? "" : v);
      }
      const code = String(src.searchParams.get("code") || "").trim();
      if (/^(ADMIN|KOS|INSTR)$/i.test(code)) u.searchParams.set("code", code.toUpperCase());
    } catch (_) {}
    return u;
  }
  function getShareTokenFromUrl() {
    try {
      return String(new URL(window.location.href).searchParams.get("share") || "").trim();
    } catch (_) {
      return "";
    }
  }

  function getShareEditModeFromUrl() {
    try {
      return new URL(window.location.href).searchParams.get("edit") === "1";
    } catch (_) {
      return false;
    }
  }

  try { window.__rkrkGetMultiEditorId = function () { return getOrCreateEditorId(); }; } catch (_) {}
  try { window.__rkrkGetMultiEditorName = function () { return getEditorName(); }; } catch (_) {}
  try { window.__rkrkStampManagedObjectSync = stampObjectSync; } catch (_) {}
  function getOrCreateEditorId() {
    if (editorId) return editorId;
    try {
      let id = String(localStorage.getItem(ID_KEY) || "").trim();
      if (!/^[a-z0-9_-]{8,64}$/i.test(id)) {
        id = ("ed" + Math.random().toString(36).slice(2) + Date.now().toString(36))
          .replace(/[^a-z0-9_-]/gi, "")
          .toLowerCase()
          .slice(0, 32);
        localStorage.setItem(ID_KEY, id);
      }
      editorId = id;
      return id;
    } catch (_) {
      editorId = "ed" + String(Date.now());
      return editorId;
    }
  }

  function isMasterEditorSession() {
    try {
      if (coEditor) return false;
      if (document.body && document.body.classList.contains("rkrk-share-sub")) return false;
      if (document.body && document.body.classList.contains("rkrk-share-host")) return true;
      // Host-Seite mit Freischaltung (noch vor Broadcast)
      return !!hasUnlock();
    } catch (_) {
      return false;
    }
  }

  function getEditorName() {
    // Master heißt immer KOS (für Subs / Locks / Anzeigen sichtbar)
    if (isMasterEditorSession()) {
      editorName = "KOS";
      try { localStorage.setItem(NAME_KEY, "KOS"); } catch (_) {}
      return "KOS";
    }
    if (editorName && editorName.trim()) return editorName.trim().slice(0, 40);
    try {
      let n = String(localStorage.getItem(NAME_KEY) || "").trim();
      if (!n || n === "Master" || n === "KOS") {
        n = "Editor-" + String(getOrCreateEditorId()).slice(-4);
        localStorage.setItem(NAME_KEY, n);
      }
      editorName = n;
      return n;
    } catch (_) {
      editorName = "Editor-" + String(getOrCreateEditorId()).slice(-4);
      return editorName;
    }
  }

  function promptEditorName() {
    if (isMasterEditorSession()) {
      editorName = "KOS";
      try { localStorage.setItem(NAME_KEY, "KOS"); } catch (_) {}
      return "KOS";
    }
    const cur = getEditorName();
    const next = window.prompt("Dein Editor-Name (für andere sichtbar):", cur);
    if (next === null) return cur;
    const cleaned = String(next || "").trim().slice(0, 40);
    if (!cleaned) return cur;
    editorName = cleaned;
    try { localStorage.setItem(NAME_KEY, cleaned); } catch (_) {}
    return cleaned;
  }

  function isSession() {
    return !!(multiEdit || coEditor);
  }

  function applyLocks(next) {
    locks = (next && typeof next === "object") ? next : {};
    paintLockedRows();
  }

  function lockInfo(objectId) {
    if (!isSession()) return null;
    const lock = locks[String(objectId)];
    return (lock && typeof lock === "object") ? lock : null;
  }

  function lockedByOther(objectId) {
    const lock = lockInfo(objectId);
    if (!lock) return false;
    return String(lock.editor_id || "") !== String(getOrCreateEditorId());
  }
  try {
    window.__rkrkObjectLockedByOther = lockedByOther;
    window.__rkrkObjectLockName = function (objectId) {
      const lock = lockInfo(objectId);
      return (lock && lock.name) ? String(lock.name) : "";
    };
    window.__rkrkGetMultiEditorId = getOrCreateEditorId;
    window.__rkrkGetMultiEditorName = getEditorName;
  } catch (_) {}


  async function claimLock(objectId) {
    const token = window.__rkrkGetLiveShareToken ? window.__rkrkGetLiveShareToken() : "";
    if (!token || !isSession()) return true;
    try {
      // Kein vorausgehendes Full-Pull: share_drawing.php auf langsamen Hosts
      // (tools.rehkitzrettung.ch) läuft sonst in ein Timeout und bricht die Bearbeitung ab.
      const res = await fetchWithTimeout(SHARE_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          token,
          claim_lock: (() => {
            let forcePoint = false;
            try {
              const objsC = window.__rkrkGetManagedObjects ? window.__rkrkGetManagedObjects() : [];
              const oC = Array.isArray(objsC) ? objsC.find((x) => x && Number(x.id) === Number(objectId)) : null;
              forcePoint = !!(oC && (oC.type === "points" || oC.type === "textfields"));
            } catch (_) { /* noop */ }
            return {
              object_id: String(objectId),
              editor_id: getOrCreateEditorId(),
              name: getEditorName(),
              ttl_sec: 90,
              force_point: forcePoint
            };
          })()
        }),
        cache: "no-store"
      }, SHARE_FETCH_MS);
      const payload = await res.json().catch(() => ({}));
      if (payload.locks) applyLocks(payload.locks);
      if (res.status === 409 || payload.error === "locked") {
        const who = (payload.lock && payload.lock.name) ? payload.lock.name : "anderer Editor";
        status("Objekt gesperrt von «" + who + "».");
        toast("Objekt gesperrt (" + who + ").", 2800);
        return false;
      }
      if (!res.ok || !payload.ok) {
        status("Sperre nicht bestätigt — Bearbeitung lokal fortgesetzt.");
        return true;
      }
      return true;
    } catch (_) {
      status("Server antwortet langsam — Bearbeitung lokal fortgesetzt.");
      return true;
    }
  }

  async function releaseLock(objectId) {
    const token = window.__rkrkGetLiveShareToken ? window.__rkrkGetLiveShareToken() : "";
    if (!token || objectId == null || !isSession()) return;
    try {
      const res = await fetch(SHARE_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          token,
          release_lock: { object_id: String(objectId), editor_id: getOrCreateEditorId() }
        }),
        cache: "no-store"
      });
      const payload = await res.json().catch(() => ({}));
      if (payload.locks) applyLocks(payload.locks);
    } catch (_) { /* noop */ }
  }

  async function renewLocks() {
    const token = window.__rkrkGetLiveShareToken ? window.__rkrkGetLiveShareToken() : "";
    if (!token || !isSession()) return;
    const ids = [];
    const sel = window.__rkrkGetSelectedManagedObjectId ? window.__rkrkGetSelectedManagedObjectId() : null;
    const objs = window.__rkrkGetManagedObjects ? window.__rkrkGetManagedObjects() : [];
    if (sel != null && Array.isArray(objs)) {
      const obj = objs.find((o) => o && Number(o.id) === Number(sel) && !o.fixed);
      if (obj) ids.push(String(obj.id));
    }
    try {
      const res = await fetch(SHARE_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          token,
          renew_locks: { editor_id: getOrCreateEditorId(), name: getEditorName(), object_ids: ids }
        }),
        cache: "no-store"
      });
      const payload = await res.json().catch(() => ({}));
      if (payload.locks) applyLocks(payload.locks);
    } catch (_) { /* noop */ }
  }

  function startLockRenew() {
    stopLockRenew();
    lockRenewTimer = setInterval(renewLocks, 25000);
  }
  function stopLockRenew() {
    if (lockRenewTimer) {
      clearInterval(lockRenewTimer);
      lockRenewTimer = null;
    }
  }

  function ensureCss() {
    if (document.getElementById("rkrkMultiEditCss")) return;
    const style = document.createElement("style");
    style.id = "rkrkMultiEditCss";
    style.textContent = ""
      + "#liveShareQrPanel .qr-multi{display:flex;align-items:center;gap:6px;margin:0 0 8px;font-size:11px;color:#334155;}"
      + "#liveShareQrPanel .qr-multi input{margin:0;}"
      + "#objManagedList li.obj-list-locked{opacity:.55;filter:grayscale(.35);background:#f1f5f9!important;}"
      + "#objManagedList li.obj-list-locked .obj-list-actions button{pointer-events:none;opacity:.45;}"
      + "#objManagedList li.obj-list-locked button.obj-list-edit:disabled,"
      + "#objManagedList button.obj-list-edit:disabled{opacity:.45;cursor:not-allowed;filter:grayscale(.2);}"
      + "#objManagedList .obj-list-badge.is-locked{border-color:#94a3b8;color:#475569;background:#e2e8f0;}"
      + "#liveShareEditorCount{position:fixed;top:60px;right:12px;z-index:41;display:none;"
      + "background:rgba(29,78,216,.94);color:#fff;font-size:12px;line-height:1.35;"
      + "padding:6px 10px;border-radius:999px;box-shadow:0 2px 10px rgba(0,0,0,.18);}"
      + "body.rkrk-share-coeditor #liveShareEditorCount{display:block;}body.rkrk-share-sub #liveBroadcastBtn{display:none!important;}"
      + "body.rkrk-share-coeditor #liveShareDeviceCount{top:94px;}"
      + "#liveShareQrPanel #liveShareQrEditorCount{font-size:11px;color:#1d4ed8;text-align:center;margin:0 0 8px;font-weight:600;}"
      + "html.rkrk-share-coeditor,body.rkrk-share-coeditor{overflow:auto!important;}body.rkrk-share-coeditor:not(.obj-sidebar-collapsed) aside.panel,body.rkrk-share-coeditor:not(.obj-sidebar-collapsed) .overlay-box,body.rkrk-share-coeditor:not(.obj-sidebar-collapsed) .style-box,body.rkrk-share-coeditor:not(.obj-sidebar-collapsed) #objManagedListPanel,body.rkrk-share-coeditor:not(.obj-sidebar-collapsed) #objManagedToolsTop,body.rkrk-share-coeditor #objSidebarToggleBtn,body.rkrk-share-coeditor:not(.obj-sidebar-collapsed) #objSidebarResizeHandle,body.rkrk-share-coeditor:not(.obj-sidebar-collapsed) #basemapTopBar,body.rkrk-share-coeditor .ol-zoom,body.rkrk-share-coeditor .ol-attribution,body.rkrk-share-coeditor #liveQrShowBtn{display:revert!important;}body.rkrk-share-coeditor.obj-sidebar-collapsed,html.rkrk-share-coeditor.obj-sidebar-collapsed{grid-template-columns:1fr!important;}body.rkrk-share-coeditor.obj-sidebar-collapsed aside.panel,body.rkrk-share-coeditor.obj-sidebar-collapsed .overlay-box,body.rkrk-share-coeditor.obj-sidebar-collapsed .style-box,body.rkrk-share-coeditor.obj-sidebar-collapsed #objManagedListPanel,body.rkrk-share-coeditor.obj-sidebar-collapsed #objSidebarResizeHandle{display:none!important;}body.rkrk-share-coeditor.obj-sidebar-collapsed #map{position:relative!important;inset:auto!important;width:100%!important;height:100vh!important;grid-column:1/-1!important;}body.rkrk-share-coeditor:not(.obj-sidebar-collapsed) #map{position:relative!important;inset:auto!important;width:100%!important;height:100vh!important;grid-column:auto!important;}body.rkrk-share-coeditor #liveShareViewerBadge{display:none!important;}";
    document.head.appendChild(style);
  }

  let lastEditorCount = 0;
  let lastViewerCount = 0;

  function formatEditorCountLabel(count) {
    const n = Math.max(0, Number(count) || 0);
    return n === 1 ? "1 Editor verbunden" : (n + " Editoren verbunden");
  }

  function ensureEditorCountBadge() {
    let badge = document.getElementById("liveShareEditorCount");
    if (!badge) {
      badge = document.createElement("div");
      badge.id = "liveShareEditorCount";
      document.body.appendChild(badge);
    }
    return badge;
  }

  function updateEditorCountUi(editorCount, viewerCount) {
    lastEditorCount = Math.max(0, Number(editorCount) || 0);
    if (viewerCount != null) lastViewerCount = Math.max(0, Number(viewerCount) || 0);
    const show = isSession() && (multiEdit || coEditor);
    const badge = ensureEditorCountBadge();
    if (show) {
      document.body.classList.add("rkrk-share-coeditor");
      document.documentElement.classList.remove("rkrk-share-viewer");
      document.body.classList.remove("rkrk-share-viewer");
      badge.style.display = "block";
      badge.textContent = formatEditorCountLabel(lastEditorCount || 1);
      badge.title = isMasterHost()
        ? "Klicken: Sub-Editoren verwalten"
        : "Multi-Editor aktiv — Menü bleibt nutzbar; gesperrte Objekte sind ausgegraut";
      try { wireEditorCountBadgeClick(); } catch (_) {}
    } else {
      document.body.classList.remove("rkrk-share-coeditor");
      badge.style.display = "none";
    }
    // QR-Panel: Editoren + optional Zuschauer
    let qrEd = document.getElementById("liveShareQrEditorCount");
    const panel = document.getElementById("liveShareQrPanel");
    if (panel && show) {
      if (!qrEd) {
        qrEd = document.createElement("p");
        qrEd.id = "liveShareQrEditorCount";
        const qrCount = document.getElementById("liveShareQrCount");
        if (qrCount) panel.insertBefore(qrEd, qrCount);
        else panel.appendChild(qrEd);
      }
      let t = formatEditorCountLabel(lastEditorCount || 1);
      if (lastViewerCount > 0) {
        t += " · " + (lastViewerCount === 1 ? "1 Zuschauer" : (lastViewerCount + " Zuschauer"));
      }
      qrEd.textContent = t;
      qrEd.style.display = "";
    } else if (qrEd) {
      qrEd.style.display = "none";
    }
    try {
      if (typeof window.updateConnectedDevicesUi === "function") {
        window.updateConnectedDevicesUi(
          viewerCount != null ? lastViewerCount : undefined,
          lastEditorCount
        );
      }
    } catch (_) { /* noop */ }
    // Gerate-Zahler (Zuschauer) unter Editor-Zahler schieben, wenn beide aktiv
    const dev = document.getElementById("liveShareDeviceCount");
    if (dev && show) {
      dev.style.top = "92px";
    } else if (dev) {
      dev.style.top = "";
    }
  }

  function setCoBadge(text) {
    // Kompatibilität: Text ignorieren, Zähler-UI aktualisieren
    updateEditorCountUi(lastEditorCount || 1, lastViewerCount);
    if (text && /verbinde/i.test(String(text))) {
      const badge = ensureEditorCountBadge();
      badge.style.display = "block";
      badge.textContent = "Multi-Editor — verbinde…";
      document.body.classList.add("rkrk-share-coeditor");
    }
  }

  function paintLockedRows() {
    const list = document.getElementById("objManagedList");
    if (!list) return;
    const objs = window.__rkrkGetManagedObjects ? window.__rkrkGetManagedObjects() : [];
    let needRefresh = false;
    Array.from(list.children).forEach((li) => {
      if (!li || li.tagName !== "LI") return;
      let obj = null;
      const oid = li.dataset && li.dataset.objectId != null ? Number(li.dataset.objectId) : NaN;
      if (Number.isFinite(oid) && Array.isArray(objs)) {
        obj = objs.find((o) => o && Number(o.id) === oid) || null;
      }
      if (!obj) {
        const nameEl = li.querySelector(".obj-list-name");
        const name = nameEl ? String(nameEl.textContent || "").trim() : "";
        obj = Array.isArray(objs) ? objs.find((o) => {
          if (!o) return false;
          if (typeof window.__rkrkManagedObjectDisplayName === "function") {
            return window.__rkrkManagedObjectDisplayName(o) === name;
          }
          return String(o.name || "") === name;
        }) : null;
      }
      if (!obj) return;
      const lock = lockInfo(obj.id);
      const badge = li.querySelector(".obj-list-badge");
      const actions = li.querySelector(".obj-list-actions");
      const foreign = !!(lock && lockedByOther(obj.id));
      const pointishPaint = !!(obj && (obj.type === "points" || obj.type === "textfields"));
      if (foreign && !pointishPaint) {
        li.classList.add("obj-list-locked");
        const who = (lock && lock.name) ? String(lock.name) : "anderem Editor";
        // Lokal wie fixiert behandeln, damit nicht "Abschliessen" erscheint
        try { if (obj && obj.fixed === false) obj.fixed = true; } catch (_) { /* noop */ }
        if (badge) {
          badge.textContent = "gesperrt";
          badge.className = "obj-list-badge is-locked";
          badge.title = "In Bearbeitung von " + who;
        }
        if (actions) {
          actions.innerHTML = "";
          const editBtn = document.createElement("button");
          editBtn.type = "button";
          editBtn.className = "obj-list-edit obj-icon-btn";
          editBtn.disabled = true;
          editBtn.setAttribute("aria-disabled", "true");
          const lockTitle = "In Bearbeitung von " + who;
          editBtn.title = lockTitle;
          editBtn.setAttribute("aria-label", lockTitle);
          editBtn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
          actions.appendChild(editBtn);
          const info = document.createElement("span");
          info.className = "obj-list-badge is-locked obj-list-lock-who";
          info.textContent = lock.name || "Editor";
          info.title = "In Bearbeitung von " + who;
          actions.appendChild(info);
        }
      } else {
        const wasLocked = li.classList.contains("obj-list-locked");
        li.classList.remove("obj-list-locked");
        if (wasLocked) needRefresh = true;
      }
    });
    if (needRefresh && !paintLockedRows._refreshing) {
      paintLockedRows._refreshing = true;
      try { refreshList(); } catch (_) { /* noop */ }
      paintLockedRows._refreshing = false;
    }
  }

  function observeList() {
    const list = document.getElementById("objManagedList");
    if (!list || list.__rkrkMultiEditObserved) return;
    list.__rkrkMultiEditObserved = true;
    const mo = new MutationObserver(() => {
      try { paintLockedRows(); } catch (_) {}
    });
    mo.observe(list, { childList: true, subtree: true });
  }

  function buildShareUrl(token) {
    try {
      const u = new URL(window.location.href);
      u.searchParams.set("share", token);
      if (multiEdit) u.searchParams.set("edit", "1");
      else u.searchParams.delete("edit");
      appendUnlock(u);
      return u.toString();
    } catch (_) {
      let q = "rkrk_map_overlay.php?share=" + encodeURIComponent(token);
      if (multiEdit) q += "&edit=1";
      if (hasUnlock()) {
        try {
          const src = new URL(window.location.href);
          if (src.searchParams.has("ADMIN")) q += "&ADMIN";
          if (src.searchParams.has("KOS")) q += "&KOS";
        } catch (_) {}
      }
      return q;
    }
  }

  function patchBuildShareUrl() {
    const orig = window.__rkrkBuildShareUrlOrig;
    if (typeof orig !== "function") return;
    window.__rkrkBuildShareUrl = function (token) {
      return buildShareUrl(token);
    };
    // features uses local buildShareUrl — monkeypatch via wrapping push is enough if we update QR ourselves
  }

  async function enrichPushBody() {
    // Intercept fetch to share_drawing.php POST drawing and inject multi_edit
    if (window.__rkrkMultiEditFetchPatched) return;
    window.__rkrkMultiEditFetchPatched = true;
    const origFetch = window.fetch.bind(window);
    window.fetch = async function (input, init) {
      try {
        const url = String(typeof input === "string" ? input : (input && input.url) || "");
        if (url.indexOf("share_drawing.php") >= 0 && init && init.method && String(init.method).toUpperCase() === "POST" && init.body) {
          const body = JSON.parse(init.body);
          if (body && body.drawing) {
            body.multi_edit = !!(multiEdit || coEditor);
            if (multiEdit || coEditor) {
              body.editor_id = getOrCreateEditorId();
              body.editor_name = getEditorName();
              // Master (nicht Sub): Host markieren
              if (!coEditor && !(document.body && document.body.classList.contains("rkrk-share-sub"))) {
                body.is_host = true;
              }
              // Basis-Zeitstempel senden; KEIN Soft-Apply vor Push
              // (sonst werden Kachelfarben / lokale Edits vor dem Speichern verworfen)
              body.base_updated_at = window.__rkrkGetLiveShareLastUpdated
                ? String(window.__rkrkGetLiveShareLastUpdated() || "")
                : "";
              try {
                const objs = body.drawing.managedObjects;
                if (Array.isArray(objs)) {
                  const my = getOrCreateEditorId();
                  const sel = window.__rkrkGetSelectedManagedObjectId
                    ? window.__rkrkGetSelectedManagedObjectId()
                    : null;
                  const now = new Date().toISOString();
                  body.drawing.managedObjects = objs.map((o) => {
                    if (!o || typeof o !== "object") return o;
                    const copy = Object.assign({}, o);
                    const selected = sel != null && Number(sel) === Number(copy.id);
                    let lockedByMe = false;
                    try {
                      const lk = locks[String(copy.id)];
                      lockedByMe = !!(lk && String(lk.editor_id || "") === String(my));
                    } catch (_) { /* noop */ }
                    const alreadyMine = String(copy.updated_by || "") === String(my);
                    const unstamped = !String(copy.updated_by || "").trim();
                    // Sub/Master: eigene / ungestempelte Objekte stempeln (auch nach Fixieren ohne Selektion)
                    if (selected || lockedByMe || alreadyMine || unstamped) {
                      copy.updated_by = my;
                      copy.sync_at = now;
                    }
                    return copy;
                  });
                }
              } catch (_) { /* noop */ }
            }
            init = Object.assign({}, init, { body: JSON.stringify(body) });
          } else if (body && body.ping) {
            body.multi_edit = !!(multiEdit || coEditor);
            if (multiEdit || coEditor) {
              body.editor_id = getOrCreateEditorId();
              body.editor_name = getEditorName();
            }
            init = Object.assign({}, init, { body: JSON.stringify(body) });
          }
          const res = await origFetch(input, init);
          try {
            const clone = res.clone();
            const payload = await clone.json();
            if (payload && payload.locks) applyLocks(payload.locks);
            if (payload && payload.multi_edit != null) {
              if (payload.multi_edit) multiEdit = true;
              else if (!coEditor) multiEdit = false;
              const chk = document.getElementById("liveShareMultiEditChk");
              if (chk && multiEdit) chk.checked = true;
            }
            if (payload && (payload.editor_count != null || payload.viewer_count != null)) {
              updateEditorCountUi(
                payload.editor_count != null ? payload.editor_count : lastEditorCount,
                payload.viewer_count != null ? payload.viewer_count : lastViewerCount
              );
            }
            // Master: Server-Merge aus POST-Antwort lokal uebernehmen (sonst Poll = unchanged, Sub fehlt)
            if (payload && payload.drawing && (multiEdit || coEditor || payload.multi_edit)) {
              if (payload.updated_at && window.__rkrkSetLiveShareLastUpdated) {
                window.__rkrkSetLiveShareLastUpdated(payload.updated_at);
              }
              applyRemoteDrawingSoft(payload.drawing);
              try { refreshList(); paintLockedRows(); } catch (_) { /* noop */ }
              if ((multiEdit || coEditor) && !coPollTimer) startCoPolling();
            }
          } catch (_) { /* noop */ }
          return res;
        }
      } catch (_) { /* fall through */ }
      return origFetch(input, init);
    };
  }

  function ensureMultiEditCheckbox() {
    const panel = document.getElementById("liveShareQrPanel");
    if (!panel) return;
    if (!hasUnlock()) {
      const chk = document.getElementById("liveShareMultiEditChk");
      if (chk) {
        const lab = chk.closest("label");
        if (lab) lab.style.display = "none";
      }
      return;
    }
    let chk = document.getElementById("liveShareMultiEditChk");
    if (!chk) {
      const lab = document.createElement("label");
      lab.className = "qr-multi";
      lab.innerHTML = '<input type="checkbox" id="liveShareMultiEditChk"/> Mehrere Editoren (Objekte sperren)';
      const media = document.getElementById("liveShareQrMedia");
      const h3 = panel.querySelector("h3");
      if (media) panel.insertBefore(lab, media);
      else if (h3 && h3.nextSibling) panel.insertBefore(lab, h3.nextSibling);
      else panel.appendChild(lab);
      chk = document.getElementById("liveShareMultiEditChk");
    }
    if (chk && chk.dataset.bound !== "1") {
      chk.dataset.bound = "1";
      chk.addEventListener("change", async () => {
        multiEdit = !!chk.checked;
        if (multiEdit) {
          promptEditorName();
          claimCurrentEditingLock();
          if (!coPollTimer) startCoPolling();
        }
        const active = window.__rkrkIsLiveShareActive && window.__rkrkIsLiveShareActive();
        if (active && typeof window.__rkrkPushLiveShareState === "function") {
          try {
            const saved = await window.__rkrkPushLiveShareState(true);
            const token = (saved && saved.token) || (window.__rkrkGetLiveShareToken && window.__rkrkGetLiveShareToken());
            const u = buildShareUrl(String(token || ""));
            panel.dataset.shareUrl = u;
            const urlEl = document.getElementById("liveShareQrUrl");
            if (urlEl) urlEl.textContent = u;
            if (typeof window.__rkrkRenderShareQr === "function") await window.__rkrkRenderShareQr(u);
            if (typeof window.__rkrkCopyText === "function") await window.__rkrkCopyText(u);
            toast(multiEdit
              ? "Multi-Editor aktiv — Link mit &edit=1"
              : "Nur Zuschauen — ohne Editor-Recht", 3200);
            if (multiEdit) {
              document.body.classList.add("rkrk-share-coeditor");
              updateEditorCountUi(Math.max(1, lastEditorCount), lastViewerCount);
              startCoPolling();
            } else if (!coEditor) {
              stopCoPolling();
              updateEditorCountUi(0, lastViewerCount);
            }
          } catch (_) { /* noop */ }
        }
      });
    }
    if (chk) chk.checked = !!multiEdit;
  }

  function objectSyncTs(obj) {
    if (!obj || typeof obj !== "object") return 0;
    const raw = obj.sync_at || obj.updated_at || obj.fixed_at || "";
    if (typeof raw === "number" && Number.isFinite(raw)) {
      return raw > 1e12 ? Math.floor(raw / 1000) : raw;
    }
    const t = Date.parse(String(raw || ""));
    return Number.isFinite(t) ? Math.floor(t / 1000) : 0;
  }

  function stampObjectSync(obj) {
    if (!obj || typeof obj !== "object") return obj;
    obj.sync_at = new Date().toISOString();
    obj.updated_by = getOrCreateEditorId();
    return obj;
  }

  /**
   * Soft-merge remote managedObjects into local without clobbering
   * objects we lock / currently edit / authored more recently.
   */
  
  function mergeGridCellFillsLocal(chosen, other) {
    if (!chosen || chosen.type !== "grid") return chosen;
    const a = (chosen.cellFills && typeof chosen.cellFills === "object") ? chosen.cellFills : {};
    const b = (other && other.type === "grid" && other.cellFills && typeof other.cellFills === "object")
      ? other.cellFills : {};
    if (!Object.keys(a).length && !Object.keys(b).length) return chosen;
    const lt = objectSyncTs(chosen);
    const rt = other ? objectSyncTs(other) : 0;
    // Neuere Basis, fehlende Keys vom anderen ergänzen
    const fills = lt >= rt ? Object.assign({}, b, a) : Object.assign({}, a, b);
    return Object.assign({}, chosen, { cellFills: fills });
  }
  function mergeManagedObjectsLocal(localList, remoteList) {
    /**
     * Shared share-file (remote) is always the Ausgangsbasis.
     * Local overlays ONLY: objects I currently lock / edit, or local-only creates.
     * This prevents master/sub keep-local deadlocks.
     */
    const myId = getOrCreateEditorId();
    const selectedId = window.__rkrkGetSelectedManagedObjectId
      ? window.__rkrkGetSelectedManagedObjectId()
      : null;
    const localById = new Map();
    (Array.isArray(localList) ? localList : []).forEach((o) => {
      if (o && o.id != null) localById.set(Number(o.id), o);
    });
    const byId = new Map();
    const remoteIds = new Set();

    // 1) Server-Stand als Basis (fremde Unfixed-Entwuerfe ausblenden)
    (Array.isArray(remoteList) ? remoteList : []).forEach((o) => {
      if (!o || o.id == null) return;
      const id = Number(o.id);
      if (o.fixed === false) {
        const by = String(o.updated_by || "");
        if (!by || by !== String(myId)) return;
      }
      remoteIds.add(id);
      const lock = lockInfo(id);
      const holder = lock ? String(lock.editor_id || "") : "";
      if (holder && holder !== String(myId)) {
        byId.set(id, Object.assign({}, o, { fixed: true }));
      } else {
        byId.set(id, o);
      }
    });

    // 2) Lokale In-Arbeit-Objekte (unfixed) und Locks behalten — Sync darf Verschieben nicht zuruecksetzen
    localById.forEach((loc, id) => {
      const lock = lockInfo(id);
      const holder = lock ? String(lock.editor_id || "") : "";
      const lockedByMe = !!(holder && holder === String(myId));
      const editingSelected = selectedId != null && Number(selectedId) === id && loc.fixed === false;
      const localUnfixed = loc.fixed === false;
      const mine = !loc.updated_by || String(loc.updated_by) === String(myId);
      if (lockedByMe || editingSelected || localUnfixed) {
        byId.set(id, loc);
        return;
      }
      // Fixieren lokal: nicht durch veraltetes remote-unfixed wieder auf Bearb. setzen
      const rem = byId.get(id);
      if (loc.fixed !== false && rem && rem.fixed === false && mine) {
        byId.set(id, loc);
        return;
      }
      // Eigene neuere lokale Version (z.B. Fix noch nicht auf allen Clients)
      if (loc.fixed !== false && rem && mine) {
        const locTs = Date.parse(String(loc.sync_at || "")) || 0;
        const remTs = Date.parse(String(rem.sync_at || "")) || 0;
        if (locTs > remTs) {
          byId.set(id, loc);
          return;
        }
      }
      if (!remoteIds.has(id)) {
        if (mine || lockedByMe || editingSelected || localUnfixed) byId.set(id, loc);
      }
    });

        // Kachelfarben aus lokal + remote zusammenführen (nicht nur eine Kachel behalten)
    const localByIdFinal = new Map();
    (Array.isArray(localList) ? localList : []).forEach((o) => {
      if (o && o.id != null) localByIdFinal.set(Number(o.id), o);
    });
    const remoteByIdFinal = new Map();
    (Array.isArray(remoteList) ? remoteList : []).forEach((o) => {
      if (o && o.id != null) remoteByIdFinal.set(Number(o.id), o);
    });
    // Reihenfolge: Remote zuerst (Share-Basis), danach lokale IDs die nur lokal existieren
    const ordered = [];
    const seen = new Set();
    (Array.isArray(remoteList) ? remoteList : []).forEach((o) => {
      if (!o || o.id == null) return;
      const id = Number(o.id);
      if (!byId.has(id) || seen.has(id)) return;
      ordered.push(byId.get(id));
      seen.add(id);
    });
    (Array.isArray(localList) ? localList : []).forEach((o) => {
      if (!o || o.id == null) return;
      const id = Number(o.id);
      if (!byId.has(id) || seen.has(id)) return;
      ordered.push(byId.get(id));
      seen.add(id);
    });
    byId.forEach((o, id) => {
      if (seen.has(id)) return;
      ordered.push(o);
      seen.add(id);
    });
    return ordered.map((o) => {
      if (!o || o.type !== "grid") return o;
      const id = Number(o.id);
      const loc = localByIdFinal.get(id);
      const rem = remoteByIdFinal.get(id);
      const other = (loc && Number(loc.id) === id && loc !== o) ? loc : rem;
      return mergeGridCellFillsLocal(o, other && other !== o ? other : null);
    });
  }


  let lastSharePullAt = 0;
  let shareSyncChain = Promise.resolve();
  function enqueueShareSync(fn) {
    shareSyncChain = shareSyncChain.then(fn, fn).catch(() => {});
    return shareSyncChain;
  }

  async function pullShareBaseQuiet() {
    // Shared file vom Server laden = Ausgangsinformation vor jeder Aenderung
    if (window.__applyingDrawingState) return null;
    if (window.__rkrkShareSyncPulling) return null;
    const token = window.__rkrkGetLiveShareToken ? window.__rkrkGetLiveShareToken() : "";
    if (!token) return null;
    if (window.__rkrkIsLiveShareViewer && window.__rkrkIsLiveShareViewer()) return null;
    const now = Date.now();
    if (now - lastSharePullAt < 350) return null;
    window.__rkrkShareSyncPulling = true;
    try {
      // ohne since → voller Server-Stand (Ausgangsbasis)
      const url = SHARE_API + "?token=" + encodeURIComponent(token)
        + "&editor_id=" + encodeURIComponent(getOrCreateEditorId())
        + "&editor_name=" + encodeURIComponent(getEditorName())
        + "&sync_base=1";
      const res = await fetchWithTimeout(url, { cache: "no-store" }, 4000);
      const payload = await res.json().catch(() => ({}));
      if (!res.ok || !payload || !payload.ok) return null;
      lastSharePullAt = Date.now();
      if (payload.locks) applyLocks(payload.locks);
      if (payload.multi_edit) multiEdit = true;
      if (payload.updated_at && window.__rkrkSetLiveShareLastUpdated) {
        window.__rkrkSetLiveShareLastUpdated(payload.updated_at);
      }
      if (payload.drawing) applyRemoteDrawingSoft(payload.drawing);
      try { refreshList(); paintLockedRows(); } catch (_) { /* noop */ }
      return payload;
    } catch (_) {
      return null;
    } finally {
      window.__rkrkShareSyncPulling = false;
    }
  }
  try { window.__rkrkPullShareBase = pullShareBaseQuiet; } catch (_) {}

  function applyRemoteDrawingSoft(drawing) {
    if (!drawing || typeof drawing !== "object") return;
    if (isObjectListRenaming()) return;
    // Waehrend lokalem Verschieben/Ziehen: Sync nicht anwenden (sonst springt Objekt zurueck)
    try {
      if (typeof window.__rkrkIsLocalObjectDragging === "function" && window.__rkrkIsLocalObjectDragging()) {
        return;
      }
    } catch (_) { /* noop */ }
    // UI-Draft (z.B. Raster-Anker nach Drag) zuerst in managedObjects schreiben
    try {
      if (typeof window.__rkrkSyncEditingObjectFromUi === "function") {
        window.__rkrkSyncEditingObjectFromUi();
      }
    } catch (_) { /* noop */ }
    const local = window.__rkrkGetManagedObjects ? window.__rkrkGetManagedObjects() : [];
    const remote = Array.isArray(drawing.managedObjects) ? drawing.managedObjects : [];
    const merged = mergeManagedObjectsLocal(local, remote);

    const patched = Object.assign({}, drawing, { managedObjects: merged });
    // Objekt-Sync darf Zoom/Ausschnitt nicht anfassen (sonst Zoom-Kampf im Sub-Editor)
    try {
      delete patched.mapView;
    } catch (_) { /* noop */ }
    window.__applyingDrawingState = true;
    window.__rkrkSoftMergingManaged = true;
    try {
      if (typeof window.__rkrkApplyDrawingState === "function") {
        window.__rkrkApplyDrawingState(patched);
      } else if (typeof window.__rkrkReplaceManagedObjectsSoft === "function") {
        window.__rkrkReplaceManagedObjectsSoft(merged);
      }
    } finally {
      window.__rkrkSoftMergingManaged = false;
      window.__applyingDrawingState = false;
    }
    try {
      if (typeof window.__rkrkRenderManagedObjects === "function") window.__rkrkRenderManagedObjects();
      else if (typeof window.__rkrkRefreshManagedObjectList === "function") window.__rkrkRefreshManagedObjectList();
    } catch (_) { /* noop */ }
  }
  try { window.__rkrkApplyRemoteDrawingSoft = applyRemoteDrawingSoft; } catch (_) {}

  let pollCoEditorTick = 0;
  async function pollCoEditor() {
    const token = window.__rkrkGetLiveShareToken ? window.__rkrkGetLiveShareToken() : "";
    if (!token) return;
    if (window.__rkrkIsLiveShareViewer && window.__rkrkIsLiveShareViewer()) return;
    try {
      pollCoEditorTick = (pollCoEditorTick + 1) % 3;
      // Jeder 3. Poll: voller Stand ohne since (Sub→Master bleibt sichtbar)
      const since = (pollCoEditorTick === 0)
        ? ""
        : (window.__rkrkGetLiveShareLastUpdated ? window.__rkrkGetLiveShareLastUpdated() : "");
      const url = SHARE_API + "?token=" + encodeURIComponent(token)
        + (since ? "&since=" + encodeURIComponent(since) : "")
        + "&editor_id=" + encodeURIComponent(getOrCreateEditorId())
        + "&editor_name=" + encodeURIComponent(getEditorName())
        + (since ? "" : "&sync_base=1");
      const res = await fetchWithTimeout(url, { cache: "no-store" }, SHARE_FETCH_MS);
      const payload = await res.json().catch(() => ({}));
      if (!res.ok || !payload.ok) return;
      if (payload.multi_edit != null) {
        if (payload.multi_edit) multiEdit = true;
        else if (!coEditor) multiEdit = false;
        const chk = document.getElementById("liveShareMultiEditChk");
        if (chk && multiEdit) chk.checked = true;
      }
      if (payload.locks) applyLocks(payload.locks);
      const nEd = payload.editor_count != null ? Number(payload.editor_count) : 0;
      const nView = payload.viewer_count != null ? Number(payload.viewer_count) : lastViewerCount;
      updateEditorCountUi(nEd || 1, nView);
      if ((nEd || 0) > 1 || multiEdit || coEditor) {
        multiEdit = true;
        const chk = document.getElementById("liveShareMultiEditChk");
        if (chk) chk.checked = true;
        if (!coPollTimer) startCoPolling();
      }
      try { ingestEditorModeration(payload); } catch (_) { /* noop */ }
      if (payload.kicked) return;
      if (isObjectListRenaming()) return;
      if (payload.unchanged || !payload.drawing) return;
      if (payload.updated_at && since && payload.updated_at === since) return;

      if (window.__rkrkSetLiveShareLastUpdated) window.__rkrkSetLiveShareLastUpdated(payload.updated_at || "");
      // Auch ohne lokales multiEdit-Flag mergen (Master startet oft spaeter)
      if (payload.drawing && !payload.kicked) applyRemoteDrawingSoft(payload.drawing);
      try {
        if (typeof window.__rkrkIngestViewLeader === "function") window.__rkrkIngestViewLeader(payload);
      } catch (_) { /* noop */ }
      refreshList();
      paintLockedRows();
    } catch (_) { /* keep polling */ }
  }


  async function claimCurrentEditingLock() {
    if (!isSession()) return;
    const sel = window.__rkrkGetSelectedManagedObjectId
      ? window.__rkrkGetSelectedManagedObjectId()
      : null;
    if (sel == null) return;
    const objs = window.__rkrkGetManagedObjects ? window.__rkrkGetManagedObjects() : [];
    const obj = Array.isArray(objs) ? objs.find((o) => o && Number(o.id) === Number(sel) && !o.fixed) : null;
    if (!obj) return;
    try {
      await claimLock(obj.id);
      paintLockedRows();
    } catch (_) { /* noop */ }
  }

  function startCoPolling() {
    if (coPollTimer) clearInterval(coPollTimer);
    coPollTimer = setInterval(pollCoEditor, 1500);
    pollCoEditor();
    startLockRenew();
    claimCurrentEditingLock();
  }

  function stopCoPolling() {
    if (coPollTimer) {
      clearInterval(coPollTimer);
      coPollTimer = null;
    }
    stopLockRenew();
    const badge = document.getElementById("liveShareEditorCount");
    if (badge) badge.style.display = "none";
    document.body.classList.remove("rkrk-share-coeditor");
    const qrEd = document.getElementById("liveShareQrEditorCount");
    if (qrEd) qrEd.style.display = "none";
  }

  async function startAsCoEditor(token) {
    coEditor = true;
    multiEdit = true;
    if (window.__rkrkSetLiveShareViewerMode) window.__rkrkSetLiveShareViewerMode(false);
    if (window.__rkrkSetLiveShareActive) window.__rkrkSetLiveShareActive(true);
    if (window.__rkrkSetLiveShareToken) window.__rkrkSetLiveShareToken(token);
    try { if (typeof window.__rkrkStopLiveShareViewerPoll === "function") window.__rkrkStopLiveShareViewerPoll(); } catch (_) {}
    document.documentElement.classList.remove("rkrk-share-viewer");
    document.body.classList.remove("rkrk-share-viewer");
    document.documentElement.classList.add("rkrk-share-coeditor");
    document.body.classList.add("rkrk-share-coeditor");
    document.documentElement.classList.add("rkrk-share-sub");
    document.body.classList.add("rkrk-share-sub");
    document.body.classList.remove("rkrk-share-host");
    try {
      const bc = document.getElementById("liveBroadcastBtn");
      if (bc) {
        bc.style.display = "none";
        bc.setAttribute("hidden", "true");
      }
    } catch (_) { /* noop */ }
    try { if (typeof window.__rkrkRestoreShareEditorChrome === "function") window.__rkrkRestoreShareEditorChrome(); } catch (_) {}
    try { if (typeof window.__rkrkReapplyObjSidebar === "function") window.__rkrkReapplyObjSidebar(); } catch (_) {}
    if (window.__rkrkEnsureBroadcastCss) window.__rkrkEnsureBroadcastCss();
    ensureCss();
    ensureEditorCountBadge();
    promptEditorName();
    if (window.__rkrkPersistShareToken) window.__rkrkPersistShareToken(token);
    updateEditorCountUi(1, 0);
    setCoBadge("Multi-Editor — verbinde…");
    toast("Multi-Editor aktiv — Menü freigegeben, gesperrte Objekte ausgegraut.", 4000);
    // Zuerst Host-Stand laden (kein Blind-Push)
    try {
      const url = SHARE_API + "?token=" + encodeURIComponent(token)
        + "&editor_id=" + encodeURIComponent(getOrCreateEditorId())
        + "&editor_name=" + encodeURIComponent(getEditorName());
      const res = await fetch(url, { cache: "no-store" });
      const payload = await res.json().catch(() => ({}));
      if (res.ok && payload && payload.ok) {
        if (payload.locks) applyLocks(payload.locks);
        if (payload.updated_at && window.__rkrkSetLiveShareLastUpdated) {
          window.__rkrkSetLiveShareLastUpdated(payload.updated_at);
        }
        if (payload.drawing) applyRemoteDrawingSoft(payload.drawing);
        if (payload.editor_count != null || payload.viewer_count != null) {
          updateEditorCountUi(
            payload.editor_count != null ? payload.editor_count : 1,
            payload.viewer_count != null ? payload.viewer_count : 0
          );
        }
      }
    } catch (_) { /* continue */ }
    wrapEditLifecycle();
    try { paintLockedRows(); } catch (_) {}
    if (typeof window.__rkrkStartLiveShareHeartbeat === "function") window.__rkrkStartLiveShareHeartbeat();
    startCoPolling();
    if (typeof window.__rkrkScheduleLiveSharePush === "function") window.__rkrkScheduleLiveSharePush();
    try { paintLockedRows(); } catch (_) {}
  }

  function wrapEditLifecycle() {
    const getBegin = window.__rkrkGetBeginEdit;
    const setBegin = window.__rkrkSetBeginEdit;
    if (typeof getBegin !== "function" || typeof setBegin !== "function") return;
    const orig = getBegin();
    if (typeof orig !== "function" || orig.__rkrkMultiWrapped) return;
    const wrapped = async function (id) {
      const objs0 = window.__rkrkGetManagedObjects ? window.__rkrkGetManagedObjects() : [];
      const obj0 = Array.isArray(objs0) ? objs0.find((o) => o && Number(o.id) === Number(id)) : null;
      const pointish = !!(obj0 && (obj0.type === "points" || obj0.type === "textfields"
        || (typeof window.__rkrkIsPointLikeManagedObject === "function" && window.__rkrkIsPointLikeManagedObject(obj0))));
      if (lockedByOther(id) && !pointish) {
        const lock = lockInfo(id);
        const who = (lock && lock.name) ? lock.name : "anderer Editor";
        const label = (obj0 && window.__rkrkManagedObjectDisplayName)
          ? window.__rkrkManagedObjectDisplayName(obj0)
          : ("Objekt " + id);
        status("«" + label + "» ist gesperrt (" + who + ").");
        toast("Gesperrt von " + who, 2500);
        return;
      }
      if (isSession()) {
        const prevId = window.__rkrkGetSelectedManagedObjectId
          ? window.__rkrkGetSelectedManagedObjectId()
          : null;
        if (prevId != null && Number(prevId) !== Number(id)) {
          try { await releaseLock(prevId); } catch (_) { /* noop */ }
        }
        const ok = await claimLock(id);
        if (!ok) {
          if (lockedByOther(id) && !pointish) {
            refreshList();
            paintLockedRows();
            return;
          }
        }
        paintLockedRows();
      }
      return orig.apply(this, arguments);
    };
    wrapped.__rkrkMultiWrapped = true;
    setBegin(wrapped);

    const getDel = window.__rkrkGetDeleteManaged;
    const setDel = window.__rkrkSetDeleteManaged;
    if (typeof getDel === "function" && typeof setDel === "function") {
      const origDel = getDel();
      if (typeof origDel === "function" && !origDel.__rkrkMultiWrapped) {
        const wrappedDel = function (id) {
          const objsD = window.__rkrkGetManagedObjects ? window.__rkrkGetManagedObjects() : [];
          const objD = Array.isArray(objsD) ? objsD.find((o) => o && Number(o.id) === Number(id)) : null;
          const pointishD = !!(objD && (objD.type === "points" || objD.type === "textfields"));
          if (lockedByOther(id) && !pointishD) {
            const lock = lockInfo(id);
            status("Löschen blockiert — gesperrt von " + ((lock && lock.name) || "anderem Editor") + ".");
            return;
          }
          try { releaseLock(id); } catch (_) {}
          return origDel.apply(this, arguments);
        };
        wrappedDel.__rkrkMultiWrapped = true;
        setDel(wrappedDel);
      }
    }

    const getFin = window.__rkrkGetFinalizeFixedRef;
    const setFin = window.__rkrkSetFinalizeFixedRef;
    if (typeof getFin === "function" && typeof setFin === "function") {
      const origFin = getFin();
      if (typeof origFin === "function" && !origFin.__rkrkMultiWrapped) {
        const wrappedFin = function () {
          const id = window.__rkrkGetSelectedManagedObjectId ? window.__rkrkGetSelectedManagedObjectId() : null;
          const ok = origFin.apply(this, arguments);
          if (ok && id != null) {
            releaseLock(id).then(() => paintLockedRows()).catch(() => paintLockedRows());
          }
          return ok;
        };
        wrappedFin.__rkrkMultiWrapped = true;
        setFin(wrappedFin);
      }
    }

    const getDisc = window.__rkrkGetDiscardChanges;
    const setDisc = window.__rkrkSetDiscardChanges;
    if (typeof getDisc === "function" && typeof setDisc === "function") {
      const origDisc = getDisc();
      if (typeof origDisc === "function" && !origDisc.__rkrkMultiWrapped) {
        const wrappedDisc = function () {
          const id = window.__rkrkGetSelectedManagedObjectId ? window.__rkrkGetSelectedManagedObjectId() : null;
          const out = origDisc.apply(this, arguments);
          if (id != null) {
            releaseLock(id).then(() => paintLockedRows()).catch(() => paintLockedRows());
          }
          return out;
        };
        wrappedDisc.__rkrkMultiWrapped = true;
        setDisc(wrappedDisc);
      }
    }
  }

  function interceptViewerStart() {
    // share+edit+ADMIN/KOS/INSTR → Co-Editor mit Menü; sonst nur Zuschauen
    const token = getShareTokenFromUrl();
    if (!token || !getShareEditModeFromUrl()) return;
    if (!hasUnlock()) {
      toast("Multi-Editor erfordert URL-Code ADMIN, KOS oder INSTR.", 3500);
      return;
    }
    // Sofort Viewer-Fullscreen beenden, bevor delayed ensureShareViewerMapOnly zuschlägt
    try { if (typeof window.__rkrkSetLiveShareViewerMode === "function") window.__rkrkSetLiveShareViewerMode(false); } catch (_) {}
    try { if (typeof window.__rkrkRestoreShareEditorChrome === "function") window.__rkrkRestoreShareEditorChrome(); } catch (_) {}
    try { if (typeof window.__rkrkReapplyObjSidebar === "function") window.__rkrkReapplyObjSidebar(); } catch (_) {}
    document.documentElement.classList.remove("rkrk-share-viewer");
    document.body.classList.remove("rkrk-share-viewer");
    document.documentElement.classList.add("rkrk-share-coeditor");
    document.body.classList.add("rkrk-share-coeditor");
    document.documentElement.classList.add("rkrk-share-sub");
    document.body.classList.add("rkrk-share-sub");
    try {
      const bc = document.getElementById("liveBroadcastBtn");
      if (bc) {
        bc.style.display = "none";
        bc.setAttribute("hidden", "true");
      }
    } catch (_) { /* noop */ }
    setTimeout(() => {
      startAsCoEditor(token).catch((err) => {
        toast("Multi-Editor fehlgeschlagen: " + ((err && err.message) || String(err)), 4500);
      });
    }, 600);
  }

  function watchQrPanel() {
    const obs = new MutationObserver(() => {
      ensureMultiEditCheckbox();
    });
    obs.observe(document.body, { childList: true, subtree: true });
    ensureMultiEditCheckbox();
  }


  let lastEditorsList = [];

  function isMasterHost() {
    return !!(document.body && document.body.classList.contains("rkrk-share-host")
      && !document.body.classList.contains("rkrk-share-sub"));
  }

  function ensureEditorAdminCss() {
    if (document.getElementById("rkrkEditorAdminCss")) return;
    const style = document.createElement("style");
    style.id = "rkrkEditorAdminCss";
    style.textContent = ""
      + "#liveShareEditorCount{cursor:pointer;}"
      + "#liveShareEditorAdmin{position:fixed;top:60px;right:12px;z-index:140;width:280px;"
      + "background:#fff;color:#0f172a;border:1px solid #cbd5e1;border-radius:12px;"
      + "box-shadow:0 8px 28px rgba(0,0,0,.22);padding:10px 12px;display:none;}"
      + "#liveShareEditorAdmin.show{display:block!important;}"
      + "#liveShareEditorAdmin h3{margin:0 0 8px;font-size:13px;font-weight:700;}"
      + "#liveShareEditorAdmin .ea-list{list-style:none;margin:0;padding:0;max-height:240px;overflow:auto;}"
      + "#liveShareEditorAdmin .ea-list li{display:flex;flex-direction:column;gap:4px;padding:8px 0;"
      + "border-bottom:1px solid #e2e8f0;font-size:12px;}"
      + "#liveShareEditorAdmin .ea-list li:last-child{border-bottom:none;}"
      + "#liveShareEditorAdmin .ea-name{font-weight:600;color:#0f172a;}"
      + "#liveShareEditorAdmin .ea-meta{font-size:10px;color:#64748b;}"
      + "#liveShareEditorAdmin .ea-actions{display:flex;gap:4px;flex-wrap:wrap;}"
      + "#liveShareEditorAdmin .ea-actions button{flex:1;font-size:10px;padding:5px 6px;border-radius:6px;"
      + "border:1px solid #cbd5e1;background:#f8fafc;cursor:pointer;}"
      + "#liveShareEditorAdmin .ea-actions button.danger{background:#fee2e2;border-color:#fecaca;color:#991b1b;}"
      + "#liveShareEditorAdmin .ea-actions button:disabled{opacity:.45;cursor:not-allowed;}"
      + "#liveShareEditorAdmin .ea-empty{font-size:11px;color:#64748b;margin:4px 0 0;}"
      + "#liveShareEditorAdmin .ea-close{position:absolute;top:8px;right:10px;border:none;background:transparent;"
      + "font-size:16px;cursor:pointer;color:#64748b;}"
      + "body.rkrk-share-sub #liveShareEditorAdmin{display:none!important;}";
    document.head.appendChild(style);
  }

  function ensureEditorAdminPanel() {
    ensureEditorAdminCss();
    let panel = document.getElementById("liveShareEditorAdmin");
    if (panel) return panel;
    panel = document.createElement("div");
    panel.id = "liveShareEditorAdmin";
    panel.innerHTML = ""
      + "<button type='button' class='ea-close' title='Schliessen' aria-label='Schliessen'>×</button>"
      + "<h3>Verbundene Editoren</h3>"
      + "<ul class='ea-list' id='liveShareEditorAdminList'></ul>"
      + "<p class='ea-empty' id='liveShareEditorAdminEmpty'>Keine Sub-Editoren verbunden.</p>";
    document.body.appendChild(panel);
    panel.querySelector(".ea-close").addEventListener("click", () => {
      panel.classList.remove("show");
    });
    return panel;
  }

  async function moderateEditor(targetId, action, name) {
    const token = window.__rkrkGetLiveShareToken ? window.__rkrkGetLiveShareToken() : "";
    if (!token) return null;
    const res = await fetch(SHARE_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      cache: "no-store",
      body: JSON.stringify({
        token,
        editor_id: getOrCreateEditorId(),
        editor_name: getEditorName(),
        is_host: true,
        moderate_editor: {
          host_id: getOrCreateEditorId(),
          target_id: String(targetId),
          action: String(action),
          name: String(name || "")
        }
      })
    });
    const payload = await res.json().catch(() => ({}));
    if (!res.ok || !payload.ok) {
      throw new Error((payload && payload.error) ? String(payload.error) : ("HTTP " + res.status));
    }
    if (Array.isArray(payload.editors)) {
      lastEditorsList = payload.editors;
      renderEditorAdminList(payload.editors);
    }
    return payload;
  }

  function renderEditorAdminList(editors) {
    const panel = ensureEditorAdminPanel();
    const list = document.getElementById("liveShareEditorAdminList");
    const empty = document.getElementById("liveShareEditorAdminEmpty");
    if (!list) return;
    const myId = String(getOrCreateEditorId());
    const rows = (Array.isArray(editors) ? editors : []).filter((e) => e && String(e.id) !== myId);
    list.innerHTML = "";
    if (!rows.length) {
      if (empty) empty.style.display = "";
      return;
    }
    if (empty) empty.style.display = "none";
    rows.forEach((ed) => {
      const li = document.createElement("li");
      const mode = String(ed.mode || "active");
      const role = String(ed.role || "sub");
      const name = String(ed.name || ("Editor-" + String(ed.id).slice(-4)));
      const modeLabel = mode === "viewer" ? "nur Share"
        : (mode === "kicked" ? "getrennt" : "Editor");
      li.innerHTML = ""
        + "<div class='ea-name'></div>"
        + "<div class='ea-meta'></div>"
        + "<div class='ea-actions'></div>";
      li.querySelector(".ea-name").textContent = name;
      li.querySelector(".ea-meta").textContent = role === "host" ? "Master (KOS)" : ("Sub · " + modeLabel);
      const actions = li.querySelector(".ea-actions");
      if (role === "host") {
        list.appendChild(li);
        return;
      }
      const btnViewer = document.createElement("button");
      btnViewer.type = "button";
      btnViewer.textContent = mode === "viewer" ? "Wieder Editor" : "Nur Share";
      btnViewer.addEventListener("click", async () => {
        btnViewer.disabled = true;
        try {
          await moderateEditor(ed.id, mode === "viewer" ? "restore" : "viewer", name);
          toast(mode === "viewer" ? (name + ": wieder Editor") : (name + ": nur Share"), 2500);
        } catch (err) {
          toast("Aktion fehlgeschlagen: " + ((err && err.message) || String(err)), 3500);
        } finally {
          btnViewer.disabled = false;
        }
      });
      const btnKick = document.createElement("button");
      btnKick.type = "button";
      btnKick.className = "danger";
      btnKick.textContent = mode === "kicked" ? "Wieder verbinden" : "Trennen";
      btnKick.addEventListener("click", async () => {
        if (mode !== "kicked") {
          if (!window.confirm("Verbindung von «" + name + "» wirklich trennen?")) return;
        }
        btnKick.disabled = true;
        try {
          await moderateEditor(ed.id, mode === "kicked" ? "restore" : "kick", name);
          toast(mode === "kicked" ? (name + ": wieder verbunden") : (name + ": getrennt"), 2500);
        } catch (err) {
          toast("Aktion fehlgeschlagen: " + ((err && err.message) || String(err)), 3500);
        } finally {
          btnKick.disabled = false;
        }
      });
      actions.appendChild(btnViewer);
      actions.appendChild(btnKick);
      list.appendChild(li);
    });
  }

  function openEditorAdminPanel() {
    if (!isMasterHost()) return;
    const panel = ensureEditorAdminPanel();
    renderEditorAdminList(lastEditorsList);
    panel.classList.add("show");
  }

  function wireEditorCountBadgeClick() {
    const badge = ensureEditorCountBadge();
    if (badge.__eaBound) return;
    badge.__eaBound = true;
    badge.addEventListener("click", () => {
      if (!isMasterHost()) return;
      const panel = ensureEditorAdminPanel();
      if (panel.classList.contains("show")) panel.classList.remove("show");
      else openEditorAdminPanel();
    });
  }

  function demoteSelfToViewer(reason) {
    const token = window.__rkrkGetLiveShareToken ? window.__rkrkGetLiveShareToken() : "";
    coEditor = false;
    stopCoPolling();
    document.documentElement.classList.remove("rkrk-share-coeditor");
    document.body.classList.remove("rkrk-share-coeditor");
    document.documentElement.classList.remove("rkrk-share-sub");
    document.body.classList.remove("rkrk-share-sub");
    document.documentElement.classList.remove("rkrk-share-host");
    document.body.classList.remove("rkrk-share-host");
    try {
      if (typeof window.__rkrkSetLiveShareViewerMode === "function") window.__rkrkSetLiveShareViewerMode(true);
    } catch (_) {}
    document.documentElement.classList.add("rkrk-share-viewer");
    document.body.classList.add("rkrk-share-viewer");
    try {
      if (typeof window.__rkrkEnsureShareViewerMapOnly === "function") window.__rkrkEnsureShareViewerMapOnly();
    } catch (_) { /* noop */ }
    try {
      const u = new URL(window.location.href);
      u.searchParams.delete("edit");
      if (token) u.searchParams.set("share", token);
      window.history.replaceState({}, "", u.pathname + u.search + u.hash);
    } catch (_) { /* noop */ }
    toast(reason || "Master hat dich auf Share-only gesetzt.", 4500);
  }

  function applyKickedByMaster() {
    coEditor = false;
    multiEdit = false;
    stopCoPolling();
    try {
      if (typeof window.__rkrkStopLiveShareBroadcast === "function") { /* host only */ }
    } catch (_) {}
    try {
      if (typeof window.__rkrkSetLiveShareActive === "function") window.__rkrkSetLiveShareActive(false);
    } catch (_) {}
    document.documentElement.classList.remove("rkrk-share-coeditor", "rkrk-share-sub", "rkrk-share-host");
    document.body.classList.remove("rkrk-share-coeditor", "rkrk-share-sub", "rkrk-share-host");
    toast("Master hat die Verbindung getrennt.", 5000);
    try {
      const u = new URL(window.location.href);
      u.searchParams.delete("edit");
      u.searchParams.delete("share");
      window.history.replaceState({}, "", u.pathname + u.search + u.hash);
    } catch (_) {}
    setTimeout(() => {
      try { window.location.reload(); } catch (_) {}
    }, 1200);
  }

  function ingestEditorModeration(payload) {
    if (!payload || typeof payload !== "object") return;
    if (Array.isArray(payload.editors) && isMasterHost()) {
      lastEditorsList = payload.editors;
      const panel = document.getElementById("liveShareEditorAdmin");
      if (panel && panel.classList.contains("show")) renderEditorAdminList(payload.editors);
      wireEditorCountBadgeClick();
      const badge = document.getElementById("liveShareEditorCount");
      if (badge && isMasterHost()) {
        badge.title = "Klicken: Sub-Editoren verwalten";
        badge.style.cursor = "pointer";
      }
    }
    const st = String(payload.my_editor_status || "");
    if (payload.kicked || st === "kicked") {
      if (document.body && document.body.classList.contains("rkrk-share-sub")) {
        applyKickedByMaster();
      }
      return;
    }
    if (st === "viewer") {
      if (document.body && document.body.classList.contains("rkrk-share-sub")) {
        demoteSelfToViewer();
      }
    }
  }


  function tryInstall() {
    if (installed) return true;
    if (typeof window.__rkrkGetBeginEdit !== "function") return false;
    installed = true;
    ensureCss();
    enrichPushBody();
    patchBuildShareUrl();
    wrapEditLifecycle();
    observeList();
    watchQrPanel();
    interceptViewerStart();
    wireEditorCountBadgeClick();
    // Host: when broadcast becomes active and checkbox on, poll locks
    setInterval(() => {
      ensureMultiEditCheckbox();
      observeList();
      wrapEditLifecycle();
      const active = window.__rkrkIsLiveShareActive && window.__rkrkIsLiveShareActive();
      const viewer = window.__rkrkIsLiveShareViewer && window.__rkrkIsLiveShareViewer();
      // Host/Master immer pollen, sobald Broadcast laeuft — sonst fehlen Sub-Aenderungen
      if (active && !viewer) {
        if (!coPollTimer) startCoPolling();
      }
    }, 2000);
    return true;
  }

  let tries = 0;
  (function boot() {
    if (tryInstall()) return;
    if (tries++ > 80) return;
    setTimeout(boot, 150);
  })();
})();

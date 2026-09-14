/**
 * Broadcast-Nachrichten: Zeitstempel, Warteschlange, Pflicht-Bestätigung auf Empfänger-Geräten.
 */
(function () {
  "use strict";

  const SHARE_API = "share_drawing.php";
  const ACK_KEY_PREFIX = "rkrk_share_announce_acks_";
  const MAX_ACK_IDS = 80;

  let announceTimer = null;
  let pollBusy = false;
  let knownById = Object.create(null);

  function toast(msg, ms) {
    try {
      if (typeof window.__rkrkShowBroadcastToast === "function") {
        window.__rkrkShowBroadcastToast(msg, ms);
        return;
      }
    } catch (_) { /* noop */ }
    try { console.log(msg); } catch (_) { /* noop */ }
  }

  function getToken() {
    try {
      if (typeof window.__rkrkGetLiveShareToken === "function") {
        const t = String(window.__rkrkGetLiveShareToken() || "").trim().toLowerCase();
        if (/^[a-z0-9]{8,64}$/.test(t)) return t;
      }
    } catch (_) { /* noop */ }
    return "";
  }

  function isViewer() {
    try {
      if (typeof window.__rkrkIsLiveShareViewer === "function") return !!window.__rkrkIsLiveShareViewer();
    } catch (_) { /* noop */ }
    return !!(document.body && document.body.classList.contains("rkrk-share-viewer")
      && !document.body.classList.contains("rkrk-share-coeditor"));
  }

  function isHostActive() {
    try {
      if (typeof window.__rkrkIsLiveShareActive === "function") return !!window.__rkrkIsLiveShareActive();
    } catch (_) { /* noop */ }
    return false;
  }

  function getActorId() {
    try {
      if (typeof window.__rkrkGetMultiEditorId === "function") {
        const ed = String(window.__rkrkGetMultiEditorId() || "").trim();
        if (ed) return ed;
      }
    } catch (_) { /* noop */ }
    try {
      const id = String(localStorage.getItem("rkrk_live_share_viewer_id_v1") || "").trim().toLowerCase();
      if (/^[a-z0-9_-]{8,64}$/.test(id)) return id;
    } catch (_) { /* noop */ }
    return "";
  }

  function getActorName() {
    try {
      if (typeof window.__rkrkGetMultiEditorName === "function") {
        const n = String(window.__rkrkGetMultiEditorName() || "").trim();
        if (n) return n.slice(0, 40);
      }
    } catch (_) { /* noop */ }
    return "Editor";
  }

  function ackStorageKey(token) {
    return ACK_KEY_PREFIX + String(token || "");
  }

  function loadAckedIds(token) {
    try {
      const raw = sessionStorage.getItem(ackStorageKey(token));
      const arr = raw ? JSON.parse(raw) : [];
      if (!Array.isArray(arr)) return [];
      return arr.map((x) => String(x || "")).filter(Boolean);
    } catch (_) {
      return [];
    }
  }

  function saveAckedIds(token, ids) {
    try {
      const uniq = [];
      const seen = Object.create(null);
      (ids || []).forEach((id) => {
        const s = String(id || "");
        if (!s || seen[s]) return;
        seen[s] = 1;
        uniq.push(s);
      });
      while (uniq.length > MAX_ACK_IDS) uniq.shift();
      sessionStorage.setItem(ackStorageKey(token), JSON.stringify(uniq));
    } catch (_) { /* noop */ }
  }

  function isAcked(token, id) {
    return loadAckedIds(token).indexOf(String(id || "")) >= 0;
  }

  function markAcked(token, id) {
    if (!token || !id) return;
    const ids = loadAckedIds(token);
    if (ids.indexOf(String(id)) < 0) ids.push(String(id));
    saveAckedIds(token, ids);
  }

  function formatAnnounceWhen(iso) {
    const t = Date.parse(String(iso || ""));
    if (!Number.isFinite(t)) return "";
    const d = new Date(t);
    const pad = (n) => String(n).padStart(2, "0");
    return pad(d.getDate()) + "." + pad(d.getMonth() + 1) + "." + d.getFullYear()
      + " " + pad(d.getHours()) + ":" + pad(d.getMinutes());
  }

  function ensureCss() {
    if (document.getElementById("rkrkAnnounceCss")) return;
    try {
      if (typeof window.__rkrkEnsureBroadcastCss === "function") window.__rkrkEnsureBroadcastCss();
    } catch (_) { /* noop */ }
    const style = document.createElement("style");
    style.id = "rkrkAnnounceCss";
    style.textContent = ""
      + "#liveShareAnnounceOverlay{position:fixed;inset:0;z-index:10000;display:none;"
      + "align-items:flex-start;justify-content:center;background:rgba(15,23,42,.45);padding:24px 16px;overflow:auto;}"
      + "#liveShareAnnounceOverlay.show{display:flex!important;}"
      + "#liveShareAnnounceStack{width:min(420px,100%);display:flex;flex-direction:column;gap:0;margin-top:8vh;}"
      + "#liveShareAnnounceStack .ann-card{position:relative;background:#fff;color:#0f172a;"
      + "border-radius:14px;border:1px solid #cbd5e1;box-shadow:0 14px 40px rgba(0,0,0,.28);"
      + "padding:16px 14px 12px;margin-top:-10px;}"
      + "#liveShareAnnounceStack .ann-card:first-child{margin-top:0;}"
      + "#liveShareAnnounceStack .ann-card.ann-top{z-index:3;}"
      + "#liveShareAnnounceStack .ann-time{margin:0 0 4px;font-size:11px;font-weight:600;color:#64748b;}"
      + "#liveShareAnnounceStack .ann-from{margin:0 0 8px;font-size:12px;color:#64748b;}"
      + "#liveShareAnnounceStack .ann-text{margin:0 0 12px;font-size:15px;line-height:1.45;white-space:pre-wrap;word-break:break-word;}"
      + "#liveShareAnnounceStack .ann-actions button{width:100%;font-size:14px;font-weight:600;padding:10px 12px;"
      + "border-radius:10px;border:1px solid #527642;background:#96B588;color:#fff;cursor:pointer;}"
      + "#liveShareAnnounceStack .ann-actions button:hover{background:#7ea56f;}"
      + "#liveShareAnnounceStack .ann-pending{margin:0 0 10px;font-size:11px;color:#92400e;font-weight:600;}"
      + "#liveShareAnnounceComposer{position:fixed;top:12px;right:252px;z-index:130;display:none;"
      + "flex-direction:column;gap:8px;width:min(320px,calc(100vw - 24px));"
      + "background:#fff;color:#0f172a;border:1px solid #cbd5e1;border-radius:12px;"
      + "box-shadow:0 8px 28px rgba(0,0,0,.22);padding:10px;}"
      + "#liveShareAnnounceComposer.is-visible{display:flex!important;}"
      + "#liveShareAnnounceComposer.collapsed{width:auto;height:40px;padding:0;margin:0;border:none;"
      + "background:transparent;box-shadow:none;align-items:center;}"
      + "#liveShareAnnounceComposer:not(.collapsed){top:60px;right:12px;height:auto;}"
      + "#liveShareAnnounceComposer.collapsed .ann-compose-body{display:none;}"
      + "#liveShareAnnounceOpenBtn{box-sizing:border-box;height:40px;min-height:40px;max-height:40px;"
      + "padding:0 12px;margin:0;border-radius:999px;border:1px solid #527642;"
      + "background:#fff;color:#527642;font-size:12px;font-weight:600;line-height:1;cursor:pointer;"
      + "display:inline-flex;align-items:center;justify-content:center;"
      + "box-shadow:0 2px 10px rgba(0,0,0,.18);}"
      + "#liveShareAnnounceComposer:not(.collapsed) #liveShareAnnounceOpenBtn{display:none;}"
      + "#liveShareAnnounceComposer .ann-compose-body{display:flex;flex-direction:column;gap:8px;}"
      + "#liveShareAnnounceComposer label{font-size:12px;font-weight:600;color:#334155;}"
      + "#liveShareAnnounceInput{width:100%;min-height:72px;resize:vertical;box-sizing:border-box;"
      + "font:inherit;font-size:13px;line-height:1.4;padding:8px 10px;border-radius:8px;"
      + "border:1px solid #cbd5e1;color:#0f172a;background:#f8fafc;}"
      + "#liveShareAnnounceComposer .ann-compose-actions{display:flex;gap:6px;}"
      + "#liveShareAnnounceComposer .ann-compose-actions button{flex:1;font-size:12px;padding:8px;"
      + "border-radius:8px;border:1px solid #cbd5e1;background:#f8fafc;color:#0f172a;cursor:pointer;}"
      + "#liveShareAnnounceComposer .ann-compose-actions button.primary{background:#96B588;border-color:#527642;color:#fff;}"
      + "#liveShareAnnounceComposer .ann-compose-hint{margin:0;font-size:10px;color:#64748b;}"
      + "body.rkrk-share-viewer:not(.rkrk-share-coeditor) #liveShareAnnounceComposer{display:none!important;}";
    document.head.appendChild(style);
  }

  function pendingFromList(token, list) {
    const myId = getActorId();
    const out = [];
    (list || []).forEach((a) => {
      if (!a || !a.id || !a.text) return;
      if (myId && a.from_id && String(a.from_id) === String(myId)) {
        markAcked(token, a.id);
        return;
      }
      if (isAcked(token, a.id)) return;
      out.push(a);
    });
    return out;
  }

  function renderAnnounceStack(pending) {
    ensureCss();
    let overlay = document.getElementById("liveShareAnnounceOverlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.id = "liveShareAnnounceOverlay";
      overlay.innerHTML = '<div id="liveShareAnnounceStack"></div>';
      document.body.appendChild(overlay);
      overlay.addEventListener("click", (ev) => {
        if (ev.target === overlay) ev.stopPropagation();
      });
    }
    const stack = document.getElementById("liveShareAnnounceStack");
    if (!stack) return;

    if (!pending.length) {
      overlay.classList.remove("show");
      stack.innerHTML = "";
      return;
    }

    // Aelteste unten, neueste oben (uebereinander)
    const ordered = pending.slice().sort((a, b) => {
      const ta = Date.parse(String(a.created_at || "")) || 0;
      const tb = Date.parse(String(b.created_at || "")) || 0;
      return ta - tb;
    });

    stack.innerHTML = "";
    ordered.forEach((a, idx) => {
      const card = document.createElement("div");
      card.className = "ann-card" + (idx === ordered.length - 1 ? " ann-top" : "");
      card.dataset.announceId = String(a.id);
      const when = formatAnnounceWhen(a.created_at);
      const who = String(a.from_name || "Editor").trim() || "Editor";
      const rest = ordered.length - 1 - idx;
      card.innerHTML = ""
        + (rest > 0
          ? ('<p class="ann-pending">' + rest + " weitere Nachricht"
            + (rest === 1 ? "" : "en") + " darunter</p>")
          : "")
        + (when ? ('<p class="ann-time">' + when + "</p>") : "")
        + '<p class="ann-from">Von: ' + who.replace(/</g, "&lt;") + "</p>"
        + '<p class="ann-text"></p>'
        + '<div class="ann-actions"><button type="button" data-ann-ack="1">Bestätigen</button></div>';
      const textEl = card.querySelector(".ann-text");
      if (textEl) textEl.textContent = String(a.text);
      const btn = card.querySelector("[data-ann-ack]");
      if (btn) {
        btn.addEventListener("click", () => {
          const token = getToken();
          markAcked(token, a.id);
          toast("Nachricht bestätigt.", 1800);
          syncAnnounceUiFromKnown();
        });
      }
      stack.appendChild(card);
    });
    overlay.classList.add("show");
  }

  function syncAnnounceUiFromKnown() {
    const token = getToken();
    if (!token) {
      renderAnnounceStack([]);
      return;
    }
    const list = Object.keys(knownById).map((k) => knownById[k]);
    renderAnnounceStack(pendingFromList(token, list));
  }

  function ingestAnnounces(payload) {
    const token = getToken() || (payload && payload.token ? String(payload.token) : "");
    if (!token) return;
    let list = [];
    if (payload && Array.isArray(payload.announces)) list = payload.announces;
    else if (payload && payload.announce) list = [payload.announce];
    list.forEach((a) => {
      if (!a || !a.id) return;
      knownById[String(a.id)] = a;
    });
    syncAnnounceUiFromKnown();
  }

  async function pollOnce() {
    if (pollBusy) return;
    const token = getToken();
    if (!token) {
      updateComposerVisibility();
      return;
    }
    if (!isHostActive() && !isViewer()
      && !(document.body && document.body.classList.contains("rkrk-share-coeditor"))) {
      updateComposerVisibility();
      return;
    }
    pollBusy = true;
    try {
      const res = await fetch(
        SHARE_API + "?token=" + encodeURIComponent(token) + "&announce_poll=1",
        { cache: "no-store" }
      );
      if (!res.ok) return;
      const payload = await res.json().catch(() => ({}));
      if (!payload || !payload.ok) return;
      ingestAnnounces(payload);
    } catch (_) { /* keep watching */ }
    finally {
      pollBusy = false;
      updateComposerVisibility();
    }
  }

  function startWatch() {
    if (announceTimer) return;
    pollOnce();
    announceTimer = setInterval(pollOnce, 700);
  }

  function canSend() {
    const token = getToken();
    if (!token) return false;
    if (document.body && (
      document.body.classList.contains("rkrk-share-coeditor")
      || document.body.classList.contains("rkrk-share-master")
      || document.body.classList.contains("rkrk-share-host")
      || document.body.classList.contains("rkrk-share-subeditor")
    )) return true;
    return isHostActive() && !isViewer();
  }

  function closeComposer() {
    const box = document.getElementById("liveShareAnnounceComposer");
    if (!box) return;
    box.classList.add("collapsed");
    const input = document.getElementById("liveShareAnnounceInput");
    if (input) input.blur();
  }

  function openComposer() {
    ensureComposer();
    if (!canSend()) {
      toast(getToken() ? "Zuschauer können keine Nachricht senden." : "Zuerst Broadcast starten.", 2800);
      return;
    }
    const box = document.getElementById("liveShareAnnounceComposer");
    if (!box) return;
    box.classList.remove("collapsed");
    const input = document.getElementById("liveShareAnnounceInput");
    if (input) {
      setTimeout(() => {
        try { input.focus(); input.select(); } catch (_) { /* noop */ }
      }, 0);
    }
  }

  async function sendAnnounce(text) {
    const msg = String(text || "").trim();
    if (!msg) throw new Error("Nachricht leer");
    const token = getToken();
    if (!token) throw new Error("Kein aktiver Broadcast");
    const createdHint = new Date().toISOString();
    const res = await fetch(SHARE_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        token: token,
        announce: {
          text: msg.slice(0, 500),
          editor_id: getActorId(),
          editor_name: getActorName()
        }
      }),
      cache: "no-store"
    });
    const payload = await res.json().catch(() => ({}));
    if (!res.ok || !payload.ok) {
      throw new Error((payload && payload.error) ? String(payload.error) : ("HTTP " + res.status));
    }
    if (payload.announce && payload.announce.id) {
      markAcked(token, payload.announce.id);
      if (!payload.announce.created_at) payload.announce.created_at = createdHint;
    }
    ingestAnnounces(payload);
    return payload;
  }

  async function submitComposer() {
    if (!canSend()) {
      toast("Senden nicht möglich.", 2500);
      return;
    }
    const input = document.getElementById("liveShareAnnounceInput");
    const cleaned = input ? String(input.value || "").trim() : "";
    if (!cleaned) {
      toast("Bitte Text eingeben.", 2000);
      if (input) input.focus();
      return;
    }
    const sendBtn = document.getElementById("liveShareAnnounceDoSendBtn");
    if (sendBtn) sendBtn.disabled = true;
    try {
      await sendAnnounce(cleaned);
      if (input) input.value = "";
      closeComposer();
      const when = formatAnnounceWhen(new Date().toISOString());
      toast("Nachricht gesendet" + (when ? (" · " + when) : "") + ".", 2800);
    } catch (err) {
      toast("Senden fehlgeschlagen: " + ((err && err.message) || String(err)), 4000);
    } finally {
      if (sendBtn) sendBtn.disabled = false;
    }
  }

  function updateComposerVisibility() {
    ensureComposer();
    const box = document.getElementById("liveShareAnnounceComposer");
    if (!box) return;
    const show = canSend();
    box.classList.toggle("is-visible", show);
    if (!show) box.classList.add("collapsed");
  }

  function ensureComposer() {
    ensureCss();
    let box = document.getElementById("liveShareAnnounceComposer");
    if (box) return box;
    box = document.createElement("div");
    box.id = "liveShareAnnounceComposer";
    box.className = "collapsed";
    box.innerHTML = ""
      + '<button type="button" id="liveShareAnnounceOpenBtn" title="Broadcast-Nachricht schreiben">Nachricht</button>'
      + '<div class="ann-compose-body">'
      + '<label for="liveShareAnnounceInput">Broadcast-Nachricht</label>'
      + '<textarea id="liveShareAnnounceInput" maxlength="500" placeholder="Text eingeben… Empfänger müssen bestätigen."></textarea>'
      + '<p class="ann-compose-hint">Wird mit Datum/Uhrzeit gesendet · Ctrl/Cmd+Enter = senden</p>'
      + '<div class="ann-compose-actions">'
      + '<button type="button" class="primary" id="liveShareAnnounceDoSendBtn">Senden</button>'
      + '<button type="button" id="liveShareAnnounceCancelBtn">Schliessen</button>'
      + "</div></div>";
    document.body.appendChild(box);
    const openBtn = document.getElementById("liveShareAnnounceOpenBtn");
    if (openBtn) openBtn.addEventListener("click", () => openComposer());
    const sendBtn = document.getElementById("liveShareAnnounceDoSendBtn");
    if (sendBtn) sendBtn.addEventListener("click", () => { submitComposer(); });
    const cancelBtn = document.getElementById("liveShareAnnounceCancelBtn");
    if (cancelBtn) cancelBtn.addEventListener("click", () => closeComposer());
    const input = document.getElementById("liveShareAnnounceInput");
    if (input) {
      input.addEventListener("keydown", (ev) => {
        if (ev.key === "Escape") {
          ev.preventDefault();
          closeComposer();
          return;
        }
        if (ev.key === "Enter" && (ev.ctrlKey || ev.metaKey)) {
          ev.preventDefault();
          submitComposer();
        }
      });
    }
    return box;
  }

  function boot() {
    ensureCss();
    ensureComposer();
    updateComposerVisibility();
    startWatch();
  }

  try {
    window.__rkrkIngestShareAnnounces = ingestAnnounces;
    window.__rkrkRefreshAnnounceUi = updateComposerVisibility;
    window.__rkrkOpenShareAnnounceComposer = openComposer;
  } catch (_) { /* noop */ }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
  setInterval(updateComposerVisibility, 1500);
})();

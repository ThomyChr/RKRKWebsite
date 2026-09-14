/**
 * Follow-Fokus (3 Zustaende auf der Schaltflaeche):
 *   ROT   – Sub hat selbst gezoomt/geschwenkt (frei)
 *   WEISS – folgt dem Follow-Fokus-Master
 *   GRUEN – dieser Editor ist Follow-Fokus-Master (alle folgen)
 *
 * Klick: ROT → WEISS (wieder folgen) → GRUEN (Master uebernehmen).
 * Gruen nochmals: Ausschnitt erneut an alle senden.
 */
(function () {
  "use strict";

  const SHARE_API = "share_drawing.php";
  let lastViewEpoch = "";
  let lastMapViewJson = "";
  let viewLeaderId = "";
  let viewLeaderName = "";
  let iAmLeader = false;
  /** true = eigener Ausschnitt, Button rot */
  let detached = false;
  let suppressDetach = false;
  let watchTimer = null;
  let mapBound = false;

  function toast(msg, ms) {
    try {
      if (typeof window.__rkrkShowBroadcastToast === "function") {
        window.__rkrkShowBroadcastToast(msg, ms);
        return;
      }
    } catch (_) { /* noop */ }
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

  /** Sync-Gerät (Share-Viewer): folgen/frei, aber kein Follow-Fokus übernehmen */
  function isSyncDeviceOnly() {
    return isViewer() && !isEditorSession();
  }

  function isHostActive() {
    try {
      if (typeof window.__rkrkIsLiveShareActive === "function") return !!window.__rkrkIsLiveShareActive();
    } catch (_) { /* noop */ }
    return false;
  }

  function isEditorSession() {
    if (isViewer() && !(document.body && document.body.classList.contains("rkrk-share-coeditor"))) {
      return false;
    }
    if (document.body && (
      document.body.classList.contains("rkrk-share-coeditor")
      || document.body.classList.contains("rkrk-share-host")
      || document.body.classList.contains("rkrk-share-master")
    )) return true;
    return isHostActive() && !isViewer();
  }

  function getEditorId() {
    try {
      if (typeof window.__rkrkGetMultiEditorId === "function") {
        const id = String(window.__rkrkGetMultiEditorId() || "").trim();
        if (id) return id;
      }
    } catch (_) { /* noop */ }
    try {
      const id = String(localStorage.getItem("rkrk_live_share_editor_id_v1") || "").trim().toLowerCase();
      if (/^[a-z0-9_-]{8,64}$/.test(id)) return id;
    } catch (_) { /* noop */ }
    return "";
  }

  function getEditorName() {
    try {
      if (typeof window.__rkrkGetMultiEditorName === "function") {
        const n = String(window.__rkrkGetMultiEditorName() || "").trim();
        if (n) return n.slice(0, 40);
      }
    } catch (_) { /* noop */ }
    return "Editor";
  }

  function collectLocalMapView() {
    try {
      if (typeof map === "undefined" || !map || !map.getView) return null;
      const view = map.getView();
      const center = view.getCenter();
      const zoom = view.getZoom();
      if (!Array.isArray(center) || center.length < 2) return null;
      try { map.updateSize(); } catch (_) { /* noop */ }
      const resolution = (typeof view.getResolution === "function") ? Number(view.getResolution()) : NaN;
      const size = (typeof map.getSize === "function") ? map.getSize() : null;
      const dpr = (typeof window !== "undefined" && Number(window.devicePixelRatio)) || 1;
      return {
        center: [Number(center[0]), Number(center[1])],
        zoom: Number(zoom),
        resolution: Number.isFinite(resolution) ? resolution : undefined,
        size: (Array.isArray(size) && size.length >= 2)
          ? [Number(size[0]) || 0, Number(size[1]) || 0]
          : undefined,
        pixelRatio: Number.isFinite(dpr) && dpr > 0 ? dpr : 1
      };
    } catch (_) {
      return null;
    }
  }

  function withSuppressDetach(fn) {
    suppressDetach = true;
    try { window.__rkrkSuppressViewDetach = true; } catch (_) {}
    try { fn(); } catch (_) { /* noop */ }
    clearTimeout(withSuppressDetach._t);
    withSuppressDetach._t = setTimeout(() => {
      suppressDetach = false;
      try { window.__rkrkSuppressViewDetach = false; } catch (_) {}
    }, 800);
  }

  function applyFollowView(drawing, opts) {
    withSuppressDetach(() => {
      try {
        if (typeof window.__rkrkApplySharedMapView === "function") {
          window.__rkrkApplySharedMapView(drawing, opts || { animate: true });
        }
      } catch (_) { /* noop */ }
    });
  }

  /** Button-Modus: leading | following | detached */
  function followUiMode() {
    if (iAmLeader) return "leading";
    if (detached) return "detached";
    return "following";
  }

  function ensureCss() {
    if (document.getElementById("rkrkViewFollowCss")) return;
    try {
      if (typeof window.__rkrkEnsureBroadcastCss === "function") window.__rkrkEnsureBroadcastCss();
    } catch (_) { /* noop */ }
    const style = document.createElement("style");
    style.id = "rkrkViewFollowCss";
    style.textContent = ""
      /* Kreis in der Live-Leiste; Hilfe (?) bleibt rechts aussen bei right:12 */
      + "#liveShareViewFollowBtn{position:fixed;top:12px;right:156px;z-index:131;"
      + "width:40px!important;min-width:40px!important;max-width:40px!important;"
      + "height:40px!important;padding:0!important;margin:0!important;"
      + "border-radius:999px!important;border:1px solid #527642;background:#fff;color:#527642;"
      + "font-size:16px;font-weight:700;line-height:1;cursor:pointer;"
      + "box-shadow:0 2px 10px rgba(0,0,0,.18);"
      + "display:none;align-items:center;justify-content:center;"
      + "transition:background .15s,color .15s,border-color .15s;}"
      + "#liveShareViewFollowBtn.is-visible{display:inline-flex!important;}"
      + "#liveShareViewFollowBtn.is-following{background:#fff;border-color:#527642;color:#527642;}"
      + "#liveShareViewFollowBtn.is-detached{background:#b91c1c;border-color:#7f1d1d;color:#fff;}"
      + "#liveShareViewFollowBtn.is-leader{background:#96B588;border-color:#527642;color:#fff;}"
      + "#liveShareViewFollowBtn:hover{filter:brightness(.97);}"
      + "#liveShareViewFollowBtn:disabled{opacity:.65;cursor:wait;}"
      + "#liveShareViewFollowHint{position:fixed;top:60px;right:60px;z-index:129;display:none;"
      + "max-width:220px;background:rgba(15,23,42,.9);color:#fff;font-size:11px;line-height:1.35;"
      + "padding:6px 10px;border-radius:8px;pointer-events:none;}"
      + "#liveShareViewFollowHint.show{display:block;}"
      + "body.rkrk-share-viewer:not(.rkrk-share-coeditor) #liveShareViewFollowBtn.is-visible{"
      + "display:inline-flex!important;}";
    document.head.appendChild(style);
  }

  function positionFollowBtnBesideNachricht() {
    // Zwischen Broadcast (rechts) und QR — gleiche Hoehe wie Broadcast
    const btn = document.getElementById("liveShareViewFollowBtn");
    const hint = document.getElementById("liveShareViewFollowHint");
    if (!btn) return;
    // Sync-Gerät: Follow links neben Hilfe (?) — Hilfe bleibt immer rechts aussen (right:12)
    if (typeof isSyncDeviceOnly === "function" && isSyncDeviceOnly()) {
      btn.style.right = "60px";
      btn.style.top = "12px";
      btn.style.left = "auto";
      btn.style.width = "40px";
      btn.style.height = "40px";
      btn.style.minWidth = "40px";
      btn.style.maxWidth = "40px";
      if (hint) {
        hint.style.right = "60px";
        hint.style.top = "60px";
      }
      return;
    }
    const bc = document.getElementById("liveBroadcastBtn");
    const qr = document.getElementById("liveQrShowBtn");
    let rightPx = 60;
    let topPx = 12;
    let sizePx = 40;
    try {
      if (bc && bc.getBoundingClientRect) {
        const br = bc.getBoundingClientRect();
        if (br.width > 0 || br.height > 0) {
          topPx = Math.max(0, Math.round(br.top));
          if (br.height > 0) sizePx = Math.round(br.height);
          // Mitte zwischen Broadcast-Linkskante und QR-Rechtskante
          if (qr && qr.getBoundingClientRect) {
            const qrR = qr.getBoundingClientRect();
            if (qrR.width > 0) {
              const gapMid = (br.left + qrR.right) / 2;
              rightPx = Math.max(48, Math.round(window.innerWidth - gapMid - sizePx / 2));
            } else {
              rightPx = Math.max(48, Math.round(window.innerWidth - br.left + 8));
            }
          } else {
            rightPx = Math.max(48, Math.round(window.innerWidth - br.left + 8));
          }
        }
      }
    } catch (_) { /* fallback */ }
    btn.style.right = rightPx + "px";
    btn.style.top = topPx + "px";
    btn.style.left = "auto";
    btn.style.width = sizePx + "px";
    btn.style.height = sizePx + "px";
    btn.style.minWidth = sizePx + "px";
    btn.style.maxWidth = sizePx + "px";
    if (hint) {
      hint.style.right = rightPx + "px";
      hint.style.top = (topPx + sizePx + 6) + "px";
    }
  }

  function updateHint() {
    let hint = document.getElementById("liveShareViewFollowHint");
    if (!hint) {
      hint = document.createElement("div");
      hint.id = "liveShareViewFollowHint";
      document.body.appendChild(hint);
    }
    // Hinweis nur als title am Kreis; kein Dauer-Balken
    hint.classList.remove("show");
  }

  function updateButton() {
    ensureHostEditorIdentity();
    ensureCss();
    let btn = document.getElementById("liveShareViewFollowBtn");
    if (!btn) {
      btn = document.createElement("button");
      btn.id = "liveShareViewFollowBtn";
      btn.type = "button";
      btn.setAttribute("aria-label", "Follow-Fokus");
      btn.addEventListener("click", () => { onFollowButtonClick(); });
      document.body.appendChild(btn);
    }
    const show = !!(getToken() && (isEditorSession() || isViewer()));
    const mode = followUiMode();
    const syncOnly = isSyncDeviceOnly();
    btn.classList.toggle("is-visible", show);
    btn.classList.toggle("is-leader", mode === "leading" && !syncOnly);
    btn.classList.toggle("is-detached", mode === "detached");
    btn.classList.toggle("is-following", mode === "following" || (syncOnly && mode === "leading"));
    // Kreis-Symbol statt Textbalken
    if (mode === "leading" && !syncOnly) {
      btn.textContent = "◉"; // leader
      btn.title = "Follow-Fokus aktiv — Klick: Ausschnitt erneut an alle senden.";
    } else if (mode === "detached") {
      btn.textContent = "●"; // detached
      btn.title = syncOnly
        ? "Eigener Zoom — Klick: wieder dem Broadcast folgen."
        : "Eigener Zoom — Klick: wieder dem Master folgen.";
    } else {
      btn.textContent = "◎"; // following
      btn.title = syncOnly
        ? "Folge dem Broadcast — Klick: eigenen Zoom steuern (ohne Fokus zu uebernehmen)."
        : "Folge dem Master — Klick: Follow-Fokus uebernehmen.";
    }
    positionFollowBtnBesideNachricht();
    updateHint();
  }

  function markDetachedFromUser() {
    if (suppressDetach) return;
    if (!isEditorSession() && !isViewer()) return;
    if (iAmLeader && !isSyncDeviceOnly()) return; // Focus-Master bleibt gruen
    if (detached) return;
    detached = true;
    updateButton();
  }

  function bindMapInteraction() {
    if (mapBound) return;
    try {
      if (typeof map === "undefined" || !map || typeof map.on !== "function") return;
      mapBound = true;
      // Nur echte User-Gesten → rot/detach. NICHT moveend (sonst Kampf mit Follow-Apply).
      map.on("pointerdrag", markDetachedFromUser);
      const vp = typeof map.getViewport === "function" ? map.getViewport() : null;
      if (vp) {
        vp.addEventListener("wheel", markDetachedFromUser, { passive: true });
        vp.addEventListener("touchstart", markDetachedFromUser, { passive: true });
        /* dblclick = Kachelfarbe — nicht für Follow-Detach verwenden */
      }
      // OL-Zoom-Buttons
      try {
        const zooms = document.querySelectorAll(".ol-zoom button");
        zooms.forEach((btn) => {
          btn.addEventListener("click", markDetachedFromUser);
        });
      } catch (_) { /* noop */ }
    } catch (_) { /* map not ready */ }
  }

  function ingestViewLeader(payload) {
    if (!payload || !payload.ok) return;
    const vl = payload.view_leader;
    const id = vl && vl.id ? String(vl.id) : "";
    const name = vl && vl.name ? String(vl.name) : "";
    const epoch = payload.view_epoch != null ? String(payload.view_epoch) : "";
    const my = getEditorId();
    const wasLeader = iAmLeader;
    viewLeaderId = id;
    viewLeaderName = name;
    iAmLeader = !!(my && id && String(my) === String(id));
    try { window.__rkrkIsViewLeader = function () { return !!iAmLeader; }; } catch (_) { /* noop */ }
    try { window.__rkrkGetViewLeaderId = function () { return viewLeaderId; }; } catch (_) { /* noop */ }
    try { window.__rkrkIsViewFollowDetached = function () { return !!detached && !iAmLeader; }; } catch (_) { /* noop */ }

    // Leadership verloren → wieder folgen (weiss), sofern nicht bewusst frei
    if (wasLeader && !iAmLeader) {
      detached = false;
    }
    // Leadership gewonnen → gruen
    if (iAmLeader) {
      detached = false;
    }

    const epochChanged = !!(epoch && epoch !== lastViewEpoch);
    if (epochChanged) lastViewEpoch = epoch;

    const drawing = payload.drawing || (payload.mapView ? { mapView: payload.mapView } : null);
    const mapView = drawing && drawing.mapView ? drawing.mapView : null;
    let mapViewChanged = false;
    if (mapView) {
      try {
        const j = JSON.stringify(mapView);
        mapViewChanged = j !== lastMapViewJson;
        if (mapViewChanged) lastMapViewJson = j;
      } catch (_) {
        mapViewChanged = true;
      }
    }

    if (mapView) {
      if (isViewer()) {
        // Sync-Gerät: folgen, ausser bewusst eigener Zoom (detached)
        if (!detached && (epochChanged || mapViewChanged || !lastViewEpoch)) {
          applyFollowView(drawing, { animate: !!epochChanged });
        }
      } else if (iAmLeader) {
        // Leader steuert — Remote nicht anwenden
      } else if (detached) {
        // ROT: eigenen Zoom behalten — kein Remote-Apply (sonst Zoom-Kampf)
      } else {
        // WEISS: nur bei explizitem Follow-Fokus (Epoch) oder erstem Bind folgen.
        // Nicht bei jedem Host-Push (Resolution/Size) → verhindert Zoom-Jitter im Sub-Editor.
        if (epochChanged || !lastViewEpoch) {
          applyFollowView(drawing, { animate: true, editorSimple: true });
        }
      }
    }

    updateButton();
  }

  async function resumeFollowing() {
    detached = false;
    updateButton();
    toast(isSyncDeviceOnly()
      ? "Folge wieder dem Broadcast."
      : "Folge wieder dem Follow-Fokus-Master.", 2800);
    try {
      const token = getToken();
      if (!token) return;
      const res = await fetch(
        SHARE_API + "?token=" + encodeURIComponent(token) + "&announce_poll=1",
        { cache: "no-store" }
      );
      const payload = await res.json().catch(() => ({}));
      if (res.ok && payload && payload.ok) {
        // Force apply even if mapView JSON unchanged
        lastMapViewJson = "";
        ingestViewLeader(payload);
        if (payload.mapView || (payload.drawing && payload.drawing.mapView)) {
          applyFollowView(
            payload.drawing || { mapView: payload.mapView },
            { animate: true, editorSimple: true }
          );
        }
      }
    } catch (_) { /* noop */ }
    updateButton();
  }

  async function claimFollowFocus() {
    ensureHostEditorIdentity();
    const token = getToken();
    if (!token) {
      toast("Zuerst Broadcast starten / verbinden.", 2800);
      return;
    }
    if (!isEditorSession()) {
      toast("Nur Editoren koennen den Follow-Fokus uebernehmen.", 2800);
      return;
    }
    const mapView = collectLocalMapView();
    if (!mapView) {
      toast("Kartenausschnitt nicht lesbar.", 2500);
      return;
    }
    const btn = document.getElementById("liveShareViewFollowBtn");
    if (btn) btn.disabled = true;
    try {
      const res = await fetch(SHARE_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          token: token,
          claim_view_leader: {
            editor_id: getEditorId(),
            editor_name: getEditorName(),
            mapView: mapView
          }
        }),
        cache: "no-store"
      });
      const payload = await res.json().catch(() => ({}));
      if (!res.ok || !payload.ok) {
        throw new Error((payload && payload.error) ? String(payload.error) : ("HTTP " + res.status));
      }
      detached = false;
      ingestViewLeader(payload);
      toast("Follow-Fokus bei dir — alle folgen deinem Ausschnitt.", 3200);
      try {
        if (typeof window.__rkrkScheduleLiveSharePush === "function") window.__rkrkScheduleLiveSharePush();
      } catch (_) { /* noop */ }
    } catch (err) {
      toast("Follow-Fokus fehlgeschlagen: " + ((err && err.message) || String(err)), 4000);
    } finally {
      if (btn) btn.disabled = false;
      updateButton();
    }
  }

  async function onFollowButtonClick() {
    const mode = followUiMode();
    if (mode === "detached") {
      await resumeFollowing();
      return;
    }
    // Sync-Gerät: nur zwischen Folgen und eigenem Zoom — Fokus nie übernehmen
    if (isSyncDeviceOnly()) {
      markDetachedFromUser();
      toast("Eigener Zoom — Klick: wieder dem Broadcast folgen.", 2800);
      return;
    }
    // following (weiss) oder leading (gruen): Fokus claimen / erneut senden
    await claimFollowFocus();
  }

  async function pollViewLeader() {
    bindMapInteraction();
    const token = getToken();
    if (!token) {
      updateButton();
      return;
    }
    if (!isHostActive() && !isViewer() && !isEditorSession()) {
      updateButton();
      return;
    }
    try {
      const res = await fetch(
        SHARE_API + "?token=" + encodeURIComponent(token) + "&announce_poll=1",
        { cache: "no-store" }
      );
      if (!res.ok) return;
      const payload = await res.json().catch(() => ({}));
      if (!payload || !payload.ok) return;
      ingestViewLeader(payload);
    } catch (_) { /* keep watching */ }
  }

  function startWatch() {
    if (watchTimer) return;
    pollViewLeader();
    watchTimer = setInterval(pollViewLeader, 1200);
  }

  function installFetchGuard() {
    if (window.__rkrkViewFollowFetchPatched) return;
    window.__rkrkViewFollowFetchPatched = true;
    const origFetch = window.fetch.bind(window);
    window.fetch = async function (input, init) {
      try {
        const url = String(typeof input === "string" ? input : (input && input.url) || "");
        if (url.indexOf("share_drawing.php") >= 0 && init && init.method
            && String(init.method).toUpperCase() === "POST" && init.body) {
          const body = JSON.parse(init.body);
          if (body && body.drawing && !body.claim_view_leader) {
            const my = getEditorId();
            const leader = viewLeaderId || (typeof window.__rkrkGetViewLeaderId === "function"
              ? window.__rkrkGetViewLeaderId() : "");
            const amLeader = !!(my && leader && String(my) === String(leader));
            const hostDefault = isHostActive() && !isViewer() && !leader;
            if (!amLeader && !hostDefault && !body.claim_view_leader) {
              if (body.drawing.mapView) delete body.drawing.mapView;
              init = Object.assign({}, init, { body: JSON.stringify(body) });
            }
          }
          const res = await origFetch(input, init);
          try {
            const clone = res.clone();
            const payload = await clone.json();
            if (payload && payload.ok) ingestViewLeader(payload);
          } catch (_) { /* noop */ }
          return res;
        }
      } catch (_) { /* fall through */ }
      return origFetch(input, init);
    };
  }

  function installSoftPullGuard() {
    try {
      window.__rkrkShouldFollowSharedMapView = function () {
        if (detached) return false;
        if (isViewer()) return true;
        // Editor: nur im weissen Folge-Modus
        if (iAmLeader) return false;
        return isEditorSession();
      };
    } catch (_) { /* noop */ }
  }

  function ensureHostEditorIdentity() {
    if (!isHostActive() || isViewer()) return;
    try {
      if (typeof window.__rkrkGetMultiEditorId === "function" && window.__rkrkGetMultiEditorId()) return;
    } catch (_) { /* noop */ }
    try {
      let id = String(localStorage.getItem("rkrk_live_share_editor_id_v1") || "").trim().toLowerCase();
      if (!/^[a-z0-9_-]{8,64}$/.test(id)) {
        const rnd = (typeof crypto !== "undefined" && crypto.getRandomValues)
          ? Array.from(crypto.getRandomValues(new Uint8Array(8)), (b) => b.toString(16).padStart(2, "0")).join("")
          : String(Date.now());
        id = ("h" + rnd).replace(/[^a-z0-9_-]/g, "").slice(0, 24);
        localStorage.setItem("rkrk_live_share_editor_id_v1", id);
      }
      window.__rkrkGetMultiEditorId = function () { return id; };
      if (typeof window.__rkrkGetMultiEditorName !== "function") {
        window.__rkrkGetMultiEditorName = function () { return "KOS"; };
      }
    } catch (_) { /* noop */ }
  }

  function boot() {
    ensureCss();
    ensureHostEditorIdentity();
    installFetchGuard();
    installSoftPullGuard();
    bindMapInteraction();
    updateButton();
    startWatch();
    window.addEventListener("resize", () => {
      try { positionFollowBtnBesideNachricht(); } catch (_) {}
    });
    setInterval(() => {
      bindMapInteraction();
      updateButton();
    }, 2000);
  }

  try {
    window.__rkrkClaimViewFollowFocus = claimFollowFocus;
    window.__rkrkIngestViewLeader = ingestViewLeader;
    window.__rkrkIsViewLeader = function () { return !!iAmLeader; };
    window.__rkrkIsViewFollowDetached = function () { return !!detached && !iAmLeader; };
    window.__rkrkResumeViewFollow = resumeFollowing;
  } catch (_) { /* noop */ }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();

/**
 * Environment-based branding for the RKR map / overlay editor.
 * Mode A (RKRS): tools.rehkitzrettung.ch OR pathname contains /rkrs/
 * Mode B (RKRK): everything else
 *
 * Branding images live in the local images/ folder next to the map.
 */
(function (global) {
  "use strict";

  var ASSET_VER = "20260722g";

  function brandImagesBase() {
    return "images/";
  }

  function brandAsset(file) {
    const base = brandImagesBase().replace(/\/?$/, "/");
    let name = String(file || "").replace(/\\/g, "/");
    if (name.indexOf("images/") === 0) name = name.slice("images/".length);
    name = name.replace(/^\/+/, "");
    const encoded = name.split("/").map(encodeURIComponent).join("/");
    return base + encoded;
  }

  function withVer(href) {
    if (!href) return href;
    return href + (href.indexOf("?") >= 0 ? "&" : "?") + "v=" + ASSET_VER;
  }

  function detectBrandEnv() {
    const host = String((global.location && location.hostname) || "").toLowerCase();
    const path = String((global.location && location.pathname) || "").toLowerCase();
    const isToolsHost = host === "tools.rehkitzrettung.ch" || host.endsWith(".tools.rehkitzrettung.ch")
      || host.indexOf("tools.rehkitzrettung.ch") !== -1;
    const isPortalHost = host === "portal.rehkitzrettung.ch" || host.endsWith(".portal.rehkitzrettung.ch")
      || host.indexOf("portal.rehkitzrettung.ch") !== -1;
    const isRkrsPath = path.indexOf("/rkrs/") !== -1;
    let hasRkrsQuery = false;
    try {
      const p = new URLSearchParams((global.location && location.search) || "");
      hasRkrsQuery = p.has("RKRS") || p.has("RKRSplus") || p.has("RKRSprev");
    } catch (_) { /* noop */ }
    const inj = (global.__RKR_FAVICON__ && typeof global.__RKR_FAVICON__ === "object") ? global.__RKR_FAVICON__ : null;
    const chromeRkrs = !!(inj && inj.chromeRkrs) || isPortalHost || isToolsHost || isRkrsPath || hasRkrsQuery;
    const isRkrs = isToolsHost || isRkrsPath;

    function rkropenFaviconDir() {
      const parts = String((global.location && location.pathname) || "").replace(/\\/g, "/").split("/").filter(Boolean);
      if (isPortalHost) {
        const j = parts.findIndex(function (s) { return String(s).toLowerCase() === "rkrsplusassessment"; });
        return (j >= 0 ? "/" + parts.slice(0, j + 1).join("/") : "/rkrsplusassessment") + "/favicon/";
      }
      const i = parts.findIndex(function (s) { return String(s).toLowerCase() === "rkropen"; });
      return (i >= 0 ? "/" + parts.slice(0, i + 1).join("/") : "/rkropen") + "/favicon/";
    }
    function slotFromPath() {
      if (/\/test27(\/|$)/.test(path)) return "test27";
      if (/\/test(\/|$)/.test(path)) return "test";
      if (/\/v2(\/|$)/.test(path)) return "v2";
      if (/\/ex(\/|$)/.test(path)) return "extern";
      if (/\/v5(\/|$)/.test(path)) return "partner";
      if (/\/pub(\/|$)/.test(path)) return "pub";
      return "pub";
    }
    function slotFavicons() {
      // tools is a map-only deploy: RKRS.ico lives in ./images, not /rkropen/favicon.
      if (isToolsHost) {
        const ico = brandAsset("RKRS.ico");
        return { faviconIco: ico, faviconPng: null, appleTouch: ico };
      }
      if (inj && inj.ico) {
        return {
          faviconIco: inj.ico,
          faviconPng: inj.png || null,
          appleTouch: inj.apple || inj.png || null
        };
      }
      let dir = rkropenFaviconDir();
      const slot = (inj && inj.slot) || slotFromPath();
      if (!chromeRkrs) {
        if (slot === "test27" || slot === "extern") dir += "Reh_FA840B_orange/";
        else if (slot === "test") dir += "Reh_FFABAB_red/";
        else if (slot === "v2") dir += "Reh_AFCEEB_blue/";
        else if (slot === "partner") dir += "Reh_885833_brown/";
      }
      const enc = function (file) {
        return dir + String(file).split("/").map(encodeURIComponent).join("/");
      };
      if (chromeRkrs) {
        const ico = host.indexOf("tchr-frog.synology.me") !== -1 ? "RKRS_onrkrk.ico" : "RKRS.ico";
        return { faviconIco: enc(ico), faviconPng: enc("RKRS_onrkrs.png"), appleTouch: enc("RKRS_onrkrs.png") };
      }
      return {
        faviconIco: enc("rkrk.ico"),
        faviconPng: enc("favicon-32x32.png"),
        appleTouch: enc("apple-icon-180x180.png")
      };
    }

    const icons = slotFavicons();
    const theme = chromeRkrs ? "rkrs" : "rkrk";
    const productTitle = isRkrs ? "RKRS Overlay-Editor" : "RKRK Overlay-Editor";

    if (isRkrs) {
      const faviconIco = isToolsHost
        ? brandAsset("RKRS.ico")
        : (icons.faviconIco || brandAsset("RKRS_onrkrs.ico"));
      const logoPng = brandAsset("RKRS Logo rund.png");
      return {
        mode: "rkrs",
        theme: theme,
        isRkrs: true,
        isToolsHost: !!isToolsHost,
        isRkrsPath: !!isRkrsPath,
        imagesBase: brandImagesBase(),
        copyright: "rehkitzrettung.ch",
        title: productTitle,
        heading: productTitle,
        faviconIco: faviconIco,
        faviconPng: icons.faviconPng,
        appleTouch: icons.appleTouch,
        logoSrc: logoPng,
        logoAlt: "RKRS",
        mapInlaySrc: logoPng,
        mapInlayAlt: "RKRS"
      };
    }

    const rkrkLogoPng = brandAsset("RKRK Logo grey.png");
    return {
      mode: "rkrk",
      theme: theme,
      isRkrs: false,
      isToolsHost: false,
      isRkrsPath: false,
      imagesBase: brandImagesBase(),
      copyright: chromeRkrs ? "rehkitzrettung.ch" : "drohnenrehkitzrettung.ch",
      title: productTitle,
      heading: productTitle,
      faviconIco: icons.faviconIco || brandAsset("rkrk.ico"),
      faviconPng: icons.faviconPng || brandAsset("rkrk-favicon-32x32.png"),
      appleTouch: icons.appleTouch || brandAsset("rkrk-apple-icon-180x180.png"),
      logoSrc: rkrkLogoPng,
      logoAlt: "RKRK",
      mapInlaySrc: rkrkLogoPng,
      mapInlayAlt: "RKRK"
    };
  }

  function overlayEditorProductName(optionalBrand) {
    const brand = optionalBrand || global.__RKR_BRAND__;
    if (brand && typeof brand.isRkrs === "boolean") {
      return brand.isRkrs ? "RKRS Overlay-Editor" : "RKRK Overlay-Editor";
    }
    if (brand && brand.heading) return String(brand.heading);
    if (global.__ON_RKRS__) return "RKRS Overlay-Editor";
    try {
      return detectBrandEnv().heading;
    } catch (_) {
      return "RKRK Overlay-Editor";
    }
  }

  function setFaviconLinks(brand) {
    const head = document.head || document.getElementsByTagName("head")[0];
    if (!head) return;

    Array.from(head.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"], link[rel="apple-touch-icon"]'))
      .forEach((el) => el.parentNode && el.parentNode.removeChild(el));

    const add = (rel, href, attrs) => {
      if (!href) return;
      const link = document.createElement("link");
      link.rel = rel;
      link.href = withVer(href);
      if (attrs) Object.keys(attrs).forEach((k) => link.setAttribute(k, attrs[k]));
      head.appendChild(link);
    };

    add("icon", brand.faviconIco, { type: "image/x-icon" });
    if (brand.faviconPng) add("icon", brand.faviconPng, { type: "image/png", sizes: "32x32" });
    if (brand.appleTouch) add("apple-touch-icon", brand.appleTouch, { sizes: "180x180" });
  }

  function ensureBrandHeader(brand) {
    const panel = document.querySelector("aside.panel");
    if (!panel) return;
    let h1 = panel.querySelector("h1");
    if (!h1) {
      h1 = document.createElement("h1");
      panel.insertBefore(h1, panel.firstChild);
    }
    h1.textContent = brand.heading;

    let wrap = document.getElementById("rkrBrandHeader");
    if (!wrap) {
      wrap = document.createElement("div");
      wrap.id = "rkrBrandHeader";
      wrap.className = "rkr-brand-header";
      h1.parentNode.insertBefore(wrap, h1);
      wrap.appendChild(h1);
    }

    let logo = document.getElementById("rkrBrandLogo");
    if (brand.logoSrc) {
      if (!logo) {
        logo = document.createElement("img");
        logo.id = "rkrBrandLogo";
        logo.className = "rkr-brand-logo";
        wrap.insertBefore(logo, h1);
      }
      logo.src = withVer(brand.logoSrc);
      logo.alt = brand.logoAlt || "";
      logo.style.display = "";
    } else if (logo) {
      logo.style.display = "none";
    }
  }

  function buildCopyrightInnerHtml(brand) {
    const year = new Date().getFullYear();
    const mail = "thomas.christen@rehkitzrettung.ch";
    const feedbackLabel = "R\u00fcckmeldung";
    const mailSvg = '<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true"><path fill="currentColor" d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5L4 8V6l8 5 8-5v2z"/></svg>';
    return ""
      + "<span class='rkr-copyright-text'>\u00a9 " + year + " " + String((brand && brand.copyright) || "") + "</span>"
      + "<a class='rkr-copyright-mail' href='mailto:" + mail + "?subject=" + encodeURIComponent("R\u00fcckmeldung " + overlayEditorProductName(brand)) + "' "
      + "title='" + feedbackLabel + " an " + mail + "' aria-label='" + feedbackLabel + " per E-Mail an " + mail + "'>"
      + mailSvg
      + "</a>";
  }

  function ensureCopyright(brand) {
    const panel = document.querySelector("aside.panel");
    if (panel) {
      let el = document.getElementById("rkrCopyright");
      if (!el) {
        el = document.createElement("p");
        el.id = "rkrCopyright";
        el.className = "rkr-copyright small";
        panel.appendChild(el);
      }
      el.innerHTML = buildCopyrightInnerHtml(brand);
    }
    ensureMapCopyright(brand);
  }

  function ensureMapCopyright(brand, retry) {
    const host = document.body || document.documentElement;
    if (!host) {
      const n = Number(retry) || 0;
      if (n < 40) {
        setTimeout(function () {
          try { ensureMapCopyright(brand, n + 1); } catch (_) {}
        }, 120);
      }
      return;
    }
    let el = document.getElementById("rkrMapCopyright");
    if (!el) {
      el = document.createElement("div");
      el.id = "rkrMapCopyright";
      el.className = "rkr-map-copyright";
      host.appendChild(el);
    } else if (el.parentNode !== host) {
      host.appendChild(el);
    }
    el.innerHTML = buildCopyrightInnerHtml(brand || global.__RKR_BRAND__ || {});
    // Sync-Gerät: Mailto explizit oeffnen (OL/Touch soll Link nicht schlucken)
    try {
      const a = el.querySelector("a.rkr-copyright-mail");
      if (a && !a.__rkrMailBound) {
        a.__rkrMailBound = true;
        a.addEventListener("click", function (ev) {
          try {
            ev.preventDefault();
            ev.stopPropagation();
            const href = a.getAttribute("href") || "";
            if (href) window.location.href = href;
          } catch (_) { /* noop */ }
        }, true);
      }
    } catch (_) { /* noop */ }
  }
  try { global.ensureRkrMapCopyright = ensureMapCopyright; } catch (_) {}

  function ensureBrandCss() {
    if (document.getElementById("rkrBrandCss")) return;
    const style = document.createElement("style");
    style.id = "rkrBrandCss";
    style.textContent = ""
      + ".rkr-brand-header{display:flex;align-items:center;gap:10px;margin:0 0 10px;}"
      + ".rkr-brand-header h1{margin:0;flex:1;line-height:1.25;}"
      + ".rkr-brand-logo{width:40px;height:40px;border-radius:50%;object-fit:contain;flex:0 0 auto;}"
      + ".rkr-copyright{margin-top:18px;padding-top:10px;border-top:1px solid #e4e4e4;color:#666;font-size:11px;display:flex;align-items:center;justify-content:space-between;gap:8px;}"
      + ".rkr-copyright-text{min-width:0;}"
      + ".rkr-copyright-mail{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:4px;color:#527642;text-decoration:none;border:1px solid transparent;}"
      + ".rkr-copyright-mail:hover,.rkr-copyright-mail:focus-visible{color:#3f5933;background:#f3f6f1;border-color:#c7d7bf;outline:none;}"
      + ".rkr-copyright-mail svg{display:block;pointer-events:none;}"
      + "body.rkr-brand-rkrs aside.panel button{background:#96B588!important;color:#fff!important;border:1px solid #527642!important;}"
      + "body.rkr-brand-rkrs aside.panel button:hover{background:#7ea56f!important;}"
      + "body.rkr-brand-rkrs aside.panel button.selected-flight-reference,"
      + "body.rkr-brand-rkrs aside.panel button:active{background:#48673A!important;border-color:#3f5933!important;color:#fff!important;}"
      + "#rkrMapCopyright,.rkr-map-copyright{display:none;position:fixed;left:8px;bottom:8px;z-index:160;align-items:center;gap:6px;padding:4px 8px;border-radius:6px;background:rgba(255,255,255,.9);border:1px solid rgba(0,0,0,.14);color:#475569;font:500 10px/1.2 Arial,Helvetica,sans-serif;box-shadow:0 1px 6px rgba(0,0,0,.16);pointer-events:auto!important;max-width:calc(100vw - 24px);}"
      + "body.rkrk-share-viewer #rkrMapCopyright,body.rkrk-share-viewer .rkr-map-copyright,html.rkrk-share-viewer #rkrMapCopyright{display:inline-flex!important;}"
      + "#rkrMapCopyright .rkr-copyright-text{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}"
      + "#rkrMapCopyright .rkr-copyright-mail{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:4px;color:#527642;text-decoration:none;border:1px solid transparent;}"
      + "#rkrMapCopyright .rkr-copyright-mail:hover,#rkrMapCopyright .rkr-copyright-mail:focus-visible{color:#3f5933;background:#f3f6f1;border-color:#c7d7bf;outline:none;}"
      + "#rkrMapCopyright .rkr-copyright-mail svg{display:block;pointer-events:none;}"
      + "@media print{#rkrMapCopyright{display:none!important;}}"
      + "#map{position:relative;}"
      + "#rkrMapInlay{position:absolute;right:12px;bottom:12px;z-index:5;pointer-events:none;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:8px 10px;min-width:88px;border-radius:10px;background:rgba(255,255,255,0.92);border:1px solid rgba(0,0,0,0.18);box-shadow:0 2px 10px rgba(0,0,0,0.28),0 0 0 1px rgba(255,255,255,0.65) inset;}"
      + "#rkrMapInlay img{display:block;width:64px;height:64px;object-fit:contain;filter:drop-shadow(0 1px 2px rgba(0,0,0,0.35));}"
      + "#rkrMapInlayTime{display:block;margin:0;padding:0;font:600 11px/1.25 Arial,Helvetica,sans-serif;color:#111827;text-align:center;white-space:nowrap;}"
      + "@media print{#rkrMapInlay{display:none!important;}}";
    (document.head || document.documentElement).appendChild(style);
  }


  function ensureMapCopyrightCss() {
    let style = document.getElementById("rkrMapCopyrightCss");
    if (!style) {
      style = document.createElement("style");
      style.id = "rkrMapCopyrightCss";
      (document.head || document.documentElement).appendChild(style);
    }
    style.textContent = ""
      + "#rkrMapCopyright,.rkr-map-copyright{display:none;position:fixed;left:8px;bottom:8px;z-index:160;"
      + "align-items:center;gap:6px;padding:4px 8px;border-radius:6px;"
      + "background:rgba(255,255,255,.9);border:1px solid rgba(0,0,0,.14);"
      + "color:#475569;font:500 10px/1.2 Arial,Helvetica,sans-serif;"
      + "box-shadow:0 1px 6px rgba(0,0,0,.16);pointer-events:auto!important;max-width:calc(100vw - 24px);}"
      + "body.rkrk-share-viewer #rkrMapCopyright,body.rkrk-share-viewer .rkr-map-copyright,"
      + "html.rkrk-share-viewer #rkrMapCopyright{display:inline-flex!important;}"
      + "#rkrMapCopyright .rkr-copyright-text{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}"
      + "#rkrMapCopyright .rkr-copyright-mail{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;"
      + "width:18px;height:18px;border-radius:4px;color:#527642;text-decoration:none;border:1px solid transparent;}"
      + "#rkrMapCopyright .rkr-copyright-mail:hover,#rkrMapCopyright .rkr-copyright-mail:focus-visible{"
      + "color:#3f5933;background:#f3f6f1;border-color:#c7d7bf;outline:none;}"
      + "#rkrMapCopyright .rkr-copyright-mail svg{display:block;pointer-events:none;}"
      + "@media print{#rkrMapCopyright{display:none!important;}}";
  }

  try { global.ensureMapCopyrightCss = ensureMapCopyrightCss; } catch (_) {}

  function formatMapInlayDateTime(d) {

    const dt = d instanceof Date ? d : new Date();
    const pad = function (n) { return String(n).padStart(2, "0"); };
    return pad(dt.getDate()) + "." + pad(dt.getMonth() + 1) + "." + dt.getFullYear()
      + " " + pad(dt.getHours()) + ":" + pad(dt.getMinutes());
  }
  function refreshMapInlayTime() {
    const el = document.getElementById("rkrMapInlayTime");
    if (el) el.textContent = formatMapInlayDateTime(new Date());
  }

  function ensureMapInlay(brand) {
    const mapEl = document.getElementById("map");
    if (!mapEl || !brand || !brand.mapInlaySrc) return;
    let box = document.getElementById("rkrMapInlay");
    if (!box) {
      box = document.createElement("div");
      box.id = "rkrMapInlay";
      box.setAttribute("aria-hidden", "true");
      const img = document.createElement("img");
      img.id = "rkrMapInlayImg";
      img.alt = "";
      img.decoding = "async";
      box.appendChild(img);
      const timeEl = document.createElement("p");
      timeEl.id = "rkrMapInlayTime";
      box.appendChild(timeEl);
      mapEl.appendChild(box);
    } else if (!document.getElementById("rkrMapInlayTime")) {
      const timeEl = document.createElement("p");
      timeEl.id = "rkrMapInlayTime";
      box.appendChild(timeEl);
    }
    const img = document.getElementById("rkrMapInlayImg");
    if (!img) return;
    const src = withVer(brand.mapInlaySrc);
    if (img.getAttribute("data-src") !== src) {
      img.setAttribute("data-src", src);
      img.src = src;
    }
    img.alt = brand.mapInlayAlt || "";
    refreshMapInlayTime();
    if (!global.__rkrMapInlayTimeTimer) {
      global.__rkrMapInlayTimeTimer = setInterval(function () {
        try { refreshMapInlayTime(); } catch (_) {}
      }, 30000);
    }
  }
  function applyBrandEnv(optionalBrand) {
    const brand = optionalBrand || detectBrandEnv();
    global.__RKR_BRAND__ = brand;
    global.__ON_RKRS__ = !!brand.isRkrs;
    try {
      document.title = brand.title;
      if (document.body) {
        document.body.classList.toggle("rkr-brand-rkrs", !!brand.isRkrs);
        document.body.classList.toggle("rkr-brand-rkrk", !brand.isRkrs);
      }
      try {
        const root = document.documentElement;
        if (root && brand.theme) root.setAttribute("data-rkrk-theme", String(brand.theme));
      } catch (_) { /* noop */ }
      ensureBrandCss();
      ensureMapCopyrightCss();
      setFaviconLinks(brand);
      ensureBrandHeader(brand);
      ensureCopyright(brand);
      ensureMapInlay(brand);
    } catch (_) { /* noop */ }
    return brand;
  }

  global.detectRkrBrandEnv = detectBrandEnv;
  global.applyRkrBrandEnv = applyBrandEnv;
  global.rkrOverlayEditorTitle = overlayEditorProductName;
  global.refreshMapInlayTime = refreshMapInlayTime;
  global.formatMapInlayDateTime = formatMapInlayDateTime;
  global.rkrBrandAsset = brandAsset;
  global.rkrBrandImagesBase = brandImagesBase;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () { applyBrandEnv(); });
  } else {
    applyBrandEnv();
  }
})(typeof window !== "undefined" ? window : this);

<?php
// Wetteransicht: Tagesdaten und 14-Tage-Ausblick (Browser lädt die Daten)
// Enthält Wetter-, KP- und GNSS-Anzeige inklusive kritischer Schwellen/Markierungen.
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');

CheckIPandExit();
headerpage("Drohnenrehkitzrettung.ch", false);
GrantAccess();

$currentAccessLevel = '';
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
  $currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo['Login']['Level'])) {
  $currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
}
$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast');
if ($showTopMenu) {
  CreateTopMenu();
  echo "<br><br>";
}
?>
<body>
<style>
    .weather-app { font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif; margin: 18px; max-width: 1200px; }
    .weather-app .muted{color:#666}
    .weather-app .row{display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end}
    .weather-app label{display:flex; flex-direction:column; font-size:.92em; gap:6px}
    .weather-app input,.weather-app select{padding:10px 12px;border:1px solid #ccc;border-radius:10px}
    .weather-app button{padding:10px 14px;border:1px solid #ccc;border-radius:10px;background:#fafafa;cursor:pointer}
    .weather-app button:hover{background:#f0f0f0}
    .weather-app .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
    /* Detailkarten: 1–3 Spalten je nach Bildschirmbreite */
    .weather-app .detail-grid{display:grid;gap:12px;margin-top:12px}
    @media (min-width: 1100px){ .weather-app .detail-grid{grid-template-columns:repeat(3,1fr)} }
    @media (min-width: 740px) and (max-width: 1099px){ .weather-app .detail-grid{grid-template-columns:repeat(2,1fr)} }
    @media (max-width: 739px){ .weather-app .detail-grid{grid-template-columns:1fr} }
    .weather-app .hint{font-size:.85em;color:#666;margin-top:6px}
    body.drohnenwetter .weather-app #rawWrap{opacity:.95}
    /* URL-Layouts: ?drohnenwetterBlende (ausblenden) oder ?ansicht=drohnenwetter (drohnen view) */
    body.drohnenwetterBlende .weather-app #smallDay{display:none},
    body.drohnenwetterBlende .weather-app #rawWrap,
    body.drohnenwetter .weather-app #smallDay,
    body.drohnenwetter .weather-app #rawWrap{display:none}

    /* Detaillierte Tagesansicht: bei breitem Bildschirm doppelt breit + zweispaltig */
    .weather-app #detailDay .kvgrid{display:grid;grid-template-columns:1fr}
    @media (min-width: 1100px){
      body.layout-new .weather-app #detailDay{grid-column:span 2}
      body.layout-new .weather-app #detailDay .kvgrid{grid-template-columns:1fr 1fr; column-gap:18px}
    }


    @media (max-width: 900px){ .weather-app .grid{grid-template-columns:1fr} }
    .weather-app .card{border:1px solid #ddd;border-radius:12px;padding:14px;background:#fff}
    .weather-app .title{font-weight:700;margin-bottom:10px}
    .weather-app .kv{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px dashed #eee}
    .weather-app .kv:last-child{border-bottom:none}
    .weather-app .k{color:#444}
    .weather-app .v{font-weight:650;text-align:right}
    .weather-app .pill{display:inline-block;padding:2px 10px;border-radius:999px;border:1px solid #ddd;font-size:.9em}
    .weather-app .good{background:#e9fbef;border-color:#bfeccd}
    .weather-app .warn{background:#fff5db;border-color:#f0d18b}
    .weather-app .bad {background:#ffe5e5;border-color:#f2a6a6}
    .weather-app .temp-cold{color:#c26a00}
    .weather-app .temp-freezing{color:#b00020}
    .weather-app .scroll{overflow:auto}
    .weather-app table{border-collapse:collapse;min-width:920px;width:100%}
    .weather-app th,.weather-app td{border:1px solid #e7e7e7;padding:8px 10px;white-space:nowrap}
    .weather-app th{background:#fafafa;text-align:left}
    .weather-app pre{background:#0b1020;color:#d7e1ff;padding:12px;border-radius:12px;overflow:auto;max-height:420px}
    .weather-app .error{color:#8a1f1f;font-weight:650}

    @media print {
      /* Kritische Werte auch im Ausdruck sichtbar halten */
      .weather-app .pill, .weather-app .good, .weather-app .warn, .weather-app .bad, .weather-app .temp-cold, .weather-app .temp-freezing {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .weather-app .warn { color:#9a5600; border-color:#cf9f39; }
      .weather-app .bad { color:#8f1624; border-color:#d97f7f; }
      .weather-app .temp-cold { color:#9a5600; font-weight:700; }
      .weather-app .temp-freezing { color:#8f1624; font-weight:700; }
    }
  </style>

<div id="weatherApp" class="weather-app">
<h1>Wetter – Tagesansicht & 14 Tage</h1>
<p class="muted">
  Ort per Name oder Lat/Lon wählen, Datum und Zeit setzen, dann laden.
</p>

<div class="card">
  <div class="row">
    <label>
      Ort (Name)
      <input id="place" value="Knonau" placeholder="z.B. Knonau">
    </label>

    <label>
      oder Lat
      <input id="lat" value="47.2235" placeholder="47.2235">
    </label>

    <label>
      Lon
      <input id="lon" value="8.46197" placeholder="8.46197">
    </label>

    <label>
      Datum
      <input id="date" type="date">
    </label>

    <label>
      Zeit
      <input id="time" type="time" step="60">
    </label>

    <button id="load">Daten laden</button>
	<button type="button" onclick="rkrk_copyShareLink()">Link kopieren</button>

    <span id="status" class="muted"></span>
  </div>

  <div class="muted" style="margin-top:10px">
    Tipp: Bei Ort per Name werden die Koordinaten automatisch ermittelt.
  </div>
</div>

<div style="height:12px"></div>
<div id="outlook14" class="card"></div>

<div style="height:12px"></div>
<div id="today3h" class="card"></div>

<div style="height:12px"></div>
<div id="dayDetails" class="detail-grid">
  <div id="smallDay" class="card"></div>
  <div id="detailDay" class="card"></div>
  <div id="droneDay" class="card"></div>
</div>

<div style="height:12px"></div>
<div id="rawWrap" class="card">
  <div class="title">RAW Daten (Array/Objekt)</div>
  <div class="muted">Das komplette Objekt ist auch in <code>window.weatherData</code> verfügbar.</div>
  <pre id="rawOut">{}</pre>
</div>
</div>

<script>
// Satelliten-Orbit-Berechnung (SGP4)
// CDN: https://github.com/shashwatak/satellite-js
</script>
<script src="https://unpkg.com/satellite.js@5.0.0/dist/satellite.min.js"></script>
<script>
// --- URL-Parameter einlesen (ohne Defaults zu überschreiben) ---
function rkrk_applyUrlParams_noDefaultOverwrite() {
  const params = new URLSearchParams(window.location.search);

  // Aliases: place oder q
  const place = params.get('place') ?? params.get('q');
  const lat   = params.get('lat');
  const lon   = params.get('lon');

  // date/time oder datetime
  let date = params.get('date');
  let time = params.get('time');
  const datetime = params.get('datetime');

  if (datetime && (!date || !time)) {
    // akzeptiert "YYYY-MM-DDTHH:MM" (oder ISO mit Sekunden/Zone am Ende)
    const m = String(datetime).match(/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/);
    if (m) { date = date ?? m[1]; time = time ?? m[2]; }
  }

  // >>> Diese IDs/Selektoren ggf. auf deine tatsächlichen Felder anpassen <<<
  const elPlace = document.getElementById('place') || document.getElementById('ort');
  const elLat   = document.getElementById('lat')   || document.getElementById('latitude');
  const elLon   = document.getElementById('lon')   || document.getElementById('lng') || document.getElementById('longitude');
  const elDate  = document.getElementById('date')  || document.getElementById('datum');
  const elTime  = document.getElementById('time')  || document.getElementById('zeit');

  // Priorität: lat/lon > place (wie in deiner Beschreibung: Ort ODER Lat/Lon) [1](https://onedrive.live.com?cid=22BFEB0FDC41B4C8&id=22BFEB0FDC41B4C8!s882e6eb549234bfb9e70689378a1d9a2)
  const hasLatLon = (lat !== null && lon !== null && lat !== '' && lon !== '');

  // NUR überschreiben, wenn Parameter vorhanden sind:
  if (hasLatLon) {
    if (elLat) elLat.value = lat;
    if (elLon) elLon.value = lon;

    // Optional: nur leeren, wenn place explizit da war (sonst nicht anfassen)
    if (place && elPlace) elPlace.value = '';
  } else if (place) {
    if (elPlace) elPlace.value = place;
  }

  if (date && elDate) elDate.value = date;
  if (time && elTime) elTime.value = time;

  // autoload nur signalisieren (nicht automatisch irgendwas ändern)
  const autoload = params.get('autoload');
  const doAutoload = (autoload === '1' || autoload === 'true');

  return { doAutoload };
}


/* ==========================================================
   - Open-Meteo Forecast vars inkl. cloud_cover, visibility, precipitation_probability, sunrise/sunset etc. [1](https://dev.to/0012303/open-meteo-has-a-free-api-get-weather-data-for-any-location-without-an-api-key-2028)[2](https://www.codexworld.com/how-to/html5-geolocation-not-working-chrome/)[5](https://www.swpc.noaa.gov/products/planetary-k-index-dashboard-test)
   - Geocoding API endpoint: https://geocoding-api.open-meteo.com/v1/search [3](https://www.omi.me/blogs/api-guides/how-to-fetch-weather-data-using-open-meteo-api-in-javascript)
   - NOAA Kp feed: planetary_k_index_1m.json [4](https://api.spectator.earth/)[6](https://developers.google.com/maps/documentation/geolocation/overview)
   ========================================================== */

const WMO = new Map(Object.entries({
  0:  ["☀️","Klarer Himmel"],
  1:  ["🌤️","Überwiegend klar"],
  2:  ["⛅","Teilweise bewölkt"],
  3:  ["☁️","Bedeckt"],
  45: ["🌫️","Nebel"],
  48: ["🌫️❄️","Eisnebel"],
  51: ["🌦️","Nieselregen"],
  61: ["🌦️","Regen (leicht)"],
  63: ["🌧️","Regen (mäßig)"],
  65: ["🌧️💧","Regen (stark)"],
  80: ["🌦️","Regenschauer (leicht)"],
  81: ["🌧️","Regenschauer"],
  82: ["🌧️⛈️","Regenschauer (kräftig)"],
  71: ["🌨️","Schnee"],
  95: ["⛈️","Gewitter"],
}));

function wmoLabel(code){
  const key = String(code ?? "");
  return WMO.get(key) || ["❔", `Unbekannt (${code ?? ""})`];
}

function windCompass(deg){
  if (deg == null || Number.isNaN(Number(deg))) return "—";
  const dirs = ["N","NNO","NO","ONO","O","OSO","SO","SSO","S","SSW","SW","WSW","W","WNW","NW","NNW"];
  const idx = Math.round(((Number(deg) % 360) / 22.5)) % 16;
  return dirs[idx];
}

function kpCategory(kp){
  if (kp == null || Number.isNaN(Number(kp))) return {label:"unbekannt", cls:"pill"};
  kp = Number(kp);
  if (kp <= 2) return {label:"ruhig / leicht aktiv", cls:"pill good"};
  if (kp <= 5) return {label:"aktiv", cls:"pill good"};
  if (kp <= 7) return {label:"stürmisch", cls:"pill warn"};
  return {label:"stark stürmisch", cls:"pill bad"};
}

function setStatus(msg, isErr=false){
  const s = document.getElementById("status");
  s.className = isErr ? "error" : "muted";
  s.textContent = msg;
}

function isoFromDateTime(dateStr, timeStr){
  if (!dateStr) return null;
  const t = timeStr && timeStr.length >= 4 ? timeStr : "09:00";
  return `${dateStr}T${t}`;
}

async function geocodePlace(name){
  const url = `https://geocoding-api.open-meteo.com/v1/search?name=${encodeURIComponent(name)}&count=1&language=de&format=json`; // [3](https://www.omi.me/blogs/api-guides/how-to-fetch-weather-data-using-open-meteo-api-in-javascript)
  const res = await fetch(url);
  if (!res.ok) throw new Error(`Geocoding HTTP ${res.status}`);
  const j = await res.json();
  const r = j?.results?.[0];
  if (!r) throw new Error("Ort nicht gefunden");
  return { lat: r.latitude, lon: r.longitude, name: r.name, country: r.country, admin1: r.admin1 };
}


// Reverse Geocoding für Lat/Lon -> "in der Nähe von ..." (Open-Meteo bietet aktuell kein Reverse-Geocoding)
// Nominatim Reverse API: https://nominatim.openstreetmap.org/reverse?lat=..&lon=..&format=json&addressdetails=1
async function reverseGeocode(lat, lon){
  const url = `https://nominatim.openstreetmap.org/reverse?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}&format=json&addressdetails=1&accept-language=de`;
  const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
  if (!res.ok) throw new Error(`Reverse-Geocoding HTTP ${res.status}`);
  const j = await res.json();
  const a = j?.address || {};
  const locality = a.city || a.town || a.village || a.municipality || a.county || a.state || null;
  const country = a.country || null;
  const label = [locality, country].filter(Boolean).join(', ');
  return label || (j?.display_name ? String(j.display_name).split(',').slice(0,2).join(', ') : null);
}

function debounce(fn, ms=600){
  let t;
  return (...args)=>{
    clearTimeout(t);
    t=setTimeout(()=>fn(...args), ms);
  };
}

let _suppressAuto = false;
let _lastMode = 'place'; // 'place' | 'coords'

async function onPlaceChanged(){
  if (_suppressAuto) return;
  const place = document.getElementById('place').value.trim();
  if (!place || place.length < 2) return;
  _lastMode = 'place';
  try{
    setStatus('Suche Ort…');
    const g = await geocodePlace(place);
    _suppressAuto = true;
    document.getElementById('lat').value = g.lat;
    document.getElementById('lon').value = g.lon;
    _suppressAuto = false;
    const label = `${g.name}${g.admin1 ? ', '+g.admin1 : ''}${g.country ? ', '+g.country : ''}`;
    setStatus(`OK – ${label}`);
  } catch(e){
    setStatus(e.message || String(e), true);
    _suppressAuto = false;
  }
}

async function onCoordsChanged(){
  if (_suppressAuto) return;
  const lat = parseFloat(document.getElementById('lat').value);
  const lon = parseFloat(document.getElementById('lon').value);
  if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;
  _lastMode = 'coords';
  try{
    setStatus('Suche Ortschaft in der Nähe…');
    const near = await reverseGeocode(lat, lon);
    if (near){
      _suppressAuto = true;
      document.getElementById('place').value = `in der Nähe von ${near}`;
      _suppressAuto = false;
      setStatus(`OK – in der Nähe von ${near}`);
    } else {
      setStatus('OK');
    }
  } catch(e){
    // Fallback: nicht hart fehlschlagen, Wetter funktioniert auch ohne Ortsname
    setStatus('Hinweis: Ortschaft konnte nicht aufgelöst werden (Wetterdaten funktionieren trotzdem).');
  } finally {
    _suppressAuto = false;
  }
}

function pickLatestKp(rows, valueReader, timeReader, startIndex = 0){
  if (!Array.isArray(rows) || rows.length <= startIndex) return null;
  let fallback = null;
  for (let i = rows.length - 1; i >= startIndex; i--){
    const row = rows[i];
    const kp = Number.parseFloat(valueReader(row));
    if (!Number.isFinite(kp) || kp < 0 || kp > 9) continue;
    const candidate = { kp, time_utc: timeReader(row) ?? null };
    // 0 kann vorkommen, ist aber oft Placeholder am Feed-Ende -> >0 bevorzugen
    if (kp > 0) return candidate;
    if (!fallback) fallback = candidate;
  }
  return fallback;
}

async function fetchKp(targetIso){
  // KP aus NOAA 3-Day Forecast (3h-Bins), passend zur Forecast-Anzeige.
  try {
    const parsedTarget = targetIso ? Date.parse(String(targetIso)) : Number.NaN;
    const targetMs = Number.isFinite(parsedTarget) ? parsedTarget : Date.now();
    const r1 = await fetch("https://services.swpc.noaa.gov/products/noaa-planetary-k-index-forecast.json", { cache: "no-store" });
    if (r1.ok){
      const j1 = await r1.json();
      if (Array.isArray(j1) && j1.length){
        const entries = [];
        for (let i = 0; i < j1.length; i++){
          const row = j1[i];
          let t = null;
          let kpRaw = null;
          if (Array.isArray(row)){
            t = row[0] ?? null;
            kpRaw = row[1] ?? null;
          } else if (row && typeof row === "object"){
            t = row.time_tag ?? row[0] ?? null;
            kpRaw = row.Kp ?? row.kp ?? row.kp_index ?? row[1] ?? null;
          }
          const kp = Number.parseFloat(kpRaw);
          if (!Number.isFinite(kp) || kp < 0 || kp > 9) continue;
          const ts = String(t || "").trim();
          if (!ts) continue;
          const ms = Date.parse(ts.endsWith("Z") ? ts : `${ts}Z`);
          if (!Number.isFinite(ms)) continue;
          entries.push({ kp, ms, time_utc: ts });
        }
        if (entries.length){
          entries.sort((a,b)=>a.ms-b.ms);
          const next = entries.find(e => e.ms >= targetMs);
          const pick = next || entries[entries.length - 1];
          return { kp: pick.kp, time_utc: pick.time_utc, category: kpCategory(pick.kp) };
        }
      }
    }
  } catch(e){}

  return { kp: null, time_utc: null, category: kpCategory(null) };
}

async function fetchKpForecast(){
  // NOAA 3-day forecast (Kp, 3-hour bins)
  const url = "https://services.swpc.noaa.gov/products/noaa-planetary-k-index-forecast.json";
  const res = await fetch(url);
  if (!res.ok) throw new Error(`KP Forecast HTTP ${res.status}`);
  const rows = await res.json();
  if (!Array.isArray(rows) || rows.length < 1) return { byDate: {}, byIso: {}, entries: [], maxKp: null };

  const byDate = {};
  const byIso = {};
  const entries = [];
  let maxKp = null;

  for (let i = 0; i < rows.length; i++){
    const r = rows[i];
    let t = null;
    let kpRaw = null;
    if (Array.isArray(r)){
      t = r[0] ?? null;
      kpRaw = r[1] ?? null;
    } else if (r && typeof r === "object"){
      t = r.time_tag ?? r[0] ?? null;
      kpRaw = r.Kp ?? r.kp ?? r.kp_index ?? r[1] ?? null;
    }
    const v = parseFloat(kpRaw);
    if (!isFinite(v)) continue;
    // NOAA liefert teils UTC-Strings ohne Zeitzonenmarker.
    // Direktes Date-Parsen kann den Tag verschieben -> robust per String extrahieren.
    const ts = String(t || "").trim();
    if (!ts) continue;
    const isoKey = ts.slice(0,16);
    let dateKey = null;
    const m = ts.match(/^(\d{4}-\d{2}-\d{2})/);
    if (m) {
      dateKey = m[1];
    } else {
      const d = new Date(ts);
      if (!isFinite(d.getTime())) continue;
      dateKey = d.toISOString().slice(0,10);
    }
    const ms = Date.parse(ts.endsWith("Z") ? ts : `${ts}Z`);

    byDate[dateKey] = Math.max(byDate[dateKey] ?? -Infinity, v);
    byIso[isoKey] = v;
    if (Number.isFinite(ms)) entries.push({ ms, kp: v, iso: isoKey });
    maxKp = (maxKp == null) ? v : Math.max(maxKp, v);
  }

  Object.keys(byDate).forEach(k => {
    if (!isFinite(byDate[k])) delete byDate[k];
  });
  entries.sort((a,b)=>a.ms-b.ms);

  return { byDate, byIso, entries, maxKp };
}

function resolveKpForDateTime(targetIso, kpForecast, currentKp, maxKp){
  const map = kpForecast?.byIso || {};
  const target = String(targetIso || "");
  const targetKey = target.slice(0,16);
  const direct = map[targetKey];
  if (direct != null && Number.isFinite(Number(direct))) return Number(direct);

  const dateOnly = target.slice(0,10);
  const dayAt0900 = `${dateOnly}T09:00`;
  const at0900 = map[dayAt0900];
  if (at0900 != null && Number.isFinite(Number(at0900))) return Number(at0900);

  const entries = kpForecast?.entries || [];
  const targetMs = Date.parse(target);
  if (entries.length && Number.isFinite(targetMs)){
    let best = null;
    let bestDist = Number.POSITIVE_INFINITY;
    for (const e of entries){
      const dist = Math.abs(e.ms - targetMs);
      if (dist < bestDist){
        bestDist = dist;
        best = e;
      }
    }
    if (best) return Number(best.kp);
  }

  const byDate = kpForecast?.byDate || {};
  const directDay = byDate[dateOnly];
  if (directDay != null && Number.isFinite(Number(directDay))) return Number(directDay);

  const keys = Object.keys(byDate);
  if (keys.length){
    const dayMs = Date.parse(`${dateOnly}T00:00:00Z`);
    let bestVal = null;
    let bestDist = Number.POSITIVE_INFINITY;
    for (const k of keys){
      const ms = Date.parse(`${k}T00:00:00Z`);
      const v = Number(byDate[k]);
      if (!Number.isFinite(ms) || !Number.isFinite(v)) continue;
      const dist = Math.abs(ms - dayMs);
      if (dist < bestDist){
        bestDist = dist;
        bestVal = v;
      }
    }
    if (bestVal != null) return bestVal;
  }

  if (currentKp != null && Number.isFinite(Number(currentKp))) return Number(currentKp);
  if (maxKp != null && Number.isFinite(Number(maxKp))) return Number(maxKp);
  return null;
}

async function fetchWeather(lat, lon){
  // Added: cloud_cover (Bewölkung) + visibility (Sichtweite) + precipitation_probability (Regenwahrscheinlichkeit) [1](https://dev.to/0012303/open-meteo-has-a-free-api-get-weather-data-for-any-location-without-an-api-key-2028)[2](https://www.codexworld.com/how-to/html5-geolocation-not-working-chrome/)
  const url =
    `https://api.open-meteo.com/v1/forecast` +
    `?latitude=${encodeURIComponent(lat)}` +
    `&longitude=${encodeURIComponent(lon)}` +
    `&timezone=auto` +
    `&forecast_days=14` +
    `&current=temperature_2m,relative_humidity_2m,dew_point_2m,weather_code,wind_speed_80m,wind_direction_10m,wind_gusts_10m,visibility,precipitation_probability,cloud_cover` +
    `&hourly=temperature_2m,relative_humidity_2m,dew_point_2m,weather_code,wind_speed_80m,wind_direction_10m,precipitation_probability,visibility,cloud_cover` +
    `&daily=sunrise,sunset,temperature_2m_max,temperature_2m_min,weather_code`;
  const res = await fetch(url);
  if (!res.ok) throw new Error(`Weather HTTP ${res.status}`);
  return await res.json();
}

function parseTleText(tleText){
  const lines = String(tleText || "")
    .split(/\r?\n/)
    .map(s => s.trim())
    .filter(Boolean);
  const out = [];
  let i = 0;
  while (i < lines.length){
    const a = lines[i] || "";
    const b = lines[i+1] || "";
    const c = lines[i+2] || "";

    if (/^1\s/.test(a) && /^2\s/.test(b)){
      out.push({ name: "GNSS", l1: a, l2: b });
      i += 2;
      continue;
    }
    if (c && /^1\s/.test(b) && /^2\s/.test(c)){
      out.push({ name: a || "GNSS", l1: b, l2: c });
      i += 3;
      continue;
    }
    i += 1;
  }
  return out;
}

function estimateGnssVisibleSatellites(){
  // Wie rkrk_checklist.php: konservative GPS-dominierte Schätzung (keine TLE-Berechnung)
  const elevationMaskDeg = 10.0;
  const totalGnssSat = 31.0;
  const fraction = (1.0 - Math.sin(elevationMaskDeg * Math.PI / 180)) / 2.0;
  const estimate = Math.round(totalGnssSat * fraction * 0.9);
  const count = Math.max(8, Math.min(16, estimate));
  return { count, elevation_mask_deg: elevationMaskDeg, source: "checklist-estimate" };
}

async function fetchGnssVisibleCount(lat, lon, targetIso){
  const when = targetIso ? new Date(targetIso) : new Date();
  const est = estimateGnssVisibleSatellites();
  return {
    ...est,
    time_iso: Number.isFinite(when.getTime()) ? when.toISOString() : null
  };
}

async function fetchGnssVisibleByDates(lat, lon, dateKeys){
  const byDate = {};
  const est = estimateGnssVisibleSatellites();
  if (!window.satellite || !Array.isArray(dateKeys) || !dateKeys.length) {
    for (const day of (dateKeys || [])) byDate[day] = est.count;
    return {
      byDate,
      elevation_mask_deg: est.elevation_mask_deg,
      source: est.source,
      time_local: "09:00"
    };
  }

  const tleUrl = "https://celestrak.org/NORAD/elements/gp.php?GROUP=gnss&FORMAT=tle";
  const res = await fetch(tleUrl, { cache: "no-store" });
  if (!res.ok){
    for (const day of dateKeys) byDate[day] = est.count;
    return {
      byDate,
      elevation_mask_deg: est.elevation_mask_deg,
      source: est.source,
      time_local: "09:00"
    };
  }
  const tleText = await res.text();
  const sats = parseTleText(tleText);
  if (!sats.length){
    for (const day of dateKeys) byDate[day] = est.count;
    return {
      byDate,
      elevation_mask_deg: est.elevation_mask_deg,
      source: est.source,
      time_local: "09:00"
    };
  }

  const obs = {
    longitude: Number(lon) * Math.PI / 180,
    latitude: Number(lat) * Math.PI / 180,
    height: 0.2
  };
  const minElevationDeg = 10;
  for (const day of dateKeys){
    const when = new Date(`${day}T09:00`);
    if (!Number.isFinite(when.getTime())) {
      byDate[day] = est.count;
      continue;
    }
    const gmst = window.satellite.gstime(when);
    let visible = 0;
    for (const t of sats){
      let satrec;
      try {
        satrec = window.satellite.twoline2satrec(t.l1, t.l2);
      } catch(e){
        continue;
      }
      const pv = window.satellite.propagate(satrec, when);
      if (!pv || !pv.position) continue;
      const ecf = window.satellite.eciToEcf(pv.position, gmst);
      const look = window.satellite.ecfToLookAngles(obs, ecf);
      const elDeg = look.elevation * 180 / Math.PI;
      if (Number.isFinite(elDeg) && elDeg >= minElevationDeg) visible++;
    }
    byDate[day] = visible;
  }

  return {
    byDate,
    elevation_mask_deg: minElevationDeg,
    source: "celestrak+sgp4",
    time_local: "09:00"
  };
}

function findHourlyIndex(hourlyTimes, targetIso){
  if (!hourlyTimes?.length || !targetIso) return 0;
  const exact = hourlyTimes.indexOf(targetIso);
  if (exact >= 0) return exact;
  const targetMs = Date.parse(targetIso);
  let best = 0;
  for (let i=0;i<hourlyTimes.length;i++){
    const ms = Date.parse(hourlyTimes[i]);
    if (!Number.isNaN(ms) && ms >= targetMs) { best = i; break; }
  }
  return best;
}

/** Index in Open-Meteo hourly.time für lokales Kalenderdatum + Stunde (z. B. 09:00), sonst -1. */
function hourlyRowIndexForDayHour(day, hourInt, hourlyTimes){
  if (!Array.isArray(hourlyTimes) || !day) return -1;
  const hh = String(hourInt).padStart(2, '0');
  const candidates = [`${day}T${hh}:00`, `${day}T${hh}:00:00`];
  for (const c of candidates){
    const ix = hourlyTimes.indexOf(c);
    if (ix >= 0) return ix;
  }
  const pfx = `${day}T`;
  for (let i = 0; i < hourlyTimes.length; i++){
    const t = String(hourlyTimes[i] ?? '');
    if (!t.startsWith(pfx)) continue;
    const m = t.match(/T(\d{2}):/);
    if (m && m[1] === hh) return i;
  }
  return -1;
}

function buildWeatherData({placeInfo, weatherJson, kpJson, kpForecast, gnssInfo, gnssForecast, targetIso}){
  const cur = weatherJson.current || {};
  const h = weatherJson.hourly || {};
  const d = weatherJson.daily || {};
  const idx = findHourlyIndex(h.time, targetIso);

  const [iconT, descT] = wmoLabel(h.weather_code?.[idx] ?? cur.weather_code);
  const windDirDeg = h.wind_direction_10m?.[idx] ?? cur.wind_direction_10m;

  const todayKey = (targetIso || cur.time || "").slice(0,10);
  let di = 0;
  if (Array.isArray(d.time)) {
    const found = d.time.indexOf(todayKey);
    if (found >= 0) di = found;
  }

  const snapshot = {
    time: h.time?.[idx] ?? targetIso ?? cur.time ?? null,
    weather_code: h.weather_code?.[idx] ?? cur.weather_code ?? null,
    icon: iconT,
    description: descT,
    temp_c: h.temperature_2m?.[idx] ?? cur.temperature_2m ?? null,
    humidity_pct: h.relative_humidity_2m?.[idx] ?? cur.relative_humidity_2m ?? null,
    dewpoint_c: h.dew_point_2m?.[idx] ?? cur.dew_point_2m ?? null,
    precip_prob_pct: h.precipitation_probability?.[idx] ?? cur.precipitation_probability ?? null,
    visibility_m: h.visibility?.[idx] ?? cur.visibility ?? null,
    cloud_cover_pct: h.cloud_cover?.[idx] ?? cur.cloud_cover ?? null,
    wind_80m_kmh: h.wind_speed_80m?.[idx] ?? cur.wind_speed_80m ?? null,
    wind_dir_deg: windDirDeg ?? null,
    wind_dir_compass: windCompass(windDirDeg),
    wind_gusts_kmh: cur.wind_gusts_10m ?? null
  };

  const sun = {
    sunrise: d.sunrise?.[di] ?? null,
    sunset:  d.sunset?.[di]  ?? null,
    tmax:    d.temperature_2m_max?.[di] ?? null,
    tmin:    d.temperature_2m_min?.[di] ?? null,
    day_icon: wmoLabel(d.weather_code?.[di] ?? null)[0],
    day_desc: wmoLabel(d.weather_code?.[di] ?? null)[1],
  };

  return {
    meta: {
      requested: { targetIso, lat: placeInfo.lat, lon: placeInfo.lon, place: placeInfo.label },
      timezone: weatherJson.timezone,
      generatedAt: new Date().toISOString()
    },
    place: placeInfo,
    kp: kpJson,
    kp_forecast: kpForecast || { byDate: {}, maxKp: null },
    weather_raw: weatherJson,
    snapshot,
    sun,
    gnss_visible_satellites: gnssInfo || null,
    gnss_forecast: gnssForecast || { byDate: {} }
  };
}

/* =========================
   RENDER VIEWS
   ========================= */
function renderSmallDay(data){
  const s = data.snapshot;
  const sun = data.sun;
  const kp = data.kp;
  const kpPill = `<span class="${kp.category.cls}">${kp.category.label}</span>`;
  const forecastMax = data.kp_forecast?.maxKp;
  const fCat = kpCategory(forecastMax);

  return `
    <div class="title">Kleine Tagesansicht</div>
    <div class="kv"><span class="k">Ort</span><span class="v">${escapeHtml(data.place.label)}</span></div>
    <div class="kv"><span class="k">Zeit</span><span class="v">${escapeHtml(fmtDateTime(s.time))}</span></div>
    <div class="kv"><span class="k">Wetter</span><span class="v">${s.icon} ${escapeHtml(s.description)}</span></div>
    <div class="kv"><span class="k">Temp</span><span class="v">${fmtTemperature(s.temp_c)}</span></div>
    <div class="kv"><span class="k">Regen‑W’keit</span><span class="v">${fmt(s.precip_prob_pct)} %</span></div>
    <div class="kv"><span class="k">Wind (80m)</span><span class="v">${fmt(s.wind_80m_kmh)} km/h ${escapeHtml(s.wind_dir_compass)}</span></div>
    <div class="kv"><span class="k">Sonnenaufgang</span><span class="v">${fmtTime(sun.sunrise)}</span></div>
    <div class="kv"><span class="k">Sonnenuntergang</span><span class="v">${fmtTime(sun.sunset)}</span></div>
    <div class="kv"><span class="k">KP (aktuell)</span><span class="v">${fmt(kp.kp)} ${kpPill}</span></div>
    <div class="kv"><span class="k">Aurora‑Forecast (max)</span><span class="v">${fmt(forecastMax)} <span class="${fCat.cls}">${fCat.label}</span></span></div>
  `;
}

function renderDetailDay(data){
  const s = data.snapshot;
  const sun = data.sun;
  const kp = data.kp;

  return `
    <div class="title">Detaillierte Tagesansicht</div>
    <div class="kvgrid">
    <div class="kv"><span class="k">Wetter</span><span class="v">${s.icon} ${escapeHtml(s.description)}</span></div>
    <div class="kv"><span class="k">Temperatur</span><span class="v">${fmtTemperature(s.temp_c)}</span></div>
    <div class="kv"><span class="k">Luftfeuchte</span><span class="v">${fmt(s.humidity_pct)} %</span></div>
    <div class="kv"><span class="k">Taupunkt</span><span class="v">${fmt(s.dewpoint_c)} °C</span></div>
    <div class="kv"><span class="k">Bewölkung</span><span class="v">${fmt(s.cloud_cover_pct)} %</span></div>
    <div class="kv"><span class="k">Sichtweite</span><span class="v">${fmtKmFromM(s.visibility_m)} km</span></div>
    <div class="kv"><span class="k">Niederschlag (W’keit)</span><span class="v">${fmt(s.precip_prob_pct)} %</span></div>
    <div class="kv"><span class="k">Wind (80m)</span><span class="v">${fmt(s.wind_80m_kmh)} km/h</span></div>
    <div class="kv"><span class="k">Windrichtung</span><span class="v">${fmt(s.wind_dir_deg)}° (${escapeHtml(s.wind_dir_compass)})</span></div>
    <div class="kv"><span class="k">Windböen</span><span class="v">${fmt(s.wind_gusts_kmh)} km/h</span></div>
    <div class="kv"><span class="k">Tages‑Tmax/Tmin</span><span class="v">${fmt(sun.tmax)} / ${fmt(sun.tmin)} °C</span></div>
    <div class="kv"><span class="k">Sonnenauf/untergang</span><span class="v">${fmtTime(sun.sunrise)} / ${fmtTime(sun.sunset)}</span></div>
    <div class="kv"><span class="k">KP</span><span class="v">${fmt(kp.kp)} <span class="${kp.category.cls}">${kp.category.label}</span></span></div>
    <div class="kv"><span class="k">GNSS Satelliten</span><span class="v">${fmt(data.gnss_visible_satellites?.count)} <span class="muted">(El≥${fmt(data.gnss_visible_satellites?.elevation_mask_deg)}°)</span></span></div>
    </div>
  `;
}
function scorePill(value, goodMax, warnMax, unit, invert=false, inclusive=false){
  if (value == null || Number.isNaN(Number(value))) return `<span class="pill">—</span>`;
  const v = Number(value);
  let cls = 'pill good';
  const x = invert ? -v : v;
  const g = invert ? -goodMax : goodMax;
  const w = invert ? -warnMax : warnMax;
  if (x > w || (inclusive && x === w)) cls = 'pill bad';
  else if (x > g || (inclusive && x === g)) cls = 'pill warn';
  return `<span class="${cls}">${fmt(v)} ${escapeHtml(unit||'')}</span>`;
}

/** Abgestimmt auf Checkliste: ≥85 % kritisch, außer geschätzte Wolkuntergrenze AGL (LCL) >150 m. */
function droneCloudCoverPill(cloudPct, tempC, dewC){
  if (cloudPct == null || Number.isNaN(Number(cloudPct))) return `<span class="pill">—</span>`;
  const v = Number(cloudPct);
  const t = Number(tempC), td = Number(dewC);
  const baseM = (Number.isFinite(t) && Number.isFinite(td)) ? Math.max(0, t - td) * 125 : null;
  let cls = 'pill good';
  if (v >= 85) cls = (baseM !== null && baseM > 150) ? 'pill good' : 'pill bad';
  else if (v >= 60) cls = 'pill warn';
  return `<span class="${cls}">${fmt(v)} %</span>`;
}

function renderDroneDay(data){
  const s = data.snapshot;
  const kp = data.kp;
  // Schwellen: ab 25 km/h orange, ab 36 km/h rot (inklusive Grenzwert)
  const windP = scorePill(s.wind_80m_kmh, 25, 36, 'km/h', false, true);
  const gustP = scorePill(s.wind_gusts_kmh, 25, 36, 'km/h', false, true);
  const rainP = scorePill(s.precip_prob_pct, 25, 60, '%');
  const visKm = (s.visibility_m==null?null:(Number(s.visibility_m)/1000));
  const visP  = scorePill(visKm, 2, 0.5, 'km', true); // <2 km warn, <0.5 km bad
  const cloudP = droneCloudCoverPill(s.cloud_cover_pct, s.temp_c, s.dewpoint_c);

  return `
    <div class="title">Drohnen‑Kurzcheck</div>
    <div class="muted" style="margin:-6px 0 8px">Orientierung aus Wetterdaten (keine Flugfreigabe). Werte beziehen sich auf ${escapeHtml(String(s.time??'—'))}.</div>
    <div class="kv"><span class="k">Wind (80m)</span><span class="v">${windP}</span></div>
    <div class="kv"><span class="k">Böen (10m)</span><span class="v">${gustP}</span></div>
    <div class="kv"><span class="k">Regen‑W’keit</span><span class="v">${rainP}</span></div>
    <div class="kv"><span class="k">Sichtweite</span><span class="v">${visP}</span></div>
    <div class="kv"><span class="k">Bewölkung</span><span class="v">${cloudP}</span></div>
    <div class="kv"><span class="k">KP‑Index</span><span class="v">${fmt(kp.kp)} <span class="${kp.category.cls}">${kp.category.label}</span></span></div>
    <div class="kv"><span class="k">GNSS sichtbar</span><span class="v">${fmt(data.gnss_visible_satellites?.count)}</span></div>
  `;
}

function renderToday3h(data){
  const h = data.weather_raw.hourly;

  // --- Determine selected date ---
  const iso = data.meta?.requested?.targetIso || "";
  const dateKey = iso.slice(0,10);

  // --- Determine today & tomorrow ---
  const today = new Date();
  const todayKey = today.toISOString().slice(0,10);

  const tomorrow = new Date();
  tomorrow.setDate(today.getDate() + 1);
  const tomorrowKey = tomorrow.toISOString().slice(0,10);

  // --- Title label logic ---
  let titleLabel = dateKey;
  if (dateKey === todayKey) titleLabel = "Heute";
  else if (dateKey === tomorrowKey) titleLabel = "Morgen";

  // --- Build header ---
  let html = `<div class="title">Wetterverlauf ${titleLabel} (alle 3 Stunden)</div>`;

  // --- Collect 3-hour intervals for the selected date ---
  const cols = [];
  for (let i = 0; i < (h.time?.length || 0); i++) {
    const t = h.time[i];
    if (t.slice(0,10) !== dateKey) continue;

    const hour = parseInt(t.slice(11,13), 10);
    if (hour % 3 !== 0) continue;

    const [ic] = wmoLabel(h.weather_code?.[i]);
    cols.push({
      time: t.slice(11,16),
      icon: ic,
      temp: h.temperature_2m?.[i],
      wind: h.wind_speed_80m?.[i],
      rain: h.precipitation_probability?.[i],
      clouds: h.cloud_cover?.[i],
    });
  }

  // --- No data case ---
  if (!cols.length) {
    return html + `<div class="muted">Keine stündlichen Daten gefunden.</div>`;
  }

  // --- Render table ---
  html += `<div class="scroll"><table>
    <tr><th>Zeit</th>${cols.map(c=>`<td>${escapeHtml(c.time)}</td>`).join("")}</tr>
    <tr><th>Wetter</th>${cols.map(c=>`<td style="font-size:18px">${escapeHtml(c.icon)}</td>`).join("")}</tr>
    <tr><th>Temp °C</th>${cols.map(c=>`<td>${fmtTemperature(c.temp, false)}</td>`).join("")}</tr>
    <tr><th>Wind (80m)</th>${cols.map(c=>`<td>${fmt(c.wind)} km/h</td>`).join("")}</tr>
    <tr><th>Niederschlagsw. %</th>${cols.map(c=>`<td>${fmt(c.rain)}</td>`).join("")}</tr>
    <tr><th>Bewölkung %</th>${cols.map(c=>`<td>${fmt(c.clouds)}</td>`).join("")}</tr>
  </table></div>`;

  return html;
}

function renderOutlook14(data){
  const d = data.weather_raw.daily;
  const h = data.weather_raw.hourly;

  const days = d.time || [];
  const hourlyTimes = h.time || [];

  // --- Selected date ---
  const iso = data.meta?.requested?.targetIso || "";
  const startDate = iso.slice(0,10);

  // --- Find index of selected date in daily array ---
  const startIndex = days.indexOf(startDate);
  const sliceFrom = startIndex >= 0 ? startIndex : 0;

  // --- Build rows starting from selected date ---
  const rows = days.slice(sliceFrom).map((day, i) => {
    const realIndex = i + sliceFrom;

    const tmax = d.temperature_2m_max?.[realIndex];
    const tmin = d.temperature_2m_min?.[realIndex];

    // 09:00 snapshot (Icon/Text wie Temp/Wind — vorher fälschlich nur daily.weather_code)
    const t0900 = `${day}T09:00`;
    const hi = hourlyRowIndexForDayHour(day, 9, hourlyTimes);

    const wind0900 = hi >= 0 ? h.wind_speed_80m?.[hi] : null;
    const temp0900 = hi >= 0 ? h.temperature_2m?.[hi] : null;

    const wcHourly = hi >= 0 ? h.weather_code?.[hi] : null;
    const wcDaily = d.weather_code?.[realIndex];
    const wcForRow = (wcHourly !== null && wcHourly !== undefined && wcHourly !== '')
      ? wcHourly
      : wcDaily;
    const [ic, desc] = wmoLabel(wcForRow);

    const kpDay = resolveKpForDateTime(`${day}T09:00`, data.kp_forecast, data.kp?.kp, data.kp_forecast?.maxKp);
    const satDay = data.gnss_forecast?.byDate?.[day] ?? null;
    return { day, ic, desc, temp0900, wind0900, tmax, tmin, kpDay, satDay };
  });

  // --- Title ---
  let html = `<div class="title">Aussicht 14 Tage (je Tag 09:00)</div>`;

  if (!rows.length) {
    return html + `<div class="muted">Keine Daily Daten gefunden.</div>`;
  }

  // --- Render table ---
  html += `<div class="scroll"><table>
    <tr><th>Tag</th>${rows.map(r=>`<td>${escapeHtml(r.day)}</td>`).join("")}</tr>
    <tr><th>Wetter</th>${rows.map(r=>`<td style="font-size:18px">${escapeHtml(r.ic)}</td>`).join("")}</tr>
    <tr><th>Beschreibung</th>${rows.map(r=>`<td>${escapeHtml(r.desc)}</td>`).join("")}</tr>
    <tr><th>Temperatur</th>${rows.map(r=>`<td>${fmtTemperature(r.temp0900)} <span class="muted">(${fmt(r.tmax)}/${fmt(r.tmin)})</span></td>`).join("")}</tr>
    <tr><th>Wind</th>${rows.map(r=>`<td>${fmt(r.wind0900)} km/h</td>`).join("")}</tr>
    <tr><th>KP‑Forecast</th>${rows.map(r=>{
      const c = kpCategory(r.kpDay);
      return `<td>${fmt(r.kpDay)} <span class="${c.cls}">${escapeHtml(c.label)}</span></td>`;
    }).join("")}</tr>
    <tr><th>GNSS Satelliten</th>${rows.map(r=>`<td>${fmt(r.satDay)}</td>`).join("")}</tr>
  </table></div>`;

  return html;
}

/* ---------------- utils ---------------- */
function fmt(x){
  return (x === null || x === undefined || Number.isNaN(Number(x))) ? "—" : String(x);
}
function fmtTemperature(tempC, withUnit = true){
  if (tempC === null || tempC === undefined || Number.isNaN(Number(tempC))) {
    return "—";
  }
  const t = Number(tempC);
  const suffix = withUnit ? " °C" : "";
  if (t < 0) return `<span class="temp-freezing">${fmt(t)}${suffix}</span>`;
  if (t < 5) return `<span class="temp-cold">${fmt(t)}${suffix}</span>`;
  return `${fmt(t)}${suffix}`;
}
function fmtTime(iso){
  if (!iso) return "—";
  return iso.slice(11,16);
}
function fmtDateTime(iso){
  if (!iso) return "—";
  const d = new Date(String(iso));
  if (!Number.isFinite(d.getTime())) return String(iso);
  const months = ['Jan','Feb','Mar','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
  const dd = String(d.getDate()).padStart(2,'0');
  const mmm = months[d.getMonth()] || String(d.getMonth()+1).padStart(2,'0');
  const yyyy = String(d.getFullYear());
  const hh = String(d.getHours()).padStart(2,'0');
  const mm = String(d.getMinutes()).padStart(2,'0');
  return `${dd}.${mmm}.${yyyy} ${hh}:${mm}`;
}
function fmtKmFromM(m){
  if (m == null || Number.isNaN(Number(m))) return "—";
  const km = Number(m)/1000;
  return km >= 10 ? km.toFixed(0) : km.toFixed(1);
}
function escapeHtml(str){
  return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

/* =========================
   MAIN LOAD
   ========================= */
async function loadAll(){
  try{
    setStatus("Lade…");
    const placeName = document.getElementById("place").value.trim();
    let lat = parseFloat(document.getElementById("lat").value);
    let lon = parseFloat(document.getElementById("lon").value);

    const dateStr = document.getElementById("date").value;
    const timeStr = document.getElementById("time").value;
    const targetIso = isoFromDateTime(dateStr, timeStr);

    let placeInfo = { lat, lon, label: `${lat}, ${lon}` };
    if (placeName && (!Number.isFinite(lat) || !Number.isFinite(lon))) {
      const g = await geocodePlace(placeName);
      lat = g.lat; lon = g.lon;
      document.getElementById("lat").value = lat;
      document.getElementById("lon").value = lon;
      placeInfo = { lat, lon, label: `${g.name}${g.admin1 ? ", "+g.admin1 : ""}${g.country ? ", "+g.country : ""}` };
    } else if (placeName) {
      placeInfo = { lat, lon, label: placeName };
    }

    if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
      throw new Error("Bitte Ort (Name) oder gültige Lat/Lon eingeben.");
    }

    const [weatherJson, kpRaw, kpForecast, gnssInfo] = await Promise.all([
      fetchWeather(lat, lon),
      fetchKp(targetIso || new Date().toISOString()),
      fetchKpForecast().catch(()=>({ byDate:{}, maxKp:null })),
      fetchGnssVisibleCount(lat, lon, targetIso || new Date().toISOString()).catch(()=>null)
    ]);
    const gnssForecast = await fetchGnssVisibleByDates(lat, lon, weatherJson?.daily?.time || []).catch(()=>({ byDate:{} }));

    const kpJson = { kp: kpRaw.kp, time_utc: kpRaw.time_utc, category: kpRaw.category };
    const data = buildWeatherData({
      placeInfo,
      weatherJson,
      kpJson,
      kpForecast,
      gnssInfo,
      gnssForecast,
      targetIso: targetIso || weatherJson.current?.time
    });

    window.weatherData = data;
    document.getElementById("rawOut").textContent = JSON.stringify(data, null, 2);

    document.getElementById("smallDay").innerHTML  = renderSmallDay(data);
    document.getElementById("detailDay").innerHTML = renderDetailDay(data);
    document.getElementById("today3h").innerHTML   = renderToday3h(data);
    document.getElementById("outlook14").innerHTML = renderOutlook14(data);

    const droneEl = document.getElementById("droneDay");
    if (droneEl) droneEl.innerHTML = renderDroneDay(data);

    setStatus("OK");
  } catch(e){
    console.error(e);
    setStatus(e.message || String(e), true);
    document.getElementById("rawOut").textContent = JSON.stringify({error: String(e)}, null, 2);
  }
}

// ----------------------------------------------------------
// Layout-/Reihenfolge-Logik (neu):
// - Standard (ohne ?demo): Detaillierte Tagesansicht + Drohnen‑Kurzcheck oben,
//   dann Wetterverlauf heute (3h), dann 14‑Tage‑Aussicht.
// - Drohnen-Ansicht: ?ansicht=drohnenwetter (oder ?view=drohnenwetter)
//   blendet zusätzlich Kleine Tagesansicht + RAW Daten aus.
// - Optional: ?drohnenwetterBlende blendet ebenfalls aus.
// - ?demo: alles bleibt wie bisher (kein Umordnen/kein Blenden/kein Breite-Tweak).
// ----------------------------------------------------------
function _isSpacer(el){
  if (!el) return false;
  if (el.tagName !== 'DIV') return false;
  const st = (el.getAttribute('style') || '').replace(/\s+/g,'').toLowerCase();
  return st.includes('height:12px');
}

function _segmentById(id){
  const el = document.getElementById(id);
  if (!el) return null;
  const sp = _isSpacer(el.previousElementSibling) ? el.previousElementSibling : null;
  return { el, sp };
}

function _hideSectionById(id){
  const seg = _segmentById(id);
  if (!seg) return;
  if (seg.sp) seg.sp.style.display = 'none';
  seg.el.style.display = 'none';
}

function applyLayoutTweaks(){
  // 1) Reihenfolge innerhalb der Tages-Detail-Kacheln
  const wrap = document.getElementById('dayDetails');
  const detail = document.getElementById('detailDay');
  const drone  = document.getElementById('droneDay');
  const small  = document.getElementById('smallDay');
  if (wrap && detail && drone){
    // Reihenfolge: Detail -> Drohne -> (optional) Klein
    wrap.insertBefore(detail, wrap.firstChild);
    wrap.insertBefore(drone, detail.nextSibling);
    if (small) wrap.appendChild(small);
  }

  // 2) Reihenfolge der Hauptsektionen auf der Seite
  const sDay = _segmentById('dayDetails');
  const s3h  = _segmentById('today3h');
  const s14  = _segmentById('outlook14');
  const sRaw = _segmentById('rawWrap');
  const anchor = (s14 && (s14.sp || s14.el)) || (s3h && (s3h.sp || s3h.el)) || (sDay && (sDay.sp || sDay.el));
  if (anchor && anchor.parentNode){
    const parent = anchor.parentNode;
    const marker = document.createComment('layout-marker');
    parent.insertBefore(marker, anchor);
    const order = [sDay, s3h, s14, sRaw].filter(Boolean);
    for (const s of order){
      if (s.sp) parent.insertBefore(s.sp, marker);
      parent.insertBefore(s.el, marker);
    }
    marker.remove();
  }
}

// =====================
// Share-Link (URL kopieren)
// =====================
function rkrk_buildShareUrl_keepExistingParams() {
  // Nimmt die aktuelle URL (inkl. aller vorhandenen Parameter wie drohnenwetter=...)
  const url = new URL(window.location.href);

  const elPlace = document.getElementById('place') || document.getElementById('ort');
  const elLat   = document.getElementById('lat')   || document.getElementById('latitude');
  const elLon   = document.getElementById('lon')   || document.getElementById('lng') || document.getElementById('longitude');
  const elDate  = document.getElementById('date')  || document.getElementById('datum');
  const elTime  = document.getElementById('time')  || document.getElementById('zeit');

  const place = elPlace?.value?.trim();
  const lat   = elLat?.value?.trim();
  const lon   = elLon?.value?.trim();
  const date  = elDate?.value;
  const time  = elTime?.value;

  // Nur Wetter-Parameter überschreiben/setzen, alles andere bleibt erhalten!
  if (lat && lon) {
    url.searchParams.set('lat', lat);
    url.searchParams.set('lon', lon);
    url.searchParams.delete('place');
    url.searchParams.delete('q');
  } else if (place) {
    url.searchParams.set('place', place);
    url.searchParams.delete('lat');
    url.searchParams.delete('lon');
  }
  if (date) url.searchParams.set('date', date);
  if (time) url.searchParams.set('time', time);

  return url.toString();
}

async function rkrk_copyShareLink() {
  const shareUrl = rkrk_buildShareUrl_keepExistingParams();
  try {
    await navigator.clipboard.writeText(shareUrl);
    // Optional: Statusmeldung anzeigen
    // setStatus?.('Link kopiert', 'ok');
  } catch (e) {
    // Optional: Fehler anzeigen
    // setStatus?.('Konnte Link nicht kopieren', 'err');
  }
}


(function initDefaults(){
  const params = new URLSearchParams(location.search);
  const isDemo = params.has('demo');


	// --- robust: drohnenwetter erkennen (ansicht/view ODER als flag ?drohnenwetter) ---
	const v = (params.get('ansicht') || params.get('view') || '').trim().toLowerCase();

	// Keys case-insensitiv machen (drohnenwetterBlende vs drohnenwetterblende)
	const keysLower = new Set([...params.keys()].map(k => k.toLowerCase()));

	const isDrone =
	  v === 'drohnenwetter' ||
	  keysLower.has('drohnenwetter'); // erlaubt auch: ?drohnenwetter

	if (isDrone) {
	  
	  document.body.classList.add('drohnenwetter');
	  document.getElementById("smallDay").style.display = 'none'
	  document.getElementById("rawWrap").style.display = 'none'
  }
  
  if (isDemo){
    document.body.classList.add('demo');
  } else {
    document.body.classList.add('layout-new');
    if (params.has('drohnenwetterBlende')) document.body.classList.add('drohnenwetterBlende');
    applyLayoutTweaks();
  }

  const now = new Date();
  const yyyy = now.getFullYear();
  const mm = String(now.getMonth()+1).padStart(2,'0');
  const dd = String(now.getDate()).padStart(2,'0');
  document.getElementById("date").value = `${yyyy}-${mm}-${dd}`;
  document.getElementById("time").value = "09:00";

  loadAll();

  // Auto-Sync: Ort -> Lat/Lon, Lat/Lon -> "in der Nähe von ..."
  document.getElementById('place').addEventListener('change', onPlaceChanged);
  document.getElementById('place').addEventListener('input', debounce(onPlaceChanged, 700));
  document.getElementById('lat').addEventListener('change', onCoordsChanged);
  document.getElementById('lon').addEventListener('change', onCoordsChanged);
  document.getElementById('lat').addEventListener('input', debounce(onPlaceChanged, 700));
  document.getElementById('lon').addEventListener('input', debounce(onCoordsChanged, 700));
  document.getElementById("date").addEventListener("change", loadAll);
  document.getElementById("time").addEventListener("change", loadAll);
})();

// Button lädt (auch wenn Auto-Sync schon lief)
document.getElementById("load").addEventListener("click", loadAll);


document.addEventListener('DOMContentLoaded', () => {
  const urlState = rkrk_applyUrlParams_noDefaultOverwrite();

  if (urlState.doAutoload) {
    // >>> HIER deinen EXISTIERENDEN "Daten laden"-Trigger aufrufen <<<
    // Beispiel 1: wenn du eine Funktion hast:
    // loadWeatherData();

    // Beispiel 2: wenn du über Button klickst:
    // document.getElementById('btnLoad')?.click();
  }
});

</script>

</body>
</html>
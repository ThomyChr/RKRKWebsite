<?php
  session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_menu.php');
  include_once('rkrk_access_control.php');
  include_once('scripts/rkrk_ref_layers_lib.php');

  $view = (isset($_GET['view']) && $_GET['view'] === 'settings') ? 'settings' : 'layers';
  $pagetitle = ($view === 'settings') ? 'Layer-Settings' : 'Layer verwalten';
  headerpage($pagetitle, false);
  GrantAccess();
  CreateTopMenu();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.5.2/ol.css" />
<link rel="stylesheet" href="template/rkrk_ref_layers.css?v=20260831a" />
<?php if ($view === 'settings') { ?>
<link rel="stylesheet" href="template/rkrk_ref_layers_settings.css?v=20260831a" />
<?php } ?>
<script>
window.RKRK_REF_LAYERS_API = 'rkrk_api_ref_layers.php';
document.documentElement.classList.add('rkrk-has-nav');
document.body.classList.add('rkrk-page', <?php echo ($view === 'settings') ? "'rkrk-layer-settings'" : "'rkrk-layer-admin'"; ?>);
</script>
<?php if ($view === 'settings') { ?>
  <div class="container">
    <div class="sticky">
      <strong style="color:var(--ink)">Settings</strong>
      <button type="button" class="icon-btn btn-ok" id="saveBtn" title="Alles speichern (Farben + Layer-Defaults)">&#128190;</button>
      <button type="button" class="icon-btn btn-warn" id="resetBtn" title="Aktiven Bereich auf Defaults zuruecksetzen">&#8634;</button>
      <button type="button" class="icon-btn btn-ghost" id="reloadBtn" title="Neu laden">&#8635;</button>
      <a class="icon-link btn-ghost" href="rkrk_kml2xml.php" title="Zurueck zur Layerverwaltung">&#128205;</a>
      <span class="status" id="status">Bereit.</span>
    </div>
    <div class="card">
      <h1>Layer-Settings</h1>
      <p class="muted">Farben und Standard-Ein/Aus. Speicherung unter <code>rkrk/webdaten/mylayer/</code>. Layer-Liste: <code>rkrk_kmlfile_settings.json</code>.</p>
    </div>
    <div class="tabs" role="tablist">
      <button type="button" class="tab is-active" data-tab="colors" title="Farben">&#127912;</button>
      <button type="button" class="tab" data-tab="layers" title="Layer-Defaults">&#128065;</button>
    </div>
    <div class="panel card" data-panel="colors">
      <h2>Farben</h2>
      <p class="muted" style="margin-bottom:10px">Datei: <code>mylayer/colors.json</code></p>
      <div class="head">Gruppen</div>
      <div class="colh"><div>Layer</div><div>Stroke</div><div>Fill</div><div>Width</div><div>Opaz.%</div><div>Vorschau</div></div>
      <div id="groupsRoot"></div>
      <div class="head">Styles (styleUrl)</div>
      <div class="colh"><div>Style</div><div>Stroke</div><div>Fill</div><div>Width</div><div>Opaz.%</div><div>Vorschau</div></div>
      <div id="stylesRoot"></div>
    </div>
    <div class="panel card" data-panel="layers" hidden>
      <h2>Layer-Defaults</h2>
      <p class="muted" style="margin-bottom:10px">Startzustand. Datei: <code>mylayer/ui_defaults.json</code></p>
      <div class="toggle-grid" id="togglesRoot"></div>
      <div class="filter-row">
        <label><input type="checkbox" id="filterBbox" /> Nur Kartenausschnitt (Standard)</label>
      </div>
      <div style="margin-top:12px;display:flex;gap:8px;">
        <button type="button" class="icon-btn btn-accent" id="allOnBtn" title="Alle Layer ein">&#9745;</button>
        <button type="button" class="icon-btn btn-ghost" id="allOffBtn" title="Alle Layer aus">&#9744;</button>
        <button type="button" class="icon-btn btn-ghost" id="presetBtn" title="Preset: FZ + HSP + Alert">&#9733;</button>
      </div>
    </div>
  </div>
  <script src="scripts/rkrk_ref_layers_settings.js?v=20260831a"></script>
<?php } else { ?>
  <aside class="panel">
    <div class="rkr-brand-header">
      <h1>Eigene Layer verwalten</h1>
    </div>
    <p class="muted">
      Wie in RKRS mapII: Regionen = Karten-View. <strong>ALL</strong> = alle Jagdgebiete.
      <strong>Karte</strong> lädt die KML rechts zum Bearbeiten.
      Wahrheit: <code>rkrk_kmlfile_settings.json</code>.
    </p>
    <div class="toplinks actions-bar">
      <a class="icon-link" href="rkrk_kml2xml.php?view=settings" title="Farben und Layer-Defaults">&#127912;</a>
      <button type="button" class="icon-btn btn-ok" id="saveBtn" title="rkrk_kmlfile_settings.json speichern">&#128190;</button>
      <button type="button" class="icon-btn btn-accent" id="buildBtn" title="GeoJSON und POI-Katalog aus KML erzeugen">&#9881;</button>
      <button type="button" class="icon-btn btn-ghost" id="resetBtn" title="Aus Mission-XML wiederherstellen">&#8634;</button>
      <button type="button" class="icon-btn btn-ghost" id="reloadBtn" title="Aktualisieren">&#8635;</button>
      <a class="icon-link btn-ghost" href="rkrk_map.php" title="Zur Karte">&#127757;</a>
    </div>
    <div id="status" class="status">Bereit.</div>
    <p id="sourceInfo" class="muted"></p>
    <div id="groupsRoot" class="group"></div>
  </aside>
  <button type="button" id="mapToggleBtn" title="Karte einblenden" aria-label="Karte einblenden" aria-expanded="false">&gt;&gt;</button>
  <div id="mapWrap">
    <div id="map"></div>
    <div id="mapEditBar">
      <strong id="mapEditFile">Keine Datei</strong>
      <select id="mapDrawType" title="Neues Objekt">
        <option value="Polygon">Polygon</option>
        <option value="LineString">Linie</option>
        <option value="Point">Punkt</option>
      </select>
      <button type="button" class="icon-btn" id="mapAddBtn" title="Neues Objekt zeichnen">&#10010;</button>
      <button type="button" class="icon-btn btn-danger" id="mapDelBtn" title="Objekt löschen">&#128465;</button>
      <input id="mapFeatName" type="text" placeholder="Name" title="Objektname" />
      <button type="button" class="icon-btn btn-ok" id="mapSaveBtn" title="KML speichern">&#128190;</button>
      <button type="button" class="icon-btn btn-ghost" id="mapCloseBtn" title="Kartenbearbeitung beenden">&#10005;</button>
    </div>
    <div id="mapHint">Ecken ziehen · Alt-Klick löscht Ecke · Neu zeichnen · Speichern schreibt die KML</div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/ol@7.5.2/dist/ol.js"></script>
  <script src="scripts/rkrk_ref_layers.js?v=20260831a"></script>
<?php } ?>

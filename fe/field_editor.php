<?php
require_once __DIR__ . '/mapll_env.php';
$mapllBrand = mapll_favicon_brand();
$rkrkKat = array_key_exists('KAT', $_GET);
$rkrkAdmin = array_key_exists('ADMIN', $_GET) || strcasecmp(trim((string)($_GET['code'] ?? '')), 'ADMIN') === 0;
?><!doctype html>
<html <?php echo mapll_html_open_attrs(); ?>>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars(($mapllBrand['label'] ?? 'RKRK') . ' Feld-Editor', ENT_QUOTES, 'UTF-8'); ?></title>
  <?php echo mapll_favicon_link_tags($mapllBrand); ?>
  <?php echo mapll_theme_css_link(); ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.5.2/ol.css" />
  <link rel="stylesheet" href="rkrk_help.css?v=20261017hilfe6" />
  <link rel="stylesheet" href="rkrk_layout.css" />
  <link rel="stylesheet" href="../template/rkrk_reg_pills.css?v=20260907nodbl" />
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f8; color: #23384d; }
    .wrap { display: grid; grid-template-columns: 380px 1fr; height: 100vh; }
    .left { padding: 14px; overflow: auto; border-right: 1px solid #d9dde2; background: #fff; }
    .map-wrap { position: relative; width: 100%; height: 100vh; }
    .card { background: #fff; border: 1px solid #d9dde2; border-radius: 8px; padding: 10px; }
    h1 { margin: 0 0 8px; font-size: 22px; }
    .rkr-brand-header { display: flex; align-items: center; gap: 10px; }
    .rkr-brand-favicon { width: 28px; height: 28px; border-radius: 6px; object-fit: contain; flex: 0 0 auto; background: #fff; }
    .small { font-size: 12px; color: #5b6f86; }
    .row { margin-bottom: 8px; }
    label { display: block; font-size: 12px; margin-bottom: 3px; color: #344f6d; }
    input, textarea, select, button { width: 100%; box-sizing: border-box; padding: 8px; border: 1px solid #f4f6f8; border-radius: 6px; }
    #feld_id, #create_feld_id, #number, #create_number { background: #fff; color: #1b3552; }
    .rkrk-kat-only[hidden] { display: none !important; }
    textarea { min-height: 70px; resize: vertical; font-family: Consolas, monospace; font-size: 12px; }
    button { cursor: pointer; background: var(--brand-dark, #48673A); color: #fff; border: 0; }
    button:hover { background: var(--brand-hover, #3d5632); }
    button:disabled, button:disabled:hover { opacity: 0.45; cursor: default; background: var(--brand-dark, #48673A); }
    .actions { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
    .actions .secondary { background: var(--brand, #96B588); }
    .actions .secondary:hover { background: var(--brand-dark, #48673A); }
    .field-list { max-height: 72vh; overflow: auto; border: 1px solid #d9dde2; border-radius: 6px; }
    .quick-slot-box {
      margin-bottom: 10px;
      border: 1px solid #d9dde2;
      border-radius: 8px;
      background: #fff;
      padding: 8px;
    }
    .menu-fold { padding: 0; }
    .menu-fold > summary {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 10px;
      cursor: pointer;
      font-size: 12px;
      font-weight: bold;
      color: #1b3552;
      list-style: none;
      user-select: none;
    }
    .menu-fold > summary::-webkit-details-marker { display: none; }
    .menu-fold > summary::before {
      content: "▾";
      width: 12px;
      flex: 0 0 auto;
      color: #5b6f86;
    }
    .menu-fold:not([open]) > summary::before { content: "▸"; }
    .menu-fold:not([open]) > :not(summary) { display: none !important; }
    .menu-fold > summary .box-dock-row { margin-left: auto; display: flex; gap: 4px; }
    .menu-fold > summary .box-dock-btn { margin-left: 0; }
    .menu-fold-body { padding: 0 10px 10px; }
    .menu-fold-body .search-line { margin-bottom: 8px; }
    .quick-slot-title {
      font-size: 12px;
      font-weight: bold;
      color: #1b3552;
      margin-bottom: 2px;
    }
    .quick-slot-help {
      font-size: 11px;
      color: #5b6f86;
      margin-bottom: 6px;
    }
    .quick-slot-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 6px;
    }
    .quick-slot-btn {
      width: 100%;
      padding: 6px 5px;
      border: 1px solid #cbd7e6;
      border-radius: 6px;
      background: #edf4fc;
      color: #23405f;
      font-size: 11px;
      line-height: 1.1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .quick-slot-btn:hover {
      background: #deebfa;
    }
    .quick-slot-btn.active {
      background: #d2e4fa;
      border-color: #8fb3de;
      font-weight: bold;
    }
    #searchInput {
      max-width: none;
      padding: 5px 7px;
      font-size: 12px;
      min-height: 28px;
      line-height: 1.1;
    }
    .search-line { display: flex; align-items: center; gap: 6px; }
    .search-line #searchInput { flex: 1 1 auto; width: auto; }
    .box-dock-btn {
      width: auto;
      min-width: 72px;
      height: 28px;
      padding: 0 8px;
      border-radius: 6px;
      border: 1px solid #8fb3de;
      background: #1f78d1;
      color: #fff;
      font-size: 12px;
      font-weight: bold;
      line-height: 26px;
      flex: 0 0 auto;
    }
    .box-dock-btn[hidden], .rkrk-sel[hidden] { display: none !important; }
    .fov-info {
      display: block;
      padding: 2px 0;
      font-size: 10px;
      color: #5b6f86;
      line-height: 1.2;
    }
    .fov-combined {
      display: block;
      padding: 2px 0;
      font-size: 10px;
      color: #5b6f86;
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .field-item { padding: 7px 8px; border-bottom: 1px solid #edf1f5; cursor: pointer; font-size: 13px; }
    .field-item:last-child { border-bottom: 0; }
    .field-item:hover { background: #f3f8ff; }
    .field-item.active { background: #e6f0fc; font-weight: bold; }
    #fieldSearchBox { margin-left: -8px; margin-right: -8px; }
    #fieldSearchBox > .menu-fold-body { padding-left: 4px; padding-right: 4px; }
    #fieldList { scrollbar-width: thin; }
    #fieldList .field-item {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 1px;
      padding: 2px 4px;
      line-height: 1.25;
    }
    #fieldList .field-item-line {
      display: flex;
      flex-wrap: nowrap;
      align-items: center;
      gap: 4px;
      min-width: 0;
      max-width: 100%;
    }
    #fieldList .field-item-id {
      flex: 0 0 auto;
      white-space: nowrap;
      color: #0f172a;
    }
    #fieldList .field-item-name {
      flex: 1 1 auto;
      min-width: 0;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      color: #0f172a;
    }
    #fieldList .field-item-meta {
      flex: 1 1 auto;
      min-width: 0;
      margin: 0;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      font-size: 11px;
      line-height: 1.25;
      color: #64748b;
      font-weight: normal;
    }
    #fieldList .field-delete-btn {
      flex: 0 0 auto;
      width: auto;
      margin: 0 0 0 auto;
      padding: 0 5px;
      font-size: 11px;
      line-height: 1.3;
      white-space: nowrap;
      font-weight: 650;
      color: #a22b2b;
      background: #fff;
      border: 1px solid #e7b4b4;
      border-radius: 4px;
      cursor: pointer;
    }
    #fieldList .field-delete-btn:hover { background: #fdecec; }
    .field-delete-ask {
      position: fixed;
      inset: 0;
      z-index: 80;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
      background: rgba(15, 23, 42, 0.45);
    }
    .field-delete-ask-box {
      width: min(420px, 100%);
      padding: 14px 14px 12px;
      border: 1px solid #d9dde2;
      border-radius: 8px;
      background: #fff;
      color: #0f172a;
      box-shadow: 0 8px 28px rgba(0, 0, 0, 0.2);
    }
    .field-delete-ask-box p { margin: 0 0 12px; font-size: 14px; line-height: 1.45; white-space: pre-wrap; }
    .field-delete-ask-actions { display: flex; justify-content: flex-end; gap: 8px; }
    .field-delete-ask-actions button { padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .field-delete-cancel { background: #fff; border: 1px solid #cfd6de; color: #0f172a; }
    .field-delete-confirm { background: #a22b2b; border: 1px solid #a22b2b; color: #fff; font-weight: 650; }
    .status { margin-top: 8px; padding: 8px; border-radius: 6px; font-size: 13px; display: none; }
    .ok { background: #e8f7ec; border: 1px solid #b8e0c2; color: #1d6d36; }
    .err { background: #fdecec; border: 1px solid #efc5c5; color: #a22b2b; }
    .toplinks { margin-bottom: 10px; }
    .map-menu {
      position: absolute;
      top: 8px;
      left: 8px;
      z-index: 20;
      max-width: calc(100% - 120px);
      padding: 6px 8px;
      border-radius: 6px;
      border: 1px solid #cfd6de;
      background: rgba(255,255,255,0.94);
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    }
    .map-toolbar[hidden] { display: none !important; }
    .map-toolbar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      margin-bottom: 4px;
    }
    .map-toolbar select, .map-toolbar button {
      width: auto;
      min-width: 0;
      padding: 5px 8px;
      font-size: 12px;
      border-radius: 5px;
    }
    .map-toolbar .info-btn {
      border-radius: 999px;
      width: 26px;
      height: 26px;
      padding: 0;
      text-align: center;
      font-weight: bold;
      line-height: 26px;
      background: #1f78d1;
      color: #fff;
    }
    .map-toolbar select.compact-select {
      padding: 4px 6px;
      font-size: 12px;
      min-height: 28px;
      line-height: 1.1;
    }
    .map-toolbar button.secondary { background: var(--brand, #96B588); }
    .map-toolbar button.secondary:hover { background: var(--brand-dark, #48673A); }
    .map-toolbar button.active { background: var(--brand-dark, #48673A); color: #fff; }
    .map-menu { left: auto; right: 8px; top: 8px; width: min(320px, calc(100% - 24px)); max-width: 320px; padding: 0; background: transparent; border: 0; box-shadow: none; }
    .plan-wizard { background: #fff; border: 1px solid #d9dde2; border-radius: 8px; box-shadow: none; padding: 0; color: #1b3552; margin: 0 0 8px; }
    .plan-wizard.menu-fold > summary .plan-mode { margin-left: auto; }
    .plan-wizard-head { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; cursor: move; }
    .plan-wizard-head strong { font-size: 14px; }
    .plan-mode, .plan-collapse { width: auto; min-width: 0; padding: 4px 8px; font-size: 11px; border-radius: 999px; background: #e8eef4; color: #1b3552; }
    .plan-mode.is-expert { background: #c47b2b; color: #fff; }
    .plan-collapse { margin-left: auto; }
    .plan-wizard-body { padding: 0 10px 10px; }
    .plan-wizard input, .plan-wizard select { background: #fff; border: 1px solid #d9dde2; width: 100%; }
    .plan-stepper { display: grid; grid-template-columns: 36px 1fr 36px; gap: 6px; align-items: center; margin-bottom: 8px; }
    .plan-stepper button { width: 36px; height: 36px; padding: 0; border-radius: 8px; background: #e8eef4; color: #1b3552; font-size: 18px; }
    .plan-stepper span { text-align: center; font-weight: bold; font-size: 18px; }
    .plan-stepper input#msAltitude, .plan-stepper input#planSpeed, .plan-stepper input#planMissionAlt, .plan-stepper input#msStartSpeed { width: 100%; text-align: center; font-weight: bold; font-size: 16px; padding: 6px; }
    #planStartSpeedRow { grid-template-columns: 1fr; }
    #planStartSpeedToggle input:disabled,
    #planStartSpeedRow input:disabled { opacity: 0.55; }
    #planAltHost label { margin-top: 8px; }
    .plan-assign {
      margin-top: 8px;
      padding: 6px 8px;
      border-radius: 6px;
      background: #eef5ea;
      color: #24402a;
      font-size: 12px;
      line-height: 1.35;
    }
    .plan-step { display: flex; justify-content: space-between; align-items: center; text-align: left; background: #fff; color: #1b3552; border: 1px solid #d9dde2; margin: 6px 0; padding: 10px 12px; }
    .plan-step::after { content: '›'; color: #5b6f86; font-size: 18px; }
    .plan-step.is-active { border-color: #1f78d1; background: #f3f8ff; }
    .plan-point-list { display: flex; flex-direction: column; gap: 6px; margin: 8px 0 4px; }
    .plan-point-row {
      display: flex;
      align-items: stretch;
      border: 1px solid #d9dde2;
      border-radius: 8px;
      background: #fff;
    }
    .plan-point-row[hidden] { display: none !important; }
    .plan-point-row:has(.is-active) { border-color: #1f78d1; }
    button.plan-point-hit {
      flex: 1 1 auto;
      display: flex;
      align-items: flex-start;
      gap: 8px;
      width: auto;
      min-width: 0;
      margin: 0;
      padding: 8px 10px;
      border: 0;
      border-radius: 8px;
      background: transparent;
      color: #1b3552;
      text-align: left;
      font-family: inherit;
      font-weight: 400;
      line-height: 1.3;
    }
    button.plan-step.plan-point-hit::after { content: none; }
    button.plan-point-hit:hover,
    button.plan-step.plan-point-hit.is-active,
    button.plan-step.plan-point-hit.is-active:hover {
      background: transparent;
      color: #1b3552;
      border: 0;
    }
    .plan-point-row:hover { border-color: #b7c3d0; }
    .plan-point-row:has(.is-active):hover { border-color: #1f78d1; }
    .plan-point-ico {
      flex: 0 0 18px;
      width: 18px;
      margin-top: 1px;
      text-align: center;
      font-size: 14px;
      line-height: 1.2;
    }
    .plan-point-ico svg { display: block; margin: 0 auto; }
    .plan-point-copy { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
    .plan-point-label { font-size: 13px; font-weight: 700; color: #1b3552; }
    .plan-note.plan-coord { display: block; margin: 0; font-size: 11px; line-height: 1.35; color: #5b6f86; }
    .plan-coord:empty { display: none; }
    .plan-coord.is-coord { font-family: Consolas, ui-monospace, monospace; letter-spacing: 0; }
    .plan-point-row .plan-quiet { align-self: center; flex: 0 0 auto; margin: 0 10px 0 0; white-space: nowrap; }
    .plan-fold[data-plan-phase="start"] > summary .plan-fold-mark {
      color: #24402a;
      background: #eef5ea;
      border: 1px solid #d5e4d0;
      border-radius: 8px;
      padding: 1px 7px;
      font-size: 11px;
      font-weight: 600;
      line-height: 1.45;
    }
    .plan-fold[data-plan-phase="start"] .plan-direct-line {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 6px 0 0;
      font-size: 12px;
      color: #1b3552;
    }
    .plan-actions { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-top: 8px; }
    .plan-actions.plan-pic-actions { grid-template-columns: 1fr 1fr; }
    .plan-actions button { padding: 8px 4px; font-size: 12px; }
    .plan-actions .secondary { background: var(--brand, #96B588); }
    .plan-note { margin-top: 6px; font-size: 11px; color: #5b6f86; }
    #debugTfBox .plan-note,
    .debug-param { color: #333333; font-style: italic; font-weight: 400; }
    .debug-param { display: block; font-size: 11px; line-height: 1.25; margin: 1px 0 4px; }
    .plan-feld-place.is-free { color: #1d6b3a; font-weight: 700; }
    .plan-feld-place.is-taken { color: #8a2a2a; font-weight: 700; }
    .plan-rail { margin: 6px 0 4px; font-size: 12px; font-weight: bold; color: #1b3552; }
    .plan-status { background: #e8f1fb; border-radius: 8px; padding: 6px 8px; }
    #planStepWp[hidden], #planReview[hidden], #planWpPanel[hidden] { display: none !important; }
    .plan-review { margin-top: 8px; padding: 8px; border: 1px solid #d9dde2; border-radius: 8px; background: #f7fafc; }
    .plan-review[hidden] { display: none !important; }
    .plan-checkline { display: flex; align-items: center; gap: 6px; font-size: 12px; }
    .plan-checkline input { width: auto; }
    .plan-fold-body { container-type: inline-size; }
    .plan-pills {
      display: grid;
      grid-template-columns: 1fr;
      gap: 6px;
    }
    @container (min-width: 120px) {
      .plan-pills { grid-template-columns: 1fr 1fr; }
    }
    @container (min-width: 240px) {
      .plan-pills { grid-template-columns: 1fr 1fr 1fr; }
    }
    .plan-pills label { width: auto; margin: 0; justify-content: center; text-align: center; }
    .plan-choice { display: flex; gap: 6px; margin-bottom: 6px; }
    .plan-choice button, #planResetPath, #planMeasureBtn, #planMeasureClearBtn { width: auto; flex: 1; background: #e8eef4; color: #1b3552; }
    .plan-dir { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; margin: 2px 0 4px; }
    .plan-dir input[type="range"] { width: 100%; margin: 0; }
    .plan-dir span { font-size: 12px; font-weight: 700; color: #1b3552; min-width: 96px; text-align: right; }
    .plan-dir-step { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin: 0 0 4px; }
    .plan-dir-step button { width: auto; padding: 6px 0; background: #e8eef4; color: #1b3552; font-size: 13px; font-weight: 700; }
    .plan-quiet-row { display: flex; flex-wrap: wrap; gap: 2px 14px; margin-top: 8px; }
    .plan-quiet { width: auto; margin: 0; padding: 2px 0; background: transparent; color: #5b6f86; border: 0; font-size: 11px; text-decoration: underline; }
    .plan-quiet:hover { background: transparent; color: #1b3552; }
    .plan-choice button.is-on, #planMeasureBtn.measure-on { background: #1f78d1; color: #fff; }
    .plan-choice #planOriginExisting, .plan-choice #planOriginNew { font-size: 12px; padding: 8px 6px; line-height: 1.2; }
    .plan-existing-list { max-height: 180px; overflow: auto; border: 1px solid #d9dde2; border-radius: 6px; margin-top: 6px; background: #fff; }
    .plan-existing-list .field-item { display: block; width: 100%; text-align: left; background: #fff; color: #0f172a; border: 0; border-bottom: 1px solid #edf1f5; border-radius: 0; font-weight: normal; }
    .plan-existing-list .field-item.active { font-weight: bold; }
    #planExistingPick { margin-bottom: 8px; }
    .plan-measure { display: flex; gap: 6px; margin-top: 8px; }
    .plan-wizard.is-collapsed .plan-wizard-body { display: none; }
    .plan-wizard.is-collapsed .plan-wizard-head { margin-bottom: 0; }
    .plan-fold { border: 1px solid #d9dde2; border-radius: 8px; margin: 6px 0; background: #fff; }
    .plan-fold > summary { cursor: pointer; font-weight: 700; padding: 8px 10px; list-style: none; display: flex; align-items: baseline; gap: 8px; }
    .plan-fold > summary::-webkit-details-marker { display: none; }
    .plan-fold-mark { margin-left: auto; font-size: 11px; font-weight: 500; color: #1d7a3a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 58%; }
    .plan-fold.is-active { border-color: #1f78d1; background: #f3f8ff; }
    .plan-fold-body { padding: 0 10px 10px; }
    .plan-param-tabs { display: flex; gap: 4px; margin: 0 0 8px; }
    .plan-param-tabs button { flex: 1; background: #e8eef4; color: #1b3552; }
    .plan-param-tabs button.is-on { background: #1f78d1; color: #fff; }
    .plan-wp-chips { margin: 0 0 8px; }
    .wp-strip {
      display: flex;
      align-items: stretch;
      gap: 2px;
      background: #121814;
      border-radius: 10px;
      padding: 6px 4px;
    }
    .wp-strip .wp-chev,
    .wp-strip .wp-chev:hover {
      flex: 0 0 22px;
      width: 22px;
      min-height: 52px;
      padding: 0;
      background: transparent;
      color: #e7eee6;
      font-size: 22px;
      font-weight: 700;
      line-height: 1;
    }
    .wp-dots {
      flex: 1 1 auto;
      min-width: 0;
      display: flex;
      gap: 1px;
      overflow-x: auto;
      scrollbar-width: none;
    }
    .wp-dots::-webkit-scrollbar { display: none; }
    .wp-strip .wp-dot,
    .wp-strip .wp-dot:hover {
      flex: 0 0 auto;
      width: 46px;
      min-height: 0;
      padding: 2px 0;
      background: transparent;
      color: #d5ddd4;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 3px;
    }
    .wp-dot .ball {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      border: 2px solid #9aabc4;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 750;
      font-size: 12px;
      line-height: 1;
      color: #e8eef6;
    }
    .wp-dot.is-on .ball { background: #1f78d1; border-color: #1f78d1; color: #fff; }
    .wp-dot .alt { font-size: 10px; line-height: 1; color: #c5d0c4; white-space: nowrap; }
    #planRoutePane .editor-right-drawer-body,
    #planPointPane .editor-right-drawer-body { padding: 0; overflow: visible; }
    #planRoutePane .mission-settings-grid,
    #planPointPane .waypoint-settings-grid { grid-template-columns: 1fr; }
    #planRoutePane .mission-settings-grid .full,
    #planPointPane .waypoint-settings-grid .full { grid-column: auto; }
    #planWpPanel.is-simple #missionSettingsBox > div:not(.plan-ms-basic) { display: none !important; }
    #planWpPanel.is-simple .waypoint-settings-grid > div:not(.plan-wp-check) { display: none !important; }
    #planWpPanel.is-simple #wpDirectionWrap[hidden],
    #planWpPanel.is-simple #wpPoiBox[hidden],
    #planWpPanel.is-simple #wpTurnDistanceWrap[hidden] { display: none !important; }
    #wpPitchWrap[hidden] { display: none !important; }
    #planWpPanel.is-simple #missionSettingsBox > #msGimbalWrap.is-gimbal-open { display: block !important; }
    #msTurnDistanceWrap[hidden], #wpTurnDistanceWrap[hidden] { display: none !important; }
    #planWpPanel.is-simple #wpTabAll,
    #planWpPanel.is-simple #wpAllPane,
    #planWpPanel.is-simple #wpAllNote { display: none !important; }
    #planPointPane .editor-right-drawer-tabs { padding: 0 0 8px; }
    #planPointPane .editor-right-drawer-foot { padding: 8px 0 0; margin-top: 8px; }
    .plan-fold[hidden], .plan-coverage-sub[hidden] { display: none !important; }
    .map-layer-btn svg { width: 18px; height: 18px; display: block; }
    .swiss-overlay-box {
      position: absolute;
      top: 8px;
      left: 8px;
      right: auto;
      z-index: 21;
      width: 320px;
      max-width: calc(100% - 16px);
      font-size: 12px;
      color: #1b3552;
      background: rgba(255, 255, 255, 0.97);
      border: 1px solid #cfd4da;
      border-radius: 6px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }
    .swiss-overlay-box > .basemap-toggle {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 5px 6px;
      margin: 0 10px 8px;
    }
    .swiss-plan-layers {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin: 0 10px 8px;
    }
    .swiss-plan-layers label { width: auto; }
    .swiss-overlay-box > .basemap-toggle .basemap-seg {
      width: auto;
      min-width: 2.15em;
      margin: 0;
      padding: 2px 10px;
      min-height: 22px;
      border: 1px solid #c5cdd4;
      border-radius: 999px;
      background: #fff;
      color: #9aa4ae;
      font-size: 12px;
      font-weight: 600;
      line-height: 1.2;
    }
    .swiss-overlay-box > .basemap-toggle .basemap-seg:hover {
      border-color: #9aa4ae;
      color: #6b7682;
      background: #fff;
    }
    .swiss-overlay-box > .basemap-toggle .basemap-seg.is-on {
      border-color: #4d6b3e;
      color: #3f5a32;
      background: #fff;
    }
    .basemap-select-sr {
      position: absolute;
      width: 1px;
      height: 1px;
      margin: -1px;
      padding: 0;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      border: 0;
    }
    .display-control-box {
      position: absolute;
      right: 14px;
      top: 52px;
      z-index: 22;
      background: rgba(255,255,255,0.95);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 8px 10px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      font-size: 12px;
      color: #222;
      width: 280px;
      max-width: min(300px, calc(100% - 16px));
      max-height: calc(100% - 70px);
      overflow: auto;
      box-sizing: border-box;
    }
    .display-control-box .rkrk-sels,
    .display-control-box .rkrk-reg-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
    }
    .display-control-box .title {
      font-weight: bold;
      margin-bottom: 6px;
      user-select: none;
    }
    .display-control-box.is-collapsed { display: none !important; }
    .swiss-overlay-box.is-collapsed { display: none !important; }
    .swiss-overlay-box > summary { cursor: pointer; user-select: none; }
    .field-characteristics-box.is-collapsed { display: none !important; }
    .field-characteristics-box > summary { cursor: pointer; user-select: none; }
    .display-control-box label:not(.rkrk-sel) {
      display: flex;
      align-items: center;
      gap: 6px;
      margin: 4px 0;
      font-size: 12px;
      color: #222;
    }
    .display-control-box .group-title {
      margin-top: 8px;
      margin-bottom: 3px;
      font-size: 11px;
      font-weight: bold;
      color: #1b3552;
      text-transform: uppercase;
      letter-spacing: 0.02em;
    }
    .display-control-box .group-title:first-of-type {
      margin-top: 2px;
    }
    .display-control-box .group-note {
      margin: 2px 0 4px;
      font-size: 11px;
      color: #5b6f86;
    }
    .display-control-box .control-divider {
      border-top: 1px solid #e1e7ef;
      margin: 6px 0;
    }
    .display-control-box label.is-disabled {
      opacity: 0.55;
    }
    .display-control-box label:not(.rkrk-sel) input[type="checkbox"] {
      width: auto;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    .swiss-overlay-box > summary {
      cursor: pointer;
      font-weight: bold;
      padding: 8px 10px;
      list-style: none;
      user-select: none;
    }
    .swiss-overlay-box > summary::-webkit-details-marker { display: none; }
    .swiss-overlay-list {
      padding: 0 8px 8px;
      display: grid;
      gap: 3px;
      max-height: min(52vh, 440px);
      overflow: auto;
    }
    .swiss-overlay-item {
      display: flex;
      flex-wrap: nowrap;
      align-items: center;
      gap: 6px 8px;
      margin: 5px 0;
      min-height: 22px;
      font-size: 13px;
      color: #333;
    }
    .swiss-overlay-item label.rkrk-sel {
      margin: 0;
      flex: 1 1 0;
      min-width: 0;
      justify-content: flex-start;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .swiss-overlay-item .opacity-wrap {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      flex: 0 0 auto;
      color: #58697a;
      font-size: 11px;
      white-space: nowrap;
    }
    .swiss-overlay-item input[type="range"] {
      width: 78px;
      accent-color: #48673A;
      height: 18px;
      margin: 0;
      padding: 0;
      border: 0;
      background: transparent;
    }
    .swiss-overlay-item .opacity-wrap span {
      text-align: right;
      font-variant-numeric: tabular-nums;
      color: #46617d;
      font-size: 11px;
      white-space: nowrap;
    }
    .swiss-overlay-item.overlay-subopt {
      padding-left: 16px;
    }
    .swiss-overlay-item.overlay-subopt label {
      grid-column: 2 / -1;
    }
    .swiss-overlay-item.overlay-subopt.is-disabled {
      opacity: 0.45;
    }
    .swiss-overlay-item.overlay-subopt.is-disabled label {
      cursor: default;
    }
    #overlayStatus {
      margin: 6px 2px 0;
      font-size: 11px;
      line-height: 1.3;
      color: #5b6f86;
    }
    .rkrk-dabs-popup {
      position: absolute;
      background: rgba(255, 255, 255, 0.97);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 10px 28px 10px 12px;
      min-width: 220px;
      max-width: 420px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.16);
      font-size: 12px;
      color: #1b3552;
    }
    .rkrk-dabs-popup .pop-title { font-weight: 700; margin-bottom: 4px; }
    .rkrk-dabs-popup .pop-desc { line-height: 1.35; margin-top: 6px; overflow-wrap: anywhere; }
    .rkrk-dabs-popup .pop-close {
      position: absolute; top: 4px; right: 6px; border: 0; background: transparent;
      cursor: pointer; font-size: 16px; line-height: 1;
    }
    #editorMap {
      width: 100%;
      height: 100vh;
      border: 1px solid #d9dde2;
      border-radius: 8px;
      overflow: hidden;
    }
    .map-wrap .ol-attribution {
      left: 8px;
      right: auto;
      bottom: 8px;
    }
    #rkrkMapCorner {
      position: absolute;
      right: 10px;
      bottom: 10px;
      z-index: 24;
      display: flex;
      align-items: flex-end;
      gap: 8px;
      pointer-events: none;
    }
    #rkrkMapLabelSize {
      pointer-events: auto;
      display: flex;
      gap: 4px;
    }
    #rkrkMapLabelSize button {
      min-width: 36px;
      height: 32px;
      border: 1px solid rgba(0,0,0,0.28);
      border-radius: 6px;
      background: rgba(255,255,255,0.92);
      color: #111827;
      font: 700 14px/1 Arial, Helvetica, sans-serif;
      cursor: pointer;
      box-shadow: 0 1px 6px rgba(0,0,0,0.16);
    }
    #rkrkMapScaleBar {
      display: block;
      position: static;
      z-index: 24;
      padding: 5px 8px 4px;
      border-radius: 6px;
      background: rgba(255,255,255,0.92);
      border: 1px solid rgba(0,0,0,0.28);
      color: #111827;
      font: 600 12px/1.2 Arial, Helvetica, sans-serif;
      box-shadow: 0 1px 6px rgba(0,0,0,0.16);
      user-select: none;
      pointer-events: none;
    }
    #rkrkMapScaleBar .rkrk-scale-ratio { display: block; margin: 0 0 3px; white-space: nowrap; }
    #rkrkMapScaleBar .rkrk-scale-track {
      display: flex;
      height: 8px;
      border: 1px solid #111827;
      width: 100%;
      box-sizing: border-box;
    }
    #rkrkMapScaleBar .rkrk-scale-track span { flex: 1 1 0; background: #111827; }
    #rkrkMapScaleBar .rkrk-scale-track span:nth-child(even) { background: #fff; }
    #rkrkMapScaleBar .rkrk-scale-caps {
      display: flex;
      justify-content: space-between;
      margin-top: 2px;
      font: 500 10px/1.1 Arial, Helvetica, sans-serif;
      color: #111827;
    }
    .map-help { margin-top: 2px; font-size: 11px; color: #5b6f86; }
    .map-quick-help {
      position: absolute;
      top: 52px;
      left: 8px;
      z-index: 29;
      background: rgba(255,255,255,0.96);
      border: 1px solid #d9dde2;
      border-radius: 6px;
      padding: 6px 8px;
      font-size: 12px;
      color: #2e4158;
      display: block;
      width: 360px;
      min-height: 72px;
      max-width: calc(100% - 64px);
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      overflow: auto;
    }
    .map-quick-help-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 4px;
    }
    .map-quick-help-head strong { font-size: 12px; color: #1b3552; }
    .map-quick-help-close {
      width: auto !important;
      min-width: 0 !important;
      height: 24px;
      padding: 0 8px !important;
      border-radius: 4px;
      line-height: 22px;
      font-size: 11px;
      background: #e8eef4;
      color: #1b3552;
      cursor: pointer;
      flex: 0 0 auto;
    }
    .map-quick-help-close:hover { background: #d5dee8; }
    .editor-right-drawer {
      position: absolute;
      top: 0;
      right: 0;
      height: 100%;
      width: min(460px, calc(100% - 16px));
      z-index: 40;
      pointer-events: none;
      transform: translateX(100%);
      transition: transform 0.28s ease;
    }
    .editor-right-drawer.open {
      transform: translateX(0);
      pointer-events: none;
    }
    .editor-right-drawer-panel {
      height: 100%;
      display: flex;
      flex-direction: column;
      background: rgba(248,251,255,0.98);
      border-left: 1px solid #d9dde2;
      box-shadow: -4px 0 18px rgba(11, 24, 37, 0.18);
      pointer-events: auto;
    }
    .editor-right-drawer-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
      padding: 12px 12px 8px;
      border-bottom: 1px solid #e1e7ef;
      flex-shrink: 0;
    }
    .editor-right-drawer-head h2 {
      margin: 0;
      font-size: 18px;
      color: #1b3552;
    }
    .editor-right-drawer-sub {
      margin-top: 2px;
      font-size: 12px;
      color: #5b6f86;
    }
    .editor-right-drawer-close {
      width: 28px !important;
      min-width: 28px !important;
      height: 28px;
      padding: 0 !important;
      border-radius: 6px;
      background: #e8eef4;
      color: #1b3552;
      font-size: 20px;
      line-height: 26px;
    }
    .editor-right-drawer-close:hover { background: #d5dee8; }
    .editor-right-drawer-tabs {
      display: grid;
      grid-template-columns: auto 1fr 1fr auto;
      gap: 4px;
      padding: 8px 12px 0;
      flex-shrink: 0;
    }
    .editor-right-drawer-tabs button {
      width: auto;
      min-width: 0;
      padding: 6px 8px;
      font-size: 12px;
      background: #e8eef4;
      color: #1b3552;
    }
    .editor-right-drawer-tabs button.active {
      background: #1f78d1;
      color: #fff;
    }
    .editor-right-drawer-body {
      flex: 1;
      overflow: auto;
      padding: 10px 12px 12px;
    }
    .editor-right-drawer-foot {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 8px;
      padding: 10px 12px 12px;
      border-top: 1px solid #e1e7ef;
      flex-shrink: 0;
    }
    .editor-right-drawer-foot button { width: 100%; }
    .editor-right-drawer-foot .secondary { background: var(--brand, #96B588); }
    .editor-right-drawer-foot .danger { background: #c0392b; }
    .editor-right-drawer-foot .danger:hover { background: #a93226; }
    .mission-settings-grid,
    .waypoint-settings-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .mission-settings-grid .full,
    .waypoint-settings-grid .full {
      grid-column: 1 / span 2;
    }
    .wp-req::after {
      content: ' *';
      color: #1f78d1;
      font-weight: bold;
    }
    .wp-hint {
      font-size: 11px;
      color: #5b6f86;
      margin-top: 2px;
    }
    .wp-check {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: #344f6d;
    }
    .wp-check input { width: auto; }
    .wp-slider-row {
      display: grid;
      grid-template-columns: 74px 1fr auto;
      gap: 6px;
      align-items: center;
    }
    .wp-alt-step {
      display: grid;
      grid-template-columns: 36px 1fr 36px auto;
      gap: 6px;
      align-items: center;
      margin-bottom: 6px;
    }
    .wp-alt-step button,
    .wp-alt-step button:hover {
      width: 36px;
      height: 36px;
      padding: 0;
      background: #e8eef4;
      color: #1b3552;
      font-size: 18px;
      font-weight: 700;
    }
    .wp-alt-step input { text-align: center; font-weight: 700; }
    .plan-wp-height input[type="range"] { width: 100%; margin: 0 0 4px; padding: 0; }
    .wp-unit { font-size: 12px; color: #5b6f86; }
    .wp-all-list {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .wp-all-item {
      display: flex;
      justify-content: space-between;
      gap: 8px;
      width: 100%;
      text-align: left;
      background: #edf4fc;
      color: #23405f;
      padding: 8px;
      font-size: 12px;
    }
    .wp-all-item.active { background: #1f78d1; color: #fff; }
    .wp-all-item .meta { font-size: 11px; opacity: 0.85; }
    .field-characteristics-box {
      position: absolute;
      top: 520px;
      right: 8px;
      z-index: 22;
      width: min(320px, calc(100% - 24px));
      border: 1px solid #d9dde2;
      border-radius: 8px;
      background: rgba(248,251,255,0.97);
      padding: 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    }
    .field-characteristics-box summary {
      cursor: pointer;
      font-weight: bold;
      color: #1b3552;
      margin-bottom: 6px;
    }
    .field-characteristics-grid {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 6px 10px;
      align-items: center;
      font-size: 12px;
      color: #2e4158;
    }
    .field-characteristics-grid .value {
      font-weight: bold;
      color: #1b3552;
      white-space: nowrap;
    }
    #wpDebugTableBox {
      --wp-box-max: min(46vh, 320px);
      position: absolute;
      left: 8px;
      top: 72px;
      right: auto;
      bottom: auto;
      z-index: 26;
      width: min(1080px, calc(100% - 24px));
      max-height: var(--wp-box-max);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-width: 0;
      min-height: 0;
      box-sizing: border-box;
      border: 1px solid #d9dde2;
      border-radius: 8px;
      background: rgba(248,251,255,0.97);
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      color: #1b3552;
      padding: 8px;
    }
    #wpDebugTableBox[hidden] { display: none !important; }
    #showBoxWaypointDataLabel[hidden] { display: none !important; }
    #coverageWindowChoice[hidden] { display: none !important; }
    #coverageWindowChoice:not([hidden]) { display: contents; }
    #wpDebugTableBox > summary {
      flex: 0 0 auto;
      cursor: pointer;
      user-select: none;
      font-weight: bold;
      color: #1b3552;
      margin-bottom: 6px;
    }
    #wpDebugTableBox:not([open]) > summary { margin-bottom: 0; }
    #wpDebugTableBox::details-content {
      flex: 1 1 auto;
      min-height: 0;
      min-width: 0;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    .wp-debug-scroll {
      flex: 1 1 auto;
      min-height: 0;
      min-width: 0;
      max-width: 100%;
      max-height: calc(var(--wp-box-max) - 44px);
      overflow: auto;
    }
    #wpDebugTable {
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      table-layout: fixed;
      font-size: 12px;
    }
    #wpDebugTable col.wp-col-nr { width: 7%; }
    #wpDebugTable col.wp-col-terrain { width: 16%; }
    #wpDebugTable col.wp-col-delta { width: 13%; }
    #wpDebugTable col.wp-col-prog { width: 15%; }
    #wpDebugTable col.wp-col-rel { width: 18%; }
    #wpDebugTable col.wp-col-yaw { width: 18%; }
    #wpDebugTable col.wp-col-gimbal { width: 13%; }
    #wpDebugTable th {
      position: sticky;
      top: 0;
      z-index: 1;
      background: #e8eef4;
      text-align: left;
      vertical-align: bottom;
      padding: 4px 6px;
      white-space: normal;
      line-height: 1.2;
      border-bottom: 1px solid #d9dde2;
    }
    #wpDebugTable td {
      padding: 3px 6px;
      border-top: 1px solid #e6ebf1;
      white-space: normal;
      vertical-align: top;
      overflow-wrap: anywhere;
    }
    #wpDebugTable td.num {
      font-family: Consolas, ui-monospace, monospace;
      text-align: right;
    }
    #wpDebugTable td.wp-debug-empty { text-align: left; font-family: Arial, sans-serif; }
    .editor-form-drawer {
      position: absolute;
      left: 14px;
      right: 14px;
      bottom: 14px;
      z-index: 22;
    }
    .editor-form-drawer > summary {
      cursor: pointer;
      background: rgba(255,255,255,0.95);
      border: 1px solid #d9dde2;
      border-radius: 8px;
      padding: 8px 10px;
      font-weight: bold;
      color: #1b3552;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      user-select: none;
    }
    .editor-form-drawer[open] > summary {
      border-bottom-left-radius: 0;
      border-bottom-right-radius: 0;
    }
    .editor-form-content {
      background: rgba(255,255,255,0.97);
      border: 1px solid #d9dde2;
      border-top: 0;
      border-bottom-left-radius: 8px;
      border-bottom-right-radius: 8px;
      padding: 10px;
      max-height: 42vh;
      overflow: auto;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    }
    #flaechendaten {
      width: 100%;
      min-width: 100%;
      box-sizing: border-box;
    }
    .editor-title-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin: 0 0 8px;
      position: relative;
      z-index: 40;
    }
    .editor-title-row h1 { margin: 0; min-width: 0; }
    .map-drone-pick {
      position: relative;
      flex: 0 0 auto;
      z-index: 40;
    }
    #mapDroneBtn {
      width: auto;
      max-width: 190px;
      min-height: 34px;
      height: 34px;
      padding: 0 12px;
      border-radius: 999px;
      background: rgba(255,255,255,0.96);
      color: #243528;
      border: 1px solid #cfd6de;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      font-weight: 700;
      font-size: 13px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .map-drone-menu {
      position: absolute;
      top: 40px;
      right: 0;
      min-width: 230px;
      max-height: 280px;
      overflow: auto;
      background: #fff;
      border: 1px solid #cfd6de;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.18);
      padding: 4px;
    }
    .map-drone-menu[hidden] { display: none !important; }
    .map-drone-menu button {
      display: block;
      width: 100%;
      height: auto;
      min-height: 32px;
      margin: 0;
      padding: 6px 8px;
      text-align: left;
      border-radius: 6px;
      background: #fff;
      color: #1b3552;
      border: 0;
      box-shadow: none;
      font-weight: 600;
      font-size: 13px;
      white-space: normal;
    }
    .map-drone-menu button.is-on,
    .map-drone-menu button.is-on:hover {
      background: var(--brand-dark, #48673A);
      color: #fff;
    }
    .map-setup-btn {
      position: absolute;
      top: 8px;
      right: 8px;
      z-index: 30;
      width: 34px;
      height: 34px;
      padding: 0;
      border-radius: 999px;
      font-size: 18px;
      line-height: 34px;
      text-align: center;
      background: rgba(255,255,255,0.96);
      color: #1b3552;
      border: 1px solid #cfd6de;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      cursor: pointer;
    }
    .map-help-btn {
      position: absolute;
      top: 8px;
      right: 48px;
      z-index: 30;
      width: 34px;
      height: 34px;
      padding: 0;
      border-radius: 999px;
      font-size: 16px;
      line-height: 34px;
      text-align: center;
      background: rgba(255,255,255,0.96);
      color: #1f78d1;
      border: 1px solid #cfd6de;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      cursor: help;
    }
    .map-dock-btn {
      position: absolute;
      top: 8px;
      z-index: 30;
      width: 34px;
      min-width: 34px;
      height: 34px;
      padding: 0;
      border-radius: 999px;
      font-size: 16px;
      line-height: 34px;
      text-align: center;
      background: rgba(255,255,255,0.96);
      color: #1f78d1;
      border: 1px solid #cfd6de;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      cursor: pointer;
    }
    .map-dock-btn[hidden] { display: none !important; }
    .map-dock-btn.is-on { background: #1f78d1; color: #fff; }
    .map-layer-btn { right: 208px; display: flex; align-items: center; justify-content: center; }
    .map-layer-btn.is-on { background: rgba(255,255,255,0.96); color: inherit; box-shadow: 0 0 0 2px #1f78d1, 0 1px 4px rgba(0,0,0,0.15); }
    .map-dock-icon { width: 22px; height: 22px; display: block; pointer-events: none; }
    .map-field-btn { right: 168px; }
    .map-display-btn {
      position: absolute;
      top: 8px;
      right: 128px;
      z-index: 30;
      width: 34px;
      min-width: 34px;
      height: 34px;
      padding: 0;
      border-radius: 999px;
      font-size: 16px;
      line-height: 34px;
      text-align: center;
      background: rgba(255,255,255,0.96);
      color: #1f78d1;
      border: 1px solid #cfd6de;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    }
    .map-find-btn {
      position: absolute;
      top: 8px;
      right: 88px;
      z-index: 30;
      width: 34px;
      height: 34px;
      padding: 0;
      border-radius: 999px;
      font-size: 16px;
      line-height: 34px;
      text-align: center;
      background: rgba(255,255,255,0.96);
      color: #1f78d1;
      border: 1px solid #cfd6de;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      cursor: pointer;
    }
    .map-setup-panel {
      position: absolute;
      top: 46px;
      right: 8px;
      z-index: 30;
      background: rgba(255,255,255,0.97);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      padding: 8px;
      min-width: 210px;
      display: none;
      font-size: 12px;
      color: #1b3552;
    }
    .map-setup-panel.show { display: block; }
    .map-setup-panel .title {
      font-weight: bold;
      margin-bottom: 6px;
    }
    .map-setup-panel label {
      display: flex;
      align-items: center;
      gap: 6px;
      margin: 4px 0;
      font-size: 12px;
      color: #2e4158;
    }
    .map-setup-panel input[type="checkbox"] {
      width: auto;
      margin: 0;
      padding: 0;
    }
    .map-setup-panel .group-title {
      margin-top: 8px;
      margin-bottom: 3px;
      font-size: 11px;
      font-weight: bold;
      color: #1b3552;
      text-transform: uppercase;
      letter-spacing: 0.02em;
    }
    .map-setup-panel .group-title:first-of-type {
      margin-top: 2px;
    }
    .map-setup-panel .group-note {
      margin: 2px 0 4px;
      font-size: 11px;
      color: #5b6f86;
    }
    .map-setup-panel .control-divider {
      border-top: 1px solid #e1e7ef;
      margin: 6px 0;
    }
    .map-setup-panel label.is-disabled {
      opacity: 0.55;
    }
    .create-modal-backdrop {
      position: fixed;
      inset: 0;
      z-index: 80;
      background: rgba(11, 24, 37, 0.45);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 18px;
    }
    .create-modal-backdrop.show { display: flex; }
    .create-modal {
      width: min(860px, 96vw);
      max-height: 90vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      background: #fff;
      border: 1px solid #d9dde2;
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.24);
      padding: 12px;
    }
    .create-modal h2 {
      margin: 0 0 8px;
      font-size: 18px;
      color: #1b3552;
    }
    .create-modal .small-note {
      font-size: 12px;
      color: #58708a;
      margin-bottom: 10px;
    }
    .create-preview-note {
      margin: 0 0 10px;
      padding: 8px;
      border-radius: 6px;
      background: #fdecec;
      border: 1px solid #efc5c5;
      color: #a22b2b;
      font-size: 13px;
    }
    .create-preview-note[hidden] { display: none !important; }
    .create-tabs {
      display: flex;
      flex-wrap: nowrap;
      align-items: stretch;
      gap: 6px;
      margin: 0 0 10px;
    }
    .create-tabs button {
      width: auto;
      flex: 1 1 0;
      min-width: 0;
      padding: 6px 8px;
    }
    .plan-pics { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
    .plan-pics:empty { display: none; }
    .plan-pics a { display: block; width: calc(50% - 4px); }
    .plan-pics img { width: 100%; height: 140px; object-fit: cover; border-radius: 6px; background: #e7ece4; }
    .create-tab-panel {
      overflow: auto;
      min-height: 0;
      flex: 1;
    }
    .create-tab-panel[hidden] { display: none !important; }
    .create-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .create-grid .full { grid-column: 1 / span 2; }
    .create-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      margin-top: 10px;
    }
    .create-actions button {
      width: auto;
      min-width: 140px;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="left" id="editorLeftPanel">
      <div class="editor-title-row">
        <h1 class="rkr-brand-header"><?php echo mapll_favicon_mark_html($mapllBrand); ?>Feld-Editor</h1>
        <div id="mapDronePick" class="map-drone-pick">
          <button id="mapDroneBtn" type="button" title="Drohnentyp ändern">Mavic 3T</button>
          <div id="mapDroneMenu" class="map-drone-menu" hidden></div>
        </div>
      </div>
      <div class="small"><?php echo $rkrkKat
        ? 'Bestehende Felder bearbeiten oder neue Felder in den Feldkatalog speichern.'
        : 'Feld planen und lokal als KML oder JSON herunterladen. Katalogauswahl und Serverspeichern nur mit ?KAT.'; ?></div>
      <div class="toplinks rkrk-sels rkrk-reg-pills">
        <button id="backBtn" type="button" class="rkrk-sel rkrk-reg-pill" title="Zur Analyse zurueck">Zurueck</button>
        <a href="analyse_ui.php" target="_blank" rel="noopener" class="rkrk-sel rkrk-reg-pill plan-admin-only" hidden title="Analyse im neuen Fenster">Analyse</a>
        <a href="rkrk_help.php" data-rkrk-help class="rkrk-sel rkrk-reg-pill" title="Hilfe zur aktuellen Sicht (F1)">Hilfe</a>
        <a href="requirements.php" target="_blank" rel="noopener" class="rkrk-sel rkrk-reg-pill plan-admin-only" hidden title="Requirements anzeigen">Requirements</a>
        <a href="rkrk_feldeditor_spec.php" target="_blank" rel="noopener" class="rkrk-sel rkrk-reg-pill" title="Spezifikation anzeigen">Spec</a>
        <a href="mapping_matrix.php" target="_blank" rel="noopener" class="rkrk-sel rkrk-reg-pill plan-admin-only" hidden title="Interface-Mapping anzeigen">Mapping</a>
      </div>
      <details id="planWizard" class="plan-wizard menu-fold" open>
        <summary>
          <span>Feldplanung</span>
          <button id="planModeToggle" class="plan-mode" type="button" title="Einfacher Modus oder Expertenmodus">Einfacher Modus</button>
        </summary>
        <div class="plan-wizard-body">
          <div id="planStepRail" class="plan-rail plan-status">Status: Fläche zeichnen</div>
          <details class="plan-fold" data-plan-phase="outline" open>
            <summary><span>1 · Fläche</span><span class="plan-fold-mark" hidden></span></summary>
            <div class="plan-fold-body">
              <div class="plan-rail">Ausgang</div>
              <div class="plan-choice">
                <button id="planOriginExisting" type="button">Bestehendes Feld</button>
                <button id="planOriginNew" type="button">Neues Feld</button>
              </div>
              <div id="planExistingPick" hidden>
                <input id="planExistingSearch" placeholder="Katalognummer oder Name" />
                <div id="planExistingList" class="plan-existing-list"></div>
              </div>
              <button id="planStepPlace" class="plan-step" type="button">🔎 Ort suchen</button>
              <div class="plan-point-list">
                <div class="plan-point-row">
                  <button id="planStepPolygon" class="plan-step plan-point-hit" type="button"><span class="plan-point-ico" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 16 16" focusable="false"><path d="M8 1.4 14.4 5.9 12.1 14.4H3.9L1.6 5.9Z" fill="#e04e7e"/></svg></span><span class="plan-point-copy"><span class="plan-point-label">Feldpolygon zeichnen</span><span id="planPolygonNote" class="plan-note plan-coord"></span></span></button>
                </div>
              </div>
              <p class="plan-note">Umrandung auf der Karte zeichnen. Doppelklick beendet. Jagdgebiet, Gemeinde und die GIS-Nummer folgen erst aus dieser Fläche.</p>
              <div class="plan-rail">Lagen zur Prüfung</div>
              <div class="plan-pills rkrk-sels rkrk-reg-pills">
                <label class="rkrk-sel rkrk-reg-pill" title="Drohnenzonen der RPAS-Karte auf die Karte legen"><input id="planLayerRpas" type="checkbox" /> RPAS</label>
                <label class="rkrk-sel rkrk-reg-pill" title="Strom-Infrastruktur auf die Karte legen"><input id="planLayerElectric" type="checkbox" /> Strom</label>
                <label class="rkrk-sel rkrk-reg-pill" title="Luftfahrthindernisse auf die Karte legen"><input id="planLayerObstacles" type="checkbox" /> Hindernisse</label>
                <label class="rkrk-sel rkrk-reg-pill" title="DABS bis 200 m AGL auf die Karte legen"><input id="planLayerDabs" type="checkbox" /> DABS</label>
                <label class="rkrk-sel rkrk-reg-pill" title="Flugzonen aus der Feldkarte (FZ) auf die Karte legen"><input id="planLayerFz" type="checkbox" /> Flugzone</label>
                <label class="rkrk-sel rkrk-reg-pill" title="swissSURFACE3D Relief, standard aus"><input id="planLayerSurface3d" type="checkbox" /> Relief</label>
              </div>
              <p id="planLayerCheckNote" class="plan-note" hidden></p>
              <div id="planGisBlock" hidden>
                <label for="planGis">GIS-Nummer</label>
                <input id="planGis" inputmode="numeric" placeholder="aus der gezeichneten Fläche" />
                <p id="planGisNote" class="plan-note" hidden>GIS-Nummer fehlt. CadastralWebMap schlägt sie vor, sonst hier eintragen.</p>
                <p id="planGisSuggest" class="plan-note" hidden></p>
                <button id="planGisApply" class="plan-quiet" type="button" hidden>Vorschlag übernehmen</button>
              </div>
              <label for="planDroneType">Drohnentyp</label>
              <select id="planDroneType">
                <option value="Mavic 3T" selected>Mavic 3T · Feldkatalog</option>
              </select>
              <p id="planDroneCatalogNote" class="plan-note">Feldkatalog: Mavic 3T, Matrice 4TD und Mavic 3T Debug. Matrice 4T, Matrice 3TD und Mavic 3TA haben keinen Feldkatalog. Mavic II und Matrice 30T sind aus der Auswahl entfernt. Ein neuer Katalog wird nur in der Website-Konfiguration angelegt.</p>
              <label>Flughöhe</label>
              <div class="plan-stepper">
                <button id="planAltMinus" type="button" title="Flughöhe senken">−</button>
                <span id="planAltLabel">60 m</span>
                <button id="planAltPlus" type="button" title="Flughöhe erhöhen">+</button>
              </div>
              <div id="planAltHost"></div>
            </div>
          </details>
          <details class="plan-fold" data-plan-phase="start">
            <summary><span>2 · Start</span><span class="plan-fold-mark" hidden></span></summary>
            <div class="plan-fold-body">
              <p class="plan-note">Startpunkt setzen. Direkt zum Startpunkt fliegen lässt den Anflug die berechnete Reihenfolge.</p>
              <div class="plan-point-list">
                <div class="plan-point-row">
                  <button id="planStepStart" class="plan-step plan-point-hit" type="button"><span class="plan-point-ico" aria-hidden="true">📍</span><span class="plan-point-copy"><span class="plan-point-label">Startpunkt setzen</span><span id="planStartCoord" class="plan-note plan-coord"></span></span></button>
                </div>
                <div id="planParkRow" class="plan-point-row" hidden>
                  <button id="planStepPark" class="plan-point-hit" type="button"><span class="plan-point-ico" aria-hidden="true">🅿</span><span class="plan-point-copy"><span class="plan-point-label">Parkplatz setzen</span><span id="planParkCoord" class="plan-note plan-coord">Parkplatz nur, wenn er sich vom Startpunkt unterscheidet.</span></span></button>
                  <button id="planParkClear" class="plan-quiet" type="button" hidden>Parkplatz entfernen</button>
                </div>
                <div id="planPoiRow" class="plan-point-row" hidden>
                  <button id="planStepPoi" class="plan-point-hit" type="button"><span class="plan-point-ico" aria-hidden="true">◎</span><span class="plan-point-copy"><span class="plan-point-label">POI setzen</span><span id="planPoiCoord" class="plan-note plan-coord">POI für die Gierrichtung Fokus POI.</span></span></button>
                  <button id="planPoiClear" class="plan-quiet" type="button" hidden>POI entfernen</button>
                </div>
              </div>
            </div>
          </details>
          <details class="plan-fold" data-plan-phase="optimize">
            <summary><span>3 · Flugpfad</span><span class="plan-fold-mark" hidden></span></summary>
            <div id="planReview" class="plan-fold-body">
              <p class="plan-note">Wegpunkte ziehen, + setzt einen neuen, Doppelklick löscht. Danach werden Flugpfad und Kennwerte sofort neu berechnet. Die Wahl nach dem Start ordnet den Anflug neu.</p>
              <label class="plan-checkline"><input id="planShowCoverage" type="checkbox" checked /> Suchabdeckung</label>
              <label class="plan-checkline plan-coverage-sub"><input id="planShowUncovered" type="checkbox" checked /> Nicht abgedeckte Flächen</label>
              <div class="plan-rail">Höhenanzeige</div>
              <label class="plan-checkline"><input id="planShowRouteHeights" type="checkbox" /> Auf der Route</label>
              <label class="plan-checkline"><input id="planShowAglLimit" type="checkbox" checked title="Roter Punkt, wo die reale Flughöhe über Grund von der Sollflughöhe um mehr als ±10 m abweicht." /> Abweichung von der Sollflughöhe</label>
              <label class="plan-checkline"><input id="planShowFieldHeights" type="checkbox" /> Im Feld</label>
              <div class="plan-rail">Zweiter Wegpunkt nach dem Start</div>
              <div class="plan-choice">
                <button id="planEntrySecond" type="button">2.</button>
                <button id="planEntrySecondLast" type="button">vorletzter</button>
                <button id="planEntryLast" type="button">letzter</button>
              </div>
              <div class="plan-rail">Fluggeschwindigkeit</div>
              <div class="plan-stepper">
                <button id="planSpeedMinus" type="button" title="0,5 m/s weniger">−</button>
                <input id="planSpeed" type="number" min="1" max="15" step="0.5" value="4.5" inputmode="decimal" title="Fluggeschwindigkeit der Route, Vorgabe 4,5 m/s, Schritte 0,5" />
                <button id="planSpeedPlus" type="button" title="0,5 m/s mehr">+</button>
              </div>
              <label class="plan-checkline" id="planStartSpeedToggle"><input id="msStartSpeedActive" type="checkbox" /> Startgeschwindigkeit einschalten</label>
              <div class="plan-stepper" id="planStartSpeedRow">
                <input id="msStartSpeed" type="number" min="0.1" max="15" step="0.1" value="4.5" inputmode="decimal" title="Startgeschwindigkeit. Im einfachen Modus und bei ausgeschaltetem Schalter gleich der Fluggeschwindigkeit." />
              </div>
              <div class="plan-rail">Flughöhe (Mission)</div>
              <div class="plan-stepper">
                <button id="planMissionAltMinus" type="button" title="1 m weniger">−</button>
                <input id="planMissionAlt" type="number" min="5" max="150" step="1" value="60" inputmode="numeric" title="Missionsflughöhe, ganze Meter" />
                <button id="planMissionAltPlus" type="button" title="1 m mehr">+</button>
              </div>
              <div class="plan-rail">Flugrichtung</div>
              <div class="plan-dir">
                <input id="planDirRange" type="range" min="0" max="179" step="1" value="0" title="Generelle Flugrichtung im Feld. Verschieben übersteuert den Vorschlag." />
                <span id="planDirLabel">Vorschlag</span>
              </div>
              <div class="plan-dir-step">
                <button id="planDirMinus5" type="button" title="Flugrichtung 5 Grad weniger">−5</button>
                <button id="planDirMinus1" type="button" title="Flugrichtung 1 Grad weniger">−1</button>
                <button id="planDirPlus1" type="button" title="Flugrichtung 1 Grad mehr">+1</button>
                <button id="planDirPlus5" type="button" title="Flugrichtung 5 Grad mehr">+5</button>
              </div>
              <div class="plan-quiet-row">
                <button id="planOptimizeDir" class="plan-quiet" type="button">Zurück zum Vorschlag</button>
              </div>
              <button id="planResetPath" type="button" hidden>↺ Zurück zum Originalpfad</button>
              <div class="plan-quiet-row">
                <button id="planStepRoute" class="plan-quiet" type="button">Route neu rechnen</button>
                <button id="planOptimizeDone" class="plan-quiet" type="button">Optimierung abschliessen</button>
              </div>
            </div>
          </details>
          <details class="plan-fold" data-plan-phase="waypoints">
            <summary><span>4 - Prüfen</span><span class="plan-fold-mark" hidden></span></summary>
            <div id="planWpPanel" class="plan-fold-body is-simple">
              <div class="plan-param-tabs">
                <button id="planTabRoute" type="button">Flugroute</button>
                <button id="planTabPoint" class="is-on" type="button">Wegpunkt</button>
              </div>
              <div id="planRoutePane" hidden></div>
              <div id="planPointPane">
                <div id="planWpChips" class="plan-wp-chips"></div>
                <p id="planWpCaption" class="plan-note">Wegpunkt wählen. Die Flughöhe gilt nur für diesen Punkt.</p>
                <div id="planWpHost"></div>
              </div>
              <button id="planStepWp" type="button" hidden>Wegpunktwerte</button>
              <div class="plan-quiet-row">
                <button id="planWpDone" class="plan-quiet" type="button">Weiter zu Speichern</button>
              </div>
            </div>
          </details>
          <details class="plan-fold" data-plan-phase="place">
            <summary><span>5 · Speichern</span><span class="plan-fold-mark" hidden></span></summary>
            <div class="plan-fold-body">
              <label for="planFieldName">Feldname</label>
              <input id="planFieldName" placeholder="Feldname" />
              <label for="planFeldId">Feldkatalognummer</label>
              <input id="planFeldId" autocomplete="off" placeholder="freie Nummer" title="Freie Nummer. Eine schon vergebene Nummer muss beim Speichern bestätigt werden." />
              <p id="planFeldIdPlace" class="plan-note plan-feld-place" hidden></p>
              <label for="planSaveGis">GIS-Nummer</label>
              <input id="planSaveGis" inputmode="numeric" autocomplete="off" placeholder="aus der gezeichneten Fläche" />
              <p class="plan-note">Speichern schreibt Feldname, Feldkatalognummer und GIS-Nummer so, wie sie hier stehen. Eine schon vergebene Katalognummer muss bestätigt werden. Ohne ?KAT bleiben KML und JSON auf diesem Gerät.</p>
              <button id="planStepSave" type="button">Speichern</button>
              <div id="debugTfBox" hidden>
                <p class="plan-note">DEBUG: nächste freie TF-Nummer und Zielordner. Dieser Knopf schreibt keinen Katalog und keine Missionsdatei.</p>
                <label for="debugTfId">TF-Katalognummer</label>
                <input id="debugTfId" autocomplete="off" />
                <p id="debugTfDir" class="plan-note"></p>
                <button id="debugTfBtn" type="button">TF-Testfeld vorbereiten</button>
              </div>
            </div>
          </details>
          <details class="plan-fold" data-plan-phase="finish">
            <summary><span>6 · Daten</span><span class="plan-fold-mark" hidden></span></summary>
            <div class="plan-fold-body">
              <p class="plan-note">Zusätzliche Feldkatalogdaten nach dem Speichern. Jagdgebiet, Gemeinde und Katalognummer kommen aus der Umrandung. Die GIS-Nummer steht schon aus der gezeichneten Fläche.</p>
              <button id="planCheck" type="button">Felddaten öffnen</button>
            </div>
          </details>
          <div class="plan-measure">
            <button id="planMeasureBtn" type="button" title="Punkte auf die Karte setzen. Doppelklick beendet.">📏 Distanz</button>
            <button id="planMeasureClearBtn" type="button">Messung löschen</button>
          </div>
          <div id="planAssign" class="plan-assign">Neues Feld startet ohne Umrandung. Zuerst den Ort suchen, dann die Umrandung zeichnen.</div>
          <div class="plan-actions">
            <button id="planSave" type="button">💾 Speichern</button>
            <button id="planJson" class="secondary" type="button">📄 JSON</button>
            <button id="planKmz" class="secondary" type="button">📦 KMZ</button>
          </div>
          <div class="plan-note">Dieselben sechs Schritte wie auf der Fernsteuerung: Fläche, Start, Flugpfad, 4 - Prüfen, Speichern, Daten. Die GIS-Nummer wird erst gelesen, wenn die Fläche gezeichnet ist.</div>
          <div class="plan-actions plan-pic-actions">
            <button id="planPicRenew" type="button">Bilder erneuern</button>
            <button id="planPicClose" type="button" title="Speichert dasselbe Feld nochmals und erneuert Suchfeld- und Flugplanbild.">Abschliessen</button>
          </div>
          <p id="planPicNote" class="plan-note">Erneuert Suchfeld- und Flugplanbild des gespeicherten Feldes von der Katalogkarte. Das Feld selbst wird nicht noch einmal angelegt.</p>
          <div class="map-help"><span id="wsSourceInfo">S/W Quelle: -</span></div>
        </div>
      </details>
      <details id="quickSlotBox" class="quick-slot-box menu-fold">
        <summary>Schnellzugriff Felder</summary>
        <div class="menu-fold-body">
          <div class="quick-slot-help">Klick: laden · Alt+Klick: aktuelles Feld speichern</div>
          <div id="quickSlotGridEditor" class="quick-slot-grid"></div>
        </div>
      </details>
      <details id="fieldSearchBox" class="card menu-fold">
        <summary>
          <span>Feld suchen</span>
          <span class="box-dock-row">
            <button id="displayControlDockBtn" class="box-dock-btn" type="button" hidden title="Anzeigekontrolle wieder öffnen">👁 Anzeige</button>
          </span>
        </summary>
        <div class="menu-fold-body">
          <div class="search-line">
            <input id="searchInput" placeholder="Feld-ID oder Name..." />
          </div>
          <div id="fieldList" class="field-list"></div>
        </div>
      </details>
    </div>
    <div class="map-wrap">
      <div id="editorMap"></div>
      <div id="dabsPopup" class="rkrk-dabs-popup" style="display:none">
        <button type="button" class="pop-close" id="dabsPopupClose" aria-label="Schliessen">×</button>
        <div id="dabsPopupBody"></div>
      </div>
      <div id="rkrkMapCorner">
      <div id="rkrkMapLabelSize" role="group" aria-label="Schriftgrösse der Kartenbeschriftung">
        <button id="mapLabelSmaller" type="button" title="Beschriftung kleiner">A−</button>
        <button id="mapLabelLarger" type="button" title="Beschriftung grösser">A+</button>
      </div>
      <div id="rkrkMapScaleBar" aria-label="Kartenmassstab" title="Kartenmassstab">
        <span class="rkrk-scale-ratio">Massstab</span>
        <div class="rkrk-scale-track" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <div class="rkrk-scale-caps"><span>0</span><span class="rkrk-scale-dist"></span></div>
      </div>
      </div>
      <button id="layerControlDockBtn" class="map-dock-btn map-layer-btn" type="button" hidden title="Layersteuerung"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 2 2 7l10 5 10-5-10-5zm-8 9.5 8 4 8-4V14l-8 4-8-4v-2.5zm0 5 8 4 8-4V19l-8 4-8-4v-2.5z"/></svg></button>
      <button id="fieldCharDockBtn" class="map-dock-btn map-field-btn" type="button" hidden title="Feldcharakteristik">📈</button>
      <button id="showDisplayMapBtn" class="map-display-btn" type="button" hidden title="Anzeigeoptionen einblenden">👁</button>
      <button id="findPlaceBtn" class="map-find-btn" title="Ort auf Karte suchen" type="button">🔎</button>
      <button id="mapHelpBtn" class="map-help-btn" data-rkrk-help title="Hilfe zur aktuellen Sicht (F1 / ?)" type="button" aria-expanded="false">?</button>
      <button id="mapSetupBtn" class="map-setup-btn" title="Setup: Boxen ein-/ausblenden" type="button">⚙</button>
      <div id="mapSetupPanel" class="map-setup-panel rkrk-sels rkrk-reg-pills">
        <div class="title">Boxen anzeigen</div>
        <label class="rkrk-sel rkrk-reg-pill" title="Haupt-Bedienmenue oben links"><input id="showBoxMapMenu" type="checkbox" checked data-rkrk-box-for="menu" /> Bedienmenue</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Anzeigekontrolle fuer Pfade, Abdeckung und Kontrollpunkte"><input id="showBoxDisplayControl" type="checkbox" checked data-rkrk-box-for="display" /> Anzeigekontrolle</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Kontext-Hilfe zur aktuellen Bearbeitung"><input id="showBoxContextHelp" type="checkbox" data-rkrk-box-for="context" /> Kontext-Hilfe</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Layersteuerung: Kartenansicht und SwissTopo-Overlays"><input id="showBoxSwissOverlay" type="checkbox" checked data-rkrk-box-for="overlays" /> Layersteuerung</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Flugvorgaben werden von rechts eingeblendet (Button Missionsparameter)" style="display:none;"><input id="showBoxMissionSettings" type="checkbox" data-rkrk-box-for="mission" /> Flugvorgaben</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Berechnete Feldcharakteristik"><input id="showBoxFieldCharacteristics" type="checkbox" checked data-rkrk-box-for="characteristics" /> Feldcharakteristik</label>
        <label id="showBoxWaypointDataLabel" class="rkrk-sel rkrk-reg-pill" hidden title="Wegpunkttabelle, nur mit DEBUG"><input id="showBoxWaypointData" type="checkbox" checked data-rkrk-box-for="waypoints" /> Wegpunktdaten</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Felddatenbereich unten"><input id="showBoxFormDrawer" type="checkbox" data-rkrk-box-for="form" /> Felddaten-Box</label>
      </div>
      <div id="displayControlBox" class="display-control-box" data-rkrk-box="display" data-rkrk-box-label="Anzeigekontrolle">
        <div class="title" title="Doppelklick klappt die Anzeigekontrolle ein">Anzeigekontrolle</div>
        <div class="group-title">Flugpfad</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill" title="Feldgrenze ein-/ausblenden"><input id="showFieldPolygon" type="checkbox" checked /> Feld anzeigen</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Der Flugpfad, an dem gearbeitet wird"><input id="showManualPath" type="checkbox" checked /> Flugpfad</label>
        <label class="rkrk-sel rkrk-reg-pill" hidden><input id="showAlgorithmPath" type="checkbox" /> Flugplan (neu)</label>
        </div>
        <div class="control-divider"></div>
        <div class="group-title">Abdeckung</div>
        <div class="group-note">Neigung −90: Streifen mittig auf der Route, Breite aus Flughöhe und Sichtfeld. Andere Neigung: schräger Footprint in Blickrichtung.</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill" title="Neigung −90: Streifen auf der Route. Andere Neigung: schräger Footprint in Gierrichtung."><input id="showCoverageCircles" type="checkbox" checked /> <span id="coverageSourceLabel">Suchabdeckung</span></label>
        <span id="coverageWindowChoice" hidden>
          <label class="rkrk-sel rkrk-reg-pill" title="DEBUG: volles Kamerafenster am Boden, die blaue Strecke W."><input type="radio" name="coverageWindowMode" value="W" /> Kamerasicht (W)</label>
          <label class="rkrk-sel rkrk-reg-pill" title="DEBUG: Spurabstand, dieselbe Formel wie die grüne Strecke S. Standard."><input type="radio" name="coverageWindowMode" value="S" checked /> Suchabdeckung (S)</label>
        </span>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showOverlapAreas" type="checkbox" /> Ueberlappung anzeigen</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showUncoveredAreas" type="checkbox" checked /> Nicht abgedeckte Flaechen</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Wenn aktiv, wird bei der Pfadberechnung die Korrektur nicht abgedeckter Flaechen per Wegpunkt-Erweiterung/-Einfügung forciert.">
          <input id="forceCoverageFix" type="checkbox" /> Offene Flaechen via Wegpunkte fuellen
        </label>
        </div>
        <div class="control-divider"></div>
        <div class="group-title">Kontrollpunkte</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="showCorrectionPoints" type="checkbox" /> Korrekturpunkte markieren</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showTurnCurves" type="checkbox" checked /> Wendekurven anzeigen</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showControlPoints" type="checkbox" /> Höhen auf der Route</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Roter Punkt, wo die reale Flughöhe über Grund von der Sollflughöhe um mehr als ±10 m abweicht."><input id="showAglLimit" type="checkbox" checked /> Abweichung von der Sollflughöhe</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showFieldControlPoints" type="checkbox" /> Höhen im Feld</label>
        </div>
        <div class="control-divider"></div>
        <div class="group-title">Analysemodus</div>
        <label title="Vereinfachte oder umfängliche Berechnung für die Flugpfad-Generierung">
          Berechnungsmodus
          <select id="computeMode" style="margin-top:4px;">
            <option value="simple" selected>einfach</option>
            <option value="full">umfänglich</option>
          </select>
        </label>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="useFieldReferenceAgl" type="checkbox" checked /> Feld-Referenzflughöhe verwenden</label>
        </div>
      </div>
      <div id="mapMenuBox" class="map-menu" data-rkrk-box="menu" data-rkrk-box-label="🧰 Bedienmenue">
      <div class="map-toolbar" hidden>
        <button id="newFieldQuickBtn" type="button" title="🆕 Neues Feld erstellen">🆕 Neues Feld</button>
        <button id="drawPolygonBtn" type="button" title="✏ Feldrand zeichnen oder bearbeiten">✏ Feldrand</button>
        <button id="pickStartBtn" type="button" title="📍 Startpunkt in der Karte setzen">📍 Startpunkt</button>
        <button id="openMissionBtn" type="button" title="🚁 Missionsparameter oeffnen">🚁 Missionsparameter</button>
        <button id="computePathBtn" type="button" title="🛫 Flugpfad aus Feld berechnen">🛫 Feldpfad rechnen</button>
        <button id="editPathBtn" type="button" class="secondary" title="🛠 Flugpfad anpassen: Punkte verschieben, + hinzufuegen, Doppelklick loeschen">🛠 Feldpfad anpassen</button>
        <button id="openPointParamsBtn" type="button" class="secondary" title="📌 Punktparameter (Wegpunkt-Typ, Wendedistanz) oeffnen">📌 Punktparameter</button>
        <button id="undoBtn" type="button" class="secondary" title="↶ Letzten Bearbeitungsschritt rueckgaengig">↶ Undo</button>
        <button id="clearPolygonBtn" type="button" class="secondary" title="🗑 Feldrand entfernen">🗑 Loeschen</button>
        <button id="exportUavJsonBtn" type="button" class="secondary" title="📄 UAV-kompatibles JSON exportieren">📄 UAV JSON</button>
        <button id="exportDjiKmzBtn" type="button" class="secondary" title="📦 DJI-kompatibles KMZ exportieren">📦 DJI KMZ</button>
      </div>
      </div>
      <div id="mapQuickHelp" class="map-quick-help" data-rkrk-box="context" data-rkrk-box-label="❓ Kontext-Hilfe" data-rkrk-box-default="off" style="display:none">
        <div class="map-quick-help-head">
          <strong>Kontext-Hilfe</strong>
          <button id="hideContextHelpBtn" class="map-quick-help-close" type="button" title="Kontext-Hilfe ausblenden">Ausblenden</button>
        </div>
        <div id="mapQuickHelpBody">
          Standard: Feld laden oder zeichnen, danach bearbeiten und exportieren.
        </div>
      </div>
      <details id="swissOverlayBox" class="swiss-overlay-box" open data-rkrk-box="overlays" data-rkrk-box-label="🗺 Layersteuerung">
        <summary>SwissTopo-Overlays</summary>
        <div id="basemapTopBar" class="basemap-toggle" title="Kartenansicht">
          <button id="basemapMapBtn" type="button" class="basemap-seg is-on" data-basemap="swisstopo" title="SwissTopo Farbe">Karte</button>
          <button id="basemapSatelliteBtn" type="button" class="basemap-seg" data-basemap="swissimage" title="Swissimage">Satellit</button>
          <button id="basemapGrayBtn" type="button" class="basemap-seg" data-basemap="swisstopo-grau" title="SwissTopo schwarz-weiss">S/W</button>
          <button id="basemapOsmBtn" type="button" class="basemap-seg" data-basemap="osm" title="OpenStreetMap">OSM</button>
        </div>
        <select id="baseMapMode" class="basemap-select-sr" title="Kartenansicht" tabindex="-1" aria-hidden="true">
          <option value="swisstopo" selected>Swisstopo Farbe</option>
          <option value="swisstopo-grau">SwissTopo schwarz-weiss</option>
          <option value="swissimage">Swissimage</option>
          <option value="osm">OpenStreetMap</option>
        </select>
        <div class="swiss-plan-layers">
          <label class="rkrk-sel rkrk-reg-pill" title="Wegpunktplan auf der Karte"><input id="layerShowWaypointPlan" type="checkbox" checked /> Wegpunktplan</label>
          <label class="rkrk-sel rkrk-reg-pill" title="Feldumrandung auf der Karte"><input id="layerShowField" type="checkbox" checked /> Feld</label>
        </div>
        <div class="swiss-overlay-list">
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Strom-Infrastruktur"><input id="layerElectric" type="checkbox" /> Elektrische Anlagen</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerElectricOpacity" type="range" min="0" max="100" step="5" value="100" /><span id="layerElectricOpacityLabel">100%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Kantons- und Gemeindegrenzen"><input id="layerBoundaries" type="checkbox" /> Kantons, Gemeindegrenzen</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerBoundariesOpacity" type="range" min="0" max="100" step="5" value="100" /><span id="layerBoundariesOpacityLabel">100%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Jagdbanngebiete AuLaV (Swisstopo/BAFU)"><input id="layerJagdbann" type="checkbox" /> Jagdbanngebiete AuLaV</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerJagdbannOpacity" type="range" min="0" max="100" step="5" value="50" /><span id="layerJagdbannOpacityLabel">50%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Drohnenzonen / RPAS"><input id="layerRpas" type="checkbox" /> RPAS Karte</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerRpasOpacity" type="range" min="0" max="100" step="5" value="50" /><span id="layerRpasOpacityLabel">50%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="UAS Aktivitätszonen"><input id="layerUas" type="checkbox" /> UAS Aktivitätszonen</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerUasOpacity" type="range" min="0" max="100" step="5" value="50" /><span id="layerUasOpacityLabel">50%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Wohngebiete nach AuLaV"><input id="layerAulav" type="checkbox" /> Wohngebiete nach AuLaV</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerAulavOpacity" type="range" min="0" max="100" step="5" value="50" /><span id="layerAulavOpacityLabel">50%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="swissALTI3D multidirektionales Relief"><input id="layerAlti3d" type="checkbox" /> swissALTI3D Relief</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerAlti3dOpacity" type="range" min="0" max="100" step="5" value="70" /><span id="layerAlti3dOpacityLabel">70%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="swissSURFACE3D multidirektionales Relief (standard aus)"><input id="layerSurface3d" type="checkbox" /> swissSURFACE3D Relief</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerSurface3dOpacity" type="range" min="0" max="100" step="5" value="70" /><span id="layerSurface3dOpacityLabel">70%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="DABS bis 200 m AGL"><input id="layerDabs" type="checkbox" /> DABS bis 200 m AGL</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerDabsOpacity" type="range" min="0" max="100" step="5" value="75" /><span id="layerDabsOpacityLabel">75%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Luftfahrthindernisse"><input id="layerObstacles" type="checkbox" /> Luftfahrthindernisse</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerObstaclesOpacity" type="range" min="0" max="100" step="5" value="100" /><span id="layerObstaclesOpacityLabel">100%</span></span>
          </div>
          <div class="swiss-overlay-item overlay-subopt" id="obstacleHeightsRow">
            <label class="rkrk-sel rkrk-reg-pill" title="Höhen der Hindernisse (zu Luftfahrthindernisse)"><input id="obstacleHeights" type="checkbox" checked disabled /> Höhenangaben</label>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Amtliche Vermessung / Grundbuch"><input id="layerGrundbuch" type="checkbox" /> Grundbuchinformationen</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerGrundbuchOpacity" type="range" min="0" max="100" step="5" value="100" /><span id="layerGrundbuchOpacityLabel">100%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Katasterdarstellung"><input id="layerCadastral" type="checkbox" /> CadastralWebMap</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerCadastralOpacity" type="range" min="0" max="100" step="5" value="100" /><span id="layerCadastralOpacityLabel">100%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Naturschutz- und Schutzgebiete"><input id="layerNature" type="checkbox" /> Naturschutzgebiete</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerNatureOpacity" type="range" min="0" max="100" step="5" value="100" /><span id="layerNatureOpacityLabel">100%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Beschriftungen von Strassen und Orten"><input id="layerRoadNames" type="checkbox" /> Strassennamen</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerRoadNamesOpacity" type="range" min="0" max="100" step="5" value="100" /><span id="layerRoadNamesOpacityLabel">100%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="Seilbahnen / Winterbahnen"><input id="layerCableways" type="checkbox" /> Seilbahnen</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerCablewaysOpacity" type="range" min="0" max="100" step="5" value="100" /><span id="layerCablewaysOpacityLabel">100%</span></span>
          </div>
          <div class="swiss-overlay-item">
            <label class="rkrk-sel rkrk-reg-pill" title="SwissTLM3D Wanderwege (Swisstopo)"><input id="layerWanderwege" type="checkbox" /> Wanderwege</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerWanderwegeOpacity" type="range" min="0" max="100" step="5" value="100" /><span id="layerWanderwegeOpacityLabel">100%</span></span>
          </div>
          <p class="small" id="overlayStatus"></p>
        </div>
      </details>
      <aside id="editorRightDrawer" class="editor-right-drawer" aria-hidden="true">
        <div class="editor-right-drawer-panel">
          <header class="editor-right-drawer-head">
            <div>
              <h2 id="editorRightDrawerTitle">Missions Einstellungen</h2>
              <div id="editorRightDrawerSubtitle" class="editor-right-drawer-sub"></div>
            </div>
            <button type="button" id="editorRightDrawerCloseBtn" class="editor-right-drawer-close" title="Schliessen">×</button>
          </header>
          <div id="waypointNavTabs" class="editor-right-drawer-tabs" hidden>
            <button type="button" id="wpTabPrev">&lt; Vorheriger</button>
            <button type="button" id="wpTabWaypoint" class="active">Wegpunkt</button>
            <button type="button" id="wpTabAll">Alle Wegpunkte</button>
            <button type="button" id="wpTabNext">Nächster &gt;</button>
          </div>
          <div id="missionDrawerPanel" class="editor-right-drawer-body">
            <div id="missionSettingsBox" class="mission-settings-grid">
              <div class="full">
                <label title="Wenn deaktiviert, werden benutzerdefinierte KMZ-Parameter ignoriert und DJI-Referenz-Defaults verwendet.">
                  <input id="msKmzParamsActive" type="checkbox" checked style="width:auto;" />
                  KMZ-Parameter aktiv
                </label>
              </div>
              <div>
                <label>DrohnenTyp</label>
                <select id="msDroneType">
                  <option value="Mavic 3T" selected>Mavic 3T</option>
                  <option value="Matrice M4TD">Matrice M4TD</option>
                  <option value="Matrice M4T">Matrice M4T</option>
                  <option value="Mavic M3TA">Mavic M3TA</option>
                </select>
                <p id="droneCatalogNote" class="plan-note">Feldkatalog: Mavic 3T, Matrice 4TD und Mavic 3T Debug. Matrice 4T, Matrice 3TD und Mavic 3TA haben keinen Feldkatalog. Mavic II und Matrice 30T sind aus der Auswahl entfernt. Ein neuer Katalog wird nur in der Website-Konfiguration angelegt.</p>
              </div>
              <div class="plan-ms-basic">
                <label>Flughöhe (m)</label>
                <input id="msAltitude" type="number" step="1" value="60" />
              </div>
              <div class="plan-ms-basic">
                <label>Flugrichtung (°, -1=auto)</label>
                <div style="display:grid; grid-template-columns: 1fr 74px; gap: 6px; align-items:center;">
                  <input id="msFlightDirectionRange" type="range" min="-1" max="259" step="1" value="-1" title="Flugrichtung per Schieber einstellen (-1 = auto)" />
                  <input id="msFlightDirection" type="number" step="1" min="-1" max="259" value="-1" title="Flugrichtung numerisch eingeben (-1 = auto)" />
                </div>
              </div>
              <div class="plan-ms-basic">
                <label>Geschwindigkeit (m/s)</label>
                <input id="msSpeed" type="number" step="0.1" value="4.5" />
              </div>
              <div class="plan-ms-basic">
                <label>Überlappung (%)</label>
                <div style="display:grid; grid-template-columns: 1fr 74px; gap: 6px; align-items:center;">
                  <input id="msOverlapPctRange" type="range" min="0" max="95" step="5" value="15" title="Überlappung per Schieber einstellen" />
                  <input id="msOverlapPct" type="number" step="5" min="0" max="95" value="15" title="Überlappung numerisch eingeben" />
                </div>
              </div>
              <div>
                <label>Max. Randfaktor (%)</label>
                <div style="display:grid; grid-template-columns: 1fr 74px; gap: 6px; align-items:center;">
                  <input id="msEdgeFactorPctRange" type="range" min="0" max="100" step="5" value="40" title="Max. Randfaktor per Schieber einstellen" />
                  <input id="msEdgeFactorPct" type="number" step="5" min="0" max="100" value="40" title="Max. Randfaktor numerisch eingeben" />
                </div>
              </div>
              <div>
                <label>FOV (°)</label>
                <span id="msFovCombined" class="fov-combined">HFOV 49.0° | VFOV 40.0° | DFOV 61.0°</span>
                <span id="msHfov" class="fov-info" style="display:none;">49.0°</span>
                <span id="msVfov" class="fov-info" style="display:none;">40.0°</span>
                <span id="msDfov" class="fov-info" style="display:none;">61.0°</span>
              </div>
              <div>
                <label>Endaktion</label>
                <select id="msEndAction">
                  <option value="Aufgabe beenden" selected>Aufgabe beenden</option>
                  <option value="Rückkehr zum Start (RTH)">Rückkehr zum Start (RTH)</option>
                  <option value="Landen">Landen</option>
                  <option value="Zum ersten Wegpunkt">Zum ersten Wegpunkt</option>
                </select>
              </div>
              <div>
                <label>Gierrichtung des Fluggeraetes</label>
                  <select id="msHeadingMode">
                  <option value="Manuell">Manuell</option>
                  <option value="Richtung zum naechsten Wegpunkt" selected>Richtung zum naechsten Wegpunkt</option>
                  <option value="Schenkachse sperren">Schenkachse sperren</option>
                  <option value="Fokus POI">Fokus POI</option>
                  <option value="Definiert">Definiert</option>
                </select>
                <div id="msPoiBox" hidden>
                  <button id="msPoiFromPark" type="button">POI am Parkplatz</button>
                  <button id="msPoiOnMap" type="button">POI auf der Karte setzen</button>
                  <div id="msPoiNote" class="wp-hint"></div>
                </div>
              </div>
              <div>
                <label>Wegpunkt Typ</label>
                <select id="msWaypointType">
                  <option value="Koordinierte Drehung, Ueberspringt den Wegpunkt" selected>Koordinierte Drehung, Ueberspringt den Wegpunkt</option>
                  <option value="Gerade Flugroute. Fluggeraet stoppt">Gerade Flugroute. Fluggeraet stoppt</option>
                  <option value="Gekruemmte Route. Fluggeraet setzt fort">Gekruemmte Route. Fluggeraet setzt fort</option>
                  <option value="Gekruemmte Route. Fluggeraet stoppt">Gekruemmte Route. Fluggeraet stoppt</option>
                  <option value="Wendet vor dem Wegpunkt. Fliegt durch">Wendet vor dem Wegpunkt. Fliegt durch</option>
                </select>
              </div>
              <div id="msTurnDistanceWrap" hidden>
                <label>Standard-Wendedistanz (m)</label>
                <input id="msTurnDistance" type="number" step="0.1" value="5" />
              </div>
              <div>
                <label>Sichere Startflughöhe</label>
                <input id="msSafeStartAltitude" type="number" step="0.1" value="20" />
              </div>
              <div>
                <label class="plan-direct-line"><input id="msDirectToStart" type="checkbox" style="width:auto;" /> Direkt zur Startposition fliegen</label>
              </div>
              <div id="msGimbalWrap">
                <label>Gimbalsteuerung</label>
                <select id="msGimbalMode">
                  <option value="Manuell" selected>Manuell</option>
                  <option value="Jeden Wegpunkt festlegen">Jeden Wegpunkt festlegen</option>
                </select>
              </div>
              <div class="full">
                <label>Flughöhenmodus</label>
                <select id="msAltitudeMode">
                  <option value="ALT (Relativ zum Startpunkt)" selected>ALT (Relativ zum Startpunkt)</option>
                </select>
              </div>
            </div>
          </div>
          <div id="waypointDrawerPanel" class="editor-right-drawer-body" hidden>
            <div id="wpAllNote" class="wp-hint" hidden style="margin:0 12px 8px;">Änderungen gelten für alle Wegpunkte gleichzeitig.</div>
            <div id="wpSinglePane">
              <div class="waypoint-settings-grid">
                <div id="wpLatWrap">
                  <label class="wp-req">Latitude</label>
                  <input id="wpLat" type="text" />
                </div>
                <div id="wpLngWrap">
                  <label class="wp-req">Longitude</label>
                  <input id="wpLng" type="text" />
                </div>
                <div class="full">
                  <label class="wp-check"><input id="wpUseMissionHeight" type="checkbox" checked /> Verwende Missions-Höhe</label>
                </div>
                <div class="full plan-wp-height plan-wp-check">
                  <label class="wp-req">Flughöhe</label>
                  <div class="wp-alt-step">
                    <button id="wpAltMinus" type="button" title="5 m weniger">−</button>
                    <input id="wpAltitude" type="number" step="1" min="1" max="500" value="60" inputmode="numeric" />
                    <button id="wpAltPlus" type="button" title="5 m mehr">+</button>
                    <span class="wp-unit">m</span>
                  </div>
                  <input id="wpAltitudeRange" type="range" min="1" max="200" step="1" value="60" />
                  <div id="wpStartRelHint" class="wp-hint">(– m)</div>
                  <div id="wpMissionAltHint" class="wp-hint">Missions-Höhe: 60m</div>
                  <div id="wpAglHint" class="wp-hint">Flughöhe zum Boden: -</div>
                </div>
                <div class="full">
                  <label class="wp-check"><input id="wpUseMissionSpeed" type="checkbox" checked /> Verwende Missions-Geschwindigkeit</label>
                </div>
                <div class="full plan-wp-check">
                  <label class="wp-req">Geschwindigkeit</label>
                  <div class="wp-slider-row">
                    <input id="wpSpeed" type="number" step="0.1" min="0.1" max="15" value="5" />
                    <input id="wpSpeedRange" type="range" min="0.1" max="15" step="0.1" value="5" />
                    <span class="wp-unit">m/s</span>
                  </div>
                  <div id="wpMissionSpeedHint" class="wp-hint">Missions-Geschwindigkeit: 5m/s</div>
                </div>
                <div class="full">
                  <label class="wp-check"><input id="wpUseMissionWaypointType" type="checkbox" checked /> Route folgen</label>
                </div>
                <div class="full plan-wp-check">
                  <label>Wegpunkt-Typ</label>
                  <select id="wpWaypointType">
                    <option value="Koordinierte Drehung, Ueberspringt den Wegpunkt" selected>Koordinierte Drehung, Ueberspringt den Wegpunkt</option>
                    <option value="Gerade Flugroute. Fluggeraet stoppt">Gerade Flugroute. Fluggeraet stoppt</option>
                    <option value="Gekruemmte Route. Fluggeraet setzt fort">Gekruemmte Route. Fluggeraet setzt fort</option>
                    <option value="Gekruemmte Route. Fluggeraet stoppt">Gekruemmte Route. Fluggeraet stoppt</option>
                    <option value="Wendet vor dem Wegpunkt. Fliegt durch">Wendet vor dem Wegpunkt. Fliegt durch</option>
                  </select>
                </div>
                <div class="full" id="wpTurnDistanceWrap" hidden>
                  <label>Wendedistanz</label>
                  <div class="wp-slider-row">
                    <input id="wpTurnDistance" type="number" step="0.1" min="0" max="40" value="5" />
                    <input id="wpTurnDistanceRange" type="range" min="0" max="40" step="0.1" value="5" />
                    <span class="wp-unit">m</span>
                  </div>
                  <div id="wpMissionTurnHint" class="wp-hint">Standard-Wendedistanz: 5.00m</div>
                </div>
                <div class="full">
                  <label class="wp-check"><input id="wpUseMissionYaw" type="checkbox" checked /> Route folgen (Gierrichtung)</label>
                </div>
                <div class="plan-wp-check">
                  <label>Gierrichtung des Fluggeräts</label>
                  <select id="wpHeadingMode">
                    <option value="Manuell" selected>Manuell</option>
                    <option value="Richtung zum naechsten Wegpunkt">Richtung zum naechsten Wegpunkt</option>
                    <option value="Schenkachse sperren">Schenkachse sperren</option>
                    <option value="Fokus POI">Fokus POI</option>
                    <option value="Definiert">Definiert</option>
                  </select>
                  <div id="wpPoiBox" hidden>
                    <button id="wpPoiFromPark" type="button">POI am Parkplatz</button>
                    <button id="wpPoiOnMap" type="button">POI auf der Karte setzen</button>
                    <div id="wpPoiNote" class="wp-hint"></div>
                  </div>
                </div>
                <div class="full" id="wpPathFollowWrap" hidden>
                  <label class="wp-check"><input id="wpUseMissionPathMode" type="checkbox" checked /> Pfadmodus</label>
                </div>
                <div>
                  <label>Ausrichtung des Fluggeräts</label>
                  <select id="wpAlignment">
                    <option value="Automatisch" selected>Automatisch</option>
                    <option value="Uhrzeigersinn">Uhrzeigersinn</option>
                    <option value="Gegen den Uhrzeigersinn">Gegen den Uhrzeigersinn</option>
                  </select>
                </div>
                <div class="full" id="wpDirFollowWrap" hidden>
                  <label class="wp-check"><input id="wpUseMissionDirectionMode" type="checkbox" checked /> Route folgen (Richtungsmodus)</label>
                </div>
                <div id="wpDirectionWrap" class="full plan-wp-check">
                  <label class="wp-req">Richtungs-Winkel</label>
                  <div class="wp-slider-row">
                    <input id="wpDirection" type="number" step="1" min="-180" max="180" value="0" />
                    <input id="wpDirectionRange" type="range" min="-180" max="180" step="1" value="0" />
                    <span class="wp-unit">°</span>
                  </div>
                  <div class="wp-hint">Richtungswinkel bei Definiert, Schritte 1°. Schenkachse sperren zeigt ein rotes X.</div>
                </div>
                <div id="wpPitchWrap" class="full plan-wp-check" hidden>
                  <label class="wp-check"><input id="msGimbalAngleActive" type="checkbox" /> Neigungswinkel setzen</label>
                  <label class="wp-req">Neigungswinkel</label>
                  <div class="wp-slider-row">
                    <input id="wpPitch" type="number" step="1" min="-90" max="30" value="-90" />
                    <input id="wpPitchRange" type="range" min="-90" max="30" step="1" value="-90" />
                    <span class="wp-unit">°</span>
                  </div>
                  <div class="wp-hint">Frei, wenn «Neigungswinkel setzen» an ist. Sonst bleibt −90 und die KMZ schreibt keinen Winkel.</div>
                </div>
              </div>
            </div>
            <div id="wpAllPane" hidden>
              <div id="wpAllList" class="wp-all-list"></div>
              <div style="margin-top:10px;">
                <button type="button" id="wpApplyAllBtn">Diese Werte auf alle anwenden</button>
              </div>
            </div>
          </div>
          <div id="waypointDrawerFoot" class="editor-right-drawer-foot" hidden>
            <button type="button" id="wpSaveBtn">Punkt speichern</button>
            <button type="button" id="wpCancelBtn" class="secondary">Abbrechen</button>
            <button type="button" id="wpDeleteBtn" class="danger">Löschen</button>
          </div>
        </div>
      </aside>
      <details id="fieldCharacteristicsBox" class="field-characteristics-box" open data-rkrk-box="characteristics" data-rkrk-box-label="📈 Feldcharakteristik">
        <summary title="Doppelklick klappt die Feldcharakteristik zum Icon neben der Suche ein">Feldcharakteristik</summary>
        <div class="field-characteristics-grid">
          <span>Quelle</span><span id="fcSource" class="value">-</span>
          <span>Länge Flugpfad</span><span id="fcPathLength" class="value">-</span>
          <span>Höhendifferenz hoch</span><span id="fcClimbUp" class="value">-</span>
          <span>Höhendifferenz runter</span><span id="fcClimbDown" class="value">-</span>
          <span>Flugzeit</span><span id="fcFlightTime" class="value">-</span>
        </div>
      </details>
      <details id="wpDebugTableBox" hidden open data-rkrk-box="waypoints" data-rkrk-box-label="Wegpunktdaten">
        <summary>Wegpunktdaten</summary>
        <div class="wp-debug-scroll">
          <table id="wpDebugTable">
            <colgroup>
              <col class="wp-col-nr">
              <col class="wp-col-terrain">
              <col class="wp-col-delta">
              <col class="wp-col-prog">
              <col class="wp-col-rel">
              <col class="wp-col-yaw">
              <col class="wp-col-gimbal">
            </colgroup>
            <thead>
              <tr>
                <th>WP<br>Nr</th>
                <th>Höhe WP<br>aus SwissTopo</th>
                <th>Delta zu<br>Starthöhe</th>
                <th>Programmierte<br>Flughöhe</th>
                <th>Flughöhe Relativ<br>zum Startpunkt</th>
                <th>Gier<br>richtung</th>
                <th>Gimbal<br>neigung</th>
              </tr>
            </thead>
            <tbody id="wpDebugTableBody"></tbody>
          </table>
        </div>
      </details>
      <details id="formDrawerBox" class="editor-form-drawer" data-rkrk-box="form" data-rkrk-box-label="📝 Felddaten-Box">
        <summary>Felddaten bearbeiten (Interne XML-ID, Koordinaten, Speichern)</summary>
        <div class="editor-form-content">
          <div class="row">
            <label>Interne XML-ID (nur Anzeige)</label>
            <input id="id" readonly />
          </div>
          <div class="row rkrk-kat-only">
            <label>Nummer (Number)</label>
            <input id="number" readonly />
          </div>
          <div class="row rkrk-kat-only">
            <label>FeldID *</label>
            <input id="feld_id" />
            <p id="feldIdPlace" class="plan-note plan-feld-place" hidden></p>
          </div>
          <div class="row">
            <label>Feldname *</label>
            <input id="name" />
          </div>
          <div class="row">
            <label>Flurname</label>
            <input id="flurname" />
          </div>
          <div class="row">
            <label>GeoID</label>
            <input id="grundstueck_id" inputmode="numeric" />
          </div>
          <div class="row">
            <label>Jagdgebiet</label>
            <select id="jg"></select>
          </div>
          <div class="row">
            <label>Gemeinde</label>
            <select id="gemeinde"></select>
          </div>
          <div class="row">
            <label>Betrieb</label>
            <input id="betrieb" />
          </div>
          <div class="row">
            <label>Freigabe</label>
            <select id="freigabe"></select>
          </div>
          <div class="row">
            <label>Warnung</label>
            <input id="warnung" list="warnungOptions" placeholder="z. B. CTR NSF" />
            <datalist id="warnungOptions"></datalist>
          </div>
          <div class="row">
            <label>Jäger</label>
            <select id="jaeger"></select>
          </div>
          <div class="row">
            <label>StdFlughoehe</label>
            <select id="std_flughoehe"></select>
          </div>
          <div class="row">
            <label>Flugzeit (min)</label>
            <input id="flugzeit" />
          </div>
          <div class="row">
            <label>Distanz (m)</label>
            <input id="distanz" />
          </div>
          <div class="row">
            <label>Wegpunkte (Anzahl)</label>
            <input id="wegpunkte" />
          </div>
          <div class="row">
            <label>Fläche</label>
            <input id="flaeche" />
          </div>
          <div class="row">
            <label>Flüge</label>
            <input id="flug" />
          </div>
          <div class="row">
            <label>Rehkitzfunde</label>
            <input id="funde" />
          </div>
          <div class="row">
            <label>Bemerkung</label>
            <textarea id="bemerkung"></textarea>
          </div>
          <div class="row">
            <label>Bauer</label>
            <textarea id="bauer"></textarea>
          </div>
          <div class="row">
            <label>Notizen</label>
            <textarea id="notiz"></textarea>
          </div>
          <div class="row">
            <label>Export</label>
            <label class="plan-checkline"><input id="export_yn" type="checkbox" /> im Export</label>
          </div>
          <div class="row">
            <label>Select</label>
            <label class="plan-checkline"><input id="select_yn" type="checkbox" /> ausgewählt</label>
          </div>
          <div class="row">
            <label>SuchfeldBild</label>
            <input id="suchfeld_bild" />
            <div id="suchfeld_bild_view" class="plan-pics"></div>
          </div>
          <div class="row">
            <label>FlugplanBild</label>
            <input id="flugplan_bild" />
            <div id="flugplan_bild_view" class="plan-pics"></div>
          </div>
          <div class="row">
            <label>JasonFile</label>
            <input id="jason_file" />
          </div>
          <div class="row">
            <label>KMLFile</label>
            <input id="kml_file" />
          </div>
          <div class="row">
            <label>KMZFile</label>
            <input id="kmz_file" />
          </div>
          <div class="row">
            <label>Erstellt</label>
            <input id="erstellt" readonly />
          </div>
          <div class="row">
            <label>Änderung</label>
            <input id="aenderung" readonly />
          </div>
          <div class="row">
            <label>Update</label>
            <input id="katalog_update" readonly />
          </div>
          <div class="row">
            <label>Startplatz (lon, lat)</label>
            <textarea id="startplatz" placeholder="8.50, 47.26"></textarea>
          </div>
          <div class="row">
            <label>Parkplatz (lon, lat)</label>
            <textarea id="parkplatz" placeholder="8.50, 47.26"></textarea>
          </div>
          <div class="row">
            <label>Zentrum (lon, lat)</label>
            <textarea id="zentrum" placeholder="8.50, 47.26"></textarea>
          </div>
          <div class="row">
            <label>Flaechendaten (lon, lat ...)</label>
            <textarea id="flaechendaten" placeholder="8.50, 47.26  8.51, 47.26  ..."></textarea>
          </div>
          <div class="row">
            <label>Flugweg (lon, lat, alt ...)</label>
            <textarea id="flugweg" placeholder="8.50, 47.26, 50  8.51, 47.26, 50 ..."></textarea>
          </div>
          <div class="actions">
            <button id="saveBtn" type="button" title="Aktuell geladenes Feld mit allen Aenderungen speichern">💾 Aktuelles Feld speichern</button>
            <button id="createBtn" type="button" title="Felddaten prüfen und als neues Feld speichern">☑ Felddaten prüfen</button>
            <button id="newBtn" class="secondary" type="button" title="Formular leeren fuer neue Eingabe">🗑 Leeres Formular</button>
          </div>
          <div id="status" class="status"></div>
        </div>
      </details>
    </div>
  </div>
  <div id="createFieldModalBackdrop" class="create-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="createFieldModalTitle">
    <div class="create-modal">
      <h2 id="createFieldModalTitle">Felddaten prüfen</h2>
      <div class="small-note">Alle Katalogfelder. Vorgaben kommen aus der Umrandung und können geändert werden. Wo eine Liste besteht, ist sie vorausgewählt.</div>
      <div id="createPreviewNote" class="create-preview-note" hidden></div>
      <div class="create-tabs rkrk-sels rkrk-reg-pills" role="tablist">
        <button type="button" class="rkrk-sel rkrk-reg-pill is-active" data-create-tab="grund" role="tab">Grunddaten</button>
        <button type="button" class="rkrk-sel rkrk-reg-pill" data-create-tab="kontakte" role="tab">Kontakte</button>
        <button type="button" class="rkrk-sel rkrk-reg-pill" data-create-tab="bilder" role="tab">Bilder</button>
        <button type="button" class="rkrk-sel rkrk-reg-pill" data-create-tab="dateien" role="tab">Dateien</button>
        <button type="button" class="rkrk-sel rkrk-reg-pill" data-create-tab="kml" role="tab">KML-Daten</button>
      </div>
      <div class="create-tab-panel" data-create-panel="grund">
      <div class="create-grid">
        <div class="rkrk-kat-only">
          <label>Katalognummer</label>
          <input id="create_feld_id" title="Freie Nummer. Eine schon vergebene Nummer muss beim Speichern bestätigt werden." />
          <p id="createFeldIdPlace" class="plan-note plan-feld-place" hidden></p>
        </div>
        <div>
          <label>Feldname *</label>
          <input id="create_name" />
        </div>
        <div>
          <label>Jagdgebiet</label>
          <select id="create_jg"></select>
        </div>
        <div>
          <label>Gemeinde</label>
          <select id="create_gemeinde"></select>
        </div>
        <div class="rkrk-kat-only">
          <label>Laufnummer</label>
          <input id="create_number" readonly />
        </div>
        <div>
          <label>Flurname</label>
          <input id="create_flurname" />
        </div>
        <div>
          <label>GeoID</label>
          <input id="create_grundstueck_id" inputmode="numeric" />
        </div>
        <div>
          <label>Betrieb</label>
          <input id="create_betrieb" />
        </div>
        <div>
          <label>Freigabe</label>
          <select id="create_freigabe"></select>
        </div>
        <div>
          <label>StdFlughoehe</label>
          <select id="create_std_flughoehe"></select>
        </div>
        <div>
          <label>Fläche</label>
          <input id="create_flaeche" />
        </div>
        <div>
          <label>Warnung</label>
          <input id="create_warnung" list="warnungOptions" />
        </div>
      </div>
      </div>
      <div class="create-tab-panel" data-create-panel="kontakte" hidden>
      <div class="create-grid">
        <div>
          <label>Jäger</label>
          <select id="create_jaeger"></select>
        </div>
        <div>
          <label>Flüge</label>
          <input id="create_flug" />
        </div>
        <div>
          <label>Rehkitzfunde</label>
          <input id="create_funde" />
        </div>
        <div class="full">
          <label>Bemerkung</label>
          <textarea id="create_bemerkung"></textarea>
        </div>
        <div class="full">
          <label>Bauer</label>
          <textarea id="create_bauer"></textarea>
        </div>
        <div class="full">
          <label>Notizen</label>
          <textarea id="create_notiz"></textarea>
        </div>
        <div>
          <label>Export</label>
          <label class="plan-checkline"><input id="create_export_yn" type="checkbox" /> im Export</label>
        </div>
        <div>
          <label>Select</label>
          <label class="plan-checkline"><input id="create_select_yn" type="checkbox" /> ausgewählt</label>
        </div>
      </div>
      </div>
      <div class="create-tab-panel" data-create-panel="bilder" hidden>
      <div class="create-actions" style="justify-content:flex-start;margin:0 0 12px;">
        <button id="createPicRenew" type="button" title="Suchfeld- und Flugplanbild des geöffneten Feldes neu erzeugen">Bilder erneuern</button>
      </div>
      <input id="create_suchfeld_bild" type="hidden" />
      <input id="create_flugplan_bild" type="hidden" />
      <div id="createPictureRows"></div>
      </div>
      <div class="create-tab-panel" data-create-panel="dateien" hidden>
      <div class="create-grid">
        <div class="full">
          <label>JasonFile</label>
          <input id="create_jason_file" />
        </div>
        <div>
          <label>KMLFile</label>
          <input id="create_kml_file" />
        </div>
        <div>
          <label>KMZFile</label>
          <input id="create_kmz_file" />
        </div>
      </div>
      </div>
      <div class="create-tab-panel" data-create-panel="kml" hidden>
      <div class="create-grid">
        <div>
          <label>Erstellt</label>
          <input id="create_erstellt" readonly />
        </div>
        <div>
          <label>Änderung</label>
          <input id="create_aenderung" readonly />
        </div>
        <div class="full">
          <label>Startplatz (lon, lat) *</label>
          <textarea id="create_startplatz" placeholder="8.50, 47.26"></textarea>
        </div>
        <div class="full">
          <label>Parkplatz (lon, lat)</label>
          <textarea id="create_parkplatz" placeholder="8.50, 47.26"></textarea>
        </div>
        <div class="full">
          <label>Zentrum (lon, lat)</label>
          <textarea id="create_zentrum" placeholder="8.50, 47.26"></textarea>
        </div>
        <div class="full">
          <label>Flaechendaten (lon, lat ...) *</label>
          <textarea id="create_flaechendaten" placeholder="8.50, 47.26  8.51, 47.26  ..."></textarea>
        </div>
        <div class="full">
          <label>Flugweg (lon, lat, alt ...) *</label>
          <textarea id="create_flugweg" placeholder="8.50, 47.26, 50  8.51, 47.26, 50 ..."></textarea>
        </div>
      </div>
      </div>
      <div class="create-actions">
        <button id="createModalCancelBtn" class="secondary" type="button" title="Prüfung schliessen, ohne zu speichern">Abbrechen</button>
        <button id="createModalSaveBtn" type="button" title="Feld speichern, wenn es neu ist oder Änderungen hat" disabled>💾 Feld speichern</button>
      </div>
    </div>
  </div>

  <?php include dirname(__DIR__) . '/scripts/rkrk_screenshot.php'; ?>
  <?php include dirname(__DIR__) . '/scripts/rkrk_catalog_shot.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/ol@7.5.2/dist/ol.js"></script>
  <script src="rkrk_swiss_overlays.js?v=20260917dabs1"></script>
  <script src="rkrk_help_catalog.js?v=20261018abdeckung2"></script>
  <script src="rkrk_help.js?v=20261017hilfe7"></script>
  <script src="rkrk_layout.js"></script>
  <script src="rkrk_field_shared.js?v=20261018ws"></script>
  <script>window.RKRK_KAT = <?php echo $rkrkKat ? 'true' : 'false'; ?>; window.RKRK_ADMIN = <?php echo $rkrkAdmin ? 'true' : 'false'; ?>;</script>
  <script>
    const els = {
      backBtn: document.getElementById('backBtn'),
      searchInput: document.getElementById('searchInput'),
      fieldList: document.getElementById('fieldList'),
      quickSlotGridEditor: document.getElementById('quickSlotGridEditor'),
      id: document.getElementById('id'),
      number: document.getElementById('number'),
      feld_id: document.getElementById('feld_id'),
      name: document.getElementById('name'),
      jg: document.getElementById('jg'),
      gemeinde: document.getElementById('gemeinde'),
      std_flughoehe: document.getElementById('std_flughoehe'),
      flugzeit: document.getElementById('flugzeit'),
      distanz: document.getElementById('distanz'),
      wegpunkte: document.getElementById('wegpunkte'),
      startplatz: document.getElementById('startplatz'),
      flaechendaten: document.getElementById('flaechendaten'),
      flugweg: document.getElementById('flugweg'),
      msDroneType: document.getElementById('msDroneType'),
      msKmzParamsActive: document.getElementById('msKmzParamsActive'),
      msAltitude: document.getElementById('msAltitude'),
      msFlightDirectionRange: document.getElementById('msFlightDirectionRange'),
      msFlightDirection: document.getElementById('msFlightDirection'),
      msSpeed: document.getElementById('msSpeed'),
      msOverlapPctRange: document.getElementById('msOverlapPctRange'),
      msOverlapPct: document.getElementById('msOverlapPct'),
      msEdgeFactorPctRange: document.getElementById('msEdgeFactorPctRange'),
      msEdgeFactorPct: document.getElementById('msEdgeFactorPct'),
      msStartSpeedActive: document.getElementById('msStartSpeedActive'),
      msStartSpeed: document.getElementById('msStartSpeed'),
      msHfov: document.getElementById('msHfov'),
      msVfov: document.getElementById('msVfov'),
      msDfov: document.getElementById('msDfov'),
      msFovCombined: document.getElementById('msFovCombined'),
      msEndAction: document.getElementById('msEndAction'),
      msHeadingMode: document.getElementById('msHeadingMode'),
      msWaypointType: document.getElementById('msWaypointType'),
      msTurnDistance: document.getElementById('msTurnDistance'),
      msSafeStartAltitude: document.getElementById('msSafeStartAltitude'),
      msDirectToStart: document.getElementById('msDirectToStart'),
      msGimbalMode: document.getElementById('msGimbalMode'),
      msGimbalAngleActive: document.getElementById('msGimbalAngleActive'),
      msAltitudeMode: document.getElementById('msAltitudeMode'),
      saveBtn: document.getElementById('saveBtn'),
      createBtn: document.getElementById('createBtn'),
      newBtn: document.getElementById('newBtn'),
      status: document.getElementById('status'),
      createFieldModalBackdrop: document.getElementById('createFieldModalBackdrop'),
      create_feld_id: document.getElementById('create_feld_id'),
      create_name: document.getElementById('create_name'),
      create_jg: document.getElementById('create_jg'),
      create_gemeinde: document.getElementById('create_gemeinde'),
      create_number: document.getElementById('create_number'),
      create_std_flughoehe: document.getElementById('create_std_flughoehe'),
      create_startplatz: document.getElementById('create_startplatz'),
      create_flaechendaten: document.getElementById('create_flaechendaten'),
      create_flugweg: document.getElementById('create_flugweg'),
      createModalCancelBtn: document.getElementById('createModalCancelBtn'),
      createModalSaveBtn: document.getElementById('createModalSaveBtn'),
      drawPolygonBtn: document.getElementById('drawPolygonBtn'),
      baseMapMode: document.getElementById('baseMapMode'),
      editPathBtn: document.getElementById('editPathBtn'),
      mapHelpBtn: document.getElementById('mapHelpBtn'),
      mapQuickHelp: document.getElementById('mapQuickHelp'),
      mapQuickHelpBody: document.getElementById('mapQuickHelpBody'),
      hideContextHelpBtn: document.getElementById('hideContextHelpBtn'),
      clearPolygonBtn: document.getElementById('clearPolygonBtn'),
      pickStartBtn: document.getElementById('pickStartBtn'),
      findPlaceBtn: document.getElementById('findPlaceBtn'),
      undoBtn: document.getElementById('undoBtn'),
      computePathBtn: document.getElementById('computePathBtn'),
      newFieldQuickBtn: document.getElementById('newFieldQuickBtn'),
      openMissionBtn: document.getElementById('openMissionBtn'),
      openPointParamsBtn: document.getElementById('openPointParamsBtn'),
      exportUavJsonBtn: document.getElementById('exportUavJsonBtn'),
      exportDjiKmzBtn: document.getElementById('exportDjiKmzBtn'),
      mapSetupBtn: document.getElementById('mapSetupBtn'),
      mapSetupPanel: document.getElementById('mapSetupPanel'),
      displayControlBox: document.getElementById('displayControlBox'),
      mapMenuBox: document.getElementById('mapMenuBox'),
      formDrawerBox: document.getElementById('formDrawerBox'),
      editorRightDrawer: document.getElementById('editorRightDrawer'),
      editorRightDrawerTitle: document.getElementById('editorRightDrawerTitle'),
      editorRightDrawerSubtitle: document.getElementById('editorRightDrawerSubtitle'),
      editorRightDrawerCloseBtn: document.getElementById('editorRightDrawerCloseBtn'),
      missionDrawerPanel: document.getElementById('missionDrawerPanel'),
      waypointDrawerPanel: document.getElementById('waypointDrawerPanel'),
      waypointNavTabs: document.getElementById('waypointNavTabs'),
      waypointDrawerFoot: document.getElementById('waypointDrawerFoot'),
      missionSettingsBox: document.getElementById('missionSettingsBox'),
      wpTabPrev: document.getElementById('wpTabPrev'),
      wpTabWaypoint: document.getElementById('wpTabWaypoint'),
      wpTabAll: document.getElementById('wpTabAll'),
      wpTabNext: document.getElementById('wpTabNext'),
      wpSinglePane: document.getElementById('wpSinglePane'),
      wpAllPane: document.getElementById('wpAllPane'),
      wpAllList: document.getElementById('wpAllList'),
      wpApplyAllBtn: document.getElementById('wpApplyAllBtn'),
      wpLat: document.getElementById('wpLat'),
      wpLng: document.getElementById('wpLng'),
      wpLatWrap: document.getElementById('wpLatWrap'),
      wpLngWrap: document.getElementById('wpLngWrap'),
      wpAllNote: document.getElementById('wpAllNote'),
      wpUseMissionHeight: document.getElementById('wpUseMissionHeight'),
      wpAltitude: document.getElementById('wpAltitude'),
      wpAltitudeRange: document.getElementById('wpAltitudeRange'),
      wpMissionAltHint: document.getElementById('wpMissionAltHint'),
      wpAglHint: document.getElementById('wpAglHint'),
      wpStartRelHint: document.getElementById('wpStartRelHint'),
      wpUseMissionSpeed: document.getElementById('wpUseMissionSpeed'),
      wpSpeed: document.getElementById('wpSpeed'),
      wpSpeedRange: document.getElementById('wpSpeedRange'),
      wpMissionSpeedHint: document.getElementById('wpMissionSpeedHint'),
      wpUseMissionWaypointType: document.getElementById('wpUseMissionWaypointType'),
      wpWaypointType: document.getElementById('wpWaypointType'),
      wpTurnDistance: document.getElementById('wpTurnDistance'),
      wpTurnDistanceRange: document.getElementById('wpTurnDistanceRange'),
      wpMissionTurnHint: document.getElementById('wpMissionTurnHint'),
      wpUseMissionYaw: document.getElementById('wpUseMissionYaw'),
      wpUseMissionPathMode: document.getElementById('wpUseMissionPathMode'),
      wpUseMissionDirectionMode: document.getElementById('wpUseMissionDirectionMode'),
      wpHeadingMode: document.getElementById('wpHeadingMode'),
      wpAlignment: document.getElementById('wpAlignment'),
      wpDirectionWrap: document.getElementById('wpDirectionWrap'),
      wpDirection: document.getElementById('wpDirection'),
      wpDirectionRange: document.getElementById('wpDirectionRange'),
      wpPitchWrap: document.getElementById('wpPitchWrap'),
      wpPitch: document.getElementById('wpPitch'),
      wpPitchRange: document.getElementById('wpPitchRange'),
      wpSaveBtn: document.getElementById('wpSaveBtn'),
      wpCancelBtn: document.getElementById('wpCancelBtn'),
      wpDeleteBtn: document.getElementById('wpDeleteBtn'),
      showBoxMapMenu: document.getElementById('showBoxMapMenu'),
      showBoxDisplayControl: document.getElementById('showBoxDisplayControl'),
      showBoxContextHelp: document.getElementById('showBoxContextHelp'),
      showBoxSwissOverlay: document.getElementById('showBoxSwissOverlay'),
      showBoxMissionSettings: document.getElementById('showBoxMissionSettings'),
      showBoxFieldCharacteristics: document.getElementById('showBoxFieldCharacteristics'),
      showBoxWaypointData: document.getElementById('showBoxWaypointData'),
      showBoxFormDrawer: document.getElementById('showBoxFormDrawer'),
      showManualPath: document.getElementById('showManualPath'),
      showAlgorithmPath: document.getElementById('showAlgorithmPath'),
      showFieldPolygon: document.getElementById('showFieldPolygon'),
      showCoverageCircles: document.getElementById('showCoverageCircles'),
      coverageSourceLabel: document.getElementById('coverageSourceLabel'),
      showOverlapAreas: document.getElementById('showOverlapAreas'),
      showUncoveredAreas: document.getElementById('showUncoveredAreas'),
      forceCoverageFix: document.getElementById('forceCoverageFix'),
      showBasePatterns: document.getElementById('showBasePatterns'),
      showSectionDirections: document.getElementById('showSectionDirections'),
      showCorrectionPoints: document.getElementById('showCorrectionPoints'),
      showTurnCurves: document.getElementById('showTurnCurves'),
      showControlPoints: document.getElementById('showControlPoints'),
      showAglLimit: document.getElementById('showAglLimit'),
      showFieldControlPoints: document.getElementById('showFieldControlPoints'),
      useFieldReferenceAgl: document.getElementById('useFieldReferenceAgl'),
      computeMode: document.getElementById('computeMode'),
      swissOverlayBox: document.getElementById('swissOverlayBox'),
      fieldCharacteristicsBox: document.getElementById('fieldCharacteristicsBox'),
      wpDebugTableBox: document.getElementById('wpDebugTableBox'),
      fcSource: document.getElementById('fcSource'),
      fcPathLength: document.getElementById('fcPathLength'),
      fcClimbUp: document.getElementById('fcClimbUp'),
      fcClimbDown: document.getElementById('fcClimbDown'),
      fcFlightTime: document.getElementById('fcFlightTime'),
      layerRoadNames: document.getElementById('layerRoadNames'),
      layerRoadNamesOpacity: document.getElementById('layerRoadNamesOpacity'),
      layerRoadNamesOpacityLabel: document.getElementById('layerRoadNamesOpacityLabel'),
      layerElectric: document.getElementById('layerElectric'),
      layerElectricOpacity: document.getElementById('layerElectricOpacity'),
      layerElectricOpacityLabel: document.getElementById('layerElectricOpacityLabel'),
      layerBoundaries: document.getElementById('layerBoundaries'),
      layerBoundariesOpacity: document.getElementById('layerBoundariesOpacity'),
      layerBoundariesOpacityLabel: document.getElementById('layerBoundariesOpacityLabel'),
      layerJagdbann: document.getElementById('layerJagdbann'),
      layerJagdbannOpacity: document.getElementById('layerJagdbannOpacity'),
      layerJagdbannOpacityLabel: document.getElementById('layerJagdbannOpacityLabel'),
      layerRpas: document.getElementById('layerRpas'),
      layerRpasOpacity: document.getElementById('layerRpasOpacity'),
      layerRpasOpacityLabel: document.getElementById('layerRpasOpacityLabel'),
      layerUas: document.getElementById('layerUas'),
      layerUasOpacity: document.getElementById('layerUasOpacity'),
      layerUasOpacityLabel: document.getElementById('layerUasOpacityLabel'),
      layerAulav: document.getElementById('layerAulav'),
      layerAulavOpacity: document.getElementById('layerAulavOpacity'),
      layerAulavOpacityLabel: document.getElementById('layerAulavOpacityLabel'),
      layerAlti3d: document.getElementById('layerAlti3d'),
      layerAlti3dOpacity: document.getElementById('layerAlti3dOpacity'),
      layerAlti3dOpacityLabel: document.getElementById('layerAlti3dOpacityLabel'),
      layerSurface3d: document.getElementById('layerSurface3d'),
      layerSurface3dOpacity: document.getElementById('layerSurface3dOpacity'),
      layerSurface3dOpacityLabel: document.getElementById('layerSurface3dOpacityLabel'),
      layerDabs: document.getElementById('layerDabs'),
      layerDabsOpacity: document.getElementById('layerDabsOpacity'),
      layerDabsOpacityLabel: document.getElementById('layerDabsOpacityLabel'),
      layerObstacles: document.getElementById('layerObstacles'),
      layerObstaclesOpacity: document.getElementById('layerObstaclesOpacity'),
      layerObstaclesOpacityLabel: document.getElementById('layerObstaclesOpacityLabel'),
      obstacleHeights: document.getElementById('obstacleHeights'),
      obstacleHeightsRow: document.getElementById('obstacleHeightsRow'),
      layerGrundbuch: document.getElementById('layerGrundbuch'),
      layerGrundbuchOpacity: document.getElementById('layerGrundbuchOpacity'),
      layerGrundbuchOpacityLabel: document.getElementById('layerGrundbuchOpacityLabel'),
      layerCadastral: document.getElementById('layerCadastral'),
      layerCadastralOpacity: document.getElementById('layerCadastralOpacity'),
      layerCadastralOpacityLabel: document.getElementById('layerCadastralOpacityLabel'),
      layerNature: document.getElementById('layerNature'),
      layerNatureOpacity: document.getElementById('layerNatureOpacity'),
      layerNatureOpacityLabel: document.getElementById('layerNatureOpacityLabel'),
      layerCableways: document.getElementById('layerCableways'),
      layerCablewaysOpacity: document.getElementById('layerCablewaysOpacity'),
      layerCablewaysOpacityLabel: document.getElementById('layerCablewaysOpacityLabel'),
      layerWanderwege: document.getElementById('layerWanderwege'),
      layerWanderwegeOpacity: document.getElementById('layerWanderwegeOpacity'),
      layerWanderwegeOpacityLabel: document.getElementById('layerWanderwegeOpacityLabel'),
      editorMap: document.getElementById('editorMap'),
      wsSourceInfo: document.getElementById('wsSourceInfo'),
    };

    let allFields = [];
    const QUICK_SLOT_COUNT = 12;
    let quickFieldSlots = new Array(QUICK_SLOT_COUNT).fill('');
    const coreCalcParams = {
      turn_delay_s: 5.5,
      turn_delay_skip_wp_s: 1.0,
      agl_tolerance_m: 10.0,
      agl_display_deviation_m: 10.0,
    };
    let coreCalcParamsLoaded = false;
    let droneProfiles = [];
    let selectedId = '';
    let map = null;
    const MAP_SCREEN_PX_MM = 0.28;
    function formatSwissScaleDenom(denom) {
      const n = Math.max(1, Math.round(Number(denom) || 1));
      const s = String(n);
      let out = '';
      for (let i = 0; i < s.length; i += 1) {
        if (i && ((s.length - i) % 3 === 0)) out += "'";
        out += s.charAt(i);
      }
      return '1:' + out;
    }
    function formatScaleBarDistance(meters) {
      const m = Number(meters);
      if (!(m > 0)) return '0 m';
      if (m >= 1000 && Math.abs(m / 1000 - Math.round(m / 1000)) < 0.05) {
        return String(Math.round(m / 1000)) + ' km';
      }
      if (m >= 1000) return (m / 1000).toFixed(1).replace('.', ',') + ' km';
      return String(Math.round(m)) + ' m';
    }
    function niceScaleBarMeters(targetM) {
      if (!(targetM > 0) || !Number.isFinite(targetM)) return 100;
      const mag = Math.pow(10, Math.floor(Math.log10(targetM)));
      const n = targetM / mag;
      const nice = n < 1.5 ? 1 : n < 3.5 ? 2 : n < 7.5 ? 5 : 10;
      return nice * mag;
    }
    function roundDisplayScaleDenom(raw) {
      const n = Number(raw);
      if (!(n > 0) || !Number.isFinite(n)) return 0;
      const standards = [10000, 25000, 50000];
      for (let i = 0; i < standards.length; i += 1) {
        const s = standards[i];
        if (Math.abs(n - s) / s < 0.03) return s;
      }
      if (n >= 20000) return Math.round(n / 500) * 500;
      if (n >= 5000) return Math.round(n / 100) * 100;
      return Math.max(1, Math.round(n / 50) * 50);
    }
    function getMapMetersPerPixel() {
      try {
        if (!map || typeof map.getView !== 'function') return 0;
        const view = map.getView();
        const center = view.getCenter();
        const res = Number(view.getResolution());
        if (!Array.isArray(center) || !(res > 0)) return 0;
        const proj = view.getProjection ? view.getProjection() : 'EPSG:3857';
        if (ol.proj && typeof ol.proj.getPointResolution === 'function') {
          const pr = Number(ol.proj.getPointResolution(proj, res, center));
          return pr > 0 ? pr : 0;
        }
        const ll = ol.proj.toLonLat(center, proj);
        const lat = Number(ll && ll[1]);
        if (!Number.isFinite(lat)) return res;
        return res * Math.cos(lat * Math.PI / 180);
      } catch (_) {
        return 0;
      }
    }
    function updateMapScaleBar() {
      const el = document.getElementById('rkrkMapScaleBar');
      if (!el) return;
      const mPerPx = getMapMetersPerPixel();
      if (!(mPerPx > 0)) return;
      const denom = roundDisplayScaleDenom(mPerPx / (MAP_SCREEN_PX_MM / 1000));
      const niceM = niceScaleBarMeters(mPerPx * 110);
      const barPx = Math.max(48, Math.min(160, niceM / mPerPx));
      el.style.width = Math.round(barPx) + 'px';
      const ratio = el.querySelector('.rkrk-scale-ratio');
      const distEl = el.querySelector('.rkrk-scale-dist');
      if (ratio) ratio.textContent = 'Massstab ' + formatSwissScaleDenom(denom);
      if (distEl) distEl.textContent = formatScaleBarDistance(niceM);
    }
    let drawInteraction = null;
    let startDrawInteraction = null;
    let modifyInteraction = null;
    let pathModifyInteraction = null;
    let pathEditFeatures = null;
    let startModifyInteraction = null;
    let parkModifyInteraction = null;
    let snapInteraction = null;
    let currentPolygonFeature = null;
    let currentStartFeature = null;
    let polygonSource = null;
    let startSource = null;
    let parkSource = null;
    let parkLayer = null;
    let poiSource = null;
    let poiLayer = null;
    let findPlaceSource = null;
    let manualPathSource = null;
    let computedPathSource = null;
    let controlPointSource = null;
    let coverageSource = null;
    let overlapSource = null;
    let uncoveredSource = null;
    let wsVisualSource = null;
    let vertexSource = null;
    let midpointSource = null;
    let osmLayer = null;
    let swisstopoLayer = null;
    let swissgrauLayer = null;
    let swissimageLayer = null;
    let pictureMap = null;
    let pictureTileLayer = null;
    let picturePolySource = null;
    let picturePathSource = null;
    let picturePathLayer = null;
    let pictureStartSource = null;
    let polygonLayer = null;
    let startLayer = null;
    let vertexLayer = null;
    let midpointLayer = null;
    let pathMidpointLayer = null;
    let findPlaceLayer = null;
    let manualPathLayer = null;
    let computedPathLayer = null;
    let controlPointLayer = null;
    let coverageLayer = null;
    let overlapLayer = null;
    let uncoveredLayer = null;
    let wsVisualLayer = null;
    const overlayLayersByKey = {};
    const overlayResolvedLayerIds = {};
    const overlayApplyTokenByKey = {};
    let dabsCtl = null;
    let uasCtl = null;
    let uasLabelCtl = null;
    let obstInfoCtl = null;
    let obstacleHeightCtl = null;
    const overlayDefinitions = {
      roadNames: {
        label: 'Strassennamen',
        fixedLayerIds: [
          'ch.astra.hauptstrassennetz',
          'ch.swisstopo.amtliches-strassenverzeichnis',
        ],
        searches: [{ text: 'amtliches-strassenverzeichnis', tokens: ['amtliches-strassenverzeichnis'] }],
      },
      electric: {
        label: 'Elektrische Anlagen',
        fixedLayerIds: ['ch.bfe.elektrische-anlagen_ueber_36'],
        searches: [{ text: 'Elektrische Anlagen', tokens: ['elektrische-anlagen_ueber_36'] }],
      },
      boundaries: {
        label: 'Kantons, Gemeindegrenzen',
        fixedLayerIds: ['ch.swisstopo.swissboundaries3d-gemeinde-flaeche.fill'],
        searches: [
          { text: 'Kantonsgrenzen', tokens: ['kanton'] },
          { text: 'Gemeindegrenzen', tokens: ['gemeinde'] },
        ],
      },
      jagdbann: {
        label: 'Jagdbanngebiete AuLaV',
        fixedLayerIds: ['ch.bafu.schutzgebiete-aulav_jagdbanngebiete'],
        searches: [{ text: 'Jagdbanngebiete AuLaV', tokens: ['aulav', 'jagdbann'] }],
      },
      rpas: {
        label: 'RPAS Karte',
        fixedLayerIds: ['ch.bazl.einschraenkungen-drohnen'],
        searches: [
          { text: 'RPAS', tokens: ['rpas'] },
          { text: 'Drohnen', tokens: ['drohnen'] },
        ],
      },
      uasZones: {
        label: 'UAS Aktivitätszonen',
        fixedLayerIds: ['ch.bazl.uas-aktivitaetszonen'],
        searches: [
          { text: 'UAS Aktivitätszonen', tokens: ['uas', 'aktivitaetszonen'] },
        ],
      },
      aulavWohngebiete: {
        label: 'Wohngebiete nach AuLaV',
        fixedLayerIds: ['ch.bazl.wohngebiete-aulav'],
        searches: [
          { text: 'Wohngebiete nach AuLaV', tokens: ['wohngebiete', 'aulav'] },
        ],
      },
      alti3dRelief: {
        label: 'swissALTI3D Relief',
        fixedLayerIds: ['ch.swisstopo.swissalti3d-reliefschattierung'],
        searches: [
          { text: 'swissALTI3D Relief', tokens: ['swissalti3d', 'reliefschattierung'] },
          { text: 'multidirektionales Relief', tokens: ['multidirektional'] },
        ],
      },
      surface3dRelief: {
        label: 'swissSURFACE3D Relief',
        fixedLayerIds: ['ch.swisstopo.swisssurface3d-reliefschattierung-multidirektional'],
        searches: [
          { text: 'swissSURFACE3D Relief', tokens: ['swisssurface3d', 'reliefschattierung'] },
          { text: 'multidirektionales Relief', tokens: ['multidirektional'] },
        ],
      },
      dabs: {
        label: 'DABS bis 200 m AGL',
        kind: 'dabs',
      },
      obstacles: {
        label: 'Luftfahrthindernisse',
        fixedLayerIds: ['ch.bazl.luftfahrthindernis'],
        searches: [{ text: 'Luftfahrthindernisse', tokens: ['ch.bazl.luftfahrthindernis'] }],
      },
      grundbuch: {
        label: 'Grundbuchinformationen',
        fixedLayerIds: ['ch.swisstopo-vd.geometa-grundbuch'],
        searches: [
          { text: 'Grundbuchinformationen', tokens: ['grundbuch'] },
          { text: 'geometa grundbuch', tokens: ['geometa-grundbuch'] },
        ],
      },
      cadastral: {
        label: 'CadastralWebMap',
        fixedLayerIds: ['ch.kantone.cadastralwebmap-farbe'],
        searches: [
          { text: 'CadastralWebMap', tokens: ['cadastralwebmap'] },
          { text: 'cadastralwebmap farbe', tokens: ['cadastral', 'farbe'] },
        ],
      },
      nature: {
        label: 'Naturschutzgebiete',
        fixedLayerIds: ['ch.pronatura.naturschutzgebiete'],
        searches: [{ text: 'Naturschutzgebiete', tokens: ['ch.pronatura.naturschutzgebiete'] }],
      },
      cableways: {
        label: 'Seilbahnen',
        fixedLayerIds: [
          'ch.swisstopo.bahnen-winter',
          'ch.swisstopo.swisstlm3d-uebrigerverkehr',
        ],
        searches: [{ text: 'bahnen-winter', tokens: ['bahnen-winter'] }],
      },
      wanderwege: {
        label: 'Wanderwege',
        fixedLayerIds: ['ch.swisstopo.swisstlm3d-wanderwege'],
        searches: [{ text: 'Wanderwege', tokens: ['wanderwege'] }],
      },
    };
    let isEditModeActive = false;
    const editHistory = [];
    const MAX_HISTORY = 60;
    let restoringHistory = false;
    let isPathEditModeActive = false;
    let flightRecomputeTimer = null;
    let flightRecomputeRunning = false;
    let flightRecomputeQueued = false;
    let mapSyncTimer = null;
    let fieldCharacteristicsRefreshTimer = null;
    let fieldCharacteristicsRefreshToken = 0;
    let currentTurnWaypointIndices = new Set();
    let currentAnalyzeFormula = null;
    let currentPathCalcSource = 'mission';
    let resetOriginalPathOnNextRecompute = false;
    const terrainHeightCache = new Map();
    let savedFieldSnapshot = '';
    let currentManualPath = [];
    let originalManualPath = [];
    let currentComputedPath = [];
    let routeCoverageReady = false;
    let generatedPathSnapshot = [];
    let pathDivergedFromGenerated = false;
    let creatingNewField = false;
    let catalogPictureDir = '';
    let planOrigin = '';
    let skipFlightRecomputeOnce = false;
    let blockAutoPathRecompute = 0;
    let pathRecomputeToken = 0;

    function holdStoredRoute() {
      blockAutoPathRecompute += 1;
      pathRecomputeToken += 1;
      flightRecomputeQueued = false;
      setTimeout(() => {
        blockAutoPathRecompute = Math.max(0, blockAutoPathRecompute - 1);
      }, 500);
    }

    function storedFieldShouldKeepRoute(v) {
      const fromCatalog = String(v && v.id || '').trim() !== '';
      let storedRoute = false;
      try { storedRoute = parseTriplets(String(v && v.flugweg || '')).length >= 2; } catch (_) { storedRoute = false; }
      return fromCatalog || storedRoute;
    }
    let newFieldDraftTimer = null;
    let measureSource = null;
    let measureLayer = null;
    let measureActive = false;
    let measureCoords = [];
    let measureHover = null;
    let currentControlPointsPath = [];
    let currentControlPointsField = [];
    let controlPointRefreshTimer = null;
    let controlPointRefreshToken = 0;
    let waypointOverrides = {};
    let missionPois = [];
    let missionPoi = null;
    let poiPlaceArmed = false;
    let poiPlaceTarget = '';
    let editorEasyModeForm = true;
    let selectedWaypointIndex1 = 1;
    let waypointFormSnapshot = null;
    let rightDrawerMode = null;
    let waypointListTab = 'single';
    let waypointFormFilling = false;

    function showStatus(text, ok = true) {
      els.status.style.display = '';
      els.status.className = `status ${ok ? 'ok' : 'err'}`;
      els.status.textContent = text;
    }

    function currentFieldSnapshot() {
      const f = formData();
      const tracked = {
        id: String(f.id || ''),
        feld_id: String(f.feld_id || ''),
        name: String(f.name || ''),
        jg: String(f.jg || ''),
        std_flughoehe: String(f.std_flughoehe || ''),
        flugzeit: String(f.flugzeit || ''),
        distanz: String(f.distanz || ''),
        wegpunkte: String(f.wegpunkte || ''),
        startplatz: String(f.startplatz || ''),
        flaechendaten: String(f.flaechendaten || ''),
        flugweg: String(f.flugweg || ''),
        flurname: String(f.flurname || ''),
        grundstueck_id: String(f.grundstueck_id || ''),
        gemeinde: String(f.gemeinde || ''),
        betrieb: String(f.betrieb || ''),
        freigabe: String(f.freigabe || ''),
        warnung: String(f.warnung || ''),
        jaeger: String(f.jaeger || ''),
        bemerkung: String(f.bemerkung || ''),
        flug: String(f.flug || ''),
        funde: String(f.funde || ''),
        bauer: String(f.bauer || ''),
        notiz: String(f.notiz || ''),
        flaeche: String(f.flaeche || ''),
        suchfeld_bild: String(f.suchfeld_bild || ''),
        flugplan_bild: String(f.flugplan_bild || ''),
        jason_file: String(f.jason_file || ''),
        kml_file: String(f.kml_file || ''),
        kmz_file: String(f.kmz_file || ''),
        parkplatz: String(f.parkplatz || ''),
        zentrum: String(f.zentrum || ''),
        export: String(f.export || ''),
        select: String(f.select || ''),
        mission_settings: f.mission_settings || {},
      };
      return JSON.stringify(tracked);
    }

    function updateSaveButtonState() {
      if (!els.saveBtn) return;
      const hasExistingField = String(els.id && els.id.value || '').trim() !== '';
      const dirty = hasExistingField && currentFieldSnapshot() !== savedFieldSnapshot;
      els.saveBtn.disabled = !dirty;
      els.saveBtn.title = dirty
        ? 'Aenderungen speichern'
        : (hasExistingField ? 'Keine ungespeicherten Aenderungen' : 'Nur fuer bestehendes Feld');
      updateCreateSaveButton();
    }

    function markFieldAsSaved() {
      savedFieldSnapshot = currentFieldSnapshot();
      updateSaveButtonState();
    }

    function currentHelpViewId() {
      if (drawInteraction) return 'editor.draw';
      if (startDrawInteraction) return 'editor.start';
      const activeFold = document.querySelector('.plan-fold.is-active');
      const phase = activeFold ? activeFold.getAttribute('data-plan-phase') : '';
      if (phase === 'finish') return 'editor.fielddata';
      if (phase === 'waypoints') return 'editor.waypoint';
      if (phase === 'optimize' || isPathEditModeActive) return 'editor.edit_path';
      if (phase === 'outline' || isEditModeActive) return phase === 'outline' ? 'editor.draw' : 'editor.edit_polygon';
      if (phase === 'start') return 'editor.start';
      if (phase === 'place') return 'editor.fielddata';
      if (isEditModeActive) return 'editor.edit_polygon';
      if (els.formDrawerBox && els.formDrawerBox.open) return 'editor.fielddata';
      if (els.showCoverageCircles && els.showCoverageCircles.checked) return 'editor.coverage';
      if (rightDrawerMode === 'waypoint') return 'editor.waypoint';
      if (rightDrawerMode === 'mission') return 'editor.mission';
      return 'editor.overview';
    }

    function setContextHelp(mode = 'default') {
      const target = els.mapQuickHelpBody || els.mapQuickHelp;
      if (!target) return;
      const helpByMode = {
        default: '<strong>Feldplanung</strong><br>1 Fläche: bestehendes oder neues Feld, Umrandung, danach GIS-Nummer, Drohne, Flughöhe<br>2 Start<br>3 Flugpfad: Suchabdeckung, Flugrichtung<br>4 Wegpunkt: Höhe pro Punkt<br>5 Speichern: Feldname<br>6 Daten: zusätzliche Feldkatalogdaten nach dem Speichern',
        draw: '<strong>Fläche</strong><br>- Linksklick setzt Eckpunkte<br>- Doppelklick beendet die Umrandung<br>- Erst danach liest die CadastralWebMap die GIS-Nummer aus dem Feldinnern<br>- RPAS, Elektrische Anlagen, Luftfahrthindernisse, DABS und Flugzone können zur Prüfung eingeschaltet werden',
        edit: '<strong>Feldrand bearbeiten</strong><br>- Eckpunkte ziehen<br>- <strong>+</strong> auf der Kante fügt einen Punkt ein<br>- Doppelklick löscht einen Punkt',
        start: '<strong>Startpunkt</strong><br>Klick setzt den Start. Ziehen verschiebt ihn. Danach wird die Route neu berechnet.',
        path: '<strong>Flugpfad</strong><br>- Wegpunkte ziehen, <strong>+</strong> setzt einen neuen, Doppelklick löscht<br>- Suchabdeckung und nicht abgedeckte Flächen<br>- Flugrichtung als Regler, mit −5 −1 +1 +5, oder als Vorschlag<br>- Wegpunktwerte erst in Schritt 4',
      };
      target.innerHTML = helpByMode[mode] || helpByMode.default;
      if (window.RkrkHelp) RkrkHelp.setView();
    }

    function currentAglValue() {
      const agl = Number(els.std_flughoehe && els.std_flughoehe.value);
      return Number.isFinite(agl) ? agl : 0;
    }

    function currentAglLabel() {
      const agl = currentAglValue();
      const useFieldRef = !!(els.useFieldReferenceAgl && els.useFieldReferenceAgl.checked);
      return useFieldRef ? `AGL ${agl.toFixed(1)}m` : `ALT ${agl.toFixed(1)}m`;
    }

    function readNumericElement(el, fallback = 0) {
      if (!el) return fallback;
      let raw = '';
      if ('value' in el) {
        raw = String(el.value || '');
      } else {
        raw = String(el.textContent || '');
      }
      const n = Number((raw.match(/-?\d+(?:\.\d+)?/) || [])[0]);
      return Number.isFinite(n) ? n : fallback;
    }

    function writeFovInfo(el, value) {
      if (!el) return;
      const n = Number(value);
      const v = Number.isFinite(n) ? n : 0;
      el.textContent = `${v.toFixed(1)}°`;
    }

    function updateCombinedFovText() {
      if (!els.msFovCombined) return;
      const hf = readNumericElement(els.msHfov, 49).toFixed(1);
      const vf = readNumericElement(els.msVfov, 40).toFixed(1);
      const df = readNumericElement(els.msDfov, 61).toFixed(1);
      els.msFovCombined.textContent = `HFOV ${hf}° | VFOV ${vf}° | DFOV ${df}°`;
    }

    function effectiveCoverageAglM() {
      const stdAgl = Number(els.std_flughoehe && els.std_flughoehe.value);
      const missionAlt = Number(els.msAltitude && els.msAltitude.value);
      const paramsActive = !!(els.msKmzParamsActive && els.msKmzParamsActive.checked);
      const useFieldRef = !!(els.useFieldReferenceAgl && els.useFieldReferenceAgl.checked);
      // For W/S coverage display, active flight-parameter altitude should take precedence.
      if (paramsActive && Number.isFinite(missionAlt) && missionAlt > 0) return missionAlt;
      if (useFieldRef && Number.isFinite(stdAgl) && stdAgl > 0) return stdAgl;
      if (Number.isFinite(missionAlt) && missionAlt > 0) return missionAlt;
      if (Number.isFinite(stdAgl) && stdAgl > 0) return stdAgl;
      return 0;
    }

    function currentCoverageMetrics() {
      const aglM = effectiveCoverageAglM();
      const hfovDeg = readNumericElement(els.msHfov, 49);
      const vfovDeg = readNumericElement(els.msVfov, 40);
      const dfovDeg = readNumericElement(els.msDfov, 61);
      const overlapPct = Number(els.msOverlapPct && els.msOverlapPct.value);
      const overlap = Number.isFinite(overlapPct)
        ? Math.min(0.95, Math.max(0, overlapPct / 100))
        : 0.15;
      const shared = window.RkrkField && RkrkField.metricsFromFov(aglM, hfovDeg, vfovDeg, overlap);
      const hfovRad = (Number(hfovDeg) * Math.PI) / 180;
      const vfovRad = (Number(vfovDeg) * Math.PI) / 180;
      const dfovRad = (Number(dfovDeg) * Math.PI) / 180;
      const widthM = shared ? shared.widthM : (Number.isFinite(aglM) && aglM > 0 && Number.isFinite(hfovRad)
        ? (2 * aglM * Math.tan(hfovRad / 2))
        : NaN);
      const lengthM = shared ? shared.lengthM : (Number.isFinite(aglM) && aglM > 0 && Number.isFinite(vfovRad)
        ? (2 * aglM * Math.tan(vfovRad / 2))
        : NaN);
      const cornerDiameterM = Number.isFinite(aglM) && aglM > 0 && Number.isFinite(dfovRad)
        ? (2 * aglM * Math.tan(dfovRad / 2))
        : NaN;
      const spacingM = shared ? shared.spacingM : (Number.isFinite(widthM) && widthM > 0
        ? Math.max(0, (1 - overlap) * widthM)
        : NaN);
      const formulaW = Number(currentAnalyzeFormula && currentAnalyzeFormula.W);
      const formulaS = Number(currentAnalyzeFormula && currentAnalyzeFormula.S);
      const preferAnalyzeFormula = currentPathCalcSource === 'analyze';
      return {
        widthM: preferAnalyzeFormula && Number.isFinite(formulaW) && formulaW > 0 ? formulaW : widthM,
        lengthM,
        cornerDiameterM,
        spacingM: preferAnalyzeFormula && Number.isFinite(formulaS) && formulaS > 0 ? formulaS : spacingM,
        hfovDeg: hfovDeg,
        vfovDeg: vfovDeg,
        coverageWindow: coverageUsesCameraWindow() ? 'W' : 'S',
      };
    }

    function coverageUsesCameraWindow() {
      if (!editorDebugOn()) return false;
      const picked = document.querySelector('input[name="coverageWindowMode"]:checked');
      return !!(picked && picked.value === 'W');
    }

    function applyCoverageWindowChoice(value) {
      const mode = value === 'W' ? 'W' : 'S';
      document.querySelectorAll('input[name="coverageWindowMode"]').forEach((el) => {
        el.checked = el.value === mode;
      });
    }

    function refreshWsSourceInfo() {
      if (!els.wsSourceInfo) return;
      const formulaW = Number(currentAnalyzeFormula && currentAnalyzeFormula.W);
      const formulaS = Number(currentAnalyzeFormula && currentAnalyzeFormula.S);
      const fromAnalyze = currentPathCalcSource === 'analyze'
        && Number.isFinite(formulaW) && formulaW > 0
        && Number.isFinite(formulaS) && formulaS > 0;
      els.wsSourceInfo.textContent = fromAnalyze
        ? 'S/W Quelle: Analyze'
        : 'S/W Quelle: Mission (lokal)';
    }

    function refreshWsVisualLines() {
      if (!map || !wsVisualSource) return;
      wsVisualSource.clear();
      refreshWsSourceInfo();
      const m = currentCoverageMetrics();
      if (!Number.isFinite(m.widthM) || m.widthM <= 0) return;
      if (!Number.isFinite(m.spacingM) || m.spacingM <= 0) return;
      const view = map.getView ? map.getView() : null;
      const size = map.getSize ? map.getSize() : null;
      if (!view || !size) return;
      const extent = view.calculateExtent(size);
      if (!extent || extent.length < 4) return;
      const minX = extent[0];
      const minY = extent[1];
      const maxX = extent[2];
      const maxY = extent[3];
      const width = maxX - minX;
      const height = maxY - minY;
      if (!(Number.isFinite(width) && Number.isFinite(height) && width > 0 && height > 0)) return;
      const anchor = [minX + width * 0.08, minY + height * 0.08];
      const scale = meterToMapUnitScaleAt(anchor);
      const wLen = m.widthM * scale;
      const sLen = m.spacingM * scale;
      const resolution = Number(view.getResolution && view.getResolution()) || 1;
      const yGap = Math.max(18 * resolution, height * 0.035);

      function addLine(name, meters, lenMap, y, color) {
        const start = [anchor[0], y];
        const end = [anchor[0] + lenMap, y];
        const f = new ol.Feature({ geometry: new ol.geom.LineString([start, end]) });
        f.set('wsLabel', `${name}: ${meters.toFixed(2)} m`);
        f.set('wsColor', color);
        wsVisualSource.addFeature(f);
      }

      addLine('W', m.widthM, wLen, anchor[1], '#1d4ed8');
      addLine('S', m.spacingM, sLen, anchor[1] + yGap, '#059669');
    }

    function meterToMapUnitScaleAt(xy) {
      if (!Array.isArray(xy) || xy.length < 2) return 1;
      try {
        const lonLat = ol.proj.toLonLat(xy);
        const latRad = Number(lonLat[1]) * Math.PI / 180;
        const cosLat = Math.cos(latRad);
        if (Number.isFinite(cosLat) && cosLat > 1e-6) return 1 / cosLat;
      } catch (_) {}
      return 1;
    }

    function buildCoverageRectangleRing(center, headingRad, widthM, lengthM) {
      if (!Array.isArray(center) || center.length < 2) return null;
      if (!Number.isFinite(widthM) || widthM <= 0 || !Number.isFinite(lengthM) || lengthM <= 0) return null;
      const scale = meterToMapUnitScaleAt(center);
      const halfW = (widthM * 0.5) * scale;
      const halfL = (lengthM * 0.5) * scale;
      const ux = Math.cos(headingRad);
      const uy = Math.sin(headingRad);
      const vx = -uy;
      const vy = ux;
      // Rounded rectangle in local (flight-aligned) coordinates.
      const radius = Math.min(halfW, halfL) * 0.28;
      const arcSteps = 12;
      const ptsLocal = [];
      const arcCenters = [
        [halfL - radius, halfW - radius],   // top-right
        [halfL - radius, -halfW + radius],  // bottom-right
        [-halfL + radius, -halfW + radius], // bottom-left
        [-halfL + radius, halfW - radius],  // top-left
      ];
      const arcAngles = [
        [0, Math.PI / 2],
        [Math.PI / 2, Math.PI],
        [Math.PI, 1.5 * Math.PI],
        [1.5 * Math.PI, 2 * Math.PI],
      ];
      for (let c = 0; c < arcCenters.length; c++) {
        const [cx, cy] = arcCenters[c];
        const [a0, a1] = arcAngles[c];
        for (let i = 0; i <= arcSteps; i++) {
          if (c > 0 && i === 0) continue;
          const t = i / arcSteps;
          const a = a0 + (a1 - a0) * t;
          ptsLocal.push([cx + radius * Math.cos(a), cy + radius * Math.sin(a)]);
        }
      }
      const ring = ptsLocal.map(([lx, ly]) => [
        center[0] + ux * lx + vx * ly,
        center[1] + uy * lx + vy * ly,
      ]);
      ring.push(ring[0].slice());
      return ring;
    }

    function buildCoverageCircleRing(center, diameterM, steps = 48) {
      if (!Array.isArray(center) || center.length < 2) return null;
      if (!Number.isFinite(diameterM) || diameterM <= 0) return null;
      const scale = meterToMapUnitScaleAt(center);
      const r = (diameterM * 0.5) * scale;
      const pts = [];
      for (let i = 0; i <= steps; i++) {
        const a = (i / steps) * Math.PI * 2;
        pts.push([center[0] + Math.cos(a) * r, center[1] + Math.sin(a) * r]);
      }
      return pts;
    }

    function directionForWaypoint(pathCoords, idx) {
      const prev = idx > 0 ? pathCoords[idx - 1] : null;
      const curr = pathCoords[idx];
      const next = idx < pathCoords.length - 1 ? pathCoords[idx + 1] : null;
      if (prev && next) {
        const inDx = curr[0] - prev[0];
        const inDy = curr[1] - prev[1];
        const outDx = next[0] - curr[0];
        const outDy = next[1] - curr[1];
        const inLen = Math.hypot(inDx, inDy);
        const outLen = Math.hypot(outDx, outDy);
        if (inLen > 1e-9 && outLen > 1e-9) {
          const bx = inDx / inLen + outDx / outLen;
          const by = inDy / inLen + outDy / outLen;
          const bLen = Math.hypot(bx, by);
          if (bLen > 1e-9) return Math.atan2(by, bx);
        }
      }
      if (next) return Math.atan2(next[1] - curr[1], next[0] - curr[0]);
      if (prev) return Math.atan2(curr[1] - prev[1], curr[0] - prev[0]);
      return 0;
    }

    function mapDistancePointToSegment(p, a, b) {
      const vx = b[0] - a[0];
      const vy = b[1] - a[1];
      const wx = p[0] - a[0];
      const wy = p[1] - a[1];
      const c1 = vx * wx + vy * wy;
      if (c1 <= 0) return Math.hypot(p[0] - a[0], p[1] - a[1]);
      const c2 = vx * vx + vy * vy;
      if (c2 <= 1e-12) return Math.hypot(p[0] - a[0], p[1] - a[1]);
      if (c2 <= c1) return Math.hypot(p[0] - b[0], p[1] - b[1]);
      const t = c1 / c2;
      const px = a[0] + t * vx;
      const py = a[1] + t * vy;
      return Math.hypot(p[0] - px, p[1] - py);
    }

    function pointCoveredByPath(p, pathCoords, halfWidthM) {
      if (!Array.isArray(pathCoords) || pathCoords.length < 2) return false;
      const scale = meterToMapUnitScaleAt(p);
      const halfWidthMap = halfWidthM * scale;
      for (let i = 0; i < pathCoords.length - 1; i++) {
        const d = mapDistancePointToSegment(p, pathCoords[i], pathCoords[i + 1]);
        if (d <= halfWidthMap) return true;
      }
      return false;
    }

    function coverageDisplayAtAgl(agl, metrics) {
      const refAgl = effectiveCoverageAglM();
      const h = Number(agl);
      const base = Number.isFinite(h) && h > 1 ? h : (Number.isFinite(refAgl) && refAgl > 0 ? refAgl : 0);
      if (!(base > 0)) return null;
      const scale = Number.isFinite(refAgl) && refAgl > 0 ? (base / refAgl) : 1;
      const widthM = Number(metrics.widthM) * scale;
      const spacingM = Number(metrics.spacingM) * scale;
      if (!(widthM > 0) || !Number.isFinite(spacingM) || spacingM < 0) return null;
      const lookM = Number(metrics.lengthM) * scale;
      return {
        displayM: spacingM + ((widthM - spacingM) / 2),
        lookM: Number.isFinite(lookM) && lookM > 0 ? lookM : 0,
      };
    }

    function addCoverageBandQuad(a, b, halfA, halfB, fillColor) {
      const dx = b[0] - a[0];
      const dy = b[1] - a[1];
      const len = Math.hypot(dx, dy);
      if (!(len > 1e-6) || !(halfA > 0) || !(halfB > 0)) return;
      const nx = -dy / len;
      const ny = dx / len;
      const ring = [
        [a[0] + nx * halfA, a[1] + ny * halfA],
        [b[0] + nx * halfB, b[1] + ny * halfB],
        [b[0] - nx * halfB, b[1] - ny * halfB],
        [a[0] - nx * halfA, a[1] - ny * halfA],
      ];
      ring.push(ring[0].slice());
      const feat = new ol.Feature({ geometry: new ol.geom.Polygon([ring]) });
      feat.set('coverageFillColor', fillColor);
      coverageSource.addFeature(feat);
    }

    function addCoverageTurnSector(center, radiusMap, a0, sweep, fillColor) {
      if (!Array.isArray(center) || !(radiusMap > 0) || !(Math.abs(sweep) > 1e-3)) return;
      const steps = Math.max(6, Math.ceil(Math.abs(sweep) / (Math.PI / 14)));
      const ring = [center.slice()];
      for (let s = 0; s <= steps; s++) {
        const a = a0 + sweep * (s / steps);
        ring.push([
          center[0] + Math.cos(a) * radiusMap,
          center[1] + Math.sin(a) * radiusMap,
        ]);
      }
      ring.push(center.slice());
      const feat = new ol.Feature({ geometry: new ol.geom.Polygon([ring]) });
      feat.set('coverageFillColor', fillColor);
      coverageSource.addFeature(feat);
    }

    function coverageHeadingChange(a, b, c) {
      const v1x = b[0] - a[0];
      const v1y = b[1] - a[1];
      const v2x = c[0] - b[0];
      const v2y = c[1] - b[1];
      const l1 = Math.hypot(v1x, v1y);
      const l2 = Math.hypot(v2x, v2y);
      if (!(l1 > 1e-3) || !(l2 > 1e-3)) return 0;
      const dot = ((v1x / l1) * (v2x / l2)) + ((v1y / l1) * (v2y / l2));
      return Math.acos(Math.max(-1, Math.min(1, dot)));
    }

    function refreshCoverageGeometry() {
      if (!coverageSource) return;
      coverageSource.clear();
      if (overlapSource) overlapSource.clear();
      if (uncoveredSource) uncoveredSource.clear();
      const showCoverage = !!(els.showCoverageCircles && els.showCoverageCircles.checked);
      if (!showCoverage) return;
      const path = workingFlightPath();
      const triplets = normalizeTripletPath(path);
      const pathCoords = triplets.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      if (pathCoords.length < 2) return;
      const metrics = currentCoverageMetrics();
      const fallbackAgl = effectiveCoverageAglM();
      const aglList = triplets.map((p, i) => {
        const row = waypointOverrides && waypointOverrides[String(i + 1)];
        if (window.RkrkField && typeof RkrkField.programmedFlightHeightM === 'function') {
          return RkrkField.programmedFlightHeightM(fallbackAgl, row, p[2]);
        }
        const z = Number(p[2]);
        return Number.isFinite(z) && z > 1 ? z : fallbackAgl;
      });
      const fillColor = 'rgba(64,196,255,0.46)';
      const pitchList = triplets.map((_, i) => {
        const row = waypointOverrides && waypointOverrides[String(i + 1)];
        return row && row.gimbal_pitch;
      });
      const bearingList = triplets.map((_, i) => {
        const yaw = yawDisplayForIndex(i + 1, pathCoords);
        const sym = window.RkrkField && typeof RkrkField.yawSymbol === 'function'
          ? RkrkField.yawSymbol(yaw && yaw.mode, yaw && yaw.angle, yaw && yaw.next)
          : null;
        if (sym && Number.isFinite(sym.bearingDeg)) return sym.bearingDeg;
        if (window.RkrkField && typeof RkrkField.lensBearingDeg === 'function') {
          return RkrkField.lensBearingDeg(pathCoords, i, yaw && yaw.next);
        }
        return yaw && yaw.next;
      });
      const headingModes = triplets.map((_, i) => {
        const yaw = yawDisplayForIndex(i + 1, pathCoords);
        return yaw && yaw.mode;
      });
      const rings = window.RkrkField && RkrkField.buildRouteCoveragePolygons
        ? RkrkField.buildRouteCoveragePolygons(pathCoords, aglList, metrics, fallbackAgl, triplets, routeCoverageReady, pitchList, bearingList, headingModes)
        : [];
      rings.forEach((item) => {
        const piece = Array.isArray(item) ? { ring: item, fill: '' } : item;
        if (!piece || !Array.isArray(piece.ring) || piece.ring.length < 4) return;
        const feat = new ol.Feature({ geometry: new ol.geom.Polygon([piece.ring]) });
        feat.set('coverageFillColor', piece.fill || fillColor);
        coverageSource.addFeature(feat);
      });
      refreshCoverageDiagnostics();
    }

    function refreshCoverageDiagnostics() {
      if (!uncoveredSource) return;
      uncoveredSource.clear();
      if (overlapSource) overlapSource.clear();
      const showOverlap = !!(els.showOverlapAreas && els.showOverlapAreas.checked);
      if (overlapLayer) overlapLayer.setVisible(showOverlap);
      const showUncovered = !!(els.showUncoveredAreas && els.showUncoveredAreas.checked);
      const showCoverage = !!(els.showCoverageCircles && els.showCoverageCircles.checked);
      if ((!showUncovered && !showOverlap) || !showCoverage) return;
      if (!currentPolygonFeature || !currentPolygonFeature.getGeometry) return;
      const fieldGeom = currentPolygonFeature.getGeometry();
      if (!(fieldGeom instanceof ol.geom.Polygon)) return;
      const covPolys = coverageSource
        ? coverageSource.getFeatures()
          .map((f) => (f && f.getGeometry ? f.getGeometry() : null))
          .filter((g) => g instanceof ol.geom.Polygon)
        : [];
      if (!covPolys.length) return;
      const covExtents = covPolys.map((g) => g.getExtent());
      const ext = fieldGeom.getExtent();
      const minX = ext[0];
      const minY = ext[1];
      const maxX = ext[2];
      const maxY = ext[3];
      if (!(maxX > minX && maxY > minY)) return;
      // Keep runtime bounded for large waypoint counts.
      const gridN = covPolys.length > 220 ? 38 : (covPolys.length > 120 ? 52 : 72);
      const cellW = (maxX - minX) / gridN;
      const cellH = (maxY - minY) / gridN;
      if (!(cellW > 0 && cellH > 0)) return;
      for (let iy = 0; iy < gridN; iy++) {
        const cy = minY + (iy + 0.5) * cellH;
        for (let ix = 0; ix < gridN; ix++) {
          const cx = minX + (ix + 0.5) * cellW;
          const p = [cx, cy];
          if (!fieldGeom.intersectsCoordinate(p)) continue;
          let coverageCount = 0;
          for (let k = 0; k < covPolys.length; k++) {
            const e = covExtents[k];
            if (cx < e[0] || cx > e[2] || cy < e[1] || cy > e[3]) continue;
            if (covPolys[k].intersectsCoordinate(p)) {
              coverageCount++;
            }
          }
          const x0 = minX + ix * cellW;
          const y0 = minY + iy * cellH;
          const rect = [
            [x0, y0],
            [x0 + cellW, y0],
            [x0 + cellW, y0 + cellH],
            [x0, y0 + cellH],
            [x0, y0],
          ];
          if (coverageCount <= 0 && showUncovered) {
            const feat = new ol.Feature({ geometry: new ol.geom.Polygon([rect]) });
            feat.setStyle(new ol.style.Style({
              fill: new ol.style.Fill({ color: 'rgba(255,70,70,0.22)' }),
              stroke: new ol.style.Stroke({ color: 'rgba(255,70,70,0.30)', width: 0.6 }),
            }));
            uncoveredSource.addFeature(feat);
          } else if (coverageCount >= 2 && showOverlap && overlapSource) {
            const feat = new ol.Feature({ geometry: new ol.geom.Polygon([rect]) });
            feat.setStyle(new ol.style.Style({
              fill: new ol.style.Fill({ color: 'rgba(64,196,255,0.34)' }),
              stroke: new ol.style.Stroke({ color: 'rgba(64,196,255,0.45)', width: 0.6 }),
            }));
            overlapSource.addFeature(feat);
          }
        }
      }
    }

    

    const floatingUiStateKey = 'fieldEditorFloatingUiState_v1';

    function loadFloatingUiState() {
      try {
        const raw = window.localStorage.getItem(floatingUiStateKey);
        if (!raw) return {};
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : {};
      } catch (_) {
        return {};
      }
    }

    function saveFloatingUiState(partial) {
      const prev = loadFloatingUiState();
      const next = { ...prev, ...(partial || {}) };
      try {
        window.localStorage.setItem(floatingUiStateKey, JSON.stringify(next));
      } catch (_) {
        // ignore storage write errors
      }
    }

    const newFieldDraftKey = 'fieldEditorNewFieldDraftV1';

    function readNewFieldDraft() {
      try {
        const raw = window.localStorage.getItem(newFieldDraftKey);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (!parsed || parsed.creating !== true || !parsed.field || typeof parsed.field !== 'object') return null;
        return parsed;
      } catch (_) {
        return null;
      }
    }

    function clearNewFieldDraft() {
      if (newFieldDraftTimer) {
        clearTimeout(newFieldDraftTimer);
        newFieldDraftTimer = null;
      }
      try { window.localStorage.removeItem(newFieldDraftKey); } catch (_) {}
    }

    function writeNewFieldDraftNow() {
      if (!creatingNewField) return;
      const field = typeof formData === 'function' ? formData() : {};
      const view = (map && map.getView) ? map.getView() : null;
      const center = view && view.getCenter() ? ol.proj.toLonLat(view.getCenter()) : null;
      let findPlace = null;
      if (findPlaceSource && typeof findPlaceSource.getFeatures === 'function') {
        const feat = findPlaceSource.getFeatures()[0];
        const geom = feat && feat.getGeometry && feat.getGeometry();
        if (geom) {
          const ll = ol.proj.toLonLat(geom.getCoordinates());
          findPlace = { lon: ll[0], lat: ll[1], label: String(feat.get('findLabel') || '') };
        }
      }
      const assignEl = document.getElementById('planAssign');
      const payload = {
        creating: true,
        field: {
          name: field.name || '',
          number: field.number || '',
          feld_id: field.feld_id || '',
          grundstueck_id: field.grundstueck_id || planGisValue(),
          jg: field.jg || '',
          jg_label: String(els.jg && els.jg.value || ''),
          gemeinde: field.gemeinde || '',
          gemeinde_label: String(els.gemeinde && els.gemeinde.value || ''),
          std_flughoehe: field.std_flughoehe || '',
          flugzeit: field.flugzeit || '',
          distanz: field.distanz || '',
          wegpunkte: field.wegpunkte || '',
          startplatz: field.startplatz || '',
          flaechendaten: field.flaechendaten || '',
          flugweg: field.flugweg || '',
          mission_settings: field.mission_settings || {},
        },
        planPhase: planPhase,
        planHold: planHold,
        planWaypointsDone: planWaypointsDone,
        pathDiverged: !!pathDivergedFromGenerated,
        generatedPath: cloneTripletPath(generatedPathSnapshot),
        assign: assignEl ? String(assignEl.textContent || '') : '',
        findPlace: findPlace,
        view: center ? { center: center, zoom: view.getZoom() } : null,
      };
      try {
        window.localStorage.setItem(newFieldDraftKey, JSON.stringify(payload));
      } catch (_) {}
    }

    function scheduleNewFieldDraftSave() {
      if (!creatingNewField) return;
      if (newFieldDraftTimer) clearTimeout(newFieldDraftTimer);
      newFieldDraftTimer = setTimeout(() => {
        newFieldDraftTimer = null;
        writeNewFieldDraftNow();
      }, 200);
    }

    function applyStoredMissionSettings(ms) {
      if (!ms || typeof ms !== 'object') return;
      if (els.msDroneType && typeof ms.drone_type === 'string') els.msDroneType.value = ms.drone_type;
      if (els.msKmzParamsActive && typeof ms.kmz_params_active === 'boolean') els.msKmzParamsActive.checked = ms.kmz_params_active;
      if (els.msAltitude && Number.isFinite(Number(ms.altitude_m))) els.msAltitude.value = String(Number(ms.altitude_m));
      if (els.msFlightDirection && Number.isFinite(Number(ms.flight_direction_deg))) els.msFlightDirection.value = String(Number(ms.flight_direction_deg));
      if (els.msSpeed && Number.isFinite(Number(ms.speed_m_s))) els.msSpeed.value = String(Number(ms.speed_m_s));
      if (els.msOverlapPct && Number.isFinite(Number(ms.overlap_pct))) els.msOverlapPct.value = String(Number(ms.overlap_pct));
      if (els.msEdgeFactorPct && Number.isFinite(Number(ms.edge_factor_pct))) els.msEdgeFactorPct.value = String(Number(ms.edge_factor_pct));
      if (els.msStartSpeedActive && typeof ms.start_speed_active === 'boolean') els.msStartSpeedActive.checked = ms.start_speed_active;
      if (els.msStartSpeed && Number.isFinite(Number(ms.start_speed_m_s))) els.msStartSpeed.value = String(Number(ms.start_speed_m_s));
      if (els.msEndAction && typeof ms.end_action === 'string') els.msEndAction.value = ms.end_action;
      if (els.msHeadingMode && typeof ms.heading_mode === 'string') els.msHeadingMode.value = ms.heading_mode;
      restoreMissionPoi(ms);
      if (els.msWaypointType && typeof ms.waypoint_type === 'string') els.msWaypointType.value = ms.waypoint_type;
      if (els.msTurnDistance && Number.isFinite(Number(ms.standard_turn_distance_m))) els.msTurnDistance.value = String(Number(ms.standard_turn_distance_m));
      if (els.msSafeStartAltitude && Number.isFinite(Number(ms.safe_start_altitude_m))) els.msSafeStartAltitude.value = String(Number(ms.safe_start_altitude_m));
      if (els.msDirectToStart && typeof ms.direct_to_start_position === 'boolean') els.msDirectToStart.checked = ms.direct_to_start_position;
      if (els.msGimbalMode && typeof ms.gimbal_mode === 'string') els.msGimbalMode.value = ms.gimbal_mode;
      syncGimbalAngleFlag();
      if (els.msAltitudeMode && typeof ms.altitude_mode === 'string') els.msAltitudeMode.value = ms.altitude_mode;
      if (typeof ms.easy_mode_form === 'boolean') setPlanExpertMode(!ms.easy_mode_form);
      syncFlightDirectionControls('number');
      syncOverlapControls('number');
      syncEdgeFactorControls('number');
      syncPlanAltitudeLabel();
      syncPlanSpeedField();
      syncPlanDroneSelect();
    }

    function clampPlanSpeed(raw) {
      const n = Number(raw);
      if (!Number.isFinite(n)) {
        const easy = window.RkrkField && RkrkField.easyMissionDefaults && RkrkField.easyMissionDefaults();
        return easy ? Number(easy.speed_m_s) : 4.5;
      }
      const stepped = Math.round(n * 2) / 2;
      return Math.max(1, Math.min(15, stepped));
    }

    function syncPlanSpeedField() {
      const input = document.getElementById('planSpeed');
      if (!input || document.activeElement === input) return;
      const n = Number(els.msSpeed && els.msSpeed.value);
      const easySpeed = window.RkrkField && RkrkField.easyMissionDefaults ? Number(RkrkField.easyMissionDefaults().speed_m_s) : 4.5;
      input.value = clampPlanSpeed(Number.isFinite(n) && n > 0 ? n : easySpeed).toFixed(1);
    }

    function commitPlanSpeed(raw) {
      const value = clampPlanSpeed(raw);
      const input = document.getElementById('planSpeed');
      if (input) input.value = value.toFixed(1);
      if (els.msSpeed) els.msSpeed.value = value.toFixed(1);
      syncPlanStartSpeed();
      try {
        syncDerivedPathFigures(typeof workingFlightPath === 'function' ? workingFlightPath() : []);
      } catch (_) {}
      saveEditorLastSettings();
    }

    function syncPlanStartSpeed() {
      const modeBtn = document.getElementById('planModeToggle');
      const expert = !!(modeBtn && modeBtn.classList.contains('is-expert'));
      const active = els.msStartSpeedActive;
      const speed = els.msStartSpeed;
      if (!active || !speed) return;
      const flight = Number(els.msSpeed && els.msSpeed.value);
      const flightTxt = (Number.isFinite(flight) && flight > 0 ? flight : 4.5).toFixed(1);
      if (!expert) {
        active.checked = false;
        active.disabled = true;
        speed.disabled = true;
        speed.value = flightTxt;
        return;
      }
      active.disabled = false;
      const on = !!active.checked;
      speed.disabled = !on;
      if (!on && document.activeElement !== speed) speed.value = flightTxt;
    }

    function restoreNewFieldDraft(draft) {
      creatingNewField = true;
      const field = draft.field || {};
      pathDivergedFromGenerated = !!draft.pathDiverged;
      generatedPathSnapshot = Array.isArray(draft.generatedPath) ? draft.generatedPath : [];
      skipFlightRecomputeOnce = true;
      setForm({
        id: '',
        name: field.name || '',
        number: field.number || '',
        feld_id: field.feld_id || '',
        grundstueck_id: field.grundstueck_id || '',
        jg: field.jg || '',
        jg_name: field.jg_label || field.jg || '',
        gemeinde: field.gemeinde || '',
        gemeinde_name: field.gemeinde_label || field.gemeinde || '',
        std_flughoehe: field.std_flughoehe || '60',
        flugzeit: field.flugzeit || '0',
        distanz: field.distanz || '0',
        wegpunkte: field.wegpunkte || '',
        startplatz: field.startplatz || '',
        flaechendaten: field.flaechendaten || '',
        flugweg: field.flugweg || '',
        mission_settings: field.mission_settings || {},
      });
      applyStoredMissionSettings(field.mission_settings);
      missionHeightFromField = false;
      const restoredAlt = Number(els.msAltitude && els.msAltitude.value);
      const restoredDefault = missionAltitudeDefaultM(els.msDroneType && els.msDroneType.value);
      missionHeightUserSet = Number.isFinite(restoredAlt) && Math.round(restoredAlt) !== restoredDefault;
      syncPlanAltitudeLabel();
      if (draft.findPlace && findPlaceSource && Number.isFinite(Number(draft.findPlace.lon)) && Number.isFinite(Number(draft.findPlace.lat))) {
        findPlaceSource.clear();
        const marker = new ol.Feature({
          geometry: new ol.geom.Point(ol.proj.fromLonLat([Number(draft.findPlace.lon), Number(draft.findPlace.lat)])),
        });
        marker.set('findLabel', String(draft.findPlace.label || ''));
        findPlaceSource.addFeature(marker);
      }
      const hasPoly = parsePairs(field.flaechendaten || '').length >= 3;
      if (!hasPoly && draft.view && map && Array.isArray(draft.view.center) && draft.view.center.length >= 2) {
        map.getView().setCenter(ol.proj.fromLonLat([Number(draft.view.center[0]), Number(draft.view.center[1])]));
        if (Number.isFinite(Number(draft.view.zoom))) map.getView().setZoom(Number(draft.view.zoom));
      }
      planHold = !!draft.planHold;
      planWaypointsDone = !!draft.planWaypointsDone;
      setPlanPhase(draft.planPhase || 'outline');
      planHold = !!draft.planHold;
      planWaypointsDone = !!draft.planWaypointsDone;
      refreshPlanStepMarks();
      if (draft.assign) updatePlanAssign(draft.assign);
      setPlanOrigin('new', { openList: false });
      showStatus('Neues Feld nach dem Neuladen wiederhergestellt.');
    }

    function applyBoxVisibilityStateFromSetup() {
      document.querySelectorAll('.plan-admin-only').forEach((el) => {
        el.hidden = !isPlanAdmin();
      });
      const showMenu = !!(els.showBoxMapMenu && els.showBoxMapMenu.checked);
      const showDisplayRequested = !!(els.showBoxDisplayControl && els.showBoxDisplayControl.checked);
      const showDisplay = isPlanAdmin() && showDisplayRequested;
      const showContext = !!(els.showBoxContextHelp && els.showBoxContextHelp.checked);
      const showOverlayBySetup = !!(els.showBoxSwissOverlay && els.showBoxSwissOverlay.checked);
      const showMission = !!(els.showBoxMissionSettings && els.showBoxMissionSettings.checked);
      const showCharacteristics = !!(els.showBoxFieldCharacteristics && els.showBoxFieldCharacteristics.checked);
      const showForm = !!(els.showBoxFormDrawer && els.showBoxFormDrawer.checked);

      if (els.mapMenuBox) els.mapMenuBox.style.display = showMenu ? '' : 'none';
      const displayCollapsed = !!(els.displayControlBox && els.displayControlBox.classList.contains('is-collapsed'));
      if (els.displayControlBox) els.displayControlBox.style.display = (showDisplay && !displayCollapsed) ? '' : 'none';
      const displayDock = document.getElementById('displayControlDockBtn');
      if (displayDock) displayDock.hidden = !showDisplay || !displayCollapsed;
      const displayEye = document.getElementById('showDisplayMapBtn');
      if (displayEye) displayEye.hidden = !isPlanAdmin();
      const displaySetup = els.showBoxDisplayControl && els.showBoxDisplayControl.closest('label');
      if (displaySetup) displaySetup.hidden = !isPlanAdmin();
      if (els.mapQuickHelp) els.mapQuickHelp.style.display = showContext ? '' : 'none';
      const charCollapsed = !!(els.fieldCharacteristicsBox && els.fieldCharacteristicsBox.classList.contains('is-collapsed'));
      if (els.fieldCharacteristicsBox) els.fieldCharacteristicsBox.style.display = (showCharacteristics && !charCollapsed) ? '' : 'none';
      const charDock = document.getElementById('fieldCharDockBtn');
      if (charDock) {
        charDock.hidden = !showCharacteristics;
        charDock.classList.toggle('is-on', showCharacteristics && !charCollapsed);
      }
      if (els.formDrawerBox) els.formDrawerBox.style.display = showForm ? '' : 'none';

      const mapMode = String(els.baseMapMode && els.baseMapMode.value || 'swisstopo');
      const baseAllowsOverlays = (mapMode === 'swisstopo' || mapMode === 'swisstopo-grau' || mapMode === 'swissimage');
      const layerCollapsed = !!(els.swissOverlayBox && els.swissOverlayBox.classList.contains('is-collapsed'));
      const showOverlayFinal = showOverlayBySetup && !layerCollapsed;
      if (els.swissOverlayBox) els.swissOverlayBox.style.display = showOverlayFinal ? '' : 'none';
      const layerDock = document.getElementById('layerControlDockBtn');
      if (layerDock) {
        layerDock.hidden = !showOverlayBySetup;
        layerDock.classList.toggle('is-on', !!showOverlayFinal);
      }
      const overlayList = els.swissOverlayBox ? els.swissOverlayBox.querySelector('.swiss-overlay-list') : null;
      if (overlayList) overlayList.hidden = !baseAllowsOverlays;

      saveFloatingUiState({
        showMapMenu: showMenu,
        showDisplayControl: showDisplayRequested,
        contextHelpVisible: showContext,
        contextHelpVisibleV2: showContext,
        showSwissOverlay: showOverlayBySetup,
        showMissionSettings: showMission,
        showFieldCharacteristics: showCharacteristics,
        showWaypointData: !!(els.showBoxWaypointData && els.showBoxWaypointData.checked),
        showFormDrawer: showForm,
      });
      if (window.RkrkLayout && typeof window.RkrkLayout.applyBoxes === 'function') {
        if (!window.RkrkLayout.__planAdminWrapped) {
          const origApplyBoxes = window.RkrkLayout.applyBoxes;
          window.RkrkLayout.applyBoxes = function () {
            origApplyBoxes();
            if (!isPlanAdmin() && els.displayControlBox) els.displayControlBox.style.display = 'none';
            syncWaypointDebugBoxVisibility();
          };
          window.RkrkLayout.__planAdminWrapped = true;
        }
        window.RkrkLayout.applyBoxes();
      } else if (!isPlanAdmin() && els.displayControlBox) {
        els.displayControlBox.style.display = 'none';
      }
      syncWaypointDebugBoxVisibility();
    }

    function restoreFloatingUiState() {
      const state = loadFloatingUiState();
      if (els.showBoxMapMenu && typeof state.showMapMenu === 'boolean') {
        els.showBoxMapMenu.checked = state.showMapMenu;
      }
      if (els.showBoxDisplayControl && typeof state.showDisplayControl === 'boolean') {
        els.showBoxDisplayControl.checked = state.showDisplayControl;
      }
      if (els.showBoxContextHelp && typeof state.contextHelpVisibleV2 === 'boolean') {
        els.showBoxContextHelp.checked = state.contextHelpVisibleV2;
      }
      if (els.showBoxSwissOverlay && typeof state.showSwissOverlay === 'boolean') {
        els.showBoxSwissOverlay.checked = state.showSwissOverlay;
      }
      if (els.showBoxMissionSettings && typeof state.showMissionSettings === 'boolean') {
        els.showBoxMissionSettings.checked = state.showMissionSettings;
      }
      if (els.showBoxFieldCharacteristics && typeof state.showFieldCharacteristics === 'boolean') {
        els.showBoxFieldCharacteristics.checked = state.showFieldCharacteristics;
      }
      if (els.showBoxWaypointData && typeof state.showWaypointData === 'boolean') {
        els.showBoxWaypointData.checked = state.showWaypointData;
      }
      if (els.showBoxFormDrawer && typeof state.showFormDrawer === 'boolean') {
        els.showBoxFormDrawer.checked = state.showFormDrawer;
      }
      if (els.swissOverlayBox && typeof state.swissOverlayOpen === 'boolean') {
        els.swissOverlayBox.open = state.swissOverlayOpen;
      }
      if (els.formDrawerBox && typeof state.formDrawerOpen === 'boolean') {
        els.formDrawerBox.open = state.formDrawerOpen;
      }
      if (els.fieldCharacteristicsBox && typeof state.fieldCharacteristicsOpen === 'boolean') {
        els.fieldCharacteristicsBox.open = state.fieldCharacteristicsOpen;
      }
      if (els.wpDebugTableBox && typeof state.waypointDataOpen === 'boolean') {
        els.wpDebugTableBox.open = state.waypointDataOpen;
      }
      if (els.displayControlBox && typeof state.displayControlOpen === 'boolean') {
        if ('open' in els.displayControlBox) {
          els.displayControlBox.open = state.displayControlOpen;
        }
      }
      if (els.displayControlBox) {
        els.displayControlBox.classList.toggle('is-collapsed', state.displayControlCollapsed === true);
      }
      if (els.swissOverlayBox) {
        els.swissOverlayBox.classList.toggle('is-collapsed', state.layerControlCollapsed === true);
      }
      if (els.fieldCharacteristicsBox) {
        els.fieldCharacteristicsBox.classList.toggle('is-collapsed', state.fieldCharacteristicsCollapsed === true);
      }
      const quickSlotBox = document.getElementById('quickSlotBox');
      const fieldSearchBox = document.getElementById('fieldSearchBox');
      if (!state.menuFoldsCollapsedDefaultV2) {
        if (quickSlotBox) quickSlotBox.open = false;
        if (fieldSearchBox) fieldSearchBox.open = false;
        saveFloatingUiState({ menuFoldsCollapsedDefaultV2: true, quickSlotOpen: false, fieldSearchOpen: false });
      } else {
        if (quickSlotBox && typeof state.quickSlotOpen === 'boolean') quickSlotBox.open = state.quickSlotOpen;
        if (fieldSearchBox && typeof state.fieldSearchOpen === 'boolean') fieldSearchBox.open = state.fieldSearchOpen;
      }
      const showContextHelp = typeof state.contextHelpVisibleV2 === 'boolean' ? state.contextHelpVisibleV2 : false;
      if (els.showBoxContextHelp) els.showBoxContextHelp.checked = showContextHelp;
      applyBoxVisibilityStateFromSetup();
    }

    function captureBoxPosition(boxEl) {
      if (!boxEl) return null;
      const parent = boxEl.offsetParent || boxEl.parentElement;
      if (!parent || typeof parent.getBoundingClientRect !== 'function') return null;
      const pr = parent.getBoundingClientRect();
      const br = boxEl.getBoundingClientRect();
      return {
        left: Math.max(4, Math.round(br.left - pr.left)),
        top: Math.max(4, Math.round(br.top - pr.top)),
        width: Math.round(br.width),
      };
    }

    function persistUiStateNow() {
      const patch = {};
      if (els.showBoxMapMenu) patch.showMapMenu = !!els.showBoxMapMenu.checked;
      if (els.showBoxDisplayControl) patch.showDisplayControl = !!els.showBoxDisplayControl.checked;
      if (els.showBoxContextHelp) {
        patch.contextHelpVisible = !!els.showBoxContextHelp.checked;
        patch.contextHelpVisibleV2 = !!els.showBoxContextHelp.checked;
      }
      if (els.showBoxSwissOverlay) patch.showSwissOverlay = !!els.showBoxSwissOverlay.checked;
      if (els.showBoxMissionSettings) patch.showMissionSettings = !!els.showBoxMissionSettings.checked;
      if (els.showBoxFieldCharacteristics) patch.showFieldCharacteristics = !!els.showBoxFieldCharacteristics.checked;
      if (els.showBoxWaypointData) patch.showWaypointData = !!els.showBoxWaypointData.checked;
      if (els.showBoxFormDrawer) patch.showFormDrawer = !!els.showBoxFormDrawer.checked;
      if (els.swissOverlayBox) patch.swissOverlayOpen = !!els.swissOverlayBox.open;
      if (els.formDrawerBox) patch.formDrawerOpen = !!els.formDrawerBox.open;
      if (els.fieldCharacteristicsBox) patch.fieldCharacteristicsOpen = !!els.fieldCharacteristicsBox.open;
      if (els.wpDebugTableBox) patch.waypointDataOpen = !!els.wpDebugTableBox.open;
      if (els.displayControlBox && 'open' in els.displayControlBox) patch.displayControlOpen = !!els.displayControlBox.open;
      if (els.displayControlBox) patch.displayControlCollapsed = els.displayControlBox.classList.contains('is-collapsed');
      const quickSlotBox = document.getElementById('quickSlotBox');
      const fieldSearchBox = document.getElementById('fieldSearchBox');
      if (quickSlotBox) patch.quickSlotOpen = !!quickSlotBox.open;
      if (fieldSearchBox) patch.fieldSearchOpen = !!fieldSearchBox.open;
      if (els.baseMapMode) patch.lastBaseMapMode = String(els.baseMapMode.value || 'swisstopo');

      const mapMenuPos = captureBoxPosition(els.mapMenuBox);
      const swissOverlayPos = captureBoxPosition(els.swissOverlayBox);
      const displayControlPos = captureBoxPosition(els.displayControlBox);
      const fieldCharacteristicsPos = captureBoxPosition(els.fieldCharacteristicsBox);
      const formDrawerPos = captureBoxPosition(els.formDrawerBox);
      const waypointVisible = !!(els.wpDebugTableBox && !els.wpDebugTableBox.hidden && els.wpDebugTableBox.style.display !== 'none');
      const waypointDataPos = waypointVisible ? captureBoxPosition(els.wpDebugTableBox) : null;
      if (mapMenuPos) patch.mapMenuPos = mapMenuPos;
      if (swissOverlayPos) patch.swissOverlayPos = swissOverlayPos;
      if (displayControlPos && !(els.displayControlBox && els.displayControlBox.classList.contains('is-collapsed'))) patch.displayControlPos = displayControlPos;
      if (fieldCharacteristicsPos) patch.fieldCharacteristicsPos = fieldCharacteristicsPos;
      if (waypointDataPos && waypointDataPos.width > 20) patch.waypointDataPos = waypointDataPos;
      if (formDrawerPos) patch.formDrawerPos = formDrawerPos;

      saveFloatingUiState(patch);
    }

    function syncPlanFieldNameFromForm() {
      const planName = document.getElementById('planFieldName');
      if (planName && els.name && document.activeElement !== planName) {
        planName.value = String(els.name.value || '');
      }
      const planId = document.getElementById('planFeldId');
      if (planId && els.feld_id && document.activeElement !== planId) {
        planId.value = String(els.feld_id.value || '');
      }
      const planGisSave = document.getElementById('planSaveGis');
      const gisNow = planGisValue();
      if (planGisSave && document.activeElement !== planGisSave) planGisSave.value = gisNow;
      refreshPlanStepMarks();
    }

    function applyPlanSaveFields() {
      const planName = document.getElementById('planFieldName');
      const planId = document.getElementById('planFeldId');
      const planGisSave = document.getElementById('planSaveGis');
      if (planName && els.name) els.name.value = String(planName.value || '').trim();
      if (planId && els.feld_id) els.feld_id.value = String(planId.value || '').trim();
      if (planGisSave) {
        const gis = String(planGisSave.value || '').trim();
        const planGis = document.getElementById('planGis');
        const formGis = document.getElementById('grundstueck_id');
        if (planGis) planGis.value = gis;
        if (formGis) formGis.value = gis;
      }
      if (els.create_feld_id && planId) els.create_feld_id.value = String(planId.value || '').trim();
      if (els.create_name && planName) els.create_name.value = String(planName.value || '').trim();
    }

    function planGisValue() {
      const form = document.getElementById('grundstueck_id');
      const plan = document.getElementById('planGis');
      const fromForm = form ? String(form.value || '').trim() : '';
      const fromPlan = plan ? String(plan.value || '').trim() : '';
      return fromForm || fromPlan;
    }

    let planGisSuggestion = '';
    let planGisSuggestToken = 0;
    let planGisSuggestBusy = false;
    let planGisSuggestTimer = null;
    let planGisSuggestKey = '';

    function planPolygonReady() {
      try {
        return parsePairs(els.flaechendaten && els.flaechendaten.value).length >= 3;
      } catch (_) {
        return false;
      }
    }

    function syncPlanGisBlock() {
      const block = document.getElementById('planGisBlock');
      if (!block) return;
      block.hidden = !planPolygonReady() && planGisValue() === '';
    }

    function renderPlanGisSuggestion() {
      syncPlanGisBlock();
      const note = document.getElementById('planGisSuggest');
      const btn = document.getElementById('planGisApply');
      const missing = document.getElementById('planGisNote');
      if (!planPolygonReady()) {
        if (missing) missing.hidden = true;
        if (note) note.hidden = true;
        if (btn) btn.hidden = true;
        return;
      }
      const current = planGisValue();
      const sug = String(planGisSuggestion || '').trim();
      if (missing) missing.hidden = current !== '' || !!sug || planGisSuggestBusy;
      if (!note) return;
      if (planGisSuggestBusy && !sug) {
        note.hidden = false;
        note.textContent = 'CadastralWebMap wird gelesen …';
        if (btn) btn.hidden = true;
        return;
      }
      if (!sug) {
        note.hidden = true;
        if (btn) btn.hidden = true;
        return;
      }
      note.hidden = false;
      if (current === sug) {
        note.textContent = 'CadastralWebMap: ' + sug;
        if (btn) btn.hidden = true;
      } else {
        note.textContent = 'CadastralWebMap schlägt ' + sug + ' vor.';
        if (btn) btn.hidden = false;
      }
    }

    function syncPlanGisNote() {
      renderPlanGisSuggestion();
      refreshPlanStepMarks();
    }

    function pointInLonLatRing(x, y, ring) {
      let inside = false;
      for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const xi = ring[i][0];
        const yi = ring[i][1];
        const xj = ring[j][0];
        const yj = ring[j][1];
        if ((yi > y) !== (yj > y) && x < ((xj - xi) * (y - yi)) / ((yj - yi) || 1e-15) + xi) inside = !inside;
      }
      return inside;
    }

    function lonLatDistanceToRing(x, y, ring) {
      let best = Infinity;
      for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const ax = ring[j][0];
        const ay = ring[j][1];
        const bx = ring[i][0];
        const by = ring[i][1];
        const vx = bx - ax;
        const vy = by - ay;
        const c2 = vx * vx + vy * vy;
        let t = 0;
        if (c2 > 1e-18) t = Math.max(0, Math.min(1, ((x - ax) * vx + (y - ay) * vy) / c2));
        const dx = x - (ax + t * vx);
        const dy = y - (ay + t * vy);
        const d = Math.hypot(dx, dy);
        if (d < best) best = d;
      }
      return best;
    }

    function polygonSampleLonLat(pairs) {
      const pts = (pairs || []).filter((p) => p && p.length >= 2 && Number.isFinite(p[0]) && Number.isFinite(p[1]));
      if (pts.length >= 2) {
        const a = pts[0];
        const b = pts[pts.length - 1];
        if (Math.hypot(a[0] - b[0], a[1] - b[1]) < 1e-7) pts.pop();
      }
      if (pts.length < 3) return [];
      let area2 = 0;
      let cx = 0;
      let cy = 0;
      let minX = Infinity;
      let minY = Infinity;
      let maxX = -Infinity;
      let maxY = -Infinity;
      for (let i = 0, j = pts.length - 1; i < pts.length; j = i++) {
        const cross = pts[j][0] * pts[i][1] - pts[i][0] * pts[j][1];
        area2 += cross;
        cx += (pts[j][0] + pts[i][0]) * cross;
        cy += (pts[j][1] + pts[i][1]) * cross;
        minX = Math.min(minX, pts[i][0]);
        minY = Math.min(minY, pts[i][1]);
        maxX = Math.max(maxX, pts[i][0]);
        maxY = Math.max(maxY, pts[i][1]);
      }
      const inside = [];
      if (Math.abs(area2) > 1e-14) {
        const c = [cx / (3 * area2), cy / (3 * area2)];
        if (pointInLonLatRing(c[0], c[1], pts)) inside.push(c);
      }
      const cols = 5;
      const rows = 5;
      for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
          const x = minX + (maxX - minX) * ((c + 0.5) / cols);
          const y = minY + (maxY - minY) * ((r + 0.5) / rows);
          if (pointInLonLatRing(x, y, pts)) inside.push([x, y]);
        }
      }
      if (!inside.length) return [];
      const span = Math.min(maxX - minX, maxY - minY);
      const margin = span * 0.08;
      const ranked = inside
        .map((p) => ({ p, d: lonLatDistanceToRing(p[0], p[1], pts) }))
        .sort((a, b) => b.d - a.d);
      const deep = ranked.filter((item) => item.d >= margin);
      const pool = deep.length ? deep : ranked.slice(0, 1);
      const minSep = span * 0.16;
      const samples = [];
      pool.forEach((item) => {
        if (samples.length >= 9) return;
        if (samples.some((q) => Math.hypot(q[0] - item.p[0], q[1] - item.p[1]) < minSep)) return;
        samples.push(item.p);
      });
      return samples.length ? samples : [pool[0].p];
    }

    async function cadastralParcelNumber(lon, lat) {
      const pad = 0.00045;
      const url = 'https://api3.geo.admin.ch/rest/services/api/MapServer/identify'
        + '?geometryType=esriGeometryPoint'
        + '&geometry=' + encodeURIComponent(Number(lon).toFixed(6) + ',' + Number(lat).toFixed(6))
        + '&imageDisplay=400,400,96'
        + '&mapExtent=' + encodeURIComponent([lon - pad, lat - pad, lon + pad, lat + pad].map((n) => Number(n).toFixed(6)).join(','))
        + '&tolerance=1'
        + '&layers=' + encodeURIComponent('all:ch.kantone.cadastralwebmap-farbe')
        + '&returnGeometry=false&sr=4326&lang=de&limit=1';
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) return '';
      const data = await res.json();
      const attrs = data && data.results && data.results[0] && data.results[0].attributes;
      return attrs ? String(attrs.number || '').trim() : '';
    }

    async function suggestGisFromCadastre(opts) {
      const raw = String(els.flaechendaten && els.flaechendaten.value || '').trim();
      const samples = polygonSampleLonLat(parsePairs(raw));
      if (samples.length < 1) {
        planGisSuggestBusy = false;
        planGisSuggestion = '';
        planGisSuggestKey = '';
        renderPlanGisSuggestion();
        return;
      }
      if (raw === planGisSuggestKey && planGisSuggestion) {
        renderPlanGisSuggestion();
        return;
      }
      const token = ++planGisSuggestToken;
      planGisSuggestBusy = true;
      planGisSuggestion = '';
      renderPlanGisSuggestion();
      const counts = new Map();
      let centroidNumber = '';
      for (let i = 0; i < samples.length; i++) {
        if (token !== planGisSuggestToken) return;
        try {
          const num = await cadastralParcelNumber(samples[i][0], samples[i][1]);
          if (!num) continue;
          if (i === 0) centroidNumber = num;
          counts.set(num, (counts.get(num) || 0) + 1);
        } catch (_) {}
      }
      if (token !== planGisSuggestToken) return;
      let best = '';
      let bestN = 0;
      counts.forEach((n, num) => {
        if (n > bestN || (n === bestN && num === centroidNumber)) {
          best = num;
          bestN = n;
        }
      });
      planGisSuggestBusy = false;
      planGisSuggestion = best;
      planGisSuggestKey = raw;
      if (opts && opts.apply && best) {
        const el = document.getElementById('planGis');
        if (el) el.value = best;
        syncPlanGisNote();
        scheduleNewFieldDraftSave();
        showStatus('GIS-Nummer aus der CadastralWebMap: ' + best);
        return;
      }
      renderPlanGisSuggestion();
    }

    function scheduleGisSuggestion() {
      if (planGisSuggestTimer) clearTimeout(planGisSuggestTimer);
      planGisSuggestTimer = setTimeout(() => {
        planGisSuggestTimer = null;
        suggestGisFromCadastre().catch(() => {
          planGisSuggestBusy = false;
          renderPlanGisSuggestion();
        });
      }, 350);
    }

    function readDrawnFieldGis() {
      if (planGisSuggestTimer) {
        clearTimeout(planGisSuggestTimer);
        planGisSuggestTimer = null;
      }
      planGisSuggestKey = '';
      suggestGisFromCadastre({ apply: true }).catch(() => {
        planGisSuggestBusy = false;
        renderPlanGisSuggestion();
      });
    }

    function applyPlanGisSuggestion() {
      if (!planGisSuggestion) return;
      const el = document.getElementById('planGis');
      if (el) el.value = planGisSuggestion;
      syncPlanGisNote();
      scheduleNewFieldDraftSave();
    }

    function ensurePlanGis(focus) {
      if (!planPolygonReady()) {
        setPlanPhase('outline', { manual: true });
        showStatus('Die GIS-Nummer folgt erst aus der gezeichneten Fläche.', false);
        return false;
      }
      if (planGisValue()) {
        syncPlanGisNote();
        return true;
      }
      setPlanPhase('outline', { manual: true });
      syncPlanGisNote();
      const el = document.getElementById('planGis');
      if (focus && el) el.focus();
      showStatus('GIS-Nummer fehlt. Sie steht bei der Fläche, sobald die Umrandung gezeichnet ist.', false);
      return false;
    }

    function renderPlanExistingList() {
      const list = document.getElementById('planExistingList');
      const qEl = document.getElementById('planExistingSearch');
      if (!list) return;
      const q = String(qEl && qEl.value || '').trim().toLowerCase();
      list.innerHTML = '';
      const rows = (Array.isArray(allFields) ? allFields : []).filter((f) => !q || `${f.feld_id} ${f.name}`.toLowerCase().includes(q)).slice(0, 40);
      if (!rows.length) {
        const empty = document.createElement('div');
        empty.className = 'plan-note';
        empty.textContent = !editorHasCatalog()
          ? 'Dieser Drohnentyp hat keinen Feldkatalog.'
          : (allFields && allFields.length ? 'Kein Feld gefunden.' : 'Katalog wird geladen …');
        list.appendChild(empty);
        return;
      }
      rows.forEach((f) => {
        const row = document.createElement('button');
        row.type = 'button';
        row.className = 'field-item' + (String(f.id) === String(selectedId) ? ' active' : '');
        row.textContent = `${f.feld_id} - ${f.name}`;
        row.addEventListener('click', () => {
          loadField(f.id).catch((e) => showStatus(String(e.message || e), false));
        });
        list.appendChild(row);
      });
    }

    function setPlanOrigin(mode, opts) {
      const pick = document.getElementById('planExistingPick');
      const wasOpen = !!(pick && !pick.hidden);
      planOrigin = mode === 'new' ? 'new' : (mode === 'existing' ? 'existing' : '');
      const ex = document.getElementById('planOriginExisting');
      const nw = document.getElementById('planOriginNew');
      if (ex) ex.classList.toggle('is-on', planOrigin === 'existing');
      if (nw) nw.classList.toggle('is-on', planOrigin === 'new');
      const openList = opts && Object.prototype.hasOwnProperty.call(opts, 'openList')
        ? !!opts.openList
        : (planOrigin === 'existing' && wasOpen);
      if (pick) pick.hidden = !(planOrigin === 'existing' && openList);
      if (planOrigin === 'existing' && openList) renderPlanExistingList();
    }

    function syncPlanDroneSelect() {
      const planDrone = document.getElementById('planDroneType');
      if (!planDrone || !els.msDroneType) return;
      const current = String(els.msDroneType.value || 'Mavic 3T');
      planDrone.innerHTML = '';
      Array.from(els.msDroneType.options).forEach((src) => {
        const opt = document.createElement('option');
        opt.value = src.value;
        opt.textContent = src.textContent;
        planDrone.appendChild(opt);
      });
      if (!planDrone.options.length) {
        const opt = document.createElement('option');
        opt.value = 'Mavic 3T';
        opt.textContent = 'Mavic 3T';
        planDrone.appendChild(opt);
      }
      if (Array.from(planDrone.options).some((o) => o.value === current)) planDrone.value = current;
      else if (Array.from(planDrone.options).some((o) => o.value === 'Mavic 3T')) planDrone.value = 'Mavic 3T';
      const mapDroneBtn = document.getElementById('mapDroneBtn');
      if (mapDroneBtn) mapDroneBtn.textContent = current;
      refreshPlanStepMarks();
    }

    function renderMapDroneMenu() {
      const menu = document.getElementById('mapDroneMenu');
      if (!menu || !els.msDroneType) return;
      const current = String(els.msDroneType.value || '');
      menu.innerHTML = '';
      Array.from(els.msDroneType.options).forEach((src) => {
        const opt = document.createElement('button');
        opt.type = 'button';
        opt.textContent = src.textContent || src.value;
        if (src.value === current) opt.classList.add('is-on');
        opt.addEventListener('click', () => {
          menu.hidden = true;
          if (els.msDroneType.value === src.value) return;
          els.msDroneType.value = src.value;
          els.msDroneType.dispatchEvent(new Event('change', { bubbles: true }));
        });
        menu.appendChild(opt);
      });
    }

    let missionHeightUserSet = false;
    let missionHeightFromField = false;

    function missionAltitudeDefaultM(name) {
      const key = String(name || '').toLowerCase().replace(/[\s_-]/g, '');
      if (key.includes('debug')) return 60;
      if (key.includes('30t')) return 60;
      if (key.includes('3ta') || key.includes('m3ta')) return 80;
      if (key.includes('4td') || key.includes('m4td')) return 75;
      if (key.includes('4t') || key.includes('m4t')) return 75;
      return 60;
    }

    function clampPlanAltitude(next) {
      const n = Math.round(Number(next));
      if (!Number.isFinite(n)) return missionAltitudeDefaultM(els.msDroneType && els.msDroneType.value);
      return Math.max(5, Math.min(150, n));
    }

    function syncPlanAltitudeLabel() {
      const label = document.getElementById('planAltLabel');
      const alt = Number(els.msAltitude && els.msAltitude.value);
      const shown = Number.isFinite(alt) ? alt : missionAltitudeDefaultM(els.msDroneType && els.msDroneType.value);
      if (label) label.textContent = `${shown} m`;
      const input = document.getElementById('planMissionAlt');
      if (input && document.activeElement !== input) input.value = String(Math.round(shown));
      refreshPlanStepMarks();
    }

    function setPlanAltitude(next, opts) {
      if (!els.msAltitude) return;
      const value = clampPlanAltitude(next);
      if (!(opts && opts.programmatic)) missionHeightUserSet = true;
      els.msAltitude.value = String(value);
      if (els.std_flughoehe) writeControl('std_flughoehe', String(value));
      if (!(opts && opts.silent)) {
        els.msAltitude.dispatchEvent(new Event('input', { bubbles: true }));
        els.msAltitude.dispatchEvent(new Event('change', { bubbles: true }));
      }
      syncPlanAltitudeLabel();
    }

    function savedMissionAltitude(field) {
      const ms = field && field.mission_settings && Number(field.mission_settings.altitude_m);
      if (Number.isFinite(ms) && ms > 0) return Math.round(ms);
      const std = Number(String((field && field.std_flughoehe) || '').replace(/[^0-9.]/g, ''));
      if (Number.isFinite(std) && std > 0) return Math.round(std);
      return null;
    }

    function applyDroneMissionAltitudeDefault() {
      if (missionHeightUserSet || missionHeightFromField) return;
      const alt = missionAltitudeDefaultM(els.msDroneType && els.msDroneType.value);
      let hasRoute = false;
      try {
        hasRoute = parsePairs(els.flaechendaten && els.flaechendaten.value).length >= 3
          && parsePairs(els.startplatz && els.startplatz.value).length >= 1;
      } catch (_) { hasRoute = false; }
      setPlanAltitude(alt, { programmatic: true, silent: !hasRoute });
    }

    function noteLoadedMissionAltitude(field) {
      const saved = savedMissionAltitude(field);
      const stored = String((field && field.id) || '').trim() !== '' && saved != null;
      missionHeightFromField = stored;
      missionHeightUserSet = false;
      if (!stored || !els.msAltitude) {
        syncPlanAltitudeLabel();
        return;
      }
      els.msAltitude.value = String(clampPlanAltitude(saved));
      syncPlanAltitudeLabel();
    }

    function setDisplayControlCollapsed(collapsed) {
      if (!els.displayControlBox) return;
      els.displayControlBox.classList.toggle('is-collapsed', !!collapsed);
      applyBoxVisibilityStateFromSetup();
      saveFloatingUiState({ displayControlCollapsed: !!collapsed });
      const dock = document.getElementById('displayControlDockBtn');
      if (dock && !dock.hidden) dock.scrollIntoView({ block: 'nearest', inline: 'nearest' });
      if (!collapsed) placeDisplayControlInView();
    }

    function placeDisplayControlInView() {
      const box = els.displayControlBox;
      if (!box || box.classList.contains('is-collapsed')) return;
      const parent = box.offsetParent || box.parentElement;
      if (!parent) return;
      const pr = parent.getBoundingClientRect();
      const width = Math.max(180, Math.min(280, pr.width - 16));
      const heightCap = Math.max(160, pr.height - 70);
      box.style.width = `${width}px`;
      box.style.maxWidth = `${width}px`;
      box.style.maxHeight = `${heightCap}px`;
      box.style.left = `${Math.max(4, pr.width - width - 8)}px`;
      box.style.top = '52px';
      box.style.right = 'auto';
      box.style.zIndex = '24';
    }

    function initDisplayControlCollapse() {
      const box = els.displayControlBox;
      const title = box ? box.querySelector('.title') : null;
      const dock = document.getElementById('displayControlDockBtn');
      if (title) {
        title.addEventListener('dblclick', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          setDisplayControlCollapsed(true);
        });
      }
      const reopen = (ev) => {
        if (ev) {
          ev.preventDefault();
          ev.stopPropagation();
        }
        setDisplayControlCollapsed(false);
        placeDisplayControlInView();
      };
      if (dock) {
        dock.addEventListener('click', reopen);
        dock.addEventListener('dblclick', reopen);
      }
      const mapBtn = document.getElementById('showDisplayMapBtn');
      if (mapBtn) mapBtn.addEventListener('click', reopen);
      window.addEventListener('resize', () => placeDisplayControlInView());
    }

    let layerWindowUserOff = false;
    let layerWindowSyncing = false;

    function setLayerControlCollapsed(collapsed) {
      if (!els.swissOverlayBox) return;
      els.swissOverlayBox.classList.toggle('is-collapsed', !!collapsed);
      if (!collapsed) els.swissOverlayBox.open = true;
      applyBoxVisibilityStateFromSetup();
      saveFloatingUiState({ layerControlCollapsed: !!collapsed });
    }

    function activateLayerWindowForPlanStep() {
      if (layerWindowUserOff) return;
      const setupOn = !!(els.showBoxSwissOverlay && els.showBoxSwissOverlay.checked);
      const collapsed = !!(els.swissOverlayBox && els.swissOverlayBox.classList.contains('is-collapsed'));
      const open = !!(els.swissOverlayBox && els.swissOverlayBox.open);
      if (setupOn && !collapsed && open) return;
      layerWindowSyncing = true;
      try {
        if (els.showBoxSwissOverlay) els.showBoxSwissOverlay.checked = true;
        setLayerControlCollapsed(false);
      } finally {
        layerWindowSyncing = false;
      }
    }

    function initLayerControlCollapse() {
      const box = els.swissOverlayBox;
      const summary = box ? box.querySelector('summary') : null;
      const dock = document.getElementById('layerControlDockBtn');
      if (summary) {
        summary.addEventListener('click', (ev) => {
          if (ev.detail >= 2) ev.preventDefault();
        });
        summary.addEventListener('dblclick', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          layerWindowUserOff = true;
          setLayerControlCollapsed(true);
        });
      }
      if (dock) {
        dock.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          const collapsed = !!(els.swissOverlayBox && els.swissOverlayBox.classList.contains('is-collapsed'));
          layerWindowUserOff = !collapsed;
          setLayerControlCollapsed(!collapsed);
        });
      }
      const layerPath = document.getElementById('layerShowWaypointPlan');
      const layerField = document.getElementById('layerShowField');
      if (layerPath) {
        layerPath.addEventListener('change', () => {
          if (!els.showManualPath || els.showManualPath.checked === layerPath.checked) return;
          els.showManualPath.checked = layerPath.checked;
          els.showManualPath.dispatchEvent(new Event('change', { bubbles: true }));
        });
      }
      if (layerField) {
        layerField.addEventListener('change', () => {
          if (!els.showFieldPolygon || els.showFieldPolygon.checked === layerField.checked) return;
          els.showFieldPolygon.checked = layerField.checked;
          els.showFieldPolygon.dispatchEvent(new Event('change', { bubbles: true }));
        });
      }
      syncLayerPlanToggles();
    }

    function syncLayerPlanToggles() {
      const path = document.getElementById('layerShowWaypointPlan');
      const field = document.getElementById('layerShowField');
      if (path && els.showManualPath) path.checked = !!els.showManualPath.checked;
      if (field && els.showFieldPolygon) field.checked = !!els.showFieldPolygon.checked;
    }

    function setFieldCharacteristicsCollapsed(collapsed) {
      if (!els.fieldCharacteristicsBox) return;
      els.fieldCharacteristicsBox.classList.toggle('is-collapsed', !!collapsed);
      if (!collapsed) els.fieldCharacteristicsBox.open = true;
      applyBoxVisibilityStateFromSetup();
      saveFloatingUiState({ fieldCharacteristicsCollapsed: !!collapsed });
    }

    function initFieldCharacteristicsCollapse() {
      const box = els.fieldCharacteristicsBox;
      const summary = box ? box.querySelector('summary') : null;
      const dock = document.getElementById('fieldCharDockBtn');
      if (summary) {
        summary.addEventListener('click', (ev) => {
          if (ev.detail >= 2) ev.preventDefault();
        });
        summary.addEventListener('dblclick', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          setFieldCharacteristicsCollapsed(true);
        });
      }
      if (dock) {
        dock.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          const collapsed = !!(els.fieldCharacteristicsBox && els.fieldCharacteristicsBox.classList.contains('is-collapsed'));
          setFieldCharacteristicsCollapsed(!collapsed);
        });
      }
    }

    function initMenuFolds() {
      ['quickSlotBox', 'fieldSearchBox'].forEach((id) => {
        const box = document.getElementById(id);
        if (!box) return;
        box.addEventListener('toggle', () => {
          saveFloatingUiState({
            quickSlotOpen: !!(document.getElementById('quickSlotBox') && document.getElementById('quickSlotBox').open),
            fieldSearchOpen: !!(document.getElementById('fieldSearchBox') && document.getElementById('fieldSearchBox').open),
          });
        });
      });
    }

    function initPlanWizard() {
      const wizard = document.getElementById('planWizard');
      const quick = document.getElementById('quickSlotBox');
      if (wizard && quick && quick.parentElement) quick.parentElement.insertBefore(wizard, quick);
      mountPlanParams();
      const planName = document.getElementById('planFieldName');
      const modeBtn = document.getElementById('planModeToggle');
      const collapseBtn = document.getElementById('planWizardCollapse');
      const clickId = (id) => {
        const el = document.getElementById(id);
        if (el) el.click();
      };
      const bindPoi = (id, fn) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', fn);
      };
      bindPoi('wpPoiFromPark', () => poiFromCurrentPark('waypoint'));
      bindPoi('msPoiFromPark', () => poiFromCurrentPark('mission'));
      bindPoi('wpPoiOnMap', () => {
        poiPlaceTarget = 'waypoint';
        poiPlaceArmed = true;
        showStatus('POI auf der Karte setzen. Start und Parkplatz werden nicht verschoben.');
      });
      bindPoi('msPoiOnMap', () => {
        poiPlaceTarget = 'mission';
        poiPlaceArmed = true;
        showStatus('POI auf der Karte setzen. Start und Parkplatz werden nicht verschoben.');
      });
      bindPoi('planStepPoi', () => {
        poiPlaceTarget = 'mission';
        poiPlaceArmed = true;
        const note = document.getElementById('planPoiCoord');
        if (note) note.textContent = 'POI auf die Karte tippen.';
        showStatus('POI auf der Karte setzen. Start und Parkplatz werden nicht verschoben.');
      });
      bindPoi('planPoiClear', () => {
        setMissionPois([]);
        if (typeof refreshFlightPathLayers === 'function') refreshFlightPathLayers();
        showStatus('POI entfernt.');
      });
      bindPoi('debugTfBtn', () => {
        const input = document.getElementById('debugTfId');
        const dirEl = document.getElementById('debugTfDir');
        const dir = debugTfDirectory();
        if (!editorDebugOn() || !dir) return;
        if (input) input.value = nextFreeTfId();
        if (dirEl) dirEl.textContent = 'Zielordner: ' + dir;
        showStatus('TF-Testfeld ' + (input ? input.value : '') + ' in ' + dir + '. Es wurde nichts geschrieben.');
      });
      refreshDebugTfOffer();
      if (planName && els.name) {
        planName.addEventListener('input', () => {
          els.name.value = planName.value;
          els.name.dispatchEvent(new Event('input', { bubbles: true }));
          refreshPlanStepMarks();
        });
        syncPlanFieldNameFromForm();
      }
      const planFeldId = document.getElementById('planFeldId');
      if (planFeldId && els.feld_id) {
        planFeldId.addEventListener('input', () => {
          els.feld_id.value = planFeldId.value;
          if (els.create_feld_id) els.create_feld_id.value = planFeldId.value;
          scheduleFeldIdPlace(planFeldId.value);
        });
      }
      const planSaveGis = document.getElementById('planSaveGis');
      if (planSaveGis) {
        planSaveGis.addEventListener('input', () => {
          const gis = planSaveGis.value;
          const planGisEl = document.getElementById('planGis');
          const formGis = document.getElementById('grundstueck_id');
          if (planGisEl && document.activeElement !== planGisEl) planGisEl.value = gis;
          if (formGis) formGis.value = gis;
        });
      }
      const planGis = document.getElementById('planGis');
      if (planGis) {
        planGis.addEventListener('input', () => {
          syncPlanGisNote();
          scheduleNewFieldDraftSave();
        });
      }
      const planGisApply = document.getElementById('planGisApply');
      if (planGisApply) planGisApply.addEventListener('click', applyPlanGisSuggestion);
      const originExisting = document.getElementById('planOriginExisting');
      const originNew = document.getElementById('planOriginNew');
      const existingSearch = document.getElementById('planExistingSearch');
      if (originExisting) originExisting.addEventListener('click', () => {
        if (window.RKRK_KAT && !editorHasCatalog()) {
          allFields = [];
          renderList();
          startNewFieldFromScratch().catch((e) => showStatus(String(e.message || e), false));
          return;
        }
        const pick = document.getElementById('planExistingPick');
        const open = !(pick && !pick.hidden && planOrigin === 'existing');
        setPlanOrigin('existing', { openList: open });
        setPlanPhase('outline');
        if (open && existingSearch) existingSearch.focus();
      });
      if (originNew) originNew.addEventListener('click', () => {
        setPlanOrigin('new', { openList: false });
        startNewFieldFromScratch().catch((e) => showStatus(String(e.message || e), false));
      });
      if (existingSearch) existingSearch.addEventListener('input', renderPlanExistingList);
      syncPlanAltitudeLabel();
      syncPlanDroneSelect();
      const planDrone = document.getElementById('planDroneType');
      if (planDrone && els.msDroneType) {
        planDrone.addEventListener('change', () => {
          if (els.msDroneType.value === planDrone.value) return;
          els.msDroneType.value = planDrone.value;
          els.msDroneType.dispatchEvent(new Event('change', { bubbles: true }));
        });
        els.msDroneType.addEventListener('change', syncPlanDroneSelect);
        els.msDroneType.addEventListener('change', () => {
          onEditorDroneCatalogChange();
          applyDroneMissionAltitudeDefault();
        });
      }
      const mapDroneBtn = document.getElementById('mapDroneBtn');
      const mapDroneMenu = document.getElementById('mapDroneMenu');
      if (mapDroneBtn && mapDroneMenu) {
        mapDroneBtn.addEventListener('click', (ev) => {
          ev.stopPropagation();
          if (mapDroneMenu.hidden) renderMapDroneMenu();
          mapDroneMenu.hidden = !mapDroneMenu.hidden;
        });
        mapDroneMenu.addEventListener('click', (ev) => ev.stopPropagation());
        document.addEventListener('click', () => { mapDroneMenu.hidden = true; });
      }
      const minus = document.getElementById('planAltMinus');
      const plus = document.getElementById('planAltPlus');
      if (minus) minus.addEventListener('click', () => setPlanAltitude(Number(els.msAltitude && els.msAltitude.value) - 5));
      if (plus) plus.addEventListener('click', () => setPlanAltitude(Number(els.msAltitude && els.msAltitude.value) + 5));
      const mark = (id) => {
        document.querySelectorAll('.plan-step').forEach((btn) => btn.classList.remove('is-active'));
        const btn = document.getElementById(id);
        if (btn) btn.classList.add('is-active');
        const rail = document.getElementById('planStepRail');
        const labels = {
          planNewField: 'Schritt 1 · Fläche',
          planStepPlace: 'Schritt 1 · Fläche',
          planStepPolygon: 'Schritt 1 · Fläche',
          planStepStart: 'Schritt 2 · Start',
          planStepRoute: 'Schritt 3 · Flugpfad',
          planStepWp: '4 - Prüfen',
          planStepSave: 'Schritt 5 · Speichern',
          planCheck: 'Schritt 6 · Daten',
        };
        if (rail && labels[id]) rail.textContent = labels[id];
      };
      const cov = document.getElementById('planShowCoverage');
      const un = document.getElementById('planShowUncovered');
      [cov, un].forEach((el) => {
        if (!el) return;
        el.addEventListener('change', () => pushPlanCoverageToMap());
      });
      syncPlanCoverageFromMap();
      const routeHeights = document.getElementById('planShowRouteHeights');
      const fieldHeights = document.getElementById('planShowFieldHeights');
      const aglLimit = document.getElementById('planShowAglLimit');
      [routeHeights, fieldHeights, aglLimit].forEach((el) => {
        if (!el) return;
        el.addEventListener('change', () => pushPlanHeightsToMap());
      });
      syncPlanHeightsFromMap();
      const tabRoute = document.getElementById('planTabRoute');
      const tabPoint = document.getElementById('planTabPoint');
      if (tabRoute) tabRoute.addEventListener('click', () => showPlanParamTab('route'));
      if (tabPoint) tabPoint.addEventListener('click', () => {
        showPlanParamTab('waypoint');
        if (selectedWaypointIndex1 < 1) openWaypointDrawer(1);
        else renderPlanWpChips();
      });
      document.querySelectorAll('.plan-fold').forEach((fold) => {
        fold.addEventListener('toggle', () => {
          if (planFoldLock) return;
          const phase = fold.getAttribute('data-plan-phase');
          if (fold.open && phase && phase !== planPhase) setPlanPhase(phase, { manual: true });
          else if (!fold.open && phase === planPhase) {
            planFoldLock = true;
            fold.open = true;
            planFoldLock = false;
          }
        });
      });
      setPlanPhase(planPhase);
      const markEntry = (id) => {
        document.querySelectorAll('.plan-choice button').forEach((btn) => btn.classList.remove('is-on'));
        const btn = document.getElementById(id);
        if (btn) btn.classList.add('is-on');
      };
      const applyEntry = (mode) => {
        const base = generatedPathSnapshot.length >= 2
          ? cloneTripletPath(generatedPathSnapshot)
          : cloneTripletPath(currentComputedPath.length >= 2 ? currentComputedPath : currentManualPath);
        if (base.length < 3) {
          showStatus('Zuerst eine Flugroute erzeugen.', false);
          return;
        }
        markEntry(mode === 'last' ? 'planEntryLast' : (mode === 'secondLast' ? 'planEntrySecondLast' : 'planEntrySecond'));
        const home = base[0];
        let cut = 1;
        while (cut < base.length - 1 && distanceMetersLonLat(home, base[cut]) < 12) cut += 1;
        const approach = base.slice(0, cut);
        const survey = base.slice(cut);
        let ordered = survey.slice();
        if (mode === 'last') ordered = survey.slice().reverse();
        else if (mode === 'secondLast' && survey.length >= 2) {
          ordered = survey.slice(0, -1).reverse().concat([survey[survey.length - 1]]);
        }
        const next = approach.concat(ordered);
        if (mode === 'second') {
          currentManualPath = cloneTripletPath(base);
          currentComputedPath = cloneTripletPath(base);
          pathDivergedFromGenerated = false;
          if (els.flugweg) els.flugweg.value = formatTriplets(base);
          if (els.showManualPath) els.showManualPath.checked = true;
          if (els.showAlgorithmPath) els.showAlgorithmPath.checked = false;
          updatePathResetButton();
          refreshFlightPathLayers();
          syncDerivedPathFigures(base);
        } else {
          commitManualPathToForm(next, 'Anflug neu geordnet');
        }
        if (planPhase === 'optimize') armOptimizePathEdit();
        const rail = document.getElementById('planStepRail');
        if (rail) rail.textContent = 'Status: Route wird optimiert';
        if (mode === 'last') showStatus('Neuer Anflug: vom letzten Wegpunkt zurück zum ersten.');
        else if (mode === 'secondLast') showStatus('Neuer Anflug: Einstieg beim vorletzten Wegpunkt, Reihenfolge angepasst.');
        else showStatus('Anflug ab dem zweiten Wegpunkt, Reihenfolge der Berechnung.');
      };
      const entrySecond = document.getElementById('planEntrySecond');
      const entrySecondLast = document.getElementById('planEntrySecondLast');
      const entryLast = document.getElementById('planEntryLast');
      if (entrySecond) entrySecond.addEventListener('click', () => applyEntry('second'));
      if (entrySecondLast) entrySecondLast.addEventListener('click', () => applyEntry('secondLast'));
      if (entryLast) entryLast.addEventListener('click', () => applyEntry('last'));
      if (els.msDirectToStart) {
        els.msDirectToStart.addEventListener('change', () => {
          if (!els.msDirectToStart.checked) return;
          const base = generatedPathSnapshot.length >= 2
            ? generatedPathSnapshot
            : (currentComputedPath.length >= 2 ? currentComputedPath : currentManualPath);
          if (base.length >= 3) applyEntry('second');
        });
      }
      markEntry('planEntrySecond');
      const planSpeed = document.getElementById('planSpeed');
      const planSpeedMinus = document.getElementById('planSpeedMinus');
      const planSpeedPlus = document.getElementById('planSpeedPlus');
      if (planSpeedMinus) planSpeedMinus.addEventListener('click', () => commitPlanSpeed(Number(planSpeed && planSpeed.value) - 0.5));
      if (planSpeedPlus) planSpeedPlus.addEventListener('click', () => commitPlanSpeed(Number(planSpeed && planSpeed.value) + 0.5));
      if (planSpeed) planSpeed.addEventListener('change', () => commitPlanSpeed(planSpeed.value));
      if (els.msStartSpeedActive) els.msStartSpeedActive.addEventListener('change', () => syncPlanStartSpeed());
      syncPlanSpeedField();
      const planMissionAlt = document.getElementById('planMissionAlt');
      const planMissionAltMinus = document.getElementById('planMissionAltMinus');
      const planMissionAltPlus = document.getElementById('planMissionAltPlus');
      if (planMissionAltMinus) planMissionAltMinus.addEventListener('click', () => setPlanAltitude(Number(els.msAltitude && els.msAltitude.value) - 1));
      if (planMissionAltPlus) planMissionAltPlus.addEventListener('click', () => setPlanAltitude(Number(els.msAltitude && els.msAltitude.value) + 1));
      if (planMissionAlt) planMissionAlt.addEventListener('change', () => setPlanAltitude(planMissionAlt.value));
      if (els.msAltitude) {
        els.msAltitude.addEventListener('input', (ev) => {
          if (ev.isTrusted) missionHeightUserSet = true;
          syncPlanAltitudeLabel();
        });
      }
      syncPlanAltitudeLabel();
      const dirRange = document.getElementById('planDirRange');
      const dirLabel = document.getElementById('planDirLabel');
      if (dirRange) {
        dirRange.addEventListener('input', () => {
          const deg = Math.max(0, Math.min(179, Math.round(Number(dirRange.value) || 0)));
          if (dirLabel) dirLabel.textContent = `${deg}°`;
        });
        dirRange.addEventListener('change', () => {
          const deg = Math.max(0, Math.min(179, Math.round(Number(dirRange.value) || 0)));
          if (els.msFlightDirection) els.msFlightDirection.value = String(deg);
          syncFlightDirectionControls('number');
          resetOriginalPathOnNextRecompute = true;
          clickId('computePathBtn');
          const rail = document.getElementById('planStepRail');
          if (rail) rail.textContent = 'Status: Route wird optimiert';
        });
      }
      const optimizeBtn = document.getElementById('planOptimizeDir');
      if (optimizeBtn) optimizeBtn.addEventListener('click', () => {
        if (els.msFlightDirection) els.msFlightDirection.value = '-1';
        syncFlightDirectionControls('number');
        resetOriginalPathOnNextRecompute = true;
        clickId('computePathBtn');
        const rail = document.getElementById('planStepRail');
        if (rail) rail.textContent = 'Status: Route wird optimiert';
      });
      document.querySelectorAll('.plan-dir-step button').forEach((btn) => {
        btn.addEventListener('click', () => {
          const step = btn.id === 'planDirMinus5' ? -5 : btn.id === 'planDirMinus1' ? -1 : btn.id === 'planDirPlus5' ? 5 : 1;
          nudgePlanDirection(step);
        });
      });
      const optimizeDone = document.getElementById('planOptimizeDone');
      if (optimizeDone) optimizeDone.addEventListener('click', () => setPlanPhase('waypoints'));
      const wpDone = document.getElementById('planWpDone');
      if (wpDone) wpDone.addEventListener('click', () => setPlanPhase('place'));
      const saveStep = document.getElementById('planStepSave');
      if (saveStep) saveStep.addEventListener('click', () => {
        applyPlanSaveFields();
        setPlanPhase('place');
        if (!window.RKRK_KAT) {
          showStatus('Ohne ?KAT das Feld als KML oder JSON herunterladen.');
          return;
        }
        const hasExisting = String(els.id && els.id.value || '').trim() !== '';
        if (hasExisting) clickId('saveBtn');
        else openCreateFieldModal().catch((e) => showStatus(String(e.message || e), false));
      });
      const resetBtn = document.getElementById('planResetPath');
      if (resetBtn) resetBtn.addEventListener('click', () => resetPathToGenerated());
      const measureBtn = document.getElementById('planMeasureBtn');
      const measureClearBtn = document.getElementById('planMeasureClearBtn');
      if (measureBtn) measureBtn.addEventListener('click', () => setMeasureActive(!measureActive));
      if (measureClearBtn) measureClearBtn.addEventListener('click', () => clearMeasure());
      const polygonBtn = document.getElementById('planStepPolygon');
      if (polygonBtn) polygonBtn.addEventListener('click', () => { mark('planStepPolygon'); clickId('drawPolygonBtn'); });
      const startBtn = document.getElementById('planStepStart');
      if (startBtn) startBtn.addEventListener('click', () => { mark('planStepStart'); clickId('pickStartBtn'); });
      const parkBtn = document.getElementById('planStepPark');
      if (parkBtn) parkBtn.addEventListener('click', () => { mark('planStepStart'); startPickPark(); });
      const parkClear = document.getElementById('planParkClear');
      if (parkClear) parkClear.addEventListener('click', () => {
        writeControl('parkplatz', '');
        writeControl('create_parkplatz', '');
        refreshParkplatzNote();
        updateSaveButtonState();
      });
      const routeBtn = document.getElementById('planStepRoute');
      if (routeBtn) routeBtn.addEventListener('click', () => {
        mark('planStepRoute');
        const expert = modeBtn && modeBtn.classList.contains('is-expert');
        clickId(expert ? 'openMissionBtn' : 'computePathBtn');
      });
      const wpBtn = document.getElementById('planStepWp');
      if (wpBtn) wpBtn.addEventListener('click', () => {
        setPlanPhase('waypoints');
        openWaypointDrawer(selectedWaypointIndex1 || 1);
      });
      const saveBtn = document.getElementById('planSave');
      if (saveBtn) saveBtn.addEventListener('click', () => clickId('saveBtn'));
      const newFieldBtn = document.getElementById('planNewField');
      if (newFieldBtn) newFieldBtn.addEventListener('click', () => {
        mark('planNewField');
        setPlanOrigin('new', { openList: false });
        startNewFieldFromScratch().catch((e) => showStatus(String(e.message || e), false));
      });
      const placeBtn = document.getElementById('planStepPlace');
      if (placeBtn) placeBtn.addEventListener('click', () => {
        mark('planStepPlace');
        findPlaceOnMap().catch((e) => showStatus(String(e.message || e), false));
      });
      bindPlanConstraintLayers();
      const checkBtn = document.getElementById('planCheck');
      if (checkBtn) checkBtn.addEventListener('click', () => {
        mark('planCheck');
        if (planPhase === 'finish') {
          openCreateFieldModal().catch((e) => showStatus(String(e.message || e), false));
        } else {
          setPlanPhase('finish');
        }
      });
      const jsonBtn = document.getElementById('planJson');
      if (jsonBtn) jsonBtn.addEventListener('click', () => {
        if (!window.RKRK_KAT) {
          try { downloadLocalField('json'); } catch (e) { showStatus(String(e.message || e), false); }
          return;
        }
        clickId('exportUavJsonBtn');
      });
      const kmzBtn = document.getElementById('planKmz');
      if (kmzBtn) kmzBtn.addEventListener('click', () => {
        if (!window.RKRK_KAT) {
          try { downloadLocalField('kml'); } catch (e) { showStatus(String(e.message || e), false); }
          return;
        }
        clickId('exportDjiKmzBtn');
      });
      ['planPicRenew', 'createPicRenew'].forEach((id) => {
        const picRenew = document.getElementById(id);
        if (picRenew) picRenew.addEventListener('click', () => { renewDeskPictures().catch((e) => showStatus(String(e.message || e), false)); });
      });
      const picClose = document.getElementById('planPicClose');
      if (picClose) picClose.addEventListener('click', () => {
        if (!catalogPictureTarget()) {
          showStatus('Zuerst das Feld im Katalog speichern. Danach können Suchfeld- und Flugplanbild erzeugt werden.', false);
          return;
        }
        save('update').catch((e) => showStatus(String(e.message || e), false));
      });
      if (modeBtn) modeBtn.addEventListener('click', () => {
        setPlanExpertMode(!modeBtn.classList.contains('is-expert'));
        settleEasyMissionDefaults();
      });
      if (collapseBtn && wizard) collapseBtn.addEventListener('click', () => {
        const collapsed = wizard.classList.toggle('is-collapsed');
        collapseBtn.textContent = collapsed ? '⟩' : '⟨';
        collapseBtn.title = collapsed ? 'Menü öffnen' : 'Menü einklappen, Karte freigeben';
      });
    }

    function makeMapBoxDraggable(boxEl, handleSelector, storageKey) {
      if (!boxEl) return;
      const handle = handleSelector ? boxEl.querySelector(handleSelector) : boxEl;
      if (!handle) return;
      handle.style.cursor = 'move';

      function containerRect() {
        const parent = boxEl.offsetParent;
        if (parent && typeof parent.getBoundingClientRect === 'function') {
          return parent.getBoundingClientRect();
        }
        return { left: 0, top: 0 };
      }

      const state = loadFloatingUiState();
      const pos = state[storageKey];
      if (pos && Number.isFinite(pos.left) && Number.isFinite(pos.top)) {
        const parentRect = containerRect();
        let left = Number(pos.left);
        let top = Number(pos.top);
        // Backward compatibility: older saves used viewport coordinates.
        if (left > parentRect.width || top > parentRect.height) {
          left = left - parentRect.left;
          top = top - parentRect.top;
        }
        left = Math.max(4, left);
        top = Math.max(4, top);
        boxEl.style.left = `${left}px`;
        boxEl.style.top = `${top}px`;
        boxEl.style.right = 'auto';
        if (storageKey === 'formDrawerPos' || storageKey === 'waypointDataPos') {
          boxEl.style.bottom = 'auto';
        }
        if (storageKey === 'formDrawerPos') {
          boxEl.style.width = 'calc(100% - 28px)';
        }
      }

      let dragging = false;
      let pendingDrag = false;
      let startX = 0;
      let startY = 0;
      let startLeft = 0;
      let startTop = 0;
      let lastLeft = 0;
      let lastTop = 0;
      const dragThresholdPx = 4;

      handle.addEventListener('mousedown', (ev) => {
        if (ev.button !== 0) return;
        const t = ev.target;
        if (t && (t.closest('input,select,textarea,button,a,label'))) return;
        pendingDrag = true;
        dragging = false;
        startX = ev.clientX;
        startY = ev.clientY;
        const parentRect = containerRect();
        const rect = boxEl.getBoundingClientRect();
        startLeft = rect.left - parentRect.left;
        startTop = rect.top - parentRect.top;
        lastLeft = startLeft;
        lastTop = startTop;
        boxEl.style.right = 'auto';
        if (storageKey === 'formDrawerPos' || storageKey === 'waypointDataPos') boxEl.style.bottom = 'auto';
      });

      window.addEventListener('mousemove', (ev) => {
        if (!pendingDrag && !dragging) return;
        const dx = ev.clientX - startX;
        const dy = ev.clientY - startY;
        if (!dragging) {
          if (Math.hypot(dx, dy) < dragThresholdPx) return;
          dragging = true;
          pendingDrag = false;
        }
        const nextLeft = Math.max(4, startLeft + dx);
        const nextTop = Math.max(4, startTop + dy);
        lastLeft = nextLeft;
        lastTop = nextTop;
        boxEl.style.left = `${nextLeft}px`;
        boxEl.style.top = `${nextTop}px`;
        ev.preventDefault();
      });

      window.addEventListener('mouseup', () => {
        if (!dragging && !pendingDrag) return;
        const wasDragging = dragging;
        dragging = false;
        pendingDrag = false;
        if (!wasDragging) return;
        if (storageKey === 'formDrawerPos') {
          saveFloatingUiState({
            [storageKey]: {
              left: lastLeft,
              top: lastTop,
            },
          });
        } else {
          saveFloatingUiState({
            [storageKey]: {
              left: lastLeft,
              top: lastTop,
              width: boxEl.getBoundingClientRect().width,
            },
          });
        }
      });
    }

    function createGeoAdminOverlayLayer(layerId, opacity = 1) {
      return new ol.layer.Tile({
        source: new ol.source.TileWMS({
          url: 'https://wms.geo.admin.ch/',
          params: {
            LAYERS: layerId === 'ch.bazl.luftfahrthindernis'
              ? 'ch.bazl.luftfahrthindernis_line,ch.bazl.luftfahrthindernis_point,ch.bazl.luftfahrthindernis_poly'
              : layerId,
            FORMAT: 'image/png',
            TRANSPARENT: true,
          },
          crossOrigin: 'anonymous',
          attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
        }),
        opacity,
        zIndex: 40,
      });
    }

    async function fetchGeoAdminLayerCandidates(searchText) {
      const url = `https://api3.geo.admin.ch/rest/services/api/SearchServer?type=layers&searchText=${encodeURIComponent(searchText)}&limit=25`;
      const resp = await fetch(url, { cache: 'no-store' });
      if (!resp.ok) throw new Error(`SearchServer ${resp.status}`);
      const payload = await resp.json();
      const rows = Array.isArray(payload && payload.results) ? payload.results : [];
      return rows.map((r) => {
        const attrs = r && r.attrs && typeof r.attrs === 'object' ? r.attrs : {};
        const layerId = attrs.layerBodId || attrs.layer || attrs.id || r.layerBodId || '';
        const label = attrs.label || attrs.title || attrs.detail || '';
        return { layerId: String(layerId), label: String(label) };
      }).filter((c) => c.layerId);
    }

    async function searchGeoAdminLayerId(searchText, requiredTokens = []) {
      const candidates = await fetchGeoAdminLayerCandidates(searchText);
      if (!candidates.length) return '';
      const tokens = (Array.isArray(requiredTokens) ? requiredTokens : [])
        .map((t) => String(t || '').toLowerCase().trim())
        .filter(Boolean);
      if (!tokens.length) return candidates[0].layerId;
      const matched = candidates.find((c) => {
        const hay = `${c.layerId} ${c.label}`.toLowerCase();
        return tokens.every((token) => hay.includes(token));
      });
      return matched ? matched.layerId : candidates[0].layerId;
    }

    async function resolveOverlayLayerIds(key) {
      if (overlayResolvedLayerIds[key]) return overlayResolvedLayerIds[key];
      const def = overlayDefinitions[key];
      if (!def) return [];
      const fixed = Array.isArray(def.fixedLayerIds) ? def.fixedLayerIds.map((s) => String(s || '').trim()).filter(Boolean) : [];
      if (fixed.length) {
        overlayResolvedLayerIds[key] = fixed;
        return fixed;
      }
      const ids = [];
      for (const search of def.searches || []) {
        try {
          const layerId = await searchGeoAdminLayerId(search.text, search.tokens || []);
          if (layerId && !ids.includes(layerId)) ids.push(layerId);
        } catch (_) {
          // ignore per-search errors
        }
      }
      overlayResolvedLayerIds[key] = ids;
      return ids;
    }

    async function setOverlayEnabled(key, enabled, opacity = 1) {
      const def = overlayDefinitions[key];
      if (!def || !map) return;
      overlayApplyTokenByKey[key] = (overlayApplyTokenByKey[key] || 0) + 1;
      const applyToken = overlayApplyTokenByKey[key];
      const existing = overlayLayersByKey[key] || [];
      if (!enabled) {
        existing.forEach((layer) => map.removeLayer(layer));
        overlayLayersByKey[key] = [];
        if (key === 'dabs' && dabsCtl) dabsCtl.disable();
        if (key === 'uasZones') {
          if (uasCtl) uasCtl.disable();
          if (uasLabelCtl) uasLabelCtl.disable();
        }
        if (key === 'obstacles') {
          if (obstacleHeightCtl) obstacleHeightCtl.disable();
          if (obstInfoCtl) obstInfoCtl.disable();
          syncObstacleHeightsSwitch(false);
        }
        return;
      }
      if (def.kind === 'dabs') {
        if (!dabsCtl && window.RkrkSwissOverlays) {
          dabsCtl = window.RkrkSwissOverlays.attachDabs(map, {
            url: 'rkrk_dabs.php',
            statusEl: document.getElementById('overlayStatus'),
          });
        }
        if (!dabsCtl) return;
        if (existing.length) {
          dabsCtl.setOpacity(opacity);
          existing.forEach((layer) => layer.setOpacity(opacity));
          return;
        }
        const layer = dabsCtl.enable(opacity);
        overlayLayersByKey[key] = layer ? [layer] : [];
        const dabsSource = layer && typeof layer.getSource === 'function' ? layer.getSource() : null;
        if (dabsSource && !dabsSource.get('planCheckBound')) {
          dabsSource.set('planCheckBound', true);
          dabsSource.on('change', () => refreshPlanConstraintCheck());
        }
        return;
      }
      if (existing.length) {
        existing.forEach((layer) => layer.setOpacity(opacity));
        if (key === 'obstacles' && obstacleHeightCtl) obstacleHeightCtl.setOpacity(opacity);
        if (key === 'uasZones' && uasLabelCtl) uasLabelCtl.setOpacity(opacity);
        if (key === 'uasZones') {
          enableUasOverlay(opacity);
        }
        if (key === 'obstacles') {
          if (obstacleHeightCtl) obstacleHeightCtl.setOpacity(opacity);
          enableObstacleIdentify();
          syncObstacleHeightsSwitch(true);
        }
        return;
      }
      const layerIds = await resolveOverlayLayerIds(key);
      if (!layerIds.length) return;
      if (overlayApplyTokenByKey[key] !== applyToken) return;
      // Hard cleanup to avoid duplicate stacked layers.
      map.getLayers().getArray().forEach((ly) => {
        if (ly && typeof ly.get === 'function' && ly.get('overlayKey') === key) {
          map.removeLayer(ly);
        }
      });
      const layers = layerIds.map((layerId) => createGeoAdminOverlayLayer(layerId, opacity));
      layers.forEach((layer) => {
        if (typeof layer.set === 'function') layer.set('overlayKey', key);
      });
      if (key === 'obstacles') {
        layers.forEach((layer) => layer.setZIndex(90));
        if (!obstacleHeightCtl && window.RkrkSwissOverlays) {
          obstacleHeightCtl = window.RkrkSwissOverlays.attachObstacleHeights(map, { checkboxId: 'obstacleHeights' });
        }
        if (obstacleHeightCtl) obstacleHeightCtl.enable(opacity);
        enableObstacleIdentify();
        syncObstacleHeightsSwitch(true);
      }
      if (key === 'uasZones') {
        layers.forEach((layer) => layer.setZIndex(88));
        enableUasOverlay(opacity);
      }
      if (key === 'rpas') {
        layers.forEach((layer) => layer.setZIndex(85));
      }
      layers.forEach((layer) => map.addLayer(layer));
      overlayLayersByKey[key] = layers;
      raiseFieldDataLayers();
    }

    function raiseFieldDataLayers() {
      const ranked = [
        [coverageLayer, 110],
        [overlapLayer, 112],
        [uncoveredLayer, 112],
        [polygonLayer, 120],
        [wsVisualLayer, 130],
        [manualPathLayer, 140],
        [computedPathLayer, 140],
        [controlPointLayer, 150],
        [pathMidpointLayer, 160],
        [vertexLayer, 160],
        [midpointLayer, 160],
        [parkLayer, 165],
        [poiLayer, 168],
        [startLayer, 170],
        [findPlaceLayer, 180],
        [measureLayer, 190],
      ];
      ranked.forEach(([ly, z]) => {
        if (ly && typeof ly.setZIndex === 'function') ly.setZIndex(z);
      });
    }

    let flightZoneLayer = null;

    function flightZoneKmlUrl() {
      return '../rkrk_dokml.php?Reg=leer&Strt=off&Cntr=off&Area=off&Pfad=off&WW=off&FZ=on&GDE=off&JGG=off&KT=off&ORT=off&Poi=off&HSP=off&Label=off&GeLa=off&Sun=off&EG=off&SORA=off&WF=off&KA=S&Zoom=1&MDB=3T&GA=gast&WebAccess=test';
    }

    function ensureFlightZoneLayer() {
      if (flightZoneLayer || !map) return flightZoneLayer;
      flightZoneLayer = new ol.layer.Vector({
        source: new ol.source.Vector({
          url: flightZoneKmlUrl(),
          format: new ol.format.KML({ extractStyles: true, showPointNames: false }),
        }),
        visible: false,
        zIndex: 84,
      });
      flightZoneLayer.set('overlayKey', 'flightzone');
      const src = flightZoneLayer.getSource();
      src.on('change', () => {
        if (src.getState() === 'ready') refreshPlanConstraintCheck();
      });
      map.addLayer(flightZoneLayer);
      raiseFieldDataLayers();
      return flightZoneLayer;
    }

    function setFlightZoneVisible(on) {
      const layer = ensureFlightZoneLayer();
      if (layer) layer.setVisible(!!on);
    }

    function fieldRingGeometry() {
      const pairs = parsePairs(els.flaechendaten && els.flaechendaten.value);
      if (pairs.length < 3) return null;
      const ring = pairs.map((pair) => ol.proj.fromLonLat(pair));
      const first = ring[0];
      const last = ring[ring.length - 1];
      if (!last || first[0] !== last[0] || first[1] !== last[1]) ring.push(first.slice());
      return new ol.geom.Polygon([ring]);
    }

    function geometriesOverlap(fieldGeom, other) {
      if (!fieldGeom || !other) return false;
      let extentOk = false;
      try { extentOk = fieldGeom.intersectsExtent(other.getExtent()); } catch (_) { extentOk = false; }
      if (!extentOk) return false;
      const type = other.getType();
      if (type === 'Point') return fieldGeom.intersectsCoordinate(other.getCoordinates());
      if (type === 'LineString') return other.getCoordinates().some((coord) => fieldGeom.intersectsCoordinate(coord));
      if (type === 'Polygon') {
        const ring = other.getCoordinates()[0] || [];
        if (ring.some((coord) => fieldGeom.intersectsCoordinate(coord))) return true;
        const fieldRing = fieldGeom.getCoordinates()[0] || [];
        return fieldRing.some((coord) => other.intersectsCoordinate(coord));
      }
      if (type === 'MultiPolygon') return other.getPolygons().some((geom) => geometriesOverlap(fieldGeom, geom));
      if (type === 'MultiLineString') return other.getLineStrings().some((geom) => geometriesOverlap(fieldGeom, geom));
      if (type === 'GeometryCollection') return other.getGeometries().some((geom) => geometriesOverlap(fieldGeom, geom));
      return true;
    }

    function constraintHits(source, fieldGeom) {
      const names = [];
      if (!source || !fieldGeom || typeof source.forEachFeature !== 'function') return names;
      source.forEachFeature((feature) => {
        if (!geometriesOverlap(fieldGeom, feature.getGeometry())) return;
        const name = String(feature.get('name') || feature.get('Name') || '').trim();
        names.push(name || 'Treffer');
      });
      return names;
    }

    function refreshPlanConstraintCheck() {
      const note = document.getElementById('planLayerCheckNote');
      if (!note) return;
      const on = (id) => !!(document.getElementById(id) && document.getElementById(id).checked);
      const visual = [];
      if (on('planLayerRpas')) visual.push('RPAS');
      if (on('planLayerElectric')) visual.push('Elektrische Anlagen');
      if (on('planLayerObstacles')) visual.push('Luftfahrthindernisse');
      const fieldGeom = fieldRingGeometry();
      const lines = [];
      if (!fieldGeom && (visual.length || on('planLayerDabs') || on('planLayerFz'))) {
        note.hidden = false;
        note.textContent = 'Layer sind auf der Karte. Die Prüfung gegen die Umrandung folgt, sobald das Polygon geschlossen ist.';
        return;
      }
      if (visual.length) lines.push(visual.join(', ') + ' eingeschaltet. Auf der Karte gegen die Umrandung prüfen.');
      if (on('planLayerFz')) {
        const src = flightZoneLayer && flightZoneLayer.getSource();
        if (!src || src.getState() !== 'ready') lines.push('Flugzone wird geladen …');
        else {
          const hits = [...new Set(constraintHits(src, fieldGeom))];
          lines.push(hits.length ? ('Flugzone: ' + hits.slice(0, 3).join('; ')) : 'Flugzone: keine Überschneidung');
        }
      }
      if (on('planLayerDabs')) {
        const layers = overlayLayersByKey.dabs || [];
        const src = layers[0] && typeof layers[0].getSource === 'function' ? layers[0].getSource() : null;
        if (!src) lines.push('DABS wird geladen …');
        else {
          const hits = constraintHits(src, fieldGeom);
          lines.push(hits.length ? ('DABS: ' + hits.length + (hits.length === 1 ? ' Gebiet' : ' Gebiete')) : 'DABS: keine Überschneidung');
        }
      }
      note.hidden = lines.length === 0;
      note.textContent = lines.join(' ');
    }

    function bindPlanConstraintLayers() {
      const pairs = [
        ['planLayerRpas', 'layerRpas'],
        ['planLayerElectric', 'layerElectric'],
        ['planLayerObstacles', 'layerObstacles'],
        ['planLayerDabs', 'layerDabs'],
        ['planLayerSurface3d', 'layerSurface3d'],
      ];
      pairs.forEach(([planId, overlayId]) => {
        const plan = document.getElementById(planId);
        const overlay = document.getElementById(overlayId);
        if (!plan || !overlay) return;
        plan.checked = !!overlay.checked;
        plan.addEventListener('change', () => {
          if (overlay.checked !== plan.checked) {
            overlay.checked = plan.checked;
            overlay.dispatchEvent(new Event('change', { bubbles: true }));
          }
          refreshPlanConstraintCheck();
        });
        overlay.addEventListener('change', () => {
          plan.checked = !!overlay.checked;
          refreshPlanConstraintCheck();
        });
      });
      const fz = document.getElementById('planLayerFz');
      if (fz) fz.addEventListener('change', () => {
        setFlightZoneVisible(!!fz.checked);
        refreshPlanConstraintCheck();
      });
    }

    function normalizeOpacityPercent(value) {
      const n = Number(value);
      if (!Number.isFinite(n)) return 100;
      return Math.max(0, Math.min(100, Math.round(n)));
    }

    function opacityFromElement(inputEl) {
      const p = normalizeOpacityPercent(inputEl && inputEl.value);
      return p / 100;
    }

    function setOpacityLabel(labelEl, inputEl) {
      if (!labelEl || !inputEl) return;
      const p = normalizeOpacityPercent(inputEl.value);
      labelEl.textContent = `${p}%`;
    }

    function enableUasOverlay(opacity) {
      if (!window.RkrkSwissOverlays) return;
      if (!uasCtl && typeof window.RkrkSwissOverlays.attachUasIdentify === 'function') {
        uasCtl = window.RkrkSwissOverlays.attachUasIdentify(map, {});
      }
      if (uasCtl) uasCtl.enable();
      if (!uasLabelCtl && typeof window.RkrkSwissOverlays.attachUasLabels === 'function') {
        uasLabelCtl = window.RkrkSwissOverlays.attachUasLabels(map, {});
      }
      if (uasLabelCtl) uasLabelCtl.enable(opacity);
    }

    function enableObstacleIdentify() {
      if (!window.RkrkSwissOverlays || typeof window.RkrkSwissOverlays.attachObstacleIdentify !== 'function') return;
      if (!obstInfoCtl) obstInfoCtl = window.RkrkSwissOverlays.attachObstacleIdentify(map, {});
      if (obstInfoCtl) obstInfoCtl.enable();
    }

    function syncObstacleHeightsSwitch(obstaclesOn) {
      const on = !!obstaclesOn;
      if (els.obstacleHeights) els.obstacleHeights.disabled = !on;
      if (els.obstacleHeightsRow) els.obstacleHeightsRow.classList.toggle('is-disabled', !on);
    }

    function overlayOpacityStateFromInputs() {
      return {
        roadNames: normalizeOpacityPercent(els.layerRoadNamesOpacity && els.layerRoadNamesOpacity.value),
        electric: normalizeOpacityPercent(els.layerElectricOpacity && els.layerElectricOpacity.value),
        boundaries: normalizeOpacityPercent(els.layerBoundariesOpacity && els.layerBoundariesOpacity.value),
        jagdbann: normalizeOpacityPercent(els.layerJagdbannOpacity && els.layerJagdbannOpacity.value),
        rpas: normalizeOpacityPercent(els.layerRpasOpacity && els.layerRpasOpacity.value),
        uasZones: normalizeOpacityPercent(els.layerUasOpacity && els.layerUasOpacity.value),
        aulavWohngebiete: normalizeOpacityPercent(els.layerAulavOpacity && els.layerAulavOpacity.value),
        alti3dRelief: normalizeOpacityPercent(els.layerAlti3dOpacity && els.layerAlti3dOpacity.value),
        surface3dRelief: normalizeOpacityPercent(els.layerSurface3dOpacity && els.layerSurface3dOpacity.value),
        dabs: normalizeOpacityPercent(els.layerDabsOpacity && els.layerDabsOpacity.value),
        obstacles: normalizeOpacityPercent(els.layerObstaclesOpacity && els.layerObstaclesOpacity.value),
        grundbuch: normalizeOpacityPercent(els.layerGrundbuchOpacity && els.layerGrundbuchOpacity.value),
        cadastral: normalizeOpacityPercent(els.layerCadastralOpacity && els.layerCadastralOpacity.value),
        nature: normalizeOpacityPercent(els.layerNatureOpacity && els.layerNatureOpacity.value),
        cableways: normalizeOpacityPercent(els.layerCablewaysOpacity && els.layerCablewaysOpacity.value),
        wanderwege: normalizeOpacityPercent(els.layerWanderwegeOpacity && els.layerWanderwegeOpacity.value),
      };
    }

    function overlayToggleStateFromInputs() {
      return {
        roadNames: !!(els.layerRoadNames && els.layerRoadNames.checked),
        electric: !!(els.layerElectric && els.layerElectric.checked),
        boundaries: !!(els.layerBoundaries && els.layerBoundaries.checked),
        jagdbann: !!(els.layerJagdbann && els.layerJagdbann.checked),
        rpas: !!(els.layerRpas && els.layerRpas.checked),
        uasZones: !!(els.layerUas && els.layerUas.checked),
        aulavWohngebiete: !!(els.layerAulav && els.layerAulav.checked),
        alti3dRelief: !!(els.layerAlti3d && els.layerAlti3d.checked),
        surface3dRelief: !!(els.layerSurface3d && els.layerSurface3d.checked),
        dabs: !!(els.layerDabs && els.layerDabs.checked),
        obstacles: !!(els.layerObstacles && els.layerObstacles.checked),
        grundbuch: !!(els.layerGrundbuch && els.layerGrundbuch.checked),
        cadastral: !!(els.layerCadastral && els.layerCadastral.checked),
        nature: !!(els.layerNature && els.layerNature.checked),
        cableways: !!(els.layerCableways && els.layerCableways.checked),
        wanderwege: !!(els.layerWanderwege && els.layerWanderwege.checked),
        obstacleHeights: !!(els.obstacleHeights && els.obstacleHeights.checked),
      };
    }

    function restoreOverlayOpacityState() {
      const state = loadFloatingUiState();
      const t = state && state.overlayToggles && typeof state.overlayToggles === 'object' ? state.overlayToggles : {};
      if (els.layerRoadNames && typeof t.roadNames === 'boolean') els.layerRoadNames.checked = t.roadNames;
      if (els.layerElectric && typeof t.electric === 'boolean') els.layerElectric.checked = t.electric;
      if (els.layerBoundaries && typeof t.boundaries === 'boolean') els.layerBoundaries.checked = t.boundaries;
      if (els.layerJagdbann && typeof t.jagdbann === 'boolean') els.layerJagdbann.checked = t.jagdbann;
      if (els.layerRpas && typeof t.rpas === 'boolean') els.layerRpas.checked = t.rpas;
      if (els.layerUas) {
        const uasOn = (typeof t.uasZones === 'boolean') ? t.uasZones : t.uas;
        if (typeof uasOn === 'boolean') els.layerUas.checked = uasOn;
      }
      if (els.layerAulav && typeof t.aulavWohngebiete === 'boolean') els.layerAulav.checked = t.aulavWohngebiete;
      if (els.layerAlti3d && typeof t.alti3dRelief === 'boolean') els.layerAlti3d.checked = t.alti3dRelief;
      if (els.layerSurface3d && typeof t.surface3dRelief === 'boolean') els.layerSurface3d.checked = t.surface3dRelief;
      if (els.layerDabs && typeof t.dabs === 'boolean') els.layerDabs.checked = t.dabs;
      if (els.layerObstacles && typeof t.obstacles === 'boolean') els.layerObstacles.checked = t.obstacles;
      if (els.obstacleHeights && typeof t.obstacleHeights === 'boolean') els.obstacleHeights.checked = t.obstacleHeights;
      if (els.layerGrundbuch && typeof t.grundbuch === 'boolean') els.layerGrundbuch.checked = t.grundbuch;
      if (els.layerCadastral && typeof t.cadastral === 'boolean') els.layerCadastral.checked = t.cadastral;
      if (els.layerNature && typeof t.nature === 'boolean') els.layerNature.checked = t.nature;
      if (els.layerCableways && typeof t.cableways === 'boolean') els.layerCableways.checked = t.cableways;
      if (els.layerWanderwege && typeof t.wanderwege === 'boolean') els.layerWanderwege.checked = t.wanderwege;
      const o = state && state.overlayOpacity && typeof state.overlayOpacity === 'object' ? state.overlayOpacity : {};
      if (els.layerRoadNamesOpacity && Number.isFinite(Number(o.roadNames))) els.layerRoadNamesOpacity.value = String(normalizeOpacityPercent(o.roadNames));
      if (els.layerElectricOpacity && Number.isFinite(Number(o.electric))) els.layerElectricOpacity.value = String(normalizeOpacityPercent(o.electric));
      if (els.layerBoundariesOpacity && Number.isFinite(Number(o.boundaries))) els.layerBoundariesOpacity.value = String(normalizeOpacityPercent(o.boundaries));
      if (els.layerJagdbannOpacity && Number.isFinite(Number(o.jagdbann))) els.layerJagdbannOpacity.value = String(normalizeOpacityPercent(o.jagdbann));
      if (els.layerRpasOpacity && Number.isFinite(Number(o.rpas))) els.layerRpasOpacity.value = String(normalizeOpacityPercent(o.rpas));
      if (els.layerUasOpacity) {
        const uasOp = Number.isFinite(Number(o.uasZones)) ? o.uasZones : o.uas;
        if (Number.isFinite(Number(uasOp))) els.layerUasOpacity.value = String(normalizeOpacityPercent(uasOp));
      }
      if (els.layerAulavOpacity && Number.isFinite(Number(o.aulavWohngebiete))) els.layerAulavOpacity.value = String(normalizeOpacityPercent(o.aulavWohngebiete));
      if (els.layerAlti3dOpacity && Number.isFinite(Number(o.alti3dRelief))) els.layerAlti3dOpacity.value = String(normalizeOpacityPercent(o.alti3dRelief));
      if (els.layerSurface3dOpacity && Number.isFinite(Number(o.surface3dRelief))) els.layerSurface3dOpacity.value = String(normalizeOpacityPercent(o.surface3dRelief));
      if (els.layerDabsOpacity && Number.isFinite(Number(o.dabs))) els.layerDabsOpacity.value = String(normalizeOpacityPercent(o.dabs));
      if (els.layerObstaclesOpacity && Number.isFinite(Number(o.obstacles))) els.layerObstaclesOpacity.value = String(normalizeOpacityPercent(o.obstacles));
      if (els.layerGrundbuchOpacity && Number.isFinite(Number(o.grundbuch))) els.layerGrundbuchOpacity.value = String(normalizeOpacityPercent(o.grundbuch));
      if (els.layerCadastralOpacity && Number.isFinite(Number(o.cadastral))) els.layerCadastralOpacity.value = String(normalizeOpacityPercent(o.cadastral));
      if (els.layerNatureOpacity && Number.isFinite(Number(o.nature))) els.layerNatureOpacity.value = String(normalizeOpacityPercent(o.nature));
      if (els.layerCablewaysOpacity && Number.isFinite(Number(o.cableways))) els.layerCablewaysOpacity.value = String(normalizeOpacityPercent(o.cableways));
      if (els.layerWanderwegeOpacity && Number.isFinite(Number(o.wanderwege))) els.layerWanderwegeOpacity.value = String(normalizeOpacityPercent(o.wanderwege));

      setOpacityLabel(els.layerRoadNamesOpacityLabel, els.layerRoadNamesOpacity);
      setOpacityLabel(els.layerElectricOpacityLabel, els.layerElectricOpacity);
      setOpacityLabel(els.layerBoundariesOpacityLabel, els.layerBoundariesOpacity);
      setOpacityLabel(els.layerJagdbannOpacityLabel, els.layerJagdbannOpacity);
      setOpacityLabel(els.layerRpasOpacityLabel, els.layerRpasOpacity);
      setOpacityLabel(els.layerUasOpacityLabel, els.layerUasOpacity);
      setOpacityLabel(els.layerAulavOpacityLabel, els.layerAulavOpacity);
      setOpacityLabel(els.layerAlti3dOpacityLabel, els.layerAlti3dOpacity);
      setOpacityLabel(els.layerSurface3dOpacityLabel, els.layerSurface3dOpacity);
      setOpacityLabel(els.layerDabsOpacityLabel, els.layerDabsOpacity);
      setOpacityLabel(els.layerObstaclesOpacityLabel, els.layerObstaclesOpacity);
      setOpacityLabel(els.layerGrundbuchOpacityLabel, els.layerGrundbuchOpacity);
      setOpacityLabel(els.layerCadastralOpacityLabel, els.layerCadastralOpacity);
      setOpacityLabel(els.layerNatureOpacityLabel, els.layerNatureOpacity);
      setOpacityLabel(els.layerCablewaysOpacityLabel, els.layerCablewaysOpacity);
      setOpacityLabel(els.layerWanderwegeOpacityLabel, els.layerWanderwegeOpacity);
    }

    function saveEditorLastSettings() {
      saveFloatingUiState({
        lastBaseMapMode: String(els.baseMapMode && els.baseMapMode.value || 'swisstopo'),
        lastShowManualPath: !!(els.showManualPath && els.showManualPath.checked),
        lastShowAlgorithmPath: !!(els.showAlgorithmPath && els.showAlgorithmPath.checked),
        lastDisplayControls: {
          showFieldPolygon: !!(els.showFieldPolygon ? els.showFieldPolygon.checked : true),
          showCoverageCircles: !!(els.showCoverageCircles && els.showCoverageCircles.checked),
          coverageWindowMode: (function () {
            const picked = document.querySelector('input[name="coverageWindowMode"]:checked');
            return picked && picked.value === 'W' ? 'W' : 'S';
          })(),
          showOverlapAreas: !!(els.showOverlapAreas && els.showOverlapAreas.checked),
          showUncoveredAreas: !!(els.showUncoveredAreas && els.showUncoveredAreas.checked),
          forceCoverageFix: !!(els.forceCoverageFix && els.forceCoverageFix.checked),
          showBasePatterns: !!(els.showBasePatterns && els.showBasePatterns.checked),
          showSectionDirections: !!(els.showSectionDirections && els.showSectionDirections.checked),
          showCorrectionPoints: !!(els.showCorrectionPoints && els.showCorrectionPoints.checked),
          showTurnCurves: !!(els.showTurnCurves && els.showTurnCurves.checked),
          mapLabelScale: window.RkrkField && typeof RkrkField.mapLabelScale === 'function' ? RkrkField.mapLabelScale() : 1,
          showControlPoints: !!(els.showControlPoints && els.showControlPoints.checked),
          showAglLimit: !!(els.showAglLimit && els.showAglLimit.checked),
          showFieldControlPoints: !!(els.showFieldControlPoints && els.showFieldControlPoints.checked),
          useFieldReferenceAgl: !!(els.useFieldReferenceAgl && els.useFieldReferenceAgl.checked),
          computeMode: String(els.computeMode && els.computeMode.value || 'simple'),
        },
        lastMissionSettings: {
          drone_type: String(els.msDroneType && els.msDroneType.value || 'Mavic 3T'),
          kmz_params_active: !!(els.msKmzParamsActive && els.msKmzParamsActive.checked),
          altitude_m: Number(els.msAltitude && els.msAltitude.value || 60),
          flight_direction_deg: Number(els.msFlightDirection && els.msFlightDirection.value || -1),
          speed_m_s: Number(els.msSpeed && els.msSpeed.value || 5),
          overlap_pct: Number(els.msOverlapPct && els.msOverlapPct.value || 15),
          edge_factor_pct: Number(els.msEdgeFactorPct && els.msEdgeFactorPct.value || 40),
          start_speed_active: !!(els.msStartSpeedActive && els.msStartSpeedActive.checked),
          start_speed_m_s: Number(els.msStartSpeed && els.msStartSpeed.value || 5),
          end_action: String(els.msEndAction && els.msEndAction.value || 'Aufgabe beenden'),
          heading_mode: String(els.msHeadingMode && els.msHeadingMode.value || 'Manuell'),
          waypoint_type: String(els.msWaypointType && els.msWaypointType.value || 'Koordinierte Drehung, Ueberspringt den Wegpunkt'),
          standard_turn_distance_m: (function () {
            const n = Number(els.msTurnDistance && els.msTurnDistance.value);
            return Number.isFinite(n) ? n : 5;
          })(),
          safe_start_altitude_m: Number(els.msSafeStartAltitude && els.msSafeStartAltitude.value || 20),
          direct_to_start_position: !!(els.msDirectToStart && els.msDirectToStart.checked),
          gimbal_mode: String(els.msGimbalMode && els.msGimbalMode.value || 'Manuell'),
          altitude_mode: String(els.msAltitudeMode && els.msAltitudeMode.value || 'ALT (Relativ zum Startpunkt)'),
        },
      });
      scheduleNewFieldDraftSave();
    }

    function restoreEditorLastSettings() {
      const state = loadFloatingUiState();
      if (Array.isArray(state.quickFieldSlots)) {
        quickFieldSlots = new Array(QUICK_SLOT_COUNT).fill('').map((_, i) => String(state.quickFieldSlots[i] || ''));
      }
      if (els.baseMapMode && typeof state.lastBaseMapMode === 'string' && state.lastBaseMapMode) {
        els.baseMapMode.value = state.lastBaseMapMode;
      }
      if (els.showManualPath && typeof state.lastShowManualPath === 'boolean') {
        els.showManualPath.checked = state.lastShowManualPath;
      }
      if (els.showAlgorithmPath && typeof state.lastShowAlgorithmPath === 'boolean') {
        els.showAlgorithmPath.checked = state.lastShowAlgorithmPath;
      }
      const dc = state && state.lastDisplayControls && typeof state.lastDisplayControls === 'object'
        ? state.lastDisplayControls
        : null;
      if (dc) {
        if (els.showFieldPolygon && typeof dc.showFieldPolygon === 'boolean') els.showFieldPolygon.checked = dc.showFieldPolygon;
        if (els.showCoverageCircles && typeof dc.showCoverageCircles === 'boolean') els.showCoverageCircles.checked = dc.showCoverageCircles;
        applyCoverageWindowChoice(dc.coverageWindowMode);
        if (els.showOverlapAreas) els.showOverlapAreas.checked = typeof dc.showOverlapAreas === 'boolean' ? dc.showOverlapAreas : false;
        if (els.showUncoveredAreas && typeof dc.showUncoveredAreas === 'boolean') els.showUncoveredAreas.checked = dc.showUncoveredAreas;
        if (els.forceCoverageFix && typeof dc.forceCoverageFix === 'boolean') els.forceCoverageFix.checked = dc.forceCoverageFix;
        if (els.showBasePatterns && typeof dc.showBasePatterns === 'boolean') els.showBasePatterns.checked = dc.showBasePatterns;
        if (els.showSectionDirections && typeof dc.showSectionDirections === 'boolean') els.showSectionDirections.checked = dc.showSectionDirections;
        if (els.showCorrectionPoints && typeof dc.showCorrectionPoints === 'boolean') els.showCorrectionPoints.checked = dc.showCorrectionPoints;
        if (els.showTurnCurves && typeof dc.showTurnCurves === 'boolean') els.showTurnCurves.checked = dc.showTurnCurves;
        if (window.RkrkField && typeof RkrkField.setMapLabelScale === 'function' && Number.isFinite(Number(dc.mapLabelScale))) {
          RkrkField.setMapLabelScale(dc.mapLabelScale);
        }
        if (els.showControlPoints && typeof dc.showControlPoints === 'boolean') els.showControlPoints.checked = dc.showControlPoints;
        if (els.showAglLimit && typeof dc.showAglLimit === 'boolean') els.showAglLimit.checked = dc.showAglLimit;
        if (els.showFieldControlPoints && typeof dc.showFieldControlPoints === 'boolean') els.showFieldControlPoints.checked = dc.showFieldControlPoints;
        if (els.useFieldReferenceAgl && typeof dc.useFieldReferenceAgl === 'boolean') els.useFieldReferenceAgl.checked = dc.useFieldReferenceAgl;
        if (els.computeMode && typeof dc.computeMode === 'string') els.computeMode.value = (dc.computeMode === 'full') ? 'full' : 'simple';
      }
      ensurePlanCoverageDefault(state);
      ensureOverlapDefaultOff(state);
      ensurePlanHeightDefault(state);
      ensureRouteAglDefaultOff(state);
      ensureDisplayDefaultsV3(state);
      syncPlanCoverageFromMap();
      syncPlanHeightsFromMap();
      const ms = state && state.lastMissionSettings && typeof state.lastMissionSettings === 'object'
        ? state.lastMissionSettings
        : null;
      if (!ms) return;
      if (els.msDroneType && typeof ms.drone_type === 'string') els.msDroneType.value = ms.drone_type;
      if (els.msKmzParamsActive && typeof ms.kmz_params_active === 'boolean') els.msKmzParamsActive.checked = ms.kmz_params_active;
      if (els.msAltitude && Number.isFinite(Number(ms.altitude_m))) els.msAltitude.value = String(Number(ms.altitude_m));
      if (els.msFlightDirection && Number.isFinite(Number(ms.flight_direction_deg))) els.msFlightDirection.value = String(Number(ms.flight_direction_deg));
      if (els.msSpeed && Number.isFinite(Number(ms.speed_m_s))) els.msSpeed.value = String(Number(ms.speed_m_s));
      if (els.msOverlapPct && Number.isFinite(Number(ms.overlap_pct))) els.msOverlapPct.value = String(Number(ms.overlap_pct));
      if (els.msEdgeFactorPct && Number.isFinite(Number(ms.edge_factor_pct))) els.msEdgeFactorPct.value = String(Number(ms.edge_factor_pct));
      if (els.msStartSpeedActive && typeof ms.start_speed_active === 'boolean') els.msStartSpeedActive.checked = ms.start_speed_active;
      if (els.msStartSpeed && Number.isFinite(Number(ms.start_speed_m_s))) els.msStartSpeed.value = String(Number(ms.start_speed_m_s));
      if (els.msEndAction && typeof ms.end_action === 'string') els.msEndAction.value = ms.end_action;
      if (els.msHeadingMode && typeof ms.heading_mode === 'string') els.msHeadingMode.value = ms.heading_mode;
      if (els.msWaypointType && typeof ms.waypoint_type === 'string') els.msWaypointType.value = ms.waypoint_type;
      if (els.msTurnDistance && Number.isFinite(Number(ms.standard_turn_distance_m))) els.msTurnDistance.value = String(Number(ms.standard_turn_distance_m));
      if (els.msSafeStartAltitude && Number.isFinite(Number(ms.safe_start_altitude_m))) els.msSafeStartAltitude.value = String(Number(ms.safe_start_altitude_m));
      if (els.msDirectToStart && typeof ms.direct_to_start_position === 'boolean') els.msDirectToStart.checked = ms.direct_to_start_position;
      if (els.msGimbalMode && typeof ms.gimbal_mode === 'string') els.msGimbalMode.value = ms.gimbal_mode;
      syncGimbalAngleFlag();
      if (els.msAltitudeMode && typeof ms.altitude_mode === 'string') els.msAltitudeMode.value = ms.altitude_mode;
      syncFlightDirectionControls('number');
      syncOverlapControls('number');
      syncEdgeFactorControls('number');
      syncPlanSpeedField();
      updateCombinedFovText();
    }

    function quickSlotLabelByDbId(fieldDbId, idx) {
      const f = allFields.find((x) => String(x.id) === String(fieldDbId));
      if (!f) return `#${idx + 1}`;
      const fid = String(f.feld_id || '').trim();
      return fid ? (fid.length > 12 ? fid.slice(0, 12) : fid) : `#${idx + 1}`;
    }

    function renderQuickSlots() {
      if (!els.quickSlotGridEditor) return;
      const container = els.quickSlotGridEditor;
      container.innerHTML = '';
      for (let i = 0; i < QUICK_SLOT_COUNT; i++) {
        const slotFieldDbId = String(quickFieldSlots[i] || '');
        const f = allFields.find((x) => String(x.id) === slotFieldDbId);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `quick-slot-btn${slotFieldDbId && String(selectedId) === slotFieldDbId ? ' active' : ''}`;
        btn.textContent = quickSlotLabelByDbId(slotFieldDbId, i);
        btn.title = f
          ? `Slot ${i + 1}: ${f.feld_id} - ${f.name || ''}`
          : `Slot ${i + 1}: leer`;
        btn.addEventListener('click', async (ev) => {
          if (ev.altKey) {
            if (!selectedId) {
              showStatus('Zum Speichern im Schnellzugriff zuerst ein Feld laden.', false);
              return;
            }
            quickFieldSlots[i] = String(selectedId || '');
            saveFloatingUiState({ quickFieldSlots });
            renderQuickSlots();
            showStatus(`Schnellzugriff #${i + 1} gespeichert.`);
            return;
          }
          if (!slotFieldDbId) return;
          if (!allFields.some((x) => String(x.id) === slotFieldDbId)) {
            showStatus(`Schnellzugriff #${i + 1} verweist auf ein nicht mehr vorhandenes Feld.`, false);
            return;
          }
          try {
            await loadField(slotFieldDbId);
          } catch (e) {
            showStatus(String(e.message || e), false);
          }
        });
        container.appendChild(btn);
      }
    }

    function reapplyPersistentUiSettingsAfterFieldChange() {
      const state = loadFloatingUiState();
      const dc = state && state.lastDisplayControls && typeof state.lastDisplayControls === 'object'
        ? state.lastDisplayControls
        : null;
      if (dc) {
        if (els.showFieldPolygon && typeof dc.showFieldPolygon === 'boolean') els.showFieldPolygon.checked = dc.showFieldPolygon;
        if (els.showCoverageCircles && typeof dc.showCoverageCircles === 'boolean') els.showCoverageCircles.checked = dc.showCoverageCircles;
        applyCoverageWindowChoice(dc.coverageWindowMode);
        if (els.showOverlapAreas) els.showOverlapAreas.checked = typeof dc.showOverlapAreas === 'boolean' ? dc.showOverlapAreas : false;
        if (els.showUncoveredAreas && typeof dc.showUncoveredAreas === 'boolean') els.showUncoveredAreas.checked = dc.showUncoveredAreas;
        if (els.forceCoverageFix && typeof dc.forceCoverageFix === 'boolean') els.forceCoverageFix.checked = dc.forceCoverageFix;
        if (els.showControlPoints && typeof dc.showControlPoints === 'boolean') els.showControlPoints.checked = dc.showControlPoints;
        if (els.showAglLimit && typeof dc.showAglLimit === 'boolean') els.showAglLimit.checked = dc.showAglLimit;
        if (els.showTurnCurves && typeof dc.showTurnCurves === 'boolean') els.showTurnCurves.checked = dc.showTurnCurves;
        if (window.RkrkField && typeof RkrkField.setMapLabelScale === 'function' && Number.isFinite(Number(dc.mapLabelScale))) {
          RkrkField.setMapLabelScale(dc.mapLabelScale);
        }
        if (els.showCorrectionPoints && typeof dc.showCorrectionPoints === 'boolean') els.showCorrectionPoints.checked = dc.showCorrectionPoints;
        if (els.showFieldControlPoints && typeof dc.showFieldControlPoints === 'boolean') els.showFieldControlPoints.checked = dc.showFieldControlPoints;
        if (els.useFieldReferenceAgl && typeof dc.useFieldReferenceAgl === 'boolean') els.useFieldReferenceAgl.checked = dc.useFieldReferenceAgl;
        if (els.computeMode && typeof dc.computeMode === 'string') els.computeMode.value = (dc.computeMode === 'full') ? 'full' : 'simple';
      }
      syncPlanCoverageFromMap();
      syncPlanHeightsFromMap();
      // Keep box visibility/positions/open state independent from field content.
      restoreFloatingUiState();
      enforceCoverageDisplayConstraints();
      refreshFlightPathLayers();
    }

    function isSimpleComputeMode() {
      return String(els.computeMode && els.computeMode.value || 'simple') !== 'full';
    }

    function currentGimbalChoice() {
      const checked = !!(els.msGimbalAngleActive && els.msGimbalAngleActive.checked);
      const mode = checked ? 'Jeden Wegpunkt festlegen' : 'Manuell';
      if (els.msGimbalMode && Array.from(els.msGimbalMode.options).some((opt) => opt.value === mode)) {
        els.msGimbalMode.value = mode;
      }
      const raw = Number(els.wpPitch && els.wpPitch.value);
      const pitch = checked && Number.isFinite(raw) ? Math.max(-90, Math.min(30, Math.round(raw))) : -90;
      return { on: checked, mode: mode, pitch: pitch };
    }

    function formData() {
      const gimbal = currentGimbalChoice();
      const overrides = cloneWaypointOverrides();
      return {
        id: String(els.id.value || '').trim(),
        number: String(els.number.value || '').trim(),
        feld_id: String(els.feld_id.value || '').trim(),
        name: String(els.name.value || '').trim(),
        grundstueck_id: planGisValue(),
        jg: placeCode(els.jg),
        gemeinde: placeCode(els.gemeinde),
        std_flughoehe: String(els.std_flughoehe.value || '').trim(),
        flugzeit: String(els.flugzeit.value || '').trim(),
        distanz: String(els.distanz.value || '').trim(),
        wegpunkte: String(els.wegpunkte.value || '').trim(),
        flurname: readControl('flurname'),
        flaeche: readControl('flaeche'),
        betrieb: readControl('betrieb'),
        freigabe: readControl('freigabe'),
        warnung: readControl('warnung'),
        jaeger: readControl('jaeger'),
        bemerkung: readControl('bemerkung'),
        flug: readControl('flug'),
        funde: readControl('funde'),
        bauer: readControl('bauer'),
        notiz: readControl('notiz'),
        suchfeld_bild: readControl('suchfeld_bild'),
        flugplan_bild: readControl('flugplan_bild'),
        jason_file: readControl('jason_file'),
        kml_file: readControl('kml_file'),
        kmz_file: readControl('kmz_file'),
        parkplatz: readControl('parkplatz'),
        zentrum: readControl('zentrum'),
        export: readControl('export_yn'),
        select: readControl('select_yn'),
        startplatz: String(els.startplatz.value || '').trim(),
        flaechendaten: String(els.flaechendaten.value || '').trim(),
        flugweg: String(els.flugweg.value || '').trim(),
        mission_settings: {
          drone_type: String(els.msDroneType.value || 'Mavic 3T'),
          kmz_params_active: !!els.msKmzParamsActive.checked,
          simple_mode: isSimpleComputeMode(),
          easy_mode_form: editorEasyModeForm === true,
          altitude_m: Number(els.msAltitude.value || 60),
          flight_direction_deg: Number(els.msFlightDirection.value || -1),
          speed_m_s: Number(els.msSpeed.value || 5),
          overlap_pct: Number(els.msOverlapPct.value || 15),
          edge_factor_pct: Number(els.msEdgeFactorPct.value || 40),
          start_speed_active: !!els.msStartSpeedActive.checked,
          start_speed_m_s: Number(els.msStartSpeed.value || 5),
          hfov_deg: readNumericElement(els.msHfov, 49),
          vfov_deg: readNumericElement(els.msVfov, 40),
          dfov_deg: readNumericElement(els.msDfov, 61),
          end_action: String(els.msEndAction.value || 'Aufgabe beenden'),
          heading_mode: String(els.msHeadingMode.value || 'Manuell'),
          pois: missionPois.map((poi) => ({
            longitude: Number(poi.longitude),
            latitude: Number(poi.latitude),
            height: Number(poi.height) || 0,
          })),
          poi: missionPois[0] && Number.isFinite(Number(missionPois[0].longitude)) && Number.isFinite(Number(missionPois[0].latitude))
            ? missionPois[0]
            : null,
          waypoint_type: String(els.msWaypointType.value || 'Koordinierte Drehung, Ueberspringt den Wegpunkt'),
          standard_turn_distance_m: (function () {
            const n = Number(els.msTurnDistance && els.msTurnDistance.value);
            return Number.isFinite(n) ? n : 5;
          })(),
          safe_start_altitude_m: Number(els.msSafeStartAltitude.value || 20),
          direct_to_start_position: !!els.msDirectToStart.checked,
          gimbal_mode: gimbal.mode,
          gimbal_pitch: gimbal.on ? gimbal.pitch : -90,
          altitude_mode: String(els.msAltitudeMode.value || 'ALT (Relativ zum Startpunkt)'),
          waypoint_settings_mode: waypointSettingsModeFromOverrides(),
          waypoint_overrides: overrides,
        },
      };
    }

    function cloneWaypointOverrides() {
      try {
        return JSON.parse(JSON.stringify(waypointOverrides && typeof waypointOverrides === 'object' ? waypointOverrides : {}));
      } catch (_) {
        return {};
      }
    }

    function waypointSettingsModeFromOverrides() {
      const keys = ['use_mission_height', 'use_mission_speed', 'use_mission_waypoint_type', 'use_mission_yaw', 'use_mission_path_mode', 'use_mission_direction_mode'];
      const rows = Object.values(waypointOverrides || {});
      const hasLocal = rows.some((row) => keys.some((k) => row && row[k] === false));
      return hasLocal ? 'per_waypoint' : 'route_follow';
    }

    function currentWaypointPath() {
      const fromForm = normalizeTripletPath(parseTriplets(els.flugweg && els.flugweg.value));
      if (fromForm.length >= 2) return fromForm;
      const manual = normalizeTripletPath(originalManualPath.length ? originalManualPath : currentManualPath);
      if (manual.length >= 2) return manual;
      return normalizeTripletPath(currentComputedPath);
    }

    function defaultWaypointOverride() {
      return {
        use_mission_height: true,
        use_mission_speed: true,
        use_mission_waypoint_type: true,
        use_mission_yaw: true,
        use_mission_path_mode: true,
        use_mission_direction_mode: true,
        use_mission_turn_distance: true,
        auto_height: true,
        agl_m: Number(els.msAltitude && els.msAltitude.value || 60),
        speed_m_s: Number(els.msSpeed && els.msSpeed.value || 5),
        waypoint_type: String(els.msWaypointType && els.msWaypointType.value || 'Koordinierte Drehung, Ueberspringt den Wegpunkt'),
        heading_mode: String(els.msHeadingMode && els.msHeadingMode.value || 'Manuell'),
        turning_distance_m: Number(els.msTurnDistance && els.msTurnDistance.value || 5),
        direction_deg: 0,
        gimbal_pitch: -90,
        alignment_aircraft: 'Kuerzester Drehweg',
      };
    }

    function waypointOverrideRow(index1) {
      const row = waypointOverrides && waypointOverrides[String(index1)];
      if (!row || typeof row !== 'object') return defaultWaypointOverride();
      const merged = { ...defaultWaypointOverride(), ...row };
      ['use_mission_height', 'use_mission_speed', 'use_mission_waypoint_type', 'use_mission_yaw', 'use_mission_path_mode', 'use_mission_direction_mode'].forEach((key) => {
        if (!Object.prototype.hasOwnProperty.call(row, key)) return;
        const flag = jsonFlag(row[key]);
        if (flag === false || flag === true) merged[key] = flag;
      });
      return merged;
    }

    function setWaypointOverrideRow(index1, row) {
      if (!waypointOverrides || typeof waypointOverrides !== 'object') waypointOverrides = {};
      waypointOverrides[String(index1)] = row && typeof row === 'object' ? row : defaultWaypointOverride();
    }

    function clearWaypointOverrides(reason = '') {
      waypointOverrides = {};
      if (rightDrawerMode === 'waypoint') fillWaypointForm(selectedWaypointIndex1, true);
      if (reason) updateSaveButtonState();
    }

    function reindexWaypointOverrides(fromIndex0, delta) {
      const next = {};
      Object.keys(waypointOverrides || {}).forEach((k) => {
        const idx1 = Number(k);
        if (!Number.isInteger(idx1) || idx1 < 1) return;
        const idx0 = idx1 - 1;
        if (delta < 0 && idx0 === fromIndex0) return;
        let n0 = idx0;
        if (delta > 0 && idx0 >= fromIndex0) n0 = idx0 + delta;
        if (delta < 0 && idx0 > fromIndex0) n0 = idx0 + delta;
        next[String(n0 + 1)] = waypointOverrides[k];
      });
      waypointOverrides = next;
    }

    function isRightDrawerOpen() {
      return !!(els.editorRightDrawer && els.editorRightDrawer.classList.contains('open'));
    }

    function closeRightDrawer() {
      if (!els.editorRightDrawer) return;
      els.editorRightDrawer.classList.remove('open');
      els.editorRightDrawer.setAttribute('aria-hidden', 'true');
      rightDrawerMode = null;
      if (els.openMissionBtn) els.openMissionBtn.classList.remove('active');
      if (els.openPointParamsBtn) els.openPointParamsBtn.classList.remove('active');
      selectedWaypointIndex1 = 0;
      refreshFlightPathLayers();
      if (window.RkrkHelp) RkrkHelp.setView();
    }

    function openRightDrawer(mode) {
      if (!els.editorRightDrawer) return;
      rightDrawerMode = mode;
      const isWp = mode === 'waypoint';
      if (els.missionDrawerPanel) els.missionDrawerPanel.hidden = isWp;
      if (els.waypointDrawerPanel) els.waypointDrawerPanel.hidden = !isWp;
      if (els.waypointNavTabs) els.waypointNavTabs.hidden = !isWp;
      if (els.waypointDrawerFoot) els.waypointDrawerFoot.hidden = !isWp;
      if (els.editorRightDrawerTitle) {
        els.editorRightDrawerTitle.textContent = isWp ? 'Wegpunkt' : 'Missions Einstellungen';
      }
      if (els.editorRightDrawerSubtitle && !isWp) els.editorRightDrawerSubtitle.textContent = '';
      if (els.openMissionBtn) els.openMissionBtn.classList.toggle('active', mode === 'mission');
      if (els.openPointParamsBtn) els.openPointParamsBtn.classList.toggle('active', isWp);
      els.editorRightDrawer.classList.add('open');
      els.editorRightDrawer.setAttribute('aria-hidden', 'false');
      if (window.RkrkHelp) RkrkHelp.setView();
    }

    function openMissionDrawer() {
      if (document.getElementById('planRoutePane')) {
        if (planPhase !== 'waypoints') setPlanPhase('waypoints');
        showPlanParamTab('route');
        syncPlanExpertUi();
        setContextHelp('default');
        return;
      }
      openRightDrawer('mission');
      setContextHelp('default');
    }

    function nudgeWaypointAltitude(delta) {
      const el = els.wpAltitude;
      if (!el || el.disabled) return;
      const latRaw = String(els.wpLat && els.wpLat.value || '').trim();
      const lngRaw = String(els.wpLng && els.wpLng.value || '').trim();
      if (latRaw === '' || lngRaw === '') {
        const path = currentWaypointPath();
        if (path.length >= 2) {
          if (selectedWaypointIndex1 < 1) selectedWaypointIndex1 = 1;
          fillWaypointForm(selectedWaypointIndex1, !waypointFormSnapshot);
        }
      }
      const cur = Number(el.value);
      const base = Number.isFinite(cur) ? Math.round(cur) : 60;
      const min = Number(el.min);
      const max = Number(el.max);
      let next = base + delta;
      if (Number.isFinite(min)) next = Math.max(min, next);
      if (Number.isFinite(max)) next = Math.min(max, next);
      el.value = String(next);
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function syncWpNumberRange(numEl, rangeEl, src) {
      if (!numEl || !rangeEl) return;
      const n = Number((src === 'range' ? rangeEl : numEl).value);
      if (!Number.isFinite(n)) return;
      numEl.value = String(n);
      rangeEl.value = String(n);
    }

    function gimbalPointSettingOn() {
      return /wegpunkt|punkt|usepoint/i.test(String(els.msGimbalMode && els.msGimbalMode.value || ''));
    }

    function syncGimbalAngleFlag(source) {
      if (source === 'check' && els.msGimbalMode && els.msGimbalAngleActive) {
        const next = els.msGimbalAngleActive.checked ? 'Jeden Wegpunkt festlegen' : 'Manuell';
        if (Array.from(els.msGimbalMode.options).some((opt) => opt.value === next)) {
          els.msGimbalMode.value = next;
        }
      }
      const on = gimbalPointSettingOn();
      if (els.msGimbalAngleActive) els.msGimbalAngleActive.checked = on;
      if (!on) {
        if (els.wpPitch) els.wpPitch.value = '-90';
        if (els.wpPitchRange) els.wpPitchRange.value = '-90';
      }
      [els.wpPitch, els.wpPitchRange].forEach((el) => { if (el) el.disabled = !on; });
      syncGimbalWrapVisibility();
    }

    function syncGimbalWrapVisibility() {
      const modeBtn = document.getElementById('planModeToggle');
      const expert = !!(modeBtn && modeBtn.classList.contains('is-expert'));
      const on = !!(els.msGimbalAngleActive && els.msGimbalAngleActive.checked);
      const pitch = Number(els.wpPitch && els.wpPitch.value);
      const deviate = on && Number.isFinite(pitch) && pitch !== -90;
      const open = expert || deviate;
      if (els.wpPitchWrap) els.wpPitchWrap.hidden = !open;
      const msWrap = document.getElementById('msGimbalWrap');
      if (msWrap) msWrap.classList.toggle('is-gimbal-open', open);
    }

    function commitGimbalAngleOnly() {
      if (waypointFormFilling) return;
      syncGimbalAngleFlag();
      const gimbalOn = !!(els.msGimbalAngleActive && els.msGimbalAngleActive.checked);
      const pitchRaw = Number(els.wpPitch && els.wpPitch.value);
      const pitch = gimbalOn && Number.isFinite(pitchRaw) ? Math.max(-90, Math.min(30, Math.round(pitchRaw))) : -90;
      const path = currentWaypointPath();
      if (path.length >= 2 && selectedWaypointIndex1 >= 1) {
        const targets = waypointListTab === 'all' ? path.map((_, i) => i + 1) : [selectedWaypointIndex1];
        targets.forEach((idx1) => {
          const prev = (waypointOverrides && waypointOverrides[String(idx1)] && typeof waypointOverrides[String(idx1)] === 'object')
            ? waypointOverrides[String(idx1)]
            : {};
          setWaypointOverrideRow(idx1, Object.assign({}, prev, { gimbal_pitch: pitch }));
        });
      }
      updateSaveButtonState();
    }

    function syncTurnDistanceVisibility() {
      const missionType = els.msWaypointType ? els.msWaypointType.value : '';
      const missionWrap = document.getElementById('msTurnDistanceWrap');
      if (missionWrap) missionWrap.hidden = !isSkipTurnWaypointType(missionType);
      const useMissionType = !els.wpUseMissionWaypointType || els.wpUseMissionWaypointType.checked;
      const pointType = useMissionType ? missionType : (els.wpWaypointType ? els.wpWaypointType.value : missionType);
      const pointWrap = document.getElementById('wpTurnDistanceWrap');
      if (pointWrap) pointWrap.hidden = !isSkipTurnWaypointType(pointType);
    }

    function applyWaypointFormEnableState() {
      syncTurnDistanceVisibility();
      const useH = !!(els.wpUseMissionHeight && els.wpUseMissionHeight.checked);
      const useS = !!(els.wpUseMissionSpeed && els.wpUseMissionSpeed.checked);
      const useT = !!(els.wpUseMissionWaypointType && els.wpUseMissionWaypointType.checked);
      const useY = !!(els.wpUseMissionYaw && els.wpUseMissionYaw.checked);
      const useP = !!(els.wpUseMissionPathMode && els.wpUseMissionPathMode.checked);
      const useD = !!(els.wpUseMissionDirectionMode && els.wpUseMissionDirectionMode.checked);
      [els.wpAltitude, els.wpAltitudeRange].forEach((el) => { if (el) el.disabled = useH; });
      [els.wpSpeed, els.wpSpeedRange].forEach((el) => { if (el) el.disabled = useS; });
      if (els.wpWaypointType) els.wpWaypointType.disabled = useT;
      [els.wpTurnDistance, els.wpTurnDistanceRange].forEach((el) => { if (el) el.disabled = useT; });
      if (els.wpHeadingMode) els.wpHeadingMode.disabled = useY;
      if (els.wpAlignment) els.wpAlignment.disabled = useP;
      const headingVal = String(els.wpHeadingMode && els.wpHeadingMode.value || '');
      const definedYaw = yawIsDefinedMode(headingVal);
      const lockedAxis = yawIsLockedAxis(headingVal);
      if (els.wpDirectionWrap) els.wpDirectionWrap.hidden = !(definedYaw || lockedAxis);
      const angleLocked = lockedAxis || (definedYaw ? useY : true);
      [els.wpDirection, els.wpDirectionRange].forEach((el) => { if (el) el.disabled = angleLocked; });
      syncGimbalAngleFlag();
      const modeBtn = document.getElementById('planModeToggle');
      const expert = !!(modeBtn && modeBtn.classList.contains('is-expert'));
      if (!expert) {
        if (els.wpAltitude) els.wpAltitude.disabled = false;
        if (els.wpAltitudeRange) els.wpAltitudeRange.disabled = false;
        [els.wpSpeed, els.wpSpeedRange].forEach((el) => { if (el) el.disabled = false; });
        if (els.wpWaypointType) els.wpWaypointType.disabled = false;
        if (els.wpHeadingMode) els.wpHeadingMode.disabled = false;
        [els.wpDirection, els.wpDirectionRange].forEach((el) => { if (el) el.disabled = lockedAxis || !definedYaw; });
      }
      const altLocked = !!(els.wpAltitude && els.wpAltitude.disabled);
      ['wpAltMinus', 'wpAltPlus'].forEach((id) => {
        const btn = document.getElementById(id);
        if (btn) btn.disabled = altLocked;
      });
      const poiBox = document.getElementById('wpPoiBox');
      if (poiBox) poiBox.hidden = !/fokus\s*poi|^poi$/i.test(headingVal);
      const msPoi = document.getElementById('msPoiBox');
      const msHeading = String(els.msHeadingMode && els.msHeadingMode.value || '');
      if (msPoi) msPoi.hidden = !/fokus\s*poi/i.test(msHeading);
      const debug = editorDebugOn();
      const pathWrap = document.getElementById('wpPathFollowWrap');
      const dirWrap = document.getElementById('wpDirFollowWrap');
      if (pathWrap) pathWrap.hidden = !debug;
      if (dirWrap) dirWrap.hidden = !debug;
      const effectiveYaw = useY ? msHeading : headingVal;
      if (yawIsDefinedMode(effectiveYaw)) {
        if (els.wpUseMissionPathMode) els.wpUseMissionPathMode.checked = true;
        if (els.wpUseMissionDirectionMode) els.wpUseMissionDirectionMode.checked = true;
      }
    }

    function planCheckIsSimple() {
      const panel = document.getElementById('planWpPanel');
      return !!(panel && panel.classList.contains('is-simple'));
    }

    function nearMissionNumber(local, mission) {
      const x = Number(local);
      const y = Number(mission);
      if (!Number.isFinite(x) || !Number.isFinite(y)) return false;
      return Math.abs(x - y) < 0.05;
    }

    function readWaypointFormRow() {
      let useH = !!(els.wpUseMissionHeight && els.wpUseMissionHeight.checked);
      let useS = !!(els.wpUseMissionSpeed && els.wpUseMissionSpeed.checked);
      let useT = !!(els.wpUseMissionWaypointType && els.wpUseMissionWaypointType.checked);
      let useY = !!(els.wpUseMissionYaw && els.wpUseMissionYaw.checked);
      const localType = String(els.wpWaypointType && els.wpWaypointType.value || '');
      const localYaw = String(els.wpHeadingMode && els.wpHeadingMode.value || '');
      if (planCheckIsSimple()) {
        useH = nearMissionNumber(els.wpAltitude && els.wpAltitude.value, els.msAltitude && els.msAltitude.value);
        useS = nearMissionNumber(els.wpSpeed && els.wpSpeed.value, els.msSpeed && els.msSpeed.value);
        useT = localType !== '' && localType === String(els.msWaypointType && els.msWaypointType.value || '');
        useY = localYaw !== '' && localYaw === String(els.msHeadingMode && els.msHeadingMode.value || '');
        if (els.wpUseMissionHeight) els.wpUseMissionHeight.checked = useH;
        if (els.wpUseMissionSpeed) els.wpUseMissionSpeed.checked = useS;
        if (els.wpUseMissionWaypointType) els.wpUseMissionWaypointType.checked = useT;
        if (els.wpUseMissionYaw) els.wpUseMissionYaw.checked = useY;
      }
      const yawNow = useY
        ? String(els.msHeadingMode && els.msHeadingMode.value || '')
        : localYaw;
      const definedYaw = yawIsDefinedMode(yawNow);
      const rawDir = String(els.wpDirection && els.wpDirection.value || '').trim();
      const dirNum = (definedYaw && rawDir !== '' && rawDir !== '-' && Number.isFinite(Number(rawDir)))
        ? Number(rawDir)
        : null;
      const gimbalOn = !!(els.msGimbalAngleActive && els.msGimbalAngleActive.checked);
      const pitchRaw = Number(els.wpPitch && els.wpPitch.value);
      const pitch = gimbalOn && Number.isFinite(pitchRaw)
        ? Math.max(-90, Math.min(30, Math.round(pitchRaw)))
        : -90;
      return {
        use_mission_height: useH,
        use_mission_speed: useS,
        use_mission_waypoint_type: useT,
        use_mission_yaw: useY,
        use_mission_path_mode: definedYaw || !!(els.wpUseMissionPathMode && els.wpUseMissionPathMode.checked),
        use_mission_direction_mode: definedYaw || !!(els.wpUseMissionDirectionMode && els.wpUseMissionDirectionMode.checked),
        use_mission_turn_distance: useT,
        agl_m: Number(els.wpAltitude && els.wpAltitude.value || 60),
        speed_m_s: Number(els.wpSpeed && els.wpSpeed.value || 5),
        waypoint_type: localType,
        heading_mode: localYaw,
        turning_distance_m: Number(els.wpTurnDistance && els.wpTurnDistance.value || 5),
        direction_deg: dirNum,
        heading_angle: dirNum,
        gimbal_pitch: pitch,
        alignment_aircraft: String(els.wpAlignment && els.wpAlignment.value || ''),
      };
    }

    function commitWaypointFormToState() {
      const path = currentWaypointPath();
      if (path.length < 2) return false;
      const row = readWaypointFormRow();
      const applyAll = waypointListTab === 'all';
      const targets = applyAll
        ? path.map((_, i) => i + 1)
        : [selectedWaypointIndex1];
      targets.forEach((idx1) => {
        const prev = (waypointOverrides && waypointOverrides[String(idx1)] && typeof waypointOverrides[String(idx1)] === 'object')
          ? waypointOverrides[String(idx1)]
          : {};
        setWaypointOverrideRow(idx1, Object.assign({}, prev, row));
      });
      const next = path.map((p) => [p[0], p[1], p[2]]);
        if (!applyAll) {
        const idx0 = selectedWaypointIndex1 - 1;
        if (idx0 >= 0 && idx0 < next.length) {
          const latRaw = String(els.wpLat && els.wpLat.value || '').trim();
          const lngRaw = String(els.wpLng && els.wpLng.value || '').trim();
          const lat = Number(latRaw);
          const lng = Number(lngRaw);
          if (latRaw !== '' && lngRaw !== '' && Number.isFinite(lat) && Number.isFinite(lng)) {
            next[idx0][0] = lng;
            next[idx0][1] = lat;
          }
          if (!row.use_mission_height && Number.isFinite(row.agl_m)) {
            next[idx0][2] = row.agl_m;
          }
        }
      } else if (!row.use_mission_height && Number.isFinite(row.agl_m)) {
        for (let i = 0; i < next.length; i++) next[i][2] = row.agl_m;
      }
      commitManualPathToForm(next, 'Wegpunktdaten geändert');
      return true;
    }

    function waypointHasLocalOverride(index1) {
      const row = waypointOverrides[String(index1)];
      if (window.RkrkField && typeof RkrkField.waypointMissesMission === 'function') {
        return RkrkField.waypointMissesMission(row);
      }
      if (!row || typeof row !== 'object') return false;
      return row.use_mission_height === false
        || row.use_mission_speed === false
        || row.use_mission_waypoint_type === false
        || row.use_mission_yaw === false
        || row.use_mission_path_mode === false
        || row.use_mission_direction_mode === false;
    }

    function renderWaypointAllList() {
      if (!els.wpAllList) return;
      const path = currentWaypointPath();
      els.wpAllList.innerHTML = '';
      path.forEach((_, i) => {
        const idx1 = i + 1;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `wp-all-item${idx1 === selectedWaypointIndex1 ? ' active' : ''}`;
        const local = waypointHasLocalOverride(idx1);
        btn.innerHTML = `<span>P${idx1}</span><span class="meta">${local ? 'eigene Werte' : 'Missionswerte'}</span>`;
        btn.addEventListener('click', () => {
          commitWaypointFormToState();
          waypointListTab = 'single';
          fillWaypointForm(idx1, true);
          showWaypointTab('single');
        });
        els.wpAllList.appendChild(btn);
      });
    }

    function showWaypointTab(tab) {
      waypointListTab = tab === 'all' ? 'all' : 'single';
      if (els.wpSinglePane) els.wpSinglePane.hidden = waypointListTab === 'all';
      if (els.wpAllPane) els.wpAllPane.hidden = waypointListTab !== 'all';
      if (els.wpAllNote) els.wpAllNote.hidden = waypointListTab !== 'all';
      if (els.wpLatWrap) els.wpLatWrap.hidden = waypointListTab === 'all';
      if (els.wpLngWrap) els.wpLngWrap.hidden = waypointListTab === 'all';
      if (els.wpDeleteBtn) els.wpDeleteBtn.style.visibility = waypointListTab === 'all' ? 'hidden' : '';
      if (els.wpTabWaypoint) els.wpTabWaypoint.classList.toggle('active', waypointListTab === 'single');
      if (els.wpTabAll) els.wpTabAll.classList.toggle('active', waypointListTab === 'all');
      const path = currentWaypointPath();
      if (waypointListTab === 'all') {
        if (els.editorRightDrawerTitle) els.editorRightDrawerTitle.textContent = 'Alle Wegpunkte';
        if (els.editorRightDrawerSubtitle) els.editorRightDrawerSubtitle.textContent = `${path.length} Marker`;
      } else if (selectedWaypointIndex1 >= 1) {
        if (els.editorRightDrawerTitle) els.editorRightDrawerTitle.textContent = `Wegpunkt P${selectedWaypointIndex1}`;
        if (els.editorRightDrawerSubtitle) els.editorRightDrawerSubtitle.textContent = `Marker ${selectedWaypointIndex1} von ${path.length}`;
      }
    }

    function writeStartRelHint(programmed, terrain, startTerrain) {
      if (!els.wpStartRelHint) return;
      if (window.RkrkField && typeof RkrkField.heightCaption === 'function') {
        els.wpStartRelHint.textContent = RkrkField.heightCaption(programmed, terrain, startTerrain).paren || '(– m)';
        return;
      }
      els.wpStartRelHint.textContent = Number.isFinite(Number(programmed)) ? ('(' + Number(programmed).toFixed(1) + ' m)') : '(– m)';
    }

    async function updateWaypointAglHint(lon, lat, agl) {
      if (!els.wpAglHint && !els.wpStartRelHint) return;
      if (!Number.isFinite(lon) || !Number.isFinite(lat) || !Number.isFinite(agl)) {
        if (els.wpAglHint) els.wpAglHint.textContent = 'Flughöhe zum Boden: -';
        writeStartRelHint(NaN, NaN);
        return;
      }
      try {
        const terrain = await fetchGeoAdminTerrainHeight(lon, lat);
        if (Number.isFinite(terrain)) {
          const startPts = parsePairs(els.startplatz && els.startplatz.value);
          let startTerrain = NaN;
          if (startPts.length) {
            try { startTerrain = await fetchGeoAdminTerrainHeight(startPts[0][0], startPts[0][1]); } catch (_) { startTerrain = NaN; }
          }
          const groundRel = Number.isFinite(startTerrain) ? (terrain - startTerrain) : 0;
          const aboveGround = agl - groundRel;
          if (els.wpAglHint) els.wpAglHint.textContent = `Flughöhe zum Boden: ${aboveGround.toFixed(2)}m`;
          writeStartRelHint(agl, Number.isFinite(startTerrain) ? terrain : NaN, startTerrain);
          return;
        }
      } catch (_) { /* ignore */ }
      if (els.wpAglHint) els.wpAglHint.textContent = `Flughöhe zum Boden: ${Number(agl).toFixed(2)}m`;
      writeStartRelHint(agl, NaN, NaN);
    }

    function fillWaypointForm(index1, takeSnapshot) {
      const path = currentWaypointPath();
      if (path.length < 2) return false;
      const idx0 = Math.max(0, Math.min(path.length - 1, Number(index1) - 1));
      selectedWaypointIndex1 = idx0 + 1;
      const pt = path[idx0];
      const storedFlags = (waypointOverrides && waypointOverrides[String(selectedWaypointIndex1)]) || {};
      const followsMission = (key) => jsonFlag(storedFlags[key]) !== false;
      const row = waypointOverrideRow(selectedWaypointIndex1);
      const missionAlt = Number(els.msAltitude && els.msAltitude.value || 60);
      const missionSpeed = Number(els.msSpeed && els.msSpeed.value || 5);
      const missionTurn = Number(els.msTurnDistance && els.msTurnDistance.value || 5);
      const missionType = String(els.msWaypointType && els.msWaypointType.value || row.waypoint_type);
      const missionYaw = String(els.msHeadingMode && els.msHeadingMode.value || row.heading_mode);
      waypointFormFilling = true;
      if (els.editorRightDrawerTitle) {
        els.editorRightDrawerTitle.textContent = waypointListTab === 'all'
          ? 'Alle Wegpunkte'
          : `Wegpunkt P${selectedWaypointIndex1}`;
      }
      if (els.editorRightDrawerSubtitle) {
        els.editorRightDrawerSubtitle.textContent = waypointListTab === 'all'
          ? `${path.length} Marker`
          : `Marker ${selectedWaypointIndex1} von ${path.length}`;
      }
      if (els.wpLat) els.wpLat.value = String(pt[1]);
      if (els.wpLng) els.wpLng.value = String(pt[0]);
      if (els.wpUseMissionHeight) els.wpUseMissionHeight.checked = followsMission('use_mission_height');
      const alt = followsMission('use_mission_height') ? missionAlt : (Number.isFinite(Number(storedFlags.agl_m)) ? Number(storedFlags.agl_m) : Number(pt[2] || missionAlt));
      if (els.wpAltitude) els.wpAltitude.value = String(alt);
      if (els.wpAltitudeRange) els.wpAltitudeRange.value = String(alt);
      if (els.wpMissionAltHint) els.wpMissionAltHint.textContent = `Missions-Höhe: ${missionAlt}m`;
      if (els.wpUseMissionSpeed) els.wpUseMissionSpeed.checked = followsMission('use_mission_speed');
      const spd = followsMission('use_mission_speed') ? missionSpeed : Number(storedFlags.speed_m_s != null ? storedFlags.speed_m_s : missionSpeed);
      if (els.wpSpeed) els.wpSpeed.value = String(spd);
      if (els.wpSpeedRange) els.wpSpeedRange.value = String(spd);
      if (els.wpMissionSpeedHint) els.wpMissionSpeedHint.textContent = `Missions-Geschwindigkeit: ${missionSpeed}m/s`;
      if (els.wpUseMissionWaypointType) els.wpUseMissionWaypointType.checked = followsMission('use_mission_waypoint_type');
      if (els.wpWaypointType) els.wpWaypointType.value = followsMission('use_mission_waypoint_type') ? missionType : (storedFlags.waypoint_type || missionType);
      const turn = row.use_mission_turn_distance !== false ? missionTurn : Number(row.turning_distance_m || missionTurn);
      if (els.wpTurnDistance) els.wpTurnDistance.value = String(turn);
      if (els.wpTurnDistanceRange) els.wpTurnDistanceRange.value = String(turn);
      if (els.wpMissionTurnHint) els.wpMissionTurnHint.textContent = `Standard-Wendedistanz: ${Number(missionTurn).toFixed(2)}m`;
      if (els.wpUseMissionYaw) els.wpUseMissionYaw.checked = followsMission('use_mission_yaw');
      if (els.wpUseMissionPathMode) els.wpUseMissionPathMode.checked = followsMission('use_mission_path_mode');
      if (els.wpUseMissionDirectionMode) els.wpUseMissionDirectionMode.checked = followsMission('use_mission_direction_mode');
      if (els.wpHeadingMode) els.wpHeadingMode.value = followsMission('use_mission_yaw') ? missionYaw : (storedFlags.heading_mode || missionYaw);
      if (els.wpAlignment) {
        const rawAlign = followsMission('use_mission_path_mode') ? (row.alignment_aircraft || 'Automatisch') : (storedFlags.alignment_aircraft || row.alignment_aircraft || 'Automatisch');
        const align = /kuerzester|kürzester|followbadarc/i.test(String(rawAlign)) ? 'Automatisch' : String(rawAlign || 'Automatisch');
        els.wpAlignment.value = align;
      }
      const storedYawRaw = storedFlags.heading_angle != null && storedFlags.heading_angle !== ''
        ? storedFlags.heading_angle
        : storedFlags.direction_deg;
      const hasStoredYaw = storedYawRaw != null && storedYawRaw !== '' && String(storedYawRaw).toLowerCase() !== 'null' && Number.isFinite(Number(storedYawRaw));
      const dir = hasStoredYaw && !followsMission('use_mission_yaw') ? Number(storedYawRaw) : null;
      if (els.wpDirection) els.wpDirection.value = dir == null ? '' : String(dir);
      if (els.wpDirectionRange) els.wpDirectionRange.value = dir == null ? '' : String(dir);
      const pitch = Number.isFinite(Number(row.gimbal_pitch)) ? Number(row.gimbal_pitch) : -90;
      if (els.wpPitch) els.wpPitch.value = String(pitch);
      if (els.wpPitchRange) els.wpPitchRange.value = String(pitch);
      applyWaypointFormEnableState();
      waypointFormFilling = false;
      if (takeSnapshot) snapshotWaypointEditorState();
      updateWaypointAglHint(pt[0], pt[1], alt);
      refreshFlightPathLayers();
      return true;
    }

    function openWaypointDrawer(index1) {
      const path = currentWaypointPath();
      if (path.length < 2) {
        showStatus('Zuerst Flugpfad laden oder rechnen.', false);
        return;
      }
      const idx = Number(index1);
      selectedWaypointIndex1 = Number.isInteger(idx) && idx >= 1 ? Math.min(idx, path.length) : 1;
      if (document.getElementById('planPointPane')) {
        if (planPhase !== 'waypoints') setPlanPhase('waypoints');
        showPlanParamTab('waypoint');
        showWaypointTab('single');
        fillWaypointForm(selectedWaypointIndex1, true);
        renderPlanWpChips();
        setContextHelp('path');
        return;
      }
      openRightDrawer('waypoint');
      showWaypointTab(waypointListTab === 'all' ? 'all' : 'single');
      fillWaypointForm(selectedWaypointIndex1, true);
      setContextHelp('path');
    }

    function snapshotWaypointEditorState() {
      waypointFormSnapshot = {
        index1: selectedWaypointIndex1,
        overrides: cloneWaypointOverrides(),
        path: currentWaypointPath().map((p) => [p[0], p[1], p[2]]),
      };
    }

    function applyWaypointSnapshot() {
      if (!waypointFormSnapshot) return;
      const snap = waypointFormSnapshot;
      waypointOverrides = snap.overrides && typeof snap.overrides === 'object'
        ? JSON.parse(JSON.stringify(snap.overrides))
        : {};
      if (Array.isArray(snap.path) && snap.path.length >= 2) {
        const next = snap.path.map((p) => [p[0], p[1], p[2]]);
        currentManualPath = next;
        originalManualPath = next.map((p) => [p[0], p[1], p[2]]);
        if (els.flugweg) els.flugweg.value = formatTriplets(next);
      }
      updateSaveButtonState();
      refreshFlightPathLayers();
    }

    function applyCurrentWaypointToAll() {
      const path = currentWaypointPath();
      if (path.length < 2) return;
      commitWaypointFormToState();
      const row = readWaypointFormRow();
      const next = {};
      for (let i = 1; i <= path.length; i++) next[String(i)] = { ...row };
      waypointOverrides = next;
      renderWaypointAllList();
      updateSaveButtonState();
      showStatus('Punktparameter auf alle Wegpunkte angewendet.');
    }

    function deleteSelectedWaypoint() {
      const path = currentWaypointPath();
      const idx0 = selectedWaypointIndex1 - 1;
      if (idx0 < 0 || idx0 >= path.length || path.length <= 2) {
        showStatus('Mindestens 2 Wegpunkte müssen bleiben.', false);
        return;
      }
      if (!window.confirm(`Wegpunkt P${selectedWaypointIndex1} löschen?`)) return;
      const next = path.slice();
      next.splice(idx0, 1);
      reindexWaypointOverrides(idx0, -1);
      commitManualPathToForm(next, 'Pfadpunkt geloescht');
      const newIdx = Math.min(selectedWaypointIndex1, next.length);
      fillWaypointForm(newIdx, true);
      showStatus('Pfadpunkt geloescht.');
    }

    function loadWaypointSettingsFromField(field) {
      const ms = field && field.mission_settings && typeof field.mission_settings === 'object'
        ? field.mission_settings
        : {};
      waypointOverrides = (ms.waypoint_overrides && typeof ms.waypoint_overrides === 'object')
        ? JSON.parse(JSON.stringify(ms.waypoint_overrides))
        : {};
    }

    function applyDroneProfileToForm() {
      const selected = String(els.msDroneType && els.msDroneType.value || '');
      const p = droneProfiles.find((x) => String(x.name || '') === selected || String(x.key || '') === selected);
      if (!p) return;
      writeFovInfo(els.msHfov, Number(p.hfov_deg || 0));
      writeFovInfo(els.msVfov, Number(p.vfov_deg || 0));
      writeFovInfo(els.msDfov, Number(p.dfov_deg || 0));
      updateCombinedFovText();
    }

    function fillSelectFromSpec(el, options) {
      if (!el || !Array.isArray(options) || !options.length) return;
      const prev = String(el.value || '');
      const defaultOpt = options.find((o) => o && o.default);
      el.innerHTML = '';
      options.forEach((o) => {
        const opt = document.createElement('option');
        opt.value = String(o.value || '');
        opt.textContent = String(o.label || o.value || '');
        if (o.help) opt.title = String(o.help);
        if (o.extra && typeof o.extra === 'object') {
          try { opt.dataset.extra = JSON.stringify(o.extra); } catch (_) { /* ignore */ }
        }
        el.appendChild(opt);
      });
      if (prev && Array.from(el.options).some((o) => o.value === prev)) {
        el.value = prev;
      } else if (defaultOpt && Array.from(el.options).some((o) => o.value === String(defaultOpt.value))) {
        el.value = String(defaultOpt.value);
      }
    }

    async function loadDjiInternalSpec() {
      const r = await fetch('api_mission_export.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_dji_spec', field: { mission_settings: {} } }),
      });
      const data = await r.json();
      if (!data.ok) throw new Error(`${data.error || 'dji_spec_failed'}${data.detail ? ` - ${data.detail}` : ''}`);
      const selects = (data.ui && data.ui.selects) ? data.ui.selects : {};
      fillSelectFromSpec(els.msEndAction, selects.end_action);
      fillSelectFromSpec(els.msHeadingMode, selects.heading_mode);
      fillSelectFromSpec(els.msWaypointType, selects.waypoint_type);
      fillSelectFromSpec(els.wpWaypointType, selects.waypoint_type);
      fillSelectFromSpec(els.wpHeadingMode, selects.heading_mode);
      fillSelectFromSpec(els.msGimbalMode, selects.gimbal_mode);
      fillSelectFromSpec(els.msAltitudeMode, selects.altitude_mode);
      fillSelectFromSpec(els.wpAlignment, selects.heading_path_mode);
    }

    async function loadDroneProfiles() {
      const r = await fetch('api_mission_export.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_drone_profiles', field: formData() }),
      });
      const data = await r.json();
      if (!data.ok) throw new Error(`${data.error || 'drone_profiles_failed'}${data.detail ? ` - ${data.detail}` : ''}`);
      const rawProfiles = Array.isArray(data.profiles) ? data.profiles.slice() : [];
      // Only profiles explicitly enabled for editor should be selectable.
      droneProfiles = rawProfiles.filter((p) => !!(p && p.editor));
      const prev = String(els.msDroneType.value || '');
      if (els.msDroneType) {
        els.msDroneType.innerHTML = '';
        droneProfiles.forEach((p) => {
          const name = String(p.name || p.key || '').trim();
          if (!name) return;
          const opt = document.createElement('option');
          opt.value = name;
          opt.textContent = droneOptionLabel(name);
          els.msDroneType.appendChild(opt);
        });
        if (prev && Array.from(els.msDroneType.options).some((o) => o.value === prev)) {
          els.msDroneType.value = prev;
        } else if (Array.from(els.msDroneType.options).some((o) => o.value === 'Mavic 3T')) {
          els.msDroneType.value = 'Mavic 3T';
        } else if (els.msDroneType.options.length) {
          els.msDroneType.value = String(els.msDroneType.options[0].value || '');
        }
      }
      syncPlanDroneSelect();
      applyDroneProfileToForm();
    }

    function applyKmzParamsUiState() {
      const active = !!(els.msKmzParamsActive && els.msKmzParamsActive.checked);
      const targets = [
        els.msAltitude, els.msFlightDirectionRange, els.msFlightDirection, els.msSpeed, els.msStartSpeedActive, els.msStartSpeed,
        els.msOverlapPctRange, els.msOverlapPct, els.msEdgeFactorPctRange, els.msEdgeFactorPct,
        els.msEndAction, els.msHeadingMode, els.msWaypointType, els.msTurnDistance,
        els.msSafeStartAltitude, els.msDirectToStart, els.msGimbalMode, els.msGimbalAngleActive, els.msAltitudeMode,
      ];
      targets.forEach((el) => {
        if (!el) return;
        el.disabled = !active;
      });
    }

    function clampFlightDirection(value) {
      let n = Number(value);
      if (!Number.isFinite(n)) n = -1;
      n = Math.round(n);
      if (n < -1) n = -1;
      if (n > 259) n = 259;
      return n;
    }

    let planDirSuggestedDeg = 0;

    function syncPlanDirSlider() {
      const slider = document.getElementById('planDirRange');
      const label = document.getElementById('planDirLabel');
      if (!slider) return;
      const fixed = clampFlightDirection(els.msFlightDirection && els.msFlightDirection.value);
      if (fixed < 0) {
        slider.value = String(planDirSuggestedDeg);
        if (label) label.textContent = `Vorschlag ${planDirSuggestedDeg}°`;
      } else {
        const shown = ((fixed % 180) + 180) % 180;
        slider.value = String(shown);
        if (label) label.textContent = `${shown}°`;
      }
    }

    function nudgePlanDirection(delta) {
      const fixed = clampFlightDirection(els.msFlightDirection && els.msFlightDirection.value);
      const base = fixed < 0 ? planDirSuggestedDeg : (((fixed % 180) + 180) % 180);
      const deg = ((base + Number(delta)) % 180 + 180) % 180;
      if (els.msFlightDirection) els.msFlightDirection.value = String(deg);
      syncFlightDirectionControls('number');
      resetOriginalPathOnNextRecompute = true;
      const compute = document.getElementById('computePathBtn');
      if (compute) compute.click();
      const rail = document.getElementById('planStepRail');
      if (rail) rail.textContent = 'Status: Route wird optimiert';
    }

    function suggestedDirectionFromUsed(used) {
      if (!used || typeof used !== 'object') return null;
      let base = Number(used.open_direction_edge_align_angle_deg);
      if (!Number.isFinite(base) || base < 0) {
        const fixed = Number(used.flight_direction_deg);
        if (Number.isFinite(fixed) && fixed >= 0) base = fixed;
        else return null;
      }
      const delta = used.angle_tune_applied ? Number(used.angle_tune_delta_deg) : 0;
      const deg = base + (Number.isFinite(delta) ? delta : 0);
      return ((Math.round(deg) % 180) + 180) % 180;
    }

    function applySuggestedFlightDirection(used) {
      const fixedNow = clampFlightDirection(els.msFlightDirection && els.msFlightDirection.value);
      if (fixedNow < 0) {
        const deg = suggestedDirectionFromUsed(used);
        if (deg !== null) planDirSuggestedDeg = deg;
      }
      syncPlanDirSlider();
    }

    function syncFlightDirectionControls(source) {
      const current = source === 'range'
        ? (els.msFlightDirectionRange ? els.msFlightDirectionRange.value : -1)
        : (els.msFlightDirection ? els.msFlightDirection.value : -1);
      const next = clampFlightDirection(current);
      if (els.msFlightDirection) els.msFlightDirection.value = String(next);
      if (els.msFlightDirectionRange) els.msFlightDirectionRange.value = String(next);
      syncPlanDirSlider();
    }

    function clampPercentValue(value, min, max, fallback) {
      let n = Number(value);
      if (!Number.isFinite(n)) n = fallback;
      if (n < min) n = min;
      if (n > max) n = max;
      return Math.round(n / 5) * 5;
    }

    function syncOverlapControls(source) {
      const current = source === 'range'
        ? (els.msOverlapPctRange ? els.msOverlapPctRange.value : 15)
        : (els.msOverlapPct ? els.msOverlapPct.value : 15);
      const next = clampPercentValue(current, 0, 95, 15);
      if (els.msOverlapPct) els.msOverlapPct.value = String(next);
      if (els.msOverlapPctRange) els.msOverlapPctRange.value = String(next);
    }

    function syncEdgeFactorControls(source) {
      const current = source === 'range'
        ? (els.msEdgeFactorPctRange ? els.msEdgeFactorPctRange.value : 40)
        : (els.msEdgeFactorPct ? els.msEdgeFactorPct.value : 40);
      const next = clampPercentValue(current, 0, 100, 40);
      if (els.msEdgeFactorPct) els.msEdgeFactorPct.value = String(next);
      if (els.msEdgeFactorPctRange) els.msEdgeFactorPctRange.value = String(next);
    }

    function markDisabledDisplayOptions() {
      const setup = els.mapSetupPanel;
      if (!setup) return;
      const disabledChecks = setup.querySelectorAll('input[type="checkbox"][disabled]');
      disabledChecks.forEach((cb) => {
        if (cb.parentElement) cb.parentElement.classList.add('is-disabled');
      });
    }

    function captureEditorState() {
      return {
        startplatz: String(els.startplatz.value || ''),
        flaechendaten: String(els.flaechendaten.value || ''),
      };
    }

    function statesEqual(a, b) {
      if (!a || !b) return false;
      return String(a.startplatz || '') === String(b.startplatz || '')
        && String(a.flaechendaten || '') === String(b.flaechendaten || '');
    }

    function pushHistory(label = 'Bearbeitung') {
      if (restoringHistory) return;
      const next = captureEditorState();
      const prev = editHistory.length ? editHistory[editHistory.length - 1].state : null;
      if (prev && statesEqual(prev, next)) return;
      editHistory.push({ label, state: next });
      if (editHistory.length > MAX_HISTORY) editHistory.shift();
      updateUndoButtonState();
    }

    function updateUndoButtonState() {
      if (!els.undoBtn) return;
      els.undoBtn.disabled = editHistory.length <= 1;
      els.undoBtn.title = editHistory.length <= 1 ? 'Kein Schritt zum Rueckgaengig machen' : 'Letzten Bearbeitungsschritt rueckgaengig';
    }

    function undoEditStep() {
      if (editHistory.length <= 1) {
        showStatus('Kein weiterer Schritt zum Rueckgaengig machen.', false);
        return;
      }
      // Drop current state, restore previous.
      editHistory.pop();
      const prev = editHistory[editHistory.length - 1];
      if (!prev || !prev.state) return;
      restoringHistory = true;
      els.startplatz.value = String(prev.state.startplatz || '');
      els.flaechendaten.value = String(prev.state.flaechendaten || '');
      syncMapFromForm();
      restoringHistory = false;
      updateUndoButtonState();
      showStatus(`Rueckgaengig: ${prev.label || 'Bearbeitungsschritt'}`);
    }

    function placeCode(el) {
      if (!el) return '';
      if (el.tagName === 'SELECT') return String(el.value || '').trim();
      const code = String(el.dataset.code || '').trim();
      return code || String(el.value || '').trim();
    }

    function showPlace(el, code, label) {
      if (!el) return;
      const id = String(code || '').trim();
      const text = String(label || id).trim();
      if (el.tagName === 'SELECT') {
        writeControl(el.id, id);
        if (id) el.dataset.code = id;
        else delete el.dataset.code;
        if (text && id) {
          const opt = Array.from(el.options).find((row) => row.value === id);
          if (opt && !opt.textContent) opt.textContent = text;
        }
        return;
      }
      if (id) el.dataset.code = id;
      else delete el.dataset.code;
      el.value = text;
    }

    function readControl(id) {
      const el = document.getElementById(id);
      if (!el) return '';
      if (el.type === 'checkbox') return el.checked ? 'Y' : 'N';
      return String(el.value || '').trim();
    }

    function writeControl(id, value) {
      const el = document.getElementById(id);
      if (!el) return;
      if (el.type === 'checkbox') {
        el.checked = String(value || '').toUpperCase() === 'Y';
        return;
      }
      const raw = value == null ? '' : String(value);
      if (el.tagName === 'SELECT') {
        const trimmed = raw.trim();
        const numeric = (el.id === 'std_flughoehe' || el.id === 'create_std_flughoehe')
          ? ((trimmed.match(/\d+/) || [''])[0])
          : trimmed;
        if (numeric && !Array.from(el.options).some((row) => row.value === numeric)) {
          const opt = document.createElement('option');
          opt.value = numeric;
          opt.textContent = numeric;
          el.appendChild(opt);
        }
        el.value = numeric;
        return;
      }
      el.value = raw;
      if (id === 'suchfeld_bild' || id === 'create_suchfeld_bild' || id === 'flugplan_bild' || id === 'create_flugplan_bild') {
        renderStoredPictures(id);
      }
    }

    // Feldkatalog: Mavic 3T, Matrice 4TD, Mavic 3T Debug.
    // Matrice 4T, Matrice 3TD und Mavic 3TA haben keinen Feldkatalog.
    // Mavic II und Matrice 30T sind keine wählbare Mission mehr.
    const editorCatalogNoteText = 'Feldkatalog: Mavic 3T, Matrice 4TD und Mavic 3T Debug. Matrice 4T, Matrice 3TD und Mavic 3TA haben keinen Feldkatalog. Mavic II und Matrice 30T sind aus der Auswahl entfernt. Ein neuer Katalog wird nur in der Website-Konfiguration angelegt.';

    function editorMissionKey() {
      const name = String((els.msDroneType && els.msDroneType.value) || '').toLowerCase().replace(/[\s_-]/g, '');
      if (name === 'd' || name.includes('debug')) return 'D';
      if (name.includes('4td')) return '4TD';
      if (name.includes('30t') || name.includes('mavic2')) return '3T';
      if (name.includes('3ta') || name.includes('m3ta')) return '3TA';
      if (name.includes('3td') || name.includes('m3td')) return '3TD';
      if (name.includes('4t') || name.includes('m4t')) return '4T';
      if (name === '3t' || name === 'm3t' || name === 'mavic3t') return '3T';
      return '3T';
    }

    function editorHasCatalog() {
      const key = editorMissionKey();
      return key === '3T' || key === '4TD' || key === 'D';
    }

    function droneOptionLabel(name) {
      const raw = String(name || '').trim();
      const saved = editorMissionKey;
      const keyName = raw.toLowerCase().replace(/[\s_-]/g, '');
      const has = keyName.includes('debug')
        || keyName.includes('4td')
        || keyName === '3t' || keyName === 'm3t' || keyName === 'mavic3t';
      return raw + (has ? ' · Feldkatalog' : ' · kein Feldkatalog');
    }

    function syncCatalogOriginUi() {
      if (!window.RKRK_KAT) return;
      const has = editorHasCatalog();
      const ex = document.getElementById('planOriginExisting');
      if (ex) ex.hidden = !has;
      if (!has) {
        const pick = document.getElementById('planExistingPick');
        if (pick) pick.hidden = true;
      }
    }

    function onEditorDroneCatalogChange() {
      if (!window.RKRK_KAT) return;
      syncCatalogOriginUi();
      if (!editorHasCatalog()) {
        allFields = [];
        selectedId = '';
        renderList();
        paintDroneCatalogNote({ catalog: false, detail: 'Dieser Drohnentyp hat keinen Feldkatalog. Es wird ein neues Feld gestartet.' });
        startNewFieldFromScratch().catch((e) => showStatus(String(e.message || e), false));
        scheduleFeldIdPlace(document.getElementById('planFeldId') && document.getElementById('planFeldId').value);
        return;
      }
      loadList().catch(() => {});
      scheduleFeldIdPlace(document.getElementById('planFeldId') && document.getElementById('planFeldId').value);
    }

    function editorMissionFolder() {
      const key = editorMissionKey();
      if (key === '4TD') return 'Missionen Matrice 4TD';
      if (key === 'D') return 'Missionen Debug';
      return 'Missionen';
    }

    let feldIdPlaceTimer = 0;
    let feldIdPlaceSeq = 0;

    function paintFeldIdPlace(text, kind) {
      ['planFeldIdPlace', 'feldIdPlace', 'createFeldIdPlace'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.hidden = !text;
        el.textContent = text || '';
        el.classList.toggle('is-free', kind === 'frei');
        el.classList.toggle('is-taken', kind === 'vergeben' || kind === 'error');
      });
    }

    function scheduleFeldIdPlace(raw) {
      const feldId = String(raw == null ? '' : raw).trim();
      window.clearTimeout(feldIdPlaceTimer);
      if (!window.RKRK_KAT || feldId === '') {
        paintFeldIdPlace('', '');
        return;
      }
      const seq = ++feldIdPlaceSeq;
      feldIdPlaceTimer = window.setTimeout(() => {
        fetch(editorApiUrl({ action: 'feld_place', feld_id: feldId }), { cache: 'no-store' })
          .then((response) => response.json())
          .then((data) => {
            if (seq !== feldIdPlaceSeq) return;
            if (!data || data.ok !== true) {
              paintFeldIdPlace('Katalogprüfung fehlgeschlagen', 'error');
              return;
            }
            const where = String(data.directory || '').trim() || 'Verzeichnis unbekannt';
            if (data.state !== 'frei' && data.state !== 'vergeben') {
              paintFeldIdPlace(where + ' · kein Feldkatalog', 'error');
              return;
            }
            paintFeldIdPlace(where + ' · ' + data.state, data.state);
          })
          .catch(() => {
            if (seq !== feldIdPlaceSeq) return;
            paintFeldIdPlace('Katalogprüfung fehlgeschlagen', 'error');
          });
      }, 250);
    }

    function reserveToken() {
      const key = 'rkrkFeldReserveToken';
      let token = '';
      try { token = sessionStorage.getItem(key) || ''; } catch (_) {}
      if (!/^[A-Za-z0-9_-]{8,80}$/.test(token)) {
        const bytes = new Uint8Array(16);
        if (window.crypto && crypto.getRandomValues) crypto.getRandomValues(bytes);
        token = Array.from(bytes).map((b) => b.toString(16).padStart(2, '0')).join('');
        if (!token || /^0+$/.test(token)) token = 't' + Date.now().toString(36) + Math.floor(Math.random() * 1e8).toString(36);
        try { sessionStorage.setItem(key, token); } catch (_) {}
      }
      return token;
    }

    function editorApiUrl(extra) {
      const q = new URLSearchParams();
      if (window.RKRK_KAT) q.set('KAT', '');
      if (window.RKRK_ADMIN) q.set('ADMIN', '');
      q.set('MDB', editorMissionKey());
      if (extra && typeof extra === 'object') {
        Object.keys(extra).forEach((name) => {
          if (extra[name] != null && String(extra[name]) !== '') q.set(name, String(extra[name]));
        });
      }
      return 'api_field_editor.php?' + q.toString();
    }

    function releaseReservation() {
      if (!window.RKRK_KAT) return;
      const payload = JSON.stringify({ mode: 'release', token: reserveToken(), mdb: editorMissionKey() });
      const url = editorApiUrl();
      try {
        const blob = new Blob([payload], { type: 'application/json' });
        if (navigator.sendBeacon && navigator.sendBeacon(url, blob)) return;
      } catch (_) {}
      fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: payload, keepalive: true }).catch(() => {});
    }

    function missionPictureUrl(rel) {
      const path = String(rel || '').trim();
      if (!path) return '';
      if (/^(https?:)?\/\//i.test(path)) return path;
      const clean = path.replace(/\\/g, '/').replace(/^\/+/, '');
      if (!clean || clean.includes('..')) return '';
      return '../../' + editorMissionFolder().split('/').map((part) => encodeURIComponent(part)).join('/') + '/' + clean.split('/').map((part) => encodeURIComponent(part)).join('/');
    }

    function renderStoredPictures(inputId) {
      const input = document.getElementById(inputId);
      const box = document.getElementById(inputId + '_view');
      if (!input || !box) return;
      box.replaceChildren();
      String(input.value || '').split(',').map((part) => part.trim()).filter(Boolean).forEach((rel) => {
        const url = missionPictureUrl(rel);
        if (!url) return;
        const fresh = url + (url.indexOf('?') >= 0 ? '&' : '?') + 'v=' + Date.now();
        const link = document.createElement('a');
        link.href = fresh;
        link.target = '_blank';
        link.rel = 'noopener';
        const img = document.createElement('img');
        img.alt = rel.split('/').pop() || 'Bild';
        img.title = img.alt;
        img.src = fresh;
        link.appendChild(img);
        box.appendChild(link);
      });
    }

    function fillChoiceSelect(id, rows, emptyLabel) {
      const el = document.getElementById(id);
      if (!el) return;
      const current = el.value;
      el.innerHTML = '';
      if (emptyLabel) {
        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = emptyLabel;
        el.appendChild(blank);
      }
      (rows || []).forEach((row) => {
        const opt = document.createElement('option');
        opt.value = String(row.value || '');
        opt.textContent = String(row.label || row.value || '');
        el.appendChild(opt);
      });
      if (current) writeControl(id, current);
    }

    async function loadCatalogChoices() {
      try {
        const r = await fetch(editorApiUrl({ action: 'choices' }), { cache: 'no-store' });
        const data = await r.json();
        if (!data.ok || !data.choices) return;
        const c = data.choices;
        ['jg', 'create_jg'].forEach((id) => fillChoiceSelect(id, c.jg, ''));
        ['gemeinde', 'create_gemeinde'].forEach((id) => fillChoiceSelect(id, c.gemeinde, ''));
        ['freigabe', 'create_freigabe'].forEach((id) => fillChoiceSelect(id, c.freigabe, ''));
        ['jaeger', 'create_jaeger'].forEach((id) => fillChoiceSelect(id, c.jaeger, '—'));
        ['std_flughoehe', 'create_std_flughoehe'].forEach((id) => fillChoiceSelect(id, c.hoehe, ''));
        const list = document.getElementById('warnungOptions');
        if (list) {
          list.innerHTML = '';
          (c.warnung || []).forEach((row) => {
            const opt = document.createElement('option');
            opt.value = String(row.value || '');
            opt.label = String(row.label || row.value || '');
            list.appendChild(opt);
          });
        }
      } catch (_) {}
    }

    const catalogModalPairs = [
      ['create_flurname', 'flurname'],
      ['create_grundstueck_id', 'grundstueck_id'],
      ['create_betrieb', 'betrieb'],
      ['create_freigabe', 'freigabe'],
      ['create_warnung', 'warnung'],
      ['create_jaeger', 'jaeger'],
      ['create_bemerkung', 'bemerkung'],
      ['create_flug', 'flug'],
      ['create_funde', 'funde'],
      ['create_flaeche', 'flaeche'],
      ['create_bauer', 'bauer'],
      ['create_notiz', 'notiz'],
      ['create_suchfeld_bild', 'suchfeld_bild'],
      ['create_flugplan_bild', 'flugplan_bild'],
      ['create_jason_file', 'jason_file'],
      ['create_kml_file', 'kml_file'],
      ['create_kmz_file', 'kmz_file'],
      ['create_parkplatz', 'parkplatz'],
      ['create_zentrum', 'zentrum'],
      ['create_erstellt', 'erstellt'],
      ['create_aenderung', 'aenderung'],
      ['create_export_yn', 'export_yn'],
      ['create_select_yn', 'select_yn'],
    ];

    function copyCatalogFormToModal() {
      catalogModalPairs.forEach(([modalId, formId]) => writeControl(modalId, readControl(formId)));
    }

    function copyCatalogModalToForm() {
      catalogModalPairs.forEach(([modalId, formId]) => writeControl(formId, readControl(modalId)));
      const plan = document.getElementById('planGis');
      const gis = document.getElementById('grundstueck_id');
      if (plan && gis) plan.value = gis.value;
    }

    function setForm(v) {
      els.id.value = v.id || '';
      els.number.value = v.number || '';
      els.feld_id.value = v.feld_id || '';
      els.name.value = v.name || '';
      const gisValue = String(v.grundstueck_id || v.gis || '').trim();
      const gisEl = document.getElementById('planGis');
      const gisForm = document.getElementById('grundstueck_id');
      if (gisEl) gisEl.value = gisValue;
      if (gisForm) gisForm.value = gisValue;
      syncPlanFieldNameFromForm();
      syncPlanGisNote();
      showPlace(els.jg, v.jg || '', v.jg_name || v.jg || '');
      showPlace(els.gemeinde, v.gemeinde || '', v.gemeinde_name || v.gemeinde || '');
      writeControl('freigabe', v.freigabe || (String(v.id || '').trim() ? '' : 'NEU'));
      writeControl('jaeger', v.jaeger || '');
      writeControl('export_yn', v.export || 'Y');
      writeControl('select_yn', v.select || 'Y');
      ['flurname', 'flaeche', 'betrieb', 'warnung', 'bemerkung', 'flug', 'funde', 'bauer', 'notiz', 'suchfeld_bild', 'flugplan_bild', 'jason_file', 'kml_file', 'kmz_file', 'parkplatz', 'zentrum', 'erstellt', 'aenderung', 'katalog_update'].forEach((id) => {
        writeControl(id, v[id] || '');
      });
      writeControl('std_flughoehe', v.std_flughoehe || '50');
      els.flugzeit.value = v.flugzeit || '0';
      els.distanz.value = v.distanz || '0';
      els.wegpunkte.value = v.wegpunkte || '';
      els.startplatz.value = v.startplatz || '';
      els.flaechendaten.value = v.flaechendaten || '';
      els.flugweg.value = v.flugweg || '';
      routeCoverageReady = parseTriplets(v.flugweg || '').length >= 2;
      normalizeStoredPlacePoints();
      originalManualPath = parseTriplets(v.flugweg || '');
      loadWaypointSettingsFromField(v);
      const release = String(v.freigabe || '').trim().toUpperCase();
      applyStoredMissionSettings(v.mission_settings);
      pushHistory('Feld geladen');
      markFieldAsSaved();
      scheduleGisSuggestion();
      scheduleFeldIdPlace(els.feld_id && els.feld_id.value);
    }

    function parsePairs(raw) {
      const nums = String(raw || '').match(/-?\d+(?:\.\d+)?/g) || [];
      const out = [];
      for (let i = 0; i + 1 < nums.length; i += 2) {
        out.push([Number(nums[i]), Number(nums[i + 1])]);
      }
      return out;
    }

    function deriveJgFromFeldId(feldId) {
      const s = String(feldId || '').trim();
      const m = s.match(/^([^-\s]+)\s*-/);
      return m ? String(m[1]).trim() : '';
    }

    function syncJgFromFeldId(target = 'main') {
      if (target === 'create') {
        if (!els.create_feld_id || !els.create_jg) return;
        const jg = deriveJgFromFeldId(els.create_feld_id.value);
        if (jg) els.create_jg.value = jg;
        return;
      }
      if (!els.feld_id || !els.jg) return;
      const jg = deriveJgFromFeldId(els.feld_id.value);
      if (jg) els.jg.value = jg;
    }

    function parseTriplets(raw) {
      const nums = String(raw || '').match(/-?\d+(?:\.\d+)?/g) || [];
      const out = [];
      for (let i = 0; i + 2 < nums.length; i += 3) {
        out.push([Number(nums[i]), Number(nums[i + 1]), Number(nums[i + 2])]);
      }
      return out;
    }

    function formatTriplets(path) {
      const pts = normalizeTripletPath(path);
      return pts.map((p) => `${Number(p[0]).toFixed(13).replace(/0+$/,'').replace(/\.$/,'')}, ${Number(p[1]).toFixed(13).replace(/0+$/,'').replace(/\.$/,'')}, ${Number(p[2] ?? 0).toFixed(2).replace(/0+$/,'').replace(/\.$/,'')}`).join('     ');
    }

    function normalizeTripletPath(path) {
      const pts = Array.isArray(path) ? path : [];
      return pts
        .map((p) => [Number(p[0]), Number(p[1]), Number(p[2] ?? 0)])
        .filter((p) => Number.isFinite(p[0]) && Number.isFinite(p[1]));
    }

    function isSkipTurnWaypointType(rawType) {
      const s = String(rawType || '').toLowerCase().trim();
      return s === 'koordinierte drehung, ueberspringt den wegpunkt'
        || s === 'koordinierte drehung, überspringt den wegpunkt';
    }

    async function loadCoreCalcParams() {
      if (coreCalcParamsLoaded) return;
      try {
        const r = await fetch('requirements_data.json', { cache: 'no-store' });
        const data = await r.json();
        if (!data || !Array.isArray(data.core_parameters)) return;
        data.core_parameters.forEach((p) => {
          const key = String(p && p.key || '').trim();
          const val = Number(p && p.value);
          if (!Number.isFinite(val)) return;
          if (key === 'turn_delay_s') coreCalcParams.turn_delay_s = val;
          if (key === 'turn_delay_skip_wp_s') coreCalcParams.turn_delay_skip_wp_s = val;
          if (key === 'agl_tolerance_m') coreCalcParams.agl_tolerance_m = val;
          if (key === 'agl_display_deviation_m') coreCalcParams.agl_display_deviation_m = val;
        });
        coreCalcParamsLoaded = true;
        paintAglDeviationTitles();
      } catch (_) {
        // Keep defaults when requirements file is temporarily unavailable.
      }
    }

    function turnDelaySecondsByWpType(wpType) {
      const normal = Number(coreCalcParams.turn_delay_s);
      const skip = Number(coreCalcParams.turn_delay_skip_wp_s);
      const normalSec = Number.isFinite(normal) ? normal : 5.5;
      const skipSec = Number.isFinite(skip) ? skip : 1.0;
      return isSkipTurnWaypointType(wpType) ? skipSec : normalSec;
    }

    function currentAglToleranceValue() {
      const shown = Number(coreCalcParams.agl_display_deviation_m);
      if (Number.isFinite(shown) && shown > 0) return shown;
      const tol = Number(coreCalcParams.agl_tolerance_m);
      return Number.isFinite(tol) && tol > 0 ? tol : 10.0;
    }

    function paintAglDeviationTitles() {
      const title = 'Roter Punkt, wo die reale Flughöhe über Grund von der Sollflughöhe um mehr als ±' + currentAglToleranceValue() + ' m abweicht.';
      ['showAglLimit', 'planShowAglLimit'].forEach((id) => {
        const el = document.getElementById(id);
        const host = el && (el.closest('label') || el);
        if (host) host.title = title;
      });
    }

    function syncPathEditFeatures() {
      if (!pathEditFeatures || !manualPathSource) return;
      pathEditFeatures.clear();
      manualPathSource.getFeatures().forEach((f) => {
        if (f.getGeometry() instanceof ol.geom.Point && Number(f.get('pathIdx')) > 0) pathEditFeatures.push(f);
      });
    }

    function yawIsDefinedMode(mode) {
      const text = String(mode || '');
      return /^definiert$/i.test(text) || /^customi[sz]ed$/i.test(text);
    }

    function yawIsLockedAxis(mode) {
      const text = String(mode || '');
      return /schenk|schwenk/i.test(text) || /^fixed$/i.test(text) || /^lock$/i.test(text);
    }

    function headingModeUsesFixedAngle(mode) {
      return yawIsDefinedMode(mode);
    }

    function storedLegBearingDeg(mapCoords, index0) {
      const n = Array.isArray(mapCoords) ? mapCoords.length : 0;
      if (n < 2 || index0 < 0 || index0 >= n) return 0;
      let from = mapCoords[index0];
      let to = mapCoords[index0 + 1];
      if (!to && index0 > 0) {
        from = mapCoords[index0 - 1];
        to = mapCoords[index0];
      }
      if (!from || !to) return 0;
      const dx = Number(to[0]) - Number(from[0]);
      const dy = Number(to[1]) - Number(from[1]);
      if (!(Math.hypot(dx, dy) > 1e-6)) return 0;
      return (Math.atan2(dx, dy) * 180 / Math.PI + 360) % 360;
    }

    function programmedHeightForIndex(index1, pathZ) {
      const row = waypointOverrides && waypointOverrides[String(index1)];
      const mission = Number(els.msAltitude && els.msAltitude.value);
      const missionHeight = Number.isFinite(mission) ? mission : Number(pathZ);
      if (window.RkrkField && typeof RkrkField.programmedFlightHeightM === 'function') {
        return RkrkField.programmedFlightHeightM(missionHeight, row, pathZ);
      }
      return Number.isFinite(Number(pathZ)) ? Number(pathZ) : missionHeight;
    }

    function yawDisplayForIndex(index1, mapCoords) {
      const row = (waypointOverrides && waypointOverrides[String(index1)]) || {};
      const missionMode = String(els.msHeadingMode && els.msHeadingMode.value || 'Richtung zum naechsten Wegpunkt');
      const yawFlag = Object.prototype.hasOwnProperty.call(row, 'use_mission_yaw') ? jsonFlag(row.use_mission_yaw) : null;
      const useMission = yawFlag !== false;
      const mode = useMission ? missionMode : String(row.heading_mode || missionMode);
      let angle = null;
      if (!useMission) {
        const rawAngle = row.heading_angle != null && row.heading_angle !== '' ? row.heading_angle : row.direction_deg;
        if (rawAngle != null && rawAngle !== '' && String(rawAngle).toLowerCase() !== 'null' && Number.isFinite(Number(rawAngle))) {
          angle = Number(rawAngle);
        }
      }
      if (/poi|fokus/i.test(mode)) {
        const own = (!useMission && row.poi && typeof row.poi === 'object') ? row.poi : null;
        const poi = own || (missionPois.length === 1 ? missionPois[0] : null);
        const xy = mapCoords && mapCoords[index1 - 1];
        const lon = Number(poi && poi.longitude);
        const lat = Number(poi && poi.latitude);
        if (xy && Number.isFinite(lon) && Number.isFinite(lat) && window.RkrkField && typeof RkrkField.bearingDegLonLat === 'function') {
          const ll = ol.proj.toLonLat(xy);
          angle = RkrkField.bearingDegLonLat(ll[0], ll[1], lon, lat);
        }
      }
      const next = window.RkrkField && typeof RkrkField.nextLegBearingDeg === 'function'
        ? RkrkField.nextLegBearingDeg(mapCoords, index1 - 1)
        : storedLegBearingDeg(mapCoords, index1 - 1);
      return { mode: mode, angle: angle, next: next };
    }

    function cameraHeadingTriangleStyles(feature, resolution, fillColor) {
      const g = feature && feature.getGeometry && feature.getGeometry();
      if (!(g instanceof ol.geom.Point) || !window.RkrkField || typeof RkrkField.yawSymbolLayout !== 'function') return [];
      const layout = RkrkField.yawSymbolLayout(
        feature.get('yawMode'),
        feature.get('yawAngleDeg'),
        feature.get('yawNextLegDeg'),
        g.getCoordinates(),
        resolution
      );
      if (!layout || layout.kind === 'none') return [];
      if (layout.kind === 'x') {
        return (layout.lines || []).map((line) => new ol.style.Style({
          geometry: new ol.geom.LineString(line),
          stroke: new ol.style.Stroke({ color: layout.color || '#e23d2b', width: 2.4 }),
        }));
      }
      if (layout.kind !== 'triangle' || !layout.ring) return [];
      return [
        new ol.style.Style({
          geometry: new ol.geom.Polygon([layout.ring]),
          fill: new ol.style.Fill({ color: fillColor || '#2f6dff' }),
          stroke: new ol.style.Stroke({ color: '#ffffff', width: 1.4 }),
        }),
      ];
    }

    let wpDebugPaintGen = 0;

    function debugHtml(text) {
      return String(text == null ? '' : text).replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
      }[ch]));
    }

    function debugMeter1(value, signed) {
      const n = Number(value);
      if (!Number.isFinite(n)) return '–';
      const tenth = Math.round(n * 10) / 10;
      const text = tenth.toFixed(1);
      return signed && tenth > 0 ? ('+' + text) : text;
    }

    function yawFormLabel(mode) {
      const raw = String(mode || '');
      const sel = els.wpHeadingMode || els.msHeadingMode;
      if (sel && sel.options) {
        const opt = Array.from(sel.options).find((item) => item.value === raw);
        if (opt && opt.textContent) return String(opt.textContent).trim();
      }
      return raw;
    }

    function gimbalDebugText(row) {
      const n = Number(row && row.gimbal_pitch);
      const pitch = Number.isFinite(n) ? Math.round(n) : -90;
      return (pitch < 0 ? '\u2212' : '') + String(Math.abs(pitch)) + '\u00b0';
    }

    function yawDebugText(index1, mapCoords) {
      const yaw = yawDisplayForIndex(index1, mapCoords);
      let label = yawFormLabel(yaw && yaw.mode);
      if (yawIsDefinedMode(yaw && yaw.mode) && Number.isFinite(Number(yaw.angle))) {
        const tenth = Math.round(Number(yaw.angle) * 10) / 10;
        const ang = Math.abs(tenth - Math.round(tenth)) < 0.001 ? String(Math.round(tenth)) : tenth.toFixed(1);
        label = (label || 'Definiert') + ' ' + ang + '\u00b0';
      }
      return label || '–';
    }

    function paintWaypointDebugTable(pathTriplets, terrains, startTerrain) {
      const box = document.getElementById('wpDebugTableBox');
      const body = document.getElementById('wpDebugTableBody');
      if (!box || !body) return;
      if (!editorDebugOn()) {
        box.dataset.ready = '0';
        body.innerHTML = '';
        syncWaypointDebugBoxVisibility();
        return;
      }
      const pts = normalizeTripletPath(pathTriplets);
      if (pts.length < 2) {
        box.dataset.ready = '0';
        body.innerHTML = '';
        syncWaypointDebugBoxVisibility();
        return;
      }
      const pending = !Array.isArray(terrains);
      const ground = pending ? [] : terrains;
      const coords = pts.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const rows = pts.map((p, idx) => {
        const terrain = Number(ground[idx]);
        const delta = Number.isFinite(terrain) && Number.isFinite(Number(startTerrain)) ? (terrain - Number(startTerrain)) : NaN;
        const programmed = programmedHeightForIndex(idx + 1, Number(p[2]));
        const row = (waypointOverrides && waypointOverrides[String(idx + 1)]) || {};
        let relText = '';
        let mainText = Number.isFinite(programmed) ? (programmed + ' m') : '–';
        if (!pending && window.RkrkField && typeof RkrkField.heightCaption === 'function') {
          const caption = RkrkField.heightCaption(programmed, NaN, NaN);
          mainText = caption.main || mainText;
        }
        if (!pending) {
          const rel = window.RkrkField && typeof RkrkField.relativeHeightFromStartM === 'function'
            ? RkrkField.relativeHeightFromStartM(programmed, terrain, startTerrain)
            : (Number.isFinite(programmed) && Number.isFinite(terrain) && Number.isFinite(Number(startTerrain))
              ? (programmed + (terrain - Number(startTerrain)))
              : NaN);
          if (Number.isFinite(rel)) relText = (Math.round(rel * 10) / 10).toFixed(1) + ' m';
        }
        if (!pending && window.RkrkField && typeof RkrkField.formatMeterLabel === 'function' && Number.isFinite(programmed)) {
          mainText = RkrkField.formatMeterLabel(programmed) || mainText;
        }
        return '<tr>'
          + '<td class="num">' + (idx + 1) + '</td>'
          + '<td class="num">' + debugHtml(pending ? '…' : debugMeter1(terrain, false)) + '</td>'
          + '<td class="num">' + debugHtml(pending ? '…' : debugMeter1(delta, true)) + '</td>'
          + '<td class="num">' + debugHtml(mainText) + '</td>'
          + '<td class="num">' + debugHtml(relText) + '</td>'
          + '<td>' + debugHtml(yawDebugText(idx + 1, coords)) + '</td>'
          + '<td class="num">' + debugHtml(gimbalDebugText(row)) + '</td>'
          + '</tr>';
      });
      box.dataset.ready = '1';
      body.innerHTML = rows.join('');
      syncWaypointDebugBoxVisibility();
    }

    function syncWaypointDebugBoxVisibility() {
      const box = document.getElementById('wpDebugTableBox');
      const cb = document.getElementById('showBoxWaypointData');
      const label = document.getElementById('showBoxWaypointDataLabel');
      const debug = editorDebugOn();
      if (label) label.hidden = !debug;
      const coverageChoice = document.getElementById('coverageWindowChoice');
      if (coverageChoice) coverageChoice.hidden = !debug;
      if (!box) return;
      const ready = box.dataset.ready === '1';
      const allowed = debug && ready && !!(cb && cb.checked);
      box.hidden = !allowed;
    }

    function setPathSourceFromTriplets(source, path, pathKind = 'manual') {
      if (!source) return;
      const debugGen = pathKind === 'manual' ? ++wpDebugPaintGen : wpDebugPaintGen;
      if (pathKind === 'manual' && pathEditFeatures) pathEditFeatures.clear();
      source.clear();
      const pts = normalizeTripletPath(path);
      if (pts.length < 2) {
        if (pathKind === 'manual') paintWaypointDebugTable([], null, NaN);
        return;
      }
      const coords = pts.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      source.addFeature(new ol.Feature({ geometry: new ol.geom.LineString(coords) }));
      for (let i = 0; i < coords.length; i++) {
        const pf = new ol.Feature({ geometry: new ol.geom.Point(coords[i]) });
        pf.set('pathIdx', i + 1);
        pf.set('isSelectedWaypoint', rightDrawerMode === 'waypoint' && selectedWaypointIndex1 === (i + 1));
        const wpAlt = Number(pts[i] && pts[i][2]);
        const programmed = programmedHeightForIndex(i + 1, wpAlt);
        const rowForCaption = waypointOverrides && waypointOverrides[String(i + 1)];
        const caption = window.RkrkField && typeof RkrkField.heightCaption === 'function'
          ? RkrkField.heightCaption(programmed, NaN, NaN)
          : { main: Number.isFinite(programmed) ? (programmed + ' m') : '', paren: '' };
        pf.set('pathWpAlt', Number.isFinite(wpAlt) ? wpAlt : NaN);
        pf.set('pathBottomLabel', caption.main || '');
        pf.set('pathStartRelLabel', caption.paren || '');
        pf.set('pathGimbalPitch', rowForCaption && rowForCaption.gimbal_pitch);
        pf.set('pathTopLabel', '');
        const yaw = yawDisplayForIndex(i + 1, coords);
        pf.set('yawMode', yaw.mode);
        pf.set('yawAngleDeg', yaw.angle);
        pf.set('yawNextLegDeg', yaw.next);
        source.addFeature(pf);
        pf.set('pathDeviates', waypointHasLocalOverride(i + 1));
      }
      const showTurnPath = !!(els.showTurnCurves && els.showTurnCurves.checked);
      const missionTurn = Number(els.msTurnDistance && els.msTurnDistance.value);
      const missionTurnM = Number.isFinite(missionTurn) && missionTurn > 0 ? missionTurn : 0;
      const turnTypeAt = (idx) => {
        const row = waypointOverrides && waypointOverrides[String(idx + 1)];
        const missionType = String(els.msWaypointType && els.msWaypointType.value || '');
        if (row && row.use_mission_waypoint_type === false && row.waypoint_type) return String(row.waypoint_type);
        return missionType;
      };
      const turnDistAt = (idx) => {
        const row = waypointOverrides && waypointOverrides[String(idx + 1)];
        if (row && row.use_mission_turn_distance === false) {
          const local = Number(row.turning_distance_m);
          if (Number.isFinite(local) && local > 0) return local;
        }
        return missionTurnM;
      };
      if (showTurnPath && window.RkrkField && typeof RkrkField.coordinateTurnCoords === 'function') {
        for (let i = 1; i + 1 < pts.length; i++) {
          if (!isSkipTurnWaypointType(turnTypeAt(i))) continue;
          const guideLen = turnDistAt(i);
          if (!(guideLen > 0)) continue;
          const line = RkrkField.coordinateTurnCoords(
            ol.proj.fromLonLat([Number(pts[i - 1][0]), Number(pts[i - 1][1])]),
            ol.proj.fromLonLat([Number(pts[i][0]), Number(pts[i][1])]),
            ol.proj.fromLonLat([Number(pts[i + 1][0]), Number(pts[i + 1][1])]),
            guideLen
          );
          if (!line || line.length < 2) continue;
          const guide = new ol.Feature({ geometry: new ol.geom.LineString(line) });
          guide.set('isTurnPath', true);
          guide.set('pathKind', pathKind);
          source.addFeature(guide);
        }
      }
      // Separate markers for "Flugrichtung pro Sektor" (not waypoint numbers).
      for (let i = 0; i < coords.length - 1; i++) {
        const a = coords[i];
        const b = coords[i + 1];
        const mx = (a[0] + b[0]) * 0.5;
        const my = (a[1] + b[1]) * 0.5;
        const headingDeg = (Math.atan2(b[1] - a[1], b[0] - a[0]) * 180 / Math.PI + 360) % 360;
        const sf = new ol.Feature({ geometry: new ol.geom.Point([mx, my]) });
        sf.set('isSectionDirectionPoint', true);
        sf.set('sectionHeadingDeg', headingDeg);
        sf.set('sectionIdx', i + 1);
        source.addFeature(sf);
      }
      if (pathKind === 'manual') paintWaypointDebugTable(pts, null, NaN);
      updatePathPointHeightLabels(source, pts, debugGen).catch(() => {});
      if (pathKind === 'manual') syncPathEditFeatures();
    }

    function mapFont(px, bold) {
      if (window.RkrkField && typeof RkrkField.mapLabelFont === 'function') return RkrkField.mapLabelFont(px, !!bold);
      return (bold ? 'bold ' : '') + px + 'px Arial';
    }

    function pathGimbalStyle(feature) {
      const text = window.RkrkField && typeof RkrkField.specificGimbalLabel === 'function'
        ? RkrkField.specificGimbalLabel(feature.get('pathGimbalPitch'))
        : '';
      if (!text) return [];
      return [new ol.style.Style({
        text: new ol.style.Text({
          text: text,
          offsetY: 12,
          offsetX: 26,
          font: mapFont(9, false),
          fill: new ol.style.Fill({ color: '#1d4e89' }),
          stroke: new ol.style.Stroke({ color: '#fff', width: 2 }),
        }),
      })];
    }

    function pathStartRelStyle(feature) {
      const startRelText = String(feature.get('pathStartRelLabel') || '');
      if (!startRelText) return [];
      return [new ol.style.Style({
        text: new ol.style.Text({
          text: startRelText,
          offsetY: 30,
          font: mapFont(11, false),
          fill: new ol.style.Fill({ color: '#1d4e89' }),
          stroke: new ol.style.Stroke({ color: '#fff', width: 2.6 }),
        }),
      })];
    }

    async function updatePathPointHeightLabels(source, pathTriplets, paintGen) {
      if (!source) return;
      const pts = normalizeTripletPath(pathTriplets);
      if (!pts.length) return;
      const terrainByIdx = new Array(pts.length).fill(NaN);
      let startTerrain = NaN;
      const startPairs = parsePairs(els.startplatz && els.startplatz.value);
      const startLonLat = startPairs.length ? [startPairs[0][0], startPairs[0][1]] : null;
      const refLon = Array.isArray(startLonLat) ? startLonLat[0] : pts[0][0];
      const refLat = Array.isArray(startLonLat) ? startLonLat[1] : pts[0][1];
      try {
        startTerrain = await fetchGeoAdminTerrainHeight(refLon, refLat);
      } catch (_) {
        startTerrain = NaN;
      }
      const pointFeaturesByIdx = new Map();
      source.getFeatures()
        .filter((f) => Number(f.get('pathIdx')) > 0)
        .forEach((f) => {
          const idx = Number(f.get('pathIdx'));
          if (Number.isFinite(idx) && idx > 0) pointFeaturesByIdx.set(idx - 1, f);
        });
      const chunkSize = 20;
      for (let i = 0; i < pts.length; i += chunkSize) {
        const chunk = pts.slice(i, i + chunkSize);
        const terrains = await Promise.all(chunk.map(async (p) => {
          try {
            return await fetchGeoAdminTerrainHeight(p[0], p[1]);
          } catch (_) {
            return NaN;
          }
        }));
        terrains.forEach((terrain, j) => {
          const idx = i + j;
          terrainByIdx[idx] = terrain;
          const feat = pointFeaturesByIdx.get(idx);
          if (!feat) return;
          const programmed = programmedHeightForIndex(idx + 1, Number(pts[idx] && pts[idx][2]));
          const row = (waypointOverrides && waypointOverrides[String(idx + 1)]) || {};
          const terrainReady = Number.isFinite(Number(terrain)) && Number.isFinite(Number(startTerrain));
          const caption = window.RkrkField && typeof RkrkField.heightCaption === 'function'
            ? RkrkField.heightCaption(programmed, terrainReady ? terrain : NaN, terrainReady ? startTerrain : NaN)
            : { main: '', paren: '' };
          feat.set('pathBottomLabel', caption.main || feat.get('pathBottomLabel') || '');
          feat.set('pathStartRelLabel', caption.paren || '');
          feat.set('pathGimbalPitch', row.gimbal_pitch);
          feat.set('pathTopLabel', '');
        });
      }
      source.changed();
      if (source === manualPathSource && paintGen === wpDebugPaintGen) {
        paintWaypointDebugTable(pts, terrainByIdx, startTerrain);
      }
    }

    function distanceMetersLonLat(a, b) {
      if (!Array.isArray(a) || !Array.isArray(b) || a.length < 2 || b.length < 2) return 0;
      try {
        return Number(ol.sphere.getDistance([Number(a[0]), Number(a[1])], [Number(b[0]), Number(b[1])])) || 0;
      } catch (_) {
        const ax = ol.proj.fromLonLat([Number(a[0]), Number(a[1])]);
        const bx = ol.proj.fromLonLat([Number(b[0]), Number(b[1])]);
        const dx = bx[0] - ax[0];
        const dy = bx[1] - ax[1];
        const center = [(ax[0] + bx[0]) * 0.5, (ax[1] + bx[1]) * 0.5];
        const scale = meterToMapUnitScaleAt(center);
        return Math.hypot(dx, dy) / Math.max(1e-9, scale);
      }
    }

    function formatDurationMMSS(totalSeconds) {
      const sec = Math.max(0, Math.round(Number(totalSeconds) || 0));
      const mm = Math.floor(sec / 60);
      const ss = sec % 60;
      return `${String(mm).padStart(2, '0')}:${String(ss).padStart(2, '0')}`;
    }

    function clearFieldCharacteristicsDisplay() {
      if (els.fcSource) els.fcSource.textContent = '-';
      if (els.fcPathLength) els.fcPathLength.textContent = '-';
      if (els.fcClimbUp) els.fcClimbUp.textContent = '-';
      if (els.fcClimbDown) els.fcClimbDown.textContent = '-';
      if (els.fcFlightTime) els.fcFlightTime.textContent = '-';
    }

    function extractTurnWaypointIndices(metrics) {
      const out = new Set();
      const m = metrics && typeof metrics === 'object' ? metrics : {};
      const candidates = [];
      if (Array.isArray(m.waypoints)) candidates.push(m.waypoints);
      if (m.timing && Array.isArray(m.timing.waypoints)) candidates.push(m.timing.waypoints);
      if (m.turn_analysis && Array.isArray(m.turn_analysis.waypoints)) candidates.push(m.turn_analysis.waypoints);
      candidates.forEach((rows) => {
        rows.forEach((row) => {
          if (!row || typeof row !== 'object') return;
          const isTurn = !!row.is_turn;
          const idx = Number(row.index);
          if (!isTurn || !Number.isFinite(idx)) return;
          out.add(Math.max(1, Math.round(idx + 1)));
        });
      });
      return out;
    }

    function selectedPathForCharacteristics() {
      const path = normalizeTripletPath(workingFlightPath());
      if (path.length >= 2) return { source: 'Flugpfad', path };
      return { source: '-', path: [] };
    }

    function scheduleFieldCharacteristicsRefresh() {
      if (fieldCharacteristicsRefreshTimer) clearTimeout(fieldCharacteristicsRefreshTimer);
      fieldCharacteristicsRefreshTimer = setTimeout(() => {
        refreshFieldCharacteristics().catch(() => {});
      }, 120);
    }

    async function refreshFieldCharacteristics() {
      await loadCoreCalcParams();
      const token = ++fieldCharacteristicsRefreshToken;
      const selected = selectedPathForCharacteristics();
      const path = selected.path;
      if (!path.length) {
        clearFieldCharacteristicsDisplay();
        return;
      }
      if (els.fcSource) els.fcSource.textContent = selected.source;

      let distanceM = 0;
      for (let i = 0; i + 1 < path.length; i++) {
        distanceM += distanceMetersLonLat(path[i], path[i + 1]);
      }
      if (els.fcPathLength) els.fcPathLength.textContent = `${distanceM.toFixed(1)} m`;
      const speedMpsNow = Number(els.msSpeed && els.msSpeed.value);
      const startSpeedActiveNow = !!(els.msStartSpeedActive && els.msStartSpeedActive.checked);
      const startSpeedMpsNow = Number(els.msStartSpeed && els.msStartSpeed.value);
      const perWpPauseSecNow = turnDelaySecondsByWpType(String(els.msWaypointType && els.msWaypointType.value || ''));
      let flightSecondsNow = path.length * perWpPauseSecNow;
      for (let i = 0; i + 1 < path.length; i++) {
        const segSpeed = (i === 0 && startSpeedActiveNow) ? startSpeedMpsNow : speedMpsNow;
        if (Number.isFinite(segSpeed) && segSpeed > 1e-6) {
          flightSecondsNow += distanceMetersLonLat(path[i], path[i + 1]) / segSpeed;
        }
      }
      if (els.fcFlightTime) els.fcFlightTime.textContent = formatDurationMMSS(flightSecondsNow);

      const startPts = parsePairs(els.startplatz && els.startplatz.value);
      const startLonLat = startPts.length ? [startPts[0][0], startPts[0][1]] : [path[0][0], path[0][1]];
      const wpLonLat = path.map((p) => [p[0], p[1]]);
      const terrainRel = await computeControlPointRelativeAgl(wpLonLat, startLonLat);
      if (token !== fieldCharacteristicsRefreshToken) return;
      const aslRel = path.map((p, i) => {
        const relTerrain = Number(terrainRel[i]);
        const agl = Number(p[2]) || 0;
        return (Number.isFinite(relTerrain) ? relTerrain : 0) + agl;
      });
      let climbUpM = 0;
      let climbDownM = 0;
      for (let i = 0; i + 1 < aslRel.length; i++) {
        const da = Number(aslRel[i + 1]) - Number(aslRel[i]);
        if (!Number.isFinite(da)) continue;
        if (da > 0) climbUpM += da;
        else climbDownM += Math.abs(da);
      }
      if (els.fcClimbUp) els.fcClimbUp.textContent = `${climbUpM.toFixed(1)} m`;
      if (els.fcClimbDown) els.fcClimbDown.textContent = `${climbDownM.toFixed(1)} m`;

      const speedMps = Number(els.msSpeed && els.msSpeed.value);
      const startSpeedActive = !!(els.msStartSpeedActive && els.msStartSpeedActive.checked);
      const startSpeedMps = Number(els.msStartSpeed && els.msStartSpeed.value);
      const wpType = String(els.msWaypointType && els.msWaypointType.value || '');
      const perWpPauseSec = turnDelaySecondsByWpType(wpType);

      let flightSeconds = 0;
      for (let i = 0; i + 1 < path.length; i++) {
        const d = distanceMetersLonLat(path[i], path[i + 1]);
        const segSpeed = (i === 0 && startSpeedActive) ? startSpeedMps : speedMps;
        if (Number.isFinite(segSpeed) && segSpeed > 1e-6) {
          flightSeconds += d / segSpeed;
        }
      }
      if (path.length > 0) flightSeconds += path.length * perWpPauseSec;
      if (els.fcFlightTime) els.fcFlightTime.textContent = formatDurationMMSS(flightSeconds);
    }

    function refreshPathMidpointHandles() {
      if (!pathMidpointLayer || !pathMidpointLayer.getVisible()) return;
      const src = pathMidpointLayer.getSource();
      if (!src) return;
      src.clear();
      const pts = normalizeTripletPath(workingFlightPath());
      if (pts.length < 2) return;
      for (let i = 0; i < pts.length - 1; i++) {
        const a = ol.proj.fromLonLat([pts[i][0], pts[i][1]]);
        const b = ol.proj.fromLonLat([pts[i + 1][0], pts[i + 1][1]]);
        const mx = (a[0] + b[0]) * 0.5;
        const my = (a[1] + b[1]) * 0.5;
        const f = new ol.Feature({ geometry: new ol.geom.Point([mx, my]) });
        f.set('insertIndex', i + 1);
        src.addFeature(f);
      }
    }

    function cloneTripletPath(path) {
      return normalizeTripletPath(path).map((p) => [p[0], p[1], p[2]]);
    }

    function pathsNearlyEqual(a, b) {
      const aa = normalizeTripletPath(a);
      const bb = normalizeTripletPath(b);
      if (aa.length !== bb.length) return false;
      for (let i = 0; i < aa.length; i++) {
        if (Math.abs(aa[i][0] - bb[i][0]) > 1e-7) return false;
        if (Math.abs(aa[i][1] - bb[i][1]) > 1e-7) return false;
        if (Math.abs((aa[i][2] || 0) - (bb[i][2] || 0)) > 0.05) return false;
      }
      return true;
    }

    function updatePathResetButton() {
      const btn = document.getElementById('planResetPath');
      if (!btn) return;
      btn.hidden = !(pathDivergedFromGenerated && generatedPathSnapshot.length >= 2);
    }

    let planPhase = 'outline';
    let parkEditMarked = false;

    function workingFlightPath() {
      if (pathDivergedFromGenerated && normalizeTripletPath(currentManualPath).length >= 2) return currentManualPath;
      if (normalizeTripletPath(currentComputedPath).length >= 2) return currentComputedPath;
      if (normalizeTripletPath(currentManualPath).length >= 2) return currentManualPath;
      return originalManualPath.length ? originalManualPath : currentManualPath;
    }

    function armOptimizePathEdit() {
      if (!map || planPhase !== 'optimize' || measureActive) return;
      const pts = normalizeTripletPath(workingFlightPath());
      if (pts.length < 2) return;
      if (!pathDivergedFromGenerated || normalizeTripletPath(currentManualPath).length < 2) {
        currentManualPath = pts.map((p) => [p[0], p[1], p[2]]);
        if (els.flugweg) els.flugweg.value = formatTriplets(currentManualPath);
      }
      isPathEditModeActive = true;
      if (els.showManualPath) els.showManualPath.checked = true;
      if (pathModifyInteraction) {
        pathModifyInteraction.setActive(true);
        if (map) {
          map.removeInteraction(pathModifyInteraction);
          map.addInteraction(pathModifyInteraction);
        }
      }
      if (pathMidpointLayer) pathMidpointLayer.setVisible(true);
      syncPathEditFeatures();
      refreshPathMidpointHandles();
      setMapDoubleClickZoom(false);
    }

    function setMapDoubleClickZoom(on) {
      if (!map) return;
      map.getInteractions().forEach((interaction) => {
        if (interaction instanceof ol.interaction.DoubleClickZoom) interaction.setActive(!!on);
      });
    }

    function disarmOptimizePathEdit() {
      if (planPhase === 'optimize') return;
      isPathEditModeActive = false;
      if (pathModifyInteraction) pathModifyInteraction.setActive(false);
      if (pathMidpointLayer) pathMidpointLayer.setVisible(false);
      if (!measureActive) setMapDoubleClickZoom(true);
    }

    function ensurePlanCoverageDefault(state) {
      if (state && state.planCoverageDefaultOnV2) return;
      if (els.showCoverageCircles) els.showCoverageCircles.checked = true;
      if (els.showOverlapAreas && !(state && state.lastDisplayControls && typeof state.lastDisplayControls.showOverlapAreas === 'boolean')) els.showOverlapAreas.checked = false;
      if (els.showUncoveredAreas) els.showUncoveredAreas.checked = true;
      saveFloatingUiState({ planCoverageDefaultOnV2: true });
      saveEditorLastSettings();
    }

    function ensureOverlapDefaultOff(state) {
      if (state && state.overlapDefaultOffV1) return;
      if (els.showOverlapAreas) els.showOverlapAreas.checked = false;
      saveFloatingUiState({ overlapDefaultOffV1: true });
      saveEditorLastSettings();
    }

    function ensurePlanHeightDefault(state) {
      if (state && state.planHeightDefaultV1) return;
      if (els.showControlPoints) els.showControlPoints.checked = false;
      if (els.showFieldControlPoints) els.showFieldControlPoints.checked = false;
      saveFloatingUiState({ planHeightDefaultV1: true });
      saveEditorLastSettings();
    }

    function ensureRouteAglDefaultOff(state) {
      if (state && state.routeAglDefaultOffV1) return;
      if (els.showControlPoints) els.showControlPoints.checked = false;
      if (els.showAglLimit) els.showAglLimit.checked = false;
      if (els.showFieldControlPoints) els.showFieldControlPoints.checked = false;
      saveFloatingUiState({ routeAglDefaultOffV1: true });
      saveEditorLastSettings();
    }

    function ensureDisplayDefaultsV3(state) {
      if (state && state.displayDefaultsV3) return;
      if (els.showAglLimit) els.showAglLimit.checked = true;
      if (els.showTurnCurves) els.showTurnCurves.checked = true;
      if (els.showCorrectionPoints) els.showCorrectionPoints.checked = false;
      if (els.forceCoverageFix) els.forceCoverageFix.checked = false;
      const planAgl = document.getElementById('planShowAglLimit');
      if (planAgl) planAgl.checked = true;
      saveFloatingUiState({ displayDefaultsV3: true });
      saveEditorLastSettings();
    }

    function syncPlanHeightsFromMap() {
      const route = document.getElementById('planShowRouteHeights');
      const field = document.getElementById('planShowFieldHeights');
      if (route && els.showControlPoints) route.checked = !!els.showControlPoints.checked;
      if (field && els.showFieldControlPoints) field.checked = !!els.showFieldControlPoints.checked;
      const agl = document.getElementById('planShowAglLimit');
      if (agl && els.showAglLimit) agl.checked = !!els.showAglLimit.checked;
    }

    function pushPlanHeightsToMap() {
      const route = document.getElementById('planShowRouteHeights');
      const field = document.getElementById('planShowFieldHeights');
      if (els.showControlPoints && route) els.showControlPoints.checked = !!route.checked;
      if (els.showFieldControlPoints && field) els.showFieldControlPoints.checked = !!field.checked;
      const agl = document.getElementById('planShowAglLimit');
      if (els.showAglLimit && agl) els.showAglLimit.checked = !!agl.checked;
      refreshFlightPathLayers();
      saveEditorLastSettings();
    }

    function syncPlanCoverageFromMap() {
      const cov = document.getElementById('planShowCoverage');
      const un = document.getElementById('planShowUncovered');
      if (cov && els.showCoverageCircles) cov.checked = !!els.showCoverageCircles.checked;
      if (un && els.showUncoveredAreas) un.checked = !!els.showUncoveredAreas.checked;
      if (un) un.disabled = !(cov && cov.checked);
    }

    function pushPlanCoverageToMap() {
      const cov = document.getElementById('planShowCoverage');
      const un = document.getElementById('planShowUncovered');
      const on = !!(cov && cov.checked);
      if (un) un.disabled = !on;
      if (els.showCoverageCircles) els.showCoverageCircles.checked = on;
      if (els.showUncoveredAreas) els.showUncoveredAreas.checked = on && !!(un && un.checked);
      refreshFlightPathLayers();
      saveEditorLastSettings();
    }

    function isPlanAdmin() {
      try {
        const u = new URL(window.location.href);
        if (u.searchParams.has('ADMIN')) return true;
        return String(u.searchParams.get('code') || '').trim().toUpperCase() === 'ADMIN';
      } catch (_) {
        return false;
      }
    }

    let planFoldLock = false;
    let planHold = false;
    let planWaypointsDone = false;
    let planParamTab = 'waypoint';

    function showPlanParamTab(tab) {
      planParamTab = tab === 'route' ? 'route' : 'waypoint';
      const route = document.getElementById('planRoutePane');
      const point = document.getElementById('planPointPane');
      const routeBtn = document.getElementById('planTabRoute');
      const pointBtn = document.getElementById('planTabPoint');
      if (route) route.hidden = planParamTab !== 'route';
      if (point) point.hidden = planParamTab !== 'waypoint';
      if (routeBtn) routeBtn.classList.toggle('is-on', planParamTab === 'route');
      if (pointBtn) pointBtn.classList.toggle('is-on', planParamTab === 'waypoint');
    }

    function syncPlanExpertUi() {
      const modeBtn = document.getElementById('planModeToggle');
      const expert = !!(modeBtn && modeBtn.classList.contains('is-expert'));
      const panel = document.getElementById('planWpPanel');
      if (panel) {
        panel.classList.toggle('is-simple', !expert);
        panel.classList.toggle('is-expert', expert);
      }
      const parkRow = document.getElementById('planParkRow');
      const poiRow = document.getElementById('planPoiRow');
      if (parkRow) parkRow.hidden = !expert;
      if (poiRow) poiRow.hidden = !expert;
      if (!expert && waypointListTab === 'all') showWaypointTab('single');
      if (typeof applyWaypointFormEnableState === 'function') applyWaypointFormEnableState();
      if (typeof syncGimbalWrapVisibility === 'function') syncGimbalWrapVisibility();
      if (typeof syncPlanStartSpeed === 'function') syncPlanStartSpeed();
      renderPlanWpChips();
    }

    function renderPlanWpChips() {
      const host = document.getElementById('planWpChips');
      if (!host || typeof currentWaypointPath !== 'function') return;
      const path = currentWaypointPath();
      host.innerHTML = '';
      if (path.length) {
        const strip = document.createElement('div');
        strip.className = 'wp-strip';
        const prev = document.createElement('button');
        prev.type = 'button';
        prev.className = 'wp-chev';
        prev.textContent = '‹';
        prev.setAttribute('aria-label', 'Vorheriger Wegpunkt');
        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'wp-chev';
        next.textContent = '›';
        next.setAttribute('aria-label', 'Nächster Wegpunkt');
        const dots = document.createElement('div');
        dots.className = 'wp-dots';
        path.forEach((pt, i) => {
          const idx = i + 1;
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'wp-dot' + (idx === selectedWaypointIndex1 ? ' is-on' : '');
          const ball = document.createElement('span');
          ball.className = 'ball';
          ball.textContent = i === 0 ? 'S' : String(idx);
          const alt = document.createElement('span');
          alt.className = 'alt';
          const h = Number(pt && pt[2]);
          alt.textContent = (Number.isFinite(h) ? h.toFixed(1) : '–') + 'm';
          btn.addEventListener('click', () => openWaypointDrawer(idx));
          btn.append(ball, alt);
          dots.appendChild(btn);
        });
        const step = (delta) => {
          if (path.length < 2) return;
          let idx = (selectedWaypointIndex1 || 1) + delta;
          if (idx < 1) idx = path.length;
          if (idx > path.length) idx = 1;
          openWaypointDrawer(idx);
        };
        prev.addEventListener('click', () => step(-1));
        next.addEventListener('click', () => step(1));
        strip.append(prev, dots, next);
        host.appendChild(strip);
        const on = dots.querySelector('.wp-dot.is-on');
        if (on) {
          const left = on.offsetLeft;
          const right = left + on.offsetWidth;
          if (left < dots.scrollLeft) dots.scrollLeft = Math.max(0, left - 4);
          else if (right > dots.scrollLeft + dots.clientWidth) dots.scrollLeft = right - dots.clientWidth + 4;
        }
      }
      const caption = document.getElementById('planWpCaption');
      if (!caption) return;
      const modeBtn = document.getElementById('planModeToggle');
      const expert = !!(modeBtn && modeBtn.classList.contains('is-expert'));
      const pt = path[selectedWaypointIndex1 - 1];
      const alt = Number(pt && pt[2]);
      const altTxt = Number.isFinite(alt) ? (alt.toFixed(1) + ' m') : '';
      if (!pt) {
        caption.textContent = 'Wegpunkt wählen.';
        return;
      }
      caption.textContent = expert
        ? ('Wegpunkt ' + selectedWaypointIndex1 + (altTxt ? (' · ' + altTxt) : '') + '. Punkt speichern schreibt die Werte dieses Punkts.')
        : ('Wegpunkt ' + selectedWaypointIndex1 + (altTxt ? (' · ' + altTxt) : '') + '. Höhe, Geschwindigkeit, Typ und Gierrichtung gelten für diesen Punkt, sobald sie vom Missionswert abweichen.');
    }

    function mountPlanParams() {
      const routePane = document.getElementById('planRoutePane');
      const host = document.getElementById('planWpHost');
      const ms = document.getElementById('missionSettingsBox');
      const wp = document.getElementById('waypointDrawerPanel');
      if (routePane && ms && ms.parentElement !== routePane) routePane.appendChild(ms);
      if (host && wp && wp.parentElement !== host) {
        host.appendChild(wp);
        wp.hidden = false;
      }
      const pointPane = document.getElementById('planPointPane');
      const nav = document.getElementById('waypointNavTabs');
      const foot = document.getElementById('waypointDrawerFoot');
      if (pointPane && nav && host && nav.parentElement !== pointPane) {
        pointPane.insertBefore(nav, host);
        nav.hidden = false;
      }
      if (pointPane && foot && foot.parentElement !== pointPane) {
        pointPane.appendChild(foot);
        foot.hidden = false;
      }
      const altHost = document.getElementById('planAltHost');
      const altInput = document.getElementById('msAltitude');
      const altLabel = document.getElementById('planAltLabel');
      if (altInput && altLabel && altLabel.parentElement) {
        const altWrap = altInput.parentElement;
        altLabel.replaceWith(altInput);
        if (altWrap) altWrap.hidden = true;
      }
      ['msAltitudeMode', 'msSafeStartAltitude'].forEach((id) => {
        const el = document.getElementById(id);
        const wrap = el ? el.parentElement : null;
        if (altHost && wrap && wrap.parentElement !== altHost) altHost.appendChild(wrap);
      });
      const startBody = document.querySelector('.plan-fold[data-plan-phase="start"] .plan-fold-body');
      const directWrap = els.msDirectToStart ? els.msDirectToStart.parentElement : null;
      if (startBody && directWrap && directWrap.parentElement !== startBody) startBody.appendChild(directWrap);
    }

    function refreshPlanStepMarks() {
      const setMark = (phase, text) => {
        const fold = document.querySelector('.plan-fold[data-plan-phase="' + phase + '"]');
        const el = fold ? fold.querySelector('.plan-fold-mark') : null;
        if (!el) return;
        el.textContent = text || '';
        el.hidden = !text;
      };
      const name = String((document.getElementById('planFieldName') && document.getElementById('planFieldName').value) || (els.name && els.name.value) || '').trim();
      const drone = String((document.getElementById('planDroneType') && document.getElementById('planDroneType').value) || (els.msDroneType && els.msDroneType.value) || '').trim();
      const altNum = Number(els.msAltitude && els.msAltitude.value);
      const alt = Number.isFinite(altNum) ? (String(altNum) + ' m') : '';
      setMark('place', name ? ('✓ ' + name) : '');
      let corners = 0;
      try { corners = parsePairs(els.flaechendaten && els.flaechendaten.value).length; } catch (_) { corners = 0; }
      const gis = planGisValue();
      const outlineBits = [];
      if (corners >= 3) outlineBits.push(corners + ' Ecken');
      const polyNote = document.getElementById('planPolygonNote');
      if (polyNote) polyNote.textContent = corners >= 3 ? (corners + ' Ecken') : '';
      if (gis) outlineBits.push('GIS ' + gis);
      if (drone) outlineBits.push(drone);
      if (alt) outlineBits.push(alt);
      setMark('outline', outlineBits.length ? ('✓ ' + outlineBits.join(' · ')) : '');
      const start = String(els.startplatz && els.startplatz.value || '').trim();
      setMark('start', start ? '✓ gesetzt' : '');
      const wpRaw = Number(els.wegpunkte && els.wegpunkte.value);
      let triplets = 0;
      try { triplets = parseTriplets(els.flugweg && els.flugweg.value).length; } catch (_) { triplets = 0; }
      const n = (Number.isFinite(wpRaw) && wpRaw > 0) ? wpRaw : triplets;
      const dist = String(els.distanz && els.distanz.value || '').trim();
      setMark('optimize', n >= 2 ? ('✓ ' + n + ' WP' + (dist ? (' · ' + dist + ' m') : '')) : '');
      setMark('waypoints', planWaypointsDone ? '✓ geprüft' : '');
      const readyToCheck = !!(name && corners >= 3 && start && n >= 2);
      setMark('finish', readyToCheck ? '✓ bereit' : '');
      refreshPlanConstraintCheck();
    }

    function syncPlanFolds() {
      planFoldLock = true;
      document.querySelectorAll('.plan-fold').forEach((fold) => {
        const phase = fold.getAttribute('data-plan-phase');
        const open = phase === planPhase;
        fold.open = open;
        fold.classList.toggle('is-active', open);
      });
      planFoldLock = false;
    }

    function planPhaseAllowed(phase) {
      let corners = 0;
      let pathN = 0;
      try { corners = parsePairs(els.flaechendaten && els.flaechendaten.value).length; } catch (_) { corners = 0; }
      try { pathN = parseTriplets(els.flugweg && els.flugweg.value).length; } catch (_) { pathN = 0; }
      const start = String(els.startplatz && els.startplatz.value || '').trim() !== '';
      const saved = String(els.id && els.id.value || '').trim() !== '';
      if (phase === 'outline') return true;
      if (phase === 'start') return corners >= 3;
      if (phase === 'optimize') return corners >= 3 && start;
      if (phase === 'waypoints' || phase === 'place') return corners >= 3 && start && pathN >= 2;
      if (phase === 'finish') return saved;
      return true;
    }

    function setPlanPhase(phase, opts) {
      const order = ['outline', 'start', 'optimize', 'waypoints', 'place', 'finish'];
      if (!order.includes(phase)) phase = 'outline';
      const fromIdx = order.indexOf(planPhase);
      const toIdx = order.indexOf(phase);
      if (toIdx > fromIdx && !planPhaseAllowed(phase)) {
        const why = {
          start: 'Zuerst die Fläche zeichnen.',
          optimize: 'Zuerst Fläche und Startpunkt.',
          waypoints: 'Zuerst den Flugpfad rechnen.',
          place: 'Zuerst den Flugpfad rechnen.',
          finish: 'Zuerst speichern. Der Schritt Daten folgt danach.'
        };
        showStatus(why[phase] || 'Dieser Schritt folgt später.', false);
        syncPlanFolds();
        return;
      }
      if (opts && opts.manual && toIdx < fromIdx) planHold = true;
      if (toIdx > fromIdx) planHold = false;
      planPhase = phase;
      if (phase === 'finish') planWaypointsDone = true;
      const rail = document.getElementById('planStepRail');
      const texts = {
        outline: 'Status: Fläche zeichnen',
        start: 'Status: Startpunkt setzen',
        optimize: 'Status: Flugpfad',
        waypoints: '4 - Prüfen',
        place: 'Status: Speichern',
        finish: 'Status: Daten',
      };
      if (rail) rail.textContent = texts[phase];
      syncPlanFolds();
      document.querySelectorAll('.plan-step').forEach((btn) => btn.classList.remove('is-active'));
      const activeId = {
        outline: 'planStepPolygon',
        start: 'planStepStart',
        optimize: 'planStepRoute',
        waypoints: 'planStepWp',
        place: 'planStepSave',
        finish: 'planCheck',
      }[phase];
      const activeBtn = activeId ? document.getElementById(activeId) : null;
      if (activeBtn) activeBtn.classList.add('is-active');
      if (phase === 'optimize') {
        if (els.showBoxFieldCharacteristics) els.showBoxFieldCharacteristics.checked = true;
        setFieldCharacteristicsCollapsed(false);
        armOptimizePathEdit();
      } else {
        disarmOptimizePathEdit();
      }
      if (phase === 'waypoints') {
        showPlanParamTab(planParamTab);
        syncPlanExpertUi();
        const path = currentWaypointPath();
        if (path.length >= 2 && (fromIdx < order.indexOf('waypoints') || selectedWaypointIndex1 < 1)) {
          if (selectedWaypointIndex1 < 1) selectedWaypointIndex1 = 1;
          showWaypointTab('single');
          fillWaypointForm(selectedWaypointIndex1, true);
        }
        renderPlanWpChips();
      }
      if (phase === 'outline' || phase === 'start' || phase === 'optimize' || phase === 'waypoints') {
        activateLayerWindowForPlanStep();
      }
      refreshPlanStepMarks();
      scheduleNewFieldDraftSave();
      applyPlanStatusTools();
    }

    function revealPlanReviewIfReady() {
      if (planHold) return;
      const ready = currentComputedPath.length >= 2 || currentManualPath.length >= 2;
      if (!ready) return;
      if (planPhase === 'outline' || planPhase === 'start') setPlanPhase('optimize');
    }

    function syncDerivedPathFigures(path) {
      const pts = normalizeTripletPath(path);
      if (els.wegpunkte) els.wegpunkte.value = pts.length ? String(pts.length) : '';
      let distanceM = 0;
      for (let i = 0; i + 1 < pts.length; i++) distanceM += distanceMetersLonLat(pts[i], pts[i + 1]);
      if (els.distanz && Number.isFinite(distanceM)) els.distanz.value = distanceM.toFixed(1);
      const speedMps = Number(els.msSpeed && els.msSpeed.value);
      const startSpeedActive = !!(els.msStartSpeedActive && els.msStartSpeedActive.checked);
      const startSpeedMps = Number(els.msStartSpeed && els.msStartSpeed.value);
      const pause = turnDelaySecondsByWpType(String(els.msWaypointType && els.msWaypointType.value || ''));
      let flightSeconds = pts.length * pause;
      for (let i = 0; i + 1 < pts.length; i++) {
        const segSpeed = (i === 0 && startSpeedActive) ? startSpeedMps : speedMps;
        if (Number.isFinite(segSpeed) && segSpeed > 1e-6) flightSeconds += distanceMetersLonLat(pts[i], pts[i + 1]) / segSpeed;
      }
      if (els.flugzeit && Number.isFinite(flightSeconds)) els.flugzeit.value = (flightSeconds / 60).toFixed(1);
    }

    function markGeneratedPath(path) {
      generatedPathSnapshot = cloneTripletPath(path);
      pathDivergedFromGenerated = false;
      updatePathResetButton();
    }

    function resetPathToGenerated() {
      if (generatedPathSnapshot.length < 2) return;
      currentManualPath = cloneTripletPath(generatedPathSnapshot);
      currentComputedPath = cloneTripletPath(generatedPathSnapshot);
      pathDivergedFromGenerated = false;
      if (els.flugweg) els.flugweg.value = formatTriplets(currentManualPath);
      if (els.showAlgorithmPath) els.showAlgorithmPath.checked = false;
      if (els.showManualPath) els.showManualPath.checked = true;
      updatePathResetButton();
      refreshFlightPathLayers();
      syncDerivedPathFigures(currentComputedPath);
      showStatus('Flugpfad auf die ursprüngliche Berechnung zurückgesetzt.');
    }

    function commitManualPathToForm(path, historyLabel = '') {
      const next = normalizeTripletPath(path);
      if (!generatedPathSnapshot.length && currentComputedPath.length >= 2) {
        generatedPathSnapshot = cloneTripletPath(currentComputedPath);
      }
      currentManualPath = next.map((p) => [p[0], p[1], p[2]]);
      if (els.flugweg) els.flugweg.value = formatTriplets(currentManualPath);
      pathDivergedFromGenerated = generatedPathSnapshot.length >= 2
        && !pathsNearlyEqual(currentManualPath, generatedPathSnapshot);
      if (pathDivergedFromGenerated) {
        if (els.showAlgorithmPath) els.showAlgorithmPath.checked = false;
        if (els.showManualPath) els.showManualPath.checked = true;
      }
      updatePathResetButton();
      const editedCoords = next.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const turns = new Set();
      const turnMin = 35 * Math.PI / 180;
      for (let i = 1; i < editedCoords.length - 1; i++) {
        if (coverageHeadingChange(editedCoords[i - 1], editedCoords[i], editedCoords[i + 1]) >= turnMin) {
          turns.add(i + 1);
        }
      }
      currentTurnWaypointIndices = turns;
      if (historyLabel) pushHistory(historyLabel);
      refreshFlightPathLayers();
      refreshPathMidpointHandles();
      syncDerivedPathFigures(currentManualPath);
      updateSaveButtonState();
      scheduleNewFieldDraftSave();
    }

    function enforceCoverageDisplayConstraints() {
      const manualVisible = !!(els.showManualPath && els.showManualPath.checked);
      const algoVisible = !!(els.showAlgorithmPath && els.showAlgorithmPath.checked);
      if (els.showCoverageCircles) {
        els.showCoverageCircles.disabled = !manualVisible && !algoVisible;
        if (els.showCoverageCircles.parentElement) {
          els.showCoverageCircles.parentElement.classList.toggle('is-disabled', !!els.showCoverageCircles.disabled);
        }
      }
      const coverageEnabled = !!(els.showCoverageCircles && els.showCoverageCircles.checked && !els.showCoverageCircles.disabled);
      if (els.showOverlapAreas) {
        els.showOverlapAreas.disabled = !coverageEnabled;
        if (els.showOverlapAreas.parentElement) {
          els.showOverlapAreas.parentElement.classList.toggle('is-disabled', !!els.showOverlapAreas.disabled);
        }
      }
      if (els.showUncoveredAreas) {
        els.showUncoveredAreas.disabled = !coverageEnabled;
        if (els.showUncoveredAreas.parentElement) {
          els.showUncoveredAreas.parentElement.classList.toggle('is-disabled', !!els.showUncoveredAreas.disabled);
        }
      }
      if (els.coverageSourceLabel) els.coverageSourceLabel.textContent = 'Suchabdeckung';
    }

    function enforceSectionDirectionDisplayConstraint() {
      const label = els.showSectionDirections && els.showSectionDirections.parentElement
        ? els.showSectionDirections.parentElement
        : null;
      if (!els.showSectionDirections || !label) return;
      // Keep behavior aligned with analyse_ui: section direction toggle is generally available.
      els.showSectionDirections.disabled = false;
      label.classList.remove('is-disabled');
      label.title = '';
    }

    function refreshFlightPathLayers() {
      if (!manualPathLayer || !computedPathLayer) return;
      enforceCoverageDisplayConstraints();
      enforceSectionDirectionDisplayConstraint();
      const showManual = !!(els.showManualPath && els.showManualPath.checked);
      if (els.showAlgorithmPath) els.showAlgorithmPath.checked = false;
      manualPathLayer.setVisible(showManual);
      computedPathLayer.setVisible(false);
      const showPoly = !!(els.showFieldPolygon && els.showFieldPolygon.checked);
      if (polygonLayer) polygonLayer.setVisible(showPoly);
      if (vertexLayer) vertexLayer.setVisible(showPoly);
      if (midpointLayer) {
        if (!showPoly) midpointLayer.setVisible(false);
        else if (typeof isEditModeActive !== 'undefined' && isEditModeActive) midpointLayer.setVisible(true);
      }
      if (pathMidpointLayer) {
        if (!showManual) pathMidpointLayer.setVisible(false);
        else if (planPhase === 'optimize' || (typeof isPathEditModeActive !== 'undefined' && isPathEditModeActive)) pathMidpointLayer.setVisible(true);
      }
      syncLayerPlanToggles();
      if (manualPathLayer && typeof manualPathLayer.changed === 'function') manualPathLayer.changed();
      if (computedPathLayer && typeof computedPathLayer.changed === 'function') computedPathLayer.changed();
      if (controlPointLayer) {
        const showPathCp = !!(els.showControlPoints && els.showControlPoints.checked);
        const showFieldCp = !!(els.showFieldControlPoints && els.showFieldControlPoints.checked);
        const showAgl = !!(els.showAglLimit && els.showAglLimit.checked);
        controlPointLayer.setVisible(showPathCp || showFieldCp || showAgl);
      }
      setPathSourceFromTriplets(manualPathSource, showManual ? workingFlightPath() : [], 'manual');
      setPathSourceFromTriplets(computedPathSource, [], 'computed');
      scheduleControlPointRefresh();
      refreshCoverageGeometry();
      refreshPathMidpointHandles();
      refreshWsVisualLines();
      scheduleFieldCharacteristicsRefresh();
      revealPlanReviewIfReady();
      refreshPlanStepMarks();
      if (planPhase === 'optimize' && !measureActive) armOptimizePathEdit();
    }

    function shouldShowManualPathControlPoints() {
      return !!(els.showControlPoints && els.showControlPoints.checked
        && els.showManualPath && els.showManualPath.checked);
    }

    function shouldShowComputedPathControlPoints() {
      return !!(els.showControlPoints && els.showControlPoints.checked
        && els.showAlgorithmPath && els.showAlgorithmPath.checked);
    }

    function colorForRelativeHeight(t) {
      const clamped = Math.max(0, Math.min(1, t));
      let r; let g; const b = 0;
      if (clamped <= 0.5) {
        const k = clamped / 0.5;
        r = 255;
        g = Math.round(255 * k);
      } else {
        const k = (clamped - 0.5) / 0.5;
        r = Math.round(255 * (1 - k));
        g = 255;
      }
      return `rgb(${r},${g},${b})`;
    }

    function pointFeatures(path, relHeights) {
      const pts = Array.isArray(path) ? path : [];
      const rels = (relHeights || [])
        .map((v) => Number(v))
        .filter((v) => Number.isFinite(v));
      const minRel = rels.length ? Math.min(...rels) : NaN;
      const maxRel = rels.length ? Math.max(...rels) : NaN;
      const span = Number.isFinite(minRel) && Number.isFinite(maxRel) ? Math.max(1e-9, maxRel - minRel) : NaN;
      return pts.map((p, idx) => {
        const relVal = Number(relHeights && relHeights[idx]);
        const relOut = Number.isFinite(relVal) ? relVal : 0;
        const relText = `${relOut >= 0 ? '+' : ''}${relOut.toFixed(1)}m`;
        const t = (Number.isFinite(relVal) && Number.isFinite(span)) ? (relVal - minRel) / span : 0.5;
        const pointColor = colorForRelativeHeight(t);
        const feat = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([p[0], p[1]])) });
        feat.setStyle(new ol.style.Style({
          image: new ol.style.Circle({
            radius: 4,
            fill: new ol.style.Fill({ color: pointColor }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 1 }),
          }),
          text: new ol.style.Text({
            text: relText,
            font: '12px Arial',
            offsetY: 14,
            fill: new ol.style.Fill({ color: '#808080' }),
            stroke: new ol.style.Stroke({ color: 'rgba(255,255,255,0.98)', width: 3.4 }),
          }),
        }));
        return feat;
      });
    }

    function pathControlPointFeatures(path, groundDepartureM, _flightLineIgnored, warnOnly) {
      const pts = Array.isArray(path) ? path : [];
      const aglTolM = currentAglToleranceValue();
      const markAgl = !!(els.showAglLimit && els.showAglLimit.checked);
      const features = [];
      pts.forEach((p, idx) => {
        const diff = Number(groundDepartureM && groundDepartureM[idx]);
        if (!Number.isFinite(diff)) return;
        const isOut = Math.abs(diff) > aglTolM;
        const showWarn = isOut && (markAgl || warnOnly);
        if (warnOnly && !showWarn) return;
        const diffText = `${diff >= 0 ? '+' : ''}${diff.toFixed(1)}m`;
        const feat = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([p[0], p[1]])) });
        feat.set('aglOut', isOut);
        if (showWarn) {
          feat.setStyle([
            new ol.style.Style({
              image: new ol.style.Circle({
                radius: 7,
                fill: new ol.style.Fill({ color: '#e10600' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 2 }),
              }),
            }),
            new ol.style.Style({
              text: new ol.style.Text({
                text: diffText,
                font: '12px Arial',
                offsetY: -14,
                fill: new ol.style.Fill({ color: '#e10600' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 3.2 }),
              }),
            }),
          ]);
        } else {
          feat.setStyle([
            new ol.style.Style({
              image: new ol.style.Circle({
                radius: 4.2,
                fill: new ol.style.Fill({ color: '#2f6dff' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 1 }),
              }),
            }),
            new ol.style.Style({
              text: new ol.style.Text({
                text: diffText,
                font: '12px Arial',
                offsetY: 14,
                fill: new ol.style.Fill({ color: '#808080' }),
                stroke: new ol.style.Stroke({ color: 'rgba(255,255,255,0.98)', width: 3.4 }),
              }),
            }),
          ]);
        }
        features.push(feat);
      });
      return features;
    }

    async function fetchTerrainHeightsList(points) {
      const list = Array.isArray(points) ? points : [];
      const out = new Array(list.length).fill(NaN);
      const chunkSize = 20;
      for (let i = 0; i < list.length; i += chunkSize) {
        const chunk = list.slice(i, i + chunkSize);
        const vals = await Promise.all(chunk.map(async (p) => {
          try {
            return await fetchGeoAdminTerrainHeight(p[0], p[1]);
          } catch (_) {
            return NaN;
          }
        }));
        vals.forEach((h, j) => { out[i + j] = h; });
      }
      return out;
    }

    async function groundDepartureAlongPath(controlPoints, pathTriplets) {
      const cps = Array.isArray(controlPoints) ? controlPoints : [];
      const path = normalizeTripletPath(pathTriplets);
      if (!cps.length || path.length < 2) return cps.map(() => NaN);
      const wpGround = await fetchTerrainHeightsList(path.map((p) => [p[0], p[1]]));
      const sampleGround = await fetchTerrainHeightsList(cps);
      const segs = [];
      for (let i = 0; i + 1 < path.length; i++) {
        const aXY = ol.proj.fromLonLat([path[i][0], path[i][1]]);
        const bXY = ol.proj.fromLonLat([path[i + 1][0], path[i + 1][1]]);
        segs.push({
          ax: aXY[0], ay: aXY[1], aG: Number(wpGround[i]),
          bx: bXY[0], by: bXY[1], bG: Number(wpGround[i + 1]),
        });
      }
      return cps.map((cp, idx) => {
        const ground = Number(sampleGround[idx]);
        if (!Number.isFinite(ground)) return NaN;
        const pXY = ol.proj.fromLonLat([cp[0], cp[1]]);
        let bestDist2 = Infinity;
        let best = NaN;
        for (let i = 0; i < segs.length; i++) {
          const s = segs[i];
          if (!Number.isFinite(s.aG) || !Number.isFinite(s.bG)) continue;
          const vx = s.bx - s.ax;
          const vy = s.by - s.ay;
          const denom = vx * vx + vy * vy;
          const tRaw = denom > 1e-9 ? (((pXY[0] - s.ax) * vx + (pXY[1] - s.ay) * vy) / denom) : 0;
          const t = Math.max(0, Math.min(1, tRaw));
          const qx = s.ax + vx * t;
          const qy = s.ay + vy * t;
          const d2 = (pXY[0] - qx) * (pXY[0] - qx) + (pXY[1] - qy) * (pXY[1] - qy);
          if (d2 < bestDist2) {
            bestDist2 = d2;
            best = ground - (s.aG + (s.bG - s.aG) * t);
          }
        }
        return best;
      });
    }

    async function aglForControlPointsAlongPath(controlPoints, pathTriplets, startLonLat) {
      const cps = Array.isArray(controlPoints) ? controlPoints : [];
      const path = normalizeTripletPath(pathTriplets);
      if (!cps.length) return [];
      const missionAlt = Number(els.msAltitude && els.msAltitude.value);
      const flightFallback = Number.isFinite(missionAlt) ? missionAlt : currentAglValue();
      if (path.length < 2) return cps.map(() => flightFallback);
      void startLonLat;
      const segs = [];
      for (let i = 0; i + 1 < path.length; i++) {
        const aXY = ol.proj.fromLonLat([path[i][0], path[i][1]]);
        const bXY = ol.proj.fromLonLat([path[i + 1][0], path[i + 1][1]]);
        const aAlt = Number(path[i][2]);
        const bAlt = Number(path[i + 1][2]);
        segs.push({
          ax: aXY[0], ay: aXY[1], aAltRelStart: Number.isFinite(aAlt) ? aAlt : flightFallback,
          bx: bXY[0], by: bXY[1], bAltRelStart: Number.isFinite(bAlt) ? bAlt : flightFallback,
        });
      }
      return cps.map((cp) => {
        const pXY = ol.proj.fromLonLat([cp[0], cp[1]]);
        const px = pXY[0];
        const py = pXY[1];
        let bestDist2 = Infinity;
        let bestAltRelStart = flightFallback;
        for (let i = 0; i < segs.length; i++) {
          const s = segs[i];
          const vx = s.bx - s.ax;
          const vy = s.by - s.ay;
          const denom = vx * vx + vy * vy;
          const tRaw = denom > 1e-9 ? (((px - s.ax) * vx + (py - s.ay) * vy) / denom) : 0;
          const t = Math.max(0, Math.min(1, tRaw));
          const qx = s.ax + vx * t;
          const qy = s.ay + vy * t;
          const dx = px - qx;
          const dy = py - qy;
          const d2 = dx * dx + dy * dy;
          if (d2 < bestDist2) {
            bestDist2 = d2;
            bestAltRelStart = s.aAltRelStart + (s.bAltRelStart - s.aAltRelStart) * t;
          }
        }
        return Number.isFinite(bestAltRelStart) ? bestAltRelStart : flightFallback;
      });
    }

    async function computeControlPointRelativeAgl(controlPoints, startLonLat) {
      const points = Array.isArray(controlPoints) ? controlPoints : [];
      if (!points.length || !Array.isArray(startLonLat) || startLonLat.length < 2) {
        return points.map(() => NaN);
      }
      let startTerrain = NaN;
      try {
        startTerrain = await fetchGeoAdminTerrainHeight(startLonLat[0], startLonLat[1]);
      } catch (_) {
        startTerrain = NaN;
      }
      if (!Number.isFinite(startTerrain)) return points.map(() => NaN);
      const out = new Array(points.length).fill(NaN);
      const chunkSize = 20;
      for (let i = 0; i < points.length; i += chunkSize) {
        const chunk = points.slice(i, i + chunkSize);
        const vals = await Promise.all(chunk.map(async (p) => {
          try {
            return await fetchGeoAdminTerrainHeight(p[0], p[1]);
          } catch (_) {
            return NaN;
          }
        }));
        vals.forEach((terrain, j) => {
          out[i + j] = Number.isFinite(terrain) ? (terrain - startTerrain) : NaN;
        });
      }
      return out;
    }

    function manualPathForControlPoints() {
      return normalizeTripletPath(workingFlightPath()).map((p) => [p[0], p[1]]);
    }

    function scheduleControlPointRefresh() {
      if (controlPointRefreshTimer) clearTimeout(controlPointRefreshTimer);
      controlPointRefreshTimer = setTimeout(() => {
        refreshControlPointFeatures().catch(() => {});
      }, 120);
    }

    async function refreshControlPointFeatures() {
      if (!controlPointSource) return;
      const token = ++controlPointRefreshToken;
      controlPointSource.clear();
      const showPathCp = !!(els.showControlPoints && els.showControlPoints.checked);
      const showFieldCp = !!(els.showFieldControlPoints && els.showFieldControlPoints.checked);
      const showAgl = !!(els.showAglLimit && els.showAglLimit.checked);
      if (!showPathCp && !showFieldCp && !showAgl) return;
      const startPts = parsePairs(els.startplatz && els.startplatz.value);
      const startLonLat = startPts.length ? [startPts[0][0], startPts[0][1]] : null;
      if (!startLonLat) return;
      const features = [];
      if (showPathCp) {
        if (shouldShowManualPathControlPoints()) {
          const manualPts = manualPathForControlPoints();
          const sampledManualPts = buildPathControlPointSamples(manualPts, 25);
          const manualPathTriplets = workingFlightPath();
          const departureManual = await groundDepartureAlongPath(sampledManualPts, manualPathTriplets);
          if (token !== controlPointRefreshToken) return;
          pathControlPointFeatures(sampledManualPts, departureManual, null, false).forEach((f) => features.push(f));
        }
        if (shouldShowComputedPathControlPoints()) {
          const algoBase = normalizeTripletPath(currentComputedPath).map((p) => [p[0], p[1]]);
          let algoPts = [];
          if (algoBase.length >= 2) {
            algoPts = buildPathControlPointSamples(algoBase, 25);
          } else if (Array.isArray(currentControlPointsPath) && currentControlPointsPath.length) {
            algoPts = currentControlPointsPath.slice();
          }
          if (algoPts.length) {
            const pathForGround = (currentComputedPath && currentComputedPath.length >= 2)
              ? currentComputedPath
              : algoPts;
            const departureAlgo = await groundDepartureAlongPath(algoPts, pathForGround);
            if (token !== controlPointRefreshToken) return;
            pathControlPointFeatures(algoPts, departureAlgo, null, false).forEach((f) => features.push(f));
          }
        }
      }
      if (showAgl && !shouldShowManualPathControlPoints()) {
        const manualPts = manualPathForControlPoints();
        if (manualPts.length >= 2) {
          const sampledManualPts = buildPathControlPointSamples(manualPts, 25);
          const departureManual = await groundDepartureAlongPath(sampledManualPts, workingFlightPath());
          if (token !== controlPointRefreshToken) return;
          pathControlPointFeatures(sampledManualPts, departureManual, null, true).forEach((f) => features.push(f));
        }
      }
      if (showFieldCp) {
        if (!Array.isArray(currentControlPointsField) || !currentControlPointsField.length) {
          currentControlPointsField = buildFieldControlPointGridFromPolygon(25);
        }
        const fieldPts = Array.isArray(currentControlPointsField) ? currentControlPointsField : [];
        const relAglField = await computeControlPointRelativeAgl(fieldPts, startLonLat);
        if (token !== controlPointRefreshToken) return;
        pointFeatures(fieldPts, relAglField).forEach((f) => features.push(f));
      }
      if (token !== controlPointRefreshToken) return;
      features.forEach((f) => controlPointSource.addFeature(f));
    }

    function buildFieldControlPointGridFromPolygon(stepMeters = 25) {
      if (!currentPolygonFeature || typeof currentPolygonFeature.getGeometry !== 'function') return [];
      const geom = currentPolygonFeature.getGeometry();
      if (!geom || typeof geom.getExtent !== 'function' || typeof geom.intersectsCoordinate !== 'function') return [];
      const extent = geom.getExtent();
      if (!extent || extent.length < 4) return [];
      const center = [(extent[0] + extent[2]) * 0.5, (extent[1] + extent[3]) * 0.5];
      const scale = meterToMapUnitScaleAt(center);
      const step = Math.max(8, Number(stepMeters) || 25) * scale;
      const out = [];
      for (let y = extent[1] + step * 0.5; y <= extent[3] - step * 0.5; y += step) {
        for (let x = extent[0] + step * 0.5; x <= extent[2] - step * 0.5; x += step) {
          const xy = [x, y];
          if (!geom.intersectsCoordinate(xy)) continue;
          const lonlat = ol.proj.toLonLat(xy);
          if (!Array.isArray(lonlat) || lonlat.length < 2) continue;
          out.push([Number(lonlat[0]), Number(lonlat[1])]);
        }
      }
      return out;
    }

    function buildPathControlPointSamples(basePath, stepMeters = 25) {
      const pts = Array.isArray(basePath) ? basePath : [];
      if (pts.length < 2) return pts.slice();
      const firstXY = ol.proj.fromLonLat([pts[0][0], pts[0][1]]);
      const scale = meterToMapUnitScaleAt(firstXY);
      const stepMap = Math.max(8, Number(stepMeters) || 25) * scale;
      const out = [];
      for (let i = 0; i < pts.length - 1; i++) {
        const a = ol.proj.fromLonLat([pts[i][0], pts[i][1]]);
        const b = ol.proj.fromLonLat([pts[i + 1][0], pts[i + 1][1]]);
        const dx = b[0] - a[0];
        const dy = b[1] - a[1];
        const segLen = Math.hypot(dx, dy);
        if (segLen <= 1e-6) continue;
        const segments = Math.max(1, Math.ceil(segLen / stepMap));
        for (let s = 0; s < segments; s++) {
          const t = s / segments;
          const xy = [a[0] + dx * t, a[1] + dy * t];
          const lonlat = ol.proj.toLonLat(xy);
          if (!Array.isArray(lonlat) || lonlat.length < 2) continue;
          out.push([Number(lonlat[0]), Number(lonlat[1])]);
        }
      }
      const last = pts[pts.length - 1];
      if (Array.isArray(last) && last.length >= 2) {
        out.push([Number(last[0]), Number(last[1])]);
      }
      return out;
    }

    function formatPairs(points, closePolygon = false) {
      const pts = Array.isArray(points) ? points.slice() : [];
      if (closePolygon && pts.length >= 3) {
        const a = pts[0];
        const b = pts[pts.length - 1];
        if (Math.abs(a[0] - b[0]) > 1e-10 || Math.abs(a[1] - b[1]) > 1e-10) {
          pts.push([a[0], a[1]]);
        }
      }
      return pts.map((p) => `${Number(p[0]).toFixed(13).replace(/0+$/,'').replace(/\.$/,'')}, ${Number(p[1]).toFixed(13).replace(/0+$/,'').replace(/\.$/,'')}`).join('     ');
    }

    function formatFixed6(points) {
      return (Array.isArray(points) ? points : [])
        .filter((p) => Array.isArray(p) && p.length >= 2 && Number.isFinite(Number(p[0])) && Number.isFinite(Number(p[1])))
        .map((p) => `${Number(p[0]).toFixed(6)}, ${Number(p[1]).toFixed(6)}`)
        .join('     ');
    }

    function formatFixed4(points) {
      return (Array.isArray(points) ? points : [])
        .filter((p) => Array.isArray(p) && p.length >= 2 && Number.isFinite(Number(p[0])) && Number.isFinite(Number(p[1])))
        .map((p) => `${Number(p[0]).toFixed(4)}, ${Number(p[1]).toFixed(4)}`)
        .join('     ');
    }

    function polygonCentroidPair(raw) {
      const pts = parsePairs(raw);
      if (pts.length < 3) return null;
      let lon = 0;
      let lat = 0;
      let n = 0;
      pts.forEach((p) => {
        const x = Number(p[0]);
        const y = Number(p[1]);
        if (!Number.isFinite(x) || !Number.isFinite(y)) return;
        lon += x;
        lat += y;
        n += 1;
      });
      if (!n) return null;
      return [lon / n, lat / n];
    }

    function sameCoord6(a, b) {
      if (!a || !b) return false;
      return Number(a[0]).toFixed(6) === Number(b[0]).toFixed(6) && Number(a[1]).toFixed(6) === Number(b[1]).toFixed(6);
    }

    function refreshParkplatzNote() {
      const start = parsePairs(els.startplatz && els.startplatz.value)[0];
      const park = parsePairs(readControl('parkplatz'))[0];
      const startNote = document.getElementById('planStartCoord');
      const parkNote = document.getElementById('planParkCoord');
      const clearBtn = document.getElementById('planParkClear');
      if (startNote) {
        startNote.textContent = start ? ('Startpunkt ' + formatFixed6([start])) : '';
        startNote.classList.toggle('is-coord', !!start);
      }
      const showPark = !!(park && (!start || !sameCoord6(park, start)));
      if (parkNote) {
        parkNote.textContent = showPark ? ('Parkplatz ' + formatFixed6([park])) : 'Parkplatz nur, wenn er sich vom Startpunkt unterscheidet.';
        parkNote.classList.toggle('is-coord', showPark);
      }
      if (clearBtn) clearBtn.hidden = !showPark;
      paintParkMarker();
    }

    function normalizeStoredPlacePoints() {
      const start = parsePairs(els.startplatz && els.startplatz.value)[0];
      const park = parsePairs(readControl('parkplatz'))[0];
      const centre = parsePairs(readControl('zentrum'))[0];
      const parkText = (park && (!start || !sameCoord6(park, start))) ? formatFixed6([park]) : '';
      writeControl('parkplatz', parkText);
      writeControl('create_parkplatz', parkText);
      const fromPoly = polygonCentroidPair(els.flaechendaten && els.flaechendaten.value);
      const centrePoint = fromPoly || centre;
      if (centrePoint) {
        const centreText = formatFixed4([centrePoint]);
        writeControl('zentrum', centreText);
        writeControl('create_zentrum', centreText);
      }
      refreshParkplatzNote();
    }

    function paintParkMarker() {
      if (!parkSource) return;
      parkSource.clear();
      const start = parsePairs(els.startplatz && els.startplatz.value)[0];
      const park = parsePairs(readControl('parkplatz'))[0];
      if (!park || (start && sameCoord6(park, start))) return;
      parkSource.addFeature(new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.fromLonLat([Number(park[0]), Number(park[1])])),
      }));
    }

    async function geocodePlaceNameOpenMeteo(query) {
      const q = String(query || '').trim();
      if (!q) return null;
      const url = 'https://geocoding-api.open-meteo.com/v1/search?name='
        + encodeURIComponent(q)
        + '&count=1&language=de&format=json';
      const r = await fetch(url, { cache: 'no-store' });
      if (!r.ok) return null;
      const j = await r.json();
      if (!j || !Array.isArray(j.results) || !j.results.length) return null;
      const res = j.results[0];
      const lat = Number(res.latitude);
      const lon = Number(res.longitude);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return null;
      return {
        name: String(res.name || q),
        admin1: String(res.admin1 || ''),
        country: String(res.country || ''),
        lat,
        lon,
      };
    }

    async function geocodePlaceNameSwissTopo(query) {
      const q = String(query || '').trim();
      if (!q) return null;
      const url = 'https://api3.geo.admin.ch/rest/services/api/SearchServer?type=locations&searchText='
        + encodeURIComponent(q)
        + '&limit=1&origins=address,zipcode,gg25,district,kantone,gazetteer';
      const r = await fetch(url, { cache: 'no-store' });
      if (!r.ok) return null;
      const j = await r.json();
      const results = Array.isArray(j && j.results) ? j.results : [];
      if (!results.length) return null;
      const attrs = results[0] && results[0].attrs ? results[0].attrs : {};
      const lat = Number(attrs.lat);
      const lon = Number(attrs.lon);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return null;
      return {
        name: String(attrs.label || attrs.detail || attrs.origin || q).replace(/<[^>]+>/g, ''),
        admin1: String(attrs.origin || ''),
        country: 'Schweiz',
        lat,
        lon,
      };
    }

    async function geocodePlaceName(query) {
      try {
        const hit = await geocodePlaceNameOpenMeteo(query);
        if (hit) return hit;
      } catch (_) {}
      try {
        const hit = await geocodePlaceNameSwissTopo(query);
        if (hit) return hit;
      } catch (_) {}
      return null;
    }

    async function findPlaceOnMap() {
      ensureMap();
      const entered = window.prompt('Ort suchen (z.B. Knonau, Zug, 8915 Hausen):', '');
      if (entered === null) return false;
      const q = String(entered || '').trim();
      if (!q) {
        showStatus('Keine Sucheingabe.', false);
        return false;
      }
      showStatus('Suche Ort...');
      const hit = await geocodePlaceName(q);
      if (!hit) {
        showStatus('Ort nicht gefunden oder Geocoding-Dienst nicht erreichbar.', false);
        return false;
      }
      if (findPlaceSource) findPlaceSource.clear();
      const xy = ol.proj.fromLonLat([hit.lon, hit.lat]);
      const f = new ol.Feature({ geometry: new ol.geom.Point(xy) });
      const lbl = [hit.name, hit.admin1, hit.country].filter(Boolean).join(', ');
      f.set('findLabel', lbl || hit.name);
      findPlaceSource.addFeature(f);
      map.getView().animate({ center: xy, zoom: Math.max(14, map.getView().getZoom() || 14), duration: 450 });
      showStatus('Ort gefunden: ' + (lbl || hit.name));
      return true;
    }

    function formatMeasureMeters(meters) {
      const n = Number(meters);
      if (!Number.isFinite(n)) return '–';
      if (n >= 1000) return (n / 1000).toFixed(n >= 10000 ? 1 : 2).replace('.', ',') + ' km';
      return Math.round(n).toLocaleString('de-CH') + ' m';
    }

    function measureSegmentMeters(a, b) {
      if (!a || !b) return 0;
      const la = ol.proj.toLonLat(a);
      const lb = ol.proj.toLonLat(b);
      return distanceMetersLonLat(la, lb);
    }

    function syncMeasureButton() {
      const btn = document.getElementById('planMeasureBtn');
      if (!btn) return;
      btn.classList.toggle('measure-on', measureActive);
      btn.textContent = measureActive ? 'Messung beenden' : '📏 Distanz';
    }

    function renderMeasure() {
      if (!measureSource) return;
      measureSource.clear();
      const live = (measureActive && measureHover) ? measureHover : null;
      const fixed = measureCoords;
      if (fixed.length >= 2) {
        measureSource.addFeature(new ol.Feature({ geometry: new ol.geom.LineString(fixed) }));
      }
      if (live && fixed.length >= 1) {
        const rubber = new ol.Feature({ geometry: new ol.geom.LineString([fixed[fixed.length - 1], live]) });
        rubber.set('measureRubber', true);
        measureSource.addFeature(rubber);
      }
      const addLabel = (a, b, offsetY) => {
        const mid = [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2];
        const feat = new ol.Feature({ geometry: new ol.geom.Point(mid) });
        feat.setStyle(new ol.style.Style({
          text: new ol.style.Text({
            text: formatMeasureMeters(measureSegmentMeters(a, b)),
            font: '600 13px Arial',
            fill: new ol.style.Fill({ color: '#111827' }),
            stroke: new ol.style.Stroke({ color: '#fff', width: 4 }),
            offsetY: offsetY,
          }),
        }));
        measureSource.addFeature(feat);
      };
      for (let i = 1; i < fixed.length; i++) addLabel(fixed[i - 1], fixed[i], -12);
      if (live && fixed.length >= 1) addLabel(fixed[fixed.length - 1], live, -12);
      fixed.forEach((c) => measureSource.addFeature(new ol.Feature({ geometry: new ol.geom.Point(c) })));
      const drawPts = live ? fixed.concat([live]) : fixed;
      if (drawPts.length >= 2) {
        let total = 0;
        for (let i = 1; i < drawPts.length; i++) total += measureSegmentMeters(drawPts[i - 1], drawPts[i]);
        const totalFeat = new ol.Feature({ geometry: new ol.geom.Point(drawPts[drawPts.length - 1]) });
        totalFeat.setStyle(new ol.style.Style({
          text: new ol.style.Text({
            text: 'Σ ' + formatMeasureMeters(total),
            font: '700 14px Arial',
            fill: new ol.style.Fill({ color: '#1d4e89' }),
            stroke: new ol.style.Stroke({ color: '#fff', width: 4 }),
            offsetY: 16,
          }),
        }));
        measureSource.addFeature(totalFeat);
      }
    }

    function setMeasureActive(on) {
      measureActive = !!on;
      if (!measureActive) measureHover = null;
      if (measureActive) stopAllDrawModes();
      else if (planPhase === 'optimize') armOptimizePathEdit();
      if (map) {
        map.getInteractions().forEach((interaction) => {
            if (interaction instanceof ol.interaction.DoubleClickZoom) interaction.setActive(!measureActive && planPhase !== 'optimize');
        });
        const el = map.getTargetElement && map.getTargetElement();
        if (el) el.style.cursor = measureActive ? 'crosshair' : '';
      }
      syncMeasureButton();
      renderMeasure();
      if (measureActive) showStatus('Distanzmessung aktiv. Klick setzt Punkte, Doppelklick beendet.');
    }

    function finishMeasure(evt) {
      if (measureCoords.length >= 2 && map && typeof map.getPixelFromCoordinate === 'function') {
        const a = map.getPixelFromCoordinate(measureCoords[measureCoords.length - 2]);
        const b = map.getPixelFromCoordinate(measureCoords[measureCoords.length - 1]);
        if (a && b && Math.hypot(a[0] - b[0], a[1] - b[1]) < 14) measureCoords.pop();
      }
      if (evt && typeof evt.preventDefault === 'function') evt.preventDefault();
      setMeasureActive(false);
    }

    function clearMeasure() {
      measureCoords = [];
      measureHover = null;
      renderMeasure();
      showStatus('Messpunkte gelöscht.');
    }

    function editorHasFieldGeometry() {
      if (currentPolygonFeature) return true;
      return String(els.flaechendaten && els.flaechendaten.value || '').trim() !== '';
    }

    function centerOnDeviceOrKnonau() {
      if (!map || editorHasFieldGeometry()) return;
      const knonau = [8.4617, 47.2236];
      map.getView().setCenter(ol.proj.fromLonLat(knonau));
      map.getView().setZoom(14);
      if (!navigator.geolocation) return;
      navigator.geolocation.getCurrentPosition((pos) => {
        if (!map || editorHasFieldGeometry()) return;
        const lon = Number(pos.coords.longitude);
        const lat = Number(pos.coords.latitude);
        if (!Number.isFinite(lon) || !Number.isFinite(lat)) return;
        map.getView().setCenter(ol.proj.fromLonLat([lon, lat]));
        map.getView().setZoom(15);
      }, () => {}, { enableHighAccuracy: true, timeout: 4000, maximumAge: 120000 });
    }

    function ensureMap() {
      if (map) return;
      polygonSource = new ol.source.Vector();
      startSource = new ol.source.Vector();
      parkSource = new ol.source.Vector();
      poiSource = new ol.source.Vector();
      poiLayer = new ol.layer.Vector({
        source: poiSource,
        style: (feature) => new ol.style.Style({
          image: new ol.style.Circle({
            radius: 8,
            fill: new ol.style.Fill({ color: '#c2410c' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 2 }),
          }),
          text: new ol.style.Text({
            text: String(feature.get('poiLabel') || ''),
            font: '12px sans-serif',
            offsetY: -16,
            fill: new ol.style.Fill({ color: '#9a3412' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 }),
          }),
        }),
      });
      parkLayer = new ol.layer.Vector({
        source: parkSource,
        style: new ol.style.Style({
          image: new ol.style.Circle({
            radius: 8,
            fill: new ol.style.Fill({ color: '#1d4ed8' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 2 }),
          }),
        }),
      });
      findPlaceSource = new ol.source.Vector();
      measureSource = new ol.source.Vector();
      manualPathSource = new ol.source.Vector();
      computedPathSource = new ol.source.Vector();
      controlPointSource = new ol.source.Vector();
      coverageSource = new ol.source.Vector();
      overlapSource = new ol.source.Vector();
      uncoveredSource = new ol.source.Vector();
      wsVisualSource = new ol.source.Vector();
      vertexSource = new ol.source.Vector();
      midpointSource = new ol.source.Vector();
      osmLayer = new ol.layer.Tile({
        source: new ol.source.OSM(),
        visible: false,
      });
      swisstopoLayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
          url: 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.pixelkarte-farbe/default/current/3857/{z}/{x}/{y}.jpeg',
          attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
          maxZoom: 19,
        }),
        visible: true,
      });
      swissgrauLayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
          url: 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.pixelkarte-grau/default/current/3857/{z}/{x}/{y}.jpeg',
          attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
          maxZoom: 19,
        }),
        visible: false,
      });
      swissimageLayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
          url: 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.swissimage/default/current/3857/{z}/{x}/{y}.jpeg',
          attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
          maxZoom: 19,
        }),
        visible: false,
      });
      polygonLayer = new ol.layer.Vector({
        source: polygonSource,
        style: () => {
          const showUncovered = !!(els.showUncoveredAreas && els.showUncoveredAreas.checked);
          return new ol.style.Style({
            stroke: new ol.style.Stroke({ color: '#ff4d4d', width: 2.2 }),
            fill: new ol.style.Fill({ color: showUncovered ? 'rgba(255,70,70,0.18)' : 'rgba(255,70,70,0.10)' }),
          });
        },
      });
      startLayer = new ol.layer.Vector({
        source: startSource,
        style: (feature) => {
          const startText = String(feature.get('startLabel') || 'Startpunkt');
          const aslText = String(feature.get('aslLabel') || '-');
          return [
            new ol.style.Style({
              image: new ol.style.Circle({
                radius: 6,
                fill: new ol.style.Fill({ color: '#f5d90a' }),
                stroke: new ol.style.Stroke({ color: '#1a1a1a', width: 2 }),
              }),
            }),
            new ol.style.Style({
              text: new ol.style.Text({
                text: startText,
                offsetY: -14,
                font: '12px Arial',
                fill: new ol.style.Fill({ color: '#1a1a1a' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
              }),
            }),
            new ol.style.Style({
              text: new ol.style.Text({
                text: aslText,
                offsetY: 14,
                font: '12px Arial',
                fill: new ol.style.Fill({ color: '#1a1a1a' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
              }),
            }),
          ];
        },
      });
      findPlaceLayer = new ol.layer.Vector({
        source: findPlaceSource,
        visible: true,
        style: (feature) => {
          const label = String(feature.get('findLabel') || 'Ort');
          return [
            new ol.style.Style({
              image: new ol.style.Circle({
                radius: 7,
                fill: new ol.style.Fill({ color: 'rgba(125,65,200,0.92)' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 1.6 }),
              }),
            }),
            new ol.style.Style({
              text: new ol.style.Text({
                text: label,
                offsetY: -16,
                font: '12px Arial',
                fill: new ol.style.Fill({ color: '#4e2a87' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
              }),
            }),
          ];
        },
      });
      measureLayer = new ol.layer.Vector({
        source: measureSource,
        visible: true,
        style: (feature) => {
          if (feature.get('measureRubber')) {
            return new ol.style.Style({
              stroke: new ol.style.Stroke({ color: 'rgba(29,78,137,0.55)', width: 2, lineDash: [6, 6] }),
            });
          }
          const g = feature.getGeometry();
          if (g instanceof ol.geom.LineString) {
            return new ol.style.Style({
              stroke: new ol.style.Stroke({ color: '#1d4e89', width: 2.5 }),
            });
          }
          return new ol.style.Style({
            image: new ol.style.Circle({
              radius: 5,
              fill: new ol.style.Fill({ color: '#1d4e89' }),
              stroke: new ol.style.Stroke({ color: '#fff', width: 2 }),
            }),
          });
        },
      });
      manualPathLayer = new ol.layer.Vector({
        source: manualPathSource,
        visible: true,
        style: (feature, resolution) => {
          const showDirectionInfo = !!(els.showSectionDirections && els.showSectionDirections.checked);
          const wpTypeNow = String(els.msWaypointType && els.msWaypointType.value || '');
          const showTurnGuide = !!(els.showTurnCurves && els.showTurnCurves.checked);
          const g = feature.getGeometry();
          if (g instanceof ol.geom.LineString) {
            if (feature.get('isTurnPath') || feature.get('isTurnGuide')) {
              if (!showTurnGuide) return null;
              return new ol.style.Style({
                stroke: new ol.style.Stroke({
                  color: 'rgba(107,114,128,0.95)',
                  width: 2.6,
                }),
              });
            }
            return new ol.style.Style({
              stroke: new ol.style.Stroke({ color: '#2f6dff', width: 2.6 }),
            });
          }
          if (feature.get('isSectionDirectionPoint')) {
            if (!showDirectionInfo) return null;
            const headingDeg = Number(feature.get('sectionHeadingDeg'));
            const headingRad = Number.isFinite(headingDeg) ? (headingDeg * Math.PI / 180) : 0;
            return new ol.style.Style({
              image: new ol.style.RegularShape({
                points: 3,
                radius: 7,
                rotation: headingRad,
                angle: Math.PI / 2,
                fill: new ol.style.Fill({ color: '#2f6dff' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 1.2 }),
              }),
            });
          }
          const idx = Number(feature.get('pathIdx') || 0);
          const deviates = feature.get('pathDeviates') === true;
          const topText = idx > 0 ? String(idx) : String(feature.get('pathTopLabel') || '-');
          const bottomText = String(feature.get('pathBottomLabel') || '-');
          const isSelected = idx === selectedWaypointIndex1 && (rightDrawerMode === 'waypoint' || planPhase === 'waypoints');
          const pointColor = isSelected ? '#f5d90a' : (deviates ? '#ff8c00' : '#2f6dff');
          return [
            new ol.style.Style({
              image: new ol.style.Circle({
                radius: isSelected ? 8 : 4.8,
                fill: new ol.style.Fill({ color: pointColor }),
                stroke: new ol.style.Stroke({ color: '#fff', width: isSelected ? 2 : 1.2 }),
              }),
            }),
            ...cameraHeadingTriangleStyles(feature, resolution, pointColor),
            new ol.style.Style({
              text: new ol.style.Text({
                text: topText,
                offsetY: -16,
                font: mapFont(18, true),
                fill: new ol.style.Fill({ color: '#1f2937' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
              }),
            }),
            ...(bottomText ? [new ol.style.Style({
              text: new ol.style.Text({
                text: bottomText,
                offsetY: 12,
                font: mapFont(12, false),
                fill: new ol.style.Fill({ color: '#6b7280' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2.6 }),
              }),
            })] : []),
            ...pathGimbalStyle(feature),
            ...pathStartRelStyle(feature),
          ];
        },
      });
      computedPathLayer = new ol.layer.Vector({
        source: computedPathSource,
        visible: true,
        style: (feature, resolution) => {
          const showDirectionInfo = !!(els.showSectionDirections && els.showSectionDirections.checked);
          const wpTypeNow = String(els.msWaypointType && els.msWaypointType.value || '');
          const showTurnGuide = !!(els.showTurnCurves && els.showTurnCurves.checked);
          const g = feature.getGeometry();
          if (g instanceof ol.geom.LineString) {
            if (feature.get('isTurnPath') || feature.get('isTurnGuide')) {
              if (!showTurnGuide) return null;
              return new ol.style.Style({
                stroke: new ol.style.Stroke({
                  color: 'rgba(107,114,128,0.95)',
                  width: 2.6,
                }),
              });
            }
            return new ol.style.Style({
              stroke: new ol.style.Stroke({ color: '#2f6dff', width: 2.6 }),
            });
          }
          if (feature.get('isSectionDirectionPoint')) {
            if (!showDirectionInfo) return null;
            const headingDeg = Number(feature.get('sectionHeadingDeg'));
            const headingRad = Number.isFinite(headingDeg) ? (headingDeg * Math.PI / 180) : 0;
            return new ol.style.Style({
              image: new ol.style.RegularShape({
                points: 3,
                radius: 7,
                rotation: headingRad,
                angle: Math.PI / 2,
                fill: new ol.style.Fill({ color: '#2f6dff' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 1.2 }),
              }),
            });
          }
          const idx = Number(feature.get('pathIdx') || 0);
          const deviates = feature.get('pathDeviates') === true;
          const topText = idx > 0 ? String(idx) : String(feature.get('pathTopLabel') || '-');
          const bottomText = String(feature.get('pathBottomLabel') || '-');
          const isSelected = idx === selectedWaypointIndex1 && (rightDrawerMode === 'waypoint' || planPhase === 'waypoints');
          const pointColor = isSelected ? '#f5d90a' : (deviates ? '#ff8c00' : '#2f6dff');
          return [
            new ol.style.Style({
              image: new ol.style.Circle({
                radius: isSelected ? 8 : 4.8,
                fill: new ol.style.Fill({ color: pointColor }),
                stroke: new ol.style.Stroke({ color: '#fff', width: isSelected ? 2 : 1.2 }),
              }),
            }),
            ...cameraHeadingTriangleStyles(feature, resolution, pointColor),
            new ol.style.Style({
              text: new ol.style.Text({
                text: topText,
                offsetY: -16,
                font: mapFont(18, true),
                fill: new ol.style.Fill({ color: '#1f2937' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
              }),
            }),
            ...(bottomText ? [new ol.style.Style({
              text: new ol.style.Text({
                text: bottomText,
                offsetY: 12,
                font: mapFont(12, false),
                fill: new ol.style.Fill({ color: '#6b7280' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2.6 }),
              }),
            })] : []),
            ...pathGimbalStyle(feature),
            ...pathStartRelStyle(feature),
          ];
        },
      });
      controlPointLayer = new ol.layer.Vector({
        source: controlPointSource,
        visible: true,
      });
      coverageLayer = new ol.layer.Vector({
        source: coverageSource,
        visible: true,
        style: (feature, resolution) => {
          const g = feature.getGeometry();
          if (g instanceof ol.geom.Polygon) {
            const polyFill = String(feature.get('coverageFillColor') || 'rgba(64,196,255,0.46)');
            return new ol.style.Style({
              fill: new ol.style.Fill({ color: polyFill }),
              stroke: null,
            });
          }
          if (g instanceof ol.geom.LineString) {
            const widthM = Number(feature.get('coverageWidthM'));
            const fillColor = String(feature.get('coverageFillColor') || 'rgba(60,150,255,0.11)');
            const coords = g.getCoordinates();
            const sample = Array.isArray(coords) && coords.length ? coords[Math.floor(coords.length / 2)] : null;
            const scale = meterToMapUnitScaleAt(sample);
            const widthPx = (Number.isFinite(widthM) && widthM > 0 && Number.isFinite(resolution) && resolution > 0)
              ? Math.max(2, Math.min(260, (widthM * scale) / resolution))
              : 2;
            return new ol.style.Style({
              stroke: new ol.style.Stroke({
                color: fillColor,
                width: widthPx,
                lineJoin: 'round',
                // Square cap closes tiny endpoint gaps without circular blobs.
                lineCap: 'square',
                miterLimit: 2,
              }),
            });
          }
          return null;
        },
      });
      overlapLayer = new ol.layer.Vector({
        source: overlapSource,
        visible: false,
      });
      uncoveredLayer = new ol.layer.Vector({
        source: uncoveredSource,
        visible: true,
      });
      wsVisualLayer = new ol.layer.Vector({
        source: wsVisualSource,
        visible: true,
        zIndex: 130,
        style: (feature) => {
          const label = String(feature.get('wsLabel') || '');
          const color = String(feature.get('wsColor') || '#1d4ed8');
          return new ol.style.Style({
            stroke: new ol.style.Stroke({ color, width: 3 }),
            text: new ol.style.Text({
              text: label,
              font: 'bold 11px Arial',
              offsetY: -10,
              fill: new ol.style.Fill({ color }),
              stroke: new ol.style.Stroke({ color: 'rgba(255,255,255,0.98)', width: 3 }),
            }),
          });
        },
      });
      vertexLayer = new ol.layer.Vector({
        source: vertexSource,
        visible: false,
        style: (feature) => {
          const txtTop = '';
          const txtBottom = String(feature.get('aslLabel') || '-');
          return [
            new ol.style.Style({
              image: new ol.style.Circle({
                radius: 5.8,
                fill: new ol.style.Fill({ color: '#ffffff' }),
                stroke: new ol.style.Stroke({ color: '#ff4d4d', width: 3 }),
              }),
            }),
            ...(txtTop ? [new ol.style.Style({
              text: new ol.style.Text({
                text: txtTop,
                offsetY: -12,
                font: '11px Arial',
                fill: new ol.style.Fill({ color: '#6b7280' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
              }),
            })] : []),
            new ol.style.Style({
              text: new ol.style.Text({
                text: txtBottom,
                offsetY: 12,
                font: '11px Arial',
                fill: new ol.style.Fill({ color: '#6b7280' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
              }),
            }),
          ];
        },
      });
      midpointLayer = new ol.layer.Vector({
        source: midpointSource,
        visible: false,
        style: new ol.style.Style({
          text: new ol.style.Text({
            text: '+',
            font: 'bold 14px Arial',
            fill: new ol.style.Fill({ color: '#6b7280' }),
            stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
          }),
        }),
      });
      pathMidpointLayer = new ol.layer.Vector({
        source: new ol.source.Vector(),
        visible: false,
        style: new ol.style.Style({
          image: new ol.style.Circle({
            radius: 11,
            fill: new ol.style.Fill({ color: '#2f6dff' }),
            stroke: new ol.style.Stroke({ color: '#fff', width: 2 }),
          }),
          text: new ol.style.Text({
            text: '+',
            font: 'bold 16px Arial',
            fill: new ol.style.Fill({ color: '#fff' }),
          }),
        }),
      });
      map = new ol.Map({
        target: els.editorMap,
        layers: [
          osmLayer,
          swisstopoLayer,
          swissgrauLayer,
          swissimageLayer,
          polygonLayer,
          overlapLayer,
          uncoveredLayer,
          wsVisualLayer,
          coverageLayer,
          manualPathLayer,
          computedPathLayer,
          controlPointLayer,
          pathMidpointLayer,
          vertexLayer,
          midpointLayer,
          parkLayer,
          poiLayer,
          startLayer,
          findPlaceLayer,
          measureLayer,
        ],
        view: new ol.View({ center: ol.proj.fromLonLat([8.4617, 47.2236]), zoom: 14 }),
      });
      raiseFieldDataLayers();
      map.on('moveend', () => {
        if (manualPathLayer && typeof manualPathLayer.changed === 'function') manualPathLayer.changed();
        if (computedPathLayer && typeof computedPathLayer.changed === 'function') computedPathLayer.changed();
        refreshWsVisualLines();
        updateMapScaleBar();
      });
      try { map.getView().on('change:resolution', updateMapScaleBar); } catch (_) {}
      try { map.getView().on('change:center', updateMapScaleBar); } catch (_) {}
      updateMapScaleBar();
      centerOnDeviceOrKnonau();
      modifyInteraction = new ol.interaction.Modify({ source: polygonSource });
      pathEditFeatures = new ol.Collection();
      pathModifyInteraction = new ol.interaction.Modify({
        features: pathEditFeatures,
        pixelTolerance: 20,
        insertVertexCondition: () => false,
      });
      startModifyInteraction = new ol.interaction.Modify({ source: startSource });
      snapInteraction = new ol.interaction.Snap({ source: polygonSource });
      map.addInteraction(snapInteraction);
      map.addInteraction(modifyInteraction);
      map.addInteraction(pathModifyInteraction);
      map.addInteraction(startModifyInteraction);
      modifyInteraction.setActive(false);
      pathModifyInteraction.setActive(false);
      startModifyInteraction.setActive(false);
      parkModifyInteraction = new ol.interaction.Modify({ source: parkSource });
      map.addInteraction(parkModifyInteraction);
      parkModifyInteraction.setActive(false);
      parkModifyInteraction.on('modifyend', () => {
        const feat = parkSource.getFeatures()[0];
        if (!feat || !feat.getGeometry()) return;
        const c = ol.proj.toLonLat(feat.getGeometry().getCoordinates());
        parkEditMarked = true;
        placeParkAtLonLat(c[0], c[1]);
      });
      startModifyInteraction.on('modifystart', () => pushHistory('Vor Startpunkt-Verschieben'));
      startModifyInteraction.on('modifyend', () => {
        const feats = startSource.getFeatures();
        currentStartFeature = feats.length ? feats[0] : null;
        applyStartPointChange('Startpunkt verschoben');
        showStatus('Startpunkt verschoben. Flugpfad wird neu berechnet.');
      });
      modifyInteraction.on('modifystart', () => pushHistory('Vor Feldrand-Bearbeitung'));
      modifyInteraction.on('modifyend', () => {
        syncFormFromMap();
        refreshPolygonVertexLabels();
        refreshMidpointHandles();
        pushHistory('Feldrand bearbeitet');
      });
      pathModifyInteraction.on('modifystart', (evt) => {
        if (!isPathEditModeActive) return;
        pushHistory('Vor Pfad-Bearbeitung');
        const syncLine = () => {
          const line = manualPathSource.getFeatures().find((f) => {
            const g = f.getGeometry();
            return g instanceof ol.geom.LineString && !f.get('isTurnGuide');
          });
          if (!line) return;
          const pts = manualPathSource.getFeatures()
            .filter((f) => f.getGeometry() instanceof ol.geom.Point && Number(f.get('pathIdx')) > 0)
            .sort((a, b) => Number(a.get('pathIdx')) - Number(b.get('pathIdx')));
          if (pts.length < 2) return;
          line.getGeometry().setCoordinates(pts.map((f) => f.getGeometry().getCoordinates().slice()));
        };
        const feats = evt && evt.features && typeof evt.features.getArray === 'function'
          ? evt.features.getArray()
          : [];
        feats.forEach((f) => {
          const g = f.getGeometry && f.getGeometry();
          if (g && typeof g.on === 'function') g.on('change', syncLine);
        });
      });
      pathModifyInteraction.on('modifyend', () => {
        if (!isPathEditModeActive) return;
        const pointFeats = manualPathSource.getFeatures()
          .filter((f) => f.getGeometry() instanceof ol.geom.Point && Number(f.get('pathIdx')) > 0)
          .sort((a, b) => Number(a.get('pathIdx')) - Number(b.get('pathIdx')));
        if (pointFeats.length < 2) return;
        const prev = normalizeTripletPath(currentManualPath);
        const defaultAlt = Number(els.std_flughoehe && els.std_flughoehe.value);
        const fallbackAlt = Number.isFinite(defaultAlt) ? defaultAlt : 0;
        const next = pointFeats.map((f, i) => {
          const ll = ol.proj.toLonLat(f.getGeometry().getCoordinates());
          const alt = Number(prev[i] && prev[i][2]);
          return [Number(ll[0]), Number(ll[1]), Number.isFinite(alt) ? alt : fallbackAlt];
        });
        commitManualPathToForm(next, 'Pfad bearbeitet');
        showStatus('Wegpunkt verschoben. Suchabdeckung aktualisiert.');
      });
      map.on('pointermove', (evt) => {
        if (!measureActive) return;
        measureHover = evt.coordinate ? evt.coordinate.slice() : null;
        renderMeasure();
      });
      map.on('singleclick', (evt) => {
        if (poiPlaceArmed && evt && evt.coordinate && !measureActive) {
          const ll = ol.proj.toLonLat(evt.coordinate);
          storePoiPoint(ll[0], ll[1], poiPlaceTarget || 'waypoint');
          poiPlaceArmed = false;
          showStatus('POI gesetzt. Start und Parkplatz bleiben unverändert.');
          return;
        }
        if (planPhase === 'start' && !measureActive) {
          handleStartStatusClick(evt);
          return;
        }
        if (measureActive) {
          if (evt.coordinate) measureCoords.push(evt.coordinate.slice());
          renderMeasure();
          return;
        }
        if (isPathEditModeActive && pathMidpointLayer && pathMidpointLayer.getVisible()) {
          const pathMidFeat = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => (layer === pathMidpointLayer ? f : null), { hitTolerance: 16 });
          if (pathMidFeat) {
            const insertIndex = Number(pathMidFeat.get('insertIndex'));
            if (Number.isInteger(insertIndex) && insertIndex > 0) {
              const pts = normalizeTripletPath(currentManualPath);
              if (pts.length >= 2) {
                const xy = pathMidFeat.getGeometry().getCoordinates();
                const ll = ol.proj.toLonLat(xy);
                const prev = pts[insertIndex - 1] || pts[0];
                const next = pts[insertIndex] || prev;
                const alt = Number.isFinite(Number(prev[2])) && Number.isFinite(Number(next[2]))
                  ? (Number(prev[2]) + Number(next[2])) * 0.5
                  : Number(prev[2] ?? els.std_flughoehe.value ?? 0);
                const merged = pts.slice();
                merged.splice(insertIndex, 0, [Number(ll[0]), Number(ll[1]), Number(alt)]);
                reindexWaypointOverrides(insertIndex, 1);
                commitManualPathToForm(merged, 'Pfadpunkt eingefuegt');
                showStatus('Wegpunkt eingefügt. Suchabdeckung aktualisiert.');
                return;
              }
            }
          }
        }
        const pathFeat = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => {
          if ((layer === manualPathLayer || layer === computedPathLayer)
            && f.getGeometry() instanceof ol.geom.Point
            && Number(f.get('pathIdx')) > 0) {
            return f;
          }
          return null;
        });
        if (pathFeat) {
          if (planPhase === 'waypoints' || planPhase === 'finish') {
            openWaypointDrawer(Number(pathFeat.get('pathIdx')));
          }
          return;
        }
        const drawingField = !!(drawInteraction && drawInteraction.getActive && drawInteraction.getActive());
        const drawingStart = !!(startDrawInteraction && startDrawInteraction.getActive && startDrawInteraction.getActive());
        if (!drawingField && !drawingStart && dabsCtl && dabsCtl.showAtEvent(evt)) return;
        if (!isEditModeActive && !isPathEditModeActive) {
          if (window.RkrkSwissOverlays && typeof window.RkrkSwissOverlays.runIdentifyChain === 'function') {
            window.RkrkSwissOverlays.runIdentifyChain(evt, [uasCtl, obstInfoCtl]);
          }
        }
        if (!isEditModeActive || !midpointLayer || !midpointLayer.getVisible()) return;
        const feat = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => (layer === midpointLayer ? f : null));
        if (!feat || !currentPolygonFeature) return;
        const insertIndex = Number(feat.get('insertIndex'));
        if (!Number.isInteger(insertIndex) || insertIndex < 1) return;
        pushHistory('Vor Punkt-Einfuegen');
        const geom = currentPolygonFeature.getGeometry();
        const ring = geom.getCoordinates()[0] || [];
        if (ring.length < 4) return;
        const xy = feat.getGeometry().getCoordinates();
        ring.splice(insertIndex, 0, [xy[0], xy[1]]);
        geom.setCoordinates([ring]);
        syncFormFromMap();
        refreshPolygonVertexLabels();
        refreshMidpointHandles();
        pushHistory('Punkt eingefuegt');
        showStatus('Neuer Feldpunkt eingefuegt.');
      });
      map.on('dblclick', (evt) => {
        if (measureActive) {
          if (evt.originalEvent && typeof evt.originalEvent.preventDefault === 'function') {
            evt.originalEvent.preventDefault();
          }
          finishMeasure(evt);
          return;
        }
        // Double-click on start marker removes the start point.
        const startFeat = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => (layer === startLayer ? f : null));
        if (startFeat && currentStartFeature) {
          if (evt.originalEvent && typeof evt.originalEvent.preventDefault === 'function') {
            evt.originalEvent.preventDefault();
          }
          pushHistory('Vor Startpunkt-Loeschen');
          startSource.clear();
          currentStartFeature = null;
          syncFormFromMap();
          pushHistory('Startpunkt geloescht');
          showStatus('Startpunkt geloescht.');
          return;
        }

        if (isPathEditModeActive && manualPathLayer && manualPathLayer.getVisible()) {
          const pathPoint = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => {
            if (layer !== manualPathLayer) return undefined;
            if (!(f.getGeometry() instanceof ol.geom.Point)) return undefined;
            if (!(Number(f.get('pathIdx')) > 0)) return undefined;
            return f;
          }, { hitTolerance: 14 });
          if (pathPoint) {
            const removeIndex = Number(pathPoint.get('pathIdx')) - 1;
            const pts = normalizeTripletPath(currentManualPath);
            if (Number.isInteger(removeIndex) && removeIndex >= 0 && pts.length > 2 && removeIndex < pts.length) {
              if (evt.originalEvent && typeof evt.originalEvent.preventDefault === 'function') {
                evt.originalEvent.preventDefault();
              }
              const next = pts.slice();
              next.splice(removeIndex, 1);
              reindexWaypointOverrides(removeIndex, -1);
              commitManualPathToForm(next, 'Pfadpunkt geloescht');
              showStatus('Wegpunkt gelöscht. Suchabdeckung aktualisiert.');
              return;
            }
          }
        }

        if (!isEditModeActive || !vertexLayer || !vertexLayer.getVisible()) return;
        const feat = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => (layer === vertexLayer ? f : null));
        if (!feat || !currentPolygonFeature) return;
        const removeIndex = Number(feat.get('vertexIndex'));
        if (!Number.isInteger(removeIndex) || removeIndex < 0) return;
        const geom = currentPolygonFeature.getGeometry();
        const ring = geom.getCoordinates()[0] || [];
        const n = ring.length - 1;
        if (n <= 3 || removeIndex >= n) return;
        if (evt.originalEvent && typeof evt.originalEvent.preventDefault === 'function') {
          evt.originalEvent.preventDefault();
        }
        pushHistory('Vor Punkt-Loeschen');
        ring.splice(removeIndex, 1);
        ring[ring.length - 1] = ring[0].slice();
        geom.setCoordinates([ring]);
        syncFormFromMap();
        refreshPolygonVertexLabels();
        refreshMidpointHandles();
        pushHistory('Punkt geloescht');
        showStatus('Feldpunkt geloescht.');
      });
      applyBaseMapMode();
      syncBasemapButtons();
      restoreOverlayOpacityState();
      applyOverlayLayers();
      updateUndoButtonState();
      initPlanWizard();
      initDisplayControlCollapse();
      initLayerControlCollapse();
      initFieldCharacteristicsCollapse();
      initMenuFolds();
      makeMapBoxDraggable(els.mapMenuBox, '.plan-wizard-head', 'mapMenuPos');
      makeMapBoxDraggable(els.swissOverlayBox, 'summary', 'swissOverlayPos');
      makeMapBoxDraggable(els.displayControlBox, '.title', 'displayControlPos');
      placeDisplayControlInView();
      makeMapBoxDraggable(els.fieldCharacteristicsBox, 'summary', 'fieldCharacteristicsPos');
      makeMapBoxDraggable(els.wpDebugTableBox, 'summary', 'waypointDataPos');
      makeMapBoxDraggable(els.formDrawerBox, 'summary', 'formDrawerPos');
      window.rkrkUpdateMapSize = function () {
        if (map && typeof map.updateSize === 'function') map.updateSize();
      };
      restoreFloatingUiState();
      refreshFlightPathLayers();
    }

    async function fetchGeoAdminTerrainHeight(lon, lat) {
      const key = `${Number(lon).toFixed(7)},${Number(lat).toFixed(7)}`;
      if (terrainHeightCache.has(key)) return terrainHeightCache.get(key);
      const reframeUrl = `https://geodesy.geo.admin.ch/reframe/wgs84tolv95?easting=${encodeURIComponent(Number(lon).toFixed(7))}&northing=${encodeURIComponent(Number(lat).toFixed(7))}&format=json`;
      const reframeResp = await fetch(reframeUrl, { cache: 'no-store' });
      if (!reframeResp.ok) throw new Error(`REFRAME ${reframeResp.status}`);
      const reframe = await reframeResp.json();
      const easting = Number(reframe.easting);
      const northing = Number(reframe.northing);
      if (!Number.isFinite(easting) || !Number.isFinite(northing)) throw new Error('Invalid LV95');
      const heightUrl = `https://api3.geo.admin.ch/rest/services/height?easting=${encodeURIComponent(easting.toFixed(3))}&northing=${encodeURIComponent(northing.toFixed(3))}&sr=2056`;
      const heightResp = await fetch(heightUrl, { cache: 'no-store' });
      if (!heightResp.ok) throw new Error(`HEIGHT ${heightResp.status}`);
      const hp = await heightResp.json();
      const h = Number(hp.height);
      const out = Number.isFinite(h) ? h : NaN;
      terrainHeightCache.set(key, out);
      return out;
    }

    async function updateStartPointLabelFromTerrain() {
      if (!currentStartFeature) return;
      const lonlat = ol.proj.toLonLat(currentStartFeature.getGeometry().getCoordinates());
      currentStartFeature.set('startLabel', 'Startpunkt');
      let asl = NaN;
      try {
        asl = await fetchGeoAdminTerrainHeight(lonlat[0], lonlat[1]);
      } catch (_) {
        asl = NaN;
      }
      currentStartFeature.set('aslLabel', Number.isFinite(asl) ? `${asl.toFixed(1)}` : '-');
      if (startSource) startSource.changed();
    }

    function applyBaseMapMode() {
      if (!map || !osmLayer || !swisstopoLayer || !swissgrauLayer || !swissimageLayer) return;
      const mode = String(els.baseMapMode.value || 'swisstopo');
      const useSwisstopo = mode === 'swisstopo';
      const useGrau = mode === 'swisstopo-grau';
      const useSwissimage = mode === 'swissimage';
      osmLayer.setVisible(!useSwisstopo && !useGrau && !useSwissimage);
      swisstopoLayer.setVisible(useSwisstopo);
      swissgrauLayer.setVisible(useGrau);
      swissimageLayer.setVisible(useSwissimage);
      updateSwissOverlayVisibility();
    }

    async function applyOverlayLayers() {
      if (!map) return;
      const mode = String(els.baseMapMode.value || 'swisstopo');
      const allow = mode === 'swisstopo' || mode === 'swisstopo-grau' || mode === 'swissimage';
      if (!allow) {
        await setOverlayEnabled('roadNames', false);
        await setOverlayEnabled('electric', false);
        await setOverlayEnabled('boundaries', false);
        await setOverlayEnabled('jagdbann', false);
        await setOverlayEnabled('rpas', false);
        await setOverlayEnabled('uasZones', false);
        await setOverlayEnabled('aulavWohngebiete', false);
        await setOverlayEnabled('alti3dRelief', false);
        await setOverlayEnabled('surface3dRelief', false);
        await setOverlayEnabled('dabs', false);
        await setOverlayEnabled('obstacles', false);
        await setOverlayEnabled('grundbuch', false);
        await setOverlayEnabled('cadastral', false);
        await setOverlayEnabled('nature', false);
        await setOverlayEnabled('cableways', false);
        await setOverlayEnabled('wanderwege', false);
        syncObstacleHeightsSwitch(false);
        return;
      }
      await setOverlayEnabled('roadNames', !!els.layerRoadNames.checked, opacityFromElement(els.layerRoadNamesOpacity));
      await setOverlayEnabled('electric', !!els.layerElectric.checked, opacityFromElement(els.layerElectricOpacity));
      await setOverlayEnabled('boundaries', !!els.layerBoundaries.checked, opacityFromElement(els.layerBoundariesOpacity));
      await setOverlayEnabled('jagdbann', !!els.layerJagdbann.checked, opacityFromElement(els.layerJagdbannOpacity));
      await setOverlayEnabled('rpas', !!els.layerRpas.checked, opacityFromElement(els.layerRpasOpacity));
      await setOverlayEnabled('uasZones', !!(els.layerUas && els.layerUas.checked), opacityFromElement(els.layerUasOpacity));
      await setOverlayEnabled('aulavWohngebiete', !!(els.layerAulav && els.layerAulav.checked), opacityFromElement(els.layerAulavOpacity));
      await setOverlayEnabled('alti3dRelief', !!(els.layerAlti3d && els.layerAlti3d.checked), opacityFromElement(els.layerAlti3dOpacity));
      await setOverlayEnabled('surface3dRelief', !!(els.layerSurface3d && els.layerSurface3d.checked), opacityFromElement(els.layerSurface3dOpacity));
      await setOverlayEnabled('dabs', !!(els.layerDabs && els.layerDabs.checked), opacityFromElement(els.layerDabsOpacity));
      await setOverlayEnabled('obstacles', !!els.layerObstacles.checked, opacityFromElement(els.layerObstaclesOpacity));
      await setOverlayEnabled('grundbuch', !!els.layerGrundbuch.checked, opacityFromElement(els.layerGrundbuchOpacity));
      await setOverlayEnabled('cadastral', !!els.layerCadastral.checked, opacityFromElement(els.layerCadastralOpacity));
      await setOverlayEnabled('nature', !!els.layerNature.checked, opacityFromElement(els.layerNatureOpacity));
      await setOverlayEnabled('cableways', !!els.layerCableways.checked, opacityFromElement(els.layerCablewaysOpacity));
      await setOverlayEnabled('wanderwege', !!(els.layerWanderwege && els.layerWanderwege.checked), opacityFromElement(els.layerWanderwegeOpacity));
      syncObstacleHeightsSwitch(!!(els.layerObstacles && els.layerObstacles.checked));

      setOpacityLabel(els.layerRoadNamesOpacityLabel, els.layerRoadNamesOpacity);
      setOpacityLabel(els.layerElectricOpacityLabel, els.layerElectricOpacity);
      setOpacityLabel(els.layerBoundariesOpacityLabel, els.layerBoundariesOpacity);
      setOpacityLabel(els.layerJagdbannOpacityLabel, els.layerJagdbannOpacity);
      setOpacityLabel(els.layerRpasOpacityLabel, els.layerRpasOpacity);
      setOpacityLabel(els.layerUasOpacityLabel, els.layerUasOpacity);
      setOpacityLabel(els.layerAulavOpacityLabel, els.layerAulavOpacity);
      setOpacityLabel(els.layerAlti3dOpacityLabel, els.layerAlti3dOpacity);
      setOpacityLabel(els.layerSurface3dOpacityLabel, els.layerSurface3dOpacity);
      setOpacityLabel(els.layerDabsOpacityLabel, els.layerDabsOpacity);
      setOpacityLabel(els.layerObstaclesOpacityLabel, els.layerObstaclesOpacity);
      setOpacityLabel(els.layerGrundbuchOpacityLabel, els.layerGrundbuchOpacity);
      setOpacityLabel(els.layerCadastralOpacityLabel, els.layerCadastralOpacity);
      setOpacityLabel(els.layerNatureOpacityLabel, els.layerNatureOpacity);
      setOpacityLabel(els.layerCablewaysOpacityLabel, els.layerCablewaysOpacity);
      setOpacityLabel(els.layerWanderwegeOpacityLabel, els.layerWanderwegeOpacity);

      saveFloatingUiState({
        overlayOpacity: overlayOpacityStateFromInputs(),
        overlayToggles: overlayToggleStateFromInputs(),
      });
    }

    function syncBasemapButtons() {
      const mode = String((els.baseMapMode && els.baseMapMode.value) || 'swisstopo');
      document.querySelectorAll('#basemapTopBar .basemap-seg').forEach((btn) => {
        btn.classList.toggle('is-on', btn.getAttribute('data-basemap') === mode);
      });
    }

    function initBasemapToggle() {
      document.querySelectorAll('#basemapTopBar .basemap-seg').forEach((btn) => {
        btn.addEventListener('click', () => {
          if (!els.baseMapMode) return;
          const next = btn.getAttribute('data-basemap') || 'swisstopo';
          if (els.baseMapMode.value === next) {
            syncBasemapButtons();
            return;
          }
          els.baseMapMode.value = next;
          els.baseMapMode.dispatchEvent(new Event('change', { bubbles: true }));
        });
      });
      syncBasemapButtons();
    }

    function showLayerControlOnce() {
      let seen = false;
      try { seen = localStorage.getItem('fieldEditorLayerControlV1') === '1'; } catch (_) {}
      if (seen) return;
      try { localStorage.setItem('fieldEditorLayerControlV1', '1'); } catch (_) {}
      if (els.showBoxSwissOverlay) els.showBoxSwissOverlay.checked = true;
      if (els.swissOverlayBox) els.swissOverlayBox.open = true;
      if (typeof applyBoxVisibilityStateFromSetup === 'function') applyBoxVisibilityStateFromSetup();
    }

    function updateSwissOverlayVisibility() {
      syncBasemapButtons();
      if (els.showBoxSwissOverlay) {
        els.showBoxSwissOverlay.disabled = false;
        els.showBoxSwissOverlay.title = 'Layersteuerung anzeigen';
      }
    }

    function refreshPolygonVertexLabels() {
      if (!vertexSource) return;
      vertexSource.clear();
      if (!currentPolygonFeature) return;
      const geom = currentPolygonFeature.getGeometry();
      if (!geom) return;
      const ring = geom.getCoordinates()[0] || [];
      if (ring.length < 2) return;
      const aglTxt = currentAglLabel();
      const n = ring.length - 1; // ignore duplicated last point
      for (let i = 0; i < n; i++) {
        const f = new ol.Feature({ geometry: new ol.geom.Point(ring[i]) });
        f.set('aglLabel', aglTxt);
        f.set('aslLabel', '-');
        f.set('vertexIndex', i);
        vertexSource.addFeature(f);
      }
      updatePolygonVertexAslLabels();
    }

    function refreshMidpointHandles() {
      if (!midpointSource) return;
      midpointSource.clear();
      if (!currentPolygonFeature) return;
      const geom = currentPolygonFeature.getGeometry();
      if (!geom) return;
      const ring = geom.getCoordinates()[0] || [];
      if (ring.length < 4) return;
      for (let i = 0; i < ring.length - 1; i++) {
        const a = ring[i];
        const b = ring[i + 1];
        const mx = (a[0] + b[0]) * 0.5;
        const my = (a[1] + b[1]) * 0.5;
        const f = new ol.Feature({ geometry: new ol.geom.Point([mx, my]) });
        f.set('insertIndex', i + 1);
        midpointSource.addFeature(f);
      }
    }

    async function updatePolygonVertexAslLabels() {
      if (!vertexSource) return;
      const feats = vertexSource.getFeatures();
      if (!feats.length) return;
      await Promise.all(feats.map(async (f) => {
        try {
          const xy = f.getGeometry() && f.getGeometry().getCoordinates ? f.getGeometry().getCoordinates() : null;
          if (!xy) return;
          const lonlat = ol.proj.toLonLat(xy);
          const asl = await fetchGeoAdminTerrainHeight(lonlat[0], lonlat[1]);
          f.set('aslLabel', Number.isFinite(asl) ? `${asl.toFixed(1)}` : '-');
        } catch (_) {
          f.set('aslLabel', '-');
        }
      }));
      vertexSource.changed();
    }

    function syncFormFromMap() {
      if (!map) return;
      if (currentStartFeature) {
        const c = ol.proj.toLonLat(currentStartFeature.getGeometry().getCoordinates());
        els.startplatz.value = formatPairs([[c[0], c[1]]], false);
      }
      if (currentPolygonFeature) {
        const ring = currentPolygonFeature.getGeometry().getCoordinates()[0] || [];
        const lonlat = ring.map((xy) => ol.proj.toLonLat(xy));
        els.flaechendaten.value = formatPairs(lonlat, true);
      } else {
        els.flaechendaten.value = '';
      }
      // Keep editor behavior aligned with analyse_ui flow:
      // map-side field geometry edits must trigger path recomputation.
      scheduleFlightPathRecompute();
      scheduleGisSuggestion();
    }

    function fitMapToFeatures() {
      if (!map) return;
      const extent = ol.extent.createEmpty();
      let hasAny = false;
      if (currentPolygonFeature) {
        ol.extent.extend(extent, currentPolygonFeature.getGeometry().getExtent());
        hasAny = true;
      }
      if (currentStartFeature) {
        ol.extent.extend(extent, currentStartFeature.getGeometry().getExtent());
        hasAny = true;
      }
      if (hasAny) {
        map.getView().fit(extent, { padding: [30, 30, 30, 30], maxZoom: 18 });
      }
    }

    function syncMapFromForm(recompute) {
      ensureMap();
      polygonSource.clear();
      startSource.clear();
      if (parkSource) parkSource.clear();
      manualPathSource.clear();
      computedPathSource.clear();
      if (controlPointSource) controlPointSource.clear();
      currentPolygonFeature = null;
      currentStartFeature = null;
      currentComputedPath = [];
      currentControlPointsPath = [];
      currentControlPointsField = [];
      currentManualPath = parseTriplets(els.flugweg.value);
      if (!originalManualPath.length) originalManualPath = currentManualPath.slice();

      const polyPts = parsePairs(els.flaechendaten.value);
      if (polyPts.length >= 3) {
        const closed = polyPts.slice();
        const a = closed[0];
        const b = closed[closed.length - 1];
        if (Math.abs(a[0] - b[0]) > 1e-10 || Math.abs(a[1] - b[1]) > 1e-10) {
          closed.push([a[0], a[1]]);
        }
        const coords = closed.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
        currentPolygonFeature = new ol.Feature({ geometry: new ol.geom.Polygon([coords]) });
        polygonSource.addFeature(currentPolygonFeature);
      }

      const startPts = parsePairs(els.startplatz.value);
      if (startPts.length >= 1) {
        const c = ol.proj.fromLonLat([startPts[0][0], startPts[0][1]]);
        currentStartFeature = new ol.Feature({ geometry: new ol.geom.Point(c) });
        currentStartFeature.set('startLabel', 'Startpunkt');
        currentStartFeature.set('aslLabel', '-');
        startSource.addFeature(currentStartFeature);
        updateStartPointLabelFromTerrain();
      }
      refreshPolygonVertexLabels();
      refreshMidpointHandles();
      refreshFlightPathLayers();
      paintParkMarker();
      fitMapToFeatures();
      updateSaveButtonState();
      scheduleNewFieldDraftSave();
      if (recompute !== false) scheduleFlightPathRecompute();
      applyPlanStatusTools();
    }

    function scheduleMapSyncFromForm() {
      if (mapSyncTimer) clearTimeout(mapSyncTimer);
      mapSyncTimer = setTimeout(() => {
        syncMapFromForm();
      }, 120);
    }

    function stopAllDrawModes() {
      if (!map) return;
      if (drawInteraction) map.removeInteraction(drawInteraction);
      if (startDrawInteraction) map.removeInteraction(startDrawInteraction);
      drawInteraction = null;
      startDrawInteraction = null;
      modifyInteraction.setActive(false);
      if (pathModifyInteraction) pathModifyInteraction.setActive(false);
      isEditModeActive = false;
      isPathEditModeActive = false;
      if (vertexLayer) vertexLayer.setVisible(false);
      if (midpointLayer) midpointLayer.setVisible(false);
      if (pathMidpointLayer) pathMidpointLayer.setVisible(false);
      refreshFlightPathLayers();
      if (planPhase === 'optimize' && !measureActive) armOptimizePathEdit();
      setContextHelp('default');
    }

    function startPolygonDraw() {
      setPlanPhase('outline');
      ensureMap();
      stopAllDrawModes();
      pushHistory('Vor Feldrand-Zeichnung');
      polygonSource.clear();
      currentPolygonFeature = null;
      drawInteraction = new ol.interaction.Draw({ source: polygonSource, type: 'Polygon' });
      drawInteraction.once('drawend', (evt) => {
        currentPolygonFeature = evt.feature;
        syncFormFromMap();
        refreshPolygonVertexLabels();
        refreshMidpointHandles();
        pushHistory('Feldrand gezeichnet');
        stopAllDrawModes();
        enablePolygonEdit();
        readDrawnFieldGis();
        if (creatingNewField) {
          assignCatalogFromOutline().catch((e) => showStatus(String(e.message || e), false));
        } else {
          showStatus('Feldrand gezeichnet. GIS-Nummer wird aus der CadastralWebMap gelesen.');
        }
      });
      map.addInteraction(drawInteraction);
      showStatus('Zeichenmodus aktiv: Feldrand klicken (Doppelklick beendet).');
      setContextHelp('draw');
    }

    function enablePolygonEdit() {
      ensureMap();
      stopAllDrawModes();
      isEditModeActive = true;
      modifyInteraction.setActive(true);
      if (vertexLayer) {
        vertexLayer.setVisible(
          !!(els.showFieldPolygon && els.showFieldPolygon.checked)
        );
      }
      if (midpointLayer) midpointLayer.setVisible(true);
      refreshPolygonVertexLabels();
      refreshMidpointHandles();
      refreshFlightPathLayers();
      showStatus('Bearbeiten aktiv: Punkte verschieben, + zum Hinzufuegen, Doppelklick zum Loeschen.');
      setContextHelp('edit');
    }

    function enablePathEditMode() {
      ensureMap();
      stopAllDrawModes();
      const pts = normalizeTripletPath(parseTriplets(els.flugweg.value));
      if (pts.length < 2) {
        showStatus('Pfad bearbeiten braucht mindestens 2 Wegpunkte im Flugweg.', false);
        return;
      }
      isPathEditModeActive = true;
      if (els.showManualPath) els.showManualPath.checked = true;
      if (els.showControlPoints) els.showControlPoints.checked = true;
      currentManualPath = pts.map((p) => [p[0], p[1], p[2]]);
      originalManualPath = currentManualPath.map((p) => [p[0], p[1], p[2]]);
      if (pathModifyInteraction) pathModifyInteraction.setActive(true);
      if (pathMidpointLayer) pathMidpointLayer.setVisible(true);
      refreshFlightPathLayers();
      refreshPathMidpointHandles();
      showStatus('Pfad bearbeiten aktiv: Punkte ziehen, + zum Einfuegen, Doppelklick zum Loeschen.');
      setContextHelp('path');
    }

    function applyStartPointChange(historyLabel = 'Startpunkt geaendert') {
      resetOriginalPathOnNextRecompute = true;
      if (currentStartFeature && currentStartFeature.getGeometry()) {
        const c = ol.proj.toLonLat(currentStartFeature.getGeometry().getCoordinates());
        els.startplatz.value = formatPairs([[c[0], c[1]]], false);
      }
      updateStartPointLabelFromTerrain();
      normalizeStoredPlacePoints();
      updateSaveButtonState();
      if (historyLabel) pushHistory(historyLabel);
      recomputeFlightPathNow();
    }

    function applyPlanStatusTools() {
      if (!map || measureActive) return;
      const drawingField = !!(drawInteraction && drawInteraction.getActive && drawInteraction.getActive());
      if (planPhase !== 'outline' && isEditModeActive) {
        isEditModeActive = false;
        if (modifyInteraction) modifyInteraction.setActive(false);
        if (vertexLayer) vertexLayer.setVisible(false);
        if (midpointLayer) midpointLayer.setVisible(false);
      }
      if (planPhase !== 'start') parkEditMarked = false;
      if (startModifyInteraction) startModifyInteraction.setActive(planPhase === 'start' && !parkEditMarked);
      if (parkModifyInteraction) parkModifyInteraction.setActive(planPhase === 'start' && parkEditMarked);
      if (planPhase === 'outline' && currentPolygonFeature && !isEditModeActive && !drawingField) {
        enablePolygonEdit();
      }
    }

    function placeStartAtLonLat(lon, lat) {
      ensureMap();
      const c = ol.proj.fromLonLat([Number(lon), Number(lat)]);
      if (!currentStartFeature) {
        startSource.clear();
        currentStartFeature = new ol.Feature({ geometry: new ol.geom.Point(c) });
        currentStartFeature.set('startLabel', 'Startpunkt');
        currentStartFeature.set('aslLabel', '-');
        startSource.addFeature(currentStartFeature);
      } else if (currentStartFeature.getGeometry()) {
        currentStartFeature.getGeometry().setCoordinates(c);
      }
      applyStartPointChange('Startpunkt gesetzt');
      showStatus('Startpunkt gesetzt.');
    }

    function poiRecordFrom(raw) {
      if (!raw || !Number.isFinite(Number(raw.longitude)) || !Number.isFinite(Number(raw.latitude))) return null;
      return {
        longitude: Number(Number(raw.longitude).toFixed(6)),
        latitude: Number(Number(raw.latitude).toFixed(6)),
        height: Number.isFinite(Number(raw.height)) ? Number(raw.height) : 0,
      };
    }

    function syncMissionPoiAlias() {
      missionPoi = missionPois.length ? missionPois[0] : null;
    }

    function setMissionPois(list) {
      missionPois = (Array.isArray(list) ? list : []).map(poiRecordFrom).filter(Boolean);
      syncMissionPoiAlias();
      paintPoiMarker();
    }

    function storePoiPoint(lon, lat, target) {
      const height = target === 'mission'
        ? Number(els.msAltitude && els.msAltitude.value)
        : Number(els.wpAltitude && els.wpAltitude.value);
      const poi = poiRecordFrom({
        longitude: lon,
        latitude: lat,
        height: Number.isFinite(height) ? height : 0,
      });
      if (!poi) return;
      if (target === 'mission') {
        missionPois.push(poi);
        syncMissionPoiAlias();
      } else {
        const key = String(selectedWaypointIndex1 || 1);
        const prev = (waypointOverrides && waypointOverrides[key] && typeof waypointOverrides[key] === 'object')
          ? waypointOverrides[key]
          : {};
        if (!waypointOverrides || typeof waypointOverrides !== 'object') waypointOverrides = {};
        waypointOverrides[key] = Object.assign({}, prev, { poi: poi });
      }
      const note = 'POI ' + poi.longitude + ', ' + poi.latitude;
      const wpNote = document.getElementById('wpPoiNote');
      const msNote = document.getElementById('msPoiNote');
      if (wpNote) wpNote.textContent = note;
      if (msNote) msNote.textContent = note;
      paintPoiMarker();
      refreshFlightPathLayers();
    }

    function restoreMissionPoi(ms) {
      if (!ms || typeof ms !== 'object') return;
      const fromList = Array.isArray(ms.pois) ? ms.pois : [];
      const list = fromList.length ? fromList : (ms.poi ? [ms.poi] : []);
      setMissionPois(list);
    }

    function paintPoiMarker() {
      const note = document.getElementById('planPoiCoord');
      const clearBtn = document.getElementById('planPoiClear');
      const show = missionPois.length > 0;
      if (clearBtn) clearBtn.hidden = !show;
      if (note) {
        note.textContent = show
          ? missionPois.map((poi, i) => 'POI ' + (i + 1) + ' ' + Number(poi.longitude).toFixed(6) + ', ' + Number(poi.latitude).toFixed(6)).join(' · ')
          : (poiPlaceArmed ? 'POI auf die Karte tippen.' : 'POI für die Gierrichtung Fokus POI.');
        note.classList.toggle('is-coord', show);
      }
      if (!poiSource) return;
      poiSource.clear();
      missionPois.forEach((poi, i) => {
        const feature = new ol.Feature({
          geometry: new ol.geom.Point(ol.proj.fromLonLat([Number(poi.longitude), Number(poi.latitude)])),
        });
        feature.set('poiLabel', 'POI ' + (i + 1));
        poiSource.addFeature(feature);
      });
    }

    function poiFromCurrentPark(target) {
      const park = parsePairs(readControl('parkplatz'))[0];
      if (!park) {
        showStatus('Kein Parkplatz gesetzt.', false);
        return;
      }
      storePoiPoint(park[0], park[1], target);
      poiPlaceArmed = false;
      showStatus('POI am Parkplatz gesetzt. Der Parkplatz selbst bleibt.');
    }

    function placeParkAtLonLat(lon, lat) {
      const point = [Number(lon), Number(lat)];
      const start = parsePairs(els.startplatz && els.startplatz.value)[0];
      if (start && sameCoord6(point, start)) {
        writeControl('parkplatz', '');
        writeControl('create_parkplatz', '');
        parkEditMarked = false;
        refreshParkplatzNote();
        paintParkMarker();
        applyPlanStatusTools();
        showStatus('Parkplatz entspricht dem Startpunkt und wird nicht gespeichert.');
        return;
      }
      const text = formatFixed6([point]);
      writeControl('parkplatz', text);
      writeControl('create_parkplatz', text);
      parkEditMarked = true;
      refreshParkplatzNote();
      paintParkMarker();
      updateSaveButtonState();
      applyPlanStatusTools();
      showStatus('Parkplatz gesetzt: ' + text);
    }

    function handleStartStatusClick(evt) {
      if (!evt || !evt.coordinate) return;
      const parkHit = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => (layer === parkLayer ? f : null), { hitTolerance: 14 });
      const startHit = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => (layer === startLayer ? f : null), { hitTolerance: 14 });
      if (parkHit) {
        parkEditMarked = true;
        applyPlanStatusTools();
        showStatus('Parkplatz ist markiert. Tippen oder ziehen verschiebt ihn.');
        return;
      }
      if (startHit && parkEditMarked) {
        parkEditMarked = false;
        applyPlanStatusTools();
        showStatus('Startpunkt ist markiert.');
        return;
      }
      if (startHit) return;
      const ll = ol.proj.toLonLat(evt.coordinate);
      if (parkEditMarked) placeParkAtLonLat(ll[0], ll[1]);
      else placeStartAtLonLat(ll[0], ll[1]);
    }

    function startPickStart() {
      parkEditMarked = false;
      setPlanPhase('start');
      showStatus('Startpunkt: in die Karte tippen oder den Punkt ziehen.');
      setContextHelp('start');
    }

    function startPickPark() {
      parkEditMarked = true;
      setPlanPhase('start');
      showStatus('Parkplatz ist markiert. Tippen oder ziehen verschiebt den Parkplatz.');
    }

    function askDeleteField(feldId, name) {
      const nummer = String(feldId || '').trim() || '(ohne Nummer)';
      const feldname = String(name || '').trim() || '(ohne Name)';
      return new Promise((resolve) => {
        const backdrop = document.createElement('div');
        backdrop.className = 'field-delete-ask';
        backdrop.setAttribute('role', 'alertdialog');
        backdrop.setAttribute('aria-modal', 'true');
        const box = document.createElement('div');
        box.className = 'field-delete-ask-box';
        const text = document.createElement('p');
        text.textContent = 'Feldkatalognummer ' + nummer + '\nFeldname ' + feldname + '\n\nDieses Feld aus dem Katalog löschen? Die Feldkatalognummer wird frei. Bilder (Suchfeld, Flugplan) und Flugprogramme (KMZ, KML, JSON) dieses Feldes werden gelöscht.';
        const actions = document.createElement('div');
        actions.className = 'field-delete-ask-actions';
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'field-delete-cancel';
        cancel.textContent = 'Abbrechen';
        const ok = document.createElement('button');
        ok.type = 'button';
        ok.className = 'field-delete-confirm';
        ok.textContent = 'Löschen';
        let done = false;
        const finish = (yes) => {
          if (done) return;
          done = true;
          document.removeEventListener('keydown', onKey);
          backdrop.remove();
          resolve(!!yes);
        };
        const onKey = (ev) => {
          if (ev.key === 'Escape') {
            ev.preventDefault();
            finish(false);
          }
        };
        cancel.addEventListener('click', () => finish(false));
        ok.addEventListener('click', () => finish(true));
        backdrop.addEventListener('click', (ev) => {
          if (ev.target === backdrop) finish(false);
        });
        actions.appendChild(cancel);
        actions.appendChild(ok);
        box.appendChild(text);
        box.appendChild(actions);
        backdrop.appendChild(box);
        document.body.appendChild(backdrop);
        document.addEventListener('keydown', onKey);
        cancel.focus();
      });
    }

    async function deleteListedField(field) {
      if (!window.RKRK_KAT) {
        showStatus('Löschen braucht ?KAT.', false);
        return;
      }
      const feldId = String(field.feld_id || '').trim();
      const name = String(field.name || '').trim();
      const yes = await askDeleteField(feldId, name);
      if (!yes) return;
      const r = await fetch(editorApiUrl(), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          mode: 'delete',
          confirm: true,
          kat: true,
          mdb: editorMissionKey(),
          field: { id: String(field.id || ''), feld_id: feldId, name: name },
        }),
      });
      const data = await r.json();
      if (!data.ok) throw new Error(data.detail || data.error || 'Löschen fehlgeschlagen');
      if (String(selectedId) === String(field.id)) selectedId = '';
      await loadList();
      showStatus('Feld ' + (data.deleted_feld_id || feldId) + ' gelöscht.');
    }

    function renderList() {
      const q = String(els.searchInput.value || '').trim().toLowerCase();
      els.fieldList.innerHTML = '';
      allFields
        .filter((f) => !q || `${f.feld_id} ${f.name}`.toLowerCase().includes(q))
        .forEach((f) => {
          const row = document.createElement('div');
          row.className = `field-item${String(f.id) === String(selectedId) ? ' active' : ''}`;
          const feldId = String(f.feld_id || '').trim();
          const name = String(f.name || '').trim();
          const line1 = document.createElement('div');
          line1.className = 'field-item-line';
          const idEl = document.createElement('span');
          idEl.className = 'field-item-id';
          idEl.textContent = feldId;
          const nameEl = document.createElement('span');
          nameEl.className = 'field-item-name';
          nameEl.textContent = name;
          if (name) nameEl.title = name;
          if (feldId) line1.appendChild(idEl);
          if (name) line1.appendChild(nameEl);
          row.appendChild(line1);
          const metaParts = [];
          const funde = String(f.funde || '').trim();
          const dyn = String(f.dynamic_feld_info || '').trim();
          if (funde) metaParts.push(`Funde: ${funde}`);
          if (dyn) metaParts.push(dyn);
          if (metaParts.length || window.RKRK_KAT) {
            const line2 = document.createElement('div');
            line2.className = 'field-item-line';
            if (metaParts.length) {
              const meta = document.createElement('span');
              meta.className = 'field-item-meta';
              meta.textContent = metaParts.join(' | ');
              meta.title = meta.textContent;
              line2.appendChild(meta);
            }
            if (window.RKRK_KAT) {
              const del = document.createElement('button');
              del.type = 'button';
              del.className = 'field-delete-btn';
              del.textContent = 'Löschen';
              del.title = 'Feld aus dem Katalog löschen';
              del.addEventListener('click', (ev) => {
                ev.preventDefault();
                ev.stopPropagation();
                deleteListedField(f).catch((err) => showStatus(String(err && err.message ? err.message : err), false));
              });
              line2.appendChild(del);
            }
            row.appendChild(line2);
          }
          row.addEventListener('click', () => {
            loadField(f.id).catch((err) => showStatus(String(err && err.message ? err.message : err), false));
          });
          els.fieldList.appendChild(row);
        });
      renderQuickSlots();
      renderPlanExistingList();
    }

    function paintDroneCatalogNote(data) {
      const note = document.getElementById('droneCatalogNote');
      if (!note) return;
      if (data && data.catalog === false) {
        note.textContent = data.detail || 'Dieser Drohnentyp hat keinen Feldkatalog.';
        return;
      }
      note.textContent = editorCatalogNoteText;
    }

    async function loadList() {
      if (!window.RKRK_KAT) {
        allFields = [];
        return;
      }
      const r = await fetch(editorApiUrl({ action: 'list' }), { cache: 'no-store' });
      const data = await r.json();
      if (!data.ok) {
        throw new Error(data.detail || data.error || 'list_failed');
      }
      paintDroneCatalogNote(data);
      if (data.catalog === false) {
        allFields = [];
        renderList();
        return;
      }
      allFields = Array.isArray(data.fields) ? data.fields : [];
      renderList();
    }

    function editorHasLocalWaypointFlags() {
      if (window.RkrkField && typeof RkrkField.overridesNeedExpert === 'function') {
        return RkrkField.overridesNeedExpert(waypointOverrides);
      }
      const keys = ['use_mission_height', 'use_mission_speed', 'use_mission_waypoint_type', 'use_mission_yaw', 'use_mission_path_mode', 'use_mission_direction_mode'];
      return Object.values(waypointOverrides || {}).some((row) => row && keys.some((key) => row[key] === false || row[key] === 0 || row[key] === '0'));
    }

    function settleEasyMissionDefaults() {
      if (editorEasyModeForm !== true || editorHasLocalWaypointFlags()) return;
      const d = window.RkrkField && typeof RkrkField.easyMissionDefaults === 'function'
        ? RkrkField.easyMissionDefaults()
        : null;
      if (!d) return;
      if (els.msDirectToStart) els.msDirectToStart.checked = d.direct_to_start_position === true;
      if (els.msSpeed) els.msSpeed.value = Number(d.speed_m_s).toFixed(1);
      if (els.msEndAction) els.msEndAction.value = d.end_action;
      if (els.msHeadingMode) els.msHeadingMode.value = d.heading_mode;
      if (els.msWaypointType) els.msWaypointType.value = d.waypoint_type;
      if (els.msTurnDistance) els.msTurnDistance.value = String(d.standard_turn_distance_m);
      if (els.msSafeStartAltitude) els.msSafeStartAltitude.value = String(d.safe_start_altitude_m);
      if (els.msStartSpeedActive) els.msStartSpeedActive.checked = d.start_speed_active === true;
      if (els.msGimbalMode) els.msGimbalMode.value = d.gimbal_mode;
      syncGimbalAngleFlag();
      if (els.msAltitudeMode) els.msAltitudeMode.value = d.altitude_mode;
      syncPlanSpeedField();
    }

    function setPlanExpertMode(expert) {
      const modeBtn = document.getElementById('planModeToggle');
      if (modeBtn) {
        modeBtn.classList.toggle('is-expert', !!expert);
        modeBtn.textContent = expert ? 'Expertenmodus' : 'Einfacher Modus';
      }
      editorEasyModeForm = !expert;
      if (typeof syncPlanExpertUi === 'function') syncPlanExpertUi();
      paintExpertDebugHints();
      if (typeof refreshDebugTfOffer === 'function') refreshDebugTfOffer();
    }

    function editorDebugOn() {
      return /DEBUG/i.test(String(window.location.search || ''));
    }

    function paintExpertDebugHints() {
      const modeBtn = document.getElementById('planModeToggle');
      const expert = !!(modeBtn && modeBtn.classList.contains('is-expert'));
      if (!window.RkrkField || typeof RkrkField.paintDebugParamHints !== 'function') return;
      RkrkField.paintDebugParamHints([
        ['wpUseMissionHeight', 'useMissionHeight · wpml:useGlobalHeight'],
        ['wpAltitude', 'height · wpml:height (ALT, Differenz zum Start)'],
        ['wpUseMissionSpeed', 'useMissionSpeed · wpml:useGlobalSpeed'],
        ['wpSpeed', 'speed · wpml:waypointSpeed'],
        ['wpUseMissionWaypointType', 'useMissionWaypointType · wpml:useGlobalTurnParam'],
        ['wpWaypointType', 'waypointType · wpml:waypointTurnMode'],
        ['wpTurnDistance', 'curveSize · wpml:waypointTurnDampingDist'],
        ['wpUseMissionYaw', 'useMissionYawDirection · wpml:useGlobalHeadingParam'],
        ['wpHeadingMode', 'directionType · wpml:waypointHeadingMode'],
        ['wpDirection', 'direction · wpml:waypointHeadingAngle'],
        ['wpUseMissionPathMode', 'useMissionPathMode · kein KMZ-Tag'],
        ['wpAlignment', 'alignmentAircraft · wpml:waypointHeadingYawPathMode'],
        ['wpUseMissionDirectionMode', 'useMissionDirectionMode · kein KMZ-Tag'],
        ['msStartSpeedActive', 'start_speed_active · transitionalSpeedActive, kein KMZ-Tag'],
        ['msStartSpeed', 'start_speed_m_s · wpml:globalTransitionalSpeed'],
      ], editorDebugOn() && expert);
    }

    function nextFreeTfId() {
      const ban = { 60: true, 62: true, 63: true, 64: true, 65: true };
      const used = {};
      (allFields || []).forEach((field) => {
        const match = String(field && field.feld_id || '').match(/^TF-(\d+)$/i);
        if (match) used[Number(match[1])] = true;
      });
      let n = 1;
      while (used[n] || ban[n]) n += 1;
      return 'TF-' + String(n).padStart(3, '0');
    }

    function debugTfDirectory() {
      const key = editorMissionKey();
      if (key === '4TD') return 'Missionen Matrice 4TD\\TF_Testfelder';
      if (key === 'D') return 'Missionen Debug\\TF_Testfelder';
      if (key === '3T') return 'Missionen\\TF_Testfelder';
      return '';
    }

    function refreshDebugTfOffer() {
      const box = document.getElementById('debugTfBox');
      if (!box) return;
      const dir = debugTfDirectory();
      box.hidden = !(editorDebugOn() && dir);
      const dirEl = document.getElementById('debugTfDir');
      if (dirEl) dirEl.textContent = dir ? ('Zielordner: ' + dir) : '';
      const input = document.getElementById('debugTfId');
      if (input && box.hidden === false && !String(input.value || '').trim()) input.value = nextFreeTfId();
    }

    function jsonFlag(value) {
      if (window.RkrkField) return RkrkField.jsonFlag(value);
      if (value === true || value === 1 || value === '1' || value === 'true') return true;
      if (value === false || value === 0 || value === '0' || value === 'false') return false;
      return null;
    }

    function missionFlagOn(value) {
      return !(value === false || value === 0 || value === '0' || value === 'false');
    }

    function uavWaypointRowsFromDocument(doc) {
      const features = doc && doc.geojson && Array.isArray(doc.geojson.features) ? doc.geojson.features : [];
      const rows = [];
      features.forEach((feature) => {
        const geom = feature && feature.geometry;
        const props = feature && feature.properties;
        if (!geom || geom.type !== 'Point' || !props || typeof props !== 'object') return;
        if (Number(props.type) === 6) return;
        const flags = {
          use_mission_height: jsonFlag(props.useMissionHeight),
          use_mission_speed: jsonFlag(props.useMissionSpeed),
          use_mission_waypoint_type: jsonFlag(props.useMissionWaypointType),
          use_mission_yaw: jsonFlag(props.useMissionYawDirection),
          use_mission_path_mode: jsonFlag(props.useMissionPathMode),
          use_mission_direction_mode: jsonFlag(props.useMissionDirectionMode),
        };
        if (!Object.values(flags).some((flag) => flag === true || flag === false)) return;
        rows.push({ flags: flags, props: props });
      });
      return rows;
    }

    function uavEasyModeForm(doc) {
      const pilot = doc && doc.missionSettings && doc.missionSettings.djiPilot2;
      if (pilot && (pilot.easyModeForm === true || pilot.easyModeForm === false)) return pilot.easyModeForm;
      const last = doc && doc.lastAutoGenerateSettings && doc.lastAutoGenerateSettings.settings;
      if (last && (last.easyModeForm === true || last.easyModeForm === false)) return last.easyModeForm;
      return null;
    }

    function headingModeFromYawDirection(value) {
      if (window.RkrkField && typeof RkrkField.headingModeFromUav === 'function') {
        return RkrkField.headingModeFromUav(value);
      }
      return '';
    }

    function waypointTypeFromUav(value) {
      const mode = String(value || '');
      if (mode === 'coordinateTurn') return 'Koordinierte Drehung, Ueberspringt den Wegpunkt';
      if (mode === 'toPointAndStopWithDiscontinuityCurvature') return 'Gerade Flugroute. Fluggeraet stoppt';
      if (mode === 'toPointAndPassWithContinuityCurvature') return 'Gekruemmte Route. Fluggeraet setzt fort';
      if (mode === 'toPointAndStopWithContinuityCurvature') return 'Gekruemmte Route. Fluggeraet stoppt';
      return '';
    }

    function applyUavJsonDocument(doc) {
      if (!window.RkrkField) return false;
      const parsed = typeof RkrkField.readMissionDeviations === 'function'
        ? RkrkField.readMissionDeviations(doc)
        : (typeof RkrkField.readUavWaypointOverrides === 'function' ? RkrkField.readUavWaypointOverrides(doc) : null);
      if (!parsed || !parsed.count) return false;
      waypointOverrides = parsed.overrides;
      if (typeof RkrkField.alignOverridesToPath === 'function') {
        waypointOverrides = RkrkField.alignOverridesToPath(waypointOverrides, currentWaypointPath());
      }
      if (Array.isArray(parsed.pois)) setMissionPois(parsed.pois);
      const anyFalse = parsed.anyFalse === true;
      const easy = parsed.easyModeForm;
      if (easy === false) setPlanExpertMode(true);
      else if (easy === true) setPlanExpertMode(false);
      const pilot = doc && doc.missionSettings && doc.missionSettings.djiPilot2;
      if (pilot && els.msSpeed && Number(pilot.speed) > 0 && !anyFalse) {
        els.msSpeed.value = Number(pilot.speed).toFixed(1);
      }
      if (pilot && els.msAltitude && Number(pilot.height) > 1) {
        els.msAltitude.value = String(Math.round(Number(pilot.height)));
        missionHeightFromField = true;
        if (typeof syncPlanAltitudeLabel === 'function') syncPlanAltitudeLabel();
      }
      if (pilot && els.msHeadingMode && window.RkrkField && typeof RkrkField.headingModeFromUav === 'function') {
        const missionYaw = RkrkField.headingModeFromUav(pilot.directionMode || pilot.yawDirection || pilot.headingMode);
        if (missionYaw) els.msHeadingMode.value = missionYaw;
      }
      if (parsed.gimbalMode && els.msGimbalMode) {
        els.msGimbalMode.value = parsed.gimbalMode;
        syncGimbalAngleFlag();
      }
      syncPlanSpeedField();
      syncPlanStartSpeed();
      return true;
    }

    function mergeWpmlUseFlagsIntoOverrides(xmlText) {
      const xml = String(xmlText || '');
      if (!xml || xml.indexOf('Placemark') < 0) return false;
      const blocks = xml.match(/<Placemark\b[^>]*>[\s\S]*?<\/Placemark>/gi) || [];
      const tagMap = {
        useGlobalHeight: 'use_mission_height',
        useGlobalSpeed: 'use_mission_speed',
        useGlobalTurnParam: 'use_mission_waypoint_type',
        useGlobalHeadingParam: 'use_mission_yaw',
      };
      let n = 0;
      let changed = false;
      blocks.forEach((block) => {
        if (!/<coordinates>\s*[-0-9.]+\s*,\s*[-0-9.]+/i.test(block)) return;
        const prevIndex = n;
        const rowDraft = {};
        let saw = false;
        Object.keys(tagMap).forEach((tag) => {
          const m = block.match(new RegExp('<wpml:' + tag + '>\\s*([^<]*)<\\/wpml:' + tag + '>', 'i'));
          if (!m) return;
          const raw = String(m[1] || '').trim().toLowerCase();
          if (raw === '1' || raw === 'true') { rowDraft[tagMap[tag]] = true; saw = true; }
          else if (raw === '0' || raw === 'false') { rowDraft[tagMap[tag]] = false; saw = true; }
        });
        if (!saw) return;
        n += 1;
        const indexMatch = block.match(/<wpml:index>\s*(\d+)\s*<\/wpml:index>/i);
        const idx = indexMatch ? (Number(indexMatch[1]) + 1) : (prevIndex + 1);
        const prev = (waypointOverrides && waypointOverrides[String(idx)] && typeof waypointOverrides[String(idx)] === 'object')
          ? waypointOverrides[String(idx)]
          : {};
        waypointOverrides[String(idx)] = { ...prev, ...rowDraft };
        changed = true;
      });
      if (!changed) return false;
      holdStoredRoute();
      refreshFlightPathLayers();
      if (rightDrawerMode === 'waypoint') fillWaypointForm(selectedWaypointIndex1, true);
      const anyFalse = Object.values(waypointOverrides).some((row) => row && (
        row.use_mission_height === false
        || row.use_mission_speed === false
        || row.use_mission_waypoint_type === false
        || row.use_mission_yaw === false
        || row.use_mission_path_mode === false
        || row.use_mission_direction_mode === false
      ));
      const easy = uavEasyModeForm({ missionSettings: { djiPilot2: { easyModeForm: editorEasyModeForm } } });
      if (easy === true && anyFalse) setPlanExpertMode(true);
      else if (anyFalse) setPlanExpertMode(true);
      return true;
    }

    async function mergeFieldKmzTemplate(field) {
      const rel = String(field && field.kmz_file || readControl('kmz_file') || '').trim();
      if (!rel || !window.RkrkField || typeof RkrkField.templateKmlFromKmz !== 'function' || typeof RkrkField.applyKmzDeviations !== 'function') return false;
      const url = missionPictureUrl(rel);
      if (!url) return false;
      try {
        const response = await fetch(url, { cache: 'no-store' });
        if (!response.ok) return false;
        const kml = await RkrkField.templateKmlFromKmz(new Uint8Array(await response.arrayBuffer()));
        if (!kml) return false;
        const parsed = RkrkField.applyKmzDeviations({ overrides: waypointOverrides || {}, count: 0, anyFalse: false }, kml);
        if (parsed && parsed.overrides) waypointOverrides = parsed.overrides;
        if (typeof RkrkField.alignOverridesToPath === 'function') {
          waypointOverrides = RkrkField.alignOverridesToPath(waypointOverrides, currentWaypointPath());
        }
        if (parsed && parsed.gimbalMode && els.msGimbalMode) {
          els.msGimbalMode.value = parsed.gimbalMode;
          syncGimbalAngleFlag();
        }
        return true;
      } catch (_) {
        return false;
      }
    }

    async function applyJasonFileMissionFlags(field) {
      const rel = String(field && field.jason_file || readControl('jason_file') || '').trim();
      if (!rel || typeof missionPictureUrl !== 'function') return false;
      const url = missionPictureUrl(rel);
      if (!url) return false;
      holdStoredRoute();
      try {
        const response = await fetch(url, { cache: 'no-store' });
        if (!response.ok) return false;
        const doc = await response.json();
        const applied = applyUavJsonDocument(doc);
        if (applied) {
          await mergeFieldKmzTemplate(field);
          holdStoredRoute();
          refreshFlightPathLayers();
          if (rightDrawerMode === 'waypoint') fillWaypointForm(selectedWaypointIndex1, true);
        }
        return applied;
      } catch (_) {
        return false;
      }
    }

    async function loadField(id) {
      if (!window.RKRK_KAT) {
        showStatus('Katalogfelder sind nur mit ?KAT auswählbar.', false);
        return;
      }
      const r = await fetch(editorApiUrl({ action: 'get', id: String(id) }), { cache: 'no-store' });
      const data = await r.json();
      if (!data.ok) {
        throw new Error(data.error || 'get_failed');
      }
      creatingNewField = false;
      clearNewFieldDraft();
      selectedId = String(id);
      saveFloatingUiState({ lastFieldId: String(id) });
      holdStoredRoute();
      setForm(data.field || {});
      syncMapFromForm(false);
      noteLoadedMissionAltitude(data.field || {});
      await applyJasonFileMissionFlags(data.field || {});
      settleEasyMissionDefaults();
      setPlanOrigin('existing');
      reapplyPersistentUiSettingsAfterFieldChange();
      markFieldAsSaved();
      renderList();
      showStatus(`Feld geladen: ${data.field.feld_id}`);
    }

    function ensurePictureMap() {
      if (pictureMap) return pictureMap;
      const host = document.createElement('div');
      host.id = 'planPictureHost';
      host.setAttribute('aria-hidden', 'true');
      host.style.cssText = 'position:fixed;left:0;top:0;width:600px;height:600px;opacity:0;pointer-events:none;z-index:-1;';
      document.body.appendChild(host);
      picturePolySource = new ol.source.Vector();
      picturePathSource = new ol.source.Vector();
      pictureStartSource = new ol.source.Vector();
      pictureTileLayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
          url: 'map_tile.php?layer=swissimage&z={z}&x={x}&y={y}',
          maxZoom: 19,
          attributions: 'swisstopo',
        }),
      });
      picturePathLayer = new ol.layer.Vector({
        source: picturePathSource,
        style: new ol.style.Style({
          stroke: new ol.style.Stroke({ color: '#1565c0', width: 3 }),
        }),
      });
      pictureMap = new ol.Map({
        target: host,
        controls: [],
        interactions: [],
        layers: [
          pictureTileLayer,
          new ol.layer.Vector({
            source: picturePolySource,
            style: new ol.style.Style({
              fill: new ol.style.Fill({ color: 'rgba(255, 214, 64, 0.35)' }),
              stroke: new ol.style.Stroke({ color: '#c45c00', width: 3 }),
            }),
          }),
          picturePathLayer,
          new ol.layer.Vector({
            source: pictureStartSource,
            style: new ol.style.Style({
              image: new ol.style.Circle({
                radius: 7,
                fill: new ol.style.Fill({ color: '#c62828' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 }),
              }),
            }),
          }),
        ],
        view: new ol.View({ center: ol.proj.fromLonLat([8.5, 47.2]), zoom: 14 }),
      });
      pictureMap.updateSize();
      return pictureMap;
    }

    function capturePictureJpeg(map) {
      return new Promise((resolve) => {
        let finished = false;
        const finish = () => {
          if (finished) return;
          finished = true;
          const canvas = map.getViewport().querySelector('canvas');
          if (!canvas || typeof canvas.toBlob !== 'function') {
            resolve(null);
            return;
          }
          try {
            canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.86);
          } catch (_) {
            resolve(null);
          }
        };
        map.once('rendercomplete', () => setTimeout(finish, 250));
        map.renderSync();
        setTimeout(finish, 6000);
      });
    }

    function catalogPictureQuery(kind) {
      if (kind === 'such' || kind === 'suchfeld') {
        return 'Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=off&GDE=off&JGG=off&Poi=off&HSP=on&OVR=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.6';
      }
      return 'Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&HSP=on&FLP=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.8';
    }

    function pictureGuestSuffix() {
      try {
        const ga = new URLSearchParams(window.location.search).get('GA');
        if (ga) return '&GA=' + encodeURIComponent(ga);
      } catch (_) {}
      return '';
    }

    function catalogPictureTarget() {
      if (!window.RKRK_KAT || creatingNewField) return null;
      const id = String(els.id && els.id.value || '').trim();
      const feldId = String(els.feld_id && els.feld_id.value || '').trim();
      if (!id || !/^[A-Za-z0-9]+-\d+$/.test(feldId)) return null;
      if (Array.isArray(allFields) && allFields.length && !fieldInCatalog()) return null;
      return { id: id, feldId: feldId };
    }

    function catalogMapPictureUrl(feldId, kind) {
      return '../rkrk_map.php?FFilter=' + encodeURIComponent(feldId) + '&MDB=' + encodeURIComponent(editorMissionKey()) + '&' + catalogPictureQuery(kind) + pictureGuestSuffix();
    }

    function catalogFieldHit(win, feldId) {
      const geoMap = win && win.__rkrkMap27;
      if (!geoMap || typeof geoMap.getSize !== 'function' || typeof geoMap.getLayers !== 'function') return null;
      const size = geoMap.getSize();
      if (!size || size[0] < 480 || size[1] < 360) return null;
      let feature = null;
      geoMap.getLayers().forEach((layer) => {
        const src = layer.getSource && layer.getSource();
        if (!src || typeof src.getFeatures !== 'function' || feature) return;
        src.getFeatures().some((f) => {
          const g = f.getGeometry && f.getGeometry();
          if (!g || String(g.getType()).indexOf('Polygon') < 0) return false;
          const fid = String((typeof f.getId === 'function' && f.getId()) || '');
          const name = String((f.get && (f.get('name') || f.get('Name'))) || '');
          if ((fid + ' ' + name).indexOf(feldId) === -1) return false;
          feature = f;
          return true;
        });
      });
      if (!feature || typeof geoMap.getPixelFromCoordinate !== 'function') return null;
      const ext = feature.getGeometry().getExtent();
      if (!ext || ext.length < 4) return null;
      const a = geoMap.getPixelFromCoordinate([ext[0], ext[1]]);
      const b = geoMap.getPixelFromCoordinate([ext[2], ext[3]]);
      if (!a || !b) return null;
      const left = Math.min(a[0], b[0]);
      const right = Math.max(a[0], b[0]);
      const top = Math.min(a[1], b[1]);
      const bottom = Math.max(a[1], b[1]);
      if (right - left < 12 || bottom - top < 12) return null;
      const cx = (left + right) / 2;
      const cy = (top + bottom) / 2;
      if (cx < size[0] * 0.2 || cx > size[0] * 0.8 || cy < size[1] * 0.2 || cy > size[1] * 0.8) return null;
      return { cx: cx, cy: cy, size: size };
    }

    function catalogFieldPainted(win, hit) {
      const nodes = win.document.querySelectorAll('#map .ol-layer canvas');
      if (!nodes.length) return false;
      for (let i = 0; i < nodes.length; i++) {
        const canvas = nodes[i];
        if (!(canvas.width > 0) || !(canvas.height > 0)) continue;
        const prX = canvas.width / hit.size[0];
        const prY = canvas.height / hit.size[1];
        let ctx = null;
        try { ctx = canvas.getContext('2d', { willReadFrequently: true }); } catch (_) { ctx = null; }
        if (!ctx) continue;
        const x = Math.max(0, Math.min(canvas.width - 1, Math.round(hit.cx * prX)));
        const y = Math.max(0, Math.min(canvas.height - 1, Math.round(hit.cy * prY)));
        try {
          const d = ctx.getImageData(x, y, 1, 1).data;
          if (d[3] > 40 && d[0] > d[1] + 28 && d[0] > d[2] + 28) return true;
        } catch (_) {}
      }
      return false;
    }

    function compositeCatalogBitmap(win) {
      const geoMap = win && win.__rkrkMap27;
      const size = geoMap && geoMap.getSize ? geoMap.getSize() : null;
      if (!size || !(size[0] > 0) || !(size[1] > 0)) return null;
      const nodes = win.document.querySelectorAll('#map .ol-layer canvas');
      if (!nodes.length) return null;
      const base = nodes[nodes.length - 1];
      const prX = base.width / size[0];
      const prY = base.height / size[1];
      if (!(prX > 0) || !(prY > 0)) return null;
      const out = document.createElement('canvas');
      out.width = Math.max(1, Math.round(size[0] * prX));
      out.height = Math.max(1, Math.round(size[1] * prY));
      const ctx = out.getContext('2d');
      if (!ctx) return null;
      Array.prototype.forEach.call(nodes, (canvas) => {
        if (!(canvas.width > 0) || !(canvas.height > 0)) return;
        const parent = canvas.parentNode;
        const opacity = parent && parent.style && parent.style.opacity !== '' ? Number(parent.style.opacity) : 1;
        ctx.globalAlpha = Number.isFinite(opacity) ? opacity : 1;
        const transform = canvas.style && canvas.style.transform ? canvas.style.transform : '';
        const matched = /^matrix\(([^)]+)\)$/.exec(transform);
        if (matched) {
          const matrix = matched[1].split(',').map((v) => Number(String(v).trim()));
          if (matrix.length === 6 && matrix.every((n) => Number.isFinite(n))) {
            ctx.setTransform(matrix[0] * prX, matrix[1] * prX, matrix[2] * prX, matrix[3] * prY, matrix[4] * prX, matrix[5] * prY);
            ctx.drawImage(canvas, 0, 0);
            return;
          }
        }
        ctx.setTransform(prX, 0, 0, prY, 0, 0);
        ctx.drawImage(canvas, 0, 0, size[0], size[1]);
      });
      ctx.globalAlpha = 1;
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      return out;
    }

    function prepareCatalogFrame(win) {
      try {
        win.document.documentElement.classList.add('rkrk-map27-embed');
        win.document.body.classList.add('rkrk-map27-page');
      } catch (_) {}
      try {
        if (typeof win.rkrkUpdateMapSize === 'function') win.rkrkUpdateMapSize(false);
        else if (win.__rkrkMap27) win.__rkrkMap27.updateSize();
      } catch (_) {}
    }

    function catalogCropRect(canvasWidth, canvasHeight, kind) {
      const zoom = ((kind === 'such' || kind === 'suchfeld') ? 70 : 90) / 100;
      const offsetY = 0;
      const out = 600;
      const visible = out / zoom;
      const scaleX = canvasWidth / 1200;
      const scaleY = canvasHeight / 900;
      const cropW = visible * scaleX;
      const cropH = visible * scaleY;
      let x = canvasWidth / 2 - cropW / 2;
      let y = canvasHeight / 2 + offsetY * scaleY - cropH / 2;
      x = Math.max(0, Math.min(x, Math.max(0, canvasWidth - cropW)));
      y = Math.max(0, Math.min(y, Math.max(0, canvasHeight - cropH)));
      return { x: x, y: y, w: cropW, h: cropH, out: out, zoom: zoom, visible: visible, scaleX: scaleX, scaleY: scaleY };
    }
    if (typeof window.rkrkCatalogCropRect !== 'function') window.rkrkCatalogCropRect = catalogCropRect;

    function cropCatalogCanvas(source, kind) {
      const rect = catalogCropRect(source.width, source.height, kind);
      const canvas = document.createElement('canvas');
      canvas.width = rect.out;
      canvas.height = rect.out;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(source, rect.x, rect.y, rect.w, rect.h, 0, 0, rect.out, rect.out);
      return new Promise((resolve) => {
        try {
          canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.92);
        } catch (_) {
          resolve(null);
        }
      });
    }

    function captureCatalogMap(feldId, kind) {
      if (typeof window.rkrkCaptureCatalogShot === 'function') {
        return window.rkrkCaptureCatalogShot(kind, feldId, catalogMapPictureUrl(feldId, kind));
      }
      return new Promise((resolve) => {
        const frame = document.createElement('iframe');
        frame.setAttribute('aria-hidden', 'true');
        frame.title = kind === 'such' ? 'Suchfeldbild' : 'Flugplanbild';
        frame.width = '1200';
        frame.height = '900';
        frame.style.cssText = 'position:fixed;left:0;top:0;width:1200px;height:900px;border:0;opacity:0.02;pointer-events:none;z-index:-1;';
        let settled = false;
        const finish = (blob) => {
          if (settled) return;
          settled = true;
          frame.remove();
          resolve(blob && blob.size > 4000 ? blob : null);
        };
        const timer = setTimeout(() => finish(null), 20000);
        const grabWhenDrawn = () => {
          if (settled) return;
          const win = frame.contentWindow;
          if (!win) {
            setTimeout(grabWhenDrawn, 200);
            return;
          }
          prepareCatalogFrame(win);
          const geoMap = win.__rkrkMap27;
          if (geoMap && typeof geoMap.updateSize === 'function') {
            try { geoMap.updateSize(); } catch (_) {}
          }
          const hit = catalogFieldHit(win, feldId);
          if (!hit || !catalogFieldPainted(win, hit)) {
            try {
              if (typeof win.rkrkUpdateMapSize === 'function') win.rkrkUpdateMapSize(true);
            } catch (_) {}
            setTimeout(grabWhenDrawn, 300);
            return;
          }
          const shoot = () => {
            setTimeout(() => {
              if (settled) return;
              const again = catalogFieldHit(win, feldId);
              if (!again || !catalogFieldPainted(win, again)) {
                setTimeout(grabWhenDrawn, 300);
                return;
              }
              try {
                const canvas = compositeCatalogBitmap(win);
                if (!canvas) {
                  clearTimeout(timer);
                  finish(null);
                  return;
                }
                cropCatalogCanvas(canvas, kind).then((blob) => {
                  clearTimeout(timer);
                  finish(blob);
                }).catch(() => {
                  clearTimeout(timer);
                  finish(null);
                });
              } catch (_) {
                clearTimeout(timer);
                finish(null);
              }
            }, 600);
          };
          if (geoMap && typeof geoMap.once === 'function') {
            geoMap.once('rendercomplete', shoot);
            try { geoMap.renderSync(); } catch (_) { shoot(); }
          } else {
            shoot();
          }
        };
        frame.addEventListener('load', () => setTimeout(grabWhenDrawn, 200));
        document.body.appendChild(frame);
        frame.src = catalogMapPictureUrl(feldId, kind);
      });
    }

    async function renderPlanPicture(kind) {
      const feldId = String(els.feld_id && els.feld_id.value || '').trim();
      if (!/^[A-Za-z0-9]+-\d+$/.test(feldId)) return null;
      return captureCatalogMap(feldId, kind === 'flug' ? 'flug' : 'such');
    }

    async function captureDeskPicture(kind, opts) {
      if (!window.RKRK_KAT) {
        showStatus('Planbilder nur mit ?KAT.', false);
        return null;
      }
      const target = catalogPictureTarget();
      if (!target) {
        showStatus('Zuerst das Feld im Katalog speichern. Danach können Suchfeld- und Flugplanbild erzeugt werden.', false);
        return null;
      }
      if (parsePairs(els.flaechendaten && els.flaechendaten.value).length < 3) {
        showStatus('Für das Bild braucht es eine Feldumrandung.', false);
        return null;
      }
      showStatus(kind === 'such'
        ? 'Suchfeldbild wird von der Katalogkarte gelesen …'
        : 'Flugplanbild wird von der Katalogkarte gelesen …');
      const blob = await renderPlanPicture(kind);
      if (!blob || blob.size < 2500) {
        showStatus('Das Feld war auf der Katalogkarte noch nicht sichtbar. Einen Moment warten und den Knopf noch einmal drücken.', false);
        return null;
      }
      if (opts && opts.downloadName) {
        const link = document.createElement('a');
        link.download = opts.downloadName;
        link.href = URL.createObjectURL(blob);
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(link.href), 1500);
      }
      if (opts && opts.post === false) {
        showStatus((kind === 'such' ? 'Suchfeldbild' : 'Flugplanbild') + ' wurde erzeugt.');
        return blob;
      }
      const body = new FormData();
      body.append('id', target.id);
      body.append('feld_id', target.feldId);
      body.append(kind === 'such' ? 'suchfeld' : 'flugplan', blob, (kind === 'such' ? 'suchfeld' : 'flugplan') + '.jpg');
      body.append('mdb', editorMissionKey());
      const r = await fetch(editorApiUrl(), { method: 'POST', body });
      const data = await r.json();
      if (!data.ok) {
        showStatus(data.detail || data.error || 'Planbild nicht geschrieben', false);
        return;
      }
      if (data.suchfeld_bild) {
        writeControl('suchfeld_bild', data.suchfeld_bild);
        writeControl('create_suchfeld_bild', data.suchfeld_bild);
      }
      if (data.flugplan_bild) {
        writeControl('flugplan_bild', data.flugplan_bild);
        writeControl('create_flugplan_bild', data.flugplan_bild);
      }
      showStatus((kind === 'such' ? 'Suchfeldbild' : 'Flugplanbild') + ' liegt im Missionsordner und im Feldkatalog.');
      return blob;
    }

    async function renewDeskPictures() {
      if (!window.RKRK_KAT) {
        showStatus('Bilder erneuern nur mit ?KAT.', false);
        return;
      }
      const target = catalogPictureTarget();
      if (!target) {
        showStatus('Zuerst das Feld im Katalog speichern. Danach können Suchfeld- und Flugplanbild erzeugt werden.', false);
        return;
      }
      if (parsePairs(els.flaechendaten && els.flaechendaten.value).length < 3) {
        showStatus('Für die Bilder braucht es eine Feldumrandung.', false);
        return;
      }
      const buttons = ['planPicRenew', 'createPicRenew'].map((id) => document.getElementById(id)).filter(Boolean);
      buttons.forEach((btn) => { btn.disabled = true; });
      showStatus('Suchfeld- und Flugplanbild werden erneuert …');
      try {
        if (typeof window.rkrkStoreCatalogPictures !== 'function') {
          showStatus('Suchfeld- und Flugplanbild konnten nicht erzeugt werden.', false);
          return;
        }
        const wasClean = currentFieldSnapshot() === savedFieldSnapshot;
        const data = await window.rkrkStoreCatalogPictures({
          id: target.id,
          feld_id: target.feldId,
          mdb: editorMissionKey(),
          mapBase: '../rkrk_map.php',
          apiUrl: editorApiUrl()
        });
        if (!data || !data.ok) {
          showStatus((data && data.error) || 'Die Bilder waren leer. Einen Moment warten und noch einmal erneuern.', false);
          return;
        }
        if (data.suchfeld_bild) {
          writeControl('suchfeld_bild', data.suchfeld_bild);
          writeControl('create_suchfeld_bild', data.suchfeld_bild);
        }
        if (data.flugplan_bild) {
          writeControl('flugplan_bild', data.flugplan_bild);
          writeControl('create_flugplan_bild', data.flugplan_bild);
        }
        renderCreatePictureRows();
        if (wasClean && typeof markFieldAsSaved === 'function') markFieldAsSaved();
        showStatus('Suchfeld- und Flugplanbild wurden erneuert.');
      } finally {
        buttons.forEach((btn) => { btn.disabled = false; });
      }
    }

    async function generateCatalogPictures() {
      const target = catalogPictureTarget();
      if (!target) return;
      const wasClean = currentFieldSnapshot() === savedFieldSnapshot;
      try {
        if (parsePairs(els.flaechendaten && els.flaechendaten.value).length < 3) return;
        const feldId = target.feldId;
        let such = await renderPlanPicture('such');
        let flug = await renderPlanPicture('flug');
        if (such && such.size < 2500) such = null;
        if (flug && flug.size < 2500) flug = null;
        if (!such && !flug) {
          showStatus('Feld ist gespeichert. Such- und Flugplanbild konnten nicht erzeugt werden.', false);
          return;
        }
        const body = new FormData();
        body.append('id', target.id);
        body.append('feld_id', feldId);
        if (such) body.append('suchfeld', such, 'suchfeld.jpg');
        if (flug) body.append('flugplan', flug, 'flugplan.jpg');
        body.append('mdb', editorMissionKey());
        const r = await fetch(editorApiUrl(), { method: 'POST', body });
        const data = await r.json();
        if (!data.ok) {
          showStatus('Feld ist gespeichert. Such- und Flugplanbild konnten nicht geschrieben werden.', false);
          return;
        }
        if (data.suchfeld_bild) {
          writeControl('suchfeld_bild', data.suchfeld_bild);
          writeControl('create_suchfeld_bild', data.suchfeld_bild);
        }
        if (data.flugplan_bild) {
          writeControl('flugplan_bild', data.flugplan_bild);
          writeControl('create_flugplan_bild', data.flugplan_bild);
        }
        if (wasClean) markFieldAsSaved();
        showStatus('Suchfeld- und Flugplanbild wurden erzeugt.');
      } catch (_) {
        showStatus('Feld ist gespeichert. Such- und Flugplanbild konnten nicht erzeugt werden.', false);
      }
    }

    async function save(mode) {
      if (!window.RKRK_KAT) {
        showStatus('Speichern in den Feldkatalog braucht ?KAT. Ohne KAT das Feld als KML oder JSON herunterladen.', false);
        return false;
      }
      if (creatingNewField && mode === 'update') {
        showStatus('Neues Feld überschreibt kein Katalogfeld.', false);
        return false;
      }
      const field = formData();
      if (mode === 'update' && (parsePairs(field.flaechendaten).length < 3 || parseTriplets(field.flugweg).length < 2)) {
        showStatus('Speichern braucht Umrandung und Flugweg. Der Feldkatalog bleibt unverändert.', false);
        return false;
      }
      try { field.terrain_delta_by_lonlat = await terrainDeltaMapForExport(); } catch (_) { field.terrain_delta_by_lonlat = {}; }
      let data = null;
      for (let attempt = 0; attempt < 2; attempt++) {
        const r = await fetch(editorApiUrl(), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mode, field, kat: !!window.RKRK_KAT, token: reserveToken(), mdb: editorMissionKey() }),
        });
        data = await r.json();
        if (data.ok) break;
        if (data.error === 'feld_id_exists' && attempt === 0) {
          const useIt = window.confirm((data.detail || 'Diese Feldkatalognummer ist schon vergeben.') + '\n\nDiese Nummer trotzdem verwenden? Dateien, die es schon unter der bisherigen Nummer gibt, werden umbenannt.');
          if (!useIt) {
            throw new Error(data.detail || 'Feldkatalognummer ist schon vergeben.');
          }
          field.confirm_existing_feld_id = true;
          continue;
        }
        throw new Error(`${data.error || 'save_failed'}${data.detail ? ` - ${data.detail}` : ''}`);
      }
      await loadList();
      if (mode === 'update') {
        markFieldAsSaved();
      }
      if (mode === 'create') {
        const created = allFields.find((f) => f.feld_id === data.saved_field_id);
        if (created) {
          await loadField(created.id);
        }
      }
      if (data.aenderung) {
        writeControl('aenderung', data.aenderung);
        writeControl('create_aenderung', data.aenderung);
      }
      const confirm = window.RkrkField && typeof RkrkField.saveConfirmText === 'function'
        ? RkrkField.saveConfirmText(data)
        : { ok: !!(data.jason_file && data.kmz_file), text: 'JSON: ' + (data.jason_file || 'fehlt') + '. KMZ: ' + (data.kmz_file || 'fehlt') + '.' };
      const place = [data.jg_name || data.jg, data.gemeinde_name || data.gemeinde].filter(Boolean).join(', ');
      const head = (mode === 'create'
        ? `Neues Feld ${data.saved_field_id || ''} angelegt${place ? ` (${place})` : ''}. `
        : 'Feld wurde gespeichert. ') + confirm.text;
      if (catalogPictureTarget()) {
        showStatus(head + ' Suchfeld- und Flugplanbild werden erzeugt …', !confirm.ok);
        await generateCatalogPictures();
      }
      showStatus(head, !confirm.ok);
      setPlanPhase('finish');
      return true;
    }

    function updatePlanAssign(text) {
      const el = document.getElementById('planAssign');
      if (el) el.textContent = text;
    }

    function applyNewPlanFlightPathDefaults() {
      const cov = document.getElementById('planShowCoverage');
      const un = document.getElementById('planShowUncovered');
      const field = document.getElementById('planShowFieldHeights');
      if (cov) cov.checked = true;
      if (un) un.checked = true;
      if (field) field.checked = false;
      if (els.showOverlapAreas) els.showOverlapAreas.checked = false;
      pushPlanCoverageToMap();
      pushPlanHeightsToMap();
      document.querySelectorAll('.plan-choice button').forEach((btn) => btn.classList.remove('is-on'));
      const second = document.getElementById('planEntrySecond');
      if (second) second.classList.add('is-on');
      if (els.msFlightDirection) els.msFlightDirection.value = '-1';
      syncFlightDirectionControls('number');
      syncPlanDirSlider();
      if (els.msSpeed) els.msSpeed.value = '5.0';
      syncPlanSpeedField();
    }

    async function startNewFieldFromScratch() {
      setPlanOrigin('new', { openList: false });
      creatingNewField = true;
      planWaypointsDone = false;
      planHold = false;
      closeCreateFieldModal();
      missionHeightUserSet = false;
      missionHeightFromField = false;
      setPlanPhase('outline');
      const altitude = String(missionAltitudeDefaultM(els.msDroneType && els.msDroneType.value));
      clearForm(true, true);
      centerOnDeviceOrKnonau();
      if (els.std_flughoehe) els.std_flughoehe.value = altitude;
      if (els.msAltitude) els.msAltitude.value = altitude;
      syncPlanAltitudeLabel();
      applyNewPlanFlightPathDefaults();
      updatePlanAssign('Neues Feld ohne Umrandung. Zuerst den Ort suchen, dann die Umrandung zeichnen.');
      showStatus('Neues Feld gestartet. Zuerst den Ort festlegen.');
      const found = await findPlaceOnMap();
      if (!creatingNewField) return;
      if (found) {
        updatePlanAssign(window.RKRK_KAT
          ? 'Ort gesetzt. Jetzt die Umrandung zeichnen. Daraus folgen Jagdgebiet, Gemeinde und Katalognummer.'
          : 'Ort gesetzt. Jetzt die Umrandung zeichnen. Daraus folgen Jagdgebiet und Gemeinde.');
        showStatus('Ort gesetzt. Jetzt die Feldumrandung zeichnen.');
      } else {
        showStatus('Ort noch offen. Suchen oder die Karte verschieben, dann die Umrandung zeichnen.');
      }
      startPolygonDraw();
    }

    async function assignCatalogFromOutline() {
      const f = formData();
      if (parsePairs(f.flaechendaten).length < 3) return;
      updatePlanAssign(window.RKRK_KAT
        ? 'Jagdgebiet, Gemeinde und Katalognummer werden ermittelt …'
        : 'Jagdgebiet und Gemeinde werden ermittelt …');
      const r = await fetch(editorApiUrl(), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mode: 'preview', token: reserveToken(), mdb: editorMissionKey(), field: { ...f, name: f.name || 'Vorschau', existing: fieldInCatalog() } }),
      });
      const data = await r.json();
      if (!data.ok) {
        const msg = `${data.error || 'preview_failed'}${data.detail ? ` – ${data.detail}` : ''}`;
        updatePlanAssign(msg);
        showStatus(msg, false);
        return;
      }
      showPlace(els.jg, data.jg || '', data.jg_name || data.jg || '');
      showPlace(els.gemeinde, data.gemeinde || '', data.gemeinde_name || data.gemeinde || '');
      if (data.picture_dir) catalogPictureDir = String(data.picture_dir);
      if (data.reserve_note) showStatus(String(data.reserve_note), false);
      if (els.number && window.RKRK_KAT) els.number.value = String(data.number || '');
      if (els.feld_id && window.RKRK_KAT) els.feld_id.value = String(data.feld_id || '');
      syncPlanFieldNameFromForm();
      if (!window.RKRK_KAT) {
        if (els.feld_id) els.feld_id.value = '';
        if (els.number) els.number.value = '';
      }
      const label = (window.RKRK_KAT ? [data.feld_id, data.jg_name || data.jg, data.gemeinde_name || data.gemeinde] : [data.jg_name || data.jg, data.gemeinde_name || data.gemeinde]).filter(Boolean).join(' · ');
      updatePlanAssign(window.RKRK_KAT
        ? `Zugeordnet: ${label}. Weiter mit Start. Die GIS-Nummer folgt aus dieser Fläche. Speichern kommt vor den Daten.`
        : `Zugeordnet: ${label}. Weiter mit Startpunkt und Route. Ohne ?KAT gibt es keine Feldkatalognummer.`);
      showStatus(`Umrandung gesetzt. ${label}`);
      scheduleNewFieldDraftSave();
    }

    function renderCreatePictureRows() {
      const host = document.getElementById('createPictureRows');
      if (!host) return;
      const icon = '../images/23color';
      const feldId = String((els.create_feld_id && els.create_feld_id.value) || (els.feld_id && els.feld_id.value) || '').trim();
      const jg = placeCode(els.create_jg) || placeCode(els.jg);
      const name = String((els.create_name && els.create_name.value) || '').trim();
      const gis = String((document.getElementById('create_grundstueck_id') && document.getElementById('create_grundstueck_id').value) || '').trim();
      const yy = String(new Date().getFullYear()).slice(-2);
      const height = String((els.create_std_flughoehe && els.create_std_flughoehe.value) || '60');
      host.innerHTML = '';
      [
        { kind: 'such', label: 'Suchfeld Bild(er)', xml: 'SuchfeldBild', input: 'create_suchfeld_bild', mode: 'suchfeld', height: '00', viewIcon: 'suchplanbild.png', shot: 'flugplanbild_shot.png', shotServer: 'flugplanbild_shot_server.png' },
        { kind: 'flug', label: 'Flugplan Bild(er)', xml: 'FlugplanBild', input: 'create_flugplan_bild', mode: 'flugplan', height: height, viewIcon: 'flugplanbild.png', shot: 'suchplanbild_shot.png', shotServer: 'suchplanbild_shot_server.png' },
      ].forEach((row) => {
        const proposed = [feldId, name, gis, row.height, 'FE', yy].join('_') + '.jpg';
        const files = String((document.getElementById(row.input) && document.getElementById(row.input).value) || '').split(',').map((part) => part.trim()).filter(Boolean);
        const wrap = document.createElement('div');
        wrap.style.margin = '0 0 14px';
        const title = document.createElement('div');
        title.innerHTML = '<i>' + row.label + '</i>';
        wrap.appendChild(title);
        const tools = document.createElement('div');
        const savedTarget = catalogPictureTarget();
        if (savedTarget) {
          const view = document.createElement('a');
          view.href = catalogMapPictureUrl(savedTarget.feldId, row.kind);
          view.target = '_geomap';
          view.innerHTML = '<img src="' + icon + '/' + row.viewIcon + '" height="28" alt="" title="' + (row.kind === 'such' ? 'Zeige Suchfeldbild' : 'Zeige Flugplanbild') + '">';
          tools.appendChild(view);
          const local = document.createElement('a');
          local.href = '#';
          local.innerHTML = '<img src="' + icon + '/' + row.shot + '" height="28" alt="" title="Snapshot lokal">';
          local.addEventListener('click', (ev) => {
            ev.preventDefault();
            captureDeskPicture(row.kind, { downloadName: proposed, post: false }).catch((e) => showStatus(String(e.message || e), false));
          });
          tools.appendChild(local);
          const server = document.createElement('a');
          server.href = '#';
          server.innerHTML = '<img src="' + icon + '/' + row.shotServer + '" height="28" alt="" title="Snapshot Server">';
          server.addEventListener('click', (ev) => {
            ev.preventDefault();
            captureDeskPicture(row.kind).catch((e) => showStatus(String(e.message || e), false));
          });
          tools.appendChild(server);
        } else {
          const needSave = document.createElement('div');
          needSave.innerHTML = '<i><small>Zuerst das Feld im Katalog speichern.</small></i>';
          tools.appendChild(needSave);
        }
        wrap.appendChild(tools);
        if (!files.length) {
          const hint = document.createElement('div');
          hint.innerHTML = '<i><small>Vorschlag Dateiname: ' + proposed.replace(/\.jpg$/, '') + '</small></i>';
          wrap.appendChild(hint);
        }
        files.forEach((rel) => {
          const base = rel.split('/').pop();
          const line = document.createElement('div');
          const del = document.createElement('button');
          del.type = 'button';
          del.title = row.label + ' löschen (' + base + ')';
          del.style.cssText = 'border:0;background:transparent;padding:0;cursor:pointer;';
          del.innerHTML = '<img src="' + icon + '/delete.png" height="22" alt="Löschen">';
          del.addEventListener('click', () => {
            const url = '../rkrk_edit_felddaten.php?ActPage=1&FeldID=' + encodeURIComponent(feldId) + '&JG=' + encodeURIComponent(jg) + '&File=' + encodeURIComponent(base) + '&deletefile=Y&XMLLabel=' + encodeURIComponent(row.xml);
            window.location.href = url;
          });
          line.appendChild(del);
          line.appendChild(document.createTextNode(' ' + base));
          wrap.appendChild(line);
        });
        const add = document.createElement('div');
        const lab = document.createElement('label');
        lab.textContent = 'Datei hinzufügen';
        const file = document.createElement('input');
        file.type = 'file';
        file.accept = 'image/jpeg,image/jpg';
        add.appendChild(lab);
        add.appendChild(document.createTextNode(' '));
        add.appendChild(file);
        if (row.kind === 'flug') {
          const sel = document.createElement('select');
          sel.id = 'create_flugplan_hoehe';
          const source = els.create_std_flughoehe;
          if (source) {
            Array.from(source.options).forEach((opt) => {
              const copy = document.createElement('option');
              copy.value = opt.value;
              copy.textContent = opt.textContent;
              if (opt.value === height) copy.selected = true;
              sel.appendChild(copy);
            });
          }
          add.appendChild(document.createTextNode(' '));
          add.appendChild(sel);
        }
        wrap.appendChild(add);
        const viewBox = document.createElement('div');
        viewBox.id = row.input + '_view';
        viewBox.className = 'plan-pics';
        wrap.appendChild(viewBox);
        host.appendChild(wrap);
      });
      renderStoredPictures('create_suchfeld_bild');
      renderStoredPictures('create_flugplan_bild');
    }

    function clearForm(clearMap = true, quiet = false) {
      holdStoredRoute();
      if (flightRecomputeTimer) {
        clearTimeout(flightRecomputeTimer);
        flightRecomputeTimer = null;
      }
      flightRecomputeQueued = false;
      releaseReservation();
      selectedId = '';
      currentManualPath = [];
      currentComputedPath = [];
      originalManualPath = [];
      generatedPathSnapshot = [];
      pathDivergedFromGenerated = false;
      currentControlPointsPath = [];
      currentControlPointsField = [];
      waypointOverrides = {};
      const emptyForm = {
        id: '',
        number: '',
        feld_id: '',
        name: '',
        jg: '',
        std_flughoehe: '50',
        flugzeit: '0',
        distanz: '0',
        wegpunkte: '',
        startplatz: '',
        flaechendaten: '',
        flugweg: '',
      };
      setForm(emptyForm);
      if (clearMap) syncMapFromForm(false);
      reapplyPersistentUiSettingsAfterFieldChange();
      renderList();
      if (!quiet) showStatus('Leeres Formular bereit. Neues Feld anlegen startet mit dem Ort.');
    }

    function showCreateTab(name) {
      const key = String(name || 'grund');
      document.querySelectorAll('[data-create-tab]').forEach((btn) => {
        btn.classList.toggle('is-active', btn.getAttribute('data-create-tab') === key);
      });
      document.querySelectorAll('[data-create-panel]').forEach((panel) => {
        panel.hidden = panel.getAttribute('data-create-panel') !== key;
      });
    }

    function setCreatePreviewNote(text) {
      const note = document.getElementById('createPreviewNote');
      if (!note) return;
      const msg = String(text || '').trim();
      note.hidden = msg === '';
      note.textContent = msg;
    }

    async function openCreateFieldModal() {
      if (!els.createFieldModalBackdrop) return;
      const f = formData();
      const hasPolygon = parsePairs(f.flaechendaten).length >= 3;
      const missing = [];
      if (!hasPolygon) missing.push('Umrandung');
      if (parsePairs(f.startplatz).length < 1) missing.push('Startpunkt');
      if (parseTriplets(f.flugweg).length < 2) missing.push('Route');
      els.create_name.value = String((document.getElementById('planFieldName') && document.getElementById('planFieldName').value) || f.name || '').trim();
      els.create_feld_id.value = window.RKRK_KAT ? 'wird ermittelt …' : '';
      showPlace(els.create_jg, '', '');
      showPlace(els.create_gemeinde, '', '');
      els.create_number.value = '';
      writeControl('create_std_flughoehe', f.std_flughoehe || (els.msAltitude && els.msAltitude.value) || '60');
      els.create_startplatz.value = String(f.startplatz || '');
      els.create_flaechendaten.value = String(f.flaechendaten || '');
      els.create_flugweg.value = String(f.flugweg || '');
      copyCatalogFormToModal();
      const release = document.getElementById('create_freigabe');
      if (release) {
        if (!fieldInCatalog()) writeControl('create_freigabe', 'NEU');
        release.disabled = !fieldInCatalog();
      }
      setCreatePreviewNote(missing.length ? `Noch offen: ${missing.join(', ')}.` : '');
      showCreateTab('grund');
      els.createFieldModalBackdrop.classList.add('show');
      if (els.create_name) els.create_name.focus();
      if (fieldInCatalog()) {
        els.create_feld_id.value = String(els.feld_id.value || '');
        els.create_number.value = String(els.number.value || '');
        showPlace(els.create_jg, placeCode(els.jg), els.jg ? els.jg.value : '');
        showPlace(els.create_gemeinde, placeCode(els.gemeinde), els.gemeinde ? els.gemeinde.value : '');
        updateCreateSaveButton();
        renderCreatePictureRows();
        return;
      }
      updateCreateSaveButton();
      renderCreatePictureRows();
      if (!hasPolygon) {
        els.create_feld_id.value = '';
        return;
      }
      try {
        const r = await fetch(editorApiUrl(), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mode: 'preview', token: reserveToken(), mdb: editorMissionKey(), field: { ...f, name: els.create_name.value || 'Vorschau', existing: fieldInCatalog() } }),
        });
        const data = await r.json();
        if (!els.createFieldModalBackdrop.classList.contains('show')) return;
        if (!data.ok) {
          const msg = `${data.error || 'preview_failed'}${data.detail ? ` - ${data.detail}` : ''}`;
          els.create_feld_id.value = '';
          setCreatePreviewNote(msg);
          showStatus(msg, false);
          return;
        }
        if (data.picture_dir) catalogPictureDir = String(data.picture_dir);
        setCreatePreviewNote([data.reserve_note, missing.length ? `Zuordnung steht. Noch offen: ${missing.join(', ')}.` : ''].filter(Boolean).join(' '));
        els.create_feld_id.value = String(data.feld_id || '');
        showPlace(els.create_jg, data.jg || '', data.jg_name || data.jg || '');
        showPlace(els.create_gemeinde, data.gemeinde || '', data.gemeinde_name || data.gemeinde || '');
        els.create_number.value = String(data.number || '');
        if (!window.RKRK_KAT) {
          els.create_feld_id.value = '';
          els.create_number.value = '';
        }
        updateCreateSaveButton();
        renderCreatePictureRows();
      } catch (e) {
        if (!els.createFieldModalBackdrop.classList.contains('show')) return;
        const msg = String(e.message || e);
        els.create_feld_id.value = '';
        setCreatePreviewNote(msg);
        showStatus(msg, false);
      }
    }

    function closeCreateFieldModal() {
      if (!els.createFieldModalBackdrop) return;
      const wasOpen = els.createFieldModalBackdrop.classList.contains('show');
      els.createFieldModalBackdrop.classList.remove('show');
      if (wasOpen && !fieldInCatalog()) releaseReservation();
    }

    function fieldInCatalog() {
      const id = String(els.id && els.id.value || '').trim();
      const feldId = String(els.feld_id && els.feld_id.value || '').trim();
      if (!Array.isArray(allFields)) return false;
      if (id && allFields.some((f) => String(f && f.id || '') === id)) return true;
      if (feldId && allFields.some((f) => String(f && f.feld_id || '').trim() === feldId)) return true;
      return false;
    }

    function modalDiffersFromForm() {
      if (!els.createFieldModalBackdrop || !els.createFieldModalBackdrop.classList.contains('show')) return false;
      const f = formData();
      const shownName = String((document.getElementById('planFieldName') && document.getElementById('planFieldName').value) || f.name || '').trim();
      if (String(els.create_name.value || '').trim() !== shownName) return true;
      if (placeCode(els.create_jg) !== placeCode(els.jg)) return true;
      if (placeCode(els.create_gemeinde) !== placeCode(els.gemeinde)) return true;
      if (String(els.create_std_flughoehe.value || '').trim() !== String(f.std_flughoehe || '').trim()) return true;
      if (catalogModalPairs.some(([modalId, formId]) => readControl(modalId) !== readControl(formId))) return true;
      if (String(els.create_startplatz.value || '').trim() !== String(f.startplatz || '').trim()) return true;
      if (String(els.create_flaechendaten.value || '').trim() !== String(f.flaechendaten || '').trim()) return true;
      if (String(els.create_flugweg.value || '').trim() !== String(f.flugweg || '').trim()) return true;
      return false;
    }

    function updateCreateSaveButton() {
      const btn = els.createModalSaveBtn;
      if (!btn) return;
      const inCatalog = fieldInCatalog();
      const dirty = currentFieldSnapshot() !== savedFieldSnapshot || modalDiffersFromForm();
      const canSave = !inCatalog || dirty;
      btn.disabled = !canSave;
      btn.title = !inCatalog
        ? 'Feld ist noch nicht im Feldkatalog'
        : (dirty ? 'Änderungen am Katalogfeld speichern' : 'Keine Änderungen gegenüber dem Feldkatalog');
    }

    async function createFieldFromModal() {
      if (els.createModalSaveBtn && els.createModalSaveBtn.disabled) return;
      const name = String(els.create_name.value || '').trim();
      if (!name) {
        setCreatePreviewNote('Feldname ist ein Pflichtfeld.');
        showStatus('Feldname ist ein Pflichtfeld.', false);
        return;
      }
      if (parsePairs(els.create_flaechendaten.value).length < 3 || parsePairs(els.create_startplatz.value).length < 1 || parseTriplets(els.create_flugweg.value).length < 2) {
        setCreatePreviewNote('Speichern braucht Umrandung, Startpunkt und Route.');
        showStatus('Speichern braucht Umrandung, Startpunkt und Route.', false);
        return;
      }
      const inCatalog = fieldInCatalog();
      els.name.value = name;
      syncPlanFieldNameFromForm();
      const chosen = String(els.create_feld_id.value || '').trim();
      if (/^\d+\s*-\s*\d+$/.test(chosen)) {
        els.feld_id.value = chosen.replace(/\s*-\s*/, '-');
      } else if (!inCatalog) {
        els.feld_id.value = '';
      }
      if (!inCatalog) {
        els.id.value = '';
        selectedId = '';
        els.number.value = String(els.create_number.value || '').trim();
      }
      showPlace(els.jg, placeCode(els.create_jg), els.create_jg ? els.create_jg.value : '');
      showPlace(els.gemeinde, placeCode(els.create_gemeinde), els.create_gemeinde ? els.create_gemeinde.value : '');
      writeControl('std_flughoehe', els.create_std_flughoehe.value || '50');
      copyCatalogModalToForm();
      els.startplatz.value = String(els.create_startplatz.value || '').trim();
      els.flaechendaten.value = String(els.create_flaechendaten.value || '').trim();
      els.flugweg.value = String(els.create_flugweg.value || '').trim();
      syncMapFromForm();
      const saved = await save(inCatalog ? 'update' : 'create');
      if (saved) {
        closeCreateFieldModal();
        updateCreateSaveButton();
      }
    }

    function activeAnalyzeFieldId() {
      const bySelected = String(selectedId || '').trim();
      if (bySelected) return bySelected;
      const byHidden = String(els.id && els.id.value || '').trim();
      if (byHidden) return byHidden;
      const feldKey = String(els.feld_id && els.feld_id.value || '').trim();
      if (feldKey && Array.isArray(allFields)) {
        const hit = allFields.find((f) => String(f && f.feld_id || '').trim() === feldKey);
        if (hit && String(hit.id || '').trim()) return String(hit.id).trim();
      }
      return '';
    }

    async function terrainDeltaMapForExport() {
      const startPts = parsePairs(els.startplatz && els.startplatz.value);
      const path = parseTriplets(els.flugweg && els.flugweg.value);
      const map = {};
      if (!startPts.length) return map;
      const startLonLat = [startPts[0][0], startPts[0][1]];
      const pts = [startLonLat];
      path.forEach((p) => {
        if (Array.isArray(p) && p.length >= 2) pts.push([p[0], p[1]]);
      });
      const deltas = await computeControlPointRelativeAgl(pts, startLonLat);
      pts.forEach((p, i) => {
        const d = Number(deltas[i]);
        if (!Number.isFinite(d)) return;
        map[`${Number(p[0]).toFixed(7)},${Number(p[1]).toFixed(7)}`] = d;
      });
      return map;
    }

    async function ensureCatalogFeldId() {
      if (!window.RKRK_KAT) return '';
      const current = String(els.feld_id && els.feld_id.value || '').trim();
      if (current && current.toUpperCase() !== 'UNDEF') return current;
      const name = String(els.name && els.name.value || '').trim().toLowerCase();
      const known = name ? (allFields || []).find((f) => String(f && f.name || '').trim().toLowerCase() === name && String(f.feld_id || '').trim()) : null;
      if (known) {
        if (els.feld_id) els.feld_id.value = String(known.feld_id).trim();
        return String(known.feld_id).trim();
      }
      const preview = String(els.create_feld_id && els.create_feld_id.value || '').trim();
      if (/^\d+-\d+$/.test(preview)) {
        if (els.feld_id) els.feld_id.value = preview;
        return preview;
      }
      if (parsePairs(els.flaechendaten && els.flaechendaten.value).length >= 3) {
        await assignCatalogFromOutline();
      }
      return String(els.feld_id && els.feld_id.value || '').trim();
    }

    async function callMissionApi(action) {
      if (action === 'export_uav_json' || action === 'export_dji_kmz') {
        await ensureCatalogFeldId();
      }
      const field = formData();
      if (action === 'export_dji_kmz' || action === 'export_uav_json') {
        try {
          field.terrain_delta_by_lonlat = await terrainDeltaMapForExport();
        } catch (_) {
          field.terrain_delta_by_lonlat = {};
        }
      }
      const r = await fetch('api_mission_export.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, field, kat: !!window.RKRK_KAT }),
      });
      const text = await r.text();
      let data = null;
      try {
        data = text ? JSON.parse(text) : null;
      } catch (_) {
        const plain = String(text || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220);
        throw new Error(`KMZ/Mission-API Fehler (HTTP ${r.status})${plain ? `: ${plain}` : ''}`);
      }
      if (!data || !data.ok) {
        throw new Error(`${(data && data.error) || 'mission_api_failed'}${(data && data.detail) ? ` - ${data.detail}` : ''}`);
      }
      return data;
    }

    async function callAnalyzeAutoPath() {
      const fieldId = activeAnalyzeFieldId();
      if (!fieldId) throw new Error('Auto-Algorithmus braucht eine FeldID.');
      const overlapPct = Number(els.msOverlapPct && els.msOverlapPct.value);
      const edgeFactorPct = Number(els.msEdgeFactorPct && els.msEdgeFactorPct.value);
      const altitudeM = Number(els.msAltitude && els.msAltitude.value);
      const speedMps = Number(els.msSpeed && els.msSpeed.value);
      const hfovDeg = readNumericElement(els.msHfov, 49);
      const dfovDeg = readNumericElement(els.msDfov, 61);
      const flightDirDeg = clampFlightDirection(els.msFlightDirection && els.msFlightDirection.value);
      const startSpeedMps = Number(els.msStartSpeed && els.msStartSpeed.value);
      const overlap = Number.isFinite(overlapPct) ? Math.min(0.95, Math.max(0, overlapPct / 100)) : 0.15;
      const edgeFactor = Number.isFinite(edgeFactorPct) ? Math.min(1, Math.max(0, edgeFactorPct / 100)) : 0.40;
      const q = new URLSearchParams({
        field_id: fieldId,
        planner: 'php',
        // For editor recomputation, mission "Flugvorgaben" must be authoritative.
        use_field_reference_agl: '0',
        overlap: String(overlap),
        edge_factor: String(edgeFactor),
      });
      if (els.msDroneType && els.msDroneType.value) q.set('drone_type', String(els.msDroneType.value));
                  if (els.msWaypointType && els.msWaypointType.value) q.set('waypoint_type', String(els.msWaypointType.value));
      if (Number.isFinite(altitudeM)) q.set('agl', String(altitudeM));
      if (Number.isFinite(hfovDeg)) q.set('hfov', String(hfovDeg));
      if (Number.isFinite(dfovDeg)) q.set('dfov', String(dfovDeg));
      if (Number.isFinite(flightDirDeg) && flightDirDeg >= 0) q.set('flight_direction', String(flightDirDeg));
      if (Number.isFinite(speedMps)) {
        q.set('speed', String(speedMps));
        q.set('speed_m_s', String(speedMps));
      }
      if (els.msStartSpeedActive) q.set('start_speed_active', els.msStartSpeedActive.checked ? '1' : '0');
      if (Number.isFinite(startSpeedMps)) q.set('start_speed_m_s', String(startSpeedMps));
      const liveStart = parsePairs(els.startplatz && els.startplatz.value);
      if (liveStart.length >= 1) {
        q.set('start_lon', String(liveStart[0][0]));
        q.set('start_lat', String(liveStart[0][1]));
      }
      q.set('simple_mode', isSimpleComputeMode() ? '1' : '0');
      const r = await fetch(`api_analyze.php?${q.toString()}`, { cache: 'no-store' });
      const data = await r.json();
      if (!data || data.ok === false) {
        throw new Error(`${(data && data.error) || 'analyze_auto_failed'}${(data && data.detail) ? ` - ${data.detail}` : ''}`);
      }
      const path = normalizeTripletPath(
        (data.algorithm && Array.isArray(data.algorithm.path)) ? data.algorithm.path : []
      );
      if (path.length < 2) throw new Error('Analyse-Algorithmus lieferte keinen gueltigen Flugpfad.');
      return {
        path,
        metrics: (data.algorithm && data.algorithm.metrics) || {},
        formula: (data && data.formula && typeof data.formula === 'object') ? data.formula : null,
        control_points_path: (data.algorithm && Array.isArray(data.algorithm.control_points_path))
          ? data.algorithm.control_points_path
          : ((data.algorithm && Array.isArray(data.algorithm.control_points)) ? data.algorithm.control_points : []),
        control_points_field: (data.algorithm && Array.isArray(data.algorithm.control_points_field))
          ? data.algorithm.control_points_field
          : [],
      };
    }

    async function fetchAnalyzeControlPoints() {
      const fieldId = activeAnalyzeFieldId();
      if (!fieldId) {
        return { control_points_path: [], control_points_field: [], formula: null };
      }
      const overlapPct = Number(els.msOverlapPct && els.msOverlapPct.value);
      const edgeFactorPct = Number(els.msEdgeFactorPct && els.msEdgeFactorPct.value);
      const altitudeM = Number(els.msAltitude && els.msAltitude.value);
      const speedMps = Number(els.msSpeed && els.msSpeed.value);
      const hfovDeg = readNumericElement(els.msHfov, 49);
      const dfovDeg = readNumericElement(els.msDfov, 61);
      const flightDirDeg = clampFlightDirection(els.msFlightDirection && els.msFlightDirection.value);
      const startSpeedMps = Number(els.msStartSpeed && els.msStartSpeed.value);
      const overlap = Number.isFinite(overlapPct) ? Math.min(0.95, Math.max(0, overlapPct / 100)) : 0.15;
      const edgeFactor = Number.isFinite(edgeFactorPct) ? Math.min(1, Math.max(0, edgeFactorPct / 100)) : 0.40;
      const q = new URLSearchParams({
        field_id: fieldId,
        planner: 'php',
        // Keep control-point query aligned with recomputed mission path inputs.
        use_field_reference_agl: '0',
        overlap: String(overlap),
        edge_factor: String(edgeFactor),
      });
      if (els.msDroneType && els.msDroneType.value) q.set('drone_type', String(els.msDroneType.value));
                  if (els.msWaypointType && els.msWaypointType.value) q.set('waypoint_type', String(els.msWaypointType.value));
      if (Number.isFinite(altitudeM)) q.set('agl', String(altitudeM));
      if (Number.isFinite(hfovDeg)) q.set('hfov', String(hfovDeg));
      if (Number.isFinite(dfovDeg)) q.set('dfov', String(dfovDeg));
      if (Number.isFinite(flightDirDeg) && flightDirDeg >= 0) q.set('flight_direction', String(flightDirDeg));
      if (Number.isFinite(speedMps)) {
        q.set('speed', String(speedMps));
        q.set('speed_m_s', String(speedMps));
      }
      if (els.msStartSpeedActive) q.set('start_speed_active', els.msStartSpeedActive.checked ? '1' : '0');
      if (Number.isFinite(startSpeedMps)) q.set('start_speed_m_s', String(startSpeedMps));
      const liveStart = parsePairs(els.startplatz && els.startplatz.value);
      if (liveStart.length >= 1) {
        q.set('start_lon', String(liveStart[0][0]));
        q.set('start_lat', String(liveStart[0][1]));
      }
      q.set('simple_mode', isSimpleComputeMode() ? '1' : '0');
      const r = await fetch(`api_analyze.php?${q.toString()}`, { cache: 'no-store' });
      const data = await r.json();
      if (!data || data.ok === false) {
        return { control_points_path: [], control_points_field: [], formula: null };
      }
      return {
        control_points_path: (data.algorithm && Array.isArray(data.algorithm.control_points_path))
          ? data.algorithm.control_points_path
          : ((data.algorithm && Array.isArray(data.algorithm.control_points)) ? data.algorithm.control_points : []),
        control_points_field: (data.algorithm && Array.isArray(data.algorithm.control_points_field))
          ? data.algorithm.control_points_field
          : [],
        formula: (data && data.formula && typeof data.formula === 'object') ? data.formula : null,
      };
    }

    async function recomputePathFromFlightVorgaben(showSuccessMessage = true, force = false) {
      if (!force && blockAutoPathRecompute > 0) {
        flightRecomputeQueued = false;
        return;
      }
      if (flightRecomputeRunning) {
        flightRecomputeQueued = true;
        return;
      }
      const startPts = parsePairs(els.startplatz.value);
      const polyPts = parsePairs(els.flaechendaten.value);
      if (startPts.length < 1 || polyPts.length < 3) return;
      const token = ++pathRecomputeToken;
      try {
        currentAnalyzeFormula = null;
        currentPathCalcSource = 'mission';
        refreshWsVisualLines();
        const manualBefore = parseTriplets(els.flugweg.value);
        const flightDir = clampFlightDirection(els.msFlightDirection && els.msFlightDirection.value);
        const forceCoverageFix = !!(els.forceCoverageFix && els.forceCoverageFix.checked);
        let data = null;
        if (forceCoverageFix) {
          try {
            const analyzed = await callAnalyzeAutoPath();
            data = {
              path: analyzed.path,
              flugweg_text: formatTriplets(analyzed.path),
              metrics: analyzed.metrics || {},
            };
            currentAnalyzeFormula = analyzed.formula && typeof analyzed.formula === 'object' ? analyzed.formula : null;
            currentPathCalcSource = 'analyze';
            currentTurnWaypointIndices = extractTurnWaypointIndices(analyzed.metrics || {});
            currentControlPointsPath = normalizeTripletPath(analyzed.control_points_path || []).map((p) => [p[0], p[1]]);
            currentControlPointsField = normalizeTripletPath(analyzed.control_points_field || []).map((p) => [p[0], p[1]]);
          } catch (_) {
            try {
              // Never fail path generation completely: fallback to mission planner.
              data = await callMissionApi('compute_path');
              currentAnalyzeFormula = null;
              currentPathCalcSource = 'mission';
              currentTurnWaypointIndices = extractTurnWaypointIndices(data.metrics || {});
              try {
                const cp = await fetchAnalyzeControlPoints();
                if (cp && cp.formula && typeof cp.formula === 'object') currentAnalyzeFormula = cp.formula;
                currentControlPointsPath = normalizeTripletPath(cp.control_points_path || []).map((p) => [p[0], p[1]]);
                currentControlPointsField = normalizeTripletPath(cp.control_points_field || []).map((p) => [p[0], p[1]]);
              } catch (_) {
                currentControlPointsPath = [];
                currentControlPointsField = [];
              }
            } catch (_) {
              data = { path: [], flugweg_text: '', metrics: {} };
            }
          }
        } else if (flightDir === -1) {
          // Use mission compute_path as primary path source for reliable live updates in auto mode.
          // flight_direction_deg = -1 is preserved in formData() and interpreted as auto there.
          try {
            data = await callMissionApi('compute_path');
            currentAnalyzeFormula = null;
            currentPathCalcSource = 'mission';
            currentTurnWaypointIndices = extractTurnWaypointIndices(data.metrics || {});
            try {
              const cp = await fetchAnalyzeControlPoints();
              if (cp && cp.formula && typeof cp.formula === 'object') currentAnalyzeFormula = cp.formula;
              currentControlPointsPath = normalizeTripletPath(cp.control_points_path || []).map((p) => [p[0], p[1]]);
              currentControlPointsField = normalizeTripletPath(cp.control_points_field || []).map((p) => [p[0], p[1]]);
            } catch (_) {
              currentControlPointsPath = [];
              currentControlPointsField = [];
            }
          } catch (_) {
            const analyzed = await callAnalyzeAutoPath();
            data = {
              path: analyzed.path,
              flugweg_text: formatTriplets(analyzed.path),
              metrics: analyzed.metrics || {},
            };
            currentAnalyzeFormula = analyzed.formula && typeof analyzed.formula === 'object' ? analyzed.formula : null;
            currentPathCalcSource = 'analyze';
            currentTurnWaypointIndices = extractTurnWaypointIndices(analyzed.metrics || {});
            currentControlPointsPath = normalizeTripletPath(analyzed.control_points_path || []).map((p) => [p[0], p[1]]);
            currentControlPointsField = normalizeTripletPath(analyzed.control_points_field || []).map((p) => [p[0], p[1]]);
          }
        } else {
          try {
            data = await callMissionApi('compute_path');
            currentAnalyzeFormula = null;
            currentPathCalcSource = 'mission';
            currentTurnWaypointIndices = extractTurnWaypointIndices(data.metrics || {});
            try {
              const cp = await fetchAnalyzeControlPoints();
              if (cp && cp.formula && typeof cp.formula === 'object') currentAnalyzeFormula = cp.formula;
              currentControlPointsPath = normalizeTripletPath(cp.control_points_path || []).map((p) => [p[0], p[1]]);
              currentControlPointsField = normalizeTripletPath(cp.control_points_field || []).map((p) => [p[0], p[1]]);
            } catch (_) {
              currentControlPointsPath = [];
              currentControlPointsField = [];
            }
          } catch (_) {
            const analyzed = await callAnalyzeAutoPath();
            data = {
              path: analyzed.path,
              flugweg_text: formatTriplets(analyzed.path),
              metrics: analyzed.metrics || {},
            };
            currentAnalyzeFormula = analyzed.formula && typeof analyzed.formula === 'object' ? analyzed.formula : null;
            currentPathCalcSource = 'analyze';
            currentTurnWaypointIndices = extractTurnWaypointIndices(analyzed.metrics || {});
            currentControlPointsPath = normalizeTripletPath(analyzed.control_points_path || []).map((p) => [p[0], p[1]]);
            currentControlPointsField = normalizeTripletPath(analyzed.control_points_field || []).map((p) => [p[0], p[1]]);
          }
        }
        if (!data) data = { path: [], flugweg_text: '', metrics: {} };
        if (token !== pathRecomputeToken || (!force && blockAutoPathRecompute > 0)) return;
        currentComputedPath = normalizeTripletPath(Array.isArray(data.path) ? data.path : parseTriplets(data.flugweg_text || ''));
        routeCoverageReady = currentComputedPath.length >= 2;
        if (currentComputedPath.length < 2) {
          const startNow = parsePairs(els.startplatz && els.startplatz.value);
          const polyNow = parsePairs(els.flaechendaten && els.flaechendaten.value);
          const startLL = startNow.length ? [Number(startNow[0][0]), Number(startNow[0][1])] : null;
          let targetLL = null;
          if (polyNow.length >= 1) {
            targetLL = [Number(polyNow[0][0]), Number(polyNow[0][1])];
          } else if (startLL) {
            targetLL = [startLL[0] + 0.00001, startLL[1] + 0.00001];
          }
          const fallbackAlt = Number(els.msAltitude && els.msAltitude.value);
          const fallbackAgl = Number.isFinite(fallbackAlt) ? fallbackAlt : 50;
          if (startLL && targetLL) {
            currentComputedPath = [
              [startLL[0], startLL[1], 0],
              [startLL[0], startLL[1], fallbackAgl],
              [targetLL[0], targetLL[1], fallbackAgl],
            ];
          }
        }
        const pathText = formatTriplets(currentComputedPath);
        els.flugweg.value = pathText;
        clearWaypointOverrides();
        if (resetOriginalPathOnNextRecompute || !pathDivergedFromGenerated) {
          markGeneratedPath(currentComputedPath);
        }
        if (resetOriginalPathOnNextRecompute) {
          currentManualPath = currentComputedPath.map((p) => [p[0], p[1], p[2]]);
          originalManualPath = [];
          resetOriginalPathOnNextRecompute = false;
          pathDivergedFromGenerated = false;
        } else {
          currentManualPath = manualBefore;
        }
        if (els.showAlgorithmPath) els.showAlgorithmPath.checked = false;
        if (els.showManualPath) els.showManualPath.checked = true;
        updatePathResetButton();
        revealPlanReviewIfReady();
        const dist = Number(data.metrics && data.metrics.distance_m);
        const wps = Number(data.metrics && data.metrics.waypoints);
        if (Number.isFinite(dist)) els.distanz.value = dist.toFixed(1);
        if (Number.isFinite(wps)) els.wegpunkte.value = String(Math.round(wps));
        else if (currentComputedPath.length) els.wegpunkte.value = String(currentComputedPath.length);
        applySuggestedFlightDirection(data.used_inputs);
        refreshFlightPathLayers();
        scheduleNewFieldDraftSave();
        showStatus(forceCoverageFix
          ? 'Flugpfad berechnet (Korrektur nicht abgedeckter Flaechen forciert).'
          : 'Flugpfad neu berechnet. Anflug Start → Feld darf ausserhalb der Feldgrenze liegen.');
      } finally {
        flightRecomputeRunning = false;
        if (flightRecomputeQueued && token === pathRecomputeToken && blockAutoPathRecompute === 0) {
          flightRecomputeQueued = false;
          setTimeout(() => {
            recomputePathFromFlightVorgaben(false).catch((e) => {
              showStatus(String(e.message || e), false);
            });
          }, 0);
        } else {
          flightRecomputeQueued = false;
        }
      }
    }

    function scheduleFlightPathRecompute() {
      if (skipFlightRecomputeOnce) {
        skipFlightRecomputeOnce = false;
        return;
      }
      if (flightRecomputeTimer) clearTimeout(flightRecomputeTimer);
      recomputePathFromFlightVorgaben(false).catch((e) => {
        showStatus(String(e.message || e), false);
      });
    }

    async function recomputeFlightPathNow() {
      if (flightRecomputeTimer) clearTimeout(flightRecomputeTimer);
      try {
        await recomputePathFromFlightVorgaben(false);
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    }

    function wireImmediateRecompute(el, preprocess) {
      if (!el) return;
      const runNow = async () => {
        if (typeof preprocess === 'function') preprocess();
        updateSaveButtonState();
        try {
          await recomputePathFromFlightVorgaben(true);
        } catch (e) {
          showStatus(String(e.message || e), false);
        }
        saveEditorLastSettings();
      };
      el.addEventListener('input', () => { runNow(); });
      el.addEventListener('change', () => { runNow(); });
    }

    function wireHardParamRecompute(el, preprocess) {
      if (!el) return;
      const run = () => {
        if (typeof preprocess === 'function') preprocess();
        currentAnalyzeFormula = null;
        currentPathCalcSource = 'mission';
        refreshWsVisualLines();
        recomputePathFromFlightVorgaben(true).catch((e) => {
          showStatus(String(e.message || e), false);
        });
      };
      el.addEventListener('input', run);
      el.addEventListener('change', run);
    }

    function bindCoreParamUpdate(el, preprocess) {
      if (!el) return;
      const run = () => {
        if (typeof preprocess === 'function') preprocess();
        currentAnalyzeFormula = null;
        currentPathCalcSource = 'mission';
        refreshWsVisualLines();
        updateSaveButtonState();
        saveEditorLastSettings();
        recomputePathFromFlightVorgaben(true).catch((e) => {
          showStatus(String(e.message || e), false);
        });
      };
      el.addEventListener('input', run);
      el.addEventListener('change', run);
    }

    function downloadTextFile(filename, text, mime = 'application/json') {
      const blob = new Blob([text], { type: mime });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }

    function localFileBase(field) {
      const raw = String((field && (field.name || field.feld_id)) || 'feld').trim() || 'feld';
      return raw.replace(/[^\w\u00C0-\u024f.-]+/g, '_').replace(/^_+|_+$/g, '') || 'feld';
    }

    function xmlEscapeText(value) {
      return String(value || '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&apos;' }[ch]));
    }

    function kmlCoord(lon, lat, alt) {
      const h = Number(alt);
      return Number(lon).toFixed(7) + ',' + Number(lat).toFixed(7) + ',' + (Number.isFinite(h) ? h.toFixed(1) : '0.0');
    }

    function localFieldSnapshot() {
      const snap = {
        name: String(els.name && els.name.value || '').trim(),
        std_flughoehe: String(els.std_flughoehe && els.std_flughoehe.value || '').trim(),
        startplatz: String(els.startplatz && els.startplatz.value || '').trim(),
        flaechendaten: String(els.flaechendaten && els.flaechendaten.value || '').trim(),
        flugweg: String(els.flugweg && els.flugweg.value || '').trim(),
        drone_type: String(els.msDroneType && els.msDroneType.value || '').trim()
      };
      if (window.RKRK_KAT) snap.feld_id = String(els.feld_id && els.feld_id.value || '').trim();
      return snap;
    }

    function buildLocalKml(field) {
      const poly = parsePairs(field.flaechendaten);
      const path = parseTriplets(field.flugweg);
      const start = parsePairs(field.startplatz)[0];
      if (poly.length < 3 && path.length < 2) {
        throw new Error('Zuerst die Fläche oder den Flugweg zeichnen.');
      }
      let body = '';
      if (poly.length >= 3) {
        const ring = poly.slice();
        const first = ring[0];
        const last = ring[ring.length - 1];
        if (first[0] !== last[0] || first[1] !== last[1]) ring.push(first);
        body += '<Placemark><name>Fläche</name><Polygon><outerBoundaryIs><LinearRing><coordinates>'
          + ring.map((p) => kmlCoord(p[0], p[1], 0)).join(' ')
          + '</coordinates></LinearRing></outerBoundaryIs></Polygon></Placemark>';
      }
      if (start) {
        body += '<Placemark><name>Start</name><Point><coordinates>' + kmlCoord(start[0], start[1], 0) + '</coordinates></Point></Placemark>';
      }
      if (path.length >= 2) {
        body += '<Placemark><name>Flugweg</name><LineString><tessellate>1</tessellate><coordinates>'
          + path.map((p) => kmlCoord(p[0], p[1], p[2])).join(' ')
          + '</coordinates></LineString></Placemark>';
      }
      const name = field.name || field.feld_id || 'Feld';
      return '<' + '?xml version="1.0" encoding="UTF-8"?>'
        + '<kml xmlns="http://www.opengis.net/kml/2.2"><Document><name>' + xmlEscapeText(name) + '</name>'
        + body + '</Document></kml>';
    }

    function downloadLocalField(kind) {
      const field = localFieldSnapshot();
      const base = localFileBase(field);
      if (kind === 'kml') {
        downloadTextFile(base + '.kml', buildLocalKml(field), 'application/vnd.google-earth.kml+xml');
        showStatus('KML liegt im Download-Ordner.');
        return;
      }
      downloadTextFile(base + '.json', JSON.stringify(field, null, 2), 'application/json');
      showStatus('JSON liegt im Download-Ordner.');
    }

    function installCatalogSwitch() {
      if (window.RKRK_KAT) return;
      document.querySelectorAll('.rkrk-kat-only').forEach((el) => { el.hidden = true; });
      ['planOriginExisting', 'planExistingPick', 'planSave', 'saveBtn', 'createModalSaveBtn', 'fieldSearchBox', 'quickSlotBox', 'planPicRenew', 'createPicRenew', 'planPicClose', 'planPicNote'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.hidden = true;
      });
      const kmlBtn = document.getElementById('planKmz');
      if (kmlBtn) {
        kmlBtn.textContent = '📄 KML';
        kmlBtn.title = 'KML lokal herunterladen';
      }
      const jsonBtn = document.getElementById('planJson');
      if (jsonBtn) jsonBtn.title = 'JSON lokal herunterladen';
      const mapKml = document.getElementById('exportDjiKmzBtn');
      if (mapKml) {
        mapKml.textContent = '📄 KML';
        mapKml.title = 'KML lokal herunterladen';
      }
      const mapJson = document.getElementById('exportUavJsonBtn');
      if (mapJson) mapJson.title = 'JSON lokal herunterladen';
      const note = document.querySelector('.plan-actions + .plan-note');
      if (note) note.textContent = 'Ohne ?KAT bleibt das Feld auf diesem Gerät. KML oder JSON gehen in den Downloadordner. Eine Feldkatalognummer wird nicht vergeben.';
      const finishNote = document.querySelector('[data-plan-phase="finish"] .plan-note');
      if (finishNote) finishNote.textContent = 'Ohne ?KAT gibt es keine Feldkatalognummer. KML oder JSON laden das Feld herunter.';
    }

    function downloadBase64File(filename, b64, mime = 'application/vnd.google-earth.kmz') {
      const bin = atob(String(b64 || ''));
      const bytes = new Uint8Array(bin.length);
      for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
      const blob = new Blob([bytes], { type: mime });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }

    els.searchInput.addEventListener('input', renderList);
    [els.startplatz, els.flaechendaten, els.flugweg].forEach((el) => {
      if (!el) return;
      el.addEventListener('input', scheduleMapSyncFromForm);
      el.addEventListener('change', scheduleMapSyncFromForm);
    });
    if (els.startplatz) {
      const markStartFormChange = () => {
        if (parsePairs(els.startplatz.value).length >= 1) {
          resetOriginalPathOnNextRecompute = true;
        }
      };
      els.startplatz.addEventListener('input', markStartFormChange);
      els.startplatz.addEventListener('change', markStartFormChange);
    }
    const dirtyTrackedInputs = [
      els.feld_id, els.name, els.jg, els.std_flughoehe, els.flugzeit, els.distanz, els.wegpunkte,
      els.startplatz, els.flaechendaten, els.flugweg,
      els.msDroneType, els.msKmzParamsActive, els.msAltitude, els.msFlightDirectionRange, els.msFlightDirection, els.msSpeed, els.msStartSpeedActive, els.msStartSpeed,
      els.msOverlapPctRange, els.msOverlapPct, els.msEdgeFactorPctRange, els.msEdgeFactorPct,
      els.msEndAction, els.msHeadingMode, els.msWaypointType, els.msTurnDistance, els.msSafeStartAltitude,
      els.msDirectToStart, els.msGimbalMode, els.msGimbalAngleActive, els.msAltitudeMode,
    ];
    dirtyTrackedInputs.forEach((el) => {
      if (!el) return;
      el.addEventListener('input', updateSaveButtonState);
      el.addEventListener('change', updateSaveButtonState);
    });
    document.querySelectorAll('#formDrawerBox input, #formDrawerBox select, #formDrawerBox textarea').forEach((el) => {
      el.addEventListener('input', updateSaveButtonState);
      el.addEventListener('change', updateSaveButtonState);
    });
    const planGisInput = document.getElementById('planGis');
    const formGisInput = document.getElementById('grundstueck_id');
    if (planGisInput && formGisInput) {
      planGisInput.addEventListener('input', () => {
        formGisInput.value = planGisInput.value;
        updateSaveButtonState();
      });
      formGisInput.addEventListener('input', () => {
        planGisInput.value = formGisInput.value;
        syncPlanGisNote();
        updateSaveButtonState();
      });
    }
    const createModalBox = document.querySelector('.create-modal');
    if (createModalBox) {
      createModalBox.addEventListener('input', updateCreateSaveButton);
      createModalBox.addEventListener('change', updateCreateSaveButton);
    }
    if (els.feld_id) {
      els.feld_id.addEventListener('input', () => {
        syncJgFromFeldId('main');
        updateSaveButtonState();
        const planId = document.getElementById('planFeldId');
        if (planId && document.activeElement !== planId) planId.value = els.feld_id.value;
        scheduleFeldIdPlace(els.feld_id.value);
      });
      els.feld_id.addEventListener('change', () => {
        syncJgFromFeldId('main');
        updateSaveButtonState();
        scheduleFeldIdPlace(els.feld_id.value);
      });
    }
    if (els.create_feld_id) {
      els.create_feld_id.addEventListener('input', () => {
        syncJgFromFeldId('create');
        scheduleFeldIdPlace(els.create_feld_id.value);
      });
      els.create_feld_id.addEventListener('change', () => {
        syncJgFromFeldId('create');
        scheduleFeldIdPlace(els.create_feld_id.value);
      });
    }
    els.std_flughoehe.addEventListener('input', () => {
      refreshPolygonVertexLabels();
      if (startSource) startSource.changed();
      if (vertexSource) vertexSource.changed();
      scheduleFlightPathRecompute();
    });
    els.msKmzParamsActive.addEventListener('change', () => {
      applyKmzParamsUiState();
      updateSaveButtonState();
      recomputeFlightPathNow();
      saveEditorLastSettings();
    });
    wireImmediateRecompute(els.msDroneType, () => { applyDroneProfileToForm(); refreshFlightPathLayers(); });
    wireImmediateRecompute(els.msAltitude);
    wireImmediateRecompute(els.msSpeed, () => { syncPlanSpeedField(); syncPlanStartSpeed(); });
    wireImmediateRecompute(els.msStartSpeedActive);
    wireImmediateRecompute(els.msStartSpeed);
    wireImmediateRecompute(els.msFlightDirectionRange, () => {
      resetOriginalPathOnNextRecompute = true;
      syncFlightDirectionControls('range');
    });
    wireImmediateRecompute(els.msFlightDirection, () => {
      resetOriginalPathOnNextRecompute = true;
      syncFlightDirectionControls('number');
    });
    wireImmediateRecompute(els.msOverlapPctRange, () => syncOverlapControls('range'));
    wireImmediateRecompute(els.msOverlapPct, () => syncOverlapControls('number'));
    wireImmediateRecompute(els.msEdgeFactorPctRange, () => syncEdgeFactorControls('range'));
    wireImmediateRecompute(els.msEdgeFactorPct, () => syncEdgeFactorControls('number'));
    // Hard guarantee: these core mission params must always trigger immediate path recomputation.
    wireHardParamRecompute(els.msDroneType, () => { applyDroneProfileToForm(); refreshFlightPathLayers(); });
    wireHardParamRecompute(els.msAltitude);
    wireHardParamRecompute(els.msOverlapPctRange, () => syncOverlapControls('range'));
    wireHardParamRecompute(els.msOverlapPct, () => syncOverlapControls('number'));
    wireHardParamRecompute(els.msEdgeFactorPctRange, () => syncEdgeFactorControls('range'));
    wireHardParamRecompute(els.msEdgeFactorPct, () => syncEdgeFactorControls('number'));
    // Final guarantee: core mission params always force a visible immediate update.
    bindCoreParamUpdate(els.msDroneType, () => { applyDroneProfileToForm(); refreshFlightPathLayers(); });
    bindCoreParamUpdate(els.msAltitude);
    bindCoreParamUpdate(els.msOverlapPctRange, () => syncOverlapControls('range'));
    bindCoreParamUpdate(els.msOverlapPct, () => syncOverlapControls('number'));
    bindCoreParamUpdate(els.msEdgeFactorPctRange, () => syncEdgeFactorControls('range'));
    bindCoreParamUpdate(els.msEdgeFactorPct, () => syncEdgeFactorControls('number'));
    if (els.msGimbalAngleActive) {
      els.msGimbalAngleActive.addEventListener('change', () => {
        syncGimbalAngleFlag('check');
        applyWaypointFormEnableState();
        commitGimbalAngleOnly();
      });
    }
    if (els.msGimbalMode) {
      els.msGimbalMode.addEventListener('change', () => {
        syncGimbalAngleFlag();
        applyWaypointFormEnableState();
        commitGimbalAngleOnly();
      });
    }
    [els.wpPitch, els.wpPitchRange].forEach((el) => {
      if (!el) return;
      const onPitch = () => {
        if (waypointFormFilling) return;
        if (el === els.wpPitchRange) syncWpNumberRange(els.wpPitch, els.wpPitchRange, 'range');
        if (el === els.wpPitch) syncWpNumberRange(els.wpPitch, els.wpPitchRange, 'number');
        commitGimbalAngleOnly();
      };
      el.addEventListener('input', onPitch);
      el.addEventListener('change', onPitch);
    });
    [
      els.msKmzParamsActive,
      els.msEndAction, els.msHeadingMode,
      els.msWaypointType, els.msTurnDistance, els.msSafeStartAltitude, els.msDirectToStart,
      els.msAltitudeMode,
    ].forEach((el) => {
      if (!el) return;
      el.addEventListener('input', scheduleFlightPathRecompute);
      el.addEventListener('change', scheduleFlightPathRecompute);
      el.addEventListener('change', saveEditorLastSettings);
    });
    els.baseMapMode.addEventListener('change', () => {
      syncBasemapButtons();
      applyBaseMapMode();
      updateSwissOverlayVisibility();
      saveEditorLastSettings();
    });
    initBasemapToggle();
    els.showBoxMapMenu.addEventListener('change', applyBoxVisibilityStateFromSetup);
    if (els.showBoxDisplayControl) els.showBoxDisplayControl.addEventListener('change', applyBoxVisibilityStateFromSetup);
    if (els.showBoxContextHelp) els.showBoxContextHelp.addEventListener('change', applyBoxVisibilityStateFromSetup);
    els.showBoxSwissOverlay.addEventListener('change', () => {
      if (!layerWindowSyncing) layerWindowUserOff = !els.showBoxSwissOverlay.checked;
      applyBoxVisibilityStateFromSetup();
    });
    els.showBoxMissionSettings.addEventListener('change', applyBoxVisibilityStateFromSetup);
    els.showBoxFieldCharacteristics.addEventListener('change', applyBoxVisibilityStateFromSetup);
    if (els.showBoxWaypointData) els.showBoxWaypointData.addEventListener('change', applyBoxVisibilityStateFromSetup);
    if (els.jg) els.jg.addEventListener('input', () => { delete els.jg.dataset.code; });
    els.showBoxFormDrawer.addEventListener('change', applyBoxVisibilityStateFromSetup);
    if (els.swissOverlayBox) {
      els.swissOverlayBox.addEventListener('toggle', () => {
        if (!layerWindowSyncing) layerWindowUserOff = !els.swissOverlayBox.open;
        saveFloatingUiState({ swissOverlayOpen: !!els.swissOverlayBox.open });
      });
    }
    if (els.formDrawerBox) {
      els.formDrawerBox.addEventListener('toggle', () => {
        saveFloatingUiState({ formDrawerOpen: !!els.formDrawerBox.open });
      });
    }
    if (els.displayControlBox && 'open' in els.displayControlBox) {
      els.displayControlBox.addEventListener('toggle', () => {
        saveFloatingUiState({ displayControlOpen: !!els.displayControlBox.open });
      });
    }
    if (els.fieldCharacteristicsBox) {
      els.fieldCharacteristicsBox.addEventListener('toggle', () => {
        saveFloatingUiState({ fieldCharacteristicsOpen: !!els.fieldCharacteristicsBox.open });
      });
    }
    if (els.wpDebugTableBox) {
      els.wpDebugTableBox.addEventListener('toggle', () => {
        saveFloatingUiState({ waypointDataOpen: !!els.wpDebugTableBox.open });
      });
    }
    window.addEventListener('beforeunload', persistUiStateNow);
    window.addEventListener('pagehide', () => { if (window.RKRK_KAT) releaseReservation(); });
    if (window.RKRK_KAT) {
      setInterval(() => {
        if (fieldInCatalog()) return;
        const held = String((els.feld_id && els.feld_id.value) || '').trim();
        if (!/^\d+-\d+$/.test(held)) return;
        fetch(editorApiUrl(), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mode: 'heartbeat', token: reserveToken(), mdb: editorMissionKey() }),
        }).catch(() => {});
      }, 60000);
    }
    els.layerRoadNames.addEventListener('change', applyOverlayLayers);
    els.layerElectric.addEventListener('change', applyOverlayLayers);
    els.layerBoundaries.addEventListener('change', applyOverlayLayers);
    els.layerJagdbann.addEventListener('change', applyOverlayLayers);
    els.layerRpas.addEventListener('change', applyOverlayLayers);
    if (els.layerUas) els.layerUas.addEventListener('change', applyOverlayLayers);
    if (els.layerAulav) els.layerAulav.addEventListener('change', applyOverlayLayers);
    if (els.layerAlti3d) els.layerAlti3d.addEventListener('change', applyOverlayLayers);
    if (els.layerSurface3d) els.layerSurface3d.addEventListener('change', applyOverlayLayers);
    if (els.layerDabs) els.layerDabs.addEventListener('change', applyOverlayLayers);
    els.layerObstacles.addEventListener('change', applyOverlayLayers);
    if (els.obstacleHeights) {
      els.obstacleHeights.addEventListener('change', () => {
        saveFloatingUiState({ overlayToggles: overlayToggleStateFromInputs() });
      });
    }
    els.layerGrundbuch.addEventListener('change', applyOverlayLayers);
    els.layerCadastral.addEventListener('change', applyOverlayLayers);
    els.layerNature.addEventListener('change', applyOverlayLayers);
    els.layerCableways.addEventListener('change', applyOverlayLayers);
    if (els.layerWanderwege) els.layerWanderwege.addEventListener('change', applyOverlayLayers);
    els.layerRoadNamesOpacity.addEventListener('input', applyOverlayLayers);
    els.layerElectricOpacity.addEventListener('input', applyOverlayLayers);
    els.layerBoundariesOpacity.addEventListener('input', applyOverlayLayers);
    els.layerJagdbannOpacity.addEventListener('input', applyOverlayLayers);
    els.layerRpasOpacity.addEventListener('input', applyOverlayLayers);
    if (els.layerUasOpacity) els.layerUasOpacity.addEventListener('input', applyOverlayLayers);
    if (els.layerAulavOpacity) els.layerAulavOpacity.addEventListener('input', applyOverlayLayers);
    if (els.layerAlti3dOpacity) els.layerAlti3dOpacity.addEventListener('input', applyOverlayLayers);
    if (els.layerSurface3dOpacity) els.layerSurface3dOpacity.addEventListener('input', applyOverlayLayers);
    if (els.layerDabsOpacity) els.layerDabsOpacity.addEventListener('input', applyOverlayLayers);
    els.layerObstaclesOpacity.addEventListener('input', applyOverlayLayers);
    els.layerGrundbuchOpacity.addEventListener('input', applyOverlayLayers);
    els.layerCadastralOpacity.addEventListener('input', applyOverlayLayers);
    els.layerNatureOpacity.addEventListener('input', applyOverlayLayers);
    els.layerCablewaysOpacity.addEventListener('input', applyOverlayLayers);
    if (els.layerWanderwegeOpacity) els.layerWanderwegeOpacity.addEventListener('input', applyOverlayLayers);
    els.backBtn.addEventListener('click', () => {
      const from = new URLSearchParams(window.location.search).get('from') || 'analyse_ui.php';
      window.open(from, '_blank', 'noopener');
    });
    els.newBtn.addEventListener('click', clearForm);
    els.createModalCancelBtn.addEventListener('click', closeCreateFieldModal);
    ['create_name', 'create_std_flughoehe', 'create_startplatz', 'create_flaechendaten', 'create_flugweg'].forEach((key) => {
      const input = els[key];
      if (!input) return;
      input.addEventListener('input', updateCreateSaveButton);
    });
    els.createModalSaveBtn.addEventListener('click', async () => {
      try {
        await createFieldFromModal();
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    document.querySelectorAll('[data-create-tab]').forEach((btn) => {
      btn.addEventListener('click', () => showCreateTab(btn.getAttribute('data-create-tab')));
    });
    ['suchfeld_bild', 'flugplan_bild', 'create_suchfeld_bild', 'create_flugplan_bild'].forEach((id) => {
      const input = document.getElementById(id);
      if (input) input.addEventListener('input', () => renderStoredPictures(id));
    });

    els.createFieldModalBackdrop.addEventListener('mousedown', (ev) => {
      if (ev.target === els.createFieldModalBackdrop) ev.preventDefault();
    });
    document.addEventListener('keydown', (ev) => {
      if (ev.key === 'Escape' && els.createFieldModalBackdrop && els.createFieldModalBackdrop.classList.contains('show')) {
        closeCreateFieldModal();
      }
    });
    els.drawPolygonBtn.addEventListener('click', () => {
      if (currentPolygonFeature) {
        enablePolygonEdit();
      } else {
        startPolygonDraw();
      }
    });
    if (els.editPathBtn) {
      els.editPathBtn.addEventListener('click', enablePathEditMode);
    }
    if (els.hideContextHelpBtn) {
      els.hideContextHelpBtn.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        if (els.showBoxContextHelp) els.showBoxContextHelp.checked = false;
        applyBoxVisibilityStateFromSetup();
        if (window.RkrkLayout && typeof window.RkrkLayout.applyBoxes === 'function') {
          window.RkrkLayout.applyBoxes();
        }
      });
    }
    function revealEditorBox(checkboxEl, boxEl, focusEl) {
      if (checkboxEl) checkboxEl.checked = true;
      applyBoxVisibilityStateFromSetup();
      if (boxEl) {
        if ('open' in boxEl) boxEl.open = true;
        boxEl.style.display = '';
      }
      if (focusEl && typeof focusEl.focus === 'function') {
        try { focusEl.focus(); } catch (_) { /* noop */ }
      }
    }
    if (els.openMissionBtn) {
      els.openMissionBtn.addEventListener('click', () => {
        if (rightDrawerMode === 'mission' && isRightDrawerOpen()) {
          closeRightDrawer();
          return;
        }
        openMissionDrawer();
      });
    }
    if (els.openPointParamsBtn) {
      els.openPointParamsBtn.addEventListener('click', () => {
        if (rightDrawerMode === 'waypoint' && isRightDrawerOpen()) {
          closeRightDrawer();
          return;
        }
        openWaypointDrawer(selectedWaypointIndex1 || 1);
      });
    }
    if (els.editorRightDrawerCloseBtn) {
      els.editorRightDrawerCloseBtn.addEventListener('click', closeRightDrawer);
    }
    document.addEventListener('keydown', (ev) => {
      if (ev.key === 'Escape' && isRightDrawerOpen()) {
        closeRightDrawer();
      }
    });
    if (els.wpTabPrev) els.wpTabPrev.addEventListener('click', () => {
      const path = currentWaypointPath();
      if (path.length < 2) return;
      commitWaypointFormToState();
      const n = selectedWaypointIndex1 <= 1 ? path.length : selectedWaypointIndex1 - 1;
      fillWaypointForm(n, true);
      showWaypointTab('single');
    });
    if (els.wpTabNext) els.wpTabNext.addEventListener('click', () => {
      const path = currentWaypointPath();
      if (path.length < 2) return;
      commitWaypointFormToState();
      const n = selectedWaypointIndex1 >= path.length ? 1 : selectedWaypointIndex1 + 1;
      fillWaypointForm(n, true);
      showWaypointTab('single');
    });
    if (els.wpTabWaypoint) els.wpTabWaypoint.addEventListener('click', () => showWaypointTab('single'));
    if (els.wpTabAll) els.wpTabAll.addEventListener('click', () => {
      commitWaypointFormToState();
      showWaypointTab('all');
    });
    if (els.wpSaveBtn) els.wpSaveBtn.addEventListener('click', () => {
      commitWaypointFormToState();
      fillWaypointForm(selectedWaypointIndex1, true);
      refreshFlightPathLayers();
      renderPlanWpChips();
      showStatus('Wegpunkt P' + selectedWaypointIndex1 + ' gespeichert.');
    });
    if (els.wpCancelBtn) els.wpCancelBtn.addEventListener('click', () => {
      applyWaypointSnapshot();
      if (document.getElementById('planPointPane')) {
        fillWaypointForm(selectedWaypointIndex1, true);
        renderPlanWpChips();
        showStatus('Wegpunkt P' + selectedWaypointIndex1 + ' zurückgesetzt.');
        return;
      }
      closeRightDrawer();
    });
    if (els.wpDeleteBtn) els.wpDeleteBtn.addEventListener('click', deleteSelectedWaypoint);
    const wpAltMinus = document.getElementById('wpAltMinus');
    const wpAltPlus = document.getElementById('wpAltPlus');
    if (wpAltMinus) wpAltMinus.addEventListener('click', () => nudgeWaypointAltitude(-5));
    if (wpAltPlus) wpAltPlus.addEventListener('click', () => nudgeWaypointAltitude(5));
    const wpLive = [
      els.wpUseMissionHeight, els.wpAltitude, els.wpAltitudeRange,
      els.wpUseMissionSpeed, els.wpSpeed, els.wpSpeedRange,
      els.wpUseMissionWaypointType, els.wpWaypointType, els.wpTurnDistance, els.wpTurnDistanceRange,
      els.wpUseMissionYaw, els.wpHeadingMode, els.wpAlignment,
      els.wpUseMissionYaw, els.wpUseMissionPathMode, els.wpUseMissionDirectionMode, els.wpHeadingMode, els.wpAlignment,
      els.wpDirection, els.wpDirectionRange,
      els.wpLat, els.wpLng,
    ];
    wpLive.forEach((el) => {
      if (!el) return;
      el.addEventListener('input', () => {
        if (waypointFormFilling) return;
        if (el === els.wpAltitudeRange) syncWpNumberRange(els.wpAltitude, els.wpAltitudeRange, 'range');
        if (el === els.wpAltitude) {
          const raw = String(el.value || '');
          const n = Number(raw);
          if (raw.trim() !== '' && Number.isFinite(n) && !/[.,]$/.test(raw)) {
            const rounded = Math.round(n);
            if (String(rounded) !== raw) el.value = String(rounded);
          }
          syncWpNumberRange(els.wpAltitude, els.wpAltitudeRange, 'number');
        }
        if (el === els.wpSpeedRange) syncWpNumberRange(els.wpSpeed, els.wpSpeedRange, 'range');
        if (el === els.wpSpeed) syncWpNumberRange(els.wpSpeed, els.wpSpeedRange, 'number');
        if (el === els.wpTurnDistanceRange) syncWpNumberRange(els.wpTurnDistance, els.wpTurnDistanceRange, 'range');
        if (el === els.wpTurnDistance) syncWpNumberRange(els.wpTurnDistance, els.wpTurnDistanceRange, 'number');
        if (el === els.wpDirectionRange || el === els.wpDirection) {
          const src = el === els.wpDirectionRange ? els.wpDirectionRange : els.wpDirection;
          const raw = String(src && src.value || '');
          const n = Number(raw);
          if (raw.trim() !== '' && raw !== '-' && Number.isFinite(n) && !/[.,]$/.test(raw)) {
            const clamped = Math.max(-180, Math.min(180, Math.round(n)));
            if (els.wpDirection) els.wpDirection.value = String(clamped);
            if (els.wpDirectionRange) els.wpDirectionRange.value = String(clamped);
          } else {
            syncWpNumberRange(els.wpDirection, els.wpDirectionRange, el === els.wpDirectionRange ? 'range' : 'number');
          }
        }
        const modeBtn = document.getElementById('planModeToggle');
        const expert = !!(modeBtn && modeBtn.classList.contains('is-expert'));
        if (!expert && (el === els.wpAltitude || el === els.wpAltitudeRange) && els.wpUseMissionHeight) {
          els.wpUseMissionHeight.checked = false;
        }
        applyWaypointFormEnableState();
        commitWaypointFormToState();
        if (!planCheckIsSimple() && editorHasLocalWaypointFlags()) setPlanExpertMode(true);
        renderPlanWpChips();
        if (el === els.wpAltitude || el === els.wpAltitudeRange || el === els.wpLat || el === els.wpLng) {
          updateWaypointAglHint(
            Number(els.wpLng && els.wpLng.value),
            Number(els.wpLat && els.wpLat.value),
            Number(els.wpAltitude && els.wpAltitude.value)
          );
        }
      });
      el.addEventListener('change', () => {
        if (waypointFormFilling) return;
        applyWaypointFormEnableState();
        commitWaypointFormToState();
        if (!planCheckIsSimple() && editorHasLocalWaypointFlags()) setPlanExpertMode(true);
        renderPlanWpChips();
      });
    });
    if (els.msWaypointType) els.msWaypointType.addEventListener('change', syncTurnDistanceVisibility);
    syncTurnDistanceVisibility();
    if (window.RkrkHelp) {
      RkrkHelp.mount({
        page: 'editor',
        getView: currentHelpViewId,
        trigger: els.mapHelpBtn,
      });
    }
    els.pickStartBtn.addEventListener('click', startPickStart);
    els.findPlaceBtn.addEventListener('click', async () => {
      try {
        await findPlaceOnMap();
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    els.undoBtn.addEventListener('click', undoEditStep);
    if (els.showManualPath) {
      els.showManualPath.addEventListener('change', () => {
        enforceCoverageDisplayConstraints();
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showFieldPolygon) {
      els.showFieldPolygon.addEventListener('change', () => {
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showAlgorithmPath) {
      els.showAlgorithmPath.addEventListener('change', () => {
        enforceCoverageDisplayConstraints();
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showCoverageCircles) {
      els.showCoverageCircles.addEventListener('change', () => {
        enforceCoverageDisplayConstraints();
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    document.querySelectorAll('input[name="coverageWindowMode"]').forEach((el) => {
      el.addEventListener('change', () => {
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    });
    if (els.showUncoveredAreas) {
      els.showUncoveredAreas.addEventListener('change', () => {
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.forceCoverageFix) {
      els.forceCoverageFix.addEventListener('change', () => {
        saveEditorLastSettings();
        recomputePathFromFlightVorgaben(true).catch((e) => {
          showStatus(String(e.message || e), false);
        });
      });
    }
    if (els.showOverlapAreas) {
      els.showOverlapAreas.addEventListener('change', () => {
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showBasePatterns) {
      els.showBasePatterns.addEventListener('change', () => {
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showSectionDirections) {
      els.showSectionDirections.addEventListener('change', () => {
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showCorrectionPoints) {
      els.showCorrectionPoints.addEventListener('change', () => {
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showTurnCurves) {
      els.showTurnCurves.addEventListener('change', () => {
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    function stepMapLabelScale(delta) {
      if (!window.RkrkField || typeof RkrkField.setMapLabelScale !== 'function') return;
      const cur = typeof RkrkField.mapLabelScale === 'function' ? RkrkField.mapLabelScale() : 1;
      RkrkField.setMapLabelScale(cur + delta);
      if (manualPathSource) manualPathSource.changed();
      if (computedPathSource) computedPathSource.changed();
      saveEditorLastSettings();
    }
    const mapLabelSmaller = document.getElementById('mapLabelSmaller');
    const mapLabelLarger = document.getElementById('mapLabelLarger');
    if (mapLabelSmaller) mapLabelSmaller.addEventListener('click', () => stepMapLabelScale(-0.2));
    if (mapLabelLarger) mapLabelLarger.addEventListener('click', () => stepMapLabelScale(0.2));
    if (els.showControlPoints) {
      els.showControlPoints.addEventListener('change', () => {
        syncPlanHeightsFromMap();
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showAglLimit) {
      els.showAglLimit.addEventListener('change', () => {
        syncPlanHeightsFromMap();
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showFieldControlPoints) {
      els.showFieldControlPoints.addEventListener('change', () => {
        syncPlanHeightsFromMap();
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.useFieldReferenceAgl) {
      els.useFieldReferenceAgl.addEventListener('change', () => {
        refreshPolygonVertexLabels();
        if (startSource) startSource.changed();
        if (vertexSource) vertexSource.changed();
        scheduleFlightPathRecompute();
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.computeMode) {
      els.computeMode.addEventListener('change', () => {
        saveEditorLastSettings();
        recomputePathFromFlightVorgaben(true).catch((e) => {
          showStatus(String(e.message || e), false);
        });
      });
    }
    els.computePathBtn.addEventListener('click', async () => {
      try {
        await recomputePathFromFlightVorgaben(true, true);
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    els.newFieldQuickBtn.addEventListener('click', () => {
      startNewFieldFromScratch().catch((e) => showStatus(String(e.message || e), false));
    });
    els.exportUavJsonBtn.addEventListener('click', async () => {
      if (!window.RKRK_KAT) {
        try { downloadLocalField('json'); } catch (e) { showStatus(String(e.message || e), false); }
        return;
      }
      try {
        if (!ensurePlanGis(true)) return;
        const data = await callMissionApi('export_uav_json');
        downloadTextFile(data.filename || 'uav_mission.json', JSON.stringify(data.payload || {}, null, 2), 'application/json');
        const hint = data.stored_path ? ` Gespeichert: ${data.stored_path}` : '';
        showStatus(`UAV JSON exportiert.${hint}`);
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    els.exportDjiKmzBtn.addEventListener('click', async () => {
      if (!window.RKRK_KAT) {
        try { downloadLocalField('kml'); } catch (e) { showStatus(String(e.message || e), false); }
        return;
      }
      try {
        if (!ensurePlanGis(true)) return;
        const startPts = parsePairs(els.startplatz && els.startplatz.value);
        const polyPts = parsePairs(els.flaechendaten && els.flaechendaten.value);
        if (startPts.length < 1 || polyPts.length < 3) {
          throw new Error('KMZ-Export braucht Startplatz und Feldgrenze.');
        }
        let path = parseTriplets(els.flugweg && els.flugweg.value);
        if (path.length < 2 && currentComputedPath.length >= 2) {
          els.flugweg.value = formatTriplets(currentComputedPath);
        } else if (path.length < 2 && currentManualPath.length >= 2) {
          els.flugweg.value = formatTriplets(currentManualPath);
        }
        if (parseTriplets(els.flugweg && els.flugweg.value).length < 2) {
          showStatus('Kein Flugweg im Formular — berechne vor KMZ-Export...');
          await recomputePathFromFlightVorgaben(false);
        }
        const data = await callMissionApi('export_dji_kmz');
        if (!data.kmz_base64) {
          throw new Error('KMZ wurde nicht als Datei geliefert.');
        }
        downloadBase64File(data.filename || 'mission.kmz', data.kmz_base64);
        let kmlNote = '';
        if (editorDebugOn() && data.template_kml) {
          const kmlName = String(data.filename || 'mission.kmz').replace(/\.kmz$/i, '.kml');
          setTimeout(() => {
            downloadTextFile(kmlName, data.template_kml, 'application/vnd.google-earth.kml+xml');
          }, 250);
          kmlNote = ' Dazu ' + kmlName + '.';
        }
        const hint = data.stored_path ? ` Gespeichert: ${data.stored_path}` : '';
        const drone = data.wpml && data.wpml.drone_key ? ` Drohne ${data.wpml.drone_key}.` : '';
        showStatus(`DJI Pilot 2 KMZ erstellt.${drone}${hint}${kmlNote}`);
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    els.clearPolygonBtn.addEventListener('click', () => {
      ensureMap();
      stopAllDrawModes();
      pushHistory('Vor Feld-Loeschung');
      polygonSource.clear();
      startSource.clear();
      if (findPlaceSource) findPlaceSource.clear();
      manualPathSource.clear();
      computedPathSource.clear();
      if (controlPointSource) controlPointSource.clear();
      currentPolygonFeature = null;
      currentStartFeature = null;
      currentManualPath = [];
      originalManualPath = [];
      currentComputedPath = [];
      currentControlPointsPath = [];
      currentControlPointsField = [];
      syncFormFromMap();
      if (els.flugweg) els.flugweg.value = '';
      refreshFlightPathLayers();
      pushHistory('Feld, Startpunkt und Flugpfad geloescht');
      showStatus('Feld, Startpunkt und Flugpfad entfernt.');
    });
    els.saveBtn.addEventListener('click', async () => {
      try {
        if (!formData().id) {
          showStatus('Kein bestehendes Feld geladen. Speichern legt ein neues Feld an, Daten folgen danach.', false);
          return;
        }
        await save('update');
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    els.createBtn.addEventListener('click', () => {
      openCreateFieldModal().catch((e) => showStatus(String(e.message || e), false));
    });

    (async function init() {
      try {
        installCatalogSwitch();
        ensureMap();
        try {
          await loadDroneProfiles();
        } catch (e) {
          showStatus(`Drohnenprofile konnten nicht geladen werden: ${String(e.message || e)}`, false);
        }
        try {
          await loadDjiInternalSpec();
        } catch (e) {
          showStatus(`DJI-Spezifikation konnte nicht geladen werden: ${String(e.message || e)}`, false);
        }
        await loadCatalogChoices();
        restoreEditorLastSettings();
        settleEasyMissionDefaults();
        syncFlightDirectionControls('number');
        syncOverlapControls('number');
        syncEdgeFactorControls('number');
        applyKmzParamsUiState();
        enforceCoverageDisplayConstraints();
        markDisabledDisplayOptions();
        setContextHelp('default');
        if (window.RkrkHelp) RkrkHelp.setView();
        showLayerControlOnce();
        updateSaveButtonState();
        pushHistory('Initial');
        window.addEventListener('beforeunload', writeNewFieldDraftNow);
        if (window.RKRK_KAT) {
          await loadList();
          if (!editorHasCatalog()) {
            await startNewFieldFromScratch();
          } else {
          const draft = readNewFieldDraft();
          if (draft) {
            restoreNewFieldDraft(draft);
          } else if (allFields.length) {
            const remembered = String(loadFloatingUiState().lastFieldId || '');
            const known = allFields.find((f) => String(f.id) === remembered);
            await loadField(known ? known.id : allFields[0].id);
          } else {
            clearForm();
          }
          }
        } else {
          const draft = readNewFieldDraft();
          if (draft) restoreNewFieldDraft(draft);
          else {
            clearForm();
            await startNewFieldFromScratch();
          }
        }
        persistUiStateNow();
      } catch (e) {
        showStatus(`Init fehlgeschlagen: ${String(e.message || e)}`, false);
      }
    })();
  </script>
</body>
</html>


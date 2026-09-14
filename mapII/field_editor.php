<?php
require_once __DIR__ . '/mapll_env.php';
$mapllBrand = mapll_favicon_brand();
?><!doctype html>
<html <?php echo mapll_html_open_attrs(); ?>>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars(($mapllBrand['label'] ?? 'RKRK') . ' Feld-Editor', ENT_QUOTES, 'UTF-8'); ?></title>
  <?php echo mapll_favicon_link_tags($mapllBrand); ?>
  <?php echo mapll_theme_css_link(); ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.5.2/ol.css" />
  <link rel="stylesheet" href="rkrk_help.css" />
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
    textarea { min-height: 70px; resize: vertical; font-family: Consolas, monospace; font-size: 12px; }
    button { cursor: pointer; background: var(--brand-dark, #48673A); color: #fff; border: 0; }
    button:hover { background: var(--brand-hover, #3d5632); }
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
      max-width: 165px;
      padding: 5px 7px;
      font-size: 12px;
      min-height: 28px;
      line-height: 1.1;
    }
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
    .field-item-main { color: #0f172a; }
    .field-item-meta { margin-top: 2px; font-size: 11px; color: #64748b; font-weight: normal; white-space: normal; }
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
    .swiss-overlay-box {
      position: absolute;
      top: 72px;
      right: 8px;
      z-index: 21;
      width: 320px;
      max-width: calc(100vw - 16px);
      font-size: 12px;
      color: #1b3552;
      background: rgba(255, 255, 255, 0.97);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }
    .swiss-base-row {
      margin: 0 8px 8px;
      display: grid;
      grid-template-columns: 48px minmax(0, 1fr);
      gap: 8px;
      align-items: center;
      font-size: 12px;
      color: #2e4158;
    }
    .swiss-base-row select {
      width: 100%;
      min-height: 28px;
      border-radius: 5px;
      border: 1px solid #cfd6de;
      padding: 4px 6px;
      font-size: 12px;
      box-sizing: border-box;
    }
    .display-control-box {
      position: absolute;
      right: 14px;
      top: 14px;
      z-index: 20;
      background: rgba(255,255,255,0.95);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 8px 10px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      font-size: 12px;
      color: #222;
      min-width: 230px;
    }
    .display-control-box .title {
      font-weight: bold;
      margin-bottom: 6px;
    }
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
      flex-wrap: wrap;
      align-items: center;
      gap: 6px 8px;
      min-height: 22px;
    }
    .swiss-overlay-item label.rkrk-sel {
      margin: 0;
      flex: 1 1 auto;
    }
    .swiss-overlay-item .opacity-wrap {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      color: #46617d;
      font-size: 11px;
    }
    .swiss-overlay-item input[type="range"] {
      width: 84px;
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
    #rkrkMapScaleBar {
      display: block;
      position: absolute;
      right: 10px;
      bottom: 10px;
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
    .wp-slider-row input[type="range"] { width: 100%; padding: 0; }
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
      overflow: auto;
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
      <h1 class="rkr-brand-header"><?php echo mapll_favicon_mark_html($mapllBrand); ?>Feld-Editor</h1>
      <div class="small">Bestehende Felder bearbeiten oder neue Felder in `RKRKFelder.xml` anlegen.</div>
      <div class="toplinks rkrk-sels rkrk-reg-pills">
        <button id="backBtn" type="button" class="rkrk-sel rkrk-reg-pill" title="Zur Analyse zurueck">Zurueck</button>
        <a href="analyse_ui.php" target="_blank" rel="noopener" class="rkrk-sel rkrk-reg-pill" title="Analyse im neuen Fenster">Analyse</a>
        <a href="rkrk_help.php" data-rkrk-help class="rkrk-sel rkrk-reg-pill" title="Hilfe zur aktuellen Sicht (F1)">Hilfe</a>
        <a href="requirements.php" target="_blank" rel="noopener" class="rkrk-sel rkrk-reg-pill" title="Requirements anzeigen">Requirements</a>
        <a href="mapping_matrix.php" target="_blank" rel="noopener" class="rkrk-sel rkrk-reg-pill" title="Interface-Mapping anzeigen">Mapping</a>
      </div>
      <div class="quick-slot-box">
        <div class="quick-slot-title">Schnellzugriff Felder</div>
        <div class="quick-slot-help">Klick: laden · Alt+Klick: aktuelles Feld speichern</div>
        <div id="quickSlotGridEditor" class="quick-slot-grid"></div>
      </div>
      <div class="card">
        <div class="row">
          <label>Feld suchen</label>
          <input id="searchInput" placeholder="Feld-ID oder Name..." />
        </div>
        <div id="fieldList" class="field-list"></div>
      </div>
    </div>
    <div class="map-wrap">
      <div id="editorMap"></div>
      <div id="dabsPopup" class="rkrk-dabs-popup" style="display:none">
        <button type="button" class="pop-close" id="dabsPopupClose" aria-label="Schliessen">×</button>
        <div id="dabsPopupBody"></div>
      </div>
      <div id="rkrkMapScaleBar" aria-label="Kartenmassstab" title="Kartenmassstab">
        <span class="rkrk-scale-ratio">Massstab</span>
        <div class="rkrk-scale-track" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <div class="rkrk-scale-caps"><span>0</span><span class="rkrk-scale-dist"></span></div>
      </div>
      <button id="findPlaceBtn" class="map-find-btn" title="Ort auf Karte suchen" type="button">🔎</button>
      <button id="mapHelpBtn" class="map-help-btn" data-rkrk-help title="Hilfe zur aktuellen Sicht (F1 / ?)" type="button" aria-expanded="false">?</button>
      <button id="mapSetupBtn" class="map-setup-btn" title="Setup: Boxen ein-/ausblenden" type="button">⚙</button>
      <div id="mapSetupPanel" class="map-setup-panel rkrk-sels rkrk-reg-pills">
        <div class="title">Boxen anzeigen</div>
        <label class="rkrk-sel rkrk-reg-pill" title="Haupt-Bedienmenue oben links"><input id="showBoxMapMenu" type="checkbox" checked data-rkrk-box-for="menu" /> Bedienmenue</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Anzeigekontrolle fuer Pfade, Abdeckung und Kontrollpunkte"><input id="showBoxDisplayControl" type="checkbox" checked data-rkrk-box-for="display" /> Anzeigekontrolle</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Kontext-Hilfe zur aktuellen Bearbeitung"><input id="showBoxContextHelp" type="checkbox" data-rkrk-box-for="context" /> Kontext-Hilfe</label>
        <label class="rkrk-sel rkrk-reg-pill" title="SwissTopo-Overlay-Konfiguration"><input id="showBoxSwissOverlay" type="checkbox" data-rkrk-box-for="overlays" /> SwissTopo-Overlays</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Flugvorgaben werden von rechts eingeblendet (Button Missionsparameter)" style="display:none;"><input id="showBoxMissionSettings" type="checkbox" data-rkrk-box-for="mission" /> Flugvorgaben</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Berechnete Feldcharakteristik"><input id="showBoxFieldCharacteristics" type="checkbox" checked data-rkrk-box-for="characteristics" /> Feldcharakteristik</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Felddatenbereich unten"><input id="showBoxFormDrawer" type="checkbox" data-rkrk-box-for="form" /> Felddaten-Box</label>
      </div>
      <div id="displayControlBox" class="display-control-box" data-rkrk-box="display" data-rkrk-box-label="Anzeigekontrolle">
        <div class="title">Anzeigekontrolle</div>
        <div class="group-title">Flugpfade</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill" title="Feldgrenze ein-/ausblenden"><input id="showFieldPolygon" type="checkbox" checked /> Feld anzeigen</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showManualPath" type="checkbox" checked /> Flugplan (orignal)</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showAlgorithmPath" type="checkbox" checked /> Flugplan (neu)</label>
        </div>
        <div class="control-divider"></div>
        <div class="group-title">Abdeckung</div>
        <div class="group-note">Abdeckung ist nur aktiv, wenn mindestens ein Flugpfad sichtbar ist.</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="showCoverageCircles" type="checkbox" /> <span id="coverageSourceLabel">Abdeckung (neu)</span></label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showOverlapAreas" type="checkbox" checked /> Ueberlappung anzeigen</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showUncoveredAreas" type="checkbox" checked /> Nicht abgedeckte Flaechen</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Wenn aktiv, wird bei der Pfadberechnung die Korrektur nicht abgedeckter Flaechen per Wegpunkt-Erweiterung/-Einfügung forciert.">
          <input id="forceCoverageFix" type="checkbox" /> Offene Flaechen via Wegpunkte fuellen
        </label>
        </div>
        <div class="control-divider"></div>
        <div class="group-title">Kontrollpunkte</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="showCorrectionPoints" type="checkbox" checked /> Korrekturpunkte markieren</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showTurnCurves" type="checkbox" checked /> Wendekurven anzeigen</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showControlPoints" type="checkbox" checked /> In Flugbahn</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showFieldControlPoints" type="checkbox" checked /> im Feld</label>
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
      <div class="map-toolbar">
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
      <div class="map-help">
        Bedienmenue in der Reihenfolge der Felderstellung.
        <br><span id="wsSourceInfo">S/W Quelle: -</span>
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
      <details id="swissOverlayBox" class="swiss-overlay-box" open data-rkrk-box="overlays" data-rkrk-box-label="🗺 SwissTopo-Overlays" data-rkrk-box-requires="#baseMapMode:swisstopo,swisstopo-grau,swissimage">
        <summary>Swisstopo & Overlays</summary>
        <div class="swiss-base-row">
          <label for="baseMapMode">Karte</label>
          <select id="baseMapMode" class="compact-select" title="🗺 Kartenbasis auswählen">
            <option value="swisstopo" selected>Swisstopo Farbe</option>
            <option value="swisstopo-grau">SwissTopo schwarz-weiss</option>
            <option value="swissimage">Swissimage</option>
            <option value="osm">OpenStreetMap</option>
          </select>
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
            <label class="rkrk-sel rkrk-reg-pill" title="Schutzgebiete fuer Jagd"><input id="layerJagdbann" type="checkbox" /> Jagdbanngebiete</label>
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
            <label class="rkrk-sel rkrk-reg-pill" title="DABS bis 120 m AGL"><input id="layerDabs" type="checkbox" /> DABS bis 120 m AGL</label>
            <span class="opacity-wrap" title="Durchsicht dieses Layers"><input id="layerDabsOpacity" type="range" min="0" max="100" step="5" value="55" /><span id="layerDabsOpacityLabel">55%</span></span>
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
              </div>
              <div>
                <label>Flughöhe (m)</label>
                <input id="msAltitude" type="number" step="1" value="60" />
              </div>
              <div>
                <label>Flugrichtung (°, -1=auto)</label>
                <div style="display:grid; grid-template-columns: 1fr 74px; gap: 6px; align-items:center;">
                  <input id="msFlightDirectionRange" type="range" min="-1" max="259" step="1" value="-1" title="Flugrichtung per Schieber einstellen (-1 = auto)" />
                  <input id="msFlightDirection" type="number" step="1" min="-1" max="259" value="-1" title="Flugrichtung numerisch eingeben (-1 = auto)" />
                </div>
              </div>
              <div>
                <label>Geschwindigkeit (m/s)</label>
                <input id="msSpeed" type="number" step="0.1" value="5" />
              </div>
              <div>
                <label>Überlappung (%)</label>
                <div style="display:grid; grid-template-columns: 1fr 74px; gap: 6px; align-items:center;">
                  <input id="msOverlapPctRange" type="range" min="0" max="95" step="5" value="15" title="Überlappung per Schieber einstellen" />
                  <input id="msOverlapPct" type="number" step="5" min="0" max="95" value="15" title="Überlappung numerisch eingeben" />
                </div>
              </div>
              <div>
                <label>Max. Randfaktor (%)</label>
                <div style="display:grid; grid-template-columns: 1fr 74px; gap: 6px; align-items:center;">
                  <input id="msEdgeFactorPctRange" type="range" min="0" max="100" step="5" value="30" title="Max. Randfaktor per Schieber einstellen" />
                  <input id="msEdgeFactorPct" type="number" step="5" min="0" max="100" value="30" title="Max. Randfaktor numerisch eingeben" />
                </div>
              </div>
              <div>
                <label><input id="msStartSpeedActive" type="checkbox" style="width:auto;" /> Start Geschwindigkeit aktiv</label>
              </div>
              <div>
                <label>Start Geschwindigkeit</label>
                <input id="msStartSpeed" type="number" step="0.1" value="5" />
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
                  <option value="Manuell" selected>Manuell</option>
                  <option value="Richtung zum naechsten Wegpunkt">Richtung zum naechsten Wegpunkt</option>
                  <option value="Schenkachse sperren">Schenkachse sperren</option>
                  <option value="Fokus POI">Fokus POI</option>
                  <option value="Definiert">Definiert</option>
                </select>
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
              <div>
                <label>Standard-Wendedistanz (m)</label>
                <input id="msTurnDistance" type="number" step="0.1" value="5" />
              </div>
              <div>
                <label>Sichere Startflughöhe</label>
                <input id="msSafeStartAltitude" type="number" step="0.1" value="20" />
              </div>
              <div>
                <label><input id="msDirectToStart" type="checkbox" style="width:auto;" /> Direkt zur Startposition fliegen</label>
              </div>
              <div>
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
                <div class="full">
                  <label class="wp-check"><input id="wpAutoHeight" type="checkbox" checked /> Auto Höhenanpassung</label>
                </div>
                <div class="full">
                  <label class="wp-req">Höhe</label>
                  <div class="wp-slider-row">
                    <input id="wpAltitude" type="number" step="0.1" min="1" max="500" value="60" />
                    <input id="wpAltitudeRange" type="range" min="1" max="200" step="1" value="60" />
                    <span class="wp-unit">m</span>
                  </div>
                  <div id="wpMissionAltHint" class="wp-hint">Missions-Höhe: 60m</div>
                  <div id="wpAglHint" class="wp-hint">Flughöhe zum Boden: -</div>
                </div>
                <div class="full">
                  <label class="wp-check"><input id="wpUseMissionSpeed" type="checkbox" checked /> Verwende Missions-Geschwindigkeit</label>
                </div>
                <div class="full">
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
                <div class="full">
                  <label>Wegpunkt-Typ</label>
                  <select id="wpWaypointType">
                    <option value="Koordinierte Drehung, Ueberspringt den Wegpunkt" selected>Koordinierte Drehung, Ueberspringt den Wegpunkt</option>
                    <option value="Gerade Flugroute. Fluggeraet stoppt">Gerade Flugroute. Fluggeraet stoppt</option>
                    <option value="Gekruemmte Route. Fluggeraet setzt fort">Gekruemmte Route. Fluggeraet setzt fort</option>
                    <option value="Gekruemmte Route. Fluggeraet stoppt">Gekruemmte Route. Fluggeraet stoppt</option>
                    <option value="Wendet vor dem Wegpunkt. Fliegt durch">Wendet vor dem Wegpunkt. Fliegt durch</option>
                  </select>
                </div>
                <div class="full">
                  <label>Wendedistanz</label>
                  <div class="wp-slider-row">
                    <input id="wpTurnDistance" type="number" step="0.1" min="0" max="40" value="5" />
                    <input id="wpTurnDistanceRange" type="range" min="0" max="40" step="0.1" value="5" />
                    <span class="wp-unit">m</span>
                  </div>
                  <div id="wpMissionTurnHint" class="wp-hint">Standard-Wendedistanz: 5.00m</div>
                </div>
                <div class="full">
                  <label class="wp-check"><input id="wpUseMissionYaw" type="checkbox" checked /> Route folgen</label>
                </div>
                <div>
                  <label>Gierrichtung des Fluggeräts</label>
                  <select id="wpHeadingMode">
                    <option value="Manuell" selected>Manuell</option>
                    <option value="Richtung zum naechsten Wegpunkt">Richtung zum naechsten Wegpunkt</option>
                    <option value="Schenkachse sperren">Schenkachse sperren</option>
                    <option value="Fokus POI">Fokus POI</option>
                    <option value="Definiert">Definiert</option>
                  </select>
                </div>
                <div>
                  <label>Ausrichtung des Fluggeräts</label>
                  <select id="wpAlignment">
                    <option value="Kuerzester Drehweg" selected>Kürzester Drehweg</option>
                    <option value="Uhrzeigersinn">Uhrzeigersinn</option>
                    <option value="Gegen den Uhrzeigersinn">Gegen den Uhrzeigersinn</option>
                  </select>
                </div>
                <div id="wpDirectionWrap" class="full">
                  <label class="wp-req">Richtungs-Winkel</label>
                  <div class="wp-slider-row">
                    <input id="wpDirection" type="number" step="0.1" min="-180" max="180" value="0" />
                    <input id="wpDirectionRange" type="range" min="-180" max="180" step="0.1" value="0" />
                    <span class="wp-unit">°</span>
                  </div>
                  <div class="wp-hint">Nur bei Gierrichtung «Schenkachse sperren» (waypointHeadingAngle).</div>
                </div>
                <div id="wpPitchWrap" class="full">
                  <label class="wp-req">Neigungswinkel</label>
                  <div class="wp-slider-row">
                    <input id="wpPitch" type="number" step="1" min="-90" max="30" value="-90" />
                    <input id="wpPitchRange" type="range" min="-90" max="30" step="1" value="-90" />
                    <span class="wp-unit">°</span>
                  </div>
                  <div class="wp-hint">Wirkt, wenn Gimbalsteuerung «Jeden Wegpunkt festlegen» ist.</div>
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
            <button type="button" id="wpSaveBtn">Speichern</button>
            <button type="button" id="wpCancelBtn" class="secondary">Abbrechen</button>
            <button type="button" id="wpDeleteBtn" class="danger">Löschen</button>
          </div>
        </div>
      </aside>
      <details id="fieldCharacteristicsBox" class="field-characteristics-box" open data-rkrk-box="characteristics" data-rkrk-box-label="📈 Feldcharakteristik">
        <summary>Feldcharakteristik</summary>
        <div class="field-characteristics-grid">
          <span>Quelle</span><span id="fcSource" class="value">-</span>
          <span>Länge Flugpfad</span><span id="fcPathLength" class="value">-</span>
          <span>Höhendifferenz hoch</span><span id="fcClimbUp" class="value">-</span>
          <span>Höhendifferenz runter</span><span id="fcClimbDown" class="value">-</span>
          <span>Flugzeit</span><span id="fcFlightTime" class="value">-</span>
        </div>
      </details>
      <details id="formDrawerBox" class="editor-form-drawer" data-rkrk-box="form" data-rkrk-box-label="📝 Felddaten-Box">
        <summary>Felddaten bearbeiten (Interne XML-ID, Koordinaten, Speichern)</summary>
        <div class="editor-form-content">
          <div class="row">
            <label>Interne XML-ID (nur Anzeige)</label>
            <input id="id" readonly />
          </div>
          <div class="row">
            <label>Nummer (Number)</label>
            <input id="number" readonly />
          </div>
          <div class="row">
            <label>FeldID *</label>
            <input id="feld_id" />
          </div>
          <div class="row">
            <label>Feldname *</label>
            <input id="name" />
          </div>
          <div class="row">
            <label>JG</label>
            <input id="jg" />
          </div>
          <div class="row">
            <label>StdFlughoehe</label>
            <input id="std_flughoehe" />
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
            <label>Startplatz (lon, lat)</label>
            <textarea id="startplatz" placeholder="8.50, 47.26"></textarea>
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
            <button id="saveBtn" type="button" title="Aktuell geladenes Feld mit allen Aenderungen speichern">Aktuelles Feld speichern</button>
            <button id="createBtn" type="button" title="Dialog oeffnen, um ein neues Feld anzulegen">Als neues Feld anlegen</button>
            <button id="newBtn" class="secondary" type="button" title="Formular leeren fuer neue Eingabe">Leeres Formular</button>
          </div>
          <div id="status" class="status"></div>
        </div>
      </details>
    </div>
  </div>
  <div id="createFieldModalBackdrop" class="create-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="createFieldModalTitle">
    <div class="create-modal">
      <h2 id="createFieldModalTitle">Neues Feld erstellen</h2>
      <div class="small-note">Pflichtangaben koennen hier direkt erfasst oder angepasst werden.</div>
      <div class="create-grid">
        <div>
          <label>FeldID *</label>
          <input id="create_feld_id" />
        </div>
        <div>
          <label>Feldname *</label>
          <input id="create_name" />
        </div>
        <div>
          <label>JG</label>
          <input id="create_jg" />
        </div>
        <div>
          <label>Feldkatalognummer (Number)</label>
          <input id="create_number" />
        </div>
        <div>
          <label>StdFlughoehe</label>
          <input id="create_std_flughoehe" />
        </div>
        <div class="full">
          <label>Startplatz (lon, lat) *</label>
          <textarea id="create_startplatz" placeholder="8.50, 47.26"></textarea>
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
      <div class="create-actions">
        <button id="createModalCancelBtn" class="secondary" type="button" title="Neues Feld ohne Speichern verwerfen">Abbrechen</button>
        <button id="createModalSaveBtn" type="button" title="Neues Feld mit den Eingaben erstellen und speichern">Neues Feld speichern</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/ol@7.5.2/dist/ol.js"></script>
  <script src="rkrk_swiss_overlays.js?v=20260906a"></script>
  <script src="rkrk_help_catalog.js?v=20260912surface"></script>
  <script src="rkrk_help.js"></script>
  <script src="rkrk_layout.js"></script>
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
      msAltitudeMode: document.getElementById('msAltitudeMode'),
      saveBtn: document.getElementById('saveBtn'),
      createBtn: document.getElementById('createBtn'),
      newBtn: document.getElementById('newBtn'),
      status: document.getElementById('status'),
      createFieldModalBackdrop: document.getElementById('createFieldModalBackdrop'),
      create_feld_id: document.getElementById('create_feld_id'),
      create_name: document.getElementById('create_name'),
      create_jg: document.getElementById('create_jg'),
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
      wpAutoHeight: document.getElementById('wpAutoHeight'),
      wpAltitude: document.getElementById('wpAltitude'),
      wpAltitudeRange: document.getElementById('wpAltitudeRange'),
      wpMissionAltHint: document.getElementById('wpMissionAltHint'),
      wpAglHint: document.getElementById('wpAglHint'),
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
      showFieldControlPoints: document.getElementById('showFieldControlPoints'),
      useFieldReferenceAgl: document.getElementById('useFieldReferenceAgl'),
      computeMode: document.getElementById('computeMode'),
      swissOverlayBox: document.getElementById('swissOverlayBox'),
      fieldCharacteristicsBox: document.getElementById('fieldCharacteristicsBox'),
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
    let startModifyInteraction = null;
    let snapInteraction = null;
    let currentPolygonFeature = null;
    let currentStartFeature = null;
    let polygonSource = null;
    let startSource = null;
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
        label: 'Jagdbanngebiete',
        fixedLayerIds: ['ch.bafu.jagdbanngebiete'],
        searches: [{ text: 'Jagdbanngebiete', tokens: ['jagdbann'] }],
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
        label: 'DABS bis 120 m AGL',
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
    let currentControlPointsPath = [];
    let currentControlPointsField = [];
    let controlPointRefreshTimer = null;
    let controlPointRefreshToken = 0;
    let waypointOverrides = {};
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
    }

    function markFieldAsSaved() {
      savedFieldSnapshot = currentFieldSnapshot();
      updateSaveButtonState();
    }

    function currentHelpViewId() {
      if (drawInteraction) return 'editor.draw';
      if (startDrawInteraction) return 'editor.start';
      if (isPathEditModeActive) return 'editor.edit_path';
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
        default: '<strong>Moegliche Bearbeitungsfunktionen:</strong><br>- Neues Feld anlegen<br>- Feldrand zeichnen<br>- Startpunkt setzen<br>- Missionsparameter pruefen<br>- Feldpfad rechnen und anpassen<br>- Punktparameter setzen<br>- UAV/KMZ exportieren',
        draw: '<strong>Zeichnen aktiv</strong><br>- Linksklick setzt Feldpunkte<br>- Doppelklick beendet Feldrand<br>- Danach automatisch Bearbeitungsmodus',
        edit: '<strong>Feldrand bearbeiten</strong><br>- Feldpunkte ziehen/verschieben<br>- <strong>+</strong> zwischen Punkten: neuen Punkt einfuegen<br>- Doppelklick auf Punkt: Punkt loeschen<br>- Undo fuer letzte Aenderung',
        start: '<strong>Startpunkt aktiv</strong><br>Klick setzt den Start neu. Bestehenden Start ziehen. Danach wird der Flugpfad neu berechnet.',
        path: '<strong>Feldpfad anpassen</strong><br>- Wegpunkte ziehen<br>- <strong>+</strong> einfuegen, Doppelklick loeschen<br>- Klick auf Punkt: Parameter oeffnen',
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
      const hfovRad = (Number(hfovDeg) * Math.PI) / 180;
      const vfovRad = (Number(vfovDeg) * Math.PI) / 180;
      const dfovRad = (Number(dfovDeg) * Math.PI) / 180;
      const widthM = Number.isFinite(aglM) && aglM > 0 && Number.isFinite(hfovRad)
        ? (2 * aglM * Math.tan(hfovRad / 2))
        : NaN;
      const lengthM = Number.isFinite(aglM) && aglM > 0 && Number.isFinite(vfovRad)
        ? (2 * aglM * Math.tan(vfovRad / 2))
        : NaN;
      const cornerDiameterM = Number.isFinite(aglM) && aglM > 0 && Number.isFinite(dfovRad)
        ? (2 * aglM * Math.tan(dfovRad / 2))
        : NaN;
      const spacingM = Number.isFinite(widthM) && widthM > 0
        ? Math.max(0, (1 - overlap) * widthM)
        : NaN;
      const formulaW = Number(currentAnalyzeFormula && currentAnalyzeFormula.W);
      const formulaS = Number(currentAnalyzeFormula && currentAnalyzeFormula.S);
      const preferAnalyzeFormula = currentPathCalcSource === 'analyze';
      return {
        widthM: preferAnalyzeFormula && Number.isFinite(formulaW) && formulaW > 0 ? formulaW : widthM,
        lengthM,
        cornerDiameterM,
        spacingM: preferAnalyzeFormula && Number.isFinite(formulaS) && formulaS > 0 ? formulaS : spacingM,
      };
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

    function refreshCoverageGeometry() {
      if (!coverageSource) return;
      coverageSource.clear();
      if (overlapSource) overlapSource.clear();
      if (uncoveredSource) uncoveredSource.clear();
      const showCoverage = !!(els.showCoverageCircles && els.showCoverageCircles.checked);
      if (!showCoverage) return;
      const manualVisible = !!(els.showManualPath && els.showManualPath.checked);
      const algoVisible = !!(els.showAlgorithmPath && els.showAlgorithmPath.checked);
      const useManual = manualVisible && !algoVisible;
      const path = useManual
        ? (originalManualPath.length ? originalManualPath : currentManualPath)
        : currentComputedPath;
      const pathCoords = normalizeTripletPath(path).map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      if (pathCoords.length < 2) return;
      const metrics = currentCoverageMetrics();
      const widthM = Number(metrics.widthM);
      const lengthM = Number(metrics.lengthM);
      const cornerDiameterM = Number(metrics.cornerDiameterM);
      if (!Number.isFinite(widthM) || widthM <= 0 || !Number.isFinite(lengthM) || lengthM <= 0) return;
      const fillColor = useManual ? 'rgba(30,120,235,0.22)' : 'rgba(70,165,255,0.20)';
      const edgeColor = null;
      // Smooth flight area: one line feature with round joins/caps.
      const lineFeat = new ol.Feature({ geometry: new ol.geom.LineString(pathCoords) });
      lineFeat.set('coverageWidthM', widthM);
      lineFeat.set('coverageFillColor', fillColor);
      coverageSource.addFeature(lineFeat);

      // Start rectangle extension: +1/2 VFOV beyond start point.
      const startHeading = Math.atan2(
        pathCoords[1][1] - pathCoords[0][1],
        pathCoords[1][0] - pathCoords[0][0]
      );
      const startExtM = lengthM * 0.5;
      const startCenter = [
        pathCoords[0][0] - Math.cos(startHeading) * meterToMapUnitScaleAt(pathCoords[0]) * (startExtM * 0.5),
        pathCoords[0][1] - Math.sin(startHeading) * meterToMapUnitScaleAt(pathCoords[0]) * (startExtM * 0.5),
      ];
      const startRing = buildCoverageRectangleRing(startCenter, startHeading, widthM, startExtM);
      if (startRing) {
        const startFeat = new ol.Feature({ geometry: new ol.geom.Polygon([startRing]) });
        startFeat.setStyle(new ol.style.Style({
          fill: new ol.style.Fill({ color: fillColor }),
          stroke: edgeColor ? new ol.style.Stroke({ color: edgeColor, width: 0.5 }) : undefined,
        }));
        coverageSource.addFeature(startFeat);
      }

      // End rectangle extension: +1/2 VFOV beyond end point.
      const last = pathCoords.length - 1;
      const endHeading = Math.atan2(
        pathCoords[last][1] - pathCoords[last - 1][1],
        pathCoords[last][0] - pathCoords[last - 1][0]
      );
      const endExtM = lengthM * 0.5;
      const endCenter = [
        pathCoords[last][0] + Math.cos(endHeading) * meterToMapUnitScaleAt(pathCoords[last]) * (endExtM * 0.5),
        pathCoords[last][1] + Math.sin(endHeading) * meterToMapUnitScaleAt(pathCoords[last]) * (endExtM * 0.5),
      ];
      const endRing = buildCoverageRectangleRing(endCenter, endHeading, widthM, endExtM);
      if (endRing) {
        const endFeat = new ol.Feature({ geometry: new ol.geom.Polygon([endRing]) });
        endFeat.setStyle(new ol.style.Style({
          fill: new ol.style.Fill({ color: fillColor }),
          stroke: edgeColor ? new ol.style.Stroke({ color: edgeColor, width: 0.5 }) : undefined,
        }));
        coverageSource.addFeature(endFeat);
      }

      // Corner model: DFOV circle only at real turns (avoid blob-like artifacts on near-straight points).
      if (Number.isFinite(cornerDiameterM) && cornerDiameterM > 0) {
        for (let i = 1; i < last; i++) {
          const prev = pathCoords[i - 1];
          const curr = pathCoords[i];
          const next = pathCoords[i + 1];
          const inDx = curr[0] - prev[0];
          const inDy = curr[1] - prev[1];
          const outDx = next[0] - curr[0];
          const outDy = next[1] - curr[1];
          const inLen = Math.hypot(inDx, inDy);
          const outLen = Math.hypot(outDx, outDy);
          if (inLen <= 1e-9 || outLen <= 1e-9) continue;
          const inUx = inDx / inLen;
          const inUy = inDy / inLen;
          const outUx = outDx / outLen;
          const outUy = outDy / outLen;
          const dot = Math.max(-1, Math.min(1, inUx * outUx + inUy * outUy));
          const turnDeg = Math.acos(dot) * (180 / Math.PI);
          if (turnDeg < 18) continue;
          const ring = buildCoverageCircleRing(pathCoords[i], cornerDiameterM, 24);
          if (!ring) continue;
          const feat = new ol.Feature({ geometry: new ol.geom.Polygon([ring]) });
          feat.setStyle(new ol.style.Style({
            fill: new ol.style.Fill({ color: fillColor }),
            stroke: edgeColor ? new ol.style.Stroke({ color: edgeColor, width: 0.5 }) : undefined,
          }));
          coverageSource.addFeature(feat);
        }
      }
      refreshCoverageDiagnostics(pathCoords, widthM);
    }

    function refreshCoverageDiagnostics(pathCoords = null, widthM = NaN) {
      if (!uncoveredSource) return;
      uncoveredSource.clear();
      if (overlapSource) overlapSource.clear();
      const showOverlap = !!(els.showOverlapAreas && els.showOverlapAreas.checked);
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
      const pathForCoverage = Array.isArray(pathCoords) && pathCoords.length >= 2 ? pathCoords : [];
      const halfWidthM = Number.isFinite(widthM) && widthM > 0 ? widthM * 0.5 : NaN;
      if (!covPolys.length && (!pathForCoverage.length || !Number.isFinite(halfWidthM))) return;
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
          if (pathForCoverage.length && Number.isFinite(halfWidthM)) {
            // Count how many flightline segments cover this cell.
            for (let si = 0; si < pathForCoverage.length - 1; si++) {
              const d = mapDistancePointToSegment(p, pathForCoverage[si], pathForCoverage[si + 1]);
              const scale = meterToMapUnitScaleAt(p);
              if (d <= halfWidthM * scale) coverageCount++;
            }
          }
          // Extra polygons (start/end + corner DFOV) contribute one additional pass.
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
              fill: new ol.style.Fill({ color: 'rgba(220,30,30,0.22)' }),
              stroke: new ol.style.Stroke({ color: 'rgba(220,30,30,0.30)', width: 0.6 }),
            }));
            uncoveredSource.addFeature(feat);
          } else if (coverageCount >= 2 && showOverlap && overlapSource) {
            const feat = new ol.Feature({ geometry: new ol.geom.Polygon([rect]) });
            feat.setStyle(new ol.style.Style({
              fill: new ol.style.Fill({ color: 'rgba(120,195,255,0.24)' }),
              stroke: new ol.style.Stroke({ color: 'rgba(120,195,255,0.34)', width: 0.6 }),
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

    function applyBoxVisibilityStateFromSetup() {
      const showMenu = !!(els.showBoxMapMenu && els.showBoxMapMenu.checked);
      const showDisplay = !!(els.showBoxDisplayControl && els.showBoxDisplayControl.checked);
      const showContext = !!(els.showBoxContextHelp && els.showBoxContextHelp.checked);
      const showOverlayBySetup = !!(els.showBoxSwissOverlay && els.showBoxSwissOverlay.checked);
      const showMission = !!(els.showBoxMissionSettings && els.showBoxMissionSettings.checked);
      const showCharacteristics = !!(els.showBoxFieldCharacteristics && els.showBoxFieldCharacteristics.checked);
      const showForm = !!(els.showBoxFormDrawer && els.showBoxFormDrawer.checked);

      if (els.mapMenuBox) els.mapMenuBox.style.display = showMenu ? '' : 'none';
      if (els.displayControlBox) els.displayControlBox.style.display = showDisplay ? '' : 'none';
      if (els.mapQuickHelp) els.mapQuickHelp.style.display = showContext ? '' : 'none';
      if (els.fieldCharacteristicsBox) els.fieldCharacteristicsBox.style.display = showCharacteristics ? '' : 'none';
      if (els.formDrawerBox) els.formDrawerBox.style.display = showForm ? '' : 'none';

      const mapMode = String(els.baseMapMode && els.baseMapMode.value || 'swisstopo');
      const baseAllowsOverlays = (mapMode === 'swisstopo' || mapMode === 'swisstopo-grau' || mapMode === 'swissimage');
      const showOverlayFinal = showOverlayBySetup && baseAllowsOverlays;
      if (els.swissOverlayBox) els.swissOverlayBox.style.display = showOverlayFinal ? '' : 'none';

      saveFloatingUiState({
        showMapMenu: showMenu,
        showDisplayControl: showDisplay,
        contextHelpVisible: showContext,
        contextHelpVisibleV2: showContext,
        showSwissOverlay: showOverlayBySetup,
        showMissionSettings: showMission,
        showFieldCharacteristics: showCharacteristics,
        showFormDrawer: showForm,
      });
      if (window.RkrkLayout && typeof window.RkrkLayout.applyBoxes === 'function') {
        window.RkrkLayout.applyBoxes();
      }
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
      if (els.displayControlBox && typeof state.displayControlOpen === 'boolean') {
        if ('open' in els.displayControlBox) {
          els.displayControlBox.open = state.displayControlOpen;
        }
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
      if (els.showBoxFormDrawer) patch.showFormDrawer = !!els.showBoxFormDrawer.checked;
      if (els.swissOverlayBox) patch.swissOverlayOpen = !!els.swissOverlayBox.open;
      if (els.formDrawerBox) patch.formDrawerOpen = !!els.formDrawerBox.open;
      if (els.fieldCharacteristicsBox) patch.fieldCharacteristicsOpen = !!els.fieldCharacteristicsBox.open;
      if (els.displayControlBox && 'open' in els.displayControlBox) patch.displayControlOpen = !!els.displayControlBox.open;
      if (els.baseMapMode) patch.lastBaseMapMode = String(els.baseMapMode.value || 'swisstopo');

      const mapMenuPos = captureBoxPosition(els.mapMenuBox);
      const swissOverlayPos = captureBoxPosition(els.swissOverlayBox);
      const displayControlPos = captureBoxPosition(els.displayControlBox);
      const fieldCharacteristicsPos = captureBoxPosition(els.fieldCharacteristicsBox);
      const formDrawerPos = captureBoxPosition(els.formDrawerBox);
      if (mapMenuPos) patch.mapMenuPos = mapMenuPos;
      if (swissOverlayPos) patch.swissOverlayPos = swissOverlayPos;
      if (displayControlPos) patch.displayControlPos = displayControlPos;
      if (fieldCharacteristicsPos) patch.fieldCharacteristicsPos = fieldCharacteristicsPos;
      if (formDrawerPos) patch.formDrawerPos = formDrawerPos;

      saveFloatingUiState(patch);
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
        if (storageKey === 'formDrawerPos') {
          boxEl.style.bottom = 'auto';
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
        if (storageKey === 'formDrawerPos') boxEl.style.bottom = 'auto';
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
    }

    function saveEditorLastSettings() {
      saveFloatingUiState({
        lastBaseMapMode: String(els.baseMapMode && els.baseMapMode.value || 'swisstopo'),
        lastShowManualPath: !!(els.showManualPath && els.showManualPath.checked),
        lastShowAlgorithmPath: !!(els.showAlgorithmPath && els.showAlgorithmPath.checked),
        lastDisplayControls: {
          showFieldPolygon: !!(els.showFieldPolygon ? els.showFieldPolygon.checked : true),
          showCoverageCircles: !!(els.showCoverageCircles && els.showCoverageCircles.checked),
          showOverlapAreas: !!(els.showOverlapAreas && els.showOverlapAreas.checked),
          showUncoveredAreas: !!(els.showUncoveredAreas && els.showUncoveredAreas.checked),
          forceCoverageFix: !!(els.forceCoverageFix && els.forceCoverageFix.checked),
          showBasePatterns: !!(els.showBasePatterns && els.showBasePatterns.checked),
          showSectionDirections: !!(els.showSectionDirections && els.showSectionDirections.checked),
          showCorrectionPoints: !!(els.showCorrectionPoints && els.showCorrectionPoints.checked),
          showTurnCurves: !!(els.showTurnCurves && els.showTurnCurves.checked),
          showControlPoints: !!(els.showControlPoints && els.showControlPoints.checked),
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
          edge_factor_pct: Number(els.msEdgeFactorPct && els.msEdgeFactorPct.value || 30),
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
        if (els.showOverlapAreas && typeof dc.showOverlapAreas === 'boolean') els.showOverlapAreas.checked = dc.showOverlapAreas;
        if (els.showUncoveredAreas && typeof dc.showUncoveredAreas === 'boolean') els.showUncoveredAreas.checked = dc.showUncoveredAreas;
        if (els.forceCoverageFix && typeof dc.forceCoverageFix === 'boolean') els.forceCoverageFix.checked = dc.forceCoverageFix;
        if (els.showBasePatterns && typeof dc.showBasePatterns === 'boolean') els.showBasePatterns.checked = dc.showBasePatterns;
        if (els.showSectionDirections && typeof dc.showSectionDirections === 'boolean') els.showSectionDirections.checked = dc.showSectionDirections;
        if (els.showCorrectionPoints && typeof dc.showCorrectionPoints === 'boolean') els.showCorrectionPoints.checked = dc.showCorrectionPoints;
        if (els.showTurnCurves && typeof dc.showTurnCurves === 'boolean') els.showTurnCurves.checked = dc.showTurnCurves;
        if (els.showControlPoints && typeof dc.showControlPoints === 'boolean') els.showControlPoints.checked = dc.showControlPoints;
        if (els.showFieldControlPoints && typeof dc.showFieldControlPoints === 'boolean') els.showFieldControlPoints.checked = dc.showFieldControlPoints;
        if (els.useFieldReferenceAgl && typeof dc.useFieldReferenceAgl === 'boolean') els.useFieldReferenceAgl.checked = dc.useFieldReferenceAgl;
        if (els.computeMode && typeof dc.computeMode === 'string') els.computeMode.value = (dc.computeMode === 'full') ? 'full' : 'simple';
      }
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
      if (els.msAltitudeMode && typeof ms.altitude_mode === 'string') els.msAltitudeMode.value = ms.altitude_mode;
      syncFlightDirectionControls('number');
      syncOverlapControls('number');
      syncEdgeFactorControls('number');
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
        if (els.showOverlapAreas && typeof dc.showOverlapAreas === 'boolean') els.showOverlapAreas.checked = dc.showOverlapAreas;
        if (els.showUncoveredAreas && typeof dc.showUncoveredAreas === 'boolean') els.showUncoveredAreas.checked = dc.showUncoveredAreas;
        if (els.forceCoverageFix && typeof dc.forceCoverageFix === 'boolean') els.forceCoverageFix.checked = dc.forceCoverageFix;
        if (els.showControlPoints && typeof dc.showControlPoints === 'boolean') els.showControlPoints.checked = dc.showControlPoints;
        if (els.showTurnCurves && typeof dc.showTurnCurves === 'boolean') els.showTurnCurves.checked = dc.showTurnCurves;
        if (els.showFieldControlPoints && typeof dc.showFieldControlPoints === 'boolean') els.showFieldControlPoints.checked = dc.showFieldControlPoints;
        if (els.useFieldReferenceAgl && typeof dc.useFieldReferenceAgl === 'boolean') els.useFieldReferenceAgl.checked = dc.useFieldReferenceAgl;
        if (els.computeMode && typeof dc.computeMode === 'string') els.computeMode.value = (dc.computeMode === 'full') ? 'full' : 'simple';
      }
      // Keep box visibility/positions/open state independent from field content.
      restoreFloatingUiState();
      enforceCoverageDisplayConstraints();
      refreshFlightPathLayers();
    }

    function isSimpleComputeMode() {
      return String(els.computeMode && els.computeMode.value || 'simple') !== 'full';
    }

    function formData() {
      return {
        id: String(els.id.value || '').trim(),
        number: String(els.number.value || '').trim(),
        feld_id: String(els.feld_id.value || '').trim(),
        name: String(els.name.value || '').trim(),
        jg: String(els.jg.value || '').trim(),
        std_flughoehe: String(els.std_flughoehe.value || '').trim(),
        flugzeit: String(els.flugzeit.value || '').trim(),
        distanz: String(els.distanz.value || '').trim(),
        wegpunkte: String(els.wegpunkte.value || '').trim(),
        startplatz: String(els.startplatz.value || '').trim(),
        flaechendaten: String(els.flaechendaten.value || '').trim(),
        flugweg: String(els.flugweg.value || '').trim(),
        mission_settings: {
          drone_type: String(els.msDroneType.value || 'Mavic 3T'),
          kmz_params_active: !!els.msKmzParamsActive.checked,
          simple_mode: isSimpleComputeMode(),
          altitude_m: Number(els.msAltitude.value || 60),
          flight_direction_deg: Number(els.msFlightDirection.value || -1),
          speed_m_s: Number(els.msSpeed.value || 5),
          overlap_pct: Number(els.msOverlapPct.value || 15),
          edge_factor_pct: Number(els.msEdgeFactorPct.value || 30),
          start_speed_active: !!els.msStartSpeedActive.checked,
          start_speed_m_s: Number(els.msStartSpeed.value || 5),
          hfov_deg: readNumericElement(els.msHfov, 49),
          vfov_deg: readNumericElement(els.msVfov, 40),
          dfov_deg: readNumericElement(els.msDfov, 61),
          end_action: String(els.msEndAction.value || 'Aufgabe beenden'),
          heading_mode: String(els.msHeadingMode.value || 'Manuell'),
          waypoint_type: String(els.msWaypointType.value || 'Koordinierte Drehung, Ueberspringt den Wegpunkt'),
          standard_turn_distance_m: (function () {
            const n = Number(els.msTurnDistance && els.msTurnDistance.value);
            return Number.isFinite(n) ? n : 5;
          })(),
          safe_start_altitude_m: Number(els.msSafeStartAltitude.value || 20),
          direct_to_start_position: !!els.msDirectToStart.checked,
          gimbal_mode: String(els.msGimbalMode.value || 'Manuell'),
          altitude_mode: String(els.msAltitudeMode.value || 'ALT (Relativ zum Startpunkt)'),
          waypoint_settings_mode: waypointSettingsModeFromOverrides(),
          waypoint_overrides: cloneWaypointOverrides(),
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
      const keys = ['use_mission_height', 'use_mission_speed', 'use_mission_waypoint_type', 'use_mission_yaw', 'use_mission_turn_distance'];
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
      return row && typeof row === 'object' ? { ...defaultWaypointOverride(), ...row } : defaultWaypointOverride();
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
      openRightDrawer('mission');
      setContextHelp('default');
    }

    function syncWpNumberRange(numEl, rangeEl, src) {
      if (!numEl || !rangeEl) return;
      const n = Number((src === 'range' ? rangeEl : numEl).value);
      if (!Number.isFinite(n)) return;
      numEl.value = String(n);
      rangeEl.value = String(n);
    }

    function applyWaypointFormEnableState() {
      const useH = !!(els.wpUseMissionHeight && els.wpUseMissionHeight.checked);
      const useS = !!(els.wpUseMissionSpeed && els.wpUseMissionSpeed.checked);
      const useT = !!(els.wpUseMissionWaypointType && els.wpUseMissionWaypointType.checked);
      const useY = !!(els.wpUseMissionYaw && els.wpUseMissionYaw.checked);
      [els.wpAltitude, els.wpAltitudeRange].forEach((el) => { if (el) el.disabled = useH; });
      [els.wpSpeed, els.wpSpeedRange].forEach((el) => { if (el) el.disabled = useS; });
      if (els.wpWaypointType) els.wpWaypointType.disabled = useT;
      [els.wpTurnDistance, els.wpTurnDistanceRange].forEach((el) => { if (el) el.disabled = useT; });
      [els.wpHeadingMode, els.wpAlignment, els.wpDirection, els.wpDirectionRange].forEach((el) => { if (el) el.disabled = useY; });
      const headingVal = String(els.wpHeadingMode && els.wpHeadingMode.value || '');
      const headingOpt = els.wpHeadingMode && els.wpHeadingMode.selectedOptions && els.wpHeadingMode.selectedOptions[0];
      let needAngle = /schenk/i.test(headingVal);
      try {
        const extra = headingOpt && headingOpt.dataset && headingOpt.dataset.extra ? JSON.parse(headingOpt.dataset.extra) : {};
        if (extra && extra['wpml:waypointHeadingAngle']) needAngle = true;
      } catch (_) { /* ignore */ }
      if (els.wpDirectionWrap) els.wpDirectionWrap.hidden = useY || !needAngle;
      const gimbal = String(els.msGimbalMode && els.msGimbalMode.value || '');
      if (els.wpPitchWrap) els.wpPitchWrap.hidden = !/wegpunkt|punkt/i.test(gimbal);
    }

    function readWaypointFormRow() {
      const useH = !!(els.wpUseMissionHeight && els.wpUseMissionHeight.checked);
      const useS = !!(els.wpUseMissionSpeed && els.wpUseMissionSpeed.checked);
      const useT = !!(els.wpUseMissionWaypointType && els.wpUseMissionWaypointType.checked);
      const useY = !!(els.wpUseMissionYaw && els.wpUseMissionYaw.checked);
      return {
        use_mission_height: useH,
        use_mission_speed: useS,
        use_mission_waypoint_type: useT,
        use_mission_yaw: useY,
        use_mission_turn_distance: useT,
        auto_height: !!(els.wpAutoHeight && els.wpAutoHeight.checked),
        agl_m: Number(els.wpAltitude && els.wpAltitude.value || 60),
        speed_m_s: Number(els.wpSpeed && els.wpSpeed.value || 5),
        waypoint_type: String(els.wpWaypointType && els.wpWaypointType.value || ''),
        heading_mode: String(els.wpHeadingMode && els.wpHeadingMode.value || ''),
        turning_distance_m: Number(els.wpTurnDistance && els.wpTurnDistance.value || 5),
        direction_deg: Number(els.wpDirection && els.wpDirection.value || 0),
        heading_angle: Number(els.wpDirection && els.wpDirection.value || 0),
        gimbal_pitch: Number(els.wpPitch && els.wpPitch.value || -90),
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
      targets.forEach((idx1) => setWaypointOverrideRow(idx1, { ...row }));
      const next = path.map((p) => [p[0], p[1], p[2]]);
      if (!applyAll) {
        const idx0 = selectedWaypointIndex1 - 1;
        if (idx0 >= 0 && idx0 < next.length) {
          const lat = Number(els.wpLat && els.wpLat.value);
          const lng = Number(els.wpLng && els.wpLng.value);
          if (Number.isFinite(lat) && Number.isFinite(lng)) {
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
      currentManualPath = next;
      originalManualPath = next.map((p) => [p[0], p[1], p[2]]);
      if (els.flugweg) els.flugweg.value = formatTriplets(next);
      updateSaveButtonState();
      refreshFlightPathLayers();
      return true;
    }

    function waypointHasLocalOverride(index1) {
      const row = waypointOverrides[String(index1)];
      if (!row || typeof row !== 'object') return false;
      return row.use_mission_height === false
        || row.use_mission_speed === false
        || row.use_mission_waypoint_type === false
        || row.use_mission_yaw === false
        || row.use_mission_turn_distance === false;
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
      if (els.wpSinglePane) els.wpSinglePane.hidden = false;
      if (els.wpAllPane) els.wpAllPane.hidden = true;
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

    async function updateWaypointAglHint(lon, lat, agl) {
      if (!els.wpAglHint) return;
      if (!Number.isFinite(lon) || !Number.isFinite(lat) || !Number.isFinite(agl)) {
        els.wpAglHint.textContent = 'Flughöhe zum Boden: -';
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
          els.wpAglHint.textContent = `Flughöhe zum Boden: ${aboveGround.toFixed(2)}m`;
          return;
        }
      } catch (_) { /* ignore */ }
      els.wpAglHint.textContent = `Flughöhe zum Boden: ${Number(agl).toFixed(2)}m`;
    }

    function fillWaypointForm(index1, takeSnapshot) {
      const path = currentWaypointPath();
      if (path.length < 2) return false;
      const idx0 = Math.max(0, Math.min(path.length - 1, Number(index1) - 1));
      selectedWaypointIndex1 = idx0 + 1;
      const pt = path[idx0];
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
      if (els.wpUseMissionHeight) els.wpUseMissionHeight.checked = row.use_mission_height !== false;
      if (els.wpAutoHeight) els.wpAutoHeight.checked = row.auto_height !== false;
      const alt = row.use_mission_height !== false ? missionAlt : (Number.isFinite(row.agl_m) ? row.agl_m : Number(pt[2] || missionAlt));
      if (els.wpAltitude) els.wpAltitude.value = String(alt);
      if (els.wpAltitudeRange) els.wpAltitudeRange.value = String(alt);
      if (els.wpMissionAltHint) els.wpMissionAltHint.textContent = `Missions-Höhe: ${missionAlt}m`;
      if (els.wpUseMissionSpeed) els.wpUseMissionSpeed.checked = row.use_mission_speed !== false;
      const spd = row.use_mission_speed !== false ? missionSpeed : Number(row.speed_m_s || missionSpeed);
      if (els.wpSpeed) els.wpSpeed.value = String(spd);
      if (els.wpSpeedRange) els.wpSpeedRange.value = String(spd);
      if (els.wpMissionSpeedHint) els.wpMissionSpeedHint.textContent = `Missions-Geschwindigkeit: ${missionSpeed}m/s`;
      if (els.wpUseMissionWaypointType) els.wpUseMissionWaypointType.checked = row.use_mission_waypoint_type !== false;
      if (els.wpWaypointType) els.wpWaypointType.value = row.use_mission_waypoint_type !== false ? missionType : (row.waypoint_type || missionType);
      const turn = row.use_mission_turn_distance !== false ? missionTurn : Number(row.turning_distance_m || missionTurn);
      if (els.wpTurnDistance) els.wpTurnDistance.value = String(turn);
      if (els.wpTurnDistanceRange) els.wpTurnDistanceRange.value = String(turn);
      if (els.wpMissionTurnHint) els.wpMissionTurnHint.textContent = `Standard-Wendedistanz: ${Number(missionTurn).toFixed(2)}m`;
      if (els.wpUseMissionYaw) els.wpUseMissionYaw.checked = row.use_mission_yaw !== false;
      if (els.wpHeadingMode) els.wpHeadingMode.value = row.use_mission_yaw !== false ? missionYaw : (row.heading_mode || missionYaw);
      if (els.wpAlignment) els.wpAlignment.value = row.alignment_aircraft || '';
      const dir = Number.isFinite(Number(row.direction_deg)) ? Number(row.direction_deg) : 0;
      if (els.wpDirection) els.wpDirection.value = String(dir);
      if (els.wpDirectionRange) els.wpDirectionRange.value = String(dir);
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
          opt.textContent = name;
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
      applyDroneProfileToForm();
    }

    function applyKmzParamsUiState() {
      const active = !!(els.msKmzParamsActive && els.msKmzParamsActive.checked);
      const targets = [
        els.msAltitude, els.msFlightDirectionRange, els.msFlightDirection, els.msSpeed, els.msStartSpeedActive, els.msStartSpeed,
        els.msOverlapPctRange, els.msOverlapPct, els.msEdgeFactorPctRange, els.msEdgeFactorPct,
        els.msEndAction, els.msHeadingMode, els.msWaypointType, els.msTurnDistance,
        els.msSafeStartAltitude, els.msDirectToStart, els.msGimbalMode, els.msAltitudeMode,
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

    function syncFlightDirectionControls(source) {
      const current = source === 'range'
        ? (els.msFlightDirectionRange ? els.msFlightDirectionRange.value : -1)
        : (els.msFlightDirection ? els.msFlightDirection.value : -1);
      const next = clampFlightDirection(current);
      if (els.msFlightDirection) els.msFlightDirection.value = String(next);
      if (els.msFlightDirectionRange) els.msFlightDirectionRange.value = String(next);
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
        ? (els.msEdgeFactorPctRange ? els.msEdgeFactorPctRange.value : 30)
        : (els.msEdgeFactorPct ? els.msEdgeFactorPct.value : 30);
      const next = clampPercentValue(current, 0, 100, 30);
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

    function setForm(v) {
      els.id.value = v.id || '';
      els.number.value = v.number || '';
      els.feld_id.value = v.feld_id || '';
      els.name.value = v.name || '';
      els.jg.value = v.jg || '';
      els.std_flughoehe.value = v.std_flughoehe || '50';
      els.flugzeit.value = v.flugzeit || '0';
      els.distanz.value = v.distanz || '0';
      els.wegpunkte.value = v.wegpunkte || '';
      els.startplatz.value = v.startplatz || '';
      els.flaechendaten.value = v.flaechendaten || '';
      els.flugweg.value = v.flugweg || '';
      originalManualPath = parseTriplets(v.flugweg || '');
      loadWaypointSettingsFromField(v);
      syncMapFromForm();
      pushHistory('Feld geladen');
      markFieldAsSaved();
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
        });
        coreCalcParamsLoaded = true;
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
      const tol = Number(coreCalcParams.agl_tolerance_m);
      return Number.isFinite(tol) && tol > 0 ? tol : 10.0;
    }

    function setPathSourceFromTriplets(source, path, pathKind = 'manual') {
      if (!source) return;
      source.clear();
      const pts = normalizeTripletPath(path);
      if (pts.length < 2) return;
      const coords = pts.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      source.addFeature(new ol.Feature({ geometry: new ol.geom.LineString(coords) }));
      for (let i = 0; i < coords.length; i++) {
        const pf = new ol.Feature({ geometry: new ol.geom.Point(coords[i]) });
        pf.set('pathIdx', i + 1);
        pf.set('isSelectedWaypoint', rightDrawerMode === 'waypoint' && selectedWaypointIndex1 === (i + 1));
        const wpAlt = Number(pts[i] && pts[i][2]);
        pf.set('pathWpAlt', Number.isFinite(wpAlt) ? wpAlt : NaN);
        pf.set('pathBottomLabel', Number.isFinite(wpAlt) ? `${wpAlt.toFixed(1)}m` : '-');
        pf.set('pathTopLabel', '-');
        source.addFeature(pf);
      }
      const wpTypeNow = String(els.msWaypointType && els.msWaypointType.value || '');
      const enableTurnGuide = !!(els.showTurnCurves && els.showTurnCurves.checked) && isSkipTurnWaypointType(wpTypeNow);
      const turnDistM = Number(els.msTurnDistance && els.msTurnDistance.value);
      const guideLen = Number.isFinite(turnDistM) && turnDistM > 0 ? turnDistM : 0;
      const isTurnIdx = (idx) => {
        const wpIdx = idx + 1;
        return currentTurnWaypointIndices.has(wpIdx)
          || (!currentTurnWaypointIndices.size && wpIdx > 1 && wpIdx < pts.length && (wpIdx % 2 === 0));
      };
      if (enableTurnGuide && guideLen > 0) {
        for (let i = 1; i + 1 < pts.length; i++) {
          if (!isTurnIdx(i)) continue;
          const prev = pts[i - 1];
          const curr = pts[i];
          const next = pts[i + 1];
          const addGuide = (toPt) => {
            const dx = Number(toPt[0]) - Number(curr[0]);
            const dy = Number(toPt[1]) - Number(curr[1]);
            const len = Math.hypot(dx, dy);
            if (!(len > 1e-12)) return;
            const ux = dx / len;
            const uy = dy / len;
            const end = [Number(curr[0]) + ux * guideLen, Number(curr[1]) + uy * guideLen];
            const guide = new ol.Feature({
              geometry: new ol.geom.LineString([
                ol.proj.fromLonLat([Number(curr[0]), Number(curr[1])]),
                ol.proj.fromLonLat([Number(end[0]), Number(end[1])]),
              ]),
            });
            guide.set('isTurnGuide', true);
            guide.set('pathKind', pathKind);
            source.addFeature(guide);
          };
          addGuide(prev);
          addGuide(next);
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
      updatePathPointHeightLabels(source, pts).catch(() => {});
    }

    async function updatePathPointHeightLabels(source, pathTriplets) {
      if (!source) return;
      const pts = normalizeTripletPath(pathTriplets);
      if (!pts.length) return;
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
          const feat = pointFeaturesByIdx.get(idx);
          if (!feat) return;
          const rel = (Number.isFinite(terrain) && Number.isFinite(startTerrain))
            ? (terrain - startTerrain)
            : NaN;
          const topText = Number.isFinite(rel) ? `${rel >= 0 ? '+' : ''}${rel.toFixed(1)}m` : '-';
          feat.set('pathTopLabel', topText);
        });
      }
      source.changed();
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
      const manualVisible = !!(els.showManualPath && els.showManualPath.checked);
      const algoVisible = !!(els.showAlgorithmPath && els.showAlgorithmPath.checked);
      const manualPath = normalizeTripletPath(originalManualPath.length ? originalManualPath : currentManualPath);
      const algoPath = normalizeTripletPath(currentComputedPath);
      if (algoVisible && algoPath.length >= 2) return { source: 'neu', path: algoPath };
      if (manualVisible && manualPath.length >= 2) return { source: 'original', path: manualPath };
      if (algoPath.length >= 2) return { source: 'neu', path: algoPath };
      if (manualPath.length >= 2) return { source: 'original', path: manualPath };
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
      const pts = normalizeTripletPath(currentManualPath);
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

    function commitManualPathToForm(path, historyLabel = '') {
      const next = normalizeTripletPath(path);
      currentManualPath = next.map((p) => [p[0], p[1], p[2]]);
      originalManualPath = currentManualPath.map((p) => [p[0], p[1], p[2]]);
      els.flugweg.value = formatTriplets(currentManualPath);
      if (historyLabel) pushHistory(historyLabel);
      refreshFlightPathLayers();
      refreshPathMidpointHandles();
      updateSaveButtonState();
    }

    function enforceCoverageDisplayConstraints() {
      const manualVisible = !!(els.showManualPath && els.showManualPath.checked);
      const algoVisible = !!(els.showAlgorithmPath && els.showAlgorithmPath.checked);
      if (els.showCoverageCircles) {
        if (!manualVisible && !algoVisible) els.showCoverageCircles.checked = false;
      }
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
      if (els.coverageSourceLabel) {
        let sourceText = 'neu';
        if (manualVisible && !algoVisible) {
          sourceText = 'original';
        }
        els.coverageSourceLabel.textContent = `Abdeckung (${sourceText})`;
      }
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
      const showComputed = !!(els.showAlgorithmPath && els.showAlgorithmPath.checked);
      manualPathLayer.setVisible(showManual);
      computedPathLayer.setVisible(showComputed);
      if (polygonLayer) {
        const showPoly = !!(els.showFieldPolygon && els.showFieldPolygon.checked);
        polygonLayer.setVisible(showPoly);
      }
      if (vertexLayer) {
        const showPoly = !!(els.showFieldPolygon && els.showFieldPolygon.checked);
        vertexLayer.setVisible(showPoly);
      }
      if (manualPathLayer && typeof manualPathLayer.changed === 'function') manualPathLayer.changed();
      if (computedPathLayer && typeof computedPathLayer.changed === 'function') computedPathLayer.changed();
      if (controlPointLayer) {
        const showPathCp = !!(els.showControlPoints && els.showControlPoints.checked);
        const showFieldCp = !!(els.showFieldControlPoints && els.showFieldControlPoints.checked);
        controlPointLayer.setVisible(showPathCp || showFieldCp);
      }
      const manualPathForDisplay = originalManualPath.length ? originalManualPath : currentManualPath;
      setPathSourceFromTriplets(manualPathSource, manualPathForDisplay, 'manual');
      setPathSourceFromTriplets(computedPathSource, currentComputedPath, 'computed');
      scheduleControlPointRefresh();
      refreshCoverageGeometry();
      refreshPathMidpointHandles();
      refreshWsVisualLines();
      scheduleFieldCharacteristicsRefresh();
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

    function pathControlPointFeatures(path, relHeights, flightLineAltRelStart) {
      const pts = Array.isArray(path) ? path : [];
      const rels = (relHeights || [])
        .map((v) => Number(v))
        .filter((v) => Number.isFinite(v));
      const minRel = rels.length ? Math.min(...rels) : NaN;
      const maxRel = rels.length ? Math.max(...rels) : NaN;
      const span = Number.isFinite(minRel) && Number.isFinite(maxRel) ? Math.max(1e-9, maxRel - minRel) : NaN;
      const fallbackAgl = currentAglValue();
      const targetAgl = Number(els.msAltitude && els.msAltitude.value);
      const targetAglM = Number.isFinite(targetAgl) ? targetAgl : fallbackAgl;
      const aglTolM = currentAglToleranceValue();
      return pts.map((p, idx) => {
        const terrainDeltaRelStart = Number(relHeights && relHeights[idx]);
        const lineAltRelStart = Number(flightLineAltRelStart && flightLineAltRelStart[idx]);
        const relOut = Number.isFinite(terrainDeltaRelStart) ? terrainDeltaRelStart : 0;
        const topText = `${relOut >= 0 ? '+' : ''}${relOut.toFixed(1)}m`;
        const aglLocal = Number.isFinite(lineAltRelStart) && Number.isFinite(terrainDeltaRelStart)
          ? (lineAltRelStart - terrainDeltaRelStart)
          : (Number.isFinite(lineAltRelStart) ? lineAltRelStart : fallbackAgl);
        const bottomText = `${aglLocal.toFixed(1)}m`;
        const aglDeviation = Math.abs(aglLocal - targetAglM);
        const isAglOutOfTol = Number.isFinite(aglDeviation) && (aglDeviation > aglTolM);
        const labelColor = isAglOutOfTol ? '#c01818' : '#808080';
        const t = (Number.isFinite(terrainDeltaRelStart) && Number.isFinite(span))
          ? (terrainDeltaRelStart - minRel) / span
          : 0.5;
        const pointColor = colorForRelativeHeight(t);
        const feat = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([p[0], p[1]])) });
        feat.setStyle([
          new ol.style.Style({
            image: new ol.style.Circle({
              radius: 4.2,
              fill: new ol.style.Fill({ color: pointColor }),
              stroke: new ol.style.Stroke({ color: '#ffffff', width: 1 }),
            }),
          }),
          new ol.style.Style({
            text: new ol.style.Text({
              text: topText,
              font: '12px Arial',
              offsetY: -14,
              fill: new ol.style.Fill({ color: isAglOutOfTol ? '#c01818' : '#1b3552' }),
              stroke: new ol.style.Stroke({ color: '#ffffff', width: 3.2 }),
            }),
          }),
          new ol.style.Style({
            text: new ol.style.Text({
              text: bottomText,
              font: '12px Arial',
              offsetY: 14,
              fill: new ol.style.Fill({ color: labelColor }),
              stroke: new ol.style.Stroke({ color: 'rgba(255,255,255,0.98)', width: 3.4 }),
            }),
          }),
        ]);
        return feat;
      });
    }

    async function aglForControlPointsAlongPath(controlPoints, pathTriplets, startLonLat) {
      const cps = Array.isArray(controlPoints) ? controlPoints : [];
      const path = normalizeTripletPath(pathTriplets);
      if (!cps.length) return [];
      if (path.length < 2) return cps.map(() => currentAglValue());
      const wpLonLat = path.map((p) => [p[0], p[1]]);
      let wpTerrainDelta = [];
      if (Array.isArray(startLonLat) && startLonLat.length >= 2) {
        wpTerrainDelta = await computeControlPointRelativeAgl(wpLonLat, startLonLat);
      } else {
        wpTerrainDelta = wpLonLat.map(() => NaN);
      }
      const segs = [];
      for (let i = 0; i + 1 < path.length; i++) {
        const aXY = ol.proj.fromLonLat([path[i][0], path[i][1]]);
        const bXY = ol.proj.fromLonLat([path[i + 1][0], path[i + 1][1]]);
        const aRelTerrain = Number(wpTerrainDelta[i]);
        const bRelTerrain = Number(wpTerrainDelta[i + 1]);
        const aAslRelStart = (Number.isFinite(aRelTerrain) ? aRelTerrain : 0) + (Number(path[i][2]) || 0);
        const bAslRelStart = (Number.isFinite(bRelTerrain) ? bRelTerrain : 0) + (Number(path[i + 1][2]) || 0);
        segs.push({
          ax: aXY[0], ay: aXY[1], aAslRelStart,
          bx: bXY[0], by: bXY[1], bAslRelStart,
        });
      }
      return cps.map((cp) => {
        const pXY = ol.proj.fromLonLat([cp[0], cp[1]]);
        const px = pXY[0];
        const py = pXY[1];
        let bestDist2 = Infinity;
        let bestAslRelStart = currentAglValue();
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
            bestAslRelStart = s.aAslRelStart + (s.bAslRelStart - s.aAslRelStart) * t;
          }
        }
        return Number.isFinite(bestAslRelStart) ? bestAslRelStart : currentAglValue();
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
      const manual = originalManualPath.length ? originalManualPath : currentManualPath;
      return normalizeTripletPath(manual).map((p) => [p[0], p[1]]);
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
      if (!showPathCp && !showFieldCp) return;
      const startPts = parsePairs(els.startplatz && els.startplatz.value);
      const startLonLat = startPts.length ? [startPts[0][0], startPts[0][1]] : null;
      if (!startLonLat) return;
      const features = [];
      if (showPathCp) {
        if (shouldShowManualPathControlPoints()) {
          const manualPts = manualPathForControlPoints();
          const sampledManualPts = buildPathControlPointSamples(manualPts, 25);
          const relAglManual = await computeControlPointRelativeAgl(sampledManualPts, startLonLat);
          const manualPathTriplets = originalManualPath.length ? originalManualPath : currentManualPath;
          const aglManual = await aglForControlPointsAlongPath(sampledManualPts, manualPathTriplets, startLonLat);
          if (token !== controlPointRefreshToken) return;
          pathControlPointFeatures(sampledManualPts, relAglManual, aglManual).forEach((f) => features.push(f));
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
            const relAglAlgo = await computeControlPointRelativeAgl(algoPts, startLonLat);
            const pathForAgl = (currentComputedPath && currentComputedPath.length >= 2)
              ? currentComputedPath
              : algoPts;
            const aglAlgo = await aglForControlPointsAlongPath(algoPts, pathForAgl, startLonLat);
            if (token !== controlPointRefreshToken) return;
            pathControlPointFeatures(algoPts, relAglAlgo, aglAlgo).forEach((f) => features.push(f));
          }
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
          if (i > 0 && s === 0) continue; // avoid duplicates at segment joints
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
      if (entered === null) return;
      const q = String(entered || '').trim();
      if (!q) {
        showStatus('Keine Sucheingabe.', false);
        return;
      }
      showStatus('Suche Ort...');
      const hit = await geocodePlaceName(q);
      if (!hit) {
        showStatus('Ort nicht gefunden oder Geocoding-Dienst nicht erreichbar.', false);
        return;
      }
      if (findPlaceSource) findPlaceSource.clear();
      const xy = ol.proj.fromLonLat([hit.lon, hit.lat]);
      const f = new ol.Feature({ geometry: new ol.geom.Point(xy) });
      const lbl = [hit.name, hit.admin1, hit.country].filter(Boolean).join(', ');
      f.set('findLabel', lbl || hit.name);
      findPlaceSource.addFeature(f);
      map.getView().animate({ center: xy, zoom: Math.max(14, map.getView().getZoom() || 14), duration: 450 });
      showStatus('Ort gefunden: ' + (lbl || hit.name));
    }

    function ensureMap() {
      if (map) return;
      polygonSource = new ol.source.Vector();
      startSource = new ol.source.Vector();
      findPlaceSource = new ol.source.Vector();
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
            stroke: new ol.style.Stroke({ color: 'rgba(185,35,35,0.95)', width: 2.2 }),
            fill: new ol.style.Fill({ color: showUncovered ? 'rgba(185,35,35,0.14)' : 'rgba(185,35,35,0.05)' }),
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
                fill: new ol.style.Fill({ color: 'rgba(240,110,110,0.95)' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 1.5 }),
              }),
            }),
            new ol.style.Style({
              text: new ol.style.Text({
                text: startText,
                offsetY: -14,
                font: '12px Arial',
                fill: new ol.style.Fill({ color: '#ef6b6b' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
              }),
            }),
            new ol.style.Style({
              text: new ol.style.Text({
                text: aslText,
                offsetY: 14,
                font: '12px Arial',
                fill: new ol.style.Fill({ color: '#ef6b6b' }),
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
      manualPathLayer = new ol.layer.Vector({
        source: manualPathSource,
        visible: true,
        style: (feature) => {
          const showDirectionInfo = !!(els.showSectionDirections && els.showSectionDirections.checked);
          const showCorrection = !!(els.showCorrectionPoints && els.showCorrectionPoints.checked);
          const wpTypeNow = String(els.msWaypointType && els.msWaypointType.value || '');
          const showTurnGuide = showCorrection && !!(els.showTurnCurves && els.showTurnCurves.checked) && isSkipTurnWaypointType(wpTypeNow);
          const g = feature.getGeometry();
          if (g instanceof ol.geom.LineString) {
            if (feature.get('isTurnGuide')) {
              if (!showTurnGuide) return null;
              return new ol.style.Style({
                stroke: new ol.style.Stroke({
                  color: 'rgba(255,140,0,0.95)',
                  width: 2.0,
                  lineDash: [6, 6],
                }),
              });
            }
            return new ol.style.Style({
              stroke: new ol.style.Stroke({ color: 'rgba(33,96,214,0.95)', width: 2.6 }),
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
                fill: new ol.style.Fill({ color: 'rgba(32,96,214,0.92)' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 1.2 }),
              }),
            });
          }
          const idx = Number(feature.get('pathIdx') || 0);
          const isTurn = showCorrection
            && (currentTurnWaypointIndices.has(idx)
              || (!currentTurnWaypointIndices.size && idx > 1 && idx % 2 === 0));
          const topText = String(feature.get('pathTopLabel') || '-');
          const bottomText = String(feature.get('pathBottomLabel') || '-');
          const isSelected = rightDrawerMode === 'waypoint' && idx === selectedWaypointIndex1;
          return [
            new ol.style.Style({
              image: new ol.style.Circle({
                radius: isSelected ? 8 : 4.8,
                fill: new ol.style.Fill({ color: isSelected ? 'rgba(220,38,38,0.95)' : (isTurn ? 'rgba(255,140,0,0.95)' : 'rgba(33,96,214,0.95)') }),
                stroke: new ol.style.Stroke({ color: '#fff', width: isSelected ? 2 : 1.2 }),
              }),
            }),
            new ol.style.Style({
              text: new ol.style.Text({
                text: topText,
                offsetY: -10,
                font: '12px Arial',
                fill: new ol.style.Fill({ color: '#6b7280' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 }),
              }),
            }),
            ...(bottomText ? [new ol.style.Style({
              text: new ol.style.Text({
                text: bottomText,
                offsetY: 12,
                font: '12px Arial',
                fill: new ol.style.Fill({ color: '#6b7280' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2.6 }),
              }),
            })] : []),
          ];
        },
      });
      computedPathLayer = new ol.layer.Vector({
        source: computedPathSource,
        visible: true,
        style: (feature) => {
          const showDirectionInfo = !!(els.showSectionDirections && els.showSectionDirections.checked);
          const showCorrection = !!(els.showCorrectionPoints && els.showCorrectionPoints.checked);
          const wpTypeNow = String(els.msWaypointType && els.msWaypointType.value || '');
          const showTurnGuide = showCorrection && !!(els.showTurnCurves && els.showTurnCurves.checked) && isSkipTurnWaypointType(wpTypeNow);
          const g = feature.getGeometry();
          if (g instanceof ol.geom.LineString) {
            if (feature.get('isTurnGuide')) {
              if (!showTurnGuide) return null;
              return new ol.style.Style({
                stroke: new ol.style.Stroke({
                  color: 'rgba(255,140,0,0.95)',
                  width: 2.0,
                  lineDash: [6, 6],
                }),
              });
            }
            return new ol.style.Style({
              stroke: new ol.style.Stroke({ color: 'rgba(27,158,71,0.95)', width: 2.6 }),
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
                fill: new ol.style.Fill({ color: 'rgba(27,158,71,0.92)' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 1.2 }),
              }),
            });
          }
          const idx = Number(feature.get('pathIdx') || 0);
          const isTurn = showCorrection
            && (currentTurnWaypointIndices.has(idx)
              || (!currentTurnWaypointIndices.size && idx > 1 && idx % 2 === 0));
          const topText = String(feature.get('pathTopLabel') || '-');
          const bottomText = String(feature.get('pathBottomLabel') || '-');
          const isSelected = rightDrawerMode === 'waypoint' && idx === selectedWaypointIndex1;
          return [
            new ol.style.Style({
              image: new ol.style.Circle({
                radius: isSelected ? 8 : 4.8,
                fill: new ol.style.Fill({ color: isSelected ? 'rgba(220,38,38,0.95)' : (isTurn ? 'rgba(255,140,0,0.95)' : 'rgba(27,158,71,0.95)') }),
                stroke: new ol.style.Stroke({ color: '#fff', width: isSelected ? 2 : 1.2 }),
              }),
            }),
            new ol.style.Style({
              text: new ol.style.Text({
                text: topText,
                offsetY: -10,
                font: '12px Arial',
                fill: new ol.style.Fill({ color: '#6b7280' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 }),
              }),
            }),
            ...(bottomText ? [new ol.style.Style({
              text: new ol.style.Text({
                text: bottomText,
                offsetY: 12,
                font: '12px Arial',
                fill: new ol.style.Fill({ color: '#6b7280' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2.6 }),
              }),
            })] : []),
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
        visible: true,
      });
      uncoveredLayer = new ol.layer.Vector({
        source: uncoveredSource,
        visible: true,
      });
      wsVisualLayer = new ol.layer.Vector({
        source: wsVisualSource,
        visible: true,
        zIndex: 55,
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
                fill: new ol.style.Fill({ color: 'rgba(185,35,35,0.95)' }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 1.2 }),
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
          text: new ol.style.Text({
            text: '+',
            font: 'bold 14px Arial',
            fill: new ol.style.Fill({ color: '#1f7a3f' }),
            stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
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
          startLayer,
          findPlaceLayer,
        ],
        view: new ol.View({ center: ol.proj.fromLonLat([8.54, 47.28]), zoom: 13 }),
      });
      map.on('moveend', () => {
        if (manualPathLayer && typeof manualPathLayer.changed === 'function') manualPathLayer.changed();
        if (computedPathLayer && typeof computedPathLayer.changed === 'function') computedPathLayer.changed();
        refreshWsVisualLines();
        updateMapScaleBar();
      });
      try { map.getView().on('change:resolution', updateMapScaleBar); } catch (_) {}
      try { map.getView().on('change:center', updateMapScaleBar); } catch (_) {}
      updateMapScaleBar();
      modifyInteraction = new ol.interaction.Modify({ source: polygonSource });
      pathModifyInteraction = new ol.interaction.Modify({ source: manualPathSource });
      startModifyInteraction = new ol.interaction.Modify({ source: startSource });
      snapInteraction = new ol.interaction.Snap({ source: polygonSource });
      map.addInteraction(snapInteraction);
      map.addInteraction(modifyInteraction);
      map.addInteraction(pathModifyInteraction);
      map.addInteraction(startModifyInteraction);
      modifyInteraction.setActive(false);
      pathModifyInteraction.setActive(false);
      startModifyInteraction.setActive(true);
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
      pathModifyInteraction.on('modifystart', () => {
        if (!isPathEditModeActive) return;
        pushHistory('Vor Pfad-Bearbeitung');
      });
      pathModifyInteraction.on('modifyend', () => {
        if (!isPathEditModeActive) return;
        const line = manualPathSource.getFeatures().find((f) => f.getGeometry() instanceof ol.geom.LineString);
        if (!line) return;
        const coords = line.getGeometry().getCoordinates() || [];
        const prev = normalizeTripletPath(currentManualPath);
        const defaultAlt = Number(els.std_flughoehe && els.std_flughoehe.value);
        const fallbackAlt = Number.isFinite(defaultAlt) ? defaultAlt : 0;
        const next = coords.map((xy, i) => {
          const ll = ol.proj.toLonLat(xy);
          const alt = Number(prev[i] && prev[i][2]);
          return [Number(ll[0]), Number(ll[1]), Number.isFinite(alt) ? alt : fallbackAlt];
        });
        commitManualPathToForm(next, 'Pfad bearbeitet');
      });
      map.on('singleclick', (evt) => {
        if (isPathEditModeActive && pathMidpointLayer && pathMidpointLayer.getVisible()) {
          const pathMidFeat = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => (layer === pathMidpointLayer ? f : null));
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
                showStatus('Neuer Pfadpunkt eingefuegt.');
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
          openWaypointDrawer(Number(pathFeat.get('pathIdx')));
          return;
        }
        if (!isEditModeActive && !isPathEditModeActive) {
          if (dabsCtl && dabsCtl.showAtEvent(evt)) return;
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
          const pathPoint = map.forEachFeatureAtPixel(evt.pixel, (f, layer) => (layer === manualPathLayer ? f : null));
          if (pathPoint && pathPoint.getGeometry() instanceof ol.geom.Point) {
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
              showStatus('Pfadpunkt geloescht.');
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
      restoreOverlayOpacityState();
      applyOverlayLayers();
      updateUndoButtonState();
      makeMapBoxDraggable(els.mapMenuBox, '.map-toolbar', 'mapMenuPos');
      makeMapBoxDraggable(els.swissOverlayBox, 'summary', 'swissOverlayPos');
      makeMapBoxDraggable(els.displayControlBox, '.title', 'displayControlPos');
      makeMapBoxDraggable(els.fieldCharacteristicsBox, 'summary', 'fieldCharacteristicsPos');
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

      saveFloatingUiState({
        overlayOpacity: overlayOpacityStateFromInputs(),
        overlayToggles: overlayToggleStateFromInputs(),
      });
    }

    function updateSwissOverlayVisibility() {
      const mode = String(els.baseMapMode.value || 'swisstopo');
      const show = mode === 'swisstopo' || mode === 'swisstopo-grau' || mode === 'swissimage';
      if (els.showBoxSwissOverlay) {
        els.showBoxSwissOverlay.disabled = !show;
        if (!show) {
          els.showBoxSwissOverlay.title = 'Nur bei Swisstopo/Swissimage verfuegbar';
        } else {
          els.showBoxSwissOverlay.title = 'SwissTopo-Overlay-Box anzeigen';
        }
      }
      applyBoxVisibilityStateFromSetup();
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

    function syncMapFromForm() {
      ensureMap();
      polygonSource.clear();
      startSource.clear();
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
      fitMapToFeatures();
      updateSaveButtonState();
      // Form-side field geometry edits must also trigger recomputation.
      scheduleFlightPathRecompute();
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
      setContextHelp('default');
    }

    function startPolygonDraw() {
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
        showStatus('Feldrand gezeichnet. Bearbeiten aktiv.');
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
      updateSaveButtonState();
      if (historyLabel) pushHistory(historyLabel);
      recomputeFlightPathNow();
    }

    function startPickStart() {
      ensureMap();
      stopAllDrawModes();
      if (startModifyInteraction) startModifyInteraction.setActive(false);
      startDrawInteraction = new ol.interaction.Draw({ source: startSource, type: 'Point' });
      startDrawInteraction.on('drawstart', () => {
        pushHistory('Vor Startpunkt-Aenderung');
        startSource.clear();
        currentStartFeature = null;
      });
      startDrawInteraction.once('drawend', (evt) => {
        currentStartFeature = evt.feature;
        currentStartFeature.set('startLabel', 'Startpunkt');
        currentStartFeature.set('aslLabel', '-');
        if (startModifyInteraction) startModifyInteraction.setActive(true);
        stopAllDrawModes();
        applyStartPointChange('Startpunkt gesetzt');
        showStatus('Startpunkt gesetzt. Flugpfad wird neu berechnet.');
      });
      map.addInteraction(startDrawInteraction);
      showStatus('Startpunkt-Modus aktiv: in die Karte klicken. Bestehenden Start danach ziehen.');
      setContextHelp('start');
    }

    function renderList() {
      const q = String(els.searchInput.value || '').trim().toLowerCase();
      els.fieldList.innerHTML = '';
      allFields
        .filter((f) => !q || `${f.feld_id} ${f.name}`.toLowerCase().includes(q))
        .forEach((f) => {
          const row = document.createElement('div');
          row.className = `field-item${String(f.id) === String(selectedId) ? ' active' : ''}`;
          const main = document.createElement('div');
          main.className = 'field-item-main';
          main.textContent = `${f.feld_id} - ${f.name}`;
          const metaParts = [];
          const funde = String(f.funde || '').trim();
          const dyn = String(f.dynamic_feld_info || '').trim();
          if (funde) metaParts.push(`Funde: ${funde}`);
          if (dyn) metaParts.push(dyn);
          row.appendChild(main);
          if (metaParts.length) {
            const meta = document.createElement('div');
            meta.className = 'field-item-meta';
            meta.textContent = metaParts.join(' | ');
            row.appendChild(meta);
          }
          row.addEventListener('click', () => loadField(f.id));
          els.fieldList.appendChild(row);
        });
      renderQuickSlots();
    }

    async function loadList() {
      const r = await fetch('api_field_editor.php?action=list', { cache: 'no-store' });
      const data = await r.json();
      if (!data.ok) {
        throw new Error(data.error || 'list_failed');
      }
      allFields = Array.isArray(data.fields) ? data.fields : [];
      renderList();
    }

    async function loadField(id) {
      const r = await fetch(`api_field_editor.php?action=get&id=${encodeURIComponent(id)}`, { cache: 'no-store' });
      const data = await r.json();
      if (!data.ok) {
        throw new Error(data.error || 'get_failed');
      }
      selectedId = String(id);
      setForm(data.field || {});
      reapplyPersistentUiSettingsAfterFieldChange();
      renderList();
      showStatus(`Feld geladen: ${data.field.feld_id}`);
    }

    async function save(mode) {
      const payload = { mode, field: formData() };
      const r = await fetch('api_field_editor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await r.json();
      if (!data.ok) {
        throw new Error(`${data.error || 'save_failed'}${data.detail ? ` - ${data.detail}` : ''}`);
      }
      await loadList();
      showStatus(mode === 'create' ? 'Neues Feld wurde angelegt.' : 'Feld wurde gespeichert.');
      if (mode === 'update') {
        markFieldAsSaved();
      }
      if (mode === 'create') {
        const created = allFields.find((f) => f.feld_id === data.saved_field_id);
        if (created) {
          await loadField(created.id);
        }
      }
    }

    function clearForm(clearMap = true) {
      selectedId = '';
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
      reapplyPersistentUiSettingsAfterFieldChange();
      renderList();
      showStatus('Leeres Formular bereit. Karte wurde geleert. Mit "Als neues Feld anlegen" speichern.');
    }

    function openCreateFieldModal() {
      if (!els.createFieldModalBackdrop) return;
      const f = formData();
      els.create_feld_id.value = f.id ? '' : String(f.feld_id || '');
      els.create_name.value = f.id ? '' : String(f.name || '');
      els.create_jg.value = String(f.jg || '');
      els.create_number.value = String(f.number || '');
      els.create_std_flughoehe.value = String(f.std_flughoehe || '50');
      els.create_startplatz.value = String(f.startplatz || '');
      els.create_flaechendaten.value = String(f.flaechendaten || '');
      els.create_flugweg.value = String(f.flugweg || '');
      els.createFieldModalBackdrop.classList.add('show');
      setTimeout(() => {
        if (els.create_feld_id) els.create_feld_id.focus();
      }, 0);
    }

    function closeCreateFieldModal() {
      if (!els.createFieldModalBackdrop) return;
      els.createFieldModalBackdrop.classList.remove('show');
    }

    async function createFieldFromModal() {
      const feldId = String(els.create_feld_id.value || '').trim();
      const name = String(els.create_name.value || '').trim();
      if (!feldId || !name) {
        showStatus('FeldID und Feldname sind Pflichtfelder.', false);
        return;
      }
      const existing = Array.isArray(allFields)
        ? allFields.find((f) => String(f && f.feld_id || '').trim() === feldId)
        : null;
      let saveMode = 'create';
      if (existing) {
        const overwrite = window.confirm(`Feld "${feldId}" ist bereits vorhanden. Bestehendes Feld ueberschreiben?`);
        if (!overwrite) {
          showStatus('Anlegen abgebrochen. Bestehendes Feld wurde nicht ueberschrieben.', false);
          return;
        }
        saveMode = 'update';
      }
      syncJgFromFeldId('create');
      els.feld_id.value = feldId;
      els.name.value = name;
      els.jg.value = String(els.create_jg.value || '').trim();
      els.number.value = String(els.create_number.value || '').trim();
      els.std_flughoehe.value = String(els.create_std_flughoehe.value || '50').trim();
      els.startplatz.value = String(els.create_startplatz.value || '').trim();
      els.flaechendaten.value = String(els.create_flaechendaten.value || '').trim();
      els.flugweg.value = String(els.create_flugweg.value || '').trim();
      if (saveMode === 'update' && existing && existing.id) {
        els.id.value = String(existing.id);
        selectedId = String(existing.id);
      } else {
        els.id.value = '';
        selectedId = '';
      }
      syncMapFromForm();
      await save(saveMode);
      closeCreateFieldModal();
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

    async function callMissionApi(action) {
      const field = formData();
      if (action === 'export_dji_kmz') {
        try {
          field.terrain_delta_by_lonlat = await terrainDeltaMapForExport();
        } catch (_) {
          field.terrain_delta_by_lonlat = {};
        }
      }
      const r = await fetch('api_mission_export.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, field }),
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
      const edgeFactor = Number.isFinite(edgeFactorPct) ? Math.min(1, Math.max(0, edgeFactorPct / 100)) : 0.30;
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
      const edgeFactor = Number.isFinite(edgeFactorPct) ? Math.min(1, Math.max(0, edgeFactorPct / 100)) : 0.30;
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

    async function recomputePathFromFlightVorgaben(showSuccessMessage = true) {
      if (flightRecomputeRunning) {
        flightRecomputeQueued = true;
        return;
      }
      const startPts = parsePairs(els.startplatz.value);
      const polyPts = parsePairs(els.flaechendaten.value);
      if (startPts.length < 1 || polyPts.length < 3) return;
      flightRecomputeRunning = true;
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
        currentComputedPath = normalizeTripletPath(Array.isArray(data.path) ? data.path : parseTriplets(data.flugweg_text || ''));
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
        if (resetOriginalPathOnNextRecompute) {
          currentManualPath = currentComputedPath.map((p) => [p[0], p[1], p[2]]);
          originalManualPath = [];
          resetOriginalPathOnNextRecompute = false;
        } else {
          currentManualPath = manualBefore;
        }
        if (els.showAlgorithmPath) els.showAlgorithmPath.checked = true;
        const dist = Number(data.metrics && data.metrics.distance_m);
        const wps = Number(data.metrics && data.metrics.waypoints);
        if (Number.isFinite(dist)) els.distanz.value = dist.toFixed(1);
        if (Number.isFinite(wps)) els.wegpunkte.value = String(Math.round(wps));
        else if (currentComputedPath.length) els.wegpunkte.value = String(currentComputedPath.length);
        refreshFlightPathLayers();
        showStatus(forceCoverageFix
          ? 'Flugpfad berechnet (Korrektur nicht abgedeckter Flaechen forciert).'
          : 'Flugpfad neu berechnet. Anflug Start → Feld darf ausserhalb der Feldgrenze liegen.');
      } finally {
        flightRecomputeRunning = false;
        if (flightRecomputeQueued) {
          flightRecomputeQueued = false;
          setTimeout(() => {
            recomputePathFromFlightVorgaben(false).catch((e) => {
              showStatus(String(e.message || e), false);
            });
          }, 0);
        }
      }
    }

    function scheduleFlightPathRecompute() {
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
      els.msDirectToStart, els.msGimbalMode, els.msAltitudeMode,
    ];
    dirtyTrackedInputs.forEach((el) => {
      if (!el) return;
      el.addEventListener('input', updateSaveButtonState);
      el.addEventListener('change', updateSaveButtonState);
    });
    if (els.feld_id) {
      els.feld_id.addEventListener('input', () => {
        syncJgFromFeldId('main');
        updateSaveButtonState();
      });
      els.feld_id.addEventListener('change', () => {
        syncJgFromFeldId('main');
        updateSaveButtonState();
      });
    }
    if (els.create_feld_id) {
      els.create_feld_id.addEventListener('input', () => syncJgFromFeldId('create'));
      els.create_feld_id.addEventListener('change', () => syncJgFromFeldId('create'));
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
    wireImmediateRecompute(els.msSpeed);
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
    [
      els.msKmzParamsActive,
      els.msEndAction, els.msHeadingMode,
      els.msWaypointType, els.msTurnDistance, els.msSafeStartAltitude, els.msDirectToStart,
      els.msGimbalMode, els.msAltitudeMode,
    ].forEach((el) => {
      if (!el) return;
      el.addEventListener('input', scheduleFlightPathRecompute);
      el.addEventListener('change', scheduleFlightPathRecompute);
      el.addEventListener('change', saveEditorLastSettings);
    });
    els.baseMapMode.addEventListener('change', () => {
      applyBaseMapMode();
      saveEditorLastSettings();
    });
    els.showBoxMapMenu.addEventListener('change', applyBoxVisibilityStateFromSetup);
    if (els.showBoxDisplayControl) els.showBoxDisplayControl.addEventListener('change', applyBoxVisibilityStateFromSetup);
    if (els.showBoxContextHelp) els.showBoxContextHelp.addEventListener('change', applyBoxVisibilityStateFromSetup);
    els.showBoxSwissOverlay.addEventListener('change', applyBoxVisibilityStateFromSetup);
    els.showBoxMissionSettings.addEventListener('change', applyBoxVisibilityStateFromSetup);
    els.showBoxFieldCharacteristics.addEventListener('change', applyBoxVisibilityStateFromSetup);
    els.showBoxFormDrawer.addEventListener('change', applyBoxVisibilityStateFromSetup);
    if (els.swissOverlayBox) {
      els.swissOverlayBox.addEventListener('toggle', () => {
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
    window.addEventListener('beforeunload', persistUiStateNow);
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
    els.backBtn.addEventListener('click', () => {
      const from = new URLSearchParams(window.location.search).get('from') || 'analyse_ui.php';
      window.open(from, '_blank', 'noopener');
    });
    els.newBtn.addEventListener('click', clearForm);
    els.createModalCancelBtn.addEventListener('click', closeCreateFieldModal);
    els.createModalSaveBtn.addEventListener('click', async () => {
      try {
        await createFieldFromModal();
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    els.createFieldModalBackdrop.addEventListener('click', (ev) => {
      if (ev.target === els.createFieldModalBackdrop) closeCreateFieldModal();
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
      showStatus('Punktparameter gespeichert.');
    });
    if (els.wpCancelBtn) els.wpCancelBtn.addEventListener('click', () => {
      applyWaypointSnapshot();
      closeRightDrawer();
    });
    if (els.wpDeleteBtn) els.wpDeleteBtn.addEventListener('click', deleteSelectedWaypoint);
    const wpLive = [
      els.wpUseMissionHeight, els.wpAutoHeight, els.wpAltitude, els.wpAltitudeRange,
      els.wpUseMissionSpeed, els.wpSpeed, els.wpSpeedRange,
      els.wpUseMissionWaypointType, els.wpWaypointType, els.wpTurnDistance, els.wpTurnDistanceRange,
      els.wpUseMissionYaw, els.wpHeadingMode, els.wpAlignment,
      els.wpDirection, els.wpDirectionRange, els.wpPitch, els.wpPitchRange,
      els.wpLat, els.wpLng,
    ];
    wpLive.forEach((el) => {
      if (!el) return;
      el.addEventListener('input', () => {
        if (waypointFormFilling) return;
        if (el === els.wpAltitudeRange) syncWpNumberRange(els.wpAltitude, els.wpAltitudeRange, 'range');
        if (el === els.wpAltitude) syncWpNumberRange(els.wpAltitude, els.wpAltitudeRange, 'number');
        if (el === els.wpSpeedRange) syncWpNumberRange(els.wpSpeed, els.wpSpeedRange, 'range');
        if (el === els.wpSpeed) syncWpNumberRange(els.wpSpeed, els.wpSpeedRange, 'number');
        if (el === els.wpTurnDistanceRange) syncWpNumberRange(els.wpTurnDistance, els.wpTurnDistanceRange, 'range');
        if (el === els.wpTurnDistance) syncWpNumberRange(els.wpTurnDistance, els.wpTurnDistanceRange, 'number');
        if (el === els.wpDirectionRange) syncWpNumberRange(els.wpDirection, els.wpDirectionRange, 'range');
        if (el === els.wpDirection) syncWpNumberRange(els.wpDirection, els.wpDirectionRange, 'number');
        if (el === els.wpPitchRange) syncWpNumberRange(els.wpPitch, els.wpPitchRange, 'range');
        if (el === els.wpPitch) syncWpNumberRange(els.wpPitch, els.wpPitchRange, 'number');
        applyWaypointFormEnableState();
        commitWaypointFormToState();
      });
      el.addEventListener('change', () => {
        if (waypointFormFilling) return;
        applyWaypointFormEnableState();
        commitWaypointFormToState();
      });
    });
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
    if (els.showControlPoints) {
      els.showControlPoints.addEventListener('change', () => {
        refreshFlightPathLayers();
        saveEditorLastSettings();
      });
    }
    if (els.showFieldControlPoints) {
      els.showFieldControlPoints.addEventListener('change', () => {
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
        await recomputePathFromFlightVorgaben(true);
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    els.newFieldQuickBtn.addEventListener('click', () => {
      openCreateFieldModal();
    });
    els.exportUavJsonBtn.addEventListener('click', async () => {
      try {
        const data = await callMissionApi('export_uav_json');
        downloadTextFile(data.filename || 'uav_mission.json', JSON.stringify(data.payload || {}, null, 2), 'application/json');
        const hint = data.stored_path ? ` Gespeichert: ${data.stored_path}` : '';
        showStatus(`UAV JSON exportiert.${hint}`);
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    els.exportDjiKmzBtn.addEventListener('click', async () => {
      try {
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
        if (data.template_kml) {
          const kmlName = data.template_filename || String(data.filename || 'mission.kmz').replace(/\.kmz$/i, '_template.kml');
          setTimeout(() => {
            downloadTextFile(kmlName, data.template_kml, 'application/vnd.google-earth.kml+xml');
          }, 250);
        }
        const hint = data.stored_path ? ` Gespeichert: ${data.stored_path}` : '';
        const drone = data.wpml && data.wpml.drone_key ? ` Drohne ${data.wpml.drone_key}.` : '';
        showStatus(`DJI Pilot 2 KMZ erstellt.${drone}${hint} Debug: template.kml wird ebenfalls heruntergeladen.`);
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
          showStatus('Kein bestehendes Feld geladen. Bitte "Als neues Feld anlegen" verwenden.', false);
          return;
        }
        await save('update');
      } catch (e) {
        showStatus(String(e.message || e), false);
      }
    });
    els.createBtn.addEventListener('click', async () => {
      openCreateFieldModal();
    });

    (async function init() {
      try {
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
        restoreEditorLastSettings();
        syncFlightDirectionControls('number');
        syncOverlapControls('number');
        syncEdgeFactorControls('number');
        applyKmzParamsUiState();
        enforceCoverageDisplayConstraints();
        markDisabledDisplayOptions();
        setContextHelp('default');
        if (window.RkrkHelp) RkrkHelp.setView();
        updateSaveButtonState();
        pushHistory('Initial');
        await loadList();
        if (allFields.length) {
          await loadField(allFields[0].id);
        } else {
          clearForm();
        }
        persistUiStateNow();
      } catch (e) {
        showStatus(`Init fehlgeschlagen: ${String(e.message || e)}`, false);
      }
    })();
  </script>
</body>
</html>


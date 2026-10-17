<?php
require_once __DIR__ . '/map_env.php';
$helpTitle = function_exists('map_overlay_editor_title')
    ? (map_overlay_editor_title() . ' Hilfe')
    : 'Overlay-Editor Hilfe';
$h = static function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
?><!doctype html>
<html lang="de" data-rkrk-layout="off">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo $h($helpTitle); ?></title>
  <link rel="stylesheet" href="rkrk_help.css" />
  <style>
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f6f8; color: #23384d; }
    .topnav { padding: 12px 16px; background: #fff; border-bottom: 1px solid #d9dde2; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .topnav a { color: #1f78d1; text-decoration: none; font-size: 13px; }
  </style>
</head>
<body>
  <div class="topnav">
    <strong><?php echo $h($helpTitle); ?></strong>
    <a href="rkrk_map_overlay.php">Editor</a>
  </div>
  <div id="helpRoot"></div>
  <script src="rkrk_env_branding.js?v=20260912title"></script>
  <script src="rkrk_help_catalog.js?v=20261002help"></script>
  <script src="rkrk_help.js?v=20261002help"></script>
  <script>
    (function () {
      var t = (typeof rkrOverlayEditorTitle === "function") ? rkrOverlayEditorTitle() : "Overlay-Editor";
      document.title = t + " Hilfe";
    })();
    RkrkHelp.renderStandalone("#helpRoot");
  </script>
</body>
</html>

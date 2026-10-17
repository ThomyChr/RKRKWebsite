<?php
?><!doctype html>
<html lang="de" data-rkrk-layout="off">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>RKRK Hilfe</title>
  <link rel="stylesheet" href="rkrk_help.css" />
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f8; color: #23384d; }
    .topnav { padding: 12px 16px; background: #fff; border-bottom: 1px solid #d9dde2; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .topnav a { color: #1f78d1; text-decoration: none; font-size: 13px; }
  </style>
</head>
<body>
  <div class="topnav">
    <strong>RKRK Hilfe</strong>
    <a href="rkrk_feld_editor.php">Feld-Editor</a>
    <a href="rkrk_feld_editor_rc.php">Fernsteuerung</a>
    <a href="analyse_ui.php">Algo-Analysator</a>
  </div>
  <div id="helpRoot"></div>
  <script src="rkrk_help_catalog.js?v=20261014easy"></script>
  <script src="rkrk_help.js"></script>
  <script>
    RkrkHelp.renderStandalone('#helpRoot');
  </script>
</body>
</html>

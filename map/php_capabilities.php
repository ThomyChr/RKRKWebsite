<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store");

$info = [
  "ok" => true,
  "checked_at" => gmdate("c"),
  "php_version" => PHP_VERSION,
  "sapi" => PHP_SAPI,
  "server_software" => $_SERVER["SERVER_SOFTWARE"] ?? "",
  "host" => $_SERVER["HTTP_HOST"] ?? "",
  "document_root" => $_SERVER["DOCUMENT_ROOT"] ?? "",
  "sqlite3_class" => class_exists("SQLite3"),
  "pdo" => class_exists("PDO"),
  "pdo_drivers" => class_exists("PDO") ? PDO::getAvailableDrivers() : [],
  "pdo_sqlite" => false,
  "loaded_sqlite_related" => [],
  "temp_dir" => sys_get_temp_dir(),
  "temp_writable" => is_writable(sys_get_temp_dir()),
];

foreach (get_loaded_extensions() as $ext) {
  if (stripos($ext, "sqlite") !== false || stripos($ext, "pdo") !== false) {
    $info["loaded_sqlite_related"][] = $ext;
  }
}

if (class_exists("PDO")) {
  $info["pdo_sqlite"] = in_array("sqlite", PDO::getAvailableDrivers(), true)
    || in_array("sqlite3", PDO::getAvailableDrivers(), true);
}

$info["sqlite_write_test"] = null;
try {
  if (class_exists("SQLite3")) {
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "rkrk_sqlite_probe_" . getmypid() . ".db";
    $db = new SQLite3($path);
    $db->exec("CREATE TABLE t(id INTEGER); INSERT INTO t VALUES (1);");
    $n = $db->querySingle("SELECT COUNT(*) FROM t");
    $db->close();
    @unlink($path);
    $info["sqlite_write_test"] = ["engine" => "SQLite3", "ok" => ((int)$n === 1)];
  } elseif (!empty($info["pdo_sqlite"])) {
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "rkrk_sqlite_probe_" . getmypid() . ".db";
    $pdo = new PDO("sqlite:" . $path);
    $pdo->exec("CREATE TABLE t(id INTEGER); INSERT INTO t VALUES (1);");
    $n = (int)$pdo->query("SELECT COUNT(*) FROM t")->fetchColumn();
    $pdo = null;
    @unlink($path);
    $info["sqlite_write_test"] = ["engine" => "PDO_SQLITE", "ok" => ($n === 1)];
  } else {
    $info["sqlite_write_test"] = ["engine" => null, "ok" => false, "error" => "no sqlite engine"];
  }
} catch (Throwable $e) {
  $info["sqlite_write_test"] = ["ok" => false, "error" => $e->getMessage()];
}

$json = json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@file_put_contents(__DIR__ . "/php_capabilities_result.json", $json);
@file_put_contents(__DIR__ . "/data/php_capabilities_result.json", $json);
echo $json;

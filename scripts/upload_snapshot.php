<?php

header("Content-Type: application/json; charset=UTF-8");

$projectRoot = rtrim(str_replace("\\", "/", dirname(__DIR__, 2)), "/");
$pathInput = trim((string)($_POST["path"] ?? ""));
$pathInput = str_replace("\\", "/", $pathInput);

if ($pathInput === "") {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "missing path"]);
    exit;
}

$isAbsolute = (strpos($pathInput, "/") === 0) || (preg_match("/^[A-Za-z]:\//", $pathInput) === 1);
if ($isAbsolute) {
    $targetDir = rtrim($pathInput, "/");
} else {
    $relative = ltrim($pathInput, "/.");
    $targetDir = $projectRoot . "/" . $relative;
}

$logLine = date("Y-m-d H:i:s") . " | pathInput=" . $pathInput . " | targetDir=" . $targetDir . PHP_EOL;
@file_put_contents(__DIR__ . "/_upload_snapshot.log", $logLine, FILE_APPEND);

if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "cannot create directory", "targetDir" => $targetDir]);
    exit;
}

if (empty($_FILES["file"]["tmp_name"])) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "no file received"]);
    exit;
}

$filename = basename((string)$_FILES["file"]["name"]);
$targetFile = $targetDir . "/" . $filename;

if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
    echo json_encode(["ok" => true, "file" => $targetFile]);
} else {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "cannot save file", "file" => $targetFile]);
}
?>
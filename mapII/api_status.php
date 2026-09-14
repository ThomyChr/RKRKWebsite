<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/analysis_lib.php';

$xmlPath = resolve_fields_xml_path();
$xmlExists = is_file($xmlPath);
$fields = $xmlExists ? load_fields_xml($xmlPath) : [];

$pythonOk = false;
$pythonVersion = '';
$pyOut = [];
$pyCode = 1;
exec('python --version 2>&1', $pyOut, $pyCode);
if ($pyCode === 0) {
    $pythonOk = true;
    $pythonVersion = trim(implode(' ', $pyOut));
}

echo json_encode([
    'xml_exists' => $xmlExists,
    'xml_path' => $xmlPath,
    'field_count' => count($fields),
    'python_ok' => $pythonOk,
    'python_version' => $pythonVersion,
], JSON_UNESCAPED_UNICODE);


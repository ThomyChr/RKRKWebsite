<?php
header('Content-Type: application/json; charset=utf-8');

$path = __DIR__ . '/requirements_data.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!is_file($path)) {
        http_response_code(404);
        echo json_encode(['error' => 'requirements_file_missing']);
        exit;
    }
    $raw = file_get_contents($path);
    echo $raw !== false ? $raw : json_encode(['error' => 'read_failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_json']);
        exit;
    }

    if (!isset($data['meta']) || !isset($data['core_parameters']) || !isset($data['requirements'])) {
        http_response_code(422);
        echo json_encode(['error' => 'missing_required_sections']);
        exit;
    }

    $data['meta']['last_updated'] = date('Y-m-d');
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        http_response_code(500);
        echo json_encode(['error' => 'encode_failed']);
        exit;
    }

    $ok = file_put_contents($path, $encoded);
    if ($ok === false) {
        http_response_code(500);
        echo json_encode(['error' => 'write_failed']);
        exit;
    }
    echo json_encode(['ok' => true, 'last_updated' => $data['meta']['last_updated']]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);


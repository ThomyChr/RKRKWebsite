<?php
header('Content-Type: application/json; charset=utf-8');

$path = __DIR__ . '/step_requirements_data.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!is_file($path)) {
        http_response_code(404);
        echo json_encode(['error' => 'file_missing']);
        exit;
    }
    $raw = file_get_contents($path);
    echo $raw !== false ? $raw : json_encode(['error' => 'read_failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    if (!is_array($data) || !isset($data['steps']) || !is_array($data['steps'])) {
        http_response_code(422);
        echo json_encode(['error' => 'invalid_payload']);
        exit;
    }
    if (!isset($data['meta']) || !is_array($data['meta'])) {
        $data['meta'] = [];
    }
    $data['meta']['last_updated'] = date('Y-m-d');
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false || file_put_contents($path, $encoded) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed']);
        exit;
    }
    echo json_encode(['ok' => true, 'last_updated' => $data['meta']['last_updated']]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);


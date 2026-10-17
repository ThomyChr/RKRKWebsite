<?php
header('Content-Type: application/json; charset=utf-8');

$path = __DIR__ . '/models_data.json';

function load_models_data(string $path): array
{
    if (!is_file($path)) {
        return ['meta' => ['version' => '1.0', 'last_updated' => date('Y-m-d')], 'models' => []];
    }
    $raw = file_get_contents($path);
    $data = $raw ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        return ['meta' => ['version' => '1.0', 'last_updated' => date('Y-m-d')], 'models' => []];
    }
    if (!isset($data['models']) || !is_array($data['models'])) {
        $data['models'] = [];
    }
    if (!isset($data['meta']) || !is_array($data['meta'])) {
        $data['meta'] = ['version' => '1.0', 'last_updated' => date('Y-m-d')];
    }
    return $data;
}

function save_models_data(string $path, array $data): bool
{
    $data['meta']['last_updated'] = date('Y-m-d');
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return false;
    }
    return file_put_contents($path, $encoded) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(load_models_data($path), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_json']);
        exit;
    }
    $action = strtolower(trim((string)($payload['action'] ?? '')));
    $data = load_models_data($path);

    if ($action === 'upsert') {
        $model = $payload['model'] ?? null;
        if (!is_array($model)) {
            http_response_code(422);
            echo json_encode(['error' => 'missing_model']);
            exit;
        }
        $id = trim((string)($model['id'] ?? ''));
        $name = trim((string)($model['name'] ?? ''));
        if ($id === '' || $name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'missing_model_id_or_name']);
            exit;
        }
        $found = false;
        foreach ($data['models'] as &$m) {
            if ((string)$m['id'] === $id) {
                $m = $model;
                $found = true;
                break;
            }
        }
        unset($m);
        if (!$found) {
            $data['models'][] = $model;
        }
        if (!save_models_data($path, $data)) {
            http_response_code(500);
            echo json_encode(['error' => 'save_failed']);
            exit;
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete') {
        $id = trim((string)($payload['id'] ?? ''));
        if ($id === '' || $id === 'default') {
            http_response_code(422);
            echo json_encode(['error' => 'invalid_model_id']);
            exit;
        }
        $before = count($data['models']);
        $data['models'] = array_values(array_filter($data['models'], static function ($m) use ($id) {
            return (string)($m['id'] ?? '') !== $id;
        }));
        if ($before === count($data['models'])) {
            http_response_code(404);
            echo json_encode(['error' => 'model_not_found']);
            exit;
        }
        if (!save_models_data($path, $data)) {
            http_response_code(500);
            echo json_encode(['error' => 'save_failed']);
            exit;
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'create_from_requirements') {
        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'missing_name']);
            exit;
        }
        $reqPath = __DIR__ . '/requirements_data.json';
        if (!is_file($reqPath)) {
            http_response_code(404);
            echo json_encode(['error' => 'requirements_file_missing']);
            exit;
        }
        $reqRaw = file_get_contents($reqPath);
        $req = $reqRaw ? json_decode($reqRaw, true) : null;
        if (!is_array($req) || !isset($req['core_parameters']) || !is_array($req['core_parameters'])) {
            http_response_code(422);
            echo json_encode(['error' => 'invalid_requirements_data']);
            exit;
        }
        $kv = [];
        foreach ($req['core_parameters'] as $p) {
            $k = trim((string)($p['key'] ?? ''));
            if ($k === '') {
                continue;
            }
            $kv[$k] = $p['value'] ?? null;
        }

        // Derive effective model settings from free-text requirements.
        $derivedEdgeFactor = null;
        $derivedDirection = 'auto';
        $derivedFixedTheta = null;
        $requirements = isset($req['requirements']) && is_array($req['requirements']) ? $req['requirements'] : [];
        foreach ($requirements as $r) {
            $txt = strtolower(trim((string)(($r['formula'] ?? '') . ' ' . ($r['description'] ?? '') . ' ' . ($r['name'] ?? ''))));
            if ($txt === '') {
                continue;
            }
            if (preg_match('/d_rand\s*<=\s*([0-9]+(?:\.[0-9]+)?)\s*\*\s*s/i', $txt, $m)) {
                $derivedEdgeFactor = floatval($m[1]);
            } elseif (preg_match('/rand(?:abstand|faktor)[^0-9]*([0-9]+(?:\.[0-9]+)?)/i', $txt, $m)) {
                $v = floatval($m[1]);
                if ($v > 0 && $v <= 1.0) {
                    $derivedEdgeFactor = $v;
                }
            }

            if (strpos($txt, 'richtung x') !== false || strpos($txt, 'bahnen x') !== false) {
                $derivedDirection = 'x';
                $derivedFixedTheta = 0.0;
            } elseif (strpos($txt, 'richtung y') !== false || strpos($txt, 'bahnen y') !== false) {
                $derivedDirection = 'y';
                $derivedFixedTheta = 90.0;
            } elseif (preg_match('/winkel[^0-9]*([0-9]{1,3}(?:\.[0-9]+)?)/i', $txt, $m)) {
                $derivedDirection = 'custom';
                $derivedFixedTheta = floatval($m[1]);
            }
        }

        $id = 'm_' . time();
        $model = [
            'id' => $id,
            'name' => $name,
            'description' => 'Aus requirements_data.json erstellt',
            'params' => [
                'hfov' => isset($kv['HFOV_deg']) ? floatval($kv['HFOV_deg']) : 48.0,
                'overlap' => isset($kv['overlap']) ? floatval($kv['overlap']) : 0.15,
                'edge_factor' => $derivedEdgeFactor !== null
                    ? $derivedEdgeFactor
                    : (isset($kv['edge_factor']) ? floatval($kv['edge_factor']) : 0.40),
                'speed' => isset($kv['speed_m_s']) ? floatval($kv['speed_m_s']) : 8.0,
                'agl_tolerance_m' => isset($kv['agl_tolerance_m']) ? floatval($kv['agl_tolerance_m']) : 10.0,
                'angle_step_deg' => isset($kv['angle_step_deg']) ? intval($kv['angle_step_deg']) : 5,
                'target_step_m' => isset($kv['target_step_m']) ? floatval($kv['target_step_m']) : 2.0,
                'max_nb_points' => isset($kv['max_nb_points']) ? intval($kv['max_nb_points']) : 5000,
                'min_delay_s' => isset($kv['min_delay_s']) ? floatval($kv['min_delay_s']) : 0.05,
                'preferred_direction' => $derivedDirection,
                'fixed_theta_deg' => $derivedFixedTheta
            ]
        ];
        $data['models'][] = $model;
        if (!save_models_data($path, $data)) {
            http_response_code(500);
            echo json_encode(['error' => 'save_failed']);
            exit;
        }
        echo json_encode(['ok' => true, 'model_id' => $id]);
        exit;
    }

    http_response_code(422);
    echo json_encode(['error' => 'unknown_action']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);


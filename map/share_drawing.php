<?php
declare(strict_types=1);

/**
 * Live-Follow / Multi-Editor share API:
 *   POST { token?, drawing, active?, multi_edit?, editor_id?, editor_name? }
 *   POST { token, ping: true, active?, editor_id?, editor_name? }
 *   POST { token, announce: { text, editor_id?, editor_name? } }
 *   POST { token, claim_view_leader: { editor_id, editor_name?, mapView } }
 *   POST { token, claim_lock: { object_id, editor_id, name?, ttl_sec? } }
 *   POST { token, release_lock: { object_id, editor_id } }
 *   POST { token, renew_locks: { editor_id, object_ids?: [] } }
 *   GET  ?token=&since=&viewer_id=&editor_id=&editor_name=
 *   GET  ?token=&announce_poll=1
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, If-None-Match');
header('Cache-Control: no-store, max-age=0');

require_once __DIR__ . '/map_env.php';
$__rkrkToolsAuth = __DIR__ . '/../rkrk_tools_auth.php';
if (is_file($__rkrkToolsAuth)) {
    require_once $__rkrkToolsAuth;
} elseif (is_file(__DIR__ . '/rkrk_tools_auth.php')) {
    require_once __DIR__ . '/rkrk_tools_auth.php';
}
unset($__rkrkToolsAuth);

function share_respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function share_sanitize_token(string $token): string
{
    $token = strtolower(trim($token));
    $token = preg_replace('/[^a-z0-9]/', '', $token) ?? '';
    if (strlen($token) < 8 || strlen($token) > 64) {
        return '';
    }
    return $token;
}

function share_sanitize_viewer_id(string $id): string
{
    $id = strtolower(trim($id));
    $id = preg_replace('/[^a-z0-9_-]/', '', $id) ?? '';
    if (strlen($id) < 8 || strlen($id) > 64) {
        return '';
    }
    return $id;
}

function share_sanitize_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }
    if (function_exists('mb_substr')) {
        $name = mb_substr($name, 0, 40);
    } else {
        $name = substr($name, 0, 40);
    }
    return $name;
}

function share_new_token(): string
{
    return bin2hex(random_bytes(16));
}

function share_file_path(string $token): ?string
{
    $dir = map_data_dir(true);
    if ($dir === null || !is_dir($dir)) {
        return null;
    }
    return $dir . DIRECTORY_SEPARATOR . 'share_' . $token . '.json';
}

function share_find_file(string $token): ?string
{
    foreach (map_data_dir_candidates(false) as $dir) {
        $try = $dir . DIRECTORY_SEPARATOR . 'share_' . $token . '.json';
        if (is_file($try) && is_readable($try)) {
            return $try;
        }
    }
    return null;
}

function share_cleanup_old(int $maxAgeSeconds = 7 * 24 * 60 * 60): void
{
    $now = time();
    foreach (map_data_dir_candidates(false) as $dir) {
        $files = @glob(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'share_*.json');
        if (!is_array($files)) {
            continue;
        }
        foreach ($files as $file) {
            $mtime = @filemtime($file);
            if ($mtime !== false && ($now - $mtime) > $maxAgeSeconds) {
                @unlink($file);
            }
        }
    }
}

function share_parse_time($seen): int
{
    if (is_string($seen) && $seen !== '') {
        $parsed = strtotime($seen);
        return $parsed !== false ? $parsed : 0;
    }
    if (is_int($seen) || is_float($seen)) {
        return (int)$seen;
    }
    return 0;
}

/** Keep viewers seen within the last N seconds; return count. */
function share_prune_viewers(array &$payload, int $maxAgeSec = 18): int
{
    $viewers = (isset($payload['viewers']) && is_array($payload['viewers'])) ? $payload['viewers'] : [];
    $now = time();
    $kept = [];
    foreach ($viewers as $id => $seen) {
        $idKey = share_sanitize_viewer_id((string)$id);
        if ($idKey === '') {
            continue;
        }
        $t = share_parse_time($seen);
        if ($t > 0 && ($now - $t) <= $maxAgeSec) {
            $kept[$idKey] = is_string($seen) && $seen !== '' ? $seen : gmdate('c', $t);
        }
    }
    $payload['viewers'] = $kept;
    return count($kept);
}

function share_touch_viewer(array &$payload, string $viewerId): int
{
    $viewerId = share_sanitize_viewer_id($viewerId);
    if ($viewerId === '') {
        return share_prune_viewers($payload);
    }
    if (!isset($payload['viewers']) || !is_array($payload['viewers'])) {
        $payload['viewers'] = [];
    }
    $payload['viewers'][$viewerId] = gmdate('c');
    return share_prune_viewers($payload);
}

function share_prune_editors(array &$payload, int $maxAgeSec = 45): int
{
    $editors = (isset($payload['editors']) && is_array($payload['editors'])) ? $payload['editors'] : [];
    $now = time();
    $kept = [];
    foreach ($editors as $id => $info) {
        $idKey = share_sanitize_viewer_id((string)$id);
        if ($idKey === '') {
            continue;
        }
        $seen = is_array($info) ? ($info['seen_at'] ?? '') : $info;
        $t = share_parse_time($seen);
        if ($t > 0 && ($now - $t) <= $maxAgeSec) {
            $name = is_array($info) ? share_sanitize_name((string)($info['name'] ?? '')) : '';
            $kept[$idKey] = [
                'name' => $name !== '' ? $name : ('Editor-' . substr($idKey, -4)),
                'seen_at' => is_string($seen) && $seen !== '' ? $seen : gmdate('c', $t),
            ];
        }
    }
    $payload['editors'] = $kept;
    return count($kept);
}

function share_touch_editor(array &$payload, string $editorId, string $name = ''): int
{
    $editorId = share_sanitize_viewer_id($editorId);
    if ($editorId === '') {
        return share_prune_editors($payload);
    }
    $mode = share_get_editor_mode($payload, $editorId);
    // Gekickt: nicht als Editor führen
    if ($mode === 'kicked') {
        if (isset($payload['editors'][$editorId])) {
            unset($payload['editors'][$editorId]);
        }
        return share_prune_editors($payload);
    }
    // Nur Share (Viewer): aus Editor-Liste entfernen, als Zuschauer zählen
    if ($mode === 'viewer') {
        if (isset($payload['editors'][$editorId])) {
            unset($payload['editors'][$editorId]);
        }
        share_touch_viewer($payload, $editorId);
        return share_prune_editors($payload);
    }
    if (!isset($payload['editors']) || !is_array($payload['editors'])) {
        $payload['editors'] = [];
    }
    $cleanName = share_sanitize_name($name);
    $prev = (isset($payload['editors'][$editorId]) && is_array($payload['editors'][$editorId]))
        ? $payload['editors'][$editorId]
        : [];
    if ($cleanName === '' && isset($prev['name'])) {
        $cleanName = share_sanitize_name((string)$prev['name']);
    }
    if ($cleanName === '') {
        $cleanName = 'Editor-' . substr($editorId, -4);
    }
    $payload['editors'][$editorId] = [
        'name' => $cleanName,
        'seen_at' => gmdate('c'),
    ];
    return share_prune_editors($payload);
}

/** Object sync timestamp (ms-friendly ISO or numeric). */

function share_editor_control(array &$payload): array
{
    if (!isset($payload['editor_control']) || !is_array($payload['editor_control'])) {
        $payload['editor_control'] = [];
    }
    return $payload['editor_control'];
}

function share_get_editor_mode(array $payload, string $editorId): string
{
    $editorId = share_sanitize_viewer_id($editorId);
    if ($editorId === '') {
        return 'active';
    }
    $ctrl = (isset($payload['editor_control']) && is_array($payload['editor_control']))
        ? $payload['editor_control'] : [];
    if (!isset($ctrl[$editorId]) || !is_array($ctrl[$editorId])) {
        return 'active';
    }
    $mode = strtolower(trim((string)($ctrl[$editorId]['mode'] ?? 'active')));
    if ($mode === 'viewer' || $mode === 'kicked') {
        return $mode;
    }
    return 'active';
}

function share_is_host_editor(array $payload, string $editorId): bool
{
    $editorId = share_sanitize_viewer_id($editorId);
    if ($editorId === '') {
        return false;
    }
    $host = share_sanitize_viewer_id((string)($payload['host_editor_id'] ?? ''));
    return $host !== '' && $host === $editorId;
}

function share_set_host_editor(array &$payload, string $editorId): void
{
    $editorId = share_sanitize_viewer_id($editorId);
    if ($editorId === '') {
        return;
    }
    if (empty($payload['host_editor_id'])) {
        $payload['host_editor_id'] = $editorId;
    }
}

function share_editors_list(array $payload): array
{
    $out = [];
    $host = share_sanitize_viewer_id((string)($payload['host_editor_id'] ?? ''));
    $editors = (isset($payload['editors']) && is_array($payload['editors'])) ? $payload['editors'] : [];
    foreach ($editors as $id => $info) {
        $idKey = share_sanitize_viewer_id((string)$id);
        if ($idKey === '') {
            continue;
        }
        $name = is_array($info) ? share_sanitize_name((string)($info['name'] ?? '')) : '';
        if ($name === '') {
            $name = 'Editor-' . substr($idKey, -4);
        }
        $mode = share_get_editor_mode($payload, $idKey);
        $role = ($host !== '' && $host === $idKey) ? 'host' : 'sub';
        $out[] = [
            'id' => $idKey,
            'name' => $name,
            'role' => $role,
            'mode' => $mode,
            'seen_at' => is_array($info) ? (string)($info['seen_at'] ?? '') : '',
        ];
    }
    // Also list controlled editors not currently in prune window (viewer/kicked)
    $ctrl = (isset($payload['editor_control']) && is_array($payload['editor_control']))
        ? $payload['editor_control'] : [];
    $have = [];
    foreach ($out as $row) {
        $have[$row['id']] = true;
    }
    foreach ($ctrl as $id => $info) {
        $idKey = share_sanitize_viewer_id((string)$id);
        if ($idKey === '' || isset($have[$idKey])) {
            continue;
        }
        $mode = share_get_editor_mode($payload, $idKey);
        if ($mode === 'active') {
            continue;
        }
        $name = is_array($info) ? share_sanitize_name((string)($info['name'] ?? '')) : '';
        if ($name === '') {
            $name = 'Editor-' . substr($idKey, -4);
        }
        $out[] = [
            'id' => $idKey,
            'name' => $name,
            'role' => ($host !== '' && $host === $idKey) ? 'host' : 'sub',
            'mode' => $mode,
            'seen_at' => is_array($info) ? (string)($info['at'] ?? '') : '',
        ];
    }
    return $out;
}

function share_apply_editor_moderation(array &$payload, string $targetId, string $action, string $hostId, string $name = ''): bool
{
    $targetId = share_sanitize_viewer_id($targetId);
    $hostId = share_sanitize_viewer_id($hostId);
    $action = strtolower(trim($action));
    if ($targetId === '' || $hostId === '' || !share_is_host_editor($payload, $hostId)) {
        return false;
    }
    if ($targetId === $hostId) {
        return false; // Master nicht selbst moderieren
    }
    if (!in_array($action, ['viewer', 'kick', 'restore'], true)) {
        return false;
    }
    $ctrl = share_editor_control($payload);
    if ($action === 'restore') {
        unset($ctrl[$targetId]);
        $payload['editor_control'] = $ctrl;
        return true;
    }
    $prevName = '';
    if (isset($payload['editors'][$targetId]) && is_array($payload['editors'][$targetId])) {
        $prevName = share_sanitize_name((string)($payload['editors'][$targetId]['name'] ?? ''));
    }
    if ($name === '') {
        $name = $prevName;
    }
    $ctrl[$targetId] = [
        'mode' => $action,
        'at' => gmdate('c'),
        'by' => $hostId,
        'name' => $name !== '' ? share_sanitize_name($name) : ('Editor-' . substr($targetId, -4)),
    ];
    $payload['editor_control'] = $ctrl;
    if ($action === 'kick' || $action === 'viewer') {
        // Locks dieses Editors freigeben
        $locks = (isset($payload['locks']) && is_array($payload['locks'])) ? $payload['locks'] : [];
        foreach ($locks as $oid => $lock) {
            if (!is_array($lock)) {
                continue;
            }
            if (share_sanitize_viewer_id((string)($lock['editor_id'] ?? '')) === $targetId) {
                unset($locks[$oid]);
            }
        }
        $payload['locks'] = $locks;
        if ($action === 'kick' && isset($payload['editors'][$targetId])) {
            unset($payload['editors'][$targetId]);
        }
    }
    return true;
}

function share_object_sync_ts($obj): int
{
    if (!is_array($obj)) {
        return 0;
    }
    foreach (['sync_at', 'updated_at', 'fixed_at'] as $key) {
        $raw = $obj[$key] ?? null;
        if (is_numeric($raw)) {
            $n = (int)$raw;
            return $n > 100000000000 ? (int)floor($n / 1000) : $n;
        }
        if (is_string($raw) && $raw !== '') {
            $t = share_parse_time($raw);
            if ($t > 0) {
                return $t;
            }
        }
    }
    return 0;
}

/**
 * Smart merge of managedObjects: newer sync wins; locks protect other editors;
 * objects unknown to the pusher are kept (no silent wipe from Master→Sub).
 */
function share_merge_managed_objects(array $existingObjs, array $incomingObjs, string $editorId, array $locks): array
{
    $byId = [];
    foreach ($existingObjs as $obj) {
        if (!is_array($obj) || !isset($obj['id'])) {
            continue;
        }
        $id = (string)$obj['id'];
        if ($id === '') {
            continue;
        }
        $byId[$id] = $obj;
    }
    $incomingIds = [];
    foreach ($incomingObjs as $obj) {
        if (!is_array($obj) || !isset($obj['id'])) {
            continue;
        }
        $id = (string)$obj['id'];
        if ($id === '') {
            continue;
        }
        $incomingIds[$id] = true;
        $lock = (isset($locks[$id]) && is_array($locks[$id])) ? $locks[$id] : null;
        $holder = $lock ? share_sanitize_viewer_id((string)($lock['editor_id'] ?? '')) : '';
        // Locked by someone else → keep existing (do not overwrite with pusher state)
        if ($holder !== '' && $editorId !== '' && $holder !== $editorId && isset($byId[$id])) {
            continue;
        }
        if (!isset($byId[$id])) {
            $byId[$id] = $obj;
            continue;
        }
        $prevTs = share_object_sync_ts($byId[$id]);
        $nextTs = share_object_sync_ts($obj);
        $prevBy = share_sanitize_viewer_id((string)($byId[$id]['updated_by'] ?? ''));
        // Other editor has a newer version → keep it
        if ($prevBy !== '' && $editorId !== '' && $prevBy !== $editorId && $prevTs > $nextTs) {
            continue;
        }
        // Prefer incoming when newer or owned/unstamped
        if ($nextTs >= $prevTs || $prevBy === '' || $prevBy === $editorId) {
            $mergedObj = $obj;
            // Raster-Kachelfarben zusammenfuehren
            if (isset($mergedObj['type']) && $mergedObj['type'] === 'grid') {
                $prevFills = (isset($byId[$id]['cellFills']) && is_array($byId[$id]['cellFills']))
                    ? $byId[$id]['cellFills'] : [];
                $nextFills = (isset($mergedObj['cellFills']) && is_array($mergedObj['cellFills']))
                    ? $mergedObj['cellFills'] : [];
                if ($nextTs >= $prevTs) {
                    $mergedObj['cellFills'] = array_merge($prevFills, $nextFills);
                } else {
                    $mergedObj['cellFills'] = array_merge($nextFills, $prevFills);
                }
            }
            $byId[$id] = $mergedObj;
        } elseif (isset($byId[$id]['type']) && $byId[$id]['type'] === 'grid'
            && isset($obj['cellFills']) && is_array($obj['cellFills'])) {
            // Incoming aelter, aber ggf. neue Kachelfarben vom Pusher ergaenzen wenn Pusher Owner
            if ($editorId !== '' && ($prevBy === '' || $prevBy === $editorId || share_sanitize_viewer_id((string)($obj['updated_by'] ?? '')) === $editorId)) {
                $prevFills = (isset($byId[$id]['cellFills']) && is_array($byId[$id]['cellFills']))
                    ? $byId[$id]['cellFills'] : [];
                $byId[$id]['cellFills'] = array_merge($prevFills, $obj['cellFills']);
            }
        }
    }
    // Missing from incoming: keep unless pusher authored it (they deleted) and not locked by other
    foreach (array_keys($byId) as $id) {
        if (isset($incomingIds[$id])) {
            continue;
        }
        $lock = (isset($locks[$id]) && is_array($locks[$id])) ? $locks[$id] : null;
        $holder = $lock ? share_sanitize_viewer_id((string)($lock['editor_id'] ?? '')) : '';
        if ($holder !== '' && $editorId !== '' && $holder !== $editorId) {
            continue; // keep locked-by-other
        }
        $prevBy = share_sanitize_viewer_id((string)($byId[$id]['updated_by'] ?? ''));
        $delType = (string)($byId[$id]['type'] ?? '');
        $delPointish = ($delType === 'points' || $delType === 'textfields');
        // Punkte/Text: jeder Editor darf löschen; sonst nur Owner
        if ($delPointish || ($editorId !== '' && $prevBy !== '' && $prevBy === $editorId) || $prevBy === '') {
            unset($byId[$id]);
        }
        // else keep
    }
    // Reihenfolge der Incoming-Liste behalten (Drag-and-Drop / Z-Order), danach Rest aus Existing
    $ordered = [];
    $seen = [];
    foreach ($incomingObjs as $obj) {
        if (!is_array($obj) || !isset($obj['id'])) {
            continue;
        }
        $id = (string)$obj['id'];
        if ($id === '' || !isset($byId[$id]) || isset($seen[$id])) {
            continue;
        }
        $ordered[] = $byId[$id];
        $seen[$id] = true;
    }
    foreach ($byId as $id => $obj) {
        if (isset($seen[$id])) {
            continue;
        }
        $ordered[] = $obj;
        $seen[$id] = true;
    }
    return $ordered;
}


/**
 * Unfixierte Objekte nur dem Origin-Editor zeigen (updated_by).
 * Viewer / andere Editoren sehen sie erst nach Fixieren.
 */
function share_filter_unfixed_for_recipient($drawing, string $editorId)
{
    if (!is_array($drawing)) {
        return $drawing;
    }
    if (!isset($drawing['managedObjects']) || !is_array($drawing['managedObjects'])) {
        return $drawing;
    }
    $out = [];
    foreach ($drawing['managedObjects'] as $obj) {
        if (!is_array($obj)) {
            continue;
        }
        $isFixed = !array_key_exists('fixed', $obj) || !empty($obj['fixed']);
        if ($isFixed) {
            $out[] = $obj;
            continue;
        }
        $by = share_sanitize_viewer_id((string)($obj['updated_by'] ?? ''));
        if ($editorId !== '' && $by !== '' && $by === $editorId) {
            $out[] = $obj;
        }
        // sonst: Entwurf fremd / Viewer → ausblenden
    }
    $drawing['managedObjects'] = array_values($out);
    return $drawing;
}

function share_merge_drawing(array $existingDrawing, array $incomingDrawing, string $editorId, array $locks): array
{
    $out = $incomingDrawing;
    $existObjs = (isset($existingDrawing['managedObjects']) && is_array($existingDrawing['managedObjects']))
        ? $existingDrawing['managedObjects']
        : [];
    $inObjs = (isset($incomingDrawing['managedObjects']) && is_array($incomingDrawing['managedObjects']))
        ? $incomingDrawing['managedObjects']
        : [];
    $merged = share_merge_managed_objects($existObjs, $inObjs, $editorId, $locks);
    $out['managedObjects'] = $merged;
    $maxId = 0;
    foreach ($merged as $obj) {
        if (is_array($obj)) {
            $maxId = max($maxId, (int)($obj['id'] ?? 0));
        }
    }
    $seqIn = (int)($incomingDrawing['managedObjectIdSeq'] ?? 0);
    $seqEx = (int)($existingDrawing['managedObjectIdSeq'] ?? 0);
    $out['managedObjectIdSeq'] = max($seqIn, $seqEx, $maxId + 1);
    // mapView: only the current view_leader may change it; others keep existing
    $leaderId = share_sanitize_viewer_id((string)($GLOBALS['__share_view_leader_id'] ?? ''));
    $canSetView = ($editorId !== '' && $leaderId !== '' && $editorId === $leaderId)
        || ($leaderId === '' && $editorId !== ''); // no leader yet → first pusher / host
    if ($canSetView && isset($incomingDrawing['mapView']) && is_array($incomingDrawing['mapView'])) {
        $out['mapView'] = $incomingDrawing['mapView'];
    } elseif (isset($existingDrawing['mapView']) && is_array($existingDrawing['mapView'])) {
        $out['mapView'] = $existingDrawing['mapView'];
    }
    if (isset($incomingDrawing['mapChrome']) && is_array($incomingDrawing['mapChrome'])) {
        $out['mapChrome'] = $incomingDrawing['mapChrome'];
    } elseif (isset($existingDrawing['mapChrome']) && is_array($existingDrawing['mapChrome'])) {
        $out['mapChrome'] = $existingDrawing['mapChrome'];
    }
    return $out;
}

function share_prune_locks(array &$payload): array
{
    $locks = (isset($payload['locks']) && is_array($payload['locks'])) ? $payload['locks'] : [];
    $now = time();
    $kept = [];
    foreach ($locks as $objId => $lock) {
        if (!is_array($lock)) {
            continue;
        }
        $oid = trim((string)$objId);
        if ($oid === '') {
            continue;
        }
        $editorId = share_sanitize_viewer_id((string)($lock['editor_id'] ?? ''));
        if ($editorId === '') {
            continue;
        }
        $exp = share_parse_time($lock['expires_at'] ?? '');
        if ($exp > 0 && $exp < $now) {
            continue;
        }
        $name = share_sanitize_name((string)($lock['name'] ?? ''));
        if ($name === '') {
            $name = 'Editor-' . substr($editorId, -4);
        }
        $kept[$oid] = [
            'editor_id' => $editorId,
            'name' => $name,
            'since' => (string)($lock['since'] ?? gmdate('c')),
            'expires_at' => $exp > 0 ? gmdate('c', $exp) : gmdate('c', $now + 90),
        ];
    }
    $payload['locks'] = $kept;
    return $kept;
}

function share_public_locks(array $payload): array
{
    return share_prune_locks($payload);
}

function share_write_payload(string $writePath, array $payload): bool
{
    $written = @file_put_contents(
        $writePath,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
    return $written !== false;
}

function share_load_payload(string $filePath): ?array
{
    $raw = @file_get_contents($filePath);
    if ($raw === false) {
        return null;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || !isset($decoded['drawing']) || !is_array($decoded['drawing'])) {
        return null;
    }
    return $decoded;
}

function share_sanitize_announce_text(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $text = strip_tags($text);
    if (function_exists('mb_substr')) {
        $text = mb_substr($text, 0, 500);
    } else {
        $text = substr($text, 0, 500);
    }
    return trim($text);
}

/** Normalize one announce object; null if invalid. */
function share_normalize_announce($raw): ?array
{
    if (!is_array($raw)) {
        return null;
    }
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($raw['id'] ?? '')) ?? '';
    $text = share_sanitize_announce_text((string)($raw['text'] ?? ''));
    if ($id === '' || $text === '') {
        return null;
    }
    $created = (string)($raw['created_at'] ?? '');
    if ($created === '') {
        $created = gmdate('c');
    }
    return [
        'id' => $id,
        'text' => $text,
        'from_id' => share_sanitize_viewer_id((string)($raw['from_id'] ?? '')),
        'from_name' => share_sanitize_name((string)($raw['from_name'] ?? '')),
        'created_at' => $created,
    ];
}

/** Queue of announces (oldest first); keeps last $max items. Also migrates legacy single announce. */
function share_announces_from_payload(array $payload, int $max = 30): array
{
    $list = [];
    if (isset($payload['announces']) && is_array($payload['announces'])) {
        foreach ($payload['announces'] as $item) {
            $n = share_normalize_announce($item);
            if ($n !== null) {
                $list[] = $n;
            }
        }
    }
    $legacy = share_normalize_announce($payload['announce'] ?? null);
    if ($legacy !== null) {
        $seen = false;
        foreach ($list as $existing) {
            if (($existing['id'] ?? '') === $legacy['id']) {
                $seen = true;
                break;
            }
        }
        if (!$seen) {
            $list[] = $legacy;
        }
    }
    if (count($list) > $max) {
        $list = array_slice($list, -$max);
    }
    return array_values($list);
}


function share_view_leader_from_payload(array $payload): array
{
    $vl = (isset($payload['view_leader']) && is_array($payload['view_leader']))
        ? $payload['view_leader']
        : [];
    $id = share_sanitize_viewer_id((string)($vl['id'] ?? ''));
    $name = share_sanitize_name((string)($vl['name'] ?? ''));
    if ($id === '') {
        return ['id' => '', 'name' => '', 'since' => ''];
    }
    if ($name === '') {
        $name = 'Editor-' . substr($id, -4);
    }
    return [
        'id' => $id,
        'name' => $name,
        'since' => (string)($vl['since'] ?? ''),
    ];
}

function share_meta_response(string $token, array $payload, int $viewerCount, bool $unchanged = false): array
{
    share_prune_locks($payload);
    share_prune_editors($payload);
    $announces = share_announces_from_payload($payload);
    $out = [
        'ok' => true,
        'token' => $token,
        'updated_at' => (string)($payload['updated_at'] ?? ''),
        'heartbeat_at' => (string)($payload['heartbeat_at'] ?? ($payload['updated_at'] ?? '')),
        'active' => !empty($payload['active']),
        'view_only' => !empty($payload['view_only']),
        'viewer_count' => $viewerCount,
        'multi_edit' => !empty($payload['multi_edit']),
        'locks' => isset($payload['locks']) && is_array($payload['locks']) ? $payload['locks'] : new stdClass(),
        'editor_count' => isset($payload['editors']) && is_array($payload['editors']) ? count($payload['editors']) : 0,
        'announces' => $announces,
        'view_leader' => share_view_leader_from_payload($payload),
        'view_epoch' => (string)($payload['view_epoch'] ?? ''),
    ];
    if (!empty($announces)) {
        $out['announce'] = $announces[count($announces) - 1];
    }
    // Follow-Ausschnitt auch bei unchanged/announce_poll mitgeben
    if (isset($payload['drawing']) && is_array($payload['drawing'])
        && isset($payload['drawing']['mapView']) && is_array($payload['drawing']['mapView'])) {
        $out['mapView'] = $payload['drawing']['mapView'];
    }
    if ($unchanged) {
        $out['unchanged'] = true;
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = share_sanitize_token((string)($_GET['token'] ?? ''));
    if ($token === '') {
        share_respond(400, ['ok' => false, 'error' => 'token required']);
    }
    $filePath = share_find_file($token);
    if ($filePath === null) {
        share_respond(404, ['ok' => false, 'error' => 'Share not found']);
    }
    $payload = share_load_payload($filePath);
    if ($payload === null) {
        share_respond(500, ['ok' => false, 'error' => 'Failed to read share']);
    }
    $viewerId = share_sanitize_viewer_id((string)($_GET['viewer_id'] ?? ''));
    $editorId = share_sanitize_viewer_id((string)($_GET['editor_id'] ?? ''));
    $editorName = share_sanitize_name((string)($_GET['editor_name'] ?? ''));
    if (!empty($_GET['announce_poll'])) {
        $viewerCount = share_prune_viewers($payload);
        share_respond(200, share_meta_response($token, $payload, $viewerCount, true));
    }
    $viewerCount = share_prune_viewers($payload);
    $dirty = false;
    if ($viewerId !== '') {
        $viewerCount = share_touch_viewer($payload, $viewerId);
        $dirty = true;
    }
    if ($editorId !== '') {
        share_touch_editor($payload, $editorId, $editorName);
        $dirty = true;
    }
    share_prune_locks($payload);
    if ($dirty) {
        $writePath = share_file_path($token) ?? $filePath;
        if (!share_write_payload($writePath, $payload)) {
            share_respond(500, ['ok' => false, 'error' => 'Failed to write share file']);
        }
    }
    $updatedAt = (string)($payload['updated_at'] ?? '');
    $since = trim((string)($_GET['since'] ?? ''));
    $rawForEtag = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $etag = '"' . md5($updatedAt . '|' . strlen((string)$rawForEtag)) . '"';
    header('ETag: ' . $etag);
    $ifNone = (string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '');
    $forceBase = !empty($_GET['sync_base']);
    if (!$forceBase && (($since !== '' && $since === $updatedAt) || ($ifNone !== '' && $ifNone === $etag))) {
        $u = share_meta_response($token, $payload, $viewerCount, true);
        if ($editorId !== '') {
            $u['my_editor_status'] = share_get_editor_mode($payload, $editorId);
            $u['is_host'] = share_is_host_editor($payload, $editorId);
            if (share_is_host_editor($payload, $editorId)) {
                $u['editors'] = share_editors_list($payload);
                $u['host_editor_id'] = share_sanitize_viewer_id((string)($payload['host_editor_id'] ?? ''));
            }
            if (share_get_editor_mode($payload, $editorId) === 'kicked') {
                $u['kicked'] = true;
            }
        }
        share_respond(200, $u);
    }
    $resp = share_meta_response($token, $payload, $viewerCount, false);
    $recipientId = $editorId !== '' ? $editorId : $viewerId;
    if ($editorId !== '') {
        $resp['my_editor_status'] = share_get_editor_mode($payload, $editorId);
        $resp['is_host'] = share_is_host_editor($payload, $editorId);
        if (share_is_host_editor($payload, $editorId)) {
            $resp['editors'] = share_editors_list($payload);
            $resp['host_editor_id'] = share_sanitize_viewer_id((string)($payload['host_editor_id'] ?? ''));
        }
    }
    // Gekickt: keine Zeichnung / kein Editor-Recht
    if ($editorId !== '' && share_get_editor_mode($payload, $editorId) === 'kicked') {
        $resp['drawing'] = ['managedObjects' => [], 'managedObjectIdSeq' => 1];
        $resp['kicked'] = true;
        share_respond(200, $resp);
    }
    if (!empty($payload['view_only'])) {
        // Statische Ansicht: komplette Zeichnung inkl. Raster-Kachelfarben
        $resp['drawing'] = isset($payload['drawing']) && is_array($payload['drawing']) ? $payload['drawing'] : [];
    } else {
        $resp['drawing'] = share_filter_unfixed_for_recipient(
            isset($payload['drawing']) && is_array($payload['drawing']) ? $payload['drawing'] : [],
            ($editorId !== '' && share_get_editor_mode($payload, $editorId) === 'viewer') ? '' : $recipientId
        );
    }
    share_respond(200, $resp);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    share_respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody ?? '', true);
if (!is_array($body)) {
    share_respond(400, ['ok' => false, 'error' => 'Invalid JSON body']);
}

$tokenIn = share_sanitize_token((string)($body['token'] ?? ''));
$isPing = !empty($body['ping']);
$active = array_key_exists('active', $body) ? (bool)$body['active'] : true;
$drawing = $body['drawing'] ?? null;

share_cleanup_old();

// --- Follow-Fokus: exklusiv Ausschnitt fuer alle Geraete ---
if (isset($body['claim_view_leader']) && is_array($body['claim_view_leader'])) {
    if ($tokenIn === '') {
        share_respond(400, ['ok' => false, 'error' => 'token required']);
    }
    $existingPath = share_find_file($tokenIn);
    if ($existingPath === null) {
        share_respond(404, ['ok' => false, 'error' => 'Share not found']);
    }
    $existing = share_load_payload($existingPath);
    if ($existing === null) {
        share_respond(500, ['ok' => false, 'error' => 'Invalid share file']);
    }
    $claim = $body['claim_view_leader'];
    $fromId = share_sanitize_viewer_id((string)($claim['editor_id'] ?? $body['editor_id'] ?? ''));
    $fromName = share_sanitize_name((string)($claim['editor_name'] ?? $body['editor_name'] ?? ''));
    if ($fromId === '') {
        share_respond(400, ['ok' => false, 'error' => 'editor_id required']);
    }
    $mapView = (isset($claim['mapView']) && is_array($claim['mapView'])) ? $claim['mapView'] : null;
    if ($mapView === null) {
        share_respond(400, ['ok' => false, 'error' => 'mapView required']);
    }
    $now = gmdate('c');
    $existing['view_leader'] = [
        'id' => $fromId,
        'name' => $fromName !== '' ? $fromName : ('Editor-' . substr($fromId, -4)),
        'since' => $now,
    ];
    $existing['view_epoch'] = $now . '-' . bin2hex(random_bytes(4));
    if (!isset($existing['drawing']) || !is_array($existing['drawing'])) {
        $existing['drawing'] = [];
    }
    $existing['drawing']['mapView'] = $mapView;
    $existing['heartbeat_at'] = $now;
    // Do not bump updated_at — object list stays; view sync via view_epoch
    if ($fromId !== '') {
        share_touch_editor($existing, $fromId, $fromName);
    }
    $viewerCount = share_prune_viewers($existing);
    share_prune_locks($existing);
    share_prune_editors($existing);
    $writePath = share_file_path($tokenIn) ?? $existingPath;
    if (!share_write_payload($writePath, $existing)) {
        share_respond(500, ['ok' => false, 'error' => 'Failed to write share file']);
    }
    share_respond(200, share_meta_response($tokenIn, $existing, $viewerCount, false));
}

// --- Broadcast-Nachricht (Queue; Empfaenger muessen je bestaetigen) ---
if (isset($body['announce']) && is_array($body['announce'])) {
    if ($tokenIn === '') {
        share_respond(400, ['ok' => false, 'error' => 'token required']);
    }
    $existingPath = share_find_file($tokenIn);
    if ($existingPath === null) {
        share_respond(404, ['ok' => false, 'error' => 'Share not found']);
    }
    $existing = share_load_payload($existingPath);
    if ($existing === null) {
        share_respond(500, ['ok' => false, 'error' => 'Invalid share file']);
    }
    $ann = $body['announce'];
    $text = share_sanitize_announce_text((string)($ann['text'] ?? ''));
    if ($text === '') {
        share_respond(400, ['ok' => false, 'error' => 'Nachricht leer']);
    }
    $fromId = share_sanitize_viewer_id((string)($ann['editor_id'] ?? $body['editor_id'] ?? ''));
    $fromName = share_sanitize_name((string)($ann['editor_name'] ?? $body['editor_name'] ?? ''));
    $createdAt = gmdate('c');
    $announceId = 'ann' . bin2hex(random_bytes(8));
    $entry = [
        'id' => $announceId,
        'text' => $text,
        'from_id' => $fromId,
        'from_name' => $fromName !== '' ? $fromName : 'Editor',
        'created_at' => $createdAt,
    ];
    $list = share_announces_from_payload($existing);
    $list[] = $entry;
    if (count($list) > 30) {
        $list = array_slice($list, -30);
    }
    $existing['announces'] = array_values($list);
    $existing['announce'] = $entry;
    $existing['heartbeat_at'] = $createdAt;
    if ($fromId !== '') {
        share_touch_editor($existing, $fromId, $fromName);
    }
    $viewerCount = share_prune_viewers($existing);
    share_prune_locks($existing);
    share_prune_editors($existing);
    $writePath = share_file_path($tokenIn) ?? $existingPath;
    if (!share_write_payload($writePath, $existing)) {
        share_respond(500, ['ok' => false, 'error' => 'Failed to write share file']);
    }
    share_respond(200, share_meta_response($tokenIn, $existing, $viewerCount, false));
}

// --- Lock claim ---
if (isset($body['claim_lock']) && is_array($body['claim_lock'])) {
    if ($tokenIn === '') {
        share_respond(400, ['ok' => false, 'error' => 'token required']);
    }
    $existingPath = share_find_file($tokenIn);
    if ($existingPath === null) {
        share_respond(404, ['ok' => false, 'error' => 'Share not found']);
    }
    $existing = share_load_payload($existingPath);
    if ($existing === null) {
        share_respond(500, ['ok' => false, 'error' => 'Invalid share file']);
    }
    if (empty($existing['multi_edit'])) {
        share_respond(403, ['ok' => false, 'error' => 'Multi-Editor nicht aktiv']);
    }
    $claim = $body['claim_lock'];
    $objectId = trim((string)($claim['object_id'] ?? ''));
    $editorId = share_sanitize_viewer_id((string)($claim['editor_id'] ?? ''));
    $name = share_sanitize_name((string)($claim['name'] ?? ''));
    $ttl = (int)($claim['ttl_sec'] ?? 90);
    if ($ttl < 30) {
        $ttl = 30;
    }
    if ($ttl > 300) {
        $ttl = 300;
    }
    if ($objectId === '' || $editorId === '') {
        share_respond(400, ['ok' => false, 'error' => 'object_id and editor_id required']);
    }
    share_prune_locks($existing);
    share_touch_editor($existing, $editorId, $name);
    $locks = isset($existing['locks']) && is_array($existing['locks']) ? $existing['locks'] : [];
    $cur = isset($locks[$objectId]) && is_array($locks[$objectId]) ? $locks[$objectId] : null;
    // Punkte/Textfelder: Lock darf von jedem Editor übernommen werden
    $objType = '';
    if (isset($existing['drawing']['managedObjects']) && is_array($existing['drawing']['managedObjects'])) {
        foreach ($existing['drawing']['managedObjects'] as $mo) {
            if (!is_array($mo) || !isset($mo['id'])) {
                continue;
            }
            if ((string)$mo['id'] === (string)$objectId) {
                $objType = (string)($mo['type'] ?? '');
                break;
            }
        }
    }
    $isPointish = ($objType === 'points' || $objType === 'textfields');
    if ($cur !== null) {
        $holder = share_sanitize_viewer_id((string)($cur['editor_id'] ?? ''));
        if ($holder !== '' && $holder !== $editorId && !$isPointish) {
            share_respond(409, [
                'ok' => false,
                'error' => 'locked',
                'lock' => $cur,
                'locks' => $locks,
            ]);
        }
    }
    if ($name === '') {
        $name = 'Editor-' . substr($editorId, -4);
    }
    $now = time();
    $locks[$objectId] = [
        'editor_id' => $editorId,
        'name' => $name,
        'since' => ($cur && ($cur['editor_id'] ?? '') === $editorId)
            ? (string)($cur['since'] ?? gmdate('c', $now))
            : gmdate('c', $now),
        'expires_at' => gmdate('c', $now + $ttl),
    ];
    $existing['locks'] = $locks;
    $writePath = share_file_path($tokenIn) ?? $existingPath;
    if (!share_write_payload($writePath, $existing)) {
        share_respond(500, ['ok' => false, 'error' => 'Failed to write share file']);
    }
    share_respond(200, [
        'ok' => true,
        'token' => $tokenIn,
        'lock' => $locks[$objectId],
        'locks' => $locks,
    ]);
}

// --- Lock release ---
if (isset($body['release_lock']) && is_array($body['release_lock'])) {
    if ($tokenIn === '') {
        share_respond(400, ['ok' => false, 'error' => 'token required']);
    }
    $existingPath = share_find_file($tokenIn);
    if ($existingPath === null) {
        share_respond(404, ['ok' => false, 'error' => 'Share not found']);
    }
    $existing = share_load_payload($existingPath);
    if ($existing === null) {
        share_respond(500, ['ok' => false, 'error' => 'Invalid share file']);
    }
    $rel = $body['release_lock'];
    $objectId = trim((string)($rel['object_id'] ?? ''));
    $editorId = share_sanitize_viewer_id((string)($rel['editor_id'] ?? ''));
    if ($objectId === '' || $editorId === '') {
        share_respond(400, ['ok' => false, 'error' => 'object_id and editor_id required']);
    }
    share_prune_locks($existing);
    $locks = isset($existing['locks']) && is_array($existing['locks']) ? $existing['locks'] : [];
    if (isset($locks[$objectId]) && is_array($locks[$objectId])) {
        $holder = share_sanitize_viewer_id((string)($locks[$objectId]['editor_id'] ?? ''));
        if ($holder === $editorId || $holder === '') {
            unset($locks[$objectId]);
        }
    }
    $existing['locks'] = $locks;
    $writePath = share_file_path($tokenIn) ?? $existingPath;
    if (!share_write_payload($writePath, $existing)) {
        share_respond(500, ['ok' => false, 'error' => 'Failed to write share file']);
    }
    share_respond(200, [
        'ok' => true,
        'token' => $tokenIn,
        'locks' => $locks,
    ]);
}

// --- Renew locks ---
if (isset($body['renew_locks']) && is_array($body['renew_locks'])) {
    if ($tokenIn === '') {
        share_respond(400, ['ok' => false, 'error' => 'token required']);
    }
    $existingPath = share_find_file($tokenIn);
    if ($existingPath === null) {
        share_respond(404, ['ok' => false, 'error' => 'Share not found']);
    }
    $existing = share_load_payload($existingPath);
    if ($existing === null) {
        share_respond(500, ['ok' => false, 'error' => 'Invalid share file']);
    }
    $renew = $body['renew_locks'];
    $editorId = share_sanitize_viewer_id((string)($renew['editor_id'] ?? ''));
    if ($editorId === '') {
        share_respond(400, ['ok' => false, 'error' => 'editor_id required']);
    }
    share_touch_editor($existing, $editorId, share_sanitize_name((string)($renew['name'] ?? '')));
    share_prune_locks($existing);
    $locks = isset($existing['locks']) && is_array($existing['locks']) ? $existing['locks'] : [];
    $ids = isset($renew['object_ids']) && is_array($renew['object_ids']) ? $renew['object_ids'] : null;
    $now = time();
    foreach ($locks as $oid => $lock) {
        if (!is_array($lock)) {
            continue;
        }
        $holder = share_sanitize_viewer_id((string)($lock['editor_id'] ?? ''));
        if ($holder !== $editorId) {
            continue;
        }
        if ($ids !== null) {
            $match = false;
            foreach ($ids as $want) {
                if ((string)$want === (string)$oid) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                continue;
            }
        }
        $locks[$oid]['expires_at'] = gmdate('c', $now + 90);
    }
    $existing['locks'] = $locks;
    $writePath = share_file_path($tokenIn) ?? $existingPath;
    if (!share_write_payload($writePath, $existing)) {
        share_respond(500, ['ok' => false, 'error' => 'Failed to write share file']);
    }
    share_respond(200, [
        'ok' => true,
        'token' => $tokenIn,
        'locks' => $locks,
    ]);
}


// --- Master: Sub-Editor moderieren (viewer / kick / restore) ---
if (isset($body['moderate_editor']) && is_array($body['moderate_editor'])) {
    if ($tokenIn === '') {
        share_respond(400, ['ok' => false, 'error' => 'token required']);
    }
    $existingPath = share_find_file($tokenIn);
    if ($existingPath === null) {
        share_respond(404, ['ok' => false, 'error' => 'Share not found']);
    }
    $existing = share_load_payload($existingPath);
    if ($existing === null) {
        share_respond(500, ['ok' => false, 'error' => 'Invalid share file']);
    }
    $mod = $body['moderate_editor'];
    $hostId = share_sanitize_viewer_id((string)($mod['host_id'] ?? $body['editor_id'] ?? ''));
    $targetId = share_sanitize_viewer_id((string)($mod['target_id'] ?? ''));
    $action = strtolower(trim((string)($mod['action'] ?? '')));
    $targetName = share_sanitize_name((string)($mod['name'] ?? ''));
    if ($hostId === '' || empty($existing['host_editor_id'])) {
        // Ersten Host setzen falls Master noch nicht gestempelt
        if ($hostId !== '') {
            share_set_host_editor($existing, $hostId);
        }
    }
    if (!share_apply_editor_moderation($existing, $targetId, $action, $hostId, $targetName)) {
        share_respond(403, ['ok' => false, 'error' => 'Moderation nicht erlaubt']);
    }
    share_touch_editor($existing, $hostId, share_sanitize_name((string)($body['editor_name'] ?? '')));
    $viewerCount = share_prune_viewers($existing);
    share_prune_locks($existing);
    share_prune_editors($existing);
    $writePath = share_file_path($tokenIn) ?? $existingPath;
    if (!share_write_payload($writePath, $existing)) {
        share_respond(500, ['ok' => false, 'error' => 'Failed to write share file']);
    }
    $resp = share_meta_response($tokenIn, $existing, $viewerCount, false);
    $resp['editors'] = share_editors_list($existing);
    $resp['host_editor_id'] = share_sanitize_viewer_id((string)($existing['host_editor_id'] ?? ''));
    $resp['moderated'] = ['target_id' => $targetId, 'action' => $action];
    share_respond(200, $resp);
}

if ($isPing) {
    if ($tokenIn === '') {
        share_respond(400, ['ok' => false, 'error' => 'token required for ping']);
    }
    $token = $tokenIn;
    $existingPath = share_find_file($token);
    if ($existingPath === null) {
        share_respond(404, ['ok' => false, 'error' => 'Share not found']);
    }
    $existing = share_load_payload($existingPath);
    if ($existing === null) {
        share_respond(500, ['ok' => false, 'error' => 'Invalid share file']);
    }
    $heartbeatAt = gmdate('c');
    if (array_key_exists('active', $body)) {
        $existing['active'] = $active;
    }
    $existing['heartbeat_at'] = $heartbeatAt;
    if (!isset($existing['updated_at'])) {
        $existing['updated_at'] = $heartbeatAt;
    }
    if (array_key_exists('multi_edit', $body)) {
        // sticky: once multi-edit is on, keep it on (sub-editors must not be wiped by host)
        if (!empty($body['multi_edit'])) {
            $existing['multi_edit'] = true;
        }
    }
    $editorId = share_sanitize_viewer_id((string)($body['editor_id'] ?? ''));
    if ($editorId !== '') {
        share_touch_editor($existing, $editorId, share_sanitize_name((string)($body['editor_name'] ?? '')));
    }
    $viewerCount = share_prune_viewers($existing);
    share_prune_locks($existing);
    share_prune_editors($existing);
    $writePath = share_file_path($token) ?? $existingPath;
    if (!share_write_payload($writePath, $existing)) {
        share_respond(500, ['ok' => false, 'error' => 'Failed to write share file']);
    }
    $resp = share_meta_response($token, $existing, $viewerCount, false);
    share_respond(200, $resp);
}

$token = $tokenIn !== '' ? $tokenIn : share_new_token();
if (!is_array($drawing)) {
    share_respond(400, ['ok' => false, 'error' => 'Drawing payload is required']);
}

$filePath = share_file_path($token);
if ($filePath === null) {
    share_respond(500, ['ok' => false, 'error' => 'Storage folder not found']);
}

$existingViewers = [];
$existingLocks = [];
$existingEditors = [];
$existingAnnounces = [];
$existingDrawing = null;
$existingViewLeader = [];
$existingViewEpoch = '';
$existingMulti = false;
$prev = null;
$existingPath = share_find_file($token);
if ($existingPath !== null) {
    $prev = share_load_payload($existingPath);
    if ($prev !== null) {
        if (isset($prev['viewers']) && is_array($prev['viewers'])) {
            $existingViewers = $prev['viewers'];
        }
        if (isset($prev['locks']) && is_array($prev['locks'])) {
            $existingLocks = $prev['locks'];
        }
        if (isset($prev['editors']) && is_array($prev['editors'])) {
            $existingEditors = $prev['editors'];
        }
        $existingAnnounces = share_announces_from_payload($prev);
        $existingMulti = !empty($prev['multi_edit']);
        if (isset($prev['drawing']) && is_array($prev['drawing'])) {
            $existingDrawing = $prev['drawing'];
        }
        if (isset($prev['view_leader']) && is_array($prev['view_leader'])) {
            $existingViewLeader = $prev['view_leader'];
        }
        $existingViewEpoch = (string)($prev['view_epoch'] ?? '');
    }
}

$updatedAt = gmdate('c');
$viewOnly = array_key_exists('view_only', $body)
    ? !empty($body['view_only'])
    : (is_array($prev) && !empty($prev['view_only']));
$multiEdit = $viewOnly
    ? false
    : ($existingMulti || (array_key_exists('multi_edit', $body) && !empty($body['multi_edit'])));
$editorId = share_sanitize_viewer_id((string)($body['editor_id'] ?? ''));
$tmpLocksPayload = ['locks' => $existingLocks];
share_prune_locks($tmpLocksPayload);
$existingLocks = isset($tmpLocksPayload['locks']) && is_array($tmpLocksPayload['locks'])
    ? $tmpLocksPayload['locks']
    : [];

// Multi-Edit: existing share file is always the merge base (Ausgangsinformation).
// Clients may send base_updated_at; mismatch still merges against file (no deadlock).
$baseUpdatedAt = trim((string)($body['base_updated_at'] ?? ''));
$existingUpdatedAtForBase = (isset($prev) && is_array($prev))
    ? (string)($prev['updated_at'] ?? '')
    : '';
$baseMatched = ($baseUpdatedAt === '' || $baseUpdatedAt === $existingUpdatedAtForBase);
$mergedDrawing = $drawing;
$GLOBALS['__share_view_leader_id'] = share_sanitize_viewer_id((string)(($existingViewLeader['id'] ?? '')));
if ($viewOnly) {
    // Statische Ansicht: Zeichnung 1:1 ersetzen, kein Live-Merge
    $mergedDrawing = is_array($drawing) ? $drawing : [];
    $existingViewLeader = [];
    $existingViewEpoch = '';
    $existingLocks = [];
    $existingEditors = [];
    $existingAnnounces = [];
} elseif ($multiEdit && is_array($existingDrawing)) {
    $mergedDrawing = share_merge_drawing($existingDrawing, $drawing, $editorId, $existingLocks);
} elseif (!$multiEdit && is_array($existingDrawing)) {
    // Non-multi: host drives view; still protect if leader set
    $GLOBALS['__share_view_leader_id'] = share_sanitize_viewer_id((string)(($existingViewLeader['id'] ?? $editorId)));
    if ($editorId !== '' && ($existingViewLeader['id'] ?? '') === '') {
        $existingViewLeader = ['id' => $editorId, 'name' => share_sanitize_name((string)($body['editor_name'] ?? '')), 'since' => gmdate('c')];
    }
}
// If no leader yet and host pushes mapView, become leader
if (!$viewOnly && $editorId !== '' && empty($existingViewLeader['id']) && isset($mergedDrawing['mapView'])) {
    $existingViewLeader = [
        'id' => $editorId,
        'name' => share_sanitize_name((string)($body['editor_name'] ?? 'Master')),
        'since' => gmdate('c'),
    ];
    if ($existingViewEpoch === '') {
        $existingViewEpoch = gmdate('c') . '-init';
    }
}

$payload = [
    'token' => $token,
    'updated_at' => $updatedAt,
    'heartbeat_at' => $updatedAt,
    'active' => $active,
    'view_only' => $viewOnly,
    'multi_edit' => $multiEdit,
    'base_matched' => isset($baseMatched) ? (bool)$baseMatched : true,
    'share_base_at' => $updatedAt,
    'drawing' => $mergedDrawing,
    'viewers' => $viewOnly ? [] : $existingViewers,
    'locks' => $existingLocks,
    'editors' => $existingEditors,
    'announces' => $existingAnnounces,
    'view_leader' => $existingViewLeader,
    'view_epoch' => $existingViewEpoch,
];
if (!empty($existingAnnounces)) {
    $payload['announce'] = $existingAnnounces[count($existingAnnounces) - 1];
}
if ($editorId !== '') {
    if (!empty($body['is_host']) || empty($payload['host_editor_id'])) {
        // Master/Host beim Broadcast stempeln (Sub sendet is_host nicht)
        if (!empty($body['is_host'])) {
            $payload['host_editor_id'] = $editorId;
        } elseif (empty($payload['host_editor_id'])) {
            share_set_host_editor($payload, $editorId);
        }
    }
    share_touch_editor($payload, $editorId, share_sanitize_name((string)($body['editor_name'] ?? '')));
}
$viewerCount = share_prune_viewers($payload);
share_prune_locks($payload);
share_prune_editors($payload);
if (!share_write_payload($filePath, $payload)) {
    share_respond(500, ['ok' => false, 'error' => 'Failed to write share file']);
}

$scriptDir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
$sharePath = $scriptDir . '/rkrk_map_overlay.php?share=' . rawurlencode($token);
if ($multiEdit) {
    $sharePath .= '&edit=1';
}

$resp = share_meta_response($token, $payload, $viewerCount, false);
$resp['share_path'] = $sharePath;
if ($editorId !== '') {
    $resp['my_editor_status'] = share_get_editor_mode($payload, $editorId);
    $resp['is_host'] = share_is_host_editor($payload, $editorId);
    if (share_is_host_editor($payload, $editorId)) {
        $resp['editors'] = share_editors_list($payload);
        $resp['host_editor_id'] = share_sanitize_viewer_id((string)($payload['host_editor_id'] ?? ''));
    }
}
if (!empty($payload['view_only'])) {
    $resp['drawing'] = isset($payload['drawing']) && is_array($payload['drawing']) ? $payload['drawing'] : [];
} else {
    $resp['drawing'] = share_filter_unfixed_for_recipient(
        isset($payload['drawing']) && is_array($payload['drawing']) ? $payload['drawing'] : [],
        $editorId
    );
}
share_respond(200, $resp);

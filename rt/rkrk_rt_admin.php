<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once(__DIR__ . '/rkrk_rt_shell.php');
rkrk_rt_boot();

$resolvedDataDir = realpath(__DIR__ . '/../../rkrk/data/rt');
$dataDir = $resolvedDataDir !== false ? $resolvedDataDir : (__DIR__ . '/../../rkrk/data/rt');
$webRtBase = rtrim((string)($RkrkLocation ?? ''), "/\\") . "/data/rt";
$baseDir = $dataDir;
$imagesDir = $baseDir . "/images";

$defaultsFile   = $baseDir . "/invoice_default.json";
$customersFile  = $baseDir . "/customers.json";
$countersFile   = $baseDir . "/invoice_counters.json";

$DEFAULTS  = json_decode(@file_get_contents($defaultsFile), true) ?: [];
$CUSTOMERS = json_decode(@file_get_contents($customersFile), true) ?: [];
$COUNTERS  = file_exists($countersFile)
    ? (json_decode(file_get_contents($countersFile), true) ?: [])
    : ["SC" => 1, "RK" => 1, "IN" => 1, "MA" => 1];

if (!isset($DEFAULTS["konto"]))               $DEFAULTS["konto"] = [];
if (!isset($DEFAULTS["material_defaults"]))   $DEFAULTS["material_defaults"] = [];
if (!isset($DEFAULTS["kategorien"]))          $DEFAULTS["kategorien"] = [];
if (!isset($DEFAULTS["standardpositionen"]))  $DEFAULTS["standardpositionen"] = [];
if (!isset($DEFAULTS["standardgruppen"]))     $DEFAULTS["standardgruppen"] = [];
if (!isset($DEFAULTS["logosets"]))            $DEFAULTS["logosets"] = [];
if (!isset($DEFAULTS["stylesets"]))           $DEFAULTS["stylesets"] = [];
if (!isset($DEFAULTS["sektionen"]))           $DEFAULTS["sektionen"] = [];
if (!isset($DEFAULTS["signaturen"]))          $DEFAULTS["signaturen"] = [];
if (!isset($DEFAULTS["signaturen_order"]))    $DEFAULTS["signaturen_order"] = array_keys($DEFAULTS["signaturen"]);
if (!isset($DEFAULTS["kategorien_order"]))    $DEFAULTS["kategorien_order"] = array_keys($DEFAULTS["kategorien"]);

$msg = "";
$activeTab = $_POST["active_tab"] ?? ($_GET["active_tab"] ?? "");
if (isset($_GET["sig_saved"]) && $_GET["sig_saved"] === "1") {
    $msg = "Signatur gespeichert.";
}

function safe($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function save_defaults($file, &$data){
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $written = file_put_contents($file, $json, LOCK_EX);
    return $written !== false;
}
function ensure_dir(string $dir): bool {
    if (is_dir($dir)) {
        return true;
    }
    return @mkdir($dir, 0775, true);
}
function upload_image_as_png(string $field, string $destFile, ?int $idx = null, ?string &$error = null): bool {
    if (!isset($_FILES[$field])) {
        return false;
    }
    $f = $_FILES[$field];
    $tmp = '';
    $err = UPLOAD_ERR_NO_FILE;
    if ($idx === null) {
        $tmp = (string)($f['tmp_name'] ?? '');
        $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
    } else {
        $tmp = (string)($f['tmp_name'][$idx] ?? '');
        $err = (int)($f['error'][$idx] ?? UPLOAD_ERR_NO_FILE);
    }
    if ($err === UPLOAD_ERR_NO_FILE || $tmp === '') {
        return false;
    }
    if ($err !== UPLOAD_ERR_OK || !is_file($tmp)) {
        $error = "Upload fehlgeschlagen ($field).";
        return false;
    }
    if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
        $error = "PNG-Konvertierung nicht verfuegbar (GD fehlt).";
        return false;
    }
    $bin = @file_get_contents($tmp);
    if ($bin === false) {
        $error = "Datei konnte nicht gelesen werden ($field).";
        return false;
    }
    $img = @imagecreatefromstring($bin);
    if ($img === false) {
        $error = "Ungueltiges Bildformat ($field).";
        return false;
    }
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $ok = @imagepng($img, $destFile, 6);
    imagedestroy($img);
    if (!$ok) {
        $error = "PNG-Datei konnte nicht geschrieben werden ($field).";
    }
    return $ok;
}
function media_web_src(string $path): string {
    global $webRtBase;
    $path = str_replace('\\', '/', trim($path));
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    // Admin läuft in /rt, Assets liegen in /data/rt/images
    if (strpos($path, 'data/rt/') === 0 || strpos($path, '../data/rt/') === 0) {
        return $path;
    }
    if (strpos($path, 'images/') === 0) {
        return rtrim($webRtBase, '/') . '/' . $path;
    }
    if (strpos($path, '/') === false) {
        return rtrim($webRtBase, '/') . '/images/' . $path;
    }
    return $path;
}
function generatePosId(&$DEFAULTS) {
    $max = 0;
    foreach ($DEFAULTS["standardpositionen"] as $id => $p) {
        if (preg_match('/^P(\d+)$/', $id, $m)) {
            $num = intval($m[1]);
            if ($num > $max) $max = $num;
        }
    }
    return "P" . ($max + 1);
}
function slug_customer_key($firma, $vorname, $nachname) {
    $base = strtolower(trim($firma . "-" . $vorname . "-" . $nachname));
    $base = preg_replace('/[^a-z0-9\-]+/', '-', $base);
    $base = trim($base, '-');
    if ($base === "") {
        $base = "kunde-" . uniqid();
    }
    return $base;
}

ensure_dir($baseDir);
ensure_dir($imagesDir);

/* KATEGORIEN-REIHENFOLGE */
if (isset($_POST["save_kat_order"])) {
    $order = $_POST["kat_order"] ?? "";
    $ids = array_filter(array_map("trim", explode(",", $order)));
    $DEFAULTS["kategorien_order"] = array_values(
        array_filter($ids, function($id) use ($DEFAULTS) {
            return isset($DEFAULTS["kategorien"][$id]);
        })
    );
    save_defaults($defaultsFile, $DEFAULTS);
    $msg = "Kategorien-Reihenfolge gespeichert.";
    $activeTab = "view-kategorien";
}

/* KATEGORIEN */
if (isset($_POST["add_kat"])) {
    $new = strtoupper(trim($_POST["new_kat"]));
    if ($new !== "" && !isset($DEFAULTS["kategorien"][$new])) {
        $DEFAULTS["kategorien"][$new] = [
            "name"        => "",
            "gruppen"     => [],
            "logoset"     => "",
            "styleset"    => "",
            "abschlussslogan" => "",
            "swissqrcode" => "",
            "watermarks"  => "",
            "finanz"      => [
                "iban"       => "",
                "empfaenger" => "",
                "strasse"    => "",
                "plz"        => "",
                "ort"        => "",
                "land"       => "",
                "bank"       => "",
                "bank_ort"   => "",
                "bic"        => "",
            ],
            "zusatzinfo"  => ""
        ];
        $DEFAULTS["kategorien_order"][] = $new;
        save_defaults($defaultsFile, $DEFAULTS);
        $msg = "Kategorie $new hinzugefügt.";
        $activeTab = "view-kategorien";
        $_POST["edit_kat"] = $new;
    }
}

if (isset($_GET["delete_kat"])) {
    $del = $_GET["delete_kat"];
    unset($DEFAULTS["kategorien"][$del]);
    $DEFAULTS["kategorien_order"] = array_values(
        array_filter($DEFAULTS["kategorien_order"], fn($k) => $k !== $del)
    );
    save_defaults($defaultsFile, $DEFAULTS);
    header("Location: rkrk_rt_admin.php?active_tab=view-kategorien");
    exit;
}

if (isset($_POST["save_kat"])) {
    $kat = $_POST["kat"];
    if (!isset($DEFAULTS["kategorien"][$kat])) {
        $DEFAULTS["kategorien"][$kat] = [
            "name"        => "",
            "gruppen"     => [],
            "logoset"     => "",
            "styleset"    => "",
            "abschlussslogan" => "",
            "swissqrcode" => "",
            "watermarks"  => "",
            "finanz"      => [
                "iban"       => "",
                "empfaenger" => "",
                "strasse"    => "",
                "plz"        => "",
                "ort"        => "",
                "land"       => "",
                "bank"       => "",
                "bank_ort"   => "",
                "bic"        => "",
            ],
            "zusatzinfo"  => ""
        ];
    }
    $cfg = &$DEFAULTS["kategorien"][$kat];
    $cfg["name"]      = trim($_POST["name"]);
    $gruppenHidden    = $_POST["gruppen_hidden"] ?? "";
    $cfg["gruppen"]   = $gruppenHidden ? array_values(array_filter(array_map("trim", explode(",", $gruppenHidden)))) : [];

    save_defaults($defaultsFile, $DEFAULTS);
    $msg = "Kategorie gespeichert.";
    $activeTab = "view-kategorien";
    $_POST["edit_kat"] = $kat;
}

/* GLOBALE EINSTELLUNGEN PRO KATEGORIE */
if (isset($_POST["save_global_cat"])) {
    $kat = $_POST["kat_global"] ?? "";
    if ($kat !== "" && isset($DEFAULTS["kategorien"][$kat])) {
        $cfg = &$DEFAULTS["kategorien"][$kat];

        $cfg["logoset"]    = $_POST["logoset"]  ?? "";
        $cfg["styleset"]   = $_POST["styleset"] ?? "";
        $cfg["zusatzinfo"] = $_POST["zusatzinfo_cat"] ?? "";
        $cfg["abschlussslogan"] = $_POST["abschlussslogan_cat"] ?? "";

        if (!isset($cfg["finanz"]) || !is_array($cfg["finanz"])) {
            $cfg["finanz"] = [];
        }
        $cfg["finanz"]["iban"]       = $_POST["iban_cat"]       ?? "";
        $cfg["finanz"]["empfaenger"] = $_POST["empfaenger_cat"] ?? "";
        $cfg["finanz"]["strasse"]    = $_POST["strasse_cat"]    ?? "";
        $cfg["finanz"]["plz"]        = $_POST["plz_cat"]        ?? "";
        $cfg["finanz"]["ort"]        = $_POST["ort_cat"]        ?? "";
        $cfg["finanz"]["land"]       = $_POST["land_cat"]       ?? "";
        $cfg["finanz"]["bank"]       = trim((string)($_POST["bank_cat"] ?? ""));
        $cfg["finanz"]["bank_ort"]   = trim((string)($_POST["bank_ort_cat"] ?? ""));
        $cfg["finanz"]["bic"]        = trim((string)($_POST["bic_cat"] ?? ""));

        $uploadDir = $imagesDir;
        ensure_dir($uploadDir);

        $upErr = null;
        $qrDest = $uploadDir . "/{$kat}_qr.png";
        if (upload_image_as_png("swissqrcode_cat", $qrDest, null, $upErr)) {
            $cfg["swissqrcode"] = "{$kat}_qr.png";
        } elseif ($upErr !== null) {
            $msg = $upErr;
        }

        $upErr = null;
        $wmDest = $uploadDir . "/{$kat}_watermark.png";
        if (upload_image_as_png("watermarks_cat", $wmDest, null, $upErr)) {
            $cfg["watermarks"] = "{$kat}_watermark.png";
        } elseif ($upErr !== null) {
            $msg = $upErr;
        }

        save_defaults($defaultsFile, $DEFAULTS);
        $msg = "Globale Einstellungen für Kategorie $kat gespeichert.";
        $activeTab = "view-global";
    }
}

/* MATERIAL */
if (isset($_POST["save_material"])) {
    $mat = [];
    if (!empty($_POST["mat_label"])) {
        foreach ($_POST["mat_label"] as $i => $lbl) {
            if (trim($lbl) === "") continue;
            $mat[] = [
                "label" => $lbl,
                "preis" => (float)($_POST["mat_preis"][$i] ?? 0)
            ];
        }
    }
    $DEFAULTS["material_defaults"] = $mat;
    save_defaults($defaultsFile, $DEFAULTS);
    $msg = "Material gespeichert.";
    $activeTab = "view-material";
}

/* KUNDEN – mit Firma, Duplikat-Schutz, auto Key */
if (isset($_POST["save_kunde"])) {

    $firma      = trim($_POST["firma"] ?? "");
    $vorname    = trim($_POST["vorname"] ?? "");
    $nachname   = trim($_POST["nachname"] ?? "");
    $originalKey= $_POST["original_key"] ?? "";

    $slug = slug_customer_key($firma, $vorname, $nachname);

    if ($originalKey !== "" && $originalKey !== $slug && isset($CUSTOMERS[$originalKey])) {
        unset($CUSTOMERS[$originalKey]);
    }

    if (isset($CUSTOMERS[$slug]) && $slug !== $originalKey) {
        $msg = "Kunde mit dieser Kombination aus Firma/Vorname/Nachname existiert bereits.";
        $activeTab = "view-kunden";
    } else {
        $CUSTOMERS[$slug] = [
            "firma"    => $firma,
            "anrede"   => $_POST["anrede"] ?? "",
            "vorname"  => $vorname,
            "nachname" => $nachname,
            "adresse"  => $_POST["adresse"] ?? "",
            "plz"      => $_POST["plz"] ?? "",
            "ort"      => $_POST["ort"] ?? "",
            "land"     => $_POST["land"] ?? "",
            "telefon1" => $_POST["telefon1"] ?? "",
            "telefon2" => $_POST["telefon2"] ?? "",
            "email1"   => $_POST["email1"] ?? "",
            "email2"   => $_POST["email2"] ?? "",
        ];
        file_put_contents($customersFile, json_encode($CUSTOMERS, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $msg = "Kunde gespeichert.";
        $activeTab = "view-kunden";
        $editKunde = $slug;
    }
}

if (isset($_GET["delete_kunde"])) {
    unset($CUSTOMERS[$_GET["delete_kunde"]]);
    file_put_contents($customersFile, json_encode($CUSTOMERS, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: rkrk_rt_admin.php?active_tab=view-kunden");
    exit;
}

/* ZÄHLER */
if (isset($_POST["save_counters"])) {
    foreach ($COUNTERS as $key => $val) {
        if (isset($_POST["counter_$key"])) {
            $COUNTERS[$key] = (int)$_POST["counter_$key"];
        }
    }
    file_put_contents($countersFile, json_encode($COUNTERS, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $msg = "Zähler gespeichert.";
    $activeTab = "view-counters";
}

/* SIGNATUREN */
if (isset($_POST["save_sig_order"])) {
    $order = $_POST["sig_order"] ?? "";
    $ids = array_filter(array_map("trim", explode(",", $order)));
    $ids = array_values(array_filter($ids, function ($id) use ($DEFAULTS) {
        return isset($DEFAULTS["signaturen"][$id]);
    }));
    if (count($ids) > 0) {
        $DEFAULTS["signaturen_order"] = $ids;
    }
    $seen = array_flip($DEFAULTS["signaturen_order"]);
    foreach (array_keys($DEFAULTS["signaturen"]) as $kid) {
        if (!isset($seen[$kid])) {
            $DEFAULTS["signaturen_order"][] = $kid;
            $seen[$kid] = true;
        }
    }
    save_defaults($defaultsFile, $DEFAULTS);
    $msg = "Signatur-Reihenfolge gespeichert.";
    $activeTab = "view-signaturen";
}

if (isset($_POST["save_sig"])) {
    $sigId = trim((string) ($_POST["sig_id"] ?? ""));
    if ($sigId === "") {
        $sigId = uniqid("sig", true);
        $sigId = preg_replace('/[^a-zA-Z0-9_]/', '', $sigId);
        if ($sigId === "") {
            $sigId = "sig" . bin2hex(random_bytes(4));
        }
    }

    $sig = $DEFAULTS["signaturen"][$sigId] ?? [
        "vorname" => "",
        "name" => "",
        "position" => "",
        "bezeichnung" => "",
        "bild" => ""
    ];

    $sig["vorname"]     = trim($_POST["vorname"] ?? "");
    $sig["name"]        = trim($_POST["name"] ?? "");
    $sig["position"]    = trim($_POST["position"] ?? "");
    $sig["bezeichnung"] = trim($_POST["bezeichnung"] ?? "");

    $uploadDir = $imagesDir;
    ensure_dir($uploadDir);

    $upErr = null;
    $sigDest = $uploadDir . "/signature_{$sigId}.png";
    if (upload_image_as_png("bild", $sigDest, null, $upErr)) {
        $sig["bild"] = "signature_{$sigId}.png";
    } elseif ($upErr !== null) {
        $msg = $upErr;
    }

    $DEFAULTS["signaturen"][$sigId] = $sig;

    if (!in_array($sigId, $DEFAULTS["signaturen_order"], true)) {
        $DEFAULTS["signaturen_order"][] = $sigId;
    }

    if (save_defaults($defaultsFile, $DEFAULTS)) {
        header("Location: rkrk_rt_admin.php?active_tab=view-signaturen&edit_sig=" . rawurlencode($sigId) . "&sig_saved=1");
        exit;
    }
    $msg = "Signatur konnte nicht gespeichert werden (Datei nicht beschreibbar?).";
    $activeTab = "view-signaturen";
    $_POST["edit_sig"] = $sigId;
}

if (isset($_GET["delete_sig"])) {
    $del = $_GET["delete_sig"];
    if (isset($DEFAULTS["signaturen"][$del])) {
        unset($DEFAULTS["signaturen"][$del]);
        $DEFAULTS["signaturen_order"] = array_values(array_filter(
            $DEFAULTS["signaturen_order"],
            fn($id) => $id !== $del
        ));
        save_defaults($defaultsFile, $DEFAULTS);
    }
    header("Location: rkrk_rt_admin.php?active_tab=view-signaturen");
    exit;
}

/* STANDARDGRUPPEN LÖSCHEN */
if (isset($_GET["delete_group"])) {
    $gid = $_GET["delete_group"];

    unset($DEFAULTS["standardgruppen"][$gid]);

    foreach ($DEFAULTS["kategorien"] as &$kat) {
        if (isset($kat["gruppen"])) {
            $kat["gruppen"] = array_values(array_filter($kat["gruppen"], fn($g) => $g !== $gid));
        }
    }

    save_defaults($defaultsFile, $DEFAULTS);
    header("Location: rkrk_rt_admin.php?active_tab=view-stdpos");
    exit;
}

/* NEUE STANDARDGRUPPE */
if (isset($_POST["add_group"]) && !empty($_POST["new_group_label"])) {
    $gid = "grp_" . substr(md5(uniqid("", true)), 0, 6);
    $DEFAULTS["standardgruppen"][$gid] = [
        "label"      => $_POST["new_group_label"],
        "positionen" => []
    ];
    save_defaults($defaultsFile, $DEFAULTS);
    header("Location: rkrk_rt_admin.php?active_tab=view-stdpos&current_group=".$gid);
    exit;
}

/* STANDARDPOSITIONEN & GRUPPEN SPEICHERN – robust + Risiko-Support */
if (isset($_POST["save_stdpos"])) {

    $newStdpos = [];
    $groupPos  = [];

    // sichere, fortlaufende ID-Erzeugung für neue Positionen
    $max = 0;
    foreach ($DEFAULTS["standardpositionen"] as $eid => $p) {
        if (preg_match('/^P(\d+)$/', $eid, $m)) {
            $n = (int)$m[1];
            if ($n > $max) $max = $n;
        }
    }
    $nextNum = $max + 1;

    if (!empty($_POST["pos_id"])) {
        foreach ($_POST["pos_id"] as $i => $id) {

            $id    = trim($_POST["pos_id"][$i] ?? "");
            $label = trim($_POST["pos_label"][$i] ?? "");

            if ($label === "") continue;

            // Risiko bekommt feste ID
            if (strtolower($label) === "risiko") {
                $id = "RISIKO";
            }

            // Neue Position → neue ID
            if ($id === "" || $id === "PNEW") {
                $id = "P" . $nextNum++;
            }

            // Einheit
            $einheit = $_POST["pos_einheit"][$i] ?? "–";

            // Preis
            $preis = (float)($_POST["pos_preis"][$i] ?? 0);

            // Bemerkung
            $bem = trim($_POST["pos_bemerkung"][$i] ?? "");

            // Risiko-Speziallogik
            /*
			if ($id === "RISIKO") {
                $einheit = "–";
                $preis   = 0;
                if (!in_array($bem, ["1.0", "1.5", "2.0"])) {
                    $bem = "1.0";
                }
            }
			*/
			
			// Prüfen, ob identische Position bereits existiert
			$duplicateId = null;
			foreach ($DEFAULTS["standardpositionen"] as $existingId => $existing) {
				if (
					strtolower($existing["label"])   === strtolower($label) &&
					$existing["einheit"]             === $einheit &&
					floatval($existing["preis"])     === floatval($preis)
				) {
					$duplicateId = $existingId;
					break;
				}
			}

			// Wenn Duplikat → existierende ID verwenden
			if ($duplicateId !== null) {
				$id = $duplicateId;
			}

			// Jetzt speichern
			$newStdpos[$id] = [
				"label"      => $label,
				"einheit"    => $einheit,
				"preis"      => $preis,
				"bemerkung"  => $bem
			];

            $groupPos[] = $id;
        }
    }

    // Merge mit bestehenden Positionen
    $merged = $DEFAULTS["standardpositionen"];
    foreach ($newStdpos as $id => $p) {
        $merged[$id] = $p;
    }
    $DEFAULTS["standardpositionen"] = $merged;

    // Gruppe speichern
    $gid   = $_POST["grp_id"][0] ?? "";
    $label = $_POST["grp_label"][0] ?? "";

    if ($gid !== "") {
        $DEFAULTS["standardgruppen"][$gid] = [
            "label"      => $label,
            "positionen" => $groupPos
        ];
    }

    save_defaults($defaultsFile, $DEFAULTS);
    $msg = "Standardpositionen & Gruppen gespeichert.";
    $activeTab = "view-stdpos";
}

/* LOGO & FARBEN */
if (isset($_POST["save_logo_colors"])) {
    $logosets = [];
    if (!empty($_POST["logo_set_id"])) {
        $uploadDir = $imagesDir;
        ensure_dir($uploadDir);

        foreach ($_POST["logo_set_id"] as $i => $id) {
            $id = trim($id);
            if ($id === "") continue;
            $label = $_POST["logo_set_label"][$i] ?? "";
            $set = [
                "label" => $label,
                "logo"  => $_POST["logo_existing"][$i] ?? ""
            ];

            $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
            if ($safeId === '') {
                $safeId = 'logo' . $i;
            }
            $upErr = null;
            $logoDest = $uploadDir . "/logo_{$safeId}.png";
            if (upload_image_as_png("logo_file", $logoDest, $i, $upErr)) {
                $set["logo"] = "logo_{$safeId}.png";
            } elseif ($upErr !== null) {
                $msg = $upErr;
            }

            $logosets[$id] = $set;
        }
    }
    $DEFAULTS["logosets"] = $logosets;

    $stylesets = [];
    if (!empty($_POST["style_set_id"])) {
        foreach ($_POST["style_set_id"] as $i => $id) {
            $id = trim($id);
            if ($id === "") continue;
            $stylesets[$id] = [
                "label"      => $_POST["style_set_label"][$i] ?? "",
                "primary"    => $_POST["style_primary"][$i] ?? "#007A3D",
                "background" => $_POST["style_background"][$i] ?? "#FFFFFF",
                "border"     => $_POST["style_border"][$i] ?? "#DDDDDD",
                "text"       => $_POST["style_text"][$i] ?? "#000000"
            ];
        }
    }
    $DEFAULTS["stylesets"] = $stylesets;

    save_defaults($defaultsFile, $DEFAULTS);
    $msg = "Logo & Farben gespeichert.";
    $activeTab = "view-logo";
}

/* VIEW-VARIABLEN */
$editKat   = $_POST["edit_kat"] ?? ($_GET["edit_kat"] ?? null);
$editKunde = $_GET["edit_kunde"] ?? "";
$editSigId = $_POST["edit_sig"] ?? ($_GET["edit_sig"] ?? "");

$cEdit = $CUSTOMERS[$editKunde] ?? [
    "firma"=>"", "anrede"=>"", "vorname"=>"", "nachname"=>"",
    "adresse"=>"", "plz"=>"", "ort"=>"", "land"=>"Schweiz",
    "telefon1"=>"", "telefon2"=>"", "email1"=>"", "email2"=>""
];

$sigEdit = $DEFAULTS["signaturen"][$editSigId] ?? [
    "vorname" => "",
    "name" => "",
    "position" => "",
    "bezeichnung" => "",
    "bild" => ""
];

$katGlobal = $_POST["kat_global"] ?? array_key_first($DEFAULTS["kategorien"]);
$katGlobalCfg = $katGlobal && isset($DEFAULTS["kategorien"][$katGlobal])
    ? $DEFAULTS["kategorien"][$katGlobal]
    : null;

$currentGroup = $_POST["current_group"] ?? ($_GET["current_group"] ?? array_key_first($DEFAULTS["standardgruppen"]));
rkrk_rt_emit_chrome('rkrk_rt.css');
?>
<style>
.admin-tabs { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:20px; }
.admin-tab { padding:2px 10px; min-height:22px; background:#fff; cursor:pointer; border-radius:999px; border:1px solid #c5cdd4; color:#9aa4ae; font-size:12px; font-weight:600; }
.admin-tab.active { background:#fff; border-color:#4d6b3e; color:#3f5a32; }
.admin-view { display:none; }
.admin-view.active { display:block; }
.table { width:100%; border-collapse:collapse; margin-top:10px; }
.table th, .table td { border:1px solid #DDDDDD; padding:4px 6px; font-size:0.9rem; }
.table td input, .table td select { width:100%; box-sizing:border-box; }
.ok { background:#dfd; padding:10px; margin-bottom:10px; border-radius:4px; }
.drag-handle { cursor:grab; user-select:none; text-align:center; width:24px; }
.drag-handle:active { cursor:grabbing; }
.kat-row.active { background:#d0f0d0; }
.kat-row:hover { background:#eef; cursor:pointer; }
.kat-group-row.active { background:#d0f0d0; }
.kat-group-row:hover { background:#eef; cursor:pointer; }
.group-row.active { background:#d0f0d0; }
.group-row:hover { background:#eef; cursor:pointer; }
.sig-row.active { background:#d0f0d0; }
.sig-row:hover { background:#eef; cursor:pointer; }
.kunde-row:hover { background:#eef; cursor:pointer; }
</style>

<div class="page">

<div class="header-top" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Admin – Rechnungsmodul</h1>
    <div class="header-logos">
        <img src="<?= safe(media_web_src('logo.png')) ?>" style="height:60px;">
    </div>
</div>

<div class="rkrk-row" style="justify-content: space-between; align-items:center; margin-bottom:20px;">
    <a href="rkrk_rt.php?mode=list" class="btn">⬅ Zurück zur Übersicht</a>
</div>

<?php if ($msg): ?>
<div class="ok"><?= safe($msg) ?></div>
<?php endif; ?>

<div class="admin-tabs">
    <div class="admin-tab" data-view="view-kategorien">Kategorien</div>
    <div class="admin-tab" data-view="view-kunden">Kunden</div>
    <div class="admin-tab" data-view="view-stdpos">Standardpositionen</div>
    <div class="admin-tab" data-view="view-material">Material</div>
    <div class="admin-tab" data-view="view-global">Globale Einstellungen</div>
    <div class="admin-tab" data-view="view-signaturen">Signaturen</div>
    <div class="admin-tab" data-view="view-logo">Logo &amp; Farben</div>
    <div class="admin-tab" data-view="view-counters">Zähler</div>
</div>

<!-- KATEGORIEN -->
<div class="admin-view" id="view-kategorien">

    <h2>Kategorien</h2>

    <form method="post">
        <input type="hidden" name="save_kat_order" value="1">
        <input type="hidden" name="active_tab" value="view-kategorien">
        <input type="hidden" id="kat_order_input" name="kat_order" value="">
        <input type="hidden" id="edit_kat" name="edit_kat" value="<?= safe($editKat) ?>">

        <table class="table" id="katTable">
            <tr>
                <th style="width:24px;"></th>
                <th>Kürzel</th>
                <th>Name</th>
                <th></th>
            </tr>
            <?php foreach ($DEFAULTS["kategorien_order"] as $k):
                if (!isset($DEFAULTS["kategorien"][$k])) continue;
                $cfg = $DEFAULTS["kategorien"][$k];
            ?>
            <tr class="kat-row <?= ($editKat === $k) ? 'active' : '' ?>" data-id="<?= safe($k) ?>">
                <td class="drag-handle">⋮⋮</td>
                <td><?= safe($k) ?></td>
                <td><?= safe($cfg["name"] ?? "") ?></td>
                <td>
                    <a href="?delete_kat=<?= safe($k) ?>" class="btn" onclick="return confirm('Kategorie wirklich löschen?')">🗑</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div style="margin-top:10px; text-align:right;">
            <button class="btn-primary">Reihenfolge speichern</button>
        </div>
    </form>

    <h3>Neue Kategorie</h3>
    <form method="post" class="rkrk-row" style="gap:10px; align-items:flex-end;">
        <input type="hidden" name="active_tab" value="view-kategorien">
        <div>
            <label>Kürzel</label>
            <input name="new_kat" placeholder="z.B. DR">
        </div>
        <button name="add_kat" class="btn-primary">Hinzufügen</button>
    </form>

    <?php if ($editKat && isset($DEFAULTS["kategorien"][$editKat])):
        $cfg = $DEFAULTS["kategorien"][$editKat];
    ?>
    <hr>

    <h3>Kategorie bearbeiten: <?= safe($editKat) ?></h3>

    <form method="post">
        <input type="hidden" name="save_kat" value="1">
        <input type="hidden" name="kat" value="<?= safe($editKat) ?>">
        <input type="hidden" name="active_tab" value="view-kategorien">

        <label>Name</label>
        <input name="name" value="<?= safe($cfg["name"] ?? "") ?>">

        <h4>Standardpositionen-Gruppen</h4>

        <table class="table" id="katGroupTable">
            <tr><th></th><th>Gruppe</th></tr>
            <?php foreach ($DEFAULTS["standardgruppen"] as $gid => $g): ?>
            <tr class="kat-group-row <?= in_array($gid, $cfg["gruppen"] ?? []) ? 'active' : '' ?>"
                data-id="<?= safe($gid) ?>">
                <td><input type="checkbox" <?= in_array($gid, $cfg["gruppen"] ?? []) ? 'checked' : '' ?>></td>
                <td><?= safe($g["label"] ?? "") ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <input type="hidden" name="gruppen_hidden" id="gruppen_hidden" value="<?= safe(implode(",", $cfg["gruppen"] ?? [])) ?>">

        <br><br>
        <button class="btn-primary">Kategorie speichern</button>

    </form>
    <?php endif; ?>

</div>

<!-- KUNDEN – moderne Liste -->
<div class="admin-view" id="view-kunden">

    <h2>Kunden</h2>

    <input type="text" id="kundenSearch" placeholder="🔍 Suche nach Firma, Name, Ort..." style="width:100%; padding:6px; margin-bottom:10px;">

    <table class="table" id="kundenTable">
        <thead>
            <tr>
                <th data-sort="firma">Firma</th>
                <th data-sort="name">Name</th>
                <th data-sort="ort">Ort</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($CUSTOMERS as $key => $c): ?>
            <tr class="kunde-row" data-id="<?= safe($key) ?>">
                <td><?= safe($c["firma"] ?? "") ?></td>
                <td><?= safe(($c["vorname"] ?? "")." ".($c["nachname"] ?? "")) ?></td>
                <td><?= safe($c["ort"] ?? "") ?></td>
                <td>
                    <a href="?delete_kunde=<?= safe($key) ?>&active_tab=view-kunden"
                       class="btn"
                       onclick="return confirm('Kunde wirklich löschen?')">🗑</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <h3>Kunde bearbeiten / hinzufügen</h3>

    <form method="post">
        <input type="hidden" name="save_kunde" value="1">
        <input type="hidden" name="active_tab" value="view-kunden">
        <input type="hidden" name="original_key" value="<?= safe($editKunde) ?>">

        <div class="rkrk-row">
            <div>
                <label>Firma</label>
                <input name="firma" value="<?= safe($cEdit["firma"] ?? "") ?>">
            </div>
            <div>
                <label>Anrede</label>
                <input name="anrede" value="<?= safe($cEdit["anrede"] ?? "") ?>">
            </div>
        </div>

        <div class="rkrk-row">
            <div>
                <label>Vorname</label>
                <input name="vorname" value="<?= safe($cEdit["vorname"] ?? "") ?>">
            </div>
            <div>
                <label>Nachname</label>
                <input name="nachname" value="<?= safe($cEdit["nachname"] ?? "") ?>">
            </div>
        </div>

        <div class="rkrk-row">
            <div>
                <label>Adresse</label>
                <input name="adresse" value="<?= safe($cEdit["adresse"] ?? "") ?>">
            </div>
            <div>
                <label>PLZ</label>
                <input name="plz" value="<?= safe($cEdit["plz"] ?? "") ?>">
            </div>
            <div>
                <label>Ort</label>
                <input name="ort" value="<?= safe($cEdit["ort"] ?? "") ?>">
            </div>
            <div>
                <label>Land</label>
                <input name="land" value="<?= safe($cEdit["land"] ?? "") ?>">
            </div>
        </div>

        <div class="rkrk-row">
            <div>
                <label>Telefon 1</label>
                <input name="telefon1" value="<?= safe($cEdit["telefon1"] ?? "") ?>">
            </div>
            <div>
                <label>Telefon 2</label>
                <input name="telefon2" value="<?= safe($cEdit["telefon2"] ?? "") ?>">
            </div>
        </div>

        <div class="rkrk-row">
            <div>
                <label>E‑Mail 1</label>
                <input name="email1" value="<?= safe($cEdit["email1"] ?? "") ?>">
            </div>
            <div>
                <label>E‑Mail 2</label>
                <input name="email2" value="<?= safe($cEdit["email2"] ?? "") ?>">
            </div>
        </div>

        <button class="btn-primary">Kunde speichern</button>
    </form>

</div>

<!-- STANDARDPOSITIONEN & GRUPPEN -->
<div class="admin-view" id="view-stdpos">
    <h2>Standardpositionen &amp; Gruppen</h2>

<form method="post">
    <input type="hidden" name="save_stdpos" value="1">
    <input type="hidden" name="active_tab" value="view-stdpos">
    <input type="hidden" name="current_group" id="current_group" value="<?= safe($currentGroup) ?>">

    <h3>Gruppen (Standardpositionen)</h3>

    <table class="table" id="groupTable">
        <tr><th style="width:24px;"></th><th>Gruppe</th><th></th></tr>
        <?php foreach ($DEFAULTS["standardgruppen"] as $gid => $g): ?>
        <tr class="group-row <?= $gid === $currentGroup ? 'active' : '' ?>" data-id="<?= safe($gid) ?>">
            <td class="drag-handle">⋮⋮</td>
            <td><?= safe($g["label"] ?? "") ?></td>
            <td>
                <a href="?delete_group=<?= safe($gid) ?>&active_tab=view-stdpos"
                   class="btn"
                   onclick="return confirm('Gruppe wirklich löschen?')">🗑</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="rkrk-row" style="align-items:flex-end; gap:10px; margin-top:10px;">
        <div>
            <label>Neue Gruppe</label>
            <input name="new_group_label" placeholder="Bezeichnung neue Gruppe">
        </div>
        <button name="add_group" value="1" class="btn">➕ Gruppe</button>
    </div>

    <?php
    // WICHTIG: groupCfg MUSS HIER stehen
    $groupCfg = ($currentGroup && isset($DEFAULTS["standardgruppen"][$currentGroup]))
        ? $DEFAULTS["standardgruppen"][$currentGroup]
        : ["label" => "", "positionen" => []];
    ?>

    <!-- Gruppe korrekt mitsenden (JETZT ist $groupCfg definiert!) -->
    <input type="hidden" name="grp_id[]" value="<?= safe($currentGroup) ?>">
    <input type="hidden" name="grp_label[]" value="<?= safe($groupCfg["label"] ?? "") ?>">

    <hr>

    <h3>Standardpositionen in Gruppe „<?= safe($groupCfg["label"] ?? "") ?>“</h3>

		<table class="table" id="stdposTable">
			<thead>
				<tr>
					<th style="width:24px;"></th>
					<th>Pos</th>
					<th style="display:none;">ID</th>
					<th>Label</th>
					<th>Einheit</th>
					<th>Preis</th>
					<th>Bemerkung</th>
					<th></th>
				</tr>
			</thead>

			<tbody>
			<?php $i=1; foreach ($groupCfg["positionen"] as $pid):
				if (!isset($DEFAULTS["standardpositionen"][$pid])) continue;   // FIX: $pid statt $pi
				$p = $DEFAULTS["standardpositionen"][$pid];
			?>
				<tr class="stdpos-row">
					<td class="drag-handle">⋮⋮</td>
					<td class="pos-number"><?= $i++ ?></td>

					<td style="display:none;">
						<input name="pos_id[]" value="<?= safe($pid) ?>">
					</td>

					<td>
						<input name="pos_label[]" value="<?= safe($p["label"] ?? "") ?>">
					</td>

					<!-- EINHEIT -->
					<td>
						<?php if ($pid === "RISIKO"): ?>
							<select name="pos_einheit[]" readonly style="background:#eee;">
								<option value="–">–</option>
							</select>
						<?php else: ?>
							<select name="pos_einheit[]">
								<option value="h"   <?= ($p["einheit"] ?? "")==="h"?"selected":"" ?>>h</option>
								<option value="Stk" <?= ($p["einheit"] ?? "")==="Stk"?"selected":"" ?>>Stk</option>
								<option value="km"  <?= ($p["einheit"] ?? "")==="km"?"selected":"" ?>>km</option>
								<option value="–"   <?= ($p["einheit"] ?? "")==="–"?"selected":"" ?>>–</option>
							</select>
						<?php endif; ?>
					</td>

					<!-- PREIS -->
					<td>
						<?php if ($pid === "RISIKO"): ?>
							<input name="pos_preis[]" value="0" readonly style="background:#eee;">
						<?php else: ?>
							<input name="pos_preis[]" value="<?= safe($p["preis"] ?? "") ?>">
						<?php endif; ?>
					</td>

					<!-- BEMERKUNG -->
					<td>
						<input name="pos_bemerkung[]" value="<?= safe($p["bemerkung"] ?? "") ?>">
					</td>

					<td>
						<button type="button" class="btn delete-row">🗑</button>
					</td>
				</tr>
			<?php endforeach; ?>

				<!-- NEUE POSITION -->
				<tr class="stdpos-row">
					<td class="drag-handle">⋮⋮</td>
					<td class="pos-number">+</td>

					<td style="display:none;">
						<input name="pos_id[]" value="PNEW">
					</td>

					<td>
						<input name="pos_label[]" placeholder="Neue Position">
					</td>

					<td>
						<select name="pos_einheit[]">
							<option value="h">h</option>
							<option value="Stk">Stk</option>
							<option value="km">km</option>
							<option value="–">–</option>
						</select>
					</td>

					<td><input name="pos_preis[]" value="0"></td>
					<td><input name="pos_bemerkung[]" placeholder="Bemerkung"></td>

					<td></td>
				</tr>

			</tbody>
		</table>
        <br>
        <button class="btn-primary">Standardpositionen speichern</button>

    </form>
</div>
<!-- MATERIAL -->
<div class="admin-view" id="view-material">
    <h2>Material</h2>

    <form method="post">
        <input type="hidden" name="save_material" value="1">
        <input type="hidden" name="active_tab" value="view-material">

        <table class="table" id="materialTable">
            <tr>
                <th>Label</th>
                <th>Preis</th>
                <th></th>
            </tr>
            <?php if (!empty($DEFAULTS["material_defaults"])): ?>
                <?php foreach ($DEFAULTS["material_defaults"] as $m): ?>
                <tr>
                    <td><input name="mat_label[]" value="<?= safe($m["label"] ?? "") ?>"></td>
                    <td><input name="mat_preis[]" value="<?= safe($m["preis"] ?? "") ?>"></td>
                    <td><button type="button" class="btn delete-row">🗑</button></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <button type="button" id="addMaterialRow" class="btn">➕ Material</button>
        <button type="submit" class="btn-primary" style="float:right;">Material speichern</button>
    </form>
</div>

<!-- GLOBALE EINSTELLUNGEN -->
<div class="admin-view" id="view-global">
    <h2>Globale Einstellungen pro Kategorie</h2>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="active_tab" value="view-global">

        <label>Kategorie</label>
        <select name="kat_global" onchange="this.form.submit()">
            <?php foreach ($DEFAULTS["kategorien"] as $k => $cfg): ?>
                <option value="<?= safe($k) ?>" <?= $k===$katGlobal?"selected":"" ?>>
                    <?= safe($k." – ".($cfg["name"] ?? "")) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($katGlobalCfg): ?>
        <h3>Einstellungen für <?= safe($katGlobal) ?></h3>

        <div class="rkrk-row">
            <div>
                <label>Logoset</label>
                <select name="logoset">
                    <option value="">–</option>
                    <?php foreach ($DEFAULTS["logosets"] as $lid => $ls): ?>
                        <option value="<?= safe($lid) ?>" <?= ($katGlobalCfg["logoset"] ?? "")===$lid?"selected":"" ?>>
                            <?= safe($ls["label"] ?? $lid) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Styleset</label>
                <select name="styleset">
                    <option value="">–</option>
                    <?php foreach ($DEFAULTS["stylesets"] as $sid => $ss): ?>
                        <option value="<?= safe($sid) ?>" <?= ($katGlobalCfg["styleset"] ?? "")===$sid?"selected":"" ?>>
                            <?= safe($ss["label"] ?? $sid) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h4>Finanzdaten</h4>
        <div class="rkrk-row">
            <div>
                <label>IBAN</label>
                <input name="iban_cat" value="<?= safe($katGlobalCfg["finanz"]["iban"] ?? "") ?>">
            </div>
            <div>
                <label>Empfänger</label>
                <input name="empfaenger_cat" value="<?= safe($katGlobalCfg["finanz"]["empfaenger"] ?? "") ?>">
            </div>
        </div>
        <div class="rkrk-row">
            <div>
                <label>Strasse</label>
                <input name="strasse_cat" value="<?= safe($katGlobalCfg["finanz"]["strasse"] ?? "") ?>">
            </div>
            <div>
                <label>PLZ</label>
                <input name="plz_cat" value="<?= safe($katGlobalCfg["finanz"]["plz"] ?? "") ?>">
            </div>
            <div>
                <label>Ort</label>
                <input name="ort_cat" value="<?= safe($katGlobalCfg["finanz"]["ort"] ?? "") ?>">
            </div>
            <div>
                <label>Land</label>
                <input name="land_cat" value="<?= safe($katGlobalCfg["finanz"]["land"] ?? "") ?>">
            </div>
        </div>
        <div class="rkrk-row">
            <div>
                <label>Finanzinstitut</label>
                <input name="bank_cat" value="<?= safe($katGlobalCfg["finanz"]["bank"] ?? $katGlobalCfg["finanz"]["finanzinstitut"] ?? "") ?>" placeholder="z.B. Credit Suisse (Schweiz) AG">
            </div>
            <div>
                <label>PLZ / Ort (Finanzinstitut)</label>
                <input name="bank_ort_cat" value="<?= safe($katGlobalCfg["finanz"]["bank_ort"] ?? $katGlobalCfg["finanz"]["bank_plz_ort"] ?? "") ?>" placeholder="z.B. 8070 Zürich">
            </div>
            <div>
                <label>BIC / SWIFT</label>
                <input name="bic_cat" value="<?= safe($katGlobalCfg["finanz"]["bic"] ?? $katGlobalCfg["finanz"]["bic_swift"] ?? "") ?>" placeholder="z.B. CRESCHZZ80A">
            </div>
        </div>

        <h4>Zusatzinfo</h4>
        <textarea name="zusatzinfo_cat" rows="3" style="width:100%;"><?= safe($katGlobalCfg["zusatzinfo"] ?? "") ?></textarea>
        <h4>Abschlussslogan</h4>
        <textarea name="abschlussslogan_cat" rows="2" style="width:100%;"><?= safe($katGlobalCfg["abschlussslogan"] ?? "") ?></textarea>

        <h4>QR &amp; Wasserzeichen</h4>
        <div class="rkrk-row">
            <div>
                <label>Swiss QR-Code</label>
                <input type="file" name="swissqrcode_cat">
                <?php if (!empty($katGlobalCfg["swissqrcode"])): ?>
                    <div><img src="<?= safe(media_web_src((string)$katGlobalCfg["swissqrcode"])) ?>" style="max-height:80px;"></div>
                <?php endif; ?>
            </div>
            <div>
                <label>Wasserzeichen</label>
                <input type="file" name="watermarks_cat">
                <?php if (!empty($katGlobalCfg["watermarks"])): ?>
                    <div><img src="<?= safe(media_web_src((string)$katGlobalCfg["watermarks"])) ?>" style="max-height:80px;"></div>
                <?php endif; ?>
            </div>
        </div>

        <button class="btn-primary" name="save_global_cat" value="1" style="margin-top:10px;">Globale Einstellungen speichern</button>
        <?php endif; ?>
    </form>
</div>

<!-- SIGNATUREN -->
<div class="admin-view" id="view-signaturen">
    <h2>Signaturen</h2>

    <form method="post">
        <input type="hidden" name="save_sig_order" value="1">
        <input type="hidden" name="active_tab" value="view-signaturen">
        <input type="hidden" id="sig_order_input" name="sig_order" value="">

        <table class="table" id="sigTable">
            <tr>
                <th style="width:24px;"></th>
                <th>Name</th>
                <th>Position</th>
                <th>Bezeichnung</th>
                <th></th>
            </tr>
            <?php foreach ($DEFAULTS["signaturen_order"] as $sid):
                if (!isset($DEFAULTS["signaturen"][$sid])) continue;
                $s = $DEFAULTS["signaturen"][$sid];
            ?>
            <tr class="sig-row <?= $editSigId===$sid?'active':'' ?>" data-id="<?= safe($sid) ?>">
                <td class="drag-handle">⋮⋮</td>
                <td><?= safe(($s["vorname"] ?? "")." ".($s["name"] ?? "")) ?></td>
                <td><?= safe($s["position"] ?? "") ?></td>
                <td><?= safe($s["bezeichnung"] ?? "") ?></td>
                <td>
                    <a href="?delete_sig=<?= safe($sid) ?>&active_tab=view-signaturen"
                       class="btn"
                       onclick="return confirm('Signatur wirklich löschen?')">🗑</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div style="margin-top:10px; text-align:right;">
            <button class="btn-primary">Reihenfolge speichern</button>
        </div>
    </form>

    <hr>

    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
        <h3 style="margin:0;"><?= $editSigId !== "" ? "Signatur bearbeiten" : "Neue Signatur anlegen" ?></h3>
        <a class="btn" href="rkrk_rt_admin.php?active_tab=view-signaturen">+ Neue Signatur</a>
    </div>
    <p style="margin:6px 0 12px; font-size:12px; color:#555;">Zum Anlegen einer weiteren Signatur: «Neue Signatur» klicken. Zum Bearbeiten eine Zeile in der Tabelle anklicken.</p>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="save_sig" value="1">
        <input type="hidden" name="active_tab" value="view-signaturen">
        <input type="hidden" name="sig_id" value="<?= safe($editSigId) ?>">

        <div class="rkrk-row">
            <div>
                <label>Vorname</label>
                <input name="vorname" value="<?= safe($sigEdit["vorname"] ?? "") ?>">
            </div>
            <div>
                <label>Name</label>
                <input name="name" value="<?= safe($sigEdit["name"] ?? "") ?>">
            </div>
        </div>

        <div class="rkrk-row">
            <div>
                <label>Position</label>
                <input name="position" value="<?= safe($sigEdit["position"] ?? "") ?>">
            </div>
            <div>
                <label>Bezeichnung</label>
                <input name="bezeichnung" value="<?= safe($sigEdit["bezeichnung"] ?? "") ?>">
            </div>
        </div>

        <div class="rkrk-row">
            <div>
                <label>Bild</label>
                <input type="file" name="bild">
                <?php if (!empty($sigEdit["bild"])): ?>
                    <div><img src="<?= safe(media_web_src((string)$sigEdit["bild"])) ?>" style="max-height:80px;"></div>
                <?php endif; ?>
            </div>
        </div>

        <button class="btn-primary">Signatur speichern</button>
    </form>
</div>

<!-- LOGO & FARBEN -->
<div class="admin-view" id="view-logo">

    <h2>Logo &amp; Farben</h2>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="save_logo_colors" value="1">
        <input type="hidden" name="active_tab" value="view-logo">

        <h3>Logosets</h3>

        <table class="table" id="logoTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bezeichnung</th>
                    <th>Logo</th>
                    <th>Upload</th>
                    <th>Löschen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($DEFAULTS["logosets"] as $id => $set): ?>
            <tr>
                <td><input name="logo_set_id[]" value="<?= safe($id) ?>"></td>
                <td><input name="logo_set_label[]" value="<?= safe($set["label"] ?? "") ?>"></td>
                <td>
                    <?php if (!empty($set["logo"])): ?>
                        <img src="<?= safe(media_web_src((string)$set["logo"])) ?>" style="max-height:40px;">
                    <?php endif; ?>
                    <input type="hidden" name="logo_existing[]" value="<?= safe($set["logo"] ?? "") ?>">
                </td>
                <td><input type="file" name="logo_file[]"></td>
                <td>
                    <button type="button" class="btn delete-row">🗑</button>
                </td>
            </tr>
            <?php endforeach; ?>

            <!-- Neue Zeile -->
            <tr>
                <td><input name="logo_set_id[]" placeholder="z.B. std"></td>
                <td><input name="logo_set_label[]" placeholder="Bezeichnung"></td>
                <td><input type="hidden" name="logo_existing[]" value=""></td>
                <td><input type="file" name="logo_file[]"></td>
                <td><button type="button" class="btn delete-row">🗑</button></td>
            </tr>
            </tbody>
        </table>
        <button type="button" class="btn" id="addLogoRow">+ Logoset Zeile</button>

        <h3>Stylesets (Farben)</h3>

        <table class="table" id="styleTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bezeichnung</th>
                    <th>Primär</th>
                    <th>Hintergrund</th>
                    <th>Rahmen</th>
                    <th>Text</th>
                    <th>Löschen</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($DEFAULTS["stylesets"] as $id => $set): ?>
            <tr>
                <td><input name="style_set_id[]" value="<?= safe($id) ?>"></td>
                <td><input name="style_set_label[]" value="<?= safe($set["label"] ?? "") ?>"></td>

                <!-- PRIMÄRFARBE -->
                <td>
                    <div class="color-field">
                        <input type="color"
                               class="color-picker"
                               value="<?= safe($set["primary"] ?? "#007A3D") ?>"
                               oninput="this.nextElementSibling.value=this.value;">
                        <input type="text"
                               class="color-hex"
                               name="style_primary[]"
                               value="<?= safe($set["primary"] ?? "#007A3D") ?>"
                               oninput="this.previousElementSibling.value=this.value;">
                    </div>
                </td>

                <!-- HINTERGRUND -->
                <td>
                    <div class="color-field">
                        <input type="color"
                               class="color-picker"
                               value="<?= safe($set["background"] ?? "#FFFFFF") ?>"
                               oninput="this.nextElementSibling.value=this.value;">
                        <input type="text"
                               class="color-hex"
                               name="style_background[]"
                               value="<?= safe($set["background"] ?? "#FFFFFF") ?>"
                               oninput="this.previousElementSibling.value=this.value;">
                    </div>
                </td>

                <!-- RAHMEN -->
                <td>
                    <div class="color-field">
                        <input type="color"
                               class="color-picker"
                               value="<?= safe($set["border"] ?? "#DDDDDD") ?>"
                               oninput="this.nextElementSibling.value=this.value;">
                        <input type="text"
                               class="color-hex"
                               name="style_border[]"
                               value="<?= safe($set["border"] ?? "#DDDDDD") ?>"
                               oninput="this.previousElementSibling.value=this.value;">
                    </div>
                </td>

                <!-- TEXT -->
                <td>
                    <div class="color-field">
                        <input type="color"
                               class="color-picker"
                               value="<?= safe($set["text"] ?? "#000000") ?>"
                               oninput="this.nextElementSibling.value=this.value;">
                        <input type="text"
                               class="color-hex"
                               name="style_text[]"
                               value="<?= safe($set["text"] ?? "#000000") ?>"
                               oninput="this.previousElementSibling.value=this.value;">
                    </div>
                </td>
                <td>
                    <button type="button" class="btn delete-row">🗑</button>
                </td>
            </tr>
            <?php endforeach; ?>

            <!-- Neue Zeile -->
            <tr>
                <td><input name="style_set_id[]" placeholder="z.B. std"></td>
                <td><input name="style_set_label[]" placeholder="Bezeichnung"></td>

                <td>
                    <div class="color-field">
                        <input type="color" class="color-picker" value="#007A3D"
                               oninput="this.nextElementSibling.value=this.value;">
                        <input type="text" class="color-hex" name="style_primary[]" value="#007A3D"
                               oninput="this.previousElementSibling.value=this.value;">
                    </div>
                </td>

                <td>
                    <div class="color-field">
                        <input type="color" class="color-picker" value="#FFFFFF"
                               oninput="this.nextElementSibling.value=this.value;">
                        <input type="text" class="color-hex" name="style_background[]" value="#FFFFFF"
                               oninput="this.previousElementSibling.value=this.value;">
                    </div>
                </td>

                <td>
                    <div class="color-field">
                        <input type="color" class="color-picker" value="#DDDDDD"
                               oninput="this.nextElementSibling.value=this.value;">
                        <input type="text" class="color-hex" name="style_border[]" value="#DDDDDD"
                               oninput="this.previousElementSibling.value=this.value;">
                    </div>
                </td>

                <td>
                    <div class="color-field">
                        <input type="color" class="color-picker" value="#000000"
                               oninput="this.nextElementSibling.value=this.value;">
                        <input type="text" class="color-hex" name="style_text[]" value="#000000"
                               oninput="this.previousElementSibling.value=this.value;">
                    </div>
                </td>
                <td><button type="button" class="btn delete-row">🗑</button></td>
            </tr>

            </tbody>
        </table>
        <button type="button" class="btn" id="addStyleRow">+ Styleset Zeile</button>

        <br>
        <button class="btn-primary">Logo &amp; Farben speichern</button>
    </form>

</div>

<style>
.color-field {
    display:flex;
    gap:6px;
    align-items:center;
}
.color-picker {
    width:40px;
    height:32px;
    padding:0;
    border:1px solid #ccc;
}
.color-hex {
    width:90px;
}
</style>
<!-- ZÄHLER -->
<div class="admin-view" id="view-counters">
    <h2>Zähler</h2>

    <form method="post">
        <input type="hidden" name="save_counters" value="1">
        <input type="hidden" name="active_tab" value="view-counters">

        <table class="table">
            <tr>
                <th>Typ</th>
                <th>Wert</th>
            </tr>
            <?php foreach ($COUNTERS as $key => $val): ?>
            <tr>
                <td><?= safe($key) ?></td>
                <td><input name="counter_<?= safe($key) ?>" value="<?= safe($val) ?>"></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <button class="btn-primary" style="margin-top:10px;">Zähler speichern</button>
    </form>
</div>

</div><!-- .page -->

<script>
// Tabs
(function() {
    const tabs = document.querySelectorAll('.admin-tab');
    const views = document.querySelectorAll('.admin-view');
    const activeTab = "<?= safe($activeTab ?: 'view-kategorien') ?>";

    function showView(id) {
        views.forEach(v => v.classList.toggle('active', v.id === id));
        tabs.forEach(t => t.classList.toggle('active', t.dataset.view === id));
    }

    tabs.forEach(t => {
        t.addEventListener('click', () => {
            const v = t.dataset.view;
            showView(v);
        });
    });

    showView(activeTab);
})();

// Kategorien: Reihenfolge + Auswahl
(function() {
    const table = document.getElementById('katTable');
    if (!table) return;
    const orderInput = document.getElementById('kat_order_input');
    const editKatInput = document.getElementById('edit_kat');

    function updateOrder() {
        const ids = [];
        table.querySelectorAll('tr.kat-row').forEach(tr => {
            ids.push(tr.getAttribute('data-id'));
        });
        orderInput.value = ids.join(',');
    }
    updateOrder();

    table.querySelectorAll('tr.kat-row').forEach(tr => {
        tr.addEventListener('click', (e) => {
            if (e.target.closest('a')) return;
            const id = tr.getAttribute('data-id');
            editKatInput.value = id;
            const form = table.closest('form');
            form.submit();
        });
    });

    let dragSrc = null;
    table.querySelectorAll('.drag-handle').forEach(handle => {
        const row = handle.closest('tr');
        row.draggable = true;

        row.addEventListener('dragstart', (e) => {
            dragSrc = row;
            e.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        row.addEventListener('drop', (e) => {
            e.preventDefault();
            if (dragSrc && dragSrc !== row) {
                const tbody = row.parentNode;
                const rows = Array.from(tbody.children);
                const srcIndex = rows.indexOf(dragSrc);
                const destIndex = rows.indexOf(row);
                if (srcIndex < destIndex) {
                    tbody.insertBefore(dragSrc, row.nextSibling);
                } else {
                    tbody.insertBefore(dragSrc, row);
                }
                updateOrder();
            }
        });
    });
})();

// Kategorie-Gruppen Checkboxen
(function() {
    const table = document.getElementById('katGroupTable');
    if (!table) return;
    const hidden = document.getElementById('gruppen_hidden');

    function updateHidden() {
        const ids = [];
        table.querySelectorAll('tr.kat-group-row').forEach(tr => {
            const cb = tr.querySelector('input[type=checkbox]');
            if (cb && cb.checked) {
                ids.push(tr.getAttribute('data-id'));
            }
        });
        hidden.value = ids.join(',');
    }

    table.querySelectorAll('tr.kat-group-row').forEach(tr => {
        const cb = tr.querySelector('input[type=checkbox]');
        if (!cb) return;
        cb.addEventListener('change', () => {
            tr.classList.toggle('active', cb.checked);
            updateHidden();
        });
        tr.addEventListener('click', (e) => {
            if (e.target.tagName.toLowerCase() === 'input') return;
            cb.checked = !cb.checked;
            tr.classList.toggle('active', cb.checked);
            updateHidden();
        });
    });

    updateHidden();
})();

// Kunden: Suche + Klick zum Bearbeiten
(function() {
    const search = document.getElementById('kundenSearch');
    const table = document.getElementById('kundenTable');
    if (!search || !table) return;

    search.addEventListener('input', () => {
        const q = search.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(tr => {
            const text = tr.innerText.toLowerCase();
            tr.style.display = text.indexOf(q) >= 0 ? '' : 'none';
        });
    });

    table.querySelectorAll('tr.kunde-row').forEach(tr => {
        tr.addEventListener('click', (e) => {
            if (e.target.closest('a')) return;
            const id = tr.getAttribute('data-id');
            const url = new URL(window.location.href);
            url.searchParams.set('edit_kunde', id);
            url.searchParams.set('active_tab', 'view-kunden');
            window.location.href = url.toString();
        });
    });
})();

// Gruppen: Auswahl
(function() {
    const table = document.getElementById('groupTable');
    const currentInput = document.getElementById('current_group');
    if (!table || !currentInput) return;

    table.querySelectorAll('tr.group-row').forEach(tr => {
        tr.addEventListener('click', (e) => {
            if (e.target.closest('a')) return;
            const id = tr.getAttribute('data-id');
            currentInput.value = id;
            const form = table.closest('form');
            form.submit();
        });
    });
})();

// Standardpositionen Drag & Drop + Delete + Renumber
(function() {
    const table = document.getElementById('stdposTable');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    let dragSrc = null;

    function renumber() {
        let i = 1;
        tbody.querySelectorAll('tr.stdpos-row').forEach(tr => {
            const cell = tr.querySelector('.pos-number');
            if (cell) cell.textContent = i++;
        });
    }

    function attachRowEvents(tr) {
        const delBtn = tr.querySelector('.delete-row');
        if (delBtn) {
            delBtn.addEventListener('click', () => {
                tr.remove();
                renumber();
            });
        }

        const handle = tr.querySelector('.drag-handle');
        if (handle) {
            tr.draggable = true;

            tr.addEventListener('dragstart', e => {
                dragSrc = tr;
                e.dataTransfer.effectAllowed = 'move';
            });

            tr.addEventListener('dragover', e => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            tr.addEventListener('drop', e => {
                e.preventDefault();
                if (dragSrc && dragSrc !== tr) {
                    const rows = Array.from(tbody.querySelectorAll('tr.stdpos-row'));
                    const srcIndex = rows.indexOf(dragSrc);
                    const destIndex = rows.indexOf(tr);

                    if (srcIndex < destIndex) {
                        tbody.insertBefore(dragSrc, tr.nextSibling);
                    } else {
                        tbody.insertBefore(dragSrc, tr);
                    }
                    renumber();
                }
            });
        }
    }

    tbody.querySelectorAll('tr.stdpos-row').forEach(attachRowEvents);
    renumber();
})();

// Material: Add/Delete
(function() {
    const table = document.getElementById('materialTable');
    const addBtn = document.getElementById('addMaterialRow');
    if (!table || !addBtn) return;

    function attachRowEvents(tr) {
        const delBtn = tr.querySelector('.delete-row');
        if (delBtn) {
            delBtn.addEventListener('click', () => {
                tr.parentNode.removeChild(tr);
            });
        }
    }

    table.querySelectorAll('tr').forEach(tr => {
        if (tr.querySelector('input[name="mat_label[]"]')) {
            attachRowEvents(tr);
        }
    });

    addBtn.addEventListener('click', () => {
        const tbody = table.querySelector('tbody') || table;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input name="mat_label[]" value=""></td>
            <td><input name="mat_preis[]" value=""></td>
            <td><button type="button" class="btn delete-row">🗑</button></td>
        `;
        tbody.appendChild(tr);
        attachRowEvents(tr);
    });
})();

// Logosets: Add/Delete
(function() {
    const table = document.getElementById('logoTable');
    const addBtn = document.getElementById('addLogoRow');
    if (!table || !addBtn) return;

    function attachRowEvents(tr) {
        const delBtn = tr.querySelector('.delete-row');
        if (delBtn) {
            delBtn.addEventListener('click', () => {
                tr.parentNode.removeChild(tr);
            });
        }
    }

    table.querySelectorAll('tr').forEach(tr => {
        if (tr.querySelector('input[name="logo_set_id[]"]')) {
            attachRowEvents(tr);
        }
    });

    addBtn.addEventListener('click', () => {
        const tbody = table.querySelector('tbody') || table;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input name="logo_set_id[]" value=""></td>
            <td><input name="logo_set_label[]" value=""></td>
            <td><input type="hidden" name="logo_existing[]" value=""></td>
            <td><input type="file" name="logo_file[]"></td>
            <td><button type="button" class="btn delete-row">🗑</button></td>
        `;
        tbody.appendChild(tr);
        attachRowEvents(tr);
    });
})();

// Stylesets: Add/Delete
(function() {
    const table = document.getElementById('styleTable');
    const addBtn = document.getElementById('addStyleRow');
    if (!table || !addBtn) return;

    function attachRowEvents(tr) {
        const delBtn = tr.querySelector('.delete-row');
        if (delBtn) {
            delBtn.addEventListener('click', () => {
                tr.parentNode.removeChild(tr);
            });
        }
    }

    table.querySelectorAll('tr').forEach(tr => {
        if (tr.querySelector('input[name="style_set_id[]"]')) {
            attachRowEvents(tr);
        }
    });

    addBtn.addEventListener('click', () => {
        const tbody = table.querySelector('tbody') || table;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input name="style_set_id[]" value=""></td>
            <td><input name="style_set_label[]" value=""></td>
            <td><input type="color" name="style_primary[]" value="#007A3D"></td>
            <td><input type="color" name="style_background[]" value="#FFFFFF"></td>
            <td><input type="color" name="style_border[]" value="#DDDDDD"></td>
            <td><input type="color" name="style_text[]" value="#000000"></td>
            <td><button type="button" class="btn delete-row">🗑</button></td>
        `;
        tbody.appendChild(tr);
        attachRowEvents(tr);
    });
})();

// Signaturen: Reihenfolge
(function() {
    const table = document.getElementById('sigTable');
    const orderInput = document.getElementById('sig_order_input');
    if (!table || !orderInput) return;

    function updateOrder() {
        const ids = [];
        table.querySelectorAll('tr.sig-row').forEach(tr => {
            ids.push(tr.getAttribute('data-id'));
        });
        orderInput.value = ids.join(',');
    }
    updateOrder();

    let dragSrc = null;
    table.querySelectorAll('.drag-handle').forEach(handle => {
        const row = handle.closest('tr');
        row.draggable = true;

        row.addEventListener('dragstart', (e) => {
            dragSrc = row;
            e.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        row.addEventListener('drop', (e) => {
            e.preventDefault();
            if (dragSrc && dragSrc !== row) {
                const tbody = row.parentNode;
                const rows = Array.from(tbody.children).filter(r => r.classList.contains('sig-row'));
                const srcIndex = rows.indexOf(dragSrc);
                const destIndex = rows.indexOf(row);
                if (srcIndex < destIndex) {
                    tbody.insertBefore(dragSrc, row.nextSibling);
                } else {
                    tbody.insertBefore(dragSrc, row);
                }
                updateOrder();
            }
        });
    });

    table.querySelectorAll('tr.sig-row').forEach(tr => {
        tr.addEventListener('click', (e) => {
            if (e.target.closest('a')) return;
            const id = tr.getAttribute('data-id');
            const url = new URL(window.location.href);
            url.searchParams.set('edit_sig', id);
            url.searchParams.set('active_tab', 'view-signaturen');
            window.location.href = url.toString();
        });
    });
})();
</script>

</body>
</html>
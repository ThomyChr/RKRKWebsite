<?php
/**
 * Kategorie → Logo/Farbe aus invoice_default.json (logosets + kategorien).
 * Von rkrk_rt.php und rkrk_pdf.php gemeinsam genutzt.
 */

function logo_path_for_web(string $path): string {
  $path = str_replace('\\', '/', trim($path));
  if ($path === '') {
    return '';
  }
  if (preg_match('#^https?://#i', $path)) {
    return $path;
  }
  $path = ltrim($path, '/');
  if (strpos($path, 'data/rt/') === 0 || strpos($path, '../data/rt/') === 0) {
    return $path;
  }
  if (strpos($path, 'images/') === 0) {
    return rtrim(rkrk_rt_web_base(), '/') . '/' . $path;
  }
  if (strpos($path, '/') === false) {
    return rtrim(rkrk_rt_web_base(), '/') . '/images/' . $path;
  }
  return $path;
}

function rkrk_rt_web_base(): string {
  return rtrim((string)($GLOBALS['RkrkLocation'] ?? ''), "/\\") . '/data/rt';
}

function rkrk_rt_base_dir(): string {
  $resolved = realpath(__DIR__ . '/../../rkrk/data/rt');
  return $resolved !== false ? $resolved : (__DIR__ . '/../../rkrk/data/rt');
}

/** Kategorie-Config: exakter Key, sonst Key per normalisiertem Code (SC/RK/…) */
function category_conf_for_code(string $katNorm, array $KATEGORIEN): array {
  $katNorm = preg_replace('/[^A-Z]/', '', strtoupper($katNorm));
  if ($katNorm === '') {
    $katNorm = 'SC';
  }
  if (isset($KATEGORIEN[$katNorm]) && is_array($KATEGORIEN[$katNorm])) {
    return $KATEGORIEN[$katNorm];
  }
  foreach ($KATEGORIEN as $k => $conf) {
    if (!is_array($conf)) {
      continue;
    }
    $kn = preg_replace('/[^A-Z]/', '', strtoupper((string) $k));
    if ($kn === $katNorm) {
      return $conf;
    }
  }
  return [];
}

/**
 * Kanonischer Kategorie-Code für Rechnung/Beispiel (wie in JSON kategorien):
 * Raw-Wert, Name/Label aus JSON, oder aus ID (z. B. RE2026XX0004 → XX).
 */
function resolve_kategorie_key(string $rawKat, string $docId, array $KATEGORIEN): string {
  $raw = trim($rawKat);
  $norm = preg_replace('/[^A-Z]/', '', strtoupper($raw));

  if ($KATEGORIEN) {
    foreach (array_keys($KATEGORIEN) as $k) {
      if (strcasecmp((string) $k, $raw) === 0) {
        return preg_replace('/[^A-Z]/', '', strtoupper((string) $k)) ?: 'SC';
      }
    }
    if ($norm !== '') {
      foreach (array_keys($KATEGORIEN) as $k) {
        $kn = preg_replace('/[^A-Z]/', '', strtoupper((string) $k));
        if ($kn === $norm) {
          return $norm;
        }
      }
    }
    if ($raw !== '') {
      foreach ($KATEGORIEN as $k => $conf) {
        if (!is_array($conf)) {
          continue;
        }
        $name = trim((string) ($conf['name'] ?? ''));
        $label = trim((string) ($conf['label'] ?? ''));
        if ($name !== '' && strcasecmp($raw, $name) === 0) {
          return preg_replace('/[^A-Z]/', '', strtoupper((string) $k)) ?: 'SC';
        }
        if ($label !== '' && strcasecmp($raw, $label) === 0) {
          return preg_replace('/[^A-Z]/', '', strtoupper((string) $k)) ?: 'SC';
        }
      }
    }
    $id = trim($docId);
    if ($id !== '' && preg_match('/^[A-Za-z]+\d{4}([A-Za-z]{1,8})\d{4}$/', $id, $m)) {
      $fromId = preg_replace('/[^A-Z]/', '', strtoupper($m[1]));
      if ($fromId !== '') {
        foreach (array_keys($KATEGORIEN) as $k) {
          $kn = preg_replace('/[^A-Z]/', '', strtoupper((string) $k));
          if ($kn === $fromId) {
            return $fromId;
          }
        }
      }
    }
  }

  return $norm !== '' ? $norm : 'SC';
}

/** Design: logosets[kategorien[kat].logoset].logo, Fallbacks wie in JSON */
function category_design(string $kat, array $KATEGORIEN, array $defaults = []): array {
  $kat = preg_replace('/[^A-Z]/', '', strtoupper($kat));
  if ($kat === '') {
    $kat = 'SC';
  }
  $conf = category_conf_for_code($kat, $KATEGORIEN);

  $color = trim((string) ($conf['farbe'] ?? ($conf['color'] ?? '')));
  if ($color === '') {
    $st = isset($conf['styleset']) ? trim((string) $conf['styleset']) : '';
    $stylesets = isset($defaults['stylesets']) && is_array($defaults['stylesets']) ? $defaults['stylesets'] : [];
    if ($st !== '' && isset($stylesets[$st]['primary']) && (string) $stylesets[$st]['primary'] !== '') {
      $color = (string) $stylesets[$st]['primary'];
    }
  }
  if ($color === '') {
    $color = '#333';
  }

  $logo = '';
  $logosets = isset($defaults['logosets']) && is_array($defaults['logosets']) ? $defaults['logosets'] : [];
  $lsKey = isset($conf['logoset']) ? trim((string) $conf['logoset']) : '';
  if ($lsKey !== '' && !isset($logosets[$lsKey])) {
    foreach ($logosets as $lk => $lv) {
      if (is_array($lv) && strcasecmp((string) $lk, $lsKey) === 0) {
        $lsKey = (string) $lk;
        break;
      }
    }
  }
  if ($lsKey !== '' && isset($logosets[$lsKey]['logo']) && $logosets[$lsKey]['logo'] !== '') {
    $logo = logo_path_for_web((string) $logosets[$lsKey]['logo']);
  }
  if ($logo === '') {
    if (!empty($conf['logo'])) {
      $logo = logo_path_for_web((string) $conf['logo']);
    } elseif (!empty($conf['logo_sky'])) {
      $logo = logo_path_for_web('logo_sky.png');
    } elseif (!empty($conf['logo_rk'])) {
      $logo = logo_path_for_web('logo_rk.png');
    } elseif (!empty($conf['watermarks'])) {
      $logo = logo_path_for_web((string) $conf['watermarks']);
    } elseif (!empty($conf['swissqrcode'])) {
      $logo = logo_path_for_web((string) $conf['swissqrcode']);
    }
  }

  if ($logo !== '' && strpos($logo, 'http') !== 0) {
    $check = __DIR__ . '/' . $logo;
    if (!is_file($check)) {
      $alt1 = __DIR__ . '/images/' . basename($logo);
      $alt2 = __DIR__ . '/assets/' . basename($logo);
      $alt3 = rkrk_rt_base_dir() . '/images/' . basename($logo);
      if (is_file($alt1)) {
        $logo = logo_path_for_web((string) basename($logo));
      } elseif (is_file($alt2)) {
        $logo = 'assets/' . basename($logo);
      } elseif (is_file($alt3)) {
        $logo = logo_path_for_web((string) basename($logo));
      } else {
        $logo = '';
      }
    }
  }

  return ['kat' => $kat, 'color' => $color, 'logo' => $logo];
}

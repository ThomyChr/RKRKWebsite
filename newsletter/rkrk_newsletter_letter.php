<?php
/**
 * Gemeindebrief im Layout der Rechnungsübersicht (Kopf, A4, Einzahlungsschein).
 */

function nl_rt_data_dir() {
	$resolved = realpath(dirname(__DIR__) . '/../rkrk/data/rt');
	return $resolved !== false ? $resolved : (dirname(__DIR__) . '/../rkrk/data/rt');
}

function nl_rt_defaults() {
	static $data = null;
	if ($data !== null) {
		return $data;
	}
	$file = nl_rt_data_dir() . '/invoice_default.json';
	$decoded = is_file($file) ? json_decode((string)file_get_contents($file), true) : array();
	$data = is_array($decoded) ? $decoded : array();
	return $data;
}

function nl_rt_categories() {
	$defaults = nl_rt_defaults();
	$kats = (isset($defaults['kategorien']) && is_array($defaults['kategorien'])) ? $defaults['kategorien'] : array();
	$out = array();
	foreach ($kats as $key => $cfg) {
		if (!is_array($cfg)) {
			continue;
		}
		$out[(string)$key] = trim((string)($cfg['name'] ?? $key));
	}
	return $out;
}

function nl_rt_data_uri($path) {
	if ($path === '' || !is_file($path)) {
		return '';
	}
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	$mime = 'application/octet-stream';
	if ($ext === 'png') {
		$mime = 'image/png';
	} elseif ($ext === 'jpg' || $ext === 'jpeg') {
		$mime = 'image/jpeg';
	} elseif ($ext === 'gif') {
		$mime = 'image/gif';
	} elseif ($ext === 'svg') {
		$mime = 'image/svg+xml';
	}
	return 'data:' . $mime . ';base64,' . base64_encode((string)file_get_contents($path));
}

function nl_rt_image($name) {
	$name = basename(str_replace('\\', '/', (string)$name));
	if ($name === '') {
		return '';
	}
	$candidates = array(
		nl_rt_data_dir() . '/images/' . $name,
		nl_rt_data_dir() . '/' . $name,
		dirname(__DIR__) . '/rt/images/' . $name,
		dirname(__DIR__) . '/rt/' . $name,
	);
	foreach ($candidates as $path) {
		if (is_file($path)) {
			return $path;
		}
	}
	return '';
}

function nl_signature_src($absender) {
	$want = preg_replace('/\s+/', ' ', trim((string)$absender));
	$want = function_exists('mb_strtolower') ? mb_strtolower($want, 'UTF-8') : strtolower($want);
	if ($want === '') {
		return '';
	}
	$defaults = nl_rt_defaults();
	$sigs = (isset($defaults['signaturen']) && is_array($defaults['signaturen'])) ? $defaults['signaturen'] : array();
	$best = '';
	$bestScore = -1;
	foreach ($sigs as $row) {
		if (!is_array($row)) {
			continue;
		}
		$full = trim((string)($row['vorname'] ?? '') . ' ' . (string)($row['name'] ?? ''));
		$full = function_exists('mb_strtolower') ? mb_strtolower($full, 'UTF-8') : strtolower($full);
		if ($full === '' || $full !== $want) {
			continue;
		}
		$path = nl_rt_image((string)($row['bild'] ?? ''));
		if ($path === '') {
			continue;
		}
		$score = 1;
		if (trim((string)($row['position'] ?? '')) !== '' || trim((string)($row['bezeichnung'] ?? '')) !== '') {
			$score = 2;
		}
		if ($score > $bestScore) {
			$bestScore = $score;
			$best = nl_rt_data_uri($path);
		}
	}
	return $best;
}

function nl_chf_amount($betrag) {
	$raw = str_replace(array("'", '’', ' '), '', (string)$betrag);
	$raw = str_replace(',', '.', $raw);
	if ($raw === '' || !is_numeric($raw)) {
		return '';
	}
	return number_format((float)$raw, 2, '.', '’');
}

function nl_format_iban($iban) {
	$iban = strtoupper(preg_replace('/\s+/', '', (string)$iban));
	if ($iban === '') {
		return '';
	}
	return trim(chunk_split($iban, 4, ' '));
}

function nl_letter_pack($katKey) {
	$defaults = nl_rt_defaults();
	$kats = (isset($defaults['kategorien']) && is_array($defaults['kategorien'])) ? $defaults['kategorien'] : array();
	$key = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)$katKey));
	if ($key === '' || !isset($kats[$key])) {
		$key = isset($kats['RK']) ? 'RK' : (string)array_key_first($kats);
	}
	$cat = (isset($kats[$key]) && is_array($kats[$key])) ? $kats[$key] : array();
	$styles = (isset($defaults['stylesets']) && is_array($defaults['stylesets'])) ? $defaults['stylesets'] : array();
	$logos = (isset($defaults['logosets']) && is_array($defaults['logosets'])) ? $defaults['logosets'] : array();
	$styleKey = (string)($cat['styleset'] ?? '');
	$style = (isset($styles[$styleKey]) && is_array($styles[$styleKey])) ? $styles[$styleKey] : array();
	$color = (string)($style['primary'] ?? '#6F8B5E');
	$border = (string)($style['border'] ?? '#000000');
	$logoName = '';
	$ls = (string)($cat['logoset'] ?? '');
	if ($ls !== '' && isset($logos[$ls]['logo'])) {
		$logoName = (string)$logos[$ls]['logo'];
	}
	$finanz = (isset($cat['finanz']) && is_array($cat['finanz'])) ? $cat['finanz'] : array();
	$root = (isset($defaults['konto']) && is_array($defaults['konto'])) ? $defaults['konto'] : array();
	$finanz = array_merge($root, $finanz);
	$street = trim((string)($finanz['strasse'] ?? ''));
	$city = trim(trim((string)($finanz['plz'] ?? '')) . ' ' . trim((string)($finanz['ort'] ?? '')));
	$iban = nl_format_iban((string)($finanz['iban'] ?? ''));
	$bankLines = array();
	$bankName = trim((string)($finanz['bank'] ?? ''));
	$bankOrt = trim((string)($finanz['bank_ort'] ?? ''));
	if ($bankName !== '' || $bankOrt !== '') {
		$bankLines[] = 'Finanzinstitut:';
		if ($bankName !== '') {
			$bankLines[] = $bankName;
		}
		if ($bankOrt !== '') {
			$bankLines[] = $bankOrt;
		}
	}
	$bic = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($finanz['bic'] ?? '')));
	if ($bic !== '') {
		$bankLines[] = 'BIC/SWIFT:';
		$bankLines[] = $bic;
	}
	if ($iban !== '') {
		$bankLines[] = 'IBAN:';
		$bankLines[] = $iban;
	}
	$logoPath = nl_rt_image($logoName);
	$qrPath = nl_rt_image((string)($cat['swissqrcode'] ?? ''));
	return array(
		'key' => $key,
		'name' => (string)($cat['name'] ?? $key),
		'color' => $color,
		'border' => $border,
		'logo' => nl_rt_data_uri($logoPath),
		'qr' => nl_rt_data_uri($qrPath),
		'empfaenger' => trim((string)($finanz['empfaenger'] ?? '')),
		'street' => $street,
		'zip' => trim((string)($finanz['plz'] ?? '')),
		'place' => trim((string)($finanz['ort'] ?? '')),
		'city' => $city,
		'land' => trim((string)($finanz['land'] ?? '')),
		'iban' => $iban,
		'ibanRaw' => strtoupper(preg_replace('/\s+/', '', (string)($finanz['iban'] ?? ''))),
		'bankText' => implode("\n", $bankLines),
		'slogan' => trim((string)($cat['abschlussslogan'] ?? '')),
		'info' => trim((string)($cat['zusatzinfo'] ?? '')),
	);
}

function nl_mail_only() {
	return !empty($GLOBALS['nl_for_mail']) && empty($GLOBALS['nl_for_pdf']);
}

function nl_mail_fit_px($src, $maxW, $maxH, $crop = null) {
	$maxW = max(1, (int)$maxW);
	$maxH = max(1, (int)$maxH);
	$abs = nl_file_for_web_src($src);
	if ($abs === '') {
		return array($maxW, 0);
	}
	$info = @getimagesize($abs);
	if (!is_array($info) || (int)$info[0] < 1 || (int)$info[1] < 1) {
		return array($maxW, 0);
	}
	$iw = (int)$info[0];
	$ih = (int)$info[1];
	$box = nl_crop_box(is_array($crop) ? $crop : null);
	if ($box) {
		$iw = max(1, (int)round($iw * $box['w'] / 100));
		$ih = max(1, (int)round($ih * $box['h'] / 100));
	}
	$scale = min($maxW / $iw, $maxH / $ih);
	return array(max(1, (int)round($iw * $scale)), max(1, (int)round($ih * $scale)));
}

function nl_letter_row_frames($slots) {
	$targetH = 181;
	$avail = 567;
	$gap = 11;
	$ratios = array();
	foreach ($slots as $slot) {
		$src = '';
		$crop = null;
		if (is_array($slot)) {
			$src = nl_media_src($slot['rel'] ?? '');
			$crop = isset($slot['crop']) ? $slot['crop'] : null;
		}
		list($w, $h) = nl_mail_fit_px($src, 10000, $targetH, $crop);
		if ($h < 1 || $w < 1) {
			$ratios[] = 1;
		} else {
			$ratios[] = $w / $h;
		}
	}
	$sum = 0;
	foreach ($ratios as $ratio) {
		$sum += $targetH * $ratio;
	}
	$room = $avail - ($gap * max(0, count($ratios) - 1));
	if ($room < 80) {
		$room = 80;
	}
	$height = $targetH;
	if ($sum > $room && $sum > 0) {
		$height = $targetH * $room / $sum;
	}
	$hPx = max(1, (int)round($height));
	$frames = array();
	foreach ($ratios as $ratio) {
		$frames[] = array(max(1, (int)round($hPx * $ratio)), $hPx);
	}
	return $frames;
}

function nl_absender_lines($pack) {
	$lines = array();
	foreach (array('empfaenger', 'street', 'city') as $key) {
		$line = trim((string)($pack[$key] ?? ''));
		if ($line !== '') {
			$lines[] = $line;
		}
	}
	$bank = trim((string)($pack['bankText'] ?? ''));
	if ($bank !== '') {
		$bank = preg_replace('/\s+(BIC\/SWIFT:|IBAN:)/', "\n" . '$1', $bank);
		foreach (preg_split('/\R/', $bank) as $line) {
			$line = trim((string)$line);
			if ($line !== '') {
				$lines[] = $line;
			}
		}
	}
	return $lines;
}

function nl_slip_hex_rgb($fill) {
	$fill = trim((string)$fill);
	if (preg_match('/^#([0-9a-f]{6})$/i', $fill, $m)) {
		$h = $m[1];
		return array(hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)));
	}
	if (preg_match('/^#([0-9a-f]{3})$/i', $fill, $m)) {
		$h = $m[1];
		return array(hexdec($h[0] . $h[0]), hexdec($h[1] . $h[1]), hexdec($h[2] . $h[2]));
	}
	return array(0, 0, 0);
}

function nl_slip_svg_png_bin($svg) {
	if (!class_exists('Imagick') || $svg === '') {
		return '';
	}
	try {
		$im = new Imagick();
		$im->setBackgroundColor(new ImagickPixel('white'));
		$im->setResolution(144, 144);
		$blob = (strpos($svg, '<svg') === 0) ? ('<?xml version="1.0" encoding="UTF-8"?>' . $svg) : $svg;
		$im->readImageBlob($blob);
		$im->setImageFormat('png');
		$w = (int)$im->getImageWidth();
		if ($w > 700) {
			$h = max(1, (int)$im->getImageHeight());
			$nw = 480;
			$nh = max(1, (int)round($h * $nw / $w));
			$im->resizeImage($nw, $nh, Imagick::FILTER_LANCZOS, 1);
		}
		$bin = $im->getImageBlob();
		$im->clear();
		$im->destroy();
	} catch (Exception $e) {
		return '';
	}
	return (is_string($bin) && strncmp($bin, "\x89PNG", 4) === 0) ? $bin : '';
}

function nl_slip_svg_gd_png($svg, $px) {
	if (!function_exists('imagecreatetruecolor') || $svg === '') {
		return '';
	}
	$unitW = 0;
	$unitH = 0;
	if (preg_match('/\bwidth="([0-9.]+)"/', $svg, $m)) {
		$unitW = (float)$m[1];
	}
	if (preg_match('/\bheight="([0-9.]+)"/', $svg, $m)) {
		$unitH = (float)$m[1];
	}
	if ($unitW < 1 || $unitH < 1) {
		return '';
	}
	$px = max(32, (int)$px);
	$im = imagecreatetruecolor($px, $px);
	if (!$im) {
		return '';
	}
	$white = imagecolorallocate($im, 255, 255, 255);
	imagefilledrectangle($im, 0, 0, $px - 1, $px - 1, $white);
	$sx = $px / $unitW;
	$sy = $px / $unitH;
	$colors = array();
	$drew = 0;
	if (preg_match_all('/<rect\b([^>]*?)\/?\s*>/', $svg, $rects)) {
		foreach ($rects[1] as $attr) {
			if (!preg_match('/\bx="([0-9.]+)"/', $attr, $xm) || !preg_match('/\by="([0-9.]+)"/', $attr, $ym)) {
				continue;
			}
			if (!preg_match('/\bwidth="([0-9.]+)"/', $attr, $wm) || !preg_match('/\bheight="([0-9.]+)"/', $attr, $hm)) {
				continue;
			}
			$fill = '#000000';
			if (preg_match('/\bfill="([^"]*)"/', $attr, $fm)) {
				$fill = $fm[1];
			}
			if (strcasecmp($fill, 'white') === 0 || strcasecmp($fill, '#fff') === 0 || strcasecmp($fill, '#ffffff') === 0) {
				continue;
			}
			if (!isset($colors[$fill])) {
				$rgb = nl_slip_hex_rgb($fill);
				$colors[$fill] = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
			}
			$x1 = (int)floor((float)$xm[1] * $sx);
			$y1 = (int)floor((float)$ym[1] * $sy);
			$x2 = (int)ceil(((float)$xm[1] + (float)$wm[1]) * $sx) - 1;
			$y2 = (int)ceil(((float)$ym[1] + (float)$hm[1]) * $sy) - 1;
			if ($x2 < $x1) {
				$x2 = $x1;
			}
			if ($y2 < $y1) {
				$y2 = $y1;
			}
			imagefilledrectangle($im, $x1, $y1, $x2, $y2, $colors[$fill]);
			$drew++;
		}
	}
	if ($drew < 1) {
		imagedestroy($im);
		return '';
	}
	ob_start();
	imagepng($im);
	$bin = ob_get_clean();
	imagedestroy($im);
	return (is_string($bin) && strncmp($bin, "\x89PNG", 4) === 0) ? $bin : '';
}

function nl_slip_empty_qr_file() {
	$names = array(
		'qrfrei.png',
		'qrfrei.jpg',
		'qrfrei.jpeg',
		'qrfrei.gif',
		'qr_frei.png',
		'qr-ohne-betrag.png',
		'ohne_betrag.png',
		'ohne-betrag.png',
		'swissqr_free.png',
		'swissqr_free.jpg',
	);
	$roots = array(
		dirname(__DIR__) . '/ar',
		__DIR__ . '/ar',
		dirname(__DIR__) . '/../rkrk/data/ar',
		dirname(__DIR__) . '/../rkrk/ar',
		dirname(__DIR__) . '/../data/ar',
	);
	foreach ($roots as $root) {
		if (!is_dir($root)) {
			continue;
		}
		foreach ($names as $name) {
			$path = $root . '/' . $name;
			if (is_file($path)) {
				return $path;
			}
		}
		$dh = @opendir($root);
		if (!$dh) {
			continue;
		}
		while (($entry = readdir($dh)) !== false) {
			if ($entry === '.' || $entry === '..' || $entry === 'belege' || $entry === 'Archiv' || $entry === 'transactions' || $entry === 'public' || $entry === 'src') {
				continue;
			}
			$sub = $root . '/' . $entry;
			if (!is_dir($sub)) {
				continue;
			}
			foreach ($names as $name) {
				$path = $sub . '/' . $name;
				if (is_file($path)) {
					closedir($dh);
					return $path;
				}
			}
		}
		closedir($dh);
	}
	$fallback = __DIR__ . '/media/qr/qrfrei.png';
	return is_file($fallback) ? $fallback : '';
}

function nl_slip_empty_qr_black_bin($path) {
	if ($path === '' || !is_file($path) || !function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
		return '';
	}
	$raw = (string)@file_get_contents($path);
	if ($raw === '') {
		return '';
	}
	$src = @imagecreatefromstring($raw);
	if (!$src) {
		return '';
	}
	$w = imagesx($src);
	$h = imagesy($src);
	if ($w < 1 || $h < 1) {
		imagedestroy($src);
		return '';
	}
	$out = imagecreatetruecolor($w, $h);
	if (!$out) {
		imagedestroy($src);
		return '';
	}
	$black = imagecolorallocate($out, 0, 0, 0);
	$white = imagecolorallocate($out, 255, 255, 255);
	imagefilledrectangle($out, 0, 0, $w - 1, $h - 1, $white);
	$true = imageistruecolor($src);
	for ($y = 0; $y < $h; $y++) {
		for ($x = 0; $x < $w; $x++) {
			$rgba = imagecolorat($src, $x, $y);
			if ($true) {
				$r = ($rgba >> 16) & 255;
				$g = ($rgba >> 8) & 255;
				$b = $rgba & 255;
				$a = ($rgba >> 24) & 127;
			} else {
				$c = imagecolorsforindex($src, $rgba);
				$r = (int)$c['red'];
				$g = (int)$c['green'];
				$b = (int)$c['blue'];
				$a = isset($c['alpha']) ? (int)$c['alpha'] : 0;
			}
			if ($a >= 64) {
				continue;
			}
			$chroma = $g - min($r, $b);
			$greenish = ($g >= $r && $g >= $b && $chroma >= 4);
			if ($greenish) {
				if (($r + $g + $b) < 520) {
					imagesetpixel($out, $x, $y, $black);
				}
				continue;
			}
			if ($r < 248 || $g < 248 || $b < 248) {
				imagesetpixel($out, $x, $y, imagecolorallocate($out, $r, $g, $b));
			}
		}
	}
	imagedestroy($src);
	ob_start();
	imagepng($out);
	$bin = ob_get_clean();
	imagedestroy($out);
	return (is_string($bin) && strncmp($bin, "\x89PNG", 4) === 0) ? $bin : '';
}

function nl_slip_empty_qr_src($path) {
	static $cache = array();
	if (isset($cache[$path])) {
		return $cache[$path];
	}
	$bin = nl_slip_empty_qr_black_bin($path);
	if ($bin !== '') {
		$cache[$path] = 'data:image/png;base64,' . base64_encode($bin);
		return $cache[$path];
	}
	$cache[$path] = nl_rt_data_uri($path);
	return $cache[$path];
}

function nl_slip_qr_mail_img($svg) {
	$bin = nl_slip_svg_png_bin($svg);
	if ($bin === '') {
		$bin = nl_slip_svg_gd_png($svg, 460);
	}
	if ($bin === '') {
		return '';
	}
	return '<img src="data:image/png;base64,' . base64_encode($bin) . '" alt="Swiss QR-Code" width="146" height="146" style="width:146px;height:146px;display:block;border:0;">';
}

function nl_slip_qr($pack, $amountIso, $payerName, $payerStreet, $payerZip, $payerCity, $info) {
	$mail = nl_mail_only();
	$attr = $mail
		? ' width="146" height="146" style="width:146px;height:146px;display:block;border:0;"'
		: ' style="width:40mm;height:40mm;display:block;"';
	if ($amountIso !== '' && $pack['qr'] !== '') {
		return '<img src="' . nl_h($pack['qr']) . '" alt="Swiss QR-Code"' . $attr . '>';
	}
	if ($amountIso === '') {
		$emptyQr = nl_slip_empty_qr_file();
		if ($emptyQr !== '') {
			$emptyExt = strtolower(pathinfo($emptyQr, PATHINFO_EXTENSION));
			if ($mail && $emptyExt === 'svg') {
				$emptySvg = (string)file_get_contents($emptyQr);
				$emptyMail = nl_slip_qr_mail_img($emptySvg);
				if ($emptyMail !== '') {
					return $emptyMail;
				}
			}
			$emptySrc = nl_slip_empty_qr_src($emptyQr);
			if ($emptySrc !== '') {
				return '<img src="' . nl_h($emptySrc) . '" alt="Swiss QR-Code"' . $attr . '>';
			}
		}
	}
	$qrFile = dirname(__DIR__) . '/rt/qr_svg.php';
	if (!class_exists('QR') && is_file($qrFile)) {
		require_once $qrFile;
	}
	if (!class_exists('QR') || !method_exists('QR', 'svg')) {
		return '';
	}
	$payload = "SPC\n0200\n1\n"
		. $pack['ibanRaw'] . "\nK\n"
		. $pack['empfaenger'] . "\n"
		. $pack['street'] . "\n"
		. $pack['zip'] . "\n"
		. $pack['place'] . "\n"
		. "CH\nK\n"
		. $payerName . "\n"
		. $payerStreet . "\n"
		. $payerZip . "\n"
		. $payerCity . "\n"
		. "CH\n"
		. $amountIso . "\nCHF\nNON\n\n"
		. $info . "\n";
	$color = ($pack['color'] !== '') ? $pack['color'] : '#000000';
	try {
		$svg = QR::svg($payload, 6, 2, $color);
	} catch (Exception $e) {
		return '';
	}
	if ($mail) {
		return nl_slip_qr_mail_img($svg);
	}
	if (!function_exists('qr_svg')) {
		return '';
	}
	try {
		return qr_svg($payload, $color);
	} catch (Exception $e) {
		return '';
	}
}

function nl_slip_b($cm, $lift) {
	$n = (float)$cm + (float)$lift;
	$txt = rtrim(rtrim(sprintf('%.2F', $n), '0'), '.');
	return $txt . 'cm';
}

function nl_payment_slip($pack, $amountDisplay, $amountIso, $payerLines, $payerName, $payerStreet, $payerZip, $payerCity, $info, $withAmount, $lift = 0) {
	$iban = nl_h($pack['iban']);
	$cred = nl_h($pack['empfaenger']);
	$street = $pack['street'] !== '' ? '<br>' . nl_h($pack['street']) : '';
	$city = $pack['city'] !== '' ? '<br>' . nl_h($pack['city']) : '';
	$land = $pack['land'] !== '' ? '<br>' . nl_h($pack['land']) : '';
	$konto = '<span class="slip-lbl">Konto / Zahlbar an</span><br>' . $iban;
	if ($cred !== '') {
		$konto .= '<br>' . $cred . $street . $city . $land;
	}
	$payer = '';
	$payerHtml = '';
	foreach ($payerLines as $line) {
		$line = trim((string)$line);
		if ($line !== '') {
			$payerHtml .= ($payerHtml === '' ? '' : '<br>') . nl_h($line);
		}
	}
	if ($payerHtml !== '') {
		$payer = '<span class="slip-lbl">Zahlbar durch (Name/Adresse)</span><br>' . $payerHtml;
	}
	$qr = nl_slip_qr($pack, $withAmount ? $amountIso : '', $payerName, $payerStreet, $payerZip, $payerCity, $info);
	$amt = $withAmount ? nl_h($amountDisplay) : '';
	$border = nl_h($pack['border']);
	if (nl_mail_only()) {
		$lbl = 'font-weight:700;';
		$cell = 'font-family:Calibri,Segoe UI,sans-serif;font-size:12px;line-height:1.25;color:#000000;vertical-align:top;background:#ffffff;';
		$kontoMail = str_replace('class="slip-lbl"', 'style="' . $lbl . '"', $konto);
		$payerMail = str_replace('class="slip-lbl"', 'style="' . $lbl . '"', $payer);
		$money = '<table cellspacing="0" cellpadding="0"><tr><td style="' . $lbl . 'padding-right:18px;">Währung</td><td style="' . $lbl . '">Betrag</td></tr>';
		$money .= '<tr><td style="padding:2px 18px 0 0;">CHF</td><td class="nl-slip-amt" style="padding-top:2px;">' . $amt . '</td></tr></table>';
		$html = '<table class="nl-a4 nl-slip" data-slip="' . ($withAmount ? 'betrag' : 'leer') . '" width="680" cellspacing="0" cellpadding="0" style="width:680px;border-collapse:collapse;margin-top:28px;border-top:1px solid ' . $border . ';background:#ffffff;color:#000000;">';
		$html .= '<tr>';
		$html .= '<td width="220" style="' . $cell . 'width:220px;border-right:1px solid ' . $border . ';padding:12px 10px 16px;">';
		$html .= '<div style="' . $lbl . 'margin:0 0 10px;">Empfangsschein</div>' . $kontoMail;
		if ($payerMail !== '') {
			$html .= '<div style="margin-top:10px;">' . $payerMail . '</div>';
		}
		$html .= '<div style="margin-top:12px;">' . $money . '</div></td>';
		$html .= '<td width="170" align="center" style="' . $cell . 'width:170px;padding:12px 8px 16px;text-align:center;">';
		$html .= '<div style="' . $lbl . 'margin:0 0 8px;text-align:left;">Zahlteil</div>' . $qr . '</td>';
		$html .= '<td style="' . $cell . 'padding:12px 10px 16px;">' . $kontoMail;
		$html .= '<div style="margin-top:8px;"><span style="' . $lbl . '">Zusätzliche Information</span><br>' . nl_h($info) . '</div>';
		if ($payerMail !== '') {
			$html .= '<div style="margin-top:8px;">' . $payerMail . '</div>';
		}
		$html .= '<div style="margin-top:12px;">' . $money . '</div></td>';
		$html .= '</tr></table>';
		return $html;
	}
	$b = function ($cm) use ($lift) {
		return nl_slip_b($cm, $lift);
	};
	$kind = $withAmount ? 'betrag' : 'leer';
	$html = '<div class="nl-slip nl-slip-layer" data-slip="' . $kind . '">';
	$html .= '<div class="nl-slip-plate" style="position:absolute;left:0;width:210mm;bottom:' . $b(0) . ';height:9cm;background:#ffffff;"></div>';
	$html .= '<div style="position:absolute;left:0;right:0;bottom:' . $b(9) . ';height:0;border-top:0.4mm solid ' . $border . ';"></div>';
	$html .= '<div style="position:absolute;left:6.5cm;bottom:' . $b(0) . ';width:0;height:9cm;border-left:0.4mm solid ' . $border . ';"></div>';
	$html .= '<div class="slip-abs" style="left:7.0cm;bottom:' . $b(4.1) . ';width:40mm;height:40mm;overflow:hidden;">' . $qr . '</div>';
	$html .= '<div class="slip-abs slip-lbl" style="left:1cm;bottom:' . $b(8.2) . ';">Empfangsschein</div>';
	$html .= '<div class="slip-abs slip-kontoBlock" style="left:1cm;bottom:' . $b(5.9) . ';">' . $konto . '</div>';
	if ($payer !== '') {
		$html .= '<div class="slip-abs" style="left:1cm;bottom:' . $b(3.5) . ';">' . $payer . '</div>';
	}
	$html .= '<div class="slip-abs slip-lbl" style="left:1cm;bottom:' . $b(3.0) . ';">Währung</div>';
	$html .= '<div class="slip-abs slip-lbl" style="left:2.5cm;bottom:' . $b(3.0) . ';">Betrag</div>';
	$html .= '<div class="slip-abs slip-money" style="left:1cm;bottom:' . $b(2.6) . ';">CHF</div>';
	$html .= '<div class="slip-abs slip-money nl-slip-amt" style="left:2.5cm;bottom:' . $b(2.6) . ';">' . $amt . '</div>';
	$html .= '<div class="slip-abs slip-lbl" style="left:7.0cm;bottom:' . $b(8.2) . ';">Zahlteil</div>';
	$html .= '<div class="slip-abs slip-kontoBlock" style="left:11.7cm;bottom:' . $b(6.5) . ';">' . $konto . '</div>';
	$html .= '<div class="slip-abs slip-lbl" style="left:11.7cm;bottom:' . $b(5.9) . ';">Zusätzliche Information</div>';
	$html .= '<div class="slip-abs" style="left:11.7cm;bottom:' . $b(5.5) . ';line-height:1;">' . nl_h($info) . '</div>';
	if ($payer !== '') {
		$html .= '<div class="slip-abs" style="left:11.7cm;bottom:' . $b(3.88) . ';">' . $payer . '</div>';
	}
	$html .= '<div class="slip-abs slip-lbl" style="left:7.0cm;bottom:' . $b(3.0) . ';">Währung</div>';
	$html .= '<div class="slip-abs slip-lbl" style="left:8.5cm;bottom:' . $b(3.0) . ';">Betrag</div>';
	$html .= '<div class="slip-abs slip-money" style="left:7.0cm;bottom:' . $b(2.6) . ';">CHF</div>';
	$html .= '<div class="slip-abs slip-money nl-slip-amt" style="left:8.5cm;bottom:' . $b(2.6) . ';">' . $amt . '</div>';
	$html .= '</div>';
	return $html;
}

function nl_section_pic_rels($section) {
	$pics = array();
	if (!is_array($section)) {
		return $pics;
	}
	$main = trim((string)($section['image'] ?? ''));
	if ($main !== '') {
		$pics[] = $main;
	}
	if (isset($section['images']) && is_array($section['images'])) {
		foreach ($section['images'] as $extra) {
			$extra = trim((string)$extra);
			if ($extra !== '' && !in_array($extra, $pics, true)) {
				$pics[] = $extra;
			}
		}
	}
	return $pics;
}

function nl_campaign_sections_live($campaign) {
	if (!isset($campaign['sections']) || !is_array($campaign['sections'])) {
		return null;
	}
	$live = array();
	foreach ($campaign['sections'] as $section) {
		if (!is_array($section)) {
			continue;
		}
		$title = trim((string)($section['title'] ?? ''));
		$text = trim((string)($section['text'] ?? ''));
		if ($title === '' && $text === '' && count(nl_section_pic_rels($section)) === 0) {
			continue;
		}
		$live[] = $section;
	}
	return count($live) > 0 ? $live : null;
}

function nl_letter_own_slots($gemeinde) {
	$b1 = trim((string)($gemeinde['bild1'] ?? ''));
	$b2 = trim((string)($gemeinde['bild2'] ?? ''));
	$extras = array();
	if (isset($gemeinde['images']) && is_array($gemeinde['images'])) {
		foreach ($gemeinde['images'] as $rel) {
			$rel = trim((string)$rel);
			if ($rel !== '') {
				$extras[] = $rel;
			}
		}
	}
	if ($b1 === '' && $b2 === '' && count($extras) === 0) {
		return array();
	}
	$caps = (isset($gemeinde['imageCaps']) && is_array($gemeinde['imageCaps'])) ? array_values($gemeinde['imageCaps']) : array();
	$slots = array(
		array('rel' => $b1, 'cap' => trim((string)($gemeinde['cap1'] ?? ''))),
		array('rel' => $b2, 'cap' => trim((string)($gemeinde['cap2'] ?? ''))),
	);
	foreach ($extras as $i => $rel) {
		$slots[] = array('rel' => $rel, 'cap' => trim((string)($caps[$i] ?? '')));
	}
	return $slots;
}

function nl_section_pic_slots($section) {
	$out = array();
	if (isset($section['pics']) && is_array($section['pics'])) {
		foreach ($section['pics'] as $pic) {
			if (!is_array($pic)) {
				continue;
			}
			$rel = trim((string)($pic['rel'] ?? ''));
			if ($rel === '') {
				continue;
			}
			$out[] = array(
				'rel' => $rel,
				'cap' => trim((string)($pic['cap'] ?? '')),
				'crop' => nl_crop_box(isset($pic['crop']) && is_array($pic['crop']) ? $pic['crop'] : null),
			);
		}
	}
	if (count($out) > 0) {
		return $out;
	}
	$caps = (isset($section['captions']) && is_array($section['captions'])) ? array_values($section['captions']) : array();
	foreach (nl_section_pic_rels($section) as $i => $rel) {
		$cap = isset($caps[$i]) ? trim((string)$caps[$i]) : '';
		if ($cap === '' && $i === 0) {
			$cap = trim((string)($section['caption'] ?? ''));
		}
		$out[] = array(
			'rel' => $rel,
			'cap' => $cap,
			'crop' => ($i === 0) ? nl_crop_box(isset($section['crop']) && is_array($section['crop']) ? $section['crop'] : null) : null,
		);
	}
	return $out;
}

function nl_letter_pic_slots($section, $gemeinde) {
	$slots = nl_section_pic_slots($section);
	$id = (string)($section['id'] ?? '');
	$over = ($id !== '' && isset($gemeinde['picOver'][$id]) && is_array($gemeinde['picOver'][$id])) ? $gemeinde['picOver'][$id] : array();
	foreach ($slots as $i => $slot) {
		$one = isset($over[$i]) && is_array($over[$i]) ? $over[$i] : null;
		if (!$one) {
			continue;
		}
		if (!empty($one['hide']) && empty($one['on'])) {
			$slots[$i]['rel'] = '';
			$slots[$i]['cap'] = '';
			continue;
		}
		if (empty($one['on'])) {
			continue;
		}
		$rel = trim((string)($one['rel'] ?? ''));
		if ($rel !== '') {
			$slots[$i]['rel'] = $rel;
			$slots[$i]['crop'] = nl_crop_box(isset($one['crop']) && is_array($one['crop']) ? $one['crop'] : null);
		}
		$slots[$i]['cap'] = trim((string)($one['cap'] ?? ''));
	}
	return $slots;
}

function nl_letter_figure($rel, $cap, $mode = '', $crop = null, $frame = null) {
	$src = nl_media_src($rel);
	if ($src === '') {
		return '';
	}
	$flatImg = !empty($GLOBALS['nl_for_mail']) || !empty($GLOBALS['nl_for_pdf']);
	$box = nl_crop_box(is_array($crop) ? $crop : null);
	$cls = ($mode === 'in') ? ' class="nl-pic-in"' : (($mode === 'row') ? ' class="nl-pic-row"' : '');
	$useFrame = (is_array($frame) && (int)($frame[0] ?? 0) > 0 && (int)($frame[1] ?? 0) > 0);
	if ($useFrame) {
		$w = (int)$frame[0];
		$h = (int)$frame[1];
	} else {
		$maxW = ($mode === 'row') ? 180 : 220;
		$maxH = ($mode === 'row') ? 181 : 320;
		list($w, $h) = nl_mail_fit_px($src, $maxW, $maxH, $box);
	}
	if ($mode === 'row' && !$flatImg && $useFrame) {
		$mm = number_format(((int)$w) * 25.4 / 96, 2, '.', '');
		$figStyle = 'margin:0;text-align:left;--nl-row-w:' . $mm . 'mm;';
		if ($box) {
			$imgHtml = nl_crop_img_html($rel, $src, $cap, $box);
		} else {
			$imgHtml = '<img src="' . nl_h($src) . '" alt="' . nl_h($cap) . '">';
		}
		$html = '<figure' . $cls . ' style="' . $figStyle . '">' . $imgHtml;
		if ($cap !== '') {
			$html .= '<figcaption>' . nl_h($cap) . '</figcaption>';
		}
		return $html . '</figure>';
	}
	if (nl_mail_only()) {
		$cropAttr = '';
		if ($box) {
			$cropAttr = ' data-crop="' . $box['x'] . ',' . $box['y'] . ',' . $box['w'] . ',' . $box['h'] . '"';
		}
		$imgStyle = 'display:block;border:0;width:' . (int)$w . 'px;';
		if ($h > 0) {
			$imgStyle .= 'height:' . (int)$h . 'px;';
		}
		$imgHtml = '<img src="' . nl_h($src) . '" alt="' . nl_h($cap) . '" width="' . (int)$w . '"' . ($h > 0 ? ' height="' . (int)$h . '"' : '') . $cropAttr . ' style="' . $imgStyle . '">';
		$out = '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;"><tr><td align="left" style="text-align:left;">' . $imgHtml . '</td></tr>';
		if ($cap !== '') {
			$out .= '<tr><td align="left" style="width:' . (int)$w . 'px;text-align:left;font-size:12px;line-height:1.3;color:#444;padding-top:4px;font-family:Calibri,Segoe UI,sans-serif;">' . nl_h($cap) . '</td></tr>';
		}
		return $out . '</table>';
	}
	$capW = '';
	if ($flatImg || $box || ($useFrame && $mode === 'row')) {
		$capW = ' style="display:block;width:' . (int)$w . 'px;max-width:100%;text-align:left;font-size:12px;line-height:1.3;color:#444;margin:4px 0 0;"';
	}
	$figStyle = 'margin:0;text-align:left;';
	if (($box || ($useFrame && $mode === 'row')) && $w > 0) {
		$figStyle .= 'width:' . (int)$w . 'px;max-width:100%;';
	}
	if ($box && !$flatImg) {
		$imgHtml = nl_crop_img_html($rel, $src, $cap, $box, ($useFrame ? $w : 0), ($useFrame ? $h : 0));
	} else {
		$imgW = '';
		if ($flatImg || ($useFrame && $mode === 'row')) {
			$imgStyle = 'display:block;border:0;width:' . (int)$w . 'px;';
			if ($h > 0) {
				$imgStyle .= 'height:' . (int)$h . 'px;';
			}
			$imgW = ' width="' . (int)$w . '"' . ($h > 0 ? ' height="' . (int)$h . '"' : '') . ' style="' . $imgStyle . '"';
		}
		$cropAttr = '';
		if ($box) {
			$cropAttr = ' data-crop="' . $box['x'] . ',' . $box['y'] . ',' . $box['w'] . ',' . $box['h'] . '"';
		}
		$imgHtml = '<img src="' . nl_h($src) . '" alt="' . nl_h($cap) . '"' . $imgW . $cropAttr . '>';
	}
	$html = '<figure' . $cls . ' style="' . $figStyle . '">' . $imgHtml;
	if ($cap !== '') {
		$html .= '<figcaption' . $capW . '>' . nl_h($cap) . '</figcaption>';
	}
	$html .= '</figure>';
	return $html;
}

function nl_letter_media_html($slots, $textHtml) {
	$clean = array();
	foreach ($slots as $slot) {
		if (trim((string)($slot['rel'] ?? '')) !== '') {
			$clean[] = $slot;
		}
	}
	$n = count($clean);
	if ($n === 0) {
		return $textHtml;
	}
	$flat = !empty($GLOBALS['nl_for_mail']) || !empty($GLOBALS['nl_for_pdf']);
	if ($n === 1) {
		$fig = nl_letter_figure($clean[0]['rel'], $clean[0]['cap'], 'in', isset($clean[0]['crop']) ? $clean[0]['crop'] : null);
		if ($flat) {
			return '<table width="100%" cellspacing="0" cellpadding="0"><tr><td width="58%" valign="top" style="padding-right:8px;">' . $textHtml . '</td><td width="42%" valign="top" align="left" style="text-align:left;">' . $fig . '</td></tr></table>';
		}
		return '<div class="nl-sec-flow">' . $fig . $textHtml . '</div>';
	}
	$rows = '';
	$chunks = array_chunk($clean, 3);
	foreach ($chunks as $chunk) {
		$frames = (count($chunk) >= 2) ? nl_letter_row_frames($chunk) : array();
		if ($flat) {
			$rows .= '<table width="100%" cellspacing="0" cellpadding="0" style="margin:4px 0 8px;border-collapse:collapse;"><tr>';
			$last = count($chunk) - 1;
			foreach ($chunk as $ci => $slot) {
				$frame = isset($frames[$ci]) ? $frames[$ci] : null;
				$px = (is_array($frame) && (int)($frame[0] ?? 0) > 0) ? (int)$frame[0] : 0;
				$pad = ($ci < $last) ? 'padding:0 8px 0 0;' : 'padding:0;';
				$tdW = ($px > 0) ? (' width="' . $px . '" style="width:' . $px . 'px;' . $pad . 'vertical-align:top;text-align:left;"') : (' style="' . $pad . 'vertical-align:top;text-align:left;"');
				$rows .= '<td valign="top" align="left"' . $tdW . '>' . nl_letter_figure($slot['rel'], $slot['cap'], 'row', isset($slot['crop']) ? $slot['crop'] : null, $frame) . '</td>';
			}
			$rows .= '</tr></table>';
		} else {
			$rows .= '<div class="nl-pics-row">';
			foreach ($chunk as $ci => $slot) {
				$frame = isset($frames[$ci]) ? $frames[$ci] : null;
				$rows .= nl_letter_figure($slot['rel'], $slot['cap'], 'row', isset($slot['crop']) ? $slot['crop'] : null, $frame);
			}
			$rows .= '</div>';
		}
	}
	return $textHtml . $rows;
}

function nl_letter_picture_slots($campaign, $gemeinde) {
	$slots = array();
	foreach (nl_letter_own_slots($gemeinde) as $slot) {
		if (trim((string)($slot['rel'] ?? '')) === '') {
			continue;
		}
		$slots[] = $slot;
	}
	if (count($slots) > 0) {
		return $slots;
	}
	$pairs = array(
		array('themeBild1', 'themeCap1'),
		array('themeBild2', 'themeCap2'),
	);
	foreach ($pairs as $pair) {
		$rel = trim((string)($campaign[$pair[0]] ?? ''));
		if ($rel === '') {
			continue;
		}
		$slots[] = array('rel' => $rel, 'cap' => trim((string)($campaign[$pair[1]] ?? '')));
	}
	return $slots;
}

function nl_letter_fill($html, $gemeinde) {
	$betrag = nl_h(trim((string)($gemeinde['betrag'] ?? '')));
	$name = nl_h(trim((string)($gemeinde['gemeinde'] ?? '')));
	return str_ireplace(array('[Betrag]', '[Gemeinde]'), array($betrag, $name), (string)$html);
}

function nl_letter_theme_html($campaign, $gemeinde) {
	$sections = nl_campaign_sections_live($campaign);
	if (!$sections) {
		$sections = array(array(
			'title' => trim((string)($campaign['themeTitle'] ?? '')),
			'text' => trim((string)($campaign['themeText'] ?? '')),
			'caption' => trim((string)($campaign['themeCap1'] ?? '')),
			'image' => '',
			'images' => array(),
		));
	}
	$hasPic = false;
	foreach ($sections as $section) {
		if (count(nl_section_pic_rels($section)) > 0) {
			$hasPic = true;
			break;
		}
	}
	$fallback = $hasPic ? array() : nl_letter_picture_slots($campaign, $gemeinde);
	$html = '';
	foreach ($sections as $si => $section) {
		$title = trim((string)($section['title'] ?? ''));
		$text = trim((string)($section['text'] ?? ''));
		$showTitle = !array_key_exists('showTitle', $section) || !empty($section['showTitle']);
		$head = ($showTitle && $title !== '') ? '<h2' . (nl_mail_only() ? ' style="margin:18px 0 8px;font-size:16px;line-height:1.3;font-weight:700;color:#222;"' : '') . '>' . nl_h($title) . '</h2>' : '';
		$textHtml = ($text !== '') ? '<div class="nl-block">' . nl_letter_fill(nl_text_to_html($text), $gemeinde) . '</div>' : '';
		$slots = nl_letter_pic_slots($section, $gemeinde);
		if (count($slots) === 0 && $si === 0) {
			$slots = $fallback;
		}
		$html .= $head . nl_letter_media_html($slots, $textHtml);
	}
	return $html;
}

function nl_render_letter($campaign, $gemeinde, $stat, $hooks = false) {
	$gemeinde = nl_with_letter($campaign, $gemeinde);
	$text = trim((string)($gemeinde['text'] ?? ''));
	if ($text === '') {
		$text = nl_suggest_gemeinde_text($gemeinde, $stat);
	}
	$dank = trim((string)($gemeinde['dank'] ?? ''));
	$anrede = trim((string)($gemeinde['anrede'] ?? ''));
	if ($anrede === '') {
		$anrede = 'Sehr geehrte Damen und Herren';
	}
	$place = trim((string)($campaign['ortDatum'] ?? 'Knonau'));
	$when = nl_campaign_letter_when($campaign);
	$pack = nl_letter_pack($campaign['rtKat'] ?? 'RK');
	$mode = (string)($gemeinde['slipMode'] ?? '');
	if (!in_array($mode, array('betrag', 'beide', 'keine'), true)) {
		$mode = (string)($campaign['slipMode'] ?? 'betrag');
	}
	if (!in_array($mode, array('betrag', 'beide', 'keine'), true)) {
		$mode = 'betrag';
	}
	$addr = array();
	foreach (array('anschrift1', 'anschrift2', 'strasse', 'strasse2') as $field) {
		$line = trim((string)($gemeinde[$field] ?? ''));
		if ($line !== '') {
			$addr[] = $line;
		}
	}
	$cityLine = trim(trim((string)($gemeinde['plz'] ?? '')) . ' ' . trim((string)($gemeinde['ort'] ?? '')));
	if ($cityLine !== '') {
		$addr[] = $cityLine;
	}
	$color = nl_h($pack['color']);
	$mailOnly = nl_mail_only();
	$pStyle = $mailOnly ? ' style="margin:0 0 12px;font-size:15px;line-height:1.45;color:#222;"' : '';
	$h2Style = $mailOnly ? ' style="margin:18px 0 8px;font-size:16px;line-height:1.3;font-weight:700;color:#222;"' : '';
	if ($mailOnly) {
		$html = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background:#ffffff;"><tr><td align="center">';
		$html .= '<table role="presentation" width="680" cellspacing="0" cellpadding="0" style="width:680px;border-collapse:collapse;font-family:Calibri,Segoe UI,sans-serif;font-size:15px;line-height:1.45;color:#222;"><tr><td align="left" style="text-align:left;padding:4px 0 28px;">';
		$html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>';
		$html .= '<td width="58%" valign="bottom" style="font-size:15px;line-height:1.3;padding:48px 16px 0 0;">' . implode('<br>', array_map('nl_h', $addr)) . '</td>';
		$html .= '<td width="42%" valign="top" align="right" style="width:42%;text-align:right;vertical-align:top;">';
		if ($pack['logo'] !== '') {
			$html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;"><tr><td align="right" style="text-align:right;padding:0 0 6px;">';
			$html .= '<img src="' . nl_h($pack['logo']) . '" alt="" width="128" style="display:block;width:128px;height:auto;border:0;">';
			$html .= '</td></tr></table>';
		}
		$html .= '<div style="text-align:right;font-size:11px;line-height:1.25;color:' . $color . ';">';
		foreach (nl_absender_lines($pack) as $line) {
			$html .= '<div style="text-align:right;">' . nl_h($line) . '</div>';
		}
		$html .= '</div></td></tr></table>';
		$html .= '<p style="text-align:right;margin:18px 0 0;font-size:15px;line-height:1.3;">' . nl_h($place) . ', ' . nl_h($when) . '</p>';
		$html .= '<h1 style="margin:16px 0 0;font-size:18px;line-height:1.3;font-weight:700;color:' . $color . ';">Aktivitäten der Rehkitzrettung mit Drohnen in ' . nl_h($gemeinde['gemeinde'] ?? '') . '</h1>';
		$html .= '<p style="margin:16px 0 12px;font-size:15px;line-height:1.45;">' . nl_h($anrede) . '</p>';
	} else {
		static $nlLetterSeq = 0;
		$nlLetterSeq++;
		$html = '<div class="nl-a4-doc">';
		$html .= '<article class="nl-a4 nl-letter" data-letter="' . $nlLetterSeq . '">';
		$html .= '<div class="nl-inv-head" style="position:relative;height:auto;width:100%;">';
		$html .= '<table class="nl-inv-table" role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;table-layout:fixed;"><tr>';
		$html .= '<td width="58%" valign="top" style="width:58%;"></td>';
		$html .= '<td class="nl-inv-logo" width="42%" valign="top" align="right" style="width:42%;text-align:right;vertical-align:top;position:static;float:none;">';
		if ($pack['logo'] !== '') {
			$html .= '<img src="' . nl_h($pack['logo']) . '" alt="" style="display:inline;float:none;width:34mm;height:auto;max-width:34mm;max-height:14mm;border:0;margin:0 0 1.5mm 0;">';
		}
		$html .= '<div class="nl-inv-absender" style="color:' . $color . ';text-align:right;width:100%;">';
		foreach (array($pack['empfaenger'], $pack['street'], $pack['city'], $pack['bankText']) as $block) {
			if (trim($block) === '') {
				continue;
			}
			$html .= '<div class="nl-inv-absender-line" style="text-align:right;white-space:pre-line;">' . nl_h($block) . '</div>';
		}
		$html .= '</div></td></tr></table></div>';
		$html .= '<p class="nl-letter-address"' . nl_hid($hooks, 'nl-pv-address') . '>' . implode('<br>', array_map('nl_h', $addr)) . '</p>';
		$html .= '<p class="nl-letter-date"' . nl_hid($hooks, 'nl-pv-date') . '>' . nl_h($place) . ', ' . nl_h($when) . '</p>';
		$html .= '<h1 class="nl-subject" style="color:' . $color . ';"' . nl_hid($hooks, 'nl-pv-title') . '>Aktivitäten der Rehkitzrettung mit Drohnen in ' . nl_h($gemeinde['gemeinde'] ?? '') . '</h1>';
		$html .= '<p class="nl-salutation"' . nl_hid($hooks, 'nl-pv-anrede') . '>' . nl_h($anrede) . '</p>';
	}
	$introHtml = nl_letter_fill(nl_sanitize_html((string)($campaign['sharedHtml'] ?? '')), $gemeinde);
	$introSec = array(
		'id' => 'intro',
		'title' => '',
		'showTitle' => 0,
		'text' => '',
		'pics' => (isset($campaign['introPics']) && is_array($campaign['introPics'])) ? $campaign['introPics'] : array(),
	);
	$introMedia = nl_letter_media_html(nl_letter_pic_slots($introSec, $gemeinde), $introHtml);
	$html .= '<div class="nl-block"' . $pStyle . nl_hid($hooks, 'nl-pv-shared') . '>' . $introMedia . '</div>';
	$html .= '<h2' . $h2Style . nl_hid($hooks, 'nl-pv-dev') . '>Einsatzentwicklung in ' . nl_h($gemeinde['gemeinde'] ?? '') . '</h2>';
	$show = isset($stat['show']) && is_array($stat['show']) ? $stat['show'] : array('flaeche', 'felder', 'einsaetze', 'fund');
	$metrics = nl_stat_metrics();
	$units = array('flaeche' => 'ha', 'einsaetze' => '', 'felder' => '', 'fund' => '');
	$order = array('flaeche', 'felder', 'einsaetze', 'fund');
	$flat = !empty($GLOBALS['nl_for_mail']) || !empty($GLOBALS['nl_for_pdf']);
	$kpiColor = array('flaeche' => '#1565c0', 'felder' => '#c0392b', 'einsaetze' => '#e67e22', 'fund' => '#2e7d32');
	if ($flat) {
		$html .= '<table class="nl-kpis" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:6px;margin:8px 0 4px;"><tr>';
	} else {
		$html .= '<div class="nl-kpis">';
	}
	foreach ($order as $key) {
		if (!in_array($key, $show, true)) {
			continue;
		}
		$num = number_format((int)($stat[$key] ?? 0), 0, '.', "'");
		$unit = ($units[$key] !== '') ? '<span class="nl-kpi-unit">' . nl_h($units[$key]) . '</span>' : '';
		if ($flat) {
			$kpiFont = 'Calibri,Segoe UI,sans-serif';
			$html .= '<td width="25%" align="center" style="background:#f7fbf5;border-top:7px solid ' . $kpiColor[$key] . ';padding:8px 4px;font-family:' . $kpiFont . ';">';
			$html .= '<div style="font-size:22px;font-weight:700;line-height:1.05;color:' . $kpiColor[$key] . ';">' . nl_h($num) . $unit . '</div>';
			$html .= '<div style="font-size:11px;color:#555;margin-top:3px;">' . nl_h($metrics[$key] ?? $key) . '</div></td>';
		} else {
			$html .= '<div class="nl-kpi nl-kpi-' . nl_h($key) . '"><span class="nl-kpi-val">' . nl_h($num) . $unit . '</span><span class="nl-kpi-label">' . nl_h($metrics[$key] ?? $key) . '</span></div>';
		}
	}
	$html .= $flat ? '</tr></table>' : '</div>';
	$chart = nl_stat_chart_svg($stat);
	if ($chart !== '') {
		if ($mailOnly) {
			$html .= $chart;
		} else {
			$html .= '<div class="nl-chart-wrap">' . $chart . '</div>';
		}
	}
	$html .= '<p' . $pStyle . ($hooks ? ' class="nl-specific"' : '') . nl_hid($hooks, 'nl-pv-text') . '>' . nl_letter_fill(nl_text_to_html($text), $gemeinde) . '</p>';
	$themeHtml = nl_letter_theme_html($campaign, $gemeinde);
	if ($hooks) {
		$html .= '<div id="nl-pv-theme">' . $themeHtml . '</div>';
	} else {
		$html .= $themeHtml;
	}
	if ($dank === '') {
		$dank = trim((string)($campaign['dankShared'] ?? ''));
	}
	if ($dank !== '' || $hooks) {
		$dankHtml = nl_letter_fill(nl_text_to_html($dank), $gemeinde);
		if (!nl_looks_like_html($dank)) {
			$dankHtml = '<strong>' . $dankHtml . '</strong>';
		}
		$html .= '<p' . $pStyle . nl_hid($hooks, 'nl-pv-dank') . '>' . $dankHtml . '</p>';
	}
	$betrag = trim((string)($gemeinde['betrag'] ?? ''));
	$absender = trim((string)($gemeinde['absender'] ?? ''));
	$signSrc = nl_signature_src($absender);
	if ($absender !== '' || $signSrc !== '' || $hooks) {
		$signImg = '';
		if ($signSrc !== '') {
			$signImg = '<img class="nl-sign-img" src="' . nl_h($signSrc) . '" alt="" width="96" style="display:block;width:96px;height:auto;margin:4px 0;border:0;">';
		}
		$nameHtml = $hooks ? ('<span id="nl-pv-sign-name">' . nl_h($absender) . '</span>') : nl_h($absender);
		$html .= '<div' . $pStyle . nl_hid($hooks, 'nl-pv-sign') . '>Freundliche Grüsse<br>' . $signImg . $nameHtml . '<br>Rehkitzrettung Knonaueramt</div>';
	}
	$html .= nl_default_footnote_html('Sie');
	$bemerkung = trim((string)($gemeinde['bemerkung'] ?? ''));
	if (($bemerkung !== '' || $hooks) && !$hooks) {
		$html .= '';
	}
	if (!$mailOnly) {
		$html .= '</article>';
	}
	$info = $pack['info'] !== '' ? $pack['info'] : ('Gemeindebrief ' . trim((string)($gemeinde['gemeinde'] ?? '')));
	$amountDisplay = nl_chf_amount($betrag);
	$amountIso = $amountDisplay === '' ? '' : str_replace('’', '', $amountDisplay);
	$payerName = trim((string)($gemeinde['anschrift1'] ?? ''));
	$payerStreet = trim((string)($gemeinde['strasse'] ?? ''));
	$payerZip = trim((string)($gemeinde['plz'] ?? ''));
	$payerCity = trim((string)($gemeinde['ort'] ?? ''));
	$showBetrag = $hooks || $mode === 'betrag' || $mode === 'beide';
	$showLeer = $hooks || $mode === 'beide';
	$duo = ($mode === 'beide');
	$liftBetrag = (!empty($GLOBALS['nl_for_pdf']) && $duo) ? 9 : 0;
	$slipBetrag = '';
	$slipLeer = '';
	if ($showBetrag) {
		$slipBetrag = nl_payment_slip($pack, $amountDisplay, $amountIso, $addr, $payerName, $payerStreet, $payerZip, $payerCity, $info, true, $liftBetrag);
		if ($hooks && $mode !== 'betrag' && $mode !== 'beide') {
			$slipBetrag = str_replace('class="nl-slip nl-slip-layer"', 'class="nl-slip nl-slip-layer" style="display:none"', $slipBetrag);
			$slipBetrag = str_replace('class="nl-a4 nl-slip"', 'class="nl-a4 nl-slip" style="display:none"', $slipBetrag);
		}
	}
	if ($showLeer) {
		$slipLeer = nl_payment_slip($pack, '', '', $addr, $payerName, $payerStreet, $payerZip, $payerCity, $info, false, 0);
		if ($hooks && $mode !== 'beide') {
			$slipLeer = str_replace('class="nl-slip nl-slip-layer"', 'class="nl-slip nl-slip-layer" style="display:none"', $slipLeer);
			$slipLeer = str_replace('class="nl-a4 nl-slip"', 'class="nl-a4 nl-slip" style="display:none"', $slipLeer);
		}
	}
	if ($mailOnly) {
		$html .= $slipBetrag . $slipLeer;
	} elseif ($slipBetrag !== '' || $slipLeer !== '') {
		$duoClass = ' nl-slip-sheet';
		if ($duo || $hooks) {
			$duoClass .= ' nl-slip-duo';
		}
		if ($duo) {
			$duoClass .= ' is-duo';
		}
		$sheetHide = ($hooks && $mode === 'keine') ? ' style="display:none"' : '';
		$html .= '<article class="nl-a4' . $duoClass . '"' . $sheetHide . '>' . $slipBetrag . $slipLeer . '</article>';
	}
	if ($mailOnly) {
		$html .= '</td></tr></table></td></tr></table>';
	} else {
		$html .= '</div>';
	}
	return $html;
}

function nl_letter_pdf_css() {
	return 'body{margin:0;font-family:Calibri,sans-serif;font-size:11px;color:#000;}'
		. '.nl-a4{page-break-after:always;}'
		. '.nl-letter{padding:1.5cm;}'
		. '.nl-inv-head{height:auto;width:100%;position:relative;}'
		. '.nl-inv-table{width:100%;border-collapse:collapse;table-layout:fixed;}'
		. '.nl-inv-logo{position:static;float:none;width:42%;text-align:right;}'
		. '.nl-inv-logo img{display:inline;float:none;width:34mm;height:auto;max-width:34mm;margin:0 0 1.5mm 0;}'
		. '.nl-inv-absender{display:block;clear:both;width:100%;margin-top:0;text-align:right;font-size:8px;line-height:1.15;white-space:pre-line;}'
		. '.nl-letter-address{margin:0;font-size:11px;line-height:1.2;}'
		. '.nl-letter-date{text-align:right;margin:5mm 0 0;}'
		. '.nl-subject{margin:5mm 0 0;font-size:14px;}'
		. '.nl-salutation{margin-top:5mm;}'
		. '.nl-slip-sheet{position:relative;width:210mm;height:297mm;page-break-before:always;overflow:hidden;padding:0;background:#fff;color:#000;}'
		. '.nl-slip-layer{position:absolute;left:0;top:0;right:0;bottom:0;color:#000;background:transparent;}'
		. '.nl-slip-layer[data-slip="betrag"],.nl-slip-layer[data-slip="leer"]{color:#000;background:transparent;}'
		. '.nl-slip-plate{background:#fff;}'
		. '.nl-slip-duo .nl-slip-layer[data-slip="betrag"]{transform:none;top:0;right:0;bottom:0;left:0;}'
		. '.nl-slip{position:relative;font-size:9px;line-height:1;color:#000;}'
		. '.slip-abs{position:absolute;margin:0;padding:0;white-space:pre-line;}'
		. '.slip-lbl{font-weight:700;}'
		. '.slip-money{font-size:12px;}'
		. 'figure{display:inline-block;vertical-align:top;margin:0 3mm 3mm 0;text-align:left;}'
		. 'figcaption{display:block;text-align:left;font-size:9pt;line-height:1.25;color:#444;margin:1mm 0 0;}'
		. '.nl-inv-logo img{display:inline;width:34mm;height:auto;margin:0 0 1.5mm 0;}'
		. 'img{border:0;}';
}

function nl_stream_gemeinde_pdf($campId, $gemeindeId, $katOverride, $slipOverride) {
	$kampagnen = nl_items('newsletter_kampagnen.json');
	$camp = $campId !== '' ? nl_find_item($kampagnen, $campId) : (isset($kampagnen[0]) ? $kampagnen[0] : null);
	if (!$camp) {
		header('Content-Type: text/plain; charset=utf-8');
		echo 'Keine Serie.';
		exit;
	}
	if ($katOverride !== '') {
		$camp['rtKat'] = $katOverride;
	}
	if ($gemeindeId !== '' && in_array($slipOverride, array('betrag', 'beide', 'keine'), true)) {
		if (!isset($camp['letters']) || !is_array($camp['letters'])) {
			$camp['letters'] = array();
		}
		$row = isset($camp['letters'][$gemeindeId]) && is_array($camp['letters'][$gemeindeId])
			? $camp['letters'][$gemeindeId]
			: nl_letter_row($camp, $gemeindeId);
		$row['slipMode'] = $slipOverride;
		$camp['letters'][$gemeindeId] = $row;
	}
	$gemeinden = nl_items('newsletter_gemeinden.json');
	$stats = nl_flight_bundle($camp);
	$body = '';
	$prevMail = !empty($GLOBALS['nl_for_mail']);
	$prevPdf = !empty($GLOBALS['nl_for_pdf']);
	$GLOBALS['nl_for_mail'] = true;
	$GLOBALS['nl_for_pdf'] = true;
	foreach ($gemeinden as $g) {
		if (empty($g['aktiv'])) {
			continue;
		}
		if ($gemeindeId !== '' && (string)$g['id'] !== $gemeindeId) {
			continue;
		}
		$body .= nl_render_letter($camp, $g, nl_stats_for_gemeinde($stats, $g), false);
	}
	$GLOBALS['nl_for_mail'] = $prevMail;
	$GLOBALS['nl_for_pdf'] = $prevPdf;
	if ($body === '') {
		header('Content-Type: text/plain; charset=utf-8');
		echo 'Kein Brief zum Export.';
		exit;
	}
	$autoload = dirname(__DIR__) . '/rt/dompdf/vendor/autoload.php';
	if (!is_file($autoload)) {
		header('Content-Type: text/plain; charset=utf-8');
		echo 'PDF-Bibliothek fehlt.';
		exit;
	}
	require_once $autoload;
	$body = nl_pdf_embed_images($body);
	$html = '<!doctype html><html><head><meta charset="utf-8"><style>' . nl_letter_pdf_css() . '</style></head><body>' . $body . '</body></html>';
	$options = new Dompdf\Options();
	$options->set('isHtml5ParserEnabled', true);
	$options->set('isRemoteEnabled', true);
	$pdf = new Dompdf\Dompdf($options);
	$pdf->loadHtml($html);
	$pdf->setPaper('A4', 'portrait');
	nl_pdf_watch_letters($pdf);
	$pdf->render();
	nl_pdf_stamp_pages($pdf);
	$name = 'Gemeindebrief_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$camp['id']);
	if ($gemeindeId !== '') {
		$name .= '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $gemeindeId);
	}
	$pdf->stream($name . '.pdf', array('Attachment' => true));
	exit;
}

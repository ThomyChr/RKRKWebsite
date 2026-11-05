<?php
/**
 * QR Code, error correction M, byte mode, versions 1–10.
 * Dark modules are 1. Used for dotted codes on the Prüfungstag page and PDF.
 */
if (!function_exists("rkrk_pt_qr_encode")) {
	/** @return array{num_rows:int,num_cols:int,bcode:list<list<int>>}|array{} */
	function rkrk_pt_qr_encode(string $text): array {
		$bytes = array_values(unpack("C*", $text) ?: array());
		$len = count($bytes);
		if ($len < 1 || $len > 200) {
			return array();
		}
		$spec = rkrk_pt_qr_spec($len);
		if ($spec === array()) {
			return array();
		}
		$data = rkrk_pt_qr_data_codewords($bytes, $spec);
		$blocks = rkrk_pt_qr_rs_blocks($data, $spec);
		$stream = rkrk_pt_qr_interleave($blocks);
		return rkrk_pt_qr_matrix_from_stream($stream, $spec);
	}
}

if (!function_exists("rkrk_pt_qr_spec")) {
	/** @return array{v:int,size:int,data:int,ecc:int,groups:list<array{0:int,1:int}>,rem:int}|array{} */
	function rkrk_pt_qr_spec(int $len): array {
		// version => [data codewords, ecc per block, group1 blocks, group1 data, group2 blocks, group2 data, remainder bits]
		$table = array(
			1 => array(16, 10, 1, 16, 0, 0, 0),
			2 => array(28, 16, 1, 28, 0, 0, 7),
			3 => array(44, 26, 1, 44, 0, 0, 7),
			4 => array(64, 18, 2, 32, 0, 0, 7),
			5 => array(86, 24, 2, 43, 0, 0, 7),
			6 => array(108, 16, 4, 27, 0, 0, 7),
			7 => array(124, 18, 4, 31, 0, 0, 0),
			8 => array(154, 22, 2, 38, 2, 39, 0),
			9 => array(182, 22, 3, 36, 2, 37, 0),
			10 => array(216, 26, 4, 43, 1, 44, 0),
		);
		foreach ($table as $v => $row) {
			$countBits = $v < 10 ? 8 : 16;
			$need = (int) ceil((4 + $countBits + (8 * $len)) / 8);
			if ($need <= $row[0]) {
				return array(
					"v" => (int) $v,
					"size" => 17 + (4 * (int) $v),
					"data" => $row[0],
					"ecc" => $row[1],
					"groups" => array(array($row[2], $row[3]), array($row[4], $row[5])),
					"rem" => $row[6],
					"countBits" => $countBits,
				);
			}
		}
		return array();
	}
}

if (!function_exists("rkrk_pt_qr_data_codewords")) {
	/** @param list<int> $bytes @param array<string,mixed> $spec @return list<int> */
	function rkrk_pt_qr_data_codewords(array $bytes, array $spec): array {
		$bits = array();
		$push = function (int $value, int $n) use (&$bits): void {
			for ($i = $n - 1; $i >= 0; $i--) {
				$bits[] = ($value >> $i) & 1;
			}
		};
		$push(4, 4);
		$push(count($bytes), (int) $spec["countBits"]);
		foreach ($bytes as $b) {
			$push($b, 8);
		}
		$capacity = (int) $spec["data"] * 8;
		$term = min(4, $capacity - count($bits));
		for ($i = 0; $i < $term; $i++) {
			$bits[] = 0;
		}
		while (count($bits) % 8 !== 0) {
			$bits[] = 0;
		}
		$pad = array(0xEC, 0x11);
		$pi = 0;
		while (count($bits) < $capacity) {
			$push($pad[$pi], 8);
			$pi = 1 - $pi;
		}
		$out = array();
		for ($i = 0; $i < $capacity; $i += 8) {
			$v = 0;
			for ($b = 0; $b < 8; $b++) {
				$v = ($v << 1) | $bits[$i + $b];
			}
			$out[] = $v;
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_qr_gf")) {
	/** @return array{exp:list<int>,log:list<int>} */
	function rkrk_pt_qr_gf(): array {
		static $tables = null;
		if (is_array($tables)) {
			return $tables;
		}
		$exp = array();
		$log = array_fill(0, 256, 0);
		$x = 1;
		for ($i = 0; $i < 255; $i++) {
			$exp[$i] = $x;
			$log[$x] = $i;
			$x <<= 1;
			if ($x & 0x100) {
				$x ^= 0x11d;
			}
		}
		for ($i = 255; $i < 512; $i++) {
			$exp[$i] = $exp[$i - 255];
		}
		$tables = array("exp" => $exp, "log" => $log);
		return $tables;
	}
}

if (!function_exists("rkrk_pt_qr_rs")) {
	/** @param list<int> $data @return list<int> */
	function rkrk_pt_qr_rs(array $data, int $eccLen): array {
		$gf = rkrk_pt_qr_gf();
		$exp = $gf["exp"];
		$log = $gf["log"];
		$gen = array(1);
		for ($i = 0; $i < $eccLen; $i++) {
			$next = array_fill(0, count($gen) + 1, 0);
			for ($j = 0; $j < count($gen); $j++) {
				$next[$j] ^= $gen[$j];
				if ($gen[$j] !== 0) {
					$next[$j + 1] ^= $exp[($log[$gen[$j]] + $i) % 255];
				}
			}
			$gen = $next;
		}
		$res = array_fill(0, $eccLen, 0);
		foreach ($data as $byte) {
			$factor = $byte ^ $res[0];
			array_shift($res);
			$res[] = 0;
			if ($factor === 0) {
				continue;
			}
			$fl = $log[$factor];
			for ($i = 0; $i < $eccLen; $i++) {
				if ($gen[$i + 1] !== 0) {
					$res[$i] ^= $exp[($fl + $log[$gen[$i + 1]]) % 255];
				}
			}
		}
		return $res;
	}
}

if (!function_exists("rkrk_pt_qr_rs_blocks")) {
	/**
	 * @param list<int> $data
	 * @param array<string,mixed> $spec
	 * @return list<array{data:list<int>,ecc:list<int>}>
	 */
	function rkrk_pt_qr_rs_blocks(array $data, array $spec): array {
		$blocks = array();
		$offset = 0;
		foreach ($spec["groups"] as $group) {
			$n = (int) $group[0];
			$dlen = (int) $group[1];
			for ($i = 0; $i < $n; $i++) {
				$chunk = array_slice($data, $offset, $dlen);
				$offset += $dlen;
				$blocks[] = array("data" => $chunk, "ecc" => rkrk_pt_qr_rs($chunk, (int) $spec["ecc"]));
			}
		}
		return $blocks;
	}
}

if (!function_exists("rkrk_pt_qr_interleave")) {
	/** @param list<array{data:list<int>,ecc:list<int>}> $blocks @return list<int> */
	function rkrk_pt_qr_interleave(array $blocks): array {
		$out = array();
		$max = 0;
		foreach ($blocks as $b) {
			$max = max($max, count($b["data"]));
		}
		for ($i = 0; $i < $max; $i++) {
			foreach ($blocks as $b) {
				if ($i < count($b["data"])) {
					$out[] = $b["data"][$i];
				}
			}
		}
		$eccLen = count($blocks[0]["ecc"]);
		for ($i = 0; $i < $eccLen; $i++) {
			foreach ($blocks as $b) {
				$out[] = $b["ecc"][$i];
			}
		}
		return $out;
	}
}

if (!function_exists("rkrk_pt_qr_matrix_from_stream")) {
	/** @param list<int> $stream @param array<string,mixed> $spec @return array{num_rows:int,num_cols:int,bcode:list<list<int>>} */
	function rkrk_pt_qr_matrix_from_stream(array $stream, array $spec): array {
		$n = (int) $spec["size"];
		$mod = array();
		$fun = array();
		for ($r = 0; $r < $n; $r++) {
			$mod[$r] = array_fill(0, $n, 0);
			$fun[$r] = array_fill(0, $n, false);
		}
		$set = function (int $r, int $c, int $v) use (&$mod, &$fun): void {
			$mod[$r][$c] = $v;
			$fun[$r][$c] = true;
		};
		$paintFinder = function (int $r0, int $c0) use ($n, $set): void {
			for ($r = -1; $r <= 7; $r++) {
				for ($c = -1; $c <= 7; $c++) {
					$rr = $r0 + $r;
					$cc = $c0 + $c;
					if ($rr < 0 || $cc < 0 || $rr >= $n || $cc >= $n) {
						continue;
					}
					$on = $r >= 0 && $c >= 0 && $r <= 6 && $c <= 6
						&& ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4));
					$set($rr, $cc, $on ? 1 : 0);
				}
			}
		};
		$paintFinder(0, 0);
		$paintFinder(0, $n - 7);
		$paintFinder($n - 7, 0);
		for ($i = 0; $i < $n; $i++) {
			if (!$fun[6][$i]) {
				$set(6, $i, $i % 2 === 0 ? 1 : 0);
			}
			if (!$fun[$i][6]) {
				$set($i, 6, $i % 2 === 0 ? 1 : 0);
			}
		}
		$set($n - 8, 8, 1);
		$align = rkrk_pt_qr_align((int) $spec["v"]);
		foreach ($align as $ar) {
			foreach ($align as $ac) {
				if ($fun[$ar][$ac]) {
					continue;
				}
				for ($r = -2; $r <= 2; $r++) {
					for ($c = -2; $c <= 2; $c++) {
						$on = max(abs($r), abs($c)) !== 1;
						$set($ar + $r, $ac + $c, $on ? 1 : 0);
					}
				}
			}
		}
		for ($i = 0; $i < 9; $i++) {
			if (!$fun[8][$i]) { $fun[8][$i] = true; }
			if (!$fun[$i][8]) { $fun[$i][8] = true; }
		}
		for ($i = 0; $i < 8; $i++) {
			$fun[8][$n - 1 - $i] = true;
			$fun[$n - 1 - $i][8] = true;
		}
		if ((int) $spec["v"] >= 7) {
			for ($i = 0; $i < 6; $i++) {
				for ($j = 0; $j < 3; $j++) {
					$fun[$i][$n - 11 + $j] = true;
					$fun[$n - 11 + $j][$i] = true;
				}
			}
		}
		$bits = array();
		foreach ($stream as $byte) {
			for ($b = 7; $b >= 0; $b--) {
				$bits[] = ($byte >> $b) & 1;
			}
		}
		for ($i = 0; $i < (int) $spec["rem"]; $i++) {
			$bits[] = 0;
		}
		$bi = 0;
		$up = true;
		for ($col = $n - 1; $col > 0; $col -= 2) {
			if ($col === 6) {
				$col = 5;
			}
			for ($step = 0; $step < $n; $step++) {
				$row = $up ? ($n - 1 - $step) : $step;
				for ($k = 0; $k < 2; $k++) {
					$c = $col - $k;
					if ($fun[$row][$c]) {
						continue;
					}
					$mod[$row][$c] = ($bi < count($bits)) ? $bits[$bi] : 0;
					$bi++;
				}
			}
			$up = !$up;
		}
		$best = null;
		$bestScore = PHP_INT_MAX;
		for ($mask = 0; $mask < 8; $mask++) {
			$trial = $mod;
			for ($r = 0; $r < $n; $r++) {
				for ($c = 0; $c < $n; $c++) {
					if ($fun[$r][$c]) {
						continue;
					}
					if (rkrk_pt_qr_mask($mask, $r, $c)) {
						$trial[$r][$c] ^= 1;
					}
				}
			}
			rkrk_pt_qr_format($trial, $n, $mask);
			if ((int) $spec["v"] >= 7) {
				rkrk_pt_qr_version($trial, $n, (int) $spec["v"]);
			}
			$score = rkrk_pt_qr_penalty($trial, $n);
			if ($score < $bestScore) {
				$bestScore = $score;
				$best = $trial;
			}
		}
		return array("num_rows" => $n, "num_cols" => $n, "bcode" => $best ?? $mod);
	}
}

if (!function_exists("rkrk_pt_qr_align")) {
	/** @return list<int> */
	function rkrk_pt_qr_align(int $v): array {
		$pos = array(
			1 => array(),
			2 => array(6, 18),
			3 => array(6, 22),
			4 => array(6, 26),
			5 => array(6, 30),
			6 => array(6, 34),
			7 => array(6, 22, 38),
			8 => array(6, 24, 42),
			9 => array(6, 26, 46),
			10 => array(6, 28, 50),
		);
		return $pos[$v] ?? array();
	}
}

if (!function_exists("rkrk_pt_qr_mask")) {
	function rkrk_pt_qr_mask(int $mask, int $r, int $c): bool {
		switch ($mask) {
			case 0: return (($r + $c) % 2) === 0;
			case 1: return ($r % 2) === 0;
			case 2: return ($c % 3) === 0;
			case 3: return (($r + $c) % 3) === 0;
			case 4: return ((intdiv($r, 2) + intdiv($c, 3)) % 2) === 0;
			case 5: return (($r * $c) % 2 + ($r * $c) % 3) === 0;
			case 6: return ((($r * $c) % 2 + ($r * $c) % 3) % 2) === 0;
			default: return ((($r + $c) % 2 + ($r * $c) % 3) % 2) === 0;
		}
	}
}

if (!function_exists("rkrk_pt_qr_bch")) {
	function rkrk_pt_qr_bch(int $data, int $poly, int $bits): int {
		$d = $data << $bits;
		for ($i = 4 + ($bits === 12 ? 1 : 0); $i >= 0; $i--) {
			if ($d & (1 << ($i + $bits))) {
				$d ^= $poly << $i;
			}
		}
		$mask = (1 << $bits) - 1;
		return $d & $mask;
	}
}

if (!function_exists("rkrk_pt_qr_format")) {
	/** @param list<list<int>> $mod */
	function rkrk_pt_qr_format(array &$mod, int $n, int $mask): void {
		$data = (0 << 3) | $mask;
		$bits = (($data << 10) | rkrk_pt_qr_bch($data, 0x537, 10)) ^ 0x5412;
		$seq = array();
		for ($i = 0; $i < 15; $i++) {
			$seq[] = ($bits >> $i) & 1;
		}
		$posA = array(array(8, 0), array(8, 1), array(8, 2), array(8, 3), array(8, 4), array(8, 5), array(8, 7), array(8, 8), array(7, 8), array(5, 8), array(4, 8), array(3, 8), array(2, 8), array(1, 8), array(0, 8));
		$posB = array();
		for ($i = 0; $i < 8; $i++) {
			$posB[] = array(8, $n - 1 - $i);
		}
		for ($i = 0; $i < 7; $i++) {
			$posB[] = array($n - 7 + $i, 8);
		}
		for ($i = 0; $i < 15; $i++) {
			$mod[$posA[$i][0]][$posA[$i][1]] = $seq[$i];
			$mod[$posB[$i][0]][$posB[$i][1]] = $seq[$i];
		}
	}
}

if (!function_exists("rkrk_pt_qr_version")) {
	/** @param list<list<int>> $mod */
	function rkrk_pt_qr_version(array &$mod, int $n, int $version): void {
		$bits = ($version << 12) | rkrk_pt_qr_bch($version, 0x1f25, 12);
		for ($i = 0; $i < 18; $i++) {
			$bit = ($bits >> $i) & 1;
			$r = intdiv($i, 3);
			$c = $i % 3;
			$mod[$r][$n - 11 + $c] = $bit;
			$mod[$n - 11 + $c][$r] = $bit;
		}
	}
}

if (!function_exists("rkrk_pt_qr_penalty")) {
	/** @param list<list<int>> $mod */
	function rkrk_pt_qr_penalty(array $mod, int $n): int {
		$score = 0;
		for ($r = 0; $r < $n; $r++) {
			$run = 1;
			for ($c = 1; $c < $n; $c++) {
				if ($mod[$r][$c] === $mod[$r][$c - 1]) {
					$run++;
					if ($run === 5) { $score += 3; }
					elseif ($run > 5) { $score++; }
				} else {
					$run = 1;
				}
			}
		}
		for ($c = 0; $c < $n; $c++) {
			$run = 1;
			for ($r = 1; $r < $n; $r++) {
				if ($mod[$r][$c] === $mod[$r - 1][$c]) {
					$run++;
					if ($run === 5) { $score += 3; }
					elseif ($run > 5) { $score++; }
				} else {
					$run = 1;
				}
			}
		}
		$dark = 0;
		for ($r = 0; $r < $n; $r++) {
			for ($c = 0; $c < $n; $c++) {
				$dark += $mod[$r][$c];
				if ($r < $n - 1 && $c < $n - 1
					&& $mod[$r][$c] === $mod[$r][$c + 1]
					&& $mod[$r][$c] === $mod[$r + 1][$c]
					&& $mod[$r][$c] === $mod[$r + 1][$c + 1]) {
					$score += 3;
				}
			}
		}
		$ratio = abs(($dark * 100 / ($n * $n)) - 50) / 5;
		$score += (int) $ratio * 10;
		return $score;
	}
}

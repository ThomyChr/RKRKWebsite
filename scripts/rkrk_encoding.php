<?php
/**
 * UTF-8 encoding edge for RKRK catalogs and mission JSON.
 * Bytes in → UTF-8 text/JSON out. No $searchSDZ on write.
 */

if (!function_exists('rkrk_strip_utf8_bom')) {
	function rkrk_strip_utf8_bom($raw) {
		$raw = (string) $raw;
		if ($raw !== '' && strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
			return substr($raw, 3);
		}
		return $raw;
	}
}

if (!function_exists('rkrk_bytes_to_utf8')) {
	function rkrk_bytes_to_utf8($raw) {
		if ($raw === null) {
			return '';
		}
		if (!is_string($raw)) {
			if (is_bool($raw)) {
				return $raw ? '1' : '0';
			}
			if (is_int($raw) || is_float($raw)) {
				return (string) $raw;
			}
			return '';
		}
		$raw = rkrk_strip_utf8_bom($raw);
		if ($raw === '') {
			return '';
		}
		if (function_exists('mb_check_encoding') && mb_check_encoding($raw, 'UTF-8')) {
			return $raw;
		}
		if (function_exists('mb_convert_encoding')) {
			$try = @mb_convert_encoding($raw, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
			if (is_string($try) && $try !== '') {
				return $try;
			}
		}
		if (function_exists('iconv')) {
			$try = @iconv('Windows-1252', 'UTF-8//IGNORE', $raw);
			if (is_string($try) && $try !== '') {
				return $try;
			}
			$try = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $raw);
			if (is_string($try) && $try !== '') {
				return $try;
			}
		}
		return $raw;
	}
}

if (!function_exists('rkrk_mojibake_map')) {
	function rkrk_mojibake_map() {
		return array(
			'Ã¤' => 'ä', 'Ã„' => 'Ä', 'Ã¶' => 'ö', 'Ã–' => 'Ö',
			'Ã¼' => 'ü', 'Ãœ' => 'Ü', 'Ã' => 'ß',
			'Ã©' => 'é', 'Ã¨' => 'è', 'Ãª' => 'ê', 'Ã«' => 'ë',
			'Ã¡' => 'á', 'Ã ' => 'à', 'Ã¢' => 'â', 'Ã£' => 'ã',
			'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú', 'Ã±' => 'ñ',
			'Ã´' => 'ô', 'Ã¹' => 'ù', 'Ã»' => 'û', 'Ã§' => 'ç',
			'Â©' => 'é', 'Âä' => 'ä', 'Âö' => 'ö', 'Âü' => 'ü',
			'ÂÄ' => 'Ä', 'ÂÖ' => 'Ö', 'ÂÜ' => 'Ü',
			'Ã?Â¼' => 'ü', 'Ã?Â¤' => 'ä', 'Â¼' => 'ü',
			'ï¿½' => '', '%3F' => 'Ü',
		);
	}
}

if (!function_exists('rkrk_looks_mojibake')) {
	function rkrk_looks_mojibake($text) {
		if (!is_string($text) || $text === '') {
			return false;
		}
		return (strpos($text, 'Ã') !== false || strpos($text, 'Â') !== false || strpos($text, 'ï¿½') !== false);
	}
}

if (!function_exists('rkrk_mojibake_repair')) {
	function rkrk_mojibake_repair($text) {
		$text = rkrk_bytes_to_utf8((string) $text);
		if ($text === '' || !rkrk_looks_mojibake($text)) {
			return $text;
		}
		if (function_exists('rkrk_adr_repair_mojibake')) {
			return rkrk_adr_repair_mojibake($text);
		}
		$map = rkrk_mojibake_map();
		for ($pass = 0; $pass < 4; $pass++) {
			$prev = $text;
			$text = strtr($text, $map);
			if ($text === $prev) {
				break;
			}
		}
		return $text;
	}
}

if (!function_exists('rkrk_text_utf8')) {
	function rkrk_text_utf8($text) {
		if ($text === null) {
			return '';
		}
		if ($text instanceof SimpleXMLElement) {
			$text = trim((string) $text);
		} elseif (is_array($text) || is_object($text)) {
			return $text;
		} else {
			$text = (string) $text;
		}
		$text = rkrk_bytes_to_utf8($text);
		if (rkrk_looks_mojibake($text)) {
			$text = rkrk_mojibake_repair($text);
		}
		return $text;
	}
}

if (!function_exists('rkrk_read_text_file')) {
	function rkrk_read_text_file($path) {
		if (!is_string($path) || $path === '' || !is_file($path)) {
			return false;
		}
		$raw = @file_get_contents($path);
		if ($raw === false) {
			return false;
		}
		return rkrk_bytes_to_utf8($raw);
	}
}

if (!function_exists('rkrk_write_utf8_file')) {
	function rkrk_write_utf8_file($path, $text) {
		if (!is_string($path) || $path === '') {
			return false;
		}
		$dir = dirname($path);
		if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
			if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
				return false;
			}
		}
		$text = rkrk_bytes_to_utf8((string) $text);
		return @file_put_contents($path, $text, LOCK_EX) !== false;
	}
}

if (!function_exists('rkrk_json_encode_utf8')) {
	function rkrk_json_encode_utf8($data) {
		$flags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
		if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
			$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
		}
		$json = json_encode($data, $flags);
		return is_string($json) ? $json : false;
	}
}

if (!function_exists('rkrk_json_decode_utf8')) {
	function rkrk_json_decode_utf8($raw) {
		$raw = rkrk_bytes_to_utf8((string) $raw);
		if ($raw === '') {
			return array();
		}
		$data = json_decode($raw, true);
		return is_array($data) ? $data : array();
	}
}

if (!function_exists('rkrk_read_json_file')) {
	function rkrk_read_json_file($path) {
		$raw = rkrk_read_text_file($path);
		if ($raw === false || $raw === '') {
			return array();
		}
		return rkrk_json_decode_utf8($raw);
	}
}

if (!function_exists('rkrk_write_json_file')) {
	function rkrk_write_json_file($path, $data) {
		$json = rkrk_json_encode_utf8($data);
		if ($json === false) {
			return false;
		}
		return rkrk_write_utf8_file($path, $json . "\n");
	}
}

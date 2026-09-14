<?php
/**
 * PHP 8.0 compatibility helpers.
 *
 * Included first from rkrk_gen_ext.php so every page that uses rkrk_gen.php
 * gets scalar-safe string handling. No storage paths or public URL names change.
 */

if (!function_exists('rkrk_str')) {
	/**
	 * @param mixed $value
	 */
	function rkrk_str($value): string {
		if ($value === null || $value === false) {
			return '';
		}
		if (is_array($value)) {
			if ($value === array()) {
				return '';
			}
			$first = reset($value);
			return rkrk_str($first);
		}
		if (is_object($value)) {
			if (method_exists($value, '__toString')) {
				return (string)$value;
			}
			return '';
		}
		if (is_bool($value)) {
			return $value ? '1' : '';
		}
		return (string)$value;
	}
}

if (!function_exists('rkrk_html')) {
	/**
	 * htmlspecialchars() compatible with PHP 8.0 (rejects null/array).
	 * Flags match PHP 8.0 default (ENT_COMPAT), not the PHP 8.1+ defaults.
	 *
	 * @param mixed $value
	 */
	function rkrk_html($value): string {
		return htmlspecialchars(rkrk_str($value), ENT_COMPAT, 'UTF-8');
	}
}

if (!function_exists('rkrk_server')) {
	function rkrk_server(string $key, string $default = ''): string {
		return rkrk_str($_SERVER[$key] ?? $default);
	}
}

if (!function_exists('rkrk_lower')) {
	/**
	 * @param mixed $value
	 */
	function rkrk_lower($value): string {
		return strtolower(rkrk_str($value));
	}
}

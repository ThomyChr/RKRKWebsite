<?php
// ==================
// Funktionen für die Rehktitzrettung
// XML File handling (UTF-8, inkl. französische Akzente é/è/à/ç …)
// ==================

include_once('scripts/rkrk_gen.php');

/**
 * Stellt sicher, dass ein Wert als gültiger UTF-8-String vorliegt.
 */
function rkrk_xml_to_string($value)
{
	if ($value === null) {
		return '';
	}
	if (is_bool($value)) {
		return $value ? '1' : '0';
	}
	if ($value instanceof SimpleXMLElement) {
		$value = trim((string) $value);
	} elseif (is_array($value) || is_object($value)) {
		$value = json_encode($value, JSON_UNESCAPED_UNICODE);
	} else {
		$value = (string) $value;
	}
	if ($value === '') {
		return '';
	}
	if (function_exists('rkrk_text_utf8')) {
		return rkrk_text_utf8($value);
	}
	if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
		$value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
	}
	return $value;
}

/**
 * Feldtext aus XML-Knoten (SimpleXML) als Plain-String – kein json_encode.
 */
function rkrk_xml_prepare_field_text($value)
{
	if ($value === null) {
		return '';
	}
	if ($value instanceof SimpleXMLElement) {
		return rkrk_xml_to_string(trim((string) $value));
	}
	if (is_array($value)) {
		if (isset($value[0]) && !is_array($value[0])) {
			return rkrk_xml_to_string($value[0]);
		}
		if (count($value) === 1) {
			return rkrk_xml_to_string(reset($value));
		}
	}
	return rkrk_xml_to_string($value);
}

/**
 * Escaped Text für XML-Element-Inhalte (behält é, É, à, È, …).
 */
function rkrk_xml_escape_text($value)
{
	$value = rkrk_xml_to_string($value);
	if ($value === '') {
		return '';
	}
	return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8', false);
}

/**
 * Escaped Text für XML-Kommentare (kein "--" erlaubt).
 */
function rkrk_xml_escape_comment($value)
{
	$value = rkrk_xml_to_string($value);
	$value = str_replace('--', '- -', $value);
	return $value;
}

function XMLHeader($Fieldname)
{
	$Text = '<?xml version="1.0" encoding="UTF-8"?>';
	$Text .= chr(10) . chr(10) . chr(10);
	$Text .= '<' . $Fieldname . '>';
	$Text .= chr(10);
	return $Text;
}

function XMLComment($Value, $intent)
{
	$Text = '';
	for ($i = 0; $i < $intent; $i++) {
		$Text .= '  ';
	}
	$Text .= '<!-- ====== ' . rkrk_xml_escape_comment($Value) . ' ====== -->';
	$Text .= chr(10);
	return $Text;
}

function XMLField($Fieldname, $Value, $intent)
{
	$Text = '';
	for ($i = 0; $i < $intent; $i++) {
		$Text .= '  ';
	}
	$Text .= '<' . $Fieldname . '>';
	$Text .= rkrk_xml_escape_text($Value);
	$Text .= '</' . $Fieldname . '>';
	$Text .= chr(10);
	return $Text;
}

function XMLSetStart($Fieldname, $idNr, $intent)
{
	$Text = '';
	for ($i = 0; $i < $intent; $i++) {
		$Text .= '  ';
	}
	$Text .= '<' . $Fieldname . ' id="' . rkrk_xml_escape_text($idNr) . '">';
	$Text .= chr(10);
	return $Text;
}

function XMLSetEnd($Fieldname, $intent)
{
	$Text = '';
	for ($i = 0; $i < $intent; $i++) {
		$Text .= '  ';
	}
	$Text .= '</' . $Fieldname . '>';
	$Text .= chr(10);
	return $Text;
}

function XMLFolderStart($Fieldname, $intent)
{
	$Text = '';
	for ($i = 0; $i < $intent; $i++) {
		$Text .= '  ';
	}
	$Text .= '<' . $Fieldname . '>';
	$Text .= chr(10);
	return $Text;
}

function XMLFolderEnd($Fieldname, $intent)
{
	$Text = '';
	for ($i = 0; $i < $intent; $i++) {
		$Text .= '  ';
	}
	$Text .= '</' . $Fieldname . '>';
	$Text .= chr(10) . chr(10);
	return $Text;
}

function XMLFooter($Fieldname)
{
	return '</' . $Fieldname . '>';
}

?>

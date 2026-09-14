<?php
// ==================
// Funktionen für die Rehktitzrettung
// ==================

// ==================
// Header Page
// ==================

function AddXMLHeader() {
	$output = "";
	$output .=  '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'. chr(10);
	//$output .= '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">'. chr(10). chr(10);
	//$output .= '<Document>'. chr(10);
	//$output .= '<name><![CDATA[KML Daten Rehkitzrettung Knonaueramt]]></name>'. chr(10);
	return $output;
}

function AddXMLFooter() {
	$output = "";
	//$output .= '</Document>' . chr(10);
	//$output .= '</kml>' . chr(10);
	return $output;

}


?>
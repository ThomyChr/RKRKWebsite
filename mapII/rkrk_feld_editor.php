<?php
declare(strict_types=1);

/**
 * Dedicated RKRK editor entrypoint.
 *
 * This wrapper intentionally renders the original editor unchanged so that
 * "Flugvorgaben" form and all editor interactions are exactly identical to
 * field_editor.php.
 */

ob_start();
require __DIR__ . '/field_editor.php';
$html = (string) ob_get_clean();

// Optional title override for easier browser tab distinction.
$html = str_replace('<title>RKRK Feld-Editor</title>', '<title>RKRK Feldeditor</title>', $html);
$html = str_replace('<title>RKRS Feld-Editor</title>', '<title>RKRS Feldeditor</title>', $html);

echo $html;


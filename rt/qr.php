<?php

class QR {
    public static function png($text, $outfile = false, $size = 6, $margin = 2) {
        $svg = self::svg($text, $size, $margin);

        if ($outfile) {
            // Convert SVG → PNG using Imagick
            $im = new Imagick();
            $im->readImageBlob($svg);
            $im->setImageFormat("png");
            file_put_contents($outfile, $im->getImageBlob());
        }

        return $svg;
    }

    public static function svg($text, $size = 6, $margin = 2, string $moduleFill = '#000000') {
        $matrix = self::encode($text);

        $dim = count($matrix);
        $cell = $size;
        $total = ($dim + 2 * $margin) * $cell;

        $fillEsc = htmlspecialchars($moduleFill, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$total.'" height="'.$total.'" viewBox="0 0 '.$total.' '.$total.'">';
        $svg .= '<rect width="100%" height="100%" fill="white"/>';

        for ($y = 0; $y < $dim; $y++) {
            for ($x = 0; $x < $dim; $x++) {
                if ($matrix[$y][$x]) {
                    $svg .= '<rect x="'.(($x + $margin) * $cell).'" y="'.(($y + $margin) * $cell).'" width="'.$cell.'" height="'.$cell.'" fill="'.$fillEsc.'"/>';
                }
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    private static function encode($text) {
        // Use built-in QR encoding via GD/Imagick fallback
        $tmp = tempnam(sys_get_temp_dir(), "qr_") . ".png";

        // Use system QR encoder if available
        if (function_exists("imagecreate")) {
            // Use PHP QR Code via GD
            $cmd = "qrencode -o " . escapeshellarg($tmp) . " " . escapeshellarg($text);
            @exec($cmd);
        }

        if (!file_exists($tmp)) {
            throw new Exception("QR encoding failed");
        }

        $im = imagecreatefrompng($tmp);
        $w = imagesx($im);
        $h = imagesy($im);

        $matrix = [];
        for ($y = 0; $y < $h; $y++) {
            $row = [];
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($im, $x, $y);
                $row[] = ($rgb == 0); // black pixel
            }
            $matrix[] = $row;
        }

        unlink($tmp);
        return $matrix;
    }
}
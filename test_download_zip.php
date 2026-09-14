<?php
$url = "https://data.geo.admin.ch/ch.swisstopo.swissalti3d/swissalti3d_2019_2501-1120/swissalti3d_2019_2501-1120_2_2056_5728.xyz.zip";
//echo $filename = pathinfo($url)['filename'];
$localPath = "https://" . $_SERVER['HTTP_HOST'].pathinfo($_SERVER['PHP_SELF'])['dirname'] . "/kmlfiles/" .$filename; // Make sure the 'downloads' directory is writable

function downloadFile($url, $path)
{
    $newfname = $path;
    $file = fopen ($url, 'rb');
    if ($file) {
        $newf = fopen ($newfname, 'wb');
        if ($newf) {
            while(!feof($file)) {
                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
            }
        }
    }
    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
}


// Download and save the file
$content = file_get_contents($url);
echo "Content: ". $content;
file_put_contents($localPath, $content);
//file_put_contents($localPath, fopen($url, 'r'));
//downloadFile($url, $localPath);

echo "Download completed!";
?>
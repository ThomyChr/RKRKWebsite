<?php
// Step 1: Download the file to a temporary location
$tempFile = tempnam(sys_get_temp_dir(), 'csv_');
file_put_contents($tempFile, file_get_contents('https://www.rkrzug.ch/rkr_csv_download.php?delimiter=;'));

// Step 2: Read and display the CSV
if (($handle = fopen($tempFile, 'r')) !== false) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    $isHeader = true;

    while (($row = fgetcsv($handle, 1000, ';')) !== false) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo $isHeader ? "<th>" . htmlspecialchars($cell) . "</th>" : "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
        $isHeader = false;
    }

    echo "</table>";
    fclose($handle);
    unlink($tempFile); // Clean up
} else {
    echo "Unable to read downloaded CSV.";
}
?>
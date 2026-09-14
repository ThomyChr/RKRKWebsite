<?php
// This PHP file contains everything: HTML, CSS, JS.
// No backend logic needed for the screenshot.
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Iframe Screenshot – Bereich um das Iframe</title>

<style>
#frameContainer {
    width: 500px;
    height: 350px;
    border: 2px solid #444;
    margin-bottom: 20px;
    overflow: hidden;
}
iframe {
    width: 100%;
    height: 100%;
    border: none;
}
button {
    padding: 10px 20px;
    font-size: 16px;
}
</style>
</head>
<body>

<h2>Screenshot vom Bereich um das Iframe</h2>

<div id="frameContainer">
    <iframe id="SuchbildFrame"
        src="https://map.geo.admin.ch/#/map?lang=de&center=2669077.15,1224453.97&z=10.316&topic=ech&layers=ch.swisstopo.swisstlm3d-karte-farbe">
    </iframe>
</div>

<button onclick="captureIframeArea()">Screenshot machen</button>

<script>
// Load html2canvas dynamically
function loadHtml2Canvas() {
    return new Promise(resolve => {
        if (window.html2canvas) return resolve();
        const script = document.createElement("script");
        script.src = "https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js";
        script.onload = resolve;
        document.body.appendChild(script);
    });
}

async function captureIframeArea() {

    const iframe = document.getElementById("SuchbildFrame");

    // Padding around the iframe (adjust as needed)
    const padding = 20;

    await loadHtml2Canvas();

    // Get iframe position relative to viewport
    const rect = iframe.getBoundingClientRect();

    // Compute crop area
    const cropX = rect.left - padding + window.scrollX;
    const cropY = rect.top - padding + window.scrollY;
    const cropW = rect.width + padding * 2;
    const cropH = rect.height + padding * 2;

    // Screenshot the whole page
    const fullCanvas = await html2canvas(document.body, {
        scrollX: -window.scrollX,
        scrollY: -window.scrollY
    });

    // Create cropped canvas
    const cropCanvas = document.createElement("canvas");
    cropCanvas.width = cropW;
    cropCanvas.height = cropH;

    const ctx = cropCanvas.getContext("2d");

    ctx.drawImage(
        fullCanvas,
        cropX, cropY, cropW, cropH,   // source
        0, 0, cropW, cropH            // destination
    );

    // Download
    const link = document.createElement("a");
    link.download = "iframe_area_screenshot.jpg";
    link.href = cropCanvas.toDataURL("image/jpeg", 0.9);
    link.click();
}
</script>

</body>
</html>
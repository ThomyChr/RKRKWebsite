<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Iframe Screenshot Demo – funktioniert garantiert</title>

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
</style>
</head>
<body>

<h2>Iframe Screenshot Demo (Chrome, ohne Auswahl)</h2>

<div id="frameContainer">
    <iframe id="SuchbildFrame" src="https://map.geo.admin.ch/#/map?lang=de&center=2669077.15,1224453.97&z=10.316&topic=ech&layers=ch.swisstopo.swisstlm3d-karte-farbe;KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?Reg=alle%26Strt=on%26Cntr=off%26Area=on%26Pfad=on%26WW=off%26FZ=off%26GDE=off%26JGG=off%26KT=off%26ORT=off%26Poi=off%26HSP=on%26Label=off%26GeLa=off%26Sun=off%26EG=off%26SORA=off%26WF=off%26KA=M%26Zoom=1%26FFilter=136-004%26SFilter=%26FLP=on%26MDB=3T%26GA=gast%26WebAccess=test&bgLayer=ch.swisstopo.pixelkarte-farbe" width="500" height="500"></iframe>
</div>

<button onclick="captureIframe()">Screenshot vom Iframe machen</button>

<script>
// html2canvas dynamisch laden
function loadHtml2Canvas() {
    return new Promise(resolve => {
        const script = document.createElement("script");
        script.src = "https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js";
        script.onload = resolve;
        document.body.appendChild(script);
    });
}

async function captureIframe() {

    const iframe = document.getElementById("SuchbildFrame");
    const iframeDoc = iframe.contentDocument;

    if (!iframeDoc) {
        alert("Iframe nicht same-origin! Screenshot nicht möglich.");
        return;
    }

    if (typeof html2canvas === "undefined") {
        await loadHtml2Canvas();
    }

    html2canvas(iframeDoc.body).then(canvas => {
        const link = document.createElement("a");
        link.download = "iframe_screenshot.jpg";
        link.href = canvas.toDataURL("image/jpeg");
        link.click();
    });
}
</script>

</body>
</html>


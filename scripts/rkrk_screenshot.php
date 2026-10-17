<?php
/* ===========================================================================
   SNAPSHOT – COMPLETE INCLUDE FILE
   Popup/Tab + Local Download + Optional Server Upload (custom path)
   WITH CORRECT AUTO-DETECTED UPLOAD URL
   =========================================================================== */

$snapScript = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$snapDir = rtrim(dirname($snapScript), '/');
if (strtolower(basename($snapDir)) === 'fe') {
    $snapDir = rtrim(dirname($snapDir), '/');
}
if ($snapDir === '' || $snapDir === '.' || $snapDir === '/') {
    $snapshotMap = 'rkrk_map.php';
    $snapshotCall = 'rkrk_edit_felddaten.php';
    $uploadURL = 'scripts/upload_snapshot.php';
} else {
    $snapshotMap = $snapDir . '/rkrk_map.php';
    $snapshotCall = $snapDir . '/rkrk_edit_felddaten.php';
    $uploadURL = $snapDir . '/scripts/upload_snapshot.php';
}
?>

<script>
const BASE_URL = "<?php echo htmlspecialchars($snapshotMap, ENT_QUOTES, 'UTF-8'); ?>";
const CALL_URL = "<?php echo htmlspecialchars($snapshotCall, ENT_QUOTES, 'UTF-8'); ?>";

// AUTO-INJECTED ABSOLUTE UPLOAD URL FROM PHP
const SNAPSHOT_UPLOAD_URL = "<?php echo $uploadURL; ?>";

/* ---------------------------------------------------------
   URL Builder
--------------------------------------------------------- */
function buildURL(ffilter, mode) {
    const such = "Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=off&GDE=off&JGG=off&Poi=off&HSP=on&OVR=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.6";
    const flug = "Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&HSP=on&FLP=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.8";
    const query = (mode === "suchfeld") ? such : flug;
    return BASE_URL + "?FFilter=" + encodeURIComponent(ffilter) + "&" + query;

/*
    const common =
        "&Reg=ALL&Strt=on&Area=on&Cntr=off&GDE=off&JGG=off&Poi=off&Label=off&EX=NO&KA=M&MAP=&MenuOn=off";

    if (mode === "suchfeld") {
        return BASE_URL + "?FFilter=" + encodeURIComponent(ffilter)
            + common + "&Pfad=off&HSP=off&FLP=off&OVR=on&Zoom=0.8";
    } else {
        return BASE_URL + "?FFilter=" + encodeURIComponent(ffilter)
            + common + "&Pfad=on&HSP=on&FLP=on&OVR=on&Zoom=1.4";
    }
*/
}

/* ---------------------------------------------------------
   Filename Generator
--------------------------------------------------------- */
function generateFilename(ffilter, mode) {
    const now = new Date();
    const YY = String(now.getFullYear()).slice(-2);
    const MM = String(now.getMonth() + 1).padStart(2, "0");
    const DD = String(now.getDate()).padStart(2, "0");
    const hh = String(now.getHours()).padStart(2, "0");
    const mm = String(now.getMinutes()).padStart(2, "0");
    return `${ffilter}_${mode}_${YY}${MM}${DD}${hh}${mm}.jpg`;
}

/* ---------------------------------------------------------
   Main Workflow
--------------------------------------------------------- */
async function startSnapshot(params) {

    const ffilter = params.ffilter;
    let filename  = params.filename;
    const mode    = params.mode;
    const cropWidth  = params.w;
    const cropHeight = params.h;
    const offsetY    = params.off;
    const zoomPercent = params.zoom;

    const usePopup   = params.usePopup ?? true;
    const serverPath = params.serverPath ?? "";

    if (!filename || filename === "") {
        filename = generateFilename(ffilter, mode);
    }

    const editWin = window.open("", "_editfield");

    const geoURL = buildURL(ffilter, mode);
    let geomapWin;

    if (usePopup) {
        geomapWin = window.open(
            geoURL,
            "_geomap",
            "width=1200,height=900,left=100,top=50"
        );
    } else {
        geomapWin = window.open(geoURL, "_geomap");
    }

    const stream = await navigator.mediaDevices.getDisplayMedia({
        video: { displaySurface: "window", logicalSurface: true },
        audio: false
    });

    const track = stream.getVideoTracks()[0];

    const interval = setInterval(() => {
        try {
            if (geomapWin.document.readyState === "complete") {
                clearInterval(interval);
                setTimeout(() => captureSnapshot(
                    ffilter,
					mode,
					track,
                    filename,
                    geomapWin,
                    editWin,
                    cropWidth,
                    cropHeight,
                    offsetY,
                    zoomPercent,
                    serverPath
                ), 600);
            }
        } catch (e) {}
    }, 300);
}

/* ---------------------------------------------------------
   Screenshot + Crop + Save + Close
--------------------------------------------------------- */
async function captureSnapshot(
    ffilter,
	mode,
	track,
    filename,
    geomapWin,
    editWin,
    cropWidth,
    cropHeight,
    offsetY,
    zoomPercent,
    serverPath
) {

    const imageCapture = new ImageCapture(track);
    const bitmap = await imageCapture.grabFrame();

    const zoom = zoomPercent / 100;

    const canvas = document.createElement("canvas");
    canvas.width = cropWidth;
    canvas.height = cropHeight;

    const ctx = canvas.getContext("2d");
    ctx.scale(zoom, zoom);

    const visibleWidth  = cropWidth  / zoom;
    const visibleHeight = cropHeight / zoom;

    const centerX = bitmap.width / 2;
    const centerY = bitmap.height / 2 + offsetY;

    let x = centerX - visibleWidth / 2;
    let y = centerY - visibleHeight / 2;

    x = Math.max(0, Math.min(x, bitmap.width  - visibleWidth));
    y = Math.max(0, Math.min(y, bitmap.height - visibleHeight));

    ctx.drawImage(
        bitmap,
        x, y,
        visibleWidth,
        visibleHeight,
        0, 0,
        visibleWidth,
        visibleHeight
    );

    track.stop();

    /* ---------------------------------------------------------
       OPTION A: Upload to server
    --------------------------------------------------------- */
    if (serverPath !== "") {

        canvas.toBlob(async function(blob) {

            const formData = new FormData();
            formData.append("file", blob, filename);
            formData.append("path", serverPath);

            const uploadResponse = await fetch(SNAPSHOT_UPLOAD_URL, {
                method: "POST",
                body: formData
            });
            const uploadResult = await uploadResponse.json().catch(() => ({ ok: false, error: "invalid upload response" }));
            if (!uploadResponse.ok || !uploadResult.ok) {
                alert("Snapshot konnte nicht gespeichert werden: " + (uploadResult.error || uploadResponse.statusText));
                if (geomapWin && !geomapWin.closed) geomapWin.close();
                if (editWin) editWin.focus();
                return;
            }
            alert("Snapshot gespeichert nach:\n" + uploadResult.file);

            if (geomapWin && !geomapWin.closed) geomapWin.close();
            if (editWin) editWin.focus();

           /* 🔄 REDIRECT AFTER UPLOAD */
			if (mode === "suchfeld") {
				//window.location.href = CALL_URL + "?"FeldID=" + encodeURIComponent(ffilter) + "&JG=" + ffilter.split("-")[0] + "&File=" + filename + "&cleanandadd=Y&XMLLabel=SuchfeldBild";
				window.location.href = CALL_URL + "?ActPage=1&FeldID=" + encodeURIComponent(ffilter) + "&JG=" + ffilter.split("-")[0] + "&File=" + filename + "&addentry=Y&XMLLabel=SuchfeldBild";
			} else {
				//window.location.href = CALL_URL + "?FeldID=" + encodeURIComponent(ffilter) + "&JG=" + ffilter.split("-")[0] + "&File=" + filename + "&cleanandadd=Y&XMLLabel=FlugplanBild";
				window.location.href = CALL_URL + "?ActPage=1&FeldID=" + encodeURIComponent(ffilter) + "&JG=" + ffilter.split("-")[0] + "&File=" + filename + "&addentry=Y&XMLLabel=FlugplanBild";
			}

        }, "image/jpeg", 0.92);

        return;
    }

    /* ---------------------------------------------------------
       OPTION B: Local download
    --------------------------------------------------------- */
    const link = document.createElement("a");
    link.download = filename;
    link.href = canvas.toDataURL("image/jpeg");

    setTimeout(() => {
        link.click();
        if (geomapWin && !geomapWin.closed) geomapWin.close();
        if (editWin) editWin.focus();
    }, 200);
}
</script>
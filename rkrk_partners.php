<?php
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');

$pagetitle = "Partnerschaften, Sponsoring & Finanzkontakte";
headerpage($pagetitle, false);
CreateTopMenu();
PrintRKRKHeader($pagetitle);

function rkrk_partners_h($v)
{
	return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$imgBase = rtrim((string)$ImageLoc, '/') . '/';
$uavUrl = 'https://rkrs.uaveditor.com/feldanmeldung?initialData=%7B%22properties%22%3A%7B%22orderData%22%3A%7B%22recommendedPilot%22%3A%22c-Cc16iQStW1%22%7D%7D%2C%22mapCenter%22%3A%7B%22lat%22%3A47.23993%2C%22lng%22%3A8.46005%7D%7D';
$webUrl = 'https://www.drohnenrehkitzrettung.ch';
$donateUrl = 'https://www.drohnenrehkitzrettung.ch/spenden';
$iban = 'CH12 0023 9239 1639 8641 T';
?>
<style>
.rkrk-partners {
	max-width: 1100px;
	margin: 0 16px 40px;
	display: grid;
	gap: 18px;
}
.rkrk-partners-intro {
	margin: 0;
	color: var(--rkrk-muted, #5a675a);
	font-size: 15px;
	line-height: 1.45;
}
.rkrk-partners-card {
	display: grid;
	grid-template-columns: minmax(0, 1fr) auto;
	gap: 20px 28px;
	align-items: start;
	background: #fff;
	border: 1px solid var(--rkrk-border, #d5e0d2);
	border-radius: 14px;
	padding: 22px 22px 20px;
	box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
}
.rkrk-partners-card h2 {
	margin: 0 0 10px;
	font-size: 1.35rem;
	line-height: 1.25;
	color: var(--brand-dark, #48673A);
}
.rkrk-partners-card p {
	margin: 0 0 12px;
	font-size: 16px;
	line-height: 1.45;
}
.rkrk-partners-card p:last-child { margin-bottom: 0; }
.rkrk-partners-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin-top: 14px;
}
.rkrk-partners-card a.btn,
.rkrk-partners-card button.btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-height: 40px;
	padding: 8px 14px;
	border-radius: 8px;
	text-decoration: none;
	font-weight: 650;
	font-size: 15px;
	cursor: pointer;
}
.rkrk-partners-qrs {
	display: flex;
	flex-wrap: wrap;
	gap: 16px;
	justify-content: flex-end;
}
.rkrk-partners-qr {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 6px;
	text-decoration: none;
	color: var(--rkrk-muted, #5a675a);
	font-size: 12px;
	font-weight: 600;
}
.rkrk-partners-qr img {
	display: block;
	width: 168px;
	height: auto;
	max-width: 36vw;
	background: #fff;
	border: 1px solid var(--rkrk-border, #d5e0d2);
	border-radius: 10px;
	padding: 8px;
}
.rkrk-partners-qr.large img {
	width: 196px;
}
.rkrk-partners-bank {
	margin: 12px 0 0;
	padding: 12px 14px;
	background: var(--brand-vlight, #ECF7EC);
	border: 1px solid var(--rkrk-border, #d5e0d2);
	border-radius: 10px;
	font-size: 15px;
	line-height: 1.45;
}
.rkrk-partners-bank dt {
	margin: 0;
	color: var(--rkrk-muted, #5a675a);
	font-size: 12px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.03em;
}
.rkrk-partners-bank dd {
	margin: 0 0 10px;
}
.rkrk-partners-bank dd:last-child { margin-bottom: 0; }
.rkrk-partners-iban {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 8px;
	font-weight: 750;
	font-size: 17px;
	letter-spacing: 0.02em;
}
.rkrk-partners-note {
	margin-top: 14px;
	font-size: 15px;
	line-height: 1.45;
}
@media (max-width: 780px) {
	.rkrk-partners { margin-left: 12px; margin-right: 12px; }
	.rkrk-partners-card {
		grid-template-columns: minmax(0, 1fr);
		padding: 16px;
	}
	.rkrk-partners-qrs { justify-content: center; }
	.rkrk-partners-qr img,
	.rkrk-partners-qr.large img { width: 160px; max-width: 46vw; }
}
</style>
<?php
if (($Layout ?? '') != 2023) {
	echo "<div class='rkrk-partners-actions' style='margin:0 16px 8px;'>";
	echo CreateSiteLink("Home", "");
	echo CreateSiteLink("GoBack", "");
	echo "</div>";
}
?>
<div class="rkrk-partners">
	<p class="rkrk-partners-intro">Feldanmeldung, Website und Spendenkontakte der IG Drohnenrehkitzrettung Knonaueramt.</p>

	<section class="rkrk-partners-card">
		<div>
			<h2>Feldanmeldung über den UAV Editor</h2>
			<p>Möchtest du ein neues Feld direkt über den UAV Editor an unser Team melden? Dann den Link oder den QR-Code verwenden.</p>
			<div class="rkrk-partners-actions">
				<a class="btn" href="<?= rkrk_partners_h($uavUrl) ?>" target="_blank" rel="noopener">Feldanmeldung öffnen</a>
			</div>
		</div>
		<div class="rkrk-partners-qrs">
			<a class="rkrk-partners-qr" href="<?= rkrk_partners_h($uavUrl) ?>" target="_blank" rel="noopener">
				<img src="<?= rkrk_partners_h($imgBase . 'UAVQRcode_RKRK.jpg') ?>" alt="QR-Code Feldanmeldung UAV Editor">
				<span>UAV Editor</span>
			</a>
		</div>
	</section>

	<section class="rkrk-partners-card">
		<div>
			<h2>Unterstützen</h2>
			<p>Möchten Sie uns gerne unterstützen? Dann den QR-Code für eine Bank- oder TWINT-Zahlung verwenden — oder direkt über die Website.</p>
			<div class="rkrk-partners-actions">
				<a class="btn" href="<?= rkrk_partners_h($webUrl) ?>" target="_blank" rel="noopener">www.drohnenrehkitzrettung.ch</a>
				<a class="btn secondary" href="<?= rkrk_partners_h($donateUrl) ?>" target="_blank" rel="noopener">Spendenseite</a>
			</div>
			<dl class="rkrk-partners-bank">
				<dt>Bank</dt>
				<dd>UBS Switzerland AG, Bahnhofstrasse 45, Postfach, CH-8001 Zürich<br>BIC: UBSWCHZH80A</dd>
				<dt>IBAN</dt>
				<dd>
					<div class="rkrk-partners-iban">
						<span id="rkrk-partners-iban"><?= rkrk_partners_h($iban) ?></span>
						<button type="button" class="btn secondary" id="rkrk-partners-copy">IBAN kopieren</button>
					</div>
				</dd>
				<dt>Konto</dt>
				<dd><i>drohnenrehkitzrettung.ch c/o Thomas Christen, Baumgartenweg 7, 8934 Knonau</i></dd>
			</dl>
			<div class="rkrk-partners-note">
				<p>Herzlichen Dank!</p>
				<p>Durch Ihre Unterstützung können wir weiterhin die <b>Drohnensuchflüge kostenfrei</b> anbieten.</p>
				<p><i>Euer Team von der IG «Drohnenrehkitzrettung Knonaueramt»</i></p>
			</div>
		</div>
		<div class="rkrk-partners-qrs">
			<a class="rkrk-partners-qr" href="<?= rkrk_partners_h($webUrl) ?>" target="_blank" rel="noopener">
				<img src="<?= rkrk_partners_h($imgBase . 'qr_drohnenrekitzrettungch.jpg') ?>" alt="QR-Code Website drohnenrehkitzrettung.ch">
				<span>Website</span>
			</a>
			<a class="rkrk-partners-qr large" href="<?= rkrk_partners_h($donateUrl) ?>" target="_blank" rel="noopener">
				<img src="<?= rkrk_partners_h($imgBase . 'twint_frei.jpg') ?>" alt="TWINT QR-Code Spende">
				<span>TWINT</span>
			</a>
			<a class="rkrk-partners-qr large" href="<?= rkrk_partners_h($donateUrl) ?>" target="_blank" rel="noopener">
				<img src="<?= rkrk_partners_h($imgBase . 'qr_swiss_frei.jpg') ?>" alt="Swiss QR-Code Bankzahlung">
				<span>Bank / Swiss QR</span>
			</a>
		</div>
	</section>
</div>
<script>
(function () {
	var btn = document.getElementById("rkrk-partners-copy");
	var iban = document.getElementById("rkrk-partners-iban");
	if (!btn || !iban) return;
	btn.addEventListener("click", function () {
		var text = (iban.textContent || "").replace(/\s+/g, " ").trim();
		var done = function () {
			btn.textContent = "Kopiert";
			setTimeout(function () { btn.textContent = "IBAN kopieren"; }, 1600);
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(done).catch(done);
			return;
		}
		done();
	});
})();
</script>
</body>
</html>

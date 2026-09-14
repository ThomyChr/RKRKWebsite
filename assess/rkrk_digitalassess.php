<?php 
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once($folderoffset.'scripts/rkrk_inputforms.php');
include_once($folderoffset.'scripts/rkrk_csvfiles.php');
include_once('rkrk_assess_common.php');
include_once(__DIR__ . '/rkrk_assess_quiz_progress.inc.php');
include_once(__DIR__ . '/rkrk_assess_exam_session.inc.php');
if (function_exists('rkrk_assess_portal_digitalassess_enforce_access')) {
	rkrk_assess_portal_digitalassess_enforce_access();
}
if (function_exists('rkrk_assess_quiz_progress_purge_old_maybe')) {
	rkrk_assess_quiz_progress_purge_old_maybe();
}

if (!$onRKRS) {
	//check impropriate IP addresses accessing page
	CheckIPandExit($folderoffset);

	//Write to LOGfile
	WriteToLog("open", basename($_SERVER['PHP_SELF']), "","","","",$folderoffset);

	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
	GrantAccess($folderoffset);
	if (empty($LoggedOn)) {
		if (function_exists('RegisterForm')) {
			RegisterForm(getIPAddress(), "Login", true);
		}
		exit;
	}

	$currentAccessLevel = '';
	if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
		$currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
	} elseif (isset($InputUserInfo['Login']['Level'])) {
		$currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
	}
	$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
	$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast' && !$onRKRS)
		&& !(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode());
	if ($showTopMenu) {
		CreateTopMenu($folderoffset);
		echo "<br><br>";
	}
}
$isAdminMode = (isset($_GET['ADMIN']) || (isset($onAdmin) && (bool)$onAdmin));
$isInstrMode = (isset($_GET['INSTR']) || isset($_POST['INSTR']) || (isset($onInstr) && (bool)$onInstr));
$digitalassessSchulungMode = isset($_GET['Schulung']);
if ($digitalassessSchulungMode) {
	$pagetitle = $onRKRS ? 'Schulung — Rehkitzrettung Schweiz' : 'Schulung — Rehkitzrettung Knonaueramt';
} elseif ($isAdminMode) {
	$pagetitle = $onRKRS ? 'Digital Assessments — RKRS Verwaltung' : 'Digital Assessments — Verwaltung (Knonaueramt)';
} elseif ($isInstrMode) {
	$pagetitle = $onRKRS ? 'Digital Assessments — RKRS Instruktor' : 'Digital Assessments — Instruktor';
} else {
	$pagetitle = $onRKRS ? 'Quiz — Rehkitzrettung Schweiz' : 'Quiz — Rehkitzrettung Knonaueramt';
}
$paperFormEarly = isset($_GET["paper"]) && (string)$_GET["paper"] === "1" && trim((string)($_GET["quiz"] ?? "")) !== "";
if (!$paperFormEarly) {
	headerpage($pagetitle, false, $folderoffset);
}

if (!$paperFormEarly && function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
	rkrk_assess_emit_admin_url_managers_menu_strip();
}

if (!$paperFormEarly) {
?>	

<!-- styles -->
<style>
  /* === Modern responsive layout (keeps existing IDs & JS hooks) === */
  /* CI-Farben: rkrk_assess_theme.css + bei ?RKRS / RKRSplus / RKRSprev rkrk_assess_theme_rkrs.css (kein :root hier — sonst wird Graustil wieder überschrieben) */

  body{ margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:var(--bg); color:var(--text); }

  /* Preserve legacy helper classes (used previously on <td>) */
  .left{ text-align:left; }
  .center{ text-align:center; }
  .right{ text-align:right; }

  /* Responsive container */
  .rkrk-wrap{ max-width:1100px; margin:0 auto; padding:14px; }
  .rkrk-card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:14px 16px;
  }

  /* Header */
  .rkrk-topbar{ display:flex; gap:12px; align-items:flex-start; justify-content:space-between; }
  .rkrk-title h1{ margin:0; font-size:24px; line-height:1.2; }
  .rkrk-title #indrodution{ display:block; margin-top:6px; opacity:.8; }
  .rkrk-title #indrodution:has(.rkrk-result-gesamt),
  .rkrk-title #indrodution:has(.rkrk-result-fragenliste){ opacity:1; }
  .rkrk-logo img{ height:56px; width:auto; object-fit:contain; display:block; }
  #rkrk-topbar-quicklinks{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    align-items:flex-start;
    margin-top:8px;
    width:100%;
    box-sizing:border-box;
  }
  /* Learning (iframe: relative Bilder in Schulungs-HTML auflösen wie unter der echten LernDatei-URL) */
  .rkrk-learning{ margin-top:14px; scroll-margin-top:12px; }
  .rkrk-learning-iframe{
    width:100%;
    height:min(78vh, 920px);
    min-height:70vh;
    border:0;
    border-radius:12px;
    background:#fff;
    display:block;
  }

  /* Main grid */
  .rkrk-grid{ display:grid; gap:14px; margin-top:14px; grid-template-columns:1fr; }
  @media (min-width: 900px){
    .rkrk-grid{ grid-template-columns: minmax(200px, 248px) 1fr; align-items:start; }
    .rkrk-sidebar{ position:sticky; top:12px; }
  }

  .rkrk-main {   width: 100%; max-width: 100%; box-sizing: border-box; }
  

  /* Sidebar: Fragen-Navigation */
  .rkrk-qnav-title{
    margin:0 0 8px 0;
    font-size:0.92rem;
    font-weight:700;
    letter-spacing:0.02em;
    color:var(--text);
  }
  .rkrk-sidebar #result.rkrk-qnav-grid{
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:5px;
    margin:0;
  }
  .rkrk-qnav-btn{
    width:100%;
    min-width:0;
    box-sizing:border-box;
    border-radius:4px;
    border:none;
    color:#fff;
    padding:5px 2px;
    font-size:12px;
    font-weight:600;
    line-height:1.15;
    cursor:pointer;
    margin:0;
    text-align:center;
    text-decoration:none;
    display:block;
  }
  .rkrk-qnav-btn--pending{ background:#aaaaaa !important; }
  .rkrk-qnav-btn--exam{ background:#1890ff !important; }
  .rkrk-qnav-btn--correct{ background:#67b516 !important; }
  .rkrk-qnav-btn--wrong{ background:#fc2222 !important; }
  .rkrk-qnav-btn--partial{ background:#f59e0b !important; }
  .rkrk-qnav-btn--current{
    box-shadow:0 0 0 1px #fff, 0 0 0 2px #334155;
  }
  .rkrk-qnav-legend{ display:block; margin-top:6px; font-size:11px; font-weight:500; color:#64748b; line-height:1.5; }
  .rkrk-qnav-legend-i{ display:inline-block; width:12px; height:12px; border-radius:3px; margin:0 4px 0 8px; vertical-align:middle; }
  .rkrk-qnav-legend-i:first-child{ margin-left:0; }
  .rkrk-qnav-legend-i--ok{ background:#67b516 !important; }
  .rkrk-qnav-legend-i--partial{ background:#f59e0b !important; }
  .rkrk-qnav-legend-i--bad{ background:#fc2222 !important; }
  .rkrk-qnav-legend-i--open{ background:#aaaaaa !important; }
  .rkrk-qnav-legend-i--exam{ background:#1890ff !important; }

  /* Question */
  .rkrk-qtitle{ font-weight:700; margin:10px 0 6px; font-size:16px; }
  .rkrk-qtext{ margin:0 0 12px; line-height:1.45; }

  /* Timers */
  #quiztimer-container, #timer-container{ width:100%; margin:8px 0; }
  #quiztimer-text, #timer-text{ display:block; text-align:center; font-weight:500; margin-bottom:6px; }
  #quiztimer-bar, #timer-bar{ height:10px; width:0%; background:#00c853; border-radius:999px; transition: width .15s linear, background-color .2s linear; }
  #quiztimer-container, #timer-container{ background:#eef1ee; border-radius:999px; padding:8px 10px; box-sizing:border-box; }
 
 /* Images responsive — Höhe nur per JS setzen, wenn ein Fragebild aktiv ist */
  #frage-bild, #loesung-bild{ max-width:100%; height:auto; vertical-align:top; }
  #frage-bild{ display:none; margin:0 0 14px; }
  .rkrk-qtext{ margin-top:0; }

  /* Answer area */
  .rkrk-fieldset{ border:1px solid var(--border); border-radius:12px; padding:12px; }
  .rkrk-fieldset legend{ padding:0 8px; font-weight:700; }
  .rkrk-antwort-hinweis{
    font-weight:500;
    font-size:0.92em;
    white-space:normal;
    padding:2px 8px;
    border-radius:6px;
  }
  .rkrk-antwort-hinweis--single{
    color:#166534;
    background:#f0fdf4;
    border:1px solid #dcfce7;
  }
  .rkrk-antwort-hinweis--multi{
    color:#1d4ed8;
    background:#eff6ff;
    border:1px solid #dbeafe;
  }
  .rkrk-progress-overlay{ position:fixed; inset:0; z-index:12000; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; padding:16px; box-sizing:border-box; }
  .rkrk-progress-card{ background:#fff; border:1px solid var(--border,#d8dfd6); border-radius:12px; padding:18px 20px; max-width:440px; width:100%; box-shadow:0 12px 40px rgba(15,23,42,.18); font-family:system-ui,sans-serif; }
  .rkrk-progress-card h3{ margin:0 0 8px; font-size:1.1rem; color:var(--text,#1f2937); }
  .rkrk-progress-card p{ margin:0 0 14px; font-size:0.95rem; line-height:1.45; color:#334155; }
  .rkrk-progress-actions{ display:flex; flex-wrap:wrap; gap:10px; }
  .rkrk-progress-status{ font-size:12px; color:#64748b; margin-top:6px; min-height:1.2em; }

  /* Make each answer option tappable */
  .rkrk-options{ display:grid; gap:10px; grid-template-columns:1fr; margin-top:10px; }
  @media (min-width: 560px){ .rkrk-options{ grid-template-columns:1fr 1fr; } }
  .rkrk-opt{ position:relative; display:block; padding:0; border:0; background:transparent; }
  .rkrk-opt input{
    position:absolute;
    opacity:0;
    pointer-events:none;
  }
  .rkrk-opt label{
    display:block;
    cursor:pointer;
    line-height:1.25;
    padding:12px 14px;
    border:1px solid var(--border);
    border-radius:12px;
    background:#fff;
    transition:background-color .15s ease, border-color .15s ease, box-shadow .15s ease, color .15s ease;
  }
  .rkrk-opt label:hover{
    border-color:#b7c7b4;
    background:#f7faf6;
  }
  .rkrk-opt input:focus-visible + label{
    outline:2px solid #7aa56d;
    outline-offset:2px;
  }
  .rkrk-opt input:checked + label{
    background:#e8f4e5;
    border-color:#6f9663;
    box-shadow:0 0 0 1px rgba(111,150,99,.15) inset;
    color:#1f3b1b;
    font-weight:600;
  }
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt label.rkrk-opt-label--richtig{
    background:#e8f4e5 !important;
    border-color:#6f9663 !important;
    box-shadow:0 0 0 1px rgba(111,150,99,.2) inset;
    color:#1f3b1b;
    font-weight:600;
  }
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt input:checked + label.rkrk-opt-label--falsch,
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt label.rkrk-opt-label--falsch{
    background:#fdeaea !important;
    border-color:#c96b6b !important;
    box-shadow:0 0 0 1px rgba(201,107,107,.2) inset;
    color:#5c1f1f;
    font-weight:600;
  }
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt label.rkrk-opt-label--verpasst{
    background:#fff4e5 !important;
    border-color:#e6983c !important;
    box-shadow:0 0 0 1px rgba(230,152,60,.2) inset;
    color:#7a420e;
    font-weight:600;
  }
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt label.rkrk-opt-label--richtig::before,
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt label.rkrk-opt-label--falsch::before,
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt label.rkrk-opt-label--verpasst::before{
    display:inline-block;
    min-width:1.1em;
    margin-right:0.4em;
    font-weight:800;
    font-size:1.05em;
    line-height:1;
    vertical-align:-0.05em;
  }
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt label.rkrk-opt-label--richtig::before{ content:"✓"; }
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt label.rkrk-opt-label--falsch::before{ content:"✗"; }
  .rkrk-fieldset.rkrk-antwort-locked .rkrk-opt label.rkrk-opt-label--verpasst::before{ content:"★"; }
  .rkrk-answer-color-legend{
    margin:10px 0 0;
    padding:0 2px;
    font-size:0.72rem;
    line-height:1.4;
    color:#64748b;
    text-align:left;
  }

  /* Ergebnis: Fragenliste */
  .rkrk-result-fragenliste{
    margin-top:1.25rem;
    padding-top:1rem;
    border-top:1px dashed var(--border);
    text-align:left;
    max-width:100%;
  }
  .rkrk-result-fragenliste-titel{
    margin:0 0 0.6rem;
    font-size:0.95rem;
    font-weight:700;
    color:#1e293b;
  }
  .rkrk-result-fragenliste-ul{
    margin:0;
    padding:0;
    list-style:none;
    font-size:0.88rem;
    line-height:1.45;
  }
  .rkrk-result-frage{
    margin:0 0 0.55rem;
    padding:0.45rem 0.5rem 0.45rem 0.65rem;
    border-radius:8px;
    border-left:4px solid #cbd5e1;
    background:#f8fafc;
  }
  .rkrk-result-frage--ok{
    border-left-color:#67b516;
    background:#f4faf0;
  }
  .rkrk-result-frage--bad{
    border-left-color:#fc2222;
    background:#fff1f2;
  }
  .rkrk-result-frage--partial{
    border-left-color:#f59e0b;
    background:#fffbeb;
  }
  .rkrk-result-frage-badge{
    display:inline-block;
    min-width:3.2rem;
    margin-right:0.35rem;
    padding:0.1rem 0.35rem;
    border-radius:4px;
    font-size:0.72rem;
    font-weight:700;
    vertical-align:top;
    color:#fff;
  }
  .rkrk-result-frage--ok .rkrk-result-frage-badge{ background:#67b516; color:#fff; }
  .rkrk-result-frage--partial .rkrk-result-frage-badge{ background:#f59e0b; color:#fff; }
  .rkrk-result-frage--bad .rkrk-result-frage-badge{ background:#fc2222; color:#fff; }
  .rkrk-result-frage-nr{ font-weight:600; color:#475569; }
  .rkrk-result-frage-loesung{
    margin:0.45rem 0 0;
    font-size:0.84rem;
  }
  .rkrk-result-frage-loesung > summary{
    cursor:pointer;
    font-weight:600;
    color:var(--brand-dark,#48673A);
    padding:0.35rem 0;
    list-style:none;
    -webkit-tap-highlight-color:transparent;
  }
  .rkrk-result-frage-loesung > summary::-webkit-details-marker{ display:none; }
  .rkrk-result-frage-loesung > summary::after{
    content:" ▾";
    font-size:0.8em;
    opacity:0.7;
  }
  .rkrk-result-frage-loesung[open] > summary::after{ content:" ▴"; }
  .rkrk-result-frage-loesung-body{
    margin:0.35rem 0 0;
    padding:0.5rem 0.65rem;
    border-radius:8px;
    background:#fff;
    border:1px solid var(--border);
    line-height:1.45;
  }
  .rkrk-result-frage-loesung-body p{ margin:0 0 0.4rem; }
  .rkrk-result-frage-loesung-body p:last-child{ margin-bottom:0; }

  /* Ergebnis: Gesamtzusammenfassung (über Fragenliste) */
  .rkrk-result-gesamt{
    margin:0 0 1.1rem;
    padding:0.9rem 1rem;
    border-radius:10px;
    background:#f8fafc;
    border:1px solid var(--border);
    text-align:left;
    font-size:0.92rem;
    line-height:1.55;
    color:#1e293b;
  }
  .rkrk-result-gesamt-zeile{ margin:0 0 0.45rem; }
  .rkrk-result-gesamt-zeile:last-of-type{ margin-bottom:0; }
  .rkrk-result-gesamt-status{
    margin:0.75rem 0 0;
    padding:0.5rem 0.75rem;
    border-radius:8px;
    font-weight:700;
    font-size:0.95rem;
    display:inline-block;
  }
  .rkrk-result-gesamt-status--ok{ background:#67b516; color:#fff; border:1px solid #67b516; }
  .rkrk-result-gesamt-status--bad{ background:#fc2222; color:#fff; border:1px solid #fc2222; }

  /* Name + drone */
  .rkrk-user{ display:grid; gap:10px; margin-top:12px; }
  .rkrk-field label{ display:block; margin-bottom:6px; font-weight:700; }
  .rkrk-field input, .rkrk-field select{
    width:100%; box-sizing:border-box; padding:11px 12px;
    border-radius:10px; border:1px solid var(--border);
    font-size:16px; background:#fff;
  }

  /* Quiz-Übersicht */
  .rkrk-quiz-list{ list-style:none; margin:0; padding:0; display:grid; gap:16px; }
  .rkrk-quiz-list-item{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:14px 16px;
    box-shadow:var(--shadow);
  }
  .rkrk-quiz-list-head{ margin-bottom:10px; line-height:1.45; }
  .rkrk-quiz-list-head a{ color:var(--brand-dark,#48673A); font-weight:700; }
  .rkrk-quiz-list-links{ font-size:0.92em; margin-left:6px; opacity:.9; }
  .rkrk-quiz-list-thumb{
    display:block;
    max-width:100%;
    width:min(600px,100%);
    height:auto;
    max-height:200px;
    object-fit:cover;
    border-radius:12px;
    margin:0 0 10px;
    border:1px solid var(--border);
  }
  .rkrk-quiz-list-desc{ margin:0; line-height:1.5; color:var(--text); }

  /* Navigation buttons */
  .rkrk-nav{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:stretch;
    justify-content:center;
    margin-top:16px;
  }
  .rkrk-nav > span{
    flex:0 1 auto;
    min-width:120px;
    display:flex;
    align-items:stretch;
  }
  .rkrk-nav button{
    width:100%;
    box-sizing:border-box;
    min-height:42px;
    padding:10px 14px;
    font-size:15px;
    font-weight:600;
    line-height:1.25;
    border-radius:8px;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    text-align:center;
  }
  #prev_button, #next_button{ flex:0 1 140px; }
  #change_button{ flex:1 1 200px; max-width:320px; order:2; display:none; }
  #submit_button{ flex:1 1 240px; max-width:420px; order:3; }
  #prev_button{ order:1; }
  #next_button{ order:4; }
  .rkrk-nav #prev, .rkrk-nav #next, .rkrk-nav #change_answer{
    border:1px solid var(--border);
    color:var(--brand-dark,#48673A);
    background:#fff;
    box-shadow:0 1px 2px rgba(0,0,0,0.04);
  }
  .rkrk-nav #prev:hover, .rkrk-nav #next:hover, .rkrk-nav #change_answer:hover{
    background:var(--kpi-bg,#f8fbf7);
    border-color:var(--brand,#98B489);
  }
  .rkrk-submit-btn{
    border:1px solid var(--brand-dark, #48673A);
    color:#fff;
    font-weight:700;
    background:linear-gradient(180deg, var(--brand,#98B489) 0%, var(--brand-dark,#48673A) 100%);
    box-shadow:0 2px 6px rgba(72,103,58,0.28);
    letter-spacing:0.01em;
  }
  .rkrk-submit-btn:hover{
    background:var(--brand-dark, #48673A);
    border-color:var(--brand-dark, #48673A);
    box-shadow:0 3px 8px rgba(72,103,58,0.35);
  }
  .rkrk-submit-btn:active{
    transform:translateY(1px);
    box-shadow:0 1px 3px rgba(72,103,58,0.2);
  }

  /* Solution */
  .rkrk-solution{ margin-top:14px; padding-top:12px; border-top:1px dashed var(--border); }

  /* Footer */
  .rkrk-footer{ margin-top:14px; font-size:13px; opacity:.85; }
  .rkrk-footer a{ color:#2a5b2a; text-decoration:none; }
  .rkrk-footer a:hover{ text-decoration:underline; }
  .rkrk-footer-report{
    margin:0 0 12px;
    display:flex;
    justify-content:center;
  }
  .rkrk-footer-btn--report{
    padding:12px 22px;
    font-size:15px;
    font-weight:700;
    letter-spacing:0.01em;
    color:#fff !important;
    background:var(--brand, #4F6A3C);
    border-color:var(--brand-dark, #48673A);
    box-shadow:0 2px 6px rgba(72,103,58,0.28);
  }
  .rkrk-footer-btn--report:hover{
    background:var(--brand-dark, #48673A);
    border-color:var(--brand-dark, #48673A);
    color:#fff !important;
    box-shadow:0 3px 8px rgba(72,103,58,0.35);
    text-decoration:none !important;
  }
  .rkrk-footer-links{
    margin-top:10px;
    display:flex;
    gap:10px;
    justify-content:center;
    flex-wrap:wrap;
  }
  .rkrk-footer-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:8px 12px;
    border-radius:8px;
    border:1px solid var(--toolbar-btn-bd);
    background:#fff;
    color:var(--toolbar-text) !important;
    text-decoration:none !important;
    font-size:13px;
    line-height:1.2;
    transition:background .15s,border-color .15s,box-shadow .15s;
  }
  .rkrk-footer-btn:hover{
    background:var(--toolbar-hover-bg);
    border-color:var(--toolbar-hover-bd);
    box-shadow:0 1px 3px rgba(45,64,36,.08);
    text-decoration:none !important;
  }
  .rkrk-footer-btn[hidden]{ display:none !important; }

  /*footnote*/
	sup a {  text-decoration: none;  font-size: 0.75em;  vertical-align: super; }

	img {
		border-radius : 5px 15% 10px;	
	}
	
	.lernimagelarge {
		//align:center;
		height:500px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimage {
		//align:center;
		height:300px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagesmall {
		//align:center;
		height:200px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimageverysmall {
		//align:center;
		height:100px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagefixwidth {
		//align:center;
		width:600px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagefixheight {
		//align:center;
		height:200px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagefixlargeheight {
		//align:center;
		height:500px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lernimagesmallfixwidth {
		//align:center;
		width:450px;
		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		//boder-radius: 10px;
	}

	.lerncaption {
		//align: middle;
		font-style: italic;
	}

    .formula {
        font-family: "Courier New", monospace;
        background: #f4f4f4;
        padding: 4px 6px;
        border-radius: 4px;
        display: inline-block;
    }

	.key {
	  background: #f0f8ff;
	  padding: 4px 8px;
	  display: inline-block;
	  margin: 5px 0;
	}

   .keywords {
	  font-style: italic;
	  color: #666;
	  margin-bottom: 1rem;
	}
	.highlight {
	  background-color: #e6f7ff;
	  border-left: 4px solid #1890ff;
	  padding: 0.5rem 1rem;
	  margin: 1rem 0;
	}
	ul.checklist {
	  list-style: none;
	  padding-left: 0;
	}
	ul.checklist li::before {
	  content: "✅ ";
	  color: green;
	}

</style>
<!-- TIMER balken -->
	<style>
	#timer-container,#quiztimer-container  {
		width: 100%;
		height: 20px;
		background: #ddd;
		border-radius: 4px;
		overflow: hidden;
	  }

	#timer-bar,#quiztimer-bar  {
		width: 100%;
		height: 100%;
		background: #00c853;
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		color: white;              /* Textfarbe */
		font-weight: bold;
		font-size: 14px;
		text-shadow: 1px 1px 2px black; /* bessere Lesbarkeit */
	}

	#timer-text,#quiztimer-text {
		position: absolute;
		z-index: 2;                /* Text über der Farbe */
		pointer-events: none;      /* Klicks gehen durch */
	}
	</style>

<!-- tabellen, rahmen -->
<style>
/* Always align all cells to the top */
.two-gray-cols td,
.two-gray-cols th,
.one-gray-col td,
.one-gray-col th {
    vertical-align: top;
}
.lightgreen-box {
    background-color: #e8f8e8; /* sehr helles Grün */
    border: 2px solid #8ac78a; /* dezentes Grün für den Rahmen */
    padding: 1rem 1.2rem;
    border-radius: 6px;
    margin: 1rem 0;
}

.lightred-box {
    background-color: #fdeaea; /* sehr helles Rot */
    border: 2px solid #e08a8a; /* dezentes, warmes Rot für den Rahmen */
    padding: 1rem 1.2rem;
    border-radius: 6px;
    margin: 1rem 0;
}

.lightred-box h3,
.lightgreen-box h3 {
    margin-top: 0;
    font-weight: bold;
}

.lightred-box p {
    margin: 0.4rem 0 1rem 0;
    font-weight: bold;
    font-size: 120%;
	color: #b85a5a; /* dezentes, etwas dunklers Rot für den Rahmen */
}

.lightgreen-box p {
    margin: 0.4rem 0 1rem 0;
}

/* Zwei graue Spalten */
.two-gray-cols td:nth-child(-n+2),
.two-gray-cols th:nth-child(-n+2),
.two-gray-cols tr:first-child th {
    background-color: #f0f0f0;
    font-weight: bold;
}

/* Sicherstellen: zweite Spalte ist wirklich fett */
.two-gray-cols td:nth-child(2),
.two-gray-cols th:nth-child(2) {
    font-weight: normal;
}

/* Eine graue Spalte */
.one-gray-col td:nth-child(1),
.one-gray-col th:nth-child(1),
.one-gray-col tr:first-child th {
    background-color: #f0f0f0;
    font-weight: bold;

</style>

<style>
.instruktor-only {
    display: none;
    color: #555;
    font-style: italic;
}
</style>
<?php
if (function_exists('rkrk_assess_theme_rkrs_layer_link')) {
	rkrk_assess_theme_rkrs_layer_link();
}
?>


<?php
}

	$urladder = "";
	// RKRS-Zweig von headerpage endet nach </head> ohne <body>; Layout 2023 öffnet <body> bereits — kein zweites <body>.
	$dgaNeedBodyOpen = !empty($onRKRS);
	if ($dgaNeedBodyOpen && !$paperFormEarly) {
		if (isset($_GET["TEST"])) {
			echo "<body>";
			$urladder = "&TEST";
		} else {
			echo "<body oncontextmenu='return false;'>";
		}
	} elseif (isset($_GET["TEST"])) {
		$urladder = "&TEST";
	}

	$rkSetting = isset($GLOBALS["AddURL"]) ? (string)$GLOBALS["AddURL"] : "";
	if ($rkSetting !== "") {
		if ($urladder !== "") {
			$urladder .= "&" . $rkSetting;
		} else {
			$urladder = $rkSetting;
		}
	}
	if ($dgaQuizModus === "pruefung") {
		if ($urladder !== "") {
			$urladder .= "&QuizModus=pruefung";
		} else {
			$urladder = "QuizModus=pruefung";
		}
	}

	if (isset($_GET["quiz"])) {
		$set_quiz = $_GET["quiz"];
	} elseif (isset($_POST["quiz"])) {
		$set_quiz = $_POST["quiz"];
	} elseif (isset($_POST["Quiz"])) {
		// Formularfeld heisst «Quiz» (POST nach Abschluss ohne Query-String)
		$set_quiz = $_POST["Quiz"];
	} else	{
		$set_quiz = "";
	}	

	if (isset($_GET["mode"])) {
		$mode = $_GET["mode"];
	} else	{
		$mode = "";
	}	

	if (isset($_GET["sortiert"])) {
		$sorted = true;
	} else	{
		$sorted = false;
	}	

	if (isset($_POST["AnzFragen"])) {
		$AnzFragen = $_POST["AnzFragen"];
	} else	{
		$AnzFragen = 0;
	}	

	if (isset($_POST["RichtigBeantwortet"])) {
		$FragenRichtig = $_POST["RichtigBeantwortet"];
	} else	{
		$FragenRichtig = 0;
	}	
	if (isset($_POST["PunkteErreicht"])) {
		$PunkteErreicht = $_POST["PunkteErreicht"];
	} else {
		$PunkteErreicht = 0;
	}
	if (isset($_POST["PunkteMax"])) {
		$PunkteMax = $_POST["PunkteMax"];
	} else {
		$PunkteMax = 0;
	}

	if (isset($_POST["Erfuellt"])) {
		$Erfuellt = $_POST["Erfuellt"];
	} else	{
		$Erfuellt = 0;
	}	

	if (isset($_POST["Status"])) {
		$Status = $_POST["Status"];
	} elseif (isset($_GET["Status"])) {
		$Status = $_GET["Status"];
	} else {
		$Status = "";
	}
	$dgaReportDownloadHref = "";
	$dgaReportDownloadLabel = "PDF-Report";

	$dgaQuizModus = "";
	$__dgaQuizModusRaw = "";
	if (isset($_GET["QuizModus"])) {
		$__dgaQuizModusRaw = trim((string)$_GET["QuizModus"]);
	} elseif (isset($_POST["QuizModus"])) {
		$__dgaQuizModusRaw = trim((string)$_POST["QuizModus"]);
	}
	$dgaQuizModusLog = function_exists("rkrk_assess_quiz_modus_normalize")
		? rkrk_assess_quiz_modus_normalize($__dgaQuizModusRaw)
		: (strcasecmp($__dgaQuizModusRaw, "pruefung") === 0 || strcasecmp($__dgaQuizModusRaw, "prüfung") === 0
			? "pruefung"
			: (strcasecmp($__dgaQuizModusRaw, "quiz") === 0 ? "quiz" : ""));
	if (strcasecmp($__dgaQuizModusRaw, "pruefung") === 0 || strcasecmp($__dgaQuizModusRaw, "prüfung") === 0) {
		$dgaQuizModus = "pruefung";
	} else {
		$dgaQuizModus = "";
	}

	// Nach Quiz-Abschluss (POST): pro Frage Text + richtig/falsch (JSON vom Client)
	$quizErgebnisFragen = null;
	if ((string)$Status === "resultat" && isset($_POST["QuizErgebnisFragen"])) {
		$__qefRaw = trim((string)$_POST["QuizErgebnisFragen"]);
		if ($__qefRaw !== "") {
			$__jsonFlags = 0;
			if (defined("JSON_INVALID_UTF8_IGNORE")) {
				$__jsonFlags |= (int) constant("JSON_INVALID_UTF8_IGNORE");
			}
			$__qefDec = json_decode($__qefRaw, true, 512, $__jsonFlags);
			if (is_array($__qefDec)) {
				$quizErgebnisFragen = array();
				foreach ($__qefDec as $__qefRow) {
					if (!is_array($__qefRow)) {
						continue;
					}
					$__opts = $__qefRow["loesungsauswahl"] ?? ($__qefRow["Loesungsauswahl"] ?? null);
					if (!is_array($__opts)) {
						$__optsRaw = trim((string) ($__opts ?? ""));
						$__opts = $__optsRaw !== "" ? array_map("trim", explode("|", $__optsRaw)) : array();
					}
					$quizErgebnisFragen[] = array(
						"nr" => isset($__qefRow["nr"]) ? (int)$__qefRow["nr"] : 0,
						"qid" => isset($__qefRow["qid"]) ? trim((string)$__qefRow["qid"]) : (isset($__qefRow["QID"]) ? trim((string)$__qefRow["QID"]) : ""),
						"frage" => isset($__qefRow["frage"]) ? (string)$__qefRow["frage"] : "",
						"richtig" => !empty($__qefRow["richtig"]),
						"antwort" => isset($__qefRow["antwort"]) ? (string)$__qefRow["antwort"] : "",
						"loesung" => isset($__qefRow["loesung"]) ? (string)$__qefRow["loesung"] : "",
						"loesungstext" => isset($__qefRow["loesungstext"]) ? (string)$__qefRow["loesungstext"] : "",
						"antwortText" => isset($__qefRow["antwortText"]) ? (string)$__qefRow["antwortText"] : (isset($__qefRow["antworttext"]) ? (string)$__qefRow["antworttext"] : ""),
						"antwortLabel" => isset($__qefRow["antwortLabel"]) ? (string)$__qefRow["antwortLabel"] : "",
						"loesungLabel" => isset($__qefRow["loesungLabel"]) ? (string)$__qefRow["loesungLabel"] : "",
						"loesungsauswahl" => array_values($__opts),
						"status" => isset($__qefRow["status"]) ? (string)$__qefRow["status"] : "",
						"punkte" => isset($__qefRow["punkte"]) ? (float)$__qefRow["punkte"] : 0,
						"punkteMax" => isset($__qefRow["punkteMax"]) ? (float)$__qefRow["punkteMax"] : 0,
					);
				}
			}
		}
	}

	if (isset($_GET["Schulung"])) {
		$Schulung = "Schulung";
		if ($urladder<>"") {
			$urladder = $urladder . "&Schulung";
		} else {
			$urladder = "Schulung";
		}
	}	
	elseif (isset($_POST["Schulung"])) {
		$Schulung = $_POST["Schulung"];
	} else {
		$Schulung = "";
	}

	/* PersPick=on|off: Namens-Vorschläge aus pilotenliste.json (datalist) ein- oder ausschalten */
	$dgaPersPickRaw = "";
	if (isset($_GET["PersPick"])) {
		$dgaPersPickRaw = trim((string)$_GET["PersPick"]);
	} elseif (isset($_POST["PersPick"])) {
		$dgaPersPickRaw = trim((string)$_POST["PersPick"]);
	}
	$dgaPersPickNorm = strtolower($dgaPersPickRaw);
	$dgaPersPickEnabled = true;
	$dgaPersPickKnown = false;
	if ($dgaPersPickRaw !== "") {
		if (in_array($dgaPersPickNorm, array("off", "0", "false", "no", "aus"), true)) {
			$dgaPersPickEnabled = false;
			$dgaPersPickKnown = true;
		} elseif (in_array($dgaPersPickNorm, array("on", "1", "true", "yes", "ein", "an"), true)) {
			$dgaPersPickEnabled = true;
			$dgaPersPickKnown = true;
		}
		if ($dgaPersPickKnown) {
			$ppParam = $dgaPersPickEnabled ? "PersPick=on" : "PersPick=off";
			if ($urladder !== "") {
				$urladder .= "&" . $ppParam;
			} else {
				$urladder = $ppParam;
			}
		}
	}

	if (function_exists("rkrk_assess_urladder_merge_carry")) {
		$urladder = rkrk_assess_urladder_merge_carry($urladder);
	}

	// RKRSPilotID zur Pilotenliste nachtragen, falls beim Aufruf von Quiz/Prüfung/Schulung vorhanden aber in der Liste fehlend.
	if (function_exists("rkrk_assess_personel_upsert_from_url_params")) {
		$__dgaUpsert = array();
		foreach (array_merge($_GET, $_POST) as $__uk => $__uv) {
			if (is_scalar($__uv)) {
				$__dgaUpsert[(string)$__uk] = (string)$__uv;
			}
		}
		rkrk_assess_personel_upsert_from_url_params($__dgaUpsert, null);
		unset($__dgaUpsert, $__uk, $__uv);
	}

	$ohneLerntextInteractive = !empty($_GET["ohne_lerntext"]);
	$ohneBilderInteractive = !empty($_GET["ohne_bilder"]);

	if (array_key_exists("Name", $_POST)) {
		$Name = trim((string)$_POST["Name"]);
	} else {
		$Name = "";
		foreach (array("Name", "Pilot", "Teilnehmer", "participant", "user") as $__gk) {
			if (isset($_GET[$__gk]) && trim((string)$_GET[$__gk]) !== "") {
				$Name = trim((string)$_GET[$__gk]);
				break;
			}
		}
		if ($Name === "" && function_exists("rkrk_assess_request_pilot_id_param") && function_exists("rkrk_assess_personel_find_by_pilot_id")) {
			$__pid0 = rkrk_assess_request_pilot_id_param();
			if ($__pid0 !== "") {
				$__hit0 = rkrk_assess_personel_find_by_pilot_id($__pid0);
				if ($__hit0 !== null && isset($__hit0["name"])) {
					$Name = trim((string)$__hit0["name"]);
				}
			}
		}
	}

	$dgaPrefillPilotId = "";
	if (array_key_exists("PilotID", $_POST)) {
		$dgaPrefillPilotId = trim((string)$_POST["PilotID"]);
	}
	if ($dgaPrefillPilotId === "") {
		$dgaPrefillPilotId = function_exists("rkrk_assess_request_pilot_id_param") ? rkrk_assess_request_pilot_id_param() : "";
	}
	if ($dgaPrefillPilotId === "" && $Name !== "" && $dgaPersPickEnabled && function_exists("rkrk_assess_personel_flat_for_select")) {
		foreach (rkrk_assess_personel_flat_for_select() as $__e) {
			if (function_exists("rkrk_assess_personel_names_equal") && rkrk_assess_personel_names_equal((string)$__e["name"], (string)$Name)) {
				$dgaPrefillPilotId = trim((string)($__e["rkrs_pilot_id"] ?? ""));
				if ($dgaPrefillPilotId === "" && isset($__e["row"]) && is_array($__e["row"]) && function_exists("rkrk_assess_personel_row_effective_rkrs_id")) {
					$dgaPrefillPilotId = rkrk_assess_personel_row_effective_rkrs_id($__e["row"]);
				}
				break;
			}
		}
	}

	if (isset($_POST["Quiz"])) {
		$Quiz = $_POST["Quiz"];
	} else	{
		$Quiz = "";
	}	

	if (isset($_POST["Drone"])) {
		$Drone = $_POST["Drone"];
	} elseif (isset($_POST["Drohne"])) {
		$Drone = $_POST["Drohne"];
	} elseif (isset($_GET["Drone"])) {
		$Drone = $_GET["Drone"];
	} elseif (isset($_GET["Drohne"])) {
		$Drone = $_GET["Drohne"];
	} else {
		$Drone = "";   // noch nichts gewählt
	}

	$dgaExamCode = function_exists("rkrk_assess_exam_read_code_from_request")
		? rkrk_assess_exam_read_code_from_request()
		: "";
	$dgaExamSession = null;
	$dgaExamCompleted = false;
	$dgaExamContextMismatch = false;
	$dgaExamSkipResultLog = false;
	if ($dgaExamCode !== "") {
		$__examUrlBit = "EXAM=" . rawurlencode($dgaExamCode);
		if ($urladder !== "") {
			if (strpos($urladder, "EXAM=") === false) {
				$urladder .= "&" . $__examUrlBit;
			}
		} else {
			$urladder = $__examUrlBit;
		}
		if (function_exists("rkrk_assess_exam_load")) {
			$dgaExamSession = rkrk_assess_exam_load($dgaExamCode, (string) $Name);
		}
		$dgaExamCompleted = function_exists("rkrk_assess_exam_is_completed")
			? rkrk_assess_exam_is_completed($dgaExamSession, (string) $Name)
			: false;
		if ($dgaExamCompleted && is_array($dgaExamSession)) {
			$__examDone = rkrk_assess_exam_apply_completed_globals($dgaExamSession, (string) $Name);
			if (trim((string) $Name) === "" && trim((string) ($__examDone["name"] ?? "")) !== "") {
				$Name = (string) $__examDone["name"];
			}
			if (trim((string) $Drone) === "" && trim((string) ($__examDone["drone"] ?? "")) !== "") {
				$Drone = (string) $__examDone["drone"];
			}
			if ((string) $Status !== "resultat" || $_SERVER["REQUEST_METHOD"] !== "POST") {
				$Status = "resultat";
			}
			if ($__examDone["quiz_ergebnis_fragen"] !== null) {
				$quizErgebnisFragen = $__examDone["quiz_ergebnis_fragen"];
			}
			if (trim((string) ($__examDone["anz_fragen"] ?? "")) !== "") {
				$AnzFragen = (string) $__examDone["anz_fragen"];
			}
			if (trim((string) ($__examDone["fragen_richtig"] ?? "")) !== "") {
				$FragenRichtig = (string) $__examDone["fragen_richtig"];
			}
			if (trim((string) ($__examDone["punkte_erreicht"] ?? "")) !== "") {
				$PunkteErreicht = (string) $__examDone["punkte_erreicht"];
			}
			if (trim((string) ($__examDone["punkte_max"] ?? "")) !== "") {
				$PunkteMax = (string) $__examDone["punkte_max"];
			}
			if (trim((string) ($__examDone["erfuellt"] ?? "")) !== "") {
				$Erfuellt = (string) $__examDone["erfuellt"];
			}
			$dgaExamSkipResultLog = true;
		}
	}

	//echo "AnzFragen: " . $AnzFragen . " Richtig: " . $FragenRichtig . " Status: " . $Status . " Quiz: " . $Quiz . "<br>";
	if ((string) $Status === "resultat" && $dgaExamCode !== "" && !$dgaExamCompleted && $_SERVER["REQUEST_METHOD"] === "POST") {
		if (function_exists("rkrk_assess_exam_mark_completed")) {
			rkrk_assess_exam_mark_completed($dgaExamCode, array(
				"anz_fragen" => (string) $AnzFragen,
				"fragen_richtig" => (string) $FragenRichtig,
				"punkte_erreicht" => (string) $PunkteErreicht,
				"punkte_max" => (string) $PunkteMax,
				"erfuellt" => (string) $Erfuellt,
				"name" => (string) $Name,
				"drone" => (string) $Drone,
				"quiz_ergebnis_fragen" => is_array($quizErgebnisFragen) ? $quizErgebnisFragen : array(),
			), (string) $Name);
			$dgaExamSession = rkrk_assess_exam_load($dgaExamCode, (string) $Name);
			$dgaExamCompleted = rkrk_assess_exam_is_completed($dgaExamSession, (string) $Name);
		}
	}
	if ($Status=="resultat" && !$dgaExamSkipResultLog) {
		$quizLogColFalsch = 'FalscheAntworten';
		$quizLogColPilotId = 'PilotID';
		$quizLogColPilotIdAlt = (function_exists("rkrk_assess_pers_pick_id_label") ? rkrk_assess_pers_pick_id_label() : "PilotenID");
		$__dgaResultsDir = '';
		if (isset($QuizLocResults) && (string)$QuizLocResults !== '') {
			$__dgaResultsDir = rtrim(str_replace("\\", "/", (string)$QuizLocResults), "/");
			if ($__dgaResultsDir !== '' && !is_dir($__dgaResultsDir)) {
				@mkdir($__dgaResultsDir, 0755, true);
			}
		}
		if ($__dgaResultsDir !== '' && function_exists("rkrk_assess_quiz_results_load_rows")) {
			$data = rkrk_assess_quiz_results_load_rows($__dgaResultsDir);
			if (!is_array($data)) {
				$data = array();
			}
			$i = count($data);
			if (count($data) > 0) {
				$__firstKey = array_key_first($data);
				$__hdr = (is_array($data[$__firstKey])) ? array_keys($data[$__firstKey]) : array();
				if (!in_array($quizLogColFalsch, $__hdr, true)) {
					$__hdr[] = $quizLogColFalsch;
				}
				if (!in_array($quizLogColPilotId, $__hdr, true)) {
					$__hdr[] = $quizLogColPilotId;
				}
				foreach (array("PunkteErreicht", "PunkteMax", "PunkteText") as $__ptsCol) {
					if (!in_array($__ptsCol, $__hdr, true)) {
						$__hdr[] = $__ptsCol;
					}
				}
				if (
					(string)$quizLogColPilotIdAlt !== ""
					&& strcasecmp((string)$quizLogColPilotIdAlt, (string)$quizLogColPilotId) !== 0
					&& !in_array($quizLogColPilotIdAlt, $__hdr, true)
				) {
					$__hdr[] = $quizLogColPilotIdAlt;
				}
				foreach ($data as $__dk => $__row) {
					if (!is_array($__row)) {
						continue;
					}
					$__new = array();
					foreach ($__hdr as $__col) {
						$__new[$__col] = array_key_exists($__col, $__row) ? $__row[$__col] : '';
					}
					$data[$__dk] = $__new;
				}
			}
			$falschPost = isset($_POST["FalscheAntworten"]) ? (string)$_POST["FalscheAntworten"] : "";
			$falschPost = preg_replace('/\r\n|\r|\n/', ' ', $falschPost);
			$falschPost = str_replace(";", ",", $falschPost);
			if (trim($falschPost) === "" && is_array($quizErgebnisFragen) && count($quizErgebnisFragen) > 0) {
				$__falschParts = array();
				foreach ($quizErgebnisFragen as $__qfRow) {
					if (!is_array($__qfRow) || !empty($__qfRow["richtig"])) {
						continue;
					}
					$__qid = "";
					if (isset($__qfRow["qid"])) {
						$__qid = trim((string)$__qfRow["qid"]);
					} elseif (isset($__qfRow["QID"])) {
						$__qid = trim((string)$__qfRow["QID"]);
					}
					$__ft = isset($__qfRow["frage"]) ? trim(preg_replace('/\s+/', ' ', (string)$__qfRow["frage"])) : "";
					if ($__ft !== "") {
						$__falschParts[] = ($__qid !== "" ? ("Frage-ID " . $__qid) : ("Frage-ID —")) . ": " . $__ft;
					}
				}
				if (count($__falschParts) > 0) {
					$falschPost = implode(" || ", $__falschParts);
					$falschPost = str_replace(";", ",", $falschPost);
				}
			}
			$data[$i]["Name"] = $Name;
			$PilotIDPost = isset($_POST["PilotID"]) ? trim((string)$_POST["PilotID"]) : "";
			if ($PilotIDPost === "") {
				$PilotIDPost = trim((string)$dgaPrefillPilotId);
			}
			if ($PilotIDPost === "" && $dgaPersPickEnabled && $Name !== "" && function_exists("rkrk_assess_personel_flat_for_select")) {
				foreach (rkrk_assess_personel_flat_for_select() as $__e) {
					if (function_exists("rkrk_assess_personel_names_equal") && rkrk_assess_personel_names_equal((string)$__e["name"], (string)$Name)) {
						$PilotIDPost = trim((string)($__e["rkrs_pilot_id"] ?? ""));
						if ($PilotIDPost === "" && isset($__e["row"]) && is_array($__e["row"]) && function_exists("rkrk_assess_personel_row_effective_rkrs_id")) {
							$PilotIDPost = rkrk_assess_personel_row_effective_rkrs_id($__e["row"]);
						}
						break;
					}
				}
			}
			$data[$i][$quizLogColPilotId] = $PilotIDPost;
			if (
				(string)$quizLogColPilotIdAlt !== ""
				&& strcasecmp((string)$quizLogColPilotIdAlt, (string)$quizLogColPilotId) !== 0
			) {
				$data[$i][$quizLogColPilotIdAlt] = $PilotIDPost;
			}
			$data[$i]["Quiz"] = $set_quiz;
			$data[$i]["Zeit"] = date("d.m.Y H:i");
			$data[$i]["AnzFragen"] = $AnzFragen;
			$data[$i]["Richtig"] = $FragenRichtig;
			$__punkteErreichtNum = is_numeric((string)$PunkteErreicht) ? (float)$PunkteErreicht : 0.0;
			$__punkteMaxNum = is_numeric((string)$PunkteMax) ? (float)$PunkteMax : 0.0;
			if ($__punkteErreichtNum <= 0.0 && is_numeric((string)$FragenRichtig)) {
				$__punkteErreichtNum = (float)((int)$FragenRichtig);
			}
			if ($__punkteMaxNum <= 0 && is_numeric((string)$AnzFragen)) {
				$__punkteMaxNum = (float)((int)$AnzFragen);
			}
			$data[$i]["PunkteErreicht"] = rtrim(rtrim(number_format($__punkteErreichtNum, 4, '.', ''), '0'), '.');
			if ($data[$i]["PunkteErreicht"] === "") {
				$data[$i]["PunkteErreicht"] = "0";
			}
			$data[$i]["PunkteMax"] = rtrim(rtrim(number_format($__punkteMaxNum, 4, '.', ''), '0'), '.');
			if ($data[$i]["PunkteMax"] === "") {
				$data[$i]["PunkteMax"] = "0";
			}
			$data[$i]["PunkteText"] = $data[$i]["PunkteErreicht"] . "/" . $data[$i]["PunkteMax"];
			$data[$i]["Erfuellungsfaktor"] = $Erfuellt;
			$__erf = is_numeric($Erfuellt) ? (float)$Erfuellt : 0.0;
			$__passedByPoints = ($__punkteMaxNum > 0.0) && (($__punkteErreichtNum / $__punkteMaxNum) >= $__erf);
			if ($__passedByPoints) {
				$data[$i]["Erfuellung"] = "Ja";
			} else {
				$data[$i]["Erfuellung"] = "Nein";
			}
			$data[$i][$quizLogColFalsch] = $falschPost;
			$data[$i]["QuizModus"] = (string) $dgaQuizModusLog;
			$data[$i]["EXAM"] = (string) $dgaExamCode;
			$data[$i]["Schulung"] = (string) $Schulung;
			rkrk_assess_quiz_results_save_rows($__dgaResultsDir, $data);
		}
		if (
			$dgaExamCode === ""
			&& $_SERVER["REQUEST_METHOD"] === "POST"
			&& function_exists("rkrk_assess_exam_save_completed_standalone")
			&& function_exists("rkrk_assess_exam_generate_auto_code")
		) {
			$__autoCtx = function_exists("rkrk_assess_exam_context_from_request")
				? rkrk_assess_exam_context_from_request(
					(string) $set_quiz,
					isset($setting) ? (string) $setting : "",
					(string) $Schulung,
					(string) $dgaQuizModus,
					(string) $Name,
					(string) $Drone
				)
				: array(
					"quiz_key" => (string) $set_quiz,
					"setting" => isset($setting) ? (string) $setting : "",
					"schulung" => (string) $Schulung,
					"quiz_modus" => (string) $dgaQuizModus,
					"name" => (string) $Name,
					"drone" => (string) $Drone,
				);
			if (isset($__dgaDriveLocation) && trim((string) $__dgaDriveLocation) !== "") {
				$__autoCtx["drive_location"] = (string) $__dgaDriveLocation;
			}
			if (isset($PilotIDPost) && trim((string) $PilotIDPost) !== "") {
				$__autoCtx["rkrs_pilot_id"] = (string) $PilotIDPost;
			}
			$__autoCode = rkrk_assess_exam_generate_auto_code($__autoCtx);
			if ($__autoCode !== "") {
				rkrk_assess_exam_save_completed_standalone($__autoCode, $__autoCtx, array(
					"anz_fragen" => (string) $AnzFragen,
					"fragen_richtig" => (string) $FragenRichtig,
					"punkte_erreicht" => (string) $PunkteErreicht,
					"punkte_max" => (string) $PunkteMax,
					"erfuellt" => (string) $Erfuellt,
					"name" => (string) $Name,
					"drone" => (string) $Drone,
					"quiz_ergebnis_fragen" => is_array($quizErgebnisFragen) ? $quizErgebnisFragen : array(),
					"auto_generated" => true,
				));
				$dgaExamCode = $__autoCode;
				if (
					isset($data) && is_array($data) && isset($i) && isset($data[$i]) && is_array($data[$i])
					&& isset($__dgaResultsDir) && (string) $__dgaResultsDir !== ""
					&& function_exists("rkrk_assess_quiz_results_save_rows")
				) {
					$data[$i]["EXAM"] = (string) $__autoCode;
					rkrk_assess_quiz_results_save_rows($__dgaResultsDir, $data);
				}
			}
		}
		if (function_exists("rkrk_assess_quiz_progress_delete_best_effort")) {
			rkrk_assess_quiz_progress_delete_best_effort(array(
				"quiz_key" => (string) $set_quiz,
				"name" => (string) $Name,
				"drone" => (string) $Drone,
				"setting" => isset($setting) ? (string) $setting : "",
				"schulung" => (string) $Schulung,
				"quiz_modus" => (string) $dgaQuizModus,
				"rkrs_pilot_id" => isset($PilotIDPost) ? (string) $PilotIDPost : (string) $dgaPrefillPilotId,
			));
		}
		if (function_exists("rkrk_assess_save_digital_result_report") && $_SERVER["REQUEST_METHOD"] === "POST") {
			$__repExam = trim((string) $dgaExamCode);
			if ($__repExam === "" && isset($__autoCode)) {
				$__repExam = trim((string) $__autoCode);
			}
			$__repBestanden = "";
			if (isset($data) && is_array($data) && isset($i) && isset($data[$i]["Erfuellung"])) {
				$__repBestanden = (string) $data[$i]["Erfuellung"];
			}
			$__repSave = rkrk_assess_save_digital_result_report(array(
				"quiz" => (string) $set_quiz,
				"quiz_key" => (string) $set_quiz,
				"quiz_title" => (string) $set_quiz,
				"name" => (string) $Name,
				"drone" => (string) $Drone,
				"exam" => $__repExam,
				"quiz_modus" => (string) $dgaQuizModus,
				"rkrs_pilot_id" => isset($PilotIDPost) ? (string) $PilotIDPost : (string) $dgaPrefillPilotId,
				"PilotID" => isset($PilotIDPost) ? (string) $PilotIDPost : (string) $dgaPrefillPilotId,
				"zeit" => date("d.m.Y H:i"),
				"punkte_erreicht" => (string) $PunkteErreicht,
				"punkte_max" => (string) $PunkteMax,
				"anz_fragen" => (string) $AnzFragen,
				"fragen_richtig" => (string) $FragenRichtig,
				"bestanden" => $__repBestanden,
				"erfuellt" => (string) $Erfuellt,
			), is_array($quizErgebnisFragen) ? $quizErgebnisFragen : array());
			if (function_exists("rkrk_assess_report_download_href")) {
				$dgaReportDownloadHref = rkrk_assess_report_download_href($__repSave, "", "assess");
				$dgaReportDownloadLabel = (isset($__repSave["format"]) && (string) $__repSave["format"] === "html")
					? "HTML-Report"
					: "PDF-Report";
			}
		}
	}

	// Bestehenden Report-Link anzeigen (z. B. EXAM erneut öffnen / Ergebnis ohne neuen POST)
	if (
		(string) $Status === "resultat"
		&& trim((string) ($dgaReportDownloadHref ?? "")) === ""
		&& function_exists("rkrk_assess_report_find_existing_href")
	) {
		$__repLookupExam = trim((string) $dgaExamCode);
		if ($__repLookupExam === "" && isset($__autoCode)) {
			$__repLookupExam = trim((string) $__autoCode);
		}
		$dgaReportDownloadHref = rkrk_assess_report_find_existing_href(
			$__repLookupExam,
			(string) $Name,
			(string) $set_quiz
		);
		if ($dgaReportDownloadHref !== "") {
			$dgaReportDownloadLabel = (stripos($dgaReportDownloadHref, "t=html") !== false) ? "HTML-Report" : "PDF-Report";
		}
	}

	// ===========================================
	// Definitionen
	// ===========================================
	
	// Öffentliche Quiz-Defaults — RKRS*-Kanal aus URL oder CI nicht auf PUB kappen (RKRS-Anzeige / Audienz würden sonst leer wirken).
	$__digHasRkrsGet = false;
	$__g = isset($_GET) && is_array($_GET) ? $_GET : array();
	foreach (array("RKRS", "RKRSplus", "RKRSprev") as $__digRk) {
		if (array_key_exists($__digRk, $__g)) {
			$__digHasRkrsGet = true;
			break;
		}
	}
	if (!rkrk_assess_ci_rkrs_mode() && !$__digHasRkrsGet && empty($onRKRS)) {
		$logo = "rkrk";
		$copyright = "drohnenrehkitzrettung.ch";
		$setting = "PUB";
	}
	
	// iframe-URL für Schulungstext: PHP-Hülle mit Theme-CSS (direkte .html-Datei hat relative Stylesheet-Pfade falsch)
	$_dgaLearnScript = str_replace("\\", "/", (string)($_SERVER["SCRIPT_NAME"] ?? ""));
	$_dgaLearnIframePrefix = ($_dgaLearnScript !== "")
		? (rtrim(dirname($_dgaLearnScript), "/") . "/rkrk_digitalassess_learn.php")
		: "/rkrk_digitalassess_learn.php";
	unset($_dgaLearnScript);

	// Browser (img src, JS): absolute Web-URL — $QuizIconLoc aus common ist ein Dateisystem-Pfad (unter test/assess oft kaputt).
	$dgaQuizIconLocWeb = function_exists("rkrk_assess_quiz_data_assess_web_prefix")
		? rkrk_assess_quiz_data_assess_web_prefix() . "images/"
		: "/rkropen/rkrk/data/assess/images/";

	$PicutreStyle ="border-radius: 20px;  border: none; object-fit: cover";
	$ButtonStyle ="border-radius: 3px;  border: none;  color: white;  background: var(--brand); padding: 10px;  text-align: center;  text-decoration: none;  display: inline-block;  font-size: 16px;  margin: 4px 2px;  cursor: pointer";
	$ButtonStyleRed ="border-radius: 3px;  border: none;  color: white;  background: var(--danger); padding: 10px;  text-align: center;";  
	$ButtonStyleGrey ="border-radius: 3px;  border: none;  color: white;  background: #E8E8E8; padding: 10px;  text-align: center;";  
	
	$PicWidth = '600';
	$PicHeight = '400';
	$PicHeightSmall = '200';
	$ColWidth=100;

	// ========================================
	// Read csv data
	// ========================================
	
	//pre_R($QuizSelection);
	$csv_desc= ";";
	$assessmentListeCsvPath = $QuizLoc . "AssessmentListe.csv";
	$_assessmentListeParse = csv_to_array_ascII($assessmentListeCsvPath, $csv_desc);
	$QuizList = is_array($_assessmentListeParse) ? $_assessmentListeParse : array();
	unset($_assessmentListeParse);
	if (function_exists("rkrk_assess_fixup_csv_assoc_rows")) {
		$QuizList = rkrk_assess_fixup_csv_assoc_rows($QuizList);
	}

	$digitalReadOnly = !empty($_GET["read_only"]) || !empty($_GET["Lesemodus"]);

	// URL-Parameter quiz an Key bzw. Titel aus AssessmentListe.csv anbinden.
	if ($set_quiz !== "" && is_array($QuizList) && function_exists("rkrk_dga_resolve_quiz_param_to_key")) {
		$set_quiz = rkrk_dga_resolve_quiz_param_to_key($QuizList, (string) $set_quiz);
	}

	if ($digitalReadOnly && $set_quiz === "") {
		include_once(__DIR__ . "/rkrk_digitalassess_read_render.php");
		rkrk_dga_rh_render_hub($QuizList, $setting, $digitalassessSchulungMode, $onRKRS, $QuizLoc, $isAdminMode, $isInstrMode);
		exit;
	}

	//pre_R($QuizList);

	//-----------------------------------------
	//Fragenauswahl
	//-----------------------------------------
	// Nicht freigegeben: Zugriff verweigern (Admin: inkl. Aktiv=Nein; Instruktor: gesperrte aber aktive Vorschau).
	if ($set_quiz !== "" && !$isAdminMode) {
		foreach ($QuizList as $__digQl) {
			if (!is_array($__digQl) || !isset($__digQl["Key"])) {
				continue;
			}
			$__qk = trim((string)$__digQl["Key"]);
			if ($__qk !== (string)$set_quiz && strcasecmp($__qk, (string)$set_quiz) !== 0) {
				continue;
			}
			if (($__digQl["Aktiv"] ?? "") !== "Ja") {
				if (!$paperFormEarly) {
					$set_quiz = "";
					break;
				}
			}
			if (!$isInstrMode) {
				$__rel = ((($__digQl["Locked"] ?? "") === "Nein"));
				if (!$__rel) {
					$__hubAccess = function_exists("rkrk_assess_hash_gate_portal_quiz_hub_access")
						&& rkrk_assess_hash_gate_portal_quiz_hub_access($__qk);
					if (!$__hubAccess) {
						$set_quiz = "";
					}
				}
			}
			break;
		}
	}
	//echo "QuizSet: " . $set_quiz . "<br>";

	$i=0;
	//$QuizSelection = Array();
	//pre_R($QuizList);
	foreach($QuizList as $key => $ql) {
		//pre_r($ql);
		//echo "Set: " . $setting . " Aud: " . $ql["Audienz"] . "<br>";
		//echo $i . " Quiz: " . $key . " " . $ql["Key"] . "<br>";
		$__kRow = trim((string)($ql["Key"] ?? ""));
		$__sq = (string)$set_quiz;
		$keyEqReq = ($__sq !== "" && ($__kRow === $__sq || strcasecmp($__kRow, $__sq) === 0));
		$__aktivJa = (($ql["Aktiv"] ?? "") == "Ja");
		$__released = $__aktivJa && (($ql["Locked"] ?? "") == "Nein");
		$__audOk = rkrk_assess_setting_sees_audience((string)$setting, (string)($ql["Audienz"] ?? ""));
		$__testOk = ($setting == "TEST");
		$__unlocked = (($ql["Locked"] ?? "") == "Nein");
		$__isQuizRow = (strtolower(trim((string)($ql["Quiz"] ?? ""))) === "ja");
		$__isSchulRow = (strtolower(trim((string)($ql["Schulung"] ?? ""))) === "ja");
		$__modeOk = $digitalassessSchulungMode ? $__isSchulRow : $__isQuizRow;
		if ($isAdminMode) {
			$rowOpen = $__modeOk;
		} elseif ($isInstrMode) {
			$rowOpen = $__aktivJa && ($__released || $__audOk || $__testOk
				|| ($digitalReadOnly && $isInstrMode)
				|| ($isInstrMode && $keyEqReq))
				|| ($paperFormEarly && $keyEqReq && $__unlocked && ($__audOk || $__testOk));
		} else {
			$__hubAccess = ($keyEqReq && function_exists("rkrk_assess_hash_gate_portal_quiz_hub_access")
				&& rkrk_assess_hash_gate_portal_quiz_hub_access($__kRow));
			$rowOpen = ($__released && ($__audOk || $__testOk))
				|| ($paperFormEarly && $keyEqReq && $__unlocked && ($__audOk || $__testOk))
				|| $__hubAccess;
		}
		if ($rowOpen) {
			if (
				($__sq !== "" && ($__kRow === $__sq || strcasecmp($__kRow, $__sq) === 0))
				|| ($__sq === "" && $__kRow === "RKR")
			) {
				$Questionair = $QuizList[$key];
				$QSelection = csv_to_array_ascII($QuizLoc.$ql["FragenDatei"],$csv_desc);
				if (function_exists("rkrk_assess_fixup_csv_assoc_rows") && is_array($QSelection)) {
					$QSelection = rkrk_assess_fixup_csv_assoc_rows($QSelection);
				}
				//pre_r($QSelection);
				foreach ($QSelection as $key => $Q) {
					$QSelection [$key]["Loesungsauswahl"] = explode("|", $Q["Loesungsauswahl"]);
					foreach ($QSelection [$key]["Loesungsauswahl"] as $kkey => $s) {
						$QSelection [$key]["Loesungsauswahl"][$kkey] = $s;
					}
					
				if (file_exists($QuizLoc.$ql["LernDatei"]) and ($ql["LernDatei"]!="")) {
					$learnQs = rkrk_assess_merge_query_with_setting(array("quiz" => (string)$ql["Key"]));
					$LearningContent = rkrk_assess_append_setting_href($_dgaLearnIframePrefix . "?" . http_build_query($learnQs));
				} else {
					$LearningContent = "";
				}

				}	
			}	
			$i++;
		}	
	}
	
	if (!isset($QSelection) || !is_array($QSelection)) {
		$QSelection = array();
	}

	if (is_array($QSelection) && function_exists("rkrk_assess_fixup_question_image_paths")) {
		rkrk_assess_fixup_question_image_paths($QSelection);
	}
	$__dgaDriveLocation = "";
	if (isset($Questionair) && is_array($Questionair) && function_exists("rkrk_assess_clean_drive_location_rel")) {
		$__dgaDriveLocation = rkrk_assess_clean_drive_location_rel((string) ($Questionair["DriveLocation"] ?? ""));
	}
	if (is_array($QSelection) && function_exists("rkrk_assess_strip_question_image_paths")) {
		rkrk_assess_strip_question_image_paths($QSelection, $__dgaDriveLocation);
	}

	if (count($QSelection)==0) {
		$NoQuiz = 1;
	} else {
		$NoQuiz = 0;
	}

	if ($paperFormEarly) {
		require_once __DIR__ . "/rkrk_assess_paper_form_inc.php";
		include_once(__DIR__ . "/rkrk_digitalassess_read_render.php");
		if ($set_quiz !== "" && isset($Questionair) && is_array($Questionair) && $NoQuiz === 0) {
			$paperIdPf = function_exists("rkrk_dga_paper_form_id_from_request")
				? rkrk_dga_paper_form_id_from_request($_GET)
				: "";
			if ($paperIdPf === "" && function_exists("rkrk_dga_paper_form_id_allocate") && function_exists("rkrk_dga_paper_form_redirect_with_id")) {
				rkrk_dga_paper_form_redirect_with_id(rkrk_dga_paper_form_id_allocate((string) $set_quiz));
			}
			$questionsRaw = (isset($QSelection) && is_array($QSelection) && count($QSelection) > 0)
				? $QSelection
				: (function_exists("rkrk_dga_rh_read_questions")
					? rkrk_dga_rh_read_questions($QuizLoc, (string) $Questionair["FragenDatei"])
					: array());
			$settingPf = function_exists("rkrk_dga_quiz_setting_for_selection")
				? rkrk_dga_quiz_setting_for_selection(isset($setting) ? (string) $setting : "RKRS")
				: (isset($setting) ? (string) $setting : "RKRS");
			$dronePf = function_exists("rkrk_dga_paper_quiz_drone_from_request")
				? rkrk_dga_paper_quiz_drone_from_request($_GET)
				: (isset($_GET["drone"]) ? trim((string) $_GET["drone"]) : "Alle");
			$paperMode = isset($_GET["paper_mode"]) ? trim((string) $_GET["paper_mode"]) : "sample";
			$buildOpts = array(
				"sample" => ($paperMode !== "pool"),
				"full_pool" => ($paperMode === "pool"),
			);
			if ($paperIdPf !== "" && function_exists("rkrk_dga_paper_form_id_to_seed")) {
				$buildOpts["seed"] = (string) rkrk_dga_paper_form_id_to_seed($paperIdPf);
			} elseif (isset($_GET["paper_seed"]) && trim((string) $_GET["paper_seed"]) !== "") {
				$buildOpts["seed"] = trim((string) $_GET["paper_seed"]);
			}
			$builtPf = function_exists("rkrk_dga_build_paper_quiz_questions")
				? rkrk_dga_build_paper_quiz_questions($questionsRaw, $Questionair, $settingPf, $dronePf, $buildOpts)
				: array("questions" => $questionsRaw, "pick_n" => 0, "pool_count" => 0, "mode" => "legacy", "setting" => $settingPf, "drone" => $dronePf);
			$questionsPf = $builtPf["questions"];
			if (is_array($questionsPf) && isset($Questionair) && is_array($Questionair) && function_exists("rkrk_assess_apply_answer_option_shuffle")) {
				$seedShuffle = isset($buildOpts["seed"]) && is_numeric((string) $buildOpts["seed"]);
				if ($seedShuffle) {
					mt_srand(((int) $buildOpts["seed"]) ^ 0x5A5A5A5A);
				}
				rkrk_assess_apply_answer_option_shuffle($questionsPf, $Questionair, $seedShuffle);
				if ($seedShuffle) {
					mt_srand();
				}
			}
			if (is_array($questionsPf) && function_exists("rkrk_assess_normalize_question_antwort_fields")) {
				rkrk_assess_normalize_question_antwort_fields($questionsPf);
			}
			$dronePf = isset($builtPf["drone"]) ? (string) $builtPf["drone"] : $dronePf;
			$paperBackQs = rkrk_assess_merge_query_with_setting(array("quiz" => (string) $set_quiz, "read_only" => "1"));
			$paperBack = rkrk_assess_append_setting_href("rkrk_digitalassess.php?" . http_build_query($paperBackQs));
			$logoPf = "";
			if (isset($dgaQuizIconLocWeb) && isset($logopic)) {
				$logoPf = (string) $dgaQuizIconLocWeb . (string) $logopic;
			}
			$imgBasePf = function_exists("rkrk_dga_rh_web_image_base") ? rkrk_dga_rh_web_image_base() : "";
			$paperShowImages = function_exists("rkrk_dga_paper_quiz_images_wanted")
				? rkrk_dga_paper_quiz_images_wanted($_GET)
				: (!isset($_GET["paper_images"]) || (string) $_GET["paper_images"] === "1");
			$imgTogglePf = function_exists("rkrk_dga_paper_quiz_image_toggle_hrefs")
				? rkrk_dga_paper_quiz_image_toggle_hrefs($_GET)
				: array("with_href" => "", "without_href" => "", "mode" => "without");
			$modeTogglePf = function_exists("rkrk_dga_paper_quiz_mode_toggle_hrefs")
				? rkrk_dga_paper_quiz_mode_toggle_hrefs($_GET)
				: array("exam_href" => "", "solution_href" => "", "mode" => "exam");
			$paperDroneOpts = function_exists("rkrk_dga_paper_quiz_drone_options_from_questions")
				? rkrk_dga_paper_quiz_drone_options_from_questions($questionsRaw)
				: array("Alle");
			if ($dronePf !== "" && $dronePf !== "Alle" && function_exists("rkrk_assess_normalize_drone")) {
				$dnPf = rkrk_assess_normalize_drone($dronePf);
				$foundPf = false;
				foreach ($paperDroneOpts as $pdo) {
					if (rkrk_assess_normalize_drone($pdo) === $dnPf) {
						$foundPf = true;
						break;
					}
				}
				if (!$foundPf) {
					$paperDroneOpts[] = $dronePf;
					sort($paperDroneOpts, SORT_STRING);
				}
			}
			$optsPf = array(
				"only_active" => false,
				"show_solutions" => !empty($_GET["show_solutions"]),
				"back_url" => $paperBack,
				"logo_src" => $logoPf,
				"selection_meta" => $builtPf,
				"quiz_image_base" => $imgBasePf,
				"paper_drive_questions" => $questionsRaw,
				"paper_show_images" => $paperShowImages,
				"paper_quiz_image_toggle" => $imgTogglePf,
				"paper_quiz_mode_toggle" => $modeTogglePf,
				"paper_drone_filter" => $dronePf,
				"paper_form_id" => $paperIdPf,
			);
			$newBogenQs = $_GET;
			unset($newBogenQs["paper_id"], $newBogenQs["paper_seed"], $newBogenQs["pick_n"]);
			$newBogenQs["paper"] = "1";
			$optsPf["paper_new_href"] = rkrk_assess_append_setting_href("rkrk_digitalassess.php?" . http_build_query(rkrk_assess_merge_query_with_setting($newBogenQs)));
			if (count($paperDroneOpts) > 1 && function_exists("rkrk_dga_paper_quiz_drone_picker_items")) {
				$optsPf["paper_quiz_drone_picker"] = array(
					"items" => rkrk_dga_paper_quiz_drone_picker_items($_GET, $paperDroneOpts),
				);
			}
			rkrk_paper_render_quiz_form($Questionair, $questionsPf, $optsPf);
		}
		rkrk_paper_emit_page_start("Quiz", "Papierformular", array());
		echo "<p>Assessment nicht gefunden oder nicht freigegeben.</p>";
		rkrk_paper_emit_page_end(array());
	}

	if ($digitalReadOnly && $set_quiz !== "") {
		include_once(__DIR__ . "/rkrk_digitalassess_read_render.php");
		if ($NoQuiz === 1 || !isset($Questionair)) {
			echo "<div class='rkrk-wrap'><div class='rkrk-card' style='max-width:720px;margin:14px auto;'><p>Assessment nicht gefunden oder nicht freigegeben.</p></div></div>";
			exit;
		}
		$onlyActiveRo = isset($_GET["only_active"]) && (string)$_GET["only_active"] === "1";
		$printViewRo = isset($_GET["print_view"]) && (string)$_GET["print_view"] === "1";
		$questionsRo = rkrk_dga_rh_read_questions($QuizLoc, (string)$Questionair["FragenDatei"]);
		$learnFsRo = "";
		$lfRo = rkrk_dga_rh_clean_rel_file((string)$Questionair["LernDatei"], "html");
		if ($lfRo !== "" && is_readable($QuizLoc . $lfRo)) {
			$learnFsRo = $QuizLoc . $lfRo;
		}
		$schulCtxRo = ($digitalassessSchulungMode || strtolower(trim((string)($Questionair["Schulung"] ?? ""))) === "ja");
		$showLearnRo = $schulCtxRo && empty($_GET["ohne_lerntext"]);
		rkrk_dga_rh_echo_styles($printViewRo);
		echo "<div class='dr-wrap'>";
		rkrk_dga_rh_render_detail($Questionair, $questionsRo, $onlyActiveRo, $printViewRo, $learnFsRo, $showLearnRo && ($learnFsRo !== ""), $schulCtxRo);
		echo "</div>";
		exit;
	}

	$DroneTypes = [];

	foreach ($QSelection as $q) {
		$d = trim($q["Drone"]);
		if ($d !== "" && $d !== "Alle" && rkrk_assess_question_is_quiz_live($q)) {
			$DroneTypes[$d] = true;
		}
	}

	$DroneTypes = array_keys($DroneTypes);
	$HasDroneTypes = count($DroneTypes) > 0;
	$DroneFilterActive = $HasDroneTypes && $Drone !== "" && $Drone !== "Alle";

	// Normalize drone strings (vor Fragen-Pool / Sortierung benoetigt)
	if (!function_exists("normalizeDrone")) {
		function normalizeDrone($str) {
			if ($str === null) {
				return "";
			}
			$str = (string)$str;
			$str = urldecode($str);
			$str = str_replace("\xC2\xA0", " ", $str);
			$str = preg_replace("/[\x{200B}-\x{200D}\x{FEFF}]/u", "", $str);
			$str = preg_replace("/\s+/", " ", $str);
			return trim($str);
		}
	}

	if (!function_exists("rkrk_dga_quiz_prio_weight")) {
		/** Gewicht fuer Zufallsziehung: hohe Prioritaet haeufiger, niedrige bleibt moeglich. */
		function rkrk_dga_quiz_prio_weight($prio) {
			$p = (int)$prio;
			if ($p < 0) {
				$p = 0;
			}
			return (float)(($p + 1) * ($p + 1));
		}
	}

	if (!function_exists("rkrk_dga_weighted_sample_keys")) {
		/**
		 * Zieht bis zu $k verschiedene Keys aus $poolItems (je Eintrag key + prio) gewichtet nach Prioritaet.
		 *
		 * @param array<int, array{key: string|int, prio: int}> $poolItems
		 * @return list<string>
		 */
		function rkrk_dga_weighted_sample_keys(array $poolItems, $k) {
			$pool = array_values($poolItems);
			$out = array();
			$k = (int)$k;
			if ($k < 1 || count($pool) < 1) {
				return $out;
			}
			$k = min($k, count($pool));
			$maxRand = (int)mt_getrandmax();
			if ($maxRand < 1) {
				$maxRand = 1;
			}
			for ($t = 0; $t < $k; $t++) {
				$sum = 0.0;
				foreach ($pool as $it) {
					$sum += rkrk_dga_quiz_prio_weight(isset($it["prio"]) ? $it["prio"] : 0);
				}
				if ($sum <= 0) {
					$ix = (int)floor((mt_rand(0, $maxRand) / $maxRand) * count($pool));
					if ($ix >= count($pool)) {
						$ix = count($pool) - 1;
					}
				} else {
					$r = (mt_rand(0, $maxRand) / $maxRand) * $sum;
					$acc = 0.0;
					$ix = 0;
					for ($i = 0; $i < count($pool); $i++) {
						$acc += rkrk_dga_quiz_prio_weight(isset($pool[$i]["prio"]) ? $pool[$i]["prio"] : 0);
						if ($r <= $acc) {
							$ix = $i;
							break;
						}
					}
				}
				$out[] = (string)$pool[$ix]["key"];
				array_splice($pool, $ix, 1);
			}
			return $out;
		}
	}


	if (!function_exists("rkrk_dga_build_stochastic_quiz_order")) {
		/**
		 * @param array<int, array{key: string|int, prio: int, bereich: string}> $eligible
		 * @return list<string>
		 */
		function rkrk_dga_build_stochastic_quiz_order(array $eligible, $pickN) {
			$pickN = (int)$pickN;
			if ($pickN < 1 || count($eligible) < 1) {
				return array();
			}
			$pickN = min($pickN, count($eligible));
			$byB = array();
			foreach ($eligible as $e) {
				$b = isset($e["bereich"]) ? (string)$e["bereich"] : "_";
				if (!isset($byB[$b])) {
					$byB[$b] = array();
				}
				$byB[$b][] = $e;
			}
			list($targets, $groups) = rkrk_dga_rr_targets_per_bereich($byB, $pickN);
			$selectedByG = array();
			$taken = array();
			foreach ($groups as $g) {
				$want = isset($targets[$g]) ? (int)$targets[$g] : 0;
				$want = min($want, count($byB[$g]));
				$keys = rkrk_dga_weighted_sample_keys($byB[$g], $want);
				$selectedByG[$g] = $keys;
				foreach ($keys as $kk) {
					$taken[(string)$kk] = true;
				}
			}
			while (count($taken) < $pickN) {
				$rest = array();
				foreach ($eligible as $e) {
					$kk = (string)$e["key"];
					if (!isset($taken[$kk])) {
						$rest[] = $e;
					}
				}
				if (count($rest) < 1) {
					break;
				}
				$need = $pickN - count($taken);
				$more = rkrk_dga_weighted_sample_keys($rest, $need);
				foreach ($more as $kk) {
					$taken[(string)$kk] = true;
				}
				if (!isset($selectedByG["_rest"])) {
					$selectedByG["_rest"] = array();
				}
				foreach ($more as $kk) {
					$selectedByG["_rest"][] = (string)$kk;
				}
				break;
			}
			$queues = array();
			foreach ($groups as $g) {
				if (!empty($selectedByG[$g])) {
					$q = array_values($selectedByG[$g]);
					shuffle($q);
					$queues[$g] = $q;
				}
			}
			$order = array();
			$guard = 0;
			while (count($order) < $pickN && $guard < $pickN * 4) {
				$guard++;
				$step = false;
				foreach ($groups as $g) {
					if (count($order) >= $pickN) {
						break 2;
					}
					if (!empty($queues[$g])) {
						$order[] = (string)array_shift($queues[$g]);
						$step = true;
					}
				}
				if (!$step) {
					break;
				}
			}
			if (!empty($selectedByG["_rest"])) {
				$tail = array_values($selectedByG["_rest"]);
				shuffle($tail);
				foreach ($tail as $kk) {
					if (count($order) >= $pickN) {
						break;
					}
					if (!in_array((string)$kk, $order, true)) {
						$order[] = (string)$kk;
					}
				}
			}
			return array_slice($order, 0, $pickN);
		}
	}

	//pre_r($DroneTypes);

	//-----------------------------------------
	//Sortiere die Fragen um
	//-----------------------------------------

	$Order = Array();

	//Define Order of Questions (Prioritaet: höhere Zahl zuerst; leer/ungültig = 0)
	$csvSeq = 0;
	foreach ($QSelection as $key => $Q) {
		$Order[$key]["key"] = $key;
		$Order[$key]["random"] = rand(0,100);
		$Order[$key]["csv_seq"] = $csvSeq++;
		$prRaw = isset($Q["Prioritaet"]) ? trim((string)$Q["Prioritaet"]) : "";
		$Order[$key]["prio"] = ($prRaw !== "" && is_numeric($prRaw)) ? (int)$prRaw : 0;
	}

	if (($mode!="Korrektur") and !$sorted) {
		uasort(
			$Order,
			static function ($a, $b) {
				$pa = is_array($a) && isset($a["prio"]) ? (int)$a["prio"] : 0;
				$pb = is_array($b) && isset($b["prio"]) ? (int)$b["prio"] : 0;
				if ($pa !== $pb) {
					return $pb <=> $pa;
				}
				$va = is_array($a) && array_key_exists("random", $a) ? $a["random"] : null;
				$vb = is_array($b) && array_key_exists("random", $b) ? $b["random"] : null;
				return $va <=> $vb;
			}
		);
		//pre_r($Order);
	} else {
		uasort(
			$Order,
			static function ($a, $b) {
				$pa = is_array($a) && isset($a["prio"]) ? (int)$a["prio"] : 0;
				$pb = is_array($b) && isset($b["prio"]) ? (int)$b["prio"] : 0;
				if ($pa !== $pb) {
					return $pb <=> $pa;
				}
				$sa = is_array($a) && isset($a["csv_seq"]) ? (int)$a["csv_seq"] : 0;
				$sb = is_array($b) && isset($b["csv_seq"]) ? (int)$b["csv_seq"] : 0;
				return $sa <=> $sb;
			}
		);
	}
	
	//pre_R($Order);
	
	$Drone = normalizeDrone($Drone);
	// echo "Drone (normalisiert): [" . $Drone . "]<br>";

	if ($HasDroneTypes && $Drone !== "" && $Drone !== "Alle") {
		if (!in_array($Drone, $DroneTypes, true)) {
			$__dgaDroneMatch = null;
			foreach ($DroneTypes as $__dt) {
				if (strcasecmp((string)$__dt, $Drone) === 0) {
					$__dgaDroneMatch = $__dt;
					break;
				}
			}
			$Drone = $__dgaDroneMatch !== null ? (string)$__dgaDroneMatch : "";
		}
	}

	// Pool aller fuer das Quiz zulaessigen Fragen (Prioritaet + Bereich fuer Zusammenstellung)
	$eligible = array();
	foreach ($QSelection as $csvKey => $qRow) {
		if (!is_array($qRow)) {
			continue;
		}
		$drone = normalizeDrone(isset($qRow["Drone"]) ? $qRow["Drone"] : "");
		if ($Drone === "" || $Drone === "Alle") {
			$droneMatch = ($drone === "" || $drone === "Alle");
		} else {
			$droneMatch = ($drone === "" || $drone === "Alle" || $drone === $Drone);
		}
		if (
			rkrk_assess_question_is_quiz_live($qRow) &&
			(
				rkrk_assess_setting_sees_audience((string)$setting, (string)($qRow["Audienz"] ?? "")) ||
				($setting == "TEST")
			) &&
			$droneMatch
		) {
			$prRaw = isset($qRow["Prioritaet"]) ? trim((string)$qRow["Prioritaet"]) : "";
			$prio = ($prRaw !== "" && is_numeric($prRaw)) ? (int)$prRaw : 0;
			$br = trim((string)($qRow["Bereich"] ?? ""));
			if ($br === "") {
				$br = "_";
			}
			$eligible[] = array(
				"key" => $csvKey,
				"prio" => $prio,
				"bereich" => $br,
			);
		}
	}

	$__anzW = isset($Questionair["AnzQuestions"]) ? (int)$Questionair["AnzQuestions"] : 0;
	if ($__anzW < 1) {
		$__anzW = count($eligible);
	}
	$pickN = min(count($eligible), max(0, $__anzW));

	$Questions = Array();
	$__dgaQuestionsFromProgress = null;
	$__dgaQuestionsFromExam = null;
	$__dgaExamDeferQuestions = false;
	$__dgaExamCreateSession = false;
	// Direktstart (Status=Quiz): Exam-Katalog auch per GET anlegen, wenn Name (+ Drohne) schon in der URL stehen
	// — sonst leere Fragen-UI (Platzhalter), weil bisher nur POST Session erzeugt hat.
	$__dgaExamDirectStartReady = (
		(string) $Status === "Quiz"
		&& trim((string) $Name) !== ""
		&& (!$HasDroneTypes || (trim((string) $Drone) !== "" && trim((string) $Drone) !== "Alle"))
	);
	if ($dgaExamCode !== "" && is_array($dgaExamSession) && !$dgaExamCompleted) {
		$__examCtx = function_exists("rkrk_assess_exam_context_from_request")
			? rkrk_assess_exam_context_from_request(
				(string) $set_quiz,
				(string) $setting,
				(string) $Schulung,
				(string) $dgaQuizModus,
				(string) $Name,
				(string) $Drone
			)
			: array();
		if (
			function_exists("rkrk_assess_exam_matches_context")
			&& !rkrk_assess_exam_matches_context($dgaExamSession, $__examCtx)
		) {
			$dgaExamContextMismatch = true;
		}
	}
	if ($dgaExamCode !== "" && is_array($dgaExamSession) && !$dgaExamCompleted && !$dgaExamContextMismatch) {
		$__examHasCatalog = function_exists("rkrk_assess_exam_has_catalog")
			? rkrk_assess_exam_has_catalog($dgaExamSession, (string) $Name)
			: false;
		if ($__examHasCatalog && function_exists("rkrk_assess_exam_questions_from_session")) {
			$__dgaQuestionsFromExam = rkrk_assess_exam_questions_from_session($dgaExamSession, (string) $Name);
			$__examAttempt = function_exists("rkrk_assess_exam_get_attempt")
				? rkrk_assess_exam_get_attempt($dgaExamSession, (string) $Name)
				: null;
			if (trim((string) $Name) === "" && is_array($__examAttempt) && trim((string) ($__examAttempt["name"] ?? "")) !== "") {
				$Name = (string) $__examAttempt["name"];
			}
			if (trim((string) $Drone) === "" && is_array($__examAttempt) && trim((string) ($__examAttempt["drone"] ?? "")) !== "") {
				$Drone = (string) $__examAttempt["drone"];
			}
			if (
				!$dgaExamCompleted
				&& $__dgaQuestionsFromExam !== null
				&& count($__dgaQuestionsFromExam) > 0
				&& (string) $Status !== "resultat"
				&& trim((string) $Name) !== ""
				&& is_array($__examAttempt)
				&& strtolower(trim((string) ($__examAttempt["status"] ?? ""))) === "in_progress"
			) {
				$Status = "Quiz";
			}
		} elseif (!$dgaExamCompleted && $__dgaExamDirectStartReady) {
			if (!function_exists("rkrk_assess_exam_has_catalog") || !rkrk_assess_exam_has_catalog($dgaExamSession, (string) $Name)) {
				$__dgaExamCreateSession = true;
			}
		} elseif (!$dgaExamCompleted) {
			$__dgaExamDeferQuestions = true;
		}
	} elseif ($dgaExamCode !== "" && !$dgaExamCompleted && !$dgaExamContextMismatch && $__dgaExamDirectStartReady) {
		$__dgaExamCreateSession = true;
	} elseif ($dgaExamCode !== "" && !$dgaExamCompleted && !$dgaExamContextMismatch) {
		$__dgaExamDeferQuestions = true;
	}

	if (
		$dgaExamCode === ""
		&& $mode !== "Korrektur"
		&& !$sorted
		&& function_exists("rkrk_assess_quiz_progress_read_resume_cookie")
		&& function_exists("rkrk_assess_quiz_questions_from_progress_snapshots")
	) {
		$__dgaResumeCookie = rkrk_assess_quiz_progress_read_resume_cookie();
		if (is_array($__dgaResumeCookie)) {
			$__dgaResumeId = rkrk_assess_quiz_progress_normalize_identity(array_merge($__dgaResumeCookie, array(
				"quiz_key" => (string) ($__dgaResumeCookie["quiz_key"] ?? $set_quiz ?? ""),
				"setting" => (string) ($__dgaResumeCookie["setting"] ?? $setting ?? ""),
				"schulung" => (string) ($__dgaResumeCookie["schulung"] ?? $Schulung ?? ""),
				"quiz_modus" => (string) ($__dgaResumeCookie["quiz_modus"] ?? $dgaQuizModus ?? ""),
			)));
			$__dgaResumeCheckId = rkrk_assess_quiz_progress_normalize_identity(array(
				"quiz_key" => (string) $set_quiz,
				"name" => (string) $Name,
				"drone" => (string) $Drone,
				"setting" => (string) $setting,
				"schulung" => (string) $Schulung,
				"quiz_modus" => (string) $dgaQuizModus,
			));
			if (
				$__dgaResumeId["quiz_key"] !== ""
				&& $__dgaResumeId["name"] !== ""
				&& rkrk_assess_quiz_progress_quiz_keys_equal((string) $__dgaResumeId["quiz_key"], (string) $set_quiz)
				&& rkrk_assess_quiz_progress_names_equal((string) $__dgaResumeId["name"], (string) $Name)
				&& function_exists("rkrk_assess_quiz_progress_try_load_incomplete")
			) {
				$__dgaResumeProgress = rkrk_assess_quiz_progress_try_load_incomplete(
					$__dgaResumeCheckId,
					$HasDroneTypes
				);
				if (is_array($__dgaResumeProgress)) {
					$__dgaQuestionsFromProgress = rkrk_assess_quiz_questions_from_progress_snapshots($__dgaResumeProgress);
				}
			}
		}
	}

	if (is_array($__dgaQuestionsFromExam) && count($__dgaQuestionsFromExam) > 0) {
		$Questions = $__dgaQuestionsFromExam;
	} elseif (is_array($__dgaQuestionsFromProgress) && count($__dgaQuestionsFromProgress) > 0) {
		$Questions = $__dgaQuestionsFromProgress;
	} elseif ($__dgaExamDeferQuestions) {
		$Questions = array();
	} elseif (($mode == "Korrektur") || $sorted) {
		$i = 0;
		foreach ($Order as $key => $Q) {
			$drone = normalizeDrone($QSelection[$Q["key"]]["Drone"]);
			if ($Drone === "" || $Drone === "Alle") {
				$droneMatch = ($drone === "" || $drone === "Alle");
			} else {
				$droneMatch = ($drone === "" || $drone === "Alle" || $drone === $Drone);
			}
			if (
				rkrk_assess_question_is_quiz_live($QSelection[$Q["key"]]) &&
				(
					rkrk_assess_setting_sees_audience((string)$setting, (string)($QSelection[$Q["key"]]["Audienz"] ?? "")) ||
					($setting == "TEST")
				) &&
				$droneMatch
			) {
				$Questions[$i] = $QSelection[$Q["key"]];
				$Questions[$i]["QID"] = $Q["key"];
				$i++;
			}
		}
		if ($pickN > 0 && count($Questions) > $pickN) {
			$Questions = array_values(array_slice($Questions, 0, $pickN));
		}
	} else {
		if ($pickN > 0 && count($eligible) > 0) {
			$keysOrder = rkrk_dga_build_stochastic_quiz_order($eligible, $pickN);
			$i = 0;
			foreach ($keysOrder as $csvKey) {
				if (!isset($QSelection[$csvKey]) || !is_array($QSelection[$csvKey])) {
					continue;
				}
				$Questions[$i] = $QSelection[$csvKey];
				$Questions[$i]["QID"] = $csvKey;
				$i++;
			}
		}
	}
	if (
		$__dgaQuestionsFromProgress === null
		&& $__dgaQuestionsFromExam === null
		&& $mode !== "Korrektur"
		&& isset($Questionair)
		&& is_array($Questionair)
		&& is_array($Questions)
		&& function_exists("rkrk_assess_apply_answer_option_shuffle")
	) {
		rkrk_assess_apply_answer_option_shuffle($Questions, $Questionair);
	}
	if (is_array($Questions) && function_exists("rkrk_assess_normalize_question_antwort_fields")) {
		rkrk_assess_normalize_question_antwort_fields($Questions);
	}
	if (
		$__dgaExamCreateSession
		&& $dgaExamCode !== ""
		&& is_array($Questions)
		&& count($Questions) > 0
		&& function_exists("rkrk_assess_exam_create_session")
	) {
		$__examCtxCreate = rkrk_assess_exam_context_from_request(
			(string) $set_quiz,
			(string) $setting,
			(string) $Schulung,
			(string) $dgaQuizModus,
			(string) $Name,
			(string) $Drone
		);
		$__examCreated = rkrk_assess_exam_create_session(
			$dgaExamCode,
			$__examCtxCreate,
			array_values($Questions),
			isset($__dgaDriveLocation) ? (string) $__dgaDriveLocation : ""
		);
		if (is_array($__examCreated)) {
			$dgaExamSession = $__examCreated;
		}
	}
	if (is_array($Questions) && function_exists("rkrk_assess_strip_question_image_paths")) {
		rkrk_assess_strip_question_image_paths($Questions, $__dgaDriveLocation);
	}
	$__dgaQuestionFingerprint = "";
	if (is_array($Questions) && function_exists("rkrk_assess_quiz_progress_fingerprint_from_list")) {
		$__dgaQuestionFingerprint = rkrk_assess_quiz_progress_fingerprint_from_list($Questions);
	}
	$__dgaProgressApiUrl = "rkrk_digitalassess_quiz_progress.php";
	if (function_exists("rkrk_assess_append_setting_href")) {
		$__dgaProgressApiUrl = rkrk_assess_append_setting_href($__dgaProgressApiUrl);
	}
	//echo "Ql";
	//pre_r($QSelection[5]);
	//echo "Q";
	//pre_r($Questions[5]);
	//echo "Lerninhalt " . $LearningContent . "<br>";

	// ===========================================
	// Inhalt
	// ===========================================
	

	// ===========================================
	// Inhalt (responsive, gleiche IDs & JS-Hooks)
	// ===========================================

	echo "<div class='rkrk-wrap'>" . chr(10);

	if (function_exists("rkrk_assess_portal_nav_echo")) {
		ob_start();
		rkrk_assess_portal_nav_echo(array(
			"btn_class" => "ql-btn",
			"schulung_mode" => $digitalassessSchulungMode,
			"is_admin" => $isAdminMode,
			"is_instr" => $isInstrMode,
			"selected_quiz" => (string)$set_quiz,
		));
		$__rkrk_portal_out = ob_get_clean();
		if ($__rkrk_portal_out !== "") {
			echo "<div id='rkrk-portal-nav-slot' class='rkrk-portal-nav-slot'>" . $__rkrk_portal_out . "</div>" . chr(10);
		}
	}

	// Topbar
	echo "<header class='rkrk-topbar rkrk-card'>" . chr(10);
	echo "  <div class='rkrk-title'>" . chr(10);
	echo "    <h1><span id='quiz-titel'>QuizTitel</span></h1>" . chr(10);
	echo "    <span id='indrodution'></span>" . chr(10);
	echo "    <div class='quick-links' id='rkrk-topbar-quicklinks'>" . chr(10);
	echo "    </div>" . chr(10);
	echo "  </div>" . chr(10);
	echo "  <div class='rkrk-logo'>" . chr(10);
	if ($logo=="rkrs") {
		echo "<img src='".$dgaQuizIconLocWeb.$logopic."' id='logopic' name='logopic' width='247' height='80'>" . chr(10);
	} elseif ($logo=="rkrk") {
		echo "<img src='".$dgaQuizIconLocWeb.$logopic."' id='logopic' name='logopic' height='80'>" . chr(10);
	} else {
		echo "<img src='".$dgaQuizIconLocWeb."void.png' id='logopic' name='logopic' width='100'>" . chr(10);
	}
	echo "  </div>" . chr(10);
	echo "</header>" . chr(10);

	// Learning content
	echo "<section class='rkrk-card rkrk-learning'  id='rkrk-card'>" . chr(10);
	echo "  <span id='learning'></span>" . chr(10);
	echo "  <span id='submit_learn' hidden><br><br><button id='LernenAbschliessen' onclick='ShowNameFormAfterLearning()' style='".$ButtonStyle ."'>Lerninhalt abschliessen</button></span>" . chr(10);
	echo "</section>" . chr(10);

	// Main grid
	echo "<div class='rkrk-grid'>" . chr(10);

	// Sidebar / Resultate
	echo "<aside class='rkrk-card rkrk-sidebar'  id='rkrk-sidebar'>" . chr(10);
	echo "  <div class='rkrk-qnav-title' id='result-titel'>Fragen</div>" . chr(10);
	echo "  <div id='result' class='rkrk-qnav-grid' role='group' aria-label='Fragen'></div>" . chr(10);
	echo "</aside>" . chr(10);

	// Main content
	echo "<main class='rkrk-card rkrk-main' id='rkrk-main'>" . chr(10);
	//Teilnehmerinfo
	echo "    <span id='Teilnehmerinfo' hidden>Teilnehmerinfo</span>" . chr(10);

	// Timers + Question
	echo "<div class='rkrk-question'>" . chr(10);
	echo "  <div id='quiztimer-container' hidden><span id='quiztimer-text'>Deine Zeit läuft...</span><div id='quiztimer-bar'></div></div>" . chr(10);
	echo "  <p class='rkrk-qtitle'><span id='Frage_Titel'>Frage_Titel</span></p>" . chr(10);
	echo "  <div id='timer-container' hidden><span id='timer-text'>Deine Zeit läuft...</span><div id='timer-bar'></div></div>" . chr(10);
	echo "  <img src='".$dgaQuizIconLocWeb."void.png' id='frage-bild' name='frage-bild' alt='' width='".$PicWidth."' style='display:none;".$PicutreStyle."'>" . chr(10);
	echo "  <p class='rkrk-qtext'><span id='Frage'>Frage</span></p>" . chr(10);
	echo "</div>" . chr(10);

	// Form (Antworten + Hidden fields + Close submit)
	echo "<form method='post' action='". htmlspecialchars($_SERVER['REQUEST_URI']) . "' class='rkrk-form' style='text-align: center;' onsubmit=\"try{if(typeof rkrkDigiAssessRefreshResultHiddenFields==='function'){rkrkDigiAssessRefreshResultHiddenFields();}if(typeof rkrkDigiAssessSnapshotQuizForSubmit==='function'){rkrkDigiAssessSnapshotQuizForSubmit();}}catch(e){}\">" . chr(10);
	echo "  <span id='auswahl'>" . chr(10);
	echo "  <fieldset id=\"rkrk-choice-fieldset\" class='rkrk-fieldset'>" . chr(10);
	echo "    <legend>Deine Wahl<span id='mehrfach'></span>:</legend>" . chr(10);
	echo "    <div class='rkrk-options'>" . chr(10);

	// Ja/Nein
	echo "      <span id='a-ja' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='ja' name='response' value='Ja' />" . chr(10);
	echo "        <label for='ja'><b>Ja</b></label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-nein' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='nein' name='response' value='Nein' />" . chr(10);
	echo "        <label for='nein'><b>Nein</b></label>" . chr(10);
	echo "      </span>" . chr(10);

	// A-F
	echo "      <span id='a-a' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='A' name='response' value='A' />" . chr(10);
	echo "        <label id='labelA' for='A'>A</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-b' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='B' name='response' value='B' />" . chr(10);
	echo "        <label id='labelB' for='B'>B</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-c' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='C' name='response' value='C' />" . chr(10);
	echo "        <label id='labelC' for='C'>C</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-d' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='D' name='response' value='D' />" . chr(10);
	echo "        <label id='labelD' for='D'>D</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-e' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='E' name='response' value='E' />" . chr(10);
	echo "        <label id='labelE' for='E'>E</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-f' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='F' name='response' value='F' />" . chr(10);
	echo "        <label id='labelF' for='F'>F</label>" . chr(10);
	echo "      </span>" . chr(10);

	echo "    </div>" . chr(10);
	echo "    <p id=\"rkrk-answer-color-legend\" class=\"rkrk-answer-color-legend\" hidden>✓ richtig gewählt · ★ richtig, aber nicht gewählt · ✗ falsch gewählt</p>" . chr(10);
	echo "  </fieldset>" . chr(10);
	echo "  </span>" . chr(10);

	// User info (optional datalist: pilotenliste.json — nur bei PersPick=on bzw. ohne explizites PersPick=off)
	$dgaNameDatalistHtml = "";
	if ($dgaPersPickEnabled && function_exists("rkrk_assess_emit_digitalassess_name_datalist_html")) {
		$dgaNameDatalistHtml = rkrk_assess_emit_digitalassess_name_datalist_html();
	}
	$dgaNameInputExtra = "";
	if ($dgaPersPickEnabled && $dgaNameDatalistHtml !== "") {
		$dgaNameInputExtra = " list=\"rkrk-dga-quiz-names\" autocomplete=\"off\" placeholder=\"" . htmlspecialchars("Aus Personal auswählen oder manuell eintragen ...", ENT_QUOTES, "UTF-8") . "\"";
	}
	echo "  <div class='rkrk-user'>" . chr(10);
	echo $dgaNameDatalistHtml;
	echo "    <div class='rkrk-field'>" . chr(10);
	echo "      <label for='Name' id='Name-Label'>Dein Name:</label>" . chr(10);
	echo "      <input type='text' id='Name' name='Name' value='" . htmlspecialchars($Name, ENT_QUOTES, "UTF-8") . "'".$dgaNameInputExtra.">" . chr(10);
	$__dgaPilotIdMapJson = "null";
	if ($dgaPersPickEnabled && function_exists("rkrk_assess_personel_flat_for_select")) {
		$__flat = rkrk_assess_personel_flat_for_select();
		if (count($__flat) > 0) {
			$__map = array();
			foreach ($__flat as $__e) {
				if (!isset($__e["name"]) || trim((string)$__e["name"]) === "") {
					continue;
				}
				$__rk = trim((string)($__e["rkrs_pilot_id"] ?? ""));
				if ($__rk === "" && isset($__e["row"]) && is_array($__e["row"]) && function_exists("rkrk_assess_personel_row_effective_rkrs_id")) {
					$__rk = rkrk_assess_personel_row_effective_rkrs_id($__e["row"]);
				}
				if ($__rk === "") {
					continue;
				}
				$__map[(string)$__e["name"]] = $__rk;
			}
			if (count($__map) > 0) {
				$__dgaPilotIdMapJson = json_encode($__map, JSON_UNESCAPED_UNICODE);
			}
		}
	}
	if ($__dgaPilotIdMapJson !== "null" || $dgaPrefillPilotId !== "") {
		echo "      <input type=\"hidden\" id=\"PilotID\" name=\"PilotID\" value=\"" . htmlspecialchars($dgaPrefillPilotId, ENT_QUOTES, "UTF-8") . "\">" . chr(10);
	}
	echo "    </div>" . chr(10);

	// Drohnenauswahl: auch bei direktem Link (?Status=Quiz / «Direkt Quiz»), solange noch keine Drohne gewählt.
	$__dgaNeedDronePick = $HasDroneTypes && $Drone === "";
	if ($__dgaNeedDronePick) {
		echo "    <div class='rkrk-field' id='rkrk-drone-field'>" . chr(10);
		echo "      <label for='Drone' id='Drone-Label'>Deine Drohne:</label>" . chr(10);
		echo "      <select id='Drone' name='Drone'>" . chr(10);
		echo "        <option value=''>Bitte wählen…</option>" . chr(10);
		foreach ($DroneTypes as $d) {
			$sel = ($Drone === $d) ? " selected" : "";
			echo "        <option value='".htmlspecialchars($d,ENT_QUOTES)."'".$sel.">".htmlspecialchars($d)."</option>" . chr(10);
		}
		echo "      </select>" . chr(10);
		echo "    </div>" . chr(10);
	} elseif ($HasDroneTypes && $Drone !== "") {
		echo "<input type='hidden' id='Drone' name='Drone' value='" . htmlspecialchars($Drone, ENT_QUOTES, "UTF-8") . "'>" . chr(10);
	} elseif (!$HasDroneTypes) {
		echo "<input type='hidden' id='Drone' name='Drone' value='Alle'>" . chr(10);
	}

	echo "  </div>" . chr(10);

	// Hidden fields
	echo "  <input type='text' id='AnzFragen' name='AnzFragen' hidden>" . chr(10);
	echo "  <input type='text' id='RichtigBeantwortet' name='RichtigBeantwortet' hidden>" . chr(10);
	echo "  <input type='text' id='PunkteErreicht' name='PunkteErreicht' hidden>" . chr(10);
	echo "  <input type='text' id='PunkteMax' name='PunkteMax' hidden>" . chr(10);
	echo "  <input type='text' id='FalscheAntworten' name='FalscheAntworten' hidden>" . chr(10);
	echo "  <input type='hidden' id='QuizErgebnisFragen' name='QuizErgebnisFragen' value=''>" . chr(10);
	echo "  <input type='text' id='Erfuellt' name='Erfuellt' hidden>" . chr(10);
	echo "  <input type='text' id='Quiz' name='Quiz' hidden>" . chr(10);
	echo "  <input type='text' id='Status' name='Status' value='".$Status."' hidden>" . chr(10);
	echo "  <input type='text' id='Schulung' name='Schulung' value='".$Schulung."' hidden>" . chr(10);
	echo "  <input type='hidden' id='QuizModus' name='QuizModus' value='" . htmlspecialchars($dgaQuizModusLog, ENT_QUOTES, "UTF-8") . "'>" . chr(10);
	if ($dgaPersPickKnown) {
		echo "  <input type='hidden' name='PersPick' value='" . htmlspecialchars($dgaPersPickEnabled ? "on" : "off", ENT_QUOTES, "UTF-8") . "'>" . chr(10);
	}
	if ($dgaExamCode !== "") {
		echo "  <input type='hidden' id='EXAM' name='EXAM' value='" . htmlspecialchars($dgaExamCode, ENT_QUOTES, "UTF-8") . "'>" . chr(10);
	}

	// Abschluss
	echo "  <input type='submit' id='close' value='Quiz Abschliessen' style='".$ButtonStyleRed ."' hidden onclick=\"try{if(typeof rkrkDigiAssessRefreshResultHiddenFields==='function'){rkrkDigiAssessRefreshResultHiddenFields();}if(typeof rkrkDigiAssessSnapshotQuizForSubmit==='function'){rkrkDigiAssessSnapshotQuizForSubmit();}}catch(e){}\">" . chr(10);
	echo "</form>" . chr(10);

	// Name fehlt Hinweis
	echo "<span id='noname' hidden><button id='noname_button' onclick='NameEingeben()' style='".$ButtonStyleRed ."'>Bitte Name vor dem Abschliessen eingeben.</button></span>" . chr(10);

	// Navigation
	echo "<div class='rkrk-nav'>" . chr(10);
	echo "  <span id='prev_button'><button type='button' id='prev' onclick='PrevQuestion()'>Zurück</button></span>" . chr(10);
	echo "  <span id='change_button' hidden><button type='button' id='change_answer' onclick='ChangeAnswer()'>Antwort ändern</button></span>" . chr(10);
	echo "  <span id='submit_button'><button type='button' id='submit' class='rkrk-submit-btn' onclick='Submit()'>eingeben und auflösen</button></span>" . chr(10);
	echo "  <span id='next_button'><button type='button' id='next' onclick='NextQuestion()'>Weiter</button></span>" . chr(10);
	echo "</div>" . chr(10);

	// Lösung
	echo "<section class='rkrk-solution' id='rkrk-solution'>" . chr(10);
	echo "  <span id='loesungstext'>Lösungstext</span><br>" . chr(10);
	echo "  <span id='loesung' hidden>" . chr(10);
	echo "    <img src='".$dgaQuizIconLocWeb."void.png' id='loesung-bild' name='loesung-bild' width='".$PicWidth."' style='".$PicutreStyle."'><br/><br/>" . chr(10);
	echo "    <span id='korrektur' hidden>Korrektur</span><br>" . chr(10);
	echo "  </span><br>" . chr(10);
	echo "</section>" . chr(10);

	// Debug
	echo "<section class='rkrk-solution' id='rkrk-debug'>" . chr(10);
	echo "  <div id='debug' name='debug' style='background-color: #f7e9e9;'></div>" . chr(10);
	echo "  <div id='debug1' name='debug1' style='background-color: #f7e9e9;'></div>" . chr(10);
	echo "  <div id='debug2' name='debug2' style='background-color: #f7e9e9;'></div>" . chr(10);
	echo "</section>" . chr(10);
	echo "</main>" . chr(10);

	echo "</div>" . chr(10); // grid
	// Footer
	$digitalFooterMailQuiz = rawurlencode($onRKRS ? 'Rückmeldung Quiz Rehkitzrettung Schweiz' : 'Rückmeldung Quiz Rehkitzrettung Knonaueramt');
	$digitalFooterMailSchul = rawurlencode($onRKRS ? 'Rückmeldung Schulungen Rehkitzrettung Schweiz' : 'Rückmeldung Schulungen Drohnenrehkitzrettung Knonaueramt');
	echo "<section class='rkrk-solution' id='rkrk-debug'>" . chr(10);
	echo "<footer class='rkrk-footer'>" . chr(10);
	$__dgaFooterHub = function_exists("rkrk_assess_append_setting_href")
		? rkrk_assess_append_setting_href($_SERVER["PHP_SELF"] . "?" . $urladder)
		: ($_SERVER["PHP_SELF"] . "?" . $urladder);
	// portal.rehkitzrettung.ch: «Weitere Quiz/Schulungen» nur für ADMIN/INSTR; NOM blendet die Hub-Links immer aus
	$__dgaHideWeitereHub = (function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode())
		|| (function_exists("rkrk_assess_hash_gate_is_portal_host")
		&& rkrk_assess_hash_gate_is_portal_host()
		&& !$isAdminMode
		&& !$isInstrMode);
	if (trim((string) ($dgaReportDownloadHref ?? "")) !== "") {
		$__repLbl = trim((string) ($dgaReportDownloadLabel ?? ""));
		if ($__repLbl === "") {
			$__repLbl = "PDF-Report";
		}
		echo "  <div class='rkrk-footer-report'>" . chr(10);
		echo "    <a class='rkrk-footer-btn rkrk-footer-btn--report' href='" . htmlspecialchars((string) $dgaReportDownloadHref, ENT_QUOTES, "UTF-8") . "' target='_blank' rel='noopener'>".htmlspecialchars($__repLbl, ENT_QUOTES, "UTF-8")."</a>" . chr(10);
		echo "  </div>" . chr(10);
	}
	echo "  <div align='center'> <i>© " . date("Y") ." by ".$copyright."</i></div>" . chr(10);
	echo "  <div class='rkrk-footer-links'>" . chr(10);
	if ($Schulung) {
		echo "<a class='rkrk-footer-btn' href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=".$digitalFooterMailSchul."'>Rückmeldungen</a>" . chr(10);
		if (!$__dgaHideWeitereHub) {
			echo "<a class='rkrk-footer-btn' href='".htmlspecialchars($__dgaFooterHub, ENT_QUOTES, "UTF-8")."'>Weitere Schulungen</a>" . chr(10);
		}
	} else {
		echo "<a class='rkrk-footer-btn' href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=".$digitalFooterMailQuiz."'>Rückmeldungen</a>" . chr(10);
		if (!$__dgaHideWeitereHub) {
			echo "<a class='rkrk-footer-btn' href='".htmlspecialchars($__dgaFooterHub, ENT_QUOTES, "UTF-8")."'>Weitere Quiz</a>" . chr(10);
		}
	}
	echo "  </div>" . chr(10);
	echo "  <span id='buffer'></span>" . chr(10);
	echo "</footer>" . chr(10);
	echo "</section>" . chr(10);
	echo "</div>" . chr(10); // wrap


?>
<!-- TIMER balken --> 
<script>
let timerDuration = 20;
let timerOrange = 5;
let timerRed = 3;
let timerStart = null;
let timerActive = false;

function startTimer() {
    timerStart = null;
    timerActive = true;
    requestAnimationFrame(runTimer);
}

function stopTimer() {
    timerActive = false;
}

function resetTimerBar() {
    const bar = document.getElementById("timer-bar");
    bar.style.width = "100%";
    bar.style.backgroundColor = "#00c853";
}

function runTimer(timestamp) {
    if (!timerActive) return;

    if (!timerStart) timerStart = timestamp;
    const elapsed = (timestamp - timerStart) / 1000;
    const remaining = Math.max(timerDuration - elapsed, 0);

    const bar = document.getElementById("timer-bar");
    const progress = remaining / timerDuration;

    bar.style.width = (progress * 100) + "%";

    // Dynamische Farbschwellen
    let orangeLimit = timerDuration / 3;
    let redLimit = timerDuration / 5;

    if (remaining <= redLimit) {
        bar.style.backgroundColor = "#e60000"; // Rot
    } else if (remaining <= orangeLimit) {
        bar.style.backgroundColor = "#ff9800"; // Orange
    } else {
        bar.style.backgroundColor = "#00c853"; // Grün
    }

    if (remaining > 0) {
        requestAnimationFrame(runTimer);
    } else {
        timerActive = false;
        Submit();
    }
	
}

function runQuizTimer(timestamp) {
	document.getElementById("quiztimer-container").style.display = "block";

    if (!quizTimerActive) return;

    if (!quizTimerStart) quizTimerStart = timestamp;

    const elapsed = (timestamp - quizTimerStart) / 1000;
    const remaining = Math.max(quizTimerDuration - elapsed, 0);

    // mm:ss Format
    let mm = Math.floor(remaining / 60);
    let ss = Math.floor(remaining % 60);
    if (ss < 10) ss = "0" + ss;

    // Elemente
    const bar = document.getElementById("quiztimer-bar");
    const txt = document.getElementById("quiztimer-text");
    const cont = document.getElementById("quiztimer-container");

    // Balken sichtbar halten
    cont.style.display = "block";

    // Balkenbreite
    const progress = remaining / quizTimerDuration;
    bar.style.width = (progress * 100) + "%";

    // Farben
    let orangeLimit = quizTimerDuration / 3;
    let redLimit = quizTimerDuration / 5;

    if (remaining <= redLimit) {
        bar.style.backgroundColor = "#e60000";
    } else if (remaining <= orangeLimit) {
        bar.style.backgroundColor = "#ff9800";
    } else {
        bar.style.backgroundColor = "#00c853";
    }

    // Text
    txt.innerHTML = mm + ":" + ss;
    if (typeof quizTimerRemainingSec !== "undefined") {
        quizTimerRemainingSec = remaining;
    }

    if (remaining > 0) {
        requestAnimationFrame(runQuizTimer);
    } else {
        quizTimerActive = false;
        if (rkrkDigiAssessPendingFinishSubmit()) {
            FinishExam();
            return;
        }
        if (typeof rkrkDigiAssessRefreshResultHiddenFields === "function") {
            rkrkDigiAssessRefreshResultHiddenFields();
        }
        document.getElementById("Status").value = "resultat";
        try {
            if (typeof rkrkDigiAssessSnapshotQuizForSubmit === "function") {
                rkrkDigiAssessSnapshotQuizForSubmit();
            }
        } catch (eSnap) {}
        document.forms[0].submit();
    }
}
</script>

<script type='text/javascript'>
<?php
if (!isset($Questionair) || !is_array($Questionair)) {
	$Questionair = array(
		"Key" => "",
		"Einfuehrung" => "",
		"QuizTime" => "",
		"DriveLocation" => "",
	);
}
if (!isset($LearningContent)) {
	$LearningContent = "";
}
$quizListForJs = $QuizList;
if (!$isAdminMode && is_array($quizListForJs)) {
	$quizListForJs = array_values(array_filter($quizListForJs, static function ($r) {
		return is_array($r) && trim((string)($r["Aktiv"] ?? "")) === "Ja";
	}));
}
$__dgaJsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
echo "var QuizList = ". json_encode($quizListForJs, $__dgaJsonFlags) . ";\n"; 
echo "var Questionair = ". json_encode($Questionair, $__dgaJsonFlags) . ";\n"; 
echo "var Questions = ". json_encode($Questions, $__dgaJsonFlags) . ";\n"; 
echo "var QuizIconTopLoc = ". json_encode($dgaQuizIconLocWeb) . ";\n"; 
$driveLoc = "";
if (function_exists("rkrk_assess_clean_drive_location_rel")) {
	$driveLoc = rkrk_assess_clean_drive_location_rel(
		isset($Questionair["DriveLocation"]) ? (string) $Questionair["DriveLocation"] : ""
	);
} else {
	$driveLoc = isset($Questionair["DriveLocation"]) ? trim((string) $Questionair["DriveLocation"]) : "";
	$driveLoc = trim(str_replace("\\", "/", $driveLoc), "/");
}
if ($driveLoc !== "" && function_exists("rkrk_assess_encode_url_path_segments")) {
	$driveLoc = rkrk_assess_encode_url_path_segments($driveLoc);
}
if ($driveLoc !== "") {
	$driveLoc .= "/";
}
echo "var QuizIconLoc = ". json_encode($dgaQuizIconLocWeb . $driveLoc) . ";\n"; 
$__dgaDriveLocationJs = isset($__dgaDriveLocation) ? (string) $__dgaDriveLocation : "";
echo "var dgaDriveLocation = ". json_encode($__dgaDriveLocationJs) . ";\n";
$__dgaImgProxy = str_replace("\\", "/", (string)(dirname((string)($_SERVER["SCRIPT_NAME"] ?? ""))));
$__dgaImgProxy = rtrim($__dgaImgProxy, "/") . "/rkrk_assess_image.php";
echo "var QuizImageProxyUrl = ". json_encode($__dgaImgProxy) . ";\n";
echo "var QuizIntroduction = '". $Questionair["Einfuehrung"] . "';\n";
echo "var onRKRS = " . ($onRKRS ? 'true' : 'false') . ";\n";
echo "var titleQuizHub = " . json_encode($onRKRS ? 'Quiz — Rehkitzrettung Schweiz' : 'Quiz — Rehkitzrettung Knonaueramt') . ";\n";
echo "var titleSchulungHub = " . json_encode($onRKRS ? 'Schulung — Rehkitzrettung Schweiz' : 'Schulung — Rehkitzrettung Knonaueramt') . ";\n";
echo "var isAdminMode = " . ($isAdminMode ? "true" : "false") . ";\n";
echo "var isInstrMode = " . ($isInstrMode ? "true" : "false") . ";\n";
echo "var setting = '". $setting . "';\n";
echo "var set_quiz = '". $set_quiz . "';\n";
echo "var rkrkDgaPrimaryButtonStyle = " . json_encode($ButtonStyle) . ";\n";
echo "var urladder = " . json_encode((string) $urladder, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ";\n";
echo "var allquestions = '". ($mode=="Korrektur")  . "';\n";
echo "var NoQuiz = " . $NoQuiz . ";\n";
echo "var Status = '" . $Status . "';\n";
echo "var Schulung = '" . $Schulung . "';\n";
echo "var ohneLerntextStart = " . ($ohneLerntextInteractive ? "true" : "false") . ";\n";
echo "var ohneBilderStart = " . ($ohneBilderInteractive ? "true" : "false") . ";\n";
echo "var quizModus = " . json_encode($dgaQuizModusLog, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";\n";
echo "var examFinishConfirmed = false;\n";
echo "var FragenRichtig = '" . $FragenRichtig . "';\n";
echo "var AnzFragen = '" . $AnzFragen . "';\n";
echo "var PunkteErreicht = " . json_encode((string) $PunkteErreicht, JSON_UNESCAPED_UNICODE) . ";\n";
echo "var PunkteMax = " . json_encode((string) $PunkteMax, JSON_UNESCAPED_UNICODE) . ";\n";
echo "var Erfuellt = '" . $Erfuellt . "';\n";
$__dgaQuizTitle = "";
if (isset($Questionair) && is_array($Questionair)) {
	$__dgaQuizTitle = trim((string) ($Questionair["Titel"] ?? ""));
}
if ($__dgaQuizTitle === "") {
	$__titleKey = trim((string) $set_quiz);
	if ($__titleKey === "") {
		$__titleKey = trim((string) $Quiz);
	}
	if ($__titleKey !== "" && function_exists("rkrk_assess_quiz_title_from_key")) {
		$__dgaQuizTitle = rkrk_assess_quiz_title_from_key($__titleKey);
	}
	if ($__dgaQuizTitle === "") {
		$__dgaQuizTitle = $__titleKey;
	}
}
echo "var dgaQuizTitle = " . json_encode($__dgaQuizTitle, JSON_UNESCAPED_UNICODE) . ";\n";
echo "var quizErgebnisFragen = " . json_encode(isset($quizErgebnisFragen) ? $quizErgebnisFragen : null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ";\n";
echo "var LearningContent = " . json_encode((string)$LearningContent, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . ";\n";
echo "var Quiz = '" . $Quiz . "';\n";
echo "var PicHeight = ". $PicHeight  . ";\n";
echo "var PicHeightSmall = ". $PicHeightSmall  . ";\n";
echo "var PicWidth = '" . $PicWidth . "';\n";
echo "var PicutreStyle = " . json_encode($PicutreStyle) . ";\n";
echo "var SelectedDrone = " . json_encode((string)$Drone, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) . ";\n";
echo "var hasDroneTypes = " . ($HasDroneTypes ? "true" : "false") . ";\n";
echo "var QuizTime = '". $Questionair["QuizTime"] . "';\n";
echo "var Name = " . json_encode((string)$Name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) . ";\n";
echo "var dgaQuestionFingerprint = " . json_encode((string)$__dgaQuestionFingerprint, JSON_UNESCAPED_UNICODE) . ";\n";
echo "var dgaProgressApiUrl = " . json_encode((string)$__dgaProgressApiUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
echo "var dgaExamCode = " . json_encode((string)$dgaExamCode, JSON_UNESCAPED_UNICODE) . ";\n";
echo "var dgaExamCompleted = " . ($dgaExamCompleted ? "true" : "false") . ";\n";
echo "var dgaExamContextMismatch = " . ($dgaExamContextMismatch ? "true" : "false") . ";\n";
if (!isset($__dgaPilotIdMapJson)) {
	$__dgaPilotIdMapJson = "null";
}
echo "var rkrkDgaPilotIdByName = " . $__dgaPilotIdMapJson . ";\n";
?>

function rkrkDigiAssessCsvJa(v) {
    return String(v == null ? "" : v).trim().toLowerCase() === "ja";
}
function rkrkDigiAssessAudienceSegments(audRaw) {
    const s = String(audRaw == null ? "" : audRaw).trim();
    if (!s) return [];
    return s.split(/[,;|/]+/).map((x) => x.trim()).filter(Boolean);
}
/** Entspricht PHP rkrk_assess_setting_sees_audience (Mehrfach-Audienz, RKRS-CH). */
function rkrkDigiAssessAudienceOk(setting, audRaw) {
    const audFull = String(audRaw == null ? "" : audRaw).trim();
    const st = String(setting == null ? "" : setting).trim();
    // Leeres Feld wie «Alle» — Schulungen/Quiz in allen Portalgruppen sichtbar.
    if (!audFull) return true;
    let segs = rkrkDigiAssessAudienceSegments(audFull);
    if (segs.length === 0) segs = [audFull];
    let i;
    for (i = 0; i < segs.length; i++) {
        const seg = segs[i];
        if (seg.localeCompare("Alle", undefined, { sensitivity: "accent" }) === 0) return true;
        if (st && seg.localeCompare(st, undefined, { sensitivity: "accent" }) === 0) return true;
    }
    if (!/^RKRS(prev|plus)?$/i.test(st)) return false;
    for (i = 0; i < segs.length; i++) {
        if (segs[i].localeCompare("RKRS", undefined, { sensitivity: "accent" }) === 0) return true;
    }
    for (i = 0; i < segs.length; i++) {
        if (/^RKRS(prev|plus)?$/i.test(segs[i])) return true;
    }
    return false;
}

function rkrkQuizEncodePathSegment(seg) {
    if (!seg) return "";
    let s = seg;
    for (let i = 0; i < 6; i++) {
        try {
            const d = decodeURIComponent(s);
            if (d === s) break;
            s = d;
        } catch (e) {
            break;
        }
    }
    try {
        if (typeof s.normalize === "function") {
            s = s.normalize("NFC");
        }
    } catch (e2) {}
    return encodeURIComponent(s);
}
function rkrkQuizImageBasename(rawPath) {
    let p = String(rawPath || "").replace(/\\/g, "/").trim();
    if (!p) return "";
    for (let i = 0; i < 6; i++) {
        try {
            const d = decodeURIComponent(p);
            if (d === p) break;
            p = d;
        } catch (e) {
            break;
        }
    }
    const markers = [
        "/rkrk/data/assess/quiz/images/",
        "/rkrs/data/assess/quiz/images/",
        "/rkrsplusassessment/data/assess/quiz/images/",
        "/rkrk/data/assess/images/",
        "/rkrs/data/assess/images/",
        "/rkrsplusassessment/data/assess/images/"
    ];
    const low = p.toLowerCase();
    for (let i = 0; i < markers.length; i++) {
        const mi = low.indexOf(markers[i]);
        if (mi >= 0) {
            p = p.substring(mi + markers[i].length);
            break;
        }
    }
    p = p.replace(/^(?:quiz\/)?images\//i, "").replace(/^\/+/, "");
    const segs = p.split("/").filter(Boolean);
    return segs.length ? segs[segs.length - 1] : p;
}
function rkrkQuizImageUrl(base, rawPath) {
    let p = rkrkQuizImageBasename(rawPath);
    if (!p) return "";
    if (/^https?:\/\//i.test(String(rawPath || ""))) return String(rawPath).trim();
    // Bevorzugt PHP-Proxy: liest aus dem Assess-Dateisystem (Portal/Static-Pfad-Abweichungen vermeiden).
    if (typeof QuizImageProxyUrl === "string" && QuizImageProxyUrl !== "") {
        let drive = "";
        if (typeof dgaDriveLocation === "string" && dgaDriveLocation) {
            drive = String(dgaDriveLocation).replace(/^\/+|\/+$/g, "");
        } else if (typeof QuizIconTopLoc === "string" && typeof base === "string" && base.indexOf(QuizIconTopLoc) === 0) {
            drive = String(base.substring(QuizIconTopLoc.length) || "").replace(/^\/+|\/+$/g, "");
            try { drive = decodeURIComponent(drive); } catch (eDec) {}
        }
        let url = QuizImageProxyUrl + "?f=" + encodeURIComponent(p);
        if (drive) {
            url += "&d=" + encodeURIComponent(drive);
        }
        return url;
    }
    const enc = rkrkQuizEncodePathSegment(p);
    return rkrkQuizEncodeFullUrl(String(base || "") + enc);
}
function rkrkQuizCleanDriveLocationRel(dl) {
    let s = String(dl || "").trim().replace(/\\/g, "/");
    const m = s.match(/\/(?:rkrk|rkrs|rkrsplusassessment)\/data\/assess\/images\/(.*)$/i);
    if (m) {
        s = m[1];
    }
    return s.replace(/^\/+|\/+$/g, "");
}
function rkrkQuizListImageBase(qs) {
    let base = String(QuizIconTopLoc || "");
    if (!base.endsWith("/")) {
        base += "/";
    }
    const dl = rkrkQuizCleanDriveLocationRel(qs && qs["DriveLocation"]);
    if (dl) {
        dl.split("/").filter(Boolean).forEach(function (seg) {
            base += rkrkQuizEncodePathSegment(seg) + "/";
        });
    }
    return base;
}
function rkrkQuizEncodeFullUrl(url) {
    const u = String(url || "");
    if (!u) return "";
    if (/^https?:\/\//i.test(u)) {
        try {
            const parsed = new URL(u);
            const path = parsed.pathname.split("/").map(rkrkQuizEncodePathSegment).filter(function (s) { return s !== ""; }).join("/");
            return parsed.origin + "/" + path + parsed.search + parsed.hash;
        } catch (eUrl) {
            return u;
        }
    }
    let path = u;
    let query = "";
    let hash = "";
    const qi = path.indexOf("?");
    if (qi >= 0) {
        query = path.substring(qi);
        path = path.substring(0, qi);
    }
    const hi = path.indexOf("#");
    if (hi >= 0) {
        hash = path.substring(hi);
        path = path.substring(0, hi);
    }
    let leading = "";
    if (path.charAt(0) === "/") {
        leading = "/";
        path = path.replace(/^\/+/, "");
    }
    const encPath = path.split("/").map(rkrkQuizEncodePathSegment).filter(Boolean).join("/");
    return leading + encPath + query + hash;
}

var AtQuestion = 0;
var url = window.location.href.split('?')[0];

// Quiz-Gesamtzeit (in Sekunden aus CSV)
var quizTimerDuration = parseInt(QuizTime || 0);
var quizTimerStart = null;
var quizTimerActive = false;
var quizTimerRemainingSec = parseInt(QuizTime || 0, 10) || 0;
var dgaProgressAutosaveTimer = null;
var dgaProgressResumeApplied = false;
var dgaQuizProgressActive = false;
var dgaProgressSavePending = false;

// Timer ausblenden
if (!quizTimerActive) {
    document.getElementById("timer-container").style.display = "none";
}

// MaxQuestions bestimmen
var MaxQuestions = 0;
if (Questions.length > 0) {
    if ((Questions.length < Questionair["AnzQuestions"]) || (allquestions)) {
        MaxQuestions = Questions.length;
    } else {
        MaxQuestions = Questionair["AnzQuestions"];
    }
}
// JSON liefert AnzQuestions oft als String — ohne Zahl bleibt Snapshot/CSV-Detail leer (nMax=0).
MaxQuestions = parseInt(String(MaxQuestions), 10) || 0;

// Farben
const red = "#fc2222";
const grey = "#aaaaaa";
const green = "#67b516";
const orange = "#f59e0b";
const blue = "#1890ff";

function rkrkDigiAssessIsExamMode() {
    return String(quizModus || "").toLowerCase() === "pruefung";
}

function rkrkDigiAssessIsExamContext() {
    return rkrkDigiAssessIsExamMode() || rkrkDigiAssessExamLocked();
}

function rkrkDigiAssessIsResultatStatus() {
    return rkrkDigiAssessGetProgressStatus() === "resultat" || String(Status || "") === "resultat";
}

function rkrkDigiAssessDeferPerQuestionFeedback() {
    if (examFinishConfirmed) {
        return false;
    }
    return rkrkDigiAssessIsExamMode();
}

function rkrkDigiAssessAllQuestionsLocked() {
    let locked = 0;
    for (let i = 0; i < MaxQuestions && i < Questions.length; i++) {
        if (rkrkDigiAssessQuestionLockedJa(Questions[i])) {
            locked++;
        }
    }
    return locked >= MaxQuestions && MaxQuestions > 0;
}

function rkrkDigiAssessPendingFinishSubmit() {
    return rkrkDigiAssessAllQuestionsLocked()
        && !examFinishConfirmed
        && !rkrkDigiAssessIsResultatStatus();
}

function rkrkDigiAssessFinishSubmitLabel() {
    if (rkrkDigiAssessIsExamContext()) {
        return "Prüfung abschliessen";
    }
    return "Fragen einreichen";
}

function rkrkDigiAssessCloseButtonLabel() {
    if (rkrkDigiAssessIsExamContext()) {
        return "Prüfung abschliessen";
    }
    return (Schulung === "Schulung") ? "Schulung abschliessen" : "Quiz abschliessen";
}

function rkrkDigiAssessSetCloseVisible(show) {
    const closeEl = document.getElementById("close");
    if (!closeEl) {
        return;
    }
    if (show) {
        closeEl.removeAttribute("hidden");
        closeEl.style.display = "block";
        closeEl.value = rkrkDigiAssessCloseButtonLabel();
    } else {
        closeEl.setAttribute("hidden", "");
        closeEl.style.display = "none";
    }
}

function rkrkDigiAssessCanChangeLockedAnswer() {
    if (!rkrkDigiAssessIsExamMode() || examFinishConfirmed || rkrkDigiAssessIsResultatStatus()) {
        return false;
    }
    const q = Questions[AtQuestion];
    if (!q) {
        return false;
    }
    return rkrkDigiAssessQuestionLockedJa(q)
        && String(q.Antwort == null ? "" : q.Antwort).trim() !== "";
}

function rkrkDigiAssessSetChangeAnswerVisible(show) {
    const el = document.getElementById("change_button");
    if (!el) {
        return;
    }
    if (show) {
        el.removeAttribute("hidden");
        el.style.display = "flex";
    } else {
        el.setAttribute("hidden", "");
        el.style.display = "none";
    }
}

function rkrkDigiAssessSyncChangeAnswerButton() {
    rkrkDigiAssessSetChangeAnswerVisible(rkrkDigiAssessCanChangeLockedAnswer());
}

function rkrkDigiAssessCommitUnlockedExamAnswer() {
    if (!rkrkDigiAssessIsExamMode() || examFinishConfirmed || rkrkDigiAssessIsResultatStatus()) {
        return false;
    }
    const q = Questions[AtQuestion];
    if (!q || rkrkDigiAssessQuestionLockedJa(q)) {
        return false;
    }
    if (String(q.Antwort == null ? "" : q.Antwort).trim() === "") {
        return false;
    }
    q.Locked = "Ja";
    UpdateButton(AtQuestion);
    return true;
}

function ChangeAnswer() {
    if (!rkrkDigiAssessCanChangeLockedAnswer()) {
        return;
    }
    Questions[AtQuestion]["Locked"] = "Nein";
    const submitWrap = document.getElementById("submit_button");
    if (submitWrap) {
        submitWrap.style.display = "block";
    }
    rkrkDigiAssessSyncSubmitButton();
    LockWahl();
    ShowResult();
    rkrkDigiAssessRefreshAllQuestionButtons();
    if (typeof rkrkDigiAssessOnQuizInteraction === "function") {
        rkrkDigiAssessOnQuizInteraction();
    }
}

function rkrkDigiAssessApplyFinishSubmitUi() {
    showTeilnehmerinfo();
    document.getElementById("submit_button").style.display = "block";
    const finishBtn = document.getElementById("submit");
    if (finishBtn) {
        finishBtn.textContent = rkrkDigiAssessFinishSubmitLabel();
        finishBtn.onclick = FinishExam;
    }
    document.getElementById("Status").value = "Quiz";
    Status = "Quiz";
    rkrkDigiAssessSyncChangeAnswerButton();
}

function rkrkDigiAssessUrladderWithoutQuizModus() {
    let u = String(urladder || "");
    u = u.replace(/(^|&)QuizModus=pruefung(&|$)/gi, "$1").replace(/&&/g, "&").replace(/^&/, "").replace(/&$/, "");
    return u;
}

function rkrkDigiAssessUrladderExam() {
    const base = rkrkDigiAssessUrladderWithoutQuizModus();
    return base ? (base + "&QuizModus=pruefung") : "QuizModus=pruefung";
}

function rkrkDigiAssessSyncSubmitButton() {
    const btn = document.getElementById("submit");
    if (!btn) {
        rkrkDigiAssessSyncChangeAnswerButton();
        return;
    }
    if (rkrkDigiAssessPendingFinishSubmit()) {
        rkrkDigiAssessApplyFinishSubmitUi();
        return;
    }
    if (rkrkDigiAssessDeferPerQuestionFeedback()) {
        btn.textContent = "Antwort übernehmen";
        btn.onclick = Submit;
    } else {
        btn.textContent = "eingeben und auflösen";
        btn.onclick = Submit;
    }
    rkrkDigiAssessSyncChangeAnswerButton();
}

function rkrkDigiAssessApplyNavLabels() {
    rkrkDigiAssessSyncSubmitButton();
}

function rkrkDigiAssessButtonColorForQuestion(nr) {
    const qu = Questions[nr];
    if (!rkrkDigiAssessIsQuestionAnsweredInQuiz(qu)) {
        return grey;
    }
    if (rkrkDigiAssessDeferPerQuestionFeedback()) {
        return blue;
    }
    const sc = rkrkDigiAssessQuestionScore(qu, rkrkDigiAssessScoreMode());
    if (sc.status === "full") {
        return green;
    }
    if (sc.status === "partial") {
        return orange;
    }
    return red;
}

function rkrkDigiAssessSyncQuestionNavButton(nr) {
    const el = document.getElementById("F" + nr);
    if (!el) {
        return;
    }
    const qu = Questions[nr];
    const color = rkrkDigiAssessButtonColorForQuestion(nr);
    el.style.background = color;
    el.classList.remove("rkrk-qnav-btn--pending", "rkrk-qnav-btn--exam", "rkrk-qnav-btn--correct", "rkrk-qnav-btn--partial", "rkrk-qnav-btn--wrong");
    if (!rkrkDigiAssessIsQuestionAnsweredInQuiz(qu)) {
        el.classList.add("rkrk-qnav-btn--pending");
    } else if (rkrkDigiAssessDeferPerQuestionFeedback()) {
        el.classList.add("rkrk-qnav-btn--exam");
    } else {
        const sc = rkrkDigiAssessQuestionScore(qu, rkrkDigiAssessScoreMode());
        if (sc.status === "full") {
            el.classList.add("rkrk-qnav-btn--correct");
        } else if (sc.status === "partial") {
            el.classList.add("rkrk-qnav-btn--partial");
        } else {
            el.classList.add("rkrk-qnav-btn--wrong");
        }
    }
}

function rkrkDigiAssessMarkCurrentNavQuestion() {
    for (let qi = 0; qi < MaxQuestions; qi++) {
        const el = document.getElementById("F" + qi);
        if (el) {
            el.classList.toggle("rkrk-qnav-btn--current", qi === AtQuestion);
        }
    }
}

function rkrkDigiAssessCollectNavLegendStates() {
    const states = { open: false, correct: false, partial: false, wrong: false, exam: false };
    for (let i = 0; i < MaxQuestions; i++) {
        const qu = Questions[i];
        if (!rkrkDigiAssessIsQuestionAnsweredInQuiz(qu)) {
            states.open = true;
        } else if (rkrkDigiAssessDeferPerQuestionFeedback()) {
            states.exam = true;
        } else {
            const sc = rkrkDigiAssessQuestionScore(qu, rkrkDigiAssessScoreMode());
            if (sc.status === "full") {
                states.correct = true;
            } else if (sc.status === "partial") {
                states.partial = true;
            } else {
                states.wrong = true;
            }
        }
    }
    return states;
}

function rkrkDigiAssessUpdateQuestionNavLegend() {
    const titleEl = document.getElementById("result-titel");
    if (!titleEl) return;
    const states = rkrkDigiAssessCollectNavLegendStates();
    const parts = [];
    if (states.correct) {
        parts.push('<span class="rkrk-qnav-legend-i rkrk-qnav-legend-i--ok"></span>richtig');
    }
    if (states.partial) {
        parts.push('<span class="rkrk-qnav-legend-i rkrk-qnav-legend-i--partial"></span>teilweise');
    }
    if (states.wrong) {
        parts.push('<span class="rkrk-qnav-legend-i rkrk-qnav-legend-i--bad"></span>falsch');
    }
    if (states.exam) {
        parts.push('<span class="rkrk-qnav-legend-i rkrk-qnav-legend-i--exam"></span>beantwortet');
    }
    if (states.open) {
        parts.push('<span class="rkrk-qnav-legend-i rkrk-qnav-legend-i--open"></span>offen');
    }
    const legendHtml = parts.length
        ? '<span class="rkrk-qnav-legend">' + parts.join("") + "</span>"
        : "";
    titleEl.innerHTML = "Fragen" + legendHtml;
}

function rkrkDigiAssessRebuildQuestionNav() {
    let output = "";
    for (let idx = 0; idx < MaxQuestions; idx++) {
        output += GetButton(idx, String(idx + 1), rkrkDigiAssessButtonColorForQuestion(idx));
    }
    const resultEl = document.getElementById("result");
    if (resultEl) {
        resultEl.innerHTML = output;
    }
    rkrkDigiAssessUpdateQuestionNavLegend();
    rkrkDigiAssessRefreshAllQuestionButtons();
    rkrkDigiAssessMarkCurrentNavQuestion();
}

function rkrkDigiAssessFinalizeResumeUi(resumeAt) {
    rkrkDigiAssessRebuildQuestionNav();
    GoToQuestion(resumeAt);
    LockWahl();
    ShowResult();
    CheckEndOfQuiz();
    requestAnimationFrame(function () {
        rkrkDigiAssessRefreshAllQuestionButtons();
        LockWahl();
        ShowResult();
    });
}

function rkrkDigiAssessUpdateAllNavButtons() {
    rkrkDigiAssessRefreshAllQuestionButtons();
}

function rkrkDigiAssessCollectDetailFragen() {
    const nMax = Math.max(0, parseInt(String(MaxQuestions), 10) || 0);
    const rows = [];
    let i;
    for (i = 0; i < nMax && i < Questions.length; i++) {
        const q = Questions[i];
        if (!q) {
            continue;
        }
        const rawQid = q.QID != null ? q.QID : q["QID"];
        const qidStr = String(rawQid == null ? "" : rawQid).trim();
        rows.push({
            nr: i + 1,
            qid: qidStr,
            frage: rkrkDigiAssessStripHtml(q.Frage || ""),
            antwort: String(q.Antwort == null ? "" : q.Antwort),
            loesung: String(q.Loesung == null ? "" : q.Loesung),
            loesungstext: rkrkDigiAssessStripHtml(q.Loesungstext || ""),
            antwortText: rkrkDigiAssessStripHtml(q.AntwortText || ""),
            antwortLabel: rkrkDigiAssessAnswerDisplay(q, q.Antwort),
			loesungLabel: rkrkDigiAssessAnswerDisplay(q, q.Loesung),
			loesungsauswahl: Array.isArray(q.Loesungsauswahl) ? q.Loesungsauswahl.slice() : [],
			richtig: false,
			status: "zero",
			punkte: 0,
			punkteMax: 1
        });
		const sc = rkrkDigiAssessQuestionScore(q, rkrkDigiAssessScoreMode());
		rows[rows.length - 1].richtig = (sc.status === "full");
		rows[rows.length - 1].status = sc.status;
		rows[rows.length - 1].punkte = sc.points;
		rows[rows.length - 1].punkteMax = sc.max;
    }
    return rows;
}

function rkrkDigiAssessStoreDetailFragen(rows) {
    const el = document.getElementById("QuizErgebnisFragen");
    let json = "";
    try {
        json = JSON.stringify(rows);
    } catch (e0) {
        json = "";
    }
    if (el) {
        el.value = json;
    }
    if (typeof sessionStorage !== "undefined" && json !== "") {
        try {
            sessionStorage.setItem("rkrk_dga_ergebnis_fragen_v1", json);
        } catch (e1) {}
    }
}

/** Fehlende Antwort-/Lösungsfelder aus dem aktuellen Questions-Array nachziehen (z. B. nach altem POST). */
function rkrkDigiAssessEnrichDetailFragen(rows) {
    if (!Array.isArray(rows) || !rows.length || !Array.isArray(Questions)) {
        return rows;
    }
    const byQid = {};
    const byNr = {};
    let qi;
    for (qi = 0; qi < Questions.length; qi++) {
        const q = Questions[qi];
        if (!q) continue;
        const qid = String(q.QID == null ? "" : q.QID).trim();
        if (qid !== "") byQid[qid] = q;
        byNr[qi + 1] = q;
    }
    return rows.map(function (row) {
        const r = Object.assign({}, row || {});
        const qid = String(r.qid == null ? "" : r.qid).trim();
        const nr = parseInt(String(r.nr == null ? "" : r.nr), 10) || 0;
        const q = (qid !== "" && byQid[qid]) ? byQid[qid] : (nr > 0 ? byNr[nr] : null);
        if (!q) return r;
        if (r.antwort == null || String(r.antwort) === "") {
            if (String(q.Antwort == null ? "" : q.Antwort).trim() !== "") {
                r.antwort = String(q.Antwort);
            }
        }
        if (r.loesung == null || String(r.loesung) === "") {
            r.loesung = String(q.Loesung == null ? "" : q.Loesung);
        }
        if (r.loesungstext == null || String(r.loesungstext).trim() === "") {
            r.loesungstext = rkrkDigiAssessStripHtml(q.Loesungstext || "");
        }
        if (r.antwortText == null || String(r.antwortText).trim() === "") {
            r.antwortText = rkrkDigiAssessStripHtml(q.AntwortText || "");
        }
        if (!Array.isArray(r.loesungsauswahl) || !r.loesungsauswahl.length) {
            r.loesungsauswahl = Array.isArray(q.Loesungsauswahl) ? q.Loesungsauswahl.slice() : [];
        }
        const qLike = { Loesung: r.loesung, Loesungsauswahl: r.loesungsauswahl || [] };
        if (r.antwortLabel == null || String(r.antwortLabel).trim() === "") {
            r.antwortLabel = rkrkDigiAssessAnswerDisplay(qLike, r.antwort);
        }
        if (r.loesungLabel == null || String(r.loesungLabel).trim() === "") {
            r.loesungLabel = rkrkDigiAssessAnswerDisplay(qLike, r.loesung);
        }
        return r;
    });
}

function rkrkDigiAssessParsePunkteNum(raw) {
    const n = parseFloat(String(raw == null ? "" : raw).replace(",", "."));
    return (typeof n === "number" && !isNaN(n) && n >= 0) ? n : 0;
}

/** Anzeige: auf 1/10 runden (32.38 → 32.4, 1.00 → 1). */
function rkrkDigiAssessFormatPunkte(n) {
    const r = Math.round(rkrkDigiAssessParsePunkteNum(n) * 10) / 10;
    if (Math.abs(r - Math.round(r)) < 0.00001) {
        return String(Math.round(r));
    }
    return r.toFixed(1);
}

function rkrkDigiAssessQuizTitle() {
    const fromQ = (typeof Questionair !== "undefined" && Questionair)
        ? String(Questionair["Titel"] || "").trim() : "";
    if (fromQ) {
        return fromQ;
    }
    const fromPhp = (typeof dgaQuizTitle !== "undefined") ? String(dgaQuizTitle || "").trim() : "";
    if (fromPhp) {
        return fromPhp;
    }
    const key = String(
        (typeof Questionair !== "undefined" && Questionair && Questionair["Key"])
            ? Questionair["Key"]
            : ((typeof Quiz !== "undefined") ? Quiz : "")
    ).trim();
    if (typeof QuizList !== "undefined" && Array.isArray(QuizList) && key) {
        let i;
        const keyLc = key.toLowerCase();
        for (i = 0; i < QuizList.length; i++) {
            const row = QuizList[i];
            if (!row) {
                continue;
            }
            if (String(row["Key"] || "").trim().toLowerCase() === keyLc) {
                const t = String(row["Titel"] || "").trim();
                if (t) {
                    return t;
                }
            }
        }
    }
    return key || "Ergebnis";
}

function rkrkDigiAssessErfuelltRaw() {
    if (typeof Questionair !== "undefined" && Questionair != null
        && Questionair["Erfuellt"] != null && String(Questionair["Erfuellt"]).trim() !== "") {
        return Questionair["Erfuellt"];
    }
    if (typeof Erfuellt !== "undefined" && String(Erfuellt).trim() !== "") {
        return Erfuellt;
    }
    return 0;
}

/** Summe punkte/punkteMax aus Ergebniszeilen (PunkteModus). */
function rkrkDigiAssessSumDetailPunkte(rows) {
    if (!Array.isArray(rows) || !rows.length) {
        return null;
    }
    let pe = 0;
    let pm = 0;
    let has = false;
    let i;
    for (i = 0; i < rows.length; i++) {
        const r = rows[i] || {};
        if (r.punkteMax == null && r.punkte == null) {
            continue;
        }
        has = true;
        pe += rkrkDigiAssessParsePunkteNum(r.punkte);
        pm += rkrkDigiAssessParsePunkteNum(r.punkteMax != null ? r.punkteMax : 1);
    }
    if (!has) {
        return null;
    }
    return { PunkteErreicht: pe, PunkteMax: pm };
}

function rkrkDigiAssessResolveResultPunkte(detailFragen, optAnz, optRicht) {
    const summed = rkrkDigiAssessSumDetailPunkte(detailFragen);
    if (summed && summed.PunkteMax > 0) {
        return summed;
    }
    const storedMax = (typeof PunkteMax !== "undefined") ? rkrkDigiAssessParsePunkteNum(PunkteMax) : 0;
    const storedErreicht = (typeof PunkteErreicht !== "undefined") ? rkrkDigiAssessParsePunkteNum(PunkteErreicht) : 0;
    if (storedMax > 0) {
        return { PunkteErreicht: storedErreicht, PunkteMax: storedMax };
    }
    const counts = rkrkDigiAssessRefreshResultHiddenFields();
    if (counts && counts.PunkteMax > 0) {
        return { PunkteErreicht: counts.PunkteErreicht, PunkteMax: counts.PunkteMax };
    }
    const optMax = (optAnz != null && optAnz !== "") ? rkrkDigiAssessParsePunkteNum(optAnz) : 0;
    const optErreicht = (optRicht != null && optRicht !== "") ? rkrkDigiAssessParsePunkteNum(optRicht) : 0;
    return { PunkteErreicht: optErreicht, PunkteMax: optMax };
}

function rkrkDigiAssessBuildResultHtml(detailFragen, punkteMaxRaw, punkteRaw, erfuelltRaw) {
    const __anz = Math.max(0, parseFloat(String(punkteMaxRaw).replace(",", ".")) || 0);
    const __richt = Math.max(0, parseFloat(String(punkteRaw).replace(",", ".")) || 0);
    const __erfRaw = parseFloat(String(erfuelltRaw == null ? "" : erfuelltRaw).replace(",", "."));
    const __erf = (typeof __erfRaw === "number" && !isNaN(__erfRaw)) ? __erfRaw : 0;
    const __pctNeed = Math.round(__erf * 100);
    const __ratio = __anz > 0 ? (__richt / __anz) : 0;
    const __passed = __anz > 0 && __ratio >= __erf;
    const showLoesung = !!examFinishConfirmed || rkrkDigiAssessIsResultatStatus();
    let html = "";

    html += "<div class='rkrk-result-gesamt'>";
    html += "<p class='rkrk-result-gesamt-zeile'>Zum Bestehen sind mindestens <strong>" + __pctNeed + "%</strong> der Punkte zu erreichen.</p>";
    html += "<p class='rkrk-result-gesamt-zeile'>Du hast <strong>" + rkrkDigiAssessFormatPunkte(__richt) + "</strong> von <strong>" + rkrkDigiAssessFormatPunkte(__anz) + "</strong> Punkten erreicht.</p>";
    html += "<p class='rkrk-result-gesamt-status " + (__passed ? "rkrk-result-gesamt-status--ok" : "rkrk-result-gesamt-status--bad") + "'>" + (__passed ? "Bestanden" : "Nicht bestanden") + "</p>";
    html += "</div>";

    if (Array.isArray(detailFragen) && detailFragen.length > 0) {
        html += "<div class='rkrk-result-fragenliste'>";
        html += "<p class='rkrk-result-fragenliste-titel'>Übersicht nach Fragen</p>";
        html += "<ul class='rkrk-result-fragenliste-ul'>";
        let ri;
        for (ri = 0; ri < detailFragen.length; ri++) {
            const row = detailFragen[ri];
            const st = String(row.status || (row.richtig ? "full" : "zero"));
            const ok = st === "full";
            const cls = st === "full"
                ? "rkrk-result-frage rkrk-result-frage--ok"
                : (st === "partial" ? "rkrk-result-frage rkrk-result-frage--partial" : "rkrk-result-frage rkrk-result-frage--bad");
            const badge = st === "full" ? "Richtig" : (st === "partial" ? "Teilweise" : "Falsch");
            const nr = row.nr != null ? row.nr : ri + 1;
            html += "<li class='" + cls + "'><span class='rkrk-result-frage-badge'>" + badge + "</span> ";
            html += "<span class='rkrk-result-frage-nr'>Frage " + nr + ":</span> ";
            html += rkrkDigiAssessEscapeHtml(row.frage || "");
            if (row.punkteMax != null) {
                const pCur = Math.max(0, parseFloat(String(row.punkte || 0)) || 0);
                const pMax = Math.max(0, parseFloat(String(row.punkteMax || 0)) || 0);
                html += " <span class='rkrk-result-frage-nr'>(" + rkrkDigiAssessFormatPunkte(pCur) + "/" + rkrkDigiAssessFormatPunkte(pMax) + " Pkt.)</span>";
            }
            if (showLoesung) {
                const opts = Array.isArray(row.loesungsauswahl) ? row.loesungsauswahl : [];
                const qLike = { Loesung: row.loesung, Loesungsauswahl: opts };
                const antLbl = (row.antwortLabel != null && String(row.antwortLabel).trim() !== "")
                    ? String(row.antwortLabel)
                    : rkrkDigiAssessAnswerDisplay(qLike, row.antwort);
                const solLbl = (row.loesungLabel != null && String(row.loesungLabel).trim() !== "")
                    ? String(row.loesungLabel)
                    : rkrkDigiAssessAnswerDisplay(qLike, row.loesung);
                const lt = row.loesungstext != null ? String(row.loesungstext).trim() : "";
                const at = row.antwortText != null ? String(row.antwortText).trim() : "";
                // Schlussübersicht: Antwort (AntwortText) bevorzugen, sonst Lösungstext, sonst Lösung-Kürzel
                const prose = at !== "" ? at : lt;
                html += "<details class='rkrk-result-frage-loesung'>";
                html += "<summary>Richtige Antwort anzeigen</summary>";
                html += "<div class='rkrk-result-frage-loesung-body'>";
                html += "<p><strong>Deine Antwort:</strong> " + rkrkDigiAssessEscapeHtml(antLbl) + "</p>";
                if (prose !== "") {
                    html += "<p><strong>Richtige Antwort:</strong> " + rkrkDigiAssessEscapeHtml(prose) + "</p>";
                } else {
                    html += "<p><strong>Richtige Antwort:</strong> " + rkrkDigiAssessEscapeHtml(solLbl) + "</p>";
                }
                html += "</div></details>";
            }
            html += "</li>";
        }
        html += "</ul></div>";
    } else {
        html += "<p class='rkrk-result-fragenliste-leer'>Fragenübersicht war nicht verfügbar.</p>";
    }
    return html;
}

function rkrkDigiAssessShowResultOverview(detailFragen, optAnz, optRicht) {
    detailFragen = rkrkDigiAssessEnrichDetailFragen(detailFragen);
    rkrkDigiAssessStoreDetailFragen(detailFragen);
    rkrkDigiAssessRefreshResultHiddenFields();
    const pts = rkrkDigiAssessResolveResultPunkte(detailFragen, optAnz, optRicht);
    document.getElementById("quiz-titel").innerHTML = rkrkDigiAssessEscapeHtml(rkrkDigiAssessQuizTitle());
    let resultHtml = rkrkDigiAssessBuildResultHtml(
        detailFragen,
        pts.PunkteMax,
        pts.PunkteErreicht,
        rkrkDigiAssessErfuelltRaw()
    );
    if (typeof dgaExamCompleted !== "undefined" && dgaExamCompleted) {
        resultHtml = "<p style='color:#64748b;margin:0 0 12px;'>Diese Prüfung wurde bereits abgeschlossen.</p>" + resultHtml;
    }
    document.getElementById("indrodution").innerHTML = resultHtml;
    document.getElementById("indrodution").style.fontSize = "12pt";

    document.getElementById("rkrk-card").style.display = "none";
    document.getElementById("rkrk-sidebar").style.display = "none";
    document.getElementById("rkrk-solution").style.display = "none";
    document.getElementById("rkrk-main").style.display = "none";
    if (setting != "TEST") {
        document.getElementById("rkrk-debug").style.display = "none";
    }
    ShowHideQuestionFields("none");
    document.getElementById("prev_button").style.display = "none";
    document.getElementById("next_button").style.display = "none";
    document.getElementById("submit_button").style.display = "none";
    rkrkDigiAssessSetChangeAnswerVisible(false);

    setTopbarQuickLinksVisible(true);
    showTeilnehmerinfo();

    document.getElementById("Status").value = "resultat";
    Status = "resultat";
    rkrkDigiAssessSnapshotQuizForSubmit();
    if (typeof rkrkDigiAssessDeleteProgress === "function") {
        rkrkDigiAssessDeleteProgress();
    }

    // Auto-Absenden beim Abschluss: EXAM (gesperrt) sowie Prüfungs-/Schulungsmodus ohne EXAM-Code,
    // sobald ein Name vorhanden ist. So werden die (Schulungs-)Resultate serverseitig gespeichert,
    // ohne dass der finale rote Knopf manuell geklickt werden muss (auch bei ohne_bilder).
    const __dgaNameReady = document.getElementById("Name").value.trim() !== "";
    const __dgaAutoFinish =
        (typeof dgaExamCompleted === "undefined" || !dgaExamCompleted)
        && examFinishConfirmed
        && __dgaNameReady
        && (rkrkDigiAssessExamLocked() || rkrkDigiAssessIsExamMode() || String(Schulung) === "Schulung");
    if (__dgaAutoFinish) {
        setTimeout(function () {
            try {
                if (typeof rkrkDigiAssessRefreshResultHiddenFields === "function") { rkrkDigiAssessRefreshResultHiddenFields(); }
                if (typeof rkrkDigiAssessSnapshotQuizForSubmit === "function") { rkrkDigiAssessSnapshotQuizForSubmit(); }
            } catch (eRef) {}
            try {
                document.forms[0].submit();
            } catch (eSubmit) {}
        }, 50);
    }

    if (__dgaNameReady) {
        document.getElementById("noname").style.display = "none";
        rkrkDigiAssessSetCloseVisible(true);
    } else {
        document.getElementById("noname").style.display = "block";
        rkrkDigiAssessSetCloseVisible(false);
    }
}

function FinishExam() {
    stopTimer();
    if (!quizTimerActive) {
        document.getElementById("timer-container").style.display = "none";
    }
    examFinishConfirmed = true;
    rkrkDigiAssessUpdateAllNavButtons();
    const detailFragen = rkrkDigiAssessCollectDetailFragen();
    rkrkDigiAssessStoreDetailFragen(detailFragen);
    rkrkDigiAssessShowResultOverview(detailFragen);
}

// Listener für Antworten
function attachResponseListeners() {
    document.querySelectorAll("input[name='response']").forEach(el => {
        el.addEventListener("change", () => {

            if (el.type === "checkbox") {
                let checked = [...document.querySelectorAll("input[name='response']:checked")]
                    .map(x => x.value);
                Questions[AtQuestion]["Antwort"] = checked.join("|");
            }

            if (el.type === "radio") {
                Questions[AtQuestion]["Antwort"] = el.value;
            }

            document.getElementById("submit_button").style.display = 'block';
            if (typeof rkrkDigiAssessOnQuizInteraction === "function") {
                rkrkDigiAssessOnQuizInteraction();
            }
        });
    });
}
attachResponseListeners();

function rkrkDigiAssessEscapeHtml(s) {
    return String(s == null ? "" : s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function rkrkDigiAssessFixMojibake(s) {
    return String(s == null ? "" : s)
        .replace(/Ã¤/g, "ä").replace(/Ã„/g, "Ä")
        .replace(/Ã¶/g, "ö").replace(/Ã–/g, "Ö")
        .replace(/Ã¼/g, "ü").replace(/Ãœ/g, "Ü")
        .replace(/ÃŸ/g, "ß")
        .replace(/â€“/g, "–").replace(/â€”/g, "—")
        .replace(/â€ž/g, "„").replace(/â€œ/g, "“").replace(/â€/g, "”")
        .replace(/â€˜/g, "‘").replace(/â€™/g, "’")
        .replace(/Â°/g, "°").replace(/Â/g, "");
}

var DGA_QUIZ_SESSION_KEY = "rkrk_dga_quiz_session_v1";
var DGA_QUIZ_PROGRESS_KEY = "rkrk_dga_quiz_progress_v1";

function rkrkDigiAssessProgressEnabled() {
    if (typeof dgaExamCompleted !== "undefined" && dgaExamCompleted) return false;
    if (allquestions) return false;
    if (!set_quiz || NoQuiz == 1) return false;
    if (!dgaProgressApiUrl) return false;
    return true;
}

function rkrkDigiAssessShowFrageBild() {
    const cfg = String((Questionair && Questionair["FragenBildAnzeigen"]) || "Ja").trim().toLowerCase();
    if (cfg === "nein") {
        return false;
    }
    return !(typeof ohneBilderStart !== "undefined" && ohneBilderStart);
}

function rkrkDigiAssessExamLocked() {
    return String(typeof dgaExamCode !== "undefined" ? dgaExamCode : "").trim() !== "";
}

function rkrkDigiAssessIsPageReload() {
    try {
        const nav = performance.getEntriesByType("navigation");
        if (nav && nav[0] && nav[0].type === "reload") return true;
    } catch (eNav) {}
    try {
        if (performance.navigation && performance.navigation.type === 1) return true;
    } catch (eNav2) {}
    return false;
}

function rkrkDigiAssessSaveQuizSession() {
    if (!rkrkDigiAssessProgressEnabled()) return;
    const id = rkrkDigiAssessProgressIdentity();
    const st = rkrkDigiAssessGetProgressStatus();
    if (!id.quiz_key || !id.name) return;
    if (st !== "Quiz" && st !== "fertig") return;
    if (typeof sessionStorage === "undefined") return;
    try {
        sessionStorage.setItem(DGA_QUIZ_SESSION_KEY, JSON.stringify({
            quiz_key: id.quiz_key,
            name: id.name,
            drone: id.drone,
            setting: id.setting,
            schulung: id.schulung,
            quiz_modus: id.quiz_modus,
            status: st,
            saved_at: Date.now()
        }));
    } catch (eSs) {}
    rkrkDigiAssessSetProgressIdentityCookie();
}

function rkrkDigiAssessLoadQuizSession() {
    if (typeof sessionStorage === "undefined") return null;
    try {
        const raw = sessionStorage.getItem(DGA_QUIZ_SESSION_KEY);
        if (!raw) return null;
        const o = JSON.parse(raw);
        return o && typeof o === "object" ? o : null;
    } catch (eLd) {
        return null;
    }
}

function rkrkDigiAssessCookiePath() {
    const p = window.location.pathname || "/";
    const i = p.lastIndexOf("/");
    return i >= 0 ? p.substring(0, i + 1) : "/";
}

function rkrkDigiAssessSetProgressIdentityCookie() {
    if (!rkrkDigiAssessProgressEnabled()) return;
    const id = rkrkDigiAssessProgressIdentity();
    if (!id.quiz_key || !id.name) return;
    try {
        const json = encodeURIComponent(JSON.stringify(id));
        document.cookie = "rkrk_dga_progress_identity=" + json
            + ";path=" + rkrkDigiAssessCookiePath()
            + ";max-age=86400;SameSite=Lax";
    } catch (eCk) {}
}

function rkrkDigiAssessClearProgressIdentityCookie() {
    try {
        document.cookie = "rkrk_dga_progress_identity=;path=" + rkrkDigiAssessCookiePath()
            + ";max-age=0;SameSite=Lax";
    } catch (eCkCl) {}
}

function rkrkDigiAssessClearQuizSession() {
    if (typeof sessionStorage === "undefined") return;
    try {
        sessionStorage.removeItem(DGA_QUIZ_SESSION_KEY);
        sessionStorage.removeItem(DGA_QUIZ_PROGRESS_KEY);
    } catch (eCl) {}
    rkrkDigiAssessClearProgressIdentityCookie();
}

function rkrkDigiAssessShouldTryRestoreOnLoad() {
    if (rkrkDigiAssessIsPageReload()) return true;
    return rkrkDigiAssessSessionMatchesCurrent(rkrkDigiAssessLoadQuizSession());
}

function rkrkDigiAssessSaveProgressLocal() {
    if (!rkrkDigiAssessProgressEnabled()) return;
    const id = rkrkDigiAssessProgressIdentity();
    if (!id.quiz_key || !id.name) return;
    if (typeof sessionStorage === "undefined") return;
    try {
        sessionStorage.setItem(DGA_QUIZ_PROGRESS_KEY, JSON.stringify({
            identity: id,
            progress: rkrkDigiAssessCollectProgressPayload(),
            saved_at: Date.now()
        }));
    } catch (ePl) {}
    rkrkDigiAssessSaveQuizSession();
    rkrkDigiAssessSyncQuizUrl();
}

function rkrkDigiAssessOnQuizInteraction() {
    if (!dgaQuizProgressActive) return;
    rkrkDigiAssessSaveProgressLocal();
    rkrkDigiAssessScheduleProgressSave();
}

function rkrkDigiAssessBindProgressInputListeners() {
    if (window.__rkrkDgaProgressInputBound) return;
    window.__rkrkDgaProgressInputBound = true;
    document.addEventListener("keydown", function () {
        if (!dgaQuizProgressActive) return;
        rkrkDigiAssessOnQuizInteraction();
    });
    document.addEventListener("click", function (ev) {
        if (!dgaQuizProgressActive || !ev || !ev.target) return;
        const t = ev.target;
        if (t.name === "response") {
            rkrkDigiAssessOnQuizInteraction();
            return;
        }
        const id = t.id || "";
        if (id === "prev_button" || id === "next_button" || id === "submit_button" || id === "submit"
            || id === "change_button" || id === "change_answer") {
            rkrkDigiAssessOnQuizInteraction();
            return;
        }
        if (t.closest && t.closest("#result")) {
            rkrkDigiAssessOnQuizInteraction();
        }
    });
}

function rkrkDigiAssessStrEqualFold(a, b) {
    return String(a == null ? "" : a).trim().toLowerCase() === String(b == null ? "" : b).trim().toLowerCase();
}

function rkrkDigiAssessNormalizeDroneForMatch(d) {
    let s = String(d == null ? "" : d).trim();
    if (s === "" || s.toLowerCase() === "alle") {
        return "";
    }
    return s.toLowerCase();
}

function rkrkDigiAssessDroneRequiredForProgress() {
    return typeof hasDroneTypes !== "undefined" && !!hasDroneTypes;
}

function rkrkDigiAssessProgressIdentityMatches(id, saved) {
    const cur = id || rkrkDigiAssessProgressIdentity();
    const src = saved || {};
    const qk = String(src.quiz_key != null ? src.quiz_key : "").trim();
    const nm = String(src.name != null ? src.name : "").trim();
    if (!cur.quiz_key || !cur.name || !qk || !nm) {
        return false;
    }
    if (!rkrkDigiAssessStrEqualFold(cur.quiz_key, qk)) {
        return false;
    }
    if (!rkrkDigiAssessStrEqualFold(cur.name, nm)) {
        return false;
    }
    const curD = rkrkDigiAssessNormalizeDroneForMatch(cur.drone);
    const savD = rkrkDigiAssessNormalizeDroneForMatch(src.drone);
    const needDrone = rkrkDigiAssessDroneRequiredForProgress() || curD !== "" || savD !== "";
    if (needDrone && (curD === "" || savD === "" || curD !== savD)) {
        return false;
    }
    return true;
}

function rkrkDigiAssessSessionMatchesCurrent(sess) {
    if (!sess) return false;
    if (String(sess.schulung || "") !== String(Schulung || "")) return false;
    if (String(sess.quiz_modus || "") !== String(quizModus || "")) return false;
    return rkrkDigiAssessProgressIdentityMatches(rkrkDigiAssessProgressIdentity(), sess);
}

function rkrkDigiAssessApplyStoredIdentityBeforeProgressCheck() {
    const sess = rkrkDigiAssessLoadQuizSession();
    if (rkrkDigiAssessSessionMatchesCurrent(sess)) {
        rkrkDigiAssessApplySessionToForm(sess);
        return;
    }
    if (!rkrkDigiAssessShouldTryRestoreOnLoad() || typeof sessionStorage === "undefined") return;
    try {
        const raw = sessionStorage.getItem(DGA_QUIZ_PROGRESS_KEY);
        if (!raw) return;
        const pack = JSON.parse(raw);
        if (!pack || !pack.identity) return;
        const lid = pack.identity;
        if (String(lid.schulung || "") !== String(Schulung || "")) return;
        if (String(lid.quiz_modus || "") !== String(quizModus || "")) return;
        const cur = rkrkDigiAssessProgressIdentity();
        if (!rkrkDigiAssessProgressIdentityMatches(cur, lid)) {
            return;
        }
        if (!pack.progress || !rkrkDigiAssessProgressIdentityMatches(cur, pack.progress)) {
            return;
        }
        const nameEl = document.getElementById("Name");
        const hasName = nameEl && String(nameEl.value || "").trim() !== "";
        if (hasName && !rkrkDigiAssessStrEqualFold(nameEl.value, lid.name)) {
            return;
        }
        rkrkDigiAssessApplySessionToForm({
            name: lid.name,
            drone: lid.drone,
            status: pack.progress ? pack.progress.status : "Quiz"
        });
        rkrkDigiAssessSetProgressIdentityCookie();
    } catch (eId) {}
}

function rkrkDigiAssessApplySessionToForm(sess) {
    if (!sess) return;
    const nameEl = document.getElementById("Name");
    if (nameEl && sess.name && !String(nameEl.value || "").trim()) {
        nameEl.value = sess.name;
        Name = sess.name;
    }
    const droneEl = document.getElementById("Drone");
    if (droneEl && sess.drone && !String(droneEl.value || "").trim()) {
        droneEl.value = sess.drone;
        SelectedDrone = String(sess.drone);
    }
    const st = String(sess.status || "Quiz");
    const stEl = document.getElementById("Status");
    if (stEl && (st === "Quiz" || st === "fertig")) {
        stEl.value = st;
        Status = st;
    }
}

function rkrkDigiAssessSyncQuizUrl() {
    if (!set_quiz || !rkrkDigiAssessProgressEnabled()) return;
    const st = rkrkDigiAssessGetProgressStatus();
    if (st !== "Quiz" && st !== "fertig") return;
    try {
        const u = new URL(window.location.href);
        u.searchParams.set("quiz", set_quiz);
        u.searchParams.set("Status", "Quiz");
        if (Schulung) {
            u.searchParams.set("Schulung", Schulung);
        }
        if (quizModus) {
            u.searchParams.set("QuizModus", quizModus);
        }
        if (rkrkDigiAssessExamLocked()) {
            u.searchParams.set("EXAM", String(dgaExamCode));
        }
        history.replaceState({ rkrkDgaQuiz: 1 }, "", u.pathname + u.search + u.hash);
    } catch (eUrl) {}
}

function rkrkDigiAssessBeaconSaveProgress() {
    if (!dgaQuizProgressActive || !rkrkDigiAssessProgressEnabled()) return;
    const id = rkrkDigiAssessProgressIdentity();
    if (!id.quiz_key || !id.name) return;
    rkrkDigiAssessSaveProgressLocal();
    if (!navigator.sendBeacon) return;
    try {
        const sep = dgaProgressApiUrl.indexOf("?") >= 0 ? "&" : "?";
        const url = dgaProgressApiUrl + sep + "action=save";
        const payload = Object.assign({}, id, rkrkDigiAssessCollectProgressPayload());
        const blob = new Blob([JSON.stringify(payload)], { type: "application/json" });
        navigator.sendBeacon(url, blob);
    } catch (eBc) {}
}

function rkrkDigiAssessGetSelectedDrone() {
    const droneEl = document.getElementById("Drone");
    if (droneEl) {
        const v = String(droneEl.value || "").trim();
        if (v !== "") {
            return v;
        }
    }
    return String(typeof SelectedDrone !== "undefined" ? SelectedDrone : "").trim();
}

function rkrkDigiAssessSyncSelectedDrone() {
    const v = rkrkDigiAssessGetSelectedDrone();
    if (v !== "") {
        SelectedDrone = v;
    }
    return v;
}

function rkrkDigiAssessProgressIdentity() {
    const nameEl = document.getElementById("Name");
    return {
        quiz_key: String(set_quiz || ""),
        name: nameEl ? String(nameEl.value || "").trim() : String(Name || ""),
        drone: rkrkDigiAssessGetSelectedDrone(),
        setting: String(setting || ""),
        schulung: String(Schulung || ""),
        quiz_modus: String(quizModus || ""),
        exam_code: rkrkDigiAssessExamLocked() ? String(dgaExamCode) : ""
    };
}

function rkrkDigiAssessGetProgressStatus() {
    const st = document.getElementById("Status");
    return st ? String(st.value || "") : String(Status || "");
}

async function rkrkDigiAssessProgressApi(action, body) {
    if (!rkrkDigiAssessProgressEnabled()) return null;
    const payload = Object.assign({}, rkrkDigiAssessProgressIdentity(), body || {});
    const sep = dgaProgressApiUrl.indexOf("?") >= 0 ? "&" : "?";
    const url = dgaProgressApiUrl + sep + "action=" + encodeURIComponent(action);
    try {
        const resp = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify(payload)
        });
        if (!resp.ok) return null;
        return await resp.json();
    } catch (eApi) {
        return null;
    }
}

function rkrkDigiAssessCollectQuestionSnapshot(q, idx) {
    const qid = String(q.QID != null ? q.QID : idx);
    let opts = q.Loesungsauswahl;
    if (!Array.isArray(opts)) {
        opts = opts ? String(opts).split("|").map(function (s) { return s.trim(); }).filter(Boolean) : [];
    }
    return {
        qid: qid,
        Frage: rkrkDigiAssessFixMojibake(q.Frage == null ? "" : q.Frage),
        Loesungsauswahl: opts,
        Loesung: String(q.Loesung == null ? "" : q.Loesung),
        Loesungstext: String(q.Loesungstext == null ? "" : q.Loesungstext),
        AntwortText: String(q.AntwortText == null ? "" : q.AntwortText),
        "Frage-Bild": rkrkQuizImageBasename(q["Frage-Bild"]),
        "Loesungs-Bild": rkrkQuizImageBasename(q["Loesungs-Bild"]),
        Bildformat: String(q.Bildformat == null ? "" : q.Bildformat),
        Zeit: String(q.Zeit == null ? "" : q.Zeit)
    };
}

function rkrkDigiAssessCollectLayoutPayload() {
    const order = [];
    const snaps = [];
    for (let idx = 0; idx < MaxQuestions && idx < Questions.length; idx++) {
        const snap = rkrkDigiAssessCollectQuestionSnapshot(Questions[idx], idx);
        order.push(snap.qid);
        snaps.push(snap);
    }
    return {
        question_order: order,
        question_snapshots: snaps
    };
}

function rkrkDigiAssessQuestionFromSnapshot(snap) {
    let opts = snap.Loesungsauswahl;
    if (!Array.isArray(opts)) {
        opts = opts ? String(opts).split("|").map(function (s) { return s.trim(); }).filter(Boolean) : [];
    }
    return {
        QID: String(snap.qid == null ? "" : snap.qid),
        Frage: rkrkDigiAssessFixMojibake(snap.Frage == null ? "" : snap.Frage),
        Loesungsauswahl: opts,
        Loesung: String(snap.Loesung == null ? "" : snap.Loesung),
        Loesungstext: String(snap.Loesungstext == null ? "" : snap.Loesungstext),
        AntwortText: String(snap.AntwortText == null ? "" : snap.AntwortText),
        "Frage-Bild": rkrkQuizImageBasename(snap["Frage-Bild"]),
        "Loesungs-Bild": rkrkQuizImageBasename(snap["Loesungs-Bild"]),
        Bildformat: String(snap.Bildformat == null ? "" : snap.Bildformat),
        Zeit: String(snap.Zeit == null ? "" : snap.Zeit),
        Antwort: "",
        Locked: "Nein"
    };
}

function rkrkDigiAssessRestoreQuizLayout(progress) {
    if (!progress) return false;
    const snaps = Array.isArray(progress.question_snapshots) ? progress.question_snapshots : [];
    if (!snaps.length) return false;

    const byQid = {};
    snaps.forEach(function (snap) {
        if (!snap) return;
        const qid = String(snap.qid == null ? "" : snap.qid).trim();
        if (qid) byQid[qid] = snap;
    });

    let order = Array.isArray(progress.question_order) ? progress.question_order : [];
    order = order.map(function (qid) { return String(qid == null ? "" : qid).trim(); }).filter(Boolean);
    if (!order.length) {
        order = snaps.map(function (snap) { return String(snap.qid == null ? "" : snap.qid).trim(); }).filter(Boolean);
    }

    const rebuilt = [];
    order.forEach(function (qid) {
        if (!byQid[qid]) return;
        rebuilt.push(rkrkDigiAssessQuestionFromSnapshot(byQid[qid]));
    });
    if (!rebuilt.length) return false;

    Questions.length = 0;
    rebuilt.forEach(function (q) { Questions.push(q); });
    Questions.forEach(function (q) {
        q["Frage-Bild"] = rkrkQuizImageBasename(q["Frage-Bild"]);
        q["Loesungs-Bild"] = rkrkQuizImageBasename(q["Loesungs-Bild"]);
    });

    const mq = parseInt(progress.max_questions, 10);
    if (!isNaN(mq) && mq > 0) {
        MaxQuestions = Math.min(mq, Questions.length);
    } else {
        MaxQuestions = Questions.length;
    }

    const fpSaved = String(progress.question_fingerprint || "").trim();
    if (fpSaved) {
        dgaQuestionFingerprint = fpSaved;
    }
    return true;
}

function rkrkDigiAssessCollectProgressPayload() {
    const qRows = [];
    (Questions || []).forEach((q, idx) => {
        if (idx >= MaxQuestions) return;
        qRows.push({
            qid: String(q.QID != null ? q.QID : idx),
            idx: idx,
            antwort: String(q.Antwort == null ? "" : q.Antwort),
            locked: rkrkDigiAssessProgressLockedForSave(q, idx)
        });
    });
    const layout = rkrkDigiAssessCollectLayoutPayload();
    return {
        question_fingerprint: String(dgaQuestionFingerprint || ""),
        max_questions: MaxQuestions,
        at_question: AtQuestion,
        quiz_timer_remaining: Math.max(0, Math.floor(quizTimerRemainingSec || 0)),
        exam_finish_confirmed: !!examFinishConfirmed,
        status: rkrkDigiAssessGetProgressStatus() || "Quiz",
        learning_skipped: true,
        questions: qRows,
        question_order: layout.question_order,
        question_snapshots: layout.question_snapshots,
        drive_location: String(typeof dgaDriveLocation !== "undefined" ? dgaDriveLocation : "")
    };
}

function rkrkDigiAssessScheduleProgressSave() {
    if (!dgaQuizProgressActive) return;
    if (dgaProgressSavePending) return;
    dgaProgressSavePending = true;
    setTimeout(function () {
        dgaProgressSavePending = false;
        rkrkDigiAssessSaveProgressNow();
    }, 150);
}

async function rkrkDigiAssessSaveProgressNow() {
    if (!dgaQuizProgressActive || !rkrkDigiAssessProgressEnabled()) return;
    const id = rkrkDigiAssessProgressIdentity();
    if (!id.quiz_key || !id.name) return;
    rkrkDigiAssessSaveProgressLocal();
    const res = await rkrkDigiAssessProgressApi("save", rkrkDigiAssessCollectProgressPayload());
    const el = document.getElementById("dga-progress-status");
    if (el && res && res.ok && res.saved_at) {
        try {
            const d = new Date(res.saved_at);
            el.textContent = "Fortschritt gespeichert um " + d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
        } catch (eDt) {
            el.textContent = "Fortschritt gespeichert.";
        }
    }
}

async function rkrkDigiAssessDeleteProgress() {
    rkrkDigiAssessProgressStopAutosave();
    rkrkDigiAssessClearQuizSession();
    if (!rkrkDigiAssessProgressEnabled()) return;
    await rkrkDigiAssessProgressApi("delete", {});
}

function rkrkDigiAssessProgressStartAutosave() {
    if (!rkrkDigiAssessProgressEnabled()) return;
    dgaQuizProgressActive = true;
    rkrkDigiAssessBindProgressInputListeners();
    rkrkDigiAssessSaveProgressLocal();
    rkrkDigiAssessSaveProgressNow();
}

function rkrkDigiAssessProgressStopAutosave() {
    dgaQuizProgressActive = false;
    if (dgaProgressAutosaveTimer) {
        clearInterval(dgaProgressAutosaveTimer);
        dgaProgressAutosaveTimer = null;
    }
}

function rkrkDigiAssessQuestionIndexByQid(qid) {
    const want = String(qid == null ? "" : qid).trim();
    if (!want) return -1;
    for (let i = 0; i < Questions.length && i < MaxQuestions; i++) {
        const qQid = String(Questions[i].QID != null ? Questions[i].QID : i).trim();
        if (qQid === want) return i;
    }
    if (/^\d+$/.test(want)) {
        const wantNum = parseInt(want, 10);
        for (let i = 0; i < Questions.length && i < MaxQuestions; i++) {
            const qQid = String(Questions[i].QID != null ? Questions[i].QID : i).trim();
            if (/^\d+$/.test(qQid) && parseInt(qQid, 10) === wantNum) return i;
        }
    }
    return -1;
}

function rkrkDigiAssessClearQuestionQuizState(idx) {
    if (idx < 0 || !Questions[idx]) return;
    Questions[idx].Antwort = "";
    Questions[idx].Locked = "Nein";
}

function rkrkDigiAssessIsQuestionAnsweredInQuiz(qu) {
    if (!qu) return false;
    return String(qu.Antwort == null ? "" : qu.Antwort).trim() !== ""
        && rkrkDigiAssessQuestionLockedJa(qu);
}

function rkrkDigiAssessProgressRowIsAnswered(row) {
    if (!row) return false;
    const ant = String(row.antwort == null ? "" : row.antwort).trim();
    return ant !== "" && rkrkDigiAssessNormalizeLockedValue(row.locked) === "Ja";
}

function rkrkDigiAssessApplyProgress(progress) {
    if (!progress || !Array.isArray(progress.questions)) return false;
    if (!rkrkDigiAssessProgressIdentityMatches(rkrkDigiAssessProgressIdentity(), progress)) {
        return false;
    }
    let atQSaved = parseInt(progress.at_question, 10);
    if (isNaN(atQSaved) || atQSaved < 0) {
        atQSaved = 0;
    }
    if (atQSaved >= MaxQuestions) {
        atQSaved = Math.max(0, MaxQuestions - 1);
    }

    const fpSaved = String(progress.question_fingerprint || "");
    const fpMatch = !fpSaved || !dgaQuestionFingerprint || fpSaved === dgaQuestionFingerprint;

    for (let i = 0; i < MaxQuestions && i < Questions.length; i++) {
        rkrkDigiAssessClearQuestionQuizState(i);
    }

    let appliedCount = 0;
    const usedIdx = {};
    progress.questions.forEach(function (row) {
        if (!row) return;
        let idx = rkrkDigiAssessQuestionIndexByQid(row.qid);
        if (idx < 0 && fpMatch && row.idx != null && !isNaN(parseInt(row.idx, 10))) {
            const savedIdx = parseInt(row.idx, 10);
            if (savedIdx >= 0 && savedIdx < MaxQuestions) {
                idx = savedIdx;
            }
        }
        if (idx < 0 || idx >= MaxQuestions || usedIdx[idx]) return;
        usedIdx[idx] = true;

        const ant = String(row.antwort == null ? "" : row.antwort);
        const antTrim = ant.trim();
        const locked = rkrkDigiAssessNormalizeLockedValue(row.locked);

        if (locked === "Ja" && antTrim !== "") {
            Questions[idx].Antwort = ant;
            Questions[idx].Locked = "Ja";
            appliedCount++;
        } else if (idx === atQSaved && antTrim !== "") {
            Questions[idx].Antwort = ant;
            Questions[idx].Locked = "Nein";
        }
    });

    if (appliedCount === 0) {
        const anyStarted = !!progress.started
            || String(progress.status || "") === "Quiz"
            || String(progress.status || "") === "fertig"
            || atQSaved > 0;
        if (!anyStarted) {
            return false;
        }
    }
    let atQ = atQSaved;
    if (isNaN(atQ) || atQ < 0) atQ = 0;
    if (atQ >= MaxQuestions) atQ = Math.max(0, MaxQuestions - 1);
    AtQuestion = atQ;
    if (progress.exam_finish_confirmed) {
        examFinishConfirmed = true;
    }
    const rem = parseInt(progress.quiz_timer_remaining, 10);
    if (!isNaN(rem) && rem > 0) {
        quizTimerRemainingSec = rem;
        quizTimerDuration = rem;
    }
    const st = document.getElementById("Status");
    const pStatus = String(progress.status || "Quiz");
    if (st && pStatus && pStatus !== "resultat") {
        st.value = pStatus;
        Status = pStatus;
    }
    if (progress.drone) {
        const de = document.getElementById("Drone");
        if (de) {
            de.value = String(progress.drone);
        }
        SelectedDrone = String(progress.drone);
    }
    dgaProgressResumeApplied = true;
    if (document.getElementById("result")) {
        rkrkDigiAssessRebuildQuestionNav();
    }
    return true;
}

function rkrkDigiAssessFormatProgressTime(iso) {
    if (!iso) return "";
    try {
        return new Date(iso).toLocaleString([], { dateStyle: "medium", timeStyle: "short" });
    } catch (eFmt) {
        return String(iso);
    }
}

function rkrkDigiAssessShowResumeDialog(progress, answeredCount) {
    return new Promise((resolve) => {
        const existing = document.getElementById("rkrk-progress-overlay");
        if (existing) existing.remove();
        const overlay = document.createElement("div");
        overlay.id = "rkrk-progress-overlay";
        overlay.className = "rkrk-progress-overlay";
        const updated = rkrkDigiAssessFormatProgressTime(progress.updated_at || "");
        const label = Schulung === "Schulung" ? "Schulungsprüfung" : "Quiz";
        const answeredTxt = answeredCount > 0
            ? ("Bereits " + answeredCount + " Frage(n) beantwortet. ")
            : "Das Quiz wurde bereits begonnen. ";
        const examLocked = rkrkDigiAssessExamLocked();
        const restartBtn = examLocked ? "" :
            "<button type=\"button\" class=\"rkrk-progress-restart\" style=\"border-radius:3px;border:1px solid #cbd5e1;background:#fff;padding:10px 14px;font-size:16px;cursor:pointer;\">Neues Quiz starten</button>";
        overlay.innerHTML =
            "<div class=\"rkrk-progress-card\" role=\"dialog\" aria-modal=\"true\" aria-labelledby=\"rkrk-progress-title\">" +
            "<h3 id=\"rkrk-progress-title\">Unvollständiges " + label + "</h3>" +
            "<p>Es wurde ein gespeicherter Fortschritt gefunden" +
            (updated ? (" (zuletzt " + rkrkDigiAssessEscapeHtml(updated) + ")") : "") + ". " +
            answeredTxt +
            (examLocked
                ? "Dieser Fragebogen ist über den Prüfungs-Code festgelegt und kann nicht neu gemischt werden."
                : "Möchtest du dieses Quiz laden oder ein neues starten?") +
            "</p>" +
            "<div class=\"rkrk-progress-actions\">" +
            "<button type=\"button\" class=\"rkrk-progress-resume\" style=\"" + (rkrkDgaPrimaryButtonStyle || "") + "\">Gespeichertes Quiz laden</button>" +
            restartBtn +
            "</div></div>";
        document.body.appendChild(overlay);
        overlay.querySelector(".rkrk-progress-resume").addEventListener("click", function () {
            overlay.remove();
            resolve("resume");
        });
        const restartEl = overlay.querySelector(".rkrk-progress-restart");
        if (restartEl) {
            restartEl.addEventListener("click", function () {
                overlay.remove();
                resolve("restart");
            });
        }
    });
}

function rkrkDigiAssessCountAnsweredInProgress(progress) {
    let n = 0;
    const rows = progress && Array.isArray(progress.questions) ? progress.questions : [];
    rows.forEach(function (row) {
        if (rkrkDigiAssessProgressRowIsAnswered(row)) n++;
    });
    return n;
}

function rkrkDigiAssessFindLocalProgressPack() {
    if (!rkrkDigiAssessProgressEnabled() || typeof sessionStorage === "undefined") return null;
    const id = rkrkDigiAssessProgressIdentity();
    if (!id.quiz_key || !id.name) return null;
    try {
        const raw = sessionStorage.getItem(DGA_QUIZ_PROGRESS_KEY);
        if (!raw) return null;
        const pack = JSON.parse(raw);
        if (!pack || !pack.identity || !pack.progress) return null;
        const lid = pack.identity;
        if (String(lid.schulung || "") !== String(Schulung || "")) return null;
        if (String(lid.quiz_modus || "") !== String(quizModus || "")) return null;
        if (!rkrkDigiAssessProgressIdentityMatches(id, lid)) return null;
        if (!pack.progress || !rkrkDigiAssessProgressIdentityMatches(id, pack.progress)) return null;
        return pack;
    } catch (eLoc) {
        return null;
    }
}

async function rkrkDigiAssessFindExistingProgress() {
    if (!rkrkDigiAssessProgressEnabled()) return null;
    const id = rkrkDigiAssessProgressIdentity();
    if (!id.quiz_key || !id.name) return null;

    const res = await rkrkDigiAssessProgressApi("check", {
        question_fingerprint: String(dgaQuestionFingerprint || "")
    });
    if (res && res.has_progress && res.progress) {
        if (res.fingerprint_mismatch || res.identity_mismatch) {
            return null;
        }
        if (!rkrkDigiAssessProgressIdentityMatches(id, res.progress)) {
            return null;
        }
        return {
            progress: res.progress,
            answered_count: res.answered_count || rkrkDigiAssessCountAnsweredInProgress(res.progress)
        };
    }

    const localPack = rkrkDigiAssessFindLocalProgressPack();
    if (!localPack || !localPack.progress) return null;
    const fpLocal = String(localPack.progress.question_fingerprint || "").trim();
    if (fpLocal && dgaQuestionFingerprint && fpLocal !== dgaQuestionFingerprint) {
        return null;
    }
    const answered = rkrkDigiAssessCountAnsweredInProgress(localPack.progress);
    const started = !!localPack.progress.started
        || answered > 0
        || String(localPack.progress.status || "") === "Quiz"
        || String(localPack.progress.status || "") === "fertig";
    if (!started) return null;

    return {
        progress: Object.assign({}, localPack.progress, {
            updated_at: localPack.saved_at
                ? new Date(localPack.saved_at).toISOString()
                : (localPack.progress.updated_at || "")
        }),
        answered_count: answered
    };
}

async function rkrkDigiAssessMaybeOfferResume() {
    if (!rkrkDigiAssessProgressEnabled()) return "none";
    const id = rkrkDigiAssessProgressIdentity();
    if (!id.quiz_key || !id.name) return "none";
    if (rkrkDigiAssessDroneRequiredForProgress() && !rkrkDigiAssessNormalizeDroneForMatch(id.drone)) {
        return "none";
    }
    const found = await rkrkDigiAssessFindExistingProgress();
    if (!found || !found.progress) return "none";
    if (!rkrkDigiAssessProgressIdentityMatches(id, found.progress)) return "none";
    const fpSaved = String(found.progress.question_fingerprint || "").trim();
    if (fpSaved && dgaQuestionFingerprint && fpSaved !== dgaQuestionFingerprint) {
        return "none";
    }
    const choice = await rkrkDigiAssessShowResumeDialog(found.progress, found.answered_count || 0);
    if (choice === "restart") {
        await rkrkDigiAssessDeleteProgress();
        dgaProgressResumeApplied = false;
        return "restart";
    }
    if (choice === "resume") {
        rkrkDigiAssessRestoreQuizLayout(found.progress);
        if (!rkrkDigiAssessApplyProgress(found.progress)) {
            await rkrkDigiAssessDeleteProgress();
            return "restart";
        }
        rkrkDigiAssessSaveProgressLocal();
        return "resume";
    }
    return "cancel";
}

async function rkrkDigiAssessInitQuizEntry(enableLearning) {
    if (dgaProgressResumeApplied) {
        ShowLearninhaltOrQuestion(enableLearning);
        return;
    }

    if (rkrkDigiAssessProgressEnabled()) {
        if (rkrkDigiAssessShouldTryRestoreOnLoad() || Status === "Quiz") {
            rkrkDigiAssessApplyStoredIdentityBeforeProgressCheck();
        }

        const inQuizContext = (Status === "Quiz" || rkrkDigiAssessGetProgressStatus() === "Quiz");
        const shouldCheckProgress = rkrkDigiAssessShouldTryRestoreOnLoad() || inQuizContext;

        if (shouldCheckProgress && rkrkDigiAssessProgressIdentity().name) {
            const offer = await rkrkDigiAssessMaybeOfferResume();
            if (offer === "cancel") {
                const stEl = document.getElementById("Status");
                if (stEl) stEl.value = "";
                Status = "";
                ShowNameForm();
                return;
            }
            if (offer === "resume") {
                rkrkDigiAssessSaveQuizSession();
                rkrkDigiAssessSyncQuizUrl();
                ShowLearninhaltOrQuestion(enableLearning);
                return;
            }
            if (offer === "restart") {
                if (rkrkDigiAssessExamLocked()) {
                    const stEl = document.getElementById("Status");
                    if (stEl) stEl.value = "Quiz";
                    Status = "Quiz";
                } else if (rkrkDigiAssessIsPageReload()) {
                    window.location.reload();
                    return;
                }
                const stEl = document.getElementById("Status");
                if (stEl) stEl.value = "Quiz";
                Status = "Quiz";
            }
        }
    }

    ShowLearninhaltOrQuestion(enableLearning);
}

function rkrkDigiAssessClearQuizSessionStorage() {
    if (typeof sessionStorage !== "undefined") {
        try {
            sessionStorage.removeItem("rkrk_dga_ergebnis_fragen_v1");
        } catch (eRm) {}
    }
}

// =====================================
// WICHTIG: StartQuiz() – korrigiert
// =====================================
async function StartQuiz() {

    if (typeof dgaExamContextMismatch !== "undefined" && dgaExamContextMismatch) {
        return;
    }

    if (typeof dgaExamCompleted !== "undefined" && dgaExamCompleted) {
        let detailFragen = Array.isArray(quizErgebnisFragen) ? quizErgebnisFragen.slice() : [];
        rkrkDigiAssessShowResultOverview(detailFragen);
        return;
    }

    let name = document.getElementById("Name").value.trim();
    let droneElement = document.getElementById("Drone");
    let drone = droneElement ? droneElement.value : "";

    if (name === "") {
        document.getElementById("indrodution").innerHTML +=
            "<div style='color:red; margin-top:10px;'>Bitte gib deinen Namen ein.</div>";
        return;
    }

    if (droneElement && drone === "") {
        document.getElementById("indrodution").innerHTML +=
            "<div style='color:red; margin-top:10px;'>Bitte wähle deine Drohne.</div>";
        return;
    }

    if (drone) {
        SelectedDrone = drone;
    }

    if (rkrkDigiAssessProgressEnabled()) {
        const offer = await rkrkDigiAssessMaybeOfferResume();
        if (offer === "cancel") return;
        if (offer === "resume") {
            document.getElementById("Status").value = "Quiz";
            Status = "Quiz";
            rkrkDigiAssessClearQuizSessionStorage();
            rkrkDigiAssessSaveQuizSession();
            rkrkDigiAssessSyncQuizUrl();
            const enableLearning = !(Schulung === "Schulung" && ohneLerntextStart);
            ShowLearninhaltOrQuestion(enableLearning);
            return;
        }
        if (offer === "restart") {
            if (rkrkDigiAssessExamLocked()) {
                return;
            }
            rkrkDigiAssessClearQuizSessionStorage();
            rkrkDigiAssessClearQuizSession();
        }
    }

    document.getElementById("Status").value = "Quiz";
    rkrkDigiAssessClearQuizSessionStorage();
    rkrkDigiAssessClearQuizSession();
    document.forms[0].submit();
}

// =====================================
// Setup
// =====================================
DoSetup();

async function DoSetup() {

    if (typeof dgaExamContextMismatch !== "undefined" && dgaExamContextMismatch) {
        document.getElementById("quiz-titel").innerHTML = rkrkDigiAssessEscapeHtml(rkrkDigiAssessQuizTitle() || "Schulungsprüfung");
        document.getElementById("indrodution").innerHTML =
            "<div style='color:#b91c1c;margin-top:10px;max-width:520px;'>" +
            "Der Prüfungs-Code <strong>" + rkrkDigiAssessEscapeHtml(String(dgaExamCode || "")) + "</strong> " +
            "passt nicht zu diesem Quiz-Aufruf (anderes Quiz oder andere Einstellungen). " +
            "Bitte den korrekten Link verwenden." +
            "</div>";
        document.getElementById("indrodution").style.fontSize = "12pt";
        document.getElementById("rkrk-card").style.display = "none";
        document.getElementById("rkrk-sidebar").style.display = "none";
        document.getElementById("rkrk-solution").style.display = "none";
        document.getElementById("rkrk-main").style.display = "none";
        setTopbarQuickLinksVisible(true);
        return;
    }

    if (Status == 'resultat') {
        let detailFragen = Array.isArray(quizErgebnisFragen) ? quizErgebnisFragen.slice() : [];
        const detailLooksIncomplete = function (rows) {
            if (!Array.isArray(rows) || !rows.length) return true;
            const r0 = rows[0] || {};
            return (r0.antwortLabel == null && r0.antwort == null && r0.loesung == null && r0.loesungLabel == null);
        };
        if (detailLooksIncomplete(detailFragen) && typeof sessionStorage !== "undefined") {
            try {
                const rawSs = sessionStorage.getItem("rkrk_dga_ergebnis_fragen_v1");
                if (rawSs) {
                    const parsedSs = JSON.parse(rawSs);
                    if (Array.isArray(parsedSs) && parsedSs.length) {
                        detailFragen = parsedSs;
                    }
                }
            } catch (eSs) {}
        }

        rkrkDigiAssessShowResultOverview(detailFragen);
        if (typeof sessionStorage !== "undefined") {
            try {
                sessionStorage.removeItem("rkrk_dga_ergebnis_fragen_v1");
            } catch (eCl) {}
        }
        return;
    }

    if ((set_quiz == "") || (NoQuiz == 1)) {
        document.getElementById("quiz-titel").innerHTML = Schulung ? titleSchulungHub : titleQuizHub;

        ShowQuizList();
        return;
    }

    document.getElementById("quiz-titel").innerHTML = rkrkDigiAssessEscapeHtml(rkrkDigiAssessQuizTitle());
    document.getElementById("indrodution").style.fontSize = "10pt";

    rkrkDigiAssessApplyNavLabels();
    await rkrkDigiAssessInitQuizEntry(!(Schulung === "Schulung" && ohneLerntextStart));
}

// Header-Navigation (Assessment / Admin / …): bei Namenseingabe ausblenden
function setTopbarQuickLinksVisible(show) {
    var el = document.getElementById("rkrk-topbar-quicklinks");
    if (el) el.style.display = show ? "" : "none";
    var slot = document.getElementById("rkrk-portal-nav-slot");
    if (slot) slot.style.display = show ? "" : "none";
}

// =====================================
// Name-Formular
// =====================================
function ShowNameForm() {

    let droneText = "";
    if (<?php echo $HasDroneTypes ? "true" : "false"; ?>) {
        droneText = " und deine Drohne";
    }

    const startQuizLabel = Schulung ? "Weiter zur Schulungsprüfung" : "Quiz starten";
    const dgaBtn = (typeof rkrkDgaPrimaryButtonStyle === "string" && rkrkDgaPrimaryButtonStyle) ? rkrkDgaPrimaryButtonStyle : "border-radius:3px;border:none;color:#fff;background:var(--brand);padding:10px;font-size:16px;cursor:pointer;";
    const startBtnStyle = dgaBtn.replace(/background\s*:[^;]*;?/i, "") + ";background:#c1121f;";
    document.getElementById("indrodution").innerHTML = `
        <div class="rkrk-name-intro" style="padding:14px 16px;background:var(--kpi-bg);border:1px solid var(--kpi-border);border-radius:var(--radius);max-width:420px;box-shadow:var(--shadow);">
            <h3 style="margin-top:0;color:var(--text);font-size:1.1rem;">Bitte gib deinen Namen${droneText} ein</h3>
            <p style="margin:0.5em 0 1em;color:var(--text);opacity:.9;font-size:0.95rem;">${droneText ? "Beides ist notwendig, um zu starten." : (Schulung ? "Bitte gib deinen Namen ein, um mit der Schulungsprüfung zu beginnen." : "Bitte gib deinen Namen ein, um das Quiz zu starten.")}</p>
            <button type="button" onclick="StartQuiz()" style="${startBtnStyle}">
                ${startQuizLabel}
            </button>
        </div>
    `;

    document.getElementById("rkrk-main").style.display = "block";
    document.getElementById("Name").style.display = "block";
    document.getElementById("Name-Label").style.display = "block";

    document.getElementById("rkrk-card").style.display = "none";
    document.getElementById("rkrk-sidebar").style.display = "none";
	document.getElementById("rkrk-solution").style.display = "none";
    if (setting != "TEST") {
		document.getElementById("rkrk-debug").style.display = "none";
	}
	
    let d = document.getElementById("Drone");
    let dl = document.getElementById("Drone-Label");
    let dWrap = document.getElementById("rkrk-drone-field");
    if (dWrap) dWrap.style.display = "";
    if (d) {
        if (d.tagName === "SELECT") {
            d.style.display = "block";
            if (!d.dataset.rkrkDroneBound) {
                d.dataset.rkrkDroneBound = "1";
                d.addEventListener("change", function () {
                    SelectedDrone = String(d.value || "");
                    const nm = document.getElementById("Name").value.trim();
                    if (nm !== "" && String(d.value || "").trim() !== "") {
                        document.getElementById("Name-Label").style.display = "none";
                        document.getElementById("Name").style.display = "none";
                        rkrkDigiAssessHideDronePickerIfSelected();
                        rkrkDigiAssessRefreshTeilnehmerinfo({ welcome: false });
                    }
                });
            }
        }
    }
    if (dl) dl.style.display = "block";

    rkrkDigiAssessSyncSelectedDrone();
    const namePrefilled = document.getElementById("Name").value.trim() !== "";
    if (namePrefilled) {
        document.getElementById("Name-Label").style.display = "none";
        document.getElementById("Name").style.display = "none";
        rkrkDigiAssessRefreshTeilnehmerinfo({ welcome: false });
    }
    rkrkDigiAssessHideDronePickerIfSelected();

    setTopbarQuickLinksVisible(false);
    ShowHideQuestionFields('none');
}

function ShowNameFormAfterLearning() {

	ShowNameForm()
    
	// Lerninhalt ausblenden
    document.getElementById("learning").innerHTML = "";
    document.getElementById("submit_learn").style.display = "none";

    // Fragenfelder ausblenden
    ShowHideQuestionFields('none');

/*
    // Headerblock wieder anzeigen
    document.getElementById("quiz-titel").style.display = "block";
    document.getElementById("indrodution").style.display = "block";
    document.getElementById("logopic").style.display = "block";

    // Resultat-Titel ausblenden (sonst überschreibt er den Header)
    document.getElementById("result-titel").style.display = "none";
*/
}

function ShowLearninhaltOrQuestion(EnableLearning) {

    if ((LearningContent != "") && (EnableLearning) && (Status != "Quiz")) {
        ShowLearnContent(LearningContent);
        document.getElementById("submit_learn").style.display = 'block';
		document.getElementById("rkrk-sidebar").style.display = "none";
        Status = "Learning";
        ShowHideQuestionFields('none');
        return;
    } else {
		document.getElementById("rkrk-sidebar").style.display = "block";
	}

	// Name MUSS zuerst eingegeben werden
	if (document.getElementById("Name").value.trim() === "") {
		ShowNameForm();
		return;
	}

	// Drohne nötig (z. B. «Direkt Quiz» mit Status=Quiz): ohne Auswahl nicht in die Fragen
	if (typeof hasDroneTypes !== "undefined" && hasDroneTypes) {
		const de = document.getElementById("Drone");
		if (de && de.tagName === "SELECT" && String(de.value || "").trim() === "") {
			ShowNameForm();
			return;
		}
	}

	rkrkDigiAssessSyncSelectedDrone();

	setTopbarQuickLinksVisible(true);

	// QUIZ-TIMER STARTEN (nur einmal)
	if (!quizTimerActive && quizTimerDuration > 0) {
		quizTimerActive = true;
		quizTimerStart = null;
		requestAnimationFrame(runQuizTimer);
	}

	const resumeAt = dgaProgressResumeApplied ? AtQuestion : 0;
	if (!dgaProgressResumeApplied) {
		AtQuestion = 0;
	}

    ShowHideQuestionFields('block');
    rkrkDigiAssessRebuildQuestionNav();
    document.getElementById("loesungstext").innerHTML = "";
    document.getElementById("buffer").innerHTML = "";
    document.getElementById("submit_learn").style.display = 'none';
    let introHtml = QuizIntroduction;
    if (rkrkDigiAssessProgressEnabled()) {
        introHtml += "<div id=\"dga-progress-status\" class=\"rkrk-progress-status\" aria-live=\"polite\"></div>";
    }
    document.getElementById("indrodution").innerHTML = introHtml;
    document.getElementById("learning").innerHTML = "";

	GoToQuestion(resumeAt);
	rkrkDigiAssessSyncQuestionNavButton(resumeAt);
	LockWahl();
	ShowResult();

	if (rkrkDigiAssessProgressEnabled()) {
		rkrkDigiAssessSaveQuizSession();
		rkrkDigiAssessSyncQuizUrl();
		rkrkDigiAssessProgressStartAutosave();
	}

	const wasResume = dgaProgressResumeApplied;
	dgaProgressResumeApplied = false;

	if (wasResume) {
		rkrkDigiAssessFinalizeResumeUi(resumeAt);
	}
}

// =====================================
// Lerninhalt laden
// =====================================
function rkrkDigiAssessScrollIframeDocTop(iframe) {
    if (!iframe) return;
    try {
        const win = iframe.contentWindow;
        const doc = win && win.document;
        if (!doc) return;
        const marker = doc.getElementById("rkrk-learn-doc-top");
        const first = doc.querySelector("section.lern-sektion, h1, h2, body > *:not(script):not(style)");
        const target = marker || first;
        if (target) {
            try { target.scrollIntoView({ block: "start", inline: "nearest" }); } catch (e) {}
        }
        win.scrollTo(0, 0);
        doc.documentElement.scrollTop = 0;
        if (doc.body) doc.body.scrollTop = 0;
    } catch (e) {}
}

function rkrkDigiAssessScrollLearningTop() {
    const topAnchor = document.getElementById("rkrk-learning-top");
    const iframe = document.querySelector(".rkrk-learning-iframe");
    const card = document.getElementById("rkrk-card");
    const scrollTarget = topAnchor || iframe || card;
    if (scrollTarget) {
        try { scrollTarget.scrollIntoView({ behavior: "auto", block: "start" }); } catch (e) {}
    }
    if (typeof window.scrollTo === "function") {
        const y = scrollTarget ? (scrollTarget.getBoundingClientRect().top + window.pageYOffset - 8) : 0;
        window.scrollTo(0, Math.max(0, y));
    }
    rkrkDigiAssessScrollIframeDocTop(iframe);
}

function rkrkDigiAssessScheduleLearningTopScroll() {
    rkrkDigiAssessScrollLearningTop();
    [50, 150, 400, 900, 1800].forEach(function (ms) {
        setTimeout(rkrkDigiAssessScrollLearningTop, ms);
    });
}

if (!window.__rkrkLearnScrollMsgBound) {
    window.__rkrkLearnScrollMsgBound = true;
    window.addEventListener("message", function (ev) {
        if (!ev || !ev.data || ev.data.rkrkLearn !== "scrollTop") return;
        rkrkDigiAssessScheduleLearningTopScroll();
    });
}

function ShowLearnContent(dateipfad) {

    // iframe: relatives src in der Schulungs-HTML löst sich gegen die echte LernDatei-URL auf (fetch+innerHTML wäre relativ zu dieser PHP).
    let skipToolbar = "";
    if (Schulung === "Schulung") {
        skipToolbar =
            "<div id='rkrk-learning-top' class='rkrk-learn-skip' style='margin:0 0 12px;'>" +
            "<button type='button' onclick='ShowNameFormAfterLearning()' style='border-radius:8px;border:1px solid #cbd5e1;background:#f8fafc;color:#334155;padding:8px 14px;font-size:14px;cursor:pointer;'>Schulungstext ausblenden — weiter zur Prüfung</button>" +
            "</div>";
    }
    document.getElementById("learning").innerHTML =
        skipToolbar +
        rkrkDigiAssessBuildParticipantBannerHtml() +
        '<iframe class="rkrk-learning-iframe" src="' + encodeURI(dateipfad) + '" title="Schulungstext"></iframe>';

    const learnIframe = document.querySelector(".rkrk-learning-iframe");
    if (learnIframe) {
        learnIframe.addEventListener("load", rkrkDigiAssessScheduleLearningTopScroll, { once: true });
    }
    rkrkDigiAssessScheduleLearningTopScroll();

    ShowHideQuestionFields('none');
    document.getElementById("Name").style.display = 'none';
    document.getElementById("Name-Label").style.display = 'none';
	document.getElementById("rkrk-main").style.display = 'none';
}

// =====================================
// Frage auswählen
// =====================================
function SelectQuestion(ToQuestion) {

    if (document.getElementById("Name").value.trim() === "") {
        alert("Bitte gib zuerst deinen Namen ein.");
        document.getElementById("Name").focus();
        return;
    }

    rkrkDigiAssessCommitUnlockedExamAnswer();

    stopTimer();

    let qTime = parseInt(Questions[ToQuestion]["Zeit"] || 0);

    if (qTime > 0) {
        document.getElementById("timer-container").style.display = "block";
        timerDuration = qTime;
        resetTimerBar();
        startTimer();
    } else {
		if (!quizTimerActive) {
			document.getElementById("timer-container").style.display = "none";
		}
    }

    GoToQuestion(ToQuestion);
    LockWahl();
    ShowResult();
}

// =====================================
// Antwort absenden
// =====================================
function Submit() {

    stopTimer();
	if (!quizTimerActive) {
		document.getElementById("timer-container").style.display = "none";
	}

    if (Questions[AtQuestion]["Antwort"] != "") {
        Questions[AtQuestion]["Locked"] = "Ja";
        document.getElementById("submit_button").style.display = 'none';
    }

    LockWahl();
    UpdateButton(AtQuestion);
    ShowResult();
    CheckEndOfQuiz();
    rkrkDigiAssessSyncSubmitButton();
    if (typeof rkrkDigiAssessOnQuizInteraction === "function") {
        rkrkDigiAssessOnQuizInteraction();
    }
}

// =====================================
// Navigation
// =====================================
function NextQuestion() {
    rkrkDigiAssessCommitUnlockedExamAnswer();
    if (AtQuestion < (MaxQuestions - 1)) AtQuestion++;
    GoToQuestion(AtQuestion);
    UpdateButton(AtQuestion);
    LockWahl();
    ShowResult();
    if (typeof rkrkDigiAssessOnQuizInteraction === "function") {
        rkrkDigiAssessOnQuizInteraction();
    }
}

function PrevQuestion() {
    rkrkDigiAssessCommitUnlockedExamAnswer();
    if (AtQuestion > 0) AtQuestion--;
    GoToQuestion(AtQuestion);
    UpdateButton(AtQuestion);
    LockWahl();
    ShowResult();
    if (typeof rkrkDigiAssessOnQuizInteraction === "function") {
        rkrkDigiAssessOnQuizInteraction();
    }
}

// =====================================
// Antwort sperren / anzeigen
// =====================================
function LockWahl() {

    document.querySelectorAll('input[name="response"]').forEach(el => el.checked = false);

    if (Questions[AtQuestion]["Antwort"]) {

        if (Questions[AtQuestion]["Antwort"].includes("|")) {
            let parts = Questions[AtQuestion]["Antwort"].split("|");
            document.querySelectorAll('input[name="response"]').forEach(el => {
                el.checked = parts.includes(el.value);
            });
        } else {
            document.querySelectorAll('input[name="response"]').forEach(el => {
                el.checked = (el.value === Questions[AtQuestion]["Antwort"]);
            });
        }
    }

    let locked = rkrkDigiAssessQuestionLockedJa(Questions[AtQuestion]);

    document.querySelectorAll("#ja, #nein, #A, #B, #C, #D, #E, #F")
        .forEach(el => el.disabled = locked);
    rkrkDigiAssessSyncChangeAnswerButton();
}

function GoToQuestion(ToQuestion) {

    AtQuestion = ToQuestion;

    clearAnswerFeedbackStyles();

    stopTimer();

    let qTime = parseInt(Questions[AtQuestion]["Zeit"] || 0);

    if (qTime > 0) {
        document.getElementById("timer-container").style.display = "block";
        timerDuration = qTime;
        resetTimerBar();
        startTimer();
    } else {
		if (!quizTimerActive) {
			document.getElementById("timer-container").style.display = "none";
		}
    }

	// Learning section    
	document.getElementById("rkrk-card").style.display = "none";
	document.getElementById("rkrk-solution").style.display = "none";

    // Navigation Buttons
    document.getElementById("prev_button").style.display = (ToQuestion < 1) ? 'none' : 'block';
    document.getElementById("next_button").style.display = (ToQuestion < (MaxQuestions - 1)) ? 'block' : 'none';

	//Telnehmerinformation
	let name = document.getElementById("Name").value.trim();
    let droneElement = document.getElementById("Drone");

	if (name !="") {
		document.getElementById("Name-Label").style.display = 'none';
		document.getElementById("Name").style.display = 'none';
		rkrkDigiAssessHideDronePickerIfSelected();
		rkrkDigiAssessRefreshTeilnehmerinfo({ welcome: true });
	} else {
		document.getElementById("Name-Label").style.display = (ToQuestion < 1) ? 'block' : 'none';
		document.getElementById("Name").style.display = (ToQuestion < 1) ? 'block' : 'none';
		document.getElementById("Teilnehmerinfo").style.display = (ToQuestion < 1) ? 'none' : 'block';
	}
	
    // Submit Button
    if (rkrkDigiAssessPendingFinishSubmit()) {
        rkrkDigiAssessApplyFinishSubmitUi();
    } else if (rkrkDigiAssessQuestionLockedJa(Questions[ToQuestion])) {
        document.getElementById("submit_button").style.display = 'none';
    } else {
        if (String(Questions[ToQuestion]["Antwort"] == null ? "" : Questions[ToQuestion]["Antwort"]).trim() === "") {
            document.getElementById("submit_button").style.display = 'none';
        } else {
            document.getElementById("submit_button").style.display = 'block';
        }
    }

    if (!rkrkDigiAssessIsResultatStatus()) {
        rkrkDigiAssessSetCloseVisible(false);
    }

    // Hinweis: eine vs. Mehrfachauswahl (ohne Anzahl der richtigen Antworten)
    const loesRaw = String(Questions[ToQuestion]["Loesung"] || "");
    const isMulti = loesRaw.indexOf("|") !== -1;
    let mehrfachHtml = "";
    if (loesRaw === "Ja" || loesRaw === "Nein") {
        mehrfachHtml = " <span class=\"rkrk-antwort-hinweis rkrk-antwort-hinweis--single\">— genau eine Antwort ist richtig (Ja oder Nein).</span>";
    } else if (isMulti) {
        mehrfachHtml = " <span class=\"rkrk-antwort-hinweis rkrk-antwort-hinweis--multi\">— Mehrfachauswahl: es können mehrere Antworten zutreffen.</span>";
    } else {
        mehrfachHtml = " <span class=\"rkrk-antwort-hinweis rkrk-antwort-hinweis--single\">— genau eine Antwort ist richtig.</span>";
    }
    document.getElementById("mehrfach").innerHTML = mehrfachHtml;

    ["A","B","C","D","E","F"].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.type = isMulti ? "checkbox" : "radio";
    });

    // Auswahlfelder anzeigen
    if (Questions[ToQuestion]["Loesung"] == "Ja" || Questions[ToQuestion]["Loesung"] == "Nein") {

        document.getElementById("a-ja").style.display = 'block';
        document.getElementById("a-nein").style.display = 'block';

        ["a-a","a-b","a-c","a-d","a-e","a-f"].forEach(id => {
            document.getElementById(id).style.display = 'none';
        });

    } else {

        document.getElementById("a-ja").style.display = 'none';
        document.getElementById("a-nein").style.display = 'none';

        const labels = ["A","B","C","D","E","F"];

        labels.forEach((letter, idx) => {
            let span = document.getElementById("a-" + letter.toLowerCase());
            let label = document.getElementById("label" + letter);

            if (Questions[ToQuestion]["Loesungsauswahl"][idx] != null) {
                span.style.display = 'block';
                label.innerHTML = "<b>" + letter + "</b> <i>" + rkrkDigiAssessFixMojibake(Questions[ToQuestion]["Loesungsauswahl"][idx]) + "</i><br><br>";
            } else {
                span.style.display = 'none';
                label.innerHTML = "";
            }
        });
    }

    // Titel + Frage
    document.getElementById("Frage_Titel").innerHTML = "Frage " + (ToQuestion + 1);
    document.getElementById("Frage_Titel").style.fontSize = "12px";

    document.getElementById("Frage").innerHTML = "<b>" + rkrkDigiAssessFixMojibake(Questions[ToQuestion]["Frage"]) + "</b>";
    document.getElementById("Frage").style.fontSize = "20px";
    document.getElementById("Frage").style.marginTop = "10px";
    document.getElementById("Frage").style.marginBottom = "18px";

    // Fragebild
    if (rkrkDigiAssessShowFrageBild() && Questions[ToQuestion]["Frage-Bild"] != "") {
        const frageBild = document.getElementById("frage-bild");
        const frageBildUrl = rkrkQuizImageUrl(QuizIconLoc, Questions[ToQuestion]["Frage-Bild"]);
        const wantHeight = (Questions[ToQuestion]["Bildformat"] == "schmal")
            ? Math.floor(PicHeight / 2)
            : PicHeight;
        // Erst nach erfolgreichem Laden anzeigen — sonst bleibt bei 404 ein grosser Leerraum.
        frageBild.style.display = "none";
        frageBild.removeAttribute("height");
        frageBild.onload = function () {
            frageBild.height = wantHeight;
            frageBild.style.display = "block";
        };
        frageBild.onerror = function () {
            frageBild.onload = null;
            frageBild.onerror = null;
            frageBild.style.display = "none";
            frageBild.removeAttribute("height");
        };
        frageBild.src = frageBildUrl;
    } else {
        const frageBildOff = document.getElementById("frage-bild");
        if (frageBildOff) {
            frageBildOff.onload = null;
            frageBildOff.onerror = null;
            frageBildOff.style.display = "none";
            frageBildOff.removeAttribute("height");
        }
    }

    // Resultatmodus
    if (rkrkDigiAssessIsResultatStatus()) {
        document.getElementById("submit_button").style.display = 'none';

        if (document.getElementById("Name").value == "") {
            document.getElementById("noname").style.display = 'block';
            rkrkDigiAssessSetCloseVisible(false);
        } else {
            document.getElementById("noname").style.display = 'none';
            rkrkDigiAssessSetCloseVisible(true);
        }
    }

    if (setting == "TEST") {
        document.getElementById("debug").innerHTML = "Frage: " + ToQuestion;
    }

    rkrkDigiAssessSyncSubmitButton();
    rkrkDigiAssessMarkCurrentNavQuestion();
}

// =====================================
// Button erzeugen
// =====================================
function GetButton(nr, name, color) {

    return "<button type='button' class='rkrk-qnav-btn' id='F" + nr + "' style='background:" + color + ";' onclick='SelectQuestion(" + nr + ")'>" + name + "</button>";
}

// =====================================
// Button aktualisieren
// =====================================
function UpdateButton(nr) {
    rkrkDigiAssessSyncQuestionNavButton(nr);
    rkrkDigiAssessUpdateQuestionNavLegend();

    if (setting == "TEST") {
        const qu = Questions[nr];
        document.getElementById("debug").innerHTML =
            "Frage:" + nr + " Antwort: " + Questions[AtQuestion]["Antwort"] +
            " Lösung: " + Questions[AtQuestion]["Loesung"];
    }
}

// =====================================
// Farb-Feedback (Mehrfach / falsch gewählt / verpasst)
// =====================================
function clearAnswerFeedbackStyles() {
    const fs = document.getElementById("rkrk-choice-fieldset");
    if (fs) {
        fs.classList.remove("rkrk-antwort-locked");
    }
    document.querySelectorAll(".rkrk-opt label").forEach(function (lb) {
        lb.classList.remove("rkrk-opt-label--richtig", "rkrk-opt-label--falsch", "rkrk-opt-label--verpasst");
        lb.removeAttribute("title");
        lb.removeAttribute("data-rkrk-feedback");
    });
    const leg = document.getElementById("rkrk-answer-color-legend");
    if (leg) {
        leg.hidden = true;
    }
}

function rkrkDigiAssessMarkAnswerFeedback(lb, kind) {
    if (!lb) {
        return;
    }
    const map = {
        richtig: "Richtig gewählt",
        falsch: "Falsch gewählt",
        verpasst: "Richtig, aber nicht gewählt"
    };
    lb.classList.add("rkrk-opt-label--" + kind);
    lb.setAttribute("data-rkrk-feedback", kind);
    lb.setAttribute("title", map[kind] || kind);
}

function rkrkDigiAssessParseAntwortTeile(raw) {
    return String(raw || "").split("|").map(function (s) { return s.trim(); }).filter(Boolean).map(function (s) { return s.toUpperCase(); });
}

/** Gleiche Antwort wie Lösung (Mehrfach: Reihenfolge von A|B egal). */
function rkrkDigiAssessAnswersEqual(loes, ant) {
    const L = String(loes == null ? "" : loes).trim();
    const A = String(ant == null ? "" : ant).trim();
    if (L === "Ja" || L === "Nein") {
        return A === L;
    }
    const sa = rkrkDigiAssessParseAntwortTeile(L).slice().sort().join("|");
    const sb = rkrkDigiAssessParseAntwortTeile(A).slice().sort().join("|");
    return sa === sb;
}

function rkrkDigiAssessScoreModeRaw() {
    return String((Questionair && Questionair["PunkteModus"]) || "").trim().toLowerCase();
}

function rkrkDigiAssessScoreMode() {
    const raw = rkrkDigiAssessScoreModeRaw();
    if (raw === "teilpunkt_gewichtet" || raw === "teilpunkt_pro_option") {
        return raw;
    }
    return "standard";
}

function rkrkDigiAssessQuestionIsMulti(qu) {
    const loes = String((qu && qu["Loesung"]) || "");
    return loes.indexOf("|") !== -1;
}

function rkrkDigiAssessQuestionOptionCount(qu) {
    const arr = Array.isArray(qu && qu["Loesungsauswahl"]) ? qu["Loesungsauswahl"] : [];
    let n = 0;
    arr.forEach(function (v) {
        if (String(v == null ? "" : v).trim() !== "") {
            n++;
        }
    });
    return n;
}

function rkrkDigiAssessQuestionCorrectOptionCount(qu) {
    return rkrkDigiAssessParseAntwortTeile(qu && qu["Loesung"]).length;
}

function rkrkDigiAssessQuestionScore(qu, mode) {
    const m = mode || rkrkDigiAssessScoreMode();
    if (!qu || typeof qu !== "object") {
        return { points: 0, max: 1, status: "zero", full: false };
    }
    const answered = rkrkDigiAssessQuestionLockedJa(qu);
    const isMulti = rkrkDigiAssessQuestionIsMulti(qu);
    if (m === "standard" || !isMulti) {
        const p = (answered && rkrkDigiAssessAnswersEqual(qu["Loesung"], qu["Antwort"])) ? 1 : 0;
        return { points: p, max: 1, status: p > 0 ? "full" : "zero", full: p > 0 };
    }

    const corrSet = new Set(rkrkDigiAssessParseAntwortTeile(qu["Loesung"]));
    const pickSet = new Set(rkrkDigiAssessParseAntwortTeile(qu["Antwort"]));
    const corrCount = Math.max(1, corrSet.size);
    let correctPicked = 0;
    let wrongPicked = 0;
    pickSet.forEach(function (tok) {
        if (corrSet.has(tok)) {
            correctPicked++;
        } else {
            wrongPicked++;
        }
    });

    let points = 0;
    let max = 1;
    if (m === "teilpunkt_gewichtet") {
        points = (correctPicked - wrongPicked) / corrCount;
        max = 1;
    } else {
        points = (correctPicked - wrongPicked);
        max = Math.max(1, corrCount);
    }
    if (!answered) {
        points = 0;
    }
    points = Math.max(0, Math.min(max, points));
    const eps = 1e-9;
    const full = points >= (max - eps);
    let status = "zero";
    if (full && max > eps) {
        status = "full";
    } else if (points > eps) {
        status = "partial";
    }
    return { points: points, max: max, status: status, full: full };
}

/** Frage im Quiz als gesperrt/beantwortet (CSV kann «Ja», «ja», Leerzeichen liefern). */
function rkrkDigiAssessQuestionLockedJa(qu) {
    if (!qu || typeof qu !== "object") {
        return false;
    }
    const raw = qu.Locked != null ? qu.Locked : qu["Locked"];
    return String(raw == null ? "" : raw).trim().toLowerCase() === "ja";
}

function rkrkDigiAssessNormalizeLockedValue(val) {
    return String(val == null ? "" : val).trim().toLowerCase() === "ja" ? "Ja" : "Nein";
}

/** Gespeicherter Fortschritt: abgeschlossene Fragen als «Ja» markieren. */
function rkrkDigiAssessProgressLockedForSave(q, idx) {
    if (rkrkDigiAssessQuestionLockedJa(q)) {
        return "Ja";
    }
    return "Nein";
}

function rkrkDigiAssessRefreshAllQuestionButtons() {
    for (let qi = 0; qi < MaxQuestions; qi++) {
        rkrkDigiAssessSyncQuestionNavButton(qi);
    }
}

function applyAnswerFeedbackStyles() {
    const q = Questions[AtQuestion];
    if (!q || !rkrkDigiAssessQuestionLockedJa(q) || q.Antwort === "") {
        return;
    }
    const fs = document.getElementById("rkrk-choice-fieldset");
    if (!fs) {
        return;
    }
    const loes = String(q.Loesung || "");
    const ant = String(q.Antwort || "");
    let showLegend = false;

    if (loes === "Ja" || loes === "Nein") {
        const correct = loes;
        const picked = ant;
        [["ja", "Ja"], ["nein", "Nein"]].forEach(function (pair) {
            const id = pair[0];
            const val = pair[1];
            const lb = document.querySelector('label[for="' + id + '"]');
            if (!lb) {
                return;
            }
            const isCorr = val === correct;
            const sel = picked === val;
            if (isCorr && sel) {
                rkrkDigiAssessMarkAnswerFeedback(lb, "richtig"); showLegend = true;
            } else if (!isCorr && sel) {
                rkrkDigiAssessMarkAnswerFeedback(lb, "falsch");
                showLegend = true;
            } else if (isCorr && !sel) {
                rkrkDigiAssessMarkAnswerFeedback(lb, "verpasst");
                showLegend = true;
            }
        });
        fs.classList.add("rkrk-antwort-locked");
        const leg = document.getElementById("rkrk-answer-color-legend");
        if (leg) {
            leg.hidden = !showLegend;
        }
        return;
    }

    const correctSet = new Set(rkrkDigiAssessParseAntwortTeile(loes));
    const pickedSet = new Set(rkrkDigiAssessParseAntwortTeile(ant));
    const letters = ["A", "B", "C", "D", "E", "F"];

    letters.forEach(function (letter) {
        const span = document.getElementById("a-" + letter.toLowerCase());
        const lb = document.getElementById("label" + letter);
        if (!span || !lb || span.style.display === "none") {
            return;
        }
        const inCorr = correctSet.has(letter);
        const inPick = pickedSet.has(letter);
        if (inCorr && inPick) {
            rkrkDigiAssessMarkAnswerFeedback(lb, "richtig"); showLegend = true;
        } else if (inPick && !inCorr) {
            rkrkDigiAssessMarkAnswerFeedback(lb, "falsch");
            showLegend = true;
        } else if (inCorr && !inPick) {
            rkrkDigiAssessMarkAnswerFeedback(lb, "verpasst");
            showLegend = true;
        }
    });

    fs.classList.add("rkrk-antwort-locked");
    const leg2 = document.getElementById("rkrk-answer-color-legend");
    if (leg2) {
        leg2.hidden = !showLegend;
    }
}

// =====================================
// Lösung anzeigen
// =====================================
function ShowResult() {

    if (String(Questions[AtQuestion]["Antwort"] == null ? "" : Questions[AtQuestion]["Antwort"]).trim() === ""
        || !rkrkDigiAssessQuestionLockedJa(Questions[AtQuestion])) {

        clearAnswerFeedbackStyles();

        document.getElementById("loesung-bild").src = "";
        document.getElementById("loesungstext").innerHTML = "";
        document.getElementById("loesung").style.display = 'none';
        document.getElementById("rkrk-solution").style.display = 'none';
    } else if (rkrkDigiAssessDeferPerQuestionFeedback()) {

        clearAnswerFeedbackStyles();
        document.getElementById("loesung-bild").src = "";
        document.getElementById("loesungstext").innerHTML = "";
        document.getElementById("korrektur").innerHTML = "";
        document.getElementById("loesung").style.display = 'none';
        document.getElementById("rkrk-solution").style.display = 'none';
    } else {

        if (Questions[AtQuestion]["Loesungs-Bild"] != "") {
            document.getElementById("loesung-bild").src = rkrkQuizImageUrl(QuizIconLoc, Questions[AtQuestion]["Loesungs-Bild"]);
            document.getElementById("loesung-bild").style.display = 'block';
        } else {
            document.getElementById("loesung-bild").style.display = 'none';
        }

        document.getElementById("loesungstext").innerHTML = rkrkDigiAssessSolutionProseHtml(Questions[AtQuestion]);

        if (rkrkDigiAssessAnswersEqual(Questions[AtQuestion]["Loesung"], Questions[AtQuestion]["Antwort"])) {
            document.getElementById("korrektur").innerHTML = "Gratuliere, deine Antwort ist richtig!";
        } else {
            document.getElementById("korrektur").innerHTML = "Tut mir leid, deine Antwort ist nicht korrekt!";
        }

        document.getElementById("rkrk-solution").style.display = 'block';
        document.getElementById("loesung").style.display = 'block';
        applyAnswerFeedbackStyles();
    }
}

function ShowHideQuestionFields(ShowHide) {

    const frageBildEl = document.getElementById("frage-bild");
    if (frageBildEl) {
        if (ShowHide === "none") {
            frageBildEl.style.display = "none";
        } else {
            const q = (typeof AtQuestion === "number" && Questions[AtQuestion]) ? Questions[AtQuestion] : null;
            const hasPic = rkrkDigiAssessShowFrageBild() && q && String(q["Frage-Bild"] || "").trim() !== "";
            frageBildEl.style.display = hasPic ? "block" : "none";
        }
    }
    document.getElementById("Frage_Titel").style.display = ShowHide;
    document.getElementById("Frage").style.display = ShowHide;
    document.getElementById("auswahl").style.display = ShowHide;
    document.getElementById("prev_button").style.display = ShowHide;
    document.getElementById("submit_button").style.display = ShowHide;
    document.getElementById("next_button").style.display = ShowHide;
    const changeEl = document.getElementById("change_button");
    if (changeEl) {
        if (ShowHide === "none") {
            rkrkDigiAssessSetChangeAnswerVisible(false);
        } else if (typeof rkrkDigiAssessSyncChangeAnswerButton === "function") {
            rkrkDigiAssessSyncChangeAnswerButton();
        }
    }
    document.getElementById("loesung").style.display = ShowHide;
    document.getElementById("loesung-bild").style.display = ShowHide;
    document.getElementById("loesungstext").style.display = ShowHide;
    document.getElementById("result").style.display = (ShowHide === "none") ? "none" : "";
    document.getElementById("result-titel").style.display = ShowHide;

    // Fehler korrigiert: 'hide' ist kein gültiger Wert
    document.getElementById("korrektur").style.display = 'none';
    rkrkDigiAssessSetCloseVisible(false);
}

function ShowQuizList() {

	setTopbarQuickLinksVisible(true);

    let QuizListText = "";

    if (Schulung == "Schulung") {
        QuizListText = onRKRS
            ? ("Willkommen zur Schulung der Rehkitzrettung Schweiz.<br><br>" +
            "Themen mit Hintergrundwissen zur Rehkitzrettung – einlesen und anschliessend dein Verständnis prüfen.<br><br>" +
            "Wähle ein Thema, geh den Lerninhalt durch und starte dann den kurzen Check.<br><br>Viel Erfolg.<br><br>")
            : ("Willkommen zur Schulung im Knonaueramt.<br><br>" +
            "Schulungsthemen mit Informationen zur Rehkitzrettung.<br><br>" +
            "Wähle ein Thema, lese den Schulungstext und prüfe dein Wissen!<br><br>Viel Erfolg.<br><br>");
        if (rkrkDigiAssessIsExamMode()) {
            QuizListText += "<p style=\"margin:0 0 12px;padding:10px 12px;background:var(--kpi-bg);border:1px solid var(--kpi-border);border-radius:8px;font-size:0.95rem;\"><strong>Prüfungsmodus:</strong> Rückmeldung pro Frage erst nach «Schulung abschliessen» in der Übersicht.</p>";
        }
		document.getElementById("rkrk-card").style.display = "none";
		document.getElementById("rkrk-sidebar").style.display = "none";
		document.getElementById("rkrk-solution").style.display = "none";
		document.getElementById("rkrk-main").style.display = 'none';
		if (setting != "TEST") {
			document.getElementById("rkrk-debug").style.display = "none";
		}
    } else {
        QuizListText = onRKRS
            ? ("Willkommen zum Quiz der Rehkitzrettung Schweiz.<br><br>" +
            "Fragen und Antworten rund um die Rehkitzrettung.<br><br>" +
            "Wähle ein Thema und prüfe dein Wissen!<br><br>Viel Erfolg.<br><br>")
            : ("Willkommen zum Quiz.<br><br>" +
            "Fragen rund um die Rehkitzrettung im Knonaueramt.<br><br>" +
            "Wähle ein Thema und prüfe dein Wissen!<br><br>Viel Erfolg.<br><br>");
        if (rkrkDigiAssessIsExamMode()) {
            QuizListText += "<p style=\"margin:0 0 12px;padding:10px 12px;background:var(--kpi-bg);border:1px solid var(--kpi-border);border-radius:8px;font-size:0.95rem;\"><strong>Prüfungsmodus:</strong> Rückmeldung pro Frage erst nach «Prüfung abschliessen» in der Übersicht.</p>";
        }
		document.getElementById("rkrk-card").style.display = "none";
		document.getElementById("rkrk-sidebar").style.display = "none";
		document.getElementById("rkrk-solution").style.display = "none";
		document.getElementById("rkrk-debug").style.display = "none";
		document.getElementById("rkrk-main").style.display = 'none';
    }

    QuizListText += "<ul class='rkrk-quiz-list'>";

    let quizListListed = 0;
    let quizListRows = Array.isArray(QuizList) ? QuizList : [];
    quizListRows.forEach((qs) => {

        let aktivJa = (String(qs["Aktiv"] || "").trim() === "Ja");
        if (!aktivJa && !isAdminMode) {
            return;
        }

        let isQuiz = (rkrkDigiAssessCsvJa(qs["Quiz"]) && Schulung === "");
        let isSchulung = (rkrkDigiAssessCsvJa(qs["Schulung"]) && Schulung === "Schulung");

        let isReleased = (qs["Locked"] === "Nein") && aktivJa;
        let audienceOk = rkrkDigiAssessAudienceOk(setting, qs["Audienz"]);
        let isTest = (setting === "TEST");
        // Admin: alle Quiz/Schulungen (auch nicht freigegeben / falsche Audienz). Instruktor: Freigabe-Vorschau.
        let allowed;
        if (isAdminMode) {
            allowed = true;
        } else if (isInstrMode) {
            allowed = isReleased || audienceOk || isTest;
        } else {
            allowed = isReleased && (audienceOk || isTest);
        }

        if ((isQuiz || isSchulung) && allowed) {
            quizListListed++;

            let remark = (qs["Locked"] == "Ja" || qs["Aktiv"] == "Nein")
                ? "<i><span style='color:red'><br>Nicht freigegeben</span></i>"
                : "";

            let qEnc = encodeURIComponent(String(qs["Key"] == null ? "" : qs["Key"]));
            const uaTrain = rkrkDigiAssessUrladderWithoutQuizModus();
            const uaExam = rkrkDigiAssessUrladderExam();
            QuizListText += "<li class='rkrk-quiz-list-item'>";
            QuizListText += "<div class='rkrk-quiz-list-head'>";
            QuizListText += "<b><a href='" + url + "?quiz=" + qEnc + (uaTrain ? "&" + uaTrain : "") + "'>" + qs["Titel"] + "</a></b> ";
            QuizListText += "<span class='rkrk-quiz-list-links'>(<a href='" + url + "?quiz=" + qEnc + (uaTrain ? "&" + uaTrain : "") + "'>Training</a> | <a href='" + url + "?quiz=" + qEnc + "&" + uaExam + "'>Prüfung</a>)</span> ";
            if (isSchulung && String(qs["LernDatei"] || "").trim() !== "") {
                QuizListText += "<span class='rkrk-quiz-list-links'>(<a href='" + url + "?quiz=" + qEnc + "&ohne_lerntext=1&" + urladder + "'>ohne Schulungstext</a> · <a href='" + url + "?quiz=" + qEnc + "&ohne_bilder=1&" + urladder + "'>ohne Bilder</a>)</span> ";
            }
            if (isAdminMode || isInstrMode) {
                QuizListText += "<a href='" + url + "?quiz=" + qEnc + "&read_only=1&" + urladder + "' class='rkrk-quiz-list-links'>Lesen</a> ";
            }

            if (qs["LernDatei"] != "" && qs["FragenDatei"] != "") {
                QuizListText += "<span class='rkrk-quiz-list-links'>(<a href='" + url + "?quiz=" + qEnc + "&Status=Quiz" + (uaTrain ? "&" + uaTrain : "") + "'>Direkt Training</a> | <a href='" + url + "?quiz=" + qEnc + "&Status=Quiz&" + uaExam + "'>Direkt Prüfung</a>)</span>";
            }

            QuizListText += remark;
            QuizListText += "</div>";

            if (qs["Bild"] != "") {
                QuizListText += "<img class='rkrk-quiz-list-thumb' src='" + rkrkQuizImageUrl(rkrkQuizListImageBase(qs), qs["Bild"]) +
                    "' alt='' loading='lazy'>";
            }

            QuizListText += "<p class='rkrk-quiz-list-desc'>" + qs["Kurzbeschreibung"] + "</p>";
            QuizListText += "</li>";
        }
    });

    QuizListText += "</ul>";

    if (Schulung === "Schulung" && quizListListed === 0) {
        QuizListText += "<p style=\"color:#92400e;background:#fff7ed;border:1px solid #f59e0b;border-radius:10px;padding:14px;margin-top:10px;line-height:1.45;\"><strong>Hinweis:</strong> Es sind keine Schulungsthemen sichtbar. Prüfen Sie in «AssessmentListe»: Spalte <strong>Schulung</strong> = Ja, sowie <strong>Audienz</strong> für den Kanal RKRS/RKRSplus (auch «Alle» oder leer möglich). Verwenden Sie in Links <code>RKRS=1</code> (nicht nur leeres <code>RKRS=</code>), falls der Kanal beim Aufruf verloren geht.</p>";
    }

    document.getElementById("indrodution").innerHTML = QuizListText;
    if (Schulung == "Schulung") {
        document.getElementById("indrodution").style.fontSize = "10pt";
    } else {
        document.getElementById("indrodution").style.fontSize = "12pt";
    }
    document.getElementById("learning").innerHTML = "";
    document.getElementById("Name").style.display = 'none';
    document.getElementById("Name-Label").style.display = 'none';

    ShowHideQuestionFields('none');
}

function rkrkDigiAssessRefreshTeilnehmerinfo(opts) {
    opts = opts || {};
    const welcome = !!opts.welcome;
    const nameEl = document.getElementById("Name");
    const info = document.getElementById("Teilnehmerinfo");
    if (!nameEl || !info) return false;

    const name = String(nameEl.value || "").trim();
    if (name === "") {
        info.style.display = "none";
        return false;
    }

    rkrkDigiAssessSyncSelectedDrone();

    let html = welcome
        ? ((Schulung ? "Willkommen zur Schulung <b>" : "Willkommen zum Quiz <b>") + rkrkDigiAssessEscapeHtml(name) + "</b>")
        : ("Teilnehmer: <b>" + rkrkDigiAssessEscapeHtml(name) + "</b>");

    const droneLabel = rkrkDigiAssessGetSelectedDrone();
    if (droneLabel !== "" && droneLabel !== "Alle") {
        html += ", gewählte Drohne: <b>" + rkrkDigiAssessEscapeHtml(droneLabel) + "</b>";
    }

    info.innerHTML = html;
    info.style.display = "block";
    return true;
}

function rkrkDigiAssessHideDronePickerIfSelected() {
    const dSel = document.getElementById("Drone");
    const dLbl = document.getElementById("Drone-Label");
    const dWrap = document.getElementById("rkrk-drone-field");
    const droneLabel = rkrkDigiAssessGetSelectedDrone();
    const hidePicker = droneLabel !== "" && droneLabel !== "Alle";
    if (!hidePicker) return;
    if (dLbl) dLbl.style.display = "none";
    if (dSel) dSel.style.display = "none";
    if (dWrap) dWrap.style.display = "none";
}

function rkrkDigiAssessBuildParticipantBannerHtml() {
    const nameEl = document.getElementById("Name");
    if (!nameEl) return "";
    const name = String(nameEl.value || "").trim();
    if (name === "") return "";
    rkrkDigiAssessSyncSelectedDrone();
    const droneLabel = rkrkDigiAssessGetSelectedDrone();
    let html = "Teilnehmer: <b>" + rkrkDigiAssessEscapeHtml(name) + "</b>";
    if (droneLabel !== "" && droneLabel !== "Alle") {
        html += ", gewählte Drohne: <b>" + rkrkDigiAssessEscapeHtml(droneLabel) + "</b>";
    }
    return "<div class=\"rkrk-participant-banner\" style=\"margin:0 0 12px;padding:8px 12px;background:var(--kpi-bg);border:1px solid var(--kpi-border);border-radius:var(--radius);font-size:0.95rem;\">" + html + "</div>";
}

function showTeilnehmerinfo() {
    const name = document.getElementById("Name").value.trim();
    if (name === "") return;

    document.getElementById("Name-Label").style.display = "none";
    document.getElementById("Name").style.display = "none";
    rkrkDigiAssessHideDronePickerIfSelected();
    rkrkDigiAssessRefreshTeilnehmerinfo({ welcome: false });
}


// =====================================
// Quiz-Ende prüfen
// =====================================
function rkrkDigiAssessStripHtml(html) {
    if (!html) return "";
    const d = document.createElement("div");
    d.innerHTML = String(html);
    return (d.textContent || d.innerText || "").replace(/\s+/g, " ").trim();
}

/** Antworttext für Anzeige: Loesungstext wenn gesetzt, sonst Katalog-AntwortText. */
function rkrkDigiAssessSolutionProseHtml(qu) {
    if (!qu) return "";
    const lt = String(qu.Loesungstext == null ? "" : qu.Loesungstext).trim();
    if (lt !== "") return lt;
    return String(qu.AntwortText == null ? "" : qu.AntwortText).trim();
}

function rkrkDigiAssessAnswerDisplay(qu, val) {
    if (val == null || val === "") return "(keine Antwort)";
    const v = String(val);
    if (qu.Loesung === "Ja" || qu.Loesung === "Nein") return v;
    const opts = Array.isArray(qu.Loesungsauswahl) ? qu.Loesungsauswahl : [];
    const letters = "ABCDEFGH";
    const parts = v.split("|").map((s) => s.trim()).filter(Boolean);
    const out = [];
    parts.forEach((p) => {
        const idx = letters.indexOf(p);
        if (idx >= 0 && opts[idx] != null && String(opts[idx]).trim() !== "") {
            out.push(p + ": " + rkrkDigiAssessStripHtml(String(opts[idx])));
        } else {
            out.push(p);
        }
    });
    return out.join(" | ");
}

function rkrkDigiAssessWrongAnswersField() {
    const nMax = Math.max(0, parseInt(String(MaxQuestions), 10) || 0);
    const n = Math.min(nMax, Questions.length);
    const items = [];
    for (let i = 0; i < n; i++) {
        const qu = Questions[i];
        if (!qu || !rkrkDigiAssessQuestionLockedJa(qu)) continue;
        if (rkrkDigiAssessQuestionScore(qu, rkrkDigiAssessScoreMode()).status === "full") continue;
        const rawQid = qu.QID != null ? qu.QID : qu["QID"];
        const qidStr = String(rawQid == null ? "" : rawQid).trim();
        const idLabel = qidStr !== "" ? ("Frage-ID " + qidStr) : "Frage-ID —";
        const chosen = rkrkDigiAssessAnswerDisplay(qu, qu.Antwort);
        const frag = rkrkDigiAssessStripHtml(qu.Frage || "");
        const tail = frag ? (" — " + frag.substring(0, 120) + (frag.length > 120 ? "…" : "")) : "";
        items.push(idLabel + ": " + chosen + tail);
    }
    return items.join(" || ").replace(/;/g, ",");
}

function rkrkDigiAssessSyncPilotIdFromName() {
    const hid = document.getElementById("PilotID");
    const nm = document.getElementById("Name");
    if (!hid || !nm) {
        return;
    }
    const prev = String(hid.value || "").trim();
    if (!rkrkDgaPilotIdByName || typeof rkrkDgaPilotIdByName !== "object") {
        return;
    }
    const v = nm.value.trim();
    const id = rkrkDgaPilotIdByName[v];
    if (id != null && id !== "") {
        hid.value = String(id);
        return;
    }
    if (prev !== "") {
        return;
    }
    hid.value = "";
}

function rkrkDigiAssessRefreshResultHiddenFields() {
    let FragenBeantwortet = 0;
    let Richtig = 0;
    let PunkteErreicht = 0;
    let PunkteMax = 0;
    try {
        if (typeof rkrkDigiAssessSyncPilotIdFromName === "function") {
            rkrkDigiAssessSyncPilotIdFromName();
        }
    } catch (e0) {}
    Questions.forEach((qu) => {
        if (rkrkDigiAssessQuestionLockedJa(qu)) {
            FragenBeantwortet++;
            const sc = rkrkDigiAssessQuestionScore(qu, rkrkDigiAssessScoreMode());
            PunkteErreicht += sc.points;
            PunkteMax += sc.max;
            if (sc.status === "full") {
                Richtig++;
            }
        }
    });
    const af = document.getElementById("AnzFragen");
    const rb = document.getElementById("RichtigBeantwortet");
    const pe = document.getElementById("PunkteErreicht");
    const pm = document.getElementById("PunkteMax");
    const fa = document.getElementById("FalscheAntworten");
    if (af) af.value = MaxQuestions;
    if (rb) rb.value = Richtig;
    if (pe) pe.value = PunkteErreicht.toFixed(4).replace(/\.?0+$/, "");
    if (pm) pm.value = PunkteMax.toFixed(4).replace(/\.?0+$/, "");
    if (fa) fa.value = rkrkDigiAssessWrongAnswersField();
    return { FragenBeantwortet, Richtig, PunkteErreicht, PunkteMax };
}

function rkrkDigiAssessSnapshotQuizForSubmit() {
    const st = document.getElementById("Status");
    const el = document.getElementById("QuizErgebnisFragen");
    if (!st || !el || st.value !== "resultat") {
        return;
    }
    if (typeof rkrkDigiAssessDeleteProgress === "function") {
        rkrkDigiAssessDeleteProgress();
    }
    const rowIncomplete = function (rows) {
        if (!Array.isArray(rows) || !rows.length) return true;
        const r0 = rows[0] || {};
        return (r0.antwortLabel == null && r0.antwort == null && r0.loesung == null && r0.loesungLabel == null);
    };
    let rows = [];
    if (el.value && String(el.value).trim() !== "") {
        try {
            const parsed = JSON.parse(el.value);
            if (Array.isArray(parsed) && parsed.length) {
                rows = parsed;
            }
        } catch (e0) {}
    }
    if (rowIncomplete(rows)) {
        const fresh = rkrkDigiAssessCollectDetailFragen();
        if (Array.isArray(fresh) && fresh.length && !rowIncomplete(fresh)) {
            rows = fresh;
        } else if (!rows.length && Array.isArray(fresh)) {
            rows = fresh;
        }
    }
    rkrkDigiAssessStoreDetailFragen(rows);
}

function CheckEndOfQuiz() {

    const counts = rkrkDigiAssessRefreshResultHiddenFields();
    let FragenBeantwortet = counts.FragenBeantwortet;

    document.getElementById("Erfuellt").value = Questionair["Erfuellt"];
    document.getElementById("Quiz").value = set_quiz;

    if (FragenBeantwortet != MaxQuestions) {
        if (typeof rkrkDigiAssessOnQuizInteraction === "function") {
            rkrkDigiAssessOnQuizInteraction();
        }
        return;
    }

    if (rkrkDigiAssessPendingFinishSubmit()) {
        rkrkDigiAssessApplyFinishSubmitUi();
        if (typeof rkrkDigiAssessOnQuizInteraction === "function") {
            rkrkDigiAssessOnQuizInteraction();
        }
        return;
    }

    if (typeof rkrkDigiAssessOnQuizInteraction === "function") {
        rkrkDigiAssessOnQuizInteraction();
    }
}

// =====================================
// Name prüfen
// =====================================
function NameEingeben() {

    if (document.getElementById("Name").value == "") {
        document.getElementById("noname").style.display = 'block';
        rkrkDigiAssessSetCloseVisible(false);
    } else {
        document.getElementById("noname").style.display = 'none';
        rkrkDigiAssessSetCloseVisible(true);
    }
}
</script>
<!-- handle hidden fields -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    const nm0 = document.getElementById("Name");
    const pid0 = document.getElementById("PilotID");
    if (nm0 && pid0 && typeof rkrkDigiAssessSyncPilotIdFromName === "function") {
        nm0.addEventListener("input", rkrkDigiAssessSyncPilotIdFromName);
        nm0.addEventListener("change", rkrkDigiAssessSyncPilotIdFromName);
        rkrkDigiAssessSyncPilotIdFromName();
    }

    const params = new URLSearchParams(window.location.search);
    const role = (params.get('rolle') || "").toLowerCase();

    function enableInstruktorFields() {
        const elements = document.querySelectorAll('.instruktor-only');

        if (elements.length > 0 && role === "instruktor") {
            elements.forEach(el => {
                el.style.display = 'inline';
            });
            return true;
        }
        return false;
    }

    // 1) Sofort versuchen
    if (enableInstruktorFields()) {
        return;
    }

    // 2) Wenn Elemente später geladen werden → MutationObserver
    const observer = new MutationObserver(() => {
        if (enableInstruktorFields()) {
            observer.disconnect();
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

});

if (!window.__rkrkDgaQuizUnloadBound) {
    window.__rkrkDgaQuizUnloadBound = true;
    window.addEventListener("pagehide", function () {
        if (typeof rkrkDigiAssessBeaconSaveProgress === "function") {
            rkrkDigiAssessBeaconSaveProgress();
        }
    });
    window.addEventListener("beforeunload", function () {
        if (typeof rkrkDigiAssessBeaconSaveProgress === "function") {
            rkrkDigiAssessBeaconSaveProgress();
        }
    });
}
</script>



</body>
</html>

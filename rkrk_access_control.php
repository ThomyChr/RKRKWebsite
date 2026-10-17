<?php
  // Robust against repeated includes and mixed runtime behavior.
  if (!isset($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
  }
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_csvfiles.php');

	if (isset($_GET['debug']) && $_GET['debug'] == '1') {
		$AccessDebugOutput = true;
	}
	
	//global defintions
	$EnableAccessControl = 	true || 
							(
								(strpos(strval($_SERVER['PHP_SELF']),"test") != false) 
							//|| (strpos(strval($_SERVER['PHP_SELF']),"v2") != false)
							);  // only enabled on test server location
	$AccessDebugOutput = 	false &&
								(strpos(strval($_SERVER['PHP_SELF']),"test") != false);  // only enabled on test server location

	$EnableCookie=false;
	$LoggedOn=false;

	$CookieKey = "RKRK";
	
	
	$AccDebugInfo = "";
	$AccSystemInfo = "";
	$AccWarningInfo = "";
	
	$dateformat = "Y-m-d - H:i:s";
	$EntryFormat=true;

	// Passwort 6-20 Zeichen, davon eine Zahl, ein Gross- und ein Kleinbuchstaben.
	if ($EntryFormat) {
		$Password_Pattern = "(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}";
		$FormatMessage =  " Das Passwort muss 6 bis 20 Zeichen enthalten, davon mind. eine Nummer, ein Gross- und ein Kleinzeichen.";
	} else {
		$Password_Pattern = "[A-Za-z]{3,20}";
		$FormatMessage =  " Das Passwort muss 6 bis 20 Zeichen enthalten (keine Sonderzeichen erlaubt).";
	}

	$RKRIPAdressFilename = "Rehitzrettung - IP Adresses.csv";
	$RKRUsersFilename = $RKRK_Users;
	//$RKRUsersFilename = "Rehitzrettung - Users.csv";
	$DebugAdder = "../";
	$DebugAdder = "";
	$csv_desc = ';';
	$dateformat = "Y-m-d - H:i:s";

		
	$IPAdress_ListSetup = Array(
						"IP Adresse",
						"Name",
						"Vorname",
						"Email",
						"Provider",
						"WebAccess",
						"AccessRights",
						"Bemerkung",
						"LetzterBesuch",
					);


	$Users_ListSetup = Array(
						"ID",
						"Initialen",
						"Benutzername",
						"Name",
						"Vorname",
						"Passwort",
						"Passwort_hashed",
						"Email",
						"Webaccess",
						"AccessRights",
						"Regionen",
						"Bemerkung",
						"LetzterBesuch",
						"Preferenz01",
						"Preferenz02",
						"Preferenz03",
						"Preferenz04",
						"RegisterDate",
					);
				
//=====================================
// Process input information from FORM
//=====================================


$InputUserInfo= Array();

if ($EnableAccessControl){
	//if ($AccessDebugOutput) 
	//	pre_r($_COOKIE[$CookieKey]);

$GuestAccess = GetURLwDefault("GA", "GA", "GA", "");
//	if (isset($_GET['GA'])) {
//		$GuestAccess = $_GET['GA'];
//	} else  {
//		$GuestAccess=false;
//	}
//echo "Gastzugriff: " . $GuestAccess;
	
//GetURLwDefault("page_action", "page_action", "page_action", "login");
	if (isset($_GET['page_action'])) {
		$WebAction = $_GET['page_action'];
	} elseif (isset($_POST['page_action'])) {
		$WebAction = $_POST['page_action'];
	} else  {
		$WebAction = "";
	}
	$WebAction = trim(strval($WebAction ?? ""));
	$GLOBALS['rkrk_auth_page_action_explicit'] = isset($_GET['page_action']) || isset($_POST['page_action']);

/*
	if (isset($_POST['login_user'])) {
		$InputUserInfo["Login"]["Benutzername"] = $_POST['login_user'];
	}
/*
	if (isset($_POST['login_prename'])) {
		$InputUserInfo["Login"]["Vorname"] = $_POST['login_prename'];
	}

	if (isset($_POST['login_name'])) {
		$InputUserInfo["Login"]["Name"] = $_POST['login_name'];
	}
*/	
	//pre_r($_SESSION);
	if ($WebAction!="logout") {
		$CookieScope = (isset($_COOKIE[$CookieKey]) && is_array($_COOKIE[$CookieKey])) ? $_COOKIE[$CookieKey] : [];
		$Cookie_User = $CookieScope[$SessNme_RegisteredUser] ?? "";
		if (isset($_POST['login_user'])) {
			$InputUserInfo["Login"]["Benutzername"]=trim((string)$_POST['login_user']);
		} elseif (($Cookie_User!="") and $EnableCookie) {
			$InputUserInfo["Login"]["Benutzername"]=$Cookie_User;
		} elseif (($_SESSION[$SessNme_RegisteredUser] ?? "")!="") {
			$InputUserInfo["Login"]["Benutzername"]=$_SESSION[$SessNme_RegisteredUser];
		} else 
			$InputUserInfo["Login"]["Benutzername"]="";

		$Cookie_AccessLevel = $CookieScope[$SessNme_AccessLevel] ?? "";
		if (($_SESSION[$SessNme_AccessLevel] ?? "")!="")
			$InputUserInfo["Login"]["Level"]=$_SESSION[$SessNme_AccessLevel];
		elseif (($Cookie_AccessLevel!="") and $EnableCookie)
			$InputUserInfo["Login"]["Level"]=$Cookie_AccessLevel;
		else 
			$InputUserInfo["Login"]["Level"]="";

		$Cookie_AccessInitialen = $CookieScope[$SessNme_RegisteredInitialen] ?? "";
		if (($_SESSION[$SessNme_RegisteredInitialen] ?? "")!="")
			$InputUserInfo["Login"]["Initialen"]=$_SESSION[$SessNme_RegisteredInitialen];
		elseif (($Cookie_AccessInitialen !="") and $EnableCookie)
			$InputUserInfo["Login"]["Initialen"]=$Cookie_AccessInitialen ;
		else 
			$InputUserInfo["Login"]["Initialen"]="";

		$Cookie_AccessVorname = $CookieScope[$SessNme_RegisteredVorname] ?? "";
		if (($_SESSION[$SessNme_RegisteredVorname] ?? "")!="")
			$InputUserInfo["Login"]["Vorname"]=$_SESSION[$SessNme_RegisteredVorname];
		elseif (($Cookie_AccessVorname !="") and $EnableCookie)
			$InputUserInfo["Login"]["Vorname"]=$Cookie_AccessVorname ;
		else 
			$InputUserInfo["Login"]["Vorname"]="";

		$Cookie_AccessName = $CookieScope[$SessNme_RegisteredName] ?? "";
		if (($_SESSION[$SessNme_RegisteredName] ?? "")!="")
			$InputUserInfo["Login"]["Name"]=$_SESSION[$SessNme_RegisteredName];
		elseif (($Cookie_AccessName!="") and $EnableCookie)
			$InputUserInfo["Login"]["Name"]=$Cookie_AccessName;
		else 
			$InputUserInfo["Login"]["Name"]="";

		$Cookie_AccessEmail = $CookieScope[$SessNme_RegisteredEmail] ?? "";
		if (($_SESSION[$SessNme_RegisteredEmail] ?? "")!="")
			$InputUserInfo["Login"]["Email"]=$_SESSION[$SessNme_RegisteredEmail];
		elseif (($Cookie_AccessEmail!="") and $EnableCookie)
			$InputUserInfo["Login"]["Email"]=$Cookie_AccessEmail;
		else 
			$InputUserInfo["Login"]["Email"]="";

		$Cookie_Password = $CookieScope[$SessNme_Password] ?? "";
		if (isset($_POST['login_password']))
			$InputUserInfo["Login"]["Passwort"] = (string)$_POST['login_password'];
		elseif (($Cookie_Password!="") and $EnableCookie)
			$InputUserInfo["Login"]["Passwort"]=$Cookie_Password;
		elseif (($_SESSION[$SessNme_Password] ?? "")!="")
			$InputUserInfo["Login"]["Passwort"]=$_SESSION[$SessNme_Password];
		else 
			$InputUserInfo["Login"]["Passwort"]="";

		$Cookie_Regions = $CookieScope[$SessNme_SelRegions] ?? "";
		if (isset($_POST['regions']))
			$InputUserInfo["Login"]["Regions"] = $_POST['regions'];
		elseif (($Cookie_Regions!="") and $EnableCookie)
			$InputUserInfo["Login"]["Regions"]=$Cookie_Regions;
		elseif (($_SESSION[$SessNme_SelRegions] ?? "")!="")
			$InputUserInfo["Login"]["Regions"]=$_SESSION[$SessNme_SelRegions];
		else 
			$InputUserInfo["Login"]["Regions"]="";

		$PasswordHashed = $CookieScope[$SessNme_PasswordHashed] ?? "";
		if (($PasswordHashed!="") and $EnableCookie)
			$InputUserInfo["Login"]["PasswordHashed"]=$PasswordHashed;
		else 
			$InputUserInfo["Login"]["PasswordHashed"]="";
	} else {
		LogoutAndClearData();
	}

	if (isset($_POST['register_user'])) {
		$InputUserInfo["Register"]["Benutzername"] = $_POST['register_user'];
	}

	if (isset($_POST['register_acronym'])) {
		$InputUserInfo["Register"]["Initialen"] = $_POST['register_acronym'];
		$InputUserInfo["Register"]["LetzterBesuch"] = date($dateformat);
	}


	if (isset($_POST['register_prename'])) {
		$InputUserInfo["Register"]["Vorname"] = $_POST['register_prename'];
	}

	if (isset($_POST['register_name'])) {
		$InputUserInfo["Register"]["Name"] = $_POST['register_name'];
	}

	if (isset($_POST['register_email'])) {
		$InputUserInfo["Register"]["Email"] = $_POST['register_email'];
	}

	if (isset($_POST['register_confirm_email'])) {
		//echo $InputUserInfo["Register"]["EMail_confirmed"] = $_POST['register_confirm_email'];
	}

	if (isset($_POST['register_password'])) {
		$InputUserInfo["Register"]["Passwort"] = $_POST['register_password'];
		$InputUserInfo["Register"]["Passwort_hashed"] = password_hash($_POST['register_password'], PASSWORD_DEFAULT);
	}

	if (isset($_POST['register_confirm_password'])) {
		//echo $InputUserInfo["Register"]["Passwort_confirmed"] = $_POST['register_confirm_password'];
	}

	if (isset($_POST['regions'])) {
		$InputUserInfo["Register"]["Regionen"] = $_POST['regions'];
	}

 	if (isset($_POST['change_user'])) {
		$InputUserInfo["Change"]["Benutzername"] = $_POST['change_user'];
	}

	if (isset($_POST['change_name'])) {
		$InputUserInfo["Change"]["Name"] = $_POST['change_name'];
	}

	if (isset($_POST['change_prename'])) {
		$InputUserInfo["Change"]["Vorname"] = $_POST['change_prename'];
	}

	if (isset($_POST['old_password'])) {
		$InputUserInfo["Change"]["Altes Passwort"] = $_POST['old_password'];
	}

	if (isset($_POST['change_password'])) {
		$InputUserInfo["Change"]["Passwort"] = $_POST['change_password'];
		$InputUserInfo["Change"]["Passwort_hashed"] = password_hash($_POST['change_password'], PASSWORD_DEFAULT);
	}

	if (isset($_POST['change_confirm_password'])) {
		//echo $InputUserInfo["Change"]["change_confirm_password"] = $_POST['change_confirm_password'];
	}
}

if ($AccessDebugOutput) {
	$AccDebugInfo .= "<li><b>". "WebAction: " . $WebAction . "</b><br>";
	//pre_R($InputUserInfo);
	if ($EnableCookie) {
		$AccDebugInfo .= "<li>". "Cookie_User:" . $_COOKIE[$CookieKey][$SessNme_RegisteredUser] . "<br>";
		$AccDebugInfo .= "<li>".  "Cookie_Level:" . $_COOKIE[$CookieKey][$SessNme_AccessLevel]  . "<br>";
		$AccDebugInfo .= "<li>".  "Cookie_PW:" . $_COOKIE[$CookieKey][$SessNme_Password]  . "<br>";
		$AccDebugInfo .= "<li>".  "Cookie_PWH:" . $_COOKIE[$CookieKey][$SessNme_PasswordHashed]  . "<br>";
	}
	$AccDebugInfo .= "<li>".  "Session_User:" . $_SESSION[$SessNme_RegisteredUser] . "<br>";
	$AccDebugInfo .= "<li>".  "Session_Level:" . $_SESSION[$SessNme_AccessLevel]  . "<br>";
	$AccDebugInfo .= "<li>".  "Session_PW:" . $_SESSION[$SessNme_Password]  . "<br>";
	$AccDebugInfo .= "<li>".  "Session_PWH:" . $_SESSION[$SessNme_PasswordHashed]  . "<br>";
	
	if ($WebAction=="login") $InfoType="Login";
	if ($WebAction=="register") $InfoType="Register";
	if ($WebAction=="changepw") $InfoType="Change";
	$AccDebugInfo .= "<li>". $InfoType . "_User:" . $InputUserInfo[$InfoType]["Benutzername"] . "<br>";
	$AccDebugInfo .= "<li>". $InfoType . "_Level:" . $InputUserInfo[$InfoType]["Level"]  . "<br>";
	$AccDebugInfo .= "<li>". $InfoType . "_PW:" . $InputUserInfo[$InfoType]["Passwort"]  . "<br>";
	$AccDebugInfo .= "<li>". $InfoType . "_PWH:" . $InputUserInfo[$InfoType]["PasswordHashed"]  . "<br>";
}

//////////////////////////////////////
// Functions
//////////////////////////////////////

/** Aktueller Einstiegs-Dateiname (ohne Pfad). */
function rkrk_auth_script_name() {
	$script = (string)($_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '');
	return basename(str_replace('\\', '/', $script));
}

/** Query-Vertrag: GA=gast (iframe/Partner, ohne Passwort). */
function rkrk_auth_is_guest_request() {
	$candidates = array();
	if (isset($_GET['GA'])) {
		$candidates[] = $_GET['GA'];
	}
	if (isset($_POST['GA'])) {
		$candidates[] = $_POST['GA'];
	}
	global $GuestAccess, $SecParamters;
	$candidates[] = $GuestAccess ?? '';
	if (isset($SecParamters) && is_array($SecParamters) && isset($SecParamters['GA'])) {
		$candidates[] = $SecParamters['GA'];
	}
	foreach ($candidates as $ga) {
		if (strtolower(trim((string)$ga)) === 'gast') {
			return true;
		}
	}
	return false;
}

/**
 * HTML-Seiten, die ohne Login erreichbar bleiben müssen
 * (Iframes von drohnenrehkitzrettung.ch, Partner, KML).
 */
function rkrk_auth_is_public_page($script = null) {
	if (!empty($GLOBALS['RKRK_AUTH_PUBLIC'])) {
		return true;
	}
	$script = $script !== null && $script !== '' ? basename(str_replace('\\', '/', (string)$script)) : rkrk_auth_script_name();
	$public = array(
		'rkrk_jg_summary.php',
		'rkrk_pubcomp.php',
		'rkrk_map.php',
		'rkrk_quiz.php',
		'rkrk_pv.php',
		'rkrk_adrlist.php',
		'rkrk_dokml.php',
		'rkrk_flugstatistik.php',
		'rkrk_flugliste.php',
		'rkrk_calc.php',
		'rkrk_calc_flightparameter.php',
		'rkrk_conversion.php',
		'rkrk_newsletter_public.php',
	);
	if ($script === 'rkrk_newsletter_review.php') {
		$token = isset($_GET['t']) ? trim((string)$_GET['t']) : '';
		if ($token !== '') {
			return true;
		}
	}
	return in_array($script, $public, true);
}

function rkrk_auth_apply_guest_identity() {
	global $InputUserInfo, $WebFreigabe;
	if (!is_array($InputUserInfo)) {
		$InputUserInfo = array();
	}
	if (!isset($InputUserInfo['Login']) || !is_array($InputUserInfo['Login'])) {
		$InputUserInfo['Login'] = array();
	}
	$InputUserInfo['Login']['Level'] = 'gast';
	$WebFreigabe = 'gast';
}

function rkrk_auth_allows_anonymous() {
	return rkrk_auth_is_guest_request() || rkrk_auth_is_public_page();
}

/** URL zur Login-Maske (Home); nutzt Menü-Pfad, falls rkrk_menu.php geladen. */
function rkrk_grant_login_screen_url() {
	global $MainMenu;
	if (function_exists('rkrk_menu_site_href')) {
		return rkrk_menu_site_href('Home', '?page_action=login');
	}
	$home = basename($MainMenu['Home']['site'] ?? 'rkrk.php');
	$dir = dirname($_SERVER['PHP_SELF'] ?? '/');
	$dir = str_replace('\\', '/', $dir);
	if ($dir === '/' || $dir === '.') {
		return $home . '?page_action=login';
	}
	return $dir . '/' . $home . '?page_action=login';
}

Function GrantAccess() {
	global 	$WebAction,
			$WebFreigabe,
			$CookieKey,
			$InputUserInfo,
			$AccessDebugOutput,
			$EnableAccessControl,
			$AccSystemInfo,
			$SessNme_RegisteredUser,
			$SessNme_AccessLevel,
			$SessNme_Sessionstart,
			$ThisPage,
			$LoggedOn,
			$MainMenu,
			$GuestAccess,
			$dateformat,
			$Layout;

	$LogInGranted = false;
	$LoginFormRendered = false;
	$GuestByUrl = rkrk_auth_is_guest_request();
	$publicPage = rkrk_auth_is_public_page();
	$allowAnonymous = $GuestByUrl || $publicPage;
	$suppressLoginHtml = !empty($GLOBALS['RKRK_AJAX_JSON_MODE']);
	$webActionNorm = strtolower(trim((string)$WebAction));
	$pageActionExplicit = !empty($GLOBALS['rkrk_auth_page_action_explicit']);
	$forceLoginForm = ($webActionNorm === 'login' && $pageActionExplicit && !$GuestByUrl);

	if ($allowAnonymous && !$forceLoginForm && !in_array($webActionNorm, array('logout', 'register', 'changepw'), true)) {
		if ($GuestByUrl) {
			rkrk_auth_apply_guest_identity();
		} elseif (isset($_SESSION[$SessNme_RegisteredUser]) && trim(strval($_SESSION[$SessNme_RegisteredUser])) !== "") {
			if (!is_array($InputUserInfo)) {
				$InputUserInfo = array();
			}
			if (!isset($InputUserInfo['Login']) || !is_array($InputUserInfo['Login'])) {
				$InputUserInfo['Login'] = array();
			}
			$sessionLevel = trim(strval($_SESSION[$SessNme_AccessLevel] ?? ''));
			if ($sessionLevel === '' && isset($CookieKey) && isset($_COOKIE[$CookieKey]) && is_array($_COOKIE[$CookieKey])) {
				$sessionLevel = trim(strval($_COOKIE[$CookieKey][$SessNme_AccessLevel] ?? ''));
			}
			if ($sessionLevel === '') {
				$sessionLevel = trim(strval($InputUserInfo['Login']['Level'] ?? ''));
			}
			if ($sessionLevel !== '') {
				$InputUserInfo['Login']['Level'] = $sessionLevel;
				$WebFreigabe = $sessionLevel;
			}
		}
		$LoggedOn = $GuestByUrl || (isset($_SESSION[$SessNme_RegisteredUser]) && trim(strval($_SESSION[$SessNme_RegisteredUser])) !== "");
		ShowAccessLevel();
		return;
	}
	
	//pre_R($LogInGranted);
	//echo "WebAction: " . $WebAction . "<br>";
		if ($Layout=="2023") {
		$BackgroundColor = "#E0E0E0";
	} else {
		$BackgroundColor = "#D0E5D0";
	}	

	
	if ($EnableAccessControl) {
		if ($WebAction == "logout") {
			LogoutAndClearData();
			echo "<style>
			#rkrk-logout-card{font-family:Arial,system-ui,sans-serif;max-width:min(92vw,560px);margin:48px auto;background:#fff;border:1px solid #d6d6d6;border-radius:12px;padding:22px 24px;box-shadow:0 8px 20px rgba(0,0,0,.12);}
			#rkrk-logout-card h2{margin:0 0 10px 0;color:#1f3f1f;font-size:32px;line-height:1.1;}
			#rkrk-logout-card p{margin:0 0 18px 0;color:#253025;font-size:22px;line-height:1.35;}
			#rkrk-logout-card .rkrk-btn{display:inline-block;background:var(--brand-dark,#48673A);color:#fff;text-decoration:none;border-radius:8px;padding:10px 16px;font-weight:700;box-shadow:0 2px 5px rgba(0,0,0,.15);}
			#rkrk-logout-card .rkrk-btn-secondary{background:var(--brand-vlight,#ECF7EC);color:var(--brand-dark,#48673A);border:1px solid var(--brand,#96B588);margin-left:8px;box-shadow:none;}
			</style>";
			echo "<div id='rkrk-logout-card'>";
			echo "<h2>Benutzer erfolgreich ausgeloggt</h2>";
			echo "<p>Zum erneuten Anmelden bitte auf den Login-Button klicken.</p>";
			echo "<a class='rkrk-btn' href='rkrk.php?page_action=login'>Zum Login</a>";
			echo "<a class='rkrk-btn rkrk-btn-secondary' href='rkrk.php'>Startseite</a>";
			echo "</div>";

			// Do NOT print debug info here — it breaks headers
			exit;
		}

		if ($WebAction!="register") {
			CheckAccessAndAutoforward();
			if ($InputUserInfo["Login"]["Level"]=="gast") {
				if (isset($_POST['login_user']) && trim(strval($_POST['login_user'])) !== "") {
					$loginResult = CheckLoginAndSetUser();
					if ($loginResult) {
						UpdateAccessDate();
					}
				}
				// GA=gast soll Seiteninhalt freischalten und Login-Weiterleitung überbrücken.
				$LogInGranted = $GuestByUrl || (trim(strval($_SESSION[$SessNme_RegisteredUser] ?? "")) !== "");
			} else {
				$LogInGranted = CheckLoginAndSetUser();
				if ($LogInGranted) {
					UpdateAccessDate();
				}
			}
		}	
		//echo "WebFreigabe: " . $WebFreigabe;
		
		if ($WebAction=="changepw") {
			if ($AccessDebugOutput)	echo "change password: " ."<br>";
			ChangePassword($InputUserInfo);
		} 

		if ($WebAction=="register") {
			if ($AccessDebugOutput)	echo "add user: " ."<br>";
			AddNewUser($InputUserInfo);
			$WebAction="login";
			RegisterForm($IPAddress);
			$LoginFormRendered = true;
			PrintDebugInformation();
			exit;
			
		} elseif ($LogInGranted==false && !$suppressLoginHtml && (!$allowAnonymous || $forceLoginForm)) {
			$faviconSrc = '/rkropen/favicon/Reh_FA840B_orange/favicon-32x32.png';
			if (function_exists('rkrk_favicon_assets')) {
				$faviconAssets = rkrk_favicon_assets();
				if (!empty($faviconAssets['png'])) {
					$faviconSrc = (string)$faviconAssets['png'];
				}
			}
			$homeHref = function_exists('rkrk_menu_site_href') ? rkrk_menu_site_href('Home', '') : 'rkrk.php';
			$scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
			if ($homeHref !== '' && $homeHref[0] !== '/' && strpos($homeHref, '../') !== 0 && substr($scriptDir, -3) === '/fe') {
				$homeHref = '../' . $homeHref;
			}
			echo "<table style='background-color: ".$BackgroundColor.";' width=100%><tr>";
			echo "<td style='width:52px;vertical-align:middle;padding-left:8px;'>";
			echo "<a href='" . htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') . "' title='Zur Startseite'><img src='" . htmlspecialchars($faviconSrc, ENT_QUOTES, 'UTF-8') . "' alt='RKRK' height='32' style='display:block;background:transparent;'></a>";
			echo "</td><td>";
			echo "<br><b>Benutzer - Login </b>" . "<br>";
			echo "<i>Bitte einloggen, resp. registrieren, falls kein Konto vorhanden ist:</i>" . "<br>". "<br>";
			echo "</td><td>";
			echo CreateSiteLink ("GoBack", "");
			echo "</td></table>";
			$IPAddress = getIPAddress();
			$UserAddresses = GetUserList();
			RegisterForm($IPAddress);
			$LoginFormRendered = true;
			echo "</div>";
		}

		$LoggedOn = $GuestByUrl || (isset($_SESSION[$SessNme_RegisteredUser]) && trim(strval($_SESSION[$SessNme_RegisteredUser])) !== "");

	} else {
	// Access Control Disabled
		if ($WebAction=="logout") {
			if ($AccessDebugOutput)	echo "logout[1]: " ."<br>";
				LogoutAndClearData();
			//$_SESSION[$SessNme_RegisteredUser]="";
			//DelCookie($SessNme_RegisteredUser);
		}
		$LoggedOn = $GuestByUrl || (isset($_SESSION[$SessNme_RegisteredUser]) && trim(strval($_SESSION[$SessNme_RegisteredUser])) !== "");
	}
	
	if ($EnableAccessControl && !$LoggedOn && !$LoginFormRendered && $WebAction !== "changepw" && !$suppressLoginHtml && (!$allowAnonymous || $forceLoginForm)) {
		$IPAddress = getIPAddress();
		RegisterForm($IPAddress, "Login", true);
		$LoginFormRendered = true;
	}
	ShowAccessLevel();
	PrintDebugInformation();
	if ($EnableAccessControl && !$LoggedOn && (!$allowAnonymous || $forceLoginForm)) {
		if ($suppressLoginHtml) {
			return;
		}
		exit;
	}
};

//Handle Cookies Store delete

Function LogoutAndClearData() {
	global $InputUserInfo,
			$AccessDebugOutput,
			$SessNme_UserList,
			$SessNme_Sessionstart,
			$SessNme_RegisteredUser,
			$SessNme_AccessLevel,
			$SessNme_Password,
			$SessNme_PasswordHashed,
			$SessNme_SelRegions,
			$SessNme_RegisteredInitialen,
			$SessNme_RegisteredName,
			$SessNme_RegisteredVorname,
			$SessNme_RegisteredEmail,
			$SessNme_IPAddress;

	if (!empty($AccessDebugOutput))
		echo "logout: " ."<br>";
	if (!isset($InputUserInfo["Login"]) || !is_array($InputUserInfo["Login"])) {
		$InputUserInfo["Login"] = array();
	}
	$UserKey = trim((string)($_SESSION[$SessNme_RegisteredUser] ?? ($InputUserInfo["Login"]["Benutzername"] ?? "")));
	$InputUserInfo["Login"]["Vorname"]="";
	$InputUserInfo["Login"]["Name"]="";
	$InputUserInfo["Login"]["Benutzername"]="";
	$InputUserInfo["Login"]["Level"]="";
	$InputUserInfo["Login"]["Passwort"]="";
	$InputUserInfo["Login"]["PasswordHashed"]="";
	$InputUserInfo["Login"]["Regions"]="";

	unset ($_SESSION[$SessNme_Sessionstart]);
	unset ($_SESSION[$SessNme_RegisteredUser]);
	unset ($_SESSION[$SessNme_AccessLevel]);
	unset ($_SESSION[$SessNme_Password]);
	unset ($_SESSION[$SessNme_PasswordHashed]);
	unset ($_SESSION[$SessNme_RegisteredInitialen]);
	unset ($_SESSION[$SessNme_RegisteredName]);
	unset ($_SESSION[$SessNme_RegisteredVorname]);
	unset ($_SESSION[$SessNme_RegisteredEmail]);
	unset ($_SESSION[$SessNme_IPAddress]);
	unset ($_SESSION[$SessNme_SelRegions]);

	WriteToLog("logout", basename($_SERVER['PHP_SELF'] ?? 'rkrk.php'), $UserKey ,"","","");
	$LogInGranted=false;
}
function UpdateData() {
    global $InputUserInfo,
           $SessNme_Sessionstart,
           $SessNme_RegisteredUser,
           $SessNme_AccessLevel,
           $SessNme_SelRegions,
           $SessNme_Password,
           $SessNme_PasswordHashed,
           $dateformat;

    $_SESSION[$SessNme_Sessionstart]   = date($dateformat);
    $_SESSION[$SessNme_RegisteredUser] = $InputUserInfo["Login"]["Benutzername"] ?? "";
    $_SESSION[$SessNme_AccessLevel]    = $InputUserInfo["Login"]["Level"] ?? "";
    $_SESSION[$SessNme_Password]       = $InputUserInfo["Login"]["Passwort"] ?? "";
    $_SESSION[$SessNme_PasswordHashed] = $InputUserInfo["Login"]["PasswordHashed"] ?? "";
    $_SESSION[$SessNme_SelRegions]     = $InputUserInfo["Login"]["Regions"] ?? "";

	if ($EnableCookie) {
		if ($WebAction != "logout") {
			UpdateData();
			StoreCookie($CookieKey, $SessNme_Sessionstart, date($dateformat));
			StoreCookie($CookieKey, $SessNme_RegisteredUser, $_SESSION[$SessNme_RegisteredUser]);
			StoreCookie($CookieKey, $SessNme_AccessLevel, $_SESSION[$SessNme_AccessLevel]);
			StoreCookie($CookieKey, $SessNme_Password, $_SESSION[$SessNme_Password]);
			StoreCookie($CookieKey, $SessNme_PasswordHashed, $_SESSION[$SessNme_PasswordHashed]);
			StoreCookie($CookieKey, $SessNme_SelRegions, $_SESSION[$SessNme_SelRegions]);
		} else {
			LogoutAndClearData();
			DeleteCookie($CookieKey, $SessNme_Sessionstart);
			DeleteCookie($CookieKey, $SessNme_RegisteredUser);
			DeleteCookie($CookieKey, $SessNme_AccessLevel);
			DeleteCookie($CookieKey, $SessNme_Password);
			DeleteCookie($CookieKey, $SessNme_PasswordHashed);
			DeleteCookie($CookieKey, $SessNme_SelRegions);
		}
	}
}
/*
Function UpdateData() {
	global $InputUserInfo,
			$AccDebugInfo,
			$AccessDebugOutput,
			$SessNme_Sessionstart,
			$SessNme_RegisteredUser,
			$SessNme_AccessLevel,
			$SessNme_SelRegions,
			$SessNme_Password,
			$SessNme_PasswordHashed;
			
			
	//if ($AccessDebugOutput)	
	echo "update login data" ."<br>";
	$_SESSION[$SessNme_Sessionstart]=date($dateformat);
	$_SESSION[$SessNme_RegisteredUser]=$InputUserInfo["Login"]["Benutzername"];
	$_SESSION[$SessNme_AccessLevel]=$InputUserInfo["Login"]["Level"];
	$_SESSION[$SessNme_Password]=$InputUserInfo["Login"]["Passwort"];
	$_SESSION[$SessNme_PasswordHashed]=$InputUserInfo["Login"]["PasswordHashed"];
	$_SESSION[$SessNme_SelRegions]=$InputUserInfo["Login"]["Regions"];
	//StoreCookie($CookieKey,$SessNme_Sessionstart, date($dateformat));
	//StoreCookie($CookieKey ,$SessNme_RegisteredUser, $InputUserInfo["Login"]["Benutzername"]);
	//StoreCookie($CookieKey, $SessNme_AccessLevel, $InputUserInfo["Login"]["Level"]);
	//StoreCookie($CookieKey, $SessNme_Password, $InputUserInfo["Login"]["Passwort"]);
	//StoreCookie($CookieKey, $SessNme_PasswordHashed, $InputUserInfo["Login"]["PasswordHashed"]);
}

if ($EnableCookie) {
	if (
			($WebAction!="logout")
		) {
		if ($AccessDebugOutput)	
			$Info =  "Cookies neu speichern" . "<br>";
		UpdateData();
		StoreCookie($CookieKey,$SessNme_Sessionstart, date($dateformat));
		StoreCookie($CookieKey ,$SessNme_RegisteredUser, $_SESSION[$SessNme_RegisteredUser]);
		StoreCookie($CookieKey, $SessNme_AccessLevel, $_SESSION[$SessNme_AccessLevel]);
		StoreCookie($CookieKey, $SessNme_Password, $_SESSION[$SessNme_Password]);
		StoreCookie($CookieKey, $SessNme_PasswordHashed, $_SESSION[$SessNme_PasswordHashed]);
		StoreCookie($CookieKey, $SessNme_SelRegions, $_SESSION[$SessNme_SelRegions]);
	} else {
		if ($AccessDebugOutput)	
			$Info =  "Cookies löschen" . "<br>";
		LogoutAndClearData();
		DeleteCookie($CookieKey, $SessNme_Sessionstart);
		DeleteCookie($CookieKey ,$SessNme_RegisteredUser);
		DeleteCookie($CookieKey, $SessNme_AccessLevel);
		DeleteCookie($CookieKey, $SessNme_Password);
		DeleteCookie($CookieKey, $SessNme_PasswordHashed);
		DeleteCookie($CookieKey, $SessNme_SelRegions);
	}
}
*/
Function CheckAccessAndAutoforward() {
	global 	$GuestAccess,
			$MainMenu,
			$InputUserInfo,
			$WebFreigabe,
			$WebLink_intern,
			$SessNme_RegisteredUser;

	if (!empty($GLOBALS['RKRK_AJAX_JSON_MODE'])) {
		return;
	}
	
	$ThisPage = basename($_SERVER['PHP_SELF']);
	if (!isset($InputUserInfo['Login']) || !is_array($InputUserInfo['Login'])) {
		$InputUserInfo['Login'] = array();
	}
	if (($GuestAccess) and (($InputUserInfo["Login"]["Level"] ?? "")=="")) $InputUserInfo["Login"]["Level"]=$GuestAccess;
	$GuestByUrl = rkrk_auth_is_guest_request();
	if ($GuestByUrl) {
		rkrk_auth_apply_guest_identity();
		return;
	}
	if (rkrk_auth_is_public_page($ThisPage) && trim(strval($_SESSION[$SessNme_RegisteredUser] ?? "")) === "") {
		return;
	}
	if ($InputUserInfo["Login"]["Level"]!="") $WebFreigabe = $InputUserInfo["Login"]["Level"];
	if ($ThisPage!=$MainMenu["Home"]["site"]) {
		$PageCheckDone = false;
		$ForwardToDo = true;
		foreach ($MainMenu as $Key => $Menu) {
			if ($Menu["site"]==$ThisPage) {
				//echo $Menu["site"] . " - " .$ThisPage . " - " . $WebFreigabe . "<br>";
				if ($ThisPage == $Menu["site"]) {
					$PageCheckDone = true;
					// WICHTIG: gleiche Zugriffslogik wie Menü verwenden (release + users/Benutzername).
					if (function_exists('MenuEntryAccessGranted')) {
						if (MenuEntryAccessGranted($Key)) {
							$ForwardToDo = false;
						}
					} else {
						// Fallback für Legacy-Umgebungen ohne neue Menüfunktion.
						if ((strpos(" " . ($Menu["release"] ?? ""), $InputUserInfo["Login"]["Level"])!= false)) {
							$ForwardToDo = false;
						}
					}
				}
			}
		}
		//echo "<br>";
		// Nur umleiten, wenn die Seite im MainMenu bekannt ist, aber Zugriff fehlt.
		// Externe/zusätzliche Seiten (z.B. ar/public/*), die nicht im MainMenu stehen,
		// sollen hier nicht pauschal auf Home umgeleitet werden.
		if (($PageCheckDone && $ForwardToDo)) 
			{
			$hasSessionLogin = trim(strval($_SESSION[$SessNme_RegisteredUser] ?? "")) !== "";
			// Nicht eingeloggt: kein Redirect hier, Login-Formular wird in GrantAccess() gerendert.
			if (!$hasSessionLogin) {
				return;
			}
			$NewPageLink =  $WebLink_intern . $MainMenu["Home"]["site"];
			$NewPageLinkEsc = htmlspecialchars($NewPageLink, ENT_QUOTES, 'UTF-8');
			if (!headers_sent()) {
				header('Location: '. $NewPageLink);
			} else {
				echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">';
				echo '<meta http-equiv="refresh" content="0;url=' . $NewPageLinkEsc . '">';
				echo '<title>Login</title></head><body>';
				echo '<p>Weiterleitung… <a href="' . $NewPageLinkEsc . '">Hier klicken</a></p>';
				echo '<script>location.replace("' . $NewPageLinkEsc . '");</script>';
				echo '</body></html>';
			}
			exit;
		}
	}
}	

Function ShowAccessLevel() {
	global 	$AccDebugInfo,
			$AccessDebugOutput,
			$SessNme_RegisteredUser,
			$SessNme_AccessLevel,
			$SessNme_Sessionstart;

	$user = isset($_SESSION[$SessNme_RegisteredUser]) ? trim(strval($_SESSION[$SessNme_RegisteredUser])) : "";
	$level = isset($_SESSION[$SessNme_AccessLevel]) ? trim(strval($_SESSION[$SessNme_AccessLevel])) : "";
	// Anmeldeinformation im UI bewusst ausgeblendet.
			
	if ($AccessDebugOutput) {
		$AccDebugInfo.= "<li>Registrierte Nutzer: " . ($_SESSION[$SessNme_RegisteredUser] ?? "") . " [" . ($_SESSION[$SessNme_AccessLevel] ?? "") . "," . ($_COOKIE[$SessNme_Sessionstart] ?? "") . "]</li>";
	}
}

function PrintDebugInformation() {
    global $AccDebugInfo, $AccSystemInfo, $AccWarningInfo, $AccessDebugOutput, $WebAction;

    // If debug is OFF → do nothing (keeps header() working)
    if (!$AccessDebugOutput) {
        return;
    }
    // Login flow should stay clean even in debug mode.
    if (strtolower(trim(strval($WebAction ?? ""))) === "login") {
        return;
    }

    // If output buffering is active, flush safely
    if (ob_get_level() > 0) {
        ob_end_flush();
    }

    echo "<div style='font-family:Arial; margin:20px;'>";

    if (!empty($AccSystemInfo)) {
        echo "<div style='background:#FFF3CD; padding:10px; border-radius:6px; margin-bottom:10px;'>
                <strong>System‑Meldungen:</strong><br>$AccSystemInfo
              </div>";
    }

    if (!empty($AccDebugInfo)) {
        echo "<div style='background:#D1ECF1; padding:10px; border-radius:6px; margin-bottom:10px;'>
                <strong>Debug‑Informationen:</strong><br>$AccDebugInfo
              </div>";
    }

    if (!empty($AccWarningInfo)) {
        echo "<div style='background:#F8D7DA; padding:10px; border-radius:6px; margin-bottom:10px;'>
                <strong>Warnungen:</strong><br>$AccWarningInfo
              </div>";
    }

    echo "</div>";
}


Function StoreCookie($CookieKey, $CookieName, $CookieValue) {
	global $EnableCookie;
	//echo "store cookie " . $CookieKey ."-". $CookieName. ": " . $CookieValue . "<br>";
	if ($EnableCookie) {
		setcookie($CookieKey."[".$CookieName."]", $CookieValue, time() + (86400 * 30), "/"); // 86400 = 1 day
	}
	//setcookie("test2", "1234", time() + (86400 * 30), "/"); // 86400 = 1 day
}

Function DeleteCookie($CookieKey, $CookieName) {
	global $EnableCookie;
	if ($EnableCookie) {
		setcookie($CookieKey."[".$CookieName."]", $CookieValue, time() - 3600, "/"); // 3600 = 1 hour
	}
	//setcookie("test2", "1234", time() - (3600), "/"); // 3600 = 1 hour
}

function GetCookieValue($CookieKey, $CookieName) {
	//if(isset($_COOKIE[$CookieKey."[".$CookieName."]"])) {
		return $_COOKIE[$CookieKey."[".$CookieName."]"];
	//} else {
	//	return "";
	//}
}

function GetCookies($CookieKey) {
	if ((isset($_COOKIE[$CookieKey])) and ($EnableCookie)) {
		return $_COOKIE[$CookieKey];
	} else {
		return "";
	}
}


function ReadIPAdressList () {
	global 	$csvMissionLocation, 
			$RKRIPAdressFilename,
			$DebugAdder,
			$csv_desc;
			
	$predata = csv_to_array($DebugAdder.$csvMissionLocation.$RKRIPAdressFilename,$csv_desc);
	//pre_r($predata);
	foreach ($predata as $entry) {
		$data[$entry['IP Adresse']]=$entry;
	}
	//pre_r($data);
	//echo "IP Adress-Daten neu gelesen.<br>";
	return $data;
}

function SaveIPAdressList () {
	global 	$RKRIPAdressFilename,
			$SessNme_RegisteredInitialen,
			$SessNme_RegisteredEmail,
			$InputUserInfo,
			$dateformat;
	
	$data = ReadIPAdressList ();
	$IPAddress = getIPAddress();
	//pre_r( $InputUserInfo);
	$data[$IPAddress]["IP Adresse"]= $IPAddress;
	$data[$IPAddress]["Benutzername"] = $InputUserInfo["Login"]["Benutzername"];
	$data[$IPAddress]["AccessRights"] = $InputUserInfo["Login"]["Level"];
	$data[$IPAddress]["Initialen"] = $_SESSION[$SessNme_RegisteredInitialen];
	$data[$IPAddress]["Email"] = $_SESSION[$SessNme_RegisteredEmail];
	$data[$IPAddress]["LetzterBesuch"] = date($dateformat); 
	unset($data[""]);
	//pre_r($data);
	SaveArrayToCSV ($data, $RKRIPAdressFilename);
}


Function ReadUserList() {
	global 	$csvMissionLocation, 
			$RKRUsersFilename,
			$csv_desc;
			
	$predata = csv_to_array($csvMissionLocation.$RKRUsersFilename,$csv_desc);
	//pre_r($predata);
	$data = array();
	if (!is_array($predata)) {
		return $data;
	}
	foreach ($predata as $entry) {
		if (!is_array($entry)) {
			continue;
		}
		$userName = trim((string)($entry['Benutzername'] ?? ''));
		if ($userName === '') {
			continue;
		}
		$data[$userName] = $entry;
		$data[$userName]['Benutzername'] = $userName;
		$data[$userName]["Passwort_hashed_file"] = (string)($entry["Passwort_hashed"] ?? '');
		$data[$userName]["Passwort_hashed"] = password_hash((string)($entry["Passwort"] ?? ''), PASSWORD_DEFAULT);
	}
	//pre_R($data);
	return $data;
}

function GetUserList () {
	global 	$SessNme_UserList;
	
	//pre_r($_SESSION[$SessNme_UserList]);
	if(!isset($_SESSION[$SessNme_UserList])) {
		//echo $csvMissionLocation.$RKRUsersFilename;
		//echo "User-Daten neu gelesen.<br>";
		$data = ReadUserList();
		return $_SESSION[$SessNme_UserList]= $data;
	} else {
		//echo "User-Daten existieren.<br>";
		//pre_r($_SESSION[$SessNme_UserList]);
		return $_SESSION[$SessNme_UserList];
	}
}

function AddNewUser($InputUserInfo) {
	global 	$SessNme_UserList, 
			$dateformat, 
			$AccSystemInfo;
			
	//renew user list in Session
	unset ($_SESSION[$SessNme_UserList]);
	GetUserList ();
	$UserKey=$InputUserInfo["Register"]["Benutzername"];

	if (!array_key_exists($UserKey,$_SESSION[$SessNme_UserList])) {
		//pre_r($_SESSION[$SessNme_UserList]);
		//echo(count($_SESSION[$SessNme_UserList]));
		$_SESSION[$SessNme_UserList][$UserKey] = $InputUserInfo["Register"];
		$_SESSION[$SessNme_UserList][$UserKey]["WebAccess"] = "";
		$_SESSION[$SessNme_UserList][$UserKey]["AccessRights"]= "pub";
		$_SESSION[$SessNme_UserList][$UserKey]["Bemerkungen"] ="";
		$_SESSION[$SessNme_UserList][$UserKey]["RegisterDate"] =date($dateformat);
		//get next empty userID
		$MaxNum = 0;
		foreach ($_SESSION[$SessNme_UserList] as $dataline) {
			if ($MaxNum< substr($dataline["ID"],-4)) {
				$MaxNum= substr($dataline["ID"],-4);
			}
		}
		$NextFreeID = "ADR". sprintf('%04d', $MaxNum+1);		
		$_SESSION[$SessNme_UserList][$UserKey]["ID"] =$NextFreeID;
		//pre_r($_SESSION[$SessNme_UserList]);
		SaveUserList();
		//pre_R(GetUserList ());
		$AccSystemInfo.= "Benutzerregistrierung erfolgreich abgeschlossen:" . "<br>";
		$AccSystemInfo.= "<li>" . $UserKey . "</li>";
	} else {
		$AccSystemInfo.= "Benutzerregistrierung bereits registriert, bitte einloggen:" . "<br>";
		$AccSystemInfo.= "<li>" . $UserKey . "</li>";
	}
}

function ChangePassword($InputUserInfo) {
	global $SessNme_UserList, $AccSystemInfo, $Info, $WebAction;
	//renew user list in Session
	unset ($_SESSION[$SessNme_UserList]);
	GetUserList ();
	$UserKey=$InputUserInfo["Change"]["Benutzername"];
	//pre_r($_SESSION[$SessNme_UserList]);
	//echo "key" . $InputUserInfo["Change"]["Vorname"] . "_" . $InputUserInfo["Change"]["Name"] . "<br>";
	if (array_key_exists($UserKey,$_SESSION[$SessNme_UserList])) {
		if ((
			password_verify($InputUserInfo["Change"]["Altes Passwort"], $_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"])) 
			or ($InputUserInfo["Login"]["PasswordHashed"]==$_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"])
			) {
			//pre_r($_SESSION[$SessNme_UserList]);
			//echo(count($_SESSION[$SessNme_UserList]));
			$_SESSION[$SessNme_UserList][$UserKey]["Passwort"] = $InputUserInfo["Change"]["Passwort"];
			$_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"]= $InputUserInfo["Change"]["Passwort_hashed"];
			//pre_r($_SESSION[$SessNme_UserList]);
			SaveUserList ();
			WriteToLog("change PW", basename($_SERVER['PHP_SELF']), $UserKey ,"","","");
			//pre_R(GetUserList ());
			$Info.= "Passwort geändert für:" . "<br>";
			$Info.= "<li>" . $UserKey . "</li>";
			$WebAction="login";
		} else {
			$Info .= "Benutzername, -vorname oder Passwort ungültig!<br>Bitte nochmals versuchen." . chr(10);
			WriteToLog("Denied change", basename($_SERVER['PHP_SELF']), $UserKey ,"","","");
		}		
	} else {
		$AccSystemInfo.= "Benutzerregistrierung nicht registriert. Bitte zuerst registrieren." . "<br>";
		WriteToLog("Denied change", basename($_SERVER['PHP_SELF']), $UserKey ,"","","");
	}
}



function SaveUserList () {
	global $RKRUsersFilename, $Users_ListSetup, $SessNme_UserList;
	SaveSessionVarToCSV ($SessNme_UserList, $Users_ListSetup, $RKRUsersFilename);
}

/*
function SaveSessionVarToCSV ($SessionVarName, $TableSetup, $csv_file) {
	global $csvMissionLocation, $csv_desc, $historyfilelocation, $AccSystemInfo;
	
	//echo $SessionVarName . "<br>";
	//pre_r(GetUserList());
	//if (!empty($_SESSION[$SessionVarName])) {
		$data = $_SESSION[$SessionVarName];
		//unset($_SESSION[$SessionVarName]);
		//echo "TableSetup: <br>";
		//pre_r($TableSetup);
		//echo "Read Data: <br>";
		//pre_r($data);
		$filepath= $csvMissionLocation.$csv_file;
		//echo "<br><br>Filepath: " . $filepath . "<br>";
		
		$filecontent = implode($csv_desc, $TableSetup). chr(13) . chr(10);
		foreach ($data as $dataline) {
			$outputdata=Array();
			foreach ($TableSetup as $Field) {
				$outputdata[$Field] = str_replace($csv_desc, ",",$dataline[$Field]);
			}
			//pre_r($outputdata);
			$filecontent .= implode($csv_desc, $outputdata). chr(13) . chr(10);
		}
		//echo "Filecontent: [" . $filecontent. "]<br>";

		// safe history
		$file = $filepath;
		$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
		//echo "<br>History: " . $file . " to  " . $newfile;
		if (!copy($file, $newfile)) {
			$AccSystemInfo .= "failed to copy $file...<br>";
		}
		// safe file
		if (true) {
		//echo "FilePath: " . $filepath . " - NumEntries:" . count($data) . "<br>";
			$df = fopen($filepath, 'w');
			fputs($df, $filecontent);
			fclose($df);
		}
		// remove all session variables
	//}
}
*/

function SaveSessionVarToCSV ($SessionVarName, $TableSetup, $csv_file) {
    global $csvMissionLocation, $csv_desc, $historyfilelocation, $AccSystemInfo;

    // nothing to save
    if (empty($_SESSION[$SessionVarName]) || empty($TableSetup)) {
        return;
    }

    $data     = $_SESSION[$SessionVarName];
    $filepath = $csvMissionLocation . $csv_file;

    // build CSV content
    $filecontent = implode($csv_desc, $TableSetup) . "\r\n";

    foreach ($data as $dataline) {
        $outputdata = [];
        foreach ($TableSetup as $Field) {
            $val = isset($dataline[$Field]) ? $dataline[$Field] : "";
            $outputdata[$Field] = str_replace($csv_desc, ",", $val);
        }
        $filecontent .= implode($csv_desc, $outputdata) . "\r\n";
    }

    // history copy (only if original file exists)
    if (is_file($filepath)) {
        $newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
        if (!@copy($filepath, $newfile)) {
            $AccSystemInfo .= "failed to copy $filepath to $newfile<br>";
        }
    }

    // ensure target directory exists (avoid runtime warnings on pages with different base paths)
    $targetDir = dirname($filepath);
    if (!is_dir($targetDir)) {
        if (!@mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            $AccSystemInfo .= "target directory not available: $targetDir<br>";
            return;
        }
    }

    // write new file atomically
    if (@file_put_contents($filepath, $filecontent) === false) {
        $AccSystemInfo .= "failed to write file: $filepath<br>";
    }
}

//NOT WORKING
Function SendMailWithLink_NOTWORKING ($InputUserInfo) {
	ini_set("SMTP", "lx62.hoststar.hosting");
	ini_set("sendmail_from", "thomas.christen@chwu.ch");
	ini_set("smtp_port", "465");

	$to_email = "t.ch@chwu.ch";
	$subject = "Simple Email Test via PHP";
	$body = "Hi,\n This is test email send by PHP Script";
	$headers = "From: sender@example.com";
 
	if ( mail($to_email, $subject, $body, $headers)) {
		echo("Email successfully sent to $to_email...");
	} else {
		echo("Email sending failed...");
	}
}

Function CheckLoginAndSetUser() {
	global 	$SessNme_UserList, 
			$InputUserInfo, 
			$AccSystemInfo, 
			$AccDebugInfo,
			$InfoSessNme_RegisterUser, 
			$SessNme_RegisteredTime, 
			$SessNme_RegisteredUser, 
			$SessNme_Sessionstart, 
			$SessNme_AccessLevel, 
			$SessNme_RegisteredInitialen,	
			$SessNme_RegisteredName,
			$SessNme_RegisteredVorname,
			$SessNme_RegisteredEmail,
			$SessNme_IPAddress,
			$SessNme_PasswordHashed, 
			$SessNme_Password,
			$SessNme_SelRegions,
			$AccessDebugOutput, 
			$CookieKey, 
			$LoggedOn,
			$dateformat,
			$EnableCookie;

	$loginPosted = isset($_POST['login_user']) || isset($_POST['login_password']);
	if ($loginPosted) {
		unset($_SESSION[$SessNme_UserList]);
	}
	GetUserList();
	if (!isset($_SESSION[$SessNme_UserList]) || !is_array($_SESSION[$SessNme_UserList])) {
		$_SESSION[$SessNme_UserList] = array();
	}
	$UserKey=trim((string)($InputUserInfo["Login"]["Benutzername"] ?? ''));
	if ($UserKey !== '' && !array_key_exists($UserKey, $_SESSION[$SessNme_UserList])) {
		foreach ($_SESSION[$SessNme_UserList] as $candidate => $candidateRow) {
			if (!is_array($candidateRow)) {
				continue;
			}
			$initialen = trim((string)($candidateRow['Initialen'] ?? ''));
			if (strcasecmp((string)$candidate, $UserKey) === 0 || ($initialen !== '' && strcasecmp($initialen, $UserKey) === 0)) {
				$UserKey = (string)$candidate;
				$InputUserInfo["Login"]["Benutzername"] = $UserKey;
				break;
			}
		}
	}

	if ($EnableCookie) {
		$CookieData = (isset($_COOKIE[$CookieKey]) && is_array($_COOKIE[$CookieKey])) ? $_COOKIE[$CookieKey] : [];
	}
	//echo "CheckLogin<br>";
	//pre_r($_COOKIE[$CookieKey]);
	//echo $CookieData["PasswordHashed"]."<br>";
	//echo $_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"] . "<br>";

	//pre_R($InputUserInfo);
	//pre_r($_SESSION[$SessNme_UserList]);
	if (!isset($_SESSION[$SessNme_IPAddress])) {
		$StoreLoginformation=true;
		//echo "store login<br>";
	} else {
		$StoreLoginformation=false;
		//echo "skip storing login<br>";
	}

	$typedPassword = (string)($InputUserInfo["Login"]["Passwort"] ?? '');
	$sessionPassword = (string)($_SESSION[$SessNme_Password] ?? '');
	if (array_key_exists($UserKey,$_SESSION[$SessNme_UserList])) {
		$freshHash = (string)($_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"] ?? '');
		$fileHash = (string)($_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed_file"] ?? '');
		$passwordOk = false;
		foreach (array($typedPassword, trim($typedPassword), $sessionPassword) as $candidatePassword) {
			if ($candidatePassword === '') {
				continue;
			}
			if ($freshHash !== '' && password_verify($candidatePassword, $freshHash)) {
				$passwordOk = true;
				break;
			}
			if ($fileHash !== '' && password_verify($candidatePassword, $fileHash)) {
				$passwordOk = true;
				break;
			}
		}
		if ($passwordOk) {
			
			$LoggedOn=true;

			$AccDebugInfo .= "<b><li>Access granted, " . $UserKey . "</b></li>";
			$InputUserInfo["Login"]["Level"]= $_SESSION[$SessNme_UserList][$UserKey]["AccessRights"];
			$InputUserInfo["Login"]["Passwort_hashed"]= $_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"];

			//pre_R($_SESSION[$SessNme_UserList][$UserKey]);
			$_SESSION[$SessNme_RegisteredUser]=$UserKey;
			$_SESSION[$SessNme_AccessLevel]= $InputUserInfo["Login"]["Level"];
			$_SESSION[$SessNme_Password]=$InputUserInfo["Login"]["Passwort"];
			$_SESSION[$SessNme_PasswordHashed]=$_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"];
			$_SESSION[$SessNme_RegisteredInitialen]=$_SESSION[$SessNme_UserList][$UserKey]["Initialen"];
			$_SESSION[$SessNme_RegisteredName]=$_SESSION[$SessNme_UserList][$UserKey]["Name"];
			$_SESSION[$SessNme_RegisteredVorname]=$_SESSION[$SessNme_UserList][$UserKey]["Vorname"];
			$_SESSION[$SessNme_RegisteredEmail]=$_SESSION[$SessNme_UserList][$UserKey]["Email"];
			$_SESSION[$SessNme_IPAddress] = getIPAddress();
			$_SESSION[$SessNme_SelRegions]=$_SESSION[$SessNme_UserList][$UserKey]["Regionen"];

			//StoreCookie($CookieKey, $SessNme_Sessionstart, date($dateformat));
			//StoreCookie($CookieKey ,$SessNme_RegisteredUser, $InputUserInfo["Login"][$SessNme_AccessLevel]);
			//StoreCookie($CookieKey, $SessNme_AccessLevel, $InputUserInfo["Login"][$SessNme_AccessLevel]);
			//StoreCookie($CookieKey, $SessNme_Password, $InputUserInfo["Login"]["Passwort"]);
			//StoreCookie($CookieKey, $SessNme_PasswordHashed, $InputUserInfo["Login"]["Passwort_hashed"]);

			//pre_R($InputUserInfo);
			if ($StoreLoginformation) {
				WriteToLog("login", basename($_SERVER['PHP_SELF']), $UserKey ,"","","");
				SaveIPAdressList();
			}
			return Array("Benutzername" => $UserKey, "Level" => $InputUserInfo["Login"]["Level"]);
		} else {
			$LoggedOn=false;
			$AccSystemInfo .= "<ul>";
			$AccSystemInfo .= "<li><b>Access denied</b></li>";
			$AccSystemInfo .= "Benutzername, -vorname oder Passwort ungültig!<br>Bitte nochmals versuchen." . chr(10);
			$AccSystemInfo .= "</ul>";
			if (isset($_POST['login_user']) || isset($_POST['login_password'])) {
				$GLOBALS['rkrk_login_error'] = 'Benutzername oder Passwort ist ungültig. Bitte nochmals versuchen.';
			}
			$_SESSION[$SessNme_RegisteredUser]="";
			$_SESSION[$SessNme_AccessLevel]= "";
			$_SESSION[$SessNme_SelRegions]="";

			//DeleteCookie($CookieKey,$SessNme_Sessionstart);
			//DeleteCookie($CookieKey ,$SessNme_RegisteredUser);
			//DeleteCookie($CookieKey, $SessNme_AccessLevel);
			//DeleteCookie($CookieKey, $SessNme_PasswordHashed);
			return false;
		}		
	} else {
		if ($AccessDebugOutput) $AccSystemInfo .= "<li>user name not existing</li>";
		if (isset($_POST['login_user']) || isset($_POST['login_password'])) {
			$GLOBALS['rkrk_login_error'] = 'Benutzername oder Passwort ist ungültig. Bitte nochmals versuchen.';
		}
		$LoggedOn=false;
		$_SESSION[$SessNme_RegisteredUser]="";
		$_SESSION[$SessNme_AccessLevel]= "";
		$_SESSION[$SessNme_SelRegions]="";
		//DeleteCookie($CookieKey, $SessNme_Sessionstart);
		//DeleteCookie($CookieKey ,$SessNme_RegisteredUser);
		//DeleteCookie($CookieKey, $SessNme_AccessLevel);
		//DeleteCookie($CookieKey, $SessNme_Password);
		//DeleteCookie($CookieKey, $SessNme_PasswordHashed);
		return false;
	}
}
/*
Function CheckIPAddress($IPAddress, &$Username) {
	$IPList = GetIPAdressList();
	//pre_R($IPList);
	$IPFound=false;
	foreach ($IPList as $Entry) {
		//pre_r($Entry);
		if ($IPAddress == $Entry["IP Adresse"]) {
			//echo $IPAddress . " " . $Entry["IP Adresse"] . " - " . "found" . "<br>";
			$Username= $Entry["Vorname"] . " " . $Entry["Name"];
			return true;
			break;
		}
	}
	$IPList[]["IP Adresse"]=$IPAddress;
	
	//echo "<br>" . "<br>" . "notfound" . "<br>" . "<br>"; 
	return false;
}
*/

Function UpdateAccessDate() {
	global 	$SessNme_UserList,
			$dateformat,
			$SessNme_RegisteredUser;

	if ($_SESSION[$SessNme_RegisteredUser]!="") {
		$_SESSION[$SessNme_UserList][$_SESSION[$SessNme_RegisteredUser]]["LetzterBesuch"] = date($dateformat); 
		SaveUserList();
	}
}

Function RegisterForm($IPAddress,$Action="Login",$FloatingTop=false) {
	global $SessNme_RegisteredUser,$WebAction,$Password_Pattern,$Layout;
	$WebAction = trim(strval($WebAction ?? ""));
	if ($WebAction === "") {
		$WebAction = "login";
	}
	
	# Simple password protection
	#
	# (c) http://www.phpbuddy.com
	# Author: Ranjit Kumar
	# Feel free to use this script but keep this message intact!
	# 
	# To protect a page include this file in your PHP pages!
	# Updated by TCHR, 24.04.2013
	$IPAddress = getIPAddress();
	//$IPRegistered = CheckIPAddress($IPAddress, $Username);
	//if ($AccessDebugOutput) echo "Benutzer (aus IP Liste): " . $Username ."<br>";
	if ($Layout=="2023") {
		$BackgroundColor = "#E0E0E0";
	} else {
		$BackgroundColor = "#D0E5D0";
	}	
	if (($WebAction=="register") or ($WebAction=="login") or ($WebAction=="logout")) {
		// Kein zusätzliches äußeres <div> hier: der Inhalt kommt in $output (inkl. Popup/span),
		// sonst bleibt ein offenes <div> übrig und der Login-Block wird im Browser „verschluckt“.
		//if (!$IPRegistered or true) {
		if (true) {
			// New User to Register
			$Reg_output= "";
			$Reg_output .= '<form name="form1" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="POST">' . chr(10);
			$Reg_output .= '	<table class="none" style="width:100%;background-color: transparent;border-collapse:separate;border-spacing:0 8px;">' . chr(10);
			$Reg_output .= '		<tr><td>Benutzername:</td><td><input type="text" name="register_user" placeholder="Benutzername" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Initialen:</td><td><input type="text" name="register_acronym" placeholder="Initialen" minlength="3" maxlength="6" size=20 required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Name:</td><td><input type="text" name="register_name" placeholder="Name" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Vorname:</td><td><input type="text" name="register_prename" placeholder="Vorname" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Passwort: </td><td><input type="password" placeholder="Passwort" name="register_password" id="register_password" pattern="'.$Password_Pattern.'" onchange="CheckPassword(document.form1.register_password)" required><span id = "password_message" style="color:#b00020;font-size:12px;"> </span> </td></tr>' . chr(10);
			$Reg_output .= '		<tr><td></td><td><input type="password" pattern="'.$Password_Pattern.'" placeholder="Passwort Bestätigung" name="register_confirm_password" id="register_confirm_password" onkeyup="validatePassword()" required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>E-mail: </td><td><input type="Email" placeholder="E-mail" name="register_email" id="register_email" required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td></td><td><input type="Email" placeholder="E-mail Bestätigung" name="register_confirm_email" id="register_confirm_email" onkeyup="validateEmail()" required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Regionen:</td><td><span title= "' . "Regionen zum Eingeben:" .chr(10). "(getrennt mit mit einem Kommma)" .chr(10). region_list() . '"><input type="regions" placeholder="Regionen" name="regions" id="regions"></span></td></tr>' . chr(10);
			$Reg_output .= '	</table>';
			$Reg_output .= '<br>' . chr(10);
			$Reg_output .= '	<input type="hidden" name="page_action" id="page_action" value="register">' . chr(10);
			$Reg_output .= '	<input type="submit" id="LogSubmit" value="Registrieren" class="rkrk-login-primary" />' . chr(10);
			$Reg_output .= '</form>' . chr(10);
			//echo $Reg_output;
			// Login
			
			//exit;
			$Log_output= "";
			$Log_output .= '<form name="form2" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="POST">' . chr(10);
			$Log_output .= '	<table class="none" style="width:100%;background-color: transparent;border-collapse:separate;border-spacing:0 8px;">' . chr(10);
			//$Log_output .= '		<tr><td>Initialen:</td><td><input type="text" name="login_acronym" placeholder="Initialen" minlength="4" maxlength="6" size=20 required></td></tr>' . chr(10);
			$Log_output .= '		<tr><td>Benutzername:</td><td><input type="text" name="login_user" autocomplete="username" placeholder="Benutzername" minlength="2" maxlength="30" size=20 required></td></tr>' . chr(10);
			//$Log_output .= '		<tr><td>Name:</td><td><input type="text" name="login_name" placeholder="Name" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
			//$Log_output .= '		<tr><td>Vorname:</td><td><input type="text" name="login_prename" placeholder="Vorname" minlength="4" maxlength="8" size=20 required></td></tr>' . chr(10);
			$Log_output .= '		<tr><td>Passwort</td><td><input type="password" autocomplete="current-password" placeholder="Passwort" name="login_password" id="login_password" required></td></tr>' . chr(10);
			$Log_output .= '	</table>';
			$Log_output .= '<br>' . chr(10);
			$Log_output .= '	<input type="hidden" name="page_action" id="page_action" value="login">' . chr(10);
			$url ="?";
			foreach($_GET as $key => $value){
				$url .= $key . "=" . $value . "&";
			}
			//echo $url;
			$Log_output .= '	<input type="submit" id="LogSubmit" value="Einloggen" class="rkrk-login-primary" formaction="' . htmlspecialchars($_SERVER["PHP_SELF"])  . $url . '" />' . chr(10);
			//$Log_output .= '	<input type="submit" id="LogSubmit" value="Einloggen" formaction="' . htmlspecialchars($_SERVER["PHP_SELF"]) . $_SERVER['REQUEST_URI'] . '" />' . chr(10);
			//$Log_output .= '	<input type="submit" id="LogSubmit" value="Einloggen" />' . chr(10);
			$Log_output .= '</form>' . chr(10);
			//echo $Log_output;
			
			$output= "";
			$output .= "<style>
			#rkrk-login-card{font-family:Arial,system-ui,sans-serif;background:#ffffff;border:1px solid #d6d6d6;border-radius:10px;padding:14px 16px;box-shadow:0 4px 14px rgba(0,0,0,.14);max-width:min(94vw,560px);}
			#rkrk-login-card table td:first-child{font-weight:600;color:#203020;padding-right:10px;white-space:nowrap;}
			#rkrk-login-card input[type=text],#rkrk-login-card input[type=password],#rkrk-login-card input[type=email],#rkrk-login-card input[type=regions]{width:100%;max-width:300px;padding:7px 8px;border:1px solid #9db79d;border-radius:6px;box-sizing:border-box;}
			#rkrk-login-card .rkrk-login-primary{background:var(--brand-dark,#48673A);color:#fff;border:0;border-radius:7px;padding:8px 14px;cursor:pointer;font-weight:700;}
			#rkrk-login-card button{background:var(--brand,#96B588);color:#fff;border:0;border-radius:7px;padding:8px 12px;cursor:pointer;}
			#rkrk-login-card #swop_hint{display:block;margin-top:8px;margin-bottom:8px;color:#334;}
			#rkrk-login-card h3{margin:0 0 8px 0;color:#1f3f1f;font-size:18px;}
			</style>". chr(10);
			if ($FloatingTop) {
				$output .= "<div id='rkrk-login-popup' style='position:fixed;top:8px;left:50%;transform:translateX(-50%);z-index:2147483646;max-width:min(94vw,590px);max-height:90vh;overflow:auto;'>". chr(10);
				$output .= "<div id='rkrk-login-card'>". chr(10);
			} else {
				$output .= "<div id='rkrk-login-card' style='margin:14px auto;'>". chr(10);
			}
			$output .= "<h3>Benutzer Login</h3>". chr(10);
			if (!empty($GLOBALS['rkrk_login_error'])) {
				$output .= '<p id="rkrk-login-error" style="margin:0 0 10px 0;padding:8px 10px;background:#F8D7DA;border:1px solid #f1b0b7;border-radius:6px;color:#7a1f1f;font-weight:600;">' . htmlspecialchars((string)$GLOBALS['rkrk_login_error'], ENT_QUOTES, 'UTF-8') . '</p>' . chr(10);
			}
			$output .= '	<div id="bloc2">' .chr(10);
			$output .= $Log_output;
			$output .= '	</div>' . chr(10);
			$output .= '	<div style="display:none" id="bloc1">' .chr(10);
			$output .= $Reg_output;
			$output .= "	</div>" . chr(10);
			$output .= '<br>' . chr(10);
			$output .= '	<span id="swop_hint">swophint</span>' . chr(10);
			$output .= '	<span id="swop">swop</span>' . chr(10);
			//$output .= '<button type="button" title="Login vergessen" onclick="location.href=' .chr(39) . htmlspecialchars($_SERVER["PHP_SELF"]) .'?page_action=lostpw' . chr(39) .' ">Login vergessen</button>' . chr(10);
			$output .= '<button type="button" title="Passwort ändern" onclick="location.href=' .chr(39) . htmlspecialchars($_SERVER["PHP_SELF"]) .'?page_action=changepw' . chr(39) .' ">Passwort ändern</button><br><br>' . chr(10);
			$output .= '</div>' . chr(10);
			if ($FloatingTop) {
				$output .= '</div>' . chr(10);
			}

			echo $output;
			rkrk_access_emit_login_scripts();
			?>
			<script>
			var WhyLogin = "Direkt einloggen, falls bereits schon als Benutzer registriert.<br>";
			var WhyRegister = "Falls Benutzer noch nicht registriert ist, bitte [zum Registrieren] drücken.<br>";
			var ButtonLogin = "zum Einloggen";
			var ButtonRegister = "zum Registrieren";
			var ActionLogin = "login";
			var ActionRegister = "register";
			swop.innerHTML = "<br><button type='button' id='LogRegButton' title='Einloggen in die Website' onclick='SwopLog(\"bloc1\", \"bloc2\" , \"LogRegButton\", \""+ButtonLogin+"\", \""+ButtonRegister+"\",\"swop_hint\", \""+WhyLogin+"\", \""+WhyRegister+"\")'>"+ButtonRegister+"</button>";
			swop_hint.innerHTML = WhyRegister
			LogSubmit.value = ButtonLogin;
			</script>
			<?php

		} else {
			if ($Username!="" and false) {
				//$output .= "Benutzer Identifiziert:" ." <br>";
				foreach ($UserList as $key => $User) {
					if ($User["Initialen"]==$UserInitial) {
						$_SESSION[$SessNme_RegisteredUser] = $User;
						//pre_R($_SESSION[$SessNme_RegisteredUser]);
						$output .= "<br>Benutzer: " . $User["Name"] . " ". $User["Vorname"] ."<br>";
					}
				}
				echo $output;
			} else {		

				//exit;
			}
		}
	}
	if (($WebAction=="changepw")) {
		$CPW_output= "<div style='background-color: #D0E5D0;'>". chr(10);
		$CPW_output .= '<form name="form3" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="POST">' . chr(10);
		$CPW_output .= '	<table class="none" style="width=400;background-color: '.$BackgroundColor.'">' . chr(10);
		$CPW_output .= '		<tr><td>Benutzername:</td><td><input type="text" name="change_user" placeholder="Benutzername" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
		//$CPW_output .= '		<tr><td>Name:</td><td><input type="text" name="change_name" placeholder="Name" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
		//$CPW_output .= '		<tr><td>Vorname:</td><td><input type="text" name="change_prename" placeholder="Vorname" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
		$CPW_output .= '		<tr><td>Altes Passwort: </td><td><input type="password" placeholder="Passwort" name="old_password" id="old_password" onchange="" required></span> </td></tr>' . chr(10);
		$CPW_output .= '		<tr><td>Passwort: </td><td><input type="password" placeholder="Passwort" name="change_password" id="change_password" pattern="'.$Password_Pattern.'" onchange="CheckChangePassword(document.form3.change_password)" required><span id = "change_password_message" style="color:red"> </span> </td></tr>' . chr(10);
		$CPW_output .= '		<tr><td></td><td><input type="password" pattern="'.$Password_Pattern.'" placeholder="Passwort Bestätigung" name="change_confirm_password" id="change_confirm_password" onkeyup="validateChangePassword()" required></td></tr>' . chr(10);
		$CPW_output .= '	</table>';
		$CPW_output .= '<br>' . chr(10);
		$CPW_output .= '	<input type="hidden" name="page_action" id="page_action" value="changepw">' . chr(10);
		$CPW_output .= '	<input type="submit" id="LogSubmit" value="Passwort ändern" />' . chr(10);
		$CPW_output .= '</form><br>' .		chr(10);
		$CPW_output .= '</div>' . chr(10);
		//$CPW_output .= '<button type="button" title="Passwort ändern" onclick="location.href=' .chr(39) . htmlspecialchars($_SERVER["PHP_SELF"]) .'?page_action=changepw' . chr(39) .' ">Passwort ändern</button>' . chr(10);
		echo $CPW_output;
		
	}
	if (($WebAction=="lostpw")) {
	}
	//if ($Action=="logout") {
	//	session_destroy();
	//}
}

$CookieData= GetCookies($CookieKey);

/**
 * Login/Register-JS nur bei RegisterForm ausgeben — nicht automatisch beim include
 * (sonst landet <script> vor Favicon und der Browser holt …/assess/favicon.ico → 404).
 */
function rkrk_access_emit_login_scripts(): void {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	global $IconLoc, $Password_Pattern, $FormatMessage;
	$icon = isset($IconLoc) ? (string)$IconLoc : 'images/23color';
	if (function_exists('rkrk_menu_apply_diroffset')) {
		$icon = rkrk_menu_apply_diroffset($icon);
	}
	$pattern = isset($Password_Pattern) ? (string)$Password_Pattern : '(?=.*\\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}';
	$fmt = isset($FormatMessage) ? (string)$FormatMessage : '';
	echo "<script>\n";
	echo "var iconloc = " . json_encode($icon, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
	echo "var buttonsize = 22;\n";
	echo "var pwpattern = /^(?:" . $pattern . ")$/;\n";
	echo "var formatmessage = " . json_encode($fmt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
	echo <<<'JS'
function SwopLogold(mybloc1, mybloc2, mybutton, mytext1, mytext2) {
	var b1 = document.getElementById(mybloc1);
	var b2 = document.getElementById(mybloc2);
	var b = document.getElementById(mybutton);
	if (b1.style.display === "none") {
		b1.style.display = "block";
		b2.style.display = "none";
		b.innerHTML =  mytext1;
	} else {
		b1.style.display = "none";
		b2.style.display = "block";
		b.innerHTML = mytext2;
	}
}
function SwopLog(mybloc1, mybloc2, mybutton, mytext1, mytext2, myhint, myhint1, myhint2) {
	var b1 = document.getElementById(mybloc1);
	var b2 = document.getElementById(mybloc2);
	var b = document.getElementById(mybutton);
	var h = document.getElementById(myhint);
	if (b1.style.display === "none") {
		b1.style.display = "block";
		b2.style.display = "none";
		b.innerHTML =  mytext1;
		h.innerHTML =  myhint1;
	} else {
		b1.style.display = "none";
		b2.style.display = "block";
		b.innerHTML =  mytext2;
		h.innerHTML =  myhint2;
	}
}
function UpdConfPassword() {
	var password = document.getElementById("register_password");
	var confirm_password = document.getElementById("register_confirm_password");
}
function CheckPassword(inputtxt) {
	var passw = pwpattern;
	var message = document.getElementById("password_message")
	if(inputtxt.value.match(passw)) {
		message.style.color = "green";
		message.innerHTML= "Passwortstruktur korrekt";
		return true;
	} else {
		message.style.color = "red";
		message.innerHTML= formatmessage;
		return false;
	}
}
function CheckChangePassword(inputtxt) {
	var passw = pwpattern;
	var message = document.getElementById("change_password_message")
	if(inputtxt.value.match(passw)) {
		message.style.color = "green";
		message.innerHTML= "Passwortstruktur korrekt";
		return true;
	} else {
		message.style.color = "red";
		message.innerHTML= formatmessage;
		return false;
	}
}
function validatePassword(){
	var password = document.getElementById("register_password");
	var confirm_password = document.getElementById("register_confirm_password");
	if(password.value != confirm_password.value) {
		confirm_password.setCustomValidity("Passwörter nicht identisch!");
	} else {
		confirm_password.setCustomValidity('');
	}
}
function validateChangePassword(){
	var password = document.getElementById("change_password");
	var confirm_password = document.getElementById("change_confirm_password");
	if(password.value != confirm_password.value) {
		confirm_password.setCustomValidity("Passwörter nicht identisch!");
	} else {
		confirm_password.setCustomValidity('');
	}
}
function validateEmail(){
	var email = document.getElementById("register_email");
	var confirm_email = document.getElementById("register_confirm_email");
	if(email.value != confirm_email.value) {
			confirm_email.setCustomValidity("Email nicht identisch!");
	} else {
			confirm_email.setCustomValidity('');
	}
}
JS;
	echo "\n</script>\n";
}


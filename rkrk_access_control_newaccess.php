<?php
  session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_csvfiles.php');
	
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
						"RegZugriff",
						"PilotInitial",
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
		$WebAction="login";
	}

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
		$Cookie_User = $_COOKIE[$CookieKey][$SessNme_RegisteredUser];
		if (isset($_POST['login_user'])) {
			$InputUserInfo["Login"]["Benutzername"]=$_POST['login_user'];
		} elseif (($Cookie_User!="") and $EnableCookie) {
			$InputUserInfo["Login"]["Benutzername"]=$Cookie_User;
		} elseif ($_SESSION[$SessNme_RegisteredUser]!="") {
			$InputUserInfo["Login"]["Benutzername"]=$_SESSION[$SessNme_RegisteredUser];
		} else 
			$InputUserInfo["Login"]["Benutzername"]="";

		$Cookie_AccessLevel = $_COOKIE[$CookieKey][$SessNme_AccessLevel];
		if ($_SESSION[$SessNme_AccessLevel]!="")
			$InputUserInfo["Login"]["Level"]=$_SESSION[$SessNme_AccessLevel];
		elseif (($Cookie_AccessLevel!="") and $EnableCookie)
			$InputUserInfo["Login"]["Level"]=$Cookie_AccessLevel;
		else 
			$InputUserInfo["Login"]["Level"]="";

		$Cookie_AccessInitialen = $_COOKIE[$CookieKey][$SessNme_RegisteredInitialen];
		if ($_SESSION[$SessNme_RegisteredInitialen]!="")
			$InputUserInfo["Login"]["Initialen"]=$_SESSION[$SessNme_RegisteredInitialen];
		elseif (($Cookie_AccessInitialen !="") and $EnableCookie)
			$InputUserInfo["Login"]["Initialen"]=$Cookie_AccessInitialen ;
		else 
			$InputUserInfo["Login"]["Initialen"]="";

		$Cookie_AccessVorname = $_COOKIE[$CookieKey][$SessNme_RegisteredVorname];
		if ($_SESSION[$SessNme_RegisteredVorname]!="")
			$InputUserInfo["Login"]["Vorname"]=$_SESSION[$SessNme_RegisteredVorname];
		elseif (($Cookie_AccessVorname !="") and $EnableCookie)
			$InputUserInfo["Login"]["Vorname"]=$Cookie_AccessVorname ;
		else 
			$InputUserInfo["Login"]["Vorname"]="";

		$Cookie_AccessName = $_COOKIE[$CookieKey][$SessNme_RegisteredName];
		if ($_SESSION[$SessNme_RegisteredName]!="")
			$InputUserInfo["Login"]["Name"]=$_SESSION[$SessNme_RegisteredName];
		elseif (($Cookie_AccessName!="") and $EnableCookie)
			$InputUserInfo["Login"]["Name"]=$Cookie_AccessName;
		else 
			$InputUserInfo["Login"]["Name"]="";

		$Cookie_AccessEmail = $_COOKIE[$CookieKey][$SessNme_RegisteredEmail];
		if ($_SESSION[$SessNme_RegisteredEmail]!="")
			$InputUserInfo["Login"]["Email"]=$_SESSION[$SessNme_RegisteredEmail];
		elseif (($Cookie_AccessEmail!="") and $EnableCookie)
			$InputUserInfo["Login"]["Email"]=$_COOKIE[$CookieKey][$SessNme_RegisteredEmail];
		else 
			$InputUserInfo["Login"]["Email"]="";

		$Cookie_Password = $_COOKIE[$CookieKey][$SessNme_Password];
		if (isset($_POST['login_password']))
			$InputUserInfo["Login"]["Passwort"] = $_POST['login_password'];
		elseif (($Cookie_Password!="") and $EnableCookie)
			$InputUserInfo["Login"]["Passwort"]=$Cookie_Password;
		elseif ($_SESSION[$SessNme_Password]!="")
			$InputUserInfo["Login"]["Passwort"]=$_SESSION[$SessNme_Password];
		else 
			$InputUserInfo["Login"]["Passwort"]="";

		$Cookie_Regions = $_COOKIE[$CookieKey][$SessNme_SelRegions];
		if (isset($_POST['regions']))
			$InputUserInfo["Login"]["Regions"] = $_POST['regions'];
		elseif (($Cookie_Regions!="") and $EnableCookie)
			$InputUserInfo["Login"]["Regions"]=$Cookie_Regions;
		elseif ($_SESSION[$SessNme_SelRegions]!="")
			$InputUserInfo["Login"]["Regions"]=$_SESSION[$SessNme_SelRegions];
		else 
			$InputUserInfo["Login"]["Regions"]="";

		$PasswordHashed = $_COOKIE[$CookieKey][$SessNme_PasswordHashed];
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
			$dateformat,
			$Layout;
	
	//pre_R($LogInGranted);
	//echo "WebAction: " . $WebAction . "<br>";
		if ($Layout=="2023") {
		$BackgroundColor = "#E0E0E0";
	} else {
		$BackgroundColor = "#D0E5D0";
	}	

	
	if ($EnableAccessControl) {
		if ($WebAction=="logout") {
			LogoutAndClearData();
			$AccSystemInfo .= "<ul>";
			$AccSystemInfo .= "<li><b>Benutzer ausgelogged</b></li>";
			$AccSystemInfo .= "<i>Zum einloggen:" . "</i><br>" . chr(10);
			$AccSystemInfo .= "</ul>";
			$AccSystemInfo .= CreateSiteLink ("Home", "");
			PrintDebugInformation();
			exit;
		}

		if ($WebAction!="register") {
			CheckAccessAndAutoforward();
			if ($InputUserInfo["Login"]["Level"]=="gast") {
				$LogInGranted = true;
			} else {
				$LogInGranted = CheckLoginAndSetUser();
				if ($LogInGranted) UpdateAccessDate();
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
			PrintDebugInformation();
			exit;
			
		} elseif ($LogInGranted==false) {
			echo "<table style='background-color: ".$BackgroundColor.";'><tr><td>";
			echo "<br><b>Benutzer - Login</b>" . "<br>";
			echo "<i>Bitte einloggen, resp. registrieren, falls kein Konto vorhanden ist:</i>" . "<br>". "<br>";
			echo "</td><td>";
			echo CreateSiteLink ("Home", "");
			echo CreateSiteLink ("GoBack", "");
			echo "</td></tr></table>";
			$IPAddress = getIPAddress();
			$UserAddresses = GetUserList();
			RegisterForm($IPAddress);
			//UpdateAccessDate();
			PrintDebugInformation();
			$LoggedOn=false;
			//echo $ThisPage(basename) . " - " . $MainMenu["Home"]["site"];
			$ThisPage = basename($_SERVER['PHP_SELF']);
			if ($ThisPage!=$MainMenu["Home"]["site"]) {
				exit;
			} else {
				//echo "Weitermachen!";
			}
			echo "</div>";
		} 

	} else {
	// Access Control Disabled
		if ($WebAction=="logout") {
			if ($AccessDebugOutput)	echo "logout[1]: " ."<br>";
				LogoutAndClearData();
			//$_SESSION[$SessNme_RegisteredUser]="";
			//DelCookie($SessNme_RegisteredUser);
		}
	}
	
	ShowAccessLevel();
	PrintDebugInformation();
};

//Handle Cookies Store delete

Function LogoutAndClearData() {
	global $InputUserInfo,
			$AccDebugInfo,
			$SessNme_Sessionstart,
			$SessNme_RegisteredUser,
			$SessNme_AccessLevel,
			$SessNme_Password,
			$SessNme_PasswordHashed,
			$SessNme_SelRegions,
			$SessNme_RegisteredName,
			$SessNme_RegisteredVorname,
			$SessNme_RegisteredEmail,
			$SessNme_IPAddress;			
			
	if ($AccessDebugOutput)	
		echo "logout: " ."<br>";
	$InputUserInfo["Login"]["Vorname"]="";
	$InputUserInfo["Login"]["Name"]="";
	$InputUserInfo["Login"]["Benutzername"]="";
	$InputUserInfo["Login"]["Level"]="";
	$InputUserInfo["Login"]["Passwort"]="";
	$InputUserInfo["Login"]["PasswordHashed"]="";
	$InputUserInfo["Login"]["Regions"]="";

	$_SESSION[$SessNme_UserList][$UserKey]["Initialen"]="";
	$_SESSION[$SessNme_UserList][$UserKey]["Name"]="";
	$_SESSION[$SessNme_UserList][$UserKey]["Vorname"]="";
	$_SESSION[$SessNme_UserList][$UserKey]["Email"]="";
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

	//DeleteCookie($CookieKey, $SessNme_Sessionstart);
	//DeleteCookie($CookieKey ,$SessNme_RegisteredUser);
	//DeleteCookie($CookieKey, $SessNme_AccessLevel);
	//DeleteCookie($CookieKey, $SessNme_Password);
	//DeleteCookie($CookieKey, $SessNme_PasswordHashed);
	WriteToLog("logout", basename($_SERVER['PHP_SELF']), $UserKey ,"","","");
	$LogInGranted=false;
}

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

Function CheckAccessAndAutoforward() {
	global 	$GuestAccess,
			$MainMenu,
			$InputUserInfo,
			$WebFreigabe,
			$WebLink_intern;

	// Finanzen-Modul unter ar/public/: nicht in $MainMenu per basename — sonst fälschlich „Access not granted“.
	$reqPath = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '')));
	if ($reqPath !== '' && strpos($reqPath, 'ar/public') !== false) {
		return;
	}
	
	$ThisPage = basename($_SERVER['PHP_SELF']);
	if (($GuestAccess) and ($InputUserInfo["Login"]["Level"]=="")) $InputUserInfo["Login"]["Level"]=$GuestAccess;
	if ($InputUserInfo["Login"]["Level"]!="") $WebFreigabe = $InputUserInfo["Login"]["Level"];
	if ($ThisPage!=$MainMenu["Home"]["site"]) {
		$PageCheckDone = false;
		$ForwardToDo = true;
		foreach ($MainMenu as $Key => $Menu) {
			if ($Menu["site"]==$ThisPage) {
				//echo $Menu["site"] . " - " .$ThisPage . " - " . $WebFreigabe . "<br>";
				if ($ThisPage == $Menu["site"]) {
					$PageCheckDone = true;
					//echo $Menu["title"] . " " . $Menu["site"] . ": " . $Menu["release"] . " - " .$InputUserInfo["Login"]["Level"] . "  - page checked <br>";
					if ((strpos(" " . $Menu["release"], $InputUserInfo["Login"]["Level"])!= false)) {
					//if (In_Array($InputUserInfo["Login"]["Level"],explode(",",$Menu["release"]))) {
						//echo ": access granted";
						$ForwardToDo = false;
					}
				}
			}
		}
		//echo "<br>";
		if (((!$PageCheckDone) or ($ForwardToDo))) 
			{
			echo "Access to page not granted forward to main home page!<br>";
			//$NewPageLink = "https://tchr-frog.synology.me:5682/rkropen/test/rkrk.php" 
			$NewPageLink =  $WebLink_intern . $MainMenu["Home"]["site"];
			//header('Location: '. $NewPageLink);
			//die();
			//break;
			?>
			<script>
			<?php 
			echo "var newlocation = '". $NewPageLink . "';\n";
			 ?>
			//location.replace(newlocation);
			</script>
			<?php
		}
	}
}	

Function SetRegionalAccess($AccessRegions) {
	global 	$RegionSettings;
	
	//echo "Zugriff: " .$AccessRegions;		
	
	foreach ($RegionSettings  as $Key => $Reg) {
		$RegArray=Array();
		if ($AccessRegions=="alle") {
			$RegionSettings[$Key]["Access"]= true;
			$RegArray=$Key;
			//echo ("Access to " . $Key . "<br>");
		} else {
			if (in_array($Key,explode(",",$AccessRegions))) {
				$RegArray=$Key;
				$RegionSettings[$Key]["Access"]= true;
				//if ($RegionSettings[$Key]["Access"]) echo ("Access to " . $Key . "<br>");
			} else {
				$RegionSettings[$Key]["Access"]= false;
			}
		}
	}
	$RegionSettings["ALL"]["JGList"]=  implode(",",$RegArray);
}

Function ShowAccessLevel() {
	global 	$AccDebugInfo,
			$AccessDebugOutput,
			$SessNme_RegisteredUser,
			$SessNme_AccessLevel,
			$SessNme_Sessionstart;
			
	if ($AccessDebugOutput) {
		$AccDebugInfo.= "<li>Registrierte Nutzer: " . $_SESSION[$SessNme_RegisteredUser] . " [" . $_SESSION[$SessNme_AccessLevel] . "," . $_COOKIE[$SessNme_Sessionstart] . "]</li>";
	}
}

Function PrintDebugInformation() {
	global 	$EnableCookie,
			$AccDebugInfo,
			$AccSystemInfo,
			$AccWarningInfo,
			$AccessDebugOutput;
			
			
	if ($AccSystemInfo != "") {
		echo "<div style='background-color: #FEF9E7;'><font size='2px'>". chr(10);
		echo "System Meldungen:". chr(10);
		echo $AccSystemInfo;
		echo "</font></div>". chr(10);
	}
	if (($AccDebugInfo != "") and $AccessDebugOutput) {
		echo "<div style='background-color: #A3E2FF;'><font size='2px'>". chr(10);
		echo "Debug: <br>". chr(10);
		echo $AccDebugInfo;
		echo "</font></div>". chr(10);
	}
	if ($AccWarningInfo != "") {
		echo "<div style='background-color: #FDEDEC;'><font size='2px'>". chr(10);
		echo "Warnungen: <br>". chr(10);
		echo $AccWarningInfo;
		echo "</font></div>". chr(10);
	}
	if ($AccessDebugOutput) {
		if ($EnableCookie) {
			echo "<font size='2px'>". chr(10);
			echo "COOKIE daten:". chr(10);
			pre_r($_COOKIE);
			echo "</font>". chr(10);
		}
	}
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
	foreach ($predata as $entry) {
		$data[$entry['Benutzername']]=$entry;
		$data[$entry['Benutzername']]["Passwort_hashed"] = password_hash($data[$entry['Benutzername']]["Passwort"], PASSWORD_DEFAULT);
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
			$dateformat;

	GetUserList();
	$UserKey=$InputUserInfo["Login"]["Benutzername"];

	if (EnableCookie) {
		$CookieData = $_COOKIE[$CookieKey];
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

	if (array_key_exists($UserKey,$_SESSION[$SessNme_UserList])) {
		if (
			(password_verify($InputUserInfo["Login"]["Passwort"], $_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"])) 
				or password_verify($_SESSION[$SessNme_Password], $_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"])
				//or ($CookieData[$PasswordHashed]== $_SESSION[$SessNme_UserList][$UserKey]["Passwort_hashed"])
			) {
			
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
			SetRegionalAccess($_SESSION[$SessNme_UserList][$UserKey]["RegZugriff"]);
			return Array("Benutzername" => $UserKey, "Level" => $InputUserInfo["Login"]["Level"]);
		} else {
			$LoggedOn=false;
			$AccSystemInfo .= "<ul>";
			$AccSystemInfo .= "<li><b>Access denied</b></li>";
			$AccSystemInfo .= "Benutzername, -vorname oder Passwort ungültig!<br>Bitte nochmals versuchen." . chr(10);
			$AccSystemInfo .= "</ul>";
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

Function RegisterForm($IPAddress,$Action="Login") {
	global $SessNme_RegisteredUser,$WebAction,$Password_Pattern,$Layout;
	
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
		echo "<div style='background-color: ".$BackgroundColor.";'>". chr(10);
		//if (!$IPRegistered or true) {
		if (true) {
			// New User to Register
			$Reg_output= "";
			$Reg_output .= '<form name="form1" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="POST">' . chr(10);
			$Reg_output .= '	<table class="none" style="width=400;background-color: '.$BackgroundColor.';">' . chr(10);
			$Reg_output .= '		<tr><td>Benutzername:</td><td><input type="text" name="register_user" placeholder="Benutzername" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Initialen:</td><td><input type="text" name="register_acronym" placeholder="Initialen" minlength="3" maxlength="6" size=20 required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Name:</td><td><input type="text" name="register_name" placeholder="Name" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Vorname:</td><td><input type="text" name="register_prename" placeholder="Vorname" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Passwort: </td><td><input type="password" placeholder="Passwort" name="register_password" id="register_password" pattern="'.$Password_Pattern.'" onchange="CheckPassword(document.form1.register_password)" required><span id = "password_message" style="color:red"> </span> </td></tr>' . chr(10);
			$Reg_output .= '		<tr><td></td><td><input type="password" pattern="'.$Password_Pattern.'" placeholder="Passwort Bestätigung" name="register_confirm_password" id="register_confirm_password" onkeyup="validatePassword()" required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>E-mail: </td><td><input type="Email" placeholder="E-mail" name="register_email" id="register_email" required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td></td><td><input type="Email" placeholder="E-mail Bestätigung" name="register_confirm_email" id="register_confirm_email" onkeyup="validateEmail()" required></td></tr>' . chr(10);
			$Reg_output .= '		<tr><td>Regionen:</td><td><span title= "' . "Regionen zum Eingeben:" .chr(10). "(getrennt mit mit einem Kommma)" .chr(10). region_list() . '"><input type="regions" placeholder="Regionen" name="regions" id="regions"></span></td></tr>' . chr(10);
			$Reg_output .= '	</table>';
			$Reg_output .= '<br>' . chr(10);
			$Reg_output .= '	<input type="hidden" name="page_action" id="page_action" value="register">' . chr(10);
			$Reg_output .= '	<input type="submit" id="LogSubmit" value="Registrieren" />' . chr(10);
			$Reg_output .= '</form>' . chr(10);
			//echo $Reg_output;
			// Login
			
			//exit;
			$Log_output= "";
			$Log_output .= '<form name="form2" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="POST">' . chr(10);
			$Log_output .= '	<table class="none" style="width=400;background-color: '.$BackgroundColor.';">' . chr(10);
			//$Log_output .= '		<tr><td>Initialen:</td><td><input type="text" name="login_acronym" placeholder="Initialen" minlength="4" maxlength="6" size=20 required></td></tr>' . chr(10);
			$Log_output .= '		<tr><td>Benutzername:</td><td><input type="text" name="login_user" placeholder="Benutzername" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
			//$Log_output .= '		<tr><td>Name:</td><td><input type="text" name="login_name" placeholder="Name" minlength="4" maxlength="30" size=20 required></td></tr>' . chr(10);
			//$Log_output .= '		<tr><td>Vorname:</td><td><input type="text" name="login_prename" placeholder="Vorname" minlength="4" maxlength="8" size=20 required></td></tr>' . chr(10);
			$Log_output .= '		<tr><td>Passwort</td><td><input type="password" placeholder="Passwort" name="login_password" id="login_password" onkeyup="validatePassword()" required></td></tr>' . chr(10);
			$Log_output .= '	</table>';
			$Log_output .= '<br>' . chr(10);
			$Log_output .= '	<input type="hidden" name="page_action" id="page_action" value="login">' . chr(10);
			$url ="?";
			foreach($_GET as $key => $value){
				$url .= $key . "=" . $value . "&";
			}
			//echo $url;
			$Log_output .= '	<input type="submit" id="LogSubmit" value="Einloggen" formaction="' . htmlspecialchars($_SERVER["PHP_SELF"])  . $url . '" />' . chr(10);
			//$Log_output .= '	<input type="submit" id="LogSubmit" value="Einloggen" formaction="' . htmlspecialchars($_SERVER["PHP_SELF"]) . $_SERVER['REQUEST_URI'] . '" />' . chr(10);
			//$Log_output .= '	<input type="submit" id="LogSubmit" value="Einloggen" />' . chr(10);
			$Log_output .= '</form>' . chr(10);
			//echo $Log_output;
			
			$output= "";
			$output= "<span style='background-color: ".$BackgroundColor.";'>". chr(10);
			$output .= '	<div style="display" id="bloc2">' .chr(10);
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
			$output .= '</span>' . chr(10);

			echo $output;
			
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

?>
<script>
<?php 
echo "var iconloc = '". $IconLoc . "';\n";
echo "var buttonsize = ". 22 . ";\n";
echo "var pwpattern = /^" . $Password_Pattern ."$/;\n";
echo "var formatmessage = '" . $FormatMessage . "';\n";

 ?>
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
	//password.value = confirm_password.value;
}


function CheckPassword(inputtxt) 
{ 
	var passw = pwpattern;
	//var passw = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/;
	
	var message = document.getElementById("password_message")
	if(inputtxt.value.match(passw)) 
	{ 
		message.style.color = "green";
		message.innerHTML= "Passwortstruktur korrekt";
		//alert('Correct, try another...')
		return true;
	}
	else
	{ 
		message.style.color = "red";
		message.innerHTML= formatmessage;
		//alert('Wrong...!')
		return false;
	}
}

function CheckChangePassword(inputtxt) 
{ 
	var passw = pwpattern;
	//var passw = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/;
	var message = document.getElementById("change_password_message")
	if(inputtxt.value.match(passw)) 
	{ 
		message.style.color = "green";
		message.innerHTML= "Passwortstruktur korrekt";
		//alert('Correct, try another...')
		return true;
	}
	else
	{ 
		message.style.color = "red";
		message.innerHTML= formatmessage;
		//alert('Wrong...!')
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

</script>


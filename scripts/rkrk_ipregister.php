<?php
	$IPUsed = Array (
						"178.196.203.171" => Array("Name" =>"AA", "Provider" => ""),
						"" => Array("Name" =>"AA", "Provider" => ""),
						"" => Array("Name" =>"AA", "Provider" => ""),
						"84.227.84.25" => Array("Name" =>"VP", "Provider" => ""),
						"" => Array("Name" =>"VP", "Provider" => ""),
						"" => Array("Name" =>"VP", "Provider" => ""),
						"82.136.104.72" => Array("Name" =>"CH", "Provider" => "wwz"),
						"18.156.116.217" => Array("Name" =>"CH", "Provider" => ""),
						"18.156.32.181" => Array("Name" =>"CH", "Provider" => ""),
						"178.197.197.249" => Array("Name" =>"CH", "Provider" => "SwCom"),
						"" => Array("Name" =>"CH", "Provider" => ""),
						"" => Array("Name" =>"CH", "Provider" => ""),
						"104.28.42.99" => Array("Name" =>"LF", "Provider" => "iCloud "),
						"178.199.69.58" => Array("Name" =>"MB", "Provider" => "SwCom"),
						"85.7.103.205" => Array("Name" =>"MB", "" => "Sunrise"),
						"46.253.188.22" => Array("Name" =>"MB?", "Provider" => ""),
						"" => Array("Name" =>"MB", "Provider" => ""),
						"195.34.89.229" => Array("Name" =>"TC", "Provider" => "ubx"),
						"195.34.89.229 " => Array("Name" =>"TC", "Provider" => "ubx"),
						"82.136.104.67" => Array("Name" =>"TC", "Provider" => "wwz"),
						"192.168.1.1" => Array("Name" =>"TC", "Provider" => "home"),
						"178.197.208.74" => Array("Name" =>"TC", "Provider" => "SwCom"),
						"178.197.212.146" => Array("Name" =>"TC", "Provider" => "SwCom"),
						"178.197.196.249" => Array("Name" =>"TC", "Provider" => "SwCom"),
						"178.197.192.193" => Array("Name" =>"TC", "Provider" => "SwCom"),
						"178.194.213.78" => Array("Name" =>"TC", "Provider" => "SwCom"),
						"213.162.81.152" => Array("Name" =>"TC", "Provider" => "AU"),
						"" => Array("Name" =>"TC", "Provider" => ""),
						"" => Array("Name" =>"TC", "Provider" => ""),
						"18.156.32.181" => Array("Name" =>"SRVR", "Provider" => "Server SG"),
						"18.156.116.217" => Array("Name" =>"SRVR", "Provider" => "Server SG0"),
						"3.120.219.207" => Array("Name" =>"SRVR", "Provider" => "DEA100"),
						"34.214.104.42" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"34.211.77.101" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"54.68.37.96" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"54.203.134.150" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"54.218.104.187" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"54.214.114.194" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"35.92.192.139" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"35.88.226.185" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"35.92.192.139" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"35.87.125.238" => Array("Name" =>"SRVR", "Provider" => "USAMZ"),
						"" => Array("Name" =>"SRVR", "Provider" => ""),
						"" => Array("Name" =>"SRVR", "Provider" => ""),
						"193.134.10.34" => Array("Name" =>"MB", "Provider" => "ZUG.CH, Netzwerk der Kantonalen Verwaltung Zug"),
						"213.55.226.127" => Array("Name" =>"MB", "Provider" => "Salt"),
						"" => Array("Name" =>"", "Provider" => ""),
						"188.61.251.2" => Array("Name" =>"LZ?", "" => "Bluewin"),
						"194.230.144.209" => Array("Name" =>"LZ", "" => "Bluewin"),
						"213.55.224.70" => Array("Name" =>"??", "Provider" => "Salt"),
						"188.60.21.223" => Array("Name" =>"LF", "Provider" => "Bluewin"),
						"" => Array("Name" =>"", "Provider" => ""),
						"" => Array("Name" =>"", "Provider" => ""),
						"" => Array("Name" =>"", "Provider" => ""),
						
	);

	$WhitelistGroup = Array (
						"195.34" => Array("Name" =>"CH", "Provider" => "Swisscom"),
						"178.197.217" => Array("Name" =>"CH", "Provider" => "Swisscom"),
						"188.60.21.223" => Array("Name" =>"CH", "Provider" => "Swisscom"),
						"185.45.239.35" => Array("Name" =>"BH", "Provider" => ""),
						"" => Array("Name" =>"SRVR", "Provider" => ""),
						"" => Array("Name" =>"SRVR", "Provider" => ""),
					);

	$IPBanned = Array (
						//"178.196.203.171" => Array("Name" =>"DataCenter", "Provider" => "Andi Amstutz"),
						"" => Array("Name" =>"SRVR", "Provider" => ""),
						"" => Array("Name" =>"SRVR", "Provider" => ""),
					);

	$IPBannedGroup = Array (
						"66" => Array("Name" =>"CN", "Provider" => ""),
						//"185" => Array("Name" =>"CN", "Provider" => ""),
						// Bruno wurde blockiert!!!!
						//"31.10" => Array("Name" =>"RS", "Provider" => "Russia Telecom"),
						// gibt auch Schweizer provider: z.B. 31.10.142.1
						"40.77" => Array("Name" =>"US", "Provider" => "Microsoft cooperation"),
						"185.191.171" => Array("Name" =>"US", "Provider" => "Google cooperation"),
						"143.244.47" => Array("Name" =>"US", "Provider" => "Datacamp, US"),
						"154.47.16" => Array("Name" =>"Col", "Provider" => "Datacamp, Columbia"),
						"162.19.145" => Array("Name" =>"GE", "Provider" => "OVH SAS, Germany"),
						"146.70.173" => Array("Name" =>"US", "Provider" => "M247 Europe SRL, US"),
						"3.0." => Array("Name" =>"US", "Provider" => "Amazone, SG"),
						"54.254" => Array("Name" =>"SG", "Provider" => "Amazone, SG"),
						"52.221" => Array("Name" =>"SG", "Provider" => "Amazone, SG"),
						"13" => Array("Name" =>"SG", "Provider" => "Singapore, SG"),
						"18.138" => Array("Name" =>"SG", "Provider" => "Singapore, SG"),
						"18.139" => Array("Name" =>"SG", "Provider" => "Singapore, SG"),
						"18.140" => Array("Name" =>"SG", "Provider" => "Singapore, SG"),
						"18.141" => Array("Name" =>"SG", "Provider" => "Singapore, SG"),
						"18.142" => Array("Name" =>"SG", "Provider" => "Singapore, SG"),
						"18.143" => Array("Name" =>"SG", "Provider" => "Singapore, SG"),
						"191.96" => Array("Name" =>"US", "Provider" => "Agent, US"),
					);

function ProtectFromAccess ($TCPAdress) {
	global $IPBanned,$IPBannedGroup, $IPUsed, $WhitelistGroup;
	if (!is_array($WhitelistGroup ?? null)) {
		$WhitelistGroup = [];
	}
	//pre_r($IPBanned);
	//pre_r($IPBannedGroup);
	$TCPAdress = getIPAddress();
	//pre_r(explode(".",$TCPAdress)) . "<br>";
	$TCPIP =explode(".",$TCPAdress);
	if ((array_key_exists($TCPAdress, $WhitelistGroup))) {
		// in whitelist
		return (false);
	} else {
		// check if banned
		return  (
				(array_key_exists($TCPIP[0], $IPBannedGroup)) 
				or (array_key_exists($TCPIP[0].".".$TCPIP[1], $IPBannedGroup))
				or (array_key_exists($TCPIP[0].".".$TCPIP[1].".".$TCPIP[2], $IPBannedGroup))
				or (array_key_exists($TCPAdress, $IPBanned))
			);
	}		
}

?>
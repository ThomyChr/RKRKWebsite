<script type='text/javascript'>
<?php
//echo json_encode($DroneData);
echo "var PrefParameters = ". json_encode($PrefParameters) . ";\n"; 
echo "var DefaultSettings = ". json_encode($DefaultSettings) . ";\n"; 
echo "var FlightSettings = ". json_encode($FlightSettings) . ";\n"; 
echo "var Hints = ". json_encode($Hints) . ";\n"; 
echo "var DroneData = ". json_encode($DroneData) . ";\n";
echo "var CalcData = ". json_encode($CalcOptions) . ";\n";
echo "var calciconloc = '". $CalcIconLoc . "';\n";
echo "var setting = '". $setting . "';\n";
echo "var SelectedDrone = '". $set_drone. "';\n";
echo "var SelectedCalc = '". $set_calc. "';\n";
echo "var HAngle = '';\n";
echo "var VAngle = '';\n";
?>

	var lgreen = "#e9f7ef";
	var lred = "#fdedec";
	var lyellow = "#fef9e7";

function round(number, form) {
	if (form=="up") {
		offset=0.5;
	} else if (form=="down") {
		offset=-0.5;
	} else {
		offset=0;
	}

	if (number>100) {
		round_factor=10;
	} else if (number>40) {
		round_factor=5;
	} else if (number>22) {
		round_factor=2;
	} else {
		round_factor=1;
	}
	return Math.round((number/round_factor)+offset)*round_factor;
}

// Functions
// =========


function selectElement(id, valueToSelect) {    
    let element = document.getElementById(id);
    element.value = valueToSelect;
}

function GetDroneList() {
	text = "<h3>Auswahl der Kamera/Drohnen:</h3>";
	text = text +"<ul>";
	for (var key in DroneData) {
    var value = DroneData[key];
		text = text+ "<li><b><a href='"+window.location.href+"&drone="+key+"'>" + key + "</a></b>: "+ value["Name"] + "</li>";
	}
	text = text +"</ul>";
	return text;
}


function UpdDrone(drone, line) {
  //alert("Drohne geändert auf: " + drone);
  document.getElementById("drone").innerHTML = "<b>"+DroneData[drone]["Name"]+"</b>";
  document["dronepic"].src = calciconloc+DroneData[drone]["img"];
  document.getElementById("step2").style.display= 'block';
  HAngle=DroneData[drone]["IRCamAngle_H"];
  VAngle=DroneData[drone]["IRCamAngle_V"];
	if (DroneData[drone]["DFOV"]==0) {
		document.getElementById("dfov").style.display= 'none';
	} else {
		document.getElementById("dfov").style.display= 'block';
		document.getElementById("dfov").innerHTML = "Diagonal Field of View (DFOV): <b>"+DroneData[drone]["DFOV"]+"</b>°";
	}
  Resolution=DroneData[drone]["IRCamResolution_H"] + "x" + DroneData[drone]["IRCamResolution_V"] ;
  document.getElementById("droneangle_H").innerHTML = "Winkel Objektiv (horziontal): <b>"+DroneData[drone]["IRCamAngle_H"]+"</b>°";
	if (DroneData[drone]["IRCamAngle_V"]==0) {
		document.getElementById("droneangle_V").style.display= 'none';
	} else {
		document.getElementById("droneangle_V").style.display= 'block';
		document.getElementById("droneangle_V").innerHTML = "Winkel Objektiv (vertikal): <b>"+DroneData[drone]["IRCamAngle_V"]+"</b>°";
	}
	document.getElementById("droneresolution").innerHTML = "Auflösung Wärmebildkamera: <b>"+Resolution+"</b> px";
  SelectedDrone = drone; 
  document.getElementById("dronelist").innerHTML= "";

  UpdCalc(SelectedCalc, line);
}

function CalcBahnabstand (height, line) {
	//alert("Berechne Bahnabstand, Höhe = " + height + ", Zeile: " + line);
	
	//Inputs
	fh = parseInt(height);							// Flughöhe
	oa = DroneData[SelectedDrone]["IRCamAngle_H"];  //Objektivwinkel
	ua = oa*0.15;  									// Überlappungswinkel
	bf_t = 2.0;										// Bahnfaktor tief
	bf_h = 2.5;										// Bahnfaktor hoch
	bf_m = (bf_t+bf_h)/2;							// Bahnfaktor mitte
	rf_t = 1.5;										// Randfaktor tief
	rf_h = 2.0;										// Randfaktor hoch

	sib = 2*Math.tan((oa/2)*Math.PI/180)*fh;		//Sichtbreite mit Überlappung [m
	sub = 2*Math.tan((oa/2-ua)*Math.PI/180)*fh;		//Suchbreite ohne Überlappung [m]
	ul = (Math.tan((oa/2)*Math.PI/180)-Math.tan((oa/2-ua)*Math.PI/180))*fh; //Überlappung [m]

	bb = round(sib-bf_h*ul,"") + " - " + round(sib-bf_t*ul,"");	// Empfohlene Bahnbreite
	ra = round(rf_t*ul,"down") + " - " + round(rf_h*ul,"up"); 	// Randabstand
	bbuav = round(sib-bf_m*ul,"");								// Bahnbreite UAV
	rauav = round((rf_t+rf_h)/2*ul,"");		 					// Randabstand UAV

	//Resultate ausgeben
	//Sichtbreite [m]
	document.getElementById("SBmU_m_"+line).innerHTML=round(sib);
	//Überlappung[m]
	document.getElementById("SBpS_m_"+line).innerHTML=round(ul,"");
	//Suchbreite ohne Überlappung[m]
	document.getElementById("SuBmU_m_"+line).innerHTML=round(sub,"");
	//Abstand der Flubahn [m]
	document.getElementById("EFbB_m_"+line).innerHTML=bb;
	//Empfohlener Randabstand [m]
	document.getElementById("ERA_m_"+line).innerHTML=ra;
	//UAV Bahnbreite [m]
	document.getElementById("UBB_m_"+line).innerHTML="<b>"+bbuav+"</b>";
	//UAV Randabstand[m]
	document.getElementById("URA_m_"+line).innerHTML="<b>"+rauav+"</b>";
/**/
/*	
	//Objektivwinkel - document.getElementById("SBmU_m_"+line).innerHTML=DroneData[SelectedDrone]["IRCamAngle_H"];
	height = parseInt(height);
	objectangle = DroneData[SelectedDrone]["IRCamAngle_H"];
	
	//Sichtbreite mit Überlappung[m]
	SiBr = 2*Math.tan((objectangle/2)*Math.PI/180)*height;
	document.getElementById("SBmU_m_"+line).innerHTML=round(SiBr);
	
	//Sichtbreite pro Seite [m]
	//im Berechnungsprogram wurde für die Überlappung 15% der Sichtbreite verwendet
	//hier wird 15% des Objektivwinkels verwendet
	ul_agle = objectangle*0.15;
	ul = Math.tan((objectangle/2)*Math.PI/180)*height - Math.tan((objectangle/2-ul_agle)*Math.PI/180)*height
	document.getElementById("SBpS_m_"+line).innerHTML=round(ul,"");

	//Suchbreite ohne Überlappung[m]
	SuBr = 2*Math.tan((objectangle/2-ul_agle*Math.PI/180))*height;
	document.getElementById("SuBmU_m_"+line).innerHTML=round(SiBr - ul*2,"");

	////Abstand der Flubahn [m]
	//ROUND(Sichtbreite pro Seite [m]- Sichtbreite pro Seite [m]*2;0)
	document.getElementById("AdFb_m_"+line).innerHTML=round(SiBr - ul*2,"");
	
	//Empfohlene Flugbahnbreite [m]
	//var bahnfaktor_tief = objectangle/17;
	//var bahnfaktor_hoch = objectangle/14;
	//Empfohlene Flugbahnbreite [m]
	var bahnfaktor_tief = 2;
	var bahnfaktor_hoch = 2.5;
	document.getElementById("EFbB_q_m_"+line).innerHTML=round(SiBr - ul*bahnfaktor_hoch,"") + " - " + round(SiBr - ul*bahnfaktor_tief,"");

	document.getElementById("EFbB_m_"+line).innerHTML=round(SiBr - ul*bahnfaktor_hoch,"") + " - " + round(SiBr - ul*bahnfaktor_tief,"");
	
	//Empfohlener Randabstand [m]
	//=CONCATENATE(ROUNDDOWN(Sichtbreite pro Seite [m]/5;0)*5; " - "; 3*ROUNDDOWN(Sichtbreite pro Seite [m]/5;0)*5)
	var randfaktor_tief = 1.5;
	var randfaktor_hoch = 2;
	var Rand_Unten = round(randfaktor_tief*ul,"down");
	var Rand_Oben = round(randfaktor_hoch*ul,"up");
	document.getElementById("ERA_m_"+line).innerHTML=Rand_Unten + " - " + Rand_Oben;

	//UAV Bahnbreite [m]
	document.getElementById("UBB_m_"+line).innerHTML="<b>"+round(SiBr - ul*2.25,"")+"</b>";

	//UAV Randabstand[m]
	document.getElementById("URA_m_"+line).innerHTML="<b>"+Math.round((Rand_Unten+Rand_Oben)/2,"down")+"</b>";
/**/

	if (FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["fh"]["flightheight"].includes(parseInt(height)))
	//if (DroneData[SelectedDrone]["recommendations"]["fh"]["flightheight"].includes(parseInt(height))) {
	//if (DroneData[SelectedDrone]["rec_flightheight"].includes(parseInt(height))) {
	{
		ColorLine("fh", line, lgreen);
	} else {
		ColorLine("fh", line, "");
	}	

	if (FlightSettings[DroneData[SelectedDrone]["RecomType"]]["hint"]!="") {
		document.getElementById("hint").innerHTML= Hints[FlightSettings[DroneData[SelectedDrone]["RecomType"]]["hint"]];
	} else {
		document.getElementById("hint").innerHTML= "";
	}

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "fh: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["fh"]["flightheight"].toString();
	}
	
}


function CalcQuer (height, line ) {
	//alert("Berechne Bahnabstand, Höhe = " + height + ", Zeile: " + line);

	//Inputs
	fh = parseInt(height);							// Flughöhe
	oa = DroneData[SelectedDrone]["IRCamAngle_V"];  //Objektivwinkel
	ua = oa*0.15;  									// Überlappungswinkel
	bf_t = 2.0;										// Bahnfaktor tief
	bf_h = 2.5;										// Bahnfaktor hoch
	bf_m = (bf_t+bf_h)/2;							// Bahnfaktor mitte
	rf_t = 1.5;										// Randfaktor tief
	rf_h = 2.0;										// Randfaktor hoch

	sib = 2*Math.tan((oa/2)*Math.PI/180)*fh;		//Sichtbreite mit Überlappung [m
	sub = 2*Math.tan((oa/2-ua)*Math.PI/180)*fh;		//Suchbreite ohne Überlappung [m]
	ul = (Math.tan((oa/2)*Math.PI/180)-Math.tan((oa/2-ua)*Math.PI/180))*fh; //Überlappung [m]

	bb = round(sib-bf_h*ul,"") + " - " + round(sib-bf_t*ul,"");	// Empfohlene Bahnbreite
	ra = round(rf_t*ul,"down") + " - " + round(rf_h*ul,"up"); 	// Randabstand
	bbuav = round(sib-bf_m*ul,"");								// Bahnbreite UAV
	rauav = round((rf_t+rf_h)/2*ul,"");		 					// Randabstand UAV

	//Resultate ausgeben
	//Sichtbreite [m]
	document.getElementById("SBmU_q_m_"+line).innerHTML=round(sib);
	//Überlappung[m]
	document.getElementById("SBpS_q_m_"+line).innerHTML=round(ul,"");
	//Suchbreite ohne Überlappung[m]
	document.getElementById("SuBmU_q_m_"+line).innerHTML=round(sub,"");
	//Abstand der Flubahn [m]
	document.getElementById("EFbB_q_m_"+line).innerHTML=bb;
	//Empfohlener Randabstand [m]
	document.getElementById("ERA_q_m_"+line).innerHTML=ra;
	//UAV Bahnbreite [m]
	document.getElementById("UBB_q_m_"+line).innerHTML="<b>"+bbuav+"</b>";
	//UAV Randabstand[m]
	document.getElementById("URA_q_m_"+line).innerHTML="<b>"+rauav+"</b>";


/*
	//Objektivwinkel - document.getElementById("SBmU_q_m_"+line).innerHTML=DroneData[SelectedDrone]["IRCamAngle_V"];
	height = parseInt(height);
	objectangle = DroneData[SelectedDrone]["IRCamAngle_V"];

	//Sichtbreite mit Überlappung[m]
	SiBr = 2*Math.tan((objectangle/2)*Math.PI/180)*height;
	document.getElementById("SBmU_q_m_"+line).innerHTML=round(SiBr);
	
	//Sichtbreite pro Seite [m]
	//im Berechnungsprogram wurde für die Überlappung 15% der Sichtbreite verwendet
	//hier wird 15% des Objektivwinkels verwendet
	ul_agle = objectangle*0.15;
	ul = Math.tan((objectangle/2)*Math.PI/180)*height - Math.tan((objectangle/2-ul_agle)*Math.PI/180)*height
	document.getElementById("SBpS_q_m_"+line).innerHTML=round(ul,"");

	//Suchbreite ohne Überlappung[m]
	SuBr = 2*Math.tan((objectangle/2-ul_agle*Math.PI/180))*height;
	document.getElementById("SuBmU_q_m"+line).innerHTML=round(SiBr - ul*2,"");

	////Abstand der Flubahn [m]
	//ROUND(Sichtbreite pro Seite [m]- Sichtbreite pro Seite [m]*2;0)
	//document.getElementById("AdFb_q_m_"+line).innerHTML=round(SiBr - ul*2,"");

	//Empfohlene Flugbahnbreite [m]
	var bahnfaktor_tief = 2;
	var bahnfaktor_hoch = 2.5;
	document.getElementById("EFbB_q_m_"+line).innerHTML=round(SiBr - ul*bahnfaktor_hoch,"") + " - " + round(SiBr - ul*bahnfaktor_tief,"");
	
	//Empfohlener Randabstand [m]
	//=CONCATENATE(ROUNDDOWN(Sichtbreite pro Seite [m]/5;0)*5; " - "; 3*ROUNDDOWN(Sichtbreite pro Seite [m]/5;0)*5)
	var randfaktor_tief = 1.5;
	var randfaktor_hoch = 2;
	var Rand_Unten = round(randfaktor_tief*ul,"down");
	var Rand_Oben = round(randfaktor_hoch*ul,"up");
	document.getElementById("ERA_q_m_"+line).innerHTML=Rand_Unten + " - " + Rand_Oben;

	//UAV Bahnbreite [m]
	document.getElementById("UBB_q_m_"+line).innerHTML="<b>"+round(SiBr - ul*2.25,"")+"</b>";

	//UAV Randabstand[m]
	document.getElementById("URA_q_m_"+line).innerHTML="<b>"+Math.round((Rand_Unten+Rand_Oben)/2,"down")+"</b>";
/**/

	if (FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["fhq"]["flightheight"].includes(parseInt(height)))
	//if (DroneData[SelectedDrone]["recommendations"]["fhq"]["flightheight"].includes(parseInt(height))) {
	{
	   ColorLine("fhq", line, lgreen);
	} else {
	   ColorLine("fhq", line, "");
	}

	if (FlightSettings[DroneData[SelectedDrone]["RecomType"]]["hint"]!="") {
		document.getElementById("hint").innerHTML= Hints[FlightSettings[DroneData[SelectedDrone]["RecomType"]]["hint"]];
	} else {
		document.getElementById("hint").innerHTML= "";
	}

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "fhq: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["fhq"]["flightheight"].toString();
	}
}			

function CalcBahnabstandQuer (height, angle, feld, line ) {
	//alert("Berechne Waldrand Abstand, Höhe = " + height + " Kamerawinkel = " + angle + ", Zeile: " + line);

	//Inputs
	fh = parseInt(height);							// Flughöhe
	aa = parseInt(angle);							// Gimbal-/Kamerawinkel
	oa = DroneData[SelectedDrone]["IRCamAngle_V"];  //Objektivwinkel
	ua = oa*0.15;  									// Überlappungswinkel
	submax = 70;									// Bahnbreitengrenze
	submaxf = 0.2;									// Bahnbreitengrenzen Faktor

	fo = Math.tan((90-aa-oa/2+ua)*Math.PI/180)*fh;				//Feldoffset [m]
	sifo = Math.tan((90-aa-oa/2)*Math.PI/180)*fh;				//Sichfeldoffset [m]
	sib = Math.tan((90-aa+oa/2)*Math.PI/180)*fh-sifo;			//Sichtbreite (mit Überlappung) [m]
	sub = (Math.tan((90-aa+oa/2-ua)*Math.PI/180)-Math.tan((90-aa-oa/2+ua)*Math.PI/180))*fh;	//Suchbreite (ohne Überlappung
	//Suchbreite (ohne Überlappung
	ulv = (Math.tan((90-aa+oa/2)*Math.PI/180)-Math.tan((90-aa+oa/2-ua)*Math.PI/180))*fh;	//Überlappung vorne
	ulh = (Math.tan((90-aa-oa/2+ua)*Math.PI/180)-Math.tan((90-aa-oa/2)*Math.PI/180))*fh;	//Überlappung hinten
	fbo = Math.tan((90-aa)*Math.PI/180)*fh;						//Offset zum Mittelpunkt [m]
	mid = fh/Math.cos((90-aa)*Math.PI/180);						// Mittlere Distanz [m]
	//mid = Math.sqrt(fh*fh+fbo*fbo);							// Mittlere Distanz [m] (alternativ)
	mad = fh/Math.cos((90-aa+oa/2)*Math.PI/180);				// Maximale Distanz [m]
	//mad = Math.sqrt(fh*fh+(sifo+sib)*(sifo+sib));				// Maximale Distanz [m] (alternativ)
	if (sub>submax) {
		bbuav = ((sub-submax)*submaxf)+submax;						// UAV Empfohlener Bahnabstand [m] > submax
	} else {
		bbuav = sub;											// UAV Empfohlener Bahnabstand [m] <= submax
    }
	fbouav = fbo;	// UAV Flugpfadoffset [m]
	rauav = 1.5*ulh;	// UAV Randabstand [m]
			
	//Resultate ausgeben
	//Sichtbreite (mit Überlappung) [m]
	document.getElementById("SBmU_bq_"+line).innerHTML=round(sib,"");

	//Suchbreite (ohne Überlappung) [m]
	document.getElementById("SuBmU_bq_"+line).innerHTML=round(sub,"");
	
	//Überlappung [m]
	document.getElementById("UeAS_bq_"+line).innerHTML="V:" + round(ulv,"") + " H:" + round(ulh,"");

	// Feldoffset [m]
	document.getElementById("FO_bq_"+line).innerHTML=round(fo,"");

	// Offset zum Mittelpunkt [m]
	document.getElementById("OMP_bq_"+line).innerHTML=round(fbo,"");

	// Mittlere Distanz [m]
	document.getElementById("MiD_bq_"+line).innerHTML=round(mid,"");

	// Maximale Distanz [m]
	document.getElementById("MaD_bq_"+line).innerHTML=round(mad,"");

	// UAV Empfohlener Bahnabstand [m]
	document.getElementById("EFBA_bq_"+line).innerHTML="<b>"+round(bbuav,"down")+"</b>";

	// UAV Flugpfadoffset [m]
	document.getElementById("FPof_bq_"+line).innerHTML="<b>"+round(fbouav,"")+"</b>";
	
	// UAV Randabstand [m]
	document.getElementById("URA_bq_"+line).innerHTML="<b>"+round(rauav,"")+"</b>";

/*
	//Objektivwinkel - document.getElementById("SBmU_q_m_"+line).innerHTML=DroneData[SelectedDrone]["IRCamAngle_V"];

	// Flughöhe
	// "flightheight_aw-".$Line
	height = parseInt(height);

	// Kamerawinkel [°]";
	// "oangle_aw-".$Line
	angle = parseInt(angle);
	objectangle = DroneData[SelectedDrone]["IRCamAngle_V"];
	
	//Überlappung
	//im Berechnungsprogram wurde für die Überlappung 15% der Sichtbreite verwendet
	//hier wird 15% des Objektivwinkels verwendet
	ul_agle = objectangle*0.15;
	
	// Sichtbreite mit Überlappung [m]
	// SBmU_w_".$Line."
	// ROUND((ROUND(TAN((90-C64+(B64/2))*PI()/180)*D64;0)-ROUND(TAN(((C64)-(B64/2))*PI()/180)*D64;0))/5;0)*5
	// B64: DroneData[SelectedDrone]["IRCamAngle_V"]
	// C64: Kamerawinkel (angle)
	// D64: Flughöhe (height)
	//var round_bahn = 5;

	//Distanz Feldrand vorne
	D_FRv = Math.tan((90-angle+objectangle/2)*Math.PI/180)*height;
	//Distanz Feldrand vorne ohne überlappung
	D_FRoUev = Math.tan((90-angle-ul_agle+objectangle/2)*Math.PI/180)*height;
	D_Uev = D_FRv-D_FRoUev;
	
	if ((angle+objectangle/2)<90) {
		//D_FRh = Math.tan((90-angle-objectangle/2)*Math.PI/180)*height;
		//SuBr = D_Frv + D_FRh;
		SiBr = (Math.tan((90-angle+objectangle/2)*Math.PI/180)*height)+(Math.tan((90-angle-objectangle/2)*Math.PI/180)*height);
		SuBr = (Math.tan((90-angle+objectangle/2-ul_agle)*Math.PI/180)*height)+(Math.tan((90-angle-objectangle/2-ul_agle)*Math.PI/180)*height);
	} else {
		//Hinterkante nachziehend (>90grad)
		//D_FRh = Math.tan((angle+objectangle/2-90)*Math.PI/180)*height;
		//	SuBr = D_Frv + D_FRh;
		SiBr = (Math.tan((90-angle+objectangle/2)*Math.PI/180)*height)+(Math.tan((angle+objectangle/2-90)*Math.PI/180)*height);
		SuBr = (Math.tan((90-angle+objectangle/2-ul_agle)*Math.PI/180)*height)+(Math.tan((angle+objectangle/2-ul_agle-90)*Math.PI/180)*height);
	}
	
	//Überlappung Hinten
	DUe_h = (Math.tan((angle+objectangle/2-90)*Math.PI/180)*height) - (Math.tan((angle+objectangle/2-ul_agle-90)*Math.PI/180)*height);

	//Sichtbreite (mit Überlappung)
	document.getElementById("SBmU_bq_"+line).innerHTML=round(SiBr,"");

	//Suchbreite (ohne Überlappung)
	document.getElementById("SuBmU_bq_"+line).innerHTML=round(SuBr,"");
	
	//Überlappung
	document.getElementById("UeAS_bq_"+line).innerHTML="V:" + round(D_Uev,"") + " H:" + round(DUe_h,"");

	// Feldoffset [m]";
	// FO_w_".$Line."
	// ROUND(TAN(((90-C64)-(B64/2))*PI()/180)*D64;0)
	// B64: DroneData[SelectedDrone]["IRCamAngle_V"]
	// C64: Kamerawinkel (angle)
	// D64: Flughöhe (height)
	document.getElementById("FO_bq_"+line).innerHTML=round(Math.tan(((90-angle+ul_agle)-(objectangle/2))*Math.PI/180)*height,"");

	// Offset zum Mittelpunkt [m]";
	// OMP_w_".$Line."
	// ROUND(TAN(((90-C64))*PI()/180)*D64;0)
	// C64: Kamerawinkel (angle)
	// D64: Flughöhe (height)
	document.getElementById("OMP_bq_"+line).innerHTML=round(Math.tan((90-angle)*Math.PI/180)*height,"");

	// Mittlere Distanz [m]";
	// MD_w_".$Line."
	// ROUND(D64/COS(((90-C64))*PI()/180);0)
	// C64: Kamerawinkel (angle)
	// D64: Flughöhe (height)
	document.getElementById("MiD_bq_"+line).innerHTML=round(height/Math.cos(((90-angle)*Math.PI/180)),"");

	// Maximale Distanz [m]";
	document.getElementById("MaD_bq_"+line).innerHTML=round(height/Math.cos(((90-angle+objectangle/2-ul_agle)*Math.PI/180)),"");

	// UAV Empfohlener Bahnabstand [m]";
	$Bahnbreitengrenze = 70;
	if (SuBr>$Bahnbreitengrenze) {
		EFBA = ((SuBr-$Bahnbreitengrenze)*.2)+$Bahnbreitengrenze;
	} else {
		EFBA = SuBr;
    }
	document.getElementById("EFBA_bq_"+line).innerHTML="<b>"+round(EFBA,"down")+"</b>";

	// UAV Flugpfadoffset [m]";
	// UBB_w_".$Line."
	document.getElementById("FPof_bq_"+line).innerHTML="<b>"+round(Math.tan((90-angle)*Math.PI/180)*height,"")+"</b>";
	
	// UAV Randabstand [m]";
	// URA_w_".$Line."
	document.getElementById("URA_bq_"+line).innerHTML="<b>"+round(1.5*DUe_h,"")+"</b>";
/**/

	//disable selection
	if (feld=="fh") {
		//alert("Feld = " + feld);
		//document.getElementById("flightheight_bq-"+line).disabled = true; 
		document.getElementById("oangle_bq-"+line).disabled = true; 
	}
	if (feld=="kw") {
		//alert("Feld = " + feld);
		document.getElementById("flightheight_bq-"+line).disabled = true; 
		//document.getElementById("oangle_bq-"+line).disabled = true; 
	}
	if (feld=="") {
		document.getElementById("flightheight_bq-"+line).disabled = false; 
		document.getElementById("oangle_bq-"+line).disabled = false; 
	}

	if ((FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["bq"]["flightheight"].includes(parseInt(height))) &&
		(FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["bq"]["angle"].includes(parseInt(angle))))
	//if (FlightSettings[$DroneData[SelectedDrone]["RecomType"]]["bq"]["flightheight"].includes(parseInt(height))) {
	//if ((DroneData[SelectedDrone]["recommendations"]["bq"]["flightheight"].includes(parseInt(height))) &&
	//	(DroneData[SelectedDrone]["recommendations"]["bq"]["angle"].includes(parseInt(angle)))) {
	{
	   ColorLine("bq", line, lgreen);
	} else {
	   ColorLine("bq", line, "");
	}
	document.getElementById("hint").innerHTML= Hints["VFW_Quer"];

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "bq: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["bq"]["flightheight"].toString() + " rec angle " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["bq"]["angle"].toString();
	}
}			



function CalcWaldrandAbstand (height, angle, feld, line ) {
	//alert("Berechne Waldrand Abstand, Höhe = " + height + " Kamerawinkel = " + angle + ", Zeile: " + line);
	//Objektivwinkel - document.getElementById("SBmU_q_m_"+line).innerHTML=DroneData[SelectedDrone]["IRCamAngle_V"];


	//Inputs
	fh = parseInt(height);							// Flughöhe
	aa = parseInt(angle);							// Gimbal-/Kamerawinkel
	oa = DroneData[SelectedDrone]["IRCamAngle_V"];  //Objektivwinkel
	ua = oa*0.15;  									// Überlappungswinkel
	//ua = oa*0.25;  									// Überlappungswinkel
	rf_t = 1.5;										// Randfaktor tief
	rf_h = 2.0;										// Randfaktor hoch

	fo = Math.tan((90-aa-oa/2+ua)*Math.PI/180)*fh;				//Feldoffset [m]
	sifo = Math.tan((90-aa-oa/2)*Math.PI/180)*fh;				//Sichfeldoffset [m]
	sib = Math.tan((90-aa+oa/2)*Math.PI/180)*fh-sifo;			//Sichtbreite (mit Überlappung) [m]
	sub = (Math.tan((90-aa+oa/2-ua)*Math.PI/180)-Math.tan((90-aa-oa/2+ua)*Math.PI/180))*fh;	//Suchbreite (ohne Überlappung
	//Suchbreite (ohne Überlappung
	ulv = (Math.tan((90-aa+oa/2)*Math.PI/180)-Math.tan((90-aa+oa/2-ua)*Math.PI/180))*fh;	//Überlappung vorne
	ulh = (Math.tan((90-aa-oa/2+ua)*Math.PI/180)-Math.tan((90-aa-oa/2)*Math.PI/180))*fh;	//Überlappung hinten
	mid = fh/Math.cos((90-aa)*Math.PI/180);						// Mittlere Distanz [m]
	//mid = Math.sqrt(fh*fh+fbo*fbo);							// Mittlere Distanz [m] (alternativ)
	mad = fh/Math.cos((90-aa+oa/2)*Math.PI/180);				// Maximale Distanz [m]
	//mad = Math.sqrt(fh*fh+(sifo+sib)*(sifo+sib));				// Maximale Distanz [m] (alternativ)
	fbo = Math.tan((90-aa)*Math.PI/180)*fh;						//Offset zum Mittelpunkt [m]
	sd = Math.tan((90-aa+oa/2-ua)*Math.PI/180)*fh;						//Suchdistanz

	bbuav = round(sd,"");									// Bahnbreite UAV
	ra	  = round(ulh*rf_t,"") + " - " + round(ulh*rf_h,"");	// Randabstand
	rauav = ulh*(rf_t+rf_h)/2;		 							// Randabstand UAV
				
	//Resultate ausgeben
	//Sichtbreite (mit Überlappung) [m]
	document.getElementById("SBmU_w_"+line).innerHTML=round(sib,"");

	//Suchbreite (ohne Überlappung) [m]
	document.getElementById("SuBmU_w_"+line).innerHTML=round(sub,"");
	//Überlappung [m]
	document.getElementById("UeAS_w_"+line).innerHTML="V:" + round(ulv,"") + " H:" + round(ulh,"");

	// Feldoffset [m]
	document.getElementById("FO_w_"+line).innerHTML=round(fo,"");

	// Offset zum Mittelpunkt [m]
	document.getElementById("OMP_w_"+line).innerHTML=round(fbo,"");

	// Mittlere Distanz [m]
	document.getElementById("MD_w_"+line).innerHTML=round(mid,"");

	// Empfohlene Suchdistanz [m]
	document.getElementById("EFBB_w_"+line).innerHTML=round(sd,"");

	// UAV Empfohlener Randabstand [m]
	document.getElementById("ERA_w_"+line).innerHTML=ra;

	// UAV Bahnbreite [m]
	document.getElementById("UBB_w_"+line).innerHTML="<b>"+round(bbuav,"down")+"</b>";

	// UAV Randabstand [m]
	document.getElementById("URA_w_"+line).innerHTML="<b>"+round(rauav,"")+"</b>";
/**/

/*
	// Flughöhe
	// "flightheight_aw-".$Line
	height = parseInt(height);

	// Kamerawinkel [°]";
	// "oangle_aw-".$Line
	angle = parseInt(angle);
	objectangle = DroneData[SelectedDrone]["IRCamAngle_V"];
	//ul_agle  = 10; // Grad überlappungswinkel
	ul_agle = objectangle/4;

	// Sichtbreite mit Überlappung [m]
	// SBmU_w_".$Line."
	// ROUND((ROUND(TAN((90-C64+(B64/2))*PI()/180)*D64;0)-ROUND(TAN(((C64)-(B64/2))*PI()/180)*D64;0))/5;0)*5
	// B64: DroneData[SelectedDrone]["IRCamAngle_V"]
	// C64: Kamerawinkel (angle)
	// D64: Flughöhe (height)
	//var round_bahn = 5;

	//Distanz Feldrand vorne
	D_FRv = Math.tan((90-angle+objectangle/2)*Math.PI/180)*height;
	//Distanz Feldrand vorne ohne überlappung
	D_FRoUev = Math.tan((90-angle-ul_agle+objectangle/2)*Math.PI/180)*height;
	D_Uev = D_FRv-D_FRoUev;
	
	if ((angle+objectangle/2)<90) {
		//D_FRh = Math.tan((90-angle-objectangle/2)*Math.PI/180)*height;
		//SuBr = D_Frv + D_FRh;
		SiBr = (Math.tan((90-angle+objectangle/2)*Math.PI/180)*height)+(Math.tan((90-angle-objectangle/2)*Math.PI/180)*height);
		SuBr = (Math.tan((90-angle+objectangle/2-ul_agle)*Math.PI/180)*height)+(Math.tan((90-angle-objectangle/2-ul_agle)*Math.PI/180)*height);
	} else {
		//Hinterkante nachziehend (>90grad)
		//D_FRh = Math.tan((angle+objectangle/2-90)*Math.PI/180)*height;
		//	SuBr = D_Frv + D_FRh;
		SiBr = (Math.tan((90-angle+objectangle/2)*Math.PI/180)*height)+(Math.tan((angle+objectangle/2-90)*Math.PI/180)*height);
		SuBr = (Math.tan((90-angle+objectangle/2-ul_agle)*Math.PI/180)*height)+(Math.tan((angle+objectangle/2-ul_agle-90)*Math.PI/180)*height);
	}
	
	//Überlappung Hinten
	DUe_h = (Math.tan((angle+objectangle/2-90)*Math.PI/180)*height) - (Math.tan((angle+objectangle/2-ul_agle-90)*Math.PI/180)*height);

	//Sichtbreite (mit Überlappung)
	document.getElementById("SBmU_w_"+line).innerHTML=round(SiBr,"");
	
	//Überlappung
	document.getElementById("UeAS_w_"+line).innerHTML="V:" + round(D_Uev,"") + " H:" + round(DUe_h,"");

	//Suchbreite (ohne Überlappung)
	document.getElementById("SuBmU_w_"+line).innerHTML=round(SuBr,"");


	// Feldoffset [m]";
	// FO_w_".$Line."
	// ROUND(TAN(((90-C64)-(B64/2))*PI()/180)*D64;0)
	// B64: DroneData[SelectedDrone]["IRCamAngle_V"]
	// C64: Kamerawinkel (angle)
	// D64: Flughöhe (height)
	FO = Math.tan((90-angle-(objectangle/2)+ul_agle)*Math.PI/180)*height;
	document.getElementById("FO_w_"+line).innerHTML=round(FO+SuBr,"");

	// Offset zum Mittelpunkt [m]";
	// OMP_w_".$Line."
	// ROUND(TAN(((90-C64))*PI()/180)*D64;0)
	// C64: Kamerawinkel (angle)
	// D64: Flughöhe (height)
	document.getElementById("OMP_w_"+line).innerHTML=round(Math.tan((90-angle)*Math.PI/180)*height,"");

	// Mittlere Distanz [m]";
	// MD_w_".$Line."
	// ROUND(D64/COS(((90-C64))*PI()/180);0)
	// C64: Kamerawinkel (angle)
	// D64: Flughöhe (height)
	document.getElementById("MD_w_"+line).innerHTML=round(height/Math.cos(((90-angle))*Math.PI/180),"");

	// Empfohlene Suchdistanz [m]";
	// EFBB_w_".$Line."
	//ROUND((E64-F64)/5;0)*5
	//document.getElementById("EFBB_w_"+line).innerHTML=round((document.getElementById("SBmU_w_"+line).innerHTML-document.getElementById("UeAS_w_"+line).innerHTML),"");
	document.getElementById("EFBB_w_"+line).innerHTML=round(Math.tan((90-angle+(objectangle/2)-ul_agle)*Math.PI/180)*height,"");
	document.getElementById("EFBB_w_"+line).innerHTML=round(+FO,"");

	// Empfohlener Randabstand [m]";
	// ERA_w_".$Line."
	//CONCATENATE(ROUNDDOWN(F64/2;0)*2; " - ";ROUND(TAN((C64-(B64/2))*PI()/180)*D64;0))
	var randfaktor_tief = 1.5;
	var randfaktor_hoch = 2;
	document.getElementById("ERA_w_"+line).innerHTML=round(DUe_h*randfaktor_tief,"") + " - " + round(DUe_h*randfaktor_hoch,"");

	// UAV Bahnbreite [m]";
	// UBB_w_".$Line."
	document.getElementById("UBB_w_"+line).innerHTML="<b>"+round(+FO,"");+"</b>";
	
	// UAV Randabstand [m]";
	// URA_w_".$Line."
	var randfaktor = 1.7;
	document.getElementById("URA_w_"+line).innerHTML="<b>"+round(DUe_h*randfaktor,"")+"</b>";

/**/
	//disable selection
	if (feld=="fh") {
		//alert("Feld = " + feld);
		//document.getElementById("flightheight_aw-"+line).disabled = true; 
		document.getElementById("oangle_aw-"+line).disabled = true; 
	}
	if (feld=="kw") {
		//alert("Feld = " + feld);
		document.getElementById("flightheight_aw-"+line).disabled = true; 
		//document.getElementById("oangle_aw-"+line).disabled = true; 
	}
	if (feld=="") {
		document.getElementById("flightheight_aw-"+line).disabled = false; 
		document.getElementById("oangle_aw-"+line).disabled = false; 
	}


	if ((FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["aw"]["flightheight"].includes(parseInt(height))) &&
		(FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["aw"]["angle"].includes(parseInt(angle))))
	//if ((DroneData[SelectedDrone]["recommendations"]["aw"]["flightheight"].includes(parseInt(height))) &&
	//	(DroneData[SelectedDrone]["recommendations"]["aw"]["angle"].includes(parseInt(angle)))) {
	{
	   ColorLine("aw", line, lgreen);
	} else {
	   ColorLine("aw", line, "");
	}	
	document.getElementById("hint").innerHTML= Hints["VFW_Wald"];

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "aw: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["aw"]["flightheight"].toString() + " rec angle " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["aw"]["angle"].toString();
	}
}

function CalcWaldrandsuche (height, angle, sdist, feld, line) {
	//alert("Berechne Waldrandsuche, Höhe = " + height + " Objektivwinkel = " + angle + " Suchdistanz = " + sdist +", Zeile: " + line);
	//Inputs

	fh = parseInt(height);							// Flughöhe
	aa = parseInt(angle);							// Gimbal/Kamerawinkel
	sd = parseInt(sdist);							// Suchdistanz (aussen)
	oa = DroneData[SelectedDrone]["IRCamAngle_V"];  // Objektivwinkel
	ua = oa*0.2;  									// Überlappungswinkel
	ua = oa*0.15;  									// Überlappungswinkel
	a_l = 0.9;										// Abstandfaktor tief
	a_h = 1.1;										// Abstandfaktor hoch


	
	fo = Math.tan((90-aa-oa/2+ua)*Math.PI/180)*fh; 	//FeldOffset [m]
	ul = (Math.tan((90-aa-oa/2+ua)*Math.PI/180)-Math.tan((90-aa-oa/2)*Math.PI/180))*fh; //Überlappung [m]
	
	sfo = Math.tan((90-aa-oa/2)*Math.PI/180)*fh; 	//Offset Sichtfeld
	aw  = fo + sd;									//Abstand Waldrand
	
	mad = Math.sqrt(fh*fh+aw*aw);
	suh = Math.tan((aa-oa/2+ua)*Math.PI/180)*aw;			//Suchhöhe [m]
	sih = Math.tan((aa-oa/2)*Math.PI/180)*aw;				//Sichhöhe [m]
	suh = Math.tan((aa-oa/2+ua)*Math.PI/180)*aw;			//Suchhöhe [m]

	rauav = round(aw*a_l,"") + " - " + round(aw*a_h,"");	// Randabstand UAV
	
	//Resultate ausgeben
	// Abstand Waldrand [m]
	document.getElementById("AFB_wr_"+line).innerHTML=round(aw,"");
	// Überlappung [m]
	document.getElementById("UeAS_wr_"+line).innerHTML=round(ul,"");
	// Suchhöhe [m]
	document.getElementById("SH_wr_"+line).innerHTML=round(suh,"");
	// Feldoffset [m]
	document.getElementById("FO_wr_"+line).innerHTML=round(fo,"");
	// Maximaler Abstand [m]
	document.getElementById("MA_wr_"+line).innerHTML=round(mad,"");
	// Empfohlener Randabstand UAV [m]
	document.getElementById("UAFW_wr_"+line).innerHTML="<b>"+rauav+"</b>";	
/*
	//Objektivwinkel - document.getElementById("SBmU_q_m_"+line).innerHTML=DroneData[SelectedDrone]["IRCamAngle_V"];

	// Flughöhe";
	// "flightheight_wr-".$Line
	height = parseInt(height);
	
	// Objektivwinkel [°]";
	// "oangle_wr-".$Line
	angle = parseInt(angle);
	
	// Äussere Suchdistanz [m]";
	// sdist_ws".$Line."
	sdist = parseInt(sdist);

	//Objektivwinkel
	objectangle = DroneData[SelectedDrone]["IRCamAngle_V"];

	//Überlappung
	//im Berechnungsprogram wurde für die Überlappung 15% der Sichtbreite verwendet
	//hier wird 20% des Objektivwinkels verwendet
	ul_agle = objectangle*0.2;
	
	//Offset Sichtfeld
	SO = round(Math.tan((90-angle-objectangle/2)*Math.PI/180)*height,"");

	//FeldOffset [m]
	FO = round(Math.tan((90-angle-objectangle/2+ul_agle)*Math.PI/180)*height,"");
	
	//Vordere Überlappung [m]
	D_Uev=FO-SO;

	//Abstand Waldrand
	dist_flighpath = FO + sdist;

	//Winkel Waldecke [°]
	W_WD = Math.atan(dist_flighpath/height)*180/Math.PI;
	MaxDist = Math.sqrt(dist_flighpath*dist_flighpath+height*height);
	
	//Oberer Sichwinkel
	W_OSi = angle -  objectangle/2;
	
	//Oberer Suchwinkel
	W_OSu = (angle -  objectangle/2) + ul_agle;

	//Sichthöhe [m]
	viewheight = Math.tan((W_OSi)*Math.PI/180)*dist_flighpath;

	//Suchthöhe [m]
	searchheight = Math.tan((W_OSu)*Math.PI/180)*dist_flighpath;


	document.getElementById("AFB_wr_"+line).innerHTML=round(dist_flighpath,"");
	document.getElementById("UeAS_wr_"+line).innerHTML=round(D_Uev,"");
	document.getElementById("SH_wr_"+line).innerHTML=round(searchheight,"");
	document.getElementById("FO_wr_"+line).innerHTML=round(FO,"");
	document.getElementById("MA_wr_"+line).innerHTML=round(MaxDist,"");
	
	// UAV Abstand Flugpfad-Waldrand [m]";
	var lowfact = 0.9;
	var highfact = 1.1;
	document.getElementById("UAFW_wr_"+line).innerHTML="<b>"+round(dist_flighpath*lowfact,"") + " - " + round(dist_flighpath*highfact,"")+"</b>";
 
 /**/   
	//disable selection
	if (feld=="fh") {
		//document.getElementById("flightheight_wr-"+line).disabled = true; 
		document.getElementById("oangle_wr-"+line).disabled = true; 
		document.getElementById("sdist_wr-"+line).disabled = true; 
	}
	if (feld=="ow") {
		document.getElementById("flightheight_wr-"+line).disabled = true; 
		//document.getElementById("oangle_wr-"+line).disabled = true; 
		document.getElementById("sdist_wr-"+line).disabled = true; 
	}
	if (feld=="sd") {
		document.getElementById("flightheight_wr-"+line).disabled = true; 
		document.getElementById("oangle_wr-"+line).disabled = true; 
		//document.getElementById("sdist_wr-"+line).disabled = true; 
	}
	if (feld=="") {
		document.getElementById("flightheight_wr-"+line).disabled = false; 
		document.getElementById("oangle_wr-"+line).disabled = false; 
		document.getElementById("sdist_wr-"+line).disabled = false; 
	}


	if ((FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["wr"]["flightheight"].includes(parseInt(height))) &&
		(FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["wr"]["angle"].includes(parseInt(angle))))
	//if ((DroneData[SelectedDrone]["recommendations"]["wr"]["flightheight"].includes(parseInt(height))) &&
	//	(DroneData[SelectedDrone]["recommendations"]["wr"]["angle"].includes(parseInt(angle)))) {
	{
	   ColorLine("wr", line, lgreen);
	} else {
	   ColorLine("wr", line, "");
	}	
	
	document.getElementById("hint").innerHTML= Hints["VFW_Waldrand"];

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "wr: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["wr"]["flightheight"].toString() + " rec angle " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["wr"]["angle"].toString();
	} else {
		document.getElementById("debug").style.display= 'none';
		document.getElementById("debug1").style.display= 'none';
	}
		
}

function ColorLine(tab, line, color) {
	//var tables = {fh:8, fhq:8, bq:10, aw: 10, wr:6};
	//alert("Zeile einfärben: " + tab + " / " + line +  " / " + tables[tab] + " / " + color);
	 
	for (let step = 1; step <= PrefParameters[tab]["#Col"]; step++) {	
	//for (let step = 1; step <= tables[tab]; step++) {	
		document.getElementById(tab+step+"-"+line).style.backgroundColor= color;		
	}	
}


function UpdCalc(val, line) {
  SelectedCalc= val;
  //alert("Calc geändert auf: " + val + line);
  document["calcpic"].style.display = 'none';
  
  	DefType = DroneData[SelectedDrone]["DefaultType"];
  
	if ((val != "Bahnabstand") && (val != "Querflug") && (val != "Querflug Bahnabstand") && (val != "Waldrand Abstand") && (val != "Waldrandsuche")) {
	//if ((val == "")) {
	    document.getElementById("calc").innerHTML = "<i>Bitte Berechnung auswählen: ";
		document.getElementById("info").innerHTML="";
		document.getElementById("hint").innerHTML="";
		document.getElementById("video").innerHTML="";
		document.getElementById("step_"+"fh").style.display= 'none';
		document.getElementById("step_"+"fhq").style.display= 'none';
		document.getElementById("step_"+"bq").style.display= 'none';
		document.getElementById("step_"+"aw").style.display= 'none';
		document.getElementById("step_"+"wr").style.display= 'none';
	} else {
	  //document.getElementById("calc").innerHTML = "<b>"+CalcData[val]["Name"]+"</b>";
	  document.getElementById("calc").innerHTML = "";
	}
	

  
	if ((val != "Bahnabstand") && (VAngle==0)) {
		document.getElementById("info").style.display= 'block';
		document.getElementById("info").innerHTML = "Diese Funktion kann leider nicht berechnet werden!";
		document["calcpic"].style.display = 'none';
	} else {
		document["calcpic"].src = calciconloc+CalcData[val]["img"];
		document["calcpic"].style.display = 'block';
		document.getElementById("info").style.display= 'none';
	}

	document.getElementById("debug1").innerHTML= "DefType: " + DefType;

	CalcOption = "Bahnabstand";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption)){
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
			//PrefParameters[CalcData[CalcOption]["key"]]["default_flightheights"].forEach((hl) => {
				selectElement('flightheight-'+line, hl);
				CalcBahnabstand(hl, line);
				line++;
			});
			$videosource ="<br><b>Lernvideo</b><br><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}


	CalcOption = "Querflug";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption) && (VAngle>0)) {
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
			//PrefParameters[CalcData[CalcOption]["key"]]["default_flightheights"].forEach((hl) => {
				selectElement('flightheight_fhq-'+line, hl);
				CalcQuer(hl, line);
				line++;
			});
			$videosource ="<br><b>Lernvideo</b><br><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}

	CalcOption = "Querflug Bahnabstand";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption) && (VAngle>0)) {
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
				DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
					selectElement('flightheight_bq-'+line, hl);
					selectElement('oangle_bq-'+line, ca);
					CalcBahnabstandQuer(hl, ca, "", line);
					line++;
				});
			});
			$videosource ="<br><b>Lernvideo</b><br><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}
		
	CalcOption = "Waldrand Abstand";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption) && (VAngle>0)) {
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
				DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
					selectElement('flightheight_aw-'+line, hl);
					selectElement('oangle_aw-'+line, ca);
					CalcWaldrandAbstand(hl, ca, "", line);
					line++;
				});
			});

			ColorLine("aw", 4);
			$videosource ="<br><b>Lernvideo</b><br><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}

	CalcOption = "Waldrandsuche";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption) && (VAngle>0)) {
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
				DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
					DefaultSettings[DefType][CalcData[CalcOption]["key"]]["searchdist"].forEach((sd) => {
						selectElement('flightheight_wr-'+line, hl);
						selectElement('oangle_wr-'+line, ca);
						selectElement('sdist_wr-'+line, sd);
						CalcWaldrandsuche(hl, ca, sd, "", line);
						line++;
					});
				});
			});
			$videosource ="<br><b>Lernvideo</b><br><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}
	document.getElementById("video").innerHTML=$videosource;
	if (setting!= "TEST") {
		document.getElementById("debug").style.display= 'none';
		document.getElementById("debug1").style.display= 'none';
	}	
}
// process input settings
if (SelectedDrone!="") {
	//alert("Drohne geändert auf: " + SelectedDrone);
	selectElement("Drohne", SelectedDrone);
	UpdDrone(SelectedDrone,1);
} else {
  document.getElementById("dronelist").innerHTML= GetDroneList();
 //document.getElementById("dronelist").innerHTML= "BLAs";
}

</script>
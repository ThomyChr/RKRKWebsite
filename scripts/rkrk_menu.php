<?php
	include_once(__DIR__ . '/rkrk_gen.php');
	
	// New MAP.GEO.ADMIN from 25. June 2024
	// ====================================
	
	/*
	Änderungen:
	1. https://test.map.geo.admin.ch/#/map
	statt https://map.geo.admin.ch/
	2. layers-Auflistung ist neu ; getrennt und nicht mehr ,
	3. KML-Notation ist neu
	KML|https...
	4. Zentrierung: &center=2660000,1190000&z=2
	statt &E=2677894.19&N=1230647.47&zoom=8
	*/
	
	//$MapGeoAdminURL = "https://test.map.geo.admin.ch/#/map?"; // set in rkrk_gen.php
	//$MapURL = "https://test.map.geo.admin.ch/embed.html?";
	$ZoomParamter = "&z=5";
	$Centering = "&center=2684128.97,1233031.15";

		
	// New 2024 mit Elektrischen Anlagen
	//https://map.geo.admin.ch/?lang=de&topic=ech&bgLayer=ch.swisstopo.swissimage&layers=ch.swisstopo.swisssurface3d-reliefschattierung-multidirektional,ch.kantone.cadastralwebmap-farbe,ch.swisstopo.swisstlm3d-karte-farbe,ch.bazl.einschraenkungen-drohnen,ch.bazl.luftfahrthindernis,ch.swisstopo-vd.geometa-gemeinde,ch.swisstopo.swissboundaries3d-gemeinde-flaeche.fill,ch.bfe.elektrische-anlagen_ueber_36,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3Dleer%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26GDE%3Doff%26JGG%3Don%26Poi%3Don%26HSP%3Doff%26Label%3Don%26GeLa%3Doff%26Sun%3Doff%26SORA%3Doff%26WF%3Doff%26KA%3DS%26Zoom%3D0.8%26FFilter%3D%26SFilter%3D%26MDB%3DM3T%26GA%3Dgast,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DNO%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26GDE%3Don%26JGG%3Doff%26Poi%3Don%26Label%3Doff%26EX%3DNO%26KA%3DS%26MAP%3D%26Zoom%3D0.8,KML%7C%7Chttps:%2F%2Fwww.marbacher.com%2Fdave%2Frkr%2Frkr_to_gpx.php,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DXX%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DAG%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D136%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D135%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DAZ%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DZG%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.8,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D28%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D27%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D26%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D25%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D21%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me:5682%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DALL%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.5&E=2684128.97&N=1233031.15&zoom=5&layers_opacity=0.8,0.5,1,1,0.25,0.6,0.75,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1&layers_visibility=true,true,true,false,false,false,true,false,true,false,true,false,false,false,false,false,true,false,false,false,false,false,false&layers_timestamp=,,,,,,2024,,,,,,,,,,,,,,,,
	

	/*
	https://map.geo.admin.ch/?lang=de&topic=ech&bgLayer=ch.swisstopo.swissimage&
	layers=ch.swisstopo.swisssurface3d-reliefschattierung-multidirektional,ch.kantone.cadastralwebmap-farbe,ch.swisstopo.swisstlm3d-karte-farbe,
	ch.bazl.einschraenkungen-drohnen,ch.bazl.luftfahrthindernis,ch.swisstopo-vd.geometa-gemeinde,ch.swisstopo.swissboundaries3d-gemeinde-flaeche.fill,
	ch.bfe.elektrische-anlagen_ueber_36,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3Dleer%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26GDE%3Doff%26JGG%3Don%26Poi%3Don%26HSP%3Doff%26Label%3Don%26GeLa%3Doff%26Sun%3Doff%26SORA%3Doff%26WF%3Doff%26KA%3DS%26Zoom%3D0.8%26FFilter%3D%26SFilter%3D%26MDB%3DM3T%26GA%3Dgast,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DNO%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26GDE%3Don%26JGG%3Doff%26Poi%3Don%26Label%3Doff%26EX%3DNO%26KA%3DS%26MAP%3D%26Zoom%3D0.8,
	KML%7C%7Chttps:%2F%2Fwww.marbacher.com%2Fdave%2Frkr%2Frkr_to_gpx.php,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DXX%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DAG%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D136%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D135%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DAZ%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DZG%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.8,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D28%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D27%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D26%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D25%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D21%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,
	KML%7C%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DALL%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.5
	&E=2684128.97&N=1233031.15&zoom=5
	&layers_opacity=0.8,0.5,1,1,0.25,0.6,0.75,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1
	&layers_visibility=true,true,true,false,false,false,true,false,true,false,true,false,false,false,false,false,true,false,false,false,false,false,false
	&layers_timestamp=,,,,,,2024,,,,,,,,,,,,,,,,	*/

	//Zuger Seite
		//$MergedMapLink ="https://map.geo.admin.ch/?lang=de&topic=ech&bgLayer=ch.swisstopo.swissimage&layers=ch.swisstopo.swisssurface3d-reliefschattierung-multidirektional,ch.kantone.cadastralwebmap-farbe,ch.swisstopo.swisstlm3d-karte-farbe,ch.bazl.einschraenkungen-drohnen,ch.are.bauzonen,ch.swisstopo-vd.geometa-gemeinde,ch.swisstopo.swissboundaries3d-gemeinde-flaeche.fill," . chr(10);



/*
https://map.geo.admin.ch/#/map?
lang=de&center=2680999.23,1234469.27&z=6.091&topic=ech
&layers=ch.swisstopo.swissboundaries3d-gemeinde-flaeche.fill@year=2026,f;
ch.swisstopo-vd.geometa-grundbuch,f;
ch.bafu.bundesinventare-jagdbanngebiete,f;
ch.pronatura.naturschutzgebiete,f;
ch.kantone.cadastralwebmap-farbe,f;
ch.swisstopo.swisstlm3d-karte-farbe,f;
ch.swisstopo.swisstlm3d-uebrigerverkehr,f;
ch.bfe.elektrische-anlagen_ueber_36,f;
ch.bazl.luftfahrthindernis,f;
ch.bazl.einschraenkungen-drohnen,f,0.48;

KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?Reg=21+24+25+26+27+28+32%26Strt=on%26Cntr=off%26Area=on%26Pfad=on%26WW=off%26FZ=on%26GDE=off%26JGG=off%26KT=off%26ORT=off%26Poi=off%26HSP=off%26Label=off%26GeLa=on%26Sun=off%26EG=off%26SORA=off%26WF=off%26KA=S%26Zoom=1.0%26FFilter=%26SFilter=%26MDB=3T%26GA=gast%26WebAccess=test&bgLayer=ch.swisstopo.swissimage
*/

	$MergedMapLink = $MapGeoAdminURL; 
	$MergedMapLink .="lang=de&topic=ech&bgLayer=ch.swisstopo.swissimage";
	$MergedMapLink .="&layers=ch.swisstopo.swisssurface3d-reliefschattierung-multidirektional;";
	$MergedMapLink .="ch.kantone.cadastralwebmap-farbe;";
	$MergedMapLink .="ch.swisstopo.swisstlm3d-karte-farbe;";
	$MergedMapLink .="ch.bazl.uas-aktivitaetszonen,f;";
	$MergedMapLink .="ch.bazl.wohngebiete-aulav,f;";
	$MergedMapLink .="ch.swisstopo.swissalti3d-reliefschattierung,f;";
	$MergedMapLink .="ch.bazl.einschraenkungen-drohnen,f;";
	$MergedMapLink .="ch.bazl.luftfahrthindernis,f;";
	$MergedMapLink .="ch.swisstopo-vd.geometa-gemeinde,f;";
	$MergedMapLink .="ch.swisstopo.swissboundaries3d-gemeinde-flaeche.fill,f;";
	$MergedMapLink .="ch.bfe.elektrische-anlagen_ueber_36,f;";

	// POI Flugverbotszonen
	$MergedMapLink .="KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?Reg=leer%26Strt=off%26Cntr=off%26Area=off%26Pfad=off%26WW=off%26FZ=on%26GDE=off%26JGG=off%26KT=off%26ORT=off%26Poi=off%26HSP=off%26Label=off%26GeLa=off%26Sun=off%26EG=off%26SORA=off%26WF=off%26KA=S%26Zoom=1%26FFilter=%26SFilter=%26MDB=3T%26GA=gast%26WebAccess=test%26Title%3DJagdgebietsgrenzen,f;";
	// POI Jagdgebiete
	$MergedMapLink .="KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?Reg=leer%26Strt=off%26Cntr=off%26Area=off%26Pfad=off%26WW=off%26FZ=off%26GDE=off%26JGG=on%26KT=off%26ORT=off%26Poi=off%26HSP=off%26Label=off%26GeLa=off%26Sun=off%26EG=off%26SORA=off%26WF=off%26KA=S%26Zoom=1%26FFilter=%26SFilter=%26MDB=3T%26GA=gast%26WebAccess=test%26Title%3DJagdgebietsgrenzen;";
	// POI Gemeindegrenzen
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3Dleer%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26GDE%3Don%26JGG%3Doff%26Poi%3Doff%26Label%3Doff%26EX%3DNO%26KA%3DS%26MAP%3D%26Zoom%3D0.8%26Title%3DGemeindegrenzen,f;";
	//von 27.03.2025
	//Ansicht mit Wegpunkten
	//$MergedMapLink .="KML%7Chttps:%2F%2Fwww.rkrzug.ch%2Frkr_to_gpx.php?;";
	//Ansicht nur Feldumrandungen und Startpunkt 07.10.2025
	$MergedMapLink .="KML%7Chttps://www.rkrzug.ch/rkr_to_gpx.php?sview=1;";
	
	// XX Andre Regionen
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DXX%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// AG Aargau
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DAG%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// 136 Abtwil
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D136%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// 148 Sins
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D148%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// 143 Dietwil
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D143%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// 145 Oberrüti
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D145%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// AZ Amt
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DAZ%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// LME Meierskappel
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DLME%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// ZG Zug
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DZG%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6;";

	// 32 Stallikon II
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D32%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// 28 Mettmenstetten
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D28%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// 27 Maschwanden
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D27%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// 26 Knonau
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D26%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// 25 Kappel
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D25%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// 21 Aeugst
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3D21%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.6,f;";
	// ALLE
	$MergedMapLink .="KML%7Chttps:%2F%2Ftchr-frog.synology.me%2Frkropen%2Ftest%2Frkrk_dokml.php%3FReg%3DALL%26Strt%3Don%26Cntr%3Doff%26Area%3Don%26Pfad%3Don%26EX%3DNO%26Poi%3Doff%26KA%3DS%26MAP%3D1%26Zoom%3D0.5,f";		
	$MergedMapLink .=  $Centering;
	$MergedMapLink .=  $ZoomParamter;
	//echo  $MergedMapLink . "<br><br><br><br>";

	$menuid=0;
	$MainMenu = Array(
			"Home" => Array (
						"title" => "Home",
						"site" => "rkrk.php",
						"parameter" => "",
						"target" => "_info",
						"image" => "/home.png",
						"image_small" => "/home_small.png",
						"desc_short" => "Zur Startseite",
						"desc_long" => "<b>Home</b></br>Zur Startseite.",
						"release" => "test, partner, extern, intern, pub, gast",
						"main_menu" => "Home",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
/*
			"FeldListeIntern" => Array (
						"title" => "Felderliste Intern",
						"site" => "rkrk_felder.php",
						"parameter" => "Reg=ALL&Typ=intern",
						"target" => "_fieldtable",
						"image" => "/intern.png",
						"desc_short" => "Felderliste Intern",
						"desc_long" => "<b>Felderliste Intern</b></br>Alle Felder für den internen Gebrauch, mit Detailinformationen.",
						"release" => "test, partner, extern, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"FeldListeExtern" => Array (
						"title" => "Felderliste Extern",
						"site" => "rkrk_felder.php",
						"parameter" => "Reg=ALL",
						"target" => "_fieldtable",
						"image" => "/public.png",
						"desc_short" => "Felderliste Extern",
						"desc_long" => "<b>Felderliste Public</b></br>Alle Felder für den externen Gebrauch, reduzierte Informationen und Funktionen.",
						"release" => "pub",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
*/
			"FeldListe" => Array (
						"title" => "Felderliste",
						"site" => "rkrk_felderliste.php",
						"parameter" => "Reg=XX",
						"target" => "_fieldtable",
						"image" => "/feldliste.png",
						"image_small" => "/feldliste_small.png",
						"desc_short" => "Felderliste",
						"desc_long" => "<b>Felderliste</b></br>Alle Felder in einer Liste.",
						"main_menu" => "Felder",
						"release" => "test, partner, extern, intern, pub, gast",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => true,
						), 
					
			"FeldKarteIntern" => Array (
						"title" => "Karte Intern",
						"site" => "rkrk_map.php",
						"parameter" => "Reg=ALL".$MapIntern,
						"target" => "_fieldmap",
						"image" => "/geomap.png",
						"image_small" => "/geomap_small.png",
						"desc_short" => "GeoMap Felderkarte intern",
						"desc_long" => "<b>GeoMap Intern</b></br>Alle Felder für den internen Gebrauch, mit Detailinformationen. Kartengrund inkl. SwissTopo schwarz-weiss; SwissTopo-Ebenen u.a. Wohngebiete nach AuLaV und swissALTI3D Relief (standard aus).",
						"main_menu" => "Felder",
						"release" => "test, partner, extern, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),

			"FeldKarteExtern" => Array (
						"title" => "Karte Extern",
						"site" => "rkrk_map.php",
						"parameter" => "Reg=ALL".$MapExtern,
						"target" => "_fieldmap",
						"image" => "/geomap.png",
						"image_small" => "/geomap_small.png",
						"desc_short" => "GeoMap Felderkarte extern",
						"desc_long" => "<b>GeoMap Public</b></br>Alle Felder für den externen Gebrauch, reduzierte Informationen und Funktionen.",
						"main_menu" => "Felder",
						"release" => "pub, gast",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 

			"Personalkarte" => Array (
						"title" => "Personalkarte",
						"site" => "rkrk_map.php",
						"parameter" => "Reg=leer".$MapPersonal,
						"target" => "_fieldmap",
						"image" => "/geomap.png",
						"image_small" => "/geomap_small.png",
						"desc_short" => "Personalkarte",
						"desc_long" => "<b>Personalkarte</b></br>Rehkiztrettungspersonal dargestellt auf einer Kartenübersicht.",
						"main_menu" => "Felder",
						"release" => "test, partner, extern, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 

			"FeldKarte" => Array (
						"title" => "GeoMap Karte",
						"site" => "rkrk_map.php",
						"parameter" => "Reg=ALL".$MapExtern,
						"target" => "_fieldmap",
						"image" => "/geomap.png",
						"image_small" => "/geomap_small.png",
						"desc_short" => "GeoMap Felderkarte",
						"desc_long" => "<b>GeoMap</b></br>Alle Felder auf der Karte.",
						"main_menu" => "Felder",
						"release" => "test, partner, extern, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"NeuesFeld" => Array (
						"title" => "Neues Feld",
						"site" => "rkrk_edit_felddaten.php",
						"parameter" => "NeuesFeld=N",
						"target" => "_editfield",
						"image" => "/neuesfeld.png",
						"image_small" => "/neuesfeld_small.png",
						"desc_short" => "Neues Feld hinzufügen",
						"desc_long" => "<b>Neue Felder</b></br>Neue Felder hinzufügen und Missionendaten laden.",
						"main_menu" => "Edit",
						"release" => "test, intern, partner",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"EditFeld" => Array (
						"title" => "Felddaten ändern",
						"site" => "rkrk_edit_felddaten.php",
						"parameter" => "",
						"target" => "_editfield",
						"image" => "/edit_feld.png",
						"image_small" => "/edit_feld_small.png",
						"desc_short" => "Feld ändern",
						"desc_long" => "<b>Felddaten ändern</b></br>Anpassen von Felddaten und laden von Missionenfiles.",
						"main_menu" => "Edit",
						"release" => "test, intern, partner",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"EditFeldliste" => Array (
						"title" => "Felderliste ändern",
						"site" => "rkrk_edit_felddatenliste.php",
						"parameter" => "",
						"target" => "_editfieldlist",
						"image" => "/edit_feldlist.png",
						"image_small" => "/edit_feldlist_small.png",
						"desc_short" => "Feldliste ändern",
						"desc_long" => "<b>Felddatenliste</b></br>Editiern von Felddaten in einer Liste.",
						"main_menu" => "Edit",
						"release" => "test, intern, partner",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"Adressen" => Array (
						"title" => "Kontakte",
						"site" => "rkrk_adrkntkt.php",
						"parameter" => "",
						"target" => "_contacts",
						"image" => "/contacts.png",
						"image_small" => "/contacts_small.png",
						"desc_short" => "Kontakte editieren",
						"desc_long" => "<b>Kontakte</b></br>Editieren von Kontakten der IG Rehkitzrettung Knonaueramt und Zug.",
						"main_menu" => "Edit",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"AdresseBauern" => Array (
						"title" => "Adresse Bauern",
						"site" => "rkrk_adrlwbtr.php",
						"parameter" => "",
						"target" => "_contacts_farmers",
						"image" => "/contacts_farmers.png",
						"image_small" => "/contacts_farmers_small.png",
						"desc_short" => "Landwirtschaftskontakte editieren",
						"desc_long" => "<b>Landwirtschaftskontakte</b></br>Editieren von Landwirtschaftskontakte der IG Rehkitzrettung Knonaueramt und Zug.",
						"main_menu" => "Edit",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"Planning" => Array (
						"title" => "Wetter und Verfügbarkeit",
						"site" => "rkrk_planning.php",
						"parameter" => "Jahr=".date('Y'),
						"target" => "_availabe",
						"image" => "/weather.png",
						"image_small" => "/weather_small.png",
						"main_menu" => "Einsätze",
						"release" => "test, partner, intern, extern",
						"desc_short" => "Wetter und Verfügbarkeit editieren",
						"desc_long" => "<b>Einsatzplanung</b></br>Wetter und Verfügbarkeit der Piloten editieren",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"Fluege" => Array (
						"title" => "Flugbuchungen",
						"site" => "rkrk_fluege.php",
						"parameter" => "",
						"target" => "_editfluege",
						"image" => "/edit_fluege.png",
						"image_small" => "/edit_fluege_small.png",
						"desc_short" => "Flugbuchungen",
						"desc_long" => "<b>Flugbuchungen & Flugplanung</b></br>Aufträge buchen und Flugerfolge eintragen.<br><b>Wichtig:</b> Bei parallelem Arbeiten Pilotenfilter einschalten oder <i>SaveLine</i> verwenden, damit nur die eigenen Flüge gespeichert werden.<br>Karten, KMZ und JSON über den Pfeil neben Kontrollflug einblenden. Fläche, JG, Gde und Flugzeit sind nur bei Feld <i>MAN</i> editierbar. Checkliste und Tages-PDF (auch für vergangene Einsatztage) stehen pro Pilotentag bereit.",
						"main_menu" => "Einsätze",
						"release" => "test, partner, intern, extern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"RcEinsatz" => Array (
						"title" => "RC Einsatz",
						"site" => "fe/rkrk_rc_einsatz.php",
						"parameter" => "",
						"target" => "_rceinsatz",
						"image" => "/edit_fluege.png",
						"image_small" => "/edit_fluege_small.png",
						"desc_short" => "RC Einsatz",
						"desc_long" => "<b>RC Einsatz</b></br>Gebuchte Felder auf der Fernsteuerung. Feld aus dem Katalog, Geflogen, Fund, Bemerkung und Kontrollflug in dieselbe Flugdatei. KMZ und Checkliste für den Pilotentag.",
						"main_menu" => "Einsätze",
						"release" => "",
						"users" => "Thomas Christen",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Flugliste" => Array (
						"title" => "Einsatzliste",
						"site" => "rkrk_flugliste.php",
						"parameter" => "",
						"target" => "_flugliste",
						"image" => "/flugliste.png",
						"image_small" => "/flugliste_small.png",
						"desc_short" => "Fluglisten",
						"desc_long" => "<b>Fluglisten</b></br>Geplante und ausgeführte Suchflüge mit Resultaten",
						"main_menu" => "Einsätze",
						"release" => "test, partner, intern, extern, pub, gast",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),

			"FlugStats" => Array (
						"title" => "Flug Statistik ",
						"site" => "rkrk_flugstatistik.php",
						"parameter" => "ALL",
						"target" => "_statistics",
						"image" => "/statistics.png",
						"image_small" => "/statistics_small.png",
						"desc_short" => "Flug Statistik",
						"desc_long" => "<b>Flugstatistik</b></br>Auswertung der geleisteten Einsätze pro Pilot, Gemeinde, Jagdgebiet, und Region.",
						"main_menu" => "Einsätze",
						"release" => "test, partner, intern, gast",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"FlugflaechenKarte" => Array (
						"title" => "Geflogene Flächen",
						"site" => "rkrk_flugflaechen_karte.php",
						"parameter" => "",
						"target" => "_flugflaechen",
						"image" => "/geomap.png",
						"image_small" => "/geomap_small.png",
						"desc_short" => "Geflogene Flächen",
						"desc_long" => "<b>Geflogene Flächen</b></br>Karte und Export (KML/PDF/PNG) der im Zeitraum geflogenen Felder.",
						"main_menu" => "Einsätze",
						"release" => "test, partner, intern, extern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"JsonHealth" => Array (
						"title" => "Json Health check",
						"site" => "rkrk_json_health.php",
						"parameter" => "",
						"target" => "_jsonhealth",
						"image" => "/jsonhealth.png",
						"image_small" => "/jsonhealth_small.png",
						"desc_short" => "Json Health",
						"desc_long" => "<b>Json Health</b></br>Prüfen der Qualität der Json Files im Katalog.",
						"main_menu" => "Unterhalt",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"GeoDataHealth" => Array (
						"title" => "Geo Data health",
						"site" => "rkrk_geofence_health.php",
						"parameter" => "",
						"target" => "_datahealth",
						"image" => "/datahealth.png",
						"image_small" => "/datahealth_small.png",
						"desc_short" => "Datenprüfung mit Geofence",
						"desc_long" => "<b>GeoFence Daten</b></br>Prüfen der Zugehörigkeit zu Gemeinde und Flugzonen und vergleich mit den Daten im Katalog.",
						"main_menu" => "Unterhalt",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"Convert" => Array (
						"title" => "Feld Konvertierung",
						"site" => "rkrk_convert.php",
						"parameter" => "",
						"target" => "_convert",
						"image" => "/convert.png",
						"image_small" => "/convert_small.png",
						"desc_short" => "Konvertierung von Flugfelder im Feldkatalog",
						"desc_long" => "<b>Konvertierung von Flugfelder</b></br>Konvertierung von Flugfelder von einer auf ein andere Dohne.",
						"main_menu" => "Unterhalt",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"EditFiles" => Array (
						"title" => "Dateien umbenennen",
						"site" => "rkrk_edit_feldfileliste.php",
						"parameter" => "",
						"target" => "_editfieldfile",
						"image" => "/filerename.png",
						"image_small" => "/filerename_small.png",
						"desc_short" => "Dateien umbenennen",
						"desc_long" => "<b>Umbennennen von Filenamenrelevanten Felder</b></br>Unterhalt des Missionenkatalogs und editieren von Filenamenrelevanten Felder (JG, FeldID, Feldname, Flurname, GeoID). Die Files der Mission werden umbenannt und umkopiert.",
						"main_menu" => "Unterhalt",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"Users" => Array (
						"title" => "Benutzer",
						"site" => "rkrk_users.php",
						"parameter" => "",
						"target" => "_user",
						"image" => "/users.png",
						"image_small" => "/users_small.png",
						"desc_short" => "Benutzerverwaltung",
						"desc_long" => "Benutzerverwaltung",
						"main_menu" => "Unterhalt",
						"release" => "test",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"PilotVer" => Array (
						"title" => "Pilotenverifizierung",
						"site" => "https://www.drohnenrehkitzrettung.ch/pv/verify",
						"parameter" => "P=" . explode(
							",",
							(string)($OptionPilot[$_SESSION[$SessNme_RegisteredInitialen] ?? ""]["PV"] ?? "")
						)[0],
						"target" => "_PV",
						"image" => "/per_ver.png",
						"image_small" => "/per_ver_small.png",
						"desc_short" => "Pilotenverifizierung",
						"desc_long" => "Pilotenverifizierung: Seite mit der Bestätigung der Pilotenidentität, -zugehörigkeit und Flugberechtigungen",
						"main_menu" => "Unterhalt",
						"release" => "test, partner, extern, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
/*
			"QRcodes" => Array (
						"title" => "Links zu internen Websites (mit QR Code)",
						"site" => "rkrk_links.php",
						"parameter" => "",
						"target" => "_qrcodes",
						"image" => "/qrcodes.png",
						"desc_short" => "QR codes",
						"desc_long" => "</b>QR codes</b></br>Links zu internen Websites (mit QR Code)",
						"release" => "test, partner, extern, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),

*/
			"AllFields" => Array (
						"title" => "Alle Felder in der Übersicht",
						"site" => "rkrk_map.php",
						"parameter" => "Reg=&Strt=on&Cntr=off&Area=on&Pfad=on&Poi=off&EX=NO&KA=S&MAP=1&Zoom=0.8",
						"target" => "_allfields",
						"image" => "/allfields.png",
						"image_small" => "/allfields_small.png",
						"desc_short" => "Alle Felder in der Übersicht",
						"desc_long" => "Alle Felder in der Übersicht",
						"main_menu" => "Unterhalt",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => true,
						),
			"Changes" => Array (
						"title" => "Änderungen",
						"site" => "rkrk_changes.php",
						"parameter" => "",
						"target" => "_changes",
						"image" => "/changes.png",
						"image_small" => "/changes_small.png",
						"desc_short" => "Website Änderungen",
						"desc_long" => "<b>Änderungen auf der Website</b></br>Änderungsprotokoll der Website und Zugriff zu den Logfiles.",
						"main_menu" => "Info",
						"release" => "test, partner, extern, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"Partners" => Array (
						"title" => "Partnerschaften, Sponsoring & Finanzkontakte",
						"site" => "rkrk_partners.php",
						"parameter" => "",
						"target" => "_info",
						"image" => "/sponsoring.png",
						"image_small" => "/sponsoring_small.png",
						"desc_short" => "Partnerschaften",
						"desc_long" => "Partnerschaften, Sponsoring & Finanzkontakte",
						"main_menu" => "Info",
						"release" => "test, partner, extern, intern, pub",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"Info" => Array (
						"title" => "Hilfe",
						"site" => "rkrk_info.php",
						"parameter" => "",
						"target" => "_info",
						"image" => "/info.png",
						"image_small" => "/info_small.png",
						"desc_short" => "Informationen zur Page und zum Gebrauch",
						"desc_long" => "<b>Infoseite</b></br>Infoseite mit Funktionsbeschreibungen und Tips.",
						"main_menu" => "Info",
						"release" => "test, partner, extern, intern, pub",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
/* not in use anymore 2024-05-16
			"Pers" => Array (
						"title" => "Pers",
						"site" => "myrkrk.php",
						"parameter" => "",
						"target" => "_etnry",
						"image" => "/perspage.png",
						"desc_short" => "Zur persönlichen Startseite",
						"desc_long" => "<b>Persönliche Startseite</b></br>Von hier sind die wichtigsten Funktionen direkt und einfach erreichbar.",
						"main_menu" => "Mehr",
						"release" => "test, partner, extern, intern, pub",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
*/
			"PdfAdressenBauern" => Array (
						"title" => "Adress-PDF kreieren",
						"site" => "rkrk_pdf_adrlwbtr.php",
						"parameter" => "",
						"target" => "_pdfdoc",
						"image" => "/pdf_button.png",
						"desc_short" => "Adress-PDF kreieren",
						"desc_long" => "PDF Adressliste von Bauernkontakten kreieren",
						"main_menu" => "",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"PdfAdressen" => Array (
						"title" => "Adress-PDF kreieren",
						"site" => "rkrk_pdf_adrkntkt.php",
						"parameter" => "",
						"target" => "_pdfdoc",
						"image" => "/pdf_button.png",
						"desc_short" => "Adress-PDF kreieren",
						"desc_long" => "PDF Adressliste der Kontakte kreieren",
						"main_menu" => "",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"PdfAdressenDaten" => Array (
						"title" => "Adressdaten-PDF kreieren",
						"site" => "rkrk_pdf_adrkntktdaten.php",
						"parameter" => "",
						"target" => "_pdfdoc",
						"image" => "/pdf_button.png",
						"desc_short" => "Adress-PDF kreieren",
						"desc_long" => "PDF Adressdatenliste der Kontakte kreieren",
						"main_menu" => "",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"Feldanmeldung" => Array (
						"title" => "Neues Feld anmelden",
						"site" => "https://rkrs.uaveditor.com/feldanmeldung",
						"parameter" => "",
						"target" => "_newfield",
						"image" => "/feldneu.png",
						"image_small" => "/feldneu_small.png",
						"desc_short" => "Neues Feld anmelden",
						"desc_long" => "Neues Feld im UAV Editor anmelden",
						"main_menu" => "Einsätze",
						"release" => "test, partner, extern, intern, pub",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"PdfFelder" => Array (
						"title" => "Flugkatalog als PDF",
						"site" => "rkrk_pdf_felderliste.php",
						"parameter" => "",
						"target" => "_pdfdoc",
						"image" => "/flugplan.png",
						"image_small" => "/flugplan_small.png",
						"desc_short" => "Flugkatalog als PDF",
						"desc_long" => "Flugkatalog als PDF",
						"main_menu" => "",
						"release" => "test, partner, extern, intern, pub",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"PdfFelderAdr" => Array (
						"title" => "Flugkatalog mit Adressen als PDF",
						"site" => "rkrk_pdf_felderliste.php",
						"parameter" => "",
						"target" => "_pdfdoc",
						"image" => "/flugplan.png",
						"image_small" => "/flugplan_small.png",
						"desc_short" => "Flugkatalog mit Adressen als PDF",
						"desc_long" => "Flugkatalog mit Adressen als PDF",
						"main_menu" => "",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"PdfFlugliste" => Array (
						"title" => "Flugliste als PDF",
						"site" => "rkrk_pdf_flugliste.php",
						"parameter" => "",
						"target" => "_pdfdoc",
						"image" => "/pdf_flugliste.png",
						"image_small" => "/pdf_flugliste_small.png",
						"desc_short" => "Flugliste als PDF",
						"desc_long" => "<b>Flugliste PDF</b></br>Gefilterte Liste der geplanten und geflogenen Einsätze als PDF.",
						"main_menu" => "",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"PdfFlugplan" => Array (
						"title" => "Flugplan als PDF",
						"site" => "rkrk_pdf_fluege.php",
						"parameter" => "",
						"target" => "_pdfdoc",
						"image" => "/pdf_flugliste_g.png",
						"image_small" => "/pdf_flugliste_g_small.png",
						"desc_short" => "Flugplan als PDF",
						"desc_long" => "<b>Flugplan PDF</b></br>Tagesflugplan eines Piloten oder Teams mit Karten, Kontakten und Checkliste.",
						"main_menu" => "",
						"release" => "test, partner, extern, intern, pub",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"RKRZG" => Array (
						"title" => "App RKR-Zug",
						"site" => "https://www.rkrzug.ch/rkr_lw.php",
						"parameter" => "",
						"target" => "_rkrkzug",
						"image" => "/rkrzug.png",
						"image_small" => "/rkrkzug_small.png",
						"desc_short" => "App RKR Zug",
						"desc_long" => "App der Rehkitzrettung Zug",
						"main_menu" => "Unterhalt",
						"release" => "test, partner",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"CompareRRKRZG" => Array (
						"title" => "Vergleich RKR-Zug",
						"site" => $MergedMapLink,
						"parameter" => "",
						"target" => "_compare",
						"image" => "/compare.png",
						"image_small" => "/compare_small.png",
						"desc_short" => "Vergleiche Felder von RKRK mit Zug",
						"desc_long" => "Vergleiche Felder von RKRK mit Zug",
						"main_menu" => "Unterhalt",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"CheckFilesName" => Array (
						"title" => "Filenamen prüfen",
						"site" => "rkrk_filenames.php",
						"parameter" => "",
						"target" => "_checkfiles",
						"image" => "/checkfliename.png",
						"image_small" => "/checkfliename_small.png",
						"desc_short" => "Filenamen prüfen",
						"desc_long" => "Filenamen prüfen",
						"main_menu" => "Unterhalt",
						"release" => "test",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Recovery" => Array (
						"title" => "Flugkatalog wiederherstellen",
						"site" => "rkrk_history_recovery.php",
						"parameter" => "",
						"target" => "_history",
						"image" => "/history.png",
						"image_small" => "/history_small.png",
						"desc_short" => "Flugkatalog wiederherstellen",
						"desc_long" => "Flugkatalog wiederherstellen",
						"main_menu" => "Unterhalt",
						"release" => "test",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"UnterhaltBackup" => Array (
						"title" => "Unterhalt",
						"site" => "rkrk_admin_unterhalt.php",
						"parameter" => "",
						"target" => "_admin",
						"image" => "/settings.png",
						"image_small" => "/settings.png",
						"desc_short" => "Backup, Recover, Code-Sync",
						"desc_long" => "<b>Unterhalt</b></br>Katalog-Sicherungen, Website-Sicherung und Code-Sync (Steuerung: test27).",
						"main_menu" => "Unterhalt",
						"release" => "test",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Jagdgebietsseite" => Array (
						"title" => "Jagdgebietsseite",
						"site" => "rkrk_jg_komm.php",
						"parameter" => "",
						"target" => "_self",
						"image" => "/settings.png",
						"image_small" => "/settings.png",
						"desc_short" => "Infoseite eines Jagdgebiets intern vorbereiten und publizieren",
						"desc_long" => "<b>Jagdgebietsseite</b></br>Texte, Kontakte und Hinweise intern vorbereiten, in der Vorschau prüfen und erst danach publizieren.",
						"main_menu" => "Tools",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Schulung" => Array (
						"title" => "Schulung",
						"site" => "rkrk_quiz.php?Schulung",
						"parameter" => "",
						"target" => "_self",
						"image" => "/schulung.png",
						"image_small" => "/schulung_small.png",
						"desc_short" => "Bibliothek",
						"desc_long" => "Bibliothek, mit spannenden Themen zum dazulernen und zur Wissensprüfung.",
						"main_menu" => "Info",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"SicherFliegen" => Array (
						"title" => "Sicher Fliegen",
						"site" => "rkrk_quiz.php?quiz=Sicheres-Drohnen-fliegen&Schulung&rolle=instruktor",
						"parameter" => "",
						"target" => "_self",
						"image" => "/schulung.png",
						"image_small" => "/schulung_small.png",
						"desc_short" => "Sicher Fliegen",
						"desc_long" => "Sicher Fliegen lernden, ein Guidebook für einen guten Start und sicheren Flug.",
						"main_menu" => "Info",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"InfoSammlung" => Array (
						"title" => "Regel- und Informationssammlung",
						"site" => "rkrk_quiz.php?quiz=Regel-%20und%20Informationssammlung&Schulung&rolle=instruktor",
						"parameter" => "",
						"target" => "_self",
						"image" => "/schulung.png",
						"image_small" => "/schulung_small.png",
						"desc_short" => "Regel- und Informationssammlung",
						"desc_long" => "Regel- und Informationssammlung, mit spannenden Themen über Drohnenflugthemen.",
						"main_menu" => "Info",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Quiz" => Array (
						"title" => "Quiz",
						"site" => "rkrk_quiz.php",
						"parameter" => "",
						"target" => "_quiz",
						"image" => "/quiz.png",
						"image_small" => "/quiz_small.png",
						"desc_short" => "Quiz-Ecke",
						"desc_long" => "Quiz-Ecke, mit spannenden Fragen zum Verweilen und Lernen.",
						"main_menu" => "Info",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"QuizAdmin" => Array (
						"title" => "Quiz Auswertungen",
						"site" => "rkrk_quiz_admin.php",
						"parameter" => "",
						"target" => "_quiz_admin",
						"image" => "/quiz.png",
						"image_small" => "/quiz_small.png",
						"desc_short" => "Auswertung Quiz-Ecke",
						"desc_long" => "Auswertung Quiz-Ecke",
						"main_menu" => "Info",
						"release" => "test",
						"users" => "Thomas Christen",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Calc" => Array (
						"title" => "Bahnberechnung",
						"site" => "rkrk_calc.php",
						"parameter" => "RKRK",
						"target" => "_calc",
						"image" => "/calc.png",
						"image_small" => "/calc_small.png",
						"desc_short" => "Bahnberechnungswerkzeug",
						"desc_long" => "Bahnberechnungswerkzeug für die unterschiedlichen Flugabläufe",
						"main_menu" => "Info",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"FPara" => Array (
						"title" => "Flugparameter",
						"site" => "rkrk_calc_flightparameter.php",
						"parameter" => "RKRK",
						"target" => "_para",
						"image" => "/calc.png",
						"image_small" => "/calc_small.png",
						"desc_short" => "Flugparameter",
						"desc_long" => "Berechnung der optimalen Flugparameter für den Suchflug (manuell oder programmiert).",
						"main_menu" => "Info",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Conversion" => Array (
						"title" => "JSON/KMZ Konvertierung",
						"site" => "rkrk_conversion.php",
						"parameter" => "TEST&ADVANCE",
						"target" => "_conversion",
						"image" => "/convert.png",
						"image_small" => "/convert_small.png",
						"desc_short" => "Konvertierung von einzelfeldern Flugfelder im JSON und KMZ Format (Ausserhalb des Feldkataloges RKRK)",
						"desc_long" => "<b>Konvertierung von Flugfelder</b></br>Konvertierung von einzelfeldern Flugfelder im JSON und KMZ Format. Es können beliebige Felder vom lokale Computer geladen werden.",
						"main_menu" => "Info",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						), 
			"Wetter" => Array (
						"title" => "Flug Wetter",
						"site" => "rkrk_wetter.php", // Drohnenwetter
						"parameter" => "drohnenwetter",
						"target" => "_wetter",
						"image" => "/wetter.png",
						"image_small" => "/wetter_small.png",
						"desc_short" => "Flugwetter",
						"desc_long" => "<b>Flugwetter</b></br>Aktuelle Wetterlage für den Drohneneinsatz (auch in der Checkliste).",
						"main_menu" => "Einsätze",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Checkliste" => Array (
						"title" => "Flug Checkliste",
						"site" => "rkrk_checklist.php", // Digitale Checkliste
						"parameter" => "",
						"target" => "_checklist",
						"image" => "/checklist.png",
						"image_small" => "/checklist_small.png",
						"desc_short" => "Checkliste",
						"desc_long" => "<b>Digitale Checkliste</b></br>Einsatz-Checkliste mit Wetter und Presets. Aus den Flugbuchungen (Pilotentag) vorbefüllt öffnen.",
						"main_menu" => "Einsätze",
						"release" => "test, partner, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"UAVFeldkatalog" => Array (
						"title" => "UAV Feldkatalog",
						"site" => "https://rkrs.uaveditor.com/fieldcatalog/RKRK", // Anpassbar im UAV Editor
						"parameter" => "",
						"target" => "_uavfeldkatalog",
						"image" => "/button_uav.png",
						"image_small" => "/button_uav_small.png",
						"desc_short" => "UAV Feldkatalog",
						"desc_long" => "UAV Feldkatalog",
						"main_menu" => "Einsätze",
						"release" => "test, intern",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"bills" => Array (
						"title" => "Rechnungen",
						"site" => "rt/rkrk_rt.php", // Digitale Rechnungstellung
						"parameter" => "",
						"target" => "_rt",
						"image" => "/rt.png",
						"image_small" => "/rt_small.png",
						"desc_short" => "Rechnungen",
						"desc_long" => "Rechnungs-Tool",
						"main_menu" => "Intern",
						"release" => "test",
						"users" => "Thomas Christen,Chris,Vesa",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"accounting" => Array (
						"title" => "Abrechnung",
						"site" => "ar/public", // Digitale Abrechnung
						"parameter" => "",
						"target" => "_ar",
						"image" => "/ar.png",
						"image_small" => "/ar_small.png",
						"desc_short" => "Abrechnung",
						"desc_long" => "Abrechnungs-Tool",
						"main_menu" => "Intern",
						"release" => "test",
						"users" => "Thomas Christen,Chris,Vesa",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
/*
			"map" => Array (
						"title" => "Karteneditor (orignal)",
						"site" => "map/", // Digitale Abrechnung
						"parameter" => "",
						"target" => "_mapedit",
						"image" => "/map.png",
						"image_small" => "/map_small.png",
						"desc_short" => "Karteneditor",
						"desc_long" => "Karteneditor",
						"main_menu" => "Intern",
						"release" => "test",
						"users" => "Thomas Christen",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
*/
/*
			"map_edit" => Array (
						"title" => "Karteneditor",
						"site" => "map/rkrk_map_edit.php", // Feld Editor
						"parameter" => "",
						"target" => "_mapedit",
						"image" => "/map.png",
						"image_small" => "/map_small.png",
						"desc_short" => "Karteneditor",
						"desc_long" => "Karteneditor",
						"main_menu" => "Intern",
						"release" => "test",
						"users" => "Thomas Christen",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
*/			"map_edit" => Array (
						"title" => "Feld Editor",
						"site" => "fe/rkrk_feld_editor.php", // Feld Editor
						"parameter" => "KAT",
						"target" => "_mapfield",
						"image" => "/map.png",
						"image_small" => "/map_small.png",
						"desc_short" => "Feld Editor",
						"desc_long" => "Feld anlegen oder laden, Flugpfad rechnen, KMZ/UAV exportieren. Kartenbasis Farbe / S/W / Swissimage; Overlays inkl. AuLaV und swissALTI3D Relief.",
						"main_menu" => "Intern",
						"release" => "",
						"users" => "Thomas Christen",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"map_edit_rc" => Array (
						"title" => "Feld Editor auf RC",
						"site" => "fe/rkrk_feld_editor_rc.php",
						"parameter" => "KAT",
						"target" => "_mapfieldrc",
						"image" => "/map.png",
						"image_small" => "/map_small.png",
						"desc_short" => "Feld Editor auf RC",
						"desc_long" => "Feld Editor für die DJI-Fernsteuerung. Fläche tippen, Startpunkt setzen, speichern.",
						"main_menu" => "Intern",
						"release" => "",
						"users" => "Thomas Christen",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"map_obj" => Array (
						"title" => "Overlay Editor",
						"site" => "map/rkrk_map_overlay.php", // Objekte auf Map kreieren
						"parameter" => "ADMIN",
						"target" => "_mapobj",
						"image" => "/map.png",
						"image_small" => "/map_small.png",
						"desc_short" => "Objekt Creator",
						"desc_long" => "Objekte auf der Karte erstellen. Basemap Karte / Satellit / S/W; SwissTopo-Overlays inkl. AuLaV und swissALTI3D Relief.",
						"main_menu" => "Intern",
						"release" => "",
						"users" => "Thomas Christen, Chris",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"map_obj_kos" => Array (
						"title" => "Einsatzkoordination",
						"site" => "map/rkrk_map_overlay.php", // Objekte auf Map kreieren und teilen mit anderen Usern
						"parameter" => "KOS",
						"target" => "_mapobj",
						"image" => "/map.png",
						"image_small" => "/map_small.png",
						"desc_short" => "Einsatzkoordination",
						"desc_long" => "<b>Einsatzkoordination</b></br>Objekte auf der Karte erstellen und mit anderen Usern teilen.",
						"main_menu" => "Intern",
						"release" => "",
						"users" => "Thomas Christen, Chris",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"assess" => Array (
						"title" => "Assesssments RKRS+",
						"site" => "assess/rkrk_assess_admin.php", // Assessment RKRS+
						"parameter" => "ADMIN",
						"target" => "_assess",
						"image" => "/assess.png",
						"image_small" => "/assess_small.png",
						"desc_short" => "Assesssments RKRS+",
						"desc_long" => "Assesssments RKRS+",
						"main_menu" => "Intern",
						"release" => "",
						"users" => "Thomas Christen,Chris",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"rkrscontent" => Array (
						"title" => "Unterlagen RKRS+",
						"site" => "../rkrk/data/rkrsplus_ep/RKRS Ziele.php", // Ziele RKRS+
						"parameter" => "ADMIN",
						"target" => "_assesscontent",
						"image" => "/assess.png",
						"image_small" => "/assess_small.png",
						"desc_short" => "Unterlagen zu Assesssments RKRS+",
						"desc_long" => "Unterlagen zu Assesssments RKRS+",
						"main_menu" => "Intern",
						"release" => "",
						"users" => "Thomas Christen,Chris",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Login" => Array (
						"title" => "Einloggen",
						"site" => "rkrk.php",
						"parameter" => "page_action=login",
						"target" => "_home",
						"image" => "/login.png",
						"image_small" => "/login_small.png",
						"desc_short" => "Benutzer einloggen",
						"desc_long" => "Benutzer einloggen",
						"main_menu" => "",
						"release" => "pub, gast",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"GoBack" => Array (
						"title" => "Zurück",
						"site" => "",
						"parameter" => "",
						"target" => "_info",
						"image" => "/return.png",
						"image_small" => "/return_small.png",
						"desc_short" => "Zurück",
						"desc_long" => "<b>Zurück</b></br>Einen Schritt zurück auf dem Browser.",
						"main_menu" => "Zurück",
						"release" => "test, partner, extern, intern, pub",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"SiteStatistics" => Array (
						"title" => "Seitenstatistik",
						"site" => "rkrk_seitenstatistik.php",
						"parameter" => "",
						"target" => "_sitestat",
						"image" => "/sitestat.png",
						"image_small" => "/sitestat_small.png",
						"desc_short" => "Seitenstatistik",
						"desc_long" => "Zugriffe nach Jahr und Quartal",
						"main_menu" => "Intern",
						"release" => "test",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Logfiles" => Array (
						"title" => "Logfiles",
						"site" => "rkrk_log.php",
						"parameter" => "",
						"target" => "_logfiles",
						"image" => "/logfiles.png",
						"image_small" => "/logfiles_small.png",
						"desc_short" => "Logfiles",
						"desc_long" => "Logfiles",
						"main_menu" => "Intern",
						"release" => "test",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"UnterhaltIntern" => Array (
						"title" => "Unterhalt",
						"site" => "rkrk_admin_unterhalt.php",
						"parameter" => "",
						"target" => "_admin",
						"image" => "/settings.png",
						"image_small" => "/settings.png",
						"desc_short" => "Backup, Recover, Code-Sync",
						"desc_long" => "<b>Unterhalt</b></br>Katalog-Sicherungen, Website-Sicherung und Code-Sync (Steuerung: test27).",
						"main_menu" => "Intern",
						"release" => "test",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"AdminConfig" => Array (
						"title" => "Konfiguration",
						"site" => "rkrk_admin.php",
						"parameter" => "",
						"target" => "_admin",
						"image" => "/settings.png",
						"image_small" => "/settings.png",
						"desc_short" => "Admin: Konfiguration",
						"desc_long" => "<b>Konfiguration</b></br>Missionsbibliotheken, Regionen, Piloten, Menü, Branding und Nachrichten (Mail, Telegram, WhatsApp).",
						"main_menu" => "Intern",
						"release" => "test",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Newsletter" => Array (
						"title" => "Newsletter",
						"site" => "newsletter/rkrk_newsletter.php",
						"parameter" => "",
						"target" => "_newsletter",
						"image" => "/logfiles.png",
						"image_small" => "/logfiles_small.png",
						"desc_short" => "Newsletter",
						"desc_long" => "<b>Newsletter</b></br>Eine Fassung, Du und Sie automatisch, Versand und öffentliche Embed-Seite.",
						"main_menu" => "Intern",
						"release" => "test",
						"users" => "Thomas Christen,Chris,Vesa",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Gemeindebrief" => Array (
						"title" => "Gemeindebriefe",
						"site" => "newsletter/rkrk_gemeindebrief.php",
						"parameter" => "",
						"target" => "_gemeindebrief",
						"image" => "/logfiles.png",
						"image_small" => "/logfiles_small.png",
						"desc_short" => "Gemeindebriefe",
						"desc_long" => "<b>Gemeindebriefe</b></br>Halbjahresbrief mit Flugstatistik, Druck und Mail an die Gemeinden.",
						"main_menu" => "Intern",
						"release" => "test",
						"users" => "Thomas Christen,Chris,Vesa",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
			"Logout" => Array (
						"title" => "Ausloggen",
						"site" => "rkrk.php",
						"parameter" => "page_action=logout",
						"target" => "_home",
						"image" => "/logout.png",
						"image_small" => "/logout_small.png",
						"desc_short" => "Benutzer ausloggen",
						"desc_long" => "Benutzer ausloggen",
						"main_menu" => "Ausloggen",
						"release" => "test, partner, extern, intern, pub",
						"sort" => $menuid++,
						"activ" => true,
						"securl" => false,
						),
	);

	$MainMenu["ChecklistModule"] = Array(
		"title" => "Checklist Module",
		"site" => "rkrk_checklist_O.php",
		"parameter" => "",
		"target" => "_self",
		"image" => "",
		"desc_short" => "",
		"desc_long" => "",
		"main_menu" => "",
		"release" => "test, partner, extern, intern, pub, gast",
		"sort" => 9999,
		"activ" => false,
		"securl" => false,
	);

	if (function_exists('rkrk_config_apply_menu')) {
		rkrk_config_apply_menu($MainMenu);
	}
	if (isset($MainMenu['AdminConfig']) && is_array($MainMenu['AdminConfig'])) {
		$MainMenu['AdminConfig']['image'] = '/settings.png';
		$MainMenu['AdminConfig']['image_small'] = '/settings.png';
	}


	$MainFunctions = Array(
			"Kontobuch" => Array (
						"users" => "Thomas Christen, Chris",
						), 
			"Rechnunungsadmin" => Array (
						"users" => "Thomas Christen, Chris",
						), 
			"AssessmentTemplate" => Array (
						"users" => "Thomas Christen, Chris",
						), 
			"ChecklistTemplate" => Array (
						"users" => "Thomas Christen, Chris",
						), 
					);

	////////////////////////////////
	// NavBar
	////////////////////////////////

Function CreateMenuTree ($OwnSite) {
	global $MainMenu, $WebFreigabe;
	$entries = array();
	foreach ($MainMenu as $key => $MenuEntry) {
		if (!MenuEntryAccessGranted($key)) {
			continue;
		}
		$group = trim(strval($MainMenu[$key]["main_menu"] ?? ""));
		if ($group === "") {
			continue;
		}
		$entries[] = array(
			'key' => $key,
			'group' => $group,
			'sort' => intval($MenuEntry['sort'] ?? 0),
		);
	}
	usort($entries, function ($a, $b) {
		if ($a['sort'] === $b['sort']) {
			return strcmp((string)$a['key'], (string)$b['key']);
		}
		return $a['sort'] <=> $b['sort'];
	});
	$MenuTree = array();
	foreach ($entries as $entry) {
		$MenuTree[$entry['group']][] = $entry['key'];
	}
	return $MenuTree;
}	

function rkrk_menu_icon_is_readable($rel) {
	global $IconLoc;
	$rel = ltrim(str_replace('\\', '/', (string)$rel), '/');
	if ($rel === '') {
		return false;
	}
	$dir = rtrim(str_replace('\\', '/', (string)($IconLoc ?? '')), '/');
	$relPath = ($dir === '') ? $rel : ($dir . '/' . $rel);
	if (is_file($relPath)) {
		return true;
	}
	$abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
	return is_file($abs);
}

function rkrk_menu_icon_src($SiteKey, $preferSmall = true){
	global $MainMenu, $IconLoc;
	$small = $MainMenu[$SiteKey]["image_small"] ?? "";
	$full  = $MainMenu[$SiteKey]["image"] ?? "";
	$candidates = array();
	if ($preferSmall && $small !== "") {
		$candidates[] = $small;
	}
	if ($small !== "") {
		$candidates[] = $small;
	}
	if ($full !== "") {
		$candidates[] = $full;
	}
	if ($small !== "" && preg_match('#^/(.+)_small\.png$#i', $small, $m)) {
		$candidates[] = '/' . $m[1] . '.png';
	}
	if ($full !== "" && preg_match('#^/(.+)_small\.png$#i', $full, $m)) {
		$candidates[] = '/' . $m[1] . '.png';
	}
	$seen = array();
	foreach ($candidates as $rel) {
		$rel = (string)$rel;
		if ($rel === '' || isset($seen[$rel])) {
			continue;
		}
		$seen[$rel] = true;
		if (rkrk_menu_icon_is_readable($rel)) {
			return rkrk_menu_apply_diroffset($IconLoc . $rel);
		}
	}
	return "";
}

function rkrk_menu_apply_diroffset($path) {
	global $MenuDirOffset, $folderoffset;
	$path = strval($path);
	if ($path === "") {
		return $path;
	}
	$offset = trim(strval($MenuDirOffset ?? ""));
	if ($offset === "") {
		$offset = trim(strval($folderoffset ?? ""));
	}
	if ($offset === "" || $offset === "./") {
		return $path;
	}
	if (substr($offset, -1) !== "/") {
		$offset .= "/";
	}
	if (preg_match('/^(?:[a-z][a-z0-9+\-.]*:|\/\/)/i', $path)) {
		return $path;
	}
	if (strpos($path, "/") === 0 || strpos($path, "./") === 0 || strpos($path, "../") === 0 || strpos($path, "#") === 0) {
		return $path;
	}
	return $offset . $path;
}

function rkrk_menu_site_href($SiteKey, $Parameters = "") {
	global $MainMenu;
	$site = strval($MainMenu[$SiteKey]["site"] ?? "");
	$site = rkrk_menu_apply_diroffset($site);
	return $site . $Parameters;
}

function MenuEntryAllowedUsersCsv($SiteKey) {
	global $MainMenu;
	if (!isset($MainMenu[$SiteKey]) || !is_array($MainMenu[$SiteKey])) {
		return "";
	}
	$entry = $MainMenu[$SiteKey];
	if (!empty($entry["users"])) {
		return strval($entry["users"]);
	}
	if (!empty($entry["Benutzername"])) {
		return strval($entry["Benutzername"]);
	}
	if (!empty($entry["allowed_users"])) {
		return strval($entry["allowed_users"]);
	}
	return "";
}

function MenuEntryAccessGranted($SiteKey) {
	global $MainMenu, $WebFreigabe, $SessNme_AccessLevel, $InputUserInfo;
	if (!isset($MainMenu[$SiteKey])) {
		return false;
	}
	if (array_key_exists("activ", $MainMenu[$SiteKey]) && !$MainMenu[$SiteKey]["activ"]) {
		return false;
	}
	$releaseList = strval($MainMenu[$SiteKey]["release"] ?? "");
	$allowedUsers = MenuEntryAllowedUsersCsv($SiteKey);
	$level = trim(strval($WebFreigabe ?? ''));
	if ($level === '') {
		$level = trim(strval($_SESSION[$SessNme_AccessLevel ?? 'UserLevel'] ?? ''));
	}
	if ($level === '') {
		$level = trim(strval($InputUserInfo['Login']['Level'] ?? ''));
	}
	if (function_exists("rkrk_check_release_or_user_access")) {
		return rkrk_check_release_or_user_access($releaseList, $level, $allowedUsers);
	}
	$releaseItems = array();
	foreach (explode(',', $releaseList) as $item) {
		$item = strtolower(trim($item));
		if ($item !== '') {
			$releaseItems[] = $item;
		}
	}
	foreach (explode(',', $level) as $lv) {
		$lv = strtolower(trim($lv));
		if ($lv !== '' && in_array($lv, $releaseItems, true)) {
			return true;
		}
	}
	if ($allowedUsers === "") {
		return false;
	}
	$currentUser = "";
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (!empty($_SESSION["Benutzername"])) {
		$currentUser = trim(strval($_SESSION["Benutzername"]));
	}
	if ($currentUser === "") {
		return false;
	}
	$list = array_map('trim', explode(',', $allowedUsers));
	return in_array($currentUser, $list, true);
}

Function CreateSiteLink ($SiteKey, $Parameters, $ShowSmall=false, $QM = true) {
	global $MainMenu, $WebFreigabe, $IconLoc, $ButtonHeight, $GuestAccess, $UseSecUrl, $Layout;
	$menuoutput = "";
	//$ButtonHeight= 38;
    //echo $WebFreigabe . "<br>";
	if (MenuEntryAccessGranted($SiteKey)) {
		if ($Parameters =="") {
			$Parameters = $MainMenu[$SiteKey]["parameter"];
		}
		if ($Parameters != "") {
			if ($MainMenu[$SiteKey]["securl"] and $UseSecUrl) {
				$Parameters = base64_encode($Parameters); //encoded
			}
		}
		if ($QM) {
			$Parameters = "?" . $Parameters;
		}	
		$ShowSmall = true;
		//$style = "style='border-radius: 10%;border: 1px solid #96B588;background-color: #eeeeee;margin: 2px 2px 2px 2px'";
		$style = "style='border-radius: 10%;border: 4px solid #eeeeee;background-color: #eeeeee;margin: 2px 2px 2px 2px'";
		//$menuoutput = "  <a href='" . $MainMenu[$SiteKey]["site"].$Parameters ."' target='" . $MainMenu[$SiteKey]["target"] . "'><img src='" . $IconLoc . $MainMenu[$SiteKey]["image"] . "' height='" . $ButtonHeight ."' title='" . $MainMenu[$SiteKey]["desc_short"] . "' " . $style . "></a>     ".chr(10);
		$iconSrc = rkrk_menu_icon_src($SiteKey, $ShowSmall);
		$targetHref = rkrk_menu_site_href($SiteKey, $Parameters);
		if ($iconSrc !== "") {
			$menuoutput = "  <a href='" . $targetHref ."' target='" . $MainMenu[$SiteKey]["target"] . "'><img src='" . $iconSrc . "' height='" . $ButtonHeight ."' title='" . $MainMenu[$SiteKey]["desc_short"] . "' " . $style . "></a>     ".chr(10);
		} else {
			$menuoutput = "  <a href='" . $targetHref ."' target='" . $MainMenu[$SiteKey]["target"] . "' title='" . $MainMenu[$SiteKey]["desc_short"] . "'>" . $MainMenu[$SiteKey]["title"] . "</a>     ".chr(10);
		}

}
	return $menuoutput;
}

Function CreateSiteLinkScaled ($SiteKey, $Parameters, $ButtonHeight, $QM = true) {
	global $MainMenu, $WebFreigabe, $IconLoc, $UseSecUrl;
	//$ButtonHeight= 38;

	if (MenuEntryAccessGranted($SiteKey)) {
		if ($Parameters =="") {
			$Parameters = $MainMenu[$SiteKey]["parameter"];
		}
		if ($Parameters != "") {
			
			if ($MainMenu[$SiteKey]["securl"] and $UseSecUrl) {
				$Parameters = base64_encode($Parameters); //encoded
			}
			if ($QM) {
				$Parameters = "?" . $Parameters;
			}	
		}

		$iconSrc = rkrk_menu_icon_src($SiteKey, true);
		$targetHref = rkrk_menu_site_href($SiteKey, $Parameters);
		if ($iconSrc !== "") {
			$menuoutput = "  <a href='" . $targetHref ."' target='" . $MainMenu[$SiteKey]["target"] . "'><img src='" . $iconSrc . "' height='" . $ButtonHeight ."' title='" . $MainMenu[$SiteKey]["desc_short"] . "'></a>     ".chr(10);
		} else {
			$menuoutput = "  <a href='" . $targetHref ."' target='" . $MainMenu[$SiteKey]["target"] . "' title='" . $MainMenu[$SiteKey]["desc_short"] . "'>" . $MainMenu[$SiteKey]["title"] . "</a>     ".chr(10);
		}
	}
	return $menuoutput;
}


Function CreateSiteLinkInfoTableEntry ($SiteKey, $Parameters, $QM = true) {
	global $MainMenu, $WebFreigabe, $IconLoc, $ButtonHeight, $UseSecUrl, $Layout;

	if (MenuEntryAccessGranted($SiteKey)) {
		if ($Parameters =="") {
			$Parameters = $MainMenu[$SiteKey]["parameter"];
		}		

		if ($Parameters != "") {
			if ($MainMenu[$SiteKey]["securl"] and $UseSecUrl) {
				$Parameters = base64_encode($Parameters); //encoded
			}
			if ($QM) {
				$Parameters = "?" . $Parameters;
			}	
		}

		//Output in TableLine
		echo "<tr><td>". chr(10);

		$iconSrc = rkrk_menu_icon_src($SiteKey, $Layout=="2023");
		$targetHref = rkrk_menu_site_href($SiteKey, $Parameters);
		if ($iconSrc !== "") {
			echo "  <a href='" . $targetHref ."' target='" . $MainMenu[$SiteKey]["target"] . "'><img src='" . $iconSrc . "' height='" . $ButtonHeight ."' title='" . $MainMenu[$SiteKey]["desc_short"] . "'></a>     ".chr(10);
		} else {
			echo "  <a href='" . $targetHref ."' target='" . $MainMenu[$SiteKey]["target"] . "' title='" . $MainMenu[$SiteKey]["desc_short"] . "'>" . $MainMenu[$SiteKey]["title"] . "</a>     ".chr(10);
		}
		echo "</td><td>" . $MainMenu[$SiteKey]["desc_long"] . "<td>". chr(10);
		echo "</td></tr>". chr(10);
	}
}

Function CreateMenuTop($SiteKey, $Parameters) {
	global $MainMenu, $WebFreigabe, $IconLoc, $ButtonHeight, $UseSecUrl;
	//echo "SiteKey: " . $SiteKey . "<br>";
	//pre_r($SiteKey);
	if (MenuEntryAccessGranted($SiteKey)) {
		if ($Parameters =="") {
			$Parameters = $MainMenu[$SiteKey]["parameter"];
		}

		if ($Parameters != "") {
			if ($MainMenu[$SiteKey]["securl"] and $UseSecUrl) {
				$Parameters = "?" . base64_encode($Parameters); //encoded
			} else {
				$Parameters = "?" . $Parameters; // clear text
			}
		}
		
		//Output as Menu entry - TOP LINE
		$iconSrc = rkrk_menu_icon_src($SiteKey, true);
		$targetHref = rkrk_menu_site_href($SiteKey, $Parameters);
		$topTitle = (string)($MainMenu[$SiteKey]["title"] ?? $SiteKey);
		if ($iconSrc !== "") {
			echo "<a href='" . $targetHref ."' class='bar-item button hide-small rkrk-nav-desktop' target='" . $MainMenu[$SiteKey]["target"] . "' title='" . htmlspecialchars($topTitle, ENT_QUOTES, 'UTF-8') . "'>" ."<img src='".$iconSrc."' height=20 alt=''> <span class='rkrk-shell-nav-label'>". htmlspecialchars($topTitle, ENT_QUOTES, 'UTF-8') . "</span></a>".chr(10);
		} else {
			echo "<a href='" . $targetHref ."' class='bar-item button padding-large hide-small rkrk-nav-desktop' target='" . $MainMenu[$SiteKey]["target"] . "'>" . htmlspecialchars($topTitle, ENT_QUOTES, 'UTF-8') . "</a>".chr(10);
		}

		//Output as Menu entry
		//echo "<a href='" . $MainMenu[$SiteKey]["site"] . $Parameters ."' class='bar-item button padding-large hide-small' target='" . //$MainMenu[$SiteKey]["target"] . "'>" . $MainMenu[$SiteKey]["title"] . "</a>".chr(10);
	}
}

Function CreateMenuMobile($SiteKey, $Parameters, $Sub="") {
	global $MainMenu, $WebFreigabe, $IconLoc, $ButtonHeight, $UseSecUrl;

	if (MenuEntryAccessGranted($SiteKey)) {
		if ($Parameters =="") {
			$Parameters = $MainMenu[$SiteKey]["parameter"];
		}		

		if ($Parameters != "") {
			if ($MainMenu[$SiteKey]["securl"] and $UseSecUrl) {
				$Parameters = "?" . base64_encode($Parameters); //encoded
			} else {
				$Parameters = "?" . $Parameters; // clear text
			}
		}
		//Output as Menu entry
		//echo "  <a href='#band' class='bar-item button padding-large' onclick='ToggleMyMenu()'>BAND</a>" . chr(10)
		//echo "  <a href='" . $MainMenu[$SiteKey]["site"] .  $Parameters ."' class='bar-item subbutton padding-large' onclick='ToggleMyMenu()'  target='" . $MainMenu[$SiteKey]["target"] . "'>" . $Sub . $MainMenu[$SiteKey]["title"] . "</a>" . chr(10);

		$iconSrc = rkrk_menu_icon_src($SiteKey, true);
		$targetHref = rkrk_menu_site_href($SiteKey, $Parameters);
		$mobTitle = htmlspecialchars((string)($MainMenu[$SiteKey]["title"] ?? $SiteKey), ENT_QUOTES, 'UTF-8');
		$mobTarget = htmlspecialchars((string)($MainMenu[$SiteKey]["target"] ?? ''), ENT_QUOTES, 'UTF-8');
		if ($iconSrc !== "") {
			echo "<a href='" . $targetHref ."' class='bar-item subbutton rkrk-mobile-link' target='" . $mobTarget . "'>" ."<img src='".$iconSrc."' height=20 alt=''>  " . $mobTitle . "</a>".chr(10);
		} else {
			echo "<a href='" . $targetHref ."' class='bar-item subbutton rkrk-mobile-link' target='" . $mobTarget . "'>" . $mobTitle . "</a>".chr(10);
		}

	}
}

Function CreateMenuTopDown($SiteKey, $Parameters) {
	global $MainMenu, $WebFreigabe, $IconLoc, $ButtonHeight, $UseSecUrl;

	if (MenuEntryAccessGranted($SiteKey)) {
		if ($Parameters =="") {
			$Parameters = $MainMenu[$SiteKey]["parameter"];
		}		

		if ($Parameters != "") {
			if ($MainMenu[$SiteKey]["securl"] and $UseSecUrl) {
				$Parameters = "?" . base64_encode($Parameters); //encoded
			} else {
				$Parameters = "?" . $Parameters; // clear text
			}
		}		
		//Output as Menu entry
		$iconSrc = rkrk_menu_icon_src($SiteKey, true);
		$targetHref = rkrk_menu_site_href($SiteKey, $Parameters);
		if ($iconSrc !== "") {
			echo "<a href='" . $targetHref ."' class='bar-item button' target='" . $MainMenu[$SiteKey]["target"] . "'>" . "<img src='".$iconSrc."' height=20>  " . $MainMenu[$SiteKey]["title"] . "</a>".chr(10);
		} else {
			echo "<a href='" . $targetHref ."' class='bar-item button' target='" . $MainMenu[$SiteKey]["target"] . "'>" . $MainMenu[$SiteKey]["title"] . "</a>".chr(10);
		}
	}
}

function CreateTopMenu($diroffset="") {
	global $Layout, $MenuDirOffset, $folderoffset;
	static $menuAlreadyRendered = false;
	if ($menuAlreadyRendered) {
		return;
	}
	if (function_exists('rkrk_shell_menu_on') && !rkrk_shell_menu_on()) {
		return;
	}
	$effectiveOffset = ($diroffset !== "") ? $diroffset : ($folderoffset ?? "");
	$MenuDirOffset = trim(strval($effectiveOffset));
	$MenuTree = CreateMenuTree("");
	DoMenu($MenuTree);
	$menuAlreadyRendered = true;
	if (function_exists('rkrk_menu_emit_client_scripts')) {
		rkrk_menu_emit_client_scripts();
	}
}
function DoMenu($MenuTree) {
	global $WebFreigabe,$LoggedOn,$IconLoc,$Layout,$MainMenu;
		echo "<!-- Navbar -->" . chr(10);
		echo "<div class='top rkrk-shell-header'>" . chr(10);
		echo "  <div class='bar black card'>" . chr(10);
		$homeHref = htmlspecialchars(rkrk_menu_site_href('Home', ''), ENT_QUOTES, 'UTF-8');
		$faviconSrc = '/rkropen/favicon/Reh_FA840B_orange/favicon-32x32.png';
		if (function_exists('rkrk_favicon_assets')) {
			$faviconAssets = rkrk_favicon_assets();
			if (!empty($faviconAssets['png'])) {
				$faviconSrc = (string)$faviconAssets['png'];
			}
		}
		$faviconSrc = htmlspecialchars($faviconSrc, ENT_QUOTES, 'UTF-8');
		echo "   <a class='rkrk-shell-favicon' href='" . $homeHref . "' title='Zur Startseite'><img src='" . $faviconSrc . "' alt='RKRK' height='32'></a>" . chr(10);
		echo "   <a class='bar-item button padding-large rkrk-nav-toggle hide-medium hide-large' href='javascript:void(0)' onclick='ToggleMyMenu()' title='Menü'><i class='fa fa-bars'></i></a>" . chr(10);
		if ($LoggedOn) {
			$seenTopTitles = array();
			foreach ($MenuTree as $Key => $Menu) {
				//pre_R($Menu);
				//echo "count: " . count($Menu) . "<br>";
				if (count($Menu)== 1) {
					//single entry
					$singleKey = $Menu[0] ?? "";
					if ($singleKey === "Home") {
						continue;
					}
					$singleTitle = trim(strval($MainMenu[$singleKey]["title"] ?? $singleKey));
					$singleNorm = mb_strtolower($singleTitle, 'UTF-8');
					if ($singleNorm !== '' && isset($seenTopTitles[$singleNorm])) {
						continue;
					}
					if ($singleNorm !== '') $seenTopTitles[$singleNorm] = 1;
					CreateMenuTop($Menu[0], "");
				} else {
					//submenu
					$keyTitle = trim(strval($Key));
					$keyNorm = mb_strtolower($keyTitle, 'UTF-8');
					if ($keyNorm !== '' && isset($seenTopTitles[$keyNorm])) {
						continue;
					}
					if ($keyNorm !== '') $seenTopTitles[$keyNorm] = 1;
					$keySafe = htmlspecialchars($keyTitle, ENT_QUOTES, 'UTF-8');
					echo "    <div class='dropdown-hover hide-small rkrk-nav-desktop'>" . chr(10);
					echo "      <button class='padding-large button' title= '". $keySafe . "'><span class='rkrk-shell-nav-label'>". $keySafe . "</span> <i class='fa fa-caret-down'></i></button>" . chr(10);     
					echo "      <div class='dropdown-content bar-block card-4'>" . chr(10);
					//pre_r($Menu);
					foreach ($Menu as $SubMenu) {
						CreateMenuTopDown($SubMenu, "");
					}
					echo "      </div>" . chr(10);
					echo "    </div>" . chr(10);
				}
			}
		} else {
			//not logged in
			CreateMenuTop("Login", "");
		}
		$logoSrc = htmlspecialchars(rkrk_menu_apply_diroffset('images/calc/rkrk_logo.png'), ENT_QUOTES, 'UTF-8');
		echo "<a class='rkrk-shell-logo' href='" . $homeHref . "' title='Zur Startseite'><img src='" . $logoSrc . "' alt='RKRK' height='40'></a>" . chr(10);
		echo "  </div>" . chr(10);
		echo "<!-- Navbar on small screens (remove the onclick attribute if you want the navbar to always show on top of the content when clicking on the links) -->" . chr(10); 
		echo "<div id='navDemo' class='bar-block black rkrk-mobile-panel hide' style='display:none;'>" . chr(10);
		if ($LoggedOn) {
			$seenMobileTitles = array();
			foreach ($MenuTree as $Key => $Menu) {
				if (count($Menu)== 1) {
					//single entry
					$singleKey = $Menu[0] ?? "";
					$singleTitle = trim(strval($MainMenu[$singleKey]["title"] ?? $singleKey));
					$singleNorm = mb_strtolower($singleTitle, 'UTF-8');
					if ($singleNorm !== '' && isset($seenMobileTitles[$singleNorm])) {
						continue;
					}
					if ($singleNorm !== '') $seenMobileTitles[$singleNorm] = 1;
					CreateMenuMobile($Menu[0], "");
				} else {
					$groupItems = array();
					foreach ($Menu as $SubMenu) {
						$subTitle = trim(strval($MainMenu[$SubMenu]["title"] ?? $SubMenu));
						$subNorm = mb_strtolower($subTitle, 'UTF-8');
						if ($subNorm !== '' && isset($seenMobileTitles[$subNorm])) {
							continue;
						}
						if ($subNorm !== '') $seenMobileTitles[$subNorm] = 1;
						$groupItems[] = $SubMenu;
					}
					if (count($groupItems) === 0) {
						continue;
					}
					$groupSafe = htmlspecialchars(trim(strval($Key)), ENT_QUOTES, 'UTF-8');
					echo "<details class='rkrk-mobile-group'>" . chr(10);
					echo "<summary class='rkrk-mobile-group-title'>" . $groupSafe . "</summary>" . chr(10);
					echo "<div class='rkrk-mobile-group-items'>" . chr(10);
					foreach ($groupItems as $SubMenu) {
						CreateMenuMobile($SubMenu, "");
					}
					echo "</div>" . chr(10);
					echo "</details>" . chr(10);
				}
			}
		} else {
			//not logged in
			CreateMenuMobile("Home", "");
			CreateMenuMobile("Login", "");
		}
		echo "</div>" . chr(10);

	   echo "</div>" . chr(10);
}

/**
 * Menü-JS nur bei CreateTopMenu ausgeben — nicht automatisch beim include von rkrk_menu.php
 * (sonst landet <script> vor Favicon/DOCTYPE und der Browser holt …/assess/favicon.ico → 404).
 */
function rkrk_menu_emit_client_scripts(): void {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	echo "<script>\n";
	echo "// Used to toggle the menu on small screens when clicking on the menu button\n";
	echo "function ToggleMyMenu() {\n";
	echo "  var x = document.getElementById(\"navDemo\");\n";
	echo "  if (!x) return;\n";
	echo "  if (x.className.indexOf(\"show\") == -1) {\n";
	echo "    x.className += \" show\";\n";
	echo "    x.style.display = \"block\";\n";
	echo "  } else {\n";
	echo "    x.className = x.className.replace(\" show\", \"\");\n";
	echo "    x.style.display = \"none\";\n";
	echo "  }\n";
	echo "}\n";
	echo "var modal = document.getElementById('ticketModal');\n";
	echo "window.onclick = function(event) {\n";
	echo "  if (event.target == modal) { modal.style.display = \"none\"; }\n";
	echo "}\n";
	echo "var myIndex = 0;\n";
	echo "function carousel() {\n";
	echo "  var i; var x = document.getElementsByClassName(\"mySlides\");\n";
	echo "  for (i = 0; i < x.length; i++) { x[i].style.display = \"none\"; }\n";
	echo "  myIndex++; if (myIndex > x.length) { myIndex = 1; }\n";
	echo "  x[myIndex-1].style.display = \"block\"; setTimeout(carousel, 4000);\n";
	echo "}\n";
	echo "function ShowHide(mybloc, mybutton, mytext1, mytext2) {\n";
	echo "  var d = document.getElementById(mybloc); var b = document.getElementById(mybutton);\n";
	echo "  if (d.style.display === \"none\") { d.style.display = \"block\"; b.innerHTML = mytext1; }\n";
	echo "  else { d.style.display = \"none\"; b.innerHTML = mytext2; }\n";
	echo "}\n";
	echo "function ShowHideOnImage(mybloc, myimage, myimage1, myimage2) {\n";
	echo "  var d = document.getElementById(mybloc); var i = document.getElementById(myimage);\n";
	echo "  if (d.style.display === \"none\") { d.style.display = \"block\"; i.innerHTML = \"show\"; i.src = myimage2; }\n";
	echo "  else { d.style.display = \"none\"; i.innerHTML = \"hide\"; i.src = myimage1; }\n";
	echo "}\n";
	echo "function Swop(mybloc1, mybloc2, mybutton, mytext1, mytext2) {\n";
	echo "  var b1 = document.getElementById(mybloc1); var b2 = document.getElementById(mybloc2); var b = document.getElementById(mybutton);\n";
	echo "  if (b1.style.display === \"none\") { b1.style.display = \"block\"; b2.style.display = \"none\"; b.innerHTML = mytext1; }\n";
	echo "  else { b1.style.display = \"none\"; b2.style.display = \"block\"; b.innerHTML = mytext2; }\n";
	echo "}\n";
	echo "function SwopOnImage(mybloc1, mybloc2, myimage, myimage1, myimage2) {\n";
	echo "  var b1 = document.getElementById(mybloc1); var b2 = document.getElementById(mybloc2); var i = document.getElementById(myimage);\n";
	echo "  if (b1.style.display === \"none\") { b1.style.display = \"block\"; b2.style.display = \"none\"; i.src = myimage1; }\n";
	echo "  else { b1.style.display = \"none\"; b2.style.display = \"block\"; i.src = myimage2; }\n";
	echo "}\n";
	echo "(function(){\n";
	echo "  var nav = document.querySelector('.rkrk-shell-header');\n";
	echo "  var html = document.documentElement;\n";
	echo "  if (!nav || !html) return;\n";
	echo "  function isMapNav(){\n";
	echo "    return html.classList.contains('rkrk-map27') || html.classList.contains('rkrk-embed') || html.classList.contains('rkrk-map27-embed');\n";
	echo "  }\n";
	echo "  if (!isMapNav()) html.classList.add('rkrk-has-nav');\n";
	echo "  var bar = nav.querySelector('.bar');\n";
	echo "  var fitting = false;\n";
	echo "  var lastW = -1;\n";
	echo "  function overflows(){\n";
	echo "    if (!bar) return false;\n";
	echo "    return bar.scrollWidth > bar.clientWidth + 2;\n";
	echo "  }\n";
	echo "  function fitNav(force){\n";
	echo "    if (!bar || fitting) return;\n";
	echo "    var w = window.innerWidth || 0;\n";
	echo "    if (!force && w === lastW) return;\n";
	echo "    lastW = w;\n";
	echo "    fitting = true;\n";
	echo "    html.classList.remove('rkrk-nav-compact', 'rkrk-nav-mobile');\n";
	echo "    void bar.offsetWidth;\n";
	echo "    if (overflows()) html.classList.add('rkrk-nav-compact');\n";
	echo "    void bar.offsetWidth;\n";
	echo "    if (overflows()) {\n";
	echo "      html.classList.remove('rkrk-nav-compact');\n";
	echo "      html.classList.add('rkrk-nav-mobile');\n";
	echo "    }\n";
	echo "    fitting = false;\n";
	echo "  }\n";
	echo "  function applyHeight(){\n";
	echo "    if (isMapNav()) return;\n";
	echo "    var h = Math.ceil(nav.getBoundingClientRect().height) || 56;\n";
	echo "    html.style.setProperty('--rkrk-nav-h', h + 'px');\n";
	echo "    if (document.body) document.body.style.setProperty('padding-top', h + 'px', 'important');\n";
	echo "  }\n";
	echo "  function apply(force){\n";
	echo "    fitNav(force);\n";
	echo "    applyHeight();\n";
	echo "  }\n";
	echo "  apply(true);\n";
	echo "  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ apply(true); });\n";
	echo "  window.addEventListener('resize', function(){ apply(false); });\n";
	echo "  if (typeof ResizeObserver !== 'undefined') { try { new ResizeObserver(function(){ applyHeight(); }).observe(nav); } catch (e) {} }\n";
	echo "})();\n";
	echo "</script>\n";
}


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="icon" type="image/png" sizes="192x192" href="../favicon/Reh_FA840B_orange/android-icon-192x192.png?v=20260915a" />
<link rel="apple-touch-icon" sizes="192x192" href="../favicon/Reh_FA840B_orange/android-icon-192x192.png?v=20260915a" />
<link rel="icon" type="image/png" sizes="96x96" href="../favicon/Reh_FA840B_orange/android-icon-96x96.png?v=20260915a" />
<link rel="icon" type="image/png" sizes="48x48" href="../favicon/Reh_FA840B_orange/android-icon-48x48.png?v=20260915a" />
<link rel="icon" type="image/png" sizes="32x32" href="../favicon/Reh_FA840B_orange/favicon-32x32.png?v=20260915a" />
<link rel="icon" type="image/png" sizes="16x16" href="../favicon/Reh_FA840B_orange/favicon-16x16.png?v=20260915a" />
<link rel="icon" type="image/x-icon" href="../favicon/Reh_FA840B_orange/rkrk.ico?v=20260915a" />
<link rel="shortcut icon" href="../favicon/Reh_FA840B_orange/rkrk.ico?v=20260915a" />
<link rel="apple-touch-icon" sizes="180x180" href="../favicon/Reh_FA840B_orange/apple-icon-180x180.png?v=20260915a" />
</head>
<?php
	//session_start();
?>
<script>
//Settings
var vJagdgebietName="26 Knonau"
var vJagdgebiet=26

//var vSiteSetting="https://tchr-frog.synology.me:5682/rkropen/pub/rkrk_jg_summary.php?Reg="+vJagdgebiet+"&Color=white";
//var vSiteSetting="https://tchr-frog.synology.me/rkropen/pub/rkrk_jg_summary.php?Reg="+vJagdgebiet+"&Color=white";
var vSiteSetting="rkrk_jg_summary.php?Reg="+vJagdgebiet+"&Color=white";
var vWidth="100%"
var vHeight="3500"

//var vTableStyle= 'style="border-collapse: collapse;border-style:hidden;"'
var vTableStyle= ''
var vCenterText = 'style="text-align: center; vertical-align: middle;"'

//TableStyle
document.write('<style>');
document.write('table, th, td {');
document.write('  border: 1px solid transparent;');
document.write('  border-collapse: collapse;');
document.write('}');
document.write('</style>');


//Titel
document.write('<H1>'+vJagdgebietName+'</H1>');

// JG Zusammenfassung
//document.write('<embed type="text/html" src="'+vSiteSetting+'"  width="'+vWidth+'" height="'+vHeight+'">');
document.write('<iframe type="text/html" src="'+vSiteSetting+'"  width="'+vWidth+'" height="'+vHeight+'">');
  
</script>

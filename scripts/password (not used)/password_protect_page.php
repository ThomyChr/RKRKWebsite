<?php
# Simple password protection
#
# (c) http://www.phpbuddy.com
# Author: Ranjit Kumar
# Feel free to use this script but keep this message intact!
# 
# To protect a page include this file in your PHP pages!
# Updated by TCHR, 24.04.2013

	//session_start();
	include('_private/dbi.php');

$admin_user_name = "admin"; 
$admin_password = "pass";
//you can change the username and password by changing the above two strings 

//enable password protection
$enable_pw_protection = TRUE;  /* !!!! */

if ($enable_pw_protection==TRUE) 
{
  if (!isset($_SESSION['user'])) {
	if(isset($_POST['u_name'])) 
		$u_name = $_POST['u_name'];
	if(isset($_POST['u_password'])) 
		$u_password = $_POST['u_password'];
	if(!isset($u_name)) {
		?>
		<HTML>
		<HEAD>
		   <LINK href="template/main_style.php" rel="stylesheet" type="text/css">
           <META HTTP-EQUIV="content-type" CONTENT="text/html; charset=ISO-8859-1">  
           <TITLE><?php echo $_SERVER['HTTP_HOST']; ?> : Authentication Required</TITLE>
		</HEAD>
		<BODY>
		<table>
			 <TR><TD>
			 <B>(Access Restricted to Authorized Personnel)</b></td>
			 </tr></table>
		<?php
		$form_to = "http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
		//echo $form_to;
		if(isset($_SERVER["QUERY_STRING"]))
		$form_to = $form_to ."?". $_SERVER["QUERY_STRING"];
		//echo $form_to;
		?>
			<body>

				<form action="<?php echo $form_to; ?>" method="POST">
					<Table width=400">
						<tr><td>User Name:</td><td><input type=text name=u_name size=20></td></tr>
						<tr><td>Password: </td><td><input type=password name=u_password size=20></td></tr>
						<tr><td span="2">
						<input class="button" type="submit"  value=" Login" />
					</td></tr>  
					</table>
				</form>
			</body>
		</html>	
		<?php
		exit;
   }
   else {
		function login_error($host,$php_self) {
			echo "<HTML><HEAD>
			<LINK href='template/main_style.php' rel='stylesheet' type='text/css'>
            <META HTTP-EQUIV='content-type' CONTENT='text/html; charset=ISO-8859-1'>  
            <TITLE>$host :  Administration</TITLE>
			</HEAD><BODY bgcolor=#ffffff>
			<table border=0 cellspacing=0 cellpadding=0 width=100%>
				 <TR><TD align=left>
				 <B>  You Need to log on to access this part of the site! </b> </td>
				 </tr></table>
			<P></P>";
			echo "Error: You are not authorized to access this part of the site!
			<B><a href=$php_self>Click here</a></b> to login again.<P>
			</HTML>";
			session_unregister("adb_password");
			session_unregister("user");
			exit;
		}
		
		$user_checked_passed = false;
		
        $sql = "SELECT `User`.User_Password, `User`.email FROM `User` WHERE `User`.User_Name='$u_name';";
	    //$res = mysql_query($sql);
        $res = mysqli_query($conn,$sql);
	    if (!$res) {
          die('Ungültige Anfrage/01: (' . mysqli_errno($conn). ':' . mysqli_error($conn). ') in SQL: '. $sql);
        }	
	    $row = mysqli_fetch_array($res);
		
		if(isset($_SESSION['adb_password'])) {
			$adb_session_password = $_SESSION['adb_password'];
			$adb_session_user = $_SESSION['user'];
			if($admin_password != $adb_session_password) 
				login_error($_SERVER['HTTP_HOST'],$_SERVER['PHP_SELF']);
			else {
				$user_checked_passed = true;
			}
		}
        if($user_checked_passed == false) {

			if(strlen($u_name)< 2) 
				login_error($_SERVER['HTTP_HOST'],$_SERVER['PHP_SELF']);
			if(isset($admin_password)) {
				if(($admin_password == $u_password) AND ($u_name == $admin_user_name)) {
				    // admin password correct
					$_SESSION["adb_password"]= $admin_password;
					$_SESSION["user"]= $u_name;
				}
				else { 
				  IF ($row[0] == $u_password) {
					// user password correct
					$_SESSION["user_password"]= $u_password;
					$_SESSION["user"]= $u_name;
					$_SESSION["email"]= $row[1];
					}
					else {
				    //password in-correct
					login_error($_SERVER['HTTP_HOST'],$_SERVER['PHP_SELF']);
					}
				}
			}
			else {
				login_error($_SERVER['HTTP_HOST'],$_SERVER['PHP_SELF']);
			}
			$page_location = $_SERVER['PHP_SELF'];
   		    if(isset($_SERVER["QUERY_STRING"])) {
			  $page_location = $page_location ."?". $_SERVER["QUERY_STRING"];
			}
			header ("Location: ". $page_location);
		}
	}	
  }
}
else
{
  echo "<p  class='small_alert'>Note: PW protection disabled!</p>";
}
?>

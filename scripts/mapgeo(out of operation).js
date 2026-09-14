 ////////////////////////////////////////////////
// include HMTL and Sidemanue
// TC 15.03.2022

function includeHTML() {
  var z, i, elmnt, file, xhttp;
  /* Loop through a collection of all HTML elements: */
  z = document.getElementsByTagName('*');
  for (i = 0; i < z.length; i++) {
    elmnt = z[i];
    /*search for elements with a certain atrribute:*/
    file = elmnt.getAttribute('include-html');
    if (file) {
      /* Make an HTTP request using the attribute value as the file name: */
      xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState == 4) {
          if (this.status == 200) {elmnt.innerHTML = this.responseText;}
          if (this.status == 404) {elmnt.innerHTML = 'Page not found.';}
          /* Remove the attribute, and call this function once more: */
          elmnt.removeAttribute('include-html');
          includeHTML();
        }
      }
      xhttp.open('GET', file, true);
      xhttp.send();
      /* Exit the function: */
      return;
    }
  }
}

    
// SideNav https://www.w3schools.com/howto/howto_js_sidenav.asp
/* Set the width of the side navigation to 250px and the left margin of the page content to 250px */
function openNav() {
  document.getElementById("mySidenav").style.width = "300px";
  document.getElementById("main").style.marginLeft = "300px";
}

function closeNav() {
  document.getElementById("mySidenav").style.width = "0";
  document.getElementById("main").style.marginLeft= "0";
}

 

//Menu Button https://www.w3schools.com/howto/howto_css_topnav_equal_width.asp


function MenuFpl() {
  document.write('blabla');	
  document.write('<div id="mySidenav" class="sidenav">');
  document.write('<a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>');
  document.write(' <a href="mapgeo.asp">ALLE</a>');
  document.write(' <a href="flugplaene21.php">21 - Aeugst</a>');
  document.write(' <a href="flugplaene25.php">25 - Kappel a.A.</a>');
  document.write(' <a href="flugplaene26.php">26 - Knonau</a>');
  document.write(' <a href="mapgeo.php?JG=27">27 - Maschwanden</a>');
  document.write(' <a href="mapgeo.php?JG=28">28 - Mettmenstetten</a>');
  document.write(' <a href="mapgeo.php?JG=AZ">AZ - Jagdgebiet Amt/ZH</a>');
  document.write(' <a href="mapgeo.php?JG=AG">AG - Oberes Freiamt/AG</a>');
  document.write(' <a href="mapgeo.php?JG=ZG">ZG - Zug</a>');
  document.write(' <a href="mapgeo.php?JG=XX">XX - Andere Regionen</a>');
  document.write(' <a href="mapgeo.php?JG=TF">TF - testfelder</a>');
  document.write(' </div>  ');
   document.write('blabla');	
  
}


function createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/; domain=.example.com";
}
function eraseCookie(name) {
createCookie(name,"",-1);
}

 function orientation() {
  /*window.orientation returns a value that indicates whether iPhone is in portrait mode, landscape mode with the screen turned to the
left, or landscape mode with the screen turned to the right. */
return(window.orientation)
}


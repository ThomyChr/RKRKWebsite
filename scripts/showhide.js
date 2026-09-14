// JSON script to show and hide blocks in HTML

<script>
// Automatic Slideshow - change image every 4 seconds
//var myIndex = 0;
//carousel();

function carousel() {
  var i;
  var x = document.getElementsByClassName("mySlides");
  for (i = 0; i < x.length; i++) {
    x[i].style.display = "none";  
  }
  myIndex++;
  if (myIndex > x.length) {myIndex = 1}    
  x[myIndex-1].style.display = "block";  
  setTimeout(carousel, 4000);    
}

function ShowHide(mybloc, mybutton, mytext1, mytext2) {
	var d = document.getElementById(mybloc);
	var b = document.getElementById(mybutton);
	if (d.style.display === "none") {
		d.style.display = "block";
		b.innerHTML = mytext1;
	} else {
		d.style.display = "none";
		b.innerHTML = mytext2;
	}
}

function ShowHideOnImage(mybloc, myimage, myimage1, myimage2) {
	var d = document.getElementById(mybloc);
	var i = document.getElementById(myimage);
	if (d.style.display === "none") {
		d.style.display = "block";
		i.innerHTML = "show";
		i.src = myimage2;
	} else {
		d.style.display = "none";
		i.innerHTML = "hide";
		i.src =  myimage1;
	}
}

function Swop(mybloc1, mybloc2, mybutton, mytext1, mytext2) {
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

function SwopOnImage(mybloc1, mybloc2, myimage, myimage1, myimage2) {
	var b1 = document.getElementById(mybloc1);
	var b2 = document.getElementById(mybloc2);
	var i = document.getElementById(myimage);
	if (b1.style.display === "none") {
		b1.style.display = "block";
		b2.style.display = "none";
		//i.innerHTML = "b1";
		i.src = myimage1;
		
	} else {
		b1.style.display = "none";
		b2.style.display = "block";
		//i.innerHTML = "b2";
		i.src = myimage2;
	}
}


</script>
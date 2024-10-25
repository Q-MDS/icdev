

                <IFRAME style='width: 120; height: 100%' ID=tourdates NAME="tourdates" SRC="tour-day.phtml?frame=1&depot=CA&ss=103449&ser=87829" SCROLLING="AUTO" FRAMEBORDER="YES"></iframe>
  		<IFRAME NAME="tourday" style='width: 600; height: 100%' SRC="tour-day.phtml?depot=CA&frame=2&ss=103449&ser=87829" SCROLLING="AUTO" FRAMEBORDER="YES">
		</iframe>

<script>
	function doresize() {
		try {
		document.getElementById('tourdates').style.width='600px';
		}
		catch (error) {
		}

	}

	function dosmallresize() {
                try {
                document.getElementById('tourdates').style.width='120px';
                }
                catch (error) {
                }

        }

</script>

<html>
<body xoncontextmenu="showMenu(); return false"; bgcolor="#FFFFFF" text="#000000"
 topmargin=2 leftmargin=2 link="#000000" vlink="#000000" alink="#000000">
<script language=javascript>
function doquit()
{
	if (confirm("Quit Application?"))
		window.location='logoff.phtml';
}
</script>


<!-- Context Menu -->
<div id=menu1 onclick="clickMenu()" onmouseover="toggleMenu()" onmouseout="toggleMenu()" style="position:absolute;display:none;border: 1px outset black; width:180;background-Color:menu">

<div class="menuItem" style="cursor:pointer;" onclick="window.print()">
Print</div>
<hr>

<div class="menuItem" style="cursor:pointer;" onclick="window.location='management.phtml'">
Management Page</div>

<div class="menuItem" style="cursor:pointer;" onclick="window.location='main.phtml'">
Main Search Page</div>
<hr>

<div class="menuItem" style="cursor:pointer;" onclick="doquit()">
Quit Application</div>
</div>
<!-- End of Context Menu -->

<script>

var el;

function showMenu() {
   ContextElement=event.srcElement;
   //menu1.style.left+=10;
   menu1.style.left=event.clientX + "px";
   menu1.style.top=event.clientY + "px";
   menu1.style.display="";
   //menu1.setCapture();
   return true;
}
function toggleMenu() {   
   el=event.srcElement;
   if (el.className=="menuItem") {
      el.className="highlightItem";
   } else if (el.className=="highlightItem") {
      el.className="menuItem";
   }
}
function clickMenu() {
//   menu1.releaseCapture();
   menu1.style.display="none";
   el=event.srcElement;
   if (el.onclick != null) {
     eval(el.onclick);
   }
}
</script>
<div><a href='switch_user.phtml?returnto=%2Fbooking%2Ftour-day.phtml%3Fdepot%3DCA%26ser%3D87829%26ss%3D103449'><img height=25 id=flag src='images/ZAR.gif' /></a><script type='text/javascript'>function switchoff(flag) { document.getElementById('flag').src='images/'+flag ; }</script></div></form>
</body>
</html>

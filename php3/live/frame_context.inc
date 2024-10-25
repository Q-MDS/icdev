<?
/* ********************************************************************************* */
/* **************** Context menu for top frame (toolbar.phtml) ********************* */
/* ********************************************************************************* */
?>
<script language=javascript>
function doquit()
{
	if (confirm("Quit Application?"))
		parent.window.location='logoff.phtml';
}
</script>

<!-- Context Menu -->
<div id=menu1 onclick="clickMenu()" onmouseover="toggleMenu()" onmouseout="toggleMenu()" style="position:absolute;display:none;border: 1px outset black; width:180;background-Color:menu">

<div class="menuItem" doFunction="window.print()">
Print</div>
<hr>

<div class="menuItem" doFunction="parent.window.location='management.phtml'">
Management Page</div>

<div class="menuItem" doFunction="parent.window.location='main.phtml'">
Main Search Page</div>
<hr>

<div class="menuItem" doFunction="doquit()">
Quit Application</div>
</div>
<!-- End of Context Menu -->

<script>

var el;

function showMenu() {
   ContextElement=event.srcElement;
   menu1.style.leftPos+=10;
   menu1.style.posLeft=event.clientX;
   menu1.style.posTop=event.clientY;
   menu1.style.display="";
   menu1.setCapture();
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
   menu1.releaseCapture();
   menu1.style.display="none";
   el=event.srcElement;
   if (el.doFunction != null) {
     eval(el.doFunction);
   }
}
</script>

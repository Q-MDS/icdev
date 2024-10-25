<?
/* ***************************************************************************************** */
/* ************ This is the javascript for the default right click context menu ************ */
/* ***************************************************************************************** */
?>
<script language=javascript>
function doquit()
{
	if (confirm("Quit Application?"))
		window.location='logoff.phtml';
}
</script>

<? if ($REMOTE_USER!="t3939393w"): ?>

<!-- Context Menu -->
<div id=menu1 onclick="clickMenu()" onmouseover="toggleMenu()" onmouseout="toggleMenu()" style="position:absolute;display:none;border: 1px outset black; width:180;background-Color:menu">

<? if (!isset( $nocontextprint)):  ?>
<div class="menuItem" style="cursor:pointer;" onclick="window.print()">
Print</div>
<hr>
<? endif;
if (isset($dosubmit))
if ($dosubmit==1):
?>
<div class="menuItem" style="cursor:pointer;" onclick="document.autoform.submit()">
Next Two</div>
<div class="menuItem" style="cursor:pointer;" onclick="history.back()">
Back</div>
<?
endif; ?>

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
<? else: ?>
<script>
</script>
<? endif; ?>

<?
/* ************************************************************** */
/* *************** Context menu with PRINT only ***************** */
/* ************************************************************** */
?>
<!-- Context Menu -->
<div id=menu1 onclick="clickMenu()" onmouseover="toggleMenu()" onmouseout="toggleMenu()" style="position:absolute;display:none;border: 1px outset black; width:180;background-Color:menu">

<div class="menuItem" doFunction="window.print()">
Print</div>
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

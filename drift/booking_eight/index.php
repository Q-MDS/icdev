
<html>
<head>
<link rel="stylesheet" type="text/css" href="/booking/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="/booking/css/dataTables.bootstrap.min.css">
</head>
<body>
<link rel="stylesheet" type="text/css" href="driftstyle.css" />
<script src="/booking/js/jquery-3.5.1.min.js"></script>
<script src="/booking/js/jquery.dataTables.min.js"></script>
<script src="/booking/js/dataTables.bootstrap.min.js"></script>
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
<div id='mainnav'><a href=drift_dates.phtml>Edit Dates</a>  <a href=drift_parameters.phtml>Edit Scenarios</a>  <a href=drift_globals.phtml>Edit Globals</a>  <a href=drift.phtml>DRIFT Forecast</font></a>  <a href=drift_report_details.phtml>Route Details</a>  <a href=drift_report_details2.phtml>Date Details</a>  <a class=active href=drift_report.phtml>Summary</a></div><hr><div style='padding: 5px'><form method=get id=myform><B>Season:</b> <select name=date_range_serial onchange="document.getElementById('myform').submit();"><option value=506 SELECTED>December 2024 - from 20241201 to 20241231
<option value=581>Dec 2024 Week of 10th - from 20241210 to 20241215
<option value=541>January 2025 - from 20250101 to 20250131
<option value=561>February 2025 - from 20250201 to 20250228
<option value=562>March 2025 - from 20250301 to 20250331
<option value=563>April 2025 - from 20250401 to 20250430
<option value=564>May 2025 - from 20250501 to 20250531
</select>
 <input type=submit value=Go><br></form><a href=drift_report.phtml?csv=Y&date_range_serial=506>Download as CSV</a><br><div id=tablediv style='width: 70%'><table id=mytable border=1 cellspacing=0 width='100%'><thead><tr bgcolor=#BBBBBB><td><B>Scenario</td><td align=right><b>Depot</td><td align=right><b>Last Generated</td><td align=right><b>Heading</td><td align=right><b>Value</td><td align=right><b>+5%</td><td align=right><b>+10%</td><td align=right><b>+15%</td></tr></thead><tbody><tr bgcolor=#96E1FA><td>BLM BUDGET 2024_25</td><td align=right>BLM</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>14</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#96E1FA><td>BLM BUDGET 2024_25</td><td align=right>BLM</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>33</td><td align=right>35</td><td align=right>36</td><td align=right>38</td></tr><tr bgcolor=#96E1FA><td>BLM BUDGET 2024_25</td><td align=right>BLM</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>334</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#9696C8><td>CA BUDGET 2024_25 DEC</td><td align=right>CA</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>233</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#9696C8><td>CA BUDGET 2024_25 DEC</td><td align=right>CA</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>232</td><td align=right>244</td><td align=right>255</td><td align=right>267</td></tr><tr bgcolor=#9696C8><td>CA BUDGET 2024_25 DEC</td><td align=right>CA</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>1611</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#96E1C8><td>CBS BUDGET 2024_25</td><td align=right>CBS</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>17</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#96E1C8><td>CBS BUDGET 2024_25</td><td align=right>CBS</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>27</td><td align=right>28</td><td align=right>30</td><td align=right>31</td></tr><tr bgcolor=#96E1C8><td>CBS BUDGET 2024_25</td><td align=right>CBS</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>195</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#969696><td>DBN BUDGET 2024_25</td><td align=right>DBN</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>99</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#969696><td>DBN BUDGET 2024_25</td><td align=right>DBN</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>112</td><td align=right>118</td><td align=right>123</td><td align=right>129</td></tr><tr bgcolor=#969696><td>DBN BUDGET 2024_25</td><td align=right>DBN</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>835</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#96E196><td>DEA BUDGET 2024_25</td><td align=right>DEA</td><td align=right>13-NOV-24</td><td align=right>Drivers on SOPS</td><td align=right>3</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#96E196><td>DEA BUDGET 2024_25</td><td align=right>DEA</td><td align=right>13-NOV-24</td><td align=right>Minimum Drivers Needed</td><td align=right>1</td><td align=right>1</td><td align=right>1</td><td align=right>1</td></tr><tr bgcolor=#96E196><td>DEA BUDGET 2024_25</td><td align=right>DEA</td><td align=right>13-NOV-24</td><td align=right>Total Trips</td><td align=right>0</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C896FA><td>ALL DEPOTS MARCH 2024</td><td align=right>EVA</td><td align=right>13-NOV-24</td><td align=right>Drivers on SOPS</td><td align=right>5</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C896FA><td>ALL DEPOTS MARCH 2024</td><td align=right>EVA</td><td align=right>13-NOV-24</td><td align=right>Minimum Drivers Needed</td><td align=right>9</td><td align=right>9</td><td align=right>10</td><td align=right>10</td></tr><tr bgcolor=#C896FA><td>ALL DEPOTS MARCH 2024</td><td align=right>EVA</td><td align=right>13-NOV-24</td><td align=right>Total Trips</td><td align=right>0</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C8E1FA><td>GAB BUDGET 2024_25</td><td align=right>GAB</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>4</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C8E1FA><td>GAB BUDGET 2024_25</td><td align=right>GAB</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>3</td><td align=right>3</td><td align=right>3</td><td align=right>3</td></tr><tr bgcolor=#C8E1FA><td>GAB BUDGET 2024_25</td><td align=right>GAB</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>58</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C896C8><td>MAP BUDGET 2024_25</td><td align=right>MAP</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>15</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C896C8><td>MAP BUDGET 2024_25</td><td align=right>MAP</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>13</td><td align=right>14</td><td align=right>14</td><td align=right>15</td></tr><tr bgcolor=#C896C8><td>MAP BUDGET 2024_25</td><td align=right>MAP</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>143</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C8E1C8><td>Umtata Budget 23/24</td><td align=right>MTH</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>6</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C8E1C8><td>Umtata Budget 23/24</td><td align=right>MTH</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>4</td><td align=right>4</td><td align=right>4</td><td align=right>5</td></tr><tr bgcolor=#C8E1C8><td>Umtata Budget 23/24</td><td align=right>MTH</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>0</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C89696><td>PE BUDGET 2024_25</td><td align=right>PE</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>31</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C89696><td>PE BUDGET 2024_25</td><td align=right>PE</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>44</td><td align=right>46</td><td align=right>48</td><td align=right>51</td></tr><tr bgcolor=#C89696><td>PE BUDGET 2024_25</td><td align=right>PE</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>340</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C8E196><td>PTA BUDGET 2024_25 DEC</td><td align=right>PTA</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>244</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#C8E196><td>PTA BUDGET 2024_25 DEC</td><td align=right>PTA</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>223</td><td align=right>234</td><td align=right>245</td><td align=right>256</td></tr><tr bgcolor=#C8E196><td>PTA BUDGET 2024_25 DEC</td><td align=right>PTA</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>2092</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#FA96FA><td>UPT BUDGET 2024_25</td><td align=right>UPT</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>11</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#FA96FA><td>UPT BUDGET 2024_25</td><td align=right>UPT</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>9</td><td align=right>9</td><td align=right>10</td><td align=right>10</td></tr><tr bgcolor=#FA96FA><td>UPT BUDGET 2024_25</td><td align=right>UPT</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>62</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#FAE1FA><td>WHK BUDGET 2024_25</td><td align=right>WHK</td><td align=right>10-DEC-24</td><td align=right>Drivers on SOPS</td><td align=right>23</td><td align=right></td><td align=right></td><td align=right></td></tr><tr bgcolor=#FAE1FA><td>WHK BUDGET 2024_25</td><td align=right>WHK</td><td align=right>10-DEC-24</td><td align=right>Minimum Drivers Needed</td><td align=right>34</td><td align=right>36</td><td align=right>37</td><td align=right>39</td></tr><tr bgcolor=#FAE1FA><td>WHK BUDGET 2024_25</td><td align=right>WHK</td><td align=right>10-DEC-24</td><td align=right>Total Trips</td><td align=right>346</td><td align=right></td><td align=right></td><td align=right></td></tr><tr><td><b>~Total</td><td align=right><b>All</td><td></td><td align=right>Drivers on SOPS</td><td align=right>705</td><td align=right></td><td align=right></td><td align=right></td></tR><tr><td><b>~Total</td><td align=right><b>All</td><td></td><td align=right>Minimum Drivers Needed</td><td align=right>744</td><td align=right>781</td><td align=right>818</td><td align=right>856</td></tR><tr><td><b>~Total</td><td align=right><b>All</td><td></td><td align=right>Total Trips</td><td align=right>6016</td><td align=right></td><td align=right></td><td align=right></td></tR></tbody></table></div></form>

</div>
<script>
$(document).ready(function() {
    console.log('about to');
    $('#mytable').DataTable( { "searching": true, "pageLength": 25,  "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ] } );
    console.log('test');
} );
</script>


</body>
</html>

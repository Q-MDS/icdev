<?php
/** 
 * NEW CODE: Start
 * 
 */
function oci_conn()
{
	$host = 'localhost';
	$port = '1521';
	$sid = 'XE';
	$username = 'SYSTEM';
	$password = 'dontletmedown3';

	$conn = oci_connect($username, $password, "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port)))(CONNECT_DATA=(SID=$sid)))");

	if (!$conn) 
	{
		$e = oci_error();
		// echo "Connection failed: " . $e['message'];
		exit;
	} 
	else 
	{
		// echo "Connection succeeded";
	}

	return $conn;
}
/** 
 * NEW CODE: End
 * 
 */
?>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;charset=iso-8859-1">
		<title>Jobcard Authorizations</title>
		<LINK href="move_style.css" type=text/css rel=stylesheet></link>
	
                <script language="javascript" type="text/javascript">
	                // Only allow numbers to be entered
	                function maskKeyPress(objEvent){
	                        var iKeyCode;  	
	                        iKeyCode = objEvent.keyCode;			
	                        if((iKeyCode>=48 && iKeyCode<=57) || iKeyCode<32) return true;
	                        return false;
	                }
                </script>
	
	</head>
<body>

 <div id="topmenu" class="menu"><table class="menu"><tr bgcolor=white><td bgcolor=white width=300><font size=-1><b>Quintin (<a target='_blank' href="changedepot.phtml">PTA</a>) (<a href="changedepot.phtml">INTERCAPE MAINL...</a>)</b></font></td><td width=100 align='center'><font size=-1><a href="index.phtml">MOVE Menu</a></font></td><td width=110 align='center'><font size=-1><a href="manageindex.phtml">Management</a></font></td><td bgcolor=yellow width=100 align='center'><a href="checkjobcard.phtml?stage=0&recent=1"><font size=-1><b>Find Job Card</font></a></td><td width=100 bgcolor=lightblue align='center'><A target='_top' href='https://secure.intercape.co.za/ignite/index.php?c=overtime_staff&m=vdash_overtime_preapprove&page_id=1561'>OVERTIME</a></td><td width=100><a href="requestorder.phtml?stage=1&filter=2&depot=PTA"><font size=-1>View Orders</font></a></td><td width=100 align='center'><a target=_new href="/parcel/index.phtml"><font size=-1>Parcels</font></a></td><td width=100 align='center'><a href="move_invoice_payandbatch.phtml"><font size=-1>Creditors</font></a></td></tr> </table></div><br>

	<script>
		var oin = -1;
		function bdalert(str,oinvalue) {
			document.getElementById('bdalert').innerHTML = str;
			document.getElementById('oin').value = oinvalue;
			oin = oinvalue;
			document.getElementById('bdoin').style.display='block';
		}

		function oincat(newcat) {
			console.log(newcat);
			console.log('#'+oin);
			document.getElementById('bdcatframe').src='bdoin.phtml?oin='+oin+'&newcat='+newcat;
		}

		function bdupdate() {
			let x = document.getElementById('newoincat');
			document.getElementById('bdalert').innerHTML = ' <font color=magenta>RECONCILED TO ' + x.options[x.selectedIndex].text;
		}
	</script>
	<script>
		function update_bdcat(ser) {
			console.log('updating with '+ ser);
			document.getElementById('bdcatframe').src='update_breakdown.phtml?jc=16213330&cat='+ser;

		}

		function update_jccat(ser) {
			console.log('updating with '+ ser);
			document.getElementById('jccatframe').src='update_jctype.phtml?jc=16213330&cat='+ser;
		}
	</script>

<!-- Jobcard Details -->
<script> 
	function update_brokedown(newvalue) { document.getElementById('brokedown').innerHTML=newvalue; brokedown=newvalue; console.log('now '+newvalue); }
</script>

<?php
/** 
 * NEW CODE: Start
 * 
 */
if (isset($_GET['stage']))
{
	$stage = $_GET['stage'];

	$conn = oci_conn();

	print_r($_POST);
	
	if ($stage == 7)
	{
		echo "Please enter a person and a comment";
		$create_timestamp = strtotime($_POST['comment_date']);
		$create_person = strtotime($_POST['comment_person']);
		$create_msg = strtotime($_POST['comment_msg']);
		
		if ($create_person == "" || $create_msg == "") 
		{
			// echo "Please enter a person and a comment";
			// exit;
		} 
		else
		{
			$sql = "INSERT INTO move_jobcard_comments (jocard_id, create_date, person, comment_desc) VALUES (:jobcard_id, :create_date, :person, :comment_desc)";

			// Parse the SQL statement
			$stid = oci_parse($conn, $sql);
	
			// Bind the POST fields to the SQL statement
			oci_bind_by_name($stid, ':jobcard_id', $_POST['jobcard_id']);
			oci_bind_by_name($stid, ':create_date', $create_timestamp);
			oci_bind_by_name($stid, ':person', $_POST['comment_person']);
			oci_bind_by_name($stid, ':comment_desc', $_POST['comment_msg']);
	
			// Execute the SQL statement
			$result = oci_execute($stid);
	
			// Check if the insert was successful
			if ($result) {
				// echo "Comment added";
			} else {
				$e = oci_error($stid);
				// echo "Error adding comment: " . $e['message'];
			}
	
			// Free the statement
			oci_free_statement($stid);
			
			// header("Location: " . $_SERVER['PHP_SELF']);
			// exit;
		}
	} 
	
	// Close the connection
	oci_close($conn);

}
/** 
 * NEW CODE: End
 * 
 */
?>
<table cellpadding='3' cellspacing='1' border='0' bgcolor='black' width='100%'>
	<tr><td colspan='12' bgcolor='#B0C4DE'><b>Jobcard Details</b></td></tr>
	<tr bgcolor='#D3D3D3'>
		<td><b>Jobcard Serial</b></td>
		<td><b>Vehicle / Unit / 3rd Party</b></td>
		<td><b>Opened</b></td>
		<td><b>Issued by</b></td>
		<td><b>Closed Date</b></td>
		<td><b>Foreman Auth Date</b></td>
		<td><b>Finance Auth Date</b></td>
		<td><b>Break Down</b></td>
		<td><b>Last Trip</b></td>
		<td><b>Closed</b></td>
		<td><b>KM Reading</b></td>
		<td><b>Depot</b></td>
	</tr>
	<tr bgcolor='#F5F5F5'>
		<td><B>16213330<A onclick="document.getElementById('reclassify').style.display='block'; return false">*</a><div style='display: none' id=reclassify><font size=1><a href=jobcard_daily_auth.phtml?stage=4&subjob_more=true&goback=Y&jobserial=16213330&newclass=N>Change to No Accident</a><br><a href=jobcard_daily_auth.phtml?stage=4&subjob_more=true&goback=Y&jobserial=16213330&newclass=M>Change to MAJOR Accident</a><br><a href=jobcard_daily_auth.phtml?stage=4&subjob_more=true&goback=Y&jobserial=16213330&newclass=m>Change to minor Accident</a><br></div></B></td>
		<td><a href=managecoachinfo.phtml?stage=51&ser=3405>DD131</a></td>
		<td>14-DEC-23</td>
		<td>Wessel         </td>
		<td></td>
		<td> </td>
		<td> </td>
		<td align='center'><script> var brokedown='N';</script><span id=brokedown onclick=" console.log('was '+brokedown); document.getElementById('bdcatframe').src='bdswitch.phtml?ser=16213330&current='+brokedown;">N</span></td>
		<td>&nbsp;</td>
		<td align='center'>N </td><td align='left'>373697.7</td><td>CA</td>
	</tr>
	<tr class=cell>
		<td class=altcel><b>Comments:</b></td>
		<td colspan=15> <iframe id=bdcatframe height='20px' width='250px'></iframe></td>
	</tr>
	<tr class=cell>
		<td class=altcel><b>Jobcard Type:</b></td>
		<td colspan=15>Unclassified 
			<Select name=jccat  onchange="update_jccat(this.options[this.selectedIndex].value);" id=jccat><option value='S'>Service</option>
				<option value='L'>Normal Repairs</option>
				<option value='J'>Major Repairs</option>
				<option value='N'>Non-critical Repairs</option>
				<option value='C'>Campaign</option>
				<option value='R'>Refurbishment</option>
				<option value='B'>Breakdown</option>
				<option value='A'>Accident</option>
				<option value='I'>Due to misuse</option>
				<option value='U' SELECTED>Unclassified</option>
			</select> (Only RTM may change this) <iframe id=jccatframe height='20px' width='250px'></iframe>
		</td>
	</tr>
	<tr class=cell>
		<td class=altcel><b>Breakdown Category:</b></td>
		<td colspan=15><input type=hidden name=oin id=oin value='-1'><span id=bdalert></span> <div id=bdoin style='display:none'>
			<select onchange="oincat(this.options[this.selectedIndex].value);" name=newoincat id=newoincat>
				<option value=''>Please select the RTM final determined classification</option>
				<option value='20'>Test2 Buggered Engine</option>
				<option value='19'>Test2 Buggered Gearbox</option>
			</select></div>
		</td>
	</tr>
</table>
<p>


<!-- 
- NEW CODE: Start 
- 
-->
<!-- New form and list: Comments -->
<form action="q1.php?stage=7" method="post">
	<table cellpadding='3' cellspacing='1' width='100%' border="0">
		<tr class="cell">
			<td colspan='12' bgcolor='#B0C4DE'><b>Comments</b></td>
		</tr>
		<tr class="cell">
			<td colspan='12' bgcolor='#D3D3D3' width="100%"><b>Add a comment</b></td>
		</tr>
		<tr>
			<td width="120">
				<input name="jobcard_id" type="hidden" value="1724827732">
				<input name="comment_date" type="text" value="<?php echo date('Y-m-d H:i:s')?>" placeholder="Enter date">
			</td>
			<td width="200"><input name="comment_person" type="text" value="" placeholder="Enter person"></td>
			<td width="100%" style="padding-right: 5px"><input name="comment_msg" type="text" value="" placeholder="Enter comment" style="width: 100%;"></td>
		</tr>
		<tr>
			<td colspan='12'><input type='submit' value='Add Comment'></td>
		</tr>
		<tr bgcolor='#D3D3D3'>
			<td width="120">Date</td>
			<td width="200">Person</td>
			<td>Comments</td>
		</tr>
		<?php
		$conn = oci_conn();

		// Prepare the SQL query
		$sql = "SELECT create_date, person, comment_desc FROM move_jobcard_comments WHERE jocard_id = 1724827732";

		// Parse the SQL statement
		$stid = oci_parse($conn, $sql);

		// Execute the SQL statement
		oci_execute($stid);

		// Loop through the fetched records and output them
		while ($row = oci_fetch_assoc($stid)) 
		{
			$create_date = date('Y-m-d H:i:s', $row['CREATE_DATE']);
			echo "<tr>";
			echo "<td>" . $create_date . "</td>";
			echo "<td>" . htmlspecialchars($row['PERSON']) . "</td>";
			echo "<td>" . htmlspecialchars($row['COMMENT_DESC']) . "</td>";
			echo "</tr>";
		}

		// Free the statement
		oci_free_statement($stid);

		// Close the connection
		oci_close($conn);
		?>
	</table>
</form>
<!-- 
- NEW CODE: End 
- 
-->


<br/>
<!-- Costing - Labour -->
<table cellpadding='3' cellspacing='1' border='0' bgcolor='black' width='100%'><tr><td colspan='10' bgcolor='#B0C4DE'><b>Costing - Labour</b></td></tr><tr bgcolor='#D3D3D3'><td><b>No.</b></td><td><b>Labour</b></td><td><b>Fault Description</b></td><td><b>Mechanic Notes</b></td><td><B>Mechanic</b></td><td><B>Comments</b></td></tr><tr bgcolor='white'><td align='left' valign='top' nowrap>1.</td><td align='right' valign='top' nowrap>0:00</td><td align='left'> <font size=2> </font> Please attend to breakdown<br>Reported 14 Dec 23 08:29</td><td align='left'><br><br>@</td><td><font color=red>(No time?)</font>  </td><td align='left'></td></tr><tr bgcolor='#FFE1E1'><td align='right'><b>Total</b></td><td align='right' nowrap><b>0.0 hrs</b></td><td colspan=4 bgcolor='#D3D3D3'></td></tr></table>

<!-- Petty Cash Expenses Title -->
<table border=1 cellspacing=0><tr class=head><td colspan=4>Petty Cash Expenses</td></tr>0</table>

<p>

<!-- Table below petty cash button -->
<table cellpadding='3' cellspacing='1' border='0' bgcolor='black' width='100%'><tr><td colspan='10' bgcolor='#B0C4DE'><b>Totals</b> (Note: It is possible for costing to change if purchases or issues against this jobcard are not yet finalized) </td></tr><tr bgcolor='#D3D3D3'><td><b>Labour 0 mins (R <font color='red'>150.00 </font>p/h )</b></td><td><b>Parts Cost</b></td><td><b>Total Jobcard Cost</b></td></tr><tr bgcolor='#D9FFE4' ><td align='right'><b>0.00</b></td><td align='right'><b>0.00</b></td><td align='right'><b>R <font color='red'>0.00</font></b></td></tr></table>NB: The stock at the depot is locked<br>Auth not possible yet.  Reason(s): Not Closed Yet, <br><p><input type='button' name='return' value='Close Window' onClick='javascript:window.close();'> &nbsp; <input type=button value='Go Back' onclick='javascript:history.back()'><br><b><u>OPS LOGS FOR MOVE16213330 (DD131):</b></u><Br><br><br><b><u>Incident reports from NEW DASHBOARD:</u></b> <b>Sorted by Incident</b>  <a href='/move/jobcard_daily_auth.phtml?zzz=1&stage=4&subjob_more=true&jobserial=16213330&goback=Y&sortby=T'>Sort by Time logged</a><bR>

<!-- Incident report from NEW DASHBOARD -->
<table border=1 cellspacing=0>
	<tr bgcolor=lavender>
		<td colspan=20><b><font style='background: yellow'>DD131</font> Incident Description: <font style='background: lightpink'>#1 </font></b></td>
	</tr>
	<tr bgcolor=lavender>
		<td><b>Vehicle</b></td>
		<td><b>Date</b></td>
		<td><b>Person</b></td>
		<td><b>Category</b></td>
		<td><b>Action</b></td>
		<td><b>Comments</b></td>
	</tr>
	<tr bgcolor=lavender><td><font size=2><font style='background: yellow'>DD131</font></td><td><font size=2>2023/12/14 08:50</td><td><font size=2>Wessel         </td><td><font size=2>Test - Test2</td><td><font size=2>Create Jobcard</td><td><font size=2>Created jobcard MOVEundefined</td><td><font size=2>1</td><td><font size=2>19</td></tr><tr bgcolor=lavender><td><font size=2><font style='background: yellow'>DD131</font></td><td><font size=2>2023/12/14 08:54</td><td><font size=2>Wessel         </td><td><font size=2>Test - Test2</td><td><font size=2>Create Jobcard</td><td><font size=2>Created jobcard MOVE16213370</td><td><font size=2>1</td><td><font size=2>19</td></tr>Jobcard Referenced below: <a target=move href='https://secure.intercape.co.za/move/newjobcarditems.phtml?stage=2&jobcardserial=16213370'>MOVE16213370</a> (Another jobcard - logs included for your interest)<br><tr bgcolor=lavender><td><font size=2><font style='background: yellow'>DD131</font></td><td><font size=2>2023/12/14 08:59</td><td><font size=2>Wessel         </td><td><font size=2>Test - Test2</td><td><font size=2>Flag Delay</td><td><font size=2>test</td><td><font size=2>1</td><td><font size=2>19</td></tr><tr bgcolor=lavender><td><font size=2><font style='background: yellow'>DD131</font></td><td><font size=2>2023/12/14 15:32</td><td><font size=2>Keith          </td><td><font size=2>Test - Test2</td><td><font size=2>Create Jobcard</td><td><font size=2>Created jobcard MOVE16213520</td><td><font size=2>1</td><td><font size=2>19</td></tr>Jobcard Referenced below: <a target=move href='https://secure.intercape.co.za/move/newjobcarditems.phtml?stage=2&jobcardserial=16213520'>MOVE16213520</a> (Another jobcard - logs included for your interest)<br><tr bgcolor=lavender><td colspan=20><b><font style='background: yellow'>DD131</font> Incident Description:</b> <font style='background: lightpink'>#21 this is the second incident</font></b></td></tr><tr bgcolor=lavender><td><b>Vehicle</b></td><td><b>Date</b></td><td><b>Person</b></td><td><b>Category</b></td><td><b>Action</b></td><td><b>Comments</b></td></tr><tr bgcolor=lavender><td><font size=2><font style='background: yellow'>DD131</font></td><td><font size=2>2023/12/14 15:33</td><td><font size=2>Keith          </td><td><font size=2>Test - Test2</td><td><font size=2>Create Jobcard</td><td><font size=2>Created jobcard MOVE16213540</td><td><font size=2>21</td><td><font size=2>20</td></tr>Jobcard Referenced below: <a target=move href='https://secure.intercape.co.za/move/newjobcarditems.phtml?stage=2&jobcardserial=16213540'>MOVE16213540</a> (Another jobcard - logs included for your interest)<br></table><p> 
</form>

</body>
</html>

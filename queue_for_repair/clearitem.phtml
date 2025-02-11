<? 

/*    PROGRAMMERS NOTES:
	
	statusengineer =
	Z - No info yet
	C - Completed / Not Found  
	I - Incomplete (Carry over)	

        D - Not Done
        S - Done


        faultvalid means the opposite - if set to Y it means 'fault not found'


*/


if (getenv("REMOTE_ADDR")=="192.168.11.100"):
/*
 	echo "Stage is $stage<br>".$_GET['stage']."<br>".getenv("REQUEST_URI")."<br>";
	$x=getenv("REQUEST_URI");
	echo "<pre>";
	for ($z=0;$z<strlen($x);$z++)
		echo ord($x[$z])."=".$x[$z]."\n";
	echo "</pre><hr>";
*/

endif;
?>
<html>
	<head><link type="text/css" rel="stylesheet" href="style.css"></head>
	<body>
	<script language="JavaScript">
        function checkLen( target, size ) {
        	if( target.value.length > size ) {
            	target.value = target.value.substring(0,size);
            }
        }
    </script>
	<?
	require_once("error.inc");
	require_once("serial.inc");
    	require_once("colors.inc");
	require_once("../php3/oracle.inc");
    	//require_once("../php3/logs.inc");
    	//require_once("../php3/misc.inc");
    	require_once("../php3/sec.inc");

    if( !open_oracle() )
    	exit;
    	
	$joballow=AllowedFlag("MOVE_JOBCARDS"); 

	get_colors( getenv("REMOTE_USER" ) );
	get_serial( getenv("REMOTE_USER" ) );

	print "<a href=\"#\" onClick=\" window.close();\">Click here</a> to close this window<hr>";
	echo "<a href='/move/'>Back to MOVE</a> ";
	if (is_numeric($jcno)) {
		echo " / <a href='viewjobcard.phtml?jobcardserial=$jcno'>Back to Jobcard MOVE$jcno</a><bR>";
	}
	echo "<hr>";


	if ($reset=="Y" && is_numeric($itemserial)) {

		ora_parse($cursor,"update move_jobcarditems set statusengineer='I' where itemserial=$itemserial");
		ora_exeC($cursor);
		ora_commit($conn);
   ?>
<script>
try { opener.location='viewjobcard.phtml?jobcardserial=<? mt_srand ((double) microtime() * 1000000);
  $rnd=mt_rand(); echo "$jcno&random=$rnd"; ?> '; window.close();  } catch (error) {}
</script>
                <form name="temp" method="post">
                        <input type="hidden" name="tfirst" value="<?=$first?>">
                        <input type="hidden" name="jcno" value="<?=$jcno?>">
                        <input type="hidden" name="stage" value="1">
                        <input type="hidden" name="itemserial" value="<?=$itemserial?>">
                        <input type="hidden" name="rnd" value="<?=$rnd?>">
                </form>
                <script language="JavaScript"> temp.submit() </script>
     <?


	}
//	echo "Stage is $stage<br>";
	if (isset($_POST['update_hrs'])){
		
		// *** UPDATE THE MECHANIC HOURS ***
		// Remove all entries in the move_jobcarditems
		
		$minutes = (((int)$hours_1 * 60) + (int)$mins_1);
		$comments_1=str_replace("'","",$comments_1);
		$qry = "UPDATE move_jobcarditems SET completedwho='".$_POST['mechanic_1']."',mechanicnotes='$comments_1', minutes='".$minutes."', mechanic_date=CURRENT_TIMESTAMP WHERE itemserial='$itemserial'";
    		ora_parse( $cursor, $qry);
    		ora_exec( $cursor );
		
		// Update from move_itemextra
		$qry = "DELETE FROM move_itemextra WHERE itemserial='$itemserial'"; 
    		
    		ora_parse( $cursor, $qry);
    		ora_exec( $cursor );		
		
		for ($i = 2; $i <= $max_count; $i++){
			$minutes = (($_POST['hours_'.$i] * 60) + $_POST['mins_'.$i]);
			$qry = "INSERT INTO move_itemextra (mechanic,itemserial,minutes,mechanicnotes,mechanic_date) VALUES ('".$_POST['mechanic_'.$i]."','".$itemserial."','".$minutes."','".$_POST['comments_'.$i]."',CURRENT_TIMESTAMP)";
	    		ora_parse( $cursor, $qry);
    			ora_exec( $cursor );		
		}
		

	}
	
	if( $stage==0 ) {
	}
	elseif( $stage==1 ) {
		ora_parse( $cursor, "SELECT B.statusengineer, A.completed, B.deferred_To, A.jobcardserial, nvl(B.minutes,0) FROM MOVE_JOBCARDITEMS B, MOVE_JOBS A WHERE B.itemserial=$itemserial AND A.jobcardserial=B.jobcardserial" );
		ora_exec( $cursor );
		$status    = ora_getColumn( $cursor, 0 );
		$completed = ora_getColumn( $cursor, 1 );
		$jc = getdata($cursor, 3);
		if ($status=="I" && !is_numeric(getdata($cursor,2))) {
			echo "<b><font color=magenta>Marked as incomplete BUT you can change it to COMPLETED still</font></b><bR>";
			$resubmit = true;
		}
		else $resubmit = false;
//		echo "Completed is $completed ($status)<br>";

		if( $completed=="Y" ) {
			print "JOB CARD IS COMPLETE";
			exit;
		}
		
		if( ($status!="Z" && $status!="D" ) )
			$tfirst = "N";
		
	$myuname=getenv("REMOTE_USER");
	ora_parse($cursor,"select stafftype,user_serial from user_details where username='$myuname' and is_current='Y'");
	ora_exec($cursor);
	if (ora_fetch($cursor))
	{
		$stafftype=getdata($cursor,0);
		$myuserial=getdata($cursor,1);
	}

    	ora_parse( $cursor, "SELECT to_char( A.reporteddate, 'DD/MM/YYYY HH24:MI:SS' ), A.reportedwho, A.faultdesc, A.faultpicture, C.description, A.jobcardgeneral FROM MOVE_JOBCARDITEMS A, MOVE_FAULTCLASS C WHERE A.itemserial=$itemserial AND A.faultclass=C.serial" );
    	ora_exec( $cursor );
    	$reportdate = ora_getColumn( $cursor, 0 );
    	$reportwho  = ora_getColumn( $cursor, 1 );
    	$faultdesc  = ora_getColumn( $cursor, 2 );
    	$faultclass = ora_getColumn( $cursor, 4 );
	$jserial = ora_getColumn($cursor,5);

	$fixed_time=0;
	if ($jserial!="") {
		ora_parse($cursor,"select esttime from move_jobgeneral where serial=$jserial");
		ora_exec($cursor);
		if (ora_fetch($cursor)) {
			$jtime=getdata($cursor,0);
			if ($jtime>0)
				$fixed_time=$jtime;
		}
	}
    	
    	if( $tfirst!="N" )
    		print "<b>You are clearing this fault</b><br>";
    	else print "<b>You are now adding additional mechanics to this fault</b><br>";
	echo "<font color=magenta>$faultdesc</font><br>";
    	?>
    	<form method=post id=clear name=clear>
    	<input type=hidden name=stage value=2>
	<input type=hidden name=jcno value=<?=$jcno?>
    	<input type=hidden name=first value="<?=$tfirst?>">
    	<input type=hidden name=itemserial value="<?=$itemserial?>">
    	<table width=100%>
	<script>
		var list_outstanding=new Array();
	</script>
    		<tr class=head><td colspan=2><?=$faultclass?>: <?=$reportwho?> @ <?=$reportdate?></td></tr>
    		
    		<?

		$clickscript="";
		if ($tfirst!="N")
			$addin = "document.getElementById('completed').checked && ";
		else
			$addin = "";

		$onclick=" if ($addin parseFloat(document.getElementById('minutes').value)==0 && parseFloat(document.getElementById('hours').value)==0) { alert('Please put a time in for work done'); return false; } ";
    		
    		if( $tfirst!="N" || $resubmit ) {
    			?>
	    		<tr class=cell>
	    			<td width=100>Job Is</td>
	    			<td>
	    				<input type=radio id=completed  name=status value="C">Completed (Done/Fixed)
	    				<input type=radio id=block1 name=status value="I">Incomplete, Delay to next jobcard
					<div id=blockme>
	    				<input type=radio id=block2 name=status value="X">No fault Found
					</div>
	    			</td>
	    		</tr>
	    		<?
				$onclick .= "if(!( document.getElementById('completed').checked || document.getElementById('block1').checked ||document.getElementById('block2').checked ) ) { alert( 'You must select an option for the fault' );  return false; } else {  console.log('looping'); for (var i in list_outstanding) {
 console.log(i+': '+list_outstanding[i]);  console.log(document.getElementById('radio1'+list_outstanding[i]));  if ( (list_outstanding[i]>0) && !(document.getElementById('radio1'+list_outstanding[i]).checked || document.getElementById('radio2'+list_outstanding[i]).checked || document.getElementById('radio3'+list_outstanding[i]).checked) ) { alert( 'You must answer YES or NO' );  return false; } } } ";
	    	}
		else echo "<input type=hidden name=status value='$status'>";

		 if ($stafftype!="M" || $joballow)
	    	$onclick .= "if( document.getElementById('mechanic').selectedIndex<2 ) { alert( 'Please select a mechanic' ); return; } document.getElementById('clear').submit();";
		else
			$onclick.="  document.getElementById('clear').submit();";

	    	?>
    		
    		<tr class=cell>
    			<td width=100>Mechanic</td>
    			<td>

    				<select id=mechanic name=mechanic>
<?  if ($stafftype!="M" || $joballow) { ?>
    				<option>Select a Mechanic
    				<option>-------------------------------
    				<?
				
    				ora_parse( $cursor, "SELECT serial, surname, name FROM MOVE_MECHANICS WHERE active='Y' ORDER BY surname, name " );
				} else ora_parse( $cursor, "SELECT user_serial, lastname, name FROM user_details WHERE is_current='Y' and username='$myuname' " );

    				ora_exec( $cursor );
    				$mechanic_list = array();
    				while( ora_fetch( $cursor ) ) {
    					$serial = ora_getColumn( $cursor, 0 );
    					$lname  = trim( ora_getColumn( $cursor, 1 ) );
    					$fname  = trim( ora_getColumn( $cursor, 2 ) );
    					$mechanic_list[$serial] = "$lname, $fname";
    					
    					print "<option value=$serial>$lname, $fname</option>";
    				}
    				?>
    				</select>
    			</td>
    		</tr>
<?
	if ($fixed_time>0) {
		$defm=$fixed_time%60;
		$defh=floor($fixed_time/60);
		if ($tfirst!="N") {
			$ro="readonly";
			$jnotes=" <i><font color=magenta>You cannot change this time.  Add extra time in seperately using the \"more mechanics\" tick box at the bottom</font><i>";
		}
	} else {
		$defm=0;
		$defh=0;
		$ro="";
		$jnotes="";
	}

?>
    		
    		<tr class=cell>
    			<td width=100>Time Spent</td>
    			<td>
					<input type="text" id=hours name="hours" <?=$ro?> size=5 maxlength=5 value=<?=$defh?>>HH
					<input type="text" id=minutes name="minutes" <?=$ro?> size=2 maxlength=2 value=<?=$defm?>>MI
				<?=$jnotes?>
				</td>
			</tr>
		<tr class=cell valign=top>
		<td colspan=2>
<?
	
		if ($jserial>0) {
			$list_outstanding=0;
			$kcur=$cursor;	
			// first get existing entires for this mechanic
			 if (!($stafftype!="M" || $joballow)) {

			ora_parse($kcur,"select A.serial, B.done from move_jobgeneral_items A, move_jobgeneral_done B where A.serial=B.serial and B.jobcarditem='$itemserial' and B.mechanic=$myuserial and A.general_serial=$jserial and A.in_use='Y' ");
                        ora_exec($kcur);
			while (ora_fetch($kcur)) {
				$mechdone[getdata($kcur,0)]=getdata($kcur,1);
			}

			}

			if ($tfirst!="N") {
                        ora_parse($kcur,"select A.instructions,A.serial,B.done from move_jobgeneral_items A left join move_jobgeneral_done B on A.serial=B.serial and B.jobcarditem='$itemserial' where A.general_serial=$jserial and A.in_use='Y' order by A.display_order,A.serial,B.done desc");
                        ora_exec($kcur);
                        $cbfound=0;
			$cbseen=array();
                        while (ora_fetch($kcur))
                        {
	
				$cbsrl=getdata($kcur,1);
				$done=getdata($kcur,2);
				if ($done=="R")
				{
					$cbseen[$cbsrl]=true;
					continue;

				}
				if (!isset($cbseen[$cbsrl]) || !$cbseen[$cbsrl]) {
					$cbseen[$cbsrl]=true;
				if ($done=="R")
					$donex="(Queued for repair)";
				else if ($done=="Y" )
					$donex="(Already Done)";
				else
					$donex="";
				if (isset($mechdone[$cbsrl]) && $mechdone[$cbsrl]=="Y")
					$doney=" CHECKED";
				else
					$doney=""; 
				if (isset($mechdone[$cbsrl]) && $mechdone[$cbsrl]=="N")
					$donen=" CHECKED";
				else
					$donen="";
			
				
                                if ($cbfound==0)
				{
                                        echo "<table border=1 cellpadding=0 cellspacing=0><Tr class=head><td><b>Instruction</td><td><center>Notes</center></td><td width=40><B>YES</td><td width=40><b>NO.</td><td>Queue for Repair</td></tr>";
					echo "<script> document.getElementById('block2').disabled=true; document.getElementById('blockme').style.display='none'; </script>\n";
				}
				$rowno++;
				$list_outstanding++;
				$klik=" onclick=\"list_outstanding[$list_outstanding]=0; document.getElementById('myrow$rowno').style.display='none';\" ";
				$clickscript.="list_outstanding[$list_outstanding]=$cbsrl;\n";
                                echo "<tr class=cell id=myrow$rowno><Td>".getdata($kcur,0)."</td><td><input name=rnotes$cbsrl size=10 maxlength=30></td><td><input type=radio id=radio1$cbsrl name=cb$cbsrl value=Y $doney $klik></td><td><input type=radio name=cb$cbsrl id=radio2$cbsrl value=N $donen  \"  ></td><td><input type=radio name=cb$cbsrl id=radio3$cbsrl value=R  \"  ></td><td>$donex</td></tr><input type=hidden name=cbneed$cbsrl value=Y>";
				
                                $cbfound++;
				} // cbseen
                        }
			if ($cbfound>0)
    	                    echo "</table>";
			} // tfirst
			
		}
		echo "\n<script> $clickscript </script>\n";

?>
		</td></tr>
    		<tr class="cell" valign='top'>
    			<td>Mechanic Notes</td>
				<td><textarea name="notes" rows=3 cols=60 onFocus="checkLen(this, 2000);" onKeydown="checkLen(this, 2000);" onKeyup="checkLen(this, 2000);" onKeyPress="checkLen(this, 2000);" onBlur="checkLen(this, 2000);" onChange="checkLen(this, 2000);" onClick="checkLen(this, 2000);"></textarea></td>
    		</tr>
    	</table>
<?
	if ($stafftype!="M" || $joballow) 
	 	echo "Click here if there are more mechanics or <b>extra time</b>: <input type=checkbox value=Y name=addmore>";
	?>
	<br>
    	<input type=button value="Update" onClick="<?=$onclick?>">
    	</form>
    	<?

    	if( $tfirst=="N" ) {
    		$flag = true;

		if ($status != "I") {
			echo "<a href=clearitem.phtml?itemserial=$itemserial&jcno=$jcno&reset=Y&stage=1>Change this item to INCOMPLETE</a>";
		} 
		else echo "<font color=red>Item has been marked as INCOMPLETE</font><bR>";

	    	?>
	    	<hr>
    		<table width=100%><form method=post>
	    		<tr class="title"><td colspan=3>Previous Work on this Fault</td></tr>
	    		<tr class="head"><td>Mechanic</td><td>Time</td><td>Comments</td></tr>
	    		<input type='hidden' name='item_serial' value='<?=$itemserial?>'>
	    		<?
	    		$results = array();
	    		$statement = "SELECT A.minutes, A.mechanicnotes, B.name, B.surname, A.completedwho FROM MOVE_JOBCARDITEMS A, MOVE_MECHANICS B WHERE A.completedwho=B.serial AND A.itemserial=$itemserial";
	    		ora_parse( $cursor, $statement );
	    		ora_exec( $cursor );
	    		$count = 0;	    		
	    		while( ora_fetch_into( $cursor, $results ) ) {
	    			$count++;
	    			if( $flag ) print "<tr class=\"cell\">";
	    			else print "<tr class=\"altcell\">";

	    			$temphours = (int)($results[0]/60);
	    			$tempmin   = $results[0]%60; if( $tempmin<10 ) $tempmin = "0" . $tempmin;
	    			?>
	    				<td width=150>
	    				
	    				<select name='mechanic_<?=$count?>'<?php if ($completed == "Y"){echo "disabled";} ?>>
	    				<?
						while (list($key, $val) = each($mechanic_list)){
						
							if ($key == $results[4]){$selected = "selected";}else{$selected = "";}
							echo "<option value='$key' $selected>$val</option>";
							
						}
						
	    				?>
	    				</select>	    				
	    				
	    				
	    				</td>
	    				<td width='150' nowrap><input type='text' value='<?=$temphours?>' name='hours_<?=$count?>' maxlength='2' size='2' <?php if ($completed == "Y"){echo "readonly";} ?>>h<input type='text' value='<?=$tempmin?>' name='mins_<?=$count?>' maxlength='2' size='2' <?php if ($completed == "Y"){echo "readonly";} ?>>min</td>
	    				<td><input type='text' name='comments_<?=$count?>' size='45' value='<?=$results[1]?>' <?php if ($completed == "Y"){echo "readonly";} ?>></td>
	    			<?
	    			$flag = !$flag;
	    			$results = array();
	    		}

	    		$results = array();
	    		$statement = "SELECT A.minutes, A.mechanicnotes, B.name, B.surname, A.mechanic FROM MOVE_ITEMEXTRA A, MOVE_MECHANICS B WHERE A.mechanic=B.serial AND A.itemserial=$itemserial";
	    		ora_parse( $cursor, $statement );
	    		ora_exec( $cursor );
	    		
	    		while( ora_fetch_into( $cursor, $results ) ) {
				$count++;
	    			if( $flag ) print "<tr class=\"cell\">";
	    			else print "<tr class=\"altcell\">";

	    			$temphours = (int)($results[0]/60);
	    			$tempmin   = $results[0]%60; if( $tempmin<10 ) $tempmin = "0" . $tempmin;
	    			?>
	    				<td width=150>
	    				<select name='mechanic_<?=$count?>' <?php if ($completed == "Y"){echo "disabled";} ?>>
	    				<?
						reset($mechanic_list);
						while (list($key, $val) = each($mechanic_list)){
						
							if ($key == $results[4]){$selected = "selected";}else{$selected = "";}
							echo "<option value='$key' $selected>$val</option>\n";
							
						}
						
	    				?>
	    				</select>
	    				</td>
	    				<td width='150' nowrap>
	    				<input type='text' value='<?=$temphours?>' name='hours_<?=$count?>' maxlength='2' size='2' <?php if ($completed == "Y"){echo "readonly";} ?>>h<input type='text' value='<?=$tempmin?>' name='mins_<?=$count?>' maxlength='2' size='2' <?php if ($completed == "Y"){echo "readonly";} ?>>min</td>
	    				<td><input type='text' name='comments_<?=$count?>' size='45' value='<?=$results[1]?>' <?php if ($completed == "Y"){echo "readonly";} ?>></td>
	    			<?
	    			$flag = !$flag;
	    			$results = array();
	    		}
	    		?>
    		</table><p><input type='hidden' name='max_count' value='<?=$count?>'><?php if ($completed != "Y"){echo "<input type='submit' name='update_hrs' value='Update Hours / Mechanic / Comments'>";} ?></form>
    		<?
    	}
	}
	elseif( $stage==2 ) {
		if (!is_numeric($itemserial)) {
			echo "ERROR 1!";
			exit;
		}
		reset($_POST);
		foreach ($_POST as $key=>$val) {
			if (substr($key,0,6)=="cbneed") {
				$k = substr($key,6);
				if (!isset($_POST["cb$k"])) {
					echo "<font color=red>SORRY, you MUST select YES, NO or REPAIR for ALL items</font><p>Please go back and fix it (press your back button)";
					exit;
				}

			}
		}
		if ($status=="") {
			echo "<font color=red>SORRY, you did not select a status!</font> <p>Please go back and fix it (press your back button)";
                        exit;

		}
                if (!is_numeric($mechanic)) {
                        echo "<font color=red>SORRY, you did not select a mechanic!</font> <p>Please go back and fix it (press your back button)";
                        exit;
                }

		ora_parse( $cursor, "SELECT completedwho, statusengineer, deferred_to , nvl(minutes,0) FROM move_jobcarditems WHERE itemserial=$itemserial");
		ora_exeC( $cursor);
		if (!ora_fetch( $cursor)) {
			echo "ERROR 2!";
			exit;
		}
		if ( strlen(ora_getColumn( $cursor, 0))==0 ||  (string)ora_getColumn( $cursor, 0)=="-1")
			$first="Y";
		else {
			echo "because complete is (".getdata($cursor,0).") first is N<bR>";
			$first="N";
		}
		if (getdata($cursor,1)=="I" && getdata($cursor,2)=="" && getdata($cursor,3)==0) {
			$first = "Y";
		}
		$oldstatus = getdata($cursor,1);
		if ($status!= "" && $status != $oldstatus && $first == "N" && $oldstatus=="I") {
			ora_parse($cursor,"update move_jobcarditems set statusengineer='$status' where itemserial=$itemserial");
			ora_Exec($cursor);
		}
//		THIS DIDNT WORK PROPERLY...
//		if( empty( $first ) ) $first="Y";

		$logfile=fopen("/tmp/jobgeneral.log","a+");
		$logline="ITEM: $itemserial: ";
		reset($_POST);
		while (list($key,$val)=each($_POST))
			$logline.="$key=$val, ";
		$logline.="\n";
		fputs($logfile,$logline);
		fclose($logfile);

	// do checkboxes...
			$queue_redo=array();
			$queue_repair=array();
			$kcur=$cursor;
                        ora_parse($kcur,"select A.serial,B.done from move_jobcarditems C, move_jobgeneral_items A left join move_jobgeneral_done B on A.serial=B.serial and B.jobcarditem='$itemserial' and B.mechanic=$mechanic where A.general_serial=C.jobcardgeneral and C.itemserial='$itemserial' and A.in_use='Y' order by A.display_order,A.serial");
                        ora_exec($kcur);
                        $cbfound=0;
                        while (ora_fetch($kcur))
                        {
                                $cbsrl=getdata($kcur,0);
                                $done=getdata($kcur,1);
		
//				echo "$cbsrl is currently $done<bR>";	
				$rnotes=$_POST["rnotes$cbsrl"];
				$rnotes=str_replace("'","",$rnotes);
				$rnotes=substr($rnotes,0,30);
				if ($_POST["cb$cbsrl"]=="R") {
                                        $queue_repair[$cbsrl]=true;
				//	$_POST["cb$cbsrl"]="Y"; //mark item as done

                                }  // Queue for repair
				if ($done=="" && strlen($_POST["cb$cbsrl"])==1) {
					$qry="insert into move_jobgeneral_done values ('$itemserial','$cbsrl','". $_POST["cb$cbsrl"]."',$mechanic,'$rnotes')";
//					echo "$qry<Br>";
                                        ora_parse($cursor,$qry);
                                        ora_exec($cursor);

				} elseif ($done!="" &&  strlen($_POST["cb$cbsrl"])==1 ) {  //update
					$qry="update move_jobgeneral_done set done='".$_POST["cb$cbsrl"]."', mechanic=$mechanic where jobcarditem='$itemserial' and serial=$cbsrl";
//					echo "$qry<Br>";
					ora_parse($cursor,$qry);
					ora_exec($cursor);
					if ($rnotes!="")  {
						$qry="update move_jobgeneral_done set notes='$rnotes' where jobcarditem='$itemserial' and serial=$cbsrl";
//	                                        echo "$qry<Br>";
 	                                        ora_parse($cursor,$qry);
       		                                ora_exec($cursor);
					}

				}
				if ($_POST["cb$cbsrl"]=="N") {
					$queue_redo[$cbsrl]=true;

				}  // No answer
				
                        }

//cb$cbsrl	

	if (!empty($queue_redo)) {
		// is it a service?

	  ora_parse($kcur,"select jobcard from move_servicelog where jobcard='$jcno'");
	  ora_exec($kcur);
	  if (ora_fetch($kcur)) {

		//  Add Ad-Hoc fault	
		reset($queue_redo);
		while (list($cbsrl,$true)=each($queue_redo)) {
			ora_parse($kcur,"select A.instructions from move_jobgeneral_items A where A.serial=$cbsrl");
                        ora_exec($kcur);
			ora_fetch($kcur);
			$instruction=getdata($kcur,0);
	
			$qry="insert into move_jobcarditems (itemserial, jobcardserial, faultclass, faultvalid, faultdesc, statusengineer, reporteddate, reportedwho, reportedonbehalf, reportcomments, unitserial, type, fromitem) select move_items.nextval, 0, faultclass, 'N', 'NOT DONE SERVICE item: $instruction - carried over from previous jobcard (Originally from '||trim(faultdesc)||')','Z', reporteddate, reportedwho, reportedonbehalf, reportcomments, unitserial, type, $itemserial from move_jobcarditems where itemserial=$itemserial"; // jobcardgeneral removed as per Stephan 28 Sep 2020 - as it creates full checklists on the new jobcard
			echo "$qry<br>";
			ora_parse($cursor,$qry);
			ora_exec($cursor);
		} // while
	  } // service

	} // redo checkbox items

        if (!empty($queue_repair)) {


		ora_parse($cursor,"select username from user_Details where user_serial=$mechanic");
		ora_Exec($cursor);
		if (ora_fetch($cursor))
			$username = getdata($cursor,0);
		else
			$username =getenv("REMOTE_USER");

                //  Add Ad-Hoc fault
                reset($queue_repair);
                while (list($cbsrl,$true)=each($queue_repair)) {
                        ora_parse($kcur,"select A.instructions from move_jobgeneral_items A where A.serial=$cbsrl");
                        ora_exec($kcur);
                        ora_fetch($kcur);
                        $instruction=getdata($kcur,0);

                        $qry="insert into move_jobcarditems (itemserial, jobcardserial, faultclass, faultvalid, faultdesc, statusengineer, reporteddate, reportedwho, reportedonbehalf, reportcomments, unitserial, type, fromitem, fault_category) select move_items.nextval, jobcardserial, faultclass, 'N', 'REPAIR REQUESTED Resulting from item: $instruction. Mech notes: $rnotes','Z', CURRENT_TIMESTAMP, '$username', '$username', null, unitserial, type, itemserial, fault_category from move_jobcarditems where itemserial=$itemserial"; 
                        ora_parse($cursor,$qry);
                        ora_exec($cursor);
                } // while


        } // queue for repair  checkbox items

	
		
    	$olditemserial = $itemserial;
	if (!is_numeric($hours))
		$hours=0;
	if (!is_numeric($minutes))
		$minutes=0;
    	$minutes = $hours*60+$minutes;
    	$readd = false;
    	$completetime = date( "dmY His" );
    	$notes = str_replace( "'", "", $notes );
	
    	
		if( $first=="Y" ) {
	    	switch( $status ) {
	    		case "X": 
	    					$statement = "UPDATE MOVE_JOBCARDITEMS SET completedate=to_date( '$completetime', 'DDMMYYYY HH24MISS' ), faultvalid='Y', statusengineer='C', mechanicnotes='$notes', completedwho='$mechanic', minutes=$minutes, mechanic_date=CURRENT_TIMESTAMP  WHERE itemserial=$itemserial";
	    					break;
	    		case "C":
	    					$statement = "UPDATE MOVE_JOBCARDITEMS SET completedate=to_date( '$completetime', 'DDMMYYYY HH24MISS' ), statusengineer='C', mechanicnotes='$notes', completedwho='$mechanic', minutes=$minutes, mechanic_date=CURRENT_TIMESTAMP  WHERE itemserial=$itemserial";
	    					break;
	    		case "I":
	    		case "P":
	    		case "D":
	    					$readd = true;
	    					$statement = "UPDATE MOVE_JOBCARDITEMS SET completedate=to_date( '$completetime', 'DDMMYYYY HH24MISS' ), statusengineer='$status', mechanicnotes='$notes', completedwho='$mechanic', minutes=$minutes, mechanic_date=CURRENT_TIMESTAMP   WHERE itemserial=$itemserial";
	    					break;
			case "S":		
					  $statement = "UPDATE MOVE_JOBCARDITEMS SET statusengineer='C', mechanicnotes='$notes', completedwho='$mechanic', minutes=$minutes, mechanic_date=CURRENT_TIMESTAMP  WHERE itemserial=$itemserial";
                                                break;

	    					
	    		default :	$statement = "Really, this shouldn't be possible ($status)"; break;
	    	}
	    	
	        ora_parse( $cursor, $statement );
	        if( !ora_exec( $cursor ) )
	        	showError( __FILE__, __LINE__, "$stage", "$statement" );
	    } elseif( $first=="N" ) {
			$statement = "INSERT INTO MOVE_ITEMEXTRA ( mechanic, itemserial, minutes, mechanicnotes, mechanic_date ) VALUES ( $mechanic, $itemserial, $minutes, '$notes', CURRENT_TIMESTAMP )";
			ora_parse( $cursor, $statement );
			ora_exec( $cursor );
	    }
		
		//event( $itemserial, 4 );
		
    	if( $readd && $first=="Y" ) {
    		/*
    		array( $results );
    		$results = array();
	    	ora_parse( $cursor, "SELECT faultclass, faultvalid, faultdesc, faultdescclass, faultpicture, to_char( reporteddate, 'DD/MM/YYYY HH24:MI:SS' ), reportedwho, reportedonbehalf, reportcomments, unitserial, mechanicnotes, servicetypeserial, urgent, type FROM MOVE_JOBCARDITEMS WHERE itemserial=$itemserial" );
	    	ora_exec( $cursor );
	    	ora_fetch_into( $cursor, $results );
    		
    	   	ora_parse( $cursor, "SELECT MOVE_ITEMS.nextval FROM dual" );
        	ora_exec( $cursor );
        	$itemserial = ora_getColumn( $cursor, 0 );
        	
	   	    if( $results[4]=="Y" ) {
	   	    	if( link( "/usr/local/www/pages/move/uploads/$oldserial", "/usr/local/www/pages/move/uploads/$itemserial" ) ) {
	   	    		chmod( "/usr/local/www/pages/move/uploads/$itemserial", 0666 );
	   	    	} else showError( __FILE__, __LINE__, "$stage Symlink", "Hard linking failed" );
	   	    }
    	    
			$results[2] = str_replace( "'", "''", $results[2] );
			$statement = "INSERT INTO MOVE_JOBCARDITEMS ( itemserial, jobcardserial, faultclass, faultvalid, faultdesc, faultdescclass, faultpicture, statusengineer, reporteddate, reportedwho, reportedonbehalf, reportcomments, unitserial, mechanicnotes, servicetypeserial, urgent, type, fromitem ) ";
			$statement .= "VALUES( $itemserial, 0, '$results[0]', '$results[1]', '$results[2]', '$results[3]', '$results[4]', 'Z', to_date( '$results[5]', 'DD/MM/YYYY HH24:MI:SS' ), '$results[6]', '$results[7]', '$results[8]', '$results[9]', '', '$results[11]', '$results[12]', '$results[13]', '$olditemserial' ) ";

    	    ora_parse( $cursor, $statement );
    	    if( !ora_exec( $cursor ) )
        		showError( __FILE__, __LINE__, "$stage", "$statement" );
        	*/
	   	}
	   	
	   	$first = "N";

/*		if( $status=="X" ) {
			?> 	<script language="JavaScript"> window.close(); </script> <?
			exit;
		}
*/

		//print "<hr>$statement<br>";


		if ($addmore) {
			mt_srand ((double) microtime() * 1000000);

			echo "<script>window.location='clearitem.phtml?itemserial=$itemserial&rnd=".mt_rand()."&jcno=$jcno&stage=1';</script>";
			exit;
		}

		?>
<script>
try { opener.location='viewjobcard.phtml?jobcardserial=<? mt_srand ((double) microtime() * 1000000);
  $rnd=mt_rand(); echo "$jcno&random=$rnd"; ?> '; window.close();  } catch (error) {}
</script>
		<form name="temp" method="post">
			<input type="hidden" name="tfirst" value="<?=$first?>">
			<input type="hidden" name="jcno" value="<?=$jcno?>">
			<input type="hidden" name="stage" value="1">
			<input type="hidden" name="itemserial" value="<?=$itemserial?>">
			<input type="hidden" name="rnd" value="<?=$rnd?>">
		</form>
		<script language="JavaScript"> temp.submit() </script>
		<?
	}
	?>
	</body>
</html>

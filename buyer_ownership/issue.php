<html>
	<head><link type="text/css" rel="stylesheet" href="style.css"><title>PARTS REQUEST</title><head>
	
	<body>
	<?

	$accepted_count = array();
	$accepted_days = array();
	$newadded = false;
	$check_empty = false;
	$line = 0;
    require_once("../php3/oracle.inc");
	VV("req");

   if( !open_oracle() )
        exit;

	$wildcard_depots = array();
	
    VV("shownotes");


    if (!isset($_GET['printslip']) && $shownotes!="Y")
		require_once ( "menu.inc" ); ?>

	<?
   if ($_SERVER['SERVER_NAME'] == "192.168.10.239") 
		$test_system=true;
	else
		$test_system=false;

	VV("newm");
	VV("newi");
	VV("newnotes");
	VV("newd");
	VV("newq");
	VV("psjobcard");
	

    require_once("serial.inc");
    require_once("error.inc");
    require_once("stock.inc"); 
    require_once("../php3/sec.inc");
    require_once("../php3/misc.inc"); 
	$rights=array();
	$rights["STOREMAN"]=AllowedFlag("MOVE_STOCK");
	$rights["SUPERVISOR"]=AllowedFlag("PARTS_REQ_AUTH");
	$rights["REVOKE"]=AllowedFlag("STK_CREATE_I");
//	if ($rights["SUPERVISOR"])
//		echo "OK!!";
	VV("jobcardserial");
	$jobcardserial=trim($jobcardserial);

	$depot=$cookiedepot;
	if ($depot=="")
	{
		echo "Please <a href='https://secure.intercape.co.za/move/changedepot.phtml'>Set up your depot first - click here</a>";
		exit;
	}



	// get related depots
	$depots[]="'".$depot."'";
	if ($depot=="HAR")
		$depots[]="'PFH'";
	elseif ($depot=="PFH")
		$depots[]="'HAR'";

	ora_parse($cursor,"select depot_code, physical_depot from depots where physical_depot='$depot' or physical_depot='***'");
	ora_exec($cursor);
	$depot_ok[$depot]=true;
	while (ora_fetch($cursor))
	{
		$depots[]="'".getdata($cursor,0)."'";
		$depot_ok[getdata($cursor,0)]=true;
		if (getdata($cursor,1) == "***")
			$wildcard_depots[getdata($cursor,0)] = true;
	}
	//print_r($depots);
	$depot_list=implode(",",$depots);
//	echo "Depot List: $depot_list<Br>";

 	 $oldpart_options=array();
         $oldpart_options["W"]="Old Part being sent to PTA (PWA)";
         $oldpart_options["S"]="Old Part stolen -  Requires Authoritzation";
         $oldpart_options["D"]="Old Part damaged in accident (not due to failure). Requires Auth";

	$oldpart_disp=array();
	$oldpart_disp["W"]="Old Part being sent to PTA (PWA)";
         $oldpart_disp["S"]="Old Part <font color=red><b>stolen</b></font> -  Requires Authoritzation";
         $oldpart_disp["D"]="Old Part <font color=red><b>damaged in accident</b></font> (not due to failure). Requires Auth";

	if (!isset($master))
		$master = "";

	if ($master!="" && isset($req) && is_numeric($req) && is_numeric($jobcardserial)) {
		$master=str_replace("'","",$master);
		ora_parse($cursor,"select email from user_details where username='$master' order by is_current desc");
		ora_exec($cursor);
		$email="";
		if (ora_fetch($cursor)) {
			$email=getdata($cursor,0);
		}
		if (!strstr($email,"@")) 
			$email=$master."@cavmail.co.za";
		mail($email,"Please approve Picking Slip $ps",getenv("REMOTE_USER")." requests that you approve picking slip PS$ps at this page: https://secure.intercape.co.za/move/partsrequest.phtml?jobcardserial=$jobcardserial&req=$req");
		echo "Emailed link to $email<hR> requests that you approve picking slip PS$ps at this page: https://secure.intercape.co.za/move/partsrequest.phtml?jobcardserial=$jobcardserial&req=$req";

	}



	if (isset($req) && is_numeric($req) && isset($newjc) && is_numeric($newjc) && isset($unhang)) {
		ora_parse($cursor,"update move_jobs_part_requests set jobcardserial=$newjc where jobcardserial<0 and pr_serial=$req");
		if (ora_exec($cursor))
			if (ora_numrows($cursor)==1) {
				$us=getuserserial();
	                        $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'Change Jobcard from $jobcardserial to $newjc')";
      		                ora_parse($cursor,$qry);
                 		ora_exec($cursor);

				$jobcardserial=$newjc;
			}
	}
	if (isset($req) && is_numeric($req) && isset($doremove) && is_numeric($jobcardserial) && $jobcardserial>0 && $rights["STOREMAN"]) {
		ora_parse($cursor,"update move_jobs_part_requests set jobcardserial=-$jobcardserial where jobcardserial=$jobcardserial and pr_serial=$req");
                if (ora_exec($cursor))
                        if (ora_numrows($cursor)==1) {
                                $us=getuserserial();
				ora_parse($cursor,"update stk_picking_slip set jobcardserial=$jobcardserial where pr_number=$req and jobcardserial is null and finalized_date is not null");
				ora_exec($cursor);
                                $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'Removed from Jobcard $jobcardserial')";
                                ora_parse($cursor,$qry);
                                ora_exec($cursor);

                                $jobcardserial=$jobcardserial*-1;
                        }
	
		
	}

	if (is_numeric($jobcardserial) && $req=="NEW") {
		ora_parse($cursor,"Select depot from move_jobs where jobcardserial=$jobcardserial");
		ora_exec($cursor);
		if (ora_fetch($cursor)) {
			$checkdepot=getdata($cursor,0);
			if ($checkepot!="OPS" && !$depot_ok[$checkdepot]) {
				echo "<hr><font color=red><b>Sorry, this jobcard is for $checkdepot and you are in $depot";
				exit;
			}
		}

	}
	
	if (!isset($_GET["printslip"]) && $shownotes!="Y")
		echo "<b>".str_replace("'","",$depot_list)."</b> | <a href=partsrequest.phtml?mode=U>Approve</a> | <a href=partsrequest.phtml?mode=I>Issue Stock</a> | <a href=partsrequest.phtml?mode=O>On Order</a> | <a href=partsrequest.phtml?mode=R>Receive</a> | <a href=partsrequest.phtml?mode=H>Hanging</a> | <a href=partsrequest.phtml?mode=L>Show All</a> | <a target=parent href='https://secure.intercape.co.za/ignite/index.php?c=reports&m=vmechanic_report&page_id=701&depot=$cookiedepot'>Dashboard</a><BR>";

	ora_parse($cursor,"alter session set nls_date_format='hh24:mi Dy dd Mon YYYY'");
	ora_exec($cursor);

	if( !AllowedAccess( "" ) )
		exit;

	if (isset($mailto) && $mailto!="" && is_numeric($req)) {
		$from=getenv("REMOTE_USER");
		mail($mailto,"From $from: Parts Request PR$req","  $from has sent you this link to parts request PR$req:\n  https://secure.intercape.co.za/move/partsrequest.phtml?jobcardserial=$jobcardserial&req=$req");
			echo " $from has sent you this link to parts request PR$req:\n  https://secure.intercape.co.za/move/partsrequest.phtml?jobcardserial=$jobcardserial&req=$req";
		echo "<font style='background:yellow'>Email sent to $mailto</font><hr>";
			$us=getuserserial();
		        $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'Emailed to $mailto')";
                        ora_parse($cursor,$qry);
                        ora_exec($cursor);


	}

	$noteline = 0;
	if ($shownotes=="Y" && is_numeric($req)) {
		echo "<table border=1 cellspacing=0 cellpadding=0>";
		echo "<tr class=head><td>Notes</td><td>Who</td><td>When</td></tr>";
                        ora_parse($cursor,"select A.username,to_char(B.date_logged,'HH24:MI DD Mon YY') , notes from  move_jobs_part_request_notes B, user_details A  where A.user_serial=B.logged_by and B.pr_serial=$req order by B.date_logged");
                        ora_exec($cursor);
                        while (ora_fetch_into($cursor,$notes)) {
                                $noteline++;
                                if ($noteline%2==0)
                                        echo "<Tr class=cell>";
                                else
                                        echo "<Tr class=altcell>";
                                echo "<Td>$notes[2]</td><td>$notes[0]</td><td>$notes[1]</td></tr>";
                                unset($notes);
                        }


		echo "</table>";
		echo "<br>To add a note, use the table at the bottom of the left hand panel";
		exit;

	} // show notes

///////////////////////////////

	function current_location($track) {
		global $conn, $locursor,$thisvehicle, $comparep;

		if (!isset($locursor))
			$locursor = ora_open($conn);
		$cursor=$locursor;

		if ($track=="")
			return "";
		$track=str_replace("'","",$track);
		ora_parse($cursor,"select serial from stk_serialass where track='$track'");
		ora_exec($cursor);
		if (!ora_fetch($cursor))
			return "<font color=red>No such ICG</font>";
		$serial=getdata($cursor,0);
		ora_parse($cursor, "select lcode, location, ipartno from stk_serialtrack where serial=$serial");
		ora_exec($cursor);
		if (!ora_fetch($cursor))
                        return "<font color=red>No such ICG</font>";
		$comparep=getdata($cursor,2);
		$lcode=getdata($cursor,0);
		$location=getdata($cursor,1);
		if ($lcode==4) {
			ora_parse($cursor,"select unitserial from move_jobs where jobcardserial='$location'");
			ora_exec($cursor);
			if (ora_fetch($cursor)) {
				$lcode=1;
				$location=getdata($cursor,0);
			}
		}
		switch ($lcode) {
			case 1: // vehicle
				ora_parse($cursor,"select code from vehicles where serial='$location'");
				ora_exec($cursor);
				if (ora_fetch($cursor))
				{
					$v=getdata($cursor,0);
					if ($v!=$thisvehicle)
						return "<font color=red>$v</font>";
					else
						return $v;
				}
				else
					return "Unknown vehcile $location";
				break;
			case 2: return $location;
				break;
			case 4: // jobcard
					return "MOVE$location";
				break;
			case 8: // inter depot
					return "Tfr to $location";
					break;
			case 10: // scrap
				return "Scrap: $location";
				break;
			default: return "$lcode: $location";
		} // switch

	}

	function create_entry($jobcardserial) {
		global $cursor, $rights, $test_system;

		// create and return the sequence number - or NEW if there was an error
		$text=trim($_POST["request"]);
		$text=str_replace("'","",$text);
		if ($text=="") {
			echo "<font color=red>ERROR!  Blank request not allowed</font>";
			return "NEW";
		}
		$us=getuserserial();
		ora_parse($cursor,"select move_jobs_part_request_serial.nextval from dual");
		ora_exec($cursor);
		ora_fetch($cursor);
		$req=getdata($cursor,0);
		$qry="insert into move_jobs_part_requests (pr_serial, jobcardserial, request_text, captured_by, capture_date, approved) values ($req, $jobcardserial, '$text', '$us', CURRENT_TIMESTAMP, 'U')";
//		echo "$qry<bR>";
		if (ora_parse($cursor,$qry))
			if (ora_exec($cursor))
			{
				// send notifications:
				$myu=getenv("REMOTE_USER");
				ora_parse($cursor,"select branch from user_details where username='$myu' order by is_current desc");
				ora_exec($cursor);
				if (!ora_fetch($cursor)) {
					echo "<font color=red>I cannot email your foreman as I dont know what branch you are in!</font><br>";	
				} else {
					$mybranch=getdata($cursor,0);
					$foremen=0;
/*
					echo "<font color=magenta><b>Emailing foremen in $mybranch:</b> ";
					ora_parse($cursor,"select A.username, A.email from user_details A, user_pages B where A.user_serial=B.user_serial and B.page_name='PARTS_REQ_AUTH' and A.is_current in ('Y','L') and staff_member='Y' and A.branch='$mybranch'");
					ora_exec($cursor);
					$femails=array();
					while (ora_Fetch($cursor)) {
						$fun=getdata($cursor,0);
						$femail=getdata($cursor,1);	
						$foremen++;
						if ($femail=="")
							$femail=$fun."@cavmail.co.za";
						elseif (!strstr($femail,"@"))
							$femail.="@cavmail.co.za";
						echo "$fun ($femail) - ";
						if ($femail!="keith@intercape.co.za")
							$femails[]=$femail;
					}

					if ($foremen==0)
						echo "</font><font color=red>NONE!!!";
					else 
					{
						echo "email to ".implode(",",$femails)."<Br>";
						if ($test_system)		
						   echo "<b>TEST SYSTEM - not mailing</b><Br>";
						else
							mail (implode(",",$femails),"Parts Request PR$req",getenv("REMOTE_USER")." has just captured a parts request.  https://secure.intercape.co.za/move/partsrequest.phtml?jobcardserial=$jobcardserial&req=$req");
					}
					echo "</font>";
*/

				}

				// done with notifications!
				return $req;
			}
		return "NEW";	

		// return the sequence number.   return NEW if there was an error
	} // create_entry

////////////////////////////////

	function edit_entry($jobcardserial,$req) {
		global $cursor, $entry, $job, $rights;


		if (isset($_POST['closeoff'])) {
			ora_parse($cursor,"select 'x' from stk_picking_slip where pr_number=$req and received_date is null");
			ora_exec($cursor);
			if (ora_fetch($cursor)) {
				error_pop("Unable to close off - there are outstanding picking slips");
			} else {
				ora_parse($cursor,"update move_jobs_part_requests set received_date=CURRENT_TIMESTAMP, jobcardserial=abs(jobcardserial) where pr_serial=$req");
				ora_exec($cursor);
				  $us=getuserserial();
	                        $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'FINALIZED (Closed Off) ')";
       		                 ora_parse($cursor,$qry);
               		         ora_exec($cursor);


			}

		} // closeoff

		// form for edit/add entry

		echo "<form name=bigform action=partsrequest.phtml id=bigform method=post><input type=hidden name=req value='$req'><input type=hidden name=jobcardserial value='$jobcardserial'><input type=hidden name=req value='$req'>";

	        if (isset($_GET["printslip"]))
		{
			$printslip=$_GET["printslip"];
			ora_parse($cursor,"select jobcardserial from stk_picking_slip where ps_number=$printslip");
			ora_exec($cursor);
			ora_fetch($cursor);
			$psjobcardserial=getdata($cursor,0);
			echo "<i>This is part of PR$req (MOVE$jobcardserial: ".$job["CODE"]."), Captured by <B>".$entry["CAP_USER"]."</b>, approved by ".$entry["APP_USER"]."</i><br>";
			if ($psjobcardserial!="" && $psjobcardserial!=$jobcardserial)
				echo "<b>THIS PICKING SLIP IS FOR JOB CARD MOVE$psjobcardserial</b><bR>";
                        ob_start();

		}


		if ($req=="NEW")
			$entry["REQUEST_TEXT"]=$_POST["request"];
		else echo "<font size=+1><B><u>Parts Request PR$req</u></b></font> ";
		if ($req!="NEW") {
			$jobcardserial=$entry["JOBCARDSERIAL"]; // override the GET value
                        echo "<font size=2>Captured by <B>".$entry["CAP_USER"]."</b> on ".$entry["CAPTURE_DATE"]."</font><br>";
			if ($entry["APPROVED"]=="N") {
				echo "<font color=red><b>Rejected</b> by ".$entry["APP_USER"]." on ".$entry["APPROVED_DATE"]."</font> <font size=2>".$entry["APPROVED_COMMENTS"]."</font><br>";

			} elseif ($entry["APPROVED"]=="R") {
                                echo "<font color=red><b>REVOKED</b> by ".$entry["APP_USER"]." on ".$entry["APPROVED_DATE"]."</font><br>";
                        }
                        elseif ($entry["APPROVED"]=="Y")
                        {
                                echo "<font color=green><b>Approved</b> by ".$entry["APP_USER"]." on ".$entry["APPROVED_DATE"]."</font> <font size=2>".$entry["APPROVED_COMMENTS"]."</font>";
				if (($rights["SUPERVISOR"]||$rights["REVOKE"]) &&  $entry["RECEIVED_DATE"]=="") {
					ora_parse($cursor,"select 'x' from stk_picking_slip A, stk_picking_slip_contents B where A.ps_number=B.ps_number and  pr_number=$req");
					ora_Exec($cursor);
					if (!ora_fetch($cursor)) {
						 echo "<input type=submit name=submit value='Revoke' onclick=\"return confirm('Are you sure you want to revoke?  Click OK to revoke, Cancel to NOT revoke'); \" style='background:lightpink'> ";
					}
				}
				echo "<br>";
				if (isset($entry["ACCEPTED_USER"]) && $entry["ACCEPTED_USER"]!="") {
					echo "<font color=blue><b>Accepted</b> by ".$entry["ACCEPTED_USER"]." on ".$entry["ACCEPTED_DATE"]."</font> ";
				}
				if ($rights["STOREMAN"] &&  $entry["RECEIVED_DATE"]=="") 	
					echo "<input type=submit name=accept value='Accept / Take Ownership'>";
				echo "<br>";
				
                        } elseif ($entry["CAP_USER"]==getenv("REMOTE_USER"))
				echo "<a href='partsrequest.phtml?jobcardserial=$jobcardserial&req=$req&deleteme=Y'>[CLICK HERE TO DELETE]</a><br>";
			
//			else echo "Compare ".$entry["CAP_USER"]." to ".getenv("REMOTE_USER")."<Br>";
                }
		else echo "<BR>";

		if (!isset($entry["RECEIVED_DATE"]))
			$entry["RECEIVED_DATE"] = "";

		if ($rights["STOREMAN"] && $entry["RECEIVED_DATE"]!="") {
			echo "<a href=partsrequest.phtml?jobcardserial=$jobcardserial&req=$req&undofinal=Y>[UNDO FINALIZATION]</a><br>";
		}
		if ($entry["ON_ORDER_FLAG"]=="Y" && $entry["RECEIVED_DATE"]=="")
			echo "<b>FLAGGED AS \"PARTS ON ORDER\"</b> - See notes for more info<bR>";
   	  

		if (($rights["SUPERVISOR"] || chop($entry["CAP_USER"])==getenv("REMOTE_USER")||  chop($entry["CAP_USER"])=="") && ($req=="NEW" || $entry["APPROVED"]=="U"))
		{
			if ($entry["FINAL_TEXT"]!="")
				$entry["REQUEST_TEXT"]=$entry["FINAL_TEXT"];
			echo "<b>Enter your request below:</b> <font size=2><i>Please include ICG numbers of old parts being replaced</i></font><br><table><tr><td valign=top><u><b>Your Request:</b></u><br><textarea style='background:#99FF99' id=request name=request rows=10 cols=40 maxlength=500>".$entry["REQUEST_TEXT"]."</textarea></td>";	

			echo "<script> try { document.getElementById('request').focus(); } catch (error) { } </script>";
		}
		else
			echo "<table border=1><tr class=cell><td valign=top><u><b>Request as typed in:</b></u><br>".str_replace("\n","<br>",$entry["REQUEST_TEXT"])."</td>";

		echo "<td valign=top><u><b>Last parts added to ".$job["CODE"]."</b></u><bR>";
		ora_parse($cursor,"select B.description,to_char(A.when,'DD Mon') when,A.who,A.quantity from stk_movement A, stk_parts B where A.ipartno=B.serial and A.lcode=4 and A.when>CURRENT_TIMESTAMP-30 and A.quantity>0 and A.location in (select to_char(jobcardserial) from move_jobs where jobopendate>CURRENT_TIMESTAMP-90 and unitserial=".$job["UNITSERIAL"].") order by A.when desc");

		echo "<font size=2>";
		ora_Exec($cursor);
		unset($phistory);
		$phcount=0;
		while (ora_fetch_into($cursor,$phistory,ORA_FETCHINTO_ASSOC)) {
			$phcount++;
			echo $phistory["WHEN"].": ".$phistory["QUANTITY"]." x <font color=magenta>".$phistory["DESCRIPTION"]."</font> ".$phistory["WHO"]."<br>";

			unset($phistory);
			if ($phcount==20)
				break;
		} // while

		echo "</td></tr></table><br>";
		//print_r($entry);
		if ($req!="NEW") {
			if ($entry["APPROVED"]=="Y")
			{
				picking_slips($req,$entry["RECEIVED_DATE"]);
			}
		}

		$newline=0;
		if ($job["WARRANTY_EXPIRES"]!="") 
			if ($job["WARRANTY_EXPIRES"]>=date("Ymd"))
			{
				echo "<font color=red>".$job["CODE"]." Warranty ".$job["WARRANTY_EXPIRES"]."</font> ";
				$newline=1;
			}
		if ($job["DRIVELINE_WARRANTY_EXPIRES"]!="") 
                        if ($job["DRIVELINE_WARRANTY_EXPIRES"]>=date("Ymd"))
                        {
                                echo " <font color=magenta>".$job["CODE"]." Driveline Warranty ".$job["DRIVELINE_WARRANTY_EXPIRES"]."</font> ";
                                $newline=1;
                        }

		if ($job["BODY_WARRANTY_EXPIRES"]!="")
                        if ($job["BODY_WARRANTY_EXPIRES"]>=date("Ymd"))
                        {
                                echo " <font color=magenta>".$job["CODE"]." Driveline Warranty ".$job["BODY_WARRANTY_EXPIRES"]."</font> ";
                                $newline=1;
                        }

		echo "<Br>Vehicle km is ".$job["KM"]."km, ";
		if ($job["WARRANTY_KM"]!="") {
			if ($job["WARRANTY_KM"]>$job["KM"])
				echo "<font color=green>End-End Warranty expires ";
			else
				echo "<font color=red>End-End Warranty expired ";
			echo $job["WARRANTY_KM"]."km,</font> ";			
		}
		if ($job["DRIVELINE_WARRANTY_KM"]!="") {
                        if ($job["DRIVELINE_WARRANTY_KM"]>$job["KM"])
                                echo "<font color=green>Driveline Warranty expires ";
                        else
                                echo "<font color=red>Driveline Warranty expired ";
                        echo $job["DRIVELINE_WARRANTY_KM"]."km,</font> ";
                }
		if ($job["BODY_WARRANTY_KM"]!="") {
                        if ($job["BODY_WARRANTY_KM"]>$job["KM"])
                                echo "<font color=green>Body Warranty expires ";
                        else
                                echo "<font color=red>Body Warranty expired ";
                        echo $job["BODY_WARRANTY_KM"]."km,</font> ";
                }


		echo "<br>";
	
		if (!isset($hanging))
			$hanging = false;

		if (!$hanging && is_numeric($req) && $rights["STOREMAN"]) {
		        echo "<a href=partsrequest.phtml?req=$req&jobcardserial=$jobcardserial&doremove=Y>REMOVE REMAINING REQUEST FROM JOB CARD</a> <font color=red>For when some parts are NOT available yet and the jobcard needs to be closed.</font><br>";
		}

		if ($newline)
			echo "<bR><font size=2 style='background: yellow'  color=red>** Please return faulty parts for warranty claim **</font><bR>";
		
		if ($req=="NEW")
			$button="Add New Entry";
		else {
//			print_r($rights);
//			echo $entry["APPROVED"];
			if ($rights["SUPERVISOR"] && $entry["APPROVED"]=="U")
			{
				echo "Comments by Approver:<br><textarea name=acomments rows=3 cols=40>".$_POST["acomments"]."</textarea><bR>";
				$button="Approve";
			       if ($entry["RECEIVED_DATE"]=="")
	               		         echo "<input style='background:lightgreen' type=submit name=submit value='Approve'> or <input style='background:lightpink' type=submit name=submit value='Reject'>";
			}
			else
			{
				if ($entry["APPROVED"]=="U")
					echo " <b>This still needs to be approved by a foreman.</b><Br><font color=magenta>If you are a foreman</font>, please ask HR to give you the rights.<Br>";
				switch ($status) {
			
			
					default: $button="Update";
				} // switch
			}
			                        // new: notes
                        echo "<Table width=550><tr class=head><td>Notes</td><td>Who</td><td>When</td></tr>";
                        ora_parse($cursor,"select A.username,to_char(B.date_logged,'HH24:MI DD Mon YY') , notes from  move_jobs_part_request_notes B, user_details A  where A.user_serial=B.logged_by and B.pr_serial=$req order by B.date_logged");
                        ora_exec($cursor);
			$noteline = 0;
                        while (ora_fetch_into($cursor,$notes)) {
                                $noteline++;
                                if ($noteline%2==0)
                                        echo "<Tr class=cell>";
                                else
                                        echo "<Tr class=altcell>";
                                echo "<Td>$notes[2]</td><td>$notes[0]</td><td>$notes[1]</td></tr>";
				unset($notes);
                        }
                        if (!isset($entry["RECEIVED"]) || $entry["RECEIVED"]=="")
			{
                                echo "<Tr class=head><td><input name=newnotes size=40 maxlength=500><input type=submit value='Add'></td><td colspan=2>&lt;-Type new notes here</td></tr>";
				if ($rights["STOREMAN"]) {
				  if ($entry["ON_ORDER_FLAG"]=="Y")
					echo "<tr class=head><td colspan=3><input type=submit name=remove_order style='background: lightpink' value='Remove ON ORDER status'>  <a href=requestorder.phtml?jobcard=$jobcardserial>Create Order</a></td></tr>";
				  elseif ( $entry["RECEIVED_DATE"]=="")
					echo "<tr class=head><td colspan=3><input type=submit name=add_order style='background: lightpink' value='Set PARTS ON ORDER Status'>  <a href=requestorder.phtml?jobcard=$jobcardserial>Create Order</a></td></tr>";
				}

			}
                        // end notes
			echo "</table>";

			echo "<hr/>";
			echo "hello";
			echo "<hr/>";


			echo "<hr>Email this parts request to:<Br><Select name=mailto><option value=''>Please select somebody...";
			ora_parse($cursor,"select nvl(email,username),null,name,lastname from user_Details A, user_pages B where A.is_current='Y' and A.staff_member='Y' and A.user_serial=B.user_serial and B.page_name in ('MOVE_STOCK','PARTS_REQ_AUTH','FOREMAN') and (B.expires is null or B.expires<CURRENT_TIMESTAMP) order by name,lastname");
			ora_exec($cursor);

			while (ora_fetch_into($cursor,$em)) {
				$email=$em[0];
				if (!strstr($email,"@"))
					$email.="@cavmail.co.za";
				echo "<option value='$email'>$em[2] $em[3] ($email)";
				unset($em);
			}
			echo "</selecT><Br><input type=submit name=submit value='Send Email'><hr>";



		}



		if ($entry["RECEIVED_DATE"]=="")
			echo "<input type=submit style='background: lightgreen' name=submit value='$button'>";
		echo "</form>";

		if ($jobcardserial!="" &&  !isset($_GET["printslip"]))
		{
			echo "<a href=partsrequest.phtml?jobcardserial=$jobcardserial>Go back to all part requests for MOVE$jobcardserial</a><br>";
		
		}
	} // edit_entry

//////////////////////////////////

function picking_slips($req,$job_received) {
	global $conn, $rights, $hanging;

	$kcur=ora_open($conn);

	$first_found=false;

	echo "<b><u>Picking Slip(s) for PR$req:</u></b><br>";

	$ps_total=0;	
	$specialflag=false;

	$qry="select B.username,C.username rx_username ,D.username appusername,A.* from user_details B, stk_picking_slip A left join user_details C on C.user_serial=A.received_by left join user_details D on D.user_serial=A.approved_by  where pr_number=$req and B.user_serial=A.created_By order by ps_number desc";
//	echo "$qry<bR>";
	ora_parse($kcur,$qry);
	ora_exec($kcur);
	$not_received=0;
	$got_received=0;
	while (ora_fetch_into($kcur,$data,ORA_FETCHINTO_ASSOC)) {
		if ($data["RECEIVED_DATE"]=="")
			$not_received++;
		else
			$got_received++;
//		echo "***".implode("|",$data)."<br>";
		if (!$first_found) {
			$first_found=true;			
			if ($data["FINALIZED_DATE"]!="" && $job_received=="" && !$hanging)
				   show_picking_slip($req,"NEW");
		}
		if (isset($_GET['printslip']) && $_GET['printslip']==$data["PS_NUMBER"])
			ob_end_clean();
		echo "<b>Picking Slip PS".$data["PS_NUMBER"]."</b> <font size=2>(Created by <b>".$data["USERNAME"]."</b> ".$data["CREATED_DATE"];
		if ($data["FINALIZED_DATE"]!="")
			echo ", <i>Finalized ".$data["FINALIZED_DATE"]."</i>";
		if ($data["APPUSERNAME"]!="")
			echo ", Approved by ".$data["APPUSERNAME"]. " ".$data["APPROVED_WHEN"];
		if ($data["RECEIVED_DATE"]!="")
			echo ",<Br><b><font color=green>Received by ".$data["RX_USERNAME"]." on ".$data["RECEIVED_DATE"]."</font></b>";
			if ($data["JOBCARDSERIAL"]!="" && $jobcardserial!=$data["JOBCARDSERIAL"])
				echo " <b><font style='background:yellow'>J/C: MOVE".$data['JOBCARDSERIAL']."</font></b> ";
		echo ")</font><br>";
		if ($data["WAYBILL_NO"]!="")
			echo "<font color=magenta><i><b>Old Items SENT TO PWA on Waybill No ".$data["WAYBILL_NO"]."</i></font><br>";
		$ps_total += show_picking_slip($req,$data["PS_NUMBER"],$data["FINALIZED_DATE"],$data["RECEIVED_DATE"],$data["APPROVED_BY"],$data['JOBCARDSERIAL']);
		unset($data);
	} // while
	ora_parse($kcur,"update move_jobs_part_requests set pick_slip_value=$ps_total where (pick_slip_value is null or pick_slip_value!=$ps_total) and pr_serial=$req");
	ora_exec($kcur);


	if (!$first_found && $job_received=="" && !$hanging)
		   show_picking_slip($req,"NEW");

	if (/*$got_received>0 &&*/ $not_received==0 && $job_received=="") {

		if ($got_received==0)
			echo "<font color=red>Only click here if this is ONLY a request for a serivce<Br>and it has been arranged</font> ";
		echo "<input type=submit style='background: lightgreen'  name=closeoff value='Final Close-off (All items received)'> / ";
		
	}



} // picking_slips



//////////////////////////////////


function get_new_ps_serial($req) {
	global $cursor;

	$us=getuserserial();
	ora_parse($cursor,"select stk_picking_slip_no.nextval from dual");
	ora_exec($cursor);
	ora_fetch($cursor);
	$ps=getdata($cursor,0);
	$qry="insert into stk_picking_slip (ps_number, pr_number, created_by, created_date) values ($ps, $req, $us, CURRENT_TIMESTAMP)";
	if (!ora_parse($cursor,$qry)) {
		echo "Error 101<br>$qry";
		exit;
	}	
	if (!ora_exec($cursor)) {
		echo "Error 102 <br> $qry<bR>";
		exit;
	}
	return $ps;

	
}


/////////////////////////////
?>

<script>

	function update_icg (ps,track) {

		window.location='partsrequest.phtml?jobcardserial=<?=$jobcardserial?>&req=<?=$req?>&psi'+ps+'_NEW='+track+'&ps_submit=Search';
/*
		try {
			document.getElementById('newtrack'+ps).value=track;
			document.getElementById('newtrack'+ps).focus();
			document.getElementById('newtrack'+ps).style='background: yellow';
	//		document.bigform.submit(); // TODO _ FIX THIS
		} 
		catch (error)
		{
			alert('error updating '+ps+' '+track);
		}

		try {
		//	document.getElementById('bigform').submit();
			document.forms["bigform"].submit();
		} catch (error)
		{
			alert('issue: '+document.getElementById('bigform'));
			alert(document.forms["bigform"]);
		}
*/

	} // update_icg;

	function icg_popup(data) {

		icg_pop=window.open('pick_icg_pop.phtml?data='+data,'icg_pop','left=40,top=40,width=300,height=500');
		icg_pop.focus();


	}

	function fill_in_icg ( id, value ){
		try {
			document.getElementById(id).value=value;	
		} catch(error)
		{
			console.log(error);
		}

	}

</script>


<?php



/////////////////////////////

function show_picking_slip($req,$ps="NEW",$finalized="",$received="",$approved_by="", $psjobserial="") {

	global $cursor, $depot, $depot_list, $jobcardserial, $rights, $conn, $cookiecompany, $job, $hanging, $lastremovedvalue, $oldpart_options, $oldpart_disp, $comparep, $wildcard_depots;


	$needsaserial = false;
	$newadded = false;
	$check_empty = false;
	if ($psjobserial=="") {
		$psjobserial = $job["JOBCARDSERIAL"];
		$psjobserial = $job["JOBCARDSERIAL"];
	}

	if ($finalized=="")
		$edit=true;
	else
		$edit=false;

	if (!$rights["STOREMAN"])
	{
		$edit=false;
		if ($ps=="NEW")
			return;
	}

	$slipvalue=0;
	$specialflag=false;

	$args=array();
	$serialized=false;

	reset($_GET);
	while (list($key,$val)=each($_GET))		
		$_POST[$key]=$_GET[$key];

	reset($_POST);
	
	$checkstring="confirm_$ps"."_";
	$checkstringq="psq$ps"."_";
	$checkstringd="ps_delete_$ps"."_";
	$checkstringc="pssc$ps"."_";
	$checkstrings="seal$ps"."_";
	$checkstringz="psz$ps"."_";

	$checklen=strlen($checkstring);
	$checklenq=strlen($checkstringq);
	$checklenz=strlen($checkstringz);
	$checklend=strlen($checkstringd);
	$checklenc=strlen($checkstringc);

	reset($_POST);
	foreach ($_POST as $key => $val) {
		if (substr($key,0,3) == "nmf") {
			$bits = explode ("_", $key);
			$ptser = $bits[1];
			
			$val = trim(strtoupper(str_replace("'","",$val)));
			if ($val != "" && is_numeric($ptser)) {
				ora_parse($cursor, "update stk_serialass set mfr_serial='$val' where serial=$ptser and mfr_serial is null");
				ora_exec($cursor);
//				echo ora_numrows($cursor)." updated ($ptser)<bR>";

			} else echo "ignoring $key -> $val = $bits[0] / $bits[1]<br>";
		}
	}

	if (isset($_POST["newjobcard$ps"])) {
		
		$qry="update stk_picking_slip set jobcardserial=".$_POST["newjobcard$ps"]." where ps_number=$ps";
		if (ora_parse($cursor,$qry))
			ora_exec($cursor);

	}
	
        if (isset($_POST["psdelete_$ps"])) { // delete Picking Slip - ONLY if it's empty
		ora_parse($cursor,"select 'x' from stk_picking_slip_contents where ps_number=$ps");
		ora_exec($cursor);
		if (ora_fetch($cursor)) {
			error_pop("Unable to delete - there is something on it");			
		} else {
                  $qry="delete from stk_picking_slip where ps_number=$ps";
//                echo "$qry<bR>";
                  if (ora_parse($cursor,$qry))
                        if (ora_exec($cursor))
                        {
                                echo "<Font size=2><b>PS$ps has been Deleted</b></font><Br>";
                                $finalized="Just Now";
                                $edit=false;
                        }
                        else
                                error_pop("Problem deleting PS$ps");
                  else
                        error_pop("Problem deletingPS$ps");
		}
        } // ps delete

	if (isset($_POST["psclose_$ps"])) {
		$qry="update stk_picking_slip set finalized_date=CURRENT_TIMESTAMP where ps_number=$ps";
//		echo "$qry<bR>";
		if (ora_parse($cursor,$qry))
			if (ora_exec($cursor))
			{
				echo "<Font size=2><b>PS$ps has been Finalized</b></font><Br>";
				$finalized="Just Now";
				$edit=false;
			}
			else
				error_pop("Problem closing PS$ps");
		else
			error_pop("Problem closing PS$ps");
	

	} // ps close

	$psjobcard = "";

	if (isset($_POST["psreceived_$ps"]) && $received=="" && $finalized!="") {
		$us=getuserserial();
		$allcorrect=true;
		ora_parse($cursor,"select jobcardserial from stk_picking_slip where ps_number=$ps");
		ora_exec($cursor);
		ora_fetch($cursor);
		$psjobcard=getdata($cursor,0);
		if ($psjobcard=="")
			$psjobcard=$jobcardserial;
		$qry="update stk_picking_slip set received_by=$us, received_date=CURRENT_TIMESTAMP where ps_number=$ps";
//                echo "$qry<bR>";
                if (ora_parse($cursor,$qry))
                        if (ora_exec($cursor))
                        {
                                echo "<font size=2><b>PS$ps has been marked as received by you</b></font><Br>";
                                $received="Just Now";
                        }
                        else
                                $allcorrect=false; //error_pop("Problem receiving PS$ps");
                else
			$allcorrect=false;

		if ($depot=="PTA")
			$waybill = false;
		else {
			ora_parse($cursor,"select 'x' from depots where depot_code='$depot' and physical_depot='PTA'");
			ora_exec($cursor);
			if (ora_fetch($cursor))
				$waybill = false;
			else
				$waybill = true;
		}
		if ($waybill) {
			ora_parse($cursor,"Select sum(quantity) from stk_picking_slip_contents where ps_number=$ps and nvl(old_part_action,'W')='W'");
			ora_Exec($cursor);
			ora_fetch($cursor);
			$pieces=getdata($cursor,0);
			if ($pieces > 0) {
				 $waybillno = getWaybill( depotpcode($depot), 1, $pieces);
				echo "<font color=magenta><b>$pieces items to be sent to PWA (PTA) on waybill $waybillno</b></font><br>";
				ora_parse($cursor,"update stk_picking_slip set waybill_no=$waybillno where ps_number=$ps");
				ora_exec($cursor);
			} else
				$waybillno=getInternalWaybill();
		} else {
			$waybillno=getInternalWaybill();
		}
//		echo "Waybill NO is $waybillno<br>";

		// get vehicle
		ora_parse($cursor,"select unitserial from move_jobs where jobcardserial=$psjobcard");
                ora_exec($cursor);
                ora_fetch($cursor);
                $unitserial=ora_getcolumn($cursor,0);
	

		// transfer ...
		$kcur=ora_open($conn);
		ora_parse($kcur,"select A.*, B.must_return from stk_picking_slip_contents A left join stk_parts B on B.serial=A.part_number where A.ps_number=$ps");
		ora_exec($kcur);
		unset($data);
		$time=time();
//		echo "check company: $cookiecompany<bR>";
		while (ora_fetch_into($kcur,$data,ORA_FETCHINTO_ASSOC)) {
	//		echo "<hr>";
	//		print_r($data);
	//		echo "<br>";
			if ($data["PART_SERIAL"]=="") {

				if (($data["PART_NUMBER"]==6105 || $data["PART_NUMBER"]==17412 || $data["PART_NUMBER"]==18933 )  && $data["SEAL"]!="") {
                                                $seal=$data["SEAL"];
                                                if (!is_numeric($seal)) {
                                                        ora_rollback($conn);
                                                        echo "Please supply seal a number<bR>";
                                                        go_back();
                                                        exit;
                                                }

                                                $now=date("YmdHis");
                                                $qry="insert into move_seals (seal_no, unitserial, date_added, jobcard) VALUES ($seal, $unitserial, to_date('$now','YYYYMMDDHH24MISS'), $psjobcard)";
//                                              echo "$qry<bR>";
                                                ora_parse($cursor, $qry);
                                                if (!ora_exec($cursor))
                                                        $allcorrect=false;


				} // seal
				elseif ($data["MUST_RETURN"]=="Y")  {

				// OLD PART
				if ($data["OLD_PART_ACTION"]=="" || $data["OLD_PART_ACTION"]=="W") {
					// send old parts to PWA
	
					
					if ($allcorrect) {
						if( !removeStock( $data["PART_NUMBER"], L_VEHICLE, $unitserial, $data["QUANTITY"], $cookiecompany, $waybillno ) ) $allcorrect = false;
					}
					if ($allcorrect) {
                                           if( !addStock( $data["PART_NUMBER"], L_STOCK, "$depot,W,1", $data["QUANTITY"], $cookiecompany, $waybillno ) ) $allcorrect = false;
					}
					sleep(1); // make the transaction below have a different time
					if ($allcorrect) {
                                          if( !removeStock( $data["PART_NUMBER"], L_STOCK, "$depot,W,1", $data["QUANTITY"], $cookiecompany, $waybillno ) ) $allcorrect = false;
					}
					if ($allcorrect) {
					  if( !addStock( $data["PART_NUMBER"], L_DEPOT, 'PWA', $data["QUANTITY"], $cookiecompany, $waybillno ) ) $allcorrect = false; 
					}

				}
			

				} // not a seal

				// NEW PART
				if ($allcorrect)
				{
			                if( !removeStock( $data["PART_NUMBER"], L_STOCK, $data["BIN"], $data["QUANTITY"], $cookiecompany, $time ) ) $allcorrect = false;
				}
				if ($allcorrect)
				{
     	  	                 	if( !addStock( $data["PART_NUMBER"], L_JOBCARD, $psjobcard, $data["QUANTITY"], $cookiecompany, $time ) ) $allcorrect = false;
				}

//				if ($allcorrect) echo "ALL OK"; else echo "BAD";
//				$allcorrect=false;

//function addStock( $ipartno, $lcode, $location, $quantity, $company, $uid=0, $length=1, $iserial=-1, $value=-1, $delnote=-1, $secondhand="", $faulty="", $gof="N", $grntkm=0, $grntm=0  ) {


			} else { // serialized

				// OLD PART

				if (!is_numeric($data["OLD_PART_SERIAL"])) {
					ora_rollback($conn);
					echo "<font color=red size=+2>BAD ERROR!  No old part specified</font>";
					exit;

				}

                                        ora_parse($cursor,"select lcode, location from stk_serialtrack where serial=".$data["OLD_PART_SERIAL"]);
                                        ora_exec($cursor);
                                        if (ora_Fetch($cursor)) {
                                                $old_lcode=getdata($cursor,0);
                                                $old_location=getdata($cursor,1);
                                        }
                                        else $old_lcode=4;

				      if ($allcorrect) {
                                        if ($old_lcode!=4)
                                        {
                                                if( !removeStock( $data["PART_NUMBER"], $old_lcode, $old_location, $data["QUANTITY"], $cookiecompany, $waybillno, 1, $data["OLD_PART_SERIAL"] ) ) $allcorrect = false;

                                        } else {
                                                if( !removeStock( $data["PART_NUMBER"], L_VEHICLE, $unitserial, $data["QUANTITY"], $cookiecompany, $waybillno, 1, $data["OLD_PART_SERIAL"] ) ) $allcorrect = false;
                                        }
				      }


				if ($data["OLD_PART_ACTION"]=="S" || $data["OLD_PART_ACTION"]=="D") {
					//scrap
					if ($data["OLD_PART_ACTION"]=="S")
						$scrapwhere="Stolen";
					else
						$scrapwhere="Damaged in accident";

					if ($allcorrect)	
					{
						if( !addStock( $data["PART_NUMBER"], L_SCRAP, $scrapwhere, $data["QUANTITY"], $cookiecompany, $waybillno, 1, $data["OLD_PART_SERIAL"] ) ) $allcorrect = false;
					}
				}


				if ($data["OLD_PART_ACTION"]=="" || $data["OLD_PART_ACTION"]=="W") {
                                        // send old parts to PWA

					if ($allcorrect)
					{
						if( !addStock( $data["PART_NUMBER"], L_STOCK, "$depot,W,1", $data["QUANTITY"], $cookiecompany, $waybillno, 1, $data["OLD_PART_SERIAL"] ) ) $allcorrect = false;
					}
					sleep(1); // make the below transaction have a different time
					
					if ($allcorrect)
					{
						if( !removeStock( $data["PART_NUMBER"], L_STOCK, "$depot,W,1" , $data["QUANTITY"], $cookiecompany, $waybillno, 1, $data["OLD_PART_SERIAL"] ) ) $allcorrect = false;
					}
					if ($allcorrect)
					{
                     	                   if( !addStock( $data["PART_NUMBER"], L_DEPOT, 'PWA', $data["QUANTITY"], $cookiecompany, $waybillno, 1, $data["OLD_PART_SERIAL"] ) ) $allcorrect = false;
					}
	

                                }

			// NEW PART:
			      if ($allcorrect)
				{
				if( !removeStock( $data["PART_NUMBER"], L_STOCK, $data["BIN"], $data["QUANTITY"], $cookiecompany, $time, 1, $data["PART_SERIAL"]) ) $allcorrect = false;
				}

			      if ($allcorrect)
				{
              	                  if( !addStock( $data["PART_NUMBER"], L_JOBCARD, $psjobcard, $data["QUANTITY"], $cookiecompany, $time, 1,  $data["PART_SERIAL"] ) ) $allcorrect = false;
				}
			}

//			echo "Storing value of ".$data["PART_NUMBER"]." as $lastremovedvalue<br>";
			if (is_numeric($lastremovedvalue)) {
				ora_parse($cursor,"update stk_picking_slip_contents set value=$lastremovedvalue where ps_number=$ps and psc_serial=".$data["PSC_SERIAL"]);
				ora_exec($cursor);
			}

			unset($data);
		} // while

	
		echo "<hr>";
		if (!$allcorrect) {
			ora_rollback($conn);
                        error_pop("Problem receiving PS$ps due to error(s). Click on UPDATE to refresh.");
		}
		else {
//			echo "COMMIT???";
//			ora_rollback($conn);
			ora_commit($conn);

		}
//		exit;

	} // received

	if (isset($_POST["psauth_$ps"])) {
		$myus=getuserserial();
		$qry="update stk_picking_slip set approved_by=$myus, approved_when=CURRENT_TIMESTAMP where ps_number=$ps";
		echo "$qry<BR>";
		ora_parse($cursor,$qry);
		if (ora_exec($cursor)) {
			ora_commit($conn);
			$approved_by=$myus;
		}
	}

        if (isset($_POST["psunclose_$ps"]) && $received=="") {
                $qry="update stk_picking_slip set finalized_date=null, approved_by=null, approved_when=null, jobcardserial=null where ps_number=$ps";
                //echo "$qry<bR>";
                if (ora_parse($cursor,$qry))
                        if (ora_exec($cursor))
                        {
                                echo "<font size=2><b>PS$ps has been un-Finalized</b></font><Br>";
                                $finalized="";
				$edit=true;
                        }
                        else
                                error_pop("Problem un-closing PS$ps");
                else
                        error_pop("Problem un-closing PS$ps");


        } // ps unclose



	while (list($key,$val)=each($_POST)) {
		$val=trim($val);
		if (substr($key,0,$checklenc)==$checkstringc) { // add serialized part
			$key2=str_replace("pssc","olds",$key);
			$oldserial=$_POST[$key2];
			$key2=str_replace("pssc","oldz",$key);
			$keybits=explode("_",$key2);
		
                        $oldserial2=trim($_POST[$key2]);
			if (is_numeric($oldserial2)) {
//				echo "check: $oldserial2/$oldserial<br>";
				if ($oldserial==-1) {
					if (!isset($allcorrect))
						$allcorrect=true;
					// create new part
					echo "ADDING NEW PART<bR>";	
						// add to vehicle $job["UNITSERIAL"];
					$bits=explode("_",$key2);
					ora_parse($cursor,"select stk_serial.nextval from dual");
					ora_exec($cursor);
					ora_fetch($cursor);
                			$iserial= ora_getColumn( $cursor,0);
				        if (!assSerial( $iserial, $oldserial2 ))
			                        $allcorrect=false;

					$qry="insert into stk_Serialtrack (serial, ipartno, lcode, location, company, value, accounted_for) values ($iserial, $bits[2], 1, '".$job["UNITSERIAL"]. "', $cookiecompany, 1, 'Y')";
					if (!ora_parse($cursor,$qry))
						$allcorrect=false;
					elseif (!ora_exec($cursor))
						$allcorrect=false;
			
					if (!$allcorrect) {
						ora_rollback($conn);
						echo "<font color=red size=+2>Unable to create new part $oldserial2</font> Please go back and try again";
						echo "<br>$qry";
						exit;
					}
					$oldserial = $iserial;
	
	
				} else {
				ora_parse($cursor,"select serial from stk_serialass where track='$oldserial2'");
				ora_exec($cursor);
				if (ora_fetch($cursor))
					$oldserial=getdata($cursor,0);
				else echo "<font color=red>Could not find $oldserial2</font><br>";
				}
			}
			if ($keybits[1]==$oldserial) {

				ora_rollback($conn);
				echo "<font color=red>SORRY, You cannot remove and add the same ICG number!</font>";
				 exit;
			}
			if (!is_numeric($oldserial)) {
				ora_rollback($conn);
                                echo "<font color=red>SORRY, cannot find that ICG number!</font>";
                                 exit;
			}
			ora_parse($cursor,"select ipartno from stk_serialtrack where serial=$oldserial");
			ora_exec($cursor);
			if (!ora_fetch($cursor)) {

				ora_rollback($conn);
                                echo "<font color=red>SORRY, cannot find that ICG number!</font>";
                                 exit;

			}
			$comparep=getdata($cursor,0);
			if ($comparep!=$keybits[2]) {
                                echo "<font color=red style='background:yellow'>WARNING: You are removing a M$comparep and replacing with a $keybits[2]</font><br><script> alert('WARNING: You are removing a M$comparep and replacing with a $keybits[2]'); </script>";
			}

			$key2=str_replace("oldz","miss",$key2);
			$missing=trim(strtoupper($_POST[$key2]));
			$missing=str_replace("ICG","",$missing);	

			if ((!is_numeric($oldserial)) || $oldserial==-1) {

				echo "<Font color=red size+2>Sorry, you did not specify the old ICG Number</font> Please go back and correct your mistake.";

			//echo "Key is $key, val is $val old serial is $oldserial, missing is $missing<br>";
				ora_rollback($conn);
				exit;
			}
			if ($ps=="NEW")
                        {
                                $newps=get_new_ps_serial($req);
                                $useps=$newps;
                        }
                        else
                                $useps=$ps;
			$bits=explode("_",$key);
			// 1=serial 2=mnumber 3=location 4=lcode - TODO!!!
			if ($bits[3]!=2) 
			{
				$bits[4]="Other";
				echo "<hr><font color=red>SORRY, but one of the parts is NOT in stock.  Please book it into stock first</font><hr>";
			} else {

			ora_parse($cursor,"select value from stk_Serialtrack where serial=$bits[1]");
			ora_Exec($cursor);
			if (ora_fetch($cursor)) {
				$value=getdata($cursor,0);
				if (!is_numeric($value))
					$value="null";
			}
			$qry="insert into stk_picking_slip_contents (psc_serial, ps_number, part_number, part_serial, quantity, bin, value, old_part_action, old_part_serial) values ( stk_picking_slip_lineserial.nextval, $useps, $bits[2], $bits[1], 1,'$bits[4]', $value, '$missing', '$oldserial') ";
                        //echo "$qry<bR>";
                        if (ora_parse($cursor,$qry))
                                if (ora_exec($cursor))
                                {
                                        $newadded=true;
//                                      echo "ADDED<Br>";
                                }
			}
	
		} elseif (substr($key,0,$checklen)==$checkstring) {
			if ($ps=="NEW")
			{
				$newps=get_new_ps_serial($req);
				$useps=$newps;
			}
			else
				$useps=$ps;
			$key2=str_replace("confirm","miss",$key);
			if (isset($_POST[$key2]))
				$missing = $_POST[$key2];
			else
				$missing = "";
		
			$bits=explode(",",substr($key,$checklen,9999));

			if (isset($_POST[str_replace("confirm","seal",$key)]))
				$seal=$_POST[str_replace("confirm","seal",$key)];
			else
				$seal = "";
//			echo "Seal is $seal and bits[0] is $bits[0]<br>";
			if (($bits[0]==6105 || $bits[0]==17412 || $bits[0]==18933 )  && !is_numeric($seal)) {
				echo "<font color=red>SORRY, you didnt specify the seal number</font><script>alert('Sorry, you didnt specify the seal number');</script><hr>";
			}
			else {
			if (!is_numeric($seal))
				$seal="null";
//			echo "CONFIRM ";
			//print_r($bits);
			$qty=substr($val,4,99);
			ora_parse($cursor,"select ave_price from stk where depot='$bits[3]' and shelf='$bits[1]' and scol=$bits[2] and i_partno=$bits[0]");
			ora_Exec($cursor);
			$value="null";
			if (ora_fetch($cursor)) {
				if (is_numeric(getdata($cursor,0)))
					$value=getdata($cursor,0);
			}
			$qry="insert into stk_picking_slip_contents (psc_serial, ps_number, part_number, quantity, bin, seal, value, old_part_action) values ( stk_picking_slip_lineserial.nextval, $useps, $bits[0], $qty,'$bits[3],$bits[1],$bits[2]',$seal, $value, '$missing') ";
			//echo "$qry<bR>";
			if (ora_parse($cursor,$qry))
				if (ora_exec($cursor))
				{
					$newadded=true;
//					echo "ADDED<Br>";
				}
			}
		} // if
		elseif (substr($key,0,$checklenz)==$checkstringz ) {
                        $bits=explode("_",$key);
                        if (isset($_POST["ps_change_$bits[1]"])) {
				$val=str_replace("'","",$val);
                                $qry="update stk_picking_slip_contents set old_part_action='$val' where psc_serial=$bits[1]";
                                //echo "$qry<BR>";
                                if (ora_parse($cursor,$qry))
                                        if (ora_exec($cursor)) {
                                                echo "<font size=2><b>Updated Old Part Action</b></font><Br>";
                                        } else error_pop("Unable to update old part action to $val");
                                else error_pop("Unable to update old part action to $val");
                        }

                }

		elseif (substr($key,0,$checklenq)==$checkstringq && $val>0 ) {
			$bits=explode("_",$key);
			if (isset($_POST["ps_change_$bits[1]"])) {
				$qry="update stk_picking_slip_contents set quantity=$val where psc_serial=$bits[1]";
				//echo "$qry<BR>";
				if (ora_parse($cursor,$qry))
					if (ora_exec($cursor)) {
						echo "<font size=2><b>Updated Quantity</b></font><Br>";
					} else error_pop("Unable to update quantity to $val");
				else error_pop("Unable to update quantity to $val");
			}

		}
		 elseif (substr($key,0,$checklend)==$checkstringd && $val=="Delete") {
                        $bits=explode("_",$key);
			$qry="delete from stk_picking_slip_contents  where psc_serial=$bits[3]";
			//echo "$qry<BR>";
			if (ora_parse($cursor,$qry))
                                if (ora_exec($cursor)) {
                                        echo "<font size=2><b>Entry has been Deleted</b></font><Br>";
					// check to see it it's empty now...
					$check_empty=true;
                                } else error_pop("Unable to delete");
                        else error_pop("Unable to delete");


		}
//		else echo "$checkstringd ($checklend) $key = $val<br>";
	} // while

	if (isset($check_empty) && $check_empty) {
		ora_parse($cursor,"select 'x' from stk_picking_slip_contents where ps_number=$ps");
		ora_exec($cursor);
		if (!ora_fetch($cursor)) {
			ora_parse($cursor,"delete from stk_picking_slip where ps_number=$ps");
			ora_exec($cursor);
			echo "<font size=2>PS$ps has been deleted</font><BR>";
			if (!$hanging)
				show_picking_slip("NEW");	
			return true;
		}
	}
	if (isset($newps))
	{
		$ps=$newps;
		echo "<u><b>Picking Slip PS$newps</b></u> (Created by YOU, just now)<br>";
	}
	elseif ($ps=="NEW")
                echo "<B><font style='background: yellow'>NEW Picking slip...</font></b><bR>";


    echo "<table border=1 cellspacing=0><tr class=head><td>M Part</td><td>ICG No</td><td>Old Part <i><font size=2>& its current location </font><font size=1>(Serialized only)</font></i></td><td>Description</td><Td>Qty</td><td>Bin</td><td>Value</td><td>Action / Notes</td></tr>";

//	echo "PS$ps<BR>";

//	echo "OUT1<bR>\n";
	if (!$newadded) {
		// check for search on new
		$newm=GARG("psm$ps"."_NEW");
		$newd=GARG("psd$ps"."_NEW");
		$newd=strtoupper(trim($newd));
		$newd=str_replace("'","",$newd);
		$newi=GARG("psi$ps"."_NEW");
		$newq=GARG("psq$ps"."_NEW");
		$newb=GARG("psb$ps"."_NEW");
		$newo=GARG("pso$ps"."_NEW");
		$newp=GARG("psp$ps"."_NEW");
		if (is_numeric($newm))
		{
			// m number search
			$args[]="B.serial=$newm";
		}
		if ($newd!="")
		{
			// description search
			$args[]="upper(B.description) like '%$newd%' ";
			$mpartfound=0;
			ora_parse($cursor,"select ipartno,description from stk_manufacturer_parts where upper(description) like '%$newd%'");
			ora_exec($cursor);
			$mparts=array();
			while(ora_fetch($cursor)) {
				$mpartfound++;
				$mparts[]=getdata($cursor,0);
				$mpartfind[getdata($cursor,0)]=getdata($cursor,1);
			}
			if ($mpartfound>0)
			{
				$mserials=implode(",",$mparts);
				$args[]="B.serial in ($mserials)";
			}
		}
		$newi=trim(strtoupper($newi));
		if ($newi!="") {
			
			// icg number search
			if (is_numeric($newi))
				ora_parse($cursor,"select serial,track,mfr_serial from stk_serialass where track='$newi' or mfr_serial='$newi'");
			else
				ora_parse($cursor,"select serial,track,mfr_serial from stk_serialass where mfr_serial='$newi'");

			$serials=array();
			echo "Searching...";
			ora_exec($cursor);
			$serialfound=0;
			while (ora_fetch($cursor)) {
				$serialfound++;
				$serialized=true;
				$partserial=getdata($cursor,0);
				$serials[]=$partserial;
				$actual_name[$partserial]=getdata($cursor,1);
				$mfr_name[$partserial]=getdata($cursor,2);
				if ($mfr_name[$partserial]!="")
					$actual_name[$partserial].=" <i>(Mfr: $mfr_name[$partserial])</i>";
		
				
			}
			if ($serialfound==0) {
				echo "<Tr><td colspan=20><font color=red>Sorry, Serial Number $newi not found in our system!</font></td></tr>";	
			}
		} // icg no search
		if ($newb!="") {
			// bin search
			$newb=strtoupper(trim($newb));
			$newb=str_replace(" ","",$newb);
			$letter="";
			$number="";
			for ($a=0;isset($newb[$a]);$a++)
				if ($newb[$a]>="0" && $newb[$a]<="9")
					$number.=$newb[$a];
				else
					$letter.=$newb[$a];
			if ($letter!="" && $number!="")
				$args[]="(A.shelf='$letter' and A.scol='$number')";
		} // bin search
		
	} // search on new

//	if (!$edit)
		//echo "NO EDIT<Br>\n";

	if ($edit) {

		if (!isset($cursor2))	
			$cursor2 = ora_open($conn);
		
//		echo "EDIT<Br>\n";
		if (!isset($newm))
			$newm = "";
		echo "<tr class=cell><td>M<input name=psm$ps"."_NEW size=4 value='$newm'></td>";
		if (!isset($newi))
			$newi = "";
		echo "<td><input name=psi$ps"."_NEW size=6 value='$newi' id=newtrack$ps></td>";

		echo "<td>&nbsp;</td>";
		if (!isset($newd))
			$newd = "";
		echo "<td><input name=psd$ps"."_NEW size=10 value='$newd'></td>";
		if (!isset($newq))
			$newq =  "";
		echo "<td><input name=psq$ps"."_NEW size=3 value='$newq'></td>";
		if (!isset($newb))
			$newb = "" ;
		echo "<td><input name=psb$ps"."_NEW size=5 value='$newb'></td>";
		echo "<td></td>";
		echo "<td><input type=submit name=ps_submit value='Search'></td>";
		echo "</tr>\n";

		// now for the search....
		echo "<tr class=cell bgcolor=yellow>";
		if ($serialized) {
			$partserials=implode(",",$serials);
			$qry="select A.lcode,A.location,A.ipartno,B.description,A.serial,A.value,B.must_return,B.mfr_serial_required, C.mfr_serial  from stk_parts B, stk_serialtrack A left join stk_serialass C on C.serial=A.serial where A.serial in ($partserials) and A.ipartno=B.serial";
			// 7 = required, 8 = mfr serial
			//echo "$qry<bR>";
			ora_parse($cursor,$qry);
			ora_Exec($cursor);
			$serialfound=0;
			while (ora_fetch_into($cursor,$sfound)) {
				$serialfound++;
				$describe="Unknown";
				$partserial=$sfound[4];
				switch ($sfound[0]) {
					case 10: $describe="<font color=red>SCRAPPED</font>"; break;
					case 4: $describe="<font color=red>JOBCARD</font>"; break;
					case 2: $describe=""; break;
					case 11:  $describe="<font color=red>SOLD</font>"; break;
					case 1: $describe="<font color=red>VEHICLE</font>"; break;
					case 3: $describe="<font color=red>SUPPLIER</font>"; break;
					case 7: $describe="<font color=red>COMPANY</font>"; break;
				        case 8: $describe="<font color=red>DEPOT</font>"; break;
					case 9: $describe="<font color=red>'OTHER' PLACE</font>"; break;
				} // switch
				if (!is_numeric($newm))
					$newm=$sfound[2];	
				echo "<td bgcolor=yellow>M$sfound[2]</td><td bgcolor=yellow><b>$actual_name[$partserial]</b></td><td bgcolor=yellow>";
//<b>Old Part Serial (ICG# Removing): <input name=olds$ps"."_$partserial"."_$sfound[2]"."_$sfound[0]"."_$sfound[1]' size=6> / Missing:<input type=checkbox value=Y name=miss$ps"."_$partserial"."_$sfound[2]"."_$sfound[0]"."_$sfound[1]'>

			// NEW CODE
		if ($sfound[6]!="N") 
		{
       		        echo "<select name='miss$ps"."_$partserial"."_$sfound[2]"."_$sfound[0]"."_$sfound[1]' >";
		
	                echo makeselect2($oldpart_options, $newo);
                	echo "</select>\n";
		}
                if (is_numeric($newm)) {
                  ora_parse($cursor,"select serialize from stk_parts where serial=$newm and serialize='Y'");
                  ora_Exec($cursor);
                  if (ora_Fetch($cursor)) {
                        echo "<select name='olds$ps"."_$partserial"."_$sfound[2]"."_$sfound[0]"."_$sfound[1]' ><option value=''>Select old part to remove";
			echo "<option value=-1>Part removed needs an ICG number (Click New ICG)";
			if ($newm==9336 || $newm==9337) {
				$partx = "A.ipartno in (9336,9337)";
			}
			else
				$partx = "A.ipartno=$newm";
                        ora_parse($cursor, "select C.serial, C.track , B.jobcardserial, to_char(B.jobopendate,'DD Mon YYYY'), to_char(B.jobclosedate,'DD Mon YYYY'), C.mfr_serial from move_jobs B, stk_serialass C, stk_serialtrack A  where C.serial=A.serial  and $partx and A.location=to_char(B.jobcardserial) and B.unitserial=".$job["UNITSERIAL"]);
                        ora_exec($cursor);
                        while (ora_fetch_into($cursor, $olddt)) {
                                echo "<option value=$olddt[0]";
                                if ($newp==$olddt[0])
                                        echo " SELECTED";
                                echo ">ICG$olddt[1] MOVE$olddt[2] ($olddt[3] - $olddt[4]) $olddt[5]</option>\n";
                                unset($olddt);
                        }
			$misc_counter++;
                        echo "</select>\nor <input id=z$misc_counter name='oldz$ps"."_$partserial"."_$sfound[2]"."_$sfound[0]"."_$sfound[1]' size=8 onkeyup=\"console.log(this.value); document.getElementById('icg$misc_counter').style.display='block'; document.getElementById('icg$misc_counter').src='captureparts2.phtml?stage=115&iframe=Y&barcode='+document.getElementById('z$misc_counter').value; \"><iframe style='display: none' id=icg$misc_counter height=300px width=500px></iframe> or <button onclick=\"makeicg=window.open('requestorder.phtml?stage=71&backref=z$misc_counter','makeicg','top=10,left=10,width=500,height=500'); makeicg.focus(); return false;\">New ICG</button>";
                  }
                }


				echo "</td><td bgcolor=yellow>$sfound[3]</td><td bgcolor=yellow>1</td><td bgcolor=yellow>$describe $sfound[1]</td><td bgcolor=yellow align=right>".sprintf("%.2f",$sfound[5])."</td><td bgcolor=yellow><input type=submit name='pssc$ps"."_$partserial"."_$sfound[2]"."_$sfound[0]"."_$sfound[1]' style='background:yellow'  value=Add></tr>";

			} 
			if ($serialfound==0)
				 echo "<tr><td colspan=20><font color=red>Sorry, but $newi was not found in our system</font></td></tr>";

		}elseif (sizeof ($args)>0) {
			// use other search criteria
			$or=implode (" or ",$args);
			ora_parse($cursor,"select A.depot, A.quantity from stk A, stk_parts B, depots C where A.depot=C.depot_code and C.chassis_depot='Y' and A.i_partno=B.serial and ($or)");
			ora_exec($cursor);
			while (ora_fetch($cursor)) {
				echo "<tr><td colspan=8 bgcolor=lightpink align=right><font color=red><b>Please give PRIORITY to the ".getdata($cursor,1)." in Chassis Store ".getdata($cursor,0)."</td></tr>";
			}
			
			$qry="select B.description,B.serialize,A.*,B.must_return from stk A, stk_parts B where A.depot in ($depot_list) and A.i_partno=B.serial and ($or)";
			if (getenv("REMOTE_USER")=="xKeith")
			echo "$qry<bR>";
			ora_parse($cursor,$qry);
			ora_exec($cursor);
			$searchresults=0;
			while (ora_fetch_into($cursor,$stk,ORA_FETCHINTO_ASSOC)) {
				if (isset($wildcard_depots[chop($stk["DEPOT"])])) {
			
					if (substr($depot,0,2) != substr($stk["SHELF"],0,2)) {
						unset($stk);
						continue;
					}
				}
				$searchresults++;
				$compressed=$stk["I_PARTNO"].",".trim($stk["SHELF"]).",".$stk["SCOL"].",".trim($stk["DEPOT"]).",$ps";

				if ($stk["SERIALIZE"]=="Y")
				{
					$compressedu=str_replace(",","_",$compressed);
				
					$warning="<a onclick=\"icg_popup('$compressedu'); return false;\"><font color=red>Click to see ICG Numbers</font></a>";
				}
				else
					$warning="";
				if (isset($mpartfind[$stk["I_PARTNO"]])) {
					$stk["DESCRIPTION"].=" <font size=1><i>(Mfr:".$mpartfind[$stk["I_PARTNO"]].")</i></font>";	
				}
				echo "<Td bgcolor=yellow><b>M".$stk["I_PARTNO"]."</td><td bgcolor=yellow></td><td bgcolor=yellow><b>";
				if ($stk["SERIALIZE"]!="Y" && $stk["MUST_RETURN"]!="N")
                                {
					echo "<select name=miss_$ps"."_$compressed>".makeselect2($oldpart_options)."</select>".$stk["MUST_RETURN"]."#";
				}
				echo "$warning</td><td bgcolor=yellow><b>".$stk["DESCRIPTION"];
				 ora_parse($cursor2,"select /*+FIRST_ROWS(1)*/ to_char(A.when,'DD Mon YYYY') when,A.who,A.quantity,A.location from stk_movement A where A.lcode=4 and A.when>CURRENT_TIMESTAMP-365 and A.quantity>0 and A.ipartno=".$stk["I_PARTNO"]." and A.location in (select to_char(jobcardserial) from move_jobs where jobopendate>CURRENT_TIMESTAMP-500 and jobcardserial!=$psjobserial and unitserial=".$job["UNITSERIAL"].") order by A.when desc");
	                        ora_exec($cursor2);
       		                if (ora_fetch_into($cursor2, $last_fitted, ORA_FETCHINTO_ASSOC)) {
                    	            echo " <b><font color=magenta>LAST ISSUED</b> ".$last_fitted["WHEN"]." on J/C ".$last_fitted["LOCATION"]." - ".$last_fitted["WHO"];
				     if (ora_fetch_into($cursor2, $last_fitted, ORA_FETCHINTO_ASSOC)) {
       		                             echo " <b><font color=magenta>PREVIOUS ISSUE</b> ".$last_fitted["WHEN"]." on J/C ".$last_fitted["LOCATION"]." - ".$last_fitted["WHO"];
					}

                       		 }
				if ($stk["HIDE_QUANTITY"] == "Y")
					$qtydisp = "Hidden";
				else
					$qtydisp = $stk["QUANTITY"];	
				echo "</td><td bgcolor=yellow align=center><b>".$qtydisp."</td><td bgcolor=yellow><b>".$stk["DEPOT"]."</b> ".trim($stk["SHELF"]).$stk["SCOL"]."</td><td bgcolor=yellow align=right>@".$stk["AVE_PRICE"]."</td><td bgcolor=yellow>";
				if ($stk["SERIALIZE"]!="Y")
				{
					if ($newq<=$stk["QUANTITY"])
						$addq=$newq;
					else			
						$addq=$stk["QUANTITY"];

					if (!is_numeric($addq))
						$addq=1;
					if ($stk["I_PARTNO"]=="6105" || $stk["I_PARTNO"]=="17412" || $stk["I_PARTNO"]=="18933") {
						$addq=1;	
						echo "Seal: <input name=seal_$ps"."_$compressed size=5><BR>";
					}
					if ($stk["QUANTITY"]>0)
						echo "<input type=submit name='confirm_$ps"."_$compressed' style='background: yellow' value='Add $addq'>";
				}
				echo "</td></tr>";
				unset($stk);
			} // while
			if ($searchresults==0)
			{
				echo "<tr><td colspan=20><b><font color=red>Sorry, your search didnt find anything. Parts NOT in stock that  match show below</font></b></td></tr>";
			   	$qry="select B.serial,B.description,B.serialize from stk_parts B left join stk A on B.serial=A.i_partno where ($or)";
				ora_parse($cursor,$qry);
				ora_exec($cursor);
				while (ora_fetch($cursor))
					echo "<tr class=cell><Td>M".getdata($cursor,0)."</td><td>&nbsp;</td><Td>".getdata($cursor,1)."</td></tr>";

			}
		}
		echo "</td></tr>";
	} // edit - new line

	$line = 0;
	
	if ($ps!="NEW") {
	  if (!isset($kcur))
		$kcur=ora_open($conn);
  	  $qry="select A.*,B.track,C.description,B.mfr_serial, Y.value current_value,Z.track oldtrack, C.must_return, F.id fault_report, Z.mfr_serial OLD_MFR, C.mfr_serial_required, C.serialize from stk_picking_slip_contents A left join stk_serialass B on B.serial=A.part_serial left join stk_parts C on C.serial=A.part_number left join stk_serialtrack Y on Y.serial=A.part_serial  left join stk_serialass Z on Z.serial=A.old_part_serial left join (select part_number, part_serial, max(id) id from warranty_failure_report where ps_serial=$ps group by part_number, part_serial) F on F.part_number=A.part_number and (A.old_part_serial=F.part_serial or F.part_serial is null) where ps_number=$ps order by psc_serial desc";

	  ora_parse($cursor,$qry);
//	  echo "<tr><td colspan=10>$qry</td></tr>";
	
	  ora_exec($cursor);
	if (!isset($cursor2))
		$cursor2=ora_open($conn);
	$block_return = false;
	  while (ora_fetch_into($cursor,$data,ORA_FETCHINTO_ASSOC)) {
		$line++;
		if ($line%2==0)
			echo "<tr class=cell>";
		else
			echo "<tr class=altcell>";

		if ($data["MFR_SERIAL"]!="")
			$data["TRACK"].="<i> (Mfr:".$data["MFR_SERIAL"].")";
		elseif ($data["MFR_SERIAL_REQUIRED"] == "Y" && $data["SERIALIZE"] == "Y") {

                               $data["TRACK"] .= "<br><font color=red>*** Mfr Serial no: <input name=nmf$ps"."_".$data["PART_SERIAL"]." size=20 maxlength=30></font>";
                               $needsaserial=true;
                        }


		if ($data["SEAL"]!="")
			$data["SEAL"]="Seal ".$data["SEAL"];
	
		
		if ($data["VALUE"]=="") {
		  if ($data["SERIAL"]=="") {
			// find value...
			$binbits=explode(",",$data["BIN"]);
			ora_parse($kcur,"select ave_price from stk where depot='$binbits[0]' and shelf='$binbits[1]' and scol=$binbits[2] and i_partno=".$data["PART_NUMBER"]);
			ora_exec($kcur);
			if (ora_fetch($kcur)) {
				$data["VALUE"] = getdata($kcur,0);
			}
//			else echo "<td>NOVALUE</td>";

		  } else { 
			$data["VALUE"] = $data["CURRENT_VALUE"];
		  }
		}

	
		if (is_numeric($data["VALUE"]))
		{
			$subtotal=$data["VALUE"]*$data["QUANTITY"];
			$slipvalue+=$subtotal;
		} else
			$subtotal=0;

		if ($data["MUST_RETURN"] == "Y") {
			$fault_must = " <b><font color=red>(REQUIRED)</font></b>";
		} else {
			$fault_must = "";
		}


		if ($edit) {
			$serial=$data["PSC_SERIAL"];
			echo "<td>M".$data["PART_NUMBER"]."</td>";
                	echo "<td><B>".$data["TRACK"].$data["SEAL"]."</b></td>";
			echo "<td>";
			if ( $data["MUST_RETURN"]!="N") 
				echo "<select name=psz$ps"."_$serial>".makeselect2($oldpart_options,$data["OLD_PART_ACTION"]). "</select> ";
			$comparep=$data["PART_NUMBER"];
			echo "<b>".$data["OLDTRACK"]."</b> <i><font size=2>".current_location($data["OLDTRACK"])."</i>";
			if ($comparep!=$data["PART_NUMBER"])
			{
				echo " <font color=red style='background: yellow'>M$comparep</font> ";
				ora_parse($kcur,"select description from stk_parts where serial=$comparep");
				orA_Exec($kcur);
				ora_fetch($kcur);
				echo "<font color=red size=1>".getdata($kcur,0)."</font> ";
			}
			
                        if ($data["OLD_MFR"] != "") {
                                echo " <i><b>(Mfr: ".$data["OLD_MFR"].")</b></i> ";
                        } elseif ($data["MFR_SERIAL_REQUIRED"] == "Y" && $data["SERIALIZE"] == "Y") {

				echo "<br><font color=magenta>*** Mfr Serial no: <input name=nmf$ps"."_".$data["OLD_PART_SERIAL"]." size=20 maxlength=30></font>";
//				$needsaserial=true;
			}

			echo "</td>"; // old part
                	echo "<td><font size=2>".trim($data["DESCRIPTION"]);

			if (isset($data["NEW_MFR_SERIAL"]) && $data["NEW_MFR_SERIAL"] != "") {
                                echo " <b>SERIAL ".$data["NEW_MFR_SERIAL"]."</b> ";
                        } 


			ora_parse($cursor2,"select /*+FIRST_ROWS(1)*/ to_char(A.when,'DD Mon YYYY') when,A.who,A.quantity,A.location from stk_movement A where A.lcode=4 and A.when>CURRENT_TIMESTAMP-365 and A.quantity>0 and A.ipartno=".$data["PART_NUMBER"]." and A.location in (select to_char(jobcardserial) from move_jobs where jobopendate>CURRENT_TIMESTAMP-500 and jobcardserial!=$psjobserial and unitserial=".$job["UNITSERIAL"].") order by A.when desc");
			//echo "test1 $psjobcard";
			ora_exec($cursor2);
			if (ora_fetch_into($cursor2, $last_fitted, ORA_FETCHINTO_ASSOC)) {
				echo " <b><font color=magenta>LAST ISSUED</b> ".$last_fitted["WHEN"]." on J/C ".$last_fitted["LOCATION"]." - ".$last_fitted["WHO"];
				if (ora_fetch_into($cursor2, $last_fitted, ORA_FETCHINTO_ASSOC)) {
                                echo " <b><font color=magenta>PREVIOUS ISSUE</b> ".$last_fitted["WHEN"]." on J/C ".$last_fitted["LOCATION"]." - ".$last_fitted["WHO"];
                                
                        	}
			}



			echo "</td>";
			if ($data["PART_SERIAL"]!="")
				echo "<td>1</td>";
			else
	                	echo "<td><input name=psq$ps"."_$serial size=3 value='".$data["QUANTITY"]."'></td>";
                	echo "<td>".$data["BIN"]."</td>";
			echo "<td align=right>".sprintf("%.2f", $subtotal)."</td>";
                	echo "<td>";
//			if ($data["PART_SERIAL"]=="")
				echo "<input type=submit name=ps_change_$serial value='Change'> ";
			echo "<input type=submit style='background:  lightpink' name=ps_delete_$ps"."_$serial value='Delete'></td>";
                	echo "</tr>";
		} else { // display
			$serial=$data["PSC_SERIAL"];
                        echo "<td>M".$data["PART_NUMBER"]."</td>";
                        echo "<td><b>".$data["TRACK"].$data["SEAL"]."</b></td>";
			$comparep=$data["PART_NUMBER"];

	                echo "<td><font size=1><i>";
			if (isset($oldpart_disp[$data["OLD_PART_ACTION"]]))
				echo $oldpart_disp[$data["OLD_PART_ACTION"]];
			echo "</i></font> <b>".$data["OLDTRACK"]."</b> <i><font size=2>".current_location($data["OLDTRACK"])."</i>";
			if ($comparep!=$data["PART_NUMBER"])
                                echo " <font color=red style='background: yellow'>M$comparep</font> ";

			// NEW: FAULT REPORT
			if (is_numeric($data["FAULT_REPORT"])) {
				echo "<a href=fault_report.phtml?id=".$data["FAULT_REPORT"].">View/Edit Fault report $fault_must</a>";
			} else {
				if ($fault_must != "")
					echo "<a href=fault_report.phtml?jc=$jobcardserial&pr=$req&ps=$ps&pn=".$data["PART_NUMBER"]."&part_serial=".$data["OLD_PART_SERIAL"]."&makenew=Y><font color=red>CREATE FAULT REPORT</font> $fault_must</a>";
			}
			echo "</td>"; // old part


                        echo "<td><font size=2>".trim($data["DESCRIPTION"]);
			ora_parse($cursor2,"select /*+FIRST_ROWS(1)*/ to_char(A.when,'DD Mon YYYY') when,A.who,A.quantity,A.location from stk_movement A where A.lcode=4 and A.when>CURRENT_TIMESTAMP-365 and A.quantity>0 and A.ipartno=".$data["PART_NUMBER"]." and A.location in (select to_char(jobcardserial) from move_jobs where jobopendate>CURRENT_TIMESTAMP-500 and jobcardserial!=$psjobserial and unitserial=".$job["UNITSERIAL"].") order by A.when desc");
                        ora_exec($cursor2);
                        if (ora_fetch_into($cursor2, $last_fitted, ORA_FETCHINTO_ASSOC)) {
                                echo " <b><font color=magenta>LAST ISSUED</b> ".$last_fitted["WHEN"]." on J/C ".$last_fitted["LOCATION"]." - ".$last_fitted["WHO"];
				ora_exec($cursor2);
                        	if (ora_fetch_into($cursor2, $last_fitted, ORA_FETCHINTO_ASSOC)) {
                               	 echo " <b><font color=magenta>PREVIOUS ISSUE</b> ".$last_fitted["WHEN"]." on J/C ".$last_fitted["LOCATION"]." - ".$last_fitted["WHO"];

                        	}
                        }

			echo "</td>";
                        echo "<td align=right>".$data["QUANTITY"]."</td>";
                        echo "<td><b>".$data["BIN"]."</b></td>";
			echo "<td align=right>".sprintf("%.2f", $subtotal)."</td>";

			echo "<td>&nbsp;</td>";
			//echo "<td><pre>";
			//print_r($data);
		
                        echo "</tr>";
		}

		if ($data["OLD_PART_ACTION"] == "S" || $data["OLD_PART_ACTION"] == "D" ) {
			$specialflag = true;
		}

		if (!is_numeric($data["FAULT_REPORT"]) && $data["MUST_RETURN"]=="Y") {
			$block_return = true;
		}

		unset($data);
	  } //while
	} // show line items
	$slipvalue=sprintf("%.2f",$slipvalue);
	echo "</table>";
	if ($ps!="NEW")
		echo "<b>TOTAL VALUE: $slipvalue";
	echo "<br>";
	if ($line==0 && $ps!="NEW")
		echo "<input type=submit name=psdelete_$ps value='Delete this picking slip'><bR>";

	if ($edit && $ps!="NEW" && $line>0)
	{
		if ($hanging)
			echo "<font color=red>Please allocate a jobcard first</font><br>";
		elseif (isset($needsaserial) && $needsaserial) {
			echo "<font color=red>Please enter a manufacturer serial number first</font><bR>";
		}
		else
			echo "I confirm the old parts have been returned to me and above info is 100% correct <input type=checkbox onclick=\"document.getElementById('psclose_$ps').disabled=!this.checked;\"><input type=submit id=psclose_$ps disabled name=psclose_$ps value='Finalize this picking slip'><Br>";
	}
	elseif ($received=="" && $ps!="NEW" && $finalized!="") 
	{

                $needmaster=false;
                $iam_master=false;
                $jobdepot=chop($job["DEPOT"]);
                $smasters=array();
//              echo "Checking $slipvalue on $jobdepot<br>";
                if ($slipvalue>=5000 || $specialflag) {
                        $mfound=false;
			if ($specialflag) {
				$masterx=" and A.senior='Y' ";
			} else {
				$masterx="";
			}
                        ora_parse($cursor,"select B.username from pick_slip_masters A, user_Details B where A.depot in ('$jobdepot','ALL') $masterx and A.user_serial=B.user_serial and B.is_current in ('Y','L')");
                        ora_exec($cursor);
                        while (ora_fetch($cursor)){
				$mastername=getdata($cursor,0);
                                $smasters[]="<a href='partsrequest.phtml?jobcardserial=".$jobcardserial."&req=$req&ps=$ps&master=$mastername'>$mastername</a>";
                                if (!$needmaster) {
                                        $needmaster=true;
                                }
                                if (getenv("REMOTE_USER")==$mastername)
                                        $iam_master=true;
                        }
			if (!$needmaster && $specialflag) {
				mail("keith@intercape.co.za","No senior master for PS","PS $ps has no senior master ($jobdepot) MOVE$jobcardserial");

				ora_parse($cursor,"select B.username from pick_slip_masters A, user_Details B where A.depot in ('$jobdepot','ALL') and A.user_serial=B.user_serial and B.is_current in ('Y','L')");
	                        ora_exec($cursor);
	                        while (ora_fetch($cursor)){
	                                $mastername=getdata($cursor,0);
	                                $smasters[]="<a href='partsrequest.phtml?jobcardserial=".$jobcardserial."&req=$req&ps=$ps&master=$mastername'>$mastername</a>";
	                                if (!$needmaster) {
	                                        $needmaster=true;
	                                }
	                                if (getenv("REMOTE_USER")==$mastername)
	                                        $iam_master=true;
	                        }
			} elseif(!$needmaster) {
				mail("keith@intercape.co.za","No master for PS","PS $ps has no master (MOVE$jobcardserial / $jobdepot)");
			}
                }

		if (isset($_GET['printslip']) && $_GET['printslip']==$ps) {
			echo "<br><br><br>______________________________________________________<Br>(Stock Picked - Sign & print name)<BR>";
			 echo "<br><br><br>______________________________________________________<Br>(Stock Received - Sign & print name)<BR>";

			echo "<script> window.print(); </script>";	
			echo "<input type=button onclick='window.close();' value='Click here to continue'>";
			exit;
		}
		if ($rights["STOREMAN"]) 
		{
			ora_parse($cursor,"select jobcardserial from stk_picking_slip where ps_number=$ps and pr_number=$req");
			ora_Exec($cursor);
			ora_Fetch($cursor);
			$psjobcard=getdata($cursor,0);
			if ($psjobcard=="")
				$psjobcard=$jobcardserial; // default 

			$qry="select jobcardserial, jobopendate, jobclosedate, auth_date, auth from move_jobs where jobcardserial=$psjobcard or (unitserial=".$job['UNITSERIAL']." and auth_date is null and jobopendate>=to_date('".$job['OPENDATE']."','YYYYMMDDHH24MISS')  and depot='".$job['DEPOT']."') order by jobcardserial";
//////			echo "$qry<bR>";
			ora_parse($cursor,$qry);
			$otherjobsfound=0;
			$otherjobs=false;
			$otherjobdata=array();
			ora_exec($cursor);
			while (ora_fetch_into($cursor,$otherj,ORA_FETCHINTO_ASSOC)) {
				$otherjobdata[]=$otherj;
				if ($otherj['JOBCARDSERIAL']!=$psjobcard)
					$otherjobs=true;
				$otherjobsfound++;
				unset($otherj);
			}
			if ($otherjobs) {
				
			   echo "Jobcard:<select name=newjobcard$ps>";
			   reset($otherjobdata);
			   while (list($jobkey,$otherj)=each($otherjobdata)) {

				echo "<option value=".$otherj['JOBCARDSERIAL'];
				if ($otherj['JOBCARDSERIAL']==$psjobcard)
					echo " SELECTED";
				echo ">".$otherj['JOBCARDSERIAL']." ".$otherj['JOBOPENDATE'];
				if ($otherj['JOBCLOSEDATE']!="")
					echo " Closed ".$otherj['JOBCLOSEDATE'];
				if ($otherj['AUTH_DATE']!="" || $otherj['AUTH']=="Y")
                                        echo " ALREADY COSTED";
				$lastj=$otherj['JOBCARDSERIAL'];
			   }  // while

	  		  echo "</select><input type=submit value='Go'>";
			  if ($psjobcard!=$lastj)	
			  {
				if ($job["AUTH_DATE"]!="" || $job["AUTH"]=="Y" || $hanging) 
					echo "<font style='background: lightpink' color=red>&lt;&lt;==<b>MUST BE CHANGED</b></font>";	
				else
					echo "<font style='background: yellow' color=red><b>&lt;&lt;==Think about changing this</font>";
//				echo "AD:".$job["AUTH_DATE"]."!";
			  }
			  echo "<p>";
			}
			if (!$needmaster)
				echo "<a href='partsrequest.phtml?printslip=$ps&jobcardserial=$jobcardserial&req=$req' target=print$ps>Print Picking Slip</a> / ";
			echo "<input type=submit name=psunclose_$ps value='UN-Finalize this picking slip'> / ";
			
		}

		if ($needmaster && $approved_by=="")
		{
			if ($iam_master) {
				echo " <input type=submit name=psauth_$ps value='APPROVE' style='background:lightgreen'>";

			} else {
				echo "<font color=magenta>Needs Authorization</font> <font size=1>".implode("/",$smasters)." Click a name to send email</font>";
			}
		}
		elseif ($block_return) {
			echo "<font color=red>Please submit fault report(s) before receiving part(s)</font><bR>";
		} else
			echo " <input type=submit name=psreceived_$ps value='I have received these parts' style='background:lightgreen'>";
		echo "<br>";
		
	}
	echo "<hr>";

	return $slipvalue;
} // show_picking_slip

////////////////////////////
	function error_pop($str) {

		echo "<font color=red><B>ERROR:</b> $str</font><br>";
		$str=str_replace("'","",$str);
		echo "<script> alert('$str'); </script>";

	} // error_pop

//////////////////////////////////


	function update_entry($jobcardserial,$req) {
		global $cursor, $entry, $rights, $test_system, $_POST;

		$error=false;

		$us=getuserserial();

		$newnotes=trim($_POST['newnotes']);
		$newnotes=str_replace("'","",$newnotes);
		if ($newnotes!="") {
			$qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'$newnotes')";
			ora_parse($cursor,$qry);
			ora_exec($cursor);
		}

		if (isset($_POST["add_order"])) {
			$qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'FLAGGED AS ON ORDER')";
                        ora_parse($cursor,$qry);
                        ora_exec($cursor);
			$qry="update move_jobs_part_requests set on_order_flag='Y' where pr_serial=$req";
                        ora_parse($cursor,$qry);
                        ora_exec($cursor);
		}

		if (isset($_POST["remove_order"])) {
                        $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'REMOVED ON ORDER STATUS')";
                        ora_parse($cursor,$qry);
                        ora_exec($cursor);
                        $qry="update move_jobs_part_requests set on_order_flag='N' where pr_serial=$req";
                        ora_parse($cursor,$qry);
                        ora_exec($cursor);
                }


		if ($entry["APPROVED"]=="U" && $_POST["request"]!="") {
			$text=trim($_POST["request"]);
	                $text=str_replace("'","",$text);
			if ($text!="") {
				if ($rights["SUPERVISOR"] && $entry["CAPTURED_BY"]!=$us)
					$qry="update move_jobs_part_requests set final_text='$text' where pr_serial=$req";
				else
					$qry="update move_jobs_part_requests set request_text='$text', captured_by=$us, capture_date=CURRENT_TIMESTAMP where pr_serial=$req";

				if (!ora_parse($cursor,$qry))
					$error=true;
				elseif (!ora_exec($cursor))
					$error=true;
			}
		}
		
		if ($_POST["accept"]!="" && $rights["STOREMAN"]  && $entry["APPROVED"]=="Y") {
			$qry="update move_jobs_part_requests set accepted_by=$us, accepted_date=CURRENT_TIMESTAMP where pr_serial=$req";
//                      echo "$qry<bR>";
                        if (!ora_parse($cursor,$qry))
                                        $error=true;
                                elseif (!ora_exec($cursor))
                                        $error=true;

			if ($error) {
				echo "<hr><font color=red><b>AN ERROR OCCURRED</b></font><hr>";	
				$error=false;
			} else {
				       $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'Accepted/Took Ownership')";
		                        ora_parse($cursor,$qry);
               			         ora_exec($cursor);

			}

		}

		if  ( (isset($_POST["submit"]) && $_POST["submit"]=="Revoke") && ($rights["SUPERVISOR"]||$rights["REVOKE"] ) && $entry["APPROVED"]=="Y") {
			// first , check for picking slip...

			ora_parse($cursor,"select 'x' from stk_picking_slip A, stk_picking_slip_contents B where A.ps_number=B.ps_number and pr_number=$req");
			ora_exec($cursor);
			if (ora_Fetch($cursor)) {
				echo "<hr><font color=red><b>SORRY, Cannot revoke, as there is already a picking slip</b></font><hr><script> alert('SORRY, Cannot revoke as there is already a picking slip'); </script>";
			} else {
				$qry="update move_jobs_part_requests set approved_by=$us, approved_date=CURRENT_TIMESTAMP, approved='R', approved_comments='$acomments'  where pr_serial=$req";
	//                      echo "$qry<bR>";
	                        if (!ora_parse($cursor,$qry))
                                        $error=true;
                                elseif (!ora_exec($cursor))
                                        $error=true;
			   	if (!$error) {
                                    $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'REVOKED')";
                                        ora_parse($cursor,$qry);
                                         ora_exec($cursor);
       		                }
			} // picking slip
		} // revoke

		if ( (   isset($_POST["submit"]) &&  ($_POST["submit"]=="Approve"||$_POST["submit"]=="Reject")) && $rights["SUPERVISOR"] && $entry["APPROVED"]=="U") {
			$acomments=trim($_POST["acomments"]);
			$acomments=str_replace("'","",$acomments);
			if ($_POST["submit"]=="Reject")
			{
				$reject=true;
				$newapp="N";
				$actiontext="Rejected";
			}
			else {
				$reject=false;
				$newapp="Y";
				$actiontext="Approved";
			}

			$qry="update move_jobs_part_requests set approved_by=$us, approved_date=CURRENT_TIMESTAMP, approved='$newapp' where pr_serial=$req";
//			echo "$qry<bR>";
			if (!ora_parse($cursor,$qry))
					$error=true;
                                elseif (!ora_exec($cursor))
                                        $error=true;

                        ora_parse($cursor,"select B.depot from move_jobs_part_requests A, move_jobs B where A.pr_serial=$req and A.jobcardserial=B.jobcardserial");
                        ora_exec($cursor);
                        ora_fetch($cursor);
                        $jobdepot=getdata($cursor,0);



			 $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'Set approved to $newapp. Comments: $acomments')";
                                        ora_parse($cursor,$qry);
                                         ora_exec($cursor);

// approved_comments='$acomments' 

			if ($reject && !$error) {
				echo "<Script> alert('Please speak to the mechanic to let him/her know that the request has been rejected.'); </script>";
				echo "<hr><font style='background:yellow' color=red><b>Please speak to the mechanic to let him/her know that the request has been rejected.</b></font><hr>";
			}

			if (!$error) {
				    $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'$actiontext')";
                                        ora_parse($cursor,$qry);
                                         ora_exec($cursor);

			}

			if (!$error && !$reject) {
                                // send notifications:
                                $myu=getenv("REMOTE_USER");
                                        $storemen=0;
                                        echo "<font color=magenta><b>Emailing storemen in ".$jobdepot."</b> ";
//                                        ora_parse($cursor,"select A.username, A.email from user_details A, user_pages B, hc_people C  where A.user_serial=B.user_serial And  c.PERSON_SERIAL=a.USER_SERIAL AND c.POSITION LIKE '%STORE%' and B.page_name='MOVE_STOCK' and A.is_current in ('Y','L')  and A.staff_member='Y' and A.branch='$mybranch'");
					ora_parse($cursor,"select A.username, A.email from user_details A, hc_people C , stores_alerts T
where  c.PERSON_SERIAL=a.USER_SERIAL and C.person_serial=T.person_serial  and A.is_current in ('Y','L')  and A.staff_member='Y' and (T.depot='".$jobdepot."' or T.depot='ALL' )");

                                        ora_exec($cursor);
                                        $femails=array();
                                        while (ora_Fetch($cursor)) {
                                                $fun=getdata($cursor,0);
                                                $femail=getdata($cursor,1);
                                                if ($fun=="Keith")
                                                        continue;
                                                $storemen++;
                                                if ($femail=="")
                                                        $femail=$fun."@cavmail.co.za";
                                                elseif (!strstr($femail,"@"))
                                                        $femail.="@cavmail.co.za";
                                                echo "$fun ($femail) - ";
                                                $femails[]=$femail;
                                        }

                                        //TEST
/*
                                        unset($femails);
                                        $femails[]="keith@intercape.co.za";
                                        $femails[]="wessel@intercape.co.za";
*/
                                        //END TEST

                                        if ($storemen==0)
                                                echo "</font><font color=red>NONE!!!";
                                        else
                                        {
                                                echo "email to ".implode(",",$femails)."<Br>";
						 if ($test_system)
                                                   echo "<b>TEST SYSTEM - not mailing</b><Br>";
                                                else
                                                mail (implode(",",$femails),"Parts Request PR$req",getenv("REMOTE_USER")." has just authorized a parts request.  https://secure.intercape.co.za/move/partsrequest.phtml?jobcardserial=$jobcardserial&req=$req");
                                        }
                                        echo "</font>";

                                // done with notifications!

			} // notifications
		}

		return !$error;	
	} // update_entry


/////////////////////////////

	function show_list($mode,$jobcardserial="") {
		global $cursor,$depot,$depot_list,$job,$cookiedepot;
	
		if ($jobcardserial!="" && $job["JOBCLOSEDATE"]=="" )
			echo "<a href=partsrequest.phtml?req=NEW&jobcardserial=$jobcardserial>Click here to add a <u>new parts request</u> for jobcard $jobcardserial</a><bR>";
	
		switch ($mode) {
			case "U": $where="and A.approved='U'" ; break;
			case "I": $where="and A.approved='Y' and A.received_date is null"; break;
			case "O": $where="and A.approved='Y' and A.received_date is null and on_order_flag='Y' "; break; 
			case "R": $where="and A.approved='Y' and A.received_date is null"; break;
			case "H": $where="and A.jobcardserial<0"; break;
			case "A": $where="and A.received_date is null"; break;
			case "L":
			default : $where="and A.capture_date>CURRENT_TIMESTAMP-60";

		} // switch

		echo "<table border=1>";
		echo "<tr class=title><td>Jobcard</td><td>PR#</td><td>Requestor</td><td>Parts Requested</td><td><font color=green>Approved</font> / <font color=red>Rejected</font> / <font color=magenta>Revoked</font></td><td>Owner</td><td>Final Close-Off</td><td>P/Slip Value</td></tr>";
		if ($jobcardserial=="") 
		{
			if ($mode=="I" || $mode=="O" || $mode=="R") {
				$qry="select  case when accepted_by is null then 'Y' else 'N' end isit,extract(day from CURRENT_TIMESTAMP-accepted_date) days_back,A.jobcardserial,E.code,B.username cap_user, U.username app_user, F.username accepted_user, A.*  from move_jobs_part_requests A join move_jobs D on  D.jobcardserial=abs(A.jobcardserial) left join user_details B on A.captured_by=B.user_serial left join user_details U on A.approved_by=U.user_serial left join vehicles E on E.serial=D.unitserial and D.type=1 left join user_details F on F.user_serial=A.accepted_by where D.depot in ($depot_list) $where order by 1 desc,A.pr_serial";

			} else {
				$qry="select A.jobcardserial,E.code,B.username cap_user, U.username app_user, F.username accepted_user, A.*  from move_jobs_part_requests A join move_jobs D on  D.jobcardserial=abs(A.jobcardserial) left join user_details B on A.captured_by=B.user_serial left join user_details U on A.approved_by=U.user_serial left join vehicles E on E.serial=D.unitserial and D.type=1 left join user_details F on F.user_serial=A.accepted_by where D.depot in ($depot_list) $where order by A.pr_serial";
			}
//			echo "$qry<br>";
		}
		else {
			if (!is_numeric($jobcardserial)) {
				echo "<font color=red>ERROR! No Job card serial</font>";
				ora_rollback($conn);
				exit;
			}
			ora_parse($cursor,"select B.code,A.unitserial from move_jobs A, vehicles B where A.unitserial=B.serial and A.type=1 and A.jobcardserial='$jobcardserial'");
			ora_exec($cursor);
			if (ora_fetch($cursor))
			{
				$code=getdata($cursor,0);
				$unitserial=getdata($cursor,1);
			}
			else {
				$code="";
				$unitserial=-1;
			}
			$qry="select jobcardserial, '$code' code, B.username cap_user, U.username app_user, A.*  from move_jobs_part_requests A left join user_details B on A.captured_by=B.user_serial left join user_details U on A.approved_by=U.user_serial where jobcardserial in (select jobcardserial from move_jobs where auth_date is null and (auth='N' or auth is null) and type=1 and unitserial=$unitserial and depot='$cookiedepot' union select $jobcardserial from dual  ) union select jobcardserial, '$code' code, B.username cap_user, U.username app_user, A.*  from move_jobs_part_requests A left join user_details B on A.captured_by=B.user_serial left join user_details U on A.approved_by=U.user_serial where pr_serial in (select pr_number from stk_picking_slip where jobcardserial=$jobcardserial) or jobcardserial=$jobcardserial or jobcardserial=-$jobcardserial order by 5"; // NB: Tuned from 200k buffer gets to 600 buffer gets using the unions 
			echo "<b>Showing ONLY Jobcard MOVE$jobcardserial (And open jobcards for $code):</b><BR>";
		}
		   if (getenv("REMOTE_USER")=="Keith")
                                echo "EXEC: $qry<Br>";

		ora_parse($cursor,$qry);
		ora_Exec($cursor);
		$lines=0;
		while (ora_fetch_into($cursor,$data,ORA_FETCHINTO_ASSOC)) {
			$lines++;
			if ($lines%2==0)
				echo "<tr class=cell>";
			else
				echo "<tr class=altcell>";
			echo "<td><font size=2";
			if ($jobcardserial!="")
				if ( $jobcardserial*-1==$data["JOBCARDSERIAL"])
                                        echo " style='background:#FF5555'";
				elseif ( $jobcardserial!=$data["JOBCARDSERIAL"])
					echo " style='background:lightpink'";	
				else echo " style='background:yellow'";
		
			echo ">".$data["JOBCARDSERIAL"]." </font><b>".$data["CODE"]."</b></td>";
			echo "<td align=right><a href=partsrequest.phtml?jobcardserial=".$data["JOBCARDSERIAL"]."&req=".$data["PR_SERIAL"].">PR".$data["PR_SERIAL"]."</a></td>";
			echo "<td><B>".$data["CAP_USER"]."</b> <font size=2>".$data['CAPTURE_DATE']."</font></td>";
			echo "<td><font size=2>".substr($data["REQUEST_TEXT"],0,30)."...</font></tD>";
			echo "<td>";
			if ($data["APPROVED"]=="Y")
				echo "<font color=green>";
			elseif ($data["APPROVED"]=="N")
				echo "<font color=red>";
			elseif ($data["APPROVED"]=="R")
				echo "<font color=magenta>";
			echo "<B>".$data["APP_USER"]."</b> <font size=2>".$data["APPROVED_DATE"]."</font></td>";
			if ($data["RECEIVED_DATE"]=="") {
				if ($data["ON_ORDER_FLAG"]=="Y")			
					$data["RECEIVED_DATE"]="Not yet, but <font color=green>PARTS ON ORDER</font>";
			}
			 echo "<td>";
			if (isset($data["ACCEPTED_USER"]))
			{
				echo $data["ACCEPTED_USER"];
				if (isset($data["DAYS_BACK"]))
				{
					if (!isset($accepted_count[$data["ACCEPTED_USER"]]))
					{
						$accepted_count[$data["ACCEPTED_USER"]] = 1;
						$accepted_days[$data["ACCEPTED_USER"]] = 0;
					}
					else
						$accepted_count[$data["ACCEPTED_USER"]]++;
					$accepted_days[$data["ACCEPTED_USER"]]+= $data["DAYS_BACK"];
					if ($data["DAYS_BACK"]>6)
						echo "<font color=red>";
					echo " - ".$data["DAYS_BACK"]." days ago";
				}
			}
			elseif (($mode=="I" || $mode=="O" || $mode=="R") && $data["APPROVED"]=="Y")
				echo "<font color=red>NOBODY!</font>";
			echo "</td>";

			echo "<td>".$data["RECEIVED_DATE"]."</td>";
			echo "<Td align=right>".sprintf("%.2f",$data["PICK_SLIP_VALUE"])."</td>";
			echo "</tr>";	
			unset($data);	
		} // while

		if ($mode=="I" || $mode=="O" || $mode=="R") {

			echo "<div id=report style='display: none'>";
			arsort($accepted_count);
			reset($accepted_count);
			foreach ($accepted_count as $key => $val) {
				echo "$key: $val PRs (".sprintf("%.1f",$accepted_days[$key]/$val)." ave days)<bR>";
			}
			echo "</div><input type=button onclick=\"document.getElementById('report').style.display='block'; return false;\" value='Show Report'><br>";

		}
		
		echo "</table>";
		if ($lines==0)
			echo "<font color=red>None found! ($depot_list)</font>";

	} // show_list

//////////////////

function fetch_data($req) {
	global $cursor,$entry;

	ora_parse($cursor,"select  B.username cap_user, D.username app_user, E.username accepted_user, A.* from move_jobs_part_requests A left join user_details B on A.captured_by=B.user_serial left join user_details D on A.approved_by=D.user_serial left join user_details E on E.user_serial=A.accepted_by where pr_serial=$req");
        ora_exec($cursor);
        $entry=array();
        if (!ora_fetch_into($cursor,$entry,ORA_FETCHINTO_ASSOC)) {
                echo "Sorry, I could not find entry $req<br>";
                exit;
        }
	$entry["CAP_USER"]=trim($entry["CAP_USER"]);

} // fetch_data



// MAIN CODE:
$entry=array();
VV("mode");

if (!is_numeric($jobcardserial)) {
	if ($mode=="") {
		//figure out default mode
		if (AllowedFlag("MOVE_STOCK"))
			$mode="I";
		elseif (AllowedFlag("PARTS_REQ_AUTH"))
			$mode="U";
		else
			$mode="R";}
	show_list($mode);
	exit;
}
elseif (is_numeric($req)) {
	ora_parse($cursor,"select jobcardserial from move_jobs_part_requests  where pr_Serial=$req");
	ora_exec($cursor);
	ora_Fetch($cursor);
	$jobcardserial=getdata($cursor,0); // override the one in GET 
}

//managecoachinfo.phtml?stage=50&ser=3501

$hanging= ($jobcardserial<0 && is_numeric($jobcardserial)) ;
if ($hanging)
{
	$jobcardserial=abs($jobcardserial);
}

ora_parse($cursor,"select A.type,A.depot,A.jobopendate,A.jobclosedate,A.auth_date,A.unitserial,B.code,to_char(A.jobopendate,'YYYYMMDDHH24MISS') opendate, to_char(B.warranty_expires,'YYYYMMDD') warranty_expires, to_char(B.driveline_warranty_expires,'YYYYMMDD') driveline_warranty_expires,  to_char(B.body_warranty_expires,'YYYYMMDD') body_warranty_expires, warranty_km, driveline_warranty_km, body_warranty_km, km, A.jobcardserial   from move_jobs A left join vehicles B  on A.unitserial=B.serial and A.type=1  where A.jobcardserial=$jobcardserial");
ora_exec($cursor);
$job=array();
if (!ora_fetch_into($cursor,$job,ORA_FETCHINTO_ASSOC)) {
	echo "Sorry, MOVE$jobcardserial not found!";
	exit;
}
if ($job["JOBCLOSEDATE"]!="" && $_GET["req"]=="NEW") {
	echo "<hr><font color=red>Sorry, MOVE$jobcardserial was CLOSED on ".$job["JOBCLOSEDATE"]."</font>";
	exit;
}
if (isset($job["AUTH"]) && $job["AUTH"]=="Y" && $_GET["req"]=="NEW") {
        echo "<hr><font color=red>Sorry, MOVE$jobcardserial is COSTED already</font>";
        exit;
}
if ($job["AUTH_DATE"]=="Y" && $_GET["req"]=="NEW") {
        echo "<hr><font color=red>Sorry, MOVE$jobcardserial is COSTED already</font>";
        exit;
}


if ($job["TYPE"]==6) {
	//get unit serial
	ora_parse($cursor,"select track from stk_serialass where serial=".$job["UNITSERIAL"]);
	ora_exec($cursor);
	if (!ora_fetch($cursor)) {
		$job["CODE"]="Unknown item";	
	}
	$job["CODE"]=getdata($cursor,0);	
}
echo "<div id=left style='float: left'>";

if ($hanging && is_numeric($req)) {
	echo "<font color=red>This PR has been removed from the Jobcard and needs to be assigned to a new Jobcard</font><br>";
	echo "<form method=get action=partsrequest.phtml>";
	echo "<input type=hidden name=req value=$req>";
	echo "<input type=hidden name=jobcardserial value=$jobcardserial>";

	echo "<select name=newjc>\n";
	$qry="select jobcardserial, depot, jobopendate from move_jobs where type=".$job["TYPE"]." and unitserial=".$job["UNITSERIAL"]." and jobclosedate is null order by jobopendate";
	echo "<option value=''>Select a jobcard to move PR to";;

	ora_parse($cursor,$qry);
	ora_exec($cursor);
	while (ora_fetch_into($cursor,$ojinfo)) {
		echo "<option value='$ojinfo[0]'>MOVE$ojinfo[0] $ojinfo[1] Opened $ojinfo[2]</option>\n";
		unset($ojinfo);
	}
	echo "</select>\n";
	echo "<input type=submit name=unhang value='Change'>";
	echo "</form>$qry<br>";
} 
if ($req!="" && !isset($_GET["printslip"]))
{
	echo "<a href=partsrequest.phtml?jobcardserial=$jobcardserial>Go back to all part requests for MOVE$jobcardserial (".$job["CODE"].")</a><br>";
	$doframe=true;
	if ($job["TYPE"]==1)
	{
		echo "<a target=rightframe href=managecoachinfo.phtml?hidemenu=Y&stage=50&ser=".$job["UNITSERIAL"]."><font color=green>Click here to See serialized parts on ".$job["CODE"]."</font></a><bR>";
		$thisvehicle=$job["CODE"];
	}
	
}


if ($req=="NEW") {
	$entry=array();
	if (isset($submit) && $submit!="") {
		$req=create_entry($jobcardserial);
		if (is_numeric($req))
			fetch_data($req);
	}
	edit_entry($jobcardserial,$req);
	exit;
}

if (!is_numeric($req)) 
{
	show_list("J",$jobcardserial);
	exit;
} else {
	
	if ($rights["STOREMAN"] && isset($undofinal) && $undofinal=="Y") {
		ora_parse($cursor,"select 'x' from move_jobs_part_requests where received_date>CURRENT_TIMESTAMP-1 and pr_serial=$req and jobcardserial=$jobcardserial");
		ora_exec($cursor);
		if (ora_fetch($cursor)) {
		   ora_parse($cursor,"update move_jobs_part_requests set received_date=null where pr_serial=$req and jobcardserial=$jobcardserial");
		   ora_exec($cursor);
		   $us=getuserserial();
                        $qry="insert into  move_jobs_part_request_notes values (move_part_request_note.nextval,$req,CURRENT_TIMESTAMP,$us,'UNDO FINALIZATION')";
                        ora_parse($cursor,$qry);
                        ora_exec($cursor);
		} else {
			echo "<font style='background: yellow' color=red size=+2>ERROR: You cannot unfinalize after 24 hours</font><hr>";
		}
	}
	fetch_data($req);

	if (isset($deleteme) && $deleteme=="Y")
		if ($entry["CAP_USER"]==getenv("REMOTE_USER") && $entry["APPROVED"]=="U") {
			ora_parse($cursor,"delete from move_jobs_part_requests where pr_serial=$req and jobcardserial=$jobcardserial and approved='U'");
			ora_exec($cursor);
			if (ora_numrows($cursor)==1) {
				echo "Deleted! <a href='http://192.168.10.239/move/partsrequest.phtml?jobcardserial=$jobcardserial'>Click here to continue</a>";
				exit;
			}

		} // deleteme	

	if (isset($submit) || isset($accept) || isset($add_order) || isset($remove_order) || $newnotes!="")
	{
		if (update_entry($jobcardserial,$req)) 
			fetch_data($req);
	}
	
	edit_entry($jobcardserial,$req);
}
 echo "</div>";
                if ($doframe) {
                        echo "<div id=right style='float: left'><iframe name=rightframe id=rightframe width=500 height=800 src='partsrequest.phtml?req=$req&shownotes=Y'></iframe>";
                }

		

	close_oracle();
?>
</body>
</html>


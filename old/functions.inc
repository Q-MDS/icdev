<?

if (!isset($movefunctionsincdone)) {

$movefunctionsincdone=true;

require_once("../php3/sec.inc");

$last_bname="";
$last_details="";
$last_ruser="";

//Returns the amount spent total for a given request (gets the budget from there)
function getRunningTotal( $ser ) {
	global $conn;
	$tcur = ora_open( $conn );

//	$now = date( "Ym" );

	ora_parse( $tcur, "select purchase_category, company, budget_month from purchase_requests where request_no=$ser");
	ora_exec( $tcur);
	if (ora_fetch($tcur)) {

	$pcat=getdata($tcur,0);
	$comp=getdata($tcur,1);
	$now=getdata($tcur,2);

	ora_parse( $tcur, "SELECT SUM( A.total ) FROM PURCHASE_RUNNING6 A WHERE A.budget=$pcat AND A.company=$comp AND A.budget_month='$now'" );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) ) {
		$total = ora_getColumn( $tcur, 0 );
	} else $total = 1;

	} else $total=1;
	
	ora_close( $tcur );
	
	return $total;
}




function moveDebug( $str) {
	if (!file_exists("/usr/local/www/movedebug.log"))
		$outfile=fopen("/usr/local/www/movedebug.log","w+");
	else
		$outfile=fopen("/usr/local/www/movedebug.log","a");
	$username=getenv("REMOTE_USER");
	$now=date("d M y H:i");
	fputs($outfile,"$now $username: $str\n");	
	fclose($outfile);
} // moveDebug

// Returns whether a vehicle has received a Audit within a month or not

function checkIfAudited( $ser ) {
	global $conn;
	$cursor = ora_open( $conn );
	        
	$year=date('Y');
	
	$month=date('m');

        $prevMonth = $month - 1; 
        if ($prevMonth == 0) {$prevMonth = 12;$year = $year - 1;}
        $prevMonth = str_pad($prevMonth, 2, "00", STR_PAD_LEFT);
	        
        $qry="select B.serial from tyres_history A, vehicles B where A.ty_fleet_number = B.code AND B.serial = '".$ser."' AND A.ty_vehicle_position = 'lf' AND A.ty_vehicle_position = 'lf' AND A.ty_entry_date >= to_date('".$year.$prevMonth."01','YYYYMMDD') AND A.ty_entry_date <= to_date('".date('Ymd')."','YYYYMMDD')";               
        
	ora_parse($cursor, $qry);
	
	ora_exec($cursor);

	if (ora_fetch_into($cursor, $data, ORA_FETCHINTO_ASSOC)){return true;}else{return false;}
	
	unset ($data);
        
}








//Returns the current budget total for a given request (gets the budget from there)
function getBudgetTotal( $ser ) {
	global $conn,$last_bname,$last_details,$last_ruser;
	$tcur = ora_open( $conn );
	ora_parse($tcur,"select budget_month from purchase_requests where request_no=$ser");
	ora_exec($tcur);
	ora_fetch($tcur);
	$bmonth=getdata($tcur,0);
	$now=substr($bmonth,4,2).substr($bmonth,0,4);

//	$now = date( "mY" );
	ora_parse( $tcur, "SELECT A.amount,A.serial,nvl(B.description,B.reason_required),B.requested_by FROM PURCHASE_BUDGET A, PURCHASE_REQUESTS B WHERE A.rundate='$now' AND A.serial=B.purchase_category AND A.company=B.company AND B.request_no=$ser" );
	ora_exec( $tcur );
	$last_bname="Unknown";
	$last_details="";
	if( ora_fetch( $tcur ) ) {
		$total = ora_getColumn( $tcur, 0 );
		$bser = ora_getColumn( $tcur, 1);
		$last_details = ora_getColumn( $tcur, 2);
		$last_ruser = ora_getColumn( $tcur, 3);

		ora_parse( $tcur, "SELECT name FROM PURCHASE_BUDGET_NAMES WHERE serial='$bser'");
		ora_exeC( $tcur);
		if ( ora_fetch( $tcur)) {
			$last_bname = ora_getColumn( $tcur, 0);
		}
	} else $total = 1;
	
	ora_close( $tcur );
	
	return $total;
}

//Decreases the amount spent on a budget for the running total
function decreaseBudget( $pc, $company, $today, $amount ) {
	global $conn;

	$allcorrect=true; // NEW!
	$tcur = ora_open( $conn );

//	echo "<h2><b>DCB:</b> $pc, $company, $today, $amount </h2>";

	if (strlen($today)==6) {
		echo "Sorry, the system didnt send through the date properly - please contact the programmers<br>";
		ora_rollback($conn);
		exit;
	}

	if (!is_numeric($today)) {
		echo "Sorry, an error occured (no date passed to decreaseBudget - $today<br>";
		ora_rollback($conn);
		exit;

	}
	
	$amount = abs( $amount );
	//Deduct from running total on auth date
	ora_parse( $tcur, "SELECT * FROM PURCHASE_RUNNING WHERE budget=$pc AND rundate='$today' AND company=$company" );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) ) {
		if( !ora_parse( $tcur, "UPDATE PURCHASE_RUNNING SET total=total-$amount WHERE budget=$pc AND rundate='$today' AND company=$company" ) ) $allcorrect = false;
		$qused= "UPDATE PURCHASE_RUNNING SET total=total-$amount WHERE budget=$pc AND rundate='$today' AND company=$company";
	//	echo "$qused<bR>";
		if( !ora_exec( $tcur ) ) $allcorrect = false;
		if (ora_numrows($tcur)!=1)
			{
				$qused.=" : NO ROWS FOUND";
				echo "Problem updating budget<br>$qused<Br>";
				$allcorrect=false;
			}
	} else {
		$amount = -1*abs( $amount );
		if( !ora_parse( $tcur, "INSERT INTO PURCHASE_RUNNING ( budget, rundate, total, company ) VALUES ( $pc, '$today', $amount, $company )" ) ) $allcorrect = false;
		$qused="INSERT INTO PURCHASE_RUNNING ( budget, rundate, total, company ) VALUES ( $pc, '$today', $amount, $company )";
	//	echo "$qused<br>";
		if( !ora_exec( $tcur ) ) $allcorrect = false;
		$amount=abs($amount);
	}

	ora_close( $tcur );
	
	if ($allcorrect)
		moveDebug("Decreased Budget $pc for $today by R$amount (Company $company) ($qused)");
	return $allcorrect;
}

//Increases the amount spent on a budget for the running total
function increaseBudget( $pc, $company, $today, $amount ) {
	global $conn;
	$tcur = ora_open( $conn );
	$allcorrect = true;

	$amount = abs( $amount );
	//Add to running total on auth date
	ora_parse( $tcur, "SELECT * FROM PURCHASE_RUNNING WHERE budget=$pc AND rundate='$today' AND company=$company" );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) ) {
		if( !ora_parse( $tcur, "UPDATE PURCHASE_RUNNING SET total=total+$amount WHERE budget=$pc AND rundate='$today' AND company=$company" ) ) $allcorrect = false;
		$qused="UPDATE PURCHASE_RUNNING SET total=total+$amount WHERE budget=$pc AND rundate='$today' AND company=$company";
		if( !ora_exec( $tcur ) ) $allcorrect = false;
	} else {
		if( !ora_parse( $tcur, "INSERT INTO PURCHASE_RUNNING ( budget, rundate, total, company ) VALUES ( $pc, '$today', $amount, $company )" ) ) $allcorrect = false;
		$qused="INSERT INTO PURCHASE_RUNNING ( budget, rundate, total, company ) VALUES ( $pc, '$today', $amount, $company )";
		if( !ora_exec( $tcur ) ) $allcorrect = false;
	}
	
	ora_close( $tcur );

	 if ($allcorrect)
                moveDebug("Increased Budget $pc for $today by R$amount (Company $company) ($qused)");


	return $allcorrect;
}

//When an order has its purchase category changed
function changeCat( $ser, $newpc ) {
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );
	$cursor=$tcur;
	$allcorrect = true;
	
	ora_parse( $tcur, "SELECT company, purchase_category, to_char( auth_date, 'YYYYMMDD' ), cost, budget_month FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) ) {
		$company = ora_getColumn( $tcur, 0 );
		$pc = ora_getColumn( $tcur, 1 );
		$today = ora_getColumn( $tcur, 2 );
		$total = ora_getColumn( $tcur, 3 );
		$bday = ora_getColumn( $tcur, 4)."01";
	} else return false;
	
        if ($today!="") { // not authorised yet.  NB: now using budget_month
	        if( !decreaseBudget( $pc, $company, $bday, $total ) ) $allcorrect = false;
	        if( !increaseBudget( $newpc, $company, $bday, $total ) ) $allcorrect = false;
        }


	if ($allcorrect) {
	
		ora_parse( $tcur, "UPDATE PURCHASE_REQUESTS SET purchase_category=$newpc WHERE request_no=$ser" );
		if (!ora_exec( $tcur ))
			$allcorrect=false;
		else {
			// logging....
			ora_parse($cursor,"select name from purchase_budget_names where serial=$pc");
			ora_exec($cursor);
			ora_fetch($cursor);
			$oldname=getdata($cursor,0);
			ora_parse($cursor,"select name from purchase_budget_names where serial=$newpc");
                        ora_exec($cursor);
                        ora_fetch($cursor);
                        $newname=getdata($cursor,0);
			$logline="Changed budget from $oldname to $newname";
			$logline=substr($logline,0,200);
			budget_log_it($ser,$logline);

		}
	}
	
	ora_close( $tcur );

	if (!$allcorrect)
		ora_rollback($conn);
	
	return $allcorrect;
}

function budget_log_it ($ser,$logline) {
	global $cursor, $REMOTE_USER;

        ora_parse($cursor,"insert into purchase_order_log (request_no, when, username, info) values ($ser, CURRENT_TIMESTAMP, '$REMOTE_USER', '$logline')");
        ora_exec($cursor);

} // budget_log_it

//When an order has its company changed
function changeCompany( $ser, $newcomp, $newpc ) {
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );
	
	ora_parse( $tcur, "SELECT company, purchase_category, to_char( auth_date, 'YYYYMMDD' ), cost, order_no, superceded_by, budget_month FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) ) {
		$company = ora_getColumn( $tcur, 0 );
		$pc = ora_getColumn( $tcur, 1 );
		$today = ora_getColumn( $tcur, 6 )."01"; // was 2
		$total = ora_getColumn( $tcur, 3 );
		$ono = ora_getColumn( $tcur, 4 );
		$sb = ora_getColumn( $tcur, 5 );
	} else return false;
	
	if( $ono>0 && $sb!=-2 ) { // now using budget_month
		if( !decreaseBudget( $pc, $company, $today, $total ) ) $allcorrect = false;
		if( !increaseBudget( $newpc, $newcomp, $today, $total ) ) $allcorrect = false;
	}

	if ($allcorrect) {
		ora_parse( $tcur, "UPDATE PURCHASE_REQUESTS SET company=$newcomp, purchase_category=$newpc WHERE request_no=$ser" );
		if (!ora_exec( $tcur ))
			$allcorrect=false;
	}
	
	ora_close( $tcur );

	if (!$allcorrect)
	{
                ora_rollback($conn);

		return false;
	}

	
	return true;
}

function smsMaster( $ser, $debug=0 ) {		//SMSs the masters of the category
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );

	print "<hr>";
	
	ora_parse( $tcur, "SELECT purchase_category, cost, requested_by, depot, reason_required, description FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
	ora_exec( $tcur );
	$pc = ora_getColumn( $tcur, 0 );
	$cost = ora_getColumn( $tcur, 1 );
	$rb = trim( ora_getColumn( $tcur, 2 ) );
	$dep = trim( ora_getColumn( $tcur, 3 ) );
	$reason = trim( ora_getColumn( $tcur, 4 ) );
	$desc = trim( ora_getColumn( $tcur, 5 ) );
	
	if( $reason=="" ) $reason = $desc;
	
	$serno = $ser;
	$letters = "ABCEGIJKLMOPQRTWXYZ";
	$ref="";

	print "Serial is <b>Q$serno</b>, ";

	while( $serno>0 ) {
		$idx = $serno%19;
		$ref .= $letters[$idx];
		$serno = floor($serno/19);
	}
			
	print "SMS Code: <b>$ref</b><br><br>";
	$cost = sprintf( "%.2f", $cost);

	trackSMSCall();
	
	ora_parse( $tcur, "SELECT A.username, B.phone_2, B.email FROM PURCHASE_AUTH A, USER_DETAILS B WHERE A.username=trim(B.username) AND is_current='Y' AND master='Y' AND category=$pc" );
	ora_exec( $tcur );
	while( ora_fetch( $tcur ) ) {
	    /////////////SEND SMS
	    $tel = "";
		$tel = ora_getColumn( $tcur, 1);
		$telno = "";

		$muser = ora_getColumn( $tcur, 0 );
		
		for( $a=0; $a<strlen($tel); $a++)
			if( is_numeric( $tel[$a] ) )
				$telno .= $tel[$a];
					
		$telno  = substr( $telno, 0, 10 ); // take only first number!
		$prefix = substr( $telno, 0, 3);
		
		if( ($prefix[0]=="0") && ( ($prefix[1]==8) || ($prefix[1]==7) )  && ( ($prefix[2]>1) && ($prefix[2]<5) ) ) {
			$message = "$rb requests Q$ser - code $ref at R$cost ($dep) $reason";
			print "$message --- ";
			
			if( $livesys ) {
//				mail("sms@intercape.co.za", $telno, "$message");
				if (!send_sms($telno,$message)) {
				       mail("sms@intercape.co.za","$telno",$message);
				}

			
			}
				
			trackSMS();
			print "<b>Sent SMS ($ref) to [$muser] $telno</b><br>";
	    }  else {
			print "$telno / $tel is not a cell number (Ignoring $muser)<br>";
		}
	}
		    
	print "<hr>";	    
	ora_close( $tcur );
}


function send_sms ($number, $msg) {

        $myDB="sms";
        $myServer="192.168.10.4:3306";
        $myUser="updown";
        $myPass="tellme!";

      try {
          $mysql_conn = new PDO  ("mysql:host=$myServer;dbname=$myDB",$myUser,$myPass);
          } catch ( PDOException $e){
            echo "Error connecting to Mysql on $myServer<bR>";
            echo $e->getMessage();
             return false;
        }


        if ($number[0]=="0")
                $number="+27".substr($number,1,99);

       $msg=str_replace("'","",substr($msg,0,160));
       echo strlen($qry);

        $qry= "INSERT INTO sms.sms_inbox (date_time, number, message, priority) VALUES(NOW(),'$number','$msg', 10)";
        echo "$qry\n";

        $stmt=$mysql_conn->prepare($qry);

        if ($stmt->execute())
        {
                        return true;
        } else {
                echo "Query failed\n";
                return false;
        }



} // send_sms



function trackSMSCall( $debug=0 ) {
	global $conn, $REMOTE_USER;
	$tcur = ora_open( $conn );
	
	ora_parse( $tcur, "SELECT actual FROM PURCHASE_SMS WHERE username='$REMOTE_USER'" );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) ) {
		$actual = ora_getColumn( $tcur, 0 );
		$actual++;
		
		ora_parse( $tcur, "UPDATE PURCHASE_SMS SET actual=$actual WHERE username='$REMOTE_USER'" );
		ora_exec( $tcur );
	} else {
		ora_parse( $tcur, "INSERT INTO PURCHASE_SMS VALUES ( '$REMOTE_USER', 0, 1 )" );
		ora_exec( $tcur );
	}
	
	ora_close( $tcur );
}

function trackSMS( $debug=0 ) {
	global $conn, $REMOTE_USER;
	$tcur = ora_open( $conn );
	
	ora_parse( $tcur, "SELECT sms FROM PURCHASE_SMS WHERE username='$REMOTE_USER'" );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) ) {
		$sms = ora_getColumn( $tcur, 0 );
		$sms++;
		
		ora_parse( $tcur, "UPDATE PURCHASE_SMS SET sms=$sms WHERE username='$REMOTE_USER'" );
		ora_exec( $tcur );
	} else {
		ora_parse( $tcur, "INSERT INTO PURCHASE_SMS VALUES ( '$REMOTE_USER', 1, 1 )" );
		ora_exec( $tcur );
	}
	
	ora_close( $tcur );
}

function emailMaster( $action, $ser, $auth, $reason, $debug=0) {		//Emails the masters of the category
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );

	if( $action=="AUTH" ) {		//Authorisation
		if( $auth ) {	//That went through, so we notify the user and masters
			ora_parse( $tcur, "SELECT purchase_category, order_no, cost, requested_by, approved_by FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
			ora_exec( $tcur );
			$pc = ora_getColumn( $tcur, 0 );
			$on = ora_getColumn( $tcur, 1 );
			$cost = ora_getColumn( $tcur, 2 );
			$rb = trim( ora_getColumn( $tcur, 3 ) );
			$ab = ora_getColumn( $tcur, 4 );
			
			$subject = "Request Q$ser has been authorised [B$on]";
			$message  = "Requested By: $rb\n";
			$message .= "Authorised By: $ab\n";
			$message .= "Cost: R $cost\n";
			$message .= "Reason: $reason\n\n";
			$message .= "More Info: https://secure.intercape.co.za/move/requestorder.phtml?stage=4&ser=$ser";
				
			print "<hr>Request (<b>Q$ser</b>) has been authorised (B$on)<br><br>";
			
			ora_parse( $tcur, "SELECT B.use_webmail, B.email FROM USER_DETAILS B WHERE B.username='$rb'" );
			ora_exec( $tcur );
			if( ora_fetch( $tcur ) ) {
				$sendto = "";
				$webmail = ora_getColumn( $tcur, 0 );
				$email = strtolower( trim( ora_getColumn( $tcur, 1 ) ) );
				
				if( $webmail=="Y" )
					$sendto = $rb . "@cavmail.co.za";
				else {
					$sendto = $email;

					if( $email=="" )
						$sendto = strtolower( $rb ) . "@intercape.co.za";
				}
				
				//mail( $sendto, $subject, $message );
			} else {
//				$sendto = "<b><font color=red>YOUR EMAIL ADDRESS COULD NOT BE FOUND, YOU WILL NOT GET AN EMAIL</font></b>";
			}
			
//			print "<b>YOU</b>: $sendto<br>";
			
			/*
			//Uncomment this if you want masters to receive email notification of every authorisation
			
			ora_parse( $tcur, "SELECT A.username, B.use_webmail, B.email FROM PURCHASE_AUTH A, USER_DETAILS B WHERE A.username=trim(B.username) AND master='Y' AND is_current='Y' AND category=$pc" );
			ora_exec( $tcur );
			while( ora_fetch( $tcur ) ) {
				$sendto   = "";
				$webmail  = "";
				$username = "";
				$email    = "";
	
				$username = strtolower( trim( ora_getColumn( $tcur, 0 ) ) );
				$webmail  = trim( ora_getColumn( $tcur, 1 ) );
				$email	  = strtolower( trim( ora_getColumn( $tcur, 2 ) ) );
				
				if( $webmail=="Y" )
					$sendto = $username . "@cavmail.co.za";
				else {
					$sendto = $email;
					
					if( $email=="" )
						$sendto = $username . "@intercape.co.za";
				}
				
				mail( $sendto, $subject, $message );
				print "<b>MASTER</b>: $sendto<br>";
			}
			*/
			
			//print "<font color=red size=+2>WARNING: NO EMAILS ARE SENT FOR AUTHORISATIONS ANYMORE</font>";
			print "<hr>";
		} else {		//Didn't go through, so we notify the masters for now
			ora_parse( $tcur, "SELECT purchase_category, order_no, cost, requested_by, description, reason_required FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
			ora_exec( $tcur );
			$pc = ora_getColumn( $tcur, 0 );
			$on = ora_getColumn( $tcur, 1 );
			$cost = ora_getColumn( $tcur, 2 );
			$rb = ora_getColumn( $tcur, 3 );
			$desc = ora_getColumn( $tcur, 4 );
			$rr = ora_getColumn( $tcur, 5 );
			
			$subject = "Request Q$ser has been submitted";
			$message  = "Requested By: $rb\n";
			$message .= "Cost: R $cost\n";
			$message .= "Description: $desc\n";
			$message .= "Reason: $rr\n";
			$message .= "More Info: https://secure.intercape.co.za/move/requestorder.phtml?stage=4&ser=$ser";

			print "<hr>Your request (Q$ser) has been submitted.<br><b><font color=red><b>YOU WILL NOT RECEIVE AN EMAIL UNTIL IT HAS BEEN AUTHORISED</font></b><br>Emails sent to: <br><br>";
			
			ora_parse( $tcur, "SELECT A.username, use_webmail, email FROM PURCHASE_AUTH A, USER_DETAILS B WHERE A.username=trim(B.username) AND master='Y' AND category=$pc and (is_current='Y' or is_current='L')" );
			ora_exec( $tcur );
			while( ora_fetch( $tcur ) ) {
				$sendto   = "";
	
				$username = strtolower( trim( ora_getColumn( $tcur, 0 ) ) );
				$webmail  = trim( ora_getColumn( $tcur, 1 ) );
				$email	  = strtolower( trim( ora_getColumn( $tcur, 2 ) ) );
				
				if( $webmail=="Y" )
					$sendto = $username . "@cavmail.co.za";
				else {
					$sendto = $email;
					
					if( $email=="" )
						$sendto = $username . "@intercape.co.za";
				}
				
//				if( $livesys )
//					mail( $sendto, $subject, $message );
					
				print "<b>MASTER:</b>$sendto<br>";
			}
			
			print "<hr>";
		}
	}
	
	if( $action=="REJ" ) {		//It was rejected, so we authorise masters and user
		ora_parse( $tcur, "SELECT purchase_category, order_no, cost, requested_by, approved_by FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
		ora_exec( $tcur );
		$pc = ora_getColumn( $tcur, 0 );
		$on = ora_getColumn( $tcur, 1 );
		$cost = ora_getColumn( $tcur, 2 );
		$rb = ora_getColumn( $tcur, 3 );
		$ab = ora_getColumn( $tcur, 4 );
		
		ora_parse( $tcur, "SELECT name, parts, technical_report FROM purchase_budget_names WHERE serial=$pc");
		ora_Exec( $tcur);
		ora_fetch( $tcur);
		$bname = ora_getcolumn( $tcur, 0);
		$pcheck1 = getdata($tcur,1);
		$pcheck2 = getdata($tcur,2);
				
		$subject = "Request Q$ser has been rejected";
		$message  = "Requested By: $rb\n";
		$message  = "Rejected By: $ab\n";
		$message .= "Cost: R $cost\n\n";
                $message .= "Budget: $bname\n\n";
		$message .= "More Info: https://secure.intercape.co.za/move/requestorder.phtml?stage=4&ser=$ser";

		print "<hr>Request (Q$ser) has been rejected. Emails sent to: <br><br>";
		
		ora_parse( $tcur, "SELECT B.use_webmail, B.email FROM USER_DETAILS B WHERE B.username='$rb'" );
		ora_exec( $tcur );
		if( ora_fetch( $tcur ) ) {
			$sendto = "";
			$webmail = ora_getColumn( $tcur, 0 );
			$email = strtolower( trim( ora_getColumn( $tcur, 1 ) ) );
				
			if( $webmail=="Y" )
				$sendto = $rb . "@cavmail.co.za";
			else {
				if( $email!="" )
					$sendto = $email;
				else $sendto = strtolower( $rb ) . "@intercape.co.za";
			}
				
			if( $livesys  ) 
				mail( $sendto, $subject, $message );
				
		} else {
			$sendto = "<b><font color=red>YOUR EMAIL ADDRESS COULD NOT BE FOUND, YOU WILL NOT GET AN EMAIL</font></b>";
		}

		print "<b>YOU</b>: $sendto<br>";
		
		ora_parse( $tcur, "SELECT A.username, use_webmail, email FROM PURCHASE_AUTH A, USER_DETAILS B WHERE A.username=trim(B.username) AND master='Y' AND is_current='Y' AND category=$pc" );
		ora_exec( $tcur );
		while( ora_fetch( $tcur ) ) {
			$sendto   = "";

			$username = strtolower( trim( ora_getColumn( $tcur, 0 ) ) );
			$webmail  = trim( ora_getColumn( $tcur, 1 ) );
			$email	  = strtolower( trim( ora_getColumn( $tcur, 2 ) ) );
			
			if( $webmail=="Y" )
				$sendto = $username . "@cavmail.co.za";
			else {
				$sendto = $email;
				
				if( $email=="" )
					$sendto = $username . "@intercape.co.za";
			}
			
			//mail( $sendto, $subject, $message );
			print "<b>MASTER</b>: $sendto<br>";
		}
		if (strstr( strtoupper($bname), "FLEET") || $pcheck1=="Y" || $pcheck2=="Y") {
			mail ("ronniem@intercape.co.za","#".$subject,$message);
		}
		
//		print "<font color=red size=+2>WARNING: NO EMAILS ARE SENT FOR REJECTIONS ANYMORE</font>";
		print "<hr>";
	}
}

function userAmount( $company, $debug=0 ) {	//Returns costs for today for user
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );

	$total = 0;
	$today = date( "dmY" );
	$qry = "SELECT SUM(cost) FROM PURCHASE_REQUESTS WHERE order_no>=0 AND approved_by='$REMOTE_USER' AND superceded_by=-1 AND auth_date LIKE to_date('$today','DDMMYYYY') AND company=$company";
	
	if( $debug && $REMOTE_USER=="Keeith" )
		print "<hr><font size=-1>$qry</font><hr>";

	ora_parse( $tcur, $qry );
	ora_exec( $tcur );
	$total = ora_getColumn( $tcur, 0 );
	
	ora_close( $tcur );
	return $total;
}

function userLeft( $round=true, $company, $debug=0 ) {	//Returns amount user has left
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );

	$limit = 0;
	$qry = "SELECT limit FROM PURCHASE_AUTH_DAILY WHERE username='$REMOTE_USER' AND company=$company";

	if( $debug && $REMOTE_USER=="Keeith" )
		print "<hr><font size=-1>$qry</font><hr>";

	ora_parse( $tcur, $qry );
	ora_exec( $tcur );
	$limit = ora_getColumn( $tcur, 0 );
	
	ora_close( $tcur );
	
	if( $round )
		return ( round( $limit-userAmount( $company, $debug ), 2 ) );
	else return ( $limit-userAmount( $company, $deubg ) );
}

function underDailyLimit( $company, $debug=0 ) {
	if( userLeft( true, $company, $debug ) > 0 )
		return true;
	
	return false;
}

function underOrderLimit( $cost, $ser, $company, $debug=0 ) {	//Is a user under their order limit?
	if( $cost==0 )
		return true;
		
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );

	$qry = "SELECT A.order_limit, A.auth_limit, B.requested_by FROM PURCHASE_AUTH A, PURCHASE_REQUESTS B WHERE A.username='$REMOTE_USER' AND A.category=B.purchase_category AND B.request_no=$ser AND A.company=B.company AND B.company=$company";

	if( $debug && $REMOTE_USER=="Keeith" )
		print "<hr><font size=-1>$qry</font><hr>";

	ora_parse( $tcur, $qry );
	ora_exec( $tcur );
	$limit = ora_getColumn( $tcur, 0 );
	$otherlimit = ora_getColumn( $tcur, 1 );
	$rb = ora_getColumn( $tcur, 2 );
	
	ora_close( $tcur );
	
	if( $rb==$REMOTE_USER ) {
		//print "REQUEST BY $REMOTE_USER: $cost : $limit <br>";
		
		if( $cost<$limit ) {
			//print "a UNDER LIMIT: $cost < $limit ";
			return true;
		}
	} else {
		//print "REQUEST NOT BY $REMOTE_USER<br>";
		
		if( $cost<$otherlimit ) {
			//print "b UNDER LIMIT $cost < $otherlimit ";
			return true;
		}
	}
		
	//print "<br>NOT UNDER LIMIT";
	//exit;
	return false;
}

function splitOrder( $ser) {
	global $conn, $cursor;
	// basic checks
		
                // 50 through 59: split order
//                case 50: $reason = "Order not valid"; break;
//                case 51: $reason = "Order split max times already"; break;
 //               case 52: $reason = "Everything is delivered."; break;
//		case 53: $reason="Bad arguments"; break;
//		case 54: $reason="Database error": break;
	if (!is_numeric($ser))
		return -53;
	
	ora_parse($cursor,"select order_no, superceded_by, cost, autogrn from purchase_requests where request_no=$ser");
	ora_exec($cursor);
	if (!ora_fetch($cursor)) 
		return -53;
	$on=getdata($cursor,0);

	$oldtotal=getdata($cursor,2);
	$origtotal=$oldtotal;
	echo "Original order worth $oldtotal<br>";
	if (on<0 || getdata($cursor,1)!=-1 || getdata($cursor,3)=="Y")
		return -50;
	$base=floor($ser);
	$newser=$ser+0.1;
	$got=false;
	while (!$got && floor($newser)==$base) {
		ora_parse($cursor,"select 'x' from purchase_requests where request_no=$newser");
		ora_exec($cursor);
		if (ora_fetch($cursor))
			$newser=$ser+0.1;
		else
			$got=true;
	} // while
	
	
	if (floor($newser)	!=$base || !$got)
			return -51;
	
	// now prepare split data...
	unset($parts);
	unset($noparts);
	$partscnt=0;
	$nopartscnt=0;
	$newparts=0;
	$newnoparts=0;
	ora_parse($cursor,"select part_serial,sum(received) from purchase_received where delnote in ( select delnote from purchase_deliveries where request_no=$ser) and part_serial is not null group by part_Serial");
	ora_Exec($cursor);
	unset($data);
	while (ora_Fetch_into($cursor,$data)) {
		$parts[$data[0]]=$data[1];	
		$partscnt+=$data[1];
		unset($data);
	} // while
	ora_parse($cursor,"select no_part_serial,sum(received) from purchase_received where delnote in ( select delnote from purchase_deliveries where request_no=$ser) and no_part_serial is not null group by no_part_Serial");
        ora_Exec($cursor);
        unset($data);
        while (ora_Fetch_into($cursor,$data)) {
                $noparts[$data[0]]=$data[1];
                $nopartscnt+=$data[1];
                unset($data);
        } // while
	
	$gap=$newser-$ser;
	$newon=sprintf("%.1f",$on+$gap);

	$now=date("YmdHis");

	// create purchase_requests entry
	ora_parse($cursor,"insert into purchase_requests select $newser,$newon,creditor,jobcard,description,requested_by,0,purchase_category,behalf_of,reason_required,reason_Auth,urgent,approved_by,approved_behalf,modified_date,auth_Date,required,contact,superceded_By,-1,0,null,croxley,0,depot,contacted,'N',null,null,company,territory_auth,territory,autogrn,budget_month,finalised,cod,debit_order,revoke_date,please_revoke,0,is_estimate, to_date( '$now', 'YYYYMMDDHH24MISS' ),null, orig_currency, site_id, reason_accredited, reason_price, reason_stock, reason_months, reason_kms, reason_replace, reason_cnum, reason_lifespan, reason_guarantee,flag_warranty,flag_afterhours,flag_emergency from purchase_requests where request_no=$ser");
	if (!ora_Exec($cursor))
		return -54;
	
	// now copy the requests...
	$newtotal=0;
	$allcorrect=true;
	$kcursor=ora_open($conn);	
	
	ora_parse($cursor,"select * from purchase_details where request_no=$ser");
	ora_Exec($cursor);
	while (ora_Fetch_into($cursor,$data)) {
		if (!isset($parts[$data[1]]))
				$parts[$data[1]]=0;
		$got=$parts[$data[1]];
		$still=$data[3]-$got;
		if ($still>0) {
			// fix old
			ora_parse($kcursor,"update purchase_details set quantity=$got where request_no=$ser and part_serial=$data[1]");
			if (!ora_exec($kcursor))
				$allcorrect=false;
			else
				$oldtotal-=$still*$data[2];
			if (!ora_numrows($kcursor)==1) {
				$allcorrect=false;
				  echo ora_numrows($kcursor)." rows : $qry<bR>";
			}
			if ($data[6]=="")
				$data[6]="null";
	
			// set up new	
			$qry="insert into purchase_details values ($newser, $data[1], $data[2], $still, $data[4], $data[5], $data[6])";
			ora_parse($kcursor,$qry);
			if (!ora_exec($kcursor)) {
				$allcorrect=false;
				echo "$qry<bR>";
			}
			else
				$newtotal+=$still*$data[2];
		
		}
		unset($data);
	} // while	

	ora_parse($cursor,"select * from purchase_noparts where request_no=$ser");
        ora_Exec($cursor);
        while (ora_Fetch_into($cursor,$data)) {
                if (!isset($noparts[$data[4]]))
                                $noparts[$data[4]]=0;
                $got=$noparts[$data[4]];
                $still=$data[3]-$got;
                if ($still>0) {
                        // fix old
                        $qry="update purchase_noparts  set quantity=$got where request_no=$ser and serial=$data[4]";
			ora_parse($kcursor,$qry);
			
                        if (!ora_exec($kcursor)) {
			
                                $allcorrect=false;
				echo "$qry<bR>";
			}
			else
				$oldtotal-=$still*$data[2];
                        if (!ora_numrows($kcursor)==1)
			{
				echo ora_numrows($kcursor)." rows : $qry<bR>";
                                $allcorrect=false;
			}
			 // set up new
			if ($data[7]=="")
				$data[7]="null";
			if ($data[8]=="")
				$data[8]="null";

                        $qry="insert into purchase_noparts values ($newser, $still, $data[2], '$data[3]', purchase_noparts_serial.nextval, $data[5], $data[6], $data[7], $data[8], '$data[9]', '$data[10]')";
			ora_parse($kcursor,$qry);
                        if (!ora_exec($kcursor)) {
                                $allcorrect=false;
				echo "$qry<bR>";
			}
			else $newtotal+=$still*$data[2];

                }
                unset($data);
        } // while

	if ($newtotal==0) {
		ora_rollback($conn);
		return -52;
	}

	if ($allcorrect) {
		echo "New total is $newtotal oldtotal now $oldtotal<br>";
		echo "ALL CORRECT!<bR>";
		$allcorrect=false;
	}

	if (!$allcorrect) {
		echo "Query went wrong?";
		ora_rollback($conn);
		return -54;
	}

	return $newser;


	

}// splitOrder

function supercedeOrder( $ser, $docopy="N", $debug=0 ) {	//Supercede order, returns new serial if okay, else -1

	
        global $conn, $REMOTE_USER, $livesys, $cookiedepot;

	
        if ($REMOTE_USER=="")
                $REMOTE_USER=getenv("REMOTE_USER");


	if ($docopy!="Y")
		$docopy="N";

	$tcur = ora_open( $conn );
	
		
	if( !isOwner( $ser, true, $debug ) && $docopy=="N" )
	{
		// check depot
		ora_parse($tcur,"select depot from purchase_requests where request_no=$ser");
		ora_exec($tcur);
		if (ora_fetch($tcur)) {
			if (getdata($tcur,0)!=$cookiedepot) {
				echo getdata($tcur,0)." is not $cookiedepot<bR>";
				return -30;
			}
		} else
			return -30;

	}



	$allcorrect=true; // NEW!
	array( $results );


				   //0		1	  2		3	4		5	6	7		8		9		10	11	12		13			14	15	   16	   17		18	19        20	   21	 	22	23   	   24    	25	26		              27         					27
	if (!is_numeric($ser))
		return -31;
	ora_parse( $tcur, "SELECT request_no, order_no, creditor, jobcard, description, requested_by, cost, purchase_category, behalf_of, reason_required, reason_auth, urgent, approved_by, approved_behalf, modified_date, auth_date, required, contact, superceded_by, grn, exported, grn_date, croxley, highest_cost, depot, contacted, to_char( auth_date, 'YYYYMMDD' ), company, autogrn, budget_month, is_estimate, cod, debit_order, vat, orig_currency, site_id, reason_accredited, reason_price, reason_stock, reason_months, reason_kms, reason_replace, reason_cnum, reason_lifespan, reason_guarantee,'N','N','N',AUTO_RESTOCK FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
	/* 
0-25 same as table  (25=contacted)
26  authdate yyyymmdd
27 company ( normally 29)
28 autogrn  ( normally 32)
29 budget_month  (normally 33)
30 is_estimate  (normall 34)
31 cod (normallly 35)
32 debit_order (normally 36)
33 vat (normally 40)
34 orig curr
35 site_id, 
36 reason_accredited, 
37 reason_price, 
38 reason_stock, 
39 reason_months, 
40 reason_kms, 
41 reason_replace, 
42 reason_cnum, 
43 reason_lifespan, 
44 reason_guarantee,
45 'N' FLAG_WARRANTY
46 'N', FLAG_AFTERHOURS
47 'N' FLAG_EMERGENCY
48 AUTO_RESTOCK
*/
	ora_exec( $tcur );
	if( !ora_fetch_into( $tcur, $results ) )
		return -31;
		
	if( $results[1]==-1 && $docopy=="N" )
		return -32;
	elseif( $results[18]>=0 && $docopy=="N" )
		return -33;
/*
	elseif( $results[19]>=0 && $docopy=="N" )
		return -34;
*/

//	$debug=true; // TEMP TEST!
//	echo "DOcopy is $docopy<br>";
	$ungrn=0;
	if ($docopy=="N" ) {

	// NEW: Aug 2009 - check deliveries
			$ungrn=0;
                         $qry=" select /* kwdb35b */ F.part_serial,sum(F.received) from purchase_deliveries E left join purchase_received F on  E.delnote=F.delnote where request_no=$ser and part_serial is not null group by F.part_serial";
                        if ($debug)
                        echo "$qry<bR>";
                        ora_parse( $tcur, $qry);


                        ora_exec($tcur);
                        while (ora_fetch_into($tcur, $ddata)) {
                                if (!is_numeric($ddata[1]))
                                        $ddata[1]=0;
                                if ($ddata[1]!=0)
                                {
                                        $ungrn++;
                                }
                                unset($ddata);
                        }
		   $qry=" select /* kwdb35b */ abs(F.no_part_serial), sum(F.received) from purchase_deliveries E left join purchase_received F on  E.delnote=F.delnote where request_no=$ser and no_part_serial is not null group by abs(F.no_part_serial)";
	
                       if ($debug)
                        echo "$qry<bR>";
                        ora_parse($tcur,$qry);
                        ora_exec($tcur);
                        while (ora_fetch_into($tcur, $ddata)) {
                                if (!is_numeric($ddata[1]))
                                        $ddata[1]=0;
                                if ($ddata[1]!=0)
                                {
                                        $ungrn++;
                                }
                                unset($ddata);
                        }

	if ($ungrn!=0)
		return -25;

	

	echo "##";
	// check invoice total
	ora_parse($tcur,"select sum(nvl(nvl(local_amount,invoice_amount), $results[6])) from purchase_invoices where request_no=$ser");
	ora_exec($tcur);
	    if (ora_fetch($tcur)) {
			echo getdata($tcur,0);
		    if (round(getdata($tcur,0))!=0)
				return -39;
			//if (getenv("REMOTE_USER")=="Keeith")
			//	echo "select sum(nvl(invoice_amount, $results[6])) from purchase_invoices where request_no=$ser = ".getdata($tcur,0);
	    }	
	}

	//echo "UNRFP: $ungrn<br>";

        // check creditors
        if ($docopy=="N") {
                ora_parse($tcur,"select 'x' from move_invoices where request_no=$ser");
                ora_exec($tcur);
                if (ora_fetch($tcur)) {
			if (!(AllowedFlag("BUDGET"))) {
                   	     echo "Sorry, this order has already been loaded onto the creditors system<BR>";
                       	     return -38;
			} else {
				ora_parse($tcur,"select sum(amount_cents*quantity*vat) from move_invoices where request_no=$ser");
				ora_Exec($tcur);
				ora_fetch($tcur);
				$totmi=getdata($tcur,0);
				if ($totmi!=0) {
					echo "Sorry, total of invoices accepted is not zero<Br>";
					return -39;
				}
				ora_parse($tcur,"select sage_ref from purchase_invoices where inv_serial in (select serial from move_invoices where request_no=$ser)");
				ora_exec($tcur);
				$fnd1=0;
				$fnd2=0;
				while (ora_fetch($tcur)) {
					$pisage=getdata($tcur,0);
					if ($pisage=="")
						$fnd1++;
					else
						$fnd2++;
				
				}
				if ($fnd1>0 && $fnd2>0 && $fnd1!=$fnd2) {
					echo "Sorry, but not all invoices are in SAGE yet<br>";
					return -39;
				}
			
		

			}
                }

        }


		
	ora_parse( $tcur, "SELECT purchase_request_no.nextval FROM dual" );
	ora_exec( $tcur );
	$newser = ora_getColumn( $tcur, 0 );
	
	ora_commitoff( $conn );	
	
	$now = date( "dmY His" );

	$results[9]=str_replacE("'","`",$results[9]);
	$results[9]=str_replace("\\","",$results[9]);

	if (!is_numeric($results[33]))
                $results[33]="null";

/*
contents of $results:
0-25 same as table  (25=contacted)
26  authdate yyyymmdd
27 company ( normally 29)
28 autogrn  ( normally 32)
29 budget_month  (normally 33)
30 is_estimate  (normall 42)
31 cod (normallly 35)
32 debit_order (normally 36)
33 vat (normally 40)
*/

	if ($results[3]=="")
		$results[3]="null";
	
	$qry = "INSERT INTO PURCHASE_REQUESTS (REQUEST_NO,ORDER_NO,CREDITOR,JOBCARD,DESCRIPTION,REQUESTED_BY,COST,PURCHASE_CATEGORY,BEHALF_OF,REASON_REQUIRED,REASON_AUTH,URGENT,APPROVED_BY,APPROVED_BEHALF,MODIFIED_DATE,AUTH_DATE,REQUIRED,CONTACT,SUPERCEDED_BY,GRN,EXPORTED,GRN_DATE,CROXLEY,HIGHEST_COST,DEPOT,CONTACTED,INVOICE_RECV,INVOICE_REASON,INVOICE_DATE,COMPANY,TERRITORY_AUTH,TERRITORY,AUTOGRN,BUDGET_MONTH,FINALISED,COD,DEBIT_ORDER,REVOKE_DATE,PLEASE_REVOKE,RECEIVED_VALUE,VAT,SMS_SENT,IS_ESTIMATE,DATE_CREATED,GRN_DONE_BY,ORIG_CURRENCY,SITE_ID,REASON_ACCREDITED,REASON_PRICE,REASON_STOCK,REASON_MONTHS,REASON_KMS,REASON_REPLACE,REASON_CNUM,REASON_LIFESPAN,REASON_GUARANTEE,FLAG_WARRANTY,FLAG_AFTERHOURS,FLAG_EMERGENCY,AUTO_RESTOCK) VALUES ( '$newser' , -1 , $results[2] , $results[3] ,'$results[4]', '$REMOTE_USER', $results[6], '$results[7]', '$REMOTE_USER', '$results[9]', '', '$results[11]','','', to_date( '$now', 'DDMMYYYY HH24MISS' ), NULL, '$results[16]', '$results[17]', -1, -1, 0, NULL, '$results[22]', '$results[23]', '$results[24]', 'N', 'N', null, null, $results[27], null, null, '$results[28]', '$results[29]', 'N', '$results[31]', '$results[32]' , null, null, null, $results[33], 'N', '$results[30]', to_date('$now','DDMMYYYY HH24MISS'),null,'$results[34]','$results[35]','$results[36]','$results[37]','$results[38]','$results[39]','$results[40]','$results[41]','$results[42]','$results[43]','$results[44]','$results[45]','$results[46]','$results[47]','$results[48]')"; //
   //, ,'$results[48]','$results[49]','$results[50]','$results[51]','$results[52]','$results[53]','$results[54]','$results[55]','$results[56]','$results[57]','$results[58]','$results[99]'  )"; // 28 = autogrn
	echo "CREATING Q$newser<Br>";

        //$test_email = mail("russell@intercape.co.za", "Error Report", $qry);

	if( $debug || $REMOTE_USER=="Keeith" )
	{
		print "<hr><font size=-1>$qry</font><hr>";
	}


	if( !ora_parse( $tcur, $qry ) ) { echo $qry; 	return -35; }
	if( !ora_exec( $tcur ) ) return -35;
		
	if( !ora_parse( $tcur, "INSERT INTO PURCHASE_DETAILS ( SELECT '$newser', part_serial, price_each, quantity, vat, kmreading, local_price  FROM PURCHASE_DETAILS WHERE request_no=$ser )" ) ) return -35;
	if( !ora_exec( $tcur ) ) return -35;

	if( !ora_parse( $tcur, "INSERT INTO PURCHASE_NOPARTS ( SELECT '$newser', quantity, price_each, description, purchase_noparts_serial.nextval , class, vat, kmreading, local_price, sage_country, sage_cost_centre FROM PURCHASE_NOPARTS WHERE request_no=$ser )" ) ) return -35;
	if( !ora_exec( $tcur ) ) return -35;

	if ($docopy=="Y") {
	        ora_commit( $conn );
	        return $newser;
	}
	$now=date("YmdHis");
	ora_parse($tcur,"update stk_monitor_order set request_no=$newser where request_no=$ser");
	ora_exec($tcur);

	if( !ora_parse( $tcur, "UPDATE PURCHASE_REQUESTS SET superceded_by=$newser, please_revoke=null,  revoke_date=to_date('$now','YYYYMMDDHH24MISS')  WHERE request_no=$ser" ) ) return -35;
	if( !ora_exec( $tcur ) ) return -35;
		
	// move deliveries on old request into new request... (Yes, even if it hasnt been approved yet!)
	if( !ora_parse( $tcur ,"UPDATE PURCHASE_DELIVERIES SET request_no=$newser WHERE request_no=$ser" ) ) return -36;
	if( !ora_exec( $tcur ) ) return -36;

/*
	removed march 2007
//	if( !ora_parse( $tcur, "UPDATE PURCHASE_INVOICES SET request_no=$newser WHERE request_no=$ser" ) ) return -37;
//	if( !ora_exec( $tcur ) ) return -37;
*/


	if( $results[1]>0 ) {	//Its been authorised, we need to deduct it from the running total
		$pc = $results[7];
		$total = $results[6];

		if (!decrease_value($ser))
			$allcorrect=false;
		
		if (substr($results[26],0,6)!=$results[29])
			$today = $results[29]."01"; // was: $results[26];  now using budget_month
		else
			$today=$results[26];
		$company = $results[27];
		
		if ($results[18]!=-2) {
			if( !decreaseBudget( $pc, $company, $today, $total ) ) 
				$allcorrect = false;
			else moveDebug("Superceded Q$ser to Q$newser, decreased budget $pc on company $company by R$total for month of $today");
		}
		else 
			moveDebug("Supercede from $ser to Q$newser - not changing budget");
		if ($allcorrect)
			checkBudget( $ser );
	}
	if (!$allcorrect) {
		ora_rollback($conn);
		return -98;	
	}
	// ok, we're happy...
	ora_commit( $conn );
	return $newser;	
}


function getTerritory ($depot) { // return territory


	                        switch (trim(strtoupper($depot))) {
                                        case "CA":
                                        case "CBS":
                                        case "PE":
                                        case "WHK":
                                        case "XXX";
                                                $territory = "S"; break;
                                        case "JHB":
                                        case "PTA":
                                        case "DBN":
                                        case "UPT":
                                        case "LUS";
                                        case "MAP";
                                                $territory = "N"; break;
	                        }

                                return $territory; echo $territory;

}



function myTerritory ($depot, $territory, $company, $tcur) { // is this in my territory?
//      echo "MYT $depot, $territory, $company<br>";

/*
        if ( trim( $territory) == "" ) {
                    switch (trim($depot)) {
                                case "CA":
                                case "CBS":
                                case "PE":
                                case "WHK":
                                                $territory = "S"; break;
                                case "JHB":
                                case "PTA":
                                case "DBN":
                                case "UPT":
                                                $territory = "N"; break;
                        } //switch
        }
//      echo "Checking you agains $territory...<bR>";

        $REMOTE_USER = getenv( "REMOTE_USER");
        if (!is_numeric($company))
        {
                $company=0;  // HUGE assumption here!!

        }
        ora_parse( $tcur, "SELECT territory, territory_master FROM purchase_auth_daily WHERE username='$REMOTE_USER' AND company=$company");
        ora_exec( $tcur);
        if ( ora_fetch( $tcur)) {
                if ( ora_getColumn( $tcur, 1) == "Y" )
         // TEMP FOR DEON SICK               if ( ora_getColumn( $tcur, 0) == $territory )
                              return true;
        } // if
*/
        $depot=chop($depot);
        $myusername=getenv("REMOTE_USER");
	ora_parse($tcur,"select * from move_preauth where username='$myusername' and depot='$depot'");
	ora_exec($tcur);
	if (ora_fetch($tcur))
		return true;
	else
        	return false;

	// return false;

} // myTerritory


function rejOrder( $ser, $reason, $debug=0 ) {		//Rejects a request actually
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );
	
	ora_parse( $tcur, "SELECT order_no, superceded_by, grn, exported, purchase_category, depot, territory, territory_auth, company FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
	ora_exec( $tcur );
	$on = ora_getColumn( $tcur, 0 );
	$sb = ora_getColumn( $tcur, 1 );
	$grn = ora_getColumn( $tcur, 2 );
	$exp = ora_getColumn( $tcur, 3 );
	$pc = ora_getColumn( $tcur, 4 );
	$depot = ora_getColumn( $tcur, 5 );
	$territory = ora_getColumn( $tcur, 6);
	$territory_auth = ora_getColumn( $tcur, 7); 
	$company = ora_getColumn( $tcur, 8);

	
	if( $on >= 0 ) { //Already processed (Includes revoked)
		return -40;
	} elseif( $sb>=0 ) {	//Superceded
		return -41;
	}

	if ( !myTerritory( $depot, $territory, $company, $tcur) )
		if( !isOwner( $ser, true, $debug ) && !canEditReq( $ser ) )	//Not the owner/Can't Edit
			return -42;

	  ora_parse($tcur,"update refund_batches set status='N' where status='F' and request_no=$ser");
          ora_exec($tcur);

		
	//Here we reject the order
	$now = date( "dmY His" );

	$reason=str_replace("'","",$reason);
	$qry = "UPDATE PURCHASE_REQUESTS SET order_no=-2, please_revoke=null, reason_auth='$reason', approved_by='$REMOTE_USER', approved_behalf='$REMOTE_USER', auth_date=to_date( '$now', 'DDMMYYYY HH24MISS' ) WHERE request_no=$ser";

	if( $debug && $REMOTE_USER=="Keeith" )
		print "<hr>$qry<hr>";
			
	ora_parse( $tcur, $qry );
	ora_exec( $tcur );
	
	if( $livesys )
		emailMaster( "REJ", $ser, true, "NA", $debug );
	
	return 1;
}

function decrease_value ( $ser ) { // when an order is revoked when it is against a jobcard for a serialized item 
	global $conn, $cursor;

	$kcur=ora_open($conn);

	ora_parse($cursor,"select A.serial, A.value, B.value, A.jobcard from stk_value_increased A left join stk_serialtrack B on A.serial=B.serial  where request_no=$ser");
	ora_exec($cursor);
	while (ora_fetch($cursor)) {
		$s=getdata($cursor,0);
		$v=getdata($cursor,1);
		$ov=getdata($cursor,2);
		$j=getdata($cursor,3);
		if ($ov<$v)
			$nv=0;
		else
			$nv="value-$v";
		
                $qry="update stk_serialtrack set value=$nv where serial=$s";
                $qry2="delete from stk_value_increased where serial=$s and request_no=$ser and jobcard=$j";
                if (getenv("REMOTE_USER")=="Keeith")
                        echo "$qry<br>$qry2<bR>";

		if (!ora_parse($kcur,$qry))
		{
			ora_close($kcur);
			return false;
		}
		elseif (!ora_exec($kcur))
		{
			ora_close($kcur);
			return false;
		}
		elseif (!ora_parse($kcur,$qry2))
		{
			ora_close($kcur);
			return false;
		}
		elseif (!ora_exec($kcur))
		{
			ora_close($kcur);
			return false;
		}
		else
			return true;

	} // while

	ora_close($kcur);
	return true;

} // decrease_value


function increase_value ( $ser ) { // when an order is approved against a jobcard for a serialized item (or a serialized item jobcard is added to an autohroised order)
	global $conn, $cursor;

	if (!is_numeric($ser))
		return false;
	ora_parse($cursor,"select jobcard, cost from purchase_requests where request_no=$ser");
	ora_exec($cursor);
	if (!ora_fetch($cursor))
		return false;

	$jc=getdata($cursor,0);
	if (!($jc>0))
		return true;

	$cost=getdata($cursor,1);

	$jc=10*(floor($jc/10));
	ora_parse($cursor,"select type, unitserial from move_jobs where jobcardserial='$jc'" );
	ora_exec($cursor);
	if (!ora_fetch($cursor))
		return true;

	if (getdata($cursor,0)!=6)
		return true;

	$serial=getdata($cursor,1);

	if (!($serial>0))
		return true;

	$qry="";
	ora_parse($cursor,"select value from stk_value_increased where request_no=$ser and serial=$serial and jobcard=$jc");
	ora_exec($cursor);
	if (ora_fetch($cursor)) {
		if (getdata($cursor,0)!=$cost) {
			$value=$cost-getdata($cursor,0);
			$qry="update stk_value_increased set value=$cost where  request_no=$ser and serial=$serial and jobcard=$jc";
		}
	} else {
		$value=$cost;
		$qry="insert into stk_value_increased values ($serial, $ser, $jc, $cost)";
	}
	if ($qry=="")
		return true;

	if (getenv("REMOTE_USER")=="Keeith")
		echo "$qry<br>update stk_serialtrack set value=value+$value where serial=$serial<br>";
	// run qry and increase part value by $value
	if (!ora_parse($cursor,$qry))
		return false;
	elseif (!ora_exec($cursor))
		return false;
	else {
		$qry2="update stk_serialtrack set value=value+$value where serial=$serial";
		if (!ora_parse($cursor,$qry2)) {
			return false;
		} elseif (!ora_exec($cursor)) {
			return false;
		} elseif (ora_numrows($cursor)!=1) {
			return false;

		} else return true;
	}

} // increase_value

function authOrder( $ser, $cost, $sms, $reason1="", $comments="", $debug=0 ) {	//This is for authorise order
	global $conn, $REMOTE_USER, $livesys,$last_bname,$last_details,$last_ruser,$changedb;

//	error_reporting(E_ALL);

	$allcorrect=true;

	$tcur = ora_open( $conn );

	ora_parse($tcur,"select request_no, order_no from purchase_requests where superceded_by=$ser and order_no>0");
	ora_exec($tcur);
	if (ora_fetch($tcur)) {
		$force_order_no=true;
		$use_order_no=getdata($tcur,1);
		echo "Q$ser: Re-using order number B$use_order_no from Q".getdata($tcur,0)."<br>";
	} 
	else
		$force_order_no=false;
	
        $qry = "SELECT A.order_no, A.superceded_by, A.grn, A.exported, A.purchase_category, A.cost, A.company, A.depot, A.territory, A.autogrn, A.approved_by, A.territory_auth, B.name, A.rfc_flag, A.requested_by, A.behalf_of, C.card_holder, A.reason_exceeded_budget  FROM PURCHASE_REQUESTS A, purchase_budget_names B, creditors C  WHERE A.creditor=C.serial and A.request_no=$ser and A.purchase_category=B.serial";

	if( $debug && $REMOTE_USER=="Keeith" )
		print "<hr><font size=-1>$qry</font><hr>";

	if (!ora_parse( $tcur, $qry ))
	{
			echo "Fatal error1<bR>";
		exit;
	}
	if (!ora_exec( $tcur ))
	{
		echo "Fatal error2<bR>";
		exit;
	}
	if( !ora_fetch( $tcur ) )
		return -6;
		
	$on = ora_getColumn( $tcur, 0 );
	$sb = ora_getColumn( $tcur, 1 );
	$grn = ora_getColumn( $tcur, 2 );
	$exp = ora_getColumn( $tcur, 3 );
	$pc = ora_getColumn( $tcur, 4 );
	$debugstr.="cost passed to function is $cost\n";	
	$cost = ora_getColumn( $tcur, 5 );				//This is the initial cost
	$debugstr.="cost from db is $cost\n";
	$company = ora_getColumn( $tcur, 6 );
	$depot = ora_getColumn( $tcur, 7 );
	$territory = ora_getColumn( $tcur, 8);
	$autogrn = ora_getColumn( $tcur, 9);
	$budname=ora_getColumn( $tcur, 12);
	$rfc_flag=ora_getColumn( $tcur, 13);
	$requestedby=getdata($tcur,14);
	$behalfof=getdata($tcur,15);
	$card_holder=getdata($tcur,16);
	$reason_exceeded_budget = getdata($tcur, 17);
	
	

	if ($requestedby==getenv("REMOTE_USER") || $behalfof==getenv("REMOTE_USER") ) {
				return -8;
	}

//	echo "RFC Flag is $rfc_flag<bR>";
	
	if( $cost==0  )
		return -1;


/*
	// New code oct/nov 2011 - for master approving an order already issued by a sub-master
	if (ora_getColumn( $tcur, 10)==ora_getColumn( $tcur, 11) && $on>0) 
	    if (!myTerritory( $depot, $territory, $company, $tcur))
	   {
			  if( isMasterReq( $ser, $debug=false ) ) {  
			       $REMOTE_USER=getenv("REMOTE_USER");
				ora_parse($tcur,"update purchase_requests set approved_by='$REMOTE_USER' where request_no=$ser");
				if (ora_exec($tcur))
					return $on;
				}
	   }
	// end of new sub-master code
*/


	if( $on >= 0 ) { //Already processed
		return -2;

	} elseif( $sb>=0 ) {	//Superceded
		return -3;
	}
	
	/*
	if( !isOwner( $ser, true, $debug ) )	//Not the owner
		return -4;
	*/

/// CHECK

        ora_parse($tcur,"select person_serial from user_details where username='".getenv("REMOTE_USER")."' order by is_current desc");
        ora_Exec($tcur);
        ora_Fetch($tcur);
        $person_serial=getdata($tcur,0);

        if ($requestedby!="") {
                ora_parse($tcur,"select person_serial from user_details where username='$requestedby' order by is_current desc");
                ora_Exec($tcur);
                ora_Fetch($tcur);
                $person_serial2=getdata($tcur,0);
                if ($person_serial2 == $person_serial)
                        return -8;
        }

	if (is_numeric($card_holder) && $card_holder==$person_serial) 
	{
		echo "<font color=red>YOU ARE THE CARD HOLDER</font><br>";
			return -8; 
	}


        if ($behalfof!="") {
                ora_parse($tcur,"select person_serial from user_details where username='$behalfof' order by is_current desc");
                ora_Exec($tcur);
                ora_Fetch($tcur);
                $person_serial2=getdata($tcur,0);
                if ($person_serial2 == $person_serial)
                        return -8;
        }




	if ( myTerritory( $depot, $territory, $company, $tcur) && strstr($budname,"Fleet")) {
		$REMOTE_USER = substr(getenv("REMOTE_USER"),0,15); 	
		ora_parse( $tcur, "UPDATE purchase_requests SET territory_auth='$REMOTE_USER' WHERE request_no=$ser");
		if (ora_exec( $tcur)) {
			echo "Area Master Approval has been granted to Q$ser<bR>";	
			return -90;
		} else
		{
			echo "Error adding area-master approval<Br>";
			echo "<hr>";
			exit;
		}
	}
	else {
	//	 if( !isOwner( $ser, true, $debug ) )    //Not the owner
        //       		 return -4;

	}



		
	//From here we are trying to authorise
	$total = 0;
	
	ora_parse( $tcur, "SELECT SUM( price_each*quantity*vat ) FROM PURCHASE_NOPARTS WHERE request_no=$ser" );
	ora_exec( $tcur );
	ora_fetch( $tcur );
	$total += ora_getColumn( $tcur, 0 );
	$debugstr.="Add ".ora_getColumn( $tcur, 0 )." gets $total\n";

    ora_parse( $tcur, "SELECT SUM( price_each*quantity*vat ) FROM PURCHASE_DETAILS WHERE request_no=$ser" );
    ora_exec( $tcur );
    ora_fetch( $tcur );
    $total += ora_getColumn( $tcur, 0 );
	  $debugstr.="Add ".ora_getColumn( $tcur, 0 )." gets $total\n";


    /*
    //There are problems when php tries to multiply out. It creates a slight rounding error, usually 1c out.
    //Oracle works fine though, so we use it instead. (see above)
    
	ora_parse( $tcur, "SELECT price_each, quantity, vat FROM PURCHASE_NOPARTS WHERE request_no=$ser" );
	ora_exec( $tcur );
	while( ora_fetch( $tcur ) )
	{
		$total += ora_getColumn( $tcur, 0 ) * ora_getColumn( $tcur, 1 ) * ora_getColumn( $tcur, 2 );
		  $debugstr.="Add ".ora_getColumn( $tcur, 0 )." * ".  ora_getColumn( $tcur, 1 ) . " gets $total\n";

	}

    ora_parse( $tcur, "SELECT price_each, quantity, vat FROM PURCHASE_DETAILS WHERE request_no=$ser" );
    ora_exec( $tcur );
    while( ora_fetch( $tcur ) )
    	$total += ora_getColumn( $tcur, 0 ) * ora_getColumn( $tcur, 1 ) * ora_getColumn( $tcur,2 );
	*/
        	
    //print "$total<br>";;
    
    if( $debug && $REMOTE_USER=="Keeith" )
    	print "<hr>$total<hr>";
    
	$debugstr.="before rounding total is $total cost is $cost\n";

	$testit=$total*100;
	if (strstr($testit,".5"))
		$total+=0.001;
	
	
    $total = round( $total, 2 )*100;
   $cost  = round( $cost, 2 )*100;
	$debugstr.="after rounding total is $total cost is $cost\n";
    
    //print "<br>I COST: $cost<br>T COST: $total<br><Br>";

	if ($total==$cost+1)
		$cost=$total;
	if ($cost==$total+1)
		$total=$cost;

    
    if( $total != $cost && $rfc_flag!="Y" && $rfc_flag!="A" ) {				//If they're not equal here, somehow the transaction has changed
    	$message = "SER: $ser\nInitial Cost: $cost\nCalc Cost: $total\nFile: " . __FILE__ . "\nLine: " . __LINE__;
    	mail( "keith@intercape.co.za", "Screwed up Calc", "$cost not $total\n$message\n$debugstr" );
	if (getenv("REMOTE_USER")=="Keeith")
		echo $debugstr;
    	return -5;
    }
	$total=$total/100;
	$cost=$cost/100;

	
	//					 /--MASTER---------\
	//					/					\
	//			Y: Okay				N:	Over daily limit
	//								/					\
	//				Y: Needs Master					N:  Over Order Limit---\
	//												/						\
	//									Y: Needs Master					N: Okay


    if ($autogrn=="Y") {
	// check invoices for AutoRFP
	ora_parse($tcur,"select sum(nvl(invoice_amount,$total)) from purchase_invoices where request_no=$ser");
	ora_exec($tcur);
	if (!ora_fetch($tcur))
		return -7;
	$invtotal=getdata($tcur,0);
	if ($invtotal!=$total) {
		return -7;
	}
    }

    $auth = false;
    $reason = "";

        // fix budget month
        ora_parse($tcur, "select budget_month from purchase_requests where request_no=$ser");
        ora_exec($tcur);
        ora_Fetch($tcur);
        $bmonth=getdata($tcur,0);


        if ($bmonth<date("Ym")) {

	   if (AllowedFlag("BUDGET") ) {
		echo "<font color=green><b>NOTE: This order is for a previous month!!</b></font><bR>";	
		moveDebug("Authorised Q$ser in previous month $bmonth");
	   } else {

		$yearend=false;
		ora_parse($tcur,"select to_char(start_at,'YYYYMMDD'), to_char(end_at,'YYYYMMDD') from purchase_yearend");
		ora_exec($tcur);
		if (ora_fetch($tcur)) {

			$todaysdate=date("Ymd");
			if (getdata($tcur,0)<=$todaysdate && getdata($tcur,1)>=$todaysdate)
			{
//				echo getdata($tcur,0)." <= $todaysdate and ".getdata($tcur,1)." >= $todaysdate<bR>";
				$yearend=true;
			}
//			else
//				 echo getdata($tcur,0)." <= $todaysdate and ".getdata($tcur,1)." >= $todaysdate fail<bR>";

		}

		if (!$yearend) {
                	$bmonth=date("Ym");
	                ora_parse($tcur,"update purchase_requests set budget_month='$bmonth' where request_no=$ser");
       		        ora_exec($tcur);
			echo "<font color=green><b>NOTE: This order WAS for a previous month!</b></font><bR>";
		}
		else	
			echo "<font size=4 color=green><b>NOTE: This order is for a previous month!</b></font><bR>";
	  }
        }


        if ($bmonth>date("Ym")) {
           if (AllowedFlag("BUDGET")) {
                echo "<font color=green><b>NOTE: This order is for a future month!</b></font><bR>";
                moveDebug("Authorised Q$ser in future month $bmonth");
           } else
                {
                $bmonth=date("Ym");
                ora_parse($tcur,"update purchase_requests set budget_month='$bmonth' where request_no=$ser");
                ora_exec($tcur);
                }
       	} 


     $success="";
	if (getenv("REMOTE_USER")=="Keeith")
		$mdebug=true;


 
    if( isMasterReq( $ser, $debug=false ) ) {	//Master

    	//Here we check if the limit still applies to the master
	if ($mdebug)
		echo "MASTER<br>";
    	ora_parse( $tcur, "SELECT A.limit FROM PURCHASE_AUTH A, PURCHASE_REQUESTS B WHERE B.request_no=$ser AND A.username='$REMOTE_USER' AND A.category=B.purchase_category" );
    	ora_exec( $tcur );
    	if( ora_fetch( $tcur ) ) {
    		$apply = ora_getColumn( $tcur, 0 );
    	} else $apply = "N";
    	
    	if( $apply=="Y" ) {
    		if( !underDailyLimit( $company, $debug ) ) {
    				print "You are over your <b><u>daily limit</u></b><br>";
        			$auth = false;
    		} else {
    			if( !underOrderLimit( $cost, $ser, $company, $debug ) ) {
        				print "You are over your <b><u>per order limit</u></b><br>";
        				$auth = false;
    			} else {
    				$auth = true;
    				$reason = "Auto-Issued as $REMOTE_USER is master within limits";
    			}
    		}
    	} else {
	    	$auth = true;
		if ($treason!="")
			$reason=$treason;
		else
	    		$reason = "Auto-Issued as $REMOTE_USER is master without limits";
    	}
	if ($mdebug)
		echo "$reason<bR>";
    }
    else {								//Not master
		return -4;
/*
    	if( !underDailyLimit( $company, $debug ) )		//Not under daily limit
    		$auth = false;
    	else {
    		if( !underOrderLimit( $cost, $ser, $company, $debug ) )	//Not under order limit
    			$auth = false;
    		else {						//Under order limit
    			$auth = true;
    			$reason = "Auto-Issued as $REMOTE_USER within limits";
			if ($mdebug)
				echo "$reason<bR>";
    		}
    	}
*/
    }
	
	if( !empty( $reason1 ) ) $reason = $reason1;
	
	if( ( getRunningTotal( $ser ) + $cost > getBudgetTotal( $ser )) || getenv("REMOTE_USER")=="Keeeith" ) {
		if( ! (AllowedFlag( "PURCHASE_OVERRIDE" ) && strlen(trim($reason_exceeded_budget))>=20 )  ) {
			print "Sorry, the budget has been exceeded and you <u>cannot authorize any more orders</u> ";
			print "until the budget has been changed.<br>Contact the Financial Manager for more information.";
			if (AllowedFlag("PURCHASE_OVERRIDE")) {
				echo "<font color='red'>You MAY authorize over the budget if you provide a reason - the reason you gave is <b>too short</b>, please go to the order and provide a reason</font><bR>";
			}
			$username=getenv("REMOTE_USER");
			if ( $livesys ) {
				$exemail = "$last_ruser@cavmail.co.za";
				ora_parse( $tcur, " SELECT email FROM user_details WHERE username='$last_ruser' AND (is_current='Y' or is_current='L')");
				ora_exec( $tcur);
				if ( ora_fetch( $tcur))
					if ( trim( ora_getColumn( $tcur, 0)) != "")
						$exemail = trim( ora_getColumn( $tcur, 0));
				$amt1=sprintf( "%.2f", getRunningTotal( $ser ) );
				$amt2=sprintf( "%.2f", getBudgetTotal( $ser ) ) ;	

//				mail ("cobusd@intercape.co.za,$exemail","Budget Exceed Attempt","$username tried to approve Q$ser ( https://secure.intercape.co.za/move/requestorder.phtml?stage=4&ser=$ser internal or https://secure.intercape.co.za/move/requestorder.phtml?stage=4&ser=$ser internet), which was requested by $last_ruser but cannot because the budget would be exceeded. \n$last_bname: $last_details\nThe current running total is: R$amt1\nThe current budget is: R$amt2"); 
		
			}	
			if( isMasterReq( $ser, $debug ) ) {
				print "<br><br>";
				print "The current running total is: <b>R</b> " . sprintf( "%.2f", getRunningTotal( $ser ) ) . "<br>";
				print "The current budget is: <b>R</b> " . sprintf( "%.2f", getBudgetTotal( $ser ) ) . "<br>";
			}
		
			print "<hr>";
			$auth = false;
		} else {
			if ($mdebug) {
				if ($auth)
					echo "AUTH1<bR>";
				else
					echo "NOAUTH1<bR>";
			}
			if ( isMasterReq( $ser, $debug ) ) {
			$success.= "This order has been authorized, <b>but</b> <u>the budget has been exceeded</u>.";
			$success.=  "<br><br>";
			$auth = true;


			}
			$success.=  "The current running total is: <b>R</b> " . sprintf( "%.2f", getRunningTotal( $ser ) ) . "<br>";
			$success.=  "The current budget is: <b>R</b> " . sprintf( "%.2f", getBudgetTotal( $ser ) ) . "<br>";
			$success.=  "<hr>";
		}
	}
	// $auth = true; // NOT TO BE USED EVER
	if( $auth ) {
		if ($mdebug)
			echo "Auth2<BR>";
		ora_parse( $tcur, "SELECT autogrn, budget_month from purchase_requests WHERE request_no=$ser");
		ora_exec( $tcur);
		if (!ora_fetch( $tcur)) {
			echo "Q$ser not found!<Br>";
			return -6;
		}
		$autogrn = ora_getColumn( $tcur, 0);
		$today = ora_getColumn( $tcur, 1)."01";
		if ( $autogrn == "Y") {
			$dogrn = ", grn=1";
		} else $dogrn="";	

		if (!increase_value($ser))
			$allcorrect=false;
		
		$now = date( "dmY His" );

		if ($force_order_no) {
			$non=$use_order_no;
		} else { 
		ora_parse( $tcur, "SELECT purchase_order_no.nextval FROM dual" );
		ora_exec( $tcur );
		ora_fetch( $tcur );
		$non = ora_getColumn( $tcur, 0 );
		}
		if (!is_numeric($non) || $non<=0)
			$allcorrect=false;

		ora_parse($tcur,"update refund_batches set status='O' where status='F' and request_no=$ser");
		if (!ora_exec($tcur))
			$allcorrect=false;

		// update autoRFP invoice value...
		ora_parse($tcur,"select cost, orig_currency from purchase_requests where request_no=$ser");
		ora_exec($tcur);
		ora_fetch($tcur);
		$inv_value=getdata($tcur,0);
		$force_curr=getdata($tcur,1);
		if ($force_curr!="S.A. Rand" && $force_curr!="Namibia Dollar") {
			$qry="update purchase_invoices set currency='$force_curr' where currency is null and request_no=$ser";
			if( $debug || $REMOTE_USER=="Keith" )
	                        print "<hr>$qry<hr>";

			ora_parse( $tcur, $qry );
       		        if (!ora_exec( $tcur ))
                	        $allcorrect=false;
			
			$local_amt=0;
			ora_parse($tcur,"select sum(quantity*local_price*vat) from purchase_noparts where request_no=$ser");
			ora_exec($tcur);
			ora_fetch($tcur);
			$local_amt+=getdata($tcur,0);

			ora_parse($tcur,"select sum(quantity*vat*local_price) from purchase_details where request_no=$ser");
			ora_exec($tcur);
                        ora_fetch($tcur);
			if (is_numeric(getdata($tcur,0)))
                   	     $local_amt+=getdata($tcur,0);

			$local_amt=sprintf("%.2f",$local_amt);
	
			$qry="update purchase_invoices set local_amount=$local_amt where local_amount is null and request_no=$ser";
			if( $debug || $REMOTE_USER=="Keith" )
                                print "<hr>$qry<hr>";

                        ora_parse( $tcur, $qry );
                        if (!ora_exec( $tcur ))
                                $allcorrect=false;

			
	

		}
		$qry="update purchase_invoices set invoice_amount=$inv_value where invoice_amount is null and request_no=$ser";
		if( $debug || $REMOTE_USER=="Keith" )
                        print "<hr>$qry<hr>";

                ora_parse( $tcur, $qry );
                if (!ora_exec( $tcur ))
                        $allcorrect=false;

		
	        $reason=str_replace("'","",$reason);

		$qry = "UPDATE PURCHASE_REQUESTS SET order_no=$non, reason_auth='$reason', approved_by='$REMOTE_USER', auth_date=to_date( '$now', 'DDMMYYYY HH24MISS' )$dogrn WHERE request_no=$ser";

		if( $debug && $REMOTE_USER=="Keeith" )
			print "<hr>$qry<hr>";

		ora_parse( $tcur, $qry );
		if (!ora_exec( $tcur ))
			$allcorrect=false;

		if( $livesys && $allcorrect )
			emailMaster( "AUTH", $ser, $auth, $reason, $debug );

		//Keep a running total for the budget today, this is superceded, revoked order as well
		// $today = date( "Ymd" ); now using budget_month
		if ($allcorrect)
			if (!increaseBudget( $pc, $company, $today, $total ))
				$allcorrect=false;
		
		//Check the budget and send SMS
		if ($allcorrect)
			if( checkBudget( $ser, $total, $debug ) ) {
				$success.= "<b><font size=+1 color=red>Your budget has been exceeded, you should contact the Financial Manager</font></b><br>";
			}


/*		if (getenv("REMOTE_USER")=="EstherD") {
			if ($allcorrect)
				echo "<b>IT WOULD HAVE WORKED</b><BR>";
			else
				echo "PROBLEM!<Br>";
			ob_end_flush();
			ora_rollback($conn);
			return -99;

		}
*/

		
		if (!$allcorrect) {
			ora_rollback($conn);
			return -99;
			
		}
		else {
			echo $success;	
			return $non;
		}
	} else { //Its just a finalised request
		//print "USERLEFT: " . userLeft( true, $company ) . "<br>";
		//print "USERAMOUNT: " . userAmount( $company ) . "<br>";
		
/*		$qry = "UPDATE PURCHASE_REQUESTS SET description='$comments' WHERE request_no=$ser";

		if( $debug && $REMOTE_USER=="Keeith" )
			print "<hr>$qry<hr>";

		ora_parse( $tcur, $qry );
		ora_exec( $tcur );
*/

		if( $livesys )
			emailMaster( "AUTH", $ser, $auth, $reason, $debug );
		
		if( $livesys ) {
			if( $sms )
			{
				smsMaster( $ser, $debug );
				$qry = "UPDATE PURCHASE_REQUESTS SET sms_sent='Y' WHERE request_no=$ser";

		                if( $debug && $REMOTE_USER=="Keeith" )
               			         print "<hr>$qry<hr>";

		                ora_parse( $tcur, $qry );
               			 ora_exec( $tcur );

			}
		}
		else echo "Test System - not sending email or sms<br>";

		return 0;
	} 
}

function revokeOrder( $ser, $debug=0, $testrun=false ) {	//Revoke order
	global $conn, $REMOTE_USER, $livesys;

	$tcur = ora_open( $conn );
	$return = -20;

	if (!is_numeric($ser))
		return $return;
		
	
	ora_parse( $tcur, "SELECT order_no, superceded_by, grn, purchase_category, cost, to_char( auth_date, 'YYYYMMDD' ), company, budget_month, autogrn FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
	ora_exec( $tcur );
	if (!ora_Fetch($tcur))
		return $return;

	
	$can = true;
	$on = ora_getColumn( $tcur, 0 );
	$sb = ora_getColumn( $tcur, 1 );
	$grn = ora_getColumn( $tcur, 2 );
	$pc = ora_getColumn( $tcur, 3 );
	$total = ora_getColumn( $tcur, 4 );
	$today = ora_getColumn( $tcur, 7 )."01"; // was ,5
	$company = ora_getColumn( $tcur, 6 );
	$autogrn=ora_getColumn($tcur,8);


	if ($autogrn!="Y") {	
          // check invoice total
          ora_parse($tcur,"select sum(nvl(nvl(local_amount,invoice_amount), $total)) from purchase_invoices where request_no=$ser");
          ora_exec($tcur);
          if (ora_fetch($tcur)) {
		if (round(getdata($tcur,0))!=0)
		{	
			if ($testrun) {
				echo errorMSG(-39);
				$return=2;
			}
			else
		                return -39;
		}
          }

	}

	
	$delnotes = "( ";
	ora_parse( $tcur, "SELECT delnote FROM PURCHASE_DELIVERIES WHERE request_no=$ser and typeof='D'" );
	ora_exec( $tcur );
	while( ora_fetch( $tcur ) )
		$delnotes .= ora_getColumn( $tcur, 0 ) .", ";
	
	$delnotes = substr( $delnotes, 0, strlen( $delnotes )-2 ); //get rid of last comma
	$delnotes .= " )";
	$nodelivered = 0;
	
	if( $delnotes != " )" ) {	//There were del notes, so we need to check that sum(total dels) is 0
		$statement = "SELECT SUM( received ) FROM PURCHASE_RECEIVED WHERE delnote IN $delnotes";
		if ($debug>0)
		echo "$statement<BR>";
		ora_parse( $tcur, $statement );
		ora_exec( $tcur );
		$nodelivered += ora_getColumn( $tcur, 0 );
	}

        $delnotes = "( ";
        ora_parse( $tcur, "SELECT delnote FROM PURCHASE_DELIVERIES WHERE request_no=$ser and typeof='T'" );
        ora_exec( $tcur );
        while( ora_fetch( $tcur ) )
                $delnotes .= ora_getColumn( $tcur, 0 ) .", ";

        $delnotes = substr( $delnotes, 0, strlen( $delnotes )-2 ); //get rid of last comma
        $delnotes .= " )";

        if( $delnotes != " )" ) {       //There were del notes, so we need to check that sum(total dels) is 0
                $statement = "SELECT SUM( received ) FROM PURCHASE_RECEIVED WHERE delnote IN $delnotes";
                ora_parse( $tcur, $statement );
                ora_exec( $tcur );
                $nodelivered += ora_getColumn( $tcur, 0 ); // changed from - to + aug 2006 as the number in the db is already negative
        }

	$cursor=$tcur;


	
	if( $REMOTE_USER=="Keeith" ) {
		if( $on < 0 ) {
			print "<hr>Already rejected/Not processed<hr>";
			$return = -21;
			$can = false;
		} elseif( abs($grn)!=1 ) {
			print "<hr>Already RFPd<hr>";
			$return = -22;
			$can = false;
		} elseif( $sb==-2 ) {
			print "<hr>Already revoked<hr>";
			$return = -23;
			$can = false;
		} elseif( $sb>=0 ) {
			print "<hr>Already superceded<hr>";
			$return = -24;
			$can = false;
		} elseif( $nodelivered>0 ) {
			print "<hr>Still have deliveries<hr>";
			$return = -25;
			$can = false;
		} elseif( $nodelivered<0 ) {
			print "<hr>Too many returns<hr>";
			$return = -26;
			$can = false;
		} else {
			$return = 1;
		}
	} else {
		if( $on < 0 ) {
			$return = -21;
			$can = false;
		} elseif( abs($grn)!=1 ) {
			$return = -22;
			$can = false;
		} elseif( $sb==-2 ) {
			$return = -23;
			$can = false;
		} elseif( $sb>=0 ) {
			$return = -24;
			$can = false;
		} elseif( $nodelivered>0 ) {
			$return = -25;
			$can = false;
		} elseif( $nodelivered<0 ) {
			$return = -26;
			$can = false;
		} else {
			$return = 1;
		}
	}
	
	if( $testrun ||  isMasterReq( $ser, $debug )  /* removed by kw march 2007|| canEditReq( $ser ) */  )
	{
	  // nothing
	}
	else $return = -27;

	// check for paid/accepted invoices....
	ora_parse($tcur,"select sum(nvl(nvl(B.local_amount,B.invoice_amount),A.cost)) from purchase_requests A, purchase_invoices B  where A.request_no=$ser and B.request_no=$ser");
	ora_exec($tcur);
	if( ora_fetch($tcur))
	{
		if (abs(round(getdata($tcur,0),2))>0.02)
		{
			if (getenv("REMOTE_ADDR")=="192.168.208.64")
				echo "select sum(nvl(nvl(B.local_amount,B.invoice_amount),A.cost)) from purchase_requests A, purchase_invoices B  where A.request_no=$ser and B.request_no=$ser<bR>";
			$return = -28;
		}

	}
	// check SAGE

	ora_parse($cursor,"select 'x' from purchase_invoices where request_no=$ser and sage_ref is null");
	ora_exec($cursor);
	if (ora_fetch($tcur))
		return -29;

	if ($testrun)
		return $return;

/*	echo "ret $return auto $autogrn<bR>";
	if ($can)
		echo "can<bR>";
	else
		echo "Cant<bR>";
*/

/*
Code doenst make sense, because above code checks that invoice total is already zero

	if( $return==1 && $can && $autogrn=="Y") {
		ora_parse($cursor,"select max(yearmonth)  from finance_close where what='C'");
		ora_exec($cursor);
		ora_fetch($cursor);
		$closedat=getdata($cursor,0);
		$y=substr($closedat,0,4);
		$m=substr($closedat,4,2);
		$m++;
		if ($m==13) {
			$m=1;
			$y++;
		}
		$m=sprintf("%02d",$m);
		$newdate=$y.$m."01";

		unset($oldi);
		ora_parse($cursor,"select request_no, invoice_no, to_Char(invoice_date,'YYYYMMDD') , invoice_amount, to_char(invoice_paper_date,'YYYYMMDD') from purchase_invoices where request_no=$ser");
		ora_exec($cursor);
		unset($data);
		while (ora_fetch_into($cursor,$data)) {
			//echo "GOT";
			$data[1]=$data[1]."CR";	
			if ($data[2]<$newdate && $data[2]>=20060901) {
				echo "Change $data[2] to $newdate<bR>";
				$data[2]=$newdate;
			}
			$oldi[]=$data;
			unset($data);
		} // while
//		echo "loop done<br>";

		if (is_array($oldi)) {
			reset($oldi);
			while(list($key,$data)=each($oldi)) {
				if ($data[3]==0 || !is_numeric($data[3])) {
					ora_parse($cursor,"select cost from purchase_requests where request_no=$data[0]");
					ora_exec($cursor);
					ora_fetch($cursor);
					$data[3]=getdata($cursor,0)*-1;
				}
				else $data[3]=$data[3]*-1;

				if (is_numeric($data[3]))
					$data[3]=round($data[3]*100)/100;
				ora_parse($cursor,"insert into purchase_invoices (request_no, invoice_no, invoice_date, invoice_amount, invoice_paper_date, serial) values ('$data[0]','$data[1]',to_date('$data[2]','YYYYMMDD'), '$data[3]', to_date('$data[4]','YYYYMMDD'), invoice_serial.nextval )");
				if (!ora_exec($cursor))
				{
					$return=-50;
					echo "Err: insert into purchase_invoices (request_no, invoice_no, invoice_date, invoice_amount, invoice_paper_date, serial) values ('$data[0]','$data[1]',to_date('$data[2]','YYYYMMDD'), '$data[3]', to_date('$data[4]','YYYYMMDD'), invoice_serial.nextval )<bR>";
				}
				

			} // while

		}


		


	}
*/

	
	if( $return==1 && $can ) {
		$now=date("YmdHis");
		$qry = "UPDATE PURCHASE_REQUESTS SET superceded_by=-2, please_revoke=null,  approved_behalf='$REMOTE_USER', revoke_date=to_date('$now','YYYYMMDDHH24MISS') WHERE request_no=$ser";

		if( $debug && $REMOTE_USER=="Keeith" )
			print "<hr>$qry<hr>";
			
		ora_parse( $tcur, $qry );
		if (ora_exec( $tcur )) {

			if (!decreaseBudget( $pc, $company, $today, $total )) // now using budget_month
				{
					$return=-99	;
				}
			else
						checkBudget( $ser );

		} else $return = -97;

		if ($return==1)
		   if (!decrease_value($ser))
                        $return=-96;


	} 

	if ($return!=1 || getenv("REMOTE_USER")=="Keeith") {
		ora_rollback($conn);
	}
	else
		ora_commit($conn);
	
	ora_close( $tcur );
	return $return;
}

function deleteReq( $ser, $debug=0 ) { //Deletes Request
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );

	ora_parse( $tcur, "SELECT order_no, superceded_by FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
	ora_exec( $tcur );
	
	$on = ora_getColumn( $tcur, 0 );
	$sb = ora_getColumn( $tcur, 1 );
	
	if( $on != -1 )
		return -10;
		
	if( $sb == -2 )
		return -11;
		
	if( $sb >= 0 )
		return -12;
		
	if( !isOwner( $ser, true, $debug ) )
		return -13;

	ora_parse($tcur,"select request_no from purchase_requests where superceded_by=$ser");
	ora_exec($tcur);
	if (ora_fetch($tcur)) {
		echo "Q".getdata($tcur,0)." was superceded to Q$ser, so cannot delete<bR>";
		return -12;
	}
		
	ora_parse( $tcur, "DELETE FROM PURCHASE_REQUESTS WHERE request_no=$ser" );
	ora_exec( $tcur );
	translog("Deleted request Q$ser");
	return 1;
}

function isOwner( $ser, $master=true, $debug=false ) {	//Are you owner/master of request?
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );
	$return = false;

//	if (getenv("REMOTE_USER")=="LisaH" ||  getenv("REMOTE_USER")=="LinaD" ||  getenv("REMOTE_USER")=="JohanS")
//		return true;
	if (AllowedFlag("CREDITORS_ADMIN"))
		return true;
	
	$qry = "SELECT requested_by, behalf_of FROM PURCHASE_REQUESTS WHERE request_no=$ser";
	
	if( $debug && $REMOTE_USER=="Keeith" )
		print "<hr><font size=-1>$qry</font><hr>";
		
	ora_parse( $tcur, $qry );
	ora_exec( $tcur );
	$rb = chop( ora_getColumn( $tcur, 0 ) );
	$ob = chop( ora_getColumn( $tcur, 1 ) );
	
	if( $rb==$REMOTE_USER || $ob==$REMOTE_USER )
		$return = true;

	if ($return && $debug)
		echo "You are owner ($rb/$ob)<bR>";

		
	if( $master && isMasterReq( $ser, $debug ) )
	{
		if ($debug)
			echo "You are master<Br>";
		$return = true;

	}
	
	ora_close( $tcur );

	if (!$return)
		return AllowedFlag("BUDGET_YEAR_END");

	return $return;	
}

function isMaster( $id, $what, $debug=0 ) {	//Master
	//This won't work with multiple companies. CAT means nothing unless we have a company to go with.
	//Just too much effort :(
	
	/*
	$return = false;
	
	if( $what=="REQ" )
		$return = isMasterReq( $id, $debug );
	elseif( $what=="CAT" )
		$return = isMasterCat( $id, $debug );
	else $return = false;
	
	return $return;
	*/
	return false;
}

function isMasterReq( $ser, $debug=0 ) { //Master of request
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );

	 if (AllowedFlag("BUDGET"))
	{
		if ($debug)
			echo "You are overall budget master.<BR>";
      	        return true;
	}

	
	$qry = "SELECT A.master FROM PURCHASE_AUTH A, PURCHASE_REQUESTS B WHERE A.category=B.purchase_category AND B.request_no=$ser AND A.username='$REMOTE_USER' AND A.company=B.company";
	
	if( $REMOTE_USER=="Keeith" && $debug )
		print "<hr><font size=-1>$qry</font><hr>";
		
	ora_parse( $tcur, $qry );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) )
		$master = ora_getColumn( $tcur, 0 );
	else $master = "N";
	
	ora_close( $tcur );
	
	if( $master=="Y" )
		return true;
	else
		return false;
	
	return false;
}

function isMasterCat( $cat, $company, $debug=0 ) {	//Master of cat [need company as well]
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );
	
	$qry = "SELECT A.master FROM PURCHASE_AUTH A WHERE A.category=$cat AND A.username='$REMOTE_USER' AND A.company=$company";
	
	if( $debug && $REMOTE_USER=="Keeith" )
		print "<hr><font size=-1>$qry</font><hr>";
		
	ora_parse( $tcur, $qry );
	ora_exec( $tcur );
	$master = ora_getColumn( $tcur, 0 );
	
	ora_close( $tcur );
	
	if( $master=="Y" )
		return true;
	else
		return false;
		
	return false;
}

function canEditReq( $ser, $debug=0 ) { //Can edit request
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );
	
	$qry = "SELECT A.master, A.edit FROM PURCHASE_AUTH A, PURCHASE_REQUESTS B WHERE A.category=B.purchase_category AND B.request_no=$ser AND A.username='$REMOTE_USER' AND A.company=B.company";
	
	if( $debug && $REMOTE_USER=="Keeith" )
		print "<hr><font size=-1>$qry</font><hr>";
		
	ora_parse( $tcur, $qry );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) ) {
		$master = ora_getColumn( $tcur, 0 );
		$edit = ora_getColumn( $tcur, 1 );
	} else $master = $edit = "N";
	
	ora_close( $tcur );
	
	if( $master=="Y" || $edit=="Y" )
		return true;
	
	return false;
}

function canEditCat( $cat, $company, $debug=0 ) {		//Can edit a category
	global $conn, $REMOTE_USER, $livesys;
	$tcur = ora_open( $conn );
	
	$qry  = "SELECT A.master, A.edit FROM PURCHASE_AUTH A WHERE A.category=$cat AND A.username='$REMOTE_USER' AND A.company=$company";

	if( $debug && $REMOTE_USER=="Keeith" )
		print "<hr><font size=-1>$qry</font><hr>";
		
	ora_parse( $tcur, $qry );
	ora_exec( $tcur );
	$master = ora_getColumn( $tcur, 0 );
	$edit = ora_getColumn( $tcur, 1 );
	
	if( $master=="Y" || $edit=="Y" )
		return true;
	
	return false;
	
	ora_close( $tcur );
}


// checks to see how much budget is left in the current month
function showBudgetAvail( $budget, $compareto=0, $fontsize=3, $moreinfo=false, $themonth="" ) {
	global $cookiecompany, $conn, $budgetcursor, $budgetavail_cache;

	if (!isset($budgetcursor))
		$budgetcursor=ora_open($conn);

	$tcur=$budgetcursor;

	// cached results...
	if (isset($budgetavail_cache[$budget])) {
	        $avail=$budgetavail_cache[$budget];

	        if ($avail<$compareto)
	                $return="<font color=red size=$fontsize>".number_format($avail,0,"."," ")."</font>";
	        else
	                $return="<font color=green size=$fontsize>".number_format($avail,0,"."," ")."</font>";
		return $return;	

	}

	if (!is_numeric($cookiecompany))
		$cookiecompany=0;

	if (!is_numeric($budget))
		return "<font size=$fontsize color=red>ERROR</font>";

	if ($themonth=="") {
		$date = date("mY");
		$ym = date("Ym");
	} else {
		$ym=$themonth;
		$date=substr($themonth,4,2).substr($themonth,0,4);
	}
	


        ora_parse( $tcur, "SELECT A.amount FROM PURCHASE_BUDGET A WHERE A.company=$cookiecompany AND A.serial=$budget AND A.rundate='$date'" );
        ora_exec( $tcur );
        if( ora_fetch( $tcur ) )
                $amount = ora_getColumn( $tcur, 0 );
        else $amount = 0;       //The budget

        ora_parse( $tcur, "SELECT SUM( total ) FROM PURCHASE_RUNNING6 WHERE budget=$budget AND budget_month='$ym' AND company=$cookiecompany" );
        ora_exec( $tcur );
        $expense = ora_getColumn( $tcur, 0 ); //Total expenses for the month

	$avail=$amount-($expense);

	$avail=sprintf("%.2f",$avail);

	$budgetavail_cache[$budget]=$avail;

	if ($avail<$compareto)
		$return="<font size=$fontsize color=red>".number_format($avail,0,"."," ")."</font>";
	else
		$return="<font size=$fontsize color=green>".number_format($avail,0,"."," ")."</font>";

	if ($moreinfo) {
		$return="Budget: R".number_format($amount,0,"."," ")." Spent: R".number_format($expense,0,"."," ")." Left: R".$return;

	}

	return $return;
	
} // budget

$budgetavail_cache=array();

//Checks to see if authorization exceeded the budget
function checkBudget( $ser, $cost=0, $debug=0 ) {
	global $conn, $livesys;
	$tcur = ora_open( $conn );
	
/*	$month = date( "m" );
	$year  = date( "Y" );
	$date  = date( "mY" );
	$today = date( "Ymd" );
*/ 
	ora_parse( $tcur, "SELECT A.company, A.purchase_category, budget_month FROM PURCHASE_REQUESTS A WHERE A.request_no=$ser" );
	ora_exec( $tcur );
	$company = ora_getColumn( $tcur, 0 );
	$pc = ora_getColumn( $tcur, 1 );
	$today = ora_getColumn( $tcur, 2) . "01"; // now using budget_month

	$year = substr( $today, 0, 4);
	$month = substr( $today, 4, 2);
	$date = $month . $year;
	
	ora_parse( $tcur, "SELECT A.amount FROM PURCHASE_BUDGET A WHERE A.company=$company AND A.serial=$pc AND A.rundate='$date'" );
	ora_exec( $tcur );
	if( ora_fetch( $tcur ) )
		$amount = ora_getColumn( $tcur, 0 );
	else $amount = 0;	//The budget
	
	ora_parse( $tcur, "SELECT SUM( total ) FROM PURCHASE_RUNNING6 WHERE budget=$pc AND budget_month='$year$month' AND company=$company" );
	ora_exec( $tcur );
	$expense = ora_getColumn( $tcur, 0 ); //Total expenses for the month
	
	if( $expense>$amount ) {	//Exceeded Budget
		if( $expense-$cost<$amount ) {	//This was the bugger that put us over the limit
			//SEND AN SMS TO ANDRE, CHECK THE TIME
			$hour = (int) date( "H" );
			
		/*	if( $hour>=7 && $hour<21 ) {	//Send an SMS
				ora_parse( $tcur, "SELECT phone_2 FROM USER_DETAILS WHERE user_serial='697627455'" );
				ora_exec( $tcur );
				$phone = ora_getColumn( $tcur, 0 );
*/
				
				ora_parse( $tcur, "SELECT name FROM PURCHASE_BUDGET_NAMES WHERE serial=$pc" );
				ora_exec( $tcur );
				$name = trim( strtoupper( ora_getColumn( $tcur, 0 ) ) );
				
				$msg = "$name - BUDGET R" . sprintf( "%.2f", $amount ) . ", EXPENSES R" . sprintf( "%.2f", $expense ) . " - ";
				$msg .= "MONTHLY budget EXCEEDED.  Approved by ".getenv("REMOTE_USER");
				
				if( $livesys ) {
//					mail( "sms@intercape.co.za", "$phone", "$msg" );
					mail("tiaan@intercape.co.za,cobusd@intercape.co.za","Order on Q$ser has taken budget over",$msg);
				}
//			} else {	//Send an email
			
			return true;	//Only the first person to exceed gets the message
		}
		return false;
	}

	return false;
}

function trackStock( $ser, $delnote, $debug=0 ) {
	return;
}

function errorMSG( $error, $debug=0 ) {
	global $hide_error_header;


	if (!$hide_error_header) {
		$before = " (<b><u>REASON [$error]:</u></b> ";
		$after  = ")";
	}
	else
	{
		$before=" * ";
		$after="<br>";
	}
	$reason = "";
	
	if( $error>=0 )
		return $before . "There was no error" . $after;
		
	$error *= -1;
	
	switch( $error ) {
		//1 through 9: Auth Order
		case 1 : $reason = "Cost is 0"; break;
		case 2 : $reason = "Already processed"; break;
		case 3 : $reason = "Superceded"; break;
		case 4 : $reason = "Not the owner/master"; break;
		case 5 : $reason = "Order has been changed (Amounts differ)"; break;
		case 6 : $reason = "Request not found"; break;
		case 7 : $reason = "AutoRFP Invoice details missing/do not match the order amount"; break;
		case 8 : $reason = "Requestor may not authorize his/her own orders"; break;
		
		//10 through 19: Del Request
		case 10: $reason = "Not yet processed"; break;
		case 11: $reason = "Revoked"; break;
		case 12: $reason = "Superceded"; break;
		case 13: $reason = "Not the owner/master"; break;
		
		//20 through 29: Revoke Order
		case 20: $reason = "Unknown"; break;
		case 21: $reason = "Already rejected/Not processed"; break;
		case 22: $reason = "Already RFPd.  <u>Creditors to undo RFP and storeman to pass return notes</u>"; break;
		case 23: $reason = "Already revoked"; break;
		case 24: $reason = "Already superceded"; break;
		case 25: $reason = "Still has deliveries. <u>Storeman responsible to make out return notes</u>"; break;
		case 26: $reason = "Too many $reason =s ????"; break;
		case 27: $reason = "You are not the owner/master"; break;
		case 28: $reason = "Invoice total is not zero"; break;
		case 29: $reason = "Not all invoices/credit notes have gone to SAGE yet"; break;
		
		//30 through 39: Supercede Order
		case 30: $reason = "Not the owner/master"; break;
		case 31: $reason = "Could not find request"; break;
		case 32: $reason = "Not yet processed"; break;
		case 33: $reason = "Already RFPd"; break;
		case 34: $reason = "Already Exported"; break;
		case 35: $reason = "Could not update parts/request"; break;
		case 36: $reason = "Could not update deliveries"; break;
		case 37: $reason = "Could not update invoices"; break;
		case 38: $reason = "Order is in creditors system already"; break;
		case 39: $reason = "Cannot revoke/supercede with invoices against an order.  <u>Creditors dept to pass Credit Note first.</u>"; break;
		case 50: $reason = "Problem reversing invoice"; break;
		case 60: $reason = "Order has delivered items"; break;
		
		//40 through 49: Reject Order
		case 40: $reason = "Already processed"; break;
		case 41: $reason = "Already superceded"; break;
		case 42: $reason = "Not the owner/master"; break;


		// 50 through 59: split order
		case 50: $reason = "Order not approved yet"; break;
		case 51: $reason = "Order split max times already"; break;
		case 52: $reason = "Everything is delivered."; break;

		// 90 through 99 : other
		case 90: $reason = "Only area approval done - the real master must approve the order"; break;

		
		default: $reason = "Unknown"; break;
	}
	
	return $before . $reason . $after;
}


} // include
?>

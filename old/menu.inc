<?


if( !isset( $cookiedepot )  ) {
        $ip=explode(".",getenv("REMOTE_ADDR"));
        switch ($ip[2]) {
                case 8:
                case 9:
                case 10:
                case 11:
                case 210:
                case 211:  $cookiedepot="CA"; break;

                case 35:  $cookiedepot="DBN"; break;

                case 14:
                case 15:
                case 16:        $cookiedepot="PE"; break;


                default:  $cookiedepot="PTA";
        } // switch
}

if( !isset( $cookiecompany ) ) {
        $cookiecompany=0;
	

        $cookiecompanyname="INTERCAPE MAINLINER (PTY) LTD";

}




if (!isset($included))
	$included="N";

/*
if (getenv("REMOTE_ADDR")=="192.168.210.248" || getenv("REMOTE_ADDR")=="192.168.83.248")
{
	putenv("REMOTE_USER=Johann");
	$REMOTE_USER="Johann";
}

*/

/*

if (strstr(getenv("REMOTE_ADDR"),"192.168.11.")) {
        echo "<Script> window.location='https://secure.intercape.co.za/".getenv("REQUEST_URI")."'; </script>";
        exit;
}
*/

$REMOTE_USER=getenv("REMOTE_USER");
if( getenv( "SERVER_ADDR" )=="192.168.10.4" || $_SERVER["SERVER_NAME"]=="secure.intercape.co.za" ) {
	$livesys = true;
	?> <div id="topmenu" class="menu"><table class="menu"><tr bgcolor=white><td bgcolor=white width=300><font size=-1><b><?=getenv("REMOTE_USER")?><? if( isset($cookiedepot) ) print " (<a target='_blank' href=\"changedepot.phtml\">$cookiedepot</a>)"; else print " (<a href=\"changedepot.phtml\">None</a>)"; ?><? if( isset($cookiecompany) ) print " (<a href=\"changedepot.phtml\">" . substr( $cookiecompanyname, 0, 15 ) . "...</a>)"; else print " (<a href=\"changedepot.phtml\">None</a>)"; ?></b></font></td><td width=100 align='center'><font size=-1><a href="index.phtml">MOVE Menu</a></font></td><td width=110 align='center'><font size=-1><a href="manageindex.phtml">Management</a></font></td><td bgcolor=yellow width=100 align='center'><a href="checkjobcard.phtml?stage=0&recent=1"><font size=-1><b>Find Job Card</font></a></td><td width=100 bgcolor=lightblue align='center'><A target='_top' href='https://secure.intercape.co.za/ignite/index.php?c=overtime_staff&m=vdash_overtime_preapprove&page_id=1561'>OVERTIME</a></td><td width=100><a href="requestorder.phtml?stage=1&filter=2&depot=<?=$cookiedepot?>"><font size=-1>View Orders</font></a></td><td width=100 align='center'><a target=_new href="/parcel/index.phtml"><font size=-1>Parcels</font></a></td><td width=100 align='center'><a href="move_invoice_payandbatch.phtml"><font size=-1>Creditors</font></a></td></tr> <?
} else {
	$livesys = false;
//	echo "<pre>";
//	echo "SERVER=". getenv( "SERVER_ADDR" )	;
//	print_r($_SERVER);
	?> <div id="topmenu" class="menu"><table class="menu"><tr bgcolor='#E1E1E1'><td bgcolor='#BBCFE9' width=300><font size=-1><b><?=getenv("REMOTE_USER")?><? if( isset($cookiedepot) ) print " (<a href=\"changedepot.phtml\">$cookiedepot</a>)"; else print " (<a href=\"changedepot.phtml\">None</a>)"; ?><? if( isset($cookiecompany) ) print " (<a href=\"changedepot.phtml\">" . substr( $cookiecompanyname, 0, 15 ) . "...</a>)"; else print " (<a href=\"changedepot.phtml\">None</a>)"; ?></b></font></td><td width=100 align='center'><font size=-1><a href="index.phtml">MOVE Menu</a></font></td><td width=110 align='center'><font size=-1><a href="manageindex.phtml">Management</a></font></td><td width=100 bgcolor=yellow align='center'><a href="checkjobcard.phtml?stage=0&recent=1"><font size=-1><b>Find Job Card</font></a></td><td width=100 align='center'><a href="requestorder.phtml?stage=1&filter=2&depot=<?=$cookiedepot?>"><font size=-1>View Orders</font></a></td><td width=100 align='center'><a target=_new href="/parcel/index.phtml"><font size=-1>Parcels</font></a></td></tr> <?
}
/*
<div id="topmenu" class="menu"><table class="menu"><tr bgcolor=pink><td bgcolor=yellow width=170><font size=-1><b><?=getenv("REMOTE_USER")?><? if( isset($cookiedepot) ) print " (<a href=\"changedepot.phtml\">$cookiedepot</a>)"; else print " (<a href=\"changedepot.phtml\">None</a>)"; ?><? if( isset($cookiecompany) ) print " (<a href=\"changedepot.phtml\">$cookiecompanyname</a>)"; else print " (<a href=\"changedepot.phtml\">None</a>)"; ?></b></font></td><td width=100><font size=-1><a href="index.phtml">MOVE Menu</a></font></td><td width=110><font size=-1><a href="manageindex.phtml">Management</a></font></td><td width=100><a href="checkjobcard.phtml?stage=0&recent=1"><font size=-1>Find Job Card</font></a></td><td width=100><a href="requestorder.phtml?stage=1&filter=2&depot=<?=$cookiedepot?>"><font size=-1>View Orders</font></a></td><td width=100><a target=_new href="https://secure.intercape.co.za/parcel/index.phtml"><font size=-1>Parcels</font></a></td><td bgcolor=red><font color=yellow size=-1><b>TEST</b></font></td></tr></table></div><br>
*/

if ($REMOTE_USER=="Johann")
{

 $cookiedepot="CA";
        $cookiecompany=0;

}

if ($included=="Y" ||   $REMOTE_USER=="dummy" ) {
	$cookiedepot="CA";
	$cookiecompany=0;
}

if( !isset( $cookiedepot ) && $REMOTE_USER!="dummy" && $included!="Y" ) {
/*	print "<b>You <font color=red><u>must</u></font> set your depot before you can continue</b><br><br>";
	print "<a href=\"changedepot.phtml\">Click here</a> to change your depot</a>";
	echo "<hr>";
	echo getenv("REQUEST_URI");
	exit;
*/
	$ip=explode(".",getenv("REMOTE_ADDR"));
	switch ($ip) {
		case 8:
		case 9:
		case 10:
		case 11:
		case 210:
		case 211:  $cookiedepot="CA"; break;

		case 35:  $cookiedepot="DBN"; break;
	
		case 14:
		case 15:
		case 15:	$cookiedepot="PE"; break;	


		default:  $cookiedepot="PTA";
	} // switch
}

if( !isset( $cookiecompany ) && $REMOTE_USER!="dummy" && $included!="Y") {
/*
	print "<b>You <font color=red><u>must</u></font> set your company before you can continue</b><br><br>";
	print "<a href=\"changedepot.phtml\">Click here</a> to change your company</a>";
	exit;
*/	
	$cookiecompany=0;
}

if( !isset( $cookiecompanyname ) && $REMOTE_USER!="dummy" && $included!="Y") {
/*
	print "<b>You <font color=red><u>must</u></font> set your company before you can continue</b><br><br>";
	print "<a href=\"changedepot.phtml\">Click here</a> to change your company</a>";
	exit;
*/

	$cookiecompanyname="INTERCAPE MAINLINER (PTY) LTD";
}
?>
<?  if( $REMOTE_USER != "Keeith" ) { /*print "Try again later"; exit;*/ }; ?>
<?
	require_once("../php3/oracle.inc");
    require_once("../php3/sec.inc");

    if( !open_oracle() )
    	exit;
	
	function domenuStuff() { 	//Watch out for overriding variables in some other script!!! (hence the function)
		global $cursor, $cookiecompany, $cookiecompanyname, $REMOTE_USER, $included, $stage;

		
if ($included=="Y" ||   $REMOTE_USER=="dummy" ) {
        $cookiedepot="CA";
        $cookiecompany=0;
}


		
		ora_parse( $cursor, "SELECT serial FROM MOVE_COMPANY WHERE serial=$cookiecompany " );
		ora_exec( $cursor );
		if( !ora_fetch( $cursor ) ) {
			print "<div class=info>Something has gone wrong with your company setting. Please <a href='changedepot.phtml'>go here</a> ";
			print "and set your company.</div>";
			exit;
		}
	
//		ora_parse( $cursor, "SELECT COUNT(*) FROM PURCHASE_REQUESTS WHERE order_no=-1 AND superceded_by=-1 AND purchase_category IN ( SELECT category FROM PURCHASE_AUTH WHERE username='$REMOTE_USER' AND master='Y' )" );
		ora_parse( $cursor, "SELECT /*+  USE_NL(B,A)  */ COUNT(A.order_no) FROM PURCHASE_REQUESTS A,PURCHASE_AUTH B WHERE order_no=-1 and A.purchase_category=B.category AND superceded_by=-1 and username='$REMOTE_USER' AND master='Y'");
		ora_exec( $cursor );
		$orders = ora_getColumn( $cursor, 0 );
		
		ora_parse( $cursor, "SELECT COUNT(*) FROM PURCHASE_REQUESTS WHERE order_no=-1 AND superceded_by=-1 AND depot IN ( SELECT depot FROM PURCHASE_COST_CENTER WHERE serial IN ( SELECT serial FROM PURCHASE_COST_CENTER WHERE depot='$cookiedepot' ) ) AND purchase_category IN ( SELECT category FROM PURCHASE_AUTH WHERE username='$REMOTE_USER' AND master='Y' )" );
		ora_exec( $cursor );
		$dorders = ora_getColumn( $cursor, 0 );
	
		$text = "";
		
		if( $orders>0 ) {
			$text .= "<b><font color=red>NB</font></b>: There are <b>$orders</b> orders that you can authorise";
			
			if( $dorders>0 )
				$text .=  " (<b>$dorders</b> from your cost center)";
			else $text .= " (<b>none</b> from your cost center)";
			
			$count = 0;
			$text .= "<br>";
//			ora_parse( $cursor, "SELECT request_no FROM PURCHASE_REQUESTS WHERE order_no=-1 AND superceded_by=-1 AND purchase_category IN ( SELECT category FROM PURCHASE_AUTH WHERE username='$REMOTE_USER' AND master='Y' )" );
			ora_parse( $cursor, "SELECT /*+  USE_NL(B,A)  */ A.request_no FROM PURCHASE_REQUESTS A,PURCHASE_AUTH B WHERE order_no=-1 and A.purchase_category=B.category AND superceded_by=-1 and username='$REMOTE_USER' AND master='Y'");
			ora_exec( $cursor );
			while( ora_fetch( $cursor ) && $count++<10 ) {
				$ser = ora_getColumn( $cursor, 0 );
				$text .= "<a target=_blank href=\"requestorder.phtml?stage=4&ser=$ser\">Q$ser</a>, ";
			}
			$text .= "<a href=\"requestorder.phtml?stage=1&filter=2\">.....</a><br>";
		}

	

$ovdefault="";
$rusername=getenv("REMOTE_USER");
ora_parse($cursor,"select category from overtime_masters where username='$rusername' and active='Y'");
ora_exec($cursor);
if (ora_fetch($cursor)) {
        $ovallowed=ora_getcolumn($cursor,0);
        if ($ovallowed=="A")
                $ovdefault="category!='P'";
        else
                $ovdefault="category='$ovallowed'";
}


if ($ovdefault!="") {
        $qqry = "SELECT COUNT( * ) FROM overtime WHERE status='O' AND $ovdefault";
        ora_parse( $cursor, $qqry);
        ora_exec( $cursor);
        $ovtime = ora_getColumn( $cursor, 0);
        if ( $ovtime > 0 ) {

                $text.= "There are <b>$ovtime Overtime entries</b> you can authorise.";
                if ( $ovtime > 0 )
                        $text.= " <a href='/booking/overtime.phtml?mode=W&branch=-1'>click here</a> to approve them<br>";
                else $text.= "<br>";

        }
}


		
		$qry = "SELECT COUNT( * ) FROM PETTY_CASH A, PETTY_CASH_CATEGORIES B, PURCHASE_AUTH C WHERE A.category=B.serial AND B.budget_serial=C.category AND C.username='$REMOTE_USER' AND A.approved='N' AND A.branch_done='Y' AND C.master='Y' AND A.deleted='N'";
		ora_parse( $cursor, $qry );
		ora_exec( $cursor );
		$petty = ora_getColumn( $cursor, 0 );
		
		if( $petty>0 )
			$text .= "There are <b>$petty petty cash orders</b> you can authorise. <a href='../booking/petty_cash_approval.phtml'>Click here</a> to approve them";
	        else {
                   ora_parse($cursor,"select count(*) from petty_cash A, petty_cash_offices B, user_details C where C.username='$REMOTE_USER' and C.user_serial=B.area_manager and B.office_serial=A.office_serial and entry_date>=to_date('20120501','YYYYMMDD') and branch_done='Y' and deleted='N' and (approved_by!='$REMOTE_USER' or approved_by is null)  and category>0 and area_manager_approval is null");
                   ora_exec($cursor);
                   ora_fetch($cursor);

                   $petty=getdata($cursor,0);
                   if ($petty>0)
                        $text.= "<br>There are <b>some petty cash orders</b> you can authorise. <a href='../booking/petty_cash_approval.phtml'>Click here</a> to approve them";
		}	
		$qry  = "SELECT SUM( A.total ), C.name ";
		$qry .= "FROM PURCHASE_RUNNING A, PURCHASE_AUTH B, PURCHASE_BUDGET_NAMES C ";
		$qry .= "WHERE A.rundate like '". date( "Ym" ) . "%' AND A.budget=B.category AND B.username='$REMOTE_USER' AND B.company=A.company AND A.budget=C.serial AND B.master='Y' ";
		$qry .= "GROUP BY C.name ";
		ora_parse( $cursor, $qry );
		ora_exec( $cursor );
		while( ora_fetch( $cursor ) ) {
			$text .= "<br>" . ora_getColumn( $cursor, 1 ) . " - <b>R</b> " . number_format( ora_getColumn( $cursor, 0 ), 2 ) . " spent this month";
		}
    
/*
		if( AllowedFlag( "MOVE_PUMP" ) ) {

                         // get today's date
                            $date = getdate();
    
                         // Calculate yesterday's date
                            $timestamp = mktime(0,0,0,$date[mon],$date[mday],$date[year]);
                            $todays_date = date("Ymd");
                            $yesterday = $timestamp - 1;  
                            $yesterdays_date = date("Ymd",$yesterday);
                            
                         // Check if yesterday was a weekend and prevent message if it is
                            $day_of_week = date("D",$yesterday);
                            
			ora_parse( $cursor, "SELECT bowserserial, description, litres, min FROM MOVE_BOWSER WHERE litres*100/125<min ORDER BY description" );
			ora_exec( $cursor );
			while( ora_fetch( $cursor ) ) {
				$bserial = ora_getColumn( $cursor, 0 );
				$desc = ora_getColumn( $cursor, 1 );
				$litres = round( ora_getColumn( $cursor, 2 ) );
				$min = ora_getColumn( $cursor, 3 );
				
				if( $litres<$min )
					$text .= "<br><b><font color=red>$bserial - $desc</font></b>: has $litres<font color=red><i>l</i></font>, min is $min<font color=red><i>l</i></font>";
				   else $text .= "<br><font color=orange>$bserial - $desc</font>: has $litres<font color=red><i>l</i></font>, min is $min<font color=red><i>l</i></font>";
			}
			

			
		     // Search for dip reading not taken yesterday
			ora_parse( $cursor, "SELECT bowserserial, description FROM MOVE_BOWSER WHERE bowserserial NOT IN ( SELECT location FROM MOVE_FUEL WHERE lcode=10 AND to_char( datepurchased, 'YYYYMMDD' ) >= '" .$yesterdays_date. "' ) ORDER BY bowserserial" ); //date( "Ymd" )
			ora_exec( $cursor );
			
			while( ora_fetch( $cursor ) ) {
				$bserial = ora_getColumn( $cursor, 0 );
				$desc = ora_getColumn( $cursor, 1 );

				if ($day_of_week != "Sun" && $day_of_week != "Sat"){$text .= "<br><font color=red>No dip reading has taken place since yesterday! : $bserial - $desc";}
				
				unset($data);
			}
		}
*/		
		
		if( $text!="" ) {
			?> <tr bgcolor=white><td colspan=4><font size=-1><marquee height=20 scrolldelay=100 scrollamount=1 direction=up onMouseEnter=' this.stop(); ' onMouseLeave=' this.start(); '><?=$text?></font></marquee></td></tr> <?
		}
	}

// removed - move audit march 2009 as data could get out date and cause confusion
    if( $REMOTE_USER!="dummy" ) {
		//echo "(The scrolling info has been removed.  Please use the menu instead)"; // domenuStuff();
	
	require_once("../booking/info_button.phtml");

	}

	$oracleopenalready=true;
//    close_oracle();
?>
</table></div><br>


<?
/* ************************************************************************* */
/* ************ Security functions to limit acces to users ***************** */
/* ************************************************************************* */

//$REMOTE_USER=getenv("REMOTE_USER");
unset ($_global_sec);

unset($_staff_member);

function AllowedFlag($flag,$log=true)
{
	global $conn, $REMOTE_USER, $_changepw, $_SHOPRITE, $_global_sec, $_staff_member, $otrusername,$mystafftype;
	if (isset($_global_sec[$flag]))
		return $_global_sec[$flag];

	$secursor = ora_open($conn);
	// SYSTEM UNDER LOAD:
	$log=false;

	if ($REMOTE_USER=="")
		return false;

	$qry = "select user_serial,branch,staff_member,pw_reset,is_current,master_user,stafftype from user_details where ";
	$qry.= "username='$REMOTE_USER' and is_current in ('Y','L')";
	if ($REMOTE_USER=="dummy")
		$log=false;
	ora_parse($secursor,$qry);
	ora_exec($secursor);
	ora_fetch($secursor);

	$mystafftype=ora_getColumn($secursor,6);

        if (ora_getcolumn($secursor,4)=="L") {
                echo "<p><font color=red><b>Your account has been LOCKED OUT due to
 too many incorrect password attempts.</b><p>Please contact HR (If you are a staff member) or Marketing (if you are an agent) on +27 21 3804444 / (021) 3804444</font>";
                ora_rollback($conn);
                exit;
        }

   if (ora_getColumn($secursor,3)=="Y" && $_changepw!="Y" && !$_SHOPRITE && $otrusername=="" && $mystafftype!="M") {
                echo "\n<script>\nalert('Your password has expired\\n\\nClick Ok, then log in again with your old password\\nThen, follow the prompts.'); window.location='/password/change_password_1.phtml?force=Y'\n</script>";
                echo "You must change your password!  <a href=/password/change_password_1.phtml?force=Y>Click here to continue</a>";
                exit;
        }


	if (ora_getcolumn($secursor,5)>0) {
		echo "*******************";
		$qry = "select user_serial,branch,staff_member,pw_reset,is_current,master_user from user_details where ";
	        $qry.= "user_serial=".ora_getcolumn($secursor,5);
        	if ($REMOTE_USER=="dummy")
                	$log=false;
        	ora_parse($secursor,$qry);
	        ora_exec($secursor);
       		ora_fetch($secursor);

		if (ora_getcolumn($secursor,4)=="L") {
       		         echo "<p><font color=red><b>Your account has been LOCKED OUT due to
 too many incorrect password attempts.</b><p>Please contact HR (If you are a staff member) or Marketing (if you are an agent) on +27 21 3804444 / (021) 3804444</font>";
               		 ora_rollback($conn);
                	exit;
       		 }

	}

	$serial= chop(ora_getColumn($secursor,0));

        $br = chop(ora_getColumn($secursor,1));
	$sm = chop(ora_getColumn($secursor,2));
	$_staff_member=$sm;

        /*  Test for a SUSPENDED User  - new 30/03/2001 */
        if ($sm=="S"):
                echo "<h3>Your Account has been SUSPENDED....!</h3>";
                ora_parse($secursor,"select reason from suspended_accounts where branch='$br' or user_serial='$serial'");
                ora_exec($secursor);
                while (ora_fetch($secursor)):
                        echo ora_getColumn($secursor,0)."<br>";
                endwhile;
                exit;
        endif;

	$qry = "select 'x' from user_pages where user_serial='$serial' ";
	$qry.= "and page_name='$flag' and ( expires is null or expires>=SYSTIMESTAMP)";
	ora_parse($secursor,$qry);
	ora_exec($secursor);
	if (ora_fetch($secursor)) {
		$retval = true;
	 	if ($log) {
                        ora_parse($secursor,"update user_pages set last_used=SYSDATE where user_serial='$serial' and page_name='$flag' and last_used<sysdate-1");
                        ora_exec($secursor);
                }

	} else {
		$qry="select A.page_name from user_pages A, user_roles B where A.page_name=role_name and B.role_flag='$flag' and user_serial='$serial' and ( expires is null or expires>=SYSTIMESTAMP)";
//		echo "$qry<BR>";
		ora_parse($secursor,$qry);
	        ora_exec($secursor);
       		if (ora_fetch($secursor))
		{
                	$retval = true;
//			echo "GOT IN ON ROLE<Br>";
			if ($log) {
				$role=ora_getcolumn($secursor,0);
				ora_parse($secursor,"update user_pages set last_used=SYSDATE where user_serial='$serial' and page_name='$role' and last_used<sysdate-1");
				ora_exec($secursor);
				

			}
		}
	        else {
			$retval = false;
		}
	}

	ora_close($secursor);
	$_global_sec[$flag]=$retval;
	return $retval;
}

function AllowedAccess($flag="",$log=true)
{
	global $conn, $PHP_SELF, $REMOTE_ADDR, $REMOTE_USER, $_changepw,  $_staff_member, $otrusername, $kkwwdebug, $mystafftype;

	$secursor = ora_open($conn);

	if ($kkwwdebug) {
		echo "Env is ".getenv("REMOTE_USER");
		echo "<br>RU is $REMOTE_USER<br>";
	}

	/* ************ Checks to see if username is valid ********** */
	/* ********************************************************** */
	$qry = "select username, NULL, user_serial, staff_member, branch, pw_reset, is_current, master_user, stafftype from user_details where ";
	$qry.= "username='$REMOTE_USER' and is_current in ('Y','L')";

	 if ($REMOTE_USER=="dummy")
                $log=false;

      // SYSTEM UNDER LOAD:
        $log=false;


	ora_parse($secursor, $qry);
	ora_exec($secursor);
	if (!ora_fetch($secursor)):
			echo "</font><hr><table><tr bgcolor=yellow><td><b>Sorry, username $REMOTE_USER is no longer valid.<br>";
			echo "Please contact your network administrator.</td></tr></table></hr>";
			ora_close($secursor);
			return false;
	endif;

	$mystafftype=ora_getcolumn($secursor,8);

	if (ora_getcolumn($secursor,6)=="L") {
		echo "<p><font color=red><b>Your account has been LOCKED OUT due to too many incorrect password attempts.</b><p>Please contact HR (If you are a staff member) or Marketing (if you are an agent) on +27 21 3804444 / (021) 3804444</font>";
		ora_rollback($conn);	
		exit;
	}

   if (ora_getColumn($secursor,5)=="Y" && $_changepw!="Y" && $otrusername=="" && $mystafftype!="M") {
                echo "\n<script>\nalert('Your password has expired\\n\\nClick Ok, then log in again with your old password\\nThen, follow the prompts.'); window.location='/password/change_password_1.phtml?force=Y'\n</script>";
                echo "You must change your password!  <a href=/password/change_password_1.phtml?force=Y>Click here to continue</a>";
                exit;
        }

     if (ora_getcolumn($secursor,7)>0) {
		echo "************************";
                $qry = "select user_serial,branch,staff_member,pw_reset,is_current,master_user from user_details where ";
                $qry.= "user_serial=".ora_getcolumn($secursor,7);
                ora_parse($secursor,$qry);
                ora_exec($secursor);
                ora_fetch($secursor);
		if (ora_getcolumn($secursor,6)=="L") {
                	echo "<p><font color=red><b>Your account has been LOCKED OUT due to too many incorrect password attempts.</b><p>Please contact HR (If you are a staff member) or Marketing (if you are an agent) on +27 21 3804444 / (021) 3804444</font>";
                	ora_rollback($conn);
       		         exit;
	        }


}


	$ip = chop(ora_getColumn($secursor,1));
	$us = chop(ora_getColumn($secursor,2));
	$sm = chop(ora_getColumn($secursor,3));
	$br = chop(ora_getColumn($secursor,4));
	 $_staff_member=$sm;

	/*  Test for a SUSPENDED User  - new 30/03/2001 */
 	if ($sm=="S"):
		echo "<h3>Your Account has been SUSPENDED....</h3>";
		ora_parse($secursor,"select reason from suspended_accounts where branch='$br' or user_serial='$us'");
		ora_exec($secursor);
		while (ora_fetch($secursor)):
			echo ora_getColumn($secursor,0)."<br>";
		endwhile;
		exit;
	endif;

	
	
	/* ****** Regenerates the main page if username is 'dummy' ******** */
	/* **************************************************************** */
	if ($REMOTE_USER=="dummy"):
		if (($PHP_SELF!="/booking/main-generate.phtml") && 
		    ($PHP_SELF!= "/booking/report_dailysales_2.phtml")):
			$ip="Page $PHP_SELF  Not Allowed";
		endif;

		if (($REMOTE_ADDR=="127.0.0.1")||($REMOTE_ADDR=="192.168.10.4"))
    		{ $ip = $REMOTE_ADDR; }
		else
		{ $ip = "IP Address Not Allowed."; }
	endif;

	/* ************ Checks IP Address Against Database IP ************* */
	/* **************************************************************** */
	/* Check to see if variable $sm is Y or N to determine agent access */

	$ip=$REMOTE_ADDR;

	if ($ip != $REMOTE_ADDR && $sm=="Y"):
		if ($ip!=""):
			echo "The IP Address of your computer and the address ";
			echo "stored in the database do not match.<br>";
			echo "Your IP Address is $REMOTE_ADDR.<br>";
			echo "The Database IP is $ip<br>";
			ora_close($secursor);
			return false;
		endif;
  		if (($ip=="")||($ip=="0.0.0.0")):
			$qry ="update user_details set user_ip='$REMOTE_ADDR' ";
			$qry.="where username ='$REMOTE_USER' and ";
			$qry.="is_current='Y'";
			ora_parse($secursor,$qry);
			ora_exec($secursor);
  		else:
			echo "The IP Address of your computer and the address ";
			echo "stored in the database do not match.<br>";
			echo "Your IP Address is $REMOTE_ADDR.<br>";
			echo "The Database IP is $ip<br><br>";
			echo "Please Contact your network administrator.<br>";
    			ora_close($secursor); 
    			return false;
		endif;
	endif;

	/* ************* Checks to see if allowed access to ************** */
	/* *************    A specific page if specified    ************** */
	if ($flag!="") {
		$qry = "select 'x' from user_pages where user_serial='$us' ";
		$qry.= "and page_name='$flag' and ( expires is null or expires>=SYSTIMESTAMP)";
		@ora_parse($secursor,$qry);
		@ora_exec($secursor);
		if (!@ora_fetch($secursor)) {
//---
                  $qry="select A.page_name from user_pages A, user_roles B where A.page_name=role_name and B.role_flag='$flag' and user_serial='$us' and ( expires is null or expires>=SYSTIMESTAMP)";
//              echo "$qry<BR>";
                  ora_parse($secursor,$qry);
                  ora_exec($secursor);
                  if (!ora_fetch($secursor)) {
//---
			ora_parse($secursor,"select description from page_codes where page_name='$flag'");
			ora_exec($secursor);
			ora_fetch($secursor);
			$desc=trim(ora_getcolumn($secursor,0));

			echo "Sorry, You do not have enough rights to<br>";
			echo "access this page. Please contact your<br>";
			echo "network administrator if you need access.<br><br><u>Flag Needed:</u> $flag: (\"$desc\")<br><b>Your Username: ".getenv("REMOTE_USER")."<bR>";
			ora_close($secursor);
		        echo "<form>";
		        echo "<input type=button value='Go Back' ";
		        echo "onclick='javascript:history.back()'>";
		        echo "</form>";
			return false;
		   } // role fetch
		   elseif ($log) { // got role
	
				$role=trim(ora_getcolumn($secursor,0));
				ora_parse($secursor,"update user_pages set last_used=SYSDATE where useR_serial='$us' and page_name='$role'");
	                        ora_exec($secursor);
			}
		} // flag fetch
		elseif ($log) {
			ora_parse($secursor,"update user_pages set last_used=SYSDATE where useR_serial='$us' and page_name='$flag'");
			ora_exec($secursor);
		}
	} // flag
	ora_close($secursor);
	return true;
}

function old_icapesecuritycheck($manfunc="")
{
	global $conn , $PHP_SELF;

	/***** SECURITY CHECK CODE *********/

	$addr=chop(getenv("REMOTE_ADDR"));
	$uname=getenv("REMOTE_USER");

	$qry="select username, user_ip, user_serial from user_details where username='$uname' and is_current='Y'";
	$cursor1=ora_open($conn);
	ora_parse($cursor1,$qry);
	ora_exec($cursor1);
	if (!ora_fetch($cursor1))
	{
		echo "<h2>Sorry, your username is not valid</h2>";
		ora_close($cursor1); 
		return 1; 
		exit;
	}

	$ip=chop(ora_getColumn($cursor1,1));
        if ($uname=="dummy")
         {
/*
		echo "This is $PHP_SELF <br>";
*/
		if (($PHP_SELF != "/booking/main-generate.phtml")&&($PHP_SELF != "/booking/report_dailysales_2.phtml"))
		{  $ip="Page $PHP_SELF  Not Allowed"; }
		if (($addr=="127.0.0.1")||($addr=="192.168.10.4"))
    		{
		   $ip=$addr;	

		}
		else
		{
                  $ip="Not allowed";
			}

          }
	if ($ip != $addr )
	{
		if ($ip!=""):
	 		echo "IP Addresses do not match.<br>"; 
			echo "Database IP is $ip, your IP is $addr<br>";
		endif;
  		chop($ip); 
  		if (($ip=="")||($ip=="0.0.0.0"))
		{
			$qry="update user_details set user_ip = '$addr' where username ='$uname' and is_current='Y'";
			$cursor2=ora_open($conn);
			ora_parse($cursor2,$qry);
			ora_exec($cursor2);
			ora_close($cursor2);
		}
  		else 
		{
    			echo "<h2>Username in use by another IP Address</h2>";  
    			echo "PLEASE CONTACT YOUR NETWORK MANAGER.<br>";
			echo "Database IP is $ip, your IP is $addr<br>";
    			ora_close($cursor1); 
    			return 1;
    			exit;
  		}
	}
	/* end of IP Checkking */

	$serial= ora_getColumn($cursor1,2);

	if ($manfunc != "") 
	{
	    $qry="select $manfunc from user_info where user_serial='$serial'";
	    $cursor3=ora_open($conn);
	    ora_parse($cursor3,$qry);
	    ora_exec($cursor3);

		$manage= ora_getColumn($cursor3,0);
		ora_close($cursor3);
		if ($manage!="Y")
		{
			echo "YOU ARE NOT ALLOWED ACCESS TO THIS!";
			ora_close($cursor1); 
			return 1;
			exit;
		}
	}
	ora_close($cursor1); 
	return 0;
}


function old_icapesecurityflag($flag)
{
	global $conn;

	/***** SECURITY FLAG CHECK CODE *********/
	$uname=getenv("REMOTE_USER");

	$qry="select user_serial from user_details where username='$uname' and is_current='Y'";
	$cursor1=ora_open($conn);
	ora_parse($cursor1,$qry);
	ora_exec($cursor1);
	$serial= ora_getColumn($cursor1,0);
	ora_close($cursor1); 
	$qry="select $flag from user_info where user_serial = '$serial'";

	$cursor3=ora_open($conn);
	ora_parse($cursor3,$qry);
	ora_exec($cursor3);
	$manage= ora_getColumn($cursor3,0);
	ora_close($cursor3);
	return $manage;
}
?>

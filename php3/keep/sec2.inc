<?
/* ************************************************************************* */
/* ************ Security functions to limit acces to users ***************** */
/* ************************************************************************* */

//$REMOTE_USER=getenv("REMOTE_USER");
unset ($_global_sec);


function AllowedFlag2($flag)
{
        global $REMOTE_USER, $_changepw, $_SHOPRITE, $_global_sec, $dbcon;

        if (isset($_global_sec[$flag]))
                return $_global_sec[$flag];


        $qry = "select user_serial,branch,staff_member,pw_reset from user_details where ";
        $qry.= "username=:remuser and is_current=:iscurrent";
	// remuser   iscurrent
        $myq=oci_parse($dbcon,$qry);
	ocibindbyname($myq,":remuser",$REMOTE_USER,-1);
	$y="Y";
	ocibindbyname($myq,":iscurrent",$y,-1);
	ociexecute($myq, OCI_DEFAULT);

        //ora_fetch($secursor);
	if (ocifetchrow($sth, $row, OCI_RETURN_NULLS)) {
	     if ($row[3]=="Y" && $_changepw!="Y" && !$_SHOPRITE) {
                echo "\n<script>\nalert('Your password has expired\\n\\nClick Ok, then log in again with your old password\\nThen, follow the prompts.'); window.location='/password/change_password_1.phtml?force=Y'\n</script>";
                echo "You must change your password!  <a href=/password/change_password_1.phtml?force=Y>Click here to continue</a>";
                exit;
       	     }

	}
	else exit;


        $serial= chop($row[0]);

        $br = chop($row[1]);
        $sm = chop($row[2]);

        /*  Test for a SUSPENDED User  - new 30/03/2001 */
        if ($sm=="S") {
                echo "<h3>Your Account has been SUSPENDED....!</h3>";
                $myq=oci_parse($dbcon,"select reason from suspended_accounts where branch=:br or user_serial=:serial");
		ocibindbyname($myq,":br",$br,-1);
		ocibindbyname($myq,":serial",$serial,-1);
	
                ociexecute($myq,OCI_DEFAULT);
                while (ocifetchrow($sth, $row, OCI_RETURN_NULLS)) {
                        echo $row[0]."<br>";
                }
                exit;
        }

        $qry = "select 'x' from user_pages where user_serial=:serial ";
        $qry.= "and page_name=:flag";
        $myq=oci_parse($dbcon,$qry);
	ocibindbyname($myq,":serial",$serial,-1);
	ocibindbyname($myq,":flag",$flag,-1);

	ociexecute($myq,OCI_DEFAULT);

        if (ocifetchrow($sth, $row, OCI_RETURN_NULLS))
                $retval = true;
        else
                $retval = false;

        $_global_sec[$flag]=$retval;
        return $retval;
}


function AllowedFlag($flag)
{
	global $conn, $REMOTE_USER, $_changepw, $_SHOPRITE, $_global_sec, $dbcon;

	if (isset($dbcon))
		return AllowedFlag2($flag); // new code

	if (isset($_global_sec[$flag]))
		return $_global_sec[$flag];

	$secursor = ora_open($conn);

	$qry = "select user_serial,branch,staff_member,pw_reset from user_details where ";
	$qry.= "username='$REMOTE_USER' and is_current='Y'";
	ora_parse($secursor,$qry);
	ora_exec($secursor);
	ora_fetch($secursor);
   if (ora_getColumn($secursor,3)=="Y" && $_changepw!="Y" && !$_SHOPRITE) {
                echo "\n<script>\nalert('Your password has expired\\n\\nClick Ok, then log in again with your old password\\nThen, follow the prompts.'); window.location='/password/change_password_1.phtml?force=Y'\n</script>";
                echo "You must change your password!  <a href=/password/change_password_1.phtml?force=Y>Click here to continue</a>";
                exit;
        }

	$serial= chop(ora_getColumn($secursor,0));

        $br = chop(ora_getColumn($secursor,1));
	$sm = chop(ora_getColumn($secursor,2));

        /*  Test for a SUSPENDED User  - new 30/03/2001 */
        if ($sm=="S") {
                echo "<h3>Your Account has been SUSPENDED....!</h3>";
                ora_parse($secursor,"select reason from suspended_accounts where branch='$br' or user_serial='$serial'");
                ora_exec($secursor);
                while (ora_fetch($secursor)) {
                        echo ora_getColumn($secursor,0)."<br>";
                }
                exit;
        }

	$qry = "select * from user_pages where user_serial='$serial' ";
	$qry.= "and page_name='$flag'";
	ora_parse($secursor,$qry);
	ora_exec($secursor);
	if (ora_fetch($secursor))
		$retval = true;
	else
		$retval = false;

	ora_close($secursor);
	$_global_sec[$flag]=$retval;
	return $retval;
}

function AllowedAccess($flag="")
{
	global $conn, $PHP_SELF, $REMOTE_ADDR, $REMOTE_USER, $_changepw;

	$secursor = ora_open($conn);

	/* ************ Checks to see if username is valid ********** */
	/* ********************************************************** */
	$qry = "select username, NULL, user_serial, staff_member, branch, pw_reset from user_details where ";
	$qry.= "username='$REMOTE_USER' and is_current='Y'";
	ora_parse($secursor, $qry);
	ora_exec($secursor);
	if (!ora_fetch($secursor)):
		echo "</font><hr><table><tr bgcolor=yellow><td><b>Sorry, username $REMOTE_USER is no longer valid.<br>";
		echo "Please contact your network administrator.</td></tr></table></hr>";
		ora_close($secursor);
		return false;
	endif;

   if (ora_getColumn($secursor,5)=="Y" && $_changepw!="Y") {
                echo "\n<script>\nalert('Your password has expired\\n\\nClick Ok, then log in again with your old password\\nThen, follow the prompts.'); window.location='/password/change_password_1.phtml?force=Y'\n</script>";
                echo "You must change your password!  <a href=/password/change_password_1.phtml?force=Y>Click here to continue</a>";
                exit;
        }

	$ip = chop(ora_getColumn($secursor,1));
	$us = chop(ora_getColumn($secursor,2));
	$sm = chop(ora_getColumn($secursor,3));
	$br = chop(ora_getColumn($secursor,4));

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
	if ($flag!=""):
		$qry = "select * from user_pages where user_serial='$us' ";
		$qry.= "and page_name='$flag'";
		@ora_parse($secursor,$qry);
		@ora_exec($secursor);
		if (!@ora_fetch($secursor)):
			ora_parse($secursor,"select description from page_codes where page_name='$flag'");
			ora_exec($secursor);
			ora_fetch($secursor);
			$desc=trim(ora_getcolumn($secursor,0));

			echo "Sorry, You do not have enough rights to<br>";
			echo "access this page. Please contact your<br>";
			echo "network administrator if you need access.<br><br><u>Flag Needed:</u> $flag: (\"$desc\")<br>";
			ora_close($secursor);
        echo "<form>";
        echo "<input type=button value='Go Back' ";
        echo "onclick='javascript:history.back()'>";
        echo "</form>";
			return false;
		endif;
	endif;
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

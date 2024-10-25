<?php


ob_end_flush(); // fix php5 feature/bug


// register global variables for legacy code...

if (!isset($REMOTE_USER))
       $REMOTE_USER=getenv("REMOTE_USER");


if (!isset($skipglobals)) {
    $superglobals = array($_SERVER, $_ENV,
        $_FILES, $_COOKIE, $_POST, $_GET);
    if (isset($_SESSION)) {
        array_unshift($superglobals, $_SESSION);
    }
    foreach ($superglobals as $superglobal) {
        extract($superglobal, EXTR_SKIP);
    }
    unset($superglobals);
	 if ($_COOKIE['otrusername']!="")
		$otrusername=$_COOKIE['otrusername'];
}

// NB: there must be NO BLANK LINES outside of the php code - it breaks graphs


//	echo $_SERVER[REQUEST_URI];

$regdebug="1";
if (substr($_SERVER[SCRIPT_NAME],0,5)=="/move")
{
	$regdebug.="2";
	$checkurl=$_SERVER[REQUEST_URI];
	
	$loadignite=true;

	if (getenv("REMOTE_USER")=="dummy") {

		 $loadignite=false;
                $regdebug="d";

	}

	if (strstr($checkurl,"/showcompany.phtml"))
	{
		$loadignite=false;
		$regdebug="3";
	}

	
        if (strstr($checkurl,"/jc_check.phtml"))
        {
                $loadignite=false;
                $regdebug="J";
        }

        if (strstr($checkurl,"/document_show.phtml"))
	{
                $loadignite=false;
		$regdebug="4";
	}

      if (strstr($checkurl,"/move/payaccess/"))
        {
                $loadignite=false;
                $regdebug="p";
        }




	
	  if (strstr($checkurl,"/barcode.phtml?"))
	{
                $loadignite=false;
		$regdebug="5";
	}

	if (strstr($checkurl,"/sales.phtml?stage=5"))
	{
		$loadignite=false;
		$regdebug="6";
	}
	
        if (strstr($checkurl,"/style.css"))
	{
                $loadignite=false;
		$regdebug="7";
	}


        if (strstr($checkurl,"/showstk.phtml"))
	{
                $loadignite=false;
		$regdebug="8";
	}

	if (strstr($checkurl,"scrapstock.phtml?showpic="))
	{
		$loadignite=false;

		$regdebug="9";
	}



	if ($loadignite) {


	$regdebug.="I";

//      echo "Including ignite:";
	if ($_SERVER[REMOTE_ADDR]=="192.168.10.4" || $_SERVER[REMOTE_ADDR]=="127.0.0.1") 
	{	
//		echo "Batch";
		 putenv("REMOTE_USER=dummy"); 
		$REMOTE_USER="dummy";
		$regdebug.="d";
	}
	else
	{
//		echo " going to ignite<Br>";
		
		$regdebug.="s";
	        require_once("/usr/local/www/pages/ignite/session.php");
		
		
	}


	} // loadignite
	else {
		  putenv("REMOTE_USER=dummy");
                $REMOTE_USER="dummy";
		echo "Coming from ". $_SERVER[REMOTE_ADDR]."<BR>";
		$regdebug.="N";

	}

}



?>

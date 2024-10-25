<?
/* ******************************************************************************************** */
/* *********** Basic POP3 Mail functions to check if mail exists on the mailserver ************ */
/* *********** Webmail program uses IMAP functions, not POP3, IMAP not compiled    ************ */
/* *********** Into this build of Apache/php. Used in toolbar.phtml                ************ */
/* ******************************************************************************************** */
function dbecho($msg)
{
	if (getenv("REMOTE_USER")=="Keeith2"):

	echo "DB: $msg<br>";
	endif;
}

function checkformail($username, $password,$debug=0)
{
	$retmsg = "";
	$username = strtolower($username);

	//echo "$username, $password<br>";
	$mailserver = "192.168.25.6";
	$port = 110;

	$conn = fsockopen($mailserver,$port,&$errno, &$errstr,30);
	$retval = fgets($conn,128);
	if (!$conn)
	{ 
		$retmsg = "Could not connect to mail server.<br>";
		dbecho("$errstr ($errno)");
		return "CONN FAILED";
	}

	dbecho("Connected to $retval",$debug);

	// **************** Sends Username
	dbecho("Sending Username $username.",$debug);
	fputs($conn, "USER $username\n");
	$retval = fgets($conn,128);
	dbecho($retval,$debug);
	if (!strpos($retval,"OK Password"))
	{
		$retmsg = "Username does not exist.";
		return "INVALID";
	}

	// ***************** Sends Password
	dbecho("Sending Password $password.",$debug);
	fputs($conn, "PASS $password\n");
	$retval = fgets($conn,128);
	dbecho($retval);
	if (!strpos($retval,"OK $username has"))
	{
		$retmsg = "Password is incorrect.";
		return "INVALID";
	} else
	$chopval = strlen($username) + 8;
	$retmsg = chop(trim(substr($retval,$chopval)));

	dbecho("Closing Connection.");
	fputs($conn, "quit\n");
	$retval = fgets($conn,128);
	dbecho($retval);

	fclose($conn);
	dbecho("Disconnected.");
	dbecho("RM: $retmsg");
	return $retmsg;
}

function misc_crypt($pw)
{
	for ($i=0; $i<strlen($pw); $i++)
	{
	}
}

function processmail($msg,$un,$pw)
{
	if ($msg=="CONN FAILED")
	{
		echo "<img align=center src=images/connfailed.gif border=0>";
	} else if ($msg=="INVALID")
	{
		echo "<img align=center src=images/password.gif border=0>";
	} else if (strpos($msg,"message"))
	{
		$data = explode(" ",$msg);
		$num = $data[0];
		settype($num,"integer");
		$num--;
		if ($num<0)
			{ $num=0; };

		if ($num==0)
		{
		  echo "<a href='http://192.168.25.8/main.html?";
		  echo "username=$un&password=$pw' target='Main'>";
	 	  echo "<img align=center src=images/nomail.gif border=0>";
		  echo "</a>";
		} else
		{
		  echo "<a href='http://192.168.25.8/main.html?";
		  echo "username=$un&password=$pw' target='Main'>";
	 	  echo "<img align=center src=images/hasmail.gif border=0>";
		  echo "</a>($num)";
		};
	} else
	{
		echo "<img align=center src=images/unknown.gif border=0>";
	}	
}
?>

<?
/* ******************************************************************************************** */
/* *********** Basic POP3 Mail functions to check if mail exists on the mailserver ************ */
/* *********** Webmail program uses IMAP functions, not POP3, IMAP not compiled    ************ */
/* *********** Into this build of Apache/php. Used in toolbar.phtml                ************ */
/* ******************************************************************************************** */
function dbecho($msg)
{
	if (getenv("REMOTE_USER")=="Keeith"):

	echo "DB: $msg<br>";
	endif;
}

function checkformail($username, $password,$debug=0)
{
	$retmsg = "";
	$username = strtolower($username);

//	echo "Trying $username/$password<bR>";

	if (!strstr($username,"@")) 
		$username.="@cavmail.co.za";

	$mbox = @imap_open ("{web.cavmail.co.za:993/imap/ssl/novalidate-cert}", "$username", "$password");

	if ($mbox === false ) {
		$retmsg =  imap_last_error();
		dbecho("Error $retmsg");
	} else {

		dbecho("Connected",$debug);

		$check = imap_mailboxmsginfo($mbox);

		$retmsg =    $check->Unread;

		imap_close($mbox);
	}

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
//	if ($un=="nicolam")
//	mail("keith@intercape.co.za","Message on $un/$pw",$msg);
//	echo "We got $msg on $un $pw<bR>";
//	exit;
	if ($msg=="CONN FAILED")
	{
		header("Location: images/connfailed.gif");
	} else if ($msg=="INVALID")
	{
		header("Location: images/password.gif");
	} else if (is_numeric($msg)) 
	{
		$num = (int)$msg;
		//settype($num,"integer");
//		echo "Num is $num<br>";
		$num--;
		if ($num<0)
			{ $num=0; };

		if ($num==0)
		{
	 	  header("Location: images/nomail.gif");
		} else
		{

//echo "test";
  Header("Content-type: x-image/jpg");
	
//	$im=imagecreate(38,12);

$im=imagecreatefromjpeg("images/hasmail.jpg");

    $orange = ImageColorAllocate($im, 220, 210, 60);
$white = imageColorAllocate( $im, 255, 255, 255 );
	$white=ImageColorAllocate($im,255,255,255);
//imageFilledRectangle( $im, 0, 0, 38, 12, $white );
	$string="[$num]";
    $px = (imagesx($im)-7.5*strlen($string))/2;
		
	if (!isset($color))
		$color = 0;
	ImageSTring($im,3,44,12,$string,$color);
    Imagejpeg($im);
    ImageDestroy($im);
	exit;


	 //	  header("Location: images/hasmail.gif");
		  //echo "</a>($num)";
		};
	} else
	{
		header("Location: images/unknown.gif");
	}	
}
?>

<?

$login_passthrough=false;

if (isset($_SERVER['PHP_AUTH_USER'])) {

	if ($_SERVER['PHP_AUTH_USER'] =='dummy' && $_SERVER['PHP_AUTH_PW'] =='ydum4') {
		putenv("REMOTE_USER=dummy");
	} else {

		// log in using supplied credentials
		$username=$_SERVER['PHP_AUTH_USER'];
		$password=$_SERVER['PHP_AUTH_PW'];
		$goto=$REQUEST_URI;
		$login_passthrough=true;
		require("/usr/local/www/pages/logon.phtml");
	}  
}

if (getenv("REMOTE_USER")=="") {

if (isset($_COOKIE["otrusername"]))
{
	$otrusername=$_COOKIE["otrusername"];
	$otrsession=$_COOKIE["otrsession"];
}
else
{
	$otrusername="";
	$otrsession="";
}


if ($otrusername=="")
	{
	header("Location: /logon.phtml?fill=$fill&goto=".urlencode($REQUEST_URI));
	exit;
	}
else {
	putenv("REMOTE_USER=$otrusername");
	$REMOTE_USER=$otrusername;
}

/*
$session_now=time();
if ($session_now-$otrsession>1200)
	{
		setcookie("otrusername","");
		header("Location: /logon.phtml?fill=$otrusername&timeout=Y&goto=".urlencode($REQUEST_URI));
		exit;
	}
*/
setcookie("otrsessionid",$now);

$yesterday = mktime(date("H"),date("i"),date("s"),date("m"),date("d")-1, date("Y")-10);
$expires = "Expires: ".date("D, d M Y H:i:s", $yesterday)." GMT";

header($expires);
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache"); // , must-revalidate");
header('Expires: now');
header("Pragma: no-cache");

if (false) // the code below BREAKS stuff
if (!isset($dontcookie)):


?>
<script>
  // reset the cookie...

  date=new(Date);
  time=date.getTime();
  time=(Math.round(time/1000));

        // set the cookie
try {
                        var expires = new Date();
                        expires.setTime( expires.getTime() + 24*60*60*365 );
                        var expireDate = expires.toGMTString();

                        document.cookie = "otrsession=<? echo time(); ?>" + ";expires=" + expireDate;
        }
  catch (error)
        { }

function resetcookie()
{

	alert('trying');
  // reset the cookie...

  date=new(Date);
  time=date.getTime();
  time=(Math.round(time/1000));

        // set the cookie
try {
                        var expires = new Date();
                        expires.setTime( expires.getTime() + 24*60*60*365 );
                        var expireDate = expires.toGMTString();

                        document.cookie = "otrsession=<? echo time(); ?>" + ";expires=" + expireDate;
        }
  catch (error)
        { }


}


</script>

<?
endif;

putenv("REMOTE_USER=$otrusername");
$REMOTE_USER=$otrusername;

} // major IF


?>

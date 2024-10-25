<?
/* ******************************************************************************** */
/* *********** Retrieves the password from the database and builds an  ************ */
/* *********** apache password file located at /usr/local/www/icape.pw ************ */
/* ******************************************************************************** */

/* pw update */


function pwupdate() 
{

global $conn;

$cursor1= Ora_Open($conn);

  $pid=getmypid();

$newfile=fopen("/usr/local/www/icape.new$pid","w+");

$newfile2=fopen("/usr/local/www/xml.new$pid","w+");



/*  $result = msql("icape", "select username,password from user_details where is_current='Y'");
        $num = msql_numrows($result);
       $i=0;
while ($i < $num) 
{
*/

ora_parse($cursor1,"select count(*) from user_details where  is_current in ('Y','L') and (master_user is null or master_user=-1) ");
ora_Exec($cursor1);
ora_Fetch($cursor1);
$checknum=getdata($cursor1,0);
if (!($checknum>0)) {

	echo "An error occurred ($checknum)<BR>";
	ora_rollback($conn);
	exit;
}

$qry="select username,password,xml_agent from user_details where is_current in ('Y','L') and (master_user is null or master_user=-1) ";
ora_parse($cursor1,$qry);
ora_exec($cursor1);

while  (ora_fetch($cursor1)){



    $loginn = chop(ora_getColumn($cursor1,0));
    $pw = ora_getColumn($cursor1,1); 
    $crypto = chop($pw); 
/* changed */
    fputs($newfile,"$loginn:$crypto\n"); 

    if (ora_getColumn($cursor1,2)=="Y")
		fputs($newfile2,"$loginn:$crypto\n");
    $ctr++;

}

if ($ctr != $checknum) {
	echo "An error occurred.  $ctr vs $checknum<br>";
	ora_rollback($conn);
	exit;
} 

fputs($newfile2,"xmltest:a8OQPB.TcK1V.\n"); // xml123

fclose($newfile); 
fclose($newfile2);

rename("/usr/local/www/icape.new$pid","/usr/local/www/icape.pw"); 

rename("/usr/local/www/xml.new$pid","/usr/local/www/xml.pw");



if (!file_exists("/usr/local/www/icape.pw") || !file_exists("/usr/local/www/xml.pw"))
{
	sleep(1);
	if (!file_exists("/usr/local/www/icape.pw"))
	{
		pwupdate();
		return;
	}
}


// squid password file:

$newfile=fopen("/usr/local/www/users.new","w+");
$newfile2=fopen("/usr/local/www/users2.new","w+");
fputs($newfile2,"file=");


$qry="select username,password from user_details where is_current in ('Y','L')  and internet='Y'";
ora_parse($cursor1,$qry);
ora_exec($cursor1);

while  (ora_fetch($cursor1)){



    $loginn = chop(ora_getColumn($cursor1,0));
    $pw = ora_getColumn($cursor1,1);
    $crypto = chop($pw);
/* changed */
    fputs($newfile,"$loginn:$crypto\n");
    fputs($newfile2,"$loginn:$crypto|\n");


}

fputs($newfile,"# This  file auto generated, do not edit! For use by proxy server (maybe you were looking for icape.pw ? ) \n");

fputs($newfile2,"# This  file auto generated, do not edit! For use by proxy server (maybe you were looking for icape.pw ? ) |\n");
//fputs($newfile2,"netstar:Hyop3Rq/8FT/Y|\n");


fclose($newfile);
fclose($newfile2);
rename("/usr/local/www/users.new","/usr/local/www/users.pwd");
rename("/usr/local/www/users2.new","/usr/local/www/users2.pwd");
system("/usr/local/bin/curl -m 30 -d @/usr/local/www/users2.pwd http://192.168.8.10/sq_users.php > /tmp/ptasquid.1 2> /tmp/ptasquid.2 &");


ora_close($cursor1); 
/* pw update */

//system(" /usr/local/bin/curl -m 3  http://172.16.0.100/pwupdate.phtml > /dev/null 2>/dev/null");


}
?>

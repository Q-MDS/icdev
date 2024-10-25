<?
/* pw update */


function pwupdate() 
{

global $conn;

$cursor1= Ora_Open($conn);


$newfile=fopen("/usr/local/www/icape.new","w+");


/*  $result = msql("icape", "select username,password from user_details where is_current='Y'");
        $num = msql_numrows($result);
       $i=0;
while ($i < $num) 
{
*/

$qry="select username,password from user_details where is_current='Y'";
ora_parse($cursor1,$qry);
ora_exec($cursor1);

while  (ora_fetch($cursor1)){



    $loginn = chop(ora_getColumn($cursor1,0));
    $pw = ora_getColumn($cursor1,1); 
    $crypto = chop($pw); 
/* changed */
    fputs($newfile,"$loginn:$crypto\n"); 

}

fclose($newfile); 
rename("/usr/local/www/icape.new","/usr/local/www/icape.pw"); 

/*echo "<br>pw file updated<br>"; 
*/

ora_close($cursor1); 
/* pw update */

}
?>

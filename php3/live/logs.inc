<?
/* ********************************************************************************* */
/* ************ Adds an entry to the manage_log table in the database ************** */
/* ********************************************************************************* */

function log_manage($user, $route, $tablename, $comments)
{
global $conn;
$logcur = ora_open($conn);
$done=true;

$cdate = mktime();
@ora_parse($logcur,"select user_serial from user_details where username='$user' and is_current='Y'");
if (!@ora_exec($logcur)):
	$done=false;
endif;

if ($done):
	@ora_fetch($logcur);
	$serial = chop(ora_getColumn($logcur,0));
	$qry = "insert into manage_log values('$serial','$route','$cdate','$comments','$tablename')";
	@ora_parse($logcur,$qry);
	if (!@ora_exec($logcur)):
		$done = false;
	else:
		$done = true;
	endif;
endif;

ora_close($logcur);
return $done;
}
?>


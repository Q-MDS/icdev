<?
function translog($logmsg)
{
	global $REMOTE_USER;
	$log = date("d/m/y H:i")." - $REMOTE_USER - $logmsg\n";
	error_log($log,3,"/usr/local/www/pages/transaction.log");
}
?>
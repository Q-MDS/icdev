<?
/* ******************************************************* */
/* ******** Adds a line to text log file ***************** */
/* ******************************************************* */
function translog($logmsg)
{
	global $REMOTE_USER;
	$log = date("d/m/y H:i")." - $REMOTE_USER - $logmsg\n";
	error_log($log,3,"/usr/local/www/pages/transaction.log");
}

function ops_translog($logmsg)
{
        global $REMOTE_USER;
        $log = date("d/m/y H:i")." - $REMOTE_USER - $logmsg\n";
        error_log($log,3,"/usr/local/www/logs/ops_transaction.log");
}

?>

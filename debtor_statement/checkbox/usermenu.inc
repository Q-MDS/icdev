<div class=menu>
<table>
	<tr bgcolor=white>
		<td bgcolor=white width=120><font size=-1><b><?=getenv("REMOTE_USER")?></b></font></td>
		<td width=100><font size=-1><a href="userindex.phtml">User Menu</a></font></td>
		<td width=100><font size=-1><a href="manageusers.phtml?stage=0">List Users</a></font></td>
		<td width=100><font size=-1><a href="managesec.phtml?stage=0">Security</a></font></td>
		<td width=100><font size=-1><a href="managegroups.phtml?stage=0">Groups</a></font></td>
		<td width=100><font size=-1><a href="manageusers.phtml?stage=0&lastsearch=1">LAST SEARCH</a></font></td>
	</tr>
</table>
</div>
<br>
<?
    function userlog( $msg ) {
    	global $REMOTE_USER;
    	$now = str_pad( date( "d/m/y H:i" ), 15 );
    	$msg = "$now - " . str_pad( $REMOTE_USER, 15 ) . " - $msg\n";
    	error_log( $msg, 3, "/usr/local/www/pages/booking/usermanage/userlog.log");
    }
?>
<?  if( $REMOTE_USER != "Travis" ) { /* exit; */ }; ?>

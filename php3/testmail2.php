<?

$user_id="elizabethl@cavmail.co.za";
$password="ICpass2023@@Unknown@321#";

$mbox = @imap_open ("{web.cavmail.co.za:993/imap/ssl/novalidate-cert}", "$user_id", "$password");

if ($mbox === false ) {
	echo imap_last_error();
}
else {

	$check = imap_mailboxmsginfo($mbox);

	echo  $check->Unread;

	imap_close($mbox);
}

?>

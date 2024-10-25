<?


$user_id="elizabethl@cavmail.co.za";
$password="ICpass2023@@Unknown@321";



$mbox = imap_open ("{web.cavmail.co.za:995/pop3/ssl/novalidate-cert}", "$user_id", "$password");

if ($mbox === false ) {
	echo "Not authenticated<bR>\n";	
	exit;
}

$check = imap_mailboxmsginfo($mbox);

if ($check) {
    echo "Date: "     . $check->Date    . "<br />\n" ;
    echo "Driver: "   . $check->Driver  . "<br />\n" ;
    echo "Mailbox: "  . $check->Mailbox . "<br />\n" ;
    echo "Messages: " . $check->Nmsgs   . "<br />\n" ;
    echo "Recent: "   . $check->Recent  . "<br />\n" ;
    echo "Unread: "   . $check->Unread  . "<br />\n" ;
    echo "Deleted: "  . $check->Deleted . "<br />\n" ;
    echo "Size: "     . $check->Size    . "<br />\n" ;
} else {
    echo "imap_mailboxmsginfo() failed: " . imap_last_error() . "<br />\n";
}

imap_close($mbox);

?>

<?
/* ****************************************************************** */
/* **************** Computicket Log on function ********************* */
/* ****************************************************************** */
 
function open_oracle()
{
global $conn,$cursor, $REMOTE_USER;

$pass="icapeoracle";
$conn = Ora_Logon("icape@192.168.10.8",$pass);

//        echo "The Booking System has been shutdown until 09:00.<br>";
//        echo "Please try again later.<br>";
//        exit;

if (!$conn)
{
	echo "Could not connect to Oracle.";
	exit;
}

$cursor = Ora_Open($conn);

if ($cursor < 0) {
    echo("Could not open a cursor.\n");
    Ora_Logoff($conn);
    return 0;
}

Ora_CommitOff($conn);

return 1;
}

function close_oracle()
{
        global $cursor, $conn;

        if ($cursor):
                @ora_close($cursor);
        endif;

        if ($conn):
                ora_logoff($conn);
        endif;
}

register_shutdown_function("close_oracle");

<?php


echo "<form method=post>";

echo "FCM token: <font size=1>$token</font><br>";
echo "<input type=hidden name=token value='$token'>";

echo "Message type: <select name=type><option value=0>Promotional<option value=1>Trip Info<option value=2>Transactional</select><br>";
echo "Message<br><textarea name=msg rows=5 cols=80></textarea><br>";

echo "<input type=submit value=Go></form>";

if (isset($type) && $msg!="") {


	require("/usr/local/www/pages/booking/push/Pushnoti.php");

	$push = new Pushnoti;

	$push->test();

	echo "Sending<bR>";

	$result = $push->send($token, $msg);

	echo "<br>Result:<br>";

	var_dump($result);

}



// Notes regarding type
// Use 0, 1 or 2 for the 3 types
// Current assignment is: 0 = promotional, 1 = trip information, 2 = transactional information




?>


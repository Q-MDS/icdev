<?php

require("Pushnoti.php");

$push = new Pushnoti;

$push->test();

// Notes regarding type
// Use 0, 1 or 2 for the 3 types
// Current assignment is: 0 = promotional, 1 = trip information, 2 = transactional information

// Quintin: Android
$result = $push->send("f9zBkAk9QtySR2pH-EodLJ:APA91bFic5DOHTWfsY_0f_GUkFKpOBah2c9a5os7nZFlCnS1iglIDeUxZtFRE32Bu2vX7t6yKmHDp90_1GSCjY1VvYtAuQIGUN3fxz4e0h1u1-JIhb4Jd4iQ5WBPSoUdfOHMfrRxjOAF", "This is a test from Keith\n\rThis is line 2");
// Quintin: iphone
// $result = $push->send("fPMuJaSDL05joYyQ0YNxEM:APA91bGuYwIL8EOUOtxQdhxuO0nIaLcT8hkSOCnv2LS7HuUYTi8qZ8rM1mllOTYUQ8dSY9x9yvdxeEVjZzuF_CoxgiRiMcw999fP1ZJC20OGZGPtXjfGcQ2mMK7Idm30ImQ8nQI75mm6", "This is a test from Keith\n\rThis is line 2");



//var_dump($result);


// Erin:

//$result = $push->send("cwXUdTZLTECBoOq7QZ9LtM:APA91bFrgQuB_SNFzxNwGns7LI4tHV_ZeiaR72waEJK6LvzZQ4vuMx7y4Y4iK8P0Q4lQOyQ6MVZMnKVz9zMtLQTSZDfS3QODf59PnkG9DWGnhJM-An2l07pKWlKSbTcR9fVxw3YCCvsJ", "This is a test from Keith\n\rThis is line 2");

var_dump($result);

?>

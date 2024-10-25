<?php
require('PushNoti.php');

function send_ios($token, $title, $body, $messagetype, $expires)
{
	$token = 'fentQvA4R0iZgY0GaUWZE5:APA91bHxerfPh6pReQmFvEVazbA1UmD7-kMI5lxly09siu8Klmh0hOmZ9e5rDbpdpXd5umc__JdOv9DifcmNnKugsUrbSVTFqP9FzBrAj-Y5rFSj9cFEiTrrPSPvJVtqvxYlUAJ-Tb1D
';
	$title = 'IOS Title';
	$body = 'IOS Body';
	$messagetype = '1';
	$expires = strtotime('+1 day');

	echo "XXX: $token, $title, $body, $messagetype, $expires\n";
	// $pushnoti = new Pushnoti();
	// $pushnoti->send($token, $title, $body, $messagetype, $expires);
}
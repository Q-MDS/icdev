<?php

require_once("vendor/autoload.php");
use Google\Client;

class Pushnoti
{

	public function test()
	{
		echo "Test";
	}

	public function send($token, $body)
	{
		// apiKey: "AIzaSyDI7_EurKIVVcl4rQK7_ef84AbNKj6-rZ8",
		// authDomain: "intercape-6c314.firebaseapp.com",
		// projectId: "intercape-6c314",
		// storageBucket: "intercape-6c314.appspot.com",
		// appId: "1:762612208172:android:86fad30774c87644c23789",
		// ddAqfH-7G09AiMFPH7TFWl:APA91bEufnVzQ0z7nRq1LTWNOROoOIdSB9siOHeNau8ESsahuvQUX3RRBOUdPu3611OvngL1y6EDvI93XAhqH7b378pbo5njuSkzm3R001SAhPrwDG_3PdxxBus7mS3iwuEiUJg8oG5j
		//'token' => 'f9zBkAk9QtySR2pH-EodLJ:APA91bEyl7PntCkL8YkqwufZmbsOlIbAP4AHR6xwXFAnR5zWpUKD2qbjWweRkgVjKL64xBn-Z5mWIB5AWmcKI1J1binZqAoyMm93PftlrJtzqZLHZb0L90FeUSw78OAjaPOxKT5umAv4',
		// iphone:'token' => 'czECTTVrVUHklkKgFaW33_:APA91bHFhZUTVFM63B3Oqilp9ebOXxGLatrRRHtmTCNSToON4tunUJ87HQm5ojrDxdvE0Wm9ZbANiYBlUwgj1nGqZj7P5qpDD_vaR_yLuLqqIeDjQmZs2xCFccex5kWajmpVXRrVM2nr',
		//'token' => 'f9zBkAk9QtySR2pH-EodLJ:APA91bEyl7PntCkL8YkqwufZmbsOlIbAP4AHR6xwXFAnR5zWpUKD2qbjWweRkgVjKL64xBn-Z5mWIB5AWmcKI1J1binZqAoyMm93PftlrJtzqZLHZb0L90FeUSw78OAjaPOxKT5umAv4',
		$accessToken = $this->getAccessToken();
		$message = [
			'token' => $token,
			'apns' => [
		        'payload' => [
		            'aps' => [
		                'alert' => [
		                    'title' => 'Intercape Notification',
		                    'body' => $body,
		                ],
		                'sound' => 'default',
		                'badge' => 1,
		                'content-available' => 1,
		            ],
		        ],
		    ],
			'notification' => [
			'title' => 'Intercape Notification',
			'body' => $body,
			], 
			'data' => ['type' => '2'],
		];
		$projectId = "intercape-6c314";
		return $this->sendMessage($accessToken, $projectId, $message);
	}

	function getAccessToken() 
	{
		$client = new Client();
		$client->setAuthConfig('res/files/intercape-6c314-82b5c56fc4f8.json');
		$client->addScope('https://www.googleapis.com/auth/firebase.messaging');
		$client->useApplicationDefaultCredentials();
		$token = $client->fetchAccessTokenWithAssertion();

		// echo $token['access_token'];
		return $token['access_token'];
	 }

	 function sendMessage($accessToken, $projectId, $message) {
		$url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';
		$headers = [
		 'Authorization: Bearer ' . $accessToken,
		 'Content-Type: application/json',
		 ];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));
		$response = curl_exec($ch);
		 if ($response === false) {
		 throw new Exception('Curl error: ' . curl_error($ch));
		 }
		curl_close($ch);
		return json_decode($response, true);
	  }

}

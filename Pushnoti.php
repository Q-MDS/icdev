<?php
require_once("vendor/autoload.php");
use Google\Client;

/** TEST EXAMPLES
 * 
 * Firebase (Android and IOS)
 * send('Token', 'Title', 'Body', 'Token', 'MessageType', 'Expires');
 * 'Title' - string : Note - the title is not always visible
 * 'Body' - string
 * 'Token' - string : fcm_token of the user
 * 'MessageType' - string : 0 = trip, 1 = transactional, 2 = promotion
 * 'Expires' - timestamp : Conditional depending on type of notification
 * 
 * Huawei (Android - EMUI)
 * huawei_send('Tokan', 'Title', 'Body', 'MessageType', 'Expires');
 * 'Title' - string : Note - the title is not always visible
 * 'Body' - string
 * 'Token' - string : token of the user
 * 'MessageType' - string : 0 = trip, 1 = transactional, 2 = promotion
 * 'Expires' - timestamp : Conditional depending on type of notification
 */
class Pushnoti
{
	/**
	 * Huawei Config
	 */
	private $appId = '111479601';
    private $appSecret = 'd32755dff09cbb3fd0d4dd8c535a6427053c651a84a742e42aa50217955fc210';
    private $tokenUrl = 'https://oauth-login.cloud.huawei.com/oauth2/v3/token';
    private $pushUrl = 'https://push-api.cloud.huawei.com/v1/{appid}/messages:send';

	public function test()
	{
		echo "Test";
	}

	/** 
	 * Firebase
	 */
	public function send($token, $title = "Intercape Notification", $body, $messagetype = '2', $expires)
	{
		$accessToken = $this->getAccessToken();
		$message = [
			'token' => $token,
			'apns' => [
		        'payload' => [
		            'aps' => [
		                'alert' => [
		                    'title' => $title,
		                    'body' => $body,
		                ],
		                'sound' => 'default',     // Leave this, play with it later
		                'badge' => 1,             // Always set to 1
		                'content-available' => 1, // Always set to 1
		            ],
		        ],
		    ],
			'notification' => [
			'title' => $title,
			'body' => $body,
			], 
			'data' => ['type' => "$messagetype", 'expires' => $expires], 
		];
		$projectId = "intercape-6c314";
		
		$result = $this->sendMessage($accessToken, $projectId, $message);
		
		var_dump($result);

		if (isset($result['error'])) 
		{
			return false;
		} 
		else 
		{
			return true;
		}
	}

	function getAccessToken() 
	{
		$client = new Client();
		$client->setAuthConfig('/usr/local/www/pages/booking/push/res/files/intercape-6c314-82b5c56fc4f8.json');
		$client->addScope('https://www.googleapis.com/auth/firebase.messaging');
		$client->useApplicationDefaultCredentials();
		$token = $client->fetchAccessTokenWithAssertion();

		// echo $token['access_token'];
		return $token['access_token'];
	}

	function sendMessage($accessToken, $projectId, $message) 
	{
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
		
		if ($response === false) 
		{
		throw new Exception('Curl error: ' . curl_error($ch));
		}
		
		curl_close($ch);
		return json_decode($response, true);
	}


	/** 
	 * Huawei
	*/
	public function huawei_send($token, $title = "Intercape Notification",  $body, $messagetype = '2', $expires) 
	{
		// Test token: IQAAAACy08poAAB7FnS7-7NEIzKw9lzEcJFWsy7YnGVZANX4ImxBq115_-8IUiTLsu57R-LwK_0TTaZdL83eDtXgYJk269E2yTyrC3IIn_TPpfAahA
		// Test URL: http://localhost/ic/pushkit/send
        $accessToken = $this->getHuaweiAccessToken();

        if ($accessToken) 
		{
            $result = $this->sendPushNotification($accessToken);
        } 
		else 
		{
            echo 'Failed to get access token';
        }
    }

	private function getHuaweiAccessToken() 
	{
        $postData = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret
        ];
		
        // $response = $this->curl->simple_post($this->tokenUrl, $postData);
        // $response = json_decode($response, true);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->tokenUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
		$response = curl_exec($ch);

		if (curl_errno($ch)) 
		{
			echo 'Curl error: ' . curl_error($ch);
		}
		curl_close($ch);

		$response = json_decode($response, true);

        return isset($response['access_token']) ? $response['access_token'] : null;
    }

	private function sendPushNotification($accessToken, $title, $body, $expirs, $type, $token) 
	{
		// Test data: START
		// $title = 'Title goes here';
		// $body = 'Body goes here';
		// $now = strtotime(date("Y-m-d H:i:s"));
		// $expires = $now + 86400;
		// $type = '0';
		// $token = 'IQAAAACy08poAAAbpru_5ybN0RUjOmz91wc1JBWUJn9qzgJ5vmUNjhCs-VhPtbHHT4N7keS7bbAdtdNkYxE372gIELhb9ZFH8eXqObxiMnLPDz-RHQ';
		// Test data: END
		
		$postData = [
			"validate_only" => false,
        	"message" => [
            "data" => json_encode([
                "title" => $title,
                "body" => $body,
            	"expires" => $expires,
            	"type" => $type
            ]),
            "token" => [$token]
        	]
		];

		$url = str_replace('{appid}', $this->appId, $this->pushUrl);
		$headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
		
		$response = curl_exec($ch);
		
		if ($response === false) 
		{
		 	throw new Exception('Curl error: ' . curl_error($ch));
		}
		
		curl_close($ch);
		
		return json_decode($response, true);
	  }

}

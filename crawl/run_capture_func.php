<?php
start();

function start()
{
	if ($_SERVER['REQUEST_METHOD'] === 'POST') 
	{
		$input = json_decode(file_get_contents('php://input'), true);
		$from = $input['from'];
		$to = $input['to'];
		$date = $input['date'];

		$data = json_encode(['from' => $from, 'to' => $to, 'date' => $date]);

		$ch = curl_init('http://localhost:3000/run-capture');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		$response = curl_exec($ch);
		curl_close($ch);

		// Parse response
		$parsed_data = parser($response);
		$data = json_encode($parsed_data);

		return array('run_date' => $date, 'from' => $from, 'to' => $to, 'output' => $data);
	} 
	else 
	{
		return array('error' => 'Invalid request method');
	}
}

function parser($json)
{
	$clean_json = str_replace('\"', '"', $json);

	$bits = explode('{"type":"avalibilityResponse"', $clean_json);

	$add_json = '{"type":"avalibilityResponse"' . $bits[3];

	$x = substr($add_json, 0, -4);

	$data = json_decode($x);

	return $data;
}
?>
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "arb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) 
{
    die("Connection failed: " . $conn->connect_error);
}
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

	// Save to database
	$parsed_data = parser($response);
	$data = json_encode($parsed_data);

    $stmt = $conn->prepare("INSERT INTO captures (from_location, to_location, date, output) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $from, $to, $date, $data);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    header('Content-Type: application/json');
    echo json_encode(['output' => $response]);
}

function parser($json)
{
	$clean_json = str_replace('\"', '"', $json);

	$bits = explode('WebSocket Frame Received: ', $clean_json);

	$add_json = '{"type":"avalibilityResponse"' . $bits[3];

	$x = substr($add_json, 0, -4);

	$data = json_decode($x);

	return $data;
}
?>
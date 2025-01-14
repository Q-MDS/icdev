<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "arb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// JSON string
$json_string = '{
    "matches": [
        {
            "text": "Zebediela",
            "id": "ZAZAZEBEDIELA",
            "type": "City",
            "displayMatchType": "City",
            "label": "City",
            "indexId": "a0",
            "innerObject": [],
            "score": 2
        },
        {
            "text": "Zakkariyya Park",
            "id": "ZAZAZAKKARIYYAPARK",
            "type": "City",
            "displayMatchType": "City",
            "label": "City",
            "indexId": "a1",
            "innerObject": [],
            "score": 2
        },
        {
            "text": "Zvishavane",
            "id": "ZAZAZVISHAVANE",
            "type": "City",
            "displayMatchType": "City",
            "label": "City",
            "indexId": "a2",
            "innerObject": [],
            "score": 2
        },
        {
            "text": "Zeerust",
            "id": "ZAZAZEERUST",
            "type": "City",
            "displayMatchType": "City",
            "label": "City",
            "indexId": "a3",
            "innerObject": [],
            "score": 2
        },
        {
            "text": "Zastron",
            "id": "ZAZAZASTRON",
            "type": "City",
            "displayMatchType": "City",
            "label": "City",
            "indexId": "a4",
            "innerObject": [],
            "score": 2
        },
        {
            "text": "Zimba",
            "id": "ZAZAZIMBA",
            "type": "City",
            "displayMatchType": "City",
            "label": "City",
            "indexId": "a5",
            "innerObject": [],
            "score": 2
        },
        {
            "text": "Zimbabwe",
            "id": "ZAZAZIMBABWE",
            "type": "City",
            "displayMatchType": "City",
            "label": "City",
            "indexId": "a6",
            "innerObject": [],
            "score": 2
        },
        {
            "text": "Zinniville",
            "id": "ZAZAZINNIVILLE",
            "type": "City",
            "displayMatchType": "City",
            "label": "City",
            "indexId": "a7",
            "innerObject": [],
            "score": 2
        }
    ],
    "alternatives": [],
    "nearby": []
}';

// Decode JSON data
$data = json_decode($json_string, true);

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO stops (stop_text,stop_id, stop_type, stop_display_match_type, stop_label, stop_index_id, stop_inner_object, stop_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssi", $text, $city_id, $type, $displayMatchType, $label, $indexId, $innerObject, $score);

// Loop through the matches and insert into the database
foreach ($data['matches'] as $match) {
    $text = $match['text'];
    $city_id = $match['id'];
    $type = $match['type'];
    $displayMatchType = $match['displayMatchType'];
    $label = $match['label'];
    $indexId = $match['indexId'];
    $innerObject = json_encode($match['innerObject']); // Convert innerObject array to JSON string
    $score = $match['score'];
    $stmt->execute();
}

echo "New records created successfully";

$stmt->close();
$conn->close();
?>
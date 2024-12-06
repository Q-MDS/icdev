<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Depot Totals Email URL Builder</title>
</head>
<body>
	<h1>Depot Totals Email URL Builder</h1>
	<form action="dp_url.php" method="post">
		<label for="depot">URL:</label>
		<input type="text" name="the_url" id="the_url" value="http://192.168.10.239/move/drift/depot_totals/index.php" style="width: 500px" required>
		<br>
		<label for="depot">Depot:</label>
		<!-- <input type="text" name="depot" id="depot" required> -->
		<select name="depot" id="depot">
			<option value="">Select...</option>
			<option value="BLM">BLM</option>
			<option value="CA">CA</option>
			<option value="CBS">CBS</option>
			<option value="DBN">DBN</option>
			<option value="GAB">GAB</option>
			<option value="MAP">MAP</option>
			<option value="MTH">MTH</option>
			<option value="PE">PE</option>
			<option value="PTA">PTA</option>
			<option value="UPT">UPT</option>
			<option value="WHK">WHK</option>

		</select>
		<br>
		<label for="date">Date:</label>
		<input type="date" name="date" id="date" required>
		<br>
		<label for="date">Expires:</label>
		<input type="date" name="date_expires" id="date_expires" required>
		<br>
		<button type="submit">Generate URL</button>
	</form>
	<?php
		if (isset($_POST['depot']) && isset($_POST['date']) && isset($_POST['date_expires'])) {
			$the_url = $_POST['the_url'];
			$depot = $_POST['depot'];
			$date = $_POST['date'];
			$expires = $_POST['date_expires'];
			$ts_start = strtotime($date);
			$ts_end = strtotime($expires);
			$params = base64_encode($depot . '##' . $ts_start . '##' . $ts_end);
			// $url = "http://192.168.10.239/move/drift/depot_totals/index.php?d=$params";
			$url = $the_url ."?d=" . $params;
			echo "<p><a href='$url'>$url</a></p>";

			echo "<p>" . base64_decode($params) . "</p>";
		}
	?>
</body>
</html>
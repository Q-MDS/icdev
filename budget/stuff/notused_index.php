<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
</head>
<body>
	<div style="display: flex; flex-direction: column; row-gap: 20px;">
		<div>Please select a month</div>
		
		<div>
			<form action="budget_update.php" method="post">
		<label for="january">
                <input type="radio" name="month" value="1" id="january"> January
            </label>
            <label for="february">
                <input type="radio" name="month" value="2" id="february"> February
            </label>
            <label for="march">
                <input type="radio" name="month" value="3" id="march"> March
            </label>
            <label for="april">
                <input type="radio" name="month" value="4" id="april"> April
            </label>
            <label for="may">
                <input type="radio" name="month" value="5" id="may"> May
            </label>
            <label for="july">
                <input type="radio" name="month" value="7" id="july"> July
            </label>
            <label for="august">
                <input type="radio" name="month" value="8" id="august"> August
            </label>
            <label for="september">
                <input type="radio" name="month" value="9" id="september"> September
            </label>
            <label for="october">
                <input type="radio" name="month" value="10" id="october"> October
            </label>
            <label for="november">
                <input type="radio" name="month" value="11" id="november"> November
            </label>
            <label for="december">
                <input type="radio" name="month" value="12" id="december"> December
            </label>
		</div>
		<div>
			<label for="backup">
				<input type="checkbox" name="backup" id="backup" checked><span style="padding-left: 5px">Backup tables</span>
			</label>
		</div>
		<div>
			<input type="submit" value="Submit">
		</div>
	</form>
	</div>
</body>
</html>
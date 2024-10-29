<!-- 1. do the includes which presents the login
2. $user = getenv(“REMOTE_USER”); 
3. select branch from user_Details where is_current=’Y’ and username=’$user’ 
4. Get list of tvs from DEPARTURE_TVS where branch=’$branch’
5. Draw layout
   - Heasding: Departure TVs
   - grid - 2 columns
	 - 1st column: rectangle with tv code in center
	 - 2nd column: Select route dropdown - Row 1
	 - 2nd column: Add/remove button - Row 2 (if empty button is disabled, if has a route the active and ADD, if has route the active and REMOVE )
	   - read DEPARTURE_TV_SETTINGS for the tv code and route - if no record found button is disabled, user selects route from dropdown, button becomes enabled and ADD
	   - read DEPARTURE_TV_SETTINGS for the tv code and route - if record IS found, set route from data and button is enabled and called REMOVE -->

	   JNB Station
	   Cape Town Depot
<?php
$user_id = 123;
$branch = '1';

// Step 1: get branch from USER_DETAILS

// Step 2: get list of TVs from DEPARTURE TVS where branch = branch

// Step 3: look for record in DEPARTURE_TV_SETTINGS. If found use that record else use loop record with no settings

// Step 4: Present the TVs in the layout

// Step 5: Do button logic

// Step 6: Update DEPARTURE_TV_SETTINGS depending on button action



?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Document</title>
<style>
	body {
		padding: 0px;
		margin: 10px;
		box-sizing: border-box;
	}
</style>
</head>
<body>

<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; max-width: 400px; border: 1px solid #ff0000; ">
	<div style="width: 100%">
		Departure TVs
	</div>

	<div style="display: grid; grid-template-columns: 100px 1fr; width: 100%; border: 1px solid #ff0000;">
		<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; border: 1px solid #000;">
			<div style="border: 1px solid #000; padding: 17px 20px; ">
				JHB1
			</div>
		</div>
		<div style="border: 1px solid #000; padding: 10px;">
			<div style="border: 1px solid #000; padding: 10px;">
				<select name="route" id="route">
					<option value="1">Route 1</option>
					<option value="2">Route 2</option>
					<option value="3">Route 3</option>
					<option value="4">Route 4</option>
				</select>
			</div>
			<div style="border: 1px solid #000; padding: 10px;">
				<button id="add">Add</button>
				<button id="remove">Remove</button>
			</div>
		</div>
	</div>

</div>

</body>
</html>

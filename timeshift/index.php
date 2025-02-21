<?php
function oci_conn()
{
	$host = 'localhost';
	$port = '1521';
	$sid = 'XE';
	$username = 'SYSTEM';
	$password = 'dontletmedown3';

	$conn = oci_connect($username, $password, "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port)))(CONNECT_DATA=(SID=$sid)))");

	if (!$conn) 
	{
		$e = oci_error();
		// echo "Connection failed: " . $e['message'];
		exit;
	} 
	else 
	{
		// echo "Connection succeeded";
	}

	return $conn;
}

function getdata($cursor, $column_index) {
    $data = oci_result($cursor, $column_index);
    return $data !== false ? $data : '';
}

function get_notes()
{
	$data = array();

	$conn = oci_conn();

	$sql = "SELECT * FROM ROUTE_STOPS_NOTES_DROPDOWN WHERE active = 'Y' ORDER BY DROPDOWN ASC";
	$cursor = oci_parse($conn, $sql);
	
	oci_execute($cursor);


	while (ocifetch($cursor))
	{
		$data[] = array('dropdown_serial' => getdata($cursor, 2), 'dropdown' => getdata($cursor, 1));
	}

	return $data;
}

$notes = get_notes();

$data = array();
$conn = oci_conn();
$sql = "select * from route_stops where route_serial='1868577346' order by stop_order asc";
$cursor = oci_parse($conn, $sql);

oci_execute($cursor);
?>
<style>
.notes_btn {
	display: flex; 
	align-items: center; 
	justify-content: center; 
	width: 24px; 
	height: 24px; 
	background-color: #efefef; 
	color: #000; 
	border-radius: 3px; 
	cursor: pointer; 
	font-size: 14px;
	border: 1px solid #000; 
	
} 

</style>
<div style="display: flex; align-items: 'center'; column-gap: 5px">
	<div>Enter minutes:</div>
	<div><input type="text" id="mins" value="10"></div>
	<div style="padding: 2px 10px; border: 1px solid #000; background-color: #2b2b2b; color: #fff" onclick="add();">+</div>
	<div style="padding: 2px 10px; border: 1px solid #000; background-color: #2b2b2b; color: #fff" onclick="minus();">-</div>
</div>
<TABLE border=0 bgcolor='#fff'>
<tr bgcolor='#d9d9d9'>
<td>No</td>
<td>Stop<br>Name</td>
<td>Days<br>After Start</td>
<td>Arrive<br>Time</td>
<td>Depart<br>Time</td>
<td>Next<br>Day</td>
<td>Major<br>Stop</td>
<td>Cross<br>Over</td>
<td>Manifest<br>Prt</td>
<td>Shuttle</td>
<td>Change <font size=2>to shuttle</font> @</td>
<td>Country<br>Time</td>
<td>Passport</td>
<td>Notes for driver</td>
<td>Incl<br>Start</td>
<td>Incl<br>End</td>
<td>New<br>Partn</td>
</tr>

<?php
$reccnt=0;
$i=0;
$stops_so_far=array();
$lastnumber="";
$table_alt = "d9d9d9";
$table_cell = "ffffff";
$printable = 'N';

	while (ocifetch($cursor))
	{
		if (getdata($cursor,9)>99)
			echo "<tr bgcolor='#$table_alt'>";
		else
			echo "<tr bgcolor='#$table_cell'>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,9);
			else {
			echo "<input size=3 type=text name=stp_$i value='";
			echo chop(oci_result($cursor,9));
			echo "'>";
			}
		if (getdata($cursor,9)==$lastnumber) {
			echo "<font color=red><b>DUPE!!</b></font>";
		}
		$lastnumber=getdata($cursor,9);
		echo "</td>";
		echo "<td>";
			// if (isset($delstops[chop(oci_result($cursor,4))]))
			// 	echo "<font color=red>* ";
			echo chop(oci_result($cursor,5));
			// if ($printable!="Y") {
			// echo "<input type=hidden name='short_$i' value='";
			// echo chop(oci_result($cursor,4));
			// echo "'>";
			// }
		echo "</td>";
		echo "<td>";
			echo chop(oci_result($cursor,12));
		echo "</td>";

		// =================================== ARRIVAL TIME ===================================
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,5);
			else {
			echo "<input class='arr' size=5 type=text name=arr_$i value='";
			echo chop(oci_result($cursor,6));
			echo "'>";
			}
		echo "</td>";

		// =================================== DEPARTURE TIME ===================================
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,6);
			else {
			echo "<input class='dep' size=5 type=text name=dep_$i value='";
			echo chop(oci_result($cursor,7));
			echo "'>";
			}
		echo "</td>";



		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,7);
			else {
			echo "<input class='next_day' type=checkbox name=nextday_$i value='Y'";
			if (chop(oci_result($cursor,7))=="Y"):
				echo " checked ";
			endif;
			echo ">";
			}
		echo "</td>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,8);
			else {
			echo "<input type=checkbox name=major_$i value='Y'";
			if (chop(oci_result($cursor,8))=="Y"):
				echo " checked ";
			endif;
			echo ">";
			}
		echo "</td>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,13);
			else {
			echo "<input type=checkbox name=crossover_$i value='Y'";
			if (chop(oci_result($cursor,13))=="Y"):
				echo " checked ";
			endif;
			echo ">";
			}
		echo "</td>";
			echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,15);
			else {
					echo "<input type=checkbox name=print_m_$i value='Y'";
					if (chop(oci_result($cursor,15))=="Y"):
							echo " checked ";
					endif;
					echo ">";
			}
			echo "</td>";
			echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,16);
			else {
					echo "<input type=checkbox name=shut_$i value='Y'";
					if (chop(oci_result($cursor,16))=="Y"):
							echo " checked ";
					endif;
					echo ">";
			}
			echo "</td>";
		echo "<td>";
			if ($printable=="Y")
				echo getdata($cursor,22);
			else {
				if (chop(oci_result($cursor,16))=="Y") {
				echo "X";
				} else echo "n/a";
			}
		echo "</td>";

		$stops_so_far[getdata($cursor,4)]=getdata($cursor,4);
		if (chop(oci_result($cursor,16))!="Y")
			$previous_stop=getdata($cursor,4);


		echo "<td>X</td>";

		echo "<td>";
			if ($printable=="Y")
					echo getdata($cursor,10);
			else {
			echo "<input type=checkbox name=passport_$i value='Y'";
			if (chop(oci_result($cursor,10))=="Y"):
				echo " checked ";
			endif;
			echo " onclick='javscript:clearbox(pfee_$i,passport_$i)'>";
			}
		echo "</td>";
		echo "<td>";
		echo "<input type=hidden name=pfee_$i value=0>";
			if ($printable=="Y")
				echo getdata($cursor,18);
			else {
			echo "<input size=30 type=text name=snotes_$i maxlength=80 value='";
			echo chop(oci_result($cursor,18));
			echo "'>";
			}
		echo "</td>";

		// Q: New notes column
		echo "<td>";
			// Dropdown, a,e,d
			echo "<div style='display: flex; flex-direction: row; align-items: center; column-gap: 5px'>";
				echo "<select id='notes_$i' name='notes_$i'>";
				echo "<option value='NONE'>NONE</option>";
				foreach ($notes as $note)
				{
					$value = $note['dropdown_serial'];
					$label = $note['dropdown'];
					
					echo "<option value='$value'>$label</option>";
				}
				echo "</select>";
				echo "<input type='text' id='note_text_$i' name='note_text_$i' placeholder='Enter note' style='display: none; width: 100%'>";
				echo "<div id='add_$i' class='notes_btn' title='Add a note'onclick='addNote($i)'><pre>A</pre></div>";
				echo "<div id='add_save_$i' class='notes_btn' style='display: none; width: 80px' title='Save' onclick='addSave($i)'><pre>Save</pre></div>";
				echo "<div id='edit_$i' class='notes_btn' title='Edit note' onclick='editNote($i)'><pre>E</pre></div>";
				echo "<div id='edit_save_$i' class='notes_btn' style='display: none; width: 80px' title='Save' onclick='editSave($i)'><pre>Update</pre></div>";
				echo "<div id='cancel_$i' class='notes_btn' style='display: none; width: 80px;' title='Cancel' onclick='cancel($i)'><pre>Cancel</pre></div>";
				echo "<div id='delete_$i' class='notes_btn' title='Delete note' style='margin-right: 5px;' onclick='remove($i)'><pre>D</pre></div>";


			echo "</div>";
		echo "</td>";

		if (!1 == 1) {
			echo "<td>";
					if ($printable=="Y")
							echo getdata($cursor,19);
					else {
					echo "<input type=checkbox name=pstart_$i value='Y'";
					if (chop(oci_result($cursor,19))=="Y"):
							echo " checked ";
					endif;
					echo ">";
					}
			echo "</td>";
			echo "<td>";
					if ($printable=="Y")
							echo getdata($cursor,20);
					else {
					echo "<input type=checkbox name=pend_$i value='Y'";
					if (chop(oci_result($cursor,20))=="Y"):
							echo " checked ";
					endif;
					echo ">";
					}
			echo "</td>";

			echo "<td>";
					if ($printable=="Y")
							echo getdata($cursor,21);
					else {
					echo "<input type=checkbox name=partn_$i value='Y'";
					if (chop(oci_result($cursor,21))=="Y"):
							echo " checked ";
					endif;
					echo ">";
					}
			echo "</td>";
		}

	echo "</tr>";
	$i++;
	}
	$reccnt=$i;

?>

<br><input type=submit name=doupdate id="upd_button" value="Update"><br><br>
</table>
<script>
baseUrl = window.location.protocol + "//" + window.location.hostname + "/icdev/timeshift/";

function add()
{
	var updButton = document.getElementById('upd_button');
	updButton.disabled = true;
	updButton.style.backgroundColor = 'orange';
	var minsToAdd = parseInt(document.getElementById('mins').value);
	var arrElements = document.querySelectorAll('.arr');
	var depElements = document.querySelectorAll('.dep');

	arrElements.forEach(function(element) 
	{
		let timeStr = element.value;

		if (timeStr != 'NONE')
		{
			var hours = parseInt(timeStr.substring(0, 2));
			var minutes = parseInt(timeStr.substring(2, 4));

			// Create a Date object with the extracted time
			var date = new Date();
			date.setHours(hours);
			date.setMinutes(minutes);

			// Add the minutes
			date.setMinutes(date.getMinutes() + minsToAdd);

			var newHours = date.getHours().toString().padStart(2, '0');
			var newMinutes = date.getMinutes().toString().padStart(2, '0');
			var newTimeStr = newHours + newMinutes;

			// Display the new time
			// console.log('Old time: ', timeStr, 'New time: ', newTimeStr);
			element.value = newTimeStr;

			element.style.backgroundColor = 'pink';
		}
	});

	depElements.forEach(function(element) 
	{
		let timeStr = element.value;

		if (timeStr != 'NONE')
		{
			var hours = parseInt(timeStr.substring(0, 2));
			var minutes = parseInt(timeStr.substring(2, 4));

			// Create a Date object with the extracted time
			var date = new Date();
			date.setHours(hours);
			date.setMinutes(minutes);

			// Add the minutes
			date.setMinutes(date.getMinutes() + minsToAdd);

			var newHours = date.getHours().toString().padStart(2, '0');
			var newMinutes = date.getMinutes().toString().padStart(2, '0');
			var newTimeStr = newHours + newMinutes;

			// Display the new time
			// console.log('Old time: ', timeStr, 'New time: ', newTimeStr);
			element.value = newTimeStr;

			element.style.backgroundColor = 'pink';
		}
	});

	nextDay();
}

function minus()
{
	var minsToAdd = parseInt(document.getElementById('mins').value);
	var arrElements = document.querySelectorAll('.arr');
	var depElements = document.querySelectorAll('.dep');

	arrElements.forEach(function(element) 
	{
		let timeStr = element.value;

		if (timeStr != 'NONE')
		{
			var hours = parseInt(timeStr.substring(0, 2));
			var minutes = parseInt(timeStr.substring(2, 4));

			// Create a Date object with the extracted time
			var date = new Date();
			date.setHours(hours);
			date.setMinutes(minutes);

			// Add the minutes
			date.setMinutes(date.getMinutes() - minsToAdd);

			var newHours = date.getHours().toString().padStart(2, '0');
			var newMinutes = date.getMinutes().toString().padStart(2, '0');
			var newTimeStr = newHours + newMinutes;

			// Display the new time
			console.log('Old time: ', timeStr, 'New time: ', newTimeStr);
			element.value = newTimeStr;

			element.style.backgroundColor = 'lime';
		}
	});

	depElements.forEach(function(element) 
	{
		let timeStr = element.value;

		if (timeStr != 'NONE')
		{
			var hours = parseInt(timeStr.substring(0, 2));
			var minutes = parseInt(timeStr.substring(2, 4));

			// Create a Date object with the extracted time
			var date = new Date();
			date.setHours(hours);
			date.setMinutes(minutes);

			// Add the minutes
			date.setMinutes(date.getMinutes() - minsToAdd);

			var newHours = date.getHours().toString().padStart(2, '0');
			var newMinutes = date.getMinutes().toString().padStart(2, '0');
			var newTimeStr = newHours + newMinutes;

			// Display the new time
			console.log('Old time: ', timeStr, 'New time: ', newTimeStr);
			element.value = newTimeStr;

			element.style.backgroundColor = 'lime';
		}
	});

	nextDay();
}

function nextDay()
{
	var arrElements = document.querySelectorAll('.arr');
	var depElements = document.querySelectorAll('.dep');
	var nextElements = document.querySelectorAll('.next_day');

	// Clear all checkboxes
	nextElements.forEach(function(element) 
	{
		element.checked = false;
	});

	let lastDep = 0;

	for (let i = 0; i < arrElements.length; i++)
	{
		let arrTime = arrElements[i].value;
		let depTime = depElements[i].value;

		if (arrTime == 'NONE')
		{
			arrTime = depTime;
		}

		if (depTime == 'NONE')
		{
			depTime = arrTime;
		}

		// console.log('Is ', arrTime, ' greater than ', lastDep, '?', arrTime > lastDep);

		if (depTime < arrTime)
		{
			console.log('Arrive was befoew midnight and depart was after midnight: ', arrTime * 1, depTime * 1);
			console.log('NEXT DAY = CHECKED');
			arrElements[i].style.backgroundColor = 'red';
			depElements[i].style.backgroundColor = 'red';
			nextElements[i].checked = true;
		}
		
		if (!(arrTime > lastDep))
		{
			console.log('Cos arr time > last dep time both are after midnight: ', arrTime * 1, depTime * 1);
			console.log('NEXT DAY = CHECKED');
			nextElements[i].checked = true;
		}

		console.log('NEXT DAY = ZILCH');
		lastDep = depTime;
	}

}

function addNote(i)
{
	var addBtn = document.getElementById('add_' + i);
	var addSaveBtn = document.getElementById('add_save_' + i);
	var cancelBtn = document.getElementById('cancel_' + i);
	var editBtn = document.getElementById('edit_' + i);
	var editSaveBtn = document.getElementById('edit_save_' + i);
	var deleteBtn = document.getElementById('delete_' + i);
	var noteDropDown = document.getElementById('notes_' + i);
	var noteInput = document.getElementById('note_text_' + i);

	addBtn.style.display = 'none';
	addSaveBtn.style.display = 'flex';
	cancelBtn.style.display = 'flex';
	cancelBtn.style.marginRight = '5px';
	editBtn.style.display = 'none';
	editSaveBtn.style.display = 'none';
	deleteBtn.style.display = 'none';
	noteDropDown.style.display = 'none';
	noteInput.style.display = 'flex';
}

function addSave(i)
{
	console.log('Save dat ting');

	addCancel(i);
}

function editNote(i)
{
	var theNote = document.getElementById('notes_' + i);
	var noteText = document.getElementById('note_text_' + i);
	var addBtn = document.getElementById('add_' + i);
	var addSaveBtn = document.getElementById('add_save_' + i);
	var cancelBtn = document.getElementById('cancel_' + i);
	var editBtn = document.getElementById('edit_' + i);
	var editSaveBtn = document.getElementById('edit_save_' + i);
	var deleteBtn = document.getElementById('delete_' + i);
	var noteDropDown = document.getElementById('notes_' + i);
	var noteInput = document.getElementById('note_text_' + i);

	addBtn.style.display = 'none';
	addSaveBtn.style.display = 'none';
	cancelBtn.style.display = 'flex';
	cancelBtn.style.marginRight = '5px';
	editBtn.style.display = 'none';
	editSaveBtn.style.display = 'flex';
	deleteBtn.style.display = 'none';
	noteDropDown.style.display = 'none';
	noteInput.style.display = 'flex';

	theNoteLabel = noteDropDown.options[theNote.selectedIndex].text;
	theNoteValue = noteDropDown.options[theNote.selectedIndex].value;

	console.log('The note: ', theNoteValue, theNoteLabel);

	noteText.value = theNoteLabel;
}

function editSave(i)
{
	console.log('Update dat ting');
	var dropdownSerial = document.getElementById('notes_' + i).value;
	var dropDown = document.getElementById('note_text_' + i).value;

	console.log('The note: ', dropdownSerial, dropDown);

	const formData = { "action" : 2, "dropdown_serial": dropdownSerial, "dropdown": dropDown };

	const result = sendData(formData)
	.then(result => 
	{
		// console.log('Result: ', result);
		const updatedNotes = fetchUpdatedNotes();
    	updateDropdown(i, updatedNotes);
		// cancel(i);
	});
}

async function fetchUpdatedNotes() 
{
	const formData = { "action" : 0 };

	const result = sendData(formData)
	.then(result => 
	{
		// console.log('Result: ', result);
		
		return result;
	});
}

function updateDropdown(i, notes)
{
	var noteDropdown = document.getElementById('notes_' + i);
    noteDropdown.innerHTML = ''; // Clear existing options

    // Add new options
    notes.forEach(note => 
	{
        var option = document.createElement('option');
        option.value = note.dropdown_serial;
        option.text = note.dropdown;
        noteDropdown.add(option);
    });

    // Optionally, set the selected value to the updated note
    noteDropdown.value = document.getElementById('note_text_' + i).value;
}

function cancel(i)
{
	var addBtn = document.getElementById('add_' + i);
	var addSaveBtn = document.getElementById('add_save_' + i);
	var cancelBtn = document.getElementById('cancel_' + i);
	var editBtn = document.getElementById('edit_' + i);
	var editSaveBtn = document.getElementById('edit_save_' + i);
	var deleteBtn = document.getElementById('delete_' + i);
	var noteDropDown = document.getElementById('notes_' + i);
	var noteInput = document.getElementById('note_text_' + i);

	addBtn.style.display = 'flex';
	addSaveBtn.style.display = 'none';
	cancelBtn.style.display = 'none';
	editBtn.style.display = 'flex';
	editSaveBtn.style.display = 'none';
	deleteBtn.style.display = 'flex';
	noteDropDown.style.display = 'flex';
	noteInput.style.display = 'none';

	noteInput.value = '';
}

async function sendData(formData) 
{
	const phpUrl = baseUrl + 'modify_route_stops_model.php';
	
	const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
	const result = await response.text();
	
	return result;
}

</script>
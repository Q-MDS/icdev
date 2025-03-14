<?php
function oci_conn()
{
	$host = 'localhost';
	$port = '1521';
	$sid = 'XE';
	$username = 'SYSTEM';
	$password = 'dontletmedown4';

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

function get_phrases()
{
	$data = array();

	$conn = oci_conn();

	$sql = "SELECT PHRASE_DESC FROM ROUTE_STOPS_NOTES_PHRASES ORDER BY PHRASE_DESC ASC";
	$cursor = oci_parse($conn, $sql);
	
	oci_execute($cursor);

	while ($row = oci_fetch_assoc($cursor)) 
	{
		$data[] = $row['PHRASE_DESC'];
	}

	return $data;
}

$phrases = get_phrases();

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
	width: 100%; 
	height: 24px; 
	background-color: #efefef; 
	color: #000; 
	border-radius: 3px; 
	cursor: pointer; 
	font-size: 14px;
	border: 1px solid #000; 
} 
</style>
<?php
// Get stop list
$stop_list = array();
$sql = "SELECT SHORT_NAME FROM ROUTE_STOPS WHERE ROUTE_SERIAL = '1868577346' ORDER BY STOP_ORDER ASC";
$stops_cursor = oci_parse($conn, $sql);

oci_execute($stops_cursor);

while ($row = oci_fetch_assoc($stops_cursor)) 
{
	$stop_list[] = $row['SHORT_NAME'];
}
oci_free_statement($stops_cursor);
?>
<div style="display: flex; align-items: 'center'; column-gap: 5px; margin-top: 10px; margin-bottom: 10px;">
	<div style="display: flex; align-items: center;">Enter minutes:</div>
	<div><input type="text" id="mins" value="10" style="width: 50px; height: 26px"></div>
	<div style="display: flex; align-items: center;">After which stop ?</div>
	<div>
		<select id="after_stop" style="width: 200px; height: 26px;" onchange="resetTimes()">
			<option value="0">Select a stop</option>
			<?php
			$i = 0;
			foreach($stop_list as $stop)
			{
				echo "<option value='" . $i . "'>" . $stop . "</option>";
				$i++;
			}
			?>
		</select>
	</div>
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
		// echo "<td>";
		// echo "<input type=hidden name=pfee_$i value=0>";
		// 	if ($printable=="Y")
		// 		echo getdata($cursor,18);
		// 	else {
		// 	echo "<input size=30 type=text name=snotes_$i maxlength=80 value=''";
		// 	echo chop(oci_result($cursor,18));
		// 	echo "'>";
		// 	}
		// echo "</td>";

		// Q: New notes column
		echo "<td>";
			echo "<div style='display: flex; flex-direction: row; align-items: center; column-gap: 5px'>";
				echo "<input id='snotes_$i' size=30 type=text name=snotes_$i maxlength=80 value='" . getdata($cursor, 19) . "'/>";
				echo "<div id='add_$i' class='notes_btn' title='Add a note' style='width: 24px; height: 18px; margin-right: 5px;' onclick='showSnippets($i)'><pre>&#x25BC;</pre></div>";
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
<!-- #8 -->
<div id="notes_container" style="width: 100vw; height: 100vh; position: fixed; top: 0; left: 0; display: none; flex-direction: row; align-items: center; justify-content: center">

	<div style="display: flex; flex-direction: column; align-items: flex-start; justify-content: center; background-color: #fff; padding: 30px; border-radius: 15px;box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);">
		<div style="font-size: 18px; font-weight: bold; margin-bottom: 20px">Select a note</div>
			<div style="display: grid; grid-template-columns: 1fr 40px 40px; gap: 10px; max-height: 500px; overflow-y: auto; width: 100%; padding-right: 20px;">
				<?php
				$j = 0;

				foreach($phrases as $phrase)
				{
					echo "<div id='phrase_$j' style='cursor: pointer'>" . $phrase . "</div>";
					echo "<div class='notes_btn' onclick='addText($j)'>A</div>";
					echo "<div class='notes_btn' onclick='removeText($j)'>D</div>";

					$j++;
				}
				?>
			</div>

			<div style="display: flex; flex-direction: row;  align-items: center; justify-content: center; width: 100%; border-top: 1px solid #ccc; margin-top: 10px;">
				<div class="notes_btn" style="width: 75px; margin-top: 15px" onclick="hideSnippets()"><pre>Close Me</pre></div>
			</div>
		</div>
	</div>
</div>
<script>
baseUrl = window.location.protocol + "//" + window.location.hostname + "/icdev/timeshift/";

let note_id;
const tmp_arr = [];
const tmp_dep = [];

function saveTimes()
{
	var arrElements = document.querySelectorAll('.arr');
	var depElements = document.querySelectorAll('.dep');

	if (tmp_arr.length == 0)
	{
		arrElements.forEach(function(element) 
		{
			tmp_arr.push(element.value);
			element.style.backgroundColor = 'white';
		});
	}

	if (tmp_dep.length == 0)
	{
		depElements.forEach(function(element) 
		{
			tmp_dep.push(element.value);
			element.style.backgroundColor = 'white';
		});
	}
}

function resetTimes()
{
	var arrElements = document.querySelectorAll('.arr');
	var depElements = document.querySelectorAll('.dep');

	if (tmp_arr.length == 0)
	{
		arrElements.forEach(function(element) 
		{
			tmp_arr.push(element.value);
			element.style.backgroundColor = 'white';
		});
	}
	else 
	{
		arrElements.forEach(function(element, i) 
		{
			element.value = tmp_arr[i];
			element.style.backgroundColor = 'white';
		});
	}

	if (tmp_dep.length == 0)
	{
		depElements.forEach(function(element) 
		{
			tmp_dep.push(element.value);
			element.style.backgroundColor = 'white';
		});
	}
	else 
	{
		depElements.forEach(function(element, i) 
		{
			element.value = tmp_dep[i];
			element.style.backgroundColor = 'white';
		});
	}

	console.log('Arr: ', tmp_arr, 'Dep: ', tmp_dep);
}

function add()
{
	saveTimes();

	const afterStop = document.getElementById('after_stop').value;
	var updButton = document.getElementById('upd_button');
	updButton.style.backgroundColor = 'orange';

	var minsToAdd = parseInt(document.getElementById('mins').value);
	var arrElements = document.querySelectorAll('.arr');
	var depElements = document.querySelectorAll('.dep');

	const x = parseInt(afterStop, 10);

	arrElements.forEach(function(element) 
	{
		let timeStr = element.value;
		let name = element.name;
		let bits = name.split('_');
		let i = bits[1];

		if (timeStr != 'NONE' && i > x - 1)
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

			element.style.backgroundColor = 'orange';
		}
	});

	depElements.forEach(function(element) 
	{
		let timeStr = element.value;
		let name = element.name;
		let bits = name.split('_');
		let i = bits[1];

		if (timeStr != 'NONE' && i > x - 1)
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

			element.style.backgroundColor = 'orange';
		}
	});

	nextDay();
}

function minus()
{
	saveTimes();

	const afterStop = document.getElementById('after_stop').value;
	var updButton = document.getElementById('upd_button');
	updButton.style.backgroundColor = 'orange';
	var minsToAdd = parseInt(document.getElementById('mins').value);
	var arrElements = document.querySelectorAll('.arr');
	var depElements = document.querySelectorAll('.dep');

	const x = parseInt(afterStop, 10);

	arrElements.forEach(function(element) 
	{
		let timeStr = element.value;
		let name = element.name;
		let bits = name.split('_');
		let i = bits[1];

		if (timeStr != 'NONE' && i > x - 1)
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
			// console.log('Old time: ', timeStr, 'New time: ', newTimeStr);
			element.value = newTimeStr;

			element.style.backgroundColor = 'lime';
		}
	});

	depElements.forEach(function(element) 
	{
		let timeStr = element.value;
		let name = element.name;
		let bits = name.split('_');
		let i = bits[1];

		if (timeStr != 'NONE' && i > x - 1)
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
			// console.log('Old time: ', timeStr, 'New time: ', newTimeStr);
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
			// console.log('Arrive was befoew midnight and depart was after midnight: ', arrTime * 1, depTime * 1);
			// console.log('NEXT DAY = CHECKED');
			arrElements[i].style.backgroundColor = 'red';
			depElements[i].style.backgroundColor = 'red';
			nextElements[i].checked = true;
		}
		
		console.log('Arrive: ', arrTime, 'Depart: ', depTime);
		if (!(arrTime >= lastDep))
		{
			// console.log('Cos arr time > last dep time both are after midnight: ', arrTime * 1, depTime * 1);
			// console.log('NEXT DAY = CHECKED');
			nextElements[i].checked = true;
		}

		// console.log('NEXT DAY = ZILCH');
		lastDep = depTime;
	}
}

// Notes utility
function showSnippets(id)
{
	note_id = id;
	let note_field = document.getElementById('snotes_' + id);
	console.log('Note field: ', note_field.value , ' > ', id );
	var notes_container = document.getElementById('notes_container');

	notes_container.style.display = 'flex';
}

function hideSnippets()
{
	var notes_container = document.getElementById('notes_container');

	notes_container.style.display = 'none';
}

function addText(line_id)
{
	let phrase = document.getElementById('phrase_' + line_id).innerText;
	let note_field = document.getElementById('snotes_' + note_id);

	if (note_field.value.length > 0)
	{
		note_field.value += ', ' + phrase;
	}
	else 
	{
		note_field.value += phrase;
	}
	console.log('Note field: ', note_field.value , ' > ', line_id, ' > ', note_id);
}

function removeText(line_id)
{
	let phrase = document.getElementById('phrase_' + line_id).innerText;
	let note_field = document.getElementById('snotes_' + note_id);

	let a = phrase.toLowerCase();
	let b = note_field.value.toLowerCase();
	
	if (b.includes(a))
	{
		if (b.includes(', ' + a))
		{
			note_field.value = b.replace(', ' + a, '');
		}
		else 
		{
			note_field.value = b.replace(a, '');
		}
	}
}


// function addNote(i)
// {
// 	var addBtn = document.getElementById('add_' + i);
// 	var addSaveBtn = document.getElementById('add_save_' + i);
// 	var cancelBtn = document.getElementById('cancel_' + i);
// 	var editBtn = document.getElementById('edit_' + i);
// 	var editSaveBtn = document.getElementById('edit_save_' + i);
// 	var deleteBtn = document.getElementById('delete_' + i);
// 	var noteDropDown = document.getElementById('notes_' + i);
// 	var noteInput = document.getElementById('note_text_' + i);

// 	addBtn.style.display = 'none';
// 	addSaveBtn.style.display = 'flex';
// 	cancelBtn.style.display = 'flex';
// 	cancelBtn.style.marginRight = '5px';
// 	editBtn.style.display = 'none';
// 	editSaveBtn.style.display = 'none';
// 	deleteBtn.style.display = 'none';
// 	noteDropDown.style.display = 'none';
// 	noteInput.style.display = 'flex';

// 	noteInput.focus();
// }

// function addSave(i)
// {
// 	console.log('Save dat ting');
// 	var dropDown = document.getElementById('note_text_' + i).value;

// 	const formData = { "action" : 1, "dropdown": dropDown };

// 	const result = sendData(formData)
// 	.then(result => 
// 	{
// 		console.log('Result add save: ', result);
// 		fetchUpdatedNotes(result, "add");
// 	});

// 	// addCancel(i);
// }

// function editNote(i)
// {
// 	var theNote = document.getElementById('notes_' + i);
// 	var noteText = document.getElementById('note_text_' + i);
// 	var addBtn = document.getElementById('add_' + i);
// 	var addSaveBtn = document.getElementById('add_save_' + i);
// 	var cancelBtn = document.getElementById('cancel_' + i);
// 	var editBtn = document.getElementById('edit_' + i);
// 	var editSaveBtn = document.getElementById('edit_save_' + i);
// 	var deleteBtn = document.getElementById('delete_' + i);
// 	var noteDropDown = document.getElementById('notes_' + i);
// 	var noteInput = document.getElementById('note_text_' + i);

// 	addBtn.style.display = 'none';
// 	addSaveBtn.style.display = 'none';
// 	cancelBtn.style.display = 'flex';
// 	cancelBtn.style.marginRight = '5px';
// 	editBtn.style.display = 'none';
// 	editSaveBtn.style.display = 'flex';
// 	deleteBtn.style.display = 'none';
// 	noteDropDown.style.display = 'none';
// 	noteInput.style.display = 'flex';

// 	theNoteLabel = noteDropDown.options[theNote.selectedIndex].text;
// 	theNoteValue = noteDropDown.options[theNote.selectedIndex].value;

// 	console.log('The note: ', theNoteValue, theNoteLabel);

// 	noteText.value = theNoteLabel;

// 	noteInput.focus();
// }

// function editSave(i)
// {
// 	console.log('Update dat ting');
// 	var dropdownSerial = document.getElementById('notes_' + i).value;
// 	var dropDown = document.getElementById('note_text_' + i).value;

// 	console.log('The note: ', dropdownSerial, dropDown);

// 	const formData = { "action" : 2, "dropdown_serial": dropdownSerial, "dropdown": dropDown };

// 	const result = sendData(formData)
// 	.then(result => 
// 	{
// 		fetchUpdatedNotes(i, "edit");
// 	});
// }

// function remove(i)
// {
// 	const dropdownSerial = document.getElementById('notes_' + i).value;
	
// 	console.log('Remove dat ting', i, " > ", dropdownSerial);

// 	if (confirm('Are you sure you want to remove this note?')) 
// 	{
// 		const formData = { "action" : 3, "dropdown_serial": dropdownSerial };

// 		const result = sendData(formData)
// 		.then(result => 
// 		{
// 			console.log('Result remove: ', result);
// 			fetchUpdatedNotes(i, "remove");
// 		});
// 	}
// 	else 
// 	{
//         console.log('Remove action cancelled');
//     }
// }

// async function fetchUpdatedNotes(i, mode) 
// {
// 	const formData = { "action" : 0 };

// 	const result = sendData(formData)
// 	.then(result => 
// 	{
// 		const notes = JSON.parse(result);
// 		updateDropdown(notes, i, mode);
// 	});
// }

// function updateDropdown(notes, i, mode)
// {
// 	console.log('Notes received: ', notes);
// 	var noteDropdown = document.getElementById('notes_' + i);
// 	const selected = noteDropdown.value;
// 	console.log('Note dropdown value: ', noteDropdown.value);
// 	var ddElements = document.querySelectorAll('.dd');

// 	ddElements.forEach(function(element) 
// 	{
// 		element.innerHTML = '';
// 		let v = "NONE";
// 		let l = "NONE";
// 		let initOption = document.createElement('option');
// 		initOption.value = v;
// 		initOption.text = l;
// 		element.add(initOption);

// 		//Add new options
// 		notes.forEach(note => 
// 		{
// 			var option = document.createElement('option');
// 			option.value = note.dropdown_serial;
// 			option.text = note.dropdown;
// 			element.add(option);
// 		});
// 	});

// 	cancel(i);
//     // Optionally, set the selected value to the updated note
//     // noteDropdown.value = document.getElementById('note_text_' + i).value;
// 	if (mode == "remove")
// 	{
// 		noteDropdown.value = "NONE";
// 	}
// 	else 
// 	{
// 		noteDropdown.value = selected;
// 	}
// }

// function cancel(i)
// {
// 	var addBtn = document.getElementById('add_' + i);
// 	var addSaveBtn = document.getElementById('add_save_' + i);
// 	var cancelBtn = document.getElementById('cancel_' + i);
// 	var editBtn = document.getElementById('edit_' + i);
// 	var editSaveBtn = document.getElementById('edit_save_' + i);
// 	var deleteBtn = document.getElementById('delete_' + i);
// 	var noteDropDown = document.getElementById('notes_' + i);
// 	var noteInput = document.getElementById('note_text_' + i);

// 	addBtn.style.display = 'flex';
// 	addSaveBtn.style.display = 'none';
// 	cancelBtn.style.display = 'none';
// 	editBtn.style.display = 'flex';
// 	editSaveBtn.style.display = 'none';
// 	deleteBtn.style.display = 'flex';
// 	noteDropDown.style.display = 'flex';
// 	noteInput.style.display = 'none';

// 	noteInput.value = '';
// }

// async function sendData(formData) 
// {
// 	const phpUrl = baseUrl + 'modify_route_stops_model.php';
	
// 	const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
// 	const result = await response.text();
	
// 	return result;
// }

</script>
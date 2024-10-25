
<html>
<body xoncontextmenu="showMenu(); return false"; bgcolor="#FFFFFF" text="#000000"
 topmargin=2 leftmargin=2 link="#000000" vlink="#000000" alink="#000000">
<script language=javascript>
function doquit()
{
	if (confirm("Quit Application?"))
		window.location='logoff.phtml';
}
</script>

<!-- Context Menu -->
<div id=menu1 onclick="clickMenu()" onmouseover="toggleMenu()" onmouseout="toggleMenu()" style="position:absolute;display:none;border: 1px outset black; width:180;background-Color:menu">

<div class="menuItem" style="cursor:pointer;" onclick="window.print()">
Print</div>
<hr>

<div class="menuItem" style="cursor:pointer;" onclick="window.location='management.phtml'">
Management Page</div>

<div class="menuItem" style="cursor:pointer;" onclick="window.location='main.phtml'">
Main Search Page</div>
<hr>

<div class="menuItem" style="cursor:pointer;" onclick="doquit()">
Quit Application</div>
</div>
<!-- End of Context Menu -->

<script>

var el;

function showMenu() {
   ContextElement=event.srcElement;
   //menu1.style.left+=10;
   menu1.style.left=event.clientX + "px";
   menu1.style.top=event.clientY + "px";
   menu1.style.display="";
   //menu1.setCapture();
   return true;
}
function toggleMenu() {   
   el=event.srcElement;
   if (el.className=="menuItem") {
      el.className="highlightItem";
   } else if (el.className=="highlightItem") {
      el.className="menuItem";
   }
}
function clickMenu() {
//   menu1.releaseCapture();
   menu1.style.display="none";
   el=event.srcElement;
   if (el.onclick != null) {
     eval(el.onclick);
   }
}
</script>
<div><a href='switch_user.phtml?returnto=%2Fbooking%2Ftour-day.phtml%3Fdepot%3DCA%26ss%3D103449%26shift%3D1%26date%3D20240903%26frame%3D2%26hirein%3D'><img height=25 id=flag src='images/ZAR.gif' /></a><script type='text/javascript'>function switchoff(flag) { document.getElementById('flag').src='images/'+flag ; }</script></div>Shift 1 on date 20240903:<Br><form method=post action=tour-day.phtml><input type=hidden name=depot value='CA'><input type=hidden name=ser value=><input type=hidden name=ss value=103449><input type=hidden name=action value=update><input type=hidden name=date value=20240903><input type=hidden name=shift value=1><input type=hidden name=frame value=2><input type=hidden name=iframe value=''><b>Time Vehicle leaves the depot: </b><input name=depot_time size=4 maxlength=4 value=''><br><b>Time Vehicle Arrives back @ Depot: </b><input name=depot_arrive size=5 maxlength=5 value=''><br>Cross Border</td><td><select name=crossborder><option value='X'>No<option value=N>Namibia<option value=Y>YES - Cross border, NOT Namibia</select><bR><span id=airport >AIRPORT Transfer <input type=checkbox id=aircheck name=airport onchange='check_airport()' value=Y ></span><br><script>
	function check_airport() {

		let a = document.getElementById('airport');
		if ( document.getElementById('aircheck').checked ) {
				a.style.background = 'yellow';
		} else {
				a.style.background = '#FFFFFF';
		}

	}
</script>
<script>
	
</script>
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
// Fetch snippets
$common = array();
$trip = array();
$places = array();
$other = array();

$conn = oci_conn();
$sql = "SELECT * FROM tour_day_snippets ORDER BY id";
$cursor = oci_parse($conn, $sql);
oci_execute($cursor);

$snippets = array();
while ($row = oci_fetch_array($cursor, OCI_ASSOC+OCI_RETURN_NULLS)) 
{
	$category = $row['CATEGORY'];
	switch ($category) {
		case 1:
			$common[] = $row;
			break;
		case 2:
			$trip[] = $row;
			break;
		case 3:
			$places[] = $row;
			break;
		case 4:
			$other[] = $row;
			break;
	}
	// $snippets[] = $row;
}

oci_close($conn);

?>
<div style="display: flex; flex-direction: row; justify-content: space-between">
	<div>
		Notes:<br>
<textarea id="notes" maxlength=1495 name=notes cols=40 rows=15 maxlenth=255>
Full Day Cpt
Hotel-UWC- RTN <br> AAA
</textarea>
	</div>
	<div style="flex-direction: column; margin-left: 20px; width: 800px; max-height: 280px; overflow: hidden; overflow-y: auto;">
		<div style="display: flex; flex-direction: 'row'; justify-content: space-between; margin-bottom: 5px; border-bottom: 1px solid #000000">
			<div id="snippet_title" style="flex: 1">Snippets: Common</div>
			<div id="action" style="background: #eaeaea; padding: 2px 5px; font-size: 13px; border: 1px solid #000000; border-radius: 3px; font-family: Arial, Helvetica, sans-serif; margin-bottom: 3px; cursor: pointer" onclick="manage();">Manage</div>
			<div onclick="addSpecial('\n');" style="display: flex; flex-direction: row; align-items: center; justify-content: center; margin-left: 5px; background: #eaeaea; padding: 2px 5px; font-size: 13px; border: 1px solid #000000; border-radius: 3px; font-family: Arial, Helvetica, sans-serif; margin-bottom: 3px; cursor: pointer">CR</div>
			<div onclick="addSpecial('- ');" style="display: flex; flex-direction: row; align-items: center; justify-content: center; margin-left: 5px; background: #eaeaea; padding: 2px 5px; font-size: 13px; border: 1px solid #000000; border-radius: 3px; font-family: Arial, Helvetica, sans-serif; margin-bottom: 3px; cursor: pointer">-</div>
		</div>
		<div id="select" style="display: block">
			<div style="display: flex; flex-direction: 'row'; justify-content: space-between; column-gap: 10px; margin-bottom: 5px;">
				<!-- <div>Times AM</div> -->
				<div style="font-size: 12px; border: 1px solid #000000; flex: 1; padding: 2px 5px; cursor: pointer;  font-family: Arial, Helvetica, sans-serif" onclick="showHide(1)">Common</div>
				<div style="font-size: 12px; border: 1px solid #000000; flex: 1; padding: 2px 5px; cursor: pointer;  font-family: Arial, Helvetica, sans-serif" onclick="showHide(2)">Trip</div>
				<div style="font-size: 12px; border: 1px solid #000000; flex: 1; padding: 2px 5px; cursor: pointer;  font-family: Arial, Helvetica, sans-serif" onclick="showHide(3)">Places</div>
				<div style="font-size: 12px; border: 1px solid #000000; flex: 1; padding: 2px 5px; cursor: pointer;  font-family: Arial, Helvetica, sans-serif" onclick="showHide(4)">Other</div>
				<!-- <div>Times PM</div> -->
			</div>
			
			<div style="display: flex; flex-direction: row; align-items: flex-start; column-gap: 5px; font-size: 11px; font-family: Arial, Helvetica, sans-serif; border: 1px solid #000000; ">
				<div>
					<div style="display: grid; grid-template-columns: repeat(2, 45px); row-gap: 2px; font-family: Courier New; font-size: 12; border-right: 1px solid #000000;  text-align: center; cursor: pointer">
						<div onclick="addKeyword(this.innerText)">00:00</div>
						<div onclick="addKeyword(this.innerText)">00:30</div>
						<div onclick="addKeyword(this.innerText)">01:00</div>
						<div onclick="addKeyword(this.innerText)">01:30</div>
						<div onclick="addKeyword(this.innerText)">02:00</div>
						<div onclick="addKeyword(this.innerText)">02:30</div>
						<div onclick="addKeyword(this.innerText)">03:00</div>
						<div onclick="addKeyword(this.innerText)">03:30</div>
						<div onclick="addKeyword(this.innerText)">04:00</div>
						<div onclick="addKeyword(this.innerText)">04:30</div>
						<div onclick="addKeyword(this.innerText)">05:00</div>
						<div onclick="addKeyword(this.innerText)">05:30</div>
						<div onclick="addKeyword(this.innerText)">06:00</div>
						<div onclick="addKeyword(this.innerText)">06:30</div>
						<div onclick="addKeyword(this.innerText)">07:00</div>
						<div onclick="addKeyword(this.innerText)">07:30</div>
						<div onclick="addKeyword(this.innerText)">08:00</div>
						<div onclick="addKeyword(this.innerText)">08:30</div>
						<div onclick="addKeyword(this.innerText)">09:00</div>
						<div onclick="addKeyword(this.innerText)">09:30</div>
						<div onclick="addKeyword(this.innerText)">10:00</div>
						<div onclick="addKeyword(this.innerText)">10:30</div>
						<div onclick="addKeyword(this.innerText)">11:00</div>
						<div onclick="addKeyword(this.innerText)">11:30</div>
					</div>
				</div>
				<div style="flex: 1;">
					<div id="common" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 2px">
						<?php foreach ($common as $snippet) { ?>
							<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start;">
								<div onclick="addKeyword(this.innerText);" style="background-color: #f5f5f5; width: 100%; font-family: Courier New; font-size: 13; padding: 4px 5px; cursor: pointer"><?php echo $snippet['SNIPPET']; ?></div>
							</div>
						<?php } ?>
					</div>
					<div id="trip" style="display: none; grid-template-columns: repeat(5, 1fr); gap: 2px">
						<?php foreach ($trip as $snippet) { ?>
							<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start;">
								<div onclick="addKeyword(this.innerText);" style="background-color: #f5f5f5; width: 100%; font-family: Courier New; font-size: 13; padding: 4px 5px; cursor: pointer"><?php echo $snippet['SNIPPET']; ?></div>
							</div>
						<?php } ?>
					</div>
					<div id="places" style="display: none; grid-template-columns: repeat(5, 1fr); gap: 2px">
						<?php foreach ($places as $snippet) { ?>
							<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start;">
								<div onclick="addKeyword(this.innerText);" style="background-color: #f5f5f5; width: 100%; font-family: Courier New; font-size: 13; padding: 4px 5px; cursor: pointer"><?php echo $snippet['SNIPPET']; ?></div>
							</div>
						<?php } ?>
					</div>
					<div id="other" style="display: none; grid-template-columns: repeat(5, 1fr); gap: 2px">
						<?php foreach ($other as $snippet) { ?>
							<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start;">
								<div onclick="addKeyword(this.innerText);" style="background-color: #f5f5f5; width: 100%; font-family: Courier New; font-size: 13; padding: 4px 5px; cursor: pointer"><?php echo $snippet['SNIPPET']; ?></div>
							</div>
						<?php } ?>
					</div>
				</div>
				<div>
				<div style="display: grid; grid-template-columns: repeat(2, 45px); row-gap: 2px; font-family: Courier New; font-size: 12; border-left: 1px solid #000000; text-align: center; cursor: pointer">
						<div onclick="addKeyword(this.innerText)">12:00</div>
						<div onclick="addKeyword(this.innerText)">12:30</div>
						<div onclick="addKeyword(this.innerText)">13:00</div>
						<div onclick="addKeyword(this.innerText)">13:30</div>
						<div onclick="addKeyword(this.innerText)">14:00</div>
						<div onclick="addKeyword(this.innerText)">14:30</div>
						<div onclick="addKeyword(this.innerText)">15:00</div>
						<div onclick="addKeyword(this.innerText)">15:30</div>
						<div onclick="addKeyword(this.innerText)">16:00</div>
						<div onclick="addKeyword(this.innerText)">16:30</div>
						<div onclick="addKeyword(this.innerText)">17:00</div>
						<div onclick="addKeyword(this.innerText)">17:30</div>
						<div onclick="addKeyword(this.innerText)">18:00</div>
						<div onclick="addKeyword(this.innerText)">18:30</div>
						<div onclick="addKeyword(this.innerText)">19:00</div>
						<div onclick="addKeyword(this.innerText)">19:30</div>
						<div onclick="addKeyword(this.innerText)">20:00</div>
						<div onclick="addKeyword(this.innerText)">20:30</div>
						<div onclick="addKeyword(this.innerText)">21:00</div>
						<div onclick="addKeyword(this.innerText)">21:30</div>
						<div onclick="addKeyword(this.innerText)">22:00</div>
						<div onclick="addKeyword(this.innerText)">22:30</div>
						<div onclick="addKeyword(this.innerText)">23:00</div>
						<div onclick="addKeyword(this.innerText)">23:30</div>
					</div>
				</div>
			</div>
		</div>

		<div id="manage" style="display: none">
			<!-- <div>Manage keyword</div> -->
			<div style="display: flex; flex-direction: row; padding-bottom: 5px; border-bottom: 1px solid #000000">
				<div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; font-weight: bold;">Category 
					<select name=category id="category" onChange="showHideEdit(this.value)" style="height: 21px">
						<option value='0'>Select...</option>
						<option value='1'>Common</option>
						<option value='2'>Trip</option>
						<option value='3'>Places</option>
						<option value='4'>Other</option>
					</select>
				</div>
				<div style="margin-left: 5px; flex: 1">
					<input type="text" name="snippet" id="snippet" placeholder="Snippet description">
					<input type="text" name="snippet_id" id="snippet_id" value="1" style="display: none;">
				</div>
				<div id="form_btn_add" style="display: block; padding-left: 5px;"><input  type="button" value="Add" style=" cursor: pointer;" onclick="sendData(0);"></div>
				<div id="form_btn_edit" style="display: none; padding-left: 5px;"><input type="button" value="Update" style=" cursor: pointer;" onclick="sendData(1);"></div>
			</div>

			<div id="common_edit" style="display: none;">
				<?php foreach ($common as $snippet) { ?>
					<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start; border-bottom: 1px solid #eaeaea;">
						<div style="flex: 1; font-family: Courier New; font-size: 13; padding: 4px 5px;"><?php echo $snippet['SNIPPET']; ?></div>
						<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="editSnippet(<?php echo $snippet['ID']; ?>, '<?php echo $snippet['SNIPPET']; ?>')">Edit</div>
						<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="deleteSnippet(<?php echo $snippet['ID']; ?>)">Delete</div>
					</div>
				<?php } ?>
			</div>
			<div id="trip_edit" style="display: none">
				<?php foreach ($trip as $snippet) { ?>
					<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start; border-bottom: 1px solid #eaeaea;">
						<div style="flex: 1; font-family: Courier New; font-size: 13; padding: 4px 5px;"><?php echo $snippet['SNIPPET']; ?></div>
						<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="editSnippet(<?php echo $snippet['ID']; ?>, '<?php echo $snippet['SNIPPET']; ?>')">Edit</div>
						<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="deleteSnippet(<?php echo $snippet['ID']; ?>)">Delete</div>
					</div>
				<?php } ?>
			</div>
			<div id="places_edit" style="display: none">
				<?php foreach ($places as $snippet) { ?>
					<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start; border-bottom: 1px solid #eaeaea;">
						<div style="flex: 1; font-family: Courier New; font-size: 13; padding: 4px 5px;"><?php echo $snippet['SNIPPET']; ?></div>
						<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="editSnippet(<?php echo $snippet['ID']; ?>, '<?php echo $snippet['SNIPPET']; ?>')">Edit</div>
						<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="deleteSnippet(<?php echo $snippet['ID']; ?>)">Delete</div>
					</div>
				<?php } ?>
			</div>
			<div id="other_edit" style="display: none">
				<?php foreach ($other as $snippet) { ?>
					<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start; border-bottom: 1px solid #eaeaea;">
						<div style="flex: 1; font-family: Courier New; font-size: 13; padding: 4px 5px;"><?php echo $snippet['SNIPPET']; ?></div>
						<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="editSnippet(<?php echo $snippet['ID']; ?>, '<?php echo $snippet['SNIPPET']; ?>')">Edit</div>
						<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="deleteSnippet(<?php echo $snippet['ID']; ?>)">Delete</div>
					</div>
				<?php } ?>
			</div>
		</div>
			<!-- <div style="display: flex; flex-direction: 'row'; justify-content: space-between;"> -->
	</div>
	<div style="flex: 1">&nbsp;</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', (event) => {
    // Focus the textarea and place the cursor inside it
    const notesTextarea = document.getElementById('notes');
    notesTextarea.focus();
    // notesTextarea.selectionStart = notesTextarea.value.length;
    // notesTextarea.selectionEnd = notesTextarea.value.length;
});	
function addKeyword(keyword) 
{
	let notes = document.getElementById('notes');
	// let body = notes.value.trim();
	notes.value += keyword + ' ';
	focustoCursor();
}
function addSpecial(snippet) 
{
	let notes = document.getElementById('notes');
	// let body = notes.value.trim();
	notes.value += snippet;
	focustoCursor();
}
function focustoCursor()
{
	const notesTextarea = document.getElementById('notes');
    notesTextarea.focus();
    notesTextarea.selectionStart = notesTextarea.value.length;
    notesTextarea.selectionEnd = notesTextarea.value.length;
}
function keywords() {
	let select = document.getElementById('select');
	select.style.display = select.style.display === 'block' ? 'none' : 'block';
	let manage = document.getElementById('manage');
	manage.style.display = manage.style.display === 'block' ? 'none' : 'block';
}
function manage()
{
	let action = document.getElementById('action');
	action.innerText = action.innerText === 'Manage' ? 'Close' : 'Manage';
	let select = document.getElementById('select');
	select.style.display = select.style.display === 'block' ? 'none' : 'block';
	let manage = document.getElementById('manage');
	manage.style.display = manage.style.display === 'block' ? 'none' : 'block';

	if (action.innerText === 'Manage')
	{
		let formBtnAdd = document.getElementById('form_btn_add');
		let formBtnEdit = document.getElementById('form_btn_edit');
		formBtnAdd.style.display = 'block';
		formBtnEdit.style.display = 'none';

		let snippet = document.getElementById('snippet');
		snippet.value = '';
		setDefaultData();
	}
}
function showHide(category) 
{
	let snippetTitle = document.getElementById('snippet_title');
	let common = document.getElementById('common');
	let trip = document.getElementById('trip');
	let places = document.getElementById('places');
	let other = document.getElementById('other');

	switch (category) {
		case 1:
			snippetTitle.innerHTML = 'Snippets: <span style="font-weight: normal; opacity: 0.5">Common</span>';
			common.style.display = 'grid';
			trip.style.display = 'none';
			places.style.display = 'none';
			other.style.display = 'none';
			break;
		case 2:
			snippetTitle.innerHTML = 'Snippets: <span style="font-weight: normal; opacity: 0.5">Trip</span>';
			common.style.display = 'none';
			trip.style.display = 'grid';
			places.style.display = 'none';
			other.style.display = 'none';
			break;
		case 3:
			snippetTitle.innerHTML = 'Snippets: <span style="font-weight: normal; opacity: 0.5">Places</span>';
			common.style.display = 'none';
			trip.style.display = 'none';
			places.style.display = 'grid';
			other.style.display = 'none';
			break;
		case 4:
			snippetTitle.innerHTML = 'Snippets: <span style="font-weight: normal; opacity: 0.5">Other</span>';
			common.style.display = 'none';
			trip.style.display = 'none';
			places.style.display = 'none';
			other.style.display = 'grid';
			break;
	}
}

function showHideEdit(category)
{
	console.log('GOT HERE: ', category);
	let common = document.getElementById('common_edit');
	let trip = document.getElementById('trip_edit');
	let places = document.getElementById('places_edit');
	let other = document.getElementById('other_edit');

	switch (category)
	{
		case '1':
			console.log('AAAA');
			common.style.display = 'grid';
			trip.style.display = 'none';
			places.style.display = 'none';
			other.style.display = 'none';
			break;
		case '2':
			common.style.display = 'none';
			trip.style.display = 'block';
			places.style.display = 'none';
			other.style.display = 'none';
			break;
		case '3':
			common.style.display = 'none';
			trip.style.display = 'none';
			places.style.display = 'block';
			other.style.display = 'none';
			break;
		case '4':
			common.style.display = 'none';
			trip.style.display = 'none';
			places.style.display = 'none';
			other.style.display = 'block';
			break;
	}
}

function editSnippet(id, desc)
{
	let formBtnAdd = document.getElementById('form_btn_add');
	let formBtnEdit = document.getElementById('form_btn_edit');
	let snippetId = document.getElementById('snippet_id');
	let snippetCategory = document.getElementById('category').value;
	let snippet = document.getElementById('snippet');
	
	snippetId.value = id;
	formBtnAdd.style.display = 'none';
	formBtnEdit.style.display = 'block';
	snippet.value = desc;
}

function deleteSnippet(id)
{
	let snippetId = document.getElementById('snippet_id');
	snippetId.value = id;

	if (confirm('Are you sure you want to delete this snippet?'))
	{
		sendData(2);
	}
}

async function fetchSnippets(category)
{
	console.log('CATEGORY: ', category);
	let data = {};
	let formData = {};

	data = {
		category: category
	};

	formData = { "action": 3, "data": data };
	
	const phpUrl = 'http://localhost/icdev/tour_day_snippets.php';
    const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
    const result = await response.text();
	setEditData(category, JSON.parse(result));
    // console.log('Result:', result);
}

async function setDefaultData()
{
	let data = {};
	let formData = {};

	data = {
		category: 1
	};

	formData = { "action": 3, "data": data };
	
	const phpUrl = 'http://localhost/icdev/tour_day_snippets.php';
    const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
    const result = await response.text();
	let snippets = JSON.parse(result);
	console.log('Result:', data);

	let common = document.getElementById('common');
	common.innerHTML = '';
	snippets.forEach(snippet => {
		common.innerHTML += `<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start; ">
		<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start;">
			<div onclick="addKeyword(this.innerText);" style="font-family: Courier New; font-size: 13; padding: 4px 5px; cursor: pointer">${snippet.SNIPPET}</div>
		</div>
		</div>`;
	});
}

function setEditData(category, data)
{
	console.log('GOT HERE: ', category, data);
	let formBtnAdd = document.getElementById('form_btn_add');
	let formBtnEdit = document.getElementById('form_btn_edit');
	formBtnAdd.style.display = 'block';
	formBtnEdit.style.display = 'none';

	let snippet = document.getElementById('snippet');
	snippet.value = '';

	switch (category)
	{
		case '1':
			let common = document.getElementById('common_edit');
			common.innerHTML = '';
			data.forEach(snippet => {
				common.innerHTML += `<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start; border-bottom: 1px solid #eaeaea; ">
					<div style="flex: 1; font-family: Courier New; font-size: 13; padding: 4px 5px;">${snippet.SNIPPET}</div>
					<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="editSnippet(${snippet.ID}, '${snippet.SNIPPET}')">Edit</div>
					<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="deleteSnippet(${snippet.ID})">Delete</div>
				</div>`;
			});
		break;
		case '2':
			let trip = document.getElementById('trip_edit');
			trip.innerHTML = '';
			data.forEach(snippet => {
				trip.innerHTML += `<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start; border-bottom: 1px solid #eaeaea; ">
					<div style="flex: 1; font-family: Courier New; font-size: 13; padding: 4px 5px;">${snippet.SNIPPET}</div>
					<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="editSnippet(${snippet.ID}, '${snippet.SNIPPET}')">Edit</div>
					<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="deleteSnippet(${snippet.ID})">Delete</div>
				</div>`;
			});
		break;
		case '3':
			let places = document.getElementById('places_edit');
			places.innerHTML = '';
			data.forEach(snippet => {
				places.innerHTML += `<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start; border-bottom: 1px solid #eaeaea; ">
					<div style="flex: 1; font-family: Courier New; font-size: 13; padding: 4px 5px;">${snippet.SNIPPET}</div>
					<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="editSnippet(${snippet.ID}, '${snippet.SNIPPET}')">Edit</div>
					<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="deleteSnippet(${snippet.ID})">Delete</div>
				</div>`;
			});
		break;
		case '4':
			let other = document.getElementById('other_edit');
			other.innerHTML = '';
			data.forEach(snippet => {
				other.innerHTML += `<div style="display: flex; flex-direction: 'row'; align-items: center; justify-content: flex-start; border-bottom: 1px solid #eaeaea; ">
					<div style="flex: 1; font-family: Courier New; font-size: 13; padding: 4px 5px;">${snippet.SNIPPET}</div>
					<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="editSnippet(${snippet.ID}, '${snippet.SNIPPET}')">Edit</div>
					<div style="width: 40px; font-family: Arial, Helvetica, sans-serif; font-size: 12; opacity: 0.7; cursor: pointer" onclick="deleteSnippet(${snippet.ID})">Delete</div>
				</div>`;
			});
		break;
	}
}

async function sendData(action) 
{
	const type = document.getElementById('category').value;

	let data = {};
	let formData = {};
	if (action == 0)
	{
		const category = document.getElementById('category').value;
		const snippet = document.getElementById('snippet').value;
		
		data = {
			category: category,
			snippet: snippet
		};	
		// formData = { "action": action, "data": data };
	} 
	else if(action == 1)
	{
		const snippet_id = document.getElementById('snippet_id').value;
		const category = document.getElementById('category').value;
		const snippet = document.getElementById('snippet').value;
		
		data = {
			snippet_id: snippet_id,
			category: category,
			snippet: snippet
		};	
	} 
	else 
	{
		const snippet_id = document.getElementById('snippet_id').value;
		
		data = {
			snippet_id: snippet_id
		};
	}

	formData = { "action": action, "data": data };
	
	const phpUrl = 'http://localhost/icdev/tour_day_snippets.php';
    const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
    const result = await response.text();
    console.log('Result:', result);

	if (result == 1)
	{
		fetchSnippets(type);
	}
    return result;
}
</script>

<p>
Ops Team: <select name=team><option value='0'>0|None Yet</option>
<option value='1'>1</option>
<option value='2'>2</option>
<option value='3' SELECTED>3</option>
<option value='4'>4</option>
<option value='5'>5</option>
<option value='6'>6</option>
<option value='7'>7</option>
<option value='8'>8</option>
<option value='9'>9</option>
<option value='10'>10</option>
<option value='11'>11</option>
<option value='12'>12</option>
<option value='13'>13</option>
<option value='14'>14</option>
<option value='15'>15</option>
<option value='16'>16</option>
<option value='17'>17</option>
<option value='18'>18</option>
<option value='19'>19</option>
<option value='20'>20</option>
<option value='21'>21</option>
<option value='22'>22</option>
<option value='23'>23</option>
<option value='24'>24</option>
<option value='25'>25</option>
<option value='26'>26</option>
<option value='27'>27</option>
<option value='28'>28</option>
<option value='29'>29</option>
<option value='30'>30</option>
<option value='31'>31</option>
<option value='32'>32</option>
<option value='33'>33</option>
<option value='34'>34</option>
<option value='35'>35</option>
<option value='36'>36</option>
<option value='37'>37</option>
<option value='38'>38</option>
<option value='39'>39</option>
<option value='40'>40</option>
<option value='41'>41</option>
<option value='42'>42</option>
<option value='43'>43</option>
<option value='44'>44</option>
<option value='45'>45</option>
<option value='46'>46</option>
<option value='47'>47</option>
<option value='48'>48</option>
<option value='49'>49</option>
<option value='50'>50</option>
<option value='51'>51</option>
<option value='52'>52</option>
<option value='53'>53</option>
<option value='54'>54</option>
<option value='55'>55</option>
<option value='56'>56</option>
<option value='57'>57</option>
<option value='58'>58</option>
<option value='59'>59</option>
<option value='60'>60</option>
<option value='61'>61</option>
<option value='62'>62</option>
<option value='63'>63</option>
<option value='64'>64</option>
<option value='65'>65</option>
<option value='66'>66</option>
<option value='67'>67</option>
<option value='68'>68</option>
<option value='69'>69</option>
</select> (CA)<br>Number of Drivers Requested: 1 <input type=radio name=numdrivers value=1  CHECKED> / 2 <input type=radio name=numdrivers value=2 ><bR>Pattern Override:(SAME)<input  type=checkbox name=pat_1 value=1 CHECKED><input  type=checkbox name=pat_2 value=1 CHECKED><input  type=checkbox name=pat_3 value=1 CHECKED><br><p><input type=submit value='Update'><br><input type=submit name=submit value='Update & Set Ops team for WHOLE quote'><p><a href=tour_km.phtml?ts=169705>Go to KM page</a> | <a target='_blank' href='tour_quotes.phtml?iframe=&mode=edit&ser=169705'>Go to Quote</a><hr><font color=magenta>Details on Related Quote Line:<br>Tue 03 Sep 2024: Hotel-UWC- RTN<br></font><a href=# onclick="document.getElementById('prevlist').style.display='block'; "><font color=green><u>Copy previous itinerary:</u></font></a><br><div id=prevlist style='display: none'>company is 0 from 169705<br>

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

function get_phrases()
{
	$data = array();

	$conn = oci_conn();

	$sql = "SELECT * FROM ROUTE_STOPS_NOTES_PHRASES ORDER BY PHRASE_DESC ASC";
	$cursor = oci_parse($conn, $sql);
	
	oci_execute($cursor);

	while ($row = oci_fetch_assoc($cursor)) 
	{
		$data[] = $row;
	}

	return $data;
}
if (isset($_POST['stage']))
{
	$stage = $_POST['stage'];

	switch ($stage)
	{
		case 1:
			add_phrase();
		break;
		case 2:
			edit_phrase();
		break;
		case 3:
			delete_phrase();
		break;
	}

	header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

function add_phrase()
{
	$conn = oci_conn();
	
	$phrase_desc = TRIM($_POST['phrase_desc']);

	if ($phrase_desc != '')
	{
		$sql = "INSERT INTO ROUTE_STOPS_NOTES_PHRASES (PHRASE_SERIAL, PHRASE_DESC) VALUES (ROUTE_STOPS_NOTES_PHRASES_SEQ.nextval, :phrase_desc)";
		
		$cursor = oci_parse($conn, $sql);

		oci_bind_by_name($cursor, ':phrase_desc', $phrase_desc);
		
		oci_execute($cursor);

		oci_free_statement($cursor);

		oci_close($conn);
	}
}

function edit_phrase()
{
	$conn = oci_conn();

	$phrase_id = $_POST['phrase_id'];
	$phrase_desc = TRIM($_POST['phrase_desc']);

	if ($phrase_desc != '')
	{
		$sql = "UPDATE ROUTE_STOPS_NOTES_PHRASES SET PHRASE_DESC = :phrase_desc WHERE PHRASE_SERIAL = :phrase_serial";
				
		$cursor = oci_parse($conn, $sql);

		oci_bind_by_name($cursor, ':phrase_serial', $phrase_id);
		oci_bind_by_name($cursor, ':phrase_desc', $phrase_desc);

		oci_execute($cursor);

		oci_free_statement($cursor);

		oci_commit($conn);
	}
}

function delete_phrase()
{
	$conn = oci_conn();
	
	$phrase_id = $_POST['phrase_id'];

	$sql = "DELETE FROM ROUTE_STOPS_NOTES_PHRASES WHERE PHRASE_SERIAL = $phrase_id";
	$cursor = oci_parse($conn, $sql);
	oci_execute($cursor);
	oci_free_statement($cursor);
	oci_close($conn);
}

$phrases = get_phrases();
?>
<div style="font-size: 16px; font-weight: bold; margin-bottom: 25px;">Manage Phrases</div>
	<form method="post" action="phrases.php" name="phrasesForm" id="phrasesForm">
		<div style="display: grid; grid-template-columns: 450px 30px 30px; gap: 3px;">
			<div style="flex: 1">
				<input id="phrase_desc" name="phrase_desc" type="text" value="" style="width: 100%; line-height: 24px" placeholder="Enter phrase" />
				<input id="stage" name="stage" type="text" value="1" style="display: none" />
				<input id="phrase_id" name="phrase_id" type="text" value="0" style="display: none" />
			</div>
			<div id="form_btn" style="grid-column: span 2; background: #efefef; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; padding: 5px; cursor: pointer; border: 1px solid #000;" onclick="addPhrase()">Add</div>
			<?php foreach ($phrases as $phrase) { ?>
				<div style="display: flex; flex-direction: row; align-items: center; border-bottom: 1px solid #dfdfdf;">
					<?php echo $phrase['PHRASE_DESC']; ?>
					<input id="pd_<?php echo $phrase['PHRASE_SERIAL']; ?>" type="text" name="pd" value="<?php echo $phrase['PHRASE_DESC']; ?>" style="display: none" />
				</div>
				<div id="edit_<?php echo $phrase['PHRASE_SERIAL']; ?>" style="display: flex; flex-direction: row; align-items: center; justify-content: center; background: #efefef; padding: 5px; cursor: pointer; border: 1px solid #000;" onclick="editPhrase(this.id)">E</div>
				<div id="delete_<?php echo $phrase['PHRASE_SERIAL']; ?>" style="display: flex; flex-direction: row; align-items: center; justify-content: center; background: #efefef; padding: 5px; cursor: pointer; border: 1px solid #000;" onclick="deletePhrase(this.id)">D</div>
			<?php } ?>
		</div>
	</form>
</div>
<script>
function addPhrase()
{
	document.getElementById('phrasesForm').submit();
}
function editPhrase(id)
{
	const fromBtn = document.getElementById('form_btn');
	const get_id = id.split('_')[1];
	const pd = document.getElementById('pd_' + get_id);
	const stage = document.getElementById('stage');
	const phrase_id = document.getElementById('phrase_id');
	const phrase_desc = document.getElementById('phrase_desc');
	
	fromBtn.innerHTML = 'Update';
	stage.value = 2;
	phrase_id.value = get_id;
	phrase_desc.value = pd.value;
}
function deletePhrase(id)
{
    if (confirm('Are you sure you want to delete this phrase?')) {
        const get_id = id.split('_')[1];
        const stage = document.getElementById('stage');
        const phrase_id = document.getElementById('phrase_id');

        stage.value = 3;
        phrase_id.value = get_id;

        document.getElementById('phrasesForm').submit();
    } else {
        console.log('Deletion cancelled');
    }
}
</script>
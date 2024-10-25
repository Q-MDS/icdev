<?php
ob_start();
require_once ("../php3/oracle.inc");
require_once ("../php3/misc.inc");
require_once ("../php3/sec.inc");

if (!open_oracle()) { Exit; };
if (!AllowedAccess("")) { Exit; };

$cursor = ora_open($conn);

$sql = "SELECT * FROM move_tech_bulletins WHERE mtb_status != 0 ORDER BY mtb_order";
ora_parse($cursor, $sql);
ora_exec($cursor);
?>
<style>
.data_row {
    display: contents;
}
.data_row:hover div {
    background-color: rgba(0, 0, 0, 0.2);
}
</style>
<div style="width: 100%; border: 1px solid #000000; ">
	<!-- Page title -->
	<div style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; column-gap: 10px; padding: 5px 10px;">
		<div style="flex: 1; font-weight: bold">Manage Move Bulletins</div>
		<div><input type="button" value="Add" id="show_form" style="display: block" onclick="showAddForm()" /></div>
	</div>
	<div id="add_form" style="display: none; border-top: 1px solid #000; padding-top: 5px; background-color: #f5f5f5">
		<div style="grid-column: span4; padding-left: 20px; font-weight: bold">Add bulletin</div>
		<div style="grid-column: span4; padding-left: 20px;">Please enter bulletin information</div>
		<div style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; column-gap: 10px; padding: 5px 20px;">
			<div style="flex: 1"><input type="text" id="bulletin_name" placeholder="Enter bulletin name" style="width: 100%" /></div>
			<div style="width: 100px;"><input type="text" id="bulletin_revision" placeholder="Enter revision" style="width: 100px" /></div>
		</div>
		<div style="flex: 1; padding: 5px 20px;"><input type="text" id="bulletin_url" placeholder="Enter URL" style="width: 100%" /></div>
		<div style="display: flex; flex-direction: row; align-items: center; padding: 5px 20px 10px 20px; column-gap: 10px;">
			<div><input type="button" value="Add" id="add_btn" onclick="addBulletin()" /></div>
			<div><input type="button" value="Close" id="close_btn" onclick="hideAddForm()" /></div>
		</div>
	</div>
	<!-- Column headings -->
	<div style="display: grid; grid-template-columns: 60px auto auto auto 1fr auto repeat(2, auto); justify-items: flex-start; border-top: 1px solid #000;">
		
		<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-bottom: 0px; height: 30px; margin-top: -1px; margin-left: -1px; margin-right: -1px;">
			<div style=" padding: 0px 5px">Order</div>
		</div>
		<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-left: 0px; border-bottom: 0px; height: 30px; margin-top: -1px;"><div style=" padding: 0px 5px">Add Date</div></div>
		<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-left: 0px; border-bottom: 0px; height: 30px; margin-top: -1px;"><div style=" padding: 0px 5px">Show Date</div></div>
		<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-left: 0px; border-bottom: 0px; height: 30px; margin-top: -1px;"><div style=" padding: 0px 5px">Revision</div></div>
		<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-left: 0px; border-bottom: 0px; height: 30px; margin-top: -1px;"><div style=" padding: 0px 5px">Bulletin Name</div></div>
		<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-left: 0px; border-bottom: 0px; height: 30px; margin-top: -1px;"><div style=" padding: 0px 5px">Priority</div></div>
		<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-left: 0px; border-bottom: 0px; height: 30px; margin-top: -1px;"><div style=" padding: 0px 5px">Active</div></div>
		<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-right: 0px; border-bottom: 0px; height: 30px; margin-top: -1px;"><div style=" padding: 0px 5px">Remove</div></div>

		<?php
		while (ora_fetch_into($cursor, $row, ORA_FETCHINTO_ASSOC)) 
		{
			$mtb_id = $row['MTB_ID'];
			$mtb_order = $row['MTB_ORDER'];
			$mtb_name = $row['MTB_NAME'];
			$mtb_url = $row['MTB_URL'];
			$mtb_priority = $row['MTB_STATUS'];
			$mtb_active = $row['MTB_ACTIVE'];
			$is_checked = '';
			if ($mtb_priority == 2) ( $is_checked = 'checked' );
			$active_checked = '';
			if ($mtb_active == 1) ( $active_checked = 'checked' );
			$mtb_rev = $row['MTB_REVISION'];
			$mtb_use_date = $row['MTB_USE_DATE'];
			if ($mtb_use_date == null)
			{
				$mtb_use_date = '-';
			} 
			else 
			{
				$mtb_use_date = date('Y-m-d', $mtb_use_date);
			}
			$mtb_date = $row['MTB_DATE'];
			$mtb_date = date('Y-m-d', $mtb_date);

			echo '<div class="data_row">';
				echo '<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-bottom: 0px; border-left: 0px; height: 26px;">';
					echo '<div style=" padding: 0px 5px"><input id="edit_' . $mtb_id . '" type="text" value="' . $mtb_order . '" style="width: 99%;" onchange="setOrder(this.id, this.value)"></div>';
				echo '</div>';
				echo '<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-bottom: 0px; border-left: 0px; height: 26px"><div style=" padding: 0px 5px">' . $mtb_date . '</div></div>';
				echo '<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-bottom: 0px; border-left: 0px; height: 26px"><div style=" padding: 0px 5px">' . $mtb_use_date . '</div></div>';
				echo '<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-bottom: 0px; border-left: 0px; height: 26px"><div style=" padding: 0px 5px">' . $mtb_rev . '</div></div>';
				echo '<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-bottom: 0px; border-left: 0px; height: 26px"><div style=" padding: 0px 5px">' . $mtb_name . '</div></div>';
				echo '<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-bottom: 0px; border-left: 0px; height: 26px"><div style=" padding: 0px 5px"><input type="checkbox" id="' . $mtb_id . '" onchange="setPriority(this.id)" ' . $is_checked . '/></div></div>';
				echo '<div style="display: flex; align-items: center; width: 100%; border: 1px solid #000; border-bottom: 0px; border-left: 0px; height: 26px"><div style=" padding: 0px 5px"><input type="checkbox" id="active_' . $mtb_id . '" onchange="setActive(this.id, this.checked)" ' . $active_checked . '/></div></div>';
				echo '<div id="' . $mtb_id . '" style="display: flex; align-items: center; border: 1px solid #000; border-bottom: 0px; border-left: 0px; border-right: 0px; height: 26px; cursor: pointer" onclick="removeBulletin(this.id)"><div style=" padding: 0px 5px">Remove</div></div>';
			echo '</div>';
		}
		?>
	</div>
</div>
<script>
function showAddForm()
{
	var show_form = document.getElementById('show_form');
	var add_form = document.getElementById('add_form');
	
	if (add_form.style.display == 'none')
	{
		add_form.style.display = 'block';
		show_form.style.display = 'none';
	}
	else
	{
		add_form.style.display = 'none';
		show_form.style.display = 'block';
	}
}
function hideAddForm()
{
	document.getElementById('add_form').style.display = 'none';
	document.getElementById('show_form').style.display = 'block';
}

function addBulletin()
{
	let errCtr = 0;
	const bulletinName = document.getElementById('bulletin_name').value;
	const bulletinRevision = document.getElementById('bulletin_revision').value;
	const bulletinUrl = document.getElementById('bulletin_url').value;

	if (bulletinName == '')
	{
		alert('Please enter bulletin name');
		errCtr++;
	}	
	if (bulletinRevision == '')
	{
		alert('Please enter bulletin revision');
		errCtr++;
	}
	if (bulletinUrl == '')
	{
		alert('Please enter bulletin URL');
		errCtr++;
	}

	if (errCtr == 0)
	{
		const formData = { "mtr_action": 2, "bulletin_name": bulletinName, "bulletin_revision": bulletinRevision, "bulletin_url": bulletinUrl };
	
		sendData(formData)
		.then(result => 
		{ 
			if (result == 1)
			{
				alert('Bulletin added successfully');
				window.location.reload(); 
			}
			else
			{
				alert('Bulletin not added');
			}
		});
	}
}

function setPriority(id)
{
	const cbPriority = document.getElementById(id);

	const formData = { "mtr_action": 3, "mtb_id": id, "mtb_status": cbPriority.checked ? 2 : 1 };

	sendData(formData)
	.then(result => 
	{ 
		if (result == 1)
		{
			window.location.reload(); 
		}
		else
		{
			alert('Bulletin priority not changed');
		}
	});
}

function removeBulletin(mtbId)
{
	const formData = { "mtr_action": 4, "mtb_id": mtbId };

	sendData(formData)
	.then(result => 
	{ 
		if (result == 1)
		{
			alert('Bulletin removed successfully');
			window.location.reload(); 
		}
		else
		{
			alert('Bulletin not removed');
		}
	});
}


function setOrder(id, value)
{
	let bits = id.split('_');

	const formData = { "mtr_action": 5, "mtb_id": bits[1], "mtb_order": value };

	sendData(formData)
	.then(result => 
	{ 
		console.log('Result: ', result);
		if (result == 1)
		{
			window.location.reload(); 
		}
		else
		{
			alert('Order not changed');
		}
	});
}

function setActive(id, checked)
{
	let bits = id.split('_');
	
	const active = checked ? 1 : 0;;

	const formData = { "mtr_action": 6, "mtb_id": bits[1], "mtb_active": active };

	sendData(formData)
	.then(result => 
	{ 
		console.log('Result: ', result);
		if (result == 1)
		{
			window.location.reload(); 
		}
		else
		{
			alert('Active status not changed');
		}
	});
}

async function sendData(formData) 
{
	// const phpUrl = 'http://localhost/icdev/bulletin/move_bulletins_modal.php';
	const phpUrl = 'http://192.168.10.239/move/bulletin/move_bulletins_modal.php';
	const response = await fetch(phpUrl, { method: "POST", body: JSON.stringify(formData), headers: {"Content-type": "application/json; charset=UTF-8"} });
	const result = await response.text();
	//console.log('Result: ', result);
	return result;
}
</script>
<?php
ora_close($cursor);
?>
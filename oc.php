<?php
// Display all errors
error_reporting(E_ALL);

// Display errors on the screen
ini_set('display_errors', 1);

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

if (isset($_GET['stage']))
{
	$stage = $_GET['stage'];

	// Add checked reasons to vehicle_oc_reasons
	if ($stage == 2)
	{
		print_r($_POST);
		foreach ($_POST as $key => $value) 
		{
			if (strpos($key, 'cb_') === 0) 
			{
				$reason_id = substr($key, 3) * 1;
				$reason_desc = $_POST[$key];
				echo "Checkbox with ID $reason_id is checked and " . $_POST[$key] . "<br>";
				// $sql = "INSERT INTO vehicle_oc_reasons VALUES (:entry_serial, :reason_desc, :reason_id)";

				// $stid = oci_parse($conn, $sql);
				// oci_bind_by_name($stid, ':entry_serial', $entry_serial);
				// oci_bind_by_name($stid, ':reason_id', $reason_id);
				// oci_bind_by_name($stid, ':reason_desc', $reason_desc);
				// $result = oci_execute($stid);
				// oci_free_statement($stid);
			}
		}
		// oci_close($conn);
		// echo '<script>window.location.href = "oc.php?stage=0";</script>';
	}
	else if ($stage == 31)
	{
		$conn = oci_conn();
		// $cursor = oci_open($conn);

		$reason = TRIM($_POST['reason_desc']);

		if ($reason != '')
		{
			// $sql = "INSERT INTO oc_reasons (reason_desc) VALUES (:reason_desc)";
			$sql = "INSERT INTO oc_reasons (reason_desc) VALUES ('$reason')";
			
			$cursor = oci_parse($conn, $sql);

			oci_execute($cursor);

			oci_close($conn);
			
			echo "<script> window.location='oc.php?stage=30'; </script>";
			exit;
		}
	} 
	else if($stage == 33)
	{
		$conn = oci_conn();
		
		$id = $_POST['reason_id'];
		$reason = TRIM($_POST['reason_desc']);

		$sql = "UPDATE oc_reasons SET reason_desc = :reason_desc WHERE id = :id";

		$stid = oci_parse($conn, $sql);
		oci_bind_by_name($stid, ':reason_desc', $reason);
		oci_bind_by_name($stid, ':id', $id);
		$result = oci_execute($stid);
		oci_free_statement($stid);
		oci_close($conn);

		echo '<script>window.location.href = "oc.php?stage=30";</script>';
	}
	else if($stage == 34)
	{
		$id = $_GET['id'];

		$conn = oci_conn();

		$sql = "DELETE FROM oc_reasons WHERE id = " . $id;

		$cursor = oci_parse($conn, $sql);
		oci_execute($cursor);

		oci_close($conn);

		echo '<script>window.location.href = "oc.php?stage=31";</script>';
	}
}
?>
<html>
	<head><link type="text/css" rel="stylesheet" href="style.css"><title>OC Vehicles </title><head>
	<body>
 <div id="topmenu" class="menu"><table class="menu"><tr bgcolor=white><td bgcolor=white width=300><font size=-1><b>Quintin (<a target='_blank' href="changedepot.phtml">PTA</a>) (<a href="changedepot.phtml">INTERCAPE MAINL...</a>)</b></font></td><td width=100 align='center'><font size=-1><a href="index.phtml">MOVE Menu</a></font></td><td width=110 align='center'><font size=-1><a href="manageindex.phtml">Management</a></font></td><td bgcolor=yellow width=100 align='center'><a href="checkjobcard.phtml?stage=0&recent=1"><font size=-1><b>Find Job Card</font></a></td><td width=100 bgcolor=lightblue align='center'><A target='_top' href='https://secure.intercape.co.za/ignite/index.php?c=overtime_staff&m=vdash_overtime_preapprove&page_id=1561'>OVERTIME</a></td><td width=100><a href="requestorder.phtml?stage=1&filter=2&depot=PTA"><font size=-1>View Orders</font></a></td><td width=100 align='center'><a target=_new href="/parcel/index.phtml"><font size=-1>Parcels</font></a></td><td width=100 align='center'><a href="move_invoice_payandbatch.phtml"><font size=-1>Creditors</font></a></td></tr> </table></div><br>

 <div class="choices">
	<table class="choices">
		<tr bgcolor=white>
			<td width=200><a href="oc.php?stage=0">Currently OC Vehicles</a></td>
			<td width=200><a href="oc.php?stage=1">Flag vehicle as OC</a></td>
			<td width=140><a href="oc.php?stage=20">History</a></td>
			<td width=200><a href="oc.php?stage=30">Manage OC Reasons</a></td>
		</tr>
	</table>
</div>
<br />
 <?php
 if (isset($_GET['stage']))
 {
 	$stage = $_GET['stage'];
	
	if ($stage < 30)
	{
		?>
		
	    <br>
		<form method=post action=oc.php?stage=2><input type=hidden name=stage value=2>Vehicle: <select name=vehicle><option value='4127'>0003</option><option value='4182'>0010</option><option value='4286'>0017</option><option value='4352'>0018</option><option value='4353'>0019</option><option value='4384'>0021</option><option value='4358'>0021A</option><option value='4359'>0021B</option><option value='4385'>0022</option><option value='4389'>0022A</option><option value='4390'>0022B</option><option value='4386'>0023</option><option value='4391'>0023A</option><option value='4392'>0023B</option><option value='4387'>0024</option><option value='4393'>0024A</option><option value='4394'>0024B</option><option value='4388'>0025</option><option value='4395'>0025A</option><option value='4396'>0025B</option><option value='4415'>0026</option><option value='4405'>0026A</option><option value='4406'>0026B</option><option value='4416'>0027</option><option value='4407'>0027A</option><option value='4408'>0027B</option><option value='4417'>0028</option><option value='4409'>0028A</option><option value='4410'>0028B</option><option value='4418'>0029</option><option value='4411'>0029A</option><option value='4412'>0029B</option><option value='4420'>0030</option><option value='4414'>0030A</option><option value='4413'>0030B</option><option value='4433'>0031</option><option value='4574'>0031A</option><option value='4571'>0031B</option><option value='4579'>0032A</option><option value='4572'>0032B</option><option value='4570'>0033A</option><option value='4575'>0033B</option><option value='4577'>0034A</option><option value='4576'>0034B</option><option value='4578'>0035A</option><option value='4573'>0035B</option><option value='4651'>0036A</option><option value='4652'>0036B</option><option value='4653'>0037A</option><option value='4654'>0037B</option><option value='4655'>0038A</option><option value='4656'>0038B</option><option value='4657'>0039A</option><option value='4658'>0039B</option><option value='4659'>0040A</option><option value='4660'>0040B</option><option value='4661'>0041A</option><option value='4662'>0041B</option><option value='4663'>0042A</option><option value='4664'>0042B</option><option value='4665'>0043A</option><option value='4666'>0043B</option><option value='4667'>0044A</option><option value='4668'>0044B</option><option value='4669'>0045A</option><option value='4670'>0045B</option><option value='4287'>1301</option><option value='4398'>BK029</option><option value='4381'>BK02HR</option><option value='3979'>BK044</option><option value='3221'>BK046</option><option value='4383'>BK048</option><option value='3982'>BK05SP</option><option value='4402'>BK077</option><option value='3760'>BK12JG</option><option value='4355'>BK146</option><option value='4510'>BK239W</option><option value='4375'>BK287</option><option value='3573'>BK299</option><option value='3948'>BK29WP</option><option value='4054'>BK300</option><option value='3789'>BK30CW</option><option value='4376'>BK348</option><option value='4544'>BK407</option><option value='3187'>BK439</option><option value='3959'>BK43FX</option><option value='4027'>BK444</option><option value='4672'>BK503</option><option value='3957'>BK52ZV</option><option value='4378'>BK553</option><option value='4442'>BK566</option><option value='4053'>BK675</option><option value='3901'>BK719</option><option value='4397'>BK735</option><option value='3035'>BK777</option><option value='3958'>BK787</option><option value='4671'>BK864</option><option value='4379'>BK961</option><option value='1257'>BT1001</option><option value='3178'>BT1001NW</option><option value='3171'>BT1002</option><option value='3145'>BT1002NW</option><option value='3705'>BT1003</option><option value='3143'>BT1003NW</option><option value='4107'>BT1004</option><option value='3406'>BT1004NW</option><option value='4102'>BT1005</option><option value='4104'>BT1006</option><option value='4345'>BT1007</option><option value='4346'>BT1008</option><option value='4347'>BT1009</option><option value='4362'>BT1010</option><option value='4432'>BT1012</option><option value='4434'>BT1013</option><option value='4437'>BT1014</option><option value='4497'>BT1015</option><option value='4498'>BT1016</option><option value='4499'>BT1017</option><option value='4500'>BT1018</option><option value='4523'>BT1019</option><option value='4528'>BT1020</option><option value='4529'>BT1021</option><option value='4609'>BT1050</option><option value='4608'>BT1050NW</option><option value='4618'>BT1051</option><option value='4619'>BT1052</option><option value='4620'>BT1053</option><option value='1701'>BT2001</option><option value='1407'>BT2001NA</option><option value='1309'>BT2002</option><option value='1516'>BT2002NA</option><option value='1600'>BT2003</option><option value='1549'>BT2003NA</option><option value='3605'>BT2004</option><option value='1534'>BT2004NA</option><option value='1831'>BT2005</option><option value='1000'>BT2005NA</option><option value='1254'>BT2006</option><option value='3785'>BT2006NA</option><option value='1371'>BT2007</option><option value='3784'>BT2007NA</option><option value='1745'>BT2008</option><option value='1161'>BT2009</option><option value='3608'>BT2010</option><option value='1765'>BT2011</option><option value='3153'>BT2012</option><option value='3667'>BT2013</option><option value='3637'>BT2014</option><option value='3647'>BT2015</option><option value='3662'>BT2016</option><option value='3663'>BT2017</option><option value='3648'>BT2018</option><option value='3696'>BT2019</option><option value='3740'>BT2020</option><option value='3699'>BT2021</option><option value='3744'>BT2022</option><option value='3722'>BT2023</option><option value='3738'>BT2024</option><option value='3741'>BT2025</option><option value='3720'>BT2026</option><option value='3719'>BT2027</option><option value='3697'>BT2028</option><option value='3692'>BT2029</option><option value='3693'>BT2030</option><option value='3694'>BT2031</option><option value='3695'>BT2032</option><option value='3743'>BT2033</option><option value='3770'>BT2034</option><option value='3765'>BT2035</option><option value='3766'>BT2036</option><option value='3767'>BT2037</option><option value='3768'>BT2038</option><option value='3769'>BT2039</option><option value='3852'>BT2040</option><option value='3853'>BT2041</option><option value='3844'>BT2042</option><option value='3841'>BT2043</option><option value='3790'>BT2044</option><option value='3791'>BT2045</option><option value='3845'>BT2046</option><option value='3849'>BT2047</option><option value='3847'>BT2048</option><option value='3850'>BT2049</option><option value='3846'>BT2050</option><option value='3611'>BT2051</option><option value='3659'>BT2052</option><option value='3661'>BT2053</option><option value='3704'>BT2054</option><option value='3742'>BT2055</option><option value='1645'>BT2056</option><option value='3412'>BT2057</option><option value='3062'>BT3001</option><option value='809'>BT3002</option><option value='3089'>BT3003</option><option value='1064'>BT3004</option><option value='1410'>BT3005</option><option value='1059'>BT3006</option><option value='3413'>BT3007</option><option value='3414'>BT3008</option><option value='3415'>BT3009</option><option value='3418'>BT3010</option><option value='3739'>BT3011</option><option value='3843'>BT3012</option><option value='3848'>BT3013</option><option value='3834'>BT3014</option><option value='3842'>BT3015</option><option value='3854'>BT3016</option><option value='4251'>BT3017</option><option value='4249'>BT3018</option><option value='4250'>BT3019</option><option value='4252'>BT3020</option><option value='4611'>BT3021</option><option value='1058'>BT3022</option><option value='1994'>BT3023</option><option value='3607'>BT3024</option><option value='4612'>BT3025</option><option value='4613'>BT3026</option><option value='4614'>BT3027</option><option value='4615'>BT3028</option><option value='4616'>BT3029</option><option value='4617'>BT3030</option><option value='4075'>BT4007</option><option value='4076'>BT4008</option><option value='4090'>BT4009</option><option value='4091'>BT4010</option><option value='4092'>BT4011</option><option value='4093'>BT4012</option><option value='4094'>BT4013</option><option value='4095'>BT4014</option><option value='4096'>BT4015</option><option value='4098'>BT4017</option><option value='4099'>BT4018</option><option value='4100'>BT4019</option><option value='4101'>BT4020</option><option value='4044'>BT4021</option><option value='4334'>BTH01</option><option value='4335'>BTH02</option><option value='4336'>BTH03</option><option value='4337'>BTH04</option><option value='4338'>BTH05</option><option value='4339'>BTH06</option><option value='4340'>BTH07</option><option value='4341'>BTH08</option><option value='4342'>BTH09</option><option value='4343'>BTH10</option><option value='4344'>BTH11</option><option value='4333'>CO001</option><option value='4530'>CO001NA</option><option value='4373'>CO002</option><option value='4545'>CO002NA</option><option value='4374'>CO003</option><option value='4491'>CO004</option><option value='4492'>CO005</option><option value='4493'>CO006</option><option value='4494'>CO007</option><option value='4495'>CO008</option><option value='4496'>CO009</option><option value='4509'>CO010</option><option value='4527'>CO011</option><option value='4548'>CO012</option><option value='4559'>CO013</option><option value='4621'>CO014</option><option value='4683'>CO015</option><option value='4403'>CR020</option><option value='4354'>CR030</option><option value='4547'>CR086</option><option value='3983'>CR09JH</option><option value='4399'>CR138</option><option value='4028'>CR150</option><option value='4674'>CR15NG</option><option value='4675'>CR173</option><option value='4419'>CR211</option><option value='4040'>CR21GJ</option><option value='4507'>CR240W</option><option value='4508'>CR241W</option><option value='4428'>CR270</option><option value='4676'>CR29DK</option><option value='4029'>CR316</option><option value='3980'>CR372</option><option value='4430'>CR419</option><option value='4426'>CR434</option><option value='4039'>CR455</option><option value='4348'>CR51XM</option><option value='4679'>CR54GV</option><option value='4350'>CR552</option><option value='4680'>CR56WD</option><option value='4427'>CR587</option><option value='3915'>CR59FV</option><option value='3914'>CR610</option><option value='4440'>CR612</option><option value='4349'>CR617</option><option value='3866'>CR624</option><option value='4424'>CR663</option><option value='4422'>CR664</option><option value='4032'>CR672</option><option value='4332'>CR681</option><option value='4068'>CR714</option><option value='3863'>CR722</option><option value='4035'>CR774</option><option value='4429'>CR775</option><option value='4546'>CR78LP</option><option value='3855'>CR797</option><option value='4423'>CR827</option><option value='4404'>CR873</option><option value='4677'>CR905</option><option value='4524'>CR953</option><option value='3867'>CR957</option><option value='4400'>CR976</option><option value='4351'>CR979</option><option value='4380'>CRTD01</option><option value='3619'>CV034</option><option value='3405'>DD131</option><option value='3759'>DD213</option><option value='3771'>DD214</option><option value='3774'>DD217</option><option value='3822'>DD219</option><option value='3823'>DD220</option><option value='3858'>DD225</option><option value='3857'>DD226</option><option value='4599'>DD231</option><option value='4561'>DD232</option><option value='4597'>DD233</option><option value='4560'>DD234</option><option value='4610'>DD235</option><option value='4627'>DD236</option><option value='4626'>DD237</option><option value='4648'>DD238</option><option value='4649'>DD239</option><option value='4549'>DD250</option><option value='4550'>DD251</option><option value='4554'>DD252</option><option value='4555'>DD253</option><option value='4556'>DD254</option><option value='4557'>DD255</option><option value='4684'>DD260</option><option value='3985'>DD901</option><option value='3986'>DD902</option><option value='3987'>DD903</option><option value='3988'>DD904</option><option value='3989'>DD905</option><option value='3990'>DD906</option><option value='3991'>DD907</option><option value='3992'>DD908</option><option value='3993'>DD909</option><option value='3994'>DD910</option><option value='3995'>DD911</option><option value='3996'>DD912</option><option value='3997'>DD913</option><option value='3998'>DD914</option><option value='3999'>DD915</option><option value='4000'>DD916</option><option value='4001'>DD917</option><option value='4002'>DD918</option><option value='4003'>DD919</option><option value='4004'>DD920</option><option value='4005'>DD921</option><option value='4551'>DD922</option><option value='4552'>DD923</option><option value='4553'>DD924</option><option value='4588'>DD925</option><option value='4589'>DD926</option><option value='4590'>DD927</option><option value='4591'>DD928</option><option value='4592'>DD929</option><option value='4593'>DD930</option><option value='4594'>DD931</option><option value='4192'>FT01</option><option value='4558'>FT02</option><option value='3547'>GEN001</option><option value='3556'>GEN002</option><option value='4067'>GEN003</option><option value='4088'>GEN004</option><option value='4114'>GEN005</option><option value='4431'>GEN006</option><option value='4441'>GEN007</option><option value='4512'>GEN008</option><option value='4521'>GEN009</option><option value='4681'>GEN010</option><option value='3114'>H001</option><option value='3875'>H003</option><option value='3876'>H005</option><option value='4055'>H006</option><option value='3757'>H007</option><option value='3813'>H008</option><option value='3814'>H009</option><option value='4522'>H010</option><option value='4436'>LT001</option><option value='4223'>MRFERREIRA</option><option value='3978'>QU008</option><option value='3977'>QU204</option><option value='4042'>QU251NA</option><option value='4041'>QU252NA</option><option value='3576'>QU732</option><option value='3578'>QU748</option><option value='4030'>QU770</option><option value='3976'>QU892</option><option value='4297'>SD049</option><option value='4294'>SD050</option><option value='4295'>SD051</option><option value='4296'>SD052</option><option value='4298'>SD054</option><option value='4299'>SD055</option><option value='4300'>SD056</option><option value='4301'>SD057</option><option value='4302'>SD058</option><option value='4457'>SD060</option><option value='4458'>SD061</option><option value='3730'>SD067</option><option value='3917'>SD071</option><option value='3918'>SD072</option><option value='3919'>SD073</option><option value='3920'>SD074</option><option value='3921'>SD075</option><option value='3922'>SD076</option><option value='3924'>SD078</option><option value='3925'>SD079</option><option value='3950'>SD087</option><option value='3946'>SD089</option><option value='3947'>SD090</option><option value='3951'>SD091</option><option value='3952'>SD092</option><option value='3954'>SD094</option><option value='3955'>SD095</option><option value='3956'>SD096</option><option value='4466'>SD098</option><option value='4467'>SD099</option><option value='4525'>SD100</option><option value='4628'>SD101</option><option value='4647'>SD102</option><option value='4650'>SD103</option><option value='4678'>SD104</option><option value='4682'>SD105</option><option value='4685'>SD106</option><option value='4673'>SD150</option><option value='4317'>SD155</option><option value='4319'>SD156</option><option value='4311'>SD180</option><option value='4312'>SD181</option><option value='4321'>SD185</option><option value='4320'>SD186</option><option value='3815'>SD280</option><option value='3614'>SD300</option><option value='3618'>SD302</option><option value='3631'>SD303</option><option value='3638'>SD304</option><option value='4289'>SD310</option><option value='4290'>SD311</option><option value='4291'>SD312</option><option value='4292'>SD313</option><option value='4293'>SD314</option><option value='4303'>SD340</option><option value='4304'>SD341</option><option value='3710'>SD348</option><option value='3711'>SD349</option><option value='4322'>SD351</option><option value='4323'>SD352</option><option value='4324'>SD353</option><option value='4325'>SD354</option><option value='4326'>SD355</option><option value='4472'>SD360</option><option value='4448'>SD500</option><option value='4455'>SD501</option><option value='4449'>SD502</option><option value='4456'>SD550</option><option value='4450'>SD551</option><option value='4483'>SD552</option><option value='4485'>SD553</option><option value='4486'>SD554</option><option value='4487'>SD555</option><option value='4488'>SD556</option><option value='4489'>SD557</option><option value='4511'>SD558</option><option value='4514'>SD559</option><option value='4515'>SD560</option><option value='4490'>SD561</option><option value='3961'>SD601</option><option value='3962'>SD602</option><option value='3963'>SD603</option><option value='3964'>SD604</option><option value='3965'>SD605</option><option value='3966'>SD606</option><option value='3967'>SD607</option><option value='3968'>SD608</option><option value='3969'>SD609</option><option value='3970'>SD610</option><option value='3971'>SD611</option><option value='3972'>SD612</option><option value='3973'>SD614</option><option value='3974'>SD615</option><option value='3975'>SD616</option><option value='4454'>SD617</option><option value='4451'>SD618</option><option value='4452'>SD619</option><option value='4630'>SD650</option><option value='4368'>SD651</option><option value='4276'>SD700</option><option value='4006'>SD701</option><option value='4007'>SD702</option><option value='4008'>SD703</option><option value='4009'>SD704</option><option value='4010'>SD705</option><option value='4011'>SD706</option><option value='4013'>SD707</option><option value='4012'>SD708</option><option value='4014'>SD709</option><option value='4016'>SD711</option><option value='4017'>SD712</option><option value='4018'>SD713</option><option value='4043'>SD714</option><option value='4045'>SD715</option><option value='4046'>SD716</option><option value='4369'>SD717</option><option value='4370'>SD718</option><option value='4371'>SD719</option><option value='4587'>SD720</option><option value='4562'>SD721</option><option value='4582'>SD722</option><option value='4583'>SD723</option><option value='4584'>SD724</option><option value='4585'>SD725</option><option value='4586'>SD726</option><option value='4595'>SD727</option><option value='4600'>SD728</option><option value='4601'>SD729</option><option value='4602'>SD730</option><option value='4603'>SD731</option><option value='4604'>SD732</option><option value='4607'>SD733</option><option value='3984'>SD801</option><option value='4048'>SD802</option><option value='4049'>SD803</option><option value='4050'>SD804</option><option value='4051'>SD805</option><option value='4444'>SD806</option><option value='4464'>SD807</option><option value='4364'>SD808</option><option value='4365'>SD809</option><option value='4366'>SD810</option><option value='4631'>SD8100</option><option value='4056'>SD8101</option><option value='4057'>SD8102</option><option value='4058'>SD8103</option><option value='4059'>SD8104</option><option value='4060'>SD8105</option><option value='4061'>SD8106</option><option value='4062'>SD8107</option><option value='4063'>SD8108</option><option value='4064'>SD8109</option><option value='4367'>SD811</option><option value='4065'>SD8110</option><option value='4066'>SD8111</option><option value='4077'>SD8112</option><option value='4078'>SD8113</option><option value='4079'>SD8114</option><option value='4080'>SD8115</option><option value='4081'>SD8116</option><option value='4083'>SD8118</option><option value='4084'>SD8119</option><option value='4565'>SD812</option><option value='4085'>SD8120</option><option value='4580'>SD8121</option><option value='4581'>SD8122</option><option value='4563'>SD8123</option><option value='4566'>SD813</option><option value='4568'>SD814</option><option value='4569'>SD815</option><option value='4634'>SD840</option><option value='4635'>SD841</option><option value='4598'>SD850</option><option value='4445'>SD851</option><option value='4460'>SD852</option><option value='4447'>SD853</option><option value='4461'>SD854</option><option value='4446'>SD855</option><option value='4462'>SD856</option><option value='4443'>SD857</option><option value='4463'>SD858</option><option value='4636'>SD859</option><option value='4637'>SD860</option><option value='4638'>SD861</option><option value='4639'>SD862</option><option value='4640'>SD863</option><option value='4641'>SD864</option><option value='4642'>SD865</option><option value='4644'>SD866</option><option value='4645'>SD867</option><option value='4646'>SD868</option><option value='4272'>SP1001</option><option value='4273'>SP1002</option><option value='4221'>SPRAY</option><option value='3828'>TEST1</option><option value='3132'>TR001</option><option value='3135'>TR002</option><option value='3749'>TR004</option><option value='3796'>TR005</option><option value='4103'>TR006</option><option value='3750'>TRA16LY</option><option value='3139'>TRA217</option><option value='3146'>TRA416</option><option value='3168'>TRA581</option><option value='3082'>TRA716</option><option value='4382'>TRA851</option><option value='3144'>TRA979</option><option value='4222'>WORKSHOP</option></select> Depot: <select name=depot><option>BDB</option>
		<option>BLM</option>
		<option>CA</option>
		<option>CAT</option>
		<option>CBS</option>
		<option>CCS</option>
		<option>DBN</option>
		<option>DBT</option>
		<option>DFL</option>
		<option>ESL</option>
		<option>JHB</option>
		<option>MAP</option>
		<option>MTH</option>
		<option>OBP</option>
		<option>OBU</option>
		<option>OPD</option>
		<option>PBU</option>
		<option>PCS</option>
		<option>PE</option>
		<option>PET</option>
		<option>PFL</option>
		<option>PIM</option>
		<option SELECTED>PTA</option>
		<option>PTT</option>
		<option>QTN</option>
		<option>UPT</option>
		<option>UTT</option>
		<option>WHK</option>
		</select><br>Flag as OC from <input name=startd size=8 value='20240905' maxlength=8> until <input name=last size=8 value='20240905' maxlength=8> at <select name=time><option value='1'>1am</option>
		<option value='2'>2am</option>
		<option value='3'>3am</option>
		<option value='4'>4am</option>
		<option value='5'>5am</option>
		<option value='6'>6am</option>
		<option value='7'>7am</option>
		<option value='8'>8am</option>
		<option value='9'>9am</option>
		<option value='10'>10am</option>
		<option value='11'>11am</option>
		<option value='12' SELECTED>noon</option>
		<option value='13'>1pm</option>
		<option value='14'>2pm</option>
		<option value='15'>3pm</option>
		<option value='16'>4pm</option>
		<option value='17'>5pm</option>
		<option value='18'>6pm</option>
		<option value='19'>7pm</option>
		<option value='20'>8pm</option>
		<option value='21'>9pm</option>
		<option value='22'>10pm</option>
		<option value='23'>11pm</option>
		</select><br>Reason/Notes: <input name=notes size=80 naxlength=100><br><br>

		<table border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td colspan="4">Select reason(s)</td>
			</tr>
		<?php
			$conn = oci_conn();

			$sql = "SELECT id, reason_desc FROM oc_reasons ORDER BY id ASC";

			$cursor = oci_parse($conn, $sql);
			oci_execute($cursor);

			$numrecs = 0;
			$column_count = 0; 

			while ($row = oci_fetch_assoc($cursor)) 
			{
				$id = $row['ID'];
				$reason = $row['REASON_DESC'];

				// $bg_color = ($row_count % 2 == 0) ? '#f5f5f5' : '#dcdcdc';

				if ($column_count % 4 == 0) {
					echo "<tr>";
				}

				echo '<td width=200>';
				echo '<input name="cb_' . $id . '" id="cb_' . $id . '" type="checkbox" value="' . $reason . '"><label for="cb_' . $id . '">' . $reason . '</label>';
				echo '</td>';

				if ($column_count % 4 == 3) {
					echo "</tr>";
					//$row_count++; // Increment the row count only after closing a row
				}
				
				$numrecs++;
				$column_count++;
			}

			if ($column_count % 4 != 0) {
				echo '<td></td></tr>'; // Add an empty cell if the last row has only one column
			}
			oci_close($conn);
			?>
	</table>
		<Br><input type=submit value='Flag as Out Of Commission'></form>
		<?php
	}
	else 
	{
		if (!isset($_GET['id']))
		{
			echo '<form action="oc.php?stage=31" method="post">';
				echo '<table>';
					echo '<tr>';
						echo '<td colspan="4"><b>Manage OC Reasons</b></td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td style="padding-top: 10px;">Add new reason</td>';
						echo '<td style="padding-top: 10px;">';
							echo '<input name="reason_desc" type="text" value="" style="width: 400px; height: 28px" placeholder="Enter reason description">';
						echo '</td>';
						echo '<td style="padding-top: 10px;"><input type="submit" value="Add Reason" style="height: 28px"></td>';
						echo '<td>&nbsp;</td>';
					echo '</tr>';
				echo '</table>';
			echo '</form>';
		} 
		else 
		{
			if ($stage == 32)
			{
				$id = $_GET['id'];
				$reason = urldecode($_GET['r']);

				echo '<form action="oc.php?stage=33" method="post">';
					echo '<table>';
						echo '<tr>';
							echo '<td colspan="3"><b>Manage OC Reasons</b></td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td style="padding-top: 10px;">Edit reason</td>';
							echo '<td style="padding-top: 10px; padding-bottom: 5px">';
								echo '<input name="reason_id" type="hidden" value="' . $id . '">';
								echo '<input name="reason_desc" type="text" value="' . $reason . '" style="width: 400px; height: 28px">';
							echo '</td>';
							echo '<td style="padding-top: 10px; padding-bottom: 5px"><input type="submit" value="Update" style="height: 28px"></td>';
							echo '<td>&nbsp;</td>';
						echo '</tr>';
					echo '</table>';
				echo '</form>';
			} 
			else 
			{
				$id = $_GET['id'];
				$reason = urldecode($_GET['r']);

				echo '<table>';
					echo '<tr>';
						echo '<td colspan="3"><b>Manage OC Reasons</b></td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td style="padding-top: 10px;">Are you sure you want to delete this reason?</td>';
						echo '<td style="padding-top: 10px; padding-bottom: 5px">';
							echo '<input name="reason_id" type="hidden" value="' . $id . '">';
							echo '<b>' . $reason . '</b>';
						echo '</td>';
						echo '<td>&nbsp;</td>';
					echo '<td>';
						echo '<a href="oc.php?stage=34&id=' . $id . '" style="color: black; text-decoration: none"><div style="background: #efefef; border: 1px solid #767676; padding: 2px 10px;">Yes</div></a>';
					echo '</td>';
					echo '<td>';
							echo '<a href="oc.php?stage=30" style="color: black; text-decoration: none"><div style="background: #efefef; border: 1px solid #767676; padding: 2px 10px;">No</div></a>';
					echo '</td>';
					echo '</tr>';
				echo '</table>';
			}
			
		}
		?>
		<table cellpadding='3' cellspacing='1' width='100%' bgcolor='black' border="0">
			
			<tr bgcolor="#E6E6FA">
				<td width=100%>Reason</td>
				<td>Edit</td>
				<td>Delete</td>
			</tr>
			<?php
			$conn = oci_conn();

			$sql = "SELECT id, reason_desc FROM oc_reasons ORDER BY id ASC";

			$cursor = oci_parse($conn, $sql);
			oci_execute($cursor);

			$row_count = 0;

			while ($row = oci_fetch_assoc($cursor)) 
			{
				$id = $row['ID'];
				$reason = $row['REASON_DESC'];

				$bg_color = ($row_count % 2 == 0) ? '#f5f5f5' : '#dcdcdc';

				echo "<tr bgcolor='" . $bg_color . "'>";
					echo "<td>" . $reason . "</td>";
					echo "<td><a href='oc.php?stage=32&id=" . $id . "&r=" . urlencode($reason) . "' style='color: black; text-decoration: none'><div style='background: #efefef; border: 1px solid #767676; padding: 2px 10px;'>Edit</div></a></td>";
					echo "<td><a href='oc.php?stage=33&id=" . $id . "&r=" . urlencode($reason) . "' style='color: black; text-decoration: none'><div style='background: #efefef; border: 1px solid #767676; padding: 2px 10px;'>Delete</div></td>";
				echo "</tr>";

				$row_count++;
			}

			oci_close($conn);
			?>
		</table>
		<?php
	}
 }
?>
		
	</body>
</html>

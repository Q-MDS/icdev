<?php
$last_updated = array("2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20", "2024-11-20");
$depot = array("BLM", "CA", "CBS", "DBN", "GAP", "MAP", "MTH", "PE", "PTA", "UPT", "WHK");
$active_drivers = array(45, 218, 17, 92, 4, 14, 6, 35, 230, 11, 24, 696);
$training = array(0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 0, 3);
$old_contracts = array(0, 7, 0, 9, 0, 0, 0, 0, 10, 0, 0, 26);
$completed_training = array(0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4);
$dismissed = array(0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 2);
$resigned = array(0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 2);

$tot_able_to_schedule = array();
for ($i = 0; $i < count($active_drivers); $i++) 
{
    $tot_able_to_schedule[$i] = $active_drivers[$i] + $training[$i] + $old_contracts[$i] + $completed_training[$i] + $dismissed[$i] + $resigned[$i];
}

$not_scheduled_in_72_hours = array(1, 9, 0, 2, 0, 0, 0, 4, 9, 0, 2, 27);
$min_drivers_needed = array(34, 227, 25, 112, 3, 15, 4, 42, 222, 9, 27, 720);

$min_drivers_5 = array();
$min_drivers_10 = array();
$min_drivers_15 = array();

foreach ($min_drivers_needed as $value) {
    $min_drivers_5[] = $value * 1.05;
}
$min_drivers_10 = array();
foreach ($min_drivers_needed as $value) {
    $min_drivers_10[] = $value * 1.10;
}
foreach ($min_drivers_needed as $value) {
    $min_drivers_15[] = $value * 1.15;
}

$total_trips = array(362, 1575, 174, 848, 58, 167, 0, 322, 2114, 62, 239);
$class_training = array(2, 20, 0, 10, 0, 0, 0, 0, 12, 0, 0, 5);
$k53 = array(0, 10, 0, 0, 0, 0, 0, 10, 3, 0, 5);
$interview = array(3, 10, -8, -1, 1, -1, 2, -27, 28, 2, -3);
$cvs = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Drift: Dashboard</title>
	<style>
        /* Reset some default browser styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Ensure html and body take up the full viewport height */
        html, body {
            height: 100%;
            width: 100%;
            font-family: Arial, sans-serif;
			font-size: 14px;
        }

        /* Full size container */
        .full-size-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            width: 100%;
			padding: 20px;
            background-color: #EC7A31;
        }

		/* Content container */
		.content_container {
			position: relative;
			display: flex; 
			flex-direction: column; 
			align-items: top; 
			justify-content: flex-start; 
			width: 100%; 
			height: 100%; 
			padding: 30px;
			background-color: white; 
			border: 1px solid #000000;
			border-radius: 20px;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3), 0 -4px 12px rgba(0, 0, 0, 0.1),  4px 0 12px rgba(0, 0, 0, 0.3),  -4px 0 12px rgba(0, 0, 0, 0.1); 
		}

		/* Title */
		.title {
			position: absolute; 
			top: -13px;
			right: 15px;
			background-color: #EC7A31; 
			padding: 2px 10px; 
			font-size: 1.5rem; 
			border: 1px solid #000000; 
			color: white; 
			border-radius: 5px;
			box-shadow: 0 4px 4px rgba(0, 0, 0, 0.2);
		}

		/* Scrollbars */
		.content {
			padding-right: 10px;
		}
		.content::-webkit-scrollbar {
            width: 8px; /* Width of the vertical scrollbar */
            height: 8px; /* Height of the horizontal scrollbar */
        }

        .content::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.3); /* Color of the scrollbar thumb */
            border-radius: 10px; /* Roundness of the scrollbar thumb */
        }

        .content::-webkit-scrollbar-track {
            background-color: rgba(0, 0, 0, 0.1); /* Color of the scrollbar track */
        }

		.content .row_title {
			background-color: #0000000A;
		}

		.content .row {
			background-color: #FFFFFF;
		}

		.content .bt {
			border-top: 1px solid #d9d9d9; 
		}

		.content .bt2 {
			border-top: 2px solid #c9c9c9; 
		}

		.content .bb {
			border-bottom: 1px solid #d9d9d9; 
		}
		
		.content .bl {
			border-left: 1px solid #d9d9d9; 
		}

		.content .br {
			border-right: 1px solid #d9d9d9; 
		}

		.content .heading {
			font-weight: bold;
			padding-left: 5px;
			border-right: 1px solid #d9d9d9;
			text-align: left;
		}

		.content .cell_title {
			padding-left: 5px;
			border-right: 1px solid #d9d9d9;
			text-align: left;
		}

		.content .cell {
			padding-right: 5px;
			border-right: 1px solid #d9d9d9;
			text-align: right;
		}

		.content .red {
			background-color: #FFA9A98C;
			border-top: 1px solid #a9a9a9;
			border-right: 1px solid #a9a9a9;
		}

		.content .green {
			background-color: #A0D09D8C;
			border-top: 1px solid #a9a9a9;
			border-right: 1px solid #a9a9a9;
		}

    </style>
</head>
<body>
	<div class="full-size-container">
        <div class="content_container">

			<div class="title"><pre>Drift Dashboard</pre></div>

			<div style="display: flex; flex-direction: row; align-items: center; column-gap: 10px;">
				<div style="">Select date</div>
				<?php
				$back = 24;
				$forward = 12;
				$now = date('Y-m-01');
				
				echo '<div>'; 
					echo '<select name="month" id="month" style="padding: 5px; border-radius: 5px;">';
					for ($i = -$back; $i <= $forward; $i++) 
					{
						$date = date('Y-m-01', strtotime("$i months"));
						if ($date == $now)
						{
							echo '<option value="' . $date . '" selected>' . $date . '</option>';
						}
						else
						{
							echo '<option value="' . $date . '">' . $date . '</option>';
						}
					}
					echo '</select>';
				echo '</div>';
				?>
				<div style="flex: 1">&nbsp;</div>
			</div>

			<div class="content" style="display: grid; grid-template-columns: repeat(13, auto); width: 100%; margin-top: 20px; line-height: 1.9rem; overflow: hidden; overflow-y: auto">
				
				<!-- Last update -->
				<div class="row_title bt bl heading">Last Updated:</div>
				<?php
				foreach ($last_updated as $date)
				{
					echo '<div class="row_title bt cell_title">' . $date . '</div>';
				}
				?>
				<div class="row_title bt br cell">&nbsp;</div>

				<!-- Depot: TC -->
				<div class="row_title bt bl heading">Depot</div>
				<?php
				foreach ($depot as $d)
				{
					echo '<div class="row_title bt cell_title">' . $d . '</div>';
				}
				?>
				<div class="row_title bt br cell">TOTAL</div>

				<!-- Active drivers -->
				<div class="row bt bl heading">Active Drivers</div>
				<?php
				foreach ($active_drivers as $ad)
				{
					echo '<div class="row bt cell">' . $ad . '</div>';
				}
				?>
				
				<!-- Training -->
				<div class="row bt bl heading">Training</div>
				<?php
				foreach ($training as $t)
				{
					echo '<div class="row bt cell">' . $t . '</div>';
				}
				?>

				<!-- Old contrcts -->
				<div class="row bt bl heading">Old Contracts</div>
				<?php
				foreach ($old_contracts as $oc)
				{
					echo '<div class="row bt cell">' . $oc . '</div>';
				}
				?>

				<!-- Completed Training/Passed -->
				<div class="row bt bl heading">Completed Training/Passed</div>
				<?php
				foreach ($completed_training as $ct)
				{
					echo '<div class="row bt cell">' . $ct . '</div>';
				}
				?>

				<!-- Dismissed -->
				<div class="row bt bl heading">Dismissed</div>
				<?php
				foreach ($dismissed as $d)
				{
					echo '<div class="row bt cell">' . $d . '</div>';
				}
				?>

				<!-- Resigned -->
				<div class="row bt bl heading">Resigned</div>
				<?php
				foreach ($resigned as $r)
				{
					echo '<div class="row bt cell">' . $r . '</div>';
				}
				?>

				<!-- Able to schedule: TC -->
				<div class="row_title bt bl heading">Able To Schedule</div>
				<?php
				foreach ($tot_able_to_schedule as $tot)
				{
					echo '<div class="row_title bt cell">' . $tot . '</div>';
				}
				?>

				<!-- Not scheduled in 72 hours: TC -->
				<div class="row_title bt bl heading">Not scheduled in 72 hours</div>
				<?php
				foreach ($not_scheduled_in_72_hours as $ns)
				{
					echo '<div class="row_title bt cell">' . $ns . '</div>';
				}
				?>

				<!-- Minimum Drivers Needed -->
				<div class="row bt bl heading">Minimum Drivers Needed</div>
				<?php
				foreach ($min_drivers_needed as $mdn)
				{
					echo '<div class="row bt cell">' . $mdn . '</div>';
				}
				?>

				<!-- Minimum Drivers Needed - 5% -->
				<div class="row bt bl heading">Minimum Drivers Needed - 5%</div>
				<?php
				foreach ($min_drivers_5 as $mdn5)
				{
					echo '<div class="row bt cell">' . number_format($mdn5, 0) . '</div>';
				}
				?>

				<!-- Minimum Drivers Needed - 10% -->
				<div class="row bt bl heading">Minimum Drivers Needed - 10%</div>
				<?php
				foreach ($min_drivers_10 as $mdn10)
				{
					echo '<div class="row bt cell">' . number_format($mdn10, 0) . '</div>';
				}
				?>

				<!-- Minimum Drivers Needed - 15% -->
				<div class="row bt bl heading">Minimum Drivers Needed - 15%</div>
				<?php
				foreach ($min_drivers_15 as $mdn15)
				{
					echo '<div class="row bt cell">' . number_format($mdn15, 0) . '</div>';
				}
				?>

				<!-- Total Trips -->
				<div class="row_title bt bl heading">Total Trips</div>
				<?php
				$tot_total_trips = array_sum($total_trips);
				foreach ($total_trips as $tt)
				{
					echo '<div class="row_title bt cell">' . $tt . '</div>';
				}
				?>
				<div class="row_title bt br cell"><?php echo $tot_total_trips; ?></div>

				<!-- Still need - MIN -->
				<div class="row bt bl heading">Still need - MIN</div>
				<?php
				$still_need_min = array();
				$tot_still_need_min = 0;
				for ($i = 0; $i < 11; $i++)
				{
					$tot = $tot_able_to_schedule[$i] - $min_drivers_needed[$i];
					$tot_still_need_min += $tot;
					$still_need_min[] = $tot;

					if ($tot < 0)
					{
						echo '<div class="row bt cell red">' . $tot . '</div>';
					}
					else
					{
						echo '<div class="row bt cell green">' . $tot . '</div>';
					}
				}
				if ($tot_still_need_min < 0)
				{
					echo '<div class="row bt cell red">' . number_format($tot_still_need_min, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green">' . number_format($tot_still_need_min, 0) . '</div>';
				}
				?>

				<!-- Still need - 5% -->
				<div class="row bt bl heading">Still need - 5%</div>
				<?php
				$still_need_5 = array();
				$tot_still_need_5 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $tot_able_to_schedule[$i] - $min_drivers_5[$i];
					$tot_still_need_5 += $tot;
					$still_need_5[] = $tot;

					if ($tot < 0)
					{
						echo '<div class="row bt cell red">' . number_format($tot, 0) . '</div>';
					}
					else
					{
						echo '<div class="row bt cell green">' . number_format($tot, 0) . '</div>';
					}
				}
				if ($tot_still_need_5 < 0)
				{
					echo '<div class="row bt cell red">' . number_format($tot_still_need_5, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green">' . number_format($tot_still_need_5, 0) . '</div>';
				}
				?>

				<!-- Still need - 10% -->
				<div class="row bt bl heading">Still need - 10%</div>
				<?php
				$still_need_10 = array();
				$tot_still_need_10 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $tot_able_to_schedule[$i] - $min_drivers_10[$i];
					$tot_still_need_10 += $tot;
					$still_need_10[] = $tot;
					
					if ($tot < 0)
					{
						echo '<div class="row bt cell red">' . number_format($tot, 0) . '</div>';
					}
					else
					{
						echo '<div class="row bt cell green">' . number_format($tot, 0) . '</div>';
					}
				}
				if ($tot_still_need_10 < 0)
				{
					echo '<div class="row bt cell red">' . number_format($tot_still_need_10, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green">' . number_format($tot_still_need_10, 0) . '</div>';
				}
				?>

				<!-- Still need - 15% -->
				<div class="row bt bl heading">Still need - 15%</div>
				<?php
				$still_need_15 = array();
				$tot_still_need_15 = 0;
				
				for ($i = 0; $i < 11; $i++)
				{
					$tot = $tot_able_to_schedule[$i] - $min_drivers_15[$i];
					$tot_still_need_15 += $tot;
					$still_need_15[] = $tot;

					if ($tot < 0)
					{
						echo '<div class="row bt cell red">' . number_format($tot, 0) . '</div>';
					}
					else
					{
						echo '<div class="row bt cell green">' . number_format($tot, 0) . '</div>';
					}
				}
				if ($tot_still_need_15 < 0)
				{
					echo '<div class="row bt cell red">' . number_format($tot_still_need_15, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green">' . number_format($tot_still_need_15, 0) . '</div>';
				}
				?>

				<!-- Class training: TC -->
				<div class="row_title bt bl heading">Class Training</div>
				<?php
				foreach ($class_training as $ct)
				{
					echo '<div class="row_title bt cell">' . $ct . '</div>';
				}
				?>

				<!-- Status - MIN -->
				<div class="row bt bl heading">Status - MIN</div>
				<?php
				$tot_status_min = 0;
				$status_min = array();

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $still_need_min[$i] + $class_training[$i];
					
					$tot_status_min += $tot;
					$status_min[] = $tot;

					if ($tot < 0)
					{
						echo '<div class="row bt cell red">' . number_format($tot, 0) . '</div>';
					}
					else
					{
						echo '<div class="row bt cell green">' . number_format($tot, 0) . '</div>';
					}
				}
				if ($tot_status_min < 0)
				{
					echo '<div class="row bt cell red">' . number_format($tot_status_min, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green">' . number_format($tot_status_min, 0) . '</div>';
				}
				?>

				<!-- Status - 5% -->
				<div class="row bt bl heading">Status - 5%</div>
				<?php
				$status_5 = array();
				$tot_status_5 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $still_need_5[$i] + $status_min[$i];
					
					$tot_status_5 += $tot;
					$status_5[] = $tot;

					if ($tot < 0)
					{
						echo '<div class="row bt cell red">' . number_format($tot, 0) . '</div>';
					}
					else
					{
						echo '<div class="row bt cell green">' . number_format($tot, 0) . '</div>';
					}
				}
				if ($tot_status_5 < 0)
				{
					echo '<div class="row bt cell red">' . number_format($tot_status_5, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green">' . number_format($tot_status_5, 0) . '</div>';
				}
				?>

				<!-- Status - 10% -->
				<div class="row bt bl heading">Status - 10%</div>
				<?php
				$status_10 = array();
				$tot_status_10 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $still_need_10[$i] + $status_5[$i];
					
					$tot_status_10 += $tot;
					$status_10[] = $tot;

					if ($tot < 0)
					{
						echo '<div class="row bt cell red">' . number_format($tot, 0) . '</div>';
					}
					else
					{
						echo '<div class="row bt cell green">' . number_format($tot, 0) . '</div>';
					}
				}
				if ($tot_status_10 < 0)
				{
					echo '<div class="row bt cell red">' . number_format($tot_status_10, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green">' . number_format($tot_status_10, 0) . '</div>';
				}
				?>

				<!-- Status - 15% -->
				<div class="row bt bl heading">Status - 15%</div>
				<?php
				$status_15 = array();
				$tot_status_15 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $still_need_15[$i] + $status_10[$i];
					
					$tot_status_15 += $tot;
					$status_15[] = $tot;

					if ($tot < 0)
					{
						echo '<div class="row bt cell red">' . number_format($tot, 0) . '</div>';
					}
					else
					{
						echo '<div class="row bt cell green">' . number_format($tot, 0) . '</div>';
					}
				}
				if ($tot_status_15 < 0)
				{
					echo '<div class="row bt cell red">' . number_format($tot_status_15, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green">' . number_format($tot_status_15, 0) . '</div>';
				}
				?>

				<!-- K53 -->
				<div class="row bt2 bl heading">K53</div>
				<?php
				foreach ($k53 as $k)
				{
					echo '<div class="row bt2 cell">' . $k . '</div>';
				}
				?>
				<div class="row bt2 cell"><?php echo array_sum($k53); ?></div>

				<!-- Interview -->
				<div class="row bt bl heading">Interview</div>
				<?php
				foreach ($interview as $i)
				{
					echo '<div class="row bt cell">' . $i . '</div>';
				}
				?>
				<div class="row bt cell"><?php echo array_sum($interview); ?></div>

				<!-- CVs in hand -->
				<div class="row bt bl bb heading">CVs in hand</div>
				<?php
				foreach ($cvs as $c)
				{
					echo '<div class="row bt bb cell">' . $c . '</div>';
				}
				?>
				<div class="row bt bb cell"><?php echo array_sum($cvs); ?></div>
			</div>	
				

		</div>
    </div>
</body>
</html>
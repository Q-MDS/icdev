<?php
require('Dashboard_model.php');
$dashboard_model = new Dashboard_model();

/**
 * Set page date
 */
if (isset($_POST['month']))
{
	$month = $_POST['month'];
}
else
{
	$month = date('Ym01');
}

$depot_list = array("BLM", "CA", "CBS", "DBN", "GAB", "MAP", "MTH", "PE", "PTA", "UPT", "WHK");

/** 
 * Get data from depot totala 
 * */
$data = $dashboard_model->getDepotTotals($month, $depot_list);
$depot_totals = $data['depot_totals'];
$result = $data['result'];

/**
 * If not data has been captured for a depot and month (highlighted in orange on page)
 */
if (count($result) > 0)
{
	$string = implode(", ", $result);
	$manual_input_msg = "Depot totals not found for: " . $string;
} 
else 
{
	$manual_input_msg = "OK";
}

/**
 * Global data
 */
$drift_data = $dashboard_model->getDriftData($month);
$sctive_drivers_data = $dashboard_model->getActiveDriversData($depot_list);

/**
 * Line 1: Last Updated
 */
$last_updated = $drift_data['last_updated'];
// echo "XXX: " . count($get_last_updated);
if (count($last_updated) > 11)
{
	$last_updated = array_slice($last_updated, 0, 11);
}

/**
 * Line 2: Depot => Static
 */
$depot = $depot_list;

/**
 * Line 3: Active drivers
 */
$active_drivers = $dashboard_model->getActiveDriversData($depot_list);

/**
 * Line 4: Training trips => DRIFT_DEPOT_TOTALS.TRAINING_TRIPS
 */
$training = $dashboard_model->getTrainingData($depot_list);

/**
 * Old Contracts => DRIFT_DEPOT_TOTALS.OLD_CONTRACTS
 */
$old_contracts = array();
foreach ($depot_totals as $depot_total) 
{
	$old_contracts[] = $depot_total['OLD_CONTRACTS'];
}
$old_contracts[] = array_sum($old_contracts);

/**
 * Line 6: Completed training/passed => DRIFT_DEPOT_TOTALS.COMPLETED_TRAINING
 */
$completed_training = array();
foreach ($depot_totals as $depot_total) 
{
	$completed_training[] = $depot_total['COMPLETED_TRAINING'];
}
$completed_training[] = array_sum($completed_training);

/**
 * Line 7: Dismissed => DRIFT_DEPOT_TOTALS.DISMISSED
 */
$dismissed = array();
foreach ($depot_totals as $depot_total) 
{
	$dismissed[] = $depot_total['DISMISSED'];
}
$dismissed[] = array_sum($dismissed);

/**
 * Line 8: Resigned => DRIFT_DEPOT_TOTALS.RESIGNED
 */
$resigned = array();
foreach ($depot_totals as $depot_total) 
{
	$resigned[] = $depot_total['RESIGNED'];
}
$resigned[] = array_sum($resigned);

/**
 * Line 9: Able to schedule
 */
$tot_able_to_schedule = array();
for ($i = 0; $i < count($depot) + 1; $i++) 
{
	$tot_able_to_schedule[$i] = $active_drivers[$i] + $training[$i] + $old_contracts[$i] + $completed_training[$i] + $dismissed[$i] + $resigned[$i];
}

/**
 * Line 10: Not scheduled in 72 hours
 */
$dormant_drivers_data = $dashboard_model->getDormantDriversData($depot_list);

$not_scheduled_in_72_hours = $dormant_drivers_data['dormant_drivers'];
$not_scheduled_in_72_hover = $dormant_drivers_data['dormant_drivers_hover'];

/**
 * Line 11: Minimum drivers needed
 */
$min_drivers_needed = $drift_data['min_drivers_needed'];
if (count($min_drivers_needed) > 11)
{
	$min_drivers_needed = array_unique($min_drivers_needed, SORT_REGULAR);
	$min_drivers_needed = array_values($min_drivers_needed);
}

/**
 * Line 12: Minimum drivers needed - 5%
 */
$min_drivers_5 = array();
foreach ($min_drivers_needed as $value) 
{
    $min_drivers_5[] = $value * 1.05;
}

/**
 * Line 13: Minimum drivers needed - 10%
 */
$min_drivers_10 = array();
foreach ($min_drivers_needed as $value) 
{
    $min_drivers_10[] = $value * 1.10;
}

/**
 * Line 14: Minimum drivers needed - 15%
 */
$min_drivers_15 = array();
foreach ($min_drivers_needed as $value) 
{
    $min_drivers_15[] = $value * 1.15;
}

/**
 * Line 15: Total trips
 */
$total_trips = $drift_data['total_trips'];
if (count($total_trips) > 11)
{
	$total_trips = array_unique($total_trips, SORT_REGULAR);
	$total_trips = array_values($total_trips);
}

/**
 * Line 16 - 19: Data generated/calculated
 */

 /**
 * Line 20: Class Training => DRIFT_DEPOT_TOTALS.CLASS_TRAINING
 */
$class_training = array();
foreach ($depot_totals as $depot_total) 
{
	$class_training[] = $depot_total['CLASS_TRAINING'];
}
$class_training[] = array_sum($class_training);

/**
 * Line 21 - 24: Data generated/calculated
 */

 /**
 * Line 25: K53
 */
// $k53 = array(0, 10, 0, 0, 0, 0, 0, 10, 3, 0, 5);
$k53 = array();
foreach ($depot_totals as $depot_total) 
{
	$k53[] = $depot_total['K53'];
}
$k53[] = array_sum($k53);

/**
 * Line 26: Interview => DRIFT_DEPOT_TOTALS.INTERVIEW
 */
$interview = array();
foreach ($depot_totals as $depot_total) 
{
	$interview[] = $depot_total['INTERVIEW'];
}
$interview[] = array_sum($interview);

/**
 * Line 27: CVs In Hand => DRIFT_DEPOT_TOTALS.CVS
 */
$cvs = array();
foreach ($depot_totals as $depot_total) 
{
	$cvs[] = $depot_total['CVS'];
}
$cvs[] = array_sum($cvs);

function arr_last_updated()
{
	global $dashboard_model, $month;

	$data = $dashboard_model->getLastUpdated($month);

	return $data;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Drift: Dashboard</title>
	<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            font-family: Arial, sans-serif;
			font-size: 14px;
        }

        .full-size-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            width: 100%;
			padding: 20px;
            background-color: #EC7A31;
        }

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
			background-color: #0000001A;
		}

		.content .row {
			background-color: #FFFFFF;
		}

		.content .bt {
			border-top: 1px solid #a9a9a9; 
		}

		.content .bt2 {
			border-top: 2px solid #454545; 
		}

		.content .bb {
			border-bottom: 1px solid #a9a9a9; 
		}
		
		.content .bl {
			border-left: 1px solid #a9a9a9; 
		}
		
		.content .br {
			border-right: 1px solid #a9a9a9; 
		}

		.content .br2 {
			border-right: 2px solid #454545; 
		}

		.content .heading {
			padding-left: 5px;
			border-right: 2px solid #454545;
			text-align: left;
		}

		.content .cell_title {
			padding-left: 5px;
			border-right: 1px solid #a9a9a9;
			text-align: left;
		}

		.content .cell {
			padding-right: 5px;
			border-right: 1px solid #a9a9a9;
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

		.content .orange {
			background-color: #f17d3226;
			border-top: 1px solid #a9a9a9;
			border-right: 1px solid #a9a9a9;
		}

		.content .orange_k53 {
			background-color: #f17d3226;
			border-top: 2px solid #454545; 
			border-right: 1px solid #a9a9a9;
		}

    </style>
</head>
<body>
	<div class="full-size-container">
        <div class="content_container">

			<div class="title"><pre>Drift Dashboard</pre></div>
			<form action="index.php" method="post">
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
						$value = date('Ym01', strtotime("$i months"));
						$date = date('Y-m-01', strtotime("$i months"));
						if ($value == $month)
						{
							echo '<option value="' . $value . '" selected>' . $date . '</option>';
						}
						else
						{
							echo '<option value="' . $value . '">' . $date . '</option>';
						}
					}
					echo '</select>';
				echo '</div>';
				?>
				<div><input type="submit" value="SUBMIT" style="background-color: #EC7A31; padding: 7px 10px; color: white; border-radius: 5px; border: 0; cursor: pointer" /></div>
				<div style="flex: 1"><?php echo $manual_input_msg; ?></div>
				<div style="background-color: #EC7A31; padding: 7px 10px; color: white; border-radius: 5px; border: 0; cursor: pointer" onclick="hideDepotTotals()">Hide Depot Totals</div>
			</div>
			</form>

			<div class="content" style="display: grid; grid-template-columns: repeat(13, auto); width: 100%; margin-top: 20px; line-height: 1.9rem; overflow: hidden; overflow-y: auto">
				
				<!-- Last update -->
				<div class="row_title bt bl heading">Last Updated:</div>
				<?php
				foreach ($last_updated as $date)
				{
					echo '<div class="row_title bt cell_title">' . $date . '</div>';
				}
				?>
				<div class="row_title bt br cell" style="border-left: 2px solid #454545;">&nbsp;</div>

				<!-- Depot: TC -->
				<div class="row_title bt bl heading">Depot</div>
				<?php
				foreach ($depot as $d)
				{
					echo '<div class="row_title bt cell_title">' . $d . '</div>';
				}
				?>
				<div class="row_title bt br cell" style="border-left: 2px solid #454545;">TOTAL</div>

				<!-- Active drivers -->
				<div class="row bt bl heading">Skilled Drivers</div>
				<?php
				foreach ($active_drivers as $index => $ad)
				{
					if ($index == count($active_drivers) - 1)
					{
						echo '<div class="row bt cell" style="border-left: 2px solid #454545;">' . $ad . '</div>';
					}
					else
					{
						echo '<div class="row bt cell">' . $ad . '</div>';
					}
				}
				?>
				
				<!-- Training -->
				<div class="row bt bl heading">Drivers on Training Trips</div>
				<?php
				foreach ($training as $index => $t)
				{
					if ($index == count($active_drivers) - 1)
					{
						echo '<div class="row bt cell" style="border-left: 2px solid #454545;">' . $t . '</div>';
					}
					else
					{
						echo '<div class="row bt cell">' . $t . '</div>';
					}
				}
				?>

				<!-- Old contracts -->
				<div id="dt_old_contracts" style="display: contents">
					<div class="row bt bl heading">Old Contracts</div>
					<?php
					foreach ($old_contracts as $index => $oc)
					{
						if ($index == count($active_drivers) - 1)
						{
							echo '<div class="row bt cell" style="border-left: 2px solid #454545;">' . $oc . '</div>';
						}
						else
						{
							if ($index < 11)
							{
								$this_depot = $depot[$index];
								if (in_array($this_depot, $result))
								{
									echo '<div class="row bt cell orange">' . $oc . '</div>';
								}
								else 
								{
									echo '<div class="row bt cell">' . $oc . '</div>';
								}
							}
						}
					}
				?>
				</div>

				<!-- Completed Training/Passed -->
				<div id="dt_completed" style="display: contents">
					<div class="row bt bl heading">Completed Training/Passed</div>
					<?php
					foreach ($completed_training as $index => $ct)
					{
						if ($index == count($active_drivers) - 1)
						{
							echo '<div class="row bt cell" style="border-left: 2px solid #454545;">' . $ct . '</div>';
						}
						else
						{
							if ($index < 11)
							{
								$this_depot = $depot[$index];
								if (in_array($this_depot, $result))
								{
									echo '<div class="row bt cell orange">' . $ct . '</div>';
								}
								else 
								{
									echo '<div class="row bt cell">' . $ct . '</div>';
								}
							}
						}
					}
					?>
				</div>

				<!-- Dismissed -->
				<div id="dt_dismissed" style="display: contents">
					<div class="row bt bl heading">Dismissed</div>
					<?php
					foreach ($dismissed as $index => $d)
					{
						if ($index == count($active_drivers) - 1)
						{
							echo '<div class="row bt cell" style="border-left: 2px solid #454545;">' . $d . '</div>';
						}
						else
						{
							if ($index < 11)
							{
								$this_depot = $depot[$index];
								if (in_array($this_depot, $result))
								{
									echo '<div class="row bt cell orange">' . $d . '</div>';
								}
								else 
								{
									echo '<div class="row bt cell">' . $d . '</div>';
								}
							}
						}
					}
					?>
				</div>

				<!-- Resigned -->
				<div id="dt_resigned" style="display: contents">
					<div class="row bt bl heading">Resigned</div>
					<?php
					foreach ($resigned as $index => $r)
					{
						if ($index == count($active_drivers) - 1)
						{
							echo '<div class="row bt cell" style="border-left: 2px solid #454545;">' . $r . '</div>';
						}
						else
						{
							if ($index < 11)
							{
								$this_depot = $depot[$index];
								if (in_array($this_depot, $result))
								{
									echo '<div class="row bt cell orange">' . $r . '</div>';
								}
								else 
								{
									echo '<div class="row bt cell">' . $r . '</div>';
								}
							}
						}
					}
					?>
				</div>

				<!-- Able to schedule: TC -->
				 <div style="display: none">
					<div class="row_title bt bl heading">Able To Schedule</div>
					<?php
					foreach ($tot_able_to_schedule as $index => $tot)
					{
						if ($index == count($active_drivers) - 1)
						{
							echo '<div class="row_title bt cell" style="border-left: 2px solid #454545;">' . $tot . '</div>';
						}
						else
						{
							echo '<div class="row_title bt cell">' . $tot . '</div>';
						}
					}
					?>
				</div>

				<!-- Not scheduled in 72 hours: TC -->
				<div class="row_title bt bl heading">Not scheduled in 72 hours</div>
				<?php
				foreach ($not_scheduled_in_72_hours as $index => $ns)
				{
					if ($index == count($active_drivers) - 1)
					{
						echo '<div class="row_title bt cell" style="border-left: 2px solid #454545;">' . $ns . '</div>';
					}
					else
					{
						if ($index < 11)
						{
							$hover_depot = $depot[$index];
							echo '<div class="row_title bt cell" title="' . $not_scheduled_in_72_hover[$hover_depot] . '">' . $ns . '</div>';
						}
					}
				}
				?>

				<!-- Minimum Drivers Needed -->
				<div class="row bt bl heading">Minimum Drivers Needed</div>
				<?php
				$tot_min_drivers_needed = array_sum($min_drivers_needed);
				foreach ($min_drivers_needed as $mdn)
				{
					echo '<div class="row bt cell">' . $mdn . '</div>';
				}
				?>
				<div class="row bt br cell" style="border-left: 2px solid #454545;"><?php echo $tot_min_drivers_needed; ?></div>

				<!-- Minimum Drivers Needed - 5% -->
				<div class="row bt bl heading">Minimum Drivers Needed - 5%</div>
				<?php
				$tot_min_drivers_needed_5 = array_sum($min_drivers_5);
				foreach ($min_drivers_5 as $mdn5)
				{
					echo '<div class="row bt cell">' . number_format($mdn5, 0) . '</div>';
				}
				?>
				<div class="row bt br cell" style="border-left: 2px solid #454545;"><?php echo number_format($tot_min_drivers_needed_5, 0); ?></div>

				<!-- Minimum Drivers Needed - 10% -->
				<div class="row bt bl heading">Minimum Drivers Needed - 10%</div>
				<?php
				$tot_min_drivers_needed_10 = array_sum($min_drivers_10);
				foreach ($min_drivers_10 as $mdn10)
				{
					echo '<div class="row bt cell">' . number_format($mdn10, 0) . '</div>';
				}
				?>
				<div class="row bt br cell" style="border-left: 2px solid #454545;"><?php echo number_format($tot_min_drivers_needed_10, 0); ?></div>

				<!-- Minimum Drivers Needed - 15% -->
				<div class="row bt bl heading">Minimum Drivers Needed - 15%</div>
				<?php
				$tot_min_drivers_needed_15 = array_sum($min_drivers_15);
				foreach ($min_drivers_15 as $mdn15)
				{
					echo '<div class="row bt cell">' . number_format($mdn15, 0) . '</div>';
				}
				?>
				<div class="row bt br cell" style="border-left: 2px solid #454545;"><?php echo number_format($tot_min_drivers_needed_15, 0); ?></div>

				<!-- Total Trips -->
				<div class="row_title bt bl heading">Total Trips</div>
				<?php
				$tot_total_trips = array_sum($total_trips);
				foreach ($total_trips as $index => $tt)
				{
					echo '<div class="row_title bt cell">' . $tt . '</div>';
				}
				?>
				<div class="row_title bt br cell" style="border-left: 2px solid #454545;"><?php echo $tot_total_trips; ?></div>

				<!-- Still need - MIN -->
				<div class="row bt bl heading">Still need - MIN</div>
				<?php
				$still_need_min = array();
				$tot_still_need_min = 0;
				for ($i = 0; $i < 11; $i++)
				{
					$tot = $active_drivers[$i] - $min_drivers_needed[$i];
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
					echo '<div class="row bt cell red" style="border-left: 2px solid #454545;">' . number_format($tot_still_need_min, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green" style="border-left: 2px solid #454545;">' . number_format($tot_still_need_min, 0) . '</div>';
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
					echo '<div class="row bt cell red" style="border-left: 2px solid #454545;">' . number_format($tot_still_need_5, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green" style="border-left: 2px solid #454545;">' . number_format($tot_still_need_5, 0) . '</div>';
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
					echo '<div class="row bt cell red" style="border-left: 2px solid #454545;">' . number_format($tot_still_need_10, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green" style="border-left: 2px solid #454545;">' . number_format($tot_still_need_10, 0) . '</div>';
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
					echo '<div class="row bt cell red" style="border-left: 2px solid #454545;">' . number_format($tot_still_need_15, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green" style="border-left: 2px solid #454545;">' . number_format($tot_still_need_15, 0) . '</div>';
				}
				?>

				<!-- Class training: TC -->
				<div id="dt_class_training" style="display: contents">
					<div class="row_title bt bl heading">Class Training</div>
					<?php
					foreach ($class_training as $index => $ct)
					{
						if ($index == count($active_drivers) - 1)
						{
							echo '<div class="row_title bt cell" style="border-left: 2px solid #454545;">' . $ct . '</div>';
						}
						else
						{
							if ($index < 11)
							{
								$this_depot = $depot[$index];
								if (in_array($this_depot, $result))
								{
									echo '<div class="row_title bt cell orange">' . $ct . '</div>';
								}
								else 
								{
									echo '<div class="row_title bt cell">' . $ct . '</div>';
								}
							}
						}
					}
					?>
				</div>

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
					echo '<div class="row bt cell red" style="border-left: 2px solid #454545;">' . number_format($tot_status_min, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green" style="border-left: 2px solid #454545;">' . number_format($tot_status_min, 0) . '</div>';
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
					echo '<div class="row bt cell red" style="border-left: 2px solid #454545;">' . number_format($tot_status_5, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green" style="border-left: 2px solid #454545;">' . number_format($tot_status_5, 0) . '</div>';
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
					echo '<div class="row bt cell red" style="border-left: 2px solid #454545;">' . number_format($tot_status_10, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green" style="border-left: 2px solid #454545;">' . number_format($tot_status_10, 0) . '</div>';
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
					echo '<div class="row bt cell red" style="border-left: 2px solid #454545;">' . number_format($tot_status_15, 0) . '</div>';
				}
				else
				{
					echo '<div class="row bt cell green" style="border-left: 2px solid #454545;">' . number_format($tot_status_15, 0) . '</div>';
				}
				?>

				<div id="hide_div" style="display: none; grid-column: span 13; border-top: 1px solid #a9a9a9">&nbsp;</div>

				<!-- K53 -->
				<div id="dt_k53" style="display: contents">
					<div class="row bt2 bl heading">K53</div>
					<?php
					foreach ($k53 as $index => $k)
					{
						if ($index == count($active_drivers) - 1)
						{
							echo '<div class="row bt2 cell" style="border-left: 2px solid #454545;">' . $k . '</div>';
						}
						else
						{
							if ($index < 11)
							{
								$this_depot = $depot[$index];
								if (in_array($this_depot, $result))
								{
									echo '<div class="row cell orange_k53">' . $k . '</div>';
								}
								else 
								{
									echo '<div class="row bt2 cell">' . $k. '</div>';
								}
							}
						}
					}
					?>
				</div>

				<!-- Interview -->
				<div id="dt_interview" style="display: contents">
					<div class="row bt bl heading">Interview</div>
					<?php
					foreach ($interview as $index => $i)
					{
						if ($index == count($active_drivers) - 1)
						{
							echo '<div class="row bt cell" style="border-left: 2px solid #454545;">' . $i . '</div>';
						}
						else
						{
							if ($index < 11)
							{
								$this_depot = $depot[$index];
								if (in_array($this_depot, $result))
								{
									echo '<div class="row bt cell orange">' . $i . '</div>';
								}
								else 
								{
									echo '<div class="row bt cell">' . $i. '</div>';
								}
							}
						}
					}
					?>
				</div>

				<!-- CVs in hand -->
				<div id="dt_cvs" style="display: contents">
					<div class="row bt bl bb heading">CVs in hand</div>
					<?php
					foreach ($cvs as $index => $c)
					{
						if ($index == count($active_drivers) - 1)
						{
							echo '<div class="row bt bb cell" style="border-left: 2px solid #454545;">' . $c . '</div>';
						}
						else
						{
							if ($index < 11)
							{
								$this_depot = $depot[$index];
								if (in_array($this_depot, $result))
								{
									echo '<div class="row bt bb cell orange">' . $c . '</div>';
								}
								else 
								{
									echo '<div class="row bt bb cell">' . $c . '</div>';
								}
							}
						}
					}
					?>
			</div>	

		</div>
    </div>
	<script>
		function hideDepotTotals()
		{
			let dt_old_contracts = document.getElementById('dt_old_contracts');
			let dt_completed = document.getElementById('dt_completed');
			let dt_dismissed = document.getElementById('dt_dismissed');
			let dt_resigned = document.getElementById('dt_resigned');
			let dt_class_training = document.getElementById('dt_class_training');
			let dt_k53 = document.getElementById('dt_k53');
			let dt_interview = document.getElementById('dt_interview');
			let dt_cvs = document.getElementById('dt_cvs');
			let hide_div = document.getElementById('hide_div');
			
			if (dt_old_contracts.style.display === 'none') { dt_old_contracts.style.display = 'contents'; } else {dt_old_contracts.style.display = 'none'; }
			if (dt_completed.style.display === 'none') { dt_completed.style.display = 'contents'; } else {dt_completed.style.display = 'none'; }
			if (dt_dismissed.style.display === 'none') { dt_dismissed.style.display = 'contents'; } else {dt_dismissed.style.display = 'none'; }
			if (dt_resigned.style.display === 'none') { dt_resigned.style.display = 'contents'; } else {dt_resigned.style.display = 'none'; }
			if (dt_class_training.style.display === 'none') { dt_class_training.style.display = 'contents'; } else {dt_class_training.style.display = 'none'; }
			if (dt_k53.style.display === 'none') { dt_k53.style.display = 'contents'; } else {dt_k53.style.display = 'none'; }
			if (dt_interview.style.display === 'none') { dt_interview.style.display = 'contents'; } else {dt_interview.style.display = 'none'; }
			if (dt_cvs.style.display === 'none') { dt_cvs.style.display = 'contents'; } else {dt_cvs.style.display = 'none'; }
			if (hide_div.style.display === 'none') { hide_div.style.display = 'grid'; } else {hide_div.style.display = 'none'; }
			
			
		}
	</script>
</body>
</html>
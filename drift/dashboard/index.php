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

			<div class="content" style="display: grid; grid-template-columns: repeat(13, auto); width: 100%; margin-top: 20px; line-height: 2rem; overflow: hidden; overflow-y: auto">
				<!-- Last update -->
				<div>Last Updated:</div>
				<?php
				foreach ($last_updated as $date)
				{
					echo '<div>' . $date . '</div>';
				}
				?>
				<div>&nbsp;</div>

				<!-- Depot: TC -->
				<div>Depot</div>
				<?php
				foreach ($depot as $d)
				{
					echo '<div>' . $d . '</div>';
				}
				?>
				<div>TOTAL</div>

				<!-- Active drivers -->
				<div>Active Drivers</div>
				<?php
				foreach ($active_drivers as $ad)
				{
					echo '<div>' . $ad . '</div>';
				}
				?>
				
				<!-- Training -->
				<div>Training</div>
				<?php
				foreach ($training as $t)
				{
					echo '<div>' . $t . '</div>';
				}
				?>

				<!-- Old contrcts -->
				<div>Old Contracts</div>
				<?php
				foreach ($old_contracts as $oc)
				{
					echo '<div>' . $oc . '</div>';
				}
				?>

				<!-- Completed Training/Passed -->
				<div>Completed Training/Passed</div>
				<?php
				foreach ($completed_training as $ct)
				{
					echo '<div>' . $ct . '</div>';
				}
				?>

				<!-- Dismissed -->
				<div>Dismissed</div>
				<?php
				foreach ($dismissed as $d)
				{
					echo '<div>' . $d . '</div>';
				}
				?>

				<!-- Resigned -->
				<div>Resigned</div>
				<?php
				foreach ($resigned as $r)
				{
					echo '<div>' . $r . '</div>';
				}
				?>

				<!-- Able to schedule: TC -->
				<div>Able To Schedule</div>
				<?php
				foreach ($tot_able_to_schedule as $tot)
				{
					echo '<div>' . $tot . '</div>';
				}
				?>

				<!-- Not scheduled in 72 hours: TC -->
				<div>Not scheduled in 72 hours</div>
				<?php
				foreach ($not_scheduled_in_72_hours as $ns)
				{
					echo '<div>' . $ns . '</div>';
				}
				?>

				<!-- Minimum Drivers Needed -->
				<div>Minimum Drivers Needed</div>
				<?php
				foreach ($min_drivers_needed as $mdn)
				{
					echo '<div>' . $mdn . '</div>';
				}
				?>

				<!-- Minimum Drivers Needed - 5% -->
				<div>Minimum Drivers Needed - 5%</div>
				<?php
				foreach ($min_drivers_5 as $mdn5)
				{
					echo '<div>' . number_format($mdn5, 0) . '</div>';
				}
				?>

				<!-- Minimum Drivers Needed - 10% -->
				<div>Minimum Drivers Needed - 10%</div>
				<?php
				foreach ($min_drivers_10 as $mdn10)
				{
					echo '<div>' . number_format($mdn10, 0) . '</div>';
				}
				?>

				<!-- Minimum Drivers Needed - 15% -->
				<div>Minimum Drivers Needed - 15%</div>
				<?php
				foreach ($min_drivers_15 as $mdn15)
				{
					echo '<div>' . number_format($mdn15, 0) . '</div>';
				}
				?>

				<!-- Total Trips -->
				<div>Total Trips</div>
				<?php
				$tot_total_trips = array_sum($total_trips);
				foreach ($total_trips as $tt)
				{
					echo '<div>' . $tt . '</div>';
				}
				?>
				<div><?php echo $tot_total_trips; ?></div>

				<!-- Still need - MIN -->
				<div>Still need - MIN</div>
				<?php
				$still_need_min = array();
				$tot_still_need_min = 0;
				for ($i = 0; $i < 11; $i++)
				{
					$tot = $tot_able_to_schedule[$i] - $min_drivers_needed[$i];
					$tot_still_need_min += $tot;
					$still_need_min[] = $tot;

					echo '<div>' . $tot . '</div>';
				}
				?>
				<div><?php echo $tot_still_need_min; ?></div>

				<!-- Still need - 5% -->
				<div>Still need - 5%</div>
				<?php
				$still_need_5 = array();
				$tot_still_need_5 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $tot_able_to_schedule[$i] - $min_drivers_5[$i];
					$tot_still_need_5 += $tot;
					$still_need_5[] = $tot;

					echo '<div>' . number_format($tot, 0) . '</div>';
				}
				?>
				<div><?php echo $tot_still_need_5; ?></div>

				<!-- Still need - 10% -->
				<div>Still need - 10%</div>
				<?php
				$still_need_10 = array();
				$tot_still_need_10 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $tot_able_to_schedule[$i] - $min_drivers_10[$i];
					$tot_still_need_10 += $tot;
					$still_need_10[] = $tot;
					
					echo '<div>' . number_format($tot, 0) . '</div>';
				}
				?>
				<div><?php echo $tot_still_need_10; ?></div>

				<!-- Still need - 15% -->
				<div>Still need - 15%</div>
				<?php
				$still_need_15 = array();
				$tot_still_need_15 = 0;
				
				for ($i = 0; $i < 11; $i++)
				{
					$tot = $tot_able_to_schedule[$i] - $min_drivers_15[$i];
					$tot_still_need_15 += $tot;
					$still_need_15[] = $tot;

					echo '<div>' . number_format($tot, 0) . '</div>';
				}
				?>
				<div><?php echo $tot_still_need_15; ?></div>

				<!-- Class training: TC -->
				<div>Class Training</div>
				<?php
				foreach ($class_training as $ct)
				{
					echo '<div>' . $ct . '</div>';
				}
				?>

				<!-- Status - MIN -->
				<div>Status - MIN</div>
				<?php
				$tot_status_min = 0;
				$status_min = array();

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $still_need_min[$i] + $class_training[$i];
					
					$tot_status_min += $tot;
					$status_min[] = $tot;

					echo '<div>' . number_format($tot, 0) . '</div>';
				}
				?>
				<div><?php echo $tot_status_min; ?></div>

				<!-- Status - 5% -->
				<div>Status - 5%</div>
				<?php
				$status_5 = array();
				$tot_status_5 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $still_need_5[$i] + $status_min[$i];
					
					$tot_status_5 += $tot;
					$status_5[] = $tot;

					echo '<div>' . number_format($tot, 0) . '</div>';
				}
				?>
				<div><?php echo $tot_status_5; ?></div>

				<!-- Status - 10% -->
				<div>Status - 10%</div>
				<?php
				$status_10 = array();
				$tot_status_10 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $still_need_10[$i] + $status_5[$i];
					
					$tot_status_10 += $tot;
					$status_10[] = $tot;

					echo '<div>' . number_format($tot, 0) . '</div>';
				}
				?>
				<div><?php echo $tot_status_10; ?></div>

				<!-- Status - 15% -->
				<div>Status - 15%</div>
				<?php
				$status_15 = array();
				$tot_status_15 = 0;

				for ($i = 0; $i < 11; $i++)
				{
					$tot = $still_need_15[$i] + $status_10[$i];
					
					$tot_status_15 += $tot;
					$status_15[] = $tot;

					echo '<div>' . number_format($tot, 0) . '</div>';
				}
				?>
				<div><?php echo $tot_status_15; ?></div>

				<!-- K53 -->
				<div>K53</div>
				<?php
				foreach ($k53 as $k)
				{
					echo '<div>' . $k . '</div>';
				}
				?>
				<div><?php echo array_sum($k53); ?></div>

				<!-- Interview -->
				<div>Interview</div>
				<?php
				foreach ($interview as $i)
				{
					echo '<div>' . $i . '</div>';
				}
				?>
				<div><?php echo array_sum($interview); ?></div>

				<!-- CVs in hand -->
				<div>CVs in hand</div>
				<?php
				foreach ($cvs as $c)
				{
					echo '<div>' . $c . '</div>';
				}
				?>
				<div><?php echo array_sum($cvs); ?></div>
			</div>	
				

		</div>
    </div>
</body>
</html>
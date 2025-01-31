<?php
include 'index.php';

global $emall_html;

$currentBatch = isset($_POST['batch']) ? (int)$_POST['batch'] : 0;

$batch = $chunks[$currentBatch];

if ($currentBatch == 0)
{
	// Backup the budget table
	// backupTables();

	// Clear the text/report file
	clearLogFile();

	// Clear the text/report file
	clearEmailLog();

	log_event("Budget update started: " . date('Y-m-d H:i:s') . "\n");
}

foreach ($batch as $serial)
{
	$budget_amounts = get_budget_amounts($the_month, $serial);
	$new_budget = $budget_amounts;
	$budget_spend = get_budget_spend($the_month, $serial);
	$budget_name = $budget_names[$serial];

	$total_budget = array_sum($budget_amounts);
	$total_used = array_sum($budget_spend);

	// Get YTD budget
	$ytd_budget_amount = 0;
	$ytd_budget = array();

	foreach ($budget_amounts as $month => $amount) 
	{
		// Check if the month is in the same year and before or equal to the target month
		if (substr($month, 2) === substr($work_month_budget, 2) && $month <= $work_month_budget) 
		{
			$ytd_budget_amount += $amount;
			if (!isset($ytd_budget[$month])) 
			{
				$ytd_budget[$month] = 0;
			}
	
			$ytd_budget[$month] += $ytd_budget_amount;
		}
	}

	// Get YTD Spend
	$ytd_spend_amount = 0;
	$ytd_spend = array();

	foreach ($budget_spend as $month => $amount) 
	{
		// Check if the month is in the same year and before or equal to the target month
		if (substr($month, 0, -2) === substr($work_month_spend, 0, -2) && $month <= $work_month_spend) 
		{
			$ytd_spend_amount += $amount;
			if (!isset($ytd_spend[$month])) 
			{
				$ytd_spend[$month] = 0;
			}
	
			$ytd_spend[$month] += $ytd_spend_amount;
		}
	}

	$diff = $ytd_budget[$work_month_budget] - $ytd_spend[$work_month_spend];
	$adjustment = $budget_spend[$work_month_spend] - $diff;

	// Get work month new budget
	$work_month_new_budget = $budget_amounts[$work_month_budget] + $adjustment;

	// Get next month new budget
	$next_month_new_budget = $budget_amounts[$next_month_budget] - $adjustment;

	log_event("- Budget name: " . $budget_name . " - Budget serial: " . $serial . "- Total budget: " . $total_budget . " - Total spend: " . $total_used . " - Difference: " . $total_budget - $total_used);

	$new_budget[$work_month_budget] = $work_month_new_budget;
	$new_budget[$next_month_budget] = $next_month_new_budget;
	

	// Borrow
	// Calculate the total sum
	$total_sum = array_sum($new_budget);

	// Check if the second last item is negative
	$keys = array_keys($new_budget);
	$second_last_key = $keys[count($keys) - 2];
	$last_key = $keys[count($keys) - 1];
	$last_value = $new_budget[$last_key];

	if ($new_budget[$second_last_key] < 0) 
	{
		// Calculate the adjustment needed
		$adjustment = abs($new_budget[$second_last_key]);
		$new_budget[$second_last_key] = 0;

		// Adjust the previous items to maintain the total sum
		for ($i = count($keys) - 3; $i >= 0; $i--) 
		{
			$key = $keys[$i];
			if ($new_budget[$key] >= $adjustment) 
			{
				$new_budget[$key] -= $adjustment;
				break;
			} 
			else 
			{
				$adjustment -= $new_budget[$key];
				$new_budget[$key] = 0;
			}
		}

		// Ensure the total sum remains the same
		$new_budget[$keys[count($keys) - 1]] = $total_sum - array_sum($new_budget);
	}

	$new_budget[$last_key] = $last_value;

	log_event("- New budget amounts: " . json_encode($new_budget));
	log_email("<tr><td align='left'>" . $budget_name . "</td><td align='right'>" . $total_budget . "</td><td align='right'>" . $total_used . "</td><td align='right'>" . $new_budget[$last_key] . "</td></tr>");

	// Update budget table
	$result = upd_budget_amounts($new_budget, $serial);

	log_event("- Update result: " . json_encode($result) . "\n");
}

if ($currentBatch == 0)
{
	log_event("Budget update ended: " . date('Y-m-d H:i:s'));
	log_email("</table></body></html>");
}

echo "1";
?>
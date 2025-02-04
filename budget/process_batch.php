<?php
include 'index.php';

// Backup the budget table
//QQQ backupTables();

// Clear the text/report file
clearLogFile();

// Clear the text/report file
clearEmailLog();

log_event("Budget update started: " . date('Y-m-d H:i:s') . "\n");


foreach ($budget_serials as $serial)
{
	// $serial = 14458;
	
	$budget_amounts = get_budget_amounts($the_month, $serial);
	$new_budget = $budget_amounts;
	$budget_spend = get_budget_spend($the_month, $serial);

	if (!isset($budget_names[$serial]))
	{
		$budget_name = 'Unknown';
	}
	else 
	{
		$budget_name = $budget_names[$serial];
	}

	$total_budget = array_sum($budget_amounts);
	$total_used = array_sum($budget_spend);

	// Get YTD budget
	$ytd_budget_amount = 0;
	$ytd_budget = array();

	foreach ($budget_amounts as $month => $amount) 
	{
		if ($month != $next_month_budget) 
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
		if ($month != $next_month_spend) 
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

	if ($diff > 0) 
	{
		// print_r($ytd_budget);
		// print_r($ytd_spend);
		// echo "TOT Budget: " . $total_budget . "\n";
		// echo "TOT Spend: " . $ytd_spend_amount. "\n";
		// echo "YTD Budget: " . json_encode($ytd_budget) . "\n";
		// echo "YTD Spend: " . json_encode($ytd_spend) . "\n";
		// echo "Difference: $diff\n";
		echo "Processed: $serial >>> $diff\n";
		// Get work month new budget
		$work_month_new_budget = $budget_amounts[$work_month_budget] + $adjustment;

		// Get next month new budget
		$next_month_new_budget = $budget_amounts[$next_month_budget] - $adjustment;
		//echo "Adjustment: $adjustment\n";
		$email_adjustment = number_format($adjustment * -1, 2);
		log_event("- Budget name: " . $budget_name . " - Budget serial: " . $serial . "- Total budget: " . $total_budget . " - Total spend: " . $total_used . " - Difference: " . $total_budget - $total_used . " - Adjustment: " . $adjustment * -1);

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
		log_email("<tr><td align='left'>" . $budget_name . " (" . $serial . ")</td><td align='right'>" . $total_budget . "</td><td align='right'>" . number_format($total_used, 2) . "</td><td align='right'>" . $email_adjustment . "</td><td align='right'>" . number_format($new_budget[$last_key], 2) . "</td></tr>");

		// Update budget table
		$result = upd_budget_amounts($new_budget, $serial);

		log_event("- Update result: " . json_encode($result) . "\n");
	}
	else
	{
		// echo "YTD Budget: " . json_encode($ytd_budget) . "\n";
		
		// print_r($ytd_budget);
		// print_r($ytd_spend);
		// echo "TOT Budget: " . $total_budget . "\n";
		// echo "TOT Spend: " . $ytd_spend_amount. "\n";
		// echo "YTD Budget: " . json_encode($ytd_budget) . "\n";
		// echo "YTD Spend: " . json_encode($ytd_spend) . "\n";
		// echo "Difference: $diff\n";
		echo "Ignored: $serial >>> $diff\n";
	}
	
}

log_event("Budget update ended: " . date('Y-m-d H:i:s'));
log_email("</table></body></html>");
// QQQ send_email();

echo "\n\nDone";
?>
<!-- 
The total budget in my calcs is wrong. I had 670000, needs to be 600000. See calcs  tesing.ods

- Budget name: RSA IFoundation - Budget serial: 12093- Total budget: 600000 - Total spend: 19913.35 - Difference: 580086.65 - Adjustment: 580018.75
- New budget amounts: {"062024":19981.25,"072024":0,"082024":0,"092024":0,"102024":0,"112024":0,"122024":0,"012025":0,"022025":580018.75}
- Update result: {"status":"success","message":"Transaction committed successfully."} 

-->
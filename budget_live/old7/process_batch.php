<?php
include 'index.php';

// Backup the budget table
backupTables();

// Clear the text/report file
clearLogFile();

// Clear the text/report file
clearEmailLog();

log_event("Budget update started: " . date('Y-m-d H:i:s') . "\n");

$run_start = time();
// echo "Start\n";

foreach ($budget_serials as $serial)
{
	// $serial = 12043; //11698
	
	$budget_amounts = get_budget_amounts($serial);
	$new_budget = $budget_amounts;
	$budget_spend = get_budget_spend($serial);

	if (!isset($budget_names[$serial]))
	{
		$budget_name = 'Unknown';
	}
	else 
	{
		$budget_name = $budget_names[$serial];
	}

	$budget_amounts_copy = $budget_amounts;
	array_pop($budget_amounts_copy);
	$total_budget = array_sum($budget_amounts_copy);

	$budget_spend_copy = $budget_spend;
	array_pop($budget_spend_copy);
	$total_used = array_sum($budget_spend_copy);

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

	$nett = $total_budget - $total_used;

	$tolerance = 1.0E-9;

	if (abs($nett) < $tolerance) {
		$nett = 0;
	}

	// echo "Nett: $nett :: $total_budget - $total_used\n";

	if ($nett > 0) 
	{
		$diff = $ytd_spend[$work_month_spend] - $ytd_budget[$work_month_budget];
		$diff = round($diff, 2);
		$adjustment = $budget_spend[$work_month_spend] - $diff;
		$current_new_budget = $diff + $budget_amounts[$work_month_budget];
		$current_new_budget = round($current_new_budget, 2);
		$next_new_budget = ($diff * -1) + $budget_amounts[$next_month_budget];
		$next_new_budget = round($next_new_budget, 2);

		// print_r($budget_amounts);
		// print_r($budget_spend);

		// echo "Nett: $nett\n";
		// echo "Serial: $serial\n";
		// echo "Difference: $diff\n";
		// echo "Tot Budget: $total_budget\n";
		// echo "Tot Spend: $total_used\n";
		// echo "Adjustment: $adjustment\n";
		// echo "CNB: $current_new_budget\n";
		// echo "NNB: $next_new_budget\n";
		
		$email_adjustment = round($diff * -1, 2);
		$neg_diff = round($diff * -1, 2);
		log_event("- Budget name: " . $budget_name . " - Budget serial: " . $serial . "- Total budget: " . $total_budget . " - Total spend: " . $total_used . " - Difference: " . $total_budget - $total_used . " - Adjustment: " . $neg_diff);

		$new_budget[$work_month_budget] = $current_new_budget;
		$new_budget[$next_month_budget] = $next_new_budget;
		
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
			$adjustment = round($adjustment, 2);
			$new_budget[$second_last_key] = 0;

			// Adjust the previous items to maintain the total sum
			for ($i = count($keys) - 3; $i >= 0; $i--) 
			{
				$key = $keys[$i];
				if ($new_budget[$key] >= $adjustment) 
				{
					$new_budget[$key] -= $adjustment;
					$new_budget[$key] = round($new_budget[$key], 2);
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
		log_email("<tr><td align='left'>" . $budget_name . " (" . $serial . ")</td><td align='right'>" . number_format($total_budget, 2) . "</td><td align='right'>" . number_format($total_used, 2) . "</td><td align='right'>" . $email_adjustment . "</td><td align='right'>" . number_format($new_budget[$last_key], 2) . "</td></tr>");

		// Update budget table
		$result = upd_budget_amounts($new_budget, $serial);

		if ($result['status'] == 'success') 
		{
			$amount = $total_budget - $total_used;
			$transfer_date = date('d/M/y H:i:s');
			
			$fmt_month_next_spend = substr($next_month_spend, 2, 2) . substr($next_month_spend, 4, 2);
			$bud_to_ym = $fmt_month_next_spend;
			$fmt_month_spend = substr($work_month_spend, 2, 2) . substr($work_month_spend, 4, 2);
			$bud_from_ym = $fmt_month_spend;
			$reason = 'Surplus YTD budget adjustment';
			$user_name = 'SYSTEM';

			// Neg change
			add_pbl_entry('', $serial, $amount, $transfer_date, 0, $bud_to_ym, '', $reason, $user_name);

			// Pos change
			add_pbl_entry($serial, '', $amount, $transfer_date, 0, '', $bud_from_ym, $reason, $user_name);
		} 

		log_event("- Update result: " . json_encode($result) . "\n");
	}
	else
	{
		// Result was over budget, dont include or output here if needed
	}
}

log_event("Budget update ended: " . date('Y-m-d H:i:s'));
log_email("</table></body></html>");
send_email();

$run_end = time();
// echo "\n\nDone in " . ($run_end - $run_start) . " seconds\n";

// Output the contents of email.html
$email_content = file_get_contents('email.html');
if ($email_content !== false) 
{
    // echo "\n\nEmail Content:\n";
    echo $email_content;
} 
else 
{
    echo "\n\nFailed to read email.html file.";
}
?>
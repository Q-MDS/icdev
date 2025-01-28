<?php
function calculateYTD($data) {
    $YTD = [];
    $sum = 0;
    foreach ($data as $month => $amount) {
        $sum += $amount;
        $YTD[$month] = $sum;
    }
    return $YTD;
}

function adjustBudgets(&$budget, $spent) {
    $monthlyDifference = [];
    $prevDeficit = 0;

    foreach ($budget as $month => $amount) {
        $difference = $amount - $spent[$month] - $prevDeficit;
        $monthlyDifference[$month] = $difference;

        if ($difference < 0) {
            // Borrow from previous months if there's a deficit
            $prevDeficit = abs($difference);
            $budget[$month] = 0;
        } else {
            $prevDeficit = 0;
        }
    }

    return $monthlyDifference;
}

// Sample data
$budget = [
    "6" => 30000,
    "7" => 30000,
    "8" => 30000,
    "9" => 30000,
    "10" => 30000,
    "11" => 30000,
    "12" => 30000,
    "13" => 30000,
    // Continue for each month...
];

$spent = [
    "6" => 19406.60,
    "7" => 20000,
    "8" => 13510.60,
    "9" => 37976,
    "10" => 30000,
    "11" => 20000,
    "12" => 20000,
    "13" => 0,
    // Continue for each month...
];

// Calculate YTD totals
$budgetYTD = calculateYTD($budget);
$spentYTD = calculateYTD($spent);

// Adjust budgets and calculate monthly differences
$monthlyDifference = adjustBudgets($budget, $spent);

print_r($budgetYTD);
print_r($spentYTD);
print_r($monthlyDifference);
?>

<?php
// Debug the forecast calculation issue
echo "Debugging Forecast Calculation\n";
echo "============================\n";

// Your data from the database
$budget = 300.00;
$actual = 100.00;
$forecast = 100.00;  // This is wrong, should be 200.00
$actual_plus_forecast = 200.00;

echo "Current Database Values:\n";
echo "Budget: $budget\n";
echo "Actual: $actual\n";
echo "Forecast (WRONG): $forecast\n";
echo "Actual + Forecast: $actual_plus_forecast\n";
echo "\n";

echo "Expected Values:\n";
$expected_forecast = $budget - $actual;
$expected_actual_plus_forecast = $actual + $expected_forecast;
echo "Expected Forecast: $expected_forecast\n";
echo "Expected Actual + Forecast: $expected_actual_plus_forecast\n";
echo "\n";

// Let's trace what the current SQL is doing
echo "Current SQL Logic Analysis:\n";
$amount = 100.00;  // The amount being added
echo "Amount being added: $amount\n";

// The current SQL is:
// actual = COALESCE(actual, 0) + ?
// forecast = COALESCE(budget, 0) - (COALESCE(actual, 0) + ?)
// actual_plus_forecast = (COALESCE(actual, 0) + ?) + (COALESCE(budget, 0) - (COALESCE(actual, 0) + ?))

// Let's trace this:
$old_actual = 0.00;  // Assuming this was the previous actual before adding the transaction
$new_actual = $old_actual + $amount;
$calculated_forecast = $budget - ($old_actual + $amount);
$calculated_actual_plus_forecast = ($old_actual + $amount) + ($budget - ($old_actual + $amount));

echo "Tracing SQL Logic:\n";
echo "Old Actual: $old_actual\n";
echo "New Actual: $new_actual\n";
echo "Calculated Forecast: $calculated_forecast\n";
echo "Calculated Actual + Forecast: $calculated_actual_plus_forecast\n";
echo "\n";

// But wait, what if the actual was already 100 before this transaction?
// Let's check that scenario:
$previous_actual = 0.00;  // What was actual before this transaction?
$current_actual = 100.00;  // What is actual now?
$transaction_amount = $current_actual - $previous_actual;

echo "Alternative Scenario:\n";
echo "Previous Actual: $previous_actual\n";
echo "Current Actual: $current_actual\n";
echo "Transaction Amount: $transaction_amount\n";
echo "Calculated Forecast: " . ($budget - $current_actual) . "\n";
echo "\n";

// The issue might be that we're not correctly calculating the forecast
// Let's check if there's an issue with the SQL formula itself
echo "SQL Formula Issue Analysis:\n";
echo "The formula in SQL is:\n";
echo "forecast = COALESCE(budget, 0) - (COALESCE(actual, 0) + ?)\n";
echo "But this is wrong! It should be:\n";
echo "forecast = COALESCE(budget, 0) - COALESCE(actual, 0)\n";
echo "Because actual is already updated in the first SET clause.\n";
?>
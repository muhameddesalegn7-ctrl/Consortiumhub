<?php
// Debug the forecast calculation issue - Corrected Version
echo "Debugging Forecast Calculation - Corrected Understanding\n";
echo "======================================================\n";

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

// The issue is with the order of operations in MySQL UPDATE statements
echo "MySQL UPDATE Statement Order of Operations Issue:\n";
echo "In MySQL, SET clauses are evaluated left to right.\n";
echo "So when we have:\n";
echo "UPDATE table SET\n";
echo "  actual = COALESCE(actual, 0) + amount,     -- actual is updated first\n";
echo "  forecast = budget - (actual + amount),     -- actual here is the NEW value!\n";
echo "  actual_plus_forecast = (actual + amount) + (budget - (actual + amount))\n";
echo "\n";

// Let's trace what actually happens:
$original_actual = 0.00;  // What actual was before the transaction
$transaction_amount = 100.00;  // The amount of the new transaction

echo "Tracing the WRONG calculation:\n";
echo "Original Actual: $original_actual\n";
echo "Transaction Amount: $transaction_amount\n";
echo "New Actual (after first SET clause): " . ($original_actual + $transaction_amount) . "\n";
echo "Forecast Calculation: budget - (NEW actual + amount)\n";
echo "Forecast = $budget - (" . ($original_actual + $transaction_amount) . " + $transaction_amount)\n";
echo "Forecast = $budget - " . ($original_actual + 2 * $transaction_amount) . " = " . ($budget - ($original_actual + 2 * $transaction_amount)) . "\n";
echo "\n";

// The correct approach:
echo "Correct Approach:\n";
echo "After actual is updated, forecast should be:\n";
echo "forecast = budget - NEW actual\n";
echo "forecast = $budget - " . ($original_actual + $transaction_amount) . " = " . ($budget - ($original_actual + $transaction_amount)) . "\n";
echo "\n";

echo "And actual_plus_forecast should be:\n";
echo "actual_plus_forecast = NEW actual + forecast\n";
echo "actual_plus_forecast = " . ($original_actual + $transaction_amount) . " + " . ($budget - ($original_actual + $transaction_amount)) . " = $budget\n";
echo "\n";

echo "Fixed SQL:\n";
echo "UPDATE budget_data SET\n";
echo "  actual = COALESCE(actual, 0) + ?,\n";
echo "  forecast = COALESCE(budget, 0) - (COALESCE(actual, 0) + ?),\n";
echo "  actual_plus_forecast = COALESCE(actual, 0) + COALESCE(forecast, 0)\n";
echo "(The last line uses the NEW values of actual and forecast after they've been updated)\n";
?>
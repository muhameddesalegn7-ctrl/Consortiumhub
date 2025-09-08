<?php
// Test script to verify budget calculations
session_start();

// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

echo "<h2>Budget Calculation Test</h2>";

// Test 1: Check budget_data table structure
echo "<h3>Test 1: Budget Data Table Structure</h3>";
$testQuery = "SELECT * FROM budget_data WHERE year2 = 2025 AND cluster = 'Mekele' LIMIT 5";
$result = $conn->query($testQuery);
if ($result) {
    echo "<p>✅ budget_data table accessible</p>";
    while ($row = $result->fetch_assoc()) {
        echo "<p>ID: {$row['id']}, Category: {$row['category_name']}, Period: {$row['period_name']}, Budget: {$row['budget']}, Actual: {$row['actual']}, Forecast: {$row['forecast']}</p>";
    }
} else {
    echo "<p>❌ Error accessing budget_data table: " . $conn->error . "</p>";
}

// Test 2: Check budget_preview table structure
echo "<h3>Test 2: Budget Preview Table Structure</h3>";
$testQuery2 = "SELECT * FROM budget_preview WHERE cluster = 'Mekele' ORDER BY PreviewID DESC LIMIT 3";
$result2 = $conn->query($testQuery2);
if ($result2) {
    echo "<p>✅ budget_preview table accessible</p>";
    while ($row = $result2->fetch_assoc()) {
        echo "<p>ID: {$row['PreviewID']}, Category: {$row['CategoryName']}, Amount: {$row['Amount']}, OriginalBudget: {$row['OriginalBudget']}, ActualSpent: {$row['ActualSpent']}, ForecastAmount: {$row['ForecastAmount']}, RemainingBudget: {$row['RemainingBudget']}</p>";
    }
} else {
    echo "<p>❌ Error accessing budget_preview table: " . $conn->error . "</p>";
}

// Test 3: Test budget calculation logic
echo "<h3>Test 3: Budget Calculation Logic</h3>";
$originalBudget = 300.00;
$currentActual = 100.00;
$newAmount = 50.00;

$newActualSpent = $currentActual + $newAmount; // 100 + 50 = 150
$forecastAmount = max(0, $originalBudget - $newActualSpent); // 300 - 150 = 150
$remainingBudget = $forecastAmount; // 150
$variancePercentage = 0; // Since Actual + Forecast = Budget

echo "<p>Original Budget: $originalBudget</p>";
echo "<p>Current Actual: $currentActual</p>";
echo "<p>New Amount: $newAmount</p>";
echo "<p>New Actual Spent: $newActualSpent</p>";
echo "<p>Forecast Amount: $forecastAmount</p>";
echo "<p>Remaining Budget: $remainingBudget</p>";
echo "<p>Variance Percentage: $variancePercentage</p>";

// Test 4: Check dashboard calculation
echo "<h3>Test 4: Dashboard Budget Calculation</h3>";
$currentYear = 2025;
$userCluster = 'Mekele';

$budgetQuery = "SELECT 
    SUM(CASE WHEN period_name = 'Annual Total' THEN budget ELSE 0 END) as total_budget,
    SUM(CASE WHEN period_name = 'Annual Total' THEN actual ELSE 0 END) as total_actual_spent,
    SUM(CASE WHEN period_name = 'Annual Total' THEN forecast ELSE 0 END) as total_forecast
    FROM budget_data WHERE cluster = ? AND year2 = ?";

$stmt = $conn->prepare($budgetQuery);
$stmt->bind_param("si", $userCluster, $currentYear);
$stmt->execute();
$budgetResult = $stmt->get_result();
$budgetData = $budgetResult->fetch_assoc();

$totalBudget = $budgetData['total_budget'] ?? 0;
$totalActualSpent = $budgetData['total_actual_spent'] ?? 0;
$totalForecast = $budgetData['total_forecast'] ?? 0;

$remainingBudget = max(0, $totalBudget - $totalActualSpent);
$budgetUtilization = $totalBudget > 0 ? ($totalActualSpent / $totalBudget) * 100 : 0;

echo "<p>Total Budget: $totalBudget</p>";
echo "<p>Total Actual Spent: $totalActualSpent</p>";
echo "<p>Total Forecast: $totalForecast</p>";
echo "<p>Remaining Budget: $remainingBudget</p>";
echo "<p>Budget Utilization: " . number_format($budgetUtilization, 2) . "%</p>";

// Test 5: Verify data consistency
echo "<h3>Test 5: Data Consistency Check</h3>";
$consistencyQuery = "SELECT 
    category_name,
    period_name,
    budget,
    actual,
    forecast,
    actual_plus_forecast,
    (budget - (actual + forecast)) as variance_amount,
    variance_percentage
    FROM budget_data 
    WHERE year2 = 2025 AND cluster = 'Mekele' 
    ORDER BY category_name, period_name";

$consistencyResult = $conn->query($consistencyQuery);
if ($consistencyResult) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Category</th><th>Period</th><th>Budget</th><th>Actual</th><th>Forecast</th><th>Actual+Forecast</th><th>Variance Amount</th><th>Variance %</th></tr>";
    
    while ($row = $consistencyResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['category_name']}</td>";
        echo "<td>{$row['period_name']}</td>";
        echo "<td>{$row['budget']}</td>";
        echo "<td>{$row['actual']}</td>";
        echo "<td>{$row['forecast']}</td>";
        echo "<td>{$row['actual_plus_forecast']}</td>";
        echo "<td>{$row['variance_amount']}</td>";
        echo "<td>{$row['variance_percentage']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Error checking data consistency: " . $conn->error . "</p>";
}

echo "<h3>Test Complete</h3>";
echo "<p>Check the results above to verify budget calculations are working correctly.</p>";
?>

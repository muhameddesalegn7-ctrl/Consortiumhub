<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Initialize response
$response = [
    'success' => false,
    'message' => 'No transaction ID provided'
];

// Check if transaction ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $transactionId = intval($_GET['id']);
    
    // Log the transaction ID being processed
    error_log('Processing delete request for transaction ID: ' . $transactionId);
    
    // Begin transaction for database consistency
    $conn->begin_transaction();
    error_log('Database transaction begun');
    
    try {
        // Get transaction details before deletion to update budget_data
        $getTransactionQuery = "SELECT Amount, budget_id, CategoryName, QuarterPeriod, EntryDate, ACCEPTANCE FROM budget_preview WHERE PreviewID = ?";
        error_log('Query to get transaction: ' . $getTransactionQuery . ' with ID: ' . $transactionId);
        
        $stmt = $conn->prepare($getTransactionQuery);
        if (!$stmt) {
            throw new Exception("Prepare statement failed for transaction lookup: " . $conn->error);
        }
        
        $stmt->bind_param("i", $transactionId);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed for transaction lookup: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("Transaction not found with ID: " . $transactionId);
            throw new Exception("Transaction not found with ID: " . $transactionId);
        }
        
        error_log("Transaction found, fetching data");
        $transaction = $result->fetch_assoc();
        error_log("Transaction data: " . print_r($transaction, true));
        
        // Check if the transaction is already accepted
        if (isset($transaction['ACCEPTANCE']) && $transaction['ACCEPTANCE'] == 1) {
            error_log("Cannot delete accepted transaction: " . $transactionId);
            throw new Exception("Cannot delete an accepted transaction");
        }
        
        // Extract transaction values safely
        $amount = floatval($transaction['Amount'] ?? 0);
        $budgetId = intval($transaction['budget_id'] ?? 0);
        $categoryName = trim($transaction['CategoryName'] ?? '');
        $quarterPeriod = trim($transaction['QuarterPeriod'] ?? '');
        $entryDate = $transaction['EntryDate'] ?? '';
        $year = $entryDate ? intval(date('Y', strtotime($entryDate))) : null;
        $cluster = null;

        // Fetch cluster from budget_data if budgetId exists
        if ($budgetId > 0) {
            $clusterQuery = "SELECT cluster FROM budget_data WHERE id = ?";
            $clusterStmt = $conn->prepare($clusterQuery);
            if (!$clusterStmt) {
                throw new Exception("Failed to prepare cluster query: " . $conn->error);
            }
            $clusterStmt->bind_param("i", $budgetId);
            $clusterStmt->execute();
            $clusterResult = $clusterStmt->get_result();
            if ($clusterRow = $clusterResult->fetch_assoc()) {
                $cluster = $clusterRow['cluster'];
            }
        }

        // FIRST: Rollback the transaction amount from budget calculations
        if ($budgetId > 0 && $amount > 0 && $year && $categoryName) {
            error_log("Rolling back budget data for budget ID: $budgetId, year: $year, category: $categoryName, cluster: " . ($cluster ?? 'NULL'));

            // Get current budget data
            $getBudgetQuery = "SELECT actual, budget, forecast, actual_plus_forecast, cluster FROM budget_data WHERE id = ?";
            $getBudgetStmt = $conn->prepare($getBudgetQuery);
            if (!$getBudgetStmt) {
                throw new Exception("Prepare failed for budget data lookup: " . $conn->error);
            }
            $getBudgetStmt->bind_param("i", $budgetId);
            $getBudgetStmt->execute();
            $budgetResult = $getBudgetStmt->get_result();

            if ($budgetResult->num_rows === 0) {
                throw new Exception("Budget data not found for ID: $budgetId");
            }

            $budgetData = $budgetResult->fetch_assoc();
            $currentActual = floatval($budgetData['actual'] ?? 0);
            $currentBudget = floatval($budgetData['budget'] ?? 0);

            // Subtract amount from actual (never go below 0) - this is the rollback
            $newActual = max(0, $currentActual - $amount);
            $newForecast = max(0, $currentBudget - $newActual);
            $newActualPlusForecast = $newActual + $newForecast;
            $newVariancePercentage = 0;
            if ($currentBudget > 0) {
                $newVariancePercentage = round((($currentBudget - $newActualPlusForecast) / $currentBudget) * 100, 2);
            } elseif ($currentBudget == 0 && $newActualPlusForecast > 0) {
                $newVariancePercentage = -100.00;
            }

            // Update the specific quarter row
            $updateBudgetQuery = "UPDATE budget_data SET 
                actual = ?, 
                forecast = ?, 
                actual_plus_forecast = ?, 
                variance_percentage = ? 
                WHERE id = ?";
            $updateStmt = $conn->prepare($updateBudgetQuery);
            if (!$updateStmt) {
                throw new Exception("Update prepare failed: " . $conn->error);
            }
            $updateStmt->bind_param("ddddi", $newActual, $newForecast, $newActualPlusForecast, $newVariancePercentage, $budgetId);
            if (!$updateStmt->execute()) {
                throw new Exception("Update execute failed: " . $updateStmt->error);
            }

            // ========== UPDATE ANNUAL TOTAL ROW ==========
            $updateAnnualQuery = "UPDATE budget_data SET actual = (
                SELECT COALESCE(SUM(actual), 0) FROM budget_data b 
                WHERE b.year2 = ? AND b.category_name = ? AND b.period_name IN ('Q1','Q2','Q3','Q4')";
            $params = [$year, $categoryName];
            $types = "is";

            if ($cluster) {
                $updateAnnualQuery .= " AND b.cluster = ?";
                $params[] = $cluster;
                $types .= "s";
            }
            $updateAnnualQuery .= "
            ) WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
            $params[] = $year;
            $params[] = $categoryName;
            $types .= "is";

            if ($cluster) {
                $updateAnnualQuery .= " AND cluster = ?";
                $params[] = $cluster;
                $types .= "s";
            }

            $updateAnnualStmt = $conn->prepare($updateAnnualQuery);
            if (!$updateAnnualStmt) {
                throw new Exception("Annual actual update prepare failed: " . $conn->error);
            }
            $updateAnnualStmt->bind_param($types, ...$params);
            if (!$updateAnnualStmt->execute()) {
                throw new Exception("Annual actual update failed: " . $updateAnnualStmt->error);
            }

            // ========== UPDATE ANNUAL FORECAST & ACTUAL+FORECAST ==========
            $updateAnnualForecastQuery = "UPDATE budget_data SET 
                forecast = (
                    SELECT COALESCE(SUM(forecast), 0) FROM budget_data b 
                    WHERE b.year2 = ? AND b.category_name = ? AND b.period_name IN ('Q1','Q2','Q3','Q4')";
            $params = [$year, $categoryName];
            $types = "is";

            if ($cluster) {
                $updateAnnualForecastQuery .= " AND b.cluster = ?";
                $params[] = $cluster;
                $types .= "s";
            }
            $updateAnnualForecastQuery .= "
                ), 
                actual_plus_forecast = (
                    SELECT COALESCE(SUM(actual), 0) + COALESCE(SUM(forecast), 0) FROM budget_data b 
                    WHERE b.year2 = ? AND b.category_name = ? AND b.period_name IN ('Q1','Q2','Q3','Q4')";
            $params[] = $year;
            $params[] = $categoryName;
            $types .= "is";

            if ($cluster) {
                $updateAnnualForecastQuery .= " AND b.cluster = ?";
                $params[] = $cluster;
                $types .= "s";
            }
            $updateAnnualForecastQuery .= "
                ) 
                WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
            $params[] = $year;
            $params[] = $categoryName;
            $types .= "is";

            if ($cluster) {
                $updateAnnualForecastQuery .= " AND cluster = ?";
                $params[] = $cluster;
                $types .= "s";
            }

            $updateAnnualForecastStmt = $conn->prepare($updateAnnualForecastQuery);
            if (!$updateAnnualForecastStmt) {
                throw new Exception("Annual forecast update prepare failed: " . $conn->error);
            }
            $updateAnnualForecastStmt->bind_param($types, ...$params);
            if (!$updateAnnualForecastStmt->execute()) {
                throw new Exception("Annual forecast update failed: " . $updateAnnualForecastStmt->error);
            }

            // ========== UPDATE VARIANCE FOR ALL RELEVANT ROWS ==========
            $updateVarianceQuery = "UPDATE budget_data SET variance_percentage = CASE 
                WHEN budget > 0 THEN ROUND(((budget - (COALESCE(actual,0) + COALESCE(forecast,0))) / budget) * 100, 2)
                WHEN budget = 0 AND (COALESCE(actual,0) + COALESCE(forecast,0)) > 0 THEN -100.00
                ELSE 0.00 
            END 
            WHERE year2 = ? AND category_name = ?";
            $varianceTypes = "is";
            $varianceParams = [$year, $categoryName];

            if ($cluster) {
                $updateVarianceQuery .= " AND cluster = ?";
                $varianceParams[] = $cluster;
                $varianceTypes .= "s";
            }

            $updateVarianceStmt = $conn->prepare($updateVarianceQuery);
            if (!$updateVarianceStmt) {
                throw new Exception("Variance update prepare failed: " . $conn->error);
            }
            $updateVarianceStmt->bind_param($varianceTypes, ...$varianceParams);
            if (!$updateVarianceStmt->execute()) {
                throw new Exception("Variance update failed: " . $updateVarianceStmt->error);
            }

            // ========== UPDATE TOTAL ROW (category = 'Total') ==========
            $updateTotalQuery = "UPDATE budget_data SET actual = (
                SELECT COALESCE(SUM(actual), 0) FROM budget_data b 
                WHERE b.year2 = ? AND b.period_name = 'Annual Total' AND b.category_name != 'Total'";
            $totalParams = [$year];
            $totalTypes = "i";

            if ($cluster) {
                $updateTotalQuery .= " AND b.cluster = ?";
                $totalParams[] = $cluster;
                $totalTypes .= "s";
            }
            $updateTotalQuery .= "
            ) WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
            $totalParams[] = $year;
            $totalTypes .= "i";

            if ($cluster) {
                $updateTotalQuery .= " AND cluster = ?";
                $totalParams[] = $cluster;
                $totalTypes .= "s";
            }

            $updateTotalStmt = $conn->prepare($updateTotalQuery);
            if (!$updateTotalStmt) {
                throw new Exception("Total actual update prepare failed: " . $conn->error);
            }
            $updateTotalStmt->bind_param($totalTypes, ...$totalParams);
            if (!$updateTotalStmt->execute()) {
                throw new Exception("Total actual update failed: " . $updateTotalStmt->error);
            }

            // ========== UPDATE FORECAST, ACTUAL+FORECAST, VARIANCE FOR TOTAL ROW ==========
            $updateTotalFullQuery = "UPDATE budget_data SET 
                forecast = (
                    SELECT COALESCE(SUM(forecast), 0) FROM budget_data b 
                    WHERE b.year2 = ? AND b.period_name = 'Annual Total' AND b.category_name != 'Total'";
            $totalFullParams = [$year];
            $totalFullTypes = "i";

            if ($cluster) {
                $updateTotalFullQuery .= " AND b.cluster = ?";
                $totalFullParams[] = $cluster;
                $totalFullTypes .= "s";
            }
            $updateTotalFullQuery .= "
                ),
                actual_plus_forecast = (
                    SELECT COALESCE(SUM(actual), 0) + COALESCE(SUM(forecast), 0) FROM budget_data b 
                    WHERE b.year2 = ? AND b.period_name = 'Annual Total' AND b.category_name != 'Total'";
            $totalFullParams[] = $year;
            $totalFullTypes .= "i";

            if ($cluster) {
                $updateTotalFullQuery .= " AND b.cluster = ?";
                $totalFullParams[] = $cluster;
                $totalFullTypes .= "s";
            }
            $updateTotalFullQuery .= "
                ),
                variance_percentage = CASE 
                    WHEN budget > 0 THEN ROUND((
                        (budget - (
                            (SELECT COALESCE(SUM(actual), 0) FROM budget_data b1 WHERE b1.year2 = ? AND b1.period_name = 'Annual Total' AND b1.category_name != 'Total'";
            $totalFullParams[] = $year;
            $totalFullTypes .= "i";

            if ($cluster) {
                $updateTotalFullQuery .= " AND b1.cluster = ?";
                $totalFullParams[] = $cluster;
                $totalFullTypes .= "s";
            }
            $updateTotalFullQuery .= ") + 
                            (SELECT COALESCE(SUM(forecast), 0) FROM budget_data b2 WHERE b2.year2 = ? AND b2.period_name = 'Annual Total' AND b2.category_name != 'Total'";
            $totalFullParams[] = $year;
            $totalFullTypes .= "i";

            if ($cluster) {
                $updateTotalFullQuery .= " AND b2.cluster = ?";
                $totalFullParams[] = $cluster;
                $totalFullTypes .= "s";
            }
            $updateTotalFullQuery .= "))
                        ) / budget) * 100, 2)
                    WHEN budget = 0 AND (
                        (SELECT COALESCE(SUM(actual), 0) FROM budget_data b3 WHERE b3.year2 = ? AND b3.period_name = 'Annual Total' AND b3.category_name != 'Total'";
            $totalFullParams[] = $year;
            $totalFullTypes .= "i";

            if ($cluster) {
                $updateTotalFullQuery .= " AND b3.cluster = ?";
                $totalFullParams[] = $cluster;
                $totalFullTypes .= "s";
            }
            $updateTotalFullQuery .= ") + 
                        (SELECT COALESCE(SUM(forecast), 0) FROM budget_data b4 WHERE b4.year2 = ? AND b4.period_name = 'Annual Total' AND b4.category_name != 'Total'";
            $totalFullParams[] = $year;
            $totalFullTypes .= "i";

            if ($cluster) {
                $updateTotalFullQuery .= " AND b4.cluster = ?";
                $totalFullParams[] = $cluster;
                $totalFullTypes .= "s";
            }
            $updateTotalFullQuery .= ")) > 0 THEN -100.00
                    ELSE 0.00 
                END
                WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
            $totalFullParams[] = $year;
            $totalFullTypes .= "i";

            if ($cluster) {
                $updateTotalFullQuery .= " AND cluster = ?";
                $totalFullParams[] = $cluster;
                $totalFullTypes .= "s";
            }

            $updateTotalFullStmt = $conn->prepare($updateTotalFullQuery);
            if (!$updateTotalFullStmt) {
                throw new Exception("Total full update prepare failed: " . $conn->error);
            }
            $updateTotalFullStmt->bind_param($totalFullTypes, ...$totalFullParams);
            if (!$updateTotalFullStmt->execute()) {
                throw new Exception("Total full update failed: " . $updateTotalFullStmt->error);
            }
        }

        // SECOND: Now delete the transaction
        $deleteQuery = "DELETE FROM budget_preview WHERE PreviewID = ?";
        error_log("Attempting to delete with query: $deleteQuery, ID: $transactionId");

        $deleteStmt = $conn->prepare($deleteQuery);
        if (!$deleteStmt) {
            throw new Exception("Delete prepare failed: " . $conn->error);
        }
        $deleteStmt->bind_param("i", $transactionId);
        if (!$deleteStmt->execute()) {
            throw new Exception("Delete execute failed: " . $deleteStmt->error);
        }

        if ($deleteStmt->affected_rows === 0) {
            throw new Exception("No transaction was deleted. ID may not exist.");
        }

        // Commit transaction
        $conn->commit();
        error_log("Transaction $transactionId deleted successfully and committed.");

        $response['success'] = true;
        $response['message'] = "Transaction deleted successfully and budget data updated.";

    } catch (Exception $e) {
        // Rollback on any error
        $conn->rollback();
        $errorMessage = "Delete failed: " . $e->getMessage();
        error_log($errorMessage);
        error_log("Stack trace: " . $e->getTraceAsString());
        $response['message'] = $errorMessage;
    }

} else {
    $response['message'] = "No transaction ID provided.";
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
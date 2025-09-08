<?php 
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if this file is being included or accessed directly
$included = defined('INCLUDED_FROM_INDEX');

if (!$included) {
    include 'header.php'; 
}
?>

<?php
// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Get current year for calculations
$currentYear = date('Y');

// Get user cluster information
$userCluster = $_SESSION['cluster_name'] ?? null;

// Calculate total spent from Budget_Preview table (filtered by cluster if available)
if ($userCluster) {
    $totalSpentQuery = "SELECT SUM(Amount) as total_spent, COUNT(*) as transaction_count FROM budget_preview WHERE YEAR(EntryDate) = ? AND cluster = ?";
    $stmt = $conn->prepare($totalSpentQuery);
    $stmt->bind_param("is", $currentYear, $userCluster);
} else {
    $totalSpentQuery = "SELECT SUM(Amount) as total_spent, COUNT(*) as transaction_count FROM budget_preview WHERE YEAR(EntryDate) = ?";
    $stmt = $conn->prepare($totalSpentQuery);
    $stmt->bind_param("i", $currentYear);
}
$stmt->execute();
$spentResult = $stmt->get_result();
$spentData = $spentResult->fetch_assoc();
$totalSpent = $spentData['total_spent'] ?? 0;
$transactionCount = $spentData['transaction_count'] ?? 0;

// Calculate data for the past 4 months
$currentMonth = date('Y-m');
$prevMonth1 = date('Y-m', strtotime('-1 month'));
$prevMonth2 = date('Y-m', strtotime('-2 months'));
$prevMonth3 = date('Y-m', strtotime('-3 months'));

if ($userCluster) {
    // Current month data
    $currentMonthQuery = "SELECT SUM(Amount) as current_spent, COUNT(*) as current_count FROM budget_preview WHERE DATE_FORMAT(EntryDate, '%Y-%m') = ? AND cluster = ?";
    $stmt = $conn->prepare($currentMonthQuery);
    $stmt->bind_param("ss", $currentMonth, $userCluster);
    $stmt->execute();
    $currentResult = $stmt->get_result();
    $currentData = $currentResult->fetch_assoc();
    $currentSpent = $currentData['current_spent'] ?? 0;
    $currentCount = $currentData['current_count'] ?? 0;
    
    // Previous 3 months data
    $prevMonthsQuery = "SELECT SUM(Amount) as prev_spent, COUNT(*) as prev_count FROM budget_preview WHERE DATE_FORMAT(EntryDate, '%Y-%m') IN (?, ?, ?) AND cluster = ?";
    $stmt = $conn->prepare($prevMonthsQuery);
    $stmt->bind_param("ssss", $prevMonth1, $prevMonth2, $prevMonth3, $userCluster);
    $stmt->execute();
    $prevResult = $stmt->get_result();
    $prevData = $prevResult->fetch_assoc();
    $prevSpent = $prevData['prev_spent'] ?? 0;
    $prevCount = $prevData['prev_count'] ?? 0;
} else {
    // Current month data
    $currentMonthQuery = "SELECT SUM(Amount) as current_spent, COUNT(*) as current_count FROM budget_preview WHERE DATE_FORMAT(EntryDate, '%Y-%m') = ?";
    $stmt = $conn->prepare($currentMonthQuery);
    $stmt->bind_param("s", $currentMonth);
    $stmt->execute();
    $currentResult = $stmt->get_result();
    $currentData = $currentResult->fetch_assoc();
    $currentSpent = $currentData['current_spent'] ?? 0;
    $currentCount = $currentData['current_count'] ?? 0;
    
    // Previous 3 months data
    $prevMonthsQuery = "SELECT SUM(Amount) as prev_spent, COUNT(*) as prev_count FROM budget_preview WHERE DATE_FORMAT(EntryDate, '%Y-%m') IN (?, ?, ?)";
    $stmt = $conn->prepare($prevMonthsQuery);
    $stmt->bind_param("ssss", $prevMonth1, $prevMonth2, $prevMonth3);
    $stmt->execute();
    $prevResult = $stmt->get_result();
    $prevData = $prevResult->fetch_assoc();
    $prevSpent = $prevData['prev_spent'] ?? 0;
    $prevCount = $prevData['prev_count'] ?? 0;
}

// Calculate totals for past 4 months
$fourMonthsSpent = $currentSpent + $prevSpent;
$fourMonthsCount = $currentCount + $prevCount;

// Calculate percentage changes
$spentChange = $prevSpent > 0 ? (($currentSpent - $prevSpent) / $prevSpent) * 100 : 0;
$transactionChange = $prevCount > 0 ? (($currentCount - $prevCount) / $prevCount) * 100 : 0;

// Get total budget and actual spent from budget_preview table (filtered by cluster if available)
// As per project specification, we should use data directly from budget_preview table
if ($userCluster) {
    $budgetQuery = "SELECT SUM(OriginalBudget) as total_budget, SUM(ActualSpent) as total_actual_spent FROM budget_preview WHERE cluster = ?";
    $stmt = $conn->prepare($budgetQuery);
    $stmt->bind_param("s", $userCluster);
} else {
    $budgetQuery = "SELECT SUM(OriginalBudget) as total_budget, SUM(ActualSpent) as total_actual_spent FROM budget_preview";
    $stmt = $conn->prepare($budgetQuery);
}
$stmt->execute();
$budgetResult = $stmt->get_result();
$budgetData = $budgetResult->fetch_assoc();
$totalBudget = $budgetData['total_budget'] ?? 0; // Total allocated budget from OriginalBudget column
$totalActualSpent = $budgetData['total_actual_spent'] ?? 0; // Total actually spent from ActualSpent column

// Calculate remaining budget and utilization
$remainingBudget = $totalBudget - $totalActualSpent;
$budgetUtilization = $totalBudget > 0 ? ($totalActualSpent / $totalBudget) * 100 : 0;

// Function to determine quarter from date based on database date ranges
function getQuarterFromDate($date, $conn, $year = null, $categoryName = '1. Administrative costs') {
    if (!$year) $year = date('Y', strtotime($date));
    
    // Get user cluster from session
    session_start();
    $userCluster = $_SESSION['cluster_name'] ?? null;
    
    $quarterQuery = "SELECT period_name FROM budget_data 
                   WHERE year2 = ? AND category_name = ? 
                   AND period_name IN ('Q1', 'Q2', 'Q3', 'Q4')
                   AND ? BETWEEN start_date AND end_date";
    
    // Add cluster condition if user has a cluster
    if ($userCluster) {
        $quarterQuery .= " AND cluster = ?";
    }
    
    $quarterQuery .= " LIMIT 1";
    
    $stmt = $conn->prepare($quarterQuery);
    
    // Bind parameters based on whether user has a cluster
    if ($userCluster) {
        $stmt->bind_param("isss", $year, $categoryName, $date, $userCluster);
    } else {
        $stmt->bind_param("iss", $year, $categoryName, $date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    return $data['period_name'] ?? 'Q1'; // default fallback
}

// Helper function to map category names
function mapCategoryName($category) {
    $categoryMappings = [
        'Administrative costs' => '1. Administrative costs',
        'Operational support costs' => '2. Operational support costs',
        'Consortium Activities' => '3. Consortium Activities',
        'Targeting new CSOs' => '4. Targeting new CSOs',
        'Contingency' => '5. Contingency'
    ];
    
    return $categoryMappings[$category] ?? $category;
}

// Helper function to map subcategory names
function mapSubcategoryName($category) {
    // For now, return the category as subcategory
    // This can be expanded later if needed
    return $category;
}

// AJAX Handler for saving transactions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_transaction') {
    error_log('AJAX Handler - Save Transaction Request Received');
    
    // Validate and sanitize inputs
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    if ($amount === false || $amount <= 0) {
        handleError('Invalid amount', 'Please enter a valid positive amount');
    }
    
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    if (empty($category)) {
        handleError('Invalid category', 'Please select a valid category');
    }
    
    $entryDate = filter_input(INPUT_POST, 'entry_date', FILTER_SANITIZE_STRING);
    if (empty($entryDate) || !DateTime::createFromFormat('Y-m-d', $entryDate)) {
        handleError('Invalid date', 'Please enter a valid date in YYYY-MM-DD format');
    }
    
    $entryDateTime = DateTime::createFromFormat('Y-m-d', $entryDate);
    $entryYear = (int)$entryDateTime->format('Y');

    // Map category to proper name if necessary
    $mappedCategoryName = mapCategoryName($category);
    if (empty($mappedCategoryName)) {
        handleError('Category mapping error', 'Selected category could not be mapped to a valid category name');
    }
    
    // Map category to proper subcategory if necessary
    $mappedSubcategoryName = mapSubcategoryName($category);


    // Start transaction
    if ($conn->begin_transaction()) {
        try {
            // Insert into budget_preview table
            $insertQuery = "INSERT INTO budget_preview (Amount, Category, EntryDate, user_id, cluster, subcategory) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("dsisss", $amount, $mappedCategoryName, $entryDate, $_SESSION['user_id'], $userCluster, $mappedSubcategoryName);
            if ($stmt->execute()) {
                $insertId = $stmt->insert_id;
                error_log('AJAX Handler - Transaction inserted into budget_preview with ID: ' . $insertId);
                
                // Update the budget_data table with proper filtering by date range, cluster, category, and quarter
                error_log('AJAX Handler - Updating budget_data table');
                
                // We already have the mapped category name and quarter from above
                $categoryName = $mappedCategoryName;
                $quarter = $quarterPeriod;
                $year = $entryYear;
                $transactionDate = $entryDateTime->format('Y-m-d');
                
                // Check if there's enough budget available for this transaction with proper filtering
                $budgetCheckQuery = "SELECT budget, actual, forecast, id FROM budget_data 
                                               WHERE year2 = ? AND category_name = ? 
                                               AND period_name = ?
                                               AND ? BETWEEN start_date AND end_date";
                            
                            // Add cluster condition if user has a cluster
                            if ($userCluster) {
                                $budgetCheckQuery .= " AND cluster = ?";
                                $budgetCheckStmt = $conn->prepare($budgetCheckQuery);
                                $budgetCheckStmt->bind_param("issss", $year, $categoryName, $quarter, $transactionDate, $userCluster);
                            } else {
                                $budgetCheckStmt = $conn->prepare($budgetCheckQuery);
                                $budgetCheckStmt->bind_param("isss", $year, $categoryName, $quarter, $transactionDate);
                            }
                            
                            $budgetCheckStmt->execute();
                            $budgetCheckResult = $budgetCheckStmt->get_result();
                            $budgetCheckData = $budgetCheckResult->fetch_assoc();
                            
                            // Get the budget_id for linking to budget_preview
                            $budgetId = $budgetCheckData['id'] ?? null;

                            // Remaining available = budget - actual (handle NULLs) - forecast is future expectation, not committed spending
                            $availableBudget = max((float)($budgetCheckData['budget'] ?? 0) - (float)($budgetCheckData['actual'] ?? 0), 0);
                            
                            if ($amount > $availableBudget) {
                                handleError('Insufficient budget available', 
                                    "Transaction amount (" . number_format($amount, 2) . ") exceeds available budget (" . number_format($availableBudget, 2) . ") for $categoryName in $quarter $year");
                            }
                            
                            // Update the quarter row: increase actual by amount, auto-adjust forecast to remaining budget, recompute actual_plus_forecast
                            // MySQL evaluates SET clauses left to right, so later expressions see updated column values
                            // We need to calculate forecast based on original actual value to avoid compounding errors
                            $updateBudgetQuery = "UPDATE budget_data SET 
                                actual = COALESCE(actual, 0) + ?,
                                forecast = GREATEST(COALESCE(budget, 0) - COALESCE(actual, 0) - ?, 0),
                                actual_plus_forecast = COALESCE(actual, 0) + COALESCE(forecast, 0)
                                WHERE year2 = ? AND category_name = ? AND period_name = ?
                                AND ? BETWEEN start_date AND end_date";
                            
                            // Add cluster condition if user has a cluster
                            if ($userCluster) {
                                $updateBudgetQuery .= " AND cluster = ?";
                                $updateStmt = $conn->prepare($updateBudgetQuery);
                                // Params: 2 doubles (amount), 1 integer (year), 3 strings (categoryName, quarter, transactionDate), 1 string (userCluster)
                                $updateStmt->bind_param("ddissss", $amount, $amount, $year, $categoryName, $quarter, $transactionDate, $userCluster);
                            } else {
                                $updateStmt = $conn->prepare($updateBudgetQuery);
                                // Params: 2 doubles (amount), 1 integer (year), 3 strings (categoryName, quarter, transactionDate)
                                $updateStmt->bind_param("ddisss", $amount, $amount, $year, $categoryName, $quarter, $transactionDate);
                            }
                            
                            if ($updateStmt->execute()) {
                                error_log('AJAX Handler - Updated quarter budget and actual amounts');
                                
                                // Update the Annual Total row for this category by summing all quarters with cluster consideration
                                $updateAnnualQuery = "UPDATE budget_data 
                                    SET budget = (
                                        SELECT SUM(COALESCE(budget, 0)) 
                                        FROM budget_data b2 
                                        WHERE b2.year2 = ? AND b2.category_name = ? AND b2.period_name IN ('Q1', 'Q2', 'Q3', 'Q4')";

                                // Add cluster condition if user has a cluster (subquery)
                                if ($userCluster) {
                                    $updateAnnualQuery .= " AND b2.cluster = ?";
                                }

                                // Target only the Annual Total row for this category/year (and cluster)
                                $updateAnnualQuery .= ") WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                                if ($userCluster) {
                                    $updateAnnualQuery .= " AND cluster = ?";
                                    $annualStmt = $conn->prepare($updateAnnualQuery);
                                    // Params: subquery (year, category, cluster), outer where (year, category, cluster)
                                    $annualStmt->bind_param("ississ", $year, $categoryName, $userCluster, $year, $categoryName, $userCluster);
                                } else {
                                    $annualStmt = $conn->prepare($updateAnnualQuery);
                                    // Params: subquery (year, category), outer where (year, category)
                                    $annualStmt->bind_param("isis", $year, $categoryName, $year, $categoryName);
                                }

                                $annualStmt->execute();
                                
                                // Update actual for Annual Total with cluster consideration
                                $updateActualQuery = "UPDATE budget_data 
                                    SET actual = (
                                        SELECT SUM(COALESCE(actual, 0)) 
                                        FROM budget_data b3 
                                        WHERE b3.year2 = ? AND b3.category_name = ? AND b3.period_name IN ('Q1', 'Q2', 'Q3', 'Q4')";

                                if ($userCluster) {
                                    $updateActualQuery .= " AND b3.cluster = ?";
                                }

                                $updateActualQuery .= ") WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                                if ($userCluster) {
                                    $updateActualQuery .= " AND cluster = ?";
                                    $actualStmt = $conn->prepare($updateActualQuery);
                                    $actualStmt->bind_param("ississ", $year, $categoryName, $userCluster, $year, $categoryName, $userCluster);
                                } else {
                                    $actualStmt = $conn->prepare($updateActualQuery);
                                    $actualStmt->bind_param("isis", $year, $categoryName, $year, $categoryName);
                                }

                                $actualStmt->execute();
                                
                                // Update forecast for Annual Total with cluster consideration
                                $updateForecastQuery = "UPDATE budget_data 
                                    SET forecast = (
                                        SELECT SUM(COALESCE(forecast, 0)) 
                                        FROM budget_data b4 
                                        WHERE b4.year2 = ? AND b4.category_name = ? AND b4.period_name IN ('Q1', 'Q2', 'Q3', 'Q4')";

                                if ($userCluster) {
                                    $updateForecastQuery .= " AND b4.cluster = ?";
                                }

                                $updateForecastQuery .= ") WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                                if ($userCluster) {
                                    $updateForecastQuery .= " AND cluster = ?";
                                    $forecastStmt = $conn->prepare($updateForecastQuery);
                                    $forecastStmt->bind_param("ississ", $year, $categoryName, $userCluster, $year, $categoryName, $userCluster);
                                } else {
                                    $forecastStmt = $conn->prepare($updateForecastQuery);
                                    $forecastStmt->bind_param("isis", $year, $categoryName, $year, $categoryName);
                                }

                                $forecastStmt->execute();
                                
                                // Removed invalid standalone WHERE statement that caused SQL syntax error
                                
                                // Update actual_plus_forecast for Annual Total with cluster consideration
                                $updateAnnualForecastQuery = "UPDATE budget_data 
                                    SET actual_plus_forecast = COALESCE(actual, 0) + COALESCE(forecast, 0)
                                    WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                                
                                // Add cluster condition if user has a cluster
                                if ($userCluster) {
                                    $updateAnnualForecastQuery .= " AND cluster = ?";
                                    $annualForecastStmt = $conn->prepare($updateAnnualForecastQuery);
                                    $annualForecastStmt->bind_param("iss", $year, $categoryName, $userCluster);
                                } else {
                                    $annualForecastStmt = $conn->prepare($updateAnnualForecastQuery);
                                    $annualForecastStmt->bind_param("is", $year, $categoryName);
                                }
                                $annualForecastStmt->execute();
                                
                                // Update the Total row across all categories with cluster consideration
                                $updateTotalQuery = "UPDATE budget_data 
                                    SET budget = (
                                        SELECT SUM(COALESCE(budget, 0)) 
                                        FROM budget_data b2 
                                        WHERE b2.year2 = ? AND b2.period_name = 'Annual Total' AND b2.category_name != 'Total'";

                                if ($userCluster) {
                                    $updateTotalQuery .= " AND b2.cluster = ?";
                                }

                                $updateTotalQuery .= ") WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                                if ($userCluster) {
                                    $updateTotalQuery .= " AND cluster = ?";
                                    $totalBudgetStmt = $conn->prepare($updateTotalQuery);
                                    // Params: subquery (year, cluster), outer where (year, cluster)
                                    $totalBudgetStmt->bind_param("isis", $year, $userCluster, $year, $userCluster);
                                } else {
                                    $totalBudgetStmt = $conn->prepare($updateTotalQuery);
                                    // Params: subquery (year), outer where (year)
                                    $totalBudgetStmt->bind_param("ii", $year, $year);
                                }
                                $totalBudgetStmt->execute();
                                
                                // Update actual for Total with cluster consideration
                                $updateTotalActualQuery = "UPDATE budget_data 
                                    SET actual = (
                                        SELECT SUM(COALESCE(actual, 0)) 
                                        FROM budget_data b3 
                                        WHERE b3.year2 = ? AND b3.period_name = 'Annual Total' AND b3.category_name != 'Total'";

                                if ($userCluster) {
                                    $updateTotalActualQuery .= " AND b3.cluster = ?";
                                }

                                $updateTotalActualQuery .= ") WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                                if ($userCluster) {
                                    $updateTotalActualQuery .= " AND cluster = ?";
                                    $totalActualStmt = $conn->prepare($updateTotalActualQuery);
                                    $totalActualStmt->bind_param("isis", $year, $userCluster, $year, $userCluster);
                                } else {
                                    $totalActualStmt = $conn->prepare($updateTotalActualQuery);
                                    $totalActualStmt->bind_param("ii", $year, $year);
                                }
                                $totalActualStmt->execute();
                                
                                // Update forecast for Total with cluster consideration
                                $updateTotalForecastQuery = "UPDATE budget_data 
                                    SET forecast = (
                                        SELECT SUM(COALESCE(forecast, 0)) 
                                        FROM budget_data b4 
                                        WHERE b4.year2 = ? AND b4.period_name = 'Annual Total' AND b4.category_name != 'Total'";

                                if ($userCluster) {
                                    $updateTotalForecastQuery .= " AND b4.cluster = ?";
                                }

                                $updateTotalForecastQuery .= ") WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                                if ($userCluster) {
                                    $updateTotalForecastQuery .= " AND cluster = ?";
                                    $totalForecastStmt = $conn->prepare($updateTotalForecastQuery);
                                    $totalForecastStmt->bind_param("isis", $year, $userCluster, $year, $userCluster);
                                } else {
                                    $totalForecastStmt = $conn->prepare($updateTotalForecastQuery);
                                    $totalForecastStmt->bind_param("ii", $year, $year);
                                }
                                $totalForecastStmt->execute();
                                
                                // Removed invalid standalone WHERE statement for Total row
                                
                                // Update actual_plus_forecast for Total with cluster consideration
                                $updateTotalActualForecastQuery = "UPDATE budget_data 
                                    SET actual_plus_forecast = COALESCE(actual, 0) + COALESCE(forecast, 0)
                                    WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                                
                                // Add cluster condition if user has a cluster
                                if ($userCluster) {
                                    $updateTotalActualForecastQuery .= " AND cluster = ?";
                                    $totalActualForecastStmt = $conn->prepare($updateTotalActualForecastQuery);
                                    $totalActualForecastStmt->bind_param("is", $year, $userCluster);
                                } else {
                                    $totalActualForecastStmt = $conn->prepare($updateTotalActualForecastQuery);
                                    $totalActualForecastStmt->bind_param("i", $year);
                                }
                                $totalActualForecastStmt->execute();
                                
                                // Update actual_plus_forecast for all quarter rows as well with cluster consideration
                                $updateQuarterForecastQuery = "UPDATE budget_data 
                                    SET actual_plus_forecast = COALESCE(actual, 0) + COALESCE(forecast, 0)
                                    WHERE year2 = ? AND period_name IN ('Q1', 'Q2', 'Q3', 'Q4')";
                                
                                // Add cluster condition if user has a cluster
                                if ($userCluster) {
                                    $updateQuarterForecastQuery .= " AND cluster = ?";
                                    $quarterForecastStmt = $conn->prepare($updateQuarterForecastQuery);
                                    $quarterForecastStmt->bind_param("is", $year, $userCluster);
                                } else {
                                    $quarterForecastStmt = $conn->prepare($updateQuarterForecastQuery);
                                    $quarterForecastStmt->bind_param("i", $year);
                                }
                                $quarterForecastStmt->execute();
                                
                                // Calculate and update variance percentages for all rows with cluster consideration
                                // Variance = ((Budget - (Actual + Forecast)) / Budget) * 100
                                $varianceQuery = "UPDATE budget_data 
                                    SET variance_percentage = CASE 
                                        WHEN budget > 0 THEN ROUND(((budget - (COALESCE(actual,0) + COALESCE(forecast,0))) / budget) * 100, 2)
                                        WHEN budget = 0 AND (COALESCE(actual,0) + COALESCE(forecast,0)) > 0 THEN -100.00
                                        ELSE 0.00 
                                    END
                                    WHERE year2 = ?";
                                
                                // Add cluster condition if user has a cluster
                                if ($userCluster) {
                                    $varianceQuery .= " AND cluster = ?";
                                    $varianceStmt = $conn->prepare($varianceQuery);
                                    $varianceStmt->bind_param("is", $year, $userCluster);
                                } else {
                                    $varianceStmt = $conn->prepare($varianceQuery);
                                    $varianceStmt->bind_param("i", $year);
                                }
                                $varianceStmt->execute();
                                
                                error_log('AJAX Handler - Updated all related budget calculations including budget reduction');
                            } else {
                                error_log('AJAX Handler - Failed to update budget_data: ' . $updateStmt->error);
                            }
                            
                            // Mark budget data as uncertified when new transaction is added with cluster consideration
                            $uncertifyQuery = "UPDATE budget_data SET certified = 'uncertified' WHERE year2 = ?";
                            
                            // Add cluster condition if user has a cluster
                            if ($userCluster) {
                                $uncertifyQuery .= " AND cluster = ?";
                                $uncertifyStmt = $conn->prepare($uncertifyQuery);
                                $uncertifyStmt->bind_param("is", $year, $userCluster);
                            } else {
                                $uncertifyStmt = $conn->prepare($uncertifyQuery);
                                $uncertifyStmt->bind_param("i", $year);
                            }
                            
                            if ($uncertifyStmt->execute()) {
                                error_log('AJAX Handler - Budget marked as uncertified due to new transaction for year: ' . $year);
                            } else {
                                error_log('AJAX Handler - Failed to mark budget as uncertified: ' . $uncertifyStmt->error);
                            }
                            
                            // Update the budget_preview table with the budget_id for proper linking
                            if ($budgetId) {
                                $updatePreviewQuery = "UPDATE budget_preview SET budget_id = ? WHERE PreviewID = ?";
                                $updatePreviewStmt = $conn->prepare($updatePreviewQuery);
                                $updatePreviewStmt->bind_param("ii", $budgetId, $insertId);
                                if ($updatePreviewStmt->execute()) {
                                    error_log('AJAX Handler - Linked budget_preview record to budget_data record with ID: ' . $budgetId);
                                } else {
                                    error_log('AJAX Handler - Failed to link budget_preview to budget_data: ' . $updatePreviewStmt->error);
                                }
                            }
                            
                            $response = [
                                'success' => true, 
                                'message' => 'Transaction saved successfully! ID: ' . $insertId,
                                'transaction_id' => $insertId
                            ];
                            error_log('AJAX Handler - Success: Transaction ID ' . $insertId);
                            
                            // Ensure clean output
                            ob_clean();
                            echo json_encode($response);
                            exit;
                        } else {
                            handleError('Database error', 'Failed to insert transaction into budget_preview: ' . $stmt->error);
                        }
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $conn->rollback();
                        handleError('Database error', 'Error during transaction: ' . $e->getMessage());
                    }
                } else {
                    handleError('Database error', 'Failed to start transaction');
                }
            }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Dashboard | Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap');
        
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7b68ee;
            --accent: #10b981;
            --light-bg: #f8fafc;
            --dark-text: #1e293b;
            --mid-text: #64748b;
            --light-text: #94a3b8;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 5px 10px -5px rgba(0, 0, 0, 0.02);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-bg) 0%, #f1f5f9 100%);
            color: var(--dark-text);
            min-height: 100vh;
        }
        
        .heading-font {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        .main-content-flex {
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
            min-height: calc(100vh - 80px);
        }

        .content-container {
            width: 100%;
            max-width: 1200px;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            box-shadow: var(--card-shadow);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .modern-border {
            border: 2px solid #2563eb;
            border-radius: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1);
        }
        
        .form-input {
            transition: all 0.3s;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            width: 100%;
            font-size: 0.95rem;
            background: #ffffff;
            position: relative;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: #fefefe;
        }
        
        .form-input:hover {
            border-color: #cbd5e1;
        }
        
        .form-label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            letter-spacing: 0.025em;
        }
        
        .form-label::after {
            content: ' *';
            color: #ef4444;
            font-weight: 500;
        }
        
        .form-section {
            background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%);
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(37, 99, 235, 0.1);
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(107, 114, 128, 0.2);
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(107, 114, 128, 0.3);
        }
        
        .btn-accent {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
        }
        
        .btn-accent:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.7s ease-out forwards;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .toast {
            animation: toastIn 0.5s ease, toastOut 0.5s ease 2.5s forwards;
        }
        
        @keyframes toastIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes toastOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .table-row {
            transition: all 0.2s;
        }
        
        .table-row:hover {
            background-color: #f8fafc;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%);
            border-radius: 1.5rem;
            padding: 1.25rem;
            box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.08), 0 4px 10px -3px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(37, 99, 235, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: #2563eb;
            transform: scaleX(0);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: left;
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px -10px rgba(37, 99, 235, 0.2), 0 15px 25px -8px rgba(0, 0, 0, 0.1);
            border-color: rgba(37, 99, 235, 0.2);
        }
        
        .stat-card-content {
            position: relative;
            z-index: 2;
        }
        
        .document-btn {
            transition: all 0.3s;
            border: 2px dashed #cbd5e1;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }
        
        .document-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .document-btn:hover::before {
            left: 100%;
        }
        
        .document-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(37, 99, 235, 0.1) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(37, 99, 235, 0.2);
        }
        
        .section-title {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -0.75rem;
            left: 0;
            width: 60%;
            height: 4px;
            background: #2563eb;
            border-radius: 2px;
        }
        
        .section-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.125rem;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%);
            border-radius: 1.5rem;
            padding: 2.5rem;
            border: 2px solid #2563eb;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2563eb, #3b82f6, #2563eb);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { background-position: 200% 0; }
            50% { background-position: -200% 0; }
        }
        
        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .dashboard-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: #2563eb;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.3);
        }
        
        .dashboard-subtitle {
            color: #64748b;
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .metric-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 6px 12px -3px rgba(0, 0, 0, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .metric-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s ease;
        }
        
        .stat-card:hover .metric-icon::before {
            left: 100%;
        }
        
        .stat-card:hover .metric-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.2);
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 900;
            color: #1e293b;
            margin-top: 0.5rem;
            letter-spacing: -0.05em;
            line-height: 1;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .metric-value {
            color: #2563eb;
            transform: scale(1.05);
        }
        
        .metric-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .metric-label::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #2563eb;
            transition: width 0.3s ease;
        }
        
        .stat-card:hover .metric-label::after {
            width: 40%;
        }
        
        .metric-change {
            margin-top: 1rem;
            padding: 0.5rem 0.75rem;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border-radius: 0.5rem;
            border: 1px solid #bbf7d0;
            position: relative;
            overflow: hidden;
        }
        
        .metric-change::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(34, 197, 94, 0.1), transparent);
            transition: left 0.8s ease;
        }
        
        .stat-card:hover .metric-change::before {
            left: 100%;
        }
        
        .metric-change-text {
            color: #15803d;
            font-size: 0.875rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .metric-change-icon {
            animation: bounce-up 2s ease-in-out infinite;
        }
        
        @keyframes bounce-up {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-4px);
            }
            60% {
                transform: translateY(-2px);
            }
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #f1f5f9;
            border-radius: 1rem;
            margin-top: 1rem;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2563eb 0%, #3b82f6 50%, #2563eb 100%);
            background-size: 200% 100%;
            border-radius: 1rem;
            transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: gradient-flow 3s ease-in-out infinite;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: progress-shine 2.5s ease-in-out infinite;
        }
        
        @keyframes gradient-flow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        @keyframes progress-shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .progress-info {
            margin-top: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .progress-text {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 600;
        }
        
        .progress-percentage {
            font-size: 0.875rem;
            color: #2563eb;
            font-weight: 700;
            background: #eff6ff;
            padding: 0.25rem 0.5rem;
            border-radius: 0.5rem;
        }
        
        .preview-item {
            display: flex;
            justify-content: between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .preview-label {
            font-weight: 600;
            color: var(--mid-text);
            min-width: 40%;
        }
        
        .preview-value {
            color: var(--dark-text);
            text-align: right;
            flex-grow: 1;
            word-break: break-word;
        }
        
        .doc-preview {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border: 1px dashed #cbd5e1;
        }
        
        .doc-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0;
            color: var(--primary);
            overflow: hidden;
        }
        
        .doc-item span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
    </style>
</head>
<body>

<div class="main-content-flex">
    <div class="content-container">
        <div class="glass-card p-6 md:p-8 card-hover animate-fadeIn mb-8">
            <h1 class="text-4xl font-bold heading-font text-gray-800 mb-2 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-chart-pie text-blue-600"></i>
                </div>
                Financial Dashboard
            </h1>
            <p class="text-gray-500 text-lg mb-1">Track and manage your consortium's financial performance</p>
            
            <div class="stats-container mt-6">
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="metric-label">Total Spent (Past 4 Months)</p>
                                <h3 class="metric-value"><i class="fas fa-money-bill-wave text-green-600 mr-1"></i><?php echo number_format($fourMonthsSpent, 2); ?></h3>
                            </div>
                            <div class="metric-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                        </div>
                        <div class="metric-change">
                            <div class="metric-change-text">
                                <span>from <?php echo date('F', strtotime('-3 months')); ?> to <?php echo date('F'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="metric-label">Transactions (Past 4 Months)</p>
                                <h3 class="metric-value"><?php echo $fourMonthsCount; ?></h3>
                            </div>
                            <div class="metric-icon bg-green-100 text-green-600">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                        </div>
                        <div class="metric-change">
                            <div class="metric-change-text">
                                <span>from <?php echo date('F', strtotime('-3 months')); ?> to <?php echo date('F'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Budget Utilization card removed as requested -->
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="glass-card p-6 md:p-8 card-hover animate-fadeIn mb-8 modern-border">
                    <div class="form-section">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-6 section-title">
                            <div class="section-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            Add New Transaction
                        </h3>
                        <form id="transactionForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <label for="budgetHeadingSelect" class="form-label">Budget Heading</label>
                                    <select id="budgetHeadingSelect" class="form-input" required>
                                        <option value="">Select Budget Heading</option>
                                        <option value="Administrative costs">1. Administrative costs</option>
                                        <option value="Operational support costs">2. Operational support costs</option>
                                        <option value="Consortium Activities">3. Consortium Activities</option>
                                        <option value="Targeting new CSOs">4. Targeting new CSOs</option>
                                        <option value="Contingency">5. Contingency</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="outcomeInput" class="form-label">Outcome</label>
                                    <input type="text" id="outcomeInput" class="form-input" placeholder="e.g., Project goal achieved" required>
                                </div>
                                <div>
                                    <label for="activityInput" class="form-label">Activity</label>
                                    <input type="text" id="activityInput" class="form-input" placeholder="e.g., Workshop organization" required>
                                </div>
                                <div>
                                    <label for="budgetLineInput" class="form-label">Budget Line</label>
                                    <input type="text" id="budgetLineInput" class="form-input" placeholder="e.g., Travel Expenses" required>
                                </div>
                                <div>
                                    <label for="transactionDescriptionInput" class="form-label">Transaction Description</label>
                                    <input type="text" id="transactionDescriptionInput" class="form-input" placeholder="e.g., Air tickets for training" required>
                                </div>
                                <div>
                                    <label for="partnerInput" class="form-label">Partner</label>
                                    <input type="text" id="partnerInput" class="form-input" placeholder="e.g., ABC Organization" required>
                                </div>
                                <div>
                                    <label for="transactionDateInput" class="form-label">Date</label>
                                    <input type="date" id="transactionDateInput" class="form-input" required>
                                </div>
                                <div>
                                    <label for="amountInput" class="form-label">Amount</label>
                                    <input type="number" id="amountInput" class="form-input" placeholder="e.g., 1500" required>
                                </div>
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 mb-4">
                                    <p class="text-sm text-gray-600 flex items-center gap-2">
                                        <i class="fas fa-info-circle text-blue-500"></i>
                                        Upload supporting documents to complete your transaction record
                                    </p>
                                </div>
                                <button type="button" id="supportingDocumentsButton" class="document-btn w-full">
                                    <i class="fas fa-paperclip text-lg"></i> 
                                    <span>Attach Supporting Documents</span>
                                    <i class="fas fa-arrow-right ml-auto text-sm opacity-60"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="lg:col-span-1">
                <div class="glass-card p-6 md:p-8 card-hover animate-fadeIn h-full">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-6 section-title">Actions</h3>
                    
                    <div class="space-y-4">
                        
      
                        
                        <button type="button" id="clearFormButton" 
    class="btn-secondary w-full flex items-center justify-center gap-2">
    <i class="fas fa-eraser"></i> Clear Form
</button>

                        
                       <button type="button" id="historyButton" 
    onclick="window.location.href='history.php'" 
    class="btn-primary w-full flex items-center justify-center gap-2">
    <i class="fas fa-history"></i> Transaction History
</button>

                    </div>
                    
                    <div class="mt-8 p-6 bg-blue-50 rounded-xl border border-blue-100">
                        <h4 class="font-medium text-blue-800 mb-4 flex items-center gap-2 text-lg">
                            <i class="fas fa-eye"></i> Live Preview
                        </h4>
                        <div class="text-sm">
                            <div class="preview-item">
                                <span class="preview-label">Budget Heading:</span>
                                <span class="preview-value" id="previewHeading">--</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Outcome:</span>
                                <span class="preview-value" id="previewOutcome">--</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Activity:</span>
                                <span class="preview-value" id="previewActivity">--</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Budget Line:</span>
                                <span class="preview-value" id="previewBudgetLine">--</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Description:</span>
                                <span class="preview-value" id="previewDescription">--</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Partner:</span>
                                <span class="preview-value" id="previewPartner">--</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Date:</span>
                                <span class="preview-value" id="previewDate">--</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Amount:</span>
                                <span class="preview-value" id="previewAmount">--</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Ref Number:</span>
                                <span class="preview-value" id="previewPvNumber">--</span>
                            </div>
                            <div class="preview-item border-none">
                                <span class="preview-label">Documents:</span>
                                <span class="preview-value" id="previewDocuments">--</span>
                            </div>
                            <div id="documentsPreview" class="doc-preview hidden">
                                <!-- Document preview will be inserted here -->
                            </div>
                            <div style="margin-top:10px;"> 
                               <button type="button" id="addTransactionButton" 
    class="btn-accent w-full flex items-center justify-center gap-2">
    <i class="fas fa-save"></i> Save Transaction
</button>
</div>
                             
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card p-6 md:p-8 mt-8 animate-fadeIn">
            <h3 class="text-2xl font-semibold text-gray-800 mb-6 section-title">Recent Transactions</h3>
            
            <div class="overflow-x-auto rounded-xl shadow-sm border border-gray-100">
<?php if (!$included): ?>
    </main>
</div>
<?php include 'message_system.php'; ?>
<?php endif; ?>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-4 px-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Budget Heading</th>
                            <th class="py-4 px-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                            <th class="py-4 px-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Partner</th>
                            <th class="py-4 px-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ref Number</th>
                            <th class="py-4 px-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                            <th class="py-4 px-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                            <th class="py-4 px-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="transactionTableBody" class="divide-y divide-gray-200">
                        <!-- Database data will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 flex justify-end">
                <a href="history.php" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-2">
                    View all transactions <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div id="toastContainer" class="fixed bottom-5 right-5 z-50 space-y-2"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form & Element selectors
        const budgetHeadingSelect = document.getElementById('budgetHeadingSelect');
        const outcomeInput = document.getElementById('outcomeInput');
        const activityInput = document.getElementById('activityInput');
        const budgetLineInput = document.getElementById('budgetLineInput');
        const transactionDescriptionInput = document.getElementById('transactionDescriptionInput');
        const partnerInput = document.getElementById('partnerInput');
        const transactionDateInput = document.getElementById('transactionDateInput');
        const amountInput = document.getElementById('amountInput');
        const transactionForm = document.getElementById('transactionForm');
        const supportingDocumentsButton = document.getElementById('supportingDocumentsButton');
        const addTransactionButton = document.getElementById('addTransactionButton');
        const clearFormButton = document.getElementById('clearFormButton');
        const toastContainer = document.getElementById('toastContainer');
        
        // Preview elements
        const previewHeading = document.getElementById('previewHeading');
        const previewOutcome = document.getElementById('previewOutcome');
        const previewActivity = document.getElementById('previewActivity');
        const previewBudgetLine = document.getElementById('previewBudgetLine');
        const previewDescription = document.getElementById('previewDescription');
        const previewPartner = document.getElementById('previewPartner');
        const previewDate = document.getElementById('previewDate');
        const previewAmount = document.getElementById('previewAmount');
        const previewPvNumber = document.getElementById('previewPvNumber');
        const previewDocuments = document.getElementById('previewDocuments');
        const documentsPreview = document.getElementById('documentsPreview');
        
        // State variables
        let uploadedDocuments = {};
        let fieldConfigurations = {};
        
        // Set default date to today
        const today = new Date().toISOString().split('T')[0];
        transactionDateInput.value = today;
        
        // --- Functions ---
        
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            let bgColor = 'bg-green-600';
            let icon = '<i class="fas fa-check-circle mr-2"></i>';
            
            if (type === 'error') {
                bgColor = 'bg-red-600';
                icon = '<i class="fas fa-exclamation-circle mr-2"></i>';
            }
            if (type === 'info') {
                bgColor = 'bg-blue-600';
                icon = '<i class="fas fa-info-circle mr-2"></i>';
            }

            toast.className = `toast p-4 rounded-xl shadow-xl text-white font-medium flex items-center ${bgColor}`;
            toast.innerHTML = `${icon} ${message}`;

            toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Helper function to get field value (works for both input and select)
        function getFieldValue(fieldId) {
            const element = document.getElementById(fieldId);
            return element ? element.value.trim() : '';
        }

        function updateLivePreview() {
            // Get budget heading value - works for both select and input
            const heading = getFieldValue('budgetHeadingSelect') || '--';
            const outcome = outcomeInput.value || '--';
            const activity = getFieldValue('activityInput') || '--';
            const budgetLine = getFieldValue('budgetLineInput') || '--';
            const description = transactionDescriptionInput.value || '--';
            const partner = getFieldValue('partnerInput') || '--';
            const date = transactionDateInput.value || '--';
            const amountValue = getFieldValue('amountInput');
            // Format amount without automatically adding decimals unless they exist
            // Fix for the rounding issue - preserve exact user input
            const amount = amountValue ? `<i class="fas fa-money-bill-wave text-green-600 mr-1"></i>${amountValue}` : '--';
            
            // Update sidebar preview
            previewHeading.textContent = heading;
            previewOutcome.textContent = outcome;
            previewActivity.textContent = activity;
            previewBudgetLine.textContent = budgetLine;
            previewDescription.textContent = description;
            previewPartner.textContent = partner;
            previewDate.textContent = date;
            previewAmount.innerHTML = amount;
            
            // Check budget availability if amount and other required fields are filled
            // Use debouncing to prevent multiple rapid calls
            clearTimeout(window.budgetCheckTimeout);
            window.budgetCheckTimeout = setTimeout(checkBudgetAvailability, 500);
            
            // Update PV Number preview
            const pvNumber = uploadedDocuments.pvNumber || '--';
            previewPvNumber.textContent = pvNumber;
            
            // Update documents preview
            let documentCount = 0;
            let docHtml = '';
            
            // Check different document sources in priority order
            const hasActiveDocuments = uploadedDocuments.documents && Object.keys(uploadedDocuments.documents).length > 0;
            const hasUploadedFiles = uploadedDocuments.uploadedFiles && uploadedDocuments.uploadedFiles.length > 0;
            const hasPersistentNames = uploadedDocuments.documentNames && Object.keys(uploadedDocuments.documentNames).length > 0;
            
            if (hasActiveDocuments) {
                // Use active documents if available (same session)
                documentCount = Object.keys(uploadedDocuments.documents).length;
                
                for (const docName in uploadedDocuments.documents) {
                    docHtml += `<div class="doc-item">
                        <i class="fas fa-file-pdf text-red-500"></i>
                        <span class="text-sm">${docName}</span>
                    </div>`;
                }
            } else if (hasUploadedFiles) {
                // Use uploaded files info (files are on server)
                documentCount = uploadedDocuments.uploadedFiles.length;
                
                uploadedDocuments.uploadedFiles.forEach(file => {
                    docHtml += `<div class="doc-item">
                        <i class="fas fa-file-pdf text-green-500"></i>
                        <span class="text-sm">${file.documentType}: ${file.originalName} ✓</span>
                    </div>`;
                });
            } else if (hasPersistentNames) {
                // Use persistent document names if files haven't been uploaded yet
                documentCount = Object.keys(uploadedDocuments.documentNames).length;
                
                for (const docType in uploadedDocuments.documentNames) {
                    const fileName = uploadedDocuments.documentNames[docType];
                    docHtml += `<div class="doc-item">
                        <i class="fas fa-file-pdf text-orange-500"></i>
                        <span class="text-sm">${docType}: ${fileName} (pending upload)</span>
                    </div>`;
                }
            }
            
            if (documentCount > 0) {
                previewDocuments.textContent = `${documentCount} document(s)`;
                documentsPreview.classList.remove('hidden');
                documentsPreview.innerHTML = docHtml;
            } else {
                previewDocuments.textContent = '--';
                documentsPreview.classList.add('hidden');
            }
        }
        
        // Add a variable to track the last budget check parameters
        let lastBudgetCheck = {
            heading: '',
            amount: 0,
            date: ''
        };
        
        function checkBudgetAvailability() {
            // Get budget heading value - works for both select and input
            const budgetHeading = getFieldValue('budgetHeadingSelect');
            const amountValue = getFieldValue('amountInput');
            const amount = parseFloat(amountValue) || 0;
            const date = transactionDateInput.value;
            
            // Check if parameters have changed since last check
            if (lastBudgetCheck.heading === budgetHeading && 
                lastBudgetCheck.amount === amount && 
                lastBudgetCheck.date === date) {
                return; // Skip if nothing has changed
            }
            
            // Update last check parameters
            lastBudgetCheck.heading = budgetHeading;
            lastBudgetCheck.amount = amount;
            lastBudgetCheck.date = date;
            
            // Clear previous budget warnings
            const existingWarning = document.getElementById('budgetWarning');
            if (existingWarning) {
                existingWarning.remove();
            }
            
            // Reset budget check result
            window.budgetCheckResult = null;
            
            if (budgetHeading && amount > 0 && date) {
                // Create budget check request - let server determine the quarter
                const formData = new FormData();
                formData.append('action', 'check_budget');
                formData.append('budgetHeading', budgetHeading);
                formData.append('amount', amount);
                formData.append('date', date); // Send full date instead of calculated quarter
                formData.append('year', new Date(date).getFullYear());
                
                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.budget_available !== undefined) {
                        const amountInput = document.getElementById('amountInput');
                        window.budgetCheckResult = {
                            available: data.budget_available,
                            entered: amount,
                            exceeded: amount > data.budget_available
                        };
                        
                        if (amount > data.budget_available) {
                            // Show budget warning
                            const warning = document.createElement('div');
                            warning.id = 'budgetWarning';
                            warning.className = 'mt-2 p-3 bg-red-100 border border-red-300 rounded-lg text-sm text-red-800';
                            warning.innerHTML = `
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Budget Warning:</strong> Amount (<i class="fas fa-money-bill-wave text-green-600 mr-1"></i>${amount.toLocaleString()}) exceeds available budget (<i class="fas fa-money-bill-wave text-green-600 mr-1"></i>${data.budget_available.toLocaleString()}) for ${budgetHeading} in ${data.quarter} (${data.date_range}).
                            `;
                            amountInput.parentNode.appendChild(warning);
                            amountInput.style.borderColor = '#ef4444';
                        } else {
                            amountInput.style.borderColor = '#10b981'; // Green border for valid amount
                            // Show success indicator
                            const success = document.createElement('div');
                            success.id = 'budgetWarning';
                            success.className = 'mt-2 p-3 bg-green-100 border border-green-300 rounded-lg text-sm text-green-800';
                            success.innerHTML = `
                                <i class="fas fa-check-circle mr-2"></i>
                                <strong>Budget OK:</strong> <i class="fas fa-money-bill-wave text-green-600 mr-1"></i>${data.budget_available.toLocaleString()} available in ${data.quarter} for ${budgetHeading}.
                            `;
                            // Only append if there's no existing success message
                            if (!document.getElementById('budgetWarning')) {
                                amountInput.parentNode.appendChild(success);
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Budget check error:', error);
                });
            }
        }

        function clearForm() {
            transactionForm.reset();
            // Set date to today again after reset
            transactionDateInput.value = today;
            localStorage.removeItem('tempFormData');
            localStorage.removeItem('uploadedDocuments');
            uploadedDocuments = {};
            updateLivePreview();
            showToast('Form has been cleared', 'info');
        }

        function addTransactionToTable(data) {
            const newRow = document.createElement('tr');
            newRow.classList.add('table-row');
            
            const amountFormatted = data.amount ? `<i class="fas fa-money-bill-wave text-green-600 mr-1"></i>${parseFloat(data.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}` : '--';
            const description = data.description || data.transactionDescription || '--';
            const date = data.entryDate || data.date || '--';
            const refNumber = data.pvNumber || '--';

            newRow.innerHTML = `
                <td class="py-4 px-4 text-sm font-medium text-gray-900">${data.budgetHeading}</td>
                <td class="py-4 px-4 text-sm text-gray-600">${description}</td>
                <td class="py-4 px-4 text-sm text-gray-600">${data.partner}</td>
                <td class="py-4 px-4 text-sm text-gray-600">${refNumber}</td>
                <td class="py-4 px-4 text-sm text-gray-600">${date}</td>
                <td class="py-4 px-4 text-sm text-gray-600">${amountFormatted}</td>
                <td class="py-4 px-4 text-sm text-gray-600">
                    <button class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;
            
            const transactionTableBody = document.getElementById('transactionTableBody');
            transactionTableBody.insertBefore(newRow, transactionTableBody.children[0]);
        }
        
        function loadRecentTransactions() {
            const formData = new FormData();
            formData.append('action', 'get_transactions');
            
            fetch('ajax_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transactionTableBody = document.getElementById('transactionTableBody');
                    
                    // Clear existing rows
                    transactionTableBody.innerHTML = '';
                    
                    // Add transactions from database
                    data.transactions.forEach(transaction => {
                        const row = document.createElement('tr');
                        row.classList.add('table-row');
                        
                        const amountFormatted = transaction.Amount ? `<i class="fas fa-money-bill-wave text-green-600 mr-1"></i>${parseFloat(transaction.Amount).toLocaleString('en-US', {minimumFractionDigits: 2})}` : '--';
                        const refNumber = transaction.PVNumber || '--';
                        
                        row.innerHTML = `
                            <td class="py-4 px-4 text-sm font-medium text-gray-900">${transaction.BudgetHeading || '--'}</td>
                            <td class="py-4 px-4 text-sm text-gray-600">${transaction.Description || '--'}</td>
                            <td class="py-4 px-4 text-sm text-gray-600">${transaction.Partner || '--'}</td>
                            <td class="py-4 px-4 text-sm text-gray-600">${refNumber}</td>
                            <td class="py-4 px-4 text-sm text-gray-600">${transaction.EntryDate || '--'}</td>
                            <td class="py-4 px-4 text-sm text-gray-600">${amountFormatted}</td>
                            <td class="py-4 px-4 text-sm text-gray-600">
                                <button class="text-blue-600 hover:text-blue-800" onclick="viewTransaction(${transaction.PreviewID})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        `;
                        
                        transactionTableBody.appendChild(row);
                    });
                } else {
                    console.error('Failed to load transactions:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading transactions:', error);
            });
        }
        
        function viewTransaction(id) {
            showToast('Transaction details view coming soon!', 'info');
        }

        // --- Event Listeners ---

        // Real-time preview updates
        const formInputs = [
            budgetHeadingSelect, outcomeInput, activityInput, budgetLineInput, 
            transactionDescriptionInput, partnerInput, transactionDateInput, amountInput
        ];
        
        formInputs.forEach(input => {
            if (input) {  // Check if element exists before adding listener
                input.addEventListener('input', updateLivePreview);
            }
        });

        // Redirect to documents.php
        supportingDocumentsButton.addEventListener('click', function() {
            if (transactionForm.checkValidity()) {
                const temporaryFormData = {
                    budgetHeading: budgetHeadingSelect.value,
                    outcome: outcomeInput.value,
                    activity: getFieldValue('activityInput'),
                    budgetLine: getFieldValue('budgetLineInput'),
                    transactionDescription: transactionDescriptionInput.value,
                    partner: getFieldValue('partnerInput'),
                    date: transactionDateInput.value,
                    amount: getFieldValue('amountInput')
                };
                localStorage.setItem('tempFormData', JSON.stringify(temporaryFormData));
                window.location.href = 'documents.php';
            } else {
                showToast('Please fill in all transaction fields before adding documents.', 'error');
            }
        });

        // Add transaction button handler
        addTransactionButton.addEventListener('click', function() {
            // Get form data using helper function
            const formData = {
                budgetHeading: getFieldValue('budgetHeadingSelect').trim(),
                outcome: outcomeInput.value.trim(),
                activity: getFieldValue('activityInput'),
                budgetLine: getFieldValue('budgetLineInput'),
                description: transactionDescriptionInput.value.trim(),
                partner: getFieldValue('partnerInput'),
                entryDate: transactionDateInput.value,
                amount: getFieldValue('amountInput')
            };
            
            // Check if all required fields are filled
            const missingFields = [];
            if (!formData.budgetHeading) missingFields.push('Budget Heading');
            if (!formData.outcome) missingFields.push('Outcome');
            if (!formData.activity) missingFields.push('Activity');
            if (!formData.budgetLine) missingFields.push('Budget Line');
            if (!formData.description) missingFields.push('Transaction Description');
            if (!formData.partner) missingFields.push('Partner');
            if (!formData.entryDate) missingFields.push('Date');
            if (!formData.amount || parseFloat(formData.amount) <= 0) missingFields.push('Amount');
            
            if (missingFields.length > 0) {
                showToast('Missing required fields: ' + missingFields.join(', '), 'error');
                return;
            }
            
            // Check if budget is exceeded
            const amount = parseFloat(formData.amount);
            if (window.budgetCheckResult && window.budgetCheckResult.exceeded) {
                showToast('Transaction amount exceeds available budget. Please reduce the amount.', 'error');
                return;
            }
            
            // Get saved documents data
            const savedDocs = JSON.parse(localStorage.getItem('uploadedDocuments'));
            
            // Allow saving even without documents, but show a warning
            if (!savedDocs || (!savedDocs.uploadedFiles && !savedDocs.documentNames)) {
                if (!confirm('No supporting documents have been uploaded. Do you want to save the transaction anyway?')) {
                    return;
                }
            }
            
            // Create FormData for AJAX request
            const ajaxFormData = new FormData();
            ajaxFormData.append('action', 'save_transaction');
            ajaxFormData.append('budgetHeading', formData.budgetHeading);
            ajaxFormData.append('outcome', formData.outcome);
            ajaxFormData.append('activity', formData.activity);
            ajaxFormData.append('budgetLine', formData.budgetLine);
            ajaxFormData.append('description', formData.description);
            ajaxFormData.append('partner', formData.partner);
            ajaxFormData.append('entryDate', formData.entryDate);
            ajaxFormData.append('amount', formData.amount);
            
            if (savedDocs && savedDocs.pvNumber) {
                ajaxFormData.append('pvNumber', savedDocs.pvNumber);
            }
            
            // Handle uploaded file paths (files already on server)
            if (savedDocs && savedDocs.uploadedFiles && savedDocs.uploadedFiles.length > 0) {
                ajaxFormData.append('uploadedFilePaths', JSON.stringify(savedDocs.uploadedFiles));
            }
            
            // Show loading state
            addTransactionButton.disabled = true;
            addTransactionButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            // Debug: Log what we're sending
            console.log('Form data being sent:', {
                budgetHeading: formData.budgetHeading,
                outcome: formData.outcome,
                activity: formData.activity,
                budgetLine: formData.budgetLine,
                description: formData.description,
                partner: formData.partner,
                entryDate: formData.entryDate,
                amount: formData.amount,
                pvNumber: savedDocs ? savedDocs.pvNumber : 'none',
                documents: savedDocs ? Object.keys(savedDocs.documents || {}).length : 0
            });
            
            // Send AJAX request
            fetch('ajax_handler.php', {
                method: 'POST',
                body: ajaxFormData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text(); // Get as text first to see raw response
            })
            .then(text => {
                console.log('Raw response:', text);
                
                // Check if response starts with valid JSON
                const trimmedText = text.trim();
                if (!trimmedText.startsWith('{') && !trimmedText.startsWith('[')) {
                    throw new Error('Invalid JSON response: ' + trimmedText.substring(0, 100));
                }
                
                try {
                    const data = JSON.parse(trimmedText);
                    console.log('Parsed response:', data);
                    if (data.success) {
                        addTransactionToTable(formData);
                        clearForm();
                        showToast(data.message, 'success');
                        loadRecentTransactions(); // Refresh the table
                        
                        // Refresh the page after 2 seconds to update budget metrics
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        let errorMessage = data.message || 'Unknown error occurred';
                        if (data.debug) {
                            console.error('Server debug info:', data.debug);
                            if (typeof data.debug === 'string') {
                                // Check if this is a budget validation error
                                if (data.debug.includes('exceeds available budget')) {
                                    errorMessage = data.debug; // Show the detailed budget error
                                } else {
                                    errorMessage += ' (Debug: ' + data.debug + ')';
                                }
                            } else if (data.debug.file) {
                                errorMessage += ' (Error in: ' + data.debug.file + ':' + data.debug.line + ')';
                            }
                        }
                        showToast(errorMessage, 'error');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw server response:', text);
                    showToast('Server returned invalid response format', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while saving the transaction.', 'error');
            })
            .finally(() => {
                // Reset button state
                addTransactionButton.disabled = false;
                addTransactionButton.innerHTML = '<i class="fas fa-save"></i> Save Transaction';
            });
        });
        
        // Clear form button handler
        clearFormButton.addEventListener('click', function() {
            clearForm();
        });

        // Load predefined field configurations
        function loadFieldConfigurations() {
            const fields = ['BudgetHeading', 'Outcome', 'Activity', 'BudgetLine', 'Partner', 'Amount'];
            
            fields.forEach(fieldName => {
                // Prepare URL-encoded data with cluster information
                let bodyData = `action=get_field_config&field_name=${encodeURIComponent(fieldName)}`;
                
                // Pass user cluster if available
                const userCluster = <?php echo json_encode($userCluster); ?>;
                if (userCluster) {
                    bodyData += `&cluster_name=${encodeURIComponent(userCluster)}`;
                }
                
                fetch('admin_fields_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: bodyData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fieldConfigurations[fieldName] = data.field;
                        setupField(fieldName, data.field);
                    }
                })
                .catch(error => {
                    console.error(`Error loading ${fieldName} config:`, error);
                    
                    // For Budget Heading, if not in admin, use default values
                    if (fieldName === 'BudgetHeading') {
                        const defaultBudgetHeadingConfig = {
                            field_name: 'BudgetHeading',
                            field_type: 'dropdown',
                            field_values: 'Administrative costs,Operational support costs,Consortium Activities,Targeting new CSOs,Contingency',
                            is_active: 1,
                            values_array: ['Administrative costs', 'Operational support costs', 'Consortium Activities', 'Targeting new CSOs', 'Contingency']
                        };
                        fieldConfigurations[fieldName] = defaultBudgetHeadingConfig;
                        setupField(fieldName, defaultBudgetHeadingConfig);
                    }
                    
                    // For Outcome, if not in admin, it should be an input field
                    if (fieldName === 'Outcome') {
                        const defaultOutcomeConfig = {
                            field_name: 'Outcome',
                            field_type: 'input',
                            field_values: '',
                            is_active: 1,
                            values_array: []
                        };
                        fieldConfigurations[fieldName] = defaultOutcomeConfig;
                        setupField(fieldName, defaultOutcomeConfig);
                    }
                });
            });
        }
        
        // Setup field based on configuration
        function setupField(fieldName, config) {
            const fieldMap = {
                'BudgetHeading': 'budgetHeadingSelect',
                'Outcome': 'outcomeInput',
                'Activity': 'activityInput',
                'BudgetLine': 'budgetLineInput', 
                'Partner': 'partnerInput',
                'Amount': 'amountInput'
            };
            
            const elementId = fieldMap[fieldName];
            const element = document.getElementById(elementId);
            
            if (!element || !config.is_active) return;
            
            if (config.field_type === 'dropdown' && config.values_array && config.values_array.length > 0) {
                // For Budget Heading, update the existing select options
                if (fieldName === 'BudgetHeading') {
                    // Clear existing options except the first one
                    while (element.options.length > 1) {
                        element.remove(1);
                    }
                    
                    // Add configured options
                    config.values_array.forEach((value, index) => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = value;
                        element.appendChild(option);
                        
                        // Remove the automatic selection of the first option to allow default "Select Budget Heading" to remain
                        // This was causing the dropdown to automatically select index 1 instead of keeping index 0 selected
                    });
                } else {
                    // Check if element is already a select
                    if (element.tagName === 'SELECT') {
                        // Update existing select options
                        // Clear existing options except the first one
                        while (element.options.length > 1) {
                            element.remove(1);
                        }
                        
                        // Add configured options
                        config.values_array.forEach(value => {
                            const option = document.createElement('option');
                            option.value = value;
                            option.textContent = value;
                            element.appendChild(option);
                        });
                    } else {
                        // Convert input to dropdown for other fields
                        const parent = element.parentNode;
                        
                        const select = document.createElement('select');
                        select.id = elementId;
                        select.className = element.className;
                        select.required = element.required;
                        
                        // Add default option
                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = `Select ${fieldName}...`;
                        select.appendChild(defaultOption);
                        
                        // Add configured options
                        config.values_array.forEach(value => {
                            const option = document.createElement('option');
                            option.value = value;
                            option.textContent = value;
                            select.appendChild(option);
                        });
                        
                        // Replace input with select
                        parent.replaceChild(select, element);
                        
                        // Update event listeners
                        select.addEventListener('input', updateLivePreview);
                        
                        // Update form inputs array reference
                        const inputIndex = formInputs.findIndex(input => input.id === elementId);
                        if (inputIndex !== -1) {
                            formInputs[inputIndex] = select;
                        }
                    }
                }
            } else if (config.field_type === 'input') {
                // Check if element is currently a select
                if (element.tagName === 'SELECT') {
                    // Convert dropdown back to input
                    const parent = element.parentNode;
                    
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.id = elementId;
                    input.className = element.className;
                    input.required = element.required;
                    
                    // Set placeholder and value from config
                    if (config.field_values) {
                        input.placeholder = config.field_values;
                        input.readOnly = true;
                        input.value = config.field_values;
                    } else {
                        input.placeholder = `Enter ${fieldName}...`;
                        input.readOnly = false;
                    }
                    
                    // Replace select with input
                    parent.replaceChild(input, element);
                    
                    // Update event listeners
                    input.addEventListener('input', updateLivePreview);
                    
                    // Update form inputs array reference
                    const inputIndex = formInputs.findIndex(input => input.id === elementId);
                    if (inputIndex !== -1) {
                        formInputs[inputIndex] = input;
                    }
                } else {
                    // For input fields, set placeholder text if predefined text exists
                    if (config.field_values) {
                        element.placeholder = config.field_values;
                        // Make the field read-only if it has predefined data
                        element.readOnly = true;
                        element.value = config.field_values;
                    } else {
                        // Default placeholder if no predefined text
                        element.placeholder = `Enter ${fieldName}...`;
                        // Make the field editable if no predefined data
                        element.readOnly = false;
                        element.value = ''; // Clear any existing value
                    }
                }
            }
        }

        // Initial setup on page load
        const savedData = JSON.parse(localStorage.getItem('tempFormData'));
        const savedDocs = JSON.parse(localStorage.getItem('uploadedDocuments'));

        if (savedData) {
            budgetHeadingSelect.value = savedData.budgetHeading;
            outcomeInput.value = savedData.outcome;
            activityInput.value = savedData.activity;
            budgetLineInput.value = savedData.budgetLine;
            transactionDescriptionInput.value = savedData.transactionDescription;
            partnerInput.value = savedData.partner;
            transactionDateInput.value = savedData.date;
            amountInput.value = savedData.amount;
        }

        if (savedDocs) {
            uploadedDocuments = savedDocs;
        }
        
        // Helper function to set field value (works for both input and select)
        function setFieldValue(fieldId, value) {
            const element = document.getElementById(fieldId);
            if (element) {
                element.value = value || '';
            }
        }
        
        // Load field configurations first, then update preview
        loadFieldConfigurations();
        
        // Small delay to allow field configurations to load
        setTimeout(() => {
            // Restore saved form data after field configurations are loaded
            if (savedData) {
                budgetHeadingSelect.value = savedData.budgetHeading;
                outcomeInput.value = savedData.outcome;
                setFieldValue('activityInput', savedData.activity);
                setFieldValue('budgetLineInput', savedData.budgetLine);
                transactionDescriptionInput.value = savedData.transactionDescription;
                setFieldValue('partnerInput', savedData.partner);
                transactionDateInput.value = savedData.date;
                setFieldValue('amountInput', savedData.amount);
            }
            
            updateLivePreview();
        }, 500);
        
        // Load recent transactions from database
        loadRecentTransactions();
    });
</script>
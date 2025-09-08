<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Initialize variables
$error_message = '';
$success_message = '';

// Get transaction ID from URL
$previewId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $budgetHeading = $_POST['budget_heading'] ?? '';
    $outcome = $_POST['outcome'] ?? '';
    $activity = $_POST['activity'] ?? '';
    $budgetLine = $_POST['budget_line'] ?? '';
    $description = $_POST['description'] ?? '';
    $partner = $_POST['partner'] ?? '';
    $entryDate = $_POST['entry_date'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $pvNumber = $_POST['pv_number'] ?? '';
    $quarterPeriod = $_POST['quarter_period'] ?? '';
    $categoryName = $_POST['category_name'] ?? '';
    $cluster = $_POST['cluster'] ?? '';
    $budgetId = $_POST['budget_id'] ?? null;
    
    // These values are calculated automatically and should not be taken from user input
    // $originalBudget = $_POST['original_budget'] ?? 0;
    // $remainingBudget = $_POST['remaining_budget'] ?? 0;
    // $actualSpent = $_POST['actual_spent'] ?? 0;
    // $forecastAmount = $_POST['forecast_amount'] ?? 0;
    
    // Handle document data
    $documentTypes = $_POST['document_types'] ?? [];
    $documentFiles = $_FILES['document_files'] ?? [];
    $existingDocumentPaths = $_POST['existing_document_paths'] ?? [];
    
    // Format document data as JSON for the Documents field
    $documentsArray = [];
    for ($i = 0; $i < count($documentTypes); $i++) {
        if (empty($documentTypes[$i])) {
            continue;
        }
        
        $serverPath = '';
        $filename = '';
        
        // Check if there's an existing document path
        if (isset($existingDocumentPaths[$i]) && !empty($existingDocumentPaths[$i])) {
            $serverPath = $existingDocumentPaths[$i];
            $filename = basename($serverPath);
        }
        
        // Check if a new file was uploaded
        if (isset($documentFiles['tmp_name'][$i]) && !empty($documentFiles['tmp_name'][$i])) {
            $uploadDir = 'admin/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = pathinfo($documentFiles['name'][$i], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $fileExtension;
            $serverPath = $uploadDir . $filename;
            
            if (!move_uploaded_file($documentFiles['tmp_name'][$i], $serverPath)) {
                $error_message = "Error uploading file: " . $documentFiles['name'][$i];
                break;
            }
        }
        
        if (!empty($serverPath)) {
            $documentsArray[] = [
                'documentType' => $documentTypes[$i],
                'serverPath' => $serverPath,
                'filename' => $filename
            ];
        }
    }
    
    $documentsJson = !empty($documentsArray) ? json_encode($documentsArray) : null;
    
    // Format document data as comma-separated strings for other fields
    $documentTypesStr = !empty($documentTypes) ? implode(',', $documentTypes) : null;
    $documentPathsStr = '';
    if (!empty($documentsArray)) {
        $paths = array_column($documentsArray, 'serverPath');
        $documentPathsStr = implode(',', $paths);
    }
    
    // Before executing the update, get original transaction data to calculate the difference
    $originalDataQuery = "SELECT Amount, budget_id, CategoryName, QuarterPeriod, EntryDate FROM budget_preview WHERE PreviewID = ?";
    $originalStmt = $conn->prepare($originalDataQuery);
    $originalStmt->bind_param("i", $previewId);
    $originalStmt->execute();
    $originalResult = $originalStmt->get_result();
    $originalData = $originalResult->fetch_assoc();
    $originalAmount = $originalData['Amount'] ?? 0;
    $budgetId = $originalData['budget_id'] ?? $budgetId; // Use existing budget_id if not provided
    $originalCategoryName = $originalData['CategoryName'] ?? $categoryName;
    $originalQuarterPeriod = $originalData['QuarterPeriod'] ?? $quarterPeriod;
    $originalEntryDate = $originalData['EntryDate'] ?? $entryDate;
    $originalYear = $originalEntryDate ? date('Y', strtotime($originalEntryDate)) : null;

    // Begin transaction for database consistency
    $conn->begin_transaction();
    
    try {
        // FIRST: Rollback the original transaction amount from budget calculations
        if ($budgetId && $originalAmount > 0) {
            // Get current budget data values
            $getBudgetQuery = "SELECT budget, actual, forecast, actual_plus_forecast, variance_percentage FROM budget_data WHERE id = ?";
            $getBudgetStmt = $conn->prepare($getBudgetQuery);
            $getBudgetStmt->bind_param("i", $budgetId);
            $getBudgetStmt->execute();
            $budgetResult = $getBudgetStmt->get_result();
            $budgetData = $budgetResult->fetch_assoc();
            $currentBudget = $budgetData['budget'] ?? 0;
            $currentActual = $budgetData['actual'] ?? 0;
            $currentForecast = $budgetData['forecast'] ?? 0;
            
            // Calculate new actual value (subtract the original amount)
            $newActual = max(0, $currentActual - $originalAmount);
            
            // Calculate new forecast (budget minus the new actual)
            $newForecast = max(0, $currentBudget - $newActual);
            
            // Calculate new actual_plus_forecast
            $newActualPlusForecast = $newActual + $newForecast;
            
            // Calculate new variance percentage using the correct formula
            // Variance = ((Budget - (Actual + Forecast)) / Budget) * 100
            $newVariancePercentage = 0;
            if ($currentBudget > 0) {
                $newVariancePercentage = round((($currentBudget - $newActualPlusForecast) / $currentBudget) * 100, 2);
            } else if ($currentBudget == 0 && $newActualPlusForecast > 0) {
                $newVariancePercentage = -100.00;
            }
            
            // Update the specific quarter row in budget_data
            $updateBudgetQuery = "UPDATE budget_data SET 
                actual = ?,
                forecast = ?,
                actual_plus_forecast = ?,
                variance_percentage = ?
                WHERE id = ?";
            $updateStmt = $conn->prepare($updateBudgetQuery);
            $updateStmt->bind_param("ddddi", $newActual, $newForecast, $newActualPlusForecast, $newVariancePercentage, $budgetId);
            $updateStmt->execute();
            
            // Extract year and cluster for further updates
            $extractYearQuery = "SELECT year2, cluster FROM budget_data WHERE id = ?";
            $extractYearStmt = $conn->prepare($extractYearQuery);
            $extractYearStmt->bind_param("i", $budgetId);
            $extractYearStmt->execute();
            $extractYearResult = $extractYearStmt->get_result();
            $extractYearData = $extractYearResult->fetch_assoc();
            $year = $extractYearData['year2'] ?? $originalYear;
            $cluster = $extractYearData['cluster'] ?? null;
            
            if ($year && $originalCategoryName) {
                // Update Annual Total row - recalculate the sum of quarterly actuals
                $updateAnnualQuery = "UPDATE budget_data 
                    SET actual = (SELECT SUM(COALESCE(actual, 0)) FROM budget_data b 
                                WHERE b.year2 = ? AND b.category_name = ? 
                                AND b.period_name IN ('Q1', 'Q2', 'Q3', 'Q4')";
                
                if ($cluster) {
                    $updateAnnualQuery .= " AND b.cluster = ?";
                }
                $updateAnnualQuery .= ") WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                
                if ($cluster) {
                    $updateAnnualQuery .= " AND cluster = ?";
                    $updateAnnualStmt = $conn->prepare($updateAnnualQuery);
                    $updateAnnualStmt->bind_param("ississ", $year, $originalCategoryName, $cluster, $year, $originalCategoryName, $cluster);
                } else {
                    $updateAnnualStmt = $conn->prepare($updateAnnualQuery);
                    $updateAnnualStmt->bind_param("isis", $year, $originalCategoryName, $year, $originalCategoryName);
                }
                $updateAnnualStmt->execute();
                
                // Update forecast for Annual Total
                $updateAnnualForecastQuery = "UPDATE budget_data 
                    SET forecast = (SELECT SUM(COALESCE(forecast, 0)) FROM budget_data b 
                                  WHERE b.year2 = ? AND b.category_name = ? 
                                  AND b.period_name IN ('Q1', 'Q2', 'Q3', 'Q4')";
                
                if ($cluster) {
                    $updateAnnualForecastQuery .= " AND b.cluster = ?";
                }
                $updateAnnualForecastQuery .= ") WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                
                if ($cluster) {
                    $updateAnnualForecastQuery .= " AND cluster = ?";
                    $updateAnnualForecastStmt = $conn->prepare($updateAnnualForecastQuery);
                    $updateAnnualForecastStmt->bind_param("ississ", $year, $originalCategoryName, $cluster, $year, $originalCategoryName, $cluster);
                } else {
                    $updateAnnualForecastStmt = $conn->prepare($updateAnnualForecastQuery);
                    $updateAnnualForecastStmt->bind_param("isis", $year, $originalCategoryName, $year, $originalCategoryName);
                }
                $updateAnnualForecastStmt->execute();
                
                // Update actual_plus_forecast for Annual Total
                $updateAnnualActualForecastQuery = "UPDATE budget_data 
                    SET actual_plus_forecast = COALESCE(actual, 0) + COALESCE(forecast, 0)
                    WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                
                if ($cluster) {
                    $updateAnnualActualForecastQuery .= " AND cluster = ?";
                    $updateAnnualActualForecastStmt = $conn->prepare($updateAnnualActualForecastQuery);
                    $updateAnnualActualForecastStmt->bind_param("iss", $year, $originalCategoryName, $cluster);
                } else {
                    $updateAnnualActualForecastStmt = $conn->prepare($updateAnnualActualForecastQuery);
                    $updateAnnualActualForecastStmt->bind_param("is", $year, $originalCategoryName);
                }
                $updateAnnualActualForecastStmt->execute();
                
                // Update variance percentages
                $updateVarianceQuery = "UPDATE budget_data 
                    SET variance_percentage = CASE 
                        WHEN budget > 0 THEN ROUND(((budget - (COALESCE(actual,0) + COALESCE(forecast,0))) / budget) * 100, 2)
                        WHEN budget = 0 AND (COALESCE(actual,0) + COALESCE(forecast,0)) > 0 THEN -100.00
                        ELSE 0.00 
                    END
                    WHERE year2 = ? AND category_name = ?";
                
                if ($cluster) {
                    $updateVarianceQuery .= " AND cluster = ?";
                    $updateVarianceStmt = $conn->prepare($updateVarianceQuery);
                    $updateVarianceStmt->bind_param("iss", $year, $originalCategoryName, $cluster);
                } else {
                    $updateVarianceStmt = $conn->prepare($updateVarianceQuery);
                    $updateVarianceStmt->bind_param("is", $year, $originalCategoryName);
                }
                $updateVarianceStmt->execute();
                
                // Finally, update the Total row
                // Calculate new actual for Total row
                $updateTotalQuery = "UPDATE budget_data 
                    SET actual = (SELECT SUM(COALESCE(actual, 0)) FROM budget_data b 
                                WHERE b.year2 = ? AND b.period_name = 'Annual Total' AND b.category_name != 'Total'";
                
                if ($cluster) {
                    $updateTotalQuery .= " AND b.cluster = ?";
                }
                $updateTotalQuery .= ") WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                
                if ($cluster) {
                    $updateTotalQuery .= " AND cluster = ?";
                    $updateTotalStmt = $conn->prepare($updateTotalQuery);
                    $updateTotalStmt->bind_param("isis", $year, $cluster, $year, $cluster);
                } else {
                    $updateTotalStmt = $conn->prepare($updateTotalQuery);
                    $updateTotalStmt->bind_param("ii", $year, $year);
                }
                $updateTotalStmt->execute();
                
                // Update forecast for Total row
                $updateTotalForecastQuery = "UPDATE budget_data 
                    SET forecast = (SELECT SUM(COALESCE(forecast, 0)) FROM budget_data b 
                                  WHERE b.year2 = ? AND b.period_name = 'Annual Total' AND b.category_name != 'Total'";
                
                if ($cluster) {
                    $updateTotalForecastQuery .= " AND b.cluster = ?";
                }
                $updateTotalForecastQuery .= ") WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                
                if ($cluster) {
                    $updateTotalForecastQuery .= " AND cluster = ?";
                    $updateTotalForecastStmt = $conn->prepare($updateTotalForecastQuery);
                    $updateTotalForecastStmt->bind_param("isis", $year, $cluster, $year, $cluster);
                } else {
                    $updateTotalForecastStmt = $conn->prepare($updateTotalForecastQuery);
                    $updateTotalForecastStmt->bind_param("ii", $year, $year);
                }
                $updateTotalForecastStmt->execute();
                
                // Update actual_plus_forecast for Total row
                $updateTotalActualForecastQuery = "UPDATE budget_data 
                    SET actual_plus_forecast = COALESCE(actual, 0) + COALESCE(forecast, 0)
                    WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                
                if ($cluster) {
                    $updateTotalActualForecastQuery .= " AND cluster = ?";
                    $updateTotalActualForecastStmt = $conn->prepare($updateTotalActualForecastQuery);
                    $updateTotalActualForecastStmt->bind_param("is", $year, $cluster);
                } else {
                    $updateTotalActualForecastStmt = $conn->prepare($updateTotalActualForecastQuery);
                    $updateTotalActualForecastStmt->bind_param("i", $year);
                }
                $updateTotalActualForecastStmt->execute();
                
                // Update variance for Total row
                $updateTotalVarianceQuery = "UPDATE budget_data 
                    SET variance_percentage = CASE 
                        WHEN budget > 0 THEN ROUND(((budget - (COALESCE(actual,0) + COALESCE(forecast,0))) / budget) * 100, 2)
                        WHEN budget = 0 AND (COALESCE(actual,0) + COALESCE(forecast,0)) > 0 THEN -100.00
                        ELSE 0.00 
                    END
                    WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                
                if ($cluster) {
                    $updateTotalVarianceQuery .= " AND cluster = ?";
                    $updateTotalVarianceStmt = $conn->prepare($updateTotalVarianceQuery);
                    $updateTotalVarianceStmt->bind_param("is", $year, $cluster);
                } else {
                    $updateTotalVarianceStmt = $conn->prepare($updateTotalVarianceQuery);
                    $updateTotalVarianceStmt->bind_param("i", $year);
                }
                $updateTotalVarianceStmt->execute();
            }
        }
        
        // SECOND: Update the transaction in the database (excluding ACCEPTANCE and COMMENTS)
        $updateQuery = "UPDATE budget_preview SET 
            BudgetHeading = ?, 
            Outcome = ?, 
            Activity = ?, 
            BudgetLine = ?, 
            Description = ?, 
            Partner = ?, 
            EntryDate = ?, 
            Amount = ?, 
            PVNumber = ?, 
            Documents = ?, 
            DocumentPaths = ?, 
            DocumentTypes = ?, 
            QuarterPeriod = ?, 
            CategoryName = ?, 
            cluster = ? 
            WHERE PreviewID = ?";
            
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssssdsssssssi", 
            $budgetHeading, 
            $outcome, 
            $activity, 
            $budgetLine, 
            $description, 
            $partner, 
            $entryDate, 
            $amount, 
            $pvNumber, 
            $documentsJson,
            $documentPathsStr,
            $documentTypesStr,
            $quarterPeriod, 
            $categoryName, 
            $cluster,
            $previewId
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating transaction: " . $conn->error);
        }
        
        // THIRD: Apply the new transaction amount to budget calculations
        if ($amount > 0) {
            // Get the budget_id if not already available
            if (!$budgetId) {
                // Find the correct budget_id based on category, quarter, and year
                $entryDateTime = new DateTime($entryDate);
                $entryYear = (int)$entryDateTime->format('Y');
                
                $budgetIdQuery = "SELECT id FROM budget_data 
                                 WHERE year2 = ? AND category_name = ? AND period_name = ? 
                                 AND ? BETWEEN start_date AND end_date";
                
                if ($cluster) {
                    $budgetIdQuery .= " AND cluster = ?";
                    $budgetIdStmt = $conn->prepare($budgetIdQuery);
                    $budgetIdStmt->bind_param("issss", $entryYear, $categoryName, $quarterPeriod, $entryDate, $cluster);
                } else {
                    $budgetIdStmt = $conn->prepare($budgetIdQuery);
                    $budgetIdStmt->bind_param("isss", $entryYear, $categoryName, $quarterPeriod, $entryDate);
                }
                
                $budgetIdStmt->execute();
                $budgetIdResult = $budgetIdStmt->get_result();
                $budgetIdData = $budgetIdResult->fetch_assoc();
                $budgetId = $budgetIdData['id'] ?? null;
                
                // Update the budget_preview with the budget_id
                if ($budgetId) {
                    $updateBudgetIdQuery = "UPDATE budget_preview SET budget_id = ? WHERE PreviewID = ?";
                    $updateBudgetIdStmt = $conn->prepare($updateBudgetIdQuery);
                    $updateBudgetIdStmt->bind_param("ii", $budgetId, $previewId);
                    $updateBudgetIdStmt->execute();
                }
            }
            
            // If we have a budget_id, update the budget_data table
            if ($budgetId) {
                // First, get current budget data values
                $getBudgetQuery = "SELECT budget, actual, forecast, actual_plus_forecast, variance_percentage FROM budget_data WHERE id = ?";
                $getBudgetStmt = $conn->prepare($getBudgetQuery);
                $getBudgetStmt->bind_param("i", $budgetId);
                $getBudgetStmt->execute();
                $budgetResult = $getBudgetStmt->get_result();
                $budgetData = $budgetResult->fetch_assoc();
                $currentBudget = $budgetData['budget'] ?? 0;
                $currentActual = $budgetData['actual'] ?? 0;
                $currentForecast = $budgetData['forecast'] ?? 0;
                
                // Calculate new actual value (add the new amount)
                $newActual = max(0, $currentActual + $amount);
                
                // Calculate new forecast (budget minus the new actual)
                $newForecast = max(0, $currentBudget - $newActual);
                
                // Calculate new actual_plus_forecast
                $newActualPlusForecast = $newActual + $newForecast;
                
                // Calculate new variance percentage using the correct formula
                // Variance = ((Budget - (Actual + Forecast)) / Budget) * 100
                $newVariancePercentage = 0;
                if ($currentBudget > 0) {
                    $newVariancePercentage = round((($currentBudget - $newActualPlusForecast) / $currentBudget) * 100, 2);
                } else if ($currentBudget == 0 && $newActualPlusForecast > 0) {
                    $newVariancePercentage = -100.00;
                }
                
                // Update the specific quarter row in budget_data
                $updateBudgetQuery = "UPDATE budget_data SET 
                    actual = ?,
                    forecast = ?,
                    actual_plus_forecast = ?,
                    variance_percentage = ?
                    WHERE id = ?";
                $updateStmt = $conn->prepare($updateBudgetQuery);
                $updateStmt->bind_param("ddddi", $newActual, $newForecast, $newActualPlusForecast, $newVariancePercentage, $budgetId);
                $updateStmt->execute();
                
                // Extract year and cluster for further updates
                $extractYearQuery = "SELECT year2, cluster FROM budget_data WHERE id = ?";
                $extractYearStmt = $conn->prepare($extractYearQuery);
                $extractYearStmt->bind_param("i", $budgetId);
                $extractYearStmt->execute();
                $extractYearResult = $extractYearStmt->get_result();
                $extractYearData = $extractYearResult->fetch_assoc();
                $year = $extractYearData['year2'] ?? $originalYear;
                $cluster = $extractYearData['cluster'] ?? null;
                
                if ($year && $categoryName) {
                    // Update Annual Total row - recalculate the sum of quarterly actuals
                    $updateAnnualQuery = "UPDATE budget_data 
                        SET actual = (SELECT SUM(COALESCE(actual, 0)) FROM budget_data b 
                                    WHERE b.year2 = ? AND b.category_name = ? 
                                    AND b.period_name IN ('Q1', 'Q2', 'Q3', 'Q4')";
                    
                    if ($cluster) {
                        $updateAnnualQuery .= " AND b.cluster = ?";
                    }
                    $updateAnnualQuery .= ") WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                    
                    if ($cluster) {
                        $updateAnnualQuery .= " AND cluster = ?";
                        $updateAnnualStmt = $conn->prepare($updateAnnualQuery);
                        $updateAnnualStmt->bind_param("ississ", $year, $categoryName, $cluster, $year, $categoryName, $cluster);
                    } else {
                        $updateAnnualStmt = $conn->prepare($updateAnnualQuery);
                        $updateAnnualStmt->bind_param("isis", $year, $categoryName, $year, $categoryName);
                    }
                    $updateAnnualStmt->execute();
                    
                    // Update forecast for Annual Total
                    $updateAnnualForecastQuery = "UPDATE budget_data 
                        SET forecast = (SELECT SUM(COALESCE(forecast, 0)) FROM budget_data b 
                                      WHERE b.year2 = ? AND b.category_name = ? 
                                      AND b.period_name IN ('Q1', 'Q2', 'Q3', 'Q4')";
                    
                    if ($cluster) {
                        $updateAnnualForecastQuery .= " AND b.cluster = ?";
                    }
                    $updateAnnualForecastQuery .= ") WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                    
                    if ($cluster) {
                        $updateAnnualForecastQuery .= " AND cluster = ?";
                        $updateAnnualForecastStmt = $conn->prepare($updateAnnualForecastQuery);
                        $updateAnnualForecastStmt->bind_param("ississ", $year, $categoryName, $cluster, $year, $categoryName, $cluster);
                    } else {
                        $updateAnnualForecastStmt = $conn->prepare($updateAnnualForecastQuery);
                        $updateAnnualForecastStmt->bind_param("isis", $year, $categoryName, $year, $categoryName);
                    }
                    $updateAnnualForecastStmt->execute();
                    
                    // Update actual_plus_forecast for Annual Total
                    $updateAnnualActualForecastQuery = "UPDATE budget_data 
                        SET actual_plus_forecast = COALESCE(actual, 0) + COALESCE(forecast, 0)
                        WHERE year2 = ? AND category_name = ? AND period_name = 'Annual Total'";
                    
                    if ($cluster) {
                        $updateAnnualActualForecastQuery .= " AND cluster = ?";
                        $updateAnnualActualForecastStmt = $conn->prepare($updateAnnualActualForecastQuery);
                        $updateAnnualActualForecastStmt->bind_param("iss", $year, $categoryName, $cluster);
                    } else {
                        $updateAnnualActualForecastStmt = $conn->prepare($updateAnnualActualForecastQuery);
                        $updateAnnualActualForecastStmt->bind_param("is", $year, $categoryName);
                    }
                    $updateAnnualActualForecastStmt->execute();
                    
                    // Update variance percentages
                    $updateVarianceQuery = "UPDATE budget_data 
                        SET variance_percentage = CASE 
                            WHEN budget > 0 THEN ROUND(((budget - (COALESCE(actual,0) + COALESCE(forecast,0))) / budget) * 100, 2)
                            WHEN budget = 0 AND (COALESCE(actual,0) + COALESCE(forecast,0)) > 0 THEN -100.00
                            ELSE 0.00 
                        END
                        WHERE year2 = ? AND category_name = ?";
                    
                    if ($cluster) {
                        $updateVarianceQuery .= " AND cluster = ?";
                        $updateVarianceStmt = $conn->prepare($updateVarianceQuery);
                        $updateVarianceStmt->bind_param("iss", $year, $categoryName, $cluster);
                    } else {
                        $updateVarianceStmt = $conn->prepare($updateVarianceQuery);
                        $updateVarianceStmt->bind_param("is", $year, $categoryName);
                    }
                    $updateVarianceStmt->execute();
                    
                    // Finally, update the Total row
                    // Calculate new actual for Total row
                    $updateTotalQuery = "UPDATE budget_data 
                        SET actual = (SELECT SUM(COALESCE(actual, 0)) FROM budget_data b 
                                    WHERE b.year2 = ? AND b.period_name = 'Annual Total' AND b.category_name != 'Total'";
                    
                    if ($cluster) {
                        $updateTotalQuery .= " AND b.cluster = ?";
                    }
                    $updateTotalQuery .= ") WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                    
                    if ($cluster) {
                        $updateTotalQuery .= " AND cluster = ?";
                        $updateTotalStmt = $conn->prepare($updateTotalQuery);
                        $updateTotalStmt->bind_param("isis", $year, $cluster, $year, $cluster);
                    } else {
                        $updateTotalStmt = $conn->prepare($updateTotalQuery);
                        $updateTotalStmt->bind_param("ii", $year, $year);
                    }
                    $updateTotalStmt->execute();
                    
                    // Update forecast for Total row
                    $updateTotalForecastQuery = "UPDATE budget_data 
                        SET forecast = (SELECT SUM(COALESCE(forecast, 0)) FROM budget_data b 
                                      WHERE b.year2 = ? AND b.period_name = 'Annual Total' AND b.category_name != 'Total'";
                    
                    if ($cluster) {
                        $updateTotalForecastQuery .= " AND b.cluster = ?";
                    }
                    $updateTotalForecastQuery .= ") WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                    
                    if ($cluster) {
                        $updateTotalForecastQuery .= " AND cluster = ?";
                        $updateTotalForecastStmt = $conn->prepare($updateTotalForecastQuery);
                        $updateTotalForecastStmt->bind_param("isis", $year, $cluster, $year, $cluster);
                    } else {
                        $updateTotalForecastStmt = $conn->prepare($updateTotalForecastQuery);
                        $updateTotalForecastStmt->bind_param("ii", $year, $year);
                    }
                    $updateTotalForecastStmt->execute();
                    
                    // Update actual_plus_forecast for Total row
                    $updateTotalActualForecastQuery = "UPDATE budget_data 
                        SET actual_plus_forecast = COALESCE(actual, 0) + COALESCE(forecast, 0)
                        WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                    
                    if ($cluster) {
                        $updateTotalActualForecastQuery .= " AND cluster = ?";
                        $updateTotalActualForecastStmt = $conn->prepare($updateTotalActualForecastQuery);
                        $updateTotalActualForecastStmt->bind_param("is", $year, $cluster);
                    } else {
                        $updateTotalActualForecastStmt = $conn->prepare($updateTotalActualForecastQuery);
                        $updateTotalActualForecastStmt->bind_param("i", $year);
                    }
                    $updateTotalActualForecastStmt->execute();
                    
                    // Update variance for Total row
                    $updateTotalVarianceQuery = "UPDATE budget_data 
                        SET variance_percentage = CASE 
                            WHEN budget > 0 THEN ROUND(((budget - (COALESCE(actual,0) + COALESCE(forecast,0))) / budget) * 100, 2)
                            WHEN budget = 0 AND (COALESCE(actual,0) + COALESCE(forecast,0)) > 0 THEN -100.00
                            ELSE 0.00 
                        END
                        WHERE year2 = ? AND category_name = 'Total' AND period_name = 'Total'";
                    
                    if ($cluster) {
                        $updateTotalVarianceQuery .= " AND cluster = ?";
                        $updateTotalVarianceStmt = $conn->prepare($updateTotalVarianceQuery);
                        $updateTotalVarianceStmt->bind_param("is", $year, $cluster);
                    } else {
                        $updateTotalVarianceStmt = $conn->prepare($updateTotalVarianceQuery);
                        $updateTotalVarianceStmt->bind_param("i", $year);
                    }
                    $updateTotalVarianceStmt->execute();
                }
            }
        }
        
        // Update the budget_preview table with calculated values
        // Get the budget_id if we don't have it yet
        if (!$budgetId) {
            // Find the correct budget_id based on category, quarter, and year
            $entryDateTime = new DateTime($entryDate);
            $entryYear = (int)$entryDateTime->format('Y');
            
            $budgetIdQuery = "SELECT id FROM budget_data 
                             WHERE year2 = ? AND category_name = ? AND period_name = ? 
                             AND ? BETWEEN start_date AND end_date";
            
            if ($cluster) {
                $budgetIdQuery .= " AND cluster = ?";
                $budgetIdStmt = $conn->prepare($budgetIdQuery);
                $budgetIdStmt->bind_param("issss", $entryYear, $categoryName, $quarterPeriod, $entryDate, $cluster);
            } else {
                $budgetIdStmt = $conn->prepare($budgetIdQuery);
                $budgetIdStmt->bind_param("isss", $entryYear, $categoryName, $quarterPeriod, $entryDate);
            }
            
            $budgetIdStmt->execute();
            $budgetIdResult = $budgetIdStmt->get_result();
            $budgetIdData = $budgetIdResult->fetch_assoc();
            $budgetId = $budgetIdData['id'] ?? null;
        }
        
        // Get the original budget value for this quarter
        $originalBudgetValue = 0;
        if ($budgetId) {
            $budgetQuery = "SELECT budget FROM budget_data WHERE id = ?";
            $budgetStmt = $conn->prepare($budgetQuery);
            $budgetStmt->bind_param("i", $budgetId);
            $budgetStmt->execute();
            $budgetResult = $budgetStmt->get_result();
            $budgetRow = $budgetResult->fetch_assoc();
            $originalBudgetValue = $budgetRow['budget'] ?? 0;
        }
        
        // Calculate the new values for budget_preview table
        // Get the current actual value from budget_data after the rollback and new update
        $currentActual = 0;
        if ($budgetId) {
            $actualQuery = "SELECT actual FROM budget_data WHERE id = ?";
            $actualStmt = $conn->prepare($actualQuery);
            $actualStmt->bind_param("i", $budgetId);
            $actualStmt->execute();
            $actualResult = $actualStmt->get_result();
            $actualRow = $actualResult->fetch_assoc();
            $currentActual = $actualRow['actual'] ?? 0;
        }
        
        // Calculate the new values for budget_preview table
        $newActualSpent = $currentActual; // Actual spent is the current actual value
        // Calculate forecast amount - this should be the remaining budget after actual spending
        $newForecastAmount = max(0, $originalBudgetValue - $newActualSpent);
        // Remaining budget should be the same as forecast (what's left to spend)
        $newRemainingBudget = $newForecastAmount;
        
        // Calculate variance percentage for budget_preview table
        // Since Actual + Forecast = Budget, variance should be 0 for proper budget management
        $newVariancePercentage = 0;
        if ($originalBudgetValue > 0) {
            $newVariancePercentage = round((($originalBudgetValue - ($newActualSpent + $newForecastAmount)) / $originalBudgetValue) * 100, 2);
        } else if ($originalBudgetValue == 0 && ($newActualSpent + $newForecastAmount) > 0) {
            $newVariancePercentage = -100.00;
        }
        
        // Update the budget_preview table with calculated values
        $updatePreviewQuery = "UPDATE budget_preview SET 
            OriginalBudget = ?,
            RemainingBudget = ?,
            ActualSpent = ?,
            ForecastAmount = ?,
            VariancePercentage = ?
            WHERE PreviewID = ?";
        $updatePreviewStmt = $conn->prepare($updatePreviewQuery);
        $updatePreviewStmt->bind_param("dddddi", 
            $originalBudgetValue,
            $newRemainingBudget,
            $newActualSpent,
            $newForecastAmount,
            $newVariancePercentage,
            $previewId
        );
        $updatePreviewStmt->execute();
        
        // Commit the transaction if everything was successful
        $conn->commit();
        
        $_SESSION['success_message'] = "Transaction updated successfully.";
        header("Location: history.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if there was an error
        $conn->rollback();
        $error_message = "Error updating transaction: " . $e->getMessage();
    }
}

// Fetch transaction data for display (only if not redirecting)
if ($previewId > 0) {
    $query = "SELECT * FROM budget_preview WHERE PreviewID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $previewId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $transaction = $result->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Transaction not found.";
        header("Location: history.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Invalid transaction ID.";
    header("Location: history.php");
    exit();
}

// Parse document data for display
$documentList = [];
if (!empty($transaction['Documents'])) {
    $documents = json_decode($transaction['Documents'], true);
    if (is_array($documents)) {
        $documentList = $documents;
    }
} else if (!empty($transaction['DocumentPaths'])) {
    // Fallback to comma-separated format
    $paths = explode(',', $transaction['DocumentPaths']);
    $types = !empty($transaction['DocumentTypes']) ? explode(',', $transaction['DocumentTypes']) : [];
    
    for ($i = 0; $i < count($paths); $i++) {
        if (!empty($paths[$i])) {
            $documentList[] = [
                'documentType' => $types[$i] ?? 'Unknown',
                'serverPath' => $paths[$i],
                'filename' => basename($paths[$i])
            ];
        }
    }
}

// Define the complete checklist for document types
$completeChecklist = [
    "1 Withholding Tax (WHT) Payments" => [
        "WHT Calculation Summary Sheet",
        "WHT Payment Slip / Receipt from Tax Authority",
        "Bank Confirmation of WHT Payment"
    ],
    "2 Income Tax Payments" => [
        "Income Tax Calculation Summary Sheet /Payroll",
        "Income Tax Payment Slip / Receipt from Tax Authority",
        "Bank Confirmation of Income Tax Payment"
    ],
    "3 Pension Contribution Payment" => [
        "Pension Calculation Sheet",
        "Pension Payment Slip / Receipt from Tax Authority",
        "Bank Confirmation of Pension Payment"
    ],
    "4 Payroll Payments" => [
        "Approved Timesheets / Attendance Records",
        "Payroll Register Sheet ( For Each Project )",
        "Master Payroll Register Sheet",
        "Payslips / Pay Stubs (for each employee) ( If applicable)",
        "Bank Transfer Request Letter",
        "Proof Of Payment",
        "Payment Voucher"
    ],
    "5 Telecom Services Payments" => [
        "Telecom Service Contract / Agreement (if applicable)",
        "Monthly Telecom Bill / Invoice",
        "Cost Pro-ration Sheet",
        "Payment Request Form (Approved (by authorized person) )",
        "Bank transfer Request Letter /Cheque copy",
        "Proof of Payment (Bank transfer confirmation/Cheque copy)"
    ],
    "6 Rent Payments" => [
        "Rental / Lease Agreement",
        "Landlord's Invoice / Payment Request",
        "Payment Request Form (Approved (by authorized person) )",
        "Cost Pro-ration Sheet",
        "Bank transfer Request Letter /Cheque copy",
        "Proof of Payment (Bank transfer Advice /Cheque copy)",
        "Withholding Tax (WHT) Receipt (if applicable)"
    ],
    "7 Consultant Payments" => [
        "Consultant Service Contract Agreement",
        "Scope of Work (SOW) / Terms of Reference (TOR)",
        "Consultant Invoice (if applicable)",
        "Consultant Service accomplishment Activity report / Progress Report",
        "Payment Request Form (Approved)",
        "Proof of Payment (Bank transfer confirmation/Cheque copy)",
        "Withholding Tax (WHT) Receipt (if applicable)",
        "Paymnet Voucher"
    ],
    "8 Freight Transportation" => [
        "Purchase request",
        "Quotation request (filled in and sent to suppliers)",
        "Quotation (received back, signed and stamped)",
        "Attached proforma invoices in a sealed envelope",
        "Proformas with all formalities, including trade license",
        "Competitive bid analysis (CBA) signed and approved",
        "Contract agreement or purchase order",
        "Payment request form",
        "Original waybill",
        "Goods received notes",
        "Cash receipt invoice (with Organizational TIN)",
        "Cheque copy or bank transfer letter from vendor",
        "Payment voucher"
    ],
    "9 Vehicle Rental" => [
        "Purchase request for rental service",
        "Quotation request (filled in and sent to suppliers)",
        "Quotation (received back, signed and stamped)",
        "Attached proforma invoices in a sealed envelope",
        "Proformas with all formalities, including trade license",
        "Competitive bid analysis (CBA) signed and approved",
        "Contract agreement or purchase order",
        "Payment request form",
        "Summary of payments sheet",
        "Signed and approved log book sheet",
        "Vehicle goods-outward inspection certificate",
        "Withholding receipt (for amounts over ETB 10,000)",
        "Cash receipt invoice (with Organizational TIN)",
        "Cheque copy or bank transfer letter from vendor",
        "Payment voucher"
    ],
    "10 Training, Workshop and Related" => [
        "Training approved by Program Manager",
        "Participant invitation letters from government parties",
        "Fully completed attendance sheet",
        "Manager's signature",
        "Approved payment rate (or justified reason for a different rate)",
        "Letter from government for fuel (if applicable)",
        "Activity (training) report",
        "Cash receipt or bank advice (if refund applicable)",
        "Expense settlement sheet with all information",
        "All required signatures on templates",
        "All documents stamped \"paid\"",
        "All documents are original (or cross-referenced if not)",
        "TIN and company name on receipt",
        "Check dates and all information on receipts"
    ],
    "11 Procurement of Services" => [
        "Purchase requisition",
        "Quotation request (filled in and sent to suppliers)",
        "Quotation (received back, signed and stamped)",
        "Attached proforma invoices in a sealed envelope",
        "Proformas with all formalities, including trade license",
        "Competitive bid analysis (CBA) signed and approved",
        "Contract agreement or purchase order",
        "Payment request form",
        "Withholding receipt (for amounts over ETB 10,000)",
        "Cash receipt invoice (with Organizational TIN)",
        "Service accomplishment report",
        "Cheque copy or bank transfer letter from vendor",
        "Payment voucher"
    ],
    "12 Procurement of Goods" => [
        "Purchase request",
        "Quotation request (filled in and sent to suppliers)",
        "Quotation (received back, signed and stamped)",
        "Attached proforma invoices in a sealed envelope",
        "Proformas with all formalities, including trade license",
        "Competitive bid analysis (CBA) signed and approved",
        "purchase order",
        "Contract agreement or Framework Agreement",
        "Payment request form",
        "Withholding receipt (for amounts over ETB 20,000)",
        "Cash receipt invoice (with Organizational TIN)",
        "Goods received note (GRN) or delivery note",
        "Cheque copy or bank transfer letter from vendor",
        "Payment voucher"
    ]
];

// Flatten the checklist for dropdown options
$flattenedChecklist = [];
foreach ($completeChecklist as $category => $documents) {
    $flattenedChecklist[$category] = $category; // Add category as an option
    foreach ($documents as $document) {
        $flattenedChecklist[$document] = "-- " . $document; // Add documents with indentation
    }
}

// Create JSON for JavaScript
$flattenedChecklistJson = json_encode($flattenedChecklist);

// Include header after processing to avoid header errors
include 'header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            background-color: #f0f4f8;
        }
        .main-content-flex {
            display: flex;
            justify-content: center;
            padding: 2rem;
        }
        .content-container {
            width: 100%;
            max-width: 1400px;
        }
        .animate-fadeIn {
            animation: fadeIn 0.8s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card-hover {
            transition: transform 0.3s ease-out, box-shadow 0.3s ease-out;
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        }
        .btn-shadow {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            ring: 2px solid #3b82f6;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .document-item {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .add-document-btn {
            background-color: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }
        .add-document-btn:hover {
            background-color: #059669;
        }
        .remove-document-btn {
            background-color: #ef4444;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            cursor: pointer;
        }
        .remove-document-btn:hover {
            background-color: #dc2626;
        }
        .file-upload-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .file-upload-input {
            flex: 1;
        }
        .upload-btn {
            background-color: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            white-space: nowrap;
        }
        .upload-btn:hover {
            background-color: #2563eb;
        }
        .file-info {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="main-content-flex">
        <div class="content-container">
            <div id="editTransactionSection" class="bg-white p-10 rounded-3xl shadow-2xl w-full mx-auto card-hover animate-fadeIn">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-4xl font-extrabold text-gray-800">Edit Transaction</h3>
                    <a href="history.php" class="bg-gray-500 text-white py-3 px-6 rounded-full font-semibold transition hover:bg-gray-600 btn-shadow flex items-center space-x-2">
                        <i class="fas fa-arrow-left"></i> <span>Back to History</span>
                    </a>
                </div>
                
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <strong class="font-bold">Error: </strong>
                        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label" for="budget_heading">Budget Heading</label>
                            <input type="text" id="budget_heading" name="budget_heading" class="form-input" value="<?php echo htmlspecialchars($transaction['BudgetHeading'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="outcome">Outcome</label>
                            <input type="text" id="outcome" name="outcome" class="form-input" value="<?php echo htmlspecialchars($transaction['Outcome'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="activity">Activity</label>
                            <input type="text" id="activity" name="activity" class="form-input" value="<?php echo htmlspecialchars($transaction['Activity'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="budget_line">Budget Line</label>
                            <input type="text" id="budget_line" name="budget_line" class="form-input" value="<?php echo htmlspecialchars($transaction['BudgetLine'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group md:col-span-2">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" class="form-textarea" rows="3"><?php echo htmlspecialchars($transaction['Description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="partner">Partner</label>
                            <input type="text" id="partner" name="partner" class="form-input" value="<?php echo htmlspecialchars($transaction['Partner'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="entry_date">Entry Date</label>
                            <input type="date" id="entry_date" name="entry_date" class="form-input" value="<?php echo htmlspecialchars($transaction['EntryDate'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="amount">Amount</label>
                            <input type="number" step="0.01" id="amount" name="amount" class="form-input" value="<?php echo htmlspecialchars($transaction['Amount'] ?? 0); ?>" required>
                            <!-- Hidden budget_id field for transaction updates -->
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="pv_number">PV Number</label>
                            <input type="text" id="pv_number" name="pv_number" class="form-input" value="<?php echo htmlspecialchars($transaction['PVNumber'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="quarter_period">Quarter Period</label>
                            <select id="quarter_period" name="quarter_period" class="form-select" required>
                                <option value="Q1" <?php echo ($transaction['QuarterPeriod'] ?? '') === 'Q1' ? 'selected' : ''; ?>>Q1</option>
                                <option value="Q2" <?php echo ($transaction['QuarterPeriod'] ?? '') === 'Q2' ? 'selected' : ''; ?>>Q2</option>
                                <option value="Q3" <?php echo ($transaction['QuarterPeriod'] ?? '') === 'Q3' ? 'selected' : ''; ?>>Q3</option>
                                <option value="Q4" <?php echo ($transaction['QuarterPeriod'] ?? '') === 'Q4' ? 'selected' : ''; ?>>Q4</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="category_name">Category Name</label>
                            <input type="text" id="category_name" name="category_name" class="form-input" value="<?php echo htmlspecialchars($transaction['CategoryName'] ?? ''); ?>" required>
                        </div>
                        
                        <!-- Hidden fields for calculated values -->
                        <input type="hidden" id="original_budget" name="original_budget" value="<?php echo htmlspecialchars($transaction['OriginalBudget'] ?? 0); ?>">
                        <input type="hidden" id="remaining_budget" name="remaining_budget" value="<?php echo htmlspecialchars($transaction['RemainingBudget'] ?? 0); ?>">
                        <input type="hidden" id="actual_spent" name="actual_spent" value="<?php echo htmlspecialchars($transaction['ActualSpent'] ?? 0); ?>">
                        <input type="hidden" id="forecast_amount" name="forecast_amount" value="<?php echo htmlspecialchars($transaction['ForecastAmount'] ?? 0); ?>">
                        
                        <div class="form-group">
                            <label class="form-label" for="cluster">Cluster</label>
                            <input type="text" id="cluster" name="cluster" class="form-input" value="<?php echo htmlspecialchars($transaction['cluster'] ?? ''); ?>" required>
                        </div>
                        
                        <!-- Hidden field to store budget_id -->
                        <input type="hidden" id="budget_id" name="budget_id" value="<?php echo htmlspecialchars($transaction['budget_id'] ?? ''); ?>">
                        
                        <!-- Document Section -->
                        <div class="md:col-span-2">
                            <h4 class="text-xl font-bold text-gray-800 mb-4">Documents</h4>
                            <div id="documents-container">
                                <?php if (!empty($documentList)): ?>
                                    <?php foreach ($documentList as $index => $doc): ?>
                                        <div class="document-item" data-document-index="<?php echo $index; ?>">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="form-label">Document Type</label>
                                                    <select name="document_types[]" class="form-select">
                                                        <?php foreach ($flattenedChecklist as $value => $label): ?>
                                                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo (isset($doc['documentType']) && $doc['documentType'] == $value) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($label); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label">File Upload</label>
                                                    <div class="file-upload-wrapper">
                                                        <input type="file" name="document_files[]" class="form-input file-upload-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                                        <button type="button" class="upload-btn" onclick="triggerFileUpload(this)">Browse</button>
                                                    </div>
                                                    <?php if (!empty($doc['filename'])): ?>
                                                        <div class="file-info">
                                                            Current file: <?php echo htmlspecialchars($doc['filename']); ?>
                                                            <input type="hidden" name="existing_document_paths[]" value="<?php echo htmlspecialchars($doc['serverPath'] ?? ''); ?>">
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-end">
                                                    <button type="button" class="remove-document-btn" onclick="removeDocument(this)">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="document-item" data-document-index="0">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="form-label">Document Type</label>
                                                <select name="document_types[]" class="form-select">
                                                    <?php foreach ($flattenedChecklist as $value => $label): ?>
                                                        <option value="<?php echo htmlspecialchars($value); ?>">
                                                            <?php echo htmlspecialchars($label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">File Upload</label>
                                                <div class="file-upload-wrapper">
                                                    <input type="file" name="document_files[]" class="form-input file-upload-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                                    <button type="button" class="upload-btn" onclick="triggerFileUpload(this)">Browse</button>
                                                </div>
                                            </div>
                                            <div class="flex items-end">
                                                <button type="button" class="remove-document-btn" onclick="removeDocument(this)">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="add-document-btn mt-2" onclick="addDocument()">
                                <i class="fas fa-plus mr-2"></i> Add Document
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4 mt-8">
                        <a href="history.php" class="px-6 py-3 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition duration-150">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-3 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 transition duration-150 shadow-sm">
                            Update Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        let documentIndex = <?php echo count($documentList) > 0 ? count($documentList) : 1; ?>;
        const checklistOptions = <?php echo $flattenedChecklistJson; ?>;
        
        function triggerFileUpload(button) {
            const fileInput = button.previousElementSibling;
            fileInput.click();
        }
        
        function addDocument() {
            const container = document.getElementById('documents-container');
            const documentItem = document.createElement('div');
            documentItem.className = 'document-item';
            documentItem.setAttribute('data-document-index', documentIndex);
            
            // Generate options for the select dropdown
            let optionsHtml = '';
            for (const [value, label] of Object.entries(checklistOptions)) {
                optionsHtml += `<option value="${value}">${label}</option>`;
            }
            
            documentItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Document Type</label>
                        <select name="document_types[]" class="form-select">
                            ${optionsHtml}
                        </select>
                    </div>
                    <div>
                        <label class="form-label">File Upload</label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="document_files[]" class="form-input file-upload-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <button type="button" class="upload-btn" onclick="triggerFileUpload(this)">Browse</button>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="remove-document-btn" onclick="removeDocument(this)">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(documentItem);
            documentIndex++;
        }
        
        function removeDocument(button) {
            const documentItem = button.closest('.document-item');
            if (document.getElementById('documents-container').children.length > 1) {
                documentItem.remove();
            } else {
                // Clear the fields if it's the last document
                const inputs = documentItem.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else if (input.type !== 'file') {
                        input.value = '';
                    }
                });
            }
        }
    </script>
</body>
</html>
<!DOCTYPE html>
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

// Get user role and cluster information
$userRole = $_SESSION['role'] ?? 'finance_officer';
$userCluster = $_SESSION['cluster_name'] ?? null;

// Set default year if not specified - use current year
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Handle cluster selection for admins
$selectedCluster = null;
if ($userRole === 'admin') {
    // Admin can select any cluster or view all data
    $selectedCluster = isset($_GET['cluster']) ? $_GET['cluster'] : null;
} else {
    // Finance officers can only see their assigned cluster
    $selectedCluster = $userCluster;
}

// Map display years to database years
$displayToDatabaseYear = [
  1 => 1,
  2 => 2,
  3 => 3,
  4 => 4,
  5 => 5,
  6 => 6
];

// Map database years to display years
$databaseToDisplayYear = [
  1 => 1,
  2 => 2,
  3 => 3,
  4 => 4,
  5 => 5,
  6 => 6
];

// Get organization name based on year
$organizationNames = [
  1 => 'Consortium Hub Organization Year 1',
  2 => 'Consortium Hub Organization Year 2', 
  3 => 'Consortium Hub Organization Year 3',
  4 => 'Consortium Hub Organization Year 4',
  5 => 'Consortium Hub Organization Year 5',
  6 => 'Consortium Hub Organization Year 6'
];

// Convert display year to database year for queries
$databaseYear = isset($displayToDatabaseYear[$selectedYear]) ? $displayToDatabaseYear[$selectedYear] : 1;
$organizationName = isset($organizationNames[$selectedYear]) ? $organizationNames[$selectedYear] : 'Consortium Hub Organization';

// Fetch summary data for metrics cards with cluster filtering
$summaryQuery = "SELECT 
  SUM(CASE WHEN period_name = 'Overall' OR period_name = 'Grand Total' THEN budget ELSE 0 END) as total_budget,
  SUM(CASE WHEN period_name = 'Annual Total' THEN actual ELSE 0 END) as total_actual,
  SUM(CASE WHEN period_name = 'Annual Total' THEN actual_plus_forecast ELSE 0 END) as total_actual_forecast
FROM budget_data WHERE year = ?";

// Add cluster condition if a specific cluster is selected
if ($selectedCluster) {
    $summaryQuery .= " AND cluster = ?";
    $stmt = $conn->prepare($summaryQuery);
    $stmt->bind_param("is", $databaseYear, $selectedCluster);
} else {
    $stmt = $conn->prepare($summaryQuery);
    $stmt->bind_param("i", $databaseYear);
}

$stmt->execute();
$summaryResult = $stmt->get_result();
$summaryData = $summaryResult->fetch_assoc();

// Use calculated Grand Total values for more accurate metrics
$totalBudget = 0;
$totalActual = 0;
$totalActualForecast = 0;

// Calculate from Annual Total values of actual categories with cluster filtering
$metricsQuery = "SELECT * FROM budget_data WHERE year = ? AND period_name = 'Annual Total' AND category_name NOT LIKE '%total%' AND category_name NOT LIKE '%grand%'";

// Add cluster condition if a specific cluster is selected
if ($selectedCluster) {
    $metricsQuery .= " AND cluster = ?";
    $stmt = $conn->prepare($metricsQuery);
    $stmt->bind_param("is", $databaseYear, $selectedCluster);
} else {
    $stmt = $conn->prepare($metricsQuery);
    $stmt->bind_param("i", $databaseYear);
}

$stmt->execute();
$metricsResult = $stmt->get_result();

while ($row = $metricsResult->fetch_assoc()) {
  $totalBudget += floatval($row['budget'] ?? 0);
  $totalActual += floatval($row['actual'] ?? 0);
  $totalActualForecast += floatval($row['actual_plus_forecast'] ?? 0);
}
$utilizationPercentage = ($totalBudget > 0) ? round(($totalActual / $totalBudget) * 100, 1) : 0;
$remainingBudget = $totalBudget - $totalActual;

// Fetch data for Section 2 table with cluster filtering
$section2Query = "SELECT * FROM budget_data WHERE year = ?";

// Add cluster condition if a specific cluster is selected
if ($selectedCluster) {
    $section2Query .= " AND cluster = ?";
}

$section2Query .= " ORDER BY 
  CASE 
    WHEN category_name LIKE '1.%' THEN 1
    WHEN category_name LIKE '2.%' THEN 2
    WHEN category_name LIKE '3.%' THEN 3
    WHEN category_name LIKE '4.%' THEN 4
    WHEN category_name LIKE '5.%' THEN 5
    ELSE 6
  END, 
  CASE 
    WHEN period_name = 'Q1' THEN 1
    WHEN period_name = 'Q2' THEN 2
    WHEN period_name = 'Q3' THEN 3
    WHEN period_name = 'Q4' THEN 4
    WHEN period_name = 'Annual Total' THEN 5
    WHEN period_name = 'Total' THEN 6
    ELSE 7
  END";

// Prepare statement with cluster parameter if needed
if ($selectedCluster) {
    $stmt = $conn->prepare($section2Query);
    $stmt->bind_param("is", $databaseYear, $selectedCluster);
} else {
    $stmt = $conn->prepare($section2Query);
    $stmt->bind_param("i", $databaseYear);
}

$stmt->execute();
$section2Result = $stmt->get_result();

// Group data by category for Section 2
$section2Data = [];
$currentCategory = '';
while ($row = $section2Result->fetch_assoc()) {
  if ($row['category_name'] != $currentCategory) {
    $currentCategory = $row['category_name'];
    $section2Data[$currentCategory] = [];
  }
  $section2Data[$currentCategory][] = $row;
}

// Calculate Grand Total from Annual Total values only from non-Total categories
$grandTotalCalculated = [
  'budget' => 0,
  'actual' => 0,
  'forecast' => 0,
  'actual_plus_forecast' => 0,
  'variance_percentage' => 0
];

// First, remove any existing Total category to avoid confusion
if (isset($section2Data['Total'])) {
  unset($section2Data['Total']);
}

// Sum all Annual Total values from actual categories (not existing Total rows)
foreach ($section2Data as $categoryName => $periods) {
  // Skip any category that might be named 'Total' or similar
  if (strtolower($categoryName) === 'total' || strtolower($categoryName) === 'grand total') continue;
  
  foreach ($periods as $row) {
    if ($row['period_name'] === 'Annual Total') {
      $grandTotalCalculated['budget'] += floatval($row['budget'] ?? 0);
      $grandTotalCalculated['actual'] += floatval($row['actual'] ?? 0);
      $grandTotalCalculated['forecast'] += floatval($row['forecast'] ?? 0);
      $grandTotalCalculated['actual_plus_forecast'] += floatval($row['actual_plus_forecast'] ?? 0);
      break; // Only one Annual Total per category
    }
  }
}

// Calculate Grand Total variance percentage
if ($grandTotalCalculated['budget'] != 0) {
  $grandTotalCalculated['variance_percentage'] = round((($grandTotalCalculated['actual_plus_forecast'] - $grandTotalCalculated['budget']) / abs($grandTotalCalculated['budget'])) * 100, 2);
}

<?php if (!$included): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forecast Budget Table</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="forecast_budget.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    <div class="flex flex-col flex-1 min-w-0">
        <!-- Header -->
        <header class="flex items-center justify-between h-20 px-8 bg-white border-b border-gray-200 shadow-sm rounded-bl-xl">
            <div class="flex items-center">
                <!-- Hamburger menu for small screens -->
                <button id="sidebarToggleBtn"
                    class="text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2 lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                            d="M4 6h16M4 12h16M4 18h16">
                        </path>
                    </svg>
                </button>
                <h2 class="ml-4 text-2xl font-semibold text-gray-800">Forecast Budget</h2>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 p-8 overflow-y-auto overflow-x-auto bg-gray-50">
<?php else: ?>
<div class="flex flex-col flex-1 min-w-0">
    <!-- Header -->
    <header class="flex items-center justify-between h-20 px-8 bg-white border-b border-gray-200 shadow-sm rounded-bl-xl">
        <div class="flex items-center">
            <!-- Hamburger menu for small screens -->
            <button id="sidebarToggleBtn"
                class="text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                        d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>
            <h2 class="ml-4 text-2xl font-semibold text-gray-800">Forecast Budget</h2>
        </div>
    </header>

    <!-- Content Area -->
    <main class="flex-1 p-8 overflow-y-auto overflow-x-auto bg-gray-50">
<?php endif; ?>

<div class="metrics-grid no-print" style="margin-bottom: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
          <div class="metric-card-mini" style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); border-left: 3px solid #2563eb; transition: all 0.3s ease;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="background: #2563eb; color: white; padding: 6px; border-radius: 6px; font-size: 12px;">
                <i class="fas fa-chart-pie"></i>
              </div>
              <div>
                <div style="font-size: 10px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;">Total Budget</div>
                <div style="font-size: 16px; font-weight: 700; color: #1e293b;"><i class="fas fa-money-bill-wave text-green-600 mr-1"></i><?php echo number_format($totalBudget, 2); ?></div>
              </div>
            </div>
          </div>
          
          <div class="metric-card-mini" style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); border-left: 3px solid #10b981; transition: all 0.3s ease;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="background: #10b981; color: white; padding: 6px; border-radius: 6px; font-size: 12px;">
                <i class="fas fa-money-bill-wave"></i>
              </div>
              <div>
                <div style="font-size: 10px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;">Total Actual</div>
                <div style="font-size: 16px; font-weight: 700; color: #1e293b;"><i class="fas fa-money-bill-wave text-green-600 mr-1"></i><?php echo number_format($totalActual, 2); ?></div>
              </div>
            </div>
          </div>
          
          <div class="metric-card-mini" style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); border-left: 3px solid #f59e0b; transition: all 0.3s ease;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="background: #f59e0b; color: white; padding: 6px; border-radius: 6px; font-size: 12px;">
                <i class="fas fa-chart-line"></i>
              </div>
              <div>
                <div style="font-size: 10px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;">Utilization</div>
                <div style="font-size: 16px; font-weight: 700; color: #1e293b;"><?php echo $utilizationPercentage; ?>%</div>
              </div>
            </div>
          </div>
          
          <div class="metric-card-mini" style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); border-left: 3px solid #8b5cf6; transition: all 0.3s ease;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="background: #8b5cf6; color: white; padding: 6px; border-radius: 6px; font-size: 12px;">
                <i class="fas fa-piggy-bank"></i>
              </div>
              <div>
                <div style="font-size: 10px; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;">Remaining</div>
                <div style="font-size: 16px; font-weight: 700; color: #1e293b;"><i class="fas fa-money-bill-wave text-green-600 mr-1"></i><?php echo number_format($remainingBudget, 2); ?></div>
              </div>
            </div>
          </div>
          

        </div>
      </div>





      <!-- Filter + Export Controls -->
      <div class="controls no-print">
          <div class="filter-section">
          <div class="filter-title">
            <i class="fas fa-calendar-alt"></i> Filter by Year
          </div>
          <div class="relative">
            <form id="yearFilterForm" method="get">
              <!-- Include cluster in form if selected -->
              <?php if ($userRole === 'admin' && $selectedCluster): ?>
                <input type="hidden" name="cluster" value="<?php echo htmlspecialchars($selectedCluster); ?>">
              <?php endif; ?>
              <select id="yearFilter" name="year" class="w-full appearance-none bg-white border border-gray-300 rounded-lg py-3 pl-4 pr-10 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-500 transition duration-150 ease-in-out shadow-sm" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; font-weight: 500;" onchange="this.form.submit()">
        
                
                <option value="1" <?php echo ($selectedYear == 1) ? 'selected' : ''; ?>>Year 1</option>
                <option value="2" <?php echo ($selectedYear == 2) ? 'selected' : ''; ?>>Year 2</option>
                <option value="3" <?php echo ($selectedYear == 3) ? 'selected' : ''; ?>>Year 3</option>
                <option value="4" <?php echo ($selectedYear == 4) ? 'selected' : ''; ?>>Year 4</option>
                <option value="5" <?php echo ($selectedYear == 5) ? 'selected' : ''; ?>>Year 5</option>
                <option value="6" <?php echo ($selectedYear == 6) ? 'selected' : ''; ?>>Year 6</option>
              </select>
            </form>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;">
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="width: 18px; height: 18px; color: #6366f1;">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
        </div>

        <?php if ($userRole === 'admin'): ?>
        <div class="filter-section">
          <div class="filter-title">
            <i class="fas fa-building"></i> Filter by Cluster
          </div>
          <div class="cluster-filter">
            <form id="clusterFilterForm" method="get">
              <!-- Include year in form -->
              <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
              
              <select id="clusterFilter" name="cluster" class="w-full appearance-none bg-white border border-gray-300 rounded-lg py-3 pl-4 pr-10 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-500 transition duration-150 ease-in-out" onchange="this.form.submit()">
                <option value="">All Clusters</option>
                <?php foreach ($clusters as $cluster): ?>
                  <option value="<?php echo htmlspecialchars($cluster); ?>" <?php echo ($selectedCluster === $cluster) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cluster); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <div class="filter-section">
          <div class="filter-title">
            <i class="fas fa-tools"></i> Actions
          </div>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button class="btn-outline" id="exportExcelBtn" style="border-color: #059669; color: #059669;"><i class="fas fa-file-archive"></i> Export ZIP (2 CSV Files)</button>
          
            <button class="btn-primary" id="printBtn"><i class="fas fa-print"></i> Print Report</button>
          </div>
        </div>

        <div class="filter-section">
          <div class="filter-title">
            <i class="fas fa-table"></i> Select Table View
          </div>
          <div class="relative">
            <select id="tableSelection" class="w-full appearance-none bg-white border border-gray-300 rounded-lg py-3 pl-4 pr-10 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-500 transition duration-150 ease-in-out" style="width: 100%; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px;">
              <option value="section2">Table 1 Budget</option>
              <option value="section3">Table 2 Budget</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); pointer-events: none;">
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="width: 16px; height: 16px;">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Financial Metrics Cards Grid -->
     

      <!-- Forecast Budget -->
      <div id="print-area">

        <!-- Section 2: Forecast Budget -->
        <div class="print-section" id="section2-table">
          <div class="card-header">
           Forecast Budget Year <?php echo $selectedYear; ?>
          </div>
          

          
          <div class="card-body">
            <div class="table-container">
 <table class="vertical-table" border="1" cellspacing="0" cellpadding="5">
  <thead>
    <tr>
      <th>Category</th>
      <th>Period</th>
      <th>Budget</th>
      <th>Actual</th>
      <th>Forecast</th>
      <th>Actual + Forecast</th>
      <th>Variance (%)</th>
    </tr>
  </thead>
  <tbody>
    <?php 
foreach ($section2Data as $categoryName => $periods): 
  $categoryRowspan = count($periods);
  $firstPeriod = true;
  $isGrandTotal = ($categoryName === 'Grand Total');

  // Calculate annual totals from Q1–Q4 for this category
  $annualBudget = 0;
  $annualActual = 0;
  $annualForecast = 0;
  $annualActualForecast = 0;
  foreach ($periods as $row) {
    if (in_array($row['period_name'], ['Q1', 'Q2', 'Q3', 'Q4'])) {
      $annualBudget += floatval($row['budget']);
      $annualActual += floatval($row['actual']);
      $annualForecast += floatval($row['forecast']);
      $annualActualForecast += floatval($row['actual_plus_forecast']);
    }
  }
  $annualVariance = ($annualBudget != 0) ? round((($annualActualForecast - $annualBudget) / abs($annualBudget)) * 100, 2) : 0;

  // Track if Annual Total row exists in DB
  $hasAnnualTotal = false;
  foreach ($periods as $row) {
    if ($row['period_name'] === 'Annual Total') {
      $hasAnnualTotal = true;
      break;
    }
  }

  foreach ($periods as $index => $row):
    $period = $row['period_name'];
    $isAnnualTotal = ($period === 'Annual Total');
    $budget = $row['budget'] !== null ? number_format($row['budget'], 2) : '-';
    $actual = $row['actual'] !== null ? number_format($row['actual'], 2) : '-';
    $forecast = $row['forecast'] !== null ? number_format($row['forecast'], 2) : '-';
    $actualForecast = $row['actual_plus_forecast'] !== null ? number_format($row['actual_plus_forecast'], 2) : '-';
    // For Annual Total rows, use our calculated variance instead of database value
    if ($isAnnualTotal) {
        $variance = $annualVariance . '%';
    } else {
        $variance = $row['variance_percentage'] !== null ? $row['variance_percentage'] . '%' : '0%';
    }
?>
  <tr class="<?php echo $isAnnualTotal ? 'summary-section' : ($isGrandTotal ? 'grand-total-section' : ($firstPeriod ? 'category-header' : '')); ?>">
    <?php if ($firstPeriod && !$isGrandTotal): ?>
    <td rowspan="<?php echo $categoryRowspan + ($hasAnnualTotal ? 0 : 1); ?>"><?php echo htmlspecialchars($categoryName); ?></td>
    <?php endif; ?>
    <?php if ($isGrandTotal): ?>
    <td class="grand-total-label">Grand Total</td>
      <td></td> <!-- Blank period cell -->
      <td><?php echo number_format($grandTotalCalculated['budget'], 2); ?></td>
      <td><?php echo number_format($grandTotalCalculated['actual'], 2); ?></td>
      <td><?php echo number_format($grandTotalCalculated['forecast'], 2); ?></td>
      <td><?php echo number_format($grandTotalCalculated['actual_plus_forecast'], 2); ?></td>
      <td class="<?php 
        $grandTotalVariance = ($grandTotalCalculated['budget'] != 0) ? round((($grandTotalCalculated['actual_plus_forecast'] - $grandTotalCalculated['budget']) / abs($grandTotalCalculated['budget'])) * 100, 2) : 0;
        if ($grandTotalVariance > 0) {
          echo 'variance-positive';
        } elseif ($grandTotalVariance < 0) {
          echo 'variance-negative';
        } else {
          echo 'variance-zero';
        }
      ?>"><?php echo $grandTotalVariance . '%'; ?></td>
    <?php elseif ($isAnnualTotal): ?>
    <td><?php echo htmlspecialchars($period); ?></td>
      <td><?php echo number_format($annualBudget, 2); ?></td>
      <td><?php echo number_format($annualActual, 2); ?></td>
      <td><?php echo number_format($annualForecast, 2); ?></td>
      <td><?php echo number_format($annualActualForecast, 2); ?></td>
      <td class="<?php 
        if ($annualVariance > 0) {
          echo 'variance-positive';
        } elseif ($annualVariance < 0) {
          echo 'variance-negative';
        } else {
          echo 'variance-zero';
        }
      ?>"><?php echo $annualVariance . '%'; ?></td>
    <?php else: ?>
    <td><?php echo htmlspecialchars($period); ?></td>
      <td><?php echo $budget; ?></td>
      <td><?php echo $actual; ?></td>
      <td><?php echo $forecast; ?></td>
      <td><?php echo $actualForecast; ?></td>
      <td class="<?php 
        // For regular rows, we use the database variance value
        $rowVariance = $row['variance_percentage'] !== null ? floatval($row['variance_percentage']) : 0;
        if ($rowVariance > 0) {
          echo 'variance-positive';
        } elseif ($rowVariance < 0) {
          echo 'variance-negative';
        } else {
          echo 'variance-zero';
        }
      ?>"><?php echo $variance; ?></td>
    <?php endif; ?>
  </tr>
<?php 
    $firstPeriod = false;
  endforeach; 

  // If Annual Total row does not exist, add it after all periods
  if (!$hasAnnualTotal && !$isGrandTotal) : ?>
    <tr class="summary-section">
      <td>Annual Total</td>
      <td><?php echo number_format($annualBudget, 2); ?></td>
      <td><?php echo number_format($annualActual, 2); ?></td>
      <td><?php echo number_format($annualForecast, 2); ?></td>
      <td><?php echo number_format($annualActualForecast, 2); ?></td>
      <td class="<?php 
        if ($annualVariance > 0) {
          echo 'variance-positive';
        } elseif ($annualVariance < 0) {
          echo 'variance-negative';
        } else {
          echo 'variance-zero';
        }
      ?>"><?php echo $annualVariance . '%'; ?></td>
    </tr>
  <?php endif; 
endforeach; 
?>
  </tbody>
</table>


            </div>
          </div>
        </div>

        <!-- Section 3: Forecast 2025 -->
        <div class="print-section" id="section3-table" style="display: none;">
          <div class="card-header">
            Forecast Year <?php echo $selectedYear; ?>
          </div>
          

          
          <div class="card-body">
            <div class="table-container">
              <table class="vertical-table" border="1" cellspacing="0" cellpadding="5">
                <thead>
                  <tr>
                    <th rowspan="2">Category</th>
                    <th colspan="2">Q1</th>
                    <th colspan="2">Q2</th>
                    <th colspan="2">Q3</th>
                    <th colspan="2">Q4</th>
                    <th colspan="3">Annual totals</th>
                  </tr>
                  <tr>
                    <th>Budget</th>
                    <th>Actual</th>
                    <th>Budget</th>
                    <th>Actual</th>
                    <th>Budget</th>
                    <th>Forecast</th>
                    <th>Budget</th>
                    <th>Forecast</th>
                    <th>Budget</th>
                    <th>Actual + Forecast</th>
                    <th>Variance (%)</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                // Prepare display data per category
                $displayedCategories = [];
                
                foreach ($section3Categories as $categoryName => $quarters) {
                  // Skip database "Total" row
                  if (strtolower($categoryName) === 'total') continue;

                  $isGrandTotal = (strtolower($categoryName) === 'grand total');
                  $annualTotal = $categoryTotals[$categoryName]['Annual Total'] ?? null;
                  $total = $categoryTotals[$categoryName]['Total'] ?? null;
                  
                  // Skip if we've already displayed this category
                  if (in_array($categoryName, $displayedCategories)) continue;
                  $displayedCategories[] = $categoryName;
                  
                  // Determine CSS class based on category type
                  $rowClass = $isGrandTotal ? 'grand-total-section' : 'category-header';
                ?>
                  <tr class="<?php echo $rowClass; ?>">
                    <td data-label="Category"><?php echo htmlspecialchars($categoryName); ?></td>
                    
                    <!-- Q1 data -->
                    <td data-label="Q1 Budget"></i> <?php echo isset($quarters['Q1']['budget']) && $quarters['Q1']['budget'] !== null ? number_format($quarters['Q1']['budget'], 2) : '-'; ?></td>
                    <td data-label="Q1 Actual"></i> <?php echo isset($quarters['Q1']['actual']) && $quarters['Q1']['actual'] !== null ? number_format($quarters['Q1']['actual'], 2) : '-'; ?></td>
                    
                    <!-- Q2 data -->
                    <td data-label="Q2 Budget"></i> <?php echo isset($quarters['Q2']['budget']) && $quarters['Q2']['budget'] !== null ? number_format($quarters['Q2']['budget'], 2) : '-'; ?></td>
                    <td data-label="Q2 Actual"></i> <?php echo isset($quarters['Q2']['actual']) && $quarters['Q2']['actual'] !== null ? number_format($quarters['Q2']['actual'], 2) : '-'; ?></td>
                    
                    <!-- Q3 data -->
                    <td data-label="Q3 Budget"></i> <?php echo isset($quarters['Q3']['budget']) && $quarters['Q3']['budget'] !== null ? number_format($quarters['Q3']['budget'], 2) : '-'; ?></td>
                    <td data-label="Q3 Forecast"></i> <?php echo isset($quarters['Q3']['forecast']) && $quarters['Q3']['forecast'] !== null ? number_format($quarters['Q3']['forecast'], 2) : '-'; ?></td>
                    
                    <!-- Q4 data -->
                    <td data-label="Q4 Budget"></i> <?php echo isset($quarters['Q4']['budget']) && $quarters['Q4']['budget'] !== null ? number_format($quarters['Q4']['budget'], 2) : '-'; ?></td>
                    <td data-label="Q4 Forecast"></i> <?php echo isset($quarters['Q4']['forecast']) && $quarters['Q4']['forecast'] !== null ? number_format($quarters['Q4']['forecast'], 2) : '-'; ?></td>
                    
                    <!-- Annual totals -->
                    <td data-label="Annual Budget"></i> <?php echo $annualTotal && $annualTotal['budget'] !== null ? number_format($annualTotal['budget'], 2) : '-'; ?></td>
                    <td data-label="Actual + Forecast"></i> <?php echo $annualTotal && $annualTotal['actual_plus_forecast'] !== null ? number_format($annualTotal['actual_plus_forecast'], 2) : '-'; ?></td>
                    <td data-label="Variance"><?php 
                      $annualVariance = $annualTotal && $annualTotal['variance_percentage'] !== null ? floatval($annualTotal['variance_percentage']) : 0;
                      $varianceClass = '';
                      if ($annualVariance > 0) {
                        $varianceClass = 'variance-positive';
                      } else if ($annualVariance < 0) {
                        $varianceClass = 'variance-negative';
                      } else {
                        $varianceClass = 'variance-zero';
                      }
                      echo '<span class="' . $varianceClass . '">' . ($annualTotal && $annualTotal['variance_percentage'] !== null ? $annualTotal['variance_percentage'] . '%' : '0%') . '</span>';
                    ?></td>
                  </tr>
                <?php
                }
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Certification Section -->
        <div class="certification-section print-section">
          <div class="certification-header">
            Certify
            <span class="certified-badge no-print"><i class="fas fa-check-circle"></i> Certify</span>
          </div>

          <div class="certification-form">
            <div class="form-group">
              <label for="granteeName">Grantee Name</label>
              <input type="text" id="granteeName" value="<?php echo htmlspecialchars($organizationName); ?>">
            </div>

            <div class="form-group">
              <label for="reportDate">Report Date</label>
              <input type="date" id="reportDate">
            </div>

            <div class="form-group full-width">
              <label>Certification Statement</label>
              <textarea>The undersigned certify that this financial report has been prepared from the books and records...</textarea>
            </div>

            <div class="form-group">
              <label>Name</label>
              <input type="text" value="John Smith">
            </div>

            <div class="form-group">
              <label>Authorized Signature</label>
              <div class="signature-box"> <!-- Will print as line --> </div>
            </div>

            <div class="form-group">
              <label>Date Submitted</label>
              <input type="date" value="2023-10-15">
            </div>

            <div class="form-group">
              <label>MMI Technical Program Reviewer</label>
              <input type="text" value="Sarah Johnson">
            </div>

            <div class="form-group">
              <label>Signature</label>
              <div class="signature-box"> <!-- Will print as line --> </div>
            </div>

            <div class="form-group full-width no-print">
              <label>Upload Signed Document</label>
              <div class="file-upload" id="fileUpload">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click to upload signed document or drag and drop</p>
                <p>PDF, JPG, PNG (Max 5MB)</p>
              </div>
              <input type="file" id="fileInput" style="display: none;" accept=".pdf,.jpg,.jpeg,.png">
              <div id="uploadedFileContainer" style="display: none;" class="uploaded-file">
                <i class="fas fa-check-circle"></i>
                <span id="uploadedFileName">document.pdf</span>
              </div>
            </div>
          </div>

          <div class="action-buttons no-print">
            <button class="btn-outline" id="printBtn"><i class="fas fa-print"></i> Print Certificate</button>
            <button class="btn-primary" id="uploadBtn"><i class="fas fa-upload"></i> Upload Signed Copy</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script>
    document.getElementById('reportDate').valueAsDate = new Date();

    // Simple print functionality - navigate to dedicated print page
    document.addEventListener('DOMContentLoaded', function() {
      const printButtons = document.querySelectorAll('#printBtn');
      
      printButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          
          // Get current year and cluster
          const yearSelect = document.getElementById('yearFilter');
          const clusterSelect = document.getElementById('clusterFilter');
          const selectedYear = yearSelect ? yearSelect.value : '1';
          const selectedCluster = clusterSelect ? clusterSelect.value : '';
          
          // Get certification form data
          const granteeName = document.getElementById('granteeName') ? document.getElementById('granteeName').value : '';
          const reportDate = document.getElementById('reportDate') ? document.getElementById('reportDate').value : '';
          const certStatement = document.querySelector('textarea') ? document.querySelector('textarea').value : '';
          const nameField = document.querySelector('input[value="John Smith"]') ? document.querySelector('input[value="John Smith"]').value : 'John Smith';
          const dateSubmitted = document.querySelector('input[value="2023-10-15"]') ? document.querySelector('input[value="2023-10-15"]').value : '10/15/2023';
          const reviewer = document.querySelector('input[value="Sarah Johnson"]') ? document.querySelector('input[value="Sarah Johnson"]').value : 'Sarah Johnson';
          
          // Build URL with parameters
          let printUrl = `print_forecast_budget.php?year=${selectedYear}`;
          
          // Add cluster parameter if selected
          if (selectedCluster) {
            printUrl += `&cluster=${encodeURIComponent(selectedCluster)}`;
          }
          
          // Add certification form data
          printUrl += `&grantee_name=${encodeURIComponent(granteeName)}&report_date=${encodeURIComponent(reportDate)}&cert_statement=${encodeURIComponent(certStatement)}&name=${encodeURIComponent(nameField)}&date_submitted=${encodeURIComponent(dateSubmitted)}&reviewer=${encodeURIComponent(reviewer)}`;
          
          // Open print page in new window
          window.open(printUrl, '_blank');
        });
      });
    });

    // Table selection functionality
    const tableSelectionDropdown = document.getElementById('tableSelection');
    const section2Table = document.getElementById('section2-table');
    const section3Table = document.getElementById('section3-table');

    if (tableSelectionDropdown) {
      tableSelectionDropdown.addEventListener('change', function() {
        if (this.value === 'section2') {
          section2Table.style.display = 'block';
          section3Table.style.display = 'none';
        } else if (this.value === 'section3') {
          section2Table.style.display = 'none';
          section3Table.style.display = 'block';
        }
      });
    }

    // Year filter functionality
    const yearFilterDropdown = document.getElementById('yearFilter');
    if (yearFilterDropdown) {
      yearFilterDropdown.addEventListener('change', function() {
        const selectedYear = this.value;
        updatePageTitle(selectedYear);
      });
    }

    function updatePageTitle(year) {
      const pageTitle = document.querySelector('.page-title');
      const section2Header = document.querySelector('#section2-table .card-header');
      const section3Header = document.querySelector('#section3-table .card-header');
      
      if (year) {
        pageTitle.textContent = `Year ${year} Forecast Budget Report`;
        section2Header.textContent = `Forecast Budget Year ${year}`;
        section3Header.textContent = `Forecast Year ${year}`;
      } else {
        pageTitle.textContent = 'Year 1 Forecast Budget Report';
        section2Header.textContent = 'Forecast Budget Year 1';
        section3Header.textContent = 'Forecast Year 1';
      }
    }

    // Export Excel functionality - Combined Budget and Transactions as ZIP with CSV files
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    if (exportExcelBtn) {
      exportExcelBtn.addEventListener('click', function() {
        exportCombinedToExcel();
      });
    }

    function exportCombinedToExcel() {
      // Get filters for consistent export
      const year = yearFilterDropdown.value || '1';
      const clusterSelect = document.getElementById('clusterFilter');
      const selectedCluster = clusterSelect ? clusterSelect.value : '';

      // Map display year to actual year for transaction export
      // Based on current year and selected year display value
      const currentYear = new Date().getFullYear();
      let actualYear;
      
      // For display years 1-6, map to current year and next few years
      switch(year) {
        case '1':
          actualYear = currentYear;
          break;
        case '2':
          actualYear = currentYear + 1;
          break;
        case '3':
          actualYear = currentYear + 2;
          break;
        case '4':
          actualYear = currentYear + 3;
          break;
        case '5':
          actualYear = currentYear + 4;
          break;
        case '6':
          actualYear = currentYear + 5;
          break;
        default:
          actualYear = currentYear;
      }

      exportExcelBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating ZIP File...';
      exportExcelBtn.disabled = true;

      // 1. Get Table 2 CSV from frontend
      const table = document.querySelector('#section3-table .vertical-table');
      if (!table) {
        showNotification('Table 2 not found!', 'error');
        exportExcelBtn.innerHTML = '<i class="fas fa-file-archive"></i> Export ZIP (2 CSV Files)';
        exportExcelBtn.disabled = false;
        return;
      }

      // Ensure Grand Total is calculated and present
      if (!table.querySelector('tr.grand-total-section')) {
        if (typeof calculateTable2GrandTotal === 'function') calculateTable2GrandTotal();
      }

      // Get all categories from the table (excluding any existing Grand Total rows)
      const categories = [];
      const categoryCells = table.querySelectorAll('tbody tr td:first-child');
      categoryCells.forEach(cell => {
        const categoryName = cell.textContent.trim();
        // Skip any rows that contain 'Grand Total' or variations
        if (!categories.includes(categoryName) && 
            categoryName !== '' && 
            !categoryName.toLowerCase().includes('grand total') &&
            !categoryName.toLowerCase().includes('grand tot')) {
          categories.push(categoryName);
        }
      });

      // Create proper CSV structure
      let csvRows = [];
      
      // Header row 1: "Section 2: Forecast YYYY" spanning all columns
      csvRows.push(['Section 2: Forecast ' + actualYear, '', '', '', '', '', '', '', '', '', '', '']);
      
      // Header row 2: Q1, Q2, Q3, Q4, Annual totals
      csvRows.push(['', 'Q1', '', 'Q2', '', 'Q3', '', 'Q4', '', 'Annual totals', '', '']);
      
      // Header row 3: Budget/Actual/Forecast columns
      csvRows.push([
        '', 
        'Budget', 'Actual', 
        'Budget', 'Actual', 
        'Budget', 'Forecast', 
        'Budget', 'Forecast', 
        'Budget', 'Actual + Forecast', 'Variance (%)'
      ]);
      
      // Now generate a row for each category with the proper data structure
      let grandTotalData = {
        q1_budget: 0, q1_actual: 0,
        q2_budget: 0, q2_actual: 0,
        q3_budget: 0, q3_forecast: 0,
        q4_budget: 0, q4_forecast: 0,
        annual_budget: 0, annual_actual_forecast: 0, variance: 0
      };
      
      categories.forEach(category => {
        // Find all rows for this category
        const categoryRows = Array.from(table.querySelectorAll('tbody tr')).filter(row => 
          row.querySelector('td:first-child') && 
          row.querySelector('td:first-child').textContent.trim() === category
        );
        
        if (categoryRows.length > 0) {
          // Extract data for this category
          const rowData = { 
            category: category,
            q1_budget: '', q1_actual: '',
            q2_budget: '', q2_actual: '',
            q3_budget: '', q3_forecast: '',
            q4_budget: '', q4_forecast: '',
            annual_budget: '', annual_actual_forecast: '', variance: ''
          };
          
          // Get the annual totals row for this category
          const annualRow = categoryRows.find(row => {
            const cells = row.querySelectorAll('td');
            // Check if this is the annual row by examining if it has the annual_budget cell
            return cells.length >= 11 && cells[9] && cells[9].hasAttribute('data-label') && 
                   cells[9].getAttribute('data-label') === 'Annual Budget';
          });
          
          if (annualRow) {
            const cells = annualRow.querySelectorAll('td');
            
            // Get Q1 data
            if (cells[1] && cells[2]) {
              rowData.q1_budget = cells[1].textContent.trim();
              rowData.q1_actual = cells[2].textContent.trim();
            }
            
            // Get Q2 data
            if (cells[3] && cells[4]) {
              rowData.q2_budget = cells[3].textContent.trim();
              rowData.q2_actual = cells[4].textContent.trim();
            }
            
            // Get Q3 data
            if (cells[5] && cells[6]) {
              rowData.q3_budget = cells[5].textContent.trim();
              rowData.q3_forecast = cells[6].textContent.trim();
            }
            
            // Get Q4 data
            if (cells[7] && cells[8]) {
              rowData.q4_budget = cells[7].textContent.trim();
              rowData.q4_forecast = cells[8].textContent.trim();
            }
            
            // Get Annual data
            if (cells[9] && cells[10] && cells[11]) {
              rowData.annual_budget = cells[9].textContent.trim();
              rowData.annual_actual_forecast = cells[10].textContent.trim();
              rowData.variance = cells[11].textContent.trim();
              
              // Add to grand total (skip rows with 'Grand Total' or 'Total' in category name)
              if (category !== 'Grand Total' && !category.toLowerCase().includes('total')) {
                // Convert to numbers and add to grand total
                grandTotalData.annual_budget += parseFloat(rowData.annual_budget.replace(/[^\d.-]/g, '')) || 0;
                grandTotalData.annual_actual_forecast += parseFloat(rowData.annual_actual_forecast.replace(/[^\d.-]/g, '')) || 0;
                
                // Add quarterly data to grand totals
                grandTotalData.q1_budget += parseFloat(rowData.q1_budget.replace(/[^\d.-]/g, '')) || 0;
                grandTotalData.q1_actual += parseFloat(rowData.q1_actual.replace(/[^\d.-]/g, '')) || 0;
                grandTotalData.q2_budget += parseFloat(rowData.q2_budget.replace(/[^\d.-]/g, '')) || 0;
                grandTotalData.q2_actual += parseFloat(rowData.q2_actual.replace(/[^\d.-]/g, '')) || 0;
                grandTotalData.q3_budget += parseFloat(rowData.q3_budget.replace(/[^\d.-]/g, '')) || 0;
                grandTotalData.q3_forecast += parseFloat(rowData.q3_forecast.replace(/[^\d.-]/g, '')) || 0;
                grandTotalData.q4_budget += parseFloat(rowData.q4_budget.replace(/[^\d.-]/g, '')) || 0;
                grandTotalData.q4_forecast += parseFloat(rowData.q4_forecast.replace(/[^\d.-]/g, '')) || 0;
              }
            }
          }
          
          // Add row to CSV data
          csvRows.push([
            rowData.category,
            rowData.q1_budget, rowData.q1_actual,
            rowData.q2_budget, rowData.q2_actual,
            rowData.q3_budget, rowData.q3_forecast,
            rowData.q4_budget, rowData.q4_forecast,
            rowData.annual_budget, rowData.annual_actual_forecast, rowData.variance
          ]);
        }
      });
      
      // Calculate variance for Grand Total
      if (grandTotalData.annual_budget !== 0) {
        grandTotalData.variance = ((grandTotalData.annual_actual_forecast - grandTotalData.annual_budget) / Math.abs(grandTotalData.annual_budget)) * 100;
      }
      
      // Format the grand total numbers
      const formatNumber = (num) => {
        return Number(num).toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
      };
      
      // Add Grand Total row
      csvRows.push([
        'Grand Total',
        formatNumber(grandTotalData.q1_budget), formatNumber(grandTotalData.q1_actual),
        formatNumber(grandTotalData.q2_budget), formatNumber(grandTotalData.q2_actual),
        formatNumber(grandTotalData.q3_budget), formatNumber(grandTotalData.q3_forecast),
        formatNumber(grandTotalData.q4_budget), formatNumber(grandTotalData.q4_forecast),
        formatNumber(grandTotalData.annual_budget), formatNumber(grandTotalData.annual_actual_forecast), 
        grandTotalData.variance.toFixed(2) + '%'
      ]);

      // Convert 2D array to CSV string with proper quoting
      const budgetCSV = csvRows.map(row => {
        return row.map(cell => {
          // If cell contains commas, quotes, or newlines, wrap in quotes
          if (cell && (typeof cell === 'string') && (cell.includes(',') || cell.includes('"') || cell.includes('\n'))) {
            // Escape any quotes in the cell value
            return '"' + cell.replace(/"/g, '""') + '"';
          }
          return cell;
        }).join(',');
      }).join('\r\n');

      // 2. Fetch transactions CSV from backend using actual year
      let downloadUrl = `ajax_handler.php?action=export_transactions_csv&year=${actualYear}`;
      // Always pass the selected cluster for filtering consistency
      if (selectedCluster) {
        downloadUrl += `&cluster=${encodeURIComponent(selectedCluster)}`;
      }

      fetch(downloadUrl)
        .then(response => response.text())
        .then(transactionsCSV => {
          // 3. Create ZIP file with both CSVs
          const zip = new JSZip();
          zip.file('Budget_Data_Table2.csv', budgetCSV);
          zip.file('Transactions.csv', transactionsCSV);

          zip.generateAsync({ type: 'blob' }).then(function(content) {
            const url = URL.createObjectURL(content);
            const a = document.createElement('a');
            a.href = url;
            // Include cluster information in the filename if it exists
            const clusterSuffix = selectedCluster ? `_${selectedCluster}` : '';
            a.download = `Budget_Report_Year_${year}${clusterSuffix}.zip`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            exportExcelBtn.innerHTML = '<i class="fas fa-file-archive"></i> Export ZIP (2 CSV Files)';
            exportExcelBtn.disabled = false;
            const clusterText = selectedCluster ? ` - ${selectedCluster}` : '';
            showNotification(`ZIP file exported successfully for Year ${year}${clusterText}!`, 'success');
          });
        })
        .catch(error => {
          exportExcelBtn.innerHTML = '<i class="fas fa-file-archive"></i> Export ZIP (2 CSV Files)';
          exportExcelBtn.disabled = false;
          showNotification('Export failed: ' + error.message, 'error');
        });
    }

    // File Upload Functionality with Drag & Drop
    const fileUpload = document.getElementById('fileUpload');
    const fileInput = document.getElementById('fileInput');
    const uploadedFileContainer = document.getElementById('uploadedFileContainer');
    const uploadedFileName = document.getElementById('uploadedFileName');
    const uploadBtn = document.getElementById('uploadBtn');

    // Click to upload
    fileUpload.addEventListener('click', () => {
      fileInput.click();
    });

    // File input change event
    fileInput.addEventListener('change', (e) => {
      handleFileSelect(e.target.files[0]);
    });

    // Drag and drop events
    fileUpload.addEventListener('dragover', (e) => {
      e.preventDefault();
      fileUpload.classList.add('drag-over');
    });

    fileUpload.addEventListener('dragleave', (e) => {
      e.preventDefault();
      fileUpload.classList.remove('drag-over');
    });

    fileUpload.addEventListener('drop', (e) => {
      e.preventDefault();
      fileUpload.classList.remove('drag-over');
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        handleFileSelect(files[0]);
      }
    });

    function handleFileSelect(file) {
      if (!file) return;
      
      // Validate file type
      const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
      if (!allowedTypes.includes(file.type)) {
        showNotification('Please select a PDF, JPG, or PNG file.', 'error');
        return;
      }
      
      // Validate file size (5MB limit)
      const maxSize = 5 * 1024 * 1024; // 5MB in bytes
      if (file.size > maxSize) {
        showNotification('File size must be less than 5MB.', 'error');
        return;
      }
      
      // Show uploaded file
      uploadedFileName.textContent = file.name;
      uploadedFileContainer.style.display = 'flex';
      fileUpload.style.display = 'none';
      
      // Store file reference for upload
      uploadBtn.dataset.file = file.name;
      
      showNotification(`File "${file.name}" selected successfully!`, 'success');
    }

    // Upload button functionality
    uploadBtn.addEventListener('click', () => {
      const fileName = uploadBtn.dataset.file;
      if (fileName) {
        // Get the actual file from the file input
        const fileInputElement = document.getElementById('fileInput');
        if (fileInputElement && fileInputElement.files.length > 0) {
          const file = fileInputElement.files[0];
          const currentYear = document.getElementById('yearFilter').value || '1';
          
          // Create FormData for file upload
          const formData = new FormData();
          formData.append('action', 'upload_certificate');
          formData.append('certificate', file);
          formData.append('year', currentYear);
          
          // Show upload progress
          uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
          uploadBtn.disabled = true;
          
          // Send AJAX request
          fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              uploadBtn.innerHTML = '<i class="fas fa-check"></i> Uploaded Successfully!';
              uploadBtn.classList.remove('btn-primary');
              uploadBtn.classList.add('btn-success');
              
              showNotification(`Certificate uploaded successfully for Year ${currentYear}! Budget data marked as certified.`, 'success');
              
              // Reset after delay
              setTimeout(() => {
                uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Signed Copy';
                uploadBtn.disabled = false;

                uploadBtn.classList.remove('btn-success');
                uploadBtn.classList.add('btn-primary');
                
                // Clear file selection
                uploadedFileContainer.style.display = 'none';
                fileUpload.style.display = 'block';
                fileInputElement.value = '';
                delete uploadBtn.dataset.file;
              }, 3000);
            } else {
              uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Signed Copy';
              uploadBtn.disabled = false;
              showNotification(data.message || 'Certificate upload failed!', 'error');
            }
          })
          .catch(error => {
            uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Signed Copy';
            uploadBtn.disabled = false;
            showNotification('Upload failed: ' + error.message, 'error');
          });
        } else {
          showNotification('No file selected!', 'error');
        }
      } else {
        showNotification('Please select a file first.', 'error');
      }
    });

    // Remove uploaded file functionality
    uploadedFileContainer.addEventListener('click', () => {
      uploadedFileContainer.style.display = 'none';
      fileUpload.style.display = 'block';
      fileInput.value = '';
      delete uploadBtn.dataset.file;
      showNotification('File removed successfully.', 'info');
    });

    // Certify Report button functionality
    const certifyBtn = document.getElementById('certifyBtn');
    if (certifyBtn) {
      certifyBtn.addEventListener('click', () => {
       
        // Create a file input for certificate upload
        const certificateInput = document.createElement('input');
        certificateInput.type = 'file';
        certificateInput.accept = '.pdf';
        certificateInput.style.display = 'none';
        
        certificateInput.addEventListener('change', (e) => {
          const file = e.target.files[0];
          if (file) {
            const currentYear = document.getElementById('yearFilter').value || '1';
            
            // Validate file type
            if (file.type !== 'application/pdf') {
              showNotification('Please select a PDF file only.', 'error');
              return;
            }
            
            // Validate file size (10MB limit)
            const maxSize = 10 * 1024 * 1024; // 10MB
            if (file.size > maxSize) {
              showNotification('Certificate file size must be less than 10MB.', 'error');
              return;
            }
            
            // Create FormData for file upload
            const formData = new FormData();
            formData.append('action', 'upload_certificate');
            formData.append('certificate', file);
            formData.append('year', currentYear);
            
            // Show upload progress
            certifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading Certificate...';
            certifyBtn.disabled = true;
            
            // Send AJAX request
            fetch('ajax_handler.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                certifyBtn.innerHTML = '<i class="fas fa-check-circle"></i> Certified Successfully!';
                certifyBtn.classList.remove('btn-success');
                certifyBtn.classList.add('btn-primary');
                
                showNotification(`Certificate uploaded successfully for Year ${currentYear}! Budget data marked as certified.`, 'success');
                
                // Reset after delay
                setTimeout(() => {
                  certifyBtn.innerHTML = '<i class="fas fa-certificate"></i> Certify Report';
                  certifyBtn.disabled = false;
                  certifyBtn.classList.remove('btn-primary');
                  certifyBtn.classList.add('btn-success');
                }, 3000);
              } else {
                certifyBtn.innerHTML = '<i class="fas fa-certificate"></i> Certify Report';
                certifyBtn.disabled = false;
                showNotification(data.message || 'Certificate upload failed!', 'error');
              }
            })
            .catch(error => {

              certifyBtn.innerHTML = '<i class="fas fa-certificate"></i> Certify Report';
              certifyBtn.disabled = false;
              showNotification('Upload failed: ' + error.message, 'error');
            });
          }
          
          // Clean up the input element
          document.body.removeChild(certificateInput);
        });
        
        // Add to DOM and trigger click
        document.body.appendChild(certificateInput);
        certificateInput.click();
      });
    }

    // Notification system
    function showNotification(message, type = 'info') {
      const notification = document.createElement('div');
      notification.className = `notification notification-${type}`;
      notification.innerHTML = `
        <div class="notification-content">
          <i class="fas ${
            type === 'success' ? 'fa-check-circle' : 
            type === 'error' ? 'fa-exclamation-circle' : 
            type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'
          }"></i>
          <span>${message}</span>
        </div>
      `;
      
      // Add notification styles
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 400px;
        background: ${
          type === 'success' ? '#10b981' : 
          type === 'error' ? '#ef4444' : 
          type === 'warning' ? '#f59e0b' : '#3b82f6'
        };
      `;
      
      document.body.appendChild(notification);
      
      // Animate in
      setTimeout(() => {
        notification.style.transform = 'translateX(0)';
      }, 100);
      
      // Auto remove
      setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
          document.body.removeChild(notification);
        }, 300);
      }, 4000);
    }

    // Enhanced hover effects for quarterly data cells
    document.addEventListener('DOMContentLoaded', function() {
      const tables = document.querySelectorAll('.vertical-table');
      
      tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach((row, rowIndex) => {
          const cells = row.querySelectorAll('td');
          
          cells.forEach((cell, cellIndex) => {
            cell.addEventListener('mouseenter', function() {
              // Find the category cell for this row (could be in current row or previous rows due to rowspan)
              let categoryCell = null;
              let categoryHeaderRow = null;
              let annualTotalRow = null;
              let q1Row = null;
              
              // Check if current row has category cell
              if (row.querySelector('td[rowspan]')) {
                categoryCell = row.querySelector('td[rowspan]');
                categoryHeaderRow = row;
              } else {
                // Look backwards to find the category header row
                for (let i = rowIndex - 1; i >= 0; i--) {
                  const prevRow = rows[i];
                  const prevRowCategoryCell = prevRow.querySelector('td[rowspan]');
                  if (prevRowCategoryCell) {
                    categoryCell = prevRowCategoryCell;
                    categoryHeaderRow = prevRow;
                    break;
                  }
                }
              }
              
              // Find the Annual Total row and Q1 row for this category group
              if ( categoryHeaderRow) {
                const categoryRowspan = categoryCell ? parseInt(categoryCell.getAttribute('rowspan')) : 0;
                const categoryStartIndex = Array.from(rows).indexOf(categoryHeaderRow);
                const annualTotalIndex = categoryStartIndex + categoryRowspan - 1;
                
                // Find Annual Total row
                if (annualTotalIndex < rows.length && rows[annualTotalIndex].classList.contains('summary-section')) {
                  annualTotalRow = rows[annualTotalIndex];
                }
                
                // Find Q1 row (first row after category header)
                if (categoryStartIndex + 1 < rows.length) {
                  q1Row = rows[categoryStartIndex];
                }
              }
              
              // Apply highlights - now include when hovering on category cell itself
              const isHoveringCategoryCell = (categoryCell && cell === categoryCell);
              
              // Always highlight category cell (unless hovering on it directly, to avoid double native+JS highlighting)
              if (categoryCell && !isHoveringCategoryCell) {
                categoryCell.classList.add('category-native-highlight');
              }
              
              // Highlight the entire Annual Total row with professional background
              // Include when hovering on category cell
              if (annualTotalRow) {
                annualTotalRow.classList.add('annual-total-row-highlight');
                // Also highlight the Annual Total text field (first column) with dark blue
                const annualTotalFirstCell = annualTotalRow.querySelector('td:first-child');
                if (annualTotalFirstCell) {
                  annualTotalFirstCell.classList.add('annual-total-text-highlight');
                }
              }
              
              // Highlight Q1 row with professional background
              // Include when hovering on category cell
              if (q1Row) {
                q1Row.classList.add('q1-row-highlight');
              }
            });
            
            cell.addEventListener('mouseleave', function() {
              // Remove all highlights from all rows
              rows.forEach(r => {
                const allCells = r.querySelectorAll('td');
                allCells.forEach(c => {
                  c.classList.remove('category-native-highlight', 'annual-total-text-highlight');
                });
                r.classList.remove('annual-total-row-highlight', 'q1-row-highlight');
              });
            });
          });
        });
      });
    });

    // Initialize with default year
    updatePageTitle('2025');

    // Front-end Grand Total calculation for Table 2
    document.addEventListener('DOMContentLoaded', function() {
      function calculateTable2GrandTotal() {
        const table = document.querySelector('#section3-table .vertical-table');
        if (!table) return;
        
        // Initialize totals for all columns
        let q1BudgetTotal = 0, q1ActualTotal = 0;
        let q2BudgetTotal = 0, q2ActualTotal = 0;
        let q3BudgetTotal = 0, q3ForecastTotal = 0;
        let q4BudgetTotal = 0, q4ForecastTotal = 0;
        let annualBudgetTotal = 0, actualForecastTotal = 0;

        // Find all rows except the header and Grand Total
        const rows = table.querySelectorAll('tbody tr:not(.grand-total-section)');
        rows.forEach(row => {
          // Q1 data
          const q1BudgetCell = row.querySelector('td[data-label="Q1 Budget"]');
          const q1ActualCell = row.querySelector('td[data-label="Q1 Actual"]');
          if (q1BudgetCell && q1ActualCell) {
            const q1Budget = parseFloat(q1BudgetCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            const q1Actual = parseFloat(q1ActualCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            q1BudgetTotal += q1Budget;
            q1ActualTotal += q1Actual;
          }
          
          // Q2 data
          const q2BudgetCell = row.querySelector('td[data-label="Q2 Budget"]');
          const q2ActualCell = row.querySelector('td[data-label="Q2 Actual"]');
          if (q2BudgetCell && q2ActualCell) {
            const q2Budget = parseFloat(q2BudgetCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            const q2Actual = parseFloat(q2ActualCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            q2BudgetTotal += q2Budget;
            q2ActualTotal += q2Actual;
          }
          
          // Q3 data
          const q3BudgetCell = row.querySelector('td[data-label="Q3 Budget"]');
          const q3ForecastCell = row.querySelector('td[data-label="Q3 Forecast"]');
          if (q3BudgetCell && q3ForecastCell) {
            const q3Budget = parseFloat(q3BudgetCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            const q3Forecast = parseFloat(q3ForecastCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            q3BudgetTotal += q3Budget;
            q3ForecastTotal += q3Forecast;
          }
          
          // Q4 data
          const q4BudgetCell = row.querySelector('td[data-label="Q4 Budget"]');
          const q4ForecastCell = row.querySelector('td[data-label="Q4 Forecast"]');
          if (q4BudgetCell && q4ForecastCell) {
            const q4Budget = parseFloat(q4BudgetCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            const q4Forecast = parseFloat(q4ForecastCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            q4BudgetTotal += q4Budget;
            q4ForecastTotal += q4Forecast;
          }
          
          // Annual totals (for variance calculation)
          const annualBudgetCell = row.querySelector('td[data-label="Annual Budget"]');
          const actualForecastCell = row.querySelector('td[data-label="Actual + Forecast"]');
          if (annualBudgetCell && actualForecastCell) {
            const annualBudget = parseFloat(annualBudgetCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            const actualForecast = parseFloat(actualForecastCell.textContent.replace(/[^0-9.-]/g, '')) || 0;
            annualBudgetTotal += annualBudget;
            actualForecastTotal += actualForecast;
          }
        });

        // Calculate variance based on annual totals
        let variance = 0;
        if (annualBudgetTotal != 0) {
          variance = ((actualForecastTotal - annualBudgetTotal) / Math.abs(annualBudgetTotal)) * 100;
        }

        // Determine variance class based on global financial standards
        let varianceClass = 'variance-zero';
        if (variance > 0) {
          varianceClass = 'variance-positive'; // Overspent - red
        } else if (variance < 0) {
          varianceClass = 'variance-negative'; // Underspent - green
        }

        // Remove existing Grand Total row if present
        const oldGrandTotal = table.querySelector('tr.grand-total-section');
        if (oldGrandTotal) oldGrandTotal.remove();

        // Add Grand Total row with correct column count (13 columns total)
        const grandTotalRow = document.createElement('tr');
        grandTotalRow.className = 'grand-total-section';
        grandTotalRow.innerHTML = `
          <td class="grand-total-label">Grand Total</td>
          <td>${q1BudgetTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${q1ActualTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${q2BudgetTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${q2ActualTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${q3BudgetTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${q3ForecastTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${q4BudgetTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${q4ForecastTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${annualBudgetTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${actualForecastTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td><span class="${varianceClass}">${variance.toFixed(2)}%</span></td>
        `;
        table.querySelector('tbody').appendChild(grandTotalRow);
      }

      // Run on load and when switching tables
      calculateTable2GrandTotal();
      document.getElementById('tableSelection').addEventListener('change', function() {
        if (this.value === 'section3') {
          setTimeout(calculateTable2GrandTotal, 100); // Wait for table to show
        }
      });
      
      // Also recalculate when year changes
      document.getElementById('yearFilter').addEventListener('change', function() {
        if (document.getElementById('tableSelection').value === 'section3') {
          setTimeout(calculateTable2GrandTotal, 100);
        }
      });
    });

    // Add after Table 1 rendering (inside <script> tag)
    document.addEventListener('DOMContentLoaded', function() {
      function calculateTable1GrandTotal() {
        const table = document.querySelector('#section2-table .vertical-table');
        if (!table) return;

        let grandBudget = 0, grandActual = 0, grandForecast = 0, grandActualForecast = 0;

        // Only sum Annual Total rows
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
          // Skip the dynamic Grand Total row
          if (row.classList.contains('grand-total-section')) return;

          const cells = Array.from(row.querySelectorAll('td'));
          if (cells.length === 0) return;

          // If the row contains the category cell (rowspan), period is at index 1 (2nd col).
          // Otherwise period is at index 0 (1st col).
          const hasCategoryCell = (cells.length === 7); // Category + Period + 5 numeric + variance
          const periodCellIndex = hasCategoryCell ? 1 : 0;
          const budgetIndex = hasCategoryCell ? 2 : 1;
          const actualIndex = hasCategoryCell ? 3 : 2;
          const forecastIndex = hasCategoryCell ? 4 : 3;
          const actualForecastIndex = hasCategoryCell ? 5 : 4;

          const period = cells[periodCellIndex].textContent.trim();
          if (period === 'Annual Total') {
            grandBudget += parseFloat((cells[budgetIndex]?.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
            grandActual += parseFloat((cells[actualIndex]?.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
            grandForecast += parseFloat((cells[forecastIndex]?.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
            grandActualForecast += parseFloat((cells[actualForecastIndex]?.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
          }
        });

        // Calculate variance
        let variance = 0;
        if (grandBudget != 0) {
          variance = ((grandActualForecast - grandBudget) / Math.abs(grandBudget)) * 100;
        }
        let varianceClass = 'variance-zero';
        if (variance > 0) varianceClass = 'variance-positive';
        else if (variance < 0) varianceClass = 'variance-negative';

        // Remove existing Grand Total row if present
        const existingGrandTotal = table.querySelector('tr.grand-total-section');
        if (existingGrandTotal) existingGrandTotal.remove();

        // Add Grand Total row
        const grandTotalRow = document.createElement('tr');
        grandTotalRow.className = 'grand-total-section';
        grandTotalRow.innerHTML = `
          <td class="grand-total-label">Grand Total</td>
          <td></td>
          <td>${grandBudget.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${grandActual.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${grandForecast.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td>${grandActualForecast.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
          <td><span class="${varianceClass}">${variance.toFixed(2)}%</span></td>
        `;
        table.querySelector('tbody').appendChild(grandTotalRow);
      }

      calculateTable1GrandTotal();
      // Recalculate if table changes (add listeners if needed)
    });

    function getTable2ExportData() {
      const table = document.querySelector('#section3-table .vertical-table');
      if (!table) return [];
      const rows = table.querySelectorAll('tbody tr:not(.grand-total-section)');
      let totals = {
        q1Budget: 0, q1Actual: 0,
        q2Budget: 0, q2Actual: 0,
        q3Budget: 0, q3Forecast: 0,
        q4Budget: 0, q4Forecast: 0,
        annualBudget: 0, actualForecast: 0
      };
      rows.forEach(row => {
        totals.q1Budget += parseFloat(row.querySelector('td[data-label="Q1 Budget"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
        totals.q1Actual += parseFloat(row.querySelector('td[data-label="Q1 Actual"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
        totals.q2Budget += parseFloat(row.querySelector('td[data-label="Q2 Budget"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
        totals.q2Actual += parseFloat(row.querySelector('td[data-label="Q2 Actual"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
        totals.q3Budget += parseFloat(row.querySelector('td[data-label="Q3 Budget"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
        totals.q3Forecast += parseFloat(row.querySelector('td[data-label="Q3 Forecast"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
        totals.q4Budget += parseFloat(row.querySelector('td[data-label="Q4 Budget"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
        totals.q4Forecast += parseFloat(row.querySelector('td[data-label="Q4 Forecast"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
        totals.annualBudget += parseFloat(row.querySelector('td[data-label="Annual Budget"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
        totals.actualForecast += parseFloat(row.querySelector('td[data-label="Actual + Forecast"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
      });
      // Calculate variance
      let variance = 0;
      if (totals.annualBudget !== 0) {
        variance = ((totals.actualForecast - totals.annualBudget) / Math.abs(totals.annualBudget)) * 100;
      }
      return [
        'Grand Total',
        totals.q1Budget, totals.q1Actual,
        totals.q2Budget, totals.q2Actual,
        totals.q3Budget, totals.q3Forecast,
        totals.q4Budget, totals.q4Forecast,
        totals.annualBudget, totals.actualForecast,
        variance.toFixed(2) + '%'
      ];
    }

    function exportTable2ToCSV() {
  const table = document.querySelector('#section3-table .vertical-table');
  if (!table) return;

  // Ensure Grand Total is calculated and present
  if (!table.querySelector('tr.grand-total-section')) {
    // If not present, calculate and append
    if (typeof calculateTable2GrandTotal === 'function') calculateTable2GrandTotal();
  }

  let csvRows = [];
  // Get headers
  const headers = Array.from(table.querySelectorAll('thead tr')).map(tr =>
    Array.from(tr.querySelectorAll('th')).map(th => th.textContent.trim())
  );
  headers.forEach(headerRow => csvRows.push(headerRow.join(',')));

  // Get all rows including Grand Total
  const rows = table.querySelectorAll('tbody tr');
  rows.forEach(row => {
    const cells = Array.from(row.querySelectorAll('td')).map(td => {
      // Remove commas from cell text to avoid CSV issues
      return td.textContent.replace(/,/g, '');
    });
    csvRows.push(cells.join(','));
  });

  // Create CSV string
  const csvString = csvRows.join('\r\n');

  // Download CSV
  const blob = new Blob([csvString], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'Budget_Data_Table2.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

    // Update metric cards based on Table 1 data
    function updateMetricCardsFromTable1() {
      const table = document.querySelector('#section2-table .vertical-table');
      if (!table) return;

      let totalBudget = 0, totalActual = 0;

      // Only sum Annual Total rows
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(row => {
        // Skip the dynamic Grand Total row
        if (row.classList.contains('grand-total-section')) return;

        const cells = Array.from(row.querySelectorAll('td'));
        if (cells.length === 0) return;

        const hasCategoryCell = (cells.length === 7);
        const periodCellIndex = hasCategoryCell ? 1 : 0;
        const budgetIndex = hasCategoryCell ? 2 : 1;
        const actualIndex = hasCategoryCell ? 3 : 2;

        const period = cells[periodCellIndex].textContent.trim();
        if (period === 'Annual Total') {
          totalBudget += parseFloat((cells[budgetIndex]?.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
          totalActual += parseFloat((cells[actualIndex]?.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
        }
      });

      // Utilization and Remaining
      const utilization = (totalBudget > 0) ? ((totalActual / totalBudget) * 100).toFixed(1) : '0';
      const remaining = (totalBudget - totalActual).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});

      // Update cards
      document.querySelectorAll('.metric-card-mini')[0].querySelector('div > div:nth-child(2) > div:nth-child(2)').textContent = totalBudget.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
      document.querySelectorAll('.metric-card-mini')[1].querySelector('div > div:nth-child(2) > div:nth-child(2)').textContent = totalActual.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
      document.querySelectorAll('.metric-card-mini')[2].querySelector('div > div:nth-child(2) > div:nth-child(2)').textContent = utilization + '%';
      document.querySelectorAll('.metric-card-mini')[3].querySelector('div > div:nth-child(2) > div:nth-child(2)').textContent = remaining;
    }

    // Run after Table 1 is rendered
    document.addEventListener('DOMContentLoaded', function() {
      updateMetricCardsFromTable1();
      // If Table 1 changes, call again
    });

    document.addEventListener('DOMContentLoaded', function() {
  function fillTable2AnnualTotals() {
    const table = document.querySelector('#section3-table .vertical-table');
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr:not(.grand-total-section)');
    rows.forEach(row => {
      // Get quarterly cells
      const q1Budget = parseFloat(row.querySelector('td[data-label="Q1 Budget"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
      const q1Actual = parseFloat(row.querySelector('td[data-label="Q1 Actual"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
      const q2Budget = parseFloat(row.querySelector('td[data-label="Q2 Budget"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
      const q2Actual = parseFloat(row.querySelector('td[data-label="Q2 Actual"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
      const q3Budget = parseFloat(row.querySelector('td[data-label="Q3 Budget"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
      const q3Forecast = parseFloat(row.querySelector('td[data-label="Q3 Forecast"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
      const q4Budget = parseFloat(row.querySelector('td[data-label="Q4 Budget"]').textContent.replace(/[^0-9.-]/g, '')) || 0;
      const q4Forecast = parseFloat(row.querySelector('td[data-label="Q4 Forecast"]').textContent.replace(/[^0-9.-]/g, '')) || 0;

      // Calculate annual totals
      const annualBudget = q1Budget + q2Budget + q3Budget + q4Budget;
      const actualForecast = q1Actual + q2Actual + q3Forecast + q4Forecast;

      // Calculate variance
      let variance = 0;
      if (annualBudget !== 0) {
        variance = ((actualForecast - annualBudget) / Math.abs(annualBudget)) * 100;
      }
      let varianceClass = 'variance-zero';
      if (variance > 0) varianceClass = 'variance-positive';
      else if (variance < 0) varianceClass = 'variance-negative';

      // Fill annual totals columns
      row.querySelector('td[data-label="Annual Budget"]').textContent = annualBudget.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
      row.querySelector('td[data-label="Actual + Forecast"]').textContent = actualForecast.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
      row.querySelector('td[data-label="Variance"]').innerHTML = `<span class="${varianceClass}">${variance.toFixed(2)}%</span>`;
    });
  }

  fillTable2AnnualTotals();
  // Recalculate if table changes (add listeners if needed)
});
  </script>
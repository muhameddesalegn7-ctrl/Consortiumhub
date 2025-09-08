<?php
// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Set default year if not specified - use current year
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get cluster from GET parameter
$selectedCluster = isset($_GET['cluster']) ? $_GET['cluster'] : null;

// Get organization name based on year
$organizationNames = [
  2025 => 'Consortium Hub Organization 2025',
  2026 => 'Consortium Hub Organization 2026', 
  2027 => 'Consortium Hub Organization 2027',
  2028 => 'Consortium Hub Organization 2028',
  2029 => 'Consortium Hub Organization 2029',
  2030 => 'Consortium Hub Organization 2030'
];
$organizationName = isset($organizationNames[$selectedYear]) ? $organizationNames[$selectedYear] : 'Consortium Hub Organization';

// Fetch data for Section 3 table (consolidated view by category with all quarters) with cluster filtering
$section3Query = "SELECT * FROM budget_data WHERE year = ? AND period_name IN ('Q1', 'Q2', 'Q3', 'Q4', 'Annual Total', 'Total')";

// Add cluster condition if a specific cluster is selected
if ($selectedCluster) {
    $section3Query .= " AND cluster = ?";
}

$section3Query .= " ORDER BY 
  CASE 
    WHEN category_name LIKE '1.%' THEN 1
    WHEN category_name LIKE '2.%' THEN 2
    WHEN category_name LIKE '3.%' THEN 3
    WHEN category_name LIKE '4.%' THEN 4
    WHEN category_name LIKE '5.%' THEN 5
    ELSE 6
  END, 
  CASE 
    WHEN period_name = 'Annual Total' THEN 1
    WHEN period_name = 'Total' THEN 2
    ELSE 3
  END";

// Prepare statement with cluster parameter if needed
if ($selectedCluster) {
    $stmt = $conn->prepare($section3Query);
    $stmt->bind_param("is", $selectedYear, $selectedCluster);
} else {
    $stmt = $conn->prepare($section3Query);
    $stmt->bind_param("i", $selectedYear);
}
$stmt->execute();
$section3Result = $stmt->get_result();

// Process data for Section 3 (pivot format)
$section3Categories = [];
$categoryTotals = [];

while ($row = $section3Result->fetch_assoc()) {
  $categoryName = $row['category_name'];
  $periodName = $row['period_name'];
  
  if ($periodName == 'Annual Total' || $periodName == 'Total') {
    $categoryTotals[$categoryName][$periodName] = $row;
  } else {
    if (!isset($section3Categories[$categoryName])) {
      $section3Categories[$categoryName] = [
        'Q1' => null,
        'Q2' => null,
        'Q3' => null,
        'Q4' => null
      ];
    }
    $section3Categories[$categoryName][$periodName] = $row;
  }
}

// Calculate Grand Total for Section 3 (quarterly and annual)
$quarterTotals = [
  'Q1' => ['budget' => 0, 'actual' => 0],
  'Q2' => ['budget' => 0, 'actual' => 0],
  'Q3' => ['budget' => 0, 'forecast' => 0],
  'Q4' => ['budget' => 0, 'forecast' => 0]
];
$annualBudgetTotal = 0;
$annualActualForecastTotal = 0;

foreach ($section3Categories as $categoryName => $quarters) {
  if (strtolower($categoryName) === 'total') continue;

  // Q1
  if ($quarters['Q1']) {
    $quarterTotals['Q1']['budget'] += floatval($quarters['Q1']['budget'] ?? 0);
    $quarterTotals['Q1']['actual'] += floatval($quarters['Q1']['actual'] ?? 0);
  }
  // Q2
  if ($quarters['Q2']) {
    $quarterTotals['Q2']['budget'] += floatval($quarters['Q2']['budget'] ?? 0);
    $quarterTotals['Q2']['actual'] += floatval($quarters['Q2']['actual'] ?? 0);
  }
  // Q3
  if ($quarters['Q3']) {
    $quarterTotals['Q3']['budget'] += floatval($quarters['Q3']['budget'] ?? 0);
    $quarterTotals['Q3']['forecast'] += floatval($quarters['Q3']['forecast'] ?? 0);
  }
  // Q4
  if ($quarters['Q4']) {
    $quarterTotals['Q4']['budget'] += floatval($quarters['Q4']['budget'] ?? 0);
    $quarterTotals['Q4']['forecast'] += floatval($quarters['Q4']['forecast'] ?? 0);
  }

  // Annual totals
  if (isset($categoryTotals[$categoryName]['Annual Total'])) {
    $annualTotal = $categoryTotals[$categoryName]['Annual Total'];
    $annualBudgetTotal += floatval($annualTotal['budget'] ?? 0);
    $annualActualForecastTotal += floatval($annualTotal['actual_plus_forecast'] ?? 0);
  }
}

// Calculate variance for Grand Total
$grandVariance = ($annualBudgetTotal != 0) ? round((($annualActualForecastTotal - $annualBudgetTotal) / abs($annualBudgetTotal)) * 100, 2) : 0;


// Get form data from URL parameters
$granteeName = isset($_GET['grantee_name']) ? $_GET['grantee_name'] : $organizationName;
$reportDate = isset($_GET['report_date']) ? $_GET['report_date'] : date('m/d/Y');
$certificationStatement = isset($_GET['cert_statement']) ? $_GET['cert_statement'] : 'The undersigned certify that this financial report has been prepared from the books and records...';
$name = isset($_GET['name']) ? $_GET['name'] : 'John Smith';
$dateSubmitted = isset($_GET['date_submitted']) ? $_GET['date_submitted'] : '10/15/2023';
$reviewer = isset($_GET['reviewer']) ? $_GET['reviewer'] : 'Sarah Johnson';

// Fetch data for Table 1 (Forecast Budget) with cluster filtering
$section2Query = "SELECT * FROM budget_data WHERE year = ?";
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
    ELSE 6
  END";
if ($selectedCluster) {
    $stmt = $conn->prepare($section2Query);
    $stmt->bind_param("is", $selectedYear, $selectedCluster);
} else {
    $stmt = $conn->prepare($section2Query);
    $stmt->bind_param("i", $selectedYear);
}
$stmt->execute();
$section2Result = $stmt->get_result();

// Group data by category
$section2Data = [];
$currentCategory = '';
while ($row = $section2Result->fetch_assoc()) {
    if ($row['category_name'] != $currentCategory) {
        $currentCategory = $row['category_name'];
        $section2Data[$currentCategory] = [];
    }
    $section2Data[$currentCategory][] = $row;
}

// Calculate Grand Total from Annual Total rows
$grandTotalBudget = 0;
$grandTotalActual = 0;
$grandTotalForecast = 0;
$grandTotalActualForecast = 0;

foreach ($section2Data as $categoryName => $periods) {
    foreach ($periods as $row) {
        if ($row['period_name'] === 'Annual Total') {
            $grandTotalBudget += floatval($row['budget'] ?? 0);
            $grandTotalActual += floatval($row['actual'] ?? 0);
            $grandTotalForecast += floatval($row['forecast'] ?? 0);
            $grandTotalActualForecast += floatval($row['actual_plus_forecast'] ?? 0);
            break; // Only one Annual Total per category
        }
    }
}
$grandTotalVariance = ($grandTotalBudget != 0) ? round((($grandTotalActualForecast - $grandTotalBudget) / abs($grandTotalBudget)) * 100, 2) : 0;

// Calculate Grand Totals from the annual totals of each category
$grandBudget = 0;
$grandActualForecast = 0;

foreach ($section3Categories as $categoryName => $quarters) {
  $annualBudget = 0;
  $annualActualForecast = 0;

  // Sum Q1–Q4 budget
  foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q) {
    if ($quarters[$q]) {
      $annualBudget += floatval($quarters[$q]['budget'] ?? 0);
    }
  }
  // Sum Q1 actual, Q2 actual, Q3 forecast, Q4 forecast
  $annualActualForecast += $quarters['Q1'] ? floatval($quarters['Q1']['actual'] ?? 0) : 0;
  $annualActualForecast += $quarters['Q2'] ? floatval($quarters['Q2']['actual'] ?? 0) : 0;
  $annualActualForecast += $quarters['Q3'] ? floatval($quarters['Q3']['forecast'] ?? 0) : 0;
  $annualActualForecast += $quarters['Q4'] ? floatval($quarters['Q4']['forecast'] ?? 0) : 0;

  $grandBudget += $annualBudget;
  $grandActualForecast += $annualActualForecast;
}
$grandVariance = ($grandBudget != 0) ? round((($grandActualForecast - $grandBudget) / abs($grandBudget)) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Print - Forecast Budget Report <?php echo $selectedYear; ?><?php echo $selectedCluster ? ' - ' . $selectedCluster : ''; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 15px;
      background: white;
      color: black;
    }

    .print-container {
      width: 100%;
      max-width: 100%;
    }

    /* Table Section */
    .table-section {
      margin-bottom: 20px;
      page-break-after: always;
    }

    .table-header {
      text-align: center;
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 15px;
      border-bottom: 2px solid #000;
      padding-bottom: 5px;
    }

    .table-container {
      overflow-x: auto;
      width: 100%;
    }

    .vertical-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8px;
    }

    .vertical-table th,
    .vertical-table td {
      border: 1px solid #000;
      padding: 3px;
      text-align: center;
      font-size: 8px;
    }

    .vertical-table th {
      background-color: #f0f0f0;
      font-weight: bold;
      font-size: 9px;
    }

    /* Left align the Category column */
    .vertical-table td:first-child,
    .vertical-table th:first-child {
      text-align: left;
      font-weight: bold;
      padding-left: 5px;
    }

    /* Annual totals highlighted */
    .vertical-table td:nth-child(10),
    .vertical-table td:nth-child(11),
    .vertical-table td:nth-child(12) {
      background-color: #ffffcc;
      font-weight: bold;
    }

    /* Certification Section */
    .certification-section {
      page-break-before: always;
      padding: 20px;
      width: 100%;
      box-sizing: border-box;
    }

    .certification-header {
      text-align: center;
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 20px;
      border-bottom: 2px solid #000;
      padding-bottom: 8px;
    }

    .certification-form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      width: 100%;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-group label {
      display: block;
      font-weight: bold;
      margin-bottom: 3px;
      font-size: 11px;
    }

    .form-group .form-value {
      border-bottom: 1px solid #000;
      padding: 4px 2px;
      min-height: 18px;
      font-size: 10px;
      width: 100%;
      display: block;
    }

    .form-group textarea.form-value {
      min-height: 40px;
      border: 1px solid #000;
      padding: 5px;
    }

    .signature-box {
      border: 1px solid #000;
      height: 25px;
      width: 100%;
      margin-top: 3px;
    }
    
    /* Variance styling for global financial standards */
    .variance-positive {
      color: #ef4444; /* red for overspent */
      font-weight: 600;
    }
    
    .variance-negative {
      color: #10b981; /* green for underspent */
      font-weight: 600;
    }
    
    .variance-zero {
      color: #64748b; /* gray for zero variance */
      font-weight: 600;
    }
    
    /* Print styles */
    @media print {
      body {
        margin: 0;
        padding: 10mm;
      }
      
      .print-btn {
        display: none;
      }
    }

    .print-btn {
      position: fixed;
      top: 10px;
      right: 10px;
      background: #007bff;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
      z-index: 1000;
    }

    .print-btn:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
  <button class="print-btn" onclick="window.print()">🖨️ Print</button>

  <div class="print-container">
    <!-- Metric Cards Section -->
 
      
     
          
        </div>
      </div>
    </div>

    <!-- Table Section -->
    <div class="table-section">
      <div class="table-header">
        Budget 
      </div>
      
      <div class="table-container">
        <table class="vertical-table">
          <thead>
            <tr>
              <th rowspan="2">Category</th>
              <th colspan="2">Q1</th>
              <th colspan="2">Q2</th>
              <th colspan="2">Q3</th>
              <th colspan="2">Q4</th>
              <th colspan="3">Annual Totals</th>
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
            <?php foreach ($section3Categories as $categoryName => $quarters): ?>
<?php
  // Calculate annual totals from Q1–Q4 for this category
  $annualBudget = 0;
  $annualActualForecast = 0;
  $annualVariance = 0;

  // Sum Q1–Q4 budget
  foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q) {
    if ($quarters[$q]) {
      $annualBudget += floatval($quarters[$q]['budget'] ?? 0);
    }
  }
  // Sum Q1 actual, Q2 actual, Q3 forecast, Q4 forecast
  $annualActualForecast += $quarters['Q1'] ? floatval($quarters['Q1']['actual'] ?? 0) : 0;
  $annualActualForecast += $quarters['Q2'] ? floatval($quarters['Q2']['actual'] ?? 0) : 0;
  $annualActualForecast += $quarters['Q3'] ? floatval($quarters['Q3']['forecast'] ?? 0) : 0;
  $annualActualForecast += $quarters['Q4'] ? floatval($quarters['Q4']['forecast'] ?? 0) : 0;

  // Calculate variance
  if ($annualBudget != 0) {
    $annualVariance = round((($annualActualForecast - $annualBudget) / abs($annualBudget)) * 100, 2);
  }
?>
<tr>
  <td><?php echo htmlspecialchars($categoryName); ?></td>
  <!-- Q1 -->
  <td><?php echo $quarters['Q1'] ? number_format($quarters['Q1']['budget'], 2) : '-'; ?></td>
  <td><?php echo $quarters['Q1'] ? number_format($quarters['Q1']['actual'], 2) : '-'; ?></td>
  <!-- Q2 -->
  <td><?php echo $quarters['Q2'] ? number_format($quarters['Q2']['budget'], 2) : '-'; ?></td>
  <td><?php echo $quarters['Q2'] ? number_format($quarters['Q2']['actual'], 2) : '-'; ?></td>
  <!-- Q3 -->
  <td><?php echo $quarters['Q3'] ? number_format($quarters['Q3']['budget'], 2) : '-'; ?></td>
  <td><?php echo $quarters['Q3'] ? number_format($quarters['Q3']['forecast'], 2) : '-'; ?></td>
  <!-- Q4 -->
  <td><?php echo $quarters['Q4'] ? number_format($quarters['Q4']['budget'], 2) : '-'; ?></td>
  <td><?php echo $quarters['Q4'] ? number_format($quarters['Q4']['forecast'], 2) : '-'; ?></td>
  <!-- Annual Totals (calculated from quarters) -->
  <td><?php echo number_format($annualBudget, 2); ?></td>
  <td><?php echo number_format($annualActualForecast, 2); ?></td>
  <td class="<?php 
    if ($annualVariance > 0) {
      echo 'variance-positive';
    } elseif ($annualVariance < 0) {
      echo 'variance-negative';
    } else {
      echo 'variance-zero';
    }
  ?>"><?php echo $annualVariance; ?>%</td>
</tr>
<?php endforeach; ?>

            <!-- Grand Total Row -->
            <tr style="background:#e0e7ff;font-weight:bold;">
  <td>Grand Total</td>
  <!-- Q1 totals -->
  <td><?php echo number_format($quarterTotals['Q1']['budget'], 2); ?></td>
  <td><?php echo number_format($quarterTotals['Q1']['actual'], 2); ?></td>
  <!-- Q2 totals -->
  <td><?php echo number_format($quarterTotals['Q2']['budget'], 2); ?></td>
  <td><?php echo number_format($quarterTotals['Q2']['actual'], 2); ?></td>
  <!-- Q3 totals -->
  <td><?php echo number_format($quarterTotals['Q3']['budget'], 2); ?></td>
  <td><?php echo number_format($quarterTotals['Q3']['forecast'], 2); ?></td>
  <!-- Q4 totals -->
  <td><?php echo number_format($quarterTotals['Q4']['budget'], 2); ?></td>
  <td><?php echo number_format($quarterTotals['Q4']['forecast'], 2); ?></td>
  <!-- Annual totals (sum of Q1–Q4) -->
  <td><?php echo number_format($grandBudget, 2); ?></td>
  <td><?php echo number_format($grandActualForecast, 2); ?></td>
  <td class="<?php 
    if ($grandVariance > 0) {
      echo 'variance-positive';
    } elseif ($grandVariance < 0) {
      echo 'variance-negative';
    } else {
      echo 'variance-zero';
    }
  ?>"><?php echo $grandVariance; ?>%</td>
</tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Certification Section -->
    <div class="certification-section">
      <div class="certification-header">
        Approve Budget Report
      </div>

      <div class="certification-form">
        <div class="form-group">
          <label for="granteeName">Grantee Name</label>
          <div class="form-value"><?php echo htmlspecialchars($granteeName); ?></div>
        </div>

        <div class="form-group">
          <label for="reportDate">Report Date</label>
          <div class="form-value"><?php echo htmlspecialchars($reportDate); ?></div>
        </div>

        <div class="form-group full-width">
          <label>Certification Statement</label>
          <div class="form-value" style="min-height: 40px; border: 1px solid #000; padding: 5px;">
            <?php echo htmlspecialchars($certificationStatement); ?>
          </div>
        </div>

        <div class="form-group">
          <label>Name</label>
          <div class="form-value"><?php echo htmlspecialchars($name); ?></div>
        </div>

        <div class="form-group">
          <label>Authorized Signature</label>
          <div class="signature-box"></div>
        </div>

        <div class="form-group">
          <label>Date Submitted</label>
          <div class="form-value"><?php echo htmlspecialchars($dateSubmitted); ?></div>
        </div>

        <div class="form-group">
          <label>MMI Technical Program Reviewer</label>
          <div class="form-value"><?php echo htmlspecialchars($reviewer); ?></div>
        </div>

        <div class="form-group">
          <label>Signature</label>
          <div class="signature-box"></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Auto-print when page loads (optional)
    // window.onload = function() {
    //   setTimeout(function() { window.print(); }, 1000);
    // }
  </script>
</body>
</html>
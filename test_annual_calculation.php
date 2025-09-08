<?php
// Test script to debug annual total calculation

// Simulate the data structure as it would come from the database
$testData = [
    [
        'category_name' => '1. Administrative costs',
        'period_name' => 'Q1',
        'budget' => 20000.00,
        'actual' => 3000.00,
        'forecast' => 17000.00,
        'actual_plus_forecast' => 20000.00
    ],
    [
        'category_name' => '1. Administrative costs',
        'period_name' => 'Q2',
        'budget' => 3000.00,
        'actual' => 400.00,
        'forecast' => 500.00,
        'actual_plus_forecast' => 600.00
    ],
    [
        'category_name' => '1. Administrative costs',
        'period_name' => 'Q3',
        'budget' => 40000.00,
        'actual' => 200.00,
        'forecast' => 300.00,
        'actual_plus_forecast' => 500.00
    ],
    [
        'category_name' => '1. Administrative costs',
        'period_name' => 'Q4',
        'budget' => 20000.00,
        'actual' => 300.00,
        'forecast' => 400.00,
        'actual_plus_forecast' => 600.00
    ],
    [
        'category_name' => '1. Administrative costs',
        'period_name' => 'Annual Total',
        'budget' => 99999999.99, // This is the incorrect value from database
        'actual' => 99999999.99, // This is the incorrect value from database
        'forecast' => 99999999.99, // This is the incorrect value from database
        'actual_plus_forecast' => null,
        'variance_percentage' => -100.00
    ]
];

// Group data by category
$section2Data = [];
$currentCategory = '';
foreach ($testData as $row) {
    if ($row['category_name'] != $currentCategory) {
        $currentCategory = $row['category_name'];
        $section2Data[$currentCategory] = [];
    }
    $section2Data[$currentCategory][] = $row;
}

echo "Before calculation:\n";
foreach ($section2Data as $categoryName => $periods) {
    foreach ($periods as $row) {
        if ($row['period_name'] === 'Annual Total') {
            echo "Annual Total - Budget: " . $row['budget'] . ", Actual: " . $row['actual'] . ", Forecast: " . $row['forecast'] . "\n";
        }
    }
}

// Calculate Annual Total by summing Q1-Q4 values
foreach ($section2Data as $categoryName => &$periods) {
    // Initialize category totals
    $categoryTotals = [
        'budget' => 0,
        'actual' => 0,
        'forecast' => 0,
        'actual_plus_forecast' => 0
    ];
    
    // Sum Q1-Q4 values for this category
    foreach ($periods as $row) {
        if (in_array($row['period_name'], ['Q1', 'Q2', 'Q3', 'Q4'])) {
            $categoryTotals['budget'] += floatval($row['budget'] ?? 0);
            $categoryTotals['actual'] += floatval($row['actual'] ?? 0);
            $categoryTotals['forecast'] += floatval($row['forecast'] ?? 0);
            // For Section 2, actual_plus_forecast should be actual + forecast for each quarter
            $categoryTotals['actual_plus_forecast'] += floatval($row['actual'] ?? 0) + floatval($row['forecast'] ?? 0);
        }
    }
    
    echo "\nCalculated totals:\n";
    echo "Budget: " . $categoryTotals['budget'] . "\n";
    echo "Actual: " . $categoryTotals['actual'] . "\n";
    echo "Forecast: " . $categoryTotals['forecast'] . "\n";
    echo "Actual + Forecast: " . $categoryTotals['actual_plus_forecast'] . "\n";
    
    // Replace Annual Total row with calculated values
    foreach ($periods as &$row) {
        if ($row['period_name'] === 'Annual Total') {
            echo "\nUpdating Annual Total row:\n";
            echo "Before - Budget: " . $row['budget'] . ", Actual: " . $row['actual'] . ", Forecast: " . $row['forecast'] . "\n";
            
            $row['budget'] = $categoryTotals['budget'];
            $row['actual'] = $categoryTotals['actual'];
            $row['forecast'] = $categoryTotals['forecast'];
            $row['actual_plus_forecast'] = $categoryTotals['actual_plus_forecast'];
            
            echo "After - Budget: " . $row['budget'] . ", Actual: " . $row['actual'] . ", Forecast: " . $row['forecast'] . ", Actual + Forecast: " . $row['actual_plus_forecast'] . "\n";
            break;
        }
    }
}

echo "\nFinal result:\n";
foreach ($section2Data as $categoryName => $periods) {
    foreach ($periods as $row) {
        if ($row['period_name'] === 'Annual Total') {
            echo "Annual Total - Budget: " . number_format($row['budget'], 2) . ", Actual: " . number_format($row['actual'], 2) . ", Forecast: " . number_format($row['forecast'], 2) . ", Actual + Forecast: " . number_format($row['actual_plus_forecast'], 2) . "\n";
        }
    }
}
?>
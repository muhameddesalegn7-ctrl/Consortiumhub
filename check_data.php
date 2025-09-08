<?php
// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Check if there's data in budget_preview table
echo "Checking budget_preview table...\n";

$sql = "SELECT COUNT(*) as count FROM budget_preview";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo "Total records in budget_preview: " . $row['count'] . "\n";
    
    if ($row['count'] > 0) {
        // Show sample data
        $sql = "SELECT PreviewID, BudgetHeading, Outcome, Activity, Amount, EntryDate FROM budget_preview LIMIT 5";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo "\nSample records:\n";
            echo "PreviewID\tBudgetHeading\tOutcome\tActivity\tAmount\tEntryDate\n";
            while ($row = $result->fetch_assoc()) {
                echo $row['PreviewID'] . "\t" . $row['BudgetHeading'] . "\t" . $row['Outcome'] . "\t" . $row['Activity'] . "\t" . $row['Amount'] . "\t" . $row['EntryDate'] . "\n";
            }
        }
    }
} else {
    echo "Error querying database: " . $conn->error . "\n";
}

// Check the export_transactions_csv functionality
echo "\n\nTesting export_transactions_csv directly...\n";

$_GET['action'] = 'export_transactions_csv';
$_GET['year'] = date('Y');

include 'ajax_handler.php';
?>
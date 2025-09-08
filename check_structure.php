<?php
include 'setup_database.php';

echo "budget_data table structure:\n";
$result = $conn->query('DESCRIBE budget_data');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nclusters table structure:\n";
$result = $conn->query('DESCRIBE clusters');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nusers table structure:\n";
$result = $conn->query('DESCRIBE users');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
<?php
// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Check if certificates_simple table exists
$sql = "SHOW TABLES LIKE 'certificates_simple'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "Table 'certificates_simple' exists.<br>";
    
    // Check table structure
    $descSql = "DESCRIBE certificates_simple";
    $descResult = $conn->query($descSql);
    
    if ($descResult && $descResult->num_rows > 0) {
        echo "Table structure:<br>";
        echo "<pre>";
        while($row = $descResult->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    }
    
    // Check if there's any data
    $countSql = "SELECT COUNT(*) as count FROM certificates_simple";
    $countResult = $conn->query($countSql);
    
    if ($countResult && $countResult->num_rows > 0) {
        $countRow = $countResult->fetch_assoc();
        echo "Total certificates: " . $countRow['count'] . "<br>";
    }
} else {
    echo "Table 'certificates_simple' does not exist.<br>";
    
    // Try to create it
    $createSql = "CREATE TABLE IF NOT EXISTS certificates_simple (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        cluster_name VARCHAR(100) NOT NULL,
        year INT(4) NOT NULL,
        certificate_path VARCHAR(500) NOT NULL,
        uploaded_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        uploaded_by VARCHAR(255) DEFAULT 'admin'
    )";
    
    if ($conn->query($createSql) === TRUE) {
        echo "Table 'certificates_simple' created successfully.<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}
?>
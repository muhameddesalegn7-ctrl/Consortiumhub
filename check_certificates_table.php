<?php
include 'setup_database.php';

$sql = "DESCRIBE certificates_simple";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Table structure for certificates_simple:\n";
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Table certificates_simple does not exist or is empty.";
}
?>
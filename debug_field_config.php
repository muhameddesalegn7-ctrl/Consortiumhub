<?php
session_start();
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Display all predefined fields in the database
echo "<h2>All Predefined Fields in Database</h2>";
$query = "SELECT * FROM predefined_fields ORDER BY field_name, cluster_name";
$result = $conn->query($query);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Field Name</th><th>Field Type</th><th>Field Values</th><th>Is Active</th><th>Cluster Name</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['field_name']}</td>";
    echo "<td>{$row['field_type']}</td>";
    echo "<td>{$row['field_values']}</td>";
    echo "<td>{$row['is_active']}</td>";
    echo "<td>{$row['cluster_name']}</td>";
    echo "</tr>";
}

echo "</table>";

// Test the get_field_config endpoint
echo "<h2>Testing get_field_config Endpoint</h2>";

$fields = ['BudgetHeading', 'Outcome', 'Activity', 'BudgetLine', 'Partner', 'Amount'];
$clusters = ['', 'Woldiya', 'Mekele']; // Empty string for global config

echo "<table border='1'>";
echo "<tr><th>Field Name</th><th>Cluster Name</th><th>Response</th></tr>";

foreach ($fields as $field) {
    foreach ($clusters as $cluster) {
        // Prepare the POST data
        $data = [
            'action' => 'get_field_config',
            'field_name' => $field
        ];
        
        if (!empty($cluster)) {
            $data['cluster_name'] = $cluster;
        }
        
        // Make the request to admin_fields_handler.php
        $ch = curl_init('http://localhost/Consortium%20Hub/admin_fields_handler.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);
        
        echo "<tr>";
        echo "<td>{$field}</td>";
        echo "<td>{$cluster}</td>";
        echo "<td><pre>" . htmlspecialchars($response) . "</pre></td>";
        echo "</tr>";
    }
}

echo "</table>";
?>
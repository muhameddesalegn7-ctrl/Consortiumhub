<?php
// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Test query for BudgetLine field with Woldiya cluster
$field_name = 'BudgetLine';
$cluster_name = 'Woldiya';

echo "Testing query for field: $field_name, cluster: $cluster_name\n";

// First, try to get field config for specific cluster
$query = "SELECT * FROM predefined_fields WHERE field_name = ? AND cluster_name = ? AND is_active = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $field_name, $cluster_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $field = $result->fetch_assoc();
    echo "Found cluster-specific config:\n";
    print_r($field);
    
    // Add values array for easier frontend processing
    if (!empty($field['field_values'])) {
        $field['values_array'] = explode(',', $field['field_values']);
    } else {
        $field['values_array'] = [];
    }
    
    echo "With values_array:\n";
    print_r($field);
} else {
    echo "No cluster-specific config found\n";
}

// Test global config
echo "\nTesting global config for field: $field_name\n";
$query = "SELECT * FROM predefined_fields WHERE field_name = ? AND cluster_name IS NULL AND is_active = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $field_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $field = $result->fetch_assoc();
    echo "Found global config:\n";
    print_r($field);
} else {
    echo "No global config found\n";
}
?>
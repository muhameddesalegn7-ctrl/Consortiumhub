<?php
// Test script to check if get_field_config is working properly

// Simulate a POST request to admin_fields_handler.php
$url = 'http://localhost/Consortium%20Hub/admin_fields_handler.php';
$data = array(
    'action' => 'get_field_config',
    'field_name' => 'BudgetLine',
    'cluster_name' => 'Woldiya'
);

// Use cURL to make the request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

$response = curl_exec($ch);
curl_close($ch);

// Display the response
echo "Response for BudgetLine field:\n";
echo $response;
echo "\n\n";

// Test another field
$data['field_name'] = 'Partner';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

$response = curl_exec($ch);
curl_close($ch);

echo "Response for Partner field:\n";
echo $response;
?>
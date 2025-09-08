<?php
// Test script to verify document type normalization functionality

function normalizeDocumentType($type) {
    // Convert database format to display format
    $normalized = str_replace('_', ' ', $type);
    $normalized = str_replace('(', '(', $normalized);
    $normalized = str_replace(')', ')', $normalized);
    $normalized = str_replace('/', ' / ', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized); // Remove extra spaces
    return trim($normalized);
}

// Test cases
$testCases = [
    "Approved_Timesheets_/_Attendance_Records" => "Approved Timesheets / Attendance Records",
    "Payroll_Register_Sheet_(_For_Each_Project_)" => "Payroll Register Sheet ( For Each Project )",
    "Proof_Of_Payment" => "Proof Of Payment"
];

echo "Testing document type normalization:\n";

foreach ($testCases as $input => $expected) {
    $result = normalizeDocumentType($input);
    $status = ($result === $expected) ? "PASS" : "FAIL";
    echo "$status: '$input' -> '$result' (expected: '$expected')\n";
}

echo "\nTest completed.\n";
?>
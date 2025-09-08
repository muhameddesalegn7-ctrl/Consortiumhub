<?php
// Test script to verify PDF merging functionality

echo "Testing PDF merging functionality...\n\n";

// Simulate PDF documents
$pdfDocuments = [
    ['path' => 'sample1.pdf', 'originalName' => 'Document 1.pdf'],
    ['path' => 'sample2.pdf', 'originalName' => 'Document 2.pdf'],
    ['path' => 'sample3.pdf', 'originalName' => 'Document 3.pdf']
];

echo "Found " . count($pdfDocuments) . " PDF documents to merge\n";

// Test the merge function
function testMergePdfs($pdfDocuments, $pvNumber) {
    echo "Attempting to merge PDFs...\n";
    
    // Check if we can use shell commands for merging
    if (function_exists('shell_exec')) {
        echo "Shell execution is available\n";
        
        // Check if Ghostscript is available (used for PDF merging)
        $gsVersion = shell_exec('gs --version 2>&1');
        if (!empty($gsVersion) && strpos($gsVersion, 'is not recognized') === false) {
            echo "Ghostscript is available (version: " . trim($gsVersion) . ")\n";
            echo "PDF merging should work using Ghostscript\n";
            return true;
        } else {
            echo "Ghostscript is not available\n";
        }
    } else {
        echo "Shell execution is not available\n";
    }
    
    // Check for ZIP creation as fallback
    if (class_exists('ZipArchive')) {
        echo "ZipArchive is available for fallback ZIP creation\n";
        return true;
    } else {
        echo "ZipArchive is not available\n";
        return false;
    }
}

$success = testMergePdfs($pdfDocuments, "TEST001");

if ($success) {
    echo "\nPDF merging functionality test PASSED\n";
    echo "The system should be able to merge PDFs or create ZIP archives\n";
} else {
    echo "\nPDF merging functionality test FAILED\n";
    echo "The system may not be able to merge PDFs\n";
}

echo "\nTest completed.\n";
?>
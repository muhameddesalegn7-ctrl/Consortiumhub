<?php
// Test script to verify FPDI library installation

echo "Testing FPDI/TCPDF library installation...\n\n";

// Check if Composer autoload exists
if (file_exists('vendor/autoload.php')) {
    echo "Composer autoload file found\n";
    
    // Try to load the autoloader
    require_once 'vendor/autoload.php';
    
    // Check if FPDI classes are available
    if (class_exists('setasign\Fpdi\TcpdfFpdi')) {
        echo "SUCCESS: FPDI/TCPDF library is properly installed\n";
        echo "PDF merging functionality should work correctly\n";
    } else {
        echo "ERROR: FPDI classes not found\n";
        echo "Please make sure you installed the correct package:\n";
        echo "composer require setasign/fpdi-tcpdf\n";
    }
} else {
    echo "ERROR: Composer autoload file not found\n";
    echo "Please install Composer dependencies first:\n";
    echo "1. Install Composer from https://getcomposer.org/\n";
    echo "2. Run: composer require setasign/fpdi-tcpdf\n";
}

echo "\nTest completed.\n";
?>
<?php
// Test for PDF merging capabilities

echo "Checking for PDF merging capabilities...\n\n";

// Check for FPDI library
if (class_exists('FPDI')) {
    echo "FPDI library is available\n";
} else {
    echo "FPDI library is NOT available\n";
    echo "You need to install FPDI library for PDF merging\n";
}

// Check for TCPDF library
if (class_exists('TCPDF')) {
    echo "TCPDF library is available\n";
} else {
    echo "TCPDF library is NOT available\n";
    echo "You need to install TCPDF library for PDF creation\n";
}

// Check for setasign/fpdi library (newer version)
if (class_exists('setasign\Fpdi\TcpdfFpdi')) {
    echo "setasign/fpdi library is available\n";
} else {
    echo "setasign/fpdi library is NOT available\n";
}

echo "\nTo install FPDI and TCPDF, you can use Composer:\n";
echo "composer require setasign/fpdi-tcpdf\n";
?>
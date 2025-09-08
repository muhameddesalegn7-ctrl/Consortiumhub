<?php
// Test if ZipArchive is available
if (class_exists('ZipArchive')) {
    echo "ZipArchive is available\n";
    
    // Test creating a simple ZIP file
    $zip = new ZipArchive();
    $filename = sys_get_temp_dir() . "/test.zip";
    
    if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $zip->addFromString('test.txt', 'This is a test file');
        $zip->close();
        echo "Successfully created test ZIP file\n";
        
        // Clean up
        unlink($filename);
    } else {
        echo "Failed to create test ZIP file\n";
    }
} else {
    echo "ZipArchive is not available\n";
    echo "Please enable the php_zip extension in your PHP installation\n";
}
?>
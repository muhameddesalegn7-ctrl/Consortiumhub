# PDF Merging Library Installation Guide

To enable proper PDF merging functionality, you need to install the FPDI/TCPDF library.

## Prerequisites

1. Composer (PHP dependency manager)
   - If you don't have Composer installed, download it from https://getcomposer.org/

## Installation Steps

1. Open a terminal/command prompt
2. Navigate to your project directory:
   ```
   cd d:\Apps\htdocs\Consortium Hub
   ```

3. Run the following command to install the FPDI/TCPDF library:
   ```
   composer require setasign/fpdi-tcpdf
   ```

4. The library will be installed in the `vendor` directory

## Verification

After installation, the PDF merging functionality will work automatically:
- The "Download Merged PDF" button will create a single merged PDF file
- Individual PDFs will be combined into one document
- No more ZIP file downloads

## Troubleshooting

If you encounter any issues:

1. Make sure Composer is properly installed and accessible from your command line
2. Check that you have write permissions in the project directory
3. Verify that PHP is properly configured to run Composer

## Alternative Installation (Manual)

If you prefer to install manually:

1. Download the library from: https://github.com/Setasign/FPDI-TCPDF
2. Extract the files to a `vendor` directory in your project
3. Include the necessary files in your PHP scripts

After installation, the PDF merging functionality will work as expected.
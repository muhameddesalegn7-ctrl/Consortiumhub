<?php
// Handle file uploads when "Complete Document Upload" is pressed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_files') {
    header('Content-Type: application/json');
    
    // Create upload directory if it doesn't exist
    $uploadDir = 'admin/documents/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // Process each uploaded file
    foreach ($_FILES as $fieldName => $fileData) {
        if ($fileData['error'] === UPLOAD_ERR_OK) {
            $originalName = basename($fileData['name']);
            $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
            $allowedExtensions = ['pdf', 'doc', 'docx'];
            
            // Validate file extension
            if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                $errors[] = "Only PDF and Word documents are allowed for {$originalName}";
                continue;
            }
            
            // Validate file size (5MB max)
            if ($fileData['size'] > 5 * 1024 * 1024) {
                $errors[] = "File size must be less than 5MB for {$originalName}";
                continue;
            }
            
            // Generate unique filename
            $newFilename = uniqid() . '_' . time() . '.' . $fileExtension;
            $destination = $uploadDir . $newFilename;
            
            if (move_uploaded_file($fileData['tmp_name'], $destination)) {
                $uploadedFiles[] = [
                    'documentType' => str_replace('_file', '', $fieldName),
                    'serverPath' => $destination,
                    'filename' => $newFilename,
                    'originalName' => $originalName
                ];
            } else {
                $errors[] = "Failed to upload {$originalName}";
            }
        } else if ($fileData['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "Upload error for {$fileData['name']}";
        }
    }
    
    if (empty($errors)) {
        echo json_encode([
            'success' => true, 
            'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
            'uploadedFiles' => $uploadedFiles
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Upload errors: ' . implode(', ', $errors),
            'errors' => $errors
        ]);
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supporting Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        .upload-area {
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .file-item {
            animation: fadeIn 0.5s ease;
        }
        
        .progress-bar {
            transition: width 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .checkmark {
            display: inline-block;
            transform: rotate(45deg);
            height: 20px;
            width: 10px;
            border-bottom: 3px solid #10B981;
            border-right: 3px solid #10B981;
        }
        
        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
            transform: scale(0.9);
        }
        
        .modal.show {
            transform: scale(1);
        }
        
        .drag-over {
            background-color: #f0f9ff;
            border-color: #3b82f6;
            transform: scale(1.02);
        }
        
        .document-preview {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header placeholder -->
    <div style="height: 60px; background: #4F46E5;" class="flex items-center justify-between px-6">
        <div class="text-white font-bold text-xl">Financial Management System</div>
        <div class="text-white"><?php echo date('F j, Y'); ?></div>
    </div>

    <div class="main-content-flex" style="margin-top: 40px; padding-top: 40px;">
        <div class="content-container max-w-5xl mx-auto px-4">
            <div class="bg-white p-8 rounded-xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-3xl font-bold text-gray-800">Supporting Documents</h2>
                    <button id="clearAllButton" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg transition-all duration-300 flex items-center">
                        <i class="fas fa-trash-alt mr-2"></i> Clear All
                    </button>
                </div>
                <p class="text-gray-500 text-center mb-8">Select document category and upload the required documents</p>

                <div class="mb-6">
                    <label for="transactionTypeSelect" class="block text-gray-700 text-sm font-semibold mb-2">
                        Document Category:
                    </label>
                    <select id="transactionTypeSelect"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200 shadow-sm">
                        <option value="">Select Document Category</option>
                        <option value="Withholding Tax (WHT) Payments">1. Withholding Tax (WHT) Payments</option>
                        <option value="Income Tax Payments">2. Income Tax Payments</option>
                        <option value="Pension Contribution Payment">3. Pension Contribution Payment</option>
                        <option value="Payroll Payments">4. Payroll Payments</option>
                        <option value="Telecom Services Payments">5. Telecom Services Payments</option>
                        <option value="Rent Payments">6. Rent Payments</option>
                        <option value="Consultant Payments">7. Consultant Payments</option>
                        <option value="Freight Transportation">8. Freight Transportation</option>
                        <option value="Vehicle Rental">9. Vehicle Rental</option>
                        <option value="Training, Workshop and Related">10. Training, Workshop and Related</option>
                        <option value="Procurement of Services">11. Procurement of Services</option>
                        <option value="Procurement of Goods">12. Procurement of Goods</option>
                    </select>
                </div>

                <div id="pvNumberContainer" class="hidden mb-6 bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-file-invoice text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-800">Reference Number (Required)</h3>
                            <p class="text-blue-600 text-sm">Please provide the Payment Voucher number for this transaction</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <input type="text" id="pvNumberInput" 
                            class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 shadow-sm" 
                            placeholder="e.g. PV-300">
                        <p class="text-red-500 text-sm mt-1 hidden" id="pvError">Reference Number is required for this transaction type</p>
                    </div>
                </div>

                <div id="checklistContainer" class="space-y-4">
                    <!-- Checklist will be generated here -->
                </div>

                <div class="mt-8 flex space-x-4">
                    <button id="saveDraftButton"
                        class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-all duration-300 shadow-md hover:shadow-lg font-medium">
                        <i class="fas fa-save mr-2"></i> Save Draft
                    </button>
                    <button id="uploadAndReturnButton"
                        class="flex-1 bg-gradient-to-r from-green-600 to-green-700 text-white py-3 px-6 rounded-lg hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-300 shadow-md hover:shadow-lg font-medium">
                        <i class="fas fa-check-circle mr-2"></i> Complete Document Upload
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for missing documents -->
    <div id="missingDocsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-11/12 md:w-1/2 max-w-2xl p-6 modal">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Missing Documents</h3>
                    <p class="text-gray-500 text-sm">You haven't uploaded all required documents</p>
                </div>
            </div>
            
            <div class="my-4">
                <p class="text-gray-700">The following documents are required but haven't been uploaded:</p>
                <ul id="missingDocsList" class="list-disc list-inside mt-2 text-gray-700 pl-4">
                    <!-- Missing documents will be listed here -->
                </ul>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button id="cancelUploadButton" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Go Back
                </button>
                <button id="proceedAnywayButton" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Proceed Anyway
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const transactionTypeSelect = document.getElementById('transactionTypeSelect');
            const checklistContainer = document.getElementById('checklistContainer');
            const uploadAndReturnButton = document.getElementById('uploadAndReturnButton');
            const saveDraftButton = document.getElementById('saveDraftButton');
            const pvNumberContainer = document.getElementById('pvNumberContainer');
            const pvNumberInput = document.getElementById('pvNumberInput');
            const pvError = document.getElementById('pvError');
            const missingDocsModal = document.getElementById('missingDocsModal');
            const cancelUploadButton = document.getElementById('cancelUploadButton');
            const proceedAnywayButton = document.getElementById('proceedAnywayButton');
            const missingDocsList = document.getElementById('missingDocsList');
            const clearAllButton = document.getElementById('clearAllButton');
            
            // Auto-clear all data when page loads for the first time
            function autoInitialClear() {
                // Clear form fields
                transactionTypeSelect.value = '';
                pvNumberInput.value = '';
                
                // Clear uploaded documents
                localStorage.removeItem('uploadedDocuments');
                
                // Clear checklist container
                checklistContainer.innerHTML = '';
                
                // Hide PV number container
                pvNumberContainer.classList.add('hidden');
                
                // Reset error states
                pvError.classList.add('hidden');
                pvNumberInput.classList.remove('border-red-500');
            }
            
            // Auto-clear on page load
            autoInitialClear();
            
            let uploadedDocuments = JSON.parse(localStorage.getItem('uploadedDocuments')) || { documents: {} };
            let currentChecklist = [];
            let currentType = '';

            // Initialize with saved PV number if exists (after auto-clear, this will be empty)
            if (uploadedDocuments.pvNumber) {
                pvNumberInput.value = uploadedDocuments.pvNumber;
            }

            const checklists = {
                'Withholding Tax (WHT) Payments': [
                    { id: 'wht_calculation_summary', label: 'WHT Calculation Summary Sheet', required: true },
                    { id: 'wht_payment_slip', label: 'WHT Payment Slip / Receipt from Tax Authority', required: true },
                    { id: 'bank_confirmation_wht', label: 'Bank Confirmation of WHT Payment', required: true }
                ],
                'Income Tax Payments': [
                    { id: 'income_tax_calculation', label: 'Income Tax Calculation Summary Sheet /Payroll', required: true },
                    { id: 'income_tax_payment_slip', label: 'Income Tax Payment Slip / Receipt from Tax Authority', required: true },
                    { id: 'bank_confirmation_income', label: 'Bank Confirmation of Income Tax Payment', required: true }
                ],
                'Pension Contribution Payment': [
                    { id: 'pension_calculation_sheet', label: 'Pension Calculation Sheet', required: true },
                    { id: 'pension_payment_slip', label: 'Pension Payment Slip / Receipt from Tax Authority', required: true },
                    { id: 'bank_confirmation_pension', label: 'Bank Confirmation of Pension Payment', required: true }
                ],
                'Payroll Payments': [
                    { id: 'approved_timesheets', label: 'Approved Timesheets / Attendance Records', required: true },
                    { id: 'payroll_register_project', label: 'Payroll Register Sheet ( For Each Project )', required: true },
                    { id: 'master_payroll_register', label: 'Master Payroll Register Sheet', required: true },
                    { id: 'payslips_pay_stubs', label: 'Payslips / Pay Stubs (for each employee) ( If applicable)', required: false },
                    { id: 'bank_transfer_request', label: 'Bank Transfer Request Letter', required: true },
                    { id: 'proof_of_payment', label: 'Proof Of Payment', required: true },
                    { id: 'payment_voucher_payroll', label: 'Payment Voucher', required: true }
                ],
                'Telecom Services Payments': [
                    { id: 'telecom_service_contract', label: 'Telecom Service Contract / Agreement (if applicable)', required: false },
                    { id: 'monthly_telecom_bill', label: 'Monthly Telecom Bill / Invoice', required: true },
                    { id: 'cost_proration_sheet_telecom', label: 'Cost Pro-ration Sheet', required: true },
                    { id: 'payment_request_form_telecom', label: 'Payment Request Form (Approved (by authorized person) )', required: true },
                    { id: 'bank_transfer_request_telecom', label: 'Bank transfer Request Letter /Cheque copy', required: true },
                    { id: 'proof_of_payment_telecom', label: 'Proof of Payment (Bank transfer confirmation/Cheque copy)', required: true }
                ],
                'Rent Payments': [
                    { id: 'rental_lease_agreement', label: 'Rental / Lease Agreement', required: true },
                    { id: 'landlord_invoice', label: 'Landlord\'s Invoice / Payment Request', required: true },
                    { id: 'payment_request_form_rent', label: 'Payment Request Form (Approved (by authorized person) )', required: true },
                    { id: 'cost_proration_sheet_rent', label: 'Cost Pro-ration Sheet', required: true },
                    { id: 'bank_transfer_request_rent', label: 'Bank transfer Request Letter /Cheque copy', required: true },
                    { id: 'proof_of_payment_rent', label: 'Proof of Payment (Bank transfer Advice /Cheque copy)', required: true },
                    { id: 'withholding_tax_receipt_rent', label: 'Withholding Tax (WHT) Receipt (if applicable)', required: false }
                ],
                'Consultant Payments': [
                    { id: 'consultant_service_contract', label: 'Consultant Service Contract Agreement', required: true },
                    { id: 'scope_of_work', label: 'Scope of Work (SOW) / Terms of Reference (TOR)', required: true },
                    { id: 'consultant_invoice', label: 'Consultant Invoice (if applicable)', required: false },
                    { id: 'consultant_service_report', label: 'Consultant Service accomplishment Activity report / Progress Report', required: true },
                    { id: 'payment_request_form_consultant', label: 'Payment Request Form (Approved)', required: true },
                    { id: 'proof_of_payment_consultant', label: 'Proof of Payment (Bank transfer confirmation/Cheque copy)', required: true },
                    { id: 'withholding_tax_receipt_consultant', label: 'Withholding Tax (WHT) Receipt (if applicable)', required: false },
                    { id: 'payment_voucher_consultant', label: 'Payment Voucher', required: true }
                ],
                'Freight Transportation': [
                    { id: 'purchase_request_freight', label: 'Purchase request', required: true },
                    { id: 'quotation_request_freight', label: 'Quotation request (filled in and sent to suppliers)', required: true },
                    { id: 'quotation_received_freight', label: 'Quotation (received back, signed and stamped)', required: true },
                    { id: 'proforma_sealed_freight', label: 'Attached proforma invoices in a sealed envelope', required: true },
                    { id: 'proformas_formalities_freight', label: 'Proformas with all formalities, including trade license', required: true },
                    { id: 'cba_signed_freight', label: 'Competitive bid analysis (CBA) signed and approved', required: true },
                    { id: 'contract_agreement_freight', label: 'Contract agreement or purchase order', required: true },
                    { id: 'payment_request_freight', label: 'Payment request form', required: true },
                    { id: 'original_waybill', label: 'Original waybill', required: true },
                    { id: 'goods_received_notes_freight', label: 'Goods received notes', required: true },
                    { id: 'cash_receipt_invoice_freight', label: 'Cash receipt invoice (with Organizational TIN)', required: true },
                    { id: 'cheque_copy_freight', label: 'Cheque copy or bank transfer letter from vendor', required: true },
                    { id: 'payment_voucher_freight', label: 'Payment voucher', required: true }
                ],
                'Vehicle Rental': [
                    { id: 'purchase_request_rental', label: 'Purchase request for rental service', required: true },
                    { id: 'quotation_request_rental', label: 'Quotation request (filled in and sent to suppliers)', required: true },
                    { id: 'quotation_received_rental', label: 'Quotation (received back, signed and stamped)', required: true },
                    { id: 'proforma_sealed_rental', label: 'Attached proforma invoices in a sealed envelope', required: true },
                    { id: 'proformas_formalities_rental', label: 'Proformas with all formalities, including trade license', required: true },
                    { id: 'cba_signed_rental', label: 'Competitive bid analysis (CBA) signed and approved', required: true },
                    { id: 'contract_agreement_rental', label: 'Contract agreement or purchase order', required: true },
                    { id: 'payment_request_rental', label: 'Payment request form', required: true },
                    { id: 'summary_payments_rental', label: 'Summary of payments sheet', required: true },
                    { id: 'log_book_rental', label: 'Signed and approved log book sheet', required: true },
                    { id: 'vehicle_inspection_rental', label: 'Vehicle goods-outward inspection certificate', required: true },
                    { id: 'withholding_receipt_rental', label: 'Withholding receipt (for amounts over ETB 10,000)', required: false },
                    { id: 'cash_receipt_rental', label: 'Cash receipt invoice (with Organizational TIN)', required: true },
                    { id: 'cheque_copy_rental', label: 'Cheque copy or bank transfer letter from vendor', required: true },
                    { id: 'payment_voucher_rental', label: 'Payment voucher', required: true }
                ],
                'Training, Workshop and Related': [
                    { id: 'training_approved', label: 'Training approved by Program Manager', required: true },
                    { id: 'invitation_letters', label: 'Participant invitation letters from government parties', required: true },
                    { id: 'attendance_sheet', label: 'Fully completed attendance sheet', required: true },
                    { id: 'manager_signature', label: 'Manager\'s signature', required: true },
                    { id: 'payment_rate', label: 'Approved payment rate (or justified reason for a different rate)', required: true },
                    { id: 'fuel_letter', label: 'Letter from government for fuel (if applicable)', required: false },
                    { id: 'activity_report', label: 'Activity (training) report', required: true },
                    { id: 'cash_receipt_refund', label: 'Cash receipt or bank advice (if refund applicable)', required: false },
                    { id: 'expense_settlement', label: 'Expense settlement sheet with all information', required: true },
                    { id: 'required_signatures', label: 'All required signatures on templates', required: true },
                    { id: 'stamped_paid', label: 'All documents stamped "paid"', required: true },
                    { id: 'original_documents', label: 'All documents are original (or cross-referenced if not)', required: true },
                    { id: 'tin_company_name', label: 'TIN and company name on receipt', required: true },
                    { id: 'check_dates', label: 'Check dates and all information on receipts', required: true }
                ],
                'Procurement of Services': [
                    { id: 'purchase_requisition_service', label: 'Purchase requisition', required: true },
                    { id: 'quotation_request_service', label: 'Quotation request (filled in and sent to suppliers)', required: true },
                    { id: 'quotation_received_service', label: 'Quotation (received back, signed and stamped)', required: true },
                    { id: 'proforma_sealed_service', label: 'Attached proforma invoices in a sealed envelope', required: true },
                    { id: 'proformas_formalities_service', label: 'Proformas with all formalities, including trade license', required: true },
                    { id: 'cba_signed_service', label: 'Competitive bid analysis (CBA) signed and approved', required: true },
                    { id: 'contract_agreement_service', label: 'Contract agreement or purchase order', required: true },
                    { id: 'payment_request_service', label: 'Payment request form', required: true },
                    { id: 'withholding_receipt_service', label: 'Withholding receipt (for amounts over ETB 10,000)', required: false },
                    { id: 'cash_receipt_service', label: 'Cash receipt invoice (with Organizational TIN)', required: true },
                    { id: 'service_accomplishment', label: 'Service accomplishment report', required: true },
                    { id: 'cheque_copy_service', label: 'Cheque copy or bank transfer letter from vendor', required: true },
                    { id: 'payment_voucher_service', label: 'Payment voucher', required: true }
                ],
                'Procurement of Goods': [
                    { id: 'purchase_request_goods', label: 'Purchase request', required: true },
                    { id: 'quotation_request_goods', label: 'Quotation request (filled in and sent to suppliers)', required: true },
                    { id: 'quotation_received_goods', label: 'Quotation (received back, signed and stamped)', required: true },
                    { id: 'proforma_sealed_goods', label: 'Attached proforma invoices in a sealed envelope', required: true },
                    { id: 'proformas_formalities_goods', label: 'Proformas with all formalities, including trade license', required: true },
                    { id: 'cba_signed_goods', label: 'Competitive bid analysis (CBA) signed and approved', required: true },
                    { id: 'purchase_order_goods', label: 'Purchase order', required: true },
                    { id: 'contract_agreement_goods', label: 'Contract agreement or Framework Agreement', required: true },
                    { id: 'payment_request_goods', label: 'Payment request form', required: true },
                    { id: 'withholding_receipt_goods', label: 'Withholding receipt (for amounts over ETB 20,000)', required: false },
                    { id: 'cash_receipt_goods', label: 'Cash receipt invoice (with Organizational TIN)', required: true },
                    { id: 'goods_received_note', label: 'Goods received note (GRN) or delivery note', required: true },
                    { id: 'cheque_copy_goods', label: 'Cheque copy or bank transfer letter from vendor', required: true },
                    { id: 'payment_voucher_goods', label: 'Payment voucher', required: true }
                ]
            };
            
            // Transaction types that require PV number - all 12 categories require PV number
            const pvRequiredTypes = ['Withholding Tax (WHT) Payments', 'Income Tax Payments', 'Pension Contribution Payment', 'Payroll Payments', 'Telecom Services Payments', 'Rent Payments', 'Consultant Payments', 'Freight Transportation', 'Vehicle Rental', 'Training, Workshop and Related', 'Procurement of Services', 'Procurement of Goods'];
            
            transactionTypeSelect.addEventListener('change', function() {
                const selectedType = this.value;
                currentType = selectedType;
                checklistContainer.innerHTML = '';
                
                // Show/hide PV number field based on transaction type
                if (pvRequiredTypes.includes(selectedType)) {
                    pvNumberContainer.classList.remove('hidden');
                } else {
                    pvNumberContainer.classList.add('hidden');
                }

                if (selectedType) {
                    currentChecklist = checklists[selectedType] || [];
                    
                    if (currentChecklist.length > 0) {
                        currentChecklist.forEach(item => {
                            const isUploaded = (uploadedDocuments.documents && uploadedDocuments.documents[item.label]) || 
                                             (uploadedDocuments.documentNames && uploadedDocuments.documentNames[item.label]);
                            
                            const checklistItem = document.createElement('div');
                            checklistItem.classList.add('upload-area', 'flex', 'flex-col', 'items-start', 'bg-gray-50', 'p-4', 'rounded-lg', 'shadow-sm', 'border', 'border-gray-200');
                            
                            checklistItem.innerHTML = `
                                <div class="flex items-center justify-between w-full mb-3">
                                    <div class="flex items-center">
                                        <span class="bg-${isUploaded ? 'green' : 'blue'}-100 text-${isUploaded ? 'green' : 'blue'}-800 text-xs font-medium px-2.5 py-0.5 rounded-full">${item.required ? 'Required' : 'Optional'}</span>
                                    </div>
                                    ${isUploaded ? `
                                        <span class="text-green-600 text-sm font-medium flex items-center">
                                            <span class="checkmark mr-1"></span> Uploaded
                                        </span>
                                    ` : ''}
                                </div>
                                
                                <div class="flex items-center mb-2 w-full">
                                    <span class="text-gray-700 text-sm font-medium">${item.label}</span>
                                </div>
                                
                                ${!isUploaded ? `
                                    <div class="w-full mt-2">
                                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition upload-container">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                                                <p class="text-sm text-gray-500 mb-1">Click to upload or drag & drop</p>
                                                <p class="text-xs text-gray-400">PDF, DOC, DOCX (Max 5MB)</p>
                                            </div>
                                            <input type="file" id="${item.id}_upload" class="hidden file-input" data-label="${item.label}" accept=".pdf,.doc,.docx" />
                                        </label>
                                        <div class="text-center mt-2 text-xs text-gray-500 drag-drop-text">Drag & drop your files here</div>
                                    </div>
                                ` : `
                                    <div class="w-full mt-2">
                                        <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-green-200">
                                            <div class="flex items-center">
                                                <i class="far fa-file-pdf text-red-500 text-xl mr-3"></i>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-700">${item.label.substring(0, 20)}...</p>
                                                    <p class="text-xs text-gray-500">Uploaded: ${new Date().toLocaleDateString()}</p>
                                                </div>
                                            </div>
                                            <div class="flex space-x-2">
                                                <button class="text-blue-500 hover:text-blue-700 view-document" data-label="${item.label}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="text-red-500 hover:text-red-700 remove-document" data-label="${item.label}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `}
                            `;
                            
                            checklistContainer.appendChild(checklistItem);
                            
                            // Add event listeners for file uploads
                            if (!isUploaded) {
                                const fileInput = checklistItem.querySelector(`#${item.id}_upload`);
                                const uploadContainer = checklistItem.querySelector('.upload-container');
                                
                                if (fileInput) {
                                    // Click to upload
                                    fileInput.addEventListener('change', function(event) {
                                        handleFileUpload(event, item);
                                    });
                                    
                                    // Drag and drop functionality
                                    setupDragAndDrop(uploadContainer, fileInput, item);
                                }
                            }
                        });
                        
                        // Add event listeners for view and remove buttons
                        document.querySelectorAll('.view-document').forEach(button => {
                            button.addEventListener('click', function() {
                                const label = this.getAttribute('data-label');
                                if (uploadedDocuments.documents && uploadedDocuments.documents[label]) {
                                    window.open(uploadedDocuments.documents[label], '_blank');
                                }
                            });
                        });
                        
                        document.querySelectorAll('.remove-document').forEach(button => {
                            button.addEventListener('click', function() {
                                const label = this.getAttribute('data-label');
                                if (uploadedDocuments.documents && uploadedDocuments.documents[label]) {
                                    delete uploadedDocuments.documents[label];
                                }
                                if (uploadedDocuments.documentNames && uploadedDocuments.documentNames[label]) {
                                    delete uploadedDocuments.documentNames[label];
                                }
                                if (uploadedDocuments.tempFiles && uploadedDocuments.tempFiles[label]) {
                                    delete uploadedDocuments.tempFiles[label];
                                }
                                localStorage.setItem('uploadedDocuments', JSON.stringify({
                                    pvNumber: uploadedDocuments.pvNumber || '',
                                    documents: {},
                                    tempFiles: {},
                                    documentNames: uploadedDocuments.documentNames || {}
                                }));
                                transactionTypeSelect.dispatchEvent(new Event('change'));
                            });
                        });
                    } else {
                        checklistContainer.innerHTML = `
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-info-circle text-3xl mb-3"></i>
                                <p>No checklist available for this transaction type.</p>
                            </div>
                        `;
                    }
                }
            });
            
            function setupDragAndDrop(container, fileInput, item) {
                // Prevent default drag behaviors
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    container.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });
                
                // Highlight drop area when item is dragged over it
                ['dragenter', 'dragover'].forEach(eventName => {
                    container.addEventListener(eventName, highlight, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    container.addEventListener(eventName, unhighlight, false);
                });
                
                // Handle dropped files
                container.addEventListener('drop', handleDrop, false);
                
                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                function highlight() {
                    container.classList.add('drag-over');
                }
                
                function unhighlight() {
                    container.classList.remove('drag-over');
                }
                
                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    
                    if (files.length > 0) {
                        // Check if file is PDF or DOC
                        const file = files[0];
                        const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                        
                        if (!validTypes.includes(file.type)) {
                            showNotification('Please upload only PDF or Word documents', 'red');
                            return;
                        }
                        
                        // Check file size (5MB max)
                        if (file.size > 5 * 1024 * 1024) {
                            showNotification('File size must be less than 5MB', 'red');
                            return;
                        }
                        
                        // Create a new event to simulate file input
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        fileInput.files = dataTransfer.files;
                        
                        // Trigger the file upload
                        const event = new Event('change', { bubbles: true });
                        fileInput.dispatchEvent(event);
                    }
                }
            }
            
            function handleFileUpload(event, item) {
                const file = event.target.files[0];
                if (file) {
                    // Validate file type
                    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    if (!validTypes.includes(file.type)) {
                        showNotification('Please upload only PDF or Word documents', 'red');
                        return;
                    }
                    
                    // Validate file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        showNotification('File size must be less than 5MB', 'red');
                        return;
                    }
                    
                    // Show processing UI
                    const checklistItem = event.target.closest('.upload-area');
                    const uploadArea = checklistItem.querySelector('.upload-container');
                    
                    uploadArea.innerHTML = `
                        <div class="w-full px-4">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">Processing...</span>
                                <span class="text-sm font-medium text-gray-700 progress-percent">100%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-green-600 h-2.5 rounded-full progress-bar" style="width: 100%"></div>
                            </div>
                        </div>
                    `;
                    
                    // Store file temporarily in browser memory
                    setTimeout(() => {
                        // Save file info to localStorage (temporarily)
                        if (!uploadedDocuments.documents) {
                            uploadedDocuments.documents = {};
                        }
                        if (!uploadedDocuments.tempFiles) {
                            uploadedDocuments.tempFiles = {};
                        }
                        if (!uploadedDocuments.documentNames) {
                            uploadedDocuments.documentNames = {};
                        }
                        
                        // Store the file object for later upload and create preview URL
                        uploadedDocuments.documents[item.label] = URL.createObjectURL(file);
                        uploadedDocuments.tempFiles[item.label] = {
                            file: file,
                            originalName: file.name,
                            size: file.size,
                            type: file.type
                        };
                        // Store document name persistently for preview
                        uploadedDocuments.documentNames[item.label] = file.name;
                        
                        // Save to localStorage (without the actual file objects)
                        const storageData = {
                            pvNumber: uploadedDocuments.pvNumber || '',
                            documents: {}, // Don't store blob URLs in localStorage
                            tempFiles: {}, // Don't store actual files in localStorage
                            documentNames: uploadedDocuments.documentNames // Keep document names
                        };
                        localStorage.setItem('uploadedDocuments', JSON.stringify(storageData));
                        
                        // Show success and refresh checklist
                        showNotification('Document selected successfully (will be uploaded when transaction is saved)', 'green');
                        transactionTypeSelect.dispatchEvent(new Event('change'));
                    }, 500);
                }
            }
            
            // Clear all button functionality
            clearAllButton.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear all entered data? This action cannot be undone.')) {
                    // Clear form fields
                    transactionTypeSelect.value = '';
                    pvNumberInput.value = '';
                    
                    // Clear uploaded documents
                    uploadedDocuments = { documents: {} };
                    localStorage.removeItem('uploadedDocuments');
                    
                    // Clear checklist container
                    checklistContainer.innerHTML = '';
                    
                    // Hide PV number container
                    pvNumberContainer.classList.add('hidden');
                    
                    showNotification('All data has been cleared', 'green');
                }
            });
            
            uploadAndReturnButton.addEventListener('click', function() {
                // Validate PV number for required transaction types
                if (pvRequiredTypes.includes(currentType) && !pvNumberInput.value.trim()) {
                    pvError.classList.remove('hidden');
                    pvNumberInput.classList.add('border-red-500');
                    pvNumberInput.focus();
                    return;
                } else {
                    pvError.classList.add('hidden');
                    pvNumberInput.classList.remove('border-red-500');
                    
                    // Save PV number
                    uploadedDocuments.pvNumber = pvNumberInput.value.trim();
                }
                
                // Check for missing required documents
                const missingDocuments = [];
                if (currentChecklist) {
                    currentChecklist.forEach(item => {
                        const hasDocument = (uploadedDocuments.documents && uploadedDocuments.documents[item.label]) ||
                                           (uploadedDocuments.documentNames && uploadedDocuments.documentNames[item.label]);
                        if (item.required && !hasDocument) {
                            missingDocuments.push(item.label);
                        }
                    });
                }
                
                if (missingDocuments.length > 0) {
                    // Show modal with missing documents
                    missingDocsList.innerHTML = '';
                    missingDocuments.forEach(doc => {
                        const li = document.createElement('li');
                        li.textContent = doc;
                        missingDocsList.appendChild(li);
                    });
                    
                    missingDocsModal.classList.remove('hidden');
                    setTimeout(() => {
                        missingDocsModal.querySelector('.modal').classList.add('show');
                    }, 10);
                } else {
                    // Upload files to server and then proceed
                    uploadFilesToServer();
                }
            });
            
            function uploadFilesToServer() {
                // Check if there are files to upload
                if (!uploadedDocuments.tempFiles || Object.keys(uploadedDocuments.tempFiles).length === 0) {
                    // No files to upload, just save data and proceed
                    saveDocumentDataAndProceed();
                    return;
                }
                
                // Show loading state
                uploadAndReturnButton.disabled = true;
                uploadAndReturnButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading Files...';
                
                // Create FormData for file upload
                const formData = new FormData();
                formData.append('action', 'upload_files');
                
                // Add all temporary files to FormData
                for (const docType in uploadedDocuments.tempFiles) {
                    const tempFile = uploadedDocuments.tempFiles[docType];
                    if (tempFile && tempFile.file) {
                        formData.append(docType.replace(/ /g, '_') + '_file', tempFile.file);
                    }
                }
                
                // Upload files via AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Files uploaded successfully, save file paths
                        uploadedDocuments.uploadedFiles = data.uploadedFiles;
                        uploadedDocuments.tempFiles = {}; // Clear temporary files
                        
                        showNotification(data.message, 'green');
                        saveDocumentDataAndProceed();
                    } else {
                        showNotification(data.message, 'red');
                        resetUploadButton();
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    showNotification('Upload failed. Please try again.', 'red');
                    resetUploadButton();
                });
            }
            
            function saveDocumentDataAndProceed() {
                // Save all document data to localStorage
                const storageData = {
                    pvNumber: uploadedDocuments.pvNumber || '',
                    documents: {}, // Don't store blob URLs
                    tempFiles: {}, // Clear temporary files
                    documentNames: uploadedDocuments.documentNames || {},
                    uploadedFiles: uploadedDocuments.uploadedFiles || []
                };
                localStorage.setItem('uploadedDocuments', JSON.stringify(storageData));
                
                // Reset button and proceed
                resetUploadButton();
                window.location.href = 'financial_report_section.php';
            }
            
            function resetUploadButton() {
                uploadAndReturnButton.disabled = false;
                uploadAndReturnButton.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Complete Document Upload';
            }
            
            saveDraftButton.addEventListener('click', function() {
                // Save PV number
                uploadedDocuments.pvNumber = pvNumberInput.value.trim();
                localStorage.setItem('uploadedDocuments', JSON.stringify(uploadedDocuments));
                
                // Show confirmation message
                showNotification('Draft saved successfully!', 'green');
            });
            
            cancelUploadButton.addEventListener('click', function() {
                missingDocsModal.classList.add('hidden');
                missingDocsModal.querySelector('.modal').classList.remove('show');
            });
            
            proceedAnywayButton.addEventListener('click', function() {
                missingDocsModal.classList.add('hidden');
                missingDocsModal.querySelector('.modal').classList.remove('show');
                
                // Upload files even if some are missing
                uploadFilesToServer();
            });
            
            // Initialize if there's a saved transaction type
            if (uploadedDocuments.transactionType) {
                transactionTypeSelect.value = uploadedDocuments.transactionType;
                transactionTypeSelect.dispatchEvent(new Event('change'));
            }
            
            // Helper function to show notifications
            function showNotification(message, color) {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white font-medium transform transition-transform duration-300 translate-x-full`;
                notification.style.backgroundColor = color === 'green' ? '#10B981' : '#EF4444';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas ${color === 'green' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.classList.remove('translate-x-full');
                    notification.classList.add('translate-x-0');
                }, 10);
                
                // Animate out after 3 seconds
                setTimeout(() => {
                    notification.classList.remove('translate-x-0');
                    notification.classList.add('translate-x-full');
                    
                    // Remove from DOM after animation
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
            }
        });
    </script>
</body>
</html>
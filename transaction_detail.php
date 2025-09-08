<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Get user role and cluster
$userRole = $_SESSION['role'] ?? '';
$userCluster = $_SESSION['cluster_name'] ?? null;

// Get transaction ID from URL parameter
$transactionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate transaction ID
if (empty($transactionId)) {
    header("Location: history.php");
    exit();
}

// Build WHERE clause for cluster filtering
$whereConditions = ["bp.PreviewID = ?"];
$params = [$transactionId];
$paramTypes = "i";

// Add cluster filter for non-admin users
if ($userRole !== 'admin' && $userCluster) {
    $whereConditions[] = "bp.cluster = ?";
    $params[] = $userCluster;
    $paramTypes .= "s";
}

$whereClause = implode(" AND ", $whereConditions);

// Fetch transaction details with documents
$transactionQuery = "SELECT 
    bp.PreviewID,
    bp.BudgetHeading,
    bp.Description as Activity,
    bp.Partner,
    bp.EntryDate,
    bp.Amount,
    bp.PVNumber,
    bp.QuarterPeriod,
    bp.CategoryName,
    bp.OriginalBudget,
    bp.RemainingBudget,
    bp.ActualSpent,
    bp.ForecastAmount,
    bp.VariancePercentage,
    YEAR(bp.EntryDate) as TransactionYear,
    bp.DocumentPaths,
    bp.DocumentTypes,
    bp.OriginalNames,
    bp.cluster,
    bp.ACCEPTANCE,
    bp.COMMENTS
FROM budget_preview bp 
WHERE " . $whereClause . "
ORDER BY bp.EntryDate DESC, bp.PreviewID DESC";

$stmt = $conn->prepare($transactionQuery);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$transactionsResult = $stmt->get_result();
$transactions = [];
while ($row = $transactionsResult->fetch_assoc()) {
    $transactions[] = $row;
}

// If no transactions found, redirect to history
if (empty($transactions)) {
    header("Location: history.php");
    exit();
}

// Get the first transaction to extract common data
$firstTransaction = $transactions[0];
$pvNumber = $firstTransaction['PVNumber'] ?? 'N/A';

// Process documents for display
$allDocuments = [];
$documentChecklist = [];

foreach ($transactions as $transaction) {
    // Process document paths, types and original names
    $documentPaths = !empty($transaction['DocumentPaths']) ? explode(',', $transaction['DocumentPaths']) : [];
    $documentTypes = !empty($transaction['DocumentTypes']) ? explode(',', $transaction['DocumentTypes']) : [];
    $originalNames = !empty($transaction['OriginalNames']) ? explode(',', $transaction['OriginalNames']) : [];
    
    // Combine documents from all transactions with this PV number
    for ($i = 0; $i < count($documentPaths); $i++) {
        if (!empty($documentPaths[$i])) {
            $doc = [
                'path' => $documentPaths[$i],
                'type' => isset($documentTypes[$i]) ? $documentTypes[$i] : 'Unknown',
                'originalName' => isset($originalNames[$i]) ? $originalNames[$i] : 'Unknown',
                'transaction' => $transaction
            ];
            $allDocuments[] = $doc;
            
            // Add to checklist using normalized type as key
            $normalizedType = normalizeDocumentType($doc['type']);
            if (!isset($documentChecklist[$normalizedType])) {
                $documentChecklist[$normalizedType] = [];
            }
            $documentChecklist[$normalizedType][] = $doc;
        }
    }
}

// Function to normalize document types for matching
function normalizeDocumentType($type) {
    // Convert database format to display format
    $normalized = str_replace('_', ' ', $type);
    $normalized = str_replace('(', '(', $normalized);
    $normalized = str_replace(')', ')', $normalized);
    $normalized = str_replace('/', ' / ', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized); // Remove extra spaces
    return trim($normalized);
}

// Define the complete document checklist based on the provided requirements
$completeChecklist = [
    "1 Withholding Tax (WHT) Payments" => [
        "WHT Calculation Summary Sheet",
        "WHT Payment Slip / Receipt from Tax Authority",
        "Bank Confirmation of WHT Payment"
    ],
    "2 Income Tax Payments" => [
        "Income Tax Calculation Summary Sheet /Payroll",
        "Income Tax Payment Slip / Receipt from Tax Authority",
        "Bank Confirmation of Income Tax Payment"
    ],
    "3 Pension Contribution Payment" => [
        "Pension Calculation Sheet",
        "Pension Payment Slip / Receipt from Tax Authority",
        "Bank Confirmation of Pension Payment"
    ],
    "4 Payroll Payments" => [
        "Approved Timesheets / Attendance Records",
        "Payroll Register Sheet ( For Each Project )",
        "Master Payroll Register Sheet",
        "Payslips / Pay Stubs (for each employee) ( If applicable)",
        "Bank Transfer Request Letter",
        "Proof Of Payment",
        "Payment Voucher"
    ],
    "5 Telecom Services Payments" => [
        "Telecom Service Contract / Agreement (if applicable)",
        "Monthly Telecom Bill / Invoice",
        "Cost Pro-ration Sheet",
        "Payment Request Form (Approved (by authorized person) )",
        "Bank transfer Request Letter /Cheque copy",
        "Proof of Payment (Bank transfer confirmation/Cheque copy)"
    ],
    "6 Rent Payments" => [
        "Rental / Lease Agreement",
        "Landlord's Invoice / Payment Request",
        "Payment Request Form (Approved (by authorized person) )",
        "Cost Pro-ration Sheet",
        "Bank transfer Request Letter /Cheque copy",
        "Proof of Payment (Bank transfer Advice /Cheque copy)",
        "Withholding Tax (WHT) Receipt (if applicable)"
    ],
    "7 Consultant Payments" => [
        "Consultant Service Contract Agreement",
        "Scope of Work (SOW) / Terms of Reference (TOR)",
        "Consultant Invoice (if applicable)",
        "Consultant Service accomplishment Activity report / Progress Report",
        "Payment Request Form (Approved)",
        "Proof of Payment (Bank transfer confirmation/Cheque copy)",
        "Withholding Tax (WHT) Receipt (if applicable)",
        "Paymnet Voucher"
    ],
    "8 Freight Transportation" => [
        "Purchase request",
        "Quotation request (filled in and sent to suppliers)",
        "Quotation (received back, signed and stamped)",
        "Attached proforma invoices in a sealed envelope",
        "Proformas with all formalities, including trade license",
        "Competitive bid analysis (CBA) signed and approved",
        "Contract agreement or purchase order",
        "Payment request form",
        "Original waybill",
        "Goods received notes",
        "Cash receipt invoice (with Organizational TIN)",
        "Cheque copy or bank transfer letter from vendor",
        "Payment voucher"
    ],
    "9 Vehicle Rental" => [
        "Purchase request for rental service",
        "Quotation request (filled in and sent to suppliers)",
        "Quotation (received back, signed and stamped)",
        "Attached proforma invoices in a sealed envelope",
        "Proformas with all formalities, including trade license",
        "Competitive bid analysis (CBA) signed and approved",
        "Contract agreement or purchase order",
        "Payment request form",
        "Summary of payments sheet",
        "Signed and approved log book sheet",
        "Vehicle goods-outward inspection certificate",
        "Withholding receipt (for amounts over ETB 10,000)",
        "Cash receipt invoice (with Organizational TIN)",
        "Cheque copy or bank transfer letter from vendor",
        "Payment voucher"
    ],
    "10 Training, Workshop and Related" => [
        "Training approved by Program Manager",
        "Participant invitation letters from government parties",
        "Fully completed attendance sheet",
        "Manager's signature",
        "Approved payment rate (or justified reason for a different rate)",
        "Letter from government for fuel (if applicable)",
        "Activity (training) report",
        "Cash receipt or bank advice (if refund applicable)",
        "Expense settlement sheet with all information",
        "All required signatures on templates",
        "All documents stamped \"paid\"",
        "All documents are original (or cross-referenced if not)",
        "TIN and company name on receipt",
        "Check dates and all information on receipts"
    ],
    "11 Procurement of Services" => [
        "Purchase requisition",
        "Quotation request (filled in and sent to suppliers)",
        "Quotation (received back, signed and stamped)",
        "Attached proforma invoices in a sealed envelope",
        "Proformas with all formalities, including trade license",
        "Competitive bid analysis (CBA) signed and approved",
        "Contract agreement or purchase order",
        "Payment request form",
        "Withholding receipt (for amounts over ETB 10,000)",
        "Cash receipt invoice (with Organizational TIN)",
        "Service accomplishment report",
        "Cheque copy or bank transfer letter from vendor",
        "Payment voucher"
    ],
    "12 Procurement of Goods" => [
        "Purchase request",
        "Quotation request (filled in and sent to suppliers)",
        "Quotation (received back, signed and stamped)",
        "Attached proforma invoices in a sealed envelope",
        "Proformas with all formalities, including trade license",
        "Competitive bid analysis (CBA) signed and approved",
        "purchase order",
        "Contract agreement or Framework Agreement",
        "Payment request form",
        "Withholding receipt (for amounts over ETB 20,000)",
        "Cash receipt invoice (with Organizational TIN)",
        "Goods received note (GRN) or delivery note",
        "Cheque copy or bank transfer letter from vendor",
        "Payment voucher"
    ]
];

// Get unique document types for checklist
$uniqueDocumentTypes = array_keys($documentChecklist);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details - <?php echo htmlspecialchars($pvNumber); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            background-color: #f0f4f8;
        }
        .main-content-flex {
            display: flex;
            justify-content: center;
            padding: 2rem;
        }
        .content-container {
            width: 100%;
            max-width: 1400px;
        }
        .animate-fadeIn {
            animation: fadeIn 0.8s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card-hover {
            transition: transform 0.3s ease-out, box-shadow 0.3s ease-out;
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        }
        .btn-shadow {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .financial-table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .table-header {
            background: linear-gradient(to right, #1d4ed8, #2563eb);
        }
        .table-header th {
            color: #ffffff;
            font-weight: 600;
            text-align: left;
            padding: 1rem 1.5rem;
        }
        .table-body tr:nth-child(odd) {
            background-color: #f9fafb;
        }
        .table-body tr:nth-child(even) {
            background-color: #ffffff;
        }
        .table-body tr:hover {
            background-color: #f1f5f9;
        }
        .table-body td {
            padding: 1rem 1.5rem;
            color: #4b5563;
            font-size: 0.875rem;
        }
        .variance-positive {
            color: #ef4444; /* red for overspent */
            font-weight: 600;
        }
        .variance-negative {
            color: #10b981; /* green for underspent */
            font-weight: 600;
        }
        .variance-neutral {
            color: #64748b; /* gray for zero variance */
            font-weight: 600;
        }
        .document-card {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
        }
        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: #93c5fd;
        }
        .document-icon {
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
        }
        .pdf-bg {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .doc-bg {
            background-color: #dbeafe;
            color: #2563eb;
        }
        .unknown-bg {
            background-color: #f3e8ff;
            color: #9333ea;
        }
        .checklist-item {
            border-left: 4px solid #e2e8f0;
        }
        .checklist-item.found {
            border-left-color: #10b981;
        }
        .checklist-item.missing {
            border-left-color: #ef4444;
        }
        /* Tab styling */
        .tab-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .tab-container::-webkit-scrollbar {
            display: none;
        }
        .tab-button {
            color: #6b7280;
            border-bottom-color: transparent;
            white-space: nowrap;
        }
        .tab-button.active-tab {
            color: #2563eb;
            border-bottom-color: #2563eb;
            font-weight: 600;
        }
        .tab-button:hover:not(.active-tab) {
            color: #3b82f6;
            border-bottom-color: #93c5fd;
        }
        /* Acceptance buttons */
        .btn-accept {
            background: #94a3b8;
            color: white;
            border: none;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .btn-accept.accepted {
            background: #10b981;
        }
        
        .btn-accept:hover {
            background: #64748b;
        }
        
        .btn-reject {
            background: #94a3b8;
            color: white;
            border: none;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .btn-reject.not-accepted {
            background: #dc2626;
        }
        
        .btn-reject:hover {
            background: #64748b;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            margin: 0;
            padding: 25px;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalFadeIn 0.3s ease-out;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: #64748b;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        /* Scrollable table container */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="main-content-flex">
        <div class="content-container">
            <div id="transactionDetailSection" class="bg-white p-8 rounded-3xl shadow-2xl w-full mx-auto card-hover animate-fadeIn">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-3xl font-extrabold text-gray-800">Transaction Details</h3>
                        <p class="text-gray-600 mt-2">Reference Number: <span class="font-semibold"><?php echo htmlspecialchars($pvNumber); ?></span></p>
                    </div>
                    <a href="history.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-6 rounded-lg transition-all duration-300 flex items-center btn-shadow">
                        <i class="fas fa-arrow-left mr-2"></i> Back to History
                    </a>
                </div>

                <!-- Acceptance Section for Admin -->
                <?php if ($userRole === 'admin'): ?>
                    <?php 
                    $accepted = isset($firstTransaction['ACCEPTANCE']) ? $firstTransaction['ACCEPTANCE'] : 0;
                    $comment = isset($firstTransaction['COMMENTS']) ? $firstTransaction['COMMENTS'] : '';
                    ?>
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h4 class="text-lg font-bold text-gray-800 mb-3">Transaction Acceptance</h4>
                        <div class="flex items-center gap-4">
                            <div class="flex gap-2">
                                <button class="btn-accept text-sm px-3 py-2 rounded <?php echo ($accepted == 1) ? 'accepted' : ''; ?>" 
                                        onclick="setAcceptance(<?php echo $transactionId; ?>, 1)">
                                    <i class="fas fa-check mr-1"></i> Accepted
                                </button>
                                <button class="btn-reject text-sm px-3 py-2 rounded <?php echo ($accepted == 0) ? 'not-accepted' : ''; ?>" 
                                        onclick="openCommentModal(<?php echo $transactionId; ?>)">
                                    <i class="fas fa-times mr-1"></i> Not Accepted
                                </button>
                            </div>
                            <?php if ($accepted == 0 && !empty($comment)): ?>
                                <div class="text-sm text-gray-600">
                                    <span class="font-semibold">Comment:</span> <?php echo htmlspecialchars($comment); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
             <div class="flex flex-wrap justify-center gap-6 mb-8">
    <div class="flex-1 min-w-[250px] max-w-[300px] bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-blue-100">Total Amount</p>
                <p class="text-2xl font-bold mt-2">
                    <?php 
                    $totalAmount = array_sum(array_column($transactions, 'Amount'));
                    echo number_format($totalAmount, 2);
                    ?>
                </p>
            </div>
            <div class="bg-blue-400 bg-opacity-30 p-3 rounded-lg">
                <i class="fas fa-coins text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex-1 min-w-[250px] max-w-[300px] bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-green-100">Documents</p>
                <p class="text-2xl font-bold mt-2"><?php echo count($allDocuments); ?></p>
            </div>
            <div class="bg-green-400 bg-opacity-30 p-3 rounded-lg">
                <i class="fas fa-file-alt text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex-1 min-w-[250px] max-w-[300px] bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-purple-100">Transactions</p>
                <p class="text-2xl font-bold mt-2"><?php echo count($transactions); ?></p>
            </div>
            <div class="bg-purple-400 bg-opacity-30 p-3 rounded-lg">
                <i class="fas fa-exchange-alt text-2xl"></i>
            </div>
        </div>
    </div>
</div>


                <!-- Tabs -->
                <div class="mb-6 border-b border-gray-200">
                    <nav class="tab-container flex space-x-8">
                        <button onclick="showTab('transactions')" id="transactions-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm active-tab">
                            Transactions (<?php echo count($transactions); ?>)
                        </button>
                        <button onclick="showTab('documents')" id="documents-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm">
                            Documents (<?php echo count($allDocuments); ?>)
                        </button>
                        <button onclick="showTab('checklist')" id="checklist-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm">
                            Document Checklist
                        </button>
                        <button onclick="showTab('merged')" id="merged-tab" class="tab-button py-4 px-1 border-b-2 font-medium text-sm">
                            Merged Documents
                        </button>
                    </nav>
                </div>

                <!-- Transactions Tab -->
                <div id="transactions-tab-content" class="tab-content">
                    <h4 class="text-2xl font-bold text-gray-800 mb-6">Transaction Records</h4>
                    <div class="table-container rounded-xl shadow-2xl border border-gray-200 bg-white">
                        <table class="min-w-full divide-y divide-gray-300 financial-table">
                            <thead class="table-header">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Category</th>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Period</th>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Activity</th>
                                    <th scope="col" class="px-6 py-4 text-right text-sm font-bold uppercase tracking-wider">Amount</th>
                                    <th scope="col" class="px-6 py-4 text-right text-sm font-bold uppercase tracking-wider">Budget</th>
                                    <th scope="col" class="px-6 py-4 text-right text-sm font-bold uppercase tracking-wider">Actual</th>
                                    <th scope="col" class="px-6 py-4 text-right text-sm font-bold uppercase tracking-wider">Variance (%)</th>
                                    <th scope="col" class="px-6 py-4 text-center text-sm font-bold uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="table-body divide-y divide-gray-200">
                                <?php foreach ($transactions as $transaction): ?>
                                    <?php 
                                        $categoryDisplay = $transaction['CategoryName'] ?? $transaction['BudgetHeading'];
                                        $quarterDisplay = $transaction['QuarterPeriod'] ?? 'Unknown';
                                        $formattedDate = $transaction['EntryDate'] ? date('d/m/Y', strtotime($transaction['EntryDate'])) : 'N/A';
                                        $variance = $transaction['VariancePercentage'] ?? 0;
                                        
                                        // Determine variance class following global financial standards
                                        $varianceClass = 'variance-neutral';
                                        if ($variance > 0) {
                                            $varianceClass = 'variance-positive'; // Overspent - red
                                        } elseif ($variance < 0) {
                                            $varianceClass = 'variance-negative'; // Underspent - green
                                        }
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($categoryDisplay); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <?php echo htmlspecialchars($quarterDisplay); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <?php echo htmlspecialchars($transaction['Activity'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">
                                            <?php echo number_format($transaction['Amount'] ?? 0, 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">
                                            <?php echo number_format($transaction['OriginalBudget'] ?? 0, 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">
                                            <?php echo number_format($transaction['ActualSpent'] ?? 0, 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right <?php echo $varianceClass; ?>">
                                            <?php echo number_format($variance, 2); ?>%
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center">
                                            <?php echo $formattedDate; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Documents Tab -->
                <div id="documents-tab-content" class="tab-content hidden">
                    <h4 class="text-2xl font-bold text-gray-800 mb-6">Supporting Documents</h4>
                    <?php if (empty($allDocuments)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-file-alt text-5xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">No documents found for this transaction</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($allDocuments as $doc): ?>
                                <div class="document-card bg-white p-6">
                                    <div class="flex items-start">
                                        <div class="document-icon <?php 
                                            $ext = pathinfo($doc['path'], PATHINFO_EXTENSION);
                                            if ($ext === 'pdf') echo 'pdf-bg';
                                            elseif (in_array($ext, ['doc', 'docx'])) echo 'doc-bg';
                                            else echo 'unknown-bg';
                                        ?>">
                                            <i class="fas fa-file-<?php 
                                                $ext = pathinfo($doc['path'], PATHINFO_EXTENSION);
                                                if ($ext === 'pdf') echo 'pdf';
                                                elseif (in_array($ext, ['doc', 'docx'])) echo 'word';
                                                else echo 'alt';
                                            ?> text-xl"></i>
                                        </div>
                                        <div class="ml-4 flex-1 min-w-0">
                                            <h5 class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($doc['type']); ?></h5>
                                            <p class="text-sm text-gray-600 mt-1 truncate"><?php echo htmlspecialchars($doc['originalName']); ?></p>
                                            <p class="text-xs text-gray-500 mt-2 truncate">Category: <?php echo htmlspecialchars($doc['transaction']['CategoryName']); ?></p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-between items-center">
                                        <span class="text-xs text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($doc['transaction']['EntryDate'])); ?>
                                        </span>
                                        <a href="<?php echo htmlspecialchars($doc['path']); ?>" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Checklist Tab -->
                <div id="checklist-tab-content" class="tab-content hidden">
                    <h4 class="text-2xl font-bold text-gray-800 mb-6">Document Checklist</h4>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                            <p class="text-blue-700">This checklist shows required document types for this transaction and indicates which ones have been uploaded.</p>
                        </div>
                    </div>
                    
                    <?php if (empty($completeChecklist)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">No document checklist available</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php 
                            // Reorganize checklist to show categories with uploaded documents first
                            $sortedCategories = [];
                            $categoriesWithUploads = [];
                            $categoriesWithoutUploads = [];
                            
                            foreach ($completeChecklist as $category => $requiredDocs) {
                                $hasUploads = false;
                                $uploadedCount = 0;
                                
                                // Check if any documents in this category have been uploaded
                                foreach ($requiredDocs as $requiredDoc) {
                                    $normalizedRequiredDoc = normalizeDocumentType($requiredDoc);
                                    if (isset($documentChecklist[$normalizedRequiredDoc])) {
                                        $hasUploads = true;
                                        $uploadedCount += count($documentChecklist[$normalizedRequiredDoc]);
                                    }
                                }
                                
                                $categoryData = [
                                    'category' => $category,
                                    'docs' => $requiredDocs,
                                    'hasUploads' => $hasUploads,
                                    'uploadedCount' => $uploadedCount
                                ];
                                
                                if ($hasUploads) {
                                    $categoriesWithUploads[] = $categoryData;
                                } else {
                                    $categoriesWithoutUploads[] = $categoryData;
                                }
                            }
                            
                            // Combine the arrays: categories with uploads first
                            $sortedCategories = array_merge($categoriesWithUploads, $categoriesWithoutUploads);
                            ?>
                            
                            <?php foreach ($sortedCategories as $categoryData): ?>
                                <div class="bg-white p-4 rounded-lg border <?php echo $categoryData['hasUploads'] ? 'border-green-200' : 'border-gray-200'; ?>">
                                    <h5 class="font-bold text-gray-900 mb-2">
                                        <?php echo htmlspecialchars($categoryData['category']); ?>
                                        <?php if ($categoryData['hasUploads']): ?>
                                            <span class="ml-2 text-sm bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                                <?php echo $categoryData['uploadedCount']; ?> uploaded
                                            </span>
                                        <?php endif; ?>
                                    </h5>
                                    <div class="space-y-2">
                                        <?php 
                                        // Show uploaded items first within each category
                                        $uploadedItems = [];
                                        $missingItems = [];
                                        
                                        foreach ($categoryData['docs'] as $requiredDoc) {
                                            $normalizedRequiredDoc = normalizeDocumentType($requiredDoc);
                                            $found = isset($documentChecklist[$normalizedRequiredDoc]);
                                            
                                            if ($found) {
                                                $uploadedItems[] = [
                                                    'doc' => $requiredDoc,
                                                    'normalized' => $normalizedRequiredDoc,
                                                    'docs' => $documentChecklist[$normalizedRequiredDoc]
                                                ];
                                            } else {
                                                $missingItems[] = [
                                                    'doc' => $requiredDoc,
                                                    'normalized' => $normalizedRequiredDoc
                                                ];
                                            }
                                        }
                                        
                                        // Display uploaded items first
                                        foreach ($uploadedItems as $item): 
                                            $requiredDoc = $item['doc'];
                                            $normalizedRequiredDoc = $item['normalized'];
                                            $foundDocs = $item['docs'];
                                        ?>
                                            <div class="checklist-item found p-3 rounded border bg-green-50">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-5 w-5 text-green-500">
                                                        <i class="fas fa-check-circle"></i>
                                                    </div>
                                                    <div class="ml-3 flex-1">
                                                        <p class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($requiredDoc); ?>
                                                        </p>
                                                        <p class="text-sm text-gray-600 mt-1">
                                                            <?php echo count($foundDocs) . ' document(s) uploaded'; ?>
                                                        </p>
                                                    </div>
                                                    <button onclick="toggleChecklistDetails('<?php echo md5($requiredDoc); ?>')" class="text-gray-400 hover:text-gray-500">
                                                        <i class="fas fa-chevron-down"></i>
                                                    </button>
                                                </div>
                                                <div id="checklist-details-<?php echo md5($requiredDoc); ?>" class="mt-3 pl-8 space-y-2 hidden">
                                                    <?php foreach ($foundDocs as $doc): ?>
                                                        <div class="flex items-center text-sm text-gray-600">
                                                            <i class="fas fa-file mr-2"></i>
                                                            <span class="truncate"><?php echo htmlspecialchars($doc['originalName']); ?></span>
                                                            <a href="<?php echo htmlspecialchars($doc['path']); ?>" target="_blank" 
                                                               class="ml-2 text-blue-600 hover:text-blue-800">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php 
                                        // Display missing items
                                        foreach ($missingItems as $item): 
                                            $requiredDoc = $item['doc'];
                                        ?>
                                            <div class="checklist-item missing p-3 rounded border bg-red-50">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-5 w-5 text-red-500">
                                                        <i class="fas fa-times-circle"></i>
                                                    </div>
                                                    <div class="ml-3 flex-1">
                                                        <p class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($requiredDoc); ?>
                                                        </p>
                                                        <p class="text-sm text-red-600 mt-1">
                                                            Document not uploaded
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Merged Documents Tab -->
                <div id="merged-tab-content" class="tab-content hidden">
                    <h4 class="text-2xl font-bold text-gray-800 mb-6">Merged Documents</h4>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                            <p class="text-blue-700">All PDF documents for this transaction merged into a single PDF file.</p>
                        </div>
                    </div>
                    
                    <?php 
                    // Filter to only show PDF documents
                    $pdfDocuments = array_filter($allDocuments, function($doc) {
                        return pathinfo($doc['path'], PATHINFO_EXTENSION) === 'pdf';
                    });
                    ?>
                    
                    <?php if (empty($pdfDocuments)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-file-pdf text-5xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">No PDF documents available to merge</p>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h5 class="text-xl font-semibold text-gray-800">Merged PDF Document Viewer</h5>
                                <button onclick="downloadMergedPDFs()" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg flex items-center">
                                    <i class="fas fa-download mr-2"></i> Download Merged PDF
                                </button>
                            </div>
                            
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <!-- Merged PDF Viewer -->
                                <iframe src="merge_pdfs.php?id=<?php echo $transactionId; ?>&view=inline" 
                                        class="w-full" style="height: 800px;" frameborder="0">
                                    <p>Your browser does not support PDF viewing. 
                                    <a href="merge_pdfs.php?id=<?php echo $transactionId; ?>&view=inline" target="_blank">
                                        Click here to view the merged PDF
                                    </a></p>
                                </iframe>
                                
                                <!-- Document list for reference -->
                                <div class="p-4 bg-gray-50 border-t border-gray-200">
                                    <h6 class="font-medium text-gray-900 mb-2">Merged Documents (<?php echo count($pdfDocuments); ?> files):</h6>
                                    <div class="text-sm text-gray-600 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                        <?php foreach ($pdfDocuments as $doc): ?>
                                            <div class="truncate">
                                                <i class="fas fa-file-pdf text-red-500 mr-1"></i>
                                                <?php echo htmlspecialchars($doc['originalName']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Comment Modal -->
    <div id="commentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Comment for Not Accepted</h3>
                <button class="modal-close" onclick="closeCommentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="acceptanceForm">
                    <input type="hidden" id="comment_preview_id" name="preview_id">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2" for="acceptance_comment">
                            Comment *
                        </label>
                        <textarea 
                            id="acceptance_comment" 
                            name="comment" 
                            class="form-input w-full" 
                            rows="4" 
                            placeholder="Enter your comment here..."
                            required
                        ></textarea>
                    </div>
                </form>
            </div>
           <div class="modal-footer flex justify-end gap-3 mt-4">
  <button 
    onclick="closeCommentModal()"
    class="px-5 py-2 rounded-xl border border-gray-300 bg-white text-gray-700 font-medium 
           hover:bg-gray-100 hover:shadow-md transition-all duration-200">
    Cancel
  </button>

  <button 
    onclick="submitComment()"
    class="px-5 py-2 rounded-xl bg-red-600 text-white font-medium shadow 
           hover:bg-red-700 hover:shadow-lg transition-all duration-200">
    Mark as Not Accepted
  </button>
</div>

        </div>
    </div>

    <script>
        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(tab => {
                tab.classList.remove('active-tab');
                tab.classList.add('inactive-tab');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab-content').classList.remove('hidden');
            
            // Add active class to selected tab
            document.getElementById(tabName + '-tab').classList.remove('inactive-tab');
            document.getElementById(tabName + '-tab').classList.add('active-tab');
        }
        
        // Toggle checklist details
        function toggleChecklistDetails(id) {
            const element = document.getElementById('checklist-details-' + id);
            const icon = event.currentTarget.querySelector('i');
            
            if (element.classList.contains('hidden')) {
                element.classList.remove('hidden');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                element.classList.add('hidden');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
        
        // Download merged PDFs
        function downloadMergedPDFs() {
            // Use the transaction ID instead of PV number
            const transactionId = <?php echo json_encode($transactionId); ?>;
            
            if (!transactionId) {
                alert('Unable to determine transaction ID.');
                return;
            }
            
            // Redirect to the merge script
            window.location.href = 'merge_pdfs.php?id=' + encodeURIComponent(transactionId);
        }
        
        // Set initial active tab
        document.addEventListener('DOMContentLoaded', function() {
            // Transactions tab is active by default
            showTab('transactions');
        });
        
        // Acceptance functions
        function setAcceptance(previewId, accepted) {
            // Prevent default behavior that might cause navigation
            if (event) {
                event.preventDefault();
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'set_acceptance');
            formData.append('preview_id', previewId);
            formData.append('accepted', accepted);
            
            // Send AJAX request
            fetch('admin_fields_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the UI without reloading the page
                    updateAcceptanceUI(previewId, accepted);
                    // If setting to accepted, also clear any comment display
                    if (accepted == 1) {
                        updateCommentDisplay(previewId, '');
                    }
                } else {
                    alert('Error updating acceptance status: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating acceptance status: ' + error);
            });
        }
        
        function updateAcceptanceUI(previewId, accepted) {
            // Update button classes based on acceptance status
            const acceptButton = document.querySelector('.btn-accept');
            const rejectButton = document.querySelector('.btn-reject');
            
            if (acceptButton && rejectButton) {
                // Update button classes based on acceptance status
                if (accepted == 1) {
                    acceptButton.classList.add('accepted');
                    rejectButton.classList.remove('not-accepted');
                } else {
                    acceptButton.classList.remove('accepted');
                    rejectButton.classList.add('not-accepted');
                }
            }
        }
        
        function updateCommentDisplay(previewId, comment) {
            // Remove any existing comment display
            const existingCommentDisplay = document.querySelector('.text-sm.text-gray-600');
            if (existingCommentDisplay && existingCommentDisplay.querySelector('span.font-semibold')) {
                existingCommentDisplay.remove();
            }
            
            // Add new comment display if comment is not empty
            if (comment && comment.trim() !== '') {
                const acceptanceSection = document.querySelector('.bg-gray-50');
                if (acceptanceSection) {
                    const commentDisplay = document.createElement('div');
                    commentDisplay.className = 'text-sm text-gray-600 mt-2';
                    commentDisplay.innerHTML = '<span class="font-semibold">Comment:</span> ' + comment;
                    acceptanceSection.appendChild(commentDisplay);
                }
            }
        }
        
        function openCommentModal(previewId) {
            // Prevent default behavior that might cause navigation
            if (event) {
                event.preventDefault();
            }
            
            document.getElementById('comment_preview_id').value = previewId;
            document.getElementById('acceptance_comment').value = '';
            document.getElementById('commentModal').style.display = 'flex';
        }
        
        function closeCommentModal() {
            document.getElementById('commentModal').style.display = 'none';
        }
        
        function submitComment() {
            const formData = new FormData(document.getElementById('acceptanceForm'));
            const comment = document.getElementById('acceptance_comment').value;
            const previewId = document.getElementById('comment_preview_id').value;
            
            formData.append('action', 'set_acceptance');
            formData.append('accepted', 0); // 0 for not accepted
            
            fetch('admin_fields_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI with the new acceptance status and comment
                    updateAcceptanceUI(previewId, 0); // 0 for not accepted
                    updateCommentDisplay(previewId, comment);
                    
                    closeCommentModal();
                } else {
                    alert('Error updating acceptance status: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating acceptance status: ' + error);
            });
        }
        
        // Handle acceptance form submission
        document.getElementById('acceptanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            
            submitComment();
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const commentModal = document.getElementById('commentModal');
            
            if (event.target == commentModal) {
                commentModal.style.display = 'none';
            }
        }

    </script>
</body>
</html>
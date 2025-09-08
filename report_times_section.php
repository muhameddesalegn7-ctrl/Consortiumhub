<?php 
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if this file is being included or accessed directly
$included = defined('INCLUDED_FROM_INDEX');

if (!$included) {
    include 'header.php'; 
}
?>

<?php
// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Get user cluster information
$userCluster = $_SESSION['cluster_name'] ?? null;

// Fetch reporting periods from budget_data table based on user's cluster
$reportingPeriods = [];
if ($userCluster) {
    $periodQuery = "SELECT DISTINCT period_name, start_date, end_date, quarter_number 
                    FROM budget_data 
                    WHERE cluster = ? AND start_date IS NOT NULL AND end_date IS NOT NULL
                    ORDER BY quarter_number";
    $stmt = $conn->prepare($periodQuery);
    $stmt->bind_param("s", $userCluster);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reportingPeriods[] = $row;
    }
}
?>

<?php if (!$included): ?>
<div class="flex flex-col flex-1 min-w-0">
    <!-- Header -->
    <header class="flex items-center justify-between h-20 px-8 bg-white border-b border-gray-200 shadow-sm rounded-bl-xl">
        <div class="flex items-center">
            <!-- Hamburger menu for small screens -->
            <button id="sidebarOpenBtn"
                class="text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                        d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>
            <h2 class="ml-4 text-2xl font-semibold text-gray-800">Report Times</h2>
        </div>
    </header>

    <!-- Content Area -->
    <main class="flex-1 p-8 overflow-y-auto overflow-x-auto bg-gray-50">
<?php endif; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <div id="reportTimesSection" class="bg-white p-8 rounded-xl shadow-lg max-w-3xl mx-auto w-full animate-fadeIn card-hover flex-1 overflow-y-auto mt-6">
        <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">Report Deadlines</h3>
        <p class="text-gray-500 text-center mb-8">Stay on track with upcoming reporting requirements</p>
        
        <div class="space-y-4">
            <?php if (!empty($reportingPeriods)): ?>
                <?php foreach ($reportingPeriods as $period): ?>
                    <div class="border border-gray-200 p-5 rounded-lg hover:border-primary-300 transition duration-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($period['period_name']); ?> Report:</p>
                                <p class="text-gray-600 mt-1">Reporting Period: <?php echo date('F j, Y', strtotime($period['start_date'])); ?> - <?php echo date('F j, Y', strtotime($period['end_date'])); ?></p>
                            </div>
                            <?php 
                            $today = new DateTime();
                            $endDate = new DateTime($period['end_date']);
                            $startDate = new DateTime($period['start_date']);
                            $interval = $today->diff($endDate);
                            $daysRemaining = $interval->days;
                            
                            // Determine status based on date
                            if ($today > $endDate) {
                                $status = 'Overdue';
                                $statusClass = 'bg-red-100 text-red-800';
                                $progress = 100;
                            } elseif ($today < $startDate) {
                                $status = 'Upcoming';
                                $statusClass = 'bg-blue-100 text-blue-800';
                                $progress = 10;
                            } else {
                                $totalPeriod = $startDate->diff($endDate)->days;
                                $elapsed = $startDate->diff($today)->days;
                                $progress = min(100, max(0, ($elapsed / $totalPeriod) * 100));
                                
                                if ($progress > 75) {
                                    $status = 'Pending';
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                } else {
                                    $status = 'In Progress';
                                    $statusClass = 'bg-green-100 text-green-800';
                                }
                            }
                            ?>
                            <span class="px-3 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo $status; ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                            <div class="bg-primary-600 h-2 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            <span><?php echo $daysRemaining; ?> days remaining</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="border border-gray-200 p-5 rounded-lg text-center">
                    <p class="text-gray-600">No reporting periods found for your cluster.</p>
                </div>
            <?php endif; ?>
            
            <div class="bg-primary-50 border border-primary-200 p-4 rounded-lg mt-6">
                <p class="text-sm text-primary-800 text-center">
                    <span class="font-medium">Note:</span> This section shows report times based on your cluster's budget periods. Reports should be submitted within the specified date ranges.
                </p>
            </div>
        </div>
    </div>
</div>

<?php if (!$included): ?>
    </main>
</div>
<?php include 'message_system.php'; ?>
<?php endif; ?>

<script>
// Show the report times section when the page loads
document.addEventListener('DOMContentLoaded', function() {
    const section = document.getElementById('reportTimesSection');
    if (section) {
        section.classList.remove('hidden');
    }
});
</script>
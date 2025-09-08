<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Get filter parameters
$clusterFilter = isset($_GET['cluster']) ? $_GET['cluster'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$sql = "SELECT id, cluster_name, year, certificate_path, uploaded_date, uploaded_by FROM certificates_simple WHERE 1=1";

$params = [];
$types = "";

if (!empty($clusterFilter)) {
    $sql .= " AND cluster_name = ?";
    $params[] = $clusterFilter;
    $types .= "s";
}

if (!empty($dateFrom)) {
    $sql .= " AND DATE(uploaded_date) >= ?";
    $params[] = $dateFrom;
    $types .= "s";
}

if (!empty($dateTo)) {
    $sql .= " AND DATE(uploaded_date) <= ?";
    $params[] = $dateTo;
    $types .= "s";
}

$sql .= " ORDER BY uploaded_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all clusters for filter dropdown
$clustersSql = "SELECT DISTINCT cluster_name FROM certificates_simple ORDER BY cluster_name";
$clustersResult = $conn->query($clustersSql);
$clusters = [];
if ($clustersResult && $clustersResult->num_rows > 0) {
    while ($row = $clustersResult->fetch_assoc()) {
        $clusters[] = $row['cluster_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #6c757d;
            --success: #10b981;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
            --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background: var(--gradient-background);
            color: var(--dark);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            overflow-x: auto; /* Enable horizontal scrolling if needed */
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            color: var(--dark);
            padding: 25px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            /* Removed gradient for black text */
            color: #000;
        }

        .logo i {
            margin-right: 12px;
            font-size: 32px;
            color: var(--primary);
        }

        .user-info {
            font-size: 16px;
            font-weight: 500;
            /* Changed to black text */
            color: #000;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin: 20px 0;
            text-align: center;
            /* Changed to black text */
            color: #000;
        }

        .filter-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 30px;
        }

        .filter-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
            display: flex;
            align-items: center;
        }

        .filter-title i {
            margin-right: 10px;
            color: var(--primary);
            font-size: 20px;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .form-group input,
        .form-group select {
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: white;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        button {
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease;
        }

        button:hover::before {
            width: 200px;
            height: 200px;
        }

        .btn-primary {
            /* Changed from gradient to solid blue */
            background: #2563eb;
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            /* Increased border */
            border: 3px solid #1d4ed8;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 10px 18px;
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .certificates-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            margin-bottom: 30px;
            overflow-x: auto; /* Enable horizontal scrolling */
        }

        .table-header {
            background: #1e40af;
            color: white;
            padding: 20px 30px;
            font-weight: 600;
            font-size: 18px;
        }

        .table-container {
            overflow-x: auto;
            max-height: 70vh; /* Set maximum height for vertical scrolling */
            overflow-y: auto; /* Enable vertical scrolling */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: #2563eb;
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tr:hover {
            background-color: #f1f5f9;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-download {
            background: var(--success);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-download:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-view {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .no-certificates {
            text-align: center;
            padding: 40px;
            color: var(--secondary);
            font-style: italic;
        }

        @media (max-width: 768px) {
            .filters {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-certificate"></i>
                    Certificate Manager
                </div>
                <div class="user-info no-print">
                    <i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                </div>
            </div>
            <h1 class="page-title">Certificate Management</h1>
        </header>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-title">
                <i class="fas fa-filter"></i> Filter Certificates
            </div>
            
            <form method="GET" id="filterForm">
                <div class="filters">
                    <div class="form-group">
                        <label for="cluster">Cluster</label>
                        <select name="cluster" id="cluster">
                            <option value="">All Clusters</option>
                            <?php foreach ($clusters as $cluster): ?>
                                <option value="<?php echo htmlspecialchars($cluster); ?>" <?php echo ($clusterFilter === $cluster) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cluster); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_from">From Date</label>
                        <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">To Date</label>
                        <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button type="button" class="btn-outline" id="resetFilters">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Certificates Table -->
        <div class="certificates-table">
            <div class="table-header">
                <i class="fas fa-table"></i> Uploaded Certificates
            </div>
            
            <div class="table-container">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Cluster</th>
                                <th>Year</th>
                                <th>Uploaded Date</th>
                                <th>Uploaded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['cluster_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($row['uploaded_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['uploaded_by']); ?></td>
                                    <td class="action-buttons">
                                        <?php if (file_exists($row['certificate_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['certificate_path']); ?>" target="_blank" class="btn-view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="<?php echo htmlspecialchars($row['certificate_path']); ?>" download class="btn-download">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <span class="text-danger">File not found</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-certificates">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <h3>No certificates found</h3>
                        <p>Try adjusting your filters or upload some certificates first.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('resetFilters').addEventListener('click', function() {
            document.getElementById('cluster').value = '';
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
        });
    </script>
</body>
</html>
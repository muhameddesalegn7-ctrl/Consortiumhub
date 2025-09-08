<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'header.php';
// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $role = $_POST['role'] ?? 'finance_officer';
                $cluster_name = !empty($_POST['cluster_name']) ? $_POST['cluster_name'] : NULL;
                
                // Validate input
                if (empty($username) || empty($email) || empty($password)) {
                    $error = "All fields except cluster are required";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format";
                } else {
                    // Check if user already exists
                    $checkQuery = "SELECT id FROM users WHERE email = ?";
                    $stmt = $conn->prepare($checkQuery);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = "User with this email already exists";
                    } else {
                        // Insert new user
                        $insertQuery = "INSERT INTO users (username, email, password, role, cluster_name, is_active) VALUES (?, ?, ?, ?, ?, 1)";
                        $stmt = $conn->prepare($insertQuery);
                        $stmt->bind_param("sssss", $username, $email, $password, $role, $cluster_name);
                        
                        if ($stmt->execute()) {
                            $success = "User added successfully";
                        } else {
                            $error = "Error adding user: " . $conn->error;
                        }
                    }
                }
                break;
                
            case 'edit_user':
                $user_id = $_POST['user_id'] ?? 0;
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $role = $_POST['role'] ?? 'finance_officer';
                $cluster_name = !empty($_POST['cluster_name']) ? $_POST['cluster_name'] : NULL;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($username) || empty($email)) {
                    $error = "Username and email are required";
                } else {
                    $updateQuery = "UPDATE users SET username = ?, email = ?, role = ?, cluster_name = ?, is_active = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("ssssii", $username, $email, $role, $cluster_name, $is_active, $user_id);
                    
                    if ($stmt->execute()) {
                        $success = "User updated successfully";
                    } else {
                        $error = "Error updating user: " . $conn->error;
                    }
                }
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'] ?? 0;
                
                // Prevent deleting the current admin user
                if ($user_id == $_SESSION['user_id']) {
                    $error = "You cannot delete your own account";
                } else {
                    $deleteQuery = "DELETE FROM users WHERE id = ?";
                    $stmt = $conn->prepare($deleteQuery);
                    $stmt->bind_param("i", $user_id);
                    
                    if ($stmt->execute()) {
                        $success = "User deleted successfully";
                    } else {
                        $error = "Error deleting user: " . $conn->error;
                    }
                }
                break;
                
            case 'change_password':
                $user_id = $_POST['user_id'] ?? 0;
                $new_password = trim($_POST['new_password'] ?? '');
                
                if (empty($new_password)) {
                    $error = "Password cannot be empty";
                } else {
                    $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("si", $new_password, $user_id);
                    
                    if ($stmt->execute()) {
                        $success = "Password updated successfully";
                    } else {
                        $error = "Error updating password: " . $conn->error;
                    }
                }
                break;
                
            case 'add_cluster':
                $cluster_name = trim($_POST['cluster_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($cluster_name)) {
                    $error = "Cluster name is required";
                } else {
                    // Check if cluster already exists
                    $checkQuery = "SELECT id FROM clusters WHERE cluster_name = ?";
                    $stmt = $conn->prepare($checkQuery);
                    $stmt->bind_param("s", $cluster_name);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = "Cluster with this name already exists";
                    } else {
                        // Insert new cluster
                        $insertQuery = "INSERT INTO clusters (cluster_name, description, is_active) VALUES (?, ?, 1)";
                        $stmt = $conn->prepare($insertQuery);
                        $stmt->bind_param("ss", $cluster_name, $description);
                        
                        if ($stmt->execute()) {
                            $success = "Cluster added successfully";
                        } else {
                            $error = "Error adding cluster: " . $conn->error;
                        }
                    }
                }
                break;
                
            case 'edit_cluster':
                $cluster_id = $_POST['cluster_id'] ?? 0;
                $cluster_name = trim($_POST['cluster_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($cluster_name)) {
                    $error = "Cluster name is required";
                } else {
                    $updateQuery = "UPDATE clusters SET cluster_name = ?, description = ?, is_active = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("ssii", $cluster_name, $description, $is_active, $cluster_id);
                    
                    if ($stmt->execute()) {
                        $success = "Cluster updated successfully";
                    } else {
                        $error = "Error updating cluster: " . $conn->error;
                    }
                }
                break;
                
            case 'delete_cluster':
                $cluster_id = $_POST['cluster_id'] ?? 0;
                
                // Check if cluster is being used by any users or fields
                $checkUsersQuery = "SELECT COUNT(*) as count FROM users WHERE cluster_name = (SELECT cluster_name FROM clusters WHERE id = ?)";
                $stmt = $conn->prepare($checkUsersQuery);
                $stmt->bind_param("i", $cluster_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $userCount = $result->fetch_assoc()['count'];
                
                $checkFieldsQuery = "SELECT COUNT(*) as count FROM predefined_fields WHERE cluster_name = (SELECT cluster_name FROM clusters WHERE id = ?)";
                $stmt = $conn->prepare($checkFieldsQuery);
                $stmt->bind_param("i", $cluster_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $fieldCount = $result->fetch_assoc()['count'];
                
                if ($userCount > 0 || $fieldCount > 0) {
                    $error = "Cannot delete cluster because it is being used by " . $userCount . " users and " . $fieldCount . " fields";
                } else {
                    $deleteQuery = "DELETE FROM clusters WHERE id = ?";
                    $stmt = $conn->prepare($deleteQuery);
                    $stmt->bind_param("i", $cluster_id);
                    
                    if ($stmt->execute()) {
                        $success = "Cluster deleted successfully";
                    } else {
                        $error = "Error deleting cluster: " . $conn->error;
                    }
                }
                break;
                
            // Predefined Fields Management
            case 'add_predefined_field':
                $field_name = trim($_POST['field_name'] ?? '');
                $field_type = $_POST['field_type'] ?? 'dropdown';
                $cluster_name = !empty($_POST['cluster_name']) ? $_POST['cluster_name'] : NULL;
                $source_field = !empty($_POST['source_field']) ? $_POST['source_field'] : NULL;
                
                // If copying from existing field, get source field details first
                if ($source_field) {
                    $sourceQuery = "SELECT field_name as source_field_name, field_type, field_values FROM predefined_fields WHERE id = ?";
                    $stmt = $conn->prepare($sourceQuery);
                    $stmt->bind_param("i", $source_field);
                    $stmt->execute();
                    $sourceResult = $stmt->get_result();
                    
                    if ($sourceResult && $sourceResult->num_rows > 0) {
                        $sourceField = $sourceResult->fetch_assoc();
                        // If no field name was entered, use the source field name
                        if (empty($field_name)) {
                            $field_name = $sourceField['source_field_name'];
                        }
                        $field_type = $sourceField['field_type'];
                        $field_values = $sourceField['field_values'];
                    }
                }
                
                if (empty($field_name)) {
                    $error = "Field name is required";
                } else {
                    // Check if field already exists for this cluster
                    $checkQuery = "SELECT id FROM predefined_fields WHERE field_name = ? AND cluster_name " . ($cluster_name ? "= ?" : "IS NULL");
                    $stmt = $conn->prepare($checkQuery);
                    if ($cluster_name) {
                        $stmt->bind_param("ss", $field_name, $cluster_name);
                    } else {
                        $stmt->bind_param("s", $field_name);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = "Field with this name already exists for this cluster";
                    } else {
                        // Insert new field with copied or default values
                        if (!empty($field_values)) {
                            if ($cluster_name) {
                                $insertQuery = "INSERT INTO predefined_fields (field_name, field_type, field_values, cluster_name) VALUES (?, ?, ?, ?)";
                                $stmt = $conn->prepare($insertQuery);
                                $stmt->bind_param("ssss", $field_name, $field_type, $field_values, $cluster_name);
                            } else {
                                $insertQuery = "INSERT INTO predefined_fields (field_name, field_type, field_values) VALUES (?, ?, ?)";
                                $stmt = $conn->prepare($insertQuery);
                                $stmt->bind_param("sss", $field_name, $field_type, $field_values);
                            }
                        } else {
                            if ($cluster_name) {
                                $insertQuery = "INSERT INTO predefined_fields (field_name, field_type, cluster_name) VALUES (?, ?, ?)";
                                $stmt = $conn->prepare($insertQuery);
                                $stmt->bind_param("sss", $field_name, $field_type, $cluster_name);
                            } else {
                                $insertQuery = "INSERT INTO predefined_fields (field_name, field_type) VALUES (?, ?)";
                                $stmt = $conn->prepare($insertQuery);
                                $stmt->bind_param("ss", $field_name, $field_type);
                            }
                        }
                        
                        if ($stmt->execute()) {
                            $success = "Predefined field added successfully";
                        } else {
                            $error = "Error adding predefined field: " . $conn->error;
                        }
                    }
                }
                break;
                
            case 'edit_predefined_field':
                $field_id = $_POST['field_id'] ?? 0;
                $field_name = trim($_POST['field_name'] ?? '');
                $field_type = $_POST['field_type'] ?? 'dropdown';
                $cluster_name = !empty($_POST['cluster_name']) ? $_POST['cluster_name'] : NULL;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($field_name)) {
                    $error = "Field name is required";
                } else {
                    $updateQuery = "UPDATE predefined_fields SET field_name = ?, field_type = ?, cluster_name = ?, is_active = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("ssssi", $field_name, $field_type, $cluster_name, $is_active, $field_id);
                    
                    if ($stmt->execute()) {
                        $success = "Predefined field updated successfully";
                    } else {
                        $error = "Error updating predefined field: " . $conn->error;
                    }
                }
                break;
                
            case 'delete_predefined_field':
                $field_id = $_POST['field_id'] ?? 0;
                
                $deleteQuery = "DELETE FROM predefined_fields WHERE id = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("i", $field_id);
                
                if ($stmt->execute()) {
                    $success = "Predefined field deleted successfully";
                } else {
                    $error = "Error deleting predefined field: " . $conn->error;
                }
                break;
        }
    }
}

// Get all clusters for dropdown
$clustersQuery = "SELECT * FROM clusters ORDER BY cluster_name";
$clustersResult = $conn->query($clustersQuery);
$clusters = [];
if ($clustersResult && $clustersResult->num_rows > 0) {
    while ($row = $clustersResult->fetch_assoc()) {
        $clusters[] = $row;
    }
}

// Get all users for display
$usersQuery = "SELECT id, username, email, role, cluster_name, is_active, created_at FROM users ORDER BY id";
$usersResult = $conn->query($usersQuery);
$users = [];
if ($usersResult && $usersResult->num_rows > 0) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get all predefined fields for display
$fieldsQuery = "SELECT pf.*, c.cluster_name as cluster_display FROM predefined_fields pf LEFT JOIN clusters c ON pf.cluster_name = c.cluster_name ORDER BY pf.field_name, pf.cluster_name IS NULL, pf.cluster_name";
$fieldsResult = $conn->query($fieldsQuery);
$fields = [];
if ($fieldsResult && $fieldsResult->num_rows > 0) {
    while ($row = $fieldsResult->fetch_assoc()) {
        $fields[] = $row;
    }
}

// Get all predefined fields for source selection
$sourceFieldsQuery = "SELECT id, field_name, cluster_name FROM predefined_fields WHERE is_active = 1 ORDER BY field_name, cluster_name";
$sourceFieldsResult = $conn->query($sourceFieldsQuery);
$sourceFields = [];
if ($sourceFieldsResult && $sourceFieldsResult->num_rows > 0) {
    while ($row = $sourceFieldsResult->fetch_assoc()) {
        $sourceFields[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management | Consortium Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            overflow-y: auto; /* Make page scrollable */
        }
        
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-input {
            transition: all 0.3s;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            width: 100%;
            font-size: 0.95rem;
            background: #ffffff;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: #fefefe;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(107, 114, 128, 0.2);
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(107, 114, 128, 0.3);
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);
        }
        
        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.3);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }
        
        .table-container {
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #334155;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background-color: #f1f5f9;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .badge-admin {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        
        .badge-finance {
            background-color: #dcfce7;
            color: #15803d;
        }
        
        .badge-active {
            background-color: #dcfce7;
            color: #15803d;
        }
        
        .badge-inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .badge-dropdown {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .badge-input {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        
        .tab {
            padding: 1rem 2rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            color: #64748b;
        }
        
        .tab.active {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
        }
        
        .tab-content {
            display: none;
            min-height: 600px; /* Ensure consistent minimum height for both tabs */
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Ensure consistent width for form containers */
        .form-container {
            max-width: 100%;
        }
        
        /* Add consistent padding and structure to both tabs */
        .tab-wrapper {
            min-height: 500px; /* Ensure wrapper has minimum height */
        }
        
        .tab-content > div {
            min-height: 200px; /* Ensure form sections have minimum height */
        }
        
        /* Ensure table containers have consistent height */
        .table-container {
            min-height: 300px; /* Ensure tables have minimum height */
        }
    </style>
</head>
<body>
    <div class="min-h-screen py-8 overflow-y-auto main-content-flex">
        <div class="max-w-6xl mx-auto px-4 w-full">
            <!-- Header -->
            <div class="admin-card p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">User & Cluster Management</h1>
                        <p class="text-gray-600 mt-2">Manage users, assign roles and clusters</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <p class="text-gray-700 font-medium"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                            <p class="text-gray-500 text-sm">Administrator</p>
                        </div>
                        <a href="logout.php" class="btn-secondary flex items-center gap-2">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($error): ?>
                <div class="admin-card p-4 mb-6 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="admin-card p-4 mb-6 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="admin-card p-6 mb-8">
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('users')">Users</div>
                    <div class="tab" onclick="switchTab('clusters')">Clusters</div>
                    <div class="tab" onclick="switchTab('fields')">Predefined Fields</div>
                </div>
                
                <!-- Users Tab -->
                <div id="users-tab" class="tab-content active">
                    <div class="tab-wrapper"> <!-- Added wrapper for consistency -->
                        <!-- Add User Form -->
                        <div class="mb-8 form-container">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New User</h2>
                            
                            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <input type="hidden" name="action" value="add_user">
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="username">
                                        <i class="fas fa-user mr-2"></i>Username
                                    </label>
                                    <input 
                                        type="text" 
                                        id="username" 
                                        name="username" 
                                        class="form-input" 
                                        placeholder="Enter username"
                                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                        required
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="email">
                                        <i class="fas fa-envelope mr-2"></i>Email
                                    </label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        class="form-input" 
                                        placeholder="Enter email"
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                        required
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="password">
                                        <i class="fas fa-lock mr-2"></i>Password
                                    </label>
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        class="form-input" 
                                        placeholder="Enter password"
                                        required
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="role">
                                        <i class="fas fa-user-tag mr-2"></i>Role
                                    </label>
                                    <select id="role" name="role" class="form-input">
                                        <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                        <option value="finance_officer" <?php echo (($_POST['role'] ?? 'finance_officer') === 'finance_officer') ? 'selected' : ''; ?>>Finance Officer</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="cluster_name">
                                        <i class="fas fa-building mr-2"></i>Cluster (Optional)
                                    </label>
                                    <select id="cluster_name" name="cluster_name" class="form-input">
                                        <option value="">No Cluster</option>
                                        <?php foreach ($clusters as $cluster): ?>
                                            <option value="<?php echo htmlspecialchars($cluster['cluster_name']); ?>" <?php echo (($_POST['cluster_name'] ?? '') === $cluster['cluster_name']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cluster['cluster_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2 flex justify-end">
                                    <button type="submit" class="btn-primary flex items-center gap-2">
                                        <i class="fas fa-plus"></i> Add User
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Users List -->
                        <div class="form-container">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Users</h2>
                            
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Cluster</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($users)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-gray-500 py-8">
                                                    <i class="fas fa-users fa-2x mb-2"></i>
                                                    <p>No users found</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td class="font-medium"><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-finance'; ?>">
                                                            <?php echo $user['role'] === 'admin' ? 'Administrator' : 'Finance Officer'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($user['cluster_name']): ?>
                                                            <span class="badge badge-admin">
                                                                <?php echo htmlspecialchars($user['cluster_name']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-gray-500">None</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $user['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                    <td>
                                                        <div class="flex gap-2">
                                                            <button class="btn-success text-sm" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo $user['role']; ?>', '<?php echo htmlspecialchars($user['cluster_name'] ?? ''); ?>', <?php echo $user['is_active']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn-danger text-sm" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> <!-- End wrapper -->
                </div>
                
                <!-- Clusters Tab -->
                <div id="clusters-tab" class="tab-content">
                    <div class="tab-wrapper"> <!-- Added wrapper for consistency -->
                        <!-- Add Cluster Form -->
                        <div class="mb-8 form-container">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Cluster</h2>
                            
                            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <input type="hidden" name="action" value="add_cluster">
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="new_cluster_name">
                                        <i class="fas fa-building mr-2"></i>Cluster Name
                                    </label>
                                    <input 
                                        type="text" 
                                        id="new_cluster_name" 
                                        name="cluster_name" 
                                        class="form-input" 
                                        placeholder="Enter cluster name"
                                        required
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="new_cluster_description">
                                        <i class="fas fa-align-left mr-2"></i>Description (Optional)
                                    </label>
                                    <input 
                                        type="text" 
                                        id="new_cluster_description" 
                                        name="description" 
                                        class="form-input" 
                                        placeholder="Enter description"
                                    >
                                </div>
                                
                                <div class="md:col-span-2 flex justify-end">
                                    <button type="submit" class="btn-primary flex items-center gap-2">
                                        <i class="fas fa-plus"></i> Add Cluster
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        
                        <!-- Clusters List -->
                        <div class="form-container">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Clusters</h2>
                            
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Cluster Name</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($clusters)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-gray-500 py-8">
                                                    <i class="fas fa-building fa-2x mb-2"></i>
                                                    <p>No clusters found</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($clusters as $cluster): ?>
                                                <tr>
                                                    <td class="font-medium"><?php echo $cluster['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($cluster['cluster_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($cluster['description'] ?? ''); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $cluster['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                            <?php echo $cluster['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($cluster['created_at'])); ?></td>
                                                    <td>
                                                        <div class="flex gap-2">
                                                            <button class="btn-success text-sm" onclick="editCluster(<?php echo $cluster['id']; ?>, '<?php echo htmlspecialchars($cluster['cluster_name']); ?>', '<?php echo htmlspecialchars($cluster['description'] ?? ''); ?>', <?php echo $cluster['is_active']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn-danger text-sm" onclick="deleteCluster(<?php echo $cluster['id']; ?>, '<?php echo htmlspecialchars($cluster['cluster_name']); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> <!-- End wrapper -->
                </div>
                
                <!-- Predefined Fields Tab -->
                <div id="fields-tab" class="tab-content">
                    <div class="tab-wrapper"> <!-- Added wrapper for consistency -->
                        <!-- Add Predefined Field Form -->
                        <div class="mb-8 form-container">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Predefined Field</h2>
                            
                            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <input type="hidden" name="action" value="add_predefined_field">
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="field_name">
                                        <i class="fas fa-tag mr-2"></i>Field Name
                                    </label>
                                    <input 
                                        type="text" 
                                        id="field_name" 
                                        name="field_name" 
                                        class="form-input" 
                                        placeholder="Enter field name (optional when copying from existing field)"
                                    >
                                    <p class="text-sm text-gray-500 mt-1">If copying from an existing field, leave blank to use the source field name</p>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="field_type">
                                        <i class="fas fa-list mr-2"></i>Field Type
                                    </label>
                                    <select id="field_type" name="field_type" class="form-input">
                                        <option value="dropdown">Dropdown</option>
                                        <option value="input">Input Field</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="field_cluster_name">
                                        <i class="fas fa-building mr-2"></i>Cluster (Optional)
                                    </label>
                                    <select id="field_cluster_name" name="cluster_name" class="form-input">
                                        <option value="">Global (All Clusters)</option>
                                        <?php foreach ($clusters as $cluster): ?>
                                            <option value="<?php echo htmlspecialchars($cluster['cluster_name']); ?>">
                                                <?php echo htmlspecialchars($cluster['cluster_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2" for="source_field">
                                        <i class="fas fa-copy mr-2"></i>Copy from Existing Field (Optional)
                                    </label>
                                    <select id="source_field" name="source_field" class="form-input" onchange="handleSourceFieldChange()">
                                        <option value="">Create New Field</option>
                                        <?php foreach ($sourceFields as $sourceField): ?>
                                            <option value="<?php echo $sourceField['id']; ?>">
                                                <?php echo htmlspecialchars($sourceField['field_name']); ?> 
                                                <?php if ($sourceField['cluster_name']): ?>
                                                    (<?php echo htmlspecialchars($sourceField['cluster_name']); ?>)
                                                <?php else: ?>
                                                    (Global)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2 flex justify-end">
                                    <button type="submit" class="btn-primary flex items-center gap-2">
                                        <i class="fas fa-plus"></i> Add Field
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Predefined Fields List -->
                        <div class="form-container">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Predefined Fields</h2>
                            
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Field Name</th>
                                            <th>Type</th>
                                            <th>Cluster</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($fields)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-gray-500 py-8">
                                                    <i class="fas fa-tags fa-2x mb-2"></i>
                                                    <p>No predefined fields found</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($fields as $field): ?>
                                                <tr>
                                                    <td class="font-medium"><?php echo $field['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($field['field_name']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $field['field_type'] === 'dropdown' ? 'badge-dropdown' : 'badge-input'; ?>">
                                                            <?php echo $field['field_type'] === 'dropdown' ? 'Dropdown' : 'Input Field'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($field['cluster_name']): ?>
                                                            <span class="badge badge-admin">
                                                                <?php echo htmlspecialchars($field['cluster_name']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-gray-500">Global</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $field['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                            <?php echo $field['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($field['created_at'])); ?></td>
                                                    <td>
                                                        <div class="flex gap-2">
                                                            <button class="btn-success text-sm" onclick="editField(<?php echo $field['id']; ?>, '<?php echo htmlspecialchars($field['field_name']); ?>', '<?php echo $field['field_type']; ?>', '<?php echo htmlspecialchars($field['cluster_name'] ?? ''); ?>', <?php echo $field['is_active']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn-danger text-sm" onclick="deleteField(<?php echo $field['id']; ?>, '<?php echo htmlspecialchars($field['field_name']); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> <!-- End wrapper -->
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="flex justify-center gap-4 mt-8">
                <a href="admin_predefined_fields.php" class="btn-secondary">
                    <i class="fas fa-cog mr-2"></i> Field Configuration
                </a>
                <a href="#clusters" class="btn-secondary" onclick="switchTab('clusters')">
                    <i class="fas fa-building mr-2"></i> Cluster Management
                </a>
                <a href="admin_budget_management.php" class="btn-secondary">
                    <i class="fas fa-chart-bar mr-2"></i> Budget Management
                </a>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Edit User</h3>
                <span class="cursor-pointer text-2xl" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="editUsername">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input 
                        type="text" 
                        id="editUsername" 
                        name="username" 
                        class="form-input" 
                        required
                    >
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="editEmail">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input 
                        type="email" 
                        id="editEmail" 
                        name="email" 
                        class="form-input" 
                        required
                    >
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="editRole">
                        <i class="fas fa-user-tag mr-2"></i>Role
                    </label>
                    <select id="editRole" name="role" class="form-input">
                        <option value="admin">Administrator</option>
                        <option value="finance_officer">Finance Officer</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="editCluster">
                        <i class="fas fa-building mr-2"></i>Cluster (Optional)
                    </label>
                    <select id="editCluster" name="cluster_name" class="form-input">
                        <option value="">No Cluster</option>
                        <?php foreach ($clusters as $cluster): ?>
                            <option value="<?php echo htmlspecialchars($cluster['cluster_name']); ?>">
                                <?php echo htmlspecialchars($cluster['cluster_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="editActive" name="is_active" class="rounded text-blue-600">
                        <span class="ml-2 text-gray-700">Active</span>
                    </label>
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete User Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Delete User</h3>
                <span class="cursor-pointer text-2xl" onclick="closeDeleteModal()">&times;</span>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                
                <p class="mb-4">Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Cluster Modal -->
    <div id="editClusterModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Edit Cluster</h3>
                <span class="cursor-pointer text-2xl" onclick="closeEditClusterModal()">&times;</span>
            </div>
            <form method="POST" id="editClusterForm">
                <input type="hidden" name="action" value="edit_cluster">
                <input type="hidden" name="cluster_id" id="editClusterId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="editClusterName">
                        <i class="fas fa-building mr-2"></i>Cluster Name
                    </label>
                    <input 
                        type="text" 
                        id="editClusterName" 
                        name="cluster_name" 
                        class="form-input" 
                        required
                    >
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="editClusterDescription">
                        <i class="fas fa-align-left mr-2"></i>Description (Optional)
                    </label>
                    <input 
                        type="text" 
                        id="editClusterDescription" 
                        name="description" 
                        class="form-input"
                    >
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="editClusterActive" name="is_active" class="rounded text-blue-600">
                        <span class="ml-2 text-gray-700">Active</span>
                    </label>
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" onclick="closeEditClusterModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Cluster</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Cluster Confirmation Modal -->
    <div id="deleteClusterModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Delete Cluster</h3>
                <span class="cursor-pointer text-2xl" onclick="closeDeleteClusterModal()">&times;</span>
            </div>
            <form method="POST" id="deleteClusterForm">
                <input type="hidden" name="action" value="delete_cluster">
                <input type="hidden" name="cluster_id" id="deleteClusterId">
                
                <p class="mb-4">Are you sure you want to delete cluster <strong id="deleteClusterName"></strong>?</p>
                <p class="mb-4 text-sm text-gray-600">Note: You can only delete clusters that are not being used by any users or fields.</p>
                
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" onclick="closeDeleteClusterModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Delete Cluster</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Predefined Field Modal -->
    <div id="editFieldModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Edit Predefined Field</h3>
                <span class="cursor-pointer text-2xl" onclick="closeEditFieldModal()">&times;</span>
            </div>
            <form method="POST" id="editFieldForm">
                <input type="hidden" name="action" value="edit_predefined_field">
                <input type="hidden" name="field_id" id="editFieldId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="editFieldName">
                        <i class="fas fa-tag mr-2"></i>Field Name
                    </label>
                    <input 
                        type="text" 
                        id="editFieldName" 
                        name="field_name" 
                        class="form-input" 
                        required
                    >
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="editFieldType">
                        <i class="fas fa-list mr-2"></i>Field Type
                    </label>
                    <select id="editFieldType" name="field_type" class="form-input">
                        <option value="dropdown">Dropdown</option>
                        <option value="input">Input Field</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="editFieldCluster">
                        <i class="fas fa-building mr-2"></i>Cluster (Optional)
                    </label>
                    <select id="editFieldCluster" name="cluster_name" class="form-input">
                        <option value="">Global (All Clusters)</option>
                        <?php foreach ($clusters as $cluster): ?>
                            <option value="<?php echo htmlspecialchars($cluster['cluster_name']); ?>">
                                <?php echo htmlspecialchars($cluster['cluster_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="editFieldActive" name="is_active" class="rounded text-blue-600">
                        <span class="ml-2 text-gray-700">Active</span>
                    </label>
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" onclick="closeEditFieldModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Field</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Predefined Field Confirmation Modal -->
    <div id="deleteFieldModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Delete Predefined Field</h3>
                <span class="cursor-pointer text-2xl" onclick="closeDeleteFieldModal()">&times;</span>
            </div>
            <form method="POST" id="deleteFieldForm">
                <input type="hidden" name="action" value="delete_predefined_field">
                <input type="hidden" name="field_id" id="deleteFieldId">
                
                <p class="mb-4">Are you sure you want to delete field <strong id="deleteFieldName"></strong>?</p>
                
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" onclick="closeDeleteFieldModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Delete Field</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Handle source field selection
        function handleSourceFieldChange() {
            const sourceField = document.getElementById('source_field');
            const fieldName = document.getElementById('field_name');
            const fieldNameLabel = fieldName.previousElementSibling;
            
            if (sourceField.value !== "") {
                fieldName.placeholder = "Enter field name (optional when copying)";
                fieldNameLabel.innerHTML = '<i class="fas fa-tag mr-2"></i>Field Name <span class="text-sm text-gray-500">(optional when copying)</span>';
            } else {
                fieldName.placeholder = "Enter field name";
                fieldNameLabel.innerHTML = '<i class="fas fa-tag mr-2"></i>Field Name';
            }
        }
        
        // Tab switching
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Ensure consistent height after switching tabs
            setTimeout(() => {
                const activeTab = document.getElementById(tabName + '-tab');
                if (activeTab) {
                    activeTab.style.minHeight = '600px';
                }
            }, 10);
        }
        
        // Check URL hash on page load and switch to appropriate tab
        window.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash === '#clusters') {
                // Switch to clusters tab
                const clustersTab = document.querySelector('.tab[onclick="switchTab(\'clusters\')"]');
                if (clustersTab) {
                    clustersTab.click();
                }
            }
            
            // Initialize form state based on source field selection
            handleSourceFieldChange();
        });
        
        // Edit User Function
        function editUser(id, username, email, role, cluster, isActive) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUsername').value = username;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;
            document.getElementById('editCluster').value = cluster;
            document.getElementById('editActive').checked = isActive == 1;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Close Edit Modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Delete User Function
        function deleteUser(id, username) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = username;
            
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Close Delete Modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Edit Cluster Function
        function editCluster(id, clusterName, description, isActive) {
            document.getElementById('editClusterId').value = id;
            document.getElementById('editClusterName').value = clusterName;
            document.getElementById('editClusterDescription').value = description;
            document.getElementById('editClusterActive').checked = isActive == 1;
            
            document.getElementById('editClusterModal').style.display = 'block';
        }
        
        // Close Edit Cluster Modal
        function closeEditClusterModal() {
            document.getElementById('editClusterModal').style.display = 'none';
        }
        
        // Delete Cluster Function
        function deleteCluster(id, clusterName) {
            document.getElementById('deleteClusterId').value = id;
            document.getElementById('deleteClusterName').textContent = clusterName;
            
            document.getElementById('deleteClusterModal').style.display = 'block';
        }
        
        // Close Delete Cluster Modal
        function closeDeleteClusterModal() {
            document.getElementById('deleteClusterModal').style.display = 'none';
        }
        
        // Edit Predefined Field Function
        function editField(id, fieldName, fieldType, clusterName, isActive) {
            document.getElementById('editFieldId').value = id;
            document.getElementById('editFieldName').value = fieldName;
            document.getElementById('editFieldType').value = fieldType;
            document.getElementById('editFieldCluster').value = clusterName;
            document.getElementById('editFieldActive').checked = isActive == 1;
            
            document.getElementById('editFieldModal').style.display = 'block';
        }
        
        // Close Edit Field Modal
        function closeEditFieldModal() {
            document.getElementById('editFieldModal').style.display = 'none';
        }
        
        // Delete Predefined Field Function
        function deleteField(id, fieldName) {
            document.getElementById('deleteFieldId').value = id;
            document.getElementById('deleteFieldName').textContent = fieldName;
            
            document.getElementById('deleteFieldModal').style.display = 'block';
        }
        
        // Close Delete Field Modal
        function closeDeleteFieldModal() {
            document.getElementById('deleteFieldModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const editClusterModal = document.getElementById('editClusterModal');
            const deleteClusterModal = document.getElementById('deleteClusterModal');
            const editFieldModal = document.getElementById('editFieldModal');
            const deleteFieldModal = document.getElementById('deleteFieldModal');
            
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
            
            if (event.target == editClusterModal) {
                editClusterModal.style.display = 'none';
            }
            
            if (event.target == deleteClusterModal) {
                deleteClusterModal.style.display = 'none';
            }
            
            if (event.target == editFieldModal) {
                editFieldModal.style.display = 'none';
            }
            
            if (event.target == deleteFieldModal) {
                deleteFieldModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
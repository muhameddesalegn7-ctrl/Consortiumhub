<?php
session_start();

// Check if user is logged in and is admin
// Only require login; restrict write actions to admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$is_admin = $_SESSION['role'] === 'admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'get_field_config':
        case 'get_fields':
            // Allow all authenticated users to read
            break;

        // Only allow admins for write operations
        case 'toggle_field':
        case 'toggle_type':
        case 'add_value':
        case 'remove_value':
        case 'add_budget_data':
        case 'edit_budget_data':
        case 'delete_budget_data':
        case 'set_acceptance':
        case 'add_budget_preview':
        case 'edit_budget_preview':
        case 'delete_budget_preview':
            if (!$is_admin) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit();
            }
            // ... proceed with action ...
            break;
    }
}
// Include database configuration
define('INCLUDED_SETUP', true);
include 'setup_database.php';

// Handle GET requests for fetching records
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_budget_data':
            // Get record ID
            $id = $_GET['id'] ?? 0;
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'Record ID is required']);
                exit();
            }
            
            // Fetch budget data record
            $query = "SELECT * FROM budget_data WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $record = $result->fetch_assoc();
                echo json_encode(['success' => true, 'record' => $record]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
            }
            exit();
            
        case 'get_budget_preview':
            // Get record ID
            $preview_id = $_GET['id'] ?? 0;
            
            if (empty($preview_id)) {
                echo json_encode(['success' => false, 'message' => 'Record ID is required']);
                exit();
            }
            
            // Fetch budget preview record
            $query = "SELECT * FROM budget_preview WHERE PreviewID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $preview_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $record = $result->fetch_assoc();
                echo json_encode(['success' => true, 'record' => $record]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
            }
            exit();
            
        case 'delete_budget_data':
            // Get record ID
            $id = $_GET['id'] ?? 0;
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'Record ID is required']);
                exit();
            }
            
            // Delete budget data record
            $deleteQuery = "DELETE FROM budget_data WHERE id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Budget data record deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting budget data record: ' . $conn->error]);
            }
            exit();
    }
}

// Handle POST requests for predefined fields management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'get_field_config':
            try {
                $field_name = $_POST['field_name'] ?? '';
                $cluster_name = $_POST['cluster_name'] ?? '';
                
                error_log("get_field_config called with field_name: $field_name, cluster_name: $cluster_name");
                
                if (empty($field_name)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Field name is required']);
                    exit();
                }
                
                // First, try to get field config for specific cluster
                if (!empty($cluster_name)) {
                    error_log("Looking for cluster-specific config for $field_name in cluster $cluster_name");
                    $query = "SELECT * FROM predefined_fields WHERE field_name = ? AND cluster_name = ? AND is_active = 1";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ss", $field_name, $cluster_name);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result && $result->num_rows > 0) {
                        $field = $result->fetch_assoc();
                        error_log("Found cluster-specific config for $field_name: " . json_encode($field));
                        // Add values array for easier frontend processing
                        if (!empty($field['field_values'])) {
                            $field['values_array'] = explode(',', $field['field_values']);
                        } else {
                            $field['values_array'] = [];
                        }
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'field' => $field]);
                        exit();
                    } else {
                        error_log("No cluster-specific config found for $field_name in cluster $cluster_name");
                    }
                }
                
                // If no cluster-specific config found or no cluster specified, get global config
                error_log("Looking for global config for $field_name");
                $query = "SELECT * FROM predefined_fields WHERE field_name = ? AND cluster_name IS NULL AND is_active = 1";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $field_name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $field = $result->fetch_assoc();
                    error_log("Found global config for $field_name: " . json_encode($field));
                    // Add values array for easier frontend processing
                    if (!empty($field['field_values'])) {
                        $field['values_array'] = explode(',', $field['field_values']);
                    } else {
                        $field['values_array'] = [];
                    }
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'field' => $field]);
                } else {
                    error_log("No global config found for $field_name");
                    // Field not configured, return default config
                    $default_field = [
                        'field_name' => $field_name,
                        'field_type' => 'input',
                        'field_values' => '',
                        'is_active' => 1,
                        'cluster_name' => null,
                        'values_array' => []
                    ];
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'field' => $default_field]);
                }
            } catch (Exception $e) {
                error_log("Error in get_field_config: " . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error loading field config: ' . $e->getMessage()]);
            }
            exit();
            
        case 'get_fields':
            try {
                // Get cluster name if provided
                $cluster_name = $_POST['cluster_name'] ?? '';
                
                if ($cluster_name === 'all' || empty($cluster_name)) {
                    // Get all fields (global and cluster-specific)
                    $query = "SELECT * FROM predefined_fields ORDER BY field_name, cluster_name IS NULL, cluster_name";
                    $result = $conn->query($query);
                } else {
                    // Get fields for specific cluster plus global fields
                    $query = "SELECT * FROM predefined_fields WHERE cluster_name = ? OR cluster_name IS NULL ORDER BY field_name, cluster_name IS NULL, cluster_name";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $cluster_name);
                    $stmt->execute();
                    $result = $stmt->get_result();
                }
                
                $fields = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $fields[] = $row;
                    }
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'fields' => $fields]);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error loading fields: ' . $e->getMessage()]);
            }
            exit();
            
        case 'toggle_field':
            try {
                $field_name = $_POST['field_name'] ?? '';
                $is_active = $_POST['is_active'] ?? 0;
                $cluster_name = $_POST['cluster_name'] ?? '';
                
                if (empty($field_name)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Field name is required']);
                    exit();
                }
                
                // Check if field exists for this cluster
                if (!empty($cluster_name)) {
                    $query = "SELECT id FROM predefined_fields WHERE field_name = ? AND cluster_name = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ss", $field_name, $cluster_name);
                } else {
                    $query = "SELECT id FROM predefined_fields WHERE field_name = ? AND cluster_name IS NULL";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $field_name);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing field
                    if (!empty($cluster_name)) {
                        $query = "UPDATE predefined_fields SET is_active = ? WHERE field_name = ? AND cluster_name = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("iss", $is_active, $field_name, $cluster_name);
                    } else {
                        $query = "UPDATE predefined_fields SET is_active = ? WHERE field_name = ? AND cluster_name IS NULL";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("is", $is_active, $field_name);
                    }
                } else {
                    // Create new field for this cluster
                    $query = "INSERT INTO predefined_fields (field_name, field_type, is_active, cluster_name) VALUES (?, 'dropdown', ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sis", $field_name, $is_active, $cluster_name);
                }
                
                if ($stmt->execute()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Field updated successfully']);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error updating field: ' . $conn->error]);
                }
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error toggling field: ' . $e->getMessage()]);
            }
            exit();
            
        case 'toggle_type':
            try {
                $field_name = $_POST['field_name'] ?? '';
                $field_type = $_POST['field_type'] ?? 'dropdown';
                $cluster_name = $_POST['cluster_name'] ?? '';
                
                if (empty($field_name)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Field name is required']);
                    exit();
                }
                
                // Check if field exists for this cluster
                if (!empty($cluster_name)) {
                    $query = "SELECT id FROM predefined_fields WHERE field_name = ? AND cluster_name = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ss", $field_name, $cluster_name);
                } else {
                    $query = "SELECT id FROM predefined_fields WHERE field_name = ? AND cluster_name IS NULL";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $field_name);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing field
                    if (!empty($cluster_name)) {
                        $query = "UPDATE predefined_fields SET field_type = ? WHERE field_name = ? AND cluster_name = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sss", $field_type, $field_name, $cluster_name);
                    } else {
                        $query = "UPDATE predefined_fields SET field_type = ? WHERE field_name = ? AND cluster_name IS NULL";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ss", $field_type, $field_name);
                    }
                } else {
                    // Create new field for this cluster
                    $query = "INSERT INTO predefined_fields (field_name, field_type, is_active, cluster_name) VALUES (?, ?, 1, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sss", $field_name, $field_type, $cluster_name);
                }
                
                if ($stmt->execute()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Field type updated successfully']);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error updating field type: ' . $conn->error]);
                }
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error toggling field type: ' . $e->getMessage()]);
            }
            exit();
            
        case 'add_value':
            try {
                $field_name = $_POST['field_name'] ?? '';
                $value = $_POST['value'] ?? '';
                $cluster_name = $_POST['cluster_name'] ?? '';
                
                if (empty($field_name)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Field name is required']);
                    exit();
                }
                
                // Get current field values
                if (!empty($cluster_name)) {
                    $query = "SELECT id, field_type, field_values FROM predefined_fields WHERE field_name = ? AND cluster_name = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ss", $field_name, $cluster_name);
                } else {
                    $query = "SELECT id, field_type, field_values FROM predefined_fields WHERE field_name = ? AND cluster_name IS NULL";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $field_name);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $field = $result->fetch_assoc();
                    $field_type = $field['field_type'];
                    $current_values = $field['field_values'];
                    
                    if ($field_type === 'dropdown') {
                        // For dropdown fields, add the new value to the comma-separated list
                        $values = $current_values ? explode(',', $current_values) : [];
                        if (!in_array($value, $values)) {
                            $values[] = $value;
                        }
                        $new_values = implode(',', $values);
                        
                        // Update field values
                        if (!empty($cluster_name)) {
                            $query = "UPDATE predefined_fields SET field_values = ? WHERE field_name = ? AND cluster_name = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("sss", $new_values, $field_name, $cluster_name);
                        } else {
                            $query = "UPDATE predefined_fields SET field_values = ? WHERE field_name = ? AND cluster_name IS NULL";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ss", $new_values, $field_name);
                        }
                    } else {
                        // For input fields, set the predefined text
                        if (!empty($cluster_name)) {
                            $query = "UPDATE predefined_fields SET field_values = ? WHERE field_name = ? AND cluster_name = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("sss", $value, $field_name, $cluster_name);
                        } else {
                            $query = "UPDATE predefined_fields SET field_values = ? WHERE field_name = ? AND cluster_name IS NULL";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ss", $value, $field_name);
                        }
                    }
                    
                    if ($stmt->execute()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Value updated successfully']);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Error updating value: ' . $conn->error]);
                    }
                } else {
                    // Field doesn't exist, create it
                    $field_type = 'dropdown'; // Default to dropdown
                    $field_values = $value;
                    
                    $query = "INSERT INTO predefined_fields (field_name, field_type, field_values, is_active, cluster_name) VALUES (?, ?, ?, 1, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssss", $field_name, $field_type, $field_values, $cluster_name);
                    
                    if ($stmt->execute()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Field and value created successfully']);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Error creating field: ' . $conn->error]);
                    }
                }
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error adding value: ' . $e->getMessage()]);
            }
            exit();
            
        case 'remove_value':
            try {
                $field_name = $_POST['field_name'] ?? '';
                $value = $_POST['value'] ?? '';
                $cluster_name = $_POST['cluster_name'] ?? '';
                
                if (empty($field_name)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Field name is required']);
                    exit();
                }
                
                // Get current field values
                if (!empty($cluster_name)) {
                    $query = "SELECT id, field_type, field_values FROM predefined_fields WHERE field_name = ? AND cluster_name = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ss", $field_name, $cluster_name);
                } else {
                    $query = "SELECT id, field_type, field_values FROM predefined_fields WHERE field_name = ? AND cluster_name IS NULL";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $field_name);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $field = $result->fetch_assoc();
                    $field_type = $field['field_type'];
                    $current_values = $field['field_values'];
                    
                    if ($field_type === 'dropdown' && !empty($value)) {
                        // For dropdown fields, remove the specified value from the comma-separated list
                        $values = $current_values ? explode(',', $current_values) : [];
                        $values = array_diff($values, [$value]);
                        $new_values = implode(',', $values);
                        
                        // Update field values
                        if (!empty($cluster_name)) {
                            $query = "UPDATE predefined_fields SET field_values = ? WHERE field_name = ? AND cluster_name = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("sss", $new_values, $field_name, $cluster_name);
                        } else {
                            $query = "UPDATE predefined_fields SET field_values = ? WHERE field_name = ? AND cluster_name IS NULL";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ss", $new_values, $field_name);
                        }
                    } else {
                        // For input fields or when clearing all values, set field_values to NULL or empty
                        if (!empty($cluster_name)) {
                            $query = "UPDATE predefined_fields SET field_values = NULL WHERE field_name = ? AND cluster_name = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ss", $field_name, $cluster_name);
                        } else {
                            $query = "UPDATE predefined_fields SET field_values = NULL WHERE field_name = ? AND cluster_name IS NULL";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("s", $field_name);
                        }
                    }
                    
                    if ($stmt->execute()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Value removed successfully']);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Error removing value: ' . $conn->error]);
                    }
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Field not found']);
                }
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error removing value: ' . $e->getMessage()]);
            }
            exit();
    }
}

// Handle form submissions for budget management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_budget_data':
                // Get form data
                $year = $_POST['year'] ?? '';
                $category_name = $_POST['category_name'] ?? '';
                $period_name = $_POST['period_name'] ?? '';
                $budget = $_POST['budget'] ?? null;
                $cluster = $_POST['cluster'] ?? '';
                $quarter_number = $_POST['quarter_number'] ?? null;
                $start_date = $_POST['start_date'] ?? null;
                $end_date = $_POST['end_date'] ?? null;
                $year2 = $_POST['year2'] ?? date('Y');
                
                // Validate input
                if (empty($year) || empty($category_name) || empty($period_name) || empty($cluster) || empty($start_date) || empty($end_date)) {
                    $_SESSION['error_message'] = "All fields are required";
                    header("Location: admin_budget_management.php");
                    exit();
                }
                
                // Insert new budget data record
                $insertQuery = "INSERT INTO budget_data (year, category_name, period_name, budget, cluster, quarter_number, start_date, end_date, year2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("issssisss", $year, $category_name, $period_name, $budget, $cluster, $quarter_number, $start_date, $end_date, $year2);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Budget data record added successfully";
                } else {
                    $_SESSION['error_message'] = "Error adding budget data record: " . $conn->error;
                }
                
                header("Location: admin_budget_management.php");
                exit();
                
            case 'set_acceptance':
                // Get form data
                $preview_id = $_POST['preview_id'] ?? 0;
                $accepted = $_POST['accepted'] ?? 0;
                $comment = $_POST['comment'] ?? '';
                
                // Validate input
                if (empty($preview_id)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Record ID is required']);
                    exit();
                }
                
                // Update acceptance status in budget_preview table
                if (!empty($comment)) {
                    // If there's a comment, update both acceptance and comment
                    $updateQuery = "UPDATE budget_preview SET ACCEPTANCE = ?, COMMENTS = ? WHERE PreviewID = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("isi", $accepted, $comment, $preview_id);
                } else {
                    // Only update acceptance status
                    $updateQuery = "UPDATE budget_preview SET ACCEPTANCE = ? WHERE PreviewID = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("ii", $accepted, $preview_id);
                }
                
                if ($stmt->execute()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Acceptance status updated successfully']);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error updating acceptance status: ' . $conn->error]);
                }
                exit();
                
            case 'add_budget_preview':
                // Get form data
                $budget_heading = $_POST['budget_heading'] ?? '';
                $category_name = $_POST['category_name'] ?? '';
                $activity = $_POST['activity'] ?? '';
                $partner = $_POST['partner'] ?? '';
                $amount = $_POST['amount'] ?? null;
                $entry_date = $_POST['entry_date'] ?? null;
                $cluster = $_POST['cluster'] ?? '';
                
                // Validate input - handle both dropdown and input field types
                if (empty($cluster)) {
                    $_SESSION['error_message'] = "Cluster is required";
                    header("Location: admin_budget_management.php");
                    exit();
                }
                
                // For budget heading, check if it's configured as an input field
                // If so, we don't require a predefined value, just that it's not empty
                $budget_heading_config = null;
                if (!empty($cluster)) {
                    $query = "SELECT field_type, field_values FROM predefined_fields WHERE field_name = 'BudgetHeading' AND cluster_name = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $cluster);
                } else {
                    $query = "SELECT field_type, field_values FROM predefined_fields WHERE field_name = 'BudgetHeading' AND cluster_name IS NULL";
                    $stmt = $conn->prepare($query);
                }
                
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $budget_heading_config = $result->fetch_assoc();
                    }
                }
                
                // Validate budget heading based on field configuration
                if ($budget_heading_config) {
                    if ($budget_heading_config['field_type'] === 'dropdown') {
                        // For dropdown, check if value is in predefined values
                        if (empty($budget_heading)) {
                            $_SESSION['error_message'] = "Budget heading is required";
                            header("Location: admin_budget_management.php");
                            exit();
                        }
                        // Check if the value is in the allowed list
                        $allowed_values = explode(',', $budget_heading_config['field_values']);
                        if (!in_array($budget_heading, $allowed_values)) {
                            $_SESSION['error_message'] = "Invalid budget heading value";
                            header("Location: admin_budget_management.php");
                            exit();
                        }
                    } else {
                        // For input field, just check that it's not empty
                        if (empty($budget_heading)) {
                            $_SESSION['error_message'] = "Budget heading is required";
                            header("Location: admin_budget_management.php");
                            exit();
                        }
                    }
                } else {
                    // No configuration found, treat as required field
                    if (empty($budget_heading)) {
                        $_SESSION['error_message'] = "Budget heading is required";
                        header("Location: admin_budget_management.php");
                        exit();
                    }
                }
                
                // Validate category name
                if (empty($category_name)) {
                    $_SESSION['error_message'] = "Category name is required";
                    header("Location: admin_budget_management.php");
                    exit();
                }
                
                // Insert new budget preview record
                $insertQuery = "INSERT INTO budget_preview (BudgetHeading, CategoryName, Activity, Partner, Amount, EntryDate, cluster) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("sssssds", $budget_heading, $category_name, $activity, $partner, $amount, $entry_date, $cluster);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Budget preview record added successfully";
                } else {
                    $_SESSION['error_message'] = "Error adding budget preview record: " . $conn->error;
                }
                
                header("Location: admin_budget_management.php");
                exit();
                
            case 'edit_budget_preview':
                // Get form data
                $preview_id = $_POST['preview_id'] ?? 0;
                $budget_heading = $_POST['budget_heading'] ?? '';
                $category_name = $_POST['category_name'] ?? '';
                $activity = $_POST['activity'] ?? '';
                $partner = $_POST['partner'] ?? '';
                $amount = $_POST['amount'] ?? null;
                $entry_date = $_POST['entry_date'] ?? null;
                $cluster = $_POST['cluster'] ?? '';
                
                // Validate input
                if (empty($preview_id)) {
                    $_SESSION['error_message'] = "Record ID is required";
                    header("Location: admin_budget_management.php");
                    exit();
                }
                
                // For budget heading, check if it's configured as an input field
                // If so, we don't require a predefined value, just that it's not empty
                $budget_heading_config = null;
                if (!empty($cluster)) {
                    $query = "SELECT field_type, field_values FROM predefined_fields WHERE field_name = 'BudgetHeading' AND cluster_name = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $cluster);
                } else {
                    $query = "SELECT field_type, field_values FROM predefined_fields WHERE field_name = 'BudgetHeading' AND cluster_name IS NULL";
                    $stmt = $conn->prepare($query);
                }
                
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $budget_heading_config = $result->fetch_assoc();
                    }
                }
                
                // Validate budget heading based on field configuration
                if ($budget_heading_config) {
                    if ($budget_heading_config['field_type'] === 'dropdown') {
                        // For dropdown, check if value is in predefined values
                        if (empty($budget_heading)) {
                            $_SESSION['error_message'] = "Budget heading is required";
                            header("Location: admin_budget_management.php");
                            exit();
                        }
                        // Check if the value is in the allowed list
                        $allowed_values = explode(',', $budget_heading_config['field_values']);
                        if (!in_array($budget_heading, $allowed_values)) {
                            $_SESSION['error_message'] = "Invalid budget heading value";
                            header("Location: admin_budget_management.php");
                            exit();
                        }
                    } else {
                        // For input field, just check that it's not empty
                        if (empty($budget_heading)) {
                            $_SESSION['error_message'] = "Budget heading is required";
                            header("Location: admin_budget_management.php");
                            exit();
                        }
                    }
                } else {
                    // No configuration found, treat as required field
                    if (empty($budget_heading)) {
                        $_SESSION['error_message'] = "Budget heading is required";
                        header("Location: admin_budget_management.php");
                        exit();
                    }
                }
                
                // Validate category name and cluster
                if (empty($category_name) || empty($cluster)) {
                    $_SESSION['error_message'] = "Category name and cluster are required";
                    header("Location: admin_budget_management.php");
                    exit();
                }
                
                // Update budget preview record
                $updateQuery = "UPDATE budget_preview SET BudgetHeading = ?, CategoryName = ?, Activity = ?, Partner = ?, Amount = ?, EntryDate = ?, cluster = ? WHERE PreviewID = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("sssssdsi", $budget_heading, $category_name, $activity, $partner, $amount, $entry_date, $cluster, $preview_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Budget preview record updated successfully";
                } else {
                    $_SESSION['error_message'] = "Error updating budget preview record: " . $conn->error;
                }
                
                header("Location: admin_budget_management.php");
                exit();
                
            case 'delete_budget_preview':
                // Get record ID
                $preview_id = $_POST['preview_id'] ?? 0;
                
                // Validate input
                if (empty($preview_id)) {
                    $_SESSION['error_message'] = "Record ID is required";
                    header("Location: admin_budget_management.php");
                    exit();
                }
                
                // Delete budget preview record
                $deleteQuery = "DELETE FROM budget_preview WHERE PreviewID = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("i", $preview_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Budget preview record deleted successfully";
                } else {
                    $_SESSION['error_message'] = "Error deleting budget preview record: " . $conn->error;
                }
                
                header("Location: admin_budget_management.php");
                exit();
                
            case 'delete_budget_data':
                // Get record ID
                $id = $_POST['id'] ?? 0;
                
                // Validate input
                if (empty($id)) {
                    $_SESSION['error_message'] = "Record ID is required";
                    header("Location: admin_budget_management.php");
                    exit();
                }
                
                // Delete budget data record
                $deleteQuery = "DELETE FROM budget_data WHERE id = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Budget data record deleted successfully";
                } else {
                    $_SESSION['error_message'] = "Error deleting budget data record: " . $conn->error;
                }
                
                header("Location: admin_budget_management.php");
                exit();
        }
    }
}

// If no action was specified, redirect to the admin page
header("Location: admin_budget_management.php");
exit();
?>
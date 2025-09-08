<?php
// Database setup and connection handler
// When INCLUDED_SETUP is defined, acts as connection provider for other files
// When accessed directly, performs full database setup

// Detect execution mode
$included = defined('INCLUDED_SETUP');

if (!$included) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Setup</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            .success { color: #28a745; }
            .error { color: #dc3545; }
            h1 { color: #007bff; }
            ul { background: #e9ecef; padding: 20px; border-radius: 5px; }
            li { margin: 10px 0; }
            a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
            a:hover { background: #0056b3; }
        </style>
    </head>
    <body>
    <div class="container">
    <?php
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password
$dbname = "consortium_hub";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    if (!$included) {
        die("<div class='error'>Connection failed: " . $conn->connect_error . "</div></div></body></html>");
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    if (!$included) echo "<p class='success'>Database '$dbname' created/verified successfully</p>";
} else {
    if (!$included) echo "<p class='error'>Error creating database: " . $conn->error . "</p>";
}

// Select database
$conn->select_db($dbname);

// Create budget_preview table with the correct schema
$sql = "CREATE TABLE IF NOT EXISTS budget_preview (
    PreviewID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    BudgetHeading VARCHAR(255) DEFAULT NULL,
    Outcome VARCHAR(255) DEFAULT NULL,
    Activity VARCHAR(255) DEFAULT NULL,
    BudgetLine VARCHAR(255) DEFAULT NULL,
    Description TEXT DEFAULT NULL,
    Partner VARCHAR(255) DEFAULT NULL,
    EntryDate DATE DEFAULT NULL,
    Amount DECIMAL(18,2) DEFAULT NULL,
    PVNumber VARCHAR(50) DEFAULT NULL,
    Documents VARCHAR(255) DEFAULT NULL,
    DocumentPaths TEXT DEFAULT NULL,
    DocumentTypes VARCHAR(500) DEFAULT NULL,
    OriginalNames VARCHAR(500) DEFAULT NULL,
    QuarterPeriod VARCHAR(10) DEFAULT NULL,
    CategoryName VARCHAR(255) DEFAULT NULL,
    OriginalBudget DECIMAL(18,2) DEFAULT NULL,
    RemainingBudget DECIMAL(18,2) DEFAULT NULL,
    ActualSpent DECIMAL(18,2) DEFAULT NULL,
    VariancePercentage DECIMAL(5,2) DEFAULT NULL,
    CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cluster VARCHAR(255) DEFAULT NULL,
    budget_id INT(11) DEFAULT NULL,
    ForecastAmount DECIMAL(18,2) DEFAULT NULL,
    KEY budget_id (budget_id)
)";

if ($conn->query($sql) === TRUE) {
    if (!$included) echo "<p class='success'>Table 'budget_preview' created/verified successfully</p>";
} else {
    if (!$included) echo "<p class='error'>Error creating table 'budget_preview': " . $conn->error . "</p>";
}

// Create budget_data table with the correct schema
$sql = "CREATE TABLE IF NOT EXISTS budget_data (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    year INT(11) NOT NULL,
    category_name VARCHAR(255) NOT NULL,
    period_name VARCHAR(50) NOT NULL,
    budget DECIMAL(10,2) DEFAULT NULL,
    actual DECIMAL(10,2) DEFAULT NULL,
    forecast DECIMAL(10,2) DEFAULT NULL,
    actual_plus_forecast DECIMAL(10,2) DEFAULT NULL,
    variance_percentage DECIMAL(5,2) DEFAULT NULL,
    quarter_number TINYINT(4) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    certified ENUM('certified','uncertified') DEFAULT 'uncertified',
    cluster VARCHAR(100) DEFAULT NULL,
    year2 INT(11) DEFAULT NULL
)";

if ($conn->query($sql) === TRUE) {
    if (!$included) echo "<p class='success'>Table 'budget_data' created/verified successfully</p>";
} else {
    if (!$included) echo "<p class='error'>Error creating table 'budget_data': " . $conn->error . "</p>";
}

// Create users table with the correct schema
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','finance_officer') NOT NULL DEFAULT 'finance_officer',
    cluster_name VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    if (!$included) echo "<p class='success'>Table 'users' created/verified successfully</p>";
} else {
    if (!$included) echo "<p class='error'>Error creating table 'users': " . $conn->error . "</p>";
}

// Create clusters table
$sql = "CREATE TABLE IF NOT EXISTS clusters (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    cluster_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    if (!$included) echo "<p class='success'>Table 'clusters' created/verified successfully</p>";
} else {
    if (!$included) echo "<p class='error'>Error creating table 'clusters': " . $conn->error . "</p>";
}

// Create predefined_fields table with cluster_name column
$sql = "CREATE TABLE IF NOT EXISTS predefined_fields (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    field_name VARCHAR(100) NOT NULL,
    field_type ENUM('dropdown', 'input') NOT NULL,
    field_values TEXT,
    is_active TINYINT(1) DEFAULT 1,
    cluster_name VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_field_cluster (field_name, cluster_name)
)";

if ($conn->query($sql) === TRUE) {
    if (!$included) echo "<p class='success'>Table 'predefined_fields' created/verified successfully</p>";
} else {
    if (!$included) echo "<p class='error'>Error creating table 'predefined_fields': " . $conn->error . "</p>";
}

// Check if cluster_name column exists in predefined_fields table, add it if missing
$checkColumn = "SHOW COLUMNS FROM predefined_fields LIKE 'cluster_name'";
$result = $conn->query($checkColumn);
if ($result->num_rows == 0) {
    $addColumn = "ALTER TABLE predefined_fields ADD COLUMN cluster_name VARCHAR(100) DEFAULT NULL, ADD UNIQUE KEY unique_field_cluster (field_name, cluster_name)";
    if ($conn->query($addColumn) === TRUE) {
        if (!$included) echo "<p class='success'>Added cluster_name column to predefined_fields table</p>";
    } else {
        if (!$included) echo "<p class='error'>Error adding cluster_name column: " . $conn->error . "</p>";
    }
}

// Create certificates_simple table for storing only certificate paths and metadata
$sql = "CREATE TABLE IF NOT EXISTS certificates_simple (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cluster_name VARCHAR(100) NOT NULL,
    year INT(4) NOT NULL,
    certificate_path VARCHAR(500) NOT NULL,
    uploaded_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    uploaded_by VARCHAR(255) DEFAULT 'admin'
)";

if ($conn->query($sql) === TRUE) {
    if (!$included) echo "<p class='success'>Table 'certificates_simple' created/verified successfully</p>";
} else {
    if (!$included) echo "<p class='error'>Error creating table 'certificates_simple': " . $conn->error . "</p>";
}

// Insert default admin user if not exists
$adminEmail = "admin@gmail.com";
$adminPassword = "1234"; // Plain text password as requested
$checkUser = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($checkUser);
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $insertAdmin = "INSERT INTO users (username, email, password, role, cluster_name) VALUES (?, ?, ?, 'admin', NULL)";
    $stmt = $conn->prepare($insertAdmin);
    $adminUsername = "admin";
    $stmt->bind_param("sss", $adminUsername, $adminEmail, $adminPassword);
    
    if ($stmt->execute()) {
        if (!$included) echo "<p class='success'>Default admin user created successfully</p>";
    } else {
        if (!$included) echo "<p class='error'>Error creating default admin user: " . $conn->error . "</p>";
    }
} else {
    if (!$included) echo "<p class='success'>Default admin user already exists</p>";
}

// Insert default finance officer user for Woldiya cluster if not exists
$financeEmail = "finance@woldiya.com";
$financePassword = "1234"; // Plain text password as requested
$checkFinanceUser = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($checkFinanceUser);
$stmt->bind_param("s", $financeEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $insertFinance = "INSERT INTO users (username, email, password, role, cluster_name, is_active) VALUES (?, ?, ?, 'finance_officer', ?, 1)";
    $stmt = $conn->prepare($insertFinance);
    $financeUsername = "woldiya_finance";
    $clusterName = "Woldiya"; // Define the variable here
    $stmt->bind_param("ssss", $financeUsername, $financeEmail, $financePassword, $clusterName);
    
    if ($stmt->execute()) {
        if (!$included) echo "<p class='success'>Default finance officer user for Woldiya cluster created successfully</p>";
    } else {
        if (!$included) echo "<p class='error'>Error creating default finance officer user: " . $conn->error . "</p>";
    }
} else {
    // Update existing finance officer user to ensure all fields are correct
    $updateFinance = "UPDATE users SET is_active = 1, password = ?, cluster_name = ? WHERE email = ?";
    $stmt = $conn->prepare($updateFinance);
    $clusterName = "Woldiya"; // Define the variable here
    $stmt->bind_param("sss", $financePassword, $clusterName, $financeEmail);
    
    if ($stmt->execute()) {
        if (!$included) echo "<p class='success'>Default finance officer user updated successfully</p>";
    } else {
        if (!$included) echo "<p class='error'>Error updating default finance officer user: " . $conn->error . "</p>";
    }
}

// Update existing predefined_fields to have cluster_name = 'Woldiya' where it's NULL
$updateFields = "UPDATE predefined_fields SET cluster_name = 'Woldiya' WHERE cluster_name IS NULL";
if ($conn->query($updateFields) === TRUE) {
    if (!$included) echo "<p class='success'>Updated existing predefined fields with cluster 'Woldiya'</p>";
} else {
    if (!$included) echo "<p class='error'>Error updating predefined fields: " . $conn->error . "</p>";
}

if (!$included) {
    ?>
    </div>
    <div style="text-align: center; margin-top: 20px;">
        <a href="admin_predefined_fields.php">Go to Admin Page</a>
        <a href="financial_report_section.php">Go to Financial Report</a>
        <a href="login.php">Go to Login</a>
    </div>
    </body>
    </html>
    <?php
}
?>
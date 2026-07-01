<?php

/**
 * Secure Database Connection
 * Environment-based configuration with error handling
 *
 * On InfinityFree (or any host without real OS env vars), create a
 * config.env.php file next to this one (NOT committed to git) that
 * calls putenv() for DB_HOST / DB_USER / DB_PASS / DB_NAME / ENVIRONMENT.
 * Locally on XAMPP, just omit that file and the defaults below apply.
 */

// Load production/deployment overrides if present (gitignored file)
if (file_exists(__DIR__ . '/config.env.php')) {
    require_once __DIR__ . '/config.env.php';
    error_log("DEBUG: config.env.php loaded. DB_HOST=[" . getenv('DB_HOST') . "] DB_NAME=[" . getenv('DB_NAME') . "]");
} else {
    error_log("DEBUG: config.env.php NOT FOUND at " . __DIR__);
}

// Use environment variables or fallback to defaults (for development)
$servername = getenv('DB_HOST') ?: "127.0.0.1";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "bookerpos_final";

// Enable error reporting only in development
$isDevelopment = getenv('ENVIRONMENT') !== 'production';
if ($isDevelopment) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/errors.log');
}

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0755, true);
}

// Establish connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    error_log("Database Connection Error: " . mysqli_connect_error());

    if ($isDevelopment) {
        die("Connection failed: " . mysqli_connect_error());
    } else {
        die("Database connection error. Please try again later.");
    }
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Enable exceptions for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * Prepare statement wrapper with error handling
 */
function prepareStatement($query)
{
    global $conn;
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Statement Prepare Error: " . $conn->error);
        throw new Exception("Database error occurred");
    }
    return $stmt;
}

/**
 * Execute query safely
 */
function executeQuery($query, $params = [], $types = '')
{
    $stmt = prepareStatement($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        error_log("Query Execution Error: " . $stmt->error);
        throw new Exception("Database error occurred");
    }
    return $stmt->get_result();
}

/**
 * Schema setup / migrations — only run once per deploy, not on every
 * request. We check for a marker file so repeated page loads (especially
 * on throttled shared hosting like InfinityFree) skip the SHOW COLUMNS /
 * CREATE TABLE overhead entirely after the first successful run.
 */
$schemaMarker = __DIR__ . '/logs/.schema_initialized';

if (!file_exists($schemaMarker)) {

    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL CHECK (role IN ('Admin', 'Cashier')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            active BOOLEAN DEFAULT TRUE,
            INDEX(username),
            INDEX(role)
        )
    ");

    // Migrate existing users table: add missing columns if they don't exist
    $checkIdColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'id'");
    if ($checkIdColumn->num_rows == 0) {
        @$conn->query("ALTER TABLE users ADD COLUMN id INT AUTO_INCREMENT UNIQUE FIRST");
    }

    $checkActiveColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'active'");
    if ($checkActiveColumn->num_rows == 0) {
        @$conn->query("ALTER TABLE users ADD COLUMN active BOOLEAN DEFAULT TRUE");
    }

    $checkCreatedColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    if ($checkCreatedColumn->num_rows == 0) {
        @$conn->query("ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    }

    $checkUpdatedColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'updated_at'");
    if ($checkUpdatedColumn->num_rows == 0) {
        @$conn->query("ALTER TABLE users ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS audit_logs (
            logID INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            action VARCHAR(255),
            details TEXT,
            ip_address VARCHAR(45),
            timestamp DATETIME,
            INDEX(username, timestamp)
        )
    ");

    /**
     * Seed default admin account if it doesn't exist.
     * Restricted to development only — on production (InfinityFree),
     * create the admin account once manually via phpMyAdmin instead,
     * so a known admin/admin123 login is never live on the internet.
     */
    if ($isDevelopment) {
        $checkAdmin = $conn->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
        $adminResult = $checkAdmin->fetch_assoc();

        if ($adminResult['count'] == 0) {
            $defaultUsername = 'admin';
            $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
            $defaultRole = 'Admin';

            $seedQuery = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($seedQuery);

            if ($stmt) {
                $stmt->bind_param('sss', $defaultUsername, $defaultPassword, $defaultRole);
                if ($stmt->execute()) {
                    error_log("Default admin account created successfully (development)");
                } else {
                    error_log("Error creating default admin account: " . $stmt->error);
                }
                $stmt->close();
            }
        }
    }

    // Mark schema as initialized so subsequent requests skip all of the above
    @file_put_contents($schemaMarker, date('Y-m-d H:i:s'));
}
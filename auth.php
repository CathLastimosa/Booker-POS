<?php

/**
 * Authentication and Authorization Helper
 * Centralized security functions for user access control
 */

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['username']) && isset($_SESSION['role']);
}

// Redirect to login if not logged in
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

// Check if user has specific role
function hasRole($requiredRole)
{
    if (!isLoggedIn()) {
        return false;
    }

    $userRole = $_SESSION['role'] ?? '';

    if (is_array($requiredRole)) {
        return in_array($userRole, $requiredRole);
    }

    return $userRole === $requiredRole;
}

// Require specific role or redirect
function requireRole($requiredRole)
{
    requireLogin();

    if (!hasRole($requiredRole)) {
        header('Location: login.php?error=unauthorized');
        exit();
    }
}

// Get current logged-in username safely
function getCurrentUser()
{
    return isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : null;
}

// Get current user's role
function getCurrentUserRole()
{
    return isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : null;
}

// Generate CSRF token
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Log activity for audit trail
function logActivity($action, $details = '', $userid = null)
{
    global $conn;

    if (!$userid && isset($_SESSION['username'])) {
        $userid = $_SESSION['username'];
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    // Create audit log table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS audit_logs (
            logID INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            action VARCHAR(255),
            details TEXT,
            ip_address VARCHAR(45),
            timestamp DATETIME,
            INDEX(username, timestamp)
        )
    ";

    $conn->query($createTableSQL);

    $stmt = $conn->prepare("INSERT INTO audit_logs (username, action, details, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssss", $userid, $action, $details, $ip_address, $timestamp);
        $stmt->execute();
        $stmt->close();
    }
}

// Sanitize user input
function sanitizeInput($input)
{
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

// Validate email format
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Check password strength
function isStrongPassword($password)
{
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

// Rate limiting helper
function checkRateLimit($identifier, $limit = 5, $window = 300)
{
    $key = 'rate_limit_' . md5($identifier);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }

    $now = time();
    $_SESSION[$key] = array_filter($_SESSION[$key], function ($timestamp) use ($now, $window) {
        return $now - $timestamp < $window;
    });

    if (count($_SESSION[$key]) >= $limit) {
        return false;
    }

    $_SESSION[$key][] = $now;
    return true;
}

// Validate file upload
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5000000)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }

    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Verify it's actually an image
    $check = getimagesize($file['tmp_name']);
    if (!$check) {
        return ['success' => false, 'message' => 'File is not a valid image'];
    }

    return ['success' => true, 'message' => 'File valid'];
}

// Session timeout (30 minutes)
function checkSessionTimeout()
{
    $timeout = 1800; // 30 minutes

    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }

    if (time() - $_SESSION['last_activity'] > $timeout) {
        session_destroy();
        header('Location: login.php?error=session_expired');
        exit();
    }

    $_SESSION['last_activity'] = time();
}

<?php
session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

// Check session timeout
checkSessionTimeout();

// Require Admin role
requireRole('Admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = sanitizeInput($_POST['role'] ?? '');

        // Validation
        if (empty($username) || empty($password) || empty($role)) {
            $error = "All fields are required.";
        } elseif (strlen($username) < 3) {
            $error = "Username must be at least 3 characters.";
        } elseif (!in_array($role, ['Admin', 'Cashier'])) {
            $error = "Invalid role selected.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (!isStrongPassword($password)) {
            $error = "Password must be at least 8 characters with uppercase, lowercase, and numbers.";
        } else {
            try {
                // Check if username exists
                $checkStmt = prepareStatement("SELECT COUNT(*) as count FROM users WHERE username = ?");
                $checkStmt->bind_param("s", $username);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $checkRow = $checkResult->fetch_assoc();

                if ($checkRow['count'] > 0) {
                    $error = "Username already exists.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = prepareStatement("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $hashedPassword, $role);

                    if ($stmt->execute()) {
                        $message = "User created successfully!";
                        logActivity('USER_CREATED', "User: $username, Role: $role", getCurrentUser());
                        // Redirect after 2 seconds
                        header("Refresh: 2; url=users-menu.php");
                    } else {
                        $error = "Error creating user. Please try again.";
                        error_log("Add User Error: " . $stmt->error);
                    }
                    $stmt->close();
                }
                $checkStmt->close();
            } catch (Exception $e) {
                $error = "Database error occurred.";
                error_log($e->getMessage());
            }
        }
    }
}

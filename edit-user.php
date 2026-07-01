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

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $originalUsername = sanitizeInput($_POST['original_username'] ?? '');
        $newUsername = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitizeInput($_POST['role'] ?? '');

        try {
            if (empty($newUsername) || empty($role)) {
                $error = "Username and role are required.";
            } elseif (!in_array($role, ['Admin', 'Cashier'])) {
                $error = "Invalid role selected.";
            } else {
                // Check if new username already exists (if changed)
                if ($newUsername !== $originalUsername) {
                    $checkStmt = prepareStatement("SELECT COUNT(*) as count FROM users WHERE username = ?");
                    $checkStmt->bind_param("s", $newUsername);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $checkRow = $checkResult->fetch_assoc();

                    if ($checkRow['count'] > 0) {
                        $error = "Username already exists.";
                    }
                    $checkStmt->close();
                }

                if (empty($error)) {
                    if (!empty($password)) {
                        if (!isStrongPassword($password)) {
                            $error = "Password must be at least 8 characters with uppercase, lowercase, and numbers.";
                        } else {
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = prepareStatement("UPDATE users SET username=?, password=?, role=? WHERE username=?");
                            $stmt->bind_param("ssss", $newUsername, $hashedPassword, $role, $originalUsername);
                        }
                    } else {
                        $stmt = prepareStatement("UPDATE users SET username=?, role=? WHERE username=?");
                        $stmt->bind_param("sss", $newUsername, $role, $originalUsername);
                    }

                    if (empty($error) && $stmt->execute()) {
                        $message = "User updated successfully!";
                        logActivity('USER_UPDATED', "Original: $originalUsername, New: $newUsername, Role: $role", getCurrentUser());
                        header("Refresh: 2; url=users-menu.php");
                    } elseif (empty($error)) {
                        $error = "Error updating user. Please try again.";
                        error_log("Edit User Error: " . $stmt->error);
                    }
                    if (isset($stmt)) {
                        $stmt->close();
                    }
                }
            }
        } catch (Exception $e) {
            $error = "Database error occurred.";
            error_log($e->getMessage());
        }
    }
} else {
    $error = "Invalid request method.";
}

<?php
session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

// Check session timeout
checkSessionTimeout();

// Require Admin role
requireRole('Admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Security token invalid.']);
        exit;
    }

    try {
        $username = sanitizeInput($_POST['username']);

        // Prevent deleting the last admin
        if ($_SESSION['role'] === 'Admin') {
            $adminCount = prepareStatement("SELECT COUNT(*) as count FROM users WHERE role = 'Admin'");
            $adminCount->execute();
            $result = $adminCount->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] <= 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete the last admin user.']);
                exit;
            }
            $adminCount->close();
        }

        $stmt = prepareStatement("DELETE FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);

        if ($stmt->execute()) {
            logActivity('USER_DELETED', "Username: $username", getCurrentUser());
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error deleting user.']);
        }
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        error_log($e->getMessage());
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

<?php
session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

// Check session timeout
checkSessionTimeout();

// Require Admin role
requireRole('Admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['productID'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Security token invalid.']);
        exit;
    }

    try {
        $productID = intval($_POST['productID']);

        if ($productID <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
            exit;
        }

        // Check if product exists
        $checkStmt = prepareStatement("SELECT productName FROM products WHERE productID = ?");
        $checkStmt->bind_param("i", $productID);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            $checkStmt->close();
            exit;
        }

        $product = $result->fetch_assoc();
        $productName = $product['productName'];
        $checkStmt->close();

        // Delete product
        $stmt = prepareStatement("DELETE FROM products WHERE productID = ?");
        $stmt->bind_param("i", $productID);

        if ($stmt->execute()) {
            logActivity('PRODUCT_DELETED', "Product ID: $productID, Name: $productName", getCurrentUser());
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error deleting product.']);
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

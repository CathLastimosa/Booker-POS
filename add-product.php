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
        try {
            $productName = sanitizeInput($_POST['name'] ?? '');
            $productCategory = sanitizeInput($_POST['category'] ?? '');
            $productDesc = sanitizeInput($_POST['description'] ?? '');
            $productPrice = floatval($_POST['price'] ?? 0);
            $productStock = intval($_POST['stock'] ?? 0);

            // Validation
            if (empty($productName) || empty($productCategory) || empty($productDesc) || $productPrice <= 0 || $productStock < 0) {
                $error = "Please fill in all fields with valid values.";
            } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = "Image upload failed. Please try again.";
            } else {
                // Validate file upload
                $fileValidation = validateFileUpload($_FILES['image']);
                if (!$fileValidation['success']) {
                    $error = $fileValidation['message'];
                } else {
                    // Generate unique product ID
                    $productID = generateUniqueProductID($conn);

                    // Create secure filename
                    $upload_dir = "uploads/products/";
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $new_filename = uniqid('product_') . '.' . $file_ext;
                    $target_file = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                        // Insert product into database
                        $stmt = prepareStatement("INSERT INTO products (productID, productName, productDesc, productPrice, productStock, ProductCategory, image) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("issdiss", $productID, $productName, $productDesc, $productPrice, $productStock, $productCategory, $target_file);

                        if ($stmt->execute()) {
                            $message = "Product added successfully!";
                            logActivity('PRODUCT_CREATED', "Product ID: $productID, Name: $productName", getCurrentUser());
                            header("Refresh: 2; url=products-menu.php");
                        } else {
                            $error = "Error adding product. Please try again.";
                            unlink($target_file); // Delete uploaded file on DB error
                            error_log("Add Product Error: " . $stmt->error);
                        }
                        $stmt->close();
                    } else {
                        $error = "Error uploading image. Please try again.";
                    }
                }
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again.";
            error_log($e->getMessage());
        }
    }
}

// Helper function to generate unique product ID
function generateUniqueProductID($conn)
{
    do {
        $randomId = rand(1000000, 9999999);
        $stmt = prepareStatement("SELECT COUNT(*) as count FROM products WHERE productID = ?");
        $stmt->bind_param('i', $randomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'];
        $stmt->close();
    } while ($count > 0);

    return $randomId;
}

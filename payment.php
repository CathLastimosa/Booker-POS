<?php
session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

// Check session timeout
checkSessionTimeout();

// Require login - Cashier or Admin
requireLogin();
if (!hasRole(['Cashier', 'Admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Security token invalid']);
            exit();
        }

        $invoice = sanitizeInput($_POST['invoiceNumber'] ?? '');
        $total = floatval($_POST['totalPrice'] ?? 0);
        $items = json_decode($_POST['itemsData'], true);
        $date = date("Y-m-d");
        $username = getCurrentUser();

        // Validation
        if (empty($invoice) || $total <= 0 || !is_array($items) || empty($items)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
            exit();
        }

        // Calculate total quantity
        $qty = 0;
        foreach ($items as $i) {
            $qty += intval($i['quantity'] ?? 0);
        }

        if ($qty <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid item quantities']);
            exit();
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into sales table
            $stmt1 = prepareStatement("INSERT INTO sales (itemQuantity, totalSales, salesDate, invoiceNumber, username) 
                                      VALUES (?, ?, ?, ?, ?)");
            $stmt1->bind_param("idsss", $qty, $total, $date, $invoice, $username);

            if (!$stmt1->execute()) {
                throw new Exception("Error saving to sales table");
            }
            $stmt1->close();

            $saleID = $conn->insert_id;

            // Insert sales items
            foreach ($items as $i) {
                $pid = intval($i['productID'] ?? 0);
                $q = intval($i['quantity'] ?? 0);
                $p = floatval($i['price'] ?? 0);

                if ($pid <= 0 || $q <= 0 || $p <= 0) {
                    throw new Exception("Invalid item data");
                }

                $stmt2 = prepareStatement("INSERT INTO salesItems (salesID, productID, quantity, price) 
                                          VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("iiid", $saleID, $pid, $q, $p);

                if (!$stmt2->execute()) {
                    throw new Exception("Error saving item to salesItems");
                }
                $stmt2->close();
            }

            // Commit transaction
            $conn->commit();

            // Store receipt in session
            if (!isset($_SESSION['receipts'])) {
                $_SESSION['receipts'] = [];
            }
            $_SESSION['receipts'][$invoice] = [
                'items' => $items,
                'total' => $total,
                'date' => $date,
                'saleID' => $saleID
            ];

            logActivity('PAYMENT_PROCESSED', "Invoice: $invoice, Total: $total, Items: $qty", $username);

            echo json_encode(['success' => true, 'message' => 'Payment Successful', 'saleID' => $saleID]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Payment processing error']);
        error_log("Payment Error: " . $e->getMessage());
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

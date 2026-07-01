<?php
require_once 'dbConnection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierID = $_POST['supplierList'];
    $invoiceNo = $_POST['invoiceNo'];
    $selectedProducts = $_POST['selectedProducts'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if (empty($selectedProducts)) {
        echo "No products selected.";
        exit;
    }

    function generateRandomPurchasedID($conn)
    {
        $count = "";
        do {
            $purchasedID = rand(1000000, 9999999);
            $stmt = $conn->prepare("SELECT COUNT(*) FROM purchases WHERE purchaseID = ?");
            $stmt->bind_param('i', $purchasedID);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
        } while ($count > 0);

        return $purchasedID;
    }

    $purchasedID = generateRandomPurchasedID($conn);

    $conn->begin_transaction();

    try {
        // Insert purchase record
        $stmt = $conn->prepare("INSERT INTO purchases (purchaseID, supplierID, invoiceNo) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $purchasedID, $supplierID, $invoiceNo); // invoiceNo is string? change if needed
        $stmt->execute();
        $stmt->close();

        // Prepare statements for stock checking and updating, and inserting purchase items
        $stmtStock = $conn->prepare("SELECT productStock FROM products WHERE productID = ?");
        $stmtUpdateStock = $conn->prepare("UPDATE products SET productStock = ? WHERE productID = ?");
        $stmtItem = $conn->prepare("INSERT INTO purchaseItems (purchaseID, productID, quantity) VALUES (?, ?, ?)");

        foreach ($selectedProducts as $productID) {
            $quantity = isset($quantities[$productID]) ? (int)$quantities[$productID] : 1;

            // Check current stock
            $stmtStock->bind_param("i", $productID);
            $stmtStock->execute();
            $result = $stmtStock->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Product ID $productID not found.");
            }
            $row = $result->fetch_assoc();
            $currentStock = (int)$row['productStock'];

            if ($quantity > $currentStock) {
                throw new Exception("Insufficient stock for product ID $productID.");
            }

            // Update stock
            $newStock = $currentStock - $quantity;
            $stmtUpdateStock->bind_param("ii", $newStock, $productID);
            $stmtUpdateStock->execute();

            // Insert into purchaseItems
            $stmtItem->bind_param("iii", $purchasedID, $productID, $quantity);
            $stmtItem->execute();
        }

        // Close statements
        $stmtStock->close();
        $stmtUpdateStock->close();
        $stmtItem->close();

        $conn->commit();
        header('Location: purchases-menu.php');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error processing purchase: " . $e->getMessage();
        exit;
    }
}
?>

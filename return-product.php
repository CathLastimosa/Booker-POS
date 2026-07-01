<?php
require_once 'dbConnection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reasons = $_POST['reason'];
    $invoiceNo = $_POST['invoiceNo'];
    $selectedProducts = $_POST['selectedProducts'];
    $quantities = $_POST['quantity'];

    if (empty($invoiceNo) || empty($selectedProducts)) {
        echo "Invoice or selected products are missing.";
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

        // Get purchaseID from invoiceNo
        $stmt = $conn->prepare("SELECT purchaseID FROM purchases WHERE invoiceNo = ?");
        $stmt->bind_param("s", $invoiceNo);
        $stmt->execute();
        $stmt->bind_result($purchaseID);
        $stmt->fetch();
        $stmt->close();

        foreach ($selectedProducts as $productID) {
            $productID = (int)$productID;
            $returnQty = (int)($quantities[$productID] ?? 0);
            $reason = trim($reasons[$productID] ?? '');

            // Get original purchase quantity
            $stmtCheck = $conn->prepare("SELECT quantity FROM purchaseItems WHERE purchaseID = ? AND productID = ?");
            $stmtCheck->bind_param("ii", $purchaseID, $productID);
            $stmtCheck->execute();
            $stmtCheck->bind_result($originalQty);
            $stmtCheck->fetch();
            $stmtCheck->close();

            $stmtUpdate = $conn->prepare("UPDATE products SET productStock = productStock + ? WHERE productID = ?");
            $stmtUpdate->bind_param("ii", $returnQty, $productID);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            $stmtReturn = $conn->prepare("INSERT INTO returns (purchaseID, productID, returnQuantity, reason) VALUES (?, ?, ?, ?)");
            $stmtReturn->bind_param("iiis", $purchaseID, $productID, $returnQty, $reason);
            $stmtReturn->execute();
            $stmtReturn->close();

            $stmtUpdatePurchaseItem = $conn->prepare("UPDATE purchaseItems SET quantity = quantity - ? WHERE purchaseID = ? AND productID = ?");
            $stmtUpdatePurchaseItem->bind_param("iii", $returnQty, $purchaseID, $productID);
            $stmtUpdatePurchaseItem->execute();
            $stmtUpdatePurchaseItem->close();

        }

        $conn->commit();
        header("Location: returns-menu.php?success=1");
        exit; 
}
?>

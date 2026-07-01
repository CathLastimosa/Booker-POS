<?php
require_once 'dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supplierID'])) {
    $supplierID = intval($_POST['supplierID']);

    $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplierID = ?");
    $stmt->bind_param("i", $supplierID);

    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "Error deleting supplier.";
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo "Invalid request.";
}

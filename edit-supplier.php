<?php
require_once 'dbConnection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $supplierID = intval($_POST['supplierID']);
    $supplierName = trim($_POST['supplierName']);
    $Contact = trim($_POST['Contact']);
    $Address = trim($_POST['Address']);

    $stmt = $conn->prepare("UPDATE suppliers SET supplierName = ?, Contact = ?, Address = ? WHERE supplierID = ?");
    $stmt->bind_param("sssi", $supplierName, $Contact, $Address, $supplierID);

    if ($stmt->execute()) {
        header("Location: suppliers-menu.php");
        exit();
    } else {
        echo "Error updating product: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

$conn->close();
?>

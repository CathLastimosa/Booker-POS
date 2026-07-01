<?php
require_once 'dbConnection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $productID = intval($_POST['productID']);
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    $stmt = $conn->prepare("UPDATE products SET productName = ?, ProductCategory = ?, productDesc = ?, productPrice = ?, productStock = ? WHERE productID = ?");
    $stmt->bind_param("sssdii", $name, $category, $description, $price, $stock, $productID);

    if ($stmt->execute()) {
        header("Location: products-menu.php");
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

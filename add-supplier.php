<?php
require_once 'dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierName = $_POST['supplierName'];
    $Contact = $_POST['Contact'];
    $Address = $_POST['Address'];
    
    function generateRandomSupplierID($conn)// Generate a unique random supplier id
    {            $count="";
        do {

            $randomId = rand(1000000, 9999999); // range
            $stmt = $conn->prepare("SELECT COUNT(*) FROM suppliers WHERE supplierID = ?");
            $stmt->bind_param('i', $randomId);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
        } while ($count > 0); // Loop if the booking_id already exists

        return $randomId;
    }

    $supplierID = generateRandomSupplierId($conn);
        $stmt = $conn->prepare("INSERT INTO suppliers (supplierID, supplierName, Contact, Address) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $supplierID, $supplierName, $Contact, $Address);

        if ($stmt->execute()) {
            header('Location: suppliers-menu.php');
            exit;
        } else {
            echo "Error inserting product: " . $stmt->error;
        }
   
}
?>

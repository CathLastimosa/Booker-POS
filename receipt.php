<?php
session_start();

$invoice = $_GET['invoice'] ?? null;

if (!$invoice || !isset($_SESSION['receipts'][$invoice])) {
    echo "Invalid or missing invoice number.";
    exit;
}

$items = $_SESSION['receipts'][$invoice];

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Receipt - <?= htmlspecialchars($invoice) ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tfoot td { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Receipt</h1>
    <p><strong>Invoice Number:</strong> <?= htmlspecialchars($invoice) ?></p>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Price (₱)</th>
                <th>Quantity</th>
                <th>Subtotal (₱)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['productName']) ?></td>
                <td><?= number_format($item['price'], 2) ?></td>
                <td><?= intval($item['quantity']) ?></td>
                <td><?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td>₱<?= number_format($total, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <p>Thank you for your purchase!</p>

    <script>
      window.onload = function() {
          window.print();
        };    

      window.onafterprint = function() {
        window.location.href = 'sales-menu.php';
    };
    </script>
</body>
</html>

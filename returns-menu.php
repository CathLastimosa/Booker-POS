<?php
require_once 'dbConnection.php';
session_start();
$adminUsername = $_SESSION['username'] ?? null;

// Fetch all purchases for dropdown
$purchased = "
    SELECT * FROM purchases 
    WHERE EXISTS (
        SELECT 1 
        FROM purchaseItems 
        WHERE purchaseItems.purchaseID = purchases.purchaseID 
        AND purchaseItems.quantity > 0
    )
";

$resultsPurchased = $conn->query($purchased);

$productsForInvoice = [];
$selectedInvoiceNo = $_POST['invoiceList'] ?? '';

if (!empty($selectedInvoiceNo)) {
    // Get purchaseID from invoiceNo
    $stmt = $conn->prepare("SELECT purchaseID FROM purchases WHERE invoiceNo = ?");
    $stmt->bind_param("s", $selectedInvoiceNo);
    $stmt->execute();
    $stmt->bind_result($purchaseID);
    $stmt->fetch();
    $stmt->close();

    // Get products for the purchaseID
    if (!empty($purchaseID)) {
        $query = "
            SELECT 
                purchaseItems.*, 
                purchases.invoiceNo, 
                products.productName, 
                products.image, 
                products.productPrice, 
                products.productStock
            FROM purchaseItems
            LEFT JOIN purchases ON purchaseItems.purchaseID = purchases.purchaseID
            LEFT JOIN products ON purchaseItems.productID = products.productID
            WHERE purchaseItems.purchaseID = ?
            AND purchaseItems.quantity > 0
        ";

        $stmt2 = $conn->prepare($query);
        $stmt2->bind_param("i", $purchaseID);
        $stmt2->execute();
        $productsForInvoice = $stmt2->get_result();
        $stmt2->close();
    }
}

$returnQuery = " 
    SELECT 
        purchases.invoiceNo,
        returns.returnDate,
        returns.productID,
        products.productName,
        returns.returnQuantity,
        returns.reason
    FROM returns
    LEFT JOIN purchases ON purchases.purchaseID = returns.purchaseID
    LEFT JOIN products ON returns.productID = products.productID
    ORDER BY returns.returnDate DESC
";
$returnResult = $conn->query($returnQuery);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Returns</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:100,200,300,400,500,600,700">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=IM+Fell+DW+Pica:ital@0;1&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="returnsBody">
        <aside id="sidebar">
            <div class="fa-solid fa-bars toggle-btn" id="toggleSidebar" onclick="toggleSidebar()"></div>
            <img src="uploads/booker-logo.png" alt="Logo">
            <ul>
                <li><a href="dashboard-menu.php"><span>📟 Dashboard</span></a></li>
                <li><a href="products-menu.php"><span>📚 Products</span></a></li>
                <li><a href="suppliers-menu.php"><span>🚛 Suppliers</span></a></li>
                <li><a href="purchases-menu.php"><span>🛒 Purchases</span></a></li>
                <li><a href="sales-menu.php"><span>🏷️ Sales</span></a></li>
                <li><a href="returns-menu.php"><span>↺ Returns</span></a></li>
                <li><a href="users-menu.php"><span>👤 User Account</span></a></li>
                <li onclick="window.location.href='logout.php';"></li>
            </ul>
            <div class="profile-card">
                <img class="profile-card-img" src="uploads\profile.jpg" alt="Profile Picture">
                <h4 class="profile-card-username">Hi, <?php echo $adminUsername; ?></h4>
                <div class="logout">
                    <a onclick="window.location.href='logout.php';"><i
                            class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </div>
            </div>
        </aside>
        <main class="returns-maincontent">
            <div class="input-return-details">
                <div class="fa-solid fa-bars toggle-btn" onclick="toggleSidebar()"></div>
                <form action="" method="POST">
                    <h1>Returns</h1><br>
                    <div>
                        <label for="invoiceNo">Invoice No.</label><br>
                        <select id="invoiceNo-list" name="invoiceList" required onchange="this.form.submit()"
                            style="width: 15%; padding: 5px; border-radius: 5px;">
                            <option value="">Select Invoice</option>
                            <?php while ($row = $resultsPurchased->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($row['invoiceNo']) ?>"
                                    <?= ($selectedInvoiceNo === $row['invoiceNo']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['invoiceNo']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </form>
                <!-- Products Section -->
                <?php if (!empty($selectedInvoiceNo) && $productsForInvoice && $productsForInvoice->num_rows > 0): ?>
                    <form action="return-product.php" method="POST">
                        <input type="hidden" name="invoiceNo" value="<?= htmlspecialchars($selectedInvoiceNo) ?>">
                        <div id="class" style="display: flex; flex-wrap: wrap;">
                            <?php while ($row = $productsForInvoice->fetch_assoc()):
                                $productId = $row['productID'];
                                ?>
                                <div style="margin: 10px; border: 1px solid #ccc; padding: 10px; width: 180px;">
                                    <input type="checkbox" name="selectedProducts[]" value="<?= $productId ?>"
                                        id="product_<?= $productId ?>">
                                    <label for="product_<?= $productId ?>">
                                        <img src="<?= htmlspecialchars($row['image']) ?>" alt="product-image" width="65px"
                                            height="70px"><br>
                                        <b><?= htmlspecialchars($row['productName']) ?></b><br>
                                        Price: ₱<span class="price"
                                            data-price="<?= $row['productPrice'] ?>"><?= htmlspecialchars($row['productPrice']) ?></span><br>
                                        Stock: <?= htmlspecialchars($row['quantity']) ?><br>
                                    </label>
                                    <label for="qty_<?= $productId ?>">Quantity:</label>
                                    <input type="number" name="quantity[<?= $productId ?>]" id="qty_<?= $productId ?>" min="1"
                                        max="<?= htmlspecialchars($row['quantity']) ?>" value="1" style="width: 60px;"
                                        class="qty-input" data-product-id="<?= $productId ?>">
                                    <br>
                                    <label for="reason_<?= $productId ?>">Reason:</label>
                                    <textarea name="reason[<?= $productId ?>]" id="reason_<?= $productId ?>" rows="2" cols="20"
                                        placeholder="Enter reason..."></textarea>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <input type="submit" value="Return Selected Products"
                            onclick="return confirm('Are you sure you want to return the selected products?');"></input>
                    </form>

                <?php elseif (!empty($selectedInvoiceNo)): ?>
                    <p>No products found!</p>
                <?php endif; ?>
            </div>
            <div class="listTable">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Date Returned</th>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Return Quantity</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($returnResult && $returnResult->num_rows > 0): ?>
                            <?php while ($row = $returnResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['invoiceNo']) ?></td>
                                    <td><?= htmlspecialchars($row['returnDate']) ?></td>
                                    <td><?= htmlspecialchars($row['productID']) ?></td>
                                    <td><?= htmlspecialchars($row['productName']) ?></td>
                                    <td><?= htmlspecialchars($row['returnQuantity']) ?></td>
                                    <td><?= htmlspecialchars($row['reason']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No returned item found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        document.addEventListener('DOMContentLoaded', function () {
            const currentPath = window.location.pathname;
            const fileName = currentPath.substring(currentPath.lastIndexOf('/') + 1);

            const sidebarLinks = document.querySelectorAll('aside ul li a');

            sidebarLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (linkHref === fileName) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
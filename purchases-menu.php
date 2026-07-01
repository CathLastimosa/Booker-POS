<?php
require_once 'dbConnection.php';
session_start(); //start session for admin account
$adminUsername = $_SESSION['username'] ?? null; // get the login user's username to display in the profile

$resultTable = "
    SELECT 
        purchases.invoiceNo,
        purchases.datePurchased,
        suppliers.supplierName,
        products.productName,
        products.image,
        purchaseItems.quantity
    FROM purchases
    INNER JOIN suppliers ON purchases.supplierID = suppliers.supplierID
    INNER JOIN purchaseItems ON purchases.purchaseID = purchaseItems.purchaseID
    INNER JOIN products ON purchaseItems.productID = products.productID
    ORDER BY purchases.datePurchased DESC
";
$purchasesResult = $conn->query($resultTable);

$supplier = "SELECT * FROM suppliers"; //fetch the suppliers to display in the drop down in selecting a supplier for purchase
$supplier_results = $conn->query($supplier);

$products = "SELECT * FROM products";// fetch the products that will be purchase by the admin
$products = $conn->query($products);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Purchases</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:100,200,300,400,500,600,700">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=IM+Fell+DW+Pica:ital@0;1&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="purchasesBody">
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

        <main class="purch-maincontent">
            <div class="input-purchase-details">
                <div class="fa-solid fa-bars toggle-btn" onclick="toggleSidebar()"></div>
                <form action="add-purchase.php" method="POST">
                    <div id="supplier">
                        <h1>Purchases</h1>
                        <label for="supplier">
                            Supplier
                        </label>
                        <select id="supplier-list" name="supplierList" required>
                            <option value="">Select Supplier</option>
                            <?php while ($row = $supplier_results->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($row['supplierID']) ?>">
                                    <?= htmlspecialchars($row['supplierID']) ?> -
                                    <?= htmlspecialchars($row['supplierName']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <label for="invoiceNumber">
                        Invoice Number
                        <?php
                        function generateRandomInvoiceNumber($conn)
                        {
                            $count = "";
                            do {

                                $invoiceNo = rand(1000000, 9999999); // range
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM purchases WHERE invoiceNo = ?");
                                $stmt->bind_param('i', $invoiceNo);
                                $stmt->execute();
                                $stmt->bind_result($count);
                                $stmt->fetch();
                            } while ($count > 0);

                            return $invoiceNo;
                        }

                        $generatedInvoiceNo = generateRandomInvoiceNumber($conn);
                        ?>
                    </label>
                    <input type="text" name="invoiceNo" id="" value="<?php echo $generatedInvoiceNo; ?>" readonly
                        style="border:none;">
                    <div class="product-search" style="margin-bottom: 1.5rem; margin-top: 1rem;">
                        <input type="text" id="productSearch" placeholder="Search products..."
                            style="padding: 0.5rem 1rem; border-radius: 25px; border: 1px solid #ccc; width: 100%; max-width: 400px;">
                    </div>

                    <div id="class">
                        <?php while ($row = $products->fetch_assoc()) {
                            $productId = $row['productID'];
                            ?>
                            <div style="margin: 10px ; border: 1px solid #ccc; padding: 10px; width: 180px;">

                                <input type="checkbox" name="selectedProducts[]" value="<?= $productId ?>">
                                <img src="<?= htmlspecialchars($row['image']) ?>" alt="product-image" width="65px"
                                    height="70px"><br>
                                <b><?= htmlspecialchars($row['productName']) ?></b><br>
                                Price: ₱<?= htmlspecialchars($row['productPrice']) ?><br>
                                Stock: <?= htmlspecialchars($row['productStock']) ?><br>

                                <?php if ((int) $row['productStock'] > 0): ?>
                                    <label for="qty_<?= $productId ?>">Quantity:</label>
                                    <input type="number" name="quantity[<?= $productId ?>]" id="qty_<?= $productId ?>" min="1"
                                        max="<?= htmlspecialchars($row['productStock']) ?>" value="1" style="width: 60px;">
                                <?php else: ?>
                                    <p style="color: red;">Out of Stock</p>
                                    <input type="number" disabled style="width: 60px;" value="0">
                                <?php endif; ?>
                            </div>
                        <?php } ?>
                    </div>
                    <h3>Total Amount: ₱<span id="totalAmount">0.00</span></h3>
                    <input type="submit" value="Buy" class="buyPurchases">
                </form>
            </div>
        </main>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        document.getElementById("productSearch").addEventListener("keyup", function () {
            let query = this.value.toLowerCase();
            let cards = document.querySelectorAll("#class > div");

            cards.forEach(function (card) {
                let text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? "block" : "none";
            });
        });

        function updateTotal() {
            let total = 0;
            document.querySelectorAll('#class input[type="checkbox"]:checked').forEach(function (checkbox) {
                let card = checkbox.closest('div');
                let priceText = card.innerHTML.match(/Price:\s*₱(\d+(\.\d+)?)/i);
                let price = priceText ? parseFloat(priceText[1]) : 0;
                let quantityInput = card.querySelector('input[type="number"]');
                let quantity = quantityInput ? parseInt(quantityInput.value) : 1;

                total += price * quantity;
            });

            document.getElementById('totalAmount').innerText = total.toFixed(2);
        }

        document.querySelectorAll('#class > div').forEach(function (card) {
            card.addEventListener('click', function (e) {
                if (e.target.tagName !== 'INPUT') {
                    let checkbox = card.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    updateTotal();
                }
            });

            let checkbox = card.querySelector('input[type="checkbox"]');
            let quantityInput = card.querySelector('input[type="number"]');

            checkbox.addEventListener('change', updateTotal);
            quantityInput.addEventListener('input', updateTotal);
        });

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
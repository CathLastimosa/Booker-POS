<?php
session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

// Check session timeout
checkSessionTimeout();

// Require login and Admin role
requireRole('Admin');

$adminUsername = getCurrentUser();

// Log page access
logActivity('PAGE_VIEW', 'Accessed Dashboard', $adminUsername);

$sql = "
    SELECT 
        p.productID,
        p.productName,
        p.productPrice,
        p.ProductCategory,
        p.image,
        COALESCE(pi.totalPurchased, 0) AS totalPurchased,
        COALESCE(pi.totalPurchased, 0) - COALESCE(si.totalSold, 0) AS productStock
    FROM 
        products p
    INNER JOIN 
        (SELECT productID, SUM(quantity) AS totalPurchased FROM purchaseItems GROUP BY productID) pi
        ON p.productID = pi.productID
    LEFT JOIN 
        (SELECT productID, SUM(quantity) AS totalSold FROM salesItems GROUP BY productID) si
        ON p.productID = si.productID
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:100,200,300,400,500,600,700">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=IM+Fell+DW+Pica:ital@0;1&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="dashboardBody">
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
                    <a onclick="window.location.href='logout.php';"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </div>
            </div>
        </aside>

        <main class="db-maincontent">
            <div class="fa-solid fa-bars toggle-btn" onclick="toggleSidebar()"></div>
            <div class="stock-filter-menu">
                <h1>Stocks</h1>
                <div class="stock-selection">
                    <button data-filter="all">All</button>
                    <button data-filter="low" style="background-color: red; color: white">Low Stocks</button>
                </div>
            </div>
            <section class="display-productCard">
                <div id="product-card">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="product-item" data-stock="<?= $row['productStock'] ?>">
                            <img src="<?= htmlspecialchars($row['image']) ?>"
                                alt="<?= htmlspecialchars($row['productName']) ?>" width="150">
                            <h3><?= htmlspecialchars($row['productName']) ?></h3>
                            <p>
                                <strong>₱<?= number_format($row['productPrice'], 2) ?></strong>
                                <br><br>
                                <?= $row['productStock'] == 0 ? '<span style="color: red;">Out of Stock</span>' : '' ?>
                            </p>

                            <?php if ($row['productStock'] > 0): ?>
                                <p style="color: <?= $row['productStock'] <= 5 ? 'red' : 'inherit' ?>;">
                                    Total Stock: <strong><?= number_format($row['productStock'], 0) ?></strong>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
        }
        const buttons = document.querySelectorAll('.stock-selection button');
        const products = document.querySelectorAll('.product-item');

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.getAttribute('data-filter');

                products.forEach(product => {
                    const stock = parseInt(product.getAttribute('data-stock'));

                    if (filter === 'all') {
                        product.style.display = 'inline-block';
                    } else if (filter === 'low') {
                        product.style.display = stock <= 5 ? 'inline-block' : 'none';
                    }
                });
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
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
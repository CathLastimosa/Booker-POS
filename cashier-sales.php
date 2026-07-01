<?php
session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

// Check session timeout
checkSessionTimeout();

// Require login - Cashier or Admin can access
requireLogin();
if (!hasRole(['Cashier', 'Admin'])) {
    header('Location: login.php?error=unauthorized');
    exit();
}

$adminUsername = getCurrentUser();

// Log page access
logActivity('PAGE_VIEW', 'Accessed Sales', $adminUsername);

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
    LEFT JOIN 
        (SELECT productID, SUM(quantity) AS totalPurchased FROM purchaseItems GROUP BY productID) pi
        ON p.productID = pi.productID
    LEFT JOIN 
        (SELECT productID, SUM(quantity) AS totalSold FROM salesItems GROUP BY productID) si
        ON p.productID = si.productID
    WHERE 
        (COALESCE(pi.totalPurchased, 0) - COALESCE(si.totalSold, 0)) > 0
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier | Sales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:100,200,300,400,500,600,700">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=IM+Fell+DW+Pica:ital@0;1&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="salesBody">
        <aside id="sidebar">
            <div class="fa-solid fa-bars toggle-btn" id="toggleSidebar" onclick="toggleSidebar()"></div>
            <img src="uploads/booker-logo.png" alt="Logo">
            <ul>
                <li><a href="cashier-sales.php"><span>🏷️ Sales</span></a></li>
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
        <main class="sales-maincontent">
            <section class="categories-selection-menu">
                <div class="fa-solid fa-bars toggle-btn" onclick="toggleSidebar()"></div>
                <h2>Categories</h2>
                <div class="genre-selection">
                    <button data-category="All">All</button>
                    <button data-category="Self-Help">Self-Help</button>
                    <button data-category="Genre-bending anthology">Genre-Bending Anthology</button>
                    <button data-category="Fantasy fiction">Fantasy Fiction</button>
                    <button data-category="Non-Fiction">Nonfiction</button>
                    <button data-category="Graphic Novel">Graphic Novel</button>
                    <button data-category="Manga">Manga</button>
                </div>
            </section>

            <div class="product-search" style="margin-bottom: 1.5rem;">
                <input type="text" id="searchInput" placeholder="Search products..." style="padding: 0.5rem 1rem; border-radius: 25px; border: 1px solid #ccc; width: 100%; max-width: 400px;">
            </div>


            <section class="display-productCard">
                <div id="salesProduct-card">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="salesProduct-item"
                            data-id="<?= $row['productID'] ?>"
                            data-name="<?= htmlspecialchars($row['productName']) ?>"
                            data-price="<?= $row['productPrice'] ?>"
                            data-category="<?= htmlspecialchars($row['ProductCategory']) ?>">
                            <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['productName']) ?>" width="150">
                            <h3><?= htmlspecialchars($row['productName']) ?></h3>
                            <p><strong>₱<?= number_format($row['productPrice'], 2) ?></strong></p>
                            <p>Total Stock: <strong><?= number_format($row['productStock'], 0) ?></strong></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <?php $invoiceNumber = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); ?>

        </main>
        <div class="salesOrder-details">
            <h2>Current Sales</h2>
            <p><strong>Invoice Number:</strong> <?= $invoiceNumber ?></p>
            <hr>

            <form action="payment.php" method="POST" id="salesForm">
                <div id="cart-container"></div>
                <input type="hidden" name="invoiceNumber" value="<?= $invoiceNumber ?>">
                <input type="hidden" name="totalPrice" id="totalPriceInput" value="0">
                <input type="hidden" name="itemsData" id="itemsDataInput" value="">
                <div class="total-display">Total: ₱<span id="total-price">0.00</span></div>
                <button type="submit">Pay Now</button>
            </form>
        </div>

        <!-- Modal -->
        <div id="saleModal">
            <div class="saleModal-content">
                <p id="saleModalMessage"></p>
                <button id="proceedBtn" class="saleModal-btn">Proceed</button>
                <button id="printBtn" class="saleModal-btn">Print Receipt</button>
            </div>
        </div>


    </div>
    <script>
        const sidebar = document.getElementById('sidebar');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
        }

        const buttons = document.querySelectorAll('.genre-selection button');
        const products = document.querySelectorAll('.salesProduct-item');
        const cartContainer = document.getElementById('cart-container');
        const totalPriceDisplay = document.getElementById('total-price');
        const cart = {};

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const selectedCategory = button.getAttribute('data-category').toLowerCase();

                products.forEach(product => {
                    const productCategory = product.getAttribute('data-category').toLowerCase();
                    product.style.display = (selectedCategory === 'all' || productCategory === selectedCategory) ? 'block' : 'none';
                });
            });
        });

        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();

            products.forEach(product => {
                const name = product.getAttribute('data-name').toLowerCase();
                const category = product.getAttribute('data-category').toLowerCase();

                product.style.display = name.includes(query) || category.includes(query) ? 'block' : 'none';
            });
        });


        products.forEach(product => {
            product.style.cursor = 'pointer';
            product.addEventListener('click', () => {
                const id = product.dataset.id;
                const name = product.dataset.name;
                const price = parseFloat(product.dataset.price);
                const stock = parseInt(product.querySelector('p:nth-of-type(2)').textContent.replace(/[^\d]/g, '')); // get stock from "Total Stock: N"

                if (!cart[id]) {
                    cart[id] = {
                        name,
                        price,
                        quantity: 1,
                        stock
                    };
                } else {
                    if (cart[id].quantity + 1 > cart[id].stock) {
                        alert("Quantity exceeds available stock!");
                        return;
                    }
                    cart[id].quantity++;
                }
                updateCartDisplay();
            });
        });


        function updateCartDisplay() {
            cartContainer.innerHTML = '';
            let total = 0;

            for (var itemId in cart) {
                var item = cart[itemId];
                total += item.price * item.quantity;

                var itemDiv = document.createElement('div');
                itemDiv.setAttribute('data-id', itemId);


                itemDiv.innerHTML = `
            <p><strong>${item.name}</strong></p>
            <p>Price: ₱${item.price.toFixed(2)}</p>
            <p>
                Quantity: 
                <input type='number' value='${item.quantity}' data-id='${itemId}' class='cart-qty'>
            </p>
            <button class='salesRemove-btn' data-id='${itemId}'>Remove</button>
            <hr style="margin-bottom: 20px;">
        `;

                cartContainer.appendChild(itemDiv);
            }

            totalPriceDisplay.textContent = total.toFixed(2);

            // Quantity Change Handling
            var inputs = document.getElementsByClassName('cart-qty');
            for (var i = 0; i < inputs.length; i++) {
                inputs[i].addEventListener('change', function() {
                    var id = this.getAttribute('data-id');
                    var newQty = parseInt(this.value);
                    var inputEl = this;

                    let existingError = inputEl.parentElement.querySelector('.input-error');
                    if (existingError) existingError.remove();
                    inputEl.style.borderColor = '';

                    if (newQty > cart[id].stock) {
                        this.value = cart[id].quantity;
                        inputEl.style.borderColor = 'red';

                        let errorMsg = document.createElement('div');
                        errorMsg.className = 'input-error';
                        errorMsg.style.color = 'red';
                        errorMsg.style.fontSize = '0.8em';
                        errorMsg.textContent = 'Exceeds available stock (' + cart[id].stock + ')';
                        inputEl.parentElement.appendChild(errorMsg);
                        return;
                    }

                    if (newQty > 0) {
                        cart[id].quantity = newQty;
                        updateCartDisplay();
                    }
                });
            }

            var removeButtons = document.getElementsByClassName('salesRemove-btn');
            for (var i = 0; i < removeButtons.length; i++) {
                removeButtons[i].addEventListener('click', function() {
                    var id = this.getAttribute('data-id');
                    delete cart[id];
                    updateCartDisplay();
                });
            }
        }

        const salesForm = document.getElementById('salesForm');
        const totalPriceInput = document.getElementById('totalPriceInput');
        const itemsDataInput = document.getElementById('itemsDataInput');

        const modal = document.getElementById('saleModal');
        const modalMessage = document.getElementById('saleModalMessage');
        const proceedBtn = document.getElementById('proceedBtn');
        const printBtn = document.getElementById('printBtn');

        salesForm.addEventListener('submit', function(event) {
            event.preventDefault();

            let cartData = [];

            for (let id in cart) {
                cartData.push({
                    productID: id,
                    productName: cart[id].name,
                    price: cart[id].price,
                    quantity: cart[id].quantity
                });
            }

            if (cartData.length === 0) {
                alert("Cart is empty!");
                return;
            }

            totalPriceInput.value = parseFloat(totalPriceDisplay.textContent);
            itemsDataInput.value = JSON.stringify(cartData);

            const formData = new FormData(salesForm);

            fetch('payment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    modalMessage.textContent = data;
                    modal.style.display = 'block';
                })
                .catch(error => {
                    alert("Error: " + error);
                });
        });

        proceedBtn.addEventListener('click', () => {
            window.location.href = 'sales-menu.php';
        });

        printBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            const invoice = document.querySelector('input[name="invoiceNumber"]').value;
            window.open('receipt.php?invoice=' + encodeURIComponent(invoice), '_blank');
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
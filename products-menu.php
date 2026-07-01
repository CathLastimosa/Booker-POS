<?php
session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

// Check session timeout
checkSessionTimeout();

// Require Admin role
requireRole('Admin');

$adminUsername = getCurrentUser();

// Log page access
logActivity('PAGE_VIEW', 'Accessed Products', $adminUsername);

$sql = "SELECT * FROM products";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:100,200,300,400,500,600,700">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=IM+Fell+DW+Pica:ital@0;1&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="productsBody">
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

        <main class="prod-maincontent">
            <div class="fa-solid fa-bars toggle-btn" onclick="toggleSidebar()"></div>
            <h1>Products</h1><br>
            <div class="input-form">
                <form action="add-product.php" method="POST" enctype="multipart/form-data">
                    <div id="input-name">
                        <label for="name">Product Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div id="input-category">
                        <label for="category">Product Category:</label>
                        <select id="category" name="category" required
                            style="width: 95%; padding: 5px; border-radius: 5px;">
                            <option value="">Select Category</option>
                            <option value="Self-Help">Self-Help</option>
                            <option value="Genre-bending anthology">Genre-bending anthology</option>
                            <option value="Fantasy Fiction">Fantasy Fiction</option>
                            <option value="Fiction">Fiction</option>
                            <option value="Non-Fiction">Non-Fiction</option>
                            <option value="Graphic Novel">Graphic Novel</option>
                            <option value="Manga">Manga</option>
                        </select>
                    </div>
                    <div id="input-description">
                        <label for="description">Description</label>
                        <input type="text" name="description">
                    </div>
                    <div id="input-price">
                        <label for="price">Product Price:</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    <div id="input-stock">
                        <label for="stock">Product Stock:</label>
                        <input type="number" id="stock" name="stock" min="0" required>
                    </div>
                    <div id="input-image">
                        <label for="image">Upload Image:</label>
                        <input type="file" id="image" name="image" accept="image/*" required />
                    </div>
                    <div id="preview-image">
                        <p>Image Preview:</p>
                        <img id="imagePreview" alt="Image Preview" />
                    </div>
                    <button type="submit">Add Product</button>
                </form>
            </div>
            <div class="listTable">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['productID']) ?></td>
                                <td><?= htmlspecialchars($row['productName']) ?></td>
                                <td><?= htmlspecialchars($row['ProductCategory']) ?></td>
                                <td><?= htmlspecialchars($row['productDesc']) ?></td>
                                <td>₱<?= number_format($row['productPrice'], 2) ?></td>
                                <td><?= $row['productStock'] ?></td>
                                <td>
                                    <?php if (!empty($row['image'])): ?>
                                        <img src="<?= $row['image'] ?>" alt="Image" width="50">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">Edit</button>
                                        <button class="btn-delete"
                                            onclick="deleteProduct(<?php echo htmlspecialchars($row['productID']); ?>)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </main>
        <div class="modal" id="editModal">
            <div class="modal-content">
                <br>
                <h3>Edit Product</h3><br>
                <form action="edit-product.php" method="POST">
                    <input type="hidden" name="productID" id="editProductID">
                    <label>Name:</label><br>
                    <input type="text" name="name" id="editName"><br><br>
                    <label>Category:</label><br>
                    <input type="text" name="category" id="editCategory"><br><br>
                    <label>Description:</label><br>
                    <input type="text" name="description" id="editDescription"><br><br>
                    <label>Price:</label><br>
                    <input type="number" step="0.01" name="price" id="editPrice"><br><br>
                    <label>Stock:</label><br>
                    <input type="number" name="stock" id="editStock"><br><br>
                    <div class="action-buttons">
                        <button type="submit">Save</button>
                        <button type="button" onclick="closeEditModal()">Cancel</button>
                    </div><br>
                </form>
            </div>
        </div>
    </div>
    <script>
        const sidebar = document.getElementById('sidebar');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
        }
        document.getElementById('image').addEventListener('change', function(event) { //image preview javascript
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        function openEditModal(product) {
            document.getElementById('editProductID').value = product.productID;
            document.getElementById('editName').value = product.productName;
            document.getElementById('editCategory').value = product.ProductCategory;
            document.getElementById('editDescription').value = product.productDesc;
            document.getElementById('editPrice').value = product.productPrice;
            document.getElementById('editStock').value = product.productStock;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Optional: Close modal when clicking outside modal-content
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        };

        function deleteProduct(productID) { //delete product
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "delete-product.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert("Product deleted successfully.");
                    window.location.reload();
                } else {
                    alert("Error deleting product.");
                }
            };
            xhr.send("productID=" + productID);
        }
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
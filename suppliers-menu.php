<?php
require_once 'dbConnection.php';
session_start();
$adminUsername = $_SESSION['username'] ?? null; // get the login user's username to display in the profile

$sql = "SELECT * FROM suppliers";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Suppliers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:100,200,300,400,500,600,700">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=IM+Fell+DW+Pica:ital@0;1&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="suppliersBody">
        <aside id="sidebar">
            <div class="fa-solid fa-bars toggle-btn" onclick="toggleSidebar()"></div>
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

        <main class="supp-maincontent">
            <div class="fa-solid fa-bars toggle-btn" onclick="toggleSidebar()"></div>
            <h1>Suppliers</h1><br>
            <div class="input-form">
                <form action="add-supplier.php" method="POST" enctype="multipart/form-data">
                    <div class="supplier-name">
                        <label for="supplierName">Supplier Name</label>
                        <input type="text" name="supplierName" id="supplierName">
                    </div>
                    <div class="supplier-number">
                        <label for="contactNum">Contact Number</label>
                        <input type="text" name="Contact" id="supplierNum">
                    </div>
                    <div class="supplier-address">
                        <label for="address">Address</label>
                        <input type="text" name="Address" id="supplierAddress">
                    </div>
                    <button type="submit">Add Supplier</button>
                </form>
            </div>
            <div class="listTable">
                <table>
                    <thead>
                        <tr>
                            <th>Supplier ID</th>
                            <th>Supplier Name</th>
                            <th>Contact Number</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['supplierID']) ?></td>
                                <td><?= htmlspecialchars($row['supplierName']) ?></td>
                                <td><?= htmlspecialchars($row['Contact']) ?></td>
                                <td><?= htmlspecialchars($row['Address']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">Edit</button>
                                        <button class="btn-delete" onclick="deleteSupplier(<?php echo htmlspecialchars($row['supplierID']); ?>)">Delete</button>
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
                <h3>Edit Supplier</h3>
                <form action="edit-supplier.php" method="POST">
                    <input type="hidden" name="supplierID" id="editSupplierID">
                    <label>Supplier Name</label><br>
                    <input type="text" name="supplierName" id="name"><br><br>
                    <label>Contact Number</label><br>
                    <input type="text" name="Contact" id="contact-number"><br><br>
                    <label>Address</label><br>
                    <input type="text" name="Address" id="address"><br><br>
                    <div class="action-buttons">
                        <button type="submit">Save</button>
                        <button type="button" onclick="closeEditModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const sidebar = document.getElementById('sidebar');
        function toggleSidebar() {
            sidebar.classList.toggle('show');
        }
        
        function openEditModal(suppliers) {
            document.getElementById('editSupplierID').value = suppliers.supplierID;
            document.getElementById('name').value = suppliers.supplierName;
            document.getElementById('contact-number').value = suppliers.Contact;
            document.getElementById('address').value = suppliers.Address;
            document.getElementById('editModal').style.display = 'block'; // Show modal
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none'; // Hide modal
        }

        // Optional: Close modal when clicking outside modal-content
        window.onclick = function (event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        };
        
        function deleteSupplier(supplierID) {//delete supplier
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "delete-supplier.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert("Supplier deleted successfully.");
                    window.location.reload();
                } else {
                    alert("Error deleting supplier.");
                }
            };
            xhr.send("supplierID=" + supplierID);
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
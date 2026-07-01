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
logActivity('PAGE_VIEW', 'Accessed Users Management', $adminUsername);

$sql = "SELECT id, username, role FROM users";
$result = $conn->query($sql);
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin | User Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:100,200,300,400,500,600,700">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=IM+Fell+DW+Pica:ital@0;1&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="usersBody">
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


        <main class="users-maincontent">
            <div class="fa-solid fa-bars toggle-btn" onclick="toggleSidebar()"></div>
            <h1>User Account</h1>
            <div class="input-form">
                <form action="add-user.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div id="input-name">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required minlength="3">
                    </div>
                    <div id="input-password">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required minlength="8" title="Password must be at least 8 characters with uppercase, lowercase, and numbers">
                    </div>
                    <div id="input-password">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div id="input-role">
                        <label for="role">Role:</label>
                        <select name="role" id="role_admin" style="width: 95%; padding: 5px; border-radius: 5px;" required>
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Cashier">Cashier</option>
                        </select>
                    </div>
                    <button type="submit">Add User</button>
                </form>

            </div>

            <div class="listTable">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['role']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)'>Edit</button>
                                        <button class="btn-delete" onclick="deleteUser('<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?>')">Delete</button>
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
                <h3>Edit User</h3>
                <form action="edit-user.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="original_username" id="originalUsername">
                    <label>Username:</label><br />
                    <input type="text" name="username" id="editUsername" required minlength="3"><br><br>

                    <label>New Password (leave blank to keep current):</label><br>
                    <input type="password" name="password" id="editPassword" minlength="8"><br><br>

                    <label>Role:</label><br>
                    <select name="role" id="editRole" required>
                        <option value="">Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Cashier">Cashier</option>
                    </select> <br><br>
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

        function openEditModal(users) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('editUsername').value = users.username;
            document.getElementById('originalUsername').value = users.username;
            document.getElementById('editPassword').value = '';
            document.getElementById('editRole').value = users.role;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }

        function deleteUser(username) {
            if (!confirm('Are you sure you want to delete this user?')) {
                return;
            }

            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "delete-user.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert(response.message);
                        window.location.reload();
                    } else {
                        alert(response.message);
                    }
                } else {
                    const response = JSON.parse(xhr.responseText);
                    alert(response.message || "Error deleting user.");
                }
            };
            xhr.send("username=" + encodeURIComponent(username) + "&csrf_token=" + encodeURIComponent(csrfToken));
        }
    </script>
</body>

</html>
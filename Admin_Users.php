<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: Home.php");
    exit();
}

$message = "";
$messageType = "success";
$current_admin_id = $_SESSION['user_id'];

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    if ($delete_id === $current_admin_id) {
        $message = "Error: You cannot delete your own admin account while logged in.";
        $messageType = "error";
    } else {
        $conn->query("DELETE FROM users WHERE user_id = $delete_id");
        header("Location: Admin_Users.php?msg=deleted");
        exit();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $message = "User account deleted successfully.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $role = $_POST['role'];

    if (!empty($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        if ($user_id === $current_admin_id && $role !== 'admin') {
            $message = "Error: You cannot demote your own account to customer.";
            $messageType = "error";
        } else {
            if (!empty($_POST['password'])) {
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, role=?, password_hash=? WHERE user_id=?");
                $stmt->bind_param("sssssi", $full_name, $email, $phone_number, $role, $password_hash, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, role=? WHERE user_id=?");
                $stmt->bind_param("ssssi", $full_name, $email, $phone_number, $role, $user_id);
            }
            
            if ($stmt->execute()) {
                $message = "User details updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error: Email might already be in use by another account.";
                $messageType = "error";
            }
            $stmt->close();
        }
    } else {
        if (empty($_POST['password'])) {
            $message = "Error: Password is required for new users.";
            $messageType = "error";
        } else {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone_number, role, password_hash) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $email, $phone_number, $role, $password_hash);
            
            if ($stmt->execute()) {
                $message = "New user created successfully!";
                $messageType = "success";
            } else {
                $message = "Error: Email already exists.";
                $messageType = "error";
            }
            $stmt->close();
        }
    }
}

$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM users WHERE user_id = $edit_id");
    $edit_user = $res->fetch_assoc();
}

$users = $conn->query("SELECT user_id, full_name, email, phone_number, role, created_at FROM users ORDER BY role ASC, full_name ASC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Admin.css">
    <title>Manage Users - Admin</title>
    <style>
        .admin-form { background: #333; padding: 25px; border-radius: 20px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .admin-form label { display: block; font-size: 14px; color: #bd5b62; font-weight: bold; margin-bottom: 5px; }
        .admin-form input, .admin-form select { width: 100%; padding: 10px; border-radius: 8px; border: none; font-family: "myFont", serif; background: #222; color: white; box-sizing: border-box; }
        .admin-form button { background: #bd5b62; color: white; padding: 12px 25px; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 15px; font-family: "myFont", serif; }
        .admin-form button:hover { background: #9f4b52; }
        .action-link { color: #4CAF50; text-decoration: none; margin-right: 15px; font-weight: bold; }
        .action-link.delete { color: #e53935; }
        .msg-box { padding: 10px; border-radius: 8px; margin-bottom: 20px; color: white; }
        .msg-box.success { background: #4CAF50; }
        .msg-box.error { background: #e53935; }
        .role-badge { padding: 5px 10px; border-radius: 8px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .role-admin { background: #bd5b62; color: white; }
        .role-customer { background: #42a5f5; color: white; }
    </style>
</head>
<body>
    <nav class="Navigation">
        <img src="Assets/UI-icons/Logo.png" class="Logo" alt="Logo" width="150px">
        <ul>
            <li><a href="Admin_Dashboard.php">Dashboard</a></li>
            <li><a href="Admin_Movies.php">Manage Movies</a></li>
            <li><a href="Admin_Theaters.php">Theaters & Screens</a></li>
            <li><a href="Admin_Schedules.php">Schedules</a></li>
            <li><a href="Admin_Reservations.php">Reservations</a></li>
            <li><a href="Admin_Users.php" style="font-weight: bold; color: #bd5b62;">Users</a></li>
            <li class="Logout"><a href="Auth/Logout.php">Log Out</a></li>
        </ul>
    </nav>
    <main class="Main">
        <h2 class="Page-title">Manage Users</h2>
        
        <?php if ($message): ?>
            <div class="msg-box <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- CREATE / UPDATE FORM -->
        <div class="admin-form">
            <h3 style="margin-top: 0; color: #fdfbfa;"><?= $edit_user ? 'Edit User' : 'Add New User' ?></h3>
            <form method="POST" action="Admin_Users.php">
                <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?? '' ?>">
                
                <div class="form-grid">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="full_name" required value="<?= htmlspecialchars($edit_user['full_name'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Email Address</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" value="<?= htmlspecialchars($edit_user['phone_number'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Role</label>
                        <select name="role" required>
                            <option value="customer" <?= (isset($edit_user['role']) && $edit_user['role'] === 'customer') ? 'selected' : '' ?>>Customer</option>
                            <option value="admin" <?= (isset($edit_user['role']) && $edit_user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Password <?= $edit_user ? '<small style="color:#aaa;">(Leave blank to keep current password)</small>' : '' ?></label>
                        <input type="password" name="password" <?= $edit_user ? '' : 'required' ?>>
                    </div>
                </div>
                
                <button type="submit"><?= $edit_user ? 'Update User' : 'Save New User' ?></button>
                <?php if ($edit_user): ?>
                    <a href="Admin_Users.php" style="margin-left: 15px; color: #f1f1f1; text-decoration: none;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- USERS LIST TABLE -->
        <div class="data-section">
            <h3>Registered Accounts</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email & Phone</th>
                        <th>Role</th>
                        <th>Registered Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?= $u['user_id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?><br><small><?= htmlspecialchars($u['phone_number'] ?? 'N/A') ?></small></td>
                        <td><span class="role-badge <?= $u['role'] === 'admin' ? 'role-admin' : 'role-customer' ?>"><?= strtoupper($u['role']) ?></span></td>
                        <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="Admin_Users.php?edit=<?= $u['user_id'] ?>" class="action-link">Edit</a>
                            <?php if ($u['user_id'] !== $current_admin_id): ?>
                                <a href="Admin_Users.php?delete=<?= $u['user_id'] ?>" class="action-link delete" onclick="return confirm('Are you sure you want to delete this user? All their reservations will also be deleted.');">Delete</a>
                            <?php else: ?>
                                <span style="color: #666; font-size: 14px;">(Current)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
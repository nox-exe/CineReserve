<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: Home.php");
    exit();
}

$message = "";

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM theaters WHERE theater_id = $delete_id");
    header("Location: Admin_Theaters.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $contact = trim($_POST['contact_number']);

    if (!empty($_POST['theater_id'])) {
        $theater_id = (int)$_POST['theater_id'];
        $stmt = $conn->prepare("UPDATE theaters SET name=?, location=?, contact_number=? WHERE theater_id=?");
        $stmt->bind_param("sssi", $name, $location, $contact, $theater_id);
        
        if ($stmt->execute()) {
            $message = "Theater updated successfully!";
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO theaters (name, location, contact_number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $location, $contact);
        
        if ($stmt->execute()) {
            $message = "New theater added successfully!";
        }
        $stmt->close();
    }
}

$edit_theater = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM theaters WHERE theater_id = $edit_id");
    $edit_theater = $res->fetch_assoc();
}

$query = "SELECT t.*, COUNT(s.screen_id) as screen_count 
          FROM theaters t 
          LEFT JOIN screens s ON t.theater_id = s.theater_id 
          GROUP BY t.theater_id 
          ORDER BY t.theater_id ASC";
$theaters = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Admin.css">
    <title>Manage Theaters - Admin</title>
    <style>
        .admin-form { background: #333; padding: 25px; border-radius: 20px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-full { grid-column: span 2; }
        .admin-form label { display: block; font-size: 14px; color: #bd5b62; font-weight: bold; margin-bottom: 5px; }
        .admin-form input, .admin-form textarea { width: 100%; padding: 10px; border-radius: 8px; border: none; font-family: "myFont", serif; background: #222; color: white; box-sizing: border-box; }
        .admin-form button { background: #bd5b62; color: white; padding: 12px 25px; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 15px; font-family: "myFont", serif; }
        .admin-form button:hover { background: #9f4b52; }
        .action-link { color: #4CAF50; text-decoration: none; margin-right: 15px; font-weight: bold; }
        .action-link.delete { color: #e53935; }
        .success-msg { background: #4CAF50; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <nav class="Navigation">
        <img src="Assets/UI-icons/Logo.png" class="Logo" alt="Logo" width="150px">
        <ul>
            <li><a href="Admin_Dashboard.php">Dashboard</a></li>
            <li><a href="Admin_Movies.php">Manage Movies</a></li>
            <li><a href="Admin_Theaters.php" style="font-weight: bold; color: #bd5b62;">Theaters & Screens</a></li>
            <li><a href="Admin_Schedules.php">Schedules</a></li>
            <li><a href="Admin_Reservations.php">Reservations</a></li>
            <li><a href="Admin_Users.php">Users</a></li>
            <li class="Logout"><a href="Auth/Logout.php">Log Out</a></li>
        </ul>
    </nav>
    <main class="Main">
        <h2 class="Page-title">Manage Theaters</h2>
        
        <?php if ($message): ?>
            <div class="success-msg"><?= $message ?></div>
        <?php endif; ?>

        <!-- CREATE / UPDATE FORM -->
        <div class="admin-form">
            <h3 style="margin-top: 0; color: #fdfbfa;"><?= $edit_theater ? 'Edit Theater' : 'Add New Theater' ?></h3>
            <form method="POST" action="Admin_Theaters.php">
                <input type="hidden" name="theater_id" value="<?= $edit_theater['theater_id'] ?? '' ?>">
                
                <div class="form-grid">
                    <div class="form-full">
                        <label>Theater Name</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($edit_theater['name'] ?? '') ?>" placeholder="e.g., CineReserve Mall of Asia">
                    </div>
                    <div>
                        <label>Location</label>
                        <input type="text" name="location" required value="<?= htmlspecialchars($edit_theater['location'] ?? '') ?>" placeholder="e.g., Pasay City, Metro Manila">
                    </div>
                    <div>
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" required value="<?= htmlspecialchars($edit_theater['contact_number'] ?? '') ?>" placeholder="e.g., (02) 8123-4567">
                    </div>
                </div>
                
                <button type="submit"><?= $edit_theater ? 'Update Theater' : 'Save New Theater' ?></button>
                <?php if ($edit_theater): ?>
                    <a href="Admin_Theaters.php" style="margin-left: 15px; color: #f1f1f1; text-decoration: none;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- THEATER LIST TABLE -->
        <div class="data-section">
            <h3>Theater Locations</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Theater Name</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Total Screens</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($theaters as $theater): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($theater['name']) ?></strong></td>
                        <td><?= htmlspecialchars($theater['location']) ?></td>
                        <td><?= htmlspecialchars($theater['contact_number']) ?></td>
                        <td><?= $theater['screen_count'] ?> Screens</td>
                        <td>
                            <a href="Admin_Theaters.php?edit=<?= $theater['theater_id'] ?>" class="action-link">Edit</a>
                            <a href="Admin_Theaters.php?delete=<?= $theater['theater_id'] ?>" class="action-link delete" onclick="return confirm('Are you sure you want to delete this theater? This removes all screens, seats, schedules, and reservations linked to it.');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
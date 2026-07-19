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
    $conn->query("DELETE FROM schedules WHERE schedule_id = $delete_id");
    header("Location: Admin_Schedules.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = (int)$_POST['movie_id'];
    $screen_id = (int)$_POST['screen_id'];
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];
    $ticket_price = (float)$_POST['ticket_price'];

    if (!empty($_POST['schedule_id'])) {
        $schedule_id = (int)$_POST['schedule_id'];
        $stmt = $conn->prepare("UPDATE schedules SET movie_id=?, screen_id=?, show_date=?, show_time=?, ticket_price=? WHERE schedule_id=?");
        $stmt->bind_param("iissdi", $movie_id, $screen_id, $show_date, $show_time, $ticket_price, $schedule_id);
        
        if ($stmt->execute()) {
            $message = "Schedule updated successfully!";
        } else {
            $message = "Error: Time slot might already be booked for this screen.";
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO schedules (movie_id, screen_id, show_date, show_time, ticket_price) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissd", $movie_id, $screen_id, $show_date, $show_time, $ticket_price);
        
        if ($stmt->execute()) {
            $message = "New schedule added successfully!";
        } else {
            $message = "Error: Time slot already exists for this screen.";
        }
        $stmt->close();
    }
}

$edit_schedule = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM schedules WHERE schedule_id = $edit_id");
    $edit_schedule = $res->fetch_assoc();
}

$movies = $conn->query("SELECT movie_id, title FROM movies ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);
$screens = $conn->query("SELECT s.screen_id, s.screen_name, t.name as theater_name FROM screens s JOIN theaters t ON s.theater_id = t.theater_id ORDER BY t.name ASC, s.screen_name ASC")->fetch_all(MYSQLI_ASSOC);

$query = "SELECT sc.schedule_id, sc.show_date, sc.show_time, sc.ticket_price, 
                 m.title AS movie_title, 
                 s.screen_name, t.name AS theater_name
          FROM schedules sc
          JOIN movies m ON sc.movie_id = m.movie_id
          JOIN screens s ON sc.screen_id = s.screen_id
          JOIN theaters t ON s.theater_id = t.theater_id
          ORDER BY sc.show_date DESC, sc.show_time DESC";
$schedules = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Admin.css">
    <title>Manage Schedules - Admin</title>
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
    </style>
</head>
<body>
    <nav class="Navigation">
        <img src="Assets/UI-icons/Logo.png" class="Logo" alt="Logo" width="150px">
        <ul>
            <li><a href="Admin_Dashboard.php">Dashboard</a></li>
            <li><a href="Admin_Movies.php">Manage Movies</a></li>
            <li><a href="Admin_Theaters.php">Theaters & Screens</a></li>
            <li><a href="Admin_Schedules.php" style="font-weight: bold; color: #bd5b62;">Schedules</a></li>
            <li><a href="Admin_Reservations.php">Reservations</a></li>
            <li><a href="Admin_Users.php">Users</a></li>
            <li class="Logout"><a href="Auth/Logout.php">Log Out</a></li>
        </ul>
    </nav>
    <main class="Main">
        <h2 class="Page-title">Manage Schedules</h2>
        
        <?php if ($message): ?>
            <div class="msg-box <?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- CREATE / UPDATE FORM -->
        <div class="admin-form">
            <h3 style="margin-top: 0; color: #fdfbfa;"><?= $edit_schedule ? 'Edit Schedule' : 'Add New Schedule' ?></h3>
            <form method="POST" action="Admin_Schedules.php">
                <input type="hidden" name="schedule_id" value="<?= $edit_schedule['schedule_id'] ?? '' ?>">
                
                <div class="form-grid">
                    <div>
                        <label>Movie</label>
                        <select name="movie_id" required>
                            <option value="">Select a Movie...</option>
                            <?php foreach($movies as $m): ?>
                                <option value="<?= $m['movie_id'] ?>" <?= (isset($edit_schedule['movie_id']) && $edit_schedule['movie_id'] == $m['movie_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Theater & Screen</label>
                        <select name="screen_id" required>
                            <option value="">Select a Screen...</option>
                            <?php foreach($screens as $s): ?>
                                <option value="<?= $s['screen_id'] ?>" <?= (isset($edit_schedule['screen_id']) && $edit_schedule['screen_id'] == $s['screen_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['theater_name']) ?> - <?= htmlspecialchars($s['screen_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Show Date</label>
                        <input type="date" name="show_date" required value="<?= $edit_schedule['show_date'] ?? '' ?>">
                    </div>
                    <div>
                        <label>Show Time</label>
                        <input type="time" name="show_time" required value="<?= $edit_schedule['show_time'] ?? '' ?>">
                    </div>
                    <div>
                        <label>Ticket Price (₱)</label>
                        <input type="number" step="0.01" name="ticket_price" required value="<?= $edit_schedule['ticket_price'] ?? '' ?>" placeholder="e.g. 350.00">
                    </div>
                </div>
                
                <button type="submit"><?= $edit_schedule ? 'Update Schedule' : 'Save New Schedule' ?></button>
                <?php if ($edit_schedule): ?>
                    <a href="Admin_Schedules.php" style="margin-left: 15px; color: #f1f1f1; text-decoration: none;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- SCHEDULES LIST TABLE -->
        <div class="data-section">
            <h3>All Schedules</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Movie</th>
                        <th>Theater & Screen</th>
                        <th>Date & Time</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $sched): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($sched['movie_title']) ?></strong></td>
                        <td><?= htmlspecialchars($sched['theater_name']) ?><br><small><?= htmlspecialchars($sched['screen_name']) ?></small></td>
                        <td><?= date('M j, Y', strtotime($sched['show_date'])) ?><br><small><?= date('g:i A', strtotime($sched['show_time'])) ?></small></td>
                        <td class="highlight">₱<?= number_format($sched['ticket_price'], 2) ?></td>
                        <td>
                            <a href="Admin_Schedules.php?edit=<?= $sched['schedule_id'] ?>" class="action-link">Edit</a>
                            <a href="Admin_Schedules.php?delete=<?= $sched['schedule_id'] ?>" class="action-link delete" onclick="return confirm('Are you sure? This will delete the schedule and cancel any reservations tied to it.');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
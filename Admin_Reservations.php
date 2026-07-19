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
    $conn->query("DELETE FROM reservations WHERE reservation_id = $delete_id");
    header("Location: Admin_Reservations.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE reservations SET status=? WHERE reservation_id=?");
    $stmt->bind_param("si", $status, $reservation_id);
    
    if ($stmt->execute()) {
        $message = "Reservation #$reservation_id status updated to " . strtoupper($status) . ".";
    }
    $stmt->close();
}

$query = "SELECT r.reservation_id, r.total_amount, r.status, r.reservation_date,
                 u.full_name, u.email,
                 m.title AS movie_title,
                 t.name AS theater_name, s.screen_name,
                 sc.show_date, sc.show_time,
                 GROUP_CONCAT(CONCAT(st.seat_row, st.seat_number) SEPARATOR ', ') AS seats
          FROM reservations r
          JOIN users u ON r.user_id = u.user_id
          JOIN schedules sc ON r.schedule_id = sc.schedule_id
          JOIN movies m ON sc.movie_id = m.movie_id
          JOIN screens s ON sc.screen_id = s.screen_id
          JOIN theaters t ON s.theater_id = t.theater_id
          LEFT JOIN reservation_seats rs ON r.reservation_id = rs.reservation_id
          LEFT JOIN seats st ON rs.seat_id = st.seat_id
          GROUP BY r.reservation_id
          ORDER BY r.reservation_date DESC";

$reservations = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Admin.css">
    <title>Manage Reservations - Admin</title>
    <style>
        .action-link.delete { color: #e53935; text-decoration: none; font-weight: bold; margin-left: 10px; }
        .success-msg { background: #4CAF50; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
        .inline-form { display: flex; gap: 5px; align-items: center; }
        .inline-form select { padding: 5px; border-radius: 5px; border: none; background: #222; color: white; font-family: "myFont", serif;}
        .inline-form button { background: #bd5b62; color: white; border: none; border-radius: 5px; padding: 6px 10px; cursor: pointer; font-family: "myFont", serif;}
        .inline-form button:hover { background: #9f4b52; }
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
            <li><a href="Admin_Reservations.php" style="font-weight: bold; color: #bd5b62;">Reservations</a></li>
            <li><a href="Admin_Users.php">Users</a></li>
            <li class="Logout"><a href="Auth/Logout.php">Log Out</a></li>
        </ul>
    </nav>
    <main class="Main">
        <h2 class="Page-title">Manage Reservations</h2>
        
        <?php if ($message): ?>
            <div class="success-msg"><?= $message ?></div>
        <?php endif; ?>

        <!-- RESERVATIONS LIST TABLE -->
        <div class="data-section">
            <h3>All Customer Bookings</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Movie & Schedule</th>
                        <th>Seats</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $res): ?>
                    <tr>
                        <td>#<?= $res['reservation_id'] ?></td>
                        <td><strong><?= htmlspecialchars($res['full_name']) ?></strong><br><small><?= htmlspecialchars($res['email']) ?></small></td>
                        <td>
                            <strong><?= htmlspecialchars($res['movie_title']) ?></strong><br>
                            <small><?= htmlspecialchars($res['theater_name']) ?> (<?= htmlspecialchars($res['screen_name']) ?>)</small><br>
                            <small><?= date('M j, Y', strtotime($res['show_date'])) ?> at <?= date('g:i A', strtotime($res['show_time'])) ?></small>
                        </td>
                        <td><?= htmlspecialchars($res['seats'] ?? 'N/A') ?></td>
                        <td class="highlight">₱<?= number_format($res['total_amount'], 2) ?></td>
                        <td>
                            <form method="POST" action="Admin_Reservations.php" class="inline-form">
                                <input type="hidden" name="reservation_id" value="<?= $res['reservation_id'] ?>">
                                <select name="status">
                                    <option value="pending" <?= $res['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $res['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="cancelled" <?= $res['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </td>
                        <td>
                            <a href="Admin_Reservations.php?delete=<?= $res['reservation_id'] ?>" class="action-link delete" onclick="return confirm('Are you sure you want to completely delete Reservation #<?= $res['reservation_id'] ?>?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
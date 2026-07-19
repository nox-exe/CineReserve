<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: Home.php");
    exit();
}

$stats = [];
$stats['movies'] = $conn->query("SELECT COUNT(*) AS count FROM movies")->fetch_assoc()['count'];
$stats['upcoming_schedules'] = $conn->query("SELECT COUNT(*) AS count FROM schedules WHERE show_date >= CURRENT_DATE")->fetch_assoc()['count'];
$stats['total_reservations'] = $conn->query("SELECT COUNT(*) AS count FROM reservations")->fetch_assoc()['count'];
$stats['customers'] = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];

$revenue_movies_query = "SELECT m.title, COUNT(DISTINCT r.reservation_id) AS total_reservations, SUM(r.total_amount) AS total_revenue
                         FROM movies m
                         JOIN schedules sc ON sc.movie_id = m.movie_id
                         JOIN reservations r ON r.schedule_id = sc.schedule_id
                         WHERE r.status = 'confirmed'
                         GROUP BY m.movie_id, m.title
                         ORDER BY total_revenue DESC LIMIT 5";
$revenue_movies = $conn->query($revenue_movies_query)->fetch_all(MYSQLI_ASSOC);

$theater_perf_query = "SELECT t.name AS theater_name, COUNT(r.reservation_id) AS total_reservations, SUM(r.total_amount) AS total_revenue
                       FROM theaters t
                       JOIN screens sr ON sr.theater_id = t.theater_id
                       JOIN schedules sc ON sc.screen_id = sr.screen_id
                       JOIN reservations r ON r.schedule_id = sc.schedule_id
                       WHERE r.status = 'confirmed'
                       GROUP BY t.theater_id, t.name
                       ORDER BY total_revenue DESC";
$theater_performance = $conn->query($theater_perf_query)->fetch_all(MYSQLI_ASSOC);

$status_query = "SELECT status, COUNT(*) AS total, SUM(total_amount) AS total_value
                 FROM reservations
                 GROUP BY status";
$status_breakdown = $conn->query($status_query)->fetch_all(MYSQLI_ASSOC);

$active_customers_query = "SELECT u.email, COUNT(r.reservation_id) AS total_bookings, SUM(r.total_amount) AS total_spent
                           FROM users u
                           JOIN reservations r ON r.user_id = u.user_id
                           WHERE r.status = 'confirmed'
                           GROUP BY u.user_id, u.email
                           ORDER BY total_spent DESC LIMIT 5";
$active_customers = $conn->query($active_customers_query)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Admin.css">
    <title>Admin Dashboard - CineReserve</title>
</head>
<body>
    <nav class="Navigation">
        <img src="Assets/UI-icons/Logo.png" class="Logo" alt="Logo" width="150px">
        <ul>
            <li><a href="Admin_Dashboard.php" style="font-weight: bold; color: #bd5b62;">Dashboard</a></li>
            <li><a href="Admin_Movies.php">Manage Movies</a></li>
            <li><a href="Admin_Theaters.php">Theaters & Screens</a></li>
            <li><a href="Admin_Schedules.php">Schedules</a></li>
            <li><a href="Admin_Reservations.php">Reservations</a></li>
            <li><a href="Admin_Users.php">Users</a></li>
            <li class="Logout">
                <a href="Auth/Logout.php">Log Out</a>
            </li>
        </ul>
    </nav>
    <main class="Main">
        <h2 class="Page-title">Analytics Dashboard</h2>
        
        <!-- Top Level Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Movies</h3>
                <p><?= $stats['movies'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Upcoming Schedules</h3>
                <p><?= $stats['upcoming_schedules'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Reservations</h3>
                <p><?= $stats['total_reservations'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Registered Customers</h3>
                <p><?= $stats['customers'] ?></p>
            </div>
        </div>

        <!-- Dashboard Tables Grid -->
        <div class="dashboard-tables">
            
            <!-- Revenue Per Movie -->
            <div class="data-section">
                <h3>Revenue by Movie (Top 5)</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Movie Title</th>
                            <th>Reservations</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revenue_movies as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= $row['total_reservations'] ?></td>
                            <td class="highlight">₱<?= number_format($row['total_revenue'] ?? 0, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($revenue_movies)) echo "<tr><td colspan='3'>No data available.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>

            <!-- Theater Performance -->
            <div class="data-section">
                <h3>Theater Performance</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Theater Branch</th>
                            <th>Reservations</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($theater_performance as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['theater_name']) ?></td>
                            <td><?= $row['total_reservations'] ?></td>
                            <td class="highlight">₱<?= number_format($row['total_revenue'] ?? 0, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($theater_performance)) echo "<tr><td colspan='3'>No data available.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>

            <!-- Most Active Customers -->
            <div class="data-section">
                <h3>Top Customers by Spend</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Bookings</th>
                            <th>Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_customers as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= $row['total_bookings'] ?></td>
                            <td class="highlight">₱<?= number_format($row['total_spent'] ?? 0, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($active_customers)) echo "<tr><td colspan='4'>No data available.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>

            <!-- Reservation Status Breakdown -->
            <div class="data-section">
                <h3>Status Breakdown</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Value Represented</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($status_breakdown as $row): ?>
                        <tr>
                            <td><span class="status-badge <?= strtolower($row['status']) ?>"><?= strtoupper($row['status']) ?></span></td>
                            <td><?= $row['total'] ?></td>
                            <td>₱<?= number_format($row['total_value'] ?? 0, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($status_breakdown)) echo "<tr><td colspan='3'>No data available.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</body>
</html>
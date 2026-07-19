<?php
session_start();
require 'db.php';

$query = "SELECT * FROM movies ORDER BY release_date ASC";
$result = $conn->query($query);
$movies = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Reservation_List.css">
    <title>CineReserve - Reservation List</title>
</head>
<body>
    <nav class="Navigation">
        <a href="Home.php">
            <img src="Assets/UI-icons/Logo.png" class="Logo" alt="Logo" width="150px">
        </a>
        <ul>
            <li>
                <img src="Assets/UI-icons/Reservation.png" class="Reservation-icon" width="30px">
                <a href="Reservation_List.php" style="font-weight: bold; color: #bd5b62;">Movies</a>
            </li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <img src="" class="Tickets-icon" width="26px">
                    <a href="My_Tickets.php">My Tickets</a>
                </li>
                <li>
                    <img src="" class="Profile-icon" width="26px">
                    <a href="Profile.php">Profile</a>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li>
                        <img src="" class="Admin-icon" width="26px">
                        <a href="Admin_Dashboard.php" style="color: #bd5b62; font-weight: bold;">Admin Panel</a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
      
            <li class="Logout">
                <img src="Assets/UI-icons/Logout.png" class="Logout-icon" width="26px">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="Auth/Logout.php">Log Out</a>
                <?php else: ?>
                    <a href="Auth/Login.php">Log In</a>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
    
    <main class="Main">
        <h2 class="Reservation-title">Reservation List</h2>
        <section class="reservation-container">

            <?php foreach ($movies as $movie): ?>
            <!-- MOVIE CARD -->
            <section class="First-Movie">
                <section class="First">
                    <div class="Screening">
                        <?= htmlspecialchars($movie['duration_minutes']) ?> Mins
                    </div>
                    <div class="First-img-container">
                        <img src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="Poster">
                    </div>
                </section>

                <div class="Reserve-container">
                    <h2>
                        <a href="Reservation.php?movie_id=<?= $movie['movie_id'] ?>">RESERVE</a>
                    </h2>
                </div>
            </section>
            <?php endforeach; ?>

        </section>
    </main>
</body>
</html>

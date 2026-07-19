<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Auth/Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_ticket') {
    $reservation_id = (int)$_POST['reservation_id'];
    
    $cancel_stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ? AND user_id = ?");
    $cancel_stmt->bind_param("ii", $reservation_id, $user_id);
    
    if ($cancel_stmt->execute()) {
        $message = "Your reservation has been successfully cancelled.";
        $messageType = "success";
    } else {
        $message = "Failed to cancel reservation. Please try again.";
        $messageType = "error";
    }
    $cancel_stmt->close();
}
$upcoming_query = "SELECT r.reservation_id, r.total_amount, r.status,
                          m.title, m.poster_url,
                          t.name AS theater_name, s.screen_name,
                          sch.show_date, sch.show_time,
                          GROUP_CONCAT(CONCAT(st.seat_row, st.seat_number) SEPARATOR ', ') AS seats
                   FROM reservations r
                   JOIN schedules sch ON r.schedule_id = sch.schedule_id
                   JOIN movies m ON sch.movie_id = m.movie_id
                   JOIN screens s ON sch.screen_id = s.screen_id
                   JOIN theaters t ON s.theater_id = t.theater_id
                   LEFT JOIN reservation_seats rs ON r.reservation_id = rs.reservation_id
                   LEFT JOIN seats st ON rs.seat_id = st.seat_id
                   WHERE r.user_id = ? AND r.status != 'cancelled' AND sch.show_date >= CURRENT_DATE
                   GROUP BY r.reservation_id
                   ORDER BY sch.show_date ASC, sch.show_time ASC";

$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$history_query = "SELECT r.reservation_id, r.total_amount, r.status,
                         m.title, m.poster_url,
                         t.name AS theater_name, s.screen_name,
                         sch.show_date, sch.show_time,
                         GROUP_CONCAT(CONCAT(st.seat_row, st.seat_number) SEPARATOR ', ') AS seats
                  FROM reservations r
                  JOIN schedules sch ON r.schedule_id = sch.schedule_id
                  JOIN movies m ON sch.movie_id = m.movie_id
                  JOIN screens s ON sch.screen_id = s.screen_id
                  JOIN theaters t ON s.theater_id = t.theater_id
                  LEFT JOIN reservation_seats rs ON r.reservation_id = rs.reservation_id
                  LEFT JOIN seats st ON rs.seat_id = st.seat_id
                  WHERE r.user_id = ? AND (r.status = 'cancelled' OR sch.show_date < CURRENT_DATE)
                  GROUP BY r.reservation_id
                  ORDER BY sch.show_date DESC, sch.show_time DESC";

$stmt2 = $conn->prepare($history_query);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$history_tickets = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/My_Tickets.css">
    <title>CineReserve - My Tickets</title>
</head>
<body>
    <nav class="Navigation">
        <a href="Home.php">
            <img src="Assets/UI-icons/Logo.png" class="Logo" alt="Logo" width="150px">
        </a>
        <ul>
            <li>
                <img src="Assets/UI-icons/Reservation.png" class="Reservation-icon" width="30px">
                <a href="Reservation_List.php">Movies</a>
            </li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <img src="" class="Tickets-icon" width="26px">
                    <a href="My_Tickets.php" style="font-weight: bold; color: #bd5b62;">My Tickets</a>
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
        <h2 class="Page-title">My Tickets</h2>

        <?php if (!empty($message)): ?>
            <div class="message-box <?= $messageType === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- UPCOMING TICKETS -->
        <h3 class="Section-title">Upcoming Reservations</h3>
        <div class="tickets-container">
            <?php if (empty($upcoming_tickets)): ?>
                <p class="empty-state">You have no upcoming movies. <a href="Reservation_List.php">Browse movies here!</a></p>
            <?php else: ?>
                <?php foreach ($upcoming_tickets as $ticket): ?>
                    <div class="ticket-card">
                        <div class="ticket-poster">
                            <img src="<?= htmlspecialchars($ticket['poster_url']) ?>" alt="Poster">
                        </div>
                        <div class="ticket-details">
                            <h2><?= htmlspecialchars($ticket['title']) ?></h2>
                            <p><strong>Theater:</strong> <?= htmlspecialchars($ticket['theater_name']) ?> (<?= htmlspecialchars($ticket['screen_name']) ?>)</p>
                            <p><strong>Date & Time:</strong> <?= date('F j, Y', strtotime($ticket['show_date'])) ?> at <?= date('g:i A', strtotime($ticket['show_time'])) ?></p>
                            <p><strong>Seats:</strong> <?= htmlspecialchars($ticket['seats']) ?></p>
                            <p><strong>Total:</strong> ₱<?= number_format($ticket['total_amount'], 2) ?></p>
                        </div>
                        <div class="ticket-actions stub">
                            <span class="status confirmed"><?= strtoupper($ticket['status']) ?></span>
                            <form method="POST" action="My_Tickets.php" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                                <input type="hidden" name="action" value="cancel_ticket">
                                <input type="hidden" name="reservation_id" value="<?= $ticket['reservation_id'] ?>">
                                <button type="submit" class="cancel-btn">Cancel Ticket</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- PAST & CANCELLED TICKETS -->
        <h3 class="Section-title" style="margin-top: 50px;">History</h3>
        <div class="tickets-container opacity-reduced">
            <?php if (empty($history_tickets)): ?>
                <p class="empty-state">No past booking records found.</p>
            <?php else: ?>
                <?php foreach ($history_tickets as $ticket): ?>
                    <div class="ticket-card history-card">
                        <div class="ticket-poster">
                            <img src="<?= htmlspecialchars($ticket['poster_url']) ?>" alt="Poster">
                        </div>
                        <div class="ticket-details">
                            <h2><?= htmlspecialchars($ticket['title']) ?></h2>
                            <p><strong>Theater:</strong> <?= htmlspecialchars($ticket['theater_name']) ?> (<?= htmlspecialchars($ticket['screen_name']) ?>)</p>
                            <p><strong>Date & Time:</strong> <?= date('F j, Y', strtotime($ticket['show_date'])) ?> at <?= date('g:i A', strtotime($ticket['show_time'])) ?></p>
                            <p><strong>Seats:</strong> <?= htmlspecialchars($ticket['seats']) ?></p>
                        </div>
                        <div class="ticket-actions stub">
                            <?php if ($ticket['status'] === 'cancelled'): ?>
                                <span class="status cancelled">CANCELLED</span>
                            <?php else: ?>
                                <span class="status completed">COMPLETED</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
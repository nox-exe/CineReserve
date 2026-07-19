<?php
session_start();
require 'db.php';

$movie_id = $_GET['movie_id'] ?? 1;
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['user_id'])) {
        $message = "You must be logged in to reserve seats.";
        $messageType = "error";
    } else {
        $user_id = $_SESSION['user_id'];
        $post_schedule_id = (int)($_POST['schedule_id'] ?? 0);

        $seat_ids_raw = $_POST['seat_ids'] ?? '';
        $seat_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $seat_ids_raw)))));
        $seat_count = count($seat_ids);

        if ($post_schedule_id <= 0 || $seat_count === 0) {
            $message = "Please choose a schedule and at least one seat.";
            $messageType = "error";
        } else {
            $sched_info_stmt = $conn->prepare("SELECT ticket_price, screen_id FROM schedules WHERE schedule_id = ?");
            $sched_info_stmt->bind_param("i", $post_schedule_id);
            $sched_info_stmt->execute();
            $sched_info = $sched_info_stmt->get_result()->fetch_assoc();
            $sched_info_stmt->close();

            if (!$sched_info) {
                $message = "Error: Invalid schedule selected.";
                $messageType = "error";
            } else {
                $ticket_price = $sched_info['ticket_price'];
                $screen_id = $sched_info['screen_id'];

                $placeholders = implode(',', array_fill(0, $seat_count, '?'));
                $seat_lookup_stmt = $conn->prepare(
                    "SELECT seat_id FROM seats WHERE screen_id = ? AND seat_id IN ($placeholders)"
                );
                $types = "i" . str_repeat("i", $seat_count);
                $params = array_merge([$screen_id], $seat_ids);
                $seat_lookup_stmt->bind_param($types, ...$params);
                $seat_lookup_stmt->execute();
                $seat_rows = $seat_lookup_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $seat_lookup_stmt->close();

                if (count($seat_rows) !== $seat_count) {
                    $message = "Error: One or more selected seats are invalid for this schedule.";
                    $messageType = "error";
                } else {
                    $total_amount = $seat_count * $ticket_price;

                    $check_query = "SELECT COUNT(*) as booked FROM reservation_seats rs
                                    JOIN reservations r ON rs.reservation_id = r.reservation_id
                                    WHERE r.schedule_id = ? AND r.status != 'cancelled' AND rs.seat_id IN ($placeholders)";
                    $check_stmt = $conn->prepare($check_query);
                    $check_types = "i" . str_repeat("i", $seat_count);
                    $check_params = array_merge([$post_schedule_id], $seat_ids);
                    $check_stmt->bind_param($check_types, ...$check_params);
                    $check_stmt->execute();
                    $booked_count = $check_stmt->get_result()->fetch_assoc()['booked'];
                    $check_stmt->close();

                    if ($booked_count > 0) {
                        $message = "Error: One or more of those seats are already booked!";
                        $messageType = "error";
                    } else {
                        $conn->begin_transaction();
                        try {
                            $res_stmt = $conn->prepare("INSERT INTO reservations (user_id, schedule_id, total_amount, status) VALUES (?, ?, ?, 'confirmed')");
                            $res_stmt->bind_param("iid", $user_id, $post_schedule_id, $total_amount);
                            $res_stmt->execute();
                            $reservation_id = $conn->insert_id;
                            $res_stmt->close();

                            $seat_link_stmt = $conn->prepare("INSERT INTO reservation_seats (reservation_id, seat_id) VALUES (?, ?)");
                            foreach ($seat_ids as $sid) {
                                $seat_link_stmt->bind_param("ii", $reservation_id, $sid);
                                $seat_link_stmt->execute();
                            }
                            $seat_link_stmt->close();

                            $conn->commit();
                            $message = "Success! Reserved $seat_count seat(s) for ₱" . number_format($total_amount, 2) . ".";
                            $messageType = "success";
                        } catch (Exception $e) {
                            $conn->rollback();
                            $message = "Database error: " . $e->getMessage();
                            $messageType = "error";
                        }
                    }
                }
            }
        }
    }
}

$movie_stmt = $conn->prepare("SELECT * FROM movies WHERE movie_id = ?");
$movie_stmt->bind_param("i", $movie_id);
$movie_stmt->execute();
$movie = $movie_stmt->get_result()->fetch_assoc();
$movie_stmt->close();

if (!$movie) { die("Movie not found."); }

$sched_query = "
    SELECT s.schedule_id, s.screen_id, t.name AS theater_name, s.show_date, s.show_time, s.ticket_price
    FROM schedules s
    JOIN screens scr ON s.screen_id = scr.screen_id
    JOIN theaters t ON scr.theater_id = t.theater_id
    WHERE s.movie_id = ?
    ORDER BY s.show_date, s.show_time
";
$sched_stmt = $conn->prepare($sched_query);
$sched_stmt->bind_param("i", $movie_id);
$sched_stmt->execute();
$schedules = $sched_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sched_stmt->close();

$seatsData = [];
$screenSeatsCache = [];

foreach ($schedules as $sched) {
    $sid = (int)$sched['schedule_id'];
    $screen_id = (int)$sched['screen_id'];

    if (!isset($screenSeatsCache[$screen_id])) {
        $seat_q = $conn->prepare("SELECT seat_id, seat_row, seat_number FROM seats WHERE screen_id = ? ORDER BY seat_row, seat_number");
        $seat_q->bind_param("i", $screen_id);
        $seat_q->execute();
        $screenSeatsCache[$screen_id] = $seat_q->get_result()->fetch_all(MYSQLI_ASSOC);
        $seat_q->close();
    }

    $booked_q = $conn->prepare(
        "SELECT rs.seat_id FROM reservation_seats rs
         JOIN reservations r ON rs.reservation_id = r.reservation_id
         WHERE r.schedule_id = ? AND r.status != 'cancelled'"
    );
    $booked_q->bind_param("i", $sid);
    $booked_q->execute();
    $bookedSet = array_flip(array_column($booked_q->get_result()->fetch_all(MYSQLI_ASSOC), 'seat_id'));
    $booked_q->close();

    $seatList = [];
    foreach ($screenSeatsCache[$screen_id] as $s) {
        $seatList[] = [
            'id'       => (int)$s['seat_id'],
            'row'      => $s['seat_row'],
            'num'      => (int)$s['seat_number'],
            'price'    => round($sched['ticket_price'], 2),
            'occupied' => isset($bookedSet[$s['seat_id']]),
        ];
    }

    $seatsData[$sid] = [
        'ticket_price' => (float)$sched['ticket_price'],
        'seats'        => $seatList,
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Reservation.css">
    <title>Reserve - <?= htmlspecialchars($movie['title']) ?></title>
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
        <h2 class="title">Reservation</h2>

        <?php if (!empty($message)): ?>
            <div style="padding: 15px; margin-bottom: 20px; border-radius: 10px; font-family: 'myFont', serif; font-size: 18px; color: white; background-color: <?= $messageType === 'success' ? '#4CAF50' : '#bd5b62' ?>;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <section class="reservation-container">
            <section class="First-Movie">
                <section class="First">

                    <div class="header">
                        <div class="First-img-container">
                            <img src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="Poster">
                        </div>

                        <div class="Movie-info">
                            <h2><?= htmlspecialchars($movie['title']) ?></h2>
                            <p><?= htmlspecialchars($movie['description']) ?></p>
                        </div>

                        <div class="Price-container" id="basePriceTag">
                            ₱<?= !empty($schedules) ? number_format($schedules[0]['ticket_price'], 2) : 'TBD' ?>
                        </div>
                    </div>

                    <div class="body-container">
                        <div class="details">

                            <form method="POST" action="Reservation.php?movie_id=<?= $movie_id ?>" id="reservationForm">

                                <div class="time-container">
                                    <label for="schedule_id">Choose Schedule:</label>
                                    <select id="schedule_id" name="schedule_id" required style="width: 100%; padding: 10px; border-radius: 12px; font-family: 'myFont';">
                                        <?php if (empty($schedules)): ?>
                                            <option value="">No schedules available</option>
                                        <?php else: ?>
                                            <?php foreach ($schedules as $sched): ?>
                                                <option value="<?= $sched['schedule_id'] ?>" data-price="<?= $sched['ticket_price'] ?>">
                                                    <?= htmlspecialchars($sched['theater_name']) ?> |
                                                    <?= date('M j, Y', strtotime($sched['show_date'])) ?> at
                                                    <?= date('g:i A', strtotime($sched['show_time'])) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="seat-container">
                                    <h2>Choose Seating</h2>

                                    <ul class="seat-legend">
                                        <li><span class="seat legend-swatch"></span> Available</li>
                                        <li><span class="seat legend-swatch selected"></span> Selected</li>
                                        <li><span class="seat legend-swatch occupied"></span> Occupied</li>
                                    </ul>

                                    <div class="screen-visual">SCREEN</div>

                                    <div class="seat-map" id="seatMap">
                                        <!-- populated by JS -->
                                    </div>

                                    <p class="seat-summary">
                                        You have selected <span id="seatCount">0</span> seat(s) for a total of
                                        ₱<span id="seatTotal">0.00</span>
                                    </p>

                                    <input type="hidden" id="selected_seats" name="seat_ids" value="">

                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button type="submit" class="confirm-seat" id="confirmBtn" disabled>Confirm Seat</button>
                                    <?php else: ?>
                                        <button type="button" class="confirm-seat" onclick="window.location.href='Auth/Login.php?msg=Please log in to make a reservation.'">Log In to Reserve</button>
                                    <?php endif; ?>

                                </div>
                            </form>

                        </div>

                        <div class="Reserve-container"></div>
                    </div>

                </section>
            </section>
        </section>
    </main>

    <script>
        window.seatsData = <?= json_encode($seatsData) ?>;
    </script>
    <script src="JS/Reservation.js"></script>
</body>
</html>
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
        $post_schedule_id = (int)$_POST['schedule_id'];
        $row = $_POST['row'];
        $start_seat_num = (int)$_POST['seat_num'];
        $seat_count = (int)$_POST['seat_count'];

        $sched_info_stmt = $conn->prepare("SELECT ticket_price, screen_id FROM schedules WHERE schedule_id = ?");
        $sched_info_stmt->bind_param("i", $post_schedule_id);
        $sched_info_stmt->execute();
        $sched_info = $sched_info_stmt->get_result()->fetch_assoc();
        $sched_info_stmt->close();

        if ($sched_info) {
            $total_amount = $sched_info['ticket_price'] * $seat_count;
            $screen_id = $sched_info['screen_id'];
            $end_seat_num = $start_seat_num + $seat_count - 1;
            if ($end_seat_num > 12) {
                $message = "Error: Not enough seats in this row starting from Seat $start_seat_num.";
                $messageType = "error";
            } else {
                $seat_ids = [];
                $seat_stmt = $conn->prepare("SELECT seat_id FROM seats WHERE screen_id = ? AND seat_row = ? AND seat_number BETWEEN ? AND ?");
                $seat_stmt->bind_param("isii", $screen_id, $row, $start_seat_num, $end_seat_num);
                $seat_stmt->execute();
                $seat_result = $seat_stmt->get_result();
                while ($seat_row = $seat_result->fetch_assoc()) {
                    $seat_ids[] = $seat_row['seat_id'];
                }
                $seat_stmt->close();

                if (count($seat_ids) !== $seat_count) {
                    $message = "Error: Could not locate those exact seats in the system.";
                    $messageType = "error";
                } else {
                    $placeholders = implode(',', array_fill(0, count($seat_ids), '?'));
                    $check_query = "SELECT COUNT(*) as booked FROM reservation_seats rs 
                                    JOIN reservations r ON rs.reservation_id = r.reservation_id 
                                    WHERE r.schedule_id = ? AND r.status != 'cancelled' AND rs.seat_id IN ($placeholders)";
                    
                    $check_stmt = $conn->prepare($check_query);
                    $types = "i" . str_repeat("i", count($seat_ids));
                    $params = array_merge([$post_schedule_id], $seat_ids);
                    $check_stmt->bind_param($types, ...$params);
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
        } else {
            $message = "Error: Invalid schedule selected.";
            $messageType = "error";
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
    SELECT s.schedule_id, t.name AS theater_name, s.show_date, s.show_time, s.ticket_price
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Reservation.css">
    <title>Reserve - <?= htmlspecialchars($movie['title']) ?></title>
</head>
<body>
    <nav class="Navigation">
        <a href="Home.php">
            <img src="" class="Logo" alt="Logo">
        </a>
        <ul>
            <li>
                <img src="Assets/UI-icons/Reservation.png" class="Reservation-icon" width="30px">
                <a href="Reservation_list.php">Reservation</a>
            </li>
            <li class="Logout">
                <img src="Assets/UI-icons/Logout.png" class="Logout-icon" width="26px">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="Logout.php">Log Out</a>
                <?php else: ?>
                    <a href="Login.php">Log In</a>
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

                        <div class="Price-container">
                            ₱<?= !empty($schedules) ? number_format($schedules[0]['ticket_price'], 2) : 'TBD' ?>
                        </div>
                    </div>

                    <div class="body-container">
                        <div class="details">
                            
                            <form method="POST" action="Reservation.php?movie_id=<?= $movie_id ?>">
                                
                                <div class="time-container">
                                    <label for="schedule_id">Choose Schedule:</label>
                                    <select id="schedule_id" name="schedule_id" required style="width: 100%; padding: 10px; border-radius: 12px; font-family: 'myFont';">
                                        <?php if (empty($schedules)): ?>
                                            <option value="">No schedules available</option>
                                        <?php else: ?>
                                            <?php foreach ($schedules as $sched): ?>
                                                <option value="<?= $sched['schedule_id'] ?>">
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

                                    <label>Row:</label>
                                    <select id="row" name="row" required>
                                        <option value="A">Row A</option>
                                        <option value="B">Row B</option>
                                        <option value="C">Row C</option>
                                        <option value="D">Row D</option>
                                        <option value="E">Row E</option>
                                        <option value="F">Row F</option>
                                        <option value="G">Row G</option>
                                        <option value="H">Row H</option>
                                        <option value="I">Row I (Premium)</option>
                                        <option value="J">Row J (VIP)</option>
                                    </select>

                                    <label>Starting Seat Number:</label>
                                    <select id="seat_num" name="seat_num" required>
                                        <?php for($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?= $i ?>">Seat <?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>

                                    <label>Number of Seats:</label>
                                    <select id="seat_count" name="seat_count" required>
                                        <option value="1">1 Seat</option>
                                        <option value="2">2 Seats</option>
                                        <option value="3">3 Seats</option>
                                        <option value="4">4 Seats</option>
                                        <option value="5">5 Seats</option>
                                    </select>

                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button type="submit" class="confirm-seat">Confirm Seat</button>
                                    <?php else: ?>
                                        <button type="button" class="confirm-seat" onclick="window.location.href='Login.php?msg=Please log in to make a reservation.'">Log In to Reserve</button>
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
</body>
</html>
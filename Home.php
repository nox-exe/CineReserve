<?php

require 'db.php';

$popular_query = "SELECT m.movie_id, m.title, m.description, m.duration_minutes, m.poster_url, 
                         COUNT(rs.seat_id) AS tickets_sold
                  FROM movies m
                  LEFT JOIN schedules sc ON sc.movie_id = m.movie_id
                  LEFT JOIN reservations r ON r.schedule_id = sc.schedule_id AND r.status = 'confirmed'
                  LEFT JOIN reservation_seats rs ON rs.reservation_id = r.reservation_id
                  WHERE m.status = 'now_showing'
                  GROUP BY m.movie_id
                  ORDER BY tickets_sold DESC
                  LIMIT 4";

$result_pop = $conn->query($popular_query);
$popular_movies = $result_pop->fetch_all(MYSQLI_ASSOC);

$search = $_GET['search'] ?? '';
$genre = $_GET['genre'] ?? 'All Genres';

$showing_query = "SELECT * FROM movies WHERE status IN ('now_showing', 'upcoming')";
$types = "";
$params = [];

if (!empty($search)) {
    $showing_query .= " AND (title LIKE ? OR description LIKE ?)";
    $types .= "ss";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
}

if ($genre !== 'All Genres' && !empty($genre)) {
    $showing_query .= " AND genre = ?";
    $types .= "s";
    $params[] = $genre;
}

$showing_query .= " ORDER BY release_date DESC";

$stmt = $conn->prepare($showing_query);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params); 
}

$stmt->execute();
$result_show = $stmt->get_result();
$showing_movies = $result_show->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Home.css">
    <title>CineReserve</title>
</head>
<body>
    <nav class="Navigation">
        <a href="Home.php">
        <img src="" class="Logo" alt ="Logo">
        </a>
    <ul>

        <li>
        <img src="Assets/UI-icons/Reservation.png" class="Reservation-icon" width="30px">
        <a href="Reservation_list.php">
        Reservation
        </a>
        </li>

        <li class="Logout">
        <img src="Assets/UI-icons/Logout.png" class="Logout-icon" width="26px">
        <a href="Login.php">
        Log Out
        </a>
        </li>

    </ul>
    </nav>
    <main class="Main">

    <header class="welcome-header">
            <h3><strong>Welcome</strong> this is <strong>CineReserve</strong></h3>
        </header>

<section class="Popular">

    <h2 class="Popular-movies">Popular</h2>

    <div class="Movie-layout">

        <!-- LEFT MOVIE -->
        <div class="First-Movie">
            <?php if (count($popular_movies) > 0): ?>
                <section class="First-Popular">
                    <div class="First-timer">
                        <?= htmlspecialchars($popular_movies[0]['duration_minutes']) ?> min
                    </div>
                    <div class="First-img-container">
                        <img src="<?= htmlspecialchars($popular_movies[0]['poster_url']) ?>" alt="Poster">
                    </div>
                </section>

                <div class="First-title-Container">
                    <h2><?= htmlspecialchars($popular_movies[0]['title']) ?></h2>
                </div>
            <?php else: ?>
                <p>No popular movies found.</p>
            <?php endif; ?>
        </div>


        <!-- RIGHT MOVIES -->
        <div class="Movie-list">
            <?php 
            for ($i = 1; $i < min(4, count($popular_movies)); $i++): 
            ?>
                <section class="Second-Popular">
                    <img src="<?= htmlspecialchars($popular_movies[$i]['poster_url']) ?>" alt="Poster">
                    <div class="Movie-info">
                        <h2><?= htmlspecialchars($popular_movies[$i]['title']) ?></h2>
                        <p><?= htmlspecialchars($popular_movies[$i]['description']) ?></p>
                    </div>
                    <div class="Movie-time">
                        <?= htmlspecialchars($popular_movies[$i]['duration_minutes']) ?> min
                    </div>
                </section>
            <?php endfor; ?>
        </div>
    </div>
</section>

<section class="Showing">

    <h2 class="Showing-title">Showing</h2>

    <div class="Showing-content">

        <!-- LEFT: MOVIE LIST -->
        <section class="Showing-list">
            <?php 
            for ($i = 0; $i < count($showing_movies); $i++): 
            ?>
                <section class="Showing-one">
                    <img src="<?= htmlspecialchars($showing_movies[$i]['poster_url']) ?>" alt="Poster">
                    <div class="Movie-info">
                        <h2><?= htmlspecialchars($showing_movies[$i]['title']) ?></h2>
                        <?php if($showing_movies[$i]['status'] === 'upcoming'): ?>
                            <p style="color: #bd5b62; font-weight: bold; margin-top: 2px; margin-bottom: 2px;">
                                Coming Soon: <?= date('F j, Y', strtotime($showing_movies[$i]['release_date'])) ?>
                            </p>
                        <?php else: ?>
                            <p style="color: #4CAF50; font-weight: bold; margin-top: 2px; margin-bottom: 2px;">
                                Now Showing
                            </p>
                        <?php endif; ?>

                        <p><?= htmlspecialchars($showing_movies[$i]['description']) ?></p>
                    </div>
                    <div class="Movie-time">
                        <?= htmlspecialchars($showing_movies[$i]['duration_minutes']) ?> min
                    </div>
                </section>
            <?php endfor; ?>
            
            <?php if(count($showing_movies) === 0): ?>
                <p style="color: #272727; font-family: 'myFont', serif;">No movies matched your search criteria.</p>
            <?php endif; ?>
        </section>


        <!-- RIGHT: OPTIONS -->
        <form method="GET" action="Home.php" class="Options">
            <input 
                type="text" 
                name="search"
                placeholder="Search movie..."
                class="Movie-search"
                value="<?= htmlspecialchars($search) ?>"
            >

            <select name="genre" class="Genre-select" onchange="this.form.submit()">
                <option value="All Genres" <?= $genre === 'All Genres' ? 'selected' : '' ?>>All Genres</option>
                <option value="Action" <?= $genre === 'Action' ? 'selected' : '' ?>>Action</option>
                <option value="Adventure" <?= $genre === 'Adventure' ? 'selected' : '' ?>>Adventure</option>
                <option value="Animation" <?= $genre === 'Animation' ? 'selected' : '' ?>>Animation</option>
                <option value="Comedy" <?= $genre === 'Comedy' ? 'selected' : '' ?>>Comedy</option>
                <option value="Horror" <?= $genre === 'Horror' ? 'selected' : '' ?>>Horror</option>
            </select>
            
            <button type="submit" style="display: none;">Search</button>
        </form>

    </div>

</section>
    </main>
    
</body>
</html>

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
    $conn->query("DELETE FROM movies WHERE movie_id = $delete_id");
    header("Location: Admin_Movies.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $genre = trim($_POST['genre']);
    $duration = (int)$_POST['duration_minutes'];
    $rating = trim($_POST['rating']);
    $poster_url = trim($_POST['poster_url']);
    $release_date = $_POST['release_date'];
    $status = $_POST['status'];

    if (!empty($_POST['movie_id'])) {
        $movie_id = (int)$_POST['movie_id'];
        $stmt = $conn->prepare("UPDATE movies SET title=?, description=?, genre=?, duration_minutes=?, rating=?, poster_url=?, release_date=?, status=? WHERE movie_id=?");
        $stmt->bind_param("sssissssi", $title, $description, $genre, $duration, $rating, $poster_url, $release_date, $status, $movie_id);
        
        if ($stmt->execute()) {
            $message = "Movie updated successfully!";
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO movies (title, description, genre, duration_minutes, rating, poster_url, release_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssissss", $title, $description, $genre, $duration, $rating, $poster_url, $release_date, $status);
        
        if ($stmt->execute()) {
            $message = "New movie added successfully!";
        }
        $stmt->close();
    }
}

$edit_movie = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM movies WHERE movie_id = $edit_id");
    $edit_movie = $res->fetch_assoc();
}

$movies = $conn->query("SELECT * FROM movies ORDER BY release_date DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Admin.css">
    <title>Manage Movies - Admin</title>
    <style>
        .admin-form { background: #333; padding: 25px; border-radius: 20px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-full { grid-column: span 2; }
        .admin-form label { display: block; font-size: 14px; color: #bd5b62; font-weight: bold; margin-bottom: 5px; }
        .admin-form input, .admin-form select, .admin-form textarea { width: 100%; padding: 10px; border-radius: 8px; border: none; font-family: "myFont", serif; background: #222; color: white; box-sizing: border-box; }
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
            <li><a href="Admin_Movies.php" style="font-weight: bold; color: #bd5b62;">Manage Movies</a></li>
            <li><a href="Admin_Theaters.php">Theaters & Screens</a></li>
            <li><a href="Admin_Schedules.php">Schedules</a></li>
            <li><a href="Admin_Reservations.php">Reservations</a></li>
            <li><a href="Admin_Users.php">Users</a></li>
            <li class="Logout"><a href="Auth/Logout.php">Log Out</a></li>
        </ul>
    </nav>
    <main class="Main">
        <h2 class="Page-title">Manage Movies</h2>
        
        <?php if ($message): ?>
            <div class="success-msg"><?= $message ?></div>
        <?php endif; ?>

        <!-- CREATE / UPDATE FORM -->
        <div class="admin-form">
            <h3 style="margin-top: 0; color: #fdfbfa;"><?= $edit_movie ? 'Edit Movie' : 'Add New Movie' ?></h3>
            <form method="POST" action="Admin_Movies.php">
                <input type="hidden" name="movie_id" value="<?= $edit_movie['movie_id'] ?? '' ?>">
                
                <div class="form-grid">
                    <div>
                        <label>Title</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($edit_movie['title'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Genre</label>
                        <select name="genre" required>
                            <?php 
                            $genres = ['Action', 'Adventure', 'Animation', 'Comedy', 'Horror', 'Drama', 'Sci-Fi'];
                            $current_genre = $edit_movie['genre'] ?? '';
                            foreach($genres as $g) {
                                $sel = ($g === $current_genre) ? 'selected' : '';
                                echo "<option value='$g' $sel>$g</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-full">
                        <label>Description</label>
                        <textarea name="description" rows="3" required><?= htmlspecialchars($edit_movie['description'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" required value="<?= $edit_movie['duration_minutes'] ?? '' ?>">
                    </div>
                    <div>
                        <label>Rating</label>
                        <input type="text" name="rating" required value="<?= htmlspecialchars($edit_movie['rating'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Release Date</label>
                        <input type="date" name="release_date" required value="<?= $edit_movie['release_date'] ?? '' ?>">
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status" required>
                            <option value="now_showing" <?= (isset($edit_movie['status']) && $edit_movie['status'] == 'now_showing') ? 'selected' : '' ?>>Now Showing</option>
                            <option value="upcoming" <?= (isset($edit_movie['status']) && $edit_movie['status'] == 'upcoming') ? 'selected' : '' ?>>Upcoming</option>
                            <option value="ended" <?= (isset($edit_movie['status']) && $edit_movie['status'] == 'ended') ? 'selected' : '' ?>>Ended</option>
                        </select>
                    </div>
                    <div class="form-full">
                        <label>Poster URL (Local Path or Link)</label>
                        <input type="text" name="poster_url" required value="<?= htmlspecialchars($edit_movie['poster_url'] ?? 'Assets/Movie-posters/placeholder.jpg') ?>">
                    </div>
                </div>
                
                <button type="submit"><?= $edit_movie ? 'Update Movie' : 'Save New Movie' ?></button>
                <?php if ($edit_movie): ?>
                    <a href="Admin_Movies.php" style="margin-left: 15px; color: #f1f1f1; text-decoration: none;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- MOVIE LIST TABLE -->
        <div class="data-section">
            <h3>Movie Roster</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Genre</th>
                        <th>Release Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies as $movie): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($movie['title']) ?></strong></td>
                        <td><?= htmlspecialchars($movie['genre']) ?></td>
                        <td><?= date('M j, Y', strtotime($movie['release_date'])) ?></td>
                        <td><span class="status-badge <?= $movie['status'] === 'now_showing' ? 'confirmed' : ($movie['status'] === 'upcoming' ? 'pending' : 'cancelled') ?>"><?= strtoupper(str_replace('_', ' ', $movie['status'])) ?></span></td>
                        <td>
                            <a href="Admin_Movies.php?edit=<?= $movie['movie_id'] ?>" class="action-link">Edit</a>
                            <a href="Admin_Movies.php?delete=<?= $movie['movie_id'] ?>" class="action-link delete" onclick="return confirm('Are you sure you want to delete this movie? This will delete all connected schedules and reservations.');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
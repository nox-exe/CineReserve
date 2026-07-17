<?php

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
        <a href="Home.html">
        <img src="" class="Logo">
        </a>
    <ul>

        <li>
        <img src="Assets/UI-icons/Reservation.png" class="Reservation-icon" width="30px">
        <a href="Reservation.php">
        Reservation
        </a>
        </li>

        <li>
        <img src="Assets/UI-icons/Movie.png" class="Movie-icon" width="30px">
        <a href="Upcoming.php">
        Upcoming Movies
        </a>
        </li>

        <li>
        <img src="Assets/UI-icons/Screening.png" class="Screening-icon" width="29px">
        <a href="Screening.php">
        Screening
        </a>
        </li>

        <li>
        <img src="Assets/UI-icons/Locations.png" class="Locations-icon" width="29px">
        <a href="Locations.php">
        Theather Locations
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

            <section class="First-Popular">

                <div class="First-timer">
                    
                </div>

                <div class="First-img-container">
                    <img src="Assets/Movie-posters/Minions.webp">
                </div>

            </section>

            <div class="First-title-Container">
                <h2>TITLE</h2>
            </div>

        </div>


        <!-- RIGHT MOVIES -->
        <div class="Movie-list">


            <section class="Second-Popular">

                <img src="Assets/Movie-posters/Insidious.jpg">

                <div class="Movie-info">
                    <h2>TITLE</h2>
                    <p>
                        description

                    </p>
                </div>

                <div class="Movie-time"></div>

            </section>


            <section class="Third-Popular">

                <img src="Assets/Movie-posters/Spiderman.jpg">

                <div class="Movie-info">
                    <h2>TITLE</h2>
                    <p>
                        description

                    </p>
                </div>

                <div class="Movie-time"></div>

            </section>


            <section class="Fourth-Popular">

                <img src="Assets/Movie-posters/Moana.jpg">

                <div class="Movie-info">
                    <h2>TITLE</h2>
                    <p>
                        description

                    </p>
                </div>

                <div class="Movie-time"></div>

            </section>
        </div>
    </div>
</section>

<section class="Showing">

    <h2 class="Showing-title">Showing</h2>

    <div class="Showing-content">

        <!-- LEFT: MOVIE LIST -->
        <section class="Showing-list">

            <section class="Showing-one">
                <img src="Assets/Movie-posters/Odyssey.jpg">

                <div class="Movie-info">
                    <h2>TITLE</h2>
                    <p>Description</p>
                </div>

                <div class="Movie-time">
                    
                </div>
            </section>


            <section class="Showing-two">
                <img src="Assets/Movie-posters/Doraemon.jpg">

                <div class="Movie-info">
                    <h2>TITLE</h2>
                    <p>Description</p>
                </div>

                <div class="Movie-time">
                    
                </div>
            </section>

        </section>


        <!-- RIGHT: OPTIONS -->
        <section class="Options">

            <input 
                type="text"
                placeholder="Search movie..."
                class="Movie-search"
            >


            <select class="Genre-select">

                <option>All Genres</option>
                <option>Action</option>
                <option>Adventure</option>
                <option>Comedy</option>
                <option>Animation</option>

            </select>

        </section>

    </div>

</section>
    </main>
    
</body>
</html>
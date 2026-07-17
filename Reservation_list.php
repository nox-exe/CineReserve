<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Reservation_List.css">
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

<h2 class="Reservation-title">Reservation List</h2>
<section class="reservation-container">

    <!-- FIRST MOVIE -->
    <section class="First-Movie">

        <section class="First">

            <div class="Screening">
            89 Mins
            </div>

            <div class="First-img-container">
                <img src="Assets/Movie-posters/Minions.webp">
            </div>

        </section>

        <div class="Reserve-container">
            <h2>
                <a href="Reservation_Minions.php">RESERVE</a>
            </h2>
        </div>

    </section>

    <!-- SECOND MOVIE -->
    <section class="Second-Movie">

        <section class="Second">

            <div class="Screening">
            102 Mins
            </div>

            <div class="Second-img-container">
                <img src="Assets/Movie-posters/Doraemon.jpg">
            </div>

        </section>

        <div class="Reserve-container">
            <h2>
                <a href="Reservation_Doraemon.php">RESERVE</a>
            </h2>
        </div>

    </section>

    <!-- THIRD MOVIE -->
    <section class="Third-Movie">

        <section class="Third">

            <div class="Screening">
            99 Mins
            </div>

            <div class="Third-img-container">
                <img src="Assets/Movie-posters/Insidious.jpg">
            </div>

        </section>

        <div class="Reserve-container">
            <h2>
                <a href="Reservation_Insidious.php">RESERVE</a>
            </h2>
        </div>

    </section>

    <!-- FOURTH MOVIE -->
    <section class="Fourth-Movie">

        <section class="Fourth">

            <div class="Screening">
            115 Mins
            </div>

            <div class="Fourth-img-container">
                <img src="Assets/Movie-posters/Moana.jpg">
            </div>

        </section>

        <div class="Reserve-container">
            <h2>
                <a href="Reservation_Moana.php">RESERVE</a>
            </h2>
        </div>

    </section>

    <!-- FIFTH MOVIE -->
    <section class="Fifth-Movie">

        <section class="Fifth">

            <div class="Screening">
            173 Mins
            </div>

            <div class="Fifth-img-container">
                <img src="Assets/Movie-posters/Odyssey.jpg">
            </div>

        </section>

        <div class="Reserve-container">
            <h2>
                <a href="Reservation_Odyssey.php">RESERVE</a>
            </h2>
        </div>

    </section>

    <!-- SIXTH MOVIE -->
    <section class="Sixth-Movie">

        <section class="Sixth">

            <div class="Screening">
            144 Mins
            </div>

            <div class="Sixth-img-container">
                <img src="Assets/Movie-posters/Spiderman.jpg">
            </div>

        </section>

        <div class="Reserve-container">
            <h2>
                <a href="Reservation_Spiderman.php">RESERVE</a>
            </h2>
        </div>

    </section>

</section>
    </main>

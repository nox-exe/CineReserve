<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Reservation.css">
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

<h2 class="title">Reservation</h2>
<section class="reservation-container">

    <section class="First-Movie">

        <section class="First">

        <div class="header">

            <div class="First-img-container">
                <img src="Assets/Movie-posters/Minions.webp">
            </div>


            <div class="Movie-info">
                    <h2>Minions & Monsters</h2>
                    <p>
                        Minion & Monsters” is the rambunctious, ridiculous and totally true story of how the Minions conquered Hollywood, became movie stars, lost everything, unleashed monsters onto the world and then banded together to try and save the planet from the mayhem they had just created.

                    </p>
            </div>

            <div class="Price-container">Price
            </div>
        </div>

    <div class="body-container">

    <div class="details">
        <h2>CineReserve Cubao</h2>
        
        <div class="time-container">
        <label for="time">Choose Time:</label>

        <select id="time" name="time">
            <option value="1:30 PM">1:30 - 3:00 PM</option>
            <option value="3:30 PM">3:30 - 6:00 PM</option>
            <option value="6:30 PM">6:30 - 8:00 PM</option>
        </select>
        </div>

<div class="seat-container">

    <h2>Choose Seating</h2>

    <!-- ROW -->
    <label>Row:</label>
    <select id="row">
        <option value="A">Row A</option>
        <option value="B">Row B</option>
        <option value="C">Row C</option>
        <option value="D">Row D</option>
        <option value="E">Row E</option>
    </select>


    <!-- COLUMN -->
    <label>Seat Number:</label>
    <select id="column">
        <option value="1">Seat 1</option>
        <option value="2">Seat 2</option>
        <option value="3">Seat 3</option>
        <option value="4">Seat 4</option>
        <option value="5">Seat 5</option>
        <option value="6">Seat 6</option>
        <option value="7">Seat 7</option>
        <option value="8">Seat 8</option>
    </select>

        <!-- HOW MANY SEATS -->
    <label>Number of Seats:</label>
    <select id="column">
        <option value="1">Seat 1</option>
        <option value="2">Seat 2</option>
        <option value="3">Seat 3</option>
        <option value="4">Seat 4</option>
        <option value="5">Seat 5</option>
        <option value="6">Seat 6</option>
        <option value="7">Seat 7</option>
        <option value="8">Seat 8</option>
    </select>


    <button class="confirm-seat">
        Confirm Seat
    </button>

</div>

    </div>

        <div class="Reserve-container">
            
        </div>

    </div>
        </section>

    </section>

</section>
    </main>

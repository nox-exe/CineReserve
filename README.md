# CineReserve

CineReserve is a web-based ticket reservation system that allows users to browse currently showing movies, view available schedules, select seats, and reserve tickets online. 

The system aims to simplify the ticket booking process while reducing manual seat arrangement.

## Features

- User registration and login
- Browse current and upcoming movies
- View movie schedules
- Interactive seat selection
- Online ticket reservation

## Project structure

```
CineReserve/
├── Assets/                  # Images, icons, and other static assets
├── cinereserve.sql          # Database schema and seed data
├── db.php                   # Database connection config
├── Home.php / .css          # Landing page — browse movies & schedules
├── Login.php                # User login
├── Register.php             # User registration
├── Logout.php               # Session termination
├── Reservation.php / .css   # Seat selection & booking flow
└── Reservation_list.php     # Reservation history / .css
```

## Getting started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack)
- PHP 7.4+
- MySQL / MariaDB

### Setup

1. **Clone the repository** into your server's web root (e.g. `htdocs` for XAMPP):
   ```bash
   git clone https://github.com/nox-exe/CineReserve.git
   ```

2. **Start Apache and MySQL** from your XAMPP control panel.

3. **Create the database** and import the schema:
   - Open phpMyAdmin (or your preferred MySQL client)
   - Create a database named `cinereserve`
   - Import `cinereserve.sql` into it

4. **Configure the database connection** in `db.php` if your setup differs from the defaults:
   ```php
   $servername = "127.0.0.1";
   $username   = "root";
   $password   = "";
   $dbname     = "cinereserve";
   ```

5. **Run the app** by visiting:
   ```
   http://localhost/CineReserve/Home.php
   ```

## Usage

- **Customers** register or log in, browse movies on the home page, pick a schedule, select seats, and confirm a reservation.

## Roadmap

- [ ] Reservation history
- [ ] Payment integration
- [ ] Email confirmation for reservations
- [ ] Ticket cancellation/refund flow
- [ ] Responsive UI improvements
- [ ] Admin Dashboard


## Contributing

This is currently a school project. Issues and pull requests are welcome if you'd like to contribute.

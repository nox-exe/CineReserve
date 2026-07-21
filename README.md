# CineReserve

CineReserve is a web-based ticket reservation system that allows users to browse currently showing movies, view available schedules, select seats, and reserve tickets online. 

The system aims to simplify the ticket booking process while reducing manual seat arrangement.

## Getting started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack)
- PHP 7.4+
- MySQL

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


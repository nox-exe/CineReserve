<?php
session_start();
require '../db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill out all required fields.";
    } else if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'customer';

            $insert_stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone_number, role) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssss", $full_name, $email, $hashed_password, $phone_number, $role);
            
            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now <a href='Login.php'>Log In</a>.";
            } else {
                $error = "Registration failed. Please try again.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CineReserve</title>
</head>
<body>
    <div class="register-container">
        <h2>Create an Account</h2>
        
        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php else: ?>
            <form method="POST" action="Register.php">
                <div>
                    <label>Full Name:</label><br>
                    <input type="text" name="full_name" required>
                </div>
                <br>
                <div>
                    <label>Email:</label><br>
                    <input type="email" name="email" required>
                </div>
                <br>
                <div>
                    <label>Phone Number:</label><br>
                    <input type="text" name="phone_number">
                </div>
                <br>
                <div>
                    <label>Password:</label><br>
                    <input type="password" name="password" required>
                </div>
                <br>
                <div>
                    <label>Confirm Password:</label><br>
                    <input type="password" name="confirm_password" required>
                </div>
                <br>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="Login.php">Log In here</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>
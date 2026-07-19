<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Auth/Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $phone_number, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $message = "Profile details updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating profile. Email might already be in use.";
            $messageType = "error";
        }
        $stmt->close();
    }
    
    elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_pw = $_POST['current_password'];
        $new_pw = $_POST['new_password'];
        $confirm_pw = $_POST['confirm_password'];
        
        if ($new_pw !== $confirm_pw) {
            $message = "New passwords do not match!";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (password_verify($current_pw, $user_data['password_hash'])) {
                $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $new_hash, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                $message = "Password changed successfully!";
                $messageType = "success";
            } else {
                $message = "Incorrect current password.";
                $messageType = "error";
            }
        }
    }
    
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_account') {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        session_unset();
        session_destroy();
        header("Location: Home.php");
        exit();
    }
}

$stmt = $conn->prepare("SELECT full_name, email, phone_number FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Profile.css">
    <title>CineReserve - My Profile</title>
</head>
<body>
    <nav class="Navigation">
        <a href="Home.php">
            <img src="Assets/UI-icons/Logo.png" class="Logo" alt="Logo" width="150px">
        </a>
        <ul>
            <li>
                <img src="Assets/UI-icons/Reservation.png" class="Reservation-icon" width="30px">
                <a href="Reservation_List.php">Movies</a>
            </li>
            <li>
                <img src="" class="Tickets-icon" width="26px">
                <a href="My_Tickets.php">My Tickets</a>
            </li>
            <li>
                <img src="" class="Profile-icon" width="26px">
                <a href="Profile.php" style="font-weight: bold; color: #bd5b62;">Profile</a>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li>
                    <img src="" class="Admin-icon" width="26px">
                    <a href="Admin_Dashboard.php" style="color: #bd5b62; font-weight: bold;">Admin Panel</a>
                </li>
            <?php endif; ?>
            <li class="Logout">
                <img src="Assets/UI-icons/Logout.png" class="Logout-icon" width="26px">
                <a href="Auth/Logout.php">Log Out</a>
            </li>
        </ul>
    </nav>
    
    <main class="Main">
        <h2 class="Profile-title">Account Settings</h2>

        <?php if (!empty($message)): ?>
            <div class="message-box <?= $messageType === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <div class="settings-card">
                <h3>Personal Information</h3>
                <form method="POST" action="Profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>">
                    </div>

                    <button type="submit" class="primary-btn">Save Changes</button>
                </form>
            </div>

            <div class="settings-card">
                <h3>Change Password</h3>
                <form method="POST" action="Profile.php">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="primary-btn">Update Password</button>
                </form>
            </div>

            <div class="settings-card danger-zone">
                <h3 style="color: #bd5b62;">Danger Zone</h3>
                <p>Once you delete your account, there is no going back. Please be certain.</p>
                <form method="POST" action="Profile.php" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This will cancel all pending reservations.');">
                    <input type="hidden" name="action" value="delete_account">
                    <button type="submit" class="danger-btn">Delete Account</button>
                </form>
            </div>

        </div>
    </main>
</body>
</html>
<?php
session_start();
include('config.php'); // Your DB connection settings

// Check if the user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirect to login if not logged in
    exit();
}

// Fetch user data from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Update profile logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $new_password = $_POST['new_password'];

    // Check if the password needs to be updated
    if (!empty($new_password)) {
        // Hash the new password
        $password = password_hash($new_password, PASSWORD_BCRYPT);
    } else {
        $password = $user['password']; // Keep the old password if no new password provided
    }

    // Update user profile in the database
    $update_query = "UPDATE users SET name = :name, email = :email, phone = :phone, password = :password WHERE id = :user_id";
    $stmt = $pdo->prepare($update_query);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        header('Location: task-info.php'); // Redirect after successful update
        exit();
    } else {
        $_SESSION['error'] = "Failed to update profile!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
    <div class="container">
        <h2>Edit Your Profile</h2>
        
        <!-- Display success/error messages -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success']; ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php elseif(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" action="edit-profile.php">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Current Password:</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your current password (if changing)">
            </div>
            <div class="form-group">
                <label for="new_password">New Password (Optional):</label>
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password if you want to change it">
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</body>
</html>

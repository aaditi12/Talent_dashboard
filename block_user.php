<?php
require 'db_connection.php'; // or wherever your DB connection is

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);

    // Sanitize input
    $username = mysqli_real_escape_string($conn, $username);

    $query = "UPDATE tbl_admin SET status='blocked' WHERE fullname='$username'";
    if (mysqli_query($conn, $query)) {
        echo "User '$username' has been blocked successfully.";
    } else {
        echo "Failed to block user '$username'.";
    }
} else {
    echo "Invalid request.";
}
?>

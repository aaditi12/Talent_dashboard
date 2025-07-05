<?php
require 'authentication.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['admin_id'] ?? null;
$security_key = $_SESSION['security_key'] ?? null;

if (!$user_id || !$security_key) {
    header('Location: index.php');
    exit();
}

// Ensure $obj_admin is defined and get DB connection
if (!isset($obj_admin)) {
    die("Database connection not established.");
}

$db = $obj_admin->getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['aten_id']) && isset($_POST['status'])) {
    $aten_id = (int) $_POST['aten_id'];
    $status = trim($_POST['status']);

    try {
        $sql = "UPDATE attendance_info SET status = :status WHERE aten_id = :aten_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':aten_id', $aten_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            header("Location: attendance-info.php");
            exit();
        } else {
            throw new Exception("Failed to update status.");
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    header("Location: attendance-info.php");
    exit();
}
?>

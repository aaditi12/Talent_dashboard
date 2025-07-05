<?php
require 'authentication.php'; 

date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;

if (!$user_id) {
    echo json_encode(['clocked_in' => false]);
    exit();
}

$current_date = date('Y-m-d');
$sql = "SELECT in_time, out_time FROM attendance_info WHERE atn_user_id = :user_id AND DATE(in_time) = :current_date ORDER BY aten_id DESC LIMIT 1";
$stmt = $obj_admin->db->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if ($record) {
    if (!empty($record['in_time']) && empty($record['out_time'])) {
        echo json_encode(['clocked_in' => true]);
    } else {
        echo json_encode(['clocked_in' => false]);
    }
} else {
    echo json_encode(['clocked_in' => false]);
}
?>

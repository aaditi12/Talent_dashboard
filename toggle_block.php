<?php
require 'authentication.php';      // your session + role check
header('Content-Type: application/json');

session_start();

// Only admins may block/unblock
if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$user_id = intval($_POST['user_id']);
$db     = $obj_admin->db;

// 1) Fetch current blocked state
$stmt = $db->prepare("SELECT clockin_blocked FROM tbl_admin WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$current = $stmt->fetchColumn();

if ($current === false) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// 2) Toggle it
$newState = $current ? 0 : 1;
$upd = $db->prepare("UPDATE tbl_admin SET clockin_blocked = :new WHERE user_id = :uid");
$success = $upd->execute([
    ':new' => $newState,
    ':uid' => $user_id
]);

if (!$success) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not update user']);
    exit;
}

// 3) Return JSON result
echo json_encode(['blocked' => (bool)$newState]);

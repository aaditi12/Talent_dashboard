<?php
require 'authentication.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json');

$uid = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);
$event = $data['event'] ?? '';
$ts    = date('Y-m-d H:i:s', intval($data['ts']/1000));

if ($uid && in_array($event, ['heartbeat','face_lost'])) {
    $stmt = $obj_admin->db->prepare("
      INSERT INTO proctor_logs (user_id, event_type, event_time)
      VALUES (:uid, :evt, :etime)
    ");
    $stmt->execute([
      ':uid'   => $uid,
      ':evt'   => $event,
      ':etime' => $ts
    ]);
    echo json_encode(['status'=>'ok']);
} else {
    http_response_code(400);
    echo json_encode(['status'=>'error']);
}

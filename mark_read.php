<?php
require 'authentication.php';
if (!isset($_POST['aten_id'])) exit;
$sql = "UPDATE attendance_info SET is_read = 1 WHERE aten_id = :id";
$stmt = $obj_admin->db->prepare($sql);
$stmt->bindParam(':id', $_POST['aten_id'], PDO::PARAM_INT);
$stmt->execute();
echo json_encode(['success'=>true]);

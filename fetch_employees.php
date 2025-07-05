<?php
require 'authentication.php'; // Admin authentication check

// Get the database connection via the public method
$db = $obj_admin->getDb();

$sql = "SELECT DISTINCT fullname FROM tbl_admin";
$stmt = $db->query($sql);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the employee data as JSON
echo json_encode($employees);
?>


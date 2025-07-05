<?php
require 'authentication.php'; // Admin authentication check

// Ensure user is authenticated
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['security_key'])) {
    http_response_code(403);
    exit('Unauthorized access.');
}

// Check if Admin_Class has a public method for DB access
if (!method_exists($obj_admin, 'getDb')) {
    http_response_code(500);
    exit('Database access method not available.');
}

$db = $obj_admin->getDb(); // Use the getter method to access PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['remarks'])) {
    $task_id = intval($_POST['task_id']);
    $remarks = trim($_POST['remarks']);

    if ($task_id <= 0 || empty($remarks)) {
        http_response_code(400);
        exit('Invalid task ID or empty remarks.');
    }

    // Check if task exists
    $sql_check = "SELECT task_id FROM task_info WHERE task_id = :task_id";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->execute(['task_id' => $task_id]);

    if (!$stmt_check->fetch()) {
        http_response_code(404);
        exit('Task not found.');
    }

    // Update the remarks
    $sql_update = "UPDATE task_info SET remarks = :remarks WHERE task_id = :task_id";
    $stmt_update = $db->prepare($sql_update);

    if ($stmt_update->execute(['remarks' => $remarks, 'task_id' => $task_id])) {
        echo htmlspecialchars($remarks, ENT_QUOTES, 'UTF-8');
        exit();
    } else {
        http_response_code(500);
        exit('Failed to update remarks.');
    }
}

http_response_code(405);
echo 'Method Not Allowed.';
exit();

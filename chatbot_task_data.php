<?php
require_once 'db_connection.php';
header('Content-Type: application/json');

// Check if task_info table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'task_info'");
if ($tableCheck->num_rows === 0) {
    echo json_encode(["error" => "Table 'task_info' not found"]);
    exit;
}

// Fetch actual task data
$sql = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) / 60, 2) AS avg_hours
        FROM task_info";

$result = $conn->query($sql);
if ($result && $data = $result->fetch_assoc()) {
    // If there are no tasks at all, respond with error
    if ($data["total"] == 0) {
        echo json_encode(["error" => "No tasks available"]);
        exit;
    }

    echo json_encode([
        "completed" => $data["completed"],
        "pending" => $data["pending"],
        "average_time" => $data["avg_hours"] . " hrs"
    ]);
} else {
    echo json_encode(["error" => "Failed to fetch task data"]);
}
?>

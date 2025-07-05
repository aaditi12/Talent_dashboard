<?php
require 'authentication.php';
require 'db_connection.php'; // Ensure this connects to $pdo

$date = isset($_GET['date']) ? $_GET['date'] : '';
$assignedTo = isset($_GET['assigned_to']) ? $_GET['assigned_to'] : '';

$query = "SELECT * FROM tasks WHERE 1=1";
$params = [];

if (!empty($date)) {
    $query .= " AND DATE(start_time) = :date";
    $params[':date'] = $date;
}

if (!empty($assignedTo)) {
    $query .= " AND assigned_to = :assigned_to";
    $params[':assigned_to'] = $assignedTo;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($tasks) {
    $i = 1;
    foreach ($tasks as $task) {
        echo "<tr>";
        echo "<td>{$i}</td>";
        echo "<td>" . htmlspecialchars($task['task_title']) . "</td>";
        echo "<td>" . htmlspecialchars($task['assigned_to']) . "</td>";
        echo "<td>" . htmlspecialchars($task['start_time']) . "</td>";
        echo "<td>" . htmlspecialchars($task['end_time']) . "</td>";
        echo "<td>" . htmlspecialchars($task['status']) . "</td>";
        echo "<td>" . htmlspecialchars($task['time_taken']) . "</td>";
        echo "</tr>";
        $i++;
    }
} else {
    echo "<tr><td colspan='7'>No tasks found for selected filters.</td></tr>";
}
?>



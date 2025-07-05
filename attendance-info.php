  <?php     
require 'authentication.php'; // Authentication check

date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : null;
$security_key = isset($_SESSION['security_key']) ? $_SESSION['security_key'] : null;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;

if (!$user_id || !$security_key) {
    header('Location: index.php');
    exit();
}


// Handle Delete Attendance Record
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_attendance'])) {
    $aten_id = $_POST['aten_id'];
    $sql_delete = "DELETE FROM attendance_info WHERE aten_id = :aten_id";
    $stmt = $obj_admin->db->prepare($sql_delete);
    $stmt->bindParam(':aten_id', $aten_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        header("Location: attendance-info.php");
        exit();
    } else {
        die("Error deleting attendance record: " . implode(" | ", $stmt->errorInfo()));
    }
}

// Handle manual block clock-in

// Handle Clock In
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['clock_in'])) {
    if ($block_clock_in) {
        echo "<script>alert('You must add remarks for working less than 8 hours yesterday before clocking in today!'); window.location.href='attendance-info.php';</script>";
        exit();
    }

    // Check if employee is connected to the correct office WiFi IP address
    if ($user_ip !== $office_ip) {
        echo "<script>alert('You can only clock in when connected to the office WiFi.'); window.location.href='attendance-info.php';</script>";
        exit();
    }

    $status = isset($_POST['status']) ? $_POST['status'] : 'Work from Office';
    $in_time = date("Y-m-d H:i:s");
    $current_date = date("Y-m-d");

    $sql_check = "SELECT COUNT(*) FROM attendance_info WHERE atn_user_id = :user_id AND DATE(in_time) = :current_date";
    $stmt_check = $obj_admin->db->prepare($sql_check);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':current_date', $current_date, PDO::PARAM_STR);
    $stmt_check->execute();
    $clock_in_count = $stmt_check->fetchColumn();

    if ($clock_in_count > 0) {
        echo "<script>alert('You have already clocked in today!'); window.location.href='attendance-info.php';</script>";
        exit();
    }

      $sql = "INSERT INTO attendance_info (atn_user_id, in_time, status) VALUES (:user_id, :in_time, :status)";
    $stmt = $obj_admin->db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':in_time', $in_time, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);

    if ($stmt->execute()) {
        header("Location: attendance-info.php");
        exit();
    }

}

// Function to get worked hours for the previous day
function getWorkedHours($user_id, $date) {
    global $conn;
    $stmt = $conn->prepare("SELECT TIMESTAMPDIFF(HOUR, start_time, end_time) AS worked_hours FROM task_info WHERE user_id = ? AND DATE(start_time) = ?");
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['worked_hours'] : 0;
}

// Function to check if remarks are provided for the previous day
function checkRemarks($user_id, $date) {
    global $conn;
    $stmt = $conn->prepare("SELECT remarks FROM task_info WHERE user_id = ? AND DATE(start_time) = ?");
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return !empty($row['remarks']);
}

// Validation for clockin based on previous day's worked hours and remarks
function validateClockIn($user_id) {
    $previous_day = date('Y-m-d', strtotime('-1 day')); // Get the previous day's date
    $worked_hours = getWorkedHours($user_id, $previous_day);
    $has_remarks = checkRemarks($user_id, $previous_day);

    if ($worked_hours < 8 && !$has_remarks) {
        return false; // Cannot clock in if worked less than 8 hours and no remarks
    }
    return true; // Allowed to clock in
}

// If clock-in attempt is made
if (isset($_POST['clockin'])) {
    $user_id = $_SESSION['user_id'];

    // Check if the employee is allowed to clock in based on validation
    if (validateClockIn($user_id)) {
        // Proceed with clock-in (your clock-in logic)
        // Add your clock-in logic here
        echo "Clock-in successful!";
    } else {
        // Prevent clock-in and show message
        echo "You cannot clock in because you worked less than 8 hours yesterday and did not provide remarks.";
    }
}


// Check yesterday's attendance to block clock-in if necessary
$yesterday_date = date('Y-m-d', strtotime('-1 day'));

$sql_yesterday = "SELECT aten_id, in_time, out_time, remarks 
                  FROM attendance_info 
                  WHERE atn_user_id = :user_id 
                  AND DATE(in_time) = :yesterday_date 
                  ORDER BY aten_id DESC LIMIT 1";
$stmt_yesterday = $obj_admin->db->prepare($sql_yesterday);
$stmt_yesterday->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_yesterday->bindParam(':yesterday_date', $yesterday_date, PDO::PARAM_STR);
$stmt_yesterday->execute();
$yesterday_record = $stmt_yesterday->fetch(PDO::FETCH_ASSOC);

// Calculate if block is needed
$block_clock_in = false;
if ($yesterday_record) {
    if (!empty($yesterday_record['in_time']) && !empty($yesterday_record['out_time'])) {
        $in_time = strtotime($yesterday_record['in_time']);
        $out_time = strtotime($yesterday_record['out_time']);
        $hoursWorked = ($out_time - $in_time) / 3600;

        if ($hoursWorked < 8 && empty(trim($yesterday_record['remarks']))) {
            $block_clock_in = true;
        }
    }
}

// Check manual 2-day block
if (isset($_SESSION['manual_block_clockin_until']) && time() < $_SESSION['manual_block_clockin_until']) {
    $block_clock_in = true;
}

// Handle Add/Edit Remarks
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_remarks'])) {
    $aten_id = isset($_POST['aten_id']) ? $_POST['aten_id'] : '';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    if ($aten_id && $remarks) {
        $sql_remarks = "UPDATE attendance_info SET remarks = :remarks WHERE aten_id = :aten_id";
        $stmt_remarks = $obj_admin->db->prepare($sql_remarks);
        $stmt_remarks->bindParam(':remarks', $remarks, PDO::PARAM_STR);
        $stmt_remarks->bindParam(':aten_id', $aten_id, PDO::PARAM_INT);

        if ($stmt_remarks->execute()) {
            header("Location: attendance-info.php");
            exit();
        } else {
            die("Error updating remarks: " . implode(" | ", $stmt_remarks->errorInfo()));
        }
    }
}

// Handle Clock Out
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['clock_out'])) {
    $out_time = date("Y-m-d H:i:s");
    $current_date = date("Y-m-d");

    $sql_check_out = "SELECT aten_id, in_time, out_time FROM attendance_info 
                      WHERE atn_user_id = :user_id AND DATE(in_time) = :current_date 
                      ORDER BY aten_id DESC LIMIT 1";
    $stmt_check_out = $obj_admin->db->prepare($sql_check_out);
    $stmt_check_out->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check_out->bindParam(':current_date', $current_date, PDO::PARAM_STR);
    $stmt_check_out->execute();
    $row = $stmt_check_out->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if (!empty($row['out_time'])) {
            echo "<script>alert('You have already clocked out today!'); window.location.href='attendance-info.php';</script>";
            exit();
        }

        $aten_id = $row['aten_id'];
        $in_time = strtotime($row['in_time']);
        $out_time_timestamp = strtotime($out_time);
        $hoursWorked = ($out_time_timestamp - $in_time) / 3600;

        $sql_update_out_time = "UPDATE attendance_info SET out_time = :out_time WHERE aten_id = :aten_id";
        $stmt_update_out_time = $obj_admin->db->prepare($sql_update_out_time);
        $stmt_update_out_time->bindParam(':out_time', $out_time, PDO::PARAM_STR);
        $stmt_update_out_time->bindParam(':aten_id', $aten_id, PDO::PARAM_INT);
        $stmt_update_out_time->execute();

        if ($hoursWorked < 8) {
            $_SESSION['underworked_alert'] = true;
        }

        header("Location: attendance-info.php");
        exit();
    } else {
        echo "<script>alert('Please clock in before trying to clock out.'); window.location.href='attendance-info.php';</script>";
        exit();
    }
}

// Handle Clock In
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['clock_in'])) {
    if ($block_clock_in) {
        echo "<script>alert('Clock-in is blocked due to previous conditions!'); window.location.href='attendance-info.php';</script>";
        exit();
    }

    // WiFi Office Check
    if ($user_ip !== $office_ip) {
        echo "<script>alert('You can only clock in when connected to the office WiFi.'); window.location.href='attendance-info.php';</script>";
        exit();
    }

    $status = isset($_POST['status']) ? $_POST['status'] : 'Work from Office';
    $in_time = date("Y-m-d H:i:s");
    $current_date = date("Y-m-d");

    $sql_check = "SELECT COUNT(*) FROM attendance_info WHERE atn_user_id = :user_id AND DATE(in_time) = :current_date";
    $stmt_check = $obj_admin->db->prepare($sql_check);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':current_date', $current_date, PDO::PARAM_STR);
    $stmt_check->execute();
    $clock_in_count = $stmt_check->fetchColumn();

    if ($clock_in_count > 0) {
        echo "<script>alert('You have already clocked in today!'); window.location.href='attendance-info.php';</script>";
        exit();
    }

    $sql = "INSERT INTO attendance_info (atn_user_id, in_time, status) VALUES (:user_id, :in_time, :status)";
    $stmt = $obj_admin->db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':in_time', $in_time, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);

    if ($stmt->execute()) {
        header("Location: attendance-info.php");
        exit();
    }
}

// Handle Clock In
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['clock_in'])) {
    if ($block_clock_in) {
        echo "<script>alert('You must add remarks for working less than 8 hours yesterday before clocking in today!'); window.location.href='attendance-info.php';</script>";
        exit();
    }

    // Check if employee is connected to the correct office WiFi IP address
    if ($user_ip !== $office_ip) {
        echo "<script>alert('You can only clock in when connected to the office WiFi.'); window.location.href='attendance-info.php';</script>";
        exit();
    }

    $status = isset($_POST['status']) ? $_POST['status'] : 'Work from Office';
    $in_time = date("Y-m-d H:i:s");
    $current_date = date("Y-m-d");

    $sql_check = "SELECT COUNT(*) FROM attendance_info WHERE atn_user_id = :user_id AND DATE(in_time) = :current_date";
    $stmt_check = $obj_admin->db->prepare($sql_check);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':current_date', $current_date, PDO::PARAM_STR);
    $stmt_check->execute();
    $clock_in_count = $stmt_check->fetchColumn();

    if ($clock_in_count > 0) {
        echo "<script>alert('You have already clocked in today!'); window.location.href='attendance-info.php';</script>";
        exit();
    }

    $sql = "INSERT INTO attendance_info (atn_user_id, in_time, status) VALUES (:user_id, :in_time, :status)";
       // **Insert with is_read = 0**
    $stmt = $obj_admin->db->prepare(
      "INSERT INTO attendance_info
         (atn_user_id, in_time, status, is_read)
       VALUES
         (:user_id, :in_time, :status, 0)"
    );
    $stmt->execute([
      ':user_id' => $user_id,
      ':in_time' => $in_time,
      ':status'  => $status
    ]);

    header("Location: attendance-info.php");
    exit();
}



// Chatbot logic for attendance and unblock actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['chatbot_message'])) {
    $message = trim($_POST['chatbot_message']);

    if (strtolower($message) == "clock in") {
        if ($block_clock_in) {
            echo json_encode(["response" => "You cannot clock in today because of restrictions."]);
        } else {
            if ($user_ip !== $office_ip) {
                echo json_encode(["response" => "You can only clock in when connected to the office WiFi."]);
                exit();
            }

            $current_date = date("Y-m-d");
            $sql_check = "SELECT COUNT(*) FROM attendance_info WHERE atn_user_id = :user_id AND DATE(in_time) = :current_date";
            $stmt_check = $obj_admin->db->prepare($sql_check);
            $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_check->bindParam(':current_date', $current_date, PDO::PARAM_STR);
            $stmt_check->execute();
            $clock_in_count = $stmt_check->fetchColumn();

            if ($clock_in_count == 0) {
                $sql_clock_in = "INSERT INTO attendance_info (atn_user_id, in_time, status) VALUES (:user_id, :in_time, 'Work from Office')";
                $stmt_clock_in = $obj_admin->db->prepare($sql_clock_in);
                $stmt_clock_in->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_clock_in->bindParam(':in_time', $current_date . ' 09:00:00', PDO::PARAM_STR);
                $stmt_clock_in->execute();

                echo json_encode(["response" => "You have successfully clocked in for today."]);
            } else {
                echo json_encode(["response" => "You have already clocked in today."]);
            }
        }
    }
}

// Manual block for 2 days logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['manual_block_clockin'])) {
        $_SESSION['manual_block_clockin_until'] = strtotime("+2 days");
        header("Location: attendance-info.php");
        exit();
    }

    if (isset($_POST['manual_unblock_clockin'])) {
        unset($_SESSION['manual_block_clockin_until']);
        header("Location: attendance-info.php");
        exit();
    }
}




// Handle Chatbot Logic to Block Clock-In Command
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['chatbot_message'])) {
    $message = trim($_POST['chatbot_message']);

    if (strtolower($message) == "block clock in") {
        // Admin gives command to block clock-in
        $_SESSION['manual_block_clockin_until'] = strtotime("+1 hour");
        echo json_encode(["response" => "Clock-in has been blocked for the next hour."]);
        exit();
    }

    if (strtolower($message) == "unblock clock in") {
        // Admin gives command to unblock clock-in
        unset($_SESSION['manual_block_clockin_until']);
        echo json_encode(["response" => "Clock-in has been unblocked."]);
        exit();
    }
}

// Manual block for 2 days logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['manual_block_clockin'])) {
        $_SESSION['manual_block_clockin_until'] = strtotime("+2 days");
        header("Location: attendance-info.php");
        exit();
    }

    if (isset($_POST['manual_unblock_clockin'])) {
        unset($_SESSION['manual_block_clockin_until']);
        header("Location: attendance-info.php");
        exit();
    }
}


// Handle block/unblock action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_action'])) {
    if ($_POST['block_action'] === 'block') {
        $_SESSION['block_clock'] = true;
    } elseif ($_POST['block_action'] === 'unblock') {
        unset($_SESSION['block_clock']);
    }
}

// Handle Clock In
if (isset($_POST['clock_in'])) {
    if (isset($_SESSION['block_clock'])) {
        echo "<p style='color:red;'>Clock In is currently blocked.</p>";
    } else {
        // Your existing Clock In logic here
        echo "<p style='color:green;'>Clock In successful.</p>";
    }
}

// Handle Clock Out
if (isset($_POST['clock_out'])) {
    if (isset($_SESSION['block_clock'])) {
        echo "<p style='color:red;'>Clock Out is currently blocked.</p>";
    } else {
        // Your existing Clock Out logic here
        echo "<p style='color:green;'>Clock Out successful.</p>";
    }
}



// Fetch Attendance Data
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

$sql = "SELECT a.*, b.fullname, b.clockin_blocked
        FROM attendance_info a 
        LEFT JOIN tbl_admin b ON a.atn_user_id = b.user_id 
        WHERE 1=1";

$params = array();

if (!empty($status_filter)) {
    $sql .= " AND a.status = :status_filter";
    $params[':status_filter'] = $status_filter;
}
if (!empty($from_date)) {
    $sql .= " AND DATE(a.in_time) >= :from_date";
    $params[':from_date'] = $from_date;
}
if (!empty($to_date)) {
    $sql .= " AND DATE(a.in_time) <= :to_date";
    $params[':to_date'] = $to_date;
}

if ($user_role === 'employee') {
    if (isset($user_id) && !empty($user_id)) {
        $sql .= " AND a.atn_user_id = :user_id";
        $params[':user_id'] = $user_id;
    } else {
        echo "Error: User ID is missing or invalid.";
        exit;
    }
}

$sql .= " ORDER BY a.aten_id DESC";

// --- COUNT UNREAD CLOCK-INS for the admin alert ---
$stmt = $obj_admin->db->query("
  SELECT COUNT(*) AS cnt
  FROM attendance_info
  WHERE is_read = 0
");
$newCount = (int)$stmt->fetchColumn();
$stmt = $obj_admin->db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$page_name = "Attendance";
include("include/sidebar.php");
?>





<!-- Frontend HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Management</title>
<style>
#remarksModal { display: none; position: fixed; z-index: 9999; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border: 1px solid #ccc; padding: 20px; width: 300px; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
.bouncing-container { text-align: center; margin-bottom: 10px; position: relative; }
.bouncing-line { width: 150px; height: 2px; background-color: #E65200; margin: 0 auto; animation: bounceLine 1s infinite alternate; }
.bouncing-arrow { font-size: 2rem; color: #E65200; animation: bounceArrow 1s infinite alternate; margin-top: 5px; }
.bouncing-text { font-weight: bold; color: #E65200; margin-top: 5px; font-size: 18px; animation: pulseText 1.5s infinite; }
@keyframes bounceArrow { 0% { transform: translateY(0); } 100% { transform: translateY(-10px); } }
@keyframes bounceLine { 0% { transform: scaleX(1); } 100% { transform: scaleX(1.2); } }
@keyframes pulseText { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
	
	  .table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        .th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .th { background-color: #f0f0f0; }
        .pagination { margin-top: 15px; }
        .pagination a, .pagination strong {
            padding: 6px 10px;
            margin: 2px;
            text-decoration: none;
            border: 1px solid #aaa;
            border-radius: 4px;
            color: #333;
        }
        .pagination strong {
            background-color: #333;
            color: white;
        }
	
	#chatbot {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 320px;
    max-height: 500px;
    background: #f1f1f1;
    border: 2px solid #ccc;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    font-family: sans-serif;
    display: flex;
    flex-direction: column;
}
#chat-header {
    background: #4CAF50;
    color: white;
    padding: 10px;
    text-align: center;
}
#chat-body {
    flex: 1;
    padding: 10px;
    overflow-y: auto;
}
#chat-input {
    display: flex;
    border-top: 1px solid #ccc;
}
#chat-input input {
    flex: 1;
    padding: 10px;
    border: none;
}
#chat-input button {
    padding: 10px;
    background: #4CAF50;
    border: none;
    color: white;
}
.chat-message {
    margin-bottom: 10px;
}
.user-message {
    text-align: right;
    color: blue;
}
.bot-message {
    text-align: left;
    color: green;
}
	 body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }
        button {
            padding: 10px 20px;
            margin: 8px;
            font-size: 16px;
        }
        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
	
	.unread-dot {
  display: inline-block;
  width: 8px; height: 8px;
  background: red;
  border-radius: 50%;
  margin-left: 6px;
  vertical-align: middle;
}

</style>
</head>
<body>

<div class="container">
<h2 style="text-align: center; top: 0px;">Attendance Management</h2>
	<!-- MediaPipe Face Detection -->
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/face_detection.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>


<form method="get" style="margin-bottom: 40px; margin-left: 0px;">

    <label for="from_date">From:</label>
    <input type="date" name="from_date" id="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
    <label for="to_date">To:</label>
    <input type="date" name="to_date" id="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
    <button type="submit" class="btn btn-info">Filter</button>
</form>
	<form method="post" style="display: flex; align-items: center; gap: 12px; margin-bottom: 40px; margin-left: 0;">
  <!-- Block / Unblock -->
  <?php if (empty($_SESSION['block_clock'])): ?>
    <button type="submit" name="block_action" value="block">
      🔒 Block Clock In/Out
    </button>
  <?php else: ?>
    <button type="submit" name="block_action" value="unblock">
      🔓 Unblock Clock In/Out
    </button>
  <?php endif; ?>
<button
    type="submit"
    name="clock_in"
    <?php echo isset($_SESSION['block_clock']) ? 'disabled title="Blocked by admin"' : ''; ?>>
    ⏰ Clock In
  </button>

  <!-- Clock Out -->
  <button
    type="submit"
    name="clock_out"
    <?php echo isset($_SESSION['block_clock']) ? 'disabled title="Blocked by admin"' : ''; ?>>
    🚪 Clock Out
  </button>
		
		<div class="bouncing-container">
    <div class="bouncing-line"></div>
    <div class="bouncing-arrow">⬇️</div>
    <div class="bouncing-text">Add/Edit Remarks if You Worked Less than 8 Hours</div>
</div>
</form>

	
<!-- PROCTORING: hidden video & canvas for face detection -->
<video id="proctorVideo" autoplay playsinline muted style="display:none;"></video>
<canvas id="proctorCanvas" style="display:none;"></canvas>




	
	


<table class="table">
<thead>
    <tr>
        <th>S.N.</th>
        <?php if ($user_role !== 'admin') { ?><th>Name</th><?php } ?>
        <th>In Time</th>
        <th>Out Time</th>
        <th>Remarks</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
<?php $serial = 1; while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
<tr>
	

    <td><?php echo $serial++; ?></td>
   <td>
  <a href="#" 
     class="toggle-block" 
     data-user-id="<?php echo $row['atn_user_id']; ?>" 
     style="color: <?= $row['clockin_blocked'] ? 'red' : 'inherit' ?>;"
     title="<?= $row['clockin_blocked'] ? 'Click to UNBLOCK clock-in' : 'Click to BLOCK clock-in' ?>">
    <?= htmlspecialchars($row['fullname']) ?>
    <?php if ($row['clockin_blocked']): ?>
      <small>(blocked)</small>
    <?php endif; ?>
  </a>
</td>

	<td>
  <a href="#"
     class="employee-name toggle-block"
     data-user-id="<?= $row['atn_user_id'] ?>"
     data-aten-id="<?= $row['aten_id'] ?>"
     title="Click to toggle block">
    <?= htmlspecialchars($row['fullname']) ?>
    <?php if ((int)$row['is_read'] === 0): ?>
      <span class="unread-dot"></span>
    <?php endif; ?>
  </a>
</td>

    <td><?php echo htmlspecialchars($row['in_time']); ?></td>
    <td><?php echo !empty($row['out_time']) ? htmlspecialchars($row['out_time']) : '------'; ?></td>
    <td><?php echo !empty($row['remarks']) ? htmlspecialchars($row['remarks']) : 'No remarks'; ?></td>
    <td>
		         <form method="post" onsubmit="return confirm('Are you sure you want to delete this record?');">
                            <input type="hidden" name="aten_id" value="<?php echo $row['aten_id']; ?>">
                            <button type="submit" name="delete_attendance" class="btn btn-danger">Delete</button>
                       

        <button class="btn <?php echo empty($row['remarks']) ? 'btn-success' : 'btn-warning'; ?>" onclick="openRemarksModal(<?php echo $row['aten_id']; ?>, '<?php echo addslashes(htmlspecialchars($row['remarks'])); ?>')">
            <?php echo empty($row['remarks']) ? 'Add Remarks' : 'Edit Remarks'; ?>
        </button>
					 
					  </form>
		


  
    </td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
	     


<!-- Remarks Modal -->
<div id="remarksModal">
    <form id="remarksForm" method="post" action="">
        <input type="hidden" name="submit_remarks" value="1">
        <input type="hidden" name="aten_id" id="aten_id">
        <textarea name="remarks" id="remarksText" class="form-control" required></textarea>
        <button type="submit" class="btn btn-primary">Save</button>
        <button type="button" onclick="closeRemarksModal()" class="btn btn-secondary">Cancel</button>
    </form>
</div>
	
	<!-- Remarks Modal -->
<!-- Add/Edit Remarks Modal -->
<div id="remarksModal">
    <form id="remarksForm" method="post" action="">
        <input type="hidden" name="aten_id" id="aten_id" value="">
        <label for="remarks">Remarks:</label>
        <textarea name="remarks" id="remarks" rows="4" cols="50"></textarea><br><br>
        <button type="submit" name="submit_remarks" class="btn btn-primary">Save Remarks</button>
        <button type="button" class="btn btn-secondary" onclick="closeRemarksModal()">Cancel</button>
    </form>
</div>
	

<!-- Digital Clock Button -->
<div id="clock-button" style="position: fixed; bottom: 20px; right: 20px; background: #E65200; color: white; padding: 10px 20px; border-radius: 10px; font-size: 20px; font-family: 'Courier New', Courier, monospace; cursor: pointer; z-index: 10001;">
    <span id="digital-clock">00:00:00</span>
</div>

<!-- Chatbot for Attendance -->
<div id="chatbot" style="position: fixed; bottom: 80px; right: 20px; width: 300px; background: #f2f2f2; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.3); overflow: hidden; z-index: 10000; display: none;">
    <div style="background: #E65200; color: white; padding: 10px; text-align: center;">Attendance Chatbot 🤖</div>
    <div id="chatbot-messages" style="height: 200px; padding: 10px; overflow-y: auto; background: white; font-size: 14px;"></div>
    <div style="display: flex; border-top: 1px solid #ccc;">
        <input type="text" id="chatbot-input" placeholder="Type here..." style="flex: 1; border: none; padding: 10px; font-size: 14px;">
        <button onclick="sendChatbotMessage()" style="background: #E65200; color: white; border: none; padding: 0 15px; cursor: pointer;">➤</button>
    </div>
</div>

<!-- Script for Digital Clock and Chatbot -->
<script>
// Update the digital clock every second
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('digital-clock').textContent = `${hours}:${minutes}:${seconds}`;
}

// Call updateClock every 1000ms (1 second)
setInterval(updateClock, 1000);
// Also call immediately when page loads
updateClock();

// Toggle chatbot when clicking the clock
document.getElementById('clock-button').addEventListener('click', function() {
    const chatbot = document.getElementById('chatbot');
    if (chatbot.style.display === 'none' || chatbot.style.display === '') {
        chatbot.style.display = 'block';
    } else {
        chatbot.style.display = 'none';
    }
});

// Chatbot send message function
function sendChatbotMessage() {
    var input = document.getElementById('chatbot-input');
    var message = input.value.trim();
    if (message === '') return;

    var chatbox = document.getElementById('chatbot-messages');
    chatbox.innerHTML += '<div><b>You:</b> ' + message + '</div>';

    // Simple AI responses
    var reply = '';
    if (message.toLowerCase().includes('show my attendance')) {
        showOnlyOwnAttendance();
        reply = '✅ Showing only your attendance records!';
    } else if (message.toLowerCase().includes('hide all')) {
        hideAttendanceTable();
        reply = '✅ Attendance records hidden!';
    } else {
        reply = "🤖 Sorry, I don't understand. Try: 'Show my attendance' or 'Hide all'.";
    }

    chatbox.innerHTML += '<div style="color: green;"><b>Bot:</b> ' + reply + '</div>';
    chatbox.scrollTop = chatbox.scrollHeight;
    input.value = '';
}

// Show only user's own attendance
function showOnlyOwnAttendance() {
    var rows = document.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        var nameCell = row.querySelector('td:nth-child(2)');
        if (nameCell) {
            var nameText = nameCell.innerText.trim();
            if (nameText !== '<?php echo $user_name; ?>') {
                row.style.display = 'none';
            } else {
                row.style.display = '';
            }
        }
    });
}

// Hide the entire attendance table
function hideAttendanceTable() {
    var table = document.querySelector('table');
    if (table) {
        table.style.display = 'none';
    }
}


</script>

	<!-- JavaScript -->
<script>
function openRemarksModal(aten_id, remarks) {
    document.getElementById("aten_id").value = aten_id;
    document.getElementById("remarks").value = remarks;
    document.getElementById("remarksModal").style.display = "block";
}

function closeRemarksModal() {
    document.getElementById("remarksModal").style.display = "none";
}
</script>
	<!-- JavaScript -->
<script>
function openRemarksModal(aten_id, remarks) {
    document.getElementById("aten_id").value = aten_id;
    document.getElementById("remarks").value = remarks;
    document.getElementById("remarksModal").style.display = "block";
}

function closeRemarksModal() {
    document.getElementById("remarksModal").style.display = "none";
}
</script>
<script>
document.querySelectorAll('.toggle-block').forEach(link => {
  link.addEventListener('click', async e => {
    e.preventDefault();
    const userId = link.dataset.userId;
    try {
      const res = await fetch('toggle_block.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: user_id=${encodeURIComponent(userId)}
      });
      const json = await res.json();
      if (json.error) throw new Error(json.error);

      // Toggle UI: color, badge, title
      if (json.blocked) {
        link.style.color = 'red';
        link.title = 'Click to UNBLOCK clock-in';
        if (!link.querySelector('small')) {
          let badge = document.createElement('small');
          badge.textContent = '(blocked)';
          link.appendChild(badge);
        }
      } else {
        link.style.color = '';
        link.title = 'Click to BLOCK clock-in';
        const badge = link.querySelector('small');
        if (badge) link.removeChild(badge);
      }
    } catch (err) {
      alert('Error toggling block: ' + err.message);
    }
  });
});
</script>
<script>
(async function() {
  // —— FACE DETECTION ——
  const video = document.getElementById('proctorVideo');
  let lastSeen = Date.now();

  const faceDetector = new FaceDetection({ locateFile: f =>
    `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${f}` });
  faceDetector.setOptions({model:'short', minDetectionConfidence:0.5});
  faceDetector.onResults(results => {
    const faces = results.detections || [];
    if (faces.length) {
      lastSeen = Date.now();
    } else if (Date.now() - lastSeen > 5000) {
      blockAll('Face not detected');
      logEvent('face_lost');
    }
  });

  new Camera(video, {
    onFrame: async () => await faceDetector.send({image:video}),
    width:640, height:480
  }).start();

  // —— SCREEN RECORDING ——
  try {
    const stream = await navigator.mediaDevices.getDisplayMedia({video:true});
    logEvent('screen_start');
    stream.getTracks()[0].addEventListener('ended', () => {
      blockAll('Screen sharing stopped');
      logEvent('screen_stop');
    });
  } catch(e) {
    console.warn('Screen share denied', e);
  }

  // —— TAB-FOCUS MONITOR ——
  let blurTime = null;
  window.addEventListener('blur', () => blurTime = Date.now());
  window.addEventListener('focus', () => {
    if (blurTime && Date.now() - blurTime > 5000) {
      blockAll('Left the tab too long');
      logEvent('tab_blur');
    }
    blurTime = null;
  });

  // —— HEARTBEAT ——
  setInterval(() => logEvent('heartbeat'), 60000);

  // —— HELPERS ——
  function logEvent(type) {
    fetch('proctor-event.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({event:type, ts:Date.now()})
    }).catch(console.error);
  }
  function blockAll(reason) {
    document.querySelectorAll('button[name="clock_in"], button[name="clock_out"]').forEach(b=>b.disabled=true);
    let o = document.getElementById('proctorOverlay');
    if (!o) {
      o = document.createElement('div'); o.id='proctorOverlay';
      Object.assign(o.style, {
        position:'fixed',top:0,left:0,width:'100%',height:'100%',
        background:'rgba(255,0,0,0.5)',color:'#fff',
        display:'flex',alignItems:'center',justifyContent:'center',
        fontSize:'24px',zIndex:99999,textAlign:'center',padding:'1em'
      });
      document.body.appendChild(o);
    }
    o.innerText = `🚨 ${reason}`;
  }
})();
</script>
<?php if ($newCount > 0): ?>
  <script>
    alert("🔔 You have <?= $newCount ?> new clock-in<?= $newCount>1?'s':'' ?>!");
  </script>
<?php endif; ?>

	
	
</body>
</html>
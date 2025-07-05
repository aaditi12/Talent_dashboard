 <?php
require 'authentication.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['security_key'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['admin_id'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['user_role']; // 1 = Admin, 2 = Employee

if (isset($_POST['add_task_post'])) {
    $obj_admin->add_new_task($_POST);
}

// Handle Delete Task
if (isset($_GET['delete_task'])) {
    $delete_id = intval($_GET['delete_task']);
    $stmt = $obj_admin->manage_all_info("DELETE FROM task_info WHERE task_id = $delete_id");
    header("Location: task-info.php?msg=deleted");
    exit();
}

$page_name = "Task_Info";
include("include/sidebar.php");

$page_size = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 10;
$page_size = in_array($page_size, [10, 25, 50]) ? $page_size : 10;

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $page_size;

$base_sql = "FROM task_info a INNER JOIN tbl_admin b ON a.t_user_id = b.user_id";
$conditions = [];

if ($user_role == 2) {
    $conditions[] = "a.t_user_id = $user_id";
} else {
    if (!empty($_GET['employee_id'])) {
        $conditions[] = "a.t_user_id = " . intval($_GET['employee_id']);
    }
    if (!empty($_GET['start_date'])) {
        $conditions[] = "DATE(a.t_start_time) >= '" . $_GET['start_date'] . "'";
    }
    if (!empty($_GET['end_date'])) {
        $conditions[] = "DATE(a.t_end_time) <= '" . $_GET['end_date'] . "'";
    }
}

$where_clause = (!empty($conditions)) ? " WHERE " . implode(" AND ", $conditions) : "";

$count_sql = "SELECT COUNT(*) AS total " . $base_sql . $where_clause;
$count_stmt = $obj_admin->manage_all_info($count_sql);
$total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $page_size);

$sql = "SELECT a.*, b.fullname " . $base_sql . $where_clause . " ORDER BY a.task_id DESC LIMIT $page_size OFFSET $offset";
$info = $obj_admin->manage_all_info($sql);

$status_data = [
    'In Progress' => 0,
    'Completed' => 0,
    'Incomplete' => 0
];
$user_task_counts = [];

foreach ($obj_admin->manage_all_info("SELECT status, t_user_id FROM task_info") as $row) {
    switch ($row['status']) {
        case 1: $status_data['In Progress']++; break;
        case 2: $status_data['Completed']++; break;
        default: $status_data['Incomplete']++; break;
    }

    if ($user_role == 1) {
        if (!isset($user_task_counts[$row['t_user_id']])) {
            $user_info = $obj_admin->manage_all_info("SELECT fullname FROM tbl_admin WHERE user_id = {$row['t_user_id']}")->fetch(PDO::FETCH_ASSOC);
            $user_task_counts[$row['t_user_id']] = [
                'name' => $user_info['fullname'],
                'count' => 1
            ];
        } else {
            $user_task_counts[$row['t_user_id']]['count']++;
        }
    }
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
.btn-orange {
    background-color: #F8F8F8;
    color: #E65200;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 1rem;
    transition: background-color 0.3s ease, transform 0.2s ease;
}
.btn-orange:hover {
    background-color: #F8F8F8;
    transform: translateY(-2px);
}
tr {
    background-color: #F8F8F8;
    color: #E65200;
}
th {
    color: #E65200;
    padding: 12px;
    text-align: left;
    font-weight: bold;
    background-color: #F8F8F8;
}
tr:hover {
    background-color: #F8F8F8;
    transition: 0.3s ease-in-out;
}
	
 .statusChart {
    width: 100%;  /* Ensures the chart takes full width of its container */
    height: 400px; /* Adjust the height as needed */
}

.userChart {
    width: 100%;  /* Ensures the chart takes full width of its container */
    height: 400px; /* Adjust the height as needed */
}

</style>

<div class="container mt-3">
    <form class="row g-2 mb-4" method="get">
        <?php if ($user_role == 1): ?>
        <div class="col-md-3">
            <label for="employee_id">Filter by Employee:</label>
            <select name="employee_id" class="form-control">
                <option value="">All Employees</option>
                <?php 
                $users = $obj_admin->manage_all_info("SELECT user_id, fullname FROM tbl_admin");
                while ($row_user = $users->fetch(PDO::FETCH_ASSOC)) {
                    $selected = (isset($_GET['employee_id']) && $_GET['employee_id'] == $row_user['user_id']) ? 'selected' : '';
                    echo "<option value='{$row_user['user_id']}' $selected>{$row_user['fullname']}</option>";
                }
                ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-md-3">
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" class="form-control" value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : '' ?>">
        </div>
        <div class="col-md-3">
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" class="form-control" value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : '' ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
            <a href="task-info.php" class="btn btn-secondary">Reset Filters</a>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center my-3 px-3 flex-wrap">
    <form method="get" class="form-inline mb-2">
        <label for="page_size" class="me-2">Show:</label>
        <select name="page_size" class="form-control me-2" onchange="this.form.submit()">
            <option value="10" <?= $page_size == 10 ? 'selected' : '' ?>>10</option>
            <option value="25" <?= $page_size == 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $page_size == 50 ? 'selected' : '' ?>>50</option>
        </select>
        entries
        <?php 
        foreach ($_GET as $key => $val) {
            if (!in_array($key, ['page_size', 'page'])) {
                echo "<input type='hidden' name='{$key}' value='{$val}'>";
            }
        }
        ?>
    </form>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <div class="alert alert-success text-center">Task deleted successfully.</div>
<?php endif; ?>


<div class="container">
    <div class="d-flex justify-content-end">
        <div class="btn-group">
            <button class="btn btn-orange btn-menu" data-toggle="modal" data-target="#myModal">
                Assign New Task <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
</div>


<div class="table-responsive">
    <table class="table table-condensed table-custom">
        <thead>
            <tr>
                <th>#</th>
                <th>Task Title</th>
                <th>Assigned To</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $serial = $offset + 1;
            while ($row = $info->fetch(PDO::FETCH_ASSOC)) { ?>
            <tr>
                <td><?= $serial++ ?></td>
                <td><?= htmlspecialchars($row['t_title']) ?></td>
                <td><?= htmlspecialchars($row['fullname']) ?></td>
                <td><?= $row['t_start_time'] ?></td>
                <td><?= $row['t_end_time'] ?></td>
                <td>
                    <?= ($row['status'] == 1) 
                        ? '<small class="label label-warning">In Progress</small>' 
                        : (($row['status'] == 2) 
                            ? '<small class="label label-success">Completed</small>' 
                            : '<small class="label label-warning">Incomplete</small>') ?>
                </td>
                <td>
                    <a href="edit-task.php?task_id=<?= $row['task_id'] ?>"><i class="fas fa-edit"></i></a> 
                    <a href="task-details.php?task_id=<?= $row['task_id'] ?>"><i class="fas fa-eye"></i></a> 
                    <a href="task-info.php?delete_task=<?= $row['task_id'] ?>" onclick="return confirm('Are you sure you want to delete this task?');"><i class="fas fa-trash-alt text-danger"></i></a>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>


<!-- Task Modal -->
<div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog add-category-modal">
        <div class="modal-content rounded-0">
            <div class="modal-header rounded-0 d-flex">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h2 class="modal-title ms-auto">Assign New Task</h2>
            </div>
            <div class="modal-body rounded-0">
                <form role="form" action="" method="post" autocomplete="off">
                    <div class="form-group">
                        <label>Task Title</label>
                        <input type="text" name="task_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Task Description</label>
                        <textarea name="task_description" class="form-control" rows="5"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="text" name="t_start_time" id="t_start_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="text" name="t_end_time" id="t_end_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Completion Time</label>
                        <input type="text" name="completion_time" id="completion_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Assign To</label>
                        <select class="form-control" name="assign_to" required>
                            <option value="">Select a User...</option>
                            <?php 
                                $users = $obj_admin->manage_all_info("SELECT user_id, fullname FROM tbl_admin");
                                while ($user = $users->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$user['user_id']}'>{$user['fullname']}</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="add_task_post" class="btn btn-primary">Assign Task</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
flatpickr('#t_start_time', { enableTime: true });
flatpickr('#t_end_time', { enableTime: true });
</script>


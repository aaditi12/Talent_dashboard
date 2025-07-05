<?php
require 'authentication.php'; // admin authentication check 

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session check
$user_id = $_SESSION['admin_id'];
$user_name = $_SESSION['name'];
$security_key = $_SESSION['security_key'];

if ($user_id == NULL || $security_key == NULL) {
    header('Location: index.php');
}

// Role check
$user_role = $_SESSION['user_role'];
if($user_role != 1){
    header('Location: task-info.php');
}

// Delete employee
if (isset($_GET['delete_user'])) {
    $action_id = $_GET['admin_id'];

    $task_sql = "DELETE FROM task_info WHERE t_user_id = ?";
    $delete_task = $obj_admin->getDb()->prepare($task_sql);
    $delete_task->execute([$action_id]);

    $attendance_sql = "DELETE FROM attendance_info WHERE atn_user_id = ?";
    $delete_attendance = $obj_admin->getDb()->prepare($attendance_sql);
    $delete_attendance->execute([$action_id]);

    $sql = "DELETE FROM tbl_admin WHERE user_id = ?";
    $stmt = $obj_admin->getDb()->prepare($sql);
    $stmt->execute([$action_id]);
    header("Location: admin-manage-user.php?deleted=1");
}

// Add employee
if (isset($_POST['add_new_employee'])) {
    $fullname = $_POST['em_fullname'];
    $username = $_POST['em_username'];
    $email = $_POST['em_email'];
    $temp_password = substr(md5(time()), 0, 8);
    $user_role = 2;

    try {
        $check_sql = "SELECT * FROM tbl_admin WHERE username = ? OR email = ?";
        $check_stmt = $obj_admin->getDb()->prepare($check_sql);
        $check_stmt->execute([$username, $email]);
        if ($check_stmt->rowCount() > 0) {
            $error = "Username or Email already exists.";
        } else {
            $sql = "INSERT INTO tbl_admin (fullname, username, email, temp_password, user_role) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $obj_admin->getDb()->prepare($sql);
            $stmt->execute([$fullname, $username, $email, $temp_password, $user_role]);
            header("Location: admin-manage-user.php?success=1");
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$page_name = "Admin";
include("include/sidebar.php");
?>

<!-- Add Employee Modal -->
<div class="modal fade" id="myModal" role="dialog" style="background-color: #F8F8F8;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #F8F8F8;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h2 class="modal-title text-center">Add Employee Info</h2>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12">
            <?php if(isset($error)){ ?>
              <h5 class="alert alert-danger"><?php echo $error; ?></h5>
            <?php } ?>
            <form role="form" action="" method="post" autocomplete="off">
              <div class="form-horizontal">
                <div class="form-group">
                  <label class="control-label text-p-reset">Fullname</label>
                  <div>
                    <input type="text" placeholder="Enter Employee Name" name="em_fullname" class="form-control input-custom" required>
                  </div>
                </div>
                <div class="form-group">
                  <label class="control-label text-p-reset">Username</label>
                  <div>
                    <input type="text" placeholder="Enter Employee Username" name="em_username" class="form-control input-custom" required>
                  </div>
                </div>
                <div class="form-group">
                  <label class="control-label text-p-reset">Email</label>
                  <div>
                    <input type="email" placeholder="Enter Employee Email" name="em_email" class="form-control input-custom" required>
                  </div>
                </div>
                <div class="form-group">
                  <div class="col-sm-offset-3 col-sm-3">
                    <button type="submit" name="add_new_employee" class="btn btn-primary btn-sm rounded-0" style=" background-color: white; color: #E65200;">Add Employee</button>
                  </div>
                  <div class="col-sm-3">
                    <button type="button" class="btn btn-default btn-sm rounded-0" data-dismiss="modal" style="color: #E65200;">Cancel</button>
                  </div>
                </div>
              </div>
            </form> 
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- End Modal -->

<div class="row">
  <div class="col-md-12" style="background-color: #F8F8F8;">
    <div class="row">
      <div class="well well-custom" style="background-color: #F8F8F8;">
        <?php if(isset($error)){ ?>
        <script>
          $(document).ready(function(){
            $('#myModal').modal('show');
          });
        </script>
        <?php } ?>
        <?php if($user_role == 1){ ?>
          <div class="btn-group">
            <button class="btn btn-primary-custom btn-menu" data-toggle="modal" data-target="#myModal">Add New Employee</button>
          </div>
        <?php } ?>
        <ul class="nav nav-tabs nav-justified nav-tabs-custom">
          <li><a href="manage-admin.php" style="color: #E65200;">Manage Admin</a></li>
          <li class="active"><a href="admin-manage-user.php" style="color: #E65200;">Manage Employee</a></li>
        </ul>
        <div class="gap"></div>
        <div class="table-responsive">
          <table class="table table-condensed table-custom">
            <thead>
              <tr>
                <th>Serial No.</th>
                <th>Fullname</th>
                <th>Email</th>
                <th>Username</th>
                <th>Temp Password</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
            <?php 
              $sql = "SELECT * FROM tbl_admin WHERE user_role = 2 ORDER BY user_id DESC";
              $info = $obj_admin->manage_all_info($sql);
              $serial = 1;
              $num_row = $info->rowCount();
              if($num_row == 0){
                echo '<tr><td colspan="7">No Data found</td></tr>';
              }
              while($row = $info->fetch(PDO::FETCH_ASSOC)){
            ?>
              <tr>
                <td><?php echo $serial++; ?></td>
                <td><?php echo $row['fullname']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['username']; ?></td>
                <td><?php echo $row['temp_password']; ?></td>
                <td>
                  <a title="Update Employee" href="update-employee.php?admin_id=<?php echo $row['user_id']; ?>"><span class="glyphicon glyphicon-edit"></span></a>&nbsp;&nbsp;
                  <a title="Delete" href="?delete_user=delete_user&admin_id=<?php echo $row['user_id']; ?>" onclick=" return confirm('Are you sure you want to delete this employee?');"><span class="glyphicon glyphicon-trash"></span></a>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
if(isset($_SESSION['update_user_pass'])){
  echo '<script>alert("Password updated successfully");</script>';
  unset($_SESSION['update_user_pass']);
}
include("include/footer.php");
?>

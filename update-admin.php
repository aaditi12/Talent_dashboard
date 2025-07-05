<?php
// Start session immediately - make sure there's NOTHING before this line
session_start();

require 'authentication.php'; // Admin authentication check

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Auth check
$user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
$security_key = isset($_SESSION['security_key']) ? $_SESSION['security_key'] : null;

if ($user_id === null || $security_key === null) {
    header('Location: index.php');
    exit();
}

// Get admin_id from URL
$admin_id = isset($_GET['admin_id']) ? $_GET['admin_id'] : null;
if ($admin_id === null) {
    die('Admin ID is missing');
}

// Handle update
if (isset($_POST['update_current_employee'])) {
    // If password is not empty, hash it
    if (!empty($_POST['em_password'])) {
        $_POST['em_password'] = password_hash($_POST['em_password'], PASSWORD_DEFAULT);
    } else {
        unset($_POST['em_password']); // Don't include empty password in update
    }

    $updated = $obj_admin->update_admin_data($_POST, $admin_id);
    if ($updated) {
        echo "<script>alert('Admin updated successfully'); window.location.href = 'admin-edit.php?admin_id=" . $admin_id . "';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to update admin');</script>";
    }
}

// Fetch current data
$sql = "SELECT * FROM tbl_admin WHERE user_id = ?";
$stmt = $obj_admin->db->prepare($sql);
$stmt->execute(array($admin_id));
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$page_name = "Admin";
include("include/sidebar.php");
?>

<div class="row">
  <div class="col-md-12">
    <div class="well well-custom">
      <ul class="nav nav-tabs nav-justified nav-tabs-custom">
        <li><a href="manage-admin.php">Manage Admin</a></li>
        <li><a href="admin-manage-user.php">Manage Employee</a></li>
      </ul>
      <div class="gap"></div>

      <div class="row">
        <div class="col-md-10 col-md-offset-1">
          <div class="well" style="background:#fff !important">
            <h3 class="text-center bg-primary" style="padding: 7px;">Edit Admin</h3><br>

            <div class="row">
              <div class="col-md-7">
                <form class="form-horizontal" method="post" autocomplete="off" action="">
                  <div class="form-group">
                    <label class="control-label col-sm-2">Fullname</label>
                    <div class="col-sm-8">
                      <input type="text" value="<?php echo htmlspecialchars($row['fullname']); ?>" placeholder="Enter Full Name" name="em_fullname" class="form-control rounded-0" required>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="control-label col-sm-2">Username</label>
                    <div class="col-sm-8">
                      <input type="text" value="<?php echo htmlspecialchars($row['username']); ?>" placeholder="Enter Username" name="em_username" class="form-control rounded-0" required>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="control-label col-sm-2">Email</label>
                    <div class="col-sm-8">
                      <input type="email" value="<?php echo htmlspecialchars($row['email']); ?>" placeholder="Enter Email" name="em_email" class="form-control rounded-0" required>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="control-label col-sm-2">Password</label>
                    <div class="col-sm-8">
                      <input type="password" name="em_password" class="form-control rounded-0" placeholder="Leave blank to keep current password">
                    </div>
                  </div>

                  <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-3">
                      <button type="submit" name="update_current_employee" class="btn btn-primary-custom">Update Now</button>
                    </div>
                  </div>
                </form> 
              </div>

              <div class="col-md-5">
                <a href="admin-password-change.php?admin_id=<?php echo $row['user_id']; ?>">Change Password (Advanced)</a>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include("include/footer.php"); ?>

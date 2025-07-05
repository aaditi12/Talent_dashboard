<?php
require 'authentication.php'; // admin authentication check

// Session check
session_start();
$user_id = $_SESSION['admin_id'] ?? null;
$user_name = $_SESSION['name'] ?? null;
$security_key = $_SESSION['security_key'] ?? null;

if (!$user_id || !$security_key) {
    header('Location: index.php');
    exit();
}

// Check admin role
$user_role = $_SESSION['user_role'] ?? '';

// Get admin_id from URL or session fallback
$admin_id = $_GET['admin_id'] ?? $user_id;

$message = '';
if (isset($_POST['btn_admin_password'])) {
    $message = $obj_admin->admin_password_change($_POST, $admin_id);
}

$page_name = "Admin";
include("include/sidebar.php");
?>

<script>
function validate(id1, id2) {
    var a = document.getElementById(id1).value;
    var b = document.getElementById(id2).value;
    if (a !== b) {
        alert("Passwords do not match");
        return false;
    }
    return true;
}
</script>

<div class="row">
    <div class="col-md-12">
        <div class="well well-custom">
            <ul class="nav nav-tabs nav-justified nav-tabs-custom">
                <li class="active"><a href="manage-admin.php">Manage Admin</a></li>
                <li><a href="admin-manage-user.php">Manage User</a></li>
            </ul>
            <div class="gap"></div>
            <div class="row">
                <div class="col-md-10 col-md-offset-1">
                    <div class="well" style="background:#fff !important">
                        <h3 class="text-center bg-primary" style="padding: .5em!important">Admin - Change Password</h3><br>

                        <div class="row">
                            <div class="col-md-8 col-md-offset-2">
                                <?php if (!empty($message)): ?>
                                    <div class="alert alert-<?php echo strpos($message, 'successfully') !== false ? 'success' : 'danger'; ?>">
                                        <strong><?php echo strpos($message, 'successfully') !== false ? 'Success!' : 'Oops!'; ?></strong> <?php echo $message; ?>
                                    </div>
                                <?php endif; ?>

                                <form class="form-horizontal" role="form" action="" method="post" autocomplete="off" onsubmit="return validate('admin_new_password','admin_cnew_password')">
                                    <div class="form-group">
                                        <label class="control-label text-p-reset">Old Password</label>
                                        <div class="">
                                            <input type="password" placeholder="Enter Old Password" name="admin_old_password" id="admin_old_password" class="form-control rounded-0" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label text-p-reset">New Password</label>
                                        <div class="">
                                            <input type="password" placeholder="Enter New Password" name="admin_new_password" id="admin_new_password" class="form-control rounded-0" minlength="8" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label text-p-reset">Confirm New Password</label>
                                        <div class="">
                                            <input type="password" placeholder="Confirm New Password" name="admin_cnew_password" id="admin_cnew_password" class="form-control rounded-0" minlength="8" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-offset-4 col-sm-3">
                                            <button type="submit" name="btn_admin_password" class="btn btn-primary-custom">Change</button>
                                        </div>
                                        <div class="col-sm-3">
                                            <a href="manage-admin.php" class="btn btn-default">Cancel</a>
                                        </div>
                                    </div>
                                </form> 
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("include/footer.php"); ?>

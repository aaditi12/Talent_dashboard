<?php
session_start();
if (isset($_POST['local_ip'])) {
    $_SESSION['local_ip'] = $_POST['local_ip'];
}
?>

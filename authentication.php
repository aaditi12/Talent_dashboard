<?php

// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

ob_start();
require 'classes/admin_class.php';

$obj_admin = new Admin_Class();

// Logout handler
if (isset($_GET['logout'])) {
    $obj_admin->admin_logout();
}

// Login form handler
if (isset($_POST['login_btn'])) {
    $username   = trim($_POST['username']);
    $password   = trim($_POST['admin_password']);
    $work_mode  = isset($_POST['work_mode']) ? $_POST['work_mode'] : '';

    if (empty($username) || empty($password)) {
        $info = "Username and Password are required.";
    } else {
        // Office mode: Check WiFi SSID
        if ($work_mode === "office") {
            $office_wifi_ssid = "YourOfficeWiFiSSID"; // Replace with your office SSID
            $user_wifi_ssid = shell_exec("netsh wlan show interfaces | findstr SSID");

            if (strpos($user_wifi_ssid, $office_wifi_ssid) === false) {
                $info = "You must be connected to the office Wi-Fi to log in.";
            } else {
                $info = $obj_admin->admin_login_check($_POST);
            }
        } else {
            // Work-from-home or other modes
            $info = $obj_admin->admin_login_check($_POST);
        }

        // Optional: Handle failure message
        if (!$info) {
            $info = "Login failed. Please check your credentials.";
        }
    }
}
?>

<?php
session_start();

// Fungsi cek login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
}

// Fungsi cek role admin
function checkAdmin() {
    checkLogin();
    if ($_SESSION['role'] != 'admin') {
        header('Location: ../staff/dashboard.php');
        exit();
    }
}

// Fungsi cek role tenant
function checkTenant() {
    checkLogin();
    if ($_SESSION['role'] != 'staff') {
        header('Location: ../admin/dashboard.php');
        exit();
    }
}
?>
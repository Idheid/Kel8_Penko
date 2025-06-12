<?php
session_start();

// Redirect berdasarkan status login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: staff/dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit();
?>
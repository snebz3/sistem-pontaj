<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function checkAuth() {
    if (!isset($_SESSION['angajat_id'])) {
        header('Location: ../index.php');
        exit();
    }
}

function checkAdmin() {
    if (!isset($_SESSION['este_admin']) || $_SESSION['este_admin'] != true) {
        header('Location: ../dashboard.php');
        exit();
    }
}
?>
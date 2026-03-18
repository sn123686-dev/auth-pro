<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    logActivity($conn, $_SESSION['user_id'], "Logged out");
}

session_destroy();
header('Location: ' . APP_URL . '/login.php');
exit();
?>
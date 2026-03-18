<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'dashboard.php');
} else {
    redirect('login.php');
}
?>
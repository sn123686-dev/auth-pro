<?php
require_once '../config/config.php';

if (!isLoggedIn()) redirect('login.php');
if (!isAdmin()) redirect('dashboard.php');

if (!isset($_GET['id']) || empty($_GET['id'])) redirect('admin/users.php');

$id = (int) $_GET['id'];

// Can't delete yourself
if ($id == $_SESSION['user_id']) {
    redirect('admin/users.php');
}

// Get user name for log
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM users WHERE id = $id"));

if ($user) {
    $name = $user['name'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    logActivity($conn, $_SESSION['user_id'], "Deleted user: $name (ID: $id)");
}

redirect('admin/users.php');
?>
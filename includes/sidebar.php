<?php
if (!isLoggedIn()) return;
updateLastSeen($conn, $_SESSION['user_id']);
$current = basename($_SERVER['PHP_SELF']);
$is_admin = isAdmin();
?>

<!-- Hamburger -->
<button class="hamburger" onclick="toggleSidebar()">
    <span></span><span></span><span></span>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo APP_URL; ?>/dashboard.php" class="sidebar-logo">
            <span class="logo-icon-sm">🔐</span>
            <span class="logo-text"><?php echo APP_NAME; ?></span>
        </a>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
        <?php if (!empty($_SESSION['user_image']) && file_exists(UPLOAD_PATH . $_SESSION['user_image'])): ?>
            <img src="<?php echo UPLOAD_URL . $_SESSION['user_image']; ?>" class="sidebar-user-avatar" alt="Avatar">
        <?php else: ?>
            <div class="sidebar-user-avatar-placeholder">
                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        <div class="sidebar-user-info">
            <div class="name">
                <span class="online-dot"></span>
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </div>
            <div class="role">
                <?php echo ucfirst($_SESSION['user_role']); ?>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <!-- Main -->
        <div class="nav-section">Main</div>
        <a href="<?php echo APP_URL; ?>/dashboard.php" class="nav-item <?php echo $current == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>

        <?php if ($is_admin): ?>
        <!-- Admin -->
        <div class="nav-section">Admin</div>
        <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="nav-item <?php echo $current == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'admin') ? 'active' : ''; ?>">
            <span class="nav-icon">⚙️</span> Admin Panel
        </a>
        <a href="<?php echo APP_URL; ?>/admin/users.php" class="nav-item <?php echo $current == 'users.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👥</span> Manage Users
        </a>
        <?php endif; ?>

        <a href="<?php echo APP_URL; ?>/login_history.php" class="nav-item <?php echo $current == 'login_history.php' ? 'active' : ''; ?>">
    <span class="nav-icon">🕐</span> Login History
</a>

<a href="<?php echo APP_URL; ?>/activity_logs.php" class="nav-item <?php echo $current == 'activity_logs.php' ? 'active' : ''; ?>">
    <span class="nav-icon">📋</span> Activity Logs
</a>

        <!-- Profile -->
        <div class="nav-section">Account</div>
        <a href="<?php echo APP_URL; ?>/profile/edit.php" class="nav-item <?php echo $current == 'edit.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👤</span> Edit Profile
        </a>
        <a href="<?php echo APP_URL; ?>/profile/change_password.php" class="nav-item <?php echo $current == 'change_password.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🔑</span> Change Password
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo APP_URL; ?>/logout.php" class="logout-btn">
            <span>🚪</span> Logout
        </a>
    </div>
</div>
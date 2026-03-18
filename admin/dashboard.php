<?php
require_once '../config/config.php';

if (!isLoggedIn()) redirect('login.php');
if (!isAdmin()) redirect('dashboard.php');

$page_title = "Admin Dashboard";

// Stats
$total_users  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='admin'"))['count'];
$total_locked = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_locked=1"))['count'];
$total_logins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_history WHERE status='success'"))['count'];

// Recent users
$recent_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Recent activity
$recent_activity = mysqli_query($conn, "
    SELECT al.*, u.name, u.email
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 8
");

// Online users (active in last 5 mins)
$online_users = mysqli_query($conn, "SELECT * FROM users WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
$online_count = mysqli_num_rows($online_users);
?>

<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>⚙️ Admin Dashboard</h1>
            <p>System overview and user management</p>
        </div>
        <div class="topbar-right">
            <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-primary">👥 Manage Users</a>
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon purple">👥</div>
            <div class="stat-info">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">🟢</div>
            <div class="stat-info">
                <h3><?php echo $online_count; ?></h3>
                <p>Online Now</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">🔒</div>
            <div class="stat-info">
                <h3><?php echo $total_locked; ?></h3>
                <p>Locked Accounts</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">✅</div>
            <div class="stat-info">
                <h3><?php echo $total_logins; ?></h3>
                <p>Total Logins</p>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;" class="responsive-grid">

        <!-- Recent Users -->
        <div class="card">
            <div class="card-header">
                <h2>👥 Recent Registrations</h2>
                <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = mysqli_fetch_assoc($recent_users)): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <?php if (!empty($u['profile_image']) && file_exists(UPLOAD_PATH . $u['profile_image'])): ?>
                                    <img src="<?php echo UPLOAD_URL . $u['profile_image']; ?>" class="avatar-sm">
                                <?php else: ?>
                                    <div class="avatar-placeholder-sm">
                                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="user-cell-info">
                                    <div class="name"><?php echo htmlspecialchars($u['name']); ?></div>
                                    <div class="email"><?php echo htmlspecialchars($u['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                        <td>
                            <?php if ($u['is_locked']): ?>
                                <span class="badge badge-danger">🔒 Locked</span>
                            <?php elseif (isOnline($u['last_seen'])): ?>
                                <span class="badge badge-online">🟢 Online</span>
                            <?php else: ?>
                                <span class="badge badge-offline">⚫ Offline</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px; color:var(--gray);">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td>
                            <a href="<?php echo APP_URL; ?>/admin/edit_user.php?id=<?php echo $u['id']; ?>" class="btn btn-warning btn-sm">✏️</a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="<?php echo APP_URL; ?>/admin/delete_user.php?id=<?php echo $u['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this user?')">🗑️</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Online Users -->
        <div class="card">
            <div class="card-header">
                <h2>🟢 Online Users</h2>
                <span class="badge badge-online"><?php echo $online_count; ?> online</span>
            </div>
            <?php
            mysqli_data_seek($online_users, 0);
            if ($online_count > 0):
                while ($u = mysqli_fetch_assoc($online_users)):
            ?>
            <div class="history-item">
                <div class="user-cell">
                    <?php if (!empty($u['profile_image']) && file_exists(UPLOAD_PATH . $u['profile_image'])): ?>
                        <img src="<?php echo UPLOAD_URL . $u['profile_image']; ?>" class="avatar-sm">
                    <?php else: ?>
                        <div class="avatar-placeholder-sm">
                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="user-cell-info">
                        <div class="name"><?php echo htmlspecialchars($u['name']); ?></div>
                        <div class="email"><?php echo timeAgo($u['last_seen']); ?></div>
                    </div>
                </div>
                <span class="badge badge-online">🟢</span>
            </div>
            <?php
                endwhile;
            else:
            ?>
                <p style="color:var(--gray); text-align:center; padding:20px;">No users online</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h2>📋 Recent System Activity</h2>
        </div>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($a = mysqli_fetch_assoc($recent_activity)): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="avatar-placeholder-sm">
                                <?php echo strtoupper(substr($a['name'], 0, 1)); ?>
                            </div>
                            <div class="user-cell-info">
                                <div class="name"><?php echo htmlspecialchars($a['name']); ?></div>
                                <div class="email"><?php echo htmlspecialchars($a['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($a['action']); ?></td>
                    <td><?php echo htmlspecialchars($a['ip_address']); ?></td>
                    <td><?php echo timeAgo($a['created_at']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once 'config/config.php';

if (!isLoggedIn()) redirect('login.php');
if (isAdmin()) redirect('admin/dashboard.php');

$page_title = "Dashboard";
$user_id    = $_SESSION['user_id'];

// Get user data
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get login history
$history = mysqli_query($conn, "SELECT * FROM login_history WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");

// Get activity logs
$activities = mysqli_query($conn, "SELECT * FROM activity_logs WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");

// Count stats
$total_logins   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_history WHERE user_id = $user_id AND status = 'success'"))['count'];
$failed_logins  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_history WHERE user_id = $user_id AND status = 'failed'"))['count'];
$total_activity = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM activity_logs WHERE user_id = $user_id"))['count'];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>👋 Welcome back, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>!</h1>
            <p>Here's what's happening with your account</p>
        </div>
        <div class="topbar-right">
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon purple">👤</div>
            <div class="stat-info">
                <h3><?php echo ucfirst($user['role']); ?></h3>
                <p>Account Role</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <h3><?php echo $total_logins; ?></h3>
                <p>Successful Logins</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">❌</div>
            <div class="stat-info">
                <h3><?php echo $failed_logins; ?></h3>
                <p>Failed Attempts</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">📋</div>
            <div class="stat-info">
                <h3><?php echo $total_activity; ?></h3>
                <p>Total Activities</p>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;" class="responsive-grid">

        <!-- Profile Card -->
        <div class="card">
            <div class="card-header">
                <h2>👤 My Profile</h2>
                <a href="<?php echo APP_URL; ?>/profile/edit.php" class="btn btn-primary btn-sm">✏️ Edit</a>
            </div>
            <div class="profile-header">
                <?php if (!empty($user['profile_image']) && file_exists(UPLOAD_PATH . $user['profile_image'])): ?>
                    <img src="<?php echo UPLOAD_URL . $user['profile_image']; ?>" class="profile-avatar-lg" alt="Avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p>📧 <?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="margin-top:5px;">
                        <span class="badge badge-<?php echo $user['role']; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </p>
                    <p style="margin-top:5px; font-size:12px; color:var(--gray);">
                        Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="<?php echo APP_URL; ?>/profile/edit.php" class="btn btn-secondary btn-sm">✏️ Edit Profile</a>
                <a href="<?php echo APP_URL; ?>/profile/change_password.php" class="btn btn-secondary btn-sm">🔑 Change Password</a>
            </div>
        </div>

        <!-- Login History -->
        <div class="card">
            <div class="card-header">
                <h2>🕐 Login History</h2>
            </div>
            <?php if (mysqli_num_rows($history) > 0): ?>
                <?php while ($h = mysqli_fetch_assoc($history)): ?>
                <div class="history-item">
                    <div>
                        <div style="font-size:13px; font-weight:600;">
                            <?php echo $h['status'] === 'success' ? '✅ Successful Login' : '❌ Failed Attempt'; ?>
                        </div>
                        <div style="font-size:12px; color:var(--gray);">
                            IP: <?php echo htmlspecialchars($h['ip_address']); ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span class="badge badge-<?php echo $h['status'] === 'success' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($h['status']); ?>
                        </span>
                        <div style="font-size:11px; color:var(--gray); margin-top:3px;">
                            <?php echo timeAgo($h['created_at']); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--gray); text-align:center; padding:20px;">No login history yet.</p>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-header">
                <h2>📋 Recent Activity</h2>
            </div>
            <?php if (mysqli_num_rows($activities) > 0): ?>
                <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Action</th>
                            <th>IP Address</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($a = mysqli_fetch_assoc($activities)): ?>
                        <tr>
                            <td><?php echo $a['id']; ?></td>
                            <td><?php echo htmlspecialchars($a['action']); ?></td>
                            <td><?php echo htmlspecialchars($a['ip_address']); ?></td>
                            <td><?php echo timeAgo($a['created_at']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <p style="color:var(--gray); text-align:center; padding:20px;">No activity yet.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
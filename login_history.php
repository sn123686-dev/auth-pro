<?php
require_once 'config/config.php';

if (!isLoggedIn()) redirect('login.php');

$page_title = "Login History";
$user_id    = $_SESSION['user_id'];

// Pagination
$per_page = 15;
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

// Admins see all, users see only their own
if (isAdmin()) {
    $total_rows = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_history"))['count'];
    $history    = mysqli_query($conn, "
        SELECT lh.*, u.name, u.email
        FROM login_history lh
        JOIN users u ON lh.user_id = u.id
        ORDER BY lh.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
} else {
    $total_rows = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_history WHERE user_id = $user_id"))['count'];
    $history    = mysqli_query($conn, "
        SELECT lh.*, u.name, u.email
        FROM login_history lh
        JOIN users u ON lh.user_id = u.id
        WHERE lh.user_id = $user_id
        ORDER BY lh.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
}

$total_pages = ceil($total_rows / $per_page);

// Stats
$success_count = isAdmin()
    ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_history WHERE status='success'"))['count']
    : mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_history WHERE user_id=$user_id AND status='success'"))['count'];

$failed_count = isAdmin()
    ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_history WHERE status='failed'"))['count']
    : mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM login_history WHERE user_id=$user_id AND status='failed'"))['count'];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>🕐 Login History</h1>
            <p><?php echo isAdmin() ? 'All users login activity' : 'Your login activity'; ?></p>
        </div>
        <div class="topbar-right">
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
            <div class="stat-icon purple">🕐</div>
            <div class="stat-info">
                <h3><?php echo $total_rows; ?></h3>
                <p>Total Records</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <h3><?php echo $success_count; ?></h3>
                <p>Successful Logins</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">❌</div>
            <div class="stat-info">
                <h3><?php echo $failed_count; ?></h3>
                <p>Failed Attempts</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>📋 Login Records</h2>
            <span style="color:var(--gray); font-size:13px;"><?php echo $total_rows; ?> total records</span>
        </div>

        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <?php if (isAdmin()): ?>
                    <th>User</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <th>IP Address</th>
                    <th>Date & Time</th>
                    <th>Time Ago</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($history) > 0): ?>
                    <?php while ($h = mysqli_fetch_assoc($history)): ?>
                    <tr>
                        <td><?php echo $h['id']; ?></td>
                        <?php if (isAdmin()): ?>
                        <td>
                            <div class="user-cell">
                                <div class="avatar-placeholder-sm">
                                    <?php echo strtoupper(substr($h['name'], 0, 1)); ?>
                                </div>
                                <div class="user-cell-info">
                                    <div class="name"><?php echo htmlspecialchars($h['name']); ?></div>
                                    <div class="email"><?php echo htmlspecialchars($h['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                        <td>
                            <?php if ($h['status'] === 'success'): ?>
                                <span class="badge badge-success">✅ Success</span>
                            <?php else: ?>
                                <span class="badge badge-danger">❌ Failed</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:13px;"><?php echo htmlspecialchars($h['ip_address']); ?></td>
                        <td style="font-size:13px;"><?php echo $h['created_at']; ?></td>
                        <td style="font-size:12px; color:var(--gray);"><?php echo timeAgo($h['created_at']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:var(--gray);">
                            No login history found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>" class="page-btn">← Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>"
                    class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>" class="page-btn">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
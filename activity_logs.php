<?php
require_once 'config/config.php';

if (!isLoggedIn()) redirect('login.php');

$page_title = "Activity Logs";
$user_id    = $_SESSION['user_id'];

// Pagination
$per_page = 15;
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

// Filter
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';

// Admins see all, users see only their own
if (isAdmin()) {
    $where = "WHERE 1=1";
    if (!empty($filter)) $where .= " AND al.action LIKE '%$filter%'";

    $total_rows = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM activity_logs al $where"))['count'];
    $logs       = mysqli_query($conn, "
        SELECT al.*, u.name, u.email
        FROM activity_logs al
        JOIN users u ON al.user_id = u.id
        $where
        ORDER BY al.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
} else {
    $where = "WHERE al.user_id = $user_id";
    if (!empty($filter)) $where .= " AND al.action LIKE '%$filter%'";

    $total_rows = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM activity_logs al $where"))['count'];
    $logs       = mysqli_query($conn, "
        SELECT al.*, u.name, u.email
        FROM activity_logs al
        JOIN users u ON al.user_id = u.id
        $where
        ORDER BY al.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
}

$total_pages  = ceil($total_rows / $per_page);
$total_actions = $total_rows;
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>📋 Activity Logs</h1>
            <p><?php echo isAdmin() ? 'All system activity' : 'Your account activity'; ?></p>
        </div>
        <div class="topbar-right">
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
            <div class="stat-icon purple">📋</div>
            <div class="stat-info">
                <h3><?php echo $total_actions; ?></h3>
                <p>Total Actions</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">👤</div>
            <div class="stat-info">
                <h3><?php echo isAdmin() ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as count FROM activity_logs"))['count'] : 1; ?></h3>
                <p><?php echo isAdmin() ? 'Active Users' : 'Your Account'; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">🕐</div>
            <div class="stat-info">
                <h3><?php echo $total_pages; ?></h3>
                <p>Total Pages</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>📜 System Activity</h2>
            <span style="color:var(--gray); font-size:13px;"><?php echo $total_rows; ?> total records</span>
        </div>

        <!-- Filter -->
        <div class="filters">
            <form method="GET" action="activity_logs.php" style="display:flex; gap:10px; flex:1; flex-wrap:wrap;">
                <div class="search-box">
                    <input type="text" name="filter" placeholder="Filter by action..."
                        value="<?php echo $filter; ?>">
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <?php if (!empty($filter)): ?>
                    <a href="activity_logs.php" class="btn btn-secondary">✖ Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <?php if (isAdmin()): ?>
                    <th>User</th>
                    <?php endif; ?>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Date & Time</th>
                    <th>Time Ago</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($logs) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                    <tr>
                        <td><?php echo $log['id']; ?></td>
                        <?php if (isAdmin()): ?>
                        <td>
                            <div class="user-cell">
                                <div class="avatar-placeholder-sm">
                                    <?php echo strtoupper(substr($log['name'], 0, 1)); ?>
                                </div>
                                <div class="user-cell-info">
                                    <div class="name"><?php echo htmlspecialchars($log['name']); ?></div>
                                    <div class="email"><?php echo htmlspecialchars($log['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                        <td>
                            <?php
                            $action = htmlspecialchars($log['action']);
                            if (strpos($log['action'], 'Logged in') !== false) {
                                echo '<span class="badge badge-success">🔐</span> ' . $action;
                            } elseif (strpos($log['action'], 'Logged out') !== false) {
                                echo '<span class="badge badge-warning">🚪</span> ' . $action;
                            } elseif (strpos($log['action'], 'created') !== false) {
                                echo '<span class="badge badge-success">➕</span> ' . $action;
                            } elseif (strpos($log['action'], 'Deleted') !== false || strpos($log['action'], 'deleted') !== false) {
                                echo '<span class="badge badge-danger">🗑️</span> ' . $action;
                            } elseif (strpos($log['action'], 'Updated') !== false || strpos($log['action'], 'Changed') !== false) {
                                echo '<span class="badge badge-warning">✏️</span> ' . $action;
                            } else {
                                echo '<span class="badge badge-admin">📋</span> ' . $action;
                            }
                            ?>
                        </td>
                        <td style="font-size:13px;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        <td style="font-size:13px;"><?php echo $log['created_at']; ?></td>
                        <td style="font-size:12px; color:var(--gray);"><?php echo timeAgo($log['created_at']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:var(--gray);">
                            No activity logs found.
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
                <a href="?page=<?php echo $page-1; ?>&filter=<?php echo urlencode($filter); ?>" class="page-btn">← Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>"
                    class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&filter=<?php echo urlencode($filter); ?>" class="page-btn">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
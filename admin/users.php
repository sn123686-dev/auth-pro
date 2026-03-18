<?php
require_once '../config/config.php';

if (!isLoggedIn()) redirect('login.php');
if (!isAdmin()) redirect('dashboard.php');

$page_title = "Manage Users";

// Search & Filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';

// Pagination
$per_page = 10;
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

$where = "WHERE 1=1";
if (!empty($search)) {
    $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($filter === 'admin')  $where .= " AND role = 'admin'";
if ($filter === 'user')   $where .= " AND role = 'user'";
if ($filter === 'locked') $where .= " AND is_locked = 1";

$total_rows  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users $where"))['count'];
$total_pages = ceil($total_rows / $per_page);
$users       = mysqli_query($conn, "SELECT * FROM users $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
?>

<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>👥 Manage Users</h1>
            <p><?php echo $total_rows; ?> users found</p>
        </div>
        <div class="topbar-right">
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>All Users</h2>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="users.php" style="display:flex; gap:10px; flex:1; flex-wrap:wrap;">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by name or email..."
                        value="<?php echo $search; ?>">
                </div>
                <select name="filter" class="filter-select">
                    <option value="">All Users</option>
                    <option value="admin"  <?php echo $filter == 'admin'  ? 'selected' : ''; ?>>Admins Only</option>
                    <option value="user"   <?php echo $filter == 'user'   ? 'selected' : ''; ?>>Users Only</option>
                    <option value="locked" <?php echo $filter == 'locked' ? 'selected' : ''; ?>>Locked Accounts</option>
                </select>
                <button type="submit" class="btn btn-primary">🔍 Search</button>
                <?php if (!empty($search) || !empty($filter)): ?>
                    <a href="users.php" class="btn btn-secondary">✖ Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Seen</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($users) > 0): ?>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
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
                        <td>
                            <span class="badge badge-<?php echo $u['role']; ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
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
                            <?php echo $u['last_seen'] ? timeAgo($u['last_seen']) : 'Never'; ?>
                        </td>
                        <td style="font-size:12px; color:var(--gray);">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td>
                            <a href="<?php echo APP_URL; ?>/admin/edit_user.php?id=<?php echo $u['id']; ?>"
                                class="btn btn-warning btn-sm">✏️ Edit</a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="<?php echo APP_URL; ?>/admin/delete_user.php?id=<?php echo $u['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete <?php echo htmlspecialchars($u['name']); ?>?')">
                                    🗑️ Delete
                                </a>
                            <?php else: ?>
                                <span style="font-size:12px; color:var(--gray);">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:var(--gray);">
                            No users found.
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
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>" class="page-btn">← Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>"
                    class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>" class="page-btn">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
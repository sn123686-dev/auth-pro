<?php
require_once '../config/config.php';

if (!isLoggedIn()) redirect('login.php');
if (!isAdmin()) redirect('dashboard.php');

$page_title = "Edit User";
$error      = "";
$success    = "";

if (!isset($_GET['id']) || empty($_GET['id'])) redirect('admin/users.php');

$id   = (int) $_GET['id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) redirect('admin/users.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $name   = sanitize($_POST['name'] ?? '');
        $email  = sanitize($_POST['email'] ?? '');
        $role   = in_array($_POST['role'], ['admin', 'user']) ? $_POST['role'] : 'user';
        $locked = isset($_POST['is_locked']) ? 1 : 0;

        if (empty($name) || empty($email)) {
            $error = "Name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } else {
            // Check email unique
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
            mysqli_stmt_bind_param($check, "si", $email, $id);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $error = "Email already used by another user.";
            } else {
                // If unlocking, reset failed attempts
                if (!$locked) {
                    $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, role=?, is_locked=0, failed_attempts=0, locked_until=NULL WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $role, $id);
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, role=?, is_locked=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "sssii", $name, $email, $role, $locked, $id);
                }

                if (mysqli_stmt_execute($stmt)) {
                    logActivity($conn, $_SESSION['user_id'], "Updated user: $name (ID: $id)");
                    $success = "User updated successfully!";
                    // Refresh user data
                    $stmt2 = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
                    mysqli_stmt_bind_param($stmt2, "i", $id);
                    mysqli_stmt_execute($stmt2);
                    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
                } else {
                    $error = "Something went wrong.";
                }
            }
        }
    }
}

$csrf = generateCSRF();
?>

<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>✏️ Edit User</h1>
            <p>Update user information and permissions</p>
        </div>
        <div class="topbar-right">
            <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-secondary">← Back to Users</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:24px;" class="responsive-grid">

        <!-- User Preview -->
        <div class="card">
            <div class="card-header">
                <h2>👤 User Preview</h2>
            </div>
            <div style="text-align:center; padding:20px 0;">
                <?php if (!empty($user['profile_image']) && file_exists(UPLOAD_PATH . $user['profile_image'])): ?>
                    <img src="<?php echo UPLOAD_URL . $user['profile_image']; ?>"
                        style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid var(--primary); margin-bottom:15px;">
                <?php else: ?>
                    <div style="width:80px; height:80px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; color:white; font-size:28px; font-weight:700; margin:0 auto 15px;">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <h3 style="font-size:16px; font-weight:700;"><?php echo htmlspecialchars($user['name']); ?></h3>
                <p style="color:var(--gray); font-size:13px; margin:5px 0;"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>

                <div style="margin-top:15px; padding-top:15px; border-top:1px solid var(--border);">
                    <div style="font-size:12px; color:var(--gray); margin-bottom:5px;">
                        📅 Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                    </div>
                    <div style="font-size:12px; color:var(--gray); margin-bottom:5px;">
                        🕐 Last seen: <?php echo $user['last_seen'] ? timeAgo($user['last_seen']) : 'Never'; ?>
                    </div>
                    <div style="font-size:12px; color:var(--gray);">
                        ❌ Failed attempts: <?php echo $user['failed_attempts']; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="card">
            <div class="card-header">
                <h2>📝 Edit Details</h2>
            </div>
            <form method="POST" action="edit_user.php?id=<?php echo $id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control"
                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control">
                            <option value="user"  <?php echo $user['role'] == 'user'  ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Account Status</label>
                        <div style="display:flex; align-items:center; gap:10px; margin-top:8px;">
                            <input type="checkbox" name="is_locked" id="is_locked"
                                <?php echo $user['is_locked'] ? 'checked' : ''; ?>
                                style="width:18px; height:18px; cursor:pointer;">
                            <label for="is_locked" style="font-size:14px; text-transform:none; letter-spacing:0; cursor:pointer;">
                                Lock this account
                            </label>
                        </div>
                        <p class="form-hint">Locked users cannot login until unlocked</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                    <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
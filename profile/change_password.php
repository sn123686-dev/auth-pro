<?php
require_once '../config/config.php';

if (!isLoggedIn()) redirect('login.php');

$page_title = "Change Password";
$error      = "";
$success    = "";
$user_id    = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($current) || empty($new) || empty($confirm)) {
            $error = "All fields are required.";
        } elseif (strlen($new) < 8) {
            $error = "New password must be at least 8 characters.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } else {
            // Get current password
            $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!password_verify($current, $user['password'])) {
                $error = "Current password is incorrect.";
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt   = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);

                if (mysqli_stmt_execute($stmt)) {
                    logActivity($conn, $user_id, "Changed password");
                    $success = "Password changed successfully!";
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
            <h1>🔑 Change Password</h1>
            <p>Keep your account secure</p>
        </div>
        <div class="topbar-right">
            <a href="<?php echo APP_URL; ?>/dashboard.php" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:24px;" class="responsive-grid">

        <!-- Security Tips -->
        <div class="card">
            <div class="card-header">
                <h2>🔒 Security Tips</h2>
            </div>
            <div style="padding:10px 0;">
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <span style="font-size:20px;">✅</span>
                    <div>
                        <div style="font-size:13px; font-weight:600;">Use 8+ characters</div>
                        <div style="font-size:12px; color:var(--gray);">Longer passwords are harder to crack</div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <span style="font-size:20px;">✅</span>
                    <div>
                        <div style="font-size:13px; font-weight:600;">Mix letters & numbers</div>
                        <div style="font-size:12px; color:var(--gray);">Include uppercase, lowercase and numbers</div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <span style="font-size:20px;">✅</span>
                    <div>
                        <div style="font-size:13px; font-weight:600;">Add special characters</div>
                        <div style="font-size:12px; color:var(--gray);">Use symbols like @, #, $, !</div>
                    </div>
                </div>
                <div style="display:flex; gap:10px;">
                    <span style="font-size:20px;">❌</span>
                    <div>
                        <div style="font-size:13px; font-weight:600;">Avoid personal info</div>
                        <div style="font-size:12px; color:var(--gray);">Don't use your name or birthday</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password Form -->
        <div class="card">
            <div class="card-header">
                <h2>🔑 Update Password</h2>
            </div>
            <form method="POST" action="change_password.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password"
                        class="form-control" placeholder="Enter current password" required>
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password"
                        class="form-control" placeholder="Min 8 characters"
                        oninput="checkStrength(this.value)" required>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill" style="width:0%"></div>
                        </div>
                        <span class="strength-text" id="strengthText"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password"
                        class="form-control" placeholder="Repeat new password" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">🔑 Change Password</button>
                    <a href="<?php echo APP_URL; ?>/dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
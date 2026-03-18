<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'dashboard.php');
}

$error   = "";
$success = isset($_GET['registered']) ? "Account created successfully! Please login." : "";
$success = isset($_GET['reset']) ? "Password reset successfully! Please login." : $success;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // CSRF check
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {

        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "All fields are required.";
        } else {
            // Get user
            $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user   = mysqli_fetch_assoc($result);

            if ($user) {
                // Check if locked
                if ($user['is_locked'] && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $mins  = ceil((strtotime($user['locked_until']) - time()) / 60);
                    $error = "Account locked. Try again in $mins minute(s).";
                } else {
                    // Unlock if lock expired
                    if ($user['is_locked'] && strtotime($user['locked_until']) <= time()) {
                        mysqli_query($conn, "UPDATE users SET is_locked=0, failed_attempts=0, locked_until=NULL WHERE id={$user['id']}");
                        $user['is_locked']      = 0;
                        $user['failed_attempts'] = 0;
                    }

                    if (password_verify($password, $user['password'])) {
                        // Success — set session
                        $_SESSION['user_id']    = $user['id'];
                        $_SESSION['user_name']  = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role']  = $user['role'];
                        $_SESSION['user_image'] = $user['profile_image'];

                        // Reset failed attempts
                        mysqli_query($conn, "UPDATE users SET failed_attempts=0, is_locked=0 WHERE id={$user['id']}");

                        // Log login history
                        $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $stmt = mysqli_prepare($conn, "INSERT INTO login_history (user_id, ip_address, status) VALUES (?, ?, 'success')");
                        mysqli_stmt_bind_param($stmt, "is", $user['id'], $ip);
                        mysqli_stmt_execute($stmt);

                        // Log activity
                        logActivity($conn, $user['id'], "Logged in");

                        // Redirect
                        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');

                    } else {
                        // Failed attempt
                        $attempts = $user['failed_attempts'] + 1;

                        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                            $lock_until = date('Y-m-d H:i:s', strtotime('+' . LOCK_TIME . ' minutes'));
                            mysqli_query($conn, "UPDATE users SET failed_attempts=$attempts, is_locked=1, locked_until='$lock_until' WHERE id={$user['id']}");
                            $error = "Too many failed attempts. Account locked for " . LOCK_TIME . " minutes.";
                        } else {
                            $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
                            mysqli_query($conn, "UPDATE users SET failed_attempts=$attempts WHERE id={$user['id']}");
                            $error = "Invalid password. $remaining attempt(s) remaining.";
                        }

                        // Log failed attempt
                        $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $stmt = mysqli_prepare($conn, "INSERT INTO login_history (user_id, ip_address, status) VALUES (?, ?, 'failed')");
                        mysqli_stmt_bind_param($stmt, "is", $user['id'], $ip);
                        mysqli_stmt_execute($stmt);
                    }
                }
            } else {
                $error = "No account found with that email.";
            }
        }
    }
}

$csrf = generateCSRF();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/style.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-logo">
        <span class="logo-icon">🔐</span>
        <h1><?php echo APP_NAME; ?></h1>
        <p>Sign in to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control"
                placeholder="Enter your email"
                value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>"
                required>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control"
                placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            🔐 Sign In
        </button>
    </form>

    <div class="divider">or</div>

    <div class="auth-footer">
        Don't have an account? <a href="register.php">Create one</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
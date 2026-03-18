<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'dashboard.php');
}

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {

        $name     = sanitize($_POST['name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            // Check if email exists
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($check, "s", $email);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $error = "Email already registered. Please login.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt   = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed);

                if (mysqli_stmt_execute($stmt)) {
                    $new_id = mysqli_insert_id($conn);
                    logActivity($conn, $new_id, "Account created");
                    redirect('login.php?registered=1');
                } else {
                    $error = "Something went wrong. Please try again.";
                }
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
    <title>Register — <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/style.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-logo">
        <span class="logo-icon">🔐</span>
        <h1><?php echo APP_NAME; ?></h1>
        <p>Create your account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control"
                placeholder="Enter your full name"
                value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>"
                required>
        </div>

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
                placeholder="Min 8 characters"
                oninput="checkStrength(this.value)"
                required>
            <div class="password-strength">
                <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill" style="width:0%"></div>
                </div>
                <span class="strength-text" id="strengthText"></span>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control"
                placeholder="Repeat your password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            🚀 Create Account
        </button>
    </form>

    <div class="divider">or</div>

    <div class="auth-footer">
        Already have an account? <a href="login.php">Sign in</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
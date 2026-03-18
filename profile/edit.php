<?php
require_once '../config/config.php';

if (!isLoggedIn()) redirect('login.php');

$page_title = "Edit Profile";
$error      = "";
$success    = "";
$user_id    = $_SESSION['user_id'];

// Get current user data
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $name  = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');

        if (empty($name) || empty($email)) {
            $error = "Name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } else {
            // Check email unique
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
            mysqli_stmt_bind_param($check, "si", $email, $user_id);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $error = "Email already in use.";
            } else {
                // Handle image upload
                $image = $user['profile_image'];

                if (!empty($_FILES['profile_image']['name'])) {
                    $ext      = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    $max_size = MAX_FILE_SIZE;

                    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                        $error = "Only JPG, PNG, GIF, WEBP images allowed.";
                    } elseif ($_FILES['profile_image']['size'] > $max_size) {
                        $error = "Image must be less than 2MB.";
                    } else {
                        $new_image = uniqid() . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], UPLOAD_PATH . $new_image)) {
                            // Delete old image
                            if (!empty($image) && file_exists(UPLOAD_PATH . $image)) {
                                unlink(UPLOAD_PATH . $image);
                            }
                            $image = $new_image;
                        } else {
                            $error = "Failed to upload image.";
                        }
                    }
                }

                if (empty($error)) {
                    $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, profile_image=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $image, $user_id);

                    if (mysqli_stmt_execute($stmt)) {
                        // Update session
                        $_SESSION['user_name']  = $name;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_image'] = $image;

                        logActivity($conn, $user_id, "Updated profile");
                        $success = "Profile updated successfully!";

                        // Refresh user data
                        $stmt2 = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
                        mysqli_stmt_bind_param($stmt2, "i", $user_id);
                        mysqli_stmt_execute($stmt2);
                        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
                    } else {
                        $error = "Something went wrong.";
                    }
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
            <h1>👤 Edit Profile</h1>
            <p>Update your personal information</p>
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

        <!-- Avatar Preview -->
        <div class="card" style="text-align:center;">
            <div class="card-header">
                <h2>🖼️ Profile Picture</h2>
            </div>
            <div style="padding:20px 0;">
                <?php if (!empty($user['profile_image']) && file_exists(UPLOAD_PATH . $user['profile_image'])): ?>
                    <img src="<?php echo UPLOAD_URL . $user['profile_image']; ?>"
                        id="avatarPreview"
                        style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--primary); margin-bottom:15px;">
                <?php else: ?>
                    <div id="avatarPlaceholder" style="width:100px; height:100px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; color:white; font-size:36px; font-weight:700; margin:0 auto 15px;">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <h3 style="font-size:16px; font-weight:700;"><?php echo htmlspecialchars($user['name']); ?></h3>
                <p style="color:var(--gray); font-size:13px;"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge badge-<?php echo $user['role']; ?>" style="margin-top:8px;">
                    <?php echo ucfirst($user['role']); ?>
                </span>
                <p style="color:var(--gray); font-size:12px; margin-top:10px;">
                    Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                </p>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="card">
            <div class="card-header">
                <h2>📝 Personal Information</h2>
            </div>
            <form method="POST" action="edit.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

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
                    <label class="form-label">Profile Picture <span style="color:var(--gray); text-transform:none;">(optional, max 2MB)</span></label>
                    <input type="file" name="profile_image" class="form-control"
                        accept="image/*" onchange="previewAvatar(this)">
                    <p class="form-hint">JPG, PNG, GIF, WEBP allowed</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                    <a href="<?php echo APP_URL; ?>/dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const preview     = document.getElementById('avatarPreview');
            const placeholder = document.getElementById('avatarPlaceholder');
            if (preview) {
                preview.src = e.target.result;
            } else if (placeholder) {
                placeholder.style.display = 'none';
                const img = document.createElement('img');
                img.id    = 'avatarPreview';
                img.src   = e.target.result;
                img.style = 'width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--primary); margin-bottom:15px;';
                placeholder.parentNode.insertBefore(img, placeholder);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
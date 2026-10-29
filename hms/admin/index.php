<?php
session_start();
include 'include/config.php';

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $username = hms_post('username');
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $admin = hms_fetch_one($con, 'SELECT * FROM admin WHERE username = ?', 's', array($username));

    if ($admin && hms_password_matches($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['login'] = $username;
        $_SESSION['id'] = (int) $admin['id'];
        $_SESSION['role'] = 'admin';

        if (hms_password_needs_upgrade($admin['password'])) {
            hms_upgrade_password($con, 'admin', 'id', (int) $admin['id'], $password);
        }

        hms_log_audit($con, 'admin', (int) $admin['id'], 'admin_login', 'admin', (int) $admin['id'], 'Admin logged in.');
        hms_redirect('dashboard.php');
    }

    hms_flash('danger', 'Invalid username or password.');
    hms_redirect('index.php');
}

hms_auth_header('Admin Login', 'Admin Login', 'Manage appointments, doctors, reports, notifications, and audit logs.', '../../index.php');
?>
<form method="post" class="mt-4">
    <?php echo hms_csrf_field(); ?>
    <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="username" name="username" autocomplete="username" required>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label" for="password">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary w-100" name="submit">
        <i class="bi bi-box-arrow-in-right me-1"></i>Log In
    </button>
    <p class="text-center auth-muted mt-4 mb-0">
        <a href="../../index.php">Back to website</a>
    </p>
</form>
<?php hms_auth_footer(); ?>

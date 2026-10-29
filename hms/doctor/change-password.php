<?php
session_start();
include 'include/config.php';
hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $current = isset($_POST['cpass']) ? (string) $_POST['cpass'] : '';
    $new = isset($_POST['npass']) ? (string) $_POST['npass'] : '';
    $confirm = isset($_POST['cfpass']) ? (string) $_POST['cfpass'] : '';
    $doctor = hms_fetch_one($con, 'SELECT password FROM doctors WHERE id = ?', 'i', array($doctorId));

    if (!$doctor || !hms_password_matches($current, $doctor['password'])) {
        hms_flash('danger', 'Current password does not match.');
    } elseif ($new === '' || $new !== $confirm) {
        hms_flash('danger', 'New password and confirm password must match.');
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        hms_execute($con, 'UPDATE doctors SET password = ?, updationDate = NOW() WHERE id = ?', 'si', array($hash, $doctorId));
        hms_flash('success', 'Password changed successfully.');
    }
    hms_redirect('change-password.php');
}

hms_layout_header('Change Password', 'doctor', 'dashboard');
?>
<div class="hms-page-title">
    <div>
        <h1>Change Password</h1>
        <p class="text-muted mb-0">Protect your doctor account with a strong password.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 col-xl-6">
        <div class="hms-card p-4">
            <form method="post">
                <?php echo hms_csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label" for="cpass">Current password</label>
                    <input type="password" class="form-control" id="cpass" name="cpass" autocomplete="current-password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="npass">New password</label>
                    <input type="password" class="form-control" id="npass" name="npass" autocomplete="new-password" minlength="6" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="cfpass">Confirm password</label>
                    <input type="password" class="form-control" id="cfpass" name="cfpass" autocomplete="new-password" minlength="6" required>
                </div>
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="bi bi-shield-lock me-1"></i>Update Password
                </button>
            </form>
        </div>
    </div>
</div>
<?php hms_layout_footer(); ?>

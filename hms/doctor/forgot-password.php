<?php
session_start();
include 'include/config.php';

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $contactNo = hms_post('contactno');
    $email = hms_post('email');
    $exists = (int) hms_scalar($con, 'SELECT COUNT(*) FROM doctors WHERE contactno = ? AND docEmail = ?', 'ss', array($contactNo, $email));

    if ($exists > 0) {
        $_SESSION['cnumber'] = $contactNo;
        $_SESSION['email'] = $email;
        hms_redirect('reset-password.php');
    }

    hms_flash('danger', 'Invalid details. Please check your registered contact number and email.');
    hms_redirect('forgot-password.php');
}

hms_auth_header('Doctor Password Recovery', 'Recover Doctor Password', 'Verify your registered contact details to set a new password.', '../../index.php');
?>
<form method="post" class="mt-4">
    <?php echo hms_csrf_field(); ?>
    <div class="mb-3">
        <label class="form-label" for="contactno">Registered contact number</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="text" class="form-control" id="contactno" name="contactno" required>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label" for="email">Registered email</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary w-100" name="submit">
        <i class="bi bi-shield-lock me-1"></i>Continue
    </button>
    <p class="text-center auth-muted mt-4 mb-0">
        Remembered it? <a href="index.php">Log in</a>
    </p>
</form>
<?php hms_auth_footer(); ?>

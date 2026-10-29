<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $title = hms_post('pagetitle');
    $description = hms_post('pagedes');
    $email = hms_post('email');
    $mobile = hms_post('mobnum');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        hms_flash('danger', 'Please enter a valid contact email.');
    } else {
        $updated = hms_execute(
            $con,
            "UPDATE tblpage SET PageTitle = ?, PageDescription = ?, Email = ?, MobileNumber = ? WHERE PageType = 'contactus'",
            'ssss',
            array($title, $description, $email, $mobile)
        );
        hms_flash($updated !== false ? 'success' : 'danger', $updated !== false ? 'Contact Us content updated.' : 'Unable to update Contact Us content.');
        hms_redirect('contact.php');
    }
}

$page = hms_fetch_one($con, "SELECT * FROM tblpage WHERE PageType = 'contactus'");

hms_layout_header('Contact Content', 'admin', 'dashboard');
?>
<div class="hms-page-title">
    <div>
        <h1>Contact Content</h1>
        <p class="text-muted mb-0">Update the public website contact details.</p>
    </div>
</div>

<div class="hms-card p-4">
    <form method="post">
        <?php echo hms_csrf_field(); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="pagetitle">Page title</label>
                <input type="text" class="form-control" id="pagetitle" name="pagetitle" value="<?php echo hms_e($page['PageTitle'] ?? ''); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo hms_e($page['Email'] ?? ''); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mobnum">Mobile number</label>
                <input type="text" class="form-control" id="mobnum" name="mobnum" value="<?php echo hms_e($page['MobileNumber'] ?? ''); ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="pagedes">Page description</label>
                <textarea class="form-control" id="pagedes" name="pagedes" rows="7" required><?php echo hms_e($page['PageDescription'] ?? ''); ?></textarea>
            </div>
        </div>
        <button type="submit" name="submit" class="btn btn-primary mt-4">
            <i class="bi bi-save me-1"></i>Save Content
        </button>
    </form>
</div>
<?php hms_layout_footer(); ?>

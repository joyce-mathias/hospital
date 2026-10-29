<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $title = hms_post('pagetitle');
    $description = hms_post('pagedes');
    $updated = hms_execute($con, "UPDATE tblpage SET PageTitle = ?, PageDescription = ? WHERE PageType = 'aboutus'", 'ss', array($title, $description));
    hms_flash($updated !== false ? 'success' : 'danger', $updated !== false ? 'About Us content updated.' : 'Unable to update About Us content.');
    hms_redirect('about-us.php');
}

$page = hms_fetch_one($con, "SELECT * FROM tblpage WHERE PageType = 'aboutus'");

hms_layout_header('About Us Content', 'admin', 'dashboard');
?>
<div class="hms-page-title">
    <div>
        <h1>About Us Content</h1>
        <p class="text-muted mb-0">Update the public website About section.</p>
    </div>
</div>

<div class="hms-card p-4">
    <form method="post">
        <?php echo hms_csrf_field(); ?>
        <div class="mb-3">
            <label class="form-label" for="pagetitle">Page title</label>
            <input type="text" class="form-control" id="pagetitle" name="pagetitle" value="<?php echo hms_e($page['PageTitle'] ?? ''); ?>" required>
        </div>
        <div class="mb-4">
            <label class="form-label" for="pagedes">Page description</label>
            <textarea class="form-control" id="pagedes" name="pagedes" rows="10" required><?php echo hms_e($page['PageDescription'] ?? ''); ?></textarea>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Save Content
        </button>
    </form>
</div>
<?php hms_layout_footer(); ?>

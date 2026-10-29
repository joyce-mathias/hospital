<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

if (isset($_POST['delete'])) {
    hms_verify_csrf();
    $userId = hms_int(hms_post('id'));
    if ($userId > 0) {
        hms_execute($con, 'DELETE FROM users WHERE id = ?', 'i', array($userId));
        hms_log_audit($con, 'admin', (int) $_SESSION['id'], 'patient_user_deleted', 'users', $userId, 'Patient user deleted.');
        hms_flash('success', 'User deleted successfully.');
    }
    hms_redirect('manage-users.php');
}

$users = hms_fetch_all($con, 'SELECT * FROM users ORDER BY id DESC');

hms_layout_header('Manage Users', 'admin', 'patients');
?>
<div class="hms-page-title">
    <div>
        <h1>Manage Users</h1>
        <p class="text-muted mb-0">Review registered patient login accounts.</p>
    </div>
</div>

<div class="hms-card p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
        <h2 class="h5 mb-0">Patient Accounts</h2>
        <input type="search" class="form-control" style="max-width: 320px;" placeholder="Search users..." data-table-search="#usersTable">
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="usersTable" data-sortable>
            <thead>
            <tr>
                <th data-sort>#</th>
                <th data-sort>Name</th>
                <th data-sort>City</th>
                <th data-sort>Gender</th>
                <th data-sort>Email</th>
                <th data-sort>Registered</th>
                <th data-sort>Updated</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $index => $user) { ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td class="fw-semibold"><?php echo hms_e($user['fullName']); ?></td>
                    <td><?php echo hms_e($user['city']); ?></td>
                    <td><?php echo hms_e($user['gender']); ?></td>
                    <td><?php echo hms_e($user['email']); ?></td>
                    <td><?php echo hms_e($user['regDate']); ?></td>
                    <td><?php echo hms_e($user['updationDate'] ?: 'Not updated'); ?></td>
                    <td class="text-end">
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this user account?');">
                            <?php echo hms_csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            <?php if (!$users) { ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php hms_layout_footer(); ?>
